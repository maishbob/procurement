<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$user = \App\Models\User::first();
auth()->setUser($user);

// Test all main pages
$pages = [
    'requisitions' => 'RequisitionController@index',
    'purchase-orders' => 'PurchaseOrderController@index',
    'procurement' => 'ProcurementController@index',
    'suppliers' => 'SupplierController@index',
    'invoices' => 'InvoiceController@index',
    'payments' => 'PaymentController@index',
    'grn' => 'GRNController@index',
    'inventory' => 'InventoryController@index',
    'reports' => 'ReportController@index',
];

echo "Testing all main pages:\n";
echo str_repeat("=", 50) . "\n";

$failed = [];
$passed = [];

foreach ($pages as $route => $controller) {
    try {
        $request = \Illuminate\Http\Request::create("/$route", 'GET');
        auth()->setUser($user);
        
        // Use the router to dispatch
        $response = app('router')->dispatch($request);
        
        if ($response->getStatusCode() === 200) {
            echo "✓ $route: OK\n";
            $passed[] = $route;
        } else {
            echo "✗ $route: Status {$response->getStatusCode()}\n";
            $failed[] = $route;
        }
    } catch (\Exception $e) {
        echo "✗ $route: {$e->getMessage()}\n";
        $failed[] = $route;
    }
}

echo str_repeat("=", 50) . "\n";
echo "Passed: " . count($passed) . "/" . count($pages) . "\n";
if ($failed) {
    echo "Failed: " . implode(", ", $failed) . "\n";
} else {
    echo "✓ All pages working!\n";
}
