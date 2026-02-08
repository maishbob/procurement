<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';

$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Test requisitions index route
try {
    $request = \Illuminate\Http\Request::create('/requisitions', 'GET');
    $response = $app->handle($request);
    
    if($response->getStatusCode() === 200) {
        echo "✓ Requisitions page loaded successfully (Status: 200)\n";
    } else {
        echo "✗ Requisitions page error (Status: " . $response->getStatusCode() . ")\n";
        echo $response->getContent();
    }
} catch (\Throwable $e) {
    echo "✗ Requisitions page exception:\n";
    echo get_class($e) . ": " . $e->getMessage() . "\n";
    echo "\nFile: " . $e->getFile() . " (Line " . $e->getLine() . ")\n";
}
