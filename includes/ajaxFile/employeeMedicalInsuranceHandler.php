<?php
/**
 * Medical Insurance (yearly-renewed) handler - Insurance No, amount, expiry, class.
 * Unlike employee_additional_info, this is a history table: every renewal adds a new
 * row instead of overwriting, and `status` tracks which one is currently active. Adding
 * a new record flips any existing active row(s) for that employee to 'expired' first.
 * Powers the "Medical Insurance" section on the Additional Information tab (view_employee.php)
 * and the bulk Excel import tool (import_medical_insurance.php).
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/session_check.php';
require_once __DIR__ . '/../../includes/helper_functions.php';
require_once __DIR__ . '/../special_access_helper.php';

$ajaxType = $_POST['ajaxType'] ?? '';

// Same visibility rule as the salary boxes / Additional Information tab on view_employee.php.
function medicalInsuranceCanView($conDB, $empid, $user_role, $user_type, $is_system_admin, $isHR, $isDeptHr)
{
    return ($is_system_admin || $isHR || $isDeptHr
        || user_has_special_access($conDB, $empid ?? '', 'view_employee_salary_value', $user_role ?? '', $user_type ?? '', $is_system_admin ?? false));
}

/**
 * Expires any existing active row for $emp_id and inserts a new active row.
 * Shared by the single "Add New Year" modal and the bulk Excel import.
 * Returns ['ok' => bool, 'error' => string|null].
 */
function addOrRenewMedicalInsurance($conDB, $emp_id, $insurance_no, $med_insurance, $medical_expiry, $medical_class, $created_by)
{
    mysqli_begin_transaction($conDB);

    $expireStmt = mysqli_prepare($conDB, "UPDATE `employee_medical_insurance` SET `status` = 'expired' WHERE `emp_id` = ? AND `status` = 'active'");
    $expireOk = $expireStmt && mysqli_stmt_bind_param($expireStmt, "s", $emp_id) && mysqli_stmt_execute($expireStmt);
    if ($expireStmt) {
        mysqli_stmt_close($expireStmt);
    }

    $insertOk = false;
    $newId = null;
    if ($expireOk) {
        $insertStmt = mysqli_prepare($conDB, "INSERT INTO `employee_medical_insurance` (`emp_id`, `insurance_no`, `med_insurance`, `medical_expiry`, `medical_class`, `status`, `created_by`) VALUES (?, ?, ?, ?, ?, 'active', ?)");
        if ($insertStmt) {
            mysqli_stmt_bind_param($insertStmt, "ssdsss", $emp_id, $insurance_no, $med_insurance, $medical_expiry, $medical_class, $created_by);
            $insertOk = mysqli_stmt_execute($insertStmt);
            $newId = mysqli_insert_id($conDB);
            mysqli_stmt_close($insertStmt);
        }
    }

    if ($expireOk && $insertOk) {
        mysqli_commit($conDB);
        return ['ok' => true, 'error' => null, 'id' => $newId];
    }

    $error = mysqli_error($conDB);
    mysqli_rollback($conDB);
    return ['ok' => false, 'error' => $error, 'id' => null];
}

if ($ajaxType === 'get_employee_medical_insurance') {
    $emp_id = trim((string)($_POST['emp_id'] ?? ''));
    if ($emp_id === '') {
        echo json_encode(['status' => 400, 'message' => 'Missing employee ID']);
        exit;
    }
    if (!medicalInsuranceCanView($conDB, $empid, $user_role, $user_type, $is_system_admin, $isHR, $isDeptHr)) {
        echo json_encode(['status' => 403, 'message' => 'Access denied']);
        exit;
    }

    $stmt = mysqli_prepare($conDB, "SELECT id, insurance_no, med_insurance, medical_expiry, medical_class, status, created_at FROM `employee_medical_insurance` WHERE `emp_id` = ? ORDER BY `created_at` DESC, `id` DESC");
    mysqli_stmt_bind_param($stmt, "s", $emp_id);
    mysqli_stmt_execute($stmt);
    $rows = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);

    echo json_encode(['status' => 200, 'data' => $rows]);
    exit;
}

if ($ajaxType === 'add_employee_medical_insurance') {
    $emp_id = trim((string)($_POST['emp_id'] ?? ''));
    if ($emp_id === '') {
        echo json_encode(['status' => 400, 'message' => 'Missing employee ID']);
        exit;
    }
    if (!($is_system_admin || $isHR || $isDeptHr)) {
        echo json_encode(['status' => 403, 'message' => 'Access denied']);
        exit;
    }

    $allowedMedicalClasses = ['CLT', 'C', 'B', 'A', 'A+'];

    $insurance_no = trim((string)($_POST['insurance_no'] ?? ''));
    $insurance_no = $insurance_no !== '' ? $insurance_no : null;

    $med_insurance = ($_POST['med_insurance'] ?? '') === '' ? null : (float)$_POST['med_insurance'];

    $medical_expiry = trim((string)($_POST['medical_expiry'] ?? ''));
    $medical_expiry = (preg_match('/^\d{4}-\d{2}-\d{2}$/', $medical_expiry)) ? $medical_expiry : null;

    $medical_class = trim((string)($_POST['medical_class'] ?? ''));
    $medical_class = in_array($medical_class, $allowedMedicalClasses, true) ? $medical_class : null;

    if ($insurance_no === null && $med_insurance === null && $medical_expiry === null && $medical_class === null) {
        echo json_encode(['status' => 400, 'message' => 'Enter at least one field']);
        exit;
    }

    $created_by = $empid ?? '';
    $result = addOrRenewMedicalInsurance($conDB, $emp_id, $insurance_no, $med_insurance, $medical_expiry, $medical_class, $created_by);

    if ($result['ok']) {
        if (class_exists('ActivityLogger')) {
            ActivityLogger::logUpdate(
                'Employee',
                'employeeMedicalInsuranceHandler.php',
                $emp_id,
                [],
                ['insurance_no' => $insurance_no, 'med_insurance' => $med_insurance, 'medical_expiry' => $medical_expiry, 'medical_class' => $medical_class],
                "Added new medical insurance record for employee: {$emp_id}",
                'employee_medical_insurance'
            );
        }
        echo json_encode(['status' => 200, 'message' => __('update_successful') ?: 'Updated successfully', 'id' => $result['id']]);
    } else {
        echo json_encode(['status' => 500, 'message' => 'Database error: ' . $result['error']]);
    }
    exit;
}

if ($ajaxType === 'bulk_import_employee_medical_insurance') {
    if (!($is_system_admin || $isHR || $isDeptHr
        || user_has_special_access($conDB, $empid ?? '', 'access_import_medical_insurance', $user_role ?? '', $user_type ?? '', $is_system_admin ?? false))) {
        echo json_encode(['status' => 'error', 'title' => 'Access Denied', 'message' => 'You are not allowed to import medical insurance.', 'type' => 'error']);
        exit;
    }

    if (!isset($_FILES['insurance_file']) || $_FILES['insurance_file']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['status' => 'error', 'title' => 'Upload Error', 'message' => 'Please choose a valid file to upload.', 'type' => 'error']);
        exit;
    }

    $autoloadPath = __DIR__ . '/../../vendor/autoload.php';
    if (!file_exists($autoloadPath)) {
        echo json_encode(['status' => 'error', 'title' => 'Server Error', 'message' => 'Vendor autoload not found. Run composer install.', 'type' => 'error']);
        exit;
    }
    require_once $autoloadPath;

    $tmpPath = $_FILES['insurance_file']['tmp_name'];
    $fileName = strtolower($_FILES['insurance_file']['name']);
    $isCsv = (substr($fileName, -4) === '.csv');

    try {
        if ($isCsv) {
            $rows = [];
            if (($handle = fopen($tmpPath, 'r')) !== false) {
                while (($data = fgetcsv($handle)) !== false) {
                    $rows[] = $data;
                }
                fclose($handle);
            }
            if (count($rows) < 2) {
                throw new Exception('File must contain a header row and at least one data row.');
            }
            $dataRows = array_slice($rows, 1);
        } else {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($tmpPath);
            $sheet = $spreadsheet->getSheet(0);
            $highestRow = $sheet->getHighestDataRow();
            $dataRows = [];
            for ($row = 2; $row <= $highestRow; $row++) {
                $expiryCell = $sheet->getCell("D$row");
                $expiryRaw = $expiryCell->getValue();
                if (is_numeric($expiryRaw)) {
                    // Excel serial date -> Y-m-d, matches PhpSpreadsheet's date handling elsewhere in this app.
                    $expiryRaw = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($expiryRaw)->format('Y-m-d');
                }
                $dataRows[] = [
                    $sheet->getCell("A$row")->getValue(),   // emp_id
                    $sheet->getCell("B$row")->getValue(),   // insurance_no
                    $sheet->getCell("C$row")->getCalculatedValue(), // med_insurance
                    $expiryRaw,                              // medical_expiry
                    $sheet->getCell("E$row")->getValue(),   // medical_class
                ];
            }
        }
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'title' => 'File Error', 'message' => 'Could not read the uploaded file: ' . $e->getMessage(), 'type' => 'error']);
        exit;
    }

    if (count($dataRows) > 2000) {
        echo json_encode(['status' => 'error', 'title' => 'Too Many Rows', 'message' => 'Too many rows in one import (max 2000).', 'type' => 'error']);
        exit;
    }

    $allowedMedicalClasses = ['CLT', 'C', 'B', 'A', 'A+'];
    $created_by = $empid ?? '';

    $inserted = 0;
    $skipped = 0;
    $errors = [];

    foreach ($dataRows as $i => $rowData) {
        $rowNum = $i + 2; // +2 accounts for the header row and 1-based spreadsheet rows
        $emp_id = trim((string)($rowData[0] ?? ''));
        if ($emp_id === '') {
            continue; // fully blank row
        }

        $checkStmt = mysqli_prepare($conDB, "SELECT 1 FROM `employees` WHERE `emp_id` = ? LIMIT 1");
        mysqli_stmt_bind_param($checkStmt, "s", $emp_id);
        mysqli_stmt_execute($checkStmt);
        $exists = mysqli_stmt_get_result($checkStmt)->fetch_row();
        mysqli_stmt_close($checkStmt);
        if (!$exists) {
            $skipped++;
            $errors[] = ['row' => $rowNum, 'emp_id' => $emp_id, 'reason' => 'Employee ID not found'];
            continue;
        }

        $insurance_no = trim((string)($rowData[1] ?? ''));
        $insurance_no = $insurance_no !== '' ? $insurance_no : null;

        $med_insurance = (isset($rowData[2]) && $rowData[2] !== '') ? (float)$rowData[2] : null;

        $medical_expiry = trim((string)($rowData[3] ?? ''));
        $medical_expiry = (preg_match('/^\d{4}-\d{2}-\d{2}$/', $medical_expiry)) ? $medical_expiry : null;

        $medical_class = strtoupper(trim((string)($rowData[4] ?? '')));
        $medical_class = in_array($medical_class, $allowedMedicalClasses, true) ? $medical_class : null;

        if ($insurance_no === null && $med_insurance === null && $medical_expiry === null && $medical_class === null) {
            $skipped++;
            $errors[] = ['row' => $rowNum, 'emp_id' => $emp_id, 'reason' => 'Row has no data'];
            continue;
        }

        $result = addOrRenewMedicalInsurance($conDB, $emp_id, $insurance_no, $med_insurance, $medical_expiry, $medical_class, $created_by);
        if ($result['ok']) {
            $inserted++;
        } else {
            $skipped++;
            $errors[] = ['row' => $rowNum, 'emp_id' => $emp_id, 'reason' => $result['error'] ?: 'Database error'];
        }
    }

    if ($inserted > 0 && class_exists('ActivityLogger')) {
        ActivityLogger::logUpdate(
            'Employee',
            'employeeMedicalInsuranceHandler.php',
            'bulk',
            [],
            ['imported' => $inserted, 'skipped' => $skipped],
            "Bulk-imported medical insurance for {$inserted} employee(s)",
            'employee_medical_insurance'
        );
    }

    echo json_encode([
        'status' => 'success',
        'title' => 'Import Complete',
        'message' => "Imported {$inserted} record(s), skipped {$skipped}.",
        'type' => 'success',
        'inserted' => $inserted,
        'skipped' => $skipped,
        'errors' => $errors,
    ]);
    exit;
}

echo json_encode(['status' => 400, 'message' => 'Invalid AJAX type specified.']);
