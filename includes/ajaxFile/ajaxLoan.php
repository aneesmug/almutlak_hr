<?php
/*******************************************************************************************************************
 * MODIFICATION SUMMARY (008-ajaxLoan.php):
 * 1. ENHANCED `add_simplified_manual_loan`: This function has been updated to process payment documentation. It now handles the file upload for the "Payment Attachment" and saves the "Receipt ID" provided in the form.
 * 2. ROBUST FILE HANDLING: The function now includes logic to securely upload the payment attachment and will automatically delete the uploaded file if the database transaction fails, preventing orphaned files on the server.
 *******************************************************************************************************************
 * MODIFICATION SUMMARY (007-ajaxLoan.php):
 * 1. ADDED `add_simplified_manual_loan`: Created a new backend function to handle the new simplified manual loan entry form.
 * 2. TRANSACTIONAL INSERT: This function uses a database transaction to ensure data integrity.
 * 3. SIMPLIFIED LOAN CREATION: It inserts a single record into `emp_loan` with all necessary approval statuses pre-set to 'approved' and 'processed' to mark it as a historical record that doesn't need to go through the live approval workflow.
 * 4. SIMPLIFIED PAYMENT CREATION: If a "Paid Amount" is entered, it creates a single corresponding payment record in `emp_loan_payments` dated the same as the loan's start date.
 * 5. STATUS DETERMINATION: The final status of the loan (`paid` or `approved`) is automatically determined based on whether the paid amount is equal to or greater than the total loan amount.
 *******************************************************************************************************************
 * MODIFICATION SUMMARY (018-ajaxLoan.php):
 * 1.  CORRECTED `finalize_loan` LOGIC: This function has been corrected to handle loan disbursement properly.
 * 2.  STORES DISBURSEMENT PROOF: It now updates the main `emp_loan` record with the `disbursement_receipt_id` and `disbursement_attachment` provided by the Finance Assistant. This serves as proof of payment *to* the employee.
 * 3.  NO LONGER CREATES REPAYMENT RECORD: The function has been fixed and **no longer** incorrectly inserts a record into the `emp_loan_payments` table during finalization. That table is only for employee repayments.
 * 4.  ACCURATE STATUS UPDATE: The loan status is correctly set to 'approved', signifying it is now an active loan ready for repayment.
 * 5.  REVISED APPROVAL CHAIN: The `approve_loan` function follows the specified workflow:
 * - Department Manager -> HR Assistant -> HR Manager -> Finance Manager -> GM -> Finance Assistant
 * 6.  UPDATED `get_loan_details`: This function now checks the logged-in user's session. It hides "Total Calculated" details only when a user with the 'employee' role is viewing their own loan application. Managers and Administrators will see the full details.
 *******************************************************************************************************************/

// require_once __DIR__ . '/../../includes/session_check.php';
// require_once __DIR__ . '/../ApprovalChainManager.php';
// include("./../../includes/helper_functions.php"); // --- Helper Function (REQUIRED for notifications) ---
include("./../../includes/validate_supervisor.php"); // --- Supervisor Validation ---

// header('Content-Type: application/json');

header('Content-Type: application/json');

try {
    require_once __DIR__ . '/../../includes/session_check.php';
    // Helper functions
    $helperFile = __DIR__ . '/../../includes/helper_functions.php';
    require_once __DIR__ . '/../ApprovalChainManager.php';
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

// Ensure session and capture current submitter id
if (session_status() === PHP_SESSION_NONE) { session_start(); }
$current_user_id = $_SESSION['empid'] ?? 0;

/**
 * Generate unique loan invoice number
 * Format: LN-YYYYMMDD-####-XXXX
 * Example: LN-20251111-5127-22fa
 */
function generate_loan_inv_no($conDB) {
    $max_attempts = 10;
    for ($attempt = 0; $attempt < $max_attempts; $attempt++) {
        // Date part: YYYYMMDD
        $date_part = date('Ymd');
        
        // Sequential part: 4-digit random number
        $seq_part = str_pad(rand(1000, 9999), 4, '0', STR_PAD_LEFT);
        
        // Random suffix: 4-character alphanumeric (lowercase)
        $chars = 'abcdefghijklmnopqrstuvwxyz0123456789';
        $suffix = '';
        for ($i = 0; $i < 4; $i++) {
            $suffix .= $chars[rand(0, strlen($chars) - 1)];
        }
        
        // Combine: LN-YYYYMMDD-####-XXXX
        $inv_no = "LN-{$date_part}-{$seq_part}-{$suffix}";
        
        // Check if already exists
        $check_stmt = $conDB->prepare("SELECT id FROM emp_loan WHERE inv_no = ? LIMIT 1");
        $check_stmt->bind_param("s", $inv_no);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        $exists = $check_result->num_rows > 0;
        $check_stmt->close();
        
        if (!$exists) {
            return $inv_no;
        }
    }
    
    // Fallback: use timestamp-based unique ID
    return "LN-" . date('Ymd') . "-" . uniqid();
}

if (isset($_POST['ajaxType'])) {
    $ajaxType = $_POST['ajaxType'];

    switch ($ajaxType) {
        case 'get_finance_staff':
            get_finance_staff();
            break;
        case 'get_loan_details':
            get_loan_details();
            break;
        case 'get_loan_details_for_modification':
            get_loan_details_for_modification();
            break;
        case 'apply_loan':
            apply_for_loan();
            break;
        case 'approve_loan':
            approve_loan();
            break;
        case 'reject_loan':
            reject_loan();
            break;
        case 'finalize_loan':
            finalize_loan();
            break;
        case 'get_loan_balance':
            get_loan_balance();
            break;
        case 'add_manual_payment':
            add_manual_payment();
            break;
        case 'modify_and_approve_loan':
            modify_and_approve_loan();
            break;
        case 'modify_and_approve_loan_hr_assistant':
            modify_and_approve_loan_hr_assistant();
            break;
        case 'check_receipt_id':
            check_receipt_id();
            break;
        case 'search_employee':
            search_employee();
            break;
        case 'add_manual_loan_history':
            add_manual_loan_history();
            break;
        case 'add_simplified_manual_loan':
            add_simplified_manual_loan();
            break;
        case 'check_loan_eligibility':
            check_loan_eligibility();
            break;
        case 'updateLoanInstallments':
            updateLoanInstallments();
            break;
        case 'updateDeductionMode':
            updateDeductionMode();
            break;
        case 'purgeAndRegenerateLoanDeductions':
            purgeAndRegenerateLoanDeductions();
            break;
        default:
            echo json_encode(['status' => 'error','title' => 'Error','message' => 'Invalid AJAX type specified.','type' => 'error']);
            break;
    }
} else {
    echo json_encode(['status' => 'error','title' => 'Error','message' => 'AJAX type not specified.','type' => 'error']);
}

function finalize_loan() {
    global $conDB;
    if (session_status() == PHP_SESSION_NONE) session_start();
    $username = $_SESSION['auth_user']['user_id'] ?? null;
    if (empty($username)) {
        echo json_encode(['status' => 'error', 'title' => 'Authentication Error', 'message' => 'User session not found.', 'type' => 'error']);
        return;
    }
    $approver_id = $username;

    if (!isset($_POST['loan_id'], $_POST['receipt_id']) || empty(trim($_POST['receipt_id'])) || !isset($_FILES['attachment']) || $_FILES['attachment']['error'] != UPLOAD_ERR_OK) {
        echo json_encode(['status' => 'error', 'title' => 'Input Error', 'message' => 'Receipt ID and attachment are required.', 'type' => 'error']);
        return;
    }

    $loan_id = filter_var($_POST['loan_id'], FILTER_VALIDATE_INT);
    $receipt_id = mysqli_real_escape_string($conDB, $_POST['receipt_id']);
    $attachment_filename = null;

    if ($loan_id === false) {
        echo json_encode(['status' => 'error', 'title' => 'Input Error', 'message' => 'Invalid Loan ID.', 'type' => 'error']);
        return;
    }

    // Handle file upload
    $upload_dir = __DIR__ . '/../../assets/loan_receipts/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    $file_extension = pathinfo($_FILES['attachment']['name'], PATHINFO_EXTENSION);
    $attachment_filename = 'disbursement_' . $loan_id . '_' . time() . '.' . $file_extension;
    $upload_file = $upload_dir . $attachment_filename;
    if (!move_uploaded_file($_FILES['attachment']['tmp_name'], $upload_file)) {
        echo json_encode(['status' => 'error', 'title' => 'Upload Error', 'message' => 'Failed to save attachment.', 'type' => 'error']);
        return;
    }

    $conDB->begin_transaction();
    try {
        $stmt_check = $conDB->prepare("SELECT id FROM emp_loan WHERE id = ? AND status = 'finance_assistant_pending' FOR UPDATE");
        $stmt_check->bind_param("i", $loan_id);
        $stmt_check->execute();
        $loan = $stmt_check->get_result()->fetch_assoc();
        $stmt_check->close();

        if (!$loan) {
            throw new Exception("This loan is not ready for final processing or does not exist.");
        }
        
        // Update the loan record with disbursement details
        $stmt_update = $conDB->prepare("UPDATE `emp_loan` SET 
            `status` = 'approved',
            `disbursement_receipt_id` = ?,
            `disbursement_attachment` = ?
            WHERE `id` = ?");
        $stmt_update->bind_param("ssi", $receipt_id, $attachment_filename, $loan_id);
        $stmt_update->execute();
        $stmt_update->close();

        // Log the finalization action
        $stmt_approval = $conDB->prepare("INSERT INTO `emp_loan_approvals` (loan_id, approver_id, approver_role, status, notes) VALUES (?, ?, ?, ?, ?)");
        $status = 'processed';
        $notes = 'Loan finalized and disbursed. Disbursement Receipt: ' . $receipt_id;
        $role = 'finance_assistant';
        $stmt_approval->bind_param("issss", $loan_id, $approver_id, $role, $status, $notes);
        $stmt_approval->execute();
        $stmt_approval->close();

        $conDB->commit();
        echo json_encode(['status' => 'success', 'title' => 'Finalized!', 'message' => 'Loan has been processed and disbursed.', 'type' => 'success']);

    } catch (Exception $e) {
        $conDB->rollback();
        // Delete uploaded file on error
        if ($attachment_filename && file_exists($upload_file)) {
            unlink($upload_file);
        }
        echo json_encode(['status' => 'error', 'title' => 'Database Error', 'message' => $e->getMessage(), 'type' => 'error']);
    }
}

function approve_loan() {
    global $conDB, $pdo;
    
    // ===== APPROVE LOAN using ApprovalChainManager =====
    try {
        if (session_status() == PHP_SESSION_NONE) session_start();
        $username = $_SESSION['auth_user']['user_id'] ?? null;
        if (empty($username)) {
            echo json_encode(['status' => 'error', 'title' => 'Authentication Error', 'message' => 'User session not found. Please log in again.', 'type' => 'error']);
            return;
        }
        
        $approver_emp_id = $_SESSION['empid'] ?? null;
        if (!$approver_emp_id) {
            $stmt_user = $conDB->prepare("SELECT emp_id FROM admin_login WHERE id_iqama = ? LIMIT 1");
            $stmt_user->bind_param("s", $username);
            $stmt_user->execute();
            $res_user = $stmt_user->get_result();
            $user_row = $res_user->fetch_assoc();
            $stmt_user->close();
            $approver_emp_id = $user_row['emp_id'] ?? null;
        }
        if (!$approver_emp_id) {
            echo json_encode(['status' => 'error', 'title' => 'Authentication Error', 'message' => 'Could not resolve approver identity.', 'type' => 'error']);
            return;
        }
        
        if (!isset($_POST['loan_id'], $_POST['approver_role'])) {
            echo json_encode(['status' => 'error', 'title' => 'Input Error', 'message' => 'Missing required approval data.', 'type' => 'error']);
            return;
        }
        
        $loan_id = filter_var($_POST['loan_id'], FILTER_VALIDATE_INT);
        $approver_role = mysqli_real_escape_string($conDB, $_POST['approver_role']);
        $approval_comment = trim($_POST['approval_comment'] ?? '');
        $approval_comment = mb_substr($approval_comment, 0, 5000);
        
        if ($loan_id === false) {
            echo json_encode(['status' => 'error', 'title' => 'Input Error', 'message' => 'Invalid Loan ID.', 'type' => 'error']);
            return;
        }

        // Get loan details and invoice number
        $stmt_inv = $conDB->prepare("SELECT inv_no, emp_id FROM emp_loan WHERE id = ? LIMIT 1");
        $stmt_inv->bind_param("i", $loan_id);
        $stmt_inv->execute();
        $inv_res = $stmt_inv->get_result();
        $inv_row = $inv_res->fetch_assoc();
        $stmt_inv->close();
        $inv_no = $inv_row['inv_no'] ?? null;
        $loan_emp_id = $inv_row['emp_id'] ?? null;
        
        if (empty($inv_no)) {
            echo json_encode(['status' => 'error', 'title' => 'Not Found', 'message' => 'Loan request not found.', 'type' => 'error']);
            return;
        }
        
        // Get loan applicant's company to verify approver has access
        $emp_stmt = $conDB->prepare("SELECT comp_no FROM employees WHERE emp_id = ? LIMIT 1");
        $emp_stmt->bind_param("s", $loan_emp_id);
        $emp_stmt->execute();
        $emp_res = $emp_stmt->get_result();
        $emp_row = $emp_res->fetch_assoc();
        $emp_stmt->close();
        $loan_company = $emp_row['comp_no'] ?? null;
        
        // Get approver's company
        $approver_stmt = $conDB->prepare("SELECT comp_no FROM employees WHERE emp_id = ? LIMIT 1");
        $approver_stmt->bind_param("s", $approver_emp_id);
        $approver_stmt->execute();
        $approver_res = $approver_stmt->get_result();
        $approver_row = $approver_res->fetch_assoc();
        $approver_stmt->close();
        $approver_company = $approver_row['comp_no'] ?? null;
        
        // Initialize ApprovalChainManager
        $chainManager = new ApprovalChainManager($conDB, $pdo, new ActivityLogger());
        
        // Verify approver is authorized
        $diagnostic = [];
        $diagnostic[] = "inv_no=$inv_no, approver_emp_id=$approver_emp_id";
        $diagnostic[] = "Loan Company: $loan_company, Approver Company: $approver_company";
        
        $verifyResult = $chainManager->verifyApprover($inv_no, $approver_emp_id);
        $diagnostic[] = "verifyApprover result: " . json_encode($verifyResult);
        
        if (!$verifyResult['authorized']) {
            // Get additional diagnostic info
            $diagnostic[] = "NOT AUTHORIZED - Fetching approval chain details";
            
            $diag_stmt = $conDB->prepare("
                SELECT id, approver_id, approval_level, status FROM request_approvers 
                WHERE request_inv_no = ? 
                ORDER BY approval_level ASC
            ");
            $diag_stmt->bind_param("s", $inv_no);
            $diag_stmt->execute();
            $diag_result = $diag_stmt->get_result();
            $approvers_list = [];
            $pending_approver = null;
            while ($row = $diag_result->fetch_assoc()) {
                $approvers_list[] = "Level {$row['approval_level']}: emp_id={$row['approver_id']}, status={$row['status']}";
                if (in_array($row['status'], ['pending', 'awaiting'])) {
                    $pending_approver = $row['approver_id'];
                }
            }
            $diag_stmt->close();
            
            if (count($approvers_list) === 0) {
                $diagnostic[] = "NO APPROVERS FOUND IN request_approvers TABLE FOR THIS INVOICE";
            } else {
                $diagnostic[] = "Approval chain: " . implode(" | ", $approvers_list);
            }
            
            $diagnostic[] = "Current user emp_id: $approver_emp_id";
            $diagnostic[] = "Next pending approver emp_id: " . ($pending_approver ?? 'NONE');
            
            // Also try to get employee name for the pending approver
            if ($pending_approver) {
                $emp_name_stmt = $conDB->prepare("SELECT name FROM employees WHERE emp_id = ? LIMIT 1");
                $emp_name_stmt->bind_param("i", $pending_approver);
                $emp_name_stmt->execute();
                $emp_name_res = $emp_name_stmt->get_result();
                $emp_row = $emp_name_res->fetch_assoc();
                $emp_name_stmt->close();
                $emp_name = $emp_row['name'] ?? 'Unknown';
                $diagnostic[] = "Pending approver name: $emp_name";
            }
            
            // Write diagnostics to file for debugging
            file_put_contents(__DIR__ . '/../../logs/loan_approval_diagnostic.log', 
                date('Y-m-d H:i:s') . " - " . implode(" | ", $diagnostic) . "\n", 
                FILE_APPEND);
            
            echo json_encode([
                'status' => 'error', 
                'title' => 'Not Allowed', 
                'message' => $verifyResult['message'], 
                'type' => 'error',
                'diagnostic' => $diagnostic // Include diagnostic info for debugging
            ]);
            return;
        }
        
        $approvalLevel = $verifyResult['level'];
        
        // Get request type ID for 'loan_request'
        $type_stmt = $conDB->prepare("SELECT id FROM approval_request_types WHERE type_name = 'loan_request' LIMIT 1");
        $type_stmt->execute();
        $type_res = $type_stmt->get_result();
        $type_row = $type_res->fetch_assoc();
        $requestTypeId = $type_row['id'] ?? 0;
        $type_stmt->close();
        
        // Check approver's user type for special handling
        $user_type_stmt = $conDB->prepare("SELECT user_type FROM admin_login WHERE emp_id = ? LIMIT 1");
        $user_type_stmt->bind_param("i", $approver_emp_id);
        $user_type_stmt->execute();
        $user_type_res = $user_type_stmt->get_result();
        $user_type_row = $user_type_res->fetch_assoc();
        $user_type_stmt->close();
        
        // Check if this is Finance Manager approval (user_type = 'finance')
        $is_finance_manager = ($user_type_row && $user_type_row['user_type'] == 'finance');
        
        // Check if current user is the assigned payer for this loan
        $is_payer = false;
        $final_approved_amount = 0;
        $loan_payer_check = $conDB->prepare("SELECT payer_emp_id FROM emp_loan WHERE id = ? LIMIT 1");
        $loan_payer_check->bind_param("i", $loan_id);
        $loan_payer_check->execute();
        $payer_result = $loan_payer_check->get_result();
        if ($payer_row = $payer_result->fetch_assoc()) {
            $is_payer = ($payer_row['payer_emp_id'] == $approver_emp_id && !empty($payer_row['payer_emp_id']));
        }
        $loan_payer_check->close();

        // If payer is approving, require payment proof and final amount
        if ($is_payer) {
            // Validate payment proof file
            if (!isset($_FILES['payment_proof']) || $_FILES['payment_proof']['error'] != UPLOAD_ERR_OK) {
                echo json_encode(['status' => 'error', 'title' => 'Input Error', 'message' => 'Payment proof file is required.', 'type' => 'error']);
                return;
            }

            // Validate final approved amount
            if (!isset($_POST['final_approved_amount']) || empty($_POST['final_approved_amount'])) {
                echo json_encode(['status' => 'error', 'title' => 'Input Error', 'message' => 'Final approved amount is required.', 'type' => 'error']);
                return;
            }

            $final_approved_amount = floatval($_POST['final_approved_amount']);
            if ($final_approved_amount <= 0) {
                echo json_encode(['status' => 'error', 'title' => 'Input Error', 'message' => 'Final approved amount must be greater than zero.', 'type' => 'error']);
                return;
            }

            // Fetch approved loan amount from DB
            $approved_amt_stmt = $conDB->prepare("SELECT loan_amount FROM emp_loan WHERE id = ? LIMIT 1");
            $approved_amt_stmt->bind_param("i", $loan_id);
            $approved_amt_stmt->execute();
            $approved_amt_res = $approved_amt_stmt->get_result();
            $approved_amt_row = $approved_amt_res->fetch_assoc();
            $approved_amt_stmt->close();
            $approved_loan_amount = isset($approved_amt_row['loan_amount']) ? floatval($approved_amt_row['loan_amount']) : 0;
            if (abs($final_approved_amount - $approved_loan_amount) > 0.009) { // allow for floating point rounding
                echo json_encode(['status' => 'error', 'title' => 'Amount Mismatch', 'message' => 'Payment amount must exactly match the approved loan amount (SAR ' . number_format($approved_loan_amount, 2) . ').', 'type' => 'error']);
                return;
            }

            // Handle payment proof file upload
            $upload_dir = __DIR__ . '/../../assets/loan_payment_proofs/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_extension = pathinfo($_FILES['payment_proof']['name'], PATHINFO_EXTENSION);
            $allowed_extensions = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx'];
            
            if (!in_array(strtolower($file_extension), $allowed_extensions)) {
                echo json_encode(['status' => 'error', 'title' => 'Invalid File', 'message' => 'Payment proof must be PDF, JPG, PNG, or DOC file.', 'type' => 'error']);
                return;
            }
            
            $payment_proof_filename = 'payment_proof_' . $inv_no . '_' . time() . '.' . $file_extension;
            $upload_file = $upload_dir . $payment_proof_filename;
            
            if (!move_uploaded_file($_FILES['payment_proof']['tmp_name'], $upload_file)) {
                echo json_encode(['status' => 'error', 'title' => 'Upload Error', 'message' => 'Failed to save payment proof file.', 'type' => 'error']);
                return;
            }
            
            // Start transaction for payer payment confirmation
            $conDB->begin_transaction();
            
            try {
                // Update emp_loan with payment proof, final amount, and payment date
                $update_loan_stmt = $conDB->prepare("UPDATE emp_loan SET payment_proof_file = ?, final_approved_amount = ?, payment_date = NOW() WHERE id = ?");
                $update_loan_stmt->bind_param("sdi", $payment_proof_filename, $final_approved_amount, $loan_id);
                if (!$update_loan_stmt->execute()) {
                    throw new Exception("Failed to update emp_loan payment proof: " . $update_loan_stmt->error);
                }
                $update_loan_stmt->close();
                
                error_log("Loan $inv_no: Payer (emp_id: $approver_emp_id) uploaded payment proof - Amount: $final_approved_amount, File: $payment_proof_filename");
                
                // ALWAYS update emp_loan status to 'approved' when payer confirms payment
                $update_status = $conDB->prepare("UPDATE emp_loan SET `status` = 'paid', `deduction_mode` = 'manual' WHERE id = ?");
                $update_status->bind_param("i", $loan_id);
                if (!$update_status->execute()) {
                    throw new Exception("Failed to update emp_loan status: " . $update_status->error);
                }
                $update_status->close();
                
                error_log("Loan $inv_no: Updated emp_loan status to 'approved' after payer payment confirmation");
                
                // ALSO update request_approvers status to 'approved' for payer record
                $update_approvers = $conDB->prepare("UPDATE request_approvers SET status = 'approved' WHERE request_inv_no = ? AND approver_id = ?");
                $update_approvers->bind_param("si", $inv_no, $approver_emp_id);
                if (!$update_approvers->execute()) {
                    throw new Exception("Failed to update request_approvers status: " . $update_approvers->error);
                }
                $update_approvers->close();
                
                error_log("Loan $inv_no: Updated request_approvers status to 'approved' for payer emp_id: $approver_emp_id");
                
                // Use ApprovalChainManager to record payer payment
                try {
                    $paymentResult = $chainManager->recordPayerPayment(
                        $inv_no,
                        $approver_emp_id,
                        $final_approved_amount,
                        $payment_proof_filename,
                        'file_upload'
                    );
                    
                    error_log("Loan $inv_no: Payment recorded via ApprovalChainManager - Result: " . json_encode($paymentResult));
                } catch (Exception $e) {
                    error_log("Loan $inv_no: Warning in recordPayerPayment: " . $e->getMessage());
                }
                
                // Commit transaction
                $conDB->commit();
                
                // Send immediate success response for Payer
                echo json_encode([
                    'status' => 'success',
                    'title' => 'Payment Processed!',
                    'message' => 'Payment recorded successfully with amount: ' . number_format($final_approved_amount, 2) . ' SAR and status updated to APPROVED',
                    'type' => 'success'
                ]);
                return;
                
            } catch (Exception $e) {
                $conDB->rollback();
                error_log("Loan $inv_no: Payer payment transaction failed: " . $e->getMessage());
                echo json_encode([
                    'status' => 'error',
                    'title' => 'Transaction Failed',
                    'message' => 'Payment processing failed: ' . $e->getMessage(),
                    'type' => 'error'
                ]);
                return;
            }
        } elseif ($is_finance_manager) {
            // Validate payer selection
            if (!isset($_POST['payer_emp_id']) || empty($_POST['payer_emp_id'])) {
                echo json_encode(['status' => 'error', 'title' => 'Input Error', 'message' => 'Please select who will process the payment.', 'type' => 'error']);
                return;
            }
            
            $payer_emp_id = (int)$_POST['payer_emp_id'];
            if ($payer_emp_id <= 0) {
                echo json_encode(['status' => 'error', 'title' => 'Input Error', 'message' => 'Invalid payer selection.', 'type' => 'error']);
                return;
            }
            
            // FIRST: Use ApprovalChainManager to handle payer selection - authorization check happens here
            // This ensures the Finance Manager is authorized BEFORE any database modifications
            try {
                $payerSelectionResult = $chainManager->approveWithPayerSelection(
                    $inv_no,
                    $approver_emp_id,
                    $payer_emp_id,
                    $approval_comment ?: 'Approved. Finance payer selected for payment processing.'
                );
                
                error_log("Loan $inv_no: Payer selection via ApprovalChainManager - Result: " . json_encode($payerSelectionResult));
            } catch (Exception $e) {
                // Log the error but return with error response - do NOT continue
                error_log("Loan $inv_no: Authorization failed in approveWithPayerSelection: " . $e->getMessage());
                echo json_encode(['status' => 'error', 'title' => 'Not Allowed', 'message' => $e->getMessage(), 'type' => 'error']);
                return;
            }
            
            // Update emp_loan with payer assignment ONLY after authorization succeeds
            $update_loan_stmt = $conDB->prepare("UPDATE emp_loan SET payer_emp_id = ? WHERE id = ?");
            $update_loan_stmt->bind_param("ii", $payer_emp_id, $loan_id);
            $update_loan_stmt->execute();
            $update_loan_stmt->close();
            
            // Log payer assignment
            error_log("Loan $inv_no: Finance Manager authorized and assigned payer: $payer_emp_id");
            
            // --- NOTIFY LOAN CREATOR ABOUT FINANCE MANAGER APPROVAL WITH PAYMENT DETAILS ---
            $stmt_creator_fm = $conDB->prepare("SELECT emp_id FROM emp_loan WHERE id = ? LIMIT 1");
            $stmt_creator_fm->bind_param("i", $loan_id);
            $stmt_creator_fm->execute();
            $creator_res_fm = $stmt_creator_fm->get_result();
            $creator_row_fm = $creator_res_fm->fetch_assoc();
            $stmt_creator_fm->close();
            
            if ($creator_row_fm && function_exists('getEmployeeDetails')) {
                $creator_emp_id_fm = $creator_row_fm['emp_id'];
                $creator_details_fm = getEmployeeDetails($conDB, $creator_emp_id_fm);
                
                // Get payer details
                $payer_details = getEmployeeDetails($conDB, $payer_emp_id);
                $payer_name = $payer_details ? $payer_details['name'] : 'Finance Staff';
                
                if ($creator_details_fm && $creator_details_fm['name'] !== 'N/A') {
                    // Send browser notification about Finance Manager approval
                    if (function_exists('create_browser_notification')) {
                        $notification_title = "Loan Payment Processing Approved";
                        $notification_message = "Finance Manager approved your loan request " . htmlspecialchars($inv_no) . ". Payment will be processed by " . $payer_name . ".";
                        $notification_url = "loan_status_history.php?inv_no=" . urlencode($inv_no);
                        create_browser_notification($conDB, $creator_emp_id_fm, $notification_title, $notification_message, $notification_url);
                    }
                    
                    // Send email with payment details
                    if (!empty($creator_details_fm['email']) && function_exists('send_approval_email')) {
                        $loan_details_fm_stmt = $conDB->prepare("SELECT el.*, e.name as employee_name FROM emp_loan el LEFT JOIN employees e ON el.emp_id = e.emp_id WHERE el.id = ? LIMIT 1");
                        $loan_details_fm_stmt->bind_param("i", $loan_id);
                        $loan_details_fm_stmt->execute();
                        $loan_details_fm = $loan_details_fm_stmt->get_result()->fetch_assoc();
                        $loan_details_fm_stmt->close();
                        
                        if ($loan_details_fm) {
                            $base_url = (function_exists('get_base_url') ? get_base_url() : 'https://hr.almutlaksystem.com');
                            $template_data = [
                                'APPROVER_NAME' => $creator_details_fm['name'],
                                'REQUEST_ID' => $inv_no,
                                'EMPLOYEE_NAME' => $loan_details_fm['employee_name'] ?? 'Employee',
                                'LOAN_TYPE' => str_replace('_', ' ', $loan_details_fm['loan_type']),
                                'LOAN_AMOUNT' => number_format($final_approved_amount, 2),
                                'INSTALLMENTS' => $loan_details_fm['installments'],
                                'REQUEST_URL' => $base_url . '/loan_status_history.php?inv_no=' . urlencode($inv_no),
                                'EMAIL_MESSAGE' => 'Great news! Finance Manager has approved your loan and assigned payment processing to ' . $payer_name . '. The payer will handle the payment and upload proof shortly.'
                            ];
                            
                            $email_subject = "Finance Manager Approved Payment - " . ucfirst(str_replace('_', ' ', $loan_details_fm['loan_type'])) . " (" . $inv_no . ")";
                            send_approval_email($conDB, $creator_details_fm['email'], $creator_details_fm['name'], $email_subject, 'loan_request', $template_data);
                        }
                    }
                }
            }
            
            // --- NOTIFY SELECTED PAYER ABOUT ASSIGNMENT ---
            if ($payer_emp_id && function_exists('getEmployeeDetails')) {
                $payer_details_notify = getEmployeeDetails($conDB, $payer_emp_id);
                if ($payer_details_notify && $payer_details_notify['name'] !== 'N/A') {
                    // Send browser notification to payer
                    if (function_exists('create_browser_notification')) {
                        $notification_title = "Loan Payment Assignment";
                        $notification_message = "You have been assigned to process payment for loan " . htmlspecialchars($inv_no) . ". Please process payment and upload proof.";
                        $notification_url = "all_applied_loan.php?status=my_pending";
                        create_browser_notification($conDB, $payer_emp_id, $notification_title, $notification_message, $notification_url);
                    }
                    
                    // Send email to payer
                    if (!empty($payer_details_notify['email']) && function_exists('send_approval_email')) {
                        $loan_details_payer_stmt = $conDB->prepare("SELECT el.*, e.name as employee_name FROM emp_loan el LEFT JOIN employees e ON el.emp_id = e.emp_id WHERE el.id = ? LIMIT 1");
                        $loan_details_payer_stmt->bind_param("i", $loan_id);
                        $loan_details_payer_stmt->execute();
                        $loan_details_payer = $loan_details_payer_stmt->get_result()->fetch_assoc();
                        $loan_details_payer_stmt->close();
                        
                        if ($loan_details_payer) {
                            $base_url = (function_exists('get_base_url') ? get_base_url() : 'https://hr.almutlaksystem.com');
                            $template_data = [
                                'APPROVER_NAME' => $payer_details_notify['name'],
                                'REQUEST_ID' => $inv_no,
                                'EMPLOYEE_NAME' => $loan_details_payer['employee_name'] ?? 'Employee',
                                'LOAN_TYPE' => str_replace('_', ' ', $loan_details_payer['loan_type']),
                                'LOAN_AMOUNT' => number_format($loan_details_payer['loan_amount'], 2),
                                'INSTALLMENTS' => $loan_details_payer['installments'],
                                'REQUEST_URL' => $base_url . '/all_applied_loan.php?status=my_pending',
                                'EMAIL_MESSAGE' => 'You have been assigned by Finance Manager to process the payment for this loan. Please process the payment, confirm the amount, and upload payment proof to complete the transaction.'
                            ];
                            
                            $email_subject = "Payment Assignment - " . ucfirst(str_replace('_', ' ', $loan_details_payer['loan_type'])) . " (" . $inv_no . ")";
                            send_approval_email($conDB, $payer_details_notify['email'], $payer_details_notify['name'], $email_subject, 'loan_request', $template_data);
                        }
                    }
                }
            }
            // --- END FINANCE MANAGER NOTIFICATIONS ---
            
            // Send immediate success response for Finance Manager
            echo json_encode([
                'status' => 'success',
                'title' => 'Payer Assigned!',
                'message' => 'Loan approved and payer assigned successfully. Awaiting payment processing.',
                'type' => 'success'
            ]);
            return;
        }
        
        // ===== PROCESS APPROVAL using ApprovalChainManager =====
        // Skip processApproval for Finance Managers (already handled by approveWithPayerSelection)
        // and for Payers (already handled by recordPayerPayment)
        if (!$is_finance_manager && !$is_payer) {
            $approvalResult = $chainManager->processApproval($inv_no, $approver_emp_id, 'approve', $approval_comment);
            
            if (!$approvalResult['success']) {
                echo json_encode(['status' => 'error', 'title' => 'Approval Error', 'message' => $approvalResult['message'], 'type' => 'error']);
                return;
            }
            
            $isFinalApproval = $approvalResult['is_final'];
            $nextApprover = $approvalResult['next_approver'] ?? null;
        } else {
            // For Finance Managers and Payers, set default values
            $isFinalApproval = false;
            $nextApprover = null;
        }
        
        // Save approval comment if provided
        if (!empty($approval_comment)) {
            $sql_approver = "SELECT name FROM employees WHERE emp_id = ?";
            $stmt_approver = $conDB->prepare($sql_approver);
            if ($stmt_approver) {
                $stmt_approver->bind_param("i", $approver_emp_id);
                $stmt_approver->execute();
                $result_approver = $stmt_approver->get_result();
                $approver_data = $result_approver->fetch_assoc();
                $approver_name = $approver_data['name'] ?? 'Unknown';
                $stmt_approver->close();
            } else {
                $approver_name = 'Unknown';
            }
            
            if (function_exists('save_approval_comment_db')) {
                save_approval_comment_db(
                    $conDB,
                    $inv_no,
                    'loan',
                    'approved',
                    $approver_emp_id,
                    $approver_name,
                    $approval_comment,
                    $approvalLevel,
                    null
                );
            }
        }
        
        // Log loan approval
        ActivityLogger::logApproval('Loan', 'ajaxLoan.php', $loan_id, 'approved', "Approved loan: {$inv_no}, Level: {$approvalLevel}, Role: {$approver_role}", 'emp_loan');
        
        // Add status history to smt_request_status
        $status_label = 'approved_level_' . $approvalLevel;
        $note = 'Approved by approver at level ' . $approvalLevel;
        $hist_stmt = $conDB->prepare("INSERT INTO smt_request_status (inv_no, emp_id, emp_name, note, status) VALUES (?, ?, 'System', ?, ?)");
        $hist_stmt->bind_param("siss", $inv_no, $approver_emp_id, $note, $status_label);
        $hist_stmt->execute();
        $hist_stmt->close();
        
        if (!$isFinalApproval && $nextApprover) {
            // Update main loan status to reflect next stage
            $stmt = $conDB->prepare("UPDATE emp_loan SET status = 'pending' WHERE id = ?");
            $stmt->bind_param("i", $loan_id);
            $stmt->execute();
            $stmt->close();
            
            // Add status history for moving to next level
            $next_approval_level = $nextApprover['approval_level'] ?? $nextApprover['level'] ?? 'next';
            $next_status_label = 'pending_level_' . $next_approval_level;
            $note_next = 'Moved to approval level ' . $next_approval_level;
            $hist_next = $conDB->prepare("INSERT INTO smt_request_status (inv_no, emp_id, emp_name, note, status) VALUES (?, ?, 'System', ?, ?)");
            $hist_next->bind_param("siss", $inv_no, $approver_emp_id, $note_next, $next_status_label);
            $hist_next->execute();
            $hist_next->close();
            
            // ===== NOTIFY NEXT APPROVER using ApprovalChainManager =====
            // Fetch loan details for email template
            $loan_details_stmt = $conDB->prepare("SELECT el.*, e.name as employee_name FROM emp_loan el LEFT JOIN employees e ON el.emp_id = e.emp_id WHERE el.inv_no = ? LIMIT 1");
            $loan_details_stmt->bind_param("s", $inv_no);
            $loan_details_stmt->execute();
            $loan_details = $loan_details_stmt->get_result()->fetch_assoc();
            $loan_details_stmt->close();
            
            if ($loan_details) {
                $nextApproverName = $nextApprover['fullname'] ?? $nextApprover['name'] ?? 'Next Approver';
                $nextApproverEmail = $nextApprover['email'] ?? null;
                $nextApproverId = $nextApprover['approver_id'] ?? $nextApprover['emp_id'] ?? null;
                
                if ($nextApproverEmail && $nextApproverId) {
                    $emailData = [
                        'APPROVER_NAME' => $nextApproverName,
                        'REQUEST_ID' => $inv_no,
                        'EMPLOYEE_NAME' => $loan_details['employee_name'] ?? 'Employee',
                        'LOAN_TYPE' => str_replace('_', ' ', $loan_details['loan_type']),
                        'LOAN_AMOUNT' => number_format($loan_details['loan_amount'], 2),
                        'INSTALLMENTS' => $loan_details['installments'],
                        'REQUEST_URL' => 'https://hr.almutlaksystem.com/all_applied_loan.php?status=my_pending',
                        'EMAIL_MESSAGE' => 'A loan request has been approved at a previous level and now requires your approval.'
                    ];
                    
                    send_approval_email(
                        $conDB,
                        $nextApproverEmail,
                        $nextApproverName,
                        "Loan Request Pending Your Approval - " . ucfirst(str_replace('_', ' ', $loan_details['loan_type'])) . " (" . $inv_no . ")",
                        'loan_request',
                        $emailData
                    );
                    
                    create_browser_notification(
                        $conDB,
                        $nextApproverId,
                        'Loan Request for Approval',
                        "Loan request " . htmlspecialchars($inv_no) . " is now pending your approval.",
                        'all_applied_loan.php?status=my_pending'
                    );
                }
            }
            
            echo json_encode(['status' => 'success', 'title' => 'Approved!', 'message' => 'Moved to next approval stage.', 'type' => 'success']);
        } else {
            // No next approver, finalize loan and trigger payroll integration
            // Always set status to 'approved' (not just in status history)
            $stmt = $conDB->prepare("UPDATE emp_loan SET status = 'approved' WHERE id = ?");
            $stmt->bind_param("i", $loan_id);
            $stmt->execute();
            $stmt->close();
            
            // Add final approval status history
            $final_status = 'fully_approved';
            $note_final = 'Loan fully approved - all levels completed';
            $hist_final = $conDB->prepare("INSERT INTO smt_request_status (inv_no, emp_id, emp_name, note, status) VALUES (?, ?, 'System', ?, ?)");
            $hist_final->bind_param("siss", $inv_no, $approver_emp_id, $note_final, $final_status);
            $hist_final->execute();
            $hist_final->close();
            
            // --- NOTIFY LOAN CREATOR THAT REQUEST IS FULLY APPROVED ---
            $stmt_creator = $conDB->prepare("SELECT emp_id FROM emp_loan WHERE id = ? LIMIT 1");
            $stmt_creator->bind_param("i", $loan_id);
            $stmt_creator->execute();
            $creator_res = $stmt_creator->get_result();
            $creator_row = $creator_res->fetch_assoc();
            $stmt_creator->close();
            
            if ($creator_row && function_exists('getEmployeeDetails')) {
                $creator_emp_id = $creator_row['emp_id'];
                $creator_details = getEmployeeDetails($conDB, $creator_emp_id);
                if ($creator_details && $creator_details['name'] !== 'N/A') {
                    // Send browser notification
                    if (function_exists('create_browser_notification')) {
                        $notification_title = "Loan Request Approved";
                        $notification_message = "Your loan request " . htmlspecialchars($inv_no) . " has been fully approved!";
                        $notification_url = "loan_status_history.php?inv_no=" . urlencode($inv_no);
                        create_browser_notification($conDB, $creator_emp_id, $notification_title, $notification_message, $notification_url);
                    }
                    
                    // Send email with template
                    if (!empty($creator_details['email']) && function_exists('send_approval_email')) {
                        // Fetch loan details for email template
                        $loan_details_stmt = $conDB->prepare("SELECT el.*, e.name as employee_name FROM emp_loan el LEFT JOIN employees e ON el.emp_id = e.emp_id WHERE el.inv_no = ? LIMIT 1");
                        $loan_details_stmt->bind_param("s", $inv_no);
                        $loan_details_stmt->execute();
                        $loan_details = $loan_details_stmt->get_result()->fetch_assoc();
                        $loan_details_stmt->close();
                        
                        if ($loan_details) {
                            $base_url = (function_exists('get_base_url') ? get_base_url() : 'https://hr.almutlaksystem.com');
                            $template_data = [
                                'APPROVER_NAME' => $creator_details['name'],
                                'REQUEST_ID' => $inv_no,
                                'EMPLOYEE_NAME' => $loan_details['employee_name'] ?? 'Employee',
                                'LOAN_TYPE' => str_replace('_', ' ', $loan_details['loan_type']),
                                'LOAN_AMOUNT' => number_format($loan_details['loan_amount'], 2),
                                'INSTALLMENTS' => $loan_details['installments'],
                                'REQUEST_URL' => $base_url . '/loan_status_history.php?inv_no=' . urlencode($inv_no),
                                'EMAIL_MESSAGE' => 'Great news! Your loan request has been fully approved and will be processed.'
                            ];
                            
                            $email_subject = "Loan Request Fully Approved - " . ucfirst(str_replace('_', ' ', $loan_details['loan_type'])) . " (" . $inv_no . ")";
                            send_approval_email($conDB, $creator_details['email'], $creator_details['name'], $email_subject, 'loan_request', $template_data);
                        }
                    }
                }
            }
            // --- END CREATOR NOTIFICATION ---

            // Trigger automated payroll integration for all approved loans
            if (function_exists('integrate_loan_to_payroll')) {
                try {
                    $payroll_result = integrate_loan_to_payroll($loan_id, $conDB);
                    if ($payroll_result['success']) {
                        error_log("Payroll integration successful for loan {$loan_id}: " . $payroll_result['message']);
                    } else {
                        // Log warning but don't fail the approval
                        error_log("Payroll integration warning for loan {$loan_id}: " . $payroll_result['message']);
                    }
                } catch (Exception $e) {
                    error_log("Payroll integration exception for loan {$loan_id}: " . $e->getMessage());
                }
            }

            // --- ADD MONTHLY INSTALLMENT DEDUCTION IMMEDIATELY AFTER GM APPROVAL ---
            // Only add if not already present (idempotent)
            $loan_stmt = $conDB->prepare("SELECT emp_id, inv_no, loan_amount, installments, start_date FROM emp_loan WHERE id = ? LIMIT 1");
            $loan_stmt->bind_param("i", $loan_id);
            $loan_stmt->execute();
            $loan_result = $loan_stmt->get_result();
            $loan = $loan_result->fetch_assoc();
            $loan_stmt->close();
            if ($loan) {
                $emp_id = $loan['emp_id'];
                $inv_no = $loan['inv_no'];
                $loan_amount = floatval($loan['loan_amount']);
                $installments = intval($loan['installments']);
                $start_date = $loan['start_date'];
                $deduction_label = 'Loan Installment';
                if ($installments > 0) {
                    $monthly_amount = round($loan_amount / $installments, 2);
                    add_monthly_installment_deduction($conDB, $emp_id, $inv_no, $monthly_amount, $installments, new DateTime($start_date), $deduction_label);
                }
            }

            echo json_encode(['status' => 'success', 'title' => 'Final Approved!', 'message' => 'Loan fully approved and added to payroll.', 'type' => 'success']);
        }
    } catch (Exception $e) {
        error_log("Loan approval error: " . $e->getMessage());
        echo json_encode(['status' => 'error', 'title' => 'System Error', 'message' => 'An error occurred during approval: ' . $e->getMessage(), 'type' => 'error']);
    }
}

// --- Other functions remain unchanged ---

/**
 * Get Finance Staff for Payer Selection
 * Returns list of employees with finance roles who can process payments
 */
function get_finance_staff() {
    global $conDB;
    
    try {
        // Get all staff from finance department (dept = 2) with user_type = 'finance_officer'
        // Join with employees table to get accurate employee details
        $query = "SELECT al.emp_id, e.name, al.user_type 
                  FROM admin_login al
                  INNER JOIN employees e ON al.emp_id = e.emp_id
                  WHERE al.dept = 2
                  AND al.user_type = 'finance_officer'
                  AND al.status = 1
                  AND e.status = 1
                  ORDER BY e.name ASC";
        
        $result = mysqli_query($conDB, $query);
        
        if (!$result) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Database error: ' . mysqli_error($conDB)
            ]);
            return;
        }
        
        $staff = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $staff[] = [
                'emp_id' => $row['emp_id'],
                'name' => $row['name'],
                'user_type' => $row['user_type']
            ];
        }
        
        echo json_encode([
            'status' => 'success',
            'staff' => $staff
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Failed to retrieve finance staff: ' . $e->getMessage()
        ]);
    }
}

function get_loan_details() {
    global $conDB;
    // Start session to access logged-in user's data
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }

    if (!isset($_POST['emp_id'])) {
        echo json_encode(['status' => 'error', 'message' => 'Employee ID not provided.']);
        return;
    }
    $emp_id_for_loan = mysqli_real_escape_string($conDB, $_POST['emp_id']);

    // Get logged-in user's unique ID from session to ensure data is accurate
    $logged_in_user_id_iqama = $_SESSION['auth_user']['user_id'] ?? null;
    if (!$logged_in_user_id_iqama) {
        echo json_encode(['status' => 'error', 'message' => 'Authentication error. Session not found.']);
        return;
    }

    // Fetch the logged-in user's details directly from the database
    $stmt_user = $conDB->prepare("SELECT * FROM admin_login WHERE id_iqama = ?");
    $stmt_user->bind_param("i", $logged_in_user_id_iqama);
    $stmt_user->execute();
    $user_row = $stmt_user->get_result()->fetch_assoc();
    $stmt_user->close();

    if (!$user_row) {
        echo json_encode(['status' => 'error', 'message' => 'Could not verify the logged-in user.']);
        return;
    }

    $logged_in_user_type = $user_row['user_type'];
    $logged_in_emp_id = $user_row['emp_id'];

    // Determine if the full details should be shown.
    // Default to showing details. Hide only if a user with role 'employee' is viewing their own loan.
    $show_full_details = true;
    if ($logged_in_user_type === 'employee' && $logged_in_emp_id === $emp_id_for_loan) {
        $show_full_details = false;
    }
    
    // This part of the function remains the same
    $query = "SELECT 
                e.joining_date, 
                s.basic, s.housing, s.transport, s.food, s.misc, s.cashier, s.fuel, s.tel, s.other, s.guard,
                (s.basic + s.housing + s.transport + s.food + s.misc + s.cashier + s.fuel + s.tel + s.other + s.guard) as total_salary
              FROM employees e 
              JOIN emp_salary s ON e.emp_id = s.emp_id 
              WHERE e.emp_id = '$emp_id_for_loan'";
              
    $result = mysqli_query($conDB, $query);
    if ($row = mysqli_fetch_assoc($result)) {
        $endOfServiceBenefit = calculateEndOfService($row['joining_date'], $row['total_salary']);
        $total_salary = $row['total_salary'];
        $housing_allowance = $row['housing'];
        
        echo json_encode([
            'status' => 'success',
            'end_of_service' => round($endOfServiceBenefit, 2),
            'total_salary' => round($total_salary, 2),
            'housing_allowance' => round($housing_allowance, 2),
            // Backward-compatible field (retain key) now reflects dynamic 40% EOS cap
            'max_loan_amount' => round($endOfServiceBenefit * 0.40, 2),
            'max_advance_salary' => round($total_salary * 0.5, 2),
            'max_housing_loan' => round(min($housing_allowance * 6, 20000, $endOfServiceBenefit), 2),
            'max_eos_loan' => round($endOfServiceBenefit * 0.40, 2),
            'has_housing' => ($housing_allowance > 0),
            'show_full_details' => $show_full_details
        ]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Could not find employee details.']);
    }
}

function get_loan_details_for_modification() {
    global $conDB;
    
    if (!isset($_POST['loan_id'])) {
        echo json_encode(['status' => 'error', 'message' => 'Loan ID not provided.']);
        return;
    }
    
    $loan_id = filter_var($_POST['loan_id'], FILTER_VALIDATE_INT);
    if ($loan_id === false) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid Loan ID.']);
        return;
    }
    
    $stmt = $conDB->prepare("SELECT emp_id, loan_amount, installments, loan_type FROM emp_loan WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $loan_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $loan = $result->fetch_assoc();
    $stmt->close();
    
    if (!$loan) {
        echo json_encode(['status' => 'error', 'message' => 'Loan not found.']);
        return;
    }
    
    echo json_encode([
        'status' => 'success',
        'emp_id' => $loan['emp_id'],
        'loan_amount' => $loan['loan_amount'],
        'installments' => $loan['installments'],
        'loan_type' => $loan['loan_type']
    ]);
}

function apply_for_loan() {
    global $conDB, $pdo;
    // Check for required fields
    if (!isset($_POST['emp_id'], $_POST['loan_amount'], $_POST['loan_type'])) {
        echo json_encode(['status' => 'error','title' => 'Input Error','message' => 'Missing required fields.','type' => 'error']);
        return;
    }

    // Sanitize and validate inputs
    $emp_id = mysqli_real_escape_string($conDB, $_POST['emp_id']);
    
    // CHECK IF EMPLOYEE HAS PENDING OR AWAITING LOAN REQUESTS
    $pending_check = $conDB->prepare("SELECT id, inv_no, loan_type, loan_amount, status, created_at FROM emp_loan WHERE emp_id = ? AND status IN ('pending', 'awaiting')");
    $pending_check->bind_param("s", $emp_id);
    $pending_check->execute();
    $pending_result = $pending_check->get_result();
    
    if ($pending_result->num_rows > 0) {
        $pending_loan = $pending_result->fetch_assoc();
        $pending_check->close();
        
        // Get approval chain for this pending loan
        $approval_chain_query = $conDB->prepare("
            SELECT ra.approval_level, ra.status, 
                   COALESCE(e.name, al.fullname, al.username) as approver_name,
                   ra.action_date
            FROM request_approvers ra
            LEFT JOIN employees e ON ra.approver_id = e.emp_id
            LEFT JOIN admin_login al ON ra.approver_id = al.id_iqama
            WHERE ra.request_inv_no = ? AND ra.request_type_id = 2
            ORDER BY ra.approval_level ASC
        ");
        $approval_chain_query->bind_param("s", $pending_loan['inv_no']);
        $approval_chain_query->execute();
        $chain_result = $approval_chain_query->get_result();
        
        $approval_chain_html = '';
        $pending_at_level = null;
        
        while ($chain_row = $chain_result->fetch_assoc()) {
            $status = strtolower($chain_row['status']);
            $icon = '';
            $badge_class = '';
            
            if ($status === 'approved') {
                $icon = '✓';
                $badge_class = 'badge-success';
            } elseif ($status === 'rejected') {
                $icon = '✗';
                $badge_class = 'badge-danger';
            } else {
                $icon = '●';
                $badge_class = 'badge-warning';
                if (!$pending_at_level) {
                    $pending_at_level = $chain_row['approval_level'];
                }
            }
            
            $approval_chain_html .= '<div style="display:flex; align-items:center; padding:8px 0; border-bottom:1px solid #eee;">
                <span class="badge ' . $badge_class . '" style="min-width:30px; margin-right:10px;">' . $icon . '</span>
                <span style="flex:1;">Level ' . $chain_row['approval_level'] . ': ' . htmlspecialchars($chain_row['approver_name']) . ' — ' . ucfirst($status) . '</span>
            </div>';
        }
        $approval_chain_query->close();
        
        $pending_at_name = 'Processing';
        if ($pending_at_level) {
            $pending_at_query = $conDB->prepare("
                SELECT COALESCE(e.name, al.fullname, al.username) as approver_name
                FROM request_approvers ra
                LEFT JOIN employees e ON ra.approver_id = e.emp_id
                LEFT JOIN admin_login al ON ra.approver_id = al.id_iqama
                WHERE ra.request_inv_no = ? AND ra.request_type_id = 2 AND ra.approval_level = ?
            ");
            $pending_at_query->bind_param("si", $pending_loan['inv_no'], $pending_at_level);
            $pending_at_query->execute();
            $pending_at_result = $pending_at_query->get_result();
            if ($row = $pending_at_result->fetch_assoc()) {
                $pending_at_name = $row['approver_name'];
            }
            $pending_at_query->close();
        }
        
        echo json_encode([
            'status' => 'error',
            'title' => 'Cannot apply now',
            'message' => 'You already have a ' . strtoupper($pending_loan['loan_type']) . ' loan request pending approval.',
            'type' => 'pending_request',
            'pending_loan' => [
                'inv_no' => $pending_loan['inv_no'],
                'loan_type' => $pending_loan['loan_type'],
                'loan_amount' => $pending_loan['loan_amount'],
                'status' => $pending_loan['status'],
                'created_at' => $pending_loan['created_at'],
                'pending_at_name' => $pending_at_name,
                'approval_chain' => $approval_chain_html
            ]
        ]);
        return;
    }
    $pending_check->close();
    
    // Validate supervisor assignment FIRST
    $supervisor_check = validate_employee_supervisor($conDB, $emp_id);
    if (!$supervisor_check['valid']) {
        send_supervisor_validation_error($supervisor_check['message']);
    }
    
    $loan_amount = filter_var($_POST['loan_amount'], FILTER_VALIDATE_FLOAT);
    $loan_type = mysqli_real_escape_string($conDB, $_POST['loan_type']); // end_of_service, housing, advance_salary

    // Start date is optional; default to first day of next month (next payroll cycle)
    $start_date = null;
    if (isset($_POST['start_date']) && $_POST['start_date']) {
        $start_date_str = $_POST['start_date'];
        $start_date = DateTime::createFromFormat('Y-m-d', $start_date_str);
        if (!$start_date || $start_date->format('Y-m-d') !== $start_date_str) {
            echo json_encode(['status' => 'error', 'title' => 'Invalid Date', 'message' => 'Please provide a valid start date in YYYY-MM-DD format.', 'type' => 'error']);
            return;
        }
    } else {
        $start_date = new DateTime('first day of next month');
    }

    if ($loan_amount === false || $loan_amount <= 0) {
        echo json_encode(['status' => 'error', 'title' => 'Invalid Input', 'message' => 'Please provide a valid loan amount.', 'type' => 'error']);
        return;
    }

    // Get employee salary details
    $query = "SELECT e.joining_date, s.basic, s.housing, s.transport, s.food, s.misc, s.cashier, s.fuel, s.tel, s.other, s.guard,
              (s.basic + s.housing + s.transport + s.food + s.misc + s.cashier + s.fuel + s.tel + s.other + s.guard) as total_salary
              FROM employees e 
              JOIN emp_salary s ON e.emp_id = s.emp_id 
              WHERE e.emp_id = '$emp_id'";
    $result = mysqli_query($conDB, $query);
    
    if (!$row = mysqli_fetch_assoc($result)) {
        echo json_encode(['status' => 'error', 'title' => 'Validation Error', 'message' => 'Cannot verify employee details.', 'type' => 'error']);
        return;
    }

    $total_salary = $row['total_salary'];
    $housing_allowance = $row['housing'];
    $joining_date = $row['joining_date'];
    $installments = 1; // Default
    $monthly_deduction = $loan_amount;

    // Validate based on loan type
    if ($loan_type === 'end_of_service') {
        // Revised End of Service Loan Rules:
        // - Maximum 40% of calculated End of Service benefit
        // - No fixed 20k cap (business requested EOS * 40%)
        // - Installments up to 12 months
        // - Optional: minimum amount removed (can set if needed)

        $endOfServiceBenefit = calculateEndOfService($joining_date, $total_salary);
        $maxAllowedEOS = $endOfServiceBenefit * 0.40; // 40%

        if ($loan_amount > $maxAllowedEOS) {
            echo json_encode([
                'status' => 'error',
                'title' => 'Amount Exceeded',
                'message' => 'Maximum allowed is 40% of your End of Service benefit: SAR ' . round($maxAllowedEOS, 2),
                'type' => 'error'
            ]);
            return;
        }

        // Get installments (must be provided and <= 12)
        if (!isset($_POST['installments'])) {
            echo json_encode(['status' => 'error', 'title' => 'Input Error', 'message' => 'Number of installments is required for End of Service loan.', 'type' => 'error']);
            return;
        }
        
        $installments = filter_var($_POST['installments'], FILTER_VALIDATE_INT);
        if ($installments === false || $installments <= 0 || $installments > 12) {
            echo json_encode(['status' => 'error', 'title' => 'Invalid Installments', 'message' => 'Installments must be between 1 and 12 months.', 'type' => 'error']);
            return;
        }

        $monthly_deduction = $loan_amount / $installments;

    } elseif ($loan_type === 'housing') {
        // Housing Loan Rules:
        // - Employee must have housing allowance
        // - Maximum 6 months housing in advance
        // - Max 20k
        // - Must not exceed EOS benefit
        // - Cannot apply if has housing loan within last year (debit free)
        
        if ($housing_allowance <= 0) {
            echo json_encode(['status' => 'error', 'title' => 'Not Eligible', 'message' => 'You do not have housing allowance. Housing loan is not available.', 'type' => 'error']);
            return;
        }

        // Check if employee has housing loan in last year that's not fully paid
        $check_last_loan = $conDB->prepare("SELECT id, start_date, status FROM emp_loan WHERE emp_id = ? AND loan_type = 'housing' ORDER BY start_date DESC LIMIT 1");
        $check_last_loan->bind_param("s", $emp_id);
        $check_last_loan->execute();
        $last_loan = $check_last_loan->get_result()->fetch_assoc();
        $check_last_loan->close();

        if ($last_loan) {
            $last_loan_date = new DateTime($last_loan['start_date']);
            $one_year_ago = (new DateTime())->modify('-1 year');
            
            if ($last_loan_date > $one_year_ago && $last_loan['status'] !== 'paid') {
                echo json_encode(['status' => 'error', 'title' => 'Not Eligible', 'message' => 'You must wait 1 year from your last housing loan and it must be fully paid before applying again.', 'type' => 'error']);
                return;
            }
        }

        $max_housing_loan = $housing_allowance * 6; // 6 months advance
        
        if ($loan_amount > $max_housing_loan) {
            echo json_encode(['status' => 'error', 'title' => 'Amount Exceeded', 'message' => 'Maximum housing loan is 6 months of your housing allowance: SAR ' . round($max_housing_loan, 2), 'type' => 'error']);
            return;
        }

        if ($loan_amount > 20000) {
            echo json_encode(['status' => 'error', 'title' => 'Amount Exceeded', 'message' => 'Maximum housing loan amount is SAR 20,000.', 'type' => 'error']);
            return;
        }

        $endOfServiceBenefit = calculateEndOfService($joining_date, $total_salary);
        if ($loan_amount > $endOfServiceBenefit) {
            echo json_encode(['status' => 'error', 'title' => 'Exceeds EOS Benefit', 'message' => 'Loan amount cannot exceed your End of Service benefit of SAR ' . round($endOfServiceBenefit, 2), 'type' => 'error']);
            return;
        }

        // Calculate installments based on housing allowance
        $installments = ceil($loan_amount / $housing_allowance);
        if ($installments > 6) $installments = 6;
        $monthly_deduction = $housing_allowance; // Deduct full housing each month

    } elseif ($loan_type === 'advance_salary') {
        // Advance Salary Rules:
        // - Maximum 50% of monthly salary
        // - Deducted in full in next payroll (1 installment)
        
        $max_advance = $total_salary * 0.5;
        
        if ($loan_amount > $max_advance) {
            echo json_encode(['status' => 'error', 'title' => 'Amount Exceeded', 'message' => 'Maximum advance salary is 50% of your monthly salary: SAR ' . round($max_advance, 2), 'type' => 'error']);
            return;
        }

        $installments = 1; // Always 1 for advance salary
        $monthly_deduction = $loan_amount; // Full amount deducted at once

    } else {
        echo json_encode(['status' => 'error', 'title' => 'Invalid Type', 'message' => 'Invalid loan type specified.', 'type' => 'error']);
        return;
    }

    // Calculate loan details
    $interest_rate = 0.00;
    $total_payable = $loan_amount;
    $end_date = clone $start_date;
    $end_date->modify('+' . ($installments - 1) . ' months');
    $end_date_str = $end_date->format('Y-m-d');
    $start_date_str_db = $start_date->format('Y-m-d');

    // Generate unique invoice number
    $inv_no = generate_loan_inv_no($conDB);

    // Insert into emp_loan with inv_no and installments (log submitter)
    $stmt = $conDB->prepare("INSERT INTO `emp_loan` (`inv_no`, `emp_id`, `submitted_by_emp_id`, `loan_type`, `loan_amount`, `installments`, `interest_rate`, `total_payable`, `monthly_deduction`, `start_date`, `end_date`, `status`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
    if ($stmt === false) {
        echo json_encode(['status' => 'error', 'title' => 'Database Error', 'message' => 'Failed to prepare the SQL statement: ' . $conDB->error, 'type' => 'error']);
        return;
    }
    $submitted_by = isset($_SESSION['empid']) ? (int)$_SESSION['empid'] : null;
    // Corrected type string: 11 placeholders => ssisdidddss (s:string, i:int, d:double)
    $stmt->bind_param("ssisdidddss", $inv_no, $emp_id, $submitted_by, $loan_type, $loan_amount, $installments, $interest_rate, $total_payable, $monthly_deduction, $start_date_str_db, $end_date_str);
    if ($stmt->execute()) {
        $loan_id = $stmt->insert_id;
        $stmt->close();
        
        // Log loan application submission
        ActivityLogger::logSubmit('Loan', 'ajaxLoan.php', $loan_id, "Submitted loan application: {$inv_no}, Amount: {$loan_amount}, Type: {$loan_type}", 'emp_loan');
        
        // ===== CREATE APPROVAL CHAIN using ApprovalChainManager =====
        try {
            $chainManager = new ApprovalChainManager($conDB, $pdo, new ActivityLogger());
            $chainResult = $chainManager->createApprovalChain('loan_request', $inv_no, $emp_id);
            
            if (!$chainResult['success']) {
                throw new Exception($chainResult['message'] ?? 'Failed to create approval chain');
            }
            
            error_log("Loan $inv_no: Created approval chain with " . ($chainResult['total_levels'] ?? 0) . " approval levels");
        } catch (Exception $chainEx) {
            error_log("Loan approval chain error for $inv_no: " . $chainEx->getMessage());
            echo json_encode(['status' => 'error', 'title' => 'Chain Error', 'message' => 'Failed to build approval chain: ' . $chainEx->getMessage(), 'type' => 'error']);
            return;
        }
        
        // Add initial status history to smt_request_status
        $initial_status = 'draft';
        $note_initial = 'Loan application submitted - ' . ucfirst($loan_type) . ' loan for SAR ' . number_format($loan_amount, 2);
        $hist_initial = $conDB->prepare("INSERT INTO smt_request_status (inv_no, emp_id, emp_name, note, status) VALUES (?, ?, 'System', ?, ?)");
        $hist_initial->bind_param("ssss", $inv_no, $emp_id, $note_initial, $initial_status);
        $hist_initial->execute();
        $hist_initial->close();
        
        // Add pending status history and notify first approver
        if (!empty($chainResult['first_approver'])) {
            $pending_status = 'pending_level_1';
            $note_pending = 'Pending approval at level 1';
            $hist_pending = $conDB->prepare("INSERT INTO smt_request_status (inv_no, emp_id, emp_name, note, status) VALUES (?, ?, 'System', ?, ?)");
            $hist_pending->bind_param("ssss", $inv_no, $emp_id, $note_pending, $pending_status);
            $hist_pending->execute();
            $hist_pending->close();
            
            // ===== NOTIFY FIRST APPROVER using ApprovalChainManager =====
            $firstApprover = $chainResult['first_approver'];
            if ($firstApprover && !empty($firstApprover['email'])) {
                // Get employee name for email template
                $employee_name = 'Employee';
                $emp_result = mysqli_query($conDB, "SELECT name FROM employees WHERE emp_id = '$emp_id' LIMIT 1");
                if ($emp_result && $emp_row = mysqli_fetch_assoc($emp_result)) {
                    $employee_name = $emp_row['name'];
                }
                if ($emp_result) mysqli_free_result($emp_result);
                
                $emailData = [
                    'APPROVER_NAME' => $firstApprover['name'],
                    'REQUEST_ID' => $inv_no,
                    'EMPLOYEE_NAME' => $employee_name,
                    'LOAN_TYPE' => str_replace('_', ' ', $loan_type),
                    'LOAN_AMOUNT' => number_format($loan_amount, 2),
                    'INSTALLMENTS' => $installments,
                    'REQUEST_URL' => 'https://hr.almutlaksystem.com/all_applied_loan.php?status=my_pending'
                ];
                
                send_approval_email(
                    $conDB,
                    $firstApprover['email'],
                    $firstApprover['name'],
                    'New Loan Request Pending Approval - ' . ucfirst(str_replace('_', ' ', $loan_type)),
                    'loan_request',
                    $emailData
                );
                
                create_browser_notification(
                    $conDB,
                    $firstApprover['approver_id'],
                    'New Loan Request',
                    "A new loan request ($inv_no) from $employee_name for SAR " . number_format($loan_amount, 2) . " is pending your approval.",
                    'all_applied_loan.php?status=my_pending'
                );
                
                error_log("Loan $inv_no: Notified first approver (emp_id: {$firstApprover['approver_id']}): {$firstApprover['name']}");
            }
        }

        // --- [NEW] Notify direct supervisor immediately on submission ---
        // We already validated supervisor at the top; reuse or re-fetch to be safe
        try {
            $sup_check = validate_employee_supervisor($conDB, $emp_id);
            if ($sup_check['valid'] && !empty($sup_check['supervisor_id'])) {
                $supervisor_id = $sup_check['supervisor_id'];
                if (function_exists('getEmployeeDetailsForApproval')) {
                    $sup_details = getEmployeeDetailsForApproval($conDB, $supervisor_id);
                    if ($sup_details && !empty($sup_details['email'])) {
                        // Build email template data
                        $employee_name = 'Employee';
                        $emp_result2 = mysqli_query($conDB, "SELECT name FROM employees WHERE emp_id = '$emp_id' LIMIT 1");
                        if ($emp_result2 && $emp_row2 = mysqli_fetch_assoc($emp_result2)) {
                            $employee_name = $emp_row2['name'];
                        }
                        if ($emp_result2) mysqli_free_result($emp_result2);

                        $base_url = (function_exists('get_base_url') ? get_base_url() : (((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST']));
                        $template_data_sup = [
                            'APPROVER_NAME' => $sup_details['name'],
                            'REQUEST_ID' => $inv_no,
                            'EMPLOYEE_NAME' => $employee_name,
                            'LOAN_TYPE' => str_replace('_', ' ', $loan_type),
                            'LOAN_AMOUNT' => number_format($loan_amount, 2),
                            'INSTALLMENTS' => $installments,
                            'REQUEST_URL' => $base_url . '/all_applied_loan.php?status=my_pending',
                            'EMAIL_MESSAGE' => 'A new loan request from your direct report is submitted and pending in the approval chain.'
                        ];
                        $email_subject_sup = 'New Loan Request by Your Direct Report - ' . ucfirst(str_replace('_', ' ', $loan_type)) . ' (' . $inv_no . ')';
                        // Use the same templating function; request_type 'loan_request'
                        send_approval_email($conDB, $sup_details['email'], $sup_details['name'], $email_subject_sup, 'loan_request', $template_data_sup);
                    }
                }
            }
        } catch (Exception $e) {
            // Don't fail the submission if email fails; just log
            error_log('Supervisor email notify error for loan ' . $inv_no . ': ' . $e->getMessage());
        }
        // --- [END NEW] ---
        
        echo json_encode(['status' => 'success', 'title' => 'Success', 'message' => 'Your loan application has been submitted successfully.', 'type' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'title' => 'Database Error', 'message' => 'Failed to submit loan application: ' . $stmt->error, 'type' => 'error']);
        $stmt->close();
    }
}


function calculateEndOfService($joining_date, $total_salary) {
    if (!$joining_date || !$total_salary) return 0;
    $joinDate = new DateTime($joining_date);
    $currentDate = new DateTime();
    $interval = $joinDate->diff($currentDate);
    $yearsOfService = $interval->y + ($interval->m / 12) + ($interval->d / 365.25);
    $benefit = 0;
    if ($yearsOfService <= 5) {
        $benefit = ($total_salary / 2) * $yearsOfService;
    } else {
        $firstFiveYearsBenefit = ($total_salary / 2) * 5;
        $subsequentYears = $yearsOfService - 5;
        $subsequentYearsBenefit = $total_salary * $subsequentYears;
        $benefit = $firstFiveYearsBenefit + $subsequentYearsBenefit;
    }
    return $benefit;
}

function reject_loan() {
    global $conDB;
    if (session_status() == PHP_SESSION_NONE) session_start();
    $username = $_SESSION['auth_user']['user_id'] ?? null; // admin_login.id_iqama
    if (empty($username)) {
        echo json_encode(['status' => 'error', 'title' => 'Authentication Error', 'message' => 'User session not found. Please log in again.', 'type' => 'error']);
        return;
    }
    // Resolve approver's employee ID
    $approver_emp_id = $_SESSION['empid'] ?? null;
    if (!$approver_emp_id) {
        $stmt_user = $conDB->prepare("SELECT emp_id FROM admin_login WHERE id_iqama = ? LIMIT 1");
        $stmt_user->bind_param("s", $username);
        $stmt_user->execute();
        $user_row = $stmt_user->get_result()->fetch_assoc();
        $stmt_user->close();
        $approver_emp_id = $user_row['emp_id'] ?? null;
    }
    if (!isset($_POST['loan_id'], $_POST['approver_role'], $_POST['rejection_note'])) {
        echo json_encode(['status' => 'error', 'title' => 'Input Error', 'message' => 'Missing required rejection data.', 'type' => 'error']);
        return;
    }
    $loan_id = filter_var($_POST['loan_id'], FILTER_VALIDATE_INT);
    $approver_role = mysqli_real_escape_string($conDB, $_POST['approver_role']);
    $rejection_note = mysqli_real_escape_string($conDB, $_POST['rejection_note']);
    if ($loan_id === false) {
        echo json_encode(['status' => 'error', 'title' => 'Input Error', 'message' => 'Invalid Loan ID.', 'type' => 'error']);
        return;
    }
    // Try to update chain row (if present)
    $inv_no = null;
    $stmt_inv = $conDB->prepare("SELECT inv_no FROM emp_loan WHERE id = ? LIMIT 1");
    $stmt_inv->bind_param("i", $loan_id);
    if ($stmt_inv->execute()) {
        $inv_res = $stmt_inv->get_result();
        $inv_row = $inv_res->fetch_assoc();
        $inv_no = $inv_row['inv_no'] ?? null;
    }
    $stmt_inv->close();
    if (!empty($inv_no) && $approver_emp_id) {
        $type_stmt = $conDB->prepare("SELECT id FROM approval_request_types WHERE type_name = 'loan_request' LIMIT 1");
        $type_stmt->execute();
        $request_type_id = ($type_stmt->get_result()->fetch_assoc()['id'] ?? 2);
        $type_stmt->close();
        // Update request_approvers with rejection status and rejection note
        $rej = $conDB->prepare("UPDATE request_approvers SET status = 'rejected', action_date = NOW(), note = ? WHERE request_inv_no = ? AND request_type_id = ? AND approver_id = ? AND status = 'pending'");
        $rej->bind_param("ssii", $rejection_note, $inv_no, $request_type_id, $approver_emp_id);
        $rej->execute();
        $rej->close();
    }

    // Update loan status to rejected
    $stmt = $conDB->prepare("UPDATE `emp_loan` SET `status` = 'rejected' WHERE `id` = ?");
    $stmt->bind_param("i", $loan_id);

    if ($stmt->execute()) {
        // Add rejection status history to smt_request_status
        if (!empty($inv_no)) {
            $reject_status = 'rejected';
            $note_reject = 'Loan rejected: ' . $rejection_note;
            $hist_reject = $conDB->prepare("INSERT INTO smt_request_status (inv_no, emp_id, emp_name, note, status) VALUES (?, ?, 'System', ?, ?)");
            $hist_reject->bind_param("ssss", $inv_no, $approver_emp_id, $note_reject, $reject_status);
            $hist_reject->execute();
            $hist_reject->close();
        }
        
        // --- SEND REJECTION NOTIFICATIONS ---
        if (!empty($inv_no) && $approver_emp_id && function_exists('getEmployeeDetails')) {
            // Get approver name for notification message
            $approver_details = getEmployeeDetails($conDB, $approver_emp_id);
            $approver_name = ($approver_details && $approver_details['name'] !== 'N/A') ? $approver_details['name'] : 'Approver';
            
            // 1. Notify the loan creator
            $stmt_creator = $conDB->prepare("SELECT emp_id FROM emp_loan WHERE id = ? LIMIT 1");
            $stmt_creator->bind_param("i", $loan_id);
            $stmt_creator->execute();
            $creator_res = $stmt_creator->get_result();
            $creator_row = $creator_res->fetch_assoc();
            $stmt_creator->close();
            
            if ($creator_row) {
                $creator_emp_id = $creator_row['emp_id'];
                if ($creator_emp_id != $approver_emp_id) { // Don't notify self
                    $creator_details = getEmployeeDetails($conDB, $creator_emp_id);
                    if ($creator_details && $creator_details['name'] !== 'N/A') {
                        // Send browser notification
                        if (function_exists('create_browser_notification')) {
                            $notification_title = "Loan Request Rejected";
                            $notification_message = "Your loan request " . htmlspecialchars($inv_no) . " was rejected by " . htmlspecialchars($approver_name) . ". Reason: " . htmlspecialchars($rejection_note);
                            $notification_url = "loan_status_history.php?inv_no=" . urlencode($inv_no);
                            create_browser_notification($conDB, $creator_emp_id, $notification_title, $notification_message, $notification_url);
                        }
                        
                        // Send email with template
                        if (!empty($creator_details['email']) && function_exists('send_approval_email')) {
                            // Fetch loan details for email template
                            $loan_details_stmt = $conDB->prepare("SELECT el.*, e.name as employee_name FROM emp_loan el LEFT JOIN employees e ON el.emp_id = e.emp_id WHERE el.inv_no = ? LIMIT 1");
                            $loan_details_stmt->bind_param("s", $inv_no);
                            $loan_details_stmt->execute();
                            $loan_details = $loan_details_stmt->get_result()->fetch_assoc();
                            $loan_details_stmt->close();
                            
                            if ($loan_details) {
                                $base_url = (function_exists('get_base_url') ? get_base_url() : 'https://hr.almutlaksystem.com');
                                $template_data = [
                                    'APPROVER_NAME' => $creator_details['name'],
                                    'REQUEST_ID' => $inv_no,
                                    'EMPLOYEE_NAME' => $loan_details['employee_name'] ?? 'Employee',
                                    'LOAN_TYPE' => str_replace('_', ' ', $loan_details['loan_type']),
                                    'LOAN_AMOUNT' => number_format($loan_details['loan_amount'], 2),
                                    'INSTALLMENTS' => $loan_details['installments'],
                                    'REQUEST_URL' => $base_url . '/loan_status_history.php?inv_no=' . urlencode($inv_no),
                                    'REJECTION_REASON' => $rejection_note,
                                    'REJECTED_BY' => $approver_name,
                                    'EMAIL_MESSAGE' => 'Unfortunately, your loan request has been rejected.'
                                ];
                                
                                $email_subject = "Loan Request Rejected - " . ucfirst(str_replace('_', ' ', $loan_details['loan_type'])) . " (" . $inv_no . ")";
                                send_approval_email($conDB, $creator_details['email'], $creator_details['name'], $email_subject, 'loan_request', $template_data);
                            }
                        }
                    }
                }
            }
            
            // 2. Notify previous approvers who already approved
            if (function_exists('mysqli_prepare')) {
                $type_stmt = $conDB->prepare("SELECT id FROM approval_request_types WHERE type_name = 'loan_request' LIMIT 1");
                $type_stmt->execute();
                $request_type_id = ($type_stmt->get_result()->fetch_assoc()['id'] ?? 2);
                $type_stmt->close();
                
                $prev_sql = "SELECT approver_id FROM request_approvers WHERE request_inv_no = ? AND request_type_id = ? AND status = 'approved'";
                $prev_stmt = $conDB->prepare($prev_sql);
                $prev_stmt->bind_param("si", $inv_no, $request_type_id);
                $prev_stmt->execute();
                $prev_result = $prev_stmt->get_result();
                
                while ($prev_row = $prev_result->fetch_assoc()) {
                    $prev_approver_id = $prev_row['approver_id'];
                    if ($prev_approver_id != $approver_emp_id) { // Don't notify the rejector
                        $prev_approver_details = getEmployeeDetails($conDB, $prev_approver_id);
                        if ($prev_approver_details && $prev_approver_details['name'] !== 'N/A') {
                            // Send browser notification
                            if (function_exists('create_browser_notification')) {
                                $notification_title = "Loan Request Rejected";
                                $notification_message = "Loan request " . htmlspecialchars($inv_no) . " was rejected by " . htmlspecialchars($approver_name) . ". Reason: " . htmlspecialchars($rejection_note);
                                $notification_url = "loan_status_history.php?inv_no=" . urlencode($inv_no);
                                create_browser_notification($conDB, $prev_approver_id, $notification_title, $notification_message, $notification_url);
                            }
                            
                            // Send email with template (optional - you may want to only email the creator)
                            if (!empty($prev_approver_details['email']) && function_exists('send_approval_email')) {
                                // Fetch loan details for email template
                                $loan_details_stmt = $conDB->prepare("SELECT el.*, e.name as employee_name FROM emp_loan el LEFT JOIN employees e ON el.emp_id = e.emp_id WHERE el.inv_no = ? LIMIT 1");
                                $loan_details_stmt->bind_param("s", $inv_no);
                                $loan_details_stmt->execute();
                                $loan_details = $loan_details_stmt->get_result()->fetch_assoc();
                                $loan_details_stmt->close();
                                
                                if ($loan_details) {
                                    $base_url = (function_exists('get_base_url') ? get_base_url() : 'https://hr.almutlaksystem.com');
                                    $template_data = [
                                        'APPROVER_NAME' => $prev_approver_details['name'],
                                        'REQUEST_ID' => $inv_no,
                                        'EMPLOYEE_NAME' => $loan_details['employee_name'] ?? 'Employee',
                                        'LOAN_TYPE' => str_replace('_', ' ', $loan_details['loan_type']),
                                        'LOAN_AMOUNT' => number_format($loan_details['loan_amount'], 2),
                                        'INSTALLMENTS' => $loan_details['installments'],
                                        'REQUEST_URL' => $base_url . '/loan_status_history.php?inv_no=' . urlencode($inv_no),
                                        'REJECTION_REASON' => $rejection_note,
                                        'REJECTED_BY' => $approver_name,
                                        'EMAIL_MESSAGE' => 'A loan request that you previously approved has been rejected at a later approval stage.'
                                    ];
                                    
                                    $email_subject = "Loan Request Rejected - " . ucfirst(str_replace('_', ' ', $loan_details['loan_type'])) . " (" . $inv_no . ")";
                                    send_approval_email($conDB, $prev_approver_details['email'], $prev_approver_details['name'], $email_subject, 'loan_request', $template_data);
                                }
                            }
                        }
                    }
                }
                $prev_stmt->close();
            }
        }
        // --- END REJECTION NOTIFICATIONS ---
        
        echo json_encode(['status' => 'success', 'title' => 'Rejected!', 'message' => 'The loan request has been rejected.', 'type' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'title' => 'Database Error', 'message' => 'Failed to reject the loan: ' . $stmt->error, 'type' => 'error']);
    }
    $stmt->close();
}

function get_loan_balance() {
    global $conDB;
    if (!isset($_POST['loan_id'])) {
        echo json_encode(['status' => 'error', 'message' => 'Loan ID not provided.']);
        return;
    }
    $loan_id = filter_var($_POST['loan_id'], FILTER_VALIDATE_INT);
    if ($loan_id === false) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid Loan ID.']);
        return;
    }

    // Get total payable amount from the loan record
    $stmt_loan = $conDB->prepare("SELECT total_payable FROM emp_loan WHERE id = ?");
    $stmt_loan->bind_param("i", $loan_id);
    $stmt_loan->execute();
    $loan = $stmt_loan->get_result()->fetch_assoc();
    $stmt_loan->close();

    if (!$loan) {
        echo json_encode(['status' => 'error', 'message' => 'Loan not found.']);
        return;
    }
    $total_payable = $loan['total_payable'];

    // Get total amount paid so far
    $stmt_paid = $conDB->prepare("SELECT COALESCE(SUM(amount), 0) as total_paid FROM emp_loan_payments WHERE loan_id = ?");
    $stmt_paid->bind_param("i", $loan_id);
    $stmt_paid->execute();
    $total_paid = $stmt_paid->get_result()->fetch_assoc()['total_paid'] ?? 0;
    $stmt_paid->close();

    $remaining_balance = $total_payable - $total_paid;

    echo json_encode(['status' => 'success', 'remaining_balance' => round($remaining_balance, 2)]);
}

function add_manual_payment() {
    global $conDB;
    
    // Handle emp_id - might come from different sources
    $emp_id = null;
    if (isset($_POST['emp_id'])) {
        $emp_id = $_POST['emp_id'];
    } elseif (isset($_GET['emp_id'])) {
        $emp_id = $_GET['emp_id'];
    } else {
        // Try to get from loan_id
        if (isset($_POST['loan_id'])) {
            $loan_id_check = filter_var($_POST['loan_id'], FILTER_VALIDATE_INT);
            if ($loan_id_check !== false) {
                $stmt_emp = $conDB->prepare("SELECT emp_id FROM emp_loan WHERE id = ? LIMIT 1");
                $stmt_emp->bind_param("i", $loan_id_check);
                $stmt_emp->execute();
                $result_emp = $stmt_emp->get_result();
                if ($row_emp = $result_emp->fetch_assoc()) {
                    $emp_id = $row_emp['emp_id'];
                    error_log("emp_id retrieved from loan record: $emp_id");
                }
                $stmt_emp->close();
            }
        }
    }
    
    // Handle file upload - check both 'payment_proof' and 'attachment' (fallback)
    $file_field = null;
    if (isset($_FILES['payment_proof']) && $_FILES['payment_proof']['error'] == UPLOAD_ERR_OK) {
        $file_field = 'payment_proof';
    } elseif (isset($_FILES['attachment']) && $_FILES['attachment']['error'] == UPLOAD_ERR_OK) {
        $file_field = 'attachment';
        error_log("Using 'attachment' field instead of 'payment_proof'");
    }
    
    // Validate required fields
    if (!isset($_POST['loan_id'], $_POST['payment_amount'], $_POST['payment_date']) 
        || empty($emp_id) || $file_field === null) {
        
        // More detailed error logging
        $missing = [];
        if (!isset($_POST['loan_id'])) $missing[] = 'loan_id';
        if (empty($emp_id)) $missing[] = 'emp_id';
        if (!isset($_POST['payment_amount'])) $missing[] = 'payment_amount';
        if (!isset($_POST['payment_date'])) $missing[] = 'payment_date';
        if ($file_field === null) $missing[] = 'payment_proof/attachment';
        
        error_log("Missing fields: " . implode(', ', $missing));
        
        echo json_encode([
            'status' => 'error', 
            'title' => 'Input Error', 
            'message' => 'Missing required fields: ' . implode(', ', $missing), 
            'type' => 'error'
        ]);
        return;
    }

    $loan_id = filter_var($_POST['loan_id'], FILTER_VALIDATE_INT);
    $emp_id = mysqli_real_escape_string($conDB, $emp_id); // Employee ID is a string
    $payment_amount = filter_var($_POST['payment_amount'], FILTER_VALIDATE_FLOAT);
    $payment_date_str = $_POST['payment_date'];
    $receipt_id = !empty($_POST['receipt_id']) ? mysqli_real_escape_string($conDB, $_POST['receipt_id']) : null;
    $payment_note = !empty($_POST['payment_note']) ? mysqli_real_escape_string($conDB, $_POST['payment_note']) : null;
    
    error_log("Parsed - loan_id: $loan_id, emp_id: $emp_id, amount: $payment_amount, file_field: $file_field");
    
    // Validate data types
    if ($loan_id === false || empty($emp_id) || $payment_amount === false || $payment_amount <= 0) {
        echo json_encode([
            'status' => 'error', 
            'title' => 'Invalid Input', 
            'message' => 'Please provide valid payment amount and loan details.', 
            'type' => 'error'
        ]);
        return;
    }

    // Validate date
    $payment_date = DateTime::createFromFormat('Y-m-d', $payment_date_str);
    if (!$payment_date) {
        echo json_encode([
            'status' => 'error', 
            'title' => 'Invalid Date', 
            'message' => 'Please provide a valid payment date.', 
            'type' => 'error'
        ]);
        return;
    }

    // Handle file upload
    $attachment_filename = null;
    if ($file_field && isset($_FILES[$file_field]) && $_FILES[$file_field]['error'] == UPLOAD_ERR_OK) {
        $allowed_types = ['application/pdf', 'image/jpeg', 'image/jpg', 'image/png', 
                          'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
        $file_type = $_FILES[$file_field]['type'];
        $file_size = $_FILES[$file_field]['size'];
        
        // Validate file type
        if (!in_array($file_type, $allowed_types)) {
            echo json_encode([
                'status' => 'error', 
                'title' => 'Invalid File Type', 
                'message' => 'Only PDF, JPG, PNG, and DOC files are allowed.', 
                'type' => 'error'
            ]);
            return;
        }
        
        // Validate file size (10MB max)
        if ($file_size > 10 * 1024 * 1024) {
            echo json_encode([
                'status' => 'error', 
                'title' => 'File Too Large', 
                'message' => 'File size must not exceed 10MB.', 
                'type' => 'error'
            ]);
            return;
        }
        
        // Create directory if not exists
        $upload_dir = __DIR__ . '/../../assets/loan_manual_payments/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        // Generate unique filename
        $file_extension = pathinfo($_FILES[$file_field]['name'], PATHINFO_EXTENSION);
        $attachment_filename = 'manual_payment_' . $loan_id . '_' . time() . '.' . $file_extension;
        $upload_file = $upload_dir . $attachment_filename;
        
        if (!move_uploaded_file($_FILES[$file_field]['tmp_name'], $upload_file)) {
            echo json_encode([
                'status' => 'error', 
                'title' => 'Upload Error', 
                'message' => 'Failed to save payment proof attachment.', 
                'type' => 'error'
            ]);
            return;
        }
    } else {
        echo json_encode([
            'status' => 'error', 
            'title' => 'File Required', 
            'message' => 'Payment proof attachment is required.', 
            'type' => 'error'
        ]);
        return;
    }

    // Start transaction
    $conDB->begin_transaction();
    try {
        // Get loan details and lock row
        $stmt_loan = $conDB->prepare("SELECT inv_no, total_payable, monthly_deduction, status FROM emp_loan WHERE id = ? AND emp_id = ? FOR UPDATE");
        $stmt_loan->bind_param("is", $loan_id, $emp_id);
        $stmt_loan->execute();
        $loan_result = $stmt_loan->get_result();
        
        if ($loan_result->num_rows === 0) {
            throw new Exception('Loan not found or does not belong to this employee.');
        }
        
        $loan = $loan_result->fetch_assoc();
        $loan_inv_no = $loan['inv_no'];
        $monthly_deduction = $loan['monthly_deduction'];
        $stmt_loan->close();
        
        // Check if loan is still active
        if ($loan['status'] === 'paid') {
            throw new Exception('This loan has already been fully paid.');
        }

        // Calculate total paid and remaining balance
        $stmt_paid = $conDB->prepare("SELECT COALESCE(SUM(amount), 0) as total_paid FROM emp_loan_payments WHERE loan_id = ?");
        $stmt_paid->bind_param("i", $loan_id);
        $stmt_paid->execute();
        $total_paid_result = $stmt_paid->get_result();
        $total_paid = $total_paid_result->fetch_assoc()['total_paid'] ?? 0;
        $stmt_paid->close();

        $remaining_balance = $loan['total_payable'] - $total_paid;

        // Validate payment amount doesn't exceed remaining balance
        if ($payment_amount > ($remaining_balance + 0.01)) { // Add small tolerance for floating point
            throw new Exception('Payment amount (' . number_format($payment_amount, 2) . ' SAR) cannot exceed remaining balance (' . number_format($remaining_balance, 2) . ' SAR).');
        }

        // Insert the manual payment record
        $stmt_insert = $conDB->prepare(
            "INSERT INTO emp_loan_payments (loan_id, payment_date, amount, receipt_id, attachment, payment_method) 
             VALUES (?, ?, ?, ?, ?, 'manual')"
        );
        $stmt_insert->bind_param("isdss", $loan_id, $payment_date_str, $payment_amount, $receipt_id, $attachment_filename);
        $stmt_insert->execute();
        $stmt_insert->close();

        // Adjust payroll deductions to reflect manual payment
        // Strategy: Remove/reduce future payroll deductions to match the manual payment amount
        $remaining_to_deduct = $payment_amount;
        
        // Get future payroll deductions for this loan (ordered by month ascending)
        $current_month = date('Y-m'); // Current month in YYYY-MM format
        $deduction_pattern = "%{$loan_inv_no}%";
        
        $stmt_deductions = $conDB->prepare(
            "SELECT id, note, month FROM payroll_deductions 
             WHERE emp_id = ? AND deduction LIKE ? AND status = 1 AND month >= ?
             ORDER BY month ASC"
        );
        $stmt_deductions->bind_param("sss", $emp_id, $deduction_pattern, $current_month);
        $stmt_deductions->execute();
        $deductions_result = $stmt_deductions->get_result();
        $deductions_to_adjust = [];
        
        while ($deduction_row = $deductions_result->fetch_assoc()) {
            $deductions_to_adjust[] = $deduction_row;
        }
        $stmt_deductions->close();
        
        error_log("Found " . count($deductions_to_adjust) . " future payroll deductions to adjust for manual payment of {$payment_amount}");
        
        // Adjust each deduction
        foreach ($deductions_to_adjust as $deduction) {
            if ($remaining_to_deduct <= 0) {
                break; // No more adjustment needed
            }
            
            $deduction_id = $deduction['id'];
            $scheduled_amount = floatval($deduction['note']); // Amount stored as string in 'note' field
            
            if ($remaining_to_deduct >= $scheduled_amount) {
                // This entire deduction is covered by the manual payment - cancel it
                $stmt_cancel = $conDB->prepare("UPDATE payroll_deductions SET status = 0 WHERE id = ?");
                $stmt_cancel->bind_param("i", $deduction_id);
                $stmt_cancel->execute();
                $stmt_cancel->close();
                
                $remaining_to_deduct -= $scheduled_amount;
                error_log("Cancelled payroll deduction ID {$deduction_id} for month {$deduction['month']} - full amount {$scheduled_amount}");
            } else {
                // Partial reduction - reduce this deduction amount
                $new_amount = $scheduled_amount - $remaining_to_deduct;
                $new_amount_str = number_format($new_amount, 2, '.', '');
                
                $stmt_reduce = $conDB->prepare("UPDATE payroll_deductions SET note = ? WHERE id = ?");
                $stmt_reduce->bind_param("si", $new_amount_str, $deduction_id);
                $stmt_reduce->execute();
                $stmt_reduce->close();
                
                error_log("Reduced payroll deduction ID {$deduction_id} for month {$deduction['month']} from {$scheduled_amount} to {$new_amount_str}");
                $remaining_to_deduct = 0;
            }
        }

        // Check if loan is now fully paid (with small tolerance for rounding)
        $new_total_paid = $total_paid + $payment_amount;
        if ($new_total_paid >= ($loan['total_payable'] - 0.01)) {
            $stmt_update = $conDB->prepare("UPDATE emp_loan SET status = 'paid' WHERE id = ?");
            $stmt_update->bind_param("i", $loan_id);
            $stmt_update->execute();
            $stmt_update->close();
            
            // Cancel all remaining payroll deductions for this loan
            $stmt_cancel_all = $conDB->prepare(
                "UPDATE payroll_deductions SET status = 0 
                 WHERE emp_id = ? AND deduction LIKE ? AND status = 1"
            );
            $stmt_cancel_all->bind_param("ss", $emp_id, $deduction_pattern);
            $stmt_cancel_all->execute();
            $cancelled_count = $stmt_cancel_all->affected_rows;
            $stmt_cancel_all->close();
            
            error_log("Loan fully paid - cancelled {$cancelled_count} remaining payroll deductions");
            $status_message = ' The loan has been marked as fully paid and all future payroll deductions have been cancelled.';
        } else {
            $status_message = ' Remaining balance: ' . number_format($loan['total_payable'] - $new_total_paid, 2) . ' SAR. Future payroll deductions have been adjusted accordingly.';
        }

        $conDB->commit();
        
        echo json_encode([
            'status' => 'success', 
            'title' => 'Payment Recorded', 
            'message' => 'Manual payment of ' . number_format($payment_amount, 2) . ' SAR has been recorded successfully.' . $status_message, 
            'type' => 'success'
        ]);

    } catch (Exception $e) {
        $conDB->rollback();
        
        // Delete uploaded file if transaction failed
        if ($attachment_filename && file_exists(__DIR__ . '/../../assets/loan_manual_payments/' . $attachment_filename)) {
            unlink(__DIR__ . '/../../assets/loan_manual_payments/' . $attachment_filename);
        }
        
        error_log("Manual payment error: " . $e->getMessage());
        echo json_encode([
            'status' => 'error', 
            'title' => 'Payment Failed', 
            'message' => $e->getMessage(), 
            'type' => 'error'
        ]);
    }
}

function modify_and_approve_loan() {
    global $conDB, $pdo;
    if (session_status() == PHP_SESSION_NONE) session_start();
    $username = $_SESSION['auth_user']['user_id'] ?? null;
    if (empty($username)) {
        echo json_encode(['status' => 'error', 'title' => 'Authentication Error', 'message' => 'User session not found. Please log in again.', 'type' => 'error']);
        return;
    }

    // Get approver emp_id from session
    $approver_emp_id = $_SESSION['empid'] ?? null;
    if (!$approver_emp_id) {
        $stmt_user = $conDB->prepare("SELECT emp_id FROM admin_login WHERE id_iqama = ? LIMIT 1");
        $stmt_user->bind_param("s", $username);
        $stmt_user->execute();
        $res_user = $stmt_user->get_result();
        $user_row = $res_user->fetch_assoc();
        $stmt_user->close();
        $approver_emp_id = $user_row['emp_id'] ?? null;
    }

    // Validate inputs
    if (!isset($_POST['loan_id'], $_POST['loan_amount'], $_POST['installments'])) {
        echo json_encode(['status' => 'error', 'title' => 'Input Error', 'message' => 'Missing required modification data.', 'type' => 'error']);
        return;
    }
    $loan_id = filter_var($_POST['loan_id'], FILTER_VALIDATE_INT);
    $new_loan_amount = filter_var($_POST['loan_amount'], FILTER_VALIDATE_FLOAT);
    $new_installments = filter_var($_POST['installments'], FILTER_VALIDATE_INT);
    $approval_comment = trim($_POST['approval_comment'] ?? '');
    $approval_comment = mb_substr($approval_comment, 0, 5000);

    if ($loan_id === false || $new_loan_amount === false || $new_loan_amount <= 0 || $new_installments === false || $new_installments <= 0) {
        echo json_encode(['status' => 'error', 'title' => 'Invalid Input', 'message' => 'Please provide a valid loan amount and number of installments.', 'type' => 'error']);
        return;
    }

    $conDB->begin_transaction();
    try {
        // Fetch loan and inv_no
        $stmt_loan = $conDB->prepare("SELECT inv_no, start_date FROM emp_loan WHERE id = ?");
        $stmt_loan->bind_param("i", $loan_id);
        $stmt_loan->execute();
        $result = $stmt_loan->get_result();
        $loan = $result->fetch_assoc();
        $stmt_loan->close();

        if (!$loan) {
            throw new Exception("Loan not found.");
        }
        
        $inv_no = $loan['inv_no'];
        $start_date = new DateTime($loan['start_date']);

        // Recalculate loan terms
        $new_total_payable = $new_loan_amount;
        $new_monthly_deduction = $new_total_payable / $new_installments;
        $new_end_date = clone $start_date;
        $new_end_date->modify('+' . ($new_installments - 1) . ' months');
        $new_end_date_str = $new_end_date->format('Y-m-d');

        // Get approver emp_id for tracking
        $approver_emp_id = $_SESSION['empid'] ?? null;
        if (!$approver_emp_id) {
            $stmt_user = $conDB->prepare("SELECT emp_id FROM admin_login WHERE id_iqama = ? LIMIT 1");
            $stmt_user->bind_param("s", $username);
            $stmt_user->execute();
            $res_user = $stmt_user->get_result();
            $user_row = $res_user->fetch_assoc();
            $stmt_user->close();
            $approver_emp_id = $user_row['emp_id'] ?? null;
        }
        
        // Update the loan record - keep original loan_amount, set approved_amount with modified value
        $stmt_update = $conDB->prepare("UPDATE `emp_loan` SET 
            `approved_amount` = ?,
            `approved_by_emp_id` = ?,
            `approved_at` = NOW(),
            `total_payable` = ?, 
            `monthly_deduction` = ?, 
            `end_date` = ?,
            `installments` = ?
            WHERE `id` = ?");
        $stmt_update->bind_param("dsddsii", $new_loan_amount, $approver_emp_id, $new_total_payable, $new_monthly_deduction, $new_end_date_str, $new_installments, $loan_id);
        $stmt_update->execute();

        if ($stmt_update->affected_rows === 0) {
            throw new Exception("Loan could not be updated.");
        }
        $stmt_update->close();

        // Use ApprovalChainManager to process approval
        $chainManager = new ApprovalChainManager($conDB, $pdo, new ActivityLogger());
        
        // Build approval comment with modification details
        $mod_note = "GM Modified and Approved: Amount changed to SAR " . number_format($new_loan_amount, 2) . ", Installments changed to " . $new_installments . " months.";
        if (!empty($approval_comment)) {
            $mod_note .= " - " . $approval_comment;
        }

        // Process approval through chain manager
        $approvalResult = $chainManager->processApproval($inv_no, $approver_emp_id, 'approve', $mod_note);

        if (!$approvalResult['success']) {
            throw new Exception($approvalResult['message']);
        }

        $isFinalApproval = $approvalResult['is_final'];
        $nextApprover = $approvalResult['next_approver'] ?? null;

        // Log the GM modification and approval
        ActivityLogger::logApproval('Loan', 'ajaxLoan.php', $loan_id, 'approved', "GM Modified and Approved: " . $mod_note, 'emp_loan');

        // Add status history
        $status_label = 'approved_gm_modification';
        $hist_stmt = $conDB->prepare("INSERT INTO smt_request_status (inv_no, emp_id, emp_name, note, status) VALUES (?, ?, 'System', ?, ?)");
        $hist_stmt->bind_param("siss", $inv_no, $approver_emp_id, $mod_note, $status_label);
        $hist_stmt->execute();
        $hist_stmt->close();

        // Send email to next approver if not final approval
        if (!$isFinalApproval && $nextApprover) {
            $nextApproverEmail = $nextApprover['email'] ?? null;
            $nextApproverName = $nextApprover['fullname'] ?? $nextApprover['name'] ?? 'Next Approver';
            $nextApproverId = $nextApprover['approver_id'] ?? $nextApprover['emp_id'] ?? null;

            // Fetch loan details for email
            $loan_details_stmt = $conDB->prepare("SELECT el.*, e.name as employee_name FROM emp_loan el LEFT JOIN employees e ON el.emp_id = e.emp_id WHERE el.inv_no = ? LIMIT 1");
            $loan_details_stmt->bind_param("s", $inv_no);
            $loan_details_stmt->execute();
            $loan_details = $loan_details_stmt->get_result()->fetch_assoc();
            $loan_details_stmt->close();

            if ($nextApproverEmail && $nextApproverId && $loan_details) {
                $emailData = [
                    'APPROVER_NAME' => $nextApproverName,
                    'REQUEST_ID' => $inv_no,
                    'EMPLOYEE_NAME' => $loan_details['employee_name'] ?? 'Employee',
                    'LOAN_TYPE' => str_replace('_', ' ', $loan_details['loan_type']),
                    'LOAN_AMOUNT' => number_format($loan_details['loan_amount'], 2),
                    'INSTALLMENTS' => $loan_details['installments'],
                    'REQUEST_URL' => 'https://hr.almutlaksystem.com/all_applied_loan.php?status=my_pending',
                    'EMAIL_MESSAGE' => 'A loan request has been modified by the GM and now requires your approval.'
                ];

                send_approval_email(
                    $conDB,
                    $nextApproverEmail,
                    $nextApproverName,
                    "Loan Request Pending Your Approval - Modified by GM (" . $inv_no . ")",
                    'loan_request',
                    $emailData
                );

                // Also send browser notification
                if (function_exists('create_browser_notification')) {
                    create_browser_notification(
                        $conDB,
                        $nextApproverId,
                        'Loan Request for Approval',
                        "Loan request " . htmlspecialchars($inv_no) . " (modified by GM) is now pending your approval.",
                        'all_applied_loan.php?status=my_pending'
                    );
                }
            }
        }

        $conDB->commit();
        echo json_encode(['status' => 'success', 'title' => 'Approved!', 'message' => 'The loan has been modified and approved successfully.', 'type' => 'success']);

    } catch (Exception $e) {
        $conDB->rollback();
        echo json_encode(['status' => 'error', 'title' => 'Error', 'message' => $e->getMessage(), 'type' => 'error']);
    }
}

function modify_and_approve_loan_hr_assistant() {
    global $conDB, $pdo;
    if (session_status() == PHP_SESSION_NONE) session_start();
    $username = $_SESSION['auth_user']['user_id'] ?? null;
    if (empty($username)) {
        echo json_encode(['status' => 'error', 'title' => 'Authentication Error', 'message' => 'User session not found.', 'type' => 'error']);
        return;
    }
    
    // Get approver emp_id from session
    $approver_emp_id = $_SESSION['empid'] ?? null;
    if (!$approver_emp_id) {
        $stmt_user = $conDB->prepare("SELECT emp_id FROM admin_login WHERE id_iqama = ? LIMIT 1");
        $stmt_user->bind_param("s", $username);
        $stmt_user->execute();
        $res_user = $stmt_user->get_result();
        $user_row = $res_user->fetch_assoc();
        $stmt_user->close();
        $approver_emp_id = $user_row['emp_id'] ?? null;
    }

    if (!isset($_POST['loan_id'], $_POST['loan_amount'], $_POST['installments'])) {
        echo json_encode(['status' => 'error', 'title' => 'Input Error', 'message' => 'Missing required modification data.', 'type' => 'error']);
        return;
    }
    $loan_id = filter_var($_POST['loan_id'], FILTER_VALIDATE_INT);
    $new_loan_amount = filter_var($_POST['loan_amount'], FILTER_VALIDATE_FLOAT);
    $new_installments = filter_var($_POST['installments'], FILTER_VALIDATE_INT);
    $approval_comment = trim($_POST['approval_comment'] ?? '');
    $approval_comment = mb_substr($approval_comment, 0, 5000);

    if ($loan_id === false || $new_loan_amount === false || $new_loan_amount <= 0 || $new_installments === false || $new_installments <= 0) {
        echo json_encode(['status' => 'error', 'title' => 'Invalid Input', 'message' => 'Please provide a valid loan amount and number of installments.', 'type' => 'error']);
        return;
    }

    $conDB->begin_transaction();
    try {
        // Fetch loan and inv_no
        $stmt_loan = $conDB->prepare("SELECT inv_no, start_date FROM emp_loan WHERE id = ?");
        $stmt_loan->bind_param("i", $loan_id);
        $stmt_loan->execute();
        $result = $stmt_loan->get_result();
        $loan = $result->fetch_assoc();
        $stmt_loan->close();

        if (!$loan) {
            throw new Exception("Loan not found.");
        }
        
        $inv_no = $loan['inv_no'];
        $start_date = new DateTime($loan['start_date']);

        // Recalculate loan terms
        $new_total_payable = $new_loan_amount;
        $new_monthly_deduction = $new_total_payable / $new_installments;
        $new_end_date = clone $start_date;
        $new_end_date->modify('+' . ($new_installments - 1) . ' months');
        $new_end_date_str = $new_end_date->format('Y-m-d');

        // Update the loan record - keep original loan_amount, set approved_amount with modified value
        $stmt_update = $conDB->prepare("UPDATE `emp_loan` SET 
            `approved_amount` = ?,
            `approved_by_emp_id` = ?,
            `approved_at` = NOW(),
            `total_payable` = ?, 
            `monthly_deduction` = ?, 
            `end_date` = ?,
            `installments` = ?
            WHERE `id` = ?");
        $stmt_update->bind_param("dsddsii", $new_loan_amount, $approver_emp_id, $new_total_payable, $new_monthly_deduction, $new_end_date_str, $new_installments, $loan_id);
        $stmt_update->execute();

        if ($stmt_update->affected_rows === 0) {
            throw new Exception("Loan could not be updated.");
        }
        $stmt_update->close();

        // Use ApprovalChainManager to process approval
        $chainManager = new ApprovalChainManager($conDB, $pdo, new ActivityLogger());
        
        // Build approval comment with modification details
        $mod_note = "HR Assistant Modified and Approved: Amount changed to SAR " . number_format($new_loan_amount, 2) . ", Installments changed to " . $new_installments . " months.";
        if (!empty($approval_comment)) {
            $mod_note .= " - " . $approval_comment;
        }

        // Process approval through chain manager
        $approvalResult = $chainManager->processApproval($inv_no, $approver_emp_id, 'approve', $mod_note);

        if (!$approvalResult['success']) {
            throw new Exception($approvalResult['message']);
        }

        $isFinalApproval = $approvalResult['is_final'];
        $nextApprover = $approvalResult['next_approver'] ?? null;

        // Log the HR Assistant modification and approval
        ActivityLogger::logApproval('Loan', 'ajaxLoan.php', $loan_id, 'approved', "HR Assistant Modified and Approved: " . $mod_note, 'emp_loan');

        // Add status history
        $status_label = 'approved_hr_assistant_modification';
        $hist_stmt = $conDB->prepare("INSERT INTO smt_request_status (inv_no, emp_id, emp_name, note, status) VALUES (?, ?, 'System', ?, ?)");
        $hist_stmt->bind_param("siss", $inv_no, $approver_emp_id, $mod_note, $status_label);
        $hist_stmt->execute();
        $hist_stmt->close();

        // Send email to next approver if not final approval
        if (!$isFinalApproval && $nextApprover) {
            $nextApproverEmail = $nextApprover['email'] ?? null;
            $nextApproverName = $nextApprover['fullname'] ?? $nextApprover['name'] ?? 'Next Approver';
            $nextApproverId = $nextApprover['approver_id'] ?? $nextApprover['emp_id'] ?? null;

            // Fetch loan details for email
            $loan_details_stmt = $conDB->prepare("SELECT el.*, e.name as employee_name FROM emp_loan el LEFT JOIN employees e ON el.emp_id = e.emp_id WHERE el.inv_no = ? LIMIT 1");
            $loan_details_stmt->bind_param("s", $inv_no);
            $loan_details_stmt->execute();
            $loan_details = $loan_details_stmt->get_result()->fetch_assoc();
            $loan_details_stmt->close();

            if ($nextApproverEmail && $nextApproverId && $loan_details) {
                $emailData = [
                    'APPROVER_NAME' => $nextApproverName,
                    'REQUEST_ID' => $inv_no,
                    'EMPLOYEE_NAME' => $loan_details['employee_name'] ?? 'Employee',
                    'LOAN_TYPE' => str_replace('_', ' ', $loan_details['loan_type']),
                    'LOAN_AMOUNT' => number_format($loan_details['loan_amount'], 2),
                    'INSTALLMENTS' => $loan_details['installments'],
                    'REQUEST_URL' => 'https://hr.almutlaksystem.com/all_applied_loan.php?status=my_pending',
                    'EMAIL_MESSAGE' => 'A loan request has been modified by the HR Assistant and now requires your approval.'
                ];

                send_approval_email(
                    $conDB,
                    $nextApproverEmail,
                    $nextApproverName,
                    "Loan Request Pending Your Approval - Modified by HR Assistant (" . $inv_no . ")",
                    'loan_request',
                    $emailData
                );

                // Also send browser notification
                if (function_exists('create_browser_notification')) {
                    create_browser_notification(
                        $conDB,
                        $nextApproverId,
                        'Loan Request for Approval',
                        "Loan request " . htmlspecialchars($inv_no) . " (modified by HR Assistant) is now pending your approval.",
                        'all_applied_loan.php?status=my_pending'
                    );
                }
            }
        }

        $conDB->commit();
        echo json_encode(['status' => 'success', 'title' => 'Approved!', 'message' => 'The loan has been modified and approved successfully.', 'type' => 'success']);

    } catch (Exception $e) {
        $conDB->rollback();
        echo json_encode(['status' => 'error', 'title' => 'Error', 'message' => $e->getMessage(), 'type' => 'error']);
    }
}


function check_receipt_id() {
    global $conDB;
    if (!isset($_POST['receipt_id']) || empty(trim($_POST['receipt_id']))) {
        echo json_encode(['status' => 'success', 'exists' => false]);
        return;
    }

    $receipt_id = mysqli_real_escape_string($conDB, $_POST['receipt_id']);

    $stmt = $conDB->prepare("SELECT id FROM emp_loan_payments WHERE receipt_id = ?");
    $stmt->bind_param("s", $receipt_id);
    $stmt->execute();
    $result = $stmt->get_result();

    echo json_encode(['status' => 'success', 'exists' => ($result->num_rows > 0)]);
    $stmt->close();
}

function search_employee() {
    global $conDB;
    $searchTerm = $_POST['searchTerm'] ?? '';
    if (empty($searchTerm)) {
        echo json_encode(['status' => 'error', 'message' => 'Search term is empty.']);
        return;
    }
    
    // Add company filter based on user's access
    $company_filter = getCompanyFilterSQL('comp_no', false);
    $department_filter = getDepartmentFilterSQL('dept', false);
    
    $param = "%{$searchTerm}%";
    $sql = "SELECT `emp_id`, `name` FROM `employees` WHERE (`name` LIKE ? OR `emp_id` LIKE ?) AND `status`=1 {$company_filter}{$department_filter} LIMIT 10";
    $stmt = $conDB->prepare($sql);
    $stmt->bind_param("ss", $param, $param);
    $stmt->execute();
    $result = $stmt->get_result();
    $employees = [];
    while ($row = $result->fetch_assoc()) {
        $employees[] = $row;
    }
    $stmt->close();
    
    echo json_encode(['status' => 'success', 'employees' => $employees]);
}

function add_manual_loan_history() {
    global $conDB;
    if (session_status() == PHP_SESSION_NONE) session_start();
    $username = $_SESSION['auth_user']['user_id'] ?? null;
    if (empty($username)) {
        echo json_encode(['status' => 'error', 'title' => 'Authentication Error', 'message' => 'User session not found.', 'type' => 'error']);
        return;
    }

    $required_fields = ['emp_id', 'loan_type', 'loan_amount', 'total_payable', 'monthly_deduction', 'start_date', 'end_date', 'status'];
    foreach ($required_fields as $field) {
        if (!isset($_POST[$field]) || empty($_POST[$field])) {
            echo json_encode(['status' => 'error', 'title' => 'Input Error', 'message' => "Field '{$field}' is required.", 'type' => 'error']);
            return;
        }
    }

    $uploaded_files = [];
    $upload_dir = __DIR__ . '/../../assets/loan_receipts/';
    if (!is_dir($upload_dir)) {
        if (!mkdir($upload_dir, 0777, true)) {
            echo json_encode(['status' => 'error', 'title' => 'Server Error', 'message' => 'Failed to create upload directory.', 'type' => 'error']);
            return;
        }
    }

    $conDB->begin_transaction();

    try {
        $disbursement_attachment_filename = null;
        if (isset($_FILES['disbursement_attachment']) && $_FILES['disbursement_attachment']['error'] == UPLOAD_ERR_OK) {
            $file_ext = pathinfo($_FILES['disbursement_attachment']['name'], PATHINFO_EXTENSION);
            $disbursement_attachment_filename = 'disbursement_manual_' . time() . '_' . rand(1000, 9999) . '.' . $file_ext;
            $upload_file = $upload_dir . $disbursement_attachment_filename;
            if (move_uploaded_file($_FILES['disbursement_attachment']['tmp_name'], $upload_file)) {
                $uploaded_files[] = $upload_file;
            } else {
                throw new Exception('Failed to upload disbursement attachment.');
            }
        }

        // Generate unique invoice number
        $inv_no = generate_loan_inv_no($conDB);

        $stmt_loan = $conDB->prepare("INSERT INTO `emp_loan` (`inv_no`, `emp_id`, `submitted_by_emp_id`, `loan_type`, `loan_amount`, `interest_rate`, `total_payable`, `monthly_deduction`, `start_date`, `end_date`, `status`, `disbursement_receipt_id`, `disbursement_attachment`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        $interest_rate = 0.00;
        $disbursement_receipt_id = $_POST['disbursement_receipt_id'] ?? null;

        $submitted_by = isset($_SESSION['empid']) ? (int)$_SESSION['empid'] : null;
        $stmt_loan->bind_param(
            "ssisddddsssss",
            $inv_no,
            $_POST['emp_id'],
            $submitted_by,
            $_POST['loan_type'],
            $_POST['loan_amount'],
            $interest_rate,
            $_POST['total_payable'],
            $_POST['monthly_deduction'],
            $_POST['start_date'],
            $_POST['end_date'],
            $_POST['status'],
            $disbursement_receipt_id,
            $disbursement_attachment_filename
        );
        $stmt_loan->execute();
        $loan_id = $conDB->insert_id;
        if ($loan_id == 0) {
            throw new Exception("Failed to create the loan record: " . $stmt_loan->error);
        }
        $stmt_loan->close();

        if (isset($_POST['payment_amount']) && is_array($_POST['payment_amount'])) {
            $stmt_payment = $conDB->prepare("INSERT INTO `emp_loan_payments` (loan_id, payment_date, amount, payment_method, receipt_id, attachment) VALUES (?, ?, ?, ?, ?, ?)");
            
            foreach ($_POST['payment_amount'] as $i => $amount) {
                if (empty($amount) || $amount <= 0) continue;

                $payment_attachment_filename = null;
                if (isset($_FILES['payment_attachment']['name'][$i]) && $_FILES['payment_attachment']['error'][$i] == UPLOAD_ERR_OK) {
                     $file_ext = pathinfo($_FILES['payment_attachment']['name'][$i], PATHINFO_EXTENSION);
                     $payment_attachment_filename = 'payment_manual_' . $loan_id . '_' . time() . '_' . rand(1000, 9999) . '.' . $file_ext;
                     $upload_file = $upload_dir . $payment_attachment_filename;
                     if (move_uploaded_file($_FILES['payment_attachment']['tmp_name'][$i], $upload_file)) {
                         $uploaded_files[] = $upload_file;
                     } else {
                         throw new Exception("Failed to upload payment attachment for payment #".($i+1));
                     }
                }
                
                $receipt_id_pay = $_POST['receipt_id'][$i] ?? null;

                $stmt_payment->bind_param(
                    "isdsss",
                    $loan_id,
                    $_POST['payment_date'][$i],
                    $amount,
                    $_POST['payment_method'][$i],
                    $receipt_id_pay,
                    $payment_attachment_filename
                );
                $stmt_payment->execute();
                if ($stmt_payment->affected_rows == 0) {
                     throw new Exception("Failed to save payment #".($i+1).": " . $stmt_payment->error);
                }
            }
            $stmt_payment->close();
        }

        $conDB->commit();
        echo json_encode(['status' => 'success', 'title' => 'Success', 'message' => 'Manual loan history added successfully.', 'type' => 'success']);

    } catch (Exception $e) {
        $conDB->rollback();
        foreach($uploaded_files as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
        echo json_encode(['status' => 'error', 'title' => 'Error', 'message' => $e->getMessage(), 'type' => 'error']);
    }
}

function add_simplified_manual_loan() {
    global $conDB;
    if (session_status() == PHP_SESSION_NONE) session_start();
    $username = $_SESSION['auth_user']['user_id'] ?? null;
    if (empty($username)) {
        echo json_encode(['status' => 'error', 'title' => 'Authentication Error', 'message' => 'User session not found.', 'type' => 'error']);
        return;
    }

    $required_fields = ['emp_id', 'start_date', 'total_loan_amount', 'paid_amount'];
     foreach ($required_fields as $field) {
        if (!isset($_POST[$field])) {
            echo json_encode(['status' => 'error', 'title' => 'Input Error', 'message' => "Field '{$field}' is required.", 'type' => 'error']);
            return;
        }
    }

    $emp_id = $_POST['emp_id'];
    $start_date = $_POST['start_date'];
    $total_amount = filter_var($_POST['total_loan_amount'], FILTER_VALIDATE_FLOAT);
    $paid_amount = filter_var($_POST['paid_amount'], FILTER_VALIDATE_FLOAT);
    $payment_receipt_id = $_POST['payment_receipt_id'] ?? null;
    $payment_attachment_filename = null;
    $uploaded_file_path = null;


    if($total_amount === false || $paid_amount === false || $total_amount <= 0) {
         echo json_encode(['status' => 'error', 'title' => 'Invalid Input', 'message' => 'Please provide valid numbers for loan amounts.', 'type' => 'error']);
        return;
    }
    
    if($paid_amount > $total_amount) {
        echo json_encode(['status' => 'error', 'title' => 'Invalid Input', 'message' => 'Paid amount cannot be greater than the total loan amount.', 'type' => 'error']);
        return;
    }

    $conDB->begin_transaction();
    try {
        if (isset($_FILES['payment_attachment']) && $_FILES['payment_attachment']['error'] == UPLOAD_ERR_OK) {
            $upload_dir = __DIR__ . '/../../assets/loan_receipts/';
            if (!is_dir($upload_dir)) { 
                if(!mkdir($upload_dir, 0777, true)) {
                    throw new Exception('Failed to create upload directory.');
                }
            }

            $file_ext = pathinfo($_FILES['payment_attachment']['name'], PATHINFO_EXTENSION);
            $payment_attachment_filename = 'pmt_hist_' . time() . '_' . rand(1000, 9999) . '.' . $file_ext;
            $uploaded_file_path = $upload_dir . $payment_attachment_filename;

            if (!move_uploaded_file($_FILES['payment_attachment']['tmp_name'], $uploaded_file_path)) {
                throw new Exception('Failed to upload payment attachment.');
            }
        }

        $final_status = ($paid_amount >= $total_amount) ? 'paid' : 'approved';
        
        // Generate unique invoice number
        $inv_no = generate_loan_inv_no($conDB);
        
        $stmt_loan = $conDB->prepare("INSERT INTO `emp_loan` (`inv_no`, `emp_id`, `submitted_by_emp_id`, `loan_type`, `loan_amount`, `interest_rate`, `total_payable`, `monthly_deduction`, `start_date`, `end_date`, `status`) VALUES (?, ?, ?, 'regular', ?, 0.00, ?, ?, ?, ?, ?)");
        
        $submitted_by = isset($_SESSION['empid']) ? (int)$_SESSION['empid'] : null;
        $stmt_loan->bind_param(
            "ssidddsss",
            $inv_no,
            $emp_id,
            $submitted_by,
            $total_amount,
            $total_amount,
            $total_amount,
            $start_date,
            $start_date,
            $final_status
        );
        
        $stmt_loan->execute();
        $loan_id = $conDB->insert_id;
        if ($loan_id == 0) {
            throw new Exception("Failed to create the loan record: " . $stmt_loan->error);
        }
        $stmt_loan->close();

        if ($paid_amount > 0) {
            $stmt_payment = $conDB->prepare("INSERT INTO `emp_loan_payments` (loan_id, payment_date, amount, payment_method, receipt_id, attachment) VALUES (?, ?, ?, 'manual', ?, ?)");
            $stmt_payment->bind_param(
                "isdss",
                $loan_id,
                $start_date,
                $paid_amount,
                $payment_receipt_id,
                $payment_attachment_filename
            );
            $stmt_payment->execute();
             if ($stmt_payment->affected_rows == 0) {
                throw new Exception("Failed to save the payment record: " . $stmt_payment->error);
            }
            $stmt_payment->close();
        }

        $conDB->commit();
        echo json_encode(['status' => 'success', 'title' => 'Success', 'message' => 'Simplified manual loan added successfully.', 'type' => 'success']);

    } catch (Exception $e) {
        $conDB->rollback();
        if ($uploaded_file_path && file_exists($uploaded_file_path)) {
            unlink($uploaded_file_path);
        }
        echo json_encode(['status' => 'error', 'title' => 'Error', 'message' => $e->getMessage(), 'type' => 'error']);
    }
}

function check_loan_eligibility() {
    global $conDB;
    
    // Determine viewer context from session (to hide EOS details from employees)
    if (session_status() == PHP_SESSION_NONE) session_start();
    $logged_in_user_type = $_SESSION['user_type'] ?? null;
    $logged_in_emp_id = $_SESSION['empid'] ?? null;

    if (!isset($_POST['emp_id'], $_POST['loan_type'])) {
        echo json_encode(['status' => 'error', 'message' => 'Employee ID and loan type required.']);
        return;
    }

    $emp_id = mysqli_real_escape_string($conDB, $_POST['emp_id']);
    $loan_type = mysqli_real_escape_string($conDB, $_POST['loan_type']);

    // Get employee salary details
    $query = "SELECT e.joining_date, s.basic, s.housing, s.transport, s.food, s.misc, s.cashier, s.fuel, s.tel, s.other, s.guard,
              (s.basic + s.housing + s.transport + s.food + s.misc + s.cashier + s.fuel + s.tel + s.other + s.guard) as total_salary
              FROM employees e 
              JOIN emp_salary s ON e.emp_id = s.emp_id 
              WHERE e.emp_id = '$emp_id'";
    $result = mysqli_query($conDB, $query);
    
    if (!$row = mysqli_fetch_assoc($result)) {
        echo json_encode(['status' => 'error', 'message' => 'Cannot find employee details.']);
        return;
    }

    $total_salary = $row['total_salary'];
    $housing_allowance = $row['housing'];
    $joining_date = $row['joining_date'];
    $endOfServiceBenefit = calculateEndOfService($joining_date, $total_salary);

    // Decide whether to show full EOS details (hide when an employee is viewing their own data)
    $show_full_details = !($logged_in_user_type === 'employee' && $logged_in_emp_id === $emp_id);

    $eligibility = [
        'status' => 'success',
        'eligible' => true,
        'message' => '',
        'max_amount' => 0,
        'min_amount' => 0,
        'max_installments' => 0,
        // Only include EOS benefit figure when allowed to show full details
        'eos_benefit' => $show_full_details ? round($endOfServiceBenefit, 2) : null,
        'total_salary' => round($total_salary, 2),
        'housing_allowance' => round($housing_allowance, 2),
        'show_full_details' => $show_full_details
    ];

    if ($loan_type === 'end_of_service') {
        $maxAllowedEOS = $endOfServiceBenefit * 0.40;
        $eligibility['eligible'] = true;
        $eligibility['min_amount'] = 0; // No minimum specified in new requirement
        $eligibility['max_amount'] = round($maxAllowedEOS, 2);
        $eligibility['max_installments'] = 12;
        if ($show_full_details) {
            $eligibility['message_key'] = 'loan_eos_eligible_message_40pct';
            $eligibility['message_data'] = [
                'eos_total' => round($endOfServiceBenefit, 2),
                'max_40pct' => round($maxAllowedEOS, 2)
            ];
        } else {
            // Do not expose EOS figures in the message for employees
            unset($eligibility['message_key']);
            $eligibility['message'] = 'You are eligible for End of Service loan. You may apply within your allowed limit.';
        }

    } elseif ($loan_type === 'housing') {
        if ($housing_allowance <= 0) {
            $eligibility['eligible'] = false;
            $eligibility['message_key'] = 'loan_housing_no_allowance';
            $eligibility['message_data'] = [];
        } else {
            // Check last housing loan
            $check_last_loan = $conDB->prepare("SELECT id, start_date, status FROM emp_loan WHERE emp_id = ? AND loan_type = 'housing' ORDER BY start_date DESC LIMIT 1");
            $check_last_loan->bind_param("s", $emp_id);
            $check_last_loan->execute();
            $last_loan = $check_last_loan->get_result()->fetch_assoc();
            $check_last_loan->close();

            if ($last_loan) {
                $last_loan_date = new DateTime($last_loan['start_date']);
                $one_year_ago = (new DateTime())->modify('-1 year');
                
                if ($last_loan_date > $one_year_ago && $last_loan['status'] !== 'paid') {
                    $eligibility['eligible'] = false;
                    $eligibility['message_key'] = 'loan_housing_exists';
                    $eligibility['message_data'] = [
                        'date' => $last_loan['start_date']
                    ];
                }
            }

            if ($eligibility['eligible']) {
                $max_housing = min($housing_allowance * 6, 20000, $endOfServiceBenefit);
                $eligibility['max_amount'] = $max_housing;
                $eligibility['min_amount'] = 0;
                $eligibility['max_installments'] = 6;
                $eligibility['message_key'] = 'loan_housing_eligible_message';
                $eligibility['message_data'] = [
                    'max' => round($max_housing, 2)
                ];
            }
        }

    } elseif ($loan_type === 'advance_salary') {
        $max_advance = $total_salary * 0.5;
        $eligibility['eligible'] = true;
        $eligibility['max_amount'] = $max_advance;
        $eligibility['min_amount'] = 0;
        $eligibility['max_installments'] = 1;
        $eligibility['message_key'] = 'loan_advance_eligible_message';
        $eligibility['message_data'] = [
            'max' => round($max_advance, 2)
        ];

    } else {
        $eligibility['status'] = 'error';
        $eligibility['eligible'] = false;
        $eligibility['message_key'] = 'loan_invalid_type';
        $eligibility['message_data'] = [];
    }

    echo json_encode($eligibility);
}
// Helper to get loan approvers chain
function get_loan_approvers($emp_id) {
    global $conDB;
    $approvers = [];
    $level = 1;
    
    // Level 1: Get employee's direct supervisor or department manager
    $stmt = $conDB->prepare("SELECT supervisor_id, dept FROM employees WHERE emp_id = ? LIMIT 1");
    $stmt->bind_param("s", $emp_id);
    $stmt->execute();
    $emp_data = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if (!$emp_data) {
        return []; // Employee not found
    }
    
    $supervisor_id = $emp_data['supervisor_id'];
    $dept = $emp_data['dept'];
    
    // If employee has a supervisor, add them as level 1
    if (!empty($supervisor_id)) {
        $approvers[$level] = $supervisor_id;
        $level++;
    } else {
        // No supervisor: get department manager
        $stmt = $conDB->prepare("SELECT emp_id FROM employees WHERE dept = ? AND emptype = 'Manager' AND status = 1 LIMIT 1");
        $stmt->bind_param("s", $dept);
        $stmt->execute();
        $manager = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($manager) {
            $approvers[$level] = $manager['emp_id'];
            $level++;
        }
    }
    
    // Level 2: HR Payroll (user_type = 'hr_payroll')
    $stmt = $conDB->prepare("SELECT emp_id FROM admin_login WHERE user_type = 'hr_payroll' AND emp_id IS NOT NULL AND status = 1 LIMIT 1");
    $stmt->execute();
    $hr_payroll = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($hr_payroll && !empty($hr_payroll['emp_id'])) {
        $approvers[$level] = $hr_payroll['emp_id'];
        $level++;
    }
    
    // Level 3: HR Manager (user_type = 'hr_supervisor' in your system)
    $stmt = $conDB->prepare("SELECT emp_id FROM admin_login WHERE user_type = 'hr_supervisor' AND emp_id IS NOT NULL AND status = 1 LIMIT 1");
    $stmt->execute();
    $hr_manager = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($hr_manager && !empty($hr_manager['emp_id'])) {
        $approvers[$level] = $hr_manager['emp_id'];
        $level++;
    }
    
    // Level 4: Audit (user_type = 'auditor' - if exists)
    // Note: Currently no auditor in your system, but keeping for future
    $stmt = $conDB->prepare("SELECT emp_id FROM admin_login WHERE user_type = 'auditor' AND emp_id IS NOT NULL AND status = 1 LIMIT 1");
    $stmt->execute();
    $audit = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($audit && !empty($audit['emp_id'])) {
        $approvers[$level] = $audit['emp_id'];
        $level++;
    }
    
    // Level 5: GM (user_type = 'gm')
    $stmt = $conDB->prepare("SELECT emp_id FROM admin_login WHERE user_type = 'gm' AND emp_id IS NOT NULL AND status = 1 LIMIT 1");
    $stmt->execute();
    $gm = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($gm && !empty($gm['emp_id'])) {
        $approvers[$level] = $gm['emp_id'];
        $level++;
    }
    
    // Level 6: Finance Manager (user_type = 'finance' - emp_id 4120, Gamal)
    $stmt = $conDB->prepare("SELECT emp_id FROM admin_login WHERE user_type = 'finance' AND emp_id IS NOT NULL AND status = 1 LIMIT 1");
    $stmt->execute();
    $finance_mgr = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($finance_mgr && !empty($finance_mgr['emp_id'])) {
        $approvers[$level] = $finance_mgr['emp_id'];
        $level++;
    }
    
    // Level 7: Payer (Finance Officer - user_type = 'finance_officer', emp_id 3061)
    $stmt = $conDB->prepare("SELECT emp_id FROM admin_login WHERE user_type = 'finance_officer' AND emp_id IS NOT NULL AND status = 1 LIMIT 1");
    $stmt->execute();
    $payer = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($payer && !empty($payer['emp_id'])) {
        $approvers[$level] = $payer['emp_id'];
        $level++;
    }
    
    return $approvers;
}

/**
 * Build dynamic loan approval flow.
 * Default order keys (edit $LOAN_APPROVAL_FLOW to change):
 * 1. employee (submitter, stored as 'submitted')
 * 2. direct_manager (supervisor_id from employees or dept manager fallback)
 * 3. hr_payroll (admin_login.user_type = 'hr_payroll')
 * 4. hr_manager (admin_login.user_type = 'hr_supervisor')
 * 5. gm (admin_login.user_type = 'gm')
 * 6. finance_manager (admin_login.user_type = 'finance')
 * 7. finance_officer (admin_login.user_type = 'finance_officer')
 * Extendable: pass a $flow array of step keys or modify $LOAN_APPROVAL_FLOW global.
 * Returns array: [level => approver_emp_id]
 */
function get_dynamic_loan_approvers($conDB, $emp_id, $flow = null) {
    // Editable global sequence
    static $LOAN_APPROVAL_FLOW = [
        'employee',
        'direct_manager',
        'hr_payroll',
        'hr_manager',
        'auditor',
        'gm',
        'finance_manager',
        'finance_officer'
    ];
    if ($flow === null || !is_array($flow) || empty($flow)) {
        $flow = $LOAN_APPROVAL_FLOW;
    }

    $resolved = [];
    $added = [];
    $level = 1;

    // Pre-fetch employee data for manager lookup
    $emp_stmt = $conDB->prepare("SELECT supervisor_id, dept FROM employees WHERE emp_id = ? LIMIT 1");
    $emp_stmt->bind_param('s', $emp_id);
    $emp_stmt->execute();
    $emp_row = $emp_stmt->get_result()->fetch_assoc();
    $emp_stmt->close();
    $supervisor_id = $emp_row['supervisor_id'] ?? null;
    $dept_id = $emp_row['dept'] ?? null;

    foreach ($flow as $step) {
        $approver_emp_id = null;
        switch ($step) {
            case 'employee':
                $approver_emp_id = $emp_id; // submitter
                break;
            case 'direct_manager':
                if (!empty($supervisor_id)) {
                    $approver_emp_id = $supervisor_id;
                } elseif (!empty($dept_id)) {
                    $stmt = $conDB->prepare("SELECT emp_id FROM employees WHERE dept = ? AND emptype = 'Manager' AND status = 1 LIMIT 1");
                    $stmt->bind_param('s', $dept_id);
                    $stmt->execute();
                    $mgr = $stmt->get_result()->fetch_assoc();
                    $stmt->close();
                    if ($mgr) $approver_emp_id = $mgr['emp_id'];
                }
                break;
            case 'hr_payroll':
                $approver_emp_id = fetch_user_type_emp($conDB, 'hr_payroll');
                break;
            case 'hr_manager':
                $approver_emp_id = fetch_user_type_emp($conDB, 'hr_supervisor');
                break;
            case 'audit':
                $approver_emp_id = fetch_user_type_emp($conDB, 'auditor');
                break;
            case 'gm':
                $approver_emp_id = fetch_user_type_emp($conDB, 'gm');
                break;
            case 'finance_manager':
                $approver_emp_id = fetch_user_type_emp($conDB, 'finance');
                break;
            case 'finance_officer':
                $approver_emp_id = fetch_user_type_emp($conDB, 'finance_officer');
                break;
            default:
                // Unknown key: attempt generic lookup treating step as user_type
                $approver_emp_id = fetch_user_type_emp($conDB, $step);
        }
        if ($approver_emp_id && !in_array($approver_emp_id, $added, true)) {
            $resolved[$level] = (int)$approver_emp_id;
            $added[] = (int)$approver_emp_id;
            $level++;
        }
    }
    return $resolved;
}

// Helper to resolve admin_login.user_type to employee id
function fetch_user_type_emp($conDB, $user_type) {
    $stmt = $conDB->prepare("SELECT emp_id FROM admin_login WHERE user_type = ? AND emp_id IS NOT NULL AND status = 1 LIMIT 1");
    $stmt->bind_param('s', $user_type);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row['emp_id'] ?? null;
}

/**
 * Integrate approved loan into payroll system
 * Creates appropriate deduction entries based on loan type
 * 
 * @param int $loan_id The loan ID
 * @param mysqli $conDB Database connection
 * @return array ['success' => bool, 'message' => string]
 */
function integrate_loan_to_payroll($loan_id, $conDB) {
    try {
        // Get loan details
        $stmt = $conDB->prepare("SELECT * FROM emp_loan WHERE id = ? LIMIT 1");
        $stmt->bind_param("i", $loan_id);
        $stmt->execute();
        $loan = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if (!$loan) {
            return ['success' => false, 'message' => 'Loan not found'];
        }
        
        // Check deduction mode - only proceed with auto-deduction if mode is 'automatic'
        if (isset($loan['deduction_mode']) && $loan['deduction_mode'] === 'manual') {
            return ['success' => true, 'message' => 'Loan set to manual deduction mode - no auto payroll entries created'];
        }
        
        $emp_id = $loan['emp_id'];
        $loan_type = $loan['loan_type'];
        // Use approved_amount if set, otherwise use loan_amount
        $final_amount = $loan['approved_amount'] ?? $loan['loan_amount'];
        $monthly_deduction = $loan['monthly_deduction'];
        $installments = $loan['installments'] ?? 1;
        $start_date = new DateTime($loan['start_date']);
        $current_month = $start_date->format('Y-m');
        
        // Get employee salary details for housing allowance
        $stmt_salary = $conDB->prepare("SELECT housing FROM emp_salary WHERE emp_id = ? AND status = 1 LIMIT 1");
        $stmt_salary->bind_param("s", $emp_id);
        $stmt_salary->execute();
        $salary_data = $stmt_salary->get_result()->fetch_assoc();
        $stmt_salary->close();
        $housing_allowance = $salary_data['housing'] ?? 0;
        
        switch ($loan_type) {
            case 'end_of_service':
                // End of Service Loan: Add as monthly installment deduction
                return add_monthly_installment_deduction(
                    $conDB, 
                    $emp_id, 
                    $loan['inv_no'], 
                    $monthly_deduction, 
                    $installments, 
                    $start_date,
                    'End of Service Loan'
                );
                
            case 'housing':
                // Housing Loan: Add as monthly deduction from housing allowance
                if ($housing_allowance <= 0) {
                    return ['success' => false, 'message' => 'Employee has no housing allowance'];
                }
                
                return add_monthly_installment_deduction(
                    $conDB, 
                    $emp_id, 
                    $loan['inv_no'], 
                    $monthly_deduction, 
                    $installments, 
                    $start_date,
                    'Housing Loan'
                );
                
            case 'advance_salary':
                // Advance Salary: Add as one-time deduction for current month
                $deduction_amount = $final_amount;
                $note = number_format($deduction_amount, 2, '.', '');
                
                $stmt_insert = $conDB->prepare(
                    "INSERT INTO payroll_deductions (emp_id, deduction, note, month, status) 
                     VALUES (?, ?, ?, ?, 1)"
                );
                $deduction_name = 'Advance Salary Deduction - ' . $loan['inv_no'];
                $stmt_insert->bind_param("ssss", $emp_id, $deduction_name, $note, $current_month);
                
                if ($stmt_insert->execute()) {
                    $stmt_insert->close();
                    return ['success' => true, 'message' => 'One-time deduction added to payroll'];
                } else {
                    $error = $stmt_insert->error;
                    $stmt_insert->close();
                    return ['success' => false, 'message' => 'Failed to add deduction: ' . $error];
                }
                
            default:
                return ['success' => false, 'message' => 'Unknown loan type: ' . $loan_type];
        }
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Exception: ' . $e->getMessage()];
    }
}

/**
 * Add monthly installment deductions to payroll
 * 
 * @param mysqli $conDB Database connection
 * @param string $emp_id Employee ID
 * @param string $inv_no Loan invoice number
 * @param float $monthly_amount Monthly deduction amount
 * @param int $installments Number of installments
 * @param DateTime $start_date Start date
 * @param string $deduction_label Deduction label
 * @return array ['success' => bool, 'message' => string]
 */
function add_monthly_installment_deduction($conDB, $emp_id, $inv_no, $monthly_amount, $installments, $start_date, $deduction_label) {
    try {
        $added_count = 0;
        $deduction_name = $deduction_label . ' - ' . $inv_no;
        
        // Add deduction for each month
        for ($i = 0; $i < $installments; $i++) {
            $month_date = clone $start_date;
            $month_date->modify("+{$i} months");
            $month_year = $month_date->format('Y-m');
            
            // Check if deduction already exists for this month
            $check_stmt = $conDB->prepare(
                "SELECT id FROM payroll_deductions 
                 WHERE emp_id = ? AND month = ? AND deduction = ? LIMIT 1"
            );
            $check_stmt->bind_param("sss", $emp_id, $month_year, $deduction_name);
            $check_stmt->execute();
            $exists = $check_stmt->get_result()->fetch_assoc();
            $check_stmt->close();
            
            if ($exists) {
                continue; // Skip if already exists
            }
            
            // Insert new deduction
            $note = number_format($monthly_amount, 2, '.', '');
            $stmt_insert = $conDB->prepare(
                "INSERT INTO payroll_deductions (emp_id, deduction, note, month, status) 
                 VALUES (?, ?, ?, ?, 1)"
            );
            $stmt_insert->bind_param("ssss", $emp_id, $deduction_name, $note, $month_year);
            
            if ($stmt_insert->execute()) {
                $added_count++;
            }
            $stmt_insert->close();
        }
        
        return [
            'success' => true, 
            'message' => "Added {$added_count} monthly installment deductions to payroll"
        ];
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Exception in installments: ' . $e->getMessage()];
    }
}

/**
 * Record payroll-applied loan deductions as payments in emp_loan_payments for a given month.
 * Call this during payroll generation after deductions are applied.
 *
 * @param mysqli $conDB Database connection
 * @param string $month Month string in 'Y-m' format (e.g., '2025-12')
 * @return array ['success' => bool, 'message' => string, 'count' => int]
 */
function record_monthly_loan_payments($conDB, $month) {
    try {
        // Find all payroll deductions for the given month that correspond to loans
        // Expected deduction formats:
        //  - "End of Service Loan - <INV_NO>"
        //  - "Housing Loan - <INV_NO>"
        //  - "Advance Salary Deduction - <INV_NO>"
        $like_loan = '%LN-%';
        $like_adv  = 'Advance Salary Deduction - %';

        $stmt = $conDB->prepare(
            "SELECT id, emp_id, deduction, note FROM payroll_deductions 
             WHERE month = ? AND status = 1 AND (deduction LIKE ? OR deduction LIKE ?)"
        );
        $stmt->bind_param("sss", $month, $like_loan, $like_adv);
        $stmt->execute();
        $result = $stmt->get_result();

        $count = 0;
        $payment_date = $month . '-01';

        while ($row = $result->fetch_assoc()) {
            $emp_id = $row['emp_id'];
            $deduction = $row['deduction'];
            $amount_str = $row['note'];
            $amount = floatval($amount_str);

            // Extract inv_no: split by ' - ' and take last segment
            $parts = explode(' - ', $deduction);
            $inv_no = trim(end($parts));
            if (empty($inv_no)) {
                // Could not parse an LN- invoice number; skip
                continue;
            }

            // Resolve loan_id by inv_no
            $stmt_loan = $conDB->prepare("SELECT id FROM emp_loan WHERE inv_no = ? AND emp_id = ? LIMIT 1");
            $stmt_loan->bind_param("ss", $inv_no, $emp_id);
            $stmt_loan->execute();
            $loan_row = $stmt_loan->get_result()->fetch_assoc();
            $stmt_loan->close();

            if (!$loan_row) {
                // No matching loan; skip
                continue;
            }

            $loan_id = intval($loan_row['id']);

            // Check if a payroll payment for this loan and month already exists to avoid duplicates
            $stmt_exists = $conDB->prepare(
                "SELECT id FROM emp_loan_payments WHERE loan_id = ? AND payment_method = 'payroll' AND DATE_FORMAT(payment_date, '%Y-%m') = ? LIMIT 1"
            );
            $stmt_exists->bind_param("is", $loan_id, $month);
            $stmt_exists->execute();
            $exists = $stmt_exists->get_result()->fetch_assoc();
            $stmt_exists->close();

            if ($exists) {
                continue; // Already recorded
            }

            // Insert payment record
            $stmt_pay = $conDB->prepare(
                "INSERT INTO emp_loan_payments (loan_id, payment_date, amount, receipt_id, attachment, payment_method) 
                 VALUES (?, ?, ?, NULL, NULL, 'payroll')"
            );
            $stmt_pay->bind_param("isd", $loan_id, $payment_date, $amount);
            if ($stmt_pay->execute()) {
                $count++;
            }
            $stmt_pay->close();
        }

        $stmt->close();

        return [
            'success' => true,
            'message' => "Recorded {$count} payroll loan payments for {$month}",
            'count' => $count
        ];
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Exception while recording payroll loan payments: ' . $e->getMessage(),
            'count' => 0
        ];
    }
}

// Lightweight AJAX handler to record payroll loan payments for a given month
if (isset($_POST['ajaxType']) && $_POST['ajaxType'] === 'recordPayrollLoanPayments') {
    $month = $_POST['month'] ?? date('Y-m');
    $month = preg_match('/^\d{4}-\d{2}$/', $month) ? $month : date('Y-m');
    $result = record_monthly_loan_payments($conDB, $month);
    echo json_encode([
        'status' => $result['success'] ? 'success' : 'error',
        'title' => $result['success'] ? 'Recorded' : 'Error',
        'message' => $result['message'],
        'count' => $result['count']
    ]);
    // Prevent falling through to other handlers in this file
    if (isset($conDB)) { $conDB->close(); }
    return;
}

if (isset($conDB)) {
    $conDB->close();
}

/**
 * Update loan installments and monthly deduction
 * Called from the Edit Installments Plan modal in view_employee.php
 */
function updateLoanInstallments() {
    global $conDB;
    
    // Validate input
    if (!isset($_POST['loan_id'], $_POST['installments'], $_POST['monthly_deduction'])) {
        echo json_encode([
            'status' => 400,
            'message' => 'Missing required parameters'
        ]);
        return;
    }
    
    $loan_id = filter_var($_POST['loan_id'], FILTER_VALIDATE_INT);
    $installments = filter_var($_POST['installments'], FILTER_VALIDATE_INT);
    $monthly_deduction = filter_var($_POST['monthly_deduction'], FILTER_VALIDATE_FLOAT);
    
    // Validate loan_id
    if ($loan_id === false || $loan_id <= 0) {
        echo json_encode([
            'status' => 400,
            'message' => 'Invalid loan ID'
        ]);
        return;
    }
    
    // Validate installments: must be between 1 and 60
    if ($installments === false || $installments < 1 || $installments > 60) {
        echo json_encode([
            'status' => 400,
            'message' => 'Installments must be between 1 and 60'
        ]);
        return;
    }
    
    // Validate monthly_deduction
    if ($monthly_deduction === false || $monthly_deduction < 0) {
        echo json_encode([
            'status' => 400,
            'message' => 'Invalid monthly deduction amount'
        ]);
        return;
    }
    
    // Check if loan exists
    $checkQuery = "SELECT id FROM emp_loan WHERE id = ?";
    $checkStmt = $conDB->prepare($checkQuery);
    
    if (!$checkStmt) {
        echo json_encode([
            'status' => 400,
            'message' => 'Database error: ' . $conDB->error
        ]);
        return;
    }
    
    $checkStmt->bind_param("i", $loan_id);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows === 0) {
        echo json_encode([
            'status' => 400,
            'message' => 'Loan not found'
        ]);
        $checkStmt->close();
        return;
    }
    
    $checkStmt->close();
    
    // Update the loan record with new installments and monthly deduction
    $updateQuery = "UPDATE emp_loan SET installments = ?, monthly_deduction = ? WHERE id = ?";
    $updateStmt = $conDB->prepare($updateQuery);
    
    if (!$updateStmt) {
        echo json_encode([
            'status' => 400,
            'message' => 'Database error: ' . $conDB->error
        ]);
        return;
    }
    
    $updateStmt->bind_param("idi", $installments, $monthly_deduction, $loan_id);
    
    if ($updateStmt->execute()) {
        echo json_encode([
            'status' => 200,
            'message' => 'Installments plan updated successfully'
        ]);
    } else {
        echo json_encode([
            'status' => 400,
            'message' => 'Failed to update installments: ' . $updateStmt->error
        ]);
    }
    
    $updateStmt->close();
}

/**
 * Update loan deduction mode (automatic or manual)
 * Called when user chooses how to handle payroll deductions
 */
function updateDeductionMode() {
    global $conDB;
    
    if (!isset($_POST['loan_id'], $_POST['deduction_mode'])) {
        echo json_encode([
            'status' => 400,
            'message' => 'Missing required parameters'
        ]);
        return;
    }
    
    $loan_id = filter_var($_POST['loan_id'], FILTER_VALIDATE_INT);
    $deduction_mode = in_array($_POST['deduction_mode'], ['automatic', 'manual']) ? $_POST['deduction_mode'] : 'automatic';
    
    if ($loan_id === false || $loan_id <= 0) {
        echo json_encode([
            'status' => 400,
            'message' => 'Invalid loan ID'
        ]);
        return;
    }
    
    // Check if loan exists
    $checkQuery = "SELECT id, emp_id, status, inv_no FROM emp_loan WHERE id = ?";
    $checkStmt = $conDB->prepare($checkQuery);
    
    if (!$checkStmt) {
        echo json_encode([
            'status' => 400,
            'message' => 'Database error: ' . $conDB->error
        ]);
        return;
    }
    
    $checkStmt->bind_param("i", $loan_id);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows === 0) {
        echo json_encode([
            'status' => 400,
            'message' => 'Loan not found'
        ]);
        $checkStmt->close();
        return;
    }
    
    $loan = $checkResult->fetch_assoc();
    $checkStmt->close();
    
    $conDB->begin_transaction();
    
    try {
        // Update deduction mode
        $updateQuery = "UPDATE emp_loan SET deduction_mode = ? WHERE id = ?";
        $updateStmt = $conDB->prepare($updateQuery);
        
        if (!$updateStmt) {
            throw new Exception("Database error: " . $conDB->error);
        }
        
        $updateStmt->bind_param("si", $deduction_mode, $loan_id);
        
        if (!$updateStmt->execute()) {
            throw new Exception("Failed to update deduction mode: " . $updateStmt->error);
        }
        $updateStmt->close();
        
        // If switching to manual mode, delete existing payroll entries for this loan
        if ($deduction_mode === 'manual') {
            $pattern = "%{$loan['inv_no']}%";
            $deleteQuery = "DELETE FROM payroll_deductions WHERE emp_id = ? AND deduction LIKE ?";
            $deleteStmt = $conDB->prepare($deleteQuery);
            
            if (!$deleteStmt) {
                throw new Exception("Failed to prepare delete query: " . $conDB->error);
            }
            
            $deleteStmt->bind_param("ss", $loan['emp_id'], $pattern);
            if (!$deleteStmt->execute()) {
                throw new Exception("Failed to delete payroll entries: " . $deleteStmt->error);
            }
            $deleteStmt->close();
        }
        
        $conDB->commit();
        echo json_encode([
            'status' => 200,
            'message' => 'Deduction mode updated to ' . ucfirst($deduction_mode) . ($deduction_mode === 'manual' ? ' - existing payroll entries removed' : ''),
            'deduction_mode' => $deduction_mode
        ]);
        
    } catch (Exception $e) {
        $conDB->rollback();
        echo json_encode([
            'status' => 400,
            'message' => 'Error: ' . $e->getMessage()
        ]);
    }
}

/**
 * Purge and regenerate payroll deductions for a loan
 * Called when loan is modified and payroll needs to be recalculated
 */
function purgeAndRegenerateLoanDeductions() {
    global $conDB;
    
    if (!isset($_POST['loan_id'])) {
        echo json_encode([
            'status' => 400,
            'message' => 'Missing loan ID'
        ]);
        return;
    }
    
    $loan_id = filter_var($_POST['loan_id'], FILTER_VALIDATE_INT);
    
    if ($loan_id === false || $loan_id <= 0) {
        echo json_encode([
            'status' => 400,
            'message' => 'Invalid loan ID'
        ]);
        return;
    }
    
    // Get loan details
    $loanQuery = "SELECT emp_id, inv_no, status, deduction_mode FROM emp_loan WHERE id = ?";
    $loanStmt = $conDB->prepare($loanQuery);
    
    if (!$loanStmt) {
        echo json_encode([
            'status' => 400,
            'message' => 'Database error: ' . $conDB->error
        ]);
        return;
    }
    
    $loanStmt->bind_param("i", $loan_id);
    $loanStmt->execute();
    $loan = $loanStmt->get_result()->fetch_assoc();
    $loanStmt->close();
    
    if (!$loan) {
        echo json_encode([
            'status' => 400,
            'message' => 'Loan not found'
        ]);
        return;
    }
    
    $conDB->begin_transaction();
    
    try {
        // Only purge if mode is 'automatic' - manual deductions are user-managed
        if ($loan['deduction_mode'] === 'automatic') {
            // Find and delete all payroll deductions tied to this loan
            $pattern = "%{$loan['inv_no']}%";
            $deleteQuery = "DELETE FROM payroll_deductions WHERE emp_id = ? AND deduction LIKE ?";
            $deleteStmt = $conDB->prepare($deleteQuery);
            
            if (!$deleteStmt) {
                throw new Exception("Failed to prepare delete query: " . $conDB->error);
            }
            
            $deleteStmt->bind_param("ss", $loan['emp_id'], $pattern);
            if (!$deleteStmt->execute()) {
                throw new Exception("Failed to delete deductions: " . $deleteStmt->error);
            }
            $deleteStmt->close();
            
            // Regenerate deductions by calling integrate_loan_to_payroll
            if (function_exists('integrate_loan_to_payroll')) {
                $result = integrate_loan_to_payroll($loan_id, $conDB);
                if (!$result['success']) {
                    throw new Exception($result['message']);
                }
            }
        }
        
        $conDB->commit();
        echo json_encode([
            'status' => 200,
            'message' => ($loan['deduction_mode'] === 'automatic' ? 'Deductions purged and regenerated' : 'Manual mode: deductions not auto-modified')
        ]);
        
    } catch (Exception $e) {
        $conDB->rollback();
        echo json_encode([
            'status' => 400,
            'message' => 'Error: ' . $e->getMessage()
        ]);
    }
}

?>
