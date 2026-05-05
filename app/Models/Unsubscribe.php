<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Unsubscribe extends Model
{
    protected $fillable = [
        'email',
        'campaign_id',
        'unsubscribed_at',
    ];

    protected function casts(): array
    {
        return [
            'unsubscribed_at' => 'datetime',
        ];
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }
}
