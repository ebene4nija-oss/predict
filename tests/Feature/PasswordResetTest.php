<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\QueuedResetPassword;
use App\Notifications\QueuedVerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_reset_link_can_be_requested(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email])->assertRedirect();

        Notification::assertSentTo($user, QueuedResetPassword::class);
    }

    /**
     * The response must not reveal whether an address has an account.
     */
    public function test_unknown_addresses_get_the_same_response(): void
    {
        Notification::fake();

        $known = $this->post('/forgot-password', ['email' => User::factory()->create()->email]);
        $unknown = $this->post('/forgot-password', ['email' => 'nobody@example.com']);

        $this->assertSame(
            $known->getSession()->get('success'),
            $unknown->getSession()->get('success'),
        );
        Notification::assertSentTimes(QueuedResetPassword::class, 1);
    }

    public function test_password_can_be_reset_with_a_valid_token(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, QueuedResetPassword::class, function ($notification) use ($user) {
            $this->post('/reset-password', [
                'token' => $notification->token,
                'email' => $user->email,
                'password' => 'a-brand-new-password',
                'password_confirmation' => 'a-brand-new-password',
            ])->assertRedirect(route('login'));

            return true;
        });

        $this->assertTrue(Hash::check('a-brand-new-password', $user->fresh()->password));
    }

    public function test_reset_fails_with_a_bogus_token(): void
    {
        $user = User::factory()->create(['password' => Hash::make('original-password')]);

        $this->post('/reset-password', [
            'token' => 'not-a-real-token',
            'email' => $user->email,
            'password' => 'attacker-chosen-password',
            'password_confirmation' => 'attacker-chosen-password',
        ])->assertSessionHasErrors('email');

        $this->assertTrue(Hash::check('original-password', $user->fresh()->password));
    }

    public function test_registration_sends_a_verification_email(): void
    {
        Notification::fake();

        $this->post('/register', [
            'name' => 'New User',
            'email' => 'new@example.com',
            'password' => 'a-perfectly-fine-password',
            'password_confirmation' => 'a-perfectly-fine-password',
        ])->assertRedirect(route('verification.notice'));

        Notification::assertSentTo(User::whereEmail('new@example.com')->firstOrFail(), QueuedVerifyEmail::class);
    }

    public function test_email_can_be_verified_from_a_signed_link(): void
    {
        $user = User::factory()->create(['email_verified_at' => null]);

        $url = URL::temporarySignedRoute('verification.verify', now()->addHour(), [
            'id' => $user->id,
            'hash' => sha1($user->email),
        ]);

        $this->actingAs($user)->get($url)->assertRedirect(route('account'));

        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    public function test_verification_fails_with_a_tampered_hash(): void
    {
        $user = User::factory()->create(['email_verified_at' => null]);

        $url = URL::temporarySignedRoute('verification.verify', now()->addHour(), [
            'id' => $user->id,
            'hash' => sha1('someone.else@example.com'),
        ]);

        $this->actingAs($user)->get($url)->assertForbidden();

        $this->assertNull($user->fresh()->email_verified_at);
    }
}
