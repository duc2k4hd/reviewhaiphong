<?php

namespace App\Http\Controllers\Api\V1\Client;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class GeneralApiController extends Controller
{
    public function category() {
        $categories = Cache::remember('categories', 60, function() {
            return Category::where('status', 1)
                ->get(['id', 'name', 'slug']);
            
        });

        return response()->json([
            'status' => 'success',
            'data' => $categories
        ]);
    }

    public function postsNew() {
        $posts = Cache::remember('postsNew', 60, function() {
            return Post::with(['category:id,name,slug', 'account:id', 'account.profile:id,name,account_id'])
                ->where('status', 'published')
                ->orderBy('created_at', 'desc')
                ->take(12)
                ->get(['id', 'category_id', 'account_id', 'name', 'seo_title', 'seo_desc', 'seo_keywords', 'seo_image', 'slug', 'content', 'published_at']);
        });
    
        return response()->json([
            'status' => 'success',
            'data' => $posts
        ]);
    }

    public function postsCategory() {
        $posts = Cache::remember('postsCategory', 60, function() {
            return Category::with([
                'posts' => function($query) {
                    $query->latest()->take(8)
                        ->where("status", "published")
                        ->select('id', 'category_id', 'account_id', 'published_at', 'seo_title', 'seo_desc', 'seo_image', 'slug');
                },
                'posts.account:id',
                'posts.account.profile:id,account_id,name'
            ])
            ->where('status', 1)
            ->whereIn('slug', ['dich-vu', 'am-thuc', 'du-lich', 'check-in', 'review-tong-hop'])
            ->get(['id', 'name', 'slug']);
        });

        return response()->json([
            'status' => 'success',
            'data' => $posts
        ]);
    }

    public function featuredPosts() {
        $posts = Cache::remember('featuredPosts', 60, function() {
            return Post::with([
                'category:id,name,slug',
                'account:id',
                'account.profile:id,name,account_id'
            ])
            ->where('status', 'published')
            ->orderBy('views', 'desc')
            ->orderBy('created_at', 'desc')
            ->take(8)
            ->get(['id', 'category_id', 'account_id', 'name', 'seo_title', 'views', 'seo_desc', 'seo_keywords', 'seo_image', 'slug', 'content', 'published_at']);
        });

    return response()->json([
        'status' => 'success',
        'data' => $posts
    ]);
    }
}
