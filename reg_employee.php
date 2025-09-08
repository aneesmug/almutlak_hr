<?php

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/session_check.php';

$query = mysqli_query($conDB, "SELECT * FROM `admin_login` WHERE `id_iqama`='" . $username . "'");
if (mysqli_num_rows($query) == 1) {
	include("./includes/avatar_select.php");
}


// --- Search, Pagination & Filtering Logic ---
$search_term = $_GET['search'] ?? '';
$limit_options = [12, 24, 36, 48];
$per_page = 12; // Default items per page
$items_per_page = isset($_GET['limit']) && in_array((int)$_GET['limit'], $limit_options) ? (int)$_GET['limit'] : $per_page;
$show_all = isset($_GET['limit']) && $_GET['limit'] == 'all';
if ($show_all) {
    $items_per_page = -1;
}

$current_page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
if ($current_page < 1) {
    $current_page = 1;
}

$where_conditions = [];
$params = [];
$types = "";

// Handle department-specific user access from the session
if (isset($user_type) && $user_type == "dept_user" && isset($_SESSION['user_dept'])) {
    $user_dept = $_SESSION['user_dept'];
    $where_conditions[] = "dept = ?";
    $params[] = $user_dept;
    $types .= "i";
}

// Add search term filter if it exists
if (!empty($search_term)) {
    $where_conditions[] = "(name LIKE ? OR emp_id LIKE ? OR mobile LIKE ? OR iqama LIKE ?)";
    $search_param = "%{$search_term}%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "ssss";
}

$where_sql = "";
if (!empty($where_conditions)) {
    $where_sql = " WHERE " . implode(' AND ', $where_conditions);
}

// Get the total count of items for pagination
$count_query = "SELECT COUNT(*) as totalCount FROM `employees`" . $where_sql;
$stmt_count = $conDB->prepare($count_query);
if (!empty($params)) {
    $stmt_count->bind_param($types, ...$params);
}
$stmt_count->execute();
$total_items = $stmt_count->get_result()->fetch_assoc()['totalCount'] ?? 0;
$stmt_count->close();

$total_pages = $show_all ? 1 : ($items_per_page > 0 ? ceil($total_items / $items_per_page) : 1);
if ($current_page > $total_pages && $total_pages > 0) {
    $current_page = $total_pages;
}

// Get the data for the current page
$employees = [];
if ($total_items > 0) {
    $sql = "SELECT * FROM `employees`" . $where_sql . " ORDER BY `created_at` DESC";

    $main_params = $params;
    $main_types = $types;

    if (!$show_all && $items_per_page > 0) {
        $offset = ($current_page - 1) * $items_per_page;
        $sql .= " LIMIT ?, ?";
        $main_params[] = $offset;
        $main_params[] = $items_per_page;
        $main_types .= "ii";
    }

    $stmt = $conDB->prepare($sql);
    if (!empty($main_params)) {
        $stmt->bind_param($main_types, ...$main_params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result) {
        while ($rec = $result->fetch_assoc()) {
            $employees[] = $rec;
        }
    }
    $stmt->close();
}

// ** NEW ** Get the total unfiltered count of all employees.
$unfiltered_sql = "SELECT COUNT(id) as total FROM employees";
$unfiltered_result = mysqli_query($conDB, $unfiltered_sql);
$unfiltered_total_items = mysqli_fetch_assoc($unfiltered_result)['total'] ?? 0;

?>
<!doctype html>
<html lang="<?= $current_lang ?? 'en' ?>" <?= ($is_rtl ?? false) ? 'dir="rtl"' : '' ?>>

<head>
	<meta charset="utf-8" />
	<title><?= $site_title ?> - All Employees</title>
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	<meta content="Anees Afzal" name="author" />
	<meta http-equiv="X-UA-Compatible" content="IE=edge" />
	<link rel="shortcut icon" href="assets/images/favicon.ico">
	<link href="./plugins/custombox/css/custombox.min.css" rel="stylesheet">
	<link href="assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
	<link href="assets/css/icons.css" rel="stylesheet" type="text/css" />
	<link href="assets/css/metismenu.min.css" rel="stylesheet" type="text/css" />
	<link href="assets/css/style.css" rel="stylesheet" type="text/css" />
	<link href="assets/css/style_dark.css" rel="stylesheet" type="text/css" />
	<script src="assets/js/modernizr.min.js"></script>
	<style type="text/css">
		.card-box.bg-light,
		.card-box.bg-warning,
		.card-box.bg-danger {
			box-shadow: 0 1px 2px rgba(0, 0, 0, 0.15);
			transition: all 0.3s ease-in-out;
			border-radius: 10px !important;
		}

		.card-box.bg-light:hover,
		.card-box.bg-warning:hover,
		.card-box.bg-danger:hover {
			box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
			transform: scale(1.005);
			cursor: pointer;
		}
	</style>
	<?php if ($is_rtl): ?>
            <link href="assets/css/style_rtl.css" rel="stylesheet" type="text/css" />
        <?php endif; ?>
		<script> window.lang = <?= json_encode($GLOBALS['translations'] ?? []) ?>;</script>
</head>

<body class="enlarged" data-keep-enlarged="true">

	<div id="wrapper">

		<div class="left side-menu">

			<div class="slimscroll-menu" id="remove-scroll">

				<div class="topbar-left">
					<a href="dashboard.php" class="logo">
						<span>
							<img src="assets/images/logo.png" alt="" height="22">
						</span>
						<i>
							<img src="assets/images/logo_sm.png" alt="" height="28">
						</i>
					</a>
				</div>
				<?php include("./includes/main_menu.php"); ?>
				<div class="clearfix"></div>

			</div>
		</div>

		<div class="content-page">

			<?php include("./includes/topbar.php"); ?>

			<div class="content">
				<div class="container-fluid">

                    <div class="row">
                        <div class="col-xl-12">
                            <div class="card-box">
                                <h4 class="header-title m-t-0 m-b-30"><?=__('find_employees')?></h4>
                                <div class="row" style="max-width: 800px; margin: auto;">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label for="searchFilter" class="font-weight-bold"><?=__('search_by_name_id_mobile_iqama_id')?></label>
                                            <div class="input-group">
                                                <input type="search" class="form-control" id="searchFilter" placeholder="<?=__('enter_search_term')?>" value="<?=htmlspecialchars($search_term); ?>">
                                                <div class="input-group-append">
                                                    <button class="btn btn-primary" type="button" onclick="applyFilters()"><i class="fas fa-search"></i></button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

					<div class="row">
						<?php if (!empty($employees)): ?>
							<?php
							foreach ($employees as $rec) {
								$id = $rec["id"];
								$name = $rec["name"];
								$emp_id = $rec["emp_id"];
								$iqama = $rec["iqama"];
								$mobile = $rec["mobile"];
								$salary = $rec["salary"];
								$vacation_days = $rec["vacation_days"];
								$joining_date = $rec["joining_date"];
								$date_reg = $rec["created_at"];
								$emp_avatar = $rec["avatar"];
								$emp_status = $rec["status"];
								$emp_status_fly = $rec["fly"];
								$emptype = $rec["emptype"];
								$sex_get = $rec["sex"];

								$sql_count = mysqli_query($conDB, "SELECT COUNT(*) `emp_id` FROM `emp_vacation` WHERE `emp_id`='" . $emp_id . "' ");
								$status_cont = mysqli_fetch_array($sql_count)[0];

								$sql_count_fly = mysqli_query($conDB, "SELECT COUNT(*) `emp_id` FROM `emp_vacation` WHERE `emp_id`='" . $emp_id . "' && `note`='Fly' ");
								$cont_fly = mysqli_fetch_array($sql_count_fly)[0];

								$sql_count_encashed = mysqli_query($conDB, "SELECT COUNT(*) `emp_id` FROM `emp_vacation` WHERE `emp_id`='" . $emp_id . "' && `note`='Encashed' ");
								$cont_encashed = mysqli_fetch_array($sql_count_encashed)[0];

								$checkGander = ($sex_get == 'male') ? './assets/emp_pics/defult.png' : './assets/emp_pics/defultFemale.jpg';
								$emp_avatar = (!empty($emp_avatar) && file_exists("./assets/emp_pics/" . basename($emp_avatar))) ? $emp_avatar : $checkGander;
							?>
								<div class="col-lg-3">
									<div class="text-center card-box <?php if ($emp_status == 1 and $emp_status_fly == 0) {
																			echo "bg-light";
																		} elseif ($emp_status_fly == 1) {
																			echo "bg-warning";
																		} else {
																			echo "bg-danger";
																		} ?>">
										<div class="text-right">
											<div class="btn-group" role="group" aria-label="Edit Button">
												<?php
												if ($emp_status == 1) {
													if ($user_type <> "dept_user") {
												?>
														<a href="edit_employee.php?emp_id=<?= $emp_id ?>" class="btn btn-custom btn-rounded waves-effect waves-light btn-sm">
															<i class="mdi mdi-account-edit"></i> <?=__('edit') ?>
														</a>
												<?php }
												} ?>

												<?php
												if (isset($access1) && $user_type == $access1) {
												?>
													<a href="javascript:void(0);" class="btn btn-danger btn-rounded waves-effect waves-light btn-sm deleteAjax" data-id="<?= $id ?>" data-tbl="employee" data-file='0'>
														<i class="mdi mdi-account-remove"></i>
													</a>
												<?php } ?>
											</div>

										</div>

										<div class="member-card pt-2 pb-2">
											<div class="thumb-lg member-thumb m-b-10 mx-auto">
												<img src="<?= $emp_avatar ?>" class="emp_avat_img empfil" alt="profile-image">
											</div>

											<div class=""><br>
												<h4 class="m-b-5"><?=parseName($name) ?></h4>
											</div>
											<div class="btn-group" role="group" aria-label="Edit Button">
												<a href="view_employee.php?emp_id=<?= $emp_id ?>" class="btn btn-primary m-t-20 btn-rounded waves-effect w-md waves-light btn-sm"><i class="mdi mdi-account-search"></i> <?=__('view_details') ?></a>
											</div><br>
											<span class="badge badge-dark badge-pill"><?=__('fly') ?>: <?= $cont_fly ?> | <?=__('encashed') ?>: <?= $cont_encashed ?></span>

											<div class="mt-4">
												<div class="row">
													<div class="col-4 text-left">
														<div class="mt-3">
															<h4 class="m-b-5"><?= $emp_id ?></h4>
															<p class="mb-0"><?=__('employee_id') ?></p>
														</div>
													</div>

													<div class="col-4">
														<div class="mt-3">
															<?php if ($emptype == "Manager") { ?>
																<button type="button" class="btn btn-custom btn-rounded waves-light waves-effect"><i class="fa fa-user-circle-o"></i> <?= __(strtolower($emptype)); ?></button>
															<?php } ?>
														</div>
													</div>

													<div class="col-4 text-right">
														<div class="mt-3">
															<h5 class="m-b-5"><span class='copyToClipboard'><?= $iqama ?></span></h5>
															<p class="mb-0"><?=__('iqama_id') ?></p>
														</div>
													</div>
												</div>
											</div>

										</div>

									</div>

								</div> <!-- end col -->
							<?php } ?>
						<?php else: ?>
                            <div class="col-12">
                                <div class="text-center mt-5">
                                    <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                    <h2><?=__('no_employees_found')?></h2>
                                    <p class="text-muted"><?=__('no_employees_matching_filters')?></p>
                                </div>
                            </div>
						<?php endif; ?>
					</div>


					<div class="row">
						<div class="col-12">
                            <?php
                                $pagination_params = [];
								if (!empty($search_term)) $pagination_params['search'] = $search_term;
								if (!empty($current_filter)) $pagination_params['status'] = $current_filter;
								echo generate_pagination_controls($current_page,$total_pages,$total_items,$items_per_page,$limit_options,$show_all,$pagination_params,$unfiltered_total_items);
                            ?>
						</div>
					</div>

				</div>

			</div>

			<footer class="footer">
				<?= $site_footer ?>
			</footer>

		</div>

	</div>

	<script src="assets/js/jquery.min.js"></script>
	<script src="assets/js/bootstrap.bundle.min.js"></script>
	<script src="assets/js/metisMenu.min.js"></script>
	<script src="assets/js/waves.js"></script>
	<script src="assets/js/jquery.slimscroll.js"></script>
	<script src="./plugins/custombox/js/custombox.min.js"></script>
	<script src="./plugins/custombox/js/legacy.min.js"></script>
	<script src="assets/js/jquery.core.js"></script>
	<script src="assets/js/jquery.app.js"></script>
    <script>
        function applyFilters() {
            const limitElement = document.getElementById('limitFilter');
            const limit = limitElement ? limitElement.value : <?= $per_page ?>;
            const search = document.getElementById('searchFilter').value;
            const baseUrl = window.location.href.split('?')[0];
            window.location.href = `${baseUrl}?limit=${limit}&search=${encodeURIComponent(search)}&page=1`;
        }
        document.getElementById('searchFilter').addEventListener('keypress', function (e) {
            if (e.key === 'Enter') { applyFilters(); }
        });
    </script>
</body>

</html>
