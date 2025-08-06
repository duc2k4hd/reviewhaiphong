<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            // Add indexes for better query performance
            $table->index(['status', 'published_at'], 'posts_status_published_at_index');
            $table->index(['category_id', 'status'], 'posts_category_status_index');
            $table->index(['account_id', 'status'], 'posts_account_status_index');
            $table->index('views', 'posts_views_index');
            $table->index('slug', 'posts_slug_index');
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->index('slug', 'categories_slug_index');
            $table->index(['parent_id', 'is_active'], 'categories_parent_active_index');
        });

        Schema::table('accounts', function (Blueprint $table) {
            $table->index('username', 'accounts_username_index');
            $table->index('email', 'accounts_email_index');
        });

        Schema::table('comments', function (Blueprint $table) {
            $table->index(['post_id', 'status'], 'comments_post_status_index');
            $table->index('account_id', 'comments_account_index');
        });

        // Add fulltext search index for posts (MySQL/MariaDB)
        if (config('database.default') === 'mysql') {
            DB::statement('ALTER TABLE posts ADD FULLTEXT search_index (seo_title, seo_desc, content)');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropIndex('posts_status_published_at_index');
            $table->dropIndex('posts_category_status_index');
            $table->dropIndex('posts_account_status_index');
            $table->dropIndex('posts_views_index');
            $table->dropIndex('posts_slug_index');
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropIndex('categories_slug_index');
            $table->dropIndex('categories_parent_active_index');
        });

        Schema::table('accounts', function (Blueprint $table) {
            $table->dropIndex('accounts_username_index');
            $table->dropIndex('accounts_email_index');
        });

        Schema::table('comments', function (Blueprint $table) {
            $table->dropIndex('comments_post_status_index');
            $table->dropIndex('comments_account_index');
        });

        // Drop fulltext index
        if (config('database.default') === 'mysql') {
            DB::statement('ALTER TABLE posts DROP INDEX search_index');
        }
    }
};