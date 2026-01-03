<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class CommentModerationService
{
    /**
     * Phân loại bình luận: approved | pending | reject
     * Trả về mảng: ['label' => string, 'score' => float, 'reasons' => array]
     */
    public function moderate(string $content): array
    {
        $content = trim($content);

        // 1) Nếu có GEMINI_API_KEY, dùng Gemini để phân loại (ưu tiên AI)
        $geminiKey = env('GEMINI_API_KEY');
        if (!empty($geminiKey) && mb_strlen($content) >= 3) {
            try {
                $instruction = "Bạn là bộ lọc kiểm duyệt bình luận tiếng Việt cho blog.\nHãy phân loại bình luận là 'approved' (nên đăng ngay) hoặc 'pending' (chờ duyệt).\n\nQuy tắc gắn 'pending' khi bình luận:\n- Có link lạ/quảng cáo\n- Xúc phạm/chửi tục/thiếu tôn trọng (kể cả tiếng lóng)\n- Quấy rối, công kích cá nhân, thù ghét, đe doạ\n- Khiêu dâm/tính dục không phù hợp\n- Spam/ký tự lặp nhiều, quá ngắn/không liên quan\n- Quá tiêu cực, bôi nhọ bài viết/trang/tác giả mà không có góp ý xây dựng\n\nChỉ trả về JSON hợp lệ đúng dạng: {\"label\": \"approved|pending\", \"reasons\": [\"...\"]}.";
                $payload = [
                    'contents' => [[
                        'role' => 'user',
                        'parts' => [[
                            'text' => $instruction . "\n\nBình luận:\n" . $content,
                        ]],
                    ]],
                ];

                $response = Http::timeout(8)
                    ->withHeaders(['Content-Type' => 'application/json'])
                    ->post('https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=' . $geminiKey, $payload);

                if ($response->successful()) {
                    $data = $response->json();
                    $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
                    // Cố gắng parse JSON từ phần trả lời
                    $parsed = null;
                    if (is_string($text)) {
                        $textTrim = trim($text);
                        // Nếu có code fence, loại bỏ
                        $textTrim = preg_replace('/^```(?:json)?|```$/m', '', $textTrim);
                        $parsed = json_decode($textTrim, true);
                    }
                    if (is_array($parsed) && isset($parsed['label'])) {
                        $label = $parsed['label'] === 'approved' ? 'approved' : 'pending';
                        $reasons = isset($parsed['reasons']) && is_array($parsed['reasons']) ? $parsed['reasons'] : [];

                        // Lưới an toàn: nếu AI nói approved nhưng heuristic phát hiện rủi ro -> chuyển pending
                        if ($label === 'approved') {
                            $heur = $this->evaluateHeuristics($content);
                            if ($heur['should_pending']) {
                                $label = 'pending';
                                $reasons = array_values(array_unique(array_merge($reasons, $heur['reasons'])));
                            }
                        }

                        return [
                            'label' => $label,
                            'score' => $label === 'pending' ? 0.6 : 0.0,
                            'reasons' => $reasons,
                        ];
                    }
                }
            } catch (\Throwable $th) {
                // Bỏ qua và thử các phương án khác
            }
        }

        // 2) Nếu có OpenAI API key, dùng API moderation của OpenAI
        $openAiKey = env('OPENAI_API_KEY');
        if (!empty($openAiKey) && strlen($content) >= 3) {
            try {
                $response = Http::withToken($openAiKey)
                    ->timeout(8)
                    ->post('https://api.openai.com/v1/moderations', [
                        'model' => 'omni-moderation-latest',
                        'input' => $content,
                    ]);

                if ($response->successful()) {
                    $data = $response->json();
                    $result = $data['results'][0] ?? [];
                    $flagged = (bool)($result['flagged'] ?? false);
                    $categories = $result['categories'] ?? [];
                    $reasons = [];
                    foreach ($categories as $name => $isTrue) {
                        if ($isTrue) { $reasons[] = $name; }
                    }
                    // Nếu OpenAI không flag nhưng heuristic phát hiện rủi ro -> chuyển pending
                    if (!$flagged) {
                        $heur = $this->evaluateHeuristics($content);
                        if ($heur['should_pending']) {
                            $flagged = true;
                            $reasons = array_values(array_unique(array_merge($reasons, $heur['reasons'])));
                        }
                    }

                    return [
                        'label' => $flagged ? 'pending' : 'approved',
                        'score' => $flagged ? 1.0 : 0.0,
                        'reasons' => $reasons,
                    ];
                }
            } catch (\Throwable $th) {
                // Bỏ qua và dùng heuristic
            }
        }

        // Fallback heuristic (không dùng AI)
        $heur = $this->evaluateHeuristics($content);
        if ($heur['should_pending']) {
            return [
                'label' => 'pending',
                'score' => 0.6,
                'reasons' => $heur['reasons'],
            ];
        }

        return [
            'label' => 'approved',
            'score' => 0.0,
            'reasons' => [],
        ];
    }

    /**
     * Heuristic đơn giản làm lưới an toàn khi AI bỏ sót.
     */
    private function evaluateHeuristics(string $content): array
    {
        $text = Str::lower(trim($content));
        $hasLink = (bool)preg_match('/https?:\/\//i', $text);
        $tooShort = mb_strlen($text) < 3;
        $repeatedChars = (bool)preg_match('/(.)\1{5,}/u', $text); // ví dụ: aaaaaa
        $badWords = [
            'địt', 'cặc', 'lồn', 'đụ', 'mẹ mày', 'địt mẹ', 'ngu', 'đồ ngu', 'khốn nạn', 'cứt', 'fuck', 'shit', 'bậy', 'tục',
        ];
        $badHit = null;
        foreach ($badWords as $w) {
            if (Str::contains($text, $w)) { $badHit = $w; break; }
        }

        $shouldPending = $tooShort || $repeatedChars || $badHit || $hasLink;

        return [
            'should_pending' => $shouldPending,
            'reasons' => array_values(array_filter([
                $tooShort ? 'too_short' : null,
                $repeatedChars ? 'repeated_chars' : null,
                $badHit ? ('badword:'.$badHit) : null,
                $hasLink ? 'link_detected' : null,
            ])),
        ];
    }
}


