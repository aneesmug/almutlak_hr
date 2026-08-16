<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/session_check.php';

header('Content-Type: application/json');

if (!isset($_SESSION['auth_user'])) {
    echo json_encode(['status' => 401, 'message' => 'Unauthorized']);
    exit;
}

$search_term = trim($_POST['search'] ?? '');
$limit_options = [12, 24, 36, 48];
$per_page = 12;
$limit_raw = $_POST['limit'] ?? $per_page;
$show_all = $limit_raw === 'all';
$items_per_page = $show_all ? -1 : (in_array((int)$limit_raw, $limit_options) ? (int)$limit_raw : $per_page);

$current_page = isset($_POST['page']) && is_numeric($_POST['page']) ? (int)$_POST['page'] : 1;
if ($current_page < 1) {
    $current_page = 1;
}

$where_conditions = [];
$params = [];
$types = "";

$company_filter = getCompanyFilterSQL('comp_no', true);
$department_filter = getDepartmentFilterSQL('dept', true);
$employee_filter = getEmployeeFilterSQL('emp_id', true);

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

$unfiltered_sql = "SELECT COUNT(id) as total FROM employees WHERE 1=1" . $company_filter . $department_filter . $employee_filter;
$unfiltered_result = mysqli_query($conDB, $unfiltered_sql);
$unfiltered_total_items = mysqli_fetch_assoc($unfiltered_result)['total'] ?? 0;

ob_start();
if (!empty($employees)) {
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
    <div class="col-12">
        <div class="text-center mt-5">
            <i class="fas fa-users fa-3x text-muted mb-3"></i>
            <h2><?= __('no_employees_found') ?></h2>
            <p class="text-muted"><?= __('no_employees_matching_filters') ?></p>
        </div>
    </div>
    <?php
}
$cards_html = ob_get_clean();

$pagination_params = [];
if (!empty($search_term)) {
    $pagination_params['search'] = $search_term;
}
if ($show_all) {
    $pagination_params['limit'] = 'all';
} elseif ($items_per_page !== $per_page) {
    $pagination_params['limit'] = $items_per_page;
}
$pagination_html = generate_pagination_controls($current_page, $total_pages, $total_items, $items_per_page, $limit_options, $show_all, $pagination_params, $unfiltered_total_items);

echo json_encode([
    'status' => 200,
    'cards_html' => $cards_html,
    'pagination_html' => $pagination_html,
    'total_items' => (int)$total_items,
    'current_page' => $current_page,
]);
