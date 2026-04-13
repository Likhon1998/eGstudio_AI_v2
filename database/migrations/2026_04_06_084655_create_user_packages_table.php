<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('user_packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('package_id')->constrained()->cascadeOnDelete();
            
            // The credits specifically for this wallet
            $table->integer('directive_credits')->default(0);
            $table->integer('image_credits')->default(0);
            $table->integer('video_credits')->default(0);
            $table->integer('branding_credits')->default(0);
            $table->integer('social_post_credits')->default(0);
            
            // Its own specific expiration date
            $table->timestamp('expires_at')->nullable();
            
            // A switch so the user can choose which wallet they are currently draining
            $table->boolean('is_active_selection')->default(false); 
            
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('user_packages');
    }
};