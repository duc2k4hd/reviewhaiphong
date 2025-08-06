<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class NewsDetailController extends Controller
{
    public function newsDetail(Request $request)
    {
        try {
            // Get post with optimized query
            $post = Post::select([
                'id', 'category_id', 'account_id', 'name', 'slug', 'views', 
                'content', 'seo_title', 'seo_desc', 'seo_image', 'seo_keywords', 
                'tags', 'published_at'
            ])
            ->with([
                'category:id,name,slug',
                'account:id,username',
                'account.profile:id,account_id,name,avatar',
                'comments' => function ($query) {
                    $query->select(['id', 'post_id', 'account_id', 'content', 'created_at'])
                          ->where('status', 'approved')
                          ->orderBy('created_at', 'desc')
                          ->limit(20); // Limit comments
                },
                'comments.account:id,username',
                'comments.account.profile:id,account_id,name,avatar'
            ])
            ->where('slug', $request->slug)
            ->published()
            ->firstOrFail();

            // Handle view increment with caching (6 hours cooldown per IP)
            $this->handleViewIncrement($post);

            // Get related posts with caching
            $categoryPosts = $this->getCategoryPosts($post);
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return view('client.templates.errors.404');
        } catch (\Throwable $th) {
            \Log::error('Error in NewsDetailController: ' . $th->getMessage());
            return view('client.templates.errors.404');
        }

        return view('client.layouts.single-page', compact('post', 'categoryPosts'));
    }

    private function handleViewIncrement(Post $post): void
    {
        $ip = request()->ip();
        $key = 'viewed_post_' . $post->id . '_' . $ip;
        
        if (!Cache::has($key)) {
            // Use database transaction for consistency
            DB::transaction(function () use ($post) {
                $post->incrementViews();
            });
            Cache::put($key, true, now()->addHours(6));
        }
    }

    private function getCategoryPosts(Post $post): ?Category
    {
        $cacheKey = 'category_posts_' . $post->category_id . '_excluding_' . $post->id;
        
        return Cache::remember($cacheKey, now()->addMinutes(30), function () use ($post) {
            return Category::select(['id', 'name', 'slug'])
                ->with([
                    'publishedPosts' => function ($query) use ($post) {
                        $query->select([
                            'id', 'category_id', 'account_id', 'name', 'slug', 
                            'seo_title', 'seo_desc', 'seo_image', 'published_at'
                        ])
                        ->where('id', '!=', $post->id)
                        ->recent()
                        ->limit(8);
                    },
                    'publishedPosts.account:id,username',
                    'publishedPosts.account.profile:id,account_id,name',
                ])
                ->find($post->category_id);
        });
    }
}
