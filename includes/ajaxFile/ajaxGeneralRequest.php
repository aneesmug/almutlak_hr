<?php

header('Content-Type: application/json');
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/session_check.php';
include("./../../includes/helper_functions.php"); // --- Helper Function ---
include("./../../includes/validate_supervisor.php"); // --- Supervisor Validation ---

// Get the User's ID and Name from session if they exist
// These are used by the handle_approval_action function
if (session_status() == PHP_SESSION_NONE) session_start(); // Ensure session is started
$emp_id = $_SESSION['empid'] ?? 0;
$userwel = $_SESSION['userwel'] ?? '';
$user_type = $_SESSION['type'] ?? '';

// Initialize response array
$response = [
    'success' => false,
    'message' => 'No response'
];

// Restrict to non-employee users
if ($user_type == 'employee' && isset($_POST['action']) && $_POST['action'] !== 'get_employees') {
    $response['message'] = __('access_denied', 'Access denied');
    echo json_encode($response);
    exit;
}

// DEBUG: Log all incoming POST/FILES for every action
$debugLog = __DIR__ . '/../../delivery_debug.log';
$timestamp = date('Y-m-d H:i:s');
file_put_contents($debugLog, "\n\n==================== AJAX REQUEST START - {$timestamp} ====================\n", FILE_APPEND);
file_put_contents($debugLog, "Action: " . ($_POST['action'] ?? 'none') . "\n", FILE_APPEND);
file_put_contents($debugLog, "POST keys: " . json_encode(array_keys($_POST)) . "\n", FILE_APPEND);
file_put_contents($debugLog, "FILES keys: " . json_encode(array_keys($_FILES)) . "\n", FILE_APPEND);
file_put_contents($debugLog, "Content-Type: " . ($_SERVER['CONTENT_TYPE'] ?? 'not set') . "\n", FILE_APPEND);
file_put_contents($debugLog, "CONTENT_LENGTH: " . ($_SERVER['CONTENT_LENGTH'] ?? '0') . "\n", FILE_APPEND);
if (!empty($_FILES)) {
    file_put_contents($debugLog, "FILES dump: " . json_encode($_FILES, JSON_PRETTY_PRINT) . "\n", FILE_APPEND);
}
file_put_contents($debugLog, "==================== AJAX REQUEST END ====================\n", FILE_APPEND);

try {
    // Create General Request
    if (isset($_POST['action']) && $_POST['action'] == 'create_general_request') {
        
        // Validate required fields
        if (empty($_POST['inv_no']) || empty($_POST['request_title']) || empty($_POST['department_to']) || empty($_POST['request_category']) || empty($_POST['priority'])) {
            $response['message'] = __('fill_out_form_error', 'Please fill all required fields');
            echo json_encode($response);
            exit;
        }
        
        // Validate at least one item
        if (!isset($_POST['items']) || !is_array($_POST['items']) || empty($_POST['items'])) {
            $response['message'] = __('add_at_least_one_item', 'Please add at least one item to the request');
            echo json_encode($response);
            exit;
        }
        
        // Sanitize inputs
        $inv_no = mysqli_real_escape_string($conDB, $_POST['inv_no']);
        $request_title = mysqli_real_escape_string($conDB, $_POST['request_title']);
        $department_to = mysqli_real_escape_string($conDB, $_POST['department_to']);
        $request_category = mysqli_real_escape_string($conDB, $_POST['request_category']);
        $priority = mysqli_real_escape_string($conDB, $_POST['priority']);
        $description = mysqli_real_escape_string($conDB, $_POST['description'] ?? '');
        $emp_name = mysqli_real_escape_string($conDB, $_POST['emp_name']);
        $user_dept = (int)$_POST['user_dept'];
        
        // Start transaction
        mysqli_begin_transaction($conDB);
        
        try {
            // Insert main request
            $insert_request = "INSERT INTO `general_requests` 
                (`inv_no`, `request_title`, `department_to`, `request_category`, `priority`, `description`, `emp_id`, `emp_name`, `user_dept`, `current_status`)
                VALUES 
                ('$inv_no', '$request_title', '$department_to', '$request_category', '$priority', '$description', '$emp_id', '$emp_name', '$user_dept', 'draft')";
            
            if (!mysqli_query($conDB, $insert_request)) {
                throw new Exception(__('failed_to_create_request', 'Failed to create request'));
            }
            
            // Insert request items
            $inserted_items = [];
            foreach ($_POST['items'] as $item_data) {
                if (empty($item_data['item_name']) || empty($item_data['quantity'])) {
                    continue; // Skip empty items
                }
                
                $item_name = mysqli_real_escape_string($conDB, trim($item_data['item_name']));
                $item_type = mysqli_real_escape_string($conDB, trim($item_data['item_type'] ?? ''));
                $quantity = (int)$item_data['quantity'];
                $specifications = mysqli_real_escape_string($conDB, trim($item_data['specifications'] ?? ''));
                
                // Create a unique hash to prevent duplicates from being inserted
                $item_hash = md5($item_name . '|' . $quantity . '|' . $specifications);
                
                if (in_array($item_hash, $inserted_items)) {
                    continue; // Skip duplicate item
                }
                $inserted_items[] = $item_hash;
                
                $insert_item = "INSERT INTO `general_request_items` 
                    (`request_inv_no`, `item_name`, `item_type`, `quantity`, `specifications`)
                    VALUES 
                    ('$inv_no', '$item_name', '$item_type', '$quantity', '$specifications')";
                
                if (!mysqli_query($conDB, $insert_item)) {
                    throw new Exception(__('failed_to_add_items', 'Failed to add request items'));
                }
            }
            
            // Handle file uploads
            if (isset($_FILES['attachments']) && !empty($_FILES['attachments']['name'][0])) {
                $upload_dir = './../../assets/general_request_attachments/';
                
                // Create directory if it doesn't exist
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                $allowed_extensions = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx', 'xls', 'xlsx'];
                
                foreach ($_FILES['attachments']['tmp_name'] as $key => $tmp_name) {
                    if ($_FILES['attachments']['error'][$key] === UPLOAD_ERR_OK) {
                        $file_name = $_FILES['attachments']['name'][$key];
                        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                        
                        // Validate extension
                        if (!in_array($file_ext, $allowed_extensions)) {
                            continue; // Skip invalid files
                        }
                        
                        // Generate unique filename
                        $new_filename = $inv_no . '_' . md5(uniqid() . $file_name) . '.' . $file_ext;
                        $target_file = $upload_dir . $new_filename;
                        
                        // Move uploaded file
                        if (move_uploaded_file($tmp_name, $target_file)) {
                            // Insert attachment record
                            $insert_attachment = "INSERT INTO `general_request_attachments` 
                                (`request_inv_no`, `attachment`, `docu_ext`)
                                VALUES 
                                ('$inv_no', '$new_filename', '$file_ext')";
                            
                            mysqli_query($conDB, $insert_attachment);
                        }
                    }
                }
            }
            
            // Insert status log
            $insert_status = "INSERT INTO `smt_request_status` 
                (`emp_id`, `inv_no`, `emp_name`, `status`, `note`)
                VALUES 
                ('$emp_id', '$inv_no', '$emp_name', 'draft', 'Request created')";
            
            mysqli_query($conDB, $insert_status);
            
            // Commit transaction
            mysqli_commit($conDB);
            
            // Log activity
            if (class_exists('ActivityLogger')) {
                ActivityLogger::logCreate('General Request', 'new_general_request.php', 0, [
                    'inv_no' => $inv_no,
                    'request_title' => $request_title,
                    'department_to' => $department_to
                ], "Created general request: {$inv_no}", 'general_requests');
            }
            
            $response['success'] = true;
            $response['message'] = __('request_created_successfully', 'Request created successfully');
            $response['inv_no'] = $inv_no;
            
        } catch (Exception $e) {
            mysqli_rollback($conDB);
            $response['message'] = $e->getMessage();
        }
    }
    
    // Delete General Request
    elseif (isset($_POST['action']) && $_POST['action'] == 'delete_general_request') {
        $inv_no = mysqli_real_escape_string($conDB, $_POST['inv_no']);
        
        // Check if user has permission to delete
        $check_query = mysqli_query($conDB, "SELECT emp_id, current_status FROM general_requests WHERE inv_no = '$inv_no'");
        
        if (!$check_query || mysqli_num_rows($check_query) == 0) {
            $response['message'] = __('request_not_found', 'Request not found');
            echo json_encode($response);
            exit;
        }
        
        $request_data = mysqli_fetch_assoc($check_query);
        
        // Only creator can delete draft requests, or administrator can delete any
        if ($user_type != 'administrator' && ($request_data['emp_id'] != $emp_id || $request_data['current_status'] != 'draft')) {
            $response['message'] = __('permission_denied', 'You do not have permission to delete this request');
            echo json_encode($response);
            exit;
        }
        
        mysqli_begin_transaction($conDB);
        
        try {
            // Delete attachments (files and records)
            $attachments_query = mysqli_query($conDB, "SELECT attachment FROM general_request_attachments WHERE request_inv_no = '$inv_no'");
            while ($attachment = mysqli_fetch_assoc($attachments_query)) {
                $file_path = './../../assets/general_request_attachments/' . $attachment['attachment'];
                if (file_exists($file_path)) {
                    unlink($file_path);
                }
            }
            mysqli_query($conDB, "DELETE FROM general_request_attachments WHERE request_inv_no = '$inv_no'");
            
            // Delete items
            mysqli_query($conDB, "DELETE FROM general_request_items WHERE request_inv_no = '$inv_no'");
            
            // Delete approval chain
            $type_query = mysqli_query($conDB, "SELECT id FROM approval_request_types WHERE main_table_name = 'general_requests' LIMIT 1");
            if ($type_query && mysqli_num_rows($type_query) > 0) {
                $type_row = mysqli_fetch_assoc($type_query);
                $request_type_id = $type_row['id'];
                mysqli_query($conDB, "DELETE FROM request_approvers WHERE request_inv_no = '$inv_no' AND request_type_id = $request_type_id");
            }
            
            // Delete status logs
            mysqli_query($conDB, "DELETE FROM smt_request_status WHERE inv_no = '$inv_no'");
            
            // Delete main request
            if (!mysqli_query($conDB, "DELETE FROM general_requests WHERE inv_no = '$inv_no'")) {
                throw new Exception(__('failed_to_delete_request', 'Failed to delete request'));
            }
            
            mysqli_commit($conDB);
            
            // Log activity
            if (class_exists('ActivityLogger')) {
                ActivityLogger::logDelete('General Request', 'all_general_requests.php', 0, "Deleted general request: {$inv_no}", 'general_requests');
            }
            
            $response['success'] = true;
            $response['message'] = __('request_deleted_successfully', 'Request deleted successfully');
            
        } catch (Exception $e) {
            mysqli_rollback($conDB);
            $response['message'] = $e->getMessage();
        }
    }
    // Add item to existing request
    elseif (isset($_POST['action']) && $_POST['action'] === 'add_item_to_request') {
        // Add new item to existing draft request
        $inv_no = escape_string($_POST['inv_no'] ?? '');
        $item_name = escape_string($_POST['item_name'] ?? '');
        $item_type = escape_string($_POST['item_type'] ?? '');
        $quantity = (int)($_POST['quantity'] ?? 0);
        $specifications = escape_string($_POST['specifications'] ?? '');
        
        // Validate input
        if (empty($inv_no) || empty($item_name) || $quantity <= 0) {
            $response['message'] = __('invalid_input', 'Invalid input. Please provide item name and quantity.');
            echo json_encode($response);
            exit;
        }
        
        // Check if request exists and is in draft status
        $check_query = mysqli_query($conDB, "SELECT inv_no, current_status FROM general_requests WHERE inv_no = '$inv_no' LIMIT 1");
        if (!$check_query || mysqli_num_rows($check_query) == 0) {
            $response['message'] = __('request_not_found', 'Request not found');
            echo json_encode($response);
            exit;
        }
        $request_check = mysqli_fetch_assoc($check_query);
        if ($request_check['current_status'] !== 'draft') {
            $response['message'] = __('request_not_in_draft', 'Can only add items to requests in draft status');
            echo json_encode($response);
            exit;
        }
        
        // Insert new item
        $insert_item = "INSERT INTO `general_request_items` 
            (`request_inv_no`, `item_name`, `item_type`, `quantity`, `specifications`)
            VALUES 
            ('$inv_no', '$item_name', '$item_type', '$quantity', '$specifications')";
        
        if (mysqli_query($conDB, $insert_item)) {
            $new_item_id = mysqli_insert_id($conDB);
            $response['success'] = true;
            $response['message'] = __('item_added_successfully', 'Item added successfully');
            $response['item_id'] = $new_item_id;
            $response['item_name'] = $_POST['item_name'];
            $response['item_quantity'] = $quantity;
        } else {
            $response['message'] = __('failed_to_add_item', 'Failed to add item') . ': ' . mysqli_error($conDB);
        }
    }
    // Get employees for select2 dropdown with department info
    elseif (isset($_POST['action']) && $_POST['action'] === 'get_employees') {
        $search = mysqli_real_escape_string($conDB, $_POST['search'] ?? '');
        
        $query = "SELECT e.emp_id, e.name, d.dep_nme as department 
                  FROM employees e
                  LEFT JOIN department d ON d.id = e.dept
                  WHERE e.status = 1";
        if (!empty($search)) {
            $query .= " AND (e.name LIKE '%$search%' OR e.emp_id LIKE '%$search%' OR d.dep_nme LIKE '%$search%')";
        }
        $query .= " ORDER BY e.name LIMIT 50";
        
        $result = mysqli_query($conDB, $query);
        if (!$result) {
            echo json_encode(['results' => [], 'error' => mysqli_error($conDB)]);
            exit;
        }
        
        $employees = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $dept_info = !empty($row['department']) ? ' - ' . htmlspecialchars($row['department']) : '';
            $employees[] = [
                'id' => $row['emp_id'],
                'text' => htmlspecialchars($row['name']) . ' (' . $row['emp_id'] . ')' . $dept_info,
                'name' => $row['name'],
                'emp_id' => $row['emp_id'],
                'department' => $row['department']
            ];
        }
        
        header('Content-Type: application/json');
        echo json_encode(['results' => $employees]);
        exit;
    }
    // Mark items as delivered
    elseif (isset($_POST['action']) && $_POST['action'] === 'mark_delivery') {
        $inv_no = escape_string($_POST['inv_no'] ?? '');
        $items = $_POST['items'] ?? []; // Array of items with delivery status
        $received_by = escape_string($_POST['received_by'] ?? '');
        $attachments = $_FILES['attachments'] ?? []; // Per-item attachments
        
        if (empty($inv_no) || empty($received_by)) {
            $response['message'] = __('invalid_input', 'Invalid input');
            echo json_encode($response);
            exit;
        }
        
        // Check if request is approved
        $check_query = mysqli_query($conDB, "SELECT current_status FROM general_requests WHERE inv_no = '$inv_no' LIMIT 1");
        if (!$check_query || mysqli_num_rows($check_query) == 0) {
            $response['message'] = __('request_not_found', 'Request not found');
            echo json_encode($response);
            exit;
        }
        
        $request_data = mysqli_fetch_assoc($check_query);
        // Allow delivery when request is approved and waiting for delivery
        $allowed_delivery_statuses = ['approved', 'waiting_for_delivery'];
        if (!in_array($request_data['current_status'], $allowed_delivery_statuses, true)) {
            $response['message'] = __('request_not_approved', 'Request must be approved first');
            echo json_encode($response);
            exit;
        }
        
        $upload_dir = __DIR__ . '/../../assets/delivery_attachments/';
        $max_size = 5 * 1024 * 1024; // 5MB
        $allowed_types = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx', 'xlsx', 'zip'];
        
        // Create directory if not exists
        if (!is_dir($upload_dir)) {
            if (!mkdir($upload_dir, 0777, true) && !is_dir($upload_dir)) {
                error_log('DELIVERY DEBUG: failed to create upload dir ' . $upload_dir . ' - perms?');
                $response['message'] = __('upload_failed', 'Failed to create delivery attachments folder');
                echo json_encode($response);
                exit;
            }
        }
        
        // Process per-item attachments
        $item_attachments = []; // Store [item_id => filename]
        $uploaded_files = []; // Track uploaded files for cleanup

        // Debug: log incoming files summary and PHP limits
        $contentLength = isset($_SERVER['CONTENT_LENGTH']) ? (int)$_SERVER['CONTENT_LENGTH'] : 0;
        $postMax = ini_get('post_max_size');
        $uploadMax = ini_get('upload_max_filesize');
        $debugLog = __DIR__ . '/../../delivery_debug.log';
        file_put_contents($debugLog, "\n--- MARK_DELIVERY START ---\n", FILE_APPEND);
        file_put_contents($debugLog, "content-length={$contentLength}, post_max_size={$postMax}, upload_max_filesize={$uploadMax}\n", FILE_APPEND);

        if (!empty($attachments)) {
            file_put_contents($debugLog, "Attachments received:\n", FILE_APPEND);
            file_put_contents($debugLog, json_encode([
                'names' => $attachments['name'] ?? null,
                'sizes' => $attachments['size'] ?? null,
                'errors' => $attachments['error'] ?? null
            ], JSON_PRETTY_PRINT) . "\n", FILE_APPEND);
        } else {
            file_put_contents($debugLog, "NO attachments received in \$_FILES\n", FILE_APPEND);
        }
        
        if (!empty($attachments) && isset($attachments['name']) && is_array($attachments['name'])) {
            foreach ($attachments['name'] as $item_id => $filename) {
                if (!empty($filename) && !empty($attachments['tmp_name'][$item_id])) {
                    $errorCode = isset($attachments['error'][$item_id]) ? (int)$attachments['error'][$item_id] : UPLOAD_ERR_OK;
                    $sizeVal = isset($attachments['size'][$item_id]) ? (int)$attachments['size'][$item_id] : 0;
                    file_put_contents($debugLog, "Processing file for item {$item_id}: name={$filename}, size={$sizeVal}, error={$errorCode}\n", FILE_APPEND);

                    // Check PHP upload error
                    if ($errorCode !== UPLOAD_ERR_OK) {
                        $response['message'] = __('upload_failed', 'Upload error for item ' . $item_id . ' (code ' . $errorCode . ')');
                        echo json_encode($response);
                        exit;
                    }

                    $file = [
                        'name' => $attachments['name'][$item_id],
                        'tmp_name' => $attachments['tmp_name'][$item_id],
                        'size' => $sizeVal,
                        'error' => $errorCode
                    ];
                    
                    // Validate file size
                    if ($file['size'] > $max_size) {
                        $response['message'] = __('file_too_large', 'File for item ' . $item_id . ' is too large (Max: 5MB)');
                        echo json_encode($response);
                        exit;
                    }
                    
                    $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                    if (!in_array($file_ext, $allowed_types)) {
                        $response['message'] = __('invalid_file_type', 'Invalid file type for item ' . $item_id . '. Allowed: PDF, JPG, PNG, DOC, DOCX, XLSX, ZIP');
                        echo json_encode($response);
                        exit;
                    }
                    
                    // Generate unique filename per item
                    $timestamp = date('YmdHis');
                    $random_id = uniqid();
                    $item_attachment_filename = $inv_no . '_item' . $item_id . '_' . $timestamp . '_' . $random_id . '.' . $file_ext;
                    $upload_path = $upload_dir . $item_attachment_filename;
                    file_put_contents($debugLog, "Attempting to move to: {$upload_path}\n", FILE_APPEND);
                    
                    // Move uploaded file
                    if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
                        $isUploaded = is_uploaded_file($file['tmp_name']) ? 'yes' : 'no';
                        file_put_contents($debugLog, "FAILED to move file. tmp_path={$file['tmp_name']}, is_uploaded_file={$isUploaded}\n", FILE_APPEND);
                        $response['message'] = __('upload_failed', 'Failed to upload file for item ' . $item_id);
                        echo json_encode($response);
                        exit;
                    }
                    file_put_contents($debugLog, "SUCCESS: File moved to {$upload_path}\n", FILE_APPEND);
                    
                    $item_attachments[$item_id] = $item_attachment_filename;
                    $uploaded_files[] = $upload_path;
                } else {
                    file_put_contents($debugLog, "SKIPPED item {$item_id}: empty filename or tmp_name\n", FILE_APPEND);
                }
            }
        }
        file_put_contents($debugLog, "Total item_attachments collected: " . count($item_attachments) . "\n", FILE_APPEND);
        
        mysqli_begin_transaction($conDB);
        
        try {
            // Prepare single attachment filename (store the first one to fit VARCHAR(255))
            $first_attachment = null;
            if (!empty($item_attachments)) {
                $values = array_values($item_attachments);
                $first_attachment = $values[0] ?? null;
            }
            $first_attachment_sql = $first_attachment ? "'" . escape_string($first_attachment) . "'" : "NULL";
            
            // Create delivery record if not exists
            $delivery_check = mysqli_query($conDB, "SELECT id FROM general_request_deliveries WHERE request_inv_no = '$inv_no' LIMIT 1");
            
            if (mysqli_num_rows($delivery_check) == 0) {
                $insert_delivery = "INSERT INTO general_request_deliveries (request_inv_no, received_by, delivery_date, attachment_filename) VALUES ('$inv_no', '$received_by', NOW(), $first_attachment_sql)";
                if (!mysqli_query($conDB, $insert_delivery)) {
                    throw new Exception(__('failed_to_create_delivery', 'Failed to create delivery record'));
                }
                $delivery_id = mysqli_insert_id($conDB);
            } else {
                $delivery_row = mysqli_fetch_assoc($delivery_check);
                $delivery_id = $delivery_row['id'];
                // Update existing delivery record with first attachment filename if present
                if ($first_attachment) {
                    $update_delivery = "UPDATE general_request_deliveries SET attachment_filename = $first_attachment_sql WHERE id = $delivery_id";
                    mysqli_query($conDB, $update_delivery);
                }
            }
            
            // Update item delivery statuses (do NOT add attachment_filename to items table - column doesn't exist)
            foreach ($items as $item_id => $status) {
                $item_id = (int)$item_id;
                $status = escape_string($status); // 'delivered', 'pending', or 'canceled'
                
                $update_item = "UPDATE general_request_items SET delivery_status = '$status', delivery_id = $delivery_id WHERE id = $item_id AND request_inv_no = '$inv_no'";
                if (!mysqli_query($conDB, $update_item)) {
                    throw new Exception(__('failed_to_update_item', 'Failed to update item delivery status'));
                }
                
                // Store per-item attachment in general_request_attachments table if exists
                if (isset($item_attachments[$item_id])) {
                    $attachment_name = escape_string($item_attachments[$item_id]);
                    $file_ext = strtolower(pathinfo($item_attachments[$item_id], PATHINFO_EXTENSION));
                    
                    $insert_attachment = "INSERT INTO general_request_attachments (request_inv_no, attachment, docu_ext, attachment_type, created_at) VALUES ('$inv_no', '$attachment_name', '$file_ext', 'delivery', NOW())";
                    if (!mysqli_query($conDB, $insert_attachment)) {
                        throw new Exception(__('failed_to_save_attachment', 'Failed to save attachment record'));
                    }
                }
            }
            
            // Check if all items are delivered
            $pending_items = mysqli_query($conDB, "SELECT COUNT(*) as count FROM general_request_items WHERE request_inv_no = '$inv_no' AND (delivery_status IS NULL OR delivery_status = 'pending')");
            $pending_data = mysqli_fetch_assoc($pending_items);
            
            if ($pending_data['count'] == 0) {
                // All items delivered, mark request as completed
                mysqli_query($conDB, "UPDATE general_requests SET current_status = 'completed', completed_at = NOW() WHERE inv_no = '$inv_no' AND current_status = 'waiting_for_delivery'");
            }
            
            mysqli_commit($conDB);
            
            $response['success'] = true;
            $response['message'] = __('delivery_updated', 'Delivery status updated successfully');
            
        } catch (Exception $e) {
            mysqli_rollback($conDB);
            // Clean up uploaded files if transaction fails
            foreach ($uploaded_files as $file_path) {
                if (file_exists($file_path)) {
                    unlink($file_path);
                }
            }
            $response['message'] = $e->getMessage();
        }
    }
    
} catch (Exception $e) {
    $response['message'] = __('error_occurred', 'An error occurred: ') . $e->getMessage();
}

echo json_encode($response);
?>
