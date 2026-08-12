<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\TelegramService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TelegramController extends Controller
{
    /**
     * Show Telegram connect page & generate link token.
     */
    public function connect(Request $request)
    {
        $user = $request->user();

        // Generate a unique link token if not already present
        if (!$user->telegram_link_token) {
            $user->update(['telegram_link_token' => Str::random(32)]);
        }

        $botUsername = \App\Models\Setting::get('telegram_bot_username', config('services.telegram.bot_username', 'ProphetAIBot'));

        return view('account', [
            'user' => $user,
            'subscription' => $user->subscription,
            'telegramConnecting' => true,
            'botUsername' => $botUsername,
            'linkToken' => $user->telegram_link_token,
        ]);
    }

    /**
     * Disconnect Telegram from user account.
     */
    public function disconnect(Request $request)
    {
        $request->user()->update([
            'telegram_chat_id' => null,
            'telegram_notifications_enabled' => false,
            'telegram_link_token' => null,
        ]);

        return redirect()->route('account')->with('success', 'Telegram disconnected successfully.');
    }

    /**
     * Toggle Telegram notification preferences.
     */
    public function toggleNotifications(Request $request)
    {
        $user = $request->user();

        if (!$user->telegram_chat_id) {
            return redirect()->route('account')->with('warning', 'Please connect Telegram first.');
        }

        $user->update([
            'telegram_notifications_enabled' => !$user->telegram_notifications_enabled,
        ]);

        $status = $user->telegram_notifications_enabled ? 'enabled' : 'disabled';

        return redirect()->route('account')->with('success', "Telegram notifications {$status}.");
    }

    /**
     * Send a test message to verify connection.
     */
    public function testMessage(Request $request, TelegramService $telegram)
    {
        $user = $request->user();

        if (!$user->telegram_chat_id) {
            return redirect()->route('account')->with('warning', 'Please connect Telegram first.');
        }

        $sent = $telegram->sendMessage(
            $user->telegram_chat_id,
            "✅ *Connection Verified!*\n\nHey {$user->name}, your Prophet AI Telegram alerts are working perfectly!\n\n🤖 You'll receive daily AI picks and match alerts here."
        );

        if ($sent) {
            return redirect()->route('account')->with('success', 'Test message sent! Check your Telegram.');
        }

        return redirect()->route('account')->with('warning', 'Failed to send test message. Please reconnect.');
    }

    /**
     * Handle incoming Telegram webhook (bot receives messages).
     */
    public function handleWebhook(Request $request, TelegramService $telegram)
    {
        $data = $request->all();

        // Process /start command with link token
        $message = $data['message'] ?? null;
        if (!$message) {
            return response()->json(['ok' => true]);
        }

        $chatId = (string) ($message['chat']['id'] ?? '');
        $text = $message['text'] ?? '';
        $firstName = $message['from']['first_name'] ?? 'there';

        // Handle /start <token>
        if (str_starts_with($text, '/start')) {
            $parts = explode(' ', $text, 2);
            $token = $parts[1] ?? null;

            if ($token) {
                $user = User::where('telegram_link_token', $token)->first();

                if ($user) {
                    $user->update([
                        'telegram_chat_id' => $chatId,
                        'telegram_notifications_enabled' => true,
                        'telegram_link_token' => null,
                    ]);

                    $telegram->sendMessage($chatId, "🎉 *Connected Successfully!*\n\nHey {$user->name}, your Prophet AI account is now linked!\n\n✅ Daily AI picks\n✅ Match kickoff alerts\n✅ Expert pick notifications\n\nYou'll start receiving alerts automatically.");
                } else {
                    $telegram->sendMessage($chatId, "❌ Invalid or expired link token.\n\nPlease generate a new connection link from your Prophet AI dashboard.");
                }
            } else {
                $telegram->sendMessage($chatId, "👋 Hey {$firstName}!\n\nTo connect your Prophet AI account, visit your dashboard at ProphetAI.com and click 'Connect Telegram'. You'll receive a personalized link to send here.");
            }
        }

        return response()->json(['ok' => true]);
    }
}
