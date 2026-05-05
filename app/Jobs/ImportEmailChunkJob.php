<?php

namespace App\Jobs;

use App\Models\EmailList;
use App\Models\Email;
use App\Services\EmailValidationService;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ImportEmailChunkJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 300;

    public function __construct(
        public int $emailListId,
        public array $chunk
    ) {}

    public function handle(EmailValidationService $validator): void
    {
        $emailList = EmailList::find($this->emailListId);
        if (!$emailList) return;

        $skipDns = $emailList->column_mapping['_settings']['skip_dns'] ?? false;

        $results = $validator->validateBatch($this->chunk, $this->emailListId, $skipDns);
        $batchEntries = [];

        foreach ($results['valid'] as $entry) {
            $batchEntries[] = $this->formatEntry($emailList, $entry, 'valid');
        }
        foreach ($results['invalid'] as $entry) {
            $batchEntries[] = $this->formatEntry($emailList, $entry, 'invalid');
        }
        foreach ($results['duplicate'] as $entry) {
            $batchEntries[] = $this->formatEntry($emailList, $entry, 'duplicate');
        }

        if (!empty($batchEntries)) {
            Email::insert($batchEntries);
        }

        // Update list stats in a single query (MUCH faster)
        $emailList->update([
            'total_records'   => $emailList->total_records + count($batchEntries),
            'valid_count'     => $emailList->valid_count + count($results['valid']),
            'invalid_count'   => $emailList->invalid_count + count($results['invalid']),
            'duplicate_count' => $emailList->duplicate_count + count($results['duplicate']),
        ]);
    }

    protected function formatEntry($emailList, $entry, $status): array
    {
        return [
            'email_list_id'      => $emailList->id,
            'email'              => $entry['email'],
            'name'               => $entry['name'] ?? null,
            'status'             => $status,
            'subscription_status'=> $status === 'valid' ? 'subscribed' : 'unsubscribed',
            'signup_source'      => $emailList->signup_source,
            'segment_name'       => $emailList->segment_name,
            'reason'             => $entry['reason'] ?? null,
            'meta'               => isset($entry['meta']) ? json_encode($entry['meta']) : null,
            'created_at'         => now(),
            'updated_at'         => now(),
        ];
    }
}
