<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds commitment-accounting columns to budget_lines when they are absent.
 *
 * The authoritative schema (2014_10_12_000002) already includes all of
 * these columns. This migration is therefore a safe no-op on fresh
 * installations; it only adds the columns on legacy databases that were
 * created from the older 2024_01_01_000001 migration (which used
 * `annual_budget` / `available_budget` instead).
 *
 * Each `addColumn` is guarded by `hasColumn` so the migration is
 * idempotent regardless of which baseline migration was run first.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('budget_lines', function (Blueprint $table) {
            if (!Schema::hasColumn('budget_lines', 'budget_code')) {
                $table->string('budget_code')->nullable()->unique();
            }
            if (!Schema::hasColumn('budget_lines', 'description')) {
                $table->string('description')->nullable();
            }
            if (!Schema::hasColumn('budget_lines', 'category')) {
                $table->string('category')->nullable();
            }
            if (!Schema::hasColumn('budget_lines', 'allocated_amount')) {
                $table->decimal('allocated_amount', 15, 2)->default(0);
            }
            if (!Schema::hasColumn('budget_lines', 'committed_amount')) {
                $table->decimal('committed_amount', 15, 2)->default(0);
            }
            if (!Schema::hasColumn('budget_lines', 'spent_amount')) {
                $table->decimal('spent_amount', 15, 2)->default(0);
            }
        });
    }

    public function down(): void
    {
        // Only drop columns that this migration may have added on legacy DBs.
        $toDrop = [];
        foreach (['budget_code', 'description', 'category', 'allocated_amount', 'committed_amount', 'spent_amount'] as $col) {
            if (Schema::hasColumn('budget_lines', $col)) {
                $toDrop[] = $col;
            }
        }
        if ($toDrop) {
            Schema::table('budget_lines', fn (Blueprint $table) => $table->dropColumn($toDrop));
        }
    }
};
