<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UsageStat extends Model
{
    protected $fillable = [
        'date',
        'emails_sent',
        'emails_failed',
        'cost',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'cost' => 'decimal:4',
        ];
    }
}
