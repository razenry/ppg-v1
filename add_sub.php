<?php

use App\Models\Subscription;
use App\Models\User;
use Illuminate\Contracts\Console\Kernel;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

$user = User::orderBy('id', 'desc')->first();
if ($user) {
    if ($user->subscriptions()->count() == 0) {
        $sub = Subscription::factory()->create([
            'user_id' => $user->id,
            'status' => 'active',
            'max_server' => 5,
        ]);
        echo "Created subscription {$sub->id} for user {$user->email}\n";
    } else {
        echo "User {$user->email} already has subscriptions\n";
    }
} else {
    echo "No users found\n";
}
