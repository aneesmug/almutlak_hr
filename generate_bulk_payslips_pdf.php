<?php
/**
 * Generate combined payslip PDF (one page per employee) for a paid payroll month,
 * optionally filtered to a single company.
 */

ob_start();
error_reporting(E_ALL & ~E_DEPRECATED);

require_once("./includes/db.php");
require_once("./includes/session_check.php");
require_once('./includes/vendor/autoload.php');
require_once('./includes/helper_functions.php');

$query = mysqli_query($conDB, "SELECT * FROM `admin_login` WHERE `id_iqama`='" . $username . "'");
if (mysqli_num_rows($query) != 1) {
    ob_end_clean();
    die('Access denied.');
}

$month_year = $_GET['month'] ?? '';
$company = trim((string)($_GET['company'] ?? ''));
$emp_id = trim((string)($_GET['emp_id'] ?? ''));

if (!preg_match('/^\d{4}-\d{2}$/', $month_year)) {
    ob_end_clean();
    die('A valid month (YYYY-MM) is required.');
}

$sql = "SELECT p.*, e.name, e.dept, d.dep_nme, d.dep_nme_ar, c.comp_name
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
if ($emp_id !== '') {
    $sql .= " AND p.emp_id = ?";
    $params[] = $emp_id;
    $types .= 's';
}
$sql .= " ORDER BY CAST(e.emp_id AS UNSIGNED) ASC";

$stmt = $conDB->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$payrolls = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

if (empty($payrolls)) {
    ob_end_clean();
    die('No paid payroll records found for the selected month' . ($company !== '' ? " and company \"$company\"" : '') . '.');
}

$logo_path = get_setting($conDB, 'logo', 'assets/images/logo.png');

$pdf = new \TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
$pdf->SetCreator('Al-Mutlak WMS');
$pdf->SetAuthor('Al-Mutlak HR System');
$pdf->SetTitle('Payslips - ' . date('F Y', strtotime($month_year . '-01')) . ($company !== '' ? " - $company" : ''));
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetMargins(15, 15, 15);
$pdf->SetAutoPageBreak(true, 15);
if ($is_rtl) {
    $pdf->setRTL(true);
}

$earnings = [
    'basic_salary' => __('basic_salary', 'Basic Salary'),
    'housing_allowance' => __('housing_allowance', 'Housing Allowance'),
    'transport_allowance' => __('transport_allowance', 'Transport Allowance'),
    'food_allowance' => __('food_allowance', 'Food Allowance'),
    'miscellaneous_allowance' => __('miscellaneous_allowance', 'Miscellaneous Allowance'),
    'cashier_allowance' => __('cashier_allowance', 'Cashier Allowance'),
    'fuel_allowance' => __('fuel_allowance', 'Fuel Allowance'),
    'telephone_allowance' => __('telephone_allowance', 'Telephone Allowance'),
    'other_allowance' => __('other_allowance', 'Other Allowances'),
    'guard_allowance' => __('guard_allowance', 'Guard Allowance'),
    'total_benefits' => __('total_benefits', 'Additional Benefits'),
];

foreach ($payrolls as $payroll_record) {
    $deductions = [];
    if (!empty($payroll_record['total_deductions'])) {
        $stmt_ded = $conDB->prepare("SELECT deduction, note FROM payroll_deductions WHERE emp_id = ? AND month = ?");
        $stmt_ded->bind_param('ss', $payroll_record['emp_id'], $payroll_record['month_year']);
        $stmt_ded->execute();
        $deductions_result = $stmt_ded->get_result();
        while ($ded = $deductions_result->fetch_assoc()) {
            if (!empty($ded['deduction']) && $ded['deduction'] > 0) {
                $deductions[] = $ded;
            }
        }
        $stmt_ded->close();
    }

    $pdf->AddPage();
    if ($is_rtl) {
        $pdf->SetFont('aealarabiya', '', 13);
    } else {
        $pdf->SetFont('helvetica', '', 10);
    }

    if (file_exists($logo_path)) {
        $pageWidth = $pdf->getPageWidth();
        $logoWidth = 45;
        $logoXPosition = ($pageWidth - $logoWidth) / 2;
        $pdf->Image($logo_path, $logoXPosition, 10, $logoWidth, 0, '', '', 'T', false, 300, 'C', false, false, 0, false, false, false);
        $pdf->Ln(35);
    }

    $html = '
    <style>
        h1 { color: #667eea; font-size: 20px; margin-bottom: 5px; }
        h2 { color: #764ba2; font-size: 16px; margin-top: 15px; margin-bottom: 10px; }
        table { border-collapse: collapse; width: 100%; margin-bottom: 15px; }
        th { background-color: #667eea; color: white; padding: 8px; text-align: left; font-weight: bold; }
        td { padding: 8px; border-bottom: 1px solid #e0e0e0; }
        .header-box { background-color: #f5f7fa; padding: 15px; margin-bottom: 20px; border-radius: 8px; }
        .section-title { background-color: #4facfe; color: white; padding: 10px; margin-top: 15px; font-weight: bold; font-size: 14px; }
        .deduction-title { background-color: #fa709a; }
        .net-salary { background-color: #11998e; color: white; padding: 15px; text-align: center; margin-top: 20px; font-size: 18px; font-weight: bold; }
        .amount { font-weight: bold; color: #333; }
    </style>

    <div style="margin-top: 40px; margin-bottom: 20px;"></div>
    <div class="header-box">
        <h2>' . __('payroll_slip', 'Payroll Slip') . ' - ' . date('F Y', strtotime($month_year . '-01')) . '</h2>
        <p><strong>' . __('employee_name', 'Employee Name') . ':</strong> ' . htmlspecialchars($payroll_record['name'] ?? '') . '</p>
        <p><strong>' . __('employee_id', 'Employee ID') . ':</strong> ' . htmlspecialchars($payroll_record['emp_id'] ?? '') . '</p>
        <p><strong>' . __('department', 'Department') . ':</strong> ' . htmlspecialchars(($is_rtl ? ($payroll_record['dep_nme_ar'] ?? '') : ($payroll_record['dep_nme'] ?? ''))) . '</p>
        <p><strong>' . __('company', 'Company') . ':</strong> ' . htmlspecialchars($payroll_record['comp_name'] ?? '') . '</p>
        <p><strong>' . __('generated_date', 'Generated Date') . ':</strong> ' . date('M d, Y') . '</p>
    </div>

    <h2>' . __('salary_summary', 'Salary Summary') . '</h2>
    <table>
        <tr>
            <td><strong>' . __('basic_salary', 'Basic Salary') . '</strong></td>
            <td class="amount">' . number_format((float)($payroll_record['basic_salary'] ?? 0), 2) . ' SAR</td>
        </tr>
        <tr>
            <td><strong>' . __('total_gross_salary', 'Total Gross Salary') . '</strong></td>
            <td class="amount">' . number_format((float)($payroll_record['total_gross_salary'] ?? 0), 2) . ' SAR</td>
        </tr>
        <tr>
            <td><strong>' . __('total_deductions', 'Total Deductions') . '</strong></td>
            <td class="amount">' . number_format((float)($payroll_record['total_deductions'] ?? 0), 2) . ' SAR</td>
        </tr>
    </table>

    <div class="section-title">' . strtoupper(__('earnings', 'EARNINGS')) . '</div>
    <table cellpadding="5">';

    foreach ($earnings as $key => $label) {
        if (isset($payroll_record[$key]) && $payroll_record[$key] > 0) {
            $html .= '<tr>
                <td>' . $label . '</td>
                <td class="amount">' . number_format((float)$payroll_record[$key], 2) . ' SAR</td>
            </tr>';
        }
    }

    $html .= '<tr style="background-color: #e0f7fa;">
        <td><strong>' . __('total_gross_salary', 'Total Gross Salary') . '</strong></td>
        <td class="amount"><strong>' . number_format((float)($payroll_record['total_gross_salary'] ?? 0), 2) . ' SAR</strong></td>
    </tr>
    </table>';

    if (!empty($payroll_record['total_deductions']) && count($deductions) > 0) {
        $html .= '<div class="section-title deduction-title">' . strtoupper(__('deductions', 'DEDUCTIONS')) . '</div>
        <table cellpadding="5">';
        foreach ($deductions as $ded) {
            $html .= '<tr>
                <td>' . htmlspecialchars($ded['note'] ?? __('deduction', 'Deduction')) . '</td>
                <td class="amount">' . number_format((float)$ded['deduction'], 2) . ' SAR</td>
            </tr>';
        }
        $html .= '<tr style="background-color: #ffe0e0;">
            <td><strong>' . __('total_deductions', 'Total Deductions') . '</strong></td>
            <td class="amount"><strong>' . number_format((float)($payroll_record['total_deductions'] ?? 0), 2) . ' SAR</strong></td>
        </tr>
        </table>';
    }

    $html .= '<div class="net-salary">
        ' . strtoupper(__('net_salary', 'NET SALARY')) . ': ' . number_format((float)($payroll_record['net_salary'] ?? 0), 2) . ' SAR
    </div>

    <p style="margin-top: 30px; font-size: 9px; color: #666;">
        <em>' . __('payroll_generated_note', 'This is a computer-generated payroll slip. Generated on') . ' ' . date('Y-m-d H:i:s') . '</em>
    </p>';

    $pdf->writeHTML($html, true, false, true, false, '');
}

ob_end_clean();

$filename = ($emp_id !== '' ? 'Payslip_' . preg_replace('/[^A-Za-z0-9_-]/', '_', $emp_id) : 'Payslips') . '_' . $month_year . ($company !== '' ? '_' . preg_replace('/[^A-Za-z0-9_-]/', '_', $company) : '') . '.pdf';
$pdf->Output($filename, 'D');
exit();
