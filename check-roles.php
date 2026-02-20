<?php

require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';

$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Role;

$user = User::first();

echo "User Roles with Details:\n";
if ($user && $user->roles->count() > 0) {
    $user->roles->each(function($role) {
        echo "  - " . $role->name . " (slug: " . $role->slug . ")\n";
    });
} else {
    echo "  No roles assigned\n";
}

echo "\nAvailable Roles in Database:\n";
Role::get()->each(function($role) {
    echo "  - " . $role->name . " (slug: " . $role->slug . ")\n";
});

echo "\nChecking Authorization:\n";
if ($user) {
    echo "hasAnyRole(['finance_manager']): " . ($user->hasAnyRole(['finance_manager']) ? 'YES' : 'NO') . "\n";
    echo "hasAnyRole(['admin']): " . ($user->hasAnyRole(['admin']) ? 'YES' : 'NO') . "\n";
    echo "hasAnyRole(['super_admin']): " . ($user->hasAnyRole(['super_admin']) ? 'YES' : 'NO') . "\n";
    echo "hasAnyRole(['Super Administrator']): " . ($user->hasAnyRole(['Super Administrator']) ? 'YES' : 'NO') . "\n";
    
    echo "\nUser role names: ";
    echo $user->roles->pluck('name')->join(', ') . "\n";
    
    echo "User role slugs: ";
    echo $user->roles->pluck('slug')->join(', ') . "\n";
}
