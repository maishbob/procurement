<?php

require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';

$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\BudgetLine;

$budget = BudgetLine::find(1);
if ($budget) {
    echo "✓ Budget 1 exists\n";
    echo "Code: " . $budget->budget_code . "\n";
    echo "Department: " . ($budget->department?->name ?? 'N/A') . "\n";
    echo "Fiscal Year: " . $budget->fiscal_year . "\n";
    echo "Allocated: " . $budget->allocated_amount . "\n";
    echo "Status: " . $budget->status . "\n";
} else {
    echo "✗ Budget 1 not found\n";
    $count = BudgetLine::count();
    echo "Total budgets: " . $count . "\n";
    if ($count > 0) {
        echo "\nAvailable budget IDs:\n";
        BudgetLine::pluck('id', 'budget_code')->take(5)->each(function ($id, $code) {
            echo "  ID $id: $code\n";
        });
    }
}
