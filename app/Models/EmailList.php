<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmailList extends Model
{
    protected $fillable = [
        'name',
        'file_path',
        'original_filename',
        'signup_source',
        'segment_name',
        'column_mapping',
        'total_records',
        'valid_count',
        'invalid_count',
        'duplicate_count',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'column_mapping' => 'array',
        ];
    }

    public function emails(): HasMany
    {
        return $this->hasMany(Email::class);
    }

    public function campaigns(): HasMany
    {
        return $this->hasMany(Campaign::class);
    }

    public function validEmails(): HasMany
    {
        return $this->hasMany(Email::class)->where('status', 'valid');
    }

    public function invalidEmails(): HasMany
    {
        return $this->hasMany(Email::class)->where('status', 'invalid');
    }

    public function duplicateEmails(): HasMany
    {
        return $this->hasMany(Email::class)->where('status', 'duplicate');
    }
}
