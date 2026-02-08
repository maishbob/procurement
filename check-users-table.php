<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$columns = Illuminate\Support\Facades\DB::select('DESCRIBE users');

echo "Users table structure:\n";
echo str_repeat('-', 50) . "\n";
foreach ($columns as $col) {
    echo "{$col->Field} ({$col->Type})\n";
}
