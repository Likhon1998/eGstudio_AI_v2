<?php

namespace App\Support;

class CgiBusinessPresets
{
    public static function all(): array
    {
        $presets = config('cgi_business_presets');

        if (! is_array($presets) || $presets === []) {
            $file = config_path('cgi_business_presets.php');
            if (is_readable($file)) {
                $presets = require $file;
            }
        }

        return is_array($presets) ? $presets : [];
    }

    /** @return list<string> */
    public static function keys(): array
    {
        return array_keys(self::all());
    }

    public static function isValid(string $key): bool
    {
        return array_key_exists($key, self::all());
    }

    public static function get(string $key): ?array
    {
        return self::all()[$key] ?? null;
    }

    public static function brandDirectives(string $key = 'lighting'): string
    {
        $preset = self::get($key) ?? self::get('lighting');
        $lines = $preset['brand_directives'] ?? [];

        if (! is_array($lines) || $lines === []) {
            return 'MANDATORY BRAND DIRECTIVES (must always be applied): Produce a clean, photorealistic advertising poster.';
        }

        return 'MANDATORY BRAND DIRECTIVES (must always be applied): ' . implode(' ', $lines);
    }
}
