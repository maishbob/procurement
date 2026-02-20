<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Originally attempted to change `available_amount` using Doctrine DBAL.
 * Two problems with the original:
 *   1. Doctrine DBAL is not installed, so ->change() throws at runtime.
 *   2. The column is named `available_budget` in the base migration, not
 *      `available_amount` — `available_amount` is a computed PHP accessor.
 *
 * This migration is therefore a safe no-op: the `available_budget` column
 * already exists and has no constraints that need changing.  The BudgetLine
 * model exposes `available_amount` as a virtual attribute calculated in PHP.
 */
return new class extends Migration
{
    public function up(): void
    {
        // No-op: column `available_amount` does not exist in the schema.
        // `available_amount` is a virtual accessor on the BudgetLine model.
    }

    public function down(): void
    {
        // No-op.
    }
};
