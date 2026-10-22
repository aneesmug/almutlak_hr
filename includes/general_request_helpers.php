<?php
// Shared lookups for the General Request pages (new_general_request.php, view_general_request.php).
// Stored values stay English (they are saved in general_requests / general_request_items);
// labels go through __() for display.

// Target departments: stored value => label
function gr_target_departments() {
    return [
        'IT'             => __('it_department', 'IT Department'),
        'HR'             => __('hr_department', 'HR Department'),
        'Transportation' => __('transportation_dept', 'Transportation'),
        'Finance'        => __('finance_department', 'Finance'),
        'Maintenance'    => __('maintenance_dept', 'Maintenance'),
        'Admin'          => __('admin_department', 'Admin'),
        'Other'          => __('other', 'Other'),
    ];
}

// Request categories: stored value => label
function gr_categories() {
    return [
        'Equipment'   => __('equipment', 'Equipment'),
        'Service'     => __('service', 'Service'),
        'Supplies'    => __('supplies', 'Supplies'),
        'Maintenance' => __('maintenance', 'Maintenance'),
        'Support'     => __('support', 'Support'),
        'Other'       => __('other', 'Other'),
    ];
}

// Priority: value => [label, sr tone]
function gr_priorities() {
    return [
        'low'    => [__('low_priority', 'Low'), 'tone-sky'],
        'medium' => [__('medium_priority', 'Medium'), 'tone-indigo'],
        'high'   => [__('high_priority', 'High'), 'tone-amber'],
        'urgent' => [__('urgent_priority', 'Urgent'), 'tone-red'],
    ];
}

// Item types per target department: [{v: stored value, l: label}]. Older requests stored
// 'Information Technology' as the department, so it maps to the IT list.
function gr_item_types() {
    $raw = [
        'IT'             => ['Laptop', 'Desktop', 'Monitor', 'Keyboard', 'Mouse', 'Printer', 'Printer Ink/Toner', 'Network Equipment', 'Software License', 'Other'],
        'Transportation' => ['Vehicle', 'Mobile Phone', 'SIM Card', 'Fuel Card', 'GPS Device', 'Other'],
        'HR'             => ['Stationery', 'Office Supplies', 'Furniture', 'ID Card', 'Badge', 'Other'],
        'Finance'        => ['Calculator', 'Receipt Book', 'Document Folder', 'Safe/Locker', 'Other'],
        'Maintenance'    => ['Tools', 'Equipment', 'Safety Gear', 'Cleaning Supplies', 'Other'],
        'Admin'          => ['Furniture', 'Office Equipment', 'Decoration', 'Other'],
        'Other'          => ['Other'],
    ];
    $out = [];
    foreach ($raw as $dept => $types) {
        $out[$dept] = array_map(function ($t) {
            $key = strtolower(preg_replace('/[^a-z0-9]+/i', '_', $t));
            return ['v' => $t, 'l' => __(trim($key, '_'), $t)];
        }, $types);
    }
    $out['Information Technology'] = $out['IT'];
    return $out;
}

// Request status: value => [label, sr tone, mdi icon]
function gr_statuses() {
    return [
        'draft'                => [__('draft_status', 'Draft'), 'tone-slate', 'mdi-pencil'],
        'pending_approval'     => [__('pending_approval', 'Pending Approval'), 'tone-amber', 'mdi-timer-sand'],
        'approved'             => [__('approved', 'Approved'), 'tone-indigo', 'mdi-seal'],
        'waiting_for_delivery' => [__('waiting_for_delivery', 'Waiting for Delivery'), 'tone-sky', 'mdi-truck-delivery'],
        'completed'            => [__('completed', 'Completed'), 'tone-green', 'mdi-check-all'],
        'rejected'             => [__('rejected', 'Rejected'), 'tone-red', 'mdi-close-circle-outline'],
        'cancelled'            => [__('cancelled', 'Cancelled'), 'tone-slate', 'mdi-cancel'],
    ];
}

// Per-item delivery status: value => [label, sr tone, mdi icon]
function gr_delivery_statuses() {
    return [
        'delivered' => [__('delivered', 'Delivered'), 'tone-green', 'mdi-check-circle'],
        'pending'   => [__('pending', 'Pending'), 'tone-amber', 'mdi-clock'],
        'canceled'  => [__('canceled', 'Canceled'), 'tone-red', 'mdi-close-circle'],
    ];
}

// general_requests.comp_no = companies.comp_id (same meaning as employees.comp_no): the company
// the request is raised for. Added on first use (SQL copy: db/migrations/20261022_general_requests_company.sql).
function gr_ensure_company_column($conDB) {
    static $done = false;
    if ($done) return;
    $done = true;
    $res = @mysqli_query($conDB, "SHOW COLUMNS FROM `general_requests` LIKE 'comp_no'");
    if ($res && mysqli_num_rows($res) === 0) {
        if (@mysqli_query($conDB, "ALTER TABLE `general_requests` ADD COLUMN `comp_no` INT NULL DEFAULT NULL AFTER `user_dept`, ADD INDEX `idx_comp_no` (`comp_no`)")) {
            // Older requests: use the requester's company
            @mysqli_query($conDB, "UPDATE `general_requests` gr JOIN `employees` e ON e.`emp_id` = gr.`emp_id` SET gr.`comp_no` = e.`comp_no` WHERE gr.`comp_no` IS NULL");
        }
    }
}

// Companies the current user may raise a request for: comp_id => label (Arabic name in RTL).
// Limited to the user's allowed companies when they have a restriction (session_check.php).
function gr_company_options($conDB) {
    global $allowed_companies_array, $is_rtl;
    $where = '';
    if (!empty($allowed_companies_array)) {
        $where = ' WHERE `comp_id` IN (' . implode(',', array_map('intval', $allowed_companies_array)) . ')';
    }
    $out = [];
    $res = mysqli_query($conDB, "SELECT `comp_id`, `comp_name`, `comp_name_ar` FROM `companies`" . $where . " ORDER BY `comp_name`");
    while ($res && ($r = mysqli_fetch_assoc($res))) {
        $out[(int)$r['comp_id']] = (($is_rtl ?? false) && !empty($r['comp_name_ar'])) ? $r['comp_name_ar'] : $r['comp_name'];
    }
    return $out;
}

// Display name of one company (comp_id), '' when unknown. $english = true for emails/notifications.
function gr_company_name($conDB, $comp_no, $english = false) {
    global $is_rtl;
    if (!$comp_no) return '';
    $res = mysqli_query($conDB, "SELECT `comp_name`, `comp_name_ar` FROM `companies` WHERE `comp_id` = " . (int)$comp_no . " LIMIT 1");
    $r = $res ? mysqli_fetch_assoc($res) : null;
    if (!$r) return '';
    return (!$english && ($is_rtl ?? false) && !empty($r['comp_name_ar'])) ? $r['comp_name_ar'] : $r['comp_name'];
}

// Display label of a stored target department / category: translated label for the known values,
// getDisplayName() for anything else (older or free-text values).
function gr_department_label($value) {
    $map = gr_target_departments();
    if ($value === 'Information Technology') $value = 'IT';
    return $map[$value] ?? getDisplayName((string)$value);
}
function gr_category_label($value) {
    $map = gr_categories();
    return $map[$value] ?? getDisplayName((string)$value);
}

// approval_request_types id for General Requests. Looked up by type_name (stable). Also repairs a
// row whose main_table_name was saved as 'general_request' - handle_approval_action() updates that
// table by name, so approve/reject failed and the chain lookups by main_table_name found nothing.
function gr_request_type_id($conDB) {
    static $id = null;
    if ($id !== null) return $id;
    @mysqli_query($conDB, "UPDATE `approval_request_types` SET `main_table_name` = 'general_requests' WHERE `type_name` = 'general_request' AND `main_table_name` <> 'general_requests'");
    $res = mysqli_query($conDB, "SELECT `id` FROM `approval_request_types` WHERE `type_name` = 'general_request' LIMIT 1");
    $row = $res ? mysqli_fetch_assoc($res) : null;
    $id = $row ? (int)$row['id'] : 0;
    return $id;
}

// Who may open / print one general request (view_general_request.php, gr_print.php):
// administrators, Management (dept 10), the creator, anyone in its approval chain,
// and managers of the requester's department. Uses the session globals from session_check.php.
function gr_can_view_request($conDB, array $request) {
    global $user_type, $user_dept, $empid, $emptypeget;
    if ($user_type == 'administrator' || $user_dept == 10 || $request['emp_id'] == $empid) {
        return true;
    }
    $res = mysqli_query($conDB, "SELECT 1 FROM request_approvers WHERE request_inv_no = '" . mysqli_real_escape_string($conDB, (string)$request['inv_no']) . "' AND approver_id = " . (int)$empid . " LIMIT 1");
    if ($res && mysqli_num_rows($res) > 0) {
        return true;
    }
    return $emptypeget == 'Manager' && $request['user_dept'] == $user_dept;
}
