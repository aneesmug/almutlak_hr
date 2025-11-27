<?php
/**
 * Employee Resignation AJAX Handler
 * Handles all resignation-related AJAX requests
 * Created: 2025-11-25
 */
header('Content-Type: application/json');

try {
    require_once __DIR__ . '/../../includes/db.php';
    // Load session context (defines $empid, $user_type, permissions)
    require_once __DIR__ . '/../../includes/session_check.php';
    // Helper functions
    $helperFile = __DIR__ . '/../../includes/helper_functions.php';
    if (file_exists($helperFile)) {
        include($helperFile);
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
              AND `status` = 'awaiting'
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
        
        // If replacement data exists, parse JSON
        if ($row['replacement_data']) {
            $parsed = json_decode($row['replacement_data'], true);
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
        // Get POST data
        $empId = isset($_POST['emp_id']) ? mysqli_real_escape_string($conDB, $_POST['emp_id']) : '';
        $lastWorkingDay = isset($_POST['last_working_day']) ? mysqli_real_escape_string($conDB, $_POST['last_working_day']) : '';
        $exitInterviewJson = isset($_POST['exit_interview']) ? $_POST['exit_interview'] : '';
        
        // Validate required fields
        if (empty($empId) || empty($lastWorkingDay) || empty($exitInterviewJson)) {
            echo json_encode([
                'type' => 'error',
                'title' => 'Validation Error',
                'message' => 'Please fill in all required fields.'
            ]);
            exit;
        }
        
        // Validate employee exists and is active
        $empCheck = mysqli_query($conDB, "SELECT e.`emp_id`, e.`name`, al.`email` FROM `employees` e 
                                         LEFT JOIN `admin_login` al ON al.`emp_id` = e.`emp_id`
                                         WHERE e.`emp_id` = '$empId' AND e.`status` = 1");
        if (!$empCheck || mysqli_num_rows($empCheck) == 0) {
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
        
        // Insert resignation record
        $insertResignation = "INSERT INTO `emp_resignations` 
            (`emp_id`, `request_inv_no`, `last_working_day`, `submission_date`, `status`, `created_at`, `updated_at`) 
            VALUES 
            ('$empId', '$requestInvNo', '$lastWorkingDay', '$submissionDate', 'pending', NOW(), NOW())";
        
        $resignationResult = mysqli_query($conDB, $insertResignation);
        
        if (!$resignationResult) {
            echo json_encode([
                'type' => 'error',
                'title' => 'Database Error',
                'message' => 'Failed to save resignation. Please try again.'
            ]);
            error_log("Resignation insert error: " . mysqli_error($conDB));
            exit;
        }
        
        // Get the inserted resignation ID
        $resignationId = mysqli_insert_id($conDB);
        
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
        
        // Insert exit interview
        $insertExitInterview = "INSERT INTO `emp_exit_interviews` 
            (`resignation_id`, `emp_id`, `q1_reasons`, `q2_support`, `q3_resources`, 
             `q4_manager`, `q5_growth`, `q6_compensation`, `q7_different`, 
             `q8_recommend`, `q9_additional`, `submitted_at`, `created_at`) 
            VALUES 
            ('$resignationId', '$empId', '$q1', '$q2', '$q3', '$q4', '$q5', 
             '$q6', '$q7', '$q8', '$q9', NOW(), NOW())";
        
        $exitInterviewResult = mysqli_query($conDB, $insertExitInterview);
        
        if (!$exitInterviewResult) {
            // Rollback: Delete the resignation record if exit interview fails
            mysqli_query($conDB, "DELETE FROM `emp_resignations` WHERE `id` = '$resignationId'");
            echo json_encode([
                'type' => 'error',
                'title' => 'Database Error',
                'message' => 'Failed to save exit interview. Please try again.'
            ]);
            error_log("Exit interview insert error: " . mysqli_error($conDB));
            exit;
        }
        
        // History is tracked via request_approvers table - no additional history table needed
        
        // Log activity
        $activityLog = "INSERT INTO `activity_log` (`user_editor`, `page`, `pg_id`, `reg_date`) 
            VALUES ('$empId', 'resignation_submit', '$resignationId', '" . date('c') . "')";
        mysqli_query($conDB, $activityLog);
        
        // ===== CREATE APPROVAL CHAIN =====
        // Get resignation request type ID
        $typeQuery = mysqli_query($conDB, "SELECT `id` FROM `approval_request_types` WHERE `type_name` = 'resignation_request' LIMIT 1");
        $typeRow = mysqli_fetch_assoc($typeQuery);
        $requestTypeId = $typeRow['id'];
        mysqli_free_result($typeQuery);
        
        // Step 1: Get Direct Supervisor (Manager)
        $supervisorQuery = "SELECT `supervisor_id` FROM `employees` WHERE `emp_id` = '$empId' AND `status` = 1";
        $supervisorResult = mysqli_query($conDB, $supervisorQuery);
        $supervisorData = mysqli_fetch_assoc($supervisorResult);
        mysqli_free_result($supervisorResult);
        
        $supervisorId = $supervisorData['supervisor_id'] ?? null;
        
        if ($supervisorId) {
            // Add supervisor as Level 1 approver (Manager)
            $chainInsert = "INSERT INTO `request_approvers` 
                (`request_inv_no`, `request_type_id`, `approver_id`, `approval_level`, `status`) 
                VALUES 
                ('$requestInvNo', $requestTypeId, $supervisorId, 1, 'awaiting')";
            mysqli_query($conDB, $chainInsert);
        }
        
        // Step 2: Get HR Operations approver (Level 2)
        $hrOpsQuery = "SELECT `emp_id` FROM `admin_login` 
                      WHERE `user_type` = 'hr_operations' AND `status` = 1 LIMIT 1";
        $hrOpsResult = mysqli_query($conDB, $hrOpsQuery);
        
        if ($hrOpsResult && mysqli_num_rows($hrOpsResult) > 0) {
            $hrOpsData = mysqli_fetch_assoc($hrOpsResult);
            $chainInsert = "INSERT INTO `request_approvers` 
                (`request_inv_no`, `request_type_id`, `approver_id`, `approval_level`, `status`) 
                VALUES 
                ('$requestInvNo', $requestTypeId, " . $hrOpsData['emp_id'] . ", 2, 'awaiting')";
            mysqli_query($conDB, $chainInsert);
        }
        mysqli_free_result($hrOpsResult);
        
        // Step 3: Get HR Payroll approver (Level 3 - Final)
        $hrPayrollQuery = "SELECT `emp_id` FROM `admin_login` 
                          WHERE `user_type` = 'hr_payroll' AND `status` = 1 LIMIT 1";
        $hrPayrollResult = mysqli_query($conDB, $hrPayrollQuery);
        
        if ($hrPayrollResult && mysqli_num_rows($hrPayrollResult) > 0) {
            $hrPayrollData = mysqli_fetch_assoc($hrPayrollResult);
            $chainInsert = "INSERT INTO `request_approvers` 
                (`request_inv_no`, `request_type_id`, `approver_id`, `approval_level`, `status`) 
                VALUES 
                ('$requestInvNo', $requestTypeId, " . $hrPayrollData['emp_id'] . ", 3, 'awaiting')";
            mysqli_query($conDB, $chainInsert);
        }
        mysqli_free_result($hrPayrollResult);
        
        // ===== SEND NOTIFICATIONS TO FIRST APPROVER (Manager) =====
        if ($supervisorId) {
            // Get supervisor details
            $supervisorDetailsQuery = "SELECT `e`.`name`, `al`.`email` 
                                      FROM `employees` `e` 
                                      LEFT JOIN `admin_login` `al` ON `al`.`emp_id` = `e`.`emp_id`
                                      WHERE `e`.`emp_id` = '$supervisorId' AND `e`.`status` = 1";
            $supervisorDetailsResult = mysqli_query($conDB, $supervisorDetailsQuery);
            
            if ($supervisorDetailsResult && mysqli_num_rows($supervisorDetailsResult) > 0) {
                $supervisor = mysqli_fetch_assoc($supervisorDetailsResult);
                mysqli_free_result($supervisorDetailsResult);
                
                // Get employee dept and job for email
                $empDetailsQuery = "SELECT `d`.`dep_nme`, `j`.`job` 
                                   FROM `employees` `e`
                                   LEFT JOIN `department` `d` ON `d`.`id` = `e`.`dept`
                                   LEFT JOIN `ac_jobs` `j` ON `j`.`id` = `e`.`actual_job`
                                   WHERE `e`.`emp_id` = '$empId'";
                $empDetailsResult = mysqli_query($conDB, $empDetailsQuery);
                $empDetails = mysqli_fetch_assoc($empDetailsResult);
                mysqli_free_result($empDetailsResult);
                
                if (!empty($supervisor['email'])) {
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
                        'APPROVER_NAME' => $supervisor['name'],
                        'approval_level' => 1,
                        'approval_level_name' => 'Manager (Direct Supervisor)'
                    ];
                    
                    send_approval_email(
                        $conDB,
                        $supervisor['email'],
                        $supervisor['name'],
                        'Employee Resignation Request - Action Required (Level 1: Manager)',
                        'resignation_request',
                        $emailData
                    );
                    
                    // Create browser notification
                    create_browser_notification(
                        $conDB,
                        $supervisorId,
                        'Resignation Request Requires Your Approval',
                        $empData['name'] . ' has submitted a resignation request. Please review and approve/reject.',
                        'all_resignations.php?inv=' . $requestInvNo
                    );
                }
            }
        }
        
        // Success response
        echo json_encode([
            'type' => 'success',
            'title' => 'Resignation Submitted',
            'message' => 'Your resignation has been submitted successfully. HR will review your request and contact you soon.'
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'type' => 'error',
            'title' => 'System Error',
            'message' => 'An unexpected error occurred. Please contact IT support.'
        ]);
        error_log("Resignation submission error: " . $e->getMessage());
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
    
} elseif ($ajaxType == 'approve_resignation') {
    // ===== APPROVE RESIGNATION =====
    try {
        error_log("Approval process started");
        
        $resignationId = isset($_POST['resignation_id']) ? (int)$_POST['resignation_id'] : 0;
        $invNo = isset($_POST['inv_no']) ? mysqli_real_escape_string($conDB, $_POST['inv_no']) : '';
        $needsReplacement = isset($_POST['needs_replacement']) ? (int)$_POST['needs_replacement'] : 0;
        $replacementData = isset($_POST['replacement_data']) ? $_POST['replacement_data'] : null;
        
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
        
        // Get approval record from request_approvers
        $approvalQuery = "SELECT `id`, `approval_level` FROM `request_approvers` 
                         WHERE `request_inv_no` = '$requestInvNo' 
                         AND `approver_id` = '$approverId'
                         AND `status` = 'awaiting'";
        $approvalResult = mysqli_query($conDB, $approvalQuery);
        
        if (!$approvalResult || mysqli_num_rows($approvalResult) == 0) {
            echo json_encode([
                'type' => 'error',
                'title' => 'Unauthorized',
                'message' => 'You are not an authorized approver for this resignation.'
            ]);
            exit;
        }
        
        $approvalRecord = mysqli_fetch_assoc($approvalResult);
        mysqli_free_result($approvalResult);
        $approvalLevel = $approvalRecord['approval_level'];
        
        // Get approver details
        $approverQuery = "SELECT `fullname`, `email` FROM `admin_login` WHERE `emp_id` = '$approverId'";
        $approverResult = mysqli_query($conDB, $approverQuery);
        $approverData = mysqli_fetch_assoc($approverResult);
        $approverName = $approverData ? $approverData['fullname'] : 'Unknown';
        mysqli_free_result($approverResult);
        
        // Get next approver in chain (if exists)
        $nextApproverQuery = "SELECT `ra`.`id`, `ra`.`approver_id`, `al`.`fullname`, `al`.`email`, `ra`.`approval_level`
                             FROM `request_approvers` `ra`
                             LEFT JOIN `admin_login` `al` ON `al`.`emp_id` = `ra`.`approver_id`
                             WHERE `ra`.`request_inv_no` = '$requestInvNo' 
                             AND `ra`.`approval_level` > $approvalLevel
                             AND `ra`.`status` = 'awaiting'
                             ORDER BY `ra`.`approval_level` ASC
                             LIMIT 1";
        $nextApproverResult = mysqli_query($conDB, $nextApproverQuery);
        $nextApprover = (mysqli_num_rows($nextApproverResult) > 0) ? mysqli_fetch_assoc($nextApproverResult) : null;
        mysqli_free_result($nextApproverResult);
        
        // Prepare replacement data if provided
        $replacementJson = null;
        if ($needsReplacement && $replacementData) {
            $replacementJson = $replacementData;
        }
        
        // Check if this is the final approval level
        $isFinalApproval = ($nextApprover === null);
        
        // Update request_approvers record
        $updateApprovalQuery = "UPDATE `request_approvers` 
                               SET `status` = 'approved', 
                                   `action_date` = NOW(),
                                   `note` = '" . mysqli_real_escape_string($conDB, "Approved by $approverName") . "'
                               WHERE `request_inv_no` = '$requestInvNo' 
                               AND `approver_id` = '$approverId'";
        
        if (!mysqli_query($conDB, $updateApprovalQuery)) {
            $dbErr = mysqli_error($conDB);
            error_log("Approval update failed: $dbErr");
            echo json_encode([
                'type' => 'error',
                'title' => 'Database Error',
                'message' => 'Failed to record approval.'
            ]);
            exit;
        }
        
        // Build UPDATE query for resignation - only update replacement fields if new data provided
        // Otherwise preserve existing replacement data from Direct Supervisor
        $updateQueryParts = [];
        
        // Always set status if final approval
        if ($isFinalApproval) {
            $updateQueryParts[] = "`status` = 'approved'";
        }
        
        // Only update replacement fields if current approval level is Level 1 (Direct Supervisor)
        // Level 2 (HR Operations) should NOT overwrite these fields
        if ($approvalLevel == 1) {
            $updateQueryParts[] = "`needs_replacement` = '$needsReplacement'";
            if ($replacementJson) {
                $updateQueryParts[] = "`replacement_data` = '" . mysqli_real_escape_string($conDB, $replacementJson) . "'";
            }
        }
        
        // Update HR last working day if provided (only for Level 2 - HR Operations)
        if ($approvalLevel == 2) {
            $hrLastWorkingDay = isset($_POST['hr_last_working_day']) ? mysqli_real_escape_string($conDB, $_POST['hr_last_working_day']) : '';
            if ($hrLastWorkingDay) {
                // Store HR's selected last working day in a separate field
                $updateQueryParts[] = "`hr_last_working_day` = '$hrLastWorkingDay'";
                error_log("HR last working day received: $hrLastWorkingDay");
            } else {
                error_log("Warning: HR last working day not received for Level 2 approval");
            }
        }
        
        // Always update timestamp
        $updateQueryParts[] = "`updated_at` = NOW()";
        
        // Build final query if there are updates
        if (!empty($updateQueryParts)) {
            $updateQuery = "UPDATE `emp_resignations` 
                           SET " . implode(', ', $updateQueryParts) . "
                           WHERE `id` = $resignationId";
        } else {
            // No updates needed
            $updateQuery = "UPDATE `emp_resignations` 
                           SET `updated_at` = NOW()
                           WHERE `id` = $resignationId";
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
        
        // Log the approval action
        error_log("Resignation approval: ID=$resignationId, Level=$approvalLevel, IsFinal=$isFinalApproval, Status=" . ($isFinalApproval ? 'approved' : 'pending'));
        
        // History is tracked via request_approvers table with status='approved' and action_date=NOW()
        // No additional history table needed
        
        // Send notification to next approver if exists
        if ($nextApprover && !empty($nextApprover['email'])) {
            $emailData = [
                'EMP_ID' => $resignation['emp_id'],
                'EMP_NAME' => $resignation['emp_name'],
                'DEPARTMENT' => $resignation['department'] ?? '',
                'DESIGNATION' => $resignation['designation'] ?? '',
                'RESIGNATION_ID' => $requestInvNo,
                'LAST_WORKING_DAY' => isset($resignation['last_working_day']) ? date('d M Y', strtotime($resignation['last_working_day'])) : 'N/A',
                'SUBMISSION_DATE' => isset($resignation['submission_date']) ? date('d M Y H:i', strtotime($resignation['submission_date'])) : 'N/A',
                'APPROVER_NAME' => $nextApprover['fullname'],
                'REQUEST_URL' => 'https://hr.almutlaksystem.com/all_resignations.php'
            ];
            
            send_approval_email(
                $conDB,
                $nextApprover['email'],
                $nextApprover['fullname'],
                'Employee Resignation Request - Action Required (Level ' . $nextApprover['approval_level'] . ')',
                'resignation_request',
                $emailData
            );
            
            // Create browser notification
            create_browser_notification(
                $conDB,
                $nextApprover['approver_id'],
                'Resignation Requires Your Approval',
                $resignation['emp_name'] . "'s resignation has been forwarded to you for approval (Level " . $nextApprover['approval_level'] . ").",
                'all_resignations.php?inv=' . $requestInvNo
            );
        }
        
        // If HR Operations (Level 2) approves, notify all HR users
        if ($approvalLevel == 2) {
            $hrUsersQuery = "SELECT DISTINCT `emp_id`, `fullname`, `email` FROM `admin_login` 
                            WHERE `user_type` IN ('hr_operations', 'hr_payroll', 'hr_senior_bp') 
                            AND `emp_id` != '$approverId'
                            AND `email` IS NOT NULL AND `email` != ''";
            $hrUsersResult = mysqli_query($conDB, $hrUsersQuery);
            
            if ($hrUsersResult) {
                while ($hrUser = mysqli_fetch_assoc($hrUsersResult)) {
                    create_browser_notification(
                        $conDB,
                        $hrUser['emp_id'],
                        'Resignation Approved by HR Operations',
                        $resignation['emp_name'] . "'s resignation has been approved by HR Operations and is now proceeding to HR Payroll.",
                        'all_resignations.php?inv=' . $requestInvNo
                    );
                }
                mysqli_free_result($hrUsersResult);
            }
        }
        
        // If final approval (HR Payroll - Level 3), notify all HR users with full information
        if ($isFinalApproval) {
            create_browser_notification(
                $conDB,
                $resignation['emp_id'],
                'Resignation Approved',
                'Your resignation has been approved by all required approvers. HR will contact you regarding the exit process.',
                'all_resignations.php?inv=' . $requestInvNo
            );
            
            // Notify all HR users about final approval with complete information
            $hrUsersQuery = "SELECT DISTINCT `emp_id`, `fullname`, `email` FROM `admin_login` 
                            WHERE `user_type` IN ('hr_operations', 'hr_payroll', 'hr_senior_bp') 
                            AND `emp_id` != '$approverId'
                            AND `email` IS NOT NULL AND `email` != ''";
            $hrUsersResult = mysqli_query($conDB, $hrUsersQuery);
            
            if ($hrUsersResult) {
                while ($hrUser = mysqli_fetch_assoc($hrUsersResult)) {
                    // Send browser notification
                    create_browser_notification(
                        $conDB,
                        $hrUser['emp_id'],
                        'Resignation Fully Approved',
                        $resignation['emp_name'] . "'s resignation has been fully approved by all levels. Employee ID: " . $resignation['emp_id'],
                        'all_resignations.php?inv=' . $requestInvNo
                    );
                    
                    // Send detailed email with all information
                    $emailData = [
                        'EMP_ID' => $resignation['emp_id'],
                        'EMP_NAME' => $resignation['emp_name'],
                        'DEPARTMENT' => $resignation['department'] ?? '',
                        'DESIGNATION' => $resignation['designation'] ?? '',
                        'RESIGNATION_ID' => $requestInvNo,
                        'LAST_WORKING_DAY' => isset($resignation['last_working_day']) ? date('d M Y', strtotime($resignation['last_working_day'])) : 'N/A',
                        'SUBMISSION_DATE' => isset($resignation['submission_date']) ? date('d M Y H:i', strtotime($resignation['submission_date'])) : 'N/A',
                        'APPROVER_NAME' => $hrUser['fullname'],
                        'REQUEST_URL' => 'https://hr.almutlaksystem.com/all_resignations.php',
                        'EMAIL_MESSAGE' => 'A resignation request has been fully approved by all required levels.'
                    ];
                    
                    send_approval_email(
                        $conDB,
                        $hrUser['email'],
                        $hrUser['fullname'],
                        'Resignation Fully Approved - ' . $resignation['emp_name'] . ' (ID: ' . $resignation['emp_id'] . ')',
                        'resignation_request',
                        $emailData
                    );
                }
                mysqli_free_result($hrUsersResult);
            }
        }
        
        // If final approval and create_eos flag is set, create End of Service record
        $createEOS = isset($_POST['create_eos']) && $_POST['create_eos'] == '1';
        $eosCreatedMessage = '';
        
        // For final approval, just skip EOS creation and redirect to EOS page
        // Don't insert anything, user will create EOS manually from the EOS page
        if ($isFinalApproval && $createEOS) {
            // Don't create EOS here, just prepare redirect info
            error_log("Resignation final approval: ID=$resignationId, Skipping EOS creation, will redirect to EOS page");
        }
        
        // Success response
        $message = $isFinalApproval 
            ? 'Resignation has been approved successfully by all required approvers.' . $eosCreatedMessage
            : 'Resignation has been approved and forwarded to the next approver (Level ' . ($nextApprover['approval_level'] ?? 'Final') . ').';
        
        // Check if this is final approval (HR Payroll) with EOS creation flag
        if ($isFinalApproval && $createEOS) {
            // Return success message for HR Payroll final approval (will redirect in JS)
            echo json_encode([
                'type' => 'success',
                'title' => 'Approved',
                'message' => 'Resignation has been approved successfully. Redirecting to End of Service...'
            ]);
        } else {
            // Return success message for other approvals
            echo json_encode([
                'type' => 'success',
                'title' => 'Approved',
                'message' => $message
            ]);
        }
        
    } catch (Exception $e) {
        $errorDetails = 'Error: ' . $e->getMessage() . ' | File: ' . $e->getFile() . ' | Line: ' . $e->getLine();
        error_log("Resignation approval error: " . $errorDetails);
        
        echo json_encode([
            'type' => 'error',
            'title' => 'System Error',
            'message' => 'An unexpected error occurred. Please contact IT support.'
        ]);
    }
    exit;
    
} elseif ($ajaxType == 'reject_resignation') {
    // ===== REJECT RESIGNATION =====
    try {
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
        
        // Verify approver has permission from request_approvers
        $approvalCheckQuery = "SELECT `id`, `approval_level` FROM `request_approvers` 
                             WHERE `request_inv_no` = '$requestInvNo' 
                             AND `approver_id` = '$rejecterId'
                             AND `status` = 'awaiting'";
        $approvalCheckResult = mysqli_query($conDB, $approvalCheckQuery);
        
        if (!$approvalCheckResult || mysqli_num_rows($approvalCheckResult) == 0) {
            echo json_encode([
                'type' => 'error',
                'title' => 'Unauthorized',
                'message' => 'You are not an authorized approver for this resignation.'
            ]);
            exit;
        }
        $approvalCheckData = mysqli_fetch_assoc($approvalCheckResult);
        mysqli_free_result($approvalCheckResult);
        $approvalLevel = $approvalCheckData['approval_level'];
        
        // Get rejector details
        $rejecterQuery = "SELECT `fullname`, `email` FROM `admin_login` WHERE `emp_id` = '$rejecterId'";
        $rejecterResult = mysqli_query($conDB, $rejecterQuery);
        $rejecterData = mysqli_fetch_assoc($rejecterResult);
        $rejecterName = $rejecterData ? $rejecterData['fullname'] : 'Unknown';
        mysqli_free_result($rejecterResult);
        
        // Update request_approvers record
        $updateApprovalQuery = "UPDATE `request_approvers` 
                               SET `status` = 'rejected', 
                                   `action_date` = NOW(),
                                   `note` = '" . mysqli_real_escape_string($conDB, "Rejected by $rejecterName: $rejectionReason") . "'
                               WHERE `request_inv_no` = '$requestInvNo' 
                               AND `approver_id` = '$rejecterId'";
        
        if (!mysqli_query($conDB, $updateApprovalQuery)) {
            echo json_encode([
                'type' => 'error',
                'title' => 'Database Error',
                'message' => 'Failed to record rejection.'
            ]);
            exit;
        }
        
        // Update resignation status to rejected
        $updateQuery = "UPDATE `emp_resignations` 
                       SET `status` = 'rejected', 
                           `rejected_by` = '$rejecterId', 
                           `approval_date` = NOW(),
                           `rejection_reason` = '$rejectionReason',
                           `updated_at` = NOW()
                       WHERE `id` = $resignationId";

        
        if (!mysqli_query($conDB, $updateQuery)) {
            echo json_encode([
                'type' => 'error',
                'title' => 'Database Error',
                'message' => 'Failed to update resignation status.'
            ]);
            exit;
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
        
        // Notify the employee
        create_browser_notification(
            $conDB,
            $resignation['emp_id'],
            'Resignation Rejected',
            'Your resignation has been rejected at Level ' . $approvalLevel . '. Reason: ' . $rejectionReason,
            'all_resignations.php?inv=' . $requestInvNo
        );
        
        // Send email to employee with rejection details
        $employeeEmailQuery = "SELECT `al`.`email` FROM `admin_login` `al` 
                             WHERE `al`.`emp_id` = '" . $resignation['emp_id'] . "'";
        $employeeEmailResult = mysqli_query($conDB, $employeeEmailQuery);
        if ($employeeEmailResult && mysqli_num_rows($employeeEmailResult) > 0) {
            $empEmailData = mysqli_fetch_assoc($employeeEmailResult);
            mysqli_free_result($employeeEmailResult);
            
            if (!empty($empEmailData['email'])) {
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
        
        // Success response
        echo json_encode([
            'type' => 'success',
            'title' => 'Rejected',
            'message' => 'Resignation has been rejected successfully. All pending approvals have been cancelled.'
        ]);
        
    } catch (Exception $e) {
        error_log("Resignation rejection error: " . $e->getMessage());
        echo json_encode([
            'type' => 'error',
            'title' => 'System Error',
            'message' => 'An unexpected error occurred. Please contact IT support.'
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