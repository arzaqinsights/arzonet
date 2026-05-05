<?php

use App\Models\EmailList;
use App\Jobs\ProcessEmailListJob;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$list = EmailList::latest()->first();
if ($list) {
    echo "Testing Job for List: " . $list->id . "\n";
    try {
        app(ProcessEmailListJob::class, ['emailListId' => $list->id])->handle(
            app(App\Services\FileParserService::class),
            app(App\Services\EmailValidationService::class)
        );
        echo "Success!\n";
    } catch (\Exception $e) {
        echo "ERROR: " . $e->getMessage() . "\n";
        echo $e->getTraceAsString() . "\n";
    }
} else {
    echo "No list found.\n";
}
