<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The house banner earns nothing; network tags are the actual revenue stream.
 * The rule that matters is that neither reaches a paying subscriber.
 */
class AdNetworkTest extends TestCase
{
    use RefreshDatabase;

    protected function enableNetwork(): void
    {
        Setting::set('ad_enabled', '1');
        Setting::set('ad_mode', 'network');
        Setting::set('ad_network_head', '<script src="https://ads.example/loader.js"></script>');
        Setting::set('ad_network_default', '<ins class="adsbygoogle" data-slot="1234"></ins>');
    }

    public function test_network_tags_render_for_guests(): void
    {
        $this->enableNetwork();

        $response = $this->get('/');

        $response->assertSee('ads.example/loader.js', false);
        $response->assertSee('data-slot="1234"', false);
    }

    public function test_subscribers_load_no_ad_script_at_all(): void
    {
        $this->enableNetwork();

        $response = $this->actingAs(User::factory()->subscriber()->create())->get('/');

        $response->assertDontSee('ads.example/loader.js', false);
        $response->assertDontSee('data-slot="1234"', false);
    }

    public function test_free_users_still_see_ads(): void
    {
        $this->enableNetwork();

        $response = $this->actingAs(User::factory()->create(['role' => 'free']))->get('/');

        $response->assertSee('data-slot="1234"', false);
    }

    public function test_a_lapsed_subscriber_sees_ads_again(): void
    {
        $this->enableNetwork();

        $response = $this->actingAs(User::factory()->lapsedSubscriber()->create())->get('/');

        $response->assertSee('data-slot="1234"', false);
    }

    public function test_network_mode_with_no_tag_renders_no_empty_frame(): void
    {
        Setting::set('ad_enabled', '1');
        Setting::set('ad_mode', 'network');

        $this->get('/')->assertDontSee('Sponsored Advertisement', false);
    }

    public function test_a_placement_specific_tag_beats_the_default(): void
    {
        $this->enableNetwork();
        Setting::set('ad_network_header', '<ins data-slot="header-only"></ins>');

        $this->get('/')->assertSee('data-slot="header-only"', false);
    }

    public function test_disabling_ads_hides_everything(): void
    {
        $this->enableNetwork();
        Setting::set('ad_enabled', '0');

        $this->get('/')->assertDontSee('ads.example/loader.js', false);
    }

    public function test_house_mode_still_renders_the_sponsor_banner(): void
    {
        Setting::set('ad_enabled', '1');
        Setting::set('ad_mode', 'house');
        Setting::set('ad_partner_name', 'Test Partner');

        $this->get('/')->assertSee('Test Partner', false);
    }
}
