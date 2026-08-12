<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramService
{
    protected string $token;
    protected string $baseUrl;
    protected ?string $channelId;

    public function __construct()
    {
        $this->token = Setting::get('telegram_bot_token', config('services.telegram.bot_token', ''));
        $this->baseUrl = "https://api.telegram.org/bot{$this->token}";
        $this->channelId = Setting::get('telegram_channel_id', config('services.telegram.channel_id'));
    }

    /**
     * Send a text message to a specific chat.
     */
    public function sendMessage(string $chatId, string $text, string $parseMode = 'Markdown'): bool
    {
        try {
            $response = Http::post("{$this->baseUrl}/sendMessage", [
                'chat_id' => $chatId,
                'text' => $text,
                'parse_mode' => $parseMode,
                'disable_web_page_preview' => true,
            ]);

            if ($response->failed()) {
                Log::warning('Telegram sendMessage failed', [
                    'chat_id' => $chatId,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return false;
            }

            return true;
        } catch (\Exception $e) {
            Log::error('Telegram sendMessage exception', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Post predictions to the public Telegram channel.
     */
    public function postToChannel(string $text): bool
    {
        if (empty($this->channelId)) {
            Log::warning('Telegram channel ID not configured.');
            return false;
        }

        return $this->sendMessage($this->channelId, $text);
    }

    /**
     * Format daily top picks into a Telegram-friendly message.
     */
    public function formatDailyPicks(iterable $predictions): string
    {
        $lines = [
            "⚽ *PROPHET AI — Daily Top Picks* ⚽",
            "📅 " . now()->format('l, M d Y'),
            "━━━━━━━━━━━━━━━━━━━━━",
            "",
        ];

        $rank = 1;
        foreach ($predictions as $prediction) {
            $match = $prediction->match;
            if (!$match) continue;

            $prob = round($prediction->probability * 100, 1);
            $emoji = $prob >= 75 ? '🔥' : ($prob >= 60 ? '✅' : '📊');

            $lines[] = "{$emoji} *#{$rank} {$match->home_team} vs {$match->away_team}*";
            $lines[] = "🏆 {$match->league}";
            $lines[] = "🎯 Pick: *{$prediction->pick}* ({$prob}%)";
            if ($prediction->rationale) {
                $rationale = mb_substr($prediction->rationale, 0, 80);
                $lines[] = "💡 _{$rationale}_";
            }
            $lines[] = "";
            $rank++;
        }

        $lines[] = "━━━━━━━━━━━━━━━━━━━━━";
        $lines[] = "🤖 Powered by Claude AI + Poisson xG Engine";
        $lines[] = "🔗 Full analysis at ProphetAI.com";
        $lines[] = "";
        $lines[] = "_Predictions, not guarantees. 18+ only._";

        return implode("\n", $lines);
    }

    /**
     * Format a single match alert.
     */
    public function formatMatchAlert($match, $predictions): string
    {
        $lines = [
            "🔔 *Match Alert — Kickoff Soon!*",
            "",
            "⚽ *{$match->home_team} vs {$match->away_team}*",
            "🏆 {$match->league}",
            "🕐 Kickoff: " . $match->kickoff_at->format('H:i (d M)'),
            "",
        ];

        foreach ($predictions as $pred) {
            $prob = round($pred->probability * 100, 1);
            $lines[] = "🎯 {$pred->market}: *{$pred->pick}* ({$prob}%)";
        }

        $lines[] = "";
        $lines[] = "📖 Full preview on ProphetAI.com";

        return implode("\n", $lines);
    }

    /**
     * Set webhook for Telegram bot.
     */
    public function setWebhook(string $url): bool
    {
        try {
            $response = Http::post("{$this->baseUrl}/setWebhook", [
                'url' => $url,
                'allowed_updates' => ['message'],
            ]);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('Telegram setWebhook exception', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Get bot info to verify token.
     */
    public function getMe(): ?array
    {
        try {
            $response = Http::get("{$this->baseUrl}/getMe");
            if ($response->successful()) {
                return $response->json('result');
            }
            return null;
        } catch (\Exception $e) {
            return null;
        }
    }
}
