<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class GalleryDownloadController extends Controller
{
    private const FORMATS = ['jpeg', 'jpg', 'png', 'webp', 'gif', 'bmp', 'svg'];

    public function download(Request $request)
    {
        $request->validate([
            'url'      => 'required|string|max:2048',
            'format'   => 'required|string|in:' . implode(',', self::FORMATS),
            'filename' => 'nullable|string|max:120',
        ]);

        $url = trim($request->url);
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return response()->json(['success' => false, 'message' => 'Invalid image URL.'], 422);
        }

        $format = strtolower($request->format === 'jpg' ? 'jpeg' : $request->format);

        if (!$this->isAllowedImageUrl($url, $request)) {
            return response()->json(['success' => false, 'message' => 'Image source is not allowed.'], 403);
        }

        try {
            $binary = $this->fetchImageBinary($url);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Could not load image for conversion.'], 422);
        }

        $baseName = $this->sanitizeFilename($request->filename ?: 'gallery-image');
        $ext = $format === 'jpeg' ? 'jpg' : $format;

        try {
            [$body, $mime] = $this->convertBinary($binary, $format, $url);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage() ?: 'Conversion failed for this format.',
            ], 422);
        }

        return response($body, 200, [
            'Content-Type'        => $mime,
            'Content-Disposition' => 'attachment; filename="' . $baseName . '.' . $ext . '"',
            'Cache-Control'       => 'no-store',
        ]);
    }

    private function isAllowedImageUrl(string $url, Request $request): bool
    {
        $host = strtolower(parse_url($url, PHP_URL_HOST) ?? '');
        if ($host === '') {
            return false;
        }

        $requestHost = strtolower($request->getHost());
        if ($requestHost !== '' && $host === $requestHost) {
            return true;
        }

        $appHost = strtolower(parse_url(config('app.url'), PHP_URL_HOST) ?? '');
        if ($appHost !== '' && $host === $appHost) {
            return true;
        }

        if (in_array($host, ['localhost', '127.0.0.1'], true)) {
            return true;
        }

        if (str_contains($host, 'cloudinary.com')) {
            return true;
        }

        return false;
    }

    private function fetchImageBinary(string $url): string
    {
        $path = parse_url($url, PHP_URL_PATH) ?? '';

        if (preg_match('#/storage/(.+)$#i', $path, $matches)) {
            $relative = urldecode($matches[1]);
            if ($relative !== '' && Storage::disk('public')->exists($relative)) {
                return Storage::disk('public')->get($relative);
            }
        }

        $response = Http::withoutVerifying()->timeout(60)->get($url);
        if (!$response->successful()) {
            throw new \RuntimeException('HTTP fetch failed');
        }

        return $response->body();
    }

    private function convertBinary(string $binary, string $format, string $sourceUrl = ''): array
    {
        $sourceFormat = $this->detectSourceFormat($binary, $sourceUrl);

        if ($sourceFormat && $this->formatsMatch($sourceFormat, $format)) {
            return [$binary, $this->mimeForFormat($sourceFormat)];
        }

        if ($format === 'svg') {
            return $this->toSvg($binary);
        }

        if (!function_exists('imagecreatefromstring')) {
            $ini = php_ini_loaded_file() ?: 'php.ini';
            throw new \RuntimeException(
                'Image conversion requires the PHP GD extension (extension=gd in ' . $ini . '). '
                . 'After enabling it, restart your PHP server — stop and start `php artisan serve`, '
                . 'or restart Apache in the XAMPP Control Panel.'
            );
        }

        $image = @imagecreatefromstring($binary);
        if ($image === false) {
            throw new \RuntimeException('Unsupported or corrupt image data.');
        }

        imagesavealpha($image, true);
        imagealphablending($image, true);

        ob_start();
        switch ($format) {
            case 'jpeg':
                $width = imagesx($image);
                $height = imagesy($image);
                $flat = imagecreatetruecolor($width, $height);
                $white = imagecolorallocate($flat, 255, 255, 255);
                imagefilledrectangle($flat, 0, 0, $width, $height, $white);
                imagecopy($flat, $image, 0, 0, 0, 0, $width, $height);
                imagejpeg($flat, null, 92);
                imagedestroy($flat);
                $mime = 'image/jpeg';
                break;
            case 'png':
                imagepng($image, null, 6);
                $mime = 'image/png';
                break;
            case 'webp':
                if (!function_exists('imagewebp')) {
                    imagedestroy($image);
                    throw new \RuntimeException('WebP conversion is not supported on this server.');
                }
                imagewebp($image, null, 90);
                $mime = 'image/webp';
                break;
            case 'gif':
                imagegif($image);
                $mime = 'image/gif';
                break;
            case 'bmp':
                if (!function_exists('imagebmp')) {
                    imagedestroy($image);
                    throw new \RuntimeException('BMP conversion is not supported on this server.');
                }
                imagebmp($image);
                $mime = 'image/bmp';
                break;
            default:
                imagedestroy($image);
                throw new \RuntimeException('Unknown format.');
        }
        imagedestroy($image);
        $body = ob_get_clean();

        return [$body, $mime];
    }

    private function toSvg(string $binary): array
    {
        $image = @imagecreatefromstring($binary);
        if ($image === false) {
            throw new \RuntimeException('Could not prepare SVG export.');
        }

        $width = imagesx($image);
        $height = imagesy($image);
        ob_start();
        imagepng($image);
        imagedestroy($image);
        $pngData = ob_get_clean();
        $b64 = base64_encode($pngData);

        $svg = '<?xml version="1.0" encoding="UTF-8"?>'
            . '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"'
            . ' width="' . $width . '" height="' . $height . '" viewBox="0 0 ' . $width . ' ' . $height . '">'
            . '<image width="' . $width . '" height="' . $height . '" xlink:href="data:image/png;base64,' . $b64 . '"/>'
            . '</svg>';

        return [$svg, 'image/svg+xml'];
    }

    private function sanitizeFilename(string $name): string
    {
        $clean = preg_replace('/[^a-zA-Z0-9_\-]+/', '-', $name) ?? 'gallery-image';
        $clean = trim($clean, '-_');

        return $clean !== '' ? $clean : 'gallery-image';
    }

    private function detectSourceFormat(string $binary, string $url): ?string
    {
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo !== false) {
                $mime = finfo_buffer($finfo, $binary) ?: '';
                finfo_close($finfo);
                $fromMime = match ($mime) {
                    'image/jpeg' => 'jpeg',
                    'image/png'  => 'png',
                    'image/webp' => 'webp',
                    'image/gif'  => 'gif',
                    'image/bmp', 'image/x-ms-bmp' => 'bmp',
                    'image/svg+xml' => 'svg',
                    default => null,
                };
                if ($fromMime) {
                    return $fromMime;
                }
            }
        }

        $path = strtolower(parse_url($url, PHP_URL_PATH) ?? '');
        return match (true) {
            str_ends_with($path, '.jpg'), str_ends_with($path, '.jpeg') => 'jpeg',
            str_ends_with($path, '.png')  => 'png',
            str_ends_with($path, '.webp') => 'webp',
            str_ends_with($path, '.gif')   => 'gif',
            str_ends_with($path, '.bmp')  => 'bmp',
            str_ends_with($path, '.svg')  => 'svg',
            default => null,
        };
    }

    private function formatsMatch(string $source, string $target): bool
    {
        $normalize = static fn (string $f) => $f === 'jpg' ? 'jpeg' : $f;

        return $normalize($source) === $normalize($target);
    }

    private function mimeForFormat(string $format): string
    {
        return match ($format === 'jpg' || $format === 'jpeg' ? 'jpeg' : $format) {
            'jpeg' => 'image/jpeg',
            'png'  => 'image/png',
            'webp' => 'image/webp',
            'gif'  => 'image/gif',
            'bmp'  => 'image/bmp',
            'svg'  => 'image/svg+xml',
            default => 'application/octet-stream',
        };
    }
}
