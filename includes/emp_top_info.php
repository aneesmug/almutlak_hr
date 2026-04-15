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
$current_page_name = basename($_SERVER['PHP_SELF']);

// Calculate employee modification permission
$can_modify_employee = (
	$is_system_admin || 
	$isDeptHr
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
if ($emprow['status'] == 1) {
	// HR ACTIONS
	$hr_actions = '';
	
	// Add Documents (HR only)
	if ($isDeptHr || $isHR || $is_system_admin) {
		$hr_actions .= "<div class=\"menu-item text-primary addEmpDocuAtter\" data-id=\"" . htmlspecialchars($emprow['eid']) . "\" data-emp_id=\"" . htmlspecialchars($emprow['empid']) . "\" role=\"button\"><i class=\"fa fa-solid fa-upload\"></i><span>" . __('add_documents') . "</span></div>";
	}
	
	// Apply Loan (HR/Admin only, if no active loan)
	if (empty($emprow['has_active_regular_loan']) /*&& ($is_system_admin || $isDeptHr || $isHR)*/) {
		$hr_actions .= "<div class=\"menu-item text-warning applyLoan\" data-emp_id=\"" . htmlspecialchars($emprow['empid']) . "\" role=\"button\"><i class=\"fa fa-money-bill-trend-up\"></i><span>" . __('apply_loan') . "</span></div>";
	}
	
	if ($hr_actions) {
		$moreActionsHtml .= $hr_actions;
	}
	
	// *IT ACTIONS
	/* if ($isItAssistant || $is_system_admin) {
		$moreActionsHtml .= "<div class=\"menu-item text-dark\" onclick=\"assignAsset('" . htmlspecialchars($emprow['empid']) . "')\" role=\"button\"><i class=\"fa fa-solid fa-project-diagram\"></i><span>" . __('assign_asset') . "</span></div>";
	} */
	
	// VACATION & LEAVE ACTIONS
	if ($user_dept == $emprow['dept'] || $is_system_admin || $isDeptHr || $isHR) {
		if ($emprow['emp_sup_type'] != "man_power") {
			// Annual Vacation
			if ($emprow['apd_status'] != 'approve' /*&& $emprow["fly"] == 0*/ ) {
				$moreActionsHtml .= "<div class=\"menu-item text-info applyvacationAtter\" data-empid=\"" . htmlspecialchars($emprow['empid']) . "\" data-dept=\"" . htmlspecialchars($emprow['dept']) . "\" data-country=\"" . htmlspecialchars($emprow['country']) . "\" data-balance=\"{$displayBalance}\" role=\"button\"><i class=\"fa fa-user-chart\"></i><span>" . __('apply_annual_vacation') . "</span></div>";
			}
			
			// Business Trip Request
			// $moreActionsHtml .= "<div class=\"menu-item text-warning\" onclick=\"openBusinessTripApplyModal('" . htmlspecialchars($emprow['empid']) . "', '" . htmlspecialchars($emprow['dept']) . "', '" . htmlspecialchars($emprow['country']) . "')\" role=\"button\"><i class=\"fa fa-plane\"></i><span>" . __('apply_business_trip', 'Apply Business Trip') . "</span></div>";
			
			// Excuse Leave
			$moreActionsHtml .= "<div class=\"menu-item text-success applyLeaveRequest\" data-empid=\"" . htmlspecialchars($emprow['empid']) . "\" role=\"button\"><i class=\"fa fa-solid fa-house-person-leave\"></i><span>" . __('excuse_leave') . "</span></div>";
			
			// Vacation arrival/departure
			if ($emprow["fly"] == 1) {
				if ($isHR || $is_system_admin || $isDeptHr) {
					$lastVac = lastVacIdGet($emprow['empid']);
					// Updated rejoin function to use new approval system with emp_id and emp_name parameters
					if ($lastVac && is_array($lastVac) && !empty($lastVac['vacid']) && !empty($lastVac['returndate'])) {
						$moreActionsHtml .= "<div class=\"menu-item text-dark\" onclick=\"returnVacationRequest(" . htmlspecialchars($lastVac['vacid']) . ", '" . htmlspecialchars($lastVac['returndate']) . "', '" . htmlspecialchars($emprow['empid']) . "', '" . htmlspecialchars($emprow['name']) . "')\" role=\"button\"><i class=\"fa fa-plane-arrival\"></i><span>" . __('rejoining') . "</span></div>";
					}
				}
			} else {
				if ($emprow['apd_status'] == 'approve' && $user_type != "dept_user") {
					$moreActionsHtml .= "<div class=\"menu-item text-dark\" onclick=\"window.location.href='add_vac_emp.php?emp_id=" . htmlspecialchars($emprow['empid']) . "'\" role=\"button\"><i class=\"fa fa-plane-departure\"></i><span>" . __('add_vacation') . "</span></div>";
				}
			}
			
			// Add Manual Vacation (HR/Admin only)
			if ($isHR || $is_system_admin || $isDeptHr) {
				$moreActionsHtml .= "<div class=\"menu-item text-info\" onclick=\"addManualVacationHistory(" . (int)$emprow['empid'] . ", '" . htmlspecialchars($emprow['name'] ?? '', ENT_QUOTES) . "', " . (int)$emprow['country'] . ");\" role=\"button\"><i class=\"fa fa-plus-circle\"></i><span>" . __('add_manual_vacation', 'Add Manual Vacation') . "</span></div>";
			}
		}
	}
	
	// ADMIN ACTIONS
	if ($is_system_admin || $isDeptHr || $isHR) {
		// Create Login
		if ($is_system_admin && empty($emprow['av_dept'])) {
			$moreActionsHtml .= "<div class=\"menu-item text-dark createUserDeptAjax\" data-emp_id=\"" . htmlspecialchars($emprow['empid']) . "\" role=\"button\"><i class=\"fa fa-user-shield\"></i><span>" . __('create_login') . "</span></div>";
		}
		
		// Edit Employee (only system admin and dept hr)
		if (!in_array($current_page_name, ["edit_employee.php"]) && $can_modify_employee && ($is_system_admin || $isDeptHr)) {
			$moreActionsHtml .= "<div class=\"menu-item text-primary\" onclick=\"window.location.href='edit_employee.php?emp_id=" . htmlspecialchars($emprow['empid']) . "'\" role=\"button\"><i class=\"fa fa-user-pen\"></i><span>" . __('edit') . "</span></div>";
		}
		
		// Add Note (only system admin and dept hr)
		if (!in_array($current_page_name, ["edit_employee.php"]) && $can_modify_employee && ($is_system_admin || $isDeptHr)) {
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
	$moreActionsHtml .= "<div class=\"menu-item text-danger applyResignation\" data-emp_id=\"" . htmlspecialchars($emprow['empid']) . "\" data-emp_name=\"" . htmlspecialchars($emprow['name']) . "\" role=\"button\"><i class=\"fa fa-sign-out-alt\"></i><span>" . __('apply_resignation') . "</span></div>";
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
		<div class="<?= $header_class ?>">
			<div class="container-custom">
				<!-- Avatar -->
				<label class="empAvatarShow" for="img-crop" data-id="<?= $emprow['eid'] ?>" data-emp_id="<?= $emprow['empid'] ?>" data-img="<?= $emprow['avatar'] ?>" data-name="<?= $emprow['name'] ?>" style="margin-bottom: 0; cursor: pointer;">
					<?php
					// Get avatar display path using centralized helper function
					$displayImage = getAvatarImagePath($emprow['avatar'] ?? '', $emprow['sex'] ?? 1);
					?>
					<img src="<?= $displayImage ?>" alt="<?= htmlspecialchars($emprow['name']) ?>" class="profile-avatar">
					<input type="file" name="image" class="image" hidden id="img-crop" accept="image/*">
				</label>

				<!-- Employee Info -->
				<div class="profile-header-info">
					<h1><?= getDisplayName($emprow['name']) ?></h1>
					<p><i class="fa fa-building"></i> <?= htmlspecialchars(($is_rtl ?? false ? $emprow["deptnme_ar"] : $emprow["deptnme"]) . " - " . getDisplayName($emprow["sectin_nme"])) ?></p>
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
						<div class="more-actions-wrapper" style="text-align:center;">
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
					<?php if ($is_system_admin || $isHR || $isDeptHr): ?>
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
			</div>
		</div>
	</div>
<?php endif; ?>
<!-- /*************************************************/ -->

<!-- Force Salary Entry for Newly Registered Employees -->
<?php
$empid_check = $emprow['empid'] ?? $emprow['emp_id'];
if (!empty($empid_check) && ($is_system_admin || $isHR || $isDeptHr)) {
	// Check if employee has no salary record in emp_salary table
	$checkSalaryStmt = $pdo->prepare("SELECT id FROM emp_salary WHERE emp_id = :emp_id LIMIT 1");
	$checkSalaryStmt->execute([':emp_id' => $empid_check]);
	$hasSalaryRecord = $checkSalaryStmt->fetch();
	
	// If no salary record exists, trigger the modal automatically
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