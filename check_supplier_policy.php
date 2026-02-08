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
echo "Has suppliers.create permission: " . ($user->hasPermission('suppliers.create') ? 'Yes' : 'No') . "\n";

// Show roles
$roles = $user->roles()->pluck('id', 'name')->toArray();
echo "Roles: " . json_encode($roles, JSON_PRETTY_PRINT) . "\n";

// Check hasAnyRole
$roleNames = $user->roles()->pluck('name')->toArray();
echo "Role names: " . implode(', ', $roleNames) . "\n";

echo "hasAnyRole(['procurement_officer', 'admin', 'super_admin']): " . 
    ($user->hasAnyRole(['procurement_officer', 'admin', 'super_admin']) ? 'Yes' : 'No') . "\n";
