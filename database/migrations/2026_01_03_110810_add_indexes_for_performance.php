<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Thêm indexes để tối ưu performance cho hàng triệu bài viết
     */
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            // Index cho status và published_at (query phổ biến nhất)
            if (!$this->hasIndex('posts', 'posts_status_published_at_index')) {
                $table->index(['status', 'published_at'], 'posts_status_published_at_index');
            }
            
            // Index cho category_id và status
            if (!$this->hasIndex('posts', 'posts_category_status_index')) {
                $table->index(['category_id', 'status'], 'posts_category_status_index');
            }
            
            // Index cho views (sắp xếp theo lượt xem)
            if (!$this->hasIndex('posts', 'posts_views_index')) {
                $table->index('views', 'posts_views_index');
            }
            
            // Index cho slug (unique lookup)
            if (!$this->hasIndex('posts', 'posts_slug_index')) {
                $table->index('slug', 'posts_slug_index');
            }
            
            // Index cho account_id
            if (!$this->hasIndex('posts', 'posts_account_id_index')) {
                $table->index('account_id', 'posts_account_id_index');
            }
            
            // Composite index cho category + published_at + status
            if (!$this->hasIndex('posts', 'posts_category_published_status_index')) {
                $table->index(['category_id', 'published_at', 'status'], 'posts_category_published_status_index');
            }
        });

        Schema::table('categories', function (Blueprint $table) {
            // Index cho slug
            if (!$this->hasIndex('categories', 'categories_slug_index')) {
                $table->index('slug', 'categories_slug_index');
            }
            
            // Index cho status
            if (!$this->hasIndex('categories', 'categories_status_index')) {
                $table->index('status', 'categories_status_index');
            }
            
            // Index cho parent_id
            if (!$this->hasIndex('categories', 'categories_parent_id_index')) {
                $table->index('parent_id', 'categories_parent_id_index');
            }
        });

        Schema::table('comments', function (Blueprint $table) {
            // Index cho post_id và status
            if (!$this->hasIndex('comments', 'comments_post_status_index')) {
                $table->index(['post_id', 'status'], 'comments_post_status_index');
            }
            
            // Index cho created_at (sắp xếp comments mới nhất)
            if (!$this->hasIndex('comments', 'comments_created_at_index')) {
                $table->index('created_at', 'comments_created_at_index');
            }
        });

        Schema::table('accounts', function (Blueprint $table) {
            // Index cho username (nếu chưa có unique)
            if (!$this->hasIndex('accounts', 'accounts_username_index')) {
                $table->index('username', 'accounts_username_index');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropIndex('posts_status_published_at_index');
            $table->dropIndex('posts_category_status_index');
            $table->dropIndex('posts_views_index');
            $table->dropIndex('posts_slug_index');
            $table->dropIndex('posts_account_id_index');
            $table->dropIndex('posts_category_published_status_index');
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropIndex('categories_slug_index');
            $table->dropIndex('categories_status_index');
            $table->dropIndex('categories_parent_id_index');
        });

        Schema::table('comments', function (Blueprint $table) {
            $table->dropIndex('comments_post_status_index');
            $table->dropIndex('comments_created_at_index');
        });

        Schema::table('accounts', function (Blueprint $table) {
            $table->dropIndex('accounts_username_index');
        });
    }

    /**
     * Kiểm tra xem index đã tồn tại chưa
     */
    private function hasIndex(string $table, string $indexName): bool
    {
        $connection = Schema::getConnection();
        $database = $connection->getDatabaseName();
        
        $indexes = $connection->select(
            "SELECT COUNT(*) as count FROM information_schema.statistics 
             WHERE table_schema = ? AND table_name = ? AND index_name = ?",
            [$database, $table, $indexName]
        );
        
        return $indexes[0]->count > 0;
    }
};
