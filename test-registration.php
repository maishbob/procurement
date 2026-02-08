<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;

try {
    $user = User::create([
        'name' => 'Test User',
        'email' => 'test@school.edu',
        'password' => Hash::make('password123'),
        'is_active' => true,
    ]);

    echo "✅ User created successfully!\n";
    echo "ID: {$user->id}\n";
    echo "Name: {$user->name}\n";
    echo "Email: {$user->email}\n";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
