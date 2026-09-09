-- App Settings sidebar cleanup: merge single-purpose tabs into related ones.
-- Safe to re-run: plain UPDATEs, no-op if already merged.
--
-- Localization (default_language, timezone) -> General
-- Api (google_maps_api_key) -> Developer
-- Social (facebook_url/twitter_url/instagram_url/linkedin_url) is left in the DB
-- untouched (unused elsewhere in the app - confirmed via grep) but its tab is
-- hidden client-side in app_settings.php.

UPDATE `app_settings` SET `setting_group` = 'general' WHERE `setting_group` = 'localization';
UPDATE `app_settings` SET `setting_group` = 'developer' WHERE `setting_group` = 'api';
