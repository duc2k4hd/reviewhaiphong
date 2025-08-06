<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\LoginController;
use App\Http\Controllers\Admin\MediaController;
use App\Http\Controllers\Admin\PostController;
use App\Http\Controllers\Client\HomeController;
use App\Http\Controllers\Client\NewsDetailController;
use App\Http\Controllers\Client\PostsCategory;
use App\Http\Controllers\Client\ProfileController;
use App\Http\Controllers\Client\SearchController;
use App\Http\Controllers\Client\SitemapController;
use App\Http\Middleware\CheckAdmin;
use App\Http\Middleware\CheckLogin;
use Illuminate\Support\Facades\Route;

// Admin Routes Group
Route::prefix('admin')->name('admin.')->group(function() {
    // Auth routes
    Route::get('/', [LoginController::class, 'index']);
    Route::get('/login', [LoginController::class, 'index'])->name('login.index');
    Route::post('/login/handle', [LoginController::class, 'post'])->name('login.post');
    
    // Protected admin routes
    Route::middleware([CheckLogin::class, CheckAdmin::class])->group(function() {
        // Dashboard
        Route::prefix('dashboard')->name('dashboard.')->group(function() {
            Route::get('/', [DashboardController::class, 'index'])->name('index');
        });

        // Posts management
        Route::prefix('posts')->name('posts.')->group(function() {
            Route::get('/', [PostController::class, 'index'])->name('index');
            Route::get('/published', [PostController::class, 'posts_published'])->name('published');
            Route::get('/draft', [PostController::class, 'posts_draft'])->name('draft');
            Route::get('/trash', [PostController::class, 'posts_trash'])->name('trash');
            Route::get('/pending', [PostController::class, 'posts_pending'])->name('pending');
            Route::get('/new', [PostController::class, 'posts_new'])->name('new');
            Route::post('/new/handle', [PostController::class, 'handle_posts_new'])->name('new.handle');
        });
    });

    // Media upload (might need auth check)
    Route::post('/media/upload', [MediaController::class, 'upload'])->name('media.upload');
});

// Client Routes
Route::group(['middleware' => ['web']], function() {
    // Home routes
    Route::get('/', [HomeController::class, 'index'])->name('home.index');
    Route::get('/trang-chu', [HomeController::class, 'index']);
    Route::get('/review-hai-phong', [HomeController::class, 'index']);
    Route::get('/gioi-thieu', [HomeController::class, 'introduce'])->name('home.introduce');
    
    // User profile
    Route::get('/user/{username}', [ProfileController::class, 'index'])->name('profile.index');
    
    // Search
    Route::get('/tim-kiem/{keyword}', [SearchController::class, 'search'])->name('search.index');
    
    // Sitemap routes - moved to controller for better performance
    Route::get('/sitemap', [SitemapController::class, 'generate'])->name('sitemap.generate');
    Route::get('/sitemap.xml', [SitemapController::class, 'xml'])->name('sitemap.xml');
    
    // Category routes
    Route::get('/{slug}', [PostsCategory::class, 'index'])
        ->name('posts-category.index')
        ->where('slug', '[a-zA-Z0-9-_]+');

    // Post detail routes (must be last)
    Route::get('/{category}/{slug}', [NewsDetailController::class, 'newsDetail'])
        ->name('news.detail')
        ->where([
            'category' => '[a-zA-Z0-9-_]+',
            'slug' => '[a-zA-Z0-9-_]+'
        ]);
});
