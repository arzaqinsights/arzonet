<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FormSubmission extends Model
{
    protected $fillable = [
        'signup_form_id',
        'session_id',
        'email',
        'abandoned_step',
        'is_completed',
    ];

    protected $casts = [
        'is_completed' => 'boolean',
    ];

    public function signupForm(): BelongsTo
    {
        return $this->belongsTo(SignupForm::class);
    }
}
