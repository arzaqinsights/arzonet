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
