<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

class PublicMediaUrl
{
    /**
     * Resolve a CGI / Occasion media field (full URL or storage-relative path).
     */
    public static function forMedia(?string $raw): string
    {
        if (empty($raw)) {
            return '';
        }

        if (str_starts_with($raw, 'http://') || str_starts_with($raw, 'https://')) {
            return $raw;
        }

        $path = ltrim(str_replace('\\', '/', $raw), '/');
        $path = preg_replace('#^storage/#', '', $path);

        return self::forPath($path);
    }

    public static function forPath(?string $filePath): string
    {
        if (empty($filePath)) {
            return '';
        }

        if (str_starts_with($filePath, 'http://') || str_starts_with($filePath, 'https://')) {
            return $filePath;
        }

        $normalized = ltrim(str_replace('\\', '/', $filePath), '/');

        if (config('filesystems.disks.public.driver') === 's3') {
            return Storage::disk('public')->url($normalized);
        }

        if (self::publicStorageLinkAvailable()) {
            return self::sameOriginUrl('storage/'.$normalized);
        }

        return self::sameOriginUrl('media/'.$normalized);
    }

    public static function exists(?string $filePath): bool
    {
        if (empty($filePath)) {
            return false;
        }

        if (str_starts_with($filePath, 'http://') || str_starts_with($filePath, 'https://')) {
            return true;
        }

        $normalized = ltrim(str_replace('\\', '/', $filePath), '/');

        return Storage::disk('public')->exists($normalized)
            || is_file(storage_path('app/public/'.$normalized));
    }

    /**
     * Build a URL on the same host the user is browsing (fixes 127.0.0.1 vs localhost mismatches).
     */
    private static function sameOriginUrl(string $path): string
    {
        $path = ltrim($path, '/');

        if (app()->bound('request') && request()) {
            $base = rtrim(request()->getBaseUrl(), '/');

            return ($base !== '' ? $base : '').'/'.$path;
        }

        return asset($path);
    }

    private static function publicStorageLinkAvailable(): bool
    {
        $link = public_path('storage');

        return is_link($link) || is_dir($link);
    }
}
