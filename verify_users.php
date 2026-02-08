<?php
use App\Models\User;

$users = User::all();

if ($users->isEmpty()) {
    echo "NO USERS FOUND.\n";
} else {
    echo "FOUND " . $users->count() . " USERS:\n";
    foreach ($users as $user) {
        $roles = $user->getRoleNames()->implode(', ');
        echo "- ID: {$user->id}, Email: {$user->email}, Roles: {$roles}, Password Hash: {$user->password}\n";
    }
}
