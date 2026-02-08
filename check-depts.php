<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Check departments table
$depts = \App\Models\Department::all();
echo "Total departments in database: " . $depts->count() . "\n";
echo "Active departments: " . \App\Models\Department::where('is_active', true)->count() . "\n";

foreach ($depts->take(3) as $dept) {
    echo "- {$dept->name} (Active: {$dept->is_active})\n";
}
?>
