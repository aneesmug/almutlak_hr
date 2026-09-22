-- Optional IP allowlist for zk_sync_import.php (App Settings -> Attendance
-- Config -> Sync Settings). Blank = no restriction, the shared secret
-- (zk_sync_secret_key) still gates the endpoint either way.
-- Safe to re-run: INSERT IGNORE.
-- Run once (phpMyAdmin import or `mysql almutlak_db < add_zk_sync_allowed_ip_setting.sql`).
-- Note: zk_helpers.php's zk_get_sync_allowed_ip() self-heals this row on first
-- use too, so running this file manually is optional.

INSERT IGNORE INTO `app_settings` (`setting_name`, `setting_value`, `setting_group`, `description`, `input_type`, `options`)
VALUES ('zk_sync_allowed_ip', '', 'sync_settings', 'Local BioTime-server IP address allowed to push attendance via zk_sync_import.php. Leave blank to allow any IP (the shared secret is still required either way).', 'text', NULL);
