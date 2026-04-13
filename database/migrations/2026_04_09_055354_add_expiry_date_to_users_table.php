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
        Schema::table('users', function (Blueprint $table) {
            // Add expiry_date if it doesn't exist
            if (!Schema::hasColumn('users', 'expiry_date')) {
                $table->timestamp('expiry_date')->nullable();
            }
            
            // Add package_id if it doesn't exist
            if (!Schema::hasColumn('users', 'package_id')) {
                $table->unsignedBigInteger('package_id')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'expiry_date')) {
                $table->dropColumn('expiry_date');
            }
            if (Schema::hasColumn('users', 'package_id')) {
                $table->dropColumn('package_id');
            }
        });
    }
};