<?php
/**
 * Master switch for the whole Microsoft Dynamics 365 integration
 * (App Settings > D365 Config > "Enable Microsoft Dynamics 365", setting d365_enabled).
 * Off = every D365 tab, status card, button, menu link, report and page is hidden, the D365 pages /
 * endpoints refuse, and D365Client::loadConfig() throws, so no call reaches D365 at all.
 * A missing row counts as ON (keeps the integration working until the switch is saved).
 */
if (!function_exists('d365_enabled')) {
    function d365_enabled($conDB = null)
    {
        static $enabled = null;
        if ($enabled !== null) {
            return $enabled;
        }
        if (!($conDB instanceof mysqli)) {
            global $conDB;
        }
        $enabled = true;
        if ($conDB instanceof mysqli) {
            $res = @$conDB->query("SELECT setting_value FROM app_settings WHERE setting_name = 'd365_enabled' LIMIT 1");
            if ($res && ($row = $res->fetch_assoc())) {
                $enabled = trim((string)$row['setting_value']) !== '0';
            }
        }
        return $enabled;
    }
}
