<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmailList extends Model
{
    use \App\Traits\BelongsToUser;

    const TYPE_EMAIL     = 'email';
    const TYPE_WHATSAPP  = 'whatsapp';
    const TYPE_DUAL      = 'dual';

    protected $fillable = [
        'user_id',
        'name',
        'list_type',
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
        'cross_duplicate_count',
        'status',
        'is_public',
        'created_by_id',
        'team_permissions',
    ];

    // --- Helper Methods ---
    public function isEmailList(): bool    { return in_array($this->list_type, [self::TYPE_EMAIL, self::TYPE_DUAL]); }
    public function isWhatsAppList(): bool { return in_array($this->list_type, [self::TYPE_WHATSAPP, self::TYPE_DUAL]); }
    public function isDualList(): bool     { return $this->list_type === self::TYPE_DUAL; }

    public function canPerformAction(string $action): bool
    {
        // Admin has full access
        if (!app()->has('team_user')) {
            return true;
        }

        $teamUserId = app('team_user')->id;
        // Creator has full access
        if ($this->created_by_id === $teamUserId) {
            return true;
        }

        // If list is private, other team members have no access
        if (!$this->is_public) {
            return false;
        }

        // Check overrides
        $perms = $this->team_permissions ?? [];
        return (bool) ($perms[$action] ?? true);
    }

    // --- Scopes for Campaign Dropdowns ---
    public function scopeForEmail($query)    { return $query->whereIn('list_type', [self::TYPE_EMAIL, self::TYPE_DUAL]); }
    public function scopeForWhatsApp($query) { return $query->whereIn('list_type', [self::TYPE_WHATSAPP, self::TYPE_DUAL]); }

    protected function casts(): array
    {
        return [
            'column_mapping' => 'array',
            'team_permissions' => 'array',
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
    public function getUniqueContactsCountAttribute()
    {
        $groupExpr = "CASE WHEN name IS NOT NULL AND TRIM(name) != '' THEN CONCAT('name_', LOWER(TRIM(name))) WHEN original_row_id IS NOT NULL AND TRIM(original_row_id) != '' THEN CONCAT('orig_', original_row_id) ELSE CONCAT('id_', id) END";
        
        return \Illuminate\Support\Facades\DB::table('emails')
            ->where('email_list_id', $this->id)
            ->where('is_archived', false)
            ->distinct()
            ->count(\Illuminate\Support\Facades\DB::raw($groupExpr));
    }

    public function recalculateStats(): void
    {
        $this->update([
            'total_records'   => $this->emails()->where('is_archived', false)->count(),
            'valid_count'     => $this->emails()->where('is_archived', false)->where('status', 'valid')->count(),
            'invalid_count'   => $this->emails()->where('is_archived', false)->where('status', 'invalid')->count(),
            'duplicate_count' => $this->emails()->where('is_archived', false)->where('status', 'duplicate')->count(),
            'cross_duplicate_count' => $this->emails()->where('is_archived', false)->where('status', 'cross_duplicate')->count(),
        ]);
    }
}
