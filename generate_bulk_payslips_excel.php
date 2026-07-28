<?php
/**
 * Generate a payslip breakdown Excel (one row per employee) for a paid payroll month,
 * optionally filtered to a single company.
 */

require_once("./includes/db.php");
require_once("./includes/session_check.php");
require_once('./includes/vendor/autoload.php');

$query = mysqli_query($conDB, "SELECT * FROM `admin_login` WHERE `id_iqama`='" . $username . "'");
if (mysqli_num_rows($query) != 1) {
    die('Access denied.');
}

$month_year = $_GET['month'] ?? '';
$company = trim((string)($_GET['company'] ?? ''));

if (!preg_match('/^\d{4}-\d{2}$/', $month_year)) {
    die('A valid month (YYYY-MM) is required.');
}

$sql = "SELECT p.*, e.name, d.dep_nme, c.comp_name
        FROM payrolls p
        JOIN employees e ON e.emp_id = p.emp_id
        LEFT JOIN department d ON d.id = e.dept
        LEFT JOIN companies c ON c.comp_id = e.comp_no
        WHERE p.month_year = ? AND p.status = 'paid'";
$params = [$month_year];
$types = 's';
if ($company !== '') {
    $sql .= " AND c.comp_name = ?";
    $params[] = $company;
    $types .= 's';
}
$sql .= " ORDER BY CAST(e.emp_id AS UNSIGNED) ASC";

$stmt = $conDB->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$payrolls = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

if (empty($payrolls)) {
    die('No paid payroll records found for the selected month' . ($company !== '' ? " and company \"$company\"" : '') . '.');
}

$spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Payslips');

$headers = [
    'Emp ID', 'Employee Name', 'Department', 'Company',
    'Basic Salary', 'Housing Allowance', 'Transport Allowance', 'Food Allowance',
    'Miscellaneous Allowance', 'Cashier Allowance', 'Fuel Allowance', 'Telephone Allowance',
    'Other Allowance', 'Guard Allowance', 'Total Benefits',
    'Total Gross Salary', 'Total Deductions', 'Net Salary', 'Status',
];
$sheet->fromArray($headers, null, 'A1');

$rowIndex = 2;
foreach ($payrolls as $p) {
    $sheet->fromArray([
        $p['emp_id'],
        $p['name'],
        $p['dep_nme'],
        $p['comp_name'],
        (float)$p['basic_salary'],
        (float)$p['housing_allowance'],
        (float)$p['transport_allowance'],
        (float)$p['food_allowance'],
        (float)$p['miscellaneous_allowance'],
        (float)$p['cashier_allowance'],
        (float)$p['fuel_allowance'],
        (float)$p['telephone_allowance'],
        (float)$p['other_allowance'],
        (float)$p['guard_allowance'],
        (float)$p['total_benefits'],
        (float)$p['total_gross_salary'],
        (float)$p['total_deductions'],
        (float)$p['net_salary'],
        $p['status'],
    ], null, 'A' . $rowIndex);
    $rowIndex++;
}

$sheet->getStyle('A1:S1')->getFont()->setBold(true);
foreach (range('A', 'S') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

$filename = 'Payslips_' . $month_year . ($company !== '' ? '_' . preg_replace('/[^A-Za-z0-9_-]/', '_', $company) : '') . '.xlsx';

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
$writer->save('php://output');
exit();
