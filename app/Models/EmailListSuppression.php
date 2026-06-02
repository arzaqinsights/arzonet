<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailListSuppression extends Model
{
    protected $fillable = [
        'email_list_id',
        'identifier',
        'reason',
    ];

    public function emailList()
    {
        return $this->belongsTo(EmailList::class);
    }
}
