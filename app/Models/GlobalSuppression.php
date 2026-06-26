<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GlobalSuppression extends Model
{
    protected $fillable = [
        'email',
        'user_id',
        'reason',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'user_id' => 'integer',
    ];
}
