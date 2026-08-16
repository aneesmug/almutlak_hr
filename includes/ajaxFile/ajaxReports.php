<?php
require_once __DIR__ . '/../init.php';
require_once __DIR__ . '/../session_check.php';
require_once __DIR__ . '/../evaluation_acknowledgment_handler.php';
require_once __DIR__ . '/../report_permissions_helper.php';
require_once __DIR__ . '/../special_access_helper.php';
require_once __DIR__ . '/../eos_estimate_helper.php';
require_once __DIR__ . '/../balance_calculator.php';
// Include shared functions to ensure __() translation helper is available (robust path resolution)
($functionPath = (function() {
    $candidates = [
        __DIR__ . '/../functions.php',
        dirname(__DIR__, 2) . '/includes/functions.php',
        dirname(__DIR__, 2) . '/functions.php',
        // Removed root translation_functions.php to avoid redeclaration errors
    ];
    foreach ($candidates as $p) {
        if (file_exists($p)) return $p;
    }
    return null;
})()) && require_once $functionPath;

// Safe shim: ensure __() exists to avoid fatal errors
if (!function_exists('__')) {
    function __($key) { return $key; }
}

header('Content-Type: application/json');
// Avoid sending notices/warnings in JSON responses
ini_set('display_errors', '0');

// Check authorization
$can_see_reports_page = ['Administrator', 'GM', 'Auditor', 'HR_Senior_BP', 'HR_Operations', 'HR_Supervisor', 'Finance_Officer', 'DPT_Manager', 'HR_Manager', 'Finance_Manager', 'HR_Payroll', 'HR_Recruitment', 'IT_Team_Manager'];

if (!in_array($user_role, $can_see_reports_page) && $user_type !== 'administrator') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

$current_emp_id_for_reports = (string)($_SESSION['empid'] ?? ($empid ?? ''));
$allowed_report_types_for_current_user = get_allowed_report_types_for_user(
    $conDB,
    $current_emp_id_for_reports,
    $user_role ?? '',
    $user_type ?? '',
    !empty($is_system_admin)
);

// Handle AJAX actions
$action = isset($_POST['action']) ? $_POST['action'] : '';

// Get evaluation details endpoint
if ($action === 'getEvaluationDetails') {
    if (!in_array('evaluation', $allowed_report_types_for_current_user, true)) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized report access']);
        exit();
    }

    $evalId = isset($_POST['evalId']) ? intval($_POST['evalId']) : 0;
    
    if ($evalId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid evaluation ID']);
        exit();
    }
    
    $query = "SELECT 
        ev.*,
        e.name AS employee_name,
        e.emp_id AS employee_emp_id_display,
        e.iqama AS employee_iqama,
        em.name AS manager_name,
        ack_em.name AS acknowledged_by_name,
        d.dep_nme AS department,
        s.section_name AS section,
        j.job AS position,
        DATE_FORMAT(ev.manager_acknowledgment_date, '%Y-%m-%d %H:%i') AS acknowledgment_date,
        CASE 
            WHEN ev.total_score >= 90 THEN 'Excellent'
            WHEN ev.total_score >= 80 THEN 'Very Good'
            WHEN ev.total_score >= 70 THEN 'Good'
            WHEN ev.total_score >= 60 THEN 'Satisfactory'
            ELSE 'Needs Improvement'
        END AS rating
        FROM emp_evaluations ev
        LEFT JOIN employees e ON ev.employee_emp_id = e.emp_id
        LEFT JOIN employees em ON ev.manager_emp_id = em.emp_id
        LEFT JOIN employees ack_em ON ev.manager_acknowledged_by = ack_em.emp_id
        LEFT JOIN department d ON e.dept = d.id
        LEFT JOIN section s ON e.sectin_nme = s.id
        LEFT JOIN ac_jobs j ON e.actual_job = j.id
        WHERE ev.id = " . intval($evalId);
    
    $result = mysqli_query($conDB, $query);
    
    if (!$result) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . mysqli_error($conDB)]);
        exit();
    }
    if ($row = mysqli_fetch_assoc($result)) {

        if (isset($row['employee_name'])) {
            $row['employee_name'] = getDisplayName($row['employee_name']);
        }
        if (isset($row['observation'])) {
            $row['observation'] = getDisplayName($row['observation']);
        }
        if (isset($row['dept_name'])) {
            $row['dept_name'] = getDisplayName($row['dept_name']);
        }
        if (isset($row['employee_position'])) {
            $row['employee_position'] = getDisplayName($row['employee_position']);
        }
        if (isset($row['manager_name'])) {
            $row['manager_name'] = getDisplayName($row['manager_name']);
        }
        if (isset($row['department'])) {
            $row['department'] = getDisplayName($row['department']);
        }
        if (isset($row['section'])) {
            $row['section'] = getDisplayName($row['section']);
        }
        if (isset($row['position'])) {
            $row['position'] = getDisplayName($row['position']);
        }
        if (isset($row['rating'])) {
            $row['rating'] = getDisplayName($row['rating']);
        }

        echo json_encode(['success' => true, 'data' => $row]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Evaluation not found']);
    }
    exit();
}

// Get asset full activity/history endpoint
if ($action === 'getAssetActivity') {
    if (!in_array('assets', $allowed_report_types_for_current_user, true) && !in_array('assets_list', $allowed_report_types_for_current_user, true)) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized report access']);
        exit();
    }

    // Accept either assetItemId (preferred for per-item history) or assetId (fallback)
    $assetItemId = isset($_POST['assetItemId']) ? intval($_POST['assetItemId']) : 0;
    $assetId = isset($_POST['assetId']) ? intval($_POST['assetId']) : 0;

    if ($assetItemId > 0) {
        // Query specific asset item with its parent asset metadata and current holder
        $aiId = intval($assetItemId);
        $assetSql = "SELECT 
                        ai.id AS asset_item_id,
                        ai.tracking_id,
                        ai.serial_number,
                        ai.description AS item_description,
                        ai.status AS item_status,
                        ai.assigned_emp_id,
                        ai.assigned_date,
                        a.id AS asset_id,
                        a.name,
                        a.asset_type,
                        a.created_at,
                        a.clearance_dept_id,
                        d.dep_nme AS asset_department,
                        e.name AS assigned_emp_name,
                        ed.dep_nme AS assigned_emp_department
                    FROM asset_items ai
                    LEFT JOIN assets a ON a.id = ai.asset_id
                    LEFT JOIN department d ON d.id = a.clearance_dept_id
                    LEFT JOIN employees e ON e.emp_id = ai.assigned_emp_id
                    LEFT JOIN department ed ON ed.id = e.dept
                    WHERE ai.id = {$aiId}";
        $assetRes = mysqli_query($conDB, $assetSql);
        if (!$assetRes) {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . mysqli_error($conDB)]);
            exit();
        }
        $asset = mysqli_fetch_assoc($assetRes);
        if (!$asset) {
            echo json_encode(['success' => false, 'message' => 'Asset item not found']);
            exit();
        }

        
        // Apply display translations to asset data
        if (isset($asset['name'])) {
            $asset['name'] = getDisplayName($asset['name']);
        }
        if (isset($asset['asset_type'])) {
            $asset['asset_type'] = getDisplayName($asset['asset_type']);
        }
        if (isset($asset['asset_department'])) {
            $asset['asset_department'] = getDisplayName($asset['asset_department']);
        }
        if (isset($asset['assigned_emp_name'])) {
            $asset['assigned_emp_name'] = getDisplayName(parseName($asset['assigned_emp_name']));
        }
        if (isset($asset['assigned_emp_department'])) {
            $asset['assigned_emp_department'] = getDisplayName($asset['assigned_emp_department']);
        }

        // Build history based on asset_id + serial (tracking_id preferred)
        $serialForHistory = '';
        if (!empty($asset['tracking_id'])) {
            $serialForHistory = mysqli_real_escape_string($conDB, $asset['tracking_id']);
        } elseif (!empty($asset['serial_number'])) {
            $serialForHistory = mysqli_real_escape_string($conDB, $asset['serial_number']);
        }
        $histWhere = "ea.asset_id = " . intval($asset['asset_id']);
        if ($serialForHistory !== '') {
            $histWhere .= " AND ea.serial_number = '" . $serialForHistory . "'";
        }

        $histSql = "SELECT ea.id,
                           ea.serial_number,
                           ea.description,
                           ea.assigned_date,
                           ea.return_date,
                           ea.status,
                           ea.return_attachment,
                           e.emp_id,
                           e.name AS employee_name,
                           d.dep_nme AS employee_department
                    FROM employee_assets ea
                    LEFT JOIN employees e ON e.emp_id = ea.emp_id
                    LEFT JOIN department d ON d.id = e.dept
                    WHERE {$histWhere}
                    ORDER BY ea.assigned_date DESC, ea.id DESC";
        $histRes = mysqli_query($conDB, $histSql);
        if (!$histRes) {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . mysqli_error($conDB)]);
            exit();
        }
        $history = [];
        while ($row = mysqli_fetch_assoc($histRes)) {
            if (isset($row['employee_name'])) {
                $row['employee_name'] = getDisplayName($row['employee_name']);
            }
            if (isset($row['employee_department'])) {
                $row['employee_department'] = getDisplayName($row['employee_department']);
            }
            if (isset($row['status'])) {
                $row['status'] = getDisplayName($row['status']);
            }
            if (isset($row['dept_name'])) {
                $row['dept_name'] = getDisplayName($row['dept_name']);
            }
            $history[] = $row;
        }

        echo json_encode(['success' => true, 'data' => ['asset' => $asset, 'history' => $history]]);
        exit();
    }

    if ($assetId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid asset ID']);
        exit();
    }

    // Fallback: Fetch by asset (assets table)
    $assetSql = "SELECT a.id, a.name, a.asset_type, a.created_at, a.clearance_dept_id, d.dep_nme AS asset_department
                 FROM assets a
                 LEFT JOIN department d ON d.id = a.clearance_dept_id
                 WHERE a.id = " . intval($assetId);
    $assetRes = mysqli_query($conDB, $assetSql);
    if (!$assetRes) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . mysqli_error($conDB)]);
        exit();
    }
    $asset = mysqli_fetch_assoc($assetRes);
    if (!$asset) {
        echo json_encode(['success' => false, 'message' => 'Asset not found']);
        exit();
    }

    // Fetch assignment/return history scoped by asset
    $histSql = "SELECT ea.id,
                       ea.serial_number,
                       ea.description,
                       ea.assigned_date,
                       ea.return_date,
                       ea.status,
                       ea.return_attachment,
                       e.emp_id,
                       e.name AS employee_name,
                       d.dep_nme AS employee_department
                FROM employee_assets ea
                LEFT JOIN employees e ON e.emp_id = ea.emp_id
                LEFT JOIN department d ON d.id = e.dept
                WHERE ea.asset_id = " . intval($assetId) . "
                ORDER BY ea.assigned_date DESC, ea.id DESC";
    $histRes = mysqli_query($conDB, $histSql);
    if (!$histRes) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . mysqli_error($conDB)]);
        exit();
    }
    $history = [];
    while ($row = mysqli_fetch_assoc($histRes)) {
        $history[] = $row;
    }

    echo json_encode(['success' => true, 'data' => ['asset' => $asset, 'history' => $history]]);
    exit();
}

// Get asset items list for Select2 (id + text)
if ($action === 'getAssetItems') {
    $q = isset($_POST['q']) ? trim($_POST['q']) : '';

    $where = ['1=1'];
    if ($q !== '') {
        $q_safe = mysqli_real_escape_string($conDB, $q);
        $where[] = "(a.name LIKE '%$q_safe%' OR ai.tracking_id LIKE '%$q_safe%' OR ai.serial_number LIKE '%$q_safe%')";
    }
    $whereClause = implode(' AND ', $where);

    $sql = "SELECT ai.id,
                   a.name AS asset_name,
                   COALESCE(ai.tracking_id, ai.serial_number) AS serial_or_tracking,
                   ai.status
            FROM asset_items ai
            LEFT JOIN assets a ON a.id = ai.asset_id
            WHERE $whereClause
            ORDER BY a.name ASC, ai.tracking_id ASC, ai.id ASC
            LIMIT 200"; // cap results for UI responsiveness

    $res = mysqli_query($conDB, $sql);
    if (!$res) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . mysqli_error($conDB)]);
        exit();
    }

    $items = [];
    while ($row = mysqli_fetch_assoc($res)) {
        $labelParts = [];
        if (!empty($row['asset_name'])) { $labelParts[] = $row['asset_name']; }
        if (!empty($row['serial_or_tracking'])) { $labelParts[] = '[' . $row['serial_or_tracking'] . ']'; }
        if (!empty($row['status'])) { $labelParts[] = '(' . $row['status'] . ')'; }
        $items[] = [
            'id' => intval($row['id']),
            'text' => implode(' ', $labelParts)
        ];
    }

    echo json_encode(['success' => true, 'items' => $items]);
    exit();
}

// Get request parameters
$reportType = isset($_POST['reportType']) ? $_POST['reportType'] : '';
$columns = isset($_POST['columns']) ? $_POST['columns'] : [];
$departments = isset($_POST['departments']) ? $_POST['departments'] : [];
$companies = isset($_POST['companies']) ? $_POST['companies'] : [];
$countries = isset($_POST['countries']) ? $_POST['countries'] : [];
$dateFrom = isset($_POST['dateFrom']) ? $_POST['dateFrom'] : '';
$dateTo = isset($_POST['dateTo']) ? $_POST['dateTo'] : '';
$status = isset($_POST['status']) ? $_POST['status'] : '';
$vacationType = isset($_POST['vacationType']) ? trim($_POST['vacationType']) : '';
$employeeId = isset($_POST['employeeId']) ? trim($_POST['employeeId']) : '';
$hasFullAccess = in_array($user_role, [
    'Administrator',
    'GM',
    'Auditor',
    'Finance_Manager',
    'HR_Manager',
    'HR_Senior_BP',
    'HR_Operations',
    'HR_Supervisor',
    'HR_Recruitment',
    'HR_Payroll'
], true) || !empty($is_system_admin);
$userDept = $_SESSION['user_dept'] ?? ($user_dept ?? '');

// Normalize departments for filters (array of IDs as strings)
if (!is_array($departments)) {
    $departments = empty($departments) ? [] : [$departments];
}
if (!is_array($companies)) {
    $companies = empty($companies) ? [] : [$companies];
}
if (!is_array($countries)) {
    $countries = empty($countries) ? [] : [$countries];
}

if (empty($reportType) || empty($columns)) {
    echo json_encode(['success' => false, 'message' => 'Report type and columns are required']);
    exit();
}

if (!in_array($reportType, $allowed_report_types_for_current_user, true)) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized report access']);
    exit();
}

try {
    $data = [];
    $headers = [];
    
    switch ($reportType) {
        case 'employee':
            $result = generateEmployeeReport($conDB, $columns, $departments, $dateFrom, $dateTo, $status, $hasFullAccess, $userDept, $employeeId, $companies, $countries);
            break;
        case 'vacation':
            $result = generateVacationReport($conDB, $columns, $departments, $dateFrom, $dateTo, $status, $hasFullAccess, $userDept, $vacationType, $employeeId, $companies, $countries);
            break;
        case 'loan':
            $result = generateLoanReport($conDB, $columns, $departments, $dateFrom, $dateTo, $status, $hasFullAccess, $userDept, $employeeId, $companies, $countries);
            break;
        case 'salary_increment':
            $result = generateSalaryIncrementReport($conDB, $columns, $departments, $dateFrom, $dateTo, $status, $hasFullAccess, $userDept, $employeeId, $companies, $countries);
            break;
        case 'salary':
            $result = generateSalaryReport($conDB, $columns, $departments, $hasFullAccess, $userDept, $status, $employeeId, $companies, $countries);
            break;
        case 'payroll':
            $result = generatePayrollReport($conDB, $columns, $departments, $dateFrom, $dateTo, $hasFullAccess, $userDept, $status, $employeeId, $companies, $countries);
            break;
        case 'attendance':
            $result = generateAttendanceReport($conDB, $columns, $departments, $dateFrom, $dateTo, $hasFullAccess, $userDept, $employeeId, $companies, $countries);
            break;
        case 'document':
            $result = generateDocumentReport($conDB, $columns, $departments, $hasFullAccess, $userDept, $status, $employeeId, $companies, $countries);
            break;
        case 'assets':
            $result = generateAssetsReport($conDB, $columns, $departments, $dateFrom, $dateTo, $hasFullAccess, $userDept, $status, $employeeId, $companies, $countries);
            break;
        case 'assets_list':
            $result = generateAssetsListReport($conDB, $columns, $departments, $dateFrom, $dateTo, $hasFullAccess, $userDept, $status, $employeeId, $companies, $countries);
            break;
        case 'evaluation':
            // Check if user can acknowledge evaluations (managers only)
            if (!can_acknowledge_evaluations($user_type, $user_role)) {
                throw new Exception('Unauthorized: Only authorized managers can access evaluation reports');
            }
            $result = generateEvaluationReport($conDB, $columns, $departments, $dateFrom, $dateTo, $hasFullAccess, $userDept, $status, $employeeId, $companies, $countries);
            break;
        case 'resignation':
            $result = generateResignationReport($conDB, $columns, $departments, $dateFrom, $dateTo, $hasFullAccess, $userDept, $status, $employeeId, $companies, $countries);
            break;
        case 'eos':
            $result = generateEOSReport($conDB, $columns, $departments, $dateFrom, $dateTo, $hasFullAccess, $userDept, $employeeId, $companies, $countries);
            break;
        case 'terminated_employees':
            $result = generateExitSettlementReport($conDB, $columns, $departments, $dateFrom, $dateTo, $hasFullAccess, $userDept, $employeeId, $companies, $countries);
            break;
        case 'dept_comparison':
            $result = generateDepartmentComparisonReport($conDB, $columns, $departments, $hasFullAccess, $userDept);
            break;
        case 'country_company_comparison':
            $result = generateCountryCompanyComparisonReport($conDB, $columns, $departments, $companies, $hasFullAccess, $userDept);
            break;
        case 'ctc':
            $ctcHasAccess = ($is_system_admin ?? false)
                || user_has_special_access($conDB, $current_emp_id_for_reports, 'access_ctc_report', $user_role ?? '', $user_type ?? '', $is_system_admin ?? false);
            if (!$ctcHasAccess) {
                throw new Exception('Unauthorized: CTC report requires elevated access');
            }
            $result = generateCTCReport($conDB, $columns, $departments, $hasFullAccess, $userDept, $employeeId, $companies, $countries);
            break;
        case 'custom':
            $customTables = isset($_POST['customTables']) ? $_POST['customTables'] : [];
            $customDepartments = isset($_POST['customDepartments']) ? $_POST['customDepartments'] : [];
            $result = generateCustomReport($conDB, $columns, $customTables, $customDepartments, $dateFrom, $dateTo, $status);
            break;
        default:
            throw new Exception('Invalid report type');
    }
    
    echo json_encode([
        'success' => true,
        'data' => $result['data'],
        'headers' => $result['headers']
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

// Shared Company/Country filter for every employee-related report - mirrors the
// existing department filter pattern (explicit user-selected IDs, not the session-based
// access-control scoping getCompanyFilterSQL()/getDepartmentFilterSQL() already apply).
function applyEmployeeCompanyCountryFilter($conDB, array &$where, $companies, $countries, $empAlias = 'e') {
    if (!empty($companies) && !in_array('all', $companies, true)) {
        $vals = array_map(function ($c) use ($conDB) {
            return "'" . mysqli_real_escape_string($conDB, trim((string)$c)) . "'";
        }, $companies);
        $where[] = "`{$empAlias}`.`comp_no` IN (" . implode(',', $vals) . ")";
    }
    if (!empty($countries) && !in_array('all', $countries, true)) {
        $vals = array_map(function ($c) use ($conDB) {
            return "'" . mysqli_real_escape_string($conDB, trim((string)$c)) . "'";
        }, $countries);
        $where[] = "`{$empAlias}`.`country` IN (" . implode(',', $vals) . ")";
    }
}

// Helper function to get column label
function getColumnLabel($column) {
    // Prefer translation key by column id if available
    if (function_exists('__')) {
        $translated = __($column);
        if (!empty($translated) && $translated !== $column) {
            return $translated;
        }
    }
    // Fallback labels (English)
    $labels = [
        'name' => 'Name',
        'emp_id' => 'Employee ID',
        'iqama' => 'Iqama',
        'mobile' => 'Mobile',
        'email' => 'Email',
        'dept' => 'Department',
        'sectin_nme' => 'Section',
        'actual_job' => 'Job Title',
        'emptype' => 'Employee Type',
        'salary' => 'Salary',
        'joining_date' => 'Joining Date',
        'contract_expiry' => 'Contract Expiry',
        'country' => 'Nationality',
        'supervisor_id' => 'Supervisor',
        'vacation_days' => 'Vacation Days',
        'fly' => 'Flight Ticket',
        'bank_name' => 'Bank Name',
        'iban' => 'IBAN',
        'dob' => 'Date of Birth',
        'sex' => 'Gender',
        'blood_type' => 'Blood Type',
        'mar_status' => 'Marital Status',
        'gosi' => 'GOSI',
        'status' => 'Status',
        'emp_name' => 'Employee Name',
        'comp_no' => 'Company',
        'company_name' => 'Company Name',
        'company' => 'Company',
        'vac_type' => 'Vacation Type',
        'start_date' => 'Start Date',
        'return_date' => 'Return Date',
        'vacdays' => 'Days',
        'fly_type' => 'Flight Type',
        'permit_no' => 'Permit No',
        'current_status' => 'Status',
        'created_at' => 'Created Date',
        'loan_amount' => 'Loan Amount',
        'monthly_deduction' => 'Monthly Deduction',
        'end_date' => 'End Date',
        'loan_type' => 'Loan Type',
        'final_approved_amount' => 'Approved Amount',
        'total_payable' => 'Total Payable',
        'remaining_amount' => 'Remaining Amount',
        'request_inv_no' => 'Request ID',
        'increment_amount' => 'Increment Amount',
        'approved_amount' => 'Approved Amount',
        'evaluation_score' => 'Evaluation Score',
        'reason' => 'Reason',
        'basic' => 'Basic Salary',
        'housing' => 'Housing',
        'transport' => 'Transport',
        'food' => 'Food',
        'misc' => 'Misc',
        'fuel' => 'Fuel',
        'tel' => 'Telephone',
        'cashier' => 'Cashier',
        'other' => 'Other',
        'guard' => 'Guard',
        'total_salary' => 'Total Salary',
        'payroll_id' => 'Payroll ID',
        'month' => 'Month',
        'year' => 'Year',
        'total_employees' => 'Total Employees',
        'total_deductions' => 'Total Deductions',
        'net_salary' => 'Net Salary',
        'generated_by' => 'Generated By',
        'date' => 'Date',
        'check_in' => 'Check In',
        'check_out' => 'Check Out',
        'hours' => 'Hours',
        'document_type' => 'Document Type',
        'document_name' => 'Document Name',
        'upload_date' => 'Upload Date',
        'evaluation_date' => 'Evaluation Date',
        'score' => 'Score',
        'rating' => 'Rating',
        'evaluator' => 'Evaluator',
        'resignation_date' => 'Resignation Date',
        'last_working_day' => 'Last Working Day',
        'reason' => 'Reason',
        'termination_date' => 'Termination Date',
        'service_years' => 'Service Years',
        'service_duration' => 'Service Duration',
        'eos_amount' => 'EOS Amount',
        'vacation_balance' => 'Vacation Balance',
        'vacation_salary' => 'Vacation Salary',
        'total_settlement' => 'Total Settlement',
        'total_amount' => 'Total Amount',
        'basic_salary' => 'Basic Salary',
        'housing_allowance' => 'Housing Allowance',
        'transport_allowance' => 'Transport Allowance',
        'food_allowance' => 'Food Allowance',
        'miscellaneous_allowance' => 'Miscellaneous Allowance',
        'cashier_allowance' => 'Cashier Allowance',
        'fuel_allowance' => 'Fuel Allowance',
        'telephone_allowance' => 'Telephone Allowance',
        'other_allowance' => 'Other Allowance',
        'guard_allowance' => 'Guard Allowance',
        'total_benefits' => 'Total Benefits',
        'comp_name' => 'Company Name',
        'department' => 'Department',
        'active_employees' => 'Active Employees',
        'inactive_employees' => 'Inactive Employees',
        'avg_salary' => 'Average Salary',
        'pending_vacations' => 'Pending Vacations',
        'approved_vacations' => 'Approved Vacations',
        'active_loans' => 'Active Loans',
        'total_loan_amount' => 'Total Loan Amount',
        'avg_service_years' => 'Avg Service Years',
        'asset_name' => 'Asset Name',
        'asset_type' => 'Asset Type',
        'serial_number' => 'Serial Number',
        'asset_tag' => 'Asset Tag',
        'purchase_date' => 'Purchase Date',
        'asset_status' => 'Asset Status',
        'assigned_to' => 'Assigned To',
        'assignment_date' => 'Assignment Date',
        'return_date' => 'Return Date',
        'assignment_status' => 'Assignment Status',
        'return_notes' => 'Return Notes',
        'employee_dept' => 'Department'
        ,
        'current_annual_balance' => 'Current Annual Leave Balance',
        'leave_type' => 'Leave Type',
        'transaction_date' => 'Transaction Date',
        'transaction_days' => 'Transaction Days',
        'running_balance' => 'Running Balance',
        'request_inv_no' => 'Request No'
    ];
    return isset($labels[$column]) ? $labels[$column] : ucwords(str_replace('_', ' ', $column));
}

// Employee Report
function generateEmployeeReport($conDB, $columns, $departments, $dateFrom, $dateTo, $status, $hasFullAccess, $userDept, $employeeId = '', $companies = [], $countries = []) {
    global $is_rtl;
    // Map column IDs to actual database columns with proper joins
    $columnMap = [
        'actual_job' => 'j.job, j.job_ar',  // Select both
        'dept' => 'd.dep_nme',  // Select both
        'sectin_nme' => 's.section_name',
        'country' => 'c.name, c.name_ar',  // Select both
        'bank_name' => 'b.name, b.bank_name_ar',  // Select both
        'sex' => "CASE WHEN e.sex = '1' THEN 'Male' WHEN e.sex = '2' THEN 'Female' ELSE e.sex END",
        'payment_type' => "
            CASE WHEN e.payment_type = '1' THEN 'Cash' 
                WHEN e.payment_type = '2' THEN 'Bank' 
                WHEN e.payment_type = '3' THEN 'Hold' 
                ELSE e.payment_type END",
        'comp_no' => 'c2.comp_name, c2.comp_name_ar',  // Select both
        'emp_sup_type' => 'sponsorship.sponsor, sponsorship.sponsor_ar',
        'vac_period' => 'contract_period.period',
        'supervisor_id' => 'sup.name',
    ];
    
    // Build SELECT clause
    $selectCols = [];
    $needsContractExpiry = in_array('contract_expiry', $columns);
    $needsVacPeriodColumn = in_array('vac_period', $columns);
    
    // If contract expiry is needed, ensure we select joining_date and vac_period ID
    if ($needsContractExpiry) {
        $selectCols[] = 'e.joining_date AS joining_date';
        $selectCols[] = 'e.vac_period AS vac_period_id';  // For calculation
    }
    
    foreach ($columns as $col) {
        // Skip contract_expiry in SELECT - we'll calculate it later
        if ($col === 'contract_expiry') {
            continue;
        }
        
        // Skip joining_date if already added for contract expiry calculation
        if ($needsContractExpiry && $col === 'joining_date' && !$needsVacPeriodColumn) {
            continue;
        }
        
        if ($col === 'comp_no') {
            // Select both language variants so we can choose in PHP based on $is_rtl
            $selectCols[] = 'c2.comp_name AS comp_name';
            $selectCols[] = 'c2.comp_name_ar AS comp_name_ar';
        } elseif ($col === 'actual_job') {
            $selectCols[] = 'j.job AS actual_job';
            $selectCols[] = 'j.job_ar AS actual_job_ar';
        } elseif ($col == 'dep_nme') {
            $selectCols[] = 'd.dep_nme AS dep_nme';
            $selectCols[] = 'd.dep_nme_ar AS dep_nme_ar';
        } elseif ($col === 'country') {
            $selectCols[] = 'c.name AS country';
            $selectCols[] = 'c.name_ar AS country_ar';
        } elseif ($col === 'bank_name') {
            $selectCols[] = 'b.name AS bank_name';
            $selectCols[] = 'b.bank_name_ar AS bank_name_ar';
        } elseif ($col === 'emp_sup_type') {
            $selectCols[] = 'sponsorship.sponsor AS emp_sup_type';
            $selectCols[] = 'sponsorship.sponsor_ar AS emp_sup_type_ar';
        } elseif ($col === 'c_email') {
            $selectCols[] = 'COALESCE(al.email, e.c_email) AS c_email';
        } elseif (isset($columnMap[$col])) {
            $selectCols[] = $columnMap[$col] . ' AS ' . $col;
        } else {
            $selectCols[] = 'e.' . $col;
        }
    }
    $selectClause = implode(', ', $selectCols);
    
    // Build WHERE clause - include all employees for complete leave transaction reporting
    $where = ['1=1'];

    // Normalize explicitly selected department filters from UI.
    $selectedDepartments = [];
    if (is_array($departments)) {
        foreach ($departments as $dept) {
            $dept = trim((string)$dept);
            if ($dept === '' || strtolower($dept) === 'all' || strtolower($dept) === 'none') {
                continue;
            }
            $selectedDepartments[] = $dept;
        }
        $selectedDepartments = array_values(array_unique($selectedDepartments));
    }
    
    // NOTE: Department and company filtering is handled by getEmployeeFilterSQL, getDepartmentFilterSQL, getCompanyFilterSQL
    // Do NOT add hard-coded department restrictions here as it conflicts with allowed_employees access control
    // The filter functions handle the OR logic: (dept IN allowed_depts OR emp_id IN allowed_emps)
    
    // Only apply manual department filter if BOTH:
    // 1. User doesn't have full access
    // 2. User has no special employee/department/company restrictions configured
    $hasSpecialRestrictions = !empty($_SESSION['allowed_employees_array']) || 
                            !empty($_SESSION['allowed_departments_array']) || 
                            !empty($_SESSION['allowed_companies_array']);
    
    if (!empty($selectedDepartments)) {
        $deptList = array_map(function($d) use ($conDB) {
            return "'" . mysqli_real_escape_string($conDB, $d) . "'";
        }, $selectedDepartments);
        $where[] = "e.dept IN (" . implode(',', $deptList) . ")";
    } elseif (!$hasFullAccess && !$hasSpecialRestrictions && !empty($userDept)) {
        // Only apply hard-coded department filter if no access controls are configured
        $where[] = "e.dept = '" . mysqli_real_escape_string($conDB, $userDept) . "'";
    }
    
    // Company filter - restrict by accessible companies
    $company_filter = getCompanyFilterSQL('e.comp_no', true);
    if (!empty($company_filter)) {
        $where[] = substr($company_filter, 5); // Remove " AND " prefix for use in WHERE array
    }
    
    // Department filter - restrict by accessible departments
    // NOTE: For reports, we use company and department filters, not individual employee ID filter
    // This allows employees to see all colleagues in their department/company
    $department_filter = getDepartmentFilterSQL('e.dept', true);
    if (!empty($department_filter)) {
        $where[] = substr($department_filter, 5); // Remove " AND " prefix for use in WHERE array
    }

    
    // Date filter
    if (!empty($dateFrom)) {
        $where[] = "e.joining_date >= '" . mysqli_real_escape_string($conDB, $dateFrom) . "'";
    }
    if (!empty($dateTo)) {
        $where[] = "e.joining_date <= '" . mysqli_real_escape_string($conDB, $dateTo) . "'";
    }
    
    // Status filter
    if ($status !== '') {
        $where[] = "e.status = '" . mysqli_real_escape_string($conDB, $status) . "'";
    }

    // Employee filter
    if (!empty($employeeId)) {
        $where[] = "e.emp_id = '" . mysqli_real_escape_string($conDB, $employeeId) . "'";
    }
    
    applyEmployeeCompanyCountryFilter($conDB, $where, $companies, $countries);
    $whereClause = implode(' AND ', $where);
    
    // Build and execute query
    $sql = "SELECT $selectClause 
            FROM employees e
            LEFT JOIN ac_jobs j ON e.actual_job = j.id
            LEFT JOIN department d ON e.dept = d.id
            LEFT JOIN section s ON e.sectin_nme = s.id
            LEFT JOIN countries c ON e.country = c.id
            LEFT JOIN bank_list b ON e.bank_name = b.id
            LEFT JOIN companies c2 ON e.comp_no = c2.comp_id
            LEFT JOIN sponsorship ON e.emp_sup_type = sponsorship.id
            LEFT JOIN contract_period ON e.vac_period = contract_period.id
            LEFT JOIN admin_login al ON e.emp_id = al.emp_id
            LEFT JOIN employees sup ON e.supervisor_id = sup.emp_id
            WHERE $whereClause
            ORDER BY e.name";
    
    $query = mysqli_query($conDB, $sql);
    if (!$query) {
        throw new Exception('Employee query error: ' . mysqli_error($conDB));
    }
    
    $data = [];
    $headers = [];
    
    // Get headers
    foreach ($columns as $col) {
        $headers[] = getColumnLabel($col);
    }
    
    // Get data
    while ($row = mysqli_fetch_assoc($query)) {
        // Process specific fields
        if (isset($row['name'])) {
            $row['name'] = getDisplayName(parseName($row['name']));
        }
        if (isset($row['status'])) {
            $row['status'] = $row['status'] == 1 ? __('active') : __('inactive');
        }
        if (isset($row['fly'])) {
            $row['fly'] = $row['fly'] == 1 ? __('yes') : __('no');
        }
        if (isset($row['mar_status'])) {
            $row['mar_status'] = $is_rtl ?? false ? __('married') : __('single');
        }
        if (isset($row['comp_name']) || isset($row['comp_name_ar'])) {
            $row['comp_no'] = $is_rtl ?? false ? $row['comp_name_ar'] : $row['comp_name'];
        }
        if (isset($row['dept'])) {
            $row['dept'] = getDisplayName($row['dept']);
        }
        if (isset($row['sectin_nme'])) {
            $row['sectin_nme'] = getDisplayName($row['sectin_nme']);
        }
        if (isset($row['actual_job'])) {
            $row['actual_job'] = $is_rtl ?? false ? $row['actual_job_ar'] : $row['actual_job'];
        }
        if (isset($row['vac_period'])) {
            $row['vac_period'] = translateContractPeriod($row['vac_period']);
        }
        if (isset($row['emptype'])) {
            $row['emptype'] = __(strtolower($row['emptype']));
        }
        if (isset($row['sex'])) {
            $row['sex'] = __(strtolower($row['sex']));
        }
        if (isset($row['country'])) {
            $row['country'] = $is_rtl ?? false ? $row['country_ar'] : $row['country'];
        }
        if (isset($row['bank_name'])) {
            $row['bank_name'] = $is_rtl ?? false ? $row['bank_name_ar'] : $row['bank_name'];
        }
        if (isset($row['emp_sup_type'])) {
            $row['emp_sup_type'] = $is_rtl ?? false ? $row['emp_sup_type_ar'] : $row['emp_sup_type'];
        }
        if (isset($row['payment_type'])) {
            $row['payment_type'] = __(strtolower($row['payment_type']));
        } 
        if (isset($row['emg_name'])) {
            $row['emg_name'] = getDisplayName($row['emg_name']);
        }
        if (isset($row['address'])) {
            $row['address'] = getDisplayName($row['address']);
        }
        if (array_key_exists('supervisor_id', $row)) {
            $row['supervisor_id'] = !empty($row['supervisor_id']) ? getDisplayName(parseName($row['supervisor_id'])) : 'N/A';
        }

        // Calculate contract expiry if requested using the helper function
        if ($needsContractExpiry) {
            if (isset($row['joining_date']) && isset($row['vac_period_id'])) {
                $contractExpiry = computeContractExpiry($row['joining_date'], (int)$row['vac_period_id']);
                $row['contract_expiry'] = $contractExpiry ?: 'N/A';
            } else {
                $row['contract_expiry'] = 'N/A';
            }
            
            // Remove the internal ID field
            unset($row['vac_period_id']);
            
            // Remove the temporary fields if they weren't originally requested
            if (!in_array('joining_date', $columns)) {
                unset($row['joining_date']);
            }
        }
        
        $data[] = $row;
    }
    
    return ['data' => $data, 'headers' => $headers];
}

// Vacation Report
function generateVacationReport($conDB, $columns, $departments, $dateFrom, $dateTo, $status, $hasFullAccess, $userDept, $vacationType = '', $employeeId = '', $companies = [], $countries = []) {
    global $is_rtl;
    
    // Build SELECT clause for transaction-level leave balance reporting
    $selectCols = [
        'v.id',
        'v.emp_id',
        'v.request_inv_no',
        'v.vac_type',
        'v.fly_type',
        'v.start_date',
        'v.return_date',
        'v.vacdays',
        'v.current_status',
        'v.review',
        'v.created_at',
        'e.name AS emp_name',
        'd.dep_nme AS dept',
        'c2.comp_name AS comp_name',
        'c2.comp_name_ar AS comp_name_ar'
    ];
    $selectClause = implode(', ', $selectCols);
    
    // Build WHERE clause - include all employees, but hide legacy-imported rows from the generated report.
    $where = ["COALESCE(v.request_inv_no, '') NOT LIKE 'LEGACY-%'"];
    
    // NOTE: Department and company filtering is handled by getEmployeeFilterSQL, getDepartmentFilterSQL, getCompanyFilterSQL
    // Do NOT add hard-coded department restrictions here as it conflicts with allowed_employees access control
    // The filter functions handle the OR logic: (dept IN allowed_depts OR emp_id IN allowed_emps)
    
    // Only apply manual department filter if BOTH:
    // 1. User doesn't have full access
    // 2. User has no special employee/department/company restrictions configured
    $hasSpecialRestrictions = !empty($_SESSION['allowed_employees_array']) || 
                            !empty($_SESSION['allowed_departments_array']) || 
                            !empty($_SESSION['allowed_companies_array']);
    
    if (!$hasFullAccess && !$hasSpecialRestrictions && !empty($userDept)) {
        // Only apply hard-coded department filter if no access controls are configured
        $where[] = "e.dept = '" . mysqli_real_escape_string($conDB, $userDept) . "'";
    } elseif (!$hasFullAccess && !$hasSpecialRestrictions && !empty($departments)) {
        // Or if departments were explicitly requested
        $deptList = array_map(function($d) use ($conDB) { return "'" . mysqli_real_escape_string($conDB, $d) . "'"; }, $departments);
        $where[] = "e.dept IN (" . implode(',', $deptList) . ")";
    }
    
    // Company filter - restrict by accessible companies
    $company_filter = getCompanyFilterSQL('e.comp_no', true);
    if (!empty($company_filter)) {
        $where[] = substr($company_filter, 5); // Remove " AND " prefix for use in WHERE array
    }
    
    // Department filter - restrict by accessible departments
    // NOTE: For reports, we use company and department filters, not individual employee ID filter
    // This allows employees to see all colleagues in their department/company
    $department_filter = getDepartmentFilterSQL('e.dept', true);
    if (!empty($department_filter)) {
        $where[] = substr($department_filter, 5); // Remove " AND " prefix for use in WHERE array
    }
    if (!empty($dateFrom)) {
        $dateFromEsc = mysqli_real_escape_string($conDB, $dateFrom);
        $where[] = "COALESCE(v.start_date, DATE(v.created_at)) >= '" . $dateFromEsc . "'";
    }
    if (!empty($dateTo)) {
        $dateToEsc = mysqli_real_escape_string($conDB, $dateTo);
        $where[] = "COALESCE(v.start_date, DATE(v.created_at)) <= '" . $dateToEsc . "'";
    }
    
    // Status filter
    if ($status !== '') {
        $where[] = "v.current_status = '" . mysqli_real_escape_string($conDB, $status) . "'";
    }

    // Vacation type filter
    if (!empty($vacationType)) {
        $where[] = "v.vac_type = '" . mysqli_real_escape_string($conDB, $vacationType) . "'";
    }

    // Employee filter
    if (!empty($employeeId)) {
        $where[] = "v.emp_id = '" . mysqli_real_escape_string($conDB, $employeeId) . "'";
    }
    
    applyEmployeeCompanyCountryFilter($conDB, $where, $companies, $countries);
    $whereClause = implode(' AND ', $where);
    
    // Build and execute query
        $sql = "SELECT $selectClause 
            FROM emp_vacation v
            INNER JOIN employees e ON v.emp_id = e.emp_id
            LEFT JOIN department d ON e.dept = d.id
            LEFT JOIN companies c2 ON e.comp_no = c2.comp_id
            WHERE $whereClause
            ORDER BY v.emp_id ASC, COALESCE(v.start_date, DATE(v.created_at)) ASC, v.id ASC";
    
    $query = mysqli_query($conDB, $sql);
    if (!$query) {
        throw new Exception('Vacation query error: ' . mysqli_error($conDB));
    }
    
    $headers = [];
    foreach ($columns as $col) {
        $headers[] = getColumnLabel($col);
    }

    // Group transactions by employee for running balance calculation
    $rowsByEmployee = [];
    $employeeIds = [];
    while ($row = mysqli_fetch_assoc($query)) {
        $empId = $row['emp_id'];
        if (!isset($rowsByEmployee[$empId])) {
            $rowsByEmployee[$empId] = [];
            $employeeIds[] = $empId;
        }
        $rowsByEmployee[$empId][] = $row;
    }

    // Fetch current annual leave balance per employee
    $currentBalanceMap = [];
    if (!empty($employeeIds)) {
        $empIdsEsc = array_map(function($id) use ($conDB) {
            return "'" . mysqli_real_escape_string($conDB, (string)$id) . "'";
        }, $employeeIds);

        $balanceSql = "SELECT b.emp_id, b.available_balance
                       FROM emp_vacation_balance b
                       INNER JOIN (
                           SELECT emp_id, MAX(id) AS max_id
                           FROM emp_vacation_balance
                           WHERE emp_id IN (" . implode(',', $empIdsEsc) . ")
                           GROUP BY emp_id
                       ) latest ON latest.max_id = b.id";
        $balanceQuery = mysqli_query($conDB, $balanceSql);
        if ($balanceQuery) {
            while ($balanceRow = mysqli_fetch_assoc($balanceQuery)) {
                $currentBalanceMap[$balanceRow['emp_id']] = (float)($balanceRow['available_balance'] ?? 0);
            }
            mysqli_free_result($balanceQuery);
        }
    }

    $data = [];

    // Helper to build user-facing leave type
    $toLeaveType = function($vacTypeRaw, $flyTypeRaw) {
        $vacType = strtolower(trim((string)$vacTypeRaw));
        $flyType = strtolower(trim((string)$flyTypeRaw));

        if ($vacType === 'fly' && $flyType === 'annual') {
            return 'Annual';
        }
        if ($vacType === 'fly' && $flyType === 'emergency') {
            return 'Emergency';
        }
        if ($vacType === 'local vacation' && $flyType === 'annual') {
            return 'Local Annual';
        }
        if ($vacType === 'local vacation' && $flyType === 'emergency') {
            return 'Local Emergency';
        }
        if ($vacType === 'encashed') {
            return 'Encashed';
        }
        return ucfirst((string)$vacTypeRaw);
    };

    // Helper: days that reduce annual leave balance for running-balance math
    $getDeductedDays = function($txRow) {
        $status = strtolower(trim((string)($txRow['current_status'] ?? '')));
        if (!in_array($status, ['approved', 'completed'], true)) {
            return 0.0;
        }

        $vacType = strtolower(trim((string)($txRow['vac_type'] ?? '')));
        $flyType = strtolower(trim((string)($txRow['fly_type'] ?? '')));
        $days = (float)($txRow['vacdays'] ?? 0);

        if ($days <= 0) {
            return 0.0;
        }

        // Annual leave-impacting transactions
        if ($vacType === 'encashed') {
            return $days;
        }
        if ($vacType === 'local vacation') {
            return $days;
        }
        if ($vacType === 'fly' && $flyType === 'annual') {
            return $days;
        }

        // Emergency and other leave types are displayed but do not reduce annual balance.
        return 0.0;
    };

    foreach ($rowsByEmployee as $empId => $empTransactions) {
        $currentBalance = isset($currentBalanceMap[$empId]) ? (float)$currentBalanceMap[$empId] : 0.0;

        $totalDeducted = 0.0;
        foreach ($empTransactions as $tx) {
            $totalDeducted += $getDeductedDays($tx);
        }

        // Opening balance before the first listed transaction.
        $runningBalance = $currentBalance + $totalDeducted;

        foreach ($empTransactions as $txIndex => $row) {
            $deductedDays = $getDeductedDays($row);
            $runningBalance -= $deductedDays;
            $showSummaryColumns = ($txIndex === 0);

            $rowData = [];
            foreach ($columns as $col) {
                switch ($col) {
                    case 'emp_id':
                        $rowData[$col] = $showSummaryColumns ? $row['emp_id'] : '';
                        break;
                    case 'emp_name':
                        $rowData[$col] = $showSummaryColumns ? getDisplayName(parseName((string)($row['emp_name'] ?? ''))) : '';
                        break;
                    case 'dept':
                        $rowData[$col] = $showSummaryColumns ? getDisplayName((string)($row['dept'] ?? '')) : '';
                        break;
                    case 'comp_no':
                        $rowData[$col] = $showSummaryColumns ? (($is_rtl ?? false) ? ($row['comp_name_ar'] ?? '') : ($row['comp_name'] ?? '')) : '';
                        break;
                    case 'current_annual_balance':
                        $rowData[$col] = $showSummaryColumns ? number_format($currentBalance, 2) : '';
                        break;
                    case 'leave_type':
                        $rowData[$col] = getDisplayName($toLeaveType($row['vac_type'] ?? '', $row['fly_type'] ?? ''));
                        break;
                    case 'transaction_date':
                        $rowData[$col] = !empty($row['start_date']) ? $row['start_date'] : substr((string)($row['created_at'] ?? ''), 0, 10);
                        break;
                    case 'transaction_days':
                        $rowData[$col] = number_format((float)($row['vacdays'] ?? 0), 2);
                        break;
                    case 'running_balance':
                        $rowData[$col] = number_format($runningBalance, 2);
                        break;
                    case 'request_inv_no':
                        $rowData[$col] = $row['request_inv_no'] ?? '';
                        break;
                    case 'vac_type':
                        $rowData[$col] = getDisplayName((string)($row['vac_type'] ?? ''));
                        break;
                    case 'fly_type':
                        $rowData[$col] = getDisplayName((string)($row['fly_type'] ?? ''));
                        break;
                    case 'current_status':
                        $rowData[$col] = getDisplayName((string)($row['current_status'] ?? ''));
                        break;
                    case 'start_date':
                    case 'return_date':
                    case 'permit_no':
                    case 'created_at':
                        $rowData[$col] = $row[$col] ?? '';
                        break;
                    case 'vacdays':
                        $rowData[$col] = number_format((float)($row['vacdays'] ?? 0), 2);
                        break;
                    default:
                        $rowData[$col] = isset($row[$col]) ? $row[$col] : '';
                        break;
                }
            }

            $data[] = $rowData;
        }
    }

    return ['data' => $data, 'headers' => $headers];
}

// Loan Report
function generateLoanReport($conDB, $columns, $departments, $dateFrom, $dateTo, $status, $hasFullAccess, $userDept, $employeeId = '', $companies = [], $countries = []) {
    // Add column mappings
    $columnMap = [
        'dept' => 'd.dep_nme',
        'sex' => "CASE WHEN e.sex = '1' THEN 'Male' WHEN e.sex = '2' THEN 'Female' ELSE e.sex END",
        'country' => 'c.name',
        'bank_name' => 'b.name',
        'sectin_nme' => 's.section_name'
    ];

    // Build SELECT clause
    $selectCols = ['l.id'];
    
    foreach ($columns as $col) {
        if ($col == 'emp_name') {
            $selectCols[] = 'e.name AS emp_name';
        } elseif ($col == 'emp_id') {
            $selectCols[] = 'l.emp_id';
        } elseif ($col == 'dept') {
            $selectCols[] = 'd.dep_nme AS dept';
        } elseif ($col == 'bank_name') {
            $selectCols[] = 'b.name AS bank_name';
        } elseif ($col == 'remaining_amount') {
            $selectCols[] = '(l.final_approved_amount - COALESCE(SUM(lp.amount), 0)) AS remaining_amount';
        } else {
            $selectCols[] = 'l.' . $col;
        }
    }
    $selectClause = implode(', ', $selectCols);
    
    // Build WHERE clause - only active employees
    $where = ['e.status = 1'];
    
    // Department fallback filter (only when no explicit scope restrictions are configured)
    $hasSpecialRestrictions = !empty($_SESSION['allowed_employees_array']) ||
                            !empty($_SESSION['allowed_departments_array']) ||
                            !empty($_SESSION['allowed_companies_array']);

    if (!$hasFullAccess && !$hasSpecialRestrictions && !empty($userDept)) {
        $where[] = "e.dept = '" . mysqli_real_escape_string($conDB, $userDept) . "'";
    } elseif (!$hasFullAccess && !$hasSpecialRestrictions && !empty($departments)) {
        $deptList = array_map(function($d) use ($conDB) { return "'" . mysqli_real_escape_string($conDB, $d) . "'"; }, $departments);
        $where[] = "e.dept IN (" . implode(',', $deptList) . ")";
    }
    
    // Company filter - restrict by accessible companies
    $company_filter = getCompanyFilterSQL('e.comp_no', true);
    if (!empty($company_filter)) {
        $where[] = substr($company_filter, 5); // Remove " AND " prefix for use in WHERE array
    }

    // Department filter - restrict by accessible departments
    // NOTE: For reports, we use company and department filters, not individual employee ID filter
    // This allows employees to see all colleagues in their department/company
    $department_filter = getDepartmentFilterSQL('e.dept', true);
    if (!empty($department_filter)) {
        $where[] = substr($department_filter, 5); // Remove " AND " prefix for use in WHERE array
    }
    
    // Date filter
    if (!empty($dateFrom)) {
        $where[] = "l.start_date >= '" . mysqli_real_escape_string($conDB, $dateFrom) . "'";
    }
    if (!empty($dateTo)) {
        $where[] = "l.start_date <= '" . mysqli_real_escape_string($conDB, $dateTo) . "'";
    }
    
    // Status filter
    if ($status !== '') {
        $where[] = "l.status = '" . mysqli_real_escape_string($conDB, $status) . "'";
    }

    // Employee filter
    if (!empty($employeeId)) {
        $where[] = "l.emp_id = '" . mysqli_real_escape_string($conDB, $employeeId) . "'";
    }
    
    applyEmployeeCompanyCountryFilter($conDB, $where, $companies, $countries);
    $whereClause = implode(' AND ', $where);
    
    // Build and execute query
    $sql = "SELECT $selectClause 
            FROM emp_loan l
            INNER JOIN employees e ON l.emp_id = e.emp_id
            LEFT JOIN department d ON e.dept = d.id
            LEFT JOIN bank_list b ON e.bank_name = b.id
            LEFT JOIN emp_loan_payments lp ON l.id = lp.loan_id
            LEFT JOIN companies c2 ON e.comp_no = c2.comp_id
            LEFT JOIN sponsorship ON e.emp_sup_type = sponsorship.id
            LEFT JOIN contract_period ON e.vac_period = contract_period.id
            WHERE $whereClause
            GROUP BY l.id
            ORDER BY l.start_date DESC";
    
    $query = mysqli_query($conDB, $sql);
    if (!$query) {
        throw new Exception('Loan query error: ' . mysqli_error($conDB));
    }
    
    $data = [];
    $headers = [];
    
    // Get headers
    foreach ($columns as $col) {
        $headers[] = getColumnLabel($col);
    }
    
    // Get data
    while ($row = mysqli_fetch_assoc($query)) {
        unset($row['id']);

        if (isset($row['emp_name'])) {
            $row['emp_name'] = getDisplayName(parseName($row['emp_name']));
        }
        if (isset($row['dept'])) {
            $row['dept'] = getDisplayName($row['dept']);
        }
        if (isset($row['loan_type'])) {
            $row['loan_type'] = getDisplayName(str_replace('_', ' ', $row['loan_type']));
        }
        if (isset($row['status'])) {
            $row['status'] = getDisplayName($row['status']);
        }

        $data[] = $row;
    }
    
    return ['data' => $data, 'headers' => $headers];
}

// Salary Increment Report
function generateSalaryIncrementReport($conDB, $columns, $departments, $dateFrom, $dateTo, $status, $hasFullAccess, $userDept, $employeeId = '', $companies = [], $countries = []) {
    // Build SELECT clause
    $selectCols = ['si.id'];

    foreach ($columns as $col) {
        if ($col == 'emp_name') {
            $selectCols[] = 'e.name AS emp_name';
        } elseif ($col == 'emp_id') {
            $selectCols[] = 'si.emp_id';
        } elseif ($col == 'dept') {
            $selectCols[] = 'd.dep_nme AS dept';
        } elseif ($col == 'status') {
            $selectCols[] = 'si.current_status AS status';
        } else {
            $selectCols[] = 'si.' . $col;
        }
    }
    $selectClause = implode(', ', $selectCols);

    // Build WHERE clause - only active employees
    $where = ['e.status = 1'];

    // Department fallback filter (only when no explicit scope restrictions are configured)
    $hasSpecialRestrictions = !empty($_SESSION['allowed_employees_array']) ||
                            !empty($_SESSION['allowed_departments_array']) ||
                            !empty($_SESSION['allowed_companies_array']);

    if (!$hasFullAccess && !$hasSpecialRestrictions && !empty($userDept)) {
        $where[] = "e.dept = '" . mysqli_real_escape_string($conDB, $userDept) . "'";
    } elseif (!$hasFullAccess && !$hasSpecialRestrictions && !empty($departments)) {
        $deptList = array_map(function($d) use ($conDB) { return "'" . mysqli_real_escape_string($conDB, $d) . "'"; }, $departments);
        $where[] = "e.dept IN (" . implode(',', $deptList) . ")";
    }

    // Company filter - restrict by accessible companies
    $company_filter = getCompanyFilterSQL('e.comp_no', true);
    if (!empty($company_filter)) {
        $where[] = substr($company_filter, 5);
    }

    // Department filter - restrict by accessible departments
    $department_filter = getDepartmentFilterSQL('e.dept', true);
    if (!empty($department_filter)) {
        $where[] = substr($department_filter, 5);
    }

    // Date filter (applied date)
    if (!empty($dateFrom)) {
        $where[] = "si.created_at >= '" . mysqli_real_escape_string($conDB, $dateFrom) . "'";
    }
    if (!empty($dateTo)) {
        $where[] = "si.created_at <= '" . mysqli_real_escape_string($conDB, $dateTo) . " 23:59:59'";
    }

    // Status filter
    if ($status !== '') {
        $where[] = "si.current_status = '" . mysqli_real_escape_string($conDB, $status) . "'";
    }

    // Employee filter
    if (!empty($employeeId)) {
        $where[] = "si.emp_id = '" . mysqli_real_escape_string($conDB, $employeeId) . "'";
    }

    applyEmployeeCompanyCountryFilter($conDB, $where, $companies, $countries);
    $whereClause = implode(' AND ', $where);

    // Build and execute query
    $sql = "SELECT $selectClause
            FROM emp_salary_increment si
            INNER JOIN employees e ON si.emp_id = e.emp_id
            LEFT JOIN department d ON e.dept = d.id
            WHERE $whereClause
            ORDER BY si.created_at DESC";

    $query = mysqli_query($conDB, $sql);
    if (!$query) {
        throw new Exception('Salary increment query error: ' . mysqli_error($conDB));
    }

    $data = [];
    $headers = [];

    foreach ($columns as $col) {
        $headers[] = getColumnLabel($col);
    }

    while ($row = mysqli_fetch_assoc($query)) {
        unset($row['id']);

        if (isset($row['emp_name'])) {
            $row['emp_name'] = getDisplayName(parseName($row['emp_name']));
        }
        if (isset($row['dept'])) {
            $row['dept'] = getDisplayName($row['dept']);
        }
        if (isset($row['status'])) {
            $row['status'] = getDisplayName(str_replace('_', ' ', $row['status']));
        }

        $data[] = $row;
    }

    return ['data' => $data, 'headers' => $headers];
}

// Salary Report
function generateSalaryReport($conDB, $columns, $departments, $hasFullAccess, $userDept, $status = '', $employeeId = '', $companies = [], $countries = []) {
    // Build SELECT clause
    $selectCols = [];
    
    foreach ($columns as $col) {
        if ($col == 'emp_name') {
            $selectCols[] = 'e.name AS emp_name';
        } elseif ($col == 'emp_id') {
            $selectCols[] = 's.emp_id';
        } elseif ($col == 'dept') {
            $selectCols[] = 'd.dep_nme AS dept';
        } elseif ($col == 'total_salary') {
            $selectCols[] = '(s.basic + s.housing + s.transport + s.food + s.misc + s.fuel + s.tel + s.cashier + s.other + s.guard) AS total_salary';
        } else {
            $selectCols[] = 's.' . $col;
        }
    }
    $selectClause = implode(', ', $selectCols);
    
    // Build WHERE clause
    $where = ['e.status = 1']; // Only active employees
    
    // Department fallback filter (only when no explicit scope restrictions are configured)
    $hasSpecialRestrictions = !empty($_SESSION['allowed_employees_array']) ||
                            !empty($_SESSION['allowed_departments_array']) ||
                            !empty($_SESSION['allowed_companies_array']);

    if (!$hasFullAccess && !$hasSpecialRestrictions && !empty($userDept)) {
        $where[] = "e.dept = '" . mysqli_real_escape_string($conDB, $userDept) . "'";
    } elseif (!$hasFullAccess && !$hasSpecialRestrictions && !empty($departments)) {
        $deptList = array_map(function($d) use ($conDB) { return "'" . mysqli_real_escape_string($conDB, $d) . "'"; }, $departments);
        $where[] = "e.dept IN (" . implode(',', $deptList) . ")";
    }
    
    // Company filter - restrict by accessible companies
    $company_filter = getCompanyFilterSQL('e.comp_no', true);
    if (!empty($company_filter)) {
        $where[] = substr($company_filter, 5); // Remove " AND " prefix for use in WHERE array
    }

    // Department filter - restrict by accessible departments
    // NOTE: For reports, we use company and department filters, not individual employee ID filter
    // This allows employees to see all colleagues in their department/company
    $department_filter = getDepartmentFilterSQL('e.dept', true);
    if (!empty($department_filter)) {
        $where[] = substr($department_filter, 5); // Remove " AND " prefix for use in WHERE array
    }
    
    // Status filter
    if ($status !== '') {
        $where[] = "s.status = '" . mysqli_real_escape_string($conDB, $status) . "'";
    }

    // Employee filter
    if (!empty($employeeId)) {
        $where[] = "s.emp_id = '" . mysqli_real_escape_string($conDB, $employeeId) . "'";
    }

    applyEmployeeCompanyCountryFilter($conDB, $where, $companies, $countries);
    $whereClause = implode(' AND ', $where);
    
    // Build and execute query
    $sql = "SELECT $selectClause 
            FROM emp_salary s
            INNER JOIN employees e ON s.emp_id = e.emp_id
            LEFT JOIN department d ON e.dept = d.id
            WHERE $whereClause
            ORDER BY e.name";
    
    $query = mysqli_query($conDB, $sql);
    if (!$query) {
        throw new Exception('Salary query error: ' . mysqli_error($conDB));
    }
    
    $data = [];
    $headers = [];
    
    // Get headers
    foreach ($columns as $col) {
        $headers[] = getColumnLabel($col);
    }
    
    // Get data
    while ($row = mysqli_fetch_assoc($query)) {
        if (isset($row['service_years'])) {
            $row['service_years'] = number_format((float)$row['service_years'], 2, '.', '');
        }

        if (isset($row['emp_name'])) {
            $row['emp_name'] = getDisplayName(parseName($row['emp_name']));
        }
        if (isset($row['dept'])) {
            $row['dept'] = getDisplayName(parseName($row['dept']));
        }

        $data[] = $row;
    }

    return ['data' => $data, 'headers' => $headers];
}

// CTC (Cost To Company) Report - one row per active employee across ~48 fixed fields
// spanning employees/emp_salary/employee_additional_info/employee_medical_insurance/
// contract_period plus a few computed figures (age, service days, EOS estimate, GOSI,
// vacation balance). Only requested $columns are computed/returned; the two per-employee
// extra queries (EOS, vacation balance) only run when their column is actually selected.
function ctc_report_get_vacation_balance_snapshot($conDB, $empId) {
    $stmt = mysqli_prepare($conDB, "SELECT `total_days`, `available_balance` FROM `emp_vacation_balance` WHERE `emp_id` = ? ORDER BY `last_updated` DESC LIMIT 1");
    mysqli_stmt_bind_param($stmt, "s", $empId);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt)) ?: null;
    mysqli_stmt_close($stmt);
    return [
        'total_days' => $row ? (float)$row['total_days'] : 0.0,
        'available_balance' => $row ? (float)$row['available_balance'] : 0.0,
    ];
}

function generateCTCReport($conDB, $columns, $departments, $hasFullAccess, $userDept, $employeeId = '', $companies = [], $countries = []) {
    global $is_rtl;

    $where = ['e.status = 1'];

    $hasSpecialRestrictions = !empty($_SESSION['allowed_employees_array']) ||
                            !empty($_SESSION['allowed_departments_array']) ||
                            !empty($_SESSION['allowed_companies_array']);

    if (!$hasFullAccess && !$hasSpecialRestrictions && !empty($userDept)) {
        $where[] = "e.dept = '" . mysqli_real_escape_string($conDB, $userDept) . "'";
    } elseif (!$hasFullAccess && !$hasSpecialRestrictions && !empty($departments)) {
        $deptList = array_map(function($d) use ($conDB) { return "'" . mysqli_real_escape_string($conDB, $d) . "'"; }, $departments);
        $where[] = "e.dept IN (" . implode(',', $deptList) . ")";
    }

    $company_filter = getCompanyFilterSQL('e.comp_no', true);
    if (!empty($company_filter)) {
        $where[] = substr($company_filter, 5);
    }
    $department_filter = getDepartmentFilterSQL('e.dept', true);
    if (!empty($department_filter)) {
        $where[] = substr($department_filter, 5);
    }
    if (!empty($employeeId)) {
        $where[] = "e.emp_id = '" . mysqli_real_escape_string($conDB, $employeeId) . "'";
    }
    applyEmployeeCompanyCountryFilter($conDB, $where, $companies, $countries);
    $whereClause = implode(' AND ', $where);

    $sql = "SELECT
            e.emp_id, e.iqama, e.name, e.sex, e.joining_date, e.dob, e.mobile, e.status, e.country, e.gosi, e.supervisor_id,
            sec.section_name AS position_name,
            aj.job AS actual_job_name, aj.job_ar AS actual_job_name_ar,
            d.id AS dept_code, d.dep_nme, d.dep_nme_ar,
            sd.name_en AS subdept_name_en, sd.name_ar AS subdept_name_ar,
            loc.id AS location_code, loc.name_en AS location_name_en, loc.name_ar AS location_name_ar,
            co.name AS country_name, co.name_ar AS country_name_ar,
            sp.sponsor, sp.sponsor_ar,
            cp.period AS contract_period_label, cp.vac_period AS contract_vac_period,
            eai.salary_grade, eai.dependants_count, eai.ticket_fare, eai.labour_office_expense, eai.iqama_renewal_fee, eai.citizen_local_relation,
            emi.med_insurance, emi.medical_class,
            es.basic, es.housing, es.transport, es.food, es.misc, es.cashier, es.fuel, es.tel, es.other, es.guard,
            (COALESCE(es.basic,0)+COALESCE(es.housing,0)+COALESCE(es.transport,0)+COALESCE(es.food,0)+COALESCE(es.misc,0)+COALESCE(es.cashier,0)+COALESCE(es.fuel,0)+COALESCE(es.tel,0)+COALESCE(es.other,0)+COALESCE(es.guard,0)) AS total_salary,
            sup.name AS supervisor_name
        FROM employees AS e
        LEFT JOIN section AS sec ON e.sectin_nme = sec.id
        LEFT JOIN ac_jobs AS aj ON e.actual_job = aj.id
        LEFT JOIN department AS d ON e.dept = d.id
        LEFT JOIN sub_departments AS sd ON e.sub_dept_id = sd.id
        LEFT JOIN locations AS loc ON e.location_id = loc.id
        LEFT JOIN countries AS co ON e.country = co.id
        LEFT JOIN sponsorship AS sp ON e.emp_sup_type = sp.id
        LEFT JOIN contract_period AS cp ON e.vac_period = cp.id
        LEFT JOIN employee_additional_info AS eai ON eai.emp_id = e.emp_id
        LEFT JOIN employee_medical_insurance AS emi ON emi.emp_id = e.emp_id AND emi.status = 'active'
        LEFT JOIN emp_salary AS es ON e.emp_id = es.emp_id AND es.status = 1
        LEFT JOIN employees AS sup ON sup.emp_id = e.supervisor_id
        WHERE $whereClause
        ORDER BY e.name";

    $query = mysqli_query($conDB, $sql);
    if (!$query) {
        throw new Exception('CTC report query error: ' . mysqli_error($conDB));
    }

    $needsEos = in_array('eos_until_today', $columns, true);
    $needsBalance = in_array('leave_balance', $columns, true) || in_array('total_accrual', $columns, true);

    $data = [];
    $headers = [];
    foreach ($columns as $col) {
        $headers[] = getColumnLabel($col);
    }

    while ($srcRow = mysqli_fetch_assoc($query)) {
        $age = null;
        if (!empty($srcRow['dob']) && $srcRow['dob'] !== '0000-00-00') {
            try {
                $dob = new DateTime($srcRow['dob']);
                $today = new DateTime('today');
                if ($dob <= $today) {
                    $age = $dob->diff($today)->y;
                }
            } catch (Exception $e) {
            }
        }

        $serviceDays = null;
        if (!empty($srcRow['joining_date']) && $srcRow['joining_date'] !== '0000-00-00') {
            try {
                $joinDate = new DateTime($srcRow['joining_date']);
                $today = new DateTime('today');
                if ($joinDate <= $today) {
                    $serviceDays = $joinDate->diff($today)->days;
                }
            } catch (Exception $e) {
            }
        }

        $yearsContract = null;
        if (!empty($srcRow['contract_period_label']) && preg_match('/^\s*(\d+(?:\.\d+)?)/', $srcRow['contract_period_label'], $m)) {
            $yearsContract = max(1, (float)$m[1]);
        }

        $monthlyLeaveAccrual = null;
        if (!empty($srcRow['contract_vac_period']) && $yearsContract) {
            $annualDays = (float)$srcRow['contract_vac_period'] / $yearsContract;
            $monthlyLeaveAccrual = $annualDays / 12;
        }

        $leaveBalanceDays = 0.0;
        $totalAccrualDays = 0.0;
        if ($needsBalance) {
            $balance = ctc_report_get_vacation_balance_snapshot($conDB, $srcRow['emp_id']);
            $leaveBalanceDays = $balance['available_balance'];
            $totalAccrualDays = $balance['total_days'];
        }

        $eosAmount = 0.0;
        if ($needsEos) {
            $eos = calculate_current_eos_estimate($conDB, $srcRow['emp_id'], $srcRow['joining_date']);
            $eosAmount = $eos['success'] ? $eos['eos_amount'] : 0.0;
        }

        $gosiAmount = 0.0;
        if ((int)($srcRow['country'] ?? 0) === 191) {
            $gosiBase = (float)($srcRow['basic'] ?? 0) + (float)($srcRow['housing'] ?? 0);
            $gosiAmount = round($gosiBase * (float)($srcRow['gosi'] ?? 0) / 100, 2);
        }

        $dailyRate = (float)($srcRow['total_salary'] ?? 0) / 30;
        $leaveAccrualCost = ($monthlyLeaveAccrual ?? 0) * $dailyRate;
        $totalCost = (float)($srcRow['total_salary'] ?? 0)
            + ((float)($srcRow['med_insurance'] ?? 0) / 12)
            + ((float)($srcRow['ticket_fare'] ?? 0) / 12)
            + ((float)($srcRow['labour_office_expense'] ?? 0) / 12)
            + ((float)($srcRow['iqama_renewal_fee'] ?? 0) / 12)
            + $gosiAmount
            + $leaveAccrualCost;

        $useAr = !empty($is_rtl);
        $row = [
            'id_iqama' => $srcRow['iqama'] ?? '',
            'emp_id' => $srcRow['emp_id'] ?? '',
            'name' => $srcRow['name'] ?? '',
            'gender' => ((int)($srcRow['sex'] ?? 0) === 1) ? __('male') : __('female'),
            'join_date' => format_safe_date($srcRow['joining_date'] ?? null),
            'position' => $srcRow['position_name'] ?? '',
            'actual_job' => $useAr ? ($srcRow['actual_job_name_ar'] ?? $srcRow['actual_job_name'] ?? '') : ($srcRow['actual_job_name'] ?? ''),
            'department' => $useAr ? ($srcRow['dep_nme_ar'] ?? $srcRow['dep_nme'] ?? '') : ($srcRow['dep_nme'] ?? ''),
            'birth_date' => format_safe_date($srcRow['dob'] ?? null),
            'age' => $age ?? '-',
            'salary_grade' => display_or_na($srcRow['salary_grade'] ?? null),
            'contract_type' => display_or_na($srcRow['contract_period_label'] ?? null),
            'years_contract' => $yearsContract ?? '-',
            'sub_department' => $useAr ? ($srcRow['subdept_name_ar'] ?? $srcRow['subdept_name_en'] ?? '') : ($srcRow['subdept_name_en'] ?? ''),
            'location' => $useAr ? ($srcRow['location_name_ar'] ?? $srcRow['location_name_en'] ?? '') : ($srcRow['location_name_en'] ?? ''),
            'country' => $useAr ? ($srcRow['country_name_ar'] ?? $srcRow['country_name'] ?? '') : ($srcRow['country_name'] ?? ''),
            'no_of_dependents' => display_or_na($srcRow['dependants_count'] ?? null),
            'basic' => number_format((float)($srcRow['basic'] ?? 0), 2),
            'housing' => number_format((float)($srcRow['housing'] ?? 0), 2),
            'transport' => number_format((float)($srcRow['transport'] ?? 0), 2),
            'food' => number_format((float)($srcRow['food'] ?? 0), 2),
            'misc' => number_format((float)($srcRow['misc'] ?? 0), 2),
            'cashier' => number_format((float)($srcRow['cashier'] ?? 0), 2),
            'fuel' => number_format((float)($srcRow['fuel'] ?? 0), 2),
            'tel' => number_format((float)($srcRow['tel'] ?? 0), 2),
            'other' => number_format((float)($srcRow['other'] ?? 0), 2),
            'guard' => number_format((float)($srcRow['guard'] ?? 0), 2),
            'total_salary' => number_format((float)($srcRow['total_salary'] ?? 0), 2),
            'med_insurance_amount' => number_format((float)($srcRow['med_insurance'] ?? 0), 2),
            'labour_office_expense' => number_format((float)($srcRow['labour_office_expense'] ?? 0), 2),
            'iqama_renewal_fee' => number_format((float)($srcRow['iqama_renewal_fee'] ?? 0), 2),
            'leave_balance' => number_format($leaveBalanceDays, 2),
            'ticket' => number_format((float)($srcRow['ticket_fare'] ?? 0), 2),
            'eos_until_today' => number_format($eosAmount, 2),
            'total_cost' => number_format($totalCost, 2),
            'gosi' => number_format($gosiAmount, 2),
            'total_accrual' => number_format($totalAccrualDays, 2),
            'monthly_leave_accrual' => number_format((float)($monthlyLeaveAccrual ?? 0), 2),
            'service_days' => $serviceDays ?? '-',
            'sponsor' => $useAr ? ($srcRow['sponsor_ar'] ?? $srcRow['sponsor'] ?? '') : ($srcRow['sponsor'] ?? ''),
            'medical_class' => display_or_na($srcRow['medical_class'] ?? null),
            'citizen_local' => display_or_na($srcRow['citizen_local_relation'] ?? null),
            'direct_manager_id' => display_or_na($srcRow['supervisor_id'] ?? null),
            'direct_manager_name' => display_or_na($srcRow['supervisor_name'] ?? null),
            'location_code' => display_or_na($srcRow['location_code'] ?? null),
            'department_code' => display_or_na($srcRow['dept_code'] ?? null),
            'mobile' => $srcRow['mobile'] ?? '',
            'status' => ((int)($srcRow['status'] ?? 0) === 1) ? __('active') : __('inactive'),
        ];

        $filteredRow = [];
        foreach ($columns as $col) {
            $filteredRow[$col] = $row[$col] ?? '';
        }
        $data[] = $filteredRow;
    }

    return ['data' => $data, 'headers' => $headers];
}

// Payroll Report
function generatePayrollReport($conDB, $columns, $departments, $dateFrom, $dateTo, $hasFullAccess, $userDept, $status = '', $employeeId = '', $companies = [], $countries = []) {
    // Build SELECT clause with column mapping aligned to actual schema
    $selectCols = [];

    foreach ($columns as $col) {
        switch ($col) {
            case 'payroll_id':
                $selectCols[] = 'p.id AS payroll_id';
                break;
            case 'emp_id':
                $selectCols[] = 'p.emp_id AS emp_id';
                break;
            case 'emp_name':
                $selectCols[] = 'e.name AS emp_name';
                break;
            case 'dept':
                $selectCols[] = 'd.dep_nme AS dept';
                break;
            case 'comp_name':
                $selectCols[] = 'c.comp_name AS comp_name';
                break;
            case 'month':
                $selectCols[] = "SUBSTRING(p.month_year, 6, 2) AS month";
                break;
            case 'year':
                $selectCols[] = "SUBSTRING(p.month_year, 1, 4) AS year";
                break;
            case 'total_employees':
                $selectCols[] = '(SELECT COUNT(*) FROM payrolls p2 WHERE p2.month_year = p.month_year) AS total_employees';
                break;
            case 'total_salary':
                $selectCols[] = 'p.total_gross_salary AS total_salary';
                break;
            case 'basic_salary':
                $selectCols[] = 'p.basic_salary AS basic_salary';
                break;
            case 'housing_allowance':
                $selectCols[] = 'p.housing_allowance AS housing_allowance';
                break;
            case 'transport_allowance':
                $selectCols[] = 'p.transport_allowance AS transport_allowance';
                break;
            case 'food_allowance':
                $selectCols[] = 'p.food_allowance AS food_allowance';
                break;
            case 'miscellaneous_allowance':
                $selectCols[] = 'p.miscellaneous_allowance AS miscellaneous_allowance';
                break;
            case 'cashier_allowance':
                $selectCols[] = 'p.cashier_allowance AS cashier_allowance';
                break;
            case 'fuel_allowance':
                $selectCols[] = 'p.fuel_allowance AS fuel_allowance';
                break;
            case 'telephone_allowance':
                $selectCols[] = 'p.telephone_allowance AS telephone_allowance';
                break;
            case 'other_allowance':
                $selectCols[] = 'p.other_allowance AS other_allowance';
                break;
            case 'guard_allowance':
                $selectCols[] = 'p.guard_allowance AS guard_allowance';
                break;
            case 'total_benefits':
                $selectCols[] = 'p.total_benefits AS total_benefits';
                break;
            case 'total_deductions':
                $selectCols[] = 'p.total_deductions AS total_deductions';
                break;
            case 'net_salary':
                $selectCols[] = 'p.net_salary AS net_salary';
                break;
            case 'generated_by':
                $selectCols[] = 'p.status AS generated_by';
                break;
            case 'created_at':
                $selectCols[] = 'p.generated_at AS created_at';
                break;
            case 'status':
                $selectCols[] = 'p.status AS status';
                break;
            default:
                break;
        }
    }

    if (empty($selectCols)) {
        $selectCols = [
            'p.id AS payroll_id',
            'p.emp_id AS emp_id',
            'e.name AS emp_name',
            "SUBSTRING(p.month_year, 6, 2) AS month",
            "SUBSTRING(p.month_year, 1, 4) AS year",
            'p.basic_salary AS basic_salary',
            'p.housing_allowance AS housing_allowance',
            'p.transport_allowance AS transport_allowance',
            'p.food_allowance AS food_allowance',
            'p.miscellaneous_allowance AS miscellaneous_allowance',
            'p.cashier_allowance AS cashier_allowance',
            'p.fuel_allowance AS fuel_allowance',
            'p.telephone_allowance AS telephone_allowance',
            'p.other_allowance AS other_allowance',
            'p.guard_allowance AS guard_allowance',
            'p.total_gross_salary AS total_salary',
            'p.total_benefits AS total_benefits',
            'p.total_deductions AS total_deductions',
            'p.net_salary AS net_salary',
            'p.status AS status',
            'p.generated_at AS created_at'
        ];
    }
    $selectClause = implode(', ', $selectCols);

    // Build WHERE clause
    $where = ['e.status = 1']; // Only active employees

    if (!empty($dateFrom)) {
        $fromYm = substr($dateFrom, 0, 7);
        $where[] = "p.month_year >= '" . mysqli_real_escape_string($conDB, $fromYm) . "'";
    }
    if (!empty($dateTo)) {
        $toYm = substr($dateTo, 0, 7);
        $where[] = "p.month_year <= '" . mysqli_real_escape_string($conDB, $toYm) . "'";
    }
    if ($status !== '') {
        $where[] = "p.status = '" . mysqli_real_escape_string($conDB, $status) . "'";
    }
    
    // Department fallback filter (only when no explicit scope restrictions are configured)
    $hasSpecialRestrictions = !empty($_SESSION['allowed_employees_array']) ||
                            !empty($_SESSION['allowed_departments_array']) ||
                            !empty($_SESSION['allowed_companies_array']);

    if (!$hasFullAccess && !$hasSpecialRestrictions && !empty($userDept)) {
        $where[] = "e.dept = '" . mysqli_real_escape_string($conDB, $userDept) . "'";
    } elseif (!$hasFullAccess && !$hasSpecialRestrictions && !empty($departments)) {
        $deptList = array_map(function($d) use ($conDB) { return "'" . mysqli_real_escape_string($conDB, $d) . "'"; }, $departments);
        $where[] = "e.dept IN (" . implode(',', $deptList) . ")";
    }

    // Company filter - restrict by accessible companies
    $company_filter = getCompanyFilterSQL('e.comp_no', true);
    if (!empty($company_filter)) {
        $where[] = substr($company_filter, 5);
    }

    // Department filter - restrict by accessible departments
    $department_filter = getDepartmentFilterSQL('e.dept', true);
    if (!empty($department_filter)) {
        $where[] = substr($department_filter, 5);
    }

    // Employee filter
    if (!empty($employeeId)) {
        $where[] = "p.emp_id = '" . mysqli_real_escape_string($conDB, $employeeId) . "'";
    }

    applyEmployeeCompanyCountryFilter($conDB, $where, $companies, $countries);
    $whereClause = implode(' AND ', $where);

    $sql = "SELECT $selectClause 
            FROM payrolls p
            LEFT JOIN employees e ON p.emp_id = e.emp_id
            LEFT JOIN department d ON e.dept = d.id
            LEFT JOIN companies c ON e.comp_no = c.comp_id
            WHERE $whereClause
            ORDER BY p.month_year DESC, p.id DESC";

    $query = mysqli_query($conDB, $sql);
    if (!$query) {
        throw new Exception('Payroll query error: ' . mysqli_error($conDB));
    }

    $data = [];
    $headers = [];

    foreach ($columns as $col) {
        $headers[] = getColumnLabel($col);
    }

    while ($row = mysqli_fetch_assoc($query)) {
        if (isset($row['month'])) {
            $monthNames = ['', 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
            $monthIndex = (int)$row['month'];
            $row['month'] = ($monthIndex >=1 && $monthIndex <=12) ? $monthNames[$monthIndex] : $row['month'];
        }

        if (isset($row['month'])) {
            $row['month'] = getDisplayName($row['month']);
        }
        if (isset($row['generated_by'])) {
            $row['generated_by'] = getDisplayName($row['generated_by']);
        }

        $data[] = $row;
    }

    return ['data' => $data, 'headers' => $headers];
}

// Attendance Report
function generateAttendanceReport($conDB, $columns, $departments, $dateFrom, $dateTo, $hasFullAccess, $userDept, $employeeId = '', $companies = [], $countries = []) {
    // Build SELECT clause
    $selectCols = [];
    
    foreach ($columns as $col) {
        if ($col == 'emp_name') {
            $selectCols[] = 'e.name AS emp_name';
        } elseif ($col == 'emp_id') {
            $selectCols[] = 'a.emp_id';
        } elseif ($col == 'dept') {
            $selectCols[] = 'd.dep_nme AS dept';
        } elseif ($col == 'check_in') {
            $selectCols[] = 'a.time_in AS check_in';
        } elseif ($col == 'check_out') {
            $selectCols[] = 'a.time_out AS check_out';
        } elseif ($col == 'hours') {
            $selectCols[] = "SEC_TO_TIME(GREATEST(0, TIME_TO_SEC(STR_TO_DATE(a.time_out, '%H:%i')) - TIME_TO_SEC(STR_TO_DATE(a.time_in, '%H:%i')))) AS hours";
        } elseif ($col == 'status') {
            $selectCols[] = 'a.state AS status';
        } else {
            $selectCols[] = 'a.' . $col;
        }
    }
    $selectClause = implode(', ', $selectCols);
    
    // Build WHERE clause
    $where = ['e.status = 1']; // Only active employees
    
    // Department fallback filter (only when no explicit scope restrictions are configured)
    $hasSpecialRestrictions = !empty($_SESSION['allowed_employees_array']) ||
                            !empty($_SESSION['allowed_departments_array']) ||
                            !empty($_SESSION['allowed_companies_array']);

    if (!$hasFullAccess && !$hasSpecialRestrictions && !empty($userDept)) {
        $where[] = "e.dept = '" . mysqli_real_escape_string($conDB, $userDept) . "'";
    } elseif (!$hasFullAccess && !$hasSpecialRestrictions && !empty($departments)) {
        $deptList = array_map(function($d) use ($conDB) { return "'" . mysqli_real_escape_string($conDB, $d) . "'"; }, $departments);
        $where[] = "e.dept IN (" . implode(',', $deptList) . ")";
    }
    
    // Date filter
    if (!empty($dateFrom)) {
        $where[] = "a.date >= '" . mysqli_real_escape_string($conDB, $dateFrom) . "'";
    }
    if (!empty($dateTo)) {
        $where[] = "a.date <= '" . mysqli_real_escape_string($conDB, $dateTo) . "'";
    }
    
    // Company filter - restrict by accessible companies
    $company_filter = getCompanyFilterSQL('e.comp_no', true);
    if (!empty($company_filter)) {
        $where[] = substr($company_filter, 5); // Remove " AND " prefix for use in WHERE array
    }

    // Department filter - restrict by accessible departments
    $department_filter = getDepartmentFilterSQL('e.dept', true);
    if (!empty($department_filter)) {
        $where[] = substr($department_filter, 5);
    }

    // Employee filter
    if (!empty($employeeId)) {
        $where[] = "a.emp_id = '" . mysqli_real_escape_string($conDB, $employeeId) . "'";
    }
    
    applyEmployeeCompanyCountryFilter($conDB, $where, $companies, $countries);
    $whereClause = implode(' AND ', $where);
    
    // Build and execute query
    $sql = "SELECT $selectClause 
            FROM attendance a
            INNER JOIN employees e ON a.emp_id = e.emp_id
            LEFT JOIN department d ON e.dept = d.id
            WHERE $whereClause
            ORDER BY a.date DESC, e.name";
    
    $query = mysqli_query($conDB, $sql);
    if (!$query) {
        throw new Exception('Attendance query error: ' . mysqli_error($conDB));
    }
    
    $data = [];
    $headers = [];
    
    // Get headers
    foreach ($columns as $col) {
        $headers[] = getColumnLabel($col);
    }
    
    // Get data
    while ($row = mysqli_fetch_assoc($query)) {
        if (isset($row['emp_name'])) {
            $row['emp_name'] = getDisplayName(parseName($row['emp_name']));
        }
        if (isset($row['dept'])) {
            $row['dept'] = getDisplayName($row['dept']);
        }
        $data[] = $row;
    }
    
    return ['data' => $data, 'headers' => $headers];
}

// Document Report
function generateDocumentReport($conDB, $columns, $departments, $hasFullAccess, $userDept, $status = '', $employeeId = '', $companies = [], $countries = []) {
    // Build SELECT clause - always include d.id and d.path for attachment button
    $selectCols = ['d.id AS document_id', 'd.path AS file_path', 'd.docu_ext AS file_extension'];
    
    foreach ($columns as $col) {
        // Skip attachment column - it's generated dynamically
        if ($col == 'attachment') {
            continue;
        } elseif ($col == 'emp_name') {
            $selectCols[] = 'e.name AS emp_name';
        } elseif ($col == 'emp_id') {
            $selectCols[] = 'd.emp_id';
        } elseif ($col == 'dept') {
            $selectCols[] = 'dep.dep_nme AS dept';
        } elseif ($col == 'document_type') {
            $selectCols[] = 'd.docu_typ AS document_type';
        } elseif ($col == 'document_name') {
            $selectCols[] = 'd.path AS document_name';
        } elseif ($col == 'upload_date') {
            $selectCols[] = 'd.created_at AS upload_date';
        } elseif ($col == 'status') {
            $selectCols[] = "CASE WHEN d.status = 'A' THEN 'Active' ELSE 'Inactive' END AS status";
        } else {
            $selectCols[] = 'd.' . $col;
        }
    }
    $selectClause = implode(', ', $selectCols);
    
    // Build WHERE clause
    $where = ['e.status = 1']; // Only active employees
    
    // Department fallback filter (only when no explicit scope restrictions are configured)
    $hasSpecialRestrictions = !empty($_SESSION['allowed_employees_array']) ||
                            !empty($_SESSION['allowed_departments_array']) ||
                            !empty($_SESSION['allowed_companies_array']);

    if (!$hasFullAccess && !$hasSpecialRestrictions && !empty($userDept)) {
        $where[] = "e.dept = '" . mysqli_real_escape_string($conDB, $userDept) . "'";
    } elseif (!$hasFullAccess && !$hasSpecialRestrictions && !empty($departments)) {
        $deptList = array_map(function($d) use ($conDB) { return "'" . mysqli_real_escape_string($conDB, $d) . "'"; }, $departments);
        $where[] = "e.dept IN (" . implode(',', $deptList) . ")";
    }
    
    // Company filter - restrict by accessible companies
    $company_filter = getCompanyFilterSQL('e.comp_no', true);
    if (!empty($company_filter)) {
        $where[] = substr($company_filter, 5); // Remove " AND " prefix for use in WHERE array
    }
    
    // Department filter - restrict by accessible departments
    $department_filter = getDepartmentFilterSQL('e.dept', true);
    if (!empty($department_filter)) {
        $where[] = substr($department_filter, 5); // Remove " AND " prefix for use in WHERE array
    }
    
    // Status filter
    if ($status !== '') {
        $where[] = "d.status = '" . mysqli_real_escape_string($conDB, $status) . "'";
    }

    // Employee filter
    if (!empty($employeeId)) {
        $where[] = "d.emp_id = '" . mysqli_real_escape_string($conDB, $employeeId) . "'";
    }
    
    applyEmployeeCompanyCountryFilter($conDB, $where, $companies, $countries);
    $whereClause = implode(' AND ', $where);
    
    // Build and execute query
    $sql = "SELECT $selectClause 
            FROM emp_docu d
            INNER JOIN employees e ON d.emp_id = e.emp_id
            LEFT JOIN department dep ON e.dept = dep.id
            WHERE $whereClause
            ORDER BY d.created_at DESC";
    
    $query = mysqli_query($conDB, $sql);
    if (!$query) {
        throw new Exception('Document query error: ' . mysqli_error($conDB));
    }
    
    $data = [];
    $headers = [];
    
    // Get headers
    foreach ($columns as $col) {
        $headers[] = getColumnLabel($col);
    }
    
    // Get data
    while ($row = mysqli_fetch_assoc($query)) {
        $docId = $row['document_id'];
        $filePath = $row['file_path'];
        $fileExt = $row['file_extension'];
        
        // Create attachment button with file path
        $viewText = function_exists('__') ? __('view') : 'View';
        $viewDocTitle = function_exists('__') ? __('view_document') : 'View Document';
        $row['attachment'] = '<a href="./assets/emp_documents/' . htmlspecialchars($filePath) . '" target="_blank" class="btn btn-sm btn-primary" title="' . htmlspecialchars($viewDocTitle) . '"><i class="mdi mdi-paperclip"></i> ' . htmlspecialchars($viewText) . '</a>';
        
        if (isset($row['emp_name'])) {
            $row['emp_name'] = getDisplayName(parseName($row['emp_name']));
        }
        if (isset($row['dept'])) {
            $row['dept'] = getDisplayName($row['dept']);
        }
        if (isset($row['document_type'])) {
            $row['document_type'] = getDisplayName($row['document_type']);
        }
        if (isset($row['status'])) {
            $row['status'] = getDisplayName($row['status']);
        }

        $data[] = $row;
    }
    
    return ['data' => $data, 'headers' => $headers];
}

// Evaluation Report
function generateEvaluationReport($conDB, $columns, $departments, $dateFrom, $dateTo, $hasFullAccess, $userDept, $status = '', $employeeId = '', $companies = [], $countries = []) {
    // Add column mappings
    $columnMap = [
        'dept' => 'd.dep_nme',
        'sex' => "CASE WHEN e.sex = '1' THEN 'Male' WHEN e.sex = '2' THEN 'Female' ELSE e.sex END",
        'country' => 'c.name',
        'bank_name' => 'b.name',
        'sectin_nme' => 's.section_name'
    ];

    // Build SELECT clause - always include ev.id for action buttons
    $selectCols = ['ev.id AS evaluation_id'];
    
    foreach ($columns as $col) {
        if ($col == 'emp_name') {
            $selectCols[] = 'e.name AS emp_name';
        } elseif ($col == 'emp_id') {
            $selectCols[] = "ev.employee_emp_id AS emp_id";
        } elseif ($col == 'dept') {
            $selectCols[] = 'd.dep_nme AS dept';
        } elseif ($col == 'evaluation_date') {
            $selectCols[] = 'ev.created_at AS evaluation_date';
        } elseif ($col == 'score') {
            $selectCols[] = 'ev.total_score AS score';
        } elseif ($col == 'rating') {
            $selectCols[] = "CASE 
                                WHEN ev.total_score < 50 THEN 'Poor'
                                WHEN ev.total_score < 70 THEN 'Average'
                                WHEN ev.total_score < 90 THEN 'Good'
                                ELSE 'Excellent'
                              END AS rating";
        } elseif ($col == 'evaluator') {
            $selectCols[] = 'em.name AS evaluator';
        } elseif ($col == 'acknowledgment_status') {
            $selectCols[] = 'ev.manager_acknowledgment_status AS acknowledgment_status';
        } elseif ($col == 'objection_note') {
            $selectCols[] = 'ev.manager_objection_note AS objection_note';
        } else {
            $selectCols[] = 'ev.' . $col;
        }
    }
    $selectClause = implode(', ', $selectCols);
    
    // Build WHERE clause
    $where = ['e.status = 1']; // Only active employees
    
    // Department fallback filter (only when no explicit scope restrictions are configured)
    $hasSpecialRestrictions = !empty($_SESSION['allowed_employees_array']) ||
                            !empty($_SESSION['allowed_departments_array']) ||
                            !empty($_SESSION['allowed_companies_array']);

    if (!$hasFullAccess && !$hasSpecialRestrictions && !empty($userDept)) {
        $where[] = "e.dept = '" . mysqli_real_escape_string($conDB, $userDept) . "'";
    } elseif (!$hasFullAccess && !$hasSpecialRestrictions && !empty($departments)) {
        $deptList = array_map(function($d) use ($conDB) { return "'" . mysqli_real_escape_string($conDB, $d) . "'"; }, $departments);
        $where[] = "e.dept IN (" . implode(',', $deptList) . ")";
    }
    
    // Date filter
    if (!empty($dateFrom)) {
        $where[] = "ev.created_at >= '" . mysqli_real_escape_string($conDB, $dateFrom) . "'";
    }
    if (!empty($dateTo)) {
        $where[] = "ev.created_at <= '" . mysqli_real_escape_string($conDB, $dateTo) . "'";
    }
    
    // Acknowledgment status filter
    // IMPORTANT: System admins can see all evaluations regardless of status
    // For other users: By default, exclude pending evaluations (they haven't been acknowledged/objected yet)
    global $user_type;
    if ($user_type !== 'administrator') {
        if ($status === 'objected') {
            // Show only objected evaluations
            $where[] = "ev.manager_acknowledgment_status = 'objected'";
        } else {
            // Default behavior: exclude pending evaluations, show only acknowledged or objected
            $where[] = "ev.manager_acknowledgment_status IN ('acknowledged', 'objected')";
        }
    }
    // System admins see all, no status filter applied
    
    // Company filter - restrict by accessible companies
    $company_filter = getCompanyFilterSQL('e.comp_no', true);
    if (!empty($company_filter)) {
        $where[] = substr($company_filter, 5); // Remove " AND " prefix for use in WHERE array
    }

    // Department filter - restrict by accessible departments
    $department_filter = getDepartmentFilterSQL('e.dept', true);
    if (!empty($department_filter)) {
        $where[] = substr($department_filter, 5);
    }

    // Employee filter
    if (!empty($employeeId)) {
        $where[] = "ev.employee_emp_id = '" . mysqli_real_escape_string($conDB, $employeeId) . "'";
    }
    
    applyEmployeeCompanyCountryFilter($conDB, $where, $companies, $countries);
    $whereClause = implode(' AND ', $where);
    
    // Build and execute query
        $sql = "SELECT $selectClause 
            FROM emp_evaluations ev
            INNER JOIN employees e ON ev.employee_emp_id = e.emp_id
            LEFT JOIN department d ON e.dept = d.id
            LEFT JOIN section s ON e.sectin_nme = s.id
            LEFT JOIN countries c ON e.country = c.id
            LEFT JOIN bank_list b ON e.bank_name = b.id
            LEFT JOIN employees em ON ev.manager_emp_id = em.emp_id
            LEFT JOIN companies c2 ON e.comp_no = c2.comp_id
            LEFT JOIN sponsorship ON e.emp_sup_type = sponsorship.id
            LEFT JOIN contract_period ON e.vac_period = contract_period.id
            WHERE $whereClause
            ORDER BY ev.created_at DESC";
    
    $query = mysqli_query($conDB, $sql);
    if (!$query) {
        throw new Exception('Evaluation query error: ' . mysqli_error($conDB));
    }
    
    $data = [];
    $headers = [];
    
    // Get headers
    foreach ($columns as $col) {
        $headers[] = getColumnLabel($col);
    }
    
    // Add Actions header
    $headers[] = function_exists('__') ? __('actions') : 'Actions';
    
    // Get data
    while ($row = mysqli_fetch_assoc($query)) {
    
        if (isset($row['dept'])) {
            $row['dept'] = getDisplayName($row['dept']);
        }
        if (isset($row['emp_name'])) {
            $row['emp_name'] = getDisplayName(parseName($row['emp_name']));
        }
        if (isset($row['rating'])) {
            $row['rating'] = getDisplayName($row['rating']);
        }
        if (isset($row['evaluator'])) {
            $row['evaluator'] = getDisplayName($row['evaluator']);
        }
        if (isset($row['acknowledgment_status'])) {
            $row['acknowledgment_status'] = getDisplayName($row['acknowledgment_status']);
        }

        $evalId = $row['evaluation_id'];
        $viewDetails = function_exists('__') ? __('view_details') : 'View Details';
        $row['actions'] = '<button class="btn btn-sm btn-info view-evaluation-details" data-eval-id="' . $evalId . '"><i class="mdi mdi-eye"></i> ' . htmlspecialchars($viewDetails) . '</button>';
        $data[] = $row;
    }
    
    return ['data' => $data, 'headers' => $headers];
}

// Resignation Report
function generateResignationReport($conDB, $columns, $departments, $dateFrom, $dateTo, $hasFullAccess, $userDept, $status = '', $employeeId = '', $companies = [], $countries = []) {
    global $is_rtl;
    
    // Build SELECT clause
    $selectCols = [];
    
    foreach ($columns as $col) {
        if ($col == 'emp_name') {
            $selectCols[] = 'e.name AS emp_name';
        } elseif ($col == 'emp_id') {
            $selectCols[] = 'r.emp_id';
        } elseif ($col == 'dept') {
            $selectCols[] = 'd.dep_nme AS dept';
        } elseif ($col == 'resignation_date') {
            // No resignation_date column; use created_at (submission timestamp)
            $selectCols[] = 'r.created_at AS resignation_date';
        } elseif ($col == 'reason') {
            // Map to rejection_reason if present (best available single text field)
            $selectCols[] = 'r.rejection_reason AS reason';
        } else {
            $selectCols[] = 'r.' . $col;
        }
    }
    $selectClause = implode(', ', $selectCols);
    
    // Build WHERE clause - only active employees
    $where = ['e.status = 1'];
    
    // Department fallback filter (only when no explicit scope restrictions are configured)
    $hasSpecialRestrictions = !empty($_SESSION['allowed_employees_array']) ||
                            !empty($_SESSION['allowed_departments_array']) ||
                            !empty($_SESSION['allowed_companies_array']);

    if (!$hasFullAccess && !$hasSpecialRestrictions && !empty($userDept)) {
        $where[] = "e.dept = '" . mysqli_real_escape_string($conDB, $userDept) . "'";
    } elseif (!$hasFullAccess && !$hasSpecialRestrictions && !empty($departments)) {
        $deptList = array_map(function($d) use ($conDB) { return "'" . mysqli_real_escape_string($conDB, $d) . "'"; }, $departments);
        $where[] = "e.dept IN (" . implode(',', $deptList) . ")";
    }
    
    // Date filter
    if (!empty($dateFrom)) {
        $where[] = "r.created_at >= '" . mysqli_real_escape_string($conDB, $dateFrom) . "'";
    }
    if (!empty($dateTo)) {
        $where[] = "r.created_at <= '" . mysqli_real_escape_string($conDB, $dateTo) . "'";
    }
    
    // Company filter - restrict by accessible companies
    $company_filter = getCompanyFilterSQL('e.comp_no', true);
    if (!empty($company_filter)) {
        $where[] = substr($company_filter, 5); // Remove " AND " prefix for use in WHERE array
    }
    
    // Department filter - restrict by accessible departments
    $department_filter = getDepartmentFilterSQL('e.dept', true);
    if (!empty($department_filter)) {
        $where[] = substr($department_filter, 5); // Remove " AND " prefix for use in WHERE array
    }
    
    // Status filter
    if ($status !== '') {
        $where[] = "r.status = '" . mysqli_real_escape_string($conDB, $status) . "'";
    }

    // Employee filter
    if (!empty($employeeId)) {
        $where[] = "r.emp_id = '" . mysqli_real_escape_string($conDB, $employeeId) . "'";
    }
    
    applyEmployeeCompanyCountryFilter($conDB, $where, $companies, $countries);
    $whereClause = implode(' AND ', $where);
    
    // Build and execute query
    $sql = "SELECT $selectClause 
            FROM emp_resignations r
            INNER JOIN employees e ON r.emp_id = e.emp_id
            LEFT JOIN department d ON e.dept = d.id
            WHERE $whereClause
            ORDER BY r.created_at DESC";
    
    $query = mysqli_query($conDB, $sql);
    if (!$query) {
        throw new Exception('Resignation query error: ' . mysqli_error($conDB));
    }
    
    $data = [];
    $headers = [];
    
    // Get headers
    foreach ($columns as $col) {
        $headers[] = getColumnLabel($col);
    }
    
    // Get data
    while ($row = mysqli_fetch_assoc($query)) {

        if (isset($row['emp_name'])) {
            $row['emp_name'] = getDisplayName(parseName($row['emp_name']));
        }
        if (isset($row['dept'])) {
            $row['dept'] = getDisplayName($row['dept']);
        }
        if (isset($row['reason'])) {
            $row['reason'] = getReasonText($row['reason'], $is_rtl);
        }
        if (isset($row['status'])) {
            $row['status'] = getDisplayName($row['status']);
        }

        $data[] = $row;
    }
    
    return ['data' => $data, 'headers' => $headers];
}

// End of Service Report (Prospective Calculation for Active Employees)
function generateEOSReport($conDB, $columns, $departments, $dateFrom, $dateTo, $hasFullAccess, $userDept, $employeeId = '', $companies = [], $countries = []) {
    // This report calculates prospective EOS amounts for active employees
    // based on a selected termination date (dateTo parameter)
    global $is_rtl;

    // Use dateTo as the termination date for calculations, default to today if not provided
    $terminationDate = !empty($dateTo) ? $dateTo : date('Y-m-d');
    
    // Build WHERE clause for employee selection
    $where = ['e.status = 1']; // Only active employees
    
    // Department fallback filter (only when no explicit scope restrictions are configured)
    $hasSpecialRestrictions = !empty($_SESSION['allowed_employees_array']) ||
                            !empty($_SESSION['allowed_departments_array']) ||
                            !empty($_SESSION['allowed_companies_array']);

    if (!$hasFullAccess && !$hasSpecialRestrictions && !empty($userDept)) {
        $where[] = "e.dept = '" . mysqli_real_escape_string($conDB, $userDept) . "'";
    } elseif (!$hasFullAccess && !$hasSpecialRestrictions && !empty($departments)) {
        $deptList = array_map(function($d) use ($conDB) { return "'" . mysqli_real_escape_string($conDB, $d) . "'"; }, $departments);
        $where[] = "e.dept IN (" . implode(',', $deptList) . ")";
    }
    
    // Company filter - restrict by accessible companies
    $company_filter = getCompanyFilterSQL('e.comp_no', true);
    if (!empty($company_filter)) {
        $where[] = substr($company_filter, 5); // Remove " AND " prefix for use in WHERE array
    }
    
    // Department filter - restrict by accessible departments
    $department_filter = getDepartmentFilterSQL('e.dept', true);
    if (!empty($department_filter)) {
        $where[] = substr($department_filter, 5); // Remove " AND " prefix for use in WHERE array
    }

    // Employee filter
    if (!empty($employeeId)) {
        $where[] = "e.emp_id = '" . mysqli_real_escape_string($conDB, $employeeId) . "'";
    }
    
    applyEmployeeCompanyCountryFilter($conDB, $where, $companies, $countries);
    $whereClause = implode(' AND ', $where);
    
    // Fetch active employees with their salary details
    $sql = "SELECT
                e.emp_id,
                e.name,
                e.joining_date,
                e.vacation_days,
                e.vac_period,
                d.dep_nme AS dept_name,
                d.dep_nme_ar AS dept_name_ar,
                c.comp_name,
                c.comp_name_ar,
                s.basic,
                s.housing,
                s.transport,
                s.food,
                s.misc,
                s.cashier,
                s.fuel,
                s.tel,
                s.guard,
                s.other
            FROM employees e
            LEFT JOIN department d ON e.dept = d.id
            LEFT JOIN companies c ON e.comp_no = c.comp_id
            LEFT JOIN emp_salary s ON e.emp_id = s.emp_id AND s.status = 1
            WHERE $whereClause
            ORDER BY e.name";
    
    $query = mysqli_query($conDB, $sql);
    if (!$query) {
        throw new Exception('EOS query error: ' . mysqli_error($conDB));
    }
    
    $data = [];
    $headers = [];
    
    // Get headers
    foreach ($columns as $col) {
        $headers[] = getColumnLabel($col);
    }
    
    // Add Actions header
    $headers[] = function_exists('__') ? __('actions') : 'Actions';
    
    // Calculate EOS for each employee
    while ($row = mysqli_fetch_assoc($query)) {
        $empId = $row['emp_id'];
        $joiningDate = $row['joining_date'];
        
        // Skip if no joining date
        if (empty($joiningDate)) {
            continue;
        }
        
        // Calculate service duration
        $joinDate = new DateTime($joiningDate);
        $endDate = new DateTime($terminationDate);
        
        // Skip if termination date is before joining date
        if ($joinDate >= $endDate) {
            continue;
        }
        
        $serviceDuration = $joinDate->diff($endDate);
        $serviceYears = $serviceDuration->y;
        $serviceMonths = $serviceDuration->m;
        $serviceDays = $serviceDuration->d;
        
        // Calculate total salary (EOS base) with housing calculation if needed
        $basic = (float)($row['basic'] ?? 0);
        $housing = (float)($row['housing'] ?? 0);
        
        // If housing is 0, calculate it as (basic/12)*2
        $calculatedHousing = ($housing == 0 && $basic > 0) ? ($basic / 12) * 2 : $housing;
        
        $totalSalary = $basic + $calculatedHousing + 
                       (float)($row['transport'] ?? 0) +
                       (float)($row['food'] ?? 0) +
                       (float)($row['misc'] ?? 0) +
                       (float)($row['cashier'] ?? 0) +
                       (float)($row['fuel'] ?? 0) +
                       (float)($row['tel'] ?? 0) +
                       (float)($row['guard'] ?? 0) +
                       (float)($row['other'] ?? 0);
        
        // Calculate vacation salary base (NO calculated housing, only actual)
        $vacationSalaryBase = $basic + $housing + 
                              (float)($row['transport'] ?? 0) +
                              (float)($row['food'] ?? 0) +
                              (float)($row['misc'] ?? 0) +
                              (float)($row['cashier'] ?? 0) +
                              (float)($row['fuel'] ?? 0) +
                              (float)($row['tel'] ?? 0) +
                              (float)($row['guard'] ?? 0) +
                              (float)($row['other'] ?? 0);
        
        // Calculate EOS using Saudi Labor Law
        // First 5 years: half month per year
        // After 5 years: full month per year
        $eosAmount = 0;
        $totalServiceYears = $serviceYears + ($serviceMonths / 12) + ($serviceDays / 365);
        
        if ($totalServiceYears <= 5) {
            $eosAmount = ($totalSalary / 2) * $totalServiceYears;
        } else {
            $eosAmount = ($totalSalary / 2) * 5 + $totalSalary * ($totalServiceYears - 5);
        }
        
        // Calculate vacation balance
        $vacationDays = (float)($row['vacation_days'] ?? 0);
        $vacPeriod = (int)($row['vac_period'] ?? 0);
        
        // Determine if 2-year contract
        $isTwoYear = false;
        if ($vacPeriod > 0) {
            $periodQuery = mysqli_query($conDB, "SELECT period FROM contract_period WHERE id = $vacPeriod LIMIT 1");
            if ($periodQuery && $periodRow = mysqli_fetch_assoc($periodQuery)) {
                $isTwoYear = (strpos($periodRow['period'], '2 Years') !== false);
            }
        }
        
        // Annual vacation rate
        $annualRate = $isTwoYear ? ($vacationDays / 2) : $vacationDays;
        $dailyRate = $annualRate / 365;
        
        // Get current balance from emp_vacation_balance
        $currentBalance = 0;
        $balanceQuery = mysqli_query($conDB, "SELECT available_balance FROM emp_vacation_balance WHERE emp_id = '{$empId}' ORDER BY id DESC LIMIT 1");
        if ($balanceQuery && $balanceRow = mysqli_fetch_assoc($balanceQuery)) {
            $currentBalance = (float)($balanceRow['available_balance'] ?? 0);
        }
        
        // Calculate accrual from today to termination date
        $today = new DateTime();
        $accruedDays = 0;
        if ($endDate > $today) {
            $daysToAdd = $today->diff($endDate)->days;
            $accruedDays = $daysToAdd * $dailyRate;
        } elseif ($endDate < $today) {
            $daysToSubtract = $endDate->diff($today)->days;
            $accruedDays = -($daysToSubtract * $dailyRate);
        }
        
        $totalVacationDays = $currentBalance + $accruedDays;
        
        // Calculate vacation salary
        $vacationSalary = ($vacationSalaryBase / 30) * $totalVacationDays;
        
        // Total settlement
        $totalSettlement = $eosAmount + $vacationSalary;
        
        // Build row data based on requested columns
        $rowData = [];
        foreach ($columns as $col) {
            switch ($col) {
                case 'emp_id':
                    $rowData[$col] = $empId;
                    break;
                case 'emp_name':
                    $rowData[$col] = getDisplayName($row['name']);
                    break;
                case 'dept':
                    // Use getDisplayName for consistent translation handling
                    $rowData[$col] = getDisplayName($row['dept_name']);
                    break;
                case 'company_name':
                    $rowData[$col] = ($is_rtl ?? false) && !empty($row['comp_name_ar']) ? $row['comp_name_ar'] : ($row['comp_name'] ?? '');
                    break;
                case 'joining_date':
                    $rowData[$col] = $joiningDate;
                    break;
                case 'termination_date':
                    $rowData[$col] = $terminationDate;
                    break;
                case 'service_duration':
                    $rowData[$col] = "{$serviceYears} " . __('years') . ", {$serviceMonths} " . __('months') . ", {$serviceDays} " . __('days');
                    break;
                case 'basic_salary':
                    $rowData[$col] = number_format($basic, 2);
                    break;
                case 'total_salary':
                    $rowData[$col] = number_format($totalSalary, 2);
                    break;
                case 'eos_amount':
                    $rowData[$col] = number_format($eosAmount, 2);
                    break;
                case 'vacation_days':
                    $rowData[$col] = number_format($totalVacationDays, 2);
                    break;
                case 'vacation_salary':
                    $rowData[$col] = number_format($vacationSalary, 2);
                    break;
                case 'total_settlement':
                    $rowData[$col] = number_format($totalSettlement, 2);
                    break;
                default:
                    $rowData[$col] = '';
            }
        }
        
        // Add print button for EOS PDF
        $empId = $row['emp_id'];
        $printLabel = function_exists('__') ? __('print') : 'Print';
        $rowData['actions'] = '<a href="eos_pdf.php?emp_id=' . htmlspecialchars($empId) . '" target="_blank" class="btn btn-sm btn-danger"><i class="mdi mdi-file-pdf"></i> ' . htmlspecialchars($printLabel) . ' PDF</a>';
        
        $data[] = $rowData;
    }
    
    return ['data' => $data, 'headers' => $headers];
}

// Exit Settlement Report - Shows detailed settlement for all terminated employees
function generateExitSettlementReport($conDB, $columns, $departments, $dateFrom, $dateTo, $hasFullAccess, $userDept, $employeeId = '', $companies = [], $countries = []) {
    global $is_rtl;
    
    // Build WHERE clause
    $where = ['e.status = 0']; // Only terminated employees
    
    // Department fallback filter (only when no explicit scope restrictions are configured)
    $hasSpecialRestrictions = !empty($_SESSION['allowed_employees_array']) ||
                            !empty($_SESSION['allowed_departments_array']) ||
                            !empty($_SESSION['allowed_companies_array']);

    if (!$hasFullAccess && !$hasSpecialRestrictions && !empty($userDept)) {
        $where[] = "e.dept = '" . mysqli_real_escape_string($conDB, $userDept) . "'";
    } elseif (!$hasFullAccess && !$hasSpecialRestrictions && !empty($departments)) {
        $deptList = array_map(function($d) use ($conDB) { return "'" . mysqli_real_escape_string($conDB, $d) . "'"; }, $departments);
        $where[] = "e.dept IN (" . implode(',', $deptList) . ")";
    }
    
    // Date filter (based on end_date / termination_date)
    if (!empty($dateFrom)) {
        $where[] = "eos.end_date >= '" . mysqli_real_escape_string($conDB, $dateFrom) . "'";
    }
    if (!empty($dateTo)) {
        $where[] = "eos.end_date <= '" . mysqli_real_escape_string($conDB, $dateTo) . "'";
    }
    
    // Company filter - restrict by accessible companies
    $company_filter = getCompanyFilterSQL('e.comp_no', true);
    if (!empty($company_filter)) {
        $where[] = substr($company_filter, 5); // Remove " AND " prefix
    }
    
    // Department filter - restrict by accessible departments
    $department_filter = getDepartmentFilterSQL('e.dept', true);
    if (!empty($department_filter)) {
        $where[] = substr($department_filter, 5); // Remove " AND " prefix
    }

    // Employee filter
    if (!empty($employeeId)) {
        $where[] = "e.emp_id = '" . mysqli_real_escape_string($conDB, $employeeId) . "'";
    }
    
    applyEmployeeCompanyCountryFilter($conDB, $where, $companies, $countries);
    $whereClause = implode(' AND ', $where);
    
    // Fetch terminated employees with their settlement details
    $sql = "SELECT 
                e.emp_id,
                e.name,
                d.dep_nme AS dept,
                eos.joining_date,
                eos.end_date,
                eos.leaving_reason,
                eos.t_years,
                eos.t_months,
                eos.t_days,
                s.basic,
                s.housing,
                s.transport,
                s.food,
                s.misc,
                s.cashier,
                s.fuel,
                s.tel,
                s.guard,
                s.other,
                eos.eos_amount,
                eos.anul_vac_days,
                eos.anul_vac_salry,
                eos.curt_month_salry,
                eos.gosi_deduction,
                eos.deduction_hours,
                eos.deduct,
                eos.overtime_hours,
                eos.overtime_days,
                eos.net_payment,
                b.name AS bank_name,
                emp.payment_type,
                emp.iban
            FROM emp_eos eos
            INNER JOIN employees e ON eos.emp_id = e.emp_id
            LEFT JOIN employees emp ON emp.emp_id = e.emp_id
            LEFT JOIN emp_salary s ON s.emp_id = e.emp_id AND s.status = 1
            LEFT JOIN department d ON e.dept = d.id
            LEFT JOIN bank_list b ON e.bank_name = b.id
            WHERE $whereClause
            ORDER BY eos.end_date DESC";
    
    $query = mysqli_query($conDB, $sql);
    if (!$query) {
        throw new Exception('Exit Settlement query error: ' . mysqli_error($conDB));
    }
    
    $data = [];
    $headers = [];
    
    // Get headers
    foreach ($columns as $col) {
        $headers[] = getColumnLabel($col);
    }
    
    // Add Actions header
    $headers[] = function_exists('__') ? __('actions') : 'Actions';
    
    // Build row data for each employee
    while ($row = mysqli_fetch_assoc($query)) {
        $empId = $row['emp_id'];
        $basic = (float)($row['basic'] ?? 0);
        $housing = (float)($row['housing'] ?? 0);
        $calculatedHousing = ($housing == 0 && $basic > 0) ? ($basic / 12) * 2 : $housing;
        
        // Calculate total monthly salary
        $totalMonthlySalary = $basic + $calculatedHousing + 
                              (float)($row['transport'] ?? 0) +
                              (float)($row['food'] ?? 0) +
                              (float)($row['misc'] ?? 0) +
                              (float)($row['cashier'] ?? 0) +
                              (float)($row['fuel'] ?? 0) +
                              (float)($row['tel'] ?? 0) +
                              (float)($row['guard'] ?? 0) +
                              (float)($row['other'] ?? 0);
        
        // Calculate deductions
        $gosiDeduction = (float)($row['gosi_deduction'] ?? 0);
        $absentDaysDeduction = (float)($row['deduction_hours'] ?? 0);
        $loanDeduction = (float)($row['deduct'] ?? 0);
        $totalDeductions = $gosiDeduction + $absentDaysDeduction + $loanDeduction;
        
        // Calculate overtime earnings (if applicable)
        $overtimeHours = (float)($row['overtime_hours'] ?? 0);
        $overtimeDays = (float)($row['overtime_days'] ?? 0);
        $hourlyRate = $totalMonthlySalary / 240; // Saudi standard: 240 working hours per month
        $overtimeEarnings = ($overtimeHours * $hourlyRate) + ($overtimeDays * $totalMonthlySalary);
        
        // Payment type mapping
        $paymentTypeMap = [
            '1' => 'Bank Transfer',
            '2' => 'Cash Payment',
            '3' => 'On Hold'
        ];
        $paymentType = $paymentTypeMap[$row['payment_type']] ?? 'Not Specified';
        
        // Service duration string
        $serviceDuration = "{$row['t_years']} " . __('years') . ", {$row['t_months']} " . __('months') . ", {$row['t_days']} " . __('days');
        
        // Build row data based on requested columns
        $rowData = [];
        foreach ($columns as $col) {
            switch ($col) {
                case 'emp_id':
                    $rowData[$col] = $empId;
                    break;
                case 'emp_name':
                    $rowData[$col] = getDisplayName(parseName($row['name']));
                    break;
                case 'dept':
                    $rowData[$col] = getDisplayName($row['dept']);
                    break;
                case 'joining_date':
                    $rowData[$col] = $row['joining_date'];
                    break;
                case 'termination_date':
                    $rowData[$col] = $row['end_date'];
                    break;
                case 'leaving_reason':
                    $rowData[$col] = getReasonText($row['leaving_reason'], $is_rtl);
                    break;
                case 'service_duration':
                    $rowData[$col] = $serviceDuration;
                    break;
                case 'basic_salary':
                    $rowData[$col] = number_format($basic, 2);
                    break;
                case 'total_monthly_salary':
                    $rowData[$col] = number_format($totalMonthlySalary, 2);
                    break;
                case 'eos_amount':
                    $rowData[$col] = number_format((float)($row['eos_amount'] ?? 0), 2);
                    break;
                case 'vacation_days':
                    $rowData[$col] = number_format((float)($row['anul_vac_days'] ?? 0), 2);
                    break;
                case 'vacation_salary':
                    $rowData[$col] = number_format((float)($row['anul_vac_salry'] ?? 0), 2);
                    break;
                case 'last_month_salary':
                    $rowData[$col] = number_format((float)($row['curt_month_salry'] ?? 0), 2);
                    break;
                case 'gosi_deduction':
                    $rowData[$col] = number_format($gosiDeduction, 2);
                    break;
                case 'absent_days_deduction':
                    $rowData[$col] = number_format($absentDaysDeduction, 2);
                    break;
                case 'loan_deduction':
                    $rowData[$col] = number_format($loanDeduction, 2);
                    break;
                case 'total_deductions':
                    $rowData[$col] = number_format($totalDeductions, 2);
                    break;
                case 'overtime_earnings':
                    $rowData[$col] = number_format($overtimeEarnings, 2);
                    break;
                case 'net_payment':
                    $rowData[$col] = number_format((float)($row['net_payment'] ?? 0), 2);
                    break;
                case 'bank_name':
                    $rowData[$col] = getDisplayName($row['bank_name']) ?? '';
                    break;
                case 'payment_type':
                    $rowData[$col] = $paymentType;
                    break;
                case 'payment_status':
                    $rowData[$col] = 'Completed'; // From emp_eos settled employees
                    break;
                default:
                    $rowData[$col] = '';
            }
        }
        
        // Add print button for EOS PDF
        $printLabel = function_exists('__') ? __('print') : 'Print';
        $rowData['actions'] = '<a href="eos_pdf.php?emp_id=' . htmlspecialchars($empId) . '" target="_blank" class="btn btn-sm btn-danger"><i class="mdi mdi-file-pdf"></i> ' . htmlspecialchars($printLabel) . ' PDF</a>';
        
        $data[] = $rowData;
    }
    
    return ['data' => $data, 'headers' => $headers];
}

// Department Comparison Report
function generateDepartmentComparisonReport($conDB, $columns, $departments, $hasFullAccess, $userDept) {
    $data = [];
    $headers = [];
    
    // Get headers
    foreach ($columns as $col) {
        $headers[] = getColumnLabel($col);
    }
    
    // Build department fallback filter (only when no explicit scope restrictions are configured)
    $whereClause = '';
    $hasSpecialRestrictions = !empty($_SESSION['allowed_employees_array']) ||
                            !empty($_SESSION['allowed_departments_array']) ||
                            !empty($_SESSION['allowed_companies_array']);

    if (!$hasFullAccess && !$hasSpecialRestrictions && !empty($userDept)) {
        // Non-admin users can only see their own department
        $whereClause = "WHERE d.id = '" . mysqli_real_escape_string($conDB, $userDept) . "'";
    } elseif (!$hasFullAccess && !$hasSpecialRestrictions && !empty($departments)) {
        // Filter by selected departments
        $deptList = array_map(function($d) use ($conDB) {
            return "'" . mysqli_real_escape_string($conDB, $d) . "'";
        }, $departments);
        $whereClause = "WHERE d.id IN (" . implode(',', $deptList) . ")";
    }
    
    // Add company filter
    $company_filter = getCompanyFilterSQL('e.comp_no', true);
    if (!empty($company_filter)) {
        $whereClause = !empty($whereClause) ? $whereClause . " AND " . substr($company_filter, 5) : "WHERE " . substr($company_filter, 5);
    }
    
    // Add department filter
    $department_filter = getDepartmentFilterSQL('e.dept', true);
    if (!empty($department_filter)) {
        $whereClause = !empty($whereClause) ? $whereClause . " AND " . substr($department_filter, 5) : "WHERE " . substr($department_filter, 5);
    }
    
    // NOTE: For reports, we use company and department filters, not individual employee ID filter
    // This allows employees to see all colleagues in their department/company
    $subCompanyScopeSql = getCompanyFilterSQL('e.comp_no', true);
    $subDepartmentScopeSql = getDepartmentFilterSQL('e.dept', true);
    
    // Get department statistics
    $sql = "SELECT 
                d.dep_nme,
                d.id,
                COUNT(DISTINCT e.id) as total_employees,
                COUNT(DISTINCT CASE WHEN e.status = 1 THEN e.id END) as active_employees,
                COUNT(DISTINCT CASE WHEN e.status = 0 THEN e.id END) as inactive_employees,
                COALESCE(SUM(CAST(e.salary AS DECIMAL(10,2))), 0) as total_salary,
                COALESCE(AVG(CAST(e.salary AS DECIMAL(10,2))), 0) as avg_salary,
                COALESCE(AVG(TIMESTAMPDIFF(YEAR, STR_TO_DATE(e.joining_date, '%Y-%m-%d'), CURDATE())), 0) as avg_service_years
            FROM department d
            LEFT JOIN employees e ON d.id = e.dept
            " . $whereClause . "
            GROUP BY d.id, d.dep_nme
            ORDER BY d.dep_nme";
    
    $result = mysqli_query($conDB, $sql);
    if (!$result) {
        throw new Exception('Department Comparison query error: ' . mysqli_error($conDB));
    }
    
    while ($row = mysqli_fetch_assoc($result)) {
        $deptId = $row['id'];
        if (isset($row['dep_nme'])) {
            $row['dep_nme'] = getDisplayName($row['dep_nme']);
        }
        $deptRow = [];
        
        foreach ($columns as $col) {
            switch ($col) {
                case 'department':
                    $deptRow[$col] = $row['dep_nme'];
                    break;
                case 'total_employees':
                    $deptRow[$col] = $row['total_employees'];
                    break;
                case 'active_employees':
                    $deptRow[$col] = $row['active_employees'];
                    break;
                case 'inactive_employees':
                    $deptRow[$col] = $row['inactive_employees'];
                    break;
                case 'total_salary':
                    $deptRow[$col] = number_format($row['total_salary'], 2);
                    break;
                case 'avg_salary':
                    $deptRow[$col] = number_format($row['avg_salary'], 2);
                    break;
                case 'avg_service_years':
                    $deptRow[$col] = number_format($row['avg_service_years'], 2);
                    break;
                case 'pending_vacations':
                    // Get pending vacation count
                    $vacQuery = mysqli_query($conDB, "SELECT COUNT(*) as cnt FROM emp_vacation v 
                                                      INNER JOIN employees e ON v.emp_id = e.emp_id 
                                                      WHERE e.dept = '$deptId' AND v.current_status = 'pending' {$subCompanyScopeSql} {$subDepartmentScopeSql}");
                    $vacRow = mysqli_fetch_assoc($vacQuery);
                    $deptRow[$col] = $vacRow['cnt'];
                    break;
                case 'approved_vacations':
                    // Get approved vacation count (this year)
                    $vacQuery = mysqli_query($conDB, "SELECT COUNT(*) as cnt FROM emp_vacation v 
                                                      INNER JOIN employees e ON v.emp_id = e.emp_id 
                                                      WHERE e.dept = '$deptId' 
                                                      AND v.current_status = 'approved' 
                                                      AND YEAR(v.start_date) = YEAR(CURDATE()) {$subCompanyScopeSql} {$subDepartmentScopeSql}");
                    $vacRow = mysqli_fetch_assoc($vacQuery);
                    $deptRow[$col] = $vacRow['cnt'];
                    break;
                case 'active_loans':
                    // Get active loan count
                    $loanQuery = mysqli_query($conDB, "SELECT COUNT(*) as cnt FROM emp_loan l 
                                                       INNER JOIN employees e ON l.emp_id = e.emp_id 
                                                       WHERE e.dept = '$deptId' AND l.status = 'active' {$subCompanyScopeSql} {$subDepartmentScopeSql}");
                    $loanRow = mysqli_fetch_assoc($loanQuery);
                    $deptRow[$col] = $loanRow['cnt'];
                    break;
                case 'total_loan_amount':
                    // Get total active loan amount
                    $loanQuery = mysqli_query($conDB, "SELECT COALESCE(SUM(CAST(l.final_approved_amount AS DECIMAL(10,2))), 0) as total 
                                                       FROM emp_loan l 
                                                       INNER JOIN employees e ON l.emp_id = e.emp_id 
                                                       WHERE e.dept = '$deptId' AND l.status = 'active' {$subCompanyScopeSql} {$subDepartmentScopeSql}");
                    $loanRow = mysqli_fetch_assoc($loanQuery);
                    $deptRow[$col] = number_format($loanRow['total'], 2);
                    break;
            }
        }
        
        $data[] = $deptRow;
    }
    
    return ['data' => $data, 'headers' => $headers];
}

function generateCountryCompanyComparisonReport($conDB, $columns, $departments, $companies, $hasFullAccess, $userDept) {
    $data = [];
    $headers = [];

    foreach ($columns as $col) {
        $headers[] = getColumnLabel($col);
    }

    $whereClause = '';
    $hasSpecialRestrictions = !empty($_SESSION['allowed_employees_array']) ||
                            !empty($_SESSION['allowed_departments_array']) ||
                            !empty($_SESSION['allowed_companies_array']);

    if (!$hasFullAccess && !$hasSpecialRestrictions && !empty($userDept)) {
        $whereClause = "WHERE e.dept = '" . mysqli_real_escape_string($conDB, $userDept) . "'";
    } elseif (!$hasFullAccess && !$hasSpecialRestrictions && !empty($departments)) {
        $deptList = array_map(function($d) use ($conDB) {
            return "'" . mysqli_real_escape_string($conDB, $d) . "'";
        }, $departments);
        $whereClause = "WHERE e.dept IN (" . implode(',', $deptList) . ")";
    }

    // User-selected company filter (from the Select Companies dropdown)
    if (!empty($companies) && !in_array('all', $companies, true)) {
        $compList = array_map(function($c) use ($conDB) {
            return "'" . mysqli_real_escape_string($conDB, $c) . "'";
        }, $companies);
        $compClause = "e.comp_no IN (" . implode(',', $compList) . ")";
        $whereClause = !empty($whereClause) ? $whereClause . " AND " . $compClause : "WHERE " . $compClause;
    }

    $company_filter = getCompanyFilterSQL('e.comp_no', true);
    if (!empty($company_filter)) {
        $whereClause = !empty($whereClause) ? $whereClause . " AND " . substr($company_filter, 5) : "WHERE " . substr($company_filter, 5);
    }

    $department_filter = getDepartmentFilterSQL('e.dept', true);
    if (!empty($department_filter)) {
        $whereClause = !empty($whereClause) ? $whereClause . " AND " . substr($department_filter, 5) : "WHERE " . substr($department_filter, 5);
    }

    // Grouped by country + company together, so each row shows the employee
    // count for a given nationality within a given company.
    $sql = "SELECT
                co.name AS country_name,
                co.name_ar AS country_name_ar,
                cm.comp_name,
                cm.comp_name_ar,
                COUNT(DISTINCT e.id) as total_employees,
                COUNT(DISTINCT CASE WHEN e.status = 1 THEN e.id END) as active_employees,
                COUNT(DISTINCT CASE WHEN e.status = 0 THEN e.id END) as inactive_employees
            FROM employees e
            LEFT JOIN countries co ON e.country = co.id
            LEFT JOIN companies cm ON e.comp_no = cm.comp_id
            " . $whereClause . "
            GROUP BY co.id, co.name, co.name_ar, cm.comp_id, cm.comp_name, cm.comp_name_ar
            ORDER BY cm.comp_name, total_employees DESC";

    $result = mysqli_query($conDB, $sql);
    if (!$result) {
        throw new Exception('Country/Company Comparison query error: ' . mysqli_error($conDB));
    }

    $isRtl = $GLOBALS['is_rtl'] ?? false;
    while ($row = mysqli_fetch_assoc($result)) {
        $countryRow = [];
        $countryName = ($isRtl && !empty($row['country_name_ar'])) ? $row['country_name_ar'] : $row['country_name'];
        $companyName = ($isRtl && !empty($row['comp_name_ar'])) ? $row['comp_name_ar'] : $row['comp_name'];

        foreach ($columns as $col) {
            switch ($col) {
                case 'country':
                    $countryRow[$col] = $countryName;
                    break;
                case 'company':
                    $countryRow[$col] = $companyName;
                    break;
                case 'total_employees':
                    $countryRow[$col] = $row['total_employees'];
                    break;
                case 'active_employees':
                    $countryRow[$col] = $row['active_employees'];
                    break;
                case 'inactive_employees':
                    $countryRow[$col] = $row['inactive_employees'];
                    break;
            }
        }

        $data[] = $countryRow;
    }

    return ['data' => $data, 'headers' => $headers];
}

function generateCustomReport($conDB, $columns, $tableNames, $departments = [], $dateFrom = '', $dateTo = '', $status = '') {
    if (empty($tableNames) || empty($columns)) {
        throw new Exception('Table name(s) and columns are required');
    }
    
    // Normalize tableNames to array
    if (!is_array($tableNames)) {
        $tableNames = [$tableNames];
    }
    
    // Validate table names (check if tables exist)
    $validatedTables = [];
    foreach ($tableNames as $tableName) {
        if (empty($tableName)) continue;
        
        $validateQuery = mysqli_query($conDB, "SHOW TABLES LIKE '" . mysqli_real_escape_string($conDB, $tableName) . "'");
        if ($validateQuery && mysqli_num_rows($validateQuery) > 0) {
            $validatedTables[] = $tableName;
        }
    }
    
    if (empty($validatedTables)) {
        throw new Exception('No valid tables provided');
    }
    
    // Reorder so employees becomes primary if selected
    if (in_array('employees', $validatedTables)) {
        $validatedTables = array_unique(array_merge(['employees'], $validatedTables));
    }

    $primaryTable = $validatedTables[0];

    // Collect columns for each table
    $tableColumns = [];
    foreach ($validatedTables as $tableName) {
        $columnsQuery = mysqli_query($conDB, "SHOW COLUMNS FROM `" . mysqli_real_escape_string($conDB, $tableName) . "`");
        $tableColumns[$tableName] = [];
        while ($row = mysqli_fetch_assoc($columnsQuery)) {
            $tableColumns[$tableName][] = $row['Field'];
        }
    }
    
    // Auto-include employees table for department/status filtering if not already
    // selected but any selected table has emp_id
    $needsEmployeesForFiltering = false;
    if (!in_array('employees', $validatedTables) && ((!empty($departments) && !in_array('all', $departments)) || $status !== '')) {
        foreach ($validatedTables as $tbl) {
            if (isset($tableColumns[$tbl]) && in_array('emp_id', $tableColumns[$tbl])) {
                $needsEmployeesForFiltering = true;
                break;
            }
        }
    }
    
    if ($needsEmployeesForFiltering) {
        // Add employees table columns for filtering only
        $columnsQuery = mysqli_query($conDB, "SHOW COLUMNS FROM `employees`");
        $tableColumns['employees'] = [];
        while ($row = mysqli_fetch_assoc($columnsQuery)) {
            $tableColumns['employees'][] = $row['Field'];
        }
    }

    // Build smarter joins
    $joins = [];
    $hasEmployees = in_array('employees', $validatedTables) || $needsEmployeesForFiltering;
    
    // Known tables with emp_id that join to employees
    $empIdTables = ['emp_salary', 'emp_vacation', 'emp_loan', 'emp_docu', 'emp_attendance', 'emp_evaluation', 'emp_resignation', 'emp_eos'];
    
    // If employees needed for filtering but not in selected tables, add it as a join
    if ($needsEmployeesForFiltering) {
        // Join employees to primary table via emp_id
        if (in_array('emp_id', $tableColumns[$primaryTable]) && in_array('emp_id', $tableColumns['employees'])) {
            $joins[] = "LEFT JOIN `employees` ON `" . mysqli_real_escape_string($conDB, $primaryTable) . "`.`emp_id` = `employees`.`emp_id`";
        }
    }
    
    foreach ($validatedTables as $tableName) {
        if ($tableName === $primaryTable) { continue; }
        // Determine join strategy
        if ($hasEmployees && ($primaryTable === 'employees' || $needsEmployeesForFiltering) && $tableName !== 'employees') {
            // Join other table to employees via emp_id
            if (in_array('emp_id', $tableColumns[$tableName]) && in_array('emp_id', $tableColumns['employees'])) {
                $joins[] = "LEFT JOIN `" . mysqli_real_escape_string($conDB, $tableName) . "` ON `employees`.`emp_id` = `" . $tableName . "`.`emp_id`";
            } elseif (in_array('employee_id', $tableColumns[$tableName]) && (in_array('id', $tableColumns['employees']) || in_array('employee_id', $tableColumns['employees']))) {
                // Fallback employee_id mapping
                $leftKey = in_array('employee_id', $tableColumns['employees']) ? 'employee_id' : 'id';
                $joins[] = "LEFT JOIN `" . mysqli_real_escape_string($conDB, $tableName) . "` ON `employees`.`" . $leftKey . "` = `" . $tableName . "`.`employee_id`";
            } elseif (in_array('id', $tableColumns[$tableName]) && in_array('id', $tableColumns['employees'])) {
                // Last resort id=id
                $joins[] = "LEFT JOIN `" . mysqli_real_escape_string($conDB, $tableName) . "` ON `employees`.`id` = `" . $tableName . "`.`id`";
            }
        } else {
            // Primary table is not employees or employees absent
            // Attempt join via emp_id if both sides have it
            if (in_array('emp_id', $tableColumns[$primaryTable]) && in_array('emp_id', $tableColumns[$tableName])) {
                $joins[] = "LEFT JOIN `" . mysqli_real_escape_string($conDB, $tableName) . "` ON `" . $primaryTable . "`.`emp_id` = `" . $tableName . "`.`emp_id`";
            } elseif (in_array('id', $tableColumns[$primaryTable]) && in_array('id', $tableColumns[$tableName])) {
                $joins[] = "LEFT JOIN `" . mysqli_real_escape_string($conDB, $tableName) . "` ON `" . $primaryTable . "`.`id` = `" . $tableName . "`.`id`";
            }
        }
    }
    
    // Filter and validate selected columns
    $safeColumns = [];
    $columnAliases = [];
    // Track extra joins needed when selecting human-readable fields from employees
    $extraJoins = [];
    $addedCustomJoins = [];
    $useArabic = isset($is_rtl) ? (bool)$is_rtl : (isset($GLOBALS['is_rtl']) ? (bool)$GLOBALS['is_rtl'] : false);

    $addEmployeeReadableColumn = function($col_name, $aliasName) use (&$extraJoins, &$addedCustomJoins, &$safeColumns, &$columnAliases, $useArabic) {
        switch ($col_name) {
            case 'dept':
                if (empty($addedCustomJoins['department'])) {
                    $extraJoins[] = "LEFT JOIN `department` d ON `employees`.`dept` = d.`id`";
                    $addedCustomJoins['department'] = true;
                }
                $deptField = $useArabic ? 'dep_nme_ar' : 'dep_nme';
                $safeColumns[] = "d.`$deptField` AS `{$aliasName}`";
                $columnAliases[] = $aliasName;
                break;
            case 'sectin_nme':
                if (empty($addedCustomJoins['section'])) {
                    $extraJoins[] = "LEFT JOIN `section` s ON `employees`.`sectin_nme` = s.`id`";
                    $addedCustomJoins['section'] = true;
                }
                $safeColumns[] = "s.`section_name` AS `{$aliasName}`";
                $columnAliases[] = $aliasName;
                break;
            case 'country':
                if (empty($addedCustomJoins['countries'])) {
                    $extraJoins[] = "LEFT JOIN `countries` c ON `employees`.`country` = c.`id`";
                    $addedCustomJoins['countries'] = true;
                }
                $countryField = $useArabic ? 'name_ar' : 'name';
                $safeColumns[] = "c.`$countryField` AS `{$aliasName}`";
                $columnAliases[] = $aliasName;
                break;
            case 'bank_name':
                if (empty($addedCustomJoins['bank_list'])) {
                    $extraJoins[] = "LEFT JOIN `bank_list` b ON `employees`.`bank_name` = b.`id`";
                    $addedCustomJoins['bank_list'] = true;
                }
                $bankField = $useArabic ? 'bank_name_ar' : 'name';
                $safeColumns[] = "b.`$bankField` AS `{$aliasName}`";
                $columnAliases[] = $aliasName;
                break;
            case 'vac_period':
                if (empty($addedCustomJoins['contract_period'])) {
                    $extraJoins[] = "LEFT JOIN `contract_period` cp ON `employees`.`vac_period` = cp.`id`";
                    $addedCustomJoins['contract_period'] = true;
                }
                $safeColumns[] = "cp.`period` AS `{$aliasName}`";
                $columnAliases[] = $aliasName;
                break;
            case 'actual_job':
                if (empty($addedCustomJoins['ac_jobs'])) {
                    $extraJoins[] = "LEFT JOIN `ac_jobs` j ON `employees`.`actual_job` = j.`id`";
                    $addedCustomJoins['ac_jobs'] = true;
                }
                $jobField = $useArabic ? 'job_ar' : 'job';
                $safeColumns[] = "j.`$jobField` AS `{$aliasName}`";
                $columnAliases[] = $aliasName;
                break;
            case 'emp_sup_type':
                if (empty($addedCustomJoins['sponsorship'])) {
                    $extraJoins[] = "LEFT JOIN `sponsorship` sp ON `employees`.`emp_sup_type` = sp.`id`";
                    $addedCustomJoins['sponsorship'] = true;
                }
                $safeColumns[] = "sp.`sponsor` AS `{$aliasName}`";
                $columnAliases[] = $aliasName;
                break;
            case 'comp_no':
                if (empty($addedCustomJoins['companies'])) {
                    $extraJoins[] = "LEFT JOIN `companies` co ON `employees`.`comp_no` = co.`comp_id`";
                    $addedCustomJoins['companies'] = true;
                }
                $compField = $useArabic ? 'comp_name_ar' : 'comp_name';
                $safeColumns[] = "co.`$compField` AS `{$aliasName}`";
                $columnAliases[] = $aliasName;
                break;
            case 'c_email':
                if (empty($addedCustomJoins['admin_login'])) {
                    $extraJoins[] = "LEFT JOIN `admin_login` al ON `employees`.`emp_id` = al.`emp_id`";
                    $addedCustomJoins['admin_login'] = true;
                }
                $safeColumns[] = "COALESCE(al.`email`, `employees`.`c_email`) AS `{$aliasName}`";
                $columnAliases[] = $aliasName;
                break;
            case 'sex':
                $safeColumns[] = "CASE WHEN `employees`.`sex` = '1' THEN 'Male' WHEN `employees`.`sex` = '2' THEN 'Female' ELSE `employees`.`sex` END AS `{$aliasName}`";
                $columnAliases[] = $aliasName;
                break;
            default:
                $safeColumns[] = "`employees`.`{$col_name}` AS `{$aliasName}`";
                $columnAliases[] = $aliasName;
                break;
        }
    };

    foreach ($columns as $col) {
        // Skip ID columns
        if ($col === 'id' || strtolower($col) === 'id' || preg_match('/\.id$/i', $col)) {
            continue;
        }
        
        $colParts = explode('.', $col);
        if (count($colParts) === 2) {
            // Prefixed column (table.column)
            list($tbl, $col_name) = $colParts;
            
            // Skip ID columns
            if (strtolower($col_name) === 'id') {
                continue;
            }
            
            if (in_array($tbl, $validatedTables) && in_array($col_name, $tableColumns[$tbl])) {
                // Special mapping for employees lookup fields to show readable names
                if ($tbl === 'employees') {
                    $aliasName = str_replace('.', '_', $col); // e.g., employees_dept
                    $addEmployeeReadableColumn($col_name, $aliasName);
                } else {
                    // Default behavior for non-employees tables
                    $safeColumn = "`" . mysqli_real_escape_string($conDB, $tbl) . "`.`" . mysqli_real_escape_string($conDB, $col_name) . "`";
                    $safeColumns[] = $safeColumn . " AS `" . str_replace('.', '_', $col) . "`";
                    $columnAliases[] = str_replace('.', '_', $col);
                }
            }
        } else {
            // Non-prefixed column - check in primary table
            if (in_array($col, $tableColumns[$primaryTable])) {
                if ($primaryTable === 'employees') {
                    // When Employees is selected alone in Custom Report, show readable labels instead of raw IDs.
                    $addEmployeeReadableColumn($col, $col);
                } else {
                    $safeColumn = "`" . mysqli_real_escape_string($conDB, $primaryTable) . "`.`" . mysqli_real_escape_string($conDB, $col) . "`";
                    $safeColumns[] = $safeColumn;
                    $columnAliases[] = $col;
                }
            }
        }
    }
    
    if (empty($safeColumns)) {
        throw new Exception('No valid columns selected');
    }
    
    // Build department filter (always anchor on employees if present)
    $whereClauses = [];
    if (!empty($departments) && !in_array('all', $departments)) {
        $deptAnchorTable = $hasEmployees ? 'employees' : $primaryTable;
        $deptCols = isset($tableColumns[$deptAnchorTable]) ? $tableColumns[$deptAnchorTable] : [];
        $deptColumnFound = null;
        foreach (['dept_id','department','dept'] as $candidate) {
            if (in_array($candidate, $deptCols)) { $deptColumnFound = $candidate; break; }
        }
        if ($deptColumnFound) {
            $values = [];
            foreach ($departments as $d) {
                $trimmed = trim((string)$d);
                if ($trimmed !== '' && ctype_digit($trimmed)) {
                    $values[] = (int)$trimmed; // numeric no quotes
                } else {
                    $values[] = "'" . mysqli_real_escape_string($conDB, $trimmed) . "'";
                }
            }
            $whereClauses[] = "`" . $deptAnchorTable . "`.`" . $deptColumnFound . "` IN (" . implode(',', $values) . ")";
        }
    }

    // Status filter - only meaningful (and only offered by the UI) when the
    // employees table is one of the selected/auto-joined tables and has a
    // 'status' column (Active/Inactive, same 1/0 values generateEmployeeReport uses).
    if ($status !== '' && $hasEmployees && isset($tableColumns['employees']) && in_array('status', $tableColumns['employees'])) {
        $whereClauses[] = "`employees`.`status` = '" . mysqli_real_escape_string($conDB, $status) . "'";
    }

    // Add date filters if provided
    if (!empty($dateFrom) || !empty($dateTo)) {
        // Find date columns in selected tables
        $dateColumns = ['created_at', 'updated_at', 'start_date', 'end_date', 'joining_date', 'date', 'month_year'];
        $dateColumnFound = null;
        $dateTableFound = null;
        
        // Check primary table first
        foreach ($dateColumns as $dateCol) {
            if (in_array($dateCol, $tableColumns[$primaryTable])) {
                $dateColumnFound = $dateCol;
                $dateTableFound = $primaryTable;
                break;
            }
        }
        
        // If not in primary, check other tables
        if (!$dateColumnFound) {
            foreach ($validatedTables as $tbl) {
                foreach ($dateColumns as $dateCol) {
                    if (in_array($dateCol, $tableColumns[$tbl])) {
                        $dateColumnFound = $dateCol;
                        $dateTableFound = $tbl;
                        break 2;
                    }
                }
            }
        }
        
        // Apply date filters if we found a date column
        if ($dateColumnFound && $dateTableFound) {
            if (!empty($dateFrom)) {
                $whereClauses[] = "`" . $dateTableFound . "`.`" . $dateColumnFound . "` >= '" . mysqli_real_escape_string($conDB, $dateFrom) . "'";
            }
            if (!empty($dateTo)) {
                $whereClauses[] = "`" . $dateTableFound . "`.`" . $dateColumnFound . "` <= '" . mysqli_real_escape_string($conDB, $dateTo) . "'";
            }
        }
    }
    
    // Add company filter for custom reports
    $company_filter = getCompanyFilterSQL('employees.comp_no', true);
    if (!empty($company_filter) && $hasEmployees) {
        // Add company filter if employees table is involved
        $whereClauses[] = substr($company_filter, 5); // Remove " AND " prefix
    }

    // Add allowed department filter for custom reports
    $department_filter = getDepartmentFilterSQL('employees.dept', true);
    if (!empty($department_filter) && $hasEmployees) {
        $whereClauses[] = substr($department_filter, 5); // Remove " AND " prefix
    }
    
    $whereClause = !empty($whereClauses) ? 'WHERE ' . implode(' AND ', $whereClauses) : '';
    
    // Build the query
    $columnList = implode(', ', $safeColumns);
    // Merge any additional joins required for readable employees columns
    if (!empty($extraJoins)) {
        $joins = array_merge($joins, $extraJoins);
    }
    $joinClause = !empty($joins) ? implode(' ', $joins) : '';
    
    $sql = "SELECT " . $columnList . " FROM `" . mysqli_real_escape_string($conDB, $primaryTable) . "` " . $joinClause . " " . $whereClause . " LIMIT 1000";
    
    // Log the SQL query for debugging
    // error_log("Custom Report SQL: " . $sql);
    
    $result = mysqli_query($conDB, $sql);
    if (!$result) {
        // error_log("Custom Report Query Error: " . mysqli_error($conDB));
        throw new Exception('Query error: ' . mysqli_error($conDB));
    }
    
    // Prepare data
    $data = [];
    while ($row = mysqli_fetch_assoc($result)) {
        // Apply getDisplayName() translation to various column types
        foreach ($row as $key => $value) {
            // Translate name-type columns (employee names, manager names, etc.)
            if (
                stripos($key, 'name') !== false || 
                stripos($key, 'emp_name') !== false || 
                stripos($key, 'employee_name') !== false ||
                stripos($key, 'manager_name') !== false ||
                stripos($key, 'acknowledged_by_name') !== false
                ) {
                if (!empty($value) && is_string($value)) {
                    $row[$key] = getDisplayName(parseName($value));
                }
            }
            // Translate department columns
            elseif (stripos($key, 'dept') !== false && !empty($value) && is_string($value)) {
                $row[$key] = getDisplayName($value);
            }
            // Translate company columns
            elseif (stripos($key, 'company') !== false && !empty($value) && is_string($value)) {
                $row[$key] = getDisplayName($value);
            }
            elseif (stripos($key, 'comp_name') !== false && !empty($value) && is_string($value)) {
                $row[$key] = getDisplayName($value);
            }
            // Translate position columns
            elseif (stripos($key, 'position') !== false && !empty($value) && is_string($value)) {
                $row[$key] = getDisplayName($value);
            }
            // Translate job columns (job title, actual_job, etc.)
            elseif (stripos($key, 'job') !== false && !empty($value) && is_string($value)) {
                $row[$key] = getDisplayName($value);
            }
        }
        $data[] = $row;
    }
    
    // Build headers with translation support; normalize keys and add smart fallbacks
    $headers = array_map(function($col) {
        // Normalize spaces/dashes to underscores for key lookup
        $normalized = strtolower(str_replace([' ', '-'], '_', $col));

        // Helper to check if a translation exists and differs from key
        $resolveTranslation = function($key) {
            if (function_exists('__')) {
                $t = __($key);
                if (!empty($t) && $t !== $key) {
                    return $t;
                }
            }
            return null;
        };

        // 1) Try full normalized key (e.g., employees_dept)
        $t1 = $resolveTranslation($normalized);
        if ($t1 !== null) return $t1;

        // 2) If composite key, try last segment as base (e.g., dept)
        if (strpos($normalized, '_') !== false) {
            $parts = explode('_', $normalized);
            $base = end($parts);
            $t2 = $resolveTranslation($base);
            if ($t2 !== null) return $t2;
        }

        // 3) Try original alias as given
        $t3 = $resolveTranslation($col);
        if ($t3 !== null) return $t3;

        // 4) Fallback: humanize alias
        return ucwords(str_replace('_', ' ', $col));
    }, $columnAliases);
    
    // Post-process: remove underscores from all headers for cleaner display
    $headers = array_map(function($h) {
        return str_replace('_', ' ', $h);
    }, $headers);
    
    return ['data' => $data, 'headers' => $headers];
}

// Asset Inventory Report
function generateAssetsReport($conDB, $columns, $departments, $dateFrom, $dateTo, $hasFullAccess, $userDept, $status = '', $employeeId = '', $companies = [], $countries = []) {
    // Build SELECT clause
    $selectCols = ['a.id AS asset_id'];
    
    foreach ($columns as $col) {
        switch ($col) {
            case 'asset_name':
                $selectCols[] = 'a.name AS asset_name';
                break;
            case 'asset_type':
                $selectCols[] = 'a.asset_type';
                break;
            case 'serial_number':
                $selectCols[] = 'ea.serial_number';
                break;
            case 'asset_tag':
                $selectCols[] = 'ea.id AS asset_tag';
                break;
            case 'purchase_date':
                $selectCols[] = 'a.created_at AS purchase_date';
                break;
            case 'asset_status':
                $selectCols[] = 'ea.status AS asset_status';
                break;
            case 'assigned_to':
                $selectCols[] = 'CONCAT(e.emp_id, " - ", e.name) AS assigned_to';
                break;
            case 'assignment_date':
                $selectCols[] = 'ea.assigned_date';
                break;
            case 'return_date':
                $selectCols[] = 'ea.return_date';
                break;
            case 'assignment_status':
                $selectCols[] = 'ea.status AS assignment_status';
                break;
            case 'return_notes':
                $selectCols[] = 'ea.description AS return_notes';
                break;
            case 'employee_dept':
                $selectCols[] = 'd.dep_nme AS employee_dept';
                break;
        }
    }
    
    if (empty($selectCols) || count($selectCols) === 1) {
        // If no valid columns selected, select all by default
        $selectCols = ['a.id AS asset_id', 'a.name AS asset_name', 'a.asset_type', 'ea.serial_number', 'ea.status AS asset_status', 'CONCAT(e.emp_id, " - ", e.name) AS assigned_to', 'ea.assigned_date', 'ea.return_date', 'ea.status AS assignment_status'];
    }
    
    $selectClause = implode(', ', $selectCols);
    
    // Build WHERE clause - only active employees
    $where = ['e.status = 1'];
    
    // Department fallback filter (only when no explicit scope restrictions are configured)
    $hasSpecialRestrictions = !empty($_SESSION['allowed_employees_array']) ||
                            !empty($_SESSION['allowed_departments_array']) ||
                            !empty($_SESSION['allowed_companies_array']);

    if (!$hasFullAccess && !$hasSpecialRestrictions && !empty($userDept)) {
        $where[] = "e.dept = '" . mysqli_real_escape_string($conDB, $userDept) . "'";
    } elseif (!$hasFullAccess && !$hasSpecialRestrictions && !empty($departments)) {
        $dept_list = "'" . implode("','", array_map(function($d) use ($conDB) { return mysqli_real_escape_string($conDB, $d); }, $departments)) . "'";
        $where[] = "e.dept IN ($dept_list)";
    }

    // Company and department scoped filters
    $company_filter = getCompanyFilterSQL('e.comp_no', true);
    if (!empty($company_filter)) {
        $where[] = substr($company_filter, 5);
    }
    $department_filter = getDepartmentFilterSQL('e.dept', true);
    if (!empty($department_filter)) {
        $where[] = substr($department_filter, 5);
    }
    
    // Date filters - check assigned or return date
    if (!empty($dateFrom)) {
        $dateFrom_safe = mysqli_real_escape_string($conDB, $dateFrom);
        $where[] = "(ea.assigned_date >= '$dateFrom_safe' OR ea.return_date >= '$dateFrom_safe')";
    }
    if (!empty($dateTo)) {
        $dateTo_safe = mysqli_real_escape_string($conDB, $dateTo);
        $where[] = "(ea.assigned_date <= '$dateTo_safe' OR ea.return_date <= '$dateTo_safe')";
    }
    
    // Status filter
    if ($status !== '') {
        $status_safe = mysqli_real_escape_string($conDB, $status);
        $where[] = "ea.status = '$status_safe'";
    }

    // Employee filter
    if (!empty($employeeId)) {
        $where[] = "e.emp_id = '" . mysqli_real_escape_string($conDB, $employeeId) . "'";
    }
    
    applyEmployeeCompanyCountryFilter($conDB, $where, $companies, $countries);
    $whereClause = implode(' AND ', $where);
    
    // Build and execute query
    $sql = "SELECT $selectClause 
            FROM assets a
            LEFT JOIN employee_assets ea ON a.id = ea.asset_id
            LEFT JOIN employees e ON ea.emp_id = e.emp_id
            LEFT JOIN department d ON e.dept = d.id
            WHERE $whereClause
            ORDER BY a.name, ea.assigned_date DESC";
    
    $query = mysqli_query($conDB, $sql);
    if (!$query) {
        throw new Exception('Database error: ' . mysqli_error($conDB));
    }
    
    $data = [];
    $headers = [];
    
    // Get headers
    foreach ($columns as $col) {
        $headers[] = getColumnLabel($col);
    }
    
    // Get data
    while ($row = mysqli_fetch_assoc($query)) {

        if (isset($row['asset_name'])) {
            $row['asset_name'] = getDisplayName($row['asset_name']);
        }
        if (isset($row['asset_type'])) {
            $row['asset_type'] = getDisplayName($row['asset_type']);
        }    
        if (isset($row['asset_status'])) {
            $row['asset_status'] = getDisplayName($row['asset_status']);
        }
        if (isset($row['assigned_to'])) {
            $row['assigned_to'] = getDisplayName(parseName($row['assigned_to']));
        }
        if (isset($row['assignment_status'])) {
            $row['assignment_status'] = getDisplayName($row['assignment_status']);
        }
        if (isset($row['return_notes'])) {
            $row['return_notes'] = getDisplayName($row['return_notes']);
        }

        $data[] = $row;
    }
    
    return ['data' => $data, 'headers' => $headers];
}

// Assets List (one row per asset with latest status/holder)
function generateAssetsListReport($conDB, $columns, $departments, $dateFrom, $dateTo, $hasFullAccess, $userDept, $status = '', $employeeId = '', $companies = [], $countries = []) {
    // Check if a specific asset item is selected - if so, show full activity
    $selectedItemId = isset($_POST['assetItemId']) ? intval($_POST['assetItemId']) : 0;
    if ($selectedItemId > 0) {
        return generateAssetDetailedActivityReport($conDB, $selectedItemId);
    }

    // Otherwise, show list view (multiple items)
    // Always keep asset item id internally for reference
    $selectCols = ['ai.id AS asset_item_id'];

    // Map requested columns to expressions from asset_items + assets
    foreach ($columns as $col) {
        switch ($col) {
            case 'asset_name':
                $selectCols[] = 'a.name AS asset_name';
                break;
            case 'asset_type':
                $selectCols[] = 'a.asset_type AS asset_type';
                break;
            case 'purchase_date':
                $selectCols[] = 'a.created_at AS purchase_date';
                break;
            case 'asset_status':
                // Prefer live assignment status from asset_items; otherwise latest history
                $selectCols[] = "COALESCE(
                                    CASE WHEN ai.status = 'Assigned' THEN 'Assigned' ELSE NULL END,
                                    (
                                        SELECT ea1.status FROM employee_assets ea1
                                        WHERE ea1.asset_id = ai.asset_id
                                          AND ea1.serial_number = COALESCE(ai.tracking_id, ai.serial_number)
                                        ORDER BY ea1.assigned_date DESC, ea1.id DESC
                                        LIMIT 1
                                    ),
                                    ai.status
                                ) AS asset_status";
                break;
            case 'assigned_to':
                // Current holder if assigned
                $selectCols[] = "CASE WHEN ai.status = 'Assigned' AND e.emp_id IS NOT NULL
                                   THEN CONCAT(e.emp_id, ' - ', e.name)
                                   ELSE '' END AS assigned_to";
                break;
            case 'assignment_date':
                $selectCols[] = 'ai.assigned_date AS assigned_date';
                break;
            case 'return_date':
                // Latest return date from history
                $selectCols[] = "(
                                    SELECT ea4.return_date FROM employee_assets ea4
                                    WHERE ea4.asset_id = ai.asset_id
                                      AND ea4.serial_number = COALESCE(ai.tracking_id, ai.serial_number)
                                    ORDER BY ea4.assigned_date DESC, ea4.id DESC
                                    LIMIT 1
                                ) AS return_date";
                break;
            case 'employee_dept':
                // Department of current holder if assigned
                $selectCols[] = 'ed.dep_nme AS employee_dept';
                break;
        }
    }

    if (count($selectCols) === 1) {
        // Default minimal set if nothing selected
        $selectCols = [
            'ai.id AS asset_item_id',
            'a.name AS asset_name',
            'a.asset_type AS asset_type',
            'a.created_at AS purchase_date',
            "COALESCE(CASE WHEN ai.status = 'Assigned' THEN 'Assigned' ELSE NULL END,
                      (SELECT ea1.status FROM employee_assets ea1 WHERE ea1.asset_id = ai.asset_id AND ea1.serial_number = COALESCE(ai.tracking_id, ai.serial_number) ORDER BY ea1.assigned_date DESC, ea1.id DESC LIMIT 1),
                      ai.status) AS asset_status",
            "CASE WHEN ai.status = 'Assigned' AND e.emp_id IS NOT NULL THEN CONCAT(e.emp_id, ' - ', e.name) ELSE '' END AS assigned_to",
            'ai.assigned_date AS assigned_date'
        ];
    }

    $selectClause = implode(', ', $selectCols);

    // Build WHERE clause - only active employees
    $where = ['e.status = 1'];

    // Department fallback filter (only when no explicit scope restrictions are configured)
    $hasSpecialRestrictions = !empty($_SESSION['allowed_employees_array']) ||
                            !empty($_SESSION['allowed_departments_array']) ||
                            !empty($_SESSION['allowed_companies_array']);

    if (!$hasFullAccess && !$hasSpecialRestrictions && !empty($userDept)) {
        $where[] = "a.clearance_dept_id = '" . mysqli_real_escape_string($conDB, $userDept) . "'";
    } elseif (!$hasFullAccess && !$hasSpecialRestrictions && !empty($departments)) {
        $dept_list = "'" . implode("','", array_map(function($d) use ($conDB) { return mysqli_real_escape_string($conDB, $d); }, $departments)) . "'";
        $where[] = "a.clearance_dept_id IN ($dept_list)";
    }

    // Company and department scoped filters from assigned employee
    $company_filter = getCompanyFilterSQL('e.comp_no', true);
    if (!empty($company_filter)) {
        $where[] = substr($company_filter, 5);
    }
    $department_filter = getDepartmentFilterSQL('e.dept', true);
    if (!empty($department_filter)) {
        $where[] = substr($department_filter, 5);
    }

    // Date filters (use asset creation date)
    if (!empty($dateFrom)) {
        $dateFrom_safe = mysqli_real_escape_string($conDB, $dateFrom);
        $where[] = "a.created_at >= '$dateFrom_safe'";
    }
    if (!empty($dateTo)) {
        $dateTo_safe = mysqli_real_escape_string($conDB, $dateTo);
        $where[] = "a.created_at <= '$dateTo_safe'";
    }

    // Status filter
    if ($status !== '') {
        $status_safe = mysqli_real_escape_string($conDB, $status);
        if ($status_safe === 'Assigned') {
            // Assigned either via live item status or latest history
            $where[] = "(ai.status = 'Assigned' OR (
                            SELECT ea1.status FROM employee_assets ea1
                            WHERE ea1.asset_id = ai.asset_id
                              AND ea1.serial_number = COALESCE(ai.tracking_id, ai.serial_number)
                            ORDER BY ea1.assigned_date DESC, ea1.id DESC
                            LIMIT 1
                        ) = 'Assigned')";
        } else {
            // Returned/Lost/Damaged via latest history
            $where[] = "(
                            SELECT ea1.status FROM employee_assets ea1
                            WHERE ea1.asset_id = ai.asset_id
                              AND ea1.serial_number = COALESCE(ai.tracking_id, ai.serial_number)
                            ORDER BY ea1.assigned_date DESC, ea1.id DESC
                            LIMIT 1
                        ) = '$status_safe'";
        }
    }

    // Employee filter
    if (!empty($employeeId)) {
        $where[] = "e.emp_id = '" . mysqli_real_escape_string($conDB, $employeeId) . "'";
    }

    applyEmployeeCompanyCountryFilter($conDB, $where, $companies, $countries);
    $whereClause = implode(' AND ', $where);

    $sql = "SELECT $selectClause
            FROM asset_items ai
            LEFT JOIN assets a ON a.id = ai.asset_id
            LEFT JOIN employees e ON e.emp_id = ai.assigned_emp_id
            LEFT JOIN department ed ON ed.id = e.dept
            WHERE $whereClause
            ORDER BY a.name ASC, ai.tracking_id ASC, ai.id ASC";

    $query = mysqli_query($conDB, $sql);
    if (!$query) {
        throw new Exception('Database error: ' . mysqli_error($conDB));
    }

    $data = [];
    while ($row = mysqli_fetch_assoc($query)) {
        $viewTxt = function_exists('__') ? __('view_activity') : 'View Activity';
        $row['actions'] = '<button class="btn btn-sm btn-primary view-asset-activity" data-asset-item-id="' . intval($row['asset_item_id']) . '"><i class="mdi mdi-eye"></i> ' . htmlspecialchars($viewTxt) . '</button>';
        
        if (isset($row['asset_name'])) {
            $row['asset_name'] = getDisplayName($row['asset_name']);
        }
        if (isset($row['asset_status'])) {
            $row['asset_status'] = getDisplayName($row['asset_status']);
        }

        $data[] = $row;
    }

    // Headers corresponding to requested columns
    $headers = [];
    foreach ($columns as $col) {
        $headers[] = getColumnLabel($col);
    }
    $headers[] = function_exists('__') ? __('actions') : 'Actions';

    return ['data' => $data, 'headers' => $headers];
}

// Generate detailed activity report for a single asset item (when item is selected from list)
function generateAssetDetailedActivityReport($conDB, $assetItemId) {
    // Get asset item info with current holder
    $aiSql = "SELECT 
                ai.id AS asset_item_id,
                ai.asset_id,
                ai.tracking_id,
                ai.serial_number,
                ai.description AS item_description,
                ai.status AS item_status,
                ai.assigned_emp_id,
                ai.assigned_date AS current_assigned_date,
                a.name AS asset_name,
                a.asset_type,
                a.created_at AS asset_created_date,
                a.clearance_dept_id,
                d.dep_nme AS asset_department,
                e.name AS current_holder_name,
                e.emp_id AS current_holder_id,
                ed.dep_nme AS current_holder_dept
            FROM asset_items ai
            LEFT JOIN assets a ON a.id = ai.asset_id
            LEFT JOIN department d ON d.id = a.clearance_dept_id
            LEFT JOIN employees e ON e.emp_id = ai.assigned_emp_id
            LEFT JOIN department ed ON ed.id = e.dept
            WHERE ai.id = " . intval($assetItemId);
    
    $aiRes = mysqli_query($conDB, $aiSql);
    if (!$aiRes) {
        throw new Exception('Database error: ' . mysqli_error($conDB));
    }
    $assetItem = mysqli_fetch_assoc($aiRes);
    if (!$assetItem) {
        throw new Exception('Asset item not found');
    }

    // Get complete activity history (all assignments, returns, status changes)
    // Fetch all employee_assets records for this specific asset item by matching asset_id AND tracking_id
    // This ensures we only get history for THIS specific physical item, not all items of the same asset type
    $histSql = "SELECT ea.id,
                       ea.emp_id,
                       ea.asset_id,
                       ea.serial_number,
                       ea.description,
                       ea.assigned_date,
                       ea.return_date,
                       ea.status,
                       ea.return_attachment,
                       e.emp_id,
                       e.name AS employee_name,
                       d.dep_nme AS employee_department
                FROM employee_assets ea
                LEFT JOIN employees e ON e.emp_id = ea.emp_id
                LEFT JOIN department d ON d.id = e.dept
                WHERE ea.asset_id = " . intval($assetItem['asset_id']) . "
                  AND ea.serial_number = '" . mysqli_real_escape_string($conDB, $assetItem['tracking_id']) . "'
                ORDER BY ea.assigned_date DESC, ea.id DESC";
    
    $histRes = mysqli_query($conDB, $histSql);
    if (!$histRes) {
        throw new Exception('Database error: ' . mysqli_error($conDB));
    }
    
    $history = [];
    while ($row = mysqli_fetch_assoc($histRes)) {
        $history[] = $row;
    }

    // Build data rows with all activity records
    $data = [];
    
    foreach ($history as $idx => $historyRow) {
        $data[] = [
            'entry_number' => $idx + 1,
            'asset_name' => $assetItem['asset_name'],
            'asset_type' => $assetItem['asset_type'],
            'tracking_id' => $assetItem['tracking_id'] ?: $assetItem['serial_number'],
            'emp_id' => $historyRow['emp_id'] ?: 'N/A',
            'employee_name' => $historyRow['employee_name'] ?: 'N/A',
            'employee_department' => $historyRow['employee_department'] ?: 'N/A',
            'assigned_date' => $historyRow['assigned_date'] ?: 'N/A',
            'return_date' => $historyRow['return_date'] ?: 'Not Returned',
            'status' => $historyRow['status'] ?: 'N/A',
            'description' => $historyRow['description'] ?: 'N/A'
        ];
    }

    // If no history, show current status
    if (empty($data)) {
        $data[] = [
            'entry_number' => 1,
            'asset_name' => $assetItem['asset_name'],
            'asset_type' => $assetItem['asset_type'],
            'tracking_id' => $assetItem['tracking_id'] ?: $assetItem['serial_number'],
            'emp_id' => $assetItem['current_holder_id'] ?: 'N/A',
            'employee_name' => $assetItem['current_holder_name'] ?: 'Not Assigned',
            'employee_department' => $assetItem['current_holder_dept'] ?: 'N/A',
            'assigned_date' => $assetItem['current_assigned_date'] ?: 'N/A',
            'return_date' => 'N/A',
            'status' => $assetItem['item_status'] ?: 'N/A',
            'description' => $assetItem['item_description'] ?: 'N/A'
        ];
    }

    // Define headers for detailed view
    $headers = [
        'entry_number' => '#',
        'asset_name' => 'Asset Name',
        'asset_type' => 'Asset Type',
        'tracking_id' => 'Tracking/Serial',
        'emp_id' => 'Employee ID',
        'employee_name' => 'Employee Name',
        'employee_department' => 'Department',
        'assigned_date' => 'Assigned Date',
        'return_date' => 'Return Date',
        'status' => 'Status',
        'description' => 'Description'
    ];

    return ['data' => $data, 'headers' => array_values($headers)];
}
?>













