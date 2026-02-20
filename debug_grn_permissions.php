<?php

use App\Models\User;
use App\Modules\GRN\Models\GoodsReceivedNote;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

// Simulate login for the first user (usually admin/super admin)
$user = User::first(); 
Auth::login($user);

echo "--- Debugging GRN Permissions ---\n";
echo "User: " . $user->name . " (ID: " . $user->id . ")\n";

// Check Roles
echo "Roles: " . implode(', ', $user->getRoleNames()->toArray()) . "\n";

// Check Permissions
$permission = 'grn.create';
echo "Has Permission '{$permission}': " . ($user->hasPermission($permission) ? 'YES' : 'NO') . "\n";

// Check Gate
echo "Can Create GRN (Gate): " . (Gate::check('create', GoodsReceivedNote::class) ? 'YES' : 'NO') . "\n";

// Inspect Policy Logic Manually
$roles = ['store_manager', 'procurement_officer', 'admin', 'super_admin'];
$hasRole = $user->hasAnyRole($roles);
echo "Has Required Role: " . ($hasRole ? 'YES' : 'NO') . "\n";

echo "Policy Condition (Role && Permission): " . (($hasRole && $user->hasPermission($permission)) ? 'PASS' : 'FAIL') . "\n";

// Check for Super Admin bypass in AuthServiceProvider (if any)
echo "Super Admin Bypass? ";
if ($user->hasRole('super_admin')) {
    echo "User is Super Admin. ";
}
echo "\n";
