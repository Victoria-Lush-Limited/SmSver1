-- SmSver1 portal: ALLIANCE FRANCISE ARUSHA | user_id (MSISDN) 255786111414 | director@aftarusha.org
-- Run once on the SAME MySQL database as db/dblink.php / production VLL_DB_*.
-- 1) Keeps header/account display fields aligned with the corporate record.
-- 2) Adjusts SMS credit ledger from 2719 -> 519 only when balance is still 2719 (idempotent guard).

UPDATE `users` SET
  `client_name` = 'ALLIANCE FRANCISE ARUSHA',
  `email` = 'director@aftarusha.org',
  `phone_number` = '255786111414',
  `contact_phone` = '255786111414'
WHERE `user_id` = '255786111414'
LIMIT 1;

INSERT INTO `transactions` (`user_id`, `username`, `allocated`, `consumed`, `tdate`)
SELECT '255786111414', 'alliancefrancaisearusha', 0, 2200, UNIX_TIMESTAMP()
FROM DUAL
WHERE (SELECT COALESCE(SUM(`allocated`) - SUM(`consumed`), 0) FROM `transactions` WHERE `user_id` = '255786111414') = 2719
LIMIT 1;
