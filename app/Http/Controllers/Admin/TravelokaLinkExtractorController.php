<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TravelokaLinkExtractorController extends Controller
{
    private const API_BASE = 'https://www.traveloka.com/api/v2/cms/article-feed';
    private const ARTICLE_URL_REGEX = '#^https?://(www\.)?traveloka\.com/vi-vn/explore/(destination|activities|culinary|tips|accommodations)/.+#i';
    private const DEFAULT_MAX_PAGES = 20;
    private const HARD_CAP_PAGES = 1000;
    private const ITEMS_PER_PAGE = 9;

    public function index()
    {
        return view('admin.traveloka-link-extractor.index');
    }

    public function extract(Request $request)
    {
        $request->validate([
            'urls'      => 'required|string',
            'max_pages' => 'nullable|integer|min:1|max:' . self::HARD_CAP_PAGES,
        ]);

        $maxPages = (int) ($request->input('max_pages') ?: self::DEFAULT_MAX_PAGES);

        $urls = array_values(array_filter(array_map('trim', explode("\n", $request->input('urls')))));
        if (empty($urls)) {
            return back()->withErrors(['urls' => 'Vui lòng nhập ít nhất một URL.'])->withInput();
        }

        // Extract keywords from URLs
        $keywords = [];
        foreach ($urls as $i => $u) {
            if (!preg_match('#[?&]q=([^&]+)#i', $u, $m)) {
                return back()->withErrors([
                    'urls' => 'URL ở dòng ' . ($i + 1) . ' không có từ khóa search (?q=...): ' . $u,
                ])->withInput();
            }
            $keywords[] = urldecode($m[1]);
        }

        @set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $allArticles  = [];
        $perCategory  = [];
        $failedUrls   = [];

        foreach ($keywords as $keyword) {
            try {
                // Fetch first page to get total count
                $firstPage = $this->fetchArticles($keyword, 1);
                if (empty($firstPage['articleResult'])) {
                    $failedUrls[] = ['url' => $keyword, 'error' => 'Không có kết quả'];
                    continue;
                }

                $totalItems = $firstPage['total'] ?? 0;
                $totalPages = (int) ceil($totalItems / self::ITEMS_PER_PAGE);
                $pagesToScan = min($totalPages, $maxPages);

                Log::info('Traveloka Link Extractor: scanning', [
                    'keyword' => $keyword,
                    'detected_total_pages' => $totalPages,
                    'will_scan' => $pagesToScan,
                ]);

                $foundForThis = [];
                foreach ($firstPage['articleResult'] as $article) {
                    $articleUrl = $article['url'] ?? null;
                    if ($articleUrl && preg_match(self::ARTICLE_URL_REGEX, $articleUrl)) {
                        $foundForThis[$articleUrl] = true;
                    }
                }

                for ($page = 2; $page <= $pagesToScan; $page++) {
                    usleep(400000);
                    try {
                        $pageData = $this->fetchArticles($keyword, $page);
                        foreach ($pageData['articleResult'] ?? [] as $article) {
                            $articleUrl = $article['url'] ?? null;
                            if ($articleUrl && preg_match(self::ARTICLE_URL_REGEX, $articleUrl)) {
                                $foundForThis[$articleUrl] = true;
                            }
                        }
                    } catch (\Throwable $e) {
                        Log::warning('Traveloka Link Extractor: page fetch failed', [
                            'keyword' => $keyword,
                            'page' => $page,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }

                foreach (array_keys($foundForThis) as $articleUrl) {
                    $allArticles[$articleUrl] = true;
                }

                $perCategory[] = [
                    'url'          => $keyword,
                    'total_pages'  => $totalPages,
                    'scanned'      => $pagesToScan,
                    'article_count' => count($foundForThis),
                ];
            } catch (\Throwable $e) {
                Log::error('Traveloka Link Extractor: keyword failed', [
                    'keyword' => $keyword,
                    'error' => $e->getMessage(),
                ]);
                $failedUrls[] = ['url' => $keyword, 'error' => $e->getMessage()];
            }
        }

        $links = array_keys($allArticles);

        if (empty($links)) {
            return back()->withErrors(['urls' => 'Không trích xuất được link bài viết nào. Vui lòng kiểm tra lại từ khóa.'])->withInput();
        }

        return view('admin.traveloka-link-extractor.result', [
            'links'       => $links,
            'count'       => count($links),
            'perCategory' => $perCategory,
            'failedUrls'  => $failedUrls,
            'totalUrls'   => count($urls),
            'maxPages'    => $maxPages,
        ]);
    }

    private function getCookieString(): string
    {
        return 'tvl=qgdHX7GvehrD9XH5a3S4PUiOJGezXQ9yizVaSxTklwrLYY64AE4apiD1qmHRGaV8gGAQoV6xR5wi1hxtboYegx0JoHbuxL9J5IDMykh7yrn/kmgjvZe3CXlrOt5A94G1h8SGYm0D03zEW7S7g02l9zkAPbkMGQ6AJj+0Bs51j2cR6ofV09dfo5BCGKiXqpyrMb21ZVNUbjvy8ZgwvKbJrbBRjIATsfBE2ATDMmYjNba9vYln2i9dSa5aKve/ygj0FjazZodoQxs/6XXwh1THf5LKw5p4pzk+V/RVc4t+EgLM0WMA1zSky7I1/5su3gOqpXm4SPierWB99H5q/OuB2ZygAhGR3hLectopvMWNLbAS0xzxL9MUljuteQYLmeToxbVWc9mUJC6U6r8wkx04c+4CDTx/jJt5ZXoEQTwGFtmvFcCuLSwzN6i0oSIN7Me18DeuItROuvCunuzMntBL3wY+YvMclqwgXq10hsBKFkY148X15DpeseJWr8HNycgbiHVsU7NMwFAZor4I04QSczHBY+r/3XFsAcPJseKrB4A=; tvs=qgdHX7GvehrD9XH5a3S4PXWKx93/3Xi103f/kPpnhg1IQez7AjqOPow88qqCMiL7CqvJjpn5Z2svD8QZzAmUN07gmFQkK2qgsdbWEYgFfB5uk0fUx7sD+NMcK9CmgVocrii3pNJ2kgG/MGUmzAKhwmCIB1NXpx+g05VnrVlNT1r5u09yAru40JsnILlCW/Axg5IrbUsETFKICIsCiEtu4b3q7uOmWYSVDu55V1wsO1vho7HGyk2U+d1Nu0O0zrhrg43qby0ICwtMQsEEYY/mJ063CCJo+Z+R9n/X0FgUdFdWi6NKpEDgL3xVNnQjLp40FhrkmWFU4Pn0+ElScsbvvbofMd3cXMWBgVtNsQwlP+XSOOvgeG4yUhe3CyoouPZaITJFvwmZCVbT2QD/NUwvNMymqr3Qvz2BhnVP8ZldAPKMkVHpP5sWkP/ou/QacaB13jW7f6f85zK7XA1xLrLbn3wpMY91AYFzJ6h8za/vSrng40uUoDT+qJIv0oQGNB1A; tv_cs=1; tv_lt=1776665524574; tv_mcc_id=01KPMQFQRS8XEHKKA6C2DDP764; tv_user={"authorizationLevel":100,"id":null}; tv-repeat-visit=true; ttcsid=1776664693336::wbxuAAKVviH7YDwAEtFm.7.1776665383973.0::1.690599.629566::690539.5.682.1383::690829.152.900; ttcsid_CUM82PBC77U4QKJNCRL0=1776664698177::s2C6s_WU5dA8PV_MSH3D.5.1776665383973.1; tvo=L2FwaS9zZW4vc3M=';
    }

    private function fetchArticles(string $keyword, int $page = 1): array
    {
        $url = self::API_BASE . '/search';

        $body = json_encode([
            'fields'           => [],
            'data'             => [
                'keyword' => $keyword,
                'page'    => $page,
                'limit'   => self::ITEMS_PER_PAGE,
            ],
            'clientInterface'  => 'desktop',
        ]);

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 5,
            CURLOPT_TIMEOUT        => 45,
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_ENCODING       => 'gzip, deflate',
            CURLOPT_CUSTOMREQUEST  => 'POST',
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36',
            CURLOPT_HTTPHEADER     => [
                'Accept: */*',
                'Accept-Language: vi-VN,vi;q=0.9,id-ID;q=0.8,id;q=0.7,fr-FR;q=0.6,fr;q=0.5,en-US;q=0.4,en;q=0.3',
                'Cache-Control: no-cache',
                'Content-Type: application/json',
                'Cookie: ' . $this->getCookieString(),
                'Origin: https://www.traveloka.com',
                'Pragma: no-cache',
                'Referer: https://www.traveloka.com/vi-vn/explore/search',
                'Sec-Fetch-Dest: empty',
                'Sec-Fetch-Mode: cors',
                'Sec-Fetch-Site: same-origin',
                'tv-country: VN',
                'tv-currency: VND',
                'tv-language: vi_VN',
                'x-client-interface: desktop',
                'x-domain: content',
                'x-route-prefix: vi-vn',
            ],
            CURLOPT_COOKIEJAR      => sys_get_temp_dir() . '/traveloka_cookies.txt',
            CURLOPT_COOKIEFILE     => sys_get_temp_dir() . '/traveloka_cookies.txt',
        ]);
        $response = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_error($ch);
        curl_close($ch);

        if ($response === false || $code !== 200) {
            throw new \Exception('HTTP ' . $code . ($err ? ' - ' . $err : ''));
        }

        $data = json_decode($response, true);
        if (!is_array($data) || !isset($data['data'])) {
            throw new \Exception('Invalid API response');
        }

        return $data['data'];
    }
}

