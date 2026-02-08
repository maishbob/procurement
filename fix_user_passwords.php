<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;

echo "--- Fixing User Passwords ---\n";

$users = User::all();

foreach ($users as $user) {
    // Skip the test user I just created correctly (User 4) if needed, but safe to reset all
    // to ensure consistency.
    
    // Deleting my test users
    if (str_contains($user->email, 'test1_') || str_contains($user->email, 'test2_')) {
        echo "Deleting test user: {$user->email}\n";
        $user->forceDelete(); // Use forceDelete since SoftDeletes is enabled
        continue;
    }

    echo "Updating password for user: {$user->email} (ID: {$user->id})\n";
    
    // Setting password to 'password'. The 'hashed' cast in User model will handle hashing.
    $user->password = 'password';
    $user->save();
}

echo "All users processed.\n";
