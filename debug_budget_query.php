<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

$kernel->bootstrap();

use App\Models\BudgetLine;
use App\Models\Department;
use App\Models\FiscalYear;

// ID 16 is Academics KG
$deptId = 16;
$inputFiscalYear = '2026'; // This is what comes from the frontend 'now()->year'

echo "Testing Budget Query for Dept ID: $deptId, Fiscal Year Input: '$inputFiscalYear'\n";

// --- Logic from Controller ---
$fiscalYear = $inputFiscalYear;

if (!$fiscalYear || is_numeric($fiscalYear)) {
     $activeFiscalYear = FiscalYear::where('is_active', true)->first();
     if ($activeFiscalYear) {
         echo "Found Active Fiscal Year: {$activeFiscalYear->name}\n";
         $fiscalYear = $activeFiscalYear->name;
     } elseif (is_numeric($fiscalYear)) {
         $fiscalYear = 'FY ' . $fiscalYear;
     } else {
         $fiscalYear = 'FY ' . date('Y');
     }
}

echo "Resolved Fiscal Year: '$fiscalYear'\n";

// Query
$query = BudgetLine::where('department_id', $deptId)
    ->where('fiscal_year', $fiscalYear) // replicating scopeForFiscalYear
    ->where('status', 'approved');

echo "SQL: " . $query->toSql() . "\n";
echo "Bindings: " . implode(', ', $query->getBindings()) . "\n";

$budgets = $query->get();

echo "Found " . $budgets->count() . " budgets.\n";

foreach ($budgets as $b) {
    echo " - Budget ID: {$b->id}, Code: {$b->budget_code}, Status: {$b->status}, Active: {$b->is_active}\n";
}

// Check if any exist without status check
$allForDept = BudgetLine::where('department_id', $deptId)->where('fiscal_year', $fiscalYear)->get();
echo "Total budgets for Dept/FY (ignoring status): " . $allForDept->count() . "\n";
if ($allForDept->count() > 0) {
    foreach ($allForDept as $b) {
        echo " -> Candidate: {$b->id} has status: '{$b->status}'\n";
    }
}
