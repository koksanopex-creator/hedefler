<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class SettingS
{
    protected static string $settingsFile = 'system_settings.json';

    /**
     * Get a setting value with a default fallback.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $settings = self::all();
        return $settings[$key] ?? $default;
    }

    /**
     * Set a setting value and persist it.
     */
    public static function set(string $key, mixed $value): void
    {
        $settings = self::all();
        $settings[$key] = $value;

        Storage::disk('local')->put(self::$settingsFile, json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        Cache::forever('system_settings', $settings);
    }

    /**
     * Get all settings.
     */
    public static function all(): array
    {
        return Cache::rememberForever('system_settings', function () {
            if (Storage::disk('local')->exists(self::$settingsFile)) {
                $content = Storage::disk('local')->get(self::$settingsFile);
                return json_decode($content, true) ?: [];
            }
            return [
                'form_revision_enabled' => true,
                'basvurular_acik' => true,
            ];
        });
    }
}

