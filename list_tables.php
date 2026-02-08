<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$tables = Illuminate\Support\Facades\DB::select('SHOW TABLES');

echo "Tables in database:\n";
foreach ($tables as $table) {
    foreach ($table as $key => $value) {
        echo $value . "\n";
    }
}
