<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('scraped_urls')) {
            Schema::create('scraped_urls', function (Blueprint $table) {
                $table->id();
                $table->string('url')->unique()->comment('URL đã được normalize (lowercase, không có trailing slash)');
                $table->string('source')->default('vinpearl')->comment('Nguồn: vinpearl, tour-pro, mia');
                $table->unsignedBigInteger('post_id')->nullable()->comment('ID bài viết đã tạo (nếu có)');
                $table->timestamp('scraped_at')->useCurrent()->comment('Thời gian cào');
                $table->timestamps();
                
                // Index để tìm kiếm nhanh
                $table->index('url');
                $table->index('source');
                $table->index('scraped_at');
                $table->index('post_id');
            });
        } else {
            // Nếu bảng đã tồn tại, chỉ thêm các cột còn thiếu
            Schema::table('scraped_urls', function (Blueprint $table) {
                if (!Schema::hasColumn('scraped_urls', 'source')) {
                    $table->string('source')->default('vinpearl')->after('url');
                }
                if (!Schema::hasColumn('scraped_urls', 'post_id')) {
                    $table->unsignedBigInteger('post_id')->nullable()->after('source');
                }
                if (!Schema::hasColumn('scraped_urls', 'scraped_at')) {
                    $table->timestamp('scraped_at')->useCurrent()->after('post_id');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scraped_urls');
    }
};
