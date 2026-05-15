<?php

use App\Models\EmailList;
use App\Services\FileParserService;
use App\Services\EmailValidationService;
use App\Jobs\ImportEmailChunkJob;
use Illuminate\Support\Facades\Storage;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// 1. Create a dummy list
$list = EmailList::create([
    'user_id' => 1,
    'name' => 'Debug List ' . now(),
    'list_type' => 'dual',
    'status' => 'pending'
]);

echo "Created list #{$list->id}\n";

// 2. Prepare mismatched data
// Row 1: 1 email, 3 phones
$data = "Name,Email,Phone\n";
$data .= "Alice,alice@example.com,\"111111, 222222, 333333\"\n";
$data .= "Bob,,444444\n";

$path = 'temp_debug_import.csv';
Storage::disk('local')->put($path, $data);

$mapping = [
    'name' => 'Name',
    'email' => 'Email',
    'whatsapp_number' => 'Phone'
];

$parser = new FileParserService();
$validator = new EmailValidationService();

echo "Starting Parse...\n";
$rows = [];
foreach ($parser->streamStoredFile($path, $mapping, 'dual') as $row) {
    $rows[] = $row;
    echo "Mapped Row: " . json_encode($row) . "\n";
}

echo "Starting Validation...\n";
$results = $validator->validateBatch($rows, $list->id);

echo "Validation Results:\n";
echo "Valid: " . count($results['valid']) . "\n";
echo "Invalid: " . count($results['invalid']) . "\n";
echo "Duplicate: " . count($results['duplicate']) . "\n";

foreach($results['invalid'] as $inv) {
    echo "Invalid Entry: " . json_encode($inv) . "\n";
}

// 3. Try to insert
echo "Attempting insertion via Job logic...\n";
$batchEntries = [];
foreach ($results['valid'] as $entry) {
    $batchEntries[] = [
        'user_id' => $list->user_id,
        'email_list_id' => $list->id,
        'email' => $entry['email'],
        'name' => $entry['name'] ?? null,
        'whatsapp_number' => $entry['whatsapp_number'] ?? null,
        'status' => 'valid',
        'created_at' => now(),
        'updated_at' => now(),
    ];
}

try {
    if (!empty($batchEntries)) {
        \App\Models\Email::insert($batchEntries);
        echo "Successfully inserted " . count($batchEntries) . " records!\n";
    }
} catch (\Exception $e) {
    echo "CRITICAL ERROR DURING INSERT: " . $e->getMessage() . "\n";
}

$list->delete();
echo "Cleaned up.\n";
