<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One row per pic/video a maker submits for client approval.
     * The approver can approve/reject and leave a note the maker can read,
     * and only approved media is allowed to be published to social.
     */
    public function up(): void
    {
        Schema::create('media_approvals', function (Blueprint $table) {
            $table->id();

            // cgi_generations.id is a string UUID, so match the type (no hard FK to stay engine-safe)
            $table->string('cgi_generation_id')->index();

            $table->unsignedBigInteger('maker_id')->index();   // who created/submitted it
            $table->string('product_name')->nullable();

            $table->string('media_type');                      // 'image' | 'video'
            $table->string('variant')->default('raw');         // raw | branded | footer | merged
            $table->boolean('is_branded')->default(false);
            $table->text('media_url');

            $table->string('status')->default('pending')->index(); // pending | approved | rejected
            $table->text('comment')->nullable();               // approver's note to the maker

            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_approvals');
    }
};
