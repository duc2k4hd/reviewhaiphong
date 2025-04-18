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
use App\Http\Middleware\CheckAdmin;
use App\Http\Middleware\CheckLogin;
use App\Models\Category;
use Illuminate\Support\Facades\Route;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\Tags\Url;
use App\Models\Post;

// Admin
Route::prefix('/admin')->name('admin.')->group(function() {
    Route::get('/', [LoginController::class,'index']);
    Route::get('/login', [LoginController::class, 'index'])->name('login.index');
    Route::post('/login/handle', [LoginController::class, 'post'])->name('login.post');
    
    Route::middleware([CheckLogin::class, CheckAdmin::class])->prefix('/dashboard')->name('dashboard.')->group((function() {
        Route::get('/', [DashboardController::class,'index'])->name('index');
    }));

    Route::post('/media/upload', [MediaController::class, 'upload'])->name('media.upload');


    Route::middleware([CheckLogin::class, CheckAdmin::class])->prefix('/posts')->name('posts.')->group((function() {
        Route::get('/', [PostController::class, 'index'])->name('index');
        Route::get('/published', [PostController::class, 'posts_published'])->name('published');
        Route::get('/draft', [PostController::class, 'posts_draft'])->name('draft');
        Route::get('/trash', [PostController::class, 'posts_trash'])->name('trash');
        Route::get('/pending', [PostController::class, 'posts_pending'])->name('pending');
        Route::get('/new', [PostController::class, 'posts_new'])->name('new');
        Route::post('/new/handle', [PostController::class, 'handle_posts_new'])->name('new.handle');
    }));
});

// Client
Route::get('/', [HomeController::class, 'index'])->name('home.index');
Route::get('/trang-chu', [HomeController::class, 'index']);
Route::get('/review-hai-phong', [HomeController::class, 'index']);
Route::get('/gioi-thieu', [HomeController::class, 'ỉntroduce'])->name('home.ỉntroduce');
Route::get('/user/{username}', [ProfileController::class, 'index'])->name('profile.index');
Route::get('/tim-kiem/{keyword}', [SearchController::class, 'search'])->name('search.index');

Route::get('/sitemap', function () {
    $posts = Post::with('category:id,slug')
        ->where('status', 'published')
        ->get(['id', 'slug', 'category_id']);
    $categories = Category::select(['id', 'name', 'slug'])->whereIn('slug', ['review-hai-phong', 'du-lich', 'am-thuc', 'check-in', 'dich-vu', 'tin-tuc', 'gioi-thieu'])->get();

    $sitemap = Sitemap::create()->add(Url::create('/'));
    $sitemap->add(
        Url::create('/review-hai-phong')
            ->setLastModificationDate(new \DateTime())
            ->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY)
            ->setPriority(0.9)
    );
    
    // URL: /gioi-thieu
    $sitemap->add(
        Url::create('/gioi-thieu')
            ->setLastModificationDate(new \DateTime())
            ->setChangeFrequency(Url::CHANGE_FREQUENCY_MONTHLY)
            ->setPriority(0.7)
    );

    foreach ($categories as $category) {
        $sitemap->add(
            Url::create("/{$category->slug}")
                ->setLastModificationDate(new \DateTime())
                ->setChangeFrequency(Url::CHANGE_FREQUENCY_MONTHLY)
                ->setPriority(0.6)
        );
    }

    foreach ($posts as $post) {
        $sitemap->add(
            Url::create("/{$post->category->slug}/{$post->slug}")
                ->setLastModificationDate(new \DateTime())
                ->setChangeFrequency(Url::CHANGE_FREQUENCY_MONTHLY)
                ->setPriority(0.6)
        );
    }

    // Render XML ra chuỗi
    $xml = $sitemap->render();

    // Gắn dòng stylesheet vào
    $xmlWithXsl = preg_replace(
        '/<\?xml version="1.0" encoding="UTF-8"\?>/','<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<?xml-stylesheet type="text/xsl" href="/sitemap.xsl"?>', $xml
    );

// Ghi ra file public
file_put_contents(public_path('sitemap.xml'), $xmlWithXsl);

return '✅ Đã tạo sitemap đẹp thành công!';
});

Route::get('/sitemap.xml', function () {
$xml = file_get_contents(public_path('sitemap.xml'));
return response($xml, 200)->header('Content-Type', 'application/xml');
});

Route::get('/{slug}', [PostsCategory::class, 'index'])->name('posts-category.index');

// Phải để dòng này cuối cùng
Route::get('/{category}/{slug}', [NewsDetailController::class, 'newsDetail'])
->name('news.detail')
->where([
'category' => '[a-zA-Z0-9-_]+',
'slug' => '[a-zA-Z0-9-_]+'
]);
