<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailStatus extends Model
{
    protected $fillable = [
        'email',
        'status',
    ];

    /**
     * Check if an email is suppressed.
     */
    public static function isSuppressed(string $email): bool
    {
        return self::where('email', $email)
            ->whereIn('status', ['bounced', 'complaint'])
            ->exists();
    }
}
