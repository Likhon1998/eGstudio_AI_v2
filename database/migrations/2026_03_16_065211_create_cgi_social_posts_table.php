<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('cgi_social_posts', function (Blueprint $table) {
            $table->id();
            
            // FIX: Changed from foreignId to foreignUuid to match your Supabase table
            $table->foreignUuid('cgi_generation_id')
                  ->constrained('cgi_generations')
                  ->onDelete('cascade');
            
            // Post Details
            $table->string('platform')->default('facebook');
            $table->string('media_type'); 
            $table->boolean('is_branded')->default(false); 
            $table->text('media_url'); 
            $table->text('caption')->nullable(); 
            
            // Status Tracking
            $table->string('status')->default('pending'); 
            $table->timestamp('published_at')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cgi_social_posts');
    }
};