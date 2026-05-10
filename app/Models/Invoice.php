<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    protected $fillable = [
        'user_id',
        'invoice_number',
        'amount',
        'currency',
        'status',
        'payment_gateway',
        'payment_id',
        'pdf_path',
        'plan_details',
    ];

    protected $casts = [
        'plan_details' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
