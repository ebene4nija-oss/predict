<?php

namespace App\Models;

use App\Notifications\QueuedResetPassword;
use App\Notifications\QueuedVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasOne;

class User extends Authenticatable implements MustVerifyEmail
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

    /**
     * Paid access.
     *
     * The `subscriber` role is a cached convenience flag, not the authority —
     * it is only ever written alongside a subscription row, and it is the
     * subscription that decides whether access is still live. Trusting the role
     * on its own meant a lapsed or cancelled account kept full paid access
     * indefinitely whenever a renewal webhook went missing.
     */
    public function isSubscriber(): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        $sub = $this->subscription;

        if (! $sub) {
            return false;
        }

        return $sub->isActive();
    }

    /**
     * Queue the verification mail instead of sending it inside the request.
     */
    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new QueuedVerifyEmail);
    }

    /**
     * Queue the password reset mail instead of sending it inside the request.
     */
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new QueuedResetPassword($token));
    }
}
