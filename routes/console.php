<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule; // Add this to allow scheduling

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// 🛡️ AUTOMATED SUBSCRIPTION EXPIRY (Runs every midnight)
// This will check the database daily and wipe the credits of users whose time is up!
Schedule::command('subscriptions:expire')->daily();