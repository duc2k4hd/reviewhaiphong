<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\Category;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use DOMDocument;
use DOMXPath;

class VinpearlScraperController extends Controller
{
    /**
     * Hiển thị form nhập link
     */
    public function index()
    {
        $account = $this->loadAccount();
        return view('admin.vinpearl-scraper.index', compact('account'));
    }

    /**
     * Xử lý scraping từ URL vinpearl.com
     */
    public function scrape(Request $request)
    {
        $request->validate([
            'urls' => 'required|string',
            'category' => 'required|string|in:du-lich,am-thuc,check-in,dich-vu,review-tong-hop'
        ]);

        try {
            // Lấy danh sách URLs từ textarea (mỗi dòng 1 URL)
            $urlsText = $request->input('urls');
            $urls = array_filter(array_map('trim', explode("\n", $urlsText)));
            
            if (empty($urls)) {
                return back()->withErrors(['urls' => 'Vui lòng nhập ít nhất một URL hợp lệ.']);
            }

            // Validate từng URL
            $validUrls = [];
            foreach ($urls as $url) {
                $url = trim($url);
                if (empty($url)) continue;
                
                if (!filter_var($url, FILTER_VALIDATE_URL) || !preg_match('/^https?:\/\/(www\.)?vinpearl\.com\/.+/i', $url)) {
                    continue; // Bỏ qua URL không hợp lệ
                }
                
                $validUrls[] = $url;
            }

            if (empty($validUrls)) {
                return back()->withErrors(['urls' => 'Không có URL hợp lệ nào. Vui lòng kiểm tra lại định dạng URL (phải là link từ vinpearl.com).']);
            }

            $account = $this->loadAccount();
            $successCount = 0;
            $errorCount = 0;
            $errors = [];
            $totalUrls = count($validUrls);
            
            // Tăng thời gian thực thi và memory limit
            set_time_limit(0); // Không giới hạn thời gian
            ini_set('memory_limit', '1024M'); // Tăng memory limit
            
            // Xử lý từng URL với delay và flush output
            foreach ($validUrls as $index => $url) {
                try {
                    // Flush output để tránh timeout
                    if (ob_get_level() > 0) {
                        ob_flush();
                    }
                    flush();
                    
                    // Log tiến trình
                    Log::info("Vinpearl Scraper: Đang xử lý URL " . ($index + 1) . "/{$totalUrls}: {$url}");
                    
                    $categorySlug = $request->input('category', 'du-lich');
                    $result = $this->scrapeSingleUrl($url, $account, $categorySlug);
                    if ($result) {
                        $successCount++;
                    } else {
                        $errorCount++;
                        $errors[] = $url . ': Không thể cào dữ liệu';
                    }
                    
                    // Delay giữa các request để tránh quá tải (chỉ delay nếu không phải URL cuối)
                    if ($index < $totalUrls - 1) {
                        usleep(200000); // Delay 0.2 giây giữa các request (giảm để tăng tốc)
                    }
                    
                } catch (\Exception $e) {
                    $errorCount++;
                    $errorMsg = $e->getMessage();
                    $errors[] = $url . ': ' . $errorMsg;
                    Log::error('Vinpearl Scraper Error for URL ' . $url . ': ' . $errorMsg, [
                        'trace' => $e->getTraceAsString()
                    ]);
                    
                    // Tiếp tục xử lý URL tiếp theo thay vì dừng lại
                    continue;
                }
            }

            // Clear cache sau khi cào xong
            $this->clearCache();

            $message = "Đã cào thành công {$successCount} bài viết";
            if ($errorCount > 0) {
                $message .= ", {$errorCount} bài viết lỗi";
            }

            return redirect()->route('admin.posts.index')
                ->with('success', $message)
                ->with('errors', $errors);

        } catch (\Exception $e) {
            Log::error('Vinpearl Scraper Error: ' . $e->getMessage(), [
                'urls' => $request->input('urls'),
                'trace' => $e->getTraceAsString()
            ]);

            return back()->withErrors(['urls' => 'Lỗi khi cào dữ liệu: ' . $e->getMessage()]);
        }
    }

    /**
     * Cào một URL đơn lẻ
     */
    private function scrapeSingleUrl(string $url, $account, string $categorySlug = 'du-lich')
    {
        // Reset static variable cho mỗi URL mới
        self::$staticDomainAccessed = false;
        
        try {
            
            // Map category slug to name
            $categoryMap = [
                'du-lich' => 'Du lịch',
                'am-thuc' => 'Ẩm thực',
                'check-in' => 'Check-in',
                'dich-vu' => 'Dịch vụ',
                'review-tong-hop' => 'Review tổng hợp'
            ];
            
            $categoryName = $categoryMap[$categorySlug] ?? 'Du lịch';
            
            // Lấy hoặc tạo category
            $category = Category::firstOrCreate(
                ['slug' => $categorySlug],
                [
                    'name' => $categoryName,
                    'status' => 'active',
                    'sort_order' => 0
                ]
            );

            // Fetch HTML từ URL với cURL để bypass Cloudflare
            $maxRetries = 3;
            $html = null;
            
            // Cookie file để maintain session (dùng chung cho tất cả request)
            $cookieFile = storage_path('app/temp/vinpearl_cookies.txt');
            $cookieDir = dirname($cookieFile);
            if (!file_exists($cookieDir)) {
                mkdir($cookieDir, 0755, true);
            }
            
            // Helper function để tạo cURL request - Giả danh Google Bot
            $makeCurlRequest = function($targetUrl, $referer = null) use ($cookieFile) {
                $ch = curl_init();
                curl_setopt_array($ch, [
                    CURLOPT_URL => $targetUrl,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 60,
                    CURLOPT_CONNECTTIMEOUT => 30,
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_SSL_VERIFYHOST => false,
                    CURLOPT_COOKIEJAR => $cookieFile,
                    CURLOPT_COOKIEFILE => $cookieFile,
                    CURLOPT_ENCODING => 'gzip, deflate, br',
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_2_0,
                    // Giả danh Google Bot để bypass Cloudflare
                    CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)',
                    CURLOPT_HTTPHEADER => [
                        'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                        'Accept-Language: en-US,en;q=0.9',
                        'Accept-Encoding: gzip, deflate, br',
                        'Referer: ' . ($referer ?: 'https://www.google.com/'),
                        'Connection: keep-alive',
                    ],
                ]);
                return $ch;
            };
            
            for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
                try {
                    // Bước 1: Truy cập trang chủ trước để lấy cookie (chỉ lần đầu tiên)
                    if ($attempt === 1) {
                        $homeCh = $makeCurlRequest('https://vinpearl.com/', null);
                        $homeHtml = curl_exec($homeCh);
                        $homeCode = curl_getinfo($homeCh, CURLINFO_HTTP_CODE);
                        curl_close($homeCh);
                        
                        // Đợi một chút trước khi truy cập trang bài viết (giảm từ 2s xuống 0.5s)
                        usleep(500000); // 0.5 giây
                    }
                    
                    // Bước 2: Truy cập trang bài viết với cookie đã có
                    $ch = $makeCurlRequest($url, 'https://vinpearl.com/');
                    $html = curl_exec($ch);
                    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    $error = curl_error($ch);
                    curl_close($ch);
                    
                    // Kiểm tra nếu response là Cloudflare challenge page
                    if ($html && (strpos($html, 'Just a moment') !== false || 
                                 strpos($html, 'cf-browser-verification') !== false ||
                                 strpos($html, 'Checking your browser') !== false ||
                                 strpos($html, 'cf-challenge') !== false)) {
                        if ($attempt === $maxRetries) {
                            throw new \Exception('Cloudflare đang chặn truy cập (yêu cầu JavaScript challenge). Website này yêu cầu trình duyệt thật để truy cập. Vui lòng thử lại sau hoặc sử dụng proxy/headless browser.');
                        }
                        // Xóa cookie và thử lại
                        if (file_exists($cookieFile)) {
                            @unlink($cookieFile);
                        }
                        usleep(1000000 * (2 + $attempt)); // Delay tăng dần: 3s, 4s, 5s (giảm từ 7s, 9s, 11s)
                        continue;
                    }
                    
                    if ($html && $httpCode === 200) {
                        break;
                    }
                    
                    if ($attempt === $maxRetries) {
                        throw new \Exception('Không thể truy cập URL sau ' . $maxRetries . ' lần thử. HTTP Code: ' . $httpCode . ($error ? ' - ' . $error : ''));
                    }
                    
                    // Đợi trước khi retry (giảm delay)
                    usleep(1000000 * (2 + $attempt)); // 3s, 4s, 5s
                    
                } catch (\Exception $e) {
                    if ($attempt === $maxRetries) {
                        throw new \Exception('Không thể truy cập URL sau ' . $maxRetries . ' lần thử: ' . $url . ' - ' . $e->getMessage());
                    }
                    usleep(1000000 * (2 + $attempt)); // 3s, 4s, 5s
                }
            }
            
            if (empty($html)) {
                throw new \Exception('Không thể lấy nội dung từ URL này: ' . $url);
            }
            $dom = new DOMDocument();
            @$dom->loadHTML('<?xml encoding="UTF-8">' . $html);
            $xpath = new DOMXPath($dom);

            // Mảng lưu dữ liệu
            $data = [];

            // 1. Cào title và viết lại bằng AI
            $titleNodes = $xpath->query('//title');
            $originalTitle = $titleNodes->length > 0 ? trim($titleNodes->item(0)->textContent) : '';
            
            // Nếu không tìm thấy title, thử tìm trong h1
            if (empty($originalTitle)) {
                $h1Nodes = $xpath->query('//h1[contains(@class, "title")] | //h1[contains(@class, "post-title")] | //article//h1 | //main//h1');
                if ($h1Nodes->length > 0) {
                    $originalTitle = trim($h1Nodes->item(0)->textContent);
                }
            }
            
            if (empty($originalTitle)) {
                throw new \Exception('Không tìm thấy tiêu đề trên trang này: ' . $url);
            }

            // Loại bỏ các text liên quan đến vinpearl.com trước khi gửi AI
            $originalTitle = $this->removeVinpearlReferences($originalTitle);
            
            $seoTitle = $this->rewriteWithGemini($originalTitle, 'title');
            
            // Thay thế vinpearl.com thành Review Hải Phòng trong title
            $seoTitle = $this->replaceVinpearlWithReviewHaiPhong($seoTitle);
            
            $slug = Str::slug($seoTitle);
            
            // Kiểm tra slug đã tồn tại chưa
            $originalSlug = $slug;
            $counter = 1;
            while (Post::where('slug', $slug)->exists()) {
                $slug = $originalSlug . '-' . $counter;
                $counter++;
            }

            $data['seo_title'] = $seoTitle;
            $data['slug'] = $slug;
            $data['title'] = $seoTitle; // Lưu title để dùng cho alt/title ảnh

            // 2. Cào meta description và viết lại bằng AI
            $metaDescNodes = $xpath->query('//meta[@name="description"]/@content');
            $originalDesc = $metaDescNodes->length > 0 ? trim($metaDescNodes->item(0)->value) : '';
            
            if (!empty($originalDesc)) {
                $originalDesc = $this->removeVinpearlReferences($originalDesc);
                $seoDesc = $this->rewriteWithGemini($originalDesc, 'description');
            } else {
                $seoDesc = $this->rewriteWithGemini($originalTitle, 'description');
            }

            // Thay thế vinpearl.com thành Review Hải Phòng trong description
            $seoDesc = $this->replaceVinpearlWithReviewHaiPhong($seoDesc);

            $data['seo_desc'] = $seoDesc;

            // 3. Cào content - ưu tiên lấy từ div.content-wrapper.read-more > div.content
            $contentNodes = $xpath->query('//div[@class="content-wrapper read-more"]//div[@class="content"] | //div[contains(@class, "content-wrapper") and contains(@class, "read-more")]//div[contains(@class, "content")]');
            
            if ($contentNodes->length === 0) {
                // Fallback: thử các selector khác
                $contentNodes = $xpath->query('//div[@class="content"] | //div[contains(@class, "content")] | //div[contains(@class, "post-content")] | //article//div[contains(@class, "entry-content")] | //main//div[contains(@class, "content")]');
            }
            
            if ($contentNodes->length === 0) {
                // Thử tìm trong article tag
                $contentNodes = $xpath->query('//article');
            }
            
            if ($contentNodes->length === 0) {
                throw new \Exception('Không tìm thấy nội dung bài viết');
            }

            $contentDiv = $contentNodes->item(0);
            
            // 4. Lấy ảnh đại diện - ưu tiên lấy từ meta og:image
            $seoImage = null;
            
            // Ưu tiên 1: Lấy từ meta og:image (thử nhiều cách)
            $ogImageNodes = $xpath->query('//meta[@property="og:image"]/@content | //meta[@name="og:image"]/@content');
            if ($ogImageNodes->length > 0) {
                $imageUrl = trim($ogImageNodes->item(0)->value);
                if (!empty($imageUrl)) {
                    // Xử lý URL relative
                    if (strpos($imageUrl, 'http') !== 0) {
                        if (strpos($imageUrl, '//') === 0) {
                            $imageUrl = 'https:' . $imageUrl;
                        } else {
                            $imageUrl = 'https://vinpearl.com/' . ltrim($imageUrl, '/');
                        }
                    }
                    
                    Log::info('Vinpearl Scraper: Tìm thấy og:image: ' . $imageUrl);
                    $cookieFile = storage_path('app/temp/vinpearl_cookies.txt');
                    $seoImage = $this->downloadImage($imageUrl, $slug, $cookieFile);
                    if ($seoImage) {
                        Log::info('Vinpearl Scraper: Download ảnh đại diện thành công: ' . $seoImage);
                    } else {
                        Log::warning('Vinpearl Scraper: Không thể download ảnh đại diện từ og:image: ' . $imageUrl);
                    }
                } else {
                    Log::warning('Vinpearl Scraper: og:image content rỗng');
                }
            } else {
                Log::warning('Vinpearl Scraper: Không tìm thấy og:image trong HTML');
                // Debug: In ra tất cả meta tags để kiểm tra
                $allMetaNodes = $xpath->query('//meta');
                Log::info('Vinpearl Scraper: Tổng số meta tags: ' . $allMetaNodes->length);
                foreach ($allMetaNodes as $meta) {
                    $property = $meta->getAttribute('property');
                    $name = $meta->getAttribute('name');
                    $content = $meta->getAttribute('content');
                    if ($property === 'og:image' || $name === 'og:image') {
                        Log::info('Vinpearl Scraper: Tìm thấy meta với property/name: ' . ($property ?: $name) . ' = ' . $content);
                    }
                }
            }
            
            // Ưu tiên 2: Nếu không có og:image, lấy ảnh đầu tiên trong content (KHÔNG xóa)
            if (!$seoImage) {
                $firstImgNodes = $xpath->query('.//img[1]', $contentDiv);
                if ($firstImgNodes->length > 0) {
                    $firstImg = $firstImgNodes->item(0);
                    $imageUrl = $firstImg->getAttribute('src');
                    if (empty($imageUrl)) {
                        $imageUrl = $firstImg->getAttribute('data-src');
                    }
                    if (!empty($imageUrl)) {
                        Log::info('Vinpearl Scraper: Lấy ảnh đầu tiên trong content: ' . $imageUrl);
                        $cookieFile = storage_path('app/temp/vinpearl_cookies.txt');
                        $seoImage = $this->downloadImage($imageUrl, $slug, $cookieFile);
                    }
                }
            }
            
            // Ưu tiên 3: Nếu vẫn không có, thử tìm ảnh featured
            if (!$seoImage) {
                $featuredImgNodes = $xpath->query('//img[contains(@class, "featured")] | //img[contains(@class, "thumbnail")] | //img[contains(@class, "post-thumbnail")]');
                if ($featuredImgNodes->length > 0) {
                    $imageUrl = $featuredImgNodes->item(0)->getAttribute('src');
                    if (!empty($imageUrl)) {
                        $cookieFile = storage_path('app/temp/vinpearl_cookies.txt');
                        $seoImage = $this->downloadImage($imageUrl, $slug, $cookieFile);
                    }
                }
            }

            $data['seo_image'] = $seoImage;
            
            // Xử lý content (giữ nguyên ảnh đầu tiên)
            $cookieFile = storage_path('app/temp/vinpearl_cookies.txt');
            $content = $this->processContent($contentDiv, $dom, $slug, $seoTitle, $category->id, $cookieFile);
            $data['content'] = $content;

            // Extract keywords và đảm bảo encoding UTF-8
            $seoKeywords = $this->extractKeywords($seoTitle, $seoDesc);
            
            // Đảm bảo tất cả các field đều là UTF-8 và không có ký tự lỗi
            $seoTitle = mb_convert_encoding($seoTitle, 'UTF-8', 'UTF-8');
            $seoDesc = mb_convert_encoding($seoDesc, 'UTF-8', 'UTF-8');
            $content = mb_convert_encoding($content, 'UTF-8', 'UTF-8');
            $seoKeywords = mb_convert_encoding($seoKeywords, 'UTF-8', 'UTF-8');
            
            // Loại bỏ ký tự không hợp lệ
            $seoKeywords = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $seoKeywords);
            
            // Tạo bài viết
            $post = Post::create([
                'name' => $seoTitle,
                'slug' => $slug,
                'content' => $content,
                'seo_title' => $seoTitle,
                'seo_desc' => $seoDesc,
                'seo_image' => $seoImage,
                'seo_keywords' => $seoKeywords,
                'category_id' => $category->id,
                'account_id' => $account->id,
                'status' => 'published',
                'published_at' => now(),
                'views' => 0,
                'last_updated_by' => $account->id,
                'type' => '', // Field type không có default value
            ]);

            return $post;

        } catch (\Exception $e) {
            Log::error('Vinpearl Scraper Single URL Error: ' . $e->getMessage(), [
                'url' => $url,
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Loại bỏ các tham chiếu đến vinpearl.com và năm từ 2000-2030
     */
    private function removeVinpearlReferences(string $text): string
    {
        // Loại bỏ các text liên quan đến vinpearl.com
        $text = preg_replace('/\bvinpearl\.com\b/i', '', $text);
        $text = preg_replace('/\bVinpearl\b/i', '', $text);
        $text = preg_replace('/\bVINPEARL\b/i', '', $text);
        
        // Loại bỏ các năm từ 2000-2030
        $text = preg_replace('/\b(200[0-9]|201[0-9]|202[0-9]|2030)\b/', '', $text);
        
        // Loại bỏ các khoảng trắng thừa
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);
        
        return $text;
    }

    /**
     * Thay thế vinpearl.com thành Review Hải Phòng
     */
    private function replaceVinpearlWithReviewHaiPhong(string $text): string
    {
        $text = preg_replace('/\bvinpearl\.com\b/i', 'Review Hải Phòng', $text);
        $text = preg_replace('/\bVinpearl\b/i', 'Review Hải Phòng', $text);
        $text = preg_replace('/\bVINPEARL\b/i', 'Review Hải Phòng', $text);
        
        return $text;
    }

    /**
     * Viết lại text bằng Gemini AI
     */
    private function rewriteWithGemini(string $text, string $type = 'title'): string
    {
        // Thêm delay để tránh rate limit của Gemini API
        static $lastCallTime = 0;
        $minDelay = 1; // Delay tối thiểu 1 giây giữa các API call
        $timeSinceLastCall = microtime(true) - $lastCallTime;
        if ($timeSinceLastCall < $minDelay) {
            usleep(($minDelay - $timeSinceLastCall) * 1000000);
        }
        $lastCallTime = microtime(true);
        try {
            $geminiApiKey = env('GEMINI_API_KEY');
            $geminiModel = env('GEMINI_MODEL', 'gemini-2.0-pro');
            $geminiUrl = env('GEMINI_API_URL');
            
            // Nếu không có URL, tạo từ model
            if (empty($geminiUrl)) {
                $geminiUrl = 'https://generativelanguage.googleapis.com/v1/models/' . $geminiModel . ':generateContent';
            }

            if (empty($geminiApiKey)) {
                Log::warning('GEMINI_API_KEY không được cấu hình');
                return $text; // Trả về text gốc nếu không có API key
            }

            $maxLength = $type === 'title' ? 70 : 160;
            $prompt = $type === 'title' 
                ? "Viết lại tiêu đề sau thành tiêu đề SEO hay và tự nhiên dưới {$maxLength} ký tự, phù hợp với ngữ cảnh và từ khóa chính. Chỉ trả về tiêu đề, không giải thích: {$text}"
                : "Viết lại mô tả sau thành mô tả SEO hay và tự nhiên dưới {$maxLength} ký tự, phù hợp với ngữ cảnh và từ khóa chính. Chỉ trả về mô tả, không giải thích: {$text}";

            $payload = [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt]
                        ]
                    ]
                ]
            ];

            $response = Http::timeout(60)
                ->retry(2, 2000) // Retry 2 lần, delay 2 giây
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($geminiUrl . '?key=' . $geminiApiKey, $payload);

            if ($response->successful()) {
                $result = $response->json();
                if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
                    $rewritten = trim($result['candidates'][0]['content']['parts'][0]['text']);
                    // Loại bỏ dấu ngoặc kép nếu có
                    $rewritten = trim($rewritten, '"\'');
                    // Giới hạn độ dài
                    if (mb_strlen($rewritten) > $maxLength) {
                        $rewritten = mb_substr($rewritten, 0, $maxLength - 3) . '...';
                    }
                    return $rewritten;
                }
            }

            Log::warning('Gemini API không trả về kết quả hợp lệ', [
                'status' => $response->status(),
                'response' => $response->body()
            ]);
            return $text;

        } catch (\Exception $e) {
            Log::error('Gemini API Error: ' . $e->getMessage());
            return $text; // Trả về text gốc nếu có lỗi
        }
    }

    /**
     * Xử lý content: decode HTML entities, loại bỏ comment HTML, style tag, chuyển div thành p, download ảnh, thay thế link
     */
    private function processContent($contentDiv, DOMDocument $dom, string $slug, string $title, ?int $categoryId = null, ?string $cookieFile = null): string
    {
        // Lấy toàn bộ HTML từ contentDiv - lấy innerHTML (chỉ childNodes, không lấy chính div)
        $html = '';
        foreach ($contentDiv->childNodes as $child) {
            $html .= $dom->saveHTML($child);
        }
        
        // Nếu không lấy được đủ, thử lấy cả div và loại bỏ thẻ div bọc ngoài
        if (empty($html) || strlen(trim(strip_tags($html))) < 50) {
            $html = $dom->saveHTML($contentDiv);
            // Loại bỏ thẻ div bọc ngoài nếu có
            $html = preg_replace('/^<div[^>]*>/i', '', $html);
            $html = preg_replace('/<\/div>\s*$/i', '', $html);
        }

        // Decode HTML entities và URL encoding
        $html = html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $html = urldecode($html);

        // Xử lý URL sai ngay từ đầu - thay thế "statics.Review Hải Phòng.com" thành "statics.vinpearl.com"
        $html = preg_replace('/statics\.Review\s+Hải\s+Phòng\.com/i', 'statics.vinpearl.com', $html);
        $html = preg_replace('/statics\.reviewhaiphong\.com/i', 'statics.vinpearl.com', $html);
        
        // Xóa các URL sai nếu vẫn còn
        $html = preg_replace('/https?:\/\/statics\.Review\s+Hải\s+Phòng\.com[^\s"\'<>]*/i', '', $html);
        $html = preg_replace('/https?:\/\/statics\.reviewhaiphong\.com[^\s"\'<>]*/i', '', $html);

        // Loại bỏ các comment HTML (bao gồm cả {cke_protected})
        $html = preg_replace('/<!--\{cke_protected\}[^>]*?-->/is', '', $html);
        $html = preg_replace('/<!--[^>]*?-->/s', '', $html);
        
        // Loại bỏ các style tag không cần thiết
        $html = preg_replace('/<style[^>]*>.*?<\/style>/is', '', $html);
        
        // Decode các ID trong heading bị mã hóa (ví dụ: 3.+Đặt+phòng&lt;!--{cke_protected}...)
        // Tìm và decode các ID attribute trong heading
        $html = preg_replace_callback(
            '/<h([1-6])[^>]*id=["\']([^"\']+)["\'][^>]*>/i',
            function($matches) {
                $tag = $matches[1];
                $id = $matches[2];
                
                // Decode URL encoding
                $id = urldecode($id);
                // Decode HTML entities
                $id = html_entity_decode($id, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                // Loại bỏ comment HTML còn sót
                $id = preg_replace('/<!--.*?-->/s', '', $id);
                // Loại bỏ các ký tự không hợp lệ trong ID
                $id = preg_replace('/[^a-zA-Z0-9\-_\+\.]/', '', $id);
                // Tạo ID mới từ text content của heading (nếu cần)
                // Giữ lại ID gốc đã được decode
                
                return '<h' . $tag . ' id="' . $id . '">';
            },
            $html
        );

        // Tạo DOMDocument mới để xử lý
        $newDom = new DOMDocument('1.0', 'UTF-8');
        libxml_use_internal_errors(true);
        
        // Wrap trong body để xử lý dễ hơn
        $wrappedHtml = '<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body>' . $html . '</body></html>';
        @$newDom->loadHTML($wrappedHtml, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        
        $newXpath = new DOMXPath($newDom);
        
        // Loại bỏ div.widget-toc
        $tocNodes = $newXpath->query('//div[contains(@class, "widget-toc")]');
        foreach ($tocNodes as $tocNode) {
            if ($tocNode->parentNode) {
                $tocNode->parentNode->removeChild($tocNode);
            }
        }
        
        // Loại bỏ tất cả div.sku-item checked và các phần tử con
        $skuNodes = $newXpath->query('//div[contains(@class, "sku-item")]');
        foreach ($skuNodes as $skuNode) {
            if ($skuNode->parentNode) {
                $skuNode->parentNode->removeChild($skuNode);
            }
        }
        
        // Loại bỏ các đoạn quảng cáo VinWonders
        $pNodes = $newXpath->query('//p');
        $pNodesArray = [];
        foreach ($pNodes as $pNode) {
            $pNodesArray[] = $pNode;
        }
        foreach ($pNodesArray as $pNode) {
            $pText = trim($pNode->textContent);
            // Xóa các đoạn có chứa "VinWonders", "Đặt vé", "vinwonders"
            if (preg_match('/VinWonders|vinwonders|Đặt vé.*VinWonders|–\s*VinWonders/i', $pText)) {
                if ($pNode->parentNode) {
                    $pNode->parentNode->removeChild($pNode);
                }
            }
        }
        
        // Xóa các text node chứa VinWonders
        $textNodes = $newXpath->query('//text()');
        foreach ($textNodes as $textNode) {
            $text = $textNode->nodeValue;
            if (preg_match('/VinWonders|vinwonders|Đặt vé.*VinWonders|–\s*VinWonders/i', $text)) {
                $textNode->nodeValue = preg_replace('/.*VinWonders.*/i', '', $text);
                $textNode->nodeValue = preg_replace('/.*Đặt vé.*VinWonders.*/i', '', $textNode->nodeValue);
                $textNode->nodeValue = preg_replace('/.*–\s*VinWonders.*/i', '', $textNode->nodeValue);
            }
        }
        
        // Xử lý các đoạn có style tag trong strong - chuyển thành "Khám phá ngay: Tên bài + href"
        // Tìm tất cả strong nodes và xử lý
        $strongNodes = $newXpath->query('//strong');
        $strongNodesArray = [];
        foreach ($strongNodes as $strongNode) {
            $strongNodesArray[] = $strongNode;
        }
        
        foreach ($strongNodesArray as $strongNode) {
            $strongHtml = $newDom->saveHTML($strongNode);
            $strongText = trim($strongNode->textContent);
            
            // Kiểm tra nếu có style tag
            if (preg_match('/<style[^>]*>.*?<\/style>/is', $strongHtml)) {
                // Tìm link trong strong
                $linkNodes = $newXpath->query('.//a', $strongNode);
                if ($linkNodes->length > 0) {
                    $linkNode = $linkNodes->item(0);
                    $linkUrl = $linkNode->getAttribute('href');
                    $linkText = trim($linkNode->textContent);
                    
                    // Lấy bài viết random từ database để tạo internal link
                    $randomPost = $this->getRandomPost($categoryId);
                    if ($randomPost) {
                        // Luôn thay thế bằng internal link, bất kể link gốc trỏ đến đâu
                        $linkUrl = 'https://reviewhaiphong.io.vn/' . $randomPost->slug;
                        $linkText = $randomPost->seo_title;
                        
                        Log::info('Vinpearl Scraper: Tạo internal link cho "Khám phá ngay": ' . $linkUrl);
                    }
                    
                    if (!empty($linkUrl) && !empty($linkText)) {
                        // Tạo text node "Khám phá ngay: " với dấu cách
                        $textNode1 = $newDom->createTextNode('Khám phá ngay: ');
                        
                        // Tạo link element và text node cho link
                        $newLink = $newDom->createElement('a');
                        $newLink->setAttribute('href', $linkUrl);
                        $newLink->setAttribute('style', 'color: #007bff; text-decoration: underline;');
                        $linkTextNode = $newDom->createTextNode($linkText);
                        $newLink->appendChild($linkTextNode);
                        
                        // Tạo một p để chứa text và link
                        $container = $newDom->createElement('p');
                        $container->appendChild($textNode1);
                        $container->appendChild($newLink);
                        
                        // Thay thế strong node bằng container mới
                        if ($strongNode->parentNode) {
                            $strongNode->parentNode->replaceChild($container, $strongNode);
                        }
                        continue; // Đã xử lý xong, bỏ qua các bước sau
                    }
                } else {
                    // Nếu không có link nhưng có text "Khám phá ngay", vẫn tạo internal link
                    $strongTextLower = strtolower($strongText);
                    if (strpos($strongTextLower, 'khám phá ngay') !== false || strpos($strongTextLower, 'kham pha ngay') !== false) {
                        $randomPost = $this->getRandomPost($categoryId);
                        if ($randomPost) {
                            $linkUrl = 'https://reviewhaiphong.io.vn/' . $randomPost->slug;
                            $linkText = $randomPost->seo_title;
                            
                            // Tạo text node "Khám phá ngay: " với dấu cách
                            $textNode1 = $newDom->createTextNode('Khám phá ngay: ');
                            
                            // Tạo link element và text node cho link
                            $newLink = $newDom->createElement('a');
                            $newLink->setAttribute('href', $linkUrl);
                            $newLink->setAttribute('style', 'color: #007bff; text-decoration: underline;');
                            $linkTextNode = $newDom->createTextNode($linkText);
                            $newLink->appendChild($linkTextNode);
                            
                            // Tạo một p để chứa text và link
                            $container = $newDom->createElement('p');
                            $container->appendChild($textNode1);
                            $container->appendChild($newLink);
                            
                            // Thay thế strong node bằng container mới
                            if ($strongNode->parentNode) {
                                $strongNode->parentNode->replaceChild($container, $strongNode);
                            }
                            continue; // Đã xử lý xong, bỏ qua các bước sau
                        }
                    }
                }
                
                // Nếu không có link hoặc link không hợp lệ, xử lý text
                // Loại bỏ style tag và các tag HTML khác, chỉ giữ text
                $textContent = preg_replace('/<style[^>]*>.*?<\/style>/is', '', $strongText);
                $textContent = strip_tags($textContent);
                $textContent = trim($textContent);
                
                if (!empty($textContent)) {
                    // Loại bỏ >>> nếu có
                    $textContent = preg_replace('/^(&gt;&gt;&gt;|>>>)\s*/i', '', $textContent);
                    $textContent = trim($textContent);
                    
                    // Kiểm tra nếu text có "Khám phá ngay" hoặc "Xem ngay"
                    $textContentLower = strtolower($textContent);
                    $hasKhamPhaNgay = strpos($textContentLower, 'khám phá ngay') !== false || strpos($textContentLower, 'kham pha ngay') !== false;
                    $hasXemNgay = strpos($textContentLower, 'xem ngay') !== false;
                    
                    if ($hasKhamPhaNgay || $hasXemNgay) {
                        // Tách text: "Khám phá ngay"/"Xem ngay" và phần còn lại
                        $prefix = '';
                        $restText = '';
                        
                        if ($hasKhamPhaNgay) {
                            // Thử nhiều pattern để match: có dấu cách, không có dấu cách, có dấu hai chấm, không có dấu hai chấm
                            if (preg_match('/khám\s+phá\s+ngay\s*:?\s*(.+)/i', $textContent, $matches) || 
                                preg_match('/kham\s+pha\s+ngay\s*:?\s*(.+)/i', $textContent, $matches) ||
                                preg_match('/khám\s+phá\s+ngay\s*:([^\s].+)/i', $textContent, $matches) ||
                                preg_match('/kham\s+pha\s+ngay\s*:([^\s].+)/i', $textContent, $matches) ||
                                preg_match('/khám\s+phá\s+ngay([^\s].+)/i', $textContent, $matches) ||
                                preg_match('/kham\s+pha\s+ngay([^\s].+)/i', $textContent, $matches)) {
                                $prefix = 'Khám phá ngay: ';
                                $restText = trim($matches[1]);
                                Log::info('Vinpearl Scraper: Match "Khám phá ngay" - restText: ' . $restText);
                            }
                        } elseif ($hasXemNgay) {
                            if (preg_match('/xem\s+ngay\s*:?\s*(.+)/i', $textContent, $matches) ||
                                preg_match('/xem\s+ngay\s*:([^\s].+)/i', $textContent, $matches) ||
                                preg_match('/xem\s+ngay([^\s].+)/i', $textContent, $matches)) {
                                $prefix = 'Xem ngay: ';
                                $restText = trim($matches[1]);
                                Log::info('Vinpearl Scraper: Match "Xem ngay" - restText: ' . $restText);
                            }
                        }
                        
                        // Nếu có phần text sau "Khám phá ngay"/"Xem ngay", tạo internal link
                        if (!empty($prefix) && !empty($restText)) {
                            $randomPost = $this->getRandomPost($categoryId);
                            if ($randomPost) {
                                $linkUrl = 'https://reviewhaiphong.io.vn/' . $randomPost->slug;
                                $linkText = $randomPost->seo_title;
                                
                                Log::info('Vinpearl Scraper: Tạo internal link - prefix: ' . $prefix . ', linkText: ' . $linkText . ', linkUrl: ' . $linkUrl);
                                
                                // Tạo text node prefix
                                $textNode1 = $newDom->createTextNode($prefix);
                                
                                // Tạo link element và text node cho link
                                $newLink = $newDom->createElement('a');
                                $newLink->setAttribute('href', $linkUrl);
                                $newLink->setAttribute('style', 'color: #007bff; text-decoration: underline;');
                                $newLink->setAttribute('data-kham-pha-ngay-link', '1'); // Đánh dấu để không bị xóa
                                $linkTextNode = $newDom->createTextNode($linkText);
                                $newLink->appendChild($linkTextNode);
                                
                                // Tạo một p để chứa text và link
                                $container = $newDom->createElement('p');
                                $container->appendChild($textNode1);
                                $container->appendChild($newLink);
                                
                                // Thay thế strong node bằng container mới
                                if ($strongNode->parentNode) {
                                    $strongNode->parentNode->replaceChild($container, $strongNode);
                                    Log::info('Vinpearl Scraper: Đã thay thế strong node bằng container với link');
                                } else {
                                    Log::warning('Vinpearl Scraper: Không có parentNode để thay thế strong node');
                                }
                                continue; // Đã xử lý xong, bỏ qua các bước sau
                            } else {
                                Log::warning('Vinpearl Scraper: Không tìm thấy random post để tạo internal link');
                            }
                        } else {
                            Log::info('Vinpearl Scraper: Không tạo link - prefix: ' . ($prefix ?: 'empty') . ', restText: ' . ($restText ?: 'empty'));
                        }
                    }
                    
                    // Tạo strong mới với style và text đã clean
                    $newStrong = $newDom->createElement('strong', $textContent);
                    $newStrong->setAttribute('style', 'padding: 3px;');
                    
                    if ($strongNode->parentNode) {
                        $strongNode->parentNode->replaceChild($newStrong, $strongNode);
                    }
                } else {
                    // Xóa hoàn toàn nếu không có nội dung
                    if ($strongNode->parentNode) {
                        $strongNode->parentNode->removeChild($strongNode);
                    }
                }
            } else {
                // Nếu không có style tag, kiểm tra xem có text "Khám phá ngay" hoặc "Xem ngay" không
                // Loại bỏ >>> nếu có
                $strongTextClean = preg_replace('/^(&gt;&gt;&gt;|>>>)\s*/i', '', $strongText);
                $strongTextClean = trim($strongTextClean);
                
                $strongTextLower = strtolower($strongTextClean);
                $hasKhamPhaNgay = strpos($strongTextLower, 'khám phá ngay') !== false || strpos($strongTextLower, 'kham pha ngay') !== false;
                $hasXemNgay = strpos($strongTextLower, 'xem ngay') !== false;
                
                if ($hasKhamPhaNgay || $hasXemNgay) {
                    // Tách text: "Khám phá ngay"/"Xem ngay" và phần còn lại
                    $prefix = '';
                    $restText = '';
                    
                    if ($hasKhamPhaNgay) {
                        // Thử nhiều pattern để match: có dấu cách, không có dấu cách, có dấu hai chấm, không có dấu hai chấm
                        if (preg_match('/khám\s+phá\s+ngay\s*:?\s*(.+)/i', $strongTextClean, $matches) || 
                            preg_match('/kham\s+pha\s+ngay\s*:?\s*(.+)/i', $strongTextClean, $matches) ||
                            preg_match('/khám\s+phá\s+ngay\s*:([^\s].+)/i', $strongTextClean, $matches) ||
                            preg_match('/kham\s+pha\s+ngay\s*:([^\s].+)/i', $strongTextClean, $matches) ||
                            preg_match('/khám\s+phá\s+ngay([^\s].+)/i', $strongTextClean, $matches) ||
                            preg_match('/kham\s+pha\s+ngay([^\s].+)/i', $strongTextClean, $matches)) {
                            $prefix = 'Khám phá ngay: ';
                            $restText = trim($matches[1]);
                            Log::info('Vinpearl Scraper: Match "Khám phá ngay" (no style tag) - restText: ' . $restText);
                        }
                    } elseif ($hasXemNgay) {
                        if (preg_match('/xem\s+ngay\s*:?\s*(.+)/i', $strongTextClean, $matches) ||
                            preg_match('/xem\s+ngay\s*:([^\s].+)/i', $strongTextClean, $matches) ||
                            preg_match('/xem\s+ngay([^\s].+)/i', $strongTextClean, $matches)) {
                            $prefix = 'Xem ngay: ';
                            $restText = trim($matches[1]);
                            Log::info('Vinpearl Scraper: Match "Xem ngay" (no style tag) - restText: ' . $restText);
                        }
                    }
                    
                    // Nếu có phần text sau "Khám phá ngay"/"Xem ngay", tạo internal link
                    if (!empty($prefix) && !empty($restText)) {
                        $randomPost = $this->getRandomPost($categoryId);
                        if ($randomPost) {
                            $linkUrl = 'https://reviewhaiphong.io.vn/' . $randomPost->slug;
                            $linkText = $randomPost->seo_title;
                            
                            Log::info('Vinpearl Scraper: Tạo internal link (no style tag) - prefix: ' . $prefix . ', linkText: ' . $linkText . ', linkUrl: ' . $linkUrl);
                            
                            // Tạo text node prefix
                            $textNode1 = $newDom->createTextNode($prefix);
                            
                            // Tạo link element và text node cho link
                            $newLink = $newDom->createElement('a');
                            $newLink->setAttribute('href', $linkUrl);
                            $newLink->setAttribute('style', 'color: #007bff; text-decoration: underline;');
                            $linkTextNode = $newDom->createTextNode($linkText);
                            $newLink->appendChild($linkTextNode);
                            
                            // Tạo một p để chứa text và link
                            $container = $newDom->createElement('p');
                            $container->appendChild($textNode1);
                            $container->appendChild($newLink);
                            
                            // Thay thế strong node bằng container mới
                            if ($strongNode->parentNode) {
                                $strongNode->parentNode->replaceChild($container, $strongNode);
                                Log::info('Vinpearl Scraper: Đã thay thế strong node (no style tag) bằng container với link');
                            } else {
                                Log::warning('Vinpearl Scraper: Không có parentNode để thay thế strong node (no style tag)');
                            }
                            continue; // Đã xử lý xong, bỏ qua các bước sau
                        } else {
                            Log::warning('Vinpearl Scraper: Không tìm thấy random post để tạo internal link (no style tag)');
                        }
                    } else {
                        Log::info('Vinpearl Scraper: Không tạo link (no style tag) - prefix: ' . ($prefix ?: 'empty') . ', restText: ' . ($restText ?: 'empty') . ', strongTextClean: ' . substr($strongTextClean, 0, 100));
                    }
                }
                
                // Nếu không có "Khám phá ngay" hoặc "Xem ngay", chỉ thêm style cho strong
                $currentStyle = $strongNode->getAttribute('style');
                if (empty($currentStyle)) {
                    $strongNode->setAttribute('style', 'padding: 3px;');
                } else {
                    // Nếu đã có style, thêm padding vào nếu chưa có
                    if (strpos($currentStyle, 'padding') === false) {
                        $currentStyle = trim($currentStyle);
                        if (!empty($currentStyle) && !preg_match('/;\s*$/', $currentStyle)) {
                            $currentStyle .= ';';
                        }
                        $strongNode->setAttribute('style', $currentStyle . ' padding: 3px;');
                    }
                    // Nếu đã có padding rồi, để phần cuối cùng xử lý để đảm bảo đúng format
                }
            }
        }
        
        // Xử lý tất cả ảnh trước (download và thay đổi src)
        $imgNodes = $newXpath->query('//img');
        $imgCount = 0;
        foreach ($imgNodes as $img) {
            $src = $img->getAttribute('src');
            if (empty($src)) {
                $src = $img->getAttribute('data-src');
            }
            if (!empty($src)) {
                // Thay thế URL sai "statics.Review Hải Phòng.com" thành URL đúng
                $src = preg_replace('/statics\.Review\s+Hải\s+Phòng\.com/i', 'statics.vinpearl.com', $src);
                $src = preg_replace('/statics\.reviewhaiphong\.com/i', 'statics.vinpearl.com', $src);
                
                // Delay giữa các request download ảnh để tránh bị chặn
                if ($imgCount > 0) {
                    usleep(300000); // Delay 0.3 giây giữa các ảnh
                }
                
                // Download ảnh
                $imageName = $this->downloadImage($src, $slug, $cookieFile);
                if ($imageName) {
                    $img->setAttribute('src', 'https://reviewhaiphong.io.vn/client/assets/images/posts/' . $imageName);
                    $img->setAttribute('alt', $title);
                    $img->setAttribute('title', $title);
                    $imgCount++;
                } else {
                    // Nếu không download được, xóa ảnh
                    if ($img->parentNode) {
                        $img->parentNode->removeChild($img);
                    }
                }
            }
        }
        

        // Xử lý các link "Xem thêm" - thay thế bằng bài viết random từ database
        $this->replaceXemThemLinks($newXpath, $newDom, $categoryId);

        // Loại bỏ tất cả thẻ <a> trừ các thẻ đi cùng với "Xem thêm" (giữ lại text)
        $this->removeLinksExceptXemThem($newXpath, $newDom);

        // Chuyển tất cả div thành p (xử lý từ dưới lên để tránh lỗi)
        $divNodes = $newXpath->query('//div');
        $divsArray = [];
        foreach ($divNodes as $div) {
            $divsArray[] = $div;
        }
        
        // Xử lý từ cuối lên đầu
        for ($i = count($divsArray) - 1; $i >= 0; $i--) {
            $div = $divsArray[$i];
            $p = $newDom->createElement('p');
            
            // Copy tất cả child nodes
            while ($div->firstChild) {
                $p->appendChild($div->firstChild);
            }
            
            // Copy attributes (trừ class, style, id)
            foreach ($div->attributes as $attr) {
                if (!in_array($attr->name, ['class', 'style', 'id'])) {
                    $p->setAttribute($attr->name, $attr->value);
                }
            }
            
            if ($div->parentNode) {
                $div->parentNode->replaceChild($p, $div);
            }
        }

        // Xử lý tất cả thẻ: loại bỏ class và attributes không cần thiết
        $allNodes = $newXpath->query('//*');
        foreach ($allNodes as $node) {
            // Xử lý đặc biệt cho thẻ span có class="dropcap"
            if ($node->nodeName === 'span') {
                $classValue = $node->getAttribute('class');
                if (!empty($classValue) && strpos($classValue, 'dropcap') !== false) {
                    // Thêm style inline cho dropcap
                    $node->setAttribute('style', 'font-size: 75px; line-height: 50px; float: left; padding: 10px 10px 10px 0 !important; color: #09c; font-weight: 400; margin-right: 10px !important;');
                    // Chỉ loại bỏ class, giữ lại style
                    $node->removeAttribute('class');
                    // Loại bỏ các attributes khác không cần thiết
                    $attributesToRemove = ['id', 'onclick', 'onerror', 'onload'];
                    foreach ($attributesToRemove as $attrName) {
                        if ($node->getAttribute($attrName) !== '') {
                            $node->removeAttribute($attrName);
                        }
                    }
                    // Loại bỏ data-* attributes
                    $attrsToRemove = [];
                    foreach ($node->attributes as $attr) {
                        if (strpos($attr->name, 'data-') === 0) {
                            $attrsToRemove[] = $attr->name;
                        }
                    }
                    foreach ($attrsToRemove as $attrName) {
                        $node->removeAttribute($attrName);
                    }
                    continue; // Bỏ qua xử lý chung cho node này
                }
            }
            
            // Xử lý đặc biệt cho thẻ strong: giữ lại style
            if ($node->nodeName === 'strong') {
                // Loại bỏ class, id, data-*, onclick, onerror, etc. nhưng GIỮ LẠI style
                $attributesToRemove = [];
                foreach ($node->attributes as $attr) {
                    if (in_array($attr->name, ['class', 'id', 'onclick', 'onerror', 'onload']) || 
                        strpos($attr->name, 'data-') === 0) {
                        $attributesToRemove[] = $attr->name;
                    }
                }
                foreach ($attributesToRemove as $attrName) {
                    $node->removeAttribute($attrName);
                }
                continue; // Bỏ qua xử lý chung cho node này
            }
            
            // Loại bỏ class, style, id, data-*, onclick, onerror, etc. cho các thẻ khác
            $attributesToRemove = [];
            foreach ($node->attributes as $attr) {
                if (in_array($attr->name, ['class', 'style', 'id', 'onclick', 'onerror', 'onload']) || 
                    strpos($attr->name, 'data-') === 0) {
                    $attributesToRemove[] = $attr->name;
                }
            }
            foreach ($attributesToRemove as $attrName) {
                $node->removeAttribute($attrName);
            }
        }
        
        // CUỐI CÙNG: Đảm bảo TẤT CẢ thẻ strong đều có style="3px;"
        // Query lại tất cả strong nodes để đảm bảo không bỏ sót node nào (kể cả node mới được tạo)
        $allStrongNodes = $newXpath->query('//strong');
        $strongNodesArray = [];
        foreach ($allStrongNodes as $strongNode) {
            $strongNodesArray[] = $strongNode;
        }
        
        foreach ($strongNodesArray as $strongNode) {
            $currentStyle = $strongNode->getAttribute('style');
            
            // Luôn đảm bảo có 3px;
            if (empty($currentStyle)) {
                // Nếu chưa có style, thêm mới
                $strongNode->setAttribute('style', '3px;');
            } else {
                // Nếu đã có style, thay thế tất cả padding bằng 3px;
                // Loại bỏ tất cả padding hiện có
                $newStyle = preg_replace('/padding\s*:\s*[^;]+;?/i', '', $currentStyle);
                $newStyle = trim($newStyle);
                // Loại bỏ các dấu ; thừa
                $newStyle = preg_replace('/;\s*;/', ';', $newStyle);
                $newStyle = trim($newStyle, '; ');
                // Thêm 3px;
                if (!empty($newStyle)) {
                    // Nếu còn style khác, thêm padding vào cuối
                    if (!preg_match('/;\s*$/', $newStyle)) {
                        $newStyle .= ';';
                    }
                    $newStyle .= ' 3px;';
                } else {
                    // Nếu không còn style nào, chỉ có padding
                    $newStyle = '3px;';
                }
                // Loại bỏ các dấu ; thừa một lần nữa
                $newStyle = preg_replace('/;\s*;/', ';', $newStyle);
                $newStyle = trim($newStyle, '; ');
                // Set lại style
                $strongNode->setAttribute('style', $newStyle);
            }
        }

        // Thay thế text "vinpearl.com", "Vinpearl" thành "Review Hải Phòng" trong text nodes
        // Và loại bỏ các năm 2000-2030, URL vinpearl.com
        $textNodes = $newXpath->query('//text()');
        foreach ($textNodes as $textNode) {
            $text = $textNode->nodeValue;
            
            // Loại bỏ URL vinpearl.com
            $text = preg_replace('/https?:\/\/(www\.)?vinpearl\.com[^\s]*/i', '', $text);
            
            // Loại bỏ URL "statics.Review Hải Phòng.com" (URL sai)
            $text = preg_replace('/https?:\/\/statics\.Review\s+Hải\s+Phòng\.com[^\s]*/i', '', $text);
            $text = preg_replace('/https?:\/\/statics\.reviewhaiphong\.com[^\s]*/i', '', $text);
            
            // Loại bỏ các năm 2000-2030
            $text = preg_replace('/\b(200[0-9]|201[0-9]|202[0-9]|2030)\b/', '', $text);
            
            // Xử lý các đoạn ">>> Khám phá ngay" không có link (xóa)
            $text = preg_replace('/(&gt;&gt;&gt;|>>>)\s*Khám phá ngay\s*[^<]+/i', '', $text);
            
            // Xóa các đoạn quảng cáo VinWonders
            $text = preg_replace('/.*VinWonders.*/i', '', $text);
            $text = preg_replace('/.*Đặt vé.*VinWonders.*/i', '', $text);
            $text = preg_replace('/.*–\s*VinWonders.*/i', '', $text);
            $text = preg_replace('/.*vinwonders.*/i', '', $text);
            
            // Loại bỏ các cụm từ liên quan đến vinpearl.com
            $text = $this->removeVinpearlReferences($text);
            
            // Thay thế các cụm từ còn lại thành Review Hải Phòng
            $text = $this->replaceVinpearlWithReviewHaiPhong($text);
            
            // Loại bỏ các khoảng trắng thừa
            $text = preg_replace('/\s+/', ' ', $text);
            $text = trim($text);
            
            if ($text !== $textNode->nodeValue) {
                $textNode->nodeValue = $text;
            }
        }
        
        // KHÔNG loại bỏ bất kỳ paragraph, heading, table nào
        // CHỈ thay thế các từ ngữ liên quan đến vinpearl.com trong text (đã xử lý ở phần textNodes ở trên)

        // Lấy HTML sau khi xử lý - lấy từ body
        $body = $newDom->getElementsByTagName('body')->item(0);
        if ($body) {
            $html = '';
            foreach ($body->childNodes as $child) {
                $html .= $newDom->saveHTML($child);
            }
        } else {
            // Fallback: lấy toàn bộ HTML
            $html = $newDom->saveHTML();
            // Loại bỏ DOCTYPE, html, head, body tags nếu có
            $html = preg_replace('/<!DOCTYPE[^>]*>/i', '', $html);
            $html = preg_replace('/<html[^>]*>/i', '', $html);
            $html = preg_replace('/<\/html>/i', '', $html);
            $html = preg_replace('/<head[^>]*>.*?<\/head>/is', '', $html);
            $html = preg_replace('/<body[^>]*>/i', '', $html);
            $html = preg_replace('/<\/body>/i', '', $html);
        }
        
        // Xử lý lại HTML string để thay thế tất cả URL sai còn sót
        // Thay thế "statics.Review Hải Phòng.com" (có thể có khoảng trắng hoặc không)
        $html = preg_replace('/statics\.Review\s+Hải\s+Phòng\.com/i', 'statics.vinpearl.com', $html);
        $html = preg_replace('/statics\.reviewhaiphong\.com/i', 'statics.vinpearl.com', $html);
        
        // Nếu vẫn còn URL sai sau khi thay thế, xóa toàn bộ URL đó
        $html = preg_replace('/https?:\/\/statics\.Review\s+Hải\s+Phòng\.com[^\s"\'<>]*/i', '', $html);
        $html = preg_replace('/https?:\/\/statics\.reviewhaiphong\.com[^\s"\'<>]*/i', '', $html);
        
        // Xóa các ảnh có src rỗng hoặc không hợp lệ
        $html = preg_replace('/<img[^>]*src=["\']\s*["\'][^>]*>/i', '', $html);

        // THAY THẾ LINK URL CUỐI CÙNG - sau khi đã xử lý xong tất cả
        // Loại bỏ tất cả URL vinpearl.com trong HTML (không chỉ trong link)
        $html = preg_replace('/https?:\/\/(www\.)?vinpearl\.com[^\s<>"\']*/i', '', $html);
        
        // Thay thế các link URL từ vinpearl.com thành reviewhaiphong.io.vn (nếu còn sót)
        $html = preg_replace_callback(
            '/(<a[^>]*href=["\'])(https?:\/\/(www\.)?vinpearl\.com\/([^"\'\s<>\)]+))(["\'])/i',
            function($matches) {
                // Kiểm tra xem link có data-xem-them-link không
                if (strpos($matches[0], 'data-xem-them-link') === false) {
                    return $matches[1] . 'https://reviewhaiphong.io.vn/' . $matches[4] . $matches[5];
                }
                return $matches[0]; // Giữ nguyên nếu đã được xử lý
            },
            $html
        );
        
        // Loại bỏ data-xem-them-link và data-kham-pha-ngay-link sau khi xử lý xong
        $html = preg_replace('/\s*data-xem-them-link=["\']1["\']/i', '', $html);
        $html = preg_replace('/\s*data-kham-pha-ngay-link=["\']1["\']/i', '', $html);
        
        // Loại bỏ các năm 2000-2030 còn sót lại trong HTML
        $html = preg_replace('/\b(200[0-9]|201[0-9]|202[0-9]|2030)\b/', '', $html);
        
        // CHỈ thay thế các từ ngữ "Vinpearl" trong text, KHÔNG xóa đoạn văn
        $html = preg_replace('/\bVinpearl\b/i', 'Review Hải Phòng', $html);
        $html = preg_replace('/\bvinpearl\.com\b/i', 'Review Hải Phòng', $html);
        
        // Thay thế các link vinpearl.com thành text (giữ lại text, xóa link)
        $html = preg_replace('/<a[^>]*href=["\']https?:\/\/(www\.)?vinpearl\.com[^"\']*["\'][^>]*>(.*?)<\/a>/is', '$2', $html);
        
        // Loại bỏ các khoảng trắng và dòng trống thừa
        $html = preg_replace('/\s+/', ' ', $html);
        $html = preg_replace('/>\s+</', '><', $html);
        $html = preg_replace('/\s*<p>\s*<\/p>\s*/i', '', $html);
        $html = preg_replace('/\s*<p>\s*&nbsp;\s*<\/p>\s*/i', '', $html);
        $html = preg_replace('/\s*<p>\s*\.\s*<\/p>\s*/i', '', $html);

        return trim($html);
    }

    /**
     * Thay thế các link "Xem thêm" bằng bài viết random từ database
     */
    private function replaceXemThemLinks(DOMXPath $xpath, DOMDocument $dom, ?int $categoryId = null)
    {
        // Tìm tất cả các thẻ <p> chứa "Xem thêm" (case insensitive)
        $paragraphs = $xpath->query('//p');
        
        foreach ($paragraphs as $p) {
            $pText = trim($p->textContent);
            
            // Kiểm tra nếu paragraph chứa "Xem thêm" (có thể có dấu hai chấm hoặc không)
            if (preg_match('/xem\s+thêm/i', $pText)) {
                $links = $xpath->query('.//a[@href]', $p);
                
                foreach ($links as $link) {
                    $href = $link->getAttribute('href');
                    
                    // CHỈ thay thế nếu link trỏ đến vinpearl.com (kiểm tra chặt chẽ hơn)
                    if (preg_match('/https?:\/\/(www\.)?vinpearl\.com\//i', $href)) {
                        // Lấy bài viết random từ database (mỗi link một bài viết khác nhau)
                        $randomPost = $this->getRandomPost($categoryId);
                        
                        if ($randomPost) {
                            // Thay thế href
                            $newHref = 'https://reviewhaiphong.io.vn/' . $randomPost->slug;
                            $link->setAttribute('href', $newHref);
                            
                            // Thay text của link bằng title của post random để khớp với href
                            if (!empty($randomPost->seo_title)) {
                                // Xóa tất cả child nodes (text và các thẻ con)
                                while ($link->firstChild) {
                                    $link->removeChild($link->firstChild);
                                }
                                // Thêm text mới
                                $link->appendChild($dom->createTextNode($randomPost->seo_title));
                            }
                            
                            // Loại bỏ target="_blank" nếu có
                            $link->removeAttribute('target');
                            
                            // Loại bỏ rel="noopener noreferrer" nếu có
                            $link->removeAttribute('rel');
                            
                            // Đánh dấu link đã được xử lý bằng data attribute để tránh bị xóa
                            $link->setAttribute('data-xem-them-link', '1');
                        }
                    }
                }
            }
        }
    }

    /**
     * Loại bỏ tất cả thẻ <a> trừ các thẻ đi cùng với "Xem thêm" (giữ lại text)
     */
    private function removeLinksExceptXemThem(DOMXPath $xpath, DOMDocument $dom)
    {
        // Tìm tất cả thẻ <a>
        $allLinks = $xpath->query('//a');
        
        Log::info('Vinpearl Scraper: removeLinksExceptXemThem - Tìm thấy ' . $allLinks->length . ' link(s)');
        
        // Tạo mảng để lưu các link cần xử lý (từ cuối lên đầu để tránh lỗi)
        $linksArray = [];
        foreach ($allLinks as $link) {
            $linksArray[] = $link;
        }
        
        // Xử lý từ cuối lên đầu
        for ($i = count($linksArray) - 1; $i >= 0; $i--) {
            $link = $linksArray[$i];
            
            $href = $link->getAttribute('href');
            $linkText = '';
            foreach ($link->childNodes as $child) {
                if ($child->nodeType === XML_TEXT_NODE) {
                    $linkText .= $child->nodeValue;
                } else {
                    $linkText .= $child->textContent;
                }
            }
            
            // Kiểm tra xem link có data-xem-them-link không (đã được xử lý bởi replaceXemThemLinks)
            $isXemThemLink = $link->getAttribute('data-xem-them-link') === '1';
            
            // Kiểm tra xem link có data-kham-pha-ngay-link không (link "Khám phá ngay" đã được tạo)
            $isKhamPhaNgayLink = $link->getAttribute('data-kham-pha-ngay-link') === '1';
            
            Log::info('Vinpearl Scraper: Link #' . ($i + 1) . ' - href: ' . ($href ?: 'empty') . ', text: ' . substr($linkText, 0, 50) . ', isXemThem: ' . ($isXemThemLink ? 'yes' : 'no') . ', isKhamPhaNgay: ' . ($isKhamPhaNgayLink ? 'yes' : 'no'));
            
            // Chỉ xóa link nếu không phải "Xem thêm" và không phải "Khám phá ngay"
            if (!$isXemThemLink && !$isKhamPhaNgayLink) {
                Log::info('Vinpearl Scraper: Xóa link - href: ' . ($href ?: 'empty') . ', text: ' . substr($linkText, 0, 50));
                
                // Tạo text node mới chứa text của link
                $textNode = $dom->createTextNode($linkText);
                
                // Thay thế thẻ <a> bằng text node
                if ($link->parentNode) {
                    $link->parentNode->replaceChild($textNode, $link);
                }
            } else {
                Log::info('Vinpearl Scraper: Giữ lại link - href: ' . ($href ?: 'empty') . ', text: ' . substr($linkText, 0, 50));
            }
        }
    }

    /**
     * Lấy bài viết random từ database
     * Ưu tiên lấy từ danh mục hiện tại, nếu không có thì random từ tất cả
     */
    private function getRandomPost(?int $categoryId = null)
    {
        if ($categoryId) {
            // Ưu tiên lấy bài viết từ danh mục hiện tại
            $post = Post::select(['id', 'slug', 'seo_title'])
                ->where('status', 'published')
                ->where('category_id', $categoryId)
                ->inRandomOrder()
                ->first();
            
            // Nếu có bài viết trong danh mục hiện tại, trả về
            if ($post) {
                return $post;
            }
        }
        
        // Nếu không có bài viết trong danh mục hiện tại, random từ tất cả bài viết
        return Post::select(['id', 'slug', 'seo_title'])
            ->where('status', 'published')
            ->inRandomOrder()
            ->first();
    }

    /**
     * Kiểm tra xem data có phải là ảnh không dựa vào magic bytes
     */
    private function isImageBytes(string $data): bool
    {
        // JPEG
        if (strncmp($data, "\xFF\xD8\xFF", 3) === 0) {
            return true;
        }
        // PNG
        if (strncmp($data, "\x89PNG\r\n\x1A\n", 8) === 0) {
            return true;
        }
        // GIF
        if (strncmp($data, 'GIF87a', 6) === 0 || strncmp($data, 'GIF89a', 6) === 0) {
            return true;
        }
        // WebP: RIFF....WEBP
        if (substr($data, 0, 4) === 'RIFF' && substr($data, 8, 4) === 'WEBP') {
            return true;
        }
        // BMP
        if (strncmp($data, 'BM', 2) === 0) {
            return true;
        }
        return false;
    }

    /**
     * Lấy origin từ URL
     */
    private function getOriginFromUrl(string $url): ?string
    {
        $p = @parse_url($url);
        if (!is_array($p) || empty($p['scheme']) || empty($p['host'])) {
            return null;
        }
        $port = isset($p['port']) ? ':' . $p['port'] : '';
        return $p['scheme'] . '://' . $p['host'] . $port;
    }

    /**
     * Tạo danh sách referer candidates
     */
    private function buildCandidateReferers(string $url, ?string $givenReferer): array
    {
        $candidates = [];
        if ($givenReferer) {
            $candidates[] = $givenReferer;
        }
        $origin = $this->getOriginFromUrl($url);
        if ($origin) {
            $candidates[] = $origin . '/';
        }
        // Thử thêm www nếu thiếu
        if ($origin) {
            $p = parse_url($origin);
            if (!empty($p['host']) && strpos($p['host'], 'www.') !== 0) {
                $www = $p['scheme'] . '://www.' . $p['host'] . (!empty($p['port']) ? ':' . $p['port'] : '') . '/';
                $candidates[] = $www;
            }
        }
        // Một số CDN: thử về domain gốc (ví dụ cdn.domain.com -> domain.com)
        if ($origin) {
            $host = parse_url($origin, PHP_URL_HOST);
            if ($host && substr_count($host, '.') >= 2) {
                $parts = explode('.', $host);
                $base = implode('.', array_slice($parts, -2));
                $scheme = parse_url($origin, PHP_URL_SCHEME) ?: 'https';
                $candidates[] = $scheme . '://' . $base . '/';
                $candidates[] = $scheme . '://www.' . $base . '/';
            }
        }
        // Loại bỏ trùng
        $candidates = array_values(array_unique(array_filter($candidates)));
        return $candidates ?: [$url];
    }

    /**
     * Tạo danh sách URL candidates (thử nhiều biến thể)
     */
    private function buildCandidateUrls(string $url, bool $tryUppercase): array
    {
        $candidates = [];
        $original = $url;
        $candidates[] = $original;

        // 1) Molex DAM: cắt "/jcr:content/renditions/..." để lấy đường dẫn gốc trước đó
        $posJcr = strpos($original, '/jcr:content/renditions/');
        if ($posJcr !== false) {
            $base = substr($original, 0, $posJcr);
            if ($base && !in_array($base, $candidates, true)) {
                $candidates[] = $base;
            }
        }

        // 2) Đổi đuôi .jpeg → .jpg nếu có
        $parsed = parse_url($original);
        if (!empty($parsed['path'])) {
            $pathLower = strtolower($parsed['path']);
            if (substr($pathLower, -5) === '.jpeg') {
                $alt = preg_replace('/\.jpeg$/i', '.jpg', $original);
                if ($alt && !in_array($alt, $candidates, true)) {
                    $candidates[] = $alt;
                }
            }
        }

        // 3) Thử phiên bản VIẾT HOA nếu được yêu cầu
        if ($tryUppercase) {
            foreach (array_slice($candidates, 0) as $u) {
                $up = strtoupper($u);
                if ($up !== $u && !in_array($up, $candidates, true)) {
                    $candidates[] = $up;
                }
            }
        }

        return $candidates;
    }

    /**
     * Download ảnh và lưu vào client/assets/images/posts
     */
    private static $staticDomainAccessed = false;
    
    private function downloadImage(string $url, string $slug, ?string $cookieFile = null): ?string
    {
        try {
            // Xử lý URL relative
            if (strpos($url, 'http') !== 0) {
                if (strpos($url, '//') === 0) {
                    $url = 'https:' . $url;
                } else {
                    $url = 'https://vinpearl.com/' . ltrim($url, '/');
                }
            }

            // Nếu URL là từ statics.vinpearl.com, truy cập subdomain trước để lấy cookie (chỉ một lần)
            if (strpos($url, 'statics.vinpearl.com') !== false && $cookieFile && file_exists($cookieFile) && !self::$staticDomainAccessed) {
                $staticCh = curl_init();
                curl_setopt_array($staticCh, [
                    CURLOPT_URL => 'https://statics.vinpearl.com/',
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_TIMEOUT => 30,
                    CURLOPT_CONNECTTIMEOUT => 10,
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_SSL_VERIFYHOST => false,
                    CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36',
                    CURLOPT_COOKIEFILE => $cookieFile,
                    CURLOPT_COOKIEJAR => $cookieFile,
                    CURLOPT_HTTPHEADER => [
                        'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                        'Accept-Language: en-US,en;q=0.9',
                        'Referer: https://vinpearl.com/',
                    ],
                ]);
                curl_exec($staticCh);
                curl_close($staticCh);
                usleep(500000); // Đợi 0.5 giây
                self::$staticDomainAccessed = true;
                Log::info('Vinpearl Scraper: Đã truy cập statics.vinpearl.com để lấy cookie cho subdomain');
            }

            // Các biến thể URL tiềm năng
            $candidateUrls = $this->buildCandidateUrls($url, true);

            $userAgents = [
                // Chrome Win
                'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36',
                // Safari iPhone
                'Mozilla/5.0 (iPhone; CPU iPhone OS 17_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1',
                // Firefox Win
                'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:127.0) Gecko/20100101 Firefox/127.0',
            ];

            $lastError = null;
            $totalAttempts = 0;
            $maxAttempts = 12;
            $tooMany = false;
            $imageData = null;
            $contentType = null;

            foreach ($candidateUrls as $attemptUrl) {
                $stopForUrl = false;
                $referers = $this->buildCandidateReferers($attemptUrl, 'https://vinpearl.com/');
                $consecutive404 = 0;
                
                foreach ($referers as $ref) {
                    foreach ($userAgents as $ua) {
                        if ($stopForUrl || $tooMany) {
                            break 2;
                        }
                        
                        // Ưu tiên thử SSL lenient trước để tránh lỗi missing CA; sau đó mới strict
                        foreach ([false, true] as $sslStrict) {
                            if ($stopForUrl || $tooMany) {
                                break;
                            }
                            $totalAttempts++;
                            if ($totalAttempts > $maxAttempts) {
                                $tooMany = true;
                                break;
                            }
                            
                            Log::info("Vinpearl Scraper: Thử #{$totalAttempts} | URL={$attemptUrl} | Ref={$ref} | SSL=" . ($sslStrict ? 'strict' : 'lenient'));
                            
                            $ch = curl_init($attemptUrl);
                            $headers = [
                                'Accept: image/avif,image/webp,image/apng,image/*,*/*;q=0.8',
                                'Accept-Language: vi,en;q=0.9',
                                'Cache-Control: no-cache',
                                'Pragma: no-cache',
                                'Connection: keep-alive',
                                'Sec-Fetch-Dest: image',
                                'Sec-Fetch-Mode: no-cors',
                                'Sec-Fetch-Site: cross-site',
                            ];
                            
                            $origin = $this->getOriginFromUrl($ref ?? $attemptUrl);
                            if ($origin) {
                                $headers[] = 'Origin: ' . $origin;
                            }
                            
                            $host = parse_url($attemptUrl, PHP_URL_HOST);
                            if ($host) {
                                $headers[] = 'Host: ' . $host;
                            }
                            
                            // sec-ch-ua cho Chrome UA
                            if (strpos($ua, 'Chrome') !== false) {
                                $headers[] = 'sec-ch-ua: "Chromium";v="126", "Not;A=Brand";v="24", "Google Chrome";v="126"';
                                $headers[] = 'sec-ch-ua-mobile: ?0';
                                $headers[] = 'sec-ch-ua-platform: "Windows"';
                            }

                            $curlOptions = [
                                CURLOPT_RETURNTRANSFER => true,
                                CURLOPT_FOLLOWLOCATION => true,
                                CURLOPT_MAXREDIRS => 10,
                                CURLOPT_TIMEOUT => 20,
                                CURLOPT_CONNECTTIMEOUT => 10,
                                CURLOPT_SSL_VERIFYPEER => $sslStrict,
                                CURLOPT_SSL_VERIFYHOST => $sslStrict ? 2 : 0,
                                CURLOPT_USERAGENT => $ua,
                                CURLOPT_HTTPHEADER => $headers,
                                CURLOPT_REFERER => $ref,
                                CURLOPT_ENCODING => '',
                                CURLOPT_HEADER => false,
                                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_2TLS,
                                CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
                                CURLOPT_AUTOREFERER => true,
                                CURLOPT_FAILONERROR => false,
                            ];

                            // Sử dụng cookie file nếu có
                            if ($cookieFile && file_exists($cookieFile)) {
                                $curlOptions[CURLOPT_COOKIEFILE] = $cookieFile;
                                $curlOptions[CURLOPT_COOKIEJAR] = $cookieFile;
                                
                                // Đọc cookie từ file và gửi trực tiếp trong header
                                $cookieContent = file_get_contents($cookieFile);
                                $cookies = [];
                                foreach (explode("\n", $cookieContent) as $line) {
                                    $line = trim($line);
                                    if (empty($line) || (strpos($line, '#') === 0 && strpos($line, '#HttpOnly_') !== 0)) {
                                        continue;
                                    }
                                    if (strpos($line, '#HttpOnly_') === 0) {
                                        $line = substr($line, 10);
                                    }
                                    $parts = explode("\t", $line);
                                    if (count($parts) >= 7) {
                                        $cookieName = trim($parts[5]);
                                        $cookieValue = trim($parts[6]);
                                        if (!empty($cookieName) && !empty($cookieValue)) {
                                            $cookies[] = $cookieName . '=' . $cookieValue;
                                        }
                                    }
                                }
                                if (!empty($cookies)) {
                                    $curlOptions[CURLOPT_COOKIE] = implode('; ', $cookies);
                                }
                            }

                            curl_setopt_array($ch, $curlOptions);
                            $data = curl_exec($ch);
                            $info = curl_getinfo($ch);
                            $err = curl_error($ch);
                            $httpCode = (int) ($info['http_code'] ?? 0);
                            curl_close($ch);

                            if ($data === false) {
                                $lastError = "cURL error: $err";
                                Log::warning("Vinpearl Scraper: Lỗi cURL: $err");
                                continue;
                            }

                            // Kiểm tra Content-Type và magic bytes
                            $ct = $info['content_type'] ?? '';
                            $isImageCT = strpos($ct, 'image/') === 0;
                            $looksLikeImage = $this->isImageBytes(substr($data, 0, 16));
                            $size = strlen($data);

                            Log::info("Vinpearl Scraper: HTTP $httpCode | CT=" . ($ct ?: 'n/a') . " | size={$size}B");

                            if ($httpCode >= 200 && $httpCode < 300 && ($isImageCT || $looksLikeImage)) {
                                $imageData = $data;
                                $contentType = $ct;
                                break 4; // Thoát tất cả vòng lặp
                            }

                            // Một số server gắn sai Content-Type nhưng data vẫn là ảnh
                            if ($looksLikeImage && ($httpCode === 200 || $httpCode === 206)) {
                                $imageData = $data;
                                $contentType = $ct;
                                break 4;
                            }

                            // Nếu HTML hoặc mã 403/401 -> thử tổ hợp khác
                            $snippet = strtolower(substr($data, 0, 400));
                            if (strpos($snippet, '<!doctype html') !== false || strpos($snippet, '<html') !== false || in_array($httpCode, [401, 403], true)) {
                                $lastError = $httpCode ? ('HTTP ' . $httpCode . ' (có thể bị chặn hotlink/captcha)') : 'Server trả về HTML';
                                Log::warning('Vinpearl Scraper: HTML/captcha hoặc bị chặn (' . ($httpCode ?: 'no code') . ')');
                                continue;
                            }

                            // 404 -> thử URL khác
                            if ($httpCode === 404) {
                                $lastError = '404 Not Found';
                                Log::warning('Vinpearl Scraper: 404 Not Found');
                                $consecutive404++;
                                if ($consecutive404 >= 2) {
                                    $stopForUrl = true;
                                }
                                continue;
                            }

                            $lastError = $httpCode ? ('HTTP ' . $httpCode . ' không hợp lệ cho ảnh') : 'Không nhận diện được dữ liệu ảnh.';
                            Log::warning('Vinpearl Scraper: ' . $lastError);
                        }
                    }
                }
                if ($tooMany) {
                    break;
                }
            }

            if (empty($imageData)) {
                $errorMsg = $tooMany && $lastError ? ($lastError . ' (dừng do đạt giới hạn thử)') : ($lastError ?: 'Tải ảnh thất bại.');
                Log::warning('Vinpearl Scraper: ' . $errorMsg);
                return null;
            }

            // Lấy extension từ URL hoặc content-type
            $extension = $this->getImageExtension($url, $contentType);
            $filename = $slug . '.' . $extension;
            $path = public_path('client/assets/images/posts');

            if (!File::exists($path)) {
                File::makeDirectory($path, 0755, true);
            }

            $filePath = $path . '/' . $filename;
            
            // Kiểm tra nếu file đã tồn tại, thêm số vào
            $counter = 1;
            while (File::exists($filePath)) {
                $filename = $slug . '-' . $counter . '.' . $extension;
                $filePath = $path . '/' . $filename;
                $counter++;
            }

            // Lưu file
            $bytesWritten = File::put($filePath, $imageData);
            
            if ($bytesWritten === false || $bytesWritten === 0) {
                Log::error('Vinpearl Scraper: Không thể lưu file ảnh: ' . $filePath);
                return null;
            }
            
            Log::info('Vinpearl Scraper: Lưu ảnh thành công: ' . $filename . ' (' . $bytesWritten . ' bytes)');

            return $filename;

        } catch (\Exception $e) {
            Log::error('Download Image Error: ' . $e->getMessage(), ['url' => $url]);
            return null;
        }
    }

    /**
     * Lấy extension từ URL hoặc Content-Type
     */
    private function getImageExtension(string $url, ?string $contentType): string
    {
        // Thử lấy từ URL trước
        $path = parse_url($url, PHP_URL_PATH);
        if ($path) {
            $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                return $ext === 'jpeg' ? 'jpg' : $ext;
            }
        }

        // Lấy từ Content-Type
        if ($contentType) {
            $mimeTypes = [
                'image/jpeg' => 'jpg',
                'image/jpg' => 'jpg',
                'image/png' => 'png',
                'image/gif' => 'gif',
                'image/webp' => 'webp'
            ];
            if (isset($mimeTypes[$contentType])) {
                return $mimeTypes[$contentType];
            }
        }

        // Mặc định là jpg
        return 'jpg';
    }

    /**
     * Extract keywords từ title và description
     */
    private function extractKeywords(string $title, string $description): string
    {
        // Loại bỏ HTML tags
        $text = strip_tags($title . ' ' . $description);
        
        // Đảm bảo encoding UTF-8
        if (!mb_check_encoding($text, 'UTF-8')) {
            $text = mb_convert_encoding($text, 'UTF-8', 'auto');
        }
        
        // Loại bỏ ký tự đặc biệt và chỉ giữ chữ, số, dấu cách
        $text = preg_replace('/[^\p{L}\p{N}\s,]/u', '', $text);
        
        // Tách thành từ
        $words = preg_split('/[\s,]+/u', $text);
        
        // Lọc từ có độ dài >= 3 và loại bỏ từ không cần thiết
        $stopWords = ['các', 'này', 'đó', 'với', 'cho', 'từ', 'của', 'trong', 'ngoài', 'và', 'là', 'có', 'được', 'một', 'những', 'nhiều', 'rất', 'sẽ', 'đã', 'đang'];
        $words = array_filter($words, function($word) use ($stopWords) {
            $word = mb_strtolower(trim($word), 'UTF-8');
            return mb_strlen($word, 'UTF-8') >= 3 && !in_array($word, $stopWords);
        });
        
        // Lấy 10 từ đầu tiên
        $keywords = array_slice(array_unique($words), 0, 10);
        
        // Đảm bảo encoding UTF-8 trước khi return
        $result = implode(', ', $keywords);
        if (!mb_check_encoding($result, 'UTF-8')) {
            $result = mb_convert_encoding($result, 'UTF-8', 'auto');
        }
        
        return $result;
    }

    /**
     * Clear cache sau khi tạo bài viết
     */
    protected function clearCache()
    {
        try {
            \Illuminate\Support\Facades\Cache::flush();
        } catch (\Exception $e) {
            Log::warning('Cache clear error: ' . $e->getMessage());
        }
    }

    /**
     * Load account từ session hoặc database
     */
    public function loadAccount()
    {
        $accountId = Auth::id();
        if (!$accountId) {
            throw new \Exception('Bạn chưa đăng nhập');
        }
        
        $account = \App\Models\Account::find($accountId);
        if (!$account) {
            throw new \Exception('Không tìm thấy tài khoản');
        }
        
        return $account;
    }
}

