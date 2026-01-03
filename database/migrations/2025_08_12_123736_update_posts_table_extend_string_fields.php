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
        Schema::table('posts', function (Blueprint $table) {
            // Thay đổi các trường string có giới hạn 255 ký tự thành text không giới hạn
            // Không thay đổi slug vì nó có unique constraint
            $table->text('seo_title')->change();
            $table->text('seo_keywords')->change();
            $table->text('name')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            // Khôi phục lại các trường về string
            $table->string('seo_title')->change();
            $table->string('seo_keywords')->change();
            $table->string('name')->change();
        });
    }
};
