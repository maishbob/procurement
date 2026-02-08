<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$user = \App\Models\User::first();

$service = new \App\Services\RequisitionService();

$data = [
    'department_id' => 1,
    'purpose' => 'Purchase of office stationery and supplies',
    'justification' => 'Required for day-to-day operations of the department',
    'required_by_date' => date('Y-m-d', strtotime('+7 days')),
    'priority' => 'normal',
    'currency' => 'KES',
    'is_emergency' => false,
    'is_single_source' => false,
    'items' => [
        [
            'description' => 'A4 Paper Reams',
            'specifications' => '80gsm white paper',
            'quantity' => 10,
            'unit_of_measure' => 'Ream',
            'estimated_unit_price' => 500.00,
            'is_vatable' => true,
        ],
        [
            'description' => 'Ballpoint Pens',
            'specifications' => 'Blue ink',
            'quantity' => 50,
            'unit_of_measure' => 'Piece',
            'estimated_unit_price' => 10.00,
            'is_vatable' => true,
        ]
    ]
];

echo "Testing requisition creation...\n";
echo str_repeat("=", 60) . "\n";

try {
    $requisition = $service->createRequisition($data, $user);
    
    echo "✓ Requisition created successfully!\n";
    echo "  Requisition Number: {$requisition->requisition_number}\n";
    echo "  Title: {$requisition->title}\n";
    echo "  Department: {$requisition->department_id}\n";
    echo "  Requested By: {$requisition->requested_by}\n";
    echo "  Status: {$requisition->status}\n";
    echo "  Estimated Total: KES " . number_format($requisition->estimated_total, 2) . "\n";
    echo "  Items: " . $requisition->items->count() . "\n";
    
    echo "\nItems:\n";
    foreach ($requisition->items as $item) {
        echo "  {$item->line_number}. {$item->description} - {$item->quantity} {$item->unit_of_measure} @ KES " . 
             number_format($item->estimated_unit_price, 2) . " = KES " . 
             number_format($item->estimated_total_price, 2) . "\n";
    }
    
} catch (\Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    echo "\n" . $e->getTraceAsString() . "\n";
}
?>
