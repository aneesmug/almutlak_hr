<?php

/**************************************************************************************************
 * MODIFICATION SUMMARY
 *
 * 1.  **RTL Layout Adjustments for Icons**:
 * - Swapped the position of icons and text in the "More" dropdown button and all its items to ensure icons appear on the right in the Arabic RTL layout.
 * - Swapped the position of icons and text for the action buttons at the bottom of the card ("Add Social Media", "Update Salary", etc.).
 * - Updated the "Goto Back" button's icon from `fa-angle-double-left` to `fa-angle-double-right` to correctly indicate direction in an RTL context.
 *
 **************************************************************************************************/
require_once __DIR__ . '/special_access_helper.php';

$current_page_name = basename($_SERVER['PHP_SELF']);

// Calculate employee modification permission - matches includes/employee_card.php's
// $can_modify_employee (was missing the 'access_edit_employee' special access grant
// here, so a granted employee still never saw Edit/Note in this "More Actions" menu).
$can_modify_employee = (
	($is_system_admin ?? false) ||
	($isDeptHr ?? false) ||
	user_has_special_access($conDB, $empid ?? '', 'access_edit_employee', $user_role ?? '', $user_type ?? '', $is_system_admin ?? false)
);

// "Update Salary" button visibility: shown only when due (a Salary Increment request
// was just finally approved and hasn't been applied yet), the employee has no salary
// breakdown at all yet (new-hire onboarding), or a sys-admin-granted special access
// override forces it on for this specific employee via edit_employee.php.
$empIdForSalaryCheck = $emprow['empid'] ?? $emprow['emp_id'] ?? '';
$hasSalaryRecord = true;
if (!empty($empIdForSalaryCheck)) {
	$checkSalaryStmt = $pdo->prepare("SELECT id FROM emp_salary WHERE emp_id = :emp_id LIMIT 1");
	$checkSalaryStmt->execute([':emp_id' => $empIdForSalaryCheck]);
	$hasSalaryRecord = (bool)$checkSalaryStmt->fetch();
}
$canForceShowUpdateSalary = (
	($is_system_admin ?? false) ||
	user_has_special_access($conDB, $empid ?? '', 'manage_update_salary_button_visibility', $user_role ?? '', $user_type ?? '', $is_system_admin ?? false)
);
$showUpdateSalaryBtn = (
	!$hasSalaryRecord ||
	((string)($emprow['salary_update_pending'] ?? '0') === '1') ||
	((string)($emprow['force_show_update_salary_btn'] ?? '0') === '1') ||
	$canForceShowUpdateSalary
);

// Effective request-block status for this employee (global block XOR employee override),
// used to hide "More Actions" menu items for request types this employee cannot submit.
// Sys admin always sees every action, regardless of block settings in
// App Settings -> Special Access.
$empIdForBlockCheck = $emprow['empid'] ?? $emprow['emp_id'] ?? '';
$isLoanBlocked = !($is_system_admin ?? false) && is_employee_request_blocked($conDB, $empIdForBlockCheck, 'loan_request')['blocked'];
$isExcuseLeaveBlocked = !($is_system_admin ?? false) && is_employee_request_blocked($conDB, $empIdForBlockCheck, 'excuse_leave')['blocked'];
$isResignationBlocked = !($is_system_admin ?? false) && is_employee_request_blocked($conDB, $empIdForBlockCheck, 'resignation_request')['blocked'];
$isVacationAnnualBlocked = !($is_system_admin ?? false) && is_employee_request_blocked($conDB, $empIdForBlockCheck, 'vacation_annual')['blocked'];
$isVacationEmergencyBlocked = !($is_system_admin ?? false) && is_employee_request_blocked($conDB, $empIdForBlockCheck, 'vacation_emergency')['blocked'];
$isVacationLocalBlocked = !($is_system_admin ?? false) && is_employee_request_blocked($conDB, $empIdForBlockCheck, 'vacation_local')['blocked'];
$isVacationEncashedBlocked = !($is_system_admin ?? false) && is_employee_request_blocked($conDB, $empIdForBlockCheck, 'vacation_encashed')['blocked'];
$isAllVacationBlocked = ($isVacationAnnualBlocked && $isVacationEmergencyBlocked && $isVacationLocalBlocked && $isVacationEncashedBlocked);
$isBusinessTripBlocked = !($is_system_admin ?? false) && is_employee_request_blocked($conDB, $empIdForBlockCheck, 'business_trip')['blocked'];

// Can the current user request an Employee Transfer for this employee? Either
// they're already a Direct Supervisor of someone (any employee), a system
// admin, or have been explicitly granted the 'request_employee_transfer'
// Special Access key (App Settings -> Special Access) - and this employee
// isn't themselves and doesn't already report to them.
$is_direct_supervisor_for_transfer = false;
if (!empty($empid)) {
	$sup_check_transfer = mysqli_query($conDB, "SELECT COUNT(*) AS cnt FROM `employees` WHERE `supervisor_id` = '" . mysqli_real_escape_string($conDB, (string)$empid) . "' AND `status` = 1");
	if ($sup_check_transfer && ($sup_row_transfer = mysqli_fetch_assoc($sup_check_transfer))) {
		$is_direct_supervisor_for_transfer = ((int)$sup_row_transfer['cnt'] > 0);
	}
	if ($sup_check_transfer) mysqli_free_result($sup_check_transfer);
}
$can_request_employee_transfer = (
	(
		$is_direct_supervisor_for_transfer
		|| ($is_system_admin ?? false)
		|| user_has_special_access($conDB, $empid ?? '', 'request_employee_transfer', $user_role ?? '', $user_type ?? '', $is_system_admin ?? false)
	)
	&& $current_page_name !== 'profile.php'
	&& (string)($emprow['empid'] ?? '') !== (string)($empid ?? '')
	&& (string)($emprow['supervisor_id'] ?? '') !== (string)($empid ?? '')
);

// Get available vacation balance for modal actions
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

// Build More Actions menu HTML organized by categories
$moreActionsHtml = '';
// Actions are collected per section, then rendered as titled groups at the end (view_employee.php styles them).
$ma = ['requests' => [], 'vacation' => [], 'manage' => [], 'exit' => []];
if ($emprow['status'] == 1 && $user_type === 'archiving') {
	// Archiving role: document upload only, none of the HR/management actions below.
	$ma['manage'][] = "<div class=\"menu-item text-primary addEmpDocuAtter\" data-id=\"" . htmlspecialchars($emprow['eid']) . "\" data-emp_id=\"" . htmlspecialchars($emprow['empid']) . "\" role=\"button\"><i class=\"fa fa-solid fa-upload\"></i><span>" . __('add_documents') . "</span></div>";
} elseif ($emprow['status'] == 1) {
	// HR ACTIONS
	// Add Documents (HR or Admin)
	if ($isDeptHr || $isHR || $is_system_admin) {
		$ma['manage'][] = "<div class=\"menu-item text-primary addEmpDocuAtter\" data-id=\"" . htmlspecialchars($emprow['eid']) . "\" data-emp_id=\"" . htmlspecialchars($emprow['empid']) . "\" role=\"button\"><i class=\"fa fa-solid fa-upload\"></i><span>" . __('add_documents') . "</span></div>";
	}
	
	// Apply Loan: always shown (unless loan requests are blocked). With an active loan the click
	// shows the loan summary + "contact HR" instead of the form (assets/js/loanHandling.js).
	if (!$isLoanBlocked /*&& ($is_system_admin || $isDeptHr || $isHR)*/) {
		$ma['requests'][] = "<div class=\"menu-item text-warning applyLoan\" data-emp_id=\"" . htmlspecialchars($emprow['empid']) . "\" role=\"button\"><i class=\"fa fa-money-bill-trend-up\"></i><span>" . __('apply_loan') . "</span></div>";
	}

	// Apply Salary Increment (Direct Supervisor only, for employees with > 1 year tenure)
	$isSupervisorOfThisEmp = (string)($emprow['supervisor_id'] ?? '') === (string)($empid ?? '');
	$tenureOk = false;
	if (!empty($emprow['joining_date'])) {
		try {
			$joinDate = new DateTime($emprow['joining_date']);
			$tenureOk = $joinDate <= (new DateTime('-1 year'));
		} catch (Exception $e) {
			$tenureOk = false;
		}
	}
	$isSalaryIncrementBlocked = !($is_system_admin ?? false) && is_employee_request_blocked($conDB, $empIdForBlockCheck, 'salary_increment')['blocked'];

	if (($isSupervisorOfThisEmp || $is_system_admin) && $tenureOk && !$isSalaryIncrementBlocked && $current_page_name !== 'profile.php') {
		$deptNameForSalaryIncrement = '';
		if (!empty($emprow['dept'])) {
			$deptNameQuery = mysqli_query($conDB, "SELECT `dep_nme` FROM `department` WHERE `id` = '" . (int)$emprow['dept'] . "' LIMIT 1");
			if ($deptNameQuery && ($deptNameRow = mysqli_fetch_assoc($deptNameQuery))) {
				$deptNameForSalaryIncrement = $deptNameRow['dep_nme'] ?? '';
			}
			if ($deptNameQuery) mysqli_free_result($deptNameQuery);
		}
		$ma['requests'][] = "<div class=\"menu-item text-info\" onclick=\"openSalaryIncrementApplyModal('" . htmlspecialchars($emprow['empid']) . "', '" . htmlspecialchars($emprow['iqama'] ?? '') . "', '" . htmlspecialchars($emprow['name'] ?? '', ENT_QUOTES) . "', '" . htmlspecialchars($deptNameForSalaryIncrement, ENT_QUOTES) . "', '" . htmlspecialchars($emprow['joining_date'] ?? '') . "')\" role=\"button\"><i class=\"fa fa-arrow-trend-up\"></i><span>" . __('apply_salary_increment', 'Apply Salary Increment') . "</span></div>";
	}

	// Request Employee Transfer (Direct Supervisor only, requesting someone who isn't already their report)
	if ($can_request_employee_transfer) {
		$ma['requests'][] = "<div class=\"menu-item text-primary\" onclick=\"openEmployeeTransferModal('" . htmlspecialchars((string)$empid, ENT_QUOTES) . "', '" . htmlspecialchars($emprow['empid'], ENT_QUOTES) . "')\" role=\"button\"><i class=\"fa fa-people-arrows\"></i><span>" . __('request_employee_transfer', 'Request Employee Transfer') . "</span></div>";
	}

	// *IT ACTIONS
	/* if ($isItAssistant || $is_system_admin) {
		$ma['manage'][] = "<div class=\"menu-item text-dark\" onclick=\"assignAsset('" . htmlspecialchars($emprow['empid']) . "')\" role=\"button\"><i class=\"fa fa-solid fa-project-diagram\"></i><span>" . __('assign_asset') . "</span></div>";
	} */
	
	// VACATION & LEAVE ACTIONS
	if ($user_dept == $emprow['dept'] || $is_system_admin || $isDeptHr || $isHR) {
		if ($emprow['emp_sup_type'] != "man_power") {
			// Annual Vacation
			if ($emprow['apd_status'] != 'approve' && !$isAllVacationBlocked /*&& $emprow["fly"] == 0*/ ) {
				$allowEmergencyVacation = ((string)($emprow['allow_emergency_vacation'] ?? '0') === '1') ? 1 : 0;
				$allowVacSalaryBelowMinDays = ((string)($emprow['allow_vacation_salary_below_min_days'] ?? '0') === '1') ? 1 : 0;
				$ma['vacation'][] = "<div class=\"menu-item text-info applyvacationAtter\" data-empid=\"" . htmlspecialchars($emprow['empid']) . "\" data-dept=\"" . htmlspecialchars($emprow['dept']) . "\" data-country=\"" . htmlspecialchars($emprow['country']) . "\" data-balance=\"{$displayBalance}\" data-allow-emergency=\"{$allowEmergencyVacation}\" data-allow-vac-salary-below-min=\"{$allowVacSalaryBelowMinDays}\" data-block-annual=\"" . ($isVacationAnnualBlocked ? 1 : 0) . "\" data-block-emergency=\"" . ($isVacationEmergencyBlocked ? 1 : 0) . "\" data-block-local=\"" . ($isVacationLocalBlocked ? 1 : 0) . "\" data-block-encashed=\"" . ($isVacationEncashedBlocked ? 1 : 0) . "\" role=\"button\"><i class=\"fa fa-user-chart\"></i><span>" . __('apply_annual_vacation') . "</span></div>";
			}
			
			// Business Trip Request
			if (!$isBusinessTripBlocked) {
				$ma['vacation'][] = "<div class=\"menu-item text-warning\" onclick=\"openBusinessTripApplyModal('" . htmlspecialchars($emprow['empid']) . "', '" . htmlspecialchars($emprow['dept']) . "', '" . htmlspecialchars($emprow['country']) . "')\" role=\"button\"><i class=\"fa fa-plane\"></i><span>" . __('apply_business_trip', 'Apply Business Trip') . "</span></div>";
			}

			// Excuse Leave
			if (!$isExcuseLeaveBlocked) {
				$ma['vacation'][] = "<div class=\"menu-item text-success applyLeaveRequest\" data-empid=\"" . htmlspecialchars($emprow['empid']) . "\" role=\"button\"><i class=\"fa fa-solid fa-house-person-leave\"></i><span>" . __('excuse_leave') . "</span></div>";
			}
			
			// Vacation arrival/departure
			if ($emprow["fly"] == 1) {
				$lastVac = lastVacIdGet($emprow['empid']);

				// Rejoining - submits a rejoin request that goes through the normal
				// approval chain (direct supervisor). Only for Fly annual/emergency vacations.
				if ($isHR || $is_system_admin || $isDeptHr) {
					$lastVacType = strtolower(trim((string)($lastVac['vac_type'] ?? '')));
					$lastFlyType = strtolower(trim((string)($lastVac['fly_type'] ?? '')));
					$isFlyRejoinEligible = ($lastVacType === 'fly' && in_array($lastFlyType, ['annual', 'emergency'], true));
					if ($lastVac && is_array($lastVac) && !empty($lastVac['vacid']) && !empty($lastVac['returndate']) && $isFlyRejoinEligible) {
						$ma['vacation'][] = "<div class=\"menu-item text-dark\" onclick=\"returnVacationRequest(" . (int)$lastVac['vacid'] . ", '" . htmlspecialchars($lastVac['returndate'], ENT_QUOTES) . "', '" . htmlspecialchars($emprow['empid'], ENT_QUOTES) . "', '" . htmlspecialchars($emprow['name'], ENT_QUOTES) . "')\" role=\"button\"><i class=\"fa fa-plane-arrival\"></i><span>" . __('rejoining', 'Rejoining') . "</span></div>";
					}
				}

				// Direct Rejoin (bypass approval chain entirely) - gated by the
				// 'direct_rejoin_bypass_approval' special access grant, independent
				// of role, so it can be granted to any specific employee.
				if (
					$lastVac && is_array($lastVac) && !empty($lastVac['vacid'])
					&& (
						!empty($is_system_admin)
						|| user_has_special_access($conDB, $empid ?? '', 'direct_rejoin_bypass_approval', $user_role ?? '', $user_type ?? '', $is_system_admin ?? false)
					)
				) {
					$ma['vacation'][] = "<div class=\"menu-item text-dark directRejoinBypass\" data-empid=\"" . htmlspecialchars($emprow['empid']) . "\" data-vacid=\"" . htmlspecialchars($lastVac['vacid']) . "\" data-empname=\"" . htmlspecialchars($emprow['name']) . "\" role=\"button\"><i class=\"fa fa-bolt\"></i><span>" . __('direct_rejoin', 'Direct Rejoin') . "</span></div>";
				}
			} else {
				if ($emprow['apd_status'] == 'approve' && $user_type != "dept_user") {
					$ma['vacation'][] = "<div class=\"menu-item text-dark\" onclick=\"window.location.href='add_vac_emp.php?emp_id=" . htmlspecialchars($emprow['empid']) . "'\" role=\"button\"><i class=\"fa fa-plane-departure\"></i><span>" . __('add_vacation') . "</span></div>";
				}
			}
			
			// Add Manual Vacation (HR/Admin only)
			/* if ($isHR || $is_system_admin || $isDeptHr) {
				$ma['vacation'][] = "<div class=\"menu-item text-info\" onclick=\"addManualVacationHistory(" . (int)$emprow['empid'] . ", '" . htmlspecialchars($emprow['name'] ?? '', ENT_QUOTES) . "', " . (int)$emprow['country'] . ");\" role=\"button\"><i class=\"fa fa-plus-circle\"></i><span>" . __('add_manual_vacation', 'Add Manual Vacation') . "</span></div>";
			} */
		}
	}
	
	// ADMIN ACTIONS
	if ($is_system_admin || $isDeptHr || $isHR || $can_modify_employee) {
		// Create Login
		if ($is_system_admin && empty($emprow['av_dept'])) {
			$ma['manage'][] = "<div class=\"menu-item text-dark createUserDeptAjax\" data-emp_id=\"" . htmlspecialchars($emprow['empid']) . "\" role=\"button\"><i class=\"fa fa-user-shield\"></i><span>" . __('create_login') . "</span></div>";
		}

		// Employee Profile - the employee's own self-service profile page (profile.php), read-only for
		// the viewer; profile.php re-checks access with canEmployeeSupervisorAccess().
		if ($current_page_name !== 'profile.php') {
			$ma['manage'][] = "<div class=\"menu-item text-info\" onclick=\"window.open('profile.php?emp_id=" . rawurlencode((string)$emprow['empid']) . "', '_blank')\" role=\"button\"><i class=\"fa fa-id-badge\"></i><span>" . __('employee_profile', 'Employee Profile') . "</span></div>";
		}

		// Edit Employee (system admin, dept hr, or 'access_edit_employee' special access grant)
		if (!in_array($current_page_name, ["edit_employee.php"]) && $can_modify_employee) {
			$ma['manage'][] = "<div class=\"menu-item text-primary\" onclick=\"window.location.href='edit_employee.php?emp_id=" . htmlspecialchars($emprow['empid']) . "'\" role=\"button\"><i class=\"fa fa-user-pen\"></i><span>" . __('edit') . "</span></div>";
		}

		// Add Note (system admin, dept hr, or 'access_edit_employee' special access grant)
		if (!in_array($current_page_name, ["edit_employee.php"]) && $can_modify_employee) {
			$ma['manage'][] = "<div class=\"menu-item text-info addnote\" data-emp_id=\"" . htmlspecialchars($emprow['empid']) . "\" role=\"button\"><i class=\"fa fa-book-user\"></i><span>" . __('note') . "</span></div>";
		}
		
		// Terminate (only on edit page)
		if ($user_type != "dept_user" && $current_page_name == "edit_employee.php") {
			$ma['exit'][] = "<div class=\"menu-item text-danger\" data-toggle=\"modal\" data-target=\".terminat\" role=\"button\"><i class=\"fa fa-user-large-slash\"></i><span>" . __('terminat') . "</span></div>";
		}
		
		// End of Service
		if ($is_system_admin || $isDeptHr || $isHR) {
			$ma['exit'][] = "<div class=\"menu-item text-secondary\" onclick=\"window.open('emp_end_of_service.php?emp_id=" . htmlspecialchars($emprow['empid']) . "', '_blank')\" role=\"button\"><i class=\"fa fa-solid fa-user-slash\"></i><span>" . __('create_end_of_service') . "</span></div>";
		}
	}
	// Apply Resignation
	if (!$isResignationBlocked) {
		$ma['exit'][] = "<div class=\"menu-item text-danger applyResignation\" data-emp_id=\"" . htmlspecialchars($emprow['empid']) . "\" data-emp_name=\"" . htmlspecialchars($emprow['name']) . "\" role=\"button\"><i class=\"fa fa-sign-out-alt\"></i><span>" . __('apply_resignation') . "</span></div>";
	}
	// Sync / Add to Microsoft Dynamics 365 (view_employee.php only) - runs the D365 widget's flow via
	// the 'd365:action-request' event; label switches to "Add to D365" when the worker isn't registered.
	if ($current_page_name === 'view_employee.php' && function_exists('d365_enabled') && d365_enabled($conDB)
		&& user_has_special_access($conDB, $logged_in_empid ?? ($empid ?? ''), 'd365_sync_employee', $user_role ?? '', $user_type ?? '', $is_system_admin ?? false)) {
		$ma['manage'][] = "<div class=\"menu-item text-teal d365MenuAction\" role=\"button\"><i class=\"fa fa-sync-alt\"></i><span>" . __('sync_to_d365', 'Sync to D365') . "</span></div>";
	}
	// Reset Login Password (view_employee.php only) - normal employees with a password login;
	// system admins + 'reset_employee_password' special access. resetEmployeePassword() lives in view_employee.php.
	if ($current_page_name === 'view_employee.php' && !empty($emprow['iqama'])
		&& user_has_special_access($conDB, $logged_in_empid ?? ($empid ?? ''), 'reset_employee_password', $user_role ?? '', $user_type ?? '', $is_system_admin ?? false)) {
		$pwStmt = mysqli_prepare($conDB, "SELECT `user_type` FROM `admin_login` WHERE `id_iqama` = ? LIMIT 1");
		$pwIqama = (string)$emprow['iqama'];
		mysqli_stmt_bind_param($pwStmt, "s", $pwIqama);
		mysqli_stmt_execute($pwStmt);
		$pwAccount = mysqli_fetch_assoc(mysqli_stmt_get_result($pwStmt));
		if ($pwAccount && $pwAccount['user_type'] === 'employee') {
			$ma['manage'][] = "<div class=\"menu-item text-danger\" onclick=\"Swal.close(); setTimeout(function(){ resetEmployeePassword(" . htmlspecialchars(json_encode($pwIqama), ENT_QUOTES) . ", " . htmlspecialchars(json_encode((string)($emprow['name'] ?? '')), ENT_QUOTES) . "); }, 100);\" role=\"button\"><i class=\"fa fa-key\"></i><span>" . __('reset_login_password', 'Reset Login Password') . "</span></div>";
		}
	}
	// Print Profile (openPrintProfileOptions() lives in view_employee.php)
	if (basename($_SERVER['PHP_SELF']) === 'view_employee.php') {
		$ma['manage'][] = "<div class=\"menu-item text-primary\" onclick=\"Swal.close(); setTimeout(openPrintProfileOptions, 100);\" role=\"button\"><i class=\"fa fa-print\"></i><span>" . __('print_profile', 'Print Profile') . "</span></div>";
	}
}
if ($emprow['status'] == 1) {
	$maTitles = [
		'requests' => ['fa-file-signature', __('ma_group_requests', 'Requests')],
		'vacation' => ['fa-umbrella-beach', __('ma_group_vacation', 'Vacation & Travel')],
		'manage'   => ['fa-id-card', __('ma_group_employee', 'Employee')],
		'exit'     => ['fa-door-open', __('ma_group_exit', 'Exit & Separation')],
	];
	foreach ($ma as $maKey => $maItems) {
		if (!$maItems) continue;
		$moreActionsHtml .= '<div class="ma-group ma-group-' . $maKey . '"><div class="ma-group-title"><i class="fa ' . $maTitles[$maKey][0] . '"></i>' . htmlspecialchars($maTitles[$maKey][1]) . '</div>'
			. '<div class="ma-grid">' . implode('', $maItems) . '</div></div>';
	}
	if ($moreActionsHtml === '') {
		$moreActionsHtml = '<div class="ma-empty"><i class="fa fa-circle-info"></i><p>' . __('no_actions_available', 'No actions available for this employee.') . '</p></div>';
	}
} elseif ((string)$emprow['status'] === '2') {
	// Draft: profile is still being prepared - only document upload, edit (to activate) and notes.
	$maDraft = [];
	if ($isDeptHr || $isHR || $is_system_admin) {
		$maDraft[] = "<div class=\"menu-item text-primary addEmpDocuAtter\" data-id=\"" . htmlspecialchars($emprow['eid']) . "\" data-emp_id=\"" . htmlspecialchars($emprow['empid']) . "\" role=\"button\"><i class=\"fa fa-solid fa-upload\"></i><span>" . __('add_documents') . "</span></div>";
	}
	if (!in_array($current_page_name, ["edit_employee.php"]) && !empty($can_modify_employee)) {
		$maDraft[] = "<div class=\"menu-item text-primary\" onclick=\"window.location.href='edit_employee.php?emp_id=" . htmlspecialchars($emprow['empid']) . "'\" role=\"button\"><i class=\"fa fa-user-pen\"></i><span>" . __('edit') . "</span></div>";
		$maDraft[] = "<div class=\"menu-item text-info addnote\" data-emp_id=\"" . htmlspecialchars($emprow['empid']) . "\" role=\"button\"><i class=\"fa fa-book-user\"></i><span>" . __('note') . "</span></div>";
	}
	$moreActionsHtml = '<div class="ma-empty" style="padding-bottom:8px;"><i class="fa fa-file-pen"></i><p>' . __('employee_is_draft', 'This employee is in Draft. Change status to Active from Edit Employee to enable all actions.') . '</p></div>';
	if ($maDraft) {
		$moreActionsHtml .= '<div class="ma-group ma-group-manage"><div class="ma-group-title"><i class="fa fa-id-card"></i>' . htmlspecialchars(__('ma_group_employee', 'Employee')) . '</div><div class="ma-grid">' . implode('', $maDraft) . '</div></div>';
	}
} else {
	$moreActionsHtml = '<div class="ma-empty"><i class="fa fa-user-lock"></i><p>' . __('employee_is_inactive') . '</p></div>';
}

$current_page_name = basename($_SERVER['PHP_SELF']);
if ($isEmployee !== true) {
	// Ensure IDs available
	$eid   = $emprow['eid'];
	$empid = $emprow['empid'];
	// QR Code filename pattern
	$qr_dir  = './assets/qrcodes/';
	$qr_file = $qr_dir . $eid . $empid . '.png';
	if (!file_exists($qr_file)) {
		// Attempt inline generation first (avoid unreliable redirect loops)
		if (!is_dir($qr_dir)) {
			@mkdir($qr_dir, 0775, true);
		}
		$qrlib_path = __DIR__ . '/qrcode/qrlib.php';
		if (is_readable($qrlib_path)) {
			require_once $qrlib_path;
			$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
			$host   = $_SERVER['HTTP_HOST'] ?? 'hr.almutlaksystem.com';
			$urlPath = $scheme . $host . '/emp_card/index.php?hashcode=' . urlencode($empid) . '&verification=' . urlencode($eid);
			QRcode::png($urlPath, $qr_file, QR_ECLEVEL_L, 4, 1, false);
		}
		// Redirect to dedicated generator only if still missing
		if (!file_exists($qr_file) && $current_page_name !== 'qrconfig_employee.php') {
			header('Location: qrconfig_employee.php?hashcode=' . urlencode($empid) . '&verification=' . urlencode($eid));
			exit();
		}
	}
	// Salary Information Check
	// if (($emprow['basic'] ?? 0) == 0 && $current_page_name !== 'add_emp_slry.php') {
	// 	header('Location: add_emp_slry.php?emp_id=' . urlencode($empid));
	// 	exit();
	// }
}

?>

<div class="row">
	<div class="col-xl-12">
		<!-- Profile Header Style Employee Card -->
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
		} elseif ((string)$emprow["status"] === "2") {
			$header_class .= ' draft';
			$status_label = __('draft', 'Draft');
			$status_icon = 'fa-file-pen';
		} elseif ($emprow["status"] == "0") {
			$header_class .= ' inactive';
			$status_label = __('inactive');
			$status_icon = 'fa-times-circle';
		}
		?>
		<?php
		// Live login presence (same check as employee_card.php's avatar ring):
		// any 'active' row in user_activity_log for this emp_id.
		$emp_top_is_online = false;
		$emp_top_emp_id = $emprow['empid'] ?? '';
		if ($emp_top_emp_id !== '') {
			$emp_top_online_stmt = mysqli_prepare($conDB, "SELECT 1 FROM `user_activity_log` WHERE `emp_id` = ? AND `status` = 'active' LIMIT 1");
			if ($emp_top_online_stmt) {
				mysqli_stmt_bind_param($emp_top_online_stmt, "s", $emp_top_emp_id);
				mysqli_stmt_execute($emp_top_online_stmt);
				$emp_top_is_online = (bool) mysqli_stmt_get_result($emp_top_online_stmt)->fetch_row();
				mysqli_stmt_close($emp_top_online_stmt);
			}
		}
		?>
		<?php
		$eh_join_ts = strtotime(str_replace('/', '-', (string)($emprow['joining_date'] ?? '')));
		$eh_dept = ($is_rtl ?? false) ? $emprow["deptnme_ar"] : $emprow["deptnme"];
		$eh_comp = ($is_rtl ?? false) ? ($emprow["compnme_ar"] ?? $emprow["compnme"]) : $emprow["compnme"];
		$eh_show_actions = !in_array($current_page_name, ["apply_vac_emp_dept.php", "add_vac_emp.php", "add_emp_docs.php"]) && in_array((string)$emprow["status"], ['1', '2'], true);
		?>
		<!-- Employee header (styles below, scoped to .emp-hero) -->
		<div class="emp-hero<?= str_replace('profile-header', '', $header_class) ?>">
			<div class="eh-top">
				<!-- Avatar (click = change photo) -->
				<label class="empAvatarShow eh-avatar<?= $emp_top_is_online ? ' is-online' : '' ?>" for="img-crop" data-id="<?= $emprow['eid'] ?>" data-emp_id="<?= $emprow['empid'] ?>" data-img="<?= $emprow['avatar'] ?>" data-name="<?= $emprow['name'] ?>" title="<?= $emp_top_is_online ? __('online', 'Online') : __('offline', 'Offline') ?>">
					<?php $displayImage = getAvatarImagePath($emprow['avatar'] ?? '', $emprow['sex'] ?? 1); ?>
					<img src="<?= $displayImage ?>" alt="<?= htmlspecialchars($emprow['name']) ?>" class="profile-avatar">
					<span class="eh-avatar-edit"><i class="fa fa-camera"></i></span>
					<?php if ($emp_top_is_online): ?><span class="eh-online"></span><?php endif; ?>
					<input type="file" name="image" class="image" hidden id="img-crop" accept="image/*">
				</label>

				<!-- Identity -->
				<div class="eh-identity">
					<div class="eh-name-row">
						<h1><?= getDisplayName($emprow['name']) ?></h1>
						<span class="eh-status"><i class="fa <?= $status_icon ?>"></i> <?= htmlspecialchars($status_label) ?></span>
					</div>
					<div class="eh-org"><i class="fa fa-building"></i> <?= htmlspecialchars((string)$eh_dept) ?><?php if (!empty($eh_comp)): ?><span class="eh-dot">&bull;</span><?= htmlspecialchars($eh_comp) ?><?php endif; ?></div>
					<div class="eh-chips">
						<span class="eh-chip"><i class="fa fa-passport"></i><small><?= __('iqama_id_label') ?></small><b><?= htmlspecialchars((string)$emprow['iqama']) ?></b></span>
						<?php if (!empty($emprow['mobile'])): ?>
						<a class="eh-chip" href="tel:<?= htmlspecialchars($emprow['mobile']) ?>"><i class="fa fa-phone"></i><small><?= __('mobile') ?></small><b dir="ltr"><?= htmlspecialchars($emprow['mobile']) ?></b></a>
						<?php endif; ?>
						<span class="eh-chip"><i class="fa fa-globe-asia"></i><small><?= __('nationality') ?></small><b><?= htmlspecialchars((string)(($is_rtl ?? false) ? $emprow["country_name_ar"] : $emprow["country_name"])) ?></b></span>
					</div>
				</div>

				<!-- QR -> employee card -->
				<a class="eh-qr" href="./emp_card/index.php?hashcode=<?= $emprow['empid'] ?>&verification=<?= $emprow['eid'] ?>" target="_blank" title="<?= __('view_employee_card') ?>">
					<img src="./assets/qrcodes/<?= $emprow['eid'] . $emprow['empid'] ?>.png" alt="<?= htmlspecialchars(__('qr_code', 'QR Code')) ?>" class="qr-code">
					<span><i class="fa fa-id-card"></i> <?= __('view_employee_card') ?></span>
				</a>
			</div>

			<div class="eh-bottom">
				<!-- Quick stats -->
				<div class="eh-stats">
					<div class="eh-stat"><i class="fa fa-hashtag"></i><div><b><?= htmlspecialchars($emprow['empid']) ?></b><small><?= __('employee_no') ?></small></div></div>
					<div class="eh-stat"><i class="fa fa-calendar-days"></i><div><b><?= htmlspecialchars((string)$emprow['vacation_days']) ?></b><small><?= __('vacation_days') ?></small></div></div>
					<div class="eh-stat"><i class="fa fa-umbrella-beach"></i><div><b><?= number_format($displayBalance, 2) ?></b><small><?= __('balance_vacations') ?></small></div></div>
					<div class="eh-stat" title="<?= $eh_join_ts ? date('d M Y', $eh_join_ts) : '' ?>"><i class="fa fa-calendar-check"></i><div><b><?= $eh_join_ts ? date('Y', $eh_join_ts) : '-' ?></b><small><?= __('joining_date') ?></small></div></div>
				</div>

				<?php if ($eh_show_actions): ?>
				<!-- D365 status + More Actions -->
				<div class="eh-actions more-actions-wrapper">
					<?php if (in_array($current_page_name, ['view_employee.php', 'edit_employee.php'], true)) {
						// Edit Employee: D365 status only - no Sync / Add to D365 buttons
						$d365WidgetStatusOnly = ($current_page_name === 'edit_employee.php');
						$d365WidgetClass = 'is-inline';
						// Sync / Add to D365 lives in the More Actions menu (d365MenuAction), not beside the card
						$d365WidgetButtonsInMenu = !$d365WidgetStatusOnly;
						include __DIR__ . '/D365/d365_widget.php'; // Microsoft Dynamics 365 status
					} ?>
					<?php if ($current_page_name !== 'edit_employee.php'): // no More actions while editing ?>
					<button type="button" id="moreActionsBtn" class="more-actions-btn">
						<i class="fa fa-grid-2"></i> <?= __('more_actions', 'More Actions') ?>
					</button>
					<?php endif; ?>
				</div>
				<?php endif; ?>
			</div>
		</div>
		<link href="assets/css/emp_hero.css?v=<?= @filemtime(__DIR__ . '/../assets/css/emp_hero.css') ?>" rel="stylesheet" type="text/css">

		<!--/ meta -->
		<?php /*if (mysqli_num_rows($getquerysocial) >= 1): ?>

	<div class="row">
	<?php
		$socquery = mysqli_query($conDB, "SELECT `social_list`.*, `social`.*, `social`.`id` AS `eslid` FROM `social_list` LEFT JOIN `social` ON `social`.`social_id` = `social_list`.`id` WHERE `social`.`emp_id`='".$emprow['empid']."' ORDER BY `social_list`.`id` ASC ");
		while($rec = mysqli_fetch_assoc($socquery)){
			$mainlink = parse_url($rec['link']);
			$social = explode('//',$mainlink['host'])[0];
			$link = ucfirst(explode('.',$social)[0]);
	?>
		<div class="col-md-2 col-xl-2">
	    <div class="card-box tilebox-one social">
		<?php if ($user_type == $access1 AND $current_page_name <> "view_employee.php"): ?>	
			<a href="javascript:void(0);" style="margin-top:-15px; margin-right: -15px;" class="float-right text-danger deleteAjax" data-id="<?=$rec['eslid']?>" data-tbl='social' data-file='0'>
				<i class='fa fa-minus-circle font-18 vertical-middle'></i>
			</a>
		<?php endif ?>
	    	<div onclick="window.open('<?=$rec["link"].$rec["s_link"]?>', '_blank')">            		
	            <i class="<?=$rec['icon']?> float-right" style="color:<?=$rec['color']?>; font-size: 48px"></i>
	            <h6 class="text-uppercase mt-0" style="color:<?=$rec['color']?>" ><?=$link?></h6>
	            <a href="javascript:void(0);" class="text-muted" style="font-size: 10px;">@<?=$rec['s_link']?></a>
	    	</div>
	    </div>
	</div>
	<?php } ?>
	</div>
<?php endif*/ ?>

		<!-- Page action bar (new GUI) - self-contained styles: several pages including this file don't load smart_request.css -->
		<style>
			.emp-actbar { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px; margin: 14px 0 18px; }
			.emp-actbar-group { display: flex; align-items: center; flex-wrap: wrap; gap: 8px; }
			.emp-actbar .eab-btn { display: inline-flex; align-items: center; justify-content: center; gap: 7px; height: 36px; padding: 0 14px; border-radius: 10px; font-size: 13px; font-weight: 600; border: 1px solid #d4d9e3; background: #fff; color: #334155; cursor: pointer; text-decoration: none !important; white-space: nowrap; box-shadow: 0 1px 2px rgba(15,23,42,.04); transition: background .15s, border-color .15s, color .15s, box-shadow .15s; }
			.emp-actbar .eab-btn i { font-size: 15px; line-height: 1; }
			.emp-actbar .eab-btn:hover { border-color: #6366f1; color: #4f46e5; }
			.emp-actbar .eab-btn:focus-visible { outline: 0; box-shadow: 0 0 0 3px rgba(99,102,241,.25); }
			.emp-actbar .eab-btn-primary { background: #6366f1; border-color: #6366f1; color: #fff !important; }
			.emp-actbar .eab-btn-primary:hover { background: #4f46e5; border-color: #4f46e5; }
			.emp-actbar .eab-btn-danger { background: #dc2626; border-color: #dc2626; color: #fff !important; }
			.emp-actbar .eab-btn-danger:hover { background: #b91c1c; border-color: #b91c1c; }
			html.app-dark .emp-actbar .eab-btn { background: var(--ad-surface, #161c2a); border-color: var(--ad-border, #2a3348); color: var(--ad-text, #d8dee9); box-shadow: none; }
			html.app-dark .emp-actbar .eab-btn:hover { border-color: #818cf8; color: #a5b4fc; }
			html.app-dark .emp-actbar .eab-btn-primary { background: #6366f1; border-color: #6366f1; color: #fff !important; }
			html.app-dark .emp-actbar .eab-btn-danger { background: #dc2626; border-color: #dc2626; color: #fff !important; }
			@media (max-width: 575px) { .emp-actbar .eab-btn { flex: 1 1 auto; } }
		</style>
		<div class="emp-actbar">
			<div class="emp-actbar-group">
				<button type="button" class="eab-btn" onclick="window.history.go(-1); return false;"><i class="mdi mdi-arrow-left"></i> <?= __('goto_back') ?></button>
			</div>
			<div class="emp-actbar-group">
				<?php if ($emprow["status"] == 1): ?>
					<?php if ($current_page_name <> "add_emp_slry.php" && $user_type <> "dept_user" && $emprow['seid'] == ""): ?>
						<a href="add_emp_slry.php?emp_id=<?= $emprow['empid'] ?>" class="eab-btn eab-btn-danger"><i class="mdi mdi-plus-circle-outline"></i> <?= __('add_details', 'Add Details') ?></a>
					<?php endif ?>
					<?php if (($is_system_admin || $isHR || $isDeptHr) && $showUpdateSalaryBtn && $current_page_name <> "add_emp_slry.php"): ?>
						<a href="javascript:void(0);" class="eab-btn eab-btn-primary updateSalaryBtn" data-emp_id="<?= $emprow['empid'] ?>" data-basic="<?= $emprow['basic'] ?>" data-housing="<?= $emprow['housing'] ?>" data-transport="<?= $emprow['transport'] ?>" data-food="<?= $emprow['food'] ?? 0 ?>" data-misc="<?= $emprow['misc'] ?? 0 ?>" data-cashier="<?= $emprow['cashier'] ?? 0 ?>" data-fuel="<?= $emprow['fuel'] ?? 0 ?>" data-tel="<?= $emprow['tel'] ?? 0 ?>" data-other="<?= $emprow['other'] ?? 0 ?>" data-guard="<?= $emprow['guard'] ?? 0 ?>"><i class="mdi mdi-cash-multiple"></i> <?= __('update_salary') ?></a>
					<?php endif ?>
				<?php elseif ((string)$emprow["status"] === "0"): ?>
					<a href="./end_of_service_print.php?emp_id=<?= $emprow['empid']; ?>" target="_blank" class="eab-btn eab-btn-danger"><i class="mdi mdi-printer"></i> <?= __('print_end_of_service') ?></a>
				<?php endif ?>
			</div>
		</div>
	</div>
</div>

<!-- /*************************************************/ -->
<?php include __DIR__ . '/emp_tenure_block.php'; ?>
<!-- /*************************************************/ -->

<!-- Force Salary Entry for Newly Registered Employees -->
<?php
$empid_check = $emprow['empid'] ?? $emprow['emp_id'];
if (!empty($empid_check) && ($is_system_admin || $isHR || $isDeptHr)) {
	// If no salary record exists, trigger the modal automatically (uses $hasSalaryRecord
	// computed above, which also drives the Update Salary button's own visibility).
	if (!$hasSalaryRecord):
?>
<script>
	// Auto-trigger the update salary button click on page load for newly registered employees
	window.addEventListener('load', function() {
		setTimeout(function() {
			var updateSalaryBtn = document.querySelector('.updateSalaryBtn');
			if (updateSalaryBtn) {
				// Set the auto_triggered flag
				updateSalaryBtn.dataset.auto_triggered = 'true';
				// Trigger click event for vanilla JavaScript
				updateSalaryBtn.click();
			}
		}, 500);
	});
</script>
<?php endif;
} ?>
<!-- End Force Salary Entry -->
