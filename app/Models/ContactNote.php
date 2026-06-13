<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContactNote extends Model
{
    protected $fillable = [
        'email_id',
        'user_id',
        'content',
    ];

    protected static function booted()
    {
        static::created(function ($note) {
            if ($note->email_id) {
                \App\Models\ContactActivity::create([
                    'user_id' => $note->user_id,
                    'email_id' => $note->email_id,
                    'type' => 'note_added',
                    'meta' => [
                        'description' => "Added a note: \"" . \Illuminate\Support\Str::limit($note->content, 60) . "\""
                    ]
                ]);
            }
        });
    }

    public function email(): BelongsTo
    {
        return $this->belongsTo(Email::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
