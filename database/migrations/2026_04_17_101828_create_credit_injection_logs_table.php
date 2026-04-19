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
    Schema::create('credit_injection_logs', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained()->onDelete('cascade'); // The client who received credits
        $table->foreignId('admin_id')->constrained('users')->onDelete('cascade'); // The admin who injected them
        $table->string('credit_type'); // e.g., 'video_credits'
        $table->integer('amount'); // e.g., 10
        $table->string('billing_note')->nullable(); // e.g., "Billed $15 on Invoice #1042"
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('credit_injection_logs');
    }
};
