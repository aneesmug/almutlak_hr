<?php
/**
 * Payroll Settings Handler
 * Handles the configurable payroll parameters (loan %, vacation/payroll thresholds,
 * overtime formula, deduction base components) and the deduction_types list.
 *
 * Every action is scoped to one of four setting_group values, each gated by its own
 * Special Access key so a plain employee can be granted just one tab (e.g. Loan
 * Settings) without exposing the rest of App Settings.
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/session_check.php';
require_once __DIR__ . '/helper_functions.php';
require_once __DIR__ . '/special_access_helper.php';

header('Content-Type: application/json');

const PAYROLL_SETTINGS_GROUP_KEYS = [
    'loan_settings' => 'manage_loan_settings',
    'vacation_payroll' => 'manage_vacation_payroll_settings',
    'overtime_settings' => 'manage_overtime_settings',
    'deduction_settings' => 'manage_deduction_settings',
    'salary_increment_settings' => 'manage_salary_increment_settings',
];

function payrollSettingsCanAccessGroup($conDB, $group) {
    global $empid, $user_role, $user_type, $is_system_admin;

    if (!array_key_exists($group, PAYROLL_SETTINGS_GROUP_KEYS)) {
        return false;
    }

    return ($is_system_admin ?? false)
        || user_has_special_access($conDB, $empid ?? '', PAYROLL_SETTINGS_GROUP_KEYS[$group], $user_role ?? '', $user_type ?? '', $is_system_admin ?? false);
}

function ensurePayrollParamSettings($conDB) {
    $defaults = [
        ['loan_max_pct_eos', '40', 'loan_settings', 'Maximum End-of-Service loan as % of calculated EOS benefit'],
        ['loan_max_pct_advance', '50', 'loan_settings', 'Maximum advance salary loan as % of total monthly salary'],
        ['loan_max_installments', '12', 'loan_settings', 'Maximum installments allowed for EOS / Housing loans'],
        ['loan_installment_edit_max', '60', 'loan_settings', 'Maximum installments allowed when HR edits an existing loan plan'],
        ['vacation_gosi_local_min_days', '20', 'vacation_payroll', 'Minimum Local Vacation days that triggers auto-GOSI-via-vacation payout'],
        ['vacation_payroll_dropout_days', '30', 'vacation_payroll', "Vacation days after which an employee is dropped from that month's payroll generation"],
        ['overtime_monthly_hours', '240', 'overtime_settings', 'Standard monthly working hours used as the divisor for overtime hourly rate'],
        ['overtime_extra_multiplier', '0.5', 'overtime_settings', 'Extra multiplier applied to the basic-salary hourly portion of overtime pay'],
        ['deduction_base_components', '["basic_salary","housing_allowance"]', 'deduction_settings', 'Salary components summed as the base for percentage-based deductions (e.g. GOSI)'],
        ['salary_increment_max_amount', '2000', 'salary_increment_settings', 'Maximum salary increment amount allowed per request'],
    ];

    foreach ($defaults as [$name, $value, $group, $description]) {
        $checkStmt = $conDB->prepare("SELECT id FROM app_settings WHERE setting_name = ? LIMIT 1");
        if (!$checkStmt) {
            continue;
        }
        $checkStmt->bind_param("s", $name);
        $checkStmt->execute();
        $exists = $checkStmt->get_result()->num_rows > 0;
        $checkStmt->close();

        if ($exists) {
            continue;
        }

        $insertStmt = $conDB->prepare("INSERT INTO app_settings (setting_name, setting_value, setting_group, description, input_type, options) VALUES (?, ?, ?, ?, 'text', NULL)");
        if (!$insertStmt) {
            continue;
        }
        $insertStmt->bind_param("ssss", $name, $value, $group, $description);
        $insertStmt->execute();
        $insertStmt->close();
    }
}

function ensureDeductionTypesTable($conDB) {
    $conDB->query("CREATE TABLE IF NOT EXISTS `deduction_types` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `name` varchar(100) NOT NULL,
        `counts_in_net` tinyint(1) NOT NULL DEFAULT 1,
        `status` tinyint(4) NOT NULL DEFAULT 1,
        PRIMARY KEY (`id`),
        UNIQUE KEY `name` (`name`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

    $seedNames = ['GOSI', 'Loan Installment', 'Joining Date Deduction', 'Absence', 'Late Deduction', 'Other'];
    $insertStmt = $conDB->prepare("INSERT IGNORE INTO deduction_types (name, counts_in_net, status) VALUES (?, 1, 1)");
    if ($insertStmt) {
        foreach ($seedNames as $seedName) {
            $insertStmt->bind_param("s", $seedName);
            $insertStmt->execute();
        }
        $insertStmt->close();
    }
}

$action = $_POST['action'] ?? '';

switch ($action) {
    case 'get_payroll_settings':
        getPayrollSettings($conDB);
        break;
    case 'update_payroll_settings':
        updatePayrollSettings($conDB);
        break;
    case 'get_deduction_types':
        getDeductionTypes($conDB);
        break;
    case 'add_deduction_type':
        addDeductionType($conDB);
        break;
    case 'update_deduction_type':
        updateDeductionType($conDB);
        break;
    case 'delete_deduction_type':
        deleteDeductionType($conDB);
        break;
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid action specified.']);
        break;
}

function getPayrollSettings($conDB) {
    $group = $_POST['group'] ?? '';

    if (!payrollSettingsCanAccessGroup($conDB, $group)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Access denied.']);
        return;
    }

    ensurePayrollParamSettings($conDB);

    $stmt = $conDB->prepare("SELECT setting_name, setting_value, description, input_type, options FROM app_settings WHERE setting_group = ? ORDER BY id");
    $stmt->bind_param("s", $group);
    $stmt->execute();
    $result = $stmt->get_result();

    $settings = [];
    while ($row = $result->fetch_assoc()) {
        $settings[] = $row;
    }
    $stmt->close();

    echo json_encode(['success' => true, 'settings' => $settings]);
}

function updatePayrollSettings($conDB) {
    $group = $_POST['group'] ?? '';

    if (!payrollSettingsCanAccessGroup($conDB, $group)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Access denied.']);
        return;
    }

    ensurePayrollParamSettings($conDB);

    // Whitelist: only setting_names that actually belong to this group may be written,
    // even if the client tampers with extra POST fields.
    $allowedStmt = $conDB->prepare("SELECT setting_name FROM app_settings WHERE setting_group = ?");
    $allowedStmt->bind_param("s", $group);
    $allowedStmt->execute();
    $allowedResult = $allowedStmt->get_result();
    $allowedNames = [];
    while ($row = $allowedResult->fetch_assoc()) {
        $allowedNames[$row['setting_name']] = true;
    }
    $allowedStmt->close();

    $fields = array_diff_key($_POST, ['action' => '', 'group' => '']);

    $conDB->begin_transaction();
    try {
        $updateStmt = $conDB->prepare("UPDATE app_settings SET setting_value = ? WHERE setting_name = ? AND setting_group = ?");
        if (!$updateStmt) {
            throw new Exception('Failed to prepare statement: ' . $conDB->error);
        }

        foreach ($fields as $settingName => $value) {
            if (!isset($allowedNames[$settingName])) {
                continue; // Silently ignore anything outside this group's whitelist.
            }

            if ($settingName === 'deduction_base_components') {
                $decoded = json_decode((string)$value, true);
                if (!is_array($decoded)) {
                    throw new Exception('Invalid deduction base components payload.');
                }
                $allowedComponents = [
                    'basic_salary', 'housing_allowance', 'transport_allowance', 'food_allowance',
                    'miscellaneous_allowance', 'cashier_allowance', 'fuel_allowance',
                    'telephone_allowance', 'other_allowance', 'guard_allowance',
                ];
                $clean = array_values(array_intersect($decoded, $allowedComponents));
                $value = json_encode($clean);
            } elseif (!is_numeric($value)) {
                throw new Exception("Invalid value for {$settingName}: must be numeric.");
            }

            $updateStmt->bind_param("ss", $value, $settingName);
            if (!$updateStmt->execute()) {
                throw new Exception('DB update failed for setting: ' . $settingName);
            }
        }
        $updateStmt->close();

        $conDB->commit();
        echo json_encode(['success' => true, 'message' => 'Settings updated successfully.']);
    } catch (Exception $e) {
        $conDB->rollback();
        echo json_encode(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()]);
    }
}

function deductionTypesCanAccess($conDB) {
    global $empid, $user_role, $user_type, $is_system_admin;
    return ($is_system_admin ?? false)
        || user_has_special_access($conDB, $empid ?? '', 'manage_deduction_settings', $user_role ?? '', $user_type ?? '', $is_system_admin ?? false);
}

function getDeductionTypes($conDB) {
    if (!deductionTypesCanAccess($conDB)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Access denied.']);
        return;
    }

    ensureDeductionTypesTable($conDB);

    $result = $conDB->query("SELECT id, name, counts_in_net, status FROM deduction_types ORDER BY name ASC");
    $types = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $types[] = $row;
        }
    }

    echo json_encode(['success' => true, 'deduction_types' => $types]);
}

function addDeductionType($conDB) {
    if (!deductionTypesCanAccess($conDB)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Access denied.']);
        return;
    }

    ensureDeductionTypesTable($conDB);

    $name = trim((string)($_POST['name'] ?? ''));
    $countsInNet = !empty($_POST['counts_in_net']) ? 1 : 0;

    if ($name === '') {
        echo json_encode(['success' => false, 'message' => 'Name is required.']);
        return;
    }

    $stmt = $conDB->prepare("INSERT INTO deduction_types (name, counts_in_net, status) VALUES (?, ?, 1)");
    $stmt->bind_param("si", $name, $countsInNet);
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'id' => $conDB->insert_id]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to add deduction type: ' . $conDB->error]);
    }
    $stmt->close();
}

function updateDeductionType($conDB) {
    if (!deductionTypesCanAccess($conDB)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Access denied.']);
        return;
    }

    $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
    $name = trim((string)($_POST['name'] ?? ''));
    $countsInNet = !empty($_POST['counts_in_net']) ? 1 : 0;
    $status = !empty($_POST['status']) ? 1 : 0;

    if (!$id || $name === '') {
        echo json_encode(['success' => false, 'message' => 'Invalid input.']);
        return;
    }

    $stmt = $conDB->prepare("UPDATE deduction_types SET name = ?, counts_in_net = ?, status = ? WHERE id = ?");
    $stmt->bind_param("siii", $name, $countsInNet, $status, $id);
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update deduction type: ' . $conDB->error]);
    }
    $stmt->close();
}

function deleteDeductionType($conDB) {
    if (!deductionTypesCanAccess($conDB)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Access denied.']);
        return;
    }

    $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'Invalid id.']);
        return;
    }

    $stmt = $conDB->prepare("DELETE FROM deduction_types WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete deduction type: ' . $conDB->error]);
    }
    $stmt->close();
}
