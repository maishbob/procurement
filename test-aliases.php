<?php
// Test if we can load the app without errors
require __DIR__ . '/vendor/autoload.php';

try {
    $app = require __DIR__ . '/bootstrap/app.php';
    $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
    
    // Try to load requisition model
    $model = new \App\Models\Requisition();
    echo "✓ Requisition model loaded successfully\n";
    
    // Check if it's an instance of the right class
    if($model instanceof \App\Modules\Requisitions\Models\Requisition) {
        echo "✓ Requisition is correctly aliased to module model\n";
    }
    
    // Try other models
    $models = [
        'PurchaseOrder' => '\App\Modules\PurchaseOrders\Models\PurchaseOrder',
        'Supplier' => '\App\Modules\Suppliers\Models\Supplier',
        'Invoice' => '\App\Modules\Finance\Models\SupplierInvoice',
        'Payment' => '\App\Modules\Finance\Models\Payment',
        'GRN' => '\App\Modules\GRN\Models\GoodsReceivedNote',
    ];
    
    foreach ($models as $name => $fqn) {
        $class = "\\App\\Models\\" . $name;
        $model = new $class();
        if($model instanceof $fqn) {
            echo "✓ $name alias working\n";
        } else {
            echo "✗ $name alias NOT working\n";
        }
    }
    
    echo "\n All model aliases configured successfully!\n";
} catch (\Throwable $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    echo "  File: " . $e->getFile() . " (Line " . $e->getLine() . ")\n";
    exit(1);
}
