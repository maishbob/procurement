<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

echo "=== Checking Database Tables ===\n\n";

try {
    $pdo = DB::connection()->getPdo();
    $query = $pdo->query("SHOW TABLES");
    $tables = $query->fetchAll(PDO::FETCH_COLUMN);

    echo "Tables in database:\n";
    foreach ($tables as $table) {
        echo "  - $table\n";
    }

    echo "\n";

    // Check specifically for permission-related tables
    $permissionTables = ['permissions', 'roles', 'model_has_permissions', 'model_has_roles', 'role_has_permissions'];
    echo "Permission tables status:\n";
    foreach ($permissionTables as $table) {
        $exists = in_array($table, $tables);
        echo "  " . ($exists ? '✓' : '✗') . " $table\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
