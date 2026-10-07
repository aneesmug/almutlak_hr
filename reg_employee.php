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

// ================================================================
// DEPARTMENT & COMPANY-BASED ACCESS CONTROL (NEW)
// ================================================================
$company_filter = getCompanyFilterSQL('comp_no', true);
$department_filter = getDepartmentFilterSQL('dept', true);
$employee_filter = getEmployeeFilterSQL('emp_id', true);

// Inactive employees are only listed for users holding the 'view_inactive_employees'
// special access (system admins always pass). Inactive = same rule as the status tile.
$can_view_inactive = user_has_special_access($conDB, $empid ?? '', 'view_inactive_employees', $user_role ?? '', $user_type ?? '', $is_system_admin ?? false);
$inactive_scope_sql = $can_view_inactive ? '' : ' AND (status = 1 OR fly = 1)';
$employee_filter .= $inactive_scope_sql;

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

// Status tile filter: active (working) / fly (on vacation) / inactive - same rules as the card's status_class
$status_filter = in_array(($_GET['emp_status'] ?? ''), ['active', 'fly', 'inactive'], true) ? $_GET['emp_status'] : '';
if ($status_filter === 'inactive' && !$can_view_inactive) {
    $status_filter = '';
}
if ($status_filter === 'active') {
    $where_conditions[] = "(status = 1 AND fly = 0)";
} elseif ($status_filter === 'fly') {
    $where_conditions[] = "fly = 1";
} elseif ($status_filter === 'inactive') {
    $where_conditions[] = "(status <> 1 AND fly <> 1)";
}


$where_sql = "";
if (!empty($where_conditions)) {
    $where_sql = " WHERE " . implode(' AND ', $where_conditions);
}
$where_sql .= ($where_sql ? " " : " WHERE 1=1 ") . $company_filter . $department_filter . $employee_filter;

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

// Get the total unfiltered count of all employees the user can access (with filters)
$unfiltered_sql = "SELECT COUNT(id) as total FROM employees WHERE 1=1" . $company_filter . $department_filter . $employee_filter;
$unfiltered_result = mysqli_query($conDB, $unfiltered_sql);
$unfiltered_total_items = mysqli_fetch_assoc($unfiltered_result)['total'] ?? 0;

// Tile counts (respect the same company/department/employee scope, ignore search + status)
$emp_counts = ['all' => 0, 'active' => 0, 'fly' => 0, 'inactive' => 0];
$counts_res = mysqli_query($conDB, "SELECT COUNT(*) AS all_cnt,
        SUM(status = 1 AND fly = 0) AS active_cnt, SUM(fly = 1) AS fly_cnt, SUM(status <> 1 AND fly <> 1) AS inactive_cnt
    FROM employees WHERE 1=1" . $company_filter . $department_filter . $employee_filter);
if ($counts_res && ($cr = mysqli_fetch_assoc($counts_res))) {
    $emp_counts = ['all' => (int)$cr['all_cnt'], 'active' => (int)$cr['active_cnt'], 'fly' => (int)$cr['fly_cnt'], 'inactive' => (int)$cr['inactive_cnt']];
}
$can_add_employee = in_array($user_role, $can_see_new_employee_page ?? []) || in_array($user_type, $can_see_new_employee_page ?? []);

?>
<!doctype html>
<html lang="<?= $current_lang ?? 'en' ?>" <?= ($is_rtl ?? false) ? 'dir="rtl"' : '' ?>>

<head>
	<meta charset="utf-8" />
	<title><?= $site_title ?> - <?= __('all_employees') ?></title>
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	<meta content="Anees Afzal" name="author" />
	<meta http-equiv="X-UA-Compatible" content="IE=edge" />
	<link rel="shortcut icon" href="<?=get_setting($conDB, 'favicon')?>">
	<link href="./plugins/custombox/css/custombox.min.css" rel="stylesheet">
    <!-- Plugins css -->
    <link href="./plugins/bootstrap-timepicker/bootstrap-timepicker.min.css" rel="stylesheet">
    <link href="./plugins/bootstrap-datepicker/css/bootstrap-datepicker.min.css" rel="stylesheet">
    <link href="./plugins/clockpicker/css/bootstrap-clockpicker.min.css" rel="stylesheet">
    <link href="./plugins/bootstrap-daterangepicker/daterangepicker.css" rel="stylesheet">

    <link rel="stylesheet" href="./plugins/bootstrap-select/css/bootstrap-select.min.css">
    <link rel="stylesheet" href="./plugins/select2/css/select2.min.css">

    <link href="./plugins/summernote/summernote.min.css" rel="stylesheet" />

    <!-- App css -->
    <link href="assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/icons.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/metismenu.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/style.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/style_dark.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/smart_request.css?v=<?= @filemtime(__DIR__ . '/assets/css/smart_request.css') ?>" rel="stylesheet" type="text/css" />

    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Dropzone CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.9.3/dropzone.min.css">

    <script src="assets/js/modernizr.min.js"></script>

    <style>
        .sr-page .sr-tiles.emp-tiles { grid-template-columns: repeat(4, minmax(0, 1fr)); }
        @media (max-width: 767px) { .sr-page .sr-tiles.emp-tiles { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
        #employeesCardsContainer { transition: opacity .15s; }
        .emp-pager { margin-top: 4px; }
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
						<span><img src="<?=get_setting($conDB, 'logo')?>" alt="" height="22"></span>
						<i><img src="<?=get_setting($conDB, 'white_logo')?>" alt="" height="28"></i>
					</a>
				</div>
				<?php include("./includes/main_menu.php"); ?>
				<div class="clearfix"></div>
			</div>
		</div>

		<div class="content-page">
			<?php include("./includes/topbar.php"); ?>

			<div class="content sr-page">
				<div class="container-fluid">

                    <div class="sr-head">
                        <div>
                            <h1><?= __('all_employees') ?></h1>
                            <p><?= __('all_employees_subtitle', 'Everyone you can access, newest first.') ?></p>
                        </div>
                        <?php if ($can_add_employee): ?>
                            <div class="sr-head-actions">
                                <button type="button" class="sr-btn sr-btn-primary" onclick="openNewEmployeeTypeModal()"><i class="fa fa-user-plus"></i> <?= __('new_employee') ?></button>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="sr-tiles emp-tiles" id="empTiles">
                        <button type="button" class="sr-tile<?= $status_filter === '' ? ' active' : '' ?>" data-key="">
                            <span class="sr-tile-label"><span class="sr-dot dot-all"></span><?= __('all', 'All') ?></span>
                            <span class="sr-tile-value"><?= $emp_counts['all'] ?></span>
                        </button>
                        <button type="button" class="sr-tile<?= $status_filter === 'active' ? ' active' : '' ?>" data-key="active">
                            <span class="sr-tile-label"><span class="sr-dot dot-green"></span><?= __('active', 'Active') ?></span>
                            <span class="sr-tile-value"><?= $emp_counts['active'] ?></span>
                        </button>
                        <button type="button" class="sr-tile<?= $status_filter === 'fly' ? ' active' : '' ?>" data-key="fly">
                            <span class="sr-tile-label"><span class="sr-dot dot-sky"></span><?= __('on_vacation', 'On vacation') ?></span>
                            <span class="sr-tile-value"><?= $emp_counts['fly'] ?></span>
                        </button>
                        <?php if ($can_view_inactive): ?>
                        <button type="button" class="sr-tile<?= $status_filter === 'inactive' ? ' active' : '' ?>" data-key="inactive">
                            <span class="sr-tile-label"><span class="sr-dot dot-slate"></span><?= __('inactive', 'Inactive') ?></span>
                            <span class="sr-tile-value"><?= $emp_counts['inactive'] ?></span>
                        </button>
                        <?php endif; ?>
                    </div>

                    <div class="sr-card">
                        <div class="sr-toolbar" style="border-bottom: 0;">
                            <div class="sr-search" style="max-width: 560px;">
                                <i class="fa fa-search"></i>
                                <input type="search" id="searchFilter" placeholder="<?= __('search_by_name_id_mobile_iqama_id') ?>..." value="<?= htmlspecialchars($search_term); ?>" autocomplete="off" aria-label="<?= __('search_by_name_id_mobile_iqama_id') ?>">
                            </div>
                        </div>
                    </div>

					<div class="row" id="employeesCardsContainer">
						<?php if (!empty($employees)): ?>
							<?php
							foreach ($employees as $rec) {
								$id = $rec["id"];
								$name = $rec["name"];
								$emp_id = $rec["emp_id"];
								$iqama = $rec["iqama"];
								$mobile = $rec["mobile"];
								$emp_avatar = getAvatarImagePath($rec['avatar'] ?? '', $rec['sex'] ?? 1);
								$emp_status = $rec["status"];
								$emp_status_fly = $rec["fly"];
								$emptype = $rec["emptype"];
								$sex_get = $rec["sex"];

                                $status_class = '';
                                if ($emp_status == 1 && $emp_status_fly == 0) {
                                    $status_class = 'status-active';
                                } elseif ($emp_status_fly == 1) {
                                    $status_class = 'status-fly';
                                } else {
                                    $status_class = 'status-inactive';
                                }
							?>
							<?php include("./includes/employee_card.php"); ?>
							<?php } ?>
						<?php else: ?>
                            <div class="col-12">
                                <div class="sr-card"><div class="sr-empty"><i class="fa fa-users"></i>
                                    <strong style="display: block; color: var(--sr-text); font-size: 15px;"><?= __('no_employees_found') ?></strong>
                                    <?= __('no_employees_matching_filters') ?>
                                </div></div>
                            </div>
						<?php endif; ?>
					</div>

                    <div class="sr-card emp-pager">
                        <div class="sr-pager" id="employeesPaginationContainer" style="padding-top: 14px;">
                            <?php
                                $pagination_params = [];
                                if (!empty($search_term)) $pagination_params['search'] = $search_term;
                                if ($status_filter !== '') $pagination_params['emp_status'] = $status_filter;
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
	<script src="assets/js/jquery.app.js?t=<?= time() ?>"></script>
    <script>
        // Live AJAX employee list - search, limit & pagination all fetch in place,
        // no full page reload / no ?page= navigation.
        let employeesSearchTimer = null;

        function loadEmployeesList(page) {
            const $cards = $('#employeesCardsContainer');
            const $pagination = $('#employeesPaginationContainer');
            const limitElement = document.getElementById('limitFilter');
            const limit = limitElement ? limitElement.value : <?= $per_page ?>;
            const search = document.getElementById('searchFilter').value;

            const lockedHeight = $cards.outerHeight();
            if (lockedHeight) {
                $cards.css('min-height', lockedHeight + 'px');
            }
            $cards.css({ opacity: 0.45, 'pointer-events': 'none' });

            $.ajax({
                url: './includes/ajaxFile/get_all_employees_list.php',
                type: 'POST',
                dataType: 'json',
                data: { search: search, limit: limit, page: page, emp_status: $('#empTiles .sr-tile.active').data('key') || '' }
            }).done(function(response) {
                if (!response || response.status !== 200) {
                    return;
                }
                $cards.html(response.cards_html);
                $pagination.html(response.pagination_html);
            }).always(function() {
                $cards.css({ opacity: 1, 'pointer-events': 'auto', 'min-height': '' });
            });
        }

        function applyFilters() {
            loadEmployeesList(1);
        }

        $('#empTiles').on('click', '.sr-tile', function() {
            $('#empTiles .sr-tile').removeClass('active');
            $(this).addClass('active');
            loadEmployeesList(1);
        });

        document.getElementById('searchFilter').addEventListener('keypress', function (e) {
            if (e.key === 'Enter') { applyFilters(); }
        });
        document.getElementById('searchFilter').addEventListener('input', function () {
            clearTimeout(employeesSearchTimer);
            employeesSearchTimer = setTimeout(function () {
                loadEmployeesList(1);
            }, 350);
        });

        // Pagination links are rendered by the shared generate_pagination_controls()
        // helper as plain <a href="?page=N..."> - intercept clicks so they run through
        // AJAX instead of a full navigation.
        $(document).on('click', '#employeesPaginationContainer .page-link', function (e) {
            const $li = $(this).closest('.page-item');
            if ($li.hasClass('disabled') || $li.hasClass('active')) {
                e.preventDefault();
                return;
            }
            const href = $(this).attr('href');
            if (!href || href === '#') {
                return;
            }
            e.preventDefault();
            const page = new URL(href, window.location.href).searchParams.get('page') || 1;
            loadEmployeesList(parseInt(page, 10));
        });

        // limitFilter select is generated by generate_pagination_controls() with
        // onchange="applyFilters()" already wired up - applyFilters() now runs via AJAX.
        // Check for SweetAlert message from session (after edit redirect)
        <?php if (isset($_SESSION['swal_alert'])): ?>
            Swal.fire({
                title: '<?= addslashes($_SESSION['swal_alert']['title']) ?>',
                text: '<?= addslashes($_SESSION['swal_alert']['message']) ?>',
                icon: '<?= $_SESSION['swal_alert']['type'] ?>',
                confirmButtonText: '<?= __("ok") ?>',
                allowOutsideClick: false,
                customClass: {
                    confirmButton: 'btn btn-primary'
                },
                buttonsStyling: false
            });
            <?php unset($_SESSION['swal_alert']); ?>
        <?php endif; ?>
    </script>
</body>

</html>
