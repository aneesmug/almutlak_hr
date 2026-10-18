-- Google Authenticator (TOTP 2FA) for OTP users (Oct 2026)
-- All changes are also applied automatically (includes/GoogleAuth/google_auth.php) the first time
-- App Settings or the login page loads; run this manually only if you prefer to apply them up front.

-- 1. Per-user columns on admin_login (skip any column that already exists)
ALTER TABLE `admin_login` ADD COLUMN `gauth_assigned` TINYINT(1) NOT NULL DEFAULT 0;      -- added in App Settings > Google Authenticator
ALTER TABLE `admin_login` ADD COLUMN `gauth_secret` VARCHAR(255) NULL DEFAULT NULL;        -- encrypted TOTP secret (key: includes/GoogleAuth/gauth_secret_key.php)
ALTER TABLE `admin_login` ADD COLUMN `gauth_enrolled_at` DATETIME NULL DEFAULT NULL;
ALTER TABLE `admin_login` ADD COLUMN `gauth_last_step` BIGINT NULL DEFAULT NULL;           -- last used 30s step (no code reuse)

-- 2. Settings rows (switch starts OFF)
INSERT INTO app_settings (setting_name, setting_value, setting_group, description, input_type, options)
SELECT * FROM (
    SELECT 'gauth_enabled' AS setting_name, '0' AS setting_value, 'integrations' AS setting_group, 'Enable Google Authenticator (2FA) for OTP users' AS description, 'select' AS input_type, '{"1":"Enabled","0":"Disabled"}' AS options UNION ALL
    SELECT 'gauth_issuer', 'Al Mutlak HR', 'Google_Authenticator', 'Issuer Name - shown above the 6-digit code in the authenticator app (changing it only affects new QR codes)', 'text', NULL UNION ALL
    SELECT 'gauth_mode', 'fallback', 'Google_Authenticator', 'Sign-in Mode - what happens when the user cannot use the authenticator app', 'select', '{"fallback":"Authenticator first - email OTP allowed as backup","required":"Authenticator only - no email OTP"}' UNION ALL
    SELECT 'gauth_scope', 'selected', 'gauth_internal', 'Google Authenticator: users (selected / all)', 'select', '{"selected":"Selected users only","all":"All OTP users (forced)"}'
) AS new_rows
WHERE NOT EXISTS (SELECT 1 FROM app_settings a WHERE a.setting_name = new_rows.setting_name);
