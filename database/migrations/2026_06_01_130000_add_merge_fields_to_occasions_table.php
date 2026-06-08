<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('occasions', function (Blueprint $table) {
            $table->string('merged_image_url')->nullable()->after('branded_video_url');
            $table->string('merge_status')->default('pending')->after('merged_image_url');
        });
    }

    public function down(): void
    {
        Schema::table('occasions', function (Blueprint $table) {
            $table->dropColumn(['merged_image_url', 'merge_status']);
        });
    }
};
