<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Post;
use App\Models\Setting;
use App\Models\Account;
use App\Models\Comment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class PostsCategory extends Controller
{
    public function index(Request $request, string $slug)
    {
        try {
            // Kiểm tra slug không hợp lệ (chứa dấu / hoặc các ký tự đặc biệt không phù hợp)
            if (strpos($slug, '/') !== false || preg_match('/\.(jpg|jpeg|png|gif|webp|css|js|ico|svg|woff|woff2|ttf|eot)$/i', $slug)) {
                abort(404);
            }

            // Cache category - 1 ngày
            $category = Cache::remember('category_' . $slug, now()->addDay(), function () use ($slug) {
                return Category::where('slug', $slug)
                ->where('status', 'active')
                ->firstOrFail();
            });

            // Pagination với cache key theo page
            $page = $request->get('page', 1);
            $cacheKey = 'category_posts_' . $category->id . '_page_' . $page;

            $posts = Cache::remember($cacheKey, now()->addHours(6), function () use ($category) {
                return $category
                ->posts()
                ->where('status', 'published')
                ->orderBy('published_at', 'desc')
                ->select('id', 'category_id', 'account_id', 'slug', 'seo_title', 'seo_desc', 'seo_image', 'seo_keywords', 'published_at', 'views')
                ->with(['account:id,username', 'account.profile:id,account_id,name'])
                ->paginate(20);
            });

            // Cache sidebar data
            $postsViews = Cache::remember('category_posts_views', now()->addHours(6), function () {
                return Post::where('status', 'published')
                ->orderBy('views', 'desc')
                ->select('id', 'category_id', 'account_id', 'views', 'slug', 'seo_title', 'seo_desc', 'seo_image', 'seo_keywords', 'published_at')
                ->take(15)->get();
            });

            // Editor's Picks - Cache theo category
            $editorPicks = Cache::remember('category_editor_picks_' . $category->id, now()->addHours(6), function () use ($category) {
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
                    ->where('category_id', $category->id)
                    ->orderBy('views', 'desc')
                    ->take(5)
                    ->get();
            });

            // Hot Topics - Cache 1 giờ
            $hotTopics = Cache::remember('category_hot_topics_' . $category->id, now()->addHour(), function () use ($category) {
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
                    ->where('category_id', $category->id)
                    ->where('published_at', '>=', now()->subDays(7))
                    ->orderBy('views', 'desc')
                    ->take(5)
                    ->get();
            });

            // Top Contributors - Cache 1 ngày
            $topContributors = Cache::remember('category_top_contributors_' . $category->id, now()->addDay(), function () use ($category) {
                return Account::select('accounts.id', 'accounts.username')
                    ->with(['profile:id,account_id,name,avatar'])
                    ->join('posts', 'accounts.id', '=', 'posts.account_id')
                    ->where('posts.status', 'published')
                    ->where('posts.category_id', $category->id)
                    ->groupBy('accounts.id', 'accounts.username')
                    ->selectRaw('accounts.id, accounts.username, COUNT(posts.id) as posts_count')
                    ->orderBy('posts_count', 'desc')
                    ->take(4)
                    ->get();
            });

            // Đếm số lượng - Cache 1 ngày
            $postsCount = Cache::remember('category_posts_count_' . $category->id, now()->addDay(), function () use ($category) {
                return Post::where('status', 'published')
                    ->where('category_id', $category->id)
                    ->count();
            });

            $commentsCount = Cache::remember('category_comments_count_' . $category->id, now()->addDay(), function () use ($category) {
                return Comment::whereHas('post', function($query) use ($category) {
                    $query->where('category_id', $category->id)
                          ->where('status', 'published');
                })->count();
            });

            // Settings - Cache 1 ngày
            $settings = Cache::remember('settings_all', now()->addDay(), function () {
                return Setting::pluck('value', 'name')->toArray();
            });

        } catch (\Throwable $th) {
            Log::error('PostsCategory Error slug=' . $slug . ' message=' . $th->getMessage());
            return view('client.templates.errors.404');
        }

        return view('client.layouts.posts-category', compact(
            'category', 
            'posts', 
            'postsViews', 
            'settings',
            'editorPicks',
            'hotTopics',
            'topContributors',
            'postsCount',
            'commentsCount'
        ));
    }
}
