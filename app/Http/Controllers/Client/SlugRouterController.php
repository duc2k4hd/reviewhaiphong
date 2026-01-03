<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SlugRouterController extends Controller
{
    /**
     * Route handler chung: kiểm tra slug là post hay category
     */
    public function handle(Request $request, string $slug)
    {
        // Kiểm tra slug không hợp lệ
        if (strpos($slug, '/') !== false || preg_match('/\.(jpg|jpeg|png|gif|webp|css|js|ico|svg|woff|woff2|ttf|eot)$/i', $slug)) {
            abort(404);
        }
        
        // Loại trừ các route đặc biệt
        $excluded = ['admin', 'bai-viet', 'danh-muc', 'trang-chu', 'gioi-thieu', 'lien-he', 'tim-kiem', 'user', 'storage', 'client', 'assets', 'vendor', 'public'];
        if (in_array($slug, $excluded)) {
            abort(404);
        }
        
        // Kiểm tra xem slug có phải là post không (cache 1 giờ)
        $isPost = Cache::remember('slug_is_post_' . $slug, now()->addHour(), function () use ($slug) {
            return Post::where('slug', $slug)
                ->where('status', 'published')
                ->exists();
        });
        
        if ($isPost) {
            // Nếu là post, gọi NewsDetailController
            $newsController = new NewsDetailController();
            return $newsController->newsDetailBySlug($request, $slug);
        }
        
        // Nếu không phải post, kiểm tra category
        $isCategory = Cache::remember('slug_is_category_' . $slug, now()->addHour(), function () use ($slug) {
            return Category::where('slug', $slug)
                ->where('status', 'active')
                ->exists();
        });
        
        if ($isCategory) {
            // Nếu là category, gọi PostsCategory
            $categoryController = new PostsCategory();
            return $categoryController->index($request, $slug);
        }
        
        // Nếu không phải cả post và category, 404
        abort(404);
    }
}


