<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

/**
 * Settings that belong to the app rather than to the server.
 *
 * A value kept in the .env can only be changed by someone with a terminal,
 * and on this host that means a one-shot script and a deploy. Anything an
 * administrator should be able to change belongs here. The .env stays the
 * fallback, so nothing already configured there stops working.
 */
class Setting extends Model
{
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

    /**
     * A value that is a password in all but name.
     *
     * Encrypted with the app key, so a copy of the database on its own does
     * not hand over the keys to anything else.
     */
    public static function secret(string $key, $default = null)
    {
        $stored = static::get($key);

        if ($stored === null || $stored === '') {
            return $default;
        }

        try {
            return Crypt::decryptString($stored);
        } catch (\Throwable $e) {
            return $default;
        }
    }

    public static function putSecret(string $key, string $value): void
    {
        static::put($key, $value === '' ? '' : Crypt::encryptString($value));
    }

    public static function forget(string $key): void
    {
        static::where('key', $key)->delete();
        unset(static::$cache[$key]);
    }
}
