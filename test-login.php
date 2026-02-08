<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Auth;

try {
    $credentials = [
        'email' => 'test@school.edu',
        'password' => 'password123'
    ];

    if (Auth::attempt($credentials)) {
        echo "✅ Login successful!\n";
        echo "User: " . Auth::user()->name . "\n";
        echo "Email: " . Auth::user()->email . "\n";
    } else {
        echo "❌ Login failed - invalid credentials\n";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
