<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailEvent extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'email_log_id',
        'type',
        'url',
        'ip_address',
        'user_agent',
        'metadata',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'metadata'   => 'json',
            'created_at' => 'datetime',
        ];
    }

    public function log(): BelongsTo
    {
        return $this->belongsTo(EmailLog::class, 'email_log_id');
    }
}
