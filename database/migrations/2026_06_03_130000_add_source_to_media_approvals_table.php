<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Distinguishes which studio an approved asset came from, so the same
     * filename in CGI vs. Occasion never collides.
     */
    public function up(): void
    {
        Schema::table('media_approvals', function (Blueprint $table) {
            $table->string('source')->default('cgi')->after('cgi_generation_id')->index();
        });
    }

    public function down(): void
    {
        Schema::table('media_approvals', function (Blueprint $table) {
            $table->dropIndex(['source']);
            $table->dropColumn('source');
        });
    }
};
