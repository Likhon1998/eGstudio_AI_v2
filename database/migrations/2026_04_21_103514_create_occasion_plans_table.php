<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('occasion_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('price', 8, 2);
            $table->string('billing_cycle')->default('monthly');
            
            // Occasion specific allocations
            $table->integer('prompt_allowance')->default(0);
            $table->integer('image_allowance')->default(0);
            $table->integer('video_allowance')->default(0);
            $table->integer('branding_image_allowance')->default(0);
            $table->integer('branding_video_allowance')->default(0);
            $table->integer('social_post_allowance')->default(0);
            
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('occasion_plans');
    }
};