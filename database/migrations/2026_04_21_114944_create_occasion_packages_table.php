<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('occasion_packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            
            // What plan did they buy?
            $table->string('package_name')->default('Custom Occasion Plan');
            
            // Is this their currently active wallet?
            $table->boolean('is_active_selection')->default(true);
            
            // Their isolated Occasion credits
            $table->integer('prompt_credits')->default(0);
            $table->integer('image_credits')->default(0);
            $table->integer('video_credits')->default(0);
            $table->integer('branding_image_credits')->default(0);
            $table->integer('branding_video_credits')->default(0);
            $table->integer('social_post_credits')->default(0);
            
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('occasion_packages');
    }
};