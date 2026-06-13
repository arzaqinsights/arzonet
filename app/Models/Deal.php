<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Deal extends Model
{
    use \App\Traits\BelongsToUser;

    protected $fillable = [
        'pipeline_stage_id',
        'email_id',
        'assigned_to_id',
        'title',
        'value',
        'currency',
        'status',
        'order',
        'expected_close_at',
        'notes',
        'tags',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'decimal:2',
            'expected_close_at' => 'date',
            'tags' => 'array',
        ];
    }

    protected static function booted()
    {
        static::created(function ($deal) {
            if ($deal->email_id) {
                \App\Jobs\CalculateLeadScoreJob::dispatch($deal->email_id);
            }
        });

        static::updated(function ($deal) {
            if ($deal->email_id) {
                \App\Jobs\CalculateLeadScoreJob::dispatch($deal->email_id);
                
                if ($deal->isDirty('pipeline_stage_id')) {
                    $oldStage = \App\Models\PipelineStage::find($deal->getOriginal('pipeline_stage_id'));
                    $newStage = \App\Models\PipelineStage::find($deal->pipeline_stage_id);
                    $oldStageName = $oldStage ? $oldStage->name : 'Unknown';
                    $newStageName = $newStage ? $newStage->name : 'Unknown';

                    \App\Models\ContactActivity::create([
                        'user_id' => $deal->user_id,
                        'email_id' => $deal->email_id,
                        'type' => 'stage_changed',
                        'meta' => [
                            'deal_title' => $deal->title,
                            'old_stage' => $oldStageName,
                            'new_stage' => $newStageName,
                            'description' => "Deal \"{$deal->title}\" stage changed from \"{$oldStageName}\" to \"{$newStageName}\"."
                        ]
                    ]);
                }
            }
            if ($deal->isDirty('email_id') && $deal->getOriginal('email_id')) {
                \App\Jobs\CalculateLeadScoreJob::dispatch($deal->getOriginal('email_id'));
            }
        });

        static::deleted(function ($deal) {
            if ($deal->email_id) {
                \App\Jobs\CalculateLeadScoreJob::dispatch($deal->email_id);
            }
        });
    }

    public function stage(): BelongsTo
    {
        return $this->belongsTo(PipelineStage::class, 'pipeline_stage_id');
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Email::class, 'email_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_id');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(DealActivity::class)->latest();
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(ContactTask::class, 'deal_id');
    }
}
