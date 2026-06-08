<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_assets', function (Blueprint $table) {
            $table->string('asset_type', 20)->default('product')->after('user_id');
            $table->index(['user_id', 'asset_type']);
        });
    }

    public function down(): void
    {
        Schema::table('product_assets', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'asset_type']);
            $table->dropColumn('asset_type');
        });
    }
};
