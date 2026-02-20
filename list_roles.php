<?php

use Spatie\Permission\Models\Role;

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "--- Roles in Database ---\n";
$roles = Role::all();
foreach ($roles as $role) {
    echo "ID: {$role->id} | Name: '{$role->name}' | Slug: '{$role->slug}'\n";
}
