<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkflowRun extends Model
{
    protected $fillable = [
        'workflow_id',
        'email_id',
        'current_node_id',
        'state',
        'status', // active, completed, failed
        'scheduled_at',
        'last_executed_at',
    ];

    protected function casts(): array
    {
        return [
            'state' => 'array',
            'scheduled_at' => 'datetime',
            'last_executed_at' => 'datetime',
        ];
    }

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(Workflow::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Email::class, 'email_id');
    }
}
