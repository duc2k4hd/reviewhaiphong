<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use DOMDocument;
use DOMXPath;

class VinpearlLinkExtractorController extends Controller
{
    /**
     * Hiển thị form nhập URL
     */
    public function index()
    {
        return view('admin.vinpearl-link-extractor.index');
    }

    /**
     * Trích xuất link từ URL vinpearl.com
     */
    public function extract(Request $request)
    {
        $request->validate([
            'urls' => 'required|string'
        ]);

        // Lấy danh sách URLs từ textarea (mỗi dòng 1 URL)
        $urlsText = $request->input('urls');
        $urls = array_filter(array_map('trim', explode("\n", $urlsText)));
        
        if (empty($urls)) {
            return back()->withErrors(['urls' => 'Vui lòng nhập ít nhất một URL hợp lệ.']);
        }

        // Validate từng URL phải từ vinpearl.com
        $validUrls = [];
        foreach ($urls as $index => $url) {
            $url = trim($url);
            if (empty($url)) {
                continue;
            }
            
            // Validate URL format
            if (!filter_var($url, FILTER_VALIDATE_URL)) {
                return back()->withErrors(['urls' => 'URL không hợp lệ ở dòng ' . ($index + 1) . ': ' . $url]);
            }
            
            // Validate URL phải từ vinpearl.com
            if (!preg_match('/^https?:\/\/(www\.)?vinpearl\.com/i', $url)) {
                return back()->withErrors(['urls' => 'URL ở dòng ' . ($index + 1) . ' phải từ vinpearl.com: ' . $url]);
            }
            
            $validUrls[] = $url;
        }

        if (empty($validUrls)) {
            return back()->withErrors(['urls' => 'Không có URL hợp lệ nào.']);
        }

        try {
            $allLinks = [];
            $processedUrls = [];
            $failedUrls = [];
            $baseUrl = 'https://vinpearl.com';

            // Xử lý từng URL
            foreach ($validUrls as $index => $url) {
                try {
                    // Delay giữa các request để tránh bị chặn (trừ request đầu tiên)
                    if ($index > 0) {
                        usleep(500000); // 0.5 giây
                    }
                    
                    Log::info('Vinpearl Link Extractor: Đang xử lý URL ' . ($index + 1) . '/' . count($validUrls) . ': ' . $url);
                    
                    // Fetch HTML từ URL
                    $html = $this->fetchHtml($url);
                    
                    if (empty($html)) {
                        $failedUrls[] = ['url' => $url, 'error' => 'Không thể lấy được nội dung'];
                        continue;
                    }

                    // Parse HTML và trích xuất links
                    $links = $this->extractLinks($html);

                    if (empty($links)) {
                        $failedUrls[] = ['url' => $url, 'error' => 'Không tìm thấy link nào'];
                        continue;
                    }

                    // Chuyển đổi relative URLs thành absolute URLs
                    $urlBase = parse_url($url, PHP_URL_SCHEME) . '://' . parse_url($url, PHP_URL_HOST);
                    $fullLinks = array_map(function($link) use ($urlBase) {
                        if (strpos($link, 'http') === 0) {
                            return $link;
                        }
                        // Nếu là relative URL, thêm base URL
                        if (strpos($link, '/') === 0) {
                            return $urlBase . $link;
                        }
                        return $urlBase . '/' . $link;
                    }, $links);

                    // Gộp vào danh sách tổng (loại bỏ trùng lặp)
                    foreach ($fullLinks as $link) {
                        if (!in_array($link, $allLinks)) {
                            $allLinks[] = $link;
                        }
                    }

                    $processedUrls[] = [
                        'url' => $url,
                        'count' => count($fullLinks)
                    ];

                    Log::info('Vinpearl Link Extractor: Đã xử lý URL ' . $url . ' - Tìm thấy ' . count($fullLinks) . ' link');

                } catch (\Exception $e) {
                    Log::error('Vinpearl Link Extractor Error cho URL ' . $url . ': ' . $e->getMessage());
                    $failedUrls[] = ['url' => $url, 'error' => $e->getMessage()];
                }
            }

            if (empty($allLinks)) {
                return back()->withErrors(['urls' => 'Không tìm thấy link nào từ các URL đã nhập.']);
            }

            return view('admin.vinpearl-link-extractor.result', [
                'links' => $allLinks,
                'count' => count($allLinks),
                'processedUrls' => $processedUrls,
                'failedUrls' => $failedUrls,
                'totalUrls' => count($validUrls)
            ]);

        } catch (\Exception $e) {
            Log::error('Vinpearl Link Extractor Error: ' . $e->getMessage());
            return back()->withErrors(['urls' => 'Lỗi khi trích xuất link: ' . $e->getMessage()]);
        }
    }

    /**
     * Fetch HTML từ URL với Cloudflare bypass
     */
    private function fetchHtml(string $url): string
    {
        $cookieFile = storage_path('app/temp/vinpearl_cookies.txt');
        $cookieDir = dirname($cookieFile);
        if (!is_dir($cookieDir)) {
            mkdir($cookieDir, 0755, true);
        }

        // Bước 1: Truy cập homepage để lấy cookie
        $ch = curl_init('https://vinpearl.com/');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)',
            CURLOPT_COOKIEFILE => $cookieFile,
            CURLOPT_COOKIEJAR => $cookieFile,
            CURLOPT_HTTPHEADER => [
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language: vi,en;q=0.9',
                'Connection: keep-alive',
            ],
        ]);
        curl_exec($ch);
        curl_close($ch);

        // Bước 2: Truy cập URL thực tế với cookie đã lấy
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)',
            CURLOPT_COOKIEFILE => $cookieFile,
            CURLOPT_COOKIEJAR => $cookieFile,
            CURLOPT_HTTPHEADER => [
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language: vi,en;q=0.9',
                'Connection: keep-alive',
                'Referer: https://vinpearl.com/',
            ],
        ]);

        $html = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($html === false || $httpCode !== 200) {
            Log::error('Vinpearl Link Extractor: Failed to fetch URL', [
                'url' => $url,
                'http_code' => $httpCode,
                'error' => $error
            ]);
            throw new \Exception('Không thể lấy được nội dung từ URL. HTTP Code: ' . $httpCode);
        }

        return $html;
    }

    /**
     * Trích xuất links từ HTML
     */
    private function extractLinks(string $html): array
    {
        // Suppress warnings từ DOMDocument
        libxml_use_internal_errors(true);
        
        $dom = new DOMDocument();
        $dom->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        
        libxml_clear_errors();

        $xpath = new DOMXPath($dom);
        
        // Tìm div có class="row news-items"
        $newsItemsDiv = $xpath->query('//div[contains(@class, "row") and contains(@class, "news-items")]');
        
        if ($newsItemsDiv->length === 0) {
            Log::warning('Vinpearl Link Extractor: Không tìm thấy div.news-items');
            return [];
        }

        $links = [];
        
        // Trong mỗi div.news-items, tìm tất cả div.info-wrapper
        foreach ($newsItemsDiv as $newsItemsContainer) {
            $infoWrappers = $xpath->query('.//div[contains(@class, "info-wrapper")]', $newsItemsContainer);
            
            Log::info('Vinpearl Link Extractor: Tìm thấy ' . $infoWrappers->length . ' info-wrapper');
            
            foreach ($infoWrappers as $infoWrapper) {
                // Tìm thẻ <a> đầu tiên trong info-wrapper
                $linksInWrapper = $xpath->query('.//a[@href]', $infoWrapper);
                
                if ($linksInWrapper->length > 0) {
                    $link = $linksInWrapper->item(0);
                    $href = $link->getAttribute('href');
                    
                    if (!empty($href)) {
                        // Loại bỏ trùng lặp
                        if (!in_array($href, $links)) {
                            $links[] = $href;
                            Log::info('Vinpearl Link Extractor: Tìm thấy link: ' . $href);
                        }
                    }
                }
            }
        }

        return $links;
    }
}

