<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmailLog extends Model
{
    use \App\Traits\BelongsToUser;

    protected $fillable = [
        'user_id',
        'campaign_id',
        'email_id',
        'email_address',
        'message_id',
        'tracking_token',
        'status',
        'open_count',
        'click_count',
        'error_message',
        'bounce_type',
        'bounce_reason',
        'sent_at',
        'delivered_at',
        'first_open_at',
        'last_open_at',
        'clicked_at',
    ];

    protected function casts(): array
    {
        return [
            'sent_at'       => 'datetime',
            'delivered_at'  => 'datetime',
            'first_open_at' => 'datetime',
            'last_open_at'  => 'datetime',
            'clicked_at'    => 'datetime',
            'open_count'    => 'integer',
            'click_count'   => 'integer',
        ];
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function email(): BelongsTo
    {
        return $this->belongsTo(Email::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(EmailEvent::class);
    }
}
