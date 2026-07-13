<?php
/**
 * Settlement API Endpoint
 * Handles all settlement operations using ApprovalChainManager
 * Ensures settlements follow app_settings approval chain configuration
 * 
 * Tables used:
 * - request_approvers: Approval tracking (created by ApprovalChainManager)
 * - smt_request_status: Complete approval history
 * - settlement_records: Settlement record linking
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/session_check.php';
require_once __DIR__ . '/../../includes/helper_functions.php';
require_once __DIR__ . '/../../includes/SettlementManager_Corrected.php';
require_once __DIR__ . '/../../includes/ApprovalChainManager.php';
require_once __DIR__ . '/../../includes/settlement_attachments_helper.php';

// Initialize managers with both MySQLi and PDO connections
$settlementManager = new SettlementManager($conDB, $pdo);

// Verify user is logged in
if (empty($_SESSION['empid'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$action = $_POST['action'] ?? '';
$currentUserId = $_SESSION['empid'];

switch ($action) {
    case 'create_settlement':
        createSettlement($settlementManager, $currentUserId);
        break;
    
    case 'get_settlement_chain':
        getSettlementChain($settlementManager);
        break;
    
    case 'check_final_approval':
        checkFinalApproval($currentUserId);
        break;
    
    case 'get_finance_employees':
        getFinanceEmployees();
        break;
    
    case 'approve_settlement':
        approveSettlement($settlementManager, $currentUserId);
        break;
    
    case 'upload_settlement_attachment':
        uploadSettlementAttachment($currentUserId);
        break;
    
    case 'approve_settlement_with_attachments':
        approveSettlementWithAttachments($settlementManager, $currentUserId);
        break;
    
    case 'reject_settlement':
        rejectSettlement($settlementManager, $currentUserId);
        break;
    
    case 'process_payment':
        processPayment($settlementManager, $currentUserId);
        break;
    
    case 'get_settlement_details':
        getSettlementDetails($settlementManager);
        break;
    
    case 'get_employee_settlements':
        getEmployeeSettlements($settlementManager, $currentUserId);
        break;

    default:
        echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
}

function createSettlement($settlementManager, $currentUserId) {
    global $conDB, $pdo;
    
    try {
        // Validate required inputs with strict checking
        $requestInvNo = trim($_POST['request_inv_no'] ?? '');
        $requestType = trim($_POST['request_type'] ?? '');
        $empId = intval($_POST['emp_id'] ?? 0);
        $settlementAmount = floatval($_POST['settlement_amount'] ?? 0);
        
        if (empty($requestInvNo) || empty($requestType) || $empId <= 0 || $settlementAmount <= 0) {
            error_log("Settlement Handler: Invalid parameters - request_inv_no: '$requestInvNo', request_type: '$requestType', emp_id: $empId, amount: $settlementAmount");
            echo json_encode([
                'status' => 'error', 
                'message' => 'Missing or invalid required parameters'
            ]);
            return;
        }
        
        error_log("Settlement Handler: Creating settlement with ApprovalChainManager - request_inv_no=$requestInvNo, emp_id=$empId, amount=$settlementAmount");
        
        // Call SettlementManager which now uses ApprovalChainManager internally
        // This ensures settlements follow ONLY app_settings.approval_chain_settlement config
        $result = $settlementManager->createSettlement(
            $requestInvNo,
            $requestType,
            $empId,
            $settlementAmount,
            $currentUserId
        );
        
        if ($result['success']) {
            error_log("Settlement Handler: Settlement created successfully - approval chain from app_settings applied");
            echo json_encode([
                'success' => true,
                'message' => 'Settlement created successfully with configured approval chain',
                'settlement_inv_no' => $result['settlement_inv_no'] ?? null
            ]);
            return;
        } else {
            error_log("Settlement Handler: Settlement creation FAILED - " . ($result['message'] ?? 'Unknown error'));
            echo json_encode([
                'success' => false,
                'message' => 'Settlement creation failed: ' . ($result['message'] ?? 'Unknown error')
            ]);
            return;
        }
        
    } catch (Exception $e) {
        error_log("Settlement Handler Exception in createSettlement: " . $e->getMessage() . " - Line: " . $e->getLine());
        echo json_encode([
            'status' => 'error',
            'message' => 'Server error: ' . $e->getMessage()
        ]);
    }
}

function getSettlementChain($settlementManager) {
    $requestType = $_POST['request_type'] ?? '';
    
    if (empty($requestType)) {
        echo json_encode(['status' => 'error', 'message' => 'Request type required']);
        return;
    }
    
    $chain = $settlementManager->getSettlementChain($requestType);
    echo json_encode([
        'status' => 'success',
        'chain' => $chain,
        'total_levels' => count($chain)
    ]);
}

function checkFinalApproval($currentUserId) {
    global $conDB;
    
    $settlementId = $_POST['settlement_id'] ?? 0;
    
    if ($settlementId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Settlement ID required']);
        return;
    }
    
    try {
        // Get settlement details
        $settlementQry = mysqli_query($conDB, "
            SELECT s.request_inv_no, s.settlement_amount 
            FROM settlement_records s 
            WHERE s.id = $settlementId
            LIMIT 1
        ");
        
        if (!$settlementQry || mysqli_num_rows($settlementQry) == 0) {
            echo json_encode(['success' => false, 'message' => 'Settlement not found']);
            return;
        }
        
        $settlement = mysqli_fetch_assoc($settlementQry);
        mysqli_free_result($settlementQry);
        
        // Get request type ID for settlement
        $typeQry = mysqli_query($conDB, "SELECT id FROM approval_request_types WHERE type_name = 'settlement' LIMIT 1");
        if (!$typeQry || mysqli_num_rows($typeQry) == 0) {
            echo json_encode(['success' => false, 'message' => 'Settlement request type not found']);
            return;
        }
        $typeId = (int)mysqli_fetch_assoc($typeQry)['id'];
        mysqli_free_result($typeQry);
        
        // Get total approval levels and current pending level
        $levelsQry = mysqli_query($conDB, "
            SELECT MAX(approval_level) as max_level,
                   (SELECT approval_level FROM request_approvers 
                    WHERE request_inv_no = '{$settlement['request_inv_no']}' 
                    AND request_type_id = $typeId 
                    AND status = 'pending' 
                    AND approver_id = $currentUserId 
                    LIMIT 1) as current_level
            FROM request_approvers 
            WHERE request_inv_no = '{$settlement['request_inv_no']}' 
            AND request_type_id = $typeId
        ");
        
        if (!$levelsQry) {
            echo json_encode(['success' => false, 'message' => 'Failed to check approval levels']);
            return;
        }
        
        $levels = mysqli_fetch_assoc($levelsQry);
        mysqli_free_result($levelsQry);
        
        $isFinalApproval = ($levels['current_level'] !== null && $levels['current_level'] == $levels['max_level']);
        
        // Check if current user is Finance Manager or Finance Employee
        $userTypeQry = mysqli_query($conDB, "
            SELECT user_type, emp_type FROM admin_login 
            WHERE emp_id = $currentUserId 
            LIMIT 1
        ");
        $userTypeData = $userTypeQry ? mysqli_fetch_assoc($userTypeQry) : null;
        if ($userTypeQry) mysqli_free_result($userTypeQry);
        
        $isFinanceManager = false;
        $isFinanceEmployee = false;
        
        if ($userTypeData) {
            // Finance Manager: user_type = 'finance' AND emp_type = 'Manager'
            $isFinanceManager = ($userTypeData['user_type'] === 'finance' && $userTypeData['emp_type'] === 'Manager');
            // Finance Employee: user_type = 'finance_officer'
            $isFinanceEmployee = ($userTypeData['user_type'] === 'finance_officer');
        }
        
        echo json_encode([
            'success' => true,
            'is_final_approval' => $isFinalApproval,
            'settlement_amount' => floatval($settlement['settlement_amount']),
            'current_level' => $levels['current_level'],
            'max_level' => $levels['max_level'],
            'is_finance_manager' => $isFinanceManager,
            'is_finance_employee' => $isFinanceEmployee
        ]);
        
    } catch (Exception $e) {
        error_log("Check final approval error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error checking approval level']);
    }
}

function getFinanceEmployees() {
    global $conDB;
    
    try {
        // Fetch finance officers with admin_login.user_type = 'finance_officer'
        $financeQuery = mysqli_query($conDB, "
            SELECT DISTINCT e.emp_id, e.name, al.email
            FROM employees e
            INNER JOIN admin_login al ON al.emp_id = e.emp_id
            WHERE al.user_type = 'finance_officer'
            AND e.status = 1
            ORDER BY e.name ASC
        ");
        
        if (!$financeQuery) {
            echo json_encode(['success' => false, 'message' => 'Failed to fetch finance employees']);
            return;
        }
        
        $employees = [];
        while ($emp = mysqli_fetch_assoc($financeQuery)) {
            $employees[] = [
                'emp_id' => $emp['emp_id'],
                'name' => getDisplayName($emp['name']),
                'email' => $emp['email']
            ];
        }
        mysqli_free_result($financeQuery);
        
        echo json_encode([
            'success' => true,
            'employees' => $employees
        ]);
        
    } catch (Exception $e) {
        error_log("Get finance employees error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error fetching finance employees']);
    }
}

function getUploadErrorMessage($errorCode) {
    switch ((int)$errorCode) {
        case UPLOAD_ERR_INI_SIZE:
            return 'Payment proof exceeds the server upload_max_filesize limit';
        case UPLOAD_ERR_FORM_SIZE:
            return 'Payment proof exceeds the maximum allowed form size';
        case UPLOAD_ERR_PARTIAL:
            return 'Payment proof was only partially uploaded';
        case UPLOAD_ERR_NO_FILE:
            return 'Payment proof is required for final approval';
        case UPLOAD_ERR_NO_TMP_DIR:
            return 'Server temporary upload folder is missing';
        case UPLOAD_ERR_CANT_WRITE:
            return 'Server failed to write the payment proof file';
        case UPLOAD_ERR_EXTENSION:
            return 'Payment proof upload was blocked by a server extension';
        default:
            return 'Failed to upload payment proof';
    }
}

function approveSettlement($settlementManager, $currentUserId) {
    global $conDB, $pdo;
    
    $settlementInvNo = $_POST['settlement_inv_no'] ?? '';
    $settlementId = $_POST['settlement_id'] ?? 0;
    $empId = $_POST['emp_id'] ?? 0;
    $approvalComment = $_POST['approval_comment'] ?? '';
    $isFinalApproval = isset($_POST['is_final_approval']) && $_POST['is_final_approval'] == '1';
    
    if (empty($settlementInvNo) || $settlementId <= 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Settlement invoice number and ID required'
        ]);
        return;
    }
    
    // Handle final approval specific fields
    $payerId = null;
    $approvedAmount = null;
    $paymentProofPath = null;
    $isOtherEmployeePaying = false;
    
    if ($isFinalApproval) {
        $payerId = $_POST['payer_id'] ?? null;
        $approvedAmount = $_POST['approved_amount'] ?? null;
        
        // Check if "Other Finance Employee" was selected
        $payerType = $_POST['payer_type'] ?? 'self';
        $isOtherEmployeePaying = ($payerType === 'other');

        // If payer_id is missing, try to resolve it from settlement_records
        if (empty($payerId) && !empty($settlementInvNo)) {
            $payerQry = mysqli_query($conDB, "
                SELECT settlement_approver
                FROM settlement_records
                WHERE request_inv_no = '$settlementInvNo'
                LIMIT 1
            ");
            $payerRow = $payerQry ? mysqli_fetch_assoc($payerQry) : null;
            if ($payerQry) mysqli_free_result($payerQry);

            if (!empty($payerRow['settlement_approver'])) {
                $payerId = $payerRow['settlement_approver'];
            }
        }

        // If still empty and payer is self, default to current user
        if (empty($payerId) && !$isOtherEmployeePaying) {
            $payerId = $currentUserId;
        }
        
        // Validate final approval fields
        if (empty($payerId) && $isOtherEmployeePaying) {
            echo json_encode(['success' => false, 'message' => 'Payer ID required for final approval']);
            return;
        }
        
        // If "Myself" selected, require amount and payment proof
        if (!$isOtherEmployeePaying) {
            if (empty($approvedAmount)) {
                echo json_encode(['success' => false, 'message' => 'Approved amount required for final approval']);
                return;
            }
            
            // Handle payment proof file upload
            if (!isset($_FILES['payment_proof'])) {
                echo json_encode(['success' => false, 'message' => 'Payment proof is required for final approval']);
                return;
            }

            if ($_FILES['payment_proof']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = __DIR__ . '/../../uploads/settlement_proofs/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                $fileExtension = pathinfo($_FILES['payment_proof']['name'], PATHINFO_EXTENSION);
                $fileName = 'settlement_' . $settlementInvNo . '_proof_' . time() . '.' . $fileExtension;
                $targetPath = $uploadDir . $fileName;
                
                if (move_uploaded_file($_FILES['payment_proof']['tmp_name'], $targetPath)) {
                    $paymentProofPath = 'uploads/settlement_proofs/' . $fileName;
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to upload payment proof']);
                    return;
                }
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => getUploadErrorMessage($_FILES['payment_proof']['error'])
                ]);
                return;
            }
        }
    }
    
    try {
        // Get request type ID for settlement
        $typeQry = mysqli_query($conDB, "SELECT id FROM approval_request_types WHERE type_name = 'settlement' LIMIT 1");
        if (!$typeQry || mysqli_num_rows($typeQry) == 0) {
            echo json_encode(['success' => false, 'message' => 'Settlement request type not found']);
            return;
        }
        $typeId = (int)mysqli_fetch_assoc($typeQry)['id'];
        mysqli_free_result($typeQry);
        
        // Get pending approval row assigned to the current user.
        // This avoids false mismatches when duplicate/stale pending rows exist for the same settlement.
        $currentQry = mysqli_query($conDB, "
            SELECT ra.*, r.settlement_status 
            FROM request_approvers ra
            JOIN settlement_records r ON r.request_inv_no = ra.request_inv_no
            WHERE ra.request_inv_no = '$settlementInvNo' 
            AND ra.request_type_id = $typeId
            AND ra.status = 'pending'
            AND ra.approver_id = $currentUserId
            ORDER BY ra.approval_level ASC, ra.id ASC
            LIMIT 1
        ");
        
        if (!$currentQry || mysqli_num_rows($currentQry) == 0) {
            $hasPendingQry = mysqli_query($conDB, "
                SELECT 1
                FROM request_approvers
                WHERE request_inv_no = '$settlementInvNo'
                AND request_type_id = $typeId
                AND status = 'pending'
                LIMIT 1
            ");
            
            if ($hasPendingQry && mysqli_num_rows($hasPendingQry) > 0) {
                mysqli_free_result($hasPendingQry);
                echo json_encode(['success' => false, 'message' => 'You are not the assigned approver for this settlement']);
            } else {
                if ($hasPendingQry) {
                    mysqli_free_result($hasPendingQry);
                }
                echo json_encode(['success' => false, 'message' => 'No pending approval found for this settlement']);
            }
            return;
        }
        
        $current = mysqli_fetch_assoc($currentQry);
        mysqli_free_result($currentQry);
        
        // Update approval status to 'approved' for current assigned approver row only
        $updateQry = mysqli_query($conDB, "
            UPDATE request_approvers 
            SET status = 'approved', action_date = NOW(), note = '" . mysqli_real_escape_string($conDB, $approvalComment) . "'
            WHERE request_inv_no = '$settlementInvNo' 
            AND request_type_id = $typeId
            AND approver_id = $currentUserId
            AND status = 'pending'
        "); 
        
        if (!$updateQry) {
            echo json_encode(['success' => false, 'message' => 'Failed to update approval status']);
            return;
        }
        
        // Only activate next approval level if this is NOT a final approval with "Other Employee" paying
        // When other employee pays, add them as a new approval level instead
        if ($isFinalApproval && $isOtherEmployeePaying) {
            // Add selected employee as final approver in next level
            $nextLevel = $current['approval_level'] + 1;
            // Fetch from admin_login to get correct email
            $otherPayerQry = mysqli_query($conDB, "SELECT e.name, al.email FROM admin_login al JOIN employees e ON al.emp_id = e.emp_id WHERE al.emp_id = $payerId LIMIT 1");
            $otherPayer = $otherPayerQry ? mysqli_fetch_assoc($otherPayerQry) : null;
            if ($otherPayerQry) mysqli_free_result($otherPayerQry);
            
            if ($otherPayer) {
                // Insert the other employee as the next (final) approver
                $insertOtherQry = mysqli_query($conDB, "
                    INSERT INTO request_approvers 
                    (request_inv_no, request_type_id, approval_level, approver_id, status)
                    VALUES ('$settlementInvNo', $typeId, $nextLevel, $payerId, 'pending')
                ");
            }
        } else {
            // Activate next approval level (change from 'awaiting' to 'pending')
            $nextLevel = $current['approval_level'] + 1;
            mysqli_query($conDB, "
                UPDATE request_approvers 
                SET status = 'pending'
                WHERE request_inv_no = '$settlementInvNo' 
                AND request_type_id = $typeId
                AND approval_level = $nextLevel
                AND status = 'awaiting'
            ");
        }
        
        // Get approver details for notification
        $approverQry = mysqli_query($conDB, "SELECT name, email FROM employees WHERE emp_id = $currentUserId LIMIT 1");
        $approverDetails = $approverQry ? mysqli_fetch_assoc($approverQry) : null;
        if ($approverQry) mysqli_free_result($approverQry);
        
        // Add approval entry to smt_request_status
        $approverName = getDisplayName($approverDetails['name'] ?? 'System');
        $statusQry = mysqli_query($conDB, "
            INSERT INTO smt_request_status (inv_no, emp_id, emp_name, note, status)
            VALUES ('$settlementInvNo', $currentUserId, '" . mysqli_real_escape_string($conDB, $approverName) . "', 
                    'Approved at level {$current['approval_level']}. Comment: " . mysqli_real_escape_string($conDB, $approvalComment) . "', 'approved')
        ");
        
        // Check if all approval levels are now completed (count approved vs total)
        $approvedQry = mysqli_query($conDB, "
            SELECT COUNT(*) as approved FROM request_approvers
            WHERE request_inv_no = '$settlementInvNo'
            AND request_type_id = $typeId
            AND status = 'approved'
        ");
        $approvedCount = $approvedQry ? (int)mysqli_fetch_assoc($approvedQry)['approved'] : 0;
        if ($approvedQry) mysqli_free_result($approvedQry);
        
        // Get total approval levels
        $allApprovalsQry = mysqli_query($conDB, "
            SELECT COUNT(*) as total FROM request_approvers
            WHERE request_inv_no = '$settlementInvNo'
            AND request_type_id = $typeId
        ");
        $totalApprovals = $allApprovalsQry ? (int)mysqli_fetch_assoc($allApprovalsQry)['total'] : 0;
        if ($allApprovalsQry) mysqli_free_result($allApprovalsQry);
        
        $allApprovalsComplete = ($approvedCount === $totalApprovals);
        
        // CALCULATE PAYABLE AMOUNT: Fetch settlement with vacation and salary data
        $settlementDataQry = mysqli_query($conDB, "
            SELECT s.*, 
                   e.gosi, e.country as country_id,
                    v.vac_type, v.fly_type, v.vacdays, v.start_date, v.return_date, v.vacation_salary_type, v.is_deductible,
                    v.overtime_hours, v.deduction_hours, v.deduction_days, v.other_earnings, v.other_deductions, v.auto_gosi_deduction,
                   sal.basic, sal.housing, sal.transport, sal.food, sal.misc, sal.cashier,
                   sal.fuel, sal.tel, sal.other, sal.guard
            FROM settlement_records s
            JOIN employees e ON s.emp_id = e.emp_id
            LEFT JOIN emp_vacation v ON v.request_inv_no = SUBSTR(s.request_inv_no, 6)
            LEFT JOIN emp_salary sal ON sal.emp_id = e.emp_id AND sal.status = 1 AND sal.id = (
                SELECT MAX(sal2.id) FROM emp_salary sal2 WHERE sal2.emp_id = e.emp_id AND sal2.status = 1
            )
            WHERE s.request_inv_no = '$settlementInvNo'
            LIMIT 1
        ");
        
        $calculatedPayableAmount = 0;
        if ($settlementDataQry && mysqli_num_rows($settlementDataQry) > 0) {
            $settlementData = mysqli_fetch_assoc($settlementDataQry);
            mysqli_free_result($settlementDataQry);
            
            // Calculate using same logic as all_settlements.php
            if (!empty($settlementData['vac_type']) && !empty($settlementData['basic'])) {
                $vac_type = $settlementData['vac_type'];
                $fly_type = $settlementData['fly_type'];
                $vacation_salary_type = $settlementData['vacation_salary_type'] ?? 'payroll';
                $approved_days = (float)($settlementData['vacdays'] ?? 0);
                
                // Fallback for legacy/incomplete rows where vacdays is zero but date range exists.
                if (
                    $approved_days <= 0 &&
                    !empty($settlementData['start_date']) &&
                    !empty($settlementData['return_date']) &&
                    strtolower(trim((string)$vac_type)) !== 'encashed'
                ) {
                    try {
                        $start_date_obj = new DateTime($settlementData['start_date']);
                        $return_date_obj = new DateTime($settlementData['return_date']);
                        if ($return_date_obj >= $start_date_obj) {
                            $approved_days = (float)$start_date_obj->diff($return_date_obj)->days + 1;
                        }
                    } catch (Exception $e) {
                        // Keep original approved_days when parsing fails.
                    }
                }
                
                $is_fly_annual = ($vac_type === 'Fly' && $fly_type === 'annual');
                $is_encashment = (trim(strtolower($vac_type)) === 'encashed');
                $is_emergency = ($fly_type === 'emergency');
                $is_local_annual_removed_from_payroll = isLocalAnnualRemovedFromPayroll(
                    $vac_type,
                    $fly_type,
                    $settlementData['country_id'] ?? 0,
                    $approved_days,
                    $settlementData['is_deductible'] ?? 0,
                    $vacation_salary_type
                );
                $is_settlement_payable_vacation = isSettlementPayableVacation(
                    $vac_type,
                    $fly_type,
                    $settlementData['country_id'] ?? 0,
                    $approved_days,
                    $settlementData['is_deductible'] ?? 0,
                    $vacation_salary_type
                );
                
                $non_payable_leave_types = ['Sick Leave', 'Casual Leave', 'Maternity Leave', 'Compassionate Leave', 'Business Trip', 'Compensatory Leave'];
                $is_non_payable_leave = in_array($vac_type, $non_payable_leave_types);
                
                $calculate_payments = !$is_non_payable_leave && !$is_emergency && $is_settlement_payable_vacation;
                
                if ($calculate_payments) {
                    $basic_salary = (float)($settlementData['basic'] ?? 0);
                    $total_monthly_salary = $basic_salary + ($settlementData['housing'] ?? 0) + ($settlementData['transport'] ?? 0) + 
                                          ($settlementData['food'] ?? 0) + ($settlementData['misc'] ?? 0) + ($settlementData['cashier'] ?? 0) + 
                                          ($settlementData['fuel'] ?? 0) + ($settlementData['tel'] ?? 0) + ($settlementData['other'] ?? 0) + 
                                          ($settlementData['guard'] ?? 0);
                    
                    if ($total_monthly_salary > 0) {
                        // Keep 30-day basis for all payroll calculations except Working Days Salary
                        $days_in_month = 30;
                        $working_days_month_days = 30;
                        if (!empty($settlementData['start_date'])) {
                            $start_ts = strtotime($settlementData['start_date']);
                            if ($start_ts !== false) {
                                $working_days_month_days = (int)date('t', $start_ts);
                            }
                        }

                        $daily_rate = round($total_monthly_salary / $days_in_month, 2);
                        $working_daily_rate = ($working_days_month_days > 0) ? round($total_monthly_salary / $working_days_month_days, 2) : 0;
                        $dailyRateDeduction = round($total_monthly_salary / $days_in_month, 2);
                        $hourlyRateDeduction = round($dailyRateDeduction / 8, 2);
                        $overtimeHourlyRate = round((($basic_salary / 240) / 2) + ($total_monthly_salary / 240), 2);
                        
                        $working_days_salary = 0;
                        $vacation_salary = 0;
                        $gosi_deduction = 0;
                        $overtime_amount = 0;
                        $deduction_amount = 0;
                        
                        if ($is_settlement_payable_vacation && !empty($settlementData['start_date'])) {
                            try {
                                $start_date_obj = new DateTime($settlementData['start_date']);
                                $start_day = (int)$start_date_obj->format('d');

                                // Business rule: exclude the start day from working-days salary (working days BEFORE departure).
                                // If vacation starts on day 1, there are zero working days in the same month.
                                if ($start_day === 1) {
                                    $working_days_salary = 0;
                                } else {
                                    // Example: start on March 11 => 10 working days (days 1-10 before departure on 11th)
                                    $working_days = $start_day - 1;
                                    if ($working_days_month_days > 0 && $working_days >= $working_days_month_days) {
                                        $working_days_salary = round($total_monthly_salary);
                                    } else {
                                        $working_days_salary = round(($total_monthly_salary / 30) * $working_days);
                                    }
                                }
                            } catch (Exception $e) {
                                $working_days_salary = 0;
                            }
                        }
                        
                        if ($is_settlement_payable_vacation && $vacation_salary_type === 'payroll') {
                            $vacation_salary = round($daily_rate * $approved_days);
                        }
                        
                        if (!empty($settlementData['overtime_hours']) && $settlementData['overtime_hours'] > 0) {
                            $overtime_amount = round($overtimeHourlyRate * $settlementData['overtime_hours']);
                        }

                        $other_earnings = !empty($settlementData['other_earnings']) ? $settlementData['other_earnings'] : 0;
                        
                        $ded_hours = !empty($settlementData['deduction_hours']) ? $settlementData['deduction_hours'] : 0;
                        $ded_days = !empty($settlementData['deduction_days']) ? $settlementData['deduction_days'] : 0;
                        $other_ded = !empty($settlementData['other_deductions']) ? $settlementData['other_deductions'] : 0;
                        
                        if ($ded_hours > 0 || $ded_days > 0 || $other_ded > 0) {
                            $deduction_hours_amount = round($hourlyRateDeduction * $ded_hours);
                            $deduction_days_amount = round($dailyRateDeduction * $ded_days);
                            $deduction_amount = round($deduction_hours_amount + $deduction_days_amount + $other_ded);
                        }
                        
                        // Check auto_gosi_deduction flag: if 1 (enabled), apply GOSI; if 0 (disabled), skip
                        $auto_gosi_deduction = (int)($settlementData['auto_gosi_deduction'] ?? 1);  // Default to 1 for backward compatibility
                        
                        if ($auto_gosi_deduction && $settlementData['country_id'] == 191 && !empty($settlementData['gosi']) && is_numeric($settlementData['gosi'])) {
                            $gosi_percentage = (float)$settlementData['gosi'];
                            if (($is_fly_annual || $is_local_annual_removed_from_payroll) && $vacation_salary_type === 'payroll') {
                                $gosi_base = (float)$basic_salary + (float)($settlementData['housing'] ?? 0);
                                $gosi_deduction = round(($gosi_base * $gosi_percentage) / 100, 2);
                            }
                        }
                        
                        if ($is_encashment) {
                            $calculatedPayableAmount = 0;
                        } elseif ($is_settlement_payable_vacation) {
                            $calculatedPayableAmount = round(($working_days_salary + $vacation_salary) + $overtime_amount + $other_earnings - $deduction_amount - $gosi_deduction);
                        }
                    }
                }
            }
            
            if ($calculatedPayableAmount <= 0 && !empty($settlementData['settlement_amount'])) {
                $calculatedPayableAmount = round($settlementData['settlement_amount']);
            }
        }
        
        // If "Other Employee" is paying at final approval level, DO NOT mark as complete yet
        // They will need to approve it as the final step
        // Only mark as complete when all approval levels are truly done
        
        $newSettlementStatus = $allApprovalsComplete ? 'completed' : 'pending_approval';
        
        // Update settlement record status
        if ($isFinalApproval) {
            if ($isOtherEmployeePaying) {
                // Other employee paying - keep as pending_approval until they complete it
                $updateSettlementQry = mysqli_query($conDB, "
                    UPDATE settlement_records 
                    SET settlement_status = 'pending_approval',
                        settlement_approver = " . (int)$payerId . ",
                        settlement_amount = $calculatedPayableAmount,
                        updated_at = NOW()
                    WHERE request_inv_no = '$settlementInvNo'
                ");
            } else {
                // Myself paying - update with amount and proof, status depends on all approvals
                $updateSettlementQry = mysqli_query($conDB, "
                    UPDATE settlement_records 
                    SET settlement_status = '$newSettlementStatus',
                        settlement_approver = " . (int)$payerId . ",
                        settlement_amount = " . floatval($approvedAmount) . ",
                        payment_reference = '" . mysqli_real_escape_string($conDB, $paymentProofPath) . "',
                        payment_date = NOW(),
                        updated_at = NOW()
                    WHERE request_inv_no = '$settlementInvNo'
                ");
            }
        } else {
            // Regular approval - update status AND sync settlement amount with calculated value
            $updateSettlementQry = mysqli_query($conDB, "
                UPDATE settlement_records 
                SET settlement_status = '$newSettlementStatus', settlement_amount = $calculatedPayableAmount, updated_at = NOW()
                WHERE request_inv_no = '$settlementInvNo'
            ");
        }
        
        // Log approval in comments table
        $approverName = getDisplayName($approverDetails['name'] ?? 'System');
        $commentQry = mysqli_query($conDB, "
            INSERT INTO approval_comments (request_inv_no, request_type, approver_emp_id, approver_name, approval_action, comment_text, comment_date)
            VALUES ('$settlementInvNo', 'settlement', $currentUserId, '" . mysqli_real_escape_string($conDB, $approverName) . "', 'approved', 
                    '" . mysqli_real_escape_string($conDB, $approvalComment) . "', NOW())
        ");
        
        // If all approvals done, notify employee; otherwise notify next approver
        if ($allApprovalsComplete) {
            // All approvals complete - notify employee
            $empQry = mysqli_query($conDB, "SELECT email, name FROM employees WHERE emp_id = $empId LIMIT 1");
            $emp = $empQry ? mysqli_fetch_assoc($empQry) : null;
            if ($empQry) mysqli_free_result($empQry);
            
            if ($emp) {
                // Create browser notification for employee
                create_browser_notification($conDB, $empId, 
                    'Settlement Approved',
                    'Your settlement ' . $settlementInvNo . ' has been fully approved and is ready for processing.',
                    'all_settlements.php?status=approved'
                );
                
                // Get settlement details for email
                $settlementDetailsQry = mysqli_query($conDB, "
                    SELECT s.*, e.name as emp_name, e.dept
                    FROM settlement_records s
                    JOIN employees e ON e.emp_id = s.emp_id
                    WHERE s.request_inv_no = '$settlementInvNo'
                    LIMIT 1
                ");
                $settlementDetails = $settlementDetailsQry ? mysqli_fetch_assoc($settlementDetailsQry) : null;
                if ($settlementDetailsQry) mysqli_free_result($settlementDetailsQry);
                
                if ($settlementDetails && !empty($emp['email'])) {
                    $settlementAmount = number_format($settlementDetails['settlement_amount'], 2);
                    
                    // Send email notification to employee
                    $emailData = [
                        'APPROVER_NAME' => $emp['name'],
                        'REQUEST_ID' => $settlementInvNo,
                        'REQUEST_TITLE' => 'Settlement Approval Complete',
                        'EMPLOYEE_NAME' => $emp['name'],
                        'EMPLOYEE_ID' => $empId,
                        'DEPARTMENT' => $settlementDetails['dept'] ?? 'N/A',
                        'REQUEST_SOURCE' => $settlementDetails['request_type'] ?? 'Settlement',
                        'SETTLEMENT_AMOUNT' => $settlementAmount,
                        'EMAIL_MESSAGE' => 'Your settlement request has been fully approved by all approvers. It is now ready for payment processing.',
                        'REQUEST_URL' => get_base_url() . '/all_settlements.php?status=approved'
                    ];
                    
                    error_log("Settlement Handler: Sending completion email to applicant - email=" . $emp['email'] . ", name=" . $emp['name'] . ", settlement=$settlementInvNo");
                    
                    try {
                        $emailResult = send_approval_email($conDB, $emp['email'], $emp['name'],
                            'Settlement Request Approved - ' . $settlementInvNo, 'settlement_approval', $emailData
                        );
                        error_log("Settlement Handler: Email to applicant result = " . ($emailResult ? 'TRUE (success)' : 'FALSE (failed)'));
                    } catch (Exception $e) {
                        error_log("Settlement Handler: Exception sending email to applicant - " . $e->getMessage());
                    }
                }
            }
        } elseif ($isFinalApproval && $isOtherEmployeePaying && $otherPayer) {
            // Notify the selected other employee that they need to process payment
            $notificationMsg = 'Settlement ' . $settlementInvNo . ' assigned to you for payment processing.';
            create_browser_notification($conDB, $payerId, 
                'Settlement Payment Assignment',
                $notificationMsg,
                'all_settlements.php?status=my_pending'
            );
            
            // Send email to other employee
            $otherPayerEmail = $otherPayer['email'];
            
            // Get settlement details for email
            $settlementDetailsQry = mysqli_query($conDB, "
                SELECT s.*, e.name as emp_name, e.dept
                FROM settlement_records s
                JOIN employees e ON e.emp_id = s.emp_id
                WHERE s.request_inv_no = '$settlementInvNo'
                LIMIT 1
            ");
            $settlementDetails = $settlementDetailsQry ? mysqli_fetch_assoc($settlementDetailsQry) : null;
            if ($settlementDetailsQry) mysqli_free_result($settlementDetailsQry);
            
            if ($settlementDetails && !empty($otherPayerEmail)) {
                $settlementAmount = number_format($settlementDetails['settlement_amount'], 2);
                $empName = getDisplayName($settlementDetails['emp_name']);
                
                // Send email notification using same template as other approvers
                $emailData = [
                    'APPROVER_NAME' => $otherPayer['name'],
                    'REQUEST_ID' => $settlementInvNo,
                    'REQUEST_TITLE' => 'Settlement Approval',
                    'REQUESTER_NAME' => $approverName,
                    'EMPLOYEE_NAME' => $empName,
                    'EMPLOYEE_ID' => $settlementDetails['emp_id'] ?? 'N/A',
                    'DEPARTMENT' => $settlementDetails['dept'] ?? 'N/A',
                    'REQUEST_SOURCE' => 'Settlement Payment',
                    'SETTLEMENT_AMOUNT' => $settlementAmount,
                    'EMAIL_MESSAGE' => 'You have been assigned to process payment for the following settlement.',
                    'REQUEST_URL' => get_base_url() . '/all_settlements.php?status=my_pending'
                ];
                
                error_log("Settlement Handler: Sending email to other payer - email=$otherPayerEmail, name={$otherPayer['name']}, settlement=$settlementInvNo");
                
                try {
                    $emailResult = send_approval_email($conDB, $otherPayerEmail, $otherPayer['name'],
                        'Settlement Approval Required - ' . $settlementInvNo, 'settlement_approval', $emailData
                    );
                    error_log("Settlement Handler: Email to other payer result = " . ($emailResult ? 'TRUE (success)' : 'FALSE (failed)'));
                } catch (Exception $e) {
                    error_log("Settlement Handler: Exception sending email to other payer - " . $e->getMessage());
                }
            }
        } else {
            // Get next approver and notify them
            error_log("Settlement Handler: Looking for next pending approver for $settlementInvNo");
            
            $nextQry = mysqli_query($conDB, "
                SELECT ra.approver_id, 
                       e.name,
                       al.email
                FROM request_approvers ra
                JOIN employees e ON e.emp_id = ra.approver_id
                INNER JOIN admin_login al ON al.emp_id = ra.approver_id
                WHERE ra.request_inv_no = '$settlementInvNo'
                AND ra.request_type_id = $typeId
                AND ra.status = 'pending'
                ORDER BY ra.approval_level ASC
                LIMIT 1
            ");
            $nextApprover = $nextQry ? mysqli_fetch_assoc($nextQry) : null;
            if ($nextQry) mysqli_free_result($nextQry);
            
            if ($nextApprover) {
                error_log("Settlement Handler: Next approver found - emp_id={$nextApprover['approver_id']}, name={$nextApprover['name']}, email={$nextApprover['email']}");
                
                $approverEmail = $nextApprover['email'];
                error_log("Settlement Handler: Approver email from admin_login: $approverEmail");
                
                // Get settlement and employee details for email
                $settlementDetailsQry = mysqli_query($conDB, "
                    SELECT s.*, e.name as emp_name, e.dept
                    FROM settlement_records s
                    JOIN employees e ON e.emp_id = s.emp_id
                    WHERE s.request_inv_no = '$settlementInvNo'
                    LIMIT 1
                ");
                $settlementDetails = $settlementDetailsQry ? mysqli_fetch_assoc($settlementDetailsQry) : null;
                if ($settlementDetailsQry) mysqli_free_result($settlementDetailsQry);
                
                error_log("Settlement Handler: Settlement details retrieved - " . json_encode($settlementDetails));
                
                // Extract request type from multiple sources
                $sourceRequest = '';
                
                // First, try to get from settlement_records.request_type column (primary source)
                if ($settlementDetails && !empty($settlementDetails['request_type'])) {
                    $typeValue = $settlementDetails['request_type'];
                    // Map database values to display names
                    $typeMap = [
                        'annual_vacation' => 'Vacation',
                        'vacation' => 'Vacation',
                        'VAC' => 'Vacation',
                        'loan' => 'Loan',
                        'LOAN' => 'Loan',
                        'advance' => 'Advance',
                        'ADV' => 'Advance',
                        'overtime' => 'Overtime',
                        'OT' => 'Overtime',
                        'bonus' => 'Bonus'
                    ];
                    $sourceRequest = $typeMap[$typeValue] ?? $typeValue;
                    error_log("Settlement Handler: Source request found in settlement_records.request_type: '$typeValue' -> '$sourceRequest'");
                }
                
                // If still not found, extract from invoice number as fallback
                if (empty($sourceRequest) && !empty($settlementInvNo)) {
                    error_log("Settlement Handler: Attempting fallback extraction from invoice number: $settlementInvNo");
                    if (preg_match('/SETL-([A-Z]+)-/', $settlementInvNo, $matches)) {
                        $typeCode = strtoupper(trim($matches[1]));
                        $typeMapFallback = [
                            'VAC' => 'Vacation',
                            'LOAN' => 'Loan',
                            'ADV' => 'Advance',
                            'OT' => 'Overtime',
                            'BONUS' => 'Bonus'
                        ];
                        $sourceRequest = $typeMapFallback[$typeCode] ?? $typeCode;
                        error_log("Settlement Handler: Fallback matched typeCode='$typeCode' -> '$sourceRequest'");
                    }
                }
                
                error_log("Settlement Handler: Final SOURCE_REQUEST value: '" . ($sourceRequest ?: 'EMPTY') . "'");
                
                // Create browser notification for next approver
                $browserNotifResult = create_browser_notification($conDB, $nextApprover['approver_id'],
                    'Settlement Approval Required',
                    'Settlement ' . $settlementInvNo . ' requires your approval.',
                    'all_settlements.php?status=my_pending'
                );
                error_log("Settlement Handler: Browser notification result = " . ($browserNotifResult ? 'success' : 'failed'));
                
                // Send email notification
                error_log("Settlement Handler: Attempting to send email to $approverEmail for {$nextApprover['name']}");
                
                $emailData = [
                    'APPROVER_NAME' => $nextApprover['name'],
                    'REQUEST_ID' => $settlementInvNo,
                    'REQUEST_TITLE' => 'Settlement Approval',
                    'REQUESTER_NAME' => $approverName,
                    'EMPLOYEE_NAME' => $settlementDetails['emp_name'] ?? 'N/A',
                    'EMPLOYEE_ID' => $settlementDetails['emp_id'] ?? 'N/A',
                    'DEPARTMENT' => $settlementDetails['dept'] ?? 'N/A',
                    'REQUEST_SOURCE' => $sourceRequest ?: 'N/A',
                    'SETTLEMENT_AMOUNT' => number_format($settlementDetails['settlement_amount'] ?? 0, 2),
                    'EMAIL_MESSAGE' => 'A settlement requires your approval.',
                    'REQUEST_URL' => get_base_url() . '/all_settlements.php?status=my_pending'
                ];
                
                error_log("Settlement Handler: Email data - " . json_encode($emailData));
                
                try {
                    $emailResult = send_approval_email($conDB, $approverEmail, $nextApprover['name'], 
                        'Settlement Approval Required - ' . $settlementInvNo, 'settlement_approval', $emailData
                    );
                    error_log("Settlement Handler: Email send result = " . ($emailResult ? 'TRUE (success)' : 'FALSE (failed)'));
                    
                    if (!$emailResult) {
                        error_log("Settlement Handler: WARNING - Email send_approval_email returned false. Check error.log for SEND_EMAIL_DEBUG messages.");
                    }
                } catch (Exception $e) {
                    error_log("Settlement Handler: EXCEPTION during email send - " . $e->getMessage());
                    error_log("Settlement Handler: Exception trace - " . $e->getTraceAsString());
                }
            } else {
                error_log("Settlement Handler: No next pending approver found!");
            }
        }
        
        echo json_encode([
            'success' => true,
            'message' => $allApprovalsComplete ? __('settlement_approved_all_approvals_complete') : __('settlement_approved_forwarded_to_next_approver'),
            'all_approvals_complete' => $allApprovalsComplete
        ]);
        
    } catch (Exception $e) {
        error_log("Settlement approval error: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => __('error') . ': ' . $e->getMessage()
        ]);
    }
}

/**
 * Upload Settlement Attachment via Dropzone
 * Stores file and immediately saves metadata to database
 */
function uploadSettlementAttachment($currentUserId) {
    global $pdo;
    
    // Log the action
    error_log("uploadSettlementAttachment called - User: $currentUserId");
    error_log("POST keys: " . json_encode(array_keys($_POST)));
    error_log("FILES keys: " . json_encode(array_keys($_FILES)));
    
    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        $errorCode = $_FILES['file']['error'] ?? 'UNKNOWN';
        error_log("Upload error: $errorCode");
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'File upload failed'
        ]);
        return;
    }
    
    try {
        $file = $_FILES['file'];
        $settlementId = intval($_POST['settlement_id'] ?? 0);
        $requestInvNo = trim($_POST['settlement_inv_no'] ?? '');
        $originalFileName = basename($file['name']);
        $fileSize = $file['size'];
        $fileTmpPath = $file['tmp_name'];
        
        // Validate
        if (!$settlementId || !$requestInvNo) {
            throw new Exception("Missing settlement context");
        }
        
        // Create upload directory
        $uploadDir = __DIR__ . '/../../uploads/settlement_attachments/' . date('Y/m');
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Generate unique filename
        $fileExtension = strtolower(pathinfo($originalFileName, PATHINFO_EXTENSION));
        $uniqueFileName = 'settlement_' . uniqid() . '_' . time() . '.' . $fileExtension;
        $filePath = $uploadDir . '/' . $uniqueFileName;
        
        // Move file
        if (!move_uploaded_file($fileTmpPath, $filePath)) {
            throw new Exception("Failed to save file to disk");
        }
        
        error_log("File saved: $filePath");
        
        // Get emp_id from settlement_records
        $stmtEmp = $pdo->prepare("SELECT emp_id FROM settlement_records WHERE id = ? LIMIT 1");
        $stmtEmp->execute([$settlementId]);
        $empRecord = $stmtEmp->fetch();
        $empId = $empRecord['emp_id'] ?? $currentUserId;
        
        // Save to database  (CORRECT column names: request_inv_no, not settlement_inv_no)
        $stmt = $pdo->prepare("
            INSERT INTO settlement_attachments 
            (settlement_id, request_inv_no, emp_id, file_name, file_path, file_type, file_size, attachment_category, uploaded_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'supporting_document', ?)
        ");
        
        $mimeType = mime_content_type($filePath) ?? 'application/octet-stream';
        $stmt->execute([
            $settlementId,
            $requestInvNo,  // <-- CORRECT: request_inv_no column
            $empId,
            $uniqueFileName,
            $uniqueFileName,
            $mimeType,
            $fileSize,
            $currentUserId
        ]);
        
        error_log("✓ Attachment saved to DB: settlement_id=$settlementId, request_inv_no=$requestInvNo, file=$uniqueFileName");
        
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'uploaded_filename' => $uniqueFileName,
            'original_filename' => $originalFileName,
            'message' => 'File uploaded successfully'
        ]);
        
    } catch (Exception $e) {
        error_log("✗ Upload error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}

/**
 * Approve Settlement with WPS File Upload (Combined Operation for HR Payroll)
 * Handles both approval and WPS file upload in a single request
 */
/**
 * Approve Settlement with Multiple Attachments
 * Handles approval workflow and stores multiple files in settlement_attachments table
 */
function approveSettlementWithAttachments($settlementManager, $currentUserId) {
    global $conDB, $pdo;
    
    $settlementInvNo = $_POST['settlement_inv_no'] ?? '';
    $settlementId = $_POST['settlement_id'] ?? 0;
    $empId = $_POST['emp_id'] ?? 0;
    $approvalComment = $_POST['approval_comment'] ?? '';
    $postedHRPayroll = ($_POST['is_hr_payroll'] ?? '0') === '1';
    $sessionUserTypeNormalized = preg_replace('/[\s-]+/', '_', strtolower(trim((string)($_SESSION['user_type'] ?? ''))));
    $isHRPayroll = $postedHRPayroll || in_array($sessionUserTypeNormalized, ['hr_payroll', 'hrpayroll'], true);
    $attachmentCount = intval($_POST['attachment_count'] ?? 0);
    
    if (empty($settlementInvNo) || $settlementId <= 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Settlement invoice number and ID required'
        ]);
        return;
    }
    
    try {
        // STEP 1: Approval workflow - same as approveSettlementWithWPS
        $typeQry = mysqli_query($conDB, "SELECT id FROM approval_request_types WHERE type_name = 'settlement' LIMIT 1");
        if (!$typeQry || mysqli_num_rows($typeQry) == 0) {
            echo json_encode(['success' => false, 'message' => 'Settlement request type not found']);
            return;
        }
        $typeId = (int)mysqli_fetch_assoc($typeQry)['id'];
        mysqli_free_result($typeQry);
        
        // Get pending approval row assigned to the current user.
        // This avoids false mismatches when duplicate/stale pending rows exist for the same settlement.
        $currentQry = mysqli_query($conDB, "
            SELECT ra.*, r.settlement_status 
            FROM request_approvers ra
            JOIN settlement_records r ON r.request_inv_no = ra.request_inv_no
            WHERE ra.request_inv_no = '$settlementInvNo' 
            AND ra.request_type_id = $typeId
            AND ra.status = 'pending'
            AND ra.approver_id = $currentUserId
            ORDER BY ra.approval_level ASC, ra.id ASC
            LIMIT 1
        ");
        
        if (!$currentQry || mysqli_num_rows($currentQry) == 0) {
            $hasPendingQry = mysqli_query($conDB, "
                SELECT 1
                FROM request_approvers
                WHERE request_inv_no = '$settlementInvNo'
                AND request_type_id = $typeId
                AND status = 'pending'
                LIMIT 1
            ");
            
            if ($hasPendingQry && mysqli_num_rows($hasPendingQry) > 0) {
                mysqli_free_result($hasPendingQry);
                echo json_encode(['success' => false, 'message' => 'You are not the assigned approver for this settlement']);
            } else {
                if ($hasPendingQry) {
                    mysqli_free_result($hasPendingQry);
                }
                echo json_encode(['success' => false, 'message' => 'No pending approval found for this settlement']);
            }
            return;
        }
        
        $current = mysqli_fetch_assoc($currentQry);
        mysqli_free_result($currentQry);
        
        // Begin transaction
        $pdo->beginTransaction();
        
        // Update approval status to 'approved' for current assigned approver row only
        $updateQry = mysqli_query($conDB, "
            UPDATE request_approvers 
            SET status = 'approved', action_date = NOW(), note = '" . mysqli_real_escape_string($conDB, $approvalComment) . "'
            WHERE request_inv_no = '$settlementInvNo' 
            AND request_type_id = $typeId
            AND approver_id = $currentUserId
            AND status = 'pending'
        ");
        
        if (!$updateQry) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => 'Failed to update approval status']);
            return;
        }
        
        // Activate next approval level
        $nextLevel = $current['approval_level'] + 1;
        mysqli_query($conDB, "
            UPDATE request_approvers 
            SET status = 'pending'
            WHERE request_inv_no = '$settlementInvNo' 
            AND request_type_id = $typeId
            AND approval_level = $nextLevel
            AND status = 'awaiting'
        ");
        
        // Get approver details
        $approverQry = mysqli_query($conDB, "SELECT name, email FROM employees WHERE emp_id = $currentUserId LIMIT 1");
        $approverDetails = $approverQry ? mysqli_fetch_assoc($approverQry) : null;
        if ($approverQry) mysqli_free_result($approverQry);
        
        // Add approval entry to smt_request_status
        $approverName = getDisplayName($approverDetails['name'] ?? 'System');
        mysqli_query($conDB, "
            INSERT INTO smt_request_status (inv_no, emp_id, emp_name, note, status)
            VALUES ('$settlementInvNo', $currentUserId, '" . mysqli_real_escape_string($conDB, $approverName) . "', 
                    'Approved at level {$current['approval_level']}. Comment: " . mysqli_real_escape_string($conDB, $approvalComment) . "', 'approved')
        ");
        
        // Check if all approval levels are complete
        $approvedQry = mysqli_query($conDB, "
            SELECT COUNT(*) as approved FROM request_approvers
            WHERE request_inv_no = '$settlementInvNo'
            AND request_type_id = $typeId
            AND status = 'approved'
        ");
        $approvedCount = $approvedQry ? (int)mysqli_fetch_assoc($approvedQry)['approved'] : 0;
        if ($approvedQry) mysqli_free_result($approvedQry);
        
        $allApprovalsQry = mysqli_query($conDB, "
            SELECT COUNT(*) as total FROM request_approvers
            WHERE request_inv_no = '$settlementInvNo'
            AND request_type_id = $typeId
        ");
        $totalApprovals = $allApprovalsQry ? (int)mysqli_fetch_assoc($allApprovalsQry)['total'] : 0;
        if ($allApprovalsQry) mysqli_free_result($allApprovalsQry);
        
        $allApprovalsComplete = ($approvedCount === $totalApprovals);
        
        // CALCULATE PAYABLE AMOUNT: Fetch settlement with vacation and salary data
        $settlementDataQry = mysqli_query($conDB, "
            SELECT s.*, 
                   e.gosi, e.country as country_id,
                                     v.vac_type, v.fly_type, v.vacdays, v.start_date, v.return_date, v.vacation_salary_type, v.is_deductible,
                     v.overtime_hours, v.deduction_hours, v.deduction_days, v.other_earnings, v.other_deductions,
                   sal.basic, sal.housing, sal.transport, sal.food, sal.misc, sal.cashier,
                   sal.fuel, sal.tel, sal.other, sal.guard
            FROM settlement_records s
            JOIN employees e ON s.emp_id = e.emp_id
            LEFT JOIN emp_vacation v ON v.request_inv_no = SUBSTR(s.request_inv_no, 6)
            LEFT JOIN emp_salary sal ON sal.emp_id = e.emp_id AND sal.status = 1 AND sal.id = (
                SELECT MAX(sal2.id) FROM emp_salary sal2 WHERE sal2.emp_id = e.emp_id AND sal2.status = 1
            )
            WHERE s.request_inv_no = '$settlementInvNo'
            LIMIT 1
        ");
        
        $calculatedPayableAmount = 0;
        if ($settlementDataQry && mysqli_num_rows($settlementDataQry) > 0) {
            $settlementData = mysqli_fetch_assoc($settlementDataQry);
            mysqli_free_result($settlementDataQry);
            
            // Calculate using same logic as all_settlements.php
            if (!empty($settlementData['vac_type']) && !empty($settlementData['basic'])) {
                $vac_type = $settlementData['vac_type'];
                $fly_type = $settlementData['fly_type'];
                $vacation_salary_type = $settlementData['vacation_salary_type'] ?? 'payroll';
                $approved_days = (float)($settlementData['vacdays'] ?? 0);
                
                // Fallback for legacy/incomplete rows where vacdays is zero but date range exists.
                if (
                    $approved_days <= 0 &&
                    !empty($settlementData['start_date']) &&
                    !empty($settlementData['return_date']) &&
                    strtolower(trim((string)$vac_type)) !== 'encashed'
                ) {
                    try {
                        $start_date_obj = new DateTime($settlementData['start_date']);
                        $return_date_obj = new DateTime($settlementData['return_date']);
                        if ($return_date_obj >= $start_date_obj) {
                            $approved_days = (float)$start_date_obj->diff($return_date_obj)->days + 1;
                        }
                    } catch (Exception $e) {
                        // Keep original approved_days when parsing fails.
                    }
                }
                
                $is_fly_annual = ($vac_type === 'Fly' && $fly_type === 'annual');
                $is_encashment = (trim(strtolower($vac_type)) === 'encashed');
                $is_emergency = ($fly_type === 'emergency');
                $is_local_annual_removed_from_payroll = isLocalAnnualRemovedFromPayroll(
                    $vac_type,
                    $fly_type,
                    $settlementData['country_id'] ?? 0,
                    $approved_days,
                    $settlementData['is_deductible'] ?? 0,
                    $vacation_salary_type
                );
                $is_settlement_payable_vacation = isSettlementPayableVacation(
                    $vac_type,
                    $fly_type,
                    $settlementData['country_id'] ?? 0,
                    $approved_days,
                    $settlementData['is_deductible'] ?? 0,
                    $vacation_salary_type
                );
                
                $non_payable_leave_types = ['Sick Leave', 'Casual Leave', 'Maternity Leave', 'Compassionate Leave', 'Business Trip', 'Compensatory Leave'];
                $is_non_payable_leave = in_array($vac_type, $non_payable_leave_types);
                
                $calculate_payments = !$is_non_payable_leave && !$is_emergency && $is_settlement_payable_vacation;
                
                if ($calculate_payments) {
                    $basic_salary = (float)($settlementData['basic'] ?? 0);
                    $total_monthly_salary = $basic_salary + ($settlementData['housing'] ?? 0) + ($settlementData['transport'] ?? 0) + 
                                          ($settlementData['food'] ?? 0) + ($settlementData['misc'] ?? 0) + ($settlementData['cashier'] ?? 0) + 
                                          ($settlementData['fuel'] ?? 0) + ($settlementData['tel'] ?? 0) + ($settlementData['other'] ?? 0) + 
                                          ($settlementData['guard'] ?? 0);
                    
                    if ($total_monthly_salary > 0) {
                        // Keep 30-day basis for all payroll calculations except Working Days Salary
                        $days_in_month = 30;
                        $working_days_month_days = 30;
                        if (!empty($settlementData['start_date'])) {
                            $start_ts = strtotime($settlementData['start_date']);
                            if ($start_ts !== false) {
                                $working_days_month_days = (int)date('t', $start_ts);
                            }
                        }

                        $daily_rate = round($total_monthly_salary / $days_in_month, 2);
                        $working_daily_rate = ($working_days_month_days > 0) ? round($total_monthly_salary / $working_days_month_days, 2) : 0;
                        $dailyRateDeduction = round($total_monthly_salary / $days_in_month, 2);
                        $hourlyRateDeduction = round($dailyRateDeduction / 8, 2);
                        $overtimeHourlyRate = round((($basic_salary / 240) / 2) + ($total_monthly_salary / 240), 2);
                        
                        $working_days_salary = 0;
                        $vacation_salary = 0;
                        $gosi_deduction = 0;
                        $overtime_amount = 0;
                        $deduction_amount = 0;
                        
                        if ($is_settlement_payable_vacation && !empty($settlementData['start_date'])) {
                            try {
                                $start_date_obj = new DateTime($settlementData['start_date']);
                                $start_day = (int)$start_date_obj->format('d');

                                // Business rule: exclude the start day from working-days salary (working days BEFORE departure).
                                // If vacation starts on day 1, there are zero working days in the same month.
                                if ($start_day === 1) {
                                    $working_days_salary = 0;
                                } else {
                                    // Example: start on March 11 => 10 working days (days 1-10 before departure on 11th)
                                    $working_days = $start_day - 1;
                                    if ($working_days_month_days > 0 && $working_days >= $working_days_month_days) {
                                        $working_days_salary = round($total_monthly_salary);
                                    } else {
                                        $working_days_salary = round(($total_monthly_salary / 30) * $working_days);
                                    }
                                }
                            } catch (Exception $e) {
                                $working_days_salary = 0;
                            }
                        }
                        
                        if ($is_settlement_payable_vacation && $vacation_salary_type === 'payroll') {
                            $vacation_salary = round($daily_rate * $approved_days);
                        }
                        
                        if (!empty($settlementData['overtime_hours']) && $settlementData['overtime_hours'] > 0) {
                            $overtime_amount = round($overtimeHourlyRate * $settlementData['overtime_hours']);
                        }

                        $other_earnings = !empty($settlementData['other_earnings']) ? $settlementData['other_earnings'] : 0;
                        
                        $ded_hours = !empty($settlementData['deduction_hours']) ? $settlementData['deduction_hours'] : 0;
                        $ded_days = !empty($settlementData['deduction_days']) ? $settlementData['deduction_days'] : 0;
                        $other_ded = !empty($settlementData['other_deductions']) ? $settlementData['other_deductions'] : 0;
                        
                        if ($ded_hours > 0 || $ded_days > 0 || $other_ded > 0) {
                            $deduction_hours_amount = round($hourlyRateDeduction * $ded_hours);
                            $deduction_days_amount = round($dailyRateDeduction * $ded_days);
                            $deduction_amount = round($deduction_hours_amount + $deduction_days_amount + $other_ded);
                        }
                        
                        // Check auto_gosi_deduction flag: if 1 (enabled), apply GOSI; if 0 (disabled), skip
                        $auto_gosi_deduction = (int)($settlementData['auto_gosi_deduction'] ?? 1);  // Default to 1 for backward compatibility
                        
                        if ($auto_gosi_deduction && $settlementData['country_id'] == 191 && !empty($settlementData['gosi']) && is_numeric($settlementData['gosi'])) {
                            $gosi_percentage = (float)$settlementData['gosi'];
                            if (($is_fly_annual || $is_local_annual_removed_from_payroll) && $vacation_salary_type === 'payroll') {
                                $gosi_base = (float)$basic_salary + (float)($settlementData['housing'] ?? 0);
                                $gosi_deduction = round(($gosi_base * $gosi_percentage) / 100, 2);
                            }
                        }
                        
                        if ($is_encashment) {
                            $calculatedPayableAmount = 0;
                        } elseif ($is_settlement_payable_vacation) {
                            $calculatedPayableAmount = round(($working_days_salary + $vacation_salary) + $overtime_amount + $other_earnings - $deduction_amount - $gosi_deduction);
                        }
                    }
                }
            }
            
            if ($calculatedPayableAmount <= 0 && !empty($settlementData['settlement_amount'])) {
                $calculatedPayableAmount = round($settlementData['settlement_amount']);
            }
        }
        
        // Update settlement status AND settlement amount with calculated value
        $newSettlementStatus = $allApprovalsComplete ? 'completed' : 'pending_approval';
        $updateSettlementQry = mysqli_query($conDB, "
            UPDATE settlement_records 
            SET settlement_status = '$newSettlementStatus', settlement_amount = $calculatedPayableAmount, updated_at = NOW()
            WHERE request_inv_no = '$settlementInvNo'
        ");
        
        // STEP 2: Handle multiple attachment uploads
        $uploadedCount = 0;
        $uploadedFiles = [];
        
        if ($attachmentCount > 0) {
            $uploadBaseDir = __DIR__ . '/../../uploads/settlement_attachments';
            $yearMonth = date('Y/m');
            $uploadDir = $uploadBaseDir . '/' . $yearMonth;
            
            // Create directory if not exists
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            // Process each uploaded file
            for ($i = 0; $i < $attachmentCount; $i++) {
                $fileKey = "attachment_$i";
                
                if (isset($_FILES[$fileKey]) && $_FILES[$fileKey]['error'] === UPLOAD_ERR_OK) {
                    $file = $_FILES[$fileKey];
                    $originalFileName = basename($file['name']);
                    $fileSize = $file['size'];
                    $fileTmpPath = $file['tmp_name'];
                    
                    // Validate file
                    $maxFileSize = 10 * 1024 * 1024; // 10MB
                    if ($fileSize > $maxFileSize) {
                        error_log("File $originalFileName exceeds size limit");
                        continue;
                    }
                    
                    // Check file type
                    $allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png', 'gif'];
                    $fileExtension = strtolower(pathinfo($originalFileName, PATHINFO_EXTENSION));
                    
                    if (!in_array($fileExtension, $allowedExtensions)) {
                        error_log("File type $fileExtension not allowed");
                        continue;
                    }
                    
                    // Generate unique filename
                    $uniqueFileName = uniqid('settlement_', true) . '.' . $fileExtension;
                    $filePath = $uploadDir . '/' . $uniqueFileName;
                    
                    // Move file
                    if (move_uploaded_file($fileTmpPath, $filePath)) {
                        // Determine attachment category
                        $category = ($isHRPayroll && strpos(strtolower($originalFileName), 'wps') !== false) ? 'wps_file' : 'supporting_document';
                        
                        // Save to settlement_attachments table using PDO
                        $stmtInsert = $pdo->prepare("
                            INSERT INTO settlement_attachments 
                            (settlement_id, request_inv_no, emp_id, file_name, file_path, file_type, file_size, attachment_category, uploaded_by)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                        ");
                        
                        $mimeType = mime_content_type($filePath);
                        
                        // Execute with error handling
                        try {
                            $executeResult = $stmtInsert->execute([
                                $settlementId,
                                $settlementInvNo,
                                $empId,
                                $originalFileName,
                                $uniqueFileName,
                                $mimeType,
                                $fileSize,
                                $category,
                                $currentUserId
                            ]);
                            
                            if (!$executeResult) {
                                error_log("Attachment INSERT failed: " . json_encode($stmtInsert->errorInfo()));
                                continue;
                            }
                        } catch (Exception $e) {
                            error_log("Attachment INSERT exception: " . $e->getMessage());
                            continue;
                        }
                        
                        $uploadedCount++;
                        $uploadedFiles[] = $originalFileName;
                        
                        // Log to audit table
                        $stmtAudit = $pdo->prepare("
                            INSERT INTO settlement_attachments_audit 
                            (attachment_id, settlement_id, emp_id, action, file_name, uploaded_by, ip_address)
                            VALUES (?, ?, ?, 'uploaded', ?, ?, ?)
                        ");
                        $stmtAudit->execute([
                            $pdo->lastInsertId(),
                            $settlementId,
                            $empId,
                            $originalFileName,
                            $currentUserId,
                            $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN'
                        ]);
                    }
                }
            }
        }
        
        // Commit transaction
        $pdo->commit();
        
        // Send notifications
        if ($allApprovalsComplete) {
            error_log("Settlement: All approvals complete, status: $newSettlementStatus");
        } else {
            error_log("Settlement: Notifying next approver for $settlementInvNo");
            notify_settlement_next_approver($conDB, $settlementInvNo, $typeId, $current['approval_level']);
        }
        
        // Return success response
        echo json_encode([
            'success' => true,
            'message' => 'Settlement approved successfully with ' . $uploadedCount . ' attachment(s)',
            'attachment_count' => $uploadedCount,
            'uploaded_files' => $uploadedFiles
        ]);
        
    } catch (Exception $e) {
        if (isset($pdo)) {
            $pdo->rollBack();
        }
        error_log("Settlement approval with attachments error: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Error processing settlement: ' . $e->getMessage()
        ]);
    }
}

function rejectSettlement($settlementManager, $currentUserId) {
    global $conDB;
    
    $settlementInvNo = $_POST['settlement_inv_no'] ?? '';
    $settlementId = $_POST['settlement_id'] ?? 0;
    $rejectionReason = $_POST['rejection_reason'] ?? '';
    
    if (empty($settlementInvNo) || $settlementId <= 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Settlement invoice number and ID required'
        ]);
        return;
    }
    
    if (empty($rejectionReason)) {
        echo json_encode([
            'success' => false,
            'message' => 'Rejection reason is required'
        ]);
        return;
    }
    
    try {
        // Get request type ID for settlement
        $typeQry = mysqli_query($conDB, "SELECT id FROM approval_request_types WHERE type_name = 'settlement' LIMIT 1");
        if (!$typeQry || mysqli_num_rows($typeQry) == 0) {
            echo json_encode(['success' => false, 'message' => 'Settlement request type not found']);
            return;
        }
        $typeId = (int)mysqli_fetch_assoc($typeQry)['id'];
        mysqli_free_result($typeQry);
        
        // Get current user's pending approval row.
        $currentQry = mysqli_query($conDB, "
            SELECT ra.*, r.emp_id, r.settlement_amount
            FROM request_approvers ra
            JOIN settlement_records r ON r.request_inv_no = ra.request_inv_no
            WHERE ra.request_inv_no = '$settlementInvNo' 
            AND ra.request_type_id = $typeId
            AND ra.status = 'pending'
            AND ra.approver_id = $currentUserId
            ORDER BY ra.approval_level ASC, ra.id ASC
            LIMIT 1
        ");
        
        if (!$currentQry || mysqli_num_rows($currentQry) == 0) {
            echo json_encode(['success' => false, 'message' => 'No pending approval found']);
            return;
        }
        
        $current = mysqli_fetch_assoc($currentQry);
        mysqli_free_result($currentQry);
        
        // Verify is no longer needed because query is already scoped to current approver.
        
        // Update approval status to 'rejected' for current assigned approver row only
        $updateQry = mysqli_query($conDB, "
            UPDATE request_approvers 
            SET status = 'rejected', action_date = NOW(), note = '" . mysqli_real_escape_string($conDB, $rejectionReason) . "'
            WHERE request_inv_no = '$settlementInvNo' 
            AND request_type_id = $typeId
            AND approver_id = $currentUserId
            AND status = 'pending'
        ");
        
        if (!$updateQry) {
            echo json_encode(['success' => false, 'message' => 'Failed to update rejection status']);
            return;
        }
        
        // Get rejecter details
        $rejecterQry = mysqli_query($conDB, "SELECT name, email FROM employees WHERE emp_id = $currentUserId LIMIT 1");
        $rejecterDetails = $rejecterQry ? mysqli_fetch_assoc($rejecterQry) : null;
        if ($rejecterQry) mysqli_free_result($rejecterQry);
        
        $rejecterName = $rejecterDetails['name'] ?? 'System';
        
        // Add rejection entry to smt_request_status
        mysqli_query($conDB, "
            INSERT INTO smt_request_status (inv_no, emp_id, emp_name, note, status)
            VALUES ('$settlementInvNo', $currentUserId, '" . mysqli_real_escape_string($conDB, $rejecterName) . "',
                    'Rejected at level {$current['approval_level']}. Reason: " . mysqli_real_escape_string($conDB, $rejectionReason) . "', 'rejected')
        ");
        
        // Update settlement status to 'rejected'
        $updateSettlementQry = mysqli_query($conDB, "
            UPDATE settlement_records 
            SET settlement_status = 'rejected', updated_at = NOW()
            WHERE request_inv_no = '$settlementInvNo'
        ");
        
        // Log rejection in comments
        $commentQry = mysqli_query($conDB, "
            INSERT INTO approval_comments (request_inv_no, request_type, approver_emp_id, approver_name, approval_action, comment_text, comment_date)
            VALUES ('$settlementInvNo', 'settlement', $currentUserId, '" . mysqli_real_escape_string($conDB, $rejecterName) . "', 'rejected', 
                    '" . mysqli_real_escape_string($conDB, $rejectionReason) . "', NOW())
        ");
        
        // Notify employee about rejection
        $empQry = mysqli_query($conDB, "SELECT email, name FROM employees WHERE emp_id = {$current['emp_id']} LIMIT 1");
        $emp = $empQry ? mysqli_fetch_assoc($empQry) : null;
        if ($empQry) mysqli_free_result($empQry);
        
        if ($emp) {
            // Browser notification
            create_browser_notification($conDB, $current['emp_id'],
                'Settlement Rejected',
                'Your settlement ' . $settlementInvNo . ' has been rejected.',
                'all_settlements.php?status=rejected'
            );
            
            // Email notification
            send_approval_email($conDB, $emp['email'], $emp['name'],
                'Settlement Rejected - ' . $settlementInvNo, 'settlement_rejection', [
                    'APPROVER_NAME' => $emp['name'],
                    'REQUEST_ID' => $settlementInvNo,
                    'REQUEST_TITLE' => 'Settlement Rejected',
                    'REJECTED_BY' => $rejecterName,
                    'REJECTION_REASON' => $rejectionReason,
                    'EMAIL_MESSAGE' => 'Your settlement has been rejected.',
                    'REQUEST_URL' => get_base_url() . '/all_settlements.php'
                ]
            );
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Settlement rejected successfully'
        ]);
        
    } catch (Exception $e) {
        error_log("Settlement rejection error: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ]);
    }
}

function processPayment($settlementManager, $currentUserId) {
    $settlementInvNo = $_POST['settlement_inv_no'] ?? '';
    $paymentMethod = $_POST['payment_method'] ?? 'bank_transfer';
    $paymentReference = $_POST['payment_reference'] ?? '';
    
    if (empty($settlementInvNo)) {
        echo json_encode([
            'status' => 'error', 
            'message' => 'Settlement invoice number required'
        ]);
        return;
    }
    
    // Process payment:
    // 1. Update settlement_records status to 'processed'
    // 2. Add payment entry to smt_request_status
    $result = $settlementManager->processPayment(
        $settlementInvNo,
        $paymentMethod,
        $paymentReference,
        $currentUserId
    );
    
    echo json_encode($result);
}

function getSettlementDetails($settlementManager) {
    global $conDB, $pdo;
    
    $settlementId = $_POST['settlement_id'] ?? 0;
    
    if ($settlementId <= 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Settlement ID required'
        ]);
        return;
    }
    
    try {
        // Get settlement details
        $detailQry = mysqli_query($conDB, "
            SELECT s.*, e.name as emp_name, e.email as emp_email
            FROM settlement_records s
            JOIN employees e ON e.emp_id = s.emp_id
            WHERE s.id = $settlementId
            LIMIT 1
        ");
        
        if (!$detailQry || mysqli_num_rows($detailQry) == 0) {
            echo json_encode(['success' => false, 'message' => 'Settlement not found']);
            return;
        }
        
        $settlement = mysqli_fetch_assoc($detailQry);
        $settlement['emp_name'] = getDisplayName($settlement['emp_name']);
        $settlement['settlement_method'] = getDisplayName(strtoupper(preg_replace('/_/', ' ', $settlement['settlement_method'])));
        $settlement['settlement_status'] = getDisplayName(preg_replace('/_/', ' ', $settlement['settlement_status']));

        mysqli_free_result($detailQry);
        
        // Get the related request ID based on request type
        // Extract the original request_inv_no from settlement's request_inv_no
        // Settlement format: SETL-<original_request_inv_no>
        // E.g., SETL-VAC-20260209131428-5127-509a → VAC-20260209131428-5127-509a
        $relatedRequestId = null;
        $requestType = strtolower($settlement['request_type']);
        
        // Extract original request_inv_no by removing 'SETL-' prefix
        $originalRequestInvNo = preg_replace('/^SETL-/', '', $settlement['request_inv_no']);
        
        if (strpos($requestType, 'vacation') !== false) {
            // Find the vacation ID using the extracted original request_inv_no
            $vacQry = mysqli_query($conDB, "SELECT id FROM emp_vacation WHERE request_inv_no = '{$originalRequestInvNo}' LIMIT 1");
            if ($vacQry && mysqli_num_rows($vacQry) > 0) {
                $relatedRequestId = (int)mysqli_fetch_assoc($vacQry)['id'];
            }
            if ($vacQry) mysqli_free_result($vacQry);
        } elseif (strpos($requestType, 'loan') !== false) {
            // Find the loan ID using the extracted original request_inv_no
            $loanQry = mysqli_query($conDB, "SELECT id FROM emp_loan WHERE request_inv_no = '{$originalRequestInvNo}' LIMIT 1");
            if ($loanQry && mysqli_num_rows($loanQry) > 0) {
                $relatedRequestId = (int)mysqli_fetch_assoc($loanQry)['id'];
            }
            if ($loanQry) mysqli_free_result($loanQry);
        }
        
        $settlement['related_request_id'] = $relatedRequestId;
        
        // Calculate total payable from vacation record (same calculation as vacation_report_details.php)
        $calculatedPayableAmount = 0;
        if (strpos($requestType, 'vacation') !== false && $relatedRequestId) {
            $vacQry = mysqli_query($conDB, "
                SELECT v.*, e.emp_id, e.gosi, e.country as country_id,
                       d.id as dept_id
                FROM emp_vacation v
                JOIN employees e ON v.emp_id = e.emp_id
                LEFT JOIN department d ON e.dept = d.id
                WHERE v.id = '{$relatedRequestId}'
                LIMIT 1
            ");
            
            if ($vacQry && mysqli_num_rows($vacQry) > 0) {
                $vacation = mysqli_fetch_assoc($vacQry);
                
                // Get salary details
                $salaryQry = mysqli_query($conDB, "
                    SELECT * FROM emp_salary WHERE emp_id = '{$vacation['emp_id']}'
                    ORDER BY id DESC LIMIT 1
                ");
                $salary = $salaryQry ? mysqli_fetch_assoc($salaryQry) : null;
                if ($salaryQry) mysqli_free_result($salaryQry);
                
                // Extract vacation types
                $vac_type = $vacation['vac_type'] ?? '';
                $fly_type = $vacation['fly_type'] ?? '';
                $vacation_salary_type = $vacation['vacation_salary_type'] ?? 'payroll';
                $approved_days = (float)($vacation['vacdays'] ?? 0);
                
                // Fallback for legacy/incomplete rows where vacdays is zero but date range exists.
                if (
                    $approved_days <= 0 &&
                    !empty($vacation['start_date']) &&
                    !empty($vacation['return_date']) &&
                    strtolower(trim((string)$vac_type)) !== 'encashed'
                ) {
                    try {
                        $start_date_obj = new DateTime($vacation['start_date']);
                        $return_date_obj = new DateTime($vacation['return_date']);
                        if ($return_date_obj >= $start_date_obj) {
                            $approved_days = (float)$start_date_obj->diff($return_date_obj)->days + 1;
                        }
                    } catch (Exception $e) {
                        // Keep original approved_days when parsing fails.
                    }
                }
                
                // Get payroll values
                $overtime_hours = (float)($vacation['overtime_hours'] ?? 0);
                $deduction_hours = (float)($vacation['deduction_hours'] ?? 0);
                $deduction_days = (float)($vacation['deduction_days'] ?? 0);
                $other_earnings = (float)($vacation['other_earnings'] ?? 0);
                $other_deductions = (float)($vacation['other_deductions'] ?? 0);
                $ticket_pay = (float)($vacation['ticket_pay'] ?? 0);
                $permit_fee = (float)($vacation['permit_fee'] ?? 0);
                
                // Determine vacation categories
                $is_fly_annual = ($vac_type === 'Fly' && $fly_type === 'annual');
                $is_encashment = (trim(strtolower($vac_type)) === 'encashed');
                $is_emergency = ($fly_type === 'emergency');
                $is_local_annual_removed_from_payroll = isLocalAnnualRemovedFromPayroll(
                    $vac_type,
                    $fly_type,
                    $vacation['country_id'] ?? 0,
                    $approved_days,
                    $vacation['is_deductible'] ?? 0,
                    $vacation_salary_type
                );
                $is_settlement_payable_vacation = isSettlementPayableVacation(
                    $vac_type,
                    $fly_type,
                    $vacation['country_id'] ?? 0,
                    $approved_days,
                    $vacation['is_deductible'] ?? 0,
                    $vacation_salary_type
                );
                
                $non_payable_leave_types = ['Sick Leave', 'Casual Leave', 'Maternity Leave', 'Compassionate Leave', 'Business Trip', 'Compensatory Leave'];
                $is_non_payable_leave = in_array($vac_type, $non_payable_leave_types);
                
                // Calculate only if payable
                $calculate_payments = !$is_non_payable_leave && !$is_emergency && $is_settlement_payable_vacation;
                
                if ($calculate_payments && $salary) {
                    $basic_salary = (float)($salary['basic'] ?? 0);
                    $total_monthly_salary = $basic_salary + ($salary['housing'] ?? 0) + ($salary['transport'] ?? 0) + ($salary['food'] ?? 0) + ($salary['misc'] ?? 0) + ($salary['cashier'] ?? 0) + ($salary['fuel'] ?? 0) + ($salary['tel'] ?? 0) + ($salary['other'] ?? 0) + ($salary['guard'] ?? 0);
                    
                    // Keep 30-day basis for all payroll calculations except Working Days Salary
                    $days_in_month = 30;
                    $working_days_month_days = 30;
                    if (!empty($vacation['start_date'])) {
                        $start_ts = strtotime($vacation['start_date']);
                        if ($start_ts !== false) {
                            $working_days_month_days = (int)date('t', $start_ts);
                        }
                    }
                    $daily_rate = round($total_monthly_salary / $days_in_month, 2);
                    $working_daily_rate = ($working_days_month_days > 0) ? round($total_monthly_salary / $working_days_month_days, 2) : 0;
                    
                    // Deduction calculation helpers
                    $dailyRateDeduction = round($total_monthly_salary / $days_in_month, 2);
                    $hourlyRateDeduction = round($dailyRateDeduction / 8, 2);
                    $overtimeHourlyRate = round((($basic_salary / 240) / 2) + ($total_monthly_salary / 240), 2);
                    
                    // Initialize amounts
                    $working_days_salary = 0;
                    $vacation_salary = 0;
                    $gosi_deduction = 0;
                    $overtime_amount = 0;
                    $deduction_amount = 0;
                    
                    // Calculate working days salary for vacations removed from payroll.
                    if (($is_fly_annual || $is_local_annual_removed_from_payroll) && !empty($vacation['start_date'])) {
                        $start_date_obj = new DateTime($vacation['start_date']);
                        $start_day = (int)$start_date_obj->format('d');

                        // Business rule: exclude the start day from working-days salary (working days BEFORE departure).
                        // If vacation starts on day 1, there are zero working days in the same month.
                        if ($start_day === 1) {
                            $working_days_salary = 0;
                        } else {
                            // Example: start on March 11 => 10 working days (days 1-10 before departure on 11th)
                            $working_days = $start_day - 1;
                            if ($working_days_month_days > 0 && $working_days >= $working_days_month_days) {
                                $working_days_salary = round($total_monthly_salary);
                            } else {
                                $working_days_salary = round(($total_monthly_salary / 30) * $working_days);
                            }
                        }
                    }
                    
                    // Calculate vacation salary for deductible payable vacations with payroll type.
                    if ($is_settlement_payable_vacation && $vacation_salary_type === 'payroll') {
                        $vacation_salary = round($daily_rate * $approved_days);
                    }
                    
                    // Calculate overtime
                    if ($overtime_hours > 0) {
                        $overtime_amount = round($overtimeHourlyRate * $overtime_hours);
                    }
                    
                    // Calculate deductions
                    if ($deduction_hours > 0 || $deduction_days > 0 || $other_deductions > 0) {
                        $deduction_hours_amount = round($hourlyRateDeduction * $deduction_hours);
                        $deduction_days_amount = round($dailyRateDeduction * $deduction_days);
                        $deduction_amount = round($deduction_hours_amount + $deduction_days_amount + $other_deductions);
                    }
                    
                    // Calculate GOSI
                    // Check auto_gosi_deduction flag: if 1 (enabled), apply GOSI; if 0 (disabled), skip
                    $auto_gosi_deduction = (int)($vacation['auto_gosi_deduction'] ?? 1);  // Default to 1 for backward compatibility
                    
                    if ($auto_gosi_deduction && $vacation['country_id'] == 191 && isset($vacation['gosi']) && is_numeric($vacation['gosi'])) {
                        $gosi_percentage = (float)$vacation['gosi'];
                        if (($is_fly_annual || $is_local_annual_removed_from_payroll) && $vacation_salary_type === 'payroll') {
                            // Match payroll config: GOSI is based on basic + housing salary components.
                            $gosi_base = (float)$basic_salary + (float)($vacation['housing'] ?? 0);
                            $gosi_deduction = round(($gosi_base * $gosi_percentage) / 100, 2);
                        }
                    }
                    
                    // Calculate total payable - MUST MATCH vacation_report_details.php exactly.
                    if ($is_encashment) {
                        $calculatedPayableAmount = 0; // Handled in encashment section
                    } elseif ($is_settlement_payable_vacation) {
                        $working_component = ($is_fly_annual || $is_local_annual_removed_from_payroll) ? $working_days_salary : 0;
                        $calculatedPayableAmount = round(($working_component + $vacation_salary) + $overtime_amount + $other_earnings - $deduction_amount - $gosi_deduction);
                    } else {
                        // Local Vacation + Annual or other
                        $calculatedPayableAmount = 0;
                    }
                }
            }
        }
        
        // Store calculated amount in settlement
        // Fallback to stored settlement_amount if calculated is 0
        if ($calculatedPayableAmount <= 0) {
            $calculatedPayableAmount = (float)($settlement['settlement_amount'] ?? 0);
        }
        $settlement['calculated_payable_amount'] = $calculatedPayableAmount;
        
        // Get approval chain status
        $typeQry = mysqli_query($conDB, "SELECT id FROM approval_request_types WHERE type_name = 'settlement' LIMIT 1");
        $typeId = $typeQry ? (int)mysqli_fetch_assoc($typeQry)['id'] : 0;
        if ($typeQry) mysqli_free_result($typeQry);
        
        $approvalChain = [];
        if ($typeId > 0) {
            // Deduplicate by (approval_level, approver_id): pick the LATEST row (highest id)
            // so the current cycle's status (pending/awaiting) is shown rather than an old
            // "approved" row from a previous settlement run overriding it.
            $approvalQry = mysqli_query($conDB, "
                SELECT ra.approval_level, ra.approver_id, ra.request_type_id, ra.request_inv_no,
                       ra.action_date, ra.note, ra.status,
                       e.name  AS approver_name,
                       e.email AS approver_email
                FROM request_approvers ra
                LEFT JOIN employees e ON e.emp_id = ra.approver_id
                WHERE ra.request_inv_no = '" . mysqli_real_escape_string($conDB, $settlement['request_inv_no']) . "'
                AND ra.request_type_id = $typeId
                ORDER BY ra.approval_level ASC, ra.id DESC
            ");

            if ($approvalQry) {
                $seen = [];
                while ($row = mysqli_fetch_assoc($approvalQry)) {
                    $key = $row['approval_level'] . '_' . $row['approver_id'];
                    if (!isset($seen[$key])) {
                        $seen[$key] = true;
                        $approvalChain[] = $row;
                    }
                }
                mysqli_free_result($approvalQry);
            }

            // Enforce logical consistency for display: once a "pending" level is found,
            // all subsequent levels must appear as "awaiting" — they cannot have beend
            // actioned while an earlier level is still waiting for approval.
            $pendingFound = false;
            foreach ($approvalChain as &$chainRow) {
                if ($pendingFound) {
                    $chainRow['status']      = 'awaiting';
                    $chainRow['action_date'] = null;
                } elseif ($chainRow['status'] === 'pending' || $chainRow['status'] === 'awaiting') {
                    $pendingFound = true;
                }
            }
            unset($chainRow);
        }
        
        // Get approval history
        $historyQry = mysqli_query($conDB, "
            SELECT srs.* FROM smt_request_status srs
            WHERE srs.inv_no = '{$settlement['request_inv_no']}'
            ORDER BY srs.created_at DESC
        ");
        
        $history = [];
        if ($historyQry) {
            while ($row = mysqli_fetch_assoc($historyQry)) {
                $history[] = $row;
            }
            mysqli_free_result($historyQry);
        }
        
        // Apply getDisplayName to settlement data
        if (!empty($settlement['employee_name'])) {
            $settlement['employee_name'] = getDisplayName($settlement['employee_name']);
        }
        
        // Apply getDisplayName to approvers in chain
        if (!empty($approvalChain)) {
            foreach ($approvalChain as &$approval) {
                if (!empty($approval['approver_name'])) {
                    $approval['approver_name'] = getDisplayName($approval['approver_name']);
                }
            }
        }
        
        // Apply getDisplayName to history entries
        if (!empty($history)) {
            foreach ($history as &$entry) {
                if (!empty($entry['emp_name'])) {
                    $entry['emp_name'] = getDisplayName($entry['emp_name']);
                }
            }
        }

        // Get settlement attachments
        $attachments = getSettlementAttachments($pdo, (int)$settlementId, $settlement['request_inv_no']);
        
        echo json_encode([
            'success' => true,
            'data' => [
                'settlement' => $settlement,
                'approval_chain' => $approvalChain,
                'history' => $history,
                'attachments' => $attachments
            ]
        ]);
        
    } catch (Exception $e) {
        error_log("Get settlement details error: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ]);
    }
}

function getEmployeeSettlements($settlementManager, $currentUserId) {
    $empId = $_POST['emp_id'] ?? $currentUserId;
    $status = $_POST['status'] ?? 'all'; // 'pending', 'approved', 'processed', 'rejected', 'all'
    
    // Get employee settlements:
    // - From settlement_records: All settlements for employee
    // - With approval status from request_approvers
    // - With history from smt_request_status
    $settlements = $settlementManager->getEmployeeSettlements($empId, $status);
    
    echo json_encode([
        'status' => 'success',
        'settlements' => $settlements,
        'count' => count($settlements)
    ]);
}

?>


