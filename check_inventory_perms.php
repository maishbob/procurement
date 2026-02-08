<?php

require_once __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;

$user = User::first();
if (!$user) {
    echo "No users found\n";
    exit(1);
}

echo "User: " . $user->email . "\n";
echo "Has inventory.manage: " . ($user->hasPermission('inventory.manage') ? 'Yes' : 'No') . "\n";

$permissions = $user->getAllPermissions()->pluck('name')->toArray();
echo "All permissions: " . implode(', ', $permissions) . "\n";
echo "Total permissions: " . count($permissions) . "\n";

// Show roles
$roles = $user->roles()->pluck('name')->toArray();
echo "Roles: " . implode(', ', $roles) . "\n";
