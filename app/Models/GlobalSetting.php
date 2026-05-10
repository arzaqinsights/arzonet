<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GlobalSetting extends Model
{
    protected $fillable = ['key', 'value'];

    protected $casts = [
        'value' => 'array',
    ];

    public static function get($key, $default = null)
    {
        $setting = self::where('key', $key)->first();
        return $setting ? $setting->value : $default;
    }

    public static function set($key, $value)
    {
        return self::updateOrCreate(['key' => $key], ['value' => $value]);
    }
}
