<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\Category;
use App\Models\ScrapedUrl;
use DOMDocument;
use DOMXPath;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TravelokaScraperController extends Controller
{
    private const ARTICLE_URL_REGEX = '#^https?://(www\.)?traveloka\.com/vi-vn/explore/(destination|activities|culinary|tips|accommodations)/.+#i';
    private const SOURCE = 'traveloka';
    private const BATCH_SIZE = 3;

    private const CATEGORY_MAP = [
        'du-lich'         => 'Du lịch',
        'am-thuc'         => 'Ẩm thực',
        'check-in'        => 'Check-in',
        'dich-vu'         => 'Dịch vụ',
        'review-tong-hop' => 'Review tổng hợp',
    ];

    public function index()
    {
        $processedUrlsCount = ScrapedUrl::countBySource(self::SOURCE);
        return view('admin.traveloka-scraper.index', compact('processedUrlsCount'));
    }

    public function clearProcessedUrls(Request $request)
    {
        $deleted = ScrapedUrl::clearBySource(self::SOURCE);
        return redirect()
            ->route('admin.traveloka-scraper.index')
            ->with('success', "Đã xóa {$deleted} URL đã cào từ database.");
    }

    public function scrape(Request $request)
    {
        if ($request->ajax() && $request->has('batch_index')) {
            while (ob_get_level() > 0) { ob_end_clean(); }
            try {
                return $this->processBatch($request);
            } catch (\Throwable $e) {
                Log::error('Traveloka Scraper: batch exception', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
                return response()->json(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()], 500);
            }
        }

        $request->validate([
            'urls'     => 'required|string|min:10',
            'category' => 'required|string|in:' . implode(',', array_keys(self::CATEGORY_MAP)),
        ], [
            'urls.required'     => 'Vui lòng nhập ít nhất một URL.',
            'category.required' => 'Vui lòng chọn một danh mục.',
            'category.in'       => 'Danh mục không hợp lệ.',
        ]);

        $urls = array_values(array_filter(array_map('trim', explode("\n", $request->input('urls')))));
        $validUrls = [];
        foreach ($urls as $u) {
            if (preg_match(self::ARTICLE_URL_REGEX, $u)) {
                $validUrls[] = $u;
            }
        }
        if (empty($validUrls)) {
            return back()->withErrors(['urls' => 'Không có URL hợp lệ. URL phải thuộc dạng https://www.traveloka.com/vi-vn/explore/{destination|activities|culinary|tips|accommodations}/...'])->withInput();
        }

        $newUrls = [];
        $skipped = 0;
        foreach ($validUrls as $u) {
            if (ScrapedUrl::isUrlScraped($u, self::SOURCE)) {
                $skipped++;
            } else {
                $newUrls[] = $u;
            }
        }
        if (empty($newUrls)) {
            return back()->withErrors(['urls' => "Tất cả {$skipped} URL đã được cào trước đó. Xóa danh sách URL đã cào nếu muốn cào lại."])->withInput();
        }

        $request->session()->forget([
            'traveloka_scraper_lock', 'traveloka_scraper_urls', 'traveloka_scraper_category',
            'traveloka_scraper_total', 'traveloka_scraper_success', 'traveloka_scraper_errors',
        ]);
        $request->session()->put('traveloka_scraper_urls', $newUrls);
        $request->session()->put('traveloka_scraper_category', $request->input('category'));
        $request->session()->put('traveloka_scraper_total', count($newUrls));
        $request->session()->put('traveloka_scraper_success', 0);
        $request->session()->put('traveloka_scraper_errors', []);

        if ($skipped > 0) {
            session()->flash('info', "Đã bỏ qua {$skipped} URL đã cào trước đó. Xử lý " . count($newUrls) . " URL mới.");
        }

        return view('admin.traveloka-scraper.processing', [
            'totalUrls' => count($newUrls),
            'category'  => $request->input('category'),
        ]);
    }

    private function processBatch(Request $request)
    {
        $batchIndex = (int) $request->input('batch_index', 0);
        $lockKey = 'traveloka_scraper_lock';
        $now = time();
        $lock = $request->session()->get($lockKey);
        if ($lock && isset($lock['batch_index'], $lock['expires_at']) && $lock['expires_at'] > $now && $lock['batch_index'] != $batchIndex) {
            return response()->json(['success' => false, 'message' => 'Đang xử lý batch khác...', 'retry' => true]);
        }
        $request->session()->put($lockKey, ['batch_index' => $batchIndex, 'expires_at' => $now + 60]);

        $urls       = $request->session()->get('traveloka_scraper_urls', []);
        $catSlug    = $request->session()->get('traveloka_scraper_category', 'du-lich');
        $total      = count($urls);
        $start      = $batchIndex * self::BATCH_SIZE;
        $end        = min($start + self::BATCH_SIZE, $total);
        $batchUrls  = array_slice($urls, $start, self::BATCH_SIZE);
        $successCnt = $request->session()->get('traveloka_scraper_success', 0);
        $errors     = $request->session()->get('traveloka_scraper_errors', []);

        if (empty($batchUrls)) {
            $request->session()->forget([
                'traveloka_scraper_urls', 'traveloka_scraper_category', 'traveloka_scraper_total',
                'traveloka_scraper_success', 'traveloka_scraper_errors', $lockKey,
            ]);
            try { Cache::flush(); } catch (\Throwable $e) {}
            return response()->json([
                'success'      => true,
                'completed'    => true,
                'message'      => "Đã cào thành công {$successCnt} bài viết" . (count($errors) ? ', ' . count($errors) . ' lỗi' : ''),
                'successCount' => $successCnt,
                'errorCount'   => count($errors),
                'errors'       => $errors,
            ]);
        }

        @set_time_limit(300);
        ini_set('memory_limit', '1024M');

        $account = $this->loadAccount();

        foreach ($batchUrls as $i => $url) {
            try {
                if (ScrapedUrl::isUrlScraped($url, self::SOURCE)) continue;

                $result = $this->scrapeSingleUrl($url, $account, $catSlug);
                if ($result instanceof Post) {
                    $successCnt++;
                    ScrapedUrl::markAsScraped($url, self::SOURCE, $result->id);
                } elseif ($result === null) {
                    ScrapedUrl::markAsScraped($url, self::SOURCE, null);
                } else {
                    $errors[] = $url . ': Không cào được';
                }
            } catch (\Throwable $e) {
                $errors[] = $url . ': ' . $e->getMessage();
                Log::error('Traveloka Scraper: URL error', ['url' => $url, 'error' => $e->getMessage()]);
            }
            if ($i < count($batchUrls) - 1) usleep(500000);
        }

        $request->session()->put('traveloka_scraper_success', $successCnt);
        $request->session()->put('traveloka_scraper_errors', $errors);
        $request->session()->forget($lockKey);

        return response()->json([
            'success'      => true,
            'completed'    => false,
            'batchIndex'   => $batchIndex,
            'processed'    => $end,
            'total'        => $total,
            'successCount' => $successCnt,
            'errorCount'   => count($errors),
            'message'      => "Đã xử lý {$end}/{$total} URL...",
        ])->header('Content-Type', 'application/json; charset=utf-8');
    }

    private function scrapeSingleUrl(string $url, $account, string $catSlug)
    {
        $html = $this->fetchHtml($url);
        if (empty($html)) {
            throw new \Exception('HTML rỗng');
        }

        $meta = $this->extractArticleData($html, $url);
        if (empty($meta['title'])) {
            throw new \Exception('Không tìm thấy tiêu đề');
        }

        $title = trim($meta['title']);
        $desc  = trim($meta['description'] ?? '');
        $slug  = Str::slug($title);
        if (empty($slug)) {
            $slug = Str::slug(parse_url($url, PHP_URL_PATH));
        }

        if (Post::where('slug', $slug)->exists()) {
            Log::info('Traveloka Scraper: slug đã tồn tại, bỏ qua', ['url' => $url, 'slug' => $slug]);
            return null;
        }

        $category = Category::firstOrCreate(
            ['slug' => $catSlug],
            ['name' => self::CATEGORY_MAP[$catSlug] ?? 'Du lịch', 'status' => 'active', 'sort_order' => 0]
        );

        $seoImage = null;
        if (!empty($meta['thumbnail'])) {
            $seoImage = $this->downloadImage($meta['thumbnail'], $slug . '.jpg');
        }

        $content = $meta['content'] ?? '';
        $content = $this->processContent($content, $slug, $title);

        if (trim(strip_tags($content)) === '' && !empty($desc)) {
            $content = '<p>' . e($desc) . '</p>';
        }

        $publishedAt = now();
        if (!empty($meta['publishedDate']) && is_numeric($meta['publishedDate'])) {
            try {
                $publishedAt = \Carbon\Carbon::createFromTimestampMs((int) $meta['publishedDate']);
            } catch (\Throwable $e) {}
        }

        $post = Post::create([
            'name'            => $title,
            'slug'            => $slug,
            'content'         => $content,
            'seo_title'       => Str::limit($title, 100, ''),
            'seo_desc'        => Str::limit($desc, 160, ''),
            'seo_image'       => $seoImage,
            'seo_keywords'    => $this->extractKeywords($title, $desc),
            'category_id'     => $category->id,
            'account_id'      => $account->id,
            'status'          => 'published',
            'published_at'    => $publishedAt,
            'views'           => 0,
            'last_updated_by' => $account->id,
            'type'            => '',
        ]);

        return $post;
    }

    private function extractArticleData(string $html, string $url): array
    {
        $data = ['title' => null, 'description' => null, 'thumbnail' => null, 'content' => null, 'publishedDate' => null];

        if (preg_match('#<script id="__NEXT_DATA__"[^>]*>(.*?)</script>#s', $html, $m)) {
            $next = json_decode($m[1], true);
            if (is_array($next)) {
                $found = $this->findArticleInNextData($next, $url);
                if ($found) {
                    $data['title']         = $found['title']         ?? null;
                    $data['description']   = $found['description']   ?? ($found['shareDescription'] ?? null);
                    $data['thumbnail']     = $found['thumbnail']     ?? ($found['coverImage'] ?? ($found['imageUrl'] ?? null));
                    $data['content']       = $found['body']          ?? ($found['content'] ?? ($found['articleBody'] ?? null));
                    $data['publishedDate'] = $found['publishedDate'] ?? ($found['publishedAt'] ?? null);
                }
            }
        }

        if (empty($data['title'])) {
            if (preg_match('#<meta[^>]+property=["\']og:title["\'][^>]+content=["\']([^"\']+)["\']#i', $html, $m)) {
                $data['title'] = html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
            } elseif (preg_match('#<title>(.*?)</title>#is', $html, $m)) {
                $data['title'] = html_entity_decode(trim($m[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            }
        }
        if (empty($data['description'])) {
            if (preg_match('#<meta[^>]+name=["\']description["\'][^>]+content=["\']([^"\']+)["\']#i', $html, $m)) {
                $data['description'] = html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
            }
        }
        if (empty($data['thumbnail'])) {
            if (preg_match('#<meta[^>]+property=["\']og:image["\'][^>]+content=["\']([^"\']+)["\']#i', $html, $m)) {
                $data['thumbnail'] = $m[1];
            }
        }

        if (empty($data['content'])) {
            $data['content'] = $this->extractContentFromDom($html);
        }

        return $data;
    }

    private function findArticleInNextData(array $node, string $url, int $depth = 0): ?array
    {
        if ($depth > 15) return null;

        $urlPath = parse_url($url, PHP_URL_PATH);
        if (isset($node['title']) && is_string($node['title']) && (
            (isset($node['url']) && is_string($node['url']) && strpos($node['url'], $urlPath) !== false) ||
            (isset($node['shareLink']) && is_string($node['shareLink']) && strpos($node['shareLink'], $urlPath) !== false) ||
            isset($node['body']) || isset($node['articleBody']) || isset($node['content'])
        )) {
            return $node;
        }

        foreach ($node as $v) {
            if (is_array($v)) {
                $found = $this->findArticleInNextData($v, $url, $depth + 1);
                if ($found) return $found;
            }
        }
        return null;
    }

    private function extractContentFromDom(string $html): string
    {
        libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        @$dom->loadHTML('<?xml encoding="UTF-8">' . $html);
        libxml_clear_errors();
        $xpath = new DOMXPath($dom);

        // Find the exact div: class="css-1dbjc4n r-1loqt21 r-1otgn73 r-1i6wzkk r-lrvibr"
        $contentNode = null;
        $nodes = $xpath->query('//*[contains(@class,"css-1dbjc4n") and contains(@class,"r-1loqt21") and contains(@class,"r-1otgn73") and contains(@class,"r-1i6wzkk") and contains(@class,"r-lrvibr")]');
        if ($nodes && $nodes->length > 0) {
            $contentNode = $nodes->item(0);
        }

        if (!$contentNode) {
            return '';
        }

        $innerHtml = '';
        foreach ($contentNode->childNodes as $child) {
            $html = $dom->saveHTML($child);

            // Skip divs with specific class + style combinations
            if (preg_match('#<div[^>]*class="[^"]*css-1dbjc4n[^"]*r-1jgb5lz[^"]*"[^>]*style="#', $html) ||
                preg_match('#<div[^>]*class="[^"]*css-1dbjc4n[^"]*r-1udh08x[^"]*"[^>]*style="#', $html)) {
                continue;
            }

            // Skip share/metadata containers
            if (preg_match('#<div[^>]*class="[^"]*css-1dbjc4n[^"]*r-1awozwy[^"]*r-18u37iz[^"]*"#', $html) ||
                preg_match('#<div[^>]*class="[^"]*r-184en5c[^"]*"#', $html) ||
                preg_match('#<div[^>]*class="[^"]*css-1dbjc4n[^"]*r-x46dho[^"]*r-1d4dt7g[^"]*"#', $html)) {
                continue;
            }

            $innerHtml .= $html;
        }

        return (strlen(strip_tags($innerHtml)) > 100) ? $innerHtml : '';
    }

    private function processContent(string $content, string $slug, string $title = ''): string
    {
        if ($content === '') return '';

        // Remove scripts, styles, comments
        $content = preg_replace('#<script\b[^>]*>.*?</script>#is', '', $content);
        $content = preg_replace('#<style\b[^>]*>.*?</style>#is', '', $content);
        $content = preg_replace('#<!--.*?-->#s', '', $content);

        // Replace all <div> with <p>
        $content = preg_replace_callback('#<div\b([^>]*)>(.*?)</div>#is', function($m) {
            return '<p>' . $m[2] . '</p>';
        }, $content);

        // Remove all class attributes from non-img tags
        $content = preg_replace_callback('#<(?!img\b)(\w+)\b([^>]*)>#i', function($m) {
            $tagName = $m[1];
            $attrs   = $m[2];
            $attrs   = preg_replace('#\s*class="[^"]*"#i', '', $attrs);
            return '<' . $tagName . $attrs . '>';
        }, $content);

        // Download images and replace src, add title/alt
        $imageCounter = 0;
        $content = preg_replace_callback('#<img\b([^>]*)>#i', function($m) use ($slug, $title, &$imageCounter) {
            $imgAttrs = $m[1];
            if (!preg_match('#src=["\']([^"\']+)["\']#i', $imgAttrs, $srcMatch)) {
                return $m[0];
            }

            $srcUrl = $srcMatch[1];
            if (strpos($srcUrl, 'data:') === 0) {
                return $m[0];
            }

            $imageCounter++;
            $imageName = ($imageCounter === 1) ? $slug : $slug . '-' . $imageCounter;
            $localPath = $this->downloadImage($srcUrl, $imageName);

            if (!$localPath) {
                return $m[0];
            }

            $assetUrl = asset($localPath);
            $titleAttr = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
            $altAttr   = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');

            // Build new img tag with replaced src and added title/alt
            $newAttrs = preg_replace('#src=["\'][^"\']+["\']#i', 'src="' . $assetUrl . '"', $imgAttrs);
            if (!preg_match('#\btitle\s*=#i', $newAttrs)) {
                $newAttrs .= ' title="' . $titleAttr . '"';
            }
            if (!preg_match('#\balt\s*=#i', $newAttrs)) {
                $newAttrs .= ' alt="' . $altAttr . '"';
            }
            return '<img ' . $newAttrs . '>';
        }, $content);

        // Remove all <a> tags, keep content
        $content = preg_replace('#<a\b[^>]*>(.*?)</a>#is', '$1', $content);

        return $content;
    }

    private function downloadImage(string $url, string $slug): ?string
    {
        try {
            if (strpos($url, '//') === 0) $url = 'https:' . $url;
            if (strpos($url, 'http') !== 0) return null;

            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_TIMEOUT        => 30,
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36',
                CURLOPT_HTTPHEADER     => ['Accept: image/*,*/*;q=0.8', 'Referer: https://www.traveloka.com/'],
            ]);
            $bytes = curl_exec($ch);
            $code  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $ctype = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
            curl_close($ch);

            if ($bytes === false || $code !== 200 || empty($bytes)) return null;

            // Validate it's actually an image
            $isImage = preg_match('#^image/#i', $ctype) || in_array(bin2hex(substr($bytes, 0, 4)), ['89504e47', 'ffd8ffe0', '47494638'], true);
            if (!$isImage) return null;

            $ext = 'jpg';
            if (stripos($ctype, 'png') !== false) $ext = 'png';
            elseif (stripos($ctype, 'webp') !== false) $ext = 'webp';
            elseif (stripos($ctype, 'gif') !== false) $ext = 'gif';
            elseif (stripos($ctype, 'jpeg') !== false || stripos($ctype, 'jpg') !== false) $ext = 'jpg';

            $dir = public_path('client/assets/images/posts');
            if (!File::isDirectory($dir)) File::makeDirectory($dir, 0755, true);

            $filename = Str::slug($slug) . '.' . $ext;
            $filePath = $dir . DIRECTORY_SEPARATOR . $filename;

            // If file exists, add counter
            $counter = 1;
            while (File::exists($filePath)) {
                $filename = Str::slug($slug) . '-' . $counter . '.' . $ext;
                $filePath = $dir . DIRECTORY_SEPARATOR . $filename;
                $counter++;
            }

            file_put_contents($filePath, $bytes);
            Log::info('Traveloka Scraper: saved image', ['filename' => $filename, 'size' => strlen($bytes)]);

            return $filename;
        } catch (\Throwable $e) {
            Log::warning('Traveloka Scraper: download image failed', ['url' => $url, 'error' => $e->getMessage()]);
            return null;
        }
    }

    private function extractKeywords(string $title, string $desc): string
    {
        $text = strip_tags($title . ' ' . $desc);
        $text = preg_replace('/[^\p{L}\p{N}\s,]/u', '', $text);
        $stop = ['các','này','đó','với','cho','từ','của','trong','và','là','có','được','một','những','nhiều','rất','sẽ','đã','đang'];
        $words = preg_split('/[\s,]+/u', $text);
        $words = array_filter($words, fn($w) => mb_strlen(trim($w), 'UTF-8') >= 3 && !in_array(mb_strtolower(trim($w), 'UTF-8'), $stop));
        return implode(', ', array_slice(array_unique($words), 0, 10));
    }

    private function fetchHtml(string $url): string
    {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 5,
            CURLOPT_TIMEOUT        => 60,
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_ENCODING       => 'gzip, deflate',
            CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36',
            CURLOPT_HTTPHEADER     => [
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language: vi,en-US;q=0.9,en;q=0.8',
                'Upgrade-Insecure-Requests: 1',
            ],
        ]);
        $html = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_error($ch);
        curl_close($ch);

        if ($html === false || $code !== 200) {
            throw new \Exception('HTTP ' . $code . ($err ? ' - ' . $err : ''));
        }
        return $html;
    }

    public function loadAccount()
    {
        $id = Auth::id();
        if (!$id) throw new \Exception('Bạn chưa đăng nhập');
        $account = \App\Models\Account::find($id);
        if (!$account) throw new \Exception('Không tìm thấy tài khoản');
        return $account;
    }
}
