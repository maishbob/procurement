<?php

use App\Models\User;
use Spatie\Permission\Models\Role;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\DB;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "--- Fixing Permissions ---\n";

// 1. Run the RolesAndPermissionsSeeder
echo "Running RolesAndPermissionsSeeder...\n";
$seeder = new RolesAndPermissionsSeeder();
$seeder->run();
echo "Roles and Permissions seeded.\n";

// 2. Assign Super Admin role to the main user
$email = 'admin@procurement.local';
$user = User::where('email', $email)->first();

if (!$user) {
    echo "User {$email} not found! Creating default admin...\n";
    $user = User::create([
        'name' => 'System Administrator',
        'email' => $email,
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
        'is_active' => true,
    ]);
}

echo "Assigning 'Super Administrator' role to {$user->name} ({$user->email})...\n";

// Check if role exists
$role = Role::where('name', 'Super Administrator')->first();
if (!$role) {
    echo "Error: 'Super Administrator' role not found even after seeding!\n";
    exit(1);
}

$user->assignRole($role);

echo "Role assigned successfully.\n";

// Verify
echo "\n--- Verification ---\n";
echo "User roles: " . implode(', ', $user->getRoleNames()->toArray()) . "\n";
echo "User has 'manage_annual_procurement_plans'? " . ($user->can('manage_annual_procurement_plans') ? 'YES' : 'NO') . "\n";
echo "User has 'requisitions.view'? " . ($user->can('requisitions.view') ? 'YES' : 'NO') . "\n";
