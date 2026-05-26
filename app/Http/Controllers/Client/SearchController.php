<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\Setting;
use App\Models\Category;
use App\Models\Account;
use App\Models\Comment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SearchController extends Controller
{
    public function search(Request $request)
    {
        $keyword = $request->query('keyword', '');
        
        try {
            // Tạo category giả cho trang search (để tương thích với view posts-category)
            $category = (object) [
                'id' => 0,
                'name' => 'Tìm kiếm: "' . $keyword . '"',
                'slug' => 'tim-kiem',
                'description' => 'Kết quả tìm kiếm cho từ khóa "' . $keyword . '"',
                'meta_description' => 'Kết quả tìm kiếm cho từ khóa "' . $keyword . '"',
                'meta_keywords' => $keyword,
                'meta_image' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            // Tìm kiếm bài viết theo keyword với pagination
            $posts = Post::with(['category:id,name,slug', 'account:id,username', 'account.profile:id,account_id,name'])
                ->whereHas('category', function($query) {
                    $query->where('status', 'active');
                })
                ->where('status', 'published')
                ->where(function($query) use ($keyword) {
                    $query->where('seo_title', 'like', '%' . $keyword . '%')
                          ->orWhere('seo_desc', 'like', '%' . $keyword . '%')
                          ->orWhere('name', 'like', '%' . $keyword . '%');
                })
                ->orderBy('published_at', 'desc')
                ->select('id', 'category_id', 'account_id', 'slug', 'seo_title', 'seo_desc', 'seo_image', 'seo_keywords', 'published_at', 'views')
                ->paginate(20)
                ->appends(['keyword' => $keyword]);

            // Cache sidebar data
            $postsViews = Cache::remember('search_posts_views', now()->addHours(6), function () {
                return Post::where('status', 'published')
                ->orderBy('views', 'desc')
                ->select('id', 'category_id', 'account_id', 'views', 'slug', 'seo_title', 'seo_desc', 'seo_image', 'seo_keywords', 'published_at')
                ->take(15)->get();
            });

            // Editor's Picks - Lấy từ tất cả category
            $editorPicks = Cache::remember('search_editor_picks', now()->addHours(6), function () {
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

            // Hot Topics - Bài hot trong tuần
            $hotTopics = Cache::remember('search_hot_topics', now()->addHour(), function () {
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
                    ->take(5)
                    ->get();
            });

            // Top Contributors
            $topContributors = Cache::remember('search_top_contributors', now()->addDay(), function () {
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

            // Đếm số lượng
            $postsCount = $posts->total();
            $commentsCount = Comment::whereHas('post', function($query) {
                $query->where('status', 'published');
            })->count();

            // Settings
            $settings = Cache::remember('settings_all', now()->addDay(), function () {
                return Setting::pluck('value', 'name')->toArray();
            });

            // Đánh dấu đây là trang search để thêm noindex, nofollow
            $isSearchPage = true;

        } catch (\Throwable $th) {
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
            'commentsCount',
            'isSearchPage',
            'keyword'
        ));
    }
}
