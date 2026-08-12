# Agentic Build Prompt — AI Football Predictions Platform

## How to use this

Same reasoning as the Stitch prompts: don't hand an agent "build the entire system" as one instruction. Agentic coding tools do measurably worse on a large, undifferentiated build than on a sequenced set of scoped, verifiable phases — context gets lost, shortcuts creep in, and nothing gets checked until it's all tangled together.

Save this file into the repo (e.g. as `AGENT_BUILD_SPEC.md`) alongside the project documentation (as `ARCHITECTURE.md`) so the spec persists across sessions instead of living only in a chat that scrolls away. Work through the phases below **in order**, verify each one before starting the next, and stop to ask on anything listed under "Open Items" rather than guessing.

---

## Tech Stack (fixed)

- Laravel (PHP)
- MySQL — assumed default; confirm before migrating if Postgres is actually wanted
- Blade + Tailwind, Livewire for the interactive prediction panels
- Laravel Queues + Task Scheduler for all background jobs
- Integrations: Football-Data.org or a paid odds API (open — see below), Gemini API, Flutterwave subscription API, PayPal

---

## Database Schema — build these migrations first, exactly as specified

**matches**
`id, home_team, away_team, league, kickoff_at, home_form, away_form, h2h_summary, injury_notes, preview_text, timestamps`

**predictions** (AI-generated only)
`id, match_id (FK), market (enum: win_draw_loss, gg, over_2_5), probability (decimal), is_top10 (bool), is_ai5 (bool), timestamps`

**experts**
`id, name, bio, photo_path, timestamps`

**expert_picks**
`id, expert_id (FK), match_id (FK), market, pick, rationale (nullable text), timestamps`

**results**
`id, match_id (FK), actual_outcome, settled_at`

**users**
`id, name, email, password, role (enum: admin, expert, subscriber, free), timestamps`

**subscriptions**
`id, user_id (FK), gateway (enum: flutterwave, paypal), gateway_subscription_id, status (enum: active, past_due, cancelled), plan, renews_at, timestamps`

---

## Roles & Permissions

- **admin** — full access: matches, experts, subscriptions, revenue
- **expert** — submits their own picks only, no admin access
- **subscriber** — full access to Top 10s, AI's Top 5, Expert Picks
- **free** — match previews + 1-2 sample picks only

---

## Non-Negotiable Constraints

These override any shortcut that seems easier in the moment:

- **Never use Laravel Cashier.** Flutterwave and PayPal both need their native subscription APIs, not Stripe/Paddle-shaped abstractions.
- **The prediction service never calls an LLM.** Probabilities come only from the deterministic Poisson model. The LLM's only job is writing `preview_text` around numbers that already exist.
- **Ads never render for an active subscriber**, on any page, under any condition.
- **No pop-up or pop-under ad units**, regardless of what an ad network's default snippet suggests using.
- **AI picks and Expert picks are never merged into one undifferentiated list.** Every pick is traceable to its source with a distinct badge.
- **"Predictions, not guarantees" + 18+ disclaimer appears wherever any pick is shown** — AI or Expert.
- **Failed payment never cuts access immediately.** Retry, then a grace period, then revoke.

---

## Build Phases

### Phase 1 — Foundation & Prediction Engine
1. Scaffold the Laravel project, install Tailwind (+ Livewire if using it for the prediction panels)
2. Write migrations for the full schema above
3. `FixtureIngestionJob` — scheduled nightly, pulls fixtures + team stats into `matches`
4. `PredictionService` — deterministic, computes Win/Draw/Loss, GG, Over 2.5 probabilities via an expected-goals (Poisson) model from form/H2H/home-away/injury inputs. Write a feature test validating it against 2-3 known fixtures with hand-checkable expected outputs.
5. `PreviewGenerationService` — calls Gemini, passes in the already-computed probabilities, writes `preview_text`. This is the only place in the whole system that calls an LLM to generate content.
6. `RankingJob` — ranks predictions per market into Top 10 lists, cached rather than computed per request
7. `Ai5SelectionJob` — pulls the highest-confidence cross-market picks (computed probability + AI narrative agreement) into `is_ai5`, capped at 5
8. **Verify before moving on:** run the full pipeline against one real day of fixtures end to end

### Phase 2 — Public Match Preview Pages
1. Route + controller + Blade view for the Match Preview page: team badges, AI-written preview, a visually separated AI Prediction panel with confidence bars + AI badge, trust note — matches the Stitch design
2. No auth required — public and free
3. **Verify:** preview pages render correctly for a full day of published matches

### Phase 3 — Paywall, Top Picks, AI's Top 5, Ads
1. Auth scaffolding (Breeze/Fortify is fine) + `role` middleware
2. `Subscription` model + Flutterwave subscription API integration, including webhook handling for status changes
3. PayPal integration for non-Nigerian customers, routed by detected country/currency at checkout
4. Failed-payment retry + grace period logic
5. Top Picks page: segmented control per market, AI's Top 5 as elevated cards, remaining Top 10 as compact rows, blur+lock on all but 1-2 sample rows for free accounts
6. Ad placements (header, sidebar, in-content, footer) — free-tier only
7. **Verify:** a free account sees the gated state; a paid account sees full content and zero ads; cancelling revokes access at the right time, not before and not past the grace period

### Phase 4 — Trust Pages
1. Track Record page: accuracy computed from `results` vs `predictions`, broken out by market
2. "How the AI Works" explainer page
3. **Verify:** accuracy numbers update automatically when a `results` row is added — no manual recompute step

### Phase 5 — Expert Picks
1. `expert` role + a submission form (a simple authenticated interface is enough, no need for a custom dashboard)
2. `expert_picks` wired to `results` the same way `predictions` is, so Track Record can show AI vs Expert accuracy side by side
3. Expert Picks page: same list layout as Top Picks, Expert badge in place of the AI badge, name/photo on each row
4. **Verify:** AI and Expert picks never appear in the same unlabeled list

---

## Open Items — ask, don't guess

- Fixture/stats data provider: Football-Data.org vs a paid odds API
- Exact confidence threshold for AI's Top 5 beyond "highest + agreement"
- DB engine: MySQL assumed — confirm if Postgres is actually wanted
- Pricing (currency, monthly amount) — needed before Subscribe can go live
- Whether Expert Picks are bundled into the base subscription or sold separately
- Whether experts get a self-serve portal or an admin enters picks for them
- Ad network choice, and whether gambling/betting-adjacent ads are allowed
