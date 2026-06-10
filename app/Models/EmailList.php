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
        'double_opt_in',
        'signup_form_token',
    ];

    protected static function booted()
    {
        static::creating(function ($emailList) {
            if (empty($emailList->signup_form_token)) {
                $emailList->signup_form_token = \Illuminate\Support\Str::random(32);
            }
        });

        static::created(function ($emailList) {
            \App\Models\SubscriptionTopic::seedDefaultsFor($emailList->id, $emailList->user_id);
            \App\Models\Segment::seedDefaultsFor($emailList->id, $emailList->user_id);
        });
    }

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

        // Check global CRM permissions first
        $globalPermMap = [
            'edit_contact' => 'crm.edit',
            'add_contact' => 'crm.create',
            'delete_contact' => 'crm.delete',
            'export_contacts' => 'crm.export',
            'import_contacts' => 'crm.import',
            'perform_bulk_actions' => 'crm.bulk',
            'scrub_contacts' => 'crm.scrub',
        ];

        if (array_key_exists($action, $globalPermMap)) {
            if (\App\Models\User::canAccess($globalPermMap[$action])) {
                return true;
            }
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
        $stats = \Illuminate\Support\Facades\DB::table('emails')
            ->where('email_list_id', $this->id)
            ->where('is_archived', false)
            ->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN status = 'valid' THEN 1 ELSE 0 END) as valid,
                SUM(CASE WHEN status = 'invalid' THEN 1 ELSE 0 END) as invalid,
                SUM(CASE WHEN status = 'duplicate' THEN 1 ELSE 0 END) as duplicate,
                SUM(CASE WHEN status = 'cross_duplicate' THEN 1 ELSE 0 END) as cross_duplicate
            ")->first();

        $this->update([
            'total_records'   => (int) ($stats->total ?? 0),
            'valid_count'     => (int) ($stats->valid ?? 0),
            'invalid_count'   => (int) ($stats->invalid ?? 0),
            'duplicate_count' => (int) ($stats->duplicate ?? 0),
            'cross_duplicate_count' => (int) ($stats->cross_duplicate ?? 0),
        ]);

        // Invalidate Redis cache
        \Illuminate\Support\Facades\Redis::del("list_stats:{$this->id}");
        \Illuminate\Support\Facades\Redis::del("list_filters:{$this->id}");
    }

    public function getStatistics(): array
    {
        $cacheKey = "list_stats:{$this->id}";
        $isProcessing = $this->status === 'processing';
        
        // Use 2s cache during active processing, 24 hours otherwise
        $cacheTtl = $isProcessing ? 2 : 86400;
        
        $cached = \Illuminate\Support\Facades\Redis::get($cacheKey);
        if ($cached) {
            $stats = json_decode($cached, true);
            if ($stats) {
                return $stats;
            }
        }
        
        $groupExpr = "CASE WHEN name IS NOT NULL AND TRIM(name) != '' THEN CONCAT('name_', LOWER(TRIM(name))) WHEN original_row_id IS NOT NULL AND TRIM(original_row_id) != '' THEN CONCAT('orig_', original_row_id) ELSE CONCAT('id_', id) END";

        $dbStats = \Illuminate\Support\Facades\DB::table('emails')
            ->where('email_list_id', $this->id)
            ->selectRaw("
                SUM(CASE WHEN is_archived = 0 AND subscription_status = 'subscribed' THEN 1 ELSE 0 END) as subscribed,
                SUM(CASE WHEN is_archived = 0 AND subscription_status = 'unsubscribed' THEN 1 ELSE 0 END) as unsubscribed,
                SUM(CASE WHEN is_archived = 0 AND subscription_status = 'bounced' THEN 1 ELSE 0 END) as bounced,
                SUM(CASE WHEN is_archived = 0 AND email_status = 'complaint' THEN 1 ELSE 0 END) as complaints,
                SUM(CASE WHEN is_archived = 1 THEN 1 ELSE 0 END) as archived,
                SUM(CASE WHEN is_archived = 0 AND email_status = 'risky' THEN 1 ELSE 0 END) as risky,
                SUM(CASE WHEN is_archived = 0 AND is_disposable = 1 THEN 1 ELSE 0 END) as disposable,
                SUM(CASE WHEN is_archived = 0 AND is_role_based = 1 THEN 1 ELSE 0 END) as role_based,
                SUM(CASE WHEN is_archived = 0 AND email_status = 'suspicious' THEN 1 ELSE 0 END) as suspicious,
                SUM(CASE WHEN is_archived = 0 AND email_status = 'hard_bounce' THEN 1 ELSE 0 END) as hard_bounce,
                SUM(CASE WHEN is_archived = 0 AND email_status = 'soft_bounce' THEN 1 ELSE 0 END) as soft_bounce,
                SUM(CASE WHEN is_archived = 0 AND email IS NOT NULL AND email != '' THEN 1 ELSE 0 END) as total_emails,
                SUM(CASE WHEN is_archived = 0 AND email IS NOT NULL AND email != '' AND subscription_status = 'subscribed' THEN 1 ELSE 0 END) as subscribed_emails,
                SUM(CASE WHEN is_archived = 0 AND whatsapp_number IS NOT NULL AND whatsapp_number != '' THEN 1 ELSE 0 END) as total_whatsapps,
                SUM(CASE WHEN is_archived = 0 AND whatsapp_number IS NOT NULL AND whatsapp_number != '' AND whatsapp_subscription_status = 'subscribed' THEN 1 ELSE 0 END) as subscribed_whatsapps
            ")->first();

        // Calculate global_main_rows distinct count
        $globalMainRows = \Illuminate\Support\Facades\DB::table('emails')
            ->where('email_list_id', $this->id)
            ->where('is_archived', false)
            ->distinct()
            ->count(\Illuminate\Support\Facades\DB::raw($groupExpr));

        $stats = [
            'total' => $this->total_records,
            'valid' => $this->valid_count,
            'invalid' => $this->invalid_count,
            'duplicate' => $this->duplicate_count,
            'cross_duplicate' => $this->cross_duplicate_count,
            'subscribed' => (int) ($dbStats->subscribed ?? 0),
            'unsubscribed' => (int) ($dbStats->unsubscribed ?? 0),
            'bounced' => (int) ($dbStats->bounced ?? 0),
            'complaints' => (int) ($dbStats->complaints ?? 0),
            'archived' => (int) ($dbStats->archived ?? 0),
            'risky' => (int) ($dbStats->risky ?? 0),
            'disposable' => (int) ($dbStats->disposable ?? 0),
            'role_based' => (int) ($dbStats->role_based ?? 0),
            'suspicious' => (int) ($dbStats->suspicious ?? 0),
            'hard_bounce' => (int) ($dbStats->hard_bounce ?? 0),
            'soft_bounce' => (int) ($dbStats->soft_bounce ?? 0),
            'global_main_rows' => $globalMainRows,
            'total_emails' => (int) ($dbStats->total_emails ?? 0),
            'subscribed_emails' => (int) ($dbStats->subscribed_emails ?? 0),
            'total_whatsapps' => (int) ($dbStats->total_whatsapps ?? 0),
            'subscribed_whatsapps' => (int) ($dbStats->subscribed_whatsapps ?? 0),
        ];

        \Illuminate\Support\Facades\Redis::setex($cacheKey, $cacheTtl, json_encode($stats));

        return $stats;
    }
}
