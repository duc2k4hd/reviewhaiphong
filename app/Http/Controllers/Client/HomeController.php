<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\Post;
use App\Models\Category;
use App\Models\Account;
use App\Models\Comment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class HomeController extends Controller
{
    public function index()
    {
        try {
            // Featured post (bài viết mới nhất) - Cache riêng
            $featuredPost = Cache::remember('homepage_featured_post', now()->addHours(6), function () {
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
                    ->orderBy('published_at', 'desc')
                    ->first();
            });

            // Side posts (2 bài viết tiếp theo) - Cache riêng
            $sidePosts = Cache::remember('homepage_side_posts_' . ($featuredPost->id ?? 0), now()->addHours(6), function () use ($featuredPost) {
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
                    ->where('id', '!=', $featuredPost->id ?? 0)
                    ->orderBy('published_at', 'desc')
                    ->take(2)
                    ->get();
            });

            // Lấy các category chính - Cache 1 ngày
            $mainCategories = Cache::remember('homepage_main_categories', now()->addDay(), function () {
                return Category::select(['id', 'name', 'slug'])
                    ->where('status', 'active')
                    ->whereNull('parent_id')
                    ->orderBy('sort_order', 'asc')
                    ->orderBy('name', 'asc')
                    ->take(7)
                    ->get();
            });

            // Posts theo từng category - Cache riêng từng category
            $postsByCategory = [];
            foreach ($mainCategories as $category) {
                $postsByCategory[$category->slug] = Cache::remember('homepage_posts_category_' . $category->id, now()->addHours(6), function () use ($category) {
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
                        ->orderBy('published_at', 'desc')
                        ->take(3)
                        ->get();
                });
            }

            // Editor's Picks - Cache 6 giờ
            $editorPicks = Cache::remember('homepage_editor_picks', now()->addHours(6), function () {
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
                    ->orderBy('views', 'desc')
                    ->take(5)
                    ->get();
            });

            // Hot Topics - Cache 1 giờ (thay đổi nhanh)
            $hotTopics = Cache::remember('homepage_hot_topics', now()->addHour(), function () {
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
                    ->where('published_at', '>=', now()->subDays(7))
                    ->orderBy('views', 'desc')
                    ->take(4)
                    ->get();
            });

            // Top Contributors - Cache 1 ngày
            $topContributors = Cache::remember('homepage_top_contributors', now()->addDay(), function () {
                return Account::select('accounts.id', 'accounts.username')
                    ->with(['profile:id,account_id,name,avatar'])
                    ->join('posts', 'accounts.id', '=', 'posts.account_id')
                    ->where('posts.status', 'published')
                    ->groupBy('accounts.id', 'accounts.username')
                    ->selectRaw('accounts.id, accounts.username, COUNT(posts.id) as posts_count')
                    ->orderBy('posts_count', 'desc')
                    ->take(4)
                    ->get();
            });

            // Stories - Cache 6 giờ
            $stories = Cache::remember('homepage_stories', now()->addHours(6), function () {
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
                    ->whereNotNull('seo_image')
                    ->where('seo_image', '!=', '')
                    ->orderBy('published_at', 'desc')
                    ->take(4)
                    ->get();
            });

            // Recent Comments - Cache 30 phút
            $recentComments = Cache::remember('homepage_recent_comments', now()->addMinutes(30), function () {
                return Comment::select(['id', 'post_id', 'account_id', 'content', 'created_at'])
                    ->with([
                        'post:id,slug,seo_title',
                        'account:id,username',
                        'account.profile:id,account_id,name,avatar'
                    ])
                    ->whereHas('post', function($query) {
                        $query->where('status', 'published');
                    })
            ->orderBy('created_at', 'desc')
                    ->take(5)
                    ->get();
            });

            // Popular Tags - Cache 1 ngày
            $popularTags = Cache::remember('homepage_popular_tags', now()->addDay(), function () {
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
                    ->take(15)
                    ->map(function ($count, $tag) {
                        return (object) ['name' => $tag, 'slug' => \Illuminate\Support\Str::slug($tag), 'count' => $count];
                    })
                    ->values();
            });

            // Settings - Cache 1 ngày
            $settings = Cache::remember('settings_all', now()->addDay(), function () {
                return Setting::pluck('value', 'name')->toArray();
            });

            return view('client.home.index', compact(
                'featuredPost',
                'sidePosts',
                'mainCategories',
                'postsByCategory',
                'editorPicks',
                'hotTopics',
                'topContributors',
                'stories',
                'recentComments',
                'popularTags',
                'settings'
            ));
            
        } catch (\Throwable $th) {
            Log::error('HomeController Error: ' . $th->getMessage());
            // Fallback values
            $featuredPost = null;
            $sidePosts = collect();
            $mainCategories = collect();
            $postsByCategory = [];
            $editorPicks = collect();
            $hotTopics = collect();
            $topContributors = collect();
            $stories = collect();
            $recentComments = collect();
            $popularTags = collect();
            
            // Settings fallback
            $settings = Cache::remember('settings_all', now()->addDay(), function () {
                return Setting::pluck('value', 'name')->toArray();
            });
            
            return view('client.home.index', compact(
                'featuredPost',
                'sidePosts',
                'mainCategories',
                'postsByCategory',
                'editorPicks',
                'hotTopics',
                'topContributors',
                'stories',
                'recentComments',
                'popularTags',
                'settings'
            ));
        }
    }

    public function introduce() {
        return view('client.home.introduce');
    }
}
