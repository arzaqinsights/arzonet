<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pipeline extends Model
{
    use \App\Traits\BelongsToUser;

    protected $fillable = ['name', 'user_id', 'is_public', 'created_by_id', 'team_permissions', 'monthly_target', 'rotting_days', 'email_list_id'];

    protected $casts = [
        'team_permissions' => 'array',
        'monthly_target' => 'decimal:2',
        'rotting_days' => 'integer',
    ];

    public function emailList(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(EmailList::class);
    }

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

        // If pipeline is private, other team members have no access
        if (!$this->is_public) {
            return false;
        }

        // Check global CRM permissions first
        $globalPermMap = [
            'add_deal' => 'pipelines.manage',
            'move_deal' => 'pipelines.manage',
            'delete_deal' => 'pipelines.manage',
            'edit_pipeline' => 'pipelines.manage',
            'delete_pipeline' => 'pipelines.manage',
        ];

        if (array_key_exists($action, $globalPermMap)) {
            if (\App\Models\User::canAccess($globalPermMap[$action])) {
                return true;
            }
        }

        // Check overrides
        $perms = $this->team_permissions ?? [];
        return (bool) ($perms[$action] ?? true);
    }

    /**
     * Boot: auto-seed default stages when a new pipeline is created.
     */
    protected static function booted()
    {
        static::created(function (Pipeline $pipeline) {
            $defaults = [
                ['name' => 'Lead',          'color' => '#6366f1', 'order' => 0],
                ['name' => 'Contacted',     'color' => '#3b82f6', 'order' => 1],
                ['name' => 'Proposal Sent', 'color' => '#f59e0b', 'order' => 2],
                ['name' => 'Won',           'color' => '#10b981', 'order' => 3],
                ['name' => 'Lost',          'color' => '#ef4444', 'order' => 4],
            ];

            foreach ($defaults as $stage) {
                $pipeline->stages()->create(array_merge($stage, [
                    'user_id' => $pipeline->user_id,
                ]));
            }
        });
    }

    public function stages(): HasMany
    {
        return $this->hasMany(PipelineStage::class)->orderBy('order');
    }

    public function deals(): \Illuminate\Database\Eloquent\Relations\HasManyThrough
    {
        return $this->hasManyThrough(Deal::class, PipelineStage::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }
}
