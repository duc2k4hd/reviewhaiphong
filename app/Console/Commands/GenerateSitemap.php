<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Post;
use App\Models\Category;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\Tags\Url;
use Illuminate\Support\Facades\File;

class GenerateSitemap extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sitemap:generate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Tạo sitemap.xml tự động';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔄 Đang tạo sitemap động...');

        try {
            // Lấy tất cả bài viết đã xuất bản
            $posts = Post::with('category:id,slug')
                ->where('status', 'published')
                ->orderBy('published_at', 'desc')
                ->get(['id', 'slug', 'category_id', 'published_at']);

            $totalPosts = $posts->count();
            $postsPerSitemap = 200;
            $totalSitemaps = ceil($totalPosts / $postsPerSitemap);

            $this->info("📝 Tìm thấy {$totalPosts} bài viết đã xuất bản");
            $this->info("📁 Sẽ tạo {$totalSitemaps} file sitemap cho bài viết");

            // Lấy danh mục chính
            $categories = Category::select(['id', 'name', 'slug'])
                ->whereIn('slug', ['review-tong-hop', 'du-lich', 'am-thuc', 'check-in', 'dich-vu', 'tin-tuc', 'gioi-thieu'])
                ->get();

            $this->info("📂 Tìm thấy {$categories->count()} danh mục chính");

            // Tạo sitemap index nếu có nhiều hơn 200 bài viết
            if ($totalSitemaps > 1) {
                $this->info('📋 Tạo sitemap index...');
                $sitemapIndex = Sitemap::create();
                
                // Sitemap chính (trang chủ, danh mục)
                $sitemapIndex->add(
                    Url::create('/sitemap-main.xml')
                        ->setLastModificationDate(new \DateTime())
                );
                
                // Sitemap cho bài viết
                for ($i = 1; $i <= $totalSitemaps; $i++) {
                    $sitemapIndex->add(
                        Url::create("/sitemap-posts-{$i}.xml")
                            ->setLastModificationDate(new \DateTime())
                    );
                }
                
                $indexXml = $sitemapIndex->render();
                $indexXmlWithXsl = preg_replace(
                    '/<\?xml version="1.0" encoding="UTF-8"\?>/',
                    '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<?xml-stylesheet type="text/xsl" href="/sitemap.xsl"?>',
                    $indexXml
                );
                
                File::put(public_path('sitemap.xml'), $indexXmlWithXsl);
                $this->info('✅ Sitemap index đã được tạo');
            }

            // Tạo sitemap chính (trang chủ, danh mục)
            $this->info('🏠 Tạo sitemap chính...');
            $mainSitemap = Sitemap::create();
            $mainSitemap->add(Url::create('/'));
            $mainSitemap->add(
                Url::create('/review-hai-phong')
                    ->setLastModificationDate(new \DateTime())
                    ->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY)
                    ->setPriority(0.9)
            );
            $mainSitemap->add(
                Url::create('/gioi-thieu')
                    ->setLastModificationDate(new \DateTime())
                    ->setChangeFrequency(Url::CHANGE_FREQUENCY_MONTHLY)
                    ->setPriority(0.7)
            );

            // Thêm danh mục vào sitemap chính
            foreach ($categories as $category) {
                $mainSitemap->add(
                    Url::create("/{$category->slug}")
                        ->setLastModificationDate(new \DateTime())
                        ->setChangeFrequency(Url::CHANGE_FREQUENCY_MONTHLY)
                        ->setPriority(0.6)
                );
            }

            $mainXml = $mainSitemap->render();
            $mainXmlWithXsl = preg_replace(
                '/<\?xml version="1.0" encoding="UTF-8"\?>/',
                '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<?xml-stylesheet type="text/xsl" href="/sitemap.xsl"?>',
                $mainXml
            );
            
            File::put(public_path('sitemap-main.xml'), $mainXmlWithXsl);
            $this->info('✅ Sitemap chính đã được tạo');

            // Tạo sitemap cho bài viết (chia nhỏ)
            $this->info('📄 Tạo sitemap cho bài viết...');
            $postChunks = $posts->chunk($postsPerSitemap);
            foreach ($postChunks as $index => $postChunk) {
                $sitemapNumber = $index + 1;
                $this->info("📄 Tạo sitemap-posts-{$sitemapNumber}.xml...");
                
                $postSitemap = Sitemap::create();
                
                foreach ($postChunk as $post) {
                    $postSitemap->add(
                        Url::create("/{$post->slug}")
                            ->setLastModificationDate($post->published_at ? new \DateTime($post->published_at) : new \DateTime())
                            ->setChangeFrequency(Url::CHANGE_FREQUENCY_MONTHLY)
                            ->setPriority(0.6)
                    );
                }
                
                $postXml = $postSitemap->render();
                $postXmlWithXsl = preg_replace(
                    '/<\?xml version="1.0" encoding="UTF-8"\?>/',
                    '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<?xml-stylesheet type="text/xsl" href="/sitemap.xsl"?>',
                    $postXml
                );
                
                File::put(public_path("sitemap-posts-{$sitemapNumber}.xml"), $postXmlWithXsl);
            }

            $this->info('✅ Tất cả sitemap đã được tạo thành công!');
            
            if ($totalSitemaps > 1) {
                $this->info("📋 Sitemap index: " . url('sitemap.xml'));
                $this->info("🏠 Sitemap chính: " . url('sitemap-main.xml'));
                for ($i = 1; $i <= $totalSitemaps; $i++) {
                    $this->info("📄 Sitemap bài viết {$i}: " . url("sitemap-posts-{$i}.xml"));
                }
            } else {
                $this->info("📄 Sitemap: " . url('sitemap.xml'));
            }

        } catch (\Exception $e) {
            $this->error('❌ Lỗi khi tạo sitemap: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
