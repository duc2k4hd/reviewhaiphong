<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class CacheController extends Controller
{
    /**
     * Hiển thị trang quản lý cache
     */
    public function index()
    {
        $account = $this->loadAccount();
        
        // Lấy thông tin cache
        $cacheInfo = [
            'app_cache' => $this->getCacheSize('app'),
            'config_cache' => $this->getCacheSize('config'),
            'route_cache' => $this->getCacheSize('routes'),
            'view_cache' => $this->getCacheSize('views'),
            'bootstrap_cache' => $this->getBootstrapCacheSize(),
        ];

        return view('admin.cache.index', compact('account', 'cacheInfo'));
    }

    /**
     * Xóa tất cả cache
     */
    public function clearAll(Request $request)
    {
        try {
            // Xóa cache ứng dụng
            Artisan::call('cache:clear');
            Artisan::call('config:clear');
            Artisan::call('route:clear');
            Artisan::call('view:clear');
            Artisan::call('clear-compiled');

            // Xóa OPcache nếu có
            if (function_exists('opcache_reset')) {
                opcache_reset();
            }

            // Xóa cache thủ công
            Cache::flush();

            // Xóa thư mục bootstrap/cache
            $bootstrapCachePath = base_path('bootstrap/cache');
            if (File::exists($bootstrapCachePath)) {
                File::deleteDirectory($bootstrapCachePath);
                File::makeDirectory($bootstrapCachePath);
            }

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => '✅ Đã xóa tất cả cache thành công!'
                ]);
            }

            return redirect()->back()->with('success', '✅ Đã xóa tất cả cache thành công!');

        } catch (\Exception $e) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => '❌ Lỗi khi xóa cache: ' . $e->getMessage()
                ]);
            }

            return redirect()->back()->with('error', '❌ Lỗi khi xóa cache: ' . $e->getMessage());
        }
    }

    /**
     * Xóa cache cụ thể
     */
    public function clearSpecific(Request $request, $type)
    {
        try {
            switch ($type) {
                case 'app':
                    Artisan::call('cache:clear');
                    $message = '✅ Đã xóa application cache';
                    break;
                case 'config':
                    Artisan::call('config:clear');
                    $message = '✅ Đã xóa config cache';
                    break;
                case 'route':
                    Artisan::call('route:clear');
                    $message = '✅ Đã xóa route cache';
                    break;
                case 'view':
                    Artisan::call('view:clear');
                    $message = '✅ Đã xóa view cache';
                    break;
                case 'bootstrap':
                    $bootstrapCachePath = base_path('bootstrap/cache');
                    if (File::exists($bootstrapCachePath)) {
                        File::deleteDirectory($bootstrapCachePath);
                        File::makeDirectory($bootstrapCachePath);
                    }
                    $message = '✅ Đã xóa bootstrap cache';
                    break;
                default:
                    throw new \Exception('Loại cache không hợp lệ');
            }

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $message
                ]);
            }

            return redirect()->back()->with('success', $message);

        } catch (\Exception $e) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => '❌ Lỗi: ' . $e->getMessage()
                ]);
            }

            return redirect()->back()->with('error', '❌ Lỗi: ' . $e->getMessage());
        }
    }

    /**
     * Lấy kích thước cache
     */
    private function getCacheSize($type)
    {
        try {
            switch ($type) {
                case 'app':
                    $path = storage_path('framework/cache');
                    break;
                case 'config':
                    $path = storage_path('framework/cache');
                    break;
                case 'routes':
                    $path = storage_path('framework/cache');
                    break;
                case 'views':
                    $path = storage_path('framework/views');
                    break;
                default:
                    return '0 KB';
            }

            if (File::exists($path)) {
                $size = $this->getDirectorySize($path);
                return $this->formatBytes($size);
            }

            return '0 KB';
        } catch (\Exception $e) {
            return '0 KB';
        }
    }

    /**
     * Lấy kích thước bootstrap cache
     */
    private function getBootstrapCacheSize()
    {
        try {
            $path = base_path('bootstrap/cache');
            if (File::exists($path)) {
                $size = $this->getDirectorySize($path);
                return $this->formatBytes($size);
            }
            return '0 KB';
        } catch (\Exception $e) {
            return '0 KB';
        }
    }

    /**
     * Tính kích thước thư mục
     */
    private function getDirectorySize($path)
    {
        $size = 0;
        foreach (File::allFiles($path) as $file) {
            $size += $file->getSize();
        }
        return $size;
    }

    /**
     * Format bytes thành KB, MB, GB
     */
    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }

    /**
     * Load thông tin tài khoản admin
     */
    public function loadAccount()
    {
        return auth()->guard('web')->user();
    }
}
