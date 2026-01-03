<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\LoginController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\CacheController;
// use App\Http\Controllers\Admin\MediaController; // Tạm ẩn vì controller này chưa tồn tại
use App\Http\Controllers\Admin\PostController;
use App\Http\Controllers\Admin\CommentController as AdminCommentController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Client\HomeController;
use App\Http\Controllers\Client\NewsDetailController;
use App\Http\Controllers\Client\PostsCategory;
use App\Http\Controllers\Client\PostsListController;
use App\Http\Controllers\Client\ProfileController;
use App\Http\Controllers\Client\SearchController;
use App\Http\Controllers\Client\ContactController;
use App\Http\Controllers\Client\CommentController;
use App\Http\Middleware\CheckAdmin;
use App\Http\Middleware\CheckLogin;
use App\Http\Middleware\MaintenanceMode;
use App\Models\Category;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Http;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\Tags\Url;
use App\Models\Post;
use Illuminate\Support\Facades\DB;

// Admin
Route::prefix('/admin')->name('admin.')->group(function() {
    Route::get('/', [LoginController::class,'index']);
    Route::get('/login', [LoginController::class, 'index'])->name('login.index');
    Route::post('/login/handle', [LoginController::class, 'post'])->name('login.post');
    
    Route::middleware([CheckLogin::class, CheckAdmin::class])->prefix('/dashboard')->name('dashboard.')->group((function() {
        Route::get('/', [DashboardController::class,'index'])->name('index');
    }));

    // Staff Routes - Gộp tất cả routes cho Staff
    Route::middleware([CheckLogin::class, 'staff'])->prefix('/staff')->name('staff.')->group((function() {
        // Dashboard - Route chính
        Route::get('/', [DashboardController::class,'staffIndex'])->name('dashboard.index');
        Route::get('/dashboard', [DashboardController::class,'staffIndex'])->name('dashboard.detail');
        
        // Posts
        Route::get('/posts', [PostController::class, 'staffIndex'])->name('posts.index');
        Route::get('/posts/new', [PostController::class, 'staffCreate'])->name('posts.new');
        Route::post('/posts/new/handle', [PostController::class, 'staffStore'])->name('posts.new.handle');
        Route::get('/posts/{post}/edit', [PostController::class, 'staffEdit'])->name('posts.edit');
        Route::put('/posts/{post}', [PostController::class, 'staffUpdate'])->name('posts.update');
        Route::delete('/posts/{post}', [PostController::class, 'staffDestroy'])->name('posts.destroy');
    }));

    // Route::post('/media/upload', [MediaController::class, 'upload'])->name('media.upload');

    // Admin Posts Routes
    Route::middleware([CheckLogin::class, CheckAdmin::class])->prefix('/posts')->name('posts.')->group((function() {
        Route::get('/', [PostController::class, 'index'])->name('index');
        Route::get('/published', [PostController::class, 'posts_published'])->name('published');
        Route::get('/draft', [PostController::class, 'posts_draft'])->name('draft');
        Route::get('/trash', [PostController::class, 'posts_trash'])->name('trash');
        Route::get('/pending', [PostController::class, 'posts_pending'])->name('pending');
        Route::get('/new', [PostController::class, 'posts_new'])->name('new');
        Route::post('/new/handle', [PostController::class, 'handle_posts_new'])->name('new.handle');
        Route::get('/{post}/edit', [PostController::class, 'edit'])->name('edit');
        Route::put('/{post}', [PostController::class, 'update'])->name('update');
        Route::post('/ai/generate', [PostController::class, 'generateWithAI'])->name('ai.generate');
        Route::get('/ai/test', [PostController::class, 'testAI'])->name('ai.test');
        // Route test AI đã tắt trên production
        // Route::post('/ai/test-simple', [PostController::class, 'testAISimple'])->name('ai.test-simple');
        // Route::get('/ai/quick-test', [PostController::class, 'quickAITest'])->name('ai.quick-test');

        // Hành động: đổi trạng thái, xoá vĩnh viễn
        Route::post('/{post}/status', [PostController::class, 'updateStatus'])->name('status');
        Route::delete('/{post}', [PostController::class, 'destroy'])->name('destroy');
    }));

    // Categories routes
    Route::middleware([CheckLogin::class, CheckAdmin::class])->prefix('/categories')->name('categories.')->group((function() {
        Route::get('/', [CategoryController::class, 'index'])->name('index');
        Route::get('/create', [CategoryController::class, 'create'])->name('create');
        Route::post('/', [CategoryController::class, 'store'])->name('store');
        Route::get('/{category}/edit', [CategoryController::class, 'edit'])->name('edit');
        Route::put('/{category}', [CategoryController::class, 'update'])->name('update');
        Route::delete('/{category}', [CategoryController::class, 'destroy'])->name('destroy');
        Route::put('/{category}/status', [CategoryController::class, 'updateStatus'])->name('status');
        Route::get('/api/list', [CategoryController::class, 'getCategories'])->name('api.list');
    }));

    // Cache management routes
    Route::middleware([CheckLogin::class, CheckAdmin::class])->prefix('/cache')->name('cache.')->group((function() {
        Route::get('/', [CacheController::class, 'index'])->name('index');
        Route::post('/clear-all', [CacheController::class, 'clearAll'])->name('clear-all');
        Route::post('/clear/{type}', [CacheController::class, 'clearSpecific'])->name('clear-specific');
    }));

    // Comments management routes
    Route::middleware([CheckLogin::class, CheckAdmin::class])->prefix('/comments')->name('comments.')->group((function() {
        Route::get('/', [AdminCommentController::class, 'index'])->name('index');
        Route::get('/{comment}', [AdminCommentController::class, 'show'])->name('show');
        Route::put('/{comment}/status', [AdminCommentController::class, 'updateStatus'])->name('status');
        Route::delete('/{comment}', [AdminCommentController::class, 'destroy'])->name('destroy');
        Route::post('/bulk-action', [AdminCommentController::class, 'bulkAction'])->name('bulk-action');
    }));

    // Settings management routes
    Route::middleware([CheckLogin::class, CheckAdmin::class])->prefix('/settings')->name('settings.')->group((function() {
        Route::get('/', [SettingController::class, 'index'])->name('index');
        Route::post('/', [SettingController::class, 'update'])->name('update');
        Route::post('/reset', [SettingController::class, 'reset'])->name('reset');
    }));
});

// Client routes with maintenance mode check
Route::middleware([MaintenanceMode::class])->group(function() {
    Route::get('/', [HomeController::class, 'index'])->name('home.index');
    Route::get('/trang-chu', [HomeController::class, 'index']);
    Route::get('/review-hai-phong', [HomeController::class, 'index']);
    Route::get('/gioi-thieu', [HomeController::class, 'introduce'])->name('home.introduce');
    Route::get('/lien-he', [ContactController::class, 'index'])->name('contact.index');
    Route::get('/user/{username}', [ProfileController::class, 'index'])->name('profile.index');
    Route::get('/tim-kiem/{keyword}', [SearchController::class, 'search'])->name('search.index');
    Route::post('/comments', [CommentController::class, 'store'])->name('comments.store');

    // Danh sách bài viết (URL chuẩn)
    Route::get('/bai-viet', [PostsListController::class, 'index'])->name('post.index');
    // Bài viết (URL chuẩn)
    Route::get('/bai-viet/{slug}', [NewsDetailController::class, 'newsDetailBySlug'])->name('post.detail')->where(['slug' => '[a-zA-Z0-9-_]+' ]);

    // Redirect 301: /danh-muc/{slug} -> /{slug}
    Route::get('/danh-muc/{slug}', function (string $slug) {
        return redirect()->route('posts-category.index', ['slug' => $slug], 301);
    })->where(['slug' => '[a-zA-Z0-9-_]+']);

    // Trang danh mục
    Route::get('/{slug}', [PostsCategory::class, 'index'])->name('posts-category.index')->where(['slug' => '^(?!bai-viet$|admin$|admin\/).+']);

    // Bài viết dạng cũ /{category}/{slug} -> redirect 301 về /bai-viet/{slug}
    Route::get('/{category}/{slug}', [NewsDetailController::class, 'newsDetail'])
        ->name('news.detail')
        ->where([
            'category' => '^(?!bai-viet$)[a-zA-Z0-9-_]+',
            'slug' => '[a-zA-Z0-9-_]+'
        ]);
});

// Sitemap động với giới hạn 200 bài viết mỗi file (chỉ chạy thủ công, không nên public trên prod)
// Đã có command sitemap:generate, nên route này có thể comment/xóa khi lên prod
/*
Route::get('/sitemap', function () {
    $posts = Post::with('category:id,slug')
        ->where('status', 'published')
        ->orderBy('published_at', 'desc')
        ->get(['id', 'slug', 'category_id', 'published_at']);
    $categories = Category::select(['id', 'name', 'slug'])->whereIn('slug', ['review-tong-hop', 'du-lich', 'am-thuc', 'check-in', 'dich-vu', 'tin-tuc', 'gioi-thieu'])->get();

    $totalPosts = $posts->count();
    $postsPerSitemap = 200;
    $totalSitemaps = ceil($totalPosts / $postsPerSitemap);

    // Tạo sitemap index nếu có nhiều hơn 200 bài viết
    if ($totalSitemaps > 1) {
        $sitemapIndex = Sitemap::create();
        
        // Sitemap chính (trang chủ, danh mục)
        $sitemapIndex->add(
            Url::create('/sitemap-main.xml')
                ->setLastModificationDate(new \DateTime())
        );
        
        // Sitemap cho bài viết
        for ($i = 1; $i <= $totalSitemaps; $i++) {
            $sitemapIndex->add(
                Url::create("/sitemap-posts-{$i}.xml")
                    ->setLastModificationDate(new \DateTime())
            );
        }
        
        $indexXml = $sitemapIndex->render();
        $indexXmlWithXsl = preg_replace(
            '/<\?xml version="1.0" encoding="UTF-8"\?>/',
            '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<?xml-stylesheet type="text/xsl" href="/sitemap.xsl"?>',
            $indexXml
        );
        
        file_put_contents(public_path('sitemap.xml'), $indexXmlWithXsl);
    }

    // Tạo sitemap chính (trang chủ, danh mục)
    $mainSitemap = Sitemap::create();
    $mainSitemap->add(Url::create('/'));
    $mainSitemap->add(
        Url::create('/review-hai-phong')
            ->setLastModificationDate(new \DateTime())
            ->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY)
            ->setPriority(0.9)
    );
    $mainSitemap->add(
        Url::create('/gioi-thieu')
            ->setLastModificationDate(new \DateTime())
            ->setChangeFrequency(Url::CHANGE_FREQUENCY_MONTHLY)
            ->setPriority(0.7)
    );

    // Thêm danh mục vào sitemap chính
    foreach ($categories as $category) {
        $mainSitemap->add(
            Url::create("/{$category->slug}")
                ->setLastModificationDate(new \DateTime())
                ->setChangeFrequency(Url::CHANGE_FREQUENCY_MONTHLY)
                ->setPriority(0.6)
        );
    }

    $mainXml = $mainSitemap->render();
    $mainXmlWithXsl = preg_replace(
        '/<\?xml version="1.0" encoding="UTF-8"\?>/',
        '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<?xml-stylesheet type="text/xsl" href="/sitemap.xsl"?>',
        $mainXml
    );
    
    file_put_contents(public_path('sitemap-main.xml'), $mainXmlWithXsl);

    // Tạo sitemap cho bài viết (chia nhỏ)
    $postChunks = $posts->chunk($postsPerSitemap);
    foreach ($postChunks as $index => $postChunk) {
        $sitemapNumber = $index + 1;
        $postSitemap = Sitemap::create();
        
        foreach ($postChunk as $post) {
            $postSitemap->add(
                Url::create("/bai-viet/{$post->slug}")
                    ->setLastModificationDate($post->published_at ? new \DateTime($post->published_at) : new \DateTime())
                    ->setChangeFrequency(Url::CHANGE_FREQUENCY_MONTHLY)
                    ->setPriority(0.6)
            );
        }
        
        $postXml = $postSitemap->render();
        $postXmlWithXsl = preg_replace(
            '/<\?xml version="1.0" encoding="UTF-8"\?>/',
            '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<?xml-stylesheet type="text/xsl" href="/sitemap.xsl"?>',
            $postXml
        );
        
        file_put_contents(public_path("sitemap-posts-{$sitemapNumber}.xml"), $postXmlWithXsl);
    }

    $message = "✅ Đã tạo sitemap thành công!\n";
    $message .= "📊 Tổng số bài viết: {$totalPosts}\n";
    $message .= "📁 Số file sitemap: " . ($totalSitemaps > 1 ? $totalSitemaps + 1 : 1) . "\n";
    
    if ($totalSitemaps > 1) {
        $message .= "📋 Sitemap index: sitemap.xml\n";
        $message .= "🏠 Sitemap chính: sitemap-main.xml\n";
        for ($i = 1; $i <= $totalSitemaps; $i++) {
            $message .= "📄 Sitemap bài viết {$i}: sitemap-posts-{$i}.xml\n";
        }
    } else {
        $message .= "📄 Sitemap: sitemap.xml\n";
    }

    return $message;
});
*/

// Serve sitemap files
Route::get('/sitemap.xml', function () {
    $xml = file_get_contents(public_path('sitemap.xml'));
    return response($xml, 200)->header('Content-Type', 'application/xml');
});

Route::get('/sitemap-main.xml', function () {
    $xml = file_get_contents(public_path('sitemap-main.xml'));
    return response($xml, 200)->header('Content-Type', 'application/xml');
});

Route::get('/sitemap-posts-{number}.xml', function ($number) {
    $filename = "sitemap-posts-{$number}.xml";
    if (file_exists(public_path($filename))) {
        $xml = file_get_contents(public_path($filename));
        return response($xml, 200)->header('Content-Type', 'application/xml');
    }
    return response('Sitemap not found', 404);
})->where('number', '[0-9]+');


