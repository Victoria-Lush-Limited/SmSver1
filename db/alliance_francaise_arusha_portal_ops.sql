-- SmSver1 portal: ALLIANCE FRANCISE ARUSHA | user_id 255786111414 | director@aftarusha.org
-- Run on the SAME MySQL database as db/dblink.php / production VLL_DB_*.
-- 1) Profile fields on `users`.
-- 2) Ledger: reduce displayed balance to 519 whenever it is still ABOVE 519 (idempotent; works for 2718, 2719, etc.).

UPDATE `users` SET
  `client_name` = 'ALLIANCE FRANCISE ARUSHA',
  `email` = 'director@aftarusha.org',
  `phone_number` = '255786111414',
  `contact_phone` = '255786111414'
WHERE `user_id` = '255786111414'
LIMIT 1;

SET @bal := (
  SELECT COALESCE(SUM(`allocated`) - SUM(`consumed`), 0)
  FROM `transactions`
  WHERE `user_id` IN ('255786111414', 'AF Arusha', 'alliancefrancaisearusha')
);

INSERT INTO `transactions` (`user_id`, `username`, `allocated`, `consumed`, `tdate`)
SELECT '255786111414', 'alliancefrancaisearusha', 0, (@bal - 519), UNIX_TIMESTAMP()
FROM DUAL
WHERE @bal > 519
LIMIT 1;
