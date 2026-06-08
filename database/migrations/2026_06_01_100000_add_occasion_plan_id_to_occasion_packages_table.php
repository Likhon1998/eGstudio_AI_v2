<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('occasion_packages', function (Blueprint $table) {
            $table->foreignId('occasion_plan_id')->nullable()->after('user_id')->constrained('occasion_plans')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('occasion_packages', function (Blueprint $table) {
            $table->dropConstrainedForeignId('occasion_plan_id');
        });
    }
};
