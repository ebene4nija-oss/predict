-- =====================================================================
-- Guaranteed Correct — newsroom, RSS newswire and team crest library
-- Generated from the Laravel migrations using the MySQL schema grammar,
-- so this is byte-for-byte what `php artisan migrate` would execute.
--
-- Target: MySQL 5.7+ / MariaDB 10.2+ (utf8mb4, InnoDB)
-- Safe to run once on a database that already has `users` and `matches`.
--
-- Run order matters: news_sources must exist before posts gains its
-- foreign key to it.
-- =====================================================================

-- ---------------------------------------------------------------------
-- 1. posts — news articles and announcements
-- ---------------------------------------------------------------------
CREATE TABLE `posts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `category` varchar(255) NOT NULL DEFAULT 'news',
  `excerpt` text NULL,
  `body` longtext NOT NULL,
  `source` varchar(255) NOT NULL DEFAULT 'human',
  `ai_model` varchar(255) NULL,
  `status` varchar(255) NOT NULL DEFAULT 'draft',
  `published_at` timestamp NULL,
  `author_id` bigint unsigned NULL,
  `meta_description` varchar(320) NULL,
  `og_image` varchar(500) NULL,
  `is_featured` tinyint(1) NOT NULL DEFAULT '0',
  `views` int unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NULL,
  `updated_at` timestamp NULL
) DEFAULT CHARACTER SET utf8mb4 COLLATE 'utf8mb4_unicode_ci' ENGINE = InnoDB;

ALTER TABLE `posts` ADD CONSTRAINT `posts_author_id_foreign`
  FOREIGN KEY (`author_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;
ALTER TABLE `posts` ADD INDEX `posts_status_published_at_index`(`status`, `published_at`);
ALTER TABLE `posts` ADD INDEX `posts_category_index`(`category`);
ALTER TABLE `posts` ADD UNIQUE `posts_slug_unique`(`slug`);

-- ---------------------------------------------------------------------
-- 2. teams — one record per club, reused by every fixture
-- ---------------------------------------------------------------------
CREATE TABLE `teams` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `external_id` varchar(255) NULL,
  `provider` varchar(255) NULL,
  `name` varchar(255) NOT NULL,
  `short_name` varchar(255) NULL,
  `tla` varchar(8) NULL,
  `crest_url` varchar(500) NULL,
  `custom_crest_url` varchar(500) NULL,
  `created_at` timestamp NULL,
  `updated_at` timestamp NULL
) DEFAULT CHARACTER SET utf8mb4 COLLATE 'utf8mb4_unicode_ci' ENGINE = InnoDB;

ALTER TABLE `teams` ADD UNIQUE `teams_provider_external_id_unique`(`provider`, `external_id`);
ALTER TABLE `teams` ADD INDEX `teams_name_index`(`name`);

-- ---------------------------------------------------------------------
-- 3. matches — link fixtures to club records (additive; the existing
--    home_team / away_team text columns are untouched)
-- ---------------------------------------------------------------------
ALTER TABLE `matches` ADD `home_team_id` bigint unsigned NULL AFTER `away_team`;
ALTER TABLE `matches` ADD CONSTRAINT `matches_home_team_id_foreign`
  FOREIGN KEY (`home_team_id`) REFERENCES `teams` (`id`) ON DELETE SET NULL;

ALTER TABLE `matches` ADD `away_team_id` bigint unsigned NULL AFTER `home_team_id`;
ALTER TABLE `matches` ADD CONSTRAINT `matches_away_team_id_foreign`
  FOREIGN KEY (`away_team_id`) REFERENCES `teams` (`id`) ON DELETE SET NULL;

-- ---------------------------------------------------------------------
-- 4. news_sources — RSS/Atom feeds the newsroom watches
-- ---------------------------------------------------------------------
CREATE TABLE `news_sources` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `name` varchar(255) NOT NULL,
  `feed_url` varchar(500) NOT NULL,
  `category` varchar(255) NOT NULL DEFAULT 'news',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `max_per_run` smallint unsigned NOT NULL DEFAULT '2',
  `max_age_hours` smallint unsigned NOT NULL DEFAULT '48',
  `last_fetched_at` timestamp NULL,
  `last_error` varchar(500) NULL,
  `items_rewritten` int unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NULL,
  `updated_at` timestamp NULL
) DEFAULT CHARACTER SET utf8mb4 COLLATE 'utf8mb4_unicode_ci' ENGINE = InnoDB;

-- ---------------------------------------------------------------------
-- 5. posts — where a story came from (origin_hash is the dedupe key that
--    stops the same feed item being written about twice)
-- ---------------------------------------------------------------------
ALTER TABLE `posts` ADD `news_source_id` bigint unsigned NULL AFTER `author_id`;
ALTER TABLE `posts` ADD CONSTRAINT `posts_news_source_id_foreign`
  FOREIGN KEY (`news_source_id`) REFERENCES `news_sources` (`id`) ON DELETE SET NULL;

ALTER TABLE `posts` ADD `origin_guid` varchar(500) NULL AFTER `news_source_id`;
ALTER TABLE `posts` ADD `origin_url` varchar(500) NULL AFTER `origin_guid`;
ALTER TABLE `posts` ADD `origin_name` varchar(255) NULL AFTER `origin_url`;
ALTER TABLE `posts` ADD `origin_hash` varchar(64) NULL AFTER `origin_name`;
ALTER TABLE `posts` ADD UNIQUE `posts_origin_hash_unique`(`origin_hash`);

-- ---------------------------------------------------------------------
-- 6. Mark the migrations as applied.
--
-- Without this, a later `php artisan migrate` would try to create these
-- tables again and fail. Uses one batch number so a rollback undoes the
-- set together.
-- ---------------------------------------------------------------------
SET @batch := (SELECT IFNULL(MAX(`batch`), 0) + 1 FROM `migrations`);

INSERT INTO `migrations` (`migration`, `batch`) VALUES
  ('2026_08_15_000001_create_posts_table', @batch),
  ('2026_08_15_000002_create_teams_table', @batch),
  ('2026_08_15_000003_add_team_ids_to_matches_table', @batch),
  ('2026_08_15_000004_create_news_sources_table', @batch),
  ('2026_08_15_000005_add_origin_fields_to_posts_table', @batch);
