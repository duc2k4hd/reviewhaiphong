<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Post;
use Illuminate\Http\Request;

class PostsListController extends Controller
{
    public function index(Request $request)
    {
        $posts = Post::whereHas('category', function($query) {
                $query->where('status', 'active');
            })
            ->where('status', 'published')
            ->orderBy('published_at', 'desc')
            ->select('id', 'category_id', 'account_id', 'slug', 'seo_title', 'seo_desc', 'seo_image', 'seo_keywords', 'published_at', 'views')
            ->with(['category:id,slug,name', 'account:id,username', 'account.profile:id,account_id,name'])
            ->paginate(20);

        $postsViews = Post::whereHas('category', function($query) {
                $query->where('status', 'active');
            })
            ->where('status', 'published')
            ->orderBy('views', 'desc')
            ->select('id', 'category_id', 'account_id', 'views', 'slug', 'seo_title', 'seo_desc', 'seo_image', 'seo_keywords', 'published_at')
            ->with(['category:id,slug'])
            ->take(15)
            ->get();

        return view('client.layouts.posts-list', compact('posts', 'postsViews'));
    }
}
