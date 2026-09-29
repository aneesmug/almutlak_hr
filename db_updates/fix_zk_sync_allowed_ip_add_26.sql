-- BioTime sync PC now pushes from 185.137.245.26, but zk_sync_allowed_ip only had
-- 185.137.245.28, so every zk_sync_import.php push was rejected (403) since 2026-09-22.
-- Adds .26 to the allowlist (keeps .28). zk_sync_ip_is_allowed() accepts a comma list.
-- Same result as App Settings -> Attendance Config -> Sync Settings -> "Add to allowed IPs" -> Save.

UPDATE `app_settings`
SET `setting_value` = CASE
        WHEN TRIM(`setting_value`) = '' THEN '185.137.245.26'
        ELSE CONCAT(TRIM(`setting_value`), ', 185.137.245.26')
    END
WHERE `setting_name` = 'zk_sync_allowed_ip'
  AND FIND_IN_SET('185.137.245.26', REPLACE(REPLACE(`setting_value`, ' ', ''), ';', ',')) = 0;
