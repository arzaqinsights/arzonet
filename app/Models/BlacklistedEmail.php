<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BlacklistedEmail extends Model
{
    use \App\Traits\BelongsToUser;

    protected $fillable = [
        'user_id',
        'email',
        'reason',
    ];
}
