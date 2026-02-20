<?php

use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "--- User Roles & Permissions Debug ---\n";

// Get the first user (likely the admin)
$user = User::first();

if (!$user) {
    echo "No users found in database.\n";
    exit;
}

echo "User: {$user->name} ({$user->email})\n";
echo "Roles:\n";
foreach ($user->roles as $role) {
    echo "- {$role->name}\n";
    echo "  Permissions:\n";
    foreach ($role->permissions as $perm) {
        echo "  * {$perm->name}\n";
    }
}

echo "\n--- All Roles in DB ---\n";
foreach (Role::all() as $role) {
    echo "- {$role->name} (" . $role->permissions->count() . " permissions)\n";
}

echo "\n--- Sample Permissions (First 10) ---\n";
foreach (Permission::limit(10)->get() as $perm) {
    echo "- {$perm->name}\n";
}
