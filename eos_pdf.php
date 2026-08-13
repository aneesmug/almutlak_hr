<?php
/**
 * END OF SERVICE PDF GENERATOR (TCPDF)
 * This script mirrors the layout and data logic of end_of_service_print.php
 */

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/session_check.php';
require_once __DIR__ . '/includes/emp_query.php';
// Load TCPDF - Path might need adjustment based on your server structure
// require_once __DIR__ . '/vendor/tecppdf/tcpdf.php'; 

// Check if TCPDF class exists, if not, assume it's in a standard include path or needs specific require
if (!class_exists('TCPDF')) {
    // Attempting common path if composer isn't used
    if (file_exists(__DIR__ . '/tcpdf/tcpdf.php')) {
        require_once __DIR__ . '/tcpdf/tcpdf.php';
    } else {
        die('TCPDF library not found. Please ensure it is installed.');
    }
}

class EOSPDF extends TCPDF {
    public function Footer() {
        $this->SetY(-28);
        // Footer font requirement: Helvetica
        $this->SetFont('helvetica', '', 8);
        $this->setRTL(true);

        $footer_html = '
        <table width="100%" cellpadding="2" cellspacing="0" style="border-top:1px solid #dee2e6; font-family: helvetica;">
            <tr>
                <td width="50%" align="center">
                    _________________________<br>
                    <strong>Compensation Signature</strong><br>
                    <span style="font-family: xnahid;">توقيع التعويض</span><br><br><br>
                    Date: ___________________
                </td>
                <td width="50%" align="center">
                    _________________________<br>
                    <strong>Company Representative</strong><br>
                    <span style="font-family: xnahid;">ممثل الشركة</span><br><br><br>
                    Date: ___________________
                </td>
            </tr>
        </table>';

        $this->writeHTMLCell(0, 0, '', '', $footer_html, 0, 0, false, true, 'C', true);
        $this->setRTL(false);
    }
}

$query = mysqli_query($conDB, "SELECT * FROM `admin_login` WHERE `id_iqama`='".$username."'");
if(mysqli_num_rows($query) == 1){
    
    // 1. DATA FETCHING (Mirroring end_of_service_print.php)
    $emprow = [];
    if (mysqli_num_rows($get_emp_data) > 0) {
        $emprow = mysqli_fetch_assoc($get_emp_data);
    } else {
        header("Location: ./reg_employee.php");
        exit();
    }

    $emp_id = mysqli_real_escape_string($conDB, $_GET['emp_id']);
    
    $get_eos_data = mysqli_query($conDB, "SELECT `emp_eos`.*, `eos_calc`.`details` FROM `emp_eos` LEFT JOIN `eos_calc` ON `eos_calc`.`cid` = `emp_eos`.`eos_reason` WHERE `emp_eos`.`emp_id`='".$emp_id."'");
    $eosrow = mysqli_fetch_assoc($get_eos_data) ?: [];

    $get_salary_data = mysqli_query($conDB, "SELECT * FROM `emp_salary` WHERE `status` = 1 AND `emp_id`='".$emp_id."'");
    $salaryrow = mysqli_fetch_assoc($get_salary_data) ?: [];

    $get_assets_data = mysqli_query($conDB, "SELECT ea.serial_number, ea.description, ea.assigned_date, a.name as asset_name FROM `employee_assets` ea LEFT JOIN `assets` a ON ea.asset_id = a.id WHERE ea.emp_id = '".$emp_id."' AND ea.status = 'Assigned'");
    $assigned_assets = mysqli_fetch_all($get_assets_data, MYSQLI_ASSOC);

    $get_loans_data = mysqli_query($conDB, "SELECT l.loan_type, l.loan_amount, l.total_payable, l.status, (l.total_payable - COALESCE((SELECT SUM(amount) FROM emp_loan_payments WHERE loan_id = l.id), 0)) as remaining_balance FROM `emp_loan` l WHERE l.emp_id = '".$emp_id."' AND l.status NOT IN ('processed', 'rejected') HAVING remaining_balance > 0");
    $outstanding_loans = mysqli_fetch_all($get_loans_data, MYSQLI_ASSOC);

    // 2. CALCULATIONS
    $years_age = '';
    if (!empty($emprow['dob'])) {
        $birth_date = new DateTime(date('Y-m-d', strtotime(str_replace('/', '-', $emprow['dob']))));
        $current_date = new DateTime();
        $years_age = $birth_date->diff($current_date)->y . " Years";
    }

    $basic_salary = (float)($salaryrow['basic'] ?? 0);
    $actual_salary_base = 0;
    if (!empty($salaryrow)) {
        $fields = ['basic', 'housing', 'transport', 'food', 'misc', 'cashier', 'fuel', 'tel', 'guard', 'other'];
        foreach($fields as $f) $actual_salary_base += (float)($salaryrow[$f] ?? 0);
    }

    $gosi_deduction = (float)($eosrow['gosi_deduction'] ?? 0);
    $last_month_salary_paid = (float)($eosrow['curt_month_salry'] ?? 0);
    $absent_days = (int)($eosrow['absent_days'] ?? 0);
    $deduction_hours = (float)($eosrow['deduction_hours'] ?? 0);
    $overtime_hours = (float)($eosrow['overtime_hours'] ?? 0);
    $overtime_days = (float)($eosrow['overtime_days'] ?? 0);
    $other_earnings = (float)($eosrow['other_earnings'] ?? 0);
    
    $overtime_earnings = 0;
    $overtime_hourly_rate = 0;
    if ($actual_salary_base > 0 && ($overtime_hours > 0 || $overtime_days > 0)) {
        $overtime_hourly_rate = (($basic_salary / 240) / 2) + ($actual_salary_base / 240);
        $overtime_earnings = ($overtime_hourly_rate * $overtime_hours) + ($overtime_hourly_rate * 8 * $overtime_days);
    }
    
    $loan_deduction = (float)($eosrow['deduct'] ?? 0);
    $net_payment = (float)($eosrow['net_payment'] ?? 0);

    $dailyRateDeduction = $actual_salary_base / 30;
    $hourlyRateDeduction = $dailyRateDeduction / 8;
    $absent_deduction_amount = $dailyRateDeduction * $absent_days;
    $hourly_deduction_amount = $hourlyRateDeduction * $deduction_hours;

    // 3. PDF CONFIGURATION
    $pdf = new EOSPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor('HR System');
    $pdf->SetTitle('Final Settlement - ' . $emprow['name']);
    
    // Remove default header/footer
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(true);
    
    // Set margins
    $pdf->SetMargins(10, 10, 10);
    $pdf->SetAutoPageBreak(TRUE, 35);
    $pdf->setFooterMargin(8);
    
    // Set default font - Helvetica for English (similar to Arial), xnahid for Arabic
    $pdf->SetFont('helvetica', '', 10);
    $pdf->AddPage();

    // 4. GENERATE HTML CONTENT
    $logo_path = get_setting($conDB, 'logo');
    
    $html = '
    <style>
        .header-table { width: 100%; border-bottom: 2px solid #444; margin-bottom: 10px; }
        .title-en { font-size: 20pt; font-weight: bold; text-align: right; font-family: helvetica;}
        .title-ar { font-size: 16pt; text-align: right; font-family: xnahid; }
        .section-header { background-color: #343a40; color: #ffffff; padding: 5px; font-weight: bold; width: 100%;}
        .section-header-ar { background-color: #343a40; color: #ffffff; padding: 5px; font-weight: bold; width: 100%; font-family: xnahid; direction: rtl; }
        .info-table { width: 100%; border-collapse: collapse; }
        .info-table td { padding: 4px; border-bottom: 1px dotted #ccc; }
        .label-en { font-weight: bold; text-align: left;font-size: 7pt !important; font-family: helvetica;}
        .label-ar { text-align: right; font-weight: bold; font-family: xnahid; direction: rtl; font-size: 7pt !important; }
        .fin-table { width: 100%; border-collapse: collapse; margin-top: 5px; }
        .fin-table th, .fin-table td { border: 1px solid #dee2e6; padding: 6px; }
        .bg-gray { background-color: #f8f9fa; }
        .text-right { text-align: right; }
        .text-danger { color: #dc3545; }
        .text-success { color: #28a745; }
        .net-row { background-color: #e9ecef; font-weight: bold; font-size: 12pt; }
        .ack-box { border-top: 1px solid #dee2e6; margin-top: 20px; font-size: 10pt; line-height: 1.5; width: 100%; word-wrap: break-word; white-space: normal; overflow-wrap: break-word; }
        .ack-ar { border-top: 1px solid #dee2e6; margin-top: 20px; font-size: 10pt; line-height: 1.5; font-family: xnahid; direction: rtl; text-align: right; width: 100%; word-wrap: break-word; white-space: normal; overflow-wrap: break-word; padding: 5px 0; }
        .ack-ar p { margin: 5px 0; word-wrap: break-word; white-space: normal; overflow-wrap: break-word; }
        .sig-table { width: 100%; margin-top: 40px; }
        .en { font-family: helvetica; }
        .ar { font-family: xnahid; direction: rtl; }
    </style>';

    // Header
    $html .= '
    <table class="header-table" cellpadding="0" cellspacing="0">
        <tr>
            <td width="30%"><img src="'.$logo_path.'" height="65"></td>
            <td width="70%" align="right">
                <span class="title-en en">FINAL SETTLEMENT</span><br>
                <span class="title-ar ar">مخالصة نهائية</span>
            </td>
        </tr>
    </table><br><br>';

    // Employee Information
    $html .= '
    <table width="100%" cellpadding="5" cellspacing="0" style="margin-bottom: 5px !important;">
        <tr>
            <td class="section-header en" width="50%">Employee Information</td>
            <td class="section-header-ar ar" width="50%" align="right">معلومات الموظف</td>
        </tr>
    </table>
    <table class="info-table" width="100%" cellpadding="4" style="margin-top: 5px !important;">
        <tr>
            <td class="label-en en" width="25%">Name: '.strtoupper($emprow['name']).'</td>
            <td class="label-ar ar" width="25%">اسم الموظف</td>
            <td class="label-en en" width="35%">Employee ID: '.strtoupper($emprow['emp_id']).'</td>
            <td class="label-ar ar" width="15%">رقم الموظف</td>
        </tr>
        <tr>
            <td class="label-en en" width="25%">Iqama / ID: '.$emprow['iqama'].'</td>
            <td class="label-ar ar" width="25%">رقم الإقامة</td>
            <td class="label-en en" width="35%">Department: '.$emprow['deptnme'].'</td>
            <td class="label-ar ar" width="15%">القسم</td>
        </tr>
        <tr>
            <td class="label-en en" width="25%">Passport: '.$emprow['passport_number'].'</td>
            <td class="label-ar ar" width="25%">رقم الجواز</td>
            <td class="label-en en" width="35%">Company: '.$emprow['compnme'].'</td>
            <td class="label-ar ar" width="15%">الشركة</td>
        </tr>
        <tr>
            <td class="label-en en" width="25%">Date of Birth: '.(!empty($emprow['dob']) ? format_safe_date($emprow['dob'], 'M d, Y') : '').' (Age: '.$years_age.')</td>
            <td class="label-ar ar" width="25%">تاريخ الميلاد</td>
            <td class="label-en en" width="35%">Date Hired: '.format_safe_date($emprow['joining_date'] ?? null, 'M d, Y').'</td>
            <td class="label-ar ar" width="15%">تاريخ التعيين</td>
        </tr>
        <tr>
            <td class="label-en en" width="25%">Nationality: '.$emprow['country_name'].'</td>
            <td class="label-ar ar" width="25%">الجنسية</td>
            <td class="label-en en" width="35%">Termination Date: '.format_safe_date($emprow['ter_date'] ?? null, 'M d, Y').'</td>
            <td class="label-ar ar" width="15%">تاريخ الإنهاء</td>
        </tr>';
    
    $html .= '</table><br>';

    // Service Summary
    $html .= '
    <table width="100%" cellpadding="5" cellspacing="0" style="margin-bottom: 10px;">
        <tr>
            <td class="section-header en" width="50%">Service Period Summary</td>
            <td class="section-header-ar ar" width="50%" align="right">ملخص فترة الخدمة</td>
        </tr>
    </table>
        <table class="info-table" width="100%" cellpadding="4" style="margin-top: 5px !important;">
        <tr>
            <td class="label-en en" width="16.6%">Years: '.$eosrow['t_years'].'</td>
            <td class="label-ar ar" width="16.6%">السنوات</td>
            <td class="label-en en" width="16.6%">Months: '.$eosrow['t_months'].'</td>
            <td class="label-ar ar" width="16.6%">الأشهر</td>
            <td class="label-en en" width="16.6%">Days: '.$eosrow['t_days'].'</td>
            <td class="label-ar ar" width="16.6%">الأيام</td>
        </tr>
        <tr>
            <td class="label-en en" width="50%">End of Service Reason: '.(!empty($eosrow['leaving_reason']) ? $eosrow['leaving_reason'] : 'N/A').'</td>
            <td class="label-ar ar" width="50%">سبب نهاية الخدمة</td>
        </tr>
    </table><br>';

    // Assets Section (Conditional)
    if (!empty($assigned_assets)) {
        $html .= '
        <table width="100%" cellpadding="5" cellspacing="0" style="margin-bottom: 10px;">
            <tr>
                <td class="section-header en" width="50%">Assets for Clearance</td>
                <td class="section-header-ar ar" width="50%" align="right">الأصول للتسليم</td>
            </tr>
        </table>
        <table class="fin-table" width="100%">
            <tr class="bg-gray">
                <th width="40%">Asset Type</th>
                <th width="30%">Serial No.</th>
                <th width="30%">Assigned Date</th>
            </tr>';
        foreach ($assigned_assets as $asset) {
            $html .= '<tr><td>'.$asset['asset_name'].'</td><td>'.$asset['serial_number'].'</td><td>'.$asset['assigned_date'].'</td></tr>';
        }
        $html .= '</table><br>';
    }

    // Financial Settlement
    $html .= '
    <table width="100%" cellpadding="5" cellspacing="0" style="margin-bottom: 10px;">
        <tr>
            <td class="section-header en" width="50%">Financial Settlement</td>
            <td class="section-header-ar ar" width="50%" align="right">التسوية المالية</td>
        </tr>
    </table>
    <table class="info-table" width="100%" cellpadding="4">
        <tr>
            <td class="label-en en" width="33.33%">End of Service Amount (EOS)</td>
            <td class="label-ar ar" width="33.33%">مبلغ نهاية الخدمة</td>
            <td class="text-right" width="33.33%">'.number_format((float)($eosrow['eos_amount'] ?? 0), 2).'</td>
        </tr>
        <tr class="bg-gray">
            <td class="label-en en" width="33.33%">Vacation Balance ('.number_format((float)($eosrow['anul_vac_days'] ?? 0), 2).' days)</td>
            <td class="label-ar ar" width="33.33%">رصيد الإجازات</td>
            <td class="text-right" width="33.33%">'.number_format((float)($eosrow['anul_vac_salry'] ?? 0), 2).'</td>
        </tr>
        <tr>
            <td class="label-en en" width="33.33%">Salary for Last Month ('.($eosrow['curt_month_days'] ?? 0).' days)</td>
            <td class="label-ar ar" width="33.33%">راتب الشهر الأخير</td>
            <td class="text-right" width="33.33%">'.number_format($last_month_salary_paid, 2).'</td>
        </tr>';

    if ($overtime_hours > 0 || $overtime_days > 0) {
        if ($overtime_hours > 0) {
            $html .= '
        <tr>
            <td class="label-en en text-success" width="33.33%">Overtime (Hours) - '.number_format($overtime_hours, 2).' hrs</td>
            <td class="label-ar ar text-success" width="33.33%">العمل الإضافي (ساعات)</td>
            <td width="33.33%" class="text-right text-success">+'.number_format($overtime_hourly_rate * $overtime_hours, 2).'</td>
        </tr>';
        }
        if ($overtime_days > 0) {
            $html .= '
        <tr>
            <td class="label-en en text-success" width="33.33%">Overtime (Days) - '.number_format($overtime_days, 2).' days</td>
            <td class="label-ar ar text-success" width="33.33%">العمل الإضافي (أيام)</td>
            <td width="33.33%" class="text-right text-success">+'.number_format($overtime_hourly_rate * 8 * $overtime_days, 2).'</td>
        </tr>';
        }
    }

    if ($other_earnings > 0.01) {
        $html .= '
        <tr>
            <td class="label-en en text-success" width="33.33%">Other Earnings</td>
            <td class="label-ar ar text-success" width="33.33%">أرباح أخرى</td>
            <td width="33.33%" class="text-right text-success">+'.number_format($other_earnings, 2).'</td>
        </tr>';
    }

    $html .= '
        <tr class="text-danger">
            <td class="label-en en" width="33.33%" style="font-size: 10pt;"><strong>Deductions</strong></td>
            <td width="33.33%"></td>
            <td class="label-ar ar" width="33.33%" style="font-size: 10pt;"><strong>الخصومات</strong></td>
        </tr>';
    
    // Calculate total deductions
    $total_deductions = 0;
    
    if ($gosi_deduction > 0.01) {
        $html .= '
        <tr>
            <td class="label-en en text-danger" width="33.33%">GOSI Deduction</td>
            <td class="label-ar ar text-danger" width="33.33%">خصم التأمينات</td>
            <td width="33.33%" class="text-right text-danger">-'.number_format($gosi_deduction, 2).'</td>
        </tr>';
        $total_deductions += $gosi_deduction;
    }
    if ($absent_days > 0) {
        $html .= '
        <tr>
            <td class="label-en en text-danger" width="33.33%">Absent ('.$absent_days.') Days</td>
            <td class="label-ar ar text-danger" width="33.33%">أيام ('.$absent_days.') الغياب</td>
            <td width="33.33%" class="text-right text-danger">-'.number_format($absent_deduction_amount, 2).'</td>
        </tr>';
        $total_deductions += $absent_deduction_amount;
    }
    if ($loan_deduction > 0.01) {
        $html .= '
        <tr>
            <td class="label-en en text-danger" width="33.33%">Loan / Other Deductions</td>
            <td class="label-ar ar text-danger" width="33.33%">القروض والخصومات الأخرى</td>
            <td width="33.33%" class="text-right text-danger">-'.number_format($loan_deduction, 2).'</td>
        </tr>';
        $total_deductions += $loan_deduction;
    }
    
    // Add Total Deductions row
    $html .= '
        <tr class="text-danger">
            <td class="label-en en" width="33.33%"><strong>Total Deductions</strong></td>
            <td class="label-ar ar" width="33.33%"><strong>إجمالي الخصومات</strong></td>
            <td width="33.33%" class="text-right"><strong>-'.number_format($total_deductions, 2).'</strong></td>
        </tr>';

    $html .= '
        <tr class="net-row">
            <td class="label-en en" width="33.33%"><strong>NET PAYMENT DUE</strong></td>
            <td class="label-ar ar" width="33.33%"><strong>صافي الدفع</strong></td>
            <td width="33.33%" class="text-right"><strong>'.number_format($net_payment, 2).' SAR</strong></td>
        </tr>
    </table><br>';

    // Acknowledgment
    $html .= '
    <div style="margin-top: 20px; padding-top: 10px; border-top: 1px solid #dee2e6; width: 100%;">
        <p style="margin: 0 0 8px 0; font-family: xnahid; font-size: 9pt;">I acknowledge and undertake, the employee / <span style="color: #dc3545;">'.strtoupper($emprow['name']).'</span> ID No <span style="color: #dc3545;">'.$emprow['iqama'].'</span>. I have received all of my statutory dues from overtime and wages according to the Labor Law and Workers from Al-Mutlak Trading.</p>
    </div>';

    // Output PDF - Page 1 (up to the English acknowledgment)
    $pdf->writeHTML($html, true, false, true, false, '');
    
    // Arabic Acknowledgment - Use TCPDF direct methods for proper RTL color handling
    $emp_name = getDisplayName($emprow['name'],'ar');
    $emp_iqama = $emprow['iqama'];
    
    $pdf->SetFont('xnahid', '', 9);
    $pdf->SetRTL(true); // Enable RTL mode for Arabic
    
    // Part 1: "أقر وأتعهد أنا الموظف / " (black)
    $pdf->SetTextColorArray([0, 0, 0]);
    $pdf->SetX(10);
    $pdf->Write(5, 'أقر وأتعهد أنا الموظف / ');
    
    // Part 2: Employee Name (red)
    $pdf->SetTextColorArray([220, 53, 69]); // #dc3545 in RGB
    $pdf->Write(5, $emp_name);
    
    // Part 3: " برقم هوية اقامة / " (black)
    $pdf->SetTextColorArray([0, 0, 0]);
    $pdf->Write(5, ' برقم هوية اقامة / ');
    
    // Part 4: Employee Iqama (red)
    $pdf->SetTextColorArray([220, 53, 69]); // #dc3545 in RGB
    $pdf->Write(5, $emp_iqama);
    
    // Part 5: Rest of text (black)
    $pdf->SetTextColorArray([0, 0, 0]);
    $pdf->Write(5, ' لقد استلمت كافة مستحقاتي القانونية من أجور العمل الإضافي والأجور حسب قانون العمل والعمال في شركة المطلق للتجارة منذ بداية عملهم حتى تاريخ ترك العمل.');
    
    $pdf->SetRTL(false); // Disable RTL after Arabic section
    
    // Signatures are rendered in the footer on every page.
    
    // ========================================
    // PAGE 2 - SALARY INFORMATION & BANK DETAILS
    // ========================================
    
    // Add new page for Salary & Bank Details
    
    $pdf->SetFont('helvetica', '', 10);
    $pdf->AddPage();
    
    // Fetch bank details from employees table
    $bank_query = mysqli_query($conDB, "SELECT bank_name, iban, payment_type FROM employees WHERE emp_id = '".$emp_id."'");
    $bank_details = mysqli_fetch_assoc($bank_query) ?: [];
    
    // Payment type mapping
    $payment_types = [
        '1' => 'Bank Transfer',
        '2' => 'Cash Payment',
        '3' => 'On Hold'
    ];
    $payment_type_text = $payment_types[$bank_details['payment_type'] ?? '1'] ?? 'Bank Transfer';
    
    // Build Page 2 HTML
    $html_page2 = '
    <style>
        .header-table { width: 100%; border-bottom: 2px solid #444; margin-bottom: 10px; }
        .title-en { font-size: 20pt; font-weight: bold; text-align: right;}
        .title-ar { font-size: 16pt; text-align: right; font-family: xnahid; }
        .section-header { background-color: #343a40; color: #ffffff; padding: 5px; font-weight: bold; width: 100%; }
        .section-header-ar { background-color: #343a40; color: #ffffff; padding: 5px; font-weight: bold; width: 100%; font-family: xnahid; direction: rtl; }
        .info-table { width: 100%; border-collapse: collapse; }
        .info-table th { background-color: #f8f9fa; border: 1px solid #dee2e6; padding: 8px; font-weight: bold; text-align: left; }
        .info-table td { border: 1px solid #dee2e6; padding: 8px; }
        .label-ar { text-align: right; font-weight: bold; font-family: xnahid; direction: rtl; }
        .label-en { font-weight: bold; text-align: left;}
        .bg-gray { background-color: #f8f9fa; }
        .text-right { text-align: right; }
        .total-row { background-color: #e9ecef; font-weight: bold; }
    </style>';
    
    // Header for Page 2
    $html_page2 .= '
    <table class="header-table" cellpadding="0" cellspacing="0">
        <tr>
            <td width="30%"><img src="'.$logo_path.'" height="65"></td>
            <td width="70%" align="right">
                <span class="title-en en">SALARY & BANK INFORMATION</span><br>
                <span class="title-ar ar">تفاصيل الراتب والمعلومات البنكية</span>
            </td>
        </tr>
    </table><br>';
    
    // Employee Name & ID
    $html_page2 .= '
    <table width="100%" cellpadding="4" style="margin-bottom: 10px;">
        <tr>
            <td width="50%" class="label-en en"><strong>Employee: '.strtoupper($emprow['name']).'</strong></td>
            <td width="50%" class="label-ar ar"><strong>الموظف</strong></td>
        </tr>
        <tr>
            <td width="50%" class="label-en en">ID: '.$emprow['iqama'].'</td>
            <td width="50%" class="label-ar ar">رقم الإقامة</td>
        </tr>
    </table><br>';
    
    // SALARY INFORMATION TABLE
    $html_page2 .= '
    <table width="100%" cellpadding="5" cellspacing="0" style="margin-bottom: 10px;">
        <tr>
            <td class="section-header en" width="50%">Monthly Salary Breakdown</td>
            <td class="section-header-ar ar" width="50%" align="right">تفصيل الراتب الشهري</td>
        </tr>
    </table>
    
    <table class="info-table" width="100%">';
    
    // Salary Components from emp_salary table
    $salary_components = [
        'basic' => 'Basic Salary / الراتب الأساسي',
        'housing' => 'Housing Allowance / بدل السكن',
        'transport' => 'Transportation / بدل النقل',
        'food' => 'Food Allowance / بدل الغذاء',
        'misc' => 'Miscellaneous / متفرقات',
        'cashier' => 'Cashier / أمين الصندوق',
        'fuel' => 'Fuel / الوقود',
        'tel' => 'Telephone / الهاتف',
        'guard' => 'Guard / الحارس',
        'other' => 'Other / أخرى'
    ];
    
    $total_salary = 0;
    foreach ($salary_components as $key => $label) {
        $amount = (float)($salaryrow[$key] ?? 0);
        if ($amount > 0) {
            $total_salary += $amount;
            $html_page2 .= '
        <tr>
            <td class="label-ar" width="60%">'.$label.'</td>
            <td width="40%" class="text-right">'.number_format($amount, 2).'</td>
        </tr>';
        }
    }
    
    // Total Salary
    $html_page2 .= '
        <tr class="total-row">
            <td class="label-ar" width="60%"><strong>Total Monthly Salary / إجمالي الراتب الشهري</strong></td>
            <td width="40%" class="text-right"><strong>'.number_format($total_salary, 2).'</strong></td>
        </tr>
    </table><br>';
    
    // BANK DETAILS TABLE
    $html_page2 .= '
    <table width="100%" cellpadding="5" cellspacing="0" style="margin-bottom: 10px; margin-top: 20px;">
        <tr>
            <td class="section-header en" width="50%">Bank Account Details</td>
            <td class="section-header-ar ar" width="50%" align="right">بيانات الحساب البنكي</td>
        </tr>
    </table>
    
    <table class="info-table" width="100%">
        <tr>
            <td class="label-ar" width="60%">Bank Name / اسم البنك</td>
            <td width="40%" class="text-right">'.(!empty($emprow['b_name']) ? $emprow['b_name'] : 'N/A').'</td>
        </tr>
        <tr>
            <td class="label-ar" width="60%">IBAN (International Bank Account Number) / الآيبان</td>
            <td width="40%" class="text-right">'.(!empty($emprow['iban']) ? $emprow['iban'] : 'N/A').'</td>
        </tr>
        <tr>
            <td class="label-ar" width="60%">Payment Type / طريقة الدفع</td>
            <td width="40%" class="text-right">'.$payment_type_text.'</td>
        </tr>
    </table>';
    
    // Output Page 2
    $pdf->writeHTML($html_page2, true, false, true, false, '');
    
    // Clean any previous output buffer
    ob_end_clean();
    
    // Close and output PDF document with both pages
    $pdf->Output('Final_Settlement_'.$emprow['empid'].'.pdf', 'I');

} else {
    echo "Unauthorized Access.";
}
?>