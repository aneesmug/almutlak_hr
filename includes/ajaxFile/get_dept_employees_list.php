<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/session_check.php';

header('Content-Type: application/json');

if (!isset($_SESSION['auth_user'])) {
    echo json_encode(['status' => 401, 'message' => 'Unauthorized']);
    exit;
}

if (!isset($_POST['dept']) || !is_numeric($_POST['dept'])) {
    echo json_encode(['status' => 400, 'message' => 'Missing dept']);
    exit;
}
$department_id = (int)$_POST['dept'];

$can_see_all_employees = function_exists('canSeeAllEmployeesByRole')
    ? canSeeAllEmployeesByRole(true)
    : ($is_system_admin || $user_type == 'administrator' || $user_dept == 5 || $isHR || $isDeptHr || $user_dept == 1);

$has_explicit_scope_restrictions = function_exists('hasExplicitEmployeeScopeRestrictions')
    ? hasExplicitEmployeeScopeRestrictions(true)
    : (!empty($allowed_companies_array) || !empty($allowed_departments_array) || !empty($allowed_employees_array));

$accessible_departments = getAccessibleDepartments(true);
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
        echo json_encode(['status' => 403, 'message' => 'Access denied']);
        exit;
    }
}

if (empty($accessible_departments) && !$has_explicit_scope_restrictions && !$can_see_all_employees) {
    if ((int)$user_dept !== (int)$department_id) {
        echo json_encode(['status' => 403, 'message' => 'Access denied']);
        exit;
    }
}

$limit_options = [12, 24, 48, 96];
$search_term = trim($_POST['search'] ?? '');
$status_filter = $_POST['status'] ?? 'all';
$limit_raw = $_POST['limit'] ?? $limit_options[0];
$show_all = $limit_raw === 'all';
$items_per_page = $show_all ? -1 : (in_array((int)$limit_raw, $limit_options) ? (int)$limit_raw : $limit_options[0]);

$current_page = isset($_POST['page']) && is_numeric($_POST['page']) ? (int)$_POST['page'] : 1;
if ($current_page < 1) {
    $current_page = 1;
}

$employee_filter_count = getEmployeeFilterSQL('emp_id', false);
$fallback_dept_clause = (!$can_see_all_employees && !$has_explicit_scope_restrictions && !empty($user_dept))
    ? " AND `dept`='" . mysqli_real_escape_string($conDB, $user_dept) . "'"
    : "";
$unfiltered_sql = "SELECT COUNT(id) as total FROM employees WHERE 1=1" . $employee_filter_count . $fallback_dept_clause;
$unfiltered_result = mysqli_query($conDB, $unfiltered_sql);
$unfiltered_total_items = mysqli_fetch_assoc($unfiltered_result)['total'] ?? 0;

$where_clauses = ["`dept` = ?"];
$params = [$department_id];
$types = "i";

$employee_filter = getEmployeeFilterSQL('emp_id', false);
if (!empty($employee_filter)) {
    $where_clauses[] = substr($employee_filter, 5);
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

$employees = [];
if ($total_items > 0) {
    $sql = "SELECT * FROM employees" . $where_sql . " ORDER BY `created_at` DESC";
    $data_params = $params;
    $data_types = $types;
    if (!$show_all) {
        $offset = ($current_page - 1) * $items_per_page;
        $sql .= " LIMIT ?, ?";
        $data_params[] = $offset;
        $data_params[] = $items_per_page;
        $data_types .= "ii";
    }
    $stmt = $conDB->prepare($sql);
    $stmt->bind_param($data_types, ...$data_params);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $employees[] = $row;
    }
    $stmt->close();
}

ob_start();
if (!empty($employees)) {
    foreach ($employees as $rec) {
        $id = $rec["id"];
        $name = $rec["name"];
        $emp_id = $rec["emp_id"];
        $iqama = $rec["iqama"];
        $emp_status = $rec["status"];
        $emp_status_fly = $rec["fly"];
        $emptype = $rec["emptype"];
        $emp_avatar = getAvatarImagePath($rec["avatar"] ?? '', $rec['sex'] ?? 1);

        $status_class = '';
        if ($emp_status == 1 && $emp_status_fly == 0) {
            $status_class = 'status-active';
        } elseif ($emp_status_fly == 1) {
            $status_class = 'status-fly';
        } else {
            $status_class = 'status-inactive';
        }
        include __DIR__ . '/../employee_card.php';
    }
} else {
    ?>
    <div class="col-12"><div class='alert alert-warning text-center'><?= __('no_employees_found_matching_your_criteria_in_this_department') ?></div></div>
    <?php
}
$cards_html = ob_get_clean();

// Pagination is rendered purely client-side (buttons, no <a href>) so paging can never
// fall back to a real page navigation - see renderStandardPagination() in the page JS.
echo json_encode([
    'status' => 200,
    'cards_html' => $cards_html,
    'total_items' => (int)$total_items,
    'total_pages' => (int)$total_pages,
    'items_per_page' => (int)$items_per_page,
    'show_all' => $show_all,
    'unfiltered_total_items' => (int)$unfiltered_total_items,
    'current_page' => $current_page,
]);
