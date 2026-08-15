<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class Subscription extends Model
{
    use HasFactory;

    /**
     * Hours past `renews_at` that still count as paid, covering gateways that
     * settle a renewal a little after the nominal date.
     */
    public const RENEWAL_SLACK_HOURS = 12;

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

    /**
     * Is paid access live right now?
     *
     * An `active` row is not enough on its own: `renews_at` is the date the
     * gateway was due to bill again, so an active row sitting past that date
     * means the renewal was never confirmed — a dropped webhook, a gateway
     * outage, a card that quietly stopped working. Treating that as paid handed
     * out free access indefinitely, so the date is checked as well and a short
     * slack window absorbs gateways that bill a few hours late.
     */
    public function isActive(): bool
    {
        if ($this->status === 'active') {
            return $this->renews_at === null
                || $this->renews_at->copy()->addHours(self::RENEWAL_SLACK_HOURS)->isFuture();
        }

        if ($this->status === 'past_due' && $this->grace_period_ends_at && $this->grace_period_ends_at->isFuture()) {
            return true;
        }

        return false;
    }

    /**
     * Has an active row sat unrenewed past the point where the gateway should
     * have billed? Used by the expiry sweep.
     */
    public function isOverdue(): bool
    {
        return $this->status === 'active'
            && $this->renews_at !== null
            && $this->renews_at->copy()->addHours(self::RENEWAL_SLACK_HOURS)->isPast();
    }
}
