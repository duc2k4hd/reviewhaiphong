<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Post;
use App\Models\Setting;
use Illuminate\Http\Request;

class NewsDetailController extends Controller
{
    public function newsDetail(Request $request)
    {
        $settings = Setting::getSettings();
        try {
            $post = Post::select(['id', 'category_id', 'account_id', 'name', 'views', 'content', 'seo_title', 'seo_desc', 'seo_image', 'seo_keywords', 'tags', 'published_at'])
                ->with([
                    'category:id,name,slug',
                    'account:id,username',
                    'account.profile:id,account_id,name,avatar',
                    'comments' => function ($query) {
                        $query->where('status', 'approved')->orderBy('created_at', 'desc');
                    },
                ])
                ->where('slug', $request->slug)
                ->where('status', 'published')
                ->first();

            $post->update([
                'views' => $post->views + 1,
            ]);

            $categoryPosts = Category::select(['id', 'name', 'slug'])
                ->with([
                    'posts' => function ($query) use ($post) {
                        $query->where('id', '!=', $post->id)->where('status', 'published')->take(8)->orderBy('created_at', 'desc');
                    },
                    'posts.account:id,username',
                    'posts.account.profile:id,account_id,name',
                ])
                ->where('id', $post->category->id)
                ->first();
        } catch (\Throwable $th) {
            return view('client.templates.errors.404');
        }

        return view('client.layouts.single-page', compact('settings', 'post', 'categoryPosts'));
    }
}
