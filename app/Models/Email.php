<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Email extends Model
{
    protected $fillable = [
        'email_list_id',
        'email',
        'name',
        'status',
        'subscription_status',
        'signup_source',
        'segment_name',
        'reason',
        'meta',
        'tags',
        'last_active_at',
        'unsubscribed_at',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
            'tags' => 'array',
            'unsubscribed_at' => 'datetime',
            'last_active_at' => 'datetime',
        ];
    }

    public function getUnsubscribeUrl(?int $logId = null): string
    {
        $token = hash_hmac('sha256', $this->id . $this->email, config('app.key'));
        return route('unsubscribe', ['id' => $this->id, 'token' => $token, 'lid' => $logId]);
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
}
