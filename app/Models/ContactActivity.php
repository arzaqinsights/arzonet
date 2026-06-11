<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContactActivity extends Model
{
    use \App\Traits\BelongsToUser;

    protected $fillable = [
        'user_id',
        'email_id',
        'campaign_id',
        'type',
        'url',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    protected static function booted()
    {
        static::created(function ($activity) {
            if ($activity->email_id) {
                \App\Jobs\CalculateLeadScoreJob::dispatch($activity->email_id);
                \App\Jobs\UpdateContactSegmentsJob::dispatch($activity->email_id);
            }
        });
    }

    public function email(): BelongsTo
    {
        return $this->belongsTo(Email::class);
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }
}
