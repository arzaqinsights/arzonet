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

    protected static function booted()
    {
        static::created(function ($enrollment) {
            if ($enrollment->email_id) {
                $sequence = $enrollment->sequence;
                \App\Models\ContactActivity::create([
                    'user_id' => $sequence ? $sequence->user_id : null,
                    'email_id' => $enrollment->email_id,
                    'type' => 'sequence_enrolled',
                    'meta' => [
                        'sequence_id' => $enrollment->sequence_id,
                        'sequence_name' => $sequence ? $sequence->name : 'Sequence',
                        'description' => "Enrolled in sequence: " . ($sequence ? $sequence->name : 'Sequence') . "."
                    ]
                ]);
            }
        });

        static::updated(function ($enrollment) {
            if ($enrollment->isDirty('status') && $enrollment->email_id) {
                $sequence = $enrollment->sequence;
                $status = $enrollment->status;
                if (in_array($status, ['completed', 'cancelled'])) {
                    $type = $status === 'completed' ? 'sequence_completed' : 'sequence_cancelled';
                    $verb = $status === 'completed' ? 'Completed' : 'Cancelled enrollment from';
                    \App\Models\ContactActivity::create([
                        'user_id' => $sequence ? $sequence->user_id : null,
                        'email_id' => $enrollment->email_id,
                        'type' => $type,
                        'meta' => [
                            'sequence_id' => $enrollment->sequence_id,
                            'sequence_name' => $sequence ? $sequence->name : 'Sequence',
                            'description' => "{$verb} sequence: " . ($sequence ? $sequence->name : 'Sequence') . "."
                        ]
                    ]);
                }
            }
        });
    }

    public function sequence(): BelongsTo
    {
        return $this->belongsTo(Sequence::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Email::class, 'email_id');
    }
}
