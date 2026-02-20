<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

$kernel->bootstrap();

use App\Http\Controllers\BudgetController;
use App\Models\Department;
use Illuminate\Http\Request;

try {
    $controller = $app->make(BudgetController::class);
    $dept = Department::find(16);
    
    if (!$dept) {
        die("Department 16 not found.\n");
    }

    echo "Testing Controller with Dept: " . $dept->name . "\n";

    // Simulate request with '2026' (string)
    $request = new Request(['fiscal_year' => '2026']);
    
    echo "Calling getDepartmentBudgets...\n";
    $response = $controller->getDepartmentBudgets($dept, $request); // This returns a JsonResponse

    echo "Status: " . $response->getStatusCode() . "\n";
    echo "Content: " . $response->getContent() . "\n";

} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
