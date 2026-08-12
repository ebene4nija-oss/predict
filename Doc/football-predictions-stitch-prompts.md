# Stitch Prompts — AI Football Predictions Platform

## Before you start

One giant prompt for all 9 screens won't work well — Stitch caps generations around 6 screens and quality drops noticeably past 2-4 in a single pass. So this is split into 3 batches of 3 screens each, plus a shared design system to keep them consistent, plus a final unify step.

Use **Experimental mode** (Gemini 2.5 Pro) for the actual generation passes — better fidelity, worth the extra time for a real build. Standard mode is fine if you want to rough out variations fast first.

Platform: specify **responsive web app, mobile-first** in every prompt — this matches the stack (Blade/Tailwind) and the mobile-first PWA direction already noted in the doc.

Admin screens are deliberately left out — that's functional CRUD better handled by a Laravel admin package than custom-designed, same reasoning as skipping the full admin dashboard spec in the documentation.

---

## Shared design system

Reuse these exact values in every batch prompt below — copy this block in each time so nothing drifts:

- Background: `#0B0F17` (near-black navy)
- Card/surface: `#151A24`
- Primary text: `#F1F5F9`
- AI accent: `#38BDF8` (electric blue)
- Expert accent: `#F5A623` (amber)
- Confidence gradient: `#22C55E` (high) → `#EF4444` (low)
- Mood: precise, confident, spacious — data-dense but not cluttered like a typical betting slip

**Named components** (use these terms explicitly so Stitch treats them consistently):
- **AI badge** — small pill, `#38BDF8`, circuit/AI icon
- **Expert badge** — small pill, `#F5A623`, analyst photo + name
- **Confidence bar** — horizontal bar, green-to-red gradient fill
- **Locked pick card** — blurred content, "Subscribe to unlock" CTA overlay
- **Segmented control** — for switching between markets (Win / GG / Over 2.5)

---

## Batch 1 — Core Product

```
Design a responsive, mobile-first web app for an AI football predictions platform. Dark theme: background #0B0F17, card surface #151A24, primary text #F1F5F9. Mood: precise, confident, spacious — not cluttered like a typical betting slip.

Generate 3 screens:

1. Match Preview screen — team badges and kickoff time at the top, an AI-written preview paragraph below (form, head-to-head, injuries), then a distinct "AI Prediction" card visually separated from the preview text: probability bars per market (Win/Draw/Loss, GG, Over 2.5) using a green-to-red gradient fill (#22C55E to #EF4444), each bar paired with a small AI badge (#38BDF8 pill, circuit icon). Below that, a one-line trust note in muted text: "AI has hit 61% on this market this season."

2. Top Picks screen — a segmented control at the top to switch between Top 10 Win / Top 10 GG / Top 10 Over 2.5. Below it, a horizontally scrolling row of featured cards labeled "AI's Top 5" — larger, elevated cards with shadow, the clear visual hero of the page. Below that, a vertical list of the remaining Top 10 picks as compact rows (fixture, confidence %, one-line AI rationale). The last 1-2 rows are visible for free users; the rest show a blurred locked-pick-card state with a "Subscribe to unlock" button overlay.

3. Expert Picks screen — same list layout as Top Picks, but each row carries an Expert badge (#F5A623 pill, analyst photo + name) instead of the AI badge, so it reads as a clearly separate source. Include a small tab or toggle at the top to switch between "AI Picks" and "Expert Picks."
```

---

## Batch 2 — Trust & Conversion

```
Design a responsive, mobile-first web app for an AI football predictions platform. Same dark theme: background #0B0F17, AI accent #38BDF8, Expert accent #F5A623, primary text #F1F5F9. Mood: precise, confident, spacious.

Generate 3 screens:

1. Home/Landing screen — hero section stating the core value prop (AI + Expert football predictions, transparent track record), a horizontal scrolling chip row of today's featured fixtures, and a summary strip up front showing overall AI accuracy this season as a trust signal.

2. Track Record screen — a public, data-dense page: win rate by market shown as large stat cards at the top (Win %, GG %, Over 2.5 %), a toggle to switch the view between "AI" and "Expert" accuracy, and a scrollable history list below of past picks vs actual results, each row marked win/loss with a small colored indicator (#22C55E win / #EF4444 loss).

3. Subscribe/Pricing screen — a single clear monthly plan card, centered, listing what's unlocked (full Top 10s, AI's Top 5, Expert Picks, ad-free) with a bold subscribe CTA button. A small note near the CTA mentions payment options (card, mobile money) without cluttering the card itself.
```

---

## Batch 3 — Auth & Account

```
Design a responsive, mobile-first web app for an AI football predictions platform. Same dark theme: background #0B0F17, AI accent #38BDF8, Expert accent #F5A623, primary text #F1F5F9.

Generate 3 screens:

1. Login screen — minimal centered form, email/password fields, a clear primary button, a link to sign up.

2. Sign Up screen — same minimal form style as login, plus a brief one-line reminder of what they're signing up for (not a full marketing pitch, just one line).

3. Account/Subscription screen — current plan status, renewal date, a visible "Cancel anytime" link (not hidden), and payment method on file with an option to update it.
```

---

## After generating all 3 batches

Shift+Click to multi-select all 9 screens on the canvas, then run this to unify anything that drifted between batches:

```
Apply consistent dark theme across all selected screens: background #0B0F17, card surface #151A24, AI accent #38BDF8, Expert accent #F5A623, consistent card corner radius and spacing.
```
