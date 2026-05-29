<?php

require_once __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Storage;
use App\Services\FileParserService;

$parser = new FileParserService();

$testFiles = [
    'email-lists/TPMHR1XGQVqig6abTzLBOKUfVXUmlydraHxyvEuI.xlsx',
    'email-lists/ocJlQWgJwZBnLnSywJIIQW1qDOXNNkgB5xmiORfl.xlsx',
    'email-lists/seGQevNf5B6oYaSEYRZaTvkuuxq7JkzRKbxxezyX.xlsx',
];

foreach ($testFiles as $filePath) {
    if (!Storage::disk('local')->exists($filePath)) {
        echo "SKIP: {$filePath} does not exist\n\n";
        continue;
    }
    
    echo "=== " . basename($filePath) . " ===\n";
    
    $fullPath = Storage::disk('local')->path($filePath);
    
    $tempFile = new \Illuminate\Http\UploadedFile($fullPath, basename($filePath), null, null, true);
    $parsed = $parser->parse($tempFile);
    echo "  Headers: " . json_encode($parsed['headers']) . "\n";
    
    $autoMapping = $parser->autoDetectMappings($parsed['headers'], $parsed['rows']);
    $flippedMapping = [];
    foreach ($autoMapping as $excelCol => $sysField) {
        $flippedMapping[$sysField] = $excelCol;
    }
    $flippedMapping['_settings'] = ['skip_dns' => false];
    
    $yieldedCount = 0;
    foreach ($parser->streamStoredFile($filePath, $flippedMapping) as $row) {
        $yieldedCount++;
    }
    
    $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
    $reader->setReadDataOnly(true);
    $ss = $reader->load($fullPath);
    $highestRow = $ss->getActiveSheet()->getHighestRow();
    $ss->disconnectWorksheets();
    
    echo "  Excel highest row: {$highestRow}\n";
    echo "  Streamed (yielded) rows: {$yieldedCount}\n";
    echo "  Difference (lost): " . ($highestRow - 1 - $yieldedCount) . " (header excluded)\n\n";
}
