<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$user = \App\Models\User::first();
auth()->setUser($user);

try {
    $controller = new \App\Http\Controllers\RequisitionController(
        app(\App\Services\RequisitionService::class),
        app(\App\Services\ApprovalService::class)
    );
    
    $response = $controller->create();
    
    if ($response instanceof \Illuminate\View\View) {
        echo "✓ Create requisition view loaded\n";
        echo "  Data passed to view:\n";
        $data = $response->getData();
        foreach (['departments', 'categories', 'suppliers'] as $key) {
            if (isset($data[$key])) {
                echo "  ✓ $key: " . count($data[$key]) . " items\n";
            } else {
                echo "  ✗ $key: NOT PASSED\n";
            }
        }
        
        if (isset($data['departments']) && count($data['departments']) > 0) {
            echo "\nDepartments in view:\n";
            foreach ($data['departments'] as $dept) {
                echo "  - {$dept->name}\n";
            }
        }
    }
} catch (\Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}
?>
