<?php

namespace Tests\Feature;

use App\Jobs\SubscriptionExpirySweepJob;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Paid access has to end on its own clock.
 *
 * Every transition used to be webhook-driven, so a dropped notification left an
 * `active` row in place and the account kept paid access for free.
 */
class SubscriptionExpiryTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_subscription_past_its_renewal_date_is_not_paid_access(): void
    {
        $user = User::factory()->lapsedSubscriber()->create();

        $this->assertFalse($user->isSubscriber());
    }

    public function test_subscriber_role_alone_does_not_grant_access(): void
    {
        $user = User::factory()->create(['role' => 'subscriber']);

        $this->assertFalse($user->isSubscriber());
    }

    public function test_active_subscription_within_its_term_is_paid_access(): void
    {
        $user = User::factory()->subscriber()->create();

        $this->assertTrue($user->isSubscriber());
    }

    public function test_past_due_inside_grace_window_keeps_access(): void
    {
        $user = User::factory()->create(['role' => 'subscriber']);
        Subscription::factory()->for($user)->pastDue()->create();

        $this->assertTrue($user->fresh()->isSubscriber());
    }

    public function test_a_renewal_settling_a_few_hours_late_still_counts_as_paid(): void
    {
        $user = User::factory()->create(['role' => 'subscriber']);
        Subscription::factory()->for($user)->create([
            'renews_at' => now()->subHours(2),
        ]);

        $this->assertTrue($user->fresh()->isSubscriber());
    }

    public function test_sweep_opens_a_grace_window_before_cutting_access(): void
    {
        $user = User::factory()->lapsedSubscriber()->create();

        (new SubscriptionExpirySweepJob)->handle(app(\App\Services\Payment\SubscriptionManager::class));

        $subscription = $user->fresh()->subscription;

        $this->assertSame('past_due', $subscription->status);
        $this->assertTrue($subscription->grace_period_ends_at->isFuture());
        // Access is deliberately not cut on the first missed renewal.
        $this->assertTrue($user->fresh()->isSubscriber());
        $this->assertSame('subscriber', $user->fresh()->role);
    }

    public function test_sweep_expires_a_subscription_once_the_grace_window_closes(): void
    {
        $user = User::factory()->create(['role' => 'subscriber']);
        Subscription::factory()->for($user)->create([
            'status' => 'past_due',
            'renews_at' => now()->subDays(10),
            'grace_period_ends_at' => now()->subDay(),
        ]);

        (new SubscriptionExpirySweepJob)->handle(app(\App\Services\Payment\SubscriptionManager::class));

        $user = $user->fresh();

        $this->assertSame('expired', $user->subscription->status);
        $this->assertSame('free', $user->role);
        $this->assertFalse($user->isSubscriber());
    }

    public function test_sweep_repairs_a_subscriber_role_with_no_live_subscription(): void
    {
        $user = User::factory()->create(['role' => 'subscriber']);
        Subscription::factory()->for($user)->cancelled()->create();

        (new SubscriptionExpirySweepJob)->handle(app(\App\Services\Payment\SubscriptionManager::class));

        $this->assertSame('free', $user->fresh()->role);
    }

    public function test_sweep_leaves_admins_alone(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        (new SubscriptionExpirySweepJob)->handle(app(\App\Services\Payment\SubscriptionManager::class));

        $this->assertSame('admin', $admin->fresh()->role);
        $this->assertTrue($admin->fresh()->isSubscriber());
    }

    public function test_a_late_webhook_reactivates_a_lapsed_subscription(): void
    {
        $user = User::factory()->lapsedSubscriber()->create();

        (new SubscriptionExpirySweepJob)->handle(app(\App\Services\Payment\SubscriptionManager::class));

        app(\App\Services\Payment\SubscriptionManager::class)
            ->activate($user, 'flutterwave', 'flw_late_webhook');

        $user = $user->fresh();

        $this->assertSame('active', $user->subscription->status);
        $this->assertNull($user->subscription->grace_period_ends_at);
        $this->assertTrue($user->isSubscriber());
    }
}
