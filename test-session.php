<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Session Config:\n";
echo "  Driver: " . config('session.default') . "\n";
echo "  Table: " . config('session.table') . "\n";
echo "  Path: " . config('session.path') . "\n";
echo "  Domain: " . config('session.domain') . "\n\n";

echo "Database Check:\n";
try {
    $hasTable = Illuminate\Support\Facades\Schema::hasTable('sessions');
    echo "  Sessions table exists: " . ($hasTable ? 'YES' : 'NO') . "\n";

    if ($hasTable) {
        $count = Illuminate\Support\Facades\DB::table('sessions')->count();
        echo "  Sessions count: $count\n";
    }
} catch (Exception $e) {
    echo "  ERROR: " . $e->getMessage() . "\n";
}
