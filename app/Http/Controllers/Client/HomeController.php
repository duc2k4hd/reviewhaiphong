<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class HomeController extends Controller
{
    public function index()
    {
        // Cache homepage data for better performance
        $data = Cache::remember('homepage_data', now()->addMinutes(30), function () {
            return [
                'featured_posts' => Post::select([
                    'id', 'category_id', 'account_id', 'slug', 'name',
                    'seo_title', 'seo_desc', 'seo_image', 'published_at'
                ])
                ->with([
                    'category:id,name,slug',
                    'account:id,username',
                    'account.profile:id,account_id,name,avatar'
                ])
                ->published()
                ->recent()
                ->limit(6)
                ->get(),
                
                'popular_posts' => Post::select([
                    'id', 'category_id', 'account_id', 'views', 'slug',
                    'seo_title', 'seo_desc', 'seo_image', 'published_at'
                ])
                ->with([
                    'category:id,name,slug',
                    'account:id,username',
                    'account.profile:id,account_id,name,avatar'
                ])
                ->published()
                ->popular(8)
                ->get(),
                
                'categories' => Category::select(['id', 'name', 'slug'])
                    ->active()
                    ->withCount('publishedPosts')
                    ->limit(8)
                    ->get()
            ];
        });

        return view('client.home.index', $data);
    }

    public function introduce() {
        return view('client.home.introduce');
    }
}
