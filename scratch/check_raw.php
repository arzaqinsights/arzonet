<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$e = \App\Models\Email::where('email', 'amandal@msmeccii.in')->first();
if ($e) {
    foreach ($e->getAttributes() as $k => $v) {
        echo "$k: " . var_export($v, true) . "\n";
    }
}
