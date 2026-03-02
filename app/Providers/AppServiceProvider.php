<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL; // Import the URL facade

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
{
    // Checks for 'ngrok-free' which covers both .app and .dev domains
    if (str_contains(request()->header('host'), 'ngrok-free')) {
        URL::forceScheme('https');
    }
}
}