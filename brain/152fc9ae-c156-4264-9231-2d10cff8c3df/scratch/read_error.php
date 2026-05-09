<?php
$logPath = 'storage/logs/laravel.log';
if (!file_exists($logPath)) {
    echo "Log file not found.\n";
    exit;
}

$content = file_get_contents($logPath);
$lines = explode("\n", $content);
$lastLines = array_slice($lines, -200);

foreach (array_reverse($lastLines) as $line) {
    if (strpos($line, 'local.ERROR') !== false || strpos($line, 'Stack trace') !== false) {
        echo $line . "\n";
    }
}
