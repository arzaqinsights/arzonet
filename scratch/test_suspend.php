<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = App\Models\User::first();
if ($user) {
    echo "Current is_suspended: " . ($user->is_suspended ? 'YES' : 'NO') . "\n";
    $user->update([
        'is_suspended' => true,
        'suspension_reason' => 'Test reason',
    ]);
    $user->refresh();
    echo "After update is_suspended: " . ($user->is_suspended ? 'YES' : 'NO') . "\n";
    
    // revert
    $user->update([
        'is_suspended' => false,
        'suspension_reason' => null,
    ]);
} else {
    echo "No user found\n";
}
