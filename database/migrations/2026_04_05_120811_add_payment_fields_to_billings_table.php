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
    Schema::table('billings', function (Blueprint $table) {
        // Check if the column exists before trying to add it
        if (!Schema::hasColumn('billings', 'transaction_id')) {
            $table->string('transaction_id')->nullable();
        }
        
        if (!Schema::hasColumn('billings', 'payment_proof')) {
            $table->string('payment_proof')->nullable();
        }
    });
}

public function down()
{
    Schema::table('billings', function (Blueprint $table) {
        $table->dropColumn(['transaction_id', 'payment_proof']);
    });
}
};
