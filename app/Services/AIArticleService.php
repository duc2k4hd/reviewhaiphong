<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AIArticleService
{
    protected $apiKey;
    protected $model;
    protected $apiUrl;

    public function __construct()
    {
        $this->apiKey = config('services.gemini.api_key');
        $this->model = config('services.gemini.model', 'gemini-2.0-flash');
        $this->apiUrl = config('services.gemini.api_url', 'https://generativelanguage.googleapis.com/v1/models/gemini-2.0-flash:generateContent');
        
        Log::info('AIArticleService initialized: apiKey = ' . ($this->apiKey ? '***' . substr($this->apiKey, -4) : 'null') . ', model = ' . $this->model . ', apiUrl = ' . $this->apiUrl);
    }

    /**
     * Test kết nối với Gemini API
     */
    public function testConnection()
    {
        try {
            Log::info('Testing AI connection...');
            
            if (empty($this->apiKey)) {
                Log::error('API key is empty');
                throw new \Exception('API key chưa được cấu hình');
            }

            Log::info('API key exists, making request to: ' . $this->apiUrl);

            // Test đơn giản hơn với prompt ngắn
            $payload = [
                'contents' => [[
                    'role' => 'user',
                    'parts' => [[
                        'text' => 'Hi'
                    ]],
                ]],
            ];

            Log::info('Request payload: ' . json_encode($payload));

            $response = Http::timeout(15)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($this->apiUrl . '?key=' . $this->apiKey, $payload);

            Log::info('Response status: ' . $response->status());
            Log::info('Response body: ' . $response->body());

            if ($response->successful()) {
                $data = $response->json();
                $result = isset($data['candidates'][0]['content']['parts'][0]['text']);
                Log::info('Connection test result: ' . ($result ? 'true' : 'false'));
                return $result;
            }

            // Log chi tiết lỗi
            if ($response->failed()) {
                Log::error('Gemini API Error Status: ' . $response->status());
                Log::error('Gemini API Error Body: ' . $response->body());
                Log::error('Gemini API Error Headers: ' . json_encode($response->headers()));
            }

            return false;

        } catch (\Exception $e) {
            Log::error('AI Service Connection Error: ' . $e->getMessage());
            Log::error('AI Service Connection Error Stack: ' . $e->getTraceAsString());
            return false;
        }
    }

    /**
     * Test connection nhanh với API key
     */
    public function quickTest()
    {
        try {
            if (empty($this->apiKey)) {
                return ['success' => false, 'message' => 'API key trống'];
            }

            // Test đơn giản nhất
            $payload = [
                'contents' => [[
                    'role' => 'user',
                    'parts' => [[
                        'text' => 'Hello'
                    ]],
                ]],
            ];

            $response = Http::timeout(10)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($this->apiUrl . '?key=' . $this->apiKey, $payload);

            if ($response->successful()) {
                return ['success' => true, 'message' => 'API key hoạt động'];
            }

            return [
                'success' => false, 
                'message' => 'API key không hoạt động',
                'status' => $response->status(),
                'body' => $response->body()
            ];

        } catch (\Exception $e) {
            return [
                'success' => false, 
                'message' => 'Lỗi kết nối: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Tạo bài viết bằng AI
     */
    public function generateArticle($prompt, $options = [])
    {
        try {
            Log::info('Generating article with AI: prompt length = ' . strlen($prompt) . ', options = ' . json_encode($options));
            
            if (empty($this->apiKey)) {
                Log::error('API key is empty in generateArticle');
                throw new \Exception('API key chưa được cấu hình');
            }

            $systemPrompt = $this->buildSystemPrompt($options);
            $fullPrompt = $systemPrompt . "\n\n" . $prompt;
            
            Log::info('Full prompt length: ' . strlen($fullPrompt));

            $payload = [
                'contents' => [[
                    'role' => 'user',
                    'parts' => [[
                        'text' => $fullPrompt
                    ]],
                ]],
            ];

            Log::info('Making request to AI service...');

            $response = Http::timeout(30)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($this->apiUrl . '?key=' . $this->apiKey, $payload);

            Log::info('AI response status: ' . $response->status());

            if (!$response->successful()) {
                Log::error('Gemini API Error: ' . $response->body());
                throw new \Exception('Không thể kết nối với AI service');
            }

            $data = $response->json();
            $content = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';

            Log::info('AI response content length: ' . strlen($content));

            if (empty($content)) {
                throw new \Exception('AI không thể tạo nội dung');
            }

            $result = $this->parseAIResponse($content, $options);
            Log::info('Article generation completed successfully');
            return $result;

        } catch (\Exception $e) {
            Log::error('AI Article Generation Error: ' . $e->getMessage());
            Log::error('AI Article Generation Stack Trace: ' . $e->getTraceAsString());
            throw $e;
        }
    }

    /**
     * Xây dựng system prompt
     */
    protected function buildSystemPrompt($options)
    {
        $category = $options['category'] ?? 'review';
        $tone = $options['tone'] ?? 'professional';
        $language = $options['language'] ?? 'Vietnamese';
        $type = $options['type'] ?? 'review';

        $toneMap = [
            'professional' => 'chuyên nghiệp, khách quan',
            'friendly' => 'thân thiện, gần gũi',
            'casual' => 'tự nhiên, thoải mái',
            'formal' => 'trang trọng, nghiêm túc'
        ];

        $categoryMap = [
            'review' => 'review địa điểm, nhà hàng, dịch vụ',
            'am-thuc' => 'ẩm thực, nhà hàng',
            'du-lich' => 'du lịch, khám phá',
            'check-in' => 'địa điểm check-in, chụp ảnh',
            'dich-vu' => 'dịch vụ, tiện ích',
            'tin-tuc' => 'tin tức, sự kiện'
        ];

        $toneText = $toneMap[$tone] ?? $tone;
        $categoryText = $categoryMap[$category] ?? $category;

        return "Bạn là một chuyên gia viết bài về Hải Phòng. Hãy tạo một bài viết {$type} về {$categoryText} với giọng văn {$toneText} bằng {$language}.

        Yêu cầu:
        1. Trả về JSON với cấu trúc đơn giản:
        {
            \"title\": \"Tiêu đề bài viết\",
            \"seo_title\": \"SEO title tối ưu (50-60 ký tự)\",
            \"seo_desc\": \"Mô tả SEO (150-160 ký tự)\",
            \"seo_keywords\": \"Từ khóa chính, từ khóa phụ\",
            \"tags\": \"tag1, tag2, tag3\",
            \"slug\": \"url-thân-thiện\",
            \"content\": \"HTML hợp lệ của bài viết\"
        }

        2. Thực hiện QUY TRÌNH NỘI BỘ (không in ra) trước khi tạo JSON:
        - Bước 1: Truy xuất thông tin liên quan đến từ khóa chính để lên outline bài viết SEO.
        Keyword to export information: [TuKhoa] = từ khóa chính được suy ra từ mô tả người dùng.
        - Bước 2: Tạo outline SEO chi tiết (H1/H2/H3) bám sát [TuKhoa].
        - Bước 3: Viết nội dung hoàn chỉnh theo outline.

        3. Nội dung trong field content PHẢI LÀ HTML HỢP LỆ, TỐI ƯU CHO NGƯỜI ĐỌC VÀ SEO:
        - Chỉ dùng các thẻ: <h1>, <h2>, <h3>, <p>, <ul>, <ol>, <li>, <strong>, <em>, <a>, <br>, <blockquote>
        - Không dùng Markdown (không **bold**, không # heading)
        - KHÔNG chèn ảnh, KHÔNG dùng <figure>, <img>, <figcaption>
        - Dùng <h2> cho các mục chính, <h3> cho mục con, đoạn văn dùng <p>
        - Danh sách dùng <ul>/<ol> với <li>
        - Độ dài bài viết khoảng 900–1500 từ (xấp xỉ 1000 từ).
        - KHÔNG dùng <h1> trong content. Content phải BẮT ĐẦU bằng <h2>.
        - Cấu trúc đề xuất:
        <h1>Tiêu đề trùng title</h1>
        <p>Mở bài 1-2 đoạn, dẫn dắt tự nhiên.</p>
        <h2>Mục chính 1</h2>
        <p>Mô tả vắn tắt.</p>
        <ul>
            <li><strong>Địa chỉ:</strong> ...</li>
            <li><strong>Giờ mở cửa:</strong> ...</li>
            <li><strong>Giá tham khảo:</strong> ...</li>
            <li><strong>Điểm nổi bật:</strong> ...</li>
        </ul>
        <h2>Mục chính 2</h2> ...
        <h2>Kết luận</h2>
        <p>Tóm tắt, CTA nhẹ nhàng.</p>
        - Ngôn ngữ: tự nhiên, mạch lạc, câu ngắn gọn; tránh lặp.
        - Không tạo dữ liệu bịa đặt; ưu tiên thông tin đúng bối cảnh Hải Phòng.

        4. SEO:
        - Title hấp dẫn, dưới 60 ký tự
        - Description mô tả chính xác nội dung
        - Keywords tự nhiên, liên quan
        - Slug ngắn gọn, dễ nhớ

        5. QUAN TRỌNG về JSON:
        - Trả về JSON đơn giản, không lồng nhau
        - Không escape các ký tự đặc biệt trong content
        - Đảm bảo JSON hợp lệ và dễ parse";
    }

    /**
     * Phân tích phản hồi từ AI
     */
    protected function parseAIResponse($content, $options)
    {
        try {
            // Loại bỏ code fence nếu có
            $content = preg_replace('/^```(?:json)?\s*\n?/m', '', $content);
            $content = preg_replace('/\n?```\s*$/m', '', $content);
            
            // Tìm JSON trong nội dung
            $jsonStart = strpos($content, '{');
            $jsonEnd = strrpos($content, '}');
            
            if ($jsonStart !== false && $jsonEnd !== false) {
                $jsonContent = substr($content, $jsonStart, $jsonEnd - $jsonStart + 1);
                $data = json_decode($jsonContent, true);
                
                if (json_last_error() === JSON_ERROR_NONE && $data) {
                    // Xử lý JSON phức tạp
                    $data = $this->processComplexJSONUltimate($data);
                    return $this->validateAndCleanData($data);
                } else {
                    // JSON có thể không hợp lệ do dấu " trong giá trị. Thử parse lỏng
                    $loose = $this->parseLooseJson($jsonContent);
                    if (!empty($loose)) {
                        return $this->validateAndCleanData($loose);
                    }
                }
            }

            // Nếu không tìm thấy JSON, tạo dữ liệu từ nội dung
            return $this->createDataFromContent($content, $options);

        } catch (\Exception $e) {
            Log::error('AI Response Parsing Error: ' . $e->getMessage());
            return $this->createDataFromContent($content, $options);
        }
    }

    /**
     * Validate và làm sạch dữ liệu
     */
    protected function validateAndCleanData($data)
    {
        $required = ['title', 'content'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                $data[$field] = $this->getDefaultValue($field);
            }
        }

        // Tạo slug nếu không có
        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['title']);
        }

        // Tạo SEO title nếu không có
        if (empty($data['seo_title'])) {
            $data['seo_title'] = Str::limit($data['title'], 60, '');
        }

        // Tạo SEO description nếu không có
        if (empty($data['seo_desc'])) {
            $data['seo_desc'] = Str::limit(strip_tags($data['content']), 160, '');
        }

        // Tạo keywords nếu không có
        if (empty($data['seo_keywords'])) {
            $data['seo_keywords'] = $this->extractKeywords($data['title'], $data['content']);
        }

        // Tạo tags nếu không có
        if (empty($data['tags'])) {
            $data['tags'] = $this->extractTags($data['title'], $data['content']);
        }

        // Làm sạch tất cả các field một cách triệt để
        foreach ($data as $key => $value) {
            if (is_string($value) && !empty($value)) {
                // Làm sạch dữ liệu
                $data[$key] = $this->deepCleanFieldValue($value);
                
                // Nếu là content, làm sạch thêm
                if ($key === 'content') {
                    // Content là HTML: không strip thẻ, chỉ làm sạch an toàn
                    $data[$key] = $this->unescapeJSONContent($data[$key]);
                    $data[$key] = $this->cleanHTMLContent($data[$key]);
                    $data[$key] = $this->enforceH2Start($data[$key]);
                }
            }
        }

        return $data;
    }

    /**
     * Loại bỏ toàn bộ <h1> và đảm bảo content không bắt đầu bằng <h1>.
     * Nếu block heading đầu tiên không phải h2, cố gắng nâng cấp thẻ đầu tiên thành h2.
     */
    protected function enforceH2Start(string $html): string
    {
        // Xóa tất cả h1
        $clean = preg_replace('/<h1[^>]*>[\s\S]*?<\/h1>/i', '', $html);

        // Nếu sau khi xóa, chưa có h2 nhưng có h3-h6, đổi thẻ heading đầu tiên thành h2
        if (!preg_match('/<h2\b/i', $clean)) {
            // Tìm heading đầu tiên bất kỳ h[3-6] và đổi thành h2
            $clean = preg_replace('/<h([3-6])([^>]*)>/', '<h2$2>', $clean, 1);
            $clean = preg_replace('/<\/h([3-6])>/', '</h2>', $clean, 1);
        }

        // Loại bỏ <p> bao ngoài h2 nếu có
        $clean = preg_replace('/<p>\s*(<h2\b[^>]*>)/i', '$1', $clean);
        $clean = preg_replace('/(<\/h2>)\s*<\/p>/i', '$1', $clean);

        return trim($clean);
    }

    /**
     * Tạo dữ liệu từ nội dung nếu không có JSON
     */
    protected function createDataFromContent($content, $options)
    {
        $title = $this->extractTitle($content) ?: 'Bài viết mới';
        
        return [
            'title' => $title,
            'seo_title' => Str::limit($title, 60, ''),
            'seo_desc' => Str::limit(strip_tags($content), 160, ''),
            'seo_keywords' => $this->extractKeywords($title, $content),
            'tags' => $this->extractTags($title, $content),
            'slug' => Str::slug($title),
            'content' => $content
        ];
    }

    /**
     * Trích xuất tiêu đề từ nội dung
     */
    protected function extractTitle($content)
    {
        // Tìm heading đầu tiên
        if (preg_match('/<h[1-6][^>]*>(.*?)<\/h[1-6]>/i', $content, $matches)) {
            return strip_tags($matches[1]);
        }

        // Tìm dòng đầu tiên không rỗng
        $lines = explode("\n", strip_tags($content));
        foreach ($lines as $line) {
            $line = trim($line);
            if (!empty($line) && strlen($line) > 10) {
                return Str::limit($line, 100, '');
            }
        }

        return 'Bài viết mới';
    }

    /**
     * Trích xuất từ khóa
     */
    protected function extractKeywords($title, $content)
    {
        $text = $title . ' ' . strip_tags($content);
        $words = preg_split('/\s+/', strtolower($text));
        $words = array_filter($words, function($word) {
            return strlen($word) > 3 && !in_array($word, ['các', 'này', 'đó', 'với', 'cho', 'từ', 'của', 'trong', 'ngoài']);
        });
        
        $wordCount = array_count_values($words);
        arsort($wordCount);
        
        $keywords = array_slice(array_keys($wordCount), 0, 5);
        return implode(', ', $keywords);
    }

    /**
     * Trích xuất tags
     */
    protected function extractTags($title, $content)
    {
        $text = $title . ' ' . strip_tags($content);
        $words = preg_split('/\s+/', strtolower($text));
        $words = array_filter($words, function($word) {
            return strlen($word) > 3 && !in_array($word, ['các', 'này', 'đó', 'với', 'cho', 'từ', 'của', 'trong', 'ngoài']);
        });
        
        $wordCount = array_count_values($words);
        arsort($wordCount);
        
        $tags = array_slice(array_keys($wordCount), 0, 3);
        return implode(', ', $tags);
    }

    /**
     * Giá trị mặc định
     */
    protected function getDefaultValue($field)
    {
        $defaults = [
            'title' => 'Bài viết mới',
            'seo_title' => 'Bài viết mới - Review Hải Phòng',
            'seo_desc' => 'Bài viết mới về Hải Phòng với thông tin chi tiết và hữu ích',
            'seo_keywords' => 'Hải Phòng, bài viết, thông tin',
            'tags' => 'Hải Phòng, bài viết',
            'slug' => 'bai-viet-moi',
            'content' => '<h2>Tiêu đề chính</h2><p>Nội dung bài viết...</p>'
        ];

        return $defaults[$field] ?? '';
    }

    /**
     * Làm sạch HTML nhưng GIỮ các thẻ hợp lệ để lưu DB và render frontend
     */
    protected function cleanHTMLContent($content)
    {
        if (!is_string($content)) {
            return '';
        }

        // Bỏ code fences nếu có
        $content = preg_replace('/^```(?:html|json)?\s*\n?/m', '', $content);
        $content = preg_replace('/\n?```\s*$/m', '', $content);

        // Giải mã entity nếu bị encode trước đó (&lt;h1&gt;...)
        $content = html_entity_decode($content, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Nếu AI trả về rác JSON bọc quanh -> loại bỏ
        $content = preg_replace('/^"[^"]+":\s*"/m', '', $content);
        $content = preg_replace('/",?\s*$/m', '', $content);

        // Chỉ cho phép một whitelist các thẻ an toàn (giữ thẻ block cần thiết)
        $allowed = '<h1><h2><h3><p><ul><ol><li><strong><em><a><br><figure><img><figcaption><blockquote>';
        $content = strip_tags($content, $allowed);

        // Loại bỏ thuộc tính nguy hiểm: on* events
        $content = preg_replace('/\son[a-z]+\s*=\s*"[^"]*"/i', '', $content);
        $content = preg_replace("/\son[a-z]+\s*=\s*'[^']*'/i", '', $content);

        // Chặn javascript: trong href/src
        $content = preg_replace('/\s(href|src)\s*=\s*"\s*javascript:[^"]*"/i', ' $1="#"', $content);
        $content = preg_replace("/\s(href|src)\s*=\s*'\s*javascript:[^']*'/i", ' $1="#"', $content);

        // Chuẩn hoá xuống dòng trong văn bản thuần
        $content = preg_replace("/\r\n?|\n/", "\n", $content);

        // Trim
        $content = trim($content);

        return $content;
    }

    /**
     * Xử lý JSON phức tạp từ AI response - cách tiếp cận cuối cùng
     */
    protected function processComplexJSONUltimate($data)
    {
        // Nếu data là string, thử parse JSON
        if (is_string($data)) {
            $parsed = json_decode($data, true);
            if ($parsed && is_array($parsed)) {
                return $this->processComplexJSONUltimate($parsed);
            }
        }
        
        // Nếu data là array, xử lý từng field
        if (is_array($data)) {
            $result = [];
            foreach ($data as $key => $value) {
                if (is_string($value)) {
                    // Làm sạch dữ liệu một cách triệt để
                    $cleanValue = $this->deepCleanFieldValue($value);
                    
                    // Kiểm tra nếu chứa JSON
                    if (strpos($cleanValue, '{') === 0 && strpos($cleanValue, '}') !== false) {
                        $parsed = json_decode($cleanValue, true);
                        if ($parsed && is_array($parsed)) {
                            // Nếu parse thành công, lấy giá trị từ JSON
                            if (isset($parsed[$key])) {
                                $result[$key] = $parsed[$key];
                            } elseif (isset($parsed['content'])) {
                                $result[$key] = $parsed['content'];
                            } elseif (isset($parsed['title'])) {
                                $result[$key] = $parsed['title'];
                            } elseif (isset($parsed['seo_title'])) {
                                $result[$key] = $parsed['seo_title'];
                            } elseif (isset($parsed['seo_desc'])) {
                                $result[$key] = $parsed['seo_desc'];
                            } elseif (isset($parsed['seo_keywords'])) {
                                $result[$key] = $parsed['seo_keywords'];
                            } elseif (isset($parsed['tags'])) {
                                $result[$key] = $parsed['tags'];
                            } elseif (isset($parsed['slug'])) {
                                $result[$key] = $parsed['slug'];
                            } else {
                                $result[$key] = $this->unescapeJSONContent($cleanValue);
                            }
                        } else {
                            $result[$key] = $this->unescapeJSONContent($cleanValue);
                        }
                    } else {
                        $result[$key] = $this->unescapeJSONContent($cleanValue);
                    }
                } else {
                    $result[$key] = $value;
                }
            }
            return $result;
        }
        
        return $data;
    }

    /**
     * Làm sạch dữ liệu field một cách triệt để
     */
    public function deepCleanFieldValue($value)
    {
        // Loại bỏ các pattern JSON một cách triệt để
        $cleanValue = $value;
        
        // Loại bỏ pattern "field": "..." ở đầu
        $cleanValue = preg_replace('/^"[^"]+":\s*"/', '', $cleanValue);
        
        // Loại bỏ pattern "field": "..." ở giữa
        $cleanValue = preg_replace('/\s*"[^"]+"\s*:\s*"/', '', $cleanValue);
        
        // Loại bỏ dấu phẩy và dấu ngoặc kép ở cuối
        $cleanValue = preg_replace('/",?\s*$/', '', $cleanValue);
        $cleanValue = preg_replace('/,\s*$/', '', $cleanValue);
        
        // Loại bỏ các ký tự JSON khác
        $cleanValue = preg_replace('/\s*"[^"]+"\s*:\s*$/', '', $cleanValue);
        $cleanValue = preg_replace('/^\s*\{\s*"/', '', $cleanValue);
        $cleanValue = preg_replace('/"\s*\}\s*$/', '', $cleanValue);
        $cleanValue = preg_replace('/^\s*\[\s*"/', '', $cleanValue);
        $cleanValue = preg_replace('/"\s*\]\s*$/', '', $cleanValue);
        $cleanValue = preg_replace('/^\s*"/', '', $cleanValue);
        $cleanValue = preg_replace('/"\s*$/', '', $cleanValue);
        
        // Loại bỏ các ký tự escape JSON
        $cleanValue = str_replace('\\n', "\n", $cleanValue);
        $cleanValue = str_replace('\\r', "\r", $cleanValue);
        $cleanValue = str_replace('\\t', "\t", $cleanValue);
        $cleanValue = str_replace('\\"', '"', $cleanValue);
        $cleanValue = str_replace('\\\\', '\\', $cleanValue);
        
        // Trim khoảng trắng
        $cleanValue = trim($cleanValue);
        
        return $cleanValue;
    }

    /**
     * Unescape JSON content để chuyển đổi các ký tự escape thành HTML thực tế
     */
    protected function unescapeJSONContent($content)
    {
        // Loại bỏ các ký tự JSON không mong muốn trước khi unescape
        $content = preg_replace('/^"[^"]+":\s*"/', '', $content);
        $content = preg_replace('/",?\s*$/', '', $content);
        $content = preg_replace('/,\s*$/', '', $content);
        
        // Unescape các ký tự JSON một cách an toàn
        $unescaped = json_decode('"' . $content . '"');
        
        // Nếu json_decode thành công, trả về kết quả
        if ($unescaped !== null) {
            return $unescaped;
        }
        
        // Nếu json_decode thất bại, thử unescape thủ công
        $content = str_replace('\\n', "\n", $content);
        $content = str_replace('\\r', "\r", $content);
        $content = str_replace('\\t', "\t", $content);
        $content = str_replace('\\"', '"', $content);
        $content = str_replace('\\\\', '\\', $content);
        
        return $content;
    }

    /**
     * Parse lỏng khi JSON có dấu nháy kép trong giá trị (ví dụ mô tả có "…")
     */
    protected function parseLooseJson(string $jsonText): array
    {
        // Cố gắng escape dấu nháy kép nằm BÊN TRONG chuỗi JSON nhưng chưa được escape
        $len = strlen($jsonText);
        $out = '';
        $inString = false;
        $escapeNext = false;
        for ($i = 0; $i < $len; $i++) {
            $ch = $jsonText[$i];
            if ($inString) {
                if ($escapeNext) {
                    $out .= $ch;
                    $escapeNext = false;
                    continue;
                }
                if ($ch === '\\') {
                    $out .= $ch;
                    $escapeNext = true;
                    continue;
                }
                if ($ch === '"') {
                    // Nhìn trước: nếu ký tự không phải kết thúc chuỗi (không theo sau bởi , } ] hoặc khoảng trắng + các ký tự đó) thì coi là dấu nháy bên trong -> escape
                    $j = $i + 1;
                    while ($j < $len && ctype_space($jsonText[$j])) { $j++; }
                    if ($j < $len && in_array($jsonText[$j], [',', '}', ']'], true)) {
                        // kết thúc chuỗi
                        $out .= '"';
                        $inString = false;
                    } else {
                        // nháy trong chuỗi -> escape
                        $out .= '\\"';
                    }
                    continue;
                }
                $out .= $ch;
            } else {
                if ($ch === '"') {
                    $inString = true;
                    $out .= $ch;
                } else {
                    $out .= $ch;
                }
            }
        }

        $data = json_decode($out, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($data)) {
            return $this->processComplexJSONUltimate($data);
        }
        return [];
    }
}
