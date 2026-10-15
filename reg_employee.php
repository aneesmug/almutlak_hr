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
// Draft (status = 2) employees are listed for inactive viewers and for anyone who can edit
// employees (they activate drafts from Edit Employee).
$can_view_draft = $can_view_inactive || ($is_system_admin ?? false) || ($isDeptHr ?? false)
    || user_has_special_access($conDB, $empid ?? '', 'access_edit_employee', $user_role ?? '', $user_type ?? '', $is_system_admin ?? false);
$inactive_scope_sql = $can_view_inactive ? '' : ($can_view_draft ? ' AND (status IN (1, 2) OR fly = 1)' : ' AND (status = 1 OR fly = 1)');
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
$status_filter = in_array(($_GET['emp_status'] ?? ''), ['active', 'fly', 'draft', 'inactive'], true) ? $_GET['emp_status'] : '';
if (($status_filter === 'inactive' && !$can_view_inactive) || ($status_filter === 'draft' && !$can_view_draft)) {
    $status_filter = '';
}
if ($status_filter === 'active') {
    $where_conditions[] = "(status = 1 AND fly = 0)";
} elseif ($status_filter === 'fly') {
    $where_conditions[] = "fly = 1";
} elseif ($status_filter === 'draft') {
    $where_conditions[] = "(status = 2 AND fly <> 1)";
} elseif ($status_filter === 'inactive') {
    $where_conditions[] = "(status = 0 AND fly <> 1)";
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
$emp_counts = ['all' => 0, 'active' => 0, 'fly' => 0, 'draft' => 0, 'inactive' => 0];
$counts_res = mysqli_query($conDB, "SELECT COUNT(*) AS all_cnt,
        SUM(status = 1 AND fly = 0) AS active_cnt, SUM(fly = 1) AS fly_cnt, SUM(status = 2 AND fly <> 1) AS draft_cnt, SUM(status = 0 AND fly <> 1) AS inactive_cnt
    FROM employees WHERE 1=1" . $company_filter . $department_filter . $employee_filter);
if ($counts_res && ($cr = mysqli_fetch_assoc($counts_res))) {
    $emp_counts = ['all' => (int)$cr['all_cnt'], 'active' => (int)$cr['active_cnt'], 'fly' => (int)$cr['fly_cnt'], 'draft' => (int)$cr['draft_cnt'], 'inactive' => (int)$cr['inactive_cnt']];
}
// main_menu.php (where $can_see_new_employee_page lives) is included later, so read the role list here
require_once __DIR__ . '/includes/page_access_helper.php';
$new_employee_roles = get_page_access_map($conDB)['add_new_employee.php'] ?? [];
$can_add_employee = in_array($user_role, $new_employee_roles) || in_array($user_type, $new_employee_roles);

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
        /* Employee stat tiles - one row: icon chip, label + share hint, count, share bar */
        .sr-page .sr-tiles.emp-tiles { grid-template-columns: repeat(var(--et-cols, 4), minmax(0, 1fr)); gap: 12px; }
        @media (max-width: 1199px) { .sr-page .sr-tiles.emp-tiles { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
        @media (max-width: 767px) { .sr-page .sr-tiles.emp-tiles { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
        @media (max-width: 420px) { .sr-page .sr-tiles.emp-tiles { grid-template-columns: 1fr; } }
        .emp-tile { --et-fg: var(--tone-indigo-fg); --et-bg: var(--tone-indigo-bg); --et-bd: var(--tone-indigo-bd); --et-bar: #6366f1;
            display: flex; flex-wrap: wrap; align-items: center; gap: 12px; padding-block: 14px 12px; padding-inline: 18px 16px; overflow: hidden; }
        .emp-tile.et-green { --et-fg: var(--tone-green-fg); --et-bg: var(--tone-green-bg); --et-bd: var(--tone-green-bd); --et-bar: #22c55e; }
        .emp-tile.et-sky   { --et-fg: var(--tone-sky-fg);   --et-bg: var(--tone-sky-bg);   --et-bd: var(--tone-sky-bd);   --et-bar: #0ea5e9; }
        .emp-tile.et-amber { --et-fg: var(--tone-amber-fg); --et-bg: var(--tone-amber-bg); --et-bd: var(--tone-amber-bd); --et-bar: #f59e0b; }
        .emp-tile.et-slate { --et-fg: var(--tone-slate-fg); --et-bg: var(--tone-slate-bg); --et-bd: var(--tone-slate-bd); --et-bar: #94a3b8; }
        .emp-tile::before { content: ''; position: absolute; inset-block: 10px; inset-inline-start: 0; width: 3px;
            border-radius: 3px; background: var(--et-bar); opacity: 0; transition: opacity .15s; }
        .emp-tile .et-icon { flex: 0 0 auto; display: inline-flex; align-items: center; justify-content: center;
            width: 40px; height: 40px; border-radius: 11px; background: var(--et-bg); color: var(--et-fg); font-size: 17px; }
        .emp-tile .et-body { flex: 1 1 0; min-width: 0; }
        .emp-tile .et-label { display: block; font-size: 13px; font-weight: 700; color: var(--sr-text); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .emp-tile .et-hint { display: block; margin-top: 2px; font-size: 11.5px; color: var(--sr-muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .emp-tile .et-hint b { color: var(--et-fg); font-weight: 700; }
        .emp-tile .sr-tile-value { flex: 0 0 auto; margin: 0; font-size: 26px; font-weight: 800; letter-spacing: -.5px; }
        .emp-tile .et-bar { flex: 0 0 100%; display: block; height: 4px; border-radius: 999px; background: var(--sr-surface-3); overflow: hidden; }
        .emp-tile .et-bar > span { display: block; height: 100%; border-radius: inherit; background: var(--et-bar); transition: width .4s; }
        .emp-tile:hover { border-color: var(--et-bd); }
        .emp-tile.active { background: var(--et-bg); border-color: var(--et-bd); box-shadow: 0 0 0 1px var(--et-bd) inset, var(--sr-shadow); }
        .emp-tile.active::after { display: none; }
        .emp-tile.active::before { opacity: 1; }
        .emp-tile.active .et-icon { background: var(--et-fg); color: var(--sr-surface); }
        .emp-tile.active .sr-tile-value { color: var(--et-fg); }
        .emp-tile.active .et-bar { background: var(--sr-surface); }
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
                    </div>

                    <?php
                    $emp_tiles = [
                        ['key' => '',         'tone' => 'indigo', 'icon' => 'fa-users',        'label' => __('all', 'All'),                 'count' => $emp_counts['all']],
                        ['key' => 'active',   'tone' => 'green',  'icon' => 'fa-check-circle', 'label' => __('active', 'Active'),           'count' => $emp_counts['active']],
                        ['key' => 'fly',      'tone' => 'sky',    'icon' => 'fa-plane',        'label' => __('on_vacation', 'On vacation'), 'count' => $emp_counts['fly']],
                    ];
                    if ($can_view_draft) {
                        $emp_tiles[] = ['key' => 'draft', 'tone' => 'amber', 'icon' => 'fa-file-pen', 'label' => __('draft', 'Draft'), 'count' => $emp_counts['draft']];
                    }
                    if ($can_view_inactive) {
                        $emp_tiles[] = ['key' => 'inactive', 'tone' => 'slate', 'icon' => 'fa-user-times', 'label' => __('inactive', 'Inactive'), 'count' => $emp_counts['inactive']];
                    }
                    ?>
                    <div class="sr-tiles emp-tiles" id="empTiles" style="--et-cols: <?= count($emp_tiles) ?>;">
                        <?php foreach ($emp_tiles as $t):
                            $pct = $emp_counts['all'] > 0 ? round($t['count'] / $emp_counts['all'] * 100) : 0; ?>
                        <button type="button" class="sr-tile emp-tile et-<?= $t['tone'] ?><?= $status_filter === $t['key'] ? ' active' : '' ?>" data-key="<?= $t['key'] ?>">
                            <span class="et-icon"><i class="fa <?= $t['icon'] ?>"></i></span>
                            <span class="et-body">
                                <span class="et-label"><?= $t['label'] ?></span>
                                <span class="et-hint"><?php if ($t['key'] === ''): ?><?= __('everyone_you_can_access', 'Everyone you can access') ?><?php else: ?><b><?= $pct ?>%</b> <?= __('of_all_employees', 'of all') ?><?php endif; ?></span>
                            </span>
                            <span class="sr-tile-value"><?= number_format($t['count']) ?></span>
                            <span class="et-bar"><span style="width: <?= $t['key'] === '' ? 100 : $pct ?>%"></span></span>
                        </button>
                        <?php endforeach; ?>
                    </div>

                    <div class="sr-card">
                        <div class="sr-toolbar" style="border-bottom: 0;">
                            <div class="sr-search" style="max-width: 560px;">
                                <i class="fa fa-search"></i>
                                <input type="search" id="searchFilter" placeholder="<?= __('search_by_name_id_mobile_iqama_id') ?>..." value="<?= htmlspecialchars($search_term); ?>" autocomplete="off" aria-label="<?= __('search_by_name_id_mobile_iqama_id') ?>">
                            </div>
                            <?php if ($can_add_employee): ?>
                                <button type="button" class="sr-btn sr-btn-primary" onclick="openNewEmployeeTypeModal()"><i class="fa fa-user-plus"></i> <?= __('register_employee', 'Register Employee') ?></button>
                            <?php endif; ?>
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
                                } elseif ($emp_status == 2) {
                                    $status_class = 'status-draft';
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
