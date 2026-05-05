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
use Illuminate\Support\Facades\DB;

class ImportEmailChunkJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 300;

    public function __construct(
        public int $emailListId,
        public array $chunk
    ) {}

    /**
     * Process a chunk of emails using bulk insert.
     */
    public function handle(EmailValidationService $validator): void
    {
        // Safety check for batches
        if ($this->batch()?->cancelled()) return;

        $emailList = EmailList::find($this->emailListId);
        if (!$emailList) return;

        try {
            $skipDns = $emailList->column_mapping['_settings']['skip_dns'] ?? false;

            // Perform bulk validation (Service already optimized for mass lookups)
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
                // BULK INSERT: Single query for the whole chunk
                Email::insert($batchEntries);
            }

            // ATOMIC UPDATE: Ensure stats are updated correctly without race conditions
            $emailList->update([
                'total_records'   => DB::raw('total_records + ' . count($batchEntries)),
                'valid_count'     => DB::raw('valid_count + ' . count($results['valid'])),
                'invalid_count'   => DB::raw('invalid_count + ' . count($results['invalid'])),
                'duplicate_count' => DB::raw('duplicate_count + ' . count($results['duplicate'])),
            ]);

        } catch (\Exception $e) {
            Log::error("ImportEmailChunkJob failed for list #{$this->emailListId}: " . $e->getMessage(), [
                'chunk_size' => count($this->chunk),
                'first_email' => $this->chunk[0]['email'] ?? 'unknown'
            ]);
            throw $e;
        }
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
