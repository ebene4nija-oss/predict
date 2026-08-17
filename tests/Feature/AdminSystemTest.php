<?php

namespace Tests\Feature;

use App\Http\Controllers\AdminSystemController;
use App\Models\Setting;
use App\Models\User;
use App\Support\MailSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AdminSystemTest extends TestCase
{
    use RefreshDatabase;

    protected function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    protected function baseSettings(): array
    {
        return [
            'prediction_provider' => 'claude',
            'min_confidence_threshold' => 0.60,
            'home_advantage' => 1.15,
            'default_league_average' => 1.35,
            'fixture_provider' => 'football_data',
            'paypal_mode' => 'sandbox',
            'preview_lead_days' => 3,
        ];
    }

    // --- access control ----------------------------------------------------

    public function test_the_system_page_is_closed_to_guests_and_non_admins(): void
    {
        $this->get(route('admin.system'))->assertRedirect(route('login'));

        // EnsureAdmin bounces browser requests to home and only 403s JSON.
        $this->actingAs(User::factory()->create(['role' => 'free']))
            ->get(route('admin.system'))
            ->assertRedirect(route('home'));
    }

    public function test_a_non_admin_cannot_run_a_maintenance_command(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'subscriber']))
            ->post(route('admin.system.command'), ['command' => 'cache_clear'])
            ->assertRedirect(route('home'));

        $this->actingAs(User::factory()->create(['role' => 'expert']))
            ->postJson(route('admin.system.command'), ['command' => 'cache_clear'])
            ->assertForbidden();
    }

    public function test_the_system_page_renders_for_an_admin(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.system'))
            ->assertOk()
            ->assertSee('System Maintenance')
            ->assertSee('Send test email');
    }

    // --- command whitelist -------------------------------------------------

    public function test_an_arbitrary_command_is_rejected(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.system.command'), ['command' => 'db:wipe'])
            ->assertSessionHasErrors('command');

        $this->actingAs($this->admin())
            ->post(route('admin.system.command'), ['command' => 'migrate:fresh --seed'])
            ->assertSessionHasErrors('command');
    }

    public function test_the_whitelist_contains_nothing_destructive(): void
    {
        $commands = array_column(AdminSystemController::COMMANDS, 'command');

        foreach (['migrate:fresh', 'migrate:reset', 'migrate:rollback', 'db:wipe', 'db:seed'] as $forbidden) {
            $this->assertNotContains($forbidden, $commands);
        }
    }

    public function test_a_whitelisted_command_runs_and_reports_back(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.system.command'), ['command' => 'cache_clear'])
            ->assertRedirect()
            ->assertSessionHas('command_output');

        $result = session('command_output');
        $this->assertTrue($result['ok']);
        $this->assertSame('Flush application cache', $result['label']);
    }

    // --- mail --------------------------------------------------------------

    public function test_the_test_mail_is_sent_immediately_rather_than_queued(): void
    {
        // The array transport records what actually reached it, which is the
        // point: a queued test would report success before the transport was
        // touched at all, hiding the very failure being diagnosed.
        config(['mail.default' => 'array']);

        $this->actingAs($this->admin())
            ->post(route('admin.system.mail-test'), ['recipient' => 'someone@example.com'])
            ->assertRedirect()
            ->assertSessionHas('mail_result');

        $messages = Mail::mailer('array')->getSymfonyTransport()->messages();

        $this->assertCount(1, $messages);
        $this->assertSame(
            'someone@example.com',
            $messages[0]->getOriginalMessage()->getTo()[0]->getAddress()
        );
        $this->assertTrue(session('mail_result')['ok']);
        $this->assertDatabaseCount('jobs', 0);
    }

    public function test_the_test_mail_recipient_must_be_an_email(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.system.mail-test'), ['recipient' => 'not-an-address'])
            ->assertSessionHasErrors('recipient');
    }

    public function test_smtp_settings_saved_in_the_dashboard_override_the_env_config(): void
    {
        config([
            'mail.default' => 'log',
            'mail.mailers.smtp.host' => 'env-host.example',
            'mail.mailers.smtp.port' => 2525,
        ]);

        Setting::set('mail_host', 'smtp.mydomain.com');
        Setting::set('mail_port', '587');
        Setting::set('mail_from_address', 'noreply@mydomain.com');

        MailSettings::apply();

        $this->assertSame('smtp.mydomain.com', config('mail.mailers.smtp.host'));
        $this->assertSame(587, config('mail.mailers.smtp.port'));
        $this->assertSame('noreply@mydomain.com', config('mail.from.address'));

        // A dashboard SMTP host means mail is meant to go out, so the `log`
        // mailer from .env must not silently swallow it.
        $this->assertSame('smtp', config('mail.default'));
    }

    public function test_mail_config_is_untouched_when_nothing_is_saved(): void
    {
        config(['mail.default' => 'log', 'mail.mailers.smtp.host' => 'env-host.example']);

        MailSettings::apply();

        $this->assertSame('log', config('mail.default'));
        $this->assertSame('env-host.example', config('mail.mailers.smtp.host'));
    }

    public function test_the_smtp_password_is_encrypted_at_rest(): void
    {
        Setting::set('mail_password', 'sup3r-s3cret');

        $stored = \DB::table('settings')->where('key', 'mail_password')->value('value');

        $this->assertNotSame('sup3r-s3cret', $stored);
        $this->assertSame('sup3r-s3cret', Setting::get('mail_password'));
    }

    public function test_smtp_settings_can_be_saved_from_the_settings_form(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.settings.update'), $this->baseSettings() + [
                'mail_host' => 'smtp.mydomain.com',
                'mail_port' => 587,
                'mail_scheme' => 'tls',
                'mail_username' => 'noreply@mydomain.com',
                'mail_password' => 'mailbox-password',
                'mail_from_address' => 'noreply@mydomain.com',
                'mail_from_name' => 'Guaranteed Correct',
            ])
            ->assertRedirect(route('admin.settings'))
            ->assertSessionHasNoErrors();

        $this->assertSame('smtp.mydomain.com', Setting::get('mail_host'));
        $this->assertSame('tls', Setting::get('mail_scheme'));
        $this->assertSame('mailbox-password', Setting::get('mail_password'));
    }

    public function test_a_blank_password_leaves_the_stored_one_alone(): void
    {
        Setting::set('mail_password', 'original-password');

        $this->actingAs($this->admin())
            ->post(route('admin.settings.update'), $this->baseSettings() + [
                'mail_host' => 'smtp.mydomain.com',
                'mail_password' => '',
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame('original-password', Setting::get('mail_password'));
    }

    // --- integration tests -------------------------------------------------

    public function test_an_unknown_integration_is_rejected(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.system.integration-test'), ['service' => 'not-a-service'])
            ->assertSessionHasErrors('service');
    }

    public function test_a_missing_credential_reports_rather_than_calling_out(): void
    {
        Http::fake();

        $this->actingAs($this->admin())
            ->post(route('admin.system.integration-test'), ['service' => 'telegram'])
            ->assertRedirect();

        $this->assertFalse(session('integration_result')['ok']);
        Http::assertNothingSent();
    }

    public function test_a_working_credential_is_reported_as_valid(): void
    {
        Setting::set('telegram_bot_token', '123:ABC');
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['username' => 'MyBot']]),
        ]);

        $this->actingAs($this->admin())
            ->post(route('admin.system.integration-test'), ['service' => 'telegram']);

        $result = session('integration_result');
        $this->assertTrue($result['ok']);
        $this->assertStringContainsString('@MyBot', $result['message']);
    }

    public function test_a_rejected_credential_is_reported_as_a_failure(): void
    {
        Setting::set('football_data_token', 'wrong-token');
        Http::fake(['api.football-data.org/*' => Http::response([], 403)]);

        $this->actingAs($this->admin())
            ->post(route('admin.system.integration-test'), ['service' => 'football_data']);

        $result = session('integration_result');
        $this->assertFalse($result['ok']);
        $this->assertStringContainsString('403', $result['message']);
    }

    public function test_an_unreachable_provider_does_not_produce_an_exception_page(): void
    {
        Setting::set('claude_api_key', 'sk-ant-test');
        Http::fake(fn () => throw new \RuntimeException('Connection timed out'));

        $this->actingAs($this->admin())
            ->post(route('admin.system.integration-test'), ['service' => 'claude'])
            ->assertRedirect();

        $this->assertFalse(session('integration_result')['ok']);
    }

    public function test_a_working_gateway_still_warns_when_its_webhook_secret_is_missing(): void
    {
        Setting::set('flutterwave_secret_key', 'FLWSECK-test');
        Http::fake(['api.flutterwave.com/*' => Http::response(['status' => 'success'])]);

        $this->actingAs($this->admin())
            ->post(route('admin.system.integration-test'), ['service' => 'flutterwave']);

        $result = session('integration_result');
        $this->assertTrue($result['ok']);
        $this->assertStringContainsString('webhooks are rejected', $result['message']);
    }
}
