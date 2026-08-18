<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use App\Support\TelegramHandles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The public Telegram identifiers are admin-managed. These used to be read
 * straight from config() on the join banner and hardcoded into two templates,
 * so an admin could change the handle in the dashboard, see it saved, and still
 * have every visitor sent to the old channel.
 */
class TelegramHandlesTest extends TestCase
{
    use RefreshDatabase;

    protected function baseSettings(): array
    {
        return [
            'prediction_provider' => 'claude',
            'min_confidence_threshold' => 0.60,
            'home_advantage' => 1.15,
            'default_league_average' => 1.35,
            'fixture_provider' => 'football_data',
            'paypal_mode' => 'sandbox',
        ];
    }

    public function test_handles_fall_back_to_config_when_no_setting_is_saved(): void
    {
        config([
            'services.telegram.bot_username' => 'EnvBot',
            'services.telegram.channel_username' => 'EnvChannel',
        ]);

        $this->assertSame('EnvBot', TelegramHandles::botUsername());
        $this->assertSame('EnvChannel', TelegramHandles::channelUsername());
    }

    public function test_admin_setting_overrides_the_config_fallback(): void
    {
        config(['services.telegram.channel_username' => 'EnvChannel']);
        Setting::set('telegram_channel_username', 'AdminChannel');

        $this->assertSame('AdminChannel', TelegramHandles::channelUsername());
        $this->assertSame('https://t.me/AdminChannel', TelegramHandles::channelUrl());
    }

    public function test_a_leading_at_sign_is_normalised(): void
    {
        Setting::set('telegram_bot_username', '@SpacedBot ');

        $this->assertSame('SpacedBot', TelegramHandles::botUsername());
        $this->assertSame('@SpacedBot', TelegramHandles::botHandle());
        $this->assertSame('https://t.me/SpacedBot', TelegramHandles::botUrl());
    }

    public function test_a_blanked_setting_does_not_produce_a_link_to_nowhere(): void
    {
        config(['services.telegram.channel_username' => 'EnvChannel']);
        Setting::set('telegram_channel_username', '');

        $this->assertSame('EnvChannel', TelegramHandles::channelUsername());
    }

    public function test_support_contact_accepts_either_a_bare_handle_or_a_full_link(): void
    {
        Setting::set('telegram_admin_support_url', 'HelpDesk');
        $this->assertSame('https://t.me/HelpDesk', TelegramHandles::supportUrl());

        Setting::set('telegram_admin_support_url', 'https://t.me/+privateInvite');
        $this->assertSame('https://t.me/+privateInvite', TelegramHandles::supportUrl());
    }

    public function test_the_join_banner_uses_the_admin_channel_not_the_env_default(): void
    {
        config(['services.telegram.channel_username' => 'EnvChannel']);
        Setting::set('telegram_channel_username', 'AdminChannel');
        Setting::set('telegram_admin_support_url', 'AdminSupport');

        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('https://t.me/AdminChannel')
            ->assertSee('https://t.me/AdminSupport')
            ->assertDontSee('https://t.me/EnvChannel');
    }

    public function test_the_support_button_is_hidden_when_no_contact_is_configured(): void
    {
        config(['services.telegram.admin_support_url' => '']);

        // The banner headline also says "Admin Support", so assert on the
        // button label specifically rather than the words alone.
        $this->get('/')->assertOk()->assertDontSee('💬 Admin Support');
    }

    public function test_the_account_page_shows_the_admin_bot_handle(): void
    {
        config(['services.telegram.bot_username' => 'EnvBot']);
        Setting::set('telegram_bot_username', 'AdminBot');

        $this->actingAs(User::factory()->create())
            ->get(route('account'))
            ->assertOk()
            ->assertSee('@AdminBot')
            ->assertDontSee('@EnvBot');
    }

    public function test_admin_analytics_shows_the_admin_channel_handle(): void
    {
        Setting::set('telegram_channel_username', 'AdminChannel');

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get(route('admin.analytics.index'))
            ->assertOk()
            ->assertSee('@AdminChannel');
    }

    public function test_every_public_telegram_identifier_can_be_saved_from_the_dashboard(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $payload = $this->baseSettings() + [
            'telegram_bot_username' => 'SavedBot',
            'telegram_channel_username' => 'SavedChannel',
            'telegram_channel_id' => '-1009876543210',
            'telegram_admin_support_url' => 'https://t.me/SavedSupport',
        ];

        $this->actingAs($admin)
            ->post(route('admin.settings.update'), $payload)
            ->assertRedirect(route('admin.settings'))
            ->assertSessionHasNoErrors();

        $this->assertSame('SavedBot', Setting::get('telegram_bot_username'));
        $this->assertSame('SavedChannel', Setting::get('telegram_channel_username'));
        $this->assertSame('-1009876543210', Setting::get('telegram_channel_id'));
        $this->assertSame('https://t.me/SavedSupport', Setting::get('telegram_admin_support_url'));
    }

    public function test_the_settings_screen_renders_the_saved_identifiers(): void
    {
        Setting::set('telegram_bot_username', 'SavedBot');
        Setting::set('telegram_admin_support_url', 'https://t.me/SavedSupport');

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get(route('admin.settings'))
            ->assertOk()
            ->assertSee('SavedBot')
            ->assertSee('https://t.me/SavedSupport');
    }

    public function test_vip_channel_banner_customization_renders_on_public_pages(): void
    {
        Setting::set('telegram_channel_username', 'CustomVipChannel');
        Setting::set('telegram_banner_headline', 'Exclusive High Odds VIP Community');
        Setting::set('telegram_banner_cta_text', 'Access VIP Now');
        Setting::set('telegram_banner_badge', 'EXCLUSIVE VIP CLUB');

        $this->get('/')
            ->assertOk()
            ->assertSee('Exclusive High Odds VIP Community')
            ->assertSee('Access VIP Now')
            ->assertSee('EXCLUSIVE VIP CLUB')
            ->assertSee('https://t.me/CustomVipChannel');
    }

    public function test_vip_channel_banner_can_be_disabled_by_admin(): void
    {
        Setting::set('telegram_channel_username', 'CustomVipChannel');
        Setting::set('telegram_banner_enabled', '0');

        $this->get('/')
            ->assertOk()
            ->assertDontSee('https://t.me/CustomVipChannel')
            ->assertDontSee('Join VIP Channel');
    }

    public function test_channel_url_supports_full_invite_links(): void
    {
        Setting::set('telegram_channel_username', 'https://t.me/+joinPrivateVip');

        $this->assertSame('https://t.me/+joinPrivateVip', TelegramHandles::channelUrl());
        $this->get('/')
            ->assertOk()
            ->assertSee('https://t.me/+joinPrivateVip');
    }

    public function test_telegram_banner_renders_on_news_and_prediction_pages(): void
    {
        Setting::set('telegram_channel_username', 'CustomVipChannel');

        // Top Picks prediction page
        $this->get(route('top.picks'))
            ->assertOk()
            ->assertSee('https://t.me/CustomVipChannel')
            ->assertSee('Join VIP Channel');

        // Match previews hub
        $this->get(route('matches.index'))
            ->assertOk()
            ->assertSee('https://t.me/CustomVipChannel')
            ->assertSee('Join VIP Channel');

        // News index
        $this->get(route('blog.index'))
            ->assertOk()
            ->assertSee('https://t.me/CustomVipChannel')
            ->assertSee('Join VIP Channel');
    }
}

