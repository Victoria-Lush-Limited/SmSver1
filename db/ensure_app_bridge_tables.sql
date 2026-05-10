-- Tables required by vll_backend API + SmSver1 incoming/autoreply flows.
-- Safe to run on shared DB (e.g. anderson_vllsms). Uses IF NOT EXISTS / idempotent adds.

CREATE TABLE IF NOT EXISTS `password_resets` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  KEY `password_resets_email_index` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `sender_pointers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `sender_id` varchar(64) NOT NULL,
  `phone` varchar(32) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sender_pointers_phone_unique` (`phone`),
  KEY `idx_sender_pointers_sender` (`sender_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `autoreplies` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `sender_id` varchar(64) NOT NULL,
  `reply` text NOT NULL,
  `scheduled_time` time NOT NULL,
  `end_schedule` time DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_autoreplies_sender` (`sender_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `social_check_results` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` varchar(32) NOT NULL,
  `phone_number` varchar(32) NOT NULL,
  `platform` varchar(32) NOT NULL,
  `status` varchar(32) NOT NULL DEFAULT 'not_configured',
  `is_found` tinyint(1) DEFAULT NULL,
  `profile_name` varchar(255) DEFAULT NULL,
  `profile_url` text,
  `metadata` longtext,
  `checked_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `social_check_results_user_id_index` (`user_id`),
  KEY `social_check_results_phone_number_index` (`phone_number`),
  KEY `social_check_results_platform_index` (`platform`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET @db = DATABASE();

-- autoreplies.segment (radio KPI tag)
SET @s = (
  SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'autoreplies' AND COLUMN_NAME = 'segment') > 0,
    'SELECT ''autoreplies.segment exists'' AS msg',
    'ALTER TABLE `autoreplies` ADD COLUMN `segment` VARCHAR(64) NULL DEFAULT NULL AFTER `sender_id`'
  )
);
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
