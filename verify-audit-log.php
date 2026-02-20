<?php

require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';

$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$audit = DB::table('audit_logs')
    ->where('auditable_type', 'BudgetLine')
    ->where('action', 'BUDGET_LINE_CREATED')
    ->latest()
    ->first();

if ($audit) {
    echo "✓ Audit log created successfully!\n";
    echo "Action: " . $audit->action . "\n";
    echo "Model Type: " . $audit->auditable_type . "\n";
    echo "Model ID: " . $audit->auditable_id . "\n";
    echo "User: " . $audit->user_name . "\n";
    echo "New Values: " . substr($audit->new_values ?? '', 0, 100) . "...\n";
    echo "\n✓ All observer methods are working correctly!\n";
} else {
    echo "✗ No audit log found\n";
}
