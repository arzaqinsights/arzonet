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

    public function email(): BelongsTo
    {
        return $this->belongsTo(Email::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
