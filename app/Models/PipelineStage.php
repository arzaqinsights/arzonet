<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PipelineStage extends Model
{
    use \App\Traits\BelongsToUser;

    protected $fillable = ['pipeline_id', 'name', 'color', 'order', 'user_id', 'automation_action', 'automation_value'];

    public function pipeline(): BelongsTo
    {
        return $this->belongsTo(Pipeline::class);
    }

    public function deals(): HasMany
    {
        return $this->hasMany(Deal::class)->orderBy('order');
    }
}
