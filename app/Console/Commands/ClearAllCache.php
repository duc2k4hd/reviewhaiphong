<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

class ClearAllCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cache:clear-all';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Xóa tất cả cache của ứng dụng';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔄 Đang xóa cache...');

        try {
            // Xóa cache ứng dụng
            Artisan::call('cache:clear');
            $this->info('✅ Đã xóa application cache');

            // Xóa config cache
            Artisan::call('config:clear');
            $this->info('✅ Đã xóa config cache');

            // Xóa route cache
            Artisan::call('route:clear');
            $this->info('✅ Đã xóa route cache');

            // Xóa view cache
            Artisan::call('view:clear');
            $this->info('✅ Đã xóa view cache');

            // Xóa compiled classes
            Artisan::call('clear-compiled');
            $this->info('✅ Đã xóa compiled classes');

            // Xóa cache database
            if (function_exists('opcache_reset')) {
                opcache_reset();
                $this->info('✅ Đã xóa OPcache');
            }

            // Xóa cache thủ công
            Cache::flush();
            $this->info('✅ Đã xóa tất cả cache');

            // Xóa thư mục bootstrap/cache
            $bootstrapCachePath = base_path('bootstrap/cache');
            if (File::exists($bootstrapCachePath)) {
                File::deleteDirectory($bootstrapCachePath);
                File::makeDirectory($bootstrapCachePath);
                $this->info('✅ Đã xóa bootstrap cache');
            }

            $this->info('🎉 Xóa cache hoàn tất!');
            return 0;

        } catch (\Exception $e) {
            $this->error('❌ Lỗi khi xóa cache: ' . $e->getMessage());
            return 1;
        }
    }
}
