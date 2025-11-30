<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../session_check.php';

header('Content-Type: application/json');

// Check authorization
$can_see_reports_page = ['Administrator', 'GM', 'Auditor', 'HR_Senior_BP', 'HR_Operations', 'HR_Supervisor', 'Finance_Officer', 'DPT_Manager', 'HR_Manager', 'Finance_Manager'];

if (!in_array($user_role, $can_see_reports_page) && $user_type !== 'is_system_admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Get request parameters
$reportType = isset($_POST['reportType']) ? $_POST['reportType'] : '';
$columns = isset($_POST['columns']) ? $_POST['columns'] : [];
$departments = isset($_POST['departments']) ? $_POST['departments'] : [];
$dateFrom = isset($_POST['dateFrom']) ? $_POST['dateFrom'] : '';
$dateTo = isset($_POST['dateTo']) ? $_POST['dateTo'] : '';
$status = isset($_POST['status']) ? $_POST['status'] : '';
$hasFullAccess = isset($_POST['hasFullAccess']) && $_POST['hasFullAccess'] === 'true';
$userDept = isset($_POST['userDept']) ? $_POST['userDept'] : '';

// Normalize departments for filters (array of IDs as strings)
if (!is_array($departments)) {
    $departments = empty($departments) ? [] : [$departments];
}

if (empty($reportType) || empty($columns)) {
    echo json_encode(['success' => false, 'message' => 'Report type and columns are required']);
    exit();
}

try {
    $data = [];
    $headers = [];
    
    switch ($reportType) {
        case 'employee':
            $result = generateEmployeeReport($conDB, $columns, $departments, $dateFrom, $dateTo, $status, $hasFullAccess, $userDept);
            break;
        case 'vacation':
            $result = generateVacationReport($conDB, $columns, $departments, $dateFrom, $dateTo, $status, $hasFullAccess, $userDept);
            break;
        case 'loan':
            $result = generateLoanReport($conDB, $columns, $departments, $dateFrom, $dateTo, $status, $hasFullAccess, $userDept);
            break;
        case 'salary':
            $result = generateSalaryReport($conDB, $columns, $departments, $hasFullAccess, $userDept);
            break;
        case 'payroll':
            $result = generatePayrollReport($conDB, $columns, $dateFrom, $dateTo, $hasFullAccess, $userDept);
            break;
        case 'attendance':
            $result = generateAttendanceReport($conDB, $columns, $departments, $dateFrom, $dateTo, $hasFullAccess, $userDept);
            break;
        case 'document':
            $result = generateDocumentReport($conDB, $columns, $departments, $hasFullAccess, $userDept);
            break;
        case 'evaluation':
            $result = generateEvaluationReport($conDB, $columns, $departments, $dateFrom, $dateTo, $hasFullAccess, $userDept);
            break;
        case 'resignation':
            $result = generateResignationReport($conDB, $columns, $departments, $dateFrom, $dateTo, $hasFullAccess, $userDept);
            break;
        case 'eos':
            $result = generateEOSReport($conDB, $columns, $departments, $dateFrom, $dateTo, $hasFullAccess, $userDept);
            break;
        case 'dept_comparison':
            $result = generateDepartmentComparisonReport($conDB, $columns, $departments, $hasFullAccess, $userDept);
            break;
        case 'custom':
            $customTables = isset($_POST['customTables']) ? $_POST['customTables'] : [];
            $customDepartments = isset($_POST['customDepartments']) ? $_POST['customDepartments'] : [];
            $result = generateCustomReport($conDB, $columns, $customTables, $customDepartments, $dateFrom, $dateTo);
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

// Helper function to get column label
function getColumnLabel($column) {
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
        'insurance_no' => 'Insurance No',
        'status' => 'Status',
        'emp_name' => 'Employee Name',
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
        'eos_amount' => 'EOS Amount',
        'vacation_balance' => 'Vacation Balance',
        'total_amount' => 'Total Amount',
        'department' => 'Department',
        'total_employees' => 'Total Employees',
        'active_employees' => 'Active Employees',
        'inactive_employees' => 'Inactive Employees',
        'avg_salary' => 'Average Salary',
        'pending_vacations' => 'Pending Vacations',
        'approved_vacations' => 'Approved Vacations',
        'active_loans' => 'Active Loans',
        'total_loan_amount' => 'Total Loan Amount',
        'avg_service_years' => 'Avg Service Years'
    ];
    return isset($labels[$column]) ? $labels[$column] : ucfirst(str_replace('_', ' ', $column));
}

// Employee Report
function generateEmployeeReport($conDB, $columns, $departments, $dateFrom, $dateTo, $status, $hasFullAccess, $userDept) {
    // Map column IDs to actual database columns with proper joins
    $columnMap = [
        'actual_job' => 'j.job',
        'dept' => 'd.dep_nme',
        'sectin_nme' => 's.section_name',
        'country' => 'c.name',
        'bank_name' => 'b.name',
        'sex' => "CASE WHEN e.sex = '1' THEN 'Male' WHEN e.sex = '2' THEN 'Female' ELSE e.sex END"
    ];
    
    // Build SELECT clause
    $selectCols = [];
    foreach ($columns as $col) {
        if (isset($columnMap[$col])) {
            $selectCols[] = $columnMap[$col] . ' AS ' . $col;
        } else {
            $selectCols[] = 'e.' . $col;
        }
    }
    $selectClause = implode(', ', $selectCols);
    
    // Build WHERE clause
    $where = ['1=1'];
    
    // Department filter
    if (!$hasFullAccess) {
        $where[] = "e.dept = '" . mysqli_real_escape_string($conDB, $userDept) . "'";
    } elseif (!empty($departments)) {
        $deptList = array_map(function($d) use ($conDB) { return "'" . mysqli_real_escape_string($conDB, $d) . "'"; }, $departments);
        $where[] = "e.dept IN (" . implode(',', $deptList) . ")";
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
    
    $whereClause = implode(' AND ', $where);
    
    // Build and execute query
    $sql = "SELECT $selectClause 
            FROM employees e
            LEFT JOIN ac_jobs j ON e.actual_job = j.id
            LEFT JOIN department d ON e.dept = d.id
            LEFT JOIN section s ON e.sectin_nme = s.id
            LEFT JOIN countries c ON e.country = c.id
            LEFT JOIN bank_list b ON e.bank_name = b.id
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
        if (isset($row['status'])) {
            $row['status'] = $row['status'] == 1 ? 'Active' : 'Inactive';
        }
        if (isset($row['fly'])) {
            $row['fly'] = $row['fly'] == 1 ? 'Yes' : 'No';
        }
        $data[] = $row;
    }
    
    return ['data' => $data, 'headers' => $headers];
}

// Vacation Report
function generateVacationReport($conDB, $columns, $departments, $dateFrom, $dateTo, $status, $hasFullAccess, $userDept) {
    // Build SELECT clause
    $selectCols = ['v.id'];
    $needsEmpName = in_array('emp_name', $columns);
    $needsDept = in_array('dept', $columns);
    
    foreach ($columns as $col) {
        if ($col == 'emp_name') {
            $selectCols[] = 'e.name AS emp_name';
        } elseif ($col == 'emp_id') {
            $selectCols[] = 'v.emp_id';
        } elseif ($col == 'dept') {
            $selectCols[] = 'd.dep_nme AS dept';
        } else {
            $selectCols[] = 'v.' . $col;
        }
    }
    $selectClause = implode(', ', $selectCols);
    
    // Build WHERE clause
    $where = ['1=1'];
    
    // Department filter
    if (!$hasFullAccess) {
        $where[] = "e.dept = '" . mysqli_real_escape_string($conDB, $userDept) . "'";
    } elseif (!empty($departments)) {
        $deptList = array_map(function($d) use ($conDB) { return "'" . mysqli_real_escape_string($conDB, $d) . "'"; }, $departments);
        $where[] = "e.dept IN (" . implode(',', $deptList) . ")";
    }
    
    // Date filter
    if (!empty($dateFrom)) {
        $where[] = "v.start_date >= '" . mysqli_real_escape_string($conDB, $dateFrom) . "'";
    }
    if (!empty($dateTo)) {
        $where[] = "v.start_date <= '" . mysqli_real_escape_string($conDB, $dateTo) . "'";
    }
    
    // Status filter
    if ($status !== '') {
        $where[] = "v.current_status = '" . mysqli_real_escape_string($conDB, $status) . "'";
    }
    
    $whereClause = implode(' AND ', $where);
    
    // Build and execute query
    $sql = "SELECT $selectClause 
            FROM emp_vacation v
            INNER JOIN employees e ON v.emp_id = e.emp_id
            LEFT JOIN department d ON e.dept = d.id
            WHERE $whereClause
            ORDER BY v.start_date DESC";
    
    $query = mysqli_query($conDB, $sql);
    if (!$query) {
        throw new Exception('Vacation query error: ' . mysqli_error($conDB));
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
        $data[] = $row;
    }
    
    return ['data' => $data, 'headers' => $headers];
}

// Loan Report
function generateLoanReport($conDB, $columns, $departments, $dateFrom, $dateTo, $status, $hasFullAccess, $userDept) {
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
        } elseif ($col == 'remaining_amount') {
            $selectCols[] = '(l.final_approved_amount - COALESCE(SUM(lp.amount), 0)) AS remaining_amount';
        } else {
            $selectCols[] = 'l.' . $col;
        }
    }
    $selectClause = implode(', ', $selectCols);
    
    // Build WHERE clause
    $where = ['1=1'];
    
    // Department filter
    if (!$hasFullAccess) {
        $where[] = "e.dept = '" . mysqli_real_escape_string($conDB, $userDept) . "'";
    } elseif (!empty($departments)) {
        $deptList = array_map(function($d) use ($conDB) { return "'" . mysqli_real_escape_string($conDB, $d) . "'"; }, $departments);
        $where[] = "e.dept IN (" . implode(',', $deptList) . ")";
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
    
    $whereClause = implode(' AND ', $where);
    
    // Build and execute query
    $sql = "SELECT $selectClause 
            FROM emp_loan l
            INNER JOIN employees e ON l.emp_id = e.emp_id
            LEFT JOIN department d ON e.dept = d.id
            LEFT JOIN emp_loan_payments lp ON l.id = lp.loan_id
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
        $data[] = $row;
    }
    
    return ['data' => $data, 'headers' => $headers];
}

// Salary Report
function generateSalaryReport($conDB, $columns, $departments, $hasFullAccess, $userDept) {
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
    $where = ['s.status = 1'];
    
    // Department filter
    if (!$hasFullAccess) {
        $where[] = "e.dept = '" . mysqli_real_escape_string($conDB, $userDept) . "'";
    } elseif (!empty($departments)) {
        $deptList = array_map(function($d) use ($conDB) { return "'" . mysqli_real_escape_string($conDB, $d) . "'"; }, $departments);
        $where[] = "e.dept IN (" . implode(',', $deptList) . ")";
    }
    
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
        $data[] = $row;
    }
    
    return ['data' => $data, 'headers' => $headers];
}

// Payroll Report
function generatePayrollReport($conDB, $columns, $dateFrom, $dateTo, $hasFullAccess, $userDept) {
    // Build SELECT clause with column mapping aligned to actual schema
    // payrolls columns: id, emp_id, month_year, total_gross_salary, total_deductions, net_salary, status, generated_at
    $selectCols = [];
    
    foreach ($columns as $col) {
        switch ($col) {
            case 'payroll_id':
                $selectCols[] = 'p.id AS payroll_id';
                break;
            case 'month':
                // Numeric month (01-12) derived directly from string to avoid date mode issues
                $selectCols[] = "SUBSTRING(p.month_year, 6, 2) AS month";
                break;
            case 'year':
                $selectCols[] = "SUBSTRING(p.month_year, 1, 4) AS year";
                break;
            case 'total_employees':
                // Count of payroll records for the same month_year
                $selectCols[] = '(SELECT COUNT(*) FROM payrolls p2 WHERE p2.month_year = p.month_year) AS total_employees';
                break;
            case 'total_salary':
                $selectCols[] = 'p.total_gross_salary AS total_salary';
                break;
            case 'total_deductions':
                $selectCols[] = 'p.total_deductions AS total_deductions';
                break;
            case 'net_salary':
                $selectCols[] = 'p.net_salary AS net_salary';
                break;
            case 'generated_by':
                // No column available; expose status as a proxy
                $selectCols[] = 'p.status AS generated_by';
                break;
            case 'created_at':
                $selectCols[] = 'p.generated_at AS created_at';
                break;
            default:
                // Skip unknown requested columns to avoid SQL errors
                // Alternatively include raw if exists
                // $selectCols[] = 'p.' . $col;
                break;
        }
    }
    if (empty($selectCols)) {
        // Fallback minimum columns
        $selectCols = [
            'p.id AS payroll_id',
            "SUBSTRING(p.month_year, 6, 2) AS month",
            "SUBSTRING(p.month_year, 1, 4) AS year",
            'p.total_gross_salary AS total_salary',
            'p.total_deductions AS total_deductions',
            'p.net_salary AS net_salary',
            'p.generated_at AS created_at'
        ];
    }
    $selectClause = implode(', ', $selectCols);
    
    // Build WHERE clause
    $where = ['1=1'];
    
    // Date filter using month_year (YYYY-MM) via string compare
    if (!empty($dateFrom)) {
        $fromYm = substr($dateFrom, 0, 7);
        $where[] = "p.month_year >= '" . mysqli_real_escape_string($conDB, $fromYm) . "'";
    }
    if (!empty($dateTo)) {
        $toYm = substr($dateTo, 0, 7);
        $where[] = "p.month_year <= '" . mysqli_real_escape_string($conDB, $toYm) . "'";
    }
    
    $whereClause = implode(' AND ', $where);
    
    // Build and execute query
        $sql = "SELECT $selectClause 
            FROM payrolls p
            WHERE $whereClause
            ORDER BY p.month_year DESC, p.id DESC";
    
    $query = mysqli_query($conDB, $sql);
    if (!$query) {
        throw new Exception('Payroll query error: ' . mysqli_error($conDB));
    }
    
    $data = [];
    $headers = [];
    
    // Get headers
    foreach ($columns as $col) {
        $headers[] = getColumnLabel($col);
    }
    
    // Get data
    while ($row = mysqli_fetch_assoc($query)) {
        // Format month name if numeric
        if (isset($row['month'])) {
            $monthNames = ['', 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
            $monthIndex = (int)$row['month'];
            $row['month'] = ($monthIndex >=1 && $monthIndex <=12) ? $monthNames[$monthIndex] : $row['month'];
        }
        $data[] = $row;
    }
    
    return ['data' => $data, 'headers' => $headers];
}

// Attendance Report
function generateAttendanceReport($conDB, $columns, $departments, $dateFrom, $dateTo, $hasFullAccess, $userDept) {
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
    $where = ['1=1'];
    
    // Department filter
    if (!$hasFullAccess) {
        $where[] = "e.dept = '" . mysqli_real_escape_string($conDB, $userDept) . "'";
    } elseif (!empty($departments)) {
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
        $data[] = $row;
    }
    
    return ['data' => $data, 'headers' => $headers];
}

// Document Report
function generateDocumentReport($conDB, $columns, $departments, $hasFullAccess, $userDept) {
    // Build SELECT clause
    $selectCols = [];
    
    foreach ($columns as $col) {
        if ($col == 'emp_name') {
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
        } else {
            $selectCols[] = 'd.' . $col;
        }
    }
    $selectClause = implode(', ', $selectCols);
    
    // Build WHERE clause
    $where = ['1=1'];
    
    // Department filter
    if (!$hasFullAccess) {
        $where[] = "e.dept = '" . mysqli_real_escape_string($conDB, $userDept) . "'";
    } elseif (!empty($departments)) {
        $deptList = array_map(function($d) use ($conDB) { return "'" . mysqli_real_escape_string($conDB, $d) . "'"; }, $departments);
        $where[] = "e.dept IN (" . implode(',', $deptList) . ")";
    }
    
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
        $data[] = $row;
    }
    
    return ['data' => $data, 'headers' => $headers];
}

// Evaluation Report
function generateEvaluationReport($conDB, $columns, $departments, $dateFrom, $dateTo, $hasFullAccess, $userDept) {
    // Add column mappings
    $columnMap = [
        'dept' => 'd.dep_nme',
        'sex' => "CASE WHEN e.sex = '1' THEN 'Male' WHEN e.sex = '2' THEN 'Female' ELSE e.sex END",
        'country' => 'c.name',
        'bank_name' => 'b.name',
        'sectin_nme' => 's.section_name'
    ];

    // Build SELECT clause
    $selectCols = [];
    
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
        } else {
            $selectCols[] = 'ev.' . $col;
        }
    }
    $selectClause = implode(', ', $selectCols);
    
    // Build WHERE clause
    $where = ['1=1'];
    
    // Department filter
    if (!$hasFullAccess) {
        $where[] = "e.dept = '" . mysqli_real_escape_string($conDB, $userDept) . "'";
    } elseif (!empty($departments)) {
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
    
    // Get data
    while ($row = mysqli_fetch_assoc($query)) {
        $data[] = $row;
    }
    
    return ['data' => $data, 'headers' => $headers];
}

// Resignation Report
function generateResignationReport($conDB, $columns, $departments, $dateFrom, $dateTo, $hasFullAccess, $userDept) {
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
    
    // Build WHERE clause
    $where = ['1=1'];
    
    // Department filter
    if (!$hasFullAccess) {
        $where[] = "e.dept = '" . mysqli_real_escape_string($conDB, $userDept) . "'";
    } elseif (!empty($departments)) {
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
        $data[] = $row;
    }
    
    return ['data' => $data, 'headers' => $headers];
}

// End of Service Report
function generateEOSReport($conDB, $columns, $departments, $dateFrom, $dateTo, $hasFullAccess, $userDept) {
    // Build SELECT clause
    $selectCols = [];
    
    foreach ($columns as $col) {
        if ($col == 'emp_name') {
            $selectCols[] = 'e.name AS emp_name';
        } elseif ($col == 'emp_id') {
            $selectCols[] = 'eos.emp_id';
        } elseif ($col == 'dept') {
            $selectCols[] = 'd.dep_nme AS dept';
        } elseif ($col == 'termination_date') {
            // Map to stored EOS end date
            $selectCols[] = 'eos.end_date AS termination_date';
        } elseif ($col == 'service_years') {
            // Compute years + months fraction and round to 2 decimals in SQL
            $selectCols[] = 'ROUND((COALESCE(eos.t_years,0) + COALESCE(eos.t_months,0)/12.0), 2) AS service_years';
        } elseif ($col == 'vacation_balance') {
            // Use annual vacation days recorded during EOS
            $selectCols[] = 'eos.anul_vac_days AS vacation_balance';
        } elseif ($col == 'total_amount') {
            // Net payment for EOS
            $selectCols[] = 'eos.net_payment AS total_amount';
        } else {
            $selectCols[] = 'eos.' . $col;
        }
    }
    $selectClause = implode(', ', $selectCols);
    
    // Build WHERE clause
    $where = ['1=1'];
    
    // Department filter
    if (!$hasFullAccess) {
        $where[] = "e.dept = '" . mysqli_real_escape_string($conDB, $userDept) . "'";
    } elseif (!empty($departments)) {
        $deptList = array_map(function($d) use ($conDB) { return "'" . mysqli_real_escape_string($conDB, $d) . "'"; }, $departments);
        $where[] = "e.dept IN (" . implode(',', $deptList) . ")";
    }
    
    // Date filter
    if (!empty($dateFrom)) {
        $where[] = "eos.end_date >= '" . mysqli_real_escape_string($conDB, $dateFrom) . "'";
    }
    if (!empty($dateTo)) {
        $where[] = "eos.end_date <= '" . mysqli_real_escape_string($conDB, $dateTo) . "'";
    }
    
    $whereClause = implode(' AND ', $where);
    
    // Build and execute query
    $sql = "SELECT $selectClause 
            FROM emp_eos eos
            INNER JOIN employees e ON eos.emp_id = e.emp_id
            LEFT JOIN department d ON e.dept = d.id
            WHERE $whereClause
            ORDER BY eos.end_date DESC";
    
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
    
    // Get data
    while ($row = mysqli_fetch_assoc($query)) {
        $data[] = $row;
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
    
    // Build department filter
    $whereClause = '';
    if (!$hasFullAccess) {
        // Non-admin users can only see their own department
        $whereClause = "WHERE d.id = '" . mysqli_real_escape_string($conDB, $userDept) . "'";
    } elseif (!empty($departments)) {
        // Filter by selected departments
        $deptList = array_map(function($d) use ($conDB) {
            return "'" . mysqli_real_escape_string($conDB, $d) . "'";
        }, $departments);
        $whereClause = "WHERE d.id IN (" . implode(',', $deptList) . ")";
    }
    
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
                                                      WHERE e.dept = '$deptId' AND v.current_status = 'pending'");
                    $vacRow = mysqli_fetch_assoc($vacQuery);
                    $deptRow[$col] = $vacRow['cnt'];
                    break;
                case 'approved_vacations':
                    // Get approved vacation count (this year)
                    $vacQuery = mysqli_query($conDB, "SELECT COUNT(*) as cnt FROM emp_vacation v 
                                                      INNER JOIN employees e ON v.emp_id = e.emp_id 
                                                      WHERE e.dept = '$deptId' 
                                                      AND v.current_status = 'approved' 
                                                      AND YEAR(v.start_date) = YEAR(CURDATE())");
                    $vacRow = mysqli_fetch_assoc($vacQuery);
                    $deptRow[$col] = $vacRow['cnt'];
                    break;
                case 'active_loans':
                    // Get active loan count
                    $loanQuery = mysqli_query($conDB, "SELECT COUNT(*) as cnt FROM emp_loan l 
                                                       INNER JOIN employees e ON l.emp_id = e.emp_id 
                                                       WHERE e.dept = '$deptId' AND l.status = 'active'");
                    $loanRow = mysqli_fetch_assoc($loanQuery);
                    $deptRow[$col] = $loanRow['cnt'];
                    break;
                case 'total_loan_amount':
                    // Get total active loan amount
                    $loanQuery = mysqli_query($conDB, "SELECT COALESCE(SUM(CAST(l.final_approved_amount AS DECIMAL(10,2))), 0) as total 
                                                       FROM emp_loan l 
                                                       INNER JOIN employees e ON l.emp_id = e.emp_id 
                                                       WHERE e.dept = '$deptId' AND l.status = 'active'");
                    $loanRow = mysqli_fetch_assoc($loanQuery);
                    $deptRow[$col] = number_format($loanRow['total'], 2);
                    break;
            }
        }
        
        $data[] = $deptRow;
    }
    
    return ['data' => $data, 'headers' => $headers];
}

function generateCustomReport($conDB, $columns, $tableNames, $departments = [], $dateFrom = '', $dateTo = '') {
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
    
    // Auto-include employees table for department filtering if not already selected
    // but any selected table has emp_id
    $needsEmployeesForFiltering = false;
    if (!in_array('employees', $validatedTables) && !empty($departments) && !in_array('all', $departments)) {
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
                $safeColumn = "`" . mysqli_real_escape_string($conDB, $tbl) . "`.`" . mysqli_real_escape_string($conDB, $col_name) . "`";
                $safeColumns[] = $safeColumn . " AS `" . str_replace('.', '_', $col) . "`";
                $columnAliases[] = str_replace('.', '_', $col);
            }
        } else {
            // Non-prefixed column - check in primary table
            if (in_array($col, $tableColumns[$primaryTable])) {
                $safeColumn = "`" . mysqli_real_escape_string($conDB, $primaryTable) . "`.`" . mysqli_real_escape_string($conDB, $col) . "`";
                $safeColumns[] = $safeColumn;
                $columnAliases[] = $col;
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
    
    $whereClause = !empty($whereClauses) ? 'WHERE ' . implode(' AND ', $whereClauses) : '';
    
    // Build the query
    $columnList = implode(', ', $safeColumns);
    $joinClause = !empty($joins) ? implode(' ', $joins) : '';
    
    $sql = "SELECT " . $columnList . " FROM `" . mysqli_real_escape_string($conDB, $primaryTable) . "` " . $joinClause . " " . $whereClause . " LIMIT 1000";
    
    // Log the SQL query for debugging
    error_log("Custom Report SQL: " . $sql);
    
    $result = mysqli_query($conDB, $sql);
    if (!$result) {
        error_log("Custom Report Query Error: " . mysqli_error($conDB));
        throw new Exception('Query error: ' . mysqli_error($conDB));
    }
    
    // Prepare data
    $data = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $data[] = $row;
    }
    
    // Build headers (use column aliases as headers, format them nicely)
    $headers = array_map(function($col) {
        // Replace underscores with spaces and capitalize words
        return ucwords(str_replace('_', ' ', $col));
    }, $columnAliases);
    
    return ['data' => $data, 'headers' => $headers];
}
?>












