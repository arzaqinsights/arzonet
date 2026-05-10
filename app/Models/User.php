<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role', 'phone_number', 'company_name', 'gstin', 'address_street', 'address_city', 'address_state', 'address_country', 'address_zip'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    const ROLE_ADMIN = 'admin';
    const ROLE_TEAM = 'team';

    public function isSuperAdmin(): bool
    {
        return (bool) $this->is_super_admin;
    }

    public function subscription()
    {
        return $this->hasOne(Subscription::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function emails()
    {
        return $this->hasMany(Email::class);
    }

    public function logs()
    {
        return $this->hasMany(EmailLog::class);
    }

    public function emailLists()
    {
        return $this->hasMany(EmailList::class);
    }

    public function getContactsUsage()
    {
        $limit = optional($this->subscription)->contacts_limit ?? 0;
        $total = $this->emails()->count();
        return (object) [
            'total' => $total,
            'limit' => $limit,
            'percent' => $limit > 0 ? ($total / $limit) * 100 : 0,
            'is_exceeded' => $limit > 0 && $total > $limit,
        ];
    }

    public function getEmailsUsage()
    {
        $limit = optional($this->subscription)->emails_limit ?? 0;
        $total = $this->logs()->whereIn('status', ['sent', 'delivered'])->count();
        return (object) [
            'total' => $total,
            'limit' => $limit,
            'percent' => $limit > 0 ? ($total / $limit) * 100 : 0,
            'is_exceeded' => $limit > 0 && $total > $limit,
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
