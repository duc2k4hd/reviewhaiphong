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

    private const GEMINI_API_URL = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-flash-latest:generateContent';
    private const GEMINI_API_KEY = 'AIzaSyDCkhiyzPrXWEHrrXWgmfz_svPSqlmcBvg';

    private const CATEGORY_MAP = [
        'du-lich'         => 'Du lịch',
        'am-thuc'         => 'Ẩm thực',
        'check-in'        => 'Check-in',
        'dich-vu'         => 'Dịch vụ',
        'review-tong-hop' => 'Review tổng hợp',
    ];

    // Map Traveloka URL categories to local category slugs
    private const TRAVELOKA_CATEGORY_MAP = [
        'destination'     => 'du-lich',
        'activities'      => 'dich-vu',
        'culinary'        => 'am-thuc',
        'tips'            => 'du-lich',
        'accommodations'  => 'check-in',
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
        ], [
            'urls.required'     => 'Vui lòng nhập ít nhất một URL.',
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
            'traveloka_scraper_lock', 'traveloka_scraper_urls', 'traveloka_scraper_total',
            'traveloka_scraper_success', 'traveloka_scraper_errors',
        ]);
        $request->session()->put('traveloka_scraper_urls', $newUrls);
        $request->session()->put('traveloka_scraper_total', count($newUrls));
        $request->session()->put('traveloka_scraper_success', 0);
        $request->session()->put('traveloka_scraper_errors', []);

        if ($skipped > 0) {
            session()->flash('info', "Đã bỏ qua {$skipped} URL đã cào trước đó. Xử lý " . count($newUrls) . " URL mới.");
        }

        return view('admin.traveloka-scraper.processing', [
            'totalUrls' => count($newUrls),
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
        $total      = count($urls);
        $start      = $batchIndex * self::BATCH_SIZE;
        $end        = min($start + self::BATCH_SIZE, $total);
        $batchUrls  = array_slice($urls, $start, self::BATCH_SIZE);
        $successCnt = $request->session()->get('traveloka_scraper_success', 0);
        $errors     = $request->session()->get('traveloka_scraper_errors', []);

        if (empty($batchUrls)) {
            $request->session()->forget([
                'traveloka_scraper_urls', 'traveloka_scraper_total',
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

        @set_time_limit(900);
        ini_set('memory_limit', '1024M');

        $account = $this->loadAccount();

        foreach ($batchUrls as $i => $url) {
            try {
                if (ScrapedUrl::isUrlScraped($url, self::SOURCE)) continue;

                // Extract category from URL
                $catSlug = $this->getCategoryFromUrl($url);
                if (!$catSlug) {
                    $errors[] = $url . ': Không thể xác định danh mục từ URL';
                    continue;
                }

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

        // AI rewrite: seo_title (60-70 chars), seo_desc (250-300 chars), content.
        // Falls back to original title/desc/content if the call fails.
        $seoTitle = Str::limit($title, 100, '');
        $seoDesc  = Str::limit($desc, 160, '');
        $rewritten = $this->rewriteWithAI($title, $content);
        if ($rewritten) {
            if (!empty($rewritten['seo_title']))  $seoTitle = $rewritten['seo_title'];
            if (!empty($rewritten['seo_desc']))   $seoDesc  = $rewritten['seo_desc'];
            if (!empty($rewritten['content']))    $content  = $rewritten['content'];
            Log::info('Traveloka Scraper: AI rewrite applied', [
                'url'         => $url,
                'title_len'   => mb_strlen($seoTitle),
                'desc_len'    => mb_strlen($seoDesc),
                'content_len' => mb_strlen($content),
            ]);
        } else {
            Log::warning('Traveloka Scraper: AI rewrite skipped, using original content', ['url' => $url]);
        }

        // Safety net: remove any <h1> the model may still have emitted (the post's
        // main title is rendered from Post::$name, so an in-content h1 duplicates it).
        $content = preg_replace('#<h1\b[^>]*>.*?</h1>#is', '', $content);

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
            'seo_title'       => $seoTitle,
            'seo_desc'        => $seoDesc,
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

        // Traveloka article chunks are divs with both r-1jgb5lz and r-1fnihl3. There
        // are typically two chunks: the header (breadcrumb + title + author + share
        // buttons) and the body. We want only the body, so we skip any chunk that
        // contains the share-container descendant — that's the reliable header flag.
        // The chunk's direct-child div with r-11yh6sk r-l0gqae is the content column
        // (grabbing it excludes the trailing "recommended articles" and product
        // widgets that sit alongside the body column).
        $sections = $xpath->query(
            '//div[contains(concat(" ", normalize-space(@class), " "), " r-1jgb5lz ")'
            . ' and contains(concat(" ", normalize-space(@class), " "), " r-1fnihl3 ")'
            . ' and not(.//*[@data-testid="desktop-detail-share-container"])'
            . ' and not(.//*[@data-testid="mobile-detail-share-container"])]'
            . '/div[contains(concat(" ", normalize-space(@class), " "), " r-11yh6sk ")'
            . ' and contains(concat(" ", normalize-space(@class), " "), " r-l0gqae ")]'
        );
        $collected = '';
        if ($sections && $sections->length > 0) {
            foreach ($sections as $section) {
                $collected .= $dom->saveHTML($section);
            }
        }

        if (strlen(strip_tags($collected)) > 100) {
            return $collected;
        }

        // Fallback: the article container is the next sibling div after #navbar-offset.
        $fallback = $xpath->query('//*[@id="navbar-offset"]/following-sibling::div[1]');
        if ($fallback && $fallback->length > 0) {
            $innerHtml = '';
            foreach ($fallback->item(0)->childNodes as $child) {
                $innerHtml .= $dom->saveHTML($child);
            }
            if (strlen(strip_tags($innerHtml)) > 100) {
                return $innerHtml;
            }
        }

        return '';
    }

    private function processContent(string $content, string $slug, string $title = ''): string
    {
        if ($content === '') return '';

        // Remove scripts, styles, comments, SVG icons
        $content = preg_replace('#<script\b[^>]*>.*?</script>#is', '', $content);
        $content = preg_replace('#<style\b[^>]*>.*?</style>#is', '', $content);
        $content = preg_replace('#<svg\b[^>]*>.*?</svg>#is', '', $content);
        $content = preg_replace('#<!--.*?-->#s', '', $content);

        // Strip Traveloka brand/watermark images (godwit logo etc.) — identified by
        // the RN Web class combo r-sdzlij + r-mabqd8, which Traveloka uses for inline
        // brand graphics, not article photos.
        $content = preg_replace(
            '#<img\b(?=[^>]*\bclass="[^"]*\br-sdzlij\b[^"]*")(?=[^>]*\bclass="[^"]*\br-mabqd8\b[^"]*")[^>]*>#i',
            '',
            $content
        );

        // Convert Traveloka's React Native Web <div dir="auto"> text blocks into <p>,
        // and unwrap all other <div> layout wrappers. Done via DOM so nested pairs
        // are matched correctly (regex cannot balance nested divs).
        $content = $this->normalizeDivs($content);

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

            $assetUrl = '/client/assets/images/posts/' . $localPath;
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

    private function normalizeDivs(string $html): string
    {
        if (trim($html) === '') return '';

        libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        @$dom->loadHTML('<?xml encoding="UTF-8"?><!DOCTYPE html><html><body>' . $html . '</body></html>');
        libxml_clear_errors();

        $body = $dom->getElementsByTagName('body')->item(0);
        if (!$body) return $html;

        $xpath = new DOMXPath($dom);

        // Drop share/social widgets (Traveloka's desktop-detail-share-container and
        // mobile variant). Must happen before the unwrap pass, otherwise the inner
        // share buttons leak into the output as loose text/icons.
        $toRemove = iterator_to_array($xpath->query(
            '//*[@data-testid="desktop-detail-share-container" or @data-testid="mobile-detail-share-container"]',
            $body
        ));
        foreach ($toRemove as $node) {
            $node->parentNode->removeChild($node);
        }

        // Drop RN Web pressable wrappers — div[tabindex=0] with the full atomic class
        // combo Traveloka uses for interactive buttons (like/save/slider-nav/etc.).
        // These are SVG-icon buttons inside article content; stripping the subtree
        // keeps stray button labels/icons out of the output.
        $pressables = iterator_to_array($xpath->query(
            '//div[@tabindex="0"'
            . ' and contains(concat(" ", normalize-space(@class), " "), " r-1loqt21 ")'
            . ' and contains(concat(" ", normalize-space(@class), " "), " r-1otgn73 ")'
            . ' and contains(concat(" ", normalize-space(@class), " "), " r-1i6wzkk ")'
            . ' and contains(concat(" ", normalize-space(@class), " "), " r-lrvibr ")]',
            $body
        ));
        foreach ($pressables as $node) {
            if ($node->parentNode) {
                $node->parentNode->removeChild($node);
            }
        }

        // Drop inline hotel/product booking cards — div with class combo r-kdyh1x +
        // r-1udh08x is Traveloka's white-bg card container (name, rating, address,
        // "Xem giá"/"Đặt ngay" CTAs). These are ads/affiliate widgets injected into
        // the article body, not content.
        $cards = iterator_to_array($xpath->query(
            '//div[contains(concat(" ", normalize-space(@class), " "), " r-kdyh1x ")'
            . ' and contains(concat(" ", normalize-space(@class), " "), " r-1udh08x ")]',
            $body
        ));
        foreach ($cards as $node) {
            if ($node->parentNode) {
                $node->parentNode->removeChild($node);
            }
        }

        // Drop Traveloka's horizontal product-row wrapper (article-chunk combo
        // r-1jgb5lz + r-1fnihl3 with r-18u37iz flex-row + r-136ojw6 padding). Used
        // for inline booking/affiliate cards rendered as a row inside article body.
        // Skip rows that contain a heading — RN Web reuses r-136ojw6 for section-
        // title rows in the body too (e.g. "1. Phố đi bộ Tam Bạc" <h3>).
        $rows = iterator_to_array($xpath->query(
            '//div[contains(concat(" ", normalize-space(@class), " "), " r-18u37iz ")'
            . ' and contains(concat(" ", normalize-space(@class), " "), " r-1jgb5lz ")'
            . ' and contains(concat(" ", normalize-space(@class), " "), " r-1fnihl3 ")'
            . ' and contains(concat(" ", normalize-space(@class), " "), " r-136ojw6 ")'
            . ' and not(.//h1) and not(.//h2) and not(.//h3)'
            . ' and not(.//h4) and not(.//h5) and not(.//h6)]',
            $body
        ));
        foreach ($rows as $node) {
            if ($node->parentNode) {
                $node->parentNode->removeChild($node);
            }
        }

        // Drop Traveloka affiliate bullet-list rows. The bullet-dot marker is a
        // 24×24 square div with combo r-obd0qt + r-1777fci + r-1kb76zh — unique to
        // this widget. Each dot's parent is the bullet row (dot + link text like
        // "Khách sạn 5 sao Hải Phòng" pointing to funnel_source=content_article).
        $dots = iterator_to_array($xpath->query(
            '//div[contains(concat(" ", normalize-space(@class), " "), " r-obd0qt ")'
            . ' and contains(concat(" ", normalize-space(@class), " "), " r-1777fci ")'
            . ' and contains(concat(" ", normalize-space(@class), " "), " r-1kb76zh ")]',
            $body
        ));
        foreach ($dots as $dot) {
            $row = $dot->parentNode;
            if ($row && $row->parentNode) {
                $row->parentNode->removeChild($row);
            }
        }

        // Drop another Traveloka affiliate/widget wrapper — unique class combo
        // r-150rngu + r-buy8e9 + r-2eszeu + r-1sncvnh (padded card block injected
        // into article body, not content).
        $widgetBlocks = iterator_to_array($xpath->query(
            '//div[contains(concat(" ", normalize-space(@class), " "), " r-150rngu ")'
            . ' and contains(concat(" ", normalize-space(@class), " "), " r-buy8e9 ")'
            . ' and contains(concat(" ", normalize-space(@class), " "), " r-2eszeu ")'
            . ' and contains(concat(" ", normalize-space(@class), " "), " r-1sncvnh ")]',
            $body
        ));
        foreach ($widgetBlocks as $node) {
            if ($node->parentNode) {
                $node->parentNode->removeChild($node);
            }
        }

        // Drop Traveloka's "Discover flight with Traveloka" widget — the h2 heading
        // plus its following sibling container (div.r-5oul0u holding flight cards
        // with route/price/"Đặt Ngay"). Affiliate booking block, not article body.
        $flightHeadings = iterator_to_array($xpath->query(
            '//*[self::h1 or self::h2 or self::h3]'
            . '[contains(normalize-space(.), "Discover flight with Traveloka")]',
            $body
        ));
        foreach ($flightHeadings as $heading) {
            $next = $heading->nextSibling;
            while ($next && $next->nodeType !== XML_ELEMENT_NODE) {
                $next = $next->nextSibling;
            }
            if ($next) {
                $next->parentNode->removeChild($next);
            }
            if ($heading->parentNode) {
                $heading->parentNode->removeChild($heading);
            }
        }

        // Safety net: any remaining widget container carrying the r-5oul0u class.
        $flightContainers = iterator_to_array($xpath->query(
            '//div[contains(concat(" ", normalize-space(@class), " "), " r-5oul0u ")]',
            $body
        ));
        foreach ($flightContainers as $node) {
            if ($node->parentNode) {
                $node->parentNode->removeChild($node);
            }
        }

        // Rename <div dir="auto"> to <p>, but only when it's a "leaf" block with no
        // descendant that's already a <p> or another dir="auto" wrapper. Traveloka
        // sometimes nests a real <p> inside a dir="auto" div; converting the wrapper
        // in that case would produce invalid <p><p>...</p></p>.
        $textDivs = iterator_to_array($xpath->query(
            '//div[@dir="auto" and not(.//div[@dir="auto"]) and not(.//p)]',
            $body
        ));
        foreach ($textDivs as $div) {
            $p = $dom->createElement('p');
            while ($div->firstChild) {
                $p->appendChild($div->firstChild);
            }
            $div->parentNode->replaceChild($p, $div);
        }

        // Unwrap every remaining <div>: splice its children into its parent, remove div.
        // Loop until no divs remain — nested divs are peeled one layer at a time.
        while (true) {
            $divs = $body->getElementsByTagName('div');
            if ($divs->length === 0) break;
            $div = $divs->item(0);
            $parent = $div->parentNode;
            while ($div->firstChild) {
                $parent->insertBefore($div->firstChild, $div);
            }
            $parent->removeChild($div);
        }

        $out = '';
        foreach ($body->childNodes as $child) {
            $out .= $dom->saveHTML($child);
        }
        return $out;
    }

    private function rewriteWithAI(string $title, string $content): ?array
    {
        if (trim($content) === '') {
            Log::warning('Gemini rewrite: empty content, skipping');
            return null;
        }

        Log::info('Gemini rewrite: start', [
            'title'       => $title,
            'content_len' => mb_strlen($content),
        ]);

        $prompt = <<<PROMPT
Bạn là chuyên gia SEO và content writer cho website du lịch "Review Hải Phòng".

Tôi cung cấp một bài viết gốc (tiêu đề + HTML). Nhiệm vụ: viết lại thành một bài chuẩn SEO, tự nhiên, hay hơn bản gốc, hoàn toàn thuộc về website Review Hải Phòng.

YÊU CẦU CỨNG (bắt buộc tuân thủ):

1. seo_title — độ dài TỪ 60 ĐẾN 70 KÝ TỰ (đếm cả dấu cách):
   - KHÔNG chứa năm, tháng (cấm "2024", "2025", "tháng 3"... và mọi biến thể).
   - KHÔNG chứa tên thương hiệu khác (cấm "Traveloka", "Booking", "Agoda", "Klook", "Expedia", "Tripadvisor"...).
   - KHÔNG dùng emoji, ký tự đặc biệt, dấu ngoặc kép, dấu gạch "|".
   - Phản ánh đúng chủ đề bài viết, hấp dẫn, tự nhiên, tiếng Việt có dấu.

2. seo_desc — độ dài TỪ 250 ĐẾN 300 KÝ TỰ (đếm cả dấu cách):
   - Các ràng buộc như seo_title (cấm năm/tháng, cấm thương hiệu khác, không emoji).
   - Tóm tắt đầy đủ, kích thích click, viết như một đoạn văn trôi chảy.

3. content — HTML sạch, chi tiết, dài hơn và CHẤT LƯỢNG HƠN bản gốc:
   - CHỈ dùng các thẻ: <h2>, <h3>, <p>, <ul>, <ol>, <li>, <strong>, <em>, <blockquote>, <img>.
   - KHÔNG dùng <div>, <span>, <section>, <article>, không class, không style, không data-*.
   - TUYỆT ĐỐI KHÔNG chứa <h1> ở bất kỳ vị trí nào (tiêu đề bài viết đã hiển thị ngoài content — đặt <h1> ở đây sẽ gây trùng lặp SEO).
   - GIỮ NGUYÊN tất cả thẻ <img> có trong bài gốc (src, alt, title không đổi) — không bỏ, không thêm ảnh mới.
   - Đặt mỗi <img> ĐÚNG ngữ cảnh: ảnh về món ăn thì đặt gần đoạn văn nói về món ăn đó, ảnh về địa điểm thì đặt gần đoạn mô tả địa điểm đó. Phân bố đều trong bài.
   - Thay MỌI đề cập đến "Traveloka" (ở bất kỳ dạng nào: Traveloka, traveloka.com, "theo Traveloka"...) bằng "Review Hải Phòng".
   - Xóa mọi lời kêu gọi đặt vé/phòng qua Traveloka hoặc dẫn link đối tác.
   - Văn phong thân thiện, chuyên nghiệp, cung cấp giá trị cho người đọc (thêm mẹo, lưu ý, gợi ý trải nghiệm nếu phù hợp).
   - Mở đầu bằng 1 đoạn <p> giới thiệu, sau đó chia <h2>/<h3> mạch lạc, kết bằng 1 đoạn tổng kết.

ĐẦU RA: CHỈ TRẢ VỀ JSON hợp lệ theo schema dưới đây, KHÔNG kèm markdown, KHÔNG ```json, KHÔNG chú thích:

{
  "seo_title": "...",
  "seo_desc": "...",
  "content": "<h2>...</h2><p>...</p><img src=\"...\" alt=\"...\" title=\"...\"><p>...</p>..."
}

BÀI GỐC:

TIÊU ĐỀ: {$title}

NỘI DUNG HTML:
{$content}
PROMPT;

        $payload = json_encode([
            'contents' => [[
                'parts' => [['text' => $prompt]],
            ]],
            'generationConfig' => [
                'responseMimeType' => 'application/json',
                'temperature'      => 0.7,
                'maxOutputTokens'  => 32768,
            ],
        ], JSON_UNESCAPED_UNICODE);

        try {
            $maxAttempts = 4;
            $backoff     = [0, 5, 15, 30]; // seconds
            $response    = false;
            $code        = 0;
            $err         = '';

            for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
                if ($backoff[$attempt - 1] > 0) sleep($backoff[$attempt - 1]);

                $ch = curl_init();
                curl_setopt_array($ch, [
                    CURLOPT_URL            => self::GEMINI_API_URL,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_POST           => true,
                    CURLOPT_POSTFIELDS     => $payload,
                    CURLOPT_HTTPHEADER     => [
                        'Content-Type: application/json',
                        'X-goog-api-key: ' . self::GEMINI_API_KEY,
                    ],
                    CURLOPT_TIMEOUT        => 180,
                    CURLOPT_CONNECTTIMEOUT => 15,
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_SSL_VERIFYHOST => false,
                ]);
                $response = curl_exec($ch);
                $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $err  = curl_error($ch);
                curl_close($ch);

                Log::info('Gemini rewrite: response received', [
                    'attempt'       => $attempt,
                    'http_code'     => $code,
                    'response_size' => is_string($response) ? strlen($response) : 0,
                    'curl_error'    => $err,
                ]);

                if ($response !== false && $code === 200) break;

                // Retry only on transient errors (429 rate limit, 500/502/503/504 server).
                if (!in_array($code, [429, 500, 502, 503, 504], true)) break;
                if ($attempt === $maxAttempts) break;
                Log::info('Gemini rewrite: transient error, will retry', ['attempt' => $attempt, 'code' => $code]);
            }

            if ($response === false || $code !== 200) {
                Log::warning('Gemini rewrite failed', ['code' => $code, 'err' => $err, 'body' => substr((string) $response, 0, 2000)]);
                return null;
            }

            $body = json_decode($response, true);
            $text = $body['candidates'][0]['content']['parts'][0]['text'] ?? null;
            if (!is_string($text) || trim($text) === '') {
                $finishReason = $body['candidates'][0]['finishReason'] ?? 'UNKNOWN';
                $promptFeedback = $body['promptFeedback'] ?? null;
                Log::warning('Gemini rewrite: empty text', [
                    'finishReason'   => $finishReason,
                    'promptFeedback' => $promptFeedback,
                    'resp'           => substr($response, 0, 2000),
                ]);
                return null;
            }

            Log::info('Gemini rewrite: text received', ['text_len' => strlen($text)]);

            // In case the model wrapped output in ```json ... ``` despite responseMimeType.
            $text = trim($text);
            if (strpos($text, '```') === 0) {
                $text = preg_replace('#^```(?:json)?\s*#i', '', $text);
                $text = preg_replace('#\s*```$#', '', $text);
            }

            $parsed = json_decode($text, true);
            if (!is_array($parsed)) {
                Log::warning('Gemini rewrite: invalid JSON', ['text' => substr($text, 0, 500)]);
                return null;
            }

            return [
                'seo_title' => isset($parsed['seo_title']) ? trim((string) $parsed['seo_title']) : '',
                'seo_desc'  => isset($parsed['seo_desc'])  ? trim((string) $parsed['seo_desc'])  : '',
                'content'   => isset($parsed['content'])   ? (string) $parsed['content']         : '',
            ];
        } catch (\Throwable $e) {
            Log::warning('Gemini rewrite exception', ['error' => $e->getMessage()]);
            return null;
        }
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

    private function getCategoryFromUrl(string $url): ?string
    {
        if (preg_match('#/vi-vn/explore/(destination|activities|culinary|tips|accommodations)/#i', $url, $matches)) {
            $travelokaCategory = strtolower($matches[1]);
            return self::TRAVELOKA_CATEGORY_MAP[$travelokaCategory] ?? null;
        }
        return null;
    }
}
