<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Schema::table ADDS to the existing table, it does NOT delete it!
        Schema::table('users', function (Blueprint $table) {
            // Link the user to their purchased package safely
            $table->foreignId('package_id')->nullable()->constrained()->nullOnDelete();
            
            // The Live Digital Wallet Balances (Countdowns)
            $table->integer('directive_credits')->default(0);
            $table->integer('image_credits')->default(0);
            $table->integer('video_credits')->default(0);
            $table->integer('branding_credits')->default(0); // Credits remaining for "Add Logo"
            $table->integer('social_post_credits')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Safely remove the columns if you ever need to rollback
            $table->dropForeign(['package_id']);
            $table->dropColumn([
                'package_id', 
                'directive_credits', 
                'image_credits', 
                'video_credits', 
                'branding_credits',
                'social_post_credits'
            ]);
        });
    }
};