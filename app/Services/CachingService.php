<?php

namespace App\Services;

use App\Models\Language;
use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class CachingService
{
    const CACHE_TTL = 86400; // 1 day
    const CACHE_SETTING_KEY_ALL = 'settings.all';

    /**
     * Generic cache remember
     */
    public static function cacheRemember($key, callable $callback, int $time = self::CACHE_TTL)
    {
        return Cache::remember($key, $time, $callback);
    }

    /**
     * Remove cache
     */
    public static function removeCache($key)
    {
        Cache::forget($key);
    }

    /**
     * Hybrid System Settings
     */
    public static function getSystemSettings(array|string $key = '*')
    {
        // 🔹 Case 1: Get all settings
        if ($key === '*') {
            return Cache::remember(self::CACHE_SETTING_KEY_ALL, self::CACHE_TTL, function () {
                return Setting::all()->pluck('value', 'name');
            });
        }

        // 🔹 Case 2: Multiple keys
        if (is_array($key)) {
            $cacheKey = 'settings.' . md5(json_encode($key));

            return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($key) {
                return Setting::whereIn('name', $key)
                    ->get()
                    ->pluck('value', 'name')
                    ->toArray();
            });
        }

        // 🔹 Case 3: Single key
        $cacheKey = "settings.$key";
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($key) {
            return Setting::where('name', $key)->first()?->value ?? '';
        });
    }

    /**
     * Get raw (un-accessed) setting values, cached.
     * Bypasses the Setting file-type URL accessor so callers get the actual stored path.
     */
    public static function getSystemSettingsRaw(array $keys)
    {
        $cacheKey = 'settings.raw.' . md5(json_encode($keys));

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($keys) {
            return Setting::whereIn('name', $keys)
                ->get()
                ->mapWithKeys(static fn (Setting $s) => [$s->name => $s->getRawOriginal('value')])
                ->toArray();
        });
    }

    /**
     * Clear settings cache (IMPORTANT)
     */
    public static function clearSettingsCache($key = null)
    {
        // Clear all
        if ($key === null) {
            Cache::forget(self::CACHE_SETTING_KEY_ALL);
            return;
        }

        // Clear multiple keys cache
        if (is_array($key)) {
            Cache::forget('settings.' . md5(json_encode($key)));
            return;
        }

        // Clear single key cache
        Cache::forget("settings.$key");
    }

    /**
     * Get all languages (cached)
     */
    public static function getLanguages()
    {
        return self::cacheRemember(config('constants.CACHE.LANGUAGE'), function () {
            return Language::where('status',true)->get();
        });
    }

    /**
     * Get default language (cached)
     */
    public static function getDefaultLanguage()
    {
        return self::cacheRemember('language.default', function () {
            return Language::where('code', 'en')->first();
        });
    }
}