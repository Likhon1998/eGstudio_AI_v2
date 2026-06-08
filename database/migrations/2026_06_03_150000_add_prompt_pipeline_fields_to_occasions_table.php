<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('occasions', function (Blueprint $table) {
            $table->text('prompt_error_message')->nullable()->after('status');
            $table->boolean('prompt_credit_deducted')->default(false)->after('prompt_error_message');
        });
    }

    public function down(): void
    {
        Schema::table('occasions', function (Blueprint $table) {
            $table->dropColumn(['prompt_error_message', 'prompt_credit_deducted']);
        });
    }
};
