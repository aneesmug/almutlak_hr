<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/session_check.php';
$query = mysqli_query($conDB, "SELECT * FROM `admin_login` WHERE `id_iqama`='" . $username . "'");
if (mysqli_num_rows($query) == 1) {
	include("./includes/avatar_select.php");
}

$can_see_all_employees = function_exists('canSeeAllEmployeesByRole')
	? canSeeAllEmployeesByRole(true)
	: ($is_system_admin || $user_type == 'administrator' || $user_dept == 5 || $isHR || $isDeptHr || $user_dept == 1);

$has_explicit_scope_restrictions = function_exists('hasExplicitEmployeeScopeRestrictions')
	? hasExplicitEmployeeScopeRestrictions(true)
	: (!empty($allowed_companies_array) || !empty($allowed_departments_array) || !empty($allowed_employees_array));

// This block seems to be for department-level access control, retaining it as is.
if (isset($_GET['dept'])) {
	$gdept = mysqli_query($conDB, "SELECT * FROM `department` WHERE `id`={$_GET['dept']} ");
	$allDeptData = mysqli_fetch_all($gdept, MYSQLI_ASSOC);
	if (isset($_GET['dept']) && isset($_SESSION['user_type']) && isset($_SESSION['user_dept'])) {
		$requested_dept = (int)$_GET['dept'];
		$accessible_departments = function_exists('getAccessibleDepartments') ? getAccessibleDepartments(true) : [];
		$allowed_by_department = !empty($accessible_departments) && in_array($requested_dept, $accessible_departments, true);

		$allowed_by_employee = false;
		if (!empty($allowed_employees_array)) {
			$allowed_emp_ids = implode(',', array_map('intval', $allowed_employees_array));
			$dept_check_sql = "SELECT 1 FROM employees WHERE dept = ? AND emp_id IN ($allowed_emp_ids) LIMIT 1";
			$dept_check_stmt = $conDB->prepare($dept_check_sql);
			if ($dept_check_stmt) {
				$dept_check_stmt->bind_param('i', $requested_dept);
				$dept_check_stmt->execute();
				$dept_check_stmt->store_result();
				$allowed_by_employee = $dept_check_stmt->num_rows > 0;
				$dept_check_stmt->close();
			}
		}

		$allowed_by_legacy_fallback = (!$has_explicit_scope_restrictions && !$can_see_all_employees && (int)$user_dept === $requested_dept);

		if (!$can_see_all_employees && !$allowed_by_department && !$allowed_by_employee && !$allowed_by_legacy_fallback) {
			$_SESSION['error_msg'] = sprintf(
				'<div class="col-xl-12">
							<div class="alert alert-danger bg-danger text-white border-0" role="alert">
								<b>Error ooooh!</b> 
								<h3>You don\'t have access for (%s) Department.</h3>
							</div>
						</div>',
				$allDeptData[0]['dep_nme']
			);
			header("Location: dashbydepart.php");
			exit;
		}
	}
}

// --- Search, Pagination & Filtering Logic ---
$search_term = $_GET['search'] ?? '';
$limit_options = [12, 24, 36, 48];
$per_page = 12;
$items_per_page = isset($_GET['limit']) && in_array((int)$_GET['limit'], $limit_options) ? (int)$_GET['limit'] : $per_page;
$show_all = isset($_GET['limit']) && $_GET['limit'] == 'all';
if ($show_all) {
    $items_per_page = -1;
}

$current_page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
if ($current_page < 1) {
    $current_page = 1;
}

$employees = [];
$total_items = 0;
$total_pages = 1;
$construct = "";
$params = [];
$types = "";

if (strlen($search_term) > 1) {
	$search_exploded = explode(" ", $search_term);
	$construct_parts = [];
	foreach ($search_exploded as $search_each) {
		$construct_parts[] = "(`name` LIKE ? OR `iqama` LIKE ? OR `mobile` LIKE ? OR `emp_id` LIKE ?)";
		$search_param = "%{$search_each}%";
		array_push($params, $search_param, $search_param, $search_param, $search_param);
		$types .= "ssss";
	}
	$construct = implode(" AND ", $construct_parts);

	// Apply effective employee scope only so explicitly allowed employees
	// are not hidden by company/department intersection.
	$employee_filter = getEmployeeFilterSQL('emp_id', false);
	if (!empty($employee_filter)) {
		$construct .= $employee_filter;
	}

	if (!$can_see_all_employees && !$has_explicit_scope_restrictions && !empty($user_dept)) {
		$construct .= " AND `dept`='" . mysqli_real_escape_string($conDB, $user_dept) . "'";
	}
}

if (!empty($construct)) {
    // Get total count
    $count_query = "SELECT COUNT(*) as totalCount FROM `employees` WHERE " . $construct;
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

    // Get paginated data
    if ($total_items > 0) {
        $data_query = "SELECT * FROM `employees` WHERE " . $construct . " ORDER BY `created_at` DESC";

        $main_params = $params;
        $main_types = $types;

        if (!$show_all && $items_per_page > 0) {
            $offset = ($current_page - 1) * $items_per_page;
            $data_query .= " LIMIT ?, ?";
            $main_params[] = $offset;
            $main_params[] = $items_per_page;
            $main_types .= "ii";
        }
        
        $stmt_data = $conDB->prepare($data_query);
        if(!empty($main_params)) {
            $stmt_data->bind_param($main_types, ...$main_params);
        }
        $stmt_data->execute();
        $result = $stmt_data->get_result();
        while($rec = $result->fetch_assoc()) {
            $employees[] = $rec;
        }
        $stmt_data->close();
    }
}
$_SESSION["foundnum"] = $total_items;
// --- ** NEW ** Get the total unfiltered count ---
// This query runs first to get the grand total before any search filters are applied.
$employee_filter_unf = getEmployeeFilterSQL('emp_id', false);
$fallback_dept_clause = (!$can_see_all_employees && !$has_explicit_scope_restrictions && !empty($user_dept))
	? " AND `dept`='" . mysqli_real_escape_string($conDB, $user_dept) . "'"
	: "";
$unfiltered_sql = "SELECT COUNT(*) as total FROM employees WHERE 1=1" . $employee_filter_unf . $fallback_dept_clause;
$unfiltered_result = mysqli_query($conDB, $unfiltered_sql);
$unfiltered_total_items = mysqli_fetch_assoc($unfiltered_result)['total'] ?? 0;
?>
<!doctype html>
<html lang="<?= $current_lang ?? 'en' ?>" <?= ($is_rtl ?? false) ? 'dir="rtl"' : '' ?>>

<head>
	<meta charset="utf-8" />
	<title><?= $site_title ?> - Search Results</title>
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

		/* Search employees - search toolbar (same look as filter_employee_by_dept.php) */
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
	<script>
		window.lang = <?= json_encode($GLOBALS['translations'] ?? []) ?>;
	</script>
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

					<div class="row">
						<div class="col-lg-12">
							<div class="search-result-box m-t-30 card-box">
								<div class="row">
									<div class="col-md-8 offset-md-2">
										<div class="pt-3 pb-4">
											<div class="dept-search-group">
												<div class="dept-search-field">
													<label for="searchFilter" class="dept-search-label"><?= __('search') ?></label>
													<div class="dept-search-input">
														<i class="mdi mdi-magnify"></i>
														<input type="search" class="form-control" id="searchFilter" placeholder="..." value="<?= htmlspecialchars($search_term); ?>" autocomplete="off">
													</div>
												</div>
											</div>
										</div>
									</div>
								</div>
								<ul class="nav nav-tabs tabs-bordered">
									<li class="nav-item">
										<a href="#home" data-toggle="tab" aria-expanded="true" class="nav-link active">
											<?= __('all_results') ?> <span class="badge badge-success ml-1" id="searchResultsCount"><?= $total_items ?></span>
										</a>
									</li>
								</ul>
								<div class="tab-content">
									<div class="tab-pane active" id="home">
										<div class="row">
											<div class="col-lg-12">
												<div class="row" id="searchCardsContainer">
                                                <?php if (strlen($search_term) <= 1): ?>
                                                    <div class="col-12"><div class='alert alert-danger w-100'>Sorry! there are no result for found! ( <strong>Search is too short</strong> ) </div></div>
                                                <?php elseif (empty($employees)): ?>
                                                    <div class="col-12">
                                                        <div class='alert alert-danger w-100'>Sorry! there are no result for found! ( <strong><?= htmlspecialchars($search_term) ?></strong> ) </div>
                                                        <br>
                                                        <div class='alert alert-warning'>
                                                            <strong>1.</strong> Try more general words.<br>
                                                            <strong>2.</strong> Try different words with similar meaning.<br>
                                                            <strong>3.</strong> Please check your spelling.
                                                        </div>
                                                    </div>
                                                <?php else: ?>
                                                    <div class="col-12"><div class='alert alert-custom bg-custom text-white border-0 w-100'>"<?= htmlspecialchars($search_term) ?>" <strong><?= $total_items ?></strong> <?= __('results_are_found') ?>!</div></div>
                                                    <?php foreach ($employees as $rec):
                                                        $id = $rec["id"];
                                                        $name = htmlspecialchars($rec["name"]);
                                                        $emp_id = htmlspecialchars($rec["emp_id"]);
                                                        $iqama = htmlspecialchars($rec["iqama"]);
                                                        $emptype = $rec["emptype"];
                                                        $emp_status = $rec["status"];
                                                        $emp_status_fly = $rec["fly"];
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
                                                <?php endif; ?>
												</div>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>

                    <div class="row mt-4">
                        <div class="col-12" id="searchPaginationContainer"></div>
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
		// Live AJAX employee search - search box, limit & pagination all fetch in
		// place. Pagination is built entirely from plain <button> elements (no
		// <a href> anywhere) so there is nothing for the browser to navigate to.
		let searchDebounceTimer = null;
		const searchLimitOptions = <?= json_encode($limit_options) ?>;

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
			searchLimitOptions.forEach(function(opt) {
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

		function loadSearchEmployees(page) {
			const $cards = $('#searchCardsContainer');
			const $pagination = $('#searchPaginationContainer');
			const limitElement = document.getElementById('limitFilter');
			const limit = limitElement ? limitElement.value : searchLimitOptions[0];
			const search = document.getElementById('searchFilter').value;

			const lockedHeight = $cards.outerHeight();
			if (lockedHeight) {
				$cards.css('min-height', lockedHeight + 'px');
			}
			$cards.css({ opacity: 0.45, 'pointer-events': 'none' });

			$.ajax({
				url: './includes/ajaxFile/get_search_employees.php',
				type: 'POST',
				dataType: 'json',
				data: { search: search, limit: limit, page: page }
			}).done(function(response) {
				if (!response || response.status !== 200) {
					return;
				}
				$cards.html(response.cards_html);
				$('#searchResultsCount').text(response.total_items);
				renderStandardPagination($pagination, {
					currentPage: response.current_page,
					totalPages: response.total_pages,
					totalItems: response.total_items,
					itemsPerPage: response.items_per_page,
					showAll: response.show_all,
					unfilteredTotalItems: response.unfiltered_total_items
				}, function(newPage) { loadSearchEmployees(newPage); }, function() { loadSearchEmployees(1); });
			}).always(function() {
				$cards.css({ opacity: 1, 'pointer-events': 'auto', 'min-height': '' });
			});
		}

		document.getElementById('searchFilter').addEventListener('input', function() {
			clearTimeout(searchDebounceTimer);
			searchDebounceTimer = setTimeout(function() {
				loadSearchEmployees(1);
			}, 350);
		});

		// Initial paint - build pagination from the server-rendered first page's
		// numbers without an extra AJAX round-trip.
		renderStandardPagination($('#searchPaginationContainer'), {
			currentPage: <?= (int)$current_page ?>,
			totalPages: <?= (int)$total_pages ?>,
			totalItems: <?= (int)$total_items ?>,
			itemsPerPage: <?= (int)$items_per_page ?>,
			showAll: <?= $show_all ? 'true' : 'false' ?>,
			unfilteredTotalItems: <?= (int)$unfiltered_total_items ?>
		}, function(newPage) { loadSearchEmployees(newPage); }, function() { loadSearchEmployees(1); });
	</script>
</body>
</html>
