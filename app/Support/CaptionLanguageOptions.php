<?php

namespace App\Support;

/**
 * Caption language list for Occasion + CGI studios (config cache safe).
 */
class CaptionLanguageOptions
{
    public static function all(): array
    {
        $languages = config('caption_language_options.languages');

        if (! is_array($languages) || $languages === []) {
            $file = config_path('caption_language_options.php');
            if (is_readable($file)) {
                $loaded = require $file;
                $languages = $loaded['languages'] ?? [];
            }
        }

        if (! is_array($languages) || $languages === []) {
            return self::defaults();
        }

        return $languages;
    }

    private static function defaults(): array
    {
        return [
            ['value' => 'bangla',     'label' => 'Bangla',     'native' => 'বাংলা'],
            ['value' => 'english',    'label' => 'English',    'native' => 'English'],
            ['value' => 'hindi',      'label' => 'Hindi',      'native' => 'हिन्दी'],
            ['value' => 'urdu',       'label' => 'Urdu',       'native' => 'اردو'],
            ['value' => 'arabic',     'label' => 'Arabic',     'native' => 'العربية'],
            ['value' => 'spanish',    'label' => 'Spanish',    'native' => 'Español'],
            ['value' => 'french',     'label' => 'French',     'native' => 'Français'],
            ['value' => 'portuguese', 'label' => 'Portuguese', 'native' => 'Português'],
            ['value' => 'mandarin',   'label' => 'Mandarin',   'native' => '中文'],
            ['value' => 'japanese',   'label' => 'Japanese',   'native' => '日本語'],
        ];
    }
}
