<?php
/**
 * Master switch for the Attendance module (biometric devices, attendance records, timetables)
 * (App Settings > Integrations > "Enable Attendance", setting attendance_enabled).
 * Off = attendance menu links, pages, the employee Attendance tab, the attendance report,
 * Attendance Config settings and attendance permissions are hidden; the pages / endpoints refuse;
 * device pushes (zk_sync_import.php, iclock/cdata) are answered with an error so the devices /
 * sync agent keep their punches and send them again once it is switched back on; payroll skips
 * the automatic attendance deduction / overtime.
 * A missing row counts as ON. Accepts a mysqli or PDO connection (payroll uses PDO, ZK uses its own mysqli).
 */
if (!function_exists('attendance_enabled')) {
    function attendance_enabled($db = null)
    {
        static $enabled = null;
        if ($enabled !== null) {
            return $enabled;
        }
        if (!($db instanceof mysqli) && !($db instanceof PDO)) {
            global $conDB;
            $db = $conDB;
        }
        $enabled = true;
        $sql = "SELECT setting_value FROM app_settings WHERE setting_name = 'attendance_enabled' LIMIT 1";
        try {
            if ($db instanceof mysqli) {
                $res = @$db->query($sql);
                $value = ($res && ($row = $res->fetch_assoc())) ? $row['setting_value'] : null;
            } elseif ($db instanceof PDO) {
                $value = $db->query($sql)->fetchColumn();
                $value = $value === false ? null : $value;
            } else {
                $value = null;
            }
            if ($value !== null) {
                $enabled = trim((string)$value) !== '0';
            }
        } catch (Throwable $ex) {
            // table not reachable - leave attendance on
        }
        return $enabled;
    }
}
