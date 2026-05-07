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
        public ?int $activityLogId = null
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
            
            $jobs = [];
            $chunkSize = 50; 
            $currentChunk = [];
            $processedCount = 0;

            foreach ($parser->streamStoredFile($emailList->file_path, $mapping) as $row) {
                $currentChunk[] = $row;
                $processedCount++;

                if (count($currentChunk) >= $chunkSize) {
                    $jobs[] = new ImportEmailChunkJob($emailListId, $currentChunk, $this->activityLogId);
                    $currentChunk = [];

                    if (count($jobs) >= 50) {
                        $this->dispatchBatch($jobs, $emailList);
                        $jobs = [];
                    }
                }
            }

            if (!empty($currentChunk)) {
                $jobs[] = new ImportEmailChunkJob($emailListId, $currentChunk, $this->activityLogId);
            }

            if (!empty($jobs)) {
                $this->dispatchBatch($jobs, $emailList);
            }

            // Update total rows in log if we have it
            if ($this->activityLogId) {
                $log = \App\Models\ActivityLog::find($this->activityLogId);
                if ($log) {
                    $log->update([
                        'details' => array_merge($log->details, ['total_in_file' => $processedCount])
                    ]);
                }
            }

        } catch (\Exception $e) {
            Log::error("CRITICAL: ProcessEmailListJob failed for list #{$this->emailListId}", [
                'message' => $e->getMessage(),
            ]);
            
            $emailList->update(['status' => 'failed']);
            throw $e;
        }
    }

    protected function dispatchBatch(array $jobs, EmailList $emailList): void
    {
        $emailListId = $emailList->id;
        $logId = $this->activityLogId;
        
        $batch = Bus::batch($jobs)
            ->name("Import List: {$emailList->name}")
            ->then(function (Batch $batch) use ($emailListId, $logId) {
                $list = EmailList::find($emailListId);
                if ($list && $list->status !== 'failed') {
                    $list->recalculateStats();
                    $list->update(['status' => 'completed']);
                    
                    if ($logId) {
                        $log = \App\Models\ActivityLog::find($logId);
                        if ($log) {
                            // Read the atomic session counters accumulated by chunks
                            $log->refresh();
                            $sValid     = (int) $log->session_valid_count;
                            $sInvalid   = (int) $log->session_invalid_count;
                            $sDuplicate = (int) $log->session_duplicate_count;

                            $log->update([
                                'details' => array_merge($log->details ?? [], [
                                    'status'      => 'completed',
                                    'processed'   => $sValid + $sInvalid + $sDuplicate,
                                    'valid'       => $sValid,
                                    'duplicate'   => $sDuplicate,
                                    'invalid'     => $sInvalid,
                                    'finished_at' => now()->toDateTimeString(),
                                ]),
                                // Reset counters after finalizing
                                'session_valid_count'     => 0,
                                'session_invalid_count'   => 0,
                                'session_duplicate_count' => 0,
                            ]);
                        }
                    }
                }
            })
            ->catch(function (Batch $batch, \Throwable $e) use ($emailListId) {
                EmailList::find($emailListId)?->update(['status' => 'failed']);
            })
            ->dispatch();

        if ($logId) {
            \App\Models\ActivityLog::where('id', $logId)->update(['batch_id' => $batch->id]);
        }
    }
}