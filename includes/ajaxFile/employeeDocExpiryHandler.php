<?php
/**
 * Quick expiry-date update for employee documents, used by the "Update Expiry" button on
 * the document alerts of view_employee.php (get_document_expiry_alerts()).
 * Handles ID/Iqama (employees.iqama_exp Hijri + iqama_exp_g Gregorian) and Passport
 * (employees.passport_exp). Medical Insurance renewals go through
 * employeeMedicalInsuranceHandler.php (add_employee_medical_insurance) instead, since
 * that table keeps one row per year.
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/session_check.php';
require_once __DIR__ . '/../../includes/helper_functions.php';

$ajaxType = $_POST['ajaxType'] ?? '';

if ($ajaxType === 'update_doc_expiry') {
    // Same rule as the Medical Insurance add button (HR / Dept HR / sys admin).
    if (!(($is_system_admin ?? false) || ($isHR ?? false) || ($isDeptHr ?? false))) {
        send_json_response(__('access_denied', 'Access Denied!'), __('you_do_not_have_permission', 'You do not have permission to perform this action.'), 'error');
    }

    $emp_id   = trim((string)($_POST['emp_id'] ?? ''));
    $doc      = (string)($_POST['doc'] ?? '');
    $expiry_g = trim((string)($_POST['expiry_g'] ?? ''));
    $expiry_h = trim((string)($_POST['expiry_h'] ?? ''));

    $g_date = DateTime::createFromFormat('!Y-m-d', $expiry_g);
    if ($emp_id === '' || !in_array($doc, ['iqama', 'passport'], true) || !$g_date || $g_date->format('Y-m-d') !== $expiry_g) {
        send_json_response(__('error', 'Error'), __('missing_required_parameters', 'Missing or invalid parameters.'), 'error');
    }

    // Only employees the user is allowed to see (company / department / employee scope).
    $scope = getCompanyFilterSQL('comp_no', true) . getDepartmentFilterSQL('dept', true) . getEmployeeFilterSQL('emp_id', true);

    if ($doc === 'iqama') {
        // The Hijri value comes from the browser's Umm al-Qura picker (AppDate.hijriPair);
        // fall back to the server conversion only when it is missing/invalid.
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $expiry_h)) {
            include_once __DIR__ . '/../../includes/Hijri_GregorianConvert.php';
            $DateConv = new Hijri_GregorianConvert;
            $expiry_h = $DateConv->GregorianToHijri($expiry_g, 'YYYY-MM-DD');
        }
        $stmt = $pdo->prepare("UPDATE `employees` SET `iqama_exp` = ?, `iqama_exp_g` = ? WHERE `emp_id` = ?" . $scope);
        $ok = $stmt->execute([$expiry_h, $expiry_g, $emp_id]);
    } else {
        $stmt = $pdo->prepare("UPDATE `employees` SET `passport_exp` = ? WHERE `emp_id` = ?" . $scope);
        $ok = $stmt->execute([$expiry_g, $emp_id]);
    }

    if ($ok) {
        send_json_response(__('updated', 'Updated'), __('this_record_has_been_updated_successfully', 'This record has been updated successfully.'), 'success');
    }
    send_json_response(__('error', 'Error'), __('record_not_updated_because_there_are_some_error', 'Record not updated.'), 'error');
}

send_json_response(__('error', 'Error'), 'Invalid AJAX type specified.', 'error');
