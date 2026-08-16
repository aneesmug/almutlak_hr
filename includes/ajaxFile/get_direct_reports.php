<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/session_check.php';

header('Content-Type: application/json');

if (!isset($_SESSION['auth_user'])) {
    echo json_encode(['status' => 401, 'message' => 'Unauthorized']);
    exit;
}

$supervisor_emp_id = trim($_POST['supervisor_emp_id'] ?? '');
$search_term = trim($_POST['search'] ?? '');
$page = isset($_POST['page']) && is_numeric($_POST['page']) ? (int)$_POST['page'] : 1;
if ($page < 1) {
    $page = 1;
}
$per_page = 4;

if ($supervisor_emp_id === '') {
    echo json_encode(['status' => 400, 'message' => 'Missing supervisor_emp_id']);
    exit;
}

$where = "`supervisor_id` = ? AND `status` = '1'";
$params = [$supervisor_emp_id];
$types = "s";
if ($search_term !== '') {
    $where .= " AND (`name` LIKE ? OR `emp_id` LIKE ? OR `iqama` LIKE ? OR `mobile` LIKE ?)";
    $search_param = "%{$search_term}%";
    array_push($params, $search_param, $search_param, $search_param, $search_param);
    $types .= "ssss";
}

$total_items = 0;
$stmt_count = mysqli_prepare($conDB, "SELECT COUNT(*) AS total FROM `employees` WHERE {$where}");
mysqli_stmt_bind_param($stmt_count, $types, ...$params);
mysqli_stmt_execute($stmt_count);
$total_items = (int)(mysqli_stmt_get_result($stmt_count)->fetch_assoc()['total'] ?? 0);
mysqli_stmt_close($stmt_count);

$total_pages = $total_items > 0 ? (int)ceil($total_items / $per_page) : 1;
if ($page > $total_pages) {
    $page = $total_pages;
}
$offset = ($page - 1) * $per_page;

$cards_html = '';
if ($total_items > 0) {
    $data_params = $params;
    $data_types = $types . "ii";
    $data_params[] = $offset;
    $data_params[] = $per_page;

    $stmt = mysqli_prepare($conDB, "SELECT * FROM `employees` WHERE {$where} ORDER BY `name` ASC LIMIT ?, ?");
    mysqli_stmt_bind_param($stmt, $data_types, ...$data_params);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    ob_start();
    while ($rec = mysqli_fetch_assoc($result)) {
        $id = $rec['id'];
        $name = htmlspecialchars($rec['name']);
        $emp_id = htmlspecialchars($rec['emp_id']);
        $iqama = htmlspecialchars($rec['iqama']);
        $emptype = $rec['emptype'];
        $emp_status = $rec['status'];
        $emp_status_fly = $rec['fly'];
        $emp_avatar = getAvatarImagePath($rec['avatar'] ?? '', $rec['sex'] ?? 1);
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
    $cards_html = ob_get_clean();
    mysqli_stmt_close($stmt);
}

echo json_encode([
    'status' => 200,
    'html' => $cards_html,
    'current_page' => $page,
    'total_pages' => $total_pages,
    'total_items' => $total_items,
]);
