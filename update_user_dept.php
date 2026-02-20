<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

$kernel->bootstrap();

$user = App\Models\User::find(1);
if ($user) {
    $user->department_id = 16;
    $user->save();
    echo "User {$user->id} updated with department_id 16.\n";
} else {
    echo "User 1 not found.\n";
}
