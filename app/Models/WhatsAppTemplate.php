<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsAppTemplate extends Model
{
    use \App\Traits\BelongsToUser;

    protected $fillable = [
        'user_id',
        'whatsapp_account_id',
        'meta_template_id',
        'name',
        'category',
        'language',
        'body',
        'status',
        'components',
    ];

    protected $casts = [
        'components' => 'array',
    ];

    public function whatsappAccount()
    {
        return $this->belongsTo(WhatsAppAccount::class);
    }

    public function campaigns()
    {
        return $this->hasMany(WhatsAppCampaign::class);
    }
}
