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
        Schema::create('packages', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g., "Creator Pro"
            $table->decimal('price', 8, 2)->default(0.00);
            $table->string('billing_cycle')->default('monthly'); 
            $table->string('stripe_product_id')->nullable(); 
            
            // --- THE SAAS CREDIT LIMITS ---
            $table->integer('directive_allowance')->default(0);  // Limit for Prompt Making
            $table->integer('image_allowance')->default(0);      // Limit for Make Pic
            $table->integer('video_allowance')->default(0);      // Limit for Make Video (includes Audio)
            $table->integer('branding_allowance')->default(0);   // Limit for "Add Logo" feature
            $table->integer('social_post_allowance')->default(0);// Limit for Publishing to Social
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('packages');
    }
};
