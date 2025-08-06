<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Post;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class WarmupCache extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'cache:warmup {--force : Force cache refresh}';

    /**
     * The console command description.
     */
    protected $description = 'Warmup application caches for better performance';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🔥 Starting cache warmup...');

        $force = $this->option('force');

        // Warmup popular posts cache
        $this->warmupPopularPosts($force);

        // Warmup categories cache
        $this->warmupCategories($force);

        // Warmup sitemap data
        $this->warmupSitemapData($force);

        $this->info('✅ Cache warmup completed successfully!');
        
        return Command::SUCCESS;
    }

    private function warmupPopularPosts(bool $force = false): void
    {
        $this->info('📊 Warming up popular posts cache...');

        $cacheKey = 'popular_posts';
        
        if ($force) {
            Cache::forget($cacheKey);
        }

        Cache::remember($cacheKey, now()->addMinutes(config('performance.cache.posts.popular_duration', 30)), function () {
            return Post::select([
                'id', 'category_id', 'account_id', 'views', 'slug',
                'seo_title', 'seo_desc', 'seo_image', 'published_at'
            ])
            ->with([
                'category:id,name,slug',
                'account:id,username',
                'account.profile:id,account_id,name,avatar'
            ])
            ->published()
            ->popular(15)
            ->get();
        });

        $this->info('   ✓ Popular posts cached');
    }

    private function warmupCategories(bool $force = false): void
    {
        $this->info('📁 Warming up categories cache...');

        $categories = Category::active()->get();

        foreach ($categories as $category) {
            $cacheKey = 'category_' . $category->slug;
            
            if ($force) {
                Cache::forget($cacheKey);
            }

            Cache::remember($cacheKey, now()->addHours(2), function () use ($category) {
                return $category;
            });
        }

        $this->info("   ✓ {$categories->count()} categories cached");
    }

    private function warmupSitemapData(bool $force = false): void
    {
        $this->info('🗺️ Warming up sitemap data...');

        $cacheKey = 'sitemap_data';
        
        if ($force) {
            Cache::forget($cacheKey);
        }

        Cache::remember($cacheKey, now()->addHour(), function () {
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

        $this->info('   ✓ Sitemap data cached');
    }
}