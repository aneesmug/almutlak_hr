<?php
/**
 * Employee Assigned Assets Page
 * Displays all assets assigned to a specific employee
 */

require_once("./includes/session_check.php");
require_once __DIR__ . '/includes/sr_history_helpers.php';
include('./includes/MainClass.php');
include("./includes/Hijri_GregorianConvert.php");
$DateConv = new Hijri_GregorianConvert;
$format = "YYYY-MM-DD";

// Get employee ID securely from session
// ?emp_id=X opens another employee's records (from their profile) - access-checked in profile_target.php.
require_once __DIR__ . '/includes/profile_target.php';
$emp_id = $profile_target_empid;
if (empty($emp_id)) {
    header("Location: ./profile.php");
    exit();
}
// Fetch employee data
$emp_query = "SELECT * FROM employees WHERE emp_id = ?";
$stmt = $conDB->prepare($emp_query);
$stmt->bind_param("s", $emp_id);
$stmt->execute();
$emprow = $stmt->get_result()->fetch_assoc();

if (!$emprow) {
    die("Employee not found.");
}

// Runs one source query; a schema difference (missing table/column on a server) is logged
// and that source is skipped instead of failing the whole page.
$fetch_rows = function ($sql, $source) use ($conDB, $emp_id) {
    try {
        $stmt = $conDB->prepare($sql);
        if (!$stmt) {
            throw new RuntimeException($conDB->error);
        }
        $stmt->bind_param("s", $emp_id);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $rows;
    } catch (Throwable $e) {
        error_log('[employee_assigned_assets] ' . $source . ' query failed: ' . $e->getMessage());
        return [];
    }
};
$column_exists = function ($table, $column) use ($conDB) {
    try {
        $res = $conDB->query("SHOW COLUMNS FROM `{$table}` LIKE '" . $conDB->real_escape_string($column) . "'");
        return $res && $res->num_rows > 0;
    } catch (Throwable $e) {
        return false;
    }
};

// Fetch assigned assets from employee_assets table (laptops, mobiles, SIMs, etc.)
// assets.asset_type is not on every install (the asset inventory migration doesn't create it).
$asset_type_col = $column_exists('assets', 'asset_type') ? 'a.asset_type' : 'NULL';
$employee_assets_query = "SELECT
                            ea.id,
                            ea.asset_id,
                            ea.serial_number,
                            ea.description,
                            ea.assigned_date,
                            ea.return_date,
                            ea.status,
                            a.name as asset_name,
                            {$asset_type_col} as asset_type
                          FROM employee_assets ea
                          LEFT JOIN assets a ON ea.asset_id = a.id
                          WHERE ea.emp_id = ?
                          ORDER BY ea.assigned_date DESC";
$employee_assets_rows = $fetch_rows($employee_assets_query, 'employee_assets');

// Fetch cars assigned through cars_drv table
$cars_drv_query = "SELECT 
                     cd.id,
                     cd.car_id,
                     cd.rcv_date as assigned_date,
                     cd.rtn_date as return_date,
                     cd.status,
                     cd.created_at,
                     c.maker_name as maker_id,
                     c.model as model_id,
                     c.made_year,
                     c.plate_no,
                     c.type,
                     cm.maker as maker_name,
                     cmd.model as model_name
                   FROM cars_drv cd
                   JOIN cars c ON cd.car_id = c.id
                   LEFT JOIN car_maker cm ON c.maker_name = cm.id
                   LEFT JOIN car_model cmd ON c.model = cmd.id
                   WHERE cd.car_user = ?
                   ORDER BY cd.rcv_date DESC";

$cars_drv_rows = $fetch_rows($cars_drv_query, 'cars_drv');

// Normalise both sources (employee_assets + cars_drv) into one list.
$items = [];
foreach ($employee_assets_rows as $asset) {
    $st = (string)($asset['status'] ?? '');
    $tone = 'green';
    if ($st === 'Returned') $tone = 'slate';
    elseif ($st === 'Lost') $tone = 'red';
    elseif ($st === 'Damaged') $tone = 'amber';
    $items[] = [
        'type' => $asset['asset_type'] ?? $asset['asset_name'] ?? 'Asset', 'type_tone' => 'indigo', 'icon' => 'mdi-laptop',
        'name' => $asset['asset_name'] ?? 'N/A', 'details' => $asset['description'] ?? '',
        'serial' => $asset['serial_number'] ?? '', 'assigned' => $asset['assigned_date'] ?? null, 'returned' => $asset['return_date'] ?? null,
        'status' => $st !== '' ? $st : 'Active', 'tone' => $tone,
    ];
}
foreach ($cars_drv_rows as $car) {
    $active = ($car['status'] == 1) && empty($car['return_date']);
    $items[] = [
        'type' => 'Car', 'type_tone' => 'sky', 'icon' => 'mdi-car',
        'name' => trim(($car['maker_name'] ?? 'N/A') . ' ' . ($car['model_name'] ?? '')),
        'details' => 'Type: ' . ($car['type'] ?? 'N/A') . ' · Year: ' . ($car['made_year'] ?? 'N/A'),
        'serial' => $car['plate_no'] ?? '', 'assigned' => $car['assigned_date'] ?? null, 'returned' => $car['return_date'] ?? null,
        'status' => $active ? 'Active' : 'Returned', 'tone' => $active ? 'green' : 'slate',
    ];
}
$in_hand = count(array_filter($items, fn($i) => $i['tone'] === 'green'));
$cars = count(array_filter($items, fn($i) => $i['type'] === 'Car'));

srh_page_open(__('assigned_assets', 'Assigned Assets'), srh_portal_head());
echo srh_header(__('assigned_assets', 'Assigned Assets'), '', srh_profile_url($emprow));
echo srh_emp_hero($emprow);
echo srh_tiles([
    [__('total_assets', 'Total Assets'), count($items), 'indigo', ''],
    [__('in_hand', 'In Hand'), $in_hand, 'green', ''],
    [__('returned_items', 'Returned'), count($items) - $in_hand, '', ''],
    [__('cars', 'Cars'), $cars, '', ''],
]);

$fmt_date = function ($d) use ($DateConv, $format) {
    if (empty($d)) return '-';
    return '<span class="sr-date">' . sr_h(format_safe_date($d, 'd M Y')) . '<small>' . sr_h($DateConv->GregorianToHijri($d, $format)) . '</small></span>';
};

if (!$items) {
    $body = sr_empty_state(__('no_assets_assigned', 'No assets assigned to this employee.'));
} else {
    $body = '<div class="sr-table-wrap"><table class="sr-table srh-dt"><thead><tr>'
        . '<th>#</th>'
        . '<th>' . sr_h(__('name_model')) . '</th>'
        . '<th>' . sr_h(__('details')) . '</th>'
        . '<th>' . sr_h(__('serial_number')) . '</th>'
        . '<th>' . sr_h(__('assigned_date')) . '</th>'
        . '<th>' . sr_h(__('return_date')) . '</th>'
        . '<th>' . sr_h(__('status')) . '</th>'
        . '</tr></thead><tbody>';
    foreach ($items as $i => $it) {
        $body .= '<tr>'
            . '<td>' . ($i + 1) . '</td>'
            . '<td><div class="sr-person"><span class="sr-avatar" style="background: var(--tone-' . $it['type_tone'] . '-bg); color: var(--tone-' . $it['type_tone'] . '-fg);"><i class="mdi ' . $it['icon'] . '"></i></span>'
                . '<div style="min-width: 0;"><span class="sr-cell-title">' . sr_h($it['name']) . '</span><span class="sr-cell-sub">' . sr_h($it['type']) . '</span></div></div></td>'
            . '<td><span class="sr-wrap-text">' . sr_h($it['details'] !== '' ? $it['details'] : '-') . '</span></td>'
            . '<td><span class="sr-mono">' . sr_h($it['serial'] !== '' ? $it['serial'] : '-') . '</span></td>'
            . '<td data-order="' . sr_h($it['assigned']) . '">' . $fmt_date($it['assigned']) . '</td>'
            . '<td data-order="' . sr_h($it['returned']) . '">' . $fmt_date($it['returned']) . '</td>'
            . '<td>' . sr_pill($it['tone'], $it['status']) . '</td>'
            . '</tr>';
    }
    $body .= '</tbody></table></div>';
}
echo srh_card('mdi-laptop', __('assigned_assets', 'Assigned Assets'), $body, $items ? srh_search_box() : '', 'sr-card-body p-0');

srh_page_close('<script src="assets/js/employee_profile.js"></script>' . srh_dt_js('.srh-dt', 4, 'desc', 'mdi-laptop'));
