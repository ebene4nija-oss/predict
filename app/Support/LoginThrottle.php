<?php

namespace App\Support;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Per-account lockout after repeated failed logins.
 *
 * The route-level `throttle:auth` limiter keys on IP, which caps how fast one
 * address can guess but does nothing about one account being ground down from
 * many addresses — the shape credential-stuffing actually takes. This keys on
 * the account instead, so the target of the attack is what gets protected.
 *
 * Lockouts escalate: each successive one for the same account doubles the wait,
 * up to an hour. That makes a sustained run expensive while a genuine user who
 * mistypes their password twice is barely inconvenienced.
 */
class LoginThrottle
{
    /** Failed attempts allowed before the account locks. */
    public const MAX_ATTEMPTS = 5;

    /** First lockout, in seconds. */
    public const BASE_LOCKOUT = 900;

    /** Ceiling for the escalated lockout, in seconds. */
    public const MAX_LOCKOUT = 3600;

    /** How long the escalation counter itself remembers, in seconds. */
    public const ESCALATION_MEMORY = 86400;

    /**
     * Reject the request if this account is currently locked.
     *
     * @throws ValidationException
     */
    public function assertNotLocked(Request $request, string $email): void
    {
        $key = $this->key($email);

        if (! RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS)) {
            return;
        }

        event(new Lockout($request));

        $seconds = RateLimiter::availableIn($key);

        throw ValidationException::withMessages([
            'email' => "Too many failed sign-in attempts for this account. Try again in {$this->humanise($seconds)}, or reset your password.",
        ]);
    }

    /**
     * Record a failed attempt, escalating the lock if this account has been
     * locked before.
     */
    public function recordFailure(string $email): void
    {
        $key = $this->key($email);

        RateLimiter::hit($key, $this->lockoutSeconds($email));

        // Crossing the threshold counts as a lockout, and the next one waits
        // twice as long.
        if (RateLimiter::attempts($key) >= self::MAX_ATTEMPTS) {
            Cache::put(
                $this->escalationKey($email),
                $this->lockoutCount($email) + 1,
                self::ESCALATION_MEMORY
            );
        }
    }

    /**
     * A successful sign-in clears the counter — but not the escalation memory,
     * so an attacker who guesses right cannot reset the ratchet for the account
     * by signing in once.
     */
    public function clear(string $email): void
    {
        RateLimiter::clear($this->key($email));
    }

    /**
     * Seconds the account stays locked, doubling per prior lockout.
     */
    protected function lockoutSeconds(string $email): int
    {
        $seconds = self::BASE_LOCKOUT * (2 ** $this->lockoutCount($email));

        return (int) min($seconds, self::MAX_LOCKOUT);
    }

    protected function lockoutCount(string $email): int
    {
        return (int) Cache::get($this->escalationKey($email), 0);
    }

    /**
     * Key on the address rather than the user id: an account that does not
     * exist has to throttle identically, or the difference in behaviour
     * enumerates which addresses are registered.
     */
    protected function key(string $email): string
    {
        return 'login:'.hash('sha256', Str::lower(trim($email)));
    }

    protected function escalationKey(string $email): string
    {
        return 'login-lockouts:'.hash('sha256', Str::lower(trim($email)));
    }

    protected function humanise(int $seconds): string
    {
        if ($seconds < 60) {
            return "{$seconds} seconds";
        }

        $minutes = (int) ceil($seconds / 60);

        return $minutes === 1 ? 'a minute' : "{$minutes} minutes";
    }
}
