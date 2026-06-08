<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Adds the "client account" linking fields used by the Approval workflow.
     *
     *  - account_type: 'maker'   => creates pics/videos, must get approval before publishing
     *                  'approver'=> only reviews/approves content for one client
     *                  null      => legacy/admin/standalone behaviour (unchanged)
     *  - client_id:    groups the 2 credentials of a single client together.
     *                  For an approver this points at the maker user id.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('account_type')->nullable()->after('role');
            $table->unsignedBigInteger('client_id')->nullable()->after('account_type');
            $table->index('client_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['client_id']);
            $table->dropColumn(['account_type', 'client_id']);
        });
    }
};
