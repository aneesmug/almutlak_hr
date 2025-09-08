<?php
/*******************************************************************************************************************
 * MODIFICATION SUMMARY (018-ajaxLoan.php):
 * 1.  CORRECTED `finalize_loan` LOGIC: This function has been corrected to handle loan disbursement properly.
 * 2.  STORES DISBURSEMENT PROOF: It now updates the main `emp_loan` record with the `disbursement_receipt_id` and `disbursement_attachment` provided by the Finance Assistant. This serves as proof of payment *to* the employee.
 * 3.  NO LONGER CREATES REPAYMENT RECORD: The function has been fixed and **no longer** incorrectly inserts a record into the `emp_loan_payments` table during finalization. That table is only for employee repayments.
 * 4.  ACCURATE STATUS UPDATE: The loan status is correctly set to 'approved', signifying it is now an active loan ready for repayment.
 * 5.  REVISED APPROVAL CHAIN: The `approve_loan` function follows the specified workflow:
 * - Department Manager -> HR Assistant -> HR Manager -> Finance Manager -> GM -> Finance Assistant
 * 6.  UPDATED `get_loan_details`: This function now checks the logged-in user's session. It hides "Total Calculated" details only when a user with the 'employee' role is viewing their own loan application. Managers and Administrators will see the full details.
 *******************************************************************************************************************/

require_once __DIR__ . '/../../includes/db.php';

header('Content-Type: application/json');

if (isset($_POST['ajaxType'])) {
    $ajaxType = $_POST['ajaxType'];

    switch ($ajaxType) {
        case 'get_loan_details':
            get_loan_details();
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
            `finance_assistant_status` = 'processed',
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
    global $conDB;
    if (session_status() == PHP_SESSION_NONE) session_start();
    $username = $_SESSION['auth_user']['user_id'] ?? null;
    if (empty($username)) {
        echo json_encode(['status' => 'error', 'title' => 'Authentication Error', 'message' => 'User session not found. Please log in again.', 'type' => 'error']);
        return;
    }
    $approver_id = $username;
    if (!isset($_POST['loan_id'], $_POST['approver_role'])) {
        echo json_encode(['status' => 'error', 'title' => 'Input Error', 'message' => 'Missing required approval data.', 'type' => 'error']);
        return;
    }
    $loan_id = filter_var($_POST['loan_id'], FILTER_VALIDATE_INT);
    $approver_role = mysqli_real_escape_string($conDB, $_POST['approver_role']);
    if ($loan_id === false) {
        echo json_encode(['status' => 'error', 'title' => 'Input Error', 'message' => 'Invalid Loan ID.', 'type' => 'error']);
        return;
    }

    // New approval chain
    $approval_stages = [
        'dept_manager' => ['current_status_col' => 'dept_manager_status', 'next_status' => 'hr_assistant_pending'],
        'hr_assistant' => ['current_status_col' => 'hr_assistant_status', 'next_status' => 'hr_manager_pending'],
        'hr_manager' => ['current_status_col' => 'hr_manager_status', 'next_status' => 'finance_manager_pending'],
        'finance_manager' => ['current_status_col' => 'finance_manager_status', 'next_status' => 'gm_pending'],
        'gm' => ['current_status_col' => 'gm_status', 'next_status' => 'finance_assistant_pending'],
    ];

    if (!array_key_exists($approver_role, $approval_stages)) {
        echo json_encode(['status' => 'error', 'title' => 'Invalid Role', 'message' => 'Invalid approver role specified.', 'type' => 'error']);
        return;
    }

    $stage = $approval_stages[$approver_role];
    $status_column = $stage['current_status_col'];
    $next_status = $stage['next_status'];

    $stmt = $conDB->prepare("UPDATE `emp_loan` SET `status` = ?, `$status_column` = 'approved' WHERE `id` = ?");
    $stmt->bind_param("si", $next_status, $loan_id);

    if ($stmt->execute()) {
        $stmt_approval = $conDB->prepare("INSERT INTO `emp_loan_approvals` (loan_id, approver_id, approver_role, status, notes) VALUES (?, ?, ?, ?, ?)");
        $status = 'approved';
        $notes = ucfirst(str_replace('_', ' ', $approver_role)) . ' approved.';
        $stmt_approval->bind_param("issss", $loan_id, $approver_id, $approver_role, $status, $notes);
        $stmt_approval->execute();
        $stmt_approval->close();
        echo json_encode(['status' => 'success', 'title' => 'Approved!', 'message' => 'The loan request has been approved and moved to the next stage.', 'type' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'title' => 'Database Error', 'message' => 'Failed to approve the loan: ' . $stmt->error, 'type' => 'error']);
    }
    $stmt->close();
}

// --- Other functions remain unchanged ---

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
                (s.basic + s.housing + s.transport + s.food + s.misc + s.cashier + s.fuel + s.tel + s.other + s.guard) as total_salary
              FROM employees e 
              JOIN emp_salary s ON e.emp_id = s.emp_id 
              WHERE e.emp_id = '$emp_id_for_loan'";
              
    $result = mysqli_query($conDB, $query);
    if ($row = mysqli_fetch_assoc($result)) {
        $endOfServiceBenefit = calculateEndOfService($row['joining_date'], $row['total_salary']);
        $maxLoanAmount = $endOfServiceBenefit * 0.40;
        
        echo json_encode([
            'status' => 'success',
            'end_of_service' => round($endOfServiceBenefit, 2),
            'max_loan_amount' => round($maxLoanAmount, 2),
            'show_full_details' => $show_full_details
        ]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Could not find employee details.']);
    }
}

function apply_for_loan() {
    global $conDB;
    // Check for required fields
    if (!isset($_POST['emp_id'], $_POST['loan_amount'], $_POST['start_date'], $_POST['installments'])) {
        echo json_encode(['status' => 'error','title' => 'Input Error','message' => 'Missing required fields.','type' => 'error']);
        return;
    }

    // Sanitize and validate inputs
    $emp_id = mysqli_real_escape_string($conDB, $_POST['emp_id']);
    $loan_amount = filter_var($_POST['loan_amount'], FILTER_VALIDATE_FLOAT);
    $installments = filter_var($_POST['installments'], FILTER_VALIDATE_INT);
    $start_date_str = $_POST['start_date'];
    $loan_type = isset($_POST['loan_type']) && $_POST['loan_type'] === 'emergency' ? 'emergency' : 'regular'; // Check for loan type

    $start_date = DateTime::createFromFormat('Y-m-d', $start_date_str);
    if (!$start_date || $start_date->format('Y-m-d') !== $start_date_str) {
        echo json_encode(['status' => 'error', 'title' => 'Invalid Date', 'message' => 'Please provide a valid start date in YYYY-MM-DD format.', 'type' => 'error']);
        return;
    }

    if ($loan_amount === false || $loan_amount <= 0 || $installments === false || $installments <= 0) {
        echo json_encode(['status' => 'error', 'title' => 'Invalid Input', 'message' => 'Please provide a valid loan amount and number of installments.', 'type' => 'error']);
        return;
    }

    // For 'regular' loans, perform End of Service validation
    if ($loan_type === 'regular') {
        $query = "SELECT e.joining_date, (s.basic + s.housing + s.transport + s.food + s.misc + s.cashier + s.fuel + s.tel + s.other + s.guard) as total_salary FROM employees e JOIN emp_salary s ON e.emp_id = s.emp_id WHERE e.emp_id = '$emp_id'";
        $result = mysqli_query($conDB, $query);
        if ($row = mysqli_fetch_assoc($result)) {
            $endOfServiceBenefit = calculateEndOfService($row['joining_date'], $row['total_salary']);
            $maxLoanAmount = $endOfServiceBenefit * 0.40;
            if ($loan_amount > $maxLoanAmount) {
                echo json_encode(['status' => 'error', 'title' => 'Amount Exceeded', 'message' => 'Loan amount cannot exceed the maximum limit of ' . round($maxLoanAmount, 2), 'type' => 'error']);
                return;
            }
        } else {
            echo json_encode(['status' => 'error', 'title' => 'Validation Error', 'message' => 'Cannot verify employee details.', 'type' => 'error']);
            return;
        }
    }
    // For 'emergency' loans, we skip the above validation block

    // Calculate loan details
    $interest_rate = 0.00;
    $total_payable = $loan_amount;
    $monthly_deduction = $total_payable / $installments;
    $end_date = clone $start_date;
    $end_date->modify('+' . ($installments - 1) . ' months');
    $end_date_str = $end_date->format('Y-m-d');
    $start_date_str_db = $start_date->format('Y-m-d');

    // Insert into database
    $stmt = $conDB->prepare("INSERT INTO `emp_loan` (`emp_id`, `loan_type`, `loan_amount`, `interest_rate`, `total_payable`, `monthly_deduction`, `start_date`, `end_date`, `status`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'dept_manager_pending')");
    if ($stmt === false) {
        echo json_encode(['status' => 'error', 'title' => 'Database Error', 'message' => 'Failed to prepare the SQL statement: ' . $conDB->error, 'type' => 'error']);
        return;
    }
    $stmt->bind_param("ssddddss", $emp_id, $loan_type, $loan_amount, $interest_rate, $total_payable, $monthly_deduction, $start_date_str_db, $end_date_str);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'title' => 'Success', 'message' => 'Your loan application has been submitted successfully.', 'type' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'title' => 'Database Error', 'message' => 'Failed to submit loan application: ' . $stmt->error, 'type' => 'error']);
    }
    $stmt->close();
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
    $username = $_SESSION['auth_user']['user_id'] ?? null;
    if (empty($username)) {
        echo json_encode(['status' => 'error', 'title' => 'Authentication Error', 'message' => 'User session not found. Please log in again.', 'type' => 'error']);
        return;
    }
    $approver_id = $username;
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
    
    $status_column = $approver_role . '_status'; // e.g., dept_manager_status
    
    $stmt = $conDB->prepare("UPDATE `emp_loan` SET `status` = 'rejected', `$status_column` = 'rejected' WHERE `id` = ?");
    $stmt->bind_param("i", $loan_id);

    if ($stmt->execute()) {
        $stmt_approval = $conDB->prepare("INSERT INTO `emp_loan_approvals` (loan_id, approver_id, approver_role, status, notes) VALUES (?, ?, ?, ?, ?)");
        $status = 'rejected';
        $stmt_approval->bind_param("issss", $loan_id, $approver_id, $approver_role, $status, $rejection_note);
        $stmt_approval->execute();
        $stmt_approval->close();
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
    if (!isset($_POST['loan_id'], $_POST['payment_amount'], $_POST['payment_date'], $_POST['receipt_id']) || empty(trim($_POST['receipt_id'])) || !isset($_FILES['attachment']) || $_FILES['attachment']['error'] != UPLOAD_ERR_OK) {
        $message = 'Missing required fields.';
        if (empty(trim($_POST['receipt_id']))) {
            $message = 'Receipt ID is required.';
        } elseif (!isset($_FILES['attachment']) || $_FILES['attachment']['error'] != UPLOAD_ERR_OK) {
            $message = 'A valid receipt attachment is required.';
        }
        echo json_encode(['status' => 'error', 'title' => 'Input Error', 'message' => $message, 'type' => 'error']);
        return;
    }


    $loan_id = filter_var($_POST['loan_id'], FILTER_VALIDATE_INT);
    $payment_amount = filter_var($_POST['payment_amount'], FILTER_VALIDATE_FLOAT);
    $payment_date_str = $_POST['payment_date'];
    $receipt_id = mysqli_real_escape_string($conDB, $_POST['receipt_id']);
    $attachment_filename = null;
    $payment_date = DateTime::createFromFormat('Y-m-d', $payment_date_str);

    if ($loan_id === false || $payment_amount === false || $payment_amount <= 0 || !$payment_date) {
        echo json_encode(['status' => 'error', 'title' => 'Invalid Input', 'message' => 'Please provide a valid amount and date.', 'type' => 'error']);
        return;
    }

    // Handle file upload
    if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] == UPLOAD_ERR_OK) {
        $upload_dir = __DIR__ . '/../../assets/loan_receipts/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        $file_extension = pathinfo($_FILES['attachment']['name'], PATHINFO_EXTENSION);
        $attachment_filename = 'receipt_' . $loan_id . '_' . time() . '.' . $file_extension;
        $upload_file = $upload_dir . $attachment_filename;
        if (!move_uploaded_file($_FILES['attachment']['tmp_name'], $upload_file)) {
            echo json_encode(['status' => 'error', 'title' => 'Upload Error', 'message' => 'Failed to save attachment.', 'type' => 'error']);
            return;
        }
    }

    $conDB->begin_transaction();
    try {
        // Server-side validation of remaining balance
        $stmt_loan = $conDB->prepare("SELECT total_payable FROM emp_loan WHERE id = ? FOR UPDATE");
        $stmt_loan->bind_param("i", $loan_id);
        $stmt_loan->execute();
        $loan = $stmt_loan->get_result()->fetch_assoc();
        $stmt_loan->close();

        if (!$loan) throw new Exception('Loan not found.');

        $stmt_paid = $conDB->prepare("SELECT COALESCE(SUM(amount), 0) as total_paid FROM emp_loan_payments WHERE loan_id = ?");
        $stmt_paid->bind_param("i", $loan_id);
        $stmt_paid->execute();
        $total_paid = $stmt_paid->get_result()->fetch_assoc()['total_paid'] ?? 0;
        $stmt_paid->close();

        $remaining_balance = $loan['total_payable'] - $total_paid;

        if ($payment_amount > $remaining_balance + 0.01) { // Add tolerance for floating point issues
            throw new Exception('Payment amount cannot exceed the remaining balance of ' . round($remaining_balance, 2));
        }

        // Insert the new payment
        $stmt_insert = $conDB->prepare("INSERT INTO emp_loan_payments (loan_id, payment_date, amount, receipt_id, attachment, payment_method) VALUES (?, ?, ?, ?, ?, 'manual')");
        $stmt_insert->bind_param("isdss", $loan_id, $payment_date_str, $payment_amount, $receipt_id, $attachment_filename);
        $stmt_insert->execute();
        $stmt_insert->close();

        // Check if the loan is now fully paid
        if (($total_paid + $payment_amount) >= $loan['total_payable']) {
            $stmt_update = $conDB->prepare("UPDATE emp_loan SET status = 'paid' WHERE id = ?");
            $stmt_update->bind_param("i", $loan_id);
            $stmt_update->execute();
            $stmt_update->close();
        }

        $conDB->commit();
        echo json_encode(['status' => 'success', 'title' => 'Success', 'message' => 'Manual payment has been recorded successfully.', 'type' => 'success']);

    } catch (Exception $e) {
        $conDB->rollback();
        echo json_encode(['status' => 'error', 'title' => 'Error', 'message' => $e->getMessage(), 'type' => 'error']);
    }
}

function modify_and_approve_loan() {
    global $conDB;
    if (session_status() == PHP_SESSION_NONE) session_start();
    $username = $_SESSION['auth_user']['user_id'] ?? null;
    if (empty($username)) {
        echo json_encode(['status' => 'error', 'title' => 'Authentication Error', 'message' => 'User session not found. Please log in again.', 'type' => 'error']);
        return;
    }
    $approver_id = $username;

    // Validate inputs
    if (!isset($_POST['loan_id'], $_POST['loan_amount'], $_POST['installments'])) {
        echo json_encode(['status' => 'error', 'title' => 'Input Error', 'message' => 'Missing required modification data.', 'type' => 'error']);
        return;
    }
    $loan_id = filter_var($_POST['loan_id'], FILTER_VALIDATE_INT);
    $new_loan_amount = filter_var($_POST['loan_amount'], FILTER_VALIDATE_FLOAT);
    $new_installments = filter_var($_POST['installments'], FILTER_VALIDATE_INT);

    if ($loan_id === false || $new_loan_amount === false || $new_loan_amount <= 0 || $new_installments === false || $new_installments <= 0) {
        echo json_encode(['status' => 'error', 'title' => 'Invalid Input', 'message' => 'Please provide a valid loan amount and number of installments.', 'type' => 'error']);
        return;
    }

    $conDB->begin_transaction();
    try {
        // Fetch original start date
        $stmt_start_date = $conDB->prepare("SELECT start_date FROM emp_loan WHERE id = ?");
        $stmt_start_date->bind_param("i", $loan_id);
        $stmt_start_date->execute();
        $result = $stmt_start_date->get_result();
        $loan = $result->fetch_assoc();
        $stmt_start_date->close();

        if (!$loan) {
            throw new Exception("Loan not found.");
        }
        $start_date = new DateTime($loan['start_date']);

        // Recalculate loan terms
        $new_total_payable = $new_loan_amount;
        $new_monthly_deduction = $new_total_payable / $new_installments;
        $new_end_date = clone $start_date;
        $new_end_date->modify('+' . ($new_installments - 1) . ' months');
        $new_end_date_str = $new_end_date->format('Y-m-d');

        // Update the loan record
        $stmt_update = $conDB->prepare("UPDATE `emp_loan` SET 
            `loan_amount` = ?, 
            `total_payable` = ?, 
            `monthly_deduction` = ?, 
            `end_date` = ?, 
            `status` = 'finance_assistant_pending', 
            `gm_status` = 'approved' 
            WHERE `id` = ? AND `status` = 'gm_pending'");
        
        $stmt_update->bind_param("dddsi", $new_loan_amount, $new_total_payable, $new_monthly_deduction, $new_end_date_str, $loan_id);
        $stmt_update->execute();

        if ($stmt_update->affected_rows === 0) {
            throw new Exception("Loan could not be updated. It might have been already processed or is not at the correct approval stage.");
        }
        $stmt_update->close();

        // Log the approval and modification
        $stmt_approval = $conDB->prepare("INSERT INTO `emp_loan_approvals` (loan_id, approver_id, approver_role, status, notes) VALUES (?, ?, ?, ?, ?)");
        $status = 'approved';
        $role = 'gm';
        $notes = "GM approved with modifications. New Amount: $new_loan_amount, New Installments: $new_installments.";
        $stmt_approval->bind_param("issss", $loan_id, $approver_id, $role, $notes, $status);
        $stmt_approval->execute();
        $stmt_approval->close();

        $conDB->commit();
        echo json_encode(['status' => 'success', 'title' => 'Approved!', 'message' => 'The loan has been modified and approved successfully.', 'type' => 'success']);

    } catch (Exception $e) {
        $conDB->rollback();
        echo json_encode(['status' => 'error', 'title' => 'Error', 'message' => $e->getMessage(), 'type' => 'error']);
    }
}

function modify_and_approve_loan_hr_assistant() {
    global $conDB;
    if (session_status() == PHP_SESSION_NONE) session_start();
    $username = $_SESSION['auth_user']['user_id'] ?? null;
    if (empty($username)) {
        echo json_encode(['status' => 'error', 'title' => 'Authentication Error', 'message' => 'User session not found.', 'type' => 'error']);
        return;
    }
    $approver_id = $username;

    if (!isset($_POST['loan_id'], $_POST['loan_amount'], $_POST['installments'])) {
        echo json_encode(['status' => 'error', 'title' => 'Input Error', 'message' => 'Missing required modification data.', 'type' => 'error']);
        return;
    }
    $loan_id = filter_var($_POST['loan_id'], FILTER_VALIDATE_INT);
    $new_loan_amount = filter_var($_POST['loan_amount'], FILTER_VALIDATE_FLOAT);
    $new_installments = filter_var($_POST['installments'], FILTER_VALIDATE_INT);

    if ($loan_id === false || $new_loan_amount === false || $new_loan_amount <= 0 || $new_installments === false || $new_installments <= 0) {
        echo json_encode(['status' => 'error', 'title' => 'Invalid Input', 'message' => 'Please provide a valid loan amount and number of installments.', 'type' => 'error']);
        return;
    }

    $conDB->begin_transaction();
    try {
        $stmt_start_date = $conDB->prepare("SELECT start_date FROM emp_loan WHERE id = ?");
        $stmt_start_date->bind_param("i", $loan_id);
        $stmt_start_date->execute();
        $loan = $stmt_start_date->get_result()->fetch_assoc();
        $stmt_start_date->close();

        if (!$loan) throw new Exception("Loan not found.");
        
        $start_date = new DateTime($loan['start_date']);
        $new_total_payable = $new_loan_amount;
        $new_monthly_deduction = $new_total_payable / $new_installments;
        $new_end_date = (clone $start_date)->modify('+' . ($new_installments - 1) . ' months');
        $new_end_date_str = $new_end_date->format('Y-m-d');

        $stmt_update = $conDB->prepare("UPDATE `emp_loan` SET 
            `loan_amount` = ?, `total_payable` = ?, `monthly_deduction` = ?, `end_date` = ?, 
            `status` = 'hr_manager_pending', `hr_assistant_status` = 'approved' 
            WHERE `id` = ? AND `status` = 'hr_assistant_pending'");
        
        $stmt_update->bind_param("dddsi", $new_loan_amount, $new_total_payable, $new_monthly_deduction, $new_end_date_str, $loan_id);
        $stmt_update->execute();

        if ($stmt_update->affected_rows === 0) {
            throw new Exception("Loan could not be updated. It might have been already processed.");
        }
        $stmt_update->close();

        $stmt_approval = $conDB->prepare("INSERT INTO `emp_loan_approvals` (loan_id, approver_id, approver_role, status, notes) VALUES (?, ?, ?, ?, ?)");
        $status = 'approved';
        $role = 'hr_assistant';
        $notes = "HR Assistant approved with modifications. New Amount: $new_loan_amount, New Installments: $new_installments.";
        $stmt_approval->bind_param("issss", $loan_id, $approver_id, $role, $status, $notes);
        $stmt_approval->execute();
        $stmt_approval->close();

        $conDB->commit();
        echo json_encode(['status' => 'success', 'title' => 'Approved!', 'message' => 'The loan has been modified and approved.', 'type' => 'success']);

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


if (isset($conDB)) {
    $conDB->close();
}
?>
