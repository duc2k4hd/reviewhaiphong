<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $table = 'settings';
    protected $fillable = [
        'name',
        'value',
    ];
    
    /**
     * Lấy tất cả settings
     */
    public static function getSettings() {
        return Cache::remember('settings', 3600, function () {
            return self::all()->pluck('value', 'name')->toArray();
        });
    }

    /**
     * Lấy tất cả keys
     */
    public static function getKeys() {
        return self::pluck('name')->toArray();
    }

    /**
     * Lấy value theo key
     */
    public static function getValue($key, $default = null) {
        $setting = self::where('name', $key)->first();
        return $setting ? $setting->value : $default;
    }

    /**
     * Set value cho key
     */
    public static function setValue($key, $value) {
        // Đảm bảo value không null
        if ($value === null) {
            $value = '';
        }
        
        $setting = self::where('name', $key)->first();
        
        if ($setting) {
            $setting->update(['value' => $value]);
        } else {
            self::create(['name' => $key, 'value' => $value]);
        }
        
        // Clear cache
        Cache::forget('settings');
        
        return true;
    }

    /**
     * Set nhiều values cùng lúc
     */
    public static function setValues($data) {
        foreach ($data as $key => $value) {
            // Đảm bảo key và value hợp lệ
            if (!empty($key) && $key !== '_token' && $key !== '_method') {
                self::setValue($key, $value);
            }
        }
        
        return true;
    }

    /**
     * Xóa setting theo key
     */
    public static function remove($key) {
        $setting = self::where('name', $key)->first();
        if ($setting) {
            $setting->delete();
            Cache::forget('settings');
            return true;
        }
        return false;
    }

    /**
     * Clear tất cả cache settings
     */
    public static function clearCache() {
        Cache::forget('settings');
    }

    /**
     * Replace custom keys in content
     */
    public static function replaceCustomKey(): array
    {
        $settings = self::getSettings();
        return collect($settings)
            ->mapWithKeys(fn ($value, $key) => ["[$key]" => $value])
            ->toArray();
    }
}
