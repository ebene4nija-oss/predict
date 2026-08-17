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
            'gemini_model' => app(PreviewGenerationService::class)->model(),
            'min_confidence_threshold' => Setting::get('min_confidence_threshold', '0.55'),
            'home_advantage' => Setting::get('home_advantage', (string) PoissonEngine::DEFAULT_HOME_ADVANTAGE),
            'default_league_average' => Setting::get('default_league_average', (string) PoissonEngine::DEFAULT_LEAGUE_AVERAGE),
            'football_data_competitions' => Setting::get('football_data_competitions', ''),
            'preview_lead_days' => GameMatch::previewLeadDays(),
            'home_fixture_days' => GameMatch::homeFixtureDays(),
            'preview_refresh_days' => GameMatch::previewRefreshDays(),
            'ai_posts_autopublish' => Setting::get('ai_posts_autopublish', '0'),
            'blog_ai_model' => Setting::get('blog_ai_model', ''),
            'fixture_provider' => Setting::get('fixture_provider', config('services.fixtures.provider', 'football_data')),
            'paypal_mode' => Setting::get('paypal_mode', config('services.paypal.mode', 'sandbox')),
            'paypal_plan_id' => Setting::get('paypal_plan_id', config('services.paypal.plan_id', '')),
            'telegram_channel_id' => Setting::get('telegram_channel_id', config('services.telegram.channel_id')),
            'telegram_bot_username' => Setting::get('telegram_bot_username', config('services.telegram.bot_username')),
            'telegram_channel_username' => Setting::get('telegram_channel_username', config('services.telegram.channel_username')),
            'telegram_admin_support_url' => Setting::get('telegram_admin_support_url', config('services.telegram.admin_support_url')),
            // Mail. Defaults come from config so the form shows whatever the
            // app is actually using, whether that is .env or the dashboard.
            'mail_host' => Setting::get('mail_host', config('mail.mailers.smtp.host')),
            'mail_port' => Setting::get('mail_port', (string) config('mail.mailers.smtp.port')),
            'mail_scheme' => Setting::get('mail_scheme', config('mail.mailers.smtp.scheme') ?? ''),
            'mail_username' => Setting::get('mail_username', config('mail.mailers.smtp.username')),
            'mail_from_address' => Setting::get('mail_from_address', config('mail.from.address')),
            'mail_from_name' => Setting::get('mail_from_name', config('mail.from.name')),
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
            // Nullable, not required: an omitted value keeps the stored setting
            // rather than rejecting the whole form. The field has a default and
            // is clamped on read, so "absent" is always safe.
            'preview_lead_days' => 'nullable|integer|min:1|max:'.GameMatch::MAX_PREVIEW_LEAD_DAYS,
            // Both are clamped against the lead time on read, so a value above
            // it is harmless rather than rejected — the form does not need to
            // know which order the two fields were saved in.
            'home_fixture_days' => 'nullable|integer|min:1|max:'.GameMatch::MAX_PREVIEW_LEAD_DAYS,
            'preview_refresh_days' => 'nullable|integer|min:1|max:'.GameMatch::MAX_PREVIEW_LEAD_DAYS,
            'blog_ai_model' => 'nullable|string|max:100',
            'flutterwave_secret_key' => 'nullable|string|max:255',
            'flutterwave_public_key' => 'nullable|string|max:255',
            'flutterwave_webhook_hash' => 'nullable|string|max:255',
            'paypal_client_id' => 'nullable|string|max:255',
            'paypal_secret' => 'nullable|string|max:255',
            'paypal_webhook_id' => 'nullable|string|max:255',
            'paypal_plan_id' => 'nullable|string|max:100',
            'paypal_mode' => 'required|in:sandbox,live',
            'telegram_channel_id' => 'nullable|string|max:100',
            'mail_host' => 'nullable|string|max:255',
            'mail_port' => 'nullable|integer|min:1|max:65535',
            'mail_scheme' => 'nullable|in:tls,ssl',
            'mail_username' => 'nullable|string|max:255',
            'mail_password' => 'nullable|string|max:255',
            'mail_from_address' => 'nullable|email|max:255',
            'mail_from_name' => 'nullable|string|max:255',
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
            'gemini_model' => 'nullable|string|max:100',
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

        // An unticked checkbox is absent from the payload, so the loop above
        // could only ever turn this on. The form sends a hidden marker so this
        // is written only when the section was actually submitted — otherwise
        // any partial settings post would silently switch it off.
        if ($request->has('ai_posts_autopublish_present')) {
            Setting::set('ai_posts_autopublish', $request->boolean('ai_posts_autopublish') ? '1' : '0');
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
