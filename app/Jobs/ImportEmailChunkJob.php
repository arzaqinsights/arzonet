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

    public int $tries = 5;
    public int $timeout = 600;

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
 
        // Query list default topics to associate with new contacts
        $listTopicIds = \App\Models\SubscriptionTopic::where('email_list_id', $this->emailListId)
            ->pluck('id')
            ->toArray();
 
        try {
            $skipDns = $emailList->column_mapping['_settings']['skip_dns'] ?? false;
 
            $results = $validator->validateBatch($this->chunk, $this->emailListId, $skipDns);
 
            // ── Step 1: Build new inserts (valid + invalid + cross_duplicate) ──
            $batchEntries = [];
            foreach ($results['valid'] as $entry) {
                $batchEntries[] = $this->formatEntry($emailList, $entry, 'valid', $listTopicIds);
            }
            foreach ($results['invalid'] as $entry) {
                $batchEntries[] = $this->formatEntry($emailList, $entry, 'invalid', $listTopicIds);
            }
            foreach ($results['cross_duplicate'] as $entry) {
                $batchEntries[] = $this->formatEntry($emailList, $entry, 'cross_duplicate', $listTopicIds);
            }
            // Duplicates are NOT inserted — they already exist

            // ── Step 2: Restore archived & promote duplicate→valid (NO activity_log_id link) ──
            // We intentionally do NOT set activity_log_id on restored/promoted records.
            // This keeps them out of the undo scope — undo should only delete NEW inserts.
            $repairableEntries = array_merge($results['to_restore'], $results['to_valid']);
            foreach ($repairableEntries as $entry) {
                $query = Email::where('email_list_id', $this->emailListId);
                if (!empty($entry['id'])) {
                    $query->where('id', $entry['id']);
                } else {
                    $query->where('email', $entry['email']);
                }

                $query->update([
                        'user_id'             => $emailList->user_id,
                        'is_archived'         => false,
                        'archived_at'         => null,
                        'name'                => DB::raw("COALESCE(NULLIF(name,''), " . DB::getPdo()->quote($entry['name'] ?? '') . ")"),
                        'status'              => 'valid',
                        'subscription_status' => 'subscribed',
                        'subscribed_topics'   => json_encode(array_map('intval', $listTopicIds)),
                        'whatsapp_number'     => DB::raw("COALESCE(NULLIF(whatsapp_number,''), " . DB::getPdo()->quote($entry['whatsapp_number'] ?? '') . ")"),
                        'whatsapp_opt_in'     => $entry['whatsapp_opt_in'] ?? true,
                        'whatsapp_subscription_status' => $entry['whatsapp_subscription_status'] ?? 'subscribed',
                        'meta'                => isset($entry['meta']) ? json_encode($entry['meta']) : DB::raw('meta'),
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
                        'original_row_id'     => $entry['original_row_id'] ?? null,
                    ]);
            }

            // ── Step 3: Insert new records ──
            if (!empty($batchEntries)) {
                try {
                    Email::insert($batchEntries);
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error("DATABASE INSERTION FAILED in ImportEmailChunkJob for list #{$this->emailListId}", [
                        'error' => $e->getMessage(),
                        'first_entry' => $batchEntries[0] ?? null,
                    ]);
                    throw $e;
                }
            }

            // ── Step 4: Atomic increment of session counters on the activity log ──
            // These dedicated integer columns handle concurrent chunk updates safely.
            $countValid     = count($results['valid']) + count($results['to_restore']) + count($results['to_valid']);
            $countInvalid   = count($results['invalid']);
            $countDuplicate = count($results['duplicate']);
            $countCrossDuplicate = count($results['cross_duplicate']);

            // Detailed Health Metrics for Log
            $healthData = collect(array_merge($results['valid'], $results['to_restore'], $results['to_valid']));
            $countRisky      = $healthData->where('email_status', 'risky')->count();
            $countRole       = $healthData->where('is_role_based', true)->count();
            $countDisposable = $healthData->where('is_disposable', true)->count();
            $countCatchAll   = $healthData->where('is_catch_all', true)->count();
            $countTypo       = $healthData->where('has_typo', true)->count();

            if ($this->activityLogId) {
                DB::table('activity_logs')
                    ->where('id', $this->activityLogId)
                    ->update([
                        'session_valid_count'     => DB::raw("session_valid_count + $countValid"),
                        'session_invalid_count'   => DB::raw("session_invalid_count + $countInvalid"),
                        'session_duplicate_count' => DB::raw("session_duplicate_count + $countDuplicate"),
                        'session_cross_duplicate_count' => DB::raw("session_cross_duplicate_count + $countCrossDuplicate"),
                        'session_risky_count'      => DB::raw("session_risky_count + $countRisky"),
                        'session_role_based_count' => DB::raw("session_role_based_count + $countRole"),
                        'session_disposable_count' => DB::raw("session_disposable_count + $countDisposable"),
                        'session_catch_all_count'  => DB::raw("session_catch_all_count + $countCatchAll"),
                        'session_typo_count'       => DB::raw("session_typo_count + $countTypo"),
                    ]);
            }

            // ── Step 5: Atomic update list-level stats ──
            // total_records includes new inserts + restored/promoted records
            $totalNewOrReactivated = count($batchEntries) + count($results['to_restore']) + count($results['to_valid']);
            $emailList->update([
                'total_records'   => DB::raw('total_records + ' . $totalNewOrReactivated),
                'valid_count'     => DB::raw('valid_count + ' . $countValid),
                'invalid_count'   => DB::raw('invalid_count + ' . $countInvalid),
                'duplicate_count' => DB::raw('duplicate_count - ' . count($results['to_valid']) . ' + ' . $countDuplicate),
                'cross_duplicate_count' => DB::raw('cross_duplicate_count + ' . $countCrossDuplicate),
            ]);

        } catch (\Exception $e) {
            Log::error("ImportEmailChunkJob failed for list #{$this->emailListId}: " . $e->getMessage(), [
                'chunk_size'  => count($this->chunk),
                'first_email' => $this->chunk[0]['email'] ?? 'unknown',
            ]);
            throw $e;
        }
    }

    protected function formatEntry($emailList, $entry, $status, array $listTopicIds = []): array
    {
        $tagsRaw = $entry['meta']['tags'] ?? null;
        $tags = null;
        if ($tagsRaw) {
            $tagsArray = array_map('trim', array_filter(explode(',', $tagsRaw)));
            $tags = !empty($tagsArray) ? json_encode($tagsArray) : null;
            unset($entry['meta']['tags']);
        }
 
        $subStatus = ($status === 'invalid') ? 'unsubscribed' : 'subscribed';
 
        return [
            'user_id'             => $emailList->user_id,
            'email_list_id'       => $emailList->id,
            'activity_log_id'     => $this->activityLogId, // Only new inserts get this link
            'email'               => $entry['email'],
            'whatsapp_number'     => $entry['whatsapp_number'] ?? null,
            'name'                => $entry['name'] ?? null,
            'status'              => $status,
            'subscription_status' => $subStatus,
            'subscribed_topics'   => ($subStatus === 'unsubscribed') ? json_encode([]) : json_encode(array_map('intval', $listTopicIds)),
            'signup_source'       => $emailList->signup_source,
            'segment_name'        => $emailList->segment_name,
            'tags'                => $tags,
            'reason'              => $entry['reason'] ?? null,
            'original_row_id'     => $entry['original_row_id'] ?? null,
            'meta'                => isset($entry['meta']) && !empty($entry['meta']) ? json_encode($entry['meta']) : null,
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
