<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('product_assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name'); // The display name for the image
            $table->string('file_path'); // The actual storage path
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('product_assets');
    }
};