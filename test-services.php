<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$user = \App\Models\User::first();
auth()->setUser($user);

// Test each service directly
$tests = [
    'PurchaseOrderService' => ['getAllPurchaseOrders'],
    'InvoiceService' => ['getAllInvoices'],
    'PaymentService' => ['getAllPayments'],
    'SupplierService' => ['getAllSuppliers'],
];

echo "Testing services:\n";
echo str_repeat("=", 60) . "\n";

foreach ($tests as $service => $methods) {
    try {
        $cls = "App\\Services\\$service";
        if (!class_exists($cls)) {
            echo "✗ $service: Class not found\n";
            continue;
        }
        
        $svc = app($cls);
        echo "✓ $service: Loaded\n";
        
        foreach ($methods as $method) {
            try {
                $result = $svc->$method();
                echo "  ✓ $method(): Returns " . class_basename(get_class($result)) . "\n";
            } catch (Exception $e) {
                echo "  ✗ $method(): " . $e->getMessage() . "\n";
            }
        }
    } catch (Exception $e) {
        echo "✗ $service: " . $e->getMessage() . "\n";
    }
}

// Check which services exist
echo "\nAvailable services:\n";
$services_dir = 'app/Services';
if (is_dir($services_dir)) {
    $files = scandir($services_dir);
    foreach ($files as $file) {
        if (ends_with($file, 'Service.php')) {
            echo "  - " . str_replace('.php', '', $file) . "\n";
        }
    }
}
