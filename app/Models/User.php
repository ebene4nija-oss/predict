<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'telegram_chat_id',
        'telegram_notifications_enabled',
        'telegram_link_token',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function subscription(): HasOne
    {
        return $this->hasOne(Subscription::class);
    }

    public function expert(): HasOne
    {
        return $this->hasOne(Expert::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isExpert(): bool
    {
        return $this->role === 'expert' || $this->role === 'admin';
    }

    public function isSubscriber(): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        if ($this->role === 'subscriber') {
            return true;
        }

        // Check active subscription model with grace period
        $sub = $this->subscription;
        if (!$sub) {
            return false;
        }

        return $sub->isActive();
    }
}
