-- Soft-delete column for autoreplies (aligns with Laravel SoftDeletes on vll_backend).
-- Run on the shared MySQL used by SmSver1 and the API if you do not use `php artisan migrate`.
-- If you see "Duplicate column name 'deleted_at'", the column already exists — skip.

ALTER TABLE `autoreplies`
  ADD COLUMN `deleted_at` TIMESTAMP NULL DEFAULT NULL AFTER `updated_at`;
