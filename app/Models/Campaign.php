<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\DB;

class Campaign extends Model
{
    use \App\Traits\BelongsToUser;

    protected $fillable = [
        'name',
        'from_name',
        'subject',
        'email_list_id',
        'template_id',
        'sender_id',
        'status',
        'scheduled_at',
        'started_at',
        'completed_at',
        'total_recipients',
        'sent_count',
        'failed_count',
        'emails_per_minute',
        'batch_size',
        'bounce_count',
        'audience_config',
        'subscription_topic_id',
    ];

    protected $appends = [
        'from_email',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at'  => 'datetime',
            'started_at'    => 'datetime',
            'completed_at'  => 'datetime',
            'audience_config' => 'array',
        ];
    }

    public function getFromEmailAttribute(): ?string
    {
        return $this->sender?->email;
    }

    public function subscriptionTopic(): BelongsTo
    {
        return $this->belongsTo(SubscriptionTopic::class);
    }

    public function emailList(): BelongsTo
    {
        return $this->belongsTo(EmailList::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(Template::class);
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(Sender::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(EmailLog::class);
    }

    public function unsubscribes(): HasMany
    {
        return $this->hasMany(Unsubscribe::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(ContactActivity::class);
    }

    public function emailEvents(): \Illuminate\Database\Eloquent\Relations\HasManyThrough
    {
        return $this->hasManyThrough(EmailEvent::class, EmailLog::class);
    }

    public function getCachedStats(): object
    {
        $key = "campaign_stats:{$this->id}";
        $cached = Redis::get($key);
        if ($cached) {
            $decoded = json_decode($cached);
            if ($decoded) {
                return $decoded;
            }
        }

        $stats = DB::table('email_logs')
            ->where('campaign_id', $this->id)
            ->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN status IN ('sent','delivered') THEN 1 ELSE 0 END) as sent,
                SUM(CASE WHEN status IN ('failed') THEN 1 ELSE 0 END) as failed,
                SUM(CASE WHEN status = 'bounced' THEN 1 ELSE 0 END) as bounced,
                SUM(CASE WHEN status IN ('complaint','spamreport') THEN 1 ELSE 0 END) as complaints,
                SUM(CASE WHEN open_count > 0 OR click_count > 0 THEN 1 ELSE 0 END) as unique_opens,
                SUM(CASE WHEN click_count > 0 THEN 1 ELSE 0 END) as unique_clicks,
                SUM(CASE WHEN status IN ('sent','delivered','failed','bounced','complaint','spamreport','dropped') THEN 1 ELSE 0 END) as processed
            ")->first();

        Redis::setex($key, 30, json_encode($stats));
        return $stats;
    }

    public function successRate(): float
    {
        $stats = $this->getCachedStats();
        $sent = (int) $stats->sent;
        $failed = (int) $stats->failed + (int) $stats->bounced;
        
        if ($sent + $failed === 0) return 0;
        return round(($sent / ($sent + $failed)) * 100, 1);
    }

    public function failureRate(): float
    {
        $stats = $this->getCachedStats();
        $sent = (int) $stats->sent;
        $failed = (int) $stats->failed + (int) $stats->bounced;
        
        if ($sent + $failed === 0) return 0;
        return round(($failed / ($sent + $failed)) * 100, 1);
    }

    public function progress(): float
    {
        if ($this->total_recipients === 0) return 0;
        
        $stats = $this->getCachedStats();
        $reached = (int) $stats->processed;
        return round(($reached / $this->total_recipients) * 100, 1);
    }

    public function getOpenRateAttribute(): float
    {
        return $this->openRate();
    }

    public function getClickRateAttribute(): float
    {
        return $this->clickRate();
    }

    public function openRate(): float
    {
        $stats = $this->getCachedStats();
        $deliveredCount = (int) $stats->sent;
        if ($deliveredCount === 0) return 0;

        $uniqueOpens = (int) $stats->unique_opens;
        return round(($uniqueOpens / $deliveredCount) * 100, 1);
    }

    public function clickRate(): float
    {
        $stats = $this->getCachedStats();
        $deliveredCount = (int) $stats->sent;
        if ($deliveredCount === 0) return 0;

        $uniqueClicks = (int) $stats->unique_clicks;
        return round(($uniqueClicks / $deliveredCount) * 100, 1);
    }

    public function bounceRate(): float
    {
        if ($this->total_recipients === 0) return 0;
        $stats = $this->getCachedStats();
        $bounces = (int) $stats->bounced;
        return round(($bounces / $this->total_recipients) * 100, 1);
    }

    public function complaintRate(): float
    {
        if ($this->total_recipients === 0) return 0;
        $stats = $this->getCachedStats();
        $complaints = (int) $stats->complaints;
        return round(($complaints / $this->total_recipients) * 100, 1);
    }

    public function currentSpeed(): float
    {
        $key = "campaign_{$this->id}_speed";
        $timestamps = \Illuminate\Support\Facades\Redis::lrange($key, 0, -1);
        
        if (count($timestamps) < 2) return 0;

        $first = (float) end($timestamps);
        $last = (float) reset($timestamps);
        $diff = $last - $first;

        if ($diff <= 0) return 0;

        return round(count($timestamps) / $diff, 1);
    }

    public function estimatedCompletion(): ?string
    {
        if ($this->status !== 'sending') return null;

        $speed = $this->currentSpeed();
        if ($speed <= 0) return 'Calculating...';

        $remaining = $this->total_recipients - ($this->sent_count + $this->failed_count);
        if ($remaining <= 0) return 'Finishing...';

        $seconds = (int) ($remaining / $speed);
        
        if ($seconds < 60) return $seconds . 's';
        if ($seconds < 3600) return ceil($seconds / 60) . 'm';
        return ceil($seconds / 3600) . 'h';
    }

    public function estimatedCost(): float
    {
        return $this->total_recipients * config('emailplatform.cost_per_email');
    }

    public function getAudienceQueryBuilder()
    {
        $config = $this->audience_config ?? [];
        $listIds = $config['list_ids'] ?? [];
        
        // Backward compatibility
        if (empty($listIds) && $this->email_list_id) {
            $listIds = [$this->email_list_id];
        }

        if (empty($listIds)) {
            return null;
        }

        $query = \App\Models\Email::query()
            ->whereIn('email_list_id', $listIds)
            ->valid()
            ->subscribed()
            ->where(function($q) {
                $q->where('is_archived', false)->orWhereNull('is_archived');
            })
            ->whereNotNull('email')
            ->where('email', '!=', '');

        if ($this->subscription_topic_id) {
            $topicId = $this->subscription_topic_id;
            $query->where(function ($q) use ($topicId) {
                $q->whereNull('subscribed_topics')
                  ->orWhereJsonContains('subscribed_topics', $topicId);
            });
        }

        if ($config) {
            if (isset($config['exclude_unhealthy']) && $config['exclude_unhealthy']) {
                $query->where(function($q) {
                    $q->whereNotIn('email_status', ['hard_bounce', 'complaint', 'invalid', 'blocked'])
                      ->orWhereNull('email_status');
                });
            }

            if (isset($config['exclude_risky']) && $config['exclude_risky']) {
                $query->where(function($q) {
                    $q->where('email_status', '!=', 'risky')
                      ->orWhereNull('email_status');
                });
            }
            if (isset($config['exclude_disposable']) && $config['exclude_disposable']) {
                $query->where('is_disposable', false);
            }
            if (isset($config['exclude_role_based']) && $config['exclude_role_based']) {
                $query->where('is_role_based', false);
            }

            // Legacy backward compatibility for old campaigns
            if (isset($config['type']) && $config['type'] === 'segment' && !empty($config['tag'])) {
                [$type, $value] = explode(':', $config['tag'], 2);
                if ($type === 'tag') {
                    $config['include_tags'] = array_merge($config['include_tags'] ?? [], [$value]);
                } elseif ($type === 'segment') {
                    $config['include_segments'] = array_merge($config['include_segments'] ?? [], [$value]);
                }
            }

            // --- INCLUDES (OR logic: match AT LEAST ONE of the tags/segments) ---
            $includeTags = $config['include_tags'] ?? [];
            $includeSegments = $config['include_segments'] ?? [];
            
            if (!empty($includeTags) || !empty($includeSegments)) {
                $query->where(function($q) use ($includeTags, $includeSegments, $listIds) {
                    foreach ($includeTags as $tag) {
                        $q->orWhere('tags', 'LIKE', "%\"{$tag}\"%")
                          ->orWhere('tags', 'LIKE', "%{$tag}%");
                    }
                    foreach ($includeSegments as $seg) {
                        $segmentModel = \App\Models\Segment::where(function ($q) use ($listIds) {
                            $q->whereIn('email_list_id', $listIds)
                              ->orWhereNull('email_list_id');
                        })->where('name', $seg)->first();
                        $q->orWhere(function($sub) use ($seg, $segmentModel) {
                            if ($segmentModel) {
                                \App\Models\Segment::applyRulesToQuery($sub, $segmentModel->rules ?? []);
                            } else {
                                $sub->where('segment_name', $seg);
                            }
                        });
                    }
                });
            }

            // --- EXCLUDES (AND logic: MUST NOT match ANY of the excluded tags/segments) ---
            $excludeTags = $config['exclude_tags'] ?? [];
            $excludeSegments = $config['exclude_segments'] ?? [];

            if (!empty($excludeTags) || !empty($excludeSegments)) {
                $query->where(function($q) use ($excludeTags, $excludeSegments, $listIds) {
                    foreach ($excludeTags as $tag) {
                        $q->where(function($subQ) use ($tag) {
                            $subQ->whereNull('tags')
                                 ->orWhere(function($sub2) use ($tag) {
                                     $sub2->where('tags', 'NOT LIKE', "%\"{$tag}\"%")
                                          ->where('tags', 'NOT LIKE', "%{$tag}%");
                                 });
                        });
                    }
                    foreach ($excludeSegments as $seg) {
                        $segmentModel = \App\Models\Segment::where(function ($q) use ($listIds) {
                            $q->whereIn('email_list_id', $listIds)
                              ->orWhereNull('email_list_id');
                        })->where('name', $seg)->first();
                        $q->where(function($subQ) use ($seg, $segmentModel) {
                            if ($segmentModel) {
                                // Exclude those who match the segment rules
                                $subQ->whereNot(function($s1) use ($segmentModel) {
                                    \App\Models\Segment::applyRulesToQuery($s1, $segmentModel->rules ?? []);
                                });
                            } else {
                                $subQ->where(function($s1) use ($seg) {
                                    $s1->where('segment_name', '!=', $seg)
                                       ->orWhereNull('segment_name');
                                });
                            }
                        });
                    }
                });
            }
        }

        return $query;
    }

    public function getEstimatedRecipientCount(): int
    {
        $query = $this->getAudienceQueryBuilder();
        if (!$query) {
            return 0;
        }

        $count = $query->count();
        $limit = isset($this->audience_config['limit']) && (int) $this->audience_config['limit'] > 0 
            ? (int) $this->audience_config['limit'] 
            : null;

        if ($limit) {
            $count = min($count, $limit);
        }

        return $count;
    }
}
