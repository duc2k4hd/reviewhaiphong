<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\Category;
use App\Models\Setting;
use App\Models\ScrapedUrl;
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
        
        // Lấy số lượng URL đã cào từ database
        $processedUrlsCount = ScrapedUrl::countBySource('vinpearl');
        
        return view('admin.vinpearl-scraper.index', compact('account', 'processedUrlsCount'));
    }
    
    /**
     * Xóa danh sách URL đã cào
     */
    public function clearProcessedUrls(Request $request)
    {
        $deletedCount = ScrapedUrl::clearBySource('vinpearl');
        return redirect()->route('admin.vinpearl-scraper.index')->with('success', "Đã xóa {$deletedCount} URL đã cào từ database.");
    }

    /**
     * Xử lý scraping từ URL vinpearl.com - Chia nhỏ batch để tránh timeout
     */
    public function scrape(Request $request)
    {
        // Log để debug
        Log::info('Vinpearl Scraper: scrape method called', [
            'is_ajax' => $request->ajax(),
            'has_batch_index' => $request->has('batch_index'),
            'method' => $request->method(),
            'has_urls' => $request->has('urls'),
            'has_category' => $request->has('category')
        ]);
        
        // Nếu là AJAX request để xử lý batch
        if ($request->ajax() && $request->has('batch_index')) {
            // Clean output buffer để tránh HTML lẫn vào JSON
            while (ob_get_level() > 0) {
                ob_end_clean();
            }
            
            try {
                return $this->processBatch($request);
            } catch (\Throwable $e) {
                Log::error('Vinpearl Scraper: Unhandled exception in scrape method', [
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString()
                ]);
                
                // Clean output buffer
                while (ob_get_level() > 0) {
                    ob_end_clean();
                }
                
                return response()->json([
                    'success' => false,
                    'message' => 'Lỗi không mong đợi: ' . $e->getMessage()
                ], 500)->header('Content-Type', 'application/json; charset=utf-8');
            }
        }

        Log::info('Vinpearl Scraper: Starting validation', [
            'urls_length' => strlen($request->input('urls', '')),
            'category' => $request->input('category', ''),
            'has_urls' => $request->has('urls'),
            'has_category' => $request->has('category')
        ]);
        
        try {
            // Kiểm tra URLs không được trống trước khi validate
            $urlsText = trim($request->input('urls', ''));
            if (empty($urlsText)) {
                return back()->withErrors(['urls' => 'Vui lòng nhập ít nhất một URL hợp lệ.'])->withInput();
            }
            
            $request->validate([
                'urls' => 'required|string|min:10',
                'category' => 'required|string|in:du-lich,am-thuc,check-in,dich-vu,review-tong-hop'
            ], [
                'urls.required' => 'Vui lòng nhập ít nhất một URL hợp lệ.',
                'urls.min' => 'URL phải có ít nhất 10 ký tự.',
                'category.required' => 'Vui lòng chọn một danh mục.',
                'category.in' => 'Danh mục không hợp lệ.'
            ]);
            
            Log::info('Vinpearl Scraper: Validation passed');
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Vinpearl Scraper: Validation failed', [
                'errors' => $e->errors()
            ]);
            return back()->withErrors($e->errors())->withInput();
        }

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

            // Xóa toàn bộ session cũ để tránh xung đột
            $request->session()->forget([
                'scraper_processing_lock',
                'scraper_urls',
                'scraper_category',
                'scraper_total',
                'scraper_success',
                'scraper_errors'
            ]);
            
            // Lọc bỏ các URL đã được cào trước đó (check từ database)
            $newUrls = [];
            $skippedUrls = [];
            foreach ($validUrls as $url) {
                $url = trim($url);
                if (empty($url)) continue;
                
                if (ScrapedUrl::isUrlScraped($url, 'vinpearl')) {
                    $normalizedUrl = ScrapedUrl::normalizeUrl($url);
                    $skippedUrls[] = $normalizedUrl;
                    Log::info("Vinpearl Scraper: Bỏ qua URL đã cào trước đó: {$normalizedUrl}");
                } else {
                    // Lưu URL gốc (không normalize) để giữ nguyên format
                    $newUrls[] = $url;
                }
            }
            
            if (empty($newUrls)) {
                $message = 'Tất cả các URL đã được cào trước đó. Không có URL mới để xử lý.';
                if (count($skippedUrls) > 0) {
                    $message .= ' Đã bỏ qua ' . count($skippedUrls) . ' URL trùng lặp.';
                }
                $message .= ' Vui lòng nhập URL mới hoặc xóa danh sách URL đã cào để cào lại.';
                return back()->withErrors(['urls' => $message])->withInput();
            }
            
            if (count($skippedUrls) > 0) {
                Log::info("Vinpearl Scraper: Đã bỏ qua " . count($skippedUrls) . " URL đã cào trước đó");
                // Thêm thông báo thành công về số URL đã bỏ qua
                session()->flash('info', 'Đã bỏ qua ' . count($skippedUrls) . ' URL đã cào trước đó. Sẽ xử lý ' . count($newUrls) . ' URL mới.');
            }
            
            // Lưu URLs mới vào session để xử lý batch
            $request->session()->put('scraper_urls', $newUrls);
            $request->session()->put('scraper_category', $request->input('category', 'du-lich'));
            $request->session()->put('scraper_total', count($newUrls));
            $request->session()->put('scraper_success', 0);
            $request->session()->put('scraper_errors', []);

            Log::info('Vinpearl Scraper: Returning processing view', [
                'totalUrls' => count($newUrls),
                'category' => $request->input('category', 'du-lich')
            ]);

            // Trả về view với JavaScript để xử lý batch
            return view('admin.vinpearl-scraper.processing', [
                'totalUrls' => count($newUrls),
                'category' => $request->input('category', 'du-lich')
            ]);

        } catch (\Exception $e) {
            Log::error('Vinpearl Scraper Error: ' . $e->getMessage(), [
                'urls' => $request->input('urls'),
                'trace' => $e->getTraceAsString()
            ]);

            return back()->withErrors(['urls' => 'Lỗi khi cào dữ liệu: ' . $e->getMessage()]);
        }
    }

    /**
     * Xử lý một batch nhỏ (5 URL mỗi lần) để tránh timeout
     */
    private function processBatch(Request $request)
    {
        // Clean output buffer ngay từ đầu để tránh HTML lẫn vào JSON
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        
        // Đảm bảo response luôn là JSON ngay từ đầu
        try {
            $batchIndex = (int) $request->input('batch_index', 0);
            $batchSize = 5; // Xử lý 5 URL mỗi batch
            
            // Kiểm tra lock để tránh xử lý song song
            $lockKey = 'scraper_processing_lock';
            $currentLock = $request->session()->get($lockKey);
            $currentTime = time();
            
            // Nếu có lock và chưa hết hạn (30 giây), kiểm tra xem có phải batch đang được xử lý không
            if ($currentLock && isset($currentLock['batch_index']) && isset($currentLock['expires_at'])) {
                if ($currentLock['expires_at'] > $currentTime) {
                    // Có batch khác đang được xử lý
                    if ($currentLock['batch_index'] != $batchIndex) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Đang xử lý batch khác, vui lòng đợi...',
                            'retry' => true
                        ], 200);
                    }
                } else {
                    // Lock đã hết hạn, xóa lock
                    $request->session()->forget($lockKey);
                }
            }
            
            // Tạo lock mới cho batch này (30 giây)
            $request->session()->put($lockKey, [
                'batch_index' => $batchIndex,
                'expires_at' => $currentTime + 30
            ]);
            
            $validUrls = $request->session()->get('scraper_urls', []);
            $categorySlug = $request->session()->get('scraper_category', 'du-lich');
            $totalUrls = count($validUrls);
            
            if (empty($validUrls)) {
                $request->session()->forget($lockKey);
                return response()->json([
                    'success' => false,
                    'message' => 'Không có URL nào để xử lý'
                ], 200);
            }

            // Tính toán batch hiện tại
            $startIndex = $batchIndex * $batchSize;
            $endIndex = min($startIndex + $batchSize, $totalUrls);
            $batchUrls = array_slice($validUrls, $startIndex, $batchSize);
            
            if (empty($batchUrls)) {
                // Đã xử lý xong tất cả
                $successCount = $request->session()->get('scraper_success', 0);
                $errors = $request->session()->get('scraper_errors', []);
                
                // Clear session và lock (danh sách URL đã cào được lưu trong database, không cần giữ trong session)
                $request->session()->forget(['scraper_urls', 'scraper_category', 'scraper_total', 'scraper_success', 'scraper_errors', $lockKey]);
                
                // Clear cache
                try {
                    $this->clearCache();
                } catch (\Exception $e) {
                    Log::warning('Error clearing cache: ' . $e->getMessage());
                }
                
                return response()->json([
                    'success' => true,
                    'completed' => true,
                    'message' => "Đã cào thành công {$successCount} bài viết" . (count($errors) > 0 ? ", " . count($errors) . " bài viết lỗi" : ""),
                    'successCount' => $successCount,
                    'errorCount' => count($errors),
                    'errors' => $errors
                ], 200);
            }

            $account = null;
            try {
                $account = $this->loadAccount();
            } catch (\Exception $e) {
                Log::error('Error loading account: ' . $e->getMessage());
                return response()->json([
                    'success' => false,
                    'message' => 'Lỗi khi tải tài khoản: ' . $e->getMessage()
                ], 500);
            }
            
            $successCount = $request->session()->get('scraper_success', 0);
            $errors = $request->session()->get('scraper_errors', []);
            
            // Tăng thời gian thực thi cho batch này
            set_time_limit(300); // 5 phút cho mỗi batch
            ini_set('memory_limit', '2048M');
            
            // Xử lý từng URL trong batch
            foreach ($batchUrls as $index => $url) {
                try {
                    $originalUrl = trim($url);
                    
                    // Kiểm tra xem URL đã được cào chưa (check từ database)
                    if (ScrapedUrl::isUrlScraped($originalUrl, 'vinpearl')) {
                        Log::info("Vinpearl Scraper: Bỏ qua URL đã cào trước đó: {$originalUrl}");
                        continue; // Bỏ qua URL này
                    }
                    
                    Log::info("Vinpearl Scraper: Đang xử lý URL " . ($startIndex + $index + 1) . "/{$totalUrls}: {$originalUrl}");
                    
                    try {
                        $result = $this->scrapeSingleUrl($originalUrl, $account, $categorySlug);
                        if ($result && $result instanceof Post) {
                            // Cào thành công, lưu vào database với post_id
                            $successCount++;
                            ScrapedUrl::markAsScraped($originalUrl, 'vinpearl', $result->id);
                            Log::info("Vinpearl Scraper: Đã cào thành công và lưu vào database - URL: {$originalUrl}, Post ID: {$result->id}");
                        } elseif ($result === null) {
                            // Bài viết đã tồn tại (slug trùng), không tính là lỗi
                            Log::info("Vinpearl Scraper: Bỏ qua URL vì slug đã tồn tại: {$originalUrl}");
                            // Vẫn lưu URL vào database để tránh cào lại (không có post_id vì không tạo mới)
                            ScrapedUrl::markAsScraped($originalUrl, 'vinpearl', null);
                        } elseif ($result === false) {
                            // Lỗi khi xử lý URL
                            $errors[] = $originalUrl . ': Lỗi khi xử lý URL';
                            // Không lưu URL vào database nếu có lỗi (để có thể thử lại)
                        } else {
                            $errors[] = $originalUrl . ': Không thể cào dữ liệu';
                            // Không lưu URL vào database nếu có lỗi (để có thể thử lại)
                        }
                    } catch (\Throwable $e) {
                        $errorMsg = $e->getMessage();
                        $errors[] = $originalUrl . ': ' . $errorMsg;
                        Log::error('Vinpearl Scraper Error for URL ' . $originalUrl . ': ' . $errorMsg . ' | Trace: ' . $e->getTraceAsString());
                        // Không lưu URL vào database nếu có lỗi (để có thể thử lại)
                    }
                    
                    // Delay nhỏ giữa các URL
                    if ($index < count($batchUrls) - 1) {
                        usleep(300000); // 0.3 giây
                    }
                    
                } catch (\Throwable $e) {
                    $errorMsg = $e->getMessage();
                    $errors[] = $url . ': ' . $errorMsg;
                    Log::error('Vinpearl Scraper Error for URL ' . $url . ': ' . $errorMsg . ' | Trace: ' . $e->getTraceAsString());
                }
            }
            
            // Cập nhật session
            try {
                $request->session()->put('scraper_success', $successCount);
                $request->session()->put('scraper_errors', $errors);
            } catch (\Exception $e) {
                Log::warning('Error updating session: ' . $e->getMessage());
            }
            
            // Xóa lock sau khi xử lý xong batch
            try {
                $request->session()->forget($lockKey);
            } catch (\Exception $e) {
                Log::warning('Error removing lock: ' . $e->getMessage());
            }
            
            // Clean output buffer trước khi trả về
            while (ob_get_level() > 0) {
                ob_end_clean();
            }
            
            return response()->json([
                'success' => true,
                'completed' => false,
                'batchIndex' => $batchIndex,
                'processed' => $endIndex,
                'total' => $totalUrls,
                'successCount' => $successCount,
                'errorCount' => count($errors),
                'message' => "Đã xử lý {$endIndex}/{$totalUrls} URL..."
            ], 200)->header('Content-Type', 'application/json; charset=utf-8');
            
        } catch (\Throwable $e) {
            // Xóa lock khi có lỗi
            try {
                $request->session()->forget('scraper_processing_lock');
            } catch (\Exception $ex) {
                // Ignore
            }
            
            Log::error('Vinpearl Scraper Batch Error: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Clean output buffer trước khi trả về
            while (ob_get_level() > 0) {
                ob_end_clean();
            }
            
            // Đảm bảo luôn trả về JSON, kể cả khi có lỗi
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi xử lý batch: ' . $e->getMessage(),
                'error' => config('app.debug') ? [
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ] : null
            ], 500)->header('Content-Type', 'application/json; charset=utf-8');
        }
    }

    /**
     * Cào một URL đơn lẻ
     */
    private function scrapeSingleUrl(string $url, $account, string $categorySlug = 'du-lich')
    {
        // Reset static variable cho mỗi URL mới
        self::$staticDomainAccessed = false;
        
        // Đảm bảo time limit không bị giới hạn
        set_time_limit(0);
        
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
                    CURLOPT_TIMEOUT => 120, // Tăng timeout lên 2 phút
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
            
            // 2. Cào meta description gốc
            $metaDescNodes = $xpath->query('//meta[@name="description"]/@content');
            $originalDesc = $metaDescNodes->length > 0 ? trim($metaDescNodes->item(0)->value) : '';
            
            if (!empty($originalDesc)) {
                $originalDesc = $this->removeVinpearlReferences($originalDesc);
            }
            
            // 3. Viết lại tiêu đề và mô tả SEO bằng AI với prompt mới (đọc cả 2 đoạn)
            $seoTitle = $this->rewriteTitleWithGemini($originalTitle, $originalDesc);
            $seoDesc = $this->rewriteDescriptionWithGemini($originalTitle, $originalDesc);
            
            // Thay thế vinpearl.com thành Review Hải Phòng
            $seoTitle = $this->replaceVinpearlWithReviewHaiPhong($seoTitle);
            $seoDesc = $this->replaceVinpearlWithReviewHaiPhong($seoDesc);
            
            $slug = Str::slug($seoTitle);
            
            // Kiểm tra slug đã tồn tại chưa - nếu đã tồn tại thì bỏ qua bài viết này
            if (Post::where('slug', $slug)->exists()) {
                Log::info("Vinpearl Scraper: Bỏ qua bài viết vì slug đã tồn tại: {$slug} - URL: {$url}");
                return null; // Bỏ qua bài viết này
            }

            $data['seo_title'] = $seoTitle;
            $data['slug'] = $slug;
            $data['title'] = $seoTitle; // Lưu title để dùng cho alt/title ảnh
            $data['seo_desc'] = $seoDesc;

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
                    
                    // Chuyển đổi URL từ storage.googleapis.com sang statics.vinpearl.com
                    // Ví dụ: https://storage.googleapis.com/vinpearl-data-01/le-hoi-hai-phong-1.jpg
                    // → https://statics.vinpearl.com/le-hoi-hai-phong-1.jpg
                    if (preg_match('/https?:\/\/storage\.googleapis\.com\/vinpearl-data-01\/(.+)$/i', $imageUrl, $matches)) {
                        $imageName = $matches[1];
                        $imageUrl = 'https://statics.vinpearl.com/' . $imageName;
                        Log::info('Vinpearl Scraper: Chuyển đổi og:image từ Google Cloud Storage sang statics.vinpearl.com: ' . $imageUrl);
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
                        // Xử lý URL relative
                        if (strpos($imageUrl, 'http') !== 0) {
                            if (strpos($imageUrl, '//') === 0) {
                                $imageUrl = 'https:' . $imageUrl;
                            } else {
                                $imageUrl = 'https://vinpearl.com/' . ltrim($imageUrl, '/');
                            }
                        }
                        
                        // Chuyển đổi URL từ storage.googleapis.com sang statics.vinpearl.com
                        if (preg_match('/https?:\/\/storage\.googleapis\.com\/vinpearl-data-01\/(.+)$/i', $imageUrl, $matches)) {
                            $imageName = $matches[1];
                            $imageUrl = 'https://statics.vinpearl.com/' . $imageName;
                            Log::info('Vinpearl Scraper: Chuyển đổi ảnh đầu tiên từ Google Cloud Storage sang statics.vinpearl.com: ' . $imageUrl);
                        }
                        
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
                        // Xử lý URL relative
                        if (strpos($imageUrl, 'http') !== 0) {
                            if (strpos($imageUrl, '//') === 0) {
                                $imageUrl = 'https:' . $imageUrl;
                            } else {
                                $imageUrl = 'https://vinpearl.com/' . ltrim($imageUrl, '/');
                            }
                        }
                        
                        // Chuyển đổi URL từ storage.googleapis.com sang statics.vinpearl.com
                        if (preg_match('/https?:\/\/storage\.googleapis\.com\/vinpearl-data-01\/(.+)$/i', $imageUrl, $matches)) {
                            $imageName = $matches[1];
                            $imageUrl = 'https://statics.vinpearl.com/' . $imageName;
                            Log::info('Vinpearl Scraper: Chuyển đổi ảnh featured từ Google Cloud Storage sang statics.vinpearl.com: ' . $imageUrl);
                        }
                        
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
            
            // Log để debug: kiểm tra ảnh trong content trước khi lưu vào database
            $imgCountBeforeSave = preg_match_all('/<img[^>]*>/i', $content);
            Log::info('Vinpearl Scraper: Số ảnh trong content TRƯỚC KHI LƯU vào database: ' . $imgCountBeforeSave);
            if ($imgCountBeforeSave > 0) {
                preg_match_all('/<img[^>]*src=["\']([^"\']+)["\'][^>]*>/i', $content, $imgMatchesBeforeSave);
                if (!empty($imgMatchesBeforeSave[1])) {
                    $imagesBeforeSave = array_slice($imgMatchesBeforeSave[1], 0, 5);
                    Log::info('Vinpearl Scraper: Ảnh trong content TRƯỚC KHI LƯU: ' . implode(', ', $imagesBeforeSave));
                }
            } else {
                Log::warning('Vinpearl Scraper: KHÔNG CÓ ẢNH trong content trước khi lưu vào database!');
            }
            
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
            
            // Log để debug: kiểm tra ảnh trong content SAU KHI LƯU vào database
            $savedContent = $post->content;
            $imgCountAfterSave = preg_match_all('/<img[^>]*>/i', $savedContent);
            Log::info('Vinpearl Scraper: Số ảnh trong content SAU KHI LƯU vào database: ' . $imgCountAfterSave);
            if ($imgCountAfterSave > 0) {
                preg_match_all('/<img[^>]*src=["\']([^"\']+)["\'][^>]*>/i', $savedContent, $imgMatchesAfterSave);
                if (!empty($imgMatchesAfterSave[1])) {
                    $imagesAfterSave = array_slice($imgMatchesAfterSave[1], 0, 5);
                    Log::info('Vinpearl Scraper: Ảnh trong content SAU KHI LƯU: ' . implode(', ', $imagesAfterSave));
                }
            } else {
                Log::warning('Vinpearl Scraper: KHÔNG CÓ ẢNH trong content sau khi lưu vào database! Content length: ' . strlen($savedContent));
                // Log một phần content để debug
                Log::info('Vinpearl Scraper: Một phần content (500 ký tự đầu): ' . substr($savedContent, 0, 500));
            }

            return $post;

        } catch (\Throwable $e) {
            Log::error('Vinpearl Scraper Single URL Error: ' . $e->getMessage(), [
                'url' => $url,
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            // Không re-throw, trả về false để processBatch có thể xử lý
            return false;
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
    /**
     * Viết lại tiêu đề SEO với prompt mới - đọc cả tiêu đề và mô tả gốc
     */
    private function rewriteTitleWithGemini(string $originalTitle, string $originalDesc = ''): string
    {
        // Thêm delay để tránh rate limit của Gemini API
        // Dùng static variable chung cho cả 2 method để tránh gọi quá nhanh
        static $lastCallTime = 0;
        $minDelay = 1.5; // Delay tối thiểu 1.5 giây giữa các API call (cho cả title và description)
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
                return $originalTitle; // Trả về text gốc nếu không có API key
            }

            // Prompt mới theo yêu cầu
            $prompt = "Đọc tiêu đề và mô tả tại: " . $originalTitle . " | " . $originalDesc . 
                ". Hãy viết MỘT tiêu đề SEO duy nhất, dài tối đa 70 - 100 ký tự, văn phong tự nhiên, hấp dẫn, dễ click, chuẩn SEO.
BẮT BUỘC: không được chứa năm, tháng, ngày hoặc mốc thời gian (ví dụ: 2024, 2025, tháng 1, năm nay, hiện nay).
KHÔNG được thêm bất kỳ từ trang trí, nhãn, ký hiệu, hoặc chú thích nào như: [SEO], (SEO), [Chuẩn SEO], dấu ngoặc, emoji.
KHÔNG dùng năm, tháng, ngày, mốc thời gian.
Chỉ trả về tiêu đề, không giải thích, không ký tự thừa, không xuống dòng.";

            $payload = [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt]
                        ]
                    ]
                ]
            ];

            // Chỉ retry 1 lần (tổng 2 lần thử) - giảm từ 2 lần retry xuống 1 lần để giảm số request
            $response = Http::timeout(60)
                ->retry(1, 1000) // Retry 1 lần, delay 1 giây
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($geminiUrl . '?key=' . $geminiApiKey, $payload);

            if ($response->successful()) {
                $result = $response->json();
                if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
                    $rewritten = trim($result['candidates'][0]['content']['parts'][0]['text']);
                    // Loại bỏ dấu ngoặc kép, ngoặc vuông, emoji nếu có
                    $rewritten = trim($rewritten, '"\'[]()');
                    // Loại bỏ các ký tự đặc biệt không mong muốn
                    $rewritten = preg_replace('/\[SEO\]|\(SEO\)|\[Chuẩn SEO\]/i', '', $rewritten);
                    $rewritten = trim($rewritten);
                    // Giới hạn độ dài 70-100 ký tự
                    if (mb_strlen($rewritten) > 100) {
                        $rewritten = mb_substr($rewritten, 0, 100);
                    }
                    // Loại bỏ năm, tháng, ngày nếu còn sót
                    $rewritten = preg_replace('/\b(20\d{2}|năm\s+\d{4}|tháng\s+\d{1,2}|ngày\s+\d{1,2})\b/i', '', $rewritten);
                    $rewritten = trim($rewritten);
                    return $rewritten ?: $originalTitle;
                }
            }

            Log::warning('Gemini API không trả về kết quả hợp lệ cho tiêu đề', [
                'status' => $response->status(),
                'response' => $response->body()
            ]);
            return $originalTitle;

        } catch (\Exception $e) {
            Log::error('Gemini API Error (Title): ' . $e->getMessage());
            return $originalTitle; // Trả về text gốc nếu có lỗi
        }
    }

    /**
     * Viết lại mô tả SEO với prompt mới - đọc cả tiêu đề và mô tả gốc
     */
    private function rewriteDescriptionWithGemini(string $originalTitle, string $originalDesc = ''): string
    {
        // Thêm delay để tránh rate limit của Gemini API
        // Dùng static variable chung cho cả 2 method để tránh gọi quá nhanh
        static $lastCallTime = 0;
        $minDelay = 1.5; // Delay tối thiểu 1.5 giây giữa các API call (cho cả title và description)
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
                return $originalDesc ?: $originalTitle; // Trả về text gốc nếu không có API key
            }

            // Prompt mới theo yêu cầu
            $prompt = "Đây là website Review Hải Phòng. 
Dựa trên tiêu đề và mô tả: " . $originalTitle . " | " . $originalDesc . 
                ". Hãy viết MỘT tiêu đề bài review duy nhất, độ dài từ 180 đến 200 ký tự.
Văn phong tự nhiên, gần gũi, đúng kiểu bài review địa điểm tại Hải Phòng, tập trung trải nghiệm thực tế, tạo cảm giác muốn đọc.
YÊU CẦU BẮT BUỘC:
- Không dùng năm, tháng, ngày, mốc thời gian
- Không nhãn quảng cáo, không từ như SEO, chuẩn SEO, review chi tiết
- Không dấu ngoặc, không emoji, không ký hiệu trang trí
- Không giải thích, không xuống dòng
Chỉ trả về đúng 1 tiêu đề thuần văn bản.";

            $payload = [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt]
                        ]
                    ]
                ]
            ];

            // Chỉ retry 1 lần (tổng 2 lần thử) - giảm từ 2 lần retry xuống 1 lần để giảm số request
            $response = Http::timeout(60)
                ->retry(1, 1000) // Retry 1 lần, delay 1 giây
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($geminiUrl . '?key=' . $geminiApiKey, $payload);

            if ($response->successful()) {
                $result = $response->json();
                if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
                    $rewritten = trim($result['candidates'][0]['content']['parts'][0]['text']);
                    // Loại bỏ dấu ngoặc kép, ngoặc vuông, emoji nếu có
                    $rewritten = trim($rewritten, '"\'[]()');
                    // Loại bỏ các từ không mong muốn
                    $rewritten = preg_replace('/\b(SEO|chuẩn SEO|review chi tiết)\b/i', '', $rewritten);
                    $rewritten = trim($rewritten);
                    // Giới hạn độ dài 180-200 ký tự
                    if (mb_strlen($rewritten) > 200) {
                        $rewritten = mb_substr($rewritten, 0, 200);
                    }
                    if (mb_strlen($rewritten) < 180 && mb_strlen($rewritten) > 0) {
                        // Nếu quá ngắn, giữ nguyên (có thể AI đã tối ưu)
                    }
                    // Loại bỏ năm, tháng, ngày nếu còn sót
                    $rewritten = preg_replace('/\b(20\d{2}|năm\s+\d{4}|tháng\s+\d{1,2}|ngày\s+\d{1,2})\b/i', '', $rewritten);
                    $rewritten = trim($rewritten);
                    return $rewritten ?: ($originalDesc ?: $originalTitle);
                }
            }

            Log::warning('Gemini API không trả về kết quả hợp lệ cho mô tả', [
                'status' => $response->status(),
                'response' => $response->body()
            ]);
            return $originalDesc ?: $originalTitle;

        } catch (\Exception $e) {
            Log::error('Gemini API Error (Description): ' . $e->getMessage());
            return $originalDesc ?: $originalTitle; // Trả về text gốc nếu có lỗi
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
        $totalImages = $imgNodes->length;
        $imgCount = 0;
        Log::info('Vinpearl Scraper: Tìm thấy ' . $totalImages . ' ảnh trong content');
        foreach ($imgNodes as $index => $img) {
            $src = $img->getAttribute('src');
            if (empty($src)) {
                $src = $img->getAttribute('data-src');
            }
            if (!empty($src)) {
                // Thay thế URL sai "statics.Review Hải Phòng.com" thành URL đúng
                $src = preg_replace('/statics\.Review\s+Hải\s+Phòng\.com/i', 'statics.vinpearl.com', $src);
                $src = preg_replace('/statics\.reviewhaiphong\.com/i', 'statics.vinpearl.com', $src);
                
                // Chuyển đổi URL từ storage.googleapis.com sang statics.vinpearl.com
                // Ví dụ: https://storage.googleapis.com/vinpearl-data-01/le-hoi-hai-phong-1.jpg
                // → https://statics.vinpearl.com/le-hoi-hai-phong-1.jpg
                if (preg_match('/https?:\/\/storage\.googleapis\.com\/vinpearl-data-01\/(.+)$/i', $src, $matches)) {
                    $imageName = $matches[1];
                    $src = 'https://statics.vinpearl.com/' . $imageName;
                    Log::info('Vinpearl Scraper: Chuyển đổi URL từ Google Cloud Storage sang statics.vinpearl.com: ' . $src);
                }
                
                Log::info('Vinpearl Scraper: Xử lý ảnh #' . ($index + 1) . '/' . $totalImages . ' - URL gốc: ' . $src);
                
                // Delay giữa các request download ảnh để tránh bị chặn
                if ($imgCount > 0) {
                    usleep(300000); // Delay 0.3 giây giữa các ảnh
                }
                
                // Download ảnh
                $imageName = $this->downloadImage($src, $slug, $cookieFile);
                if ($imageName) {
                    $newSrc = 'https://reviewhaiphong.io.vn/client/assets/images/posts/' . $imageName;
                    $img->setAttribute('src', $newSrc);
                    $img->setAttribute('alt', $title);
                    $img->setAttribute('title', $title);
                    Log::info('Vinpearl Scraper: Đã thay thế src ảnh thành: ' . $newSrc);
                    $imgCount++;
                } else {
                    Log::warning('Vinpearl Scraper: Không thể download ảnh, xóa ảnh khỏi content - URL: ' . $src);
                    // Nếu không download được, xóa ảnh
                    if ($img->parentNode) {
                        $img->parentNode->removeChild($img);
                    }
                }
            } else {
                Log::warning('Vinpearl Scraper: Ảnh không có src hoặc data-src, xóa ảnh');
                // Xóa ảnh không có src
                if ($img->parentNode) {
                    $img->parentNode->removeChild($img);
                }
            }
        }
        Log::info('Vinpearl Scraper: Đã xử lý xong ' . $imgCount . ' ảnh trong content');
        

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
            // NHƯNG giữ lại data-xem-them-link và data-kham-pha-ngay-link cho thẻ <a>
            $attributesToRemove = [];
            foreach ($node->attributes as $attr) {
                if (in_array($attr->name, ['class', 'style', 'id', 'onclick', 'onerror', 'onload'])) {
                    $attributesToRemove[] = $attr->name;
                } elseif (strpos($attr->name, 'data-') === 0) {
                    // Giữ lại data-xem-them-link và data-kham-pha-ngay-link cho thẻ <a>
                    if ($node->nodeName === 'a' && 
                        ($attr->name === 'data-xem-them-link' || $attr->name === 'data-kham-pha-ngay-link')) {
                        continue; // Giữ lại attribute này
                    }
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
                $strongNode->setAttribute('style', 'padding: 3px;');
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
                    $newStyle .= ' padding: 3px;';
                } else {
                    // Nếu không còn style nào, chỉ có padding
                    $newStyle = 'padding: 3px;';
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
        
        // Xóa các ảnh có src rỗng hoặc không hợp lệ (chỉ xóa nếu src thực sự rỗng)
        $html = preg_replace('/<img[^>]*src=["\']\s*["\'][^>]*>/i', '', $html);
        
        // Log để debug: đếm số ảnh trong HTML sau khi xử lý
        $imgCountInHtml = preg_match_all('/<img[^>]*>/i', $html);
        Log::info('Vinpearl Scraper: Số ảnh trong HTML sau khi xử lý: ' . $imgCountInHtml);
        
        // Log một vài ảnh đầu tiên để debug
        if ($imgCountInHtml > 0) {
            preg_match_all('/<img[^>]*src=["\']([^"\']+)["\'][^>]*>/i', $html, $imgMatches);
            if (!empty($imgMatches[1])) {
                $firstFewImages = array_slice($imgMatches[1], 0, 3);
                Log::info('Vinpearl Scraper: Một vài ảnh đầu tiên trong HTML: ' . implode(', ', $firstFewImages));
            }
        }

        // THAY THẾ LINK URL CUỐI CÙNG - sau khi đã xử lý xong tất cả
        // Loại bỏ tất cả URL vinpearl.com trong HTML (KHÔNG xóa trong thuộc tính src của ảnh)
        // Cách an toàn: tách HTML thành các phần (img tags và phần còn lại), chỉ xóa URL trong phần không phải img
        // Bước 1: Tìm và lưu tất cả img tags với placeholder (dùng base64 để tránh conflict)
        $imgPlaceholders = [];
        $imgIndex = 0;
        $html = preg_replace_callback(
            '/<img[^>]*>/i',
            function($matches) use (&$imgPlaceholders, &$imgIndex) {
                $imgTag = $matches[0];
                // Tạo placeholder unique bằng base64 encode
                $placeholder = '___IMG_' . base64_encode($imgTag . '_' . $imgIndex . '_' . time()) . '___';
                $imgPlaceholders[$placeholder] = $imgTag;
                $imgIndex++;
                Log::info('Vinpearl Scraper: Tạo placeholder cho ảnh #' . $imgIndex . ': ' . substr($placeholder, 0, 50) . '...');
                return $placeholder;
            },
            $html
        );
        
        Log::info('Vinpearl Scraper: Đã tạo ' . count($imgPlaceholders) . ' placeholder cho ảnh');
        
        // Bước 2: Xóa tất cả URL vinpearl.com trong HTML (không còn img tags nữa)
        $html = preg_replace('/https?:\/\/(www\.)?vinpearl\.com[^\s<>"\']*/i', '', $html);
        
        // Bước 3: Thay thế lại các img tags (theo thứ tự ngược để tránh conflict)
        $replacedCount = 0;
        foreach ($imgPlaceholders as $placeholder => $imgTag) {
            if (strpos($html, $placeholder) !== false) {
                $html = str_replace($placeholder, $imgTag, $html);
                $replacedCount++;
            } else {
                Log::warning('Vinpearl Scraper: Không tìm thấy placeholder trong HTML: ' . substr($placeholder, 0, 50));
            }
        }
        Log::info('Vinpearl Scraper: Đã thay thế ' . $replacedCount . '/' . count($imgPlaceholders) . ' placeholder');
        
        // Log để debug: đếm số ảnh sau khi xóa URL vinpearl.com
        $imgCountAfterClean = preg_match_all('/<img[^>]*>/i', $html);
        Log::info('Vinpearl Scraper: Số ảnh trong HTML sau khi xóa URL vinpearl.com: ' . $imgCountAfterClean);
        
        if ($imgCountAfterClean > 0) {
            preg_match_all('/<img[^>]*src=["\']([^"\']+)["\'][^>]*>/i', $html, $imgMatchesAfter);
            if (!empty($imgMatchesAfter[1])) {
                $firstFewImagesAfter = array_slice($imgMatchesAfter[1], 0, 3);
                Log::info('Vinpearl Scraper: Ảnh sau khi xóa URL vinpearl.com: ' . implode(', ', $firstFewImagesAfter));
            }
        } else {
            Log::warning('Vinpearl Scraper: Ảnh bị mất sau khi xóa URL vinpearl.com!');
        }
        
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
        
        // Log cuối cùng: đếm số ảnh trước khi return
        $finalImgCount = preg_match_all('/<img[^>]*>/i', $html);
        Log::info('Vinpearl Scraper: Số ảnh trong HTML cuối cùng (trước khi return): ' . $finalImgCount);
        
        if ($finalImgCount > 0) {
            preg_match_all('/<img[^>]*src=["\']([^"\']+)["\'][^>]*>/i', $html, $finalImgMatches);
            if (!empty($finalImgMatches[1])) {
                $finalImages = array_slice($finalImgMatches[1], 0, 5);
                Log::info('Vinpearl Scraper: Ảnh cuối cùng trong HTML: ' . implode(', ', $finalImages));
            }
        } else {
            Log::warning('Vinpearl Scraper: KHÔNG CÓ ẢNH trong HTML cuối cùng!');
        }

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
            
            // Kiểm tra xem link có phải là internal link từ reviewhaiphong.io.vn không
            $isInternalLink = false;
            if ($href && (strpos($href, 'reviewhaiphong.io.vn') !== false || strpos($href, '/') === 0)) {
                $isInternalLink = true;
            }
            
            Log::info('Vinpearl Scraper: Link #' . ($i + 1) . ' - href: ' . ($href ?: 'empty') . ', text: ' . substr($linkText, 0, 50) . ', isXemThem: ' . ($isXemThemLink ? 'yes' : 'no') . ', isKhamPhaNgay: ' . ($isKhamPhaNgayLink ? 'yes' : 'no') . ', isInternal: ' . ($isInternalLink ? 'yes' : 'no'));
            
            // Kiểm tra xem link có phải là internal link từ reviewhaiphong.io.vn không
            $isInternalLink = false;
            if ($href && (strpos($href, 'reviewhaiphong.io.vn') !== false || strpos($href, '/') === 0)) {
                $isInternalLink = true;
            }
            
            // Chỉ xóa link nếu không phải "Xem thêm", không phải "Khám phá ngay", và không phải internal link
            if (!$isXemThemLink && !$isKhamPhaNgayLink && !$isInternalLink) {
                Log::info('Vinpearl Scraper: Xóa link - href: ' . ($href ?: 'empty') . ', text: ' . substr($linkText, 0, 50));
                
                // Tạo text node mới chứa text của link
                $textNode = $dom->createTextNode($linkText);
                
                // Thay thế thẻ <a> bằng text node
                if ($link->parentNode) {
                    $link->parentNode->replaceChild($textNode, $link);
                }
            } else {
                if ($isInternalLink) {
                    Log::info('Vinpearl Scraper: Giữ lại internal link - href: ' . ($href ?: 'empty') . ', text: ' . substr($linkText, 0, 50));
                } else {
                    Log::info('Vinpearl Scraper: Giữ lại link - href: ' . ($href ?: 'empty') . ', text: ' . substr($linkText, 0, 50));
                }
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

            // Nếu URL là từ statics.vinpearl.com hoặc storage.googleapis.com, đảm bảo có cookie từ vinpearl.com
            // (cả 2 domain này đều có thể cần cookie để truy cập)
            if (($url && (strpos($url, 'statics.vinpearl.com') !== false || strpos($url, 'storage.googleapis.com') !== false)) 
                && $cookieFile && file_exists($cookieFile) && !self::$staticDomainAccessed) {
                // Truy cập trang chủ vinpearl.com để đảm bảo có cookie
                $homeCh = curl_init();
                curl_setopt_array($homeCh, [
                    CURLOPT_URL => 'https://vinpearl.com/',
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_TIMEOUT => 30,
                    CURLOPT_CONNECTTIMEOUT => 10,
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_SSL_VERIFYHOST => false,
                    CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)',
                    CURLOPT_COOKIEFILE => $cookieFile,
                    CURLOPT_COOKIEJAR => $cookieFile,
                    CURLOPT_HTTPHEADER => [
                        'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                        'Accept-Language: en-US,en;q=0.9',
                        'Referer: https://www.google.com/',
                    ],
                ]);
                curl_exec($homeCh);
                curl_close($homeCh);
                usleep(500000); // Đợi 0.5 giây
                self::$staticDomainAccessed = true;
                Log::info('Vinpearl Scraper: Đã truy cập vinpearl.com để lấy cookie cho ảnh');
            }

            // KHÔNG bỏ qua ảnh từ Google Cloud Storage nữa - thử download trước, chỉ bỏ qua nếu thực sự fail
            // (Vinpearl thường host ảnh trên Google Cloud Storage và vẫn có thể tải được)

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
            $maxAttempts = 4; // Giảm từ 12 xuống 4 để tránh timeout
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
                                CURLOPT_MAXREDIRS => 5, // Giảm từ 10 xuống 5
                                CURLOPT_TIMEOUT => 10, // Giảm từ 20 xuống 10 giây
                                CURLOPT_CONNECTTIMEOUT => 5, // Giảm từ 10 xuống 5 giây
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

                            // 404 -> thử URL candidate khác hoặc bỏ qua nếu đã thử nhiều lần
                            if ($httpCode === 404) {
                                $lastError = '404 Not Found';
                                Log::warning('Vinpearl Scraper: 404 Not Found - URL: ' . $attemptUrl);
                                $consecutive404++;
                                // Chỉ bỏ qua URL này nếu đã thử nhiều lần (không phân biệt domain)
                                if ($consecutive404 >= 2) {
                                    $stopForUrl = true;
                                    Log::info('Vinpearl Scraper: Bỏ qua URL sau ' . $consecutive404 . ' lần 404: ' . $attemptUrl);
                                }
                                continue;
                            }

                            // 400 -> thử URL candidate khác
                            if ($httpCode === 400) {
                                $lastError = 'HTTP 400 Bad Request';
                                Log::warning('Vinpearl Scraper: HTTP 400 - URL: ' . $attemptUrl);
                                // Thử URL candidate tiếp theo thay vì bỏ qua ngay
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

