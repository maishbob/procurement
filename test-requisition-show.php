<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Requisition;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

// Find a user to authenticate as
$user = User::first();
if (!$user) {
    echo "❌ No users found in database\n";
    exit;
}

Auth::login($user);

// Find a requisition to test with
$requisition = Requisition::with(['items', 'approvals', 'auditLogs'])->first();

if (!$requisition) {
    echo "❌ No requisitions found in database\n";
    exit;
}

echo "✓ Testing requisition show page data loading\n";
echo "\n";
echo "Requisition: {$requisition->requisition_number}\n";
echo "Title: {$requisition->title}\n";
echo "Status: {$requisition->status}\n";
echo "\n";

echo "Items: " . $requisition->items->count() . "\n";
foreach ($requisition->items as $item) {
    echo "  - {$item->description}: {$item->quantity} {$item->unit_of_measure} @ {$requisition->currency} " . number_format($item->estimated_unit_price, 2) . "\n";
}
echo "\n";

echo "Approvals: " . $requisition->approvals->count() . "\n";
echo "\n";

echo "Audit Logs: " . $requisition->auditLogs->count() . "\n";
if ($requisition->auditLogs->count() > 0) {
    foreach ($requisition->auditLogs as $log) {
        echo "  - {$log->created_at->format('Y-m-d H:i:s')} - {$log->action} by {$log->user_name}\n";
    }
} else {
    echo "  (No audit logs yet)\n";
}
echo "\n";

echo "✓ All requisition relationships loaded successfully!\n";
