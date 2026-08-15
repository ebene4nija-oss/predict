<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * The gateways will not approve a live merchant account unless these three are
 * publicly reachable, so a broken route here blocks taking money.
 */
class LegalPagesTest extends TestCase
{
    use RefreshDatabase;

    public static function legalRoutes(): array
    {
        return [
            'terms' => ['/terms', 'Terms of Service'],
            'privacy' => ['/privacy', 'Privacy Policy'],
            'refunds' => ['/refunds', 'Refund'],
        ];
    }

    #[DataProvider('legalRoutes')]
    public function test_legal_page_is_publicly_reachable(string $path, string $expected): void
    {
        $this->get($path)
            ->assertOk()
            ->assertSee($expected, false);
    }

    public function test_footer_links_to_every_legal_page(): void
    {
        $response = $this->get('/');

        $response->assertSee(route('legal.terms'), false);
        $response->assertSee(route('legal.privacy'), false);
        $response->assertSee(route('legal.refunds'), false);
    }

    public function test_legal_pages_are_listed_in_the_sitemap(): void
    {
        $response = $this->get('/sitemap.xml');

        $response->assertOk();
        $response->assertSee(route('legal.terms'), false);
        $response->assertSee(route('legal.privacy'), false);
        $response->assertSee(route('legal.refunds'), false);
    }

    public function test_refund_policy_states_that_losing_picks_are_not_refundable(): void
    {
        // The single most disputed point with a predictions subscription; if
        // this wording goes missing, chargeback defence goes with it.
        $this->get('/refunds')->assertSee('do not refund on the basis of prediction outcomes', false);
    }
}
