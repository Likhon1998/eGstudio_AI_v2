<?php

namespace App\Support;

/**
 * Normalizes n8n CGI autofill JSON into the exact keys the create form expects.
 */
class CgiAutofillMapper
{
    /** @var array<string, list<string>> */
    private const FIELD_ALIASES = [
        'product_name'    => ['product_name', 'productName', 'product', 'name', 'title'],
        'marketing_angle' => ['marketing_angle', 'marketingAngle', 'overlay_text', 'text_overlay', 'benefits', 'marketing', 'text'],
        'visual_prop'     => ['visual_prop', 'visualProp', 'product_usage', 'usage', 'props', 'decoration', 'how_used'],
        'atmosphere'      => ['atmosphere', 'background', 'scene', 'environment', 'setting'],
        'camera_motion'   => ['camera_motion', 'cameraMotion', 'movement', 'camera', 'camera_style'],
        'composition'     => ['composition', 'layout', 'position', 'framing', 'product_position'],
        'lighting_style'  => ['lighting_style', 'lightingStyle', 'lighting', 'light'],
    ];

    public static function map(array $raw): array
    {
        if (isset($raw[0]) && is_array($raw[0])) {
            $raw = $raw[0];
        }

        if (isset($raw['output']) && is_array($raw['output'])) {
            $raw = $raw['output'];
        } elseif (isset($raw['output']) && is_string($raw['output'])) {
            $decoded = json_decode($raw['output'], true);
            if (is_array($decoded)) {
                $raw = $decoded;
            }
        }

        $flat = self::flatten($raw);
        $out = [];

        foreach (self::FIELD_ALIASES as $canonical => $aliases) {
            foreach ($aliases as $key) {
                if (!empty($flat[$key]) && is_string($flat[$key])) {
                    $out[$canonical] = trim($flat[$key]);
                    break;
                }
            }
        }

        return $out;
    }

    /**
     * @return array<string, mixed>
     */
    private static function flatten(array $data, string $prefix = ''): array
    {
        $flat = [];

        foreach ($data as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            if (is_array($value) && !self::isList($value)) {
                $flat = array_merge($flat, self::flatten($value, $prefix));
                continue;
            }

            if (is_scalar($value)) {
                $flat[(string) $key] = $value;
            }
        }

        return $flat;
    }

    private static function isList(array $arr): bool
    {
        return $arr === [] || array_keys($arr) === range(0, count($arr) - 1);
    }
}
