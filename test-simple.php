<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$user = \App\Models\User::first();
auth()->setUser($user);

echo "Testing individual pages:\n";

// Test requisitions (should work)
try {
    $controller = new \App\Http\Controllers\RequisitionController(
        app(\App\Services\RequisitionService::class),
        app(\App\Services\ApprovalService::class)
    );
    $request = \Illuminate\Http\Request::create('/requisitions', 'GET');
    $response = $controller->index($request);
    echo "✓ Requisitions: OK\n";
} catch (\Exception $e) {
    echo "✗ Requisitions: " . $e->getMessage() . "\n";
}

// Test suppliers
try {
    $controller = new \App\Http\Controllers\SupplierController(
        app(\App\Services\SupplierService::class)
    );
    $request = \Illuminate\Http\Request::create('/suppliers', 'GET');
    $response = $controller->index($request);
    echo "✓ Suppliers: OK\n";
} catch (\Exception $e) {
    echo "✗ Suppliers: " . substr($e->getMessage(), 0, 80) . "\n";
}

// Test inventory
try {
    $controller = new \App\Http\Controllers\InventoryController(
        app(\App\Services\InventoryService::class)
    );
    $request = \Illuminate\Http\Request::create('/inventory', 'GET');
    $response = $controller->index($request);
    echo "✓ Inventory: OK\n";
} catch (\Exception $e) {
    echo "✗ Inventory: " . substr($e->getMessage(), 0, 80) . "\n";
}
?>
