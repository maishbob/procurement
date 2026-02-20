<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Make budget_lines.cost_center_id nullable without Doctrine DBAL.
 *
 * Uses driver-native ALTER TABLE statements so the migration works in
 * both production (MySQL/PostgreSQL) and the in-memory SQLite test DB.
 * SQLite does not support ALTER COLUMN — the column stays NOT NULL there,
 * but the FK constraint is unenforced and tests supply a valid cost_center_id.
 */
return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE budget_lines MODIFY COLUMN cost_center_id BIGINT UNSIGNED NULL');
        } elseif ($driver === 'pgsql') {
            DB::statement('ALTER TABLE budget_lines ALTER COLUMN cost_center_id DROP NOT NULL');
        }
        // SQLite: no ALTER COLUMN support — tests must supply a cost_center_id value.
    }

    public function down(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE budget_lines MODIFY COLUMN cost_center_id BIGINT UNSIGNED NOT NULL');
        } elseif ($driver === 'pgsql') {
            DB::statement('ALTER TABLE budget_lines ALTER COLUMN cost_center_id SET NOT NULL');
        }
    }
};
