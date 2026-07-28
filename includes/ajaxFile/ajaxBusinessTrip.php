<?php
/**
 * Business Trip Request AJAX Handler
 * Handles submission, approval, and rejection of business trip requests
 * 
 * Features:
 * - Multi-level approval workflow based on app_settings configuration
 * - Domestic (own car/rental) and International trip types
 * - City selection from saudi_cities table
 * - Email notifications to approvers
 * - Browser notifications
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../../includes/session_check.php';
include("./../../includes/validate_supervisor.php");

if (session_status() == PHP_SESSION_NONE) session_start();
$current_user_id = $_SESSION['empid'] ?? 0;
$userwel = $_SESSION['userwel'] ?? 'System';

function getBusinessTripRequestTypeId($conDB)
{
    $type_stmt = mysqli_prepare($conDB, "SELECT id FROM approval_request_types WHERE type_name = 'business_trip' LIMIT 1");
    if ($type_stmt) {
        mysqli_stmt_execute($type_stmt);
        $type_res = mysqli_stmt_get_result($type_stmt);
        if ($type_res && ($type_row = mysqli_fetch_assoc($type_res))) {
            $resolvedId = (int)($type_row['id'] ?? 0);
            mysqli_free_result($type_res);
            mysqli_stmt_close($type_stmt);
            return ($resolvedId > 0) ? $resolvedId : 0;
        }
        if ($type_res) mysqli_free_result($type_res);
        mysqli_stmt_close($type_stmt);
    }
    return 0;
}

function getIsRtlFlag()
{
    if (isset($GLOBALS['is_rtl'])) {
        return (bool)$GLOBALS['is_rtl'];
    }

    if (isset($GLOBALS['current_lang'])) {
        return ((string)$GLOBALS['current_lang'] === 'ar');
    }

    if (isset($_SESSION['lang'])) {
        return ((string)$_SESSION['lang'] === 'ar');
    }

    if (isset($_POST['is_rtl'])) {
        return ($_POST['is_rtl'] == 'true');
    }

    return false;
}

function ensureBusinessTripAllowancesTable($conDB)
{
    // Validation only: DB schema is managed manually/migration scripts.
    $requiredTables = ['emp_business_trip_allowances', 'emp_business_trip_allowance_items'];
    foreach ($requiredTables as $tableName) {
        $tblSql = "SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? LIMIT 1";
        $tblStmt = mysqli_prepare($conDB, $tblSql);
        if (!$tblStmt) {
            throw new Exception('Failed to validate allowance tables: ' . mysqli_error($conDB));
        }
        mysqli_stmt_bind_param($tblStmt, 's', $tableName);
        mysqli_stmt_execute($tblStmt);
        $tblRes = mysqli_stmt_get_result($tblStmt);
        $tableExists = ($tblRes && mysqli_num_rows($tblRes) > 0);
        mysqli_stmt_close($tblStmt);

        if (!$tableExists) {
            throw new Exception('Required table is missing: ' . $tableName . '. Please run db/create_emp_business_trip_allowances.sql');
        }
    }

    $requiredHeadColumns = [
        'trip_id', 'request_inv_no', 'emp_id', 'ticket_fares', 'other_allowance',
        'allowance_days', 'total_allowance', 'notes', 'created_by', 'updated_by'
    ];

    foreach ($requiredHeadColumns as $columnName) {
        $colSql = "SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'emp_business_trip_allowances' AND COLUMN_NAME = ? LIMIT 1";
        $colStmt = mysqli_prepare($conDB, $colSql);
        if (!$colStmt) {
            throw new Exception('Failed to validate allowance table columns: ' . mysqli_error($conDB));
        }
        mysqli_stmt_bind_param($colStmt, 's', $columnName);
        mysqli_stmt_execute($colStmt);
        $colRes = mysqli_stmt_get_result($colStmt);
        $columnExists = ($colRes && mysqli_num_rows($colRes) > 0);
        mysqli_stmt_close($colStmt);

        if (!$columnExists) {
            throw new Exception('Missing column in emp_business_trip_allowances: ' . $columnName);
        }
    }
}

function normalizeAllowanceType($rawType)
{
    $type = strtolower(trim((string)$rawType));
    $map = [
        'ticket' => 'ticket_fares',
        'ticket_fares' => 'ticket_fares',
        'hotel' => 'hotel_allowance',
        'hotel_allowance' => 'hotel_allowance',
        'transport' => 'transportation_allowance',
        'transportation' => 'transportation_allowance',
        'transportation_allowance' => 'transportation_allowance',
        'others' => 'others',
        'other' => 'others',
        'other_allowance' => 'others'
    ];
    return $map[$type] ?? 'others';
}

function calculateTripAllowanceDays($startDate, $endDate)
{
    try {
        $startDate = trim((string)$startDate);
        $endDate = trim((string)$endDate);
        if ($startDate === '' || $endDate === '') {
            return 0;
        }

        $start = new DateTime($startDate);
        $end = new DateTime($endDate);
        if ($end < $start) {
            return 0;
        }

        return (int)$start->diff($end)->format('%a') + 1;
    } catch (Exception $e) {
        return 0;
    }
}

function isCurrentUserHRPayroll()
{
    $userType = strtolower((string)($GLOBALS['user_type'] ?? ($_SESSION['user_type'] ?? '')));
    return (strpos($userType, 'hr_payroll') !== false);
}

/**
 * Generate unique Business Trip Request ID
 * Format: BT-YYYYMMDDHHMMSS-EMPID-RND
 */
function generateBusinessTripRequestID($conDB, $emp_id)
{
    $safeEmpId = preg_replace('/[^A-Za-z0-9]/', '', (string)$emp_id);
    $safeEmpId = ($safeEmpId !== '') ? $safeEmpId : 'EMP';

    $max_attempts = 5;
    $attempt = 0;

    while ($attempt < $max_attempts) {
        $request_inv_no_candidate = sprintf(
            'BT-%s-%s-%s',
            date('YmdHis'),
            $safeEmpId,
            substr(bin2hex(random_bytes(4)), 0, 4)
        );

        $exists_stmt = mysqli_prepare($conDB, "SELECT id FROM emp_business_trip WHERE request_inv_no = ? LIMIT 1");
        if ($exists_stmt) {
            mysqli_stmt_bind_param($exists_stmt, "s", $request_inv_no_candidate);
            mysqli_stmt_execute($exists_stmt);
            $exists_res = mysqli_stmt_get_result($exists_stmt);
            $exists = ($exists_res && mysqli_num_rows($exists_res) > 0);
            if ($exists_res) {
                mysqli_free_result($exists_res);
            }
            mysqli_stmt_close($exists_stmt);

            if (!$exists) {
                return $request_inv_no_candidate;
            }
        } else {
            return $request_inv_no_candidate;
        }

        $attempt++;
    }

    throw new Exception('Unable to generate unique business trip request ID. Please try again.');
}

/**
 * Get Saudi cities for dropdown
 */
if (isset($_POST['ajaxType']) && $_POST['ajaxType'] === 'getSaudiCities') {
    try {
        $is_rtl = getIsRtlFlag();
        $cityColumn = $is_rtl ? 'name_ar' : 'name_en';
        
        $query = mysqli_query($conDB, "SELECT id, $cityColumn as city_name FROM saudi_cities ORDER BY $cityColumn ASC");
        $cities = [];
        
        if ($query) {
            while ($row = mysqli_fetch_assoc($query)) {
                $cities[] = [
                    'id' => (int)$row['id'],
                    'name' => $row['city_name']
                ];
            }
            mysqli_free_result($query);
        }
        
        echo json_encode(['status' => 'success', 'cities' => $cities]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Failed to load cities: ' . $e->getMessage()]);
    }
    exit;
}

/**
 * Get Countries for international destination dropdown
 */
if (isset($_POST['ajaxType']) && $_POST['ajaxType'] === 'getCountries') {
    try {
        $is_rtl = getIsRtlFlag();
        $countryColumn = $is_rtl ? 'name_ar' : 'name';

        $query = mysqli_query($conDB, "SELECT id, $countryColumn as country_name FROM countries ORDER BY $countryColumn ASC");
        $countries = [];

        if ($query) {
            while ($row = mysqli_fetch_assoc($query)) {
                $countries[] = [
                    'id' => (int)$row['id'],
                    'name' => $row['country_name']
                ];
            }
            mysqli_free_result($query);
        }

        echo json_encode(['status' => 'success', 'countries' => $countries]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Failed to load countries: ' . $e->getMessage()]);
    }
    exit;
}

/**
 * Check passport eligibility for international business trip
 * - Passport expiry must be more than 3 months
 * - Passport attachment must exist in emp_docu.docu_typ, otherwise upload is required
 */
if (isset($_POST['ajaxType']) && $_POST['ajaxType'] === 'checkPassportEligibility') {
    try {
        $emp_id = trim((string)($_POST['emp_id'] ?? ''));
        if ($emp_id === '') {
            echo json_encode([
                'status' => 'error',
                'message' => 'Invalid employee id.'
            ]);
            exit;
        }

        $emp_id_esc = escape_string($emp_id);

        $passport_exp_raw = '';
        $emp_query = mysqli_query($conDB, "SELECT passport_exp FROM employees WHERE emp_id = '{$emp_id_esc}' LIMIT 1");
        if ($emp_query && ($emp_row = mysqli_fetch_assoc($emp_query))) {
            $passport_exp_raw = trim((string)($emp_row['passport_exp'] ?? ''));
            mysqli_free_result($emp_query);
        }

        $passport_exp_date = null;
        if ($passport_exp_raw !== '') {
            $formats = ['Y-m-d', 'd-m-Y', 'd/m/Y', 'm/d/Y', 'Y/m/d'];
            foreach ($formats as $format) {
                $dt = DateTime::createFromFormat($format, $passport_exp_raw);
                if ($dt instanceof DateTime) {
                    $passport_exp_date = $dt;
                    break;
                }
            }

            if (!$passport_exp_date) {
                $ts = strtotime($passport_exp_raw);
                if ($ts !== false) {
                    $passport_exp_date = new DateTime(date('Y-m-d', $ts));
                }
            }
        }

        $min_valid_date = new DateTime('today');
        $min_valid_date->modify('+3 months');
        $passport_valid = ($passport_exp_date instanceof DateTime) && ($passport_exp_date > $min_valid_date);

        $has_attachment = false;
        $doc_query = mysqli_query($conDB, "SELECT COUNT(*) AS total FROM emp_docu WHERE emp_id = '{$emp_id_esc}' AND path <> '' AND (status IS NULL OR status = 'A') AND LOWER(docu_typ) LIKE '%passport%'");
        if ($doc_query && ($doc_row = mysqli_fetch_assoc($doc_query))) {
            $has_attachment = ((int)$doc_row['total'] > 0);
            mysqli_free_result($doc_query);
        }

        $requires_upload = !$has_attachment;

        $message = '';
        if (!$passport_valid) {
            $message = __('passport_valid_message');
        } else if ($requires_upload) {
            $message = __('no_passport_attachment_message');
        } else {
            $message = __('passport_eligibility_check_passed');
        }

        echo json_encode([
            'status' => 'success',
            'passport_valid' => $passport_valid,
            'has_attachment' => $has_attachment,
            'requires_upload' => $requires_upload,
            'passport_exp' => $passport_exp_raw,
            'message' => $message
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Failed to check passport eligibility: ' . $e->getMessage()
        ]);
    }
    exit;
}

/**
 * Check active business trip request before opening apply form
 * If active request exists, return applied information + approval chain
 */
if (isset($_POST['ajaxType']) && $_POST['ajaxType'] === 'checkActiveBusinessTrip') {
    try {
        $emp_id = trim((string)($_POST['emp_id'] ?? ''));
        $is_rtl = getIsRtlFlag();
        $cityColumn = $is_rtl ? 'name_ar' : 'name_en';
        if ($emp_id === '') {
            echo json_encode([
                'status' => 'error',
                'message' => 'Invalid employee id.'
            ]);
            exit;
        }

        $existing_request = null;
        $active_req_stmt = mysqli_prepare($conDB, "SELECT 
            `bt`.`id`, 
            `bt`.`request_inv_no`, 
            `bt`.`trip_type`, 
            `bt`.`trip_start_date`, 
            `bt`.`trip_end_date`, 
            `bt`.`from_city_id`, 
            `bt`.`to_city_id`, 
            `bt`.`destination_country`, 
            `bt`.`current_status`, 
            `bt`.`created_at`, 
            `fc`." . $cityColumn . " AS `from_city_route_name`, 
            `tc`." . $cityColumn . " AS `to_city_route_name` 
            FROM `emp_business_trip` `bt` 
            LEFT JOIN `saudi_cities` `fc` ON `bt`.`from_city_id` = `fc`.`id` 
            LEFT JOIN `saudi_cities` `tc` ON `bt`.`to_city_id` = `tc`.`id` 
            WHERE `bt`.`emp_id` = ? AND `bt`.`current_status` NOT IN ('completed','rejected','cancelled') 
            ORDER BY `bt`.`id` DESC LIMIT 1");
        if ($active_req_stmt) {
            mysqli_stmt_bind_param($active_req_stmt, "s", $emp_id);
            mysqli_stmt_execute($active_req_stmt);
            $active_res = mysqli_stmt_get_result($active_req_stmt);
            if ($active_res && ($active_row = mysqli_fetch_assoc($active_res))) {
                $existing_request = $active_row;
            }
            if ($active_res) mysqli_free_result($active_res);
            mysqli_stmt_close($active_req_stmt);
        }

        if (!$existing_request) {
            echo json_encode([
                'status' => 'success',
                'has_active_request' => false
            ]);
            exit;
        }

        $approval_chain = [];
        $chain_stmt = mysqli_prepare($conDB, "SELECT 
            `ra`.`approval_level`, 
            `ra`.`status`, 
            `ra`.`approver_id`, 
            `e`.`name` AS `approver_name` 
            FROM `request_approvers` `ra` 
            LEFT JOIN `employees` `e` ON `e`.`emp_id` = `ra`.`approver_id` 
            WHERE `ra`.`request_inv_no` = ? 
            ORDER BY `ra`.`approval_level` ASC, `ra`.`id` ASC");
        if ($chain_stmt) {
            $existing_inv = (string)$existing_request['request_inv_no'];
            mysqli_stmt_bind_param($chain_stmt, "s", $existing_inv);
            mysqli_stmt_execute($chain_stmt);
            $chain_res = mysqli_stmt_get_result($chain_stmt);
            if ($chain_res) {
                while ($chain_row = mysqli_fetch_assoc($chain_res)) {
                    $approval_chain[] = [
                        'level' => (int)($chain_row['approval_level'] ?? 0),
                        'approver_id' => (int)($chain_row['approver_id'] ?? 0),
                        'approver_name' => (string)(getDisplayName(parseName($chain_row['approver_name'])) ?? ''),
                        'status' => (string)($chain_row['status'] ?? '')
                    ];
                }
                mysqli_free_result($chain_res);
            }
            mysqli_stmt_close($chain_stmt);
        }

        $route_text = '';
        if (($existing_request['trip_type'] ?? '') === 'international') {
            $route_text = 'Destination Country: ' . (string)($existing_request['destination_country'] ?? '');
        } else {
            $fromRouteName = trim((string)($existing_request['from_city_route_name'] ?? ''));
            $toRouteName = trim((string)($existing_request['to_city_route_name'] ?? ''));
            if ($fromRouteName !== '' || $toRouteName !== '') {
                $route_text = 'Route: ' . ($fromRouteName !== '' ? $fromRouteName : ('#' . (string)($existing_request['from_city_id'] ?? ''))) . ' -> ' . ($toRouteName !== '' ? $toRouteName : ('#' . (string)($existing_request['to_city_id'] ?? '')));
            } else {
                $route_text = 'Route (City IDs): ' . (string)($existing_request['from_city_id'] ?? '') . ' -> ' . (string)($existing_request['to_city_id'] ?? '');
            }
        }

        $chain_html = '';
        if (!empty($approval_chain)) {
            $chain_html = '<ul style="text-align:left;margin:8px 0 0 18px;">';
            foreach ($approval_chain as $c) {
                $line = 'Level ' . $c['level'] . ': ' . ($c['approver_name'] !== '' ? $c['approver_name'] : ('Emp#' . $c['approver_id'])) . ' (' . $c['status'] . ')';
                $chain_html .= '<li>' . htmlspecialchars($line, ENT_QUOTES, 'UTF-8') . '</li>';
            }
            $chain_html .= '</ul>';
        }

        $summary_html =
            '<div style="text-align:left;">'
            . '<p style="margin:0 0 8px 0;">' . __('you_already_have_active_business_trip') . '</p>'
            . '<p style="margin:0 0 6px 0;"><strong>' . __('applied_information') . '</strong></p>'
            . '<ul style="margin:0 0 8px 18px;">'
            . '<li>Request ID: ' . htmlspecialchars((string)$existing_request['request_inv_no'], ENT_QUOTES, 'UTF-8') . '</li>'
            . '<li>Status: ' . htmlspecialchars((string)$existing_request['current_status'], ENT_QUOTES, 'UTF-8') . '</li>'
            . '<li>Trip Type: ' . htmlspecialchars((string)$existing_request['trip_type'], ENT_QUOTES, 'UTF-8') . '</li>'
            . '<li>Trip Dates: ' . htmlspecialchars((string)$existing_request['trip_start_date'], ENT_QUOTES, 'UTF-8') . ' to ' . htmlspecialchars((string)$existing_request['trip_end_date'], ENT_QUOTES, 'UTF-8') . '</li>'
            . '<li>' . htmlspecialchars($route_text, ENT_QUOTES, 'UTF-8') . '</li>'
            . '</ul>'
            . (!empty($approval_chain) ? '<p style="margin:0 0 6px 0;"><strong>' . __('request_chain') . '</strong></p>' . $chain_html : '')
            . '</div>';

        echo json_encode([
            'status' => 'success',
            'has_active_request' => true,
            'title' => __('active_request_exists'),
            'message' => __('you_already_have_active_business_trip'),
            'html' => $summary_html,
            'type' => 'warning',
            'existing_request' => $existing_request,
            'approval_chain' => $approval_chain
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Failed to check active business trip request: ' . $e->getMessage()
        ]);
    }
    exit;
}

/**
 * Approve business trip request
 */
if (isset($_POST['ajaxType']) && $_POST['ajaxType'] === 'approveBusinessTrip') {
    try {
        $request_type_id = getBusinessTripRequestTypeId($conDB);
        if ($request_type_id <= 0) {
            throw new Exception('Business trip request type is not configured in approval_request_types.');
        }
        $trip_id = trim((string)($_POST['trip_id'] ?? ''));
        $approval_comment = trim((string)($_POST['approval_comment'] ?? 'Approved'));
        if ($trip_id === '') {
            echo json_encode([
                'status' => 'error',
                'title' => 'Invalid Request',
                'message' => 'Invalid business trip request id.',
                'type' => 'error'
            ]);
            exit;
        }

        $request_inv_no = $trip_id;
        $trip_status = '';
        $trip_emp_id = '';
        $trip_type = '';
        $trip_start_date = '';
        $trip_end_date = '';
        
        $trip_stmt = mysqli_prepare($conDB, "SELECT 
            current_status, emp_id, trip_type, trip_start_date, trip_end_date, 
            from_city_id, to_city_id, destination_country,
            fc.name_en as from_city_name_en, fc.name_ar as from_city_name_ar,
            tc.name_en as to_city_name_en, tc.name_ar as to_city_name_ar
            FROM emp_business_trip bt
            LEFT JOIN saudi_cities fc ON bt.from_city_id = fc.id
            LEFT JOIN saudi_cities tc ON bt.to_city_id = tc.id
            WHERE bt.request_inv_no = ? LIMIT 1");
        if ($trip_stmt) {
            mysqli_stmt_bind_param($trip_stmt, 's', $request_inv_no);
            mysqli_stmt_execute($trip_stmt);
            $trip_res = mysqli_stmt_get_result($trip_stmt);
            if ($trip_res && ($trip_row = mysqli_fetch_assoc($trip_res))) {
                $trip_status = (string)($trip_row['current_status'] ?? '');
                $trip_emp_id = (string)($trip_row['emp_id'] ?? '');
                $trip_type = (string)($trip_row['trip_type'] ?? '');
                $trip_start_date = (string)($trip_row['trip_start_date'] ?? '');
                $trip_end_date = (string)($trip_row['trip_end_date'] ?? '');
            }
            if ($trip_res) mysqli_free_result($trip_res);
            mysqli_stmt_close($trip_stmt);
        }

        if ($trip_status === '') {
            echo json_encode([
                'status' => 'error',
                'title' => 'Not Found',
                'message' => 'Business trip request not found.',
                'type' => 'error'
            ]);
            exit;
        }

        if (in_array($trip_status, ['approved', 'rejected', 'completed', 'cancelled'], true)) {
            echo json_encode([
                'status' => 'error',
                'title' => 'Invalid Status',
                'message' => 'This request is already finalized and cannot be approved again.',
                'type' => 'warning'
            ]);
            exit;
        }

        $pending_stmt = mysqli_prepare($conDB, "SELECT id FROM request_approvers WHERE request_inv_no = ? AND request_type_id = ? AND approver_id = ? AND status = 'pending' LIMIT 1");
        $has_pending_access = false;
        if ($pending_stmt) {
            mysqli_stmt_bind_param($pending_stmt, 'sii', $request_inv_no, $request_type_id, $current_user_id);
            mysqli_stmt_execute($pending_stmt);
            $pending_res = mysqli_stmt_get_result($pending_stmt);
            $has_pending_access = ($pending_res && mysqli_num_rows($pending_res) > 0);
            if ($pending_res) mysqli_free_result($pending_res);
            mysqli_stmt_close($pending_stmt);
        }

        if (!$has_pending_access) {
            echo json_encode([
                'status' => 'error',
                'title' => 'Access Denied',
                'message' => 'This request is not pending your approval.',
                'type' => 'error'
            ]);
            exit;
        }

        $approval_result = handle_approval_action(
            $conDB,
            $request_inv_no,
            'business_trip',
            (int)$current_user_id,
            'approve',
            ($approval_comment !== '' ? $approval_comment : 'Approved')
        );

        if (($approval_result['status'] ?? 'error') === 'error') {
            throw new Exception((string)($approval_result['message'] ?? 'Approval failed.'));
        }

        if (function_exists('save_approval_comment_db') && $approval_comment !== '') {
            save_approval_comment_db(
                $conDB,
                $request_inv_no,
                'business_trip',
                'approved',
                (int)$current_user_id,
                (string)$userwel,
                $approval_comment
            );
        }

        if (class_exists('ActivityLogger')) {
            ActivityLogger::logApproval(
                'Business Trip',
                'ajaxBusinessTrip.php',
                $trip_id,
                'approved',
                "Approved business trip request: {$request_inv_no}",
                'emp_business_trip'
            );
        }

        echo json_encode([
            'status' => 'success',
            'title' => 'Approved',
            'message' => 'Business trip request approved successfully.',
            'type' => 'success',
            'request_inv_no' => $request_inv_no,
            'employee_id' => $trip_emp_id
        ]);
    } catch (Exception $e) {
        error_log('Business Trip Approval Error: ' . $e->getMessage());
        echo json_encode([
            'status' => 'error',
            'title' => 'Approval Failed',
            'message' => 'An error occurred while approving request: ' . $e->getMessage(),
            'type' => 'error'
        ]);
    }
    exit;
}

/**
 * Reject business trip request
 */
if (isset($_POST['ajaxType']) && $_POST['ajaxType'] === 'cancelBusinessTripAdmin') {
    try {
        require_once __DIR__ . '/../special_access_helper.php';

        $can_cancel_any = (
            !empty($is_system_admin)
            || user_has_special_access($conDB, $current_user_id, 'cancel_business_trip_requests', $user_role ?? '', $user_type ?? '', $is_system_admin ?? false)
        );
        if (!$can_cancel_any) {
            echo json_encode(['status' => 'error', 'title' => 'Access Denied', 'message' => __('access_denied', 'Access denied'), 'type' => 'error']);
            exit;
        }

        $trip_id = trim((string)($_POST['trip_id'] ?? ''));
        $cancellation_note = trim((string)($_POST['cancellation_note'] ?? ''));
        if ($trip_id === '') {
            echo json_encode(['status' => 'error', 'title' => 'Invalid Request', 'message' => 'Invalid business trip request id.', 'type' => 'error']);
            exit;
        }

        $trip_stmt = mysqli_prepare($conDB, "SELECT current_status, emp_id FROM emp_business_trip WHERE request_inv_no = ? LIMIT 1");
        mysqli_stmt_bind_param($trip_stmt, 's', $trip_id);
        mysqli_stmt_execute($trip_stmt);
        $trip_res = mysqli_stmt_get_result($trip_stmt);
        $trip_row = $trip_res ? mysqli_fetch_assoc($trip_res) : null;
        mysqli_stmt_close($trip_stmt);

        if (!$trip_row) {
            echo json_encode(['status' => 'error', 'title' => 'Not Found', 'message' => 'Business trip request not found.', 'type' => 'error']);
            exit;
        }

        if (in_array($trip_row['current_status'], ['rejected', 'completed', 'cancelled'], true)) {
            echo json_encode(['status' => 'error', 'title' => 'Invalid Status', 'message' => 'This request in status "' . $trip_row['current_status'] . '" cannot be cancelled.', 'type' => 'warning']);
            exit;
        }

        $update_stmt = mysqli_prepare($conDB, "UPDATE emp_business_trip SET current_status = 'cancelled', modified_by = ?, last_modified = NOW() WHERE request_inv_no = ? AND current_status = ?");
        mysqli_stmt_bind_param($update_stmt, 'iss', $current_user_id, $trip_id, $trip_row['current_status']);
        mysqli_stmt_execute($update_stmt);
        $affected = mysqli_stmt_affected_rows($update_stmt);
        mysqli_stmt_close($update_stmt);

        if ($affected <= 0) {
            echo json_encode(['status' => 'error', 'title' => 'Error', 'message' => 'Failed to cancel request - status may have changed.', 'type' => 'error']);
            exit;
        }

        $ra_stmt = mysqli_prepare($conDB, "UPDATE request_approvers ra JOIN approval_request_types art ON art.id = ra.request_type_id AND art.type_name = 'business_trip' SET ra.status = 'cancelled' WHERE ra.request_inv_no = ? AND ra.status IN ('pending', 'awaiting')");
        mysqli_stmt_bind_param($ra_stmt, 's', $trip_id);
        mysqli_stmt_execute($ra_stmt);
        mysqli_stmt_close($ra_stmt);

        if (function_exists('create_browser_notification')) {
            create_browser_notification(
                $conDB,
                $trip_row['emp_id'],
                'Business Trip Request Cancelled',
                'Your business trip request ' . htmlspecialchars($trip_id) . ' was cancelled by an administrator.' . ($cancellation_note !== '' ? ' Reason: ' . $cancellation_note : ''),
                'business_trip_status_history.php?inv_no=' . urlencode($trip_id)
            );
        }

        echo json_encode(['status' => 'success', 'title' => 'Cancelled', 'message' => 'Business trip request has been cancelled successfully.', 'type' => 'success']);
        exit;
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'title' => 'Error', 'message' => $e->getMessage(), 'type' => 'error']);
        exit;
    }
}

// Purpose: Allow an employee to cancel their OWN business trip request while it's
// still pending or approved (not yet completed/rejected/cancelled).
if (isset($_POST['ajaxType']) && $_POST['ajaxType'] === 'cancelBusinessTripSelf') {
    try {
        $trip_id = trim((string)($_POST['trip_id'] ?? ''));
        if ($trip_id === '') {
            echo json_encode(['status' => 'error', 'title' => 'Invalid Request', 'message' => 'Invalid business trip request id.', 'type' => 'error']);
            exit;
        }

        $trip_stmt = mysqli_prepare($conDB, "SELECT current_status, emp_id FROM emp_business_trip WHERE request_inv_no = ? LIMIT 1");
        mysqli_stmt_bind_param($trip_stmt, 's', $trip_id);
        mysqli_stmt_execute($trip_stmt);
        $trip_res = mysqli_stmt_get_result($trip_stmt);
        $trip_row = $trip_res ? mysqli_fetch_assoc($trip_res) : null;
        mysqli_stmt_close($trip_stmt);

        if (!$trip_row) {
            echo json_encode(['status' => 'error', 'title' => 'Not Found', 'message' => 'Business trip request not found.', 'type' => 'error']);
            exit;
        }

        if ((string)$trip_row['emp_id'] !== (string)$current_user_id) {
            echo json_encode(['status' => 'error', 'title' => 'Access Denied', 'message' => __('you_can_only_cancel_your_own_requests', 'You can only cancel your own requests'), 'type' => 'error']);
            exit;
        }

        if (in_array($trip_row['current_status'], ['rejected', 'completed', 'cancelled'], true)) {
            echo json_encode(['status' => 'error', 'title' => 'Invalid Status', 'message' => 'This request in status "' . $trip_row['current_status'] . '" cannot be cancelled.', 'type' => 'warning']);
            exit;
        }

        $update_stmt = mysqli_prepare($conDB, "UPDATE emp_business_trip SET current_status = 'cancelled', modified_by = ?, last_modified = NOW() WHERE request_inv_no = ? AND current_status = ?");
        mysqli_stmt_bind_param($update_stmt, 'iss', $current_user_id, $trip_id, $trip_row['current_status']);
        mysqli_stmt_execute($update_stmt);
        $affected = mysqli_stmt_affected_rows($update_stmt);
        mysqli_stmt_close($update_stmt);

        if ($affected <= 0) {
            echo json_encode(['status' => 'error', 'title' => 'Error', 'message' => 'Failed to cancel request - status may have changed.', 'type' => 'error']);
            exit;
        }

        $ra_stmt = mysqli_prepare($conDB, "UPDATE request_approvers ra JOIN approval_request_types art ON art.id = ra.request_type_id AND art.type_name = 'business_trip' SET ra.status = 'cancelled' WHERE ra.request_inv_no = ? AND ra.status IN ('pending', 'awaiting')");
        mysqli_stmt_bind_param($ra_stmt, 's', $trip_id);
        mysqli_stmt_execute($ra_stmt);
        mysqli_stmt_close($ra_stmt);

        echo json_encode(['status' => 'success', 'title' => 'Cancelled', 'message' => 'Your business trip request has been cancelled successfully.', 'type' => 'success']);
        exit;
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'title' => 'Error', 'message' => $e->getMessage(), 'type' => 'error']);
        exit;
    }
}

if (isset($_POST['ajaxType']) && $_POST['ajaxType'] === 'rejectBusinessTrip') {
    try {
        $request_type_id = getBusinessTripRequestTypeId($conDB);
        if ($request_type_id <= 0) {
            throw new Exception('Business trip request type is not configured in approval_request_types.');
        }
        $trip_id = trim((string)($_POST['trip_id'] ?? ''));
        $rejection_reason = trim((string)($_POST['rejection_reason'] ?? ''));
        if ($trip_id === '') {
            echo json_encode([
                'status' => 'error',
                'title' => 'Invalid Request',
                'message' => 'Invalid business trip request id.',
                'type' => 'error'
            ]);
            exit;
        }

        if ($rejection_reason === '') {
            echo json_encode([
                'status' => 'error',
                'title' => 'Validation Error',
                'message' => 'Rejection reason is required.',
                'type' => 'warning'
            ]);
            exit;
        }

        $request_inv_no = $trip_id;
        $trip_status = '';
        $trip_emp_id = '';
        $trip_type = '';
        $trip_start_date = '';
        $trip_end_date = '';
        
        $trip_stmt = mysqli_prepare($conDB, "SELECT 
            current_status, emp_id, trip_type, trip_start_date, trip_end_date, 
            from_city_id, to_city_id, destination_country,
            fc.name_en as from_city_name_en, fc.name_ar as from_city_name_ar,
            tc.name_en as to_city_name_en, tc.name_ar as to_city_name_ar
            FROM emp_business_trip bt
            LEFT JOIN saudi_cities fc ON bt.from_city_id = fc.id
            LEFT JOIN saudi_cities tc ON bt.to_city_id = tc.id
            WHERE bt.request_inv_no = ? LIMIT 1");
        if ($trip_stmt) {
            mysqli_stmt_bind_param($trip_stmt, 's', $request_inv_no);
            mysqli_stmt_execute($trip_stmt);
            $trip_res = mysqli_stmt_get_result($trip_stmt);
            if ($trip_res && ($trip_row = mysqli_fetch_assoc($trip_res))) {
                $trip_status = (string)($trip_row['current_status'] ?? '');
                $trip_emp_id = (string)($trip_row['emp_id'] ?? '');
                $trip_type = (string)($trip_row['trip_type'] ?? '');
                $trip_start_date = (string)($trip_row['trip_start_date'] ?? '');
                $trip_end_date = (string)($trip_row['trip_end_date'] ?? '');
            }
            if ($trip_res) mysqli_free_result($trip_res);
            mysqli_stmt_close($trip_stmt);
        }

        if ($trip_status === '') {
            echo json_encode([
                'status' => 'error',
                'title' => 'Not Found',
                'message' => 'Business trip request not found.',
                'type' => 'error'
            ]);
            exit;
        }

        if (in_array($trip_status, ['approved', 'rejected', 'completed', 'cancelled'], true)) {
            echo json_encode([
                'status' => 'error',
                'title' => 'Invalid Status',
                'message' => 'This request is already finalized and cannot be rejected again.',
                'type' => 'warning'
            ]);
            exit;
        }

        $pending_stmt = mysqli_prepare($conDB, "SELECT id FROM request_approvers WHERE request_inv_no = ? AND request_type_id = ? AND approver_id = ? AND status = 'pending' LIMIT 1");
        $has_pending_access = false;
        if ($pending_stmt) {
            mysqli_stmt_bind_param($pending_stmt, 'sii', $request_inv_no, $request_type_id, $current_user_id);
            mysqli_stmt_execute($pending_stmt);
            $pending_res = mysqli_stmt_get_result($pending_stmt);
            $has_pending_access = ($pending_res && mysqli_num_rows($pending_res) > 0);
            if ($pending_res) mysqli_free_result($pending_res);
            mysqli_stmt_close($pending_stmt);
        }

        if (!$has_pending_access) {
            echo json_encode([
                'status' => 'error',
                'title' => 'Access Denied',
                'message' => 'This request is not pending your approval.',
                'type' => 'error'
            ]);
            exit;
        }

        $approval_result = handle_approval_action(
            $conDB,
            $request_inv_no,
            'business_trip',
            (int)$current_user_id,
            'reject',
            $rejection_reason
        );

        if (($approval_result['status'] ?? 'error') === 'error') {
            throw new Exception((string)($approval_result['message'] ?? 'Rejection failed.'));
        }

        if (function_exists('save_approval_comment_db')) {
            save_approval_comment_db(
                $conDB,
                $request_inv_no,
                'business_trip',
                'rejected',
                (int)$current_user_id,
                (string)$userwel,
                $rejection_reason
            );
        }

        if (class_exists('ActivityLogger')) {
            ActivityLogger::logApproval(
                'Business Trip',
                'ajaxBusinessTrip.php',
                $trip_id,
                'rejected',
                "Rejected business trip request: {$request_inv_no}",
                'emp_business_trip'
            );
        }

        echo json_encode([
            'status' => 'success',
            'title' => 'Rejected',
            'message' => 'Business trip request rejected successfully.',
            'type' => 'success',
            'request_inv_no' => $request_inv_no,
            'employee_id' => $trip_emp_id
        ]);
    } catch (Exception $e) {
        error_log('Business Trip Rejection Error: ' . $e->getMessage());
        echo json_encode([
            'status' => 'error',
            'title' => 'Rejection Failed',
            'message' => 'An error occurred while rejecting request: ' . $e->getMessage(),
            'type' => 'error'
        ]);
    }
    exit;
}

/**
 * Submit new business trip request
 */
if (isset($_POST['ajaxType']) && $_POST['ajaxType'] === 'submitBusinessTrip') {
    try {
        $request_type_id = getBusinessTripRequestTypeId($conDB);
        if ($request_type_id <= 0) {
            throw new Exception('Business trip request type is not configured in approval_request_types.');
        }
        $is_rtl = getIsRtlFlag();
        $cityColumn = $is_rtl ? 'name_ar' : 'name_en';
        $emp_id = (int)($_POST['emp_id'] ?? 0);

        // Block-check: employee may be restricted from submitting business trip requests
        require_once __DIR__ . '/../special_access_helper.php';
        $block_status = is_employee_request_blocked($conDB, $emp_id, 'business_trip');
        if ($block_status['blocked']) {
            echo json_encode([
                'status' => 'error',
                'title' => 'Request Blocked',
                'message' => $block_status['reason'],
                'type' => 'error'
            ]);
            exit;
        }

        // Validate supervisor assignment FIRST
        $supervisor_check = validate_employee_supervisor($conDB, $emp_id);
        if (!$supervisor_check['valid']) {
            echo json_encode([
                'status' => 'error',
                'title' => 'Supervisor Assignment Required',
                'message' => $supervisor_check['message'],
                'type' => 'warning'
            ]);
            exit;
        }

        // Sanitize inputs
        $trip_type = escape_string($_POST['trip_type'] ?? 'domestic');
        $trip_purpose = escape_string($_POST['trip_purpose'] ?? '');
        $transportation_type = escape_string($_POST['transportation_type'] ?? '');
        $trip_start_date = escape_string($_POST['trip_start_date'] ?? '');
        $trip_end_date = escape_string($_POST['trip_end_date'] ?? '');
        $from_city_id = (int)($_POST['from_city_id'] ?? 0);
        $to_city_id = (int)($_POST['to_city_id'] ?? 0);
        $destination_country = escape_string($_POST['destination_country'] ?? '');
        $visa_required = ((int)($_POST['visa_required'] ?? 0) === 1) ? 1 : 0;
        $request_notes = escape_string($_POST['request_notes'] ?? '');
        $first_approver_id = (int)($_POST['first_approver_id'] ?? 0);

        // Validate required fields
        if (empty($trip_type) || empty($trip_purpose) || empty($trip_start_date) || empty($trip_end_date) || 
            ($trip_type === 'domestic' && $from_city_id === 0) || 
            ($trip_type === 'domestic' && $to_city_id === 0) ||
            ($trip_type === 'domestic' && empty($transportation_type)) ||
            ($trip_type === 'international' && $from_city_id === 0) ||
            ($trip_type === 'international' && $to_city_id === 0)) {
            echo json_encode([
                'status' => 'error',
                'title' => 'Validation Error',
                'message' => 'Please fill in all required fields.',
                'type' => 'error'
            ]);
            exit;
        }

        if ($trip_type === 'domestic' && $from_city_id === $to_city_id) {
            echo json_encode([
                'status' => 'error',
                'title' => 'Validation Error',
                'message' => 'Departure city and destination must be different.',
                'type' => 'error'
            ]);
            exit;
        }

        if ($trip_type === 'international') {
            $transportation_type = '';
            if ($destination_country === '') {
                $country_stmt = mysqli_prepare($conDB, "SELECT name FROM countries WHERE id = ? LIMIT 1");
                if ($country_stmt) {
                    mysqli_stmt_bind_param($country_stmt, 'i', $to_city_id);
                    mysqli_stmt_execute($country_stmt);
                    $country_res = mysqli_stmt_get_result($country_stmt);
                    if ($country_res && ($country_row = mysqli_fetch_assoc($country_res))) {
                        $destination_country = escape_string((string)($country_row['name'] ?? ''));
                    }
                    if ($country_res) mysqli_free_result($country_res);
                    mysqli_stmt_close($country_stmt);
                }
            }
        }

        // International trip rules: passport must be valid > 3 months and attachment must exist or be uploaded now
        if ($trip_type === 'international') {
            $passport_exp_raw = '';
            $emp_query = mysqli_query($conDB, "SELECT passport_exp FROM employees WHERE emp_id = {$emp_id} LIMIT 1");
            if ($emp_query && ($emp_row = mysqli_fetch_assoc($emp_query))) {
                $passport_exp_raw = trim((string)($emp_row['passport_exp'] ?? ''));
                mysqli_free_result($emp_query);
            }

            $passport_exp_date = null;
            if ($passport_exp_raw !== '') {
                $formats = ['Y-m-d', 'd-m-Y', 'd/m/Y', 'm/d/Y', 'Y/m/d'];
                foreach ($formats as $format) {
                    $dt = DateTime::createFromFormat($format, $passport_exp_raw);
                    if ($dt instanceof DateTime) {
                        $passport_exp_date = $dt;
                        break;
                    }
                }

                if (!$passport_exp_date) {
                    $ts = strtotime($passport_exp_raw);
                    if ($ts !== false) {
                        $passport_exp_date = new DateTime(date('Y-m-d', $ts));
                    }
                }
            }

            $min_valid_date = new DateTime('today');
            $min_valid_date->modify('+3 months');
            if (!($passport_exp_date instanceof DateTime) || $passport_exp_date <= $min_valid_date) {
                echo json_encode([
                    'status' => 'error',
                    'title' => 'Passport Validation',
                    'message' => 'Passport must be valid for more than 3 months for international trip.',
                    'type' => 'warning'
                ]);
                exit;
            }

            $has_attachment = false;
            $emp_id_esc = escape_string((string)$emp_id);
            $doc_query = mysqli_query($conDB, "SELECT COUNT(*) AS total FROM emp_docu WHERE emp_id = '{$emp_id_esc}' AND path <> '' AND (status IS NULL OR status = 'A') AND LOWER(docu_typ) LIKE '%passport%'");
            if ($doc_query && ($doc_row = mysqli_fetch_assoc($doc_query))) {
                $has_attachment = ((int)$doc_row['total'] > 0);
                mysqli_free_result($doc_query);
            }

            if (!$has_attachment) {
                $uploadedPassportName = '';

                if (isset($_FILES['passport_file']) && !empty($_FILES['passport_file']['name']) && is_uploaded_file($_FILES['passport_file']['tmp_name'])) {
                    $uploadDir = __DIR__ . '/../../assets/emp_documents/';
                    if (!is_dir($uploadDir)) {
                        @mkdir($uploadDir, 0755, true);
                    }

                    $originalName = basename($_FILES['passport_file']['name']);
                    $tmpName = $_FILES['passport_file']['tmp_name'];
                    $fileExt = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
                    $allowedExt = ['jpg', 'jpeg', 'png', 'pdf'];

                    if (!in_array($fileExt, $allowedExt, true)) {
                        echo json_encode([
                            'status' => 'error',
                            'title' => 'Invalid File Type',
                            'message' => 'Passport attachment must be JPG, PNG, or PDF.',
                            'type' => 'error'
                        ]);
                        exit;
                    }

                    $uploadedPassportName = $emp_id . 'PASSPORT' . time() . rand(1000, 9999) . '.' . $fileExt;
                    $uploadPath = $uploadDir . $uploadedPassportName;

                    if (!move_uploaded_file($tmpName, $uploadPath)) {
                        throw new Exception('Failed to upload passport file.');
                    }

                    $ins_doc_stmt = mysqli_prepare($conDB, "INSERT INTO emp_docu (emp_id, docu_typ, path, docu_ext, pgid) VALUES (?, 'Passport', ?, ?, ?)");
                    if ($ins_doc_stmt) {
                        mysqli_stmt_bind_param($ins_doc_stmt, 'issi', $emp_id, $uploadedPassportName, $fileExt, $current_user_id);
                        if (!mysqli_stmt_execute($ins_doc_stmt)) {
                            mysqli_stmt_close($ins_doc_stmt);
                            throw new Exception('Failed to register passport attachment in employee documents.');
                        }
                        mysqli_stmt_close($ins_doc_stmt);
                    } else {
                        throw new Exception('Failed to prepare passport document insert.');
                    }
                } else {
                    echo json_encode([
                        'status' => 'error',
                        'title' => 'Passport Attachment Required',
                        'message' => 'No passport attachment found in system. Please upload passport file (JPG/PNG/PDF).',
                        'type' => 'warning'
                    ]);
                    exit;
                }
            }
        }

        // Strict rule: block new request if employee already has an active (not completed/rejected/cancelled) trip request
        $existing_request = null;
        $active_req_stmt = mysqli_prepare($conDB, "SELECT bt.id, bt.request_inv_no, bt.trip_type, bt.trip_start_date, bt.trip_end_date, bt.from_city_id, bt.to_city_id, bt.destination_country, bt.current_status, bt.created_at, fc." . $cityColumn . " AS from_city_route_name, tc." . $cityColumn . " AS to_city_route_name FROM emp_business_trip bt LEFT JOIN saudi_cities fc ON bt.from_city_id = fc.id LEFT JOIN saudi_cities tc ON bt.to_city_id = tc.id WHERE bt.emp_id = ? AND bt.current_status NOT IN ('completed','rejected','cancelled') ORDER BY bt.id DESC LIMIT 1");
        if ($active_req_stmt) {
            $emp_id_str = (string)$emp_id;
            mysqli_stmt_bind_param($active_req_stmt, "s", $emp_id_str);
            mysqli_stmt_execute($active_req_stmt);
            $active_res = mysqli_stmt_get_result($active_req_stmt);
            if ($active_res && ($active_row = mysqli_fetch_assoc($active_res))) {
                $existing_request = $active_row;
            }
            if ($active_res) mysqli_free_result($active_res);
            mysqli_stmt_close($active_req_stmt);
        }

        if ($existing_request) {
            $approval_chain = [];
            $chain_stmt = mysqli_prepare($conDB, "SELECT ra.approval_level, ra.status, ra.approver_id, e.name AS approver_name FROM request_approvers ra LEFT JOIN employees e ON e.emp_id = ra.approver_id WHERE ra.request_inv_no = ? ORDER BY ra.approval_level ASC, ra.id ASC");
            if ($chain_stmt) {
                $existing_inv = (string)$existing_request['request_inv_no'];
                mysqli_stmt_bind_param($chain_stmt, "s", $existing_inv);
                mysqli_stmt_execute($chain_stmt);
                $chain_res = mysqli_stmt_get_result($chain_stmt);
                if ($chain_res) {
                    while ($chain_row = mysqli_fetch_assoc($chain_res)) {
                        $approval_chain[] = [
                            'level' => (int)($chain_row['approval_level'] ?? 0),
                            'approver_id' => (int)($chain_row['approver_id'] ?? 0),
                            'approver_name' => (string)($chain_row['approver_name'] ?? ''),
                            'status' => (string)($chain_row['status'] ?? '')
                        ];
                    }
                    mysqli_free_result($chain_res);
                }
                mysqli_stmt_close($chain_stmt);
            }

            $route_text = '';
            if (($existing_request['trip_type'] ?? '') === 'international') {
                $route_text = 'Destination Country: ' . (string)($existing_request['destination_country'] ?? '');
            } else {
                $fromRouteName = trim((string)($existing_request['from_city_route_name'] ?? ''));
                $toRouteName = trim((string)($existing_request['to_city_route_name'] ?? ''));
                if ($fromRouteName !== '' || $toRouteName !== '') {
                    $route_text = 'Route: ' . ($fromRouteName !== '' ? $fromRouteName : ('#' . (string)($existing_request['from_city_id'] ?? ''))) . ' -> ' . ($toRouteName !== '' ? $toRouteName : ('#' . (string)($existing_request['to_city_id'] ?? '')));
                } else {
                    $route_text = 'Route (City IDs): ' . (string)($existing_request['from_city_id'] ?? '') . ' -> ' . (string)($existing_request['to_city_id'] ?? '');
                }
            }

            $message_lines = [
                'You already have an active business trip request. New request is not allowed until current one is completed.',
                '',
                'Applied Information:',
                'Request ID: ' . (string)$existing_request['request_inv_no'],
                'Status: ' . (string)$existing_request['current_status'],
                'Trip Type: ' . (string)$existing_request['trip_type'],
                'Trip Dates: ' . (string)$existing_request['trip_start_date'] . ' to ' . (string)$existing_request['trip_end_date'],
                $route_text
            ];

            $chain_html = '';
            $chain_lines = [];
            if (!empty($approval_chain)) {
                $message_lines[] = '';
                $message_lines[] = 'Request Chain:';
                $chain_html = '<ul style="text-align:left;margin:8px 0 0 18px;">';
                foreach ($approval_chain as $c) {
                    $line = 'Level ' . $c['level'] . ': ' . ($c['approver_name'] !== '' ? $c['approver_name'] : ('Emp#' . $c['approver_id'])) . ' (' . $c['status'] . ')';
                    $chain_lines[] = $line;
                    $chain_html .= '<li>' . htmlspecialchars($line, ENT_QUOTES, 'UTF-8') . '</li>';
                }
                $chain_html .= '</ul>';
            }

            $summary_html =
                '<div style="text-align:left;">'
                . '<p style="margin:0 0 8px 0;">You already have an active business trip request. New request is not allowed until current one is completed.</p>'
                . '<p style="margin:0 0 6px 0;"><strong>Applied Information</strong></p>'
                . '<ul style="margin:0 0 8px 18px;">'
                . '<li>Request ID: ' . htmlspecialchars((string)$existing_request['request_inv_no'], ENT_QUOTES, 'UTF-8') . '</li>'
                . '<li>Status: ' . htmlspecialchars((string)$existing_request['current_status'], ENT_QUOTES, 'UTF-8') . '</li>'
                . '<li>Trip Type: ' . htmlspecialchars((string)$existing_request['trip_type'], ENT_QUOTES, 'UTF-8') . '</li>'
                . '<li>Trip Dates: ' . htmlspecialchars((string)$existing_request['trip_start_date'], ENT_QUOTES, 'UTF-8') . ' to ' . htmlspecialchars((string)$existing_request['trip_end_date'], ENT_QUOTES, 'UTF-8') . '</li>'
                . '<li>' . htmlspecialchars($route_text, ENT_QUOTES, 'UTF-8') . '</li>'
                . '</ul>'
                . (!empty($approval_chain) ? '<p style="margin:0 0 6px 0;"><strong>Request Chain</strong></p>' . $chain_html : '')
                . '</div>';

            if (!empty($chain_lines)) {
                $message_lines = array_merge($message_lines, $chain_lines);
            }

            echo json_encode([
                'status' => 'error',
                'title' => 'Active Request Exists',
                'message' => implode("\n", $message_lines),
                'html' => $summary_html,
                'type' => 'warning',
                'existing_request' => $existing_request,
                'approval_chain' => $approval_chain
            ]);
            exit;
        }

        // Build approval chain from app_settings
        $approver_chain = [];
        $approvalChainSetting = "approval_chain_business_trip";
        
        $settingQuery = mysqli_query($conDB, "SELECT setting_value FROM app_settings WHERE setting_name = '" . escape_string($approvalChainSetting) . "' LIMIT 1");
        $configured_chain = [];
        
        if ($settingQuery && mysqli_num_rows($settingQuery) > 0) {
            $cfg_row = mysqli_fetch_assoc($settingQuery);
            $decoded = json_decode($cfg_row['setting_value'], true);
            if (is_array($decoded)) {
                $configured_chain = $decoded;
            }
        }
        if ($settingQuery) mysqli_free_result($settingQuery);

        // Helper: resolve configured role to approver emp_id
        $resolveApprover = function ($role, $emp_context) use ($conDB) {
            $role = trim((string)$role);
            if ($role === '') return null;

            if ($role === 'direct_supervisor') {
                return !empty($emp_context['supervisor_id']) ? (int)$emp_context['supervisor_id'] : null;
            }

            if ($role === 'dept_manager' && function_exists('getDeptManager')) {
                $dept_mgr = getDeptManager($conDB, $emp_context['dept']);
                return ($dept_mgr && !empty($dept_mgr['emp_id'])) ? (int)$dept_mgr['emp_id'] : null;
            }

            // Default: first active employee with matching user_type
            $stmt = mysqli_prepare($conDB, "SELECT e.emp_id FROM employees e 
                                           JOIN admin_login al ON e.emp_id = al.emp_id 
                                           WHERE al.user_type = ? AND e.status = 1 
                                           ORDER BY e.emp_id ASC LIMIT 1");
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "s", $role);
                mysqli_stmt_execute($stmt);
                $res = mysqli_stmt_get_result($stmt);
                if ($res && ($row = mysqli_fetch_assoc($res))) {
                    $empId = (int)$row['emp_id'];
                    mysqli_free_result($res);
                    mysqli_stmt_close($stmt);
                    return $empId > 0 ? $empId : null;
                }
                if ($res) mysqli_free_result($res);
                mysqli_stmt_close($stmt);
            }
            return null;
        };

        // Get employee context
        $emp_ctx = ['supervisor_id' => null, 'dept' => null, 'dept_name' => '', 'name' => '', 'email' => ''];
        $emp_stmt = mysqli_prepare($conDB, "SELECT e.supervisor_id, e.dept, d.dep_nme AS dept_name, e.name, al.email
                                            FROM employees e 
                                            LEFT JOIN department d ON d.id = e.dept
                                            LEFT JOIN admin_login al ON e.emp_id = al.emp_id 
                                            WHERE e.emp_id = ? LIMIT 1");
        if ($emp_stmt) {
            mysqli_stmt_bind_param($emp_stmt, "i", $emp_id);
            mysqli_stmt_execute($emp_stmt);
            $res = mysqli_stmt_get_result($emp_stmt);
            if ($res && ($row = mysqli_fetch_assoc($res))) {
                $emp_ctx = $row;
            }
            if ($res) mysqli_free_result($res);
            mysqli_stmt_close($emp_stmt);
        }

        // Resolve approval chain
        if (!empty($configured_chain)) {
            foreach ($configured_chain as $step) {
                $role = $step['user_type'] ?? '';
                $approverId = $resolveApprover($role, $emp_ctx);
                if ($approverId && !in_array($approverId, $approver_chain, true)) {
                    $approver_chain[] = $approverId;
                }
            }
        }

        if (empty($approver_chain) && $first_approver_id > 0) {
            $approver_chain[] = $first_approver_id;
        }

        if (!empty($approver_chain)) {
            $first_approver_id = (int)$approver_chain[0];
        }

        // Generate request ID
        $request_inv_no = generateBusinessTripRequestID($conDB, $emp_id);

        // Start transaction
        mysqli_begin_transaction($conDB);

        try {
            // Insert business trip request
            $insert_query = "INSERT INTO emp_business_trip (
                request_inv_no, emp_id, trip_type, trip_purpose, transportation_type,
                trip_start_date, trip_end_date,
                from_city_id, to_city_id,
                destination_country, visa_required, current_status, current_approval_level,
                request_notes, created_by, created_at
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW()
            )";
            
            $stmt = mysqli_prepare($conDB, $insert_query);
            if (!$stmt) {
                throw new Exception("Prepare failed: " . mysqli_error($conDB));
            }

            $current_status = 'pending_approval';
            $current_approval_level = 1;
            mysqli_stmt_bind_param($stmt, 
                "sisssssiisisisi",
                $request_inv_no, $emp_id, $trip_type, $trip_purpose, $transportation_type,
                $trip_start_date, $trip_end_date,
                $from_city_id, $to_city_id,
                $destination_country, $visa_required, $current_status, $current_approval_level, $request_notes, 
                $current_user_id
            );
            
            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception("Execute failed: " . mysqli_stmt_error($stmt));
            }

            $trip_id = mysqli_insert_id($conDB);
            mysqli_stmt_close($stmt);

            // Create request approvers entries
            foreach ($approver_chain as $index => $approver_id) {
                $level = $index + 1;
                $status = ($level === 1) ? 'pending' : 'awaiting';
                
                $approver_query = "INSERT INTO request_approvers (
                    request_inv_no, request_type_id, approver_id, approval_level, status
                ) VALUES (?, ?, ?, ?, ?)";
                
                $approver_stmt = mysqli_prepare($conDB, $approver_query);
                if (!$approver_stmt) {
                    throw new Exception("Approver prepare failed: " . mysqli_error($conDB));
                }

                mysqli_stmt_bind_param($approver_stmt, "siiss", $request_inv_no, $request_type_id, $approver_id, $level, $status);
                
                if (!mysqli_stmt_execute($approver_stmt)) {
                    throw new Exception("Approver execute failed: " . mysqli_stmt_error($approver_stmt));
                }
                mysqli_stmt_close($approver_stmt);
            }

            // Log activity
            if (class_exists('ActivityLogger')) {
                ActivityLogger::logSubmit('Business Trip', 'ajaxBusinessTrip.php', $trip_id, 
                    "Submitted business trip request: {$request_inv_no}, Type: {$trip_type}", 
                    'emp_business_trip');
            }

            // Create browser notification for first approver
            if (function_exists('create_browser_notification') && $first_approver_id > 0) {
                $notification_title = "New Business Trip Request for Approval";
                $notification_message = "Request {$request_inv_no} from {$emp_ctx['name']} is pending your approval.";
                $notification_url = "view_business_trip.php?id=" . urlencode($request_inv_no);
                create_browser_notification($conDB, $first_approver_id, $notification_title, $notification_message, $notification_url);
            }

            // Send email notification to first approver
            if (function_exists('send_approval_email')) {
                $first_approver_details = getEmployeeDetails($conDB, $first_approver_id);
                if ($first_approver_details && !empty($first_approver_details['email'])) {
                    $emailData = [
                        'APPROVER_NAME' => $first_approver_details['name'],
                        'REQUEST_TYPE' => 'Business Trip Request',
                        'REQUEST_TYPE_LOWER' => 'business trip request',
                        'REQUEST_ID' => $request_inv_no,
                        'REQUEST_TITLE' => 'Business Trip Request',
                        'SUBMITTED_BY' => $emp_ctx['name'] ?: $userwel,
                        'DEPARTMENT' => $emp_ctx['dept_name'] ?: 'N/A',
                        'EMPLOYEE_NAME' => $emp_ctx['name'],
                        'TRIP_TYPE' => ucfirst(str_replace('_', ' ', $trip_type)),
                        'TRIP_DATES' => $trip_start_date . ' to ' . $trip_end_date,
                        'REQUEST_URL' => get_base_url() . '/view_business_trip.php?id=' . urlencode($request_inv_no),
                        'EMAIL_MESSAGE' => 'A new business trip request requires your approval.'
                    ];
                    
                    send_approval_email($conDB, $first_approver_details['email'], $first_approver_details['name'],
                        "Business Trip Request Pending Approval - {$request_inv_no}", 
                        'business_trip', $emailData);
                }
            }

            mysqli_commit($conDB);

            echo json_encode([
                'status' => 'success',
                'title' => 'Request Submitted!',
                'message' => 'Your business trip request has been submitted successfully. Request ID: ' . $request_inv_no,
                'type' => 'success',
                'request_inv_no' => $request_inv_no
            ]);

        } catch (Exception $e) {
            mysqli_rollback($conDB);
            error_log("Business Trip Submission Error: " . $e->getMessage());
            echo json_encode([
                'status' => 'error',
                'title' => 'Submission Failed',
                'message' => 'An error occurred while submitting your request: ' . $e->getMessage(),
                'type' => 'error'
            ]);
        }

    } catch (Exception $e) {
        error_log("Business Trip Request Error: " . $e->getMessage());
        echo json_encode([
            'status' => 'error',
            'title' => 'Error',
            'message' => 'An unexpected error occurred: ' . $e->getMessage(),
            'type' => 'error'
        ]);
    }
    exit;
}

/**
 * Get Traveler Details for Business Trip Travel Email
 * Fetches business trip and employee details for travel email verification
 */
if (isset($_POST['ajaxType']) && $_POST['ajaxType'] === 'getTravelerDetailsBusinessTrip') {
    try {
        // Ensure travel_email_sent column exists
        $chk_col = mysqli_query($conDB, "SHOW COLUMNS FROM emp_business_trip LIKE 'travel_email_sent'");
        if (!$chk_col || mysqli_num_rows($chk_col) === 0) {
            mysqli_query($conDB, "ALTER TABLE emp_business_trip ADD COLUMN travel_email_sent TINYINT(1) DEFAULT 0");
        }
        if ($chk_col) mysqli_free_result($chk_col);
        
        $trip_id = (int)($_POST['trip_id'] ?? 0);
        
        if (empty($trip_id)) {
            throw new Exception('Invalid trip ID');
        }

        // Fetch business trip and employee details
        $sql = "SELECT 
                    bt.*,
                    e.name as employee_name,
                    e.passport_number,
                    e.passport_exp,
                    e.email as employee_email,
                    d.dep_nme as department_name,
                    fc.name_en as from_city_en,
                    fc.name_ar as from_city_ar,
                    tc.name_en as to_city_en,
                    tc.name_ar as to_city_ar,
                    c.name as destination_country_name
                FROM emp_business_trip bt
                JOIN employees e ON bt.emp_id = e.emp_id
                LEFT JOIN department d ON e.dept = d.id
                LEFT JOIN saudi_cities fc ON bt.from_city_id = fc.id
                LEFT JOIN saudi_cities tc ON bt.to_city_id = tc.id
                LEFT JOIN countries c ON bt.destination_country = c.name
                WHERE bt.id = ?";

        $stmt = mysqli_prepare($conDB, $sql);
        if (!$stmt) {
            throw new Exception('Database error: ' . mysqli_error($conDB));
        }

        mysqli_stmt_bind_param($stmt, 'i', $trip_id);
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception('Query execution failed: ' . mysqli_stmt_error($stmt));
        }

        $result = mysqli_stmt_get_result($stmt);
        $trip = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        if (!$trip) {
            throw new Exception('Business trip not found');
        }

        // Check passport validity (must be valid for 3 months beyond trip start date)
        $trip_start = new DateTime($trip['trip_start_date']);
        $min_valid_date = clone $trip_start;
        $min_valid_date->modify('+3 months');

        $passport_number = $trip['passport_number'] ?? '';
        $passport_exp = $trip['passport_exp'] ?? '';
        $missingPassport = empty($passport_number);
        
        $expSoon = false;
        if (!empty($passport_exp)) {
            $formats = ['Y-m-d', 'd-m-Y', 'd/m/Y', 'm/d/Y', 'Y/m/d'];
            $passport_exp_date = null;
            foreach ($formats as $format) {
                $dt = DateTime::createFromFormat($format, $passport_exp);
                if ($dt instanceof DateTime) {
                    $passport_exp_date = $dt;
                    break;
                }
            }
            if (!$passport_exp_date) {
                $ts = strtotime($passport_exp);
                if ($ts !== false) {
                    $passport_exp_date = new DateTime(date('Y-m-d', $ts));
                }
            }
            $expSoon = ($passport_exp_date instanceof DateTime) && ($passport_exp_date < $min_valid_date);
        } else {
            $expSoon = true;
        }

        $passport_valid = !$missingPassport && !$expSoon;

        // Check for stored passport document
        $has_attachment = false;
        $passport_doc_url = '';
        $passport_doc_is_image = false;
        $passport_doc_ext = '';
        
        $doc_stmt = mysqli_prepare($conDB, "SELECT path, docu_ext FROM emp_docu WHERE emp_id = ? AND LOWER(docu_typ) LIKE '%passport%' ORDER BY id DESC LIMIT 1");
        if ($doc_stmt) {
            mysqli_stmt_bind_param($doc_stmt, 's', $trip['emp_id']);
            mysqli_stmt_execute($doc_stmt);
            $doc_query = mysqli_stmt_get_result($doc_stmt);
            if ($doc_query && ($doc_row = mysqli_fetch_assoc($doc_query))) {
                $doc_path = trim((string)($doc_row['path'] ?? ''));
                if (!empty($doc_path)) {
                    $has_attachment = true;
                    $passport_doc_ext = strtoupper((string)($doc_row['docu_ext'] ?? ''));
                    $passport_doc_is_image = in_array(strtolower($passport_doc_ext), ['jpg', 'jpeg', 'png']);
                    $passport_doc_url = './assets/emp_documents/' . rawurlencode($doc_path);
                    $full_doc_path = __DIR__ . '/../../assets/emp_documents/' . $doc_path;
                    if (is_file($full_doc_path)) {
                        $passport_doc_url .= '?v=' . (string)filemtime($full_doc_path);
                    }
                }
                mysqli_free_result($doc_query);
            }
            mysqli_stmt_close($doc_stmt);
        }

        // Format dates for display
        $is_rtl = getIsRtlFlag();
        $start_date = new DateTime($trip['trip_start_date']);
        $end_date = new DateTime($trip['trip_end_date']);
        $start_str = $start_date->format('Y-m-d');
        $end_str = $end_date->format('Y-m-d');
        $diff = $end_date->diff($start_date);
        $days = (int)($diff->format('%a')) + 1;

        // Determine destination
        $destination = '';
        if ($trip['trip_type'] === 'international') {
            $destination = $trip['destination_country_name'] ?? $trip['destination_country'];
        } else {
            $from_city = $is_rtl ? ($trip['from_city_ar'] ?? $trip['from_city_en']) : ($trip['from_city_en'] ?? $trip['from_city_ar']);
            $to_city = $is_rtl ? ($trip['to_city_ar'] ?? $trip['to_city_en']) : ($trip['to_city_en'] ?? $trip['to_city_ar']);
            $destination = ($from_city ? $from_city : '') . ' → ' . ($to_city ? $to_city : '');
        }

        echo json_encode([
            'status' => 'success',
            'type' => 'success',
            'data' => [
                'employee_name' => $trip['employee_name'] ?? '',
                'emp_id' => $trip['emp_id'] ?? '',
                'department_name' => $trip['department_name'] ?? '',
                'passport_number' => $passport_number,
                'passport_number_raw' => $passport_number,
                'passport_exp' => $passport_exp,
                'passport_exp_raw' => $passport_exp,
                'passport_valid' => $passport_valid,
                'has_attachment' => $has_attachment,
                'passport_doc_url' => $passport_doc_url,
                'passport_doc_is_image' => $passport_doc_is_image,
                'passport_doc_ext' => $passport_doc_ext,
                'trip_type' => $trip['trip_type'] ?? 'domestic',
                'transportation_type' => $trip['transportation_type'] ?? '',
                'destination' => $destination,
                'destination_country' => $trip['destination_country_name'] ?? $trip['destination_country'] ?? '',
                'trip_start_date' => $start_str,
                'trip_end_date' => $end_str,
                'trip_start_date_raw' => $trip['trip_start_date'],
                'trip_days' => $days,
                'request_inv_no' => $trip['request_inv_no'] ?? '',
                'trip_purpose' => $trip['trip_purpose'] ?? ''
            ]
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'status' => 'error',
            'type' => 'error',
            'message' => $e->getMessage()
        ]);
    }
    exit;
}

/**
 * Send Travel Email for Business Trip (By Air)
 * Sends email to travel company for ticket purchase
 */
if (isset($_POST['ajaxType']) && $_POST['ajaxType'] === 'sendTravelEmailBusinessTrip') {
    try {
        // Ensure travel_email_sent column exists
        $chk_col = mysqli_query($conDB, "SHOW COLUMNS FROM emp_business_trip LIKE 'travel_email_sent'");
        if (!$chk_col || mysqli_num_rows($chk_col) === 0) {
            mysqli_query($conDB, "ALTER TABLE emp_business_trip ADD COLUMN travel_email_sent TINYINT(1) DEFAULT 0");
        }
        if ($chk_col) mysqli_free_result($chk_col);
        
        $trip_id = (int)($_POST['trip_id'] ?? 0);

        if (empty($trip_id)) {
            throw new Exception('Invalid trip ID');
        }

        // Prepare passport file variables
        $passport_file_path = '';
        $passport_file_name = '';
        $stored_doc_used = false;

        // Check if file was uploaded
        $has_new_upload = (isset($_FILES['passport_file']) && $_FILES['passport_file']['error'] === UPLOAD_ERR_OK);
        if ($has_new_upload) {
            $passport_file = $_FILES['passport_file'];
            $allowed_types = ['application/pdf', 'image/jpeg', 'image/png', 'image/jpg'];
            $max_size = 5 * 1024 * 1024; // 5MB
            
            if ($passport_file['size'] > $max_size) {
                throw new Exception('Passport file is too large. Maximum size is 5MB.');
            }
            
            $file_type = mime_content_type($passport_file['tmp_name']);
            if (!in_array($file_type, $allowed_types)) {
                throw new Exception('Invalid file type. Only PDF, JPG, and PNG files are allowed.');
            }
        }

        // Fetch business trip details
        $sql = "SELECT 
                    bt.*,
                    e.name as employee_name,
                    e.emp_id,
                    e.passport_number,
                    e.passport_exp,
                    c.name as destination_country_name
                FROM emp_business_trip bt
                JOIN employees e ON bt.emp_id = e.emp_id
                LEFT JOIN countries c ON bt.destination_country = c.name
                WHERE bt.id = ?";

        $stmt = mysqli_prepare($conDB, $sql);
        if (!$stmt) {
            throw new Exception('Database error: ' . mysqli_error($conDB));
        }

        mysqli_stmt_bind_param($stmt, 'i', $trip_id);
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception('Query execution failed: ' . mysqli_stmt_error($stmt));
        }

        $result = mysqli_stmt_get_result($stmt);
        $trip = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        if (!$trip) {
            throw new Exception('Business trip not found');
        }

        // Validate trip is approved and by_air
        if ($trip['current_status'] !== 'approved') {
            throw new Exception('Business trip must be approved before sending travel email');
        }

        if ($trip['transportation_type'] !== 'by_air') {
            throw new Exception('Travel email can only be sent for by-air trips');
        }

        // Handle passport file upload
        if ($has_new_upload) {
            $emp_id_for_passport = $trip['emp_id'];
            $file_extension = strtolower(pathinfo($_FILES['passport_file']['name'], PATHINFO_EXTENSION));
            $new_filename = $emp_id_for_passport . '_passport_' . date('YmdHis') . '_' . substr(md5(uniqid((string)mt_rand(), true)), 0, 8) . '.' . $file_extension;
            $destination_dir = __DIR__ . '/../../assets/emp_documents/';
            
            if (!is_dir($destination_dir)) {
                @mkdir($destination_dir, 0775, true);
            }
            
            $destination_path = $destination_dir . $new_filename;
            if (!move_uploaded_file($_FILES['passport_file']['tmp_name'], $destination_path)) {
                throw new Exception('Failed to store passport document');
            }
            
            // Upsert into emp_docu
            $existing_passport_doc = null;
            $chk_sql = "SELECT id FROM emp_docu WHERE emp_id = ? AND LOWER(docu_typ) LIKE '%passport%' ORDER BY id DESC LIMIT 1";
            if ($cs = mysqli_prepare($conDB, $chk_sql)) {
                mysqli_stmt_bind_param($cs, 's', $emp_id_for_passport);
                mysqli_stmt_execute($cs);
                $cr = mysqli_stmt_get_result($cs);
                $existing_passport_doc = mysqli_fetch_assoc($cr);
                mysqli_stmt_close($cs);
            }
            
            if ($existing_passport_doc) {
                $upd_sql = "UPDATE emp_docu SET path = ?, docu_ext = ?, created_at = NOW() WHERE id = ?";
                if ($us = mysqli_prepare($conDB, $upd_sql)) {
                    mysqli_stmt_bind_param($us, 'ssi', $new_filename, $file_extension, $existing_passport_doc['id']);
                    mysqli_stmt_execute($us);
                    mysqli_stmt_close($us);
                }
            } else {
                $ins_sql = "INSERT INTO emp_docu (emp_id, docu_typ, path, docu_ext, pgid, status, created_at) VALUES (?, 'passport', ?, ?, 0, 'A', NOW())";
                if ($is = mysqli_prepare($conDB, $ins_sql)) {
                    mysqli_stmt_bind_param($is, 'sss', $emp_id_for_passport, $new_filename, $file_extension);
                    mysqli_stmt_execute($is);
                    mysqli_stmt_close($is);
                }
            }
            
            $passport_file_path = $destination_path;
            $passport_file_name = $new_filename;
        } else {
            // Use existing passport document
            $existing_sql = "SELECT path FROM emp_docu WHERE emp_id = ? AND LOWER(docu_typ) LIKE '%passport%' ORDER BY id DESC LIMIT 1";
            if ($es = mysqli_prepare($conDB, $existing_sql)) {
                mysqli_stmt_bind_param($es, 's', $trip['emp_id']);
                mysqli_stmt_execute($es);
                $er = mysqli_stmt_get_result($es);
                $existing_passport = mysqli_fetch_assoc($er);
                mysqli_stmt_close($es);
                
                if ($existing_passport && !empty($existing_passport['path'])) {
                    $passport_file_name = $existing_passport['path'];
                    $passport_file_path = __DIR__ . '/../../assets/emp_documents/' . $passport_file_name;
                    $stored_doc_used = true;
                }
            }
            
            if (empty($passport_file_path) || !file_exists($passport_file_path)) {
                throw new Exception('Passport copy is required. Please attach a passport file.');
            }
        }

        // Get HR Payroll email for CC
        $hr_payroll_email = '';
        $hr_query = mysqli_query($conDB, "SELECT email FROM admin_login WHERE user_type LIKE '%hr_payroll%' AND email IS NOT NULL AND email != '' LIMIT 1");
        if ($hr_query && $hr_row = mysqli_fetch_assoc($hr_query)) {
            $hr_payroll_email = $hr_row['email'];
        }
        if ($hr_query) mysqli_free_result($hr_query);

        // Send email to travel company
        require_once __DIR__ . '/../helper_functions.php';

        $email_sent = send_travel_company_email(
            $conDB,
            $trip['employee_name'],
            $trip['passport_number'],
            $trip['passport_exp'],
            $trip['destination_country_name'] ?? $trip['destination_country'],
            $trip['trip_start_date'],
            $trip['trip_end_date'],
            $trip['request_inv_no'],
            $hr_payroll_email,
            $passport_file_path,
            $passport_file_name
        );

        if (!$email_sent) {
            throw new Exception('Error sending travel email. Please check SMTP settings and email configuration.');
        }

        // Mark email as sent
        $update_sql = "UPDATE emp_business_trip SET travel_email_sent = 1 WHERE id = ?";
        $update_stmt = mysqli_prepare($conDB, $update_sql);
        if ($update_stmt) {
            mysqli_stmt_bind_param($update_stmt, 'i', $trip_id);
            mysqli_stmt_execute($update_stmt);
            mysqli_stmt_close($update_stmt);
        }

        echo json_encode([
            'status' => 'success',
            'title' => 'Email Sent',
            'message' => 'Travel email has been sent to the travel company successfully.',
            'type' => 'success'
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'status' => 'error',
            'title' => 'Error',
            'message' => $e->getMessage(),
            'type' => 'error'
        ]);
    }
    exit;
}

if (isset($_POST['ajaxType']) && $_POST['ajaxType'] === 'getBusinessTripAllowances') {
    try {
        ensureBusinessTripAllowancesTable($conDB);

        $trip_id = (int)($_POST['trip_id'] ?? 0);
        if ($trip_id <= 0) {
            throw new Exception('Invalid trip ID');
        }

        $sqlTrip = "SELECT id, request_inv_no, emp_id, travel_email_sent, transportation_type, trip_start_date, trip_end_date FROM emp_business_trip WHERE id = ? LIMIT 1";
        $stmtTrip = mysqli_prepare($conDB, $sqlTrip);
        if (!$stmtTrip) {
            throw new Exception('Database error: ' . mysqli_error($conDB));
        }
        mysqli_stmt_bind_param($stmtTrip, 'i', $trip_id);
        mysqli_stmt_execute($stmtTrip);
        $resTrip = mysqli_stmt_get_result($stmtTrip);
        $trip = mysqli_fetch_assoc($resTrip);
        mysqli_stmt_close($stmtTrip);

        if (!$trip) {
            throw new Exception('Business trip not found');
        }

        // Only check travel_email_sent if transportation is by_air
        $transport_type = (string)($trip['transportation_type'] ?? '');
        if ($transport_type === 'by_air' && (int)($trip['travel_email_sent'] ?? 0) !== 1) {
            throw new Exception('Travel email must be sent before entering ticket allowances.');
        }

        $computed_allowance_days = calculateTripAllowanceDays($trip['trip_start_date'] ?? '', $trip['trip_end_date'] ?? '');

        $row = null;
        $sql = "SELECT * FROM emp_business_trip_allowances WHERE trip_id = ? LIMIT 1";
        $stmt = mysqli_prepare($conDB, $sql);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'i', $trip_id);
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);
            $row = mysqli_fetch_assoc($res);
            mysqli_stmt_close($stmt);
        }

        $lines = [];
        if (!empty($row['id'])) {
            $allowance_id = (int)$row['id'];
            $sqlLines = "SELECT allowance_type, unit_amount, qty, line_total FROM emp_business_trip_allowance_items WHERE allowance_id = ? ORDER BY line_order ASC, id ASC";
            $stmtLines = mysqli_prepare($conDB, $sqlLines);
            if ($stmtLines) {
                mysqli_stmt_bind_param($stmtLines, 'i', $allowance_id);
                mysqli_stmt_execute($stmtLines);
                $resLines = mysqli_stmt_get_result($stmtLines);
                while ($resLines && ($line = mysqli_fetch_assoc($resLines))) {
                    $lines[] = [
                        'allowance_type' => (string)($line['allowance_type'] ?? 'others'),
                        'unit_amount' => (float)($line['unit_amount'] ?? 0),
                        'qty' => (int)($line['qty'] ?? 1),
                        'line_total' => (float)($line['line_total'] ?? 0)
                    ];
                }
                mysqli_stmt_close($stmtLines);
            }
        }

        if (empty($lines)) {
            if ((float)($row['ticket_fares'] ?? 0) > 0) {
                $lines[] = ['allowance_type' => 'ticket_fares', 'unit_amount' => (float)$row['ticket_fares'], 'qty' => 1, 'line_total' => (float)$row['ticket_fares']];
            }
            if ((float)($row['other_allowance'] ?? 0) > 0) {
                $lines[] = ['allowance_type' => 'others', 'unit_amount' => (float)$row['other_allowance'], 'qty' => 1, 'line_total' => (float)$row['other_allowance']];
            }
        }

        echo json_encode([
            'status' => 'success',
            'data' => [
                'trip_start_date' => (string)($trip['trip_start_date'] ?? ''),
                'trip_end_date' => (string)($trip['trip_end_date'] ?? ''),
                'ticket_fares' => (float)($row['ticket_fares'] ?? 0),
                'other_allowance' => (float)($row['other_allowance'] ?? 0),
                'hotel_allowance' => 0.0,
                'transportation_allowance' => 0.0,
                'allowance_per_day' => 0.0,
                'allowance_weeks' => 0,
                'allowance_days' => $computed_allowance_days,
                'total_allowance' => (float)($row['total_allowance'] ?? 0),
                'notes' => (string)($row['notes'] ?? ''),
                'lines' => $lines
            ]
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'status' => 'error',
            'message' => $e->getMessage()
        ]);
    }
    exit;
}

if (isset($_POST['ajaxType']) && $_POST['ajaxType'] === 'saveBusinessTripAllowances') {
    try {
        ensureBusinessTripAllowancesTable($conDB);

        $trip_id = (int)($_POST['trip_id'] ?? 0);
        if ($trip_id <= 0) {
            throw new Exception('Invalid trip ID');
        }

        // Check if allowance record already exists for this trip
        $checkExistingSql = "SELECT id FROM emp_business_trip_allowances WHERE trip_id = ? LIMIT 1";
        $checkStmt = mysqli_prepare($conDB, $checkExistingSql);
        if (!$checkStmt) {
            throw new Exception('Database error: ' . mysqli_error($conDB));
        }
        mysqli_stmt_bind_param($checkStmt, 'i', $trip_id);
        mysqli_stmt_execute($checkStmt);
        $checkRes = mysqli_stmt_get_result($checkStmt);
        $existingRecord = mysqli_fetch_assoc($checkRes);
        mysqli_stmt_close($checkStmt);

        if ($existingRecord) {
            throw new Exception('An allowance record already exists for this trip. Please contact administrator to update it.');
        }

        $line_items_raw = (string)($_POST['line_items'] ?? '[]');
        $line_items = json_decode($line_items_raw, true);
        if (!is_array($line_items) || empty($line_items)) {
            throw new Exception('At least one allowance line is required.');
        }

        $ticket_fares = 0.0;
        $other_allowance = 0.0;
        $notes = trim((string)($_POST['notes'] ?? ''));

        $prepared_lines = [];
        $total_allowance = 0.0;
        foreach ($line_items as $index => $line) {
            $type = normalizeAllowanceType($line['allowance_type'] ?? 'others');
            $unit_amount = max(0, (float)($line['unit_amount'] ?? 0));
            $qty = max(1, (int)($line['qty'] ?? 1));
            if ($type === 'ticket_fares') {
                $qty = 1;
            }
            $line_total = $unit_amount * $qty;

            if ($type === 'ticket_fares') {
                $ticket_fares += $line_total;
            } else {
                $other_allowance += $line_total;
            }

            $total_allowance += $line_total;

            $prepared_lines[] = [
                'allowance_type' => $type,
                'unit_amount' => $unit_amount,
                'qty' => $qty,
                'line_total' => $line_total,
                'line_order' => $index + 1
            ];
        }

        $sqlTrip = "SELECT id, request_inv_no, emp_id, travel_email_sent, transportation_type, trip_start_date, trip_end_date FROM emp_business_trip WHERE id = ? LIMIT 1";
        $stmtTrip = mysqli_prepare($conDB, $sqlTrip);
        if (!$stmtTrip) {
            throw new Exception('Database error: ' . mysqli_error($conDB));
        }
        mysqli_stmt_bind_param($stmtTrip, 'i', $trip_id);
        mysqli_stmt_execute($stmtTrip);
        $resTrip = mysqli_stmt_get_result($stmtTrip);
        $trip = mysqli_fetch_assoc($resTrip);
        mysqli_stmt_close($stmtTrip);

        if (!$trip) {
            throw new Exception('Business trip not found');
        }

        // Only check travel_email_sent if transportation is by_air
        $transport_type = (string)($trip['transportation_type'] ?? '');
        if ($transport_type === 'by_air' && (int)($trip['travel_email_sent'] ?? 0) !== 1) {
            throw new Exception('Travel email must be sent before adding allowances.');
        }

        $trip_start_date = (string)($trip['trip_start_date'] ?? '');
        $existing_trip_end_date = (string)($trip['trip_end_date'] ?? '');
        $updated_trip_end_date = $existing_trip_end_date;

        $posted_return_date = trim((string)($_POST['return_date'] ?? ''));
        if ($posted_return_date !== '') {
            try {
                $startObj = new DateTime($trip_start_date);
                $returnObj = new DateTime($posted_return_date);
                if ($returnObj < $startObj) {
                    throw new Exception('Return date must be after or equal to start date.');
                }
                $updated_trip_end_date = $returnObj->format('Y-m-d');
            } catch (Exception $e) {
                throw new Exception('Invalid return date.');
            }
        }

        $allowance_days = calculateTripAllowanceDays($trip_start_date, $updated_trip_end_date);

        $request_inv_no = (string)($trip['request_inv_no'] ?? '');
        $emp_id = (string)($trip['emp_id'] ?? '');
        $current_user_id = (int)($_SESSION['empid'] ?? 0);

        mysqli_begin_transaction($conDB);

        if ($updated_trip_end_date !== $existing_trip_end_date) {
            $updTripSql = "UPDATE emp_business_trip SET trip_end_date = ? WHERE id = ? LIMIT 1";
            $updTripStmt = mysqli_prepare($conDB, $updTripSql);
            if (!$updTripStmt) {
                throw new Exception('Failed to update return date: ' . mysqli_error($conDB));
            }
            mysqli_stmt_bind_param($updTripStmt, 'si', $updated_trip_end_date, $trip_id);
            if (!mysqli_stmt_execute($updTripStmt)) {
                throw new Exception('Failed to update return date: ' . mysqli_stmt_error($updTripStmt));
            }
            mysqli_stmt_close($updTripStmt);
        }

        $sql = "INSERT INTO emp_business_trip_allowances
            (trip_id, request_inv_no, emp_id, ticket_fares, other_allowance, allowance_days, total_allowance, notes, created_by, updated_by)
            VALUES
            (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                request_inv_no = VALUES(request_inv_no),
                emp_id = VALUES(emp_id),
                ticket_fares = VALUES(ticket_fares),
                other_allowance = VALUES(other_allowance),
                allowance_days = VALUES(allowance_days),
                total_allowance = VALUES(total_allowance),
                notes = VALUES(notes),
                updated_by = VALUES(updated_by),
                updated_at = NOW()";

        $stmt = mysqli_prepare($conDB, $sql);
        if (!$stmt) {
            throw new Exception('Failed to save allowances: ' . mysqli_error($conDB));
        }

        mysqli_stmt_bind_param(
            $stmt,
            'issddidsii',
            $trip_id,
            $request_inv_no,
            $emp_id,
            $ticket_fares,
            $other_allowance,
            $allowance_days,
            $total_allowance,
            $notes,
            $current_user_id,
            $current_user_id
        );

        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception('Failed to save allowances: ' . mysqli_stmt_error($stmt));
        }
        mysqli_stmt_close($stmt);

        $allowance_id = 0;
        $selSql = "SELECT id FROM emp_business_trip_allowances WHERE trip_id = ? LIMIT 1";
        $selStmt = mysqli_prepare($conDB, $selSql);
        if (!$selStmt) {
            throw new Exception('Failed to load allowance record ID: ' . mysqli_error($conDB));
        }
        mysqli_stmt_bind_param($selStmt, 'i', $trip_id);
        mysqli_stmt_execute($selStmt);
        $selRes = mysqli_stmt_get_result($selStmt);
        if ($selRes && ($selRow = mysqli_fetch_assoc($selRes))) {
            $allowance_id = (int)$selRow['id'];
        }
        mysqli_stmt_close($selStmt);

        if ($allowance_id <= 0) {
            throw new Exception('Failed to prepare allowance details.');
        }

        $delSql = "DELETE FROM emp_business_trip_allowance_items WHERE allowance_id = ?";
        $delStmt = mysqli_prepare($conDB, $delSql);
        if (!$delStmt) {
            throw new Exception('Failed to clear old allowance lines: ' . mysqli_error($conDB));
        }
        mysqli_stmt_bind_param($delStmt, 'i', $allowance_id);
        mysqli_stmt_execute($delStmt);
        mysqli_stmt_close($delStmt);

        $insLineSql = "INSERT INTO emp_business_trip_allowance_items (allowance_id, trip_id, allowance_type, unit_amount, qty, line_total, line_order) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $insLineStmt = mysqli_prepare($conDB, $insLineSql);
        if (!$insLineStmt) {
            throw new Exception('Failed to prepare allowance line insert: ' . mysqli_error($conDB));
        }

        foreach ($prepared_lines as $line) {
            $lineType = $line['allowance_type'];
            $lineAmount = $line['unit_amount'];
            $lineQty = $line['qty'];
            $lineTotal = $line['line_total'];
            $lineOrder = $line['line_order'];
            mysqli_stmt_bind_param($insLineStmt, 'iisdidi', $allowance_id, $trip_id, $lineType, $lineAmount, $lineQty, $lineTotal, $lineOrder);
            if (!mysqli_stmt_execute($insLineStmt)) {
                throw new Exception('Failed to save allowance line: ' . mysqli_stmt_error($insLineStmt));
            }
        }
        mysqli_stmt_close($insLineStmt);

        mysqli_commit($conDB);

        echo json_encode([
            'status' => 'success',
            'title' => 'Saved',
            'message' => 'Ticket and allowances saved successfully.',
            'total_allowance' => round($total_allowance, 2),
            'trip_end_date' => $updated_trip_end_date,
            'allowance_days' => $allowance_days
        ]);
    } catch (Exception $e) {
        @mysqli_rollback($conDB);
        echo json_encode([
            'status' => 'error',
            'message' => $e->getMessage()
        ]);
    }
    exit;
}

if (isset($_POST['ajaxType']) && $_POST['ajaxType'] === 'getBusinessTripTicketFareOnly') {
    try {
        ensureBusinessTripAllowancesTable($conDB);

        if (!isCurrentUserHRPayroll()) {
            throw new Exception('Only HR Payroll can manage ticket fare only.');
        }

        $trip_id = (int)($_POST['trip_id'] ?? 0);
        if ($trip_id <= 0) {
            throw new Exception('Invalid trip ID');
        }

        $sqlTrip = "SELECT id, travel_email_sent, transportation_type FROM emp_business_trip WHERE id = ? LIMIT 1";
        $stmtTrip = mysqli_prepare($conDB, $sqlTrip);
        if (!$stmtTrip) {
            throw new Exception('Database error: ' . mysqli_error($conDB));
        }
        mysqli_stmt_bind_param($stmtTrip, 'i', $trip_id);
        mysqli_stmt_execute($stmtTrip);
        $resTrip = mysqli_stmt_get_result($stmtTrip);
        $trip = mysqli_fetch_assoc($resTrip);
        mysqli_stmt_close($stmtTrip);

        if (!$trip) {
            throw new Exception('Business trip not found');
        }
        // Only check travel_email_sent if transportation is by_air
        $transport_type = (string)($trip['transportation_type'] ?? '');
        if ($transport_type === 'by_air' && (int)($trip['travel_email_sent'] ?? 0) !== 1) {
            throw new Exception('Travel email must be sent before adding ticket fare.');
        }

        $allowance_id = 0;
        $stmtHead = mysqli_prepare($conDB, "SELECT id FROM emp_business_trip_allowances WHERE trip_id = ? LIMIT 1");
        if ($stmtHead) {
            mysqli_stmt_bind_param($stmtHead, 'i', $trip_id);
            mysqli_stmt_execute($stmtHead);
            $resHead = mysqli_stmt_get_result($stmtHead);
            if ($resHead && ($rowHead = mysqli_fetch_assoc($resHead))) {
                $allowance_id = (int)$rowHead['id'];
            }
            mysqli_stmt_close($stmtHead);
        }

        $ticket_unit_amount = 0.0;
        $ticket_qty = 1;
        $ticket_total = 0.0;
        if ($allowance_id > 0) {
            $stmtLine = mysqli_prepare($conDB, "SELECT unit_amount, qty, line_total FROM emp_business_trip_allowance_items WHERE allowance_id = ? AND allowance_type = 'ticket_fares' ORDER BY line_order ASC, id ASC LIMIT 1");
            if ($stmtLine) {
                mysqli_stmt_bind_param($stmtLine, 'i', $allowance_id);
                mysqli_stmt_execute($stmtLine);
                $resLine = mysqli_stmt_get_result($stmtLine);
                if ($resLine && ($rowLine = mysqli_fetch_assoc($resLine))) {
                    $ticket_unit_amount = (float)($rowLine['unit_amount'] ?? 0);
                    $ticket_qty = max(1, (int)($rowLine['qty'] ?? 1));
                    $ticket_total = (float)($rowLine['line_total'] ?? 0);
                }
                mysqli_stmt_close($stmtLine);
            }
        }

        echo json_encode([
            'status' => 'success',
            'data' => [
                'ticket_unit_amount' => $ticket_unit_amount,
                'ticket_qty' => $ticket_qty,
                'ticket_total' => $ticket_total
            ]
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'status' => 'error',
            'message' => $e->getMessage()
        ]);
    }
    exit;
}

if (isset($_POST['ajaxType']) && $_POST['ajaxType'] === 'saveBusinessTripTicketFareOnly') {
    try {
        ensureBusinessTripAllowancesTable($conDB);

        if (!isCurrentUserHRPayroll()) {
            throw new Exception('Only HR Payroll can save ticket fare only.');
        }

        $trip_id = (int)($_POST['trip_id'] ?? 0);
        $ticket_unit_amount = max(0, (float)($_POST['ticket_unit_amount'] ?? 0));
        $ticket_qty = max(1, (int)($_POST['ticket_qty'] ?? 1));
        if ($trip_id <= 0) {
            throw new Exception('Invalid trip ID');
        }

        // Check if allowance record already exists for this trip
        $checkExistingSql = "SELECT id FROM emp_business_trip_allowances WHERE trip_id = ? LIMIT 1";
        $checkStmt = mysqli_prepare($conDB, $checkExistingSql);
        if (!$checkStmt) {
            throw new Exception('Database error: ' . mysqli_error($conDB));
        }
        mysqli_stmt_bind_param($checkStmt, 'i', $trip_id);
        mysqli_stmt_execute($checkStmt);
        $checkRes = mysqli_stmt_get_result($checkStmt);
        $existingRecord = mysqli_fetch_assoc($checkRes);
        mysqli_stmt_close($checkStmt);

        if ($existingRecord) {
            throw new Exception('An allowance record already exists for this trip. Contact administrator to modify.');
        }

        $sqlTrip = "SELECT id, request_inv_no, emp_id, travel_email_sent, transportation_type, trip_start_date, trip_end_date FROM emp_business_trip WHERE id = ? LIMIT 1";
        $stmtTrip = mysqli_prepare($conDB, $sqlTrip);
        if (!$stmtTrip) {
            throw new Exception('Database error: ' . mysqli_error($conDB));
        }
        mysqli_stmt_bind_param($stmtTrip, 'i', $trip_id);
        mysqli_stmt_execute($stmtTrip);
        $resTrip = mysqli_stmt_get_result($stmtTrip);
        $trip = mysqli_fetch_assoc($resTrip);
        mysqli_stmt_close($stmtTrip);

        if (!$trip) {
            throw new Exception('Business trip not found');
        }
        // Only check travel_email_sent if transportation is by_air
        $transport_type = (string)($trip['transportation_type'] ?? '');
        if ($transport_type === 'by_air' && (int)($trip['travel_email_sent'] ?? 0) !== 1) {
            throw new Exception('Travel email must be sent before adding ticket fare.');
        }

        $allowance_days = calculateTripAllowanceDays($trip['trip_start_date'] ?? '', $trip['trip_end_date'] ?? '');

        $request_inv_no = (string)($trip['request_inv_no'] ?? '');
        $emp_id = (string)($trip['emp_id'] ?? '');
        $current_user_id = (int)($_SESSION['empid'] ?? 0);

        mysqli_begin_transaction($conDB);

        $insHeadSql = "INSERT INTO emp_business_trip_allowances
            (trip_id, request_inv_no, emp_id, ticket_fares, other_allowance, allowance_days, total_allowance, notes, created_by, updated_by)
            VALUES
            (?, ?, ?, 0, 0, 0, 0, '', ?, ?)
            ON DUPLICATE KEY UPDATE
                request_inv_no = VALUES(request_inv_no),
                emp_id = VALUES(emp_id),
                updated_by = VALUES(updated_by),
                updated_at = NOW()";
        $insHeadStmt = mysqli_prepare($conDB, $insHeadSql);
        if (!$insHeadStmt) {
            throw new Exception('Failed to initialize allowance record: ' . mysqli_error($conDB));
        }
        mysqli_stmt_bind_param($insHeadStmt, 'issii', $trip_id, $request_inv_no, $emp_id, $current_user_id, $current_user_id);
        if (!mysqli_stmt_execute($insHeadStmt)) {
            throw new Exception('Failed to initialize allowance record: ' . mysqli_stmt_error($insHeadStmt));
        }
        mysqli_stmt_close($insHeadStmt);

        $allowance_id = 0;
        $selHeadStmt = mysqli_prepare($conDB, "SELECT id FROM emp_business_trip_allowances WHERE trip_id = ? LIMIT 1");
        if (!$selHeadStmt) {
            throw new Exception('Failed to find allowance record: ' . mysqli_error($conDB));
        }
        mysqli_stmt_bind_param($selHeadStmt, 'i', $trip_id);
        mysqli_stmt_execute($selHeadStmt);
        $selHeadRes = mysqli_stmt_get_result($selHeadStmt);
        if ($selHeadRes && ($selHeadRow = mysqli_fetch_assoc($selHeadRes))) {
            $allowance_id = (int)$selHeadRow['id'];
        }
        mysqli_stmt_close($selHeadStmt);

        if ($allowance_id <= 0) {
            throw new Exception('Failed to prepare allowance record id.');
        }

        $delTicketStmt = mysqli_prepare($conDB, "DELETE FROM emp_business_trip_allowance_items WHERE allowance_id = ? AND allowance_type = 'ticket_fares'");
        if (!$delTicketStmt) {
            throw new Exception('Failed to clear ticket fare lines: ' . mysqli_error($conDB));
        }
        mysqli_stmt_bind_param($delTicketStmt, 'i', $allowance_id);
        mysqli_stmt_execute($delTicketStmt);
        mysqli_stmt_close($delTicketStmt);

        $ticket_total = $ticket_unit_amount * $ticket_qty;
        $insTicketStmt = mysqli_prepare($conDB, "INSERT INTO emp_business_trip_allowance_items (allowance_id, trip_id, allowance_type, unit_amount, qty, line_total, line_order) VALUES (?, ?, 'ticket_fares', ?, ?, ?, 1)");
        if (!$insTicketStmt) {
            throw new Exception('Failed to prepare ticket fare insert: ' . mysqli_error($conDB));
        }
        mysqli_stmt_bind_param($insTicketStmt, 'iidid', $allowance_id, $trip_id, $ticket_unit_amount, $ticket_qty, $ticket_total);
        if (!mysqli_stmt_execute($insTicketStmt)) {
            throw new Exception('Failed to save ticket fare line: ' . mysqli_stmt_error($insTicketStmt));
        }
        mysqli_stmt_close($insTicketStmt);

        $aggSql = "SELECT
            SUM(CASE WHEN allowance_type = 'ticket_fares' THEN line_total ELSE 0 END) AS ticket_fares,
            SUM(CASE WHEN allowance_type IN ('others', 'other_allowance') THEN line_total ELSE 0 END) AS other_allowance,
            SUM(line_total) AS total_allowance
            FROM emp_business_trip_allowance_items WHERE allowance_id = ?";
        $aggStmt = mysqli_prepare($conDB, $aggSql);
        if (!$aggStmt) {
            throw new Exception('Failed to aggregate allowance totals: ' . mysqli_error($conDB));
        }
        mysqli_stmt_bind_param($aggStmt, 'i', $allowance_id);
        mysqli_stmt_execute($aggStmt);
        $aggRes = mysqli_stmt_get_result($aggStmt);
        $agg = mysqli_fetch_assoc($aggRes) ?: [];
        mysqli_stmt_close($aggStmt);

        $updHeadSql = "UPDATE emp_business_trip_allowances
            SET ticket_fares = ?, other_allowance = ?, allowance_days = ?, total_allowance = ?, updated_by = ?, updated_at = NOW()
            WHERE id = ?";
        $updHeadStmt = mysqli_prepare($conDB, $updHeadSql);
        if (!$updHeadStmt) {
            throw new Exception('Failed to update allowance totals: ' . mysqli_error($conDB));
        }
        $aggTicket = (float)($agg['ticket_fares'] ?? 0);
        $aggOther = (float)($agg['other_allowance'] ?? 0);
        $aggDays = $allowance_days;
        $aggTotal = (float)($agg['total_allowance'] ?? 0);
        mysqli_stmt_bind_param($updHeadStmt, 'ddidii', $aggTicket, $aggOther, $aggDays, $aggTotal, $current_user_id, $allowance_id);
        if (!mysqli_stmt_execute($updHeadStmt)) {
            throw new Exception('Failed to update allowance totals: ' . mysqli_stmt_error($updHeadStmt));
        }
        mysqli_stmt_close($updHeadStmt);

        mysqli_commit($conDB);

        echo json_encode([
            'status' => 'success',
            'title' => 'Saved',
            'message' => 'Ticket fare saved successfully.',
            'ticket_total' => round($ticket_total, 2)
        ]);
    } catch (Exception $e) {
        @mysqli_rollback($conDB);
        echo json_encode([
            'status' => 'error',
            'message' => $e->getMessage()
        ]);
    }
    exit;
}

?>
