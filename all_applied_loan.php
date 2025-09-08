<?php
/****************************************************************
 * MODIFICATION SUMMARY (015-all_applied_loan.php):
 * 1.  ROBUST VARIABLE ASSIGNMENT: Moved the explicit fetching of user data (`user_type`, `emp_type`, `user_dept`) to run BEFORE the `avatar_select.php` include. This prevents the include file from overwriting the correct user data and ensures the role detection logic works reliably.
 * 2.  REVISED WORKFLOW: The role definitions and status arrays have been updated to match the new approval chain:
 * Dept Manager -> HR Assistant -> HR Manager -> Finance Manager -> GM -> Finance Assistant
 * 3.  UPDATED STATUSES: The `$all_loan_statuses` array now includes `hr_assistant_pending` and `hr_manager_pending` in the correct order for the filter dropdown.
 * 4.  REVISED BUTTON VISIBILITY: The logic that determines which approval buttons are shown (`$can_take_action`) has been rewritten to accurately reflect each user's role in the new, longer approval process.
 * 5.  ADDED EMPLOYEE ID TO GM FUNCTION: The `emp_id` is now passed to the `modifyAndApproveLoan` function so it can fetch End of Service details for the GM's view.
 * 6.  PAGINATION IMPLEMENTED: Added full pagination controls, including an items-per-page selector, to handle large numbers of loan requests efficiently.
 ****************************************************************/
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/session_check.php';

$query = mysqli_query($conDB, "SELECT * FROM `admin_login` WHERE `id_iqama`='" . $username . "'");
if (mysqli_num_rows($query) == 1) {
    
    // --- FIX START: Fetch data BEFORE the include to prevent overwrites ---
    $user_data = mysqli_fetch_assoc($query);
    $user_type = $user_data['user_type'];
    $emp_type = $user_data['emp_type'];
    $user_dept = $user_data['dept'];
    mysqli_data_seek($query, 0); // Reset pointer for the include file if it needs it
    // --- FIX END ---

    // Include the file, which may use its own variables or the reset $query.
    include("./includes/avatar_select.php");

    // --- Corrected Role Definition ---
    $user_role = 'Employee'; // Default role

    if ($user_type == 'administrator') {
        $user_role = 'administrator';
    } elseif ($user_type == 'gm') {
        $user_role = 'GM';
    } elseif ($user_type == 'hr') {
        $user_role = 'HR_Manager';
    } elseif ($user_type == 'assistant' && $user_dept == 5) { // Dept 5 is HR
        $user_role = 'HR_Assistant';
    } elseif ($emp_type == 'Manager' && $user_dept == 2) {
        $user_role = 'Finance_Manager';
    } elseif ($user_type == 'assistant' && $user_dept == 2) { // Dept 2 is Finance
        $user_role = 'Finance_Assistant';
    } elseif ($emp_type == 'Manager') {
        $user_role = 'DPT_Manager';
    }


    // --- Loan Request Logic ---
    $all_loan_statuses = [
        'dept_manager_pending' => __('pending_department_manager'),
        'hr_assistant_pending' => __('pending_hr_assistant'),
        'hr_manager_pending' => __('pending_hr_manager'),
        'finance_manager_pending' => __('pending_finance_manager'),
        'gm_pending' => __('pending_gm'),
        'finance_assistant_pending' => __('pending_final_processing'),
        'approved' => __('approved_and_processed'),
        'paid' => __('paid_and_closed'),
        'rejected' => __('rejected')
    ];
    
    // --- Search, Pagination & Filtering Logic ---
    $search_term = $_GET['search'] ?? '';
    $limit_options = [8, 12, 16];
    $perpage = 8;
    $items_per_page = isset($_GET['limit']) && in_array((int)$_GET['limit'], $limit_options) ? (int)$_GET['limit'] : $perpage;
    $show_all = isset($_GET['limit']) && $_GET['limit'] == 'all';
    if ($show_all) {
        $items_per_page = -1;
    }

    $current_page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
    if ($current_page < 1) {
        $current_page = 1;
    }
    
    $current_filter = $_GET['status'] ?? null; // Start with null

    // Determine the default filter if none is selected in the URL
    if ($current_filter === null) {
        switch ($user_role) {
            case 'DPT_Manager': $current_filter = 'dept_manager_pending'; break;
            case 'HR_Assistant': $current_filter = 'hr_assistant_pending'; break;
            case 'HR_Manager': $current_filter = 'hr_manager_pending'; break;
            case 'Finance_Manager': $current_filter = 'finance_manager_pending'; break;
            case 'GM': $current_filter = 'gm_pending'; break;
            case 'Finance_Assistant': $current_filter = 'finance_assistant_pending'; break;
            default: $current_filter = 'all'; break; // Default for admin or others
        }
    }

    $loan_where_clauses = [];
    $loan_params = [];
    $loan_types = "";
    $page_title = __('loan_requests');

    // Build query based on the final filter value
    if ($current_filter !== 'all') {
        if (array_key_exists($current_filter, $all_loan_statuses)) {
            $loan_where_clauses[] = "l.status = ?";
            $loan_params[] = $current_filter;
            $loan_types .= "s";
            $page_title = $all_loan_statuses[$current_filter];

            // Add department-specific filter for DPT managers
            if ($user_role === 'DPT_Manager') {
                $loan_where_clauses[] = "e.dept = ?";
                $loan_params[] = $user_dept;
                $loan_types .= "i";
            }
        } else {
             $loan_where_clauses[] = "1=0"; // No valid status, show nothing
        }
    } else {
        $page_title = __('all_loan_requests');
    }
    
    // Add search term filter if it exists
    if (!empty($search_term)) {
        $loan_where_clauses[] = "(e.name LIKE ? OR l.emp_id LIKE ?)";
        $search_param = "%{$search_term}%";
        $loan_params[] = $search_param;
        $loan_params[] = $search_param;
        $loan_types .= "ss";
    }

    $loan_where_sql = "";
    if (!empty($loan_where_clauses)) {
        $loan_where_sql = " WHERE " . implode(" AND ", $loan_where_clauses);
    }
    
    // First, get the total count of items for pagination
    $count_sql = "SELECT COUNT(l.id) as total FROM emp_loan l JOIN employees e ON l.emp_id = e.emp_id" . $loan_where_sql;
    $stmt_count = $conDB->prepare($count_sql);
    if (!empty($loan_params)) {
        $stmt_count->bind_param($loan_types, ...$loan_params);
    }
    $stmt_count->execute();
    $total_items = $stmt_count->get_result()->fetch_assoc()['total'] ?? 0;
    $stmt_count->close();

    $total_pages = $show_all ? 1 : ceil($total_items / $items_per_page);
    if ($current_page > $total_pages && $total_pages > 0) {
        $current_page = $total_pages;
    }

    // Now, fetch the data for the current page
    $loan_requests = [];
    if ($total_items > 0) {
        $loan_sql = "SELECT l.*, e.name as employee_name, e.dept, (l.total_payable / l.monthly_deduction) as installments FROM emp_loan l JOIN employees e ON l.emp_id = e.emp_id" . $loan_where_sql . " ORDER BY l.created_at DESC";
        
        $main_params = $loan_params;
        $main_types = $loan_types;

        if (!$show_all) {
            $offset = ($current_page - 1) * $items_per_page;
            $loan_sql .= " LIMIT ?, ?";
            $main_params[] = $offset;
            $main_params[] = $items_per_page;
            $main_types .= "ii";
        }

        $stmt_loan = $conDB->prepare($loan_sql);
        if (!empty($main_params)) {
            $stmt_loan->bind_param($main_types, ...$main_params);
        }
        $stmt_loan->execute();
        $loan_result = $stmt_loan->get_result();
        if ($loan_result->num_rows > 0) {
            while ($row = $loan_result->fetch_assoc()) {
                $loan_requests[] = $row;
            }
        }
        $stmt_loan->close();
    }
    // ** NEW ** Get the total unfiltered count of all loan requests.
    $unfiltered_sql = "SELECT COUNT(id) as total FROM emp_loan";
    $unfiltered_result = mysqli_query($conDB, $unfiltered_sql);
    $unfiltered_total_items = mysqli_fetch_assoc($unfiltered_result)['total'] ?? 0;
?>
    <!doctype html>
    <html lang="<?= $current_lang ?? 'en' ?>" <?= ($is_rtl ?? false) ? 'dir="rtl"' : '' ?>>
    <head>
        <meta charset="utf-8" />
        <title><?= $site_title ?> - <?=__('all_loan_requests')?></title>
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
        <meta content="Anees Afzal" name="author" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />
        <link rel="shortcut icon" href="assets/images/favicon.ico">
        <link href="assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
        <link href="assets/css/metismenu.min.css" rel="stylesheet" type="text/css" />
        <link href="assets/css/style.css" rel="stylesheet" type="text/css" />
        <link href="assets/css/style_dark.css" rel="stylesheet" type="text/css" />
        <script src="assets/js/modernizr.min.js"></script>
        <style>
            .filter-controls { max-width: 800px; }
            .request-card { border-radius: 15px; border: none; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.07); transition: transform 0.3s ease, box-shadow 0.3s ease; }
            .request-card:hover { transform: translateY(-5px); box-shadow: 0 15px 40px rgba(0, 0, 0, 0.1); }
            .request-card .card-header { background-color: #fff; border-bottom: 1px solid #eef; font-weight: 600; font-size: 1.1em; }
            .request-card .card-header span { font-size: 0.9em; color: #8a94a6; }
            .request-card .card-body { padding: 1.5rem; }
            .detail-item { display: flex; align-items: center; margin-bottom: 1rem; font-size: 1.09em; }
            .detail-item i { color: #4a90e2; margin-right: 15px; width: 20px; text-align: center; }
            .detail-item strong { color: #8a94a6; min-width: 100px; display: inline-block; }
            .request-card .card-footer { background-color: #fafbff; border-top: 1px solid #eef; }
            .no-requests { padding: 3rem; background: #fff; border-radius: 15px; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.07); }
			.btn-block + .btn-block{ margin-top: 0rem !important; }
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
                            <span><img src="assets/images/logo.png" alt="" height="22"></span>
                            <i><img src="assets/images/logo_sm.png" alt="" height="28"></i>
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
                                    <h4 class="header-title m-t-0 m-b-30"><?=__('loan_approval_center')?></h4>

                                    <div class="row filter-controls mx-auto mb-5">
                                        <div class="col-md-6 mb-3 mb-md-0">
                                            <div class="form-group">
                                                <label for="statusFilter" class="font-weight-bold"><?=__('filter_by_status')?></label>
                                                <select class="form-control" id="statusFilter" onchange="applyFilters()">
                                                    <option value="all" <?php if ($current_filter == 'all') echo 'selected'; ?>><?=__('all_requests')?></option>
                                                    <?php foreach ($all_loan_statuses as $status_key => $status_value): ?>
                                                        <option value="<?=$status_key; ?>" <?php if ($current_filter == $status_key) echo 'selected'; ?>>
                                                            <?=htmlspecialchars($status_value); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="searchFilter" class="font-weight-bold"><?=__('search_by_name_id')?></label>
                                                <div class="input-group">
                                                    <input type="search" class="form-control" id="searchFilter" placeholder="<?=__('enter_search_term')?>" value="<?=htmlspecialchars($search_term); ?>">
                                                    <div class="input-group-append">
                                                        <button class="btn btn-primary" type="button" onclick="applyFilters()"><i class="fas fa-search"></i></button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="d-flex justify-content-between align-items-center mb-4">
                                        <h4 class="mb-0 text-muted"><?=__('showing')?>: <?=htmlspecialchars($page_title); ?></h4>
                                        <span class="badge badge-light p-2"><?=__('total_found')?>: <?=$total_items; ?></span>
                                    </div>

                                    <?php if (!empty($loan_requests)): ?>
                                        <div class="row">
                                            <?php foreach ($loan_requests as $loan): ?>
                                                <div class="col-lg-3 col-md-6 mb-3">
                                                    <div class="card request-card h-100">
                                                        <div class="card-header">
                                                            <?=($loan['employee_name']); ?>
                                                            <span class="float-right"><?=__('emp_id')?>: <?=htmlspecialchars($loan['emp_id']); ?></span>
                                                        </div>
                                                        <div class="card-body">
                                                            <div class="detail-item"><i class="fad fa-hand-holding-usd duotone-info"></i><strong><?=__('amount')?>:</strong> <?=htmlspecialchars($loan['loan_amount']); ?></div>
                                                            <div class="detail-item"><i class="fad fa-calendar-alt duotone-info"></i><strong><?=__('start_date')?>:</strong> <?=htmlspecialchars(date('d M Y', strtotime($loan['start_date']))); ?></div>
                                                            <div class="detail-item"><i class="fad fa-calendar-check duotone-info"></i><strong><?=__('end_date')?>:</strong> <?=htmlspecialchars(date('d M Y', strtotime($loan['end_date']))); ?></div>
                                                            <div class="detail-item"><i class="fad fa-wallet duotone-info"></i><strong><?=__('monthly')?>:</strong> <?=htmlspecialchars($loan['monthly_deduction']); ?></div>
                                                            <div class="detail-item">
                                                                <?php
                                                                    $loan_status_text = $all_loan_statuses[$loan['status']] ?? __('unknown');
                                                                    $loan_badge_class = 'secondary';
                                                                    switch ($loan['status']) {
                                                                        case 'dept_manager_pending': $loan_badge_class = 'info'; break;
                                                                        case 'hr_assistant_pending':
                                                                        case 'hr_manager_pending':
                                                                        case 'finance_manager_pending':
                                                                        case 'gm_pending':
                                                                        case 'finance_assistant_pending': $loan_badge_class = 'warning'; break;
                                                                        case 'approved': $loan_badge_class = 'success'; break;
                                                                        case 'paid': $loan_badge_class = 'primary'; break;
                                                                        case 'rejected': $loan_badge_class = 'danger'; break;
                                                                    }
                                                                ?>
                                                                <i class="fad fa-info-circle duotone-info"></i>
                                                                <strong><?=__('status')?>:</strong> <span class="badge badge-<?=$loan_badge_class; ?> p-2"><?=htmlspecialchars($loan_status_text); ?></span>
                                                            </div>
                                                        </div>
                                                        <?php
                                                            // Logic to show/hide buttons based on user role and loan status
                                                            $can_take_action = false;
                                                            $action_role = '';

                                                            if ($loan['status'] == 'dept_manager_pending' && $user_role == 'DPT_Manager' && $loan['dept'] == $user_dept && $loan['dept_manager_status'] == 'pending') {
                                                                $can_take_action = true; $action_role = 'dept_manager';
                                                            } elseif ($loan['status'] == 'hr_assistant_pending' && $user_role == 'HR_Assistant' && $loan['hr_assistant_status'] == 'pending') {
                                                                $can_take_action = true; $action_role = 'hr_assistant';
                                                            } elseif ($loan['status'] == 'hr_manager_pending' && $user_role == 'HR_Manager' && $loan['hr_manager_status'] == 'pending') {
                                                                $can_take_action = true; $action_role = 'hr_manager';
                                                            } elseif ($loan['status'] == 'finance_manager_pending' && $user_role == 'Finance_Manager' && $loan['finance_manager_status'] == 'pending') {
                                                                $can_take_action = true; $action_role = 'finance_manager';
                                                            } elseif ($loan['status'] == 'gm_pending' && $user_role == 'GM' && $loan['gm_status'] == 'pending') {
                                                                $can_take_action = true; $action_role = 'gm';
                                                            } elseif ($loan['status'] == 'finance_assistant_pending' && $user_role == 'Finance_Assistant' && $loan['finance_assistant_status'] == 'pending') {
                                                                $can_take_action = true; $action_role = 'finance_assistant';
                                                            } elseif ($user_role == 'administrator' && !in_array($loan['status'], ['approved', 'rejected', 'paid'])) {
                                                                $can_take_action = true;
                                                                $status_to_role_map = [
                                                                    'dept_manager_pending' => 'dept_manager',
                                                                    'hr_assistant_pending' => 'hr_assistant',
                                                                    'hr_manager_pending' => 'hr_manager',
                                                                    'finance_manager_pending' => 'finance_manager',
                                                                    'gm_pending' => 'gm',
                                                                    'finance_assistant_pending' => 'finance_assistant'
                                                                ];
                                                                $action_role = $status_to_role_map[$loan['status']] ?? '';
                                                            }
                                                        ?>
                                                        <div class="card-footer d-flex justify-content-between align-items-center btn-group">
                                                            <a href="loan_report_details.php?id=<?=$loan['id']; ?>&emp_id=<?=$loan['emp_id']; ?>" target="_blank" class="btn btn-info btn-block waves-effect"><i class="fa fa-eye"></i> <?=__('view')?></a>
                                                            <?php if($can_take_action && $action_role): ?>
                                                                <?php if($action_role == 'finance_assistant'): ?>
                                                                    <button class="btn btn-danger btn-block waves-effect" onclick="rejectLoanRequest(<?=$loan['id']; ?>, '<?=$action_role?>')"><i class="fa fa-times"></i> <?=__('reject')?></button>
                                                                    <button class="btn btn-primary btn-block waves-effect" onclick="finalizeLoan(<?=$loan['id']; ?>)"><i class="fa fa-check-double"></i> <?=__('finalize')?></button>
                                                                <?php elseif($action_role == 'gm'): ?>
                                                                    <button class="btn btn-danger btn-block waves-effect" onclick="rejectLoanRequest(<?=$loan['id']; ?>, '<?=$action_role?>')"><i class="fa fa-times"></i> <?=__('reject')?></button>
                                                                    <button class="btn btn-success btn-block waves-effect" onclick="modifyAndApproveLoan(<?=$loan['id']; ?>, '<?=$loan['loan_amount']?>', '<?=round($loan['installments'])?>', '<?=$loan['emp_id']?>')"><i class="fa fa-edit"></i> <?=__('approve')?></button>
                                                                <?php elseif($action_role == 'hr_assistant'): ?>
                                                                    <button class="btn btn-danger btn-block waves-effect" onclick="rejectLoanRequest(<?=$loan['id']; ?>, '<?=$action_role?>')"><i class="fa fa-times"></i> <?=__('reject')?></button>
                                                                    <button class="btn btn-success btn-block waves-effect" onclick="modifyAndApproveLoanHRAssistant(<?=$loan['id']; ?>, '<?=$loan['loan_amount']?>', '<?=round($loan['installments'])?>', '<?=$loan['emp_id']?>')"><i class="fa fa-edit"></i> <?=__('approve')?></button>
                                                                <?php else: ?>
                                                                    <button class="btn btn-danger btn-block waves-effect" onclick="rejectLoanRequest(<?=$loan['id']; ?>, '<?=$action_role?>')"><i class="fa fa-times"></i> <?=__('reject')?></button>
                                                                    <button class="btn btn-success btn-block waves-effect" onclick="approveLoanRequest(<?=$loan['id']; ?>, '<?=$action_role?>')"><i class="fa fa-check"></i> <?=__('approve')?></button>
                                                                <?php endif; ?>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>

                                        <?php
                                            $pagination_params = [];
                                            if (!empty($search_term)) $pagination_params['search'] = $search_term;
                                            if (!empty($current_filter)) $pagination_params['status'] = $current_filter;
                                            echo generate_pagination_controls($current_page,$total_pages,$total_items,$items_per_page,$limit_options,$show_all,$pagination_params,$unfiltered_total_items);
                                        ?>
                                    <?php else: ?>
                                        <div class="row justify-content-center">
                                            <div class="col-md-8">
                                                <div class="text-center no-requests">
                                                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                                    <h2><?=__('no_loan_requests_found')?></h2>
                                                    <p class="text-muted"><?=__('no_requests_matching_filters')?></p>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <footer class="footer"><?= $site_footer ?></footer>
            </div>
        </div>

        <script src="assets/js/jquery.min.js"></script>
        <script src="assets/js/bootstrap.bundle.min.js"></script>
        <script src="assets/js/metisMenu.min.js"></script>
        <script src="assets/js/waves.js"></script>
        <script src="assets/js/jquery.slimscroll.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script src="assets/js/jquery.core.js"></script>
        <script src="assets/js/jquery.app.js"></script>
        <script src="assets/js/loan_approval.js"></script>
        <script>
            function applyFilters() {
                const status = document.getElementById('statusFilter').value;
                const limitElement = document.getElementById('limitFilter');
                const limit = limitElement ? limitElement.value : <?= $perpage ?>;
                const search = document.getElementById('searchFilter').value;
                const baseUrl = window.location.href.split('?')[0];
                window.location.href = `${baseUrl}?status=${status}&limit=${limit}&search=${encodeURIComponent(search)}&page=1`;
            }
            document.getElementById('searchFilter').addEventListener('keypress', function (e) {
                if (e.key === 'Enter') { applyFilters(); }
            });
        </script>
    </body>
    </html>
<?php
    $conDB->close();
}
?>
