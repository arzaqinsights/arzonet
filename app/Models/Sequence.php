<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sequence extends Model
{
    use \App\Traits\BelongsToUser;

    protected $fillable = [
        'email_list_id',
        'user_id',
        'name',
    ];

    public function emailList(): BelongsTo
    {
        return $this->belongsTo(EmailList::class);
    }

    public function steps(): HasMany
    {
        return $this->hasMany(SequenceStep::class)->orderBy('step_number');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(SequenceEnrollment::class);
    }
}
