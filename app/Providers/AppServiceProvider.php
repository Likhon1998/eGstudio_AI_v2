<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate; // 1. YOU MUST IMPORT THIS
use Illuminate\Support\Facades\URL; // 2. YOU MUST IMPORT THIS

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
        // 2. THE MASTER OVERRIDE
        // This intercepts every single @can() and $user->can() check in the entire system.
        Gate::before(function ($user, $ability) {
            
            // If the user's database column role is 'admin', instantly grant access.
            if ($user->role === 'admin') {
                return true;
            }

            // If they are not an admin, return null so Spatie can do its normal checks.
            return null;
        });

        if (config('app.env') !== 'local') {
            URL::forceScheme('https');
        }
    }
}