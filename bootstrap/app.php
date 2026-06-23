<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // ALLOW n8n to post data to this specific URL
        // We commented this out because n8n now uses the Supabase Node directly!
        // $middleware->validateCsrfTokens(except: [
        //     'cgi/callback', 
        // ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (TokenMismatchException $e, Request $request) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'message'         => 'Your session has expired. Please log in again.',
                    'session_expired' => true,
                    'login_url'       => route('login', ['session_expired' => 1]),
                ], 419);
            }

            return response()->view('errors.419', [], 419);
        });
    })->create();