<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$user = \App\Models\User::first();
auth()->setUser($user);

// Simulate requisition form data
$formData = [
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

echo "Testing requisition validation...\n";
echo str_repeat("=", 50) . "\n";

$validator = \Illuminate\Support\Facades\Validator::make($formData, [
    'department_id' => 'required|exists:departments,id',
    'purpose' => 'required|string|min:10',
    'justification' => 'required|string|min:10',
    'required_by_date' => 'required|date|after:today',
    'priority' => 'required|in:low,normal,high,urgent',
    'currency' => 'required|in:KES,USD,GBP,EUR',
    'budget_line_id' => 'nullable|exists:budget_lines,id',
    'is_emergency' => 'nullable|boolean',
    'is_single_source' => 'nullable|boolean',
    'items' => 'required|array|min:1',
    'items.*.description' => 'required|string',
    'items.*.specifications' => 'nullable|string',
    'items.*.quantity' => 'required|numeric|min:1',
    'items.*.unit_of_measure' => 'required|string',
    'items.*.estimated_unit_price' => 'required|numeric|min:0.01',
    'items.*.is_vatable' => 'nullable|boolean',
]);

if ($validator->fails()) {
    echo "✗ Validation failed:\n";
    foreach ($validator->errors()->all() as $error) {
        echo "  - $error\n";
    }
} else {
    echo "✓ Validation passed!\n";
    echo "\nValidated data structure:\n";
    $validated = $validator->validated();
    echo "  Department ID: {$validated['department_id']}\n";
    echo "  Purpose: {$validated['purpose']}\n";
    echo "  Required by: {$validated['required_by_date']}\n";
    echo "  Priority: {$validated['priority']}\n";
    echo "  Items count: " . count($validated['items']) . "\n";
    echo "  Total estimated: KES " . number_format(array_sum(array_map(function($item) {
        return $item['quantity'] * $item['estimated_unit_price'];
    }, $validated['items'])), 2) . "\n";
}
?>
