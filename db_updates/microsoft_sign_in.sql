-- Microsoft Sign-in (Entra ID) for OTP users (Oct 2026)
-- Both changes are also applied automatically (includes/microsoft_auth.php) the first time
-- App Settings or the login page loads; run this manually only if you prefer to apply them up front.

-- 1. Microsoft object ID bound to the login account at first Microsoft sign-in (skip if the column exists)
ALTER TABLE `admin_login` ADD COLUMN `ms_oid` VARCHAR(64) NULL DEFAULT NULL;

-- 1b. Per-user sign-in method, assigned in App Settings > Microsoft Sign In > Users (skip if the column exists)
--     'email_otp' (default) or 'microsoft'
ALTER TABLE `admin_login` ADD COLUMN `login_method` VARCHAR(20) NOT NULL DEFAULT 'email_otp';

-- 2. Settings rows (switch starts OFF)
INSERT INTO app_settings (setting_name, setting_value, setting_group, description, input_type, options)
SELECT * FROM (
    SELECT 'msauth_enabled' AS setting_name, '0' AS setting_value, 'integrations' AS setting_group, 'Enable Microsoft Sign-in (Entra ID) for OTP users' AS description, 'select' AS input_type, '{"1":"Enabled","0":"Disabled"}' AS options UNION ALL
    SELECT 'msauth_tenant_id', '1dd2e950-8575-4619-859c-bdb07e7d4695', 'Microsoft_Sign_In', 'Tenant ID - Azure: App registrations > HR System Sign-in > Overview > "Directory (tenant) ID"', 'text', NULL UNION ALL
    SELECT 'msauth_client_id', '', 'Microsoft_Sign_In', 'Client ID - Azure: App registrations > HR System Sign-in > Overview > "Application (client) ID"', 'text', NULL UNION ALL
    SELECT 'msauth_client_secret', '', 'Microsoft_Sign_In', 'Client Secret - Azure: HR System Sign-in > Certificates & secrets > Client secrets > "Value" column (NOT "Secret ID"; shown only once when created)', 'password', NULL UNION ALL
    SELECT 'msauth_mode', 'fallback', 'Microsoft_Sign_In', 'Sign-in Mode - what happens when Microsoft sign-in fails or is cancelled', 'select', '{"fallback":"Microsoft first - email OTP allowed as backup","required":"Microsoft only - no email OTP"}' UNION ALL
    SELECT 'msauth_scope', 'selected', 'msauth_internal', 'Microsoft Sign-in: users (selected / all)', 'select', '{"selected":"Selected users only","all":"All OTP users (forced)"}' UNION ALL
    SELECT 'msauth_allowed_domains', 'almutlak.com', 'Microsoft_Sign_In', 'Allowed Email Domains - comma separated, e.g. almutlak.com (staff with another email domain keep using email OTP)', 'text', NULL
) AS new_rows
WHERE NOT EXISTS (SELECT 1 FROM app_settings a WHERE a.setting_name = new_rows.setting_name);
