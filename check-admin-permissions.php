<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Get the admin user
$admin = \App\Models\User::where('email', 'admin@procurement.local')->first();

if (!$admin) {
    echo "Admin user not found!\n";
    exit;
}

echo "User: {$admin->full_name}\n";
echo "Email: {$admin->email}\n\n";

echo "Roles:\n";
foreach ($admin->roles as $role) {
    echo "  - {$role->name}\n";
}

echo "\nDirect Permissions:\n";
foreach ($admin->permissions as $permission) {
    echo "  - {$permission->name}\n";
}

echo "\nAll Permissions (including via roles):\n";
$allPermissions = $admin->getAllPermissions();
foreach ($allPermissions as $permission) {
    echo "  - {$permission->name}\n";
}

echo "\nChecking specific permissions:\n";
echo "Can 'departments.manage': " . ($admin->can('departments.manage') ? 'YES' : 'NO') . "\n";
echo "Can 'users.manage': " . ($admin->can('users.manage') ? 'YES' : 'NO') . "\n";

echo "Can 'system.configure': " . ($admin->can('system.configure') ? 'YES' : 'NO') . "\n";

