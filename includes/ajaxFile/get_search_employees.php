<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/session_check.php';

header('Content-Type: application/json');

if (!isset($_SESSION['auth_user'])) {
    echo json_encode(['status' => 401, 'message' => 'Unauthorized']);
    exit;
}

$can_see_all_employees = function_exists('canSeeAllEmployeesByRole')
    ? canSeeAllEmployeesByRole(true)
    : ($is_system_admin || $user_type == 'administrator' || $user_dept == 5 || $isHR || $isDeptHr || $user_dept == 1);

$has_explicit_scope_restrictions = function_exists('hasExplicitEmployeeScopeRestrictions')
    ? hasExplicitEmployeeScopeRestrictions(true)
    : (!empty($allowed_companies_array) || !empty($allowed_departments_array) || !empty($allowed_employees_array));

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

    $employee_filter = getEmployeeFilterSQL('emp_id', false);
    if (!empty($employee_filter)) {
        $construct .= $employee_filter;
    }

    if (!$can_see_all_employees && !$has_explicit_scope_restrictions && !empty($user_dept)) {
        $construct .= " AND `dept`='" . mysqli_real_escape_string($conDB, $user_dept) . "'";
    }
}

if (!empty($construct)) {
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
        if (!empty($main_params)) {
            $stmt_data->bind_param($main_types, ...$main_params);
        }
        $stmt_data->execute();
        $result = $stmt_data->get_result();
        while ($rec = $result->fetch_assoc()) {
            $employees[] = $rec;
        }
        $stmt_data->close();
    }
}
$_SESSION["foundnum"] = $total_items;

$employee_filter_unf = getEmployeeFilterSQL('emp_id', false);
$fallback_dept_clause = (!$can_see_all_employees && !$has_explicit_scope_restrictions && !empty($user_dept))
    ? " AND `dept`='" . mysqli_real_escape_string($conDB, $user_dept) . "'"
    : "";
$unfiltered_sql = "SELECT COUNT(*) as total FROM employees WHERE 1=1" . $employee_filter_unf . $fallback_dept_clause;
$unfiltered_result = mysqli_query($conDB, $unfiltered_sql);
$unfiltered_total_items = mysqli_fetch_assoc($unfiltered_result)['total'] ?? 0;

ob_start();
if (strlen($search_term) <= 1) {
    ?>
    <div class="col-12"><div class='alert alert-danger w-100'><?= __('no_results_short_search', "Sorry! there are no result for found! ( Search is too short )") ?></div></div>
    <?php
} elseif (empty($employees)) {
    ?>
    <div class="col-12">
        <div class='alert alert-danger w-100'>Sorry! there are no result for found! ( <strong><?= htmlspecialchars($search_term) ?></strong> ) </div>
        <br>
        <div class='alert alert-warning'>
            <strong>1.</strong> Try more general words.<br>
            <strong>2.</strong> Try different words with similar meaning.<br>
            <strong>3.</strong> Please check your spelling.
        </div>
    </div>
    <?php
} else {
    ?>
    <div class="col-12"><div class='alert alert-custom bg-custom text-white border-0 w-100'>"<?= htmlspecialchars($search_term) ?>" <strong><?= $total_items ?></strong> <?= __('results_are_found') ?>!</div></div>
    <?php
    foreach ($employees as $rec) {
        $id = $rec["id"];
        $name = htmlspecialchars($rec["name"]);
        $emp_id = htmlspecialchars($rec["emp_id"]);
        $iqama = htmlspecialchars($rec["iqama"]);
        $emptype = $rec["emptype"];
        $emp_status = $rec["status"];
        $emp_status_fly = $rec["fly"];
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
}
$cards_html = ob_get_clean();

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
