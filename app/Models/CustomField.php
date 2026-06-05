<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomField extends Model
{
    use \App\Traits\BelongsToUser;

    protected $fillable = [
        'name',
        'label',
        'type',
        'choices',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'choices' => 'array',
        ];
    }
}
