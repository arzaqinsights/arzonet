<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FormView extends Model
{
    protected $fillable = [
        'signup_form_id',
        'session_id',
        'ip_address',
        'referrer',
        'user_agent',
    ];

    public function signupForm(): BelongsTo
    {
        return $this->belongsTo(SignupForm::class);
    }
}
