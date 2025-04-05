<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\Setting;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function search(Request $request)
    {
        $keyword = $request->keyword;
        $settings = Setting::getSettings();
        try {
            $posts = Post::with(['category:id,name,slug', 'account:id', 'account.profile:id,name,account_id'])
                ->where('status', 'published')
                ->where('seo_title', 'like', '%' . $keyword . '%')
                ->orderBy('created_at', 'desc')
                ->get(['id', 'category_id', 'account_id', 'name', 'seo_title', 'seo_desc', 'seo_image', 'slug', 'published_at']);
            if ($posts->isEmpty()) {
                $posts = Post::with(['category:id,name,slug', 'account:id', 'account.profile:id,name,account_id'])
                    ->where('status', 'published')
                    ->where('seo_desc', 'like', '%' . $keyword . '%')
                    ->orderBy('created_at', 'desc')
                    ->get(['id', 'category_id', 'account_id', 'name', 'seo_title', 'seo_desc', 'seo_image', 'slug', 'published_at']);
            }

            $postsViews = Post::where('status', 'published')->orderBy('views', 'desc')->select('id', 'category_id', 'account_id', 'views', 'seo_title', 'seo_desc', 'seo_image', 'seo_keywords', 'published_at')->take(10)->get();
        } catch (\Throwable $th) {
            return view('client.templates.errors.404');
        }
        return view('client.layouts.search', compact('posts', 'settings', 'keyword', 'postsViews'));
    }
}
