<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SignupForm extends Model
{
    use \App\Traits\BelongsToUser;

    protected $fillable = [
        'email_list_id',
        'user_id',
        'name',
        'token',
        'title',
        'description',
        'button_text',
        'success_message',
        'double_opt_in',
        'subscribed_topics',
        'custom_fields',
        'theme_color',
    ];

    protected $casts = [
        'double_opt_in' => 'boolean',
        'subscribed_topics' => 'array',
        'custom_fields' => 'array',
    ];

    public function emailList(): BelongsTo
    {
        return $this->belongsTo(EmailList::class);
    }
}
