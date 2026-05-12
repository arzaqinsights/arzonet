<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsAppMessage extends Model
{
    use \App\Traits\BelongsToUser;

    protected $fillable = [
        'user_id',
        'whatsapp_account_id',
        'contact_id',
        'wa_message_id',
        'direction',
        'type',
        'message_body',
        'status',
        'payload',
        'metadata',
    ];

    protected $casts = [
        'payload' => 'array',
        'metadata' => 'array',
    ];

    public function whatsappAccount()
    {
        return $this->belongsTo(WhatsAppAccount::class);
    }

    public function contact()
    {
        return $this->belongsTo(Email::class, 'contact_id');
    }
}
