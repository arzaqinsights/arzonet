<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\WhatsAppMessage;
use Illuminate\Support\Facades\DB;

$lastMsg = WhatsAppMessage::latest()->first();

if ($lastMsg) {
    echo "Last Message ID: " . $lastMsg->id . "\n";
    echo "WA ID: " . $lastMsg->wa_message_id . "\n";
    echo "Status: " . $lastMsg->status . "\n";
    echo "To: " . $lastMsg->contact->whatsapp_number . "\n";
    
    $statuses = DB::table('whatsapp_message_statuses')
        ->where('whatsapp_message_id', $lastMsg->id)
        ->get();
        
    echo "\nStatus History:\n";
    foreach ($statuses as $s) {
        echo "- " . $s->status . " at " . $s->occurred_at . " (Raw: " . $s->raw_response . ")\n";
    }
} else {
    echo "No messages found.\n";
}
