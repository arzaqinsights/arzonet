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
    public int $timeout = 900; // Increased timeout for large file scanning

    public function __construct(
        public int $emailListId
    ) {}

    public function handle(FileParserService $parser): void
    {
        $emailList = EmailList::findOrFail($this->emailListId);
        Log::info("Initiating Streamed Processing for list: {$emailList->id}");

        try {
            $emailList->update([
                'status' => 'processing', 
                'total_records' => 0, 
                'valid_count' => 0, 
                'invalid_count' => 0, 
                'duplicate_count' => 0
            ]);
            
            if (!$emailList->file_path) {
                $emailList->update(['status' => 'completed']);
                return;
            }

            $mapping = $emailList->column_mapping;
            $emailListId = $this->emailListId;
            
            $jobs = [];
            $chunkSize = 250; // Increased chunk size for efficiency
            $currentChunk = [];

            // STREAMING: No memory spike even with 1M rows
            foreach ($parser->streamStoredFile($emailList->file_path, $mapping) as $row) {
                $currentChunk[] = $row;

                if (count($currentChunk) >= $chunkSize) {
                    $jobs[] = new ImportEmailChunkJob($emailListId, $currentChunk);
                    $currentChunk = [];

                    // Dispatch batches of 50 chunks to prevent huge batch initialization lag
                    if (count($jobs) >= 50) {
                        $this->dispatchBatch($jobs, $emailList);
                        $jobs = [];
                    }
                }
            }

            // Final chunk
            if (!empty($currentChunk)) {
                $jobs[] = new ImportEmailChunkJob($emailListId, $currentChunk);
            }

            if (!empty($jobs)) {
                $this->dispatchBatch($jobs, $emailList);
            }

            // If no jobs were generated, mark as completed
            if ($emailList->total_records === 0 && empty($jobs)) {
                $emailList->update(['status' => 'completed']);
            }

        } catch (\Exception $e) {
            Log::error("ProcessEmailListJob Master Job failed: {$e->getMessage()}", [
                'list_id' => $this->emailListId,
                'trace' => $e->getTraceAsString()
            ]);
            $emailList->update(['status' => 'failed']);
            throw $e;
        }
    }

    protected function dispatchBatch(array $jobs, EmailList $emailList): void
    {
        $emailListId = $emailList->id;
        
        Bus::batch($jobs)
            ->name("Import List: {$emailList->name}")
            ->then(function (Batch $batch) use ($emailListId) {
                EmailList::find($emailListId)?->update(['status' => 'completed']);
                Log::info("Batch Import Part Finished for list: {$emailListId}");
            })
            ->catch(function (Batch $batch, \Throwable $e) use ($emailListId) {
                EmailList::find($emailListId)?->update(['status' => 'failed']);
                Log::error("Batch Import Failed for list: {$emailListId} - {$e->getMessage()}");
            })
            ->dispatch();
    }
}