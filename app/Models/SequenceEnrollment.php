<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SequenceEnrollment extends Model
{
    protected $fillable = [
        'sequence_id',
        'email_id',
        'current_step_number',
        'status',
        'scheduled_at',
        'last_sent_at',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'last_sent_at' => 'datetime',
    ];

    public function sequence(): BelongsTo
    {
        return $this->belongsTo(Sequence::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Email::class, 'email_id');
    }
}
