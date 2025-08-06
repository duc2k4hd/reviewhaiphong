<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Post;
use Illuminate\Support\Facades\Cache;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\Tags\Url;

class SitemapController extends Controller
{
    public function generate()
    {
        try {
            $this->generateSitemapFile();
            return response()->json([
                'success' => true,
                'message' => '✅ Đã tạo sitemap đẹp thành công!'
            ]);
        } catch (\Exception $e) {
            \Log::error('Sitemap generation error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi tạo sitemap.'
            ], 500);
        }
    }

    public function xml()
    {
        $sitemapPath = public_path('sitemap.xml');
        
        // Generate sitemap if it doesn't exist
        if (!file_exists($sitemapPath)) {
            $this->generateSitemapFile();
        }

        // Check if file is older than 1 hour, regenerate if needed
        if (file_exists($sitemapPath) && filemtime($sitemapPath) < now()->subHour()->timestamp) {
            $this->generateSitemapFile();
        }

        if (file_exists($sitemapPath)) {
            $xml = file_get_contents($sitemapPath);
            return response($xml, 200)->header('Content-Type', 'application/xml');
        }

        return response('Sitemap not found', 404);
    }

    private function generateSitemapFile(): void
    {
        // Cache the sitemap data for 1 hour
        $sitemapData = Cache::remember('sitemap_data', now()->addHour(), function () {
            return [
                'posts' => Post::select(['id', 'slug', 'category_id', 'updated_at'])
                    ->with('category:id,slug')
                    ->published()
                    ->get(),
                'categories' => Category::select(['id', 'name', 'slug', 'updated_at'])
                    ->active()
                    ->whereIn('slug', [
                        'review-hai-phong', 'du-lich', 'am-thuc', 
                        'check-in', 'dich-vu', 'tin-tuc', 'gioi-thieu'
                    ])
                    ->get()
            ];
        });

        $sitemap = Sitemap::create();

        // Add homepage
        $sitemap->add(
            Url::create('/')
                ->setLastModificationDate(now())
                ->setChangeFrequency(Url::CHANGE_FREQUENCY_DAILY)
                ->setPriority(1.0)
        );

        // Add main review page
        $sitemap->add(
            Url::create('/review-hai-phong')
                ->setLastModificationDate(now())
                ->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY)
                ->setPriority(0.9)
        );
        
        // Add introduction page
        $sitemap->add(
            Url::create('/gioi-thieu')
                ->setLastModificationDate(now())
                ->setChangeFrequency(Url::CHANGE_FREQUENCY_MONTHLY)
                ->setPriority(0.7)
        );

        // Add category pages
        foreach ($sitemapData['categories'] as $category) {
            $sitemap->add(
                Url::create("/{$category->slug}")
                    ->setLastModificationDate($category->updated_at ?? now())
                    ->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY)
                    ->setPriority(0.8)
            );
        }

        // Add post pages
        foreach ($sitemapData['posts'] as $post) {
            if ($post->category) {
                $sitemap->add(
                    Url::create("/{$post->category->slug}/{$post->slug}")
                        ->setLastModificationDate($post->updated_at ?? now())
                        ->setChangeFrequency(Url::CHANGE_FREQUENCY_MONTHLY)
                        ->setPriority(0.6)
                );
            }
        }

        // Render XML
        $xml = $sitemap->render();

        // Add XSL stylesheet
        $xmlWithXsl = preg_replace(
            '/<\?xml version="1.0" encoding="UTF-8"\?>/',
            '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<?xml-stylesheet type="text/xsl" href="/sitemap.xsl"?>',
            $xml
        );

        // Write to public directory
        file_put_contents(public_path('sitemap.xml'), $xmlWithXsl);
    }
}