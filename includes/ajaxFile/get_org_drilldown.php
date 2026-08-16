<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/session_check.php';

header('Content-Type: application/json');

if (!isset($_SESSION['auth_user'])) {
    echo json_encode(['status' => 401, 'message' => 'Unauthorized']);
    exit;
}

$level = $_POST['level'] ?? '';
$company_id = isset($_POST['company']) ? (int)$_POST['company'] : 0;
$city_id = isset($_POST['city']) ? (int)$_POST['city'] : 0;
$location_id = isset($_POST['location']) ? (int)$_POST['location'] : 0;
$dept_id = isset($_POST['dept']) ? (int)$_POST['dept'] : 0;
$subdept_raw = $_POST['subdept'] ?? ''; // '', 'unassigned', or numeric id

if ($company_id <= 0 || !in_array($level, ['cities', 'locations', 'departments', 'subdepts', 'employees'], true)) {
    echo json_encode(['status' => 400, 'message' => 'Invalid request']);
    exit;
}

$can_see_all_employees = function_exists('canSeeAllEmployeesByRole')
    ? canSeeAllEmployeesByRole(true)
    : ($is_system_admin || $user_type == 'administrator' || $user_dept == 5 || $isHR || $isDeptHr || $user_dept == 1);
$has_explicit_scope_restrictions = function_exists('hasExplicitEmployeeScopeRestrictions')
    ? hasExplicitEmployeeScopeRestrictions(true)
    : (!empty($allowed_companies_array) || !empty($allowed_departments_array) || !empty($allowed_employees_array));

$company_filter = getCompanyFilterSQL('emp.comp_no', true);
$employee_filter = getEmployeeFilterSQL('emp.emp_id', true);
$fallback_dept_filter_emp = (!$can_see_all_employees && !$has_explicit_scope_restrictions && !empty($user_dept))
    ? " AND `emp`.`dept`='" . mysqli_real_escape_string($conDB, $user_dept) . "'"
    : "";
$scope_filters = $company_filter . $employee_filter . $fallback_dept_filter_emp;

// --- Breadcrumb (labels are supplied by the client, already known from the tile it
// clicked - just escape and render, no extra lookups needed) ---
$company_name = trim($_POST['company_name'] ?? '');
$city_name = trim($_POST['city_name'] ?? '');
$location_name = trim($_POST['location_name'] ?? '');
$dept_name = trim($_POST['dept_name'] ?? '');
$subdept_name = trim($_POST['subdept_name'] ?? '');

function drilldown_crumb($label, $level, $data_attrs)
{
    $data_html = '';
    foreach ($data_attrs as $key => $value) {
        $data_html .= ' data-' . htmlspecialchars($key, ENT_QUOTES) . '="' . htmlspecialchars((string)$value, ENT_QUOTES) . '"';
    }
    return '<span class="drilldown-crumb" data-level="' . htmlspecialchars($level, ENT_QUOTES) . '"' . $data_html . '>' . htmlspecialchars($label, ENT_QUOTES) . '</span>';
}

// Build the path as an ordered list of segments, then render the LAST one present
// as the non-clickable "active" crumb and every earlier one as a clickable link back
// to the tiles that contain it - whatever level we're actually on.
// City/location can legitimately be the "Unlisted" sentinel (0) once we're past that
// level, so whether a segment belongs in the trail is based on how deep the current
// level is - not on the id being > 0 (0 there means "Unlisted", not "not chosen yet").
$level_depth = ['cities' => 0, 'locations' => 1, 'departments' => 2, 'subdepts' => 3, 'employees' => 4];
$current_depth = $level_depth[$level] ?? 4;

$segments = [];
$segments[] = ['label' => $company_name, 'level' => 'cities', 'data' => ['company' => $company_id, 'company-name' => $company_name]];
if ($current_depth >= 1) {
    $segments[] = ['label' => $city_name, 'level' => 'locations', 'data' => ['company' => $company_id, 'company-name' => $company_name, 'city' => $city_id, 'city-name' => $city_name]];
}
if ($current_depth >= 2) {
    $segments[] = ['label' => $location_name, 'level' => 'departments', 'data' => ['company' => $company_id, 'company-name' => $company_name, 'city' => $city_id, 'city-name' => $city_name, 'location' => $location_id, 'location-name' => $location_name]];
}
if ($current_depth >= 3) {
    $segments[] = ['label' => $dept_name, 'level' => 'subdepts', 'data' => ['company' => $company_id, 'company-name' => $company_name, 'city' => $city_id, 'city-name' => $city_name, 'location' => $location_id, 'location-name' => $location_name, 'dept' => $dept_id, 'dept-name' => $dept_name]];
}
if ($level === 'employees' && $subdept_raw !== '') {
    $current_label = ($subdept_raw === 'unassigned') ? __('unassigned', 'Unassigned') : $subdept_name;
    $segments[] = ['label' => $current_label, 'level' => null, 'data' => []];
}

$breadcrumb_html = '<span class="drilldown-crumb drilldown-crumb-root" data-level="companies"><i class="mdi mdi-home-variant-outline"></i> ' . htmlspecialchars(__('companies'), ENT_QUOTES) . '</span>';
$last_index = count($segments) - 1;
foreach ($segments as $index => $segment) {
    $breadcrumb_html .= ' <i class="mdi mdi-chevron-right"></i> ';
    if ($index === $last_index) {
        $breadcrumb_html .= '<span class="drilldown-crumb drilldown-crumb-current">' . htmlspecialchars($segment['label'], ENT_QUOTES) . '</span>';
    } else {
        $breadcrumb_html .= drilldown_crumb($segment['label'], $segment['level'], $segment['data']);
    }
}

$colorArr = ["primary", "success", "warning", "danger", "info", "dark"];

// Employees can be left with no city/location assigned. Instead of silently dropping
// them from the drilldown, every level that filters by an id that might be unset
// (city, location) falls back to an "IS NULL OR = 0" match when the id is 0/unset,
// so the drill always accounts for every employee.
function unlisted_or_equals($column_expr, $value, &$params, &$types)
{
    $value = (int)$value;
    if ($value > 0) {
        $params[] = $value;
        $types .= "i";
        return "{$column_expr} = ?";
    }
    return "({$column_expr} IS NULL OR {$column_expr} = 0)";
}

if ($level === 'cities') {
    $sql = "SELECT IFNULL(`emp`.`city_id`, 0) AS `city_id`, `sc`.`name_en`, `sc`.`name_ar`, COUNT(*) AS `cnt`
        FROM `employees` `emp`
        LEFT JOIN `saudi_cities` `sc` ON `sc`.`id` = `emp`.`city_id`
        WHERE `emp`.`status` = 1 AND `emp`.`comp_no` = ?" . $scope_filters . "
        GROUP BY IFNULL(`emp`.`city_id`, 0), `sc`.`name_en`, `sc`.`name_ar`
        ORDER BY (IFNULL(`emp`.`city_id`, 0) = 0) ASC, `sc`.`name_en` ASC";
    $stmt = $conDB->prepare($sql);
    $stmt->bind_param("i", $company_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $rows = [];
    $total = 0;
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
        $total += (int)$row['cnt'];
    }
    $stmt->close();

    $tiles_html = '';
    $index = 0;
    foreach ($rows as $row) {
        $city_id_val = (int)$row['city_id'];
        $is_unlisted = $city_id_val <= 0;
        $label = $is_unlisted
            ? __('unlisted', 'Unlisted') . ' (' . __('city', 'City') . ')'
            : (($GLOBALS['is_rtl'] ?? false) ? ($row['name_ar'] ?: $row['name_en']) : ($row['name_en'] ?: $row['name_ar']));
        $percentage = $total > 0 ? round(($row['cnt'] / $total) * 100, 1) : 0;
        $tiles_html .= generate_drilldown_tile(
            $row['cnt'],
            $label ?: ('City #' . $city_id_val),
            $is_unlisted ? 'mdi mdi-map-marker-question-outline' : 'mdi mdi-city-variant-outline',
            $is_unlisted ? 'dark' : $colorArr[$index % count($colorArr)],
            $percentage,
            ['next-level' => 'locations', 'company' => $company_id, 'company-name' => $company_name, 'city' => $city_id_val, 'city-name' => $label],
            $is_unlisted ? 'drilldown-tile-unassigned' : ''
        );
        if (!$is_unlisted) {
            $index++;
        }
    }
    if ($tiles_html === '') {
        $tiles_html = '<div class="col-12"><div class="alert alert-warning mb-0">' . __('no_data_available_in_table') . '</div></div>';
    }
    echo json_encode(['status' => 200, 'level' => 'cities', 'breadcrumb_html' => $breadcrumb_html, 'tiles_html' => $tiles_html]);
    exit;
}

if ($level === 'locations') {
    $loc_params = [$company_id];
    $loc_types = "i";
    $city_condition = unlisted_or_equals('`emp`.`city_id`', $city_id, $loc_params, $loc_types);

    $sql = "SELECT IFNULL(`emp`.`location_id`, 0) AS `location_id`, `l`.`name_en`, `l`.`name_ar`, COUNT(*) AS `cnt`
        FROM `employees` `emp`
        LEFT JOIN `locations` `l` ON `l`.`id` = `emp`.`location_id`
        WHERE `emp`.`status` = 1 AND `emp`.`comp_no` = ? AND {$city_condition}" . $scope_filters . "
        GROUP BY IFNULL(`emp`.`location_id`, 0), `l`.`name_en`, `l`.`name_ar`
        ORDER BY (IFNULL(`emp`.`location_id`, 0) = 0) ASC, `l`.`name_en` ASC";
    $stmt = $conDB->prepare($sql);
    $stmt->bind_param($loc_types, ...$loc_params);
    $stmt->execute();
    $result = $stmt->get_result();
    $rows = [];
    $total = 0;
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
        $total += (int)$row['cnt'];
    }
    $stmt->close();

    $tiles_html = '';
    $index = 0;
    foreach ($rows as $row) {
        $location_id_val = (int)$row['location_id'];
        $is_unlisted = $location_id_val <= 0;
        $label = $is_unlisted
            ? __('unlisted', 'Unlisted') . ' (' . __('location', 'Location') . ')'
            : (($GLOBALS['is_rtl'] ?? false) ? ($row['name_ar'] ?: $row['name_en']) : ($row['name_en'] ?: $row['name_ar']));
        $percentage = $total > 0 ? round(($row['cnt'] / $total) * 100, 1) : 0;
        $tiles_html .= generate_drilldown_tile(
            $row['cnt'],
            $label ?: ('Location #' . $location_id_val),
            $is_unlisted ? 'mdi mdi-map-marker-question-outline' : 'mdi mdi-map-marker-outline',
            $is_unlisted ? 'dark' : $colorArr[$index % count($colorArr)],
            $percentage,
            ['next-level' => 'departments', 'company' => $company_id, 'company-name' => $company_name, 'city' => $city_id, 'city-name' => $city_name, 'location' => $location_id_val, 'location-name' => $label],
            $is_unlisted ? 'drilldown-tile-unassigned' : ''
        );
        if (!$is_unlisted) {
            $index++;
        }
    }
    if ($tiles_html === '') {
        $tiles_html = '<div class="col-12"><div class="alert alert-warning mb-0">' . __('no_data_available_in_table') . '</div></div>';
    }
    echo json_encode(['status' => 200, 'level' => 'locations', 'breadcrumb_html' => $breadcrumb_html, 'tiles_html' => $tiles_html]);
    exit;
}

if ($level === 'departments') {
    $dept_list_params = [$company_id];
    $dept_list_types = "i";
    $city_condition = unlisted_or_equals('`emp`.`city_id`', $city_id, $dept_list_params, $dept_list_types);
    $location_condition = unlisted_or_equals('`emp`.`location_id`', $location_id, $dept_list_params, $dept_list_types);

    $sql = "SELECT `emp`.`dept`, `d`.`dep_nme`, `d`.`dep_nme_ar`, COUNT(*) AS `cnt`
        FROM `employees` `emp`
        LEFT JOIN `department` `d` ON `d`.`id` = `emp`.`dept`
        WHERE `emp`.`status` = 1 AND `emp`.`comp_no` = ? AND {$city_condition} AND {$location_condition}" . $scope_filters . "
        GROUP BY `emp`.`dept`, `d`.`dep_nme`, `d`.`dep_nme_ar`
        ORDER BY `d`.`dep_nme` ASC";
    $stmt = $conDB->prepare($sql);
    $stmt->bind_param($dept_list_types, ...$dept_list_params);
    $stmt->execute();
    $result = $stmt->get_result();
    $rows = [];
    $total = 0;
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
        $total += (int)$row['cnt'];
    }
    $stmt->close();

    // Batch-check which of these departments have sub-departments configured.
    $dept_ids = array_values(array_filter(array_map(static function ($r) {
        return (int)$r['dept'];
    }, $rows)));
    $depts_with_subdepts = [];
    if (!empty($dept_ids)) {
        $placeholders = implode(',', array_fill(0, count($dept_ids), '?'));
        $sub_stmt = $conDB->prepare("SELECT DISTINCT `department_id` FROM `sub_departments` WHERE `department_id` IN ($placeholders)");
        $sub_types = str_repeat('i', count($dept_ids));
        $sub_stmt->bind_param($sub_types, ...$dept_ids);
        $sub_stmt->execute();
        $sub_result = $sub_stmt->get_result();
        while ($sub_row = $sub_result->fetch_assoc()) {
            $depts_with_subdepts[(int)$sub_row['department_id']] = true;
        }
        $sub_stmt->close();
    }

    $tiles_html = '';
    $index = 0;
    foreach ($rows as $row) {
        if ((int)$row['dept'] <= 0) {
            continue;
        }
        $label = ($GLOBALS['is_rtl'] ?? false) ? ($row['dep_nme_ar'] ?: $row['dep_nme']) : ($row['dep_nme'] ?: $row['dep_nme_ar']);
        $percentage = $total > 0 ? round(($row['cnt'] / $total) * 100, 1) : 0;
        $has_sub = isset($depts_with_subdepts[(int)$row['dept']]);
        $tiles_html .= generate_drilldown_tile(
            $row['cnt'],
            $label ?: ('Department #' . $row['dept']),
            'fa fa-building',
            $colorArr[$index % count($colorArr)],
            $percentage,
            [
                'next-level' => $has_sub ? 'subdepts' : 'employees',
                'company' => $company_id, 'company-name' => $company_name,
                'city' => $city_id, 'city-name' => $city_name,
                'location' => $location_id, 'location-name' => $location_name,
                'dept' => $row['dept'], 'dept-name' => $label,
            ]
        );
        $index++;
    }
    if ($tiles_html === '') {
        $tiles_html = '<div class="col-12"><div class="alert alert-warning mb-0">' . __('no_data_available_in_table') . '</div></div>';
    }
    echo json_encode(['status' => 200, 'level' => 'departments', 'breadcrumb_html' => $breadcrumb_html, 'tiles_html' => $tiles_html]);
    exit;
}

if ($level === 'subdepts') {
    $sub_list_params = [$company_id];
    $sub_list_types = "i";
    $city_condition = unlisted_or_equals('`emp`.`city_id`', $city_id, $sub_list_params, $sub_list_types);
    $location_condition = unlisted_or_equals('`emp`.`location_id`', $location_id, $sub_list_params, $sub_list_types);
    $sub_list_params[] = $dept_id;
    $sub_list_types .= "i";

    $sql = "SELECT `emp`.`sub_dept_id`, `sd`.`name_en`, `sd`.`name_ar`, COUNT(*) AS `cnt`
        FROM `employees` `emp`
        LEFT JOIN `sub_departments` `sd` ON `sd`.`id` = `emp`.`sub_dept_id`
        WHERE `emp`.`status` = 1 AND `emp`.`comp_no` = ? AND {$city_condition} AND {$location_condition} AND `emp`.`dept` = ?
            AND `emp`.`sub_dept_id` IS NOT NULL AND `emp`.`sub_dept_id` != 0" . $scope_filters . "
        GROUP BY `emp`.`sub_dept_id`, `sd`.`name_en`, `sd`.`name_ar`
        ORDER BY `sd`.`name_en` ASC";
    $stmt = $conDB->prepare($sql);
    $stmt->bind_param($sub_list_types, ...$sub_list_params);
    $stmt->execute();
    $result = $stmt->get_result();
    $rows = [];
    $total = 0;
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
        $total += (int)$row['cnt'];
    }
    $stmt->close();

    $unassigned_params = [$company_id];
    $unassigned_types = "i";
    $city_condition2 = unlisted_or_equals('`emp`.`city_id`', $city_id, $unassigned_params, $unassigned_types);
    $location_condition2 = unlisted_or_equals('`emp`.`location_id`', $location_id, $unassigned_params, $unassigned_types);
    $unassigned_params[] = $dept_id;
    $unassigned_types .= "i";

    $unassigned_sql = "SELECT COUNT(*) AS `cnt` FROM `employees` `emp`
        WHERE `emp`.`status` = 1 AND `emp`.`comp_no` = ? AND {$city_condition2} AND {$location_condition2} AND `emp`.`dept` = ?
            AND (`emp`.`sub_dept_id` IS NULL OR `emp`.`sub_dept_id` = 0)" . $scope_filters;
    $unassigned_stmt = $conDB->prepare($unassigned_sql);
    $unassigned_stmt->bind_param($unassigned_types, ...$unassigned_params);
    $unassigned_stmt->execute();
    $unassigned_count = (int)($unassigned_stmt->get_result()->fetch_assoc()['cnt'] ?? 0);
    $unassigned_stmt->close();
    $total += $unassigned_count;

    $tiles_html = '';
    $index = 0;
    foreach ($rows as $row) {
        if ((int)$row['sub_dept_id'] <= 0) {
            continue;
        }
        $label = ($GLOBALS['is_rtl'] ?? false) ? ($row['name_ar'] ?: $row['name_en']) : ($row['name_en'] ?: $row['name_ar']);
        $percentage = $total > 0 ? round(($row['cnt'] / $total) * 100, 1) : 0;
        $tiles_html .= generate_drilldown_tile(
            $row['cnt'],
            $label ?: ('Sub-department #' . $row['sub_dept_id']),
            'mdi mdi-sitemap-outline',
            $colorArr[$index % count($colorArr)],
            $percentage,
            [
                'next-level' => 'employees',
                'company' => $company_id, 'company-name' => $company_name,
                'city' => $city_id, 'city-name' => $city_name,
                'location' => $location_id, 'location-name' => $location_name,
                'dept' => $dept_id, 'dept-name' => $dept_name,
                'subdept' => $row['sub_dept_id'], 'subdept-name' => $label,
            ]
        );
        $index++;
    }
    if ($unassigned_count > 0) {
        $percentage = $total > 0 ? round(($unassigned_count / $total) * 100, 1) : 0;
        $tiles_html .= generate_drilldown_tile(
            $unassigned_count,
            __('unassigned', 'Unassigned'),
            'mdi mdi-account-question-outline',
            'dark',
            $percentage,
            [
                'next-level' => 'employees',
                'company' => $company_id, 'company-name' => $company_name,
                'city' => $city_id, 'city-name' => $city_name,
                'location' => $location_id, 'location-name' => $location_name,
                'dept' => $dept_id, 'dept-name' => $dept_name,
                'subdept' => 'unassigned', 'subdept-name' => '',
            ],
            'drilldown-tile-unassigned'
        );
    }
    if ($tiles_html === '') {
        $tiles_html = '<div class="col-12"><div class="alert alert-warning mb-0">' . __('no_data_available_in_table') . '</div></div>';
    }
    echo json_encode(['status' => 200, 'level' => 'subdepts', 'breadcrumb_html' => $breadcrumb_html, 'tiles_html' => $tiles_html]);
    exit;
}

if ($level === 'employees') {
    if ($dept_id <= 0) {
        echo json_encode(['status' => 400, 'message' => 'Missing dept']);
        exit;
    }

    $limit_options = [12, 24, 48, 96];
    $search_term = trim($_POST['search'] ?? '');
    $status_filter = $_POST['status'] ?? 'active';
    if (!in_array($status_filter, ['all', 'active', 'inactive', 'on_vacation'], true)) {
        $status_filter = 'active';
    }
    $limit_raw = $_POST['limit'] ?? $limit_options[0];
    $show_all = $limit_raw === 'all';
    $items_per_page = $show_all ? -1 : (in_array((int)$limit_raw, $limit_options) ? (int)$limit_raw : $limit_options[0]);
    $current_page = isset($_POST['page']) && is_numeric($_POST['page']) ? (int)$_POST['page'] : 1;
    if ($current_page < 1) {
        $current_page = 1;
    }

    $where_clauses = ["`comp_no` = ?"];
    $params = [$company_id];
    $types = "i";
    $where_clauses[] = unlisted_or_equals('`city_id`', $city_id, $params, $types);
    $where_clauses[] = unlisted_or_equals('`location_id`', $location_id, $params, $types);
    $where_clauses[] = "`dept` = ?";
    $params[] = $dept_id;
    $types .= "i";

    if ($subdept_raw === 'unassigned') {
        $where_clauses[] = "(`sub_dept_id` IS NULL OR `sub_dept_id` = 0)";
    } elseif ($subdept_raw !== '' && is_numeric($subdept_raw)) {
        $where_clauses[] = "`sub_dept_id` = ?";
        $params[] = (int)$subdept_raw;
        $types .= "i";
    }

    $employee_filter_plain = getEmployeeFilterSQL('emp_id', false);
    if (!empty($employee_filter_plain)) {
        $where_clauses[] = substr($employee_filter_plain, 5);
    }

    if (!empty($search_term)) {
        $where_clauses[] = "(`name` LIKE ? OR `iqama` LIKE ? OR `emp_id` LIKE ? OR `mobile` LIKE ?)";
        $like_term = "%{$search_term}%";
        array_push($params, $like_term, $like_term, $like_term, $like_term);
        $types .= "ssss";
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

    echo json_encode([
        'status' => 200,
        'level' => 'employees',
        'breadcrumb_html' => $breadcrumb_html,
        'cards_html' => $cards_html,
        'total_items' => (int)$total_items,
        'total_pages' => (int)$total_pages,
        'current_page' => (int)$current_page,
    ]);
    exit;
}
