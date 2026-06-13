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
    protected $activityLogId;

    public function __construct($emailListId, $actionType, $isGlobal, $filters, $ids, $payload, $userId, $activityLogId = null)
    {
        $this->emailListId = $emailListId;
        $this->actionType = $actionType;
        $this->isGlobal = $isGlobal;
        $this->filters = $filters;
        $this->ids = $ids;
        $this->payload = $payload;
        $this->userId = $userId;
        $this->activityLogId = $activityLogId;
    }

    public function handle()
    {
        DB::connection()->disableQueryLog();

        $emailList = EmailList::find($this->emailListId);
        if (!$emailList)
            return;

        // Build the base query
        if ($this->isGlobal && $this->filters) {
            $query = clone $emailList->emails();

            // Re-apply filters manually since we don't have access to the controller
            $filters = $this->filters;
            // Normalize filter values coming from frontend
            foreach ($filters as $key => $value) {

                if (is_array($value)) {

                    // Empty array => null
                    if (count($value) === 0) {
                        $filters[$key] = null;
                        continue;
                    }

                    // Single value array => first value
                    if (count($value) === 1) {
                        $filters[$key] = reset($value);
                    }
                }
            }

            if (isset($filters['status']) && $filters['status'] !== 'all')
                $query->where('status', $filters['status']);
            if (!empty($filters['subscription']) && $filters['subscription'] !== 'all')
                $query->where('subscription_status', $filters['subscription']);
            if (!empty($filters['segment']) && $filters['segment'] !== 'all') {
                $segments = is_array($filters['segment']) ? $filters['segment'] : [$filters['segment']];
                $query->where(function($q) use ($segments, $emailList) {
                    foreach ($segments as $value) {
                        $segmentModel = \App\Models\Segment::where(function($sq) use ($emailList) {
                                $sq->whereNull('email_list_id')->orWhere('email_list_id', $emailList->id);
                            })->where('name', $value)->first();

                        if ($segmentModel) {
                            $q->orWhere(function($subQ) use ($segmentModel) {
                                \App\Models\Segment::applyRulesToQuery($subQ, $segmentModel->rules ?? []);
                            });
                        } else {
                            $q->orWhere('segment_name', $value);
                        }
                    }
                });
            }
            if (!empty($filters['tag']) && $filters['tag'] !== 'all') {

                if (is_array($filters['tag'])) {

                    $query->where(function ($q) use ($filters) {

                        foreach ($filters['tag'] as $tag) {

                            if (is_array($tag)) {
                                $tag = $tag['value'] ?? $tag['label'] ?? null;
                            }

                            if (!$tag) {
                                continue;
                            }

                            $q->orWhere('tags', 'like', '%' . $tag . '%');
                        }
                    });

                } else {

                    $query->where('tags', 'like', '%' . $filters['tag'] . '%');
                }
            }
            if (!empty($filters['source']) && $filters['source'] !== 'all')
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
                    if (empty($rule['field']) || empty($rule['operator']))
                        continue;

                    $field = $rule['field'];
                    $operator = $rule['operator'];
                    $value = $rule['value'] ?? '';

                    $query->where(function ($q) use ($field, $operator, $value) {
                        $isCustom = str_starts_with($field, 'custom_');
                        $dbField = $isCustom ? "LOWER(JSON_UNQUOTE(JSON_EXTRACT(meta, '$.{$field}')))" : "LOWER({$field})";
                        $dbValue = strtolower($value);

                        switch ($operator) {
                            case 'equals':
                                if ($isCustom)
                                    $q->whereRaw("$dbField = ?", [$dbValue]);
                                else
                                    $q->where($field, 'LIKE', $value);
                                break;
                            case 'not_equals':
                                if ($isCustom)
                                    $q->whereRaw("$dbField != ? OR JSON_EXTRACT(meta, '$.{$field}') IS NULL", [$dbValue]);
                                else
                                    $q->where($field, 'NOT LIKE', $value)->orWhereNull($field);
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
                                if ($isCustom)
                                    $q->whereRaw("JSON_EXTRACT(meta, '$.{$field}') IS NULL OR $dbField = ''");
                                else
                                    $q->whereNull($field)->orWhere($field, '');
                                break;
                            case 'is_not_empty':
                                if ($isCustom)
                                    $q->whereRaw("JSON_EXTRACT(meta, '$.{$field}') IS NOT NULL AND $dbField != ''");
                                else
                                    $q->whereNotNull($field)->where($field, '!=', '');
                                break;
                            case 'greater_than':
                                if ($isCustom)
                                    $q->whereRaw("CAST(JSON_UNQUOTE(JSON_EXTRACT(meta, '$.{$field}')) AS DECIMAL) > ?", [(float) $value]);
                                else
                                    $q->where($field, '>', $value);
                                break;
                            case 'less_than':
                                if ($isCustom)
                                    $q->whereRaw("CAST(JSON_UNQUOTE(JSON_EXTRACT(meta, '$.{$field}')) AS DECIMAL) < ?", [(float) $value]);
                                else
                                    $q->where($field, '<', $value);
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
                        $existingTags = is_array($email->tags) ? $email->tags : [];
                        $newTags = is_array($this->payload['tags']) ? $this->payload['tags'] : (is_string($this->payload['tags'] ?? null) ? explode(',', $this->payload['tags']) : []);
                        $newTags = array_map('trim', array_filter($newTags));
                        $mergedTags = array_values(array_unique(array_merge($existingTags, $newTags)));
                        $email->update(['tags' => $mergedTags]);
                        break;

                    case 'remove_tags':
                        $existingTags = is_array($email->tags) ? $email->tags : [];
                        $removeTags = is_array($this->payload['tags']) ? $this->payload['tags'] : (is_string($this->payload['tags'] ?? null) ? explode(',', $this->payload['tags']) : []);
                        $removeTags = array_map('trim', array_filter($removeTags));
                        $finalTags = array_values(array_diff($existingTags, $removeTags));
                        $email->update(['tags' => $finalTags]);
                        break;

                    case 'replace_tags':
                        $newTags = is_array($this->payload['tags']) ? $this->payload['tags'] : (is_string($this->payload['tags'] ?? null) ? explode(',', $this->payload['tags']) : []);
                        $newTags = array_map('trim', array_filter($newTags));
                        $email->update(['tags' => array_values(array_unique($newTags))]);
                        break;

                    case 'manage_subscriptions':
                        $newTopics = $this->payload['topics'] ?? [];
                        if (!is_array($newTopics))
                            $newTopics = [];

                        if (empty($newTopics)) {
                            $email->update([
                                'subscribed_topics' => [],
                                'subscription_status' => 'unsubscribed',
                            ]);
                        } else {
                            $email->update([
                                'subscribed_topics' => array_values(array_unique($newTopics)),
                                'subscription_status' => 'subscribed',
                            ]);
                        }
                        break;

                    case 'create_deals':
                        if (!empty($this->payload['pipeline_id']) && !empty($this->payload['stage_id'])) {
                            $targetUserId = $this->userId;
                            $userModel = \App\Models\User::find($this->userId);
                            if ($userModel) {
                                $targetUserId = $userModel->getOwnerId();
                            }
                            Deal::create([
                                'email_id' => $email->id,
                                'pipeline_stage_id' => $this->payload['stage_id'],
                                'title' => ($email->name ?: 'Deal for ' . ($email->email ?: $email->whatsapp_number)) . ' Deal',
                                'value' => 0,
                                'currency' => 'USD',
                                'status' => 'open',
                                'user_id' => $targetUserId,
                            ]);
                        }
                        break;

                    case 'transfer':
                        if (!empty($this->payload['target_list_id'])) {
                            $targetListId = $this->payload['target_list_id'];
                            $targetList = \App\Models\EmailList::find($targetListId);
                            $targetListUserId = $targetList ? $targetList->user_id : null;

                            // Fetch target list's topics or seed defaults if none exist
                            $targetTopicIds = \App\Models\SubscriptionTopic::withoutGlobalScopes()
                                ->where('email_list_id', $targetListId)
                                ->pluck('id')
                                ->map('strval')
                                ->toArray();

                            if (empty($targetTopicIds) && $targetList) {
                                \App\Models\SubscriptionTopic::seedDefaultsFor($targetList->id, $targetList->user_id);
                                $targetTopicIds = \App\Models\SubscriptionTopic::withoutGlobalScopes()
                                    ->where('email_list_id', $targetList->id)
                                    ->pluck('id')
                                    ->map('strval')
                                    ->toArray();
                            }

                            // Fetch all associated emails in the same group sharing original_row_id
                            $emailsToTransfer = collect([$email]);
                            if (!empty($email->original_row_id)) {
                                $subRows = \App\Models\Email::where('email_list_id', $email->email_list_id)
                                    ->where('original_row_id', $email->original_row_id)
                                    ->where('id', '!=', $email->id)
                                    ->get();
                                $emailsToTransfer = $emailsToTransfer->merge($subRows);
                            }

                            // Move contact and alternate channels
                            foreach ($emailsToTransfer as $e) {
                                $e->update([
                                    'email_list_id' => $targetListId,
                                    'user_id' => $targetListUserId ?? $e->user_id,
                                    'subscribed_topics' => $targetTopicIds,
                                    'meta' => [], // reset custom columns
                                ]);
                            }
                        }
                        break;

                    case 'enroll_sequence':
                        if (!empty($this->payload['sequence_id'])) {
                            $sequenceId = $this->payload['sequence_id'];
                            $sequence = \App\Models\Sequence::find($sequenceId);
                            if ($sequence) {
                                $firstStep = $sequence->steps()->where('step_number', 1)->first();
                                if ($firstStep) {
                                    $delay = $firstStep->delay_days;
                                    $scheduledAt = now()->addDays($delay);

                                    $existing = \App\Models\SequenceEnrollment::where('sequence_id', $sequence->id)
                                        ->where('email_id', $email->id)
                                        ->first();

                                    if ($existing) {
                                        if ($existing->status !== 'active') {
                                            $existing->update([
                                                'status' => 'active',
                                                'current_step_number' => 1,
                                                'scheduled_at' => $scheduledAt,
                                            ]);
                                        }
                                    } else {
                                        \App\Models\SequenceEnrollment::create([
                                            'sequence_id' => $sequence->id,
                                            'email_id' => $email->id,
                                            'current_step_number' => 1,
                                            'status' => 'active',
                                            'scheduled_at' => $scheduledAt,
                                        ]);
                                    }
                                }
                            }
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

                    case 'unsubscribe':
                        $duration = $this->payload['duration'] ?? 'forever';
                        $expiresAt = null;
                        if ($duration !== 'forever') {
                            $days = (int) $duration;
                            if ($days > 0) {
                                $expiresAt = now()->addDays($days);
                            }
                        }
                        $email->update([
                            'subscription_status' => 'unsubscribed',
                            'unsubscribed_at' => now(),
                            'unsubscribe_expires_at' => $expiresAt
                        ]);
                        break;

                    case 'subscribe':
                        $email->update([
                            'subscription_status' => 'subscribed',
                            'unsubscribed_at' => null,
                            'unsubscribe_expires_at' => null
                        ]);
                        break;

                    case 'archive':
                        $email->update(['is_archived' => true, 'archived_at' => now()]);
                        break;

                    case 'unarchive':
                        $email->update(['is_archived' => false, 'archived_at' => null]);
                        break;

                    case 'update_column':
                    case 'edit_column':
                        $column = $this->payload['column'] ?? null;
                        $value = $this->payload['value'] ?? '';
                        if ($column) {
                            if (in_array($column, ['name', 'company', 'job_title', 'phone', 'city', 'tags', 'country'])) {
                                $email->update([$column => $value]);
                            } else if (str_starts_with($column, 'custom_')) {
                                $meta = $email->meta ?? [];
                                $meta[$column] = $value;
                                $email->update(['meta' => $meta]);
                            }
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

        // Reset list status and update activity log
        $emailList->update(['status' => 'completed']);
        if ($this->activityLogId) {
            $log = \App\Models\ActivityLog::find($this->activityLogId);
            if ($log) {
                $details = $log->details ?? [];
                $details['status'] = 'completed';
                $details['finished_at'] = now()->toDateTimeString();
                $log->update(['details' => $details]);
            }
        }
    }
}
