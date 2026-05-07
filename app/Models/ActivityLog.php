<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'email_list_id', 'user_id', 'type', 'details', 'batch_id',
        'session_valid_count', 'session_invalid_count', 'session_duplicate_count',
    ];

    protected $casts = [
        'details' => 'array'
    ];

    public function emailList()
    {
        return $this->belongsTo(EmailList::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
