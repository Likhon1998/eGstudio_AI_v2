<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Postgres rejects integer bindings (0/1) on strict boolean columns (42804).
 * Store is_branded as smallint; Eloquent boolean cast still works in PHP.
 */
return new class extends Migration
{
    private array $tables = ['occasion_social_posts', 'cgi_social_posts'];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            if (!Schema::hasTable($table) || !Schema::hasColumn($table, 'is_branded')) {
                continue;
            }

            if (DB::getDriverName() === 'pgsql') {
                DB::statement("ALTER TABLE {$table} ALTER COLUMN is_branded DROP DEFAULT");
                DB::statement("ALTER TABLE {$table} ALTER COLUMN is_branded TYPE smallint USING (is_branded::integer)");
                DB::statement("ALTER TABLE {$table} ALTER COLUMN is_branded SET DEFAULT 0");
            } else {
                Schema::table($table, function (Blueprint $blueprint) {
                    $blueprint->smallInteger('is_branded')->default(0)->change();
                });
            }
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $table) {
            if (!Schema::hasTable($table) || !Schema::hasColumn($table, 'is_branded')) {
                continue;
            }

            if (DB::getDriverName() === 'pgsql') {
                DB::statement("ALTER TABLE {$table} ALTER COLUMN is_branded DROP DEFAULT");
                DB::statement("ALTER TABLE {$table} ALTER COLUMN is_branded TYPE boolean USING (is_branded <> 0)");
                DB::statement("ALTER TABLE {$table} ALTER COLUMN is_branded SET DEFAULT false");
            } else {
                Schema::table($table, function (Blueprint $blueprint) {
                    $blueprint->boolean('is_branded')->default(false)->change();
                });
            }
        }
    }
};
