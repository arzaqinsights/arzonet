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
        'tags',
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

    public function emails()
    {
        return $this->hasMany(Email::class);
    }

    public function activityLogs()
    {
        return $this->hasMany(ActivityLog::class)->latest();
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
    public function recalculateStats(): void
    {
        $this->update([
            'total_records'   => $this->emails()->where('is_archived', false)->count(),
            'valid_count'     => $this->emails()->where('is_archived', false)->where('status', 'valid')->count(),
            'invalid_count'   => $this->emails()->where('is_archived', false)->where('status', 'invalid')->count(),
            'duplicate_count' => $this->emails()->where('is_archived', false)->where('status', 'duplicate')->count(),
        ]);
    }
}
