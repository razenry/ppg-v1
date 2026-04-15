<?php

use App\Models\Setting;
use App\Models\Subscription;
use Illuminate\Contracts\Console\Kernel;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

$subs = Subscription::latest()->take(3)->get();
$setting = Setting::get('subscription_duration_default');

file_put_contents('debug-output.txt', json_encode([
    'setting' => $setting,
    'subs' => $subs->toArray(),
], JSON_PRETTY_PRINT));
echo 'Done';
