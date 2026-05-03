-- Run once if `users.api_key` is missing (required for My Account → API key).
ALTER TABLE `users` ADD COLUMN `api_key` VARCHAR(64) NULL DEFAULT NULL;
