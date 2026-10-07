<?php
/**
 * Dynamics 365 hooks called from the HR app's own flows.
 *
 * d365_auto_register_employee(): right after a new employee is saved, create the worker in D365
 * (company suggested from colleagues of the same app company). It never throws and never blocks
 * the save - the result is stored in d365_worker_status, and on failure the employee header
 * (includes/emp_top_info.php) shows the manual "Add to D365" button.
 */
function d365_auto_register_employee($conDB, $empId, $company = '')
{
    // Callers answer with JSON (ajaxEmployeeCreateModal.php) - a stray warning must not break it
    ob_start();
    try {
        $empId = trim((string)$empId);
        if ($empId === '' || !($conDB instanceof mysqli)) {
            return;
        }
        require_once __DIR__ . '/D365Workers.php';
        if (!d365_enabled($conDB)) {
            return; // Microsoft Dynamics 365 switched off in App Settings > D365 Config
        }
        $config = D365Client::loadConfig();
        if (empty($config['CLIENT_SECRET']) || empty($config['RESOURCE_URL'])) {
            return; // D365 not configured - nothing to do
        }
        @set_time_limit(120);
        (new D365Workers($conDB, new D365Client($config)))->autoRegister($empId, $company);
    } catch (Throwable $ex) {
        error_log('D365 auto-register ' . $empId . ': ' . $ex->getMessage());
    } finally {
        $stray = ob_get_clean();
        if ($stray !== '' && $stray !== false) {
            error_log('D365 auto-register ' . $empId . ' output: ' . mb_substr(strip_tags($stray), 0, 500));
        }
    }
}
