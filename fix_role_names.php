<?php

use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "--- Updating Role Names to Slugs ---\n";

$roles = Role::all();
foreach ($roles as $role) {
    if ($role->slug && $role->name !== $role->slug) {
        echo "Updating '{$role->name}' to '{$role->slug}'\n";
        $role->name = $role->slug;
        $role->save();
    }
}

// Clear cache
app()[PermissionRegistrar::class]->forgetCachedPermissions();

echo "Done.\n";
