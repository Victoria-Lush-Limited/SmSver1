-- One-time migration: default system sender id for FastHub / app OTP paths.
-- Safe to run multiple times (only updates legacy hyphenated values).

UPDATE app
SET sender_id = 'VLL SMS'
WHERE sender_id IN ('VLL-SMS', 'VLLSMS', 'vll-sms', 'Vll-Sms');

UPDATE senders
SET sender_id = 'VLL SMS'
WHERE sender_id IN ('VLL-SMS', 'VLLSMS', 'vll-sms', 'Vll-Sms');

UPDATE outgoing
SET sender_id = 'VLL SMS'
WHERE sender_id IN ('VLL-SMS', 'VLLSMS', 'vll-sms', 'Vll-Sms')
  AND sms_status = 'Pending';

UPDATE custom_sms
SET sender_id = 'VLL SMS'
WHERE sender_id IN ('VLL-SMS', 'VLLSMS', 'vll-sms', 'Vll-Sms');
