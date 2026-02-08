<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$users = \App\Models\User::all();
echo "Available users:\n";
foreach($users as $u) {
    echo "  - {$u->email}\n";
}

// Now test loading requisitions page with auth
echo "\nTesting requisitions page with actual request emulation...\n";

$user = $users->first();
if($user) {
    try {
        // Simulate an authenticated request
        auth()->setUser($user);
        
        // Try to manually call the controller method
        $controller = new \App\Http\Controllers\RequisitionController(
            app(\App\Services\RequisitionService::class),
            app(\App\Services\ApprovalService::class)
        );
        
        $request = \Illuminate\Http\Request::create('/requisitions', 'GET');
        $response = $controller->index($request);
        
        echo "✓ Controller executed without errors\n";
        echo "Response type: " . get_class($response) . "\n";
        
        if ($response instanceof \Illuminate\View\View) {
            echo "✓ View rendered: " . $response->getName() . "\n";
        }
    } catch (\Exception $e) {
        echo "✗ Error: " . $e->getMessage() . "\n";
        echo "  File: " . $e->getFile() . " (Line " . $e->getLine() . ")\n";
    }
}
