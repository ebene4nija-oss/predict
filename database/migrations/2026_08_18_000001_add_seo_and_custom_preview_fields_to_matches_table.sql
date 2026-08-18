-- MySQL Schema Migration for: 2026_08_18_000001_add_seo_and_custom_preview_fields_to_matches_table

-- UP: Add SEO metadata, custom editorial headlines, and curation lock to matches table
ALTER TABLE `matches`
  ADD COLUMN `preview_headline` VARCHAR(255) NULL AFTER `preview_text`,
  ADD COLUMN `seo_title` VARCHAR(255) NULL AFTER `preview_headline`,
  ADD COLUMN `seo_description` TEXT NULL AFTER `seo_title`,
  ADD COLUMN `seo_keywords` TEXT NULL AFTER `seo_description`,
  ADD COLUMN `preview_status` VARCHAR(255) NOT NULL DEFAULT 'published' AFTER `preview_source`,
  ADD COLUMN `is_preview_custom` TINYINT(1) NOT NULL DEFAULT 0 AFTER `preview_status`;

-- DOWN: Rollback changes
-- ALTER TABLE `matches`
--   DROP COLUMN `preview_headline`,
--   DROP COLUMN `seo_title`,
--   DROP COLUMN `seo_description`,
--   DROP COLUMN `seo_keywords`,
--   DROP COLUMN `preview_status`,
--   DROP COLUMN `is_preview_custom`;
