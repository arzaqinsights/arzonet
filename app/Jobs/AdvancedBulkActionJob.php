<?php

namespace App\Jobs;

use App\Models\EmailList;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use App\Models\Deal;

class AdvancedBulkActionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 3600;

    protected $emailListId;
    protected $actionType;
    protected $isGlobal;
    protected $filters;
    protected $ids;
    protected $payload;
    protected $userId;

    public function __construct($emailListId, $actionType, $isGlobal, $filters, $ids, $payload, $userId)
    {
        $this->emailListId = $emailListId;
        $this->actionType = $actionType;
        $this->isGlobal = $isGlobal;
        $this->filters = $filters;
        $this->ids = $ids;
        $this->payload = $payload;
        $this->userId = $userId;
    }

    public function handle()
    {
        $emailList = EmailList::find($this->emailListId);
        if (!$emailList) return;

        // Build the base query
        if ($this->isGlobal && $this->filters) {
            $query = clone $emailList->emails();
            
            // Re-apply filters manually since we don't have access to the controller
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
                    if ($field === 'all' || $field === 'whatsapp_number')
                        $q->orWhere('whatsapp_number', 'like', "%$search%");
                });
            }
            // Dynamic Advanced Rules
            if (isset($filters['advanced_rules']) && is_array($filters['advanced_rules'])) {
                foreach ($filters['advanced_rules'] as $rule) {
                    if (empty($rule['field']) || empty($rule['operator'])) continue;
                    
                    $field = $rule['field'];
                    $operator = $rule['operator'];
                    $value = $rule['value'] ?? '';

                    $query->where(function ($q) use ($field, $operator, $value) {
                        $isCustom = str_starts_with($field, 'custom_');
                        $dbField = $isCustom ? "LOWER(JSON_UNQUOTE(JSON_EXTRACT(meta, '$.{$field}')))" : "LOWER({$field})";
                        $dbValue = strtolower($value);

                        switch ($operator) {
                            case 'equals':
                                if ($isCustom) $q->whereRaw("$dbField = ?", [$dbValue]);
                                else $q->where($field, 'LIKE', $value);
                                break;
                            case 'not_equals':
                                if ($isCustom) $q->whereRaw("$dbField != ? OR JSON_EXTRACT(meta, '$.{$field}') IS NULL", [$dbValue]);
                                else $q->where($field, 'NOT LIKE', $value)->orWhereNull($field);
                                break;
                            case 'contains':
                                $q->whereRaw("$dbField LIKE ?", ["%{$dbValue}%"]);
                                break;
                            case 'not_contains':
                                $q->whereRaw("$dbField NOT LIKE ? OR $dbField IS NULL", ["%{$dbValue}%"]);
                                break;
                            case 'starts_with':
                                $q->whereRaw("$dbField LIKE ?", ["{$dbValue}%"]);
                                break;
                            case 'ends_with':
                                $q->whereRaw("$dbField LIKE ?", ["%{$dbValue}"]);
                                break;
                            case 'is_empty':
                                if ($isCustom) $q->whereRaw("JSON_EXTRACT(meta, '$.{$field}') IS NULL OR $dbField = ''");
                                else $q->whereNull($field)->orWhere($field, '');
                                break;
                            case 'is_not_empty':
                                if ($isCustom) $q->whereRaw("JSON_EXTRACT(meta, '$.{$field}') IS NOT NULL AND $dbField != ''");
                                else $q->whereNotNull($field)->where($field, '!=', '');
                                break;
                            case 'greater_than':
                                if ($isCustom) $q->whereRaw("CAST(JSON_UNQUOTE(JSON_EXTRACT(meta, '$.{$field}')) AS DECIMAL) > ?", [(float)$value]);
                                else $q->where($field, '>', $value);
                                break;
                            case 'less_than':
                                if ($isCustom) $q->whereRaw("CAST(JSON_UNQUOTE(JSON_EXTRACT(meta, '$.{$field}')) AS DECIMAL) < ?", [(float)$value]);
                                else $q->where($field, '<', $value);
                                break;
                        }
                    });
                }
            }

            $emails = clone $query;
        } else {
            $emails = $emailList->emails()->whereIn('id', $this->ids);
        }

        $emails->chunkById(500, function ($chunk) use ($emailList) {
            foreach ($chunk as $email) {
                switch ($this->actionType) {
                    case 'add_tags':
                        $existingTags = explode(',', $email->tags ?? '');
                        $existingTags = array_map('trim', array_filter($existingTags));
                        $newTags = explode(',', $this->payload['tags'] ?? '');
                        $newTags = array_map('trim', array_filter($newTags));
                        $mergedTags = array_unique(array_merge($existingTags, $newTags));
                        $email->update(['tags' => implode(',', $mergedTags)]);
                        break;

                    case 'remove_tags':
                        $existingTags = explode(',', $email->tags ?? '');
                        $existingTags = array_map('trim', array_filter($existingTags));
                        $removeTags = explode(',', $this->payload['tags'] ?? '');
                        $removeTags = array_map('trim', array_filter($removeTags));
                        $finalTags = array_diff($existingTags, $removeTags);
                        $email->update(['tags' => implode(',', $finalTags)]);
                        break;

                    case 'add_topics':
                        $existingTopics = $email->subscribed_topics ?? [];
                        if (!is_array($existingTopics)) $existingTopics = [];
                        $newTopics = $this->payload['topics'] ?? [];
                        if (!is_array($newTopics)) $newTopics = [];
                        $mergedTopics = array_values(array_unique(array_merge($existingTopics, $newTopics)));
                        $email->update(['subscribed_topics' => $mergedTopics]);
                        break;

                    case 'remove_topics':
                        $existingTopics = $email->subscribed_topics ?? [];
                        if (!is_array($existingTopics)) $existingTopics = [];
                        $removeTopics = $this->payload['topics'] ?? [];
                        if (!is_array($removeTopics)) $removeTopics = [];
                        $finalTopics = array_values(array_diff($existingTopics, $removeTopics));
                        $email->update(['subscribed_topics' => $finalTopics]);
                        break;

                    case 'create_deals':
                        if (!empty($this->payload['pipeline_id']) && !empty($this->payload['stage_id'])) {
                            Deal::create([
                                'email_id' => $email->id,
                                'email_list_id' => $emailList->id,
                                'pipeline_id' => $this->payload['pipeline_id'],
                                'stage_id' => $this->payload['stage_id'],
                                'title' => ($email->name ?: 'Deal for ' . ($email->email ?: $email->whatsapp_number)) . ' Deal',
                                'value' => 0,
                                'currency' => 'USD',
                                'status' => 'open'
                            ]);
                        }
                        break;

                    case 'transfer':
                        if (!empty($this->payload['target_list_id'])) {
                            $email->update(['email_list_id' => $this->payload['target_list_id']]);
                        }
                        break;

                    case 'add_note':
                        if (!empty($this->payload['note'])) {
                            $email->notes()->create([
                                'user_id' => $this->userId,
                                'note' => $this->payload['note']
                            ]);
                        }
                        break;

                    case 'add_task':
                        if (!empty($this->payload['title'])) {
                            $email->tasks()->create([
                                'user_id' => $this->userId,
                                'title' => $this->payload['title'],
                                'description' => $this->payload['description'] ?? null,
                                'due_date' => $this->payload['due_date'] ?? null,
                                'status' => 'pending'
                            ]);
                        }
                        break;
                }
            }
        });

        // Recalculate stats for the current list
        $emailList->recalculateStats();

        // If transfer, recalculate stats for the target list
        if ($this->actionType === 'transfer' && !empty($this->payload['target_list_id'])) {
            $targetList = EmailList::find($this->payload['target_list_id']);
            if ($targetList) {
                $targetList->recalculateStats();
            }
        }
    }
}
