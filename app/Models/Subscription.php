<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    protected $fillable = [
        'user_id',
        'plan_name',
        'contacts_limit',
        'emails_limit',
        'selected_modules',
        'whatsapp_limit',
        'team_limit',
        'status',
        'starts_at',
        'ends_at',
    ];

    protected $casts = [
        'selected_modules' => 'array',
        'starts_at' => 'datetime',
        'ends_at'   => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
