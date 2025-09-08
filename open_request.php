<?php
/*
MODIFICATION SUMMARY:
- Added: When a Department Manager views a request pending their approval, a dropdown now appears, allowing them to select a specific Finance approver.
- Modified: The form submission logic for Department Manager approval has been updated to capture the selected Finance approver's ID and assign them to the request.
- Modified: The main database query now joins with the `employees` table to fetch the names of the Department Manager, Finance Manager, and General Manager.
- Modified: The "Approval Status" section in the HTML has been updated to display the name of the approver and the date of their action, providing a clearer audit trail.
- Added: A new helper function `getEmployeeDetails()` has been created to centralize fetching employee information by ID, making the code cleaner.
- Moved: The "Have Attachments" radio button section has been relocated to appear under the "Sub-Title" field.
- Added: A new approval section is now visible to Managers on their own 'draft' requests, allowing them to select a Finance approver and specify the need for GM approval before final submission.
- Modified: The form's POST handler has been updated to process the Manager's submission, correctly setting the request status to 'pending_finance_approval' and assigning the chosen approvers.
*/

require_once __DIR__ . '/includes/db.php';

// Handle AJAX Payment Processing Separately
if (isset($_POST['process_payment'])) {
    header('Content-Type: application/json');
    require_once __DIR__ . '/includes/session_check.php'; // Needed for user details

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
            mysqli_query($conDB, "UPDATE `smart_request` SET `current_status`='paid' WHERE `inv_no`='$inv_no_pay'");

            // Insert into new smt_payment table
            $insert_payment = mysqli_query($conDB, "INSERT INTO `smt_payment` (`inv_no`, `paid_amount`, `payment_invoice`, `paid_by_id`, `paid_by_name`, `note`) VALUES ('$inv_no_pay', '$paid_amount', '$attachment_name', '$empid', '$userwel', '$payment_note')");
            
            if($insert_payment){
                // Add a status log
                mysqli_query($conDB, "INSERT INTO `smt_request_status` (`emp_id`, `inv_no`, `emp_name`, `status`, `note`) VALUES ('$empid', '$inv_no_pay', '$userwel', 'paid', 'payment_processed.')");
                $response = ['status' => 'success', 'message' => __('payment_processed_successfully')];
            } else {
                $response = ['status' => 'error', 'message' => __('failed_to_save_payment_details')];
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


require_once __DIR__ . '/includes/session_check.php';
include("./includes/convertNumbersToWords.php");

require './includes/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$query = mysqli_query($conDB, "SELECT * FROM `admin_login` WHERE `id_iqama`='" . $username . "'");
if (mysqli_num_rows($query) == 1) {
    include("./includes/avatar_select.php");
}

// Modified query to fetch new approval flow columns and approver names
$getquery = mysqli_query($conDB, "SELECT 
            `smt`.*, 
            SUM(`smt`.`total_cost`) as `subtotal`, 
            SUM(`smt`.`vat_val`) as `vat_val`,
            `dpt`.`dep_nme`,
            dm.name AS dept_manager_name,
            fm.name AS finance_manager_name,
            gm.name AS gm_name
            FROM `smart_request` `smt`
            LEFT JOIN `department` `dpt` ON `dpt`.`id` = `smt`.`department`
            LEFT JOIN `employees` dm ON dm.emp_id = smt.dept_manager_id
            LEFT JOIN `employees` fm ON fm.emp_id = smt.finance_manager_id
            LEFT JOIN `employees` gm ON gm.emp_id = smt.gm_id
            WHERE `smt`.`inv_no`='" . $_GET['id'] . "' ");

while ($row = mysqli_fetch_assoc($getquery)) {
    $idno = $row["id"];
    $invnoget = $row["inv_no"];
    $tally_id_get = $row["tally_id"];
    $injazat_id_get = $row["injazat_id"];
    $total_costget = $row['subtotal'];
    $vat_get = $row['vat_val'];
    $discount_get = $row["discount"];
    $location_get = $row["location"];
    $sub_type_get = $row["sub_type"];
    $sub_title_get = $row["sub_title"];
    $prep_by_get = (explode(" ", $row["prep_by"])[0]) . " " . (explode(" ", $row["prep_by"])[1]);
    $department_get = $row["department"];
    $dep_nme_get = $row["dep_nme"];
    $remarks_get = $row["remarks"];
    $emp_id_get = $row["emp_id"];
    $created_at_get = $row["created_at"];
    
    $current_status_get = $row['current_status'];
    $dept_manager_id_get = $row['dept_manager_id'];
    $dept_manager_status_get = $row['dept_manager_status'];
    $dept_manager_note_get = $row['dept_manager_note'];
    $dept_manager_date_get = $row['dept_manager_date'];
    $dept_manager_name_get = $row['dept_manager_name']; // New variable for DM name
    $finance_manager_id_get = $row['finance_manager_id'];
    $finance_manager_status_get = $row['finance_manager_status'];
    $finance_manager_note_get = $row['finance_manager_note'];
    $finance_manager_date_get = $row['finance_manager_date'];
    $finance_manager_name_get = $row['finance_manager_name']; // New variable for Finance name
    $gm_id_get = $row['gm_id'];
    $gm_status_get = $row['gm_status'];
    $gm_note_get = $row['gm_note'];
    $gm_date_get = $row['gm_date'];
    $gm_name_get = $row['gm_name']; // New variable for GM name
    $requires_gm_approval_get = $row['requires_gm_approval'];

    $total_cost_get = $total_costget - $vat_get;
    $total = $total_cost_get + $vat_get;
    $gtotal = $total - $discount_get;
}

// Get creator details
$creator_query = mysqli_query($conDB, "SELECT `emp_id`, `dept`, `emptype` FROM `employees` WHERE `emp_id` = '$emp_id_get'");
$creator_details = mysqli_fetch_assoc($creator_query);
$creator_emptype = $creator_details['emptype'];
$creator_dept = $creator_details['dept'];


if (isset($_POST['submit'])) {
    $inv_no_po = $_POST['inv_no'];
    $note_po = mysqli_real_escape_string($conDB, $_POST['note'] ?? '');
    $status_po = $_POST['status'] ?? 'approve';

    $next_approver_email = '';
    $next_approver_name = '';
    
    // NEW LOGIC FOR MANAGER SUBMITTING THEIR OWN DRAFT
    if ($creator_emptype == 'Manager' && $current_status_get == 'draft' && $empid == $emp_id_get) {
        $finance_approver_id_po = mysqli_real_escape_string($conDB, $_POST['finance_approver_id']);
        $requires_gm_approval_po = mysqli_real_escape_string($conDB, $_POST['requires_gm_approval']);

        // Fetch finance approver details for email
        $finance_approver_details = getEmployeeDetails($finance_approver_id_po);
        $next_approver_name = $finance_approver_details['name'];
        $next_approver_email = $finance_approver_details['email'];

        // Update the request
        mysqli_query($conDB, "UPDATE `smart_request` SET 
            `dept_manager_id`='$empid', 
            `dept_manager_status`='approved', 
            `dept_manager_date`=NOW(), 
            `finance_manager_id`='$finance_approver_id_po', 
            `requires_gm_approval` = '$requires_gm_approval_po',
            `current_status`='pending_finance_approval' 
            WHERE `inv_no`='$inv_no_po'");
        
        // Add status log
        mysqli_query($conDB, "INSERT INTO `smt_request_status` (`emp_id`, `inv_no`, `emp_name`, `status`, `note`) VALUES ('$empid', '$inv_no_po', '$userwel', 'pending_finance_approval', '".__('submitted_by_manager_for_finance_approval')."')");

    } // EXISTING LOGIC FOR NON-MANAGERS
    elseif ($current_status_get == 'draft' && $empid == $emp_id_get) {
        $dept_manager = getDeptManager($creator_dept);
        if ($dept_manager) {
            $dept_manager_id = $dept_manager['emp_id'];
            mysqli_query($conDB, "UPDATE `smart_request` SET `dept_manager_id`='$dept_manager_id', `current_status`='pending_dept_manager_approval' WHERE `inv_no`='$inv_no_po'");
            mysqli_query($conDB, "INSERT INTO `smt_request_status` (`emp_id`, `inv_no`, `emp_name`, `status`, `note`) VALUES ('$empid', '$inv_no_po', '$userwel', 'pending_dept_manager_approval', '".__('submitted_for_approval')."')");
            $next_approver_name = $dept_manager['name'];
            $next_approver_email = $dept_manager['email'];
        }
    } elseif ($current_status_get == 'pending_dept_manager_approval' && $empid == $dept_manager_id_get) {
        $requires_gm_approval_po = mysqli_real_escape_string($conDB, $_POST['requires_gm_approval']);
        $finance_manager_id_po = mysqli_real_escape_string($conDB, $_POST['finance_manager_id']);
        
        if ($status_po == 'approve') {
            $finance_approver_details = getEmployeeDetails($finance_manager_id_po);
            $next_approver_name = $finance_approver_details['name'];
            $next_approver_email = $finance_approver_details['email'];

            mysqli_query($conDB, "UPDATE `smart_request` SET `dept_manager_status`='approved', `dept_manager_note`='$note_po', `dept_manager_date`=NOW(), `finance_manager_id`='$finance_manager_id_po', `current_status`='pending_finance_approval', `requires_gm_approval` = '$requires_gm_approval_po' WHERE `inv_no`='$inv_no_po'");
            mysqli_query($conDB, "INSERT INTO `smt_request_status` (`emp_id`, `inv_no`, `emp_name`, `status`, `note`) VALUES ('$empid', '$inv_no_po', '$userwel', 'pending_finance_approval', '$note_po')");
        } else { // Reject
            mysqli_query($conDB, "UPDATE `smart_request` SET `dept_manager_status`='rejected', `dept_manager_note`='$note_po', `dept_manager_date`=NOW(), `current_status`='rejected' WHERE `inv_no`='$inv_no_po'");
            mysqli_query($conDB, "INSERT INTO `smt_request_status` (`emp_id`, `inv_no`, `emp_name`, `status`, `note`) VALUES ('$empid', '$inv_no_po', '$userwel', 'rejected', '$note_po')");
        }
    } elseif ($current_status_get == 'pending_finance_approval' && $user_dept == 2) {
        if ($status_po == 'approve') {
            $check_gm_query = mysqli_query($conDB, "SELECT `requires_gm_approval` FROM `smart_request` WHERE `inv_no` = '$inv_no_po' LIMIT 1");
            $gm_approval_needed = mysqli_fetch_assoc($check_gm_query)['requires_gm_approval'];

            if ($gm_approval_needed == 1) {
                $gm = getGeneralManager();
                $gm_id = $gm['emp_id'];
                mysqli_query($conDB, "UPDATE `smart_request` SET `finance_manager_status`='approved', `finance_manager_note`='$note_po', `finance_manager_date`=NOW(), `gm_id`='$gm_id', `current_status`='pending_gm_approval' WHERE `inv_no`='$inv_no_po'");
                mysqli_query($conDB, "INSERT INTO `smt_request_status` (`emp_id`, `inv_no`, `emp_name`, `status`, `note`) VALUES ('$empid', '$inv_no_po', '$userwel', 'pending_gm_approval', '$note_po')");
                $next_approver_name = $gm['name'];
                $next_approver_email = $gm['email'];
            } else {
                mysqli_query($conDB, "UPDATE `smart_request` SET `finance_manager_status`='approved', `finance_manager_note`='$note_po', `finance_manager_date`=NOW(), `current_status`='approved' WHERE `inv_no`='$inv_no_po'");
                mysqli_query($conDB, "INSERT INTO `smt_request_status` (`emp_id`, `inv_no`, `emp_name`, `status`, `note`) VALUES ('$empid', '$inv_no_po', '$userwel', 'approved', '".__('approved_by_finance')."')");
            }
        } else {
            mysqli_query($conDB, "UPDATE `smart_request` SET `finance_manager_status`='rejected', `finance_manager_note`='$note_po', `finance_manager_date`=NOW(), `current_status`='rejected' WHERE `inv_no`='$inv_no_po'");
            mysqli_query($conDB, "INSERT INTO `smt_request_status` (`emp_id`, `inv_no`, `emp_name`, `status`, `note`) VALUES ('$empid', '$inv_no_po', '$userwel', 'rejected', '$note_po')");
        }
    } elseif ($current_status_get == 'pending_gm_approval' && $empid == $gm_id_get) {
        if ($status_po == 'approve') {
            mysqli_query($conDB, "UPDATE `smart_request` SET `gm_status`='approved', `gm_note`='$note_po', `gm_date`=NOW(), `current_status`='approved' WHERE `inv_no`='$inv_no_po'");
            mysqli_query($conDB, "INSERT INTO `smt_request_status` (`emp_id`, `inv_no`, `emp_name`, `status`, `note`) VALUES ('$empid', '$inv_no_po', '$userwel', 'approved', '$note_po')");
        } else {
            mysqli_query($conDB, "UPDATE `smart_request` SET `gm_status`='rejected', `gm_note`='$note_po', `gm_date`=NOW(), `current_status`='rejected' WHERE `inv_no`='$inv_no_po'");
            mysqli_query($conDB, "INSERT INTO `smt_request_status` (`emp_id`, `inv_no`, `emp_name`, `status`, `note`) VALUES ('$empid', '$inv_no_po', '$userwel', 'rejected', '$note_po')");
        }
    }

    if (!empty($next_approver_email)) {
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.office365.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'noreply@almutlak.com';
            $mail->Password = 'HO@66887';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;
            $mail->CharSet = 'UTF-8';
            $mail->setFrom("noreply@almutlak.com", "Al Mutlak System");
            $mail->addAddress($next_approver_email, $next_approver_name);
            $mail->isHTML(true);
            $mail->Subject = 'Smart Request for your Approval from ' . $userwelext . " - " . $invnoget;
            $bodycus = $mail->msgHTML(file_get_contents('./includes/PHPMailerMaster/email_contant_body.php'), dirname(__FILE__));
            $bodycus = preg_replace('/\\\\/', '', $bodycus);
            $bodycus = str_replace('$userwelext', $userwelext, $bodycus);
            $bodycus = str_replace('$sub_title_get', $sub_title_get, $bodycus);
            $bodycus = str_replace('$invnoget', $invnoget, $bodycus);
            $bodycus = str_replace('$dept', $usrdeptnme, $bodycus);
            $mail->Body = $bodycus;
            $mail->send();
        } catch (Exception $e) {
            error_log("Mailer Error: " . $e->getMessage());
        }
    }
    salert("Success!", __('success_record_submitted'), "success", "all_requests.php");
}

// Logic to determine which approvers to list in the dropdown
$applst = [];
if ($current_status_get == 'draft') {
    if ($creator_emptype == 'Manager') {
        $approvers = getFinancePersonnel();
    } else {
        $approvers = [getDeptManager($creator_dept)];
    }
    if ($approvers[0]) {
        foreach ($approvers as $rec) {
            $applst[] = "<option value=\"" . $rec['emp_id'] . "\">" . $rec['name'] . "</option>";
        }
    }
}
$applist = implode("", $applst);

$lstapp =
    <<<HTML
<div class="input-group-prepend">
    <div class="input-group-text"><?=__('approved_by')?> *</div>
</div>
<select class="form-control" name="approv_by" required>
<option value=""><?=__('select')?></option>
    $applist
</select>
HTML;

// New Status Display Logic
$status_get = "";
$rejection_note = "";
switch ($current_status_get) {
    case "draft":
        $status_get = "<input class='form-control bg-secondary border-secondary text-white' type='text' value='" . __('draft_not_submitted') . "' readonly />";
        break;
    case "pending_dept_manager_approval":
        $status_get = "<input class='form-control bg-custom border-custom text-white' type='text' value='" . __('pending_department_manager_approval') . "' readonly />";
        break;
    case "pending_finance_approval":
        $status_get = "<input class='form-control bg-warning border-warning text-white' type='text' value='" . __('pending_finance_approval') . "' readonly />";
        break;
    case "pending_gm_approval":
        $status_get = "<input class='form-control bg-primary border-primary text-white' type='text' value='" . __('pending_general_manager_approval') . "' readonly />";
        break;
    case "approved":
        $status_get = "<input class='form-control bg-success border-success text-white' type='text' value='" . __('approved') . "' readonly />";
        break;
    case "rejected":
        if ($gm_status_get == 'rejected') {
            $rejected_by = __('rejected_by_gm');
            $rejection_note = $gm_note_get;
        } elseif ($finance_manager_status_get == 'rejected') {
            $rejected_by = __('rejected_by_finance');
            $rejection_note = $finance_manager_note_get;
        } elseif ($dept_manager_status_get == 'rejected') {
            $rejected_by = __('rejected_by_dm');
            $rejection_note = $dept_manager_note_get;
        } else {
            $rejected_by = __('rejected_by_system');
        }
        $status_get = "<input class='form-control bg-danger border-danger text-white' type='text' value='$rejected_by' readonly />";
        break;
    case "Paid":
        $status_get = "<div class='input-group'>
            <input class='form-control bg-success border-success text-white' type='text' value='" . __('payment_paid') . "' readonly />
            <span class='input-group-text bg-success border-success text-white'><i class='mdi mdi-approval'></i></span>
        </div>";
        break;
    default:
        $status_get = "<input class='form-control bg-danger border-danger text-white' type='text' value='" . __('unknown_status') . "' readonly />";
}

// Get Payment Details if Paid
$payment_details = null;
if ($current_status_get == 'paid') {
    $payment_query = mysqli_query($conDB, "SELECT * FROM `smt_payment` WHERE `inv_no` = '$invnoget' ORDER BY `id` DESC LIMIT 1");
    if(mysqli_num_rows($payment_query) > 0){
        $payment_details = mysqli_fetch_assoc($payment_query);
    }
}

?>

<!doctype html>
<html lang="<?= $current_lang ?? 'en' ?>" <?= ($is_rtl ?? false) ? 'dir="rtl"' : '' ?>>

<head>
    <meta charset="utf-8" />
    <title><?= $site_title ?> - <?= $sub_title_get ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta content="Anees Afzal" name="author" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />

    <!-- App favicon -->
    <link rel="shortcut icon" href="assets/images/favicon.ico">

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
        .approval-status { padding: 10px; margin-bottom: 10px; border-left: 4px solid #ccc; }
        .approval-status.pending { border-color: #ffc107; }
        .approval-status.approved { border-color: #28a745; }
        .approval-status.rejected { border-color: #dc3545; }
        .customSweetAlertMLR { margin-left: auto; margin-right: auto; }
        .radioalign { margin-right: 20px; }
        .atch { cursor: pointer; }
    </style>
    <?php if ($is_rtl): ?>
            <link href="assets/css/style_rtl.css" rel="stylesheet" type="text/css" />
        <?php endif; ?>
		<script> window.lang = <?= json_encode($GLOBALS['translations'] ?? []) ?>;</script>
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
                        <span><img src="assets/images/logo.png" alt="" height="22"></span>
                        <i><img src="assets/images/logo_sm.png" alt="" height="28"></i>
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
                            <form action="open_request.php?id=<?= $_GET['id'] ?>" method="post" enctype="multipart/form-data">
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
                                                            <input class="form-control" type='text' value="<?= date("d F Y", strtotime($created_at_get)) ?>" readonly />
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
                                                            $query_chkattach = mysqli_query($conDB, "SELECT * FROM `smt_attachment` WHERE `inv_no`='" . $_GET['id'] . "' ");
                                                            if (mysqli_num_rows($query_chkattach) <= 5) { ?>
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
                                                                    <a href="javascript:void(0);" class="btn btn-sm btn-custom waves-effect waves-light noneDIV checkattach attachmentDIV smt_attachment" data-attach="ok" data-inv_no="<?= $invnoget ?>">
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

                                                        <!-- Approval Status Trail -->
                                                        <div class="mt-4">
                                                            <h5><?=__('approval_status')?></h5>
                                                            <div class="approval-status <?= $dept_manager_status_get ? ($dept_manager_status_get == 'approved' ? 'approved' : ($dept_manager_status_get == 'rejected' ? 'rejected' : 'pending')) : '' ?>">
                                                                <strong><?=__('department_manager')?>:</strong> 
                                                                <span><?= __($dept_manager_status_get) ?? __('pending') ?></span>
                                                                <?php if($dept_manager_name_get): ?>
                                                                    <br><small><?=__('by')?> <?= htmlspecialchars($dept_manager_name_get) ?> <?=__('on')?> <?= $dept_manager_date_get ? date('d M Y H:i', strtotime($dept_manager_date_get)) : '' ?></small>
                                                                <?php endif; ?>
                                                            </div>
                                                            <div class="approval-status <?= $finance_manager_status_get ? ($finance_manager_status_get == 'approved' ? 'approved' : ($finance_manager_status_get == 'rejected' ? 'rejected' : 'pending')) : '' ?>">
                                                                <strong><?=__('finance')?>:</strong>
                                                                <span><?= __($finance_manager_status_get) ?? __('pending') ?></span>
                                                                <?php if($finance_manager_name_get): ?>
                                                                    <br><small><?=__('by')?> <?= htmlspecialchars($finance_manager_name_get) ?> <?=__('on')?> <?= $finance_manager_date_get ? date('d M Y H:i', strtotime($finance_manager_date_get)) : '' ?></small>
                                                                <?php endif; ?>
                                                            </div>
                                                            <?php if($requires_gm_approval_get == 1): ?>
                                                            <div class="approval-status <?= $gm_status_get ? ($gm_status_get == 'approved' ? 'approved' : ($gm_status_get == 'rejected' ? 'rejected' : 'pending')) : '' ?>">
                                                                <strong><?=__('general_manager')?>:</strong>
                                                                <span><?= __($gm_status_get) ?? __('pending') ?></span>
                                                                <?php if($gm_name_get): ?>
                                                                    <br><small><?=__('by')?> <?= htmlspecialchars($gm_name_get) ?> <?=__('on')?> <?= $gm_date_get ? date('d M Y H:i', strtotime($gm_date_get)) : '' ?></small>
                                                                <?php endif; ?>
                                                            </div>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-4">
                                                    <div class="noneDIV attachmentDIV mt-3" id="">
                                                        <img src="qrconfig_smartrequest.php?id=<?= $_GET['id'] ?>" />
                                                        <p><?=__('scan_qr_for_attachments')?></p>
                                                    </div>
                                                </div>
                                                <div class="col-4 ">
                                                    <div class="mt-3 float-right">
                                                        <div class="input-group mb-2">
                                                            <div class="input-group-prepend"><div class="input-group-text"><?=__('invoice_no')?>:</div></div>
                                                            <input class="form-control" type='text' name='inv_no' value="<?= $invnoget ?>" readonly />
                                                        </div>
                                                        <div class="input-group mb-2">
                                                            <div class="input-group-prepend"><div class="input-group-text"><?=__('department')?>:</div></div>
                                                            <input class="form-control" type='text' value="<?= htmlspecialchars($dep_nme_get) ?>" readonly />
                                                        </div>
                                                        <div class="input-group mb-2">
                                                            <div class="input-group-prepend"><div class="input-group-text"><?=__('prepared_by')?>:</div></div>
                                                            <input class="form-control" type='text' value="<?= htmlspecialchars($prep_by_get) ?>" readonly />
                                                        </div>

                                                        <?php
                                                        $show_submit_button = false;
                                                        if (
                                                            ($current_status_get == 'pending_dept_manager_approval' && $empid == $dept_manager_id_get) || 
                                                            ($current_status_get == 'pending_finance_approval' && $user_dept == 2) ||
                                                            ($current_status_get == 'pending_gm_approval' && $empid == $gm_id_get) ||
                                                            ($current_status_get == "draft" && $empid == $emp_id_get)
                                                        ) {
                                                            $show_submit_button = true;
                                                        }
                                                        
                                                        // This block is for managers to submit their own requests
                                                        if ($empid == $emp_id_get && $creator_emptype == 'Manager' && $current_status_get == 'draft'): ?>
                                                            <div class="input-group mb-2">
                                                                <div class="input-group-prepend"><div class="input-group-text"><?=__('finance_approver')?> <span class="text-danger ml-2">*</span></div></div>
                                                                <select class="form-control" name="finance_approver_id" required>
                                                                    <option value=""><?=__('select_finance_approver')?></option>
                                                                    <?php
                                                                        $finance_personnel = getFinancePersonnel();
                                                                        foreach($finance_personnel as $person){
                                                                            echo "<option value='{$person['emp_id']}'>{$person['name']}</option>";
                                                                        }
                                                                    ?>
                                                                </select>
                                                            </div>
                                                            <div class="input-group mb-2">
                                                                <div class="input-group-prepend"><div class="input-group-text"><?=__('forward_to_gm')?> <span class="text-danger ml-2">*</span></div></div>
                                                                <select class="form-control" name="requires_gm_approval" required>
                                                                    <option value=""><?=__('select')?></option>
                                                                    <option value="0"><?=__('no')?></option>
                                                                    <option value="1"><?=__('yes')?></option>
                                                                </select>
                                                            </div>
                                                        <?php // This block is for other approvers (non-managers or managers approving others' requests)
                                                        elseif ($show_submit_button && !($creator_emptype == 'Manager' && $current_status_get == 'draft')): ?>
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
                                                            <?php if ($current_status_get == 'pending_dept_manager_approval' && $empid == $dept_manager_id_get): ?>
                                                            <div class="input-group mb-2">
                                                                <div class="input-group-prepend"><div class="input-group-text"><?=__('finance_approver')?> <span class="text-danger ml-2">*</span></div></div>
                                                                <select class="form-control" name="finance_manager_id" required>
                                                                    <option value=""><?=__('select_finance_approver')?></option>
                                                                    <?php
                                                                        $finance_personnel = getFinancePersonnel();
                                                                        foreach($finance_personnel as $person){
                                                                            echo "<option value='{$person['emp_id']}'>{$person['name']}</option>";
                                                                        }
                                                                    ?>
                                                                </select>
                                                            </div>
                                                            <div class="input-group mb-2">
                                                                <div class="input-group-prepend"><div class="input-group-text"><?=__('forward_to_gm')?> <span class="text-danger ml-2">*</span></div></div>
                                                                <select class="form-control" name="requires_gm_approval" required>
                                                                    <option value=""><?=__('select_gm_approver')?></option>
                                                                    <option value="0"><?=__('no')?></option>
                                                                    <option value="1"><?=__('yes')?></option>
                                                                </select>
                                                            </div>
                                                            <?php endif; ?>
                                                        <?php endif; ?>

                                                        <div class="input-group mb-2" id="RejectDIV" style="display: none;">
                                                            <div class="input-group-prepend"><div class="input-group-text"><?=__('rejection_note')?><span class="text-danger ml-2">*</span></div></div>
                                                            <input type='text' class="form-control" name="note" id="RejectInput" />
                                                        </div>
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
                                                        <?php if($payment_details['note']): ?>
                                                            <p><strong><?=__('note')?>:</strong> <?= htmlspecialchars($payment_details['note']) ?></p>
                                                        <?php endif; ?>
                                                        <hr>
                                                        <a href="assets/smt_payment_invoices/<?= $payment_details['payment_invoice'] ?>" target="_blank" class="btn btn-sm btn-primary"><i class="mdi mdi-eye-outline"></i> <?=__('view_payment_invoice')?></a>
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
                                                                $getdataloop = mysqli_query($conDB, "SELECT * FROM `smart_request` WHERE `inv_no`='" . $_GET['id'] . "' ");
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
                                                                <?php } ?>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-9">
                                                    <div class="row">
                                                        <?php
                                                        $queryempdocu = mysqli_query($conDB, "SELECT * FROM `smt_attachment` WHERE `inv_no`='" . $_GET['id'] . "' ");
                                                        if(mysqli_num_rows($queryempdocu) > 0) {
                                                            echo '<div class="col-12"><h5 class="header-title m-t-0 m-b-30">'.__('existing_attachments').'</h5></div>';
                                                        }
                                                        while ($recempdoc = mysqli_fetch_assoc($queryempdocu)) {
                                                            $id_empdoc_get = $recempdoc["id"];
                                                            $attachment_get = $recempdoc["attachment"];
                                                            $docu_ext_get = $recempdoc["docu_ext"];
                                                            $doc_date_reg_get = date('d, M Y h:ia', strtotime($recempdoc["created_at"]));
                                                            $fileIcon = ($docu_ext_get == "pdf" ? "pdf" : ($docu_ext_get == "xls" ? "excel" : ($docu_ext_get == "tif" ? "tif" : "")));
                                                        ?>
                                                            <div class="col-lg-2 col-xl-2">
                                                                <div class="file-man-box">
                                                                    <?php if ($current_status_get == "draft" && $empid == $emp_id_get): ?>
                                                                        <a href="javascript:void(0);" class="file-close deleteAjax" data-id="<?= $id_empdoc_get ?>" data-tbl="smt_attachment" data-file="1" data-column="attachment"><i class="mdi mdi-close-circle"></i></a>
                                                                    <?php endif ?>
                                                                    <div class="file-img-box showAttach" style="cursor: pointer;" data-target="#ShowModal" data-id="<?= $id_empdoc_get ?>" data-i_attachment="<?= $attachment_get ?>">
                                                                        <?php if (in_array($docu_ext_get, ["pdf", "xls", "xlsx", "doc", "docx", "tif"])): ?>
                                                                            <img src="assets/images/file_icons/<?= $fileIcon ?>.svg" alt="file icon" />
                                                                        <?php else: ?>
                                                                            <img src="./assets/smt_attachment/<?= $attachment_get ?>" alt="attachment image" />
                                                                        <?php endif ?>
                                                                    </div>
                                                                    <a href="./downloadFile.php?file=./assets/smt_attachment/<?= $attachment_get ?>" class="file-download"><i class="mdi mdi-download"></i></a>
                                                                    <div class="file-man-title"><p class="mb-0"><small><?= $doc_date_reg_get ?></small></p></div>
                                                                </div>
                                                            </div>
                                                        <?php } ?>
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
                                                    <?php if ($show_submit_button) : ?>
                                                        <button type="submit" name="submit" class="btn btn-info waves-effect waves-light"><?=__('submit_for_approval')?></button>
                                                    <?php endif; ?>
                                                    <?php if ($current_status_get == "draft" && $empid == $emp_id_get): ?>
                                                        <a href="add_line_request.php?id=<?= htmlspecialchars($_GET['id']) ?>" class="btn btn-success btn-sm bbtn" title="Add field"><?=__('add_line')?> <i class="mdi mdi-database-plus"></i></a>
                                                        <a href="javascript:void(0);" class="btn btn-warning waves-effect waves-light editReqAttr" data-sub_type="<?= htmlspecialchars($sub_type_get) ?>" data-sub_title="<?= htmlspecialchars($sub_title_get) ?>" data-tally_id="<?= htmlspecialchars($tally_id_get) ?>" data-injazat_id="<?= htmlspecialchars($injazat_id_get) ?>" data-remarks="<?= htmlspecialchars($remarks_get) ?>" data-id="<?= htmlspecialchars($invnoget) ?>"><i class="fa fa-pencil m-r-5"></i> <?=__('edit_request_details')?></a>
                                                    <?php endif; ?>
                                                    <a href="./all_requests.php" class="btn btn-dark waves-effect waves-light"><i class="fa fa-angle-double-left"></i> <?=__('back_button')?></a>
                                                    <a href="smt_print.php?id=<?= htmlspecialchars($invnoget) ?>" class="btn btn-primary waves-effect waves-light" target="_blank"><i class="fa fa-print m-r-5"></i> <?=__('print')?></a>
                                                    <?php if ($current_status_get == "approved" && $user_type == 'assistant' && $user_dept == 2) { ?>
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
        function showAttachment() {
            $('.attachmentDIV').removeClass('noneDIV').addClass('showDIV');
        }
        function hideAttachment() {
            $('.attachmentDIV').removeClass('showDIV').addClass('noneDIV');
        }

        jQuery('.showAttach').on('click', function(event) {
            var img = $(this).data('i_attachment');
            $(".previewImg").empty().append("<iframe src='./assets/smt_attachment/" + img + "' frameborder='0' scrolling='no' id='iFramePreview'></iframe>");
            $(".zoomFile").attr("href", "javascript:displayPopup('./assets/smt_attachment/" + img + "')");
            jQuery('.preview').show('slow');
            $("#main-content").addClass('col-md-8').removeClass('col-md-12');
        });
        jQuery('#closeTab').on('click', function(event) {
            jQuery('.preview').hide('slow');
            $("#main-content").removeClass('col-md-8').addClass('col-md-12');
        });

        $(document).ready(function() {
            $("#statlist").change(function() {
                if ($("#statlist option:selected").val() == "reject") {
                    $("#RejectDIV").show();
                    $("#RejectInput").prop('required', true);
                } else {
                    $("#RejectDIV").hide();
                    $("#RejectInput").prop('required', false);
                }
            }).change();
        });

        // SweetAlert2 for Payment
        $('#processPaymentBtn').on('click', function(e) {
            e.preventDefault();
            Swal.fire({
                title: '<?=__('process_payment_for')?> <?= $invnoget ?>',
                html: payment_modal_HTML('<?= round($gtotal, 2) ?>'),
                showCancelButton: true,
                confirmButtonText: '<?=__('submit_payment')?>',
                showLoaderOnConfirm: true,
                allowOutsideClick: false,
                width: '50%',
                preConfirm: () => {
                    const form = document.getElementById('paymentForm');
                    const formData = new FormData(form);
                    if (!form.checkValidity()) {
                        Swal.showValidationMessage(`<?=__('fill_required_fields_error')?>`);
                        return false;
                    }
                    return fetch('open_request.php?id=<?= $_GET['id'] ?>', {
                        method: 'POST',
                        body: formData,
                    })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error(response.statusText);
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
                        location.reload();
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
            const tally_id = $(this).data('tally_id');
            const injazat_id = $(this).data('injazat_id');
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
                    $('#tally_id').val(tally_id);
                    $('#injazat_id').val(injazat_id);
                    $('#remarks').val(remarks);
                    // AJAX call to populate sub_type dropdown
                    $.ajax({
                        url: './includes/ajaxFile/ajaxSmartRequest.php',
                        dataType: 'JSON', type: 'POST',
                        data: { ajaxType: "sub_type" },
                        success: function(res) {
                            if (res.status == 200) {
                                let options = res.data.map(item => `<option value="${item.sub_type}">${item.sub_type}</option>`).join('');
                                $('#sub_type').append(options).val(sub_type);
                            }
                        }
                    });
                },
                preConfirm: () => {
                    const form = $('#submitEditReqForm');
                    return $.ajax({
                        url: './includes/ajaxFile/ajaxSmartRequest.php',
                        type: 'POST', dataType: "JSON",
                        data: form.serialize() + '&' + $.param({ ajaxType: "request_update" }),
                    }).then(response => {
                        return response;
                    }).catch(error => {
                        Swal.showValidationMessage(`<?=__('request_failed')?>: ${error.statusText}`)
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
            var id = $(this).data('id');
            var i_item_name = $(this).data('i_item_name');
            var i_quantity = $(this).data('i_quantity');
            var i_product_price = $(this).data('i_product_price');
            var i_vat_rate = $(this).data('i_vat_rate');
            var i_idiscount = $(this).data('i_idiscount');
            var i_itmvalue = $(this).data('i_itmvalue');
            var i_vat_val = $(this).data('i_vat_val');
            var i_amount = $(this).data('i_amount');
            var i_total_cost = $(this).data('i_total_cost');
            var i_location = $(this).data('i_location');

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
                    $('.vat_rate').val(i_vat_rate);
                    $('.idiscount').val(i_idiscount);
                    $('.itmvalue').val(i_itmvalue);
                    $('.vat_val').val(i_vat_val);
                    $('.amount').val(i_amount);
                    $('.total_cost').val(i_total_cost);
                    
                    $.ajax({
                        url: './includes/ajaxFile/ajaxLocation.php',
                        dataType: 'JSON', type: 'POST',
                        data: { ajaxType: "section_view" },
                        success: function(res) {
                            if (res.status == 200) {
                                let options = res.data.map(item => `<option value="${item.section_name}">${item.section_name}</option>`).join('');
                                $('#location').append(options).val(i_location);
                            }
                        }
                    });

                    function calculateTotals() {
                        var qty = parseFloat($('#quantity').val()) || 0;
                        var price = parseFloat($('#product_price').val()) || 0;
                        var discount = parseFloat($('#idiscount').val()) || 0;
                        var vatOption = $('.vat_option').val();
                        var vatRate = 15;

                        if (vatOption === 'no_vat') {
                            vatRate = 0;
                        }

                        var itemValue = qty * price;
                        $('#itmvalue').val(itemValue.toFixed(2));
                        var vatValue, amount;
                        
                        if (vatOption === 'exclude') {
                            vatValue = itemValue * (vatRate / 100);
                            amount = itemValue + vatValue;
                        } else if (vatOption === 'include') {
                            vatValue = itemValue - (itemValue / (1 + (vatRate / 100)));
                            amount = itemValue;
                        } else { // 'no_vat'
                             vatValue = 0;
                             amount = itemValue;
                        }

                        var total = amount - discount;
                        $('#vat_rate').val(vatRate);
                        $('#vat_val').val(vatValue.toFixed(2));
                        $('#amount').val(amount.toFixed(2));
                        $('#total_cost').val(total.toFixed(2));
                    }

                    $("#swal2-html-container").on('input', 'input, select', calculateTotals);
                    calculateTotals();
                },
                preConfirm: function() {
                    const form = $('#submitEditLineForm');
                    if ($('.item_name').val() == "" || $('#location').val() == '' || $('.quantity').val() == '' || $('.product_price').val() == '') {
                        Swal.showValidationMessage(`<?=__('fill_required_fields_validation')?>`);
                        return false;
                    }
                    return $.ajax({
                        url: './includes/ajaxFile/ajaxSmartRequest.php',
                        type: 'POST', dataType: "JSON",
                        data: form.serialize() + '&' + $.param({ ajaxType: "request_line_update" }),
                    }).then(response => {
                        return response;
                    }).catch(error => {
                        Swal.showValidationMessage(`<?=__('request_failed')?>: ${error.statusText}`);
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
            return `
                <form id="paymentForm" action="open_request.php?id=<?= $_GET['id'] ?>" method="post" enctype="multipart/form-data" class="text-left">
                    <input type="hidden" name="inv_no" value="<?= $invnoget ?>">
                    <input type="hidden" name="process_payment" value="1">
                    <div class="form-group">
                        <label for="paid_amount"><?=__('paid_amount_sar')?></label>
                        <input type="number" step="0.01" class="form-control" id="paid_amount" name="paid_amount" value="${gtotal}" required>
                    </div>
                    <div class="form-group">
                        <label for="payment_invoice"><?=__('payment_invoice_receipt')?></label>
                        <input type="file" class="form-control-file" id="payment_invoice" name="payment_invoice" required>
                    </div>
                    <div class="form-group">
                        <label for="payment_note"><?=__('note_optional')?></label>
                        <textarea class="form-control" id="payment_note" name="payment_note" rows="3"></textarea>
                    </div>
                </form>`;
        }

        function request_details_HTML() {
            return `
                <form id="submitEditReqForm" class="text-left">
                    <div class="form-group">
                        <label for="sub_type"><?=__('subject_type')?></label>
                        <select id="sub_type" name="sub_type" class="form-control"><option value=""><?=__('select')?></option></select>
                    </div>
                    <div class="form-group">
                        <label for="sub_title"><?=__('subject_title')?></label>
                        <input type="text" id="sub_title" name="sub_title" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="tally_id"><?=__('tally_id')?></label>
                        <input type="text" id="tally_id" name="tally_id" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="injazat_id"><?=__('injazat_id')?></label>
                        <input type="text" id="injazat_id" name="injazat_id" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="remarks"><?=__('remarks')?></label>
                        <textarea id="remarks" name="remarks" class="form-control" rows="3"></textarea>
                    </div>
                    <input type="hidden" id="reqid" name="reqid">
                </form>`;
        }
        
        function request_line_HTML() {
            var strView =
                `<form id="submitEditLineForm">
                    <div class="form-row customSweetAlertMLR">
                        <div class="form-group col-md-4"><label><?=__('item_name')?>*</label><input type="text" name="item_name" class="form-control item_name"></div>
                        <div class="form-group col-md-3"><label><?=__('location')?>*</label><select id="location" class="form-control" name="location"><option value=""><?=__('select')?></option></select></div>
                        <div class="form-group col-md-2"><label><?=__('quantity')?>*</label><input type="text" name="quantity" class="form-control quantity" id='quantity'></div>
                        <div class="form-group col-md-3"><label><?=__('unit_cost')?>*</label><input type="text" name="product_price" class="form-control product_price" id='product_price'></div>
                    </div>
                    <div class="form-row customSweetAlertMLR">
                        <div class="form-group col-md-2"><label><?=__('item_value')?></label><input type='text' id='itmvalue' class="form-control itmvalue" name='itmvalue' readonly /></div>
                        <div class="form-group col-md-2"><label><?=__('vat_opt')?></label><select class="form-control vat_option" name="vat_option[]"><option value="include">${__('include')} ${DEFAULT_VAT_RATE}%</option><option value="exclude" selected=selected>${__('exclude')} ${DEFAULT_VAT_RATE}%</option><option value="no_vat">${__('no')} ${DEFAULT_VAT_RATE}%</option></select></div>
                        <div class="form-group col-md-2"><label><?=__('vat_rate_percent')?></label><input type="text" name="vat_rate" class="form-control vat_rate" id="vat_rate" readonly /></div>
                        <div class="form-group col-md-2"><label><?=__('vat_val')?></label><input type='text' class="form-control vat_val" id='vat_val' name='vat_val' readonly /></div>
                        <div class="form-group col-md-2"><label><?=__('amount')?></label><input type='text' class="form-control amount" id='amount' name='amount' readonly /></div>
                        <div class="form-group col-md-2"><label><?=__('discount')?></label><input type="text" name="idiscount" class="form-control idiscount" id='idiscount'></div>
                    </div>
                    <div class="form-row customSweetAlertMLR justify-content-end">
                        <div class="form-group col-md-3"><label><?=__('total')?></label><input type='text' class="form-control total_cost" id='total_cost' name='total_cost' readonly /></div>
                    </div>
                    <input type="hidden" id="itemid" name="itemid">
                </form>`;
            return strView;
        }
    </script>
</body>
</html>
<?php
function getDeptManager($dept_id) {
    global $conDB;
    $query = mysqli_query($conDB, "SELECT e.emp_id, e.name, al.email FROM `employees` e LEFT JOIN `admin_login` al ON e.emp_id = al.emp_id WHERE e.`dept`='$dept_id' AND e.`emptype`='Manager' AND e.`status`=1 LIMIT 1");
    return mysqli_num_rows($query) > 0 ? mysqli_fetch_assoc($query) : null;
}
function getFinancePersonnel() {
    global $conDB;
    $query = mysqli_query($conDB, "SELECT e.emp_id, e.name, al.email FROM `employees` e LEFT JOIN `admin_login` al ON e.emp_id = al.emp_id WHERE e.`dept`=2 AND e.`status`=1 AND e.emp_id IN ('4120', '3061') ORDER BY FIELD(e.emptype, 'Manager', 'Supporter')");
    $personnel = [];
    while ($row = mysqli_fetch_assoc($query)) { $personnel[] = $row; }
    return $personnel;
}
function getGeneralManager() {
    global $conDB;
    $query = mysqli_query($conDB, "SELECT e.emp_id, e.name, al.email FROM `employees` e LEFT JOIN `admin_login` al ON e.emp_id = al.emp_id WHERE e.`emp_id`='3928' AND e.`status`=1 LIMIT 1");
    return mysqli_num_rows($query) > 0 ? mysqli_fetch_assoc($query) : null;
}
// New helper function to get details for any employee
function getEmployeeDetails($emp_id) {
    global $conDB;
    $query = mysqli_query($conDB, "SELECT e.name, al.email FROM `employees` e LEFT JOIN `admin_login` al ON e.emp_id = al.emp_id WHERE e.`emp_id`='$emp_id' LIMIT 1");
    return mysqli_num_rows($query) > 0 ? mysqli_fetch_assoc($query) : ['name' => 'N/A', 'email' => ''];
}
?>
