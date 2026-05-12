<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsAppConversation extends Model
{
    use \App\Traits\BelongsToUser;

    protected $fillable = [
        'user_id',
        'whatsapp_account_id',
        'contact_id',
        'last_message_at',
        'unread_count',
    ];

    protected $casts = [
        'last_message_at' => 'datetime',
    ];

    public function whatsappAccount()
    {
        return $this->belongsTo(WhatsAppAccount::class);
    }

    public function contact()
    {
        return $this->belongsTo(Email::class, 'contact_id');
    }

    public function messages()
    {
        return $this->hasMany(WhatsAppMessage::class, 'contact_id', 'contact_id')
            ->where('whatsapp_account_id', $this->whatsapp_account_id);
    }
}
