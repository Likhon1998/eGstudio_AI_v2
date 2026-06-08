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
        Schema::table('cgi_generations', function (Blueprint $table) {
            $table->string('merged_image_url')->nullable();
            $table->string('merge_status')->default('pending'); // pending, processing, completed, failed
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cgi_generations', function (Blueprint $table) {
            $table->dropColumn(['merged_image_url', 'merge_status']);
        });
    }
};
