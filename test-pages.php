<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';

$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Test database connectivity and table existence
$db = app('db');
$tables = $db->select("SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA='procurement'");
$tableNames = array_map(fn($t) => $t->TABLE_NAME, $tables);

echo "Database Status:\n";
echo "  Total tables: " . count($tableNames) . "\n";
echo "  model_has_roles exists: " . (in_array('model_has_roles', $tableNames) ? "✓ YES\n" : "✗ NO\n");
echo "  roles table exists: " . (in_array('roles', $tableNames) ? "✓ YES\n" : "✗ NO\n");

echo "\nUser count: " . \App\Models\User::count() . "\n";
echo "Role count: " . \Spatie\Permission\Models\Role::count() . "\n";

echo "\nKey tables present:\n";
foreach(['users', 'requisitions', 'purchase_orders', 'invoices', 'suppliers', 'permissions', 'roles', 'model_has_roles'] as $table) {
    if(in_array($table, $tableNames)) {
        $count = $db->table($table)->count();
        echo "  ✓ $table ($count rows)\n";
    } else {
        echo "  ✗ $table (MISSING)\n";
    }
}
