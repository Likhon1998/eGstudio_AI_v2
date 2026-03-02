<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cgi_generations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // Director Questions
            $table->string('product_name');
            $table->string('marketing_angle');
            $table->string('visual_prop');
            $table->string('atmosphere');
            $table->string('camera_motion');
            $table->string('composition');
            $table->string('lighting_style');
            
            // Prompts & Outputs
            $table->longtext('image_prompt')->nullable();  
            $table->longText('video_prompt')->nullable(); 
            $table->longText('audio_prompt')->nullable(); // Added audio prompt column
            $table->longText('negative_prompt')->nullable(); 

            // Status Tracking (All set to processing by default)
            $table->string('status')->default('processing');      // Prompt generation status
            $table->string('image_status')->default('processing'); // Image making status
            $table->string('video_status')->default('processing'); // Video making status
            
            // URLs
            $table->string('image_url')->nullable(); 
            $table->string('video_url')->nullable(); 
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cgi_generations');
    }
};