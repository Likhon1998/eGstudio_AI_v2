<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('cgi_generations', function (Blueprint $table) {
            $table->text('video_error_message')->nullable()->after('video_status');
        });
    }

    public function down()
    {
        Schema::table('cgi_generations', function (Blueprint $table) {
            $table->dropColumn('video_error_message');
        });
    }
};