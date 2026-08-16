<?php
/**
 * Employee Additional Information handler (Salary Grade, Ticket, Labour Office Expenses,
 * Iqama Renewal Fee, Citizen (Local) relation, Saudi Engineering Council fee/expiry).
 * Monthly Leave Accrual is derived from contract data, not stored here.
 * Medical Insurance / Insurance No / Medical Expiry / Medical Class live in
 * `employee_medical_insurance` (yearly-renewed, tracked separately - see
 * employeeMedicalInsuranceHandler.php).
 * Powers the "Additional Information" tab on view_employee.php.
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/session_check.php';
require_once __DIR__ . '/../../includes/helper_functions.php';
require_once __DIR__ . '/../special_access_helper.php';

$ajaxType = $_POST['ajaxType'] ?? '';

// Same visibility rule as the salary boxes / Payroll tab on view_employee.php.
function additionalInfoCanView($conDB, $empid, $user_role, $user_type, $is_system_admin, $isHR, $isDeptHr)
{
    return ($is_system_admin || $isHR || $isDeptHr
        || user_has_special_access($conDB, $empid ?? '', 'view_employee_salary_value', $user_role ?? '', $user_type ?? '', $is_system_admin ?? false));
}

if ($ajaxType === 'get_employee_additional_info') {
    $emp_id = trim((string)($_POST['emp_id'] ?? ''));
    if ($emp_id === '') {
        echo json_encode(['status' => 400, 'message' => 'Missing employee ID']);
        exit;
    }
    if (!additionalInfoCanView($conDB, $empid, $user_role, $user_type, $is_system_admin, $isHR, $isDeptHr)) {
        echo json_encode(['status' => 403, 'message' => 'Access denied']);
        exit;
    }

    $stmt = mysqli_prepare($conDB, "SELECT salary_grade, dependants_count, ticket_fare, labour_office_expense, iqama_renewal_fee, citizen_local_relation, eng_council_fee, eng_council_expiry FROM `employee_additional_info` WHERE `emp_id` = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, "s", $emp_id);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt)) ?: [];
    mysqli_stmt_close($stmt);

    echo json_encode(['status' => 200, 'data' => $row]);
    exit;
}

if ($ajaxType === 'save_employee_additional_info') {
    $emp_id = trim((string)($_POST['emp_id'] ?? ''));
    if ($emp_id === '') {
        echo json_encode(['status' => 400, 'message' => 'Missing employee ID']);
        exit;
    }
    // Editing is restricted to HR/Admin - the special-access "view" grant does not extend to edit.
    if (!($is_system_admin || $isHR || $isDeptHr)) {
        echo json_encode(['status' => 403, 'message' => 'Access denied']);
        exit;
    }

    $allowedRelations = ['Son', 'Daughter', 'Wife', 'Husband'];
    $allowedGrades = array_map('strval', range(1, 12));

    $salary_grade = trim((string)($_POST['salary_grade'] ?? ''));
    $salary_grade = in_array($salary_grade, $allowedGrades, true) ? $salary_grade : null;

    $citizen_local_relation = trim((string)($_POST['citizen_local_relation'] ?? ''));
    $citizen_local_relation = in_array($citizen_local_relation, $allowedRelations, true) ? $citizen_local_relation : null;

    $dependants_count = ($_POST['dependants_count'] ?? '') === '' ? null : max(0, (int)$_POST['dependants_count']);
    $ticket_fare = ($_POST['ticket_fare'] ?? '') === '' ? null : (float)$_POST['ticket_fare'];
    $labour_office_expense = ($_POST['labour_office_expense'] ?? '') === '' ? null : (float)$_POST['labour_office_expense'];
    $iqama_renewal_fee = ($_POST['iqama_renewal_fee'] ?? '') === '' ? null : (float)$_POST['iqama_renewal_fee'];
    $eng_council_fee = ($_POST['eng_council_fee'] ?? '') === '' ? null : (float)$_POST['eng_council_fee'];

    $eng_council_expiry = trim((string)($_POST['eng_council_expiry'] ?? ''));
    $eng_council_expiry = (preg_match('/^\d{4}-\d{2}-\d{2}$/', $eng_council_expiry)) ? $eng_council_expiry : null;

    $stmt = mysqli_prepare($conDB, "INSERT INTO `employee_additional_info`
            (`emp_id`, `salary_grade`, `dependants_count`, `ticket_fare`, `labour_office_expense`, `iqama_renewal_fee`, `citizen_local_relation`, `eng_council_fee`, `eng_council_expiry`, `updated_by`)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            `salary_grade` = VALUES(`salary_grade`),
            `dependants_count` = VALUES(`dependants_count`),
            `ticket_fare` = VALUES(`ticket_fare`),
            `labour_office_expense` = VALUES(`labour_office_expense`),
            `iqama_renewal_fee` = VALUES(`iqama_renewal_fee`),
            `citizen_local_relation` = VALUES(`citizen_local_relation`),
            `eng_council_fee` = VALUES(`eng_council_fee`),
            `eng_council_expiry` = VALUES(`eng_council_expiry`),
            `updated_by` = VALUES(`updated_by`)");

    if (!$stmt) {
        echo json_encode(['status' => 500, 'message' => 'Database error: ' . mysqli_error($conDB)]);
        exit;
    }

    $updated_by = $empid ?? '';
    mysqli_stmt_bind_param(
        $stmt,
        "ssidddsdss",
        $emp_id,
        $salary_grade,
        $dependants_count,
        $ticket_fare,
        $labour_office_expense,
        $iqama_renewal_fee,
        $citizen_local_relation,
        $eng_council_fee,
        $eng_council_expiry,
        $updated_by
    );

    if (mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        if (class_exists('ActivityLogger')) {
            ActivityLogger::logUpdate(
                'Employee',
                'employeeAdditionalInfoHandler.php',
                $emp_id,
                [],
                ['salary_grade' => $salary_grade],
                "Updated additional information for employee: {$emp_id}",
                'employee_additional_info'
            );
        }
        echo json_encode(['status' => 200, 'message' => __('update_successful') ?: 'Updated successfully']);
    } else {
        $error = mysqli_stmt_error($stmt);
        mysqli_stmt_close($stmt);
        echo json_encode(['status' => 500, 'message' => 'Database error: ' . $error]);
    }
    exit;
}

echo json_encode(['status' => 400, 'message' => 'Invalid AJAX type specified.']);
