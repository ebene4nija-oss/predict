# AI Football Predictions Platform — Project Documentation

## 1. Overview

**What it is:** A subscription-based website where an AI generates football match predictions across multiple betting markets, publishes ranked "Top 10" pick lists per market, and curates its own highest-conviction 5 picks from across those lists. Human expert picks sit alongside the AI's, clearly labeled as a separate source.

**Value proposition:** Predictions are transparent (stats-driven, not black-box), ranked by confidence, and backed by a public track record. The AI-powered nature of the picks is the product's core identity — visible on every page — with human expert picks as a distinct, clearly-labeled second source rather than something blended in.

**Monetization:** Monthly recurring subscription as the primary stream, with free-tier display advertising as a secondary one. Free tier acts as a funnel (match previews + a sample pick or two); paid tier unlocks the full Top 10 lists, AI's Top 5, Expert Picks, and an ad-free experience.

---

## 2. Product Structure

### Match Preview Page (per fixture)
- Team badges, kickoff time, competition/league
- AI-written preview: recent form, head-to-head record, key injuries/absences
- **AI Prediction panel** — visually separated from the editorial preview text:
  - Probability bars per market: Match Win/Draw/Loss, GG (Both Teams to Score), Over 2.5 Goals
  - Consistent AI badge/icon marking it as model output, not commentary
- Trust note: "AI has hit X% on this market this season" (pulled from the track record data)

### Top Picks Page (flagship subscriber page)
- Sectioned/tabbed by market: Top 10 Win · Top 10 GG · Top 10 Over 2.5 (extensible to more markets)
- Each row: fixture, kickoff, confidence %, one-line AI rationale
- **AI's Top 5** — elevated above the rest as featured cards, not list rows; this is the core paid hook and should look like it
- Free users see 1–2 unlocked rows; remainder blurred with a subscribe CTA

### Expert Picks (new)
- Human analysts submit their own picks per fixture/market, entirely separate from the AI pipeline
- Displayed alongside AI picks but visually distinguished — a different badge/icon than the AI one, plus the expert's name/bio attached to each pick
- Scored and tracked the same way as AI picks, so the trust page can show AI accuracy vs Expert accuracy side by side — this is a stronger hook than either alone

---

## 3. Tech Stack

| Layer | Choice | Notes |
|---|---|---|
| Backend | Laravel | Confirmed |
| Billing | Flutterwave (primary) + PayPal (secondary, other countries) | Stripe doesn't pay out to Nigerian bank accounts, so Flutterwave covers the Nigerian/African base via its own subscription API — not Laravel Cashier. PayPal covers other countries; a plain PayPal-Nigeria account can't receive/withdraw, but a Jan 2026 PayPal–Paga partnership now allows it if the account is linked through Paga |
| Fixture/stats data | Football-Data.org or a paid odds API | Free tier likely too thin for confidence scoring at scale — open decision |
| AI narrative generation | Gemini | Writes the human-readable preview around pre-computed probabilities |
| Frontend | Blade + Tailwind (or Livewire for the interactive prediction panels) | Not yet finalized — proposed default given the backend choice |
| Queue/scheduling | Laravel Queues + Task Scheduler | Nightly fixture ingestion, prediction computation, ranking jobs |

---

## 4. Prediction Engine

Pipeline, run on a schedule:

1. **Ingest fixtures** — nightly job pulls upcoming fixtures + team stats into a `matches` table
2. **Compute probabilities** — inputs: recent form (last 5–10 matches), home/away split performance, head-to-head record, goals scored/conceded averages, available injury/absence data. Method: an expected-goals (Poisson) model — the standard, well-established approach for exactly these markets — converts those inputs into Win/Draw/Loss, Over 2.5, and GG probabilities per match. This step does **not** use the LLM — numbers come from real data first
3. **Generate narrative** — Gemini call writes the readable preview text around the computed numbers, stored as `preview_text`
4. **Rank Top 10** — separate ranked list per market, by probability, cached rather than computed per page view
5. **Select AI's Top 5** — pulled from across all Top 10 lists: highest confidence where the computed probability and the AI's narrative reasoning agree, capped at 5
6. **Notify & update accuracy** — once results come in, update the `results` table and recompute the track record numbers

Confidence score starts as the computed probability itself, refined later with a stability factor once enough historical data exists. Validate the model against 1–2 seasons of historical results before it goes live — not just on paper.

Expert Picks run entirely outside this pipeline — see Sections 5 and 6.

---

## 5. Roles & Permissions

- **Admin** — full access: manages matches, experts, subscriptions, revenue visibility
- **Expert** — submits their own picks only, no admin access
- **Subscriber** — full access to Top 10s, AI's Top 5, and Expert Picks
- **Free user** — match previews + 1–2 sample picks only

---

## 6. Database Schema (high-level)

- **matches** — fixture info, team stats, kickoff time, `preview_text`
- **predictions** — `match_id`, `market`, `probability`, `is_top10`, `is_ai5` (AI-generated only)
- **experts** — `name`, `bio`, `photo`
- **expert_picks** — `expert_id`, `match_id`, `market`, `pick`, `rationale` (optional text)
- **results** — actual outcomes, used to compute historical accuracy for both `predictions` and `expert_picks`
- **users** — standard auth, `role` field per Section 5
- **subscriptions** — gateway used (Flutterwave/PayPal), subscription reference, status, plan, renewal date

---

## 7. Subscription & Paywall

- Free tier: match previews + 1–2 sample picks
- Paid tier: full Top 10 lists, AI's Top 5, Expert Picks, and an ad-free experience — billed monthly
- Billing handled via Flutterwave's subscription/plan API for the primary (Nigerian/African) base; PayPal added for customers in other countries, routed by detected country/currency at checkout
- Middleware gates prediction routes on active subscription status
- Failed payment: automatic retry, then a short grace period before access is cut — don't cut access on the first failed charge
- Cancel anytime, no lock-in
- Open question: whether Expert Picks ship bundled into the same subscription or as a separate add-on tier (see Section 14)

---

## 8. Ad Monetization

- **Free tier only** — paid subscribers see zero ads; "ad-free" is an explicit part of the subscription pitch, reinforcing the free→paid funnel from Section 1
- **Placements**: header/top banner, sidebar (desktop), in-content banner (between Top 10 rows), footer
- **Served via a standard ad network** (e.g. Google Ad Manager/AdSense-style tags) in those placements — no custom schema needed unless direct-sold ads are added later
- **Pop-ups and pop-unders specifically**: skipping these. Pop-unders are blocked by default in every major browser now, so documenting them as a working feature would be inaccurate, and the ad networks that still push the format lean heavily toward malvertising and scam ads — which cuts directly against everything else in this doc (the trust page, the track record, clean AI/Expert badging). If something more assertive than a banner is wanted, a single once-per-session interstitial is the closer legitimate equivalent — not repeated, not stacked
- Worth activating once there's meaningful organic traffic; bolting it on pre-launch adds clutter with no revenue to show for it yet

---

## 9. Security Baseline

- Rate limiting on auth and prediction API endpoints
- CSRF protection (Laravel handles this by default — confirm it's not disabled anywhere)
- Account lockout after repeated failed logins
- Deferred to later hardening: two-factor authentication, bot protection (see Future Considerations)

---

## 10. Trust & Credibility

- Public, auto-updating **track record page**: win rate by market, historical picks vs actual results — broken out by AI and by each Expert
- **"How the AI works" page**: plain explanation of the pipeline (stats in → model computes probabilities → AI writes it up) — this is what makes "AI-powered" a credibility signal rather than a tagline
- AI badge/icon used consistently on every AI surface; Expert picks carry their own distinct badge and byline, so the two sources never get confused

---

## 11. Compliance Notes

- Clear disclaimer on every prediction (AI and Expert alike): "Predictions, not guarantees" + 18+ notice
- Content sits close to gambling-adjacent territory — Nigerian ad/content regulation for this category hasn't been checked yet; flag for legal review before launch
- If the ad network serves betting/bookmaker ads, that compounds the same regulatory review — whether to allow gambling-related ads at all is its own decision (see Section 14)

---

## 12. Build Roadmap

1. **Phase 1** — Prediction engine + ranking logic (no UI yet)
2. **Phase 2** — Publish free match preview pages
3. **Phase 3** — Paywall + Top Picks page + AI's Top 5
4. **Phase 4** — Track record / "How the AI works" pages
5. **Phase 5** — Expert Picks: contributor role, submission workflow, display alongside AI picks, accuracy tracking

---

## 13. Future Considerations (not yet committed)

Ideas worth revisiting post-launch rather than building into the MVP:
- Annual subscription plan at a discount
- Referral program
- Additional markets (Over/Under 1.5, Double Chance, Asian Handicap)
- Additional sports beyond football
- Notification system (email at minimum; Telegram/WhatsApp/push later)
- Coupon/promo codes, free trial
- Two-factor authentication
- SEO details (schema.org markup, sitemap) — worth doing at launch, not before it

---

## 14. Open Decisions

- Fixture/stats data provider (Football-Data.org vs a paid odds API)
- Frontend approach (Blade/Livewire vs a separate SPA)
- Exact confidence threshold/rule for AI's Top 5 selection
- Pricing (currency, monthly fee amount)
- Legal review of Nigerian regulations for gambling-adjacent content
- Whether Expert Picks are bundled into the existing subscription or sold as a separate tier/add-on
- Whether experts self-submit via a portal, or an admin enters picks on their behalf
- Ad network choice, and whether to allow gambling/betting-related ads
