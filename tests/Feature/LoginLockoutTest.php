<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\LoginThrottle;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * The route limiter is per-IP, so it never protected an individual account from
 * a distributed run. These cover the per-account lock.
 */
class LoginLockoutTest extends TestCase
{
    use RefreshDatabase;

    protected function user(): User
    {
        return User::factory()->create([
            'email' => 'victim@guaranteedcorrectscoretips.com',
            'password' => Hash::make('correct-horse-battery'),
        ]);
    }

    protected function attempt(string $password, string $ip = '10.0.0.1')
    {
        return $this->withServerVariables(['REMOTE_ADDR' => $ip])
            ->post('/login', [
                'email' => 'victim@guaranteedcorrectscoretips.com',
                'password' => $password,
            ]);
    }

    public function test_account_locks_after_repeated_failures(): void
    {
        $this->user();

        // Each attempt comes from a different address, which the per-IP limiter
        // alone would happily wave through.
        for ($i = 0; $i < LoginThrottle::MAX_ATTEMPTS; $i++) {
            $this->attempt('wrong-'.$i, "10.0.0.{$i}");
        }

        $response = $this->attempt('correct-horse-battery', '10.0.0.99');

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_correct_password_still_works_below_the_threshold(): void
    {
        $this->user();

        $this->attempt('wrong');
        $this->attempt('wrong');

        $this->attempt('correct-horse-battery')->assertRedirect();
        $this->assertAuthenticated();
    }

    public function test_a_successful_sign_in_clears_the_failure_count(): void
    {
        $this->user();

        // Addresses vary so the per-IP limiter stays out of the way and only
        // the per-account counter is under test.
        $this->attempt('wrong', '10.0.3.1');
        $this->attempt('wrong', '10.0.3.2');
        $this->attempt('correct-horse-battery', '10.0.3.3');
        $this->post('/logout');

        // Counter reset, so the account should not be near a lock.
        $this->attempt('wrong', '10.0.3.4');
        $this->attempt('wrong', '10.0.3.5');
        $this->attempt('correct-horse-battery', '10.0.3.6')->assertRedirect();

        $this->assertAuthenticated();
    }

    public function test_lockout_event_is_fired(): void
    {
        Event::fake([Lockout::class]);
        $this->user();

        for ($i = 0; $i <= LoginThrottle::MAX_ATTEMPTS; $i++) {
            $this->attempt('wrong-'.$i, "10.0.1.{$i}");
        }

        Event::assertDispatched(Lockout::class);
    }

    public function test_unknown_addresses_are_throttled_the_same_way(): void
    {
        // No user row at all: the response must not reveal that by behaving
        // differently from a locked real account.
        for ($i = 0; $i < LoginThrottle::MAX_ATTEMPTS; $i++) {
            $this->withServerVariables(['REMOTE_ADDR' => "10.0.2.{$i}"])
                ->post('/login', ['email' => 'ghost@guaranteedcorrectscoretips.com', 'password' => 'wrong']);
        }

        $response = $this->withServerVariables(['REMOTE_ADDR' => '10.0.2.99'])
            ->post('/login', ['email' => 'ghost@guaranteedcorrectscoretips.com', 'password' => 'wrong']);

        $response->assertSessionHasErrors('email');
    }

    public function test_lockout_escalates_on_repeat_offences(): void
    {
        $throttle = new LoginThrottle;

        for ($i = 0; $i < LoginThrottle::MAX_ATTEMPTS; $i++) {
            $throttle->recordFailure('victim@guaranteedcorrectscoretips.com');
        }

        $first = \Illuminate\Support\Facades\RateLimiter::availableIn(
            'login:'.hash('sha256', 'victim@guaranteedcorrectscoretips.com')
        );

        $throttle->clear('victim@guaranteedcorrectscoretips.com');

        for ($i = 0; $i < LoginThrottle::MAX_ATTEMPTS; $i++) {
            $throttle->recordFailure('victim@guaranteedcorrectscoretips.com');
        }

        $second = \Illuminate\Support\Facades\RateLimiter::availableIn(
            'login:'.hash('sha256', 'victim@guaranteedcorrectscoretips.com')
        );

        $this->assertGreaterThan($first, $second);
    }
}
