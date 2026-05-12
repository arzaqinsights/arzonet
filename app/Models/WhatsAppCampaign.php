<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsAppCampaign extends Model
{
    use \App\Traits\BelongsToUser;

    protected $table = 'whatsapp_campaigns';


    protected $fillable = [
        'user_id',
        'whatsapp_template_id',
        'name',
        'status',
        'scheduled_at',
        'total_recipients',
        'sent_count',
        'failed_count',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
    ];

    public function template()
    {
        return $this->belongsTo(WhatsAppTemplate::class, 'whatsapp_template_id');
    }
}
