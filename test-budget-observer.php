<?php

require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';

$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\BudgetLine;
use App\Models\CostCenter;

try {
    // Check for cost centers
    $totalCostCenters = CostCenter::count();
    echo "Total cost centers in database: " . $totalCostCenters . "\n";

    if ($totalCostCenters === 0) {
        echo "No cost centers found. Creating a test cost center...\n";
        $costCenter = CostCenter::create([
            'code' => 'TEST-' . time(),
            'name' => 'Test Cost Center',
            'department_id' => 1,
        ]);
        echo "Created cost center with ID: " . $costCenter->id . "\n";
    } else {
        $costCenter = CostCenter::first();
        echo "Using existing cost center: " . $costCenter->name . "\n";
    }

    // Get or create a department
    $department = \App\Models\Department::first();
    if (!$department) {
        $department = \App\Models\Department::create([
            'name' => 'Test Department',
            'slug' => 'test-dept',
        ]);
    }

    echo "\nCreating budget line...\n";
    $budgetLine = BudgetLine::create([
        'cost_center_id' => $costCenter->id,
        'department_id' => $department->id,
        'budget_code' => 'TEST-' . time(),
        'category' => 'Operational',
        'description' => 'Test budget line',
        'fiscal_year' => 2024,
        'allocated_amount' => 10000,
        'committed_amount' => 0,
        'spent_amount' => 0,
        'status' => 'draft',
    ]);

    echo "✓ Budget line created successfully with ID: " . $budgetLine->id . "\n";
    echo "✓ No parameter errors encountered!\n";
    echo "✓ Observer executed without 'Unknown named parameter' errors!\n";
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    echo "✗ File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
}
