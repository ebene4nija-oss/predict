-- =====================================================================
-- Guaranteed Correct — Pipeline Runs & Live Transcripts
--
-- MySQL equivalent of migration:
--   2026_08_17_000001_create_pipeline_runs_tables.php
--
-- Target: MySQL 5.7+ / MariaDB 10.2+ (utf8mb4, InnoDB)
--
-- Run this in phpMyAdmin or MySQL CLI if you deploy without running
-- 'php artisan migrate' in the shell.
-- =====================================================================

CREATE TABLE IF NOT EXISTS `pipeline_runs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'queued',
  `step` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `days` smallint(5) unsigned DEFAULT NULL,
  `trigger` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'admin',
  `error` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `started_at` timestamp NULL DEFAULT NULL,
  `finished_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pipeline_runs_status_index` (`status`),
  KEY `pipeline_runs_user_id_foreign` (`user_id`),
  CONSTRAINT `pipeline_runs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pipeline_run_lines` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `pipeline_run_id` bigint(20) unsigned NOT NULL,
  `level` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'info',
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `elapsed_ms` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pipeline_run_lines_pipeline_run_id_id_index` (`pipeline_run_id`, `id`),
  CONSTRAINT `pipeline_run_lines_pipeline_run_id_foreign` FOREIGN KEY (`pipeline_run_id`) REFERENCES `pipeline_runs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
