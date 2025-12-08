<?php
/**
 * Generate Payroll Slip PDF
 * Creates a PDF version of the employee payroll slip
 */

// Prevent any output before PDF generation
ob_start();
error_reporting(E_ALL & ~E_DEPRECATED);

require_once("./includes/session_check.php");
require_once('./includes/vendor/autoload.php');
require_once('./includes/MainClass.php');
require_once("./includes/Hijri_GregorianConvert.php");

$DateConv = new Hijri_GregorianConvert;
$format = "YYYY-MM-DD";

// Get employee ID and date parameters
$emp_id = $_SESSION['empid'] ?? ($_GET['emp_id'] ?? null);
$month = isset($_GET['month']) ? $_GET['month'] : date('m');
$year = isset($_GET['year']) ? $_GET['year'] : date('Y');

if (empty($emp_id)) {
    die("Employee ID is required.");
}

// Fetch employee data
$emp_query = "SELECT 
e.*,
`department`.`dep_nme`,
`department`.`dep_nme_ar`
FROM employees e
LEFT JOIN `department` ON `department`.`id` = `e`.`dept`
WHERE emp_id = ?";
$stmt = $conDB->prepare($emp_query);
$stmt->bind_param("s", $emp_id);
$stmt->execute();
$emprow = $stmt->get_result()->fetch_assoc();

if (!$emprow) {
    die("Employee not found.");
}

// Fetch payroll record
$month_year = sprintf('%04d-%02d', $year, $month);
$payroll_query = "SELECT * FROM payrolls 
                  WHERE emp_id = ? AND month_year = ?
                  ORDER BY month_year DESC";
$stmt = $conDB->prepare($payroll_query);
$stmt->bind_param("ss", $emp_id, $month_year);
$stmt->execute();
$payroll_result = $stmt->get_result();
$payroll_record = $payroll_result->fetch_assoc();

if (!$payroll_record) {
    die("No payroll record found for the selected period.");
}

// Fetch deductions if any
$deductions = [];
if (!empty($payroll_record['total_deductions'])) {
    $deductions_query = "SELECT deduction, note FROM payroll_deductions WHERE emp_id = ? AND month = ?";
    $stmt_ded = $conDB->prepare($deductions_query);
    $stmt_ded->bind_param("ss", $emp_id, $payroll_record['month_year']);
    $stmt_ded->execute();
    $deductions_result = $stmt_ded->get_result();
    while ($ded = $deductions_result->fetch_assoc()) {
        if (!empty($ded['deduction']) && $ded['deduction'] > 0) {
            $deductions[] = $ded;
        }
    }
}

// Get logo from settings
require_once('./includes/helper_functions.php');
$logo_path = get_setting($conDB, 'logo', 'assets/images/logo.png');
$company_name = get_setting($conDB, 'company_name', 'Al-Mutlak');

// Create PDF instance
$pdf = new \TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// Set document information
$pdf->SetCreator('Al-Mutlak WMS');
$pdf->SetAuthor('Al-Mutlak HR System');
$pdf->SetTitle('Payroll Slip - ' . $emprow['name']);
$pdf->SetSubject('Payroll Slip for ' . date('F Y', strtotime($month_year . '-01')));

// Remove default header/footer
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

// Set margins
$pdf->SetMargins(15, 15, 15);
$pdf->SetAutoPageBreak(TRUE, 15);

// Set RTL mode for Arabic
if ($is_rtl) {
    $pdf->setRTL(true);
}

// Add a page
$pdf->AddPage();

// Set font to support Arabic - usingaealarabiya which has excellent Arabic support
if ($is_rtl) {
    $pdf->SetFont('aealarabiya', '', 13);
} else {
    $pdf->SetFont('helvetica', '', 10);
}

// Add company logo centered
if (file_exists($logo_path)) {
    // Get page width and calculate center
    $pageWidth = $pdf->getPageWidth();
    $logoWidth = 45; // Logo width in mm
    $logoXPosition = ($pageWidth - $logoWidth) / 2; // Center the logo
    
    // Add logo at center top
    $pdf->Image($logo_path, $logoXPosition, 10, $logoWidth, 0, '', '', 'T', false, 300, 'C', false, false, 0, false, false, false);
    
    // Add space after logo to prevent overlap
    $pdf->Ln(35); // Add line break with 35mm space
}

// Build HTML content
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
    .row-item { padding: 5px 0; }
    .amount { font-weight: bold; color: #333; }
</style>

<div style="margin-top: 40px; margin-bottom: 20px;"></div>
<div class="header-box">
    <h2>' . __('payroll_slip', 'Payroll Slip') . ' - ' . date('F Y', strtotime($month_year . '-01')) . '</h2>
    <p><strong>' . __('employee_name', 'Employee Name') . ':</strong> ' . htmlspecialchars($emprow['name'] ?? '') . '</p>
    <p><strong>' . __('employee_id', 'Employee ID') . ':</strong> ' . htmlspecialchars($emprow['emp_id'] ?? '') . '</p>
    <p><strong>' . __('department', 'Department') . ':</strong> ' . htmlspecialchars(($is_rtl ? ($emprow['dep_nme_ar'] ?? '') : ($emprow['dep_nme'] ?? ''))) . '</p>
    <p><strong>' . __('generated_date', 'Generated Date') . ':</strong> ' . date('M d, Y') . '</p>
</div>

<h2>' . __('salary_summary', 'Salary Summary') . '</h2>
<table>
    <tr>
        <td><strong>' . __('basic_salary', 'Basic Salary') . '</strong></td>
        <td class="amount">' . number_format($payroll_record['basic_salary'] ?? 0, 2) . ' SAR</td>
    </tr>
    <tr>
        <td><strong>' . __('total_gross_salary', 'Total Gross Salary') . '</strong></td>
        <td class="amount">' . number_format($payroll_record['total_gross_salary'] ?? 0, 2) . ' SAR</td>
    </tr>
    <tr>
        <td><strong>' . __('total_deductions', 'Total Deductions') . '</strong></td>
        <td class="amount">' . number_format($payroll_record['total_deductions'] ?? 0, 2) . ' SAR</td>
    </tr>
</table>

<div class="section-title">' . strtoupper(__('earnings', 'EARNINGS')) . '</div>
<table cellpadding="5">';

// Add earnings items
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
    'total_benefits' => __('total_benefits', 'Additional Benefits')
];

foreach ($earnings as $key => $label) {
    if (isset($payroll_record[$key]) && $payroll_record[$key] > 0) {
        $html .= '<tr>
            <td>' . $label . '</td>
            <td class="amount">' . number_format($payroll_record[$key], 2) . ' SAR</td>
        </tr>';
    }
}

$html .= '<tr style="background-color: #e0f7fa;">
    <td><strong>' . __('total_gross_salary', 'Total Gross Salary') . '</strong></td>
    <td class="amount"><strong>' . number_format($payroll_record['total_gross_salary'] ?? 0, 2) . ' SAR</strong></td>
</tr>
</table>';

// Add deductions if any
if (!empty($payroll_record['total_deductions']) && count($deductions) > 0) {
    $html .= '<div class="section-title deduction-title">' . strtoupper(__('deductions', 'DEDUCTIONS')) . '</div>
    <table cellpadding="5">';
    
    foreach ($deductions as $ded) {
        $html .= '<tr>
            <td>' . htmlspecialchars($ded['note'] ?? __('deduction', 'Deduction')) . '</td>
            <td class="amount">' . number_format($ded['deduction'], 2) . ' SAR</td>
        </tr>';
    }
    
    $html .= '<tr style="background-color: #ffe0e0;">
        <td><strong>' . __('total_deductions', 'Total Deductions') . '</strong></td>
        <td class="amount"><strong>' . number_format($payroll_record['total_deductions'] ?? 0, 2) . ' SAR</strong></td>
    </tr>
    </table>';
}

// Net salary
$html .= '<div class="net-salary">
    ' . strtoupper(__('net_salary', 'NET SALARY')) . ': ' . number_format($payroll_record['net_salary'] ?? 0, 2) . ' SAR
</div>

<p style="margin-top: 30px; font-size: 9px; color: #666;">
    <em>' . __('payroll_generated_note', 'This is a computer-generated payroll slip. Generated on') . ' ' . date('Y-m-d H:i:s') . '</em>
</p>';

// Write HTML content
$pdf->writeHTML($html, true, false, true, false, '');

// Clean output buffer
ob_end_clean();

// Output PDF
$filename = 'Payroll_Slip_' . $emprow['emp_id'] . '_' . $month_year . '.pdf';
$pdf->Output($filename, 'D'); // D = Download
exit();
