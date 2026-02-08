<?php
require __DIR__ . '/vendor/autoload.php';

$app = require __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Http\Request;
use Illuminate\Foundation\Testing\Concerns\MakesHttpRequests;

// Get a user from database for authentication
$user = \App\Models\User::first();

if (!$user) {
    echo "✗ No users found in database\n";
    exit(1);
}

echo "Testing with user: {$user->email}\n\n";

// Test requisitions page (requires authentication)
try {
    // Create a request to the requisitions index
    $request = Request::create('/requisitions', 'GET');
    $request->setUserResolver(function () use ($user) {
        return $user;
    });

    // Handle the request
    $kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);
    $response = $kernel->handle($request);
    
    $statusCode = $response->getStatusCode();
    
    if ($statusCode === 200) {
        echo "✓ Requisitions page loads successfully (Status: $statusCode)\n";
    } else {
        echo "✗ Requisitions page error (Status: $statusCode)\n";
        // Try to find error in logs
        $logTail = shell_exec('tail -50 storage/logs/laravel.log 2>/dev/null | grep -i error | head -5');
        if ($logTail) {
            echo "\nRecent error:\n$logTail\n";
        }
    }
    
} catch (\Exception $e) {
    echo "✗ Exception loading requisitions:\n";
    echo get_class($e) . ": {$e->getMessage()}\n";
    echo "File: {$e->getFile()} (Line {$e->getLine()})\n";
}

// Test other key pages
$pages = [
    '/purchase-orders' => 'Purchase Orders',
    '/procurement' => 'Procurement',
    '/suppliers' => 'Suppliers',
];

echo "\nTesting other pages:\n";

foreach ($pages as $url => $label) {
    try {
        $request = Request::create($url, 'GET');
        $request->setUserResolver(function () use ($user) {
            return $user;
        });
        
        $response = $kernel->handle($request);
        $statusCode = $response->getStatusCode();
        
        if ($statusCode === 200) {
            echo "✓ $label: OK\n";
        } else {
            echo "✗ $label: Status $statusCode\n";
        }
    } catch (\Exception $e) {
        echo "✗ $label: {$e->getMessage()}\n";
    }
}

echo "\n✓ All tests completed!\n";
