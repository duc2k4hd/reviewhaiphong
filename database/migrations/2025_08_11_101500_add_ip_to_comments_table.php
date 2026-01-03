<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('comments', function (Blueprint $table) {
            if (!Schema::hasColumn('comments', 'ip')) {
                $table->string('ip', 45)->nullable()->after('account_id');
            }
        });

        // Thêm unique index để đảm bảo một IP chỉ được bình luận 1 lần trên mỗi bài viết
        Schema::table('comments', function (Blueprint $table) {
            $table->unique(['post_id', 'ip'], 'comments_post_ip_unique');
        });
    }

    public function down()
    {
        Schema::table('comments', function (Blueprint $table) {
            // Xoá unique index nếu tồn tại
            $sm = Schema::getConnection()->getDoctrineSchemaManager();
            $indexes = $sm->listTableIndexes('comments');
            if (array_key_exists('comments_post_ip_unique', $indexes)) {
                $table->dropUnique('comments_post_ip_unique');
            }

            if (Schema::hasColumn('comments', 'ip')) {
                $table->dropColumn('ip');
            }
        });
    }
};




