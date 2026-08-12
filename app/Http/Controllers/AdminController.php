<?php

namespace App\Http\Controllers;

use App\Models\GameMatch;
use App\Models\Prediction;
use App\Models\Setting;
use App\Models\User;
use App\Services\PredictionService;
use App\Services\PreviewGenerationService;
use App\Jobs\FixtureIngestionJob;
use App\Jobs\RankingJob;
use App\Jobs\Ai5SelectionJob;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function dashboard()
    {
        $matchCount = GameMatch::count();
        $predictionCount = Prediction::count();
        $subscriberCount = User::where('role', 'subscriber')->count();
        $freeUserCount = User::where('role', 'free')->count();

        $settings = [
            'gemini_api_key' => Setting::get('gemini_api_key', env('GEMINI_API_KEY', '')),
            'claude_api_key' => Setting::get('claude_api_key', env('CLAUDE_API_KEY', '')),
            'prediction_provider' => Setting::get('prediction_provider', 'claude'),
            'min_confidence_threshold' => Setting::get('min_confidence_threshold', '0.55'),
            'poisson_home_weight' => Setting::get('poisson_home_weight', '1.3'),
            'poisson_away_weight' => Setting::get('poisson_away_weight', '1.3'),
        ];

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
        $settings = [
            'gemini_api_key' => Setting::get('gemini_api_key', env('GEMINI_API_KEY', '')),
            'claude_api_key' => Setting::get('claude_api_key', env('CLAUDE_API_KEY', '')),
            'prediction_provider' => Setting::get('prediction_provider', 'claude'),
            'min_confidence_threshold' => Setting::get('min_confidence_threshold', '0.55'),
            'poisson_home_weight' => Setting::get('poisson_home_weight', '1.3'),
            'poisson_away_weight' => Setting::get('poisson_away_weight', '1.3'),
        ];

        return view('admin.settings', compact('settings'));
    }

    public function updateSettings(Request $request)
    {
        $validated = $request->validate([
            'gemini_api_key' => 'nullable|string',
            'claude_api_key' => 'nullable|string',
            'ga4_measurement_id' => 'nullable|string',
            'search_console_verification_code' => 'nullable|string',
            'site_logo' => 'nullable|string',
            'site_favicon' => 'nullable|string',
            'site_og_image' => 'nullable|string',
            'telegram_bot_token' => 'nullable|string',
            'telegram_bot_username' => 'nullable|string',
            'telegram_channel_username' => 'nullable|string',
            'prediction_provider' => 'required|in:claude,poisson_xg',
            'min_confidence_threshold' => 'required|numeric|min:0.1|max:0.99',
            'poisson_home_weight' => 'required|numeric|min:0.5|max:3.0',
            'poisson_away_weight' => 'required|numeric|min:0.5|max:3.0',
        ]);

        foreach ($validated as $key => $value) {
            if ($value !== null) {
                Setting::set($key, $value);
            }
        }

        return redirect()->route('admin.settings')->with('success', 'AI parameters and API keys updated successfully!');
    }

    public function runPipeline(PredictionService $predictionService, PreviewGenerationService $previewService)
    {
        (new FixtureIngestionJob())->handle($predictionService, $previewService);
        (new RankingJob())->handle();
        (new Ai5SelectionJob())->handle();

        return redirect()->route('admin.dashboard')->with('success', 'Full AI Pipeline executed! Gemini Previews & Claude Predictions updated.');
    }
}
