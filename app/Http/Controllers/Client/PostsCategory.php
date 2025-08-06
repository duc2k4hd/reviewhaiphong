<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class PostsCategory extends Controller
{
    public function index(Request $request)
    {
        try {
            // Get category with caching
            $category = Cache::remember(
                'category_' . $request->slug, 
                now()->addHours(2), 
                function () use ($request) {
                    return Category::select(['id', 'name', 'slug', 'description'])
                        ->where('slug', $request->slug)
                        ->active()
                        ->firstOrFail();
                }
            );

            // Get paginated posts for this category
            $posts = $category->publishedPosts()
                ->select([
                    'id', 'category_id', 'account_id', 'slug', 'name',
                    'seo_title', 'seo_desc', 'seo_image', 'seo_keywords', 
                    'published_at', 'views'
                ])
                ->with([
                    'account:id,username', 
                    'account.profile:id,account_id,name,avatar'
                ])
                ->recent()
                ->paginate(20);

            // Get popular posts with caching
            $postsViews = Cache::remember(
                'popular_posts', 
                now()->addMinutes(30), 
                function () {
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
                }
            );

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return view('client.templates.errors.404');
        } catch (\Throwable $th) {
            \Log::error('Error in PostsCategory: ' . $th->getMessage());
            return view('client.templates.errors.404');
        }

        return view('client.layouts.posts-category', compact('category', 'posts', 'postsViews'));
    }
}
