<?php
/**
 * Approval Chain Configuration Handler
 * Manages approval workflow settings for different request types
 * 
 * IMPORTANT: This system manages STATIC approval chains only (configured roles).
 * 
 * ASSET CLEARANCE IS NOT AFFECTED:
 * The dynamic asset clearance logic (IT, Admin, Transportation departments) 
 * is separate and continues to work automatically based on assigned assets.
 * 
 * Integration Approach:
 * 1. Static approvers (from this config) - e.g., HR Senior BP, HR Payroll
 * 2. Dynamic asset clearance (automatic) - e.g., IT Manager, Admin Manager
 * 3. Both can coexist in the same approval chain
 * 
 * Example Flow:
 * Level 1: Direct Supervisor (static, from config)
 * Level 2: HR Senior BP (static, from config)
 * Level 3: IT Manager (dynamic, if laptop assigned)
 * Level 4: Admin Manager (dynamic, if mobile assigned)
 * Level 5: HR Payroll (static, from config)
 */

define('SKIP_PAGE_ACCESS_CONTROL', true); // Treat as JSON/AJAX endpoint to avoid HTML redirects
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/session_check.php';

header('Content-Type: application/json');

// Only allow admin users to configure approval chains
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'administrator') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

$action = $_POST['action'] ?? '';

switch ($action) {
    case 'get_approval_chain':
        getApprovalChain($conDB);
        break;
    case 'add_approval_step':
        addApprovalStep($conDB);
        break;
    case 'remove_approval_step':
        removeApprovalStep($conDB);
        break;
    case 'update_approval_order':
        updateApprovalOrder($conDB);
        break;
    case 'get_all_request_types':
        getAllRequestTypes($conDB);
        break;
    case 'create_new_request_type':
        createNewRequestType($conDB);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}

/**
 * Get approval chain configuration for a request type
 */
function getApprovalChain($conDB) {
    $requestType = mysqli_real_escape_string($conDB, $_POST['request_type'] ?? '');
    
    if (empty($requestType)) {
        echo json_encode(['success' => false, 'message' => 'Request type is required']);
        return;
    }

    // Check if configuration exists in app_settings
    $settingName = "approval_chain_{$requestType}";
    $query = mysqli_query($conDB, "SELECT setting_value FROM app_settings WHERE setting_name = '{$settingName}' LIMIT 1");
    
    if ($query && mysqli_num_rows($query) > 0) {
        $row = mysqli_fetch_assoc($query);
        $chainData = json_decode($row['setting_value'], true);
        mysqli_free_result($query);
        
        if ($chainData && is_array($chainData)) {
            echo json_encode([
                'success' => true,
                'chain' => $chainData
            ]);
            return;
        }
    }
    
    if ($query) mysqli_free_result($query);
    
    // Return empty chain if not configured
    echo json_encode([
        'success' => true,
        'chain' => []
    ]);
}

/**
 * Add a new approval step to the chain
 */
function addApprovalStep($conDB) {
    $requestType = mysqli_real_escape_string($conDB, $_POST['request_type'] ?? '');
    $userType = mysqli_real_escape_string($conDB, $_POST['user_type'] ?? '');
    
    if (empty($requestType) || empty($userType)) {
        echo json_encode(['success' => false, 'message' => 'Request type and user type are required']);
        return;
    }

    // Get current chain
    $settingName = "approval_chain_{$requestType}";
    $query = mysqli_query($conDB, "SELECT setting_value FROM app_settings WHERE setting_name = '{$settingName}' LIMIT 1");
    
    $chain = [];
    $exists = false;
    
    if ($query && mysqli_num_rows($query) > 0) {
        $row = mysqli_fetch_assoc($query);
        $chain = json_decode($row['setting_value'], true) ?? [];
        $exists = true;
    }
    if ($query) mysqli_free_result($query);

    // Add new step at the end
    $newLevel = count($chain) + 1;
    $chain[] = [
        'level' => $newLevel,
        'user_type' => $userType,
        'role_label' => getRoleLabel($userType)
    ];

    $chainJson = json_encode($chain);
    $chainJsonEsc = mysqli_real_escape_string($conDB, $chainJson);

    if ($exists) {
        // Update existing setting
        $updateQuery = "UPDATE app_settings SET setting_value = '{$chainJsonEsc}' WHERE setting_name = '{$settingName}'";
        $result = mysqli_query($conDB, $updateQuery);
    } else {
        // Insert new setting
        $description = getRequestTypeDescription($conDB, $requestType);
        $insertQuery = "INSERT INTO app_settings (setting_name, setting_value, setting_group, description, input_type) 
                        VALUES ('{$settingName}', '{$chainJsonEsc}', 'approval', '{$description}', 'json')";
        $result = mysqli_query($conDB, $insertQuery);
    }

    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Approval step added successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . mysqli_error($conDB)]);
    }
}

/**
 * Remove an approval step from the chain
 */
function removeApprovalStep($conDB) {
    $requestType = mysqli_real_escape_string($conDB, $_POST['request_type'] ?? '');
    $level = (int)($_POST['level'] ?? 0);
    
    if (empty($requestType) || $level <= 0) {
        echo json_encode(['success' => false, 'message' => 'Request type and level are required']);
        return;
    }

    // Get current chain
    $settingName = "approval_chain_{$requestType}";
    $query = mysqli_query($conDB, "SELECT setting_value FROM app_settings WHERE setting_name = '{$settingName}' LIMIT 1");
    
    if (!$query || mysqli_num_rows($query) == 0) {
        if ($query) mysqli_free_result($query);
        echo json_encode(['success' => false, 'message' => 'Approval chain not found']);
        return;
    }

    $row = mysqli_fetch_assoc($query);
    $chain = json_decode($row['setting_value'], true) ?? [];
    mysqli_free_result($query);

    // Remove the step and renumber levels
    $newChain = [];
    $newLevel = 1;
    foreach ($chain as $step) {
        if ($step['level'] != $level) {
            $step['level'] = $newLevel++;
            $newChain[] = $step;
        }
    }

    $chainJson = json_encode($newChain);
    $chainJsonEsc = mysqli_real_escape_string($conDB, $chainJson);

    $updateQuery = "UPDATE app_settings SET setting_value = '{$chainJsonEsc}' WHERE setting_name = '{$settingName}'";
    $result = mysqli_query($conDB, $updateQuery);

    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Approval step removed successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . mysqli_error($conDB)]);
    }
}

/**
 * Update the order of approval steps
 */
function updateApprovalOrder($conDB) {
    $requestType = mysqli_real_escape_string($conDB, $_POST['request_type'] ?? '');
    $newOrder = $_POST['order'] ?? [];
    
    if (empty($requestType) || !is_array($newOrder)) {
        echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
        return;
    }

    // Rebuild chain with new order
    $chain = [];
    $level = 1;
    foreach ($newOrder as $userType) {
        $chain[] = [
            'level' => $level++,
            'user_type' => $userType,
            'role_label' => getRoleLabel($userType)
        ];
    }

    $settingName = "approval_chain_{$requestType}";
    $chainJson = json_encode($chain);
    $chainJsonEsc = mysqli_real_escape_string($conDB, $chainJson);

    $updateQuery = "UPDATE app_settings SET setting_value = '{$chainJsonEsc}' WHERE setting_name = '{$settingName}'";
    $result = mysqli_query($conDB, $updateQuery);

    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Approval order updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . mysqli_error($conDB)]);
    }
}

/**
 * Get human-readable label for user role
 */
function getRoleLabel($userType) {
    $labels = [
        'administrator' => 'Administrator',
        'gm' => 'General Manager (GM)',
        'hr_senior_bp' => 'HR Senior BP',
        'hr_operations' => 'HR Operations',
        'hr_supervisor' => 'HR Supervisor',
        'hr_recruitment' => 'HR Recruitment',
        'hr_payroll' => 'HR Payroll',
        'hr' => 'HR',
        'finance_officer' => 'Finance Officer',
        'finance' => 'Finance',
        'auditor' => 'Auditor',
        'gr_officer' => 'GR Officer',
        'it' => 'IT',
        'dept_user' => 'Department User',
        'employee' => 'Employee',
        'assistant' => 'Assistant',
        'direct_supervisor' => 'Direct Supervisor',
        'dept_manager' => 'Department Manager',
        'it_manager' => 'IT Manager',
        'admin_manager' => 'Admin Manager',
        'transportation_manager' => 'Transportation Manager'
    ];
    
    return $labels[$userType] ?? ucwords(str_replace('_', ' ', $userType));
}

/**
 * Get description for request type from database
 */
function getRequestTypeDescription($conDB, $requestType) {
    $requestTypeEsc = mysqli_real_escape_string($conDB, $requestType);
    $defaultDescription = 'Approval chain configuration';

    $query = mysqli_query($conDB, "SELECT description FROM approval_request_types WHERE id = '{$requestTypeEsc}' LIMIT 1");
    if ($query && mysqli_num_rows($query) > 0) {
        $row = mysqli_fetch_assoc($query);
        mysqli_free_result($query);
        return mysqli_real_escape_string($conDB, $row['description'] ?? $defaultDescription);
    }
    if ($query) mysqli_free_result($query);

    return mysqli_real_escape_string($conDB, $defaultDescription);
}

/**
 * Get all request types from database
 * Returns type_name as 'id' for backward compatibility with frontend
 */
function getAllRequestTypes($conDB) {
    $requestTypes = [];

    $query = mysqli_query($conDB, "SELECT type_name, description, main_table_name FROM approval_request_types WHERE is_active = 1 ORDER BY id ASC");
    
    if ($query && mysqli_num_rows($query) > 0) {
        while ($row = mysqli_fetch_assoc($query)) {
            $requestTypes[] = [
                'id' => $row['type_name'],
                'name' => ucwords(str_replace('_', ' ', $row['type_name'])),
                'description' => $row['description'] ?? '',
                'table' => $row['main_table_name'] ?? ''
            ];
        }
        mysqli_free_result($query);
    }
    
    echo json_encode([
        'success' => true,
        'types' => $requestTypes
    ]);
}

/**
 * Create a new custom request type with initial approval chain
 */
function createNewRequestType($conDB) {
    $requestTypeId = mysqli_real_escape_string($conDB, $_POST['request_type_id'] ?? '');
    $requestTypeName = mysqli_real_escape_string($conDB, $_POST['request_type_name'] ?? '');
    $description = mysqli_real_escape_string($conDB, $_POST['request_type_description'] ?? '');
    $mainTableName = mysqli_real_escape_string($conDB, $_POST['main_table_name'] ?? '');
    
    if (empty($requestTypeId) || empty($requestTypeName)) {
        echo json_encode(['success' => false, 'message' => 'Request Type ID and Name are required']);
        return;
    }
    
    // Validate format
    if (!preg_match('/^[a-z_]+$/', $requestTypeId)) {
        echo json_encode(['success' => false, 'message' => 'Request Type ID must contain only lowercase letters and underscores']);
        return;
    }
    
    try {
        // Check if already exists in approval_request_types (check by type_name)
        $checkQuery = mysqli_query($conDB, "SELECT id FROM approval_request_types WHERE type_name = '{$requestTypeId}' LIMIT 1");
        
        if ($checkQuery && mysqli_num_rows($checkQuery) > 0) {
            echo json_encode(['success' => false, 'message' => 'This request type already exists']);
            return;
        }
        if ($checkQuery) mysqli_free_result($checkQuery);
        
        // Insert new request type into approval_request_types table
        // id is auto-increment, so we don't specify it
        $insertTypeQuery = "INSERT INTO approval_request_types (type_name, main_table_name, description, is_default, is_active, created_at) 
                           VALUES ('{$requestTypeId}', '{$mainTableName}', '{$description}', 0, 1, NOW())";
        
        if (!mysqli_query($conDB, $insertTypeQuery)) {
            throw new Exception('Failed to create request type: ' . mysqli_error($conDB));
        }
        
        // Create empty approval chain in app_settings
        $settingName = "approval_chain_{$requestTypeId}";
        $emptyChain = json_encode([]);
        $emptyChainEsc = mysqli_real_escape_string($conDB, $emptyChain);
        
        $insertSettingQuery = "INSERT INTO app_settings (setting_name, setting_value, setting_group, description, input_type) 
                              VALUES ('{$settingName}', '{$emptyChainEsc}', 'approval', '{$description}', 'json')";
        
        if (!mysqli_query($conDB, $insertSettingQuery)) {
            throw new Exception('Failed to create approval chain setting: ' . mysqli_error($conDB));
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Request type created successfully',
            'request_type' => [
                'id' => $requestTypeId,
                'name' => $requestTypeName,
                'description' => $description,
                'table' => $mainTableName
            ]
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

