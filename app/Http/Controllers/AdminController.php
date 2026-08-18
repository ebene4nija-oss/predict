<?php

namespace App\Http\Controllers;

use App\Models\GameMatch;
use App\Models\PipelineRun;
use App\Models\Prediction;
use App\Models\Setting;
use App\Models\User;
use App\Services\ClaudePredictionService;
use App\Services\GeminiPredictionService;
use App\Services\KimiPredictionService;
use App\Services\OpenAiPredictionService;
use App\Services\PostGenerationService;
use App\Services\PreviewGenerationService;
use App\Services\TrackRecordService;
use App\Support\MarketRegistry;
use App\Support\PoissonEngine;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function dashboard(TrackRecordService $trackRecordService)
    {
        $matchCount = GameMatch::count();
        $predictionCount = Prediction::count();
        $subscriberCount = User::where('role', 'subscriber')->count();
        $freeUserCount = User::where('role', 'free')->count();

        $settings = $this->settingsPayload();

        $stats = $trackRecordService->getAccuracyStats();
        $markets = \App\Support\MarketRegistry::all();

        // So the live transcript of the last run stays reachable after the admin
        // navigates away from it.
        $lastRun = null;
        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('pipeline_runs')) {
                $lastRun = PipelineRun::with('user')->latest('id')->first();
            } else {
                // Auto-migrate if table missing
                \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
                if (\Illuminate\Support\Facades\Schema::hasTable('pipeline_runs')) {
                    $lastRun = PipelineRun::with('user')->latest('id')->first();
                }
            }
        } catch (\Throwable) {
            $lastRun = null;
        }

        return view('admin.dashboard', compact(
            'matchCount',
            'predictionCount',
            'subscriberCount',
            'freeUserCount',
            'settings',
            'lastRun',
            'stats',
            'markets'
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
            'openai_model' => Setting::get('openai_model', OpenAiPredictionService::DEFAULT_MODEL),
            'gemini_prediction_model' => Setting::get('gemini_prediction_model', GeminiPredictionService::DEFAULT_MODEL),
            'kimi_model' => Setting::get('kimi_model', KimiPredictionService::DEFAULT_MODEL),
            'gemini_model' => app(PreviewGenerationService::class)->model(),
            'min_confidence_threshold' => Setting::get('min_confidence_threshold', '0.55'),
            'home_advantage' => Setting::get('home_advantage', (string) PoissonEngine::DEFAULT_HOME_ADVANTAGE),
            'default_league_average' => Setting::get('default_league_average', (string) PoissonEngine::DEFAULT_LEAGUE_AVERAGE),
            'football_data_competitions' => Setting::get('football_data_competitions', ''),
            'preview_lead_days' => GameMatch::previewLeadDays(),
            'home_fixture_days' => GameMatch::homeFixtureDays(),
            'preview_refresh_days' => GameMatch::previewRefreshDays(),
            'ai_posts_autopublish' => Setting::get('ai_posts_autopublish', '0'),
            'news_ai_provider' => Setting::get('news_ai_provider', app(PostGenerationService::class)->provider()),
            'blog_ai_model' => Setting::get('blog_ai_model', ''),
            'fixture_provider' => Setting::get('fixture_provider', config('services.fixtures.provider', 'football_data')),
            'price_ngn' => Setting::get('price_ngn', (string) config('pricing.prices.NGN.amount', 5000)),
            'price_usd' => Setting::get('price_usd', (string) config('pricing.prices.USD.amount', 4.99)),
            'free_pick_limit' => \App\Http\Controllers\PredictionController::freePicks(),
            'home_hero_stats_enabled' => Setting::get('home_hero_stats_enabled', '1'),
            'home_hero_section_enabled' => Setting::get('home_hero_section_enabled', '1'),
            'pricing_default_country' => Setting::get('pricing_default_country', config('pricing.default_country', 'NG')),
            'paypal_email' => Setting::get('paypal_email', config('services.paypal.email', '')),
            'paypal_mode' => Setting::get('paypal_mode', config('services.paypal.mode', 'sandbox')),
            'paypal_plan_id' => Setting::get('paypal_plan_id', config('services.paypal.plan_id', '')),
            'telegram_channel_id' => Setting::get('telegram_channel_id', config('services.telegram.channel_id')),
            'telegram_bot_username' => Setting::get('telegram_bot_username', config('services.telegram.bot_username')),
            'telegram_channel_username' => Setting::get('telegram_channel_username', config('services.telegram.channel_username')),
            'telegram_admin_support_url' => Setting::get('telegram_admin_support_url', config('services.telegram.admin_support_url')),
            'telegram_banner_enabled' => Setting::get('telegram_banner_enabled', '1'),
            'telegram_banner_badge' => Setting::get('telegram_banner_badge', 'OFFICIAL TELEGRAM COMMUNITY'),
            'telegram_banner_icon' => Setting::get('telegram_banner_icon', '✈️'),
            'telegram_banner_headline' => Setting::get('telegram_banner_headline', 'Get Instant AI Predictions & Admin Support on Telegram'),
            'telegram_banner_description' => Setting::get('telegram_banner_description', 'Join our public channel for instant match alerts or contact live admin support anytime.'),
            'telegram_banner_cta_text' => Setting::get('telegram_banner_cta_text', 'Join VIP Channel'),
            'telegram_support_cta_text' => Setting::get('telegram_support_cta_text', '💬 Admin Support'),
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
            'sportybet_booking_code_enabled' => Setting::get('sportybet_booking_code_enabled', '1'),
            'sportybet_top5_booking_code' => Setting::get('sportybet_top5_booking_code', ''),
            'sportybet_region' => Setting::get('sportybet_region', 'ng'),
            'sportybet_custom_url' => Setting::get('sportybet_custom_url', ''),
        ];

        foreach (\App\Support\MarketRegistry::all() as $mKey => $mDef) {
            $settings["market_threshold_{$mKey}"] = Setting::get("min_confidence_threshold.{$mKey}", '');
        }

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
            'openai_api_key' => 'nullable|string|max:255',
            'kimi_api_key' => 'nullable|string|max:255',
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
            'news_ai_provider' => 'nullable|in:gemini,claude,openai',
            'blog_ai_model' => 'nullable|string|max:100',
            'flutterwave_secret_key' => 'nullable|string|max:255',
            'flutterwave_public_key' => 'nullable|string|max:255',
            'flutterwave_encryption_key' => 'nullable|string|max:255',
            'flutterwave_webhook_hash' => 'nullable|string|max:255',
            'paypal_email' => 'nullable|string|max:255',
            'paypal_client_id' => 'nullable|string|max:255',
            'paypal_secret' => 'nullable|string|max:255',
            'paypal_webhook_id' => 'nullable|string|max:255',
            'paypal_plan_id' => 'nullable|string|max:100',
            'paypal_mode' => 'required|in:sandbox,live',
            'price_ngn' => 'nullable|numeric|min:1',
            'price_usd' => 'nullable|numeric|min:0.01',
            'free_pick_limit' => 'nullable|integer|min:1|max:10',
            'pricing_default_country' => 'nullable|string|size:2',
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
            'site_logo' => 'nullable|string|max:500',
            'site_favicon' => 'nullable|string|max:500',
            'site_og_image' => 'nullable|string|max:500',
            'logo_file' => 'nullable|file|mimes:png,jpg,jpeg,webp,svg,gif|max:5120',
            'favicon_file' => 'nullable|file|mimes:ico,png,svg,jpg,jpeg|max:2048',
            'og_image_file' => 'nullable|file|mimes:png,jpg,jpeg,webp|max:5120',
            'telegram_bot_token' => 'nullable|string|max:255',
            'telegram_webhook_secret' => 'nullable|string|max:255',
            'telegram_bot_username' => 'nullable|string|max:100',
            'telegram_channel_username' => 'nullable|string|max:255',
            'telegram_admin_support_url' => 'nullable|string|max:255',
            'telegram_banner_badge' => 'nullable|string|max:100',
            'telegram_banner_icon' => 'nullable|string|max:50',
            'telegram_banner_headline' => 'nullable|string|max:255',
            'telegram_banner_description' => 'nullable|string|max:500',
            'telegram_banner_cta_text' => 'nullable|string|max:100',
            'telegram_support_cta_text' => 'nullable|string|max:100',
            'sportybet_top5_booking_code' => 'nullable|string|max:50',
            'sportybet_region' => 'nullable|in:ng,gh,ke,ug,zm,tz',
            'sportybet_custom_url' => 'nullable|string|max:500',
            'sportybet_cookie' => 'nullable|string',
            'sportybet_token' => 'nullable|string|max:255',
            'prediction_provider' => 'required|in:claude,chatgpt,openai,gemini,kimi,poisson_xg',
            'claude_model' => 'nullable|string|max:100',
            'openai_model' => 'nullable|string|max:100',
            'gemini_prediction_model' => 'nullable|string|max:100',
            'kimi_model' => 'nullable|string|max:100',
            'gemini_model' => 'nullable|string|max:100',
            'claude_effort' => 'nullable|in:low,medium,high,xhigh,max',
            'min_confidence_threshold' => 'required|numeric|min:0.1|max:0.99',
            'home_advantage' => 'required|numeric|min:1.0|max:2.0',
            'default_league_average' => 'required|numeric|min:0.5|max:4.0',
        ]);

        // Process direct file uploads for branding
        $uploadsDir = public_path('uploads/branding');
        if (!file_exists($uploadsDir)) {
            @mkdir($uploadsDir, 0755, true);
        }

        if ($request->hasFile('logo_file')) {
            $file = $request->file('logo_file');
            $filename = 'logo_' . time() . '.' . $file->getClientOriginalExtension();
            $file->move($uploadsDir, $filename);
            $validated['site_logo'] = asset('uploads/branding/' . $filename);
        }

        if ($request->hasFile('favicon_file')) {
            $file = $request->file('favicon_file');
            $filename = 'favicon_' . time() . '.' . $file->getClientOriginalExtension();
            $file->move($uploadsDir, $filename);
            $validated['site_favicon'] = asset('uploads/branding/' . $filename);
        }

        if ($request->hasFile('og_image_file')) {
            $file = $request->file('og_image_file');
            $filename = 'og_' . time() . '.' . $file->getClientOriginalExtension();
            $file->move($uploadsDir, $filename);
            $validated['site_og_image'] = asset('uploads/branding/' . $filename);
        }

        unset($validated['logo_file'], $validated['favicon_file'], $validated['og_image_file']);

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

        if ($request->has('homepage_toggles_present')) {
            Setting::set('home_hero_stats_enabled', $request->boolean('home_hero_stats_enabled') ? '1' : '0');
            Setting::set('home_hero_section_enabled', $request->boolean('home_hero_section_enabled') ? '1' : '0');
        }

        if ($request->has('telegram_banner_present')) {
            Setting::set('telegram_banner_enabled', $request->boolean('telegram_banner_enabled') ? '1' : '0');
        }

        if ($request->has('sportybet_booking_present')) {
            Setting::set('sportybet_booking_code_enabled', $request->boolean('sportybet_booking_code_enabled') ? '1' : '0');
        }

        // Save or clear per-market confidence threshold overrides
        foreach (\App\Support\MarketRegistry::keys() as $mKey) {
            $field = "market_threshold_{$mKey}";
            if ($request->has($field)) {
                $rawVal = $request->input($field);
                if ($rawVal !== null && $rawVal !== '' && is_numeric($rawVal)) {
                    $clamped = max(0.10, min(0.99, (float) $rawVal));
                    Setting::set("min_confidence_threshold.{$mKey}", (string) $clamped);
                } else {
                    Setting::where('key', "min_confidence_threshold.{$mKey}")->delete();
                }
            }
        }

        \App\Support\MarketRegistry::flush();

        return redirect()->route('admin.settings')->with('success', 'AI parameters, payment pricing, and Telegram banner settings updated successfully!');
    }

}
