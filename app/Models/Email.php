<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Email extends Model
{
    use \App\Traits\BelongsToUser;

    protected $fillable = [
        'user_id',
        'email_list_id',
        'original_row_id',
        'activity_log_id',
        'email',
        'name',
        'status',
        'email_status',
        'email_score',
        'email_risk_level',
        'is_role_based',
        'is_disposable',
        'is_catch_all',
        'has_typo',
        'validation_reason',
        'subscription_status',
        'is_archived',
        'archived_at',
        'signup_source',
        'segment_name',
        'reason',
        'meta',
        'tags',
        'last_active_at',
        'unsubscribed_at',
        'unsubscribe_expires_at',
        'engagement_score',
        'email_lead_score',
        'whatsapp_lead_score',
        'last_engaged_at',
        'whatsapp_number',
        'whatsapp_opt_in',
        'whatsapp_subscription_status',
        'whatsapp_unsubscribed_at',
        'whatsapp_last_message_at',
        'last_campaign_status',
        'bounce_count',
        'complaint_count',
        'last_bounce_type',
        'subscribed_topics',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
            'tags' => 'array',
            'subscribed_topics' => 'array',
            'is_archived' => 'boolean',
            'archived_at' => 'datetime',
            'unsubscribed_at' => 'datetime',
            'unsubscribe_expires_at' => 'datetime',
            'last_active_at' => 'datetime',
            'last_engaged_at' => 'datetime',
            'engagement_score' => 'integer',
            'email_lead_score' => 'integer',
            'whatsapp_lead_score' => 'integer',
        ];
    }

    protected static function booted()
    {
        static::created(function ($email) {
            \App\Jobs\UpdateContactSegmentsJob::dispatch($email->id);
            \App\Jobs\CalculateLeadScoreJob::dispatch($email->id);
        });

        static::updated(function ($email) {
            if ($email->isDirty(['status', 'email_status', 'email_score', 'subscription_status', 'bounce_count', 'complaint_count', 'whatsapp_number', 'whatsapp_opt_in', 'whatsapp_subscription_status', 'email'])) {
                \App\Jobs\UpdateContactSegmentsJob::dispatch($email->id);
                \App\Jobs\CalculateLeadScoreJob::dispatch($email->id);
            }

            if ($email->isDirty(['subscription_status', 'email_status'])) {
                $unsubscribed = $email->subscription_status === 'unsubscribed';
                $bouncedOrComplaint = in_array($email->email_status, ['hard_bounce', 'soft_bounce', 'bounce', 'complaint']);
                if ($unsubscribed || $bouncedOrComplaint) {
                    $email->sequenceEnrollments()->where('status', 'active')->update(['status' => 'cancelled']);
                }
            }

            if ($email->isDirty('subscribed_topics')) {
                $oldTopics = $email->getOriginal('subscribed_topics') ?: [];
                $newTopics = $email->subscribed_topics ?: [];
                $added = array_diff(array_map('strval', $newTopics), array_map('strval', $oldTopics));
                foreach ($added as $topicId) {
                    \App\Models\Workflow::trigger('topic_subscribe', $email, $topicId);
                }
            }

            if ($email->isDirty('tags')) {
                $oldTags = $email->getOriginal('tags') ?: [];
                $newTags = $email->tags ?: [];
                $added = array_diff($newTags, $oldTags);
                foreach ($added as $tag) {
                    \App\Models\Workflow::trigger('tag_added', $email, $tag);
                }
            }
        });
    }

    /**
     * Legacy unsubscribe method. 
     * @deprecated Use the tracking_token from EmailLog instead.
     */
    public function getUnsubscribeUrl(?int $logId = null): string
    {
        if ($logId) {
            $log = EmailLog::find($logId);
            if ($log && $log->tracking_token) {
                return route('unsubscribe', ['token' => $log->tracking_token]);
            }
        }

        // Fallback for tests or manual links
        $token = hash_hmac('sha256', $this->id . $this->email, config('app.key'));
        return route('unsubscribe', ['token' => $token]);
    }

    public function scopeSubscribed($query)
    {
        return $query->where('subscription_status', 'subscribed');
    }

    public function emailList(): BelongsTo
    {
        return $this->belongsTo(EmailList::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(EmailLog::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(ContactActivity::class)->latest();
    }

    public function notes(): HasMany
    {
        return $this->hasMany(ContactNote::class)->latest();
    }

    public function deals(): HasMany
    {
        return $this->hasMany(Deal::class, 'email_id');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(ContactTask::class, 'email_id');
    }

    public function sequenceEnrollments(): HasMany
    {
        return $this->hasMany(SequenceEnrollment::class, 'email_id');
    }

    public function scopeValid($query)
    {
        return $query->where('status', 'valid');
    }

    public function scopeInvalid($query)
    {
        return $query->where('status', 'invalid');
    }

    public function scopeDuplicate($query)
    {
        return $query->where('status', 'duplicate');
    }

    public function scopeCrossDuplicate($query)
    {
        return $query->where('status', 'cross_duplicate');
    }
}
