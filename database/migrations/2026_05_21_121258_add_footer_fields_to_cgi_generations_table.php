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
            $table->string('footer_image_url')->nullable()->after('branded_video_url');
            $table->string('footer_status')->default('pending')->after('footer_image_url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cgi_generations', function (Blueprint $table) {
            $table->dropColumn(['footer_image_url', 'footer_status']);
        });
    }
};
