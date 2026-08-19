<?php
ob_start();
error_reporting(E_ALL & ~E_DEPRECATED);

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/session_check.php';
require_once __DIR__ . '/includes/special_access_helper.php';
require_once __DIR__ . '/includes/payroll_approval_helpers.php';
require_once __DIR__ . '/includes/helper_functions.php';

$currentUserRole = strtolower(trim((string)($user_type ?? '')));
$allowed = ($is_system_admin ?? false)
    || in_array($currentUserRole, ['hr_payroll', 'hr_senior_bp'], true)
    || user_has_special_access($conDB, $empid ?? '', 'assign_payroll_supervisor', $user_role ?? '', $user_type ?? '', $is_system_admin ?? false);
if (!$allowed) {
    header("Location: error403.php?page=" . urlencode(basename(__FILE__)));
    exit;
}

require_once __DIR__ . '/includes/vendor/autoload.php';

$pdo = getDbConnection();
ensurePayrollSupervisorAssignmentsTable($pdo);

// Assignments are effective-dated - show each employee's currently-in-effect
// supervisor as of the requested month (defaults to this calendar month), matching
// what the "Assign Direct Supervisor (Payroll)" modal shows when this button is
// clicked from its Step 1 month field.
$effectiveMonth = trim((string)($_GET['effective_month'] ?? $_POST['effective_month'] ?? ''));
if (!preg_match('/^\d{4}-\d{2}$/', $effectiveMonth)) {
    $effectiveMonth = date('Y-m');
}

$rowsStmt = $pdo->prepare("SELECT
        e.emp_id,
        e.name AS employee_name,
        COALESCE(d.dep_nme, '') AS department_name,
        COALESCE(c.comp_name, '') AS company_name,
        sup.emp_id AS supervisor_emp_id,
        sup.name AS supervisor_name,
        psa.effective_month
    FROM payroll_supervisor_assignments psa
    INNER JOIN employees e ON e.emp_id = psa.emp_id
    INNER JOIN employees sup ON sup.emp_id = psa.supervisor_emp_id
    LEFT JOIN department d ON d.id = e.dept
    LEFT JOIN companies c ON c.comp_id = e.comp_no
    WHERE psa.effective_month = (
        SELECT MAX(psa2.effective_month) FROM payroll_supervisor_assignments psa2
        WHERE psa2.emp_id = psa.emp_id AND psa2.effective_month <= :as_of_month
    )
    ORDER BY sup.name ASC, e.name ASC");
$rowsStmt->execute([':as_of_month' => $effectiveMonth]);
$rows = $rowsStmt->fetchAll(PDO::FETCH_ASSOC);

$logo_path = get_setting($conDB, 'logo', 'assets/images/logo.png');

$pdf = new \TCPDF('L', PDF_UNIT, 'A4', true, 'UTF-8', false);
$pdf->SetCreator('Al-Mutlak WMS');
$pdf->SetAuthor('Al-Mutlak HR System');
$pdf->SetTitle('Payroll Supervisor Assignments');
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetMargins(12, 12, 12);
$pdf->SetAutoPageBreak(true, 12);

if ($is_rtl ?? false) {
    $pdf->setRTL(true);
}

$pdf->AddPage();

if ($is_rtl ?? false) {
    $pdf->SetFont('aealarabiya', '', 10);
} else {
    $pdf->SetFont('helvetica', '', 9);
}

if (file_exists($logo_path)) {
    $pageWidth = $pdf->getPageWidth();
    $logoWidth = 35;
    $pdf->Image($logo_path, ($pageWidth - $logoWidth) / 2, 10, $logoWidth, 0, '', '', 'T', false, 300, 'C', false, false, 0, false, false, false);
    $pdf->Ln(25);
}

$html = '
<style>
    h2 { color: #667eea; font-size: 16px; margin-bottom: 5px; }
    table { border-collapse: collapse; width: 100%; }
    th { background-color: #667eea; color: white; padding: 6px; text-align: left; font-weight: bold; font-size: 9px; }
    td { padding: 6px; border-bottom: 1px solid #e0e0e0; font-size: 9px; }
</style>
<h2>' . __('download_payroll_supervisor_assignments_button', 'Payroll Supervisor Assignments') . '</h2>
<p style="font-size:9px;color:#666;">' . __('generated_date', 'Generated Date') . ': ' . date('Y-m-d H:i:s') . ' &middot; ' . __('effective_as_of_month', 'As of') . ': ' . htmlspecialchars($effectiveMonth) . '</p>
<table cellpadding="4">
<thead>
<tr>
    <th width="9%">' . __('emp_id', 'Emp ID') . '</th>
    <th width="20%">' . __('name', 'Emp Name') . '</th>
    <th width="16%">' . __('department', 'Department') . '</th>
    <th width="16%">' . __('company', 'Company') . '</th>
    <th width="11%">' . __('supervisor_emp_id', 'Supervisor Emp ID') . '</th>
    <th width="17%">' . __('direct_supervisor', 'Direct Supervisor') . '</th>
    <th width="11%">' . __('effective_from_month_label', 'Effective From') . '</th>
</tr>
</thead>
<tbody>';

foreach ($rows as $row) {
    $html .= '<tr>
        <td>' . htmlspecialchars($row['emp_id'] ?? '') . '</td>
        <td>' . htmlspecialchars($row['employee_name'] ?? '') . '</td>
        <td>' . htmlspecialchars($row['department_name'] ?? '') . '</td>
        <td>' . htmlspecialchars($row['company_name'] ?? '') . '</td>
        <td>' . htmlspecialchars($row['supervisor_emp_id'] ?? '') . '</td>
        <td>' . htmlspecialchars($row['supervisor_name'] ?? '') . '</td>
        <td>' . htmlspecialchars($row['effective_month'] ?? '') . '</td>
    </tr>';
}

if (empty($rows)) {
    $html .= '<tr><td colspan="7" style="text-align:center;">' . __('no_data_available_in_table', 'No data available') . '</td></tr>';
}

$html .= '</tbody></table>';

$pdf->writeHTML($html, true, false, true, false, '');

ob_end_clean();

$filename = 'payroll_supervisor_assignments_' . date('Y-m-d') . '.pdf';
$pdf->Output($filename, 'D');
exit;
