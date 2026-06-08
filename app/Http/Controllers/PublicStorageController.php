<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class PublicStorageController extends Controller
{
    /**
     * Serve public disk files when the symlink target is missing (common on Windows)
     * or when the request falls through to Laravel.
     */
    public function show(string $path): Response
    {
        $path = str_replace(['..', '\\'], ['', '/'], $path);

        if ($path === '' || ! Storage::disk('public')->exists($path)) {
            abort(404);
        }

        return Storage::disk('public')->response($path);
    }
}
