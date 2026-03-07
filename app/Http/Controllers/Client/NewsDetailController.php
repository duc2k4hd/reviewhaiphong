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
        // Redirect 301 về URL chuẩn SEO: /{slug}
        return redirect()->route('post.detail', ['slug' => $slug], 301);
    }

    // Hỗ trợ URL: /{slug}
    public function newsDetailBySlug(Request $request, string $slug)
    {
        return $this->renderPostDetailBySlug($slug, $request);
    }

    private function renderPostDetailBySlug(string $slug, Request $request)
    {
        try {
            // Cache bài viết chính - Tối ưu: Dùng array để nhanh hơn
            $postData = Cache::remember('post_detail_array_' . $slug, now()->addDay(), function () use ($slug) {
                $post = Post::select([
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

                return $post ? $post->toArray() : null;
            });
            
            if (!$postData) {
                return view('client.templates.errors.404');
            }
            
            // Convert to object for compatibility with Blade
            $post = json_decode(json_encode($postData));

            // Tăng lượt xem - afterResponse
            $ip = $request->ip();
            $key = 'viewed_post_' . $post->id . '_' . $ip;
            if (!cache()->has($key)) {
                app()->terminating(function () use ($post, $key) {
                    Post::where('id', $post->id)->increment('views');
                    cache()->put($key, true, now()->addHours(6));
                });
            }
            // Cache bài viết liên quan - Array
            $relatedPostsData = Cache::remember('post_related_array_' . $post->id, now()->addHours(6), function () use ($post) {
                $baseQuery = Post::select(['id', 'category_id', 'account_id', 'slug', 'seo_title', 'seo_desc', 'seo_image', 'published_at', 'views'])
                    ->with(['category:id,name,slug', 'account:id,username', 'account.profile:id,account_id,name'])
                    ->where('status', 'published')
                    ->where('id', '!=', $post->id);
                
                $previousPosts = (clone $baseQuery)->where('published_at', '<', $post->published_at)->orderBy('published_at', 'desc')->limit(3)->get();
                $nextPosts = (clone $baseQuery)->where('published_at', '>', $post->published_at)->orderBy('published_at', 'asc')->limit(3)->get();
                
                $merged = $previousPosts->reverse()->values()->merge($nextPosts->values());
                
                if ($merged->count() < 6) {
                    $remaining = 6 - $merged->count();
                    $extraPosts = (clone $baseQuery)->where('category_id', $post->category_id)
                        ->whereNotIn('id', $merged->pluck('id')->push($post->id)->toArray())
                        ->orderBy('published_at', 'desc')->limit($remaining)->get();
                    $merged = $merged->merge($extraPosts);
                }
                return $merged->toArray();
            });
            $relatedPosts = json_decode(json_encode($relatedPostsData));

            // GỘP CACHE: Popular + Categories + Tags
            $sidebarBundle = Cache::remember('article_sidebar_bundle_v1', now()->addHours(6), function () {
                // 1. Popular (lấy 10 bài để filter bài hiện tại cho chắc)
                $popular = Post::select(['id', 'category_id', 'account_id', 'slug', 'seo_title', 'seo_desc', 'seo_image', 'published_at', 'views'])
                    ->with(['category:id,name,slug', 'account:id,username', 'account.profile:id,account_id,name'])
                    ->where('status', 'published')
                    ->orderBy('views', 'desc')->take(10)->get()->toArray();

                // 2. Categories
                $categories = Category::select(['id', 'name', 'slug'])
                    ->where('status', 'active')
                    ->withCount(['posts' => function ($query) { $query->where('status', 'published'); }])
                    ->orderBy('name')->get()->toArray();

                // 3. Tags
                $tags = Post::select('tags')->where('status', 'published')->whereNotNull('tags')->where('tags', '!=', '')
                    ->orderBy('id', 'desc')->limit(1000)->get()
                    ->flatMap(function ($p) { return array_filter(array_map('trim', explode(',', $p->tags))); })
                    ->countBy()->sortDesc()->take(10)
                    ->map(function ($count, $tag) {
                         return ['name' => $tag, 'slug' => \Illuminate\Support\Str::slug($tag), 'count' => $count];
                    })->values()->toArray();

                return compact('popular', 'categories', 'tags');
            });

            // Lọc ra bài hiện tại từ bundle (xử lý trên RAM)
            $popularPosts = collect($sidebarBundle['popular'])
                ->where('id', '!=', $post->id)
                ->take(5)
                ->map(fn($item) => (object) $item);
            
            $categories = json_decode(json_encode($sidebarBundle['categories']));
            $popularTags = json_decode(json_encode($sidebarBundle['tags']));

        } catch (\Throwable $th) {
            Log::error('NewsDetail Error: ' . $th->getMessage());
            return view('client.templates.errors.404');
        }

        return view('client.layouts.single-page', compact('post', 'relatedPosts', 'popularPosts', 'categories', 'popularTags'));
    }
}
