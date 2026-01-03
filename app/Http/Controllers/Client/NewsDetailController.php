<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class NewsDetailController extends Controller
{
    public function newsDetail(Request $request, string $category, string $slug)
    {
        // Redirect 301 về URL chuẩn SEO: /bai-viet/{slug}
        return redirect()->route('post.detail', ['slug' => $slug], 301);
    }

    // Hỗ trợ URL: /bai-viet/{slug}
    public function newsDetailBySlug(Request $request, string $slug)
    {
        return $this->renderPostDetailBySlug($slug, $request);
    }

    private function renderPostDetailBySlug(string $slug, Request $request)
    {
        try {
            // Cache bài viết chính - 1 ngày
            $post = Cache::remember('post_detail_' . $slug, now()->addDay(), function () use ($slug) {
                return Post::select([
                    'id', 'category_id', 'account_id', 'name', 'slug', 'seo_title', 
                    'seo_desc', 'seo_image', 'seo_keywords', 'tags', 'published_at', 
                    'content', 'views', 'status', 'updated_at'
                ])
                    ->with([
                        'category:id,name,slug,status',
                        'account:id,username',
                        'account.profile:id,account_id,name,avatar',
                        'comments' => function ($query) {
                            $query->where('status', 'approved')->orderBy('created_at', 'desc');
                        },
                    ])
                    ->whereHas('category', function($query) {
                        $query->where('status', 'active');
                    })
                    ->where('slug', $slug)
                    ->where('status', 'published')
                    ->first();
            });

            if (!$post) {
                return view('client.templates.errors.404');
            }

            // Tăng lượt xem
            $ip = $request->ip();
            $key = 'viewed_post_' . $post->id . '_' . $ip;
            
            if (!cache()->has($key)) {
                $post->increment('views');
                cache()->put($key, true, now()->addHours(6));
            }

            // Cache bài viết liên quan - 6 giờ
            $categoryPosts = Cache::remember('post_related_' . $post->id, now()->addHours(6), function () use ($post) {
                $baseQuery = Post::select([
                    'id', 'category_id', 'account_id', 'slug', 'seo_title', 
                    'seo_desc', 'seo_image', 'published_at', 'views'
                ])
                    ->with([
                        'category:id,name,slug',
                        'account:id,username',
                        'account.profile:id,account_id,name',
                    ])
                    ->where('status', 'published')
                    ->where('id', '!=', $post->id);
                
                // Lấy 3 bài trước (published_at < bài hiện tại)
                $previousPosts = (clone $baseQuery)
                    ->where('published_at', '<', $post->published_at)
                    ->orderBy('published_at', 'desc')
                    ->limit(3)
                    ->get();
                
                // Lấy 3 bài sau (published_at > bài hiện tại)
                $nextPosts = (clone $baseQuery)
                    ->where('published_at', '>', $post->published_at)
                    ->orderBy('published_at', 'asc')
                    ->limit(3)
                    ->get();
                
                // Nếu không đủ 6 bài, lấy random từ cùng danh mục để bù
                $totalNeeded = 6;
                $currentCount = $previousPosts->count() + $nextPosts->count();
                
                if ($currentCount < $totalNeeded) {
                    $remaining = $totalNeeded - $currentCount;
                    $excludedIds = $previousPosts->pluck('id')
                        ->merge($nextPosts->pluck('id'))
                        ->push($post->id)
                        ->toArray();
                    
                    $randomPosts = (clone $baseQuery)
                        ->where('category_id', $post->category_id)
                        ->whereNotIn('id', $excludedIds)
                        ->inRandomOrder()
                        ->limit($remaining)
                        ->get();
                    
                    return $previousPosts->reverse()->values()
                        ->merge($nextPosts->values())
                        ->merge($randomPosts->values());
                } else {
                    return $previousPosts->reverse()->values()
                        ->merge($nextPosts->values());
                }
            });

            // Bài viết phổ biến - Cache 6 giờ
            $popularPosts = Cache::remember('post_popular_posts', now()->addHours(6), function () use ($post) {
                return Post::select([
                    'id', 'category_id', 'account_id', 'slug', 'seo_title', 
                    'seo_desc', 'seo_image', 'published_at', 'views'
                ])
                    ->with([
                        'category:id,name,slug',
                        'account:id,username',
                        'account.profile:id,account_id,name',
                    ])
                    ->where('status', 'published')
                    ->where('id', '!=', $post->id)
                    ->orderBy('views', 'desc')
                    ->take(5)
                    ->get();
            });

            // Danh mục - Cache 1 ngày
            $categories = Cache::remember('categories_all_active', now()->addDay(), function () {
                return Category::select(['id', 'name', 'slug'])
                    ->where('status', 'active')
                    ->withCount(['posts' => function ($query) {
                        $query->where('status', 'published');
                    }])
                    ->orderBy('name')
                    ->get();
            });

            // Tags phổ biến - Cache 1 ngày
            $popularTags = Cache::remember('popular_tags_all', now()->addDay(), function () {
                return Post::select('tags')
                    ->where('status', 'published')
                    ->whereNotNull('tags')
                    ->where('tags', '!=', '')
                    ->get()
                    ->flatMap(function ($p) {
                        if (is_string($p->tags)) {
                            return array_filter(array_map('trim', explode(',', $p->tags)));
                        }
                        return [];
                    })
                    ->countBy()
                    ->sortDesc()
                    ->take(10)
                    ->map(function ($count, $tag) {
                        return (object) ['name' => $tag, 'slug' => \Illuminate\Support\Str::slug($tag), 'count' => $count];
                    })
                    ->values();
            });

        } catch (\Throwable $th) {
            Log::error('NewsDetail Error: ' . $th->getMessage());
            return view('client.templates.errors.404');
        }

        return view('client.layouts.single-page', compact(
            'post', 
            'categoryPosts', 
            'popularPosts', 
            'categories', 
            'popularTags'
        ));
    }
}
