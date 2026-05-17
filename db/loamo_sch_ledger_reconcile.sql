-- LOAMO SCH credit reconciliation (SmSver1 + shared transactions ledger)
-- Run on production DB (e.g. anderson_vllsms) after deploying ledger_balance.php fixes.
-- Review all SELECT results before running INSERT sections.

SET @school_uid = '255756715903';
SET @sender_name = 'LOAMO SCH';
SET @reseller_uid = '255739272247';
SET @since_ts = UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 90 DAY));

-- ---------------------------------------------------------------------------
-- 1) Sender ownership (billing account for private LOAMO SCH)
-- ---------------------------------------------------------------------------
SELECT id, sender_id, id_type, user_id, id_status
FROM senders
WHERE sender_id = @sender_name
ORDER BY id;

-- ---------------------------------------------------------------------------
-- 2) Ledger keys for school account (balance helper uses all of these)
-- ---------------------------------------------------------------------------
SELECT user_id, username, phone_number, contact_phone, client_name, status
FROM users
WHERE user_id = @school_uid
   OR username IN (@school_uid, 'HOPE G')
   OR phone_number = @school_uid
LIMIT 5;

SELECT
  user_id,
  COALESCE(SUM(allocated), 0) AS allocated,
  COALESCE(SUM(consumed), 0) AS consumed,
  COALESCE(SUM(allocated), 0) - COALESCE(SUM(consumed), 0) AS net
FROM transactions
WHERE user_id IN (
  SELECT DISTINCT k FROM (
    SELECT @school_uid AS k
    UNION SELECT username FROM users WHERE user_id = @school_uid AND username IS NOT NULL AND username <> ''
    UNION SELECT phone_number FROM users WHERE user_id = @school_uid AND phone_number IS NOT NULL AND phone_number <> ''
    UNION SELECT contact_phone FROM users WHERE user_id = @school_uid AND contact_phone IS NOT NULL AND contact_phone <> ''
  ) keys
)
GROUP BY user_id
ORDER BY net DESC;

-- ---------------------------------------------------------------------------
-- 3) Outgoing SMS using LOAMO SCH (last 90 days) — expected billable credits
-- ---------------------------------------------------------------------------
SELECT
  o.user_id AS outgoing_session_user,
  o.schedule,
  o.sms_status,
  COUNT(*) AS message_rows,
  SUM(o.credits) AS billable_credits
FROM outgoing o
WHERE o.sender_id = @sender_name
  AND o.date_created >= @since_ts
  AND o.sms_status IN ('Sent', 'Pending', 'Queued for provider')
GROUP BY o.user_id, o.schedule, o.sms_status
ORDER BY billable_credits DESC;

SELECT
  SUM(o.credits) AS total_outgoing_credits_90d
FROM outgoing o
WHERE o.sender_id = @sender_name
  AND o.date_created >= @since_ts
  AND o.sms_status IN ('Sent', 'Pending', 'Queued for provider');

-- ---------------------------------------------------------------------------
-- 4) Consumed recorded on school vs reseller session (mis-billing indicator)
-- ---------------------------------------------------------------------------
SELECT
  t.user_id,
  SUM(t.consumed) AS consumed_total,
  COUNT(*) AS consumed_rows
FROM transactions t
WHERE t.consumed > 0
  AND t.tdate >= @since_ts
  AND t.user_id IN (@school_uid, @reseller_uid)
GROUP BY t.user_id;

-- ---------------------------------------------------------------------------
-- 5) Gap: outgoing credits not matched by consumed on school billing key
--    (Adjust @billing_uid if senders.user_id differs)
-- ---------------------------------------------------------------------------
SET @billing_uid = (
  SELECT COALESCE(
    (SELECT user_id FROM senders WHERE sender_id = @sender_name AND LOWER(id_type) NOT IN ('public', 'global') AND id_status = 'Active' ORDER BY id LIMIT 1),
    @school_uid
  )
);

SELECT @billing_uid AS billing_user_id_for_loamo;

SET @expected = (
  SELECT COALESCE(SUM(o.credits), 0)
  FROM outgoing o
  WHERE o.sender_id = @sender_name
    AND o.date_created >= @since_ts
    AND o.sms_status IN ('Sent', 'Pending', 'Queued for provider')
);

SET @recorded_school = (
  SELECT COALESCE(SUM(t.consumed), 0)
  FROM transactions t
  WHERE t.user_id = @billing_uid
    AND t.consumed > 0
    AND t.tdate >= @since_ts
);

SET @recorded_reseller = (
  SELECT COALESCE(SUM(t.consumed), 0)
  FROM transactions t
  WHERE t.user_id = @reseller_uid
    AND t.consumed > 0
    AND t.tdate >= @since_ts
);

SELECT
  @expected AS outgoing_credits_90d,
  @recorded_school AS consumed_on_billing_account_90d,
  @recorded_reseller AS consumed_on_reseller_90d,
  GREATEST(0, @expected - @recorded_school) AS underbilled_on_school_account,
  @recorded_reseller AS wrongly_charged_reseller_instead;

-- ---------------------------------------------------------------------------
-- 6) OPTIONAL: Post missing consumption to school billing account
--    Only if underbilled_on_school_account > 0 and you have verified sends are legitimate.
--    Uncomment after review.
-- ---------------------------------------------------------------------------
-- SET @adjust = GREATEST(0, @expected - @recorded_school);
-- INSERT INTO transactions (user_id, allocated, consumed, tdate)
-- VALUES (@billing_uid, 0, @adjust, UNIX_TIMESTAMP());

-- ---------------------------------------------------------------------------
-- 7) OPTIONAL: Move mis-posted reseller consumption to school (manual review)
--    Example: if staff sent as LOAMO while logged in as reseller but school should pay.
-- ---------------------------------------------------------------------------
-- INSERT INTO transactions (user_id, allocated, consumed, tdate)
-- SELECT @billing_uid, 0, SUM(t.consumed), UNIX_TIMESTAMP()
-- FROM transactions t
-- INNER JOIN outgoing o ON o.user_id = @reseller_uid AND o.sender_id = @sender_name
--   AND o.date_created >= @since_ts AND o.sms_status = 'Sent'
-- WHERE t.user_id = @reseller_uid AND t.consumed > 0 AND t.tdate >= @since_ts;

-- ---------------------------------------------------------------------------
-- 8) Post-deploy monitor (run weekly) — should return 0 rows when billing is correct
-- ---------------------------------------------------------------------------
SELECT
  o.sender_id,
  DATE(FROM_UNIXTIME(o.date_created)) AS send_day,
  SUM(o.credits) AS outgoing_credits,
  COALESCE((
    SELECT SUM(t.consumed)
    FROM transactions t
    WHERE t.user_id = @billing_uid
      AND t.consumed > 0
      AND t.tdate BETWEEN UNIX_TIMESTAMP(DATE(FROM_UNIXTIME(o.date_created)))
                      AND UNIX_TIMESTAMP(DATE(FROM_UNIXTIME(o.date_created))) + 86399
  ), 0) AS consumed_same_day
FROM outgoing o
WHERE o.sender_id = @sender_name
  AND o.date_created >= UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 14 DAY))
  AND o.sms_status IN ('Sent', 'Pending', 'Queued for provider')
GROUP BY o.sender_id, send_day
HAVING outgoing_credits > consumed_same_day + 1
ORDER BY send_day DESC;
