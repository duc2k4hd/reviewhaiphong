<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use DOMDocument;
use DOMXPath;

class LinkExtractorController extends Controller
{
    public function index()
    {
        return view('admin.link-extractor.index');
    }

    public function extract(Request $request)
    {
        $request->validate([
            'urls' => 'required|string',
        ]);

        $urlsInput = $request->input('urls');
        $urls = array_filter(array_map('trim', explode("\n", $urlsInput)));
        
        if (empty($urls)) {
            return back()->withErrors(['urls' => 'Vui lòng nhập ít nhất một URL.']);
        }

        $allLinks = [];
        $errors = [];

        foreach ($urls as $url) {
            $url = trim($url);
            if (empty($url)) {
                continue;
            }

            // Đảm bảo URL có protocol
            if (!preg_match('/^https?:\/\//i', $url)) {
                $url = 'https://' . $url;
            }

            try {
                $links = $this->extractLinksFromUrl($url);
                $allLinks = array_merge($allLinks, $links);
            } catch (\Exception $e) {
                $errors[] = "Lỗi khi xử lý URL: {$url} - " . $e->getMessage();
                Log::error('LinkExtractor Error', [
                    'url' => $url,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Loại bỏ duplicate và sort
        $allLinks = array_unique($allLinks);
        sort($allLinks);

        return view('admin.link-extractor.result', [
            'links' => $allLinks,
            'total' => count($allLinks),
            'errors' => $errors,
        ]);
    }

    private function extractLinksFromUrl(string $url): array
    {
        // Fetch HTML từ URL
        $response = Http::timeout(30)->get($url);
        if (!$response->successful()) {
            throw new \Exception('Không thể truy cập URL: ' . $url);
        }

        $html = $response->body();
        $dom = new DOMDocument();
        @$dom->loadHTML('<?xml encoding="UTF-8">' . $html);
        $xpath = new DOMXPath($dom);

        // Tìm <div class="article-list"> trước
        $articleListNodes = $xpath->query('//div[@class="article-list"]');
        
        $links = [];
        
        if ($articleListNodes->length > 0) {
            // Tìm tất cả <div class="article-item"> trong article-list
            $articleItemNodes = $xpath->query('.//div[@class="article-item"]', $articleListNodes->item(0));
            
            foreach ($articleItemNodes as $articleItem) {
                // Tìm <a class="article-link"> trong mỗi article-item
                $linkNodes = $xpath->query('.//a[@class="article-link"]', $articleItem);
                
                foreach ($linkNodes as $linkNode) {
                    $href = $linkNode->getAttribute('href');
                    $href = trim($href);
                    if (!empty($href)) {
                        // Nếu là relative URL, chuyển thành absolute
                        if (strpos($href, 'http') !== 0) {
                            $parsedUrl = parse_url($url);
                            $baseUrl = $parsedUrl['scheme'] . '://' . $parsedUrl['host'];
                            if (strpos($href, '/') === 0) {
                                $href = $baseUrl . $href;
                            } else {
                                $href = $baseUrl . '/' . ltrim($href, '/');
                            }
                        }
                        $links[] = $href;
                    }
                }
            }
        }

        return $links;
    }
}

