<?php

namespace App\Jobs;

use App\Models\EmailList;
use App\Models\ActivityLog;
use App\Exports\ContactsExport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ExportContactsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 3600;

    protected $emailListId;
    protected $filters;
    protected $userId;
    protected $activityLogId;

    public function __construct($emailListId, $filters, $userId, $activityLogId)
    {
        $this->emailListId = $emailListId;
        $this->filters = $filters;
        $this->userId = $userId;
        $this->activityLogId = $activityLogId;
    }

    public function handle()
    {
        DB::connection()->disableQueryLog();

        $emailList = EmailList::find($this->emailListId);
        if (!$emailList) {
            return;
        }

        $log = ActivityLog::find($this->activityLogId);
        if (!$log) {
            return;
        }

        try {
            $query = $emailList->emails()->orderBy('created_at', 'desc');

            $filters = $this->filters;

            // Re-apply filters manually (matching applyFiltersToQuery in EmailListController.php)
            if ($filters) {
                // Normalize filters
                foreach ($filters as $key => $value) {
                    if (is_array($value)) {
                        if (count($value) === 0) {
                            $filters[$key] = null;
                            continue;
                        }
                        if (count($value) === 1) {
                            $filters[$key] = reset($value);
                        }
                    }
                }

                if (isset($filters['added_by']) && $filters['added_by'] !== 'all' && (!is_array($filters['added_by']) || count($filters['added_by']) > 0)) {
                    $addedBy = is_array($filters['added_by']) ? $filters['added_by'] : [$filters['added_by']];
                    $query->whereIn('user_id', $addedBy);
                }

                if (isset($filters['status']) && $filters['status'] !== 'all' && (!is_array($filters['status']) || count($filters['status']) > 0)) {
                    $statuses = is_array($filters['status']) ? $filters['status'] : [$filters['status']];
                    $query->where(function($q) use ($statuses) {
                        foreach ($statuses as $st) {
                            if ($st === 'role_based') $q->orWhere('is_role_based', true);
                            elseif ($st === 'disposable') $q->orWhere('is_disposable', true);
                            elseif (in_array($st, ['risky', 'suspicious', 'cross_duplicate'])) $q->orWhere('email_status', $st);
                            else $q->orWhere('status', $st);
                        }
                    });
                }

                if (isset($filters['subscription']) && $filters['subscription'] !== 'all' && (!is_array($filters['subscription']) || count($filters['subscription']) > 0)) {
                    $subs = is_array($filters['subscription']) ? $filters['subscription'] : [$filters['subscription']];
                    $query->where(function($q) use ($subs) {
                        foreach ($subs as $sub) {
                            if (in_array($sub, ['hard_bounce', 'soft_bounce', 'complaint'])) {
                                $q->orWhere('email_status', $sub);
                            } else {
                                $q->orWhere('subscription_status', $sub);
                            }
                        }
                    });
                }

                if (isset($filters['archived']) && $filters['archived'] !== 'all' && (!is_array($filters['archived']) || count($filters['archived']) > 0)) {
                    $archives = is_array($filters['archived']) ? $filters['archived'] : [$filters['archived']];
                    if (in_array('yes', $archives) && !in_array('no', $archives)) {
                        $query->where('is_archived', true);
                    } elseif (in_array('no', $archives) && !in_array('yes', $archives)) {
                        $query->where('is_archived', false);
                    }
                }

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

                if (isset($filters['topic']) && $filters['topic'] !== 'all' && (!is_array($filters['topic']) || count($filters['topic']) > 0)) {
                    $topics = is_array($filters['topic']) ? $filters['topic'] : [$filters['topic']];
                    $query->where(function($q) use ($topics) {
                        foreach ($topics as $t) {
                            $q->orWhereJsonContains('subscribed_topics', (string) $t)
                              ->orWhereJsonContains('subscribed_topics', (int) $t);
                        }
                    });
                }

                if (isset($filters['source']) && $filters['source'] !== 'all' && (!is_array($filters['source']) || count($filters['source']) > 0)) {
                    $sources = is_array($filters['source']) ? $filters['source'] : [$filters['source']];
                    $query->whereIn('signup_source', $sources);
                }

                if (isset($filters['tag']) && $filters['tag'] !== 'all' && (!is_array($filters['tag']) || count($filters['tag']) > 0)) {
                    $tags = is_array($filters['tag']) ? $filters['tag'] : [$filters['tag']];
                    $query->where(function($q) use ($tags) {
                        foreach ($tags as $t) {
                            $q->orWhereJsonContains('tags', $t);
                        }
                    });
                }

                if (isset($filters['channel']) && $filters['channel'] !== 'all' && (!is_array($filters['channel']) || count($filters['channel']) > 0)) {
                    $channels = is_array($filters['channel']) ? $filters['channel'] : [$filters['channel']];
                    if (in_array('only_email', $channels) && !in_array('only_whatsapp', $channels)) {
                        $query->whereNotNull('email')->where('email', '!=', '');
                    } elseif (in_array('only_whatsapp', $channels) && !in_array('only_email', $channels)) {
                        $query->whereNotNull('whatsapp_number')->where('whatsapp_number', '!=', '');
                    }
                }

                if (isset($filters['wa_status']) && $filters['wa_status'] !== 'all' && (!is_array($filters['wa_status']) || count($filters['wa_status']) > 0)) {
                    $waStatuses = is_array($filters['wa_status']) ? $filters['wa_status'] : [$filters['wa_status']];
                    $query->whereIn('whatsapp_subscription_status', $waStatuses);
                }

                if (!empty($filters['search'])) {
                    $search = $filters['search'];
                    $field = $filters['search_field'] ?? 'name';
                    $query->where(function ($q) use ($search, $field) {
                        if ($field === 'email') {
                            $q->whereRaw("LOWER(email) LIKE ?", ["%" . strtolower($search) . "%"]);
                        } elseif ($field === 'name') {
                            $q->whereRaw("LOWER(name) LIKE ?", ["%" . strtolower($search) . "%"]);
                        } elseif ($field === 'whatsapp_number') {
                            $q->whereRaw("LOWER(whatsapp_number) LIKE ?", ["%" . strtolower($search) . "%"]);
                        } elseif ($field === 'segment') {
                            $q->whereRaw("LOWER(segment_name) LIKE ?", ["%" . strtolower($search) . "%"]);
                        } elseif ($field === 'tag') {
                            $q->whereRaw("LOWER(tags) LIKE ?", ["%" . strtolower($search) . "%"]);
                        } elseif ($field === 'source') {
                            $q->whereRaw("LOWER(signup_source) LIKE ?", ["%" . strtolower($search) . "%"]);
                        } else {
                            $q->whereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(meta, '$.{$field}'))) LIKE ?", ["%" . strtolower($search) . "%"]);
                        }
                    });
                }

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
            }

            // Consolidate if configured
            $isConsolidated = ($filters['consolidate'] ?? '0') === '1' || ($filters['consolidate'] ?? false) === true;
            if ($isConsolidated) {
                $query->selectRaw('ANY_VALUE(original_row_id) as original_row_id')
                      ->selectRaw('ANY_VALUE(name) as name')
                      ->selectRaw('ANY_VALUE(meta) as meta')
                      ->selectRaw('ANY_VALUE(created_at) as created_at')
                      ->selectRaw('ANY_VALUE(tags) as tags')
                      ->selectRaw('ANY_VALUE(email_status) as email_status')
                      ->selectRaw('ANY_VALUE(status) as status')
                      ->selectRaw('ANY_VALUE(validation_reason) as validation_reason')
                      ->selectRaw('ANY_VALUE(reason) as reason')
                      ->selectRaw('GROUP_CONCAT(DISTINCT email SEPARATOR ", ") as email')
                      ->selectRaw('GROUP_CONCAT(DISTINCT whatsapp_number SEPARATOR ", ") as whatsapp_number')
                      ->groupBy(\Illuminate\Support\Facades\DB::raw("CASE WHEN name IS NOT NULL AND TRIM(name) != '' THEN CONCAT('name_', LOWER(TRIM(name))) ELSE COALESCE(original_row_id, CAST(id AS CHAR)) END"))
                      ->reorder()
                      ->orderByRaw('ANY_VALUE(created_at) DESC');
            }

            // Export using existing ContactsExport and Excel::store
            $mapping = $emailList->column_mapping ?? [];
            $internalFields = ['email', 'name', 'whatsapp_number', 'phone', 'segment_name', 'signup_source', '_settings'];
            $extraFields = [];
            foreach ($mapping as $field => $index) {
                if (!in_array($field, $internalFields) && is_string($field)) {
                    $extraFields[] = $field;
                }
            }

            $topicsMap = \App\Models\SubscriptionTopic::where('email_list_id', $emailList->id)
                ->pluck('name', 'id')
                ->toArray();

            $filename = $log->details['filename'];
            
            \Maatwebsite\Excel\Facades\Excel::store(
                new ContactsExport($query, $extraFields, $topicsMap),
                'exports/' . $filename,
                'local'
            );

            $details = $log->details ?? [];
            $details['status'] = 'completed';
            $details['finished_at'] = now()->toDateTimeString();
            $log->update(['details' => $details]);

        } catch (\Exception $e) {
            Log::error("CRITICAL: ExportContactsJob failed", [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $details = $log->details ?? [];
            $details['status'] = 'failed';
            $details['error'] = $e->getMessage();
            $details['finished_at'] = now()->toDateTimeString();
            $log->update(['details' => $details]);

            throw $e;
        }
    }
}
