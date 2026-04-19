<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            $table->integer('branding_image_allowance')->default(0)->after('branding_allowance');
            $table->integer('branding_video_allowance')->default(0)->after('branding_image_allowance');
        });

        Schema::table('user_packages', function (Blueprint $table) {
            $table->integer('branding_image_credits')->default(0)->after('branding_credits');
            $table->integer('branding_video_credits')->default(0)->after('branding_image_credits');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->integer('branding_image_credits')->default(0)->after('branding_credits');
            $table->integer('branding_video_credits')->default(0)->after('branding_image_credits');
        });
    }

    public function down(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            $table->dropColumn(['branding_image_allowance', 'branding_video_allowance']);
        });

        Schema::table('user_packages', function (Blueprint $table) {
            $table->dropColumn(['branding_image_credits', 'branding_video_credits']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['branding_image_credits', 'branding_video_credits']);
        });
    }
};
