<?php

use App\Http\Controllers\Client\HomeController;
use App\Http\Controllers\Client\NewsDetailController;
use App\Http\Controllers\Client\PostsCategory;
use App\Http\Controllers\Client\ProfileController;
use App\Http\Controllers\Client\SearchController;
use Illuminate\Support\Facades\Route;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\Tags\Url;
use App\Models\Post;

Route::get('/', [HomeController::class, 'index']);
Route::get('/trang-chu', [HomeController::class, 'index']);
Route::get('/review-hai-phong', [HomeController::class, 'index'])->name('home.index');
Route::get('/user/{username}', [ProfileController::class, 'index'])->name('profile.index');
Route::get('/tim-kiem/{keyword}', [SearchController::class, 'search'])->name('search.index');

Route::get('/sitemap', function () {
    $posts = Post::with('category:id,slug')->where('status', 'published')->get(['id', 'slug', 'category_id','updated_at']);

    $sitemap = Sitemap::create();
    $sitemap->add(Url::create('/'));
    foreach ($posts as $post) {
        $sitemap->add(
            Url::create("/{$post->category->slug}/{$post->slug}")
                ->setLastModificationDate($post->updated_at)
                ->setChangeFrequency(Url::CHANGE_FREQUENCY_MONTHLY)
                ->setPriority(0.6)
        );
    }

    $sitemap->writeToFile(public_path('sitemap.xml'));

    return 'Thêm sitemap thành công!';
});

Route::get('/{slug}', [PostsCategory::class, 'index'])->name('posts-category.index');

// Phải để dòng này cuối cùng
Route::get('/{category}/{slug}', [NewsDetailController::class, 'newsDetail'])
    ->name('news.detail')
    ->where([
        'category' => '[a-zA-Z0-9-_]+',
        'slug' => '[a-zA-Z0-9-_]+'   
    ]);