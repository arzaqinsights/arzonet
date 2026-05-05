<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Campaign extends Model
{
    protected $fillable = [
        'name',
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
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at'  => 'datetime',
            'started_at'    => 'datetime',
            'completed_at'  => 'datetime',
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
        
        $reached = $this->logs()->whereIn('status', ['sent', 'failed', 'bounced'])->count();
        return round(($reached / $this->total_recipients) * 100, 1);
    }

    public function estimatedCost(): float
    {
        return $this->total_recipients * config('emailplatform.cost_per_email');
    }
}
