<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    Schema::create('marketing_themes', function (Blueprint $table) {
        $table->id();
        $table->string('category_name'); 
        $table->string('prop_focus');    
        $table->text('motion_type');     
        $table->string('atmosphere');    
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('marketing_themes');
    }
};
