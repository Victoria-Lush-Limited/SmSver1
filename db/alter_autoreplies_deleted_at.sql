-- Soft-delete column for autoreplies (aligns with Laravel SoftDeletes on vll_backend).
-- Run on the shared MySQL used by SmSver1 and the API if you do not use `php artisan migrate`.
-- Idempotent: safe to run multiple times; skips if `autoreplies` table is missing.
--
-- CLI (same DB as db/dblink.php / Laravel .env):
--   mysql -h HOST -u USER -p DBNAME < SmSver1/db/alter_autoreplies_deleted_at.sql

SET @db = DATABASE();
SET @has_table := (
  SELECT COUNT(*) FROM information_schema.TABLES
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'autoreplies'
);
SET @exist := IF(
  @has_table = 0,
  999,
  (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'autoreplies' AND COLUMN_NAME = 'deleted_at'
  )
);
SET @sql := IF(
  @has_table = 0,
  'SELECT ''skip: no autoreplies table'' AS info',
  IF(
    @exist > 0,
    'SELECT ''autoreplies.deleted_at already present'' AS info',
    'ALTER TABLE `autoreplies` ADD COLUMN `deleted_at` TIMESTAMP NULL DEFAULT NULL AFTER `updated_at`'
  )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
