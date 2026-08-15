<?php

namespace App\Services\Payment;

use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * Single source of truth for subscription state transitions.
 *
 * Activation used to be copy-pasted across the callback controller and both
 * gateway webhooks, which let the three drift apart. Every path now funnels
 * through here so role, status and renewal dates always move together.
 */
class SubscriptionManager
{
    /**
     * Mark a user as an active paying subscriber.
     */
    public function activate(User $user, string $gateway, string $reference, string $plan = 'monthly_pro'): Subscription
    {
        $subscription = Subscription::updateOrCreate(
            ['user_id' => $user->id],
            [
                'gateway' => $gateway,
                'gateway_subscription_id' => $reference,
                'status' => 'active',
                'plan' => $plan,
                'renews_at' => now()->addMonth(),
                'grace_period_ends_at' => null,
            ]
        );

        // Never demote an admin to a plain subscriber.
        if (! $user->isAdmin()) {
            $user->update(['role' => 'subscriber']);
        }

        Log::info('Subscription activated', [
            'user_id' => $user->id,
            'gateway' => $gateway,
            'reference' => $reference,
        ]);

        return $subscription;
    }

    /**
     * A payment failed: keep access alive for a short grace period.
     */
    public function markPastDue(User $user, int $graceDays = 7): void
    {
        $subscription = Subscription::where('user_id', $user->id)->first();

        if (! $subscription) {
            return;
        }

        $subscription->update([
            'status' => 'past_due',
            'grace_period_ends_at' => now()->addDays($graceDays),
        ]);

        Log::info('Subscription marked past due', [
            'user_id' => $user->id,
            'grace_days' => $graceDays,
        ]);
    }

    /**
     * The grace window ran out without a payment landing: cut paid access.
     *
     * Distinct from {@see cancel()}, which is the user's own decision. This is
     * the end of the dunning sequence, so the row keeps its own status for
     * reporting rather than being folded into cancellations.
     */
    public function expire(User $user): void
    {
        $subscription = $user->subscription;

        if (! $subscription) {
            return;
        }

        $subscription->update([
            'status' => 'expired',
            'grace_period_ends_at' => null,
        ]);

        if (! $user->isAdmin()) {
            $user->update(['role' => 'free']);
        }

        Log::info('Subscription expired', [
            'user_id' => $user->id,
            'gateway' => $subscription->gateway,
        ]);
    }

    /**
     * Cancel a subscription and drop the user back to the free tier.
     */
    public function cancel(User $user): void
    {
        $subscription = $user->subscription;

        if (! $subscription) {
            return;
        }

        $subscription->update([
            'status' => 'cancelled',
            'grace_period_ends_at' => null,
        ]);

        if (! $user->isAdmin()) {
            $user->update(['role' => 'free']);
        }

        Log::info('Subscription cancelled', ['user_id' => $user->id]);
    }
}
