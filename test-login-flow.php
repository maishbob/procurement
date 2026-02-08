<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

echo "=== Testing Login Flow ===\n\n";

// Step 1: Get login page and extract CSRF token
echo "1. Getting login page...\n";
$getRequest = Illuminate\Http\Request::create('/login', 'GET');
$getResponse = $kernel->handle($getRequest);
echo "   Status: " . $getResponse->status() . "\n";

preg_match('/<input[^>]+name="_token"[^>]*value="([^"]+)"/', $getResponse->getContent(), $matches);
$csrfToken = $matches[1] ?? 'NO_TOKEN_FOUND';
echo "   CSRF Token: " . substr($csrfToken, 0, 20) . "...\n\n";

// Step 2: Check if test user exists
echo "2. Checking test user...\n";
try {
    $user = \App\Models\User::where('email', 'test@school.edu')->first();
    if ($user) {
        echo "   ✓ User exists: " . $user->name . " (ID: " . $user->id . ")\n";
        echo "   Active: " . ($user->is_active ? 'Yes' : 'No') . "\n\n";
    } else {
        echo "   ✗ User not found!\n";
        echo "   Creating test user...\n";
        $user = \App\Models\User::create([
            'name' => 'Test User',
            'email' => 'test@school.edu',
            'password' => \Illuminate\Support\Facades\Hash::make('password123'),
            'is_active' => true,
        ]);
        echo "   ✓ User created (ID: " . $user->id . ")\n\n";
    }
} catch (Exception $e) {
    echo "   ✗ Error: " . $e->getMessage() . "\n\n";
}

// Step 3: Test login POST
echo "3. Testing login POST...\n";
$postRequest = Illuminate\Http\Request::create('/login', 'POST', [
    'email' => 'test@school.edu',
    'password' => 'password123',
    '_token' => $csrfToken,
]);

// Simulate session
$postRequest->setLaravelSession(
    $app->make('session.store')
);

try {
    $postResponse = $kernel->handle($postRequest);
    echo "   Status: " . $postResponse->status() . "\n";

    if ($postResponse->status() === 302) {
        echo "   Redirect to: " . $postResponse->headers->get('Location') . "\n";
        echo "   ✓ Login successful!\n\n";

        // Step 4: Test accessing dashboard
        echo "4. Testing dashboard access...\n";
        $dashRequest = Illuminate\Http\Request::create('/dashboard', 'GET');
        $dashRequest->setLaravelSession($postRequest->session());

        $dashResponse = $kernel->handle($dashRequest);
        echo "   Status: " . $dashResponse->status() . "\n";

        if ($dashResponse->status() === 200) {
            echo "   ✓ Dashboard accessible!\n";
        } else {
            echo "   ✗ Dashboard returned: " . $dashResponse->status() . "\n";
        }
    } elseif ($postResponse->status() === 422) {
        echo "   ✗ Validation errors:\n";
        $content = json_decode($postResponse->getContent(), true);
        if (isset($content['errors'])) {
            foreach ($content['errors'] as $field => $errors) {
                echo "     - $field: " . implode(', ', $errors) . "\n";
            }
        }
    } else {
        echo "   ✗ Unexpected response\n";
        echo "   Content preview: " . substr($postResponse->getContent(), 0, 200) . "\n";
    }
} catch (Exception $e) {
    echo "   ✗ Error: " . $e->getMessage() . "\n";
    echo "   File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}

echo "\n=== Test Complete ===\n";
