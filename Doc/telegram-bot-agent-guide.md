# Telegram Bot — Agent Development Guide

**Project:** Guaranteed Correct (`guaranteedcorrectscoretips.com`) — Laravel football predictions platform
**Scope of this document:** everything an agent needs to take the existing partial Telegram integration and finish it as a production bot.
**Audience:** a coding agent working in this repo, one phase per session.

---

## 0. How to use this document

Do **not** treat this as "build the Telegram bot" in one pass. The integration already exists in skeleton form and is wired into scheduling, admin settings, and the account page — a from-scratch rewrite would break live surfaces.

Rules for the agent:

1. Read §2 (Existing Code Inventory) before touching anything. Half the work is already done and the other half depends on it.
2. Work through the phases in §6 **in order**. Each phase ends with a **Verify** block. Do not start phase N+1 until phase N verifies.
3. Never guess on anything in §12 (Open Items) — stop and ask.
4. The constraints in §4 override any shortcut that looks easier mid-task.
5. Every phase must leave `php artisan test` green. New behaviour needs a new test in `tests/Feature/`.
6. Commit per phase, with a message naming the phase.

---

## 1. What the bot is for

Three audiences, one bot:

| Audience | Delivery | Content |
|---|---|---|
| **Public / free** | Public channel (`telegram_channel_username`) + bot DMs to unlinked users | Teasers: top 3 ranked picks per list, match alerts, links back to the site |
| **Subscribers** | Bot DMs to linked, paying users | Full Top 10 lists, AI's Top 5, kickoff alerts, settlement recaps |
| **Admin** | Bot DMs + admin dashboard | Broadcast composer, delivery health, integration test |

The bot is a **distribution channel, not a second product**. It never computes a prediction, never calls an LLM, and never becomes the source of truth for anything. It reads what the pipeline already produced and formats it.

---

## 2. Existing Code Inventory — read this first

### 2.1 Files that already exist

| File | State |
|---|---|
| `app/Services/TelegramService.php` | Skeleton. `sendMessage`, `postToChannel`, `formatDailyPicks`, `formatMatchAlert`, `setWebhook`, `getMe`, `verifyWebhookSecret`. No retries, no keyboards, no rate-limit handling. |
| `app/Http/Controllers/TelegramController.php` | Account-linking flow + `handleWebhook`. Webhook handles **only** `/start <token>`; every other message is silently dropped. |
| `app/Support/TelegramHandles.php` | Handle/URL normalisation for bot, channel, support. Already handles `@handle` vs bare vs full-URL input. **Use this — never read `config('services.telegram.*')` directly.** |
| `app/Jobs/SendTelegramDailyPicks.php` | Posts formatted daily picks to channel, then DMs opted-in subscribers, paced at `usleep(40_000)`. Gates on `$user->isSubscriber()`, not the role flag. |
| `app/Support/IntegrationTester.php` | `telegram()` health check calls `getMe`; surfaces in the admin integrations panel. |
| `database/migrations/2026_08_12_081216_add_telegram_fields_to_users_table.php` | Adds `telegram_chat_id`, `telegram_notifications_enabled`, `telegram_link_token` to `users`. |
| `resources/views/components/telegram-banner.blade.php` | Admin-configurable join/support banner on home, account, leaderboard. |
| `tests/Feature/TelegramHandlesTest.php` | Handle normalisation coverage. Follow its style for new tests. |

### 2.2 Wiring already in place

- **Routes** — `routes/web.php:76-79` (auth'd account routes: connect, disconnect, toggle, test) and `routes/web.php:87` (`POST /webhooks/telegram`, named `webhooks.telegram`).
- **CSRF exemption** — `bootstrap/app.php:20-23` already excepts `webhooks/telegram`. Authentication is by secret header, not token.
- **Schedule** — `routes/console.php:41`: `Schedule::job(new SendTelegramDailyPicks)->dailyAt('09:00')->withoutOverlapping()`.
- **Config** — `config/services.php:94-104`: `bot_token`, `webhook_secret`, `bot_username`, `channel_id`, `channel_username`, `admin_support_url`.
- **Admin settings** — `resources/views/admin/settings.blade.php` exposes every Telegram field, including the webhook URL as copyable text (line ~41). Validation in `AdminController::updateSettings` (`app/Http/Controllers/AdminController.php:147,163-170`).
- **Secrets at rest** — `telegram_bot_token` and `telegram_webhook_secret` are in `Setting::SECRET_KEYS` (`app/Models/Setting.php:23-39`), so they are encrypted in the DB.

### 2.3 Configuration precedence — this matters

`Setting::credential($key, $configKey)` prefers the **admin-typed DB value** and falls back to `.env`/config only when the DB value is blank. It never throws (a missing settings table degrades to "unconfigured"). Every new Telegram config read must go through `Setting::credential()` or `TelegramHandles`, or admins will change a value in the dashboard and see nothing happen.

### 2.4 Known gaps — this is the actual work

Confirmed by reading the code, in rough order of severity:

1. **No idempotency.** Telegram retries an update until it gets a 200. `handleWebhook` has no `update_id` dedupe, so a slow response can link an account twice or double-send replies.
2. **The webhook does synchronous HTTP.** `handleWebhook` calls `sendMessage` inline. Telegram expects a fast ACK; on cPanel shared hosting an outbound API call inside the request is the exact thing that times out and triggers retries (see 1).
3. **`allowed_updates` is `['message']` only** (`TelegramService::setWebhook`). Inline keyboards are therefore impossible today — `callback_query` updates never arrive.
4. **Only `/start` is handled.** No `/help`, `/today`, `/top10`, `/status`, `/alerts`, `/unlink`, `/support`. Any other text gets silence, which reads as a broken bot.
5. **`parse_mode` is legacy `Markdown`** with no escaping. A team, league or rationale containing `_`, `*`, `[` or `` ` `` produces a 400 from Telegram and the message is dropped — silently, because `sendMessage` only logs a warning.
6. **No 429 / `retry_after` handling.** `usleep(40_000)` paces sends but nothing reacts when Telegram actually throttles; those messages are lost.
7. **No 403 handling.** A user who blocks the bot keeps being retried forever; `telegram_notifications_enabled` is never cleared.
8. **Link tokens never expire.** `Str::random(32)` is written once and only cleared on successful `/start`. A token in an old browser tab or a shared screenshot stays valid indefinitely.
9. **`telegram_chat_id` is not unique.** Two accounts can link the same chat, and the second link leaves the first user DM'd about someone else's subscription.
10. **No command registration.** `setMyCommands` is never called, so Telegram's UI shows no command menu.
11. **No delivery record.** Nothing persists what was sent to whom, so "did the 09:00 job actually reach subscribers?" is answerable only from `laravel.log`.
12. **No kickoff-alert job.** `formatMatchAlert` exists and is never called by anything.
13. **No admin broadcast** and no way to set the webhook other than by hand-crafting an HTTP call.

---

## 3. Domain facts the bot must respect

Pulled from the live code and locked product decisions. Do not re-derive these.

### 3.1 Markets

`app/Support/MarketRegistry.php` is the single source of truth. Seven public Top 10 lists, keys:

`win`, `over_2_5`, `gg`, `fh_over_0_5`, `ht_win`, `corners_over_8_5`, `cards_over_2_5`
(plus the legacy `win_draw_loss` entry).

Every market carries `listLabel` ("Top 10 BTTS"), `label`, `short` ("GG"), outcomes and an optional `line`. **Bot command surfaces must be generated from the registry, never from a hardcoded list** — adding a market is one registry entry and the bot must follow automatically.

Not every fixture has every market: Champions League fixtures have no corner/card pick because the free CSVs are domestic only. Handle absence, don't special-case leagues.

### 3.2 Paywall

- Free members see the **first 3 ranked picks** in every list; #4–10 are blurred on the site.
- The bot mirrors this: **unlinked or non-subscriber chats get 3 picks and a link**; subscribers get the full list.
- Subscriber status is `User::isSubscriber()` (`app/Models/User.php:71`) — admin passes, otherwise an **active subscription**. Never gate on `role` alone; the role flag outlives the payment. `SendTelegramDailyPicks` already does this correctly (`app/Jobs/SendTelegramDailyPicks.php:43-56`) — copy that pattern.

### 3.3 Compliance — non-optional on every pick-bearing message

- `Predictions, not guarantees. 18+ only.` must appear in **any** message containing a pick, AI or expert.
- AI picks and expert picks are **never merged** into one undifferentiated list; each is labelled.
- No gambling-inducement language ("guaranteed profit", "can't lose", "bet your …"). The brand name is a brand name; the copy stays predictive.

### 3.4 Timing

- Pipeline: stats 01:40 → fixtures 02:00 → ranking 02:30 → AI5 02:35 (`routes/console.php:22-28`).
- Daily picks post at 09:00. Anything the bot sends about "today" must run after 02:35.
- Previews are written once at ingestion and once inside `preview_refresh_days` (default 2) of kickoff; `preview_lead_days` default 7. Do not assume a fixture 6 days out has final preview copy.

---

## 4. Non-negotiable constraints

1. **The bot never calls an LLM and never computes a probability.** It reads `predictions` / `matches` and formats. Generation lives in `PreviewGenerationService` and the AI provider services only.
2. **All outbound Telegram HTTP happens inside a queued job**, never inside a web request. The webhook controller may only validate, persist, dispatch, and return 200.
3. **The webhook always returns 200 for a well-authenticated update**, even when handling fails — otherwise Telegram retries the same update forever. Failures go to the queue/log, not the HTTP status. The only non-200 is 403 for a bad/missing secret.
4. **Reject unauthenticated updates.** `verifyWebhookSecret` already fails closed when the secret is unconfigured. Keep that behaviour; do not add a "development bypass".
5. **Never log the bot token**, and never echo it into an admin page, an exception message, or a test fixture.
6. **Never read `config('services.telegram.*')` directly** outside `Setting::credential()` / `TelegramHandles`.
7. **No `getUpdates` long-polling in production.** cPanel shared hosting has no supervised daemon. Webhook only. (A local-dev polling command is acceptable if it is explicitly dev-guarded.)
8. **Markets, labels and lines come from `MarketRegistry`.** No hardcoded "Over 2.5" strings in bot code.
9. **Do not change the paywall split.** 3 free picks, everywhere, including in Telegram.
10. **Message sends must be idempotent per (chat, notification, day).** A queue retry must not double-DM a subscriber.

---

## 5. Target architecture

```
POST /webhooks/telegram
  └── TelegramController::handleWebhook
        ├── verifyWebhookSecret(header)            → 403 if bad
        ├── TelegramUpdate::firstOrCreate(update_id)  → 200 immediately if seen
        ├── dispatch(ProcessTelegramUpdate::class)
        └── 200 {"ok":true}

ProcessTelegramUpdate (queued)
  └── TelegramCommandRouter::route($update)
        ├── Commands\StartCommand         /start [token]
        ├── Commands\HelpCommand          /help
        ├── Commands\TodayCommand         /today
        ├── Commands\TopCommand           /top <market>
        ├── Commands\StatusCommand        /status
        ├── Commands\AlertsCommand        /alerts on|off
        ├── Commands\UnlinkCommand        /unlink
        ├── Commands\SupportCommand       /support
        ├── Commands\VipCommand           /vip
        └── Commands\FallbackCommand      anything else → help hint

Outbound
  TelegramClient  (low-level HTTP: retry, 429 retry_after, 403 handling)
    └── TelegramService (domain API: sendMessage/postToChannel/answerCallback)
          └── MessageFormatter (escaping, paywall gating, disclaimers)
                └── SendTelegramMessage (queued, per-message)
```

**Layer discipline:** formatters never send, senders never format, commands never do HTTP directly — they build a payload and dispatch `SendTelegramMessage`. This is what makes the whole thing testable with `Http::fake()`.

**Why a router class and not a `match` in the controller:** commands need to grow (§6 phase 5 adds notification prefs, phase 6 adds callbacks). One class per command keeps each independently testable and keeps `handleWebhook` at ~15 lines.

---

## 6. Build phases

### Phase 0 — Baseline verification (no code changes)

1. Confirm `TELEGRAM_BOT_TOKEN` and `TELEGRAM_WEBHOOK_SECRET` are set (admin settings or `.env`).
2. Run the integration health check for `telegram` (admin → integrations, or `IntegrationTester`) and confirm `getMe` returns the expected bot username.
3. Run the suite.

**Verify:**
```bash
php artisan test
php artisan tinker --execute="dump(app(App\Services\TelegramService::class)->getMe());"
```
Both must succeed before proceeding. If `getMe` returns `null`, stop — the token is wrong and every later phase will produce false failures.

---

### Phase 1 — Harden the webhook (fixes gaps 1, 2, 3)

**New migration** — `telegram_updates`:

| column | type | notes |
|---|---|---|
| `id` | id | |
| `update_id` | unsignedBigInteger, **unique** | Telegram's monotonic id; the dedupe key |
| `chat_id` | string, nullable, indexed | |
| `type` | string | `message`, `callback_query`, … |
| `payload` | json | raw update, for debugging |
| `processed_at` | timestamp, nullable | |
| `error` | text, nullable | last failure reason |
| timestamps | | |

**New job** — `app/Jobs/ProcessTelegramUpdate.php`. Takes the update array (or the `telegram_updates` row id), resolves the router, marks `processed_at`. `$tries = 3`, `backoff = [10, 60]`, and a `failed()` that writes `error`.

**Rewrite** `TelegramController::handleWebhook` to:
1. verify secret → 403 on failure (unchanged behaviour),
2. `firstOrCreate` on `update_id`; if the row already existed and is processed, return 200 without dispatching,
3. dispatch `ProcessTelegramUpdate`,
4. return `['ok' => true]`.

Move the current `/start` logic into `Commands\StartCommand` (phase 2 builds the rest of the router; for this phase a minimal router with `StartCommand` + fallback is enough).

**Update** `TelegramService::setWebhook` to request `allowed_updates: ['message', 'callback_query', 'my_chat_member']`.
- `callback_query` — needed by phase 6 keyboards.
- `my_chat_member` — how Telegram tells you a user blocked the bot; phase 4 uses it to clear `telegram_notifications_enabled`.

**Add** a `telegram_updates` prune to the schedule (weekly, keep ~30 days) next to `pageviews:prune` at `routes/console.php:49`.

**Verify:** `tests/Feature/TelegramWebhookTest.php`
- valid secret + `/start` → 200, one `telegram_updates` row, one queued job (`Queue::fake()`).
- missing/wrong secret → 403, no row, no job.
- **same `update_id` posted twice → 200 both times, exactly one job dispatched.**
- unknown update shape → 200, no exception.
- a queued handler throwing → the row records `error`, no HTTP 500.

---

### Phase 2 — Command router and the command set (fixes gap 4, 10)

**New:** `app/Services/Telegram/TelegramCommandRouter.php` and `app/Services/Telegram/Commands/*.php` implementing a small interface:

```php
interface TelegramCommand
{
    public function matches(string $text): bool;
    public function handle(TelegramChatContext $context): void; // builds + dispatches replies
}
```

`TelegramChatContext` carries: `chat_id`, `?User $user` (resolved from `telegram_chat_id`), `first_name`, raw `text`, parsed args, and `isSubscriber()`.

Commands to implement:

| Command | Behaviour | Auth |
|---|---|---|
| `/start` | With token → link account (existing logic, moved). Without → welcome + how to link + channel/support buttons. | public |
| `/help` | Command list, generated from the registered commands, not hardcoded. | public |
| `/today` | Today's headline picks. Free/unlinked: top 3 + upgrade CTA. Subscriber: full AI5 + Top 10 highlights. | public, gated content |
| `/top <market>` | One Top 10 list. Market resolved via `MarketRegistry` including aliases (`btts` → `gg`, `o2.5` → `over_2_5`). No arg → list available markets as buttons/inline text. Free: 3 picks + CTA. | public, gated content |
| `/status` | Linked account, subscription state + renewal date, alerts on/off. | linked only |
| `/alerts on\|off` | Toggles `telegram_notifications_enabled`. | linked only |
| `/unlink` | Clears `telegram_chat_id`, token, notification flag. Confirms. | linked only |
| `/support` | `TelegramHandles::supportUrl()`; if blank, fall back to the site contact page. | public |
| `/vip` | Channel URL + subscribe link with UTM. | public |
| fallback | Short "I didn't catch that" + `/help`. Never silence. | public |

Also handle the `@BotUsername` command suffix Telegram appends in groups (`/today@GuaranteedCorrectBot`).

**Register commands with Telegram** — add `setMyCommands` to `TelegramService`, plus `php artisan telegram:sync` (phase 7) to push it.

**Verify:** `tests/Feature/TelegramCommandTest.php`, one case per command, with `Http::fake()` asserting the outbound payload. Include: `/top btts` resolves via alias; `/top nonsense` replies with the market list; `/status` on an unlinked chat replies with the link instructions rather than erroring; a free user's `/top win` contains exactly 3 picks and the upgrade CTA; a subscriber's contains 10.

---

### Phase 3 — Formatting layer (fixes gap 5)

**New:** `app/Services/Telegram/MessageFormatter.php`. Move `formatDailyPicks` / `formatMatchAlert` here; leave thin delegating methods on `TelegramService` so nothing breaks (`SendTelegramDailyPicks` calls them).

**Switch to `parse_mode: 'HTML'`.** HTML needs only `& < >` escaped, versus MarkdownV2's 18 characters, and team names like `Bayer Leverkusen (W)` or a rationale containing `2_5` stop being landmines. Every interpolated value goes through `e()`/`htmlspecialchars`; only the tags the formatter itself emits are literal.

Requirements:
- `<b>`, `<i>`, `<code>`, `<a href>` only — Telegram supports a small subset.
- Message body capped at **4096 characters**. A 10-pick list with rationales can exceed it: the formatter returns an **array of chunks**, split at pick boundaries, never mid-entry.
- Truncate rationale to 80 chars (existing behaviour) on a word boundary.
- Confidence emoji thresholds stay as they are: `>=75% 🔥`, `>=60% ✅`, else `📊`.
- Every pick-bearing message ends with the disclaimer line from §3.3.
- Site links use the canonical domain and carry `?utm_source=telegram&utm_medium=bot&utm_campaign=<context>`.
- Paywall teaser for non-subscribers: 3 picks, then a locked line (`🔒 7 more picks for subscribers`) then the CTA. Do **not** send the hidden picks and rely on the client to hide them.

**Verify:** `tests/Feature/TelegramFormatterTest.php`
- a team named `FC Bayern_München*` renders without breaking HTML and without dropping characters,
- an 11-pick list with long rationales splits into chunks each `<= 4096`, with no pick split across chunks,
- the disclaimer appears in every pick-bearing output,
- a non-subscriber payload contains 3 picks and the lock line; the 4th pick's team name is absent from the payload entirely.

---

### Phase 4 — Reliable sending (fixes gaps 6, 7, 11)

**New:** `app/Services/Telegram/TelegramClient.php` — the only place that talks to `api.telegram.org`.

- `Http::timeout(10)->retry(...)` for connection errors and 5xx.
- **429:** read `parameters.retry_after` from the response body and release the job back with that delay (`$this->release($retryAfter)`). Do not busy-wait.
- **403 `Forbidden: bot was blocked by the user`:** clear `telegram_notifications_enabled` and `telegram_chat_id` for that chat, log at info, and **do not** retry.
- **400 `chat not found`:** same treatment as 403.
- Any other 4xx: log with the response body, fail without retry (retrying a malformed payload never helps).
- Never include the token in logs — the base URL contains it, so log the method name, not the URL.

**New:** `app/Jobs/SendTelegramMessage.php` — one queued job per outbound message. Fields: `chat_id`, `text` (or chunks), `parse_mode`, optional `reply_markup`, optional `notification_key`.

**New migration** — `telegram_deliveries`: `id`, `chat_id`, `user_id` (nullable), `notification_key` (e.g. `daily_picks:2026-08-17`), `status` (`queued|sent|failed|skipped`), `error` nullable, `sent_at`, timestamps. **Unique on (`chat_id`, `notification_key`)** — this is the idempotency guarantee from constraint 10: a job retry after a partial run re-attempts only what has no `sent` row.

**Refactor** `SendTelegramDailyPicks` to dispatch `SendTelegramMessage` per recipient instead of sending inline. Keep the pacing intent by spacing dispatches (`->delay()`) rather than `usleep` — a cPanel cron worker running `--max-time=55` cannot afford to sleep through its window.

> **Rate limits to design against:** ~30 messages/second overall; **~20 messages per minute to the same group/channel**; per-user DM limits are looser but bursty sends to many chats still trigger 429. The channel post is one message — the DM fan-out is what needs pacing.

**Verify:** `tests/Feature/TelegramDeliveryTest.php`
- a faked 429 with `retry_after: 5` releases the job with delay 5 and records no `sent` row,
- a faked 403 clears the user's `telegram_chat_id` / notification flag and does not retry,
- running `SendTelegramDailyPicks` twice for the same day produces exactly one `sent` delivery per subscriber,
- a 500 then a 200 results in one delivery marked `sent`.

---

### Phase 5 — Notifications (fixes gap 12)

**Per-type preferences.** One boolean is too coarse once there are four notification kinds. Add a `telegram_preferences` JSON column on `users` (or a `telegram_preferences` table if you prefer queryability) with keys: `daily_picks`, `kickoff_alerts`, `results`, `ai5`. Default all true when `telegram_notifications_enabled` is set. Keep `telegram_notifications_enabled` as the master switch so existing code and `/alerts off` keep working.

Jobs:

| Job | Schedule | Content | Audience |
|---|---|---|---|
| `SendTelegramDailyPicks` (exists) | 09:00 | Channel teaser + subscriber DMs | channel + subscribers |
| `SendTelegramKickoffAlerts` (new) | every 15 min | Fixtures kicking off in the next 60–75 min that have a published pick; uses `formatMatchAlert` | subscribers with `kickoff_alerts` |
| `SendTelegramResultRecap` (new) | daily, after settlement | Yesterday's settled picks, W/L, running record from `TrackRecordService` | channel + subscribers with `results` |
| `SendTelegramAi5Alert` (new) | after `Ai5SelectionJob` (02:35) — hold delivery to a civil hour | The day's AI's Top 5, labelled as AI | subscribers with `ai5` |

Rules:
- Each job builds a `notification_key` that includes the date (and fixture id for kickoff alerts) so §Phase 4's unique index prevents duplicates.
- Alerts must never fire for a fixture already kicked off — check `kickoff_at > now()`.
- Empty result set → log and return, no message. `SendTelegramDailyPicks` already does this (`app/Jobs/SendTelegramDailyPicks.php:29-32`); match that.
- The result recap is the credibility surface. It reports losses too. Do not filter to winners.

**Verify:** `tests/Feature/TelegramNotificationsTest.php` — per job: correct recipients, correct exclusions (unsubscribed, per-type opt-out, already-kicked-off, no picks), no duplicate on a second run within the window. Freeze time with `travelTo` for the kickoff windows.

---

### Phase 6 — Inline keyboards and callbacks

Requires phase 1's `allowed_updates` change to be live on the real webhook (re-run `setWebhook`, verify with `getWebhookInfo`).

- Add `reply_markup` support to `TelegramClient::sendMessage` and an `answerCallbackQuery` method — an unanswered callback leaves a spinner on the user's button.
- `Commands\CallbackCommand` routes `callback_data`. Keep `callback_data` **under 64 bytes** — use short opaque forms (`t:gg`, `m:1234`), never a serialised payload.
- Keyboards worth having: market picker for `/top`, "Full analysis on site" URL button, "Alerts on/off" toggle, "Subscribe" URL button for free users.
- URL buttons carry the same UTM parameters as inline links.

**Verify:** a `callback_query` update produces both an `answerCallbackQuery` and the expected follow-up message; oversized `callback_data` is caught by a test asserting `strlen <= 64` for every generated button.

---

### Phase 7 — Admin tooling (fixes gap 13)

1. **`php artisan telegram:sync`** — calls `setWebhook` with `url(route('webhooks.telegram'))` + the stored secret, then `setMyCommands`, then `getWebhookInfo`, and prints the result. This is the command referenced in the deploy runbook (§9). Follow `app/Console/Commands/CreateAdminCommand.php` for structure and `tests/Feature/CreateAdminCommandTest.php` for test style.
2. **Admin bot panel** (`resources/views/admin/`): `getMe` + `getWebhookInfo` status, last-24h delivery counts by status from `telegram_deliveries`, recent `telegram_updates` errors, and a "Re-sync webhook" button that runs the command above.
3. **Broadcast composer** — admin-only form → `BroadcastTelegramMessage` job. Requirements: audience selector (channel / all linked / subscribers only), a **preview render** using the real formatter, an explicit confirm step, a rate-limited fan-out through `SendTelegramMessage`, and an audit row per broadcast (`admin_id`, audience, body, counts). Admin-authored text is escaped by the formatter like any other input.
4. Extend `IntegrationTester::telegram()` to also report webhook health, not just `getMe`.

**Verify:** `tests/Feature/TelegramAdminTest.php` — non-admin gets 403 on every new route; the broadcast requires confirmation; a broadcast to "subscribers only" excludes an expired subscription; the artisan command reports failure (non-zero exit) when the token is unset.

---

### Phase 8 — Documentation and handover

- Add a **Telegram** section to `DEPLOY.md` covering §9 below.
- Add an ops runbook: "picks didn't send", "bot silent", "429 storm", "token rotated" — each with the command that diagnoses it.
- Update this file's §2.4 to strike the gaps that are now closed.

---

## 7. Data model summary after all phases

**Changes to `users`:** `telegram_preferences` (json, nullable). Backfill from `telegram_notifications_enabled`.
**`telegram_chat_id`:** add a unique index. Migration must resolve pre-existing duplicates first (keep the most recently updated user, null the rest) or it will fail on production data.
**Add `telegram_link_token_expires_at`** (timestamp, nullable) — `TelegramController::connect` sets `now()->addMinutes(15)`; `StartCommand` rejects expired tokens with a "generate a new link" reply. This closes gap 8.

**New tables:** `telegram_updates` (phase 1), `telegram_deliveries` (phase 4), optionally `telegram_broadcasts` (phase 7).

Local dev is SQLite, production is MySQL. Keep migrations portable: no `ALTER` on JSON internals, no MySQL-only defaults, and add indexes in the same migration that creates the column.

---

## 8. Testing conventions

- `tests/Feature/`, `RefreshDatabase`, factories for `User`/`GameMatch`/`Prediction`.
- `Http::fake()` for every Telegram call. **No test may reach `api.telegram.org`.** Consider asserting in a shared `setUp` that no unfaked request escapes.
- `Queue::fake()` for dispatch assertions; run jobs directly (`(new Job)->handle(...)`) for behaviour assertions.
- `Setting::set()` + cache flush for credential-dependent tests — `Setting::get` caches forever, so a test that writes a setting must clear the cache key or use the existing helper pattern in `tests/Feature/AdminCredentialsTest.php`.
- `MarketRegistry::flush()` after changing market-related settings mid-test (it memoises in a static).
- Time-sensitive tests use `travelTo`, never `sleep`.

Minimum new test files by the end: `TelegramWebhookTest`, `TelegramCommandTest`, `TelegramFormatterTest`, `TelegramDeliveryTest`, `TelegramNotificationsTest`, `TelegramAdminTest`.

---

## 9. Deployment & operations (cPanel shared hosting)

The host has no supervised daemon and often no SSH. This shapes everything.

**Queue** — a once-per-minute cron running `php artisan queue:work --stop-when-empty --max-time=55`. Consequences the bot must live with:
- worst-case delivery latency is ~1 minute; that is fine for picks, acceptable for a `/today` reply, and the reason `SendTelegramKickoffAlerts` uses a 60–75 minute window rather than 15.
- a job that sleeps burns the worker's whole window. Use `delay()` / `release()`, never `sleep()`.
- **without the queue cron the bot is completely silent** — every send is queued. Check this first when debugging "nothing sends".

**Scheduler** — a once-per-minute `php artisan schedule:run` cron drives `routes/console.php`.

**Webhook registration** — after every deploy to a new domain or after rotating the secret:
```bash
php artisan telegram:sync
```
Then confirm `getWebhookInfo` reports the right URL, `pending_update_count` near 0, and no `last_error_message`. HTTPS is mandatory; Telegram will not deliver to plain HTTP.

**Secrets** — set the token and webhook secret through the admin dashboard (encrypted at rest) rather than `.env` on a shared host. Rotate through BotFather → update the setting → re-run `telegram:sync`.

**Local development** — Telegram cannot reach `localhost`. Either tunnel (ngrok/Cloudflare) and point a **second, separate dev bot** at it, or drive the flow with crafted POSTs to `/webhooks/telegram` carrying the secret header. Never point the production bot at a dev tunnel; its webhook is global and you will silently take the live bot offline.

---

## 10. Failure modes reference

| Symptom | Likely cause | Check |
|---|---|---|
| Bot totally silent, no errors | Queue cron not running | `jobs` table growing; run `queue:work` by hand |
| `getMe` null, integrations panel red | Token wrong/revoked | Admin settings; BotFather |
| Every update 403s | Webhook secret mismatch | `verifyWebhookSecret`; re-run `telegram:sync` |
| Same reply arrives repeatedly | Non-200 responses → Telegram retries | `pending_update_count`, `telegram_updates.error` |
| Some messages never arrive, no error visible | 400 from bad Markdown escaping | phase 3; `laravel.log` warnings from `sendMessage` |
| Channel post fine, DMs missing | `telegram_channel_id` set but subscribers not linked / opted out / expired | `telegram_deliveries` by status |
| Sends stop mid-fan-out | 429 without `retry_after` handling | phase 4 |
| One user gets someone else's alerts | Duplicate `telegram_chat_id` | phase 7 unique index |

---

## 11. Definition of done

- [ ] Webhook is idempotent, fast, and dispatches all work to the queue.
- [ ] Every command in §6 phase 2 answers; nothing is silent.
- [ ] All formatting is escaped, chunked under 4096, disclaimer-bearing, registry-driven.
- [ ] 429/403/400 are handled distinctly; blocked users are pruned automatically.
- [ ] Daily picks, kickoff alerts, result recaps and AI5 all deliver, exactly once each.
- [ ] Free-tier chats see 3 picks; subscribers see 10; `isSubscriber()` is the gate.
- [ ] Admin can see bot health, re-sync the webhook, and broadcast with a preview and a confirm.
- [ ] Six new test files, suite green, no test touches the network.
- [ ] `DEPLOY.md` documents the webhook and queue requirements.

---

## 12. Open items — ask, do not guess

1. **Group/supergroup support.** Should the bot answer commands in groups at all, or DM + channel only? Group support changes privacy-mode settings in BotFather and the rate limits that apply.
2. **A second private VIP channel** for subscribers, versus DMs for paid content. DMs are assumed here; a private channel needs an invite-link lifecycle and membership revocation on subscription expiry.
3. **Telegram-native payments** (Stars / provider tokens) for subscribing inside the bot, or always redirect to the site checkout. Redirect is assumed. Note the project constraint against Cashier-shaped abstractions applies here too.
4. **Localisation.** Single-language (English) is assumed.
5. **Result recap send time**, and whether losses appear per-pick or only in the aggregate record.
6. **AI5 alert delivery hour** — the job can run at 02:40 but nobody wants a 02:40 DM.
7. **Broadcast authority** — admin only, or experts too for their own picks?
8. **Retention** for `telegram_updates` / `telegram_deliveries` payloads (30 days assumed; raw payloads contain user names).

---

## Appendix A — Telegram API facts worth not re-learning

- Message text limit **4096 characters** (UTF-16 code units, so emoji cost 2).
- Caption limit 1024.
- `callback_data` limit **64 bytes**.
- ~30 messages/second global; **~20/minute to one group or channel**.
- 429 responses carry `parameters.retry_after` in seconds — honour it.
- Webhook secret arrives as the `X-Telegram-Bot-Api-Secret-Token` header on every update.
- `allowed_updates` is a whitelist: anything not listed is **never delivered and never queued**.
- A user must message the bot first; bots cannot open a DM. This is why the deep link `https://t.me/<bot>?start=<token>` is the linking mechanism.
- `getWebhookInfo` is the fastest diagnostic: URL, pending count, last error, and its timestamp.
- HTML parse mode supports only `b i u s a code pre blockquote tg-spoiler` and a couple of others — unsupported tags produce a 400, not a fallback.

## Appendix B — Key file map

| Concern | File |
|---|---|
| Low-level HTTP | `app/Services/Telegram/TelegramClient.php` *(new)* |
| Domain API | `app/Services/TelegramService.php` |
| Formatting | `app/Services/Telegram/MessageFormatter.php` *(new)* |
| Routing commands | `app/Services/Telegram/TelegramCommandRouter.php` + `Commands/` *(new)* |
| Handles / URLs | `app/Support/TelegramHandles.php` |
| Webhook entry | `app/Http/Controllers/TelegramController.php` |
| Account linking UI | `resources/views/account.blade.php` |
| Outbound jobs | `app/Jobs/SendTelegram*.php` |
| Inbound job | `app/Jobs/ProcessTelegramUpdate.php` *(new)* |
| Schedule | `routes/console.php` |
| Routes | `routes/web.php:76-87` |
| CSRF exemption | `bootstrap/app.php:20-23` |
| Config fallbacks | `config/services.php:94-104` |
| Credentials | `app/Models/Setting.php` (`SECRET_KEYS`, `credential()`) |
| Admin settings UI | `resources/views/admin/settings.blade.php` |
| Health check | `app/Support/IntegrationTester.php` |
| Markets | `app/Support/MarketRegistry.php` |
