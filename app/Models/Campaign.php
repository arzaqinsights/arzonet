<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Campaign extends Model
{
    use \App\Traits\BelongsToUser;

    protected $fillable = [
        'name',
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

    public function successRate(): float
    {
        $sent = $this->logs()->where('status', 'sent')->count();
        $failed = $this->logs()->whereIn('status', ['failed', 'bounced'])->count();
        
        if ($sent + $failed === 0) return 0;
        return round(($sent / ($sent + $failed)) * 100, 1);
    }

    public function failureRate(): float
    {
        $sent = $this->logs()->where('status', 'sent')->count();
        $failed = $this->logs()->whereIn('status', ['failed', 'bounced'])->count();
        
        if ($sent + $failed === 0) return 0;
        return round(($failed / ($sent + $failed)) * 100, 1);
    }

    public function progress(): float
    {
        if ($this->total_recipients === 0) return 0;
        
        $reached = $this->logs()->whereIn('status', ['sent', 'failed', 'bounced', 'delivered'])->count();
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
        if ($this->sent_count === 0) return 0;
        $uniqueOpens = $this->logs()->where('open_count', '>', 0)->count();
        return round(($uniqueOpens / max(1, $this->sent_count)) * 100, 1);
    }

    public function clickRate(): float
    {
        if ($this->sent_count === 0) return 0;
        $uniqueClicks = $this->logs()->where('click_count', '>', 0)->count();
        return round(($uniqueClicks / max(1, $this->sent_count)) * 100, 1);
    }

    public function bounceRate(): float
    {
        if ($this->total_recipients === 0) return 0;
        $bounces = $this->logs()->where('status', 'bounced')->count();
        return round(($bounces / $this->total_recipients) * 100, 1);
    }

    public function complaintRate(): float
    {
        if ($this->total_recipients === 0) return 0;
        $complaints = $this->logs()->where('status', 'complaint')->count();
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
}
