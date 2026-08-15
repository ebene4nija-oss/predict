<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\MatchController;
use App\Http\Controllers\PredictionController;
use App\Http\Controllers\ExpertController;
use App\Http\Controllers\TrackRecordController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\EmailVerificationController;
use App\Http\Controllers\LegalController;
use App\Http\Controllers\PasswordResetController;
use Illuminate\Support\Facades\Route;

// Public Pages
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/matches/{match}', [MatchController::class, 'show'])->name('matches.show');
Route::get('/how-ai-works', [HomeController::class, 'howAiWorks'])->name('how-ai-works');
Route::get('/track-record', [TrackRecordController::class, 'index'])->name('track-record');
Route::get('/subscribe', [SubscriptionController::class, 'pricing'])->name('subscription.pricing');
Route::get('/top-picks', [PredictionController::class, 'topPicks'])->name('top.picks');
Route::get('/expert-picks', [ExpertController::class, 'index'])->name('expert.picks');
Route::get('/expert-leaderboard', [ExpertController::class, 'leaderboard'])->name('expert.leaderboard');

// Legal. Both payment gateways require these to be publicly reachable before a
// live merchant account is approved.
Route::get('/terms', [LegalController::class, 'terms'])->name('legal.terms');
Route::get('/privacy', [LegalController::class, 'privacy'])->name('legal.privacy');
Route::get('/refunds', [LegalController::class, 'refunds'])->name('legal.refunds');

// Authentication Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:auth');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:auth');

    // Forgotten password
    Route::get('/forgot-password', [PasswordResetController::class, 'showLinkRequestForm'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLink'])
        ->middleware('throttle:auth')->name('password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'showResetForm'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'resetPassword'])
        ->middleware('throttle:auth')->name('password.update');
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// Email verification
Route::middleware('auth')->group(function () {
    Route::get('/email/verify', [EmailVerificationController::class, 'notice'])->name('verification.notice');
    Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
        ->middleware('signed')->name('verification.verify');
    Route::post('/email/verification-notification', [EmailVerificationController::class, 'resend'])
        ->middleware('throttle:auth')->name('verification.send');
});

// Subscription Payments & Callbacks
Route::middleware('auth')->group(function () {
    Route::post('/checkout', [SubscriptionController::class, 'checkout'])->name('subscription.checkout');
    Route::get('/subscription/callback', [SubscriptionController::class, 'callback'])->name('subscription.callback');
    Route::get('/account', [SubscriptionController::class, 'account'])->name('account');
    Route::post('/cancel-subscription', [SubscriptionController::class, 'cancel'])->name('subscription.cancel');
    
    // Expert Submission Portal
    Route::get('/expert/submit', [ExpertController::class, 'createSubmitForm'])->name('expert.submit');
    Route::post('/expert/submit', [ExpertController::class, 'storePick']);

    // Telegram Bot User Integration
    Route::get('/account/telegram/connect', [\App\Http\Controllers\TelegramController::class, 'connect'])->name('telegram.connect');
    Route::post('/account/telegram/disconnect', [\App\Http\Controllers\TelegramController::class, 'disconnect'])->name('telegram.disconnect');
    Route::post('/account/telegram/toggle-notifications', [\App\Http\Controllers\TelegramController::class, 'toggleNotifications'])->name('telegram.toggle');
    Route::post('/account/telegram/test', [\App\Http\Controllers\TelegramController::class, 'testMessage'])->name('telegram.test');
});

// SEO & Sitemap Routes
Route::get('/sitemap.xml', [\App\Http\Controllers\SitemapController::class, 'sitemapXml'])->name('sitemap.xml');
Route::get('/robots.txt', [\App\Http\Controllers\SitemapController::class, 'robotsTxt'])->name('robots.txt');

// Webhooks
Route::post('/webhooks/telegram', [\App\Http\Controllers\TelegramController::class, 'handleWebhook'])->name('webhooks.telegram');

// Admin Control Portal Routes
Route::middleware(['auth', 'admin'])->prefix('admin')->group(function () {
    // Dashboard & AI Settings
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('admin.dashboard');
    Route::get('/analytics', [\App\Http\Controllers\AdminAnalyticsController::class, 'index'])->name('admin.analytics.index');
    Route::get('/settings', [AdminController::class, 'settings'])->name('admin.settings');
    Route::post('/settings', [AdminController::class, 'updateSettings'])->name('admin.settings.update');
    Route::post('/pipeline/run', [AdminController::class, 'runPipeline'])->name('admin.pipeline.run');

    // Match & Fixture Manager
    Route::get('/matches', [\App\Http\Controllers\AdminMatchController::class, 'index'])->name('admin.matches.index');
    Route::get('/matches/create', [\App\Http\Controllers\AdminMatchController::class, 'create'])->name('admin.matches.create');
    Route::post('/matches', [\App\Http\Controllers\AdminMatchController::class, 'store'])->name('admin.matches.store');
    Route::get('/matches/{match}/edit', [\App\Http\Controllers\AdminMatchController::class, 'edit'])->name('admin.matches.edit');
    Route::put('/matches/{match}', [\App\Http\Controllers\AdminMatchController::class, 'update'])->name('admin.matches.update');
    Route::delete('/matches/{match}', [\App\Http\Controllers\AdminMatchController::class, 'destroy'])->name('admin.matches.destroy');
    Route::get('/matches/{match}/settle', [\App\Http\Controllers\AdminMatchController::class, 'showSettleForm'])->name('admin.matches.settle');
    Route::post('/matches/{match}/settle', [\App\Http\Controllers\AdminMatchController::class, 'settleResult'])->name('admin.matches.settle.store');

    // Prediction Override Manager
    Route::get('/predictions', [\App\Http\Controllers\AdminPredictionController::class, 'index'])->name('admin.predictions.index');
    Route::put('/predictions/{prediction}', [\App\Http\Controllers\AdminPredictionController::class, 'update'])->name('admin.predictions.update');

    // User & Subscription Manager
    Route::get('/users', [\App\Http\Controllers\AdminUserController::class, 'index'])->name('admin.users.index');
    Route::post('/users/{user}/role', [\App\Http\Controllers\AdminUserController::class, 'updateRole'])->name('admin.users.role');
    Route::post('/users/{user}/extend', [\App\Http\Controllers\AdminUserController::class, 'extendSubscription'])->name('admin.users.extend');
    Route::post('/users/{user}/cancel', [\App\Http\Controllers\AdminUserController::class, 'cancelSubscription'])->name('admin.users.cancel');

    // Expert Profile & Pick Manager
    Route::get('/experts', [\App\Http\Controllers\AdminExpertController::class, 'index'])->name('admin.experts.index');
    Route::post('/experts', [\App\Http\Controllers\AdminExpertController::class, 'storeExpert'])->name('admin.experts.store');
    Route::put('/experts/{expert}', [\App\Http\Controllers\AdminExpertController::class, 'updateExpert'])->name('admin.experts.update');
    Route::delete('/experts/{expert}', [\App\Http\Controllers\AdminExpertController::class, 'destroyExpert'])->name('admin.experts.destroy');
    Route::post('/experts/picks', [\App\Http\Controllers\AdminExpertController::class, 'storePick'])->name('admin.experts.picks.store');
    Route::delete('/experts/picks/{pick}', [\App\Http\Controllers\AdminExpertController::class, 'destroyPick'])->name('admin.experts.picks.destroy');

    // Ad Banner & Sponsor Manager
    Route::get('/ads', [\App\Http\Controllers\AdminAdController::class, 'index'])->name('admin.ads.index');
    Route::post('/ads', [\App\Http\Controllers\AdminAdController::class, 'update'])->name('admin.ads.update');
});

Route::post('/webhooks/payment', [SubscriptionController::class, 'handleWebhook'])->name('webhooks.payment');

