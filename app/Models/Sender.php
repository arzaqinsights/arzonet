<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sender extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'email',
        'status',
        'type', // ses, smtp
        'from_name',
        'ses_identity',
        'smtp_host',
        'smtp_port',
        'smtp_username',
        'smtp_password',
        'smtp_encryption',
        'verified_at',
    ];

    protected $casts = [
        'verified_at' => 'datetime',
    ];

    /**
     * Scope a query to only include verified senders.
     */
    public function scopeVerified($query)
    {
        return $query->where('status', 'verified');
    }

    /**
     * Scope a query to only include pending senders.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
}
