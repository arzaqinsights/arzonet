<?php

namespace App\Jobs;

use App\Models\ActivityLog;
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
        public array $chunk,
        public ?int $activityLogId = null
    ) {}

    /**
     * Process a chunk of emails using bulk insert.
     */
    public function handle(EmailValidationService $validator): void
    {
        if ($this->batch()?->cancelled()) return;

        $emailList = EmailList::find($this->emailListId);
        if (!$emailList) return;

        try {
            $skipDns = $emailList->column_mapping['_settings']['skip_dns'] ?? false;

            $results = $validator->validateBatch($this->chunk, $this->emailListId, $skipDns);

            // ── Step 1: Build new inserts (valid + invalid only) ──
            $batchEntries = [];
            foreach ($results['valid'] as $entry) {
                $batchEntries[] = $this->formatEntry($emailList, $entry, 'valid');
            }
            foreach ($results['invalid'] as $entry) {
                $batchEntries[] = $this->formatEntry($emailList, $entry, 'invalid');
            }
            // Duplicates are NOT inserted — they already exist

            // ── Step 2: Restore archived & promote duplicate→valid (NO activity_log_id link) ──
            // We intentionally do NOT set activity_log_id on restored/promoted records.
            // This keeps them out of the undo scope — undo should only delete NEW inserts.
            $repairableEntries = array_merge($results['to_restore'], $results['to_valid']);
            foreach ($repairableEntries as $entry) {
                Email::where('email_list_id', $this->emailListId)
                    ->where('email', $entry['email'])
                    ->update([
                        'user_id'             => $emailList->user_id, // Fix: Ensure user_id is set
                        'is_archived'         => false,
                        'archived_at'         => null,
                        'name'                => DB::raw("COALESCE(NULLIF(name,''), " . DB::getPdo()->quote($entry['name'] ?? '') . ")"),
                        'status'              => 'valid',
                        'subscription_status' => 'subscribed',
                        // New health columns
                        'email_status'        => $entry['email_status'] ?? 'valid',
                        'email_score'         => $entry['email_score'] ?? 5,
                        'email_risk_level'    => $entry['email_risk_level'] ?? 'low',
                        'last_validation_at'  => $entry['last_validation_at'] ?? now(),
                        'is_role_based'       => $entry['is_role_based'] ?? false,
                        'is_disposable'       => $entry['is_disposable'] ?? false,
                        'is_catch_all'        => $entry['is_catch_all'] ?? false,
                        'has_typo'            => $entry['has_typo'] ?? false,
                        'validation_reason'   => $entry['validation_reason'] ?? null,
                    ]);
            }

            // ── Step 3: Insert new records ──
            if (!empty($batchEntries)) {
                Email::insert($batchEntries);
            }

            // ── Step 4: Atomic increment of session counters on the activity log ──
            // These dedicated integer columns handle concurrent chunk updates safely.
            $countValid     = count($results['valid']) + count($results['to_restore']) + count($results['to_valid']);
            $countInvalid   = count($results['invalid']);
            $countDuplicate = count($results['duplicate']);

            if ($this->activityLogId) {
                DB::table('activity_logs')
                    ->where('id', $this->activityLogId)
                    ->update([
                        'session_valid_count'     => DB::raw("session_valid_count + $countValid"),
                        'session_invalid_count'   => DB::raw("session_invalid_count + $countInvalid"),
                        'session_duplicate_count' => DB::raw("session_duplicate_count + $countDuplicate"),
                    ]);
            }

            // ── Step 5: Atomic update list-level stats ──
            $emailList->update([
                'total_records'   => DB::raw('total_records + ' . count($batchEntries)),
                'valid_count'     => DB::raw('valid_count + ' . $countValid),
                'invalid_count'   => DB::raw('invalid_count + ' . $countInvalid),
                'duplicate_count' => DB::raw('duplicate_count - ' . count($results['to_valid']) . ' + ' . $countDuplicate),
            ]);

        } catch (\Exception $e) {
            Log::error("ImportEmailChunkJob failed for list #{$this->emailListId}: " . $e->getMessage(), [
                'chunk_size'  => count($this->chunk),
                'first_email' => $this->chunk[0]['email'] ?? 'unknown',
            ]);
            throw $e;
        }
    }

    protected function formatEntry($emailList, $entry, $status): array
    {
        return [
            'user_id'             => $emailList->user_id,
            'email_list_id'       => $emailList->id,
            'activity_log_id'     => $this->activityLogId, // Only new inserts get this link
            'email'               => $entry['email'],
            'name'                => $entry['name'] ?? null,
            'status'              => $status,
            'subscription_status' => ($status === 'invalid') ? 'unsubscribed' : 'subscribed',
            'signup_source'       => $emailList->signup_source,
            'segment_name'        => $emailList->segment_name,
            'reason'              => $entry['reason'] ?? null,
            'meta'                => isset($entry['meta']) ? json_encode($entry['meta']) : null,
            'created_at'          => now(),
            'updated_at'          => now(),
            // New health columns
            'email_status'        => $entry['email_status'] ?? 'valid',
            'email_score'         => $entry['email_score'] ?? 5,
            'email_risk_level'    => $entry['email_risk_level'] ?? 'low',
            'last_validation_at'  => $entry['last_validation_at'] ?? now(),
            'is_role_based'       => $entry['is_role_based'] ?? false,
            'is_disposable'       => $entry['is_disposable'] ?? false,
            'is_catch_all'        => $entry['is_catch_all'] ?? false,
            'has_typo'            => $entry['has_typo'] ?? false,
            'validation_reason'   => $entry['validation_reason'] ?? null,
        ];
    }
}
