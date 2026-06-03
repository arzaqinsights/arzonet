<?php

namespace App\Jobs;

use App\Models\EmailList;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class BulkPermanentDeleteJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 3600;

    protected $emailListId;
    protected $isGlobal;
    protected $filters;
    protected $ids;
    protected $reason;

    public function __construct($emailListId, $isGlobal, $filters, $ids, $reason)
    {
        $this->emailListId = $emailListId;
        $this->isGlobal = $isGlobal;
        $this->filters = $filters;
        $this->ids = $ids;
        $this->reason = $reason;
    }

    public function handle()
    {
        $emailList = EmailList::find($this->emailListId);
        if (!$emailList) return;

        if ($this->isGlobal && $this->filters) {
            $query = $emailList->emails();
            $filters = $this->filters;

            if (isset($filters['status']) && $filters['status'] !== 'all')
                $query->where('status', $filters['status']);
            if (isset($filters['subscription']) && $filters['subscription'] !== 'all')
                $query->where('subscription_status', $filters['subscription']);
            if (isset($filters['segment']) && $filters['segment'] !== 'all')
                $query->where('segment_name', $filters['segment']);
            if (isset($filters['tag']) && $filters['tag'] !== 'all')
                $query->where('tags', 'like', '%' . $filters['tag'] . '%');
            if (isset($filters['source']) && $filters['source'] !== 'all')
                $query->where('signup_source', $filters['source']);
            if (isset($filters['archived'])) {
                if ($filters['archived'] === 'yes')
                    $query->where('is_archived', true);
                elseif ($filters['archived'] === 'no')
                    $query->where('is_archived', false);
            }

            if (!empty($filters['search'])) {
                $search = $filters['search'];
                $field = $filters['search_field'] ?? 'all';
                $query->where(function ($q) use ($search, $field) {
                    if ($field === 'all' || $field === 'email')
                        $q->orWhere('email', 'like', "%$search%");
                    if ($field === 'all' || $field === 'name')
                        $q->orWhere('name', 'like', "%$search%");
                });
            }
            $emails = $query;
        } else {
            $emails = $emailList->emails()->whereIn('id', $this->ids);
        }

        $deleteQuery = clone $emails;

        $identifiers = [];
        $emails->chunkById(500, function ($chunk) use (&$identifiers) {
            foreach ($chunk as $email) {
                if (!empty($email->email)) {
                    $identifiers[] = $email->email;
                }
                if (!empty($email->whatsapp_number)) {
                    $identifiers[] = $email->whatsapp_number;
                }
            }
        });
        
        $identifiers = array_unique(array_filter($identifiers));
        $suppressions = [];
        $now = now()->toDateTimeString();
        foreach ($identifiers as $identifier) {
            $suppressions[] = [
                'email_list_id' => $emailList->id,
                'identifier' => $identifier,
                'reason' => $this->reason,
                'created_at' => $now,
                'updated_at' => $now
            ];
            
            // Insert in chunks of 500 to avoid large payload errors
            if (count($suppressions) >= 500) {
                \App\Models\EmailListSuppression::upsert(
                    $suppressions, 
                    ['email_list_id', 'identifier'], 
                    ['reason', 'updated_at']
                );
                $suppressions = [];
            }
        }
        
        if (count($suppressions) > 0) {
            \App\Models\EmailListSuppression::upsert(
                $suppressions, 
                ['email_list_id', 'identifier'], 
                ['reason', 'updated_at']
            );
        }

        $deleteQuery->delete();

        $emailList->recalculateStats();
    }
}
