<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('occasion_social_posts', function (Blueprint $table) {
            $table->id();
            
            // 🚨 THE FIX: Use foreignUuid instead of foreignId 🚨
            $table->foreignUuid('occasion_id')->constrained()->onDelete('cascade');
            
            // Keep user_id as foreignId (assuming your users table uses standard IDs)
            // *Note: If your users table ALSO uses UUIDs, change this to foreignUuid('user_id') as well.*
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // Post Details
            $table->string('platform')->default('facebook');
            $table->string('media_url');
            $table->boolean('is_branded')->default(false);
            $table->text('caption');
            
            // Tracking & Scheduling
            $table->enum('status', ['pending', 'scheduled', 'published', 'failed', 'n8n_rejected'])->default('pending');
            $table->timestamp('scheduled_at')->nullable();
            
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('occasion_social_posts');
    }
};