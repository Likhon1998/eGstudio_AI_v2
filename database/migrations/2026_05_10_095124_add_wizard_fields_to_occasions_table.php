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
        Schema::table('occasions', function (Blueprint $table) {
            // Adding the missing fields from the new Alpine UI
            $table->integer('target_month')->nullable()->after('user_id');
            $table->integer('target_year')->nullable()->after('target_month');
            $table->text('custom_text_payload')->nullable()->after('custom_text');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('occasions', function (Blueprint $table) {
            // Drops the columns if you ever need to rollback
            $table->dropColumn([
                'target_month', 
                'target_year', 
                'custom_text_payload'
            ]);
        });
    }
};