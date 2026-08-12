<?php

namespace App\Http\Controllers;

use App\Models\GameMatch;
use App\Models\Prediction;
use App\Models\User;
use App\Models\Pageview;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminAnalyticsController extends Controller
{
    public function index()
    {
        // 1. Overall Traffic & Visitor Stats
        $totalPageviews = Pageview::count();
        $todayPageviews = Pageview::whereDate('created_at', today())->count();
        $uniqueVisitorsToday = Pageview::whereDate('created_at', today())->distinct('ip_address')->count('ip_address');

        // Top Most Popular Pages
        $topPages = Pageview::select('path', DB::raw('count(*) as views'))
            ->groupBy('path')
            ->orderByDesc('views')
            ->take(8)
            ->get();

        // 2. User & Subscription Conversion Funnel
        $totalUsers = User::count();
        $subscribersCount = User::where('role', 'subscriber')->count();
        $freeUsersCount = User::where('role', 'free')->count();
        $expertUsersCount = User::where('role', 'expert')->count();

        $conversionRate = $totalUsers > 0 ? round(($subscribersCount / $totalUsers) * 100, 1) : 0;
        $monthlyRevenueEstimate = $subscribersCount * 9.99;

        // 3. Telegram Community Analytics
        $telegramConnectedUsers = User::whereNotNull('telegram_chat_id')->count();
        $telegramActiveSubscribers = User::whereNotNull('telegram_chat_id')
            ->where('telegram_notifications_enabled', true)
            ->count();

        // 4. Predictions & Markets Breakdown
        $totalMatches = GameMatch::count();
        $totalPredictions = Prediction::count();

        $marketBreakdown = Prediction::select('market', DB::raw('count(*) as count'))
            ->groupBy('market')
            ->get()
            ->pluck('count', 'market');

        $highConvictionPicks = Prediction::where('probability', '>=', 0.75)->count();

        // 5. Recent Traffic Activity Log
        $recentPageviews = Pageview::with('user')
            ->orderByDesc('created_at')
            ->take(15)
            ->get();

        return view('admin.analytics.index', compact(
            'totalPageviews',
            'todayPageviews',
            'uniqueVisitorsToday',
            'topPages',
            'totalUsers',
            'subscribersCount',
            'freeUsersCount',
            'expertUsersCount',
            'conversionRate',
            'monthlyRevenueEstimate',
            'telegramConnectedUsers',
            'telegramActiveSubscribers',
            'totalMatches',
            'totalPredictions',
            'marketBreakdown',
            'highConvictionPicks',
            'recentPageviews'
        ));
    }
}
