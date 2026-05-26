<?php
use App\Models\Category;
use App\Models\Post;
use App\Models\Account;
use Illuminate\Support\Str;

$categories = Category::where('status', 'active')->get();
$account = Account::first();

if (!$account) {
    $account = Account::create(['username' => 'admin', 'password' => bcrypt('password'), 'email' => 'admin@gmail.com', 'role' => 'admin', 'status' => 'active']);
}

foreach ($categories as $category) {
    for ($i = 1; $i <= 9; $i++) {
        Post::create([
            'category_id' => $category->id,
            'account_id' => $account->id,
            'name' => "Bài viết mẫu $i cho danh mục {$category->name}",
            'slug' => Str::slug("Bai viet mau $i cho danh muc {$category->name} " . rand(1000, 9999)),
            'content' => '<p>Đây là nội dung bài viết mẫu. Nội dung chi tiết sẽ được cập nhật sau.</p>',
            'seo_title' => "Bài viết mẫu $i",
            'seo_desc' => "Mô tả bài viết mẫu $i",
            'seo_keywords' => "từ khóa 1, từ khóa 2",
            'type' => 'article',
            'last_updated_by' => $account->id,
            'seo_image' => 'default.jpg',
            'status' => 'published',
            'published_at' => now()->subDays(rand(1, 10)),
            'views' => rand(100, 5000)
        ]);
    }
}
echo "Created dummy posts.\n";
