<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Notifications\CustomVerifyEmail;
use App\Notifications\CustomResetPassword;

#[Fillable(['name', 'email', 'password', 'role', 'phone_number', 'company_name', 'gstin', 'address_street', 'address_city', 'address_state', 'address_country', 'address_zip', 'parent_id', 'permissions'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail
{
    const ROLE_ADMIN = 'admin';
    const ROLE_TEAM = 'team';

    public function teamMembers()
    {
        return $this->hasMany(User::class, 'parent_id');
    }

    public function parent()
    {
        return $this->belongsTo(User::class, 'parent_id');
    }

    public function hasPermission(string $permission): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        if ($this->role === self::ROLE_ADMIN) {
            return true;
        }

        if ($this->role === self::ROLE_TEAM) {
            return is_array($this->permissions) && in_array($permission, $this->permissions);
        }

        return false;
    }

    public static function canAccess(string $permission): bool
    {
        if (app()->has('team_user')) {
            return app('team_user')->hasPermission($permission);
        }

        $user = auth()->user();
        if (!$user) {
            return false;
        }

        return $user->hasPermission($permission);
    }

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

    public function whatsappAccounts()
    {
        return $this->hasMany(WhatsAppAccount::class);
    }

    public function whatsappTemplates()
    {
        return $this->hasMany(WhatsAppTemplate::class);
    }

    public function whatsappCampaigns()
    {
        return $this->hasMany(WhatsAppCampaign::class);
    }

    public function whatsappMessages()
    {
        return $this->hasMany(WhatsAppMessage::class);
    }

    public function whatsappConversations()
    {
        return $this->hasMany(WhatsAppConversation::class);
    }


    public function hasModule(string $module): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }
        $sub = $this->subscription;
        if (!$sub || $sub->status !== 'active') {
            return false;
        }
        $modules = $sub->selected_modules ?? [];
        return in_array($module, $modules);
    }

    public function getWhatsAppLimit(): int
    {
        if ($this->isSuperAdmin()) {
            return 999;
        }
        $sub = $this->subscription;
        if (!$sub || $sub->status !== 'active') {
            return 0;
        }
        return (int) ($sub->whatsapp_limit ?? 0);
    }

    public function getTeamLimit(): int
    {
        if ($this->isSuperAdmin()) {
            return 999;
        }
        $sub = $this->subscription;
        if (!$sub || $sub->status !== 'active') {
            return 0;
        }
        return (int) ($sub->team_limit ?? 0);
    }

    public function getContactsUsage()
    {
        if ($this->isSuperAdmin()) {
            return (object) [
                'total' => $this->emails()->count(),
                'limit' => 999999,
                'percent' => 0,
                'is_exceeded' => false,
            ];
        }
        
        $hasCrm = $this->hasModule('crm');
        $limit = $hasCrm ? (optional($this->subscription)->contacts_limit ?? 0) : 0;
        $total = $this->emails()->count();
        return (object) [
            'total' => $total,
            'limit' => $limit,
            'percent' => $limit > 0 ? ($total / $limit) * 100 : 0,
            'is_exceeded' => !$hasCrm || ($limit > 0 && $total > $limit),
        ];
    }

    public function getEmailsUsage()
    {
        if ($this->isSuperAdmin()) {
            return (object) [
                'total' => $this->logs()->count(),
                'limit' => 999999,
                'percent' => 0,
                'is_exceeded' => false,
            ];
        }

        $hasEmail = $this->hasModule('email');
        $limit = $hasEmail ? (optional($this->subscription)->emails_limit ?? 0) : 0;
        $total = $this->logs()->count();
        return (object) [
            'total' => $total,
            'limit' => $limit,
            'percent' => $limit > 0 ? ($total / $limit) * 100 : 0,
            'is_exceeded' => !$hasEmail || ($limit > 0 && $total > $limit),
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Send the email verification notification.
     */
    public function sendEmailVerificationNotification()
    {
        $this->notify(new CustomVerifyEmail);
    }

    /**
     * Send the password reset notification.
     */
    public function sendPasswordResetNotification($token)
    {
        $this->notify(new CustomResetPassword($token));
    }

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
            'permissions' => 'array',
        ];
    }
}
