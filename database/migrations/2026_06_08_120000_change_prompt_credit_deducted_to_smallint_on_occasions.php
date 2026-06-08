<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Laravel binds PHP booleans as integers (0/1). Postgres `boolean` columns
 * reject that (SQLSTATE 42804). Store as smallint; model cast keeps true/false.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE occasions ALTER COLUMN prompt_credit_deducted DROP DEFAULT');
            DB::statement('ALTER TABLE occasions ALTER COLUMN prompt_credit_deducted TYPE smallint USING (prompt_credit_deducted::integer)');
            DB::statement('ALTER TABLE occasions ALTER COLUMN prompt_credit_deducted SET DEFAULT 0');
        } else {
            Schema::table('occasions', function (Blueprint $table) {
                $table->smallInteger('prompt_credit_deducted')->default(0)->change();
            });
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE occasions ALTER COLUMN prompt_credit_deducted DROP DEFAULT');
            DB::statement('ALTER TABLE occasions ALTER COLUMN prompt_credit_deducted TYPE boolean USING (prompt_credit_deducted <> 0)');
            DB::statement('ALTER TABLE occasions ALTER COLUMN prompt_credit_deducted SET DEFAULT false');
        } else {
            Schema::table('occasions', function (Blueprint $table) {
                $table->boolean('prompt_credit_deducted')->default(false)->change();
            });
        }
    }
};
