<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VerifiedDomain extends Model
{
    use \App\Traits\BelongsToUser;

    protected $fillable = [
        'user_id',
        'domain',
        'sendgrid_domain_id',
        'dns_records',
        'status',
        'verified_at',
    ];

    protected function casts(): array
    {
        return [
            'dns_records' => 'array',
            'verified_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function senders()
    {
        return $this->hasMany(Sender::class, 'verified_domain_id');
    }
}
