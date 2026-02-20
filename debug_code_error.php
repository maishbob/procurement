<?php

use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Department;
use App\Models\BudgetLine;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

// Login a user
$user = User::first();
if (!$user) {
    echo "No users found!\n";
    exit;
}
Auth::login($user);
echo "Logged in as: " . $user->name . "\n";

// Test Views
$views = [
    'dashboard.index',
    'requisitions.index',
    'requisitions.create-simple',
    'budgets.index',
];

foreach ($views as $viewName) {
    echo "Testing view: $viewName ... ";
    try {
        // Mock data where necessary
        $data = [];
        if ($viewName == 'dashboard.index') {
            $data['stats'] = [];
            $data['recentRequisitions'] = [];
            $data['pendingActions'] = [];
            $data['budgetLines'] = [];
            $data['recentActivity'] = [];
        }
        if ($viewName == 'requisitions.index') {
            $data['requisitions'] = \App\Modules\Requisitions\Models\Requisition::paginate(10);
            $data['departments'] = Department::all();
        }
        if ($viewName == 'requisitions.create-simple') {
            $data['departments'] = Department::all();
        }
        if ($viewName == 'budgets.index') {
            $data['budgetLines'] = BudgetLine::paginate(15);
            $data['departments'] = Department::all();
            $data['fiscalYears'] = App\Models\FiscalYear::all();
            $data['summary'] = [
                'total_allocated' => 0,
                'total_committed' => 0,
                'total_spent' => 0,
                'total_available' => 0,
            ];
        }

        $content = View::make($viewName, $data)->render();
        echo "OK\n";
    } catch (\Throwable $e) {
        echo "FAILED\n";
        echo "Error: " . $e->getMessage() . "\n";
        echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
        // echo "Trace: " . $e->getTraceAsString() . "\n"; 
    }
}
