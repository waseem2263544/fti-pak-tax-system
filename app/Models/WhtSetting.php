<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhtSetting extends Model
{
    protected $table = 'wht_settings';
    protected $fillable = ['key', 'value'];

    protected static array $cache = [];

    public static function get(string $key, $default = null)
    {
        if (!array_key_exists($key, static::$cache)) {
            static::$cache[$key] = static::where('key', $key)->value('value');
        }

        return static::$cache[$key] ?? $default;
    }

    public static function put(string $key, $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
        static::$cache[$key] = $value;
    }
}
