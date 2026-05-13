-- Optional: create `audience_polls` when not using `php artisan migrate` (same shape as Laravel migration).
CREATE TABLE IF NOT EXISTS `audience_polls` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` varchar(64) NOT NULL,
  `title` varchar(512) NOT NULL,
  `opt1` varchar(255) NOT NULL,
  `opt2` varchar(255) NOT NULL,
  `opt3` varchar(255) NOT NULL DEFAULT '',
  `opt4` varchar(255) NOT NULL DEFAULT '',
  `started_at_ms` bigint(20) unsigned NOT NULL,
  `ended_at_ms` bigint(20) unsigned DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `tallies_json` longtext DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `audience_polls_user_id_index` (`user_id`),
  KEY `audience_polls_active_index` (`active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
