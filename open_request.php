<?php

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/FileUploader.php';
require_once __DIR__ . '/includes/session_check.php'; // Needed for user details & includes helper_functions.php now

// Handle AJAX Payment Processing Separately
if (isset($_POST['process_payment'])) {
    header('Content-Type: application/json');

    $inv_no_pay = $_POST['inv_no'];
    $paid_amount = $_POST['paid_amount'];
    $payment_note = mysqli_real_escape_string($conDB, $_POST['payment_note']);
    $response = [];

    // File upload handling
    $target_dir = "assets/smt_payment_invoices/";
    $attachment_name = "";
    if (isset($_FILES["payment_invoice"]) && $_FILES["payment_invoice"]["error"] == 0) {
        $file_ext = (string)FileUploader::safeExt($_FILES["payment_invoice"]["name"], 'document');
        $attachment_name = $inv_no_pay . "_payment_" . time() . "." . $file_ext;
        $target_file = $target_dir . $attachment_name;

        if (FileUploader::moveTo($_FILES["payment_invoice"]["tmp_name"], $target_file, 'document')) {
            // Update smart_request status to 'paid'
            $update_sql = "UPDATE `smart_request` SET
                            `current_status`='paid'
                           WHERE `inv_no`='$inv_no_pay'";
            mysqli_query($conDB, $update_sql);

            // Insert into new smt_payment table
            $insert_payment = mysqli_query($conDB, "INSERT INTO `smt_payment` (`inv_no`, `paid_amount`, `payment_invoice`, `paid_by_id`, `paid_by_name`, `note`) VALUES ('$inv_no_pay', '$paid_amount', '$attachment_name', '$empid', '$userwel', '$payment_note')");

            if($insert_payment){
                // Add a status log
                mysqli_query($conDB, "INSERT INTO `smt_request_status` (`emp_id`, `inv_no`, `emp_name`, `status`, `note`) VALUES ('$empid', '$inv_no_pay', '$userwel', 'paid', 'payment_processed.')");
                
                // Send email notification to request creator with payment proof
                require_once __DIR__ . '/includes/helper_functions.php';
                require_once __DIR__ . '/includes/vendor/autoload.php';
                
                // Get request details and creator info
                $request_query = mysqli_query($conDB, "SELECT `emp_id`, `sub_title`, `prep_by` FROM `smart_request` WHERE `inv_no` = '" . escape_string($inv_no_pay) . "' LIMIT 1");
                if ($request_query && mysqli_num_rows($request_query) > 0) {
                    $request_data = mysqli_fetch_assoc($request_query);
                    $creator_id = $request_data['emp_id'];
                    $request_title = $request_data['sub_title'];
                    
                    // Get creator email
                    $creator_details = getEmployeeDetails($conDB, $creator_id);
                    if ($creator_details && !empty($creator_details['email'])) {
                        try {
                            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
                            
                            // SMTP settings
                            $smtp_host = get_setting($conDB, 'smtp_host');
                            $smtp_port = (int)get_setting($conDB, 'smtp_port');
                            $smtp_user = get_setting($conDB, 'smtp_user');
                            $smtp_pass = get_setting($conDB, 'smtp_pass');
                            $smtp_from_email = get_setting($conDB, 'from_email');
                            $smtp_from_name = get_setting($conDB, 'from_name', 'Al Mutlak HR System');
                            $smtp_secure = get_setting($conDB, 'smtp_encryption');
                            
                            $mail->isSMTP();
                            $mail->Host = $smtp_host;
                            $mail->SMTPAuth = true;
                            $mail->Username = $smtp_user;
                            $mail->Password = $smtp_pass;
                            
                            switch (strtolower($smtp_secure)) {
                                case 'tls':
                                    $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                                    break;
                                case 'ssl':
                                    $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
                                    break;
                                default:
                                    $mail->SMTPSecure = false;
                                    break;
                            }
                            
                            $mail->Port = $smtp_port;
                            $mail->CharSet = 'UTF-8';
                            $mail->setFrom($smtp_from_email, $smtp_from_name);
                            $mail->addAddress($creator_details['email'], $creator_details['name']);
                            
                            // Attach payment proof
                            if (file_exists($target_file)) {
                                $mail->addAttachment($target_file, $attachment_name);
                            }
                            
                            $mail->isHTML(true);
                            $mail->Subject = 'Payment Processed Successfully - ' . $inv_no_pay;
                            
                            // Prepare template data for payment notification
                            $template_data = [
                                'APPROVER_NAME' => $creator_details['name'],
                                'REQUEST_ID' => $inv_no_pay,
                                'REQUEST_TITLE' => $request_title,
                                'SUBMITTED_BY' => $request_data['prep_by'],
                                'DEPARTMENT' => '', // Not critical for payment notification
                                'VIEW_REQUEST_URL' => get_base_url($conDB) . '/open_request.php?id=' . urlencode($inv_no_pay),
                                'LOGO_URL' => get_base_url($conDB) . '/' . (get_setting($conDB, 'logo') ?? 'assets/images/logo.png'),
                                'EMAIL_MESSAGE' => "Your smart request has been <strong style='color: #28a745;'>paid successfully</strong>. Please find the payment details below:<br><br>
                                    <strong>Paid Amount:</strong> SAR " . number_format($paid_amount, 2) . "<br>
                                    <strong>Paid By:</strong> " . htmlspecialchars($userwel) . "<br>
                                    <strong>Payment Note:</strong> " . htmlspecialchars($payment_note) . "<br><br>
                                    The payment proof is attached to this email for your records."
                            ];
                            
                            // Use the smart request email template
                            $email_html = load_email_template('smart_request', $template_data);
                            
                            if ($email_html !== false) {
                                $mail->Body = $email_html;
                            } else {
                                // Fallback to plain text
                                $mail->Body = "Dear " . htmlspecialchars($creator_details['name']) . ",\n\nYour smart request has been paid successfully.\n\nRequest ID: $inv_no_pay\nRequest Title: $request_title\nPaid Amount: SAR " . number_format($paid_amount, 2) . "\nPaid By: $userwel\nPayment Note: $payment_note\n\nThe payment proof is attached to this email.";
                            }
                            
                            $mail->AltBody = "Payment Processed Successfully\n\nRequest ID: $inv_no_pay\nRequest Title: $request_title\nPaid Amount: SAR " . number_format($paid_amount, 2) . "\nPaid By: $userwel\nPayment Note: $payment_note\n\nThe payment proof is attached to this email.";
                            
                            $mail->send();
                            error_log("Payment notification email sent to creator (emp_id: $creator_id) for request: $inv_no_pay");
                        } catch (Exception $e) {
                            error_log("Failed to send payment notification email for $inv_no_pay: " . $mail->ErrorInfo);
                        }
                    }
                }
                
                $response = ['status' => 'success', 'message' => __('payment_processed_successfully')];
            } else {
                // Optionally remove the uploaded file if DB insert fails
                if (file_exists($target_file)) { unlink($target_file); }
                $response = ['status' => 'error', 'message' => __('failed_to_save_payment_details') . " DB Error: " . mysqli_error($conDB)];
                 mysqli_query($conDB, "UPDATE `smart_request` SET `current_status`='approved' WHERE `inv_no`='$inv_no_pay'"); // Revert status
            }
        } else {
            $response = ['status' => 'error', 'message' => __('error_uploading_file') . ' ' . FileUploader::lastError()];
        }
    } else {
        $response = ['status' => 'error', 'message' => __('select_payment_invoice_to_upload')];
    }
    echo json_encode($response);
    exit(); // Stop script execution for AJAX requests
}


// --- Early guard: ensure a valid request id is provided ---
if (!isset($_GET['id']) || trim((string)$_GET['id']) === '') {
    // No id supplied; redirect to the listing page with error flag
    header('Location: all_requests.php?error=request_not_found');
    exit;
}


include("./includes/convertNumbersToWords.php");

require './includes/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$query = mysqli_query($conDB, "SELECT * FROM `admin_login` WHERE `id_iqama`='" . $username . "'");
if ($query && mysqli_num_rows($query) == 1) { // Add check for query success
    include("./includes/avatar_select.php");
}

// Fetch potential approvers (not 'employee' user_type) AND INCLUDE user_type AND dept ID
$potential_approvers = [];
// --- MODIFIED QUERY TO INCLUDE e.dept ---
$approver_query = mysqli_query($conDB, "SELECT e.`emp_id`, e.`name`, al.`user_type`, e.`dept`
                                        FROM `employees` e
                                        JOIN `admin_login` al ON e.`emp_id` = al.`emp_id`
                                        WHERE al.`user_type` != 'employee' AND e.`status` = 1
                                        ORDER BY e.`name`");
if ($approver_query) {
    while ($row_approver = mysqli_fetch_assoc($approver_query)) {
        $potential_approvers[] = $row_approver;
    }
}
// --- END MODIFICATION ---

// --- NEW: Department ID to Name Mapping ---
// Based on the schema provided
$department_map = [
    1 => 'Administration', // Simplified name
    2 => 'Finance',
    5 => 'HR', // Simplified name
    12 => 'Public Relation',
    14 => 'Sales',
    7 => 'Inspection',
    13 => 'Purchase',
    6 => 'IT', // Simplified name
    11 => 'Production',
    15 => 'Warehouse',
    9 => 'Maintenance',
    10 => 'Management',
    3 => 'General',
    4 => 'Housing',
    8 => 'Logistics',
    16 => 'Training',
];
// --- END NEW MAPPING ---


// Fetch main request details
$getquery = mysqli_query($conDB, "SELECT
            `smt`.*,
            SUM(`smt`.`total_cost`) as `subtotal`,
            SUM(`smt`.`vat_val`) as `vat_val`,
            MAX(`smt`.`created_at`) as `latest_created_at`,
            `dpt`.`dep_nme`
            FROM `smart_request` `smt`
            LEFT JOIN `department` `dpt` ON `dpt`.`id` = `smt`.`department`
            WHERE `smt`.`inv_no`='" . escape_string($_GET['id']) . "'
            GROUP BY `smt`.`inv_no`");

// Initialize variables outside the loop
$idno = null;
$invnoget = $_GET['id']; // Use GET param as default if query fails
$total_costget = 0;
$vat_get = 0;
$discount_get = 0;
$location_get = '';
$sub_type_get = '';
$sub_title_get = '';
$prep_by_get = '';
$department_get = '';
$dep_nme_get = '';
$remarks_get = '';
$emp_id_get = null; // Creator's emp_id
$created_at_get = '';
$current_status_get = 'draft'; // Default status
$current_approval_level_get = null;
$payable_by_emp_id_get = null; // Initialize new variable
$assigned_payer_name = null; // Initialize payer name
$total_cost_get = 0;
$total = 0;
$gtotal = 0;

// Fetch data if query was successful and returned rows
if ($getquery && mysqli_num_rows($getquery) > 0) {
    while ($row = mysqli_fetch_assoc($getquery)) {
        $idno = $row["id"];
        $invnoget = $row["inv_no"];
        $total_costget = $row['subtotal'];
        $vat_get = $row['vat_val'];
        $discount_get = $row["discount"];
        $location_get = $row["location"];
        $sub_type_get = $row["sub_type"];
        $sub_title_get = $row["sub_title"];
        $prep_by_get = isset($row["prep_by"]) ? ((explode(" ", $row["prep_by"])[0]) . " " . (explode(" ", $row["prep_by"])[1])) : '';
        $department_get = $row["department"];
        $dep_nme_get = $row["dep_nme"];
        $remarks_get = $row["remarks"];
        $emp_id_get = $row["emp_id"]; // Creator's ID
        $created_at_get = $row["latest_created_at"];
        $current_status_get = $row['current_status'];
        $current_approval_level_get = $row['current_approval_level'];
        $payable_by_emp_id_get = $row['payable_by_emp_id']; // Fetch new column

        $total_cost_get = $total_costget - $vat_get;
        $total = $total_cost_get + $vat_get;
        $gtotal = $total - $discount_get;
    }
    // Fetch assigned payer name if ID exists
    if ($payable_by_emp_id_get) {
         require_once __DIR__ . '/includes/helper_functions.php'; // Ensure getEmployeeDetails is loaded
        $payerDetails = getEmployeeDetails($conDB, $payable_by_emp_id_get);
        if ($payerDetails && $payerDetails['name'] !== 'N/A') {
            $assigned_payer_name = $payerDetails['name'];
        }
    }
} else {
    // Invalid or not found: redirect away to list (clean failure)
    header('Location: all_requests.php?error=request_not_found');
    exit;
}


// Get creator details (only if emp_id_get was found)
$creator_emptype = null;
$creator_dept = null;
if ($emp_id_get) {
    $creator_query = mysqli_query($conDB, "SELECT `emp_id`, `dept`, `emptype` FROM `employees` WHERE `emp_id` = '$emp_id_get'");
    if ($creator_query && mysqli_num_rows($creator_query) > 0) {
        $creator_details = mysqli_fetch_assoc($creator_query);
        $creator_emptype = $creator_details['emptype']; // Creator's employee type
        $creator_dept = $creator_details['dept']; // Creator's department
    }
}


// --- GENERAL APPROVAL POST HANDLER ---
if (isset($_POST['submit']) || isset($_POST['assign_payer_submit'])) { // Combine checks
     require_once __DIR__ . '/includes/helper_functions.php'; // Ensure approval functions are loaded

    $inv_no_po = $_POST['inv_no'];
    $note_po = mysqli_real_escape_string($conDB, $_POST['note'] ?? '');
    $status_po = $_POST['status'] ?? null; // For approval actions
    $request_type = 'smart_request'; // This can be dynamic for other modules

    $next_approver_email = '';
    $next_approver_name = '';
    $cc_hr_employees = []; // Initialize CC array

    // Check if this is a DRAFT SUBMISSION by the creator
    if (isset($_POST['submit']) && $current_status_get == 'draft' && $empid == $emp_id_get && isset($_POST['approvers'])) {

        $approver_ids = $_POST['approvers']; // This is already an array
        $approver_ids = array_filter($approver_ids); // Filter out empty/null values

        if (empty($approver_ids)) {
             $msg = "<div class=\"alert alert-danger bg-danger text-white border-0\" role=\"alert\">".__('select_at_least_one_approver')."</div>";
        } else {
            if (save_approval_chain($conDB, $inv_no_po, $request_type, $approver_ids)) {
                $first_approver_id = $approver_ids[0];
                mysqli_query($conDB, "UPDATE `smart_request` SET `current_status` = 'pending_approval', `current_approval_level` = 1 WHERE `inv_no` = '" . escape_string($inv_no_po) . "'");
                mysqli_query($conDB, "INSERT INTO `smt_request_status` (`emp_id`, `inv_no`, `emp_name`, `status`, `note`) VALUES ('$empid', '".escape_string($inv_no_po)."', '".escape_string($userwel)."', 'pending_approval', '" . __('submitted_for_approval') . "')");
                $first_approver_details = getEmployeeDetails($conDB, $first_approver_id);
                if ($first_approver_details && $first_approver_details['name'] !== 'N/A') {
                    $next_approver_name = $first_approver_details['name'];
                    $next_approver_email = $first_approver_details['email'];

                    // --- ADD BROWSER NOTIFICATION ---
                    $notification_title = "New Request for Approval";
                    $notification_message = "Request " . htmlspecialchars($inv_no_po) . " is waiting for your action.";
                    $notification_url = "open_request.php?id=" . urlencode($inv_no_po);
                    create_browser_notification($conDB, $first_approver_id, $notification_title, $notification_message, $notification_url);
                    // --- END NOTIFICATION ---

                } else { error_log("Could not find details for first approver ID: " . $first_approver_id); }
            } else {
                $msg = "<div class=\"alert alert-danger bg-danger text-white border-0\" role=\"alert\">".__('failed_to_save_approval_chain')." Error: ".mysqli_error($conDB)."</div>";
            }
        }

    // Check if this is an APPROVAL ACTION by an approver
    } elseif (isset($_POST['submit']) && isset($status_po) && ($status_po == 'approve' || $status_po == 'reject')) {

        $result = handle_approval_action($conDB, $inv_no_po, $request_type, $empid, $status_po, $note_po);
        if ($result['status'] == 'success') {
            if (isset($result['next_approver']) && $result['next_approver'] != null) {
                // Approval went through, notify next approver
                $next_approver_name = $result['next_approver']['name'];
                $next_approver_email = $result['next_approver']['email'];
                $next_approver_id = $result['next_approver_id']; // Get the ID from the result

                // --- ADD BROWSER NOTIFICATION (for next approver) ---
                if (isset($next_approver_id) && $next_approver_id) {
                    $notification_title = "Request for Approval";
                    $notification_message = "Request " . htmlspecialchars($inv_no_po) . " has been approved and is now waiting for your action.";
                    $notification_url = "open_request.php?id=" . urlencode($inv_no_po);
                    create_browser_notification($conDB, $next_approver_id, $notification_title, $notification_message, $notification_url);
                }
                // --- END NOTIFICATION ---
            } elseif ($status_po == 'reject') {
                // --- REJECTION NOTIFICATION LOGIC ---
                $notification_title = "Request Rejected";
                $notification_message = "Request " . htmlspecialchars($inv_no_po) . " was rejected by " . htmlspecialchars($userwel) . ". Reason: " . htmlspecialchars($note_po);
                $notification_url = "open_request.php?id=" . urlencode($inv_no_po);

                // 1. Notify the Creator (ensure creator ID $emp_id_get exists)
                if ($emp_id_get && $emp_id_get != $empid) { // Don't notify self
                    create_browser_notification($conDB, $emp_id_get, $notification_title, $notification_message, $notification_url);
                }

                // 2. Notify Previous Approvers
                // Get request_type_id first
                 $type_query_reject = mysqli_query($conDB, "SELECT `id` FROM `approval_request_types` WHERE `type_name` = '" . escape_string($request_type) . "' LIMIT 1");
                 if ($type_query_reject && mysqli_num_rows($type_query_reject) > 0) {
                     $type_row_reject = mysqli_fetch_assoc($type_query_reject);
                     $request_type_id_reject = $type_row_reject['id'];

                     $prev_approvers_sql = "SELECT `approver_id` FROM `request_approvers`
                                            WHERE `request_inv_no` = '" . escape_string($inv_no_po) . "'
                                              AND `request_type_id` = $request_type_id_reject
                                              AND `status` = 'approved'"; // Only those who already approved

                     $prev_approvers_query = mysqli_query($conDB, $prev_approvers_sql);
                     if ($prev_approvers_query) {
                         while ($prev_approver_row = mysqli_fetch_assoc($prev_approvers_query)) {
                             $prev_approver_id = $prev_approver_row['approver_id'];
                             if ($prev_approver_id != $empid) { // Don't notify the rejector
                                 create_browser_notification($conDB, $prev_approver_id, $notification_title, $notification_message, $notification_url);
                             }
                         }
                     } else {
                          error_log("Rejection Notification: Failed to query previous approvers for InvNo: $inv_no_po. Error: " . mysqli_error($conDB));
                     }
                 } else {
                     error_log("Rejection Notification: Could not find request_type_id for '$request_type'.");
                 }
                // --- END REJECTION NOTIFICATION LOGIC ---
            }

            // Check if current user is HR (dept 5) and action was approve
            if ($user_dept == 5 && $status_po == 'approve' && isset($_POST['cc_hr_employees']) && is_array($_POST['cc_hr_employees'])) {
                $cc_hr_employees = array_map('intval', $_POST['cc_hr_employees']); // Sanitize IDs
                 error_log("HR Approval: CC IDs selected: " . print_r($cc_hr_employees, true)); // DEBUG: Log selected CC IDs
            }
        } else {
            $msg = "<div class=\"alert alert-danger bg-danger text-white border-0\" role=\"alert\">" . htmlspecialchars($result['message']) . "</div>";
        }

    // Check if this is an ASSIGN PAYER action by Finance Manager
    // FIXED: Use $emptypeget (current user) instead of $emptypegetget (creator)
    } elseif (isset($_POST['assign_payer_submit']) && $current_status_get == 'approved' && $emptypeget == 'Manager' && $user_dept == 2) {
        $payable_by_emp_id_assign = isset($_POST['payable_by_emp_id']) ? (int)$_POST['payable_by_emp_id'] : 0;
        if ($payable_by_emp_id_assign > 0) {
            // Update both payable_by_emp_id AND current_status to 'pending_payment'
            $update_payer_sql = "UPDATE `smart_request` SET `payable_by_emp_id` = $payable_by_emp_id_assign, `current_status` = 'pending_payment' WHERE `inv_no` = '" . escape_string($inv_no_po) . "'";
            if (mysqli_query($conDB, $update_payer_sql)) {
                mysqli_query($conDB, "INSERT INTO `smt_request_status` (`emp_id`, `inv_no`, `emp_name`, `status`, `note`) VALUES ('$empid', '".escape_string($inv_no_po)."', '".escape_string($userwel)."', 'payment_assigned', 'Assigned for payment processing.')");
                $assignee_details = getEmployeeDetails($conDB, $payable_by_emp_id_assign);
                if ($assignee_details && $assignee_details['name'] !== 'N/A' && !empty($assignee_details['email'])) {
                    $next_approver_name = $assignee_details['name'];
                    $next_approver_email = $assignee_details['email'];
                    $_POST['email_subject'] = 'Payment Processing Request - ' . $invnoget;
                    $_POST['email_body_line'] = 'A Smart Request has been assigned to you for payment processing.';

                    // --- ADD BROWSER NOTIFICATION ---
                    $notification_title = "Payment Processing Assigned";
                    $notification_message = "Request " . htmlspecialchars($inv_no_po) . " has been assigned to you for payment.";
                    $notification_url = "open_request.php?id=" . urlencode($inv_no_po);
                    create_browser_notification($conDB, $payable_by_emp_id_assign, $notification_title, $notification_message, $notification_url);
                    // --- END NOTIFICATION ---
                    
                }
                // Refresh the page data after assignment
                $current_status_get = 'pending_payment';
                $payable_by_emp_id_get = $payable_by_emp_id_assign;
                $msg = "<div class=\"alert alert-success bg-success text-white border-0\" role=\"alert\">".__('payer_assigned_successfully')."</div>";
            } else {
                 $msg = "<div class=\"alert alert-danger bg-danger text-white border-0\" role=\"alert\">".__('failed_to_assign_payer')." Error: ".mysqli_error($conDB)."</div>";
            }
        } else {
             $msg = "<div class=\"alert alert-danger bg-danger text-white border-0\" role=\"alert\">".__('please_select_valid_employee')."</div>";
        }

    } else {
        // Handle cases like refresh or draft submission without approvers
        if (isset($_POST['submit']) && !isset($_POST['approvers']) && $current_status_get == 'draft' && $empid == $emp_id_get) {
             if (!isset($msg)) {
                $msg = "<div class=\"alert alert-danger bg-danger text-white border-0\" role=\"alert\">".__('select_at_least_one_approver')."</div>";
             }
        }
    }

    // --- EMAIL LOGIC ---
    // (Email logic remains unchanged, only browser notifications added for rejection)
    if (!empty($next_approver_email) || !empty($cc_hr_employees)) {
        $mail = new PHPMailer(true);
        try {
            // Fetch SMTP settings (consistent with helper_functions.php)
            $smtp_host = get_setting($conDB, 'smtp_host');
            $smtp_port = (int)get_setting($conDB, 'smtp_port');
            $smtp_user = get_setting($conDB, 'smtp_user');
            $smtp_pass = get_setting($conDB, 'smtp_pass');
            $smtp_from_email = get_setting($conDB, 'from_email');
            $smtp_from_name = get_setting($conDB, 'from_name', 'Al Mutlak HR System');
            $smtp_secure = get_setting($conDB, 'smtp_encryption');

            // Server settings
            $mail->isSMTP();
            $mail->Host = $smtp_host;
            $mail->SMTPAuth = true;
            $mail->Username = $smtp_user;
            $mail->Password = $smtp_pass;
            
            // Set encryption based on setting (consistent with helper_functions.php)
            switch (strtolower($smtp_secure)) {
                case 'tls':
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    break;
                case 'ssl':
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                    break;
                default:
                    $mail->SMTPSecure = false;
                    break;
            }
            
            $mail->Port = $smtp_port;
            $mail->CharSet = 'UTF-8';
            $mail->setFrom($smtp_from_email, $smtp_from_name);

            if (!empty($next_approver_email)) {
                $mail->addAddress($next_approver_email, $next_approver_name);
            }

            if (!empty($cc_hr_employees)) {
                 error_log("Attempting to add CC recipients: " . print_r($cc_hr_employees, true));
                foreach ($cc_hr_employees as $cc_emp_id) {
                     error_log("Processing CC for emp_id: " . $cc_emp_id);
                    if ($cc_emp_id == $empid) {
                        error_log("Skipping CC for self (emp_id: " . $cc_emp_id . ")");
                        continue;
                    }
                    $cc_details = getEmployeeDetails($conDB, $cc_emp_id);
                     error_log("Details found for emp_id " . $cc_emp_id . ": " . print_r($cc_details, true));
                    if ($cc_details && $cc_details['name'] !== 'N/A' && !empty($cc_details['email'])) {
                        error_log("Adding CC: " . $cc_details['email'] . " for emp_id: " . $cc_emp_id);
                        $mail->addCC($cc_details['email'], $cc_details['name']);
                    } else {
                        error_log("Skipping CC for emp_id: " . $cc_emp_id . " - Details or Email missing/invalid.");
                    }
                }
            }

            if (empty($next_approver_email) && !empty($cc_hr_employees)) {
                 $preparer_details = getEmployeeDetails($conDB, $emp_id_get);
                 if ($preparer_details && !empty($preparer_details['email'])) {
                     $mail->addAddress($preparer_details['email'], $preparer_details['name']);
                     error_log("Only CCs found, adding preparer as primary recipient: " . $preparer_details['email']);
                 } else { error_log("Only CCs found, but could not get preparer's email."); }
            }

            $mail->isHTML(true);
            
            // Determine email subject based on context
            $default_subject = "Smart Request Notification - " . ($invnoget ?? '');
            if (isset($_POST['email_subject'])) {
                $mail->Subject = $_POST['email_subject'];
            } elseif (!empty($cc_hr_employees) && empty($next_approver_email)) {
                $mail->Subject = "Smart Request Approved by HR - " . ($invnoget ?? '');
            } elseif (!empty($next_approver_email)) {
                $mail->Subject = "Smart Request Requires Your Approval - " . ($invnoget ?? '');
            } else {
                $mail->Subject = $default_subject;
            }

            // Prepare template data for the new email template
            $template_data = [
                'APPROVER_NAME' => $next_approver_name ?? 'Recipient',
                'REQUEST_ID' => $invnoget ?? '',
                'REQUEST_TITLE' => $sub_title_get ?? '',
                'SUBMITTED_BY' => $userwelext ?? $userwel ?? '',
                'DEPARTMENT' => $usrdeptnme ?? $dep_nme_get ?? '',
                'VIEW_REQUEST_URL' => get_base_url($conDB) . '/open_request.php?id=' . urlencode($invnoget ?? ''),
                'LOGO_URL' => get_base_url($conDB) . '/' . (get_setting($conDB, 'logo') ?? 'assets/images/logo.png')
            ];

            // Use the new HTML email template
            require_once __DIR__ . '/includes/helper_functions.php';
            $email_html = load_email_template('smart_request', $template_data);
            
            if ($email_html !== false) {
                $mail->Body = $email_html;
            } else {
                // Fallback to plain text if template fails
                $default_body_line = "A Smart Request requires attention.";
                if (isset($_POST['email_body_line'])) {
                    $default_body_line = $_POST['email_body_line'] ?? '';
                } elseif (!empty($cc_hr_employees) && empty($next_approver_email)) {
                    $default_body_line = "This request (" . ($invnoget ?? '') . ": " . ($sub_title_get ?? '') . ") has been approved by HR.";
                } elseif (!empty($next_approver_email)) {
                    $default_body_line = "A new Smart Request (" . ($invnoget ?? '') . ": " . ($sub_title_get ?? '') . ") requires your approval.";
                }
                $mail->Body = "Dear " . ($next_approver_name ?? 'Recipient') . ",\n\n" . $default_body_line . "\n\nRequest ID: " . ($invnoget ?? '') . "\nTitle: " . ($sub_title_get ?? '') . "\nPrepared by: " . ($userwelext ?? $userwel ?? '') . "\nDepartment: " . ($usrdeptnme ?? $dep_nme_get ?? '') . "\n\nView Request: " . get_base_url($conDB) . '/open_request.php?id=' . urlencode($invnoget ?? '');
                error_log("Smart Request: Email template load failed, using plain text fallback");
            }

            if (!empty($mail->getToAddresses()) || !empty($mail->getCcAddresses())) {
                 error_log("Attempting to send email. To: " . print_r($mail->getToAddresses(), true) . " CC: " . print_r($mail->getCcAddresses(), true));
                 $mail->send();
                 error_log("Email send attempted for InvNo: " . $inv_no_po);
            } else {
                 error_log("Email not sent for InvNo: " . $inv_no_po . " - No valid recipients (To or CC).");
            }
        } catch (Exception $e) {
            error_log("Mailer Error for InvNo: " . $inv_no_po . " - " . $mail->ErrorInfo);
        }
    } else {
         error_log("Email not triggered for InvNo: " . $inv_no_po . " - No next approver and no CCs specified.");
    }

    // Redirect only if $msg is not set (meaning no errors occurred during POST handling)
    if (empty($msg)) {
         echo "<script>window.location.href = 'all_requests.php?action=success';</script>";
         exit;
    }
}
// --- END OF POST HANDLER ---


// --- Status Display Logic ---
// Label + tone (tone-* classes in assets/css/smart_request.css) for the status pill.
require_once __DIR__ . '/includes/helper_functions.php'; // get_approval_chain_status, get_current_approver, personnel lists
$approval_chain = get_approval_chain_status($conDB, $invnoget, 'smart_request') ?: [];
$status_label = '';
$status_tone = 'tone-slate';
$rejection_note = "";
$rejected_by_name = '';
switch ($current_status_get) {
    case "draft":
        $status_label = __('draft_not_submitted');
        $status_tone = 'tone-slate';
        break;
    case "pending_approval":
        $status_label = __('pending_approval_level') . " " . $current_approval_level_get;
        $status_tone = 'tone-amber';
        break;
    case "approved":
        $status_label = $assigned_payer_name ? __('approved_pending_payment') : __('approved_pending_assignment');
        $status_tone = 'tone-indigo';
        break;
    case "pending_payment":
        $status_label = __('ready_for_payment', 'Ready for Payment');
        $status_tone = 'tone-sky';
        break;
    case "rejected":
        $status_label = __('rejected');
        foreach ($approval_chain as $step) {
            if ($step['status'] == 'rejected') {
                $rejected_by_name = parseName($step['approver_name']);
                $status_label = __('rejected_by') . " " . $rejected_by_name;
                $rejection_note = $step['note'];
                break;
            }
        }
        $status_tone = 'tone-red';
        break;
    case "paid":
        $status_label = __('payment_paid');
        $status_tone = 'tone-green';
        break;
    case "cancelled":
        $status_label = __('cancelled', 'Cancelled');
        $status_tone = 'tone-slate';
        break;
    // --- Fallback for Old Statuses ---
    case "pending_dept_manager_approval":
        $status_label = __('pending_department_manager_approval');
        $status_tone = 'tone-amber';
        break;
    case "pending_finance_approval":
        $status_label = __('pending_finance_approval');
        $status_tone = 'tone-amber';
        break;
    case "pending_gm_approval":
        $status_label = __('pending_general_manager_approval');
        $status_tone = 'tone-amber';
        break;
    default:
        $status_label = __('unknown_status') . ": " . $current_status_get;
        $status_tone = 'tone-red';
}

// Progress stepper: Created > Approval > Approved > Payment assigned > Paid.
// $steps_done = last completed index, $steps_current = active index, $steps_failed = rejected index.
$steps_done = -1; $steps_current = -1; $steps_failed = -1;
switch ($current_status_get) {
    case 'draft':            $steps_current = 0; break;
    case 'approved':         $steps_done = 2; $steps_current = 3; break;
    case 'pending_payment':  $steps_done = 3; $steps_current = 4; break;
    case 'paid':             $steps_done = 4; break;
    case 'rejected':         $steps_done = 0; $steps_failed = 1; break;
    case 'cancelled':        break;
    default:                 $steps_done = 0; $steps_current = 1; // pending_approval + legacy pending statuses
}
$progress_steps = [
    ['icon' => 'mdi mdi-file-document', 'label' => __('created', 'Created')],
    ['icon' => 'mdi mdi-account-check',      'label' => $steps_failed === 1 ? __('rejected') : __('approval', 'Approval')],
    ['icon' => 'mdi mdi-seal',     'label' => __('approved')],
    ['icon' => 'mdi mdi-cash',       'label' => __('payment_assigned', 'Payment Assigned')],
    ['icon' => 'mdi mdi-cash-usd',                 'label' => __('paid_status')],
];

// Who holds the request right now (pending approval only)
$waiting_for_name = '';
foreach ($approval_chain as $step) {
    if ($step['status'] == 'pending') {
        $waiting_for_name = parseName($step['approver_name']);
        break;
    }
}

// Initials for avatar circles
$sr_initials = function ($name) {
    $parts = preg_split('/\s+/', trim((string)$name), -1, PREG_SPLIT_NO_EMPTY);
    if (!$parts) return '?';
    $first = mb_substr($parts[0], 0, 1);
    $second = isset($parts[1]) ? mb_substr($parts[1], 0, 1) : '';
    return mb_strtoupper($first . $second);
};

// Get Payment Details if Paid
$payment_details = null;
if ($current_status_get == 'paid') {
    $payment_query = mysqli_query($conDB, "SELECT * FROM `smt_payment` WHERE `inv_no` = '".escape_string($invnoget)."' ORDER BY `id` DESC LIMIT 1");
    if($payment_query && mysqli_num_rows($payment_query) > 0){
        $payment_details = mysqli_fetch_assoc($payment_query);
    }
}

// Check who is the current approver
$current_pending_approver_id = null;
if ($current_status_get == 'pending_approval') {
    $current_pending_approver_id = get_current_approver($conDB, $invnoget, 'smart_request');
}

// Get Finance employees for the Payable By dropdown
 require_once __DIR__ . '/includes/helper_functions.php'; // Ensure getFinancePersonnel is loaded
$finance_employees = getFinancePersonnel($conDB);

// Get HR employees for the CC dropdown
 require_once __DIR__ . '/includes/helper_functions.php'; // Ensure getHRPersonnel is loaded
$hr_employees = getHRPersonnel($conDB); // Dept ID 5 is now the default

// Status history (smt_request_status)
$history = [];
$history_query = mysqli_query($conDB, "SELECT status, note, emp_name, created_at FROM smt_request_status WHERE inv_no = '" . escape_string($invnoget) . "' ORDER BY created_at DESC");
if ($history_query) {
    while ($h_row = mysqli_fetch_assoc($history_query)) {
        $history[] = $h_row;
    }
}

// Line items
$line_items = [];
$getdataloop = mysqli_query($conDB, "SELECT * FROM `smart_request` WHERE `inv_no`='" . escape_string($_GET['id']) . "' ");
if ($getdataloop) {
    while ($rec = mysqli_fetch_assoc($getdataloop)) {
        $line_items[] = $rec;
    }
}

// Attachments
$attachments = [];
$queryempdocu = mysqli_query($conDB, "SELECT * FROM `smt_attachment` WHERE `inv_no`='" . escape_string($_GET['id']) . "' ");
if ($queryempdocu) {
    while ($recempdoc = mysqli_fetch_assoc($queryempdocu)) {
        $attachments[] = $recempdoc;
    }
}

$is_draft_owner = ($current_status_get == "draft" && $empid == $emp_id_get);
// Attachment upload choice: only the creator, in draft, while there is room (max 5 files)
$can_add_attachment = $is_draft_owner && count($attachments) <= 5;

// --- ACTION BOX LOGIC ---
$show_submit_button = false; // Creator submits the draft and defines the approval chain
$show_action_box = false; // Current approver approves / rejects
$show_assign_payer_box = false; // Finance Manager assigns who pays
$show_process_payment_button = false; // Assigned payer records the payment

if ($is_draft_owner) {
    $show_submit_button = true;
} elseif ($current_status_get == 'pending_approval' && $empid == $current_pending_approver_id) {
    $show_action_box = true;
} elseif ($current_status_get == 'approved' && $emptypeget == 'Manager' && $user_dept == 2 && !$payable_by_emp_id_get) { // Only Finance Manager can assign
    $show_assign_payer_box = true;
} elseif (($current_status_get == 'approved' || $current_status_get == 'pending_payment') && $empid == $payable_by_emp_id_get) { // Only assigned user can pay
    $show_process_payment_button = true;
}

$riyal = '<i class="icon-saudi_riyal"></i>';
$fmt = function ($n) { return number_format((float)$n, 2); };

?>

<!doctype html>
<html lang="<?= $current_lang ?? 'en' ?>" <?= ($is_rtl ?? false) ? 'dir="rtl"' : '' ?>>

<head>
    <meta charset="utf-8" />
    <title><?= $site_title ?> - <?= htmlspecialchars($sub_title_get) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta content="Anees Afzal" name="author" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />

    <!-- App favicon -->
    <link rel="shortcut icon" href="<?=get_setting($conDB, 'favicon')?>">

    <link href="./plugins/select2/css/select2.min.css" rel="stylesheet" type="text/css" />

    <!-- App css -->
    <link href="assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/icons.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/metismenu.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/style.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/style_dark.css" rel="stylesheet" type="text/css" />
    <link href="./plugins/dropzone/dropzone.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/smart_request.css?v=<?= @filemtime(__DIR__ . '/assets/css/smart_request.css') ?>" rel="stylesheet" type="text/css" />
    <script src="assets/js/modernizr.min.js"></script>
    <!-- Sweet Alert -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style type="text/css">
        .swal-wide { width: 850px !important; }
        .customSweetAlertMLR { margin-left: auto; margin-right: auto; }
    </style>
    <?php if ($is_rtl): ?>
        <link href="assets/css/style_rtl.css" rel="stylesheet" type="text/css" />
    <?php endif; ?>
    <script>
        window.departmentMap = <?= json_encode($department_map ?? []) ?>;
        window.lang = <?= json_encode($GLOBALS['translations'] ?? []) ?>;
        window.currentUserDept = <?= json_encode($user_dept) ?>;
    </script>
</head>

<body class="enlarged" data-keep-enlarged="true">

    <!-- Begin page -->
    <div id="wrapper">

        <!-- ========== Left Sidebar Start ========== -->
        <div class="left side-menu">
            <div class="slimscroll-menu" id="remove-scroll">
                <!-- LOGO -->
                <div class="topbar-left">
                    <a href="dashboard.php" class="logo">
                        <span><img src="<?=get_setting($conDB, 'logo')?>" alt="" height="22"></span>
                        <i><img src="<?=get_setting($conDB, 'white_logo')?>" alt="" height="28"></i>
                    </a>
                </div>
                <!--- Sidemenu -->
                <?php include("./includes/main_menu.php"); ?>
                <div class="clearfix"></div>
            </div>
        </div>
        <!-- Left Sidebar End -->

        <div class="content-page">
            <!-- Top Bar Start -->
            <?php include("./includes/topbar.php"); ?>
            <!-- Top Bar End -->

            <!-- Start Page content -->
            <div class="content sr-page">
                <div class="container-fluid">
                    <form action="open_request.php?id=<?= htmlspecialchars($_GET['id']) ?>" method="post" enctype="multipart/form-data" id="srRequestForm">
                        <input type="hidden" name="inv_no" value="<?= htmlspecialchars($invnoget) ?>" />

                        <?= $msg ?? '' ?>

                        <!-- ===== Header ===== -->
                        <div class="sr-card sr-hero">
                            <a href="./all_requests.php" class="sr-back"><i class="mdi mdi-arrow-left"></i> <?= __('all_smart_requests_header') ?></a>
                            <div class="sr-hero-top">
                                <div style="min-width: 0; flex: 1 1 420px;">
                                    <div class="sr-hero-tags">
                                        <span class="sr-chip sr-mono"><i class="mdi mdi-pound"></i><?= htmlspecialchars($invnoget) ?></span>
                                        <span class="sr-pill sr-pill-lg <?= $status_tone ?>"><span class="sr-dot"></span><?= htmlspecialchars($status_label) ?></span>
                                        <?php if ($waiting_for_name && $current_status_get == 'pending_approval'): ?>
                                            <span class="sr-card-sub"><i class="mdi mdi-timer-sand"></i> <?= __('waiting_for', 'Waiting for') ?> <strong><?= htmlspecialchars($waiting_for_name) ?></strong></span>
                                        <?php endif; ?>
                                    </div>
                                    <h1 class="sr-hero-title"><?= htmlspecialchars($sub_title_get) ?></h1>
                                </div>
                                <div class="sr-hero-amount">
                                    <div class="sr-amount-label"><?= __('grand_total') ?></div>
                                    <div class="sr-amount-value"><?= $fmt($gtotal) ?> <?= $riyal ?></div>
                                    <div class="sr-card-sub"><?= count($line_items) ?> <?= count($line_items) == 1 ? __('item', 'item') : __('items', 'items') ?></div>
                                </div>
                            </div>

                            <div class="sr-meta">
                                <div class="sr-meta-item">
                                    <div class="sr-meta-label"><i class="mdi mdi-calendar"></i> <?= __('invoice_date') ?></div>
                                    <div class="sr-meta-value"><?= $created_at_get ? date("d M Y", strtotime($created_at_get)) : '-' ?></div>
                                </div>
                                <div class="sr-meta-item">
                                    <div class="sr-meta-label"><i class="mdi mdi-tag-outline"></i> <?= __('sub_type') ?></div>
                                    <div class="sr-meta-value" title="<?= htmlspecialchars($sub_type_get) ?>"><?= htmlspecialchars($sub_type_get ?: '-') ?></div>
                                </div>
                                <div class="sr-meta-item">
                                    <div class="sr-meta-label"><i class="mdi mdi-domain"></i> <?= __('department') ?></div>
                                    <div class="sr-meta-value" title="<?= htmlspecialchars($dep_nme_get) ?>"><?= htmlspecialchars($dep_nme_get ?: '-') ?></div>
                                </div>
                                <div class="sr-meta-item">
                                    <div class="sr-meta-label"><i class="mdi mdi-account-outline"></i> <?= __('prepared_by') ?></div>
                                    <div class="sr-meta-value"><?= htmlspecialchars($prep_by_get ?: '-') ?></div>
                                </div>
                            </div>

                            <?php if ($remarks_get): ?>
                                <div class="sr-remarks"><strong><?= __('remarks') ?>:</strong> <?= nl2br(htmlspecialchars($remarks_get)) ?></div>
                            <?php endif; ?>

                            <!-- Progress -->
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
                                        <div class="sr-step-label"><?= htmlspecialchars($ps['label']) ?></div>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <div class="sr-hero-actions hidden-print">
                                <?php if ($is_draft_owner): ?>
                                    <button type="button" class="sr-btn sr-btn-sm addLineBtn"><i class="mdi mdi-plus"></i> <?= __('add_line') ?></button>
                                    <a href="javascript:void(0);" class="sr-btn sr-btn-sm editReqAttr"
                                       data-sub_type="<?= htmlspecialchars($sub_type_get) ?>"
                                       data-sub_title="<?= htmlspecialchars($sub_title_get) ?>"
                                       data-remarks="<?= htmlspecialchars($remarks_get) ?>"
                                       data-request_date="<?= $created_at_get ? htmlspecialchars(date('Y-m-d', strtotime($created_at_get))) : '' ?>"
                                       data-id="<?= htmlspecialchars($invnoget) ?>"><i class="mdi mdi-pencil"></i> <?= __('edit_request_details') ?></a>
                                <?php endif; ?>
                                <span class="sr-spacer"></span>
                                <a href="smt_print.php?id=<?= htmlspecialchars($invnoget) ?>" class="sr-btn sr-btn-sm js-print-link" target="_blank"><i class="mdi mdi-printer"></i> <?= __('print') ?></a>
                                <?php if ($show_process_payment_button): ?>
                                    <button type="button" class="sr-btn sr-btn-sm sr-btn-success" id="processPaymentBtn"><i class="mdi mdi-cash-multiple"></i> <?= __('process_payment') ?></button>
                                <?php endif; ?>
                            </div>
                        </div>

                        <?php if ($current_status_get == 'rejected' && !empty($rejection_note)): ?>
                            <div class="sr-notice tone-red" role="alert">
                                <i class="mdi mdi-close-octagon-outline"></i>
                                <div><?= __('request_rejected_reason') ?> <strong>"<?= htmlspecialchars($rejection_note) ?>"</strong><?php if ($rejected_by_name): ?> &mdash; <?= htmlspecialchars($rejected_by_name) ?><?php endif; ?></div>
                            </div>
                        <?php endif; ?>

                        <div class="row">
                            <!-- ===== Main column ===== -->
                            <div class="col-xl-8">

                                <!-- Line items -->
                                <div class="sr-card">
                                    <div class="sr-card-head">
                                        <h5 class="sr-card-title"><i class="mdi mdi-format-list-bulleted"></i> <?= __('items', 'Items') ?> <span class="sr-count"><?= count($line_items) ?></span></h5>
                                        <?php if ($is_draft_owner): ?>
                                            <button type="button" class="sr-btn sr-btn-sm sr-btn-ghost addLineBtn"><i class="mdi mdi-plus"></i> <?= __('add_line') ?></button>
                                        <?php endif; ?>
                                    </div>
                                    <div class="table-responsive">
                                        <table class="sr-lines">
                                            <thead>
                                                <tr>
                                                    <th style="width: 44px;">#</th>
                                                    <th><?= __('description_item_name_invoice_num') ?></th>
                                                    <th class="num"><?= __('quantity') ?></th>
                                                    <th class="num"><?= __('unit_cost') ?></th>
                                                    <th class="num"><?= __('item_value') ?></th>
                                                    <th class="num"><?= __('vat_val') ?></th>
                                                    <th class="num"><?= __('discount') ?></th>
                                                    <th class="num"><?= __('total') ?></th>
                                                    <?php if ($is_draft_owner): ?><th style="width: 80px;"></th><?php endif; ?>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (empty($line_items)): ?>
                                                    <tr><td colspan="<?= $is_draft_owner ? 9 : 8 ?>" class="text-center text-muted py-4"><?= __('no_data_available_in_table') ?></td></tr>
                                                <?php endif; ?>
                                                <?php $x = 1; foreach ($line_items as $rec): ?>
                                                    <tr>
                                                        <td><span class="sr-line-no"><?= $x++ ?></span></td>
                                                        <td>
                                                            <div class="sr-line-name"><?= htmlspecialchars($rec["item_name"]) ?></div>
                                                            <div class="sr-line-meta">
                                                                <?php if (!empty($rec["reference"])): ?><span><i class="mdi mdi-pound"></i><?= htmlspecialchars($rec["reference"]) ?></span><?php endif; ?>
                                                                <?php if (!empty($rec["location"])): ?><span><i class="mdi mdi-map-marker-outline"></i><?= htmlspecialchars($rec["location"]) ?></span><?php endif; ?>
                                                            </div>
                                                        </td>
                                                        <td class="num"><?= htmlspecialchars($rec["quantity"]) ?></td>
                                                        <td class="num"><?= $fmt($rec["product_price"]) ?></td>
                                                        <td class="num"><?= $fmt($rec["itmvalue"]) ?></td>
                                                        <td class="num"><?= $fmt($rec["vat_val"]) ?><div class="sr-card-sub"><?= htmlspecialchars($rec["vat_rate"]) ?>%</div></td>
                                                        <td class="num"><?= (float)$rec["idiscount"] > 0 ? $fmt($rec["idiscount"]) : '<span class="text-muted">&ndash;</span>' ?></td>
                                                        <td class="num"><span class="sr-money"><?= $fmt($rec["total_cost"]) ?></span></td>
                                                        <?php if ($is_draft_owner): ?>
                                                            <td class="num">
                                                                <span class="sr-line-actions">
                                                                    <a href="javascript:void(0);" class="sr-btn sr-btn-sm sr-btn-icon editItemLineAttr" title="<?= __('edit', 'Edit') ?>" data-id="<?= $rec['id'] ?>" data-i_item_name="<?= htmlspecialchars($rec['item_name']) ?>" data-i_reference="<?= htmlspecialchars($rec['reference'] ?? '') ?>" data-i_quantity="<?= $rec['quantity'] ?>" data-i_product_price="<?= $rec['product_price'] ?>" data-i_vat_rate="<?= $rec['vat_rate'] ?>" data-i_idiscount="<?= $rec['idiscount'] ?>" data-i_itmvalue="<?= $rec['itmvalue'] ?>" data-i_vat_val="<?= $rec['vat_val'] ?>" data-i_amount="<?= $rec['amount'] ?>" data-i_total_cost="<?= $rec['total_cost'] ?>" data-i_location="<?= htmlspecialchars($rec['location']) ?>">
                                                                        <i class="mdi mdi-pencil"></i>
                                                                    </a>
                                                                    <a href="javascript:void(0);" class="sr-btn sr-btn-sm sr-btn-icon deleteAjax" title="<?= __('delete', 'Delete') ?>" data-id="<?= $rec["id"] ?>" data-tbl="smart_request" data-file="0">
                                                                        <i class="mdi mdi-delete text-danger"></i>
                                                                    </a>
                                                                </span>
                                                            </td>
                                                        <?php endif; ?>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="sr-totals">
                                        <dl>
                                            <div class="row-t"><dt><?= __('net_total_without_vat') ?></dt><dd><?= $fmt($total_cost_get) ?> <?= $riyal ?></dd></div>
                                            <div class="row-t"><dt><?= __('vat_15_percent') ?></dt><dd><?= $fmt($vat_get) ?> <?= $riyal ?></dd></div>
                                            <div class="row-t"><dt><?= __('total_before_disc') ?></dt><dd><?= $fmt($total) ?> <?= $riyal ?></dd></div>
                                            <?php if ((float)$discount_get > 0): ?>
                                                <div class="row-t"><dt><?= __('discount') ?></dt><dd>&minus; <?= $fmt($discount_get) ?> <?= $riyal ?></dd></div>
                                            <?php endif; ?>
                                            <div class="row-t grand"><dt><?= __('grand_total') ?></dt><dd><?= $fmt($gtotal) ?> <?= $riyal ?></dd></div>
                                            <!-- Opening cash: on-screen helper only (no name attribute, never saved);
                                                 passed to smt_print.php in the URL so the printout can show it. -->
                                            <div class="sr-cash">
                                                <div class="row-t sr-cash-input">
                                                    <dt><label for="srOpeningCash"><?= __('opening_cash', 'Opening Cash') ?></label></dt>
                                                    <dd><input type="number" step="0.01" min="0" id="srOpeningCash" class="form-control" placeholder="0.00" inputmode="decimal" autocomplete="off"></dd>
                                                </div>
                                                <div class="row-t sr-cash-balance" id="srCashBalanceRow" style="display: none;">
                                                    <dt><?= __('balance', 'Balance') ?></dt>
                                                    <dd><span id="srCashBalance">0.00</span> <?= $riyal ?></dd>
                                                </div>
                                            </div>
                                        </dl>
                                    </div>
                                    <?php if ($gtotal > 0 && function_exists('getSaudiCurrency')): ?>
                                        <div class="sr-amount-words"><?= htmlspecialchars(ucfirst(trim(getSaudiCurrency($gtotal)))) ?></div>
                                    <?php endif; ?>
                                </div>

                                <!-- Attachments -->
                                <div class="sr-card">
                                    <div class="sr-card-head">
                                        <h5 class="sr-card-title"><i class="mdi mdi-paperclip"></i> <?= __('existing_attachments') ?> <span class="sr-count"><?= count($attachments) ?></span></h5>
                                    </div>
                                    <div class="sr-card-body">
                                        <?php if ($can_add_attachment): ?>
                                            <label class="sr-field-label"><?= __('attachment') ?> <span class="text-danger">*</span></label>
                                            <div class="sr-attach-choice mb-3">
                                                <label class="sr-choice">
                                                    <input type="radio" id="inlineRadio3" value="yes" name="attach" onclick="showAttachment()" required data-parsley-errors-container="#attach-error-container">
                                                    <span><i class="mdi mdi-paperclip"></i> <?= __('have_attachments') ?></span>
                                                </label>
                                                <label class="sr-choice">
                                                    <input type="radio" id="inlineRadio2" value="no" name="attach" onclick="hideAttachment()">
                                                    <span><i class="mdi mdi-clippy"></i> <?= __('no_attachment') ?></span>
                                                </label>
                                            </div>
                                            <div id="attach-error-container" class="text-danger small mb-2"></div>
                                            <div class="sr-qr attachmentDIV" style="display: none;">
                                                <img src="qrconfig_smartrequest.php?id=<?= htmlspecialchars($_GET['id']) ?>" alt="QR" />
                                                <div>
                                                    <p><?= __('scan_qr_for_attachments') ?></p>
                                                    <a href="javascript:void(0);" class="sr-btn sr-btn-sm sr-btn-primary smt_attachment" data-attach="ok" data-inv_no="<?= htmlspecialchars($invnoget) ?>">
                                                        <i class="mdi mdi-cloud-upload"></i> <?= __('upload_documents') ?>
                                                    </a>
                                                </div>
                                            </div>
                                            <?php if (!empty($attachments)): ?><div class="mb-3"></div><?php endif; ?>
                                        <?php endif; ?>

                                        <?php if (empty($attachments)): ?>
                                            <div class="sr-empty-files"><i class="mdi mdi-file-hidden"></i> <?= __('no_attachment') ?></div>
                                        <?php else: ?>
                                            <div class="sr-files">
                                                <?php foreach ($attachments as $recempdoc):
                                                    $id_empdoc_get = $recempdoc["id"];
                                                    $attachment_get = $recempdoc["attachment"];
                                                    $docu_ext_get = strtolower((string)$recempdoc["docu_ext"]);
                                                    $doc_date_reg_get = date('d M Y, h:ia', strtotime($recempdoc["created_at"]));
                                                    $fileIcon = ($docu_ext_get == "pdf" ? "pdf" : ($docu_ext_get == "xls" || $docu_ext_get == "xlsx" ? "excel" : ($docu_ext_get == "tif" ? "tif" : ($docu_ext_get == "doc" || $docu_ext_get == "docx" ? "word" : ""))));
                                                    $is_doc = in_array($docu_ext_get, ["pdf", "xls", "xlsx", "doc", "docx", "tif"]);
                                                ?>
                                                    <div class="sr-file">
                                                        <?php if ($is_draft_owner): ?>
                                                            <a href="javascript:void(0);" class="sr-file-del deleteAjax" title="<?= __('delete', 'Delete') ?>" data-id="<?= $id_empdoc_get ?>" data-tbl="smt_attachment" data-file="1" data-column="attachment"><i class="mdi mdi-close"></i></a>
                                                        <?php endif; ?>
                                                        <div class="sr-file-thumb showAttach" role="button" tabindex="0" data-id="<?= $id_empdoc_get ?>" data-i_attachment="<?= htmlspecialchars($attachment_get) ?>">
                                                            <?php if ($is_doc): ?>
                                                                <img class="sr-file-icon" src="assets/images/file_icons/<?= $fileIcon ?: 'blank' ?>.svg" alt="<?= htmlspecialchars($docu_ext_get) ?>" />
                                                            <?php else: ?>
                                                                <img src="./assets/smt_attachment/<?= htmlspecialchars($attachment_get) ?>" alt="<?= __('attachment') ?>" loading="lazy" />
                                                            <?php endif; ?>
                                                        </div>
                                                        <div class="sr-file-foot">
                                                            <div style="min-width: 0;">
                                                                <div class="sr-file-name" title="<?= htmlspecialchars($attachment_get) ?>"><?= strtoupper(htmlspecialchars($docu_ext_get ?: 'file')) ?></div>
                                                                <div class="sr-file-date"><?= $doc_date_reg_get ?></div>
                                                            </div>
                                                            <a href="./downloadFile.php?file=./assets/smt_attachment/<?= urlencode($attachment_get) ?>" class="sr-file-dl" title="<?= __('download', 'Download') ?>"><i class="mdi mdi-download"></i></a>
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

                                <?php if ($show_submit_button): ?>
                                    <!-- Creator: build the approval chain and submit -->
                                    <div class="sr-card sr-action-card">
                                        <div class="sr-card-head">
                                            <h5 class="sr-card-title"><i class="mdi mdi-send"></i> <?= __('submit_for_approval') ?></h5>
                                        </div>
                                        <div class="sr-card-body">
                                            <label class="sr-field-label" for="approver-select"><?= __('select_approvers_in_order') ?></label>
                                            <div class="sr-add-row">
                                                <select class="form-control" id="approver-select" data-placeholder="<?= __('select_approver') ?>">
                                                    <option value=""></option>
                                                    <?php foreach ($potential_approvers as $employee): ?>
                                                        <option value="<?= $employee['emp_id'] ?>" data-type="<?= htmlspecialchars($employee['user_type']) ?>" data-dept="<?= htmlspecialchars($employee['dept']) ?>">
                                                            <?= parseName($employee['name']) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <button class="sr-btn sr-btn-primary" type="button" id="add-approver-btn"><i class="mdi mdi-plus"></i> <?= __('add') ?></button>
                                            </div>
                                            <ol class="sr-approver-list" id="approver-list-container"></ol>
                                            <div class="sr-approver-empty" id="approver-empty"><i class="mdi mdi-account-multiple-plus"></i> <?= __('approval_chain_not_defined_yet') ?></div>
                                            <div id="approver-error-container" class="text-danger"></div>
                                            <div class="sr-hint"><i class="mdi mdi-information-outline"></i> <?= __('approver_order_hint', 'Approvers act one after another, in this order. Use the arrows to reorder.') ?></div>
                                            <button type="submit" name="submit" value="1" class="sr-btn sr-btn-primary sr-btn-block mt-3"><i class="mdi mdi-send"></i> <?= __('submit_for_approval') ?></button>
                                        </div>
                                    </div>

                                <?php elseif ($show_action_box): ?>
                                    <!-- Current approver: approve / reject -->
                                    <div class="sr-card sr-action-card">
                                        <div class="sr-card-head">
                                            <h5 class="sr-card-title"><i class="mdi mdi-gavel"></i> <?= __('your_decision', 'Your decision') ?></h5>
                                            <span class="sr-pill tone-amber"><?= __('level') ?> <?= (int)$current_approval_level_get ?></span>
                                        </div>
                                        <div class="sr-card-body">
                                            <div class="sr-attach-choice">
                                                <label class="sr-choice sr-choice-approve">
                                                    <input type="radio" name="status" value="approve" class="sr-decision" required data-parsley-errors-container="#decision-error-container">
                                                    <span><i class="mdi mdi-check-circle-outline"></i> <?= __('approve') ?></span>
                                                </label>
                                                <label class="sr-choice sr-choice-reject">
                                                    <input type="radio" name="status" value="reject" class="sr-decision">
                                                    <span><i class="mdi mdi-close-circle-outline"></i> <?= __('reject') ?></span>
                                                </label>
                                            </div>
                                            <div id="decision-error-container" class="text-danger small mt-1"></div>

                                            <div class="mt-3" id="RejectDIV">
                                                <label class="sr-field-label" for="RejectInput">
                                                    <span class="note-label-approve"><?= __('note_optional') ?></span>
                                                    <span class="note-label-reject" style="display: none;"><?= __('rejection_note') ?> <span class="text-danger">*</span></span>
                                                </label>
                                                <textarea class="form-control" name="note" id="RejectInput" rows="3"></textarea>
                                            </div>

                                            <?php if ($user_dept == 5): // HR can CC colleagues on approval ?>
                                                <div class="mt-3" id="cc_hr_select_div" style="display: none;">
                                                    <label class="sr-field-label" for="cc_hr_select"><?= __('cc_hr_employees_optional') ?></label>
                                                    <select class="form-control" name="cc_hr_employees[]" id="cc_hr_select" multiple="multiple">
                                                        <?php foreach ($hr_employees as $hr_emp): ?>
                                                            <?php if ($hr_emp['emp_id'] == $empid) continue; // Skip self ?>
                                                            <option value="<?= $hr_emp['emp_id'] ?>"><?= parseName($hr_emp['name']) ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                            <?php endif; ?>

                                            <button type="submit" name="submit" value="1" class="sr-btn sr-btn-primary sr-btn-block mt-3" id="decisionSubmit"><i class="mdi mdi-check-all"></i> <?= __('submit_action') ?></button>
                                        </div>
                                    </div>

                                <?php elseif ($show_assign_payer_box): ?>
                                    <!-- Finance Manager: assign who pays -->
                                    <div class="sr-card sr-action-card">
                                        <div class="sr-card-head">
                                            <h5 class="sr-card-title"><i class="mdi mdi-cash"></i> <?= __('assign_payable_to') ?></h5>
                                        </div>
                                        <div class="sr-card-body">
                                            <label class="sr-field-label" for="payable_by_emp_id"><?= __('select_finance_employee') ?> <span class="text-danger">*</span></label>
                                            <select class="form-control" name="payable_by_emp_id" id="payable_by_emp_id" required data-parsley-errors-container="#payer-error-container">
                                                <option value=""><?= __('select_finance_employee') ?></option>
                                                <?php foreach ($finance_employees as $fin_emp): ?>
                                                    <option value="<?= $fin_emp['emp_id'] ?>" <?= ($fin_emp['emp_id'] == $payable_by_emp_id_get) ? 'selected' : '' ?>><?= parseName($fin_emp['name']) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            <div id="payer-error-container" class="text-danger small mt-1"></div>
                                            <button type="submit" name="assign_payer_submit" value="1" class="sr-btn sr-btn-primary sr-btn-block mt-3"><i class="mdi mdi-account-check"></i> <?= __('assign_payer_button') ?></button>
                                        </div>
                                    </div>

                                <?php elseif ($show_process_payment_button): ?>
                                    <!-- Assigned payer -->
                                    <div class="sr-card sr-action-card">
                                        <div class="sr-card-head">
                                            <h5 class="sr-card-title"><i class="mdi mdi-cash-multiple"></i> <?= __('process_payment') ?></h5>
                                        </div>
                                        <div class="sr-card-body">
                                            <p class="sr-card-sub mb-3"><?= __('payment_assigned_to_you', 'This request is assigned to you for payment. Upload the payment receipt to close it.') ?></p>
                                            <button type="button" class="sr-btn sr-btn-success sr-btn-block" onclick="$('#processPaymentBtn').trigger('click');"><i class="mdi mdi-cash-usd"></i> <?= __('process_payment') ?> &middot; <?= $fmt($gtotal) ?> <?= $riyal ?></button>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <?php if ($payment_details || $assigned_payer_name): ?>
                                    <!-- Payment -->
                                    <div class="sr-card">
                                        <div class="sr-card-head">
                                            <h5 class="sr-card-title"><i class="mdi mdi-credit-card"></i> <?= __('payment_information') ?></h5>
                                            <?php if ($payment_details): ?><span class="sr-pill tone-green"><span class="sr-dot"></span><?= __('paid_status') ?></span><?php endif; ?>
                                        </div>
                                        <div class="sr-card-body">
                                            <dl class="sr-kv">
                                                <?php if ($assigned_payer_name): ?>
                                                    <div class="row-kv"><dt><?= __('payable_assigned_to') ?></dt><dd><?= htmlspecialchars(parseName($assigned_payer_name)) ?></dd></div>
                                                <?php endif; ?>
                                                <?php if ($payment_details): ?>
                                                    <div class="row-kv"><dt><?= __('paid_amount') ?></dt><dd><?= $fmt($payment_details['paid_amount']) ?> <?= $riyal ?></dd></div>
                                                    <div class="row-kv"><dt><?= __('paid_by') ?></dt><dd><?= htmlspecialchars($payment_details['paid_by_name']) ?></dd></div>
                                                    <div class="row-kv"><dt><?= __('on') ?></dt><dd><?= date('d M Y, H:i', strtotime($payment_details['created_at'])) ?></dd></div>
                                                    <?php if ($payment_details['note']): ?>
                                                        <div class="row-kv"><dt><?= __('note') ?></dt><dd><?= htmlspecialchars($payment_details['note']) ?></dd></div>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            </dl>
                                            <?php if ($payment_details && !empty($payment_details['payment_invoice'])): ?>
                                                <a href="assets/smt_payment_invoices/<?= htmlspecialchars($payment_details['payment_invoice']) ?>" target="_blank" class="sr-btn sr-btn-sm sr-btn-block mt-3"><i class="mdi mdi-eye-outline"></i> <?= __('view_payment_invoice') ?></a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <!-- Approval chain -->
                                <div class="sr-card">
                                    <div class="sr-card-head">
                                        <h5 class="sr-card-title"><i class="mdi mdi-account-multiple"></i> <?= __('approval_status') ?></h5>
                                        <span class="sr-count"><?= count($approval_chain) ?></span>
                                    </div>
                                    <div class="sr-card-body">
                                        <?php if (empty($approval_chain)): ?>
                                            <div class="sr-empty-files"><?= __('approval_chain_not_defined_yet') ?></div>
                                        <?php else: ?>
                                            <ol class="sr-chain">
                                                <?php foreach ($approval_chain as $step):
                                                    $step_status = $step['status']; // pending | approved | rejected | awaiting
                                                    $step_tone = ['approved' => 'tone-green', 'rejected' => 'tone-red', 'pending' => 'tone-amber'][$step_status] ?? 'tone-slate';
                                                    $action_date = $step['action_date'] ? date('d M Y, H:i', strtotime($step['action_date'])) : '';
                                                ?>
                                                    <li class="sr-chain-item <?= htmlspecialchars($step_status) ?>">
                                                        <span class="sr-chain-marker">
                                                            <?php if ($step_status == 'approved'): ?><i class="mdi mdi-check"></i>
                                                            <?php elseif ($step_status == 'rejected'): ?><i class="mdi mdi-close"></i>
                                                            <?php else: ?><?= htmlspecialchars($sr_initials(parseName($step['approver_name']))) ?><?php endif; ?>
                                                        </span>
                                                        <div class="sr-chain-body">
                                                            <div class="sr-chain-row">
                                                                <span class="sr-chain-name"><?= parseName($step['approver_name']) ?></span>
                                                                <span class="sr-pill <?= $step_tone ?>"><?= __($step_status) ?></span>
                                                            </div>
                                                            <div class="sr-chain-level"><?= __('level') ?> <?= (int)$step['approval_level'] ?><?php if ($action_date): ?> &middot; <?= $action_date ?><?php endif; ?></div>
                                                            <?php if ($step['note']): ?>
                                                                <div class="sr-chain-note"><?= htmlspecialchars($step['note']) ?></div>
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
                                                <?php foreach ($history as $item):
                                                    $status_clean = strtolower($item['status']);
                                                    $h_tone = 'tone-amber'; $h_icon = 'mdi-clock';
                                                    if (strpos($status_clean, 'paid') !== false) {
                                                        $h_tone = 'tone-green'; $h_icon = 'mdi-cash-usd';
                                                    } elseif (strpos($status_clean, 'approved') !== false || strpos($status_clean, 'completed') !== false) {
                                                        $h_tone = 'tone-green'; $h_icon = 'mdi-check';
                                                    } elseif (strpos($status_clean, 'rejected') !== false || strpos($status_clean, 'cancel') !== false) {
                                                        $h_tone = 'tone-red'; $h_icon = 'mdi-close';
                                                    } elseif (strpos($status_clean, 'assigned') !== false) {
                                                        $h_tone = 'tone-sky'; $h_icon = 'mdi-account-switch';
                                                    } elseif (strpos($status_clean, 'draft') !== false || strpos($status_clean, 'created') !== false) {
                                                        $h_tone = 'tone-slate'; $h_icon = 'mdi-file-document';
                                                    }
                                                ?>
                                                    <li>
                                                        <span class="sr-activity-icon <?= $h_tone ?>"><i class="mdi <?= $h_icon ?>"></i></span>
                                                        <div class="sr-activity-body">
                                                            <div class="sr-activity-title"><?= getDisplayName(ucwords(str_replace('_', ' ', $item['status']))) ?></div>
                                                            <?php if (!empty($item['note'])): ?>
                                                                <div class="sr-activity-note"><?= nl2br(htmlspecialchars(getDisplayName($item['note']))) ?></div>
                                                            <?php endif; ?>
                                                            <div class="sr-activity-meta">
                                                                <span><i class="mdi mdi-account-outline"></i> <?= getDisplayName($item['emp_name']) ?></span>
                                                                <span><i class="mdi mdi-clock"></i> <?= date('d M Y, H:i', strtotime($item['created_at'])) ?></span>
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
                    </form>

                    <!-- Attachment preview -->
                    <div class="modal fade" id="srPreviewModal" tabindex="-1" role="dialog" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title"><i class="mdi mdi-paperclip"></i> <?= __('attachment') ?></h5>
                                    <div class="d-flex align-items-center" style="gap: 6px;">
                                        <a class="sr-btn sr-btn-sm zoomFile" href="javascript:void(0);"><i class="mdi mdi-open-in-new"></i> <?= __('make_it_zoom') ?></a>
                                        <a class="sr-btn sr-btn-sm sr-preview-dl" href="javascript:void(0);"><i class="mdi mdi-download"></i></a>
                                        <button type="button" class="sr-btn sr-btn-sm sr-btn-icon sr-btn-ghost" data-dismiss="modal" aria-label="Close"><i class="mdi mdi-close"></i></button>
                                    </div>
                                </div>
                                <div class="modal-body previewImg"></div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
            <footer class="footer"><?= $site_footer ?></footer>
        </div>
    </div>

    <!-- jQuery  -->
    <script src="assets/js/jquery.min.js"></script>
    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/metisMenu.min.js"></script>
    <script src="assets/js/waves.js"></script>
    <script src="assets/js/jquery.slimscroll.js"></script>
    <script type="text/javascript" src="./plugins/parsleyjs/parsley.min.js"></script>
    <script src="./plugins/bootstrap-inputmask/jquery.inputmask.min.js" type="text/javascript"></script>
    <script src="./plugins/autoNumeric/autoNumeric.js" type="text/javascript"></script>
    <script src="./plugins/select2/js/select2.min.js" type="text/javascript"></script>
    <script src="assets/js/jquery.core.js"></script>
    <script src="assets/js/jquery.app.js?t=<?= time() ?>"></script>
    <?php include __DIR__ . '/includes/smart_request_lines_js.php'; ?>

    <script>
        function displayPopup(url) {
           window.open(url, 'popupWindow', 'width=900,height=700,scrollbars=yes');
        }

        function showAttachment() {
            $('.attachmentDIV').slideDown(150);
        }
        function hideAttachment() {
            $('.attachmentDIV').slideUp(150);
        }

        // Attachment preview in a modal
        $(document).on('click keydown', '.showAttach', function(event) {
            if (event.type === 'keydown' && event.key !== 'Enter' && event.key !== ' ') return;
            event.preventDefault();
            var img = $(this).data('i_attachment');
            if (!img || typeof img !== 'string' || img.includes('..') || img.startsWith('/')) {
                console.error("Invalid attachment path");
                return;
            }
            var src = './assets/smt_attachment/' + encodeURIComponent(img);
            $(".previewImg").empty().append($('<iframe>', { src: src, id: 'iFramePreview' }));
            $(".zoomFile").attr("href", "javascript:displayPopup('" + src + "')");
            $(".sr-preview-dl").attr("href", "./downloadFile.php?file=./assets/smt_attachment/" + encodeURIComponent(img));
            $('#srPreviewModal').modal('show');
        });
        $('#srPreviewModal').on('hidden.bs.modal', function () {
            $(".previewImg").empty();
        });

        $(document).ready(function() {
            const $form = $('#srRequestForm');
            $form.parsley();

            const HR_DEPT_ID = 5;

            // Approve / reject: note is required only on reject; HR may CC colleagues on approve
            $('.sr-decision').on('change', function() {
                const selectedAction = $('.sr-decision:checked').val();
                const isReject = selectedAction === 'reject';
                $('#RejectInput').prop('required', isReject);
                $('.note-label-reject').toggle(isReject);
                $('.note-label-approve').toggle(!isReject);
                $('#RejectInput').attr('placeholder', isReject ? <?= json_encode(__('rejection_note')) ?> : '');
                if (isReject) {
                    $('#RejectInput').trigger('focus');
                }
                $('#cc_hr_select_div').toggle(window.currentUserDept == HR_DEPT_ID && selectedAction === 'approve');
                $('#decisionSubmit')
                    .toggleClass('sr-btn-primary', !selectedAction)
                    .toggleClass('sr-btn-success', selectedAction === 'approve')
                    .toggleClass('sr-btn-danger', isReject);
            });

            // Approver select2 with Department + Role badge
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
                var $element = $(approver.element);
                var userType = $element.data('type') || '';
                var deptName = window.departmentMap[$element.data('dept') || ''] || '';
                var roleText = getRoleText(userType);
                var badgeText = deptName ? (deptName + ' ' + roleText) : roleText;
                if (!badgeText) return null;
                return $('<span>', { 'class': 'user-type-badge ' + userType + ' ' + extraClass, text: badgeText });
            }
            function formatApprover(approver) {
                if (!approver.id) { return approver.text; }
                return $('<span class="d-flex align-items-center justify-content-between w-100">')
                    .append($('<span class="select2-option-text">').text(approver.text))
                    .append(approverBadge(approver, ''));
            }
            function formatApproverSelection(approver) {
                if (!approver.id) { return approver.text; }
                return $('<span class="d-flex align-items-center w-100">')
                    .append($('<span class="select2-selection-text">').text(approver.text))
                    .append(approverBadge(approver, 'select2-selection__rendered-badge'));
            }

            $('#approver-select').select2({
                placeholder: $('#approver-select').data('placeholder'),
                allowClear: true,
                width: '100%',
                templateResult: formatApprover,
                templateSelection: formatApproverSelection
            });
            $('#payable_by_emp_id').select2({
                placeholder: <?= json_encode(__('select_finance_employee')) ?>,
                allowClear: true,
                width: '100%'
            });
            $('#cc_hr_select').select2({
                placeholder: <?= json_encode(__('select_employees_to_cc_optional')) ?>,
                allowClear: true,
                width: '100%'
            });

            // Approval chain builder (order = approval level)
            const $approverList = $('#approver-list-container');

            function renumberApprovers() {
                const $items = $approverList.children('.sr-approver-item');
                $items.each(function(i) {
                    $(this).find('.sr-level').text(i + 1);
                    $(this).find('.move-up-btn').prop('disabled', i === 0);
                    $(this).find('.move-down-btn').prop('disabled', i === $items.length - 1);
                });
                $('#approver-empty').toggle($items.length === 0);
                if ($items.length > 0) {
                    $('#approver-error-container').empty();
                }
            }

            $('#add-approver-btn').on('click', function() {
                const $selected = $('#approver-select').find('option:selected');
                const approverId = $selected.val();
                const approverName = $.trim($selected.text());

                if (!approverId) {
                    Swal.fire({ title: <?= json_encode(__('error')) ?>, text: <?= json_encode(__('select_approver_from_list')) ?>, icon: 'warning', allowOutsideClick: false });
                    return;
                }
                if ($approverList.find('input[name="approvers[]"][value="' + approverId + '"]').length) {
                    Swal.fire({ title: <?= json_encode(__('error')) ?>, text: <?= json_encode(__('approver_already_added')) ?>, icon: 'warning', allowOutsideClick: false });
                    return;
                }

                const $item = $('<li class="sr-approver-item approver-tag">').attr('data-id', approverId)
                    .append('<span class="sr-level"></span>')
                    .append($('<span class="sr-approver-name">').text(approverName))
                    .append($('<input type="hidden" name="approvers[]">').val(approverId))
                    .append(
                        '<span class="sr-approver-tools">' +
                            '<button type="button" class="move-up-btn" title="<?= htmlspecialchars(__('move_up', 'Move up'), ENT_QUOTES) ?>"><i class="mdi mdi-chevron-up"></i></button>' +
                            '<button type="button" class="move-down-btn" title="<?= htmlspecialchars(__('move_down', 'Move down'), ENT_QUOTES) ?>"><i class="mdi mdi-chevron-down"></i></button>' +
                            '<button type="button" class="remove-approver-btn" aria-label="<?= htmlspecialchars(__('remove', 'Remove'), ENT_QUOTES) ?>"><i class="mdi mdi-close"></i></button>' +
                        '</span>'
                    );
                $approverList.append($item);

                $('#approver-select').val(null).trigger('change');
                renumberApprovers();
            });

            $approverList.on('click', '.remove-approver-btn', function() {
                $(this).closest('.sr-approver-item').remove();
                renumberApprovers();
            });
            $approverList.on('click', '.move-up-btn', function() {
                const $item = $(this).closest('.sr-approver-item');
                $item.prev('.sr-approver-item').before($item);
                renumberApprovers();
            });
            $approverList.on('click', '.move-down-btn', function() {
                const $item = $(this).closest('.sr-approver-item');
                $item.next('.sr-approver-item').after($item);
                renumberApprovers();
            });

            // Draft submit needs at least one approver
            $form.on('submit', function(e) {
                if ($('#approver-select').length && $approverList.children('.sr-approver-item').length === 0) {
                    e.preventDefault();
                    $('#approver-error-container').html('<div class="small mt-2"><i class="mdi mdi-alert-circle-outline"></i> ' + <?= json_encode(__('select_at_least_one_approver')) ?> + '</div>');
                    $('#approver-select').select2('open');
                }
            });

            if ($approverList.length) {
                renumberApprovers();
            }
        });

        // SweetAlert2 for Payment
        $('#processPaymentBtn').on('click', function(e) {
            e.preventDefault();

            // REMOVED: Check for payable_by_emp_id select (no longer here)

            Swal.fire({
                title: '<?=__('process_payment_for')?> <?= htmlspecialchars($invnoget, ENT_QUOTES) ?>', // Escape inv no
                html: payment_modal_HTML('<?= round($gtotal, 2) ?>'),
                showCancelButton: true,
                confirmButtonText: '<?=__('submit_payment')?>',
                showLoaderOnConfirm: true,
                allowOutsideClick: false,
                width: '50%',
                 didOpen: () => {
                     // REMOVED: logic to append payable_by_emp_id
                 },
                preConfirm: () => {
                    const form = document.getElementById('paymentForm');
                    const formData = new FormData(form);

                    if (!form.checkValidity()) {
                        Swal.showValidationMessage(`<?=__('fill_required_fields_error')?>`);
                         // Trigger Parsley validation display manually if needed
                         $(form).parsley().validate();
                        return false;
                    }

                    // Simple check for file size (e.g., max 5MB)
                     const fileInput = document.getElementById('payment_invoice');
                     if (fileInput.files.length > 0) {
                        const fileSize = fileInput.files[0].size / 1024 / 1024; // in MB
                        if (fileSize > 5) {
                            Swal.showValidationMessage(`<?=__('file_too_large_error')?> (Max 5MB)`);
                            return false;
                        }
                     }


                    return fetch('open_request.php?id=<?= htmlspecialchars($_GET['id'], ENT_QUOTES) ?>', { // Escape GET param
                        method: 'POST',
                        body: formData, // FormData
                    })
                    .then(response => {
                        if (!response.ok) {
                             // Try to get more error details from response body if available
                             return response.text().then(text => { throw new Error(text || response.statusText) });
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.status !== 'success') {
                            throw new Error(data.message);
                        }
                        return data;
                    })
                    .catch(error => {
                        Swal.showValidationMessage(`<?=__('request_failed')?>: ${error.message}`);
                    });
                },
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({ allowOutsideClick: false,
                        title: '<?=__('success')?>!',
                        text: result.value.message,
                        icon: 'success'
                    }).then(() => {
                        location.reload(); // Reload to reflect changes
                    });
                }
            })
        });


        // Opening cash -> balance (grand total deducted). Not saved; only carried to the print link.
        (function() {
            const grandTotal = <?= json_encode(round((float)$gtotal, 2)) ?>;
            const printBase = 'smt_print.php?id=' + encodeURIComponent(<?= json_encode($invnoget) ?>);
            $('#srOpeningCash').on('input change', function() {
                const raw = $.trim($(this).val());
                const opening = parseFloat(raw);
                const hasValue = raw !== '' && !isNaN(opening);
                $('#srCashBalanceRow').toggle(hasValue);
                $('.js-print-link').attr('href', hasValue ? printBase + '&opening=' + encodeURIComponent(opening.toFixed(2)) : printBase);
                if (!hasValue) return;
                const balance = Math.round((opening - grandTotal) * 100) / 100;
                $('#srCashBalance').text(balance.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
                $('#srCashBalanceRow').toggleClass('is-negative', balance < 0);
            });
        })();

        // SweetAlert2 for Adding Line Items (line editor: assets/js/smart_request_lines.js)
        (function() {
            const T = {
                title: <?= json_encode(__('add_line')) ?>,
                newTotal: <?= json_encode(__('new_lines_total', 'New lines total')) ?>,
                requestTotal: <?= json_encode(__('request_total_after_save', 'Request total after save')) ?>,
                more: <?= json_encode(__('more_details', 'More details (Tally / Injazat ID)')) ?>,
                keep: <?= json_encode(__('leave_empty_to_keep', 'Leave empty to keep the current value')) ?>,
                save: <?= json_encode(__('save_lines', 'Save Lines')) ?>,
                cancel: <?= json_encode(__('cancel', 'Cancel')) ?>,
                required: <?= json_encode(__('fill_required_fields_validation')) ?>,
                failed: <?= json_encode(__('request_failed')) ?>
            };
            const currentTotal = <?= json_encode(round((float)$gtotal, 2)) ?>;

            function modalHTML() {
                const L = SRLines.label, E = SRLines.esc;
                return `
                <form id="srAddLineForm" class="sr-page text-left" autocomplete="off" novalidate>
                    <div id="srAddLines" class="sr-addlines"></div>
                    ${SRLines.addButtonHTML()}
                    <details class="sr-addline-extra">
                        <summary>${E(T.more)}</summary>
                        <div class="sr-addline-grid mt-2">
                            <div class="g-half"><label>Tally ID</label><input type="text" name="tally_id" class="form-control" placeholder="${E(T.keep)}"></div>
                            <div class="g-half"><label>Injazat ID</label><input type="text" name="injazat_id" class="form-control" placeholder="${E(T.keep)}"></div>
                        </div>
                    </details>
                    <div class="sr-addline-summary">
                        <div><span>${L('net')}</span><b id="srAddNet">0.00</b></div>
                        <div><span>${L('vat')}</span><b id="srAddVat">0.00</b></div>
                        <div><span>${E(T.newTotal)}</span><b id="srAddTotal">0.00</b></div>
                        <div class="accent"><span>${E(T.requestTotal)}</span><b id="srAddGrand">0.00</b></div>
                    </div>
                </form>`;
            }

            $(document).on('click', '.addLineBtn', function(e) {
                e.preventDefault();
                Swal.fire({
                    title: T.title,
                    html: modalHTML(),
                    width: '1000px',
                    showCancelButton: true,
                    confirmButtonText: '<i class="mdi mdi-content-save"></i> ' + SRLines.esc(T.save),
                    cancelButtonText: T.cancel,
                    confirmButtonColor: APP_COLORS.primary,
                    cancelButtonColor: APP_COLORS.danger_dark,
                    showLoaderOnConfirm: true,
                    allowOutsideClick: false,
                    customClass: { popup: 'sr-addline-popup' },
                    didOpen: () => {
                        const editor = SRLines.bind($('#srAddLineForm'), $('#srAddLines'), function(sum) {
                            $('#srAddNet').text(SRLines.money(sum.net));
                            $('#srAddVat').text(SRLines.money(sum.vat));
                            $('#srAddTotal').text(SRLines.money(sum.total));
                            $('#srAddGrand').text(SRLines.money(currentTotal + sum.total));
                        });
                        SRLines.loadLocations().always(function() { editor.addLine(true); });
                    },
                    preConfirm: () => {
                        const $form = $('#srAddLineForm');
                        if (SRLines.validate($form)) {
                            Swal.showValidationMessage(T.required);
                            return false;
                        }
                        return $.ajax({
                            url: './includes/ajaxFile/ajaxSmartRequest.php',
                            type: 'POST', dataType: 'JSON',
                            data: $form.serialize() + '&' + $.param({ ajaxType: 'request_line_add', inv_no: <?= json_encode($invnoget) ?> })
                        }).then(response => {
                            if (response.type !== 'success') {
                                throw new Error(response.message || 'Save failed');
                            }
                            return response;
                        }).catch(error => {
                            Swal.showValidationMessage(T.failed + ': ' + (error.message || error.statusText || 'Error'));
                        });
                    }
                }).then((result) => {
                    if (result.isConfirmed && result.value) {
                        Swal.fire({ allowOutsideClick: false, title: result.value.title, text: result.value.message, icon: result.value.type, timer: 1600, showConfirmButton: false })
                            .then(() => location.reload());
                    }
                });
            });

            // Old add_line_request.php links redirect here with ?addline=1
            $(function() {
                if (new URLSearchParams(window.location.search).get('addline') === '1' && $('.addLineBtn').length) {
                    const waitSwal = setInterval(function() {
                        if (window.Swal) { clearInterval(waitSwal); $('.addLineBtn').first().trigger('click'); }
                    }, 100);
                }
            });
        })();

        // SweetAlert2 for Editing Request Details
        $(document).on('click', '.editReqAttr', function(e) {
            e.preventDefault();
            const id = $(this).data('id');
            const sub_type = $(this).data('sub_type');
            const sub_title = $(this).data('sub_title');
            const remarks = $(this).data('remarks');
            const request_date = $(this).data('request_date');

            Swal.fire({
                title: '<?=__('update_request_information')?>',
                html: request_details_HTML(),
                showCancelButton: true,
                confirmButtonText: '<?=__('update')?>',
                showLoaderOnConfirm: true,
                width: '50%',
                didOpen: () => {
                    $('#reqid').val(id);
                    $('#sub_title').val(sub_title);
                    $('#remarks').val(remarks);
                    const $requestDate = $('#request_date');
                    if ($.fn.datepicker) {
                        $requestDate.datepicker({
                            format: 'yyyy-mm-dd',
                            autoclose: true,
                            todayHighlight: true
                        });
                        if (request_date) {
                            $requestDate.datepicker('setDate', request_date);
                        }
                    } else {
                        $requestDate.val(request_date);
                    }
                    // AJAX call to populate sub_type dropdown
                    $.ajax({
                        url: './includes/ajaxFile/ajaxSmartRequest.php',
                        dataType: 'JSON', type: 'POST',
                        data: { ajaxType: "sub_type" },
                        success: function(res) {
                            if (res.status == 200) {
                                let options = '<option value=""><?=__('select')?></option>'; // Add select option
                                options += res.data.map(item => `<option value="${item.sub_type}">${item.sub_type}</option>`).join('');
                                $('#sub_type').html(options).val(sub_type); // Use html() to replace, then set value
                            }
                        },
                        error: function(jqXHR, textStatus, errorThrown) {
                             console.error("Error fetching sub types:", textStatus, errorThrown);
                        }
                    });
                },
                preConfirm: () => {
                    const form = $('#submitEditReqForm');
                     // Manually trigger Parsley validation for the modal form
                     const parsleyInstance = form.parsley();
                     if (!parsleyInstance.validate()) {
                         // If validation fails, prevent submission and show messages
                         Swal.showValidationMessage(`<?=__('fill_required_fields_validation')?>`);
                         return false;
                     }
                    return $.ajax({
                        url: './includes/ajaxFile/ajaxSmartRequest.php',
                        type: 'POST', dataType: "JSON",
                        data: form.serialize() + '&' + $.param({ ajaxType: "request_update" }),
                    }).then(response => {
                        if (response.type !== 'success') { // Check response type from server
                            throw new Error(response.message || 'Update failed');
                        }
                        return response;
                    }).catch(error => {
                        Swal.showValidationMessage(`<?=__('request_failed')?>: ${error.message || error.statusText}`)
                    });
                },
                allowOutsideClick: false
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({ allowOutsideClick: false,
                        title: result.value.title,
                        text: result.value.message,
                        icon: result.value.type
                    }).then(() => {
                        if(result.value.type === 'success') location.reload();
                    });
                }
            });
        });

        // SweetAlert2 for Editing Line Item
        $(document).on('click', '.editItemLineAttr', function(e) {
            e.preventDefault();
            // Use .data() consistently and provide defaults
            var id = $(this).data('id');
            var i_item_name = $(this).data('i_item_name') || '';
            var i_reference = $(this).data('i_reference') || '';
            var i_quantity = $(this).data('i_quantity') || 1;
            var i_product_price = $(this).data('i_product_price') || 0;
            var i_vat_rate = $(this).data('i_vat_rate') || <?= get_setting($conDB, 'vat') ?>; // Default VAT rate
            var i_idiscount = $(this).data('i_idiscount') || 0;
            var i_itmvalue = $(this).data('i_itmvalue') || 0;
            var i_vat_val = $(this).data('i_vat_val') || 0;
            var i_amount = $(this).data('i_amount') || 0;
            var i_total_cost = $(this).data('i_total_cost') || 0;
            var i_location = $(this).data('i_location') || '';

            // Determine initial VAT option based on retrieved values
             let initialVatOption = 'exclude'; // Default
             if (parseFloat(i_vat_rate) === 0) {
                 initialVatOption = 'no_vat';
             } else {
                 // Simple check: if amount approx equals itemvalue+vatvalue, it was likely exclude
                 // If amount approx equals itemvalue, it was likely include. Needs tolerance for rounding.
                 const calculatedAmountExclude = parseFloat(i_itmvalue) + parseFloat(i_vat_val);
                 if (Math.abs(parseFloat(i_amount) - calculatedAmountExclude) < 0.01) {
                    initialVatOption = 'exclude';
                 } else if (Math.abs(parseFloat(i_amount) - parseFloat(i_itmvalue)) < 0.01 && parseFloat(i_vat_val) > 0) {
                     initialVatOption = 'include';
                 }
                 // If VAT is 0, it must be no_vat
                 if(parseFloat(i_vat_val) === 0 && parseFloat(i_vat_rate) > 0){
                      //This might indicate an issue, but default to exclude or include based on amount vs itemvalue
                      if (Math.abs(parseFloat(i_amount) - parseFloat(i_itmvalue)) < 0.01) {
                         initialVatOption = 'include'; // Amount equals item value suggests include (price has vat)
                      } else {
                         initialVatOption = 'exclude';
                      }
                 } else if (parseFloat(i_vat_val) === 0 && parseFloat(i_vat_rate) === 0) {
                      initialVatOption = 'no_vat';
                 }

             }


            Swal.fire({
                title: '<?=__('update_line_information')?>',
                html: request_line_HTML(),
                showCancelButton: true,
                confirmButtonColor: APP_COLORS.primary,
                cancelButtonColor: APP_COLORS.danger_dark,
                confirmButtonText: '<?=__('yes_update')?>',
                showLoaderOnConfirm: true,
                allowOutsideClick: false,
                width: '80%',
                didOpen: function() {
                    $('#itemid').val(id);
                    $('.item_name').val(i_item_name);
                    $('.item_reference').val(i_reference);
                    $('.quantity').val(i_quantity);
                    $('.product_price').val(i_product_price);
                    // Set VAT option based on calculation
                    $('.vat_option').val(initialVatOption);
                    // Fields below will be calculated by calculateTotals
                    $('.idiscount').val(i_idiscount);

                    $.ajax({
                        url: './includes/ajaxFile/ajaxLocation.php',
                        dataType: 'JSON', type: 'POST',
                        data: { ajaxType: "section_view" },
                        success: function(res) {
                            if (res.status == 200) {
                                let options = '<option value=""><?=__('select')?></option>';
                                options += res.data.map(item => `<option value="${item.section_name}">${item.section_name}</option>`).join('');
                                $('#location').html(options).val(i_location);
                            }
                        },
                         error: function(jqXHR, textStatus, errorThrown) {
                             console.error("Error fetching locations:", textStatus, errorThrown);
                        }
                    });

                    // Define VAT rate globally or fetch dynamically if needed
                    const DEFAULT_VAT_RATE = <?= get_setting($conDB, 'vat') ?>;

                     function calculateTotals() {
                        var qty = parseFloat($('.quantity').val()) || 0; // Use class selector inside modal
                        var price = parseFloat($('.product_price').val()) || 0;
                        var discount = parseFloat($('.idiscount').val()) || 0;
                        var vatOption = $('.vat_option').val();
                        var vatRate = DEFAULT_VAT_RATE;

                        if (vatOption === 'no_vat') {
                            vatRate = 0;
                        }

                        var itemValue = qty * price; // Base value (price * qty)
                        var preVatValue, vatValue, amount;

                        if (vatOption === 'exclude') {
                            preVatValue = itemValue; // Price entered excludes VAT
                            vatValue = preVatValue * (vatRate / 100);
                            amount = preVatValue + vatValue; // Total including VAT before item discount
                        } else if (vatOption === 'include') {
                            amount = itemValue; // Price entered includes VAT
                            preVatValue = amount / (1 + (vatRate / 100));
                            vatValue = amount - preVatValue;
                        } else { // 'no_vat'
                             preVatValue = itemValue;
                             vatValue = 0;
                             amount = preVatValue; // Total is same as item value
                        }

                        var total = amount - discount; // Apply item discount to the VAT-inclusive amount

                        $('.itmvalue').val(preVatValue.toFixed(2)); // Value before VAT
                        $('.vat_rate').val(vatRate); // Update VAT rate display
                        $('.vat_val').val(vatValue.toFixed(2));
                        $('.amount').val(amount.toFixed(2)); // Value including VAT (before discount)
                        $('.total_cost').val(total.toFixed(2)); // Final total after item discount
                    }


                    // Use event delegation for dynamically added elements within Swal modal
                    $(document).on('input change', '#swal2-html-container input, #swal2-html-container select', calculateTotals);
                    calculateTotals(); // Initial calculation
                },
                 willClose: () => {
                     // Unbind events when modal closes to prevent multiple triggers
                    $(document).off('input change', '#swal2-html-container input, #swal2-html-container select');
                },
                preConfirm: function() {
                    const form = $('#submitEditLineForm');
                     // Manually trigger Parsley validation for the modal form
                     const parsleyInstance = form.parsley();
                     if (!parsleyInstance) { // Check if instance exists
                         form.parsley(); // Initialize if not already
                     }
                      if (!form.parsley().validate()) {
                         // If validation fails, prevent submission and show messages
                         Swal.showValidationMessage(`<?=__('fill_required_fields_validation')?>`);
                         return false;
                     }

                    // Simple check for negative numbers where they shouldn'T be
                    if (parseFloat($('.quantity').val()) < 0 || parseFloat($('.product_price').val()) < 0 || parseFloat($('.idiscount').val()) < 0) {
                         Swal.showValidationMessage(`<?=__('negative_values_not_allowed')?>`);
                         return false;
                    }

                    return $.ajax({
                        url: './includes/ajaxFile/ajaxSmartRequest.php',
                        type: 'POST', dataType: "JSON",
                        data: form.serialize() + '&' + $.param({ ajaxType: "request_line_update" }),
                    }).then(response => {
                         if (response.type !== 'success') { // Check response type from server
                            throw new Error(response.message || 'Update failed');
                        }
                        return response;
                    }).catch(error => {
                        Swal.showValidationMessage(`<?=__('request_failed')?>: ${error.message || error.statusText}`);
                    });
                },
            }).then(function(result) {
                if (result.isConfirmed) {
                    Swal.fire({ allowOutsideClick: false,
                        title: result.value.title,
                        text: result.value.message,
                        icon: result.value.type,
                    }).then(() => {
                        if(result.value.type === 'success') location.reload();
                    });
                }
            });
        });

        function payment_modal_HTML(gtotal) {
            // Added required and parsley attributes
            return `
                <form id="paymentForm" action="open_request.php?id=<?= htmlspecialchars($_GET['id'], ENT_QUOTES) ?>" method="post" enctype="multipart/form-data" class="text-left" data-parsley-validate>
                    <input type="hidden" name="inv_no" value="<?= htmlspecialchars($invnoget, ENT_QUOTES) ?>">
                    <input type="hidden" name="process_payment" value="1">
                    <div class="form-group">
                        <label for="paid_amount"><?=__('paid_amount_sar')?></label>
                        <input type="number" step="0.01" min="0" class="form-control" id="paid_amount" name="paid_amount" value="${gtotal}" required data-parsley-type="number" data-parsley-min="0">
                    </div>
                    <div class="form-group">
                        <label for="payment_invoice"><?=__('payment_invoice_receipt')?></label>
                        <input type="file" class="form-control-file" id="payment_invoice" name="payment_invoice" required data-parsley-max-file-size="5"> <!-- Max 5MB -->
                    </div>
                    <div class="form-group">
                        <label for="payment_note"><?=__('note_optional')?></label>
                        <textarea class="form-control" id="payment_note" name="payment_note" rows="3"></textarea>
                    </div>
                     <!-- Hidden input for payable_by_emp_id removed -->
                </form>`;
        }

        function request_details_HTML() {
            // Added required attributes
            return `
                <form id="submitEditReqForm" class="text-left" data-parsley-validate>
                    <div class="form-group">
                        <label for="request_date">Request date</label>
                        <input type="text" id="request_date" name="request_date" class="form-control datepicker" placeholder="YYYY-MM-DD" autocomplete="off" readonly required>
                    </div>
                    <div class="form-group">
                        <label for="sub_type"><?=__('subject_type')?></label>
                        <select id="sub_type" name="sub_type" class="form-control" required><option value=""><?=__('select')?></option></select>
                    </div>
                    <div class="form-group">
                        <label for="sub_title"><?=__('subject_title')?></label>
                        <input type="text" id="sub_title" name="sub_title" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="remarks"><?=__('remarks')?></label>
                        <textarea id="remarks" name="remarks" class="form-control" rows="3"></textarea>
                    </div>
                    <input type="hidden" id="reqid" name="reqid">
                </form>`;
        }

        function request_line_HTML() {
             // Define VAT rate globally or fetch dynamically if needed
             const DEFAULT_VAT_RATE = <?= get_setting($conDB, 'vat') ?>;
             // Added required and parsley attributes, min="0" for numbers
            var strView =
                `<form id="submitEditLineForm" data-parsley-validate>
                    <div class="form-row customSweetAlertMLR">
                        <div class="form-group col-md-4"><label><?=__('item_name')?>*</label><input type="text" name="item_name" class="form-control item_name" required></div>
                        <div class="form-group col-md-4"><label><?=__('reference', 'Reference')?></label><input type="text" name="reference" class="form-control item_reference" placeholder="Reference"></div>
                        <div class="form-group col-md-4"><label><?=__('location')?>*</label><select id="location" class="form-control location" name="location" required><option value=""><?=__('select')?></option></select></div>
                    </div>
                    <div class="form-row customSweetAlertMLR">
                        <div class="form-group col-md-3"><label><?=__('quantity')?>*</label><input type="number" step="any" min="0" name="quantity" class="form-control quantity" id='quantity' required data-parsley-type="number" data-parsley-min="0"></div>
                        <div class="form-group col-md-3"><label><?=__('unit_cost')?>*</label><input type="number" step="0.01" min="0" name="product_price" class="form-control product_price" id='product_price' required data-parsley-type="number" data-parsley-min="0"></div>
                    </div>
                    <div class="form-row customSweetAlertMLR">
                        <div class="form-group col-md-2"><label><?=__('item_value')?> (${__('before_vat')})</label><input type='text' id='itmvalue' class="form-control itmvalue" name='itmvalue' readonly /></div>
                        <div class="form-group col-md-2"><label><?=__('vat_opt')?></label><select class="form-control vat_option" name="vat_option"><option value="include">${__('include')} ${DEFAULT_VAT_RATE}%</option><option value="exclude" selected=selected>${__('exclude')} ${DEFAULT_VAT_RATE}%</option><option value="no_vat">${__('no_vat')}</option></select></div>
                        <div class="form-group col-md-2"><label><?=__('vat_rate_percent')?></label><input type="text" name="vat_rate" class="form-control vat_rate" id="vat_rate" readonly /></div>
                        <div class="form-group col-md-2"><label><?=__('vat_val')?></label><input type='text' class="form-control vat_val" id='vat_val' name='vat_val' readonly /></div>
                        <div class="form-group col-md-2"><label><?=__('amount')?> (${__('inc_vat')})</label><input type='text' class="form-control amount" id='amount' name='amount' readonly /></div>
                        <div class="form-group col-md-2"><label><?=__('discount')?> (${__('item_disc')})</label><input type="number" step="0.01" min="0" name="idiscount" class="form-control idiscount" id='idiscount' value="0" data-parsley-type="number" data-parsley-min="0"></div>
                    </div>
                    <div class="form-row customSweetAlertMLR justify-content-end">
                        <div class="form-group col-md-3"><label><?=__('total')?> (${__('after_disc')})</label><input type='text' class="form-control total_cost" id='total_cost' name='total_cost' readonly /></div>
                    </div>
                    <input type="hidden" id="itemid" name="itemid">
                </form>`;
            return strView;
        }

    </script>
    <script src="assets/js/notifications.js"></script>
</body>
</html>
