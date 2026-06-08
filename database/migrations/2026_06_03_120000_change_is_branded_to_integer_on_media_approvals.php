<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Laravel converts PHP booleans to integers (0/1) when binding query params.
 * Postgres' strict `boolean` column rejects integers, causing a 42804 type
 * mismatch on insert. Storing `is_branded` as a small integer is cross-DB
 * safe; the model's `boolean` cast still presents it as true/false.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE media_approvals ALTER COLUMN is_branded DROP DEFAULT');
            DB::statement('ALTER TABLE media_approvals ALTER COLUMN is_branded TYPE smallint USING (is_branded::integer)');
            DB::statement('ALTER TABLE media_approvals ALTER COLUMN is_branded SET DEFAULT 0');
        } else {
            Schema::table('media_approvals', function (Blueprint $table) {
                $table->smallInteger('is_branded')->default(0)->change();
            });
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE media_approvals ALTER COLUMN is_branded DROP DEFAULT');
            DB::statement('ALTER TABLE media_approvals ALTER COLUMN is_branded TYPE boolean USING (is_branded <> 0)');
            DB::statement('ALTER TABLE media_approvals ALTER COLUMN is_branded SET DEFAULT false');
        } else {
            Schema::table('media_approvals', function (Blueprint $table) {
                $table->boolean('is_branded')->default(false)->change();
            });
        }
    }
};
