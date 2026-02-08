<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

echo "--- Testing User Creation and Authentication ---\n";

// Test Case 1: With Hash::make (Current Implementation)
echo "\nTest Case 1: Creating user with Hash::make()\n";
$email1 = 'test1_' . time() . '@example.com';
$password = 'password123';

try {
    $user1 = User::create([
        'name' => 'Test User 1',
        'email' => $email1,
        'password' => Hash::make($password),
        'is_active' => true,
    ]);

    echo "User 1 created. ID: " . $user1->id . "\n";
    echo "Stored Password Hash: " . $user1->password . "\n";

    if (Auth::attempt(['email' => $email1, 'password' => $password])) {
        echo "LOGIN SUCCESS for User 1\n";
    } else {
        echo "LOGIN FAILED for User 1 (Double Hashing Issue Confirmed)\n";
    }

} catch (Exception $e) {
    echo "Error creating User 1: " . $e->getMessage() . "\n";
}

// Test Case 2: Without Hash::make (Proposed Fix)
echo "\nTest Case 2: Creating user WITHOUT Hash::make()\n";
$email2 = 'test2_' . time() . '@example.com';

try {
    $user2 = User::create([
        'name' => 'Test User 2',
        'email' => $email2,
        'password' => $password, // Passing plain text
        'is_active' => true,
    ]);

    echo "User 2 created. ID: " . $user2->id . "\n";
    echo "Stored Password Hash: " . $user2->password . "\n";

    if (Auth::attempt(['email' => $email2, 'password' => $password])) {
        echo "LOGIN SUCCESS for User 2\n";
    } else {
        echo "LOGIN FAILED for User 2\n";
    }

} catch (Exception $e) {
    echo "Error creating User 2: " . $e->getMessage() . "\n";
}
