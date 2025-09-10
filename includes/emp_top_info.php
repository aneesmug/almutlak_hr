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

// --- 6. Forced Action Redirects for Employees ---
if ($emprow['user_type'] !== 'employee') {
	// -- QR Code Check --
	$eid = $emprow['id'];
	$empid = $emprow['emp_id'];
	$qr_file = "./assets/qrcodes/" . $eid . $empid . ".png";

	if (!file_exists($qr_file) && $current_page !== 'qrconfig_employee.php') {
		header("Location: qrconfig_employee.php?hashcode=" . urlencode($empid) . "&verification=" . urlencode($eid));
		exit();
	}
	// -- NEW: Salary Information Check --
	// If basic salary is 0 and we are not on the salary page, redirect.
	if (($emprow['basic'] ?? 0) == 0 && $current_page !== 'add_emp_slry.php') {
		header("Location: add_emp_slry.php?emp_id=" . urlencode($empid));
		exit();
	}
}

?>

<div class="row">
	<div class="col-xl-12">
		<!-- meta -->
		<div class="profile-user-box card-box <?= ($emprow["status"] == "1" && $emprow["fly"] == 0 ? "bg-dark" : ($emprow["fly"] == 1 ? "bg-warning" : "bg-danger")) ?>">
			<div class="row">
				<div class="col-sm-1">
					<label class="empAvatarShow" for="img-crop" data-id="<?= $emprow['eid'] ?>" data-emp_id="<?= $emprow['empid'] ?>" data-img="<?= $emprow['avatar'] ?>" data-name="<?= $emprow['name'] ?>" style="margin-bottom: 0 !important">
						<div>
							<img src="<?= $emprow['avatar'] ?>" alt="<?= htmlspecialchars($emprow['name']) ?>" class="thumb-lg rounded-circle emp_avat_img">
							<div class="empAvatar"><i class="fad fa-images-user duotone-danger"></i></div>
						</div>
					</label>
					<input type="file" name="image" class="image" hidden id="img-crop" accept="image/*">
				</div>

				<div class="col-sm-5">
					<div class="media-body text-white">
						<h4 class="mt-1 mb-1 font-18"><?= __('name') ?>: <span class="copyToClipboard"><?= htmlspecialchars($emprow['name']) ?></span> <i class="fa fa-clipboard"></i></h4>
						<p class="text-light mb-0"><?= __('joining_date') ?>: <?= date('M d Y', strtotime(str_replace('/', '-', $emprow['joining_date']))) ?></p>
						<p class="text-light mb-0"><?= __('mobile') ?>: <span class='copyToClipboard'><?= htmlspecialchars($emprow['mobile']) ?></span> <i class='fa fa-clipboard'></i></p>
						<p class="text-light mb-0"><?= __('vacation_days') ?>: <?= htmlspecialchars($emprow['vacation_days']) ?></p>
						<?php if ($emprow["status"] == 0) : ?>
							<p class="text-light mb-0">
								<?= __('terminated_reason') . ": " . ($is_rtl ?? false ? $emprow['leaving_reason_ar']:$emprow['leaving_reason']); ?>
							</p>
						<?php endif; ?>
					</div>
				</div>

				<div class="col-sm-2">
					<div class="media-body text-white float-right">
						<a href="./emp_card/index.php?hashcode=<?= $emprow['empid'] ?>&verification=<?= $emprow['eid'] ?>" target="_blank">
							<img src="./assets/qrcodes/<?= $emprow['eid'] . $emprow['empid'] ?>.png" alt="QR Code">
						</a>
					</div>
				</div>

				<div class="col-sm-4">
					<div class="text-left">
						<p class="text-light mb-0">
							<?= __('iqama_id') . ": <span class='copyToClipboard'>" . htmlspecialchars($emprow['iqama']) . "</span> <i class='fa fa-clipboard'></i>"; ?>
						</p>
						<p class="text-light mb-0"><?= __('employee_no') ?>: <span class='copyToClipboard'><?= htmlspecialchars($emprow['empid']) ?></span> <i class='fa fa-clipboard'></i></p>
						<p class="text-light mb-0"><?= __('department') ?>: <?= htmlspecialchars(($is_rtl ?? false ? $emprow["deptnme_ar"] : $emprow["deptnme"]) . " - " . $emprow["sectin_nme"]) ?></p>
						<p class="text-light mb-0"><?= __('nationality') ?>: <?= ($is_rtl ?? false ? $emprow["country_name_ar"] : $emprow["country_name"]) ?></p>
						<p class="text-light mb-0"><?= __('balance_vacations') ?>:
							<?php
							$finalvacd = $emprow["total_remaining_leave"];
							echo $finalvacd < 0 ? "<span class='badge badge-danger badge-pill'>" . $finalvacd . __('days') . " </span>" : $finalvacd . " " . __('days');
							?>
						</p>
						<?php if ($emprow["status"] == 0) : ?>
							<p class="text-light mb-0">
								<?=  __('terminated_date'). ": " . date('d M Y', strtotime($emprow["end_date"])); ?>
							</p>
						<?php endif; ?>
					</div>

					<?php if (!in_array($current_page_name, ["apply_vac_emp_dept.php", "add_vac_emp.php", "add_emp_docs.php"])) : ?>
						<div class="text-right">
							<?php if ($emprow["status"] == 1) : ?>
								<div class="btn-group" role="group" aria-label="Edit Button">
									<button type="button" class="btn btn-sm btn-light dropdown-toggle waves-effect" data-toggle="dropdown" aria-expanded="false">
										<?= __('more') ?> <i class="fa fa-chart-simple-horizontal font-18 vertical-middle"></i>
									</button>
									<div class="dropdown-menu">
										<a href="javascript:void(0);" class="text-primary dropdown-item addEmpDocuAtter d-flex align-items-center" data-id="<?= $emprow['eid'] ?>" data-emp_id="<?= $emprow['empid'] ?>">
											<i class="fa fa-solid fa-upload mr-2"></i> <?= __('add_documents') ?>
										</a>
										<a href="javascript:void(0);" class="text-dark dropdown-item d-flex align-items-center" onclick="assignAsset('<?= $emprow['empid'] ?>')">
											<i class="fa fa-solid fa-project-diagram mr-2"></i> <?= __('assign_asset') ?>
										</a>
										<?php if (empty($emprow['has_active_regular_loan'])) : ?>
											<a href="javascript:void(0);" class="text-warning dropdown-item applyLoan d-flex align-items-center" data-emp_id="<?= $emprow['empid'] ?>">
												<i class="fa fa-money-bill-trend-up mr-2"></i> <?= __('apply_loan') ?>
											</a>
										<?php endif; ?>
										<?php if (empty($emprow['has_active_emergency_loan'])) : ?>
											<a href="javascript:void(0);" class="text-info dropdown-item applyEmergencyLoan d-flex align-items-center" data-emp_id="<?= $emprow['empid'] ?>">
												<i class="fa fa-money-bill-wheat mr-2"></i> <?= __('emergency_loan') ?>
											</a>
										<?php endif; ?>
										<?php if ($is_system_admin && $emprow['c_email']) : ?>
											<a class="text-info dropdown-item d-flex align-items-center" href="./qrsend.php?hashcode=<?= $emprow['empid'] ?>&verification=<?= $emprow['eid'] ?>">
												<i class="fa fa-solid fa-qrcode-read mr-2"></i> <?= __('send_qr') ?>
											</a>
										<?php endif; ?>
										<?php if ($user_dept == $emprow['dept'] || $is_system_admin || $isDeptHr || $isHR) : ?>

											<?php if ($emprow['emp_sup_type'] != "man_power") : ?>
												<?php if ($emprow['apd_status'] != 'approve' && $emprow["fly"] == 0) : ?>
													<a href="javascript:void(0);" data-empid="<?= $emprow['empid'] ?>" data-dept="<?= $emprow['dept'] ?>" data-country="<?= $emprow['country'] ?>" class="text-dark dropdown-item applyvacationAtter d-flex align-items-center">
														<i class="fa fa-user-chart mr-2"></i> <?= __('apply_annual_vacation') ?>
													</a>
												<?php endif; ?>
												<?php if ($emprow['apd_review'] == "A" && $emprow['apd_status'] == "apply") : ?>
													<?php
													$status_text = $all_statuses[$emprow['apd_status']] ?? 'Unknown';
													$badge_class = 'secondary';
													switch ($req['approval_status']) {
														case 'apply':
															$badge_class = 'info';
															break;
														case 'pending':
															$badge_class = 'warning';
															break;
														case 'gm_approved':
															$badge_class = 'success';
															break;
														case 'rejected':
															$badge_class = 'danger';
															break;
														default:
															$badge_class = 'primary';
															break;
													}
													?>
													<a class="text-warning dropdown-item d-flex align-items-center">
														<i class="fa fa-user-check mr-2"></i> <?= htmlspecialchars($status_text) ?>
													</a>
												<?php endif; ?>
												<a href="javascript:void(0);" data-empid="<?= $emprow['empid'] ?>" class="text-info dropdown-item applyLeaveRequest d-flex align-items-center">
													<i class="fa fa-solid fa-house-person-leave mr-2"></i> <?= __('apply_leave') ?>
												</a>
											<?php endif; ?>

											<?php if ($is_system_admin && empty($emprow['av_dept'])) : ?>
												<a href="javascript:void(0);" data-emp_id=<?= $emprow['empid'] ?> class="text-dark dropdown-item createUserDeptAjax d-flex align-items-center">
													<i class="fa fa-user-shield mr-2"></i> <?= __('create_login') ?>
												</a>
											<?php endif; ?>

											<?php if ($emprow["fly"] == 1) : ?>
												<?php if ($user_type != "dept_user") : ?>
													<a href="javascript:void(0);" class="text-dark dropdown-item d-flex align-items-center" onclick="returnVacationRequest(<?= lastVacIdGet($emprow['empid'])['vacid'] ?>, '<?= lastVacIdGet($emprow['empid'])['returndate'] ?>')">
														<i class="fa fa-plane-arrival mr-2"></i> <?= __('arrived') ?>
													</a>
												<?php endif; ?>
											<?php else : ?>
												<?php if ($emprow['apd_status'] == 'approve' && $user_type != "dept_user") : ?>
													<a href="add_vac_emp.php?emp_id=<?= $emprow['empid'] ?>" class="text-primary dropdown-item d-flex align-items-center">
														<i class="fa fa-plane-departure mr-2"></i> <?= __('add_vacation') ?>
													</a>
												<?php endif; ?>
											<?php endif; ?>
										<?php endif; ?>

										<?php if ($is_system_admin || $isDeptHr || $isHR) : ?>
											<?php if ($user_type != "dept_user" && $current_page_name == "edit_employee.php") : ?>
												<a href="javascript:void(0);" class="text-danger dropdown-item d-flex align-items-center" data-toggle="modal" data-target=".terminat">
													<i class="fa fa-user-large-slash mr-2"></i> <?= __('terminat') ?>
												</a>
											<?php endif; ?>

											<?php if (!in_array($current_page_name, ["edit_employee.php"]) && $isHR or $is_system_admin or $isDeptHr) : ?>
												<a href="edit_employee.php?emp_id=<?= $emprow['empid'] ?>" class="text-primary dropdown-item d-flex align-items-center">
													<i class="fa fa-user-pen mr-2"></i> <?= __('edit') ?>
												</a>
											<?php endif; ?>

											<?php if (!in_array($current_page_name, ["edit_employee.php"]) && $isHR or $is_system_admin or $isDeptHr) : ?>
												<a href="javascript:void(0);" class="text-info dropdown-item addnote d-flex align-items-center" data-emp_id="<?= $emprow['empid'] ?>">
													<i class="fa fa-book-user mr-2"></i> <?= __('note') ?>
												</a>
											<?php endif; ?>

											<?php if ($isEmployee) : ?>
												<a href="javascript:void(0);" class="text-primary dropdown-item d-flex align-items-center">
													<i class="fa fa-user-pen mr-2"></i> <?= __('edit_information') ?>
												</a>
											<?php endif; ?>

											<?php if ($is_system_admin || $isDeptHr || $isHR) : ?>
												<a href="emp_end_of_service.php?emp_id=<?= $emprow['empid'] ?>" target="_blank" class="text-danger dropdown-item d-flex align-items-center">
													<i class="fa fa-solid fa-user-slash mr-2"></i> <?= __('create_end_of_service') ?>
												</a>
											<?php endif; ?>

										<?php endif; ?>
									</div>
								</div>
							<?php endif; ?>
						</div>

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
				<button action="action" onclick="window.history.go(-1); return false;" type="button" class="btn-sm btn btn-danger waves-effect float-left btn-rounded"><?= __('goto_back') ?> <i class="fa fa-angle-double-right "></i></button>
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
						<?php if ($emprow['empsocialcount'] < 9): ?>
							<a href="javascript:void(0);" class="btn-sm btn btn-info waves-effect btn-rounded addSocial" data-emp_id="<?= $emprow['empid'] ?>">
								Add Social Media <i class="mdi mdi-link-variant"></i>
							</a>
						<?php endif ?>
						<?php if (!$emprow['description']): ?>
							<a href="javascript:void(0);" class="btn-sm btn btn-dark waves-effect btn-rounded addPortfolio" data-emp_id="<?= $emprow['empid'] ?>">
								Add Portfolio Dedails <i class="mdi mdi mdi-account-card-details"></i>
							</a>
						<?php endif ?>
						<?php if ($is_system_admin || $isHR): ?>
							<?php if ($current_page_name <> "add_emp_slry.php"): ?>
								<a href="add_emp_slry.php?emp_id=<?= $emprow['empid'] ?>" class="btn-sm btn btn-secondary waves-effect btn-rounded">
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
	<div class="alert alert-info alert-dismissible bg-info text-white border-0 fade show" role="alert">
		<button type="button" class="close" data-dismiss="alert" aria-label="Close">
			<span aria-hidden="true">×</span>
		</button>
		<div style="color: #fff; font-size: 23px;"> <?= __('happy_life_with_us') . " " . ageDOB($emprow['joining_date']) ?></div>
	</div>
<?php endif; ?>
<!-- /*************************************************/ -->