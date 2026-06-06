<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sender extends Model
{
    use HasFactory, \App\Traits\BelongsToUser;

    protected $fillable = [
        'user_id',
        'email',
        'status',
        'type', // ses, smtp, sendgrid
        'from_name',
        'emails_per_second',
        'emails_per_minute',
        'daily_limit',
        'ses_identity',
        'smtp_host',
        'smtp_port',
        'smtp_username',
        'smtp_password',
        'smtp_encryption',
        'ses_key',
        'ses_secret',
        'ses_region',
        'sendgrid_api_key',
        'verified_at',
        'verified_domain_id',
        'is_authenticated',
        'email_list_id',
    ];

    public function emailList()
    {
        return $this->belongsTo(EmailList::class);
    }

    public function domain()
    {
        return $this->belongsTo(VerifiedDomain::class, 'verified_domain_id');
    }

    protected $casts = [
        'verified_at' => 'datetime',
        'emails_per_second' => 'integer',
        'emails_per_minute' => 'integer',
        'daily_limit' => 'integer',
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
