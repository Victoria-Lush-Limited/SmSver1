-- Run once on the SMS MySQL database (phpMyAdmin → SQL, or mysql CLI).
-- Stores sign-in instructions shown on SmSver1/login.php (plain text; no code changes needed to update copy).

ALTER TABLE `app`
  ADD COLUMN `login_help` TEXT NULL DEFAULT NULL COMMENT 'Shown on client login page';

-- Example (edit to suit your organisation):
-- UPDATE `app` SET `login_help` = 'Sign in with your User ID (phone number) or username and your account password.\nForgot your password? Use Forgot Password below.\nNew user? Use Sign Up.' WHERE `app_id` = 1;
