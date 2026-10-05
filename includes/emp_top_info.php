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
if ($emprow['status'] == 1 && $user_type === 'archiving') {
	// Archiving role: document upload only, none of the HR/management actions below.
	$moreActionsHtml .= "<div class=\"menu-item text-primary addEmpDocuAtter\" data-id=\"" . htmlspecialchars($emprow['eid']) . "\" data-emp_id=\"" . htmlspecialchars($emprow['empid']) . "\" role=\"button\"><i class=\"fa fa-solid fa-upload\"></i><span>" . __('add_documents') . "</span></div>";
} elseif ($emprow['status'] == 1) {
	// HR ACTIONS
	$hr_actions = '';

	// Add Documents (HR or Admin)
	if ($isDeptHr || $isHR || $is_system_admin) {
		$hr_actions .= "<div class=\"menu-item text-primary addEmpDocuAtter\" data-id=\"" . htmlspecialchars($emprow['eid']) . "\" data-emp_id=\"" . htmlspecialchars($emprow['empid']) . "\" role=\"button\"><i class=\"fa fa-solid fa-upload\"></i><span>" . __('add_documents') . "</span></div>";
	}
	
	// Apply Loan (HR/Admin only, if no active loan)
	if (empty($emprow['has_active_regular_loan']) && !$isLoanBlocked /*&& ($is_system_admin || $isDeptHr || $isHR)*/) {
		$hr_actions .= "<div class=\"menu-item text-warning applyLoan\" data-emp_id=\"" . htmlspecialchars($emprow['empid']) . "\" role=\"button\"><i class=\"fa fa-money-bill-trend-up\"></i><span>" . __('apply_loan') . "</span></div>";
	}
	
	if ($hr_actions) {
		$moreActionsHtml .= $hr_actions;
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
		$moreActionsHtml .= "<div class=\"menu-item text-info\" onclick=\"openSalaryIncrementApplyModal('" . htmlspecialchars($emprow['empid']) . "', '" . htmlspecialchars($emprow['iqama'] ?? '') . "', '" . htmlspecialchars($emprow['name'] ?? '', ENT_QUOTES) . "', '" . htmlspecialchars($deptNameForSalaryIncrement, ENT_QUOTES) . "', '" . htmlspecialchars($emprow['joining_date'] ?? '') . "')\" role=\"button\"><i class=\"fa fa-arrow-trend-up\"></i><span>" . __('apply_salary_increment', 'Apply Salary Increment') . "</span></div>";
	}

	// Request Employee Transfer (Direct Supervisor only, requesting someone who isn't already their report)
	if ($can_request_employee_transfer) {
		$moreActionsHtml .= "<div class=\"menu-item text-primary\" onclick=\"openEmployeeTransferModal('" . htmlspecialchars((string)$empid, ENT_QUOTES) . "', '" . htmlspecialchars($emprow['empid'], ENT_QUOTES) . "')\" role=\"button\"><i class=\"fa fa-people-arrows\"></i><span>" . __('request_employee_transfer', 'Request Employee Transfer') . "</span></div>";
	}

	// *IT ACTIONS
	/* if ($isItAssistant || $is_system_admin) {
		$moreActionsHtml .= "<div class=\"menu-item text-dark\" onclick=\"assignAsset('" . htmlspecialchars($emprow['empid']) . "')\" role=\"button\"><i class=\"fa fa-solid fa-project-diagram\"></i><span>" . __('assign_asset') . "</span></div>";
	} */
	
	// VACATION & LEAVE ACTIONS
	if ($user_dept == $emprow['dept'] || $is_system_admin || $isDeptHr || $isHR) {
		if ($emprow['emp_sup_type'] != "man_power") {
			// Annual Vacation
			if ($emprow['apd_status'] != 'approve' && !$isAllVacationBlocked /*&& $emprow["fly"] == 0*/ ) {
				$allowEmergencyVacation = ((string)($emprow['allow_emergency_vacation'] ?? '0') === '1') ? 1 : 0;
				$allowVacSalaryBelowMinDays = ((string)($emprow['allow_vacation_salary_below_min_days'] ?? '0') === '1') ? 1 : 0;
				$moreActionsHtml .= "<div class=\"menu-item text-info applyvacationAtter\" data-empid=\"" . htmlspecialchars($emprow['empid']) . "\" data-dept=\"" . htmlspecialchars($emprow['dept']) . "\" data-country=\"" . htmlspecialchars($emprow['country']) . "\" data-balance=\"{$displayBalance}\" data-allow-emergency=\"{$allowEmergencyVacation}\" data-allow-vac-salary-below-min=\"{$allowVacSalaryBelowMinDays}\" data-block-annual=\"" . ($isVacationAnnualBlocked ? 1 : 0) . "\" data-block-emergency=\"" . ($isVacationEmergencyBlocked ? 1 : 0) . "\" data-block-local=\"" . ($isVacationLocalBlocked ? 1 : 0) . "\" data-block-encashed=\"" . ($isVacationEncashedBlocked ? 1 : 0) . "\" role=\"button\"><i class=\"fa fa-user-chart\"></i><span>" . __('apply_annual_vacation') . "</span></div>";
			}
			
			// Business Trip Request
			if (!$isBusinessTripBlocked) {
				$moreActionsHtml .= "<div class=\"menu-item text-warning\" onclick=\"openBusinessTripApplyModal('" . htmlspecialchars($emprow['empid']) . "', '" . htmlspecialchars($emprow['dept']) . "', '" . htmlspecialchars($emprow['country']) . "')\" role=\"button\"><i class=\"fa fa-plane\"></i><span>" . __('apply_business_trip', 'Apply Business Trip') . "</span></div>";
			}

			// Excuse Leave
			if (!$isExcuseLeaveBlocked) {
				$moreActionsHtml .= "<div class=\"menu-item text-success applyLeaveRequest\" data-empid=\"" . htmlspecialchars($emprow['empid']) . "\" role=\"button\"><i class=\"fa fa-solid fa-house-person-leave\"></i><span>" . __('excuse_leave') . "</span></div>";
			}
			
			// Vacation arrival/departure
			if ($emprow["fly"] == 1) {
				$lastVac = lastVacIdGet($emprow['empid']);

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
					$moreActionsHtml .= "<div class=\"menu-item text-dark directRejoinBypass\" data-empid=\"" . htmlspecialchars($emprow['empid']) . "\" data-vacid=\"" . htmlspecialchars($lastVac['vacid']) . "\" data-empname=\"" . htmlspecialchars($emprow['name']) . "\" role=\"button\"><i class=\"fa fa-bolt\"></i><span>" . __('direct_rejoin', 'Direct Rejoin') . "</span></div>";
				}
			} else {
				if ($emprow['apd_status'] == 'approve' && $user_type != "dept_user") {
					$moreActionsHtml .= "<div class=\"menu-item text-dark\" onclick=\"window.location.href='add_vac_emp.php?emp_id=" . htmlspecialchars($emprow['empid']) . "'\" role=\"button\"><i class=\"fa fa-plane-departure\"></i><span>" . __('add_vacation') . "</span></div>";
				}
			}
			
			// Add Manual Vacation (HR/Admin only)
			/* if ($isHR || $is_system_admin || $isDeptHr) {
				$moreActionsHtml .= "<div class=\"menu-item text-info\" onclick=\"addManualVacationHistory(" . (int)$emprow['empid'] . ", '" . htmlspecialchars($emprow['name'] ?? '', ENT_QUOTES) . "', " . (int)$emprow['country'] . ");\" role=\"button\"><i class=\"fa fa-plus-circle\"></i><span>" . __('add_manual_vacation', 'Add Manual Vacation') . "</span></div>";
			} */
		}
	}
	
	// ADMIN ACTIONS
	if ($is_system_admin || $isDeptHr || $isHR || $can_modify_employee) {
		// Create Login
		if ($is_system_admin && empty($emprow['av_dept'])) {
			$moreActionsHtml .= "<div class=\"menu-item text-dark createUserDeptAjax\" data-emp_id=\"" . htmlspecialchars($emprow['empid']) . "\" role=\"button\"><i class=\"fa fa-user-shield\"></i><span>" . __('create_login') . "</span></div>";
		}

		// Edit Employee (system admin, dept hr, or 'access_edit_employee' special access grant)
		if (!in_array($current_page_name, ["edit_employee.php"]) && $can_modify_employee) {
			$moreActionsHtml .= "<div class=\"menu-item text-primary\" onclick=\"window.location.href='edit_employee.php?emp_id=" . htmlspecialchars($emprow['empid']) . "'\" role=\"button\"><i class=\"fa fa-user-pen\"></i><span>" . __('edit') . "</span></div>";
		}

		// Add Note (system admin, dept hr, or 'access_edit_employee' special access grant)
		if (!in_array($current_page_name, ["edit_employee.php"]) && $can_modify_employee) {
			$moreActionsHtml .= "<div class=\"menu-item text-info addnote\" data-emp_id=\"" . htmlspecialchars($emprow['empid']) . "\" role=\"button\"><i class=\"fa fa-book-user\"></i><span>" . __('note') . "</span></div>";
		}
		
		// Terminate (only on edit page)
		if ($user_type != "dept_user" && $current_page_name == "edit_employee.php") {
			$moreActionsHtml .= "<div class=\"menu-item text-danger\" data-toggle=\"modal\" data-target=\".terminat\" role=\"button\"><i class=\"fa fa-user-large-slash\"></i><span>" . __('terminat') . "</span></div>";
		}
		
		// End of Service
		if ($is_system_admin || $isDeptHr || $isHR) {
			$moreActionsHtml .= "<div class=\"menu-item text-secondary\" onclick=\"window.open('emp_end_of_service.php?emp_id=" . htmlspecialchars($emprow['empid']) . "', '_blank')\" role=\"button\"><i class=\"fa fa-solid fa-user-slash\"></i><span>" . __('create_end_of_service') . "</span></div>";
		}
	}
	// Apply Resignation
	if (!$isResignationBlocked) {
		$moreActionsHtml .= "<div class=\"menu-item text-danger applyResignation\" data-emp_id=\"" . htmlspecialchars($emprow['empid']) . "\" data-emp_name=\"" . htmlspecialchars($emprow['name']) . "\" role=\"button\"><i class=\"fa fa-sign-out-alt\"></i><span>" . __('apply_resignation') . "</span></div>";
	}
} else {
	$moreActionsHtml = '<div style="padding:24px; text-align:center; color: #6c757d;"><p>' . __('employee_is_inactive') . '</p></div>';
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
		<div class="<?= $header_class ?>">
			<div class="container-custom">
				<!-- Avatar -->
				<label class="empAvatarShow" for="img-crop" data-id="<?= $emprow['eid'] ?>" data-emp_id="<?= $emprow['empid'] ?>" data-img="<?= $emprow['avatar'] ?>" data-name="<?= $emprow['name'] ?>" style="margin-bottom: 0; cursor: pointer; position: relative; display: inline-block; line-height: 0;" title="<?= $emp_top_is_online ? __('online', 'Online') : __('offline', 'Offline') ?>">
					<?php
					// Get avatar display path using centralized helper function
					$displayImage = getAvatarImagePath($emprow['avatar'] ?? '', $emprow['sex'] ?? 1);
					?>
					<img src="<?= $displayImage ?>" alt="<?= htmlspecialchars($emprow['name']) ?>" class="profile-avatar" style="<?= $emp_top_is_online ? 'box-shadow: 0 0 0 4px #28a745;' : '' ?>">
					<?php if ($emp_top_is_online): ?><span style="position: absolute; bottom: 2px; <?= ($is_rtl ?? false) ? 'left' : 'right' ?>: 2px; width: 20px; height: 20px; border-radius: 50%; background: #28a745; border: 3px solid #fff; z-index: 5; box-shadow: 0 2px 6px rgba(0,0,0,0.25);"></span><?php endif; ?>
					<input type="file" name="image" class="image" hidden id="img-crop" accept="image/*">
				</label>

				<!-- Employee Info -->
				<div class="profile-header-info">
					<h1><?= getDisplayName($emprow['name']) ?></h1>
					<p><i class="fa fa-building"></i> <?= htmlspecialchars(($is_rtl ?? false ? $emprow["deptnme_ar"] : $emprow["deptnme"]) . " - " . (($is_rtl ?? false ? ($emprow["compnme_ar"] ?? $emprow["compnme"]) : $emprow["compnme"]) ?? '')) ?></p>
					<p><i class="fa fa-passport"></i> <?= __('iqama_id_label') ?>: <?= htmlspecialchars($emprow['iqama']) ?></p>
					<p><i class="fa fa-phone-laptop"></i> <?= __('mobile') ?>: <?= htmlspecialchars($emprow['mobile']) ?></p>
					<p><i class="fa fa-globe-asia"></i> <?= __('nationality') ?>: <?= ($is_rtl ?? false ? $emprow["country_name_ar"] : $emprow["country_name"]) ?></p>
				</div>

				<!-- Quick Stats -->
				<div class="profile-quick-stats">
					<div class="stat-item">
						<div class="stat-number"><?= htmlspecialchars($emprow['empid']) ?></div>
						<div class="stat-label"><?= __('employee_no') ?></div>
					</div>
					<div class="stat-item">
						<div class="stat-number"><?= htmlspecialchars($emprow['vacation_days']) ?></div>
						<div class="stat-label"><?= __('vacation_days') ?></div>
					</div>
					<div class="stat-item">
						<div class="stat-number">
							<?php
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
							echo number_format($displayBalance, 2);
							?>
						</div>
						<div class="stat-label"><?= __('balance_vacations') ?></div>
					</div>
					<div class="stat-item">
						<div class="stat-number"><?= date('Y', strtotime(str_replace('/', '-', $emprow['joining_date']))) ?></div>
						<div class="stat-label"><?= __('joining_date') ?></div>
					</div>
				</div>

				<!-- QR Code + Actions stacked vertically -->
				<div class="qr-actions-block" style="display:flex; flex-direction:column; align-items:center; gap:10px;">
					<a href="./emp_card/index.php?hashcode=<?= $emprow['empid'] ?>&verification=<?= $emprow['eid'] ?>" target="_blank" title="<?= __('view_employee_card') ?>" style="display:inline-block;">
						<img src="./assets/qrcodes/<?= $emprow['eid'] . $emprow['empid'] ?>.png" alt="QR Code" class="qr-code">
					</a>
					<?php if (!in_array($current_page_name, ["apply_vac_emp_dept.php", "add_vac_emp.php", "add_emp_docs.php"])) : ?>
						<?php if ($emprow["status"] == 1) : ?>
						<div class="more-actions-wrapper" style="text-align:center; position:relative;">
							<?php if (in_array($current_page_name, ['view_employee.php', 'edit_employee.php'], true)) :
								// Status pill for everyone; Sync / Add buttons only for system admins + 'd365_sync_employee' special access
								$d365CanSync = user_has_special_access($conDB, $empid ?? '', 'd365_sync_employee', $user_role ?? '', $user_type ?? '', $is_system_admin ?? false);
								if (empty($_SESSION['d365_csrf'])) {
									$_SESSION['d365_csrf'] = bin2hex(random_bytes(16));
								} ?>
								<!-- Dynamics 365 status / Add / Sync (filled by the script below, see includes/ajaxFile/d365_employee.php) -->
								<div id="d365Widget" class="d365-widget" data-emp="<?= htmlspecialchars($emprow['empid']) ?>" data-csrf="<?= htmlspecialchars($_SESSION['d365_csrf']) ?>" data-can-sync="<?= $d365CanSync ? '1' : '0' ?>">
									<span class="d365-pill is-loading"><span class="d365-logo"><i></i><i></i><i></i><i></i></span><span class="d365-txt"><b>Microsoft Dynamics 365</b><small><i class="fa fa-spinner fa-spin"></i> Checking...</small></span></span>
								</div>
							<?php endif; ?>
							<button type="button" id="moreActionsBtn" class="more-actions-btn">
								<i class="fa fa-bars"></i> <?= __('more') ?>
							</button>
						</div>
						<?php endif; ?>
					<?php endif; ?>
				</div>
			</div>
		</div>

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

		<div class="row">
			<div class="col-sm-6">
				<button action="action" onclick="window.history.go(-1); return false;" type="button" class="btn-sm btn btn-danger waves-effect float-left btn-rounded"><i class="fa fa-angle-double-left "></i> <?= __('goto_back') ?></button>
			</div>
			<div class="col-sm-6">
				<div class="btn-group float-right" role="group" aria-label="Edit Button">
					<?php if ($emprow["status"] == 1): ?>

						<?php if ($current_page_name <> "add_emp_slry.php") {
							if ($user_type <> "dept_user") {
								if ($emprow['seid'] == "") { ?>
									<a href="add_emp_slry.php?emp_id=<?= $emprow['empid'] ?>" class="btn-sm btn btn-danger waves-effect btn-rounded">
										Add Details
									</a>
									<?php } else {
									if ($current_page_name <> "add_emp_slry.php") { ?>
						<?php }
								}
							}
						} ?>
						<?php /* if ($emprow['empsocialcount'] < 9 || $is_system_admin): ?>
							<a href="javascript:void(0);" class="btn-sm btn btn-info waves-effect btn-rounded addSocial" data-emp_id="<?= $emprow['empid'] ?>">
								Add Social Media <i class="mdi mdi-link-variant"></i>
							</a>
						<?php endif ?>
						<?php if (!$emprow['description'] || $is_system_admin): ?>
							<a href="javascript:void(0);" class="btn-sm btn btn-dark waves-effect btn-rounded addPortfolio" data-emp_id="<?= $emprow['empid'] ?>">
								Add Portfolio Dedails <i class="mdi mdi mdi-account-card-details"></i>
							</a>
						<?php endif */ ?>
					<?php if (($is_system_admin || $isHR || $isDeptHr) && $showUpdateSalaryBtn): ?>
						<?php if ($current_page_name <> "add_emp_slry.php"): ?>
							<a href="javascript:void(0);" class="btn-sm btn btn-secondary waves-effect btn-rounded updateSalaryBtn" data-emp_id="<?= $emprow['empid'] ?>" data-basic="<?= $emprow['basic'] ?>" data-housing="<?= $emprow['housing'] ?>" data-transport="<?= $emprow['transport'] ?>" data-food="<?= $emprow['food'] ?? 0 ?>" data-misc="<?= $emprow['misc'] ?? 0 ?>" data-cashier="<?= $emprow['cashier'] ?? 0 ?>" data-fuel="<?= $emprow['fuel'] ?? 0 ?>" data-tel="<?= $emprow['tel'] ?? 0 ?>" data-other="<?= $emprow['other'] ?? 0 ?>" data-guard="<?= $emprow['guard'] ?? 0 ?>">
								<?= __('update_salary') ?> <i class="mdi mdi-inbox-arrow-up"></i>
							</a>
						<?php endif ?>
					<?php endif ?>
					<?php else: ?>
						<a href="./end_of_service_print.php?emp_id=<?= $emprow['empid']; ?>" target="_blank" class="btn-sm btn btn-danger waves-effect btn-rounded">
							<?= __('print_end_of_service') ?> <i class="mdi mdi-printer"></i>
						</a>
					<?php endif ?>
				</div>
			</div>
		</div>

		<br>
	</div>
</div>

<!-- /*************************************************/ -->
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
				<?php if (!empty($canViewEosValue) && !empty($eos_estimate['success'])): ?>
				<div class="tenure-badge">
					<i class="fa fa-hand-holding-dollar"></i>
					<?= __('estimated_eos_value', 'Estimated EOS') ?>: <?= number_format($eos_estimate['eos_amount'], 2) ?> <?= __('sar', 'SAR') ?>
				</div>
				<?php endif; ?>
			</div>
		</div>
	</div>
<?php endif; ?>
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

<?php if (in_array($current_page_name, ['view_employee.php', 'edit_employee.php'], true)) : ?>
<!-- Dynamics 365 widget (status / Add to D365 / Sync to D365) - status for everyone, buttons for system admins + 'd365_sync_employee' special access, see includes/ajaxFile/d365_employee.php -->
<style>
	.d365-widget { position: absolute; right: 100%; top: 50%; transform: translateY(-50%); margin-right: 10px; display: flex; gap: 8px; align-items: center; white-space: nowrap; }
	[dir="rtl"] .d365-widget { right: auto; left: 100%; margin-right: 0; margin-left: 10px; }
	/* Status card: Microsoft logo + full product name + company / state line */
	.d365-widget .d365-pill { display: inline-flex; align-items: center; gap: 10px; padding: 6px 14px 6px 10px; border-radius: 10px; background: #fff; color: #1e293b; border: 1px solid rgba(15,23,42,.08); border-left: 4px solid #94a3b8; box-shadow: 0 2px 8px rgba(15,23,42,.18); line-height: 1.15; text-align: left; cursor: default; }
	[dir="rtl"] .d365-widget .d365-pill { border-left-width: 1px; border-right: 4px solid #94a3b8; text-align: right; padding: 6px 10px 6px 14px; }
	.d365-widget .d365-pill.is-ok { border-left-color: #16a34a; }
	.d365-widget .d365-pill.is-bad { border-left-color: #dc2626; cursor: help; }
	[dir="rtl"] .d365-widget .d365-pill.is-ok { border-right-color: #16a34a; }
	[dir="rtl"] .d365-widget .d365-pill.is-bad { border-right-color: #dc2626; }
	.d365-widget .d365-logo { display: grid; grid-template-columns: 9px 9px; gap: 2px; flex: none; }
	.d365-widget .d365-logo i { width: 9px; height: 9px; display: block; }
	.d365-widget .d365-logo i:nth-child(1) { background: #f25022; }
	.d365-widget .d365-logo i:nth-child(2) { background: #7fba00; }
	.d365-widget .d365-logo i:nth-child(3) { background: #00a4ef; }
	.d365-widget .d365-logo i:nth-child(4) { background: #ffb900; }
	.d365-widget .d365-txt { display: flex; flex-direction: column; gap: 2px; }
	.d365-widget .d365-txt b { font-size: 12.5px; font-weight: 700; letter-spacing: .1px; color: #0f172a; }
	.d365-widget .d365-txt small { display: inline-flex; align-items: center; gap: 5px; font-size: 11px; font-weight: 600; color: #64748b; }
	.d365-widget .d365-dot { width: 7px; height: 7px; border-radius: 50%; background: #94a3b8; flex: none; }
	.d365-widget .is-ok .d365-dot { background: #16a34a; box-shadow: 0 0 0 3px rgba(22,163,74,.18); }
	.d365-widget .is-bad .d365-dot { background: #dc2626; box-shadow: 0 0 0 3px rgba(220,38,38,.18); }
	.d365-widget .is-ok .d365-txt small { color: #15803d; }
	.d365-widget .is-bad .d365-txt small { color: #b91c1c; }
	.d365-widget .more-actions-btn { padding: 8px 14px; }
	.d365-widget .more-actions-btn.is-warn { background: rgba(245,158,11,.9); border-color: rgba(255,255,255,.4); }
	@media (max-width: 991px) { .d365-widget { position: static; transform: none; margin: 0 0 8px; justify-content: center; } }
</style>
<script>
(function () {
	var box = document.getElementById('d365Widget');
	if (!box) return;
	var empId = box.getAttribute('data-emp');
	var csrf = box.getAttribute('data-csrf');
	var canSync = box.getAttribute('data-can-sync') === '1';
	var ENDPOINT = './includes/ajaxFile/d365_employee.php';
	var last = null;

	function esc(s) { var d = document.createElement('div'); d.textContent = s == null ? '' : String(s); return d.innerHTML; }
	var POP = { popup: 'sr-addline-popup sr-page' };
	// New GUI popups need smart_request.css + icons.css (not every page that includes this header links them)
	function ensureCss() {
		[['smart_request.css', 'assets/css/smart_request.css'], ['icons.css', 'assets/css/icons.css']].forEach(function (c) {
			if (document.querySelector('link[href*="' + c[0] + '"]')) return;
			var l = document.createElement('link'); l.rel = 'stylesheet'; l.href = c[1]; document.head.appendChild(l);
		});
	}
	function withSwal(cb) {
		ensureCss();
		if (window.Swal) { cb(); return; }
		var s = document.createElement('script');
		s.src = './plugins/sweet-alert/v11/sweetalert2.all.min.js';
		s.onload = cb;
		document.head.appendChild(s);
	}
	function call(action, extra) {
		var fd = new FormData();
		fd.append('action', action);
		fd.append('csrf', csrf);
		fd.append('emp_id', empId);
		Object.keys(extra || {}).forEach(function (k) { fd.append(k, extra[k]); });
		return fetch(ENDPOINT, { method: 'POST', body: fd, credentials: 'same-origin' })
			.then(function (r) { return r.json().catch(function () { throw new Error('Server error (HTTP ' + r.status + ')'); }); });
	}

	// Status card: tone = ok / bad / loading, line = company / state text (already escaped)
	function pill(tone, line, title) {
		return '<span class="d365-pill is-' + tone + '"' + (title ? ' title="' + esc(title) + '"' : '') + '>'
			+ '<span class="d365-logo"><i></i><i></i><i></i><i></i></span>'
			+ '<span class="d365-txt"><b>Microsoft Dynamics 365</b><small>' + (tone === 'loading' ? '<i class="fa fa-spinner fa-spin"></i>' : '<span class="d365-dot"></span>') + line + '</small></span></span>';
	}

	function render(res) {
		last = res;
		if (!res || (res.ok === false && !res.status)) {
			box.innerHTML = pill('bad', 'Status unavailable', res && res.error)
				+ '<button type="button" class="more-actions-btn" data-act="refresh" title="Retry"><i class="fa fa-redo"></i></button>';
			return;
		}
		var st = res.status || {};
		var env = (res.environment || '').toUpperCase();
		var writeAttr = res.can_write ? '' : ' disabled title="Writes are off (App Settings > D365 Config)"';
		if (st.status === 'registered') {
			var info = 'Microsoft Dynamics 365 (' + env + ') worker ' + (st.d365_name || empId)
				+ (st.synced_at ? '\nLast sync: ' + st.synced_at : '\nNot synced from the app yet')
				+ (st.last_error ? '\n' + st.last_error : '');
			var line = (st.legal_entity ? esc(st.legal_entity) + ' · ' : '') + (st.last_error ? 'Sync error' : (st.synced_at ? 'Synced' : 'Registered'));
			box.innerHTML = pill(st.last_error ? 'bad' : 'ok', line, info)
				+ (canSync ? '<button type="button" class="more-actions-btn" data-act="sync"' + writeAttr + '><i class="fa fa-sync-alt"></i> Sync to D365</button>' : '');
		} else {
			var why = st.status === 'missing' ? 'Not registered in Microsoft Dynamics 365 ' + env : (st.last_error || 'Registration failed');
			box.innerHTML = pill('bad', 'Not registered' + (env ? ' · ' + esc(env) : ''), why)
				+ (canSync ? '<button type="button" class="more-actions-btn is-warn" data-act="register"' + writeAttr + '><i class="fa fa-plus"></i> Add to D365</button>' : '');
		}
	}

	function load(refresh) {
		box.innerHTML = pill('loading', 'Checking...');
		call('status', refresh ? { refresh: 1 } : {}).then(render).catch(function (e) { render({ ok: false, error: e.message }); });
	}

	function doRegister() {
		var sug = (last && last.suggest) || { company: '', entities: [] };
		withSwal(function () {
			var opts = '<option value="">- choose -</option>' + (sug.entities || []).map(function (en) {
				return '<option value="' + esc(en) + '"' + (en === sug.company ? ' selected' : '') + '>' + esc(en) + '</option>';
			}).join('');
			var env = esc((last.environment || '').toUpperCase());
			Swal.fire({
				title: 'Add ' + esc(empId) + ' to D365',
				html: '<div class="sr-form">'
					+ '<div class="sr-notice tone-sky" style="margin-bottom:12px"><i class="mdi mdi-information-outline"></i><div>Creates the worker and employment in D365 <b>' + env + '</b> from this employee&#39;s HR data.</div></div>'
					+ (last.status && last.status.last_error ? '<div class="sr-notice tone-red" style="margin-bottom:12px"><i class="mdi mdi-alert-circle-outline"></i><div><b>Last error:</b> ' + esc(last.status.last_error) + '</div></div>' : '')
					+ '<div class="sr-fsec mb-0">'
					+ '<div class="sr-fsec-head"><span><i class="mdi mdi-domain"></i> D365 worker</span>' + (env ? '<span class="sr-pill sr-pill-xs ' + (env === 'PROD' || env === 'PRODUCTION' ? 'tone-red' : 'tone-amber') + '">' + env + '</span>' : '') + '</div>'
					+ '<div class="sr-fgrid">'
					+ '<div class="sr-fcol c-6"><label>Employee ID</label><input type="text" class="form-control" value="' + esc(empId) + '" readonly></div>'
					+ '<div class="sr-fcol c-6"><label for="d365Company">D365 company <span class="text-danger">*</span></label><select id="d365Company" class="form-control">' + opts + '</select>'
					+ (sug.company ? '<span class="sr-fhint">Suggested from the app company: <b>' + esc(sug.company) + '</b></span>' : '') + '</div>'
					+ '</div></div></div>',
				showCancelButton: true,
				confirmButtonText: '<i class="mdi mdi-account-plus"></i> Add to D365',
				confirmButtonColor: (window.APP_COLORS && APP_COLORS.primary) || undefined,
				cancelButtonColor: (window.APP_COLORS && APP_COLORS.danger_dark) || undefined,
				width: '560px',
				customClass: POP,
				allowOutsideClick: false,
				showLoaderOnConfirm: true,
				preConfirm: function () {
					var company = document.getElementById('d365Company').value;
					if (!company) { Swal.showValidationMessage('Choose the D365 company'); return false; }
					return call('register', { company: company }).then(function (res) {
						if (!res.ok) { Swal.showValidationMessage(res.error || 'Failed'); if (res.status) render(res); return false; }
						return res;
					}).catch(function (e) { Swal.showValidationMessage(e.message); return false; });
				}
			}).then(function (r) {
				if (!r.isConfirmed) return;
				render(r.value);
				if (r.value.warning) {
					Swal.fire({ icon: 'warning', title: 'Added to D365', text: r.value.warning, customClass: POP });
				} else {
					Swal.fire({ icon: 'success', title: 'Added to D365', text: 'Worker, employment, contact details and bank account created.', timer: 2200, showConfirmButton: false, customClass: POP });
				}
				// D365 tab (view_employee.php) re-reads D365 so it shows the new worker
				document.dispatchEvent(new CustomEvent('d365:synced', { detail: { emp: empId } }));
			});
		});
	}

	function doSync() {
		withSwal(function () {
			var fields = ['Name', 'Birth date', 'Gender', 'Email', 'Mobile', 'Marital status', 'Salary bank account (IBAN)'];
			Swal.fire({
				title: 'Sync ' + esc(empId) + ' to D365?',
				html: '<div class="sr-form">'
					+ '<div class="sr-fsec mb-0">'
					+ '<div class="sr-fsec-head"><span><i class="mdi mdi-cloud-sync"></i> Sent from the HR app</span>' + (last && last.environment ? '<span class="sr-pill sr-pill-xs tone-amber">' + esc(String(last.environment).toUpperCase()) + '</span>' : '') + '</div>'
					+ '<div class="sr-fsec-body"><div class="d-flex flex-wrap" style="gap:6px">'
					+ fields.map(function (f) { return '<span class="sr-chip"><i class="mdi mdi-check"></i> ' + f + '</span>'; }).join('')
					+ '</div><span class="sr-fhint mt-2">These values overwrite the D365 worker record.</span></div>'
					+ '</div></div>',
				showCancelButton: true,
				confirmButtonText: '<i class="mdi mdi-cloud-sync"></i> Sync',
				confirmButtonColor: (window.APP_COLORS && APP_COLORS.primary) || undefined,
				cancelButtonColor: (window.APP_COLORS && APP_COLORS.danger_dark) || undefined,
				width: '560px',
				customClass: POP,
				allowOutsideClick: false,
				showLoaderOnConfirm: true,
				preConfirm: function () {
					return call('sync').then(function (res) {
						if (!res.ok) { Swal.showValidationMessage(res.error || 'Failed'); return false; }
						return res;
					}).catch(function (e) { Swal.showValidationMessage(e.message); return false; });
				}
			}).then(function (r) {
				if (!r.isConfirmed) return;
				render(r.value);
				var bankText = { created: 'Bank account added.', updated: 'Bank account IBAN updated.', unchanged: 'Bank account already up to date.' }[r.value.bank] || '';
				if (r.value.warning) {
					Swal.fire({ icon: 'warning', title: 'Synced to D365', text: r.value.warning, customClass: POP });
				} else {
					Swal.fire({ icon: 'success', title: 'Synced to D365', text: bankText, timer: 2200, showConfirmButton: false, customClass: POP });
				}
				// D365 tab (view_employee.php) re-reads D365 so it shows the new values
				document.dispatchEvent(new CustomEvent('d365:synced', { detail: { emp: empId } }));
			});
		});
	}
	// D365 tab "Sync to D365" button uses the same flow
	document.addEventListener('d365:sync-request', function () { if (canSync) doSync(); });
	document.addEventListener('d365:register-request', function () { if (canSync) doRegister(); });

	box.addEventListener('click', function (ev) {
		var b = ev.target.closest('[data-act]');
		if (!b || b.disabled) return;
		var act = b.getAttribute('data-act');
		if (act === 'register') { if (canSync) doRegister(); }
		else if (act === 'sync') { if (canSync) doSync(); }
		else load(true);
	});
	load(false);
})();
</script>
<?php endif; ?>