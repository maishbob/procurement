<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

function descTable($tableName) {
    try {
        $columns = DB::select("DESCRIBE $tableName");
        echo "Table: $tableName\n";
        foreach ($columns as $col) {
            echo "  {$col->Field} ({$col->Type})\n";
        }
    } catch (Exception $e) {
        echo "Table $tableName not found.\n";
    }
    echo "\n";
}

descTable('user_roles');
descTable('role_permissions');
descTable('roles');
