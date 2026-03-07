<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Cache;
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
            // Tối ưu cực hạn: Gom nhóm global data (settings + navCategories)
            $globalBundle = Cache::remember('site_global_bundle_v1', now()->addHour(), function() {
                $categories = Category::with(['children:id,name,slug,parent_id'])
                    ->where('status', 'active')
                    ->where(function ($q) {
                        $q->whereNull('parent_id')->orWhere('parent_id', 0);
                    })
                    ->orderBy('name')
                    ->get(['id','name','slug','parent_id'])
                    ->toArray();
                
                $settings = Setting::all()->pluck('value', 'name')->toArray();
                
                return compact('categories', 'settings');
            });

            $navCategories = json_decode(json_encode($globalBundle['categories']));
            $settings = $globalBundle['settings'];
            $customKeySettings = collect($settings)->mapWithKeys(fn ($v, $k) => ["[$k]" => $v])->toArray();

            $view->with([
                'settings' => $settings,
                'customKeySettings' => $customKeySettings,
                'navCategories' => $navCategories,
            ]);
        });
    }
}
