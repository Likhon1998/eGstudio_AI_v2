<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('credit_injection_logs', function (Blueprint $table) {
            $table->string('wallet_type', 20)->default('cgi')->after('admin_id');
        });
    }

    public function down(): void
    {
        Schema::table('credit_injection_logs', function (Blueprint $table) {
            $table->dropColumn('wallet_type');
        });
    }
};
