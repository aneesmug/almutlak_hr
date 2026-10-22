<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/session_check.php';
require_once __DIR__ . '/includes/helper_functions.php';
require_once __DIR__ . '/includes/general_request_helpers.php';

// View / act on one General Request. Layout mirrors open_request.php (sr-* design system,
// assets/css/smart_request.css): hero + progress steps, items, attachments, and a side column
// with the action card for the current user, the approval chain and the status history.

// Restrict access to non-employee users only
if ($user_type == 'employee') {
    header('Location: dashboard.php');
    exit;
}

$query = mysqli_query($conDB, "SELECT * FROM `admin_login` WHERE `id_iqama`='".$username."'");
if(mysqli_num_rows($query) == 1){
    include("./includes/avatar_select.php");
}

// Fetch potential approvers (not 'employee' user_type) AND INCLUDE user_type AND dept ID
$potential_approvers = [];
$company_filter = getCompanyFilterSQL('e.comp_no', true);
$employee_filter = getEmployeeFilterSQL('e.emp_id', true);
$approver_query = mysqli_query($conDB, "SELECT e.`emp_id`, e.`name`, al.`user_type`, e.`dept`
                                        FROM `employees` e
                                        JOIN `admin_login` al ON e.`emp_id` = al.`emp_id`
                                        WHERE al.`user_type` != 'employee' AND e.`status` = 1" . $company_filter . $employee_filter . "
                                        ORDER BY e.`name`");
if ($approver_query) {
    while ($row_approver = mysqli_fetch_assoc($approver_query)) {
        $potential_approvers[] = $row_approver;
    }
}

// Department ID to Name Mapping
$department_map = [
    1 => 'Administration',
    2 => 'Finance',
    5 => 'HR',
    12 => 'Public Relation',
    14 => 'Sales',
    7 => 'Inspection',
    13 => 'Purchase',
    6 => 'IT',
    11 => 'Production',
    15 => 'Warehouse',
    9 => 'Maintenance',
    10 => 'Management',
    3 => 'General',
    4 => 'Housing',
    8 => 'Logistics',
    16 => 'Training',
];

// Initialize message variable
$msg = '';

// Check if request ID is provided
if (!isset($_GET['id'])) {
    header('Location: all_general_requests.php?error=request_not_found');
    exit;
}

$inv_no = escape_string($_GET['id']);

// Fetch request details
$request_query = mysqli_query($conDB, "SELECT gr.*, d.dep_nme 
    FROM general_requests gr
    LEFT JOIN department d ON d.id = gr.user_dept
    WHERE gr.inv_no = '$inv_no'
    LIMIT 1");

if (!$request_query || mysqli_num_rows($request_query) == 0) {
    header('Location: all_general_requests.php?error=request_not_found');
    exit;
}

$request = mysqli_fetch_assoc($request_query);

// Access control: Check if user can view this request
$can_view_request = false; // Default: deny access

// 1. Administrators can view all requests
if ($user_type == 'administrator') {
    $can_view_request = true;
}
// 2. Management (dept 10) can view all requests
elseif ($user_dept == 10) {
    $can_view_request = true;
}
// 3. Check if user created this request
elseif ($request['emp_id'] == $empid) {
    $can_view_request = true;
}
// 4. Check if user is in the approval chain for this specific request
else {
    $approver_check_query = mysqli_query($conDB, "SELECT ra.id, ra.approval_level 
        FROM request_approvers ra
        WHERE ra.request_inv_no = '".escape_string($inv_no)."' 
        AND ra.approver_id = ".(int)$empid);
    
    if ($approver_check_query && mysqli_num_rows($approver_check_query) > 0) {
        // User is in the approval chain for this request
        $can_view_request = true;
    }
    // 5. Department managers can view requests from their department
    elseif ($emptypeget == 'Manager' && $request['user_dept'] == $user_dept) {
        $can_view_request = true;
    }
}

// Deny access if user doesn't have permission
if (!$can_view_request) {
    header('Location: all_general_requests.php?error=request_not_found');
    exit;
}

// Fetch request items with delivery status
$items_query = mysqli_query($conDB, "SELECT DISTINCT `item_name`, `item_type`, `quantity`, `specifications`, `id`, `delivery_status` FROM general_request_items WHERE request_inv_no = '$inv_no' ORDER BY id");
$items = [];
$seen_items = [];
while ($item = mysqli_fetch_assoc($items_query)) {
    // Prevent duplicate items from being added to the display array
    $item_key = md5($item['item_name'] . '|' . $item['quantity'] . '|' . $item['specifications']);
    if (!in_array($item_key, $seen_items)) {
        $items[] = $item;
        $seen_items[] = $item_key;
    }
}

// Fetch delivery info if exists
$delivery_info = null;
// Apply access control for delivery records
$company_filter_delivery = getCompanyFilterSQL('e.comp_no', true);
$department_filter_delivery = getDepartmentFilterSQL('e.dept', true);
$employee_filter_delivery = getEmployeeFilterSQL('e.emp_id', true);
// (filters already start with " AND ")
$delivery_query = mysqli_query($conDB, "SELECT d.*, e.name as received_employee_name FROM general_request_deliveries d LEFT JOIN employees e ON e.emp_id = d.received_by WHERE d.request_inv_no = '$inv_no'" . $company_filter_delivery . $department_filter_delivery . $employee_filter_delivery . " LIMIT 1");
if ($delivery_query && mysqli_num_rows($delivery_query) > 0) {
    $delivery_info = mysqli_fetch_assoc($delivery_query);
}

// Fetch attachments
$attachments_query = mysqli_query($conDB, "SELECT * FROM general_request_attachments WHERE request_inv_no = '$inv_no' ORDER BY id");
$attachments = [];
while ($attachment = mysqli_fetch_assoc($attachments_query)) {
    $attachments[] = $attachment;
}

// Fetch approval chain
$approval_chain = [];
$type_query = mysqli_query($conDB, "SELECT id FROM approval_request_types WHERE main_table_name = 'general_requests' LIMIT 1");
if ($type_query && mysqli_num_rows($type_query) > 0) {
    $type_row = mysqli_fetch_assoc($type_query);
    $request_type_id = $type_row['id'];
    
    $approvers_query = mysqli_query($conDB, "SELECT ra.*, e.name as approver_name 
        FROM request_approvers ra
        LEFT JOIN employees e ON e.emp_id = ra.approver_id
        WHERE ra.request_inv_no = '$inv_no' AND ra.request_type_id = $request_type_id
        ORDER BY ra.approval_level ASC");
    
    while ($approver = mysqli_fetch_assoc($approvers_query)) {
        $approval_chain[] = $approver;
    }
}

// Check permissions
$is_creator = ($request['emp_id'] == $empid);
$is_current_approver = false;
$is_any_approver = false;
$current_approver_level = null;

foreach ($approval_chain as $approver) {
    if (in_array((int)$approver['approver_id'], effectiveApproverIds(), true) && $approver['status'] == 'pending') {
        $is_current_approver = true;
        $current_approver_level = $approver['approval_level'];
        break;
    }
}

// Check if user is any approver in the chain
foreach ($approval_chain as $approver) {
    if (in_array((int)$approver['approver_id'], effectiveApproverIds(), true)) {
        $is_any_approver = true;
        break;
    }
}

// Determine if current user can modify items
$can_modify_items = false;
if ($request['current_status'] == 'draft' && $is_creator) {
    $can_modify_items = true;
} elseif ($request['current_status'] == 'pending_approval' && $is_any_approver) {
    $can_modify_items = true;
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    require_once __DIR__ . '/includes/vendor/autoload.php';
    
    // Handle item deletion
    if (isset($_POST['action']) && $_POST['action'] == 'delete_item') {
        if (!$can_modify_items) {
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(403);
            echo json_encode(['status' => 'error', 'message' => 'Unauthorized to modify items']);
            exit;
        }
        $item_id = intval($_POST['item_id'] ?? 0);
        if ($item_id > 0) {
            $delete_result = mysqli_query($conDB, "DELETE FROM general_request_items WHERE id = $item_id AND request_inv_no = '".escape_string($inv_no)."'");
            if ($delete_result) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['status' => 'success', 'message' => 'Item removed successfully']);
                exit;
            }
        }
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['status' => 'error', 'message' => 'Failed to delete item']);
        exit;
    }
    
    // Handle item quantity update
    if (isset($_POST['action']) && $_POST['action'] == 'update_item') {
        if (!$can_modify_items) {
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(403);
            echo json_encode(['status' => 'error', 'message' => 'Unauthorized to modify items']);
            exit;
        }
        $item_id = intval($_POST['item_id'] ?? 0);
        $new_quantity = intval($_POST['quantity'] ?? 0);
        if ($item_id > 0 && $new_quantity > 0) {
            $update_result = mysqli_query($conDB, "UPDATE general_request_items SET quantity = $new_quantity WHERE id = $item_id AND request_inv_no = '".escape_string($inv_no)."'");
            if ($update_result) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['status' => 'success', 'message' => 'Quantity updated successfully']);
                exit;
            }
        }
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['status' => 'error', 'message' => 'Failed to update quantity']);
        exit;
    }
    
    // Draft submission
    if (isset($_POST['action']) && $request['current_status'] == 'draft' && $is_creator) {
        $is_ajax = (isset($_POST['ajax']) && $_POST['ajax']) || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');
        $approver_ids = isset($_POST['approvers']) ? array_filter($_POST['approvers']) : [];

        if (empty($approver_ids)) {
            if ($is_ajax) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['status' => 'error', 'message' => __('select_at_least_one_approver', 'Please select at least one approver.')]);
                exit;
            } else {
                $msg = __('select_at_least_one_approver', 'Please select at least one approver.');
            }
        } else {
            if (save_approval_chain($conDB, $inv_no, 'general_request', $approver_ids)) {
                mysqli_query($conDB, "UPDATE general_requests SET current_status = 'pending_approval', current_approval_level = 1 WHERE inv_no = '".escape_string($inv_no)."'");
                mysqli_query($conDB, "INSERT INTO smt_request_status (emp_id, inv_no, emp_name, status, note) VALUES ('$empid', '".escape_string($inv_no)."', '".escape_string($userwel)."', 'pending_approval', 'Submitted for approval')");
                
                // Notify first approver
                $first_approver_id = $approver_ids[0];
                $first_approver_details = getEmployeeDetails($conDB, $first_approver_id);
                if ($first_approver_details && !empty($first_approver_details['email'])) {
                    create_browser_notification($conDB, $first_approver_id, "New General Request for Approval", "Request $inv_no is waiting for your action.", "view_general_request.php?id=".urlencode($inv_no));
                    
                    // Send approval email with request details
                    $request_url = get_base_url() . '/view_general_request.php?id=' . urlencode($inv_no);
                    
                    // Build template data with safe defaults
                    $template_data = [
                        'APPROVER_NAME' => $first_approver_details['name'] ?? 'Approver',
                        'REQUEST_ID' => $inv_no,
                        'REQUEST_TITLE' => trim($request['request_title'] ?? '') ?: 'N/A',
                        'REQUESTER_NAME' => trim($request['emp_name'] ?? '') ?: 'N/A',
                        'DEPARTMENT' => trim($request['dep_nme'] ?? '') ?: (isset($department_map[$request['user_dept'] ?? 0]) ? $department_map[$request['user_dept']] : 'N/A'),
                        'PRIORITY' => trim($request['priority'] ?? '') ? ucfirst($request['priority']) : 'N/A',
                        'CATEGORY' => trim($request['request_category'] ?? '') ?: 'N/A',
                        'DESCRIPTION' => trim($request['description'] ?? '') ?: 'No description provided',
                        'EMAIL_MESSAGE' => 'A new General Request requires your approval.',
                        'REQUEST_URL' => $request_url
                    ];
                    
                    send_approval_email($conDB, $first_approver_details['email'], $first_approver_details['name'], 'New General Request for Approval', 'general_request', $template_data);
                }
                
                if ($is_ajax) {
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode(['status' => 'success', 'message' => __('request_submitted_successfully', 'Request submitted successfully.')]);
                    exit;
                } else {
                    header('Location: view_general_request.php?id=' . urlencode($inv_no));
                    exit;
                }
            } else {
                if ($is_ajax) {
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode(['status' => 'error', 'message' => __('failed_to_save_approval_chain', 'Failed to save approval chain.')]);
                    exit;
                } else {
                    $msg = __('failed_to_save_approval_chain', 'Failed to save approval chain.');
                }
            }
        }
    }
    
    // Approval/Rejection action
    elseif (isset($_POST['action']) && isset($_POST['status']) && $is_current_approver) {
        $status_action = $_POST['status'];
        $note = mysqli_real_escape_string($conDB, $_POST['note'] ?? '');
        
        $result = handle_approval_action($conDB, $inv_no, 'general_request', $empid, $status_action, $note);
        
        if ($result['status'] == 'success') {
            $action_text = ($status_action == 'approve') ? __('approved', 'Approved') : __('rejected', 'Rejected');
            // Get approver name
            $approver_details = getEmployeeDetails($conDB, $empid);
            $approver_name = $approver_details ? $approver_details['name'] : $userwel;
            
            // Get current approval level
            $current_level = 0;
            foreach ($approval_chain as $approver) {
                if (in_array((int)$approver['approver_id'], effectiveApproverIds(), true) && $approver['status'] == 'pending') {
                    $current_level = $approver['approval_level'];
                    break;
                }
            }
            
            // If no note provided, use the action status (Approved/Rejected)
            $comment_text = !empty($note) ? $note : $action_text;
            save_approval_comment_db($conDB, $inv_no, 'general_request', $status_action, 
                                    $empid, $approver_name, $comment_text, $current_level, null);
            
            if (isset($result['next_approver']) && $result['next_approver'] != null) {
                // Email already sent by handle_approval_action
                // Just create browser notification
                $next_approver_id = $result['next_approver_id'];
                create_browser_notification($conDB, $next_approver_id, "General Request for Approval", "Request $inv_no has been approved and is now waiting for your action.", "view_general_request.php?id=".urlencode($inv_no));
            } elseif ($status_action == 'reject') {
                // Email already sent by handle_approval_action
                // Just create browser notification
                if ($request['emp_id'] && $request['emp_id'] != $empid) {
                    create_browser_notification($conDB, $request['emp_id'], "Request Rejected", "Request $inv_no was rejected.", "view_general_request.php?id=".urlencode($inv_no));
                }
            }
            
            // Post/Redirect/Get: reload with a notice instead of re-rendering the POST
            header('Location: view_general_request.php?id=' . urlencode($inv_no) . '&done=' . ($status_action == 'approve' ? 'approve' : 'reject'));
            exit;
        } else {
            $msg = $result['message'] ?? __('request_failed', 'Request failed');
        }
    }
}

// --- Display data ---
$gr_status_map = gr_statuses();
$current_status = $request['current_status'];
[$status_label, $status_tone] = $gr_status_map[$current_status] ?? [ucwords(str_replace('_', ' ', (string)$current_status)), 'tone-slate'];
if ($current_status === 'pending_approval' && !empty($request['current_approval_level'])) {
    $status_label .= ' · ' . __('level', 'Level') . ' ' . (int)$request['current_approval_level'];
}
$gr_priority_map = gr_priorities();
[$priority_label, $priority_tone] = $gr_priority_map[$request['priority']] ?? [ucfirst((string)$request['priority']), 'tone-slate'];
$delivery_map = gr_delivery_statuses();

// Who holds the request now, and why it was rejected
$waiting_for_name = '';
$rejection_note = '';
$rejected_by_name = '';
foreach ($approval_chain as $step) {
    if ($step['status'] == 'pending' && $waiting_for_name === '') {
        $waiting_for_name = getDisplayName($step['approver_name']);
    }
    if ($step['status'] == 'rejected') {
        $rejected_by_name = getDisplayName($step['approver_name']);
        $rejection_note = (string)$step['note'];
    }
}

// Progress stepper: Created > Approval > Approved > Delivery > Completed.
$steps_done = -1; $steps_current = -1; $steps_failed = -1;
switch ($current_status) {
    case 'draft':                $steps_current = 0; break;
    case 'pending_approval':     $steps_done = 0; $steps_current = 1; break;
    case 'approved':
    case 'waiting_for_delivery': $steps_done = 2; $steps_current = 3; break;
    case 'completed':            $steps_done = 4; break;
    case 'rejected':             $steps_done = 0; $steps_failed = 1; break;
}
$progress_steps = [
    ['icon' => 'mdi mdi-file-document', 'label' => __('created', 'Created')],
    ['icon' => 'mdi mdi-account-check', 'label' => $steps_failed === 1 ? __('rejected') : __('approval', 'Approval')],
    ['icon' => 'mdi mdi-seal', 'label' => __('approved')],
    ['icon' => 'mdi mdi-truck-delivery', 'label' => __('delivery', 'Delivery')],
    ['icon' => 'mdi mdi-check-all', 'label' => __('completed', 'Completed')],
];

// Status history (shared smt_request_status table, keyed by inv_no)
$history = [];
$history_query = mysqli_query($conDB, "SELECT status, note, emp_name, created_at FROM smt_request_status WHERE inv_no = '" . escape_string($inv_no) . "' ORDER BY created_at DESC");
while ($history_query && ($h_row = mysqli_fetch_assoc($history_query))) {
    $history[] = $h_row;
}

$is_draft_owner = ($current_status == 'draft' && $is_creator);
$can_deliver = in_array($current_status, ['approved', 'waiting_for_delivery'], true);
$show_delivery_col = in_array($current_status, ['completed', 'waiting_for_delivery'], true);

// Initials for avatar circles
$sr_initials = function ($name) {
    $parts = preg_split('/\s+/', trim((string)$name), -1, PREG_SPLIT_NO_EMPTY);
    if (!$parts) return '?';
    return mb_strtoupper(mb_substr($parts[0], 0, 1) . (isset($parts[1]) ? mb_substr($parts[1], 0, 1) : ''));
};

$h = function ($s) { return htmlspecialchars((string)$s, ENT_QUOTES); };
$total_qty = array_sum(array_map(function ($it) { return (int)$it['quantity']; }, $items));
$chain_approved = count(array_filter($approval_chain, function ($s) { return $s['status'] == 'approved'; }));
?>
<!doctype html>
<html lang="<?= $current_lang ?? 'en' ?>" <?= ($is_rtl ?? false) ? 'dir="rtl"' : '' ?>>
<head>
    <meta charset="utf-8" />
    <title><?= $site_title ?> - <?= $h(getDisplayName($request['request_title'])) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta content="Anees Afzal" name="author" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <link rel="shortcut icon" href="<?=get_setting($conDB, 'favicon')?>">

    <link href="./plugins/select2/css/select2.min.css" rel="stylesheet" type="text/css" />

    <!-- App css -->
    <link href="assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/icons.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/metismenu.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/style.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/style_dark.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/smart_request.css?v=<?= @filemtime(__DIR__ . '/assets/css/smart_request.css') ?>" rel="stylesheet" type="text/css" />
    <script src="assets/js/modernizr.min.js"></script>
    <style type="text/css">
        .gr-view .sr-file-thumb a { display: flex; align-items: center; justify-content: center; width: 100%; height: 100%; }
        .gr-view .sr-file .gr-file-kind { position: absolute; top: 8px; inset-inline-start: 8px; z-index: 1; }
        .gr-del-popup .sr-addlines { max-height: 46vh; padding: 14px; margin: 0; }
        .gr-del-popup .sr-addline-head b { font-size: 13px; color: var(--sr-text); min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .gr-del-popup .sr-attach-choice { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        .gr-del-popup .sr-filepick { margin-top: 10px !important; padding: 10px 12px !important; }
        .gr-del-popup .sr-filepick > i { font-size: 22px !important; }
    </style>
    <?php if ($is_rtl): ?>
        <link href="assets/css/style_rtl.css" rel="stylesheet" type="text/css" />
    <?php endif; ?>
    <script>
        window.departmentMap = <?= json_encode($department_map ?? []) ?>;
        window.lang = <?= json_encode($GLOBALS['translations'] ?? []) ?>;
    </script>
</head>

<body class="enlarged" data-keep-enlarged="true">
    <div id="wrapper">
        <div class="left side-menu">
            <div class="slimscroll-menu" id="remove-scroll">
                <div class="topbar-left">
                    <a href="dashboard.php" class="logo">
                        <span><img src="<?=get_setting($conDB, 'logo')?>" alt="" height="22"></span>
                        <i><img src="<?=get_setting($conDB, 'white_logo')?>" alt="" height="28"></i>
                    </a>
                </div>
                <?php include("./includes/main_menu.php"); ?>
                <div class="clearfix"></div>
            </div>
        </div>

        <div class="content-page">
            <?php include("./includes/topbar.php"); ?>

            <div class="content sr-page gr-view">
                <div class="container-fluid">

                    <?php if (isset($_GET['done']) && in_array($_GET['done'], ['approve', 'reject'], true)): ?>
                        <div class="sr-notice tone-green" role="status">
                            <i class="mdi mdi-check-circle-outline"></i>
                            <div><?= $_GET['done'] === 'approve' ? __('request_approved_successfully', 'The request was approved successfully.') : __('request_rejected_successfully', 'The request was rejected.') ?></div>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($msg)): ?>
                        <div class="sr-notice tone-red" role="alert">
                            <i class="mdi mdi-alert-circle-outline"></i>
                            <div><?= $h($msg) ?></div>
                        </div>
                    <?php endif; ?>

                    <!-- ===== Header ===== -->
                    <div class="sr-card sr-hero">
                        <a href="./all_general_requests.php" class="sr-back"><i class="mdi mdi-arrow-left"></i> <?= __('all_general_requests', 'All General Requests') ?></a>
                        <div class="sr-hero-top">
                            <div style="min-width: 0; flex: 1 1 420px;">
                                <div class="sr-hero-tags">
                                    <span class="sr-chip sr-mono"><i class="mdi mdi-pound"></i><?= $h($request['inv_no']) ?></span>
                                    <span class="sr-pill sr-pill-lg <?= $status_tone ?>"><span class="sr-dot"></span><?= $h($status_label) ?></span>
                                    <span class="sr-pill <?= $priority_tone ?>"><i class="mdi mdi-flag"></i> <?= $h($priority_label) ?></span>
                                    <?php if ($waiting_for_name && $current_status == 'pending_approval'): ?>
                                        <span class="sr-card-sub"><i class="mdi mdi-timer-sand"></i> <?= __('waiting_for', 'Waiting for') ?> <strong><?= $h($waiting_for_name) ?></strong></span>
                                    <?php endif; ?>
                                </div>
                                <h1 class="sr-hero-title"><?= $h(getDisplayName($request['request_title'])) ?></h1>
                            </div>
                            <div class="sr-hero-amount">
                                <div class="sr-amount-label"><?= __('items', 'Items') ?></div>
                                <div class="sr-amount-value"><?= count($items) ?></div>
                                <div class="sr-card-sub"><?= __('total_quantity', 'Total quantity') ?>: <?= $total_qty ?></div>
                            </div>
                        </div>

                        <div class="sr-meta">
                            <div class="sr-meta-item">
                                <div class="sr-meta-label"><i class="mdi mdi-calendar"></i> <?= __('request_date', 'Request Date') ?></div>
                                <div class="sr-meta-value"><?= !empty($request['created_at']) ? date('d M Y', strtotime($request['created_at'])) : '-' ?></div>
                            </div>
                            <div class="sr-meta-item">
                                <div class="sr-meta-label"><i class="mdi mdi-arrow-right"></i> <?= __('target_department', 'Target Department') ?></div>
                                <div class="sr-meta-value" title="<?= $h($request['department_to']) ?>"><?= $h(getDisplayName($request['department_to']) ?: '-') ?></div>
                            </div>
                            <div class="sr-meta-item">
                                <div class="sr-meta-label"><i class="mdi mdi-tag-outline"></i> <?= __('category', 'Category') ?></div>
                                <div class="sr-meta-value"><?= $h(getDisplayName($request['request_category']) ?: '-') ?></div>
                            </div>
                            <div class="sr-meta-item">
                                <div class="sr-meta-label"><i class="mdi mdi-domain"></i> <?= __('requester_department', 'Requester Department') ?></div>
                                <div class="sr-meta-value" title="<?= $h($request['dep_nme']) ?>"><?= $h(getDisplayName($request['dep_nme']) ?: '-') ?></div>
                            </div>
                            <div class="sr-meta-item">
                                <div class="sr-meta-label"><i class="mdi mdi-account-outline"></i> <?= __('requester', 'Requester') ?></div>
                                <div class="sr-meta-value"><?= $h(getDisplayName($request['emp_name']) ?: '-') ?></div>
                            </div>
                        </div>

                        <?php if (!empty($request['description'])): ?>
                            <div class="sr-remarks"><strong><?= __('description', 'Description') ?>:</strong> <?= nl2br($h(getDisplayName($request['description']))) ?></div>
                        <?php endif; ?>

                        <!-- Progress -->
                        <?php if ($current_status !== 'cancelled'): ?>
                        <div class="sr-steps" aria-label="<?= __('status') ?>">
                            <?php foreach ($progress_steps as $i => $ps):
                                $cls = '';
                                if ($i === $steps_failed) { $cls = 'failed'; }
                                elseif ($i <= $steps_done) { $cls = 'done'; }
                                elseif ($i === $steps_current) { $cls = 'current'; }
                            ?>
                                <div class="sr-step <?= $cls ?>">
                                    <div class="sr-step-dot">
                                        <?php if ($cls === 'done'): ?><i class="mdi mdi-check"></i>
                                        <?php elseif ($cls === 'failed'): ?><i class="mdi mdi-close"></i>
                                        <?php else: ?><i class="<?= $ps['icon'] ?>"></i><?php endif; ?>
                                    </div>
                                    <div class="sr-step-label"><?= $h($ps['label']) ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>

                        <?php if ($is_draft_owner || $can_deliver): ?>
                        <div class="sr-hero-actions hidden-print">
                            <?php if ($is_draft_owner): ?>
                                <button type="button" class="sr-btn sr-btn-sm grAddItemBtn"><i class="mdi mdi-plus"></i> <?= __('add_new_item', 'Add New Item') ?></button>
                            <?php endif; ?>
                            <span class="sr-spacer"></span>
                            <?php if ($can_deliver): ?>
                                <button type="button" class="sr-btn sr-btn-sm sr-btn-success grDeliverBtn"><i class="mdi mdi-truck-delivery"></i> <?= __('deliver_items', 'Deliver Items') ?></button>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </div>

                    <?php if ($current_status == 'rejected' && $rejection_note !== ''): ?>
                        <div class="sr-notice tone-red" role="alert">
                            <i class="mdi mdi-close-octagon-outline"></i>
                            <div><?= __('request_rejected_reason') ?> <strong>"<?= $h($rejection_note) ?>"</strong><?php if ($rejected_by_name): ?> &mdash; <?= $h($rejected_by_name) ?><?php endif; ?></div>
                        </div>
                    <?php endif; ?>

                    <div class="row">
                        <!-- ===== Main column ===== -->
                        <div class="col-xl-8">

                            <!-- Items -->
                            <div class="sr-card">
                                <div class="sr-card-head">
                                    <h5 class="sr-card-title"><i class="mdi mdi-format-list-bulleted"></i> <?= __('requested_items', 'Requested Items') ?> <span class="sr-count"><?= count($items) ?></span></h5>
                                    <?php if ($is_draft_owner): ?>
                                        <button type="button" class="sr-btn sr-btn-sm sr-btn-ghost grAddItemBtn"><i class="mdi mdi-plus"></i> <?= __('add_new_item', 'Add New Item') ?></button>
                                    <?php endif; ?>
                                </div>
                                <div class="table-responsive">
                                    <table class="sr-lines">
                                        <thead>
                                            <tr>
                                                <th style="width: 44px;">#</th>
                                                <th><?= __('item_name', 'Item Name') ?></th>
                                                <th class="num"><?= __('quantity') ?></th>
                                                <?php if ($show_delivery_col): ?><th class="num"><?= __('delivery', 'Delivery') ?></th><?php endif; ?>
                                                <?php if ($can_modify_items): ?><th style="width: 80px;"></th><?php endif; ?>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($items)): ?>
                                                <tr><td colspan="5" class="text-center text-muted py-4"><?= __('no_data_available_in_table') ?></td></tr>
                                            <?php endif; ?>
                                            <?php foreach ($items as $index => $item): ?>
                                                <tr>
                                                    <td><span class="sr-line-no"><?= $index + 1 ?></span></td>
                                                    <td>
                                                        <div class="sr-line-name"><?= $h(getDisplayName($item['item_name'])) ?></div>
                                                        <?php if (!empty($item['item_type']) || !empty($item['specifications'])): ?>
                                                            <div class="sr-line-meta">
                                                                <?php if (!empty($item['item_type'])): ?><span><i class="mdi mdi-tag-outline"></i><?= $h(getDisplayName($item['item_type'])) ?></span><?php endif; ?>
                                                                <?php if (!empty($item['specifications'])): ?><span class="sr-wrap-text"><i class="mdi mdi-information-outline"></i><?= $h(getDisplayName($item['specifications'])) ?></span><?php endif; ?>
                                                            </div>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="num"><strong><?= (int)$item['quantity'] ?></strong></td>
                                                    <?php if ($show_delivery_col):
                                                        $ds = $item['delivery_status'] ?: 'pending';
                                                        [$ds_label, $ds_tone, $ds_icon] = $delivery_map[$ds] ?? [$ds, 'tone-slate', 'mdi-help-circle']; ?>
                                                        <td class="num"><span class="sr-pill <?= $ds_tone ?>"><i class="mdi <?= $ds_icon ?>"></i> <?= $h($ds_label) ?></span></td>
                                                    <?php endif; ?>
                                                    <?php if ($can_modify_items): ?>
                                                        <td class="num">
                                                            <span class="sr-line-actions">
                                                                <a href="javascript:void(0);" class="sr-btn sr-btn-sm sr-btn-icon grEditQty" title="<?= __('edit_qty', 'Edit Qty') ?>" data-id="<?= (int)$item['id'] ?>" data-name="<?= $h(getDisplayName($item['item_name'])) ?>" data-qty="<?= (int)$item['quantity'] ?>"><i class="mdi mdi-pencil"></i></a>
                                                                <a href="javascript:void(0);" class="sr-btn sr-btn-sm sr-btn-icon deleteAjax" title="<?= __('remove', 'Remove') ?>" data-id="<?= (int)$item['id'] ?>" data-tbl="general_request_items" data-file="0"><i class="mdi mdi-delete text-danger"></i></a>
                                                            </span>
                                                        </td>
                                                    <?php endif; ?>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Attachments -->
                            <div class="sr-card">
                                <div class="sr-card-head">
                                    <h5 class="sr-card-title"><i class="mdi mdi-paperclip"></i> <?= __('attachments', 'Attachments') ?> <span class="sr-count"><?= count($attachments) ?></span></h5>
                                </div>
                                <div class="sr-card-body">
                                    <?php if (empty($attachments)): ?>
                                        <div class="sr-empty-files"><i class="mdi mdi-file-hidden"></i> <?= __('no_attachment') ?></div>
                                    <?php else: ?>
                                        <div class="sr-files">
                                            <?php foreach ($attachments as $attachment):
                                                $is_delivery_file = ($attachment['attachment_type'] ?? 'request') === 'delivery';
                                                $file_url = ($is_delivery_file ? 'assets/delivery_attachments/' : 'assets/general_request_attachments/') . rawurlencode((string)$attachment['attachment']);
                                                $ext = strtolower((string)($attachment['docu_ext'] ?: pathinfo((string)$attachment['attachment'], PATHINFO_EXTENSION)));
                                                $is_image = in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true);
                                                $file_icon = $ext === 'pdf' ? 'pdf' : (in_array($ext, ['xls', 'xlsx'], true) ? 'excel' : (in_array($ext, ['doc', 'docx'], true) ? 'doc' : 'txt'));
                                            ?>
                                                <div class="sr-file" style="position: relative;">
                                                    <span class="sr-pill sr-pill-xs gr-file-kind <?= $is_delivery_file ? 'tone-green' : 'tone-sky' ?>"><?= $is_delivery_file ? __('delivery', 'Delivery') : __('request', 'Request') ?></span>
                                                    <div class="sr-file-thumb">
                                                        <a href="<?= $h($file_url) ?>" target="_blank" rel="noopener" title="<?= $h($attachment['attachment']) ?>">
                                                            <?php if ($is_image): ?>
                                                                <img src="<?= $h($file_url) ?>" alt="<?= __('attachment') ?>" loading="lazy" />
                                                            <?php else: ?>
                                                                <img class="sr-file-icon" src="assets/images/file_icons/<?= $file_icon ?>.svg" alt="<?= $h($ext) ?>" />
                                                            <?php endif; ?>
                                                        </a>
                                                    </div>
                                                    <div class="sr-file-foot">
                                                        <div style="min-width: 0;">
                                                            <div class="sr-file-name" title="<?= $h($attachment['attachment']) ?>"><?= strtoupper($h($ext ?: 'file')) ?></div>
                                                            <?php if (!empty($attachment['created_at'])): ?><div class="sr-file-date"><?= date('d M Y, h:ia', strtotime($attachment['created_at'])) ?></div><?php endif; ?>
                                                        </div>
                                                        <a href="<?= $h($file_url) ?>" download class="sr-file-dl" title="<?= __('download', 'Download') ?>"><i class="mdi mdi-download"></i></a>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                        </div>

                        <!-- ===== Side column ===== -->
                        <div class="col-xl-4">
                            <div class="sr-sticky">

                            <?php if ($is_draft_owner): ?>
                                <!-- Creator: build the approval chain and submit -->
                                <div class="sr-card sr-action-card">
                                    <div class="sr-card-head">
                                        <h5 class="sr-card-title"><i class="mdi mdi-send"></i> <?= __('submit_for_approval', 'Submit for Approval') ?></h5>
                                    </div>
                                    <div class="sr-card-body">
                                        <form method="POST" id="approvalForm">
                                            <input type="hidden" name="inv_no" value="<?= $h($inv_no) ?>">
                                            <label class="sr-field-label" for="approver-select"><?= __('select_approvers_in_order', 'Select approvers in order') ?></label>
                                            <div class="sr-add-row">
                                                <select class="form-control" id="approver-select" data-placeholder="<?= $h(__('select_an_approver', 'Select an approver')) ?>">
                                                    <option value=""></option>
                                                    <?php foreach ($potential_approvers as $approver): ?>
                                                        <option value="<?= (int)$approver['emp_id'] ?>" data-type="<?= $h($approver['user_type']) ?>" data-dept="<?= $h($approver['dept']) ?>"><?= $h(getDisplayName($approver['name'])) ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <button class="sr-btn sr-btn-primary" type="button" id="add-approver-btn"><i class="mdi mdi-plus"></i> <?= __('add', 'Add') ?></button>
                                            </div>
                                            <ol class="sr-approver-list" id="approver-list-container"></ol>
                                            <div class="sr-approver-empty" id="approver-empty"><i class="mdi mdi-account-multiple-plus"></i> <?= __('approval_chain_not_defined_yet') ?></div>
                                            <div id="approver-error-container" class="text-danger"></div>
                                            <div class="sr-hint"><i class="mdi mdi-information-outline"></i> <?= __('approver_order_hint', 'Approvers act one after another, in this order. Use the arrows to reorder.') ?></div>
                                            <button type="submit" class="sr-btn sr-btn-primary sr-btn-block mt-3"><i class="mdi mdi-send"></i> <?= __('submit_for_approval', 'Submit for Approval') ?></button>
                                        </form>
                                    </div>
                                </div>

                            <?php elseif ($is_current_approver): ?>
                                <!-- Current approver: approve / reject -->
                                <div class="sr-card sr-action-card">
                                    <div class="sr-card-head">
                                        <h5 class="sr-card-title"><i class="mdi mdi-gavel"></i> <?= __('your_decision', 'Your decision') ?></h5>
                                        <span class="sr-pill tone-amber"><?= __('level') ?> <?= (int)$current_approver_level ?></span>
                                    </div>
                                    <div class="sr-card-body">
                                        <form method="POST" id="approvalActionForm">
                                            <input type="hidden" name="inv_no" value="<?= $h($inv_no) ?>">
                                            <input type="hidden" name="action" value="submit">
                                            <div class="sr-attach-choice">
                                                <label class="sr-choice sr-choice-approve">
                                                    <input type="radio" name="status" value="approve" class="sr-decision">
                                                    <span><i class="mdi mdi-check-circle-outline"></i> <?= __('approve') ?></span>
                                                </label>
                                                <label class="sr-choice sr-choice-reject">
                                                    <input type="radio" name="status" value="reject" class="sr-decision">
                                                    <span><i class="mdi mdi-close-circle-outline"></i> <?= __('reject') ?></span>
                                                </label>
                                            </div>
                                            <div id="decision-error-container" class="text-danger small mt-1"></div>
                                            <div class="mt-3">
                                                <label class="sr-field-label" for="RejectInput">
                                                    <span class="note-label-approve"><?= __('note_optional', 'Note (optional)') ?></span>
                                                    <span class="note-label-reject" style="display: none;"><?= __('rejection_note') ?> <span class="text-danger">*</span></span>
                                                </label>
                                                <textarea class="form-control" name="note" id="RejectInput" rows="3"></textarea>
                                            </div>
                                            <button type="submit" class="sr-btn sr-btn-primary sr-btn-block mt-3" id="decisionSubmit"><i class="mdi mdi-check-all"></i> <?= __('submit_action', 'Submit') ?></button>
                                        </form>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <?php if ($can_deliver): ?>
                                <!-- Approved: deliver -->
                                <div class="sr-card sr-action-card">
                                    <div class="sr-card-head">
                                        <h5 class="sr-card-title"><i class="mdi mdi-truck-delivery"></i> <?= $current_status === 'waiting_for_delivery' ? __('waiting_for_delivery', 'Waiting for Delivery') : __('ready_for_delivery', 'Ready for Delivery') ?></h5>
                                    </div>
                                    <div class="sr-card-body">
                                        <p class="sr-card-sub mb-3">
                                            <?= $current_status === 'waiting_for_delivery'
                                                ? __('approval_complete_mark_items_as_delivered_to_finalize_this_request', 'Approval complete. Mark items as delivered to finalize this request.')
                                                : __('this_request_is_approved_click_below_to_mark_items_as_delivered', 'This request is approved. Click below to mark items as delivered.') ?>
                                        </p>
                                        <button type="button" class="sr-btn sr-btn-success sr-btn-block grDeliverBtn"><i class="mdi mdi-truck-delivery"></i> <?= __('deliver_items', 'Deliver Items') ?></button>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <?php if ($delivery_info): ?>
                                <!-- Delivery -->
                                <div class="sr-card">
                                    <div class="sr-card-head">
                                        <h5 class="sr-card-title"><i class="mdi mdi-truck-delivery"></i> <?= __('delivery_details', 'Delivery Details') ?></h5>
                                        <?php if ($current_status === 'completed'): ?><span class="sr-pill tone-green"><span class="sr-dot"></span><?= __('completed', 'Completed') ?></span><?php endif; ?>
                                    </div>
                                    <div class="sr-card-body">
                                        <dl class="sr-kv">
                                            <div class="row-kv"><dt><?= __('received_by', 'Received By') ?></dt><dd><?= $h(getDisplayName($delivery_info['received_employee_name']) ?: '-') ?></dd></div>
                                            <div class="row-kv"><dt><?= __('date', 'Date') ?></dt><dd><?= !empty($delivery_info['delivery_date']) ? date('d M Y, H:i', strtotime($delivery_info['delivery_date'])) : '-' ?></dd></div>
                                            <?php foreach ($delivery_map as $ds_key => $ds):
                                                $n = count(array_filter($items, function ($it) use ($ds_key) { return ($it['delivery_status'] ?: 'pending') === $ds_key; }));
                                                if (!$n) continue; ?>
                                                <div class="row-kv"><dt><?= $h($ds[0]) ?></dt><dd><span class="sr-pill <?= $ds[1] ?>"><?= $n ?></span></dd></div>
                                            <?php endforeach; ?>
                                        </dl>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <!-- Approval chain -->
                            <div class="sr-card">
                                <div class="sr-card-head">
                                    <h5 class="sr-card-title"><i class="mdi mdi-account-multiple"></i> <?= __('approval_status', 'Approval Status') ?></h5>
                                    <span class="sr-count"><?= $chain_approved ?>/<?= count($approval_chain) ?></span>
                                </div>
                                <div class="sr-card-body">
                                    <?php if (empty($approval_chain)): ?>
                                        <div class="sr-empty-files"><?= __('approval_chain_not_defined_yet') ?></div>
                                    <?php else: ?>
                                        <ol class="sr-chain">
                                            <?php foreach ($approval_chain as $step):
                                                $step_status = $step['status']; // pending | approved | rejected | awaiting
                                                $step_tone = ['approved' => 'tone-green', 'rejected' => 'tone-red', 'pending' => 'tone-amber'][$step_status] ?? 'tone-slate';
                                                $action_date = !empty($step['action_date']) ? date('d M Y, H:i', strtotime($step['action_date'])) : '';
                                                $step_name = getDisplayName($step['approver_name']);
                                            ?>
                                                <li class="sr-chain-item <?= $h($step_status) ?>">
                                                    <span class="sr-chain-marker">
                                                        <?php if ($step_status == 'approved'): ?><i class="mdi mdi-check"></i>
                                                        <?php elseif ($step_status == 'rejected'): ?><i class="mdi mdi-close"></i>
                                                        <?php else: ?><?= $h($sr_initials($step_name)) ?><?php endif; ?>
                                                    </span>
                                                    <div class="sr-chain-body">
                                                        <div class="sr-chain-row">
                                                            <span class="sr-chain-name"><?= $h($step_name) ?></span>
                                                            <span class="sr-pill <?= $step_tone ?>"><?= $h(__($step_status)) ?></span>
                                                        </div>
                                                        <div class="sr-chain-level"><?= __('level') ?> <?= (int)$step['approval_level'] ?><?php if ($action_date): ?> &middot; <?= $action_date ?><?php endif; ?></div>
                                                        <?php if (!empty($step['note'])): ?>
                                                            <div class="sr-chain-note"><?= $h($step['note']) ?></div>
                                                        <?php endif; ?>
                                                    </div>
                                                </li>
                                            <?php endforeach; ?>
                                        </ol>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Activity -->
                            <div class="sr-card">
                                <div class="sr-card-head">
                                    <h5 class="sr-card-title"><i class="mdi mdi-history"></i> <?= __('status_history_timeline', 'Status History Timeline') ?></h5>
                                    <span class="sr-count"><?= count($history) ?></span>
                                </div>
                                <div class="sr-card-body">
                                    <?php if (empty($history)): ?>
                                        <div class="sr-empty-files"><?= __('no_history', 'No history recorded yet.') ?></div>
                                    <?php else: ?>
                                        <ul class="sr-activity">
                                            <?php foreach ($history as $hist):
                                                $sc = strtolower((string)$hist['status']);
                                                $h_tone = 'tone-amber'; $h_icon = 'mdi-clock';
                                                if (strpos($sc, 'deliver') !== false || strpos($sc, 'completed') !== false) {
                                                    $h_tone = 'tone-green'; $h_icon = 'mdi-truck-delivery';
                                                } elseif (strpos($sc, 'approved') !== false) {
                                                    $h_tone = 'tone-green'; $h_icon = 'mdi-check';
                                                } elseif (strpos($sc, 'rejected') !== false || strpos($sc, 'cancel') !== false) {
                                                    $h_tone = 'tone-red'; $h_icon = 'mdi-close';
                                                } elseif (strpos($sc, 'draft') !== false || strpos($sc, 'created') !== false) {
                                                    $h_tone = 'tone-slate'; $h_icon = 'mdi-file-document';
                                                }
                                            ?>
                                                <li>
                                                    <span class="sr-activity-icon <?= $h_tone ?>"><i class="mdi <?= $h_icon ?>"></i></span>
                                                    <div class="sr-activity-body">
                                                        <div class="sr-activity-title"><?= $h(getDisplayName(ucwords(str_replace('_', ' ', (string)$hist['status'])))) ?></div>
                                                        <?php if (!empty($hist['note'])): ?>
                                                            <div class="sr-activity-note"><?= nl2br($h(getDisplayName($hist['note']))) ?></div>
                                                        <?php endif; ?>
                                                        <div class="sr-activity-meta">
                                                            <span><i class="mdi mdi-account-outline"></i> <?= $h(getDisplayName($hist['emp_name'])) ?></span>
                                                            <span><i class="mdi mdi-clock"></i> <?= date('d M Y, H:i', strtotime($hist['created_at'])) ?></span>
                                                        </div>
                                                    </div>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php endif; ?>
                                </div>
                            </div>

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
    <script src="./plugins/select2/js/select2.min.js"></script>
    <script src="assets/js/jquery.core.js"></script>
    <script src="assets/js/jquery.app.js?t=<?= time() ?>"></script>
    <script src="assets/js/gr_item_suggest.js?v=<?= @filemtime(__DIR__ . '/assets/js/gr_item_suggest.js') ?>"></script>

    <script>
    $(function () {
        const INV_NO = <?= json_encode((string)$inv_no) ?>;
        const ITEMS = <?= json_encode(array_values($items)) ?>;
        const ITEM_TYPES = <?= json_encode(gr_item_types()) ?>;
        const TARGET_DEPT = <?= json_encode((string)$request['department_to']) ?>;
        const DELIVERY = <?= json_encode(array_map(function ($d) { return ['label' => $d[0], 'icon' => $d[2]]; }, gr_delivery_statuses())) ?>;
        const T = {
            error: <?= json_encode(__('error')) ?>,
            success: <?= json_encode(__('success')) ?>,
            ok: <?= json_encode(__('ok', 'OK')) ?>,
            cancel: <?= json_encode(__('cancel', 'Cancel')) ?>,
            select: <?= json_encode(__('select')) ?>,
            selectApprover: <?= json_encode(__('select_approver_from_list', 'Please select an approver from the list')) ?>,
            approverAdded: <?= json_encode(__('approver_already_added', 'This approver has already been added')) ?>,
            oneApprover: <?= json_encode(__('select_at_least_one_approver', 'Please select at least one approver.')) ?>,
            moveUp: <?= json_encode(__('move_up', 'Move up')) ?>,
            moveDown: <?= json_encode(__('move_down', 'Move down')) ?>,
            remove: <?= json_encode(__('remove', 'Remove')) ?>,
            submitting: <?= json_encode(__('submitting', 'Submitting...')) ?>,
            submitted: <?= json_encode(__('request_submitted_successfully', 'Request submitted successfully.')) ?>,
            submitFailed: <?= json_encode(__('submission_failed', 'Submission failed.')) ?>,
            pickDecision: <?= json_encode(__('please_choose_approve_or_reject', 'Please choose Approve or Reject.')) ?>,
            rejectNote: <?= json_encode(__('rejection_note_required', 'Please enter a rejection note.')) ?>,
            removeApproversFirst: <?= json_encode(__('remove_approvers_to_add_items', 'Remove approvers to add more items')) ?>,
            editQty: <?= json_encode(__('edit_item_quantity', 'Edit item quantity')) ?>,
            newQty: <?= json_encode(__('new_quantity', 'New quantity')) ?>,
            qtyGt0: <?= json_encode(__('quantity_must_be_greater_than', 'Quantity must be greater than 0')) ?>,
            qtyUpdated: <?= json_encode(__('item_quantity_updated', 'Item quantity updated')) ?>,
            update: <?= json_encode(__('yes_update', 'Update')) ?>,
            addItem: <?= json_encode(__('add_new_item', 'Add New Item')) ?>,
            itemName: <?= json_encode(__('item_name', 'Item Name')) ?>,
            itemNamePh: <?= json_encode(__('enter_item_name', 'Enter item name')) ?>,
            itemType: <?= json_encode(__('type_category', 'Type/Category')) ?>,
            quantity: <?= json_encode(__('quantity', 'Quantity')) ?>,
            specs: <?= json_encode(__('specifications_optional', 'Specifications (optional)')) ?>,
            specsPh: <?= json_encode(__('item_specifications', 'Item specifications or requirements')) ?>,
            itemDetails: <?= json_encode(__('item_details', 'Item details')) ?>,
            required: <?= json_encode(__('fill_required_fields_validation', 'Please fill all required fields')) ?>,
            itemAdded: <?= json_encode(__('item_added_successfully', 'Item added successfully')) ?>,
            addFailed: <?= json_encode(__('failed_to_add_item', 'Failed to add item')) ?>,
            delivery: <?= json_encode(__('delivery_details', 'Delivery Details')) ?>,
            receivedBy: <?= json_encode(__('received_by_employee', 'Received by (employee)')) ?>,
            searchEmp: <?= json_encode(__('search_employee', 'Search employee')) ?>,
            searchHint: <?= json_encode(__('search_by_name_employee_id_or_department', 'Search by name, employee ID or department')) ?>,
            searching: <?= json_encode(__('searching', 'Searching...')) ?>,
            itemsToDeliver: <?= json_encode(__('items_to_deliver', 'Items to deliver')) ?>,
            attachOpt: <?= json_encode(__('attachment_for_this_item_optional', 'Attachment for this item (optional)')) ?>,
            attachHint: <?= json_encode(__('delivery_file_hint', 'PDF, JPG, PNG, DOC, XLSX, ZIP - max 5 MB')) ?>,
            fileTooLarge: <?= json_encode(__('file_too_large', 'File is too large (max 5 MB)')) ?>,
            badFileType: <?= json_encode(__('invalid_file_type', 'Invalid file type')) ?>,
            pickReceiver: <?= json_encode(__('please_select_an_employee_who_received_the_items', 'Please select the employee who received the items')) ?>,
            submitDelivery: <?= json_encode(__('submit_delivery', 'Submit Delivery')) ?>,
            delivered: <?= json_encode(__('delivery_submitted_successfully', 'Delivery submitted successfully')) ?>,
            deliveryFailed: <?= json_encode(__('failed_to_submit_delivery', 'Failed to submit delivery')) ?>
        };

        function esc(str) {
            return $('<div>').text(str == null ? '' : String(str)).html();
        }
        function errText(err, fallback) {
            return (err && (err.message || err.statusText)) ? String(err.message || err.statusText).substring(0, 300) : fallback;
        }

        // ---------- Draft: approval chain builder (order = approval level) ----------
        function getRoleText(userType) {
            if (!userType) return '';
            switch (userType.toLowerCase()) {
                case 'dept_user': return 'Manager';
                case 'assistant': return 'Assistant';
                case 'gm': return 'General Manager';
                case 'hr': return 'HR';
                case 'administrator': return 'Admin';
                default: return userType.charAt(0).toUpperCase() + userType.slice(1);
            }
        }
        function approverBadge(approver, extraClass) {
            const $el = $(approver.element);
            const userType = $el.data('type') || '';
            const deptName = window.departmentMap[$el.data('dept') || ''] || '';
            const roleText = getRoleText(userType);
            const badgeText = deptName ? (deptName + ' ' + roleText) : roleText;
            if (!badgeText) return null;
            return $('<span>', { 'class': 'user-type-badge ' + userType + ' ' + extraClass, text: badgeText });
        }

        const $approverList = $('#approver-list-container');
        if ($('#approver-select').length) {
            $('#approver-select').select2({
                placeholder: $('#approver-select').data('placeholder'),
                allowClear: true,
                width: '100%',
                templateResult: function (a) {
                    if (!a.id) return a.text;
                    return $('<span class="d-flex align-items-center justify-content-between w-100">')
                        .append($('<span class="select2-option-text">').text(a.text)).append(approverBadge(a, ''));
                },
                templateSelection: function (a) {
                    if (!a.id) return a.text;
                    return $('<span class="d-flex align-items-center w-100">')
                        .append($('<span class="select2-selection-text">').text(a.text)).append(approverBadge(a, 'select2-selection__rendered-badge'));
                }
            });
        }

        function renumberApprovers() {
            const $list = $approverList.children('.sr-approver-item');
            $list.each(function (i) {
                $(this).find('.sr-level').text(i + 1);
                $(this).find('.move-up-btn').prop('disabled', i === 0);
                $(this).find('.move-down-btn').prop('disabled', i === $list.length - 1);
            });
            $('#approver-empty').toggle($list.length === 0);
            if ($list.length) $('#approver-error-container').empty();
            // Items are locked once approvers are being chosen
            $('.grAddItemBtn').prop('disabled', $list.length > 0).attr('title', $list.length ? T.removeApproversFirst : null);
        }

        $('#add-approver-btn').on('click', function () {
            const $sel = $('#approver-select').find('option:selected');
            const id = $sel.val();
            if (!id) {
                Swal.fire({ title: T.error, text: T.selectApprover, icon: 'warning', allowOutsideClick: false });
                return;
            }
            if ($approverList.find('input[name="approvers[]"][value="' + id + '"]').length) {
                Swal.fire({ title: T.error, text: T.approverAdded, icon: 'warning', allowOutsideClick: false });
                return;
            }
            $approverList.append(
                $('<li class="sr-approver-item approver-tag">').attr('data-id', id)
                    .append('<span class="sr-level"></span>')
                    .append($('<span class="sr-approver-name">').text($.trim($sel.text())))
                    .append($('<input type="hidden" name="approvers[]">').val(id))
                    .append('<span class="sr-approver-tools">' +
                        '<button type="button" class="move-up-btn" title="' + esc(T.moveUp) + '"><i class="mdi mdi-chevron-up"></i></button>' +
                        '<button type="button" class="move-down-btn" title="' + esc(T.moveDown) + '"><i class="mdi mdi-chevron-down"></i></button>' +
                        '<button type="button" class="remove-approver-btn" aria-label="' + esc(T.remove) + '"><i class="mdi mdi-close"></i></button>' +
                    '</span>')
            );
            $('#approver-select').val(null).trigger('change');
            renumberApprovers();
        });
        $approverList.on('click', '.remove-approver-btn', function () {
            $(this).closest('.sr-approver-item').remove();
            renumberApprovers();
        }).on('click', '.move-up-btn', function () {
            const $it = $(this).closest('.sr-approver-item');
            $it.prev('.sr-approver-item').before($it);
            renumberApprovers();
        }).on('click', '.move-down-btn', function () {
            const $it = $(this).closest('.sr-approver-item');
            $it.next('.sr-approver-item').after($it);
            renumberApprovers();
        });
        if ($approverList.length) renumberApprovers();

        // Draft -> pending_approval (AJAX to this page)
        $('#approvalForm').on('submit', function (e) {
            e.preventDefault();
            if ($approverList.children('.sr-approver-item').length === 0) {
                $('#approver-error-container').html('<div class="small mt-2"><i class="mdi mdi-alert-circle-outline"></i> ' + esc(T.oneApprover) + '</div>');
                $('#approver-select').select2('open');
                return;
            }
            Swal.fire({ title: T.submitting, allowOutsideClick: false, showConfirmButton: false, didOpen: function () { Swal.showLoading(); } });
            $.ajax({
                url: window.location.href,
                type: 'POST',
                dataType: 'json',
                data: $(this).serialize() + '&' + $.param({ action: 'submit', ajax: 1 })
            }).then(function (data) {
                if (data.status !== 'success') throw new Error(data.message || T.submitFailed);
                Swal.fire({ title: T.success, text: data.message || T.submitted, icon: 'success', timer: 1600, showConfirmButton: false, allowOutsideClick: false })
                    .then(function () { window.location.replace(window.location.pathname + window.location.search); });
            }).catch(function (err) {
                Swal.fire({ title: T.error, text: errText(err, T.submitFailed), icon: 'error', allowOutsideClick: false });
            });
        });

        // ---------- Current approver: approve / reject (note required on reject) ----------
        $('.sr-decision').on('change', function () {
            const action = $('.sr-decision:checked').val();
            const isReject = action === 'reject';
            $('#decision-error-container').empty();
            $('.note-label-reject').toggle(isReject);
            $('.note-label-approve').toggle(!isReject);
            if (isReject) $('#RejectInput').trigger('focus');
            $('#decisionSubmit')
                .toggleClass('sr-btn-primary', !action)
                .toggleClass('sr-btn-success', action === 'approve')
                .toggleClass('sr-btn-danger', isReject);
        });
        $('#approvalActionForm').on('submit', function (e) {
            const action = $('.sr-decision:checked').val();
            if (!action) {
                e.preventDefault();
                $('#decision-error-container').text(T.pickDecision);
                return;
            }
            if (action === 'reject' && !$.trim($('#RejectInput').val())) {
                e.preventDefault();
                $('#decision-error-container').text(T.rejectNote);
                $('#RejectInput').trigger('focus');
                return;
            }
            $('#decisionSubmit').prop('disabled', true).html('<i class="mdi mdi-loading mdi-spin"></i> ' + esc(T.submitting));
        });

        // ---------- Items: edit quantity / add item ----------
        $(document).on('click', '.grEditQty', function () {
            const id = $(this).data('id');
            Swal.fire({
                title: T.editQty,
                text: String($(this).data('name') || ''),
                input: 'number',
                inputValue: $(this).data('qty'),
                inputLabel: T.newQty,
                inputAttributes: { min: 1, step: 1 },
                showCancelButton: true,
                confirmButtonText: T.update,
                cancelButtonText: T.cancel,
                allowOutsideClick: false,
                showLoaderOnConfirm: true,
                preConfirm: function (value) {
                    const qty = parseInt(value, 10) || 0;
                    if (qty <= 0) { Swal.showValidationMessage(T.qtyGt0); return false; }
                    return $.ajax({ url: window.location.href, type: 'POST', dataType: 'json', data: { action: 'update_item', item_id: id, quantity: qty } })
                        .then(function (data) {
                            if (data.status !== 'success') throw new Error(data.message);
                            return data;
                        }).catch(function (err) { Swal.showValidationMessage(errText(err, T.error)); });
                }
            }).then(function (r) {
                if (r.isConfirmed && r.value) {
                    Swal.fire({ title: T.success, text: T.qtyUpdated, icon: 'success', timer: 1200, showConfirmButton: false }).then(function () { location.reload(); });
                }
            });
        });

        function itemTypeOptions() {
            const types = ITEM_TYPES[TARGET_DEPT] || ITEM_TYPES['Other'] || [];
            return '<option value="">' + esc(T.select) + '</option>' +
                types.map(function (t) { return '<option value="' + esc(t.v) + '">' + esc(t.l) + '</option>'; }).join('');
        }

        let itemSuggest = null;
        $(document).on('click', '.grAddItemBtn', function () {
            if ($(this).prop('disabled')) return;
            Swal.fire({
                title: T.addItem,
                width: '640px',
                html:
                    '<form id="grAddItemForm" class="sr-page sr-form" autocomplete="off" novalidate>' +
                        '<div class="sr-fsec">' +
                            '<div class="sr-fsec-head"><span><i class="mdi mdi-package-variant"></i> ' + esc(T.itemDetails) + '</span></div>' +
                            '<div class="sr-fgrid">' +
                                '<div class="sr-fcol c-12"><label>' + esc(T.itemName) + ' <span class="text-danger">*</span></label>' +
                                    '<input type="text" name="item_name" class="form-control" maxlength="250" autocomplete="off" placeholder="' + esc(T.itemNamePh) + '"></div>' +
                                '<div class="sr-fcol c-8"><label>' + esc(T.itemType) + '</label>' +
                                    '<select name="item_type" class="form-control">' + itemTypeOptions() + '</select></div>' +
                                '<div class="sr-fcol c-4"><label>' + esc(T.quantity) + ' <span class="text-danger">*</span></label>' +
                                    '<input type="number" name="quantity" class="form-control" value="1" min="1" step="1"></div>' +
                                '<div class="sr-fcol c-12"><label>' + esc(T.specs) + '</label>' +
                                    '<textarea name="specifications" class="form-control" rows="3" placeholder="' + esc(T.specsPh) + '"></textarea></div>' +
                            '</div>' +
                        '</div>' +
                    '</form>',
                showCancelButton: true,
                confirmButtonText: '<i class="mdi mdi-plus"></i> ' + esc(T.addItem),
                cancelButtonText: T.cancel,
                allowOutsideClick: false,
                showLoaderOnConfirm: true,
                customClass: { popup: 'sr-addline-popup' },
                didOpen: function () {
                    const $f = $('#grAddItemForm');
                    // Item Name auto-fill from earlier requests; picking one also fills Type and Specifications
                    itemSuggest = GRItemSuggest.bind($f, {
                        selector: '[name="item_name"]',
                        getDept: function () { return TARGET_DEPT; },
                        onPick: function (it) {
                            const $type = $f.find('[name="item_type"]');
                            if (it.type && $type.find('option').filter(function () { return this.value === it.type; }).length) $type.val(it.type);
                            const $specs = $f.find('[name="specifications"]');
                            if (it.specs && !$.trim($specs.val())) $specs.val(it.specs);
                            $f.find('[name="quantity"]').trigger('focus').trigger('select');
                        }
                    });
                    $f.find('[name="item_name"]').trigger('focus');
                },
                willClose: function () {
                    if (itemSuggest) { itemSuggest.destroy(); itemSuggest = null; }
                },
                preConfirm: function () {
                    const $f = $('#grAddItemForm');
                    const name = $.trim($f.find('[name="item_name"]').val());
                    const qty = parseInt($f.find('[name="quantity"]').val(), 10) || 0;
                    $f.find('[name="item_name"]').toggleClass('is-invalid', !name);
                    $f.find('[name="quantity"]').toggleClass('is-invalid', qty <= 0);
                    if (!name || qty <= 0) { Swal.showValidationMessage(T.required); return false; }
                    return $.ajax({
                        url: './includes/ajaxFile/ajaxGeneralRequest.php',
                        type: 'POST',
                        dataType: 'json',
                        data: $f.serialize() + '&' + $.param({ action: 'add_item_to_request', inv_no: INV_NO })
                    }).then(function (data) {
                        if (!data.success) throw new Error(data.message || T.addFailed);
                        return data;
                    }).catch(function (err) { Swal.showValidationMessage(errText(err, T.addFailed)); });
                }
            }).then(function (r) {
                if (r.isConfirmed && r.value) {
                    Swal.fire({ title: T.success, text: T.itemAdded, icon: 'success', timer: 1200, showConfirmButton: false }).then(function () { location.reload(); });
                }
            });
        });

        // ---------- Delivery (approved / waiting_for_delivery) ----------
        const DELIVERY_EXT = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx', 'xlsx', 'zip'];
        const DELIVERY_MAX = 5 * 1024 * 1024;

        function deliveryFormHTML() {
            const lines = ITEMS.map(function (it, i) {
                const id = parseInt(it.id, 10);
                const choices = ['delivered', 'pending', 'canceled'].map(function (s) {
                    return '<label class="sr-choice"><input type="radio" name="items[' + id + ']" value="' + s + '"' + (s === 'delivered' ? ' checked' : '') + '>' +
                        '<span><i class="mdi ' + DELIVERY[s].icon + '"></i> ' + esc(DELIVERY[s].label) + '</span></label>';
                }).join('');
                return '<div class="sr-addline">' +
                    '<div class="sr-addline-head">' +
                        '<span class="sr-addline-no">' + (i + 1) + '</span>' +
                        '<b title="' + esc(it.item_name) + '">' + esc(it.item_name) + '</b>' +
                        '<span class="sr-addline-total">' + esc(T.quantity) + ': <strong>' + esc(it.quantity) + '</strong></span>' +
                    '</div>' +
                    '<div class="sr-attach-choice">' + choices + '</div>' +
                    '<label class="sr-filepick" for="grDelFile' + id + '">' +
                        '<input type="file" id="grDelFile' + id + '" name="attachments[' + id + ']" accept=".' + DELIVERY_EXT.join(',.') + '">' +
                        '<i class="mdi mdi-cloud-upload"></i>' +
                        '<span><b class="js-file-name">' + esc(T.attachOpt) + '</b><small>' + esc(T.attachHint) + '</small></span>' +
                    '</label>' +
                '</div>';
            }).join('');

            return '<form id="grDeliveryForm" class="sr-page sr-form" autocomplete="off" novalidate>' +
                '<div class="sr-fsec">' +
                    '<div class="sr-fsec-head"><span><i class="mdi mdi-account-check"></i> ' + esc(T.receivedBy) + ' <span class="text-danger">*</span></span></div>' +
                    '<div class="sr-fgrid"><div class="sr-fcol c-12">' +
                        '<select id="grReceivedBy" name="received_by" class="form-control"><option value=""></option></select>' +
                        '<small class="sr-fhint">' + esc(T.searchHint) + '</small>' +
                    '</div></div>' +
                '</div>' +
                '<div class="sr-fsec">' +
                    '<div class="sr-fsec-head"><span><i class="mdi mdi-package-variant"></i> ' + esc(T.itemsToDeliver) + '</span><span class="sr-count">' + ITEMS.length + '</span></div>' +
                    '<div class="sr-addlines">' + lines + '</div>' +
                '</div>' +
            '</form>';
        }

        $(document).on('click', '.grDeliverBtn', function () {
            Swal.fire({
                title: T.delivery,
                width: '760px',
                html: deliveryFormHTML(),
                showCancelButton: true,
                confirmButtonText: '<i class="mdi mdi-check"></i> ' + esc(T.submitDelivery),
                cancelButtonText: T.cancel,
                allowOutsideClick: false,
                showLoaderOnConfirm: true,
                customClass: { popup: 'sr-addline-popup gr-del-popup' },
                didOpen: function () {
                    const $f = $('#grDeliveryForm');
                    $('#grReceivedBy').select2({
                        placeholder: T.searchEmp,
                        allowClear: true,
                        width: '100%',
                        dropdownParent: $(Swal.getPopup()),
                        minimumInputLength: 0,
                        ajax: {
                            url: './includes/ajaxFile/ajaxGeneralRequest.php',
                            type: 'POST',
                            dataType: 'json',
                            delay: 300,
                            data: function (params) { return { action: 'get_employees', search: params.term || '' }; },
                            processResults: function (data) { return { results: data.results || [] }; }
                        },
                        templateResult: function (d) {
                            if (d.loading) return T.searching;
                            if (!d.id) return d.text;
                            return $('<div>').text(d.text + (d.department ? ' - ' + d.department : ''));
                        },
                        templateSelection: function (d) {
                            if (!d.id) return d.text;
                            return d.name ? (d.name + ' (' + d.emp_id + ')' + (d.department ? ' - ' + d.department : '')) : d.text;
                        }
                    });
                    // Per-item file: show the name, reject wrong type / size
                    $f.find('.sr-filepick input[type=file]').each(function () {
                        const $in = $(this), $pick = $in.closest('.sr-filepick'), $name = $pick.find('.js-file-name'), empty = $name.text();
                        $in.on('change', function () {
                            const f = this.files && this.files[0];
                            let bad = '';
                            if (f && DELIVERY_EXT.indexOf((f.name.split('.').pop() || '').toLowerCase()) === -1) bad = T.badFileType;
                            else if (f && f.size > DELIVERY_MAX) bad = T.fileTooLarge;
                            if (bad) { this.value = ''; }
                            $pick.toggleClass('has-file', !!f && !bad).toggleClass('is-invalid', !!bad);
                            $name.text(bad || (f ? f.name + ' (' + (f.size / 1048576).toFixed(2) + ' MB)' : empty));
                        });
                    });
                },
                willClose: function () {
                    if ($('#grReceivedBy').hasClass('select2-hidden-accessible')) $('#grReceivedBy').select2('destroy');
                },
                preConfirm: function () {
                    const form = document.getElementById('grDeliveryForm');
                    if (!$('#grReceivedBy').val()) { Swal.showValidationMessage(T.pickReceiver); return false; }
                    const fd = new FormData(form);
                    fd.append('action', 'mark_delivery');
                    fd.append('inv_no', INV_NO);
                    return $.ajax({
                        url: './includes/ajaxFile/ajaxGeneralRequest.php',
                        type: 'POST',
                        data: fd,
                        processData: false,
                        contentType: false,
                        dataType: 'json'
                    }).then(function (data) {
                        if (!data.success) throw new Error(data.message || T.deliveryFailed);
                        return data;
                    }).catch(function (err) { Swal.showValidationMessage(errText(err, T.deliveryFailed)); });
                }
            }).then(function (r) {
                if (r.isConfirmed && r.value) {
                    Swal.fire({ title: T.success, text: T.delivered, icon: 'success', timer: 1500, showConfirmButton: false, allowOutsideClick: false }).then(function () { location.reload(); });
                }
            });
        });
    });
    </script>
</body>
</html>
