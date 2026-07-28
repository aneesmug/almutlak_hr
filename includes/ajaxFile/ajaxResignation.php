<?php
/**
 * Employee Resignation AJAX Handler
 * Handles all resignation-related AJAX requests
 * Created: 2025-11-25
 */
header('Content-Type: application/json');

try {
    require_once __DIR__ . '/../../includes/session_check.php';
    // Helper functions
    $helperFile = __DIR__ . '/../../includes/helper_functions.php';
    if (file_exists($helperFile)) {
        include($helperFile);
    }
    // Supervisor validation
    $supervisorFile = __DIR__ . '/../../includes/validate_supervisor.php';
    if (file_exists($supervisorFile)) {
        include($supervisorFile);
    }
} catch (Exception $e) {
    echo json_encode([
        'type' => 'error',
        'title' => 'System Error',
        'message' => 'Failed to load required files: ' . $e->getMessage()
    ]);
    exit;
}

$ajaxType = isset($_POST['ajaxType']) ? $_POST['ajaxType'] : '';

// Log AJAX request for debugging
error_log("ajaxResignation.php - AJAX Request: ajaxType = " . $ajaxType . " | POST = " . json_encode($_POST));

// Define helper functions if they don't exist
if (!function_exists('send_approval_email')) {
    function send_approval_email($conDB, $email, $name, $subject, $type, $data) {
        // Stub function - implement email sending logic here
        // For now, just log the email attempt
        error_log("Email would be sent to: $email - Subject: $subject");
        return true;
    }
}

if (!function_exists('create_browser_notification')) {
    function create_browser_notification($conDB, $userId, $title, $message, $link = '') {
        // Stub function - implement notification creation here
        $userId = mysqli_real_escape_string($conDB, $userId);
        $title = mysqli_real_escape_string($conDB, $title);
        $message = mysqli_real_escape_string($conDB, $message);
        $link = mysqli_real_escape_string($conDB, $link);
        
        $query = "INSERT INTO `notifications` (`user_id`, `title`, `message`, `link`, `created_at`, `is_read`) 
                  VALUES ('$userId', '$title', '$message', '$link', NOW(), 0)";
        return mysqli_query($conDB, $query);
    }
}

// validate_employee_supervisor is now defined in helper_functions.php
// and is loaded via the include above

if ($ajaxType == 'get_approval_level') {
    // ===== GET CURRENT APPROVAL LEVEL =====
    $resignationId = isset($_POST['resignation_id']) ? (int)$_POST['resignation_id'] : 0;
    
    if ($resignationId <= 0) {
        echo json_encode([
            'type' => 'error',
            'message' => 'Invalid resignation ID'
        ]);
        exit;
    }
    
    // Get current approver's level from session
    $approverId = isset($empid) ? $empid : '';
    
    if (empty($approverId)) {
        echo json_encode([
            'type' => 'error',
            'message' => 'Not authenticated'
        ]);
        exit;
    }
    
    // Get resignation and its request_inv_no
    $query = "SELECT `request_inv_no` FROM `emp_resignations` WHERE `id` = $resignationId LIMIT 1";
    $result = mysqli_query($conDB, $query);
    $resignation = mysqli_fetch_assoc($result);
    mysqli_free_result($result);
    
    if (!$resignation) {
        echo json_encode([
            'type' => 'error',
            'message' => 'Resignation not found'
        ]);
        exit;
    }
    
    $requestInvNo = $resignation['request_inv_no'];
    
    // Get current approver's level for this resignation
    $query = "SELECT `approval_level` FROM `request_approvers` 
              WHERE `request_inv_no` = '$requestInvNo' 
              AND `approver_id` = '$approverId'
              AND `status` IN ('pending','awaiting')
              LIMIT 1";
    
    $result = mysqli_query($conDB, $query);
    $approver = mysqli_fetch_assoc($result);
    mysqli_free_result($result);
    
    $currentLevel = $approver ? (int)$approver['approval_level'] : 0;
    
    echo json_encode([
        'success' => true,
        'approval_level' => $currentLevel
    ]);
    exit;
}

if ($ajaxType == 'get_replacement_data') {
    // ===== GET REPLACEMENT DATA FROM DIRECT MANAGER =====
    $resignationId = isset($_POST['resignation_id']) ? (int)$_POST['resignation_id'] : 0;
    
    if ($resignationId <= 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid resignation ID'
        ]);
        exit;
    }
    
    // Fetch replacement data from emp_resignations table
    $query = "SELECT `needs_replacement`, `replacement_data` FROM `emp_resignations` 
              WHERE `id` = $resignationId LIMIT 1";
    
    $result = mysqli_query($conDB, $query);
    
    if (!$result) {
        echo json_encode([
            'success' => false,
            'message' => 'Database error: ' . mysqli_error($conDB)
        ]);
        exit;
    }
    
    $replacementData = [];
    
    if ($row = mysqli_fetch_assoc($result)) {
        $replacementData['needs_replacement'] = $row['needs_replacement'];
        
        // If replacement data exists, parse JSON (handle double-encoded JSON too)
        if (!empty($row['replacement_data'])) {
            $raw = $row['replacement_data'];
            $parsed = json_decode($raw, true);
            if (is_string($parsed)) {
                // Older records may be double-encoded; decode again
                $parsedSecond = json_decode($parsed, true);
                if (is_array($parsedSecond)) {
                    $parsed = $parsedSecond;
                }
            }
            if (is_array($parsed)) {
                $replacementData = array_merge($replacementData, $parsed);
            }
        }
    }
    
    mysqli_free_result($result);
    
    echo json_encode([
        'success' => true,
        'data' => $replacementData
    ]);
    exit;
}

if ($ajaxType == 'get_exit_interview') {
    // ===== GET EXIT INTERVIEW DATA =====
    $resignationId = isset($_POST['resignation_id']) ? (int)$_POST['resignation_id'] : 0;
    
    if ($resignationId <= 0) {
        echo json_encode([
            'type' => 'error',
            'message' => 'Invalid resignation ID'
        ]);
        exit;
    }
    
    // Fetch exit interview data
    $query = "SELECT * FROM `emp_exit_interviews` 
              WHERE `resignation_id` = $resignationId 
              LIMIT 1";
    
    $result = mysqli_query($conDB, $query);
    
    if (!$result) {
        echo json_encode([
            'type' => 'error',
            'message' => 'Database error: ' . mysqli_error($conDB)
        ]);
        exit;
    }
    
    $exitInterviews = [];
    
    if ($row = mysqli_fetch_assoc($result)) {
        // Map questions to keys
        $answerKeys = ['q1_reasons', 'q2_support', 'q3_resources', 'q4_manager', 'q5_growth', 'q6_compensation', 'q7_different', 'q8_recommend', 'q9_additional'];
        
        foreach ($answerKeys as $key) {
            $exitInterviews[$key] = $row[$key] ?? 'No answer provided';
        }
    }
    
    mysqli_free_result($result);
    
    echo json_encode([
        'success' => true,
        'message' => 'Exit interview data retrieved',
        'data' => $exitInterviews
    ]);
    exit;
}

if ($ajaxType == 'apply_resignation') {
    // ===== EMPLOYEE RESIGNATION SUBMISSION =====
    try {
        error_log("ajaxResignation: apply_resignation - START");
        
        // Get POST data
        $empId = isset($_POST['emp_id']) ? mysqli_real_escape_string($conDB, $_POST['emp_id']) : '';
        error_log("ajaxResignation: apply_resignation - empId = " . $empId);

        // Block-check: employee may be restricted from submitting resignation requests
        require_once __DIR__ . '/../special_access_helper.php';
        $block_status = is_employee_request_blocked($conDB, $empId, 'resignation_request');
        if ($block_status['blocked']) {
            echo json_encode([
                'type' => 'error',
                'title' => 'Request Blocked',
                'message' => $block_status['reason']
            ]);
            exit;
        }

        // Validate supervisor assignment FIRST
        if (function_exists('validate_employee_supervisor')) {
            $supervisor_check = validate_employee_supervisor($conDB, $empId);
            if (!$supervisor_check['valid']) {
                error_log("ajaxResignation: apply_resignation - supervisor validation failed");
                if (function_exists('send_supervisor_validation_error')) {
                    send_supervisor_validation_error($supervisor_check['message']);
                } else {
                    echo json_encode([
                        'type' => 'error',
                        'title' => 'Supervisor Required',
                        'message' => $supervisor_check['message']
                    ]);
                    exit;
                }
            }
        }
        
        $lastWorkingDay = isset($_POST['last_working_day']) ? mysqli_real_escape_string($conDB, $_POST['last_working_day']) : '';
        $exitInterviewJson = isset($_POST['exit_interview']) ? $_POST['exit_interview'] : '';
        $rejectionReason = isset($_POST['rejection_reason']) ? mysqli_real_escape_string($conDB, $_POST['rejection_reason']) : '';
        
        error_log("ajaxResignation: apply_resignation - lastWorkingDay = " . $lastWorkingDay . ", exitInterviewJson = " . substr($exitInterviewJson, 0, 50));
        
        // Validate required fields
        if (empty($empId) || empty($lastWorkingDay) || empty($exitInterviewJson) || empty($rejectionReason)) {
            error_log("ajaxResignation: apply_resignation - validation failed - empId:" . empty($empId) . ", lastWorkingDay:" . empty($lastWorkingDay) . ", exitInterview:" . empty($exitInterviewJson));
            echo json_encode([
                'type' => 'error',
                'title' => 'Validation Error',
                'message' => 'Please fill in all required fields.'
            ]);
            exit;
        }
        
        error_log("ajaxResignation: apply_resignation - validation passed");
        
        // Validate employee exists and is active
        $empCheck = mysqli_query($conDB, "SELECT e.`emp_id`, e.`name`, al.`email` FROM `employees` e 
                                         LEFT JOIN `admin_login` al ON al.`emp_id` = e.`emp_id`
                                         WHERE e.`emp_id` = '$empId' AND e.`status` = 1");
        if (!$empCheck || mysqli_num_rows($empCheck) == 0) {
            error_log("ajaxResignation: apply_resignation - employee check failed");
            echo json_encode([
                'type' => 'error',
                'title' => 'Error',
                'message' => 'Employee not found or inactive.'
            ]);
            exit;
        }
        $empData = mysqli_fetch_assoc($empCheck);
        mysqli_free_result($empCheck);
        
        // Validate date is in the future
        $today = date('Y-m-d');
        if ($lastWorkingDay <= $today) {
            echo json_encode([
                'type' => 'error',
                'title' => 'Invalid Date',
                'message' => 'Last working day must be a future date.'
            ]);
            exit;
        }
        
        // Check if employee already has a pending resignation
        $pendingCheck = mysqli_query($conDB, "SELECT `id` FROM `emp_resignations` 
            WHERE `emp_id` = '$empId' AND `status` IN ('pending', 'approved')");
        if ($pendingCheck && mysqli_num_rows($pendingCheck) > 0) {
            mysqli_free_result($pendingCheck);
            echo json_encode([
                'type' => 'warning',
                'title' => 'Duplicate Submission',
                'message' => 'You already have an active resignation request.'
            ]);
            exit;
        }
        if ($pendingCheck) {
            mysqli_free_result($pendingCheck);
        }
        
        // Decode and validate exit interview data
        $exitInterview = json_decode($exitInterviewJson, true);
        if (!$exitInterview || !is_array($exitInterview)) {
            echo json_encode([
                'type' => 'error',
                'title' => 'Invalid Data',
                'message' => 'Exit interview data is invalid.'
            ]);
            exit;
        }
        
        // Validate all exit interview questions are answered
        $requiredQuestions = ['q1_reasons', 'q2_support', 'q3_resources', 'q4_manager', 
                             'q5_growth', 'q6_compensation', 'q7_different', 'q8_recommend', 'q9_additional'];
        foreach ($requiredQuestions as $question) {
            if (empty($exitInterview[$question])) {
                echo json_encode([
                    'type' => 'error',
                    'title' => 'Incomplete Data',
                    'message' => 'Please answer all exit interview questions.'
                ]);
                exit;
            }
        }
        
        // Prepare data
        $submissionDate = date('Y-m-d H:i:s');
        
        // Generate unique request_inv_no
        $requestInvNo = 'RES-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
        
        error_log("ajaxResignation: apply_resignation - requestInvNo = " . $requestInvNo);
        
        // Insert resignation record
        $insertResignation = "INSERT INTO `emp_resignations` 
            (`emp_id`, `request_inv_no`, `last_working_day`, `submission_date`, `status`, `created_at`, `updated_at`, `rejection_reason`) 
            VALUES 
            ('$empId', '$requestInvNo', '$lastWorkingDay', '$submissionDate', 'pending', NOW(), NOW(), '$rejectionReason')";
        
        error_log("ajaxResignation: apply_resignation - about to INSERT resignation");
        
        $resignationResult = mysqli_query($conDB, $insertResignation);
        
        if (!$resignationResult) {
            $dbError = mysqli_error($conDB);
            error_log("Resignation insert error: " . $dbError);
            echo json_encode([
                'type' => 'error',
                'title' => 'Database Error',
                'message' => 'Failed to save resignation. Please try again.',
                'debug_info' => (defined('DEBUG_MODE') && constant('DEBUG_MODE') === true ? $dbError : null)
            ]);
            exit;
        }
        
        // Get the inserted resignation ID
        $resignationId = mysqli_insert_id($conDB);
        
        // Log resignation submission - wrap in try-catch to prevent breaking flow
        try {
            if (class_exists('ActivityLogger')) {
                ActivityLogger::logSubmit('Resignation', 'ajaxResignation.php', $resignationId, "Submitted resignation request: {$requestInvNo}, Last Working Day: {$lastWorkingDay}", 'emp_resignations');
                error_log("ajaxResignation: ActivityLogger::logSubmit() succeeded");
            } else {
                error_log("WARNING: ActivityLogger class not found, skipping activity logging");
            }
        } catch (Exception $e) {
            error_log("WARNING: ActivityLogger::logSubmit() failed: " . $e->getMessage() . " | Line: " . $e->getLine());
            // Don't fail the whole submission - just log the error
        }
        
        // Prepare exit interview answers (escape all inputs)
        $q1 = mysqli_real_escape_string($conDB, $exitInterview['q1_reasons']);
        $q2 = mysqli_real_escape_string($conDB, $exitInterview['q2_support']);
        $q3 = mysqli_real_escape_string($conDB, $exitInterview['q3_resources']);
        $q4 = mysqli_real_escape_string($conDB, $exitInterview['q4_manager']);
        $q5 = mysqli_real_escape_string($conDB, $exitInterview['q5_growth']);
        $q6 = mysqli_real_escape_string($conDB, $exitInterview['q6_compensation']);
        $q7 = mysqli_real_escape_string($conDB, $exitInterview['q7_different']);
        $q8 = mysqli_real_escape_string($conDB, $exitInterview['q8_recommend']);
        $q9 = mysqli_real_escape_string($conDB, $exitInterview['q9_additional']);
        
        error_log("ajaxResignation: apply_resignation - prepared exit interview answers");
        
        // Insert exit interview
        $insertExitInterview = "INSERT INTO `emp_exit_interviews` 
            (`resignation_id`, `emp_id`, `q1_reasons`, `q2_support`, `q3_resources`, 
             `q4_manager`, `q5_growth`, `q6_compensation`, `q7_different`, 
             `q8_recommend`, `q9_additional`, `submitted_at`, `created_at`) 
            VALUES 
            ('$resignationId', '$empId', '$q1', '$q2', '$q3', '$q4', '$q5', 
             '$q6', '$q7', '$q8', '$q9', NOW(), NOW())";
        
        error_log("ajaxResignation: apply_resignation - about to INSERT exit interview");
        $exitInterviewResult = mysqli_query($conDB, $insertExitInterview);
        
        if (!$exitInterviewResult) {
            // Rollback: Delete the resignation record if exit interview fails
            mysqli_query($conDB, "DELETE FROM `emp_resignations` WHERE `id` = '$resignationId'");
            $dbError = mysqli_error($conDB);
            error_log("Exit interview insert error: " . $dbError);
            echo json_encode([
                'type' => 'error',
                'title' => 'Database Error',
                'message' => 'Failed to save exit interview. Please try again.',
                'debug_info' => (defined('DEBUG_MODE') && constant('DEBUG_MODE') === true ? $dbError : null)
            ]);
            exit;
        }
        
        error_log("ajaxResignation: apply_resignation - exit interview INSERT succeeded");
        
        // History is tracked via request_approvers table and ActivityLogger - no additional history table needed
        
        // ===== CREATE APPROVAL CHAIN using ApprovalChainManager =====
        error_log("ajaxResignation: apply_resignation - starting approval chain creation");

        $firstApproverId = null;
        $firstApproverLabel = null;

        try {
            require_once __DIR__ . '/../ApprovalChainManager.php';
            
            // Get employee's department
            $dept_query = mysqli_query($conDB, "SELECT dept FROM employees WHERE emp_id = '$empId' LIMIT 1");
            $dept_row = mysqli_fetch_assoc($dept_query);
            $empDept = $dept_row ? $dept_row['dept'] : null;
            if ($dept_query) mysqli_free_result($dept_query);
            
            $chainManager = new ApprovalChainManager($conDB, $pdo, new ActivityLogger());
            
            $chainResult = $chainManager->createApprovalChain(
                'resignation_request',
                $requestInvNo,
                $empId,
                $empDept
            );
            
            if (!$chainResult['success']) {
                throw new Exception('Failed to create approval chain: ' . ($chainResult['message'] ?? 'Unknown error'));
            }
            
            $first_approver = $chainResult['first_approver'];
            $firstApproverId = $first_approver['approver_id'];
            $firstApproverLabel = $first_approver['role_label'] ?? 'Approver';

            error_log("ajaxResignation: apply_resignation - approval chain creation completed");

        } catch (Exception $chainException) {
            error_log("ERROR in approval chain creation: " . $chainException->getMessage());
            // Rollback created records (exit interview + resignation)
            if (!empty($resignationId)) {
                mysqli_query($conDB, "DELETE FROM `emp_exit_interviews` WHERE `resignation_id` = " . (int)$resignationId);
                mysqli_query($conDB, "DELETE FROM `emp_resignations` WHERE `id` = " . (int)$resignationId);
            }
            echo json_encode([
                'type' => 'error',
                'title' => 'Approval Chain Not Configured',
                'message' => $chainException->getMessage()
            ]);
            exit;
        }

        // ===== SEND NOTIFICATIONS TO FIRST APPROVER using ApprovalChainManager =====
        try {
            if ($firstApproverId) {
                // Get first approver details
                $approverDetailsQuery = "SELECT `e`.`name`, `al`.`email` 
                                          FROM `employees` `e` 
                                          LEFT JOIN `admin_login` `al` ON `al`.`emp_id` = `e`.`emp_id`
                                          WHERE `e`.`emp_id` = ? AND `e`.`status` = 1";
                $approverStmt = $conDB->prepare($approverDetailsQuery);
                $approver = null;
                if ($approverStmt) {
                    $approverStmt->bind_param('i', $firstApproverId);
                    $approverStmt->execute();
                    $approverResult = $approverStmt->get_result();
                    $approver = $approverResult ? $approverResult->fetch_assoc() : null;
                    if ($approverResult) {
                        $approverResult->free();
                    }
                    $approverStmt->close();
                }

                if (!empty($approver) && !empty($approver['email'])) {
                    error_log("ajaxResignation: first approver details retrieved (emp_id: {$firstApproverId})");

                    // Get employee dept and job for email
                    $empDetailsQuery = "SELECT `d`.`dep_nme`, `j`.`job` 
                                       FROM `employees` `e`
                                       LEFT JOIN `department` `d` ON `d`.`id` = `e`.`dept`
                                       LEFT JOIN `ac_jobs` `j` ON `j`.`id` = `e`.`actual_job`
                                       WHERE `e`.`emp_id` = '$empId'";
                    $empDetailsResult = mysqli_query($conDB, $empDetailsQuery);
                    $empDetails = mysqli_fetch_assoc($empDetailsResult);
                    mysqli_free_result($empDetailsResult);

                    $approvalLevelName = $firstApproverLabel ?: 'Approver';

                    // Send email notification using load_email_template
                    $emailData = [
                        'EMP_ID' => $empId,
                        'EMP_NAME' => $empData['name'],
                        'DEPARTMENT' => $empDetails['dep_nme'] ?? '',
                        'DESIGNATION' => $empDetails['job'] ?? '',
                        'REQUEST_ID' => $requestInvNo,
                        'RESIGNATION_ID' => $requestInvNo,
                        'LAST_WORKING_DAY' => $lastWorkingDay,
                        'SUBMISSION_DATE' => $submissionDate,
                        'APPROVER_NAME' => $approver['name'],
                        'approval_level' => 1,
                        'approval_level_name' => $approvalLevelName
                    ];

                    send_approval_email(
                        $conDB,
                        $approver['email'],
                        $approver['name'],
                        'Employee Resignation Request - Action Required (Level 1: ' . $approvalLevelName . ')',
                        'resignation_request',
                        $emailData
                    );

                    error_log("ajaxResignation: approval email sent to first approver");

                    // Create browser notification using ApprovalChainManager
                    $chainManager->notifyApprover(
                        $firstApproverId,
                        'Resignation Request Requires Your Approval',
                        $empData['name'] . ' has submitted a resignation request. Please review and approve/reject.',
                        'all_resignations.php?inv=' . $requestInvNo
                    );

                    error_log("ajaxResignation: browser notification created for first approver");
                }
            }
        } catch (Exception $notificationException) {
            error_log("WARNING: Failed to send notifications: " . $notificationException->getMessage());
            // Don't fail the whole submission - notifications are secondary
        }
        
        error_log("ajaxResignation: apply_resignation - SUCCESS, about to send success response");
        
        // Success response
        echo json_encode([
            'type' => 'success',
            'title' => 'Resignation Submitted',
            'message' => 'Your resignation has been submitted successfully. HR will review your request and contact you soon.'
        ]);
        
    } catch (Exception $e) {
        $errorDetails = 'Error: ' . $e->getMessage() . ' | File: ' . $e->getFile() . ' | Line: ' . $e->getLine();
        error_log("ajaxResignation: apply_resignation - EXCEPTION CAUGHT: " . $errorDetails);
        error_log("Stack Trace: " . $e->getTraceAsString());
        
        echo json_encode([
            'type' => 'error',
            'title' => 'System Error',
            'message' => 'An unexpected error occurred. Please contact IT support.',
            'error_detail' => $e->getMessage(),
            'error_file' => $e->getFile(),
            'error_line' => $e->getLine(),
            'debug_info' => (defined('DEBUG_MODE') && constant('DEBUG_MODE') === true ? $dbError : null)
        ]);
    }
    exit;
    
} elseif ($ajaxType == 'get_resignation_status') {
    // ===== GET EMPLOYEE RESIGNATION STATUS =====
    $empId = isset($_POST['emp_id']) ? mysqli_real_escape_string($conDB, $_POST['emp_id']) : '';
    
    if (empty($empId)) {
        echo json_encode([
            'type' => 'error',
            'message' => 'Employee ID is required.'
        ]);
        exit;
    }
    
    $query = "SELECT `r`.*, `e`.`name` as `emp_name` 
              FROM `emp_resignations` `r` 
              LEFT JOIN `employees` `e` ON `e`.`emp_id` = `r`.`emp_id`
              WHERE `r`.`emp_id` = '$empId' 
              AND `r`.`status` IN ('pending', 'approved')
              ORDER BY `r`.`created_at` DESC 
              LIMIT 1";
    
    $result = mysqli_query($conDB, $query);
    
    if ($result && mysqli_num_rows($result) > 0) {
        $resignation = mysqli_fetch_assoc($result);
        mysqli_free_result($result);
        
        echo json_encode([
            'type' => 'success',
            'has_resignation' => true,
            'data' => $resignation
        ]);
    } else {
        echo json_encode([
            'type' => 'success',
            'has_resignation' => false
        ]);
    }
    exit;
    
} elseif ($ajaxType == 'cancel_resignation') {
    // ===== CANCEL PENDING RESIGNATION =====
    $resignationId = isset($_POST['resignation_id']) ? (int)$_POST['resignation_id'] : 0;
    $empId = isset($_POST['emp_id']) ? mysqli_real_escape_string($conDB, $_POST['emp_id']) : '';
    
    if ($resignationId <= 0 || empty($empId)) {
        echo json_encode([
            'type' => 'error',
            'title' => 'Invalid Request',
            'message' => 'Invalid resignation ID or employee ID.'
        ]);
        exit;
    }
    
    // Check if resignation belongs to current user and is pending
    $checkQuery = "SELECT `id`, `status` FROM `emp_resignations` 
                   WHERE `id` = $resignationId AND `emp_id` = '$empId' AND `status` = 'pending'";
    $checkResult = mysqli_query($conDB, $checkQuery);
    
    if (!$checkResult || mysqli_num_rows($checkResult) == 0) {
        echo json_encode([
            'type' => 'error',
            'title' => 'Invalid Request',
            'message' => 'Resignation not found or cannot be cancelled.'
        ]);
        exit;
    }
    mysqli_free_result($checkResult);
    
    // Update resignation status to cancelled
    $updateQuery = "UPDATE `emp_resignations` 
                    SET `status` = 'cancelled', `updated_at` = NOW() 
                    WHERE `id` = $resignationId";
    
    if (mysqli_query($conDB, $updateQuery)) {
        // Log the cancellation
        try {
            if (class_exists('ActivityLogger')) {
                ActivityLogger::logSubmit('Resignation', 'ajaxResignation.php', $resignationId, "Cancelled resignation request by employee {$empId}", 'emp_resignations');
            }
        } catch (Exception $e) {
            error_log("WARNING: Failed to log resignation cancellation: " . $e->getMessage());
        }
        
        // History is tracked via request_approvers table - no additional history table needed
        
        echo json_encode([
            'type' => 'success',
            'title' => 'Cancelled',
            'message' => 'Your resignation has been cancelled successfully.'
        ]);
    } else {
        echo json_encode([
            'type' => 'error',
            'title' => 'Database Error',
            'message' => 'Failed to cancel resignation. Please try again.'
        ]);
    }
    exit;

} elseif ($ajaxType == 'cancelResignationAdmin') {
    // ===== HR/ADMIN-INITIATED CANCELLATION OF ANY EMPLOYEE'S RESIGNATION =====
    require_once __DIR__ . '/../special_access_helper.php';

    $can_cancel_any = (
        !empty($is_system_admin)
        || user_has_special_access($conDB, $empid ?? '', 'cancel_resignation_requests', $user_role ?? '', $user_type ?? '', $is_system_admin ?? false)
    );
    if (!$can_cancel_any) {
        echo json_encode(['type' => 'error', 'title' => 'Error', 'message' => __('access_denied', 'Access denied')]);
        exit;
    }

    $resignationId = isset($_POST['resignation_id']) ? (int)$_POST['resignation_id'] : 0;
    $cancellationNote = trim((string)($_POST['cancellation_note'] ?? ''));
    if ($resignationId <= 0) {
        echo json_encode(['type' => 'error', 'title' => 'Invalid Request', 'message' => 'Invalid resignation ID.']);
        exit;
    }

    $checkQuery = "SELECT `id`, `emp_id`, `request_inv_no`, `status` FROM `emp_resignations` WHERE `id` = ?";
    $checkStmt = mysqli_prepare($conDB, $checkQuery);
    mysqli_stmt_bind_param($checkStmt, 'i', $resignationId);
    mysqli_stmt_execute($checkStmt);
    $checkResult = mysqli_stmt_get_result($checkStmt);
    $resignation = $checkResult ? mysqli_fetch_assoc($checkResult) : null;
    mysqli_stmt_close($checkStmt);

    if (!$resignation) {
        echo json_encode(['type' => 'error', 'title' => 'Invalid Request', 'message' => 'Resignation not found.']);
        exit;
    }

    if (!in_array($resignation['status'], ['pending', 'approved'], true)) {
        echo json_encode(['type' => 'error', 'title' => 'Invalid Request', 'message' => 'Resignation in status "' . $resignation['status'] . '" cannot be cancelled.']);
        exit;
    }

    $reasonSuffix = $cancellationNote !== '' ? (' Reason: ' . $cancellationNote) : '';
    $updateStmt = mysqli_prepare($conDB, "UPDATE `emp_resignations` SET `status` = 'cancelled', `rejection_reason` = CONCAT(COALESCE(`rejection_reason`, ''), ?), `updated_at` = NOW() WHERE `id` = ? AND `status` = ?");
    $logNote = "\nCancelled by admin (emp_id {$empid}) on behalf of employee {$resignation['emp_id']}.{$reasonSuffix}";
    mysqli_stmt_bind_param($updateStmt, 'sis', $logNote, $resignationId, $resignation['status']);
    mysqli_stmt_execute($updateStmt);
    $affected = mysqli_stmt_affected_rows($updateStmt);
    mysqli_stmt_close($updateStmt);

    if ($affected <= 0) {
        echo json_encode(['type' => 'error', 'title' => 'Error', 'message' => 'Failed to cancel resignation - status may have changed.']);
        exit;
    }

    try {
        if (class_exists('ActivityLogger')) {
            ActivityLogger::logSubmit('Resignation', 'ajaxResignation.php', $resignationId, "Resignation cancelled by admin/HR (emp_id {$empid}) on behalf of employee {$resignation['emp_id']}", 'emp_resignations');
        }
    } catch (Exception $e) {
        error_log("WARNING: Failed to log admin resignation cancellation: " . $e->getMessage());
    }

    if (function_exists('create_browser_notification')) {
        create_browser_notification(
            $conDB,
            $resignation['emp_id'],
            'Resignation Request Cancelled',
            'Your resignation request ' . htmlspecialchars($resignation['request_inv_no']) . ' was cancelled by an administrator.' . htmlspecialchars($reasonSuffix),
            'view_employee.php?id=' . urlencode($resignation['emp_id'])
        );
    }

    echo json_encode(['type' => 'success', 'title' => 'Cancelled', 'message' => 'Resignation request has been cancelled successfully.']);
    exit;

} elseif ($ajaxType == 'approve_resignation') {
    // ===== APPROVE RESIGNATION using ApprovalChainManager =====
    try {
        error_log("Approval process started");
        
        $resignationId = isset($_POST['resignation_id']) ? (int)$_POST['resignation_id'] : 0;
        $invNo = isset($_POST['inv_no']) ? mysqli_real_escape_string($conDB, $_POST['inv_no']) : '';
        $needsReplacement = isset($_POST['needs_replacement']) ? (int)$_POST['needs_replacement'] : 0;
        $replacementData = isset($_POST['replacement_data']) ? $_POST['replacement_data'] : null;
        $approval_comment = isset($_POST['approval_comment']) ? trim($_POST['approval_comment']) : '';
        
        error_log("Parameters - ID: $resignationId, InvNo: $invNo, Replacement: $needsReplacement");
        
        // Get resignation details
        $resignationQuery = "SELECT r.*, e.emp_id, e.name AS emp_name, 
                        COALESCE(d.dep_nme, 'Unknown') AS department,
                        COALESCE(j.job, 'Unknown') AS designation,
                        e.supervisor_id, r.request_inv_no
                    FROM emp_resignations r
                    LEFT JOIN employees e ON e.emp_id = r.emp_id
                    LEFT JOIN department d ON d.id = e.dept
                    LEFT JOIN ac_jobs j ON j.id = e.actual_job
                    WHERE " . ($resignationId > 0 ? "r.id = $resignationId" : "r.request_inv_no = '$invNo'") . " 
                    AND r.status = 'pending'";
        
        error_log("Resignation query: " . $resignationQuery);
        $resignationResult = mysqli_query($conDB, $resignationQuery);
        
        if (!$resignationResult) {
            $dbError = mysqli_error($conDB);
            error_log("Database query error: " . $dbError);
            echo json_encode([
                'type' => 'error',
                'title' => 'Database Error',
                'message' => 'Database query failed: ' . $dbError
            ]);
            exit;
        }
        
        if (mysqli_num_rows($resignationResult) == 0) {
            echo json_encode([
                'type' => 'error',
                'title' => 'Not Found',
                'message' => 'Resignation not found or already processed.'
            ]);
            exit;
        }
        
        $resignation = mysqli_fetch_assoc($resignationResult);
        mysqli_free_result($resignationResult);
        $resignationId = $resignation['id'];
        $requestInvNo = $resignation['request_inv_no'];
        
        // Current user (approver) from session_check.php globals
        $approverId = isset($empid) ? $empid : '';
        $approverType = isset($user_type) ? $user_type : '';
        
        if (empty($approverId)) {
            echo json_encode([
                'type' => 'error',
                'title' => 'Unauthorized',
                'message' => 'You must be logged in to approve resignations.'
            ]);
            exit;
        }
        
        // Use ApprovalChainManager to verify and process approval
        require_once __DIR__ . '/../ApprovalChainManager.php';
        $chainManager = new ApprovalChainManager($conDB, $pdo, new ActivityLogger());
        
        // Verify approver (must be current pending approver for this request)
        $verifyResult = $chainManager->verifyApprover($requestInvNo, $approverId);
        if (!is_array($verifyResult) || empty($verifyResult['authorized'])) {
            echo json_encode([
                'type' => 'error',
                'title' => 'Unauthorized',
                'message' => $verifyResult['message'] ?? 'You are not an authorized approver for this resignation.'
            ]);
            exit;
        }
        
        // NOTE: Approval authorization is controlled by approval-chain verification and processing.
        /*
        LEGACY (kept as comment per request): scope-based approval restriction check.
        // ===== CHECK ACCESS CONTROL for approver =====
        // Get approver's allowed scope restrictions
        $approverQuery = "SELECT allowed_companies, allowed_departments, allowed_employees FROM admin_login WHERE emp_id = '$approverId'";
        $approverResult = mysqli_query($conDB, $approverQuery);
        $approverData = mysqli_fetch_assoc($approverResult);
        mysqli_free_result($approverResult);

        if ($approverData) {
            // Check allowed companies
            $allowedCompanies = !empty($approverData['allowed_companies']) ? json_decode($approverData['allowed_companies'], true) : null;
            $allowedDepts = !empty($approverData['allowed_departments']) ? json_decode($approverData['allowed_departments'], true) : null;
            $allowedEmps = !empty($approverData['allowed_employees']) ? json_decode($approverData['allowed_employees'], true) : null;

            // Get employee's company and department
            $empScopeQuery = "SELECT comp_no, dept, emp_id FROM employees WHERE emp_id = '" . mysqli_real_escape_string($conDB, $resignation['emp_id']) . "'";
            $empScopeResult = mysqli_query($conDB, $empScopeQuery);
            $empScope = mysqli_fetch_assoc($empScopeResult);
            mysqli_free_result($empScopeResult);

            $hasAccess = true;

            // If approver has company restrictions, check if employee is in allowed companies
            if (is_array($allowedCompanies) && !empty($allowedCompanies) && is_array($empScope)) {
                if (!in_array($empScope['comp_no'], $allowedCompanies)) {
                    $hasAccess = false;
                }
            }

            // If approver has department restrictions, check if employee is in allowed departments
            if ($hasAccess && is_array($allowedDepts) && !empty($allowedDepts) && is_array($empScope)) {
                if (!in_array($empScope['dept'], $allowedDepts)) {
                    $hasAccess = false;
                }
            }

            // If approver has employee restrictions, check if employee is in allowed employees
            if ($hasAccess && is_array($allowedEmps) && !empty($allowedEmps)) {
                $empId = (int)$empScope['emp_id'];
                if (!in_array($empId, array_map('intval', $allowedEmps))) {
                    $hasAccess = false;
                }
            }

            if (!$hasAccess) {
                echo json_encode([
                    'type' => 'error',
                    'title' => 'Access Denied',
                    'message' => 'This employee is outside your approval scope. You are not authorized to approve this resignation.'
                ]);
                exit;
            }
        }
        */
        
        // Get approver details
        $approverQuery = "SELECT `fullname`, `email` FROM `admin_login` WHERE `emp_id` = '$approverId'";
        $approverResult = mysqli_query($conDB, $approverQuery);
        $approverData = mysqli_fetch_assoc($approverResult);
        $approverName = $approverData ? $approverData['fullname'] : 'Unknown';
        mysqli_free_result($approverResult);
        
        // Process the approval using ApprovalChainManager
        $approvalResult = $chainManager->processApproval(
            $requestInvNo,
            $approverId,
            'approve',
            $approval_comment ?: 'Approved'
        );
        
        $isFinalApproval = $approvalResult['is_final'];
        $nextApprover = $approvalResult['next_approver'] ?? null;
        
        // Get approval level for this approver
        $levelQuery = "SELECT approval_level FROM request_approvers 
                      WHERE request_inv_no = '$requestInvNo' AND approver_id = '$approverId' 
                      ORDER BY action_date DESC LIMIT 1";
        $levelResult = mysqli_query($conDB, $levelQuery);
        $levelRow = mysqli_fetch_assoc($levelResult);
        $approvalLevel = $levelRow ? $levelRow['approval_level'] : 1;
        mysqli_free_result($levelResult);
        
        // Build UPDATE query for resignation
        $updateQueryParts = [];
        
        // Set status if final approval
        if ($isFinalApproval) {
            $updateQueryParts[] = "`status` = 'approved'";
        }
        
        // Update replacement fields for Level 1 (Direct Supervisor)
        if ($approvalLevel == 1) {
            $updateQueryParts[] = "`needs_replacement` = '" . (int)$needsReplacement . "'";
            if (!empty($replacementData)) {
                $replacementJson = null;
                if (is_array($replacementData)) {
                    $replacementJson = json_encode($replacementData);
                } else {
                    // Expecting a JSON string from frontend; validate and normalize
                    $decoded = json_decode($replacementData, true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                        $replacementJson = json_encode($decoded);
                    }
                }
                if (!empty($replacementJson)) {
                    $updateQueryParts[] = "`replacement_data` = '" . mysqli_real_escape_string($conDB, $replacementJson) . "'";
                }
            }
        }
        
        // Update HR last working day for Level 2 (HR Operations)
        if ($approvalLevel == 2) {
            $hrLastWorkingDay = isset($_POST['hr_last_working_day']) ? mysqli_real_escape_string($conDB, $_POST['hr_last_working_day']) : '';
            if ($hrLastWorkingDay) {
                $updateQueryParts[] = "`hr_last_working_day` = '$hrLastWorkingDay'";
                error_log("HR last working day received: $hrLastWorkingDay");
            }
        }
        
        // Always update timestamp
        $updateQueryParts[] = "`updated_at` = NOW()";
        
        // Execute update
        if (!empty($updateQueryParts)) {
            $updateQuery = "UPDATE `emp_resignations` 
                           SET " . implode(', ', $updateQueryParts) . "
                           WHERE `id` = $resignationId";
        } else {
            $updateQuery = "UPDATE `emp_resignations` SET `updated_at` = NOW() WHERE `id` = $resignationId";
        }
        
        if (!mysqli_query($conDB, $updateQuery)) {
            $dbErr = mysqli_error($conDB);
            error_log("Resignation update failed: $dbErr");
            echo json_encode([
                'type' => 'error',
                'title' => 'Database Error',
                'message' => 'Failed to update resignation status.'
            ]);
            exit;
        }
        
        // Log the resignation approval
        ActivityLogger::logApproval('Resignation', 'ajaxResignation.php', $resignationId, 'approved', "Approved resignation request: {$requestInvNo}, Level: {$approvalLevel}, Final: " . ($isFinalApproval ? 'Yes' : 'No'), 'emp_resignations');
        
        // Save approval comment if provided
        if (!empty($approval_comment) && function_exists('save_approval_comment_db')) {
            save_approval_comment_db(
                $conDB,
                $requestInvNo,
                'resignation',
                'approved',
                $approverId,
                $approverName,
                $approval_comment,
                $approvalLevel,
                $approverId
            );
        }
        
        // Send notification to next approver using ApprovalChainManager
        if ($nextApprover && !empty($nextApprover['approver_id'])) {
            // Get next approver details
            $nextApproverQuery = "SELECT e.name as fullname, al.email 
                                 FROM employees e 
                                 LEFT JOIN admin_login al ON e.emp_id = al.emp_id 
                                 WHERE e.emp_id = " . $nextApprover['approver_id'];
            $nextApproverResult = mysqli_query($conDB, $nextApproverQuery);
            $nextApproverDetails = mysqli_fetch_assoc($nextApproverResult);
            mysqli_free_result($nextApproverResult);
            
            if ($nextApproverDetails && !empty($nextApproverDetails['email'])) {
                // Send email
                $emailData = [
                    'EMP_ID' => $resignation['emp_id'],
                    'EMP_NAME' => $resignation['emp_name'],
                    'DEPARTMENT' => $resignation['department'] ?? '',
                    'DESIGNATION' => $resignation['designation'] ?? '',
                    'RESIGNATION_ID' => $requestInvNo,
                    'LAST_WORKING_DAY' => isset($resignation['last_working_day']) ? date('d M Y', strtotime($resignation['last_working_day'])) : 'N/A',
                    'SUBMISSION_DATE' => isset($resignation['submission_date']) ? date('d M Y H:i', strtotime($resignation['submission_date'])) : 'N/A',
                    'APPROVER_NAME' => $nextApproverDetails['fullname'],
                    'REQUEST_URL' => 'https://hr.almutlaksystem.com/all_resignations.php'
                ];
                
                send_approval_email(
                    $conDB,
                    $nextApproverDetails['email'],
                    $nextApproverDetails['fullname'],
                    'Employee Resignation Request - Action Required (Level ' . ($approvalLevel + 1) . ')',
                    'resignation_request',
                    $emailData
                );
                
                // Send browser notification
                $chainManager->notifyApprover(
                    $nextApprover['approver_id'],
                    'Resignation Requires Your Approval',
                    $resignation['emp_name'] . "'s resignation has been forwarded to you for approval.",
                    'all_resignations.php?inv=' . $requestInvNo
                );
            }
        }
        
        // If final approval, notify employee, HR team, and GR officer if applicable
        if ($isFinalApproval) {
            // Notify employee
            create_browser_notification(
                $conDB,
                $resignation['emp_id'],
                'Resignation Approved',
                'Your resignation has been approved by all required approvers. HR will contact you regarding the exit process.',
                'all_resignations.php?inv=' . $requestInvNo
            );
            
            // Check employee's country - if not Saudi Arabia (191), notify GR officer for final exit process
            $countryCheckQuery = "SELECT e.country FROM employees e WHERE e.emp_id = '" . mysqli_real_escape_string($conDB, $resignation['emp_id']) . "' LIMIT 1";
            $countryResult = mysqli_query($conDB, $countryCheckQuery);
            $countryData = mysqli_fetch_assoc($countryResult);
            mysqli_free_result($countryResult);
            
            $employeeCountry = $countryData ? $countryData['country'] : null;
            
            // If employee's country is not 191 (Saudi Arabia), notify GR officers
            if ($employeeCountry && $employeeCountry != 191) {
                error_log("Country check: Employee country = $employeeCountry (not 191), notifying GR officers");
                
                // Get all active GR officers
                $grOfficersQuery = "SELECT al.emp_id, al.fullname, al.email, e.name 
                                   FROM admin_login al
                                   LEFT JOIN employees e ON al.emp_id = e.emp_id
                                   WHERE al.user_type = 'gr_officer' 
                                   AND al.status = 1 
                                   AND al.email IS NOT NULL
                                   AND al.email != ''";
                
                $grResult = mysqli_query($conDB, $grOfficersQuery);
                
                if ($grResult && mysqli_num_rows($grResult) > 0) {
                    while ($grOfficer = mysqli_fetch_assoc($grResult)) {
                        // Send email to GR officer
                        $grEmailData = [
                            'EMP_ID' => $resignation['emp_id'],
                            'EMP_NAME' => $resignation['emp_name'],
                            'DEPARTMENT' => $resignation['department'] ?? '',
                            'DESIGNATION' => $resignation['designation'] ?? '',
                            'RESIGNATION_ID' => $requestInvNo,
                            'LAST_WORKING_DAY' => isset($resignation['last_working_day']) ? date('d M Y', strtotime($resignation['last_working_day'])) : 'N/A',
                            'SUBMISSION_DATE' => isset($resignation['submission_date']) ? date('d M Y H:i', strtotime($resignation['submission_date'])) : 'N/A',
                            'APPROVER_NAME' => $grOfficer['fullname'] ?? $grOfficer['name'],
                            'REQUEST_URL' => 'https://hr.almutlaksystem.com/all_resignations.php?inv=' . $requestInvNo,
                            'COUNTRY_ID' => $employeeCountry
                        ];
                        
                        send_approval_email(
                            $conDB,
                            $grOfficer['email'],
                            $grOfficer['fullname'] ?? $grOfficer['name'],
                            'Employee Resignation - for Final Exit Process',
                            'resignation_request',
                            $grEmailData
                        );
                        
                        // Create browser notification for GR officer with approval required message
                        if ($grOfficer['emp_id']) {
                            create_browser_notification(
                                $conDB,
                                $grOfficer['emp_id'],
                                'Employee Resignation - for Final Exit Process',
                                'Employee ' . $resignation['emp_name'] . ' (ID: ' . $resignation['emp_id'] . ') resignation requires your approval for final exit process. Last working day: ' . (isset($resignation['last_working_day']) ? date('d M Y', strtotime($resignation['last_working_day'])) : 'N/A'),
                                'all_resignations.php?inv=' . $requestInvNo
                            );
                        }
                        
                        error_log("Notified GR officer: " . $grOfficer['emp_id'] . " for employee: " . $resignation['emp_id']);
                    }
                    mysqli_free_result($grResult);
                } else {
                    error_log("No GR officers found to notify");
                }
            }
        }
        
        // Success response
        $createEOS = isset($_POST['create_eos']) && $_POST['create_eos'] == '1';
        
        if ($isFinalApproval && $createEOS) {
            // Return resignation reason for EOS pre-fill
            $resignation_reason = $resignation['rejection_reason'] ?? '';
            echo json_encode([
                'type' => 'success',
                'title' => 'Approved',
                'message' => 'Resignation has been approved successfully. Redirecting to End of Service...',
                'redirect_to_eos' => true,
                'emp_id' => $resignation['emp_id'],
                'end_date' => $resignation['hr_last_working_day'] ?? $resignation['last_working_day'],
                'resignation_reason' => $resignation_reason,
                'request_inv_no' => $requestInvNo
            ]);
        } else {
            $message = $isFinalApproval 
                ? 'Resignation has been approved successfully by all required approvers.'
                : 'Resignation has been approved and forwarded to the next approver.';
            
            echo json_encode([
                'type' => 'success',
                'title' => 'Approved',
                'message' => $message
            ]);
        }
        
    } catch (Exception $e) {
        $errorDetails = 'Error: ' . $e->getMessage() . ' | File: ' . $e->getFile() . ' | Line: ' . $e->getLine();
        error_log("Resignation approval error: " . $errorDetails);
        error_log("Stack Trace: " . $e->getTraceAsString());
        
        echo json_encode([
            'type' => 'error',
            'title' => 'System Error',
            'message' => 'An unexpected error occurred. Please contact IT support.',
            'error_detail' => $e->getMessage(),
            'error_file' => $e->getFile(),
            'error_line' => $e->getLine()
        ]);
    }
    exit;
    
} elseif ($ajaxType == 'reject_resignation') {
    // ===== REJECT RESIGNATION =====
    try {
        $debugLogPath = __DIR__ . '/../../logs/resignation_reject.log';
        $logRejectDebug = function ($message) use ($debugLogPath) {
            $timestamp = date('Y-m-d H:i:s');
            @file_put_contents($debugLogPath, "[$timestamp] $message" . PHP_EOL, FILE_APPEND);
        };
        $logRejectDebug('START reject_resignation | POST=' . json_encode($_POST));

        $resignationId = isset($_POST['resignation_id']) ? (int)$_POST['resignation_id'] : 0;
        $invNo = isset($_POST['inv_no']) ? mysqli_real_escape_string($conDB, $_POST['inv_no']) : '';
        $rejectionReason = isset($_POST['rejection_reason']) ? mysqli_real_escape_string($conDB, $_POST['rejection_reason']) : '';
        
        // Validate inputs
        if (($resignationId <= 0 && empty($invNo)) || empty($rejectionReason)) {
            echo json_encode([
                'type' => 'error',
                'title' => 'Invalid Request',
                'message' => 'Invalid resignation ID or rejection reason.'
            ]);
            exit;
        }
        
        // Get resignation details
        $resignationQuery = "SELECT `r`.*, `e`.`emp_id`, `e`.`name` as `emp_name`, `r`.`request_inv_no`
                             FROM `emp_resignations` `r`
                             LEFT JOIN `employees` `e` ON `e`.`emp_id` = `r`.`emp_id`
                             WHERE " . ($resignationId > 0 ? "r.id = $resignationId" : "r.request_inv_no = '$invNo'") . " 
                             AND `r`.`status` = 'pending'";
        
        $resignationResult = mysqli_query($conDB, $resignationQuery);
        
        if (!$resignationResult || mysqli_num_rows($resignationResult) == 0) {
            $logRejectDebug('Resignation not found or already processed. Query=' . $resignationQuery . ' | Error=' . mysqli_error($conDB));
            echo json_encode([
                'type' => 'error',
                'title' => 'Not Found',
                'message' => 'Resignation not found or already processed.'
            ]);
            exit;
        }
        
        $resignation = mysqli_fetch_assoc($resignationResult);
        mysqli_free_result($resignationResult);
        $resignationId = $resignation['id'];
        $requestInvNo = $resignation['request_inv_no'];
        $logRejectDebug("Loaded resignation id=$resignationId | inv=$requestInvNo | emp_id=" . ($resignation['emp_id'] ?? ''));
        
        // Current user (rejector) from session_check.php globals
        $rejecterId = isset($empid) ? $empid : '';
        
        if (empty($rejecterId)) {
            echo json_encode([
                'type' => 'error',
                'title' => 'Unauthorized',
                'message' => 'You must be logged in to reject resignations.'
            ]);
            exit;
        }
        
        // Verify rejector is the current pending approver in approval chain
        require_once __DIR__ . '/../ApprovalChainManager.php';
        $chainManager = new ApprovalChainManager($conDB, $pdo, new ActivityLogger());
        $verifyRejectResult = $chainManager->verifyApprover($requestInvNo, $rejecterId);
        if (!is_array($verifyRejectResult) || empty($verifyRejectResult['authorized'])) {
            $logRejectDebug('Reject authorization failed: ' . json_encode($verifyRejectResult));
            echo json_encode([
                'type' => 'error',
                'title' => 'Unauthorized',
                'message' => $verifyRejectResult['message'] ?? 'You are not an authorized approver for this resignation.'
            ]);
            exit;
        }
        $approvalLevel = (int)($verifyRejectResult['level'] ?? 0);
        
        $logRejectDebug('Skipping scope restrictions; rejection authorization follows approval chain membership.');
        /*
        LEGACY (kept as comment per request): scope-based rejection restriction check.
        // ===== CHECK ACCESS CONTROL for rejector =====
        // Get rejector's allowed scope restrictions
        $rejecterScopeQuery = "SELECT allowed_companies, allowed_departments, allowed_employees FROM admin_login WHERE emp_id = '$rejecterId'";
        $rejecterScopeResult = mysqli_query($conDB, $rejecterScopeQuery);
        $rejecterScopeData = mysqli_fetch_assoc($rejecterScopeResult);
        mysqli_free_result($rejecterScopeResult);
        $logRejectDebug('Rejecter scope loaded for emp_id=' . $rejecterId);

        if ($rejecterScopeData) {
            // Check allowed companies
            $allowedCompanies = !empty($rejecterScopeData['allowed_companies']) ? json_decode($rejecterScopeData['allowed_companies'], true) : null;
            $allowedDepts = !empty($rejecterScopeData['allowed_departments']) ? json_decode($rejecterScopeData['allowed_departments'], true) : null;
            $allowedEmps = !empty($rejecterScopeData['allowed_employees']) ? json_decode($rejecterScopeData['allowed_employees'], true) : null;

            // Get employee's company and department
            $empScopeQuery = "SELECT comp_no, dept, emp_id FROM employees WHERE emp_id = '" . mysqli_real_escape_string($conDB, $resignation['emp_id']) . "'";
            $empScopeResult = mysqli_query($conDB, $empScopeQuery);
            $empScope = mysqli_fetch_assoc($empScopeResult);
            mysqli_free_result($empScopeResult);
            $logRejectDebug('Employee scope loaded for emp_id=' . ($resignation['emp_id'] ?? ''));

            $hasAccess = true;

            // If rejector has company restrictions, check if employee is in allowed companies
            if (is_array($allowedCompanies) && !empty($allowedCompanies) && is_array($empScope)) {
                if (!in_array($empScope['comp_no'], $allowedCompanies)) {
                    $hasAccess = false;
                }
            }

            // If rejector has department restrictions, check if employee is in allowed departments
            if ($hasAccess && is_array($allowedDepts) && !empty($allowedDepts) && is_array($empScope)) {
                if (!in_array($empScope['dept'], $allowedDepts)) {
                    $hasAccess = false;
                }
            }

            // If rejector has employee restrictions, check if employee is in allowed employees
            if ($hasAccess && is_array($allowedEmps) && !empty($allowedEmps)) {
                $empId = (int)$empScope['emp_id'];
                if (!in_array($empId, array_map('intval', $allowedEmps))) {
                    $hasAccess = false;
                }
            }

            if (!$hasAccess) {
                $logRejectDebug('Access denied by scope restrictions.');
                echo json_encode([
                    'type' => 'error',
                    'title' => 'Access Denied',
                    'message' => 'This employee is outside your rejection scope. You are not authorized to reject this resignation.'
                ]);
                exit;
            }
        }
        */
        
        // Get rejector details
        $rejecterQuery = "SELECT `fullname`, `email` FROM `admin_login` WHERE `emp_id` = '$rejecterId'";
        $rejecterResult = mysqli_query($conDB, $rejecterQuery);
        $rejecterData = mysqli_fetch_assoc($rejecterResult);
        $rejecterName = $rejecterData ? $rejecterData['fullname'] : 'Unknown';
        mysqli_free_result($rejecterResult);
        $logRejectDebug('Rejecter details loaded: ' . $rejecterName);
        
        // Update request_approvers record
        $updateApprovalQuery = "UPDATE `request_approvers` 
                               SET `status` = 'rejected', 
                                   `action_date` = NOW(),
                                   `note` = '" . mysqli_real_escape_string($conDB, "Rejected by $rejecterName: $rejectionReason") . "'
                               WHERE `request_inv_no` = '$requestInvNo' 
                               AND `approver_id` = '$rejecterId'
                               AND `status` = 'pending'";
        
        if (!mysqli_query($conDB, $updateApprovalQuery)) {
            $dbError = mysqli_error($conDB);
            error_log("[ajaxResignation] request_approvers update failed: $dbError | Query: $updateApprovalQuery");
            $logRejectDebug("request_approvers update failed: $dbError | Query=$updateApprovalQuery");
            echo json_encode([
                'type' => 'error',
                'title' => 'Database Error',
                'message' => 'Failed to record rejection.',
                'debug_info' => $dbError
            ]);
            exit;
        }
        $logRejectDebug('request_approvers updated for rejection.');
        
        // Update resignation status to rejected (aligned with current table schema)
        $updateQuery = "UPDATE `emp_resignations` 
                       SET `status` = 'rejected',
                           `rejection_reason` = '$rejectionReason',
                           `updated_at` = NOW()
                       WHERE `id` = $resignationId";

        
        if (!mysqli_query($conDB, $updateQuery)) {
            $dbError = mysqli_error($conDB);
            error_log("[ajaxResignation] emp_resignations update failed: $dbError | Query: $updateQuery");
            $logRejectDebug("emp_resignations update failed: $dbError | Query=$updateQuery");
            echo json_encode([
                'type' => 'error',
                'title' => 'Database Error',
                'message' => 'Failed to update resignation status.',
                'debug_info' => $dbError
            ]);
            exit;
        }
        $logRejectDebug('emp_resignations updated to rejected.');
        
        // Log the resignation rejection (wrapped in try-catch to prevent breaking flow)
        try {
            $old_resignation = mysqli_query($conDB, "SELECT * FROM emp_resignations WHERE id = $resignationId");
            $old_data = mysqli_fetch_assoc($old_resignation);
            if ($old_data && class_exists('ActivityLogger')) {
                ActivityLogger::logApproval('Resignation', 'ajaxResignation.php', $resignationId, 'rejected', "Rejected resignation request: {$requestInvNo}, Level: {$approvalLevel}, Reason: {$rejectionReason}", 'emp_resignations');
            }
            if ($old_resignation) mysqli_free_result($old_resignation);
        } catch (Exception $logException) {
            error_log("WARNING: Failed to log resignation rejection: " . $logException->getMessage());
            // Continue - logging failure should not block rejection
        }
        
        // Save rejection comment (wrapped in try-catch)
        try {
            if (!empty($rejectionReason) && function_exists('save_approval_comment_db')) {
                save_approval_comment_db(
                    $conDB,
                    $requestInvNo,
                    'resignation',
                    'rejected',
                    $rejecterId,
                    $rejecterName,
                    $rejectionReason,
                    $approvalLevel,
                    $rejecterId
                );
            }
        } catch (Exception $commentException) {
            error_log("WARNING: Failed to save rejection comment: " . $commentException->getMessage());
            // Continue - comment save failure should not block rejection
        }
        
        // History is tracked via request_approvers table with status='rejected' and action_date=NOW()
        // No additional history table needed
        
        // Mark all pending approvals as cancelled
        $cancelApprovalQuery = "UPDATE `request_approvers` 
                               SET `status` = 'rejected',
                                   `action_date` = NOW(),
                                   `note` = 'Cancelled - Resignation rejected at Level $approvalLevel'
                               WHERE `request_inv_no` = '$requestInvNo' 
                               AND `status` = 'awaiting'";
        mysqli_query($conDB, $cancelApprovalQuery);
        
        // Notify the employee (wrapped in try-catch to prevent blocking rejection)
        try {
            if (function_exists('create_browser_notification')) {
                create_browser_notification(
                    $conDB,
                    $resignation['emp_id'],
                    'Resignation Rejected',
                    'Your resignation has been rejected at Level ' . $approvalLevel . '. Reason: ' . $rejectionReason,
                    'all_resignations.php?inv=' . $requestInvNo
                );
            }
        } catch (Exception $notifyException) {
            error_log("WARNING: Failed to create browser notification for rejection: " . $notifyException->getMessage());
            // Continue - notification failure should not block rejection
        }
        
        // Send email to employee with rejection details (wrapped in try-catch)
        try {
            $employeeEmailQuery = "SELECT `al`.`email` FROM `admin_login` `al` 
                                 WHERE `al`.`emp_id` = '" . $resignation['emp_id'] . "'";
            $employeeEmailResult = mysqli_query($conDB, $employeeEmailQuery);
            if ($employeeEmailResult && mysqli_num_rows($employeeEmailResult) > 0) {
                $empEmailData = mysqli_fetch_assoc($employeeEmailResult);
                mysqli_free_result($employeeEmailResult);
                
                if (!empty($empEmailData['email']) && function_exists('send_approval_email')) {
                    $emailData = [
                        'emp_name' => $resignation['emp_name'],
                        'rejection_reason' => $rejectionReason,
                        'rejected_by' => $rejecterName,
                        'approval_level' => $approvalLevel,
                        'approver_name' => $resignation['emp_name']
                    ];
                    
                    send_approval_email(
                        $conDB,
                        $empEmailData['email'],
                        $resignation['emp_name'],
                        'Your Resignation Request - Rejected',
                        'resignation_request',
                        $emailData
                    );
                }
            }
        } catch (Exception $emailException) {
            error_log("WARNING: Failed to send rejection email: " . $emailException->getMessage());
            // Continue - email failure should not block rejection
        }
        
        // Success response
        echo json_encode([
            'type' => 'success',
            'title' => 'Rejected',
            'message' => 'Resignation has been rejected successfully. All pending approvals have been cancelled.'
        ]);
        
    } catch (Throwable $e) {
        $errorDetails = 'Error: ' . $e->getMessage() . ' | File: ' . $e->getFile() . ' | Line: ' . $e->getLine();
        error_log("Resignation rejection error: " . $errorDetails);
        if (isset($logRejectDebug) && is_callable($logRejectDebug)) {
            $logRejectDebug('EXCEPTION: ' . $errorDetails);
        }
        
        echo json_encode([
            'type' => 'error',
            'title' => 'System Error',
            'message' => 'An unexpected error occurred. Please contact IT support.',
            'error_detail' => $e->getMessage(),
            'error_file' => $e->getFile(),
            'error_line' => $e->getLine()
        ]);
    }
    exit;
    
} elseif ($ajaxType == 'get_resignation_details') {
    // ===== GET FULL RESIGNATION DETAILS WITH EXIT INTERVIEW =====
    try {
        $resignationId = isset($_POST['resignation_id']) ? (int)$_POST['resignation_id'] : 0;
        $invNo = isset($_POST['inv_no']) ? mysqli_real_escape_string($conDB, $_POST['inv_no']) : '';
        
        if ($resignationId <= 0 && empty($invNo)) {
            echo json_encode([
                'type' => 'error',
                'message' => 'Invalid resignation ID or request number.'
            ]);
            exit;
        }
        
        // Get resignation details with employee info - includes hr_last_working_day via r.*
        $query = "SELECT 
                    r.id,
                    r.emp_id,
                    r.request_inv_no,
                    r.last_working_day,
                    r.hr_last_working_day,
                    r.submission_date,
                    r.status,
                    r.needs_replacement,
                    r.replacement_data,
                    r.rejection_reason,
                    r.created_at,
                    r.updated_at,
                    e.emp_id, 
                    e.name as emp_name, 
                    e.iqama,
                    COALESCE(d.dep_nme, 'N/A') as department,
                    COALESCE(j.job, 'N/A') as designation
                  FROM emp_resignations r
                  LEFT JOIN employees e ON e.emp_id = r.emp_id
                  LEFT JOIN department d ON d.id = e.dept
                  LEFT JOIN ac_jobs j ON j.id = e.actual_job
                  WHERE " . ($resignationId > 0 ? "r.id = $resignationId" : "r.request_inv_no = '$invNo'");
        
        $result = mysqli_query($conDB, $query);
        
        if (!$result || mysqli_num_rows($result) == 0) {
            echo json_encode([
                'type' => 'error',
                'message' => 'Resignation not found.'
            ]);
            exit;
        }
        
        $resignation = mysqli_fetch_assoc($result);
        mysqli_free_result($result);
        
        // Get exit interview answers
        $exitQuery = "SELECT * FROM emp_exit_interviews WHERE resignation_id = " . $resignation['id'];
        $exitResult = mysqli_query($conDB, $exitQuery);
        $exitInterview = ($exitResult && mysqli_num_rows($exitResult) > 0) ? mysqli_fetch_assoc($exitResult) : [];
        if ($exitResult) mysqli_free_result($exitResult);
        
        // Success response with hr_last_working_day explicitly included
        echo json_encode([
            'type' => 'success',
            'success' => true,
            'resignation' => $resignation,
            'exit_interview' => $exitInterview
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'type' => 'error',
            'message' => 'Failed to fetch resignation details: ' . $e->getMessage()
        ]);
    }
    exit;

} else {
    echo json_encode([
        'type' => 'error',
        'title' => 'Invalid Request',
        'message' => 'Invalid AJAX type specified.'
    ]);
    exit;
}
?>