-- Shared MySQL (SmSver1 + vll_backend). Safe to run more than once.
-- Use when `php artisan migrate` is not available (e.g. PHP < 8.1 on XAMPP).

SET @db = DATABASE();

-- segment
SET @s = (
  SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'incoming' AND COLUMN_NAME = 'segment') > 0,
    'SELECT ''column segment already exists'' AS msg',
    'ALTER TABLE `incoming` ADD COLUMN `segment` VARCHAR(64) NULL DEFAULT NULL AFTER `message`'
  )
);
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- auto_reply_status
SET @s = (
  SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'incoming' AND COLUMN_NAME = 'auto_reply_status') > 0,
    'SELECT ''column auto_reply_status already exists'' AS msg',
    'ALTER TABLE `incoming` ADD COLUMN `auto_reply_status` VARCHAR(32) NULL DEFAULT NULL AFTER `segment`'
  )
);
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- is_read (API markRead)
SET @s = (
  SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'incoming' AND COLUMN_NAME = 'is_read') > 0,
    'SELECT ''column is_read already exists'' AS msg',
    'ALTER TABLE `incoming` ADD COLUMN `is_read` TINYINT(1) NOT NULL DEFAULT 0 AFTER `user_id`'
  )
);
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
