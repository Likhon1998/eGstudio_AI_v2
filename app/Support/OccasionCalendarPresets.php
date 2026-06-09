<?php

namespace App\Support;

/**
 * Month-indexed occasion presets for Occasion Studio create form (config cache safe).
 */
class OccasionCalendarPresets
{
    public static function maps(): array
    {
        $occasionsMap = config('occasion_presets.occasionsMap');
        $masterFestivals = config('occasion_presets.masterFestivals');

        $needsFallback = ! is_array($occasionsMap) || $occasionsMap === []
            || ! is_array($masterFestivals) || $masterFestivals === [];

        if ($needsFallback) {
            $file = config_path('occasion_presets/maps.php');
            if (is_readable($file)) {
                $maps = require $file;
                if (! is_array($occasionsMap) || $occasionsMap === []) {
                    $occasionsMap = $maps['occasionsMap'] ?? [];
                }
                if (! is_array($masterFestivals) || $masterFestivals === []) {
                    $masterFestivals = $maps['masterFestivals'] ?? [];
                }
            }
        }

        return [
            'occasionsMap'    => is_array($occasionsMap) ? $occasionsMap : [],
            'masterFestivals' => is_array($masterFestivals) ? $masterFestivals : [],
        ];
    }
}
