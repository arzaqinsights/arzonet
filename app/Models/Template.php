<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Template extends Model
{
    use \App\Traits\BelongsToUser;

    protected $fillable = [
        'user_id',
        'name',
        'subject',
        'html_content',
        'json_design',
    ];

    public function campaigns(): HasMany
    {
        return $this->hasMany(Campaign::class);
    }
}
