<?php

namespace App\Http\Controllers;

use App\Models\GameMatch;
use App\Models\Prediction;
use App\Models\Setting;
use App\Models\User;
use App\Jobs\FixtureIngestionJob;
use App\Jobs\RankingJob;
use App\Jobs\Ai5SelectionJob;
use App\Services\ClaudePredictionService;
use App\Support\PoissonEngine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Bus;

class AdminController extends Controller
{
    public function dashboard()
    {
        $matchCount = GameMatch::count();
        $predictionCount = Prediction::count();
        $subscriberCount = User::where('role', 'subscriber')->count();
        $freeUserCount = User::where('role', 'free')->count();

        $settings = $this->settingsPayload();

        return view('admin.dashboard', compact(
            'matchCount',
            'predictionCount',
            'subscriberCount',
            'freeUserCount',
            'settings'
        ));
    }

    public function settings()
    {
        $settings = $this->settingsPayload();

        return view('admin.settings', compact('settings'));
    }

    /**
     * Settings for the admin screens.
     *
     * Credentials are reported as a boolean "configured" flag instead of their
     * value — rendering a live API key into HTML puts it in browser history,
     * proxy logs, and any screen recording of the admin panel.
     *
     * @return array<string, mixed>
     */
    protected function settingsPayload(): array
    {
        $settings = [
            'prediction_provider' => Setting::get('prediction_provider', 'claude'),
            'claude_model' => Setting::get('claude_model', ClaudePredictionService::DEFAULT_MODEL),
            'claude_effort' => Setting::get('claude_effort', ClaudePredictionService::DEFAULT_EFFORT),
            'min_confidence_threshold' => Setting::get('min_confidence_threshold', '0.55'),
            'home_advantage' => Setting::get('home_advantage', (string) PoissonEngine::DEFAULT_HOME_ADVANTAGE),
            'default_league_average' => Setting::get('default_league_average', (string) PoissonEngine::DEFAULT_LEAGUE_AVERAGE),
            'football_data_competitions' => Setting::get('football_data_competitions', ''),
            'fixture_provider' => Setting::get('fixture_provider', config('services.fixtures.provider', 'football_data')),
            'paypal_mode' => Setting::get('paypal_mode', config('services.paypal.mode', 'sandbox')),
            'paypal_plan_id' => Setting::get('paypal_plan_id', config('services.paypal.plan_id', '')),
            'telegram_channel_id' => Setting::get('telegram_channel_id', config('services.telegram.channel_id')),
            'telegram_bot_username' => Setting::get('telegram_bot_username', config('services.telegram.bot_username')),
            'telegram_channel_username' => Setting::get('telegram_channel_username', config('services.telegram.channel_username')),
            'telegram_admin_support_url' => Setting::get('telegram_admin_support_url', config('services.telegram.admin_support_url')),
            'ga4_measurement_id' => Setting::get('ga4_measurement_id', ''),
            'search_console_verification_code' => Setting::get('search_console_verification_code', ''),
            'site_logo' => Setting::get('site_logo', ''),
            'site_favicon' => Setting::get('site_favicon', ''),
            'site_og_image' => Setting::get('site_og_image', ''),
        ];

        foreach (Setting::SECRET_KEYS as $key) {
            $settings[$key.'_configured'] = filled(Setting::get($key));
        }

        return $settings;
    }

    public function updateSettings(Request $request)
    {
        $validated = $request->validate([
            'gemini_api_key' => 'nullable|string|max:255',
            'claude_api_key' => 'nullable|string|max:255',
            'football_data_token' => 'nullable|string|max:255',
            'football_data_competitions' => 'nullable|string|max:255',
            'fixture_provider' => 'required|in:football_data,sample',
            'flutterwave_secret_key' => 'nullable|string|max:255',
            'flutterwave_public_key' => 'nullable|string|max:255',
            'flutterwave_webhook_hash' => 'nullable|string|max:255',
            'paypal_client_id' => 'nullable|string|max:255',
            'paypal_secret' => 'nullable|string|max:255',
            'paypal_webhook_id' => 'nullable|string|max:255',
            'paypal_plan_id' => 'nullable|string|max:100',
            'paypal_mode' => 'required|in:sandbox,live',
            'telegram_channel_id' => 'nullable|string|max:100',
            'ga4_measurement_id' => 'nullable|string|max:100',
            'search_console_verification_code' => 'nullable|string|max:255',
            'site_logo' => 'nullable|url|max:500',
            'site_favicon' => 'nullable|url|max:500',
            'site_og_image' => 'nullable|url|max:500',
            'telegram_bot_token' => 'nullable|string|max:255',
            'telegram_webhook_secret' => 'nullable|string|max:255',
            'telegram_bot_username' => 'nullable|string|max:100',
            'telegram_channel_username' => 'nullable|string|max:100',
            'telegram_admin_support_url' => 'nullable|string|max:255',
            'prediction_provider' => 'required|in:claude,poisson_xg',
            'claude_model' => 'nullable|string|max:100',
            'claude_effort' => 'nullable|in:low,medium,high,xhigh,max',
            'min_confidence_threshold' => 'required|numeric|min:0.1|max:0.99',
            'home_advantage' => 'required|numeric|min:1.0|max:2.0',
            'default_league_average' => 'required|numeric|min:0.5|max:4.0',
        ]);

        foreach ($validated as $key => $value) {
            // A blank credential field means "leave it alone", not "erase it" —
            // the form never renders the current value back, so submitting the
            // page would otherwise wipe every key.
            if ($value === null || ($value === '' && Setting::isSecret($key))) {
                continue;
            }

            Setting::set($key, $value);
        }

        return redirect()->route('admin.settings')->with('success', 'AI parameters and API keys updated successfully!');
    }

    public function runPipeline()
    {
        // Queued and chained rather than run inline: ingestion makes one HTTP
        // call per fixture plus a model call each, which will exceed the
        // request timeout well before the fixture list gets interesting.
        Bus::chain([
            new FixtureIngestionJob,
            new RankingJob,
            new Ai5SelectionJob,
        ])->dispatch();

        return redirect()->route('admin.dashboard')
            ->with('success', 'Pipeline queued. Fixtures, predictions and rankings will refresh in the background.');
    }
}
