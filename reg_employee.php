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

?>
<!doctype html>
<html lang="<?= $current_lang ?? 'en' ?>" <?= ($is_rtl ?? false) ? 'dir="rtl"' : '' ?>>

<head>
	<meta charset="utf-8" />
	<title><?= $site_title ?> - All Employees</title>
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	<meta content="Anees Afzal" name="author" />
	<meta http-equiv="X-UA-Compatible" content="IE=edge" />
	<link rel="shortcut icon" href="<?=get_setting($conDB, 'favicon')?>">
	<link href="./plugins/custombox/css/custombox.min.css" rel="stylesheet">
	<link href="assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
	<link href="assets/css/icons.css" rel="stylesheet" type="text/css" />
	<link href="assets/css/metismenu.min.css" rel="stylesheet" type="text/css" />
	<link href="assets/css/style.css" rel="stylesheet" type="text/css" />
	<link href="assets/css/style_dark.css" rel="stylesheet" type="text/css" />
	<script src="assets/js/modernizr.min.js"></script>
    
    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

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

			<div class="content">
				<div class="container-fluid">
                    <div class="row">
                        <div class="col-xl-12">
                            <div class="card-box">
                                <h4 class="header-title m-t-0 m-b-30"><?=__('all_employees')?></h4>
                                <div class="row" style="max-width: 800px; margin: auto;">
                                    <div class="col-md-12">
                                        <div class="form-group" style="background: #f4f6f9; border: 1px solid #e2e8f0; border-radius: 10px; padding: 12px 16px;">
                                            <label for="searchFilter" class="font-weight-bold"><?=__('search_by_name_id_mobile_iqama_id')?></label>
                                            <input type="search" class="form-control" id="searchFilter" placeholder="<?=__('enter_search_term')?>" value="<?=htmlspecialchars($search_term); ?>" style="background: #fff;">
                                        </div>
                                    </div>
                                </div>
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
                                <div class="text-center mt-5">
                                    <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                    <h2><?=__('no_employees_found')?></h2>
                                    <p class="text-muted"><?=__('no_employees_matching_filters')?></p>
                                </div>
                            </div>
						<?php endif; ?>
					</div>

					<div class="row">
						<div class="col-12" id="employeesPaginationContainer">
                            <?php
                                $pagination_params = [];
								if (!empty($search_term)) $pagination_params['search'] = $search_term;
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
                data: { search: search, limit: limit, page: page }
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
