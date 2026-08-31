<?php
require_once("./includes/init.php");
require_once("./includes/session_check.php");
require_once("./includes/special_access_helper.php");
include('./includes/MainClass.php');
include("./includes/avatar_select.php");
include("./includes/Hijri_GregorianConvert.php");
$DateConv = new Hijri_GregorianConvert;
$format = "YYYY-MM-DD";

require("./includes/emp_query.php");
$emprow = mysqli_fetch_assoc($get_emp_data);

if (!$emprow) {
    die("Employee data not found.");
}

// Insurance No / Expiry / Class are yearly-renewed - fetch the current active record from
// employee_medical_insurance.
$current_medical_insurance = null;
$mi_stmt = mysqli_prepare($conDB, "SELECT insurance_no, medical_expiry, medical_class FROM `employee_medical_insurance` WHERE `emp_id` = ? AND `status` = 'active' LIMIT 1");
mysqli_stmt_bind_param($mi_stmt, "s", $emprow['empid']);
mysqli_stmt_execute($mi_stmt);
$current_medical_insurance = mysqli_fetch_assoc(mysqli_stmt_get_result($mi_stmt)) ?: null;
mysqli_stmt_close($mi_stmt);
$current_medical_class = $current_medical_insurance['medical_class'] ?? null;

// Safe display helper: returns translated not_available when empty/null
if (!function_exists('display_or_na')) {
    function display_or_na($val)
    {
        if (is_null($val) || $val === '' || $val === false) {
            return __('not_available');
        }
        return htmlspecialchars((string)$val);
    }
}

// Compute age in years from DOB for header and personal info
$years = '';
if (!empty($emprow['dob']) && $emprow['dob'] !== '0000-00-00') {
    try {
        $dobDate = new DateTime($emprow['dob']);
        $today = new DateTime();
        $years = $dobDate->diff($today)->y;
    } catch (Exception $e) {
        $years = '';
    }
}

// Get available vacation balance directly from emp_vacation_balance table
// (Updated daily by cron job - no need for live calculation)
$displayBalance = 0;
$empid_for_calc = $emprow['empid'] ?? $emprow['emp_id'];
if ($emprow['status'] == 1 && !empty($empid_for_calc)) {
    $balance_query = mysqli_query($conDB, "SELECT `available_balance` FROM `emp_vacation_balance` WHERE `emp_id` = '" . mysqli_real_escape_string($conDB, $empid_for_calc) . "' ORDER BY `last_updated` DESC LIMIT 1");
    if ($balance_query && mysqli_num_rows($balance_query) > 0) {
        $balance_row = mysqli_fetch_assoc($balance_query);
        $displayBalance = (float)$balance_row['available_balance'];
        mysqli_free_result($balance_query);
    }
}

$lastVac = lastVacIdGet($emprow['empid']);
$allActiveVacations = getAllActiveVacations($emprow['empid']);
$activeVacCount = count($allActiveVacations);
$activeFlyVacations = array_values(array_filter($allActiveVacations, function ($vac) {
    $vacType = strtolower(trim((string)($vac['vac_type'] ?? '')));
    $flyType = strtolower(trim((string)($vac['fly_type'] ?? '')));
    return $vacType === 'fly' && in_array($flyType, ['annual', 'emergency'], true);
}));
$activeFlyVacCount = count($activeFlyVacations);

// Get latest selected resignation reason code for preselecting Apply Resignation flow
$selectedResignationReason = '';
$selectedResignationReasonText = (string)($emprow['leaving_reason'] ?? '');
if (!empty($emprow['empid'])) {
    $reasonQuery = mysqli_query(
        $conDB,
        "SELECT `rejection_reason` FROM `emp_resignations` WHERE `emp_id` = '" . mysqli_real_escape_string($conDB, $emprow['empid']) . "' AND `rejection_reason` <> '' ORDER BY `id` DESC LIMIT 1"
    );
    if ($reasonQuery && mysqli_num_rows($reasonQuery) > 0) {
        $reasonRow = mysqli_fetch_assoc($reasonQuery);
        $selectedResignationReason = (string)($reasonRow['rejection_reason'] ?? '');
        mysqli_free_result($reasonQuery);
    }
}

// Effective request-block status for this employee (global block XOR employee override),
// used to hide "More Actions" menu items for request types this employee cannot submit.
$empIdForBlockCheck = $emprow['empid'] ?? $emprow['emp_id'] ?? '';
$isLoanBlocked = is_employee_request_blocked($conDB, $empIdForBlockCheck, 'loan_request')['blocked'];
$isExcuseLeaveBlocked = is_employee_request_blocked($conDB, $empIdForBlockCheck, 'excuse_leave')['blocked'];
$isResignationBlocked = is_employee_request_blocked($conDB, $empIdForBlockCheck, 'resignation_request')['blocked'];
$isRejoinBlocked = is_employee_request_blocked($conDB, $empIdForBlockCheck, 'rejoin_request')['blocked'];
$isVacationAnnualBlocked = is_employee_request_blocked($conDB, $empIdForBlockCheck, 'vacation_annual')['blocked'];
$isVacationEmergencyBlocked = is_employee_request_blocked($conDB, $empIdForBlockCheck, 'vacation_emergency')['blocked'];
$isVacationLocalBlocked = is_employee_request_blocked($conDB, $empIdForBlockCheck, 'vacation_local')['blocked'];
$isVacationEncashedBlocked = is_employee_request_blocked($conDB, $empIdForBlockCheck, 'vacation_encashed')['blocked'];
$isAllVacationBlocked = ($isVacationAnnualBlocked && $isVacationEmergencyBlocked && $isVacationLocalBlocked && $isVacationEncashedBlocked);
$isBusinessTripBlocked = is_employee_request_blocked($conDB, $empIdForBlockCheck, 'business_trip')['blocked'];

// Build More Actions HTML for SweetAlert2
$moreActionsHtml = '';
if ($emprow['status'] == 1) {
    $moreActionsHtml .= "<a href=\"javascript:void(0);\" class=\"menu-item text-danger\" onclick=\"window.location.href='./system_guide.php'\"><i class=\"fa fa-book-open-lines\"></i><span>" . __('system_guide') . "</span></a>";
    $moreActionsHtml .= '<hr style="margin: 0; border-color: var(--light);">';
    // Sequence below mirrors includes/emp_top_info.php's More Actions order
    // (Loan -> Vacation -> Excuse Leave -> Rejoin -> Edit -> Resignation), limited
    // to the items that apply in this self-service context.
    if (!$isLoanBlocked && empty($emprow['has_active_regular_loan'])) {
        $moreActionsHtml .= "<a href=\"javascript:void(0);\" class=\"menu-item apply-loan applyLoan text-warning\" data-emp_id=\"{$emprow['empid']}\" data-user_type=\"" . htmlspecialchars($_SESSION['user_type'] ?? '') . "\"><i class=\"fa fa-money-bill-wave\"></i><span>" . __('apply_loan') . "</span></a>";
    }
    $allowEmergencyVacation = ((string)($emprow['allow_emergency_vacation'] ?? '0') === '1') ? 1 : 0;
    if (!$isAllVacationBlocked) {
        $moreActionsHtml .= "<a href=\"javascript:void(0);\" class=\"menu-item annual-vac applyvacationAtter text-info\" data-empid=\"{$emprow['empid']}\" data-dept=\"{$emprow['dept']}\" data-country=\"{$emprow['country']}\" data-balance=\"{$displayBalance}\" data-allow-emergency=\"{$allowEmergencyVacation}\" data-block-annual=\"" . ($isVacationAnnualBlocked ? 1 : 0) . "\" data-block-emergency=\"" . ($isVacationEmergencyBlocked ? 1 : 0) . "\" data-block-local=\"" . ($isVacationLocalBlocked ? 1 : 0) . "\" data-block-encashed=\"" . ($isVacationEncashedBlocked ? 1 : 0) . "\"><i class=\"fa fa-plane\"></i><span>" . __('apply_annual_vacation') . "</span></a>";
    }
    if (!$isBusinessTripBlocked) {
        $moreActionsHtml .= "<a href=\"javascript:void(0);\" class=\"menu-item text-warning\" onclick=\"openBusinessTripApplyModal('{$emprow['empid']}', '{$emprow['dept']}', '{$emprow['country']}')\"><i class=\"fa fa-plane\"></i><span>" . __('apply_business_trip', 'Apply Business Trip') . "</span></a>";
    }
    if (!$isExcuseLeaveBlocked) {
        $moreActionsHtml .= "<a href=\"javascript:void(0);\" class=\"menu-item apply-leave applyLeaveRequest text-success\" data-empid=\"{$emprow['empid']}\"><i class=\"fa fa-solid fa-house-person-leave\"></i><span>" . __('excuse_leave') . "</span></a>";
    }
    if ($activeFlyVacCount > 0 && !$isRejoinBlocked) {
        $badgeText = $activeFlyVacCount > 1 ? " ({$activeFlyVacCount})" : '';
        $moreActionsHtml .= "<a href=\"javascript:void(0);\" class=\"menu-item rejoin submitMultipleRejoinRequest text-warning\" data-emp-id=\"{$emprow['empid']}\" data-emp-name=\"{$emprow['name']}\" data-total-vacations=\"{$activeFlyVacCount}\"><i class=\"fa fa-plane-arrival\"></i><span>" . __('rejoin_request') . "{$badgeText}</span></a>";
    }
    $moreActionsHtml .= "<a href=\"javascript:void(0);\" class=\"menu-item edit text-primary\" id=\"startUpdateRequest\" data-avatar=\"" . display_or_na($emprow['avatar'] ?? null) . "\" data-empid=\"" . display_or_na($emprow['empid'] ?? null) . "\" data-mobile=\"" . display_or_na($emprow['mobile'] ?? null) . "\" data-email=\"" . display_or_na($emprow['email'] ?? null) . "\" data-address=\"" . display_or_na($emprow['address'] ?? null) . "\" data-passport_number=\"" . display_or_na($emprow['passport_number'] ?? null) . "\" data-passport_exp=\"" . display_or_na($emprow['passport_exp'] ?? null) . "\"><i class=\"fa fa-edit\"></i><span>" . __('update_information') . "</span></a>";
    if (!$isResignationBlocked) {
        $moreActionsHtml .= "<a href=\"javascript:void(0);\" class=\"menu-item apply-resignation applyResignation text-danger\" data-emp_id=\"{$emprow['empid']}\" data-emp_name=\"{$emprow['name']}\" data-selected_reason=\"" . htmlspecialchars($selectedResignationReason, ENT_QUOTES, 'UTF-8') . "\" data-selected_reason_text=\"" . htmlspecialchars($selectedResignationReasonText, ENT_QUOTES, 'UTF-8') . "\"><i class=\"fa fa-solid fa-portal-exit\"></i><span>" . __('apply_resignation') . "</span></a>";
    }
} else {
    $moreActionsHtml .= '<div style="padding:24px; text-align:center; color: var(--secondary);"><p>' . __('employee_is_inactive') . '</p></div>';
}
// Add HR and Sign Out button
$moreActionsHtml .= '<hr style="margin: 0; border-color: var(--light);">';
$moreActionsHtml .= "<a href=\"javascript:void(0);\" class=\"menu-item signout text-secondary\" data-action=\"signout\"><i class=\"fa fa-sign-out\"></i><span>" . __('logout_button') . "</span></a>";

// Build "My Pages" menu (same SweetAlert2 menu pattern as More Actions) - lists only the
// pages this specific employee was granted via app_settings -> Special Access
// (get_special_access_page_labels() in special_access_helper.php). Plain employees have no
// main menu, so this is their only route to a page an admin unlocked for them individually.
// NOTE: payroll_status_history.php, loan_report_details.php, settlement_status_history.php,
// vacation_status_history.php and business_trip_status_history.php are intentionally NOT listed
// here even though they're grantable in Special Access - they're record-detail pages that always
// need a specific request_inv_no chosen from elsewhere and have no sensible standalone landing
// state, so a bare link to them would only ever show a "not provided" error.
$grantedPageLinks = [
    'access_all_applied_vac' => ['file' => 'all_applied_vac.php', 'icon' => 'fa-plane', 'label' => __('all_applied_vacations', 'All Applied Vacations')],
    'access_all_applied_loan' => ['file' => 'all_applied_loan.php', 'icon' => 'fa-money-bill-wave', 'label' => __('all_applied_loans', 'All Applied Loans')],
    'access_all_applied_business_trip' => ['file' => 'all_applied_business_trip.php', 'icon' => 'fa-suitcase', 'label' => __('all_applied_business_trips', 'All Applied Business Trips')],
    'access_all_resignations' => ['file' => 'all_resignations.php', 'icon' => 'fa-solid fa-portal-exit', 'label' => __('all_resignations', 'All Resignations')],
    'access_all_settlements' => ['file' => 'all_settlements.php', 'icon' => 'fa-file-invoice-dollar', 'label' => __('all_settlements', 'All Settlements')],
    'access_all_payroll_approvals' => ['file' => 'all_payroll_approvals.php', 'icon' => 'fa-tasks', 'label' => __('all_payroll_approvals', 'All Payroll Approvals')],
];

// payroll_checklist_report.php requires ?month=YYYY-MM or it dies with "Valid payroll month
// not provided". The current calendar month usually has no payroll_approval_requests row yet
// (payroll for month N is only requested near/after month-end), so defaulting to date('Y-m')
// silently lands on an empty request - link to the latest month that actually has a request.
$latestPayrollMonthStmt = mysqli_query($conDB, "SELECT payroll_month FROM payroll_approval_requests ORDER BY id DESC LIMIT 1");
$latestPayrollMonthRow = $latestPayrollMonthStmt ? mysqli_fetch_assoc($latestPayrollMonthStmt) : null;
$defaultPayrollChecklistMonth = ($latestPayrollMonthRow['payroll_month'] ?? '') ?: date('Y-m');
$grantedPageLinks['access_payroll_checklist_report'] = [
    'file' => 'payroll_checklist_report.php?month=' . urlencode($defaultPayrollChecklistMonth),
    'icon' => 'fa-list-check',
    'label' => __('payroll_checklist_report', 'Payroll Checklist Report'),
];

$grantedPagesHtml = '';
$empIdForPageAccess = $emprow['empid'] ?? $emprow['emp_id'] ?? '';
foreach ($grantedPageLinks as $accessKey => $pageInfo) {
    if (user_has_special_access($conDB, $empIdForPageAccess, $accessKey, $user_role ?? '', $user_type ?? '', $is_system_admin ?? false)) {
        $grantedPagesHtml .= "<a href=\"./{$pageInfo['file']}\" class=\"menu-item text-primary\"><i class=\"fa {$pageInfo['icon']}\"></i><span>" . htmlspecialchars($pageInfo['label']) . "</span></a>";
    }
}
$hasGrantedPages = ($grantedPagesHtml !== '');
?>
<!doctype html>
<html lang="<?= $current_lang ?? 'en' ?>" <?= ($is_rtl ?? false) ? 'dir="rtl"' : '' ?>>

<head>
    <meta charset="utf-8" />
    <title><?= $site_title ?> - Employee Profile</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />

    <!-- Favicon -->
    <link rel="shortcut icon" href="<?= get_setting($conDB, 'favicon') ?>">

    <!-- App CSS -->
    <link href="assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/metismenu.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/style.css" rel="stylesheet" type="text/css" />
    <!-- Latest compiled and minified CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.10.0/css/bootstrap-datepicker.min.css" rel="stylesheet" />

    <!-- Plugins CSS -->

    <!-- Additional Plugins -->
    <link rel="stylesheet" href="./plugins/croppie/croppie.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" />
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Dropzone CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.9.3/dropzone.min.css" />

    <style>
        :root {
            --primary: #007bff;
            --secondary: #6c757d;
            --success: #28a745;
            --danger: #dc3545;
            --warning: #ffc107;
            --info: #17a2b8;
            --light: #f8f9fa;
            --dark: #343a40;
            --white: #ffffff;
            --shadow-sm: 0 1px 3px rgba(0, 0, 0, 0.12);
            --shadow-md: 0 2px 8px rgba(0, 0, 0, 0.15);
            --shadow-lg: 0 4px 16px rgba(0, 0, 0, 0.2);
        }

        * {
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        /* ===== HEADER SECTION ===== */
        .profile-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--info) 100%);
            border-radius: 16px;
            padding: 40px;
            color: white;
            margin-bottom: 40px;
            box-shadow: var(--shadow-lg);
            overflow: hidden;
            position: relative;
            max-width: 1400px;
            margin-left: auto;
            margin-right: auto;
        }

        /* Inactive employee header color */
        .profile-header.inactive {
            background: linear-gradient(135deg, var(--danger) 0%, #b02a37 100%);
        }

        .profile-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -10%;
            width: 500px;
            height: 500px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
        }

        .profile-header .container-custom {
            display: grid;
            grid-template-columns: auto 1fr auto auto auto;
            gap: 40px;
            align-items: center;
            position: relative;
            z-index: 1;
        }

        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            border: 5px solid rgba(255, 255, 255, 0.3);
            object-fit: cover;
            background: rgba(255, 255, 255, 0.1);
        }

        .profile-header-info h1 {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .profile-header-info p {
            font-size: 14px;
            opacity: 0.9;
            margin: 4px 0;
        }

        .profile-quick-stats {
            display: flex;
            gap: 30px;
            justify-content: flex-end;
        }

        .stat-item {
            text-align: center;
        }

        .stat-number {
            font-size: 24px;
            font-weight: 700;
        }

        .stat-label {
            font-size: 12px;
            opacity: 0.9;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .qr-code {
            width: 130px;
            height: 130px;
        }

        /* ===== EMPLOYEE TENURE CARD ===== */
        .employee-tenure-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 16px;
            padding: 28px 36px;
            margin: 0 auto 24px;
            max-width: 1400px;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.25);
            position: relative;
            overflow: hidden;
            border: none;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 24px;
        }

        .employee-tenure-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 15px 40px rgba(102, 126, 234, 0.35);
        }

        .employee-tenure-card::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -10%;
            width: 300px;
            height: 300px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            pointer-events: none;
        }

        .employee-tenure-card::after {
            content: '';
            position: absolute;
            bottom: -30%;
            left: -5%;
            width: 200px;
            height: 200px;
            background: rgba(255, 255, 255, 0.08);
            border-radius: 50%;
            pointer-events: none;
        }

        .tenure-content {
            display: flex;
            align-items: center;
            gap: 24px;
            position: relative;
            z-index: 2;
        }

        .tenure-icon-wrapper {
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            width: 80px;
            height: 80px;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        .employee-tenure-card:hover .tenure-icon-wrapper {
            transform: scale(1.1) rotate(5deg);
        }

        .tenure-icon-wrapper i {
            font-size: 38px;
            color: #ffffff;
            text-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
        }

        .tenure-text-wrapper {
            flex: 1;
        }

        .tenure-title {
            font-size: 15px;
            font-weight: 600;
            color: rgba(255, 255, 255, 0.9);
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .tenure-title i {
            font-size: 14px;
        }

        .tenure-message {
            font-size: 26px;
            font-weight: 700;
            color: #ffffff;
            line-height: 1.4;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.15);
            margin: 0;
        }

        .tenure-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(255, 255, 255, 0.25);
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
            color: #ffffff;
            margin-top: 12px;
            backdrop-filter: blur(10px);
        }

        .tenure-badge i {
            font-size: 16px;
        }

        .tenure-close-btn {
            position: absolute;
            top: 20px;
            background: rgba(255, 255, 255, 0.2);
            border: none;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            z-index: 3;
            backdrop-filter: blur(10px);
            direction: ltr !important;
            right: 20px !important;
            left: auto !important;
        }

        [dir="rtl"] .tenure-close-btn {
            right: 20px !important;
            left: auto !important;
        }

        .tenure-close-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: rotate(90deg);
        }

        .tenure-close-btn i {
            color: #ffffff;
            font-size: 18px;
        }

        @media (max-width: 768px) {
            .employee-tenure-card {
                padding: 24px 20px;
                flex-direction: column;
                align-items: flex-start;
            }

            .tenure-content {
                flex-direction: column;
                text-align: center;
            }

            .tenure-message {
                font-size: 20px;
            }
        }

        [dir="rtl"] .employee-tenure-card {
            direction: rtl;
        }

        /* ===== MAIN CONTENT ===== */
        .profile-container {
            max-width: 1400px;
            margin: 0 auto;
        }

        /* ===== CARDS GRID ===== */
        .cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 24px;
            margin-bottom: 40px;
            max-width: 1400px;
            margin-left: auto;
            margin-right: auto;
        }

        /* ===== INFO CARD ===== */
        .info-card {
            background: var(--white);
            border-radius: 12px;
            padding: 24px;
            box-shadow: var(--shadow-md);
            transition: all 0.3s ease;
        }

        .info-card:hover {
            box-shadow: var(--shadow-lg);
            transform: translateY(-4px);
        }

        .info-card-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 20px;
            padding-bottom: 16px;
            border-bottom: 2px solid var(--light);
        }

        .info-card-icon {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            color: white;
        }

        .info-card-title {
            font-size: 16px;
            font-weight: 600;
            color: var(--dark);
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid var(--light);
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            font-size: 13px;
            color: var(--secondary);
            font-weight: 500;
        }

        .info-value {
            font-size: 14px;
            color: var(--dark);
            font-weight: 500;
            text-align: right;
        }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 768px) {
            .profile-header {
                padding: 24px;
            }

            .profile-header .container-custom {
                grid-template-columns: auto 1fr auto;
                gap: 20px;
            }

            .profile-quick-stats {
                grid-column: 1 / -1;
                justify-content: space-around;
                gap: 15px;
            }

            .header-buttons {
                grid-column: 1 / -1;
                grid-template-columns: 1fr;
                gap: 10px;
            }

            .buttons-left {
                flex-direction: column;
                gap: 10px;
            }

            .more-actions-btn {
                width: 100%;
                justify-content: center;
            }

            .qr-code {
                width: 120px;
                height: 120px;
            }

            .profile-avatar {
                width: 80px;
                height: 80px;
            }

            .profile-header-info h1 {
                font-size: 22px;
            }

            .cards-grid {
                grid-template-columns: 1fr;
                gap: 16px;
            }
        }

        @media (max-width: 480px) {
            body {
                padding: 10px;
            }

            .profile-header {
                padding: 16px;
                margin-bottom: 20px;
            }

            .profile-header .container-custom {
                grid-template-columns: auto;
                gap: 16px;
            }

            .profile-quick-stats {
                flex-direction: column;
                gap: 12px;
            }

            .qr-code {
                width: 120px;
                height: 120px;
                justify-self: center;
            }

            .cards-grid {
                grid-template-columns: 1fr;
                gap: 12px;
            }

            .info-card {
                padding: 16px;
            }

            .info-row {
                flex-direction: column;
                align-items: flex-start;
                gap: 4px;
            }

            .info-value {
                text-align: left;
            }
        }

        /* ===== UTILITY ===== */
        .section-title {
            font-size: 20px;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 12px;
            max-width: 1400px;
            margin-left: auto;
            margin-right: auto;
        }

        .section-title i {
            font-size: 24px;
            color: var(--primary);
        }

        .copy-btn {
            cursor: pointer;
            opacity: 0.6;
            transition: opacity 0.2s;
        }

        .copy-btn:hover {
            opacity: 1;
        }

        /* ===== MORE ACTIONS MODAL ===== */
        .more-actions-wrapper {
            position: relative;
            display: inline-block;
        }

        .more-actions-btn {
            background: rgba(255, 255, 255, 0.2);
            border: 2px solid rgba(255, 255, 255, 0.3);
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .more-actions-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            border-color: rgba(255, 255, 255, 0.5);
        }

        /* Modal Overlay */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 999;
            animation: fadeIn 0.3s ease-out;
        }

        .modal-overlay.active {
            display: block;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        .modal-header {
            padding: 24px;
            border-bottom: 2px solid var(--light);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-title {
            font-size: 20px;
            font-weight: 700;
            color: var(--dark);
        }

        .modal-close-btn {
            background: none;
            border: none;
            font-size: 24px;
            color: var(--secondary);
            cursor: pointer;
            padding: 0;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: all 0.2s ease;
        }

        .modal-close-btn:hover {
            background: var(--light);
            color: var(--dark);
        }

        .modal-content {
            padding: 0;
        }

        .menu-item {
            padding: 16px 24px;
            display: flex;
            align-items: center;
            gap: 16px;
            color: var(--dark);
            text-decoration: none;
            font-size: 15px;
            cursor: pointer;
            border-bottom: 1px solid var(--light);
            transition: background-color 0.2s ease;
        }

        .menu-item:last-child {
            border-bottom: none;
        }

        .menu-item:hover {
            background-color: var(--light);
        }

        .menu-item i {
            font-size: 20px;
            width: 24px;
            text-align: center;
        }

        .menu-item.add-documents i {
            color: var(--primary);
        }

        .menu-item.assign-asset i {
            color: var(--secondary);
        }

        .menu-item.apply-loan i {
            color: var(--warning);
        }

        .menu-item.annual-vac i {
            color: var(--info);
        }

        .menu-item.apply-leave i {
            color: var(--success);
        }

        .menu-item.edit i {
            color: var(--primary);
        }

        .menu-item.note i {
            color: #ffc107;
        }

        .menu-item.end-service i {
            color: var(--danger);
        }

        /* RTL Support */
        [dir="rtl"] .profile-header .container-custom {
            grid-template-columns: auto auto 1fr auto;
        }

        [dir="rtl"] .profile-actions-row {
            flex-direction: row-reverse;
            padding-left: 40px !important;
            padding-right: 40px !important;
        }

        [dir="rtl"] .profile-quick-stats {
            justify-content: flex-start;
        }

        [dir="rtl"] .info-row {
            flex-direction: row-reverse;
        }

        [dir="rtl"] .info-label,
        [dir="rtl"] .info-value {
            text-align: left;
        }

        /* Select2 alignment with Bootstrap form controls */
        .select2-container {
            width: 100% !important;
        }

        .select2-container .select2-selection--single {
            height: 38px;
            border: 1px solid #ced4da;
            border-radius: 4px;
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 36px;
            padding-left: 12px;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 36px;
        }

        /* Ensure dropdowns appear above SweetAlert2 */
        .select2-container--open {
            z-index: 99999 !important;
        }

        .select2-dropdown {
            z-index: 99999 !important;
            border: 1px solid #ced4da;
            border-radius: 4px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
        }

        .select2-results__option {
            padding: 8px 12px;
            font-size: 14px;
        }

        .select2-results__option--highlighted {
            background-color: #4e73df !important;
            color: white !important;
        }

        .select2-results__option[aria-selected="true"] {
            background-color: #e9ecef;
        }

        .select2-search--dropdown {
            padding: 8px;
        }

        .select2-search__field {
            border: 1px solid #ced4da;
            border-radius: 4px;
            padding: 6px 12px;
            width: 100% !important;
        }

        .datepicker-dropdown {
            z-index: 99999 !important;
        }

        .daterangepicker {
            z-index: 99999 !important;
        }

        .datepicker table tr td.disabled,
        .datepicker table tr td.disabled:hover {
            color: #ff0000;
            background: #ffeeee;
        }

        /* ===== MORE ACTIONS MODAL - PROFESSIONAL ACTION-SHEET DESIGN ===== */
        .more-actions-modal .swal2-popup {
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(15, 23, 42, 0.18);
            overflow: hidden;
            background: #f7f8fa;
        }

        .more-actions-modal .swal2-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: #1f2937;
            padding: 1.25rem 1.5rem 1rem;
            margin: 0;
            text-align: left;
            background: #fff;
            border-bottom: 1px solid #eef0f4;
        }

        .more-actions-modal .swal2-html-container {
            margin: 0 !important;
            padding: 0 !important;
            overflow: visible;
        }

        /* Menu Items Container */
        .more-actions-modal .menu-items-container {
            display: flex;
            flex-direction: column;
            gap: 6px;
            margin: 0;
            padding: 10px 10px 14px;
            width: 100%;
            background: #f7f8fa;
            max-height: 60vh;
            overflow-y: auto;
        }

        .more-actions-modal .menu-item {
            display: flex !important;
            align-items: center;
            gap: 12px;
            padding: 10px 12px !important;
            margin: 0 !important;
            cursor: pointer !important;
            transition: all 0.2s ease;
            border: 1px solid transparent;
            border-radius: 12px;
            font-weight: 600;
            font-size: 14.5px;
            user-select: none;
            box-sizing: border-box;
            background-color: #fff;
            position: relative;
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
        }

        .more-actions-modal .menu-item:hover {
            transform: translateX(2px);
            box-shadow: 0 4px 12px rgba(15, 23, 42, 0.08);
            border-color: currentColor;
        }

        .more-actions-modal .menu-item:active {
            transform: translateX(2px) scale(0.99);
        }

        .more-actions-modal .menu-item i {
            font-size: 15px;
            width: 34px;
            height: 34px;
            border-radius: 10px;
            text-align: center;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: rgba(108, 117, 125, 0.14);
        }

        .more-actions-modal .menu-item span {
            font-size: 14.5px;
            white-space: nowrap;
            flex: 1;
            overflow: hidden;
            text-overflow: ellipsis;
            text-align: start;
            color: #374151;
        }

        .more-actions-modal .menu-item span.menu-item-count {
            flex: 0 0 auto;
            width: auto;
            font-size: 11px;
            font-weight: 700;
            padding: 2px 9px;
            border-radius: 12px;
            background: rgba(0, 0, 0, 0.06);
            color: inherit;
            text-align: center;
        }

        .more-actions-modal .menu-item::after {
            content: '\f105';
            font-family: 'Font Awesome 5 Free', 'FontAwesome';
            font-weight: 900;
            font-size: 13px;
            color: #c7cbd1;
            flex-shrink: 0;
            transition: transform 0.2s ease, color 0.2s ease;
        }

        .more-actions-modal .menu-item:hover::after {
            transform: translateX(3px);
            color: currentColor;
        }

        /* Color Schemes - icon badge tinted to its own action color, label stays neutral */
        .more-actions-modal .menu-item.text-primary { color: var(--primary) !important; }
        .more-actions-modal .menu-item.text-primary i { background-color: rgba(91, 115, 232, 0.14); }

        .more-actions-modal .menu-item.text-warning { color: var(--warning) !important; }
        .more-actions-modal .menu-item.text-warning i { background-color: rgba(241, 180, 76, 0.16); }

        .more-actions-modal .menu-item.text-info { color: var(--info) !important; }
        .more-actions-modal .menu-item.text-info i { background-color: rgba(80, 165, 241, 0.14); }

        .more-actions-modal .menu-item.text-danger { color: var(--danger) !important; }
        .more-actions-modal .menu-item.text-danger i { background-color: rgba(244, 106, 106, 0.14); }

        .more-actions-modal .menu-item.text-success { color: var(--success) !important; }
        .more-actions-modal .menu-item.text-success i { background-color: rgba(40, 167, 69, 0.14); }

        .more-actions-modal .menu-item.text-dark { color: var(--dark) !important; }
        .more-actions-modal .menu-item.text-dark i { background-color: rgba(52, 58, 64, 0.14); }

        .more-actions-modal .menu-item.text-secondary { color: var(--secondary) !important; }
        .more-actions-modal .menu-item.text-secondary i { background-color: rgba(108, 117, 125, 0.14); }

        .more-actions-modal .menu-item.text-purple { color: #6f42c1 !important; }
        .more-actions-modal .menu-item.text-purple i { background-color: rgba(111, 66, 193, 0.14); }

        /* Close Button */
        .more-actions-modal .swal2-close {
            font-size: 1.5rem;
            color: #9aa1ac;
            width: 36px;
            height: 36px;
        }

        .more-actions-modal .swal2-close:hover {
            color: #f46a6a;
        }

        /* ===== RESIGNATION WIZARD STYLES ===== */
        .resignation-wizard {
            z-index: 9999 !important;
        }

        .resignation-popup {
            border-radius: 12px !important;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3) !important;
        }

        .exit-interview-wizard {
            z-index: 9999 !important;
        }

        .exit-interview-popup {
            border-radius: 12px !important;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3) !important;
        }

        .resignation-step1 {
            text-align: left;
        }

        .resignation-step1 .form-group {
            margin-bottom: 20px;
        }

        .resignation-step1 .form-label {
            font-weight: 500;
            color: #34495e;
            margin-bottom: 8px;
            display: block;
        }

        .resignation-step1 .form-control {
            padding: 12px;
            border: 1px solid #bdc3c7;
            border-radius: 5px;
            font-size: 14px;
        }

        .resignation-step1 .form-text {
            margin-top: 5px;
            color: #7f8c8d;
        }

        .exit-interview-step2 {
            text-align: left;
            max-height: 500px;
            overflow-y: auto;
            padding: 20px;
        }

        .exit-interview-step2 .form-group {
            margin-bottom: 20px;
        }

        .exit-interview-step2 .form-label {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 10px;
            display: block;
        }

        .exit-interview-step2 .form-control {
            padding: 12px;
            border: 1px solid #bdc3c7;
            border-radius: 5px;
            font-size: 14px;
            resize: vertical;
        }

        .exit-interview-step2 .form-control.is-invalid {
            border-color: #dc3545;
            background-color: #fff5f7;
        }

        .exit-interview-step2 .form-text {
            display: block;
            margin-top: 5px;
            color: #7f8c8d;
            font-size: 12px;
        }

        .question-number {
            display: inline-block;
            background: #3498db;
            color: white;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            text-align: center;
            line-height: 28px;
            margin-right: 8px;
            font-size: 12px;
            font-weight: bold;
        }

        .alert-info {
            margin-top: 20px;
            padding: 15px;
            border-radius: 5px;
            background-color: #e3f2fd;
            border-left: 4px solid #2196f3;
            color: #1565c0;
        }

        .alert-warning {
            margin-top: 20px;
            padding: 15px;
            border-radius: 5px;
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
            color: #856404;
        }

        /* SweetAlert2 Button Customization for Resignation */
        .resignation-wizard .swal2-confirm,
        .exit-interview-wizard .swal2-confirm {
            background-color: #3498db !important;
        }

        .resignation-wizard .swal2-confirm:hover,
        .exit-interview-wizard .swal2-confirm:hover {
            background-color: #2980b9 !important;
        }

        .resignation-wizard .swal2-cancel,
        .exit-interview-wizard .swal2-cancel {
            background-color: #95a5a6 !important;
        }

        .resignation-wizard .swal2-cancel:hover,
        .exit-interview-wizard .swal2-cancel:hover {
            background-color: #7f8c8d !important;
        }

        /* ==========================================
   Documents List View with Viewer
   ========================================== */

        .documents-list-container {
            max-height: 600px;
            overflow-y: auto;
            border: 1px solid #e3eaef;
            border-radius: 8px;
            background: #fff;
        }

        .doc-list-item {
            display: flex;
            align-items: center;
            padding: 8px 12px;
            border-bottom: 1px solid #f0f2f5;
            cursor: pointer;
            transition: all 0.2s ease;
            gap: 8px;
        }

        .doc-list-item:last-child {
            border-bottom: none;
        }

        .doc-list-item:hover {
            background: #f8f9fa;
        }

        .doc-list-item.active {
            background: #e3f2fd;
            border-left: 3px solid #3f51b5;
        }

        .doc-item-icon {
            font-size: 20px;
            width: 28px;
            height: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .doc-item-info {
            flex: 1;
            min-width: 0;
        }

        .doc-item-name {
            font-size: 13px;
            font-weight: 600;
            margin: 0 0 2px 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            color: #313a46;
        }

        .doc-item-meta {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 11px;
        }

        .doc-item-meta .badge-sm {
            padding: 2px 6px;
            font-size: 10px;
            font-weight: 600;
        }

        .doc-item-actions {
            display: flex;
            gap: 4px;
            opacity: 0;
            transition: opacity 0.2s ease;
        }

        .doc-list-item:hover .doc-item-actions {
            opacity: 1;
        }

        .btn-icon {
            width: 32px;
            height: 32px;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 4px;
            border: 1px solid #e3eaef;
            background: #fff;
            color: #74788d;
            transition: all 0.2s ease;
        }

        .btn-icon:hover {
            background: #f8f9fa;
            color: #313a46;
            border-color: #c8ccd4;
        }

        .btn-download-doc:hover {
            background: #e8f5e9;
            color: #51cf66;
            border-color: #51cf66;
        }

        .btn-delete-item:hover {
            background: #ffebee;
            color: #f1556c;
            border-color: #f1556c;
        }

        /* Document Viewer */
        .document-viewer-container {
            border: 1px solid #e3eaef;
            border-radius: 8px;
            background: #fff;
            display: flex;
            flex-direction: column;
            height: 600px;
        }

        .viewer-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 16px;
            border-bottom: 1px solid #e3eaef;
            background: #f8f9fa;
            border-radius: 8px 8px 0 0;
        }

        .viewer-title {
            margin: 0;
            font-size: 15px;
            font-weight: 600;
            color: #313a46;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .viewer-title i {
            color: #74788d;
        }

        .viewer-actions {
            display: flex;
            gap: 6px;
        }

        .viewer-body {
            flex: 1;
            overflow: hidden;
            position: relative;
            background: #f0f2f5;
        }

        .viewer-placeholder {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            color: #98a6ad;
        }

        .viewer-body iframe,
        .viewer-body embed,
        .viewer-body img {
            width: 100%;
            height: 100%;
            border: none;
            display: block;
        }

        .viewer-body img {
            object-fit: contain;
            background: #fff;
        }

        /* Scrollbar Styling */
        .documents-list-container::-webkit-scrollbar {
            width: 6px;
        }

        .documents-list-container::-webkit-scrollbar-track {
            background: #f0f2f5;
        }

        .documents-list-container::-webkit-scrollbar-thumb {
            background: #c8ccd4;
            border-radius: 3px;
        }

        .documents-list-container::-webkit-scrollbar-thumb:hover {
            background: #98a6ad;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .document-viewer-container {
                height: 400px;
                margin-top: 20px;
            }

            .documents-list-container {
                max-height: 300px;
            }
        }

        /* Old Grid System - Deprecated */
        .documents-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
            padding: 10px 0;
        }

        .document-card {
            background: #fff;
            border: 1px solid #e3eaef;
            border-radius: 8px;
            padding: 16px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.08);
        }

        .document-card:hover {
            border-color: #3f51b5;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.12);
            transform: translateY(-4px);
        }

        .document-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 12px;
            gap: 8px;
        }

        .file-type-badge {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 48px;
            height: 48px;
            border-radius: 8px;
            font-size: 24px;
            color: #fff;
            font-weight: 600;
            flex-shrink: 0;
        }

        .file-type-badge.badge-danger {
            background: linear-gradient(135deg, #f1556c 0%, #ee3d54 100%);
        }

        .file-type-badge.badge-success {
            background: linear-gradient(135deg, #51cf66 0%, #37b24d 100%);
        }

        .file-type-badge.badge-primary {
            background: linear-gradient(135deg, #3f51b5 0%, #303f9f 100%);
        }

        .file-type-badge.badge-info {
            background: linear-gradient(135deg, #00bcd4 0%, #00acc1 100%);
        }

        .file-type-badge.badge-warning {
            background: linear-gradient(135deg, #ffc107 0%, #ffb300 100%);
        }

        .file-type-badge.badge-secondary {
            background: linear-gradient(135deg, #98a6ad 0%, #7a8a97 100%);
        }

        .btn-delete-doc {
            background: transparent;
            border: none;
            color: #98a6ad;
            font-size: 18px;
            cursor: pointer;
            padding: 4px 8px;
            border-radius: 4px;
            transition: all 0.2s ease;
            opacity: 0.6;
        }

        .btn-delete-doc:hover {
            background: rgba(241, 85, 108, 0.1);
            color: #f1556c;
            opacity: 1;
        }

        .document-preview {
            margin-bottom: 12px;
            border-radius: 6px;
            overflow: hidden;
            background: #f8f9fa;
            min-height: 120px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-grow: 1;
        }

        .preview-image {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .preview-image img {
            max-width: 100%;
            max-height: 100%;
            border-radius: 4px;
            cursor: pointer;
            transition: transform 0.3s ease;
        }

        .preview-image img:hover {
            transform: scale(1.05);
        }

        .preview-icon {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
            color: #fff;
            opacity: 0.8;
            background-size: cover;
            background-position: center;
        }

        .preview-icon.bg-danger {
            background: linear-gradient(135deg, rgba(241, 85, 108, 0.2) 0%, rgba(238, 61, 84, 0.2) 100%);
            color: #f1556c;
        }

        .preview-icon.bg-success {
            background: linear-gradient(135deg, rgba(81, 207, 102, 0.2) 0%, rgba(55, 178, 77, 0.2) 100%);
            color: #51cf66;
        }

        .preview-icon.bg-primary {
            background: linear-gradient(135deg, rgba(63, 81, 181, 0.2) 0%, rgba(48, 63, 159, 0.2) 100%);
            color: #3f51b5;
        }

        .preview-icon.bg-info {
            background: linear-gradient(135deg, rgba(0, 188, 212, 0.2) 0%, rgba(0, 172, 193, 0.2) 100%);
            color: #00bcd4;
        }

        .preview-icon.bg-warning {
            background: linear-gradient(135deg, rgba(255, 193, 7, 0.2) 0%, rgba(255, 179, 0, 0.2) 100%);
            color: #ffc107;
        }

        .preview-icon.bg-secondary {
            background: linear-gradient(135deg, rgba(152, 166, 173, 0.2) 0%, rgba(122, 138, 151, 0.2) 100%);
            color: #98a6ad;
        }

        .document-info {
            margin-bottom: 12px;
        }

        .document-type {
            font-size: 14px;
            font-weight: 600;
            color: #313a46;
            margin: 0 0 4px 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .document-category {
            font-size: 13px;
            color: #7a8a97;
            margin: 0 0 8px 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .document-meta {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            font-size: 12px;
            color: #98a6ad;
        }

        .document-meta span {
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .document-meta i {
            font-size: 11px;
        }

        .document-actions {
            display: flex;
            gap: 8px;
            margin-top: auto;
        }

        .document-actions .btn {
            flex: 1;
            padding: 6px 8px;
            font-size: 12px;
            border-radius: 4px;
            transition: all 0.2s ease;
            border: none;
            font-weight: 500;
        }

        .document-actions .btn-view {
            background: #e3f2fd;
            color: #3f51b5;
        }

        .document-actions .btn-view:hover {
            background: #bbdefb;
            color: #303f9f;
        }

        .document-actions .btn-download {
            background: #e8f5e9;
            color: #51cf66;
        }

        .document-actions .btn-download:hover {
            background: #c8e6c9;
            color: #37b24d;
        }

        /* Responsive Grid Adjustments */
        @media (max-width: 768px) {
            .documents-grid {
                grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
                gap: 16px;
            }
        }

        @media (max-width: 576px) {
            .documents-grid {
                grid-template-columns: 1fr;
                gap: 12px;
            }

            .document-card {
                padding: 12px;
            }

            .document-actions {
                flex-direction: column;
            }
        }

        /* ==========================================
RTL Support
========================================== */
        .rtl {
            direction: rtl;
            text-align: right;
        }

        /* Flip common spacing utilities used here */
        .rtl .ml-2 {
            margin-left: 0 !important;
            margin-right: .5rem !important;
        }

        .rtl .mr-3 {
            margin-right: 0 !important;
            margin-left: 1rem !important;
        }

        .rtl .viewer-actions {
            flex-direction: row-reverse;
        }

        .rtl .doc-list-item {
            flex-direction: row-reverse;
        }

        /* Ensure icon renders at right side with proper spacing in RTL */
        .rtl .doc-item-icon {
            order: 3;
            margin-left: 0;
            margin-right: 8px;
        }

        .rtl .doc-item-actions {
            order: 1;
        }

        .rtl .doc-item-info {
            order: 2;
            text-align: right;
        }

        .rtl .doc-item-info {
            text-align: right;
        }

        .rtl .documents-list-container {
            text-align: right;
        }

        .rtl .viewer-title {
            flex-direction: row-reverse;
        }

        .rtl .document-meta {
            justify-content: flex-end;
        }

        .rtl .info-row {
            justify-content: space-between;
        }

        .rtl .info-label {
            margin-left: 0;
            margin-right: 8px;
        }

        .rtl .profile-header-info p {
            text-align: right;
        }

        .rtl .menu-items-container {
            direction: rtl;
        }

        .rtl .swal2-close {
            left: 12px;
            right: auto;
        }
    </style>

    <?php if($is_rtl ?? false): ?> 
        <style>
            .swal2-html-container > div{
                direction: rtl !important;
                text-align: right !important;
            }
        </style>
    <?php endif; ?>
    <?php
		// Determine status styling
		$header_class = 'profile-header';
		$status_label = __('active');
		$status_icon = 'fa-check-circle';
		
		// Check vacation status first (has priority)
		if ($emprow["fly"] == 1) {
			$header_class .= ' vacation';
			$status_label = __('on_vacation');
			$status_icon = 'fa-plane-departure';
		} elseif ($emprow["status"] == "0") {
			$header_class .= ' inactive';
			$status_label = __('inactive');
			$status_icon = 'fa-times-circle';
		}
		?>

    <script>
        window.lang = <?= json_encode($GLOBALS['translations'] ?? []) ?>;
    </script>

</head>

<body dir="<?= ($is_rtl ?? false) ? 'rtl' : 'ltr' ?>" class="<?= ($is_rtl ?? false) ? 'rtl' : '' ?>">
    <div class="profile-container">
        <!-- HEADER SECTION -->
        <div class="<?= $header_class ?>">
            <div class="container-custom">
                <!-- Avatar (display only - no upload) -->
                <div>
                    <?php
                    // Get avatar display path using centralized helper function
                    $displayImage = getAvatarImagePath($emprow['avatar'] ?? '', $emprow['sex'] ?? 1);
                    ?>
                    <img src="<?= $displayImage ?>" alt="<?= htmlspecialchars($emprow['name']) ?>" class="profile-avatar">
                </div>

                <div class="profile-header-info">
                    <h1><?= getDisplayName($emprow['name']) ?></h1>
                    <p><strong><?= __('employee_id') ?>:</strong> <?= display_or_na($emprow['empid'] ?? null) ?></p>
                    <p><strong><?= __('department') ?>:</strong> <?= ($is_rtl ?? false) ? $emprow["deptnme_ar"] : $emprow["deptnme"] ?></p>
                    <p><strong><?= __('actual_job_label') ?>:</strong> <?= ($is_rtl ?? false) ? $emprow["jobname_ar"] : $emprow["jobname"] ?></p>
                </div>

                <div class="profile-quick-stats">
                    <div class="stat-item">
                        <div class="stat-number"><?= $years ?></div>
                        <div class="stat-label"><?= __('age') ?></div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number"><?= $displayBalance < 0
                                                        ? number_format($displayBalance, 2)
                                                        : ($displayBalance == floor($displayBalance) ? number_format($displayBalance, 0) : number_format($displayBalance, 2))
                                                    ?></div>
                        <div class="stat-label"><?= __('vacation_days') ?></div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number"><?= number_format($emprow['salary'], 0) ?></div>
                        <div class="stat-label"><?= __('salary') ?> (<i class="icon-saudi_riyal"></i>)</div>
                    </div>
                </div>

                <?php if (file_exists("./assets/qrcodes/" . $emprow['eid'] . $emprow['empid'] . ".png")): ?>
                    <img src="./assets/qrcodes/<?= $emprow['eid'] . $emprow['empid'] . ".png" ?>" alt="QR Code" class="qr-code">
                <?php endif; ?>
            </div>

            <!-- LANGUAGE SWITCHER & MORE ACTIONS - BELOW QR CODE -->
            <?php
            $switch_to_lang = ($current_lang == 'en') ? 'ar' : 'en';
            $button_text = ($current_lang == 'en') ? 'العربية' : 'English';
            $query_params = [];
            if (!empty($_SERVER['QUERY_STRING'])) {
                parse_str($_SERVER['QUERY_STRING'], $query_params);
            }
            $query_params['change_lang'] = $switch_to_lang;
            $base_path = strtok($_SERVER['REQUEST_URI'], '?');
            $new_query_string = http_build_query($query_params);
            $switch_url = htmlspecialchars($base_path . '?' . $new_query_string);
            ?>
            <div class="profile-actions-row" style="display: flex; gap: 8px; align-items: center; padding-left: 40px; padding-right: 40px; padding-top: 16px; position: relative; z-index: 10;">
                <a href="<?= $switch_url ?>" class="more-actions-btn" style="text-decoration: none;">
                    <i class="fa fa-language"></i> <?= $button_text ?>
                </a>
                <button class="more-actions-btn" id="moreActionsBtn">
                    <i class="fa fa-ellipsis-v"></i> <?= __('more') ?>
                </button>
                <button class="more-actions-btn" id="recordsBtn">
                    <i class="fa fa-folder-open"></i> <?= __('employee_records') ?>
                </button>
            <?php if ($hasGrantedPages) : ?>
                <button class="more-actions-btn" id="myPagesBtn">
                    <i class="fa fa-th-large"></i> <?= __('my_pages', 'My Pages') ?>
                </button>
            <?php endif; ?>
            <?php if ($emprow["user_type"] <> 'employee') : ?>
                <a href="dashboard.php" class="more-actions-btn" style="text-decoration: none;">
                    <i class="fa fa-airplay"></i> <?= __('dashboard') ?>
                </a>
            <?php endif; ?>

            </div>
        </div>

        <!-- More Actions handled by SweetAlert2 -->

        <?php if ($emprow["status"] == 1 && $emprow["fly"] == 0) : ?>
            <div class="employee-tenure-card">
                <button type="button" class="tenure-close-btn" onclick="this.closest('.employee-tenure-card').style.display='none'">
                    <i class="fa fa-times"></i>
                </button>

                <div class="tenure-content">
                    <div class="tenure-icon-wrapper">
                        <i class="fa fa-award"></i>
                    </div>

                    <div class="tenure-text-wrapper">
                        <div class="tenure-title">
                            <i class="fa fa-sparkles"></i>
                            <?= __('employee_milestone', 'Employee Milestone') ?>
                        </div>
                        <div class="tenure-message">
                            <?= __('happy_life_with_us') . " " . ageDOB($emprow['joining_date']) ?>
                        </div>
                        <div class="tenure-badge">
                            <i class="fa fa-calendar-check"></i>
                            <?= __('active_status', 'Active Member') ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- PERSONAL INFORMATION -->
        <div style="margin-bottom: 40px;">
            <h3 class="section-title"><i class="fa fa-user-circle"></i> <?= __('personal_information') ?></h3>
            <div class="cards-grid">
                <div class="info-card">
                    <div class="info-card-header">
                        <div class="info-card-icon" style="background: var(--primary);"><i class="fa fa-id-card"></i></div>
                        <div class="info-card-title"><?= __('identity') ?></div>
                    </div>
                    <div class="info-row">
                        <span class="info-label"><?= __('iqama_id_label') ?></span>
                        <span class="info-value"><?= display_or_na($emprow['iqama'] ?? null) ?> <i class="fa fa-copy copy-btn"></i></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label"><?= __('iqama_id_expiry') ?></span>
                        <span class="info-value">
                            <?php
                            $iqama_h = trim($emprow['iqama_exp'] ?? '');
                            $iqama_g = trim($emprow['iqama_exp_g'] ?? '');
                            if ($iqama_h) {
                                echo htmlspecialchars($iqama_h) . ' (' . __('hijri') . ')';
                                try {
                                    $convG = $DateConv->HijriToGregorian($iqama_h, $format);
                                    if (!empty($convG)) echo ' / ' . htmlspecialchars($convG) . ' (' . __('gregorian') . ')';
                                } catch (Exception $e) { /* ignore */
                                }
                            } elseif ($iqama_g) {
                                echo htmlspecialchars($iqama_g) . ' (' . __('gregorian') . ')';
                                try {
                                    $convH = $DateConv->GregorianToHijri($iqama_g, $format);
                                    if (!empty($convH)) echo ' / ' . htmlspecialchars($convH) . ' (' . __('hijri') . ')';
                                } catch (Exception $e) { /* ignore */
                                }
                            } else {
                                echo 'N/A';
                            }
                            ?>
                        </span>
                    </div>
                    <div class="info-row">
                        <span class="info-label"><?= __('passport_no_label') ?></span>
                        <span class="info-value"><?= display_or_na($emprow['passport_number'] ?? null) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label"><?= __('passport_expiry') ?></span>
                        <span class="info-value">
                            <?php if (!empty($emprow['passport_exp'])):
                                $pexp_g = $emprow['passport_exp'];
                                echo htmlspecialchars($pexp_g) . ' (' . __('gregorian') . ')';
                                try {
                                    $pexp_h = $DateConv->GregorianToHijri($pexp_g, $format);
                                    if (!empty($pexp_h)) echo ' / ' . htmlspecialchars($pexp_h) . ' (' . __('hijri') . ')';
                                } catch (Exception $e) { /* ignore */
                                }
                            else: echo 'N/A';
                            endif; ?>
                        </span>
                    </div>
                    <div class="info-row">
                        <span class="info-label"><?= __('dob_label') ?></span>
                        <span class="info-value"><?= $emprow['dob'] ?> (<?= $years ?> <?= __('yrs') ?>)</span>
                    </div>
                </div>

                <div class="info-card">
                    <div class="info-card-header">
                        <div class="info-card-icon" style="background: var(--success);"><i class="fa fa-envelope"></i></div>
                        <div class="info-card-title"><?= __('contact') ?></div>
                    </div>
                    <div class="info-row">
                        <span class="info-label"><?= __('email') ?></span>
                        <span class="info-value"><?= display_or_na($emprow['email'] ?? null) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label"><?= __('mobile') ?></span>
                        <span class="info-value"><?= display_or_na($emprow['mobile'] ?? null) ?> <i class="fa fa-copy copy-btn"></i></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label"><?= __('address') ?></span>
                        <span class="info-value"><?= display_or_na($emprow['address'] ?? null) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label"><?= __('country') ?></span>
                        <span class="info-value"><?= ($is_rtl ?? false) ? $emprow["country_name_ar"] : $emprow["country_name"] ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label"><?= __('emergency_contact') ?></span>
                        <span class="info-value"><?= display_or_na($emprow['emg_name'] ?? null) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label"><?= __('emergency_mobile_no_label') ?></span>
                        <span class="info-value"><?= display_or_na($emprow['emg_mobile'] ?? null) ?> <i class="fa fa-copy copy-btn"></i></span>
                    </div>
                </div>

                <div class="info-card">
                    <div class="info-card-header">
                        <div class="info-card-icon" style="background: var(--info);"><i class="fa fa-briefcase"></i></div>
                        <div class="info-card-title"><?= __('employment') ?></div>
                    </div>
                    <div class="info-row">
                        <span class="info-label"><?= __('joining_date_label') ?></span>
                        <span class="info-value"><?= $emprow['joining_date'] ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label"><?= __('contract_expiry_label', 'Contract Expiry') ?></span>
                        <span class="info-value">
                            <?php
                            $expiryStr = computeContractExpiry(
                                $emprow['joining_date'] ?? null,
                                isset($emprow['vac_period']) ? (int)$emprow['vac_period'] : null,
                                'd M Y'
                            );
                            echo htmlspecialchars($expiryStr ?? 'N/A');
                            ?>
                        </span>
                    </div>
                    <div class="info-row">
                        <span class="info-label"><?= __('company_label') ?></span>
                        <span class="info-value"><?= htmlspecialchars((($is_rtl ?? false) ? ($emprow['compnme_ar'] ?? $emprow['compnme']) : $emprow['compnme']) ?? 'N/A') ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label"><?= __('sponsorship_label') ?></span>
                        <span class="info-value"><?= htmlspecialchars($emprow['sponsor']) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label"><?= __('contract_period_label') ?></span>
                        <span class="info-value"><?= formatPeriod($emprow["period"]) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label"><?= __('direct_supervisor', 'Direct Supervisor') ?></span>
                        <span class="info-value">
                            <?php
                            if (!empty($emprow['supervisor_id']) && !empty($emprow['supervisor_name'])) {
                                echo getDisplayName($emprow['supervisor_name']) . ' (' . htmlspecialchars($emprow['supervisor_id']) . ')';
                            } else {
                                echo __('not_assigned', 'Not Assigned');
                            }
                            ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- EMPLOYMENT DETAILS -->
        <div style="margin-bottom: 40px;">
            <h3 class="section-title"><i class="fa fa-file-contract"></i> <?= __('employment_details') ?></h3>
            <div class="cards-grid">
                <div class="info-card">
                    <div class="info-card-header">
                        <div class="info-card-icon" style="background: var(--warning);"><i class="fa fa-money-bill"></i></div>
                        <div class="info-card-title"><?= __('salary') ?></div>
                    </div>
                    <div class="info-row">
                        <span class="info-label"><?= __('total_salary') ?></span>
                        <span class="info-value"><?= number_format($emprow['salary'], 2) ?> <i class="icon-saudi_riyal"></i></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label"><?= __('basic') ?></span>
                        <span class="info-value"><?= number_format($emprow['basic'], 2) ?> <i class="icon-saudi_riyal"></i></span>
                    </div>
                    <?php
                    // Dynamically list all additional salary components (allowances/benefits)
                    $salary_components = [
                        'housing'   => __('housing', 'Housing Allowance'),
                        'transport' => __('transport_allowance', 'Transport Allowance'),
                        'food'      => __('food_allowance', 'Food Allowance'),
                        'fuel'      => __('fuel_allowance', 'Fuel Allowance'),
                        'tel'       => __('telephone_allowance', 'Telephone Allowance'),
                        'cashier'   => __('cashier_allowance', 'Cashier Allowance'),
                        'misc'      => __('misc_allowance', 'Misc Allowance'),
                        'other'     => __('other_allowance', 'Other Allowance'),
                        'guard'     => __('guard_allowance', 'Guard Allowance'),
                    ];

                    foreach ($salary_components as $field => $label) {
                        if (isset($emprow[$field]) && floatval($emprow[$field]) > 0) {
                            echo '<div class="info-row">'
                                . '<span class="info-label">' . htmlspecialchars($label) . '</span>'
                                . '<span class="info-value">' . number_format((float)$emprow[$field], 2) . ' <i class="icon-saudi_riyal"></i></span>'
                                . '</div>';
                        }
                    }
                    ?>
                </div>

                <div class="info-card">
                    <div class="info-card-header">
                        <div class="info-card-icon" style="background: var(--danger);"><i class="fa fa-university"></i></div>
                        <div class="info-card-title"><?= __('banking_insurance', 'Banking & Insurance') ?></div>
                    </div>
                    <div class="info-row">
                        <span class="info-label"><?= __('status') ?></span>
                        <span class="info-value"><?= display_or_na($emprow['gosi'] ?? null) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label"><?= __('gosi_no') ?></span>
                        <span class="info-value"><?= display_or_na($emprow['gosi_no'] ?? null) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label"><?= __('bank_name_label') ?></span>
                        <span class="info-value"><?= ($is_rtl ?? false) ? $emprow["b_name_ar"] : $emprow["b_name"] ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label"><?= __('iban', 'IBAN') ?></span>
                        <span class="info-value"><?= display_or_na($emprow['iban'] ?? null) ?> <i class="fa fa-copy copy-btn"></i></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label"><?= __('insurance_no', 'Insurance No.') ?></span>
                        <span class="info-value"><?= display_or_na($current_medical_insurance['insurance_no'] ?? null) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label"><?= __('insurance_class', 'Insurance Class') ?></span>
                        <span class="info-value"><?= display_or_na($current_medical_class) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label"><?= __('insurance_expiry', 'Insurance Expiry') ?></span>
                        <span class="info-value"><?= (!empty($current_medical_insurance['medical_expiry']) && $current_medical_insurance['medical_expiry'] !== '0000-00-00') ? format_safe_date($current_medical_insurance['medical_expiry'], 'd M, Y') : __('not_available') ?></span>
                    </div>
                </div>

                <div class="info-card">
                    <div class="info-card-header">
                        <div class="info-card-icon" style="background: #6f42c1;"><i class="fa fa-boxes"></i></div>
                        <div class="info-card-title"><?= __('status_assets', 'Status & Assets') ?></div>
                    </div>
                    <div class="info-row">
                        <span class="info-label"><?= __('status') ?></span>
                        <span class="info-value">
                            <?php if ($emprow['status'] == 1): ?>
                                <span style="color: var(--success); font-weight: 600;"><?= __('active') ?></span>
                            <?php else: ?>
                                <span style="color: var(--danger); font-weight: 600;"><?= __('inactive') ?></span>
                            <?php endif; ?>
                        </span>
                    </div>
                    <div class="info-row">
                        <span class="info-label"><?= __('salary_payment_type_label') ?></span>
                        <span class="info-value"><?= ($emprow['payment_type'] == 1 ? __('bank_option') : __('cash_option')) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label"><?= __('fly_trips', 'Fly Trips') ?></span>
                        <span class="info-value"><?= (int)($emprow['flystus'] ?? 0) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label"><?= __('encashed') ?></span>
                        <span class="info-value"><?= (int)($emprow['encashstus'] ?? 0) ?></span>
                    </div>
                    <?php
                    // Count all assigned assets (employee_assets) - only active ones
                    $assets_count_query = mysqli_query($conDB, "SELECT COUNT(*) as total 
                                                               FROM employee_assets ea 
                                                               WHERE ea.emp_id = '" . mysqli_real_escape_string($conDB, $emprow['empid']) . "' 
                                                               AND (ea.return_date IS NULL OR ea.return_date = '' OR ea.return_date = '0000-00-00')");
                    $assets_count = 0;
                    if ($assets_count_query) {
                        $assets_result = mysqli_fetch_assoc($assets_count_query);
                        $assets_count = (int)$assets_result['total'];
                        mysqli_free_result($assets_count_query);
                    }
                    
                    // Count assigned cars (cars_drv) - only active ones
                    $cars_count_query = mysqli_query($conDB, "SELECT COUNT(*) as total 
                                                             FROM cars_drv cd 
                                                             WHERE cd.car_user = '" . mysqli_real_escape_string($conDB, $emprow['empid']) . "' 
                                                             AND (cd.rtn_date IS NULL OR cd.rtn_date = '' OR cd.rtn_date = '0000-00-00')");
                    $cars_count = 0;
                    if ($cars_count_query) {
                        $cars_result = mysqli_fetch_assoc($cars_count_query);
                        $cars_count = (int)$cars_result['total'];
                        mysqli_free_result($cars_count_query);
                    }
                    
                    $total_assets = $assets_count + $cars_count;
                    ?>
                    <div class="info-row">
                        <span class="info-label"><?= __('assigned_assets', 'Assigned Assets') ?></span>
                        <span class="info-value">
                            <?php if ($total_assets > 0): ?>
                                <strong><?= $total_assets ?></strong>
                            <?php else: ?>
                                <?= __('none', 'None') ?>
                            <?php endif; ?>
                        </span>
                    </div>
                    <?php if (!empty($emprow['leaving_reason']) || !empty($emprow['end_date'])): ?>
                        <div class="info-row">
                            <span class="info-label"><?= __('eos_leaving_reason', 'Leaving Reason') ?></span>
                            <span class="info-value"><?= htmlspecialchars(($is_rtl ?? false) ? ($emprow['leaving_reason_ar'] ?? $emprow['leaving_reason']) : ($emprow['leaving_reason'] ?? '')) ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label"><?= __('eos_end_date', 'End Date') ?></span>
                            <span class="info-value"><?= htmlspecialchars($emprow['end_date'] ?? '') ?></span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- DOCUMENTS SECTION -->
        <div>
            <h3 class="section-title"><i class="mdi mdi-file-document-multiple"></i> <?= __('my_files') ?></h3>
            <div class="card-box" style="background: var(--white); border-radius: 12px; padding: 24px; box-shadow: var(--shadow-md);">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0"><i class="fa fa-file-upload"></i> <?= __('employee_documents') ?></h5>
                    <span class="badge badge-primary badge-pill">
                        <?php
                        $doc_query = mysqli_query($conDB, "SELECT COUNT(*) as total FROM `emp_docu` WHERE `emp_id`='" . $emprow['empid'] . "'");
                        $doc_result = mysqli_fetch_assoc($doc_query);
                        echo $doc_result['total'] ?? 0;
                        ?>
                    </span>
                </div>

                <?php
                $queryempdocu = mysqli_query($conDB, "SELECT * FROM `emp_docu` WHERE `emp_id`='" . $emprow['empid'] . "' AND `status`='A' ORDER BY `id` DESC ");
                $doc_count = mysqli_num_rows($queryempdocu);

                if ($doc_count > 0):
                ?>
                    <div class="row">
                        <!-- Documents List Column -->
                        <div class="col-12">
                            <div class="documents-list-container">
                                <?php
                                mysqli_data_seek($queryempdocu, 0);
                                while ($recempdoc = mysqli_fetch_assoc($queryempdocu)) {
                                    $id_empdoc_get = $recempdoc["id"];
                                    $docu_typ_get = $recempdoc["docu_typ"];
                                    $attachment_get = $recempdoc["path"];
                                    $docu_ext_get = strtolower($recempdoc["docu_ext"]);
                                    $doc_date_reg_get = $recempdoc["created_at"];
                                    $times_reg = strtotime("$doc_date_reg_get");
                                    $doc_date_formatted = date('d M Y', $times_reg);

                                    // Determine file type and icon
                                    $file_type_map = [
                                        'pdf' => ['icon' => 'fa-file-pdf', 'color' => 'danger', 'label' => 'PDF'],
                                        'jpg' => ['icon' => 'fa-file-image', 'color' => 'info', 'label' => 'Image'],
                                        'jpeg' => ['icon' => 'fa-file-image', 'color' => 'info', 'label' => 'Image'],
                                        'png' => ['icon' => 'fa-file-image', 'color' => 'info', 'label' => 'Image'],
                                        'gif' => ['icon' => 'fa-file-image', 'color' => 'info', 'label' => 'Image'],
                                    ];
                                    $file_info = $file_type_map[$docu_ext_get] ?? ['icon' => 'fa-file', 'color' => 'secondary', 'label' => 'File'];
                                ?>
                                    <div class="doc-list-item" data-doc-id="<?= $id_empdoc_get ?>"
                                        data-doc-path="./assets/emp_documents/<?= $attachment_get ?>"
                                        data-doc-ext="<?= $docu_ext_get ?>"
                                        data-doc-name="<?= htmlspecialchars($docu_typ_get ?: $file_info['label']) ?>">
                                        <div class="doc-item-icon">
                                            <i class="fa <?= $file_info['icon'] ?> text-<?= $file_info['color'] ?>"></i>
                                        </div>
                                        <div class="doc-item-info">
                                            <h6 class="doc-item-name"><?= htmlspecialchars($docu_typ_get ?: $file_info['label']) ?></h6>
                                            <small class="doc-item-meta">
                                                <span class="badge badge-<?= $file_info['color'] ?> badge-sm"><?= strtoupper($docu_ext_get) ?></span>
                                                <span class="text-muted ml-2"><i class="fa fa-calendar"></i> <?= $doc_date_formatted ?></span>
                                            </small>
                                        </div>
                                        <div class="doc-item-actions">
                                            <button class="btn btn-sm btn-icon btn-download-doc" title="<?= __('download') ?>" onclick="window.location.href='./downloadFile.php?file=./assets/emp_documents/<?= $attachment_get ?>'">
                                                <i class="fa fa-download"></i>
                                            </button>
                                        </div>
                                    </div>
                                <?php } ?>
                            </div>
                        </div>

                        <!-- Document Viewer Column -->
                        <div class="col-md-7">
                            <div class="document-viewer-container" style="display:none;">
                                <div class="viewer-header">
                                    <h5 class="viewer-title"><i class="fa fa-file"></i> <span id="viewer-doc-name-profile"><?= __('select_document') ?></span></h5>
                                    <div class="viewer-actions">
                                        <button class="btn btn-sm btn-light" id="viewer-fullscreen-profile" title="<?= __('fullscreen') ?>">
                                            <i class="fa fa-expand"></i>
                                        </button>
                                        <button class="btn btn-sm btn-light" id="viewer-download-profile" title="<?= __('download') ?>" style="display:none;">
                                            <i class="fa fa-download"></i>
                                        </button>
                                        <button class="btn btn-sm btn-light" id="viewer-clear-profile" title="<?= __('close') ?>">
                                            <i class="fa fa-times"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="viewer-body" id="document-viewer-profile">
                                    <div class="viewer-placeholder">
                                        <i class="fa fa-file-text" style="font-size: 64px; color: #ddd;"></i>
                                        <p class="text-muted mt-3"><?= __('select_document_to_view') ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info" role="alert">
                        <div class="d-flex align-items-center">
                            <i class="fa fa-info-circle mr-3" style="font-size: 24px;"></i>
                            <div>
                                <h5 class="mb-1"><?= __('no_documents_found') ?></h5>
                                <p class="mb-0"><?= __('no_documents_have_been_uploaded_yet') ?></p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </div>

    <?php
    // Employee Records - same counts previously shown as cards, now surfaced via the
    // "Records" button/modal (same SweetAlert2 menu pattern as More Actions / My Pages).
    $emp_id_escaped = mysqli_real_escape_string($conDB, $emprow['empid']);

    $vac_count_query = mysqli_query($conDB, "SELECT COUNT(*) as total FROM emp_vacation WHERE emp_id = '$emp_id_escaped' AND current_status IN ('pending_approval', 'approved')");
    $vac_count = 0;
    if ($vac_count_query) {
        $vac_result = mysqli_fetch_assoc($vac_count_query);
        $vac_count = (int)$vac_result['total'];
    }

    $loan_count_query = mysqli_query($conDB, "SELECT COUNT(*) as total FROM emp_loan WHERE emp_id = '$emp_id_escaped' AND status LIKE 'pending%'");
    $loan_count = 0;
    if ($loan_count_query) {
        $loan_result = mysqli_fetch_assoc($loan_count_query);
        $loan_count = (int)$loan_result['total'];
    }

    // Active assigned assets (already calculated above)
    // $total_assets already available

    $payroll_count_query = mysqli_query($conDB, "SELECT COUNT(*) as total FROM emp_salary WHERE emp_id = '$emp_id_escaped' AND status = 1");
    $payroll_count = 0;
    if ($payroll_count_query) {
        $payroll_result = mysqli_fetch_assoc($payroll_count_query);
        $payroll_count = (int)$payroll_result['total'];
    }

    $warnings_count_query = mysqli_query($conDB, "SELECT COUNT(*) as total FROM emp_notice WHERE emp_id = '$emp_id_escaped' AND status = 1 AND is_deleted = 0");
    $warnings_count = 0;
    if ($warnings_count_query) {
        $warnings_result = mysqli_fetch_assoc($warnings_count_query);
        $warnings_count = (int)$warnings_result['total'];
    }

    // Evaluations table doesn't exist in current schema
    $eval_count = 0;

    $business_trip_count_query = mysqli_query($conDB, "SELECT COUNT(*) as total FROM emp_business_trip WHERE emp_id = '$emp_id_escaped'");
    $business_trip_count = 0;
    if ($business_trip_count_query) {
        $business_trip_count_result = mysqli_fetch_assoc($business_trip_count_query);
        $business_trip_count = (int)($business_trip_count_result['total'] ?? 0);
        mysqli_free_result($business_trip_count_query);
    }

    $salary_increment_count_query = mysqli_query($conDB, "SELECT COUNT(*) as total FROM emp_salary_increment WHERE emp_id = '$emp_id_escaped'");
    $salary_increment_count = 0;
    if ($salary_increment_count_query) {
        $salary_increment_count_result = mysqli_fetch_assoc($salary_increment_count_query);
        $salary_increment_count = (int)($salary_increment_count_result['total'] ?? 0);
        mysqli_free_result($salary_increment_count_query);
    }

    $recordItem = function ($href, $empIdVal, $colorClass, $icon, $title, $count) {
        $countBadge = $count > 0 ? "<span class=\"menu-item-count\">" . (int)$count . "</span>" : '';
        return "<a href=\"" . htmlspecialchars($href . '?emp_id=' . $empIdVal, ENT_QUOTES) . "\" class=\"menu-item {$colorClass}\"><i class=\"fa {$icon}\"></i><span>" . $title . "</span>{$countBadge}</a>";
    };

    $recordsHtml = '';
    $recordsHtml .= $recordItem('employee_vacation_history.php', $emprow['empid'], 'text-primary', 'fa-calendar-check', __('vacation_history'), $vac_count);
    $recordsHtml .= $recordItem('employee_loan_history.php', $emprow['empid'], 'text-success', 'fa-money-bill', __('loan_history'), $loan_count);
    $recordsHtml .= $recordItem('employee_assigned_assets.php', $emprow['empid'], 'text-info', 'fa-briefcase', __('assigned_assets'), $total_assets ?? 0);
    $recordsHtml .= $recordItem('employee_payroll_slip.php', $emprow['empid'], 'text-warning', 'fa-file-invoice', __('payroll_slips'), $payroll_count);
    $recordsHtml .= $recordItem('employee_warnings.php', $emprow['empid'], 'text-danger', 'fa-exclamation-circle', __('notices'), $warnings_count);
    $recordsHtml .= $recordItem('employee_evaluation_history.php', $emprow['empid'], 'text-purple', 'fa-star', __('evaluations'), $eval_count);
    $recordsHtml .= $recordItem('employee_business_trip_history.php', $emprow['empid'], 'text-info', 'fa-plane', __('business_trip_history', 'Business Trip History'), $business_trip_count);
    $recordsHtml .= $recordItem('employee_salary_increment_history.php', $emprow['empid'], 'text-primary', 'fa-arrow-trend-up', __('salary_increment_history', 'Salary Increment History'), $salary_increment_count);
    ?>

    <!-- Hidden file input for image cropping -->
    <input type="file" id="img-crop-input" accept="image/*" style="display: none;">

    <script src="assets/js/jquery.min.js?t=<?= time() ?>"></script>
    <script src="assets/js/bootstrap.bundle.min.js?t=<?= time() ?>"></script>
    <script src="./plugins/croppie/exif.js"></script>
    <script src="./plugins/croppie/croppie.min.js"></script>

    <!-- Dropzone JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.9.3/dropzone.min.js?t=<?= time() ?>"></script>

    <!-- Moment.js for date manipulation -->
    <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.4/moment.min.js?t=<?= time() ?>"></script>
    <!-- Date Pickers -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.10.0/js/bootstrap-datepicker.min.js?t=<?= time() ?>"></script>
    <script src="./plugins/bootstrap-daterangepicker/daterangepicker.js?t=<?= time() ?>"></script>
    <!-- Select2 -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js?t=<?= time() ?>"></script>
    <!-- Main App JS -->
    <script src="assets/js/employee_profile.js?t=<?= time() ?>"></script>
    <script src="assets/js/businessTrip.js?t=<?= time() ?>"></script>
    <script src="assets/js/loanHandling.js?t=<?= time() ?>"></script>
    <script src="assets/js/resignationWizard.js?t=<?= time() ?>"></script>
    <script>
        $(document).ready(function() {

            var moreActionsHtml = <?= json_encode($moreActionsHtml); ?>;
            var grantedPagesHtml = <?= json_encode($grantedPagesHtml); ?>;
            var recordsHtml = <?= json_encode($recordsHtml); ?>;
            $('#recordsBtn').click(function() {
                Swal.fire({
                    title: '<?= __('employee_records') ?>',
                    html: '<div class="menu-items-container">' + recordsHtml + '</div>',
                    showConfirmButton: false,
                    showCloseButton: true,
                    customClass: {
                        container: 'more-actions-modal',
                        popup: 'swal2-popup',
                        closeButton: 'swal2-close'
                    },
                    width: '450px',
                    padding: '0',
                    allowOutsideClick: false
                });
            });
            $('#myPagesBtn').click(function() {
                Swal.fire({
                    title: '<?= __('my_pages', 'My Pages') ?>',
                    html: '<div class="menu-items-container">' + grantedPagesHtml + '</div>',
                    showConfirmButton: false,
                    showCloseButton: true,
                    customClass: {
                        container: 'more-actions-modal',
                        popup: 'swal2-popup',
                        closeButton: 'swal2-close'
                    },
                    allowOutsideClick: false,
                    width: '450px',
                    padding: '0'
                });
            });
            $('#moreActionsBtn').click(function() {
                Swal.fire({
                    title: '<?= __('more_actions') ?>',
                    html: '<div class="menu-items-container">' + moreActionsHtml + '</div>',
                    showConfirmButton: false,
                    showCloseButton: true,
                    customClass: {
                        container: 'more-actions-modal',
                        popup: 'swal2-popup',
                        closeButton: 'swal2-close'
                    },
                    width: '450px',
                    padding: '0',
                    allowOutsideClick: false,
                    didOpen: function() {
                        // Get modal container and wrap it with jQuery
                        var modalContainer = $(Swal.getHtmlContainer());

                        // Edit Information
                        modalContainer.find('#startUpdateRequest').on('click', function(e) {
                            e.preventDefault();
                            Swal.close();
                            setTimeout(function() {
                                $('#startUpdateRequest').trigger('click');
                            }, 100);
                        });

                        // Apply Vacation
                        modalContainer.find('.applyvacationAtter').on('click', function(e) {
                            e.preventDefault();
                            var empid = $(this).data('empid');
                            Swal.close();
                            setTimeout(function() {
                                $('.applyvacationAtter[data-empid="' + empid + '"]').not('.swal2-html-container *').first().trigger('click');
                            }, 100);
                        });

                        // Apply Leave Request
                        modalContainer.find('.applyLeaveRequest').on('click', function(e) {
                            e.preventDefault();
                            var empid = $(this).data('empid');
                            Swal.close();
                            setTimeout(function() {
                                $('.applyLeaveRequest[data-empid="' + empid + '"]').not('.swal2-html-container *').first().trigger('click');
                            }, 100);
                        });

                        // Apply Resignation
                        modalContainer.find('.applyResignation').on('click', function(e) {
                            e.preventDefault();
                            var emp_id = $(this).data('emp_id');
                            var emp_name = $(this).data('emp_name');
                            var selected_reason = $(this).data('selected_reason');
                            var selected_reason_text = $(this).data('selected_reason_text');
                            Swal.close();
                            setTimeout(function() {
                                openResignationWizard(emp_id, emp_name, selected_reason, selected_reason_text);
                            }, 100);
                        });

                        // Apply Loan
                        modalContainer.find('.applyLoan').on('click', function(e) {
                            e.preventDefault();
                            var emp_id = $(this).data('emp_id');
                            var user_type = $(this).data('user_type');
                            Swal.close();
                            setTimeout(function() {
                                openLoanWizard(emp_id, user_type);
                            }, 100);
                        });

                        // Multiple Rejoin Request Handler
                        modalContainer.find('.submitMultipleRejoinRequest').on('click', function(e) {
                            e.preventDefault();
                            var empId = $(this).data('emp-id');
                            var empName = $(this).data('emp-name');
                            var totalVacations = $(this).data('total-vacations');
                            Swal.close();
                            setTimeout(function() {
                                handleMultipleRejoinProcess(empId, empName, totalVacations);
                            }, 100);
                        });

                        // Logout/Signout
                        modalContainer.find('.signout').on('click', function(e) {
                            e.preventDefault();
                            Swal.close();
                            // setTimeout(function() {
                            //     window.location.href = './includes/logout.php';
                            // }, 100);
                            setTimeout(function() {
                                $('.signout').trigger('click');
                            }, 100);
                        });
                    }
                });
            });

            // Copy to clipboard functionality (robust + works on mobile)
            $(document).on('click', '.copy-btn', function(e) {
                e.preventDefault();
                var $icon = $(this);
                var $container = $icon.closest('.info-value');
                // Get text content excluding the icon itself
                var text = $container.clone().find('.copy-btn').remove().end().text().trim();

                if (!text) return;

                function showSuccess() {
                    $icon.removeClass('fa-copy').addClass('fa-check').css('color', 'var(--success)');
                    setTimeout(function() {
                        $icon.removeClass('fa-check').addClass('fa-copy').removeAttr('style');
                    }, 2000);
                }

                function fallbackCopy(t) {
                    var $temp = $('<textarea>').css({
                        position: 'fixed',
                        left: '-9999px',
                        top: '-9999px'
                    }).val(t).appendTo('body');
                    $temp[0].focus();
                    $temp[0].select();
                    try {
                        document.execCommand('copy');
                        showSuccess();
                    } catch (err) {
                        console.error('Copy failed:', err);
                    } finally {
                        $temp.remove();
                    }
                }

                if (navigator.clipboard && window.isSecureContext) {
                    navigator.clipboard.writeText(text).then(showSuccess).catch(function() {
                        fallbackCopy(text);
                    });
                } else {
                    fallbackCopy(text);
                }
            });

            // Document Viewer Functionality for Profile Page
            var currentDocPath = '';

            // Use delegated event so dynamically added items work too
            $(document).on('click', '.doc-list-item', function() {
                // Remove active class from all items
                $('.doc-list-item').removeClass('active');
                // Add active class to clicked item
                $(this).addClass('active');

                // Get document details
                var docPath = $(this).data('doc-path');
                var docExt = $(this).data('doc-ext');
                var docName = $(this).data('doc-name');

                currentDocPath = docPath;

                // Show viewer container now that a file is selected
                $('.document-viewer-container').show();

                // Adjust columns: list -> col-md-5, viewer -> col-md-7
                var $listCol = $('.documents-list-container').closest('.col-12, .col-md-5');
                $listCol.removeClass('col-12').addClass('col-md-5');
                var $viewerCol = $('.document-viewer-container').closest('.col-md-7, .col-12');
                $viewerCol.removeClass('col-12').addClass('col-md-7');

                // Update viewer title
                $('#viewer-doc-name-profile').text(docName);

                // Show download button
                $('#viewer-download-profile').show().off('click').on('click', function() {
                    window.location.href = './downloadFile.php?file=' + docPath;
                });

                // Load document based on type
                var viewer = $('#document-viewer-profile');

                if (docExt === 'pdf') {
                    viewer.html('<embed src="' + docPath + '" type="application/pdf" width="100%" height="100%">');
                } else if (['jpg', 'jpeg', 'png', 'gif'].includes(docExt)) {
                    viewer.html('<img src="' + docPath + '" alt="' + docName + '" style="max-width: 100%; height: auto; display: block; margin: 0 auto;">');
                } else if (['doc', 'docx', 'xls', 'xlsx'].includes(docExt)) {
                    viewer.html('<iframe src="https://view.officeapps.live.com/op/embed.aspx?src=' + encodeURIComponent(window.location.origin + '/' + docPath) + '" width="100%" height="100%" frameborder="0"></iframe>');
                } else if (docExt === 'txt') {
                    $.get(docPath, function(data) {
                        viewer.html('<pre style="padding: 20px; white-space: pre-wrap; word-wrap: break-word;">' + $('<div>').text(data).html() + '</pre>');
                    });
                } else {
                    viewer.html('<div class="viewer-placeholder"><i class="fa fa-file" style="font-size: 64px; color: #ddd;"></i><p class="text-muted mt-3">Preview not available for this file type</p><a href="' + docPath + '" download class="btn btn-primary mt-3"><i class="fa fa-download"></i> Download File</a></div>');
                }
            });

            // Fullscreen toggle for profile viewer
            $('#viewer-fullscreen-profile').on('click', function() {
                var container = $('.document-viewer-container')[0];
                if (container.requestFullscreen) {
                    container.requestFullscreen();
                } else if (container.webkitRequestFullscreen) {
                    container.webkitRequestFullscreen();
                } else if (container.msRequestFullscreen) {
                    container.msRequestFullscreen();
                }
            });

            // Clear selection and hide viewer
            $('#viewer-clear-profile').on('click', function() {
                // Remove active state and hide download button
                $('.doc-list-item').removeClass('active');
                $('#viewer-download-profile').hide();

                // Reset title and body to placeholder
                $('#viewer-doc-name-profile').text('<?= __('select_document') ?>');
                $('#document-viewer-profile').html(`
                    <div class="viewer-placeholder">
                        <i class="fa fa-file-text" style="font-size: 64px; color: #ddd;"></i>
                        <p class="text-muted mt-3"><?= __('select_document_to_view') ?></p>
                    </div>
                `);

                // Hide the entire viewer container
                $('.document-viewer-container').hide();

                // Reset columns: list back to full width
                var $listCol = $('.documents-list-container').closest('.col-md-5, .col-12');
                $listCol.removeClass('col-md-5').addClass('col-12');
                var $viewerCol = $('.document-viewer-container').closest('.col-md-7, .col-12');
                $viewerCol.removeClass('col-md-7').addClass('col-12');
            });

            // Do not auto-load any document; wait for user selection
        });

        /**
         * Handle Multiple Vacation Rejoin Process
         * Shows all active vacations and forces sequential rejoin
         */
        function handleMultipleRejoinProcess(empId, empName, totalVacations) {
            // Show loading
            Swal.fire({
                title: '<?= __('loading') ?>',
                html: '<?= __('fetching_active_vacations', 'Fetching active vacations...') ?>',
                allowOutsideClick: false,
                didOpen: () => { Swal.showLoading(); }
            });

            // Get all active vacations
            $.ajax({
                url: './includes/ajaxFile/leaveHandler.php',
                type: 'POST',
                data: {
                    ajaxType: 'getAllActiveVacationsForRejoin',
                    emp_id: empId
                },
                dataType: 'json',
                success: function(response) {
                    if (response.ok && response.vacations && response.vacations.length > 0) {
                        showVacationsList(empId, empName, response.vacations);
                    } else {
                        Swal.fire({
                            icon: 'info',
                            title: '<?= __('no_active_vacations') ?>',
                            text: '<?= __('no_active_vacations_found', 'No active vacations found that require rejoin.') ?>',
                            confirmButtonText: '<?= __('ok') ?>'
                        });
                    }
                },
                error: function(xhr) {
                    var errorMsg = '<?= __('error_fetching_vacations', 'Error fetching vacations.') ?>';
                    try {
                        var response = JSON.parse(xhr.responseText);
                        if (response.message) errorMsg = response.message;
                    } catch(e) {}
                    
                    Swal.fire({
                        icon: 'error',
                        title: '<?= __('error') ?>',
                        text: errorMsg,
                        confirmButtonText: '<?= __('ok') ?>'
                    });
                }
            });
        }

        /**
         * Show all active vacations list with sequential rejoin enforcement
         */
        function showVacationsList(empId, empName, vacations) {
            const totalCount = vacations.length;
            const firstVacation = vacations[0]; // Must rejoin this one first
            
            // Build vacation cards HTML
            let vacationsHtml = '<div class=\"vacations-list-container\" style=\"max-height: 400px; overflow-y: auto; padding: 10px;\">';
            
            vacations.forEach((vac, index) => {
                const isFirst = (index === 0);
                const vacType = vac.fly_type ? `${vac.vac_type} (${vac.fly_type})` : vac.vac_type;
                const startDate = new Date(vac.start_date).toLocaleDateString();
                const returnDate = vac.return_date ? new Date(vac.return_date).toLocaleDateString() : 'N/A';
                
                // Check if rejoin request already submitted
                const hasRejoinRequest = vac.rejoin_request_id && vac.rejoin_status;
                const isRejoinPending = hasRejoinRequest && vac.rejoin_status === 'pending';
                const isRejoinApproved = hasRejoinRequest && vac.rejoin_status === 'approved';
                
                // Determine card styling based on status
                let borderColor = '#e9ecef';
                let backgroundColor = '#f8f9fa';
                let statusBadge = '';
                
                if (isFirst && !hasRejoinRequest) {
                    borderColor = '#ffc107';
                    backgroundColor = '#fffbf0';
                    statusBadge = '<div style=\"position: absolute; top: 10px; right: 10px; background: #ffc107; color: #000; padding: 4px 12px; border-radius: 4px; font-size: 11px; font-weight: bold;\">⚠️ REJOIN FIRST</div>';
                } else if (isRejoinPending) {
                    borderColor = '#17a2b8';
                    backgroundColor = '#e7f6f8';
                    statusBadge = '<div style=\"position: absolute; top: 10px; right: 10px; background: #17a2b8; color: #fff; padding: 4px 12px; border-radius: 4px; font-size: 11px; font-weight: bold;\">⏳ PENDING APPROVAL</div>';
                } else if (isRejoinApproved) {
                    borderColor = '#28a745';
                    backgroundColor = '#e8f5e9';
                    statusBadge = '<div style=\"position: absolute; top: 10px; right: 10px; background: #28a745; color: #fff; padding: 4px 12px; border-radius: 4px; font-size: 11px; font-weight: bold;\">✓ APPROVED</div>';
                }
                
                vacationsHtml += `
                    <div class=\"vacation-card\" style=\"
                        border: 2px solid ${borderColor}; 
                        border-radius: 8px; 
                        padding: 15px; 
                        margin-bottom: 10px;
                        background: ${backgroundColor};
                        position: relative;
                    \">
                        ${statusBadge}
                        <div style=\"display: flex; justify-content: space-between; align-items: start; margin-bottom: 10px;\">
                            <div>
                                <div style=\"font-weight: 600; font-size: 16px; color: #212529;\">#${index + 1} - ${vac.request_inv_no}</div>
                                <div style=\"color: #6c757d; font-size: 13px; margin-top: 4px;\">
                                    <i class=\"fa fa-calendar\"></i> ${vacType} 
                                    <span style=\"margin-left: 10px;\"><i class=\"fa fa-clock\"></i> ${vac.vacdays} ${vac.vacdays > 1 ? '<?= __('days') ?>' : '<?= __('day') ?>'}</span>
                                </div>
                            </div>
                        </div>
                        <div style=\"display: grid; grid-template-columns: 1fr 1fr; gap: 10px; font-size: 13px;\">
                            <div>
                                <div style=\"color: #6c757d; font-size: 11px; text-transform: uppercase; margin-bottom: 4px;\"><?= __('start_date') ?></div>
                                <div style=\"font-weight: 500;\">${startDate}</div>
                            </div>
                            <div>
                                <div style=\"color: #6c757d; font-size: 11px; text-transform: uppercase; margin-bottom: 4px;\"><?= __('return_date') ?></div>
                                <div style=\"font-weight: 500;\">${returnDate}</div>
                            </div>
                        </div>
                        ${isRejoinPending ? '<div style=\"margin-top: 10px; padding: 8px; background: #d1ecf1; border-radius: 4px; font-size: 12px; color: #0c5460;\"><i class=\"fa fa-hourglass-half\"></i> <?= __('rejoin_request_submitted_waiting', 'Rejoin request submitted and waiting for approval') ?></div>' : ''}
                        ${isRejoinApproved ? '<div style=\"margin-top: 10px; padding: 8px; background: #d4edda; border-radius: 4px; font-size: 12px; color: #155724;\"><i class=\"fa fa-check-circle\"></i> <?= __('rejoin_request_approved', 'Rejoin request has been approved') ?></div>' : ''}
                        ${!isFirst && !hasRejoinRequest ? '<div style=\"margin-top: 10px; padding: 8px; background: #fff3cd; border-radius: 4px; font-size: 12px; color: #856404;\"><i class=\"fa fa-lock\"></i> <?= __('locked_rejoin_first_vacation', 'Complete rejoin for previous vacation first') ?></div>' : ''}
                    </div>
                `;
            });
            
            vacationsHtml += '</div>';
            
            // Determine if we can show rejoin button
            const canRejoin = firstVacation && !firstVacation.rejoin_request_id;
            
            // Show the list with rejoin button for first vacation (if not already submitted)
            Swal.fire({
                title: `<i class=\"fa fa-plane-arrival\"></i> <?= __('active_vacations') ?>`,
                html: `
                    <div style=\"text-align: left; margin-bottom: 20px;\">
                        <div style=\"background: #e7f3ff; border-left: 4px solid #0066cc; padding: 12px; border-radius: 4px; margin-bottom: 15px;\">
                            <div style=\"font-weight: 600; color: #0066cc; margin-bottom: 4px;\">
                                <i class=\"fa fa-info-circle\"></i> <?= __('sequential_rejoin_required', 'Sequential Rejoin Required') ?>
                            </div>
                            <div style=\"font-size: 13px; color: #495057;\">
                                <?= __('rejoin_order_message', 'You have {count} active vacation(s). Please rejoin them in order starting from the oldest.') ?>
                            </div>
                        </div>
                        <div style=\"text-align: center; margin-bottom: 15px;\">
                            <div style=\"display: inline-block; background: #28a745; color: white; padding: 8px 16px; border-radius: 20px; font-size: 14px; font-weight: 600;\">
                                ${empName}
                            </div>
                        </div>
                    </div>
                    ${vacationsHtml}
                `.replace('{count}', totalCount),
                showCancelButton: true,
                confirmButtonText: canRejoin ? `<i class=\"fa fa-check\"></i> <?= __('rejoin_first_vacation', 'Rejoin First Vacation') ?>` : `<i class=\"fa fa-times\"></i> <?= __('close') ?>`,
                cancelButtonText: `<i class=\"fa fa-times\"></i> <?= __('cancel') ?>`,
                confirmButtonColor: canRejoin ? '#ffc107' : APP_COLORS.secondary,
                cancelButtonColor: APP_COLORS.secondary,
                showCancelButton: canRejoin,
                allowOutsideClick: false,
                width: '650px',
                customClass: {
                    confirmButton: canRejoin ? 'btn btn-warning' : 'btn btn-secondary',
                    cancelButton: 'btn btn-secondary'
                }
            }).then((result) => {
                if (result.isConfirmed && canRejoin) {
                    // Start rejoin process for first vacation
                    startRejoinProcess(firstVacation, empId, empName, totalCount);
                }
            });
        }

        /**
         * Start rejoin process for a specific vacation
         */
        function startRejoinProcess(vacation, empId, empName, remainingCount) {
            const vacType = vacation.fly_type ? `${vacation.vac_type} (${vacation.fly_type})` : vacation.vac_type;
            const returnDate = vacation.returndate || vacation.return_date || vacation.returnDate || '';
            const todayIso = new Date().toISOString().split('T')[0];
            const normalizedReturn = returnDate ? moment(returnDate).format('YYYY-MM-DD') : '';
            const defaultRejoinDate = normalizedReturn || todayIso;
            
            Swal.fire({
                title: `<i class=\"fa fa-plane-arrival\"></i> <?= __('rejoin_request') ?>`,
                html: `
                    <div style=\"text-align: left;\">
                        <div style=\"background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 20px;\">
                            <div style=\"display: grid; grid-template-columns: 1fr 1fr; gap: 10px; font-size: 13px;\">
                                <div>
                                    <div style=\"color: #6c757d; font-size: 11px; text-transform: uppercase; margin-bottom: 4px;\"><?= __('request_number') ?></div>
                                    <div style=\"font-weight: 600;\">${vacation.request_inv_no}</div>
                                </div>
                                <div>
                                    <div style=\"color: #6c757d; font-size: 11px; text-transform: uppercase; margin-bottom: 4px;\"><?= __('vacation_type') ?></div>
                                    <div style=\"font-weight: 600;\">${vacType}</div>
                                </div>
                                <div>
                                    <div style=\"color: #6c757d; font-size: 11px; text-transform: uppercase; margin-bottom: 4px;\"><?= __('days') ?></div>
                                    <div style=\"font-weight: 600;\">${vacation.vacdays}</div>
                                </div>
                                <div>
                                    <div style=\"color: #6c757d; font-size: 11px; text-transform: uppercase; margin-bottom: 4px;\"><?= __('expected_return') ?></div>
                                    <div style=\"font-weight: 600;\">${returnDate || 'N/A'}</div>
                                </div>
                            </div>
                        </div>
                        
                        ${remainingCount > 1 ? `
                            <div style=\"background: #fff3cd; border-left: 4px solid #ffc107; padding: 12px; border-radius: 4px; margin-bottom: 20px;\">
                                <div style=\"font-size: 13px; color: #856404;\">
                                    <i class=\"fa fa-exclamation-triangle\"></i> 
                                    <?= __('more_vacations_pending', 'You have {count} more vacation(s) pending after this one.') ?>
                                </div>
                            </div>
                        `.replace('{count}', remainingCount - 1) : ''}
                        
                        <div class="form-group">
                            <label for="rejoinDate" style="font-weight: 600; color: #212529; margin-bottom: 8px; display: block;">
                                <i class="fa fa-calendar"></i> <?= __('actual_rejoin_date') ?> <span style="color: red;">*</span>
                            </label>
                            <input type="text" id="rejoinDate" class="form-control" value="${defaultRejoinDate}" placeholder="YYYY-MM-DD" required style="width: 100%; padding: 8px; border: 1px solid #ced4da; border-radius: 4px;">
                            <small class="text-muted" style="display:block; margin-top:6px;">
                                <?= __('expected_return') ?>: ${normalizedReturn || '—'}
                            </small>
                        </div>
                        
                        <div class=\"form-group\" style=\"margin-top: 15px;\">
                            <label for=\"rejoinReason\" style=\"font-weight: 600; color: #212529; margin-bottom: 8px; display: block;\">
                                <i class=\"fa fa-comment\"></i> <?= __('reason_notes') ?>
                            </label>
                            <textarea id=\"rejoinReason\" class=\"form-control\" rows=\"3\" placeholder=\"<?= __('optional_notes', 'Optional notes...') ?>\" style=\"width: 100%; padding: 8px; border: 1px solid #ced4da; border-radius: 4px;\"></textarea>
                        </div>
                    </div>
                `,
                showCancelButton: true,
                confirmButtonText: `<i class=\"fa fa-paper-plane\"></i> <?= __('submit_rejoin') ?>`,
                cancelButtonText: `<i class=\"fa fa-times\"></i> <?= __('cancel') ?>`,
                confirmButtonColor: APP_COLORS.success,
                cancelButtonColor: APP_COLORS.secondary,
                allowOutsideClick: false,
                width: '600px',
                didOpen: () => {
                    const $rejoin = $('#rejoinDate');
                    $rejoin.datepicker({
                        format: 'yyyy-mm-dd',
                        autoclose: true,
                        todayHighlight: true
                    }).datepicker('update', defaultRejoinDate);
                },
                preConfirm: () => {
                    const rejoinDate = document.getElementById('rejoinDate').value;
                    const rejoinReason = document.getElementById('rejoinReason').value;
                    
                    if (!rejoinDate) {
                        Swal.showValidationMessage('<?= __('rejoin_date_required', 'Please select actual rejoin date') ?>');
                        return false;
                    }
                    
                    return { rejoinDate, rejoinReason };
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    submitRejoinRequest(vacation.id, result.value.rejoinDate, result.value.rejoinReason, empId, empName, remainingCount);
                }
            });
        }

        /**
         * Submit rejoin request via AJAX
         */
        function submitRejoinRequest(vacationId, rejoinDate, rejoinReason, empId, empName, remainingCount) {
            Swal.fire({
                title: '<?= __('submitting') ?>',
                html: '<?= __('please_wait') ?>',
                allowOutsideClick: false,
                didOpen: () => { Swal.showLoading(); }
            });

            $.ajax({
                url: './includes/ajaxFile/leaveHandler.php',
                type: 'POST',
                data: {
                    ajaxType: 'submitRejoinRequest',
                    vacation_id: vacationId,
                    emp_id: empId,
                    rejoin_date: rejoinDate,
                    rejoin_reason: rejoinReason
                },
                dataType: 'json',
                success: function(response) {
                    if (response.ok || response.type === 'success') {
                        const isLastVacation = (remainingCount <= 1);
                        
                        Swal.fire({
                            icon: 'success',
                            title: '<?= __('rejoin_submitted') ?>',
                            html: response.message || '<?= __('rejoin_request_submitted_successfully') ?>',
                            confirmButtonText: isLastVacation ? '<?= __('done') ?>' : '<?= __('next_vacation', 'Next Vacation') ?>',
                            confirmButtonColor: isLastVacation ? APP_COLORS.success : APP_COLORS.warning,
                            allowOutsideClick: false
                        }).then(() => {
                            if (!isLastVacation) {
                                // Reload the list to show next vacation
                                setTimeout(() => {
                                    handleMultipleRejoinProcess(empId, empName, remainingCount - 1);
                                }, 300);
                            } else {
                                // All done - reload page
                                window.location.reload();
                            }
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: '<?= __('error') ?>',
                            text: response.message || '<?= __('rejoin_submission_failed') ?>',
                            confirmButtonText: '<?= __('ok') ?>',
                            confirmButtonColor: APP_COLORS.danger
                        });
                    }
                },
                error: function(xhr) {
                    var errorMsg = '<?= __('rejoin_submission_error', 'Error submitting rejoin request.') ?>';
                    try {
                        var response = JSON.parse(xhr.responseText);
                        if (response.message) errorMsg = response.message;
                    } catch(e) {}
                    
                    Swal.fire({
                        icon: 'error',
                        title: '<?= __('error') ?>',
                        text: errorMsg,
                        confirmButtonText: '<?= __('ok') ?>'
                    });
                }
            });
        }
    </script>
</body>

</html>