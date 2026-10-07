<?php
/*******************************************************************************************************************
 * MODIFICATION SUMMARY (007-employee_profile.php):
 * 1. SINGLE A4 PAGE: Standalone print sheet (no sidebar/topbar). The sheet is laid out at the exact A4 printable
 * size and auto-scaled by a small script so the whole profile always prints on ONE page.
 * 2. SYNCED WITH view_employee.php: probation, city/location/sub-department, employee type, supervisor, sponsorship,
 * overtime / emergency-vacation flags, payment type, GOSI, vacation balance, additional information, last salary
 * increment, EOS estimate, active loan, and the current emp_vacation / emp_loan / emp_eos column names.
 * 3. ACCESS CONTROL: Same canEmployeeSupervisorAccess() gate as view_employee.php, plus the salary / EOS /
 * additional-information special access keys.
 * 4. HISTORY LISTS: Loans, vacations, assets and notes show the latest rows only (with a "latest N of M" hint) so
 * the sheet stays on one page. Full history lives on view_employee.php.
 *******************************************************************************************************************/
    require_once __DIR__ . '/includes/db.php';
    require_once __DIR__ . '/includes/session_check.php';
    require_once __DIR__ . '/includes/helper_functions.php';
    require_once __DIR__ . '/includes/special_access_helper.php';
    require_once __DIR__ . '/includes/eos_estimate_helper.php';

    // emp_query.php places emp_id straight into its SQL, so only a plain numeric ID may reach it.
    if (isset($_GET['emp_id']) && !ctype_digit((string)$_GET['emp_id'])) {
        header("Location: ./reg_employee.php");
        exit;
    }

    $query = mysqli_query($conDB, "SELECT * FROM `admin_login` WHERE `id_iqama`='".$username."'");
    if (!$query || mysqli_num_rows($query) != 1) {
        header("Location: ./reg_employee.php");
        exit;
    }

    include("./includes/avatar_select.php");

    include("./includes/Hijri_GregorianConvert.php");
    $DateConv = new Hijri_GregorianConvert;
    $format = "YYYY-MM-DD";

    require("./includes/emp_query.php");

    if (!$get_emp_data || mysqli_num_rows($get_emp_data) === 0) {
        header("Location: ./reg_employee.php");
        exit;
    }
    $emprow = mysqli_fetch_assoc($get_emp_data);

    // Same unified access rule as view_employee.php (admin, HR, department manager, direct supervisor, self).
    $user_data = [
        'emp_id' => $_SESSION['auth_user']['emp_id'] ?? $empid,
        'dept' => $_SESSION['auth_user']['dept'] ?? $user_dept,
        'comp_no' => $_SESSION['auth_user']['comp_no'] ?? 1,
        'user_type' => $user_type,
        'accessible_departments' => getAccessibleDepartments(true)
    ];
    $employee_data = [
        'emp_id' => $emprow['emp_id'] ?? $emprow['empid'],
        'supervisor_id' => $emprow['supervisor_id'] ?? null,
        'dept' => $emprow['dept'] ?? null,
        'comp_no' => $emprow['comp_no'] ?? 1
    ];
    if (!canEmployeeSupervisorAccess($employee_data, $user_data, $user_role ?? '')) {
        $_SESSION['error_msg'] = sprintf(
            '<div class="col-xl-12">
                <div class="alert alert-danger bg-danger text-white border-0" role="alert">
                    <b>%s</b>
                    <h4>%s</h4>
                </div>
            </div>',
            __('access_denied', 'Access Denied!'),
            __('access_denied_employee_view_message', "You don't have access to view this employee. Your access is limited to employees in your department and company.")
        );
        header("Location: ./dashboard.php");
        exit;
    }

    // --- Permissions (same keys as view_employee.php) ---
    $canViewSalary = (
        ($is_system_admin ?? false) || ($isHR ?? false) || ($isDeptHr ?? false)
        || user_has_special_access($conDB, $empid ?? '', 'view_employee_salary_value', $user_role ?? '', $user_type ?? '', $is_system_admin ?? false)
    );
    $canViewEosValue = (
        ($is_system_admin ?? false) || ($isHR ?? false) || ($isDeptHr ?? false)
        || user_has_special_access($conDB, $empid ?? '', 'view_employee_eos_value', $user_role ?? '', $user_type ?? '', $is_system_admin ?? false)
    );
    $canViewAdditionalInfo = (
        ($is_system_admin ?? false)
        || user_has_special_access($conDB, $empid ?? '', 'view_employee_additional_info', $user_role ?? '', $user_type ?? '', $is_system_admin ?? false)
    );

    // --- Print options: ?hide=salary,bank,... (comma list chosen in the view_employee.php print dialog) ---
    // Hiding only narrows what the permissions above already allow; it can never reveal anything.
    $printSections = ['photo', 'ids', 'personal', 'contact', 'bank', 'employment', 'salary', 'vacation_balance', 'additional', 'assets', 'loans', 'vacations', 'eos', 'notes'];
    $hiddenSections = array_intersect($printSections, array_map('trim', explode(',', (string)($_GET['hide'] ?? ''))));
    $show = function ($section) use ($hiddenSections) {
        return !in_array($section, $hiddenSections, true);
    };
    $canViewSalary = $canViewSalary && $show('salary');
    $canViewEosValue = $canViewEosValue && $show('eos');
    $canViewAdditionalInfo = $canViewAdditionalInfo && $show('additional');

    // --- Output helpers ---
    $rtl = ($is_rtl ?? false);
    $na = __('not_available', 'N/A');
    $e = function ($v) {
        return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
    };
    $val = function ($v) use ($e, $na) {
        return ($v === null || trim((string)$v) === '') ? '<span class="muted">' . $e($na) . '</span>' : $e($v);
    };
    // Translate a stored status/type value (e.g. "Local Vacation", "pending_approval").
    $tr = function ($v) {
        $v = trim((string)($v ?? ''));
        if ($v === '') {
            return '';
        }
        return __(strtolower(str_replace(' ', '_', $v)), ucwords(str_replace('_', ' ', $v)));
    };
    $money = function ($n) {
        return number_format((float)$n, 2) . ' <i class="icon-saudi_riyal"></i>';
    };
    $dt = function ($v, $fmt = 'd M Y') use ($na) {
        return format_safe_date($v, $fmt, $na);
    };
    $pick = function ($en, $ar) use ($rtl) {
        return ($rtl && !empty($ar)) ? $ar : $en;
    };
    $badge = function ($text, $tone = 'mute') use ($e) {
        return '<span class="bdg bdg-' . $tone . '">' . $e($text) . '</span>';
    };
    $field = function ($label, $html) use ($e) {
        echo '<div class="f"><span class="k">' . $e($label) . '</span><span class="v">' . $html . '</span></div>';
    };
    // Gregorian date with its Hijri equivalent.
    $gregWithHijri = function ($greg) use ($DateConv, $format, $e, $val) {
        if (!is_valid_date_value($greg)) {
            return $val(null);
        }
        return '<bdi>' . $e($greg) . '</bdi> <span class="alt"><bdi>' . $e($DateConv->GregorianToHijri($greg, $format)) . '</bdi> ' . $e(__('hijri', 'Hijri')) . '</span>';
    };

    $empId = (string)$emprow['empid'];

    // --- Salary ---
    $salaryItems = ['basic', 'housing', 'transport', 'food', 'misc', 'cashier', 'fuel', 'tel', 'other', 'guard'];
    $shownItems = [];
    $salary_get = 0;
    foreach ($salaryItems as $item) {
        $salary_get += (float)($emprow[$item] ?? 0);
        if (!empty($emprow[$item]) && $emprow[$item] != "0") {
            $shownItems[] = $item;
        }
    }
    $totalSalary = $salary_get ?: (float)($emprow['salary'] ?? 0);
    $paymentTypeText = ($emprow['payment_type'] == 1 ? __('bank_transfer', 'Bank Transfer') : ($emprow['payment_type'] == 2 ? __('cash_payment', 'Cash Payment') : __('about_to_hold', 'About to Hold')));

    $last_salary_increment = null;
    if ($canViewSalary) {
        $last_si_stmt = mysqli_prepare($conDB, "SELECT approved_amount, increment_amount, COALESCE(last_increment_date, DATE(last_modified)) AS effective_date FROM `emp_salary_increment` WHERE `emp_id` = ? AND `current_status` = 'approved' ORDER BY COALESCE(last_increment_date, DATE(last_modified)) DESC LIMIT 1");
        if ($last_si_stmt) {
            mysqli_stmt_bind_param($last_si_stmt, "s", $empId);
            mysqli_stmt_execute($last_si_stmt);
            $last_salary_increment = mysqli_fetch_assoc(mysqli_stmt_get_result($last_si_stmt)) ?: null;
            mysqli_stmt_close($last_si_stmt);
        }
    }

    // --- Dates / service ---
    $years = '';
    if (is_valid_date_value($emprow['dob'] ?? null)) {
        $years = (new DateTime($emprow['dob']))->diff(new DateTime())->y . ' ' . __('years', 'Years');
    }

    $joinValid = is_valid_date_value($emprow['joining_date'] ?? null);
    $joindiff = $joinValid ? (new DateTime($emprow['joining_date']))->diff(new DateTime()) : null;
    $serviceText = $joindiff
        ? sprintf('%d %s %d %s', $joindiff->y, __('years', 'Years'), $joindiff->m, __('months', 'Months'))
        : $na;

    // Same rule as view_employee.php: probation months from the employee record, 90 days when not set.
    $joinDays = $joindiff ? $joindiff->days : PHP_INT_MAX;
    $probationStatus = ($emprow['probation'] !== NULL && $emprow['probation'] !== "")
        ? (($joinDays > ((int)$emprow['probation'] * 30))
            ? __('no_probation', 'No Probation')
            : (int)$emprow['probation'] . " " . __('months', 'Months'))
        : (($joinDays < 90)
            ? __('under_probation', 'Under Probation')
            : __('no_probation', 'No Probation'));

    $contractExpiryIso = computeContractExpiry($emprow['joining_date'] ?? null, isset($emprow['vac_period']) ? (int)$emprow['vac_period'] : null, 'Y-m-d');

    $iqamaExpiryGreg = !empty($emprow['iqama_exp']) ? $DateConv->HijriToGregorian($emprow['iqama_exp'], $format) : '';

    // --- Employee status ---
    if ((string)$emprow['status'] === '1') {
        $statusBadge = $badge(__('active', 'Active'), 'ok');
    } elseif ($emprow['note'] == 'expired') {
        $statusBadge = $badge(__('expired', 'Expired'), 'bad');
    } elseif ($emprow['note'] == 'terminat') {
        $statusBadge = $badge(__('terminated', 'Terminated'), 'bad');
    } else {
        $statusBadge = $badge(__('inactive', 'Inactive'), 'mute');
    }

    // --- Medical insurance (yearly renewed - the active row is the current one) ---
    $mi_stmt = mysqli_prepare($conDB, "SELECT insurance_no, medical_expiry, medical_class FROM `employee_medical_insurance` WHERE `emp_id` = ? AND `status` = 'active' LIMIT 1");
    mysqli_stmt_bind_param($mi_stmt, "s", $empId);
    mysqli_stmt_execute($mi_stmt);
    $current_medical_insurance = mysqli_fetch_assoc(mysqli_stmt_get_result($mi_stmt)) ?: null;
    mysqli_stmt_close($mi_stmt);

    // --- Additional information (HR/Payroll reference fields) ---
    $employee_additional_info = null;
    $monthly_leave_accrual_calculated = null;
    if ($canViewAdditionalInfo && $canViewSalary) {
        $additional_info_stmt = mysqli_prepare($conDB, "SELECT * FROM `employee_additional_info` WHERE `emp_id` = ? LIMIT 1");
        mysqli_stmt_bind_param($additional_info_stmt, "s", $empId);
        mysqli_stmt_execute($additional_info_stmt);
        $employee_additional_info = mysqli_fetch_assoc(mysqli_stmt_get_result($additional_info_stmt)) ?: null;
        mysqli_stmt_close($additional_info_stmt);

        // contract_period.vac_period is the total for the whole contract term, so divide by the
        // year count in the period label to get the annual rate, then by 12 for the monthly one.
        if (!empty($emprow['vac_period'])) {
            $contract_period_stmt = mysqli_prepare($conDB, "SELECT `period`, `vac_period` FROM `contract_period` WHERE `id` = ? LIMIT 1");
            mysqli_stmt_bind_param($contract_period_stmt, "i", $emprow['vac_period']);
            mysqli_stmt_execute($contract_period_stmt);
            $contract_period_row = mysqli_fetch_assoc(mysqli_stmt_get_result($contract_period_stmt));
            mysqli_stmt_close($contract_period_stmt);
            if ($contract_period_row && (float)$contract_period_row['vac_period'] > 0) {
                $contract_years = 1;
                if (preg_match('/^\s*(\d+(?:\.\d+)?)/', (string)$contract_period_row['period'], $years_match)) {
                    $contract_years = max(1, (float)$years_match[1]);
                }
                $monthly_leave_accrual_calculated = ((float)$contract_period_row['vac_period'] / $contract_years) / 12;
            }
        }
    }

    // --- Vacation balance + counters ---
    $vac_total = (float)($emprow['vacation_days'] ?? 0);
    $vac_used = (float)($emprow['used_days'] ?? 0);
    $vac_available = (float)($emprow['available_balance'] ?? 0);
    $hasVacBalance = ($vac_total > 0 || $vac_used > 0 || $vac_available > 0);

    $local_vacation_count = 0;
    $local_vac_stmt = mysqli_prepare($conDB, "SELECT COUNT(*) AS cnt FROM `emp_vacation` WHERE `emp_id` = ? AND `vac_type` = 'Local Vacation' AND `current_status` IN ('approved','completed')");
    if ($local_vac_stmt) {
        mysqli_stmt_bind_param($local_vac_stmt, "s", $empId);
        mysqli_stmt_execute($local_vac_stmt);
        $local_vacation_count = (int)(mysqli_fetch_assoc(mysqli_stmt_get_result($local_vac_stmt))['cnt'] ?? 0);
        mysqli_stmt_close($local_vac_stmt);
    }

    // --- History lists: latest rows only so the sheet stays on one page. Rejected requests are left out. ---
    $maxVacations = 8;
    $maxLoans = 4;
    $maxAssets = 6;
    $maxNotes = 4;

    $vac_stmt = mysqli_prepare($conDB, "SELECT * FROM `emp_vacation` WHERE `emp_id` = ? AND (`request_inv_no` IS NULL OR `request_inv_no` NOT LIKE 'LEGACY-%') AND (`current_status` IS NULL OR `current_status` <> 'rejected') ORDER BY `id` DESC");
    mysqli_stmt_bind_param($vac_stmt, "s", $empId);
    mysqli_stmt_execute($vac_stmt);
    $vacation_history = mysqli_fetch_all(mysqli_stmt_get_result($vac_stmt), MYSQLI_ASSOC);
    mysqli_stmt_close($vac_stmt);

    $loan_stmt = mysqli_prepare($conDB, "SELECT l.*, (SELECT COALESCE(SUM(p.amount), 0) FROM `emp_loan_payments` p WHERE p.loan_id = l.id) AS total_paid FROM `emp_loan` l WHERE l.emp_id = ? AND (l.inv_no IS NULL OR l.inv_no NOT LIKE 'LEGACY-%') AND (l.status IS NULL OR l.status <> 'rejected') ORDER BY l.id DESC");
    mysqli_stmt_bind_param($loan_stmt, "s", $empId);
    mysqli_stmt_execute($loan_stmt);
    $loan_history = mysqli_fetch_all(mysqli_stmt_get_result($loan_stmt), MYSQLI_ASSOC);
    mysqli_stmt_close($loan_stmt);

    // Latest approved loan = the active one (same rule as the view_employee.php summary card).
    $active_loan = null;
    foreach ($loan_history as $loan) {
        if ($loan['status'] === 'approved') {
            $active_loan = $loan;
            break;
        }
    }

    $assets_stmt = mysqli_prepare($conDB, "SELECT ea.*, a.name AS asset_name FROM `employee_assets` ea JOIN `assets` a ON ea.asset_id = a.id WHERE ea.emp_id = ? AND ea.status = 'Assigned' ORDER BY ea.assigned_date DESC");
    mysqli_stmt_bind_param($assets_stmt, "s", $empId);
    mysqli_stmt_execute($assets_stmt);
    $assigned_assets = mysqli_fetch_all(mysqli_stmt_get_result($assets_stmt), MYSQLI_ASSOC);
    mysqli_stmt_close($assets_stmt);

    $car_info = !empty($emprow["car_id"]) ? car_get_info($emprow["car_id"]) : null;

    $notes_stmt = mysqli_prepare($conDB, "SELECT * FROM `emp_notice` WHERE `emp_id` = ? AND `is_deleted` = 0 ORDER BY `id` DESC");
    mysqli_stmt_bind_param($notes_stmt, "s", $empId);
    mysqli_stmt_execute($notes_stmt);
    $employee_notes = mysqli_fetch_all(mysqli_stmt_get_result($notes_stmt), MYSQLI_ASSOC);
    mysqli_stmt_close($notes_stmt);

    // --- End of service: final record if settled, otherwise the "if terminated today" estimate ---
    $eos_stmt = mysqli_prepare($conDB, "SELECT * FROM `emp_eos` WHERE `emp_id` = ? ORDER BY `id` DESC LIMIT 1");
    mysqli_stmt_bind_param($eos_stmt, "s", $empId);
    mysqli_stmt_execute($eos_stmt);
    $end_of_service = mysqli_fetch_assoc(mysqli_stmt_get_result($eos_stmt)) ?: null;
    mysqli_stmt_close($eos_stmt);

    $eos_estimate = ($canViewEosValue && !$end_of_service)
        ? calculate_current_eos_estimate($conDB, $empId, $emprow['joining_date'] ?? '')
        : null;

    // --- Summary tiles ---
    $tiles = [];
    $tiles[] = [__('working_period', 'Working Period'), $e($serviceText), $joinValid ? $e(__('joining_date', 'Joining Date') . ': ' . $dt($emprow['joining_date'])) : ''];
    $tiles[] = [__('contract_expiry_label', 'Contract Expiry'), $contractExpiryIso ? $e($dt($contractExpiryIso)) : $e($na), $e(translateContractPeriod($emprow['period'] ?? ''))];
    if ($show('vacation_balance')) {
        $tiles[] = [__('available_balance', 'Available Balance'), $e(number_format($vac_available, 2)) . ' <small>' . $e(__('days', 'Days')) . '</small>', $e(__('used_days', 'Used Days') . ': ' . number_format($vac_used, 1))];
    }
    if ($canViewSalary) {
        $tiles[] = [__('total_salary', 'Total Salary'), $money($totalSalary), $e($paymentTypeText)];
        if ($active_loan && $show('loans')) {
            $tiles[] = [__('active_loan_summary', 'Active Loan'), $money($active_loan['total_payable'] - $active_loan['total_paid']), $e($tr($active_loan['loan_type']) . ' · ' . __('remaining_balance', 'Remaining Balance'))];
        }
    }
    if ($eos_estimate && $eos_estimate['success']) {
        $tiles[] = [__('eos_estimate', 'EOS Estimate'), $money($eos_estimate['eos_amount']), $e(number_format($eos_estimate['service_years'], 2) . ' ' . __('years', 'Years'))];
    }

    $displayName = getDisplayName($emprow['name']);
    $jobTitle = $pick($emprow['jobname'], $emprow['jobname_ar']);
    $deptName = $pick($emprow['deptnme'], $emprow['deptnme_ar']);
    $companyName = $pick($emprow['compnme'], $emprow['compnme_ar'] ?? '');
    // 'white_logo' is the colour logo (for white backgrounds); 'logo' is the white one used on the dark sidebar.
    $companyLogo = get_setting($conDB, 'white_logo') ?: get_setting($conDB, 'logo');
?>
<!doctype html>
<html lang="<?= $current_lang ?? 'en' ?>" dir="<?= $rtl ? 'rtl' : 'ltr' ?>">

    <head>
        <meta charset="utf-8" />
        <title><?= $e($displayName) ?> - <?= $e(__('employee_profile', 'Employee Profile')) ?></title>
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
        <meta content="Anees Afzal" name="author" />

        <!-- App favicon -->
        <link rel="shortcut icon" href="<?=get_setting($conDB, 'favicon')?>">

        <link href="https://fonts.googleapis.com/css2?family=Rubik:wght@400;500;600;700&family=Amiri:wght@400;700&display=swap" rel="stylesheet">
        <style>
            @font-face {
                font-family: 'saudi_riyal';
                src: url('assets/fonts/saudi_riyal.woff2') format('woff2'),
                    url('assets/fonts/saudi_riyal.woff') format('woff'),
                    url('assets/fonts/saudi_riyal.ttf') format('truetype');
                font-weight: normal;
                font-style: normal;
            }
            .icon-saudi_riyal { font-style: normal; }
            .icon-saudi_riyal::before { content: "\e900"; font-family: 'saudi_riyal' !important; }

            :root {
                --ink: #1c2530;
                --muted: #66717f;
                --line: #dfe4ea;
                --tint: #f3f6fa;
                --accent: #1d4e89;
                --ok: #1e7e46;
                --warn: #9a6400;
                --bad: #b3261e;
                --info: #1d4e89;
                /* A4 minus the @page margin below */
                --page-w: 194mm;
                --page-h: 281mm;
            }
            @page { size: A4 portrait; margin: 8mm; }

            *, *::before, *::after { box-sizing: border-box; }
            html, body { margin: 0; padding: 0; }
            body {
                background: #e9edf2;
                color: var(--ink);
                font-family: "Rubik", "Segoe UI", Tahoma, Arial, sans-serif;
                line-height: 1.4;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            html[dir="rtl"] body { font-family: "Amiri", "Segoe UI", Tahoma, Arial, sans-serif; }
            html[dir="rtl"] #sheet { font-size: 11px; }

            .toolbar { display: flex; justify-content: center; gap: 8px; padding: 12px 16px 0; }
            .toolbar button {
                font: inherit; font-size: 13px; font-weight: 500; cursor: pointer;
                padding: 7px 16px; border-radius: 6px; border: 1px solid var(--accent);
                background: var(--accent); color: #fff;
            }
            .toolbar button.ghost { background: #fff; color: var(--accent); }

            .paper {
                width: calc(var(--page-w) + 16mm);
                margin: 12px auto 24px;
                padding: 8mm;
                background: #fff;
                box-shadow: 0 2px 14px rgba(28, 37, 48, .18);
            }
            /* Every size inside the sheet is in em, so the fit script below scales the whole sheet through this one font-size */
            #sheet { width: var(--page-w); font-size: 10px; }
            #pageProbe { position: absolute; top: 0; inset-inline-start: 0; width: var(--page-w); height: var(--page-h); visibility: hidden; pointer-events: none; }

            .muted { color: var(--muted); font-weight: 400; }
            .alt { color: var(--muted); font-weight: 400; font-size: .9em; white-space: nowrap; }
            small { font-size: .7em; font-weight: 500; color: var(--muted); }

            /* Header */
            .head { display: flex; align-items: center; gap: 1.2em; padding-bottom: .8em; border-bottom: 2px solid var(--accent); }
            .head .avatar { width: 6.2em; height: 6.2em; border-radius: .8em; object-fit: cover; border: 1px solid var(--line); flex: none; }
            .head .who { flex: 1; min-width: 0; }
            .head h1 { margin: 0 0 1px; font-size: 1.75em; font-weight: 700; line-height: 1.2; text-wrap: balance; }
            .head .role { margin: 0 0 .45em; color: var(--muted); font-size: 1.05em; }
            .head .role b { color: var(--ink); font-weight: 600; }
            .head .chips { display: flex; flex-wrap: wrap; gap: .5em; }
            .head .brand { flex: none; text-align: end; }
            .head .brand img { max-height: 3.4em; max-width: 15em; display: block; margin-inline-start: auto; }
            .head .brand .doc { margin-top: .4em; font-weight: 600; color: var(--accent); font-size: .95em; }

            .bdg {
                display: inline-block; padding: .1em .8em; border-radius: 2em; font-size: .88em; font-weight: 600;
                border: 1px solid currentColor; white-space: nowrap; line-height: 1.45;
            }
            .bdg-ok { color: var(--ok); background: #e8f5ec; }
            .bdg-warn { color: var(--warn); background: #fff4dc; }
            .bdg-bad { color: var(--bad); background: #fdecea; }
            .bdg-info { color: var(--info); background: #e7eff8; }
            .bdg-mute { color: var(--muted); background: #f1f3f5; }

            /* Summary tiles */
            .tiles { display: flex; gap: .6em; margin: .8em 0 .2em; }
            .tile { flex: 1 1 0; min-width: 0; padding: .5em .8em; background: var(--tint); border: 1px solid var(--line); border-radius: .6em; }
            .tile .k { color: var(--muted); font-size: .85em; font-weight: 500; }
            .tile .n { font-size: 1.3em; font-weight: 700; line-height: 1.3; font-variant-numeric: tabular-nums; }
            .tile .s { color: var(--muted); font-size: .85em; overflow-wrap: anywhere; }

            /* Sections */
            .cols { display: grid; grid-template-columns: 1fr 1fr; gap: 0 1.4em; align-items: start; }
            .sec { margin-top: .8em; break-inside: avoid; }
            .sec > h2 {
                display: flex; align-items: baseline; justify-content: space-between; gap: .8em;
                margin: 0 0 .2em; padding-bottom: .2em; border-bottom: 1px solid var(--accent);
                font-size: 1em; font-weight: 700; color: var(--accent);
            }
            html[dir="ltr"] .sec > h2, html[dir="ltr"] .tile .k { text-transform: uppercase; letter-spacing: .04em; }
            .sec > h2 .hint { font-weight: 400; color: var(--muted); text-transform: none; letter-spacing: 0; font-size: .9em; }

            .f { display: flex; gap: .8em; padding: .2em 0; border-bottom: 1px solid var(--line); }
            .f .k { flex: 0 0 38%; color: var(--muted); }
            .f .v { flex: 1; min-width: 0; font-weight: 600; overflow-wrap: anywhere; }
            .f.total { background: var(--tint); padding-inline: .5em; border-bottom: 0; }
            .f.total .v { color: var(--accent); }
            .g2 { display: grid; grid-template-columns: 1fr 1fr; gap: 0 1.4em; }
            .g3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 0 1.4em; }
            .g2 .f .k, .g3 .f .k, .f.total .k { flex: 1 1 0; min-width: 0; }
            .g2 .f .v, .g3 .f .v, .f.total .v { flex: 0 1 auto; text-align: end; overflow-wrap: normal; }
            /* Dates, numbers and English values keep their own reading order inside the Arabic (RTL) sheet */
            .f .v, .alt, .tile .n, .tile .s, table.t td, .foot span { unicode-bidi: plaintext; }
            /* ...which makes start/end follow the content, so alignment is pinned per page direction */
            html[dir="ltr"] .f .v, html[dir="ltr"] .tile .n, html[dir="ltr"] .tile .s, html[dir="ltr"] table.t td { text-align: left; }
            html[dir="rtl"] .f .v, html[dir="rtl"] .tile .n, html[dir="rtl"] .tile .s, html[dir="rtl"] table.t td { text-align: right; }
            html[dir="ltr"] .g2 .f .v, html[dir="ltr"] .g3 .f .v, html[dir="ltr"] .f.total .v, html[dir="ltr"] table.t td.num { text-align: right; }
            html[dir="rtl"] .g2 .f .v, html[dir="rtl"] .g3 .f .v, html[dir="rtl"] .f.total .v, html[dir="rtl"] table.t td.num { text-align: left; }

            table.t { width: 100%; border-collapse: collapse; font-variant-numeric: tabular-nums; }
            table.t th, table.t td { padding: .2em .5em; text-align: start; border-bottom: 1px solid var(--line); vertical-align: top; }
            table.t th { background: var(--tint); color: var(--muted); font-weight: 600; font-size: .92em; white-space: nowrap; }
            table.t td.num, table.t th.num { text-align: end; white-space: nowrap; }
            table.t .neg { color: var(--bad); font-weight: 600; }
            table.t .pos { color: var(--ok); font-weight: 600; }

            .foot { display: flex; justify-content: space-between; gap: 1.2em; margin-top: .8em; padding-top: .4em; border-top: 1px solid var(--line); color: var(--muted); font-size: .88em; }

            @media print {
                body { background: #fff; }
                .toolbar, #pageProbe { display: none !important; }
                .paper { width: auto; margin: 0; padding: 0; box-shadow: none; }
                #sheet { break-inside: avoid; }
            }
        </style>
    </head>
    <body>

        <div class="toolbar">
            <button type="button" onclick="window.print()"><?= $e(__('print_profile', 'Print Profile')) ?></button>
            <button type="button" class="ghost" onclick="window.close()"><?= $e(__('close', 'Close')) ?></button>
        </div>

        <div id="pageProbe"></div>

        <div class="paper">
            <div id="sheet">

                <!-- Header -->
                <div class="head">
                    <?php if ($show('photo')): ?><img class="avatar" src="<?= $e(getAvatarImagePath($emprow['avatar'] ?? '', $emprow['sex'] ?? 1)) ?>" alt=""><?php endif; ?>
                    <div class="who">
                        <h1><?= $e($displayName) ?></h1>
                        <p class="role"><b><?= $val($jobTitle) ?></b> &middot; <?= $val($deptName) ?> &middot; <?= $val($companyName) ?></p>
                        <div class="chips">
                            <?= $badge('#' . $empId, 'info') ?>
                            <?= $statusBadge ?>
                            <?php if (!empty($emprow['emptype'])): ?><?= $badge($tr($emprow['emptype']), 'mute') ?><?php endif; ?>
                        </div>
                    </div>
                    <div class="brand">
                        <?php if (!empty($companyLogo)): ?><img src="<?= $e($companyLogo) ?>" alt=""><?php endif; ?>
                        <div class="doc"><?= $e(__('employee_profile', 'Employee Profile')) ?></div>
                    </div>
                </div>

                <!-- Summary tiles -->
                <?php if (!empty($tiles)): ?>
                <div class="tiles">
                    <?php foreach ($tiles as $tile): ?>
                    <div class="tile">
                        <div class="k"><?= $e($tile[0]) ?></div>
                        <div class="n"><?= $tile[1] ?></div>
                        <div class="s"><?= $tile[2] ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <div class="cols">
                    <div class="col">

                        <!-- Personal Information -->
                        <?php if ($show('ids') || $show('personal')): ?>
                        <div class="sec">
                            <h2><?= $e(__('personal_information', 'Personal Information')) ?></h2>
                            <?php
                            if ($show('ids')) {
                                $field(__('iqama_id', 'Iqama / ID'), $val($emprow['iqama']));
                                $field(__('id_expiry', 'ID Expiry'), !empty($emprow['iqama_exp'])
                                    ? '<bdi>' . $e($iqamaExpiryGreg) . '</bdi> <span class="alt"><bdi>' . $e($emprow['iqama_exp']) . '</bdi> ' . $e(__('hijri', 'Hijri')) . '</span>'
                                    : $val(null));
                                $field(__('passport_no', 'Passport No'), $val($emprow['passport_number']));
                                $field(__('passport_expiry', 'Passport Expiry'), $gregWithHijri($emprow['passport_exp'] ?? null));
                            }
                            if ($show('personal')) {
                            $field(__('date_of_birth', 'Date of Birth'), is_valid_date_value($emprow['dob'] ?? null)
                                ? '<bdi>' . $e($emprow['dob']) . '</bdi> <span class="alt">' . $e($years) . '</span>'
                                : $val(null));
                            $field(__('country', 'Country'), $val($pick($emprow['country_name'], $emprow['country_name_ar'])));
                            $field(__('gender_blood_group', 'Gender | Blood Group'), $val($tr($emprow['sex'])) . ' | ' . $val($emprow['blood_type']));
                            $field(__('marital_status', 'Marital Status'), $val($tr($emprow['mar_status'])));
                            $field(__('tshirt_size', 'T-Shirt Size'), $val(ucfirst((string)$emprow['t_shirt_size'])));
                            }
                            ?>
                        </div>
                        <?php endif; ?>

                        <!-- Contact -->
                        <?php if ($show('contact')): ?>
                        <div class="sec">
                            <h2><?= $e(__('contact', 'Contact Information')) ?></h2>
                            <?php
                            $field(__('mobile', 'Mobile'), '<bdi>' . $val($emprow['mobile']) . '</bdi>');
                            $field(__('email', 'Email') . ' (' . __('personal', 'Personal') . ')', $val($emprow['email']));
                            $field(__('email', 'Email') . ' (' . __('company', 'Company') . ')', $val($emprow['c_email']));
                            $field(__('emergency_contact', 'Emergency Contact'), $val(trim(($emprow['emg_name'] ?? '') . ' - ' . ($emprow['emg_mobile'] ?? ''), ' -')));
                            $field(__('address', 'Address'), $val(getDisplayName(ucfirst((string)$emprow['address']))));
                            ?>
                        </div>
                        <?php endif; ?>

                        <!-- Bank, GOSI & Insurance -->
                        <?php if ($show('bank')): ?>
                        <div class="sec">
                            <h2><?= $e(__('bank_&_gosi_details', 'Bank & GOSI Details')) ?></h2>
                            <?php
                            $field(__('payment_type', 'Payment Type'), $e($paymentTypeText));
                            $field(__('bank_name', 'Bank Name'), $val($pick($emprow['b_name'], $emprow['b_name_ar'])));
                            $field(__('iban', 'IBAN'), '<bdi>' . $val($emprow['iban']) . '</bdi>');
                            $field(__('gosi_gosi_no', 'GOSI % | GOSI No'), $val($emprow['gosi']) . ' | ' . $val($emprow['gosi_no']));
                            $field(__('gosi_expiry', 'GOSI Expiry'), $val(trim(($emprow['date_hijri'] ?? '') . ' | ' . ($emprow['date_greg'] ?? ''), ' |')));
                            $field(__('insurance_no_class', 'Insurance No | Class'), $val($current_medical_insurance['insurance_no'] ?? null) . ' | ' . $val($current_medical_insurance['medical_class'] ?? null));
                            $field(__('insurance_expiry', 'Insurance Expiry'), $gregWithHijri($current_medical_insurance['medical_expiry'] ?? null));
                            ?>
                        </div>
                        <?php endif; ?>

                    </div>
                    <div class="col">

                        <!-- Employment -->
                        <?php if ($show('employment')): ?>
                        <div class="sec">
                            <h2><?= $e(__('employment', 'Employment Information')) ?></h2>
                            <?php
                            $field(__('joining_date', 'Joining Date'), $gregWithHijri($emprow['joining_date'] ?? null));
                            $field(__('contract_period', 'Contract Period'), $val(translateContractPeriod($emprow['period'] ?? '')));
                            $field(__('probation_period', 'Probation Period'), $e($probationStatus));
                            $field(__('sub_department_label', 'Sub-Department'), $val($pick($emprow['subdeptname'], $emprow['subdeptname_ar'])));
                            $field(__('city_label', 'City'), $val($pick($emprow['cityname'], $emprow['cityname_ar'])));
                            $field(__('location_label', 'Location'), $val($pick($emprow['locationname'], $emprow['locationname_ar'])));
                            $field(__('direct_supervisor', 'Direct Supervisor'), !empty($emprow['supervisor_name'])
                                ? $e(getDisplayName($emprow['supervisor_name'])) . ' <span class="alt">(' . $e($emprow['supervisor_emp_id']) . ')</span>'
                                : '<span class="muted">' . $e(__('not_assigned', 'Not Assigned')) . '</span>');
                            $field(__('sponsorship_label', 'Sponsorship'), $val($pick($emprow['sponsor'], $emprow['sponsor_ar'])));
                            $field(__('eligible_for_overtime', 'Eligible For Overtime'), $e(((string)($emprow['is_overtime_eligible'] ?? '0') === '1') ? __('yes', 'Yes') : __('no', 'No')));
                            $field(__('allow_emergency_vacation', 'Allow Emergency Vacation'), $e(((string)($emprow['allow_emergency_vacation'] ?? '0') === '1') ? __('yes', 'Yes') : __('no', 'No')));
                            ?>
                        </div>
                        <?php endif; ?>

                        <!-- Salary (left out entirely when unticked in the print dialog) -->
                        <?php if ($show('salary')): ?>
                        <div class="sec">
                            <h2><?= $e(__('salary_breakdown_header', 'Salary Breakdown')) ?></h2>
                            <?php if ($canViewSalary): ?>
                                <div class="g2">
                                    <?php foreach ($shownItems as $item) { $field(__($item, ucfirst($item)), $money($emprow[$item])); } ?>
                                </div>
                                <div class="f total"><span class="k"><?= $e(__('total_salary', 'Total Salary')) ?></span><span class="v"><?= $money($totalSalary) ?></span></div>
                                <?php if ($last_salary_increment) {
                                    $field(__('last_salary_increment', 'Last Salary Increment'), $money($last_salary_increment['approved_amount'] ?? $last_salary_increment['increment_amount'])
                                        . ' <span class="alt">' . $e($dt($last_salary_increment['effective_date'])) . '</span>');
                                } ?>
                            <?php else: ?>
                                <div class="f"><span class="v muted"><?= $e(__('salary_hidden_no_access', 'Salary details are hidden.')) ?></span></div>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>

                        <!-- Vacation balance -->
                        <?php if ($show('vacation_balance')): ?>
                        <div class="sec">
                            <h2><?= $e(__('vacation_balance_summary', 'Vacation Balance Summary')) ?></h2>
                            <?php if ($hasVacBalance): ?>
                            <div class="g3">
                                <?php
                                $field(__('total_vacation_days', 'Total Days'), $e(number_format($vac_total, 1)));
                                $field(__('used_days', 'Used Days'), $e(number_format($vac_used, 1)));
                                $field(__('available_balance', 'Available'), $e(number_format($vac_available, 2)));
                                ?>
                            </div>
                            <?php else: ?>
                            <div class="f"><span class="v muted"><?= $e(__('no_vacation_balance_record_found', 'No vacation balance record found.')) ?></span></div>
                            <?php endif; ?>
                            <?php if ($emprow["emp_sup_type"] <> "man_power"): ?>
                            <div class="g3">
                                <?php
                                $field(__('flys', 'Fly'), $e((int)$emprow['flystus']));
                                $field(__('encashed', 'Encashed'), $e((int)$emprow['encashstus']));
                                $field(__('local_vacation', 'Local Vacation'), $e($local_vacation_count));
                                ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>

                    </div>
                </div>

                <!-- Additional Information -->
                <?php if ($canViewAdditionalInfo && $canViewSalary): ?>
                <div class="sec">
                    <h2><?= $e(__('additional_information', 'Additional Information')) ?></h2>
                    <div class="g3">
                        <?php
                        $ai = $employee_additional_info ?? [];
                        $aiMoney = function ($key) use ($ai, $money, $val) {
                            return isset($ai[$key]) ? $money($ai[$key]) : $val(null);
                        };
                        $field(__('salary_grade', 'Salary Grade'), !empty($ai['salary_grade']) ? $e(__('grade', 'Grade') . ' ' . $ai['salary_grade']) : $val(null));
                        $field(__('dependants_count', 'No. of Dependants'), $val($ai['dependants_count'] ?? null));
                        $field(__('ticket_fare', 'Ticket'), $aiMoney('ticket_fare'));
                        if ((int)($emprow['country'] ?? 0) !== 191) {
                            $field(__('labour_office_expense', 'Labour Office Expenses'), $aiMoney('labour_office_expense'));
                            $field(__('iqama_renewal_fee', 'Iqama Renewal Fee'), $aiMoney('iqama_renewal_fee'));
                            $field(__('citizen_local_relation', 'Citizen (Local)'), $val($ai['citizen_local_relation'] ?? null));
                        }
                        $field(__('monthly_leave_accrual', 'Monthly Leave Accrual'), $monthly_leave_accrual_calculated !== null ? $e(number_format($monthly_leave_accrual_calculated, 2) . ' ' . __('day_s', 'Days')) : $val(null));
                        $field(__('eng_council_fee', 'Saudi Engineering Council Fee'), $aiMoney('eng_council_fee'));
                        $field(__('eng_council_expiry', 'Saudi Engineering Council Expiry'), is_valid_date_value($ai['eng_council_expiry'] ?? null) ? $e($dt($ai['eng_council_expiry'])) : $val(null));
                        ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Assigned Assets -->
                <?php if ($show('assets') && ($car_info || !empty($assigned_assets))): ?>
                <div class="sec">
                    <h2>
                        <?= $e(__('assigned_assets', 'Assigned Assets')) ?>
                        <?php if (count($assigned_assets) > $maxAssets): ?><span class="hint"><?= $e(sprintf(__('latest_n_of_m', 'Latest %d of %d'), $maxAssets, count($assigned_assets))) ?></span><?php endif; ?>
                    </h2>
                    <table class="t">
                        <thead>
                            <tr><th><?= $e(__('asset_type', 'Asset Type')) ?></th><th><?= $e(__('serial_number', 'Serial Number')) ?></th><th><?= $e(__('assigned_date', 'Assigned Date')) ?></th></tr>
                        </thead>
                        <tbody>
                            <?php if ($car_info): ?>
                            <tr>
                                <td><?= $e(__('car_details', 'Car')) ?>: <?= $e(getDisplayName($car_info['maker_name'])) ?> <?= $e(getDisplayName($car_info['model'])) ?> (<?= $e($car_info['made_year']) ?>)</td>
                                <td><bdi><?= $val($car_info['plate_no'] ?? null) ?></bdi></td>
                                <td><?= $e($dt($emprow['rcv_date'] ?? null)) ?></td>
                            </tr>
                            <?php endif; ?>
                            <?php foreach (array_slice($assigned_assets, 0, $maxAssets) as $asset): ?>
                            <tr>
                                <td><?= $e(getDisplayName($asset['asset_name'])) ?></td>
                                <td><bdi><?= $val($asset['serial_number']) ?></bdi></td>
                                <td><?= $e($dt($asset['assigned_date'] ?? null)) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>

                <!-- Loan History -->
                <?php if ($show('loans') && !empty($loan_history)): ?>
                <div class="sec">
                    <h2>
                        <?= $e(__('loan_history', 'Loan History')) ?>
                        <?php if (count($loan_history) > $maxLoans): ?><span class="hint"><?= $e(sprintf(__('latest_n_of_m', 'Latest %d of %d'), $maxLoans, count($loan_history))) ?></span><?php endif; ?>
                    </h2>
                    <table class="t">
                        <thead>
                            <tr>
                                <th class="num"><?= $e(__('loan_amount', 'Loan Amount')) ?></th>
                                <th class="num"><?= $e(__('monthly_deduction', 'Monthly Deduction')) ?></th>
                                <th class="num"><?= $e(__('remaining_balance', 'Remaining Balance')) ?></th>
                                <th><?= $e(__('start_date', 'Start Date')) ?></th>
                                <th><?= $e(__('end_date', 'End Date')) ?></th>
                                <th><?= $e(__('type', 'Type')) ?></th>
                                <th><?= $e(__('status', 'Status')) ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (array_slice($loan_history, 0, $maxLoans) as $loan):
                                $remaining_balance_hist = (float)$loan['total_payable'] - (float)$loan['total_paid'];
                                $loanTone = ($loan['status'] == 'approved' ? 'ok' : ($loan['status'] == 'paid' ? 'info' : ($loan['status'] == 'rejected' ? 'bad' : 'warn')));
                            ?>
                            <tr>
                                <td class="num"><?= $money($loan['loan_amount']) ?></td>
                                <td class="num"><?= $money($loan['monthly_deduction']) ?></td>
                                <td class="num <?= ($remaining_balance_hist > 0) ? 'neg' : 'pos' ?>"><?= $money($remaining_balance_hist) ?></td>
                                <td><?= $e(format_safe_date($loan['start_date'] ?? null, 'd M Y', '-')) ?></td>
                                <td><?= $e(format_safe_date($loan['end_date'] ?? null, 'd M Y', '-')) ?></td>
                                <td><?= $e($tr($loan['loan_type'])) ?></td>
                                <td><?= trim((string)$loan['status']) !== '' ? $badge($tr($loan['status']), $loanTone) : '-' ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>

                <!-- Vacation History -->
                <?php if ($show('vacations') && !empty($vacation_history)): ?>
                <div class="sec">
                    <h2>
                        <?= $e(__('vacation_history_header', 'Vacation History')) ?>
                        <?php if (count($vacation_history) > $maxVacations): ?><span class="hint"><?= $e(sprintf(__('latest_n_of_m', 'Latest %d of %d'), $maxVacations, count($vacation_history))) ?></span><?php endif; ?>
                    </h2>
                    <table class="t">
                        <thead>
                            <tr>
                                <th><?= $e(__('vacation_type', 'Vacation Type')) ?></th>
                                <th><?= $e(__('fly_type', 'Fly Type')) ?></th>
                                <th><?= $e(__('start_date', 'Start Date')) ?></th>
                                <th><?= $e(__('return_date', 'Return Date')) ?></th>
                                <th class="num"><?= $e(__('days', 'Days')) ?></th>
                                <th><?= $e(__('permit_no', 'Permit No')) ?></th>
                                <th><?= $e(__('approval_status', 'Approval Status')) ?></th>
                                <th><?= $e(__('arrived', 'Arrived')) ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (array_slice($vacation_history, 0, $maxVacations) as $vac):
                                $vac_type = $vac['vac_type'] ?? $vac['note'];
                                $fly_type = $vac['fly_type'] ?? '';
                                $current_status = $vac['current_status'] ?: 'pending';
                                $vacTone = in_array($current_status, ['approved', 'completed'], true) ? 'ok' : (in_array($current_status, ['rejected', 'cancelled'], true) ? 'bad' : 'warn');
                            ?>
                            <tr>
                                <td><?= $val($tr($vac_type)) ?></td>
                                <td><?= ($fly_type && $fly_type != 'N/A') ? $e($tr($fly_type)) : '-' ?></td>
                                <td><?= $e(format_safe_date($vac['start_date'] ?? null, 'd M Y', '-')) ?></td>
                                <td><?= $e(format_safe_date($vac['return_date'] ?? null, 'd M Y', '-')) ?></td>
                                <td class="num"><?= $e($vac['vacdays'] ?? 0) ?></td>
                                <td><?= $e($vac['permit_no'] ?: '-') ?></td>
                                <td><?= $badge($tr($current_status), $vacTone) ?></td>
                                <td><?= ($vac['arrived_date'] == "") ? '<span class="muted">' . $e(__('not_yet', 'Not Yet')) . '</span>' : $e(format_safe_date($vac['arrived_date'], 'd M Y', '-')) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>

                <!-- End of Service -->
                <?php if ($show('eos') && $end_of_service): ?>
                <div class="sec">
                    <h2><?= $e(__('end_of_service_header', 'End of Service')) ?></h2>
                    <div class="g3">
                        <?php
                        $field(__('end_date', 'End Date'), $e($dt($end_of_service['end_date'] ?? null)));
                        $field(__('working_period', 'Working Period'), $e(sprintf('%d %s %d %s %d %s', $end_of_service['t_years'], __('years', 'Years'), $end_of_service['t_months'], __('months', 'Months'), $end_of_service['t_days'], __('days', 'Days'))));
                        $field(__('reason_label', 'Reason'), $val($pick($end_of_service['leaving_reason'], $end_of_service['leaving_reason_ar'])));
                        if ($canViewEosValue) {
                            $field(__('eos_amount_label', 'EOS Amount'), $money($end_of_service['eos_amount'] ?? 0));
                            $field(__('net_payment', 'Net Payment'), $money($end_of_service['net_payment'] ?? 0));
                        }
                        ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Notes -->
                <?php if ($show('notes') && !empty($employee_notes)): ?>
                <div class="sec">
                    <h2>
                        <?= $e(__('notes_notices_header', 'Notes & Notices')) ?>
                        <?php if (count($employee_notes) > $maxNotes): ?><span class="hint"><?= $e(sprintf(__('latest_n_of_m', 'Latest %d of %d'), $maxNotes, count($employee_notes))) ?></span><?php endif; ?>
                    </h2>
                    <table class="t">
                        <tbody>
                            <?php foreach (array_slice($employee_notes, 0, $maxNotes) as $note):
                                $noteText = trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags((string)$note['note']), ENT_QUOTES, 'UTF-8')));
                            ?>
                            <tr>
                                <td style="width: 7em; white-space: nowrap;"><?= $e(format_safe_date($note['created_at'] ?? null, 'd M Y', '-')) ?></td>
                                <td dir="auto"><?= $e(mb_strimwidth($noteText, 0, 220, '…', 'UTF-8')) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>

                <div class="foot">
                    <span><?= $e($site_title ?? '') ?> &middot; <?= $e(__('confidential_hr_record', 'Confidential HR record')) ?></span>
                    <span><?= $e(__('printed_by', 'Printed by')) ?> <?= $e(getDisplayName($fname ?? '')) ?> &middot; <bdi><?= date('d M Y H:i') ?></bdi></span>
                </div>

            </div>
        </div>

        <script>
            // Keep the profile on ONE A4 page: the sheet is sized in em, so stepping its font-size down
            // shrinks everything together until the real laid-out height fits the printable area.
            (function () {
                var sheet = document.getElementById('sheet');
                var pageH = document.getElementById('pageProbe').offsetHeight * 0.985; // small safety margin against rounding
                var baseSize = parseFloat(getComputedStyle(sheet).fontSize);

                function fitToPage() {
                    var size = baseSize;
                    sheet.style.fontSize = '';
                    while (sheet.offsetHeight > pageH && size > baseSize * 0.5) {
                        size -= 0.2;
                        sheet.style.fontSize = size + 'px';
                    }
                }

                window.addEventListener('beforeprint', fitToPage);
                window.addEventListener('load', function () {
                    var ready = (document.fonts && document.fonts.ready) ? document.fonts.ready : Promise.resolve();
                    ready.then(function () {
                        fitToPage();
                        window.print();
                    });
                });
            })();
        </script>

    </body>
</html>
