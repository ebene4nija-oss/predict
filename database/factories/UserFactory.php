<?php

namespace Database\Factories;

use App\Models\Subscription;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * A paying subscriber.
     *
     * The role alone no longer grants access — {@see User::isSubscriber()}
     * defers to the subscription row — so paid access has to be built the same
     * way the application builds it.
     */
    public function subscriber(): static
    {
        return $this->state(fn (array $attributes) => ['role' => 'subscriber'])
            ->afterCreating(function (User $user) {
                Subscription::factory()->for($user)->create();
            });
    }

    /**
     * A subscriber whose renewal date has passed without the gateway
     * confirming a fresh payment.
     */
    public function lapsedSubscriber(): static
    {
        return $this->state(fn (array $attributes) => ['role' => 'subscriber'])
            ->afterCreating(function (User $user) {
                Subscription::factory()->for($user)->create([
                    'status' => 'active',
                    'renews_at' => now()->subDays(3),
                ]);
            });
    }
}
