-- Mark all current vll_backend migrations as applied on a shared SmSver1 + Laravel DB.
-- Run AFTER schema matches (ensure_app_bridge_tables.sql, create_incoming_table_if_missing.sql, alter_incoming_segment_status.sql).
-- Prevents `php artisan migrate` from failing on "table already exists".
--
-- DESTRUCTIVE: DELETE wipes every row in `migrations`. Use only on dev / empty history.
-- For production, use baseline_laravel_migrations_safe.sql instead.

DELETE FROM `migrations`;

INSERT INTO `migrations` (`migration`, `batch`) VALUES
('2014_10_12_000000_create_users_table', 1),
('2014_10_12_100000_create_password_resets_table', 1),
('2019_08_19_000000_create_failed_jobs_table', 1),
('2019_12_14_000001_create_personal_access_tokens_table', 1),
('2022_10_08_102547_create_resellers_table', 1),
('2022_11_28_194738_create_jobs_table', 1),
('2022_12_08_115520_create_autoreplies_table', 1),
('2022_12_12_132747_create_sender_pointers_table', 1),
('2022_12_12_133305_create_incoming_table', 1),
('2026_05_08_090000_create_social_check_results_table', 1),
('2026_05_10_000001_add_segment_to_autoreplies_table', 1),
('2026_05_10_120000_add_incoming_segment_and_auto_reply_status', 1),
('2026_05_13_000001_add_deleted_at_to_autoreplies_table', 2),
('2026_05_13_120000_create_audience_polls_table', 2);
