<?php

namespace App\Jobs;

use App\Models\Subscription;
use App\Models\User;
use App\Services\Payment\SubscriptionManager;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * Age paid access out of the system without waiting on a gateway webhook.
 *
 * Every subscription transition used to be webhook-driven, which meant a
 * dropped or unsigned notification left an `active` row in place forever and
 * the user kept full paid access for free. This sweep is the safety net: it
 * reads the dates the application itself wrote and moves rows along whether or
 * not the gateway ever calls back.
 *
 * Two stages, matching the doc's "retry, then a short grace period before
 * access is cut" rule:
 *
 *   active, past renewal  ->  past_due  (grace window opens; access continues)
 *   past_due, grace over  ->  expired   (access cut, role back to free)
 *
 * A late webhook arriving mid-window still reactivates through
 * {@see SubscriptionManager::activate()}, so this only ever bites accounts the
 * gateway has genuinely stopped billing.
 */
class SubscriptionExpirySweepJob implements ShouldQueue
{
    use Queueable;

    public function handle(SubscriptionManager $subscriptions): void
    {
        $openedGrace = 0;
        $expired = 0;

        // Stage 1: renewal date passed and no fresh payment confirmed.
        Subscription::with('user')
            ->where('status', 'active')
            ->whereNotNull('renews_at')
            ->where('renews_at', '<=', now()->subHours(Subscription::RENEWAL_SLACK_HOURS))
            ->chunkById(200, function ($due) use ($subscriptions, &$openedGrace) {
                foreach ($due as $subscription) {
                    if (! $subscription->user) {
                        continue;
                    }

                    $subscriptions->markPastDue($subscription->user);
                    $openedGrace++;
                }
            });

        // Stage 2: grace window closed.
        Subscription::with('user')
            ->where('status', 'past_due')
            ->whereNotNull('grace_period_ends_at')
            ->where('grace_period_ends_at', '<=', now())
            ->chunkById(200, function ($lapsed) use ($subscriptions, &$expired) {
                foreach ($lapsed as $subscription) {
                    if (! $subscription->user) {
                        continue;
                    }

                    $subscriptions->expire($subscription->user);
                    $expired++;
                }
            });

        // Stage 3: repair rows whose role drifted out of step with the
        // subscription — an old cancellation that never demoted the user, or a
        // role set by hand in the database.
        $demoted = User::where('role', 'subscriber')
            ->whereDoesntHave('subscription', function ($query) {
                $query->whereIn('status', ['active', 'past_due']);
            })
            ->update(['role' => 'free']);

        if ($openedGrace || $expired || $demoted) {
            Log::info('Subscription expiry sweep', [
                'grace_opened' => $openedGrace,
                'expired' => $expired,
                'roles_repaired' => $demoted,
            ]);
        }
    }
}
