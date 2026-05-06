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
        public int $emailListId
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
            $chunkSize = 100; // Scalable chunk size (User requested 100-200)
            $currentChunk = [];
            $processedCount = 0;

            // STREAMING: Reads row by row using PHP Generators
            foreach ($parser->streamStoredFile($emailList->file_path, $mapping) as $row) {
                $currentChunk[] = $row;
                $processedCount++;

                if (count($currentChunk) >= $chunkSize) {
                    $jobs[] = new ImportEmailChunkJob($emailListId, $currentChunk);
                    $currentChunk = [];

                    // Dispatch batch every 50 chunks (5000 records) to maintain responsiveness
                    if (count($jobs) >= 50) {
                        $this->dispatchBatch($jobs, $emailList);
                        $jobs = [];
                        Log::info("Dispatched 5000 records for list #{$emailListId}. Continuing...");
                    }
                }
            }

            // Process the final chunk
            if (!empty($currentChunk)) {
                $jobs[] = new ImportEmailChunkJob($emailListId, $currentChunk);
            }

            if (!empty($jobs)) {
                $this->dispatchBatch($jobs, $emailList);
            }

            Log::info("ProcessEmailListJob scan complete. Total rows scanned: {$processedCount} for list #{$emailListId}");

            // If no records found at all
            if ($processedCount === 0) {
                $emailList->update(['status' => 'completed']);
            }

        } catch (\Exception $e) {
            Log::error("CRITICAL: ProcessEmailListJob failed for list #{$this->emailListId}", [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            $emailList->update(['status' => 'failed']);
            throw $e;
        }
    }

    /**
     * Dispatch a batch of chunks to the database queue.
     */
    protected function dispatchBatch(array $jobs, EmailList $emailList): void
    {
        $emailListId = $emailList->id;
        
        Bus::batch($jobs)
            ->name("Import List: {$emailList->name}")
            ->then(function (Batch $batch) use ($emailListId) {
                $list = EmailList::find($emailListId);
                if ($list && $list->status !== 'failed') {
                    $list->recalculateStats();
                    $list->update(['status' => 'completed']);
                }
                Log::info("Batch component finished successfully for list #{$emailListId}");
            })
            ->catch(function (Batch $batch, \Throwable $e) use ($emailListId) {
                EmailList::find($emailListId)?->update(['status' => 'failed']);
                Log::error("Batch processing failed for list #{$emailListId}: " . $e->getMessage());
            })
            ->dispatch();
    }
}