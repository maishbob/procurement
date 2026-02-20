<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

// Get Super Administrator role
$superAdmin = Role::where('name', 'Super Administrator')->first();

if (!$superAdmin) {
    echo "Super Administrator role not found!\n";
    exit;
}

// Create permissions if they don't exist
$permissions = [
    'departments.manage',
    'users.manage',

    'admin.roles',
    'system.configure',

];

foreach ($permissions as $permName) {
    $permission = Permission::firstOrCreate(
        ['name' => $permName],
        ['description' => ucfirst(str_replace('.', ' ', $permName)), 'module' => 'admin']
    );

    // Assign to Super Administrator if not already assigned
    if (!$superAdmin->hasPermissionTo($permName)) {
        $superAdmin->givePermissionTo($permName);
        echo "✓ Granted '{$permName}' to Super Administrator\n";
    } else {
        echo "  Already has '{$permName}'\n";
    }
}

// Also grant to the admin user directly
$admin = \App\Models\User::where('email', 'admin@procurement.local')->first();
if ($admin) {
    // Clear permission cache
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    echo "\n✓ Permission cache cleared\n";
    echo "✓ Admin user should now have access to Administration menu\n";
}

echo "\nDone!\n";
