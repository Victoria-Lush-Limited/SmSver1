-- Run once on the SMS database to improve throughput under many users / large queues.
-- Safe to re-run: skip any line that errors with "Duplicate" if the object already exists.
--
-- Application tuning (optional, set in Apache SetEnv / php-fpm pool / systemd):
--   VLL_SEND_INSERT_CHUNK      — rows per INSERT when queueing outgoing (default 200, max 350).
--   VLL_SEND_MAX_SECONDS       — max PHP time for compose send (default 600).
--   VLL_OUTGOING_WORKER_BATCH  — rows smpp_client.php picks per cron run (default 200, max 500).
--   VLL_OUTGOING_MAX_ATTEMPTS  — retry cap before marking Failed (default 5).
-- Server: raise innodb_buffer_pool_size and max_allowed_packet as traffic grows; run one smpp_client
-- cron per host unless you add row locking (e.g. FOR UPDATE SKIP LOCKED) for multiple workers.

-- API log table (previously created on-the-fly by smpp_client.php each run — now created here only)
CREATE TABLE IF NOT EXISTS `sms_api_logs` (
  `log_id` INT NOT NULL AUTO_INCREMENT,
  `provider` VARCHAR(50) NOT NULL,
  `request_body` MEDIUMTEXT,
  `response_body` MEDIUMTEXT,
  `http_code` INT DEFAULT NULL,
  `status` VARCHAR(25) NOT NULL,
  `created_at` INT NOT NULL,
  PRIMARY KEY (`log_id`),
  KEY `idx_sms_api_logs_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Worker query: WHERE sms_status='Pending' AND date_created <= ? AND attempts < ?
ALTER TABLE `outgoing` ADD INDEX `idx_outgoing_worker` (`sms_status`, `date_created`, `attempts`);

-- Balance / history lookups per user
ALTER TABLE `transactions` ADD INDEX `idx_transactions_user` (`user_id`);

-- Compose: group → contacts resolution
ALTER TABLE `group_contacts` ADD INDEX `idx_group_contacts_group` (`group_id`);
ALTER TABLE `contacts` ADD INDEX `idx_contacts_user` (`user_id`);
