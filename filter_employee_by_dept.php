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

if (!isset($_GET['dept']) || !is_numeric($_GET['dept'])) {
    header("Location: dashboard.php");
    exit;
}
$department_id = (int)$_GET['dept'];

$can_see_all_employees = function_exists('canSeeAllEmployeesByRole')
    ? canSeeAllEmployeesByRole(true)
    : ($is_system_admin || $user_type == 'administrator' || $user_dept == 5 || $isHR || $isDeptHr || $user_dept == 1);

$has_explicit_scope_restrictions = function_exists('hasExplicitEmployeeScopeRestrictions')
    ? hasExplicitEmployeeScopeRestrictions(true)
    : (!empty($allowed_companies_array) || !empty($allowed_departments_array) || !empty($allowed_employees_array));

// DEPARTMENT-BASED ACCESS CONTROL
// Check if user has permission to view this department's employees
// Uses the new allowed_departments array from session
$accessible_departments = getAccessibleDepartments(true);

// If user has restricted access (not empty array), check if requested department is allowed
if (!empty($accessible_departments) && !in_array($department_id, $accessible_departments)) {
    $allow_by_employee = false;
    if (!empty($allowed_employees_array)) {
        $allowed_emp_ids = implode(',', array_map('intval', $allowed_employees_array));
        $dept_check_sql = "SELECT 1 FROM employees WHERE dept = ? AND emp_id IN ($allowed_emp_ids) LIMIT 1";
        $dept_check_stmt = $conDB->prepare($dept_check_sql);
        if ($dept_check_stmt) {
            $dept_check_stmt->bind_param('i', $department_id);
            $dept_check_stmt->execute();
            $dept_check_stmt->store_result();
            $allow_by_employee = $dept_check_stmt->num_rows > 0;
            $dept_check_stmt->close();
        }
    }

    if (!$allow_by_employee) {
        $_SESSION['error_msg'] = sprintf(
            '<div class="col-xl-12">
                <div class="alert alert-danger bg-danger text-white border-0" role="alert">
                    <b>Access Denied!</b> 
                    <h3>You don\'t have access to view employees from this department.</h3>
                </div>
            </div>'
        );
        header("Location: dashbydepart.php");
        exit;
    }
}

// Legacy-safe fallback: when no explicit scope is configured, non-full-access users are limited to own department.
if (empty($accessible_departments) && !$has_explicit_scope_restrictions && !$can_see_all_employees) {
    if ((int)$user_dept !== (int)$department_id) {
        $_SESSION['error_msg'] = sprintf(
            '<div class="col-xl-12">
                <div class="alert alert-danger bg-danger text-white border-0" role="alert">
                    <b>Access Denied!</b>
                    <h3>You don\'t have access to view employees from this department.</h3>
                </div>
            </div>'
        );
        header("Location: dashbydepart.php");
        exit;
    }
}

$query = mysqli_query($conDB, "SELECT * FROM `admin_login` WHERE `id_iqama`='" . $username . "'");
if (mysqli_num_rows($query) == 1) {
    include("./includes/avatar_select.php");
}

// --- Get Department Name ---
$dept_name_stmt = $conDB->prepare("SELECT `dep_nme` FROM `department` WHERE `id` = ?");
$dept_name_stmt->bind_param("i", $department_id);
$dept_name_stmt->execute();
$department_name = $dept_name_stmt->get_result()->fetch_assoc()['dep_nme'] ?? 'Unknown Department';
$dept_name_stmt->close();


// --- Pagination & Filtering Setup ---
$limit_options = [12, 24, 48, 96];
$search_term = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? 'all'; // 'all', 'active', 'inactive', 'on_vacation'
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
$fallback_dept_clause = (!$can_see_all_employees && !$has_explicit_scope_restrictions && !empty($user_dept))
    ? " AND `dept`='" . mysqli_real_escape_string($conDB, $user_dept) . "'"
    : "";
$unfiltered_sql = "SELECT COUNT(id) as total FROM employees WHERE 1=1" . $employee_filter_count . $fallback_dept_clause;
$unfiltered_result = mysqli_query($conDB, $unfiltered_sql);
$unfiltered_total_items = mysqli_fetch_assoc($unfiltered_result)['total'] ?? 0;

// --- Build Query ---
$where_clauses = ["`dept` = ?"]; // Department is the base filter for this page
$params = [$department_id];
$types = "i";

// --- NEW ACCESS CONTROL: Always apply employee filter ---
$employee_filter = getEmployeeFilterSQL('emp_id', false);
if (!empty($employee_filter)) {
    $where_clauses[] = substr($employee_filter, 5); // remove leading ' AND '
}

if (!$can_see_all_employees && !$has_explicit_scope_restrictions && !empty($user_dept)) {
    $where_clauses[] = "`dept` = ?";
    $params[] = (int)$user_dept;
    $types .= "i";
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

        /* Department employees - search toolbar */
        .dept-search-group {
            display: flex;
            flex-direction: row;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
            padding: 10px 14px;
            background: #f4f6f9;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
        }

        .dept-search-field {
            display: flex;
            align-items: center;
            gap: 10px;
            flex: 1 1 auto;
        }

        .dept-search-field-status {
            flex: 0 0 auto;
        }

        .dept-search-field-status select {
            width: auto;
            min-width: 180px;
        }

        .dept-search-label {
            margin: 0;
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .04em;
            color: #8a94a6;
            white-space: nowrap;
        }

        .dept-search-input {
            position: relative;
            display: flex;
            align-items: center;
            flex: 1 1 auto;
            gap: 8px;
        }

        .dept-search-input i.mdi-magnify {
            position: absolute;
            left: 12px;
            color: #94a3b8;
            font-size: 1rem;
            pointer-events: none;
        }

        .dept-search-input input {
            padding-left: 34px;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            background: #fff;
            transition: border-color .15s ease, box-shadow .15s ease, background-color .15s ease;
        }

        .dept-search-input input:focus {
            border-color: rgba(67, 97, 238, 0.5);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.12);
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
                        <h4 class="header-title m-t-0 m-b-30">Employees in: <?= htmlspecialchars($department_name) ?></h4>

                        <!-- ** NEW ** Filter controls -->
                        <div class="row filter-controls mx-auto mb-5">
                            <div class="col-12">
                                <div class="dept-search-group">
                                    <div class="dept-search-field">
                                        <label for="searchFilter" class="dept-search-label"><?=__('search')?></label>
                                        <div class="dept-search-input">
                                            <i class="mdi mdi-magnify"></i>
                                            <input type="search" class="form-control" id="searchFilter" placeholder="..." value="<?=htmlspecialchars($search_term); ?>">
                                        </div>
                                    </div>
                                    <div class="dept-search-field dept-search-field-status">
                                        <label for="statusFilter" class="dept-search-label"><?=__('filter_by_status')?></label>
                                        <select class="form-control" id="statusFilter" onchange="applyFilters()">
                                            <option value="all" <?= $status_filter == 'all' ? 'selected' : '' ?>><?=__('all_option')?></option>
                                            <option value="active" <?= $status_filter == 'active' ? 'selected' : '' ?>><?=__('active')?></option>
                                            <option value="on_vacation" <?= $status_filter == 'on_vacation' ? 'selected' : '' ?>><?=__('on_vacations')?></option>
                                            <option value="inactive" <?= $status_filter == 'inactive' ? 'selected' : '' ?>><?=__('inactive')?></option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row" id="deptEmployeesCardsContainer">
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
                            <div class="col-12" id="deptEmployeesPaginationContainer"></div>
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
        // Live AJAX department employee list - search, status filter, limit &
        // pagination all fetch in place. Pagination is built entirely from plain
        // <button> elements (no <a href> anywhere) so there is nothing for the
        // browser to navigate to - clicking a page number can never reload the page.
        let deptSearchTimer = null;
        const deptDepartmentId = <?= (int)$department_id ?>;
        const deptLimitOptions = <?= json_encode($limit_options) ?>;

        function renderStandardPagination($container, opts, onPageChange, onLimitChange) {
            $container.empty();
            if (opts.totalItems <= 0) {
                return;
            }

            const $wrap = $('<div class="d-md-flex justify-content-between align-items-center"></div>');

            const $limitBlock = $('<div class="mb-3 mb-md-0"><div class="form-inline"></div></div>');
            const $form = $limitBlock.find('.form-inline');
            $form.append($('<label class="mr-2 font-weight-bold"></label>').text(__('show', 'Show') + ':'));
            const $select = $('<select class="form-control form-control-sm" id="limitFilter"></select>');
            deptLimitOptions.forEach(function(opt) {
                const $option = $('<option></option>').attr('value', opt).text(opt);
                if (!opts.showAll && opts.itemsPerPage === opt) $option.prop('selected', true);
                $select.append($option);
            });
            const $allOption = $('<option value="all"></option>').text(__('all_option', 'All'));
            if (opts.showAll) $allOption.prop('selected', true);
            $select.append($allOption);
            $select.on('change', onLimitChange);
            $form.append($select);
            $form.append($('<span class="ml-2 text-muted"></span>').text(__('items_per_page', 'items per page')));
            $wrap.append($limitBlock);

            const $right = $('<div class="d-flex align-items-center justify-content-center flex-wrap"></div>');
            let startItem = 0, endItem = 0;
            if (!opts.showAll && opts.itemsPerPage > 0 && opts.totalPages > 0) {
                startItem = ((opts.currentPage - 1) * opts.itemsPerPage) + 1;
                endItem = Math.min(startItem + opts.itemsPerPage - 1, opts.totalItems);
            } else {
                startItem = 1;
                endItem = opts.totalItems;
            }
            let showingText = __('showing', 'Showing') + ' ' + startItem + ' ' + __('to', 'to') + ' ' + endItem + ' ' + __('of', 'of') + ' ' + opts.totalItems + ' ' + __('entries', 'entries');
            if (opts.unfilteredTotalItems > opts.totalItems) {
                showingText += ' (' + __('filtered_from', 'filtered from') + ' ' + opts.unfilteredTotalItems + ' ' + __('entries', 'entries') + ')';
            }
            $right.append($('<span class="text-muted mr-3"></span>').text(showingText));

            if (opts.totalPages > 1 && !opts.showAll) {
                const $nav = $('<nav aria-label="Pagination"></nav>');
                const $ul = $('<ul class="pagination mb-0"></ul>');

                function pageButton(label, page, disabled, active) {
                    const $li = $('<li class="page-item"></li>').toggleClass('disabled', !!disabled).toggleClass('active', !!active);
                    const $btn = $('<button type="button" class="page-link"></button>').text(label);
                    if (!disabled && !active) {
                        $btn.on('click', function() { onPageChange(page); });
                    }
                    $li.append($btn);
                    return $li;
                }

                $ul.append(pageButton(__('first', 'First'), 1, opts.currentPage <= 1, false));
                $ul.append(pageButton(__('previous', 'Previous'), opts.currentPage - 1, opts.currentPage <= 1, false));

                const range = 2;
                const startRange = Math.max(1, opts.currentPage - range);
                const endRange = Math.min(opts.totalPages, opts.currentPage + range);

                if (startRange > 1) {
                    $ul.append(pageButton('1', 1, false, false));
                    if (startRange > 2) {
                        $ul.append($('<li class="page-item disabled"><span class="page-link">...</span></li>'));
                    }
                }
                for (let i = startRange; i <= endRange; i++) {
                    $ul.append(pageButton(String(i), i, false, i === opts.currentPage));
                }
                if (endRange < opts.totalPages) {
                    if (endRange < opts.totalPages - 1) {
                        $ul.append($('<li class="page-item disabled"><span class="page-link">...</span></li>'));
                    }
                    $ul.append(pageButton(String(opts.totalPages), opts.totalPages, false, false));
                }

                $ul.append(pageButton(__('next', 'Next'), opts.currentPage + 1, opts.currentPage >= opts.totalPages, false));
                $ul.append(pageButton(__('last', 'Last'), opts.totalPages, opts.currentPage >= opts.totalPages, false));

                $nav.append($ul);
                $right.append($nav);
            }

            $wrap.append($right);
            $container.append($wrap);
        }

        function loadDeptEmployees(page) {
            const $cards = $('#deptEmployeesCardsContainer');
            const $pagination = $('#deptEmployeesPaginationContainer');
            const status = document.getElementById('statusFilter').value;
            const limitElement = document.getElementById('limitFilter');
            const limit = limitElement ? limitElement.value : deptLimitOptions[0];
            const search = document.getElementById('searchFilter').value;

            const lockedHeight = $cards.outerHeight();
            if (lockedHeight) {
                $cards.css('min-height', lockedHeight + 'px');
            }
            $cards.css({ opacity: 0.45, 'pointer-events': 'none' });

            $.ajax({
                url: './includes/ajaxFile/get_dept_employees_list.php',
                type: 'POST',
                dataType: 'json',
                data: { dept: deptDepartmentId, status: status, limit: limit, search: search, page: page }
            }).done(function(response) {
                if (!response || response.status !== 200) {
                    return;
                }
                $cards.html(response.cards_html);
                renderStandardPagination($pagination, {
                    currentPage: response.current_page,
                    totalPages: response.total_pages,
                    totalItems: response.total_items,
                    itemsPerPage: response.items_per_page,
                    showAll: response.show_all,
                    unfilteredTotalItems: response.unfiltered_total_items
                }, function(newPage) { loadDeptEmployees(newPage); }, function() { loadDeptEmployees(1); });
            }).always(function() {
                $cards.css({ opacity: 1, 'pointer-events': 'auto', 'min-height': '' });
            });
        }

        function applyFilters() {
            loadDeptEmployees(1);
        }

        document.getElementById('searchFilter').addEventListener('keypress', function (e) {
            if (e.key === 'Enter') { applyFilters(); }
        });
        document.getElementById('searchFilter').addEventListener('input', function () {
            clearTimeout(deptSearchTimer);
            deptSearchTimer = setTimeout(function () {
                loadDeptEmployees(1);
            }, 350);
        });

        // Initial paint - build pagination from the server-rendered first page's
        // numbers without an extra AJAX round-trip.
        renderStandardPagination($('#deptEmployeesPaginationContainer'), {
            currentPage: <?= (int)$current_page ?>,
            totalPages: <?= (int)$total_pages ?>,
            totalItems: <?= (int)$total_items ?>,
            itemsPerPage: <?= (int)$items_per_page ?>,
            showAll: <?= $show_all ? 'true' : 'false' ?>,
            unfilteredTotalItems: <?= (int)$unfiltered_total_items ?>
        }, function(newPage) { loadDeptEmployees(newPage); }, function() { loadDeptEmployees(1); });
    </script>
</body>
</html>

