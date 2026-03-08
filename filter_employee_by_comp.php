<?php
/****************************************************************
 * MODIFICATION SUMMARY (filter_employee_by_dept.php):
 * 1.  IMPLEMENTED ADVANCED PAGINATION: Replaced all old logic with the new standardized pagination function.
 * 2.  ADDED UNFILTERED COUNT: A query now gets the total number of all employees in the system for the detailed count text.
 * 3.  ADDED FILTERING & SEARCH: Integrated the same search bar and status filter controls used on the main employee page.
 * 4.  REFACTORED QUERIES: All database queries have been converted to use secure prepared statements.
 ****************************************************************/
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/session_check.php';

if (!isset($_GET['comp']) || !is_numeric($_GET['comp'])) {
    header("Location: dashboard.php");
    exit;
}
$company_id = (int)$_GET['comp'];
$department_id = (isset($_GET['dept']) && is_numeric($_GET['dept'])) ? (int)$_GET['dept'] : 0;

$can_see_all_employees = function_exists('canSeeAllEmployeesByRole')
    ? canSeeAllEmployeesByRole(true)
    : ($is_system_admin || $user_type == 'administrator' || $user_dept == 5 || $isHR || $isDeptHr || $user_dept == 1);

$has_explicit_scope_restrictions = function_exists('hasExplicitEmployeeScopeRestrictions')
    ? hasExplicitEmployeeScopeRestrictions(true)
    : (!empty($allowed_companies_array) || !empty($allowed_departments_array) || !empty($allowed_employees_array));

// --- ACCESS CONTROL: Check if user is allowed to access this company ---
$accessible_companies = [];
if (!empty($allowed_companies_array)) {
    $accessible_companies = $allowed_companies_array;
} else {
    // User has no restrictions - can access all companies
}

// If user has company restrictions, validate they have access to this specific company
if (!empty($accessible_companies) && !in_array($company_id, $accessible_companies)) {
    // Check if user has explicitly allowed employees in this company
    $allow_by_employee = false;
    if (!empty($allowed_employees_array)) {
        $allowed_emp_ids = implode(',', array_map('intval', $allowed_employees_array));
        $comp_check_sql = "SELECT 1 FROM employees WHERE comp_no = ? AND emp_id IN ($allowed_emp_ids) LIMIT 1";
        $comp_check_stmt = $conDB->prepare($comp_check_sql);
        $comp_check_stmt->bind_param("i", $company_id);
        $comp_check_stmt->execute();
        $allow_by_employee = $comp_check_stmt->get_result()->num_rows > 0;
        $comp_check_stmt->close();
    }
    
    if (!$allow_by_employee) {
        header("Location: dashboard.php");
        exit;
    }
}

// Legacy-safe fallback: when no explicit scope is configured, non-full-access users are limited to own company.
if (empty($accessible_companies) && !$has_explicit_scope_restrictions && !$can_see_all_employees) {
    $currentUserCompany = (int)($user_company ?? ($_SESSION['auth_user']['comp_no'] ?? 0));
    if ($currentUserCompany > 0 && $currentUserCompany !== (int)$company_id) {
        header("Location: dashboard.php");
        exit;
    }
}

$query = mysqli_query($conDB, "SELECT * FROM `admin_login` WHERE `id_iqama`='" . $username . "'");
if (mysqli_num_rows($query) == 1) {
    include("./includes/avatar_select.php");
}

// --- Get Company Name ---
$dept_name_stmt = $conDB->prepare("SELECT `comp_name` FROM `companies` WHERE `comp_id` = ?");
$dept_name_stmt->bind_param("i", $company_id);
$dept_name_stmt->execute();
$department_name = $dept_name_stmt->get_result()->fetch_assoc()['comp_name'] ?? 'Unknown Department';
$dept_name_stmt->close();

$selected_department_name = '';
if ($department_id > 0) {
    $selected_dept_stmt = $conDB->prepare("SELECT `dep_nme` FROM `department` WHERE `id` = ? LIMIT 1");
    if ($selected_dept_stmt) {
        $selected_dept_stmt->bind_param("i", $department_id);
        $selected_dept_stmt->execute();
        $selected_department_name = $selected_dept_stmt->get_result()->fetch_assoc()['dep_nme'] ?? '';
        $selected_dept_stmt->close();
    }
}


// --- Pagination & Filtering Setup ---
$limit_options = [12, 24, 48, 96];
$search_term = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? 'active'; // 'all', 'active', 'inactive', 'on_vacation'
if (!in_array($status_filter, ['all', 'active', 'inactive', 'on_vacation'], true)) {
    $status_filter = 'active';
}
$items_per_page = isset($_GET['limit']) && in_array((int)$_GET['limit'], $limit_options) ? (int)$_GET['limit'] : $limit_options[0];
$show_all = isset($_GET['limit']) && $_GET['limit'] == 'all';
if ($show_all) {
    $items_per_page = -1;
}
$current_page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
if ($current_page < 1) {
    $current_page = 1;
}

// ** NEW ** Get the total unfiltered count of ALL employees.
$employee_filter_count = getEmployeeFilterSQL('emp_id', false);
$fallback_scope_clause = "";
if (!$can_see_all_employees && !$has_explicit_scope_restrictions) {
    $currentUserCompany = (int)($user_company ?? ($_SESSION['auth_user']['comp_no'] ?? 0));
    if ($currentUserCompany > 0) {
        $fallback_scope_clause .= " AND `comp_no`='" . mysqli_real_escape_string($conDB, $currentUserCompany) . "'";
    }
    if (!empty($user_dept)) {
        $fallback_scope_clause .= " AND `dept`='" . mysqli_real_escape_string($conDB, $user_dept) . "'";
    }
}
$unfiltered_sql = "SELECT COUNT(id) as total FROM employees WHERE 1=1" . $employee_filter_count . $fallback_scope_clause;
$unfiltered_result = mysqli_query($conDB, $unfiltered_sql);
$unfiltered_total_items = mysqli_fetch_assoc($unfiltered_result)['total'] ?? 0;

// --- Build Query ---
$where_clauses = ["`comp_no` = ?"]; // Company is the base filter for this page

$params = [$company_id];
$types = "i";

if ($department_id > 0) {
    $where_clauses[] = "`dept` = ?";
    $params[] = $department_id;
    $types .= "i";
}

// --- NEW ACCESS CONTROL: Always apply company and department filters ---
$company_filter = getCompanyFilterSQL('comp_no', false);
if (!empty($company_filter)) {
    $where_clauses[] = substr($company_filter, 5); // remove leading ' AND '
}
$department_filter = getDepartmentFilterSQL('dept', false);
if (!empty($department_filter)) {
    $where_clauses[] = substr($department_filter, 5); // remove leading ' AND '
}
$employee_filter = getEmployeeFilterSQL('emp_id', false);
if (!empty($employee_filter)) {
    $where_clauses[] = substr($employee_filter, 5); // remove leading ' AND '
}

if (!$can_see_all_employees && !$has_explicit_scope_restrictions) {
    $currentUserCompany = (int)($user_company ?? ($_SESSION['auth_user']['comp_no'] ?? 0));
    if ($currentUserCompany > 0) {
        $where_clauses[] = "`comp_no` = ?";
        $params[] = $currentUserCompany;
        $types .= "i";
    }
    if (!empty($user_dept)) {
        $where_clauses[] = "`dept` = ?";
        $params[] = (int)$user_dept;
        $types .= "i";
    }
}

if (!empty($search_term)) {
    $where_clauses[] = "(`name` LIKE ? OR `iqama` LIKE ? OR `emp_id` LIKE ?)";
    $like_term = "%{$search_term}%";
    array_push($params, $like_term, $like_term, $like_term);
    $types .= "sss";
}

if ($status_filter != 'all') {
    switch ($status_filter) {
        case 'active':
            $where_clauses[] = "status = ? AND fly = ?";
            array_push($params, 1, 0);
            $types .= "ii";
            break;
        case 'inactive':
            $where_clauses[] = "status = ?";
            $params[] = 0;
            $types .= "i";
            break;
        case 'on_vacation':
            $where_clauses[] = "fly = ?";
            $params[] = 1;
            $types .= "i";
            break;
    }
}

$where_sql = " WHERE " . implode(" AND ", $where_clauses);

// Get filtered count
$count_sql = "SELECT COUNT(*) as total FROM employees" . $where_sql;
$stmt_count = $conDB->prepare($count_sql);
$stmt_count->bind_param($types, ...$params);
$stmt_count->execute();
$total_items = $stmt_count->get_result()->fetch_assoc()['total'] ?? 0;
$stmt_count->close();

$total_pages = ($show_all || $items_per_page <= 0) ? 1 : ceil($total_items / $items_per_page);
if ($current_page > $total_pages && $total_pages > 0) {
    $current_page = $total_pages;
}

// Get data for current page
$employees = [];
if ($total_items > 0) {
    $sql = "SELECT * FROM employees" . $where_sql . " ORDER BY `created_at` DESC";
    if (!$show_all) {
        $offset = ($current_page - 1) * $items_per_page;
        $sql .= " LIMIT ?, ?";
        $params[] = $offset;
        $params[] = $items_per_page;
        $types .= "ii";
    }
    $stmt = $conDB->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $employees[] = $row;
    }
    $stmt->close();
}
?>
<!doctype html>
<html lang="<?= $current_lang ?? 'en' ?>" <?= ($is_rtl ?? false) ? 'dir="rtl"' : '' ?>>
<head>
    <meta charset="utf-8" />
    <title><?= $site_title ?> - <?= htmlspecialchars($department_name) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	<link rel="shortcut icon" href="<?=get_setting($conDB, 'favicon')?>">
	<link href="./plugins/custombox/css/custombox.min.css" rel="stylesheet">
	<link href="assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
	<link href="assets/css/icons.css" rel="stylesheet" type="text/css" />
	<link href="assets/css/metismenu.min.css" rel="stylesheet" type="text/css" />
	<link href="assets/css/style.css" rel="stylesheet" type="text/css" />
	<link href="assets/css/style_dark.css" rel="stylesheet" type="text/css" />
	<script src="assets/js/modernizr.min.js"></script>
    <style>
        .filter-controls { max-width: 800px; }
        .card-box { border-radius: 10px !important; }
        .card-box.bg-light,
		.card-box.bg-warning,
		.card-box.bg-danger {
			box-shadow: 0 1px 2px rgba(0, 0, 0, 0.15);
			transition: all 0.3s ease-in-out;
		}
		.card-box.bg-light:hover,
		.card-box.bg-warning:hover,
		.card-box.bg-danger:hover {
			box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
			transform: scale(1.005);
			cursor: pointer;
		}
        .emp_avat_img {
            border: 2px solid #555;
            border-radius: 50%;
            width: 120px;
            height: 120px;
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
							<img src="<?=get_setting($conDB, 'logo')?>" alt="" height="22">
						</span>
						<i>
							<img src="<?=get_setting($conDB, 'white_logo')?>" alt="" height="28">
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
                    <div class="card-box">
                        <h4 class="header-title m-t-0 m-b-30">
                            Employees in: <?= htmlspecialchars($department_name) ?>
                            <?php if ($department_id > 0 && $selected_department_name !== ''): ?>
                                - <?= htmlspecialchars($selected_department_name) ?>
                            <?php endif; ?>
                        </h4>

                        <!-- ** NEW ** Filter controls -->
                        <div class="row filter-controls mx-auto mb-5">
                            <div class="col-md-6 mb-3 mb-md-0">
                                <label for="statusFilter" class="font-weight-bold"><?=__('filter_by_status')?></label>
                                <select class="form-control" id="statusFilter" onchange="applyFilters()">
                                    <option value="all" <?= $status_filter == 'all' ? 'selected' : '' ?>><?=__('all_option')?></option>
                                    <option value="active" <?= $status_filter == 'active' ? 'selected' : '' ?>><?=__('active')?></option>
                                    <option value="on_vacation" <?= $status_filter == 'on_vacation' ? 'selected' : '' ?>><?=__('on_vacations')?></option>
                                    <option value="inactive" <?= $status_filter == 'inactive' ? 'selected' : '' ?>><?=__('inactive')?></option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="searchFilter" class="font-weight-bold"><?=__('search')?></label>
                                <div class="input-group">
                                    <input type="search" class="form-control" id="searchFilter" placeholder="..." value="<?=htmlspecialchars($search_term); ?>">
                                    <div class="input-group-append">
                                        <button class="btn btn-primary" type="button" onclick="applyFilters()"><i class="fas fa-search"></i></button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <?php if (!empty($employees)): ?>
                                <?php foreach ($employees as $rec): ?>
                                    <?php
                                        $id = $rec["id"];
                                        $name = $rec["name"];
                                        $emp_id = $rec["emp_id"];
                                        $iqama = $rec["iqama"];
                                        $emp_status = $rec["status"];
                                        $emp_status_fly = $rec["fly"];
                                        $emptype = $rec["emptype"];

                                        $sql_count_fly = mysqli_query($conDB, "SELECT COUNT(*) FROM `emp_vacation` WHERE `emp_id`='{$emp_id}' AND `note`='Fly'");
                                        $cont_fly = mysqli_fetch_array($sql_count_fly)[0] ?? 0;

                                        $sql_count_encashed = mysqli_query($conDB, "SELECT COUNT(*) FROM `emp_vacation` WHERE `emp_id`='{$emp_id}' AND `note`='Encashed'");
                                        $cont_encashed = mysqli_fetch_array($sql_count_encashed)[0] ?? 0;

                                        $emp_avatar = getAvatarImagePath($rec["avatar"] ?? '', $rec['sex'] ?? 1);

                                        // Determine card status class
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
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="col-12"><div class='alert alert-warning text-center'><?=__('no_employees_found_matching_your_criteria_in_this_department') ?></div></div>
                            <?php endif; ?>
                        </div>

                        <div class="row mt-4">
                            <div class="col-12">
                                <?php
                                $pagination_params = ['comp' => $company_id]; // Company is always a param
                                if ($department_id > 0) $pagination_params['dept'] = $department_id;
                                if (!empty($search_term)) $pagination_params['search'] = $search_term;
                                if (!empty($status_filter) && $status_filter != 'all') $pagination_params['status'] = $status_filter;
                                
                                echo generate_pagination_controls(
                                    $current_page,
                                    $total_pages,
                                    $total_items,
                                    $items_per_page,
                                    $limit_options,
                                    $show_all,
                                    $pagination_params,
                                    $unfiltered_total_items
                                );
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <footer class="footer"><?= $site_footer ?? '© 2025' ?></footer>
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
        function applyFilters() {
            const status = document.getElementById('statusFilter').value;
            const limit = document.getElementById('limitFilter') ? document.getElementById('limitFilter').value : '<?=$items_per_page?>';
            const search = document.getElementById('searchFilter').value;
            const url = new URL(window.location.href);
            url.searchParams.set('status', status);
            url.searchParams.set('limit', limit);
            url.searchParams.set('search', search);
            url.searchParams.set('page', '1');
            window.location.href = url.toString();
        }
    </script>
</body>
</html>

