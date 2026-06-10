<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PreferenceLog extends Model
{
    protected $fillable = [
        'email_id',
        'action', // subscribe, unsubscribe, preference_update
        'details', // json of metadata (ip, user-agent, topic updates)
    ];

    protected function casts(): array
    {
        return [
            'details' => 'array',
        ];
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Email::class, 'email_id');
    }
}
