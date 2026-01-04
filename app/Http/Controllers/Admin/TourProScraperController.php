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

class TourProScraperController extends Controller
{
    /**
     * Hiển thị form nhập link
     */
    public function index()
    {
        $account = $this->loadAccount();
        return view('admin.tour-pro-scraper.index', compact('account'));
    }

    /**
     * Xử lý scraping từ URL tour.pro.vn
     */
    public function scrape(Request $request)
    {
        $request->validate([
            'urls' => 'required|string'
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
                
                if (!filter_var($url, FILTER_VALIDATE_URL) || !preg_match('/^https?:\/\/(www\.)?tour\.pro\.vn\/.+/i', $url)) {
                    continue; // Bỏ qua URL không hợp lệ
                }
                
                $validUrls[] = $url;
            }

            if (empty($validUrls)) {
                return back()->withErrors(['urls' => 'Không có URL hợp lệ nào. Vui lòng kiểm tra lại định dạng URL (phải là link từ tour.pro.vn).']);
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
                    Log::info("Tour Pro Scraper: Đang xử lý URL " . ($index + 1) . "/{$totalUrls}: {$url}");
                    
                    $result = $this->scrapeSingleUrl($url, $account);
                    if ($result) {
                        $successCount++;
                    } else {
                        $errorCount++;
                        $errors[] = $url . ': Không thể cào dữ liệu';
                    }
                    
                    // Delay giữa các request để tránh quá tải (chỉ delay nếu không phải URL cuối)
                    if ($index < $totalUrls - 1) {
                        usleep(500000); // Delay 0.5 giây giữa các request
                    }
                    
                } catch (\Exception $e) {
                    $errorCount++;
                    $errorMsg = $e->getMessage();
                    $errors[] = $url . ': ' . $errorMsg;
                    Log::error('Tour Pro Scraper Error for URL ' . $url . ': ' . $errorMsg, [
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
            Log::error('Tour Pro Scraper Error: ' . $e->getMessage(), [
                'urls' => $request->input('urls'),
                'trace' => $e->getTraceAsString()
            ]);

            return back()->withErrors(['urls' => 'Lỗi khi cào dữ liệu: ' . $e->getMessage()]);
        }
    }

    /**
     * Cào một URL đơn lẻ
     */
    private function scrapeSingleUrl(string $url, $account)
    {
        try {
            
            // Lấy hoặc tạo category "Du lịch"
            $category = Category::firstOrCreate(
                ['slug' => 'du-lich'],
                [
                    'name' => 'Du lịch',
                    'status' => 'active',
                    'sort_order' => 0
                ]
            );

            // Fetch HTML từ URL với timeout dài hơn và retry
            $maxRetries = 3;
            $response = null;
            
            for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
                try {
                    $response = Http::timeout(60)
                        ->retry(2, 1000) // Retry 2 lần, delay 1 giây
                        ->get($url);
                    
                    if ($response->successful()) {
                        break;
                    }
                } catch (\Exception $e) {
                    if ($attempt === $maxRetries) {
                        throw new \Exception('Không thể truy cập URL sau ' . $maxRetries . ' lần thử: ' . $url . ' - ' . $e->getMessage());
                    }
                    // Đợi trước khi retry
                    sleep(2);
                }
            }
            
            if (!$response || !$response->successful()) {
                throw new \Exception('Không thể truy cập URL này: ' . $url);
            }

            $html = $response->body();
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

            // Loại bỏ các text liên quan đến tour.pro.vn trước khi gửi AI
            $originalTitle = $this->removeTourProReferences($originalTitle);
            
            $seoTitle = $this->rewriteWithGemini($originalTitle, 'title');
            
            // Thay thế tour.pro.vn, Tour Pro, TourPro thành Review Hải Phòng trong title
            $seoTitle = $this->replaceTourProWithReviewHaiPhong($seoTitle);
            
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
                $originalDesc = $this->removeTourProReferences($originalDesc);
                $seoDesc = $this->rewriteWithGemini($originalDesc, 'description');
            } else {
                $seoDesc = $this->rewriteWithGemini($originalTitle, 'description');
            }

            // Thay thế tour.pro.vn thành Review Hải Phòng trong description
            $seoDesc = $this->replaceTourProWithReviewHaiPhong($seoDesc);

            $data['seo_desc'] = $seoDesc;

            // 3. Cào content - ưu tiên lấy từ div.article-content.rte
            $contentNodes = $xpath->query('//div[@class="article-content rte"] | //div[contains(@class, "article-content") and contains(@class, "rte")]');
            
            if ($contentNodes->length === 0) {
                // Fallback: thử các selector khác
                $contentNodes = $xpath->query('//div[contains(@class, "article-content")] | //div[contains(@class, "post-content")] | //div[contains(@class, "content")] | //article//div[contains(@class, "entry-content")] | //main//div[contains(@class, "content")]');
            }
            
            if ($contentNodes->length === 0) {
                // Thử tìm trong article tag
                $contentNodes = $xpath->query('//article');
            }
            
            if ($contentNodes->length === 0) {
                throw new \Exception('Không tìm thấy nội dung bài viết');
            }

            $contentDiv = $contentNodes->item(0);
            $content = $this->processContent($contentDiv, $dom, $slug, $seoTitle);
            $data['content'] = $content;

            // 4. Lấy ảnh đại diện - thử nhiều selector
            $seoImage = null;
            
            // Thử tìm ảnh từ meta og:image
            $ogImageNodes = $xpath->query('//meta[@property="og:image"]/@content');
            if ($ogImageNodes->length > 0) {
                $imageUrl = trim($ogImageNodes->item(0)->value);
                if (!empty($imageUrl)) {
                    $seoImage = $this->downloadImage($imageUrl, $slug);
                }
            }
            
            // Nếu không có, thử tìm ảnh đầu tiên trong content
            if (!$seoImage) {
                $firstImgNodes = $xpath->query('//div[contains(@class, "post-content")]//img[1]/@src | //div[contains(@class, "article-content")]//img[1]/@src | //article//img[1]/@src');
                if ($firstImgNodes->length > 0) {
                    $imageUrl = trim($firstImgNodes->item(0)->value);
                    if (!empty($imageUrl)) {
                        $seoImage = $this->downloadImage($imageUrl, $slug);
                    }
                }
            }
            
            // Nếu vẫn không có, thử tìm ảnh featured
            if (!$seoImage) {
                $featuredImgNodes = $xpath->query('//img[contains(@class, "featured")] | //img[contains(@class, "thumbnail")] | //img[contains(@class, "post-thumbnail")]');
                if ($featuredImgNodes->length > 0) {
                    $imageUrl = $featuredImgNodes->item(0)->getAttribute('src');
                    if (!empty($imageUrl)) {
                        $seoImage = $this->downloadImage($imageUrl, $slug);
                    }
                }
            }

            $data['seo_image'] = $seoImage;

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
            Log::error('Tour Pro Scraper Single URL Error: ' . $e->getMessage(), [
                'url' => $url,
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Loại bỏ các tham chiếu đến tour.pro.vn và năm từ 2000-2030
     */
    private function removeTourProReferences(string $text): string
    {
        // Loại bỏ các text liên quan đến tour.pro.vn (bao gồm cả cụm "- Tour.Pro.Vn")
        $text = preg_replace('/\s*-\s*Tour\.Pro\.Vn\b/i', '', $text);
        $text = preg_replace('/\s*-\s*Tour\.Pro\.vn\b/i', '', $text);
        $text = preg_replace('/\s*-\s*Tour Pro\b/i', '', $text);
        $text = preg_replace('/\s*-\s*TourPro\b/i', '', $text);
        $text = preg_replace('/\s*-\s*Tour\.Pro\b/i', '', $text);
        $text = preg_replace('/\s*-\s*TOUR PRO\b/i', '', $text);
        $text = preg_replace('/\btour\.pro\.vn\b/i', '', $text);
        $text = preg_replace('/\bTour Pro\b/i', '', $text);
        $text = preg_replace('/\bTourPro\b/i', '', $text);
        $text = preg_replace('/\bTour\.Pro\b/i', '', $text);
        $text = preg_replace('/\bTOUR PRO\b/i', '', $text);
        
        // Loại bỏ các năm từ 2000-2030
        $text = preg_replace('/\b(200[0-9]|201[0-9]|202[0-9]|2030)\b/', '', $text);
        
        // Loại bỏ các khoảng trắng thừa
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);
        
        return $text;
    }

    /**
     * Thay thế tour.pro.vn thành Review Hải Phòng
     */
    private function replaceTourProWithReviewHaiPhong(string $text): string
    {
        $text = preg_replace('/\btour\.pro\.vn\b/i', 'Review Hải Phòng', $text);
        $text = preg_replace('/\bTour Pro\b/i', 'Review Hải Phòng', $text);
        $text = preg_replace('/\bTourPro\b/i', 'Review Hải Phòng', $text);
        $text = preg_replace('/\bTour\.Pro\b/i', 'Review Hải Phòng', $text);
        $text = preg_replace('/\bTOUR PRO\b/i', 'Review Hải Phòng', $text);
        
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
     * Xử lý content: chuyển div thành p, loại bỏ class/attributes, download ảnh, thay thế link
     */
    private function processContent($contentDiv, DOMDocument $dom, string $slug, string $title): string
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

        // Tạo DOMDocument mới để xử lý
        $newDom = new DOMDocument('1.0', 'UTF-8');
        libxml_use_internal_errors(true);
        
        // Wrap trong body để xử lý dễ hơn
        $wrappedHtml = '<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body>' . $html . '</body></html>';
        @$newDom->loadHTML($wrappedHtml, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        
        $newXpath = new DOMXPath($newDom);
        
        // Xử lý tất cả ảnh trước (download và thay đổi src)
        $imgNodes = $newXpath->query('//img');
        foreach ($imgNodes as $img) {
            $src = $img->getAttribute('src');
            if (!empty($src)) {
                // Download ảnh
                $imageName = $this->downloadImage($src, $slug);
                if ($imageName) {
                    $img->setAttribute('src', 'https://reviewhaiphong.io.vn/client/assets/images/posts/' . $imageName);
                    $img->setAttribute('alt', $title);
                    $img->setAttribute('title', $title);
                }
            }
        }

        // Xử lý các link "Xem thêm" - thay thế bằng bài viết random từ database
        $this->replaceXemThemLinks($newXpath, $newDom);

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

        // Thay thế text "tour.pro.vn", "Tour Pro", "TourPro" thành "Review Hải Phòng" trong text nodes
        // Và loại bỏ các năm 2000-2030, URL tour.pro.vn
        $textNodes = $newXpath->query('//text()');
        foreach ($textNodes as $textNode) {
            $text = $textNode->nodeValue;
            
            // Loại bỏ URL tour.pro.vn
            $text = preg_replace('/https?:\/\/(www\.)?tour\.pro\.vn[^\s]*/i', '', $text);
            
            // Loại bỏ các năm 2000-2030
            $text = preg_replace('/\b(200[0-9]|201[0-9]|202[0-9]|2030)\b/', '', $text);
            
            // Loại bỏ các cụm từ liên quan đến tour.pro.vn
            $text = $this->removeTourProReferences($text);
            
            // Thay thế các cụm từ còn lại thành Review Hải Phòng
            $text = $this->replaceTourProWithReviewHaiPhong($text);
            
            // Loại bỏ các cụm từ như "Tour Thung Nham", "Tour Tam Cốc" (giữ lại tên địa điểm)
            $text = preg_replace('/\bTour\s+([A-Z][a-zàáảãạăắằẳẵặâấầẩẫậèéẻẽẹêếềểễệìíỉĩịòóỏõọôốồổỗộơớờởỡợùúủũụưứừửữựỳýỷỹỵđ]+)\b/i', '$1', $text);
            
            // Loại bỏ các khoảng trắng thừa
            $text = preg_replace('/\s+/', ' ', $text);
            $text = trim($text);
            
            if ($text !== $textNode->nodeValue) {
                $textNode->nodeValue = $text;
            }
        }
        
        // KHÔNG loại bỏ bất kỳ paragraph, heading, table nào
        // CHỈ thay thế các từ ngữ liên quan đến tour.pro.vn trong text (đã xử lý ở phần textNodes ở trên)

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

        // THAY THẾ LINK URL CUỐI CÙNG - sau khi đã xử lý xong tất cả
        // Loại bỏ tất cả URL tour.pro.vn trong HTML (không chỉ trong link)
        $html = preg_replace('/https?:\/\/(www\.)?tour\.pro\.vn[^\s<>"\']*/i', '', $html);
        
        // Thay thế các link URL từ tour.pro.vn thành reviewhaiphong.io.vn (nếu còn sót)
        $html = preg_replace_callback(
            '/(<a[^>]*href=["\'])(https?:\/\/(www\.)?tour\.pro\.vn\/([^"\'\s<>\)]+))(["\'])/i',
            function($matches) {
                // Kiểm tra xem link có data-xem-them-link không
                if (strpos($matches[0], 'data-xem-them-link') === false) {
                    return $matches[1] . 'https://reviewhaiphong.io.vn/' . $matches[4] . $matches[5];
                }
                return $matches[0]; // Giữ nguyên nếu đã được xử lý
            },
            $html
        );
        
        // Loại bỏ data-xem-them-link sau khi xử lý xong (chỉ giữ lại các link "Xem thêm")
        $html = preg_replace('/\s*data-xem-them-link=["\']1["\']/i', '', $html);
        
        // Loại bỏ các năm 2000-2030 còn sót lại trong HTML
        $html = preg_replace('/\b(200[0-9]|201[0-9]|202[0-9]|2030)\b/', '', $html);
        
        // CHỈ thay thế các từ ngữ "Tour Pro" trong text, KHÔNG xóa đoạn văn
        $html = preg_replace('/\bBài viết này\s+Tour Pro\b/i', 'Bài viết này', $html);
        $html = preg_replace('/\bTour Pro\s+(sẽ|đã|đang)\s+(hướng dẫn|gợi ý|tư vấn)\b/i', 'Review Hải Phòng $2', $html);
        $html = preg_replace('/\bđược gợi ý của Tour Pro\b/i', 'được gợi ý của Review Hải Phòng', $html);
        $html = preg_replace('/\bđược Tour Pro\b/i', 'được Review Hải Phòng', $html);
        $html = preg_replace('/\btại Tour Pro\b/i', 'tại Review Hải Phòng', $html);
        
        // Thay thế các link tour.pro.vn thành text (giữ lại text, xóa link)
        $html = preg_replace('/<a[^>]*href=["\']https?:\/\/(www\.)?tour\.pro\.vn[^"\']*["\'][^>]*>(.*?)<\/a>/is', '$2', $html);
        
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
    private function replaceXemThemLinks(DOMXPath $xpath, DOMDocument $dom)
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
                    
                    // CHỈ thay thế nếu link trỏ đến tour.pro.vn (kiểm tra chặt chẽ hơn)
                    if (preg_match('/https?:\/\/(www\.)?tour\.pro\.vn\//i', $href)) {
                        // Lấy bài viết random từ database (mỗi link một bài viết khác nhau)
                        $randomPost = $this->getRandomPost();
                        
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
        
        // Tạo mảng để lưu các link cần xử lý (từ cuối lên đầu để tránh lỗi)
        $linksArray = [];
        foreach ($allLinks as $link) {
            $linksArray[] = $link;
        }
        
        // Xử lý từ cuối lên đầu
        for ($i = count($linksArray) - 1; $i >= 0; $i--) {
            $link = $linksArray[$i];
            
            // Kiểm tra xem link có data-xem-them-link không (đã được xử lý bởi replaceXemThemLinks)
            $isXemThemLink = $link->getAttribute('data-xem-them-link') === '1';
            
            if (!$isXemThemLink) {
                // Lấy text bên trong thẻ <a>
                $linkText = '';
                foreach ($link->childNodes as $child) {
                    if ($child->nodeType === XML_TEXT_NODE) {
                        $linkText .= $child->nodeValue;
                    } else {
                        // Nếu có thẻ con, lấy text từ thẻ con
                        $linkText .= $child->textContent;
                    }
                }
                
                // Tạo text node mới chứa text của link
                $textNode = $dom->createTextNode($linkText);
                
                // Thay thế thẻ <a> bằng text node
                if ($link->parentNode) {
                    $link->parentNode->replaceChild($textNode, $link);
                }
            }
        }
    }

    /**
     * Lấy bài viết random từ database
     */
    private function getRandomPost()
    {
        // Không cache để mỗi lần gọi sẽ lấy bài viết random khác nhau
        return Post::select(['id', 'slug', 'seo_title'])
            ->where('status', 'published')
            ->inRandomOrder()
            ->first();
    }

    /**
     * Download ảnh và lưu vào client/assets/images/posts
     */
    private function downloadImage(string $url, string $slug): ?string
    {
        try {
            // Xử lý URL relative
            if (strpos($url, 'http') !== 0) {
                if (strpos($url, '//') === 0) {
                    $url = 'https:' . $url;
                } else {
                    $url = 'https://tour.pro.vn/' . ltrim($url, '/');
                }
            }

            // Retry logic cho download ảnh
            $maxRetries = 3;
            $response = null;
            
            for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
                try {
                    $response = Http::timeout(60)
                        ->retry(1, 1000)
                        ->get($url);
                    
                    if ($response->successful()) {
                        break;
                    }
                } catch (\Exception $e) {
                    if ($attempt === $maxRetries) {
                        Log::warning('Không thể download ảnh sau ' . $maxRetries . ' lần thử: ' . $url . ' - ' . $e->getMessage());
                        return null;
                    }
                    sleep(1); // Đợi 1 giây trước khi retry
                }
            }
            
            if (!$response || !$response->successful()) {
                Log::warning('Không thể download ảnh: ' . $url);
                return null;
            }

            // Lấy extension từ URL hoặc content-type
            $extension = $this->getImageExtension($url, $response->header('Content-Type'));
            $filename = $slug . '.' . $extension;
            $path = public_path('client/assets/images/posts');

            if (!File::exists($path)) {
                File::makeDirectory($path, 0755, true);
            }

            $filePath = $path . '/' . $filename;
            
            // Kiểm tra nếu file đã tồn tại, thêm số vào
            $counter = 1;
            $originalFilename = $filename;
            while (File::exists($filePath)) {
                $filename = $slug . '-' . $counter . '.' . $extension;
                $filePath = $path . '/' . $filename;
                $counter++;
            }

            // Lưu file
            File::put($filePath, $response->body());

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
}

