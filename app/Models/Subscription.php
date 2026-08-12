<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class Subscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'gateway',
        'gateway_subscription_id',
        'status',
        'plan',
        'renews_at',
        'grace_period_ends_at',
    ];

    protected $casts = [
        'renews_at' => 'datetime',
        'grace_period_ends_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isActive(): bool
    {
        if ($this->status === 'active') {
            return true;
        }

        if ($this->status === 'past_due' && $this->grace_period_ends_at && $this->grace_period_ends_at->isFuture()) {
            return true;
        }

        return false;
    }
}
