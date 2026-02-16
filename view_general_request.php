<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/session_check.php';
require_once __DIR__ . '/includes/helper_functions.php';

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
$approver_query = mysqli_query($conDB, "SELECT e.`emp_id`, e.`name`, al.`user_type`, e.`dept`
                                        FROM `employees` e
                                        JOIN `admin_login` al ON e.`emp_id` = al.`emp_id`
                                        WHERE al.`user_type` != 'employee' AND e.`status` = 1" . $company_filter . "
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
$delivery_query = mysqli_query($conDB, "SELECT d.*, e.name as received_employee_name FROM general_request_deliveries d LEFT JOIN employees e ON e.emp_id = d.received_by WHERE d.request_inv_no = '$inv_no'" . (strpos($delivery_query, 'WHERE') !== false ? " AND " : " WHERE ") . $company_filter_delivery . $department_filter_delivery . " LIMIT 1");
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
    if ($approver['approver_id'] == $empid && $approver['status'] == 'pending') {
        $is_current_approver = true;
        $current_approver_level = $approver['approval_level'];
        break;
    }
}

// Check if user is any approver in the chain
foreach ($approval_chain as $approver) {
    if ($approver['approver_id'] == $empid) {
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
                $msg = '<div class="alert alert-danger bg-danger text-white border-0" role="alert">'.__('select_at_least_one_approver', 'Please select at least one approver.').'</div>';
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
                    echo json_encode(['status' => __('success'), 'message' => __('request_submitted_successfully', 'Request submitted successfully.')]);
                    exit;
                } else {
                    $msg = '<div class="alert alert-success bg-success text-white border-0" role="alert">'.__('request_submitted_successfully').'</div>';
                    $request['current_status'] = 'pending_approval';
                    $request['current_approval_level'] = 1;
                }
            } else {
                if ($is_ajax) {
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode(['status' => __('error'), 'message' => __('failed_to_save_approval_chain', 'Failed to save approval chain.')]);
                    exit;
                } else {
                    $msg = '<div class="alert alert-danger bg-danger text-white border-0" role="alert">'.__('failed_to_save_approval_chain').'</div>';
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
            // Set success message
            $action_text = ($status_action == 'approve') ? __('approved', 'Approved') : __('rejected', 'Rejected');
            $msg = '<div class="alert alert-success bg-success text-white border-0" role="alert">
                        <strong>'.$action_text.'!</strong> Request has been '.$action_text.' successfully.
                    </div>';
            // Get approver name
            $approver_details = getEmployeeDetails($conDB, $empid);
            $approver_name = $approver_details ? $approver_details['name'] : $userwel;
            
            // Get current approval level
            $current_level = 0;
            foreach ($approval_chain as $approver) {
                if ($approver['approver_id'] == $empid && $approver['status'] == 'pending') {
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
            
            // Refresh request data
            $request_query = mysqli_query($conDB, "SELECT gr.*, d.dep_nme FROM general_requests gr LEFT JOIN department d ON d.id = gr.user_dept WHERE gr.inv_no = '$inv_no' LIMIT 1");
            $request = mysqli_fetch_assoc($request_query);
            $is_current_approver = false;
        }
    }
}

// Priority classes
$priority_classes = [
    'low' => 'badge-info',
    'medium' => 'badge-primary',
    'high' => 'badge-warning',
    'urgent' => 'badge-danger'
];

$status_classes = [
    'draft' => 'badge-secondary',
    'pending_approval' => 'badge-warning',
    'approved' => 'badge-success',
    'rejected' => 'badge-danger'
];
?>
<!doctype html>
<html lang="<?= $current_lang ?? 'en' ?>" <?= ($is_rtl ?? false) ? 'dir="rtl"' : '' ?>>
<head>
    <meta charset="utf-8" />
    <title><?= $site_title ?> - <?=__('view_general_request', 'View General Request')?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta content="Anees Afzal" name="author" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <link rel="shortcut icon" href="<?=get_setting($conDB, 'favicon')?>">
    
    <link href="./plugins/custombox/css/custombox.min.css" rel="stylesheet">
    <link href="./plugins/select2/css/select2.min.css" rel="stylesheet" type="text/css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    
    <link href="assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/icons.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/metismenu.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/style.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/style_dark.css" rel="stylesheet" type="text/css" />
    <script src="assets/js/modernizr.min.js"></script>
    
    <?php if ($is_rtl): ?>
        <link href="assets/css/style_rtl.css" rel="stylesheet" type="text/css" />
    <?php endif; ?>
    <script> window.lang = <?= json_encode($GLOBALS['translations'] ?? []) ?>;</script>
    
    <style>
        /* Modern Layout Redesign */
        body {background-color: #f5f7fa;}

        
        /* Request Header Card */
        .request-header-card {background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);color: white;border-radius: 10px;padding: 30px;margin-bottom: 25px;box-shadow: 0 4px 20px rgba(0,0,0,0.1);}
        .request-header-card h3 {font-size: 28px;font-weight: 700;margin-bottom: 10px;}
        .request-meta {display: flex;flex-wrap: wrap;gap: 20px;margin-top: 20px;font-size: 0.95rem;}
        .request-meta-item {display: flex;align-items: center;gap: 10px;}
        .request-meta-icon {width: 40px;height: 40px;background: rgba(255,255,255,0.2);border-radius: 50%;display: flex;align-items: center;justify-content: center;}
        .badge-custom {display: inline-block;padding: 0.4rem 0.8rem;border-radius: 20px;font-weight: 600;font-size: 0.85rem;margin-right: 8px;}
        
        /* Two Column Layout */
        .main-content {display: grid;grid-template-columns: 2fr 1fr;gap: 25px;}
        @media (max-width: 1024px) {.main-content {grid-template-columns: 1fr;}}
        
        /* Content Cards */
        .content-card {background: white;border-radius: 10px;box-shadow: 0 2px 10px rgba(0,0,0,0.08);margin-bottom: 25px;overflow: hidden;}
        .card-header-custom {background: #f8f9fa;border-bottom: 2px solid #e3eaef;padding: 16px 20px;font-weight: 600;color: #2c3e50;display: flex;align-items: center;gap: 10px;}
        .card-header-custom i {font-size: 20px;color: #f8f9fa;}
        .card-body-custom {padding: 20px;}
        
        /* Details Grid */
        .details-grid {display: grid;grid-template-columns: repeat(2, 1fr);gap: 15px;margin-bottom: 15px;}
        .detail-item {padding: 12px;background: #f8f9fa;border-radius: 6px;border-left: 4px solid #667eea;}
        .detail-label {font-size: 0.85rem;color: #6c757d;font-weight: 600;text-transform: uppercase;margin-bottom: 4px;}
        .detail-value {font-size: 1rem;color: #2c3e50;font-weight: 500;}
        
        /* Items List */
        .items-list {display: grid;gap: 12px;}
        .item-row {display: grid;grid-template-columns: 30px 1fr 80px;gap: 15px;padding: 12px;background: #f8f9fa;border-radius: 6px;border-left: 4px solid #667eea;}
        .item-number {font-weight: 700;color: #667eea;text-align: center;}
        .item-info h6 {margin: 0;color: #2c3e50;font-weight: 600;}
        .item-info small {color: #6c757d;}
        .item-qty {text-align: center;font-weight: 600;color: #2c3e50;}
        
        /* Attachments */
        .attachments-grid {display: grid;grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));gap: 12px;}
        .attachment-item {padding: 12px;background: #f8f9fa;border-radius: 6px;text-align: center;cursor: pointer;transition: all 0.3s;}
        .attachment-item:hover {background: #e9ecef;transform: translateY(-2px);}
        
        /* Sidebar */
        .sidebar-section {background: white;border-radius: 10px;padding: 20px;margin-bottom: 20px;box-shadow: 0 2px 10px rgba(0,0,0,0.08);}
        .sidebar-title {font-size: 14px;font-weight: 700;color: #2c3e50;text-transform: uppercase;margin-bottom: 15px;padding-bottom: 10px;border-bottom: 2px solid #e3eaef;}
        
        /* Approval Section */
        .approval-card {border: 2px solid #667eea;border-radius: 10px;padding: 20px;background: linear-gradient(135deg, rgba(102,126,234,0.05) 0%, rgba(118,75,162,0.05) 100%);}
        .approval-title {font-weight: 700;color: #667eea;margin-bottom: 15px;display: flex;align-items: center;gap: 10px;}
        
        /* Timeline Redesign */
        .timeline-modern {position: relative;padding: 20px 0;}
        .timeline-item-modern {position: relative;margin-bottom: 20px;padding-left: 60px;}
        .timeline-icon-modern {position: absolute;left: 0;width: 40px;height: 40px;border-radius: 50%;display: flex;align-items: center;justify-content: center;font-weight: 700;color: white;}
        .timeline-icon-modern.pending {background: #ffbd4a;}
        .timeline-icon-modern.approved {background: #1abc9c;}
        .timeline-icon-modern.rejected {background: #f1556c;}
        .timeline-content {background: #f8f9fa;padding: 15px;border-radius: 6px;border-left: 4px solid #667eea;}
        
        /* Action Buttons */
        .action-buttons {display: flex;gap: 10px;flex-wrap: wrap;margin-top: 15px;}
        .btn-action {padding: 8px 16px;font-size: 0.9rem;border-radius: 6px;border: none;cursor: pointer;transition: all 0.3s;}
        
        /* Approver Tags */
        .approver-tag {display: flex;justify-content: space-between;align-items: center;padding: 8px 12px;background: #e9ecef;border: 1px solid #dee2e6;border-radius: 6px;margin-bottom: 8px;font-size: 0.9rem;}
        .approver-tag span {font-weight: 600;}
        .remove-approver-btn {cursor: pointer;color: #dc3545;font-weight: bold;background: none;border: none;padding: 0;font-size: 1.2rem;}
        
        /* Select2 Customization */
        .select2-container {width: 100% !important;}
        /* Keep Select2 and the "+ Add" button on one row inside input-group */
        .input-group .select2-container {flex: 1 1 auto; width: 1% !important; min-width: 0;}
        .input-group .select2-selection--single {height: calc(2.25rem + 2px);}
        .input-group .select2-selection__rendered {line-height: calc(2.25rem + 2px);}
        .input-group .select2-selection__arrow {height: calc(2.25rem + 2px);}
        .user-type-badge {font-size: 0.65em;font-weight: 700;padding: 0.2em 0.5em;border-radius: 10rem;color: #fff;background-color: #6c757d;margin-left: 5px;display: inline-block;line-height: 1.2;}
        .user-type-badge.administrator {background-color: #dc3545;}
        .user-type-badge.hr {background-color: #17a2b8;}
        .user-type-badge.gm {background-color: #007bff;}
        .user-type-badge.dept_user {background-color: #ffc107;color: #212529;}
        .user-type-badge.assistant {background-color: #28a745;}
        
        @media print {.no-print {display: none;}}
    </style>
    <script>
        window.departmentMap = <?= json_encode($department_map ?? []) ?>;
        window.lang = <?= json_encode($GLOBALS['translations'] ?? []) ?>;
    </script>
    <?php if ($is_rtl): ?>
        <link href="assets/css/style_rtl.css" rel="stylesheet" type="text/css" />
    <?php endif; ?>
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
            
            <div class="content">
                <div class="container-fluid">
                    
                    <!-- Request Header Card -->
                    <div class="profile-header no-print">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                            <div>
                                <h3><?= getDisplayName($request['request_title']) ?></h3>
                                <div style="margin-top: 10px;">
                                    <span class="badge-custom" style="background: rgba(255,255,255,0.2);">
                                        <?= __($request['current_status']) ?>
                                    </span>
                                    <span class="badge-custom" style="background: <?= ($priority_classes[$request['priority']] === 'badge-danger' ? '#dc3545' : ($priority_classes[$request['priority']] === 'badge-warning' ? '#ffbd4a' : '#667eea')) ?>;">
                                        <?= __(strtolower($request['priority'])).' '.__('priority') ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="request-meta">
                            <div class="request-meta-item">
                                <div class="request-meta-icon"><i class="mdi mdi-file-document"></i></div>
                                <div><small><?=__('request') ?> #</small><br><strong><?= htmlspecialchars($request['inv_no']) ?></strong></div>
                            </div>
                            <div class="request-meta-item">
                                <div class="request-meta-icon"><i class="mdi mdi-account"></i></div>
                                <div><small><?=__('requester') ?></small><br><strong><?= getDisplayName($request['emp_name']) ?></strong></div>
                            </div>
                            <div class="request-meta-item">
                                <div class="request-meta-icon"><i class="mdi mdi-calendar"></i></div>
                                <div><small><?=__('date') ?></small><br><strong><?= date('M d, Y', strtotime($request['created_at'])) ?></strong></div>
                            </div>
                            <div class="request-meta-item">
                                <div class="request-meta-icon"><i class="mdi mdi-folder"></i></div>
                                <div><small><?=__('target') ?></small><br><strong><?= getDisplayName($request['department_to']) ?></strong></div>
                            </div>
                        </div>
                    </div>

                    

                    <!-- Alert Messages Section -->
                    <?php if (!empty($msg)): ?>
                    <div id="messageContainer" style="margin-bottom: 20px;">
                        <?= $msg ?>
                    </div>
                    <script>
                        document.addEventListener('DOMContentLoaded', function() {
                            const messageContainer = document.getElementById('messageContainer');
                            if (messageContainer) {
                                Swal.fire({
                                    title: '<?= __("success", "Success") ?>',
                                    html: messageContainer.innerHTML,
                                    icon: 'success',
                                    confirmButtonText: '<?= __("ok", "OK") ?>',
                                    confirmButtonColor: '#1abc9c'
                                }).then((result) => {
                                    if (result.isConfirmed) {
                                        // Navigate with GET to avoid POST resubmission
                                        const url = window.location.pathname + window.location.search;
                                        window.location.replace(url);
                                    }
                                });
                            }
                        });
                    </script>
                    <?php endif; ?>

                    <!-- Two Column Layout -->
                    <div class="main-content">
                        <!-- Left Column - Main Content -->
                        <div>
                            <!-- Approver Action Section -->
                            <?php if ($is_current_approver): ?>
                            <div class="content-card no-print">
                                <div class="card-header-custom" style="background: linear-gradient(135deg, #f1556c 0%, #ee5a6f 100%); color: white;">
                                    <i class="mdi mdi-check-circle"></i>
                                    <?= __('your_action_required', 'Your Action Required') ?> 
                                </div>
                                <div class="card-body-custom">
                                    <form method="POST" id="approvalActionForm" action="">
                                        <input type="hidden" name="inv_no" value="<?= htmlspecialchars($inv_no) ?>">
                                        <div class="action-buttons">
                                            <button type="button" id="approveBtn" class="btn btn-action" style="background: #1abc9c; color: white;">
                                                <i class="mdi mdi-check-circle"></i> <?= __('approve', 'Approve') ?>
                                            </button>
                                            <button type="button" id="rejectBtn" class="btn btn-action" style="background: #f1556c; color: white;">
                                                <i class="mdi mdi-close-circle"></i> <?= __('reject', 'Reject') ?>
                                            </button>
                                        </div>
                                        <input type="hidden" name="note" id="approvalNote" value="">
                                        <input type="hidden" name="status" id="statusInput" value="">
                                        <input type="hidden" name="action" id="actionInput" value="">
                                    </form>
                                </div>
                            </div>
                            <?php endif; ?>

                            <?php if ($request['current_status'] === 'completed'): ?>
                            <!-- INLINE: Delivery details when completed -->
                            <div class="content-card" style="background-color: #e3f2fd; border-left: 4px solid #2196F3;">
                                <div class="card-header-custom" style="color: #2196F3;">
                                    <i class="mdi mdi-check-all"></i>
                                    <?= __('delivery_completed', 'Delivery Completed') ?>
                                </div>
                                <div class="card-body-custom">
                                    <div style="margin-bottom: 15px; padding: 12px; background-color: #eaf4ff; border-radius: 6px; border-left: 4px solid #2196F3;">
                                        <div style="font-weight: 600; color: #1976d2; margin-bottom: 6px;">
                                            <i class="mdi mdi-truck-delivery"></i> <?= __('delivery_details', 'Delivery Details') ?>
                                        </div>
                                        <div style="font-size: 14px; color: #1565c0;">
                                            <strong><?= __('received_by', 'Received By') ?>:</strong>
                                            <?= getDisplayName($delivery_info['received_employee_name']) ?>
                                        </div>
                                        <div style="font-size: 14px; color: #1565c0;">
                                            <strong><?= __('date', 'Date') ?>:</strong>
                                            <?= isset($delivery_info['delivery_date']) && $delivery_info['delivery_date'] ? date('M d, Y H:i', strtotime($delivery_info['delivery_date'])) : 'N/A' ?>
                                        </div>
                                    </div>

                                    <div style="margin-bottom: 8px; font-weight: 600; color: #2c3e50;">
                                        📦 <?= __('delivered_items', 'Delivered Items') ?>
                                    </div>
                                    <div style="border: 1px solid #dee2e6; border-radius: 6px; padding: 12px; background-color: #f8f9fa;">
                                        <?php foreach ($items as $it): ?>
                                            <?php
                                                $status = $it['delivery_status'] ?? 'pending';
                                                $statusIcon = $status === 'delivered' ? 'mdi-check-circle' : ($status === 'pending' ? 'mdi-clock-outline' : 'mdi-close-circle');
                                                $statusColor = $status === 'delivered' ? '#28a745' : ($status === 'pending' ? '#ffc107' : '#dc3545');
                                            ?>
                                            <div style="padding: 12px; background-color: #fff; border-radius: 4px; margin-bottom: 10px; border-left: 4px solid <?= $statusColor ?>;">
                                                <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 12px;">
                                                    <div>
                                                        <div style="font-weight: 500; color: #2c3e50;">
                                                            <?= getDisplayName($it['item_name']) ?>
                                                        </div>
                                                        <small style="color: #6c757d; display: inline-block; margin-top: 2px;">
                                                            <?= __('quantity', 'Quantity') ?>: <?= (int)$it['quantity'] ?>
                                                        </small>
                                                    </div>
                                                    <div style="display: flex; align-items: center; gap: 6px; font-weight: 600; color: <?= $statusColor ?>; white-space: nowrap;">
                                                        <i class="mdi <?= $statusIcon ?>"></i> <?= __($status) ?>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>

                            <!-- Requested Items -->
                            <div class="content-card">
                                <div class="card-header-custom">
                                    <i class="mdi mdi-format-list-bulleted"></i>
                                    <?= __('requested_items', 'Requested Items') ?> (<?= count($items) ?>)
                                </div>
                                <div class="card-body-custom">
                                    <div class="items-list">
                                        <?php foreach ($items as $index => $item): ?>
                                        <div class="item-row" style="display: flex; justify-content: space-between; align-items: center; padding: 15px; border: 1px solid #e9ecef; border-radius: 6px; margin-bottom: 12px; background-color: #f8f9fa;">
                                            <div style="flex: 1;">
                                                <div style="display: flex; align-items: center; gap: 12px;">
                                                    <div class="item-number" style="width: 32px; height: 32px; background-color: #667eea; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 600;"><?= $index + 1 ?></div>
                                                    <div class="item-info">
                                                        <h6 style="margin: 0 0 5px 0; font-weight: 600; color: #2c3e50;">
                                                            <?= getDisplayName($item['item_name'])  ?>
                                                            <!-- Delivery Status Badge for Completed Requests -->
                                                            <?php if ($request['current_status'] === 'completed'): ?>
                                                                <?php 
                                                                    $status = $item['delivery_status'] ?? 'pending';
                                                                    $badge_color = $status === 'delivered' ? 'success' : ($status === 'pending' ? 'warning' : 'danger');
                                                                    $icon = $status === 'delivered' ? 'check-circle' : ($status === 'pending' ? 'clock-outline' : 'close-circle');
                                                                ?>
                                                                <span class="badge badge-<?= $badge_color ?>" style="padding: 4px 8px; font-size: 0.75rem; margin-left: 8px;">
                                                                    <i class="mdi mdi-<?= $icon ?>" style="font-size: 12px;"></i> <?= __($status) ?>
                                                                </span>
                                                            <?php endif; ?>
                                                        </h6>
                                                        <?php if (!empty($item['item_type'])): ?>
                                                            <small style="color: #6c757d;"><?=__('type')?>: <?= getDisplayName($item['item_type']) ?></small>
                                                        <?php endif; ?>
                                                        <?php if (!empty($item['specifications'])): ?>
                                                            <small style="display: block; margin-top: 4px; color: #6c757d;"><?=__('specs', 'Specs')?>: <?= getDisplayName($item['specifications']) ?></small>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                            <div style="display: flex; align-items: center; gap: 15px; margin-left: 20px;">
                                                <div style="background-color: white; padding: 8px 12px; border-radius: 4px; border: 1px solid #dee2e6; font-weight: 600; color: #667eea; min-width: 60px; text-align: center;">x<?= $item['quantity'] ?></div>
                                                <?php if ($can_modify_items): ?>
                                                <button type="button" class="btn-item-edit" onclick="editItemQuantity(<?= $item['id'] ?>, '<?= htmlspecialchars($item['item_name']) ?>', <?= $item['quantity'] ?>)" style="padding: 6px 10px; background-color: #17a2b8; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 12px;">
                                                    <i class="mdi mdi-pencil"></i> <?= __('edit_qty', 'Edit Qty') ?>
                                                </button>
                                                <button type="button" class="btn-item-delete deleteAjax" data-id='<?=$item['id']?>' data-tbl='general_request_items' data-file='0' style="padding: 6px 10px; background-color: #dc3545; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 12px;">
                                                    <i class="mdi mdi-delete"></i> <?= __('remove', 'Remove') ?>
                                                </button>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                    
                                    <!-- Add Item Button (Draft Status + No Approvers Selected) -->
                                    <?php if ($request['current_status'] == 'draft' && $is_creator): ?>
                                    <div style="margin-top: 20px; padding-top: 20px; border-top: 2px solid #e9ecef;">
                                        <button type="button" id="addItemBtn" class="btn btn-success" style="width: 100%; padding: 12px;">
                                            <i class="mdi mdi-plus-circle"></i> <?= __('add_new_item', 'Add New Item') ?>
                                        </button>
                                        <small class="form-text text-muted" style="display: block; margin-top: 8px;"><?= __('add_more_items_to_this_request_while_its_in_draft_status', "Add more items to this request while it's in draft status") ?></small>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Attachments -->
                            <?php if (!empty($attachments)): ?>
                            <div class="content-card">
                                <div class="card-header-custom">
                                    <i class="mdi mdi-paperclip"></i>
                                    <?= __('attachments', 'Attachments') ?> (<?= count($attachments) ?>)
                                </div>
                                <div class="card-body-custom">
                                    <div class="attachments-grid">
                                        <?php foreach ($attachments as $attachment): ?>
                                        <?php 
                                            // Determine correct folder based on attachment type
                                            $attachmentType = $attachment['attachment_type'] ?? 'request';
                                            $folderPath = ($attachmentType === 'delivery') ? 'assets/delivery_attachments/' : 'assets/general_request_attachments/';
                                            $fullPath = $folderPath . htmlspecialchars($attachment['attachment']);
                                            
                                            // Set styling based on type
                                            if ($attachmentType === 'delivery') {
                                                $bgColor = '#e8f5e9';
                                                $borderColor = '#4caf50';
                                                $iconColor = '#4caf50';
                                                $badge = '<span style="display: inline-block; background-color: #4caf50; color: white; padding: 2px 8px; border-radius: 10px; font-size: 10px; font-weight: 600; margin-top: 4px;">📦 ' . __('delivery', 'Delivery') . '</span>';
                                            } else {
                                                $bgColor = '#e3f2fd';
                                                $borderColor = '#2196f3';
                                                $iconColor = '#2196f3';
                                                $badge = '<span style="display: inline-block; background-color: #2196f3; color: white; padding: 2px 8px; border-radius: 10px; font-size: 10px; font-weight: 600; margin-top: 4px;">📋 ' . __('request', 'Request') . '</span>';
                                            }
                                        ?>
                                        <a href="<?= $fullPath ?>" target="_blank" class="attachment-item" title="<?= htmlspecialchars($attachment['attachment']) ?>" style="background-color: <?= $bgColor ?>; border: 2px solid <?= $borderColor ?>; padding: 12px; border-radius: 8px; text-align: center; transition: all 0.3s; text-decoration: none; display: flex; flex-direction: column; align-items: center; justify-content: center;">
                                            <div><i class="mdi mdi-file-document" style="font-size: 32px; color: <?= $iconColor ?>;"></i></div>
                                            <small style="color: #424242; font-weight: 500; margin-top: 4px;"><?= substr($attachment['docu_ext'], 0, 10) ?></small>
                                            <?= $badge ?>
                                        </a>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>

                            <!-- Delivery Section -->
                            <?php if ($request['current_status'] === 'approved'): ?>
                            <!-- BUTTON: Show Deliver button when approved -->
                            <div class="content-card" style="background-color: #e8f5e9; border-left: 4px solid #28a745;">
                                <div class="card-header-custom" style="color: #28a745;">
                                    <i class="mdi mdi-truck-delivery"></i>
                                    <?= __('ready_for_delivery', 'Ready for Delivery') ?>
                                </div>
                                <div class="card-body-custom">
                                    <p style="color: #28a745; margin-bottom: 15px; font-weight: 500;">
                                        <i class="mdi mdi-information-outline"></i>
                                        <?= __('this_request_is_approved_click_below_to_mark_items_as_delivered') ?>
                                    </p>
                                    <button type="button" class="btn btn-success" onclick="showDeliveryModal()" style="width: 100%; padding: 12px; font-size: 16px;">
                                        <i class="mdi mdi-truck-delivery"></i> <?= __('deliver_items', 'Deliver Items') ?>
                                    </button>
                                </div>
                            </div>

                            <?php elseif ($request['current_status'] === 'waiting_for_delivery'): ?>
                            <!-- BUTTON: Show Deliver button when waiting for delivery -->
                            <div class="content-card" style="background-color: #fff3e0; border-left: 4px solid #ff9800;">
                                <div class="card-header-custom" style="color: #ff9800;">
                                    <i class="mdi mdi-clock-outline"></i>
                                    <?= __('waiting_for_delivery', 'Waiting for Delivery') ?>
                                </div>
                                <div class="card-body-custom">
                                    <p style="color: #e65100; margin-bottom: 15px; font-weight: 500;">
                                        <i class="mdi mdi-information-outline"></i>
                                        <?= __('approval_complete_mark_items_as_delivered_to_finalize_this_request', 'Approval complete. Mark items as delivered to finalize this request.') ?>
                                    </p>
                                    <button type="button" class="btn btn-warning" onclick="showDeliveryModal()" style="width: 100%; padding: 12px; font-size: 16px; color: #fff;">
                                        <i class="mdi mdi-truck-delivery"></i> <?= __('deliver_items', 'Deliver Items') ?>
                                    </button>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>


                        
                        <!-- Right Column - Sidebar -->
                        <div>
                            <!-- Quick Info Card -->
                            <div class="sidebar-section">
                                <!-- Approval Section (if draft) -->
                                <?php if ($request['current_status'] == 'draft' && $is_creator): ?>
                                <div class="content-card no-print">
                                    <div class="card-header-custom">
                                        <i class="fa fa-user-check text-dark"></i>
                                        <?=__('setup_approval_chain', 'Setup Approval Chain')?>
                                    </div>
                                    <div class="card-body-custom">
                                        <form method="POST" id="approvalForm" data-parsley-validate>
                                            <input type="hidden" name="inv_no" value="<?= htmlspecialchars($inv_no) ?>">
                                            
                                            <div class="form-group mb-3">
                                                <label style="font-weight: 600;"><?=__('select_approvers_in_order', 'Select approvers in order')?> <span class="text-danger">*</span></label>
                                                <div class="input-group">
                                                    <select id="approver-select" class="form-control" data-placeholder="<?=__('select_an_approver', 'Select an approver')?>">
                                                        <option value=""></option>
                                                        <?php foreach ($potential_approvers as $approver): ?>
                                                            <option value="<?= $approver['emp_id'] ?>" data-type="<?= ($approver['user_type']) ?>" data-dept="<?= $approver['dept'] ?>">
                                                                <?= getDisplayName($approver['name']) ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                    <div class="input-group-append">
                                                        <button type="button" id="add-approver-btn" class="btn btn-primary">
                                                            <i class="mdi mdi-plus"></i> <?= __('add', 'Add') ?>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="form-group mb-3">
                                                <label style="font-weight: 600;"><?= __('approval_chain', 'Approval Chain') ?></label>
                                                <div id="approver-list-container" style="max-height: 250px; overflow-y: auto; border: 1px solid #e3eaef; padding: 10px; border-radius: 6px; background: #f8f9fa;"></div>
                                                <input type="hidden" id="min-approver-check" data-parsley-required="true" data-parsley-errors-container="#approver-error-container" />
                                                <div id="approver-error-container" class="small" style="margin-top: 8px;"></div>
                                                <small class="form-text text-muted" style="margin-top: 8px;"><?= __('approvers_will_be_notified_in_the_order_listed_above', 'Approvers will be notified in the order listed above') ?></small>
                                            </div>
                                            
                                            <button type="submit" name="action" value="submit" class="btn btn-primary btn-block" style="margin-top: 15px;">
                                                <i class="mdi mdi-send"></i> <?=__('submit_for_approval', 'Submit for Approval')?>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                                <?php endif; ?>
                                <div class="sidebar-title" style="margin-bottom: 8px;"><?= __('quick_info', 'Quick Info') ?></div>
                                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 8px;">
                                        <div style="padding: 6px; background: #f8f9fa; border-radius: 4px;">
                                            <small style="color: #6c757d; font-weight: 600; font-size: 0.75rem; display: block;"><?= __('request_id', 'ID') ?></small>
                                            <div style="color: #2c3e50; font-weight: 600; font-size: 0.85rem; word-break: break-all;"><?= htmlspecialchars($request['inv_no']) ?></div>
                                        </div>
                                        <div style="padding: 6px; background: #f8f9fa; border-radius: 4px;">
                                            <small style="color: #6c757d; font-weight: 600; font-size: 0.75rem; display: block;"><?= __('priority', 'Priority') ?></small>
                                            <span class="priority-badge <?= 'priority-' . $request['priority'] ?>" style="<?= $priority_classes[$request['priority']] === 'badge-danger' ? 'background: #dc3545;' : ($priority_classes[$request['priority']] === 'badge-warning' ? 'background: #ffbd4a;' : 'background: #667eea;') ?> color: white; padding: 2px 6px; border-radius: 3px; font-size: 0.7rem; display: inline-block; font-weight: 600;">
                                                <?= __($request['priority']) ?>
                                            </span>
                                        </div>
                                        <div style="padding: 6px; background: #f8f9fa; border-radius: 4px;">
                                            <small style="color: #6c757d; font-weight: 600; font-size: 0.75rem; display: block;"><?= __('status', 'Status') ?></small>
                                            <?php 
                                                $statusColors = [
                                                    'draft' => ['bg' => '#6c757d', 'icon' => 'pencil', 'label' => __('draft')],
                                                    'pending_approval' => ['bg' => '#ffc107', 'icon' => 'clock-outline', 'label' => __('pending_approval')],
                                                    'approved' => ['bg' => '#28a745', 'icon' => 'check-circle', 'label' => __('approved')],
                                                    'rejected' => ['bg' => '#dc3545', 'icon' => 'close-circle', 'label' => __('rejected')],
                                                    'waiting_for_delivery' => ['bg' => '#ff9800', 'icon' => 'truck-delivery', 'label' => __('waiting_for_delivery')],
                                                    'completed' => ['bg' => '#17a2b8', 'icon' => 'check-all', 'label' => __('completed')]
                                                ];
                                                $currentStatus = $request['current_status'];
                                                $statusInfo = $statusColors[$currentStatus] ?? ['bg' => '#6c757d', 'icon' => 'help-circle', 'label' => $currentStatus];
                                            ?>
                                            <span class="badge" style="background-color: <?= $statusInfo['bg'] ?>; color: white; padding: 6px; font-size: 0.7rem; display: inline-flex; align-items: center; gap: 3px; font-weight: 600;">
                                                <i class="mdi mdi-<?= $statusInfo['icon'] ?>" style="font-size: 10px;"></i>
                                                <?= $statusInfo['label'] ?>
                                            </span>
                                        </div>
                                        <div style="padding: 6px; background: #f8f9fa; border-radius: 4px;">
                                            <small style="color: #6c757d; font-weight: 600; font-size: 0.75rem; display: block;"><?= __('created', 'Created') ?></small>
                                            <div style="color: #2c3e50; font-size: 0.8rem;"><?= date('M d, Y', strtotime($request['created_at'])) ?></div>
                                        </div>
                                    </div>
                                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
                                        <div style="padding: 6px; background: #f8f9fa; border-radius: 4px;">
                                            <small style="color: #6c757d; font-weight: 600; font-size: 0.75rem; display: block;"><?= __('category', 'Category') ?></small>
                                            <div style="color: #2c3e50; font-size: 0.8rem;"><?= getDisplayName($request['request_category']) ?></div>
                                        </div>
                                        <div style="padding: 6px; background: #f8f9fa; border-radius: 4px;">
                                            <small style="color: #6c757d; font-weight: 600; font-size: 0.75rem; display: block;"><?= __('department', 'Department') ?></small>
                                            <div style="color: #2c3e50; font-size: 0.8rem;"><?= getDisplayName($request['dep_nme']) ?></div>
                                        </div>
                                    </div>
                                    <div style="padding: 6px; background: #f8f9fa; border-radius: 4px; margin-top: 8px;">
                                        <small style="color: #6c757d; font-weight: 600; font-size: 0.75rem; display: block;"><?= __('requester', 'Requester') ?></small>
                                        <div style="color: #2c3e50; font-size: 0.8rem;"><?= getDisplayName($request['emp_name']) ?></div>
                                    </div>
                                    <?php if (!empty($request['description'])): ?>
                                    <div style="padding: 6px; background: #f8f9fa; border-radius: 4px; margin-top: 8px;">
                                        <small style="color: #6c757d; font-weight: 600; font-size: 0.75rem; display: block;"><?= __('description', 'Description') ?></small>
                                        <div style="color: #2c3e50; font-size: 0.8rem; line-height: 1.2;"><?= getDisplayName($request['description']) ?></div>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <!-- Approval Summary Card -->
                                <?php if (!empty($approval_chain)): ?>
                                <div class="sidebar-section">
                                    <div class="sidebar-title"><?= __('approval_status', 'Approval Status') ?></div>
                                    <div>
                                        <?php $pending_count = 0; $approved_count = 0; $rejected_count = 0; ?>
                                        <?php foreach ($approval_chain as $approver): ?>
                                            <?php if ($approver['status'] == 'pending') $pending_count++; ?>
                                            <?php if ($approver['status'] == 'approved') $approved_count++; ?>
                                            <?php if ($approver['status'] == 'rejected') $rejected_count++; ?>
                                        <?php endforeach; ?>
                                        <div style="margin-bottom: 12px; padding: 10px; background: #f8f9fa; border-radius: 6px;">
                                            <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                                                <span style="font-weight: 600;"><?= __('progress', 'Progress') ?></span>
                                                <span style="color: #667eea; font-weight: 600;"><?= $approved_count ?>/<?= count($approval_chain) ?></span>
                                            </div>
                                            <div style="background: #e3eaef; border-radius: 3px; height: 8px; overflow: hidden;">
                                                <div style="background: #1abc9c; height: 100%; width: <?= (count($approval_chain) > 0 ? ($approved_count / count($approval_chain) * 100) : 0) ?>%;"></div>
                                            </div>
                                        </div>
                                        <div style="padding: 8px 10px; background: #e7f3ff; border-left: 3px solid #667eea; border-radius: 4px; margin-bottom: 8px;">
                                            <small><strong><?= __('pending', 'Pending') ?>:</strong> <?= $pending_count ?></small>
                                        </div>
                                        <div style="padding: 8px 10px; background: #e6ffed; border-left: 3px solid #1abc9c; border-radius: 4px; margin-bottom: 8px;">
                                            <small><strong><?= __('approved', 'Approved') ?>:</strong> <?= $approved_count ?></small>
                                        </div>
                                        <?php if ($rejected_count > 0): ?>
                                        <div style="padding: 8px 10px; background: #ffe6e6; border-left: 3px solid #f1556c; border-radius: 4px;">
                                            <small><strong><?= __('rejected', 'Rejected') ?>:</strong> <?= $rejected_count ?></small>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php endif; ?>
                                <!-- Approval Timeline -->
                                <?php if (!empty($approval_chain)): ?>
                                <div class="content-card">
                                    <div class="card-header-custom">
                                        <i class="mdi mdi-timeline-check"></i>
                                        <?= __('approval_timeline', 'Approval Timeline') ?>
                                    </div>
                                    <div class="card-body-custom">
                                        <div class="timeline-modern">
                                            <?php 
                                                // Check if request was rejected
                                                $is_request_rejected = isset($request['current_status']) && $request['current_status'] === 'rejected';
                                            ?>
                                            <?php foreach ($approval_chain as $approver): 
                                                // If request is rejected, skip pending/awaiting approvers
                                                if ($is_request_rejected && in_array($approver['status'], ['pending', 'awaiting'])) {
                                                    continue;
                                                }
                                            ?>
                                            <div class="timeline-item-modern">
                                                <div class="timeline-icon-modern <?= $approver['status'] ?>">
                                                    <i class="mdi mdi-<?= $approver['status'] == 'approved' ? 'check' : ($approver['status'] == 'rejected' ? 'close' : 'clock-outline') ?>"></i>
                                                </div>
                                                <div class="timeline-content">
                                                    <h6 style="margin: 0 0 5px 0; color: #2c3e50;"><?= __('level', 'Level') ?> <?= $approver['approval_level'] ?>: <?=  getDisplayName($approver['approver_name']) ?></h6>
                                                    <small style="color: #6c757d;"><?= __('status', 'Status') ?>: <strong><?= __(strtolower($approver['status'])) ?></strong></small>
                                                    <?php if ($approver['action_date']): ?>
                                                        <small style="display: block; color: #6c757d; margin-top: 5px;"><?= date('M d, Y H:i', strtotime($approver['action_date'])) ?></small>
                                                    <?php endif; ?>
                                                    <?php if (!empty($approver['note'])): ?>
                                                        <p style="margin: 8px 0 0 0; color: #6c757d; font-size: 0.9rem;"><strong><?= __('note', 'Note') ?>:</strong> <?= htmlspecialchars($approver['note']) ?></p>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <?php endforeach; ?>
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
    <script type="text/javascript" src="./plugins/parsleyjs/parsley.min.js"></script>
    <script src="./plugins/select2/js/select2.min.js"></script>
    <script src="assets/js/jquery.core.js"></script>
    <script src="assets/js/jquery.app.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        $(document).ready(function() {
            // Initialize Parsley
            if ($('#approvalForm').length) {
                $('#approvalForm').parsley();
            }
            
            // Helper function to get role text
            function getRoleText(userType) {
                if (!userType) return '';
                let role = userType.toLowerCase();
                switch (role) {
                    case 'dept_user': return 'manager';
                    case 'assistant': return 'assistant';
                    case 'gm': return 'general_manager';
                    case 'hr': return 'hr';
                    case 'administrator': return 'administrator';
                    default: return role.charAt(0).toUpperCase() + role.slice(1);
                }
            }

            function formatApprover(approver) {
                if (!approver.id) { return approver.text; }
                var $element = $(approver.element);
                var userType = $element.data('type') || '';
                var deptId = $element.data('dept') || '';
                var deptName = window.departmentMap[deptId] || '';
                let roleText = getRoleText(userType);
                var badgeText = deptName ? `${deptName} ${roleText}` : roleText;
                var badgeHtml = badgeText ? '<span class="user-type-badge ' + userType + ' select2-results__option .user-type-badge">' + badgeText + '</span>' : '';
                var $approver = $('<span class="select2-option-text">' + approver.text + '</span>' + badgeHtml);
                return $approver;
            }

            function formatApproverSelection(approver) {
                if (!approver.id) { return approver.text; }
                var $element = $(approver.element);
                var userType = $element.data('type') || '';
                var deptId = $element.data('dept') || '';
                var deptName = window.departmentMap[deptId] || '';
                let roleText = getRoleText(userType);
                var badgeText = deptName ? `${deptName} ${roleText}` : roleText;
                var badgeHtml = badgeText ? '<span class="user-type-badge ' + userType + ' select2-selection__rendered-badge">' + badgeText + '</span>' : '';
                var $approver = $('<span class="select2-selection-text">' + approver.text + '</span>' + badgeHtml);
                return $approver;
            }

            $('#approver-select').select2({
                placeholder: $(this).data('placeholder'),
                allowClear: true,
                templateResult: formatApprover,
                templateSelection: formatApproverSelection
            });

            let approverCount = 0;

            function updateValidation() {
                const parsleyInstance = $('#min-approver-check').parsley();
                if (!parsleyInstance) return;
                if (approverCount > 0) {
                    $('#min-approver-check').val('ok');
                } else {
                    $('#min-approver-check').val('');
                }
                parsleyInstance.validate();
            }

            $('#add-approver-btn').on('click', function() {
                const selectedApprover = $('#approver-select').find('option:selected');
                const approverId = selectedApprover.val();
                const approverName = selectedApprover.text();

                if (!approverId) {
                    Swal.fire({
                        title: '<?=__('error')?>', 
                        text: '<?=__('select_approver_from_list', 'Please select an approver from the list')?>', 
                        icon: 'warning',  
                        confirmButtonText: '<?=__('ok')?>', 
                        allowOutsideClick: false
                    });
                    return;
                }

                let alreadyAdded = false;
                $('#approver-list-container').find('input[name="approvers[]"]').each(function() {
                    if ($(this).val() == approverId) {
                        alreadyAdded = true;
                        return false;
                    }
                });

                if (alreadyAdded) {
                    Swal.fire({
                        title: '<?=__('error')?>', 
                        text: '<?=__('approver_already_added', 'This approver has already been added')?>', 
                        icon: 'warning', 
                        confirmButtonText: '<?=__('ok')?>', 
                        allowOutsideClick: false
                    });
                    return;
                }

                approverCount++;
                const approverLevel = approverCount;
                const tagHtml = `
                    <div class="approver-tag" data-id="${approverId}">
                        <span>${approverLevel}. ${approverName}</span>
                        <input type="hidden" name="approvers[]" value="${approverId}" />
                        <button type="button" class="remove-approver-btn" aria-label="Remove">&times;</button>
                    </div>
                `;
                $('#approver-list-container').append(tagHtml);
                $('#approver-select').val(null).trigger('change');
                updateValidation();
                
                // Disable "Add Item" button when approvers are selected
                if (approverCount > 0) {
                    $('#addItemBtn').prop('disabled', true).css('opacity', '0.5').attr('title', 'Remove approvers to add more items');
                }
            });

            // AJAX submit for approval chain (draft -> pending_approval)
            $('#approvalForm').on('submit', function(e) {
                e.preventDefault();
                var $form = $(this);
                var approverCount = $form.find('input[name="approvers[]"]').length;
                if (approverCount === 0) {
                    Swal.fire({
                        title: __('approver_required', 'Approver R  equired'),
                        text: __('please_select_at_least_one_approver_from_the_list', 'Please select at least one approver from the list.'),
                        icon: 'error',
                        confirmButtonText: __('ok', 'OK'),
                        allowOutsideClick: false
                    });
                    return;
                }

                const params = new URLSearchParams($form.serialize());
                params.set('action', 'submit');
                params.set('ajax', '1');

                Swal.fire({
                    title: __('submitting', 'Submitting...'),
                    html: '<div class="spinner-border text-primary" role="status"><span class="sr-only">' + __('loading', 'Loading...') + '</span></div>',
                    allowOutsideClick: false,
                    showConfirmButton: false,
                    didOpen: () => Swal.showLoading()
                });

                fetch(window.location.href, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: params.toString()
                })
                .then(async (response) => {
                    const text = await response.text();
                    try { return JSON.parse(text); } catch (e) { throw new Error(text || __('invalid_response', 'Invalid response')); }
                })
                .then((data) => {
                    if (data.status === 'success') {
                        Swal.fire({
                            title: 'Submitted', 
                            text: data.message || __('success_record_submitted', 'Record submitted successfully.'), 
                            icon: 'success',
                            allowOutsideClick: false,
                            confirmButtonText: __('ok', 'OK')
                        })
                            .then(() => {
                                const url = window.location.pathname + window.location.search;
                                window.location.replace(url);
                            });
                    } else {
                        Swal.fire({
                            title: 'Error', 
                            text: data.message || __('submission_failed', 'Submission failed.'), 
                            icon: 'error',
                            confirmButtonText: __('ok', 'OK'),
                            allowOutsideClick: false
                        });
                    }
                })
                .catch((err) => {
                    const msg = (err && err.message) ? err.message.substring(0, 500) : __('submission_failed', 'Submission failed.');
                    Swal.fire({
                        title: 'Error', 
                        text: msg, 
                        icon: 'error',
                        confirmButtonText: __('ok', 'OK'),
                        allowOutsideClick: false
                    });
                    console.error(err);
                });
            });

            $(document).on('click', '.remove-approver-btn', function() {
                $(this).closest('.approver-tag').remove();
                approverCount = 0;
                $('#approver-list-container').find('.approver-tag').each(function() {
                    approverCount++;
                    const currentSpan = $(this).find('span');
                    const nameParts = currentSpan.text().split('. ');
                    const approverName = nameParts.length > 1 ? nameParts.slice(1).join('. ') : currentSpan.text();
                    currentSpan.text(`${approverCount}. ${approverName}`);
                });
                updateValidation();
                
                // Re-enable "Add Item" button when all approvers are removed
                if (approverCount === 0) {
                    $('#addItemBtn').prop('disabled', false).css('opacity', '1').removeAttr('title');
                }
            });

            if ($('#min-approver-check').length) {
                updateValidation();
            }
            
            // Handle approve/reject button clicks with SweetAlert2
            $('#approveBtn').on('click', function(e) {
                e.preventDefault();
                
                Swal.fire({
                    title: '<?= __("confirm_approval", "Confirm Approval") ?>',
                    html: `<?= __("are_you_sure_approve", "Are you sure you want to approve this request?") ?><br><br><label style="font-weight: 600; display: block; text-align: left; margin-bottom: 8px;">${__('add_a_note_optional', 'Add a Note (Optional)')}</label><textarea id="swalNoteInput" class="swal2-textarea" placeholder="${__('enter_your_approval_note', 'Enter your approval note...')}" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-family: Arial, sans-serif; font-size: 14px; resize: vertical; min-height: 80px;"></textarea>`, 
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: APP_COLORS.success,
                    cancelButtonColor: APP_COLORS.secondary,
                    confirmButtonText: '<?= __("yes_approve_it", "Yes, Approve") ?>',
                    cancelButtonText: '<?= __("cancel", "Cancel") ?>',
                    allowOutsideClick: false,
                    didOpen: (modal) => {
                        const textarea = modal.querySelector('#swalNoteInput');
                        if (textarea) {
                            setTimeout(() => textarea.focus(), 100);
                        }
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Get note value from the textarea in the modal BEFORE it closes
                        const noteTextarea = document.querySelector('#swalNoteInput');
                        const note = noteTextarea ? noteTextarea.value.trim() : '';
                        
                        // Show loading
                        Swal.fire({
                            title: '<?= __("processing", "Processing") ?>',
                            html: `<div class="spinner-border text-success" role="status"><span class="sr-only">${__('loading', 'Loading...')}</span></div><p style="margin-top: 15px;">${__('processing_your_approval', 'Processing your approval...')}</p>`,
                            icon: 'info',
                            allowOutsideClick: false,
                            allowEscapeKey: false,
                            showConfirmButton: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });
                        
                        // Set form values
                        document.getElementById('approvalNote').value = note;
                        document.getElementById('statusInput').value = 'approve';
                        document.getElementById('actionInput').value = 'submit';
                        
                        // Submit form using native submit
                        setTimeout(() => {
                            document.getElementById('approvalActionForm').submit();
                        }, 300);
                    }
                });
            });
            
            $('#rejectBtn').on('click', function(e) {
                e.preventDefault();
                
                Swal.fire({
                    title: '<?= __("confirm_rejection", "Confirm Rejection") ?>',
                    html: `<?= __("are_you_sure_reject", "Are you sure you want to reject this request?") ?><br><br><label style="font-weight: 600; display: block; text-align: left; margin-bottom: 8px;">${__('add_a_note_optional', 'Add a Note (Optional)')}</label><textarea id="swalNoteInput" class="swal2-textarea" placeholder="${__('enter_your_rejection_note', 'Enter your rejection note...')}" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-family: Arial, sans-serif; font-size: 14px; resize: vertical; min-height: 80px;"></textarea>`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: APP_COLORS.danger,
                    cancelButtonColor: APP_COLORS.secondary,
                    confirmButtonText: '<?= __("yes_reject", "Yes, Reject") ?>',
                    cancelButtonText: '<?= __("cancel", "Cancel") ?>',
                    allowOutsideClick: false,
                    didOpen: (modal) => {
                        const textarea = modal.querySelector('#swalNoteInput');
                        if (textarea) {
                            setTimeout(() => textarea.focus(), 100);
                        }
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Get note value from the textarea in the modal BEFORE it closes
                        const noteTextarea = document.querySelector('#swalNoteInput');
                        const note = noteTextarea ? noteTextarea.value.trim() : '';
                        
                        // Show loading
                        Swal.fire({
                            title: '<?= __("processing", "Processing") ?>',
                            html: `<div class="spinner-border text-danger" role="status"><span class="sr-only">${__('loading', 'Loading...')}</span></div><p style="margin-top: 15px;">${__('processing_your_rejection', 'Processing your rejection...')}</p>`,
                            icon: 'info',
                            allowOutsideClick: false,
                            allowEscapeKey: false,
                            showConfirmButton: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });
                        
                        // Set form values
                        document.getElementById('approvalNote').value = note;
                        document.getElementById('statusInput').value = 'reject';
                        document.getElementById('actionInput').value = 'submit';
                        
                        // Submit form using native submit
                        setTimeout(() => {
                            document.getElementById('approvalActionForm').submit();
                        }, 300);
                    }
                });
            });
            
            // Function to edit item quantity
            window.editItemQuantity = function(itemId, itemName, currentQty) {
                Swal.fire({
                    title: __('edit_item_quantity'),
                    html: __('item') + ': <strong>' + escapeHtml(itemName) + '</strong><br><br><label style="font-weight: 600; display: block; text-align: left; margin-bottom: 8px;">' + __('new_quantity') + '</label><input type="number" id="swalQtyInput" class="swal2-input" value="' + currentQty + '" min="1" placeholder="' + __('enter_quantity') + '" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;">',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: APP_COLORS.success,
                    cancelButtonColor: APP_COLORS.secondary,
                    confirmButtonText: __('yes_update'),
                    cancelButtonText: __('cancel'),
                    allowOutsideClick: false,
                    didOpen: (modal) => {
                        const input = modal.querySelector('#swalQtyInput');
                        if (input) {
                            setTimeout(() => input.focus(), 100);
                            input.select();
                        }
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        const newQty = parseInt(document.querySelector('#swalQtyInput').value) || 0;
                        if (newQty <= 0) {
                            Swal.fire('error', __('quantity_must_be_greater_than') , 'error');
                            return;
                        }
                        
                        // Show loading
                        Swal.fire({
                            title: __('updating'),
                            html: '<div class="spinner-border text-info" role="status"><span class="sr-only">' + __('loading') + '</span></div>',
                            icon: 'info',
                            allowOutsideClick: false,
                            allowEscapeKey: false,
                            showConfirmButton: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });
                        
                        // Send AJAX request
                        const params = new URLSearchParams();
                        params.set('action', 'update_item');
                        params.set('item_id', String(itemId));
                        params.set('quantity', String(newQty));
                        fetch(window.location.href, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                            body: params.toString()
                        })
                        .then(async (response) => {
                            const text = await response.text();
                            try { return JSON.parse(text); } catch (e) { throw new Error(text || __('invalid_json_response')); }
                        })
                        .then((data) => {
                            if (data.status === 'success') {
                                Swal.fire(__('success'), __('item_quantity_updated'), 'success').then(() => {
                                    location.reload();
                                });
                            } else {
                                Swal.fire(__('error'), data.message || __('failed_to_update_quantity'), 'error');
                            }
                        })
                        .catch(error => {
                            const msg = (error && error.message) ? error.message.substring(0, 300) : __('an_error_occurred');
                            Swal.fire(__('error'), escapeHtml(msg), 'error');
                            console.error(error);
                        });
                    }
                });
            };
            
            // Function to remove item
            window.removeItem = function(itemId, itemName) {
                Swal.fire({
                    title: 'Remove Item?',
                    html: 'Are you sure you want to remove <strong>' + escapeHtml(itemName) + '</strong> from this request?<br><br><em style="color: #6c757d;">This action cannot be undone.</em>',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: APP_COLORS.danger,
                    cancelButtonColor: APP_COLORS.secondary,
                    confirmButtonText: 'Yes, Remove',
                    cancelButtonText: 'Cancel',
                    allowOutsideClick: false
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Show loading
                        Swal.fire({
                            title: 'Removing...',
                            html: '<div class="spinner-border text-danger" role="status"><span class="sr-only">Loading...</span></div>',
                            icon: 'info',
                            allowOutsideClick: false,
                            allowEscapeKey: false,
                            showConfirmButton: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });
                        
                        // Send AJAX request
                        const params = new URLSearchParams();
                        params.set('action', 'delete_item');
                        params.set('item_id', String(itemId));
                        fetch(window.location.href, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                            body: params.toString()
                        })
                        .then(async (response) => {
                            const text = await response.text();
                            try { return JSON.parse(text); } catch (e) { throw new Error(text || 'Invalid JSON response'); }
                        })
                        .then((data) => {
                            if (data.status === 'success') {
                                Swal.fire('Removed', 'Item has been removed from the request', 'success').then(() => {
                                    location.reload();
                                });
                            } else {
                                Swal.fire('Error', data.message || 'Failed to remove item', 'error');
                            }
                        })
                        .catch(error => {
                            const msg = (error && error.message) ? error.message.substring(0, 300) : 'An error occurred';
                            Swal.fire('Error', escapeHtml(msg), 'error');
                            console.error(error);
                        });
                    }
                });
            };

            // Handle Add Item button click
            // Item types mapping by department with translation support
            const itemTypesByDepartment = {
                'Information Technology': [
                    {value: 'laptop', label: __('laptop', 'Laptop')},
                    {value: 'desktop', label: __('desktop', 'Desktop')},
                    {value: 'monitor', label: __('monitor', 'Monitor')},
                    {value: 'keyboard', label: __('keyboard', 'Keyboard')},
                    {value: 'mouse', label: __('mouse', 'Mouse')},
                    {value: 'printer', label: __('printer', 'Printer')},
                    {value: 'printer_ink_toner', label: __('printer_inktoner', 'Printer Ink/Toner')},
                    {value: 'network_equipment', label: __('network_equipment', 'Network Equipment')},
                    {value: 'software_license', label: __('software_license', 'Software License')},
                    {value: 'other', label: __('other', 'Other')}
                ],
                'Transportation': [
                    {value: 'vehicle', label: __('vehicle', 'Vehicle')},
                    {value: 'mobile_phone', label: __('mobile_phone', 'Mobile Phone')},
                    {value: 'sim_card', label: __('sim_card', 'SIM Card')},
                    {value: 'fuel_card', label: __('fuel_card', 'Fuel Card')},
                    {value: 'gps_device', label: __('gps_device', 'GPS Device')},
                    {value: 'other', label: __('other', 'Other')}
                ],
                'HR': [
                    {value: 'stationery', label: __('stationery', 'Stationery')},
                    {value: 'office_supplies', label: __('office_supplies', 'Office Supplies')},
                    {value: 'furniture', label: __('furniture', 'Furniture')},
                    {value: 'id_card', label: __('id_card', 'ID Card')},
                    {value: 'badge', label: __('badge', 'Badge')},
                    {value: 'other', label: __('other', 'Other')}
                ],
                'Finance': [
                    {value: 'calculator', label: __('calculator', 'Calculator')},
                    {value: 'receipt_book', label: __('receipt_book', 'Receipt Book')},
                    {value: 'document_folder', label: __('document_folder', 'Document Folder')},
                    {value: 'safe_locker', label: __('safelocker', 'Safe/Locker')},
                    {value: 'other', label: __('other', 'Other')}
                ],
                'Maintenance': [
                    {value: 'tools', label: __('tools', 'Tools')},
                    {value: 'equipment', label: __('equipment', 'Equipment')},
                    {value: 'safety_gear', label: __('safety_gear', 'Safety Gear')},
                    {value: 'cleaning_supplies', label: __('cleaning_supplies', 'Cleaning Supplies')},
                    {value: 'other', label: __('other', 'Other')}
                ],
                'Admin': [
                    {value: 'furniture', label: __('furniture', 'Furniture')},
                    {value: 'office_equipment', label: __('office_equipment', 'Office Equipment')},
                    {value: 'decoration', label: __('decoration', 'Decoration')},
                    {value: 'other', label: __('other', 'Other')}
                ],
                'Other': [
                    {value: 'other', label: __('other', 'Other')}
                ]
            };

            function getItemTypeOptions(department) {
                const types = itemTypesByDepartment[department] || itemTypesByDepartment['Other'];
                let options = `<option value="">${__('select_type_category')}</option>`;
                types.forEach(type => {
                    options += `<option value="${type.value}">${type.label}</option>`;
                });
                return options;
            }

            $('#addItemBtn').on('click', function() {
                const inv_no = '<?= htmlspecialchars($inv_no) ?>';
                const approverCount = <?= count($approval_chain) ?>;
                const currentDepartment = '<?= htmlspecialchars($request["department_to"]) ?>';
                
                // Don't show if approvers are already selected
                if (approverCount > 0) {
                    Swal.fire('Cannot Add Items', 'Please submit this request for approval first, or remove all approvers to add more items.', 'warning');
                    return;
                }
                
                // Show department selection first
                Swal.fire({
                    title: __('select_target_department'),
                    html: `
                        <div style="text-align: left;">
                            <label style="font-weight: 600; display: block; margin-bottom: 10px;">${__('target_department')} <span class="text-danger">*</span></label>
                            <select id="selectDepartment" class="form-control" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;">
                                <option value="">-- ${__('select_department')} --</option>
                                <option value="Information Technology" ${currentDepartment === 'Information Technology' ? 'selected' : ''}>${__('it')}</option>
                                <option value="Transportation" ${currentDepartment === 'Transportation' ? 'selected' : ''}>${__('transportation')}</option>
                                <option value="HR" ${currentDepartment === 'HR' ? 'selected' : ''}>${__('hr')}</option>
                                <option value="Finance" ${currentDepartment === 'Finance' ? 'selected' : ''}>${__('finance')}</option>
                                <option value="Maintenance" ${currentDepartment === 'Maintenance' ? 'selected' : ''}>${__('maintenance')}</option>
                                <option value="Admin" ${currentDepartment === 'Admin' ? 'selected' : ''}>${__('administration')}</option>
                                <option value="Other">${__('other')}</option>
                            </select>
                        </div>
                    `,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#007bff',
                    cancelButtonColor: APP_COLORS.secondary,
                    confirmButtonText: __('next'),
                    cancelButtonText: __('cancel'),
                    allowOutsideClick: false,
                    preConfirm: () => {
                        const department = document.getElementById('selectDepartment').value;
                        if (!department) {
                            Swal.showValidationMessage(__('please_select_a_department'));
                            return false;
                        }
                        return department;
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        const selectedDepartment = result.value;
                        
                        // Show item entry form with dynamic type options
                        Swal.fire({
                            title: __('add_new_item'),
                            html: `
                                <form id="addItemForm" style="text-align: left;">
                                    <div class="form-group" style="margin-bottom: 15px;">
                                        <label style="font-weight: 600; display: block; margin-bottom: 5px;">${__('target_department')}</label>
                                        <input type="text" class="form-control" value="${selectedDepartment}" disabled style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; background-color: #f8f9fa;">
                                    </div>
                                    <div class="form-group" style="margin-bottom: 15px;">
                                        <label style="font-weight: 600; display: block; margin-bottom: 5px;">${__('item_name')} <span class="text-danger">*</span></label>
                                        <input type="text" id="newItemName" class="form-control" placeholder="${__('enter_item_name')}" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;" required>
                                    </div>
                                    <div class="form-group" style="margin-bottom: 15px;">
                                        <label style="font-weight: 600; display: block; margin-bottom: 5px;">${__('type_category')} <span class="text-danger">*</span></label>
                                        <select id="newItemType" class="form-control" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;" required>
                                            ${getItemTypeOptions(selectedDepartment)}
                                        </select>
                                    </div>
                                    <div class="form-group" style="margin-bottom: 15px;">
                                        <label style="font-weight: 600; display: block; margin-bottom: 5px;">${__('quantity')} <span class="text-danger">*</span></label>
                                        <input type="number" id="newItemQty" class="form-control" placeholder="${__('enter_quantity')}" min="1" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;" required>
                                    </div>
                                    <div class="form-group">
                                        <label style="font-weight: 600; display: block; margin-bottom: 5px;">${__('specifications_optional')}</label>
                                        <textarea id="newItemSpecs" class="form-control" placeholder="${__('please_enter_specifications')}" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; resize: vertical; min-height: 60px;"></textarea>
                                    </div>
                                </form>
                            `,
                            icon: 'info',
                            showCancelButton: true,
                            confirmButtonColor: APP_COLORS.success,
                            cancelButtonColor: APP_COLORS.secondary,
                            confirmButtonText: `<i class="mdi mdi-plus-circle"></i> ${__('add_item')}`,
                            cancelButtonText: `${__('cancel')}`,
                            allowOutsideClick: false,
                            preConfirm: () => {
                                const itemName = document.getElementById('newItemName').value.trim();
                                const itemType = document.getElementById('newItemType').value.trim();
                                const itemQty = parseInt(document.getElementById('newItemQty').value) || 0;
                                const itemSpecs = document.getElementById('newItemSpecs').value.trim();
                                
                                // Validation
                                if (!itemName) {
                                    Swal.showValidationMessage(__('please_enter_an_item_name'));
                                    return false;
                                }
                                if (!itemType) {
                                    Swal.showValidationMessage(__('please_select_a_typecategory'));
                                    return false;
                                }
                                if (itemQty <= 0) {
                                    Swal.showValidationMessage(__('quantity_must_be_greater_than'));
                                    return false;
                                }
                                
                                return {
                                    itemName: itemName,
                                    itemType: itemType,
                                    itemQty: itemQty,
                                    itemSpecs: itemSpecs,
                                    department: selectedDepartment
                                };
                            }
                        }).then((result) => {
                            if (result.isConfirmed) {
                                // Show loading
                                Swal.fire({
                                    title: __('adding_item'),
                                    html: '<div class="spinner-border text-primary" role="status"><span class="sr-only">'+ __('loading') +'</span></div>',
                                    allowOutsideClick: false,
                                    showConfirmButton: false,
                                    didOpen: () => Swal.showLoading()
                                });
                                
                                // Submit via AJAX
                                const formData = new FormData();
                                formData.append('action', 'add_item_to_request');
                                formData.append('inv_no', inv_no);
                                formData.append('item_name', result.value.itemName);
                                formData.append('item_type', result.value.itemType);
                                formData.append('quantity', result.value.itemQty);
                                formData.append('specifications', result.value.itemSpecs);
                                
                                fetch('./includes/ajaxFile/ajaxGeneralRequest.php', {
                                    method: 'POST',
                                    body: formData
                                })
                                .then(response => response.json())
                                .then(data => {
                                    if (data.success) {
                                        Swal.fire({
                                            title: __('success'),
                                            text: __('item_added_successfully'),
                                            icon: 'success',
                                            confirmButtonText: __('ok', 'OK'),
                                            allowOutsideClick: false
                                        }).then(() => {
                                            location.reload(); // Refresh to show new item
                                        });
                                    } else {
                                        Swal.fire({
                                            title: __('error'),
                                            text: data.message || __('failed_to_add_item'),
                                            icon: 'error',
                                            confirmButtonText: __('ok', 'OK'),
                                            allowOutsideClick: false
                                        });
                                    }
                                })
                                .catch(error => {
                                    const msg = (error && error.message) ? error.message.substring(0, 300) : __('an_error_occurred');
                                    Swal.fire({
                                        title: __('error'),
                                        text: msg,
                                        icon: 'error',
                                        confirmButtonText: __('ok', 'OK'),
                                        allowOutsideClick: false
                                    });
                                    console.error(error);
                                });
                            }
                        });
                    }
                });
            });
            
            // Hide "Add Item" button if approvers are added
            $(document).on('approver-added', function() {
                const approverCount = $('#approver-list-container').find('input[name="approvers[]"]').length;
                if (approverCount > 0) {
                    $('#addItemBtn').prop('disabled', true).css('opacity', '0.5');
                } else {
                    $('#addItemBtn').prop('disabled', false).css('opacity', '1');
                }
            });

            // Initialize Select2 for employees (delivery form) with department display
            // NOTE: Select2 initialization moved to showDeliveryModal() for modal-based form
        });

        // SweetAlert2 Delivery Modal - for both Approved and Completed requests
        let deliveryFormData = {}; // Store modal form data before modal closes
        
        function showDeliveryModal() {
            const inv_no = '<?= htmlspecialchars($inv_no) ?>';
            const items = <?php echo json_encode($items) ?>;
            const currentStatus = '<?= $request['current_status'] ?>';
            const deliveryInfo = <?php echo json_encode($delivery_info) ?>;

            const currentLang = getCurrentLanguage();
            
            let modalContent = '';
            let showConfirmButton = true;
            
            // IF COMPLETED - Show read-only delivery summary
            if (currentStatus === 'completed') {
                showConfirmButton = false;
                
                // Build delivery summary
                let summaryHtml = `
                    <div style="margin-bottom: 20px; padding: 15px; background-color: #e3f2fd; border-radius: 6px; border-left: 4px solid #2196F3;">
                        <div style="font-weight: 600; color: #1976d2; margin-bottom: 8px; font-size: 16px;">✓ <?= __('delivery_completed') ?></div>
                        <div style="font-size: 14px; color: #1565c0; margin-bottom: 5px;">
                            <strong><?= __('received_by') ?>:</strong> ${deliveryInfo ? deliveryInfo.received_employee_name : 'N/A'}
                        </div>
                        <div style="font-size: 14px; color: #1565c0;">
                            <strong><?= __('date') ?>:</strong> ${deliveryInfo ? new Date(deliveryInfo.delivery_date).toLocaleString() : 'N/A'}
                        </div>
                    </div>
                `;
                
                // Build read-only items list
                let itemsHtml = '<div style="margin-bottom: 20px; text-align: left;">';
                itemsHtml += '<label style="font-weight: 600; display: block; margin-bottom: 12px;">📦 <?= __('delivered_items') ?></label>';
                itemsHtml += '<div style="max-height: 400px; overflow-y: auto; border: 1px solid #dee2e6; border-radius: 6px; padding: 12px; background-color: #f8f9fa;">';
                
                items.forEach((item, idx) => {
                    let statusIcon = '';
                    let statusText = '';
                    let statusColor = '';
                    
                    if (item.delivery_status === 'delivered') {
                        statusIcon = 'mdi-check-circle';
                        statusText = __('delivered');
                        statusColor = '#28a745';
                    } else if (item.delivery_status === 'pending') {
                        statusIcon = 'mdi-clock-outline';
                        statusText = __('pending');
                        statusColor = '#ffc107';
                    } else if (item.delivery_status === 'canceled') {
                        statusIcon = 'mdi-close-circle';
                        statusText = __('canceled');
                        statusColor = '#dc3545';
                    }
                    
                    translateName(item.item_name, 'en', 'ar', function(translated) {
                        const itemName = document.querySelector('.itemname');
                        if (itemName) itemName.textContent = translated;
                    });
                                    
                    itemsHtml += `
                        <div style="padding: 12px; background-color: white; border-radius: 4px; margin-bottom: 10px; border-left: 4px solid ${statusColor};">
                            <div style="display: flex; justify-content: space-between; align-items: start;">
                                <div>
                                    <div style="font-weight: 500; color: #2c3e50;" class="itemname">${item.item_name}</div>
                                    <small style="color: #6c757d;"><?= __('quantity') ?>: ${item.quantity}</small>
                                </div>
                                <div style="display: flex; align-items: center; gap: 6px; color: ${statusColor}; font-weight: 600;">
                                    <i class="mdi ${statusIcon}"></i> ${statusText}
                                </div>
                            </div>
                        </div>
                    `;
                });
                itemsHtml += '</div></div>';
                
                modalContent = summaryHtml + itemsHtml;
                
            } else {
                // IF NOT COMPLETED - Show delivery form
                showConfirmButton = true;
                
                // Build items HTML with status radios and per-item attachment upload
                let itemsHtml = '<div style="margin-bottom: 20px; text-align: left;">';
                itemsHtml += '<label style="font-weight: 600; display: block; margin-bottom: 12px;">📦 <?= __('items_to_deliver') ?></label>';
                itemsHtml += '<div style="max-height: 400px; overflow-y: auto; border: 1px solid #dee2e6; border-radius: 6px; padding: 12px; background-color: #f8f9fa;">';
                
                items.forEach((item, idx) => {
                    itemsHtml += `
                        <div style="padding: 12px; background-color: white; border-radius: 4px; margin-bottom: 10px; border: 1px solid #e9ecef;">
                            <div style="margin-bottom: 10px;">
                                <div style="font-weight: 500; color: #2c3e50;">${item.item_name}</div>
                                <small style="color: #6c757d;"><?= __('quantity') ?>: ${item.quantity} | Item ID: ${item.id}</small>
                            </div>
                            
                            <div style="display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 10px;">
                                <label style="display: flex; align-items: center; gap: 6px; cursor: pointer; margin: 0;">
                                    <input type="radio" name="modal_item_status[${item.id}]" value="delivered" checked>
                                    <span style="color: #28a745; font-size: 13px;"><i class="mdi mdi-check-circle"></i> <?= __('delivered') ?></span>
                                </label>
                                <label style="display: flex; align-items: center; gap: 6px; cursor: pointer; margin: 0;">
                                    <input type="radio" name="modal_item_status[${item.id}]" value="pending">
                                    <span style="color: #ffc107; font-size: 13px;"><i class="mdi mdi-clock-outline"></i> <?= __('pending') ?></span>
                                </label>
                                <label style="display: flex; align-items: center; gap: 6px; cursor: pointer; margin: 0;">
                                    <input type="radio" name="modal_item_status[${item.id}]" value="canceled">
                                    <span style="color: #dc3545; font-size: 13px;"><i class="mdi mdi-close-circle"></i> <?= __('canceled') ?></span>
                                </label>
                            </div>
                            
                            <div style="border: 1px dashed #bbb; border-radius: 4px; padding: 8px; background-color: #fafafa;">
                                <small style="font-weight: 500; color: #666; display: block; margin-bottom: 5px;">📎 <?= __('attachment_for_this_item_optional') ?>:</small>
                                <div style="display: flex; gap: 8px; align-items: center;">
                                    <input type="file" id="modal_item_file_${item.id}" class="modal_item_file" data-item-id="${item.id}" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xlsx" style="font-size: 12px; flex: 1;">
                                    <div id="modal_item_file_display_${item.id}" style="font-size: 12px; color: #666;"></div>
                                </div>
                            </div>
                        </div>
                    `;
                });
                itemsHtml += '</div></div>';
                
                // Build employee selector
                let employeeHtml = `
                    <div style="margin-bottom: 20px; text-align: left;">
                        <label style="font-weight: 600; display: block; margin-bottom: 8px;">👤 <?= __('received_by_employee') ?> <span style="color: #dc3545;">*</span></label>
                        <select id="modal_receivedBySelect" class="form-control" style="width: 100%;" required>
                            <option value="">-- <?= __('select_employee') ?> --</option>
                        </select>
                        <input type="hidden" id="modal_receivedBy" name="received_by">
                        <small style="color: #6c757d;"><?= __('search_by_name_employee_id_or_department') ?></small>
                    </div>
                `;
                
                modalContent = employeeHtml + itemsHtml;
            }

            Swal.fire({
                title: '<i class="mdi mdi-truck-delivery" style="margin-right: 10px;"></i> <?= __('delivery_details') ?>',
                html: modalContent,
                icon: 'info',
                width: '700px',
                showCancelButton: showConfirmButton,
                showConfirmButton: showConfirmButton,
                confirmButtonColor: APP_COLORS.success,
                confirmButtonText: '<i class="mdi mdi-check"></i> <?= __('submit_delivery') ?>',
                cancelButtonText: __('cancel'),
                allowOutsideClick: false,
                didOpen: function(modal) {
                    // Only initialize form elements if not completed
                    if (currentStatus !== 'completed') {
                        // Initialize Select2 for employee in modal
                        setTimeout(function() {
                            if ($('#modal_receivedBySelect').length) {
                                $('#modal_receivedBySelect').select2({
                                    placeholder: '<?= __('search_employee') ?>',
                                    allowClear: true,
                                    width: '100%',
                                    ajax: {
                                        url: './includes/ajaxFile/ajaxGeneralRequest.php',
                                        type: 'POST',
                                        dataType: 'json',
                                        delay: 300,
                                        data: function (params) {
                                            return {
                                                action: 'get_employees',
                                                search: params.term || ''
                                            };
                                        },
                                        processResults: function (data) {
                                            return { results: data.results || [] };
                                        }
                                    },
                                    minimumInputLength: 0,
                                    templateResult: function(data) {
                                        if (data.loading) return __('searching');
                                        if (!data.id) return data.text;
                                        let dept = data.department ? ' - ' + data.department : '';
                                        return $('<div>' + data.text + dept + '</div>');
                                    },
                                    templateSelection: function(data) {
                                        if (!data.id) return data.text;
                                        $('#modal_receivedBy').val(data.id);
                                        let dept = data.department ? ' - ' + data.department : '';
                                        return data.name + ' (' + data.emp_id + ')' + dept;
                                    }
                                });
                            }
                        }, 300);

                        // Handle per-item file uploads
                        document.querySelectorAll('.modal_item_file').forEach(fileInput => {
                            fileInput.addEventListener('change', function() {
                                const itemId = this.getAttribute('data-item-id');
                                displayItemFileName(this, itemId);
                            });
                        });
                    }
                },
                willClose: function() {
                    // CAPTURE FILES AND FORM DATA BEFORE MODAL CLOSES
                    if (currentStatus !== 'completed') {
                        deliveryFormData = {
                            receivedBy: $('#modal_receivedBy').val(),
                            items: {},
                            files: {}
                        };
                        
                        // Capture item statuses
                        $('input[name^="modal_item_status"]').each(function() {
                            const name = $(this).attr('name');
                            const itemId = name.match(/\[(\d+)\]/)[1];
                            if ($(this).is(':checked')) {
                                deliveryFormData.items[itemId] = $(this).val();
                            }
                        });
                        
                        // Capture file data BEFORE modal DOM is destroyed
                        document.querySelectorAll('[id^="modal_item_file_"]').forEach(input => {
                            const itemId = input.id.replace('modal_item_file_', '');
                            if (input.files && input.files.length > 0) {
                                deliveryFormData.files[itemId] = input.files[0];
                                console.log('DEBUG willClose: Captured file for item ' + itemId + ': ' + input.files[0].name);
                            }
                        });
                        
                        console.log('DEBUG willClose: Form data captured:', deliveryFormData);
                    }
                    
                    // Destroy Select2 instance if exists
                    if ($('#modal_receivedBySelect').hasClass('select2-hidden-accessible')) {
                        $('#modal_receivedBySelect').select2('destroy');
                    }
                }
            }).then((result) => {
                if (result.isConfirmed && currentStatus !== 'completed') {
                    submitDelivery(inv_no);
                }
            });
        }

        // Display selected file name per item
        function displayItemFileName(fileInput, itemId) {
            const file = fileInput.files[0];
            const display = document.getElementById('modal_item_file_display_' + itemId);
            
            if (file) {
                const maxSize = 5 * 1024 * 1024; // 5MB
                const allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx', 'xlsx'];
                const fileName = file.name.toLowerCase();
                const fileExt = fileName.split('.').pop();
                
                if (file.size > maxSize) {
                    display.innerHTML = '<span style="color: #dc3545; font-size: 11px;"><i class="mdi mdi-alert-circle"></i> <?= __('file_too_large') ?></span>';
                    fileInput.value = '';
                    return;
                }
                
                if (!allowedExtensions.includes(fileExt)) {
                    display.innerHTML = '<span style="color: #dc3545; font-size: 11px;"><i class="mdi mdi-alert-circle"></i> <?= __('invalid_file_type') ?></span>';
                    fileInput.value = '';
                    return;
                }
                
                display.innerHTML = `
                    <div style="display: flex; align-items: center; gap: 5px; background-color: #e8f5e9; padding: 4px 8px; border-radius: 3px; color: #28a745; font-size: 11px;">
                        <i class="mdi mdi-check-circle"></i>
                        <span title="${file.name}">${file.name.length > 20 ? file.name.substring(0, 17) + '...' : file.name}</span>
                        <button type="button" onclick="removeItemAttachment(${itemId})" style="background: none; border: none; color: #dc3545; cursor: pointer; padding: 0; margin-left: 3px;">
                            <i class="mdi mdi-close-circle"></i>
                        </button>
                    </div>
                `;
            } else {
                display.innerHTML = '';
            }
        }

        // Remove attachment from item
        function removeItemAttachment(itemId) {
            const fileInput = document.getElementById('modal_item_file_' + itemId);
            const display = document.getElementById('modal_item_file_display_' + itemId);
            fileInput.value = '';
            display.innerHTML = '';
        }

        // Submit delivery with all data (per-item attachments)
        function submitDelivery(inv_no) {
            // Use captured form data from willClose callback
            if (!deliveryFormData.receivedBy) {
                Swal.fire({
                    title: __('warning'), 
                    text: __('please_select_an_employee_who_received_the_items'), 
                    icon: 'warning', 
                    confirmButtonText: __('ok', 'OK'), 
                    allowOutsideClick: false
                });
                return;
            }

            if (Object.keys(deliveryFormData.items).length === 0) {
                Swal.fire({
                    title: __('warning'), 
                    text: __('please_select_delivery_status_for_all_items'), 
                    icon: 'warning', 
                    confirmButtonText: __('ok', 'OK'), 
                    allowOutsideClick: false
                });
                return;
            }

            // Show loading
            Swal.fire({
                title: __('processing_delivery'),
                html: '<div class="spinner-border text-success" role="status"><span class="sr-only">' + __('loading') + '</span></div>',
                allowOutsideClick: false,
                showConfirmButton: false,
                didOpen: () => Swal.showLoading()
            });

            // Build form data with per-item attachments
            const formData = new FormData();
            formData.append('action', 'mark_delivery');
            formData.append('inv_no', inv_no);
            formData.append('received_by', deliveryFormData.receivedBy);
            
            // Add item statuses
            for (const [itemId, status] of Object.entries(deliveryFormData.items)) {
                formData.append('items[' + itemId + ']', status);
            }

            // Add files from captured data
            let fileCount = 0;
            for (const [itemId, file] of Object.entries(deliveryFormData.files)) {
                if (file instanceof File) {
                    formData.append('attachments[' + itemId + ']', file);
                    fileCount++;
                }
            }
            
            for (let [key, value] of formData.entries()) {
                if (value instanceof File) {
                    console.log('  ' + key + ': [File] ' + value.name + ' (' + value.size + ' bytes)');
                } else {
                    console.log('  ' + key + ': ' + value);
                }
            }

            fetch('./includes/ajaxFile/ajaxGeneralRequest.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        title: __('success'),
                        text: __('delivery_submitted_successfully'),
                        icon: 'success',
                        confirmButtonText: __('ok', 'OK'),
                        allowOutsideClick: false
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        title: __('error'),
                        text: data.message || __('failed_to_submit_delivery'),
                        icon: 'error',
                        confirmButtonText: __('ok', 'OK'),
                        allowOutsideClick: false
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    title: __('error'),
                    text: __('an_error_occurred_while_submitting_delivery'),
                    icon: 'error',
                    confirmButtonText: __('ok', 'OK'),
                    allowOutsideClick: false
                });
            });
        }
    </script>
    <script src="./plugins/select2/js/select2.min.js" type="text/javascript"></script>
</body>
</html>
