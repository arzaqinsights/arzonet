<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BlacklistedEmail extends Model
{
    protected $fillable = [
        'email',
        'reason',
    ];
}
