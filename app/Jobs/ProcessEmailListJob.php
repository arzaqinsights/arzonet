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
    public int $timeout = 600;

    public function __construct(
        public int $emailListId
    ) {}

    public function handle(FileParserService $parser): void
    {
        $emailList = EmailList::findOrFail($this->emailListId);
        Log::info("Initiating Parallel Processing for list: {$emailList->id}");

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
            $data = $parser->parseStoredFile($emailList->file_path, $mapping);
            
            // Optimization: Split into micro-chunks of 50 for massive parallel DNS resolution
            $chunkSize = 100;
            $chunks = array_chunk($data, $chunkSize);
            $emailListId = $this->emailListId;

            $jobs = [];
            foreach ($chunks as $chunk) {
                $jobs[] = new ImportEmailChunkJob($emailListId, $chunk);
            }

            Bus::batch($jobs)
                ->name("Import List: {$emailList->name}")
                ->then(function (Batch $batch) use ($emailListId) {
                    EmailList::find($emailListId)?->update(['status' => 'completed']);
                    Log::info("Batch Import Finished for list: {$emailListId}");
                })
                ->catch(function (Batch $batch, \Throwable $e) use ($emailListId) {
                    EmailList::find($emailListId)?->update(['status' => 'failed']);
                    Log::error("Batch Import Failed for list: {$emailListId} - {$e->getMessage()}");
                })
                ->dispatch();

        } catch (\Exception $e) {
            Log::error("ProcessEmailListJob Master Job failed: {$e->getMessage()}");
            $emailList->update(['status' => 'failed']);
            throw $e;
        }
    }
}