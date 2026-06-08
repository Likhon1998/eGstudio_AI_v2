<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Occasion Studio now runs on the unified "pic plan" (the CGI packages /
 * user_packages wallet). The separate Occasion monetization tables are no
 * longer needed and are dropped here.
 *
 * Campaign data (occasions, occasion_social_posts) is preserved — only the
 * billing/wallet tables are removed.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('occasion_packages');
        Schema::dropIfExists('occasion_plans');
    }

    public function down(): void
    {
        Schema::create('occasion_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('price', 10, 2)->default(0);
            $table->string('billing_cycle')->default('monthly');
            $table->integer('prompt_allowance')->default(0);
            $table->integer('image_allowance')->default(0);
            $table->integer('video_allowance')->default(0);
            $table->integer('branding_image_allowance')->default(0);
            $table->integer('branding_video_allowance')->default(0);
            $table->integer('social_post_allowance')->default(0);
            $table->timestamps();
        });

        Schema::create('occasion_packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('occasion_plan_id')->nullable();
            $table->string('package_name')->nullable();
            $table->integer('prompt_credits')->default(0);
            $table->integer('image_credits')->default(0);
            $table->integer('video_credits')->default(0);
            $table->integer('branding_image_credits')->default(0);
            $table->integer('branding_video_credits')->default(0);
            $table->integer('social_post_credits')->default(0);
            $table->string('is_active_selection')->default('false');
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
    }
};
