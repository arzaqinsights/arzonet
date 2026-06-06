<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

trait BelongsToUser
{
    /**
     * Boot the trait to apply the global scope.
     */
    protected static function bootBelongsToUser()
    {
        static::creating(function ($model) {
            if (Auth::check() && !$model->user_id) {
                $user = Auth::user();
                $userId = $user->id;
                if ($user->role === 'team' && $user->parent_id) {
                    $userId = $user->parent_id;
                }
                $model->user_id = $userId;
            }
        });

        static::addGlobalScope('user_id', function (Builder $builder) {
            if (Auth::check()) {
                $user = Auth::user();
                $userId = $user->id;
                if ($user->role === 'team' && $user->parent_id) {
                    $userId = $user->parent_id;
                }
                $builder->where($builder->getModel()->getTable() . '.user_id', $userId);
            }
        });
    }

    /**
     * Relationship to the user.
     */
    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }
}
