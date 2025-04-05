<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Post;
use App\Models\Setting;
use Illuminate\Http\Request;

class PostsCategory extends Controller
{
    public function index(Request $request)
    {
        try {
            $settings = Setting::getSettings();
            $category = Category::where('slug', $request->slug)->firstOrFail();
            $posts = $category
                ->posts()
                ->where('status', 'published')
                ->orderBy('published_at', 'desc')
                ->select('id', 'category_id', 'account_id', 'slug', 'seo_title', 'seo_desc', 'seo_image', 'seo_keywords', 'published_at')
                ->with(['account:id,username', 'account.profile:id,account_id,name'])
                ->paginate(20);

            $postsViews = Post::where('status', 'published')->orderBy('views', 'desc')->select('id', 'category_id', 'account_id', 'views', 'slug', 'seo_title', 'seo_desc', 'seo_image', 'seo_keywords', 'published_at')->take(15)->get();
        } catch (\Throwable $th) {
            return view('client.templates.errors.404');
        }

        return view('client.layouts.posts-category', compact('category', 'posts', 'settings', 'postsViews'));
    }
}
