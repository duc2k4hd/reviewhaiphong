<?php

namespace App\Observers;

use App\Models\Post;
use App\Models\Category;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\Tags\Url;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class PostObserver
{
    /**
     * Handle the Post "created" event.
     */
    public function created(Post $post): void
    {
        $this->updateSitemap();
    }

    /**
     * Handle the Post "updated" event.
     */
    public function updated(Post $post): void
    {
        $this->updateSitemap();
    }

    /**
     * Handle the Post "deleted" event.
     */
    public function deleted(Post $post): void
    {
        $this->updateSitemap();
    }

    /**
     * Handle the Post "restored" event.
     */
    public function restored(Post $post): void
    {
        $this->updateSitemap();
    }

    /**
     * Handle the Post "force deleted" event.
     */
    public function forceDeleted(Post $post): void
    {
        $this->updateSitemap();
    }

    /**
     * Cập nhật sitemap.xml
     */
    private function updateSitemap(): void
    {
        try {
            // Lấy tất cả bài viết đã xuất bản
            $posts = Post::with('category:id,slug')
                ->where('status', 'published')
                ->get(['id', 'slug', 'category_id', 'updated_at']);

            // Lấy danh mục chính
            $categories = Category::select(['id', 'name', 'slug'])
                ->whereIn('slug', ['review-hai-phong', 'du-lich', 'am-thuc', 'check-in', 'dich-vu', 'tin-tuc', 'gioi-thieu'])
                ->get();

            // Tạo sitemap mới
            $sitemap = Sitemap::create()->add(Url::create('/'));
            
            // Thêm trang chính
            $sitemap->add(
                Url::create('/review-hai-phong')
                    ->setLastModificationDate(new \DateTime())
                    ->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY)
                    ->setPriority(0.9)
            );
            
            $sitemap->add(
                Url::create('/gioi-thieu')
                    ->setLastModificationDate(new \DateTime())
                    ->setChangeFrequency(Url::CHANGE_FREQUENCY_MONTHLY)
                    ->setPriority(0.7)
            );

            // Thêm danh mục vào sitemap
            foreach ($categories as $category) {
                $sitemap->add(
                    Url::create("/{$category->slug}")
                        ->setLastModificationDate(new \DateTime())
                        ->setChangeFrequency(Url::CHANGE_FREQUENCY_MONTHLY)
                        ->setPriority(0.6)
                );
            }

            // Thêm bài viết với URL chuẩn /{slug}
            foreach ($posts as $post) {
                $sitemap->add(
                    Url::create("/{$post->slug}")
                        ->setLastModificationDate($post->updated_at ? new \DateTime($post->updated_at) : new \DateTime())
                        ->setChangeFrequency(Url::CHANGE_FREQUENCY_MONTHLY)
                        ->setPriority(0.6)
                );
            }

            // Render XML
            $xml = $sitemap->render();
            
            // Thêm XSL stylesheet
            $xmlWithXsl = preg_replace(
                '/<\?xml version="1.0" encoding="UTF-8"\?>/',
                '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<?xml-stylesheet type="text/xsl" href="/sitemap.xsl"?>',
                $xml
            );

            // Lưu vào file sitemap.xml
            File::put(public_path('sitemap.xml'), $xmlWithXsl);

        } catch (\Exception $e) {
            // Log lỗi nếu có
            Log::error('Lỗi khi cập nhật sitemap: ' . $e->getMessage());
        }
    }
}
