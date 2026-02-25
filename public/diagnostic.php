<?php
/**
 * Diagnostic script to check server configuration, permissions, and basic Laravel bootstrapping
 * Instructions: Place this file in the `public` or `public_html` directory of your cPanel and access it via browser.
 */

// Enable error reporting to catch fatal errors before Laravel boots
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: text/plain');

echo "============================================\n";
echo "   PRE-FLIGHT DIAGNOSTIC CHECKER\n";
echo "============================================\n\n";

// 1. Check PHP Version
echo "1. PHP Version Check:\n";
echo "   Current Version: " . PHP_VERSION . "\n";
if (version_compare(PHP_VERSION, '8.1.0', '>=')) {
    echo "   Status: OK (>= 8.1.0 required)\n";
} else {
    echo "   Status: ERROR (PHP 8.1+ is required for this application)\n";
    die("\nDiagnostics Halted: Please upgrade your cPanel PHP version for this domain.");
}
echo "\n";


// 2. Check Directory Structure and Basic Paths
echo "2. Path Resolution Check:\n";
$publicPath = __DIR__;
echo "   Current Script Path: " . $publicPath . "\n";

// Determine where the assumed base path is based on typical cPanel setups 
// (e.g., public_html -> home/domain/procurement or similar)
$assumedBasePath = realpath($publicPath . '/../'); 
$vendorPath = $assumedBasePath . '/vendor';

echo "   Assumed Base Path: " . $assumedBasePath . "\n";
if (is_dir($vendorPath)) {
    echo "   Vendor Path: " . $vendorPath . "\n";
    echo "   Status: OK (Found vendor directory)\n";
} else {
    // If we're in public_html and the app is in /procurement
    echo "   Vendor Path: " . $vendorPath . " (NOT FOUND)\n";
    
    // Look for alternative paths based on the user's setup description
    $homeDir = preg_replace('/\/public_html[^\/]*$/', '', $publicPath);
    $alternativeAppPath = $homeDir . '/procurement';
    if (is_dir($alternativeAppPath . '/vendor')) {
         echo "   --> FOUND alternative valid base path at: " . $alternativeAppPath . "\n";
         $assumedBasePath = $alternativeAppPath;
         $vendorPath = $alternativeAppPath . '/vendor';
    } else {
         echo "   Status: ERROR (Could not locate the /vendor directory. Did you run composer install or upload the vendor folder?)\n";
    }
}
echo "\n";


// 3. File Permissions Check
echo "3. File Permissions / Access Check:\n";
$envPath = $assumedBasePath . '/.env';
$storagePath = $assumedBasePath . '/storage';

if (file_exists($envPath)) {
    echo "   .env File: FOUND\n";
    if (is_readable($envPath)) {
        echo "   .env Readable: YES\n";
    } else {
        echo "   .env Readable: NO (Check permissions on .env file, should be 0644)\n";
    }
} else {
    echo "   .env File: MISSING\n";
}

if (is_dir($storagePath)) {
    echo "   Storage Directory: FOUND\n";
    if (is_writable($storagePath)) {
        echo "   Storage Writable: YES\n";
    } else {
        echo "   Storage Writable: NO (Check permissions, storage folder requires 0755 or 0775 permissions)\n";
    }
} else {
    echo "   Storage Directory: MISSING\n";
}
echo "\n";


// 4. Test Autoloader Boot
echo "4. Vendor Autoloader Test:\n";
$autoloadPath = $vendorPath . '/autoload.php';
if (file_exists($autoloadPath)) {
    try {
        require $autoloadPath;
        echo "   Status: OK (Composer autoloader loaded successfully)\n";
    } catch (\Throwable $e) {
        echo "   Status: ERROR loading autoloader: " . $e->getMessage() . "\n";
    }
} else {
    echo "   Status: ERROR (Autoload file is missing. The /vendor directory is incomplete.)\n";
}
echo "\n";


// 5. App Bootstrap Test
echo "5. Laravel Bootstrap Test:\n";
$bootstrapPath = $assumedBasePath . '/bootstrap/app.php';
if (file_exists($bootstrapPath)) {
    try {
        $app = require_once $bootstrapPath;
        echo "   Status: OK (Laravel bootstrap container initialized successfully)\n";
        echo "\n============================================\n";
        echo "DIAGNOSTICS COMPLETE. \nIf no errors are shown above, the 500 error is likely originating deeper within Laravel (e.g., an invalid DB connection in .env or missing extension). \nCheck the storage/logs/laravel.log file directly.";
    } catch (\Throwable $e) {
        echo "   Status: FATAL ERROR during bootstrap: " . $e->getMessage() . "\n";
        echo "   Location: " . $e->getFile() . " on line " . $e->getLine() . "\n";
    }
} else {
    echo "   Status: ERROR (Application bootstrap file is missing.)\n";
}
