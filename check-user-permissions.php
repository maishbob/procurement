<?php

require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';

$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Auth;

// Get the first user or create a test user
$user = User::first();

if ($user) {
    echo "Current/First User in Database:\n";
    echo "Name: " . $user->name . "\n";
    echo "Email: " . $user->email . "\n";
    echo "Department ID: " . ($user->department_id ?? 'None') . "\n";
    echo "\nRoles: " . ($user->roles->pluck('name')->join(', ') ?? 'None') . "\n";
    echo "Permissions: " . ($user->permissions->pluck('name')->join(', ') ?? 'None') . "\n";
} else {
    echo "No users found in database\n";
}

// Check budget department
echo "\n\nBudget ID 1 Information:\n";
$budget = \App\Models\BudgetLine::find(1);
if ($budget) {
    echo "Department ID: " . $budget->department_id . "\n";
    echo "Cost Center ID: " . ($budget->cost_center_id ?? 'None') . "\n";
    if ($budget->costCenter) {
        echo "Department (via Cost Center): " . $budget->costCenter->department_id . "\n";
    }
    echo "Department: " . ($budget->department?->name ?? 'N/A') . "\n";
}
