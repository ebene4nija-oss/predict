<?php

namespace Database\Factories;

use App\Models\Subscription;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Subscription>
 */
class SubscriptionFactory extends Factory
{
    protected $model = Subscription::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'gateway' => 'flutterwave',
            'gateway_subscription_id' => 'test_'.Str::random(16),
            'status' => 'active',
            'plan' => 'monthly_pro',
            'renews_at' => now()->addMonth(),
            'grace_period_ends_at' => null,
        ];
    }

    /** Renewal date passed with no fresh payment confirmed. */
    public function lapsed(): static
    {
        return $this->state(fn () => [
            'status' => 'active',
            'renews_at' => now()->subDays(3),
        ]);
    }

    /** Payment failed, still inside the grace window. */
    public function pastDue(): static
    {
        return $this->state(fn () => [
            'status' => 'past_due',
            'renews_at' => now()->subDay(),
            'grace_period_ends_at' => now()->addDays(5),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn () => [
            'status' => 'cancelled',
            'grace_period_ends_at' => null,
        ]);
    }
}
