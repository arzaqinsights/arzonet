<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContactTask extends Model
{
    use \App\Traits\BelongsToUser;

    protected $fillable = [
        'email_id',
        'deal_id',
        'title',
        'description',
        'due_date',
        'is_completed',
        'priority',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'is_completed' => 'boolean',
        ];
    }

    protected static function booted()
    {
        static::created(function ($task) {
            if ($task->email_id) {
                \App\Models\ContactActivity::create([
                    'user_id' => $task->user_id,
                    'email_id' => $task->email_id,
                    'type' => 'task_created',
                    'meta' => [
                        'task_title' => $task->title,
                        'description' => "Task created: \"{$task->title}\"."
                    ]
                ]);
            }
        });

        static::updated(function ($task) {
            if ($task->isDirty('is_completed') && $task->email_id) {
                \App\Models\ContactActivity::create([
                    'user_id' => $task->user_id,
                    'email_id' => $task->email_id,
                    'type' => $task->is_completed ? 'task_completed' : 'task_reopened',
                    'meta' => [
                        'task_title' => $task->title,
                        'description' => $task->is_completed ? "Task completed: \"{$task->title}\"." : "Task re-opened: \"{$task->title}\"."
                    ]
                ]);
            }
        });
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Email::class, 'email_id');
    }

    public function deal(): BelongsTo
    {
        return $this->belongsTo(Deal::class, 'deal_id');
    }
}
