<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$user = \App\Models\User::first();
auth()->setUser($user);

echo "Checking permissions database...\n";

// Get all permissions
$permissions = \Spatie\Permission\Models\Permission::all();
echo "Total permissions: " . $permissions->count() . "\n\n";

// Group by prefix
$grouped = $permissions->groupBy(function($p) {
    return explode('_', $p->name)[0];
})->map->pluck('name');

foreach ($grouped as $prefix => $perms) {
    echo "├─ $prefix: " . count($perms) . "\n";
    foreach ($perms->take(3) as $p) {
        echo "│  - $p\n";
    }
    if (count($perms) > 3) {
        echo "│  ... " . (count($perms) - 3) . " more\n";
    }
}

echo "\nLooking for specific missing permissions:\n";
$looking_for = ['view_suppliers', 'view_inventory', 'view_purchase_orders', 'view_requisitions'];
foreach ($looking_for as $perm) {
    $exists = $permissions->where('name', $perm)->first();
    echo $exists ? "✓ $perm\n" : "✗ $perm\n";
}
?>
