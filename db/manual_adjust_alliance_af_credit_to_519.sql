-- Ledger correction for ALLIANCE FRANCISE ARUSHA | director@aftarusha.org | user_id 255786111414 (legacy username alliancefrancaisearusha).
-- Portal balance = SUM(allocated) - SUM(consumed) on `transactions` (see SmSver1 header.php / index.php).
-- This insert records +2200 in `consumed` with no allocation, reducing available credits by 2200 (2719 -> 519).
-- Idempotent guard: runs only when current balance is exactly 2719 for that user_id.
INSERT INTO `transactions` (`user_id`, `username`, `allocated`, `consumed`, `tdate`)
SELECT '255786111414', 'alliancefrancaisearusha', 0, 2200, UNIX_TIMESTAMP()
FROM DUAL
WHERE (SELECT COALESCE(SUM(`allocated`) - SUM(`consumed`), 0) FROM `transactions` WHERE `user_id` = '255786111414') = 2719
LIMIT 1;
