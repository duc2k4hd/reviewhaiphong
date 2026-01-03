<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use App\Models\Setting;
use App\Models\Category;

class ViewServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        View::composer('*', function($view) {
            // Lấy danh mục gốc theo DB thực tế: parent_id NULL hoặc 0
            $navCategories = Category::with(['children:id,name,slug,parent_id'])
                ->where('status', 'active')
                ->where(function ($q) {
                    $q->whereNull('parent_id')->orWhere('parent_id', 0);
                })
                ->orderBy('name')
                ->get(['id','name','slug','parent_id']);

            $view->with([
                'settings' => Setting::getSettings(),
                'customKeySettings' => Setting::replaceCustomKey(),
                'navCategories' => $navCategories,
            ]);
        });
    }
}
