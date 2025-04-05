<?php

namespace App\Http\Controllers\Api\V1\Client;

use App\Http\Controllers\Controller;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class NewsDetailApiController extends Controller
{
    public function newsDetail(Request $request) {
        $posts = Cache::remember('', 60, function() use($request) {
            return Post::select(['id', 'category_id', 'account_id', 'seo_title', 'seo_desc', 'seo_image', 'seo_keywords', 'published_at'])
            ->with("category:id,name,slug", "account:id", "account.profile:id,account_id,name")
            ->where("slug", $request->slug)
            ->where("status", "published")->first();
        });
        
        return response()->json([
            'status' => 'success',
            'data' => $posts
        ]);
    }
}
