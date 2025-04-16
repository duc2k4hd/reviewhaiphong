<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $table = 'settings';
    protected $fillable = [
        'key',
        'value',
    ];
    
    public static function getSettings() {
        $settings = Setting::all()->pluck('value', 'name')->toArray();
        return $settings;
    }

    public static function getKeys() {
        $key = Setting::pluck("name")->toArray();
        return $key;
    }

    public static function replaceCustomKey(): array
    {
        $settings = self::getSettings();
        return collect($settings)
            ->mapWithKeys(fn ($value, $key) => ["[$key]" => $value])
            ->toArray();
    }

    public static function getValue($key) {
        $value = Setting::where("name", $key)->first();
    }
}
