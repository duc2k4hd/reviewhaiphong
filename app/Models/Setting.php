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
}
