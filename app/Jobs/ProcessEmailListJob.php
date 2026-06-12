<?php

namespace App\Jobs;

use App\Models\EmailList;
use App\Services\FileParserService;
use Illuminate\Bus\Batch;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use App\Jobs\ImportEmailChunkJob;

class ProcessEmailListJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 1800; // 30 minutes for extremely large files

    public function __construct(
        public int $emailListId,
        public ?int $activityLogId = null,
        public array $selectedTags = [],
        public array $selectedTopicIds = []
    ) {}

    public function handle(FileParserService $parser): void
    {
        $emailList = EmailList::find($this->emailListId);
        if (!$emailList) {
            Log::error("ProcessEmailListJob failed: EmailList #{$this->emailListId} not found.");
            return;
        }

        Log::info("Starting background processing for list: {$emailList->id} ({$emailList->name})");

        try {
            $emailList->update([
                'status' => 'processing',
            ]);
            
            if (!$emailList->file_path) {
                Log::warning("ProcessEmailListJob: No file path for list #{$emailList->id}");
                $emailList->update(['status' => 'completed']);
                return;
            }

            $mapping = $emailList->column_mapping;
            $emailListId = $this->emailListId;
            $listType = $emailList->list_type;
            $logId = $this->activityLogId;
            
            $chunkSize = 100;
            $currentChunk = [];
            $totalInFile = 0;

            // Create the batch FIRST with an empty jobs array, then add them incrementally
            $batch = Bus::batch([])
                ->name("Import List: {$emailList->name}")
                ->finally(function (Batch $batch) use ($emailListId, $logId) {
                    $list = EmailList::find($emailListId);
                    if ($list && $list->status !== 'failed') {
                        // Force completion status first to unlock UI
                        $list->update(['status' => 'completed']);
                        $list->recalculateStats();

                        // Automatically compute segments for the entire imported list
                        \App\Jobs\UpdateContactSegmentsJob::dispatch(null, $emailListId);
                        
                        if ($logId) {
                            $log = \App\Models\ActivityLog::find($logId);
                            if ($log) {
                                // Read the atomic session counters accumulated by chunks
                                $log->refresh();
                                $sValid       = (int) $log->session_valid_count;
                                $sInvalid     = (int) $log->session_invalid_count;
                                $sDuplicate   = (int) $log->session_duplicate_count;
                                $sCrossDuplicate = (int) $log->session_cross_duplicate_count;
                                $sRisky       = (int) $log->session_risky_count;
                                $sRole        = (int) $log->session_role_based_count;
                                $sDisposable  = (int) $log->session_disposable_count;
                                $sCatchAll    = (int) $log->session_catch_all_count;
                                $sTypo        = (int) $log->session_typo_count;

                                $log->update([
                                    'details' => array_merge($log->details ?? [], [
                                        'status'      => 'completed',
                                        'processed'   => $sValid + $sInvalid + $sDuplicate + $sCrossDuplicate,
                                        'valid'       => $sValid,
                                        'duplicate'   => $sDuplicate,
                                        'invalid'     => $sInvalid,
                                        'cross_duplicate' => $sCrossDuplicate,
                                        'risky'       => $sRisky,
                                        'role_based'  => $sRole,
                                        'disposable'  => $sDisposable,
                                        'catch_all'   => $sCatchAll,
                                        'typo'        => $sTypo,
                                        'finished_at' => now()->toDateTimeString(),
                                    ]),
                                    // Reset counters after finalizing
                                    'session_valid_count'     => 0,
                                    'session_invalid_count'   => 0,
                                    'session_duplicate_count' => 0,
                                    'session_cross_duplicate_count' => 0,
                                    'session_risky_count'      => 0,
                                    'session_role_based_count' => 0,
                                    'session_disposable_count' => 0,
                                    'session_catch_all_count'  => 0,
                                    'session_typo_count'       => 0,
                                ]);
                            }
                        }
                    }
                })
                ->dispatch();

            if ($logId) {
                \App\Models\ActivityLog::where('id', $logId)->update(['batch_id' => $batch->id]);
            }

            $pendingJobs = [];
            foreach ($parser->streamStoredFile($emailList->file_path, $mapping, $listType) as $row) {
                $currentChunk[] = $row;
                $totalInFile++;

                if (count($currentChunk) >= $chunkSize) {
                    $pendingJobs[] = new ImportEmailChunkJob($emailListId, $currentChunk, $this->activityLogId, $this->selectedTags, $this->selectedTopicIds);
                    $currentChunk = [];
 
                    // Flush to batch every 10 jobs to prevent memory buildup
                    if (count($pendingJobs) >= 10) {
                        $batch->add($pendingJobs);
                        $pendingJobs = [];
                    }
                }
            }
 
            if (!empty($currentChunk)) {
                $pendingJobs[] = new ImportEmailChunkJob($emailListId, $currentChunk, $this->activityLogId, $this->selectedTags, $this->selectedTopicIds);
            }
 
            if (!empty($pendingJobs)) {
                $batch->add($pendingJobs);
            }

            // Update total rows in log if we have it
            if ($logId) {
                $log = \App\Models\ActivityLog::find($logId);
                if ($log) {
                    $log->update([
                        'details' => array_merge($log->details ?? [], ['total_in_file' => $totalInFile])
                    ]);
                }
            }

            // If no jobs were added to the batch, we should mark completion
            if ($totalInFile === 0) {
                $emailList->update(['status' => 'completed']);
            }

        } catch (\Exception $e) {
            Log::error("CRITICAL: ProcessEmailListJob failed for list #{$this->emailListId}", [
                'message' => $e->getMessage(),
            ]);
            
            $emailList->update(['status' => 'failed']);
            throw $e;
        }
    }
}