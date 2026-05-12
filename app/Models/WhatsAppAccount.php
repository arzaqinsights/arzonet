<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsAppAccount extends Model
{
    use \App\Traits\BelongsToUser;

    protected $table = 'whatsapp_accounts';


    protected $fillable = [
        'user_id',
        'business_name',
        'display_name',
        'phone_number',
        'phone_number_id',
        'whatsapp_business_account_id',
        'access_token',
        'token_expires_at',
        'status',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'token_expires_at' => 'datetime',
    ];

    public function templates()
    {
        return $this->hasMany(WhatsAppTemplate::class);
    }

    public function messages()
    {
        return $this->hasMany(WhatsAppMessage::class);
    }

    public function conversations()
    {
        return $this->hasMany(WhatsAppConversation::class);
    }
}
