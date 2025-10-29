<?php

require_once __DIR__ . '/includes/db.php';
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
        $file_ext = strtolower(pathinfo($_FILES["payment_invoice"]["name"], PATHINFO_EXTENSION));
        $attachment_name = $inv_no_pay . "_payment_" . time() . "." . $file_ext;
        $target_file = $target_dir . $attachment_name;

        if (move_uploaded_file($_FILES["payment_invoice"]["tmp_name"], $target_file)) {
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
                $response = ['status' => 'success', 'message' => __('payment_processed_successfully')];
            } else {
                // Optionally remove the uploaded file if DB insert fails
                if (file_exists($target_file)) { unlink($target_file); }
                $response = ['status' => 'error', 'message' => __('failed_to_save_payment_details') . " DB Error: " . mysqli_error($conDB)];
                 mysqli_query($conDB, "UPDATE `smart_request` SET `current_status`='approved' WHERE `inv_no`='$inv_no_pay'"); // Revert status
            }
        } else {
            $response = ['status' => 'error', 'message' => __('error_uploading_file')];
        }
    } else {
        $response = ['status' => 'error', 'message' => __('select_payment_invoice_to_upload')];
    }
    echo json_encode($response);
    exit(); // Stop script execution for AJAX requests
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
        $created_at_get = $row["created_at"];
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
    // Handle case where request ID is invalid or query fails
     $msg = "<div class=\"alert alert-danger bg-danger text-white border-0\" role=\"alert\">".__('error_request_not_found')." Error: ".mysqli_error($conDB)."</div>";
     // Potentially redirect or stop further processing
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
            $update_payer_sql = "UPDATE `smart_request` SET `payable_by_emp_id` = $payable_by_emp_id_assign WHERE `inv_no` = '" . escape_string($inv_no_po) . "'";
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
            $mail->isSMTP();
            $mail->Host = get_setting($conDB, 'smtp_host');
            $mail->SMTPAuth = true;
            $mail->Username = get_setting($conDB, 'smtp_user');
            $mail->Password = get_setting($conDB, 'smtp_pass');
            $mail->SMTPSecure = get_setting($conDB, 'smtp_secure');
            $mail->Port = get_setting($conDB, 'smtp_port');
            $mail->CharSet = 'UTF-8';
            $mail->setFrom(get_setting($conDB, 'smtp_user'), get_setting($conDB, 'application_name'));

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
            $mail->Subject = $_POST['email_subject'] ?? ('Smart Request Approved by HR - ' . ($userwelext ?? $userwel) . " - " . $invnoget);

            $email_template_path = './includes/PHPMailerMaster/email_contant_body_redesigned.php';
            if (file_exists($email_template_path)) {
                 $bodycus = file_get_contents($email_template_path) ?: '';
                 $bodycus = (string)$bodycus;
                 $bodycus = preg_replace('/\\\\/', '', $bodycus);

                 $userwel_str = $userwelext ?? $userwel ?? '';
                 $sub_title_get_str = $sub_title_get ?? '';
                 $invnoget_str = $invnoget ?? '';
                 $dept_str = $usrdeptnme ?? $dep_nme_get ?? '';

                 $bodycus = str_replace('$userwelext', $userwel_str, $bodycus);
                 $bodycus = str_replace('$sub_title_get', $sub_title_get_str, $bodycus);
                 $bodycus = str_replace('$invnoget', $invnoget_str, $bodycus);
                 $bodycus = str_replace('$dept', $dept_str, $bodycus);

                 if (isset($_POST['email_body_line'])) {
                    $bodycus = str_replace('A new request is waiting for your approval.', $_POST['email_body_line'] ?? '', $bodycus);
                 } else if (!empty($cc_hr_employees)) {
                     $bodycus = str_replace('A new request is waiting for your approval.', 'This request has been approved by HR.', $bodycus);
                 }
                 $mail->Body = $bodycus;
            } else {
                // Fallback text email
                $default_subject = "Smart Request Notification";
                $default_body_line = "A Smart Request requires attention.";
                 if (isset($_POST['email_body_line'])) {
                     $default_body_line = $_POST['email_body_line'] ?? '';
                     $default_subject = $_POST['email_subject'] ?? $default_subject;
                 } elseif (!empty($cc_hr_employees) && empty($next_approver_email)) {
                     $default_body_line = "This request (" . ($invnoget ?? '') . ": " . ($sub_title_get ?? '') . ") has been approved by HR.";
                     $default_subject = "Smart Request Approved by HR - " . ($invnoget ?? '');
                 } elseif (!empty($next_approver_email)) {
                     $default_body_line = "A new Smart Request (" . ($invnoget ?? '') . ": " . ($sub_title_get ?? '') . ") requires your approval.";
                      $default_subject = "Smart Request for your Approval - " . ($invnoget ?? '');
                 }
                 $mail->Subject = $default_subject;
                 $mail->Body = "Dear " . ($next_approver_name ?? 'Recipient') . ",\n\n" . $default_body_line . "\n\nPrepared by: " . ($userwelext ?? $userwel ?? '') . "\nDepartment: " . ($usrdeptnme ?? $dep_nme_get ?? '');
                error_log("Email template not found: " . $email_template_path);
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
         // --- Check if Finance Manager was explicitly selected ---
         $finance_manager_selected = false;
         if (isset($_POST['approvers'])) {
             foreach ($_POST['approvers'] as $approver_id) {
                 // Fetch approver's department (assuming dept 2 is Finance)
                 $app_query = mysqli_query($conDB, "SELECT dept FROM employees WHERE emp_id = " . (int)$approver_id);
                 if ($app_query && $app_row = mysqli_fetch_assoc($app_query)) {
                     if ($app_row['dept'] == 2) { // Assuming 2 is Finance Dept ID
                         $finance_manager_selected = true;
                         break;
                     }
                 }
             }
         }

         // --- If Finance Manager was NOT selected, send email ---
         if (!$finance_manager_selected && $current_status_get == 'draft' && isset($_POST['submit'])) { // Only on initial submission
              require_once __DIR__ . '/includes/helper_functions.php'; // Ensure function is loaded
             $finance_manager_details = getDeptManager($conDB, 2); // Get Finance Manager (Dept 2)
             if ($finance_manager_details && !empty($finance_manager_details['email'])) {
                 $mail_fm = new PHPMailer(true);
                 try {
                     // (Setup PHPMailer as above - Host, Auth, From etc.)
                     $mail_fm->isSMTP();
                     $mail_fm->Host = get_setting($conDB, 'smtp_host');
                     $mail_fm->SMTPAuth = true;
                     $mail_fm->Username = get_setting($conDB, 'smtp_user');
                     $mail_fm->Password = get_setting($conDB, 'smtp_pass');
                     $mail_fm->SMTPSecure = get_setting($conDB, 'smtp_secure');
                     $mail_fm->Port = get_setting($conDB, 'smtp_port');
                     $mail_fm->CharSet = 'UTF-8';
                     $mail_fm->setFrom(get_setting($conDB, 'smtp_user'), get_setting($conDB, 'application_name'));

                     $mail_fm->addAddress($finance_manager_details['email'], $finance_manager_details['name']);
                     $mail_fm->isHTML(true);
                     $mail_fm->Subject = 'Smart Request Requires Payer Assignment - ' . $inv_no_po;
                     // Simple Body - You can use the template logic if preferred
                     $mail_fm->Body = "Dear " . $finance_manager_details['name'] . ",<br><br>Smart Request <b>" . $inv_no_po . "</b> (" . ($sub_title_get ?? '') . ") has reached the approved stage and requires a payer to be assigned.<br><br>Please review the request.<br><br>Prepared by: " . ($prep_by_get ?? '') . "<br>Department: " . ($dep_nme_get ?? '');
                     $mail_fm->send();
                     error_log("Payer assignment notification sent to Finance Manager for InvNo: " . $inv_no_po);
                 } catch (Exception $e) {
                     error_log("Mailer Error (Finance Manager Payer Assignment) for InvNo: " . $inv_no_po . " - " . $mail_fm->ErrorInfo);
                 }
             } else {
                 error_log("Could not find Finance Manager details to send payer assignment notification for InvNo: " . $inv_no_po);
             }
         }
         // --- End Finance Manager Email Logic ---

         echo "<script>window.location.href = 'all_requests.php?action=success';</script>";
         exit;
    }
}
// --- END OF POST HANDLER ---


// --- Status Display Logic ---
$status_get = "";
$rejection_note = "";
switch ($current_status_get) {
    case "draft":
        $status_get = "<input class='form-control bg-secondary border-secondary text-white' type='text' value='" . __('draft_not_submitted') . "' readonly />";
        break;
    case "pending_approval":
        $status_get = "<input class='form-control bg-custom border-custom text-white' type='text' value='" . __('pending_approval_level') . " " . $current_approval_level_get . "' readonly />";
        break;
    case "approved":
        $status_text_approved = $assigned_payer_name ? __('approved_pending_payment') : __('approved_pending_assignment');
        // --- MODIFIED: Changed from bg-success to bg-warning as requested ---
        $status_get = "<input class='form-control bg-warning border-warning text-white' type='text' value='" . $status_text_approved . "' readonly />";
        break;
    case "rejected":
        require_once __DIR__ . '/includes/helper_functions.php'; // Ensure get_approval_chain_status is loaded
        $chain_status_for_reject = get_approval_chain_status($conDB, $invnoget, 'smart_request');
        $rejected_by = __('rejected');
        if($chain_status_for_reject){
            foreach($chain_status_for_reject as $step) {
                if ($step['status'] == 'rejected') {
                    $rejected_by = __('rejected_by') . " " . parseName($step['approver_name']);
                    $rejection_note = $step['note'];
                    break;
                }
            }
        }
        $status_get = "<input class='form-control bg-danger border-danger text-white' type='text' value='$rejected_by' readonly />";
        break;
    case "paid":
        // --- This is now the only 'bg-success' status ---
        $status_get = "<input class='form-control bg-success border-success text-white' type='text' value='" . __('payment_paid') . "' readonly />";
        break;
    // --- Fallback for Old Statuses ---
    case "pending_dept_manager_approval":
        $status_get = "<input class='form-control bg-custom border-custom text-white' type='text' value='" . __('pending_department_manager_approval') . "' readonly />";
        break;
    case "pending_finance_approval":
        $status_get = "<input class='form-control bg-warning border-warning text-white' type='text' value='" . __('pending_finance_approval') . "' readonly />";
        break;
    case "pending_gm_approval":
        $status_get = "<input class='form-control bg-primary border-primary text-white' type='text' value='" . __('pending_general_manager_approval') . "' readonly />";
        break;
    default:
        $status_get = "<input class='form-control bg-danger border-danger text-white' type='text' value='" . __('unknown_status') . ": " . htmlspecialchars($current_status_get) ."' readonly />";
}

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
     require_once __DIR__ . '/includes/helper_functions.php'; // Ensure get_current_approver is loaded
    $current_pending_approver_id = get_current_approver($conDB, $invnoget, 'smart_request');
}

// Get Finance employees for the Payable By dropdown
 require_once __DIR__ . '/includes/helper_functions.php'; // Ensure getFinancePersonnel is loaded
$finance_employees = getFinancePersonnel($conDB);

// Get HR employees for the CC dropdown
 require_once __DIR__ . '/includes/helper_functions.php'; // Ensure getHRPersonnel is loaded
$hr_employees = getHRPersonnel($conDB); // Dept ID 5 is now the default

?>

<!doctype html>
<html lang="<?= $current_lang ?? 'en' ?>" <?= ($is_rtl ?? false) ? 'dir="rtl"' : '' ?>>

<head>
    <meta charset="utf-8" />
    <title><?= $site_title ?> - <?= htmlspecialchars($sub_title_get) ?></title> <!-- Added htmlspecialchars -->
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta content="Anees Afzal" name="author" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />

    <!-- App favicon -->
    <link rel="shortcut icon" href="<?=get_setting($conDB, 'favicon')?>">

    <link href="./plugins/bootstrap-tagsinput/css/bootstrap-tagsinput.css" rel="stylesheet" />
    <link href="./plugins/bootstrap-select/css/bootstrap-select.min.css" rel="stylesheet" />
    <link href="./plugins/select2/css/select2.min.css" rel="stylesheet" type="text/css" />
    <link rel="stylesheet" href="./plugins/switchery/switchery.min.css" />

    <!-- App css -->
    <link href="assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/icons.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/metismenu.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/style.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/style_dark.css" rel="stylesheet" type="text/css" />
    <script src="assets/js/modernizr.min.js"></script>
    <!-- Sweet Alert -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style type="text/css">
        .noneDIV { display: none; }
        .showDIV { display: block; }
        .swal-wide { width: 850px !important; }
        .currencyicon { border: 1px solid #d9e3e9 !important; border-radius: 0 0.25rem 0.25rem 0 !important; border-left: 0px !important; }
        .grandtotal, .discount, .total, .vat, .subtotal { border-right: 0px !important; }
        .input-group-text { border: 1px solid #d9e3e9 !important; }
        .approval-status { padding: 10px; margin-bottom: 10px; border-left: 4px solid #ccc; background-color: #f9f9f9; }
        .approval-status.pending { border-color: #ffc107; background-color: #fffaf0; }
        .approval-status.approved { border-color: #28a745; background-color: #f0fff4; }
        .approval-status.rejected { border-color: #dc3545; background-color: #fff0f1; }
        .approval-status.awaiting { border-color: #e0e0e0; background-color: #fafafa; }
        .customSweetAlertMLR { margin-left: auto; margin-right: auto; }
        .radioalign { margin-right: 20px; }
        .atch { cursor: pointer; }

        /* NEW STYLES FOR DYNAMIC APPROVERS */
        .approver-tag {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.375rem 0.75rem;
            background-color: #f0f2f5;
            border: 1px solid #ced4da;
            border-radius: 0.25rem;
            margin-bottom: 5px;
        }
        .approver-tag span {
            font-weight: 500;
        }
        .approver-tag .remove-approver-btn {
            font-size: 1.2rem;
            font-weight: 700;
            line-height: 1;
            color: #dc3545;
            text-shadow: 0 1px 0 #fff;
            opacity: 0.75;
            cursor: pointer;
            border: none;
            background: transparent;
            padding: 0;
        }
        .approver-tag .remove-approver-btn:hover {
            opacity: 1;
        }
        /* Fix for select2 width */
        .select2-container { width: 100% !important; }

        /* MODIFIED BADGE STYLES */
        .user-type-badge {
            font-size: 0.7em; /* Smaller font */
            font-weight: 700;
            padding: .2em .5em; /* Adjusted padding */
            border-radius: 10rem;
            color: #fff;
            background-color: #6c757d; /* Default: secondary */
            margin-left: 5px;
            vertical-align: middle; /* Align badge vertically */
            display: inline-block; /* Ensure it behaves like an inline element with block properties */
            line-height: 1.2; /* Adjust line height if necessary */
            max-width: 90%; /* Prevent extremely long badges from breaking layout too much */
            overflow: hidden; /* Hide overflow */
            text-overflow: ellipsis; /* Add ellipsis for overflow */
            white-space: nowrap; /* Prevent wrapping */
        }
        .user-type-badge.administrator { background-color: #dc3545; } /* Danger */
        .user-type-badge.hr { background-color: #17a2b8; } /* Info */
        .user-type-badge.gm { background-color: #007bff; } /* Primary */
        .user-type-badge.dept_user { background-color: #ffc107; color: #212529; } /* Warning */
        .user-type-badge.assistant { background-color: #28a745; } /* Success */

        /* Right align badge within select2 RESULTS */
         .select2-results__option .user-type-badge {
            float: right;
            margin-top: 3px;
         }

        /* NEW: Fix for missing Parsley icon */
        #approver-error-container .parsley-errors-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        #approver-error-container .parsley-errors-list li {
            font-weight: 500;
            margin-top: 0.25rem;
            font-size: 0.875rem;
        }
        #approver-error-container .parsley-errors-list li::before {
            content: none !important; /* Remove any ::before pseudo-element icon */
            display: none !important;
        }
         /* NEW: Style for HR CC Select2 box */
        #cc_hr_select_div .select2-container--default .select2-selection--multiple {
            border: 1px solid #ced4da;
            border-radius: 0.25rem;
        }

        /* --- UPDATED CSS FOR SELECTED ITEM BADGE FIX V3 --- */
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            display: flex !important; /* Ensure flex is applied */
            justify-content: space-between !important;
            align-items: center !important;
            padding-right: 5px !important; /* Minimal padding */
            line-height: inherit !important; /* Inherit from parent */
            overflow: hidden; /* Prevent content overflow */
        }

        /* Target the span holding the name */
        .select2-container--default .select2-selection--single .select2-selection__rendered .select2-selection-text {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            flex-grow: 1; /* Allow name to take space */
            margin-right: 5px; /* Space before badge */
        }

        /* Target the badge itself */
        .select2-container--default .select2-selection--single .select2-selection__rendered .select2-selection__rendered-badge {
            display: inline-flex !important; /* Use inline-flex for better control */
            align-items: center; /* Center text vertically within badge */
            font-size: 0.6em !important; /* Even smaller font */
            padding: 1px 4px !important; /* Minimal padding */
            line-height: 1 !important; /* Compact line height */
            margin-left: 5px !important;
            flex-shrink: 0; /* Prevent badge shrinking */
            max-width: 55%; /* Limit width slightly more */
            vertical-align: middle; /* Try middle alignment */
            /* Removed float, position, top */
        }
        /* --- END UPDATED BADGE FIX CSS V3 --- */

    </style>
    <?php if ($is_rtl): ?>
            <link href="assets/css/style_rtl.css" rel="stylesheet" type="text/css" />
        <?php endif; ?>
		<script>
            // --- NEW: Pass Department Map to JS ---
            window.departmentMap = <?= json_encode($department_map ?? []) ?>;
            // --- END NEW ---
            window.lang = <?= json_encode($GLOBALS['translations'] ?? []) ?>;
            window.currentUserDept = <?= json_encode($user_dept) ?>; // Pass user dept to JS
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

        <!-- ============================================================== -->
        <!-- Start right Content here -->
        <!-- ============================================================== -->
        <div class="content-page">
            <!-- Top Bar Start -->
            <?php include("./includes/topbar.php"); ?>
            <!-- Top Bar End -->

            <!-- Start Page content -->
            <div class="content">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-md-12" id="DataContact">
                            <!-- Make sure form ID is unique if needed, otherwise action should point correctly -->
                            <form action="open_request.php?id=<?= htmlspecialchars($_GET['id']) ?>" method="post" enctype="multipart/form-data">
                                <div class="row">
                                    <div class="col-md-12" id="main-content">
                                        <div class="card-box">

                                            <?= $msg ?? '' ?>

                                            <?php if ($current_status_get == 'rejected' && !empty($rejection_note)): ?>
                                                <div class="alert alert-danger bg-danger text-white border-0" role="alert" id="attachmentsSmt">
                                                    <?=__('request_rejected_reason')?> <strong> "<?= htmlspecialchars($rejection_note) ?>" </strong>
                                                </div>
                                            <?php endif; ?>
                                            <div class="row">
                                                <div class="col-4 ">
                                                    <div class="mt-3 float-left">
                                                        <div class="input-group mb-2">
                                                            <div class="input-group-prepend"><div class="input-group-text"><?=__('invoice_date')?>:</div></div>
                                                            <input class="form-control" type='text' value="<?= $created_at_get ? date("d F Y", strtotime($created_at_get)) : '' ?>" readonly />
                                                        </div>
                                                        <div class="input-group mb-2">
                                                            <div class="input-group-prepend"><div class="input-group-text"><?=__('sub_type')?></div></div>
                                                            <input class="form-control" type='text' value="<?= htmlspecialchars($sub_type_get) ?>" readonly />
                                                        </div>
                                                        <div class="input-group mb-2">
                                                            <div class="input-group-prepend"><div class="input-group-text"><?=__('sub_title')?></div></div>
                                                            <input class="form-control" type='text' value="<?= htmlspecialchars($sub_title_get) ?>" readonly />
                                                        </div>
                                                        <?php
                                                        $can_add_attachment = false;
                                                        // Show attachment option only to the creator of the request in draft status
                                                        if ($empid == $emp_id_get && $current_status_get == 'draft') {
                                                            $can_add_attachment = true;
                                                        }

                                                        if ($can_add_attachment):
                                                            $query_chkattach = mysqli_query($conDB, "SELECT * FROM `smt_attachment` WHERE `inv_no`='" . escape_string($_GET['id']) . "' ");
                                                            if ($query_chkattach && mysqli_num_rows($query_chkattach) <= 5) { ?>
                                                                <div class="input-group mb-2">
                                                                    <label for="inlineRadio3" class="col-form-label radioalign"><?=__('attachment')?><span class="text-danger">*</span></label>
                                                                    <div class="radio radio-info form-check-inline">
                                                                        <input type="radio" id="inlineRadio3" value="yes" name="attach" onclick="showAttachment()" required>
                                                                        <label for="inlineRadio3" class="atch"><i class="mdi mdi-paperclip"></i> <?=__('have_attachments')?></label>
                                                                    </div>
                                                                    <div class="radio radio-info form-check-inline">
                                                                        <input type="radio" id="inlineRadio2" value="no" name="attach" onclick="hideAttachment()" required>
                                                                        <label for="inlineRadio2" class="atch"><i class="mdi mdi-clippy"></i> <?=__('no_attachment')?></label>
                                                                    </div>
                                                                    <a href="javascript:void(0);" class="btn btn-sm btn-custom waves-effect waves-light noneDIV checkattach attachmentDIV smt_attachment" data-attach="ok" data-inv_no="<?= htmlspecialchars($invnoget) ?>">
                                                                        <i class="mdi mdi-cloud-upload "></i> <?=__('upload_documents')?></a>
                                                                    <input type="text" id="checkatt" class="noneDIV checkatt">
                                                                </div>
                                                            <?php }
                                                        endif; ?>
                                                        <?php if ($remarks_get): ?>
                                                            <div class="input-group mb-2">
                                                                <div class="input-group-prepend"><div class="input-group-text"><?=__('remarks')?></div></div>
                                                                <input class="form-control" type='text' value="<?= htmlspecialchars($remarks_get) ?>" readonly />
                                                            </div>
                                                        <?php endif; ?>

                                                        <!-- NEW Approval Status Trail -->
                                                        <div class="mt-4">
                                                            <h5><?=__('approval_status')?></h5>
                                                            <?php
                                                                require_once __DIR__ . '/includes/helper_functions.php'; // Ensure get_approval_chain_status is loaded
                                                                // PASS $conDB
                                                                $approval_chain = get_approval_chain_status($conDB, $invnoget, 'smart_request');
                                                                if (empty($approval_chain) && $current_status_get == 'draft') {
                                                                    echo "<div class='approval-status awaiting'><small>" . __('approval_chain_not_defined_yet') . "</small></div>";
                                                                }
                                                                foreach ($approval_chain as $step):
                                                                    $status_class = $step['status']; // 'pending', 'approved', 'rejected', 'awaiting'
                                                                    $status_text = __($step['status']);
                                                                    $action_date = $step['action_date'] ? date('d M Y H:i', strtotime($step['action_date'])) : '';
                                                            ?>
                                                            <div class="approval-status <?= $status_class ?>">
                                                                <strong><?=__('level')?> <?= $step['approval_level'] ?>: <?= parseName($step['approver_name']) ?></strong>
                                                                <span class="float-right"><?= $status_text ?></span>
                                                                <?php if($action_date): ?>
                                                                    <br><small><?=__('on')?> <?= $action_date ?></small>
                                                                <?php endif; ?>
                                                                <?php if($step['note']): ?>
                                                                    <br><small><em><?=__('note')?>: <?= htmlspecialchars($step['note']) ?></em></small>
                                                                <?php endif; ?>
                                                            </div>
                                                            <?php endforeach; ?>
                                                        </div>
                                                        <!-- END NEW Approval Status Trail -->

                                                        <!-- NEW: Show Assigned Payer on Left Side -->
                                                         <?php if ($assigned_payer_name): ?>
                                                         <div class="approval-status pending"> <!-- MODIFIED: Changed from approved to pending for warning color -->
                                                            <strong><?=__('payable_assigned_to')?>: <?= parseName($assigned_payer_name) ?></strong>
                                                            <!-- Optionally add assignment date here if you fetch it -->
                                                         </div>
                                                         <?php endif; ?>
                                                    </div>
                                                </div>
                                                <div class="col-4">
                                                    <div class="noneDIV attachmentDIV mt-3" id="">
                                                        <img src="qrconfig_smartrequest.php?id=<?= htmlspecialchars($_GET['id']) ?>" />
                                                        <p><?=__('scan_qr_for_attachments')?></p>
                                                    </div>
                                                </div>
                                                <div class="col-4 ">
                                                    <div class="mt-3 float-right">
                                                        <div class="input-group mb-2">
                                                            <div class="input-group-prepend"><div class="input-group-text"><?=__('invoice_no')?>:</div></div>
                                                            <input class="form-control" type='text' name='inv_no' value="<?= htmlspecialchars($invnoget) ?>" readonly />
                                                        </div>
                                                        <div class="input-group mb-2">
                                                            <div class="input-group-prepend"><div class="input-group-text"><?=__('department')?>:</div></div>
                                                            <input class="form-control" type='text' value="<?= htmlspecialchars($dep_nme_get) ?>" readonly />
                                                        </div>
                                                        <div class="input-group mb-2">
                                                            <div class="input-group-prepend"><div class="input-group-text"><?=__('prepared_by')?>:</div></div>
                                                            <input class="form-control" type='text' value="<?= htmlspecialchars($prep_by_get) ?>" readonly />
                                                        </div>

                                                        <!-- Display Current Status -->
                                                         <div class="input-group mb-2">
                                                            <div class="input-group-prepend"><div class="input-group-text"><?=__('current_status_label')?>:</div></div>
                                                            <?= $status_get ?>
                                                         </div>

                                                        <!-- REMOVED: Display Assigned Payer if set (moved to left side) -->


                                                        <?php
                                                        // --- NEW ACTION BOX LOGIC ---
                                                        $show_submit_button = false; // For draft
                                                        $show_action_box = false; // For approvers
                                                        $show_assign_payer_box = false; // For Finance Manager to assign
                                                        $show_process_payment_button = false; // For assigned payer

                                                        if ($current_status_get == "draft" && $empid == $emp_id_get) {
                                                            $show_submit_button = true;
                                                        } elseif ($current_status_get == 'pending_approval' && $empid == $current_pending_approver_id) {
                                                            $show_action_box = true;
                                                        } elseif ($current_status_get == 'approved' && $emptypeget == 'Manager' && $user_dept == 2 && !$payable_by_emp_id_get) { // Only Finance Manager can assign
                                                            $show_assign_payer_box = true;
                                                        } elseif ($current_status_get == 'approved' && $empid == $payable_by_emp_id_get) { // Only assigned user can pay
                                                            $show_process_payment_button = true;
                                                        }

                                                        // This block is for creators to submit their draft and define the approval chain
                                                        if ($show_submit_button): ?>

                                                            <!-- NEW DYNAMIC APPROVER UI -->
                                                            <div class="form-group mb-2">
                                                                <label><?=__('select_approvers_in_order')?></label>
                                                                <div class="input-group">
                                                                    <select class="form-control" id="approver-select" data-placeholder="<?=__('select_approver')?>">
                                                                        <option value=""></option> <!-- Empty for placeholder -->
                                                                        <?php foreach($potential_approvers as $employee): ?>
                                                                        <option value="<?= $employee['emp_id'] ?>" data-type="<?= htmlspecialchars($employee['user_type']) ?>" data-dept="<?= htmlspecialchars($employee['dept']) ?>">
                                                                            <?= parseName($employee['name']) ?>
                                                                        </option>
                                                                        <?php endforeach; ?>
                                                                    </select>
                                                                    <div class="input-group-append">
                                                                        <button class="btn btn-success" type="button" id="add-approver-btn"><i class="mdi mdi-plus"></i> <?=__('add')?></button>
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            <div id="approver-list-container">
                                                                <!-- Approvers will be added here dynamically -->
                                                            </div>
                                                            <!-- Dummy input for parsley validation -->
                                                            <input type="hidden" id="min-approver-check"
                                                                   data-parsley-required="true"
                                                                   data-parsley-error-message="<?=__('select_at_least_one_approver')?>"
                                                                   data-parsley-errors-container="#approver-error-container">
                                                            <div id="approver-error-container" class="text-danger"></div> <!-- Error container -->

                                                            <button type="submit" name="submit" value="1" class="btn btn-info waves-effect waves-light mt-2"><?=__('submit_for_approval')?></button>
                                                            <!-- END NEW DYNAMIC APPROVER UI -->

                                                        <?php
                                                        // This block is for approvers to take action
                                                        elseif ($show_action_box): ?>
                                                            <div class="input-group mb-2">
                                                                <div class="input-group-prepend"><div class="input-group-text"><?=__('action')?><span class="text-danger ml-2">*</span></div></div>
                                                                <select class="form-control" name="status" id="statlist" required>
                                                                    <option value=""><?=__('select')?></option>
                                                                    <option value="approve"><?=__('approve')?></option>
                                                                    <option value="reject"><?=__('reject')?></option>
                                                                </select>
                                                            </div>
                                                            <div class="input-group mb-2" id="RejectDIV" style="display: none;">
                                                                <div class="input-group-prepend"><div class="input-group-text"><?=__('rejection_note')?><span class="text-danger ml-2">*</span></div></div>
                                                                <input type='text' class="form-control" name="note" id="RejectInput" />
                                                            </div>
                                                            <!-- NEW: HR CC Select Dropdown (hidden by default) -->
                                                            <?php if ($user_dept == 5): // UPDATED: HR Dept ID is 5 ?>
                                                            <div class="form-group mb-2" id="cc_hr_select_div" style="display: none;">
                                                                <label for="cc_hr_select"><?=__('cc_hr_employees_optional')?></label>
                                                                <select class="form-control" name="cc_hr_employees[]" id="cc_hr_select" multiple="multiple">
                                                                    <?php foreach($hr_employees as $hr_emp): ?>
                                                                        <?php if($hr_emp['emp_id'] == $empid) continue; // Skip self ?>
                                                                        <option value="<?= $hr_emp['emp_id'] ?>">
                                                                            <?= parseName($hr_emp['name']) ?>
                                                                        </option>
                                                                    <?php endforeach; ?>
                                                                </select>
                                                            </div>
                                                            <?php endif; ?>

                                                            <button type="submit" name="submit" value="1" class="btn btn-info waves-effect waves-light mt-2"><?= __('submit_action') ?></button>

                                                        <?php
                                                        // This block is for Finance Manager to select who pays
                                                        elseif ($show_assign_payer_box): ?>
                                                            <div class="form-group mb-2">
                                                                <label for="payable_by_emp_id"><?=__('assign_payable_to')?> <span class="text-danger">*</span></label>
                                                                <select class="form-control" name="payable_by_emp_id" id="payable_by_emp_id" required>
                                                                    <option value=""><?=__('select_finance_employee')?></option>
                                                                    <?php foreach($finance_employees as $fin_emp): ?>
                                                                        <option value="<?= $fin_emp['emp_id'] ?>" <?= ($fin_emp['emp_id'] == $payable_by_emp_id_get) ? 'selected' : '' ?>>
                                                                            <?= parseName($fin_emp['name']) ?>
                                                                        </option>
                                                                    <?php endforeach; ?>
                                                                </select>
                                                            </div>
                                                            <button type="submit" name="assign_payer_submit" value="1" class="btn btn-info waves-effect waves-light mt-2"><?=__('assign_payer_button')?></button>
                                                        <?php endif; ?>

                                                        <!-- Old action box logic placeholder (kept for rule 2) -->
                                                        <?php /* if (false): ?> ... <?php endif; */ ?>
                                                    </div>

                                                </div>
                                            </div>

                                            <?php if($payment_details): ?>
                                            <div class="row mt-4">
                                                <div class="col-md-12">
                                                    <div class="alert alert-info">
                                                        <h5 class="alert-heading"><?=__('payment_information')?></h5>
                                                        <p><strong><?=__('paid_amount')?>:</strong> <?= number_format($payment_details['paid_amount'], 2) ?> SAR</p>
                                                        <p><strong><?=__('paid_by')?>:</strong> <?= htmlspecialchars($payment_details['paid_by_name']) ?> <?=__('on')?> <?= date('d M Y H:i', strtotime($payment_details['created_at'])) ?></p>
                                                         <?php if ($assigned_payer_name): ?>
                                                            <p><strong><?=__('payable_assigned_to')?>:</strong> <?= htmlspecialchars($assigned_payer_name) ?></p>
                                                         <?php endif; ?>
                                                        <?php if($payment_details['note']): ?>
                                                            <p><strong><?=__('note')?>:</strong> <?= htmlspecialchars($payment_details['note']) ?></p>
                                                        <?php endif; ?>
                                                        <hr>
                                                        <a href="assets/smt_payment_invoices/<?= htmlspecialchars($payment_details['payment_invoice']) ?>" target="_blank" class="btn btn-sm btn-primary"><i class="mdi mdi-eye-outline"></i> <?=__('view_payment_invoice')?></a>
                                                    </div>
                                                </div>
                                            </div>
                                            <?php endif; ?>

                                            <div class="row">
                                                <div class="col-md-12">
                                                    <div class="table-responsive">
                                                        <table class="table mt-4">
                                                            <thead>
                                                                <tr>
                                                                    <th width="70">#</th>
                                                                    <th><?=__('description_item_name_invoice_num')?></th>
                                                                    <th width="160"><?=__('location')?></th>
                                                                    <th width="80"><?=__('quantity')?></th>
                                                                    <th width="120"><?=__('unit_cost')?> <i class="icon-saudi_riyal" style="font-size: 13px !important;"></i></th>
                                                                    <th width="130"><?=__('item_value')?> <i class="icon-saudi_riyal" style="font-size: 13px !important;"></i></th>
                                                                    <th width="70"><?=__('vat_percent')?></th>
                                                                    <th width="100"><?=__('vat_val')?> <i class="icon-saudi_riyal" style="font-size: 13px !important;"></i></th>
                                                                    <th width="130"><?=__('amount')?> <i class="icon-saudi_riyal" style="font-size: 13px !important;"></i></th>
                                                                    <th width="100"><?=__('discount')?> <i class="icon-saudi_riyal" style="font-size: 13px !important;"></i></th>
                                                                    <th width="150" class="text-right"><?=__('total')?> <i class="icon-saudi_riyal" style="font-size: 13px !important;"></i></th>
                                                                    <?php if ($current_status_get == "draft" && $empid == $emp_id_get): ?>
                                                                        <th width="60" class="text-right"></th>
                                                                    <?php endif ?>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <?php
                                                                $x = 1;
                                                                $getdataloop = mysqli_query($conDB, "SELECT * FROM `smart_request` WHERE `inv_no`='" . escape_string($_GET['id']) . "' ");
                                                                if ($getdataloop) { // Check if query was successful
                                                                    while ($rec = mysqli_fetch_assoc($getdataloop)) {
                                                                ?>
                                                                    <tr class="set">
                                                                        <td><input type="text" class="form-control" readonly value="<?= $x++ ?>" id="row"></td>
                                                                        <td><input type="text" name="item_name[]" readonly class="form-control" value="<?= htmlspecialchars($rec["item_name"]); ?>" /></td>
                                                                        <td><input type="text" name="location[]" readonly class="form-control" value="<?= htmlspecialchars($rec["location"]); ?>" /></td>
                                                                        <td><input class="form-control" readonly type='text' name='quantity[]' value="<?= $rec["quantity"]; ?>" /></td>
                                                                        <td><input class="form-control" type='text' name='product_price[]' readonly value="<?= $rec["product_price"]; ?>" /></td>
                                                                        <td><input class="form-control" type='text' name='itmvalue[]' readonly value="<?= $rec["itmvalue"]; ?>" /></td>
                                                                        <td><input class="form-control" type='text' name='vat_rate[]' readonly value="<?= $rec["vat_rate"]; ?>" /></td>
                                                                        <td><input class="form-control" type='text' name='vat_val[]' readonly value="<?= $rec["vat_val"]; ?>" /></td>
                                                                        <td><input class="form-control" type='text' name='amount[]' readonly value="<?= $rec["amount"]; ?>" /></td>
                                                                        <td><input class="form-control" type='text' name='idiscount[]' readonly value="<?= $rec["idiscount"]; ?>" /></td>
                                                                        <td class="text-right"><input class="form-control" type='text' name='total_cost[]' readonly value="<?= $rec["total_cost"]; ?>" /></td>
                                                                        <?php if ($current_status_get == "draft" && $empid == $emp_id_get): ?>
                                                                            <td class="text-right">
                                                                                <div class="btn-group" role="group" aria-label="Edit Button">
                                                                                    <a href="javascript:void(0);" class="btn btn-sm btn-primary waves-effect editItemLineAttr bbtn" data-id="<?= $rec['id'] ?>" data-i_item_name="<?= htmlspecialchars($rec['item_name']) ?>" data-i_quantity="<?= $rec['quantity'] ?>" data-i_product_price="<?= $rec['product_price'] ?>" data-i_vat_rate="<?= $rec['vat_rate'] ?>" data-i_idiscount="<?= $rec['idiscount'] ?>" data-i_itmvalue="<?= $rec['itmvalue'] ?>" data-i_vat_val="<?= $rec['vat_val'] ?>" data-i_amount="<?= $rec['amount'] ?>" data-i_total_cost="<?= $rec['total_cost'] ?>" data-i_location="<?= htmlspecialchars($rec['location']) ?>">
                                                                                        <i class="mdi mdi-table-edit"></i>
                                                                                    </a>
                                                                                    <a href="javascript:void(0);" class="btn_remove btn btn-danger btn-sm bbtn deleteAjax" data-id="<?= $rec["id"] ?>" data-tbl="smart_request" data-file="0">
                                                                                        <i class="mdi mdi-database-minus"></i>
                                                                                    </a>
                                                                                </div>
                                                                            </td>
                                                                        <?php endif ?>
                                                                    </tr>
                                                                <?php } // end while
                                                                  } // end if $getdataloop
                                                                ?>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-9">
                                                    <div class="row">
                                                        <?php
                                                        $queryempdocu = mysqli_query($conDB, "SELECT * FROM `smt_attachment` WHERE `inv_no`='" . escape_string($_GET['id']) . "' ");
                                                        if($queryempdocu && mysqli_num_rows($queryempdocu) > 0) {
                                                            echo '<div class="col-12"><h5 class="header-title m-t-0 m-b-30">'.__('existing_attachments').'</h5></div>';

                                                            while ($recempdoc = mysqli_fetch_assoc($queryempdocu)) {
                                                                $id_empdoc_get = $recempdoc["id"];
                                                                $attachment_get = $recempdoc["attachment"];
                                                                $docu_ext_get = $recempdoc["docu_ext"];
                                                                $doc_date_reg_get = date('d, M Y h:ia', strtotime($recempdoc["created_at"]));
                                                                $fileIcon = ($docu_ext_get == "pdf" ? "pdf" : ($docu_ext_get == "xls" || $docu_ext_get == "xlsx" ? "excel" : ($docu_ext_get == "tif" ? "tif" : ($docu_ext_get == "doc" || $docu_ext_get == "docx" ? "word" : ""))));
                                                        ?>
                                                            <div class="col-lg-2 col-xl-2">
                                                                <div class="file-man-box">
                                                                    <?php if ($current_status_get == "draft" && $empid == $emp_id_get): ?>
                                                                        <a href="javascript:void(0);" class="file-close deleteAjax" data-id="<?= $id_empdoc_get ?>" data-tbl="smt_attachment" data-file="1" data-column="attachment"><i class="mdi mdi-close-circle"></i></a>
                                                                    <?php endif ?>
                                                                    <div class="file-img-box showAttach" style="cursor: pointer;" data-target="#ShowModal" data-id="<?= $id_empdoc_get ?>" data-i_attachment="<?= htmlspecialchars($attachment_get) ?>">
                                                                        <?php if (in_array($docu_ext_get, ["pdf", "xls", "xlsx", "doc", "docx", "tif"]) && $fileIcon): ?>
                                                                            <img src="assets/images/file_icons/<?= $fileIcon ?>.svg" alt="file icon" />
                                                                        <?php elseif (!in_array($docu_ext_get, ["pdf", "xls", "xlsx", "doc", "docx", "tif"])) : ?>
                                                                            <img src="./assets/smt_attachment/<?= htmlspecialchars($attachment_get) ?>" alt="attachment image" style="max-height: 100px; object-fit: contain;"/>
                                                                        <?php else: ?>
                                                                             <img src="assets/images/file_icons/blank.svg" alt="file icon" />
                                                                        <?php endif ?>
                                                                    </div>
                                                                    <a href="./downloadFile.php?file=./assets/smt_attachment/<?= urlencode($attachment_get) ?>" class="file-download"><i class="mdi mdi-download"></i></a> <!-- urlencode filename -->
                                                                    <div class="file-man-title"><p class="mb-0"><small><?= $doc_date_reg_get ?></small></p></div>
                                                                </div>
                                                            </div>
                                                        <?php } // end while $recempdoc
                                                          } // end if $queryempdocu
                                                        ?>
                                                    </div>
                                                </div>
                                                <div class="col-3" id="gtotal">
                                                    <div class="float-right">
                                                        <div class="input-group mb-2">
                                                            <div class="input-group-prepend"><div class="input-group-text"><?=__('net_total_without_vat')?></div></div>
                                                            <input class="form-control subtotal" type='text' id='subtotal' name='subtotal' readonly value="<?= round($total_cost_get, 2); ?>" />
                                                            <div class="input-group-prepend"><div class="input-group-text currencyicon"><i class="icon-saudi_riyal" style="font-size: 15px !important;"></i></div></div>
                                                        </div>
                                                        <div class="input-group mb-2">
                                                            <div class="input-group-prepend"><div class="input-group-text"><?=__('vat_15_percent')?></div></div>
                                                            <input class="form-control vat" type='text' id='vat' name='vat' readonly value="<?= round($vat_get, 2); ?>" />
                                                            <div class="input-group-prepend"><div class="input-group-text currencyicon"><i class="icon-saudi_riyal" style="font-size: 15px !important;"></i></div></div>
                                                        </div>
                                                        <div class="input-group mb-2">
                                                            <div class="input-group-prepend"><div class="input-group-text"><?=__('total_before_disc')?></div></div>
                                                            <input class="form-control total" type='text' id='total' name='total' readonly value="<?= round($total, 2); ?>" />
                                                            <div class="input-group-prepend"><div class="input-group-text currencyicon"><i class="icon-saudi_riyal" style="font-size: 15px !important;"></i></div></div>
                                                        </div>
                                                        <div class="input-group mb-2">
                                                            <div class="input-group-prepend"><div class="input-group-text"><?=__('discount')?></div></div>
                                                            <input class="form-control discount" type='text' id='discount' name='discount' readonly value="<?= round($discount_get, 2); ?>" />
                                                            <div class="input-group-prepend"><div class="input-group-text currencyicon"><i class="icon-saudi_riyal" style="font-size: 15px !important;"></i></div></div>
                                                        </div>
                                                        <div class="input-group mb-2">
                                                            <div class="input-group-prepend"><div class="input-group-text"><?=__('grand_total')?></div></div>
                                                            <input class="form-control grandtotal" type='text' id='grandtotal' name='grandtotal' readonly value="<?= round($gtotal, 2); ?>" />
                                                            <div class="input-group-prepend"><div class="input-group-text currencyicon"><i class="icon-saudi_riyal" style="font-size: 15px !important;"></i></div></div>
                                                        </div>
                                                    </div>
                                                    <div class="clearfix"></div>
                                                </div>
                                            </div>

                                            <div class="hidden-print mt-4 mb-4">
                                                <div class="text-right">
                                                    <!-- REMOVED: Generic submit button. Specific buttons are now in the conditional blocks -->
                                                    <?php if ($current_status_get == "draft" && $empid == $emp_id_get): ?>
                                                        <a href="add_line_request.php?id=<?= htmlspecialchars($_GET['id']) ?>" class="btn btn-success btn-sm bbtn" title="Add field"><?=__('add_line')?> <i class="mdi mdi-database-plus"></i></a>
                                                        <a href="javascript:void(0);" class="btn btn-warning waves-effect waves-light editReqAttr"
                                                           data-sub_type="<?= htmlspecialchars($sub_type_get) ?>"
                                                           data-sub_title="<?= htmlspecialchars($sub_title_get) ?>"
                                                           data-remarks="<?= htmlspecialchars($remarks_get) ?>"
                                                           data-id="<?= htmlspecialchars($invnoget) ?>"><i class="fa fa-pencil m-r-5"></i> <?=__('edit_request_details')?></a>
                                                    <?php endif; ?>
                                                    <a href="./all_requests.php" class="btn btn-dark waves-effect waves-light"><i class="fa fa-angle-double-left"></i> <?=__('back_button')?></a>
                                                    <a href="smt_print.php?id=<?= htmlspecialchars($invnoget) ?>" class="btn btn-primary waves-effect waves-light" target="_blank"><i class="fa fa-print m-r-5"></i> <?=__('print')?></a>
                                                    <?php
                                                        // Show Process Payment button only if:
                                                        // 1. Request is Approved
                                                        // 2. The logged-in user IS the assigned payer
                                                        if ($show_process_payment_button) {
                                                    ?>
                                                        <button type="button" class="btn btn-danger waves-effect waves-light" id="processPaymentBtn"><i class="fa fa-money-bill-wave m-r-5"></i> <?=__('process_payment')?></button>
                                                    <?php } ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4 preview" id="ShowModal" style="display: none;">
                                        <div class="card-box project-box" style="height: 97% !important;">
                                            <a class='btn btn-primary btn-sm zoomFile'><i class='fa fa-paperclip'></i> <?=__('make_it_zoom')?></a>
                                            <div class="dropdown float-right"><a href="javascript:void(0);" class="" id="closeTab"><h3 class="m-0 text-muted"><i class="mdi mdi-close"></i></h3></a></div><hr>
                                            <div class="previewImg"></div>
                                        </div>
                                    </div>
                                </div>
                            </form>
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
    <script src="assets/js/jquery.app.js"></script>
    
    <script>
        // Function to display popup (ensure it's defined or included)
        function displayPopup(url) {
           window.open(url, 'popupWindow', 'width=800,height=600,scrollbars=yes');
        }

        function showAttachment() {
            $('.attachmentDIV').removeClass('noneDIV').addClass('showDIV');
        }
        function hideAttachment() {
            $('.attachmentDIV').removeClass('showDIV').addClass('noneDIV');
        }

        jQuery('.showAttach').on('click', function(event) {
            var img = $(this).data('i_attachment');
            // Basic security check for filename
             if (!img || typeof img !== 'string' || img.includes('..') || img.startsWith('/')) {
                 console.error("Invalid attachment path");
                 return;
             }
            $(".previewImg").empty().append("<iframe src='./assets/smt_attachment/" + encodeURIComponent(img) + "' frameborder='0' scrolling='yes' id='iFramePreview' style='width:100%; height: 500px;'></iframe>"); // Added style
            $(".zoomFile").attr("href", "javascript:displayPopup('./assets/smt_attachment/" + encodeURIComponent(img) + "')");
            jQuery('.preview').show('slow');
            $("#main-content").addClass('col-md-8').removeClass('col-md-12');
        });
        jQuery('#closeTab').on('click', function(event) {
            jQuery('.preview').hide('slow');
            $("#main-content").removeClass('col-md-8').addClass('col-md-12');
             $(".previewImg").empty(); // Clear iframe content
        });

        $(document).ready(function() {
            // Initialize the form validation
            $('form').parsley();

             // HR Department ID - UPDATED to 5
            const HR_DEPT_ID = 5;

            $("#statlist").change(function() {
                const selectedAction = $(this).val();
                if (selectedAction === "reject") {
                    $("#RejectDIV").show();
                    $("#RejectInput").prop('required', true).parsley().validate();
                    $("#cc_hr_select_div").hide(); // Hide CC on reject
                } else {
                    $("#RejectDIV").hide();
                    $("#RejectInput").prop('required', false).parsley().validate();
                     // Show CC only for HR users selecting 'approve'
                    if (window.currentUserDept == HR_DEPT_ID && selectedAction === 'approve') {
                        $("#cc_hr_select_div").show();
                    } else {
                         $("#cc_hr_select_div").hide();
                    }
                }
            }).change(); // Trigger change on load to set initial state


            // NEW Select2 for dynamic approver list with BADGE and Department
            // Helper function to capitalize first letter and handle role mapping
            function getRoleText(userType) {
                 if (!userType) return '';
                 let role = userType.toLowerCase(); // Work with lowercase
                 switch (role) {
                     case 'dept_user': return 'Manager'; // Explicitly map dept_user
                     case 'assistant': return 'Assistant'; // Explicitly map assistant
                     // Add other specific mappings if needed (e.g., 'gm' -> 'General Manager')
                     case 'gm': return 'General Manager';
                     case 'hr': return 'HR'; // Keep HR simple if desired, or map based on dept
                     case 'administrator': return 'Admin'; // Shorten Admin
                     // Default: Capitalize the first letter if no specific mapping
                     default:
                         return userType.charAt(0).toUpperCase() + userType.slice(1);
                 }
            }


            function formatApprover (approver) {
                if (!approver.id) { return approver.text; }
                var $element = $(approver.element);
                var userType = $element.data('type') || '';
                var deptId = $element.data('dept') || '';
                var deptName = window.departmentMap[deptId] || ''; // Get dept name from map

                // Get role text based on userType
                let roleText = getRoleText(userType); // Use helper function

                // Construct badge text: Dept Name + Role Text (e.g., Finance Manager)
                var badgeText = deptName ? `${deptName} ${roleText}` : roleText;
                
                var badgeHtml = badgeText ? '<span class="user-type-badge ' + userType + ' select2-results__option .user-type-badge">' + badgeText + '</span>' : '';

                var $approver = $(
                    // Wrap the main text in a span to allow flexbox to manage space
                    '<span class="select2-option-text">' + approver.text + '</span>' + badgeHtml
                );
                return $approver;
            };

            function formatApproverSelection (approver) {
                 if (!approver.id) { return approver.text; }
                 var $element = $(approver.element);
                 var userType = $element.data('type') || '';
                 var deptId = $element.data('dept') || '';
                 var deptName = window.departmentMap[deptId] || '';

                 // Get role text based on userType
                 let roleText = getRoleText(userType); // Use helper function

                 // Construct badge text: Dept Name + Role Text
                 var badgeText = deptName ? `${deptName} ${roleText}` : roleText;

                 var badgeHtml = badgeText ? '<span class="user-type-badge ' + userType + ' select2-selection__rendered-badge">' + badgeText + '</span>' : ''; // Different class for selection
                 
                 // Return structure for flexbox alignment in the selection area
                 var $approver = $(
                     // Wrap the main text in a span
                     '<span class="select2-selection-text">' + approver.text + '</span>' + badgeHtml
                 );
                 return $approver;
            };

            $('#approver-select').select2({
                placeholder: $(this).data('placeholder'),
                allowClear: true,
                templateResult: formatApprover, // Function to render dropdown options
                templateSelection: formatApproverSelection // Function to render selected option
            });
             // Also initialize the payable_by_emp_id dropdown if it exists
             $('#payable_by_emp_id').select2({
                 placeholder: '<?=__('select_finance_employee')?>',
                 allowClear: true
             });

             // NEW: Initialize HR CC Select2
             $('#cc_hr_select').select2({
                 placeholder: '<?=__('select_employees_to_cc_optional')?>',
                 allowClear: true,
                 width: '100%' // Ensure it takes full width
             });


            // NEW Dynamic Approver List Logic
            let approverCount = 0;

            function updateValidation() {
                 const parsleyInstance = $('#min-approver-check').parsley();
                 if (!parsleyInstance) return; // Exit if parsley not initialized

                if (approverCount > 0) {
                    $('#min-approver-check').val('ok'); // Satisfy parsley
                } else {
                    $('#min-approver-check').val(''); // Fail parsley
                }
                // Re-validate the dummy input using the instance
                 parsleyInstance.validate();
            }

            $('#add-approver-btn').on('click', function() {
                const selectedApprover = $('#approver-select').find('option:selected');
                const approverId = selectedApprover.val();
                const approverName = selectedApprover.text(); // Text without badge for display list

                if (!approverId) {
                    Swal.fire('<?=__('error')?>', '<?=__('select_approver_from_list')?>', 'warning');
                    return;
                }

                // Check if already added
                let alreadyAdded = false;
                $('#approver-list-container').find('input[name="approvers[]"]').each(function() {
                    if ($(this).val() == approverId) {
                        alreadyAdded = true;
                    }
                });

                if (alreadyAdded) {
                    Swal.fire('<?=__('error')?>', '<?=__('approver_already_added')?>', 'warning');
                    return;
                }

                // Add to list
                approverCount++;
                const approverLevel = approverCount;
                const tagHtml = `
                    <div class="approver-tag" data-id="${approverId}">
                        <span>${approverLevel}. ${approverName}</span>
                        <input type="hidden" name="approvers[]" value="${approverId}">
                        <button type="button" class="remove-approver-btn" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                `;
                $('#approver-list-container').append(tagHtml);

                // Reset select2
                $('#approver-select').val(null).trigger('change');
                updateValidation();
            });

            // Handle remove approver
            $(document).on('click', '.remove-approver-btn', function() {
                $(this).closest('.approver-tag').remove();

                // Re-number list
                approverCount = 0;
                $('#approver-list-container').find('.approver-tag').each(function() {
                    approverCount++;
                    const currentSpan = $(this).find('span');
                    const nameParts = currentSpan.text().split('. ');
                    const approverName = nameParts.length > 1 ? nameParts.slice(1).join('. ') : currentSpan.text(); // Get name after number, handle names with '.'
                    currentSpan.text(`${approverCount}. ${approverName}`);
                });
                updateValidation();
            });

            // Initial validation check (call only if the element exists)
            if ($('#min-approver-check').length) {
                updateValidation();
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
                    Swal.fire({
                        title: '<?=__('success')?>!',
                        text: result.value.message,
                        icon: 'success'
                    }).then(() => {
                        location.reload(); // Reload to reflect changes
                    });
                }
            })
        });


        // SweetAlert2 for Editing Request Details
        $(document).on('click', '.editReqAttr', function(e) {
            e.preventDefault();
            const id = $(this).data('id');
            const sub_type = $(this).data('sub_type');
            const sub_title = $(this).data('sub_title');
            const remarks = $(this).data('remarks');

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
                allowOutsideClick: () => !Swal.isLoading()
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
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
            var i_quantity = $(this).data('i_quantity') || 1;
            var i_product_price = $(this).data('i_product_price') || 0;
            var i_vat_rate = $(this).data('i_vat_rate') || 15; // Default VAT rate
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
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: '<?=__('yes_update')?>',
                showLoaderOnConfirm: true,
                allowOutsideClick: false,
                width: '80%',
                didOpen: function() {
                    $('#itemid').val(id);
                    $('.item_name').val(i_item_name);
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
                    const DEFAULT_VAT_RATE = 15;

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
                    Swal.fire({
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
             const DEFAULT_VAT_RATE = 15;
             // Added required and parsley attributes, min="0" for numbers
            var strView =
                `<form id="submitEditLineForm" data-parsley-validate>
                    <div class="form-row customSweetAlertMLR">
                        <div class="form-group col-md-4"><label><?=__('item_name')?>*</label><input type="text" name="item_name" class="form-control item_name" required></div>
                        <div class="form-group col-md-3"><label><?=__('location')?>*</label><select id="location" class="form-control location" name="location" required><option value=""><?=__('select')?></option></select></div>
                        <div class="form-group col-md-2"><label><?=__('quantity')?>*</label><input type="number" step="any" min="0" name="quantity" class="form-control quantity" id='quantity' required data-parsley-type="number" data-parsley-min="0"></div>
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
    <!-- Add this line RIGHT BEFORE the closing </body> tag if notifications.js is not already included -->
    <script src="assets/js/notifications.js"></script>
</body>
</html>