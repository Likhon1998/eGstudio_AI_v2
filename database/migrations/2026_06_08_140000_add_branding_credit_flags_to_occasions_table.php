<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('occasions', function (Blueprint $table) {
            $table->smallInteger('branding_logo_credit_deducted')->default(0)->after('merge_status');
            $table->smallInteger('merge_branding_credit_deducted')->default(0)->after('branding_logo_credit_deducted');
        });
    }

    public function down(): void
    {
        Schema::table('occasions', function (Blueprint $table) {
            $table->dropColumn(['branding_logo_credit_deducted', 'merge_branding_credit_deducted']);
        });
    }
};
