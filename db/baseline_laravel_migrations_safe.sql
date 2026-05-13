-- Idempotent: insert each migration name only if missing. Keeps existing `migrations` rows.
-- Use on production when you cannot DELETE the migrations table.

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2014_10_12_000000_create_users_table', 1 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2014_10_12_000000_create_users_table');

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2014_10_12_100000_create_password_resets_table', 1 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2014_10_12_100000_create_password_resets_table');

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2019_08_19_000000_create_failed_jobs_table', 1 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2019_08_19_000000_create_failed_jobs_table');

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2019_12_14_000001_create_personal_access_tokens_table', 1 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2019_12_14_000001_create_personal_access_tokens_table');

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2022_10_08_102547_create_resellers_table', 1 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2022_10_08_102547_create_resellers_table');

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2022_11_28_194738_create_jobs_table', 1 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2022_11_28_194738_create_jobs_table');

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2022_12_08_115520_create_autoreplies_table', 1 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2022_12_08_115520_create_autoreplies_table');

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2022_12_12_132747_create_sender_pointers_table', 1 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2022_12_12_132747_create_sender_pointers_table');

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2022_12_12_133305_create_incoming_table', 1 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2022_12_12_133305_create_incoming_table');

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_05_08_090000_create_social_check_results_table', 1 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_05_08_090000_create_social_check_results_table');

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_05_10_000001_add_segment_to_autoreplies_table', 1 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_05_10_000001_add_segment_to_autoreplies_table');

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_05_10_120000_add_incoming_segment_and_auto_reply_status', 1 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_05_10_120000_add_incoming_segment_and_auto_reply_status');

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_05_13_000001_add_deleted_at_to_autoreplies_table', 2 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_05_13_000001_add_deleted_at_to_autoreplies_table');

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_05_13_120000_create_audience_polls_table', 2 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_05_13_120000_create_audience_polls_table');
