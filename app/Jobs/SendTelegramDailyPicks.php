<?php

namespace App\Jobs;

use App\Models\Prediction;
use App\Models\User;
use App\Services\TelegramService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendTelegramDailyPicks implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(TelegramService $telegram): void
    {
        // Get today's top predictions
        $predictions = Prediction::with('match')
            ->where('is_top10', true)
            ->whereHas('match', fn($q) => $q->whereDate('kickoff_at', today()))
            ->orderBy('probability', 'desc')
            ->take(5)
            ->get();

        if ($predictions->isEmpty()) {
            Log::info('SendTelegramDailyPicks: No predictions for today, skipping.');
            return;
        }

        $message = $telegram->formatDailyPicks($predictions);

        // 1. Post to public channel
        $telegram->postToChannel($message);
        Log::info('Telegram daily picks posted to channel.');

        // 2. Send personal DMs to opted-in subscribers
        $users = User::where('telegram_notifications_enabled', true)
            ->whereNotNull('telegram_chat_id')
            ->where(function ($q) {
                $q->where('role', 'subscriber')
                  ->orWhere('role', 'admin');
            })
            ->cursor();

        $sent = 0;
        foreach ($users as $user) {
            $personalMessage = "🔔 *Your Daily AI Picks, {$user->name}!*\n\n" . $message;
            $telegram->sendMessage($user->telegram_chat_id, $personalMessage);
            $sent++;

            // Rate limit: max 30 msgs/sec for Telegram API
            if ($sent % 25 === 0) {
                sleep(1);
            }
        }

        Log::info("SendTelegramDailyPicks: Sent DMs to {$sent} subscribers.");
    }
}
