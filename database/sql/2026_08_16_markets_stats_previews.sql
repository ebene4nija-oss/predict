-- =====================================================================
-- Guaranteed Correct — seven Top 10 markets, match statistics, and
-- preview publication tracking.
--
-- The MySQL equivalent of these four Laravel migrations, in one script:
--
--   2026_08_16_000001_widen_market_columns_to_string
--   2026_08_16_000002_add_half_time_scores_to_results_table
--   2026_08_16_000003_add_count_stats_for_corner_and_card_markets
--   2026_08_16_000004_add_preview_publication_fields_to_matches_table
--
-- Target: MySQL 5.7+ / MariaDB 10.2+ (utf8mb4, InnoDB)
--
-- Requires: `matches`, `predictions`, `expert_picks`, `results` and
-- `teams`. The last of those is created by 2026_08_15_newsroom_teams.sql,
-- which must have been run first.
--
-- Run once, top to bottom. Every statement is additive except the two
-- MODIFYs in section 1, which widen a column and preserve their data.
-- Nothing here drops a column or deletes a row.
-- =====================================================================

-- ---------------------------------------------------------------------
-- 1. predictions / expert_picks — market becomes free text
--
-- `market` was enum('win_draw_loss','gg','over_2_5'). The catalogue is
-- now a registry that grows — win, first-half goals, half-time result,
-- corners and cards — and an enum makes every addition a schema change
-- on a live table. The vocabulary is validated in the application, where
-- it can grow without a migration.
--
-- Existing values are enum labels, which are already strings, so the
-- widening preserves every row.
-- ---------------------------------------------------------------------
ALTER TABLE `predictions` MODIFY `market` varchar(255) NOT NULL;
ALTER TABLE `expert_picks` MODIFY `market` varchar(255) NOT NULL;

-- ---------------------------------------------------------------------
-- 2. results — half-time score
--
-- Settles "Top 10 1H Over 0.5" and "Top 10 HT Win". Nullable, never
-- defaulted to 0: rows settled before this column existed have no
-- half-time score and never will, and a zero would grade every one of
-- them as a goalless first half. Absent means unsettleable, which is
-- the truth, and specifically not a loss.
-- ---------------------------------------------------------------------
ALTER TABLE `results` ADD `ht_home_score` int NULL AFTER `away_score`;
ALTER TABLE `results` ADD `ht_away_score` int NULL AFTER `ht_home_score`;

-- ---------------------------------------------------------------------
-- 3. results — corner and card totals
--
-- Settles "Top 10 Corners" (over 8.5) and "Top 10 Cards" (over 2.5
-- yellows). Populated from the football-data.co.uk season CSVs by
-- MatchStatsIngestionJob, on a later pass than the score itself.
-- ---------------------------------------------------------------------
ALTER TABLE `results` ADD `home_corners` smallint unsigned NULL AFTER `ht_away_score`;
ALTER TABLE `results` ADD `away_corners` smallint unsigned NULL AFTER `home_corners`;
ALTER TABLE `results` ADD `home_yellows` smallint unsigned NULL AFTER `away_corners`;
ALTER TABLE `results` ADD `away_yellows` smallint unsigned NULL AFTER `home_yellows`;

-- ---------------------------------------------------------------------
-- 4. teams — rolling corner and card rates
--
-- Per game over the sampled window. "For" is what the club wins or
-- receives itself; "against" is what its opponents do.
--
-- All nullable. A club with no rates is a club we cannot price a corner
-- market for — Champions League sides have no domestic-CSV row — and
-- absent must stay distinguishable from zero, or an unmapped fixture
-- would be modelled as a game with no corners in it.
-- ---------------------------------------------------------------------
ALTER TABLE `teams` ADD `corners_for` decimal(5,3) NULL AFTER `custom_crest_url`;
ALTER TABLE `teams` ADD `corners_against` decimal(5,3) NULL AFTER `corners_for`;
ALTER TABLE `teams` ADD `cards_for` decimal(5,3) NULL AFTER `corners_against`;
ALTER TABLE `teams` ADD `cards_against` decimal(5,3) NULL AFTER `cards_for`;

-- Sample size behind the rates. A club with three matches played is not
-- evidence, and the model falls back to the league baseline.
ALTER TABLE `teams` ADD `stats_matches` smallint unsigned NULL AFTER `cards_against`;
ALTER TABLE `teams` ADD `stats_updated_at` timestamp NULL AFTER `stats_matches`;

-- The club's name in the stats CSV, which does not match the fixture
-- provider's ("West Ham" vs "West Ham United FC"). Resolved once and
-- stored so the mapping is auditable and an admin can correct a bad
-- match by hand instead of it silently re-breaking.
ALTER TABLE `teams` ADD `stats_alias` varchar(255) NULL AFTER `stats_updated_at`;
ALTER TABLE `teams` ADD INDEX `teams_stats_alias_index`(`stats_alias`);

-- ---------------------------------------------------------------------
-- 5. matches — preview publication tracking
--
-- The ingestion job used to rewrite every unstarted fixture's preview on
-- every nightly run, so a fixture a week out was sent to the model seven
-- times and its published copy replaced seven times — seven times the
-- spend, on text written specifically to be indexed, which search
-- engines then saw change daily.
--
-- Recording when a preview was written, whether it has had its
-- near-kickoff refresh, and whether the model actually produced it lets
-- the job write each preview once, refresh it once on settled data, and
-- retry only the ones that fell back to boilerplate.
-- ---------------------------------------------------------------------
ALTER TABLE `matches` ADD `preview_generated_at` timestamp NULL AFTER `preview_text`;
ALTER TABLE `matches` ADD `preview_refreshed_at` timestamp NULL AFTER `preview_generated_at`;

-- 'gemini' or 'fallback'. The fallback is boilerplate written when the
-- model is unreachable; without recording it, an outage would be
-- indistinguishable from a real preview and would bake the placeholder
-- in permanently once the daily rewrites stopped.
ALTER TABLE `matches` ADD `preview_source` varchar(255) NULL AFTER `preview_refreshed_at`;

-- ---------------------------------------------------------------------
-- 6. Mark the migrations as applied.
--
-- Without this, a later `php artisan migrate` would try to run all four
-- again and fail on the first duplicate column. One batch number, so a
-- rollback undoes the set together.
-- ---------------------------------------------------------------------
SET @batch := (SELECT IFNULL(MAX(`batch`), 0) + 1 FROM `migrations`);

INSERT INTO `migrations` (`migration`, `batch`) VALUES
  ('2026_08_16_000001_widen_market_columns_to_string', @batch),
  ('2026_08_16_000002_add_half_time_scores_to_results_table', @batch),
  ('2026_08_16_000003_add_count_stats_for_corner_and_card_markets', @batch),
  ('2026_08_16_000004_add_preview_publication_fields_to_matches_table', @batch);
