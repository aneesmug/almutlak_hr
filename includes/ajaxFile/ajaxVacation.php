<?php

/****************************************************************
 * MODIFICATION SUMMARY:
 * 1. REVISED DEDUCTION LOGIC: The logic for `is_deductible` has been updated. It is no longer about deducting from Annual Vacation.
 * 2. NEW MEANING FOR `is_deductible`: This flag now means "deduct from monthly salary as absent".
 * 3. UPDATED DEDUCTIBLE TYPES: The `$deductible_leave_types` array now only contains 'Casual Leave' and 'Other Leave'.
 * 4. ACCURATE FLAGGING: When an employee applies for 'Casual Leave' or 'Other Leave', the `is_deductible` flag will be set to `1`. All other leave types ('Sick Leave', 'Maternity Leave', etc.) will have this flag set to `0`. This ensures the payroll process correctly identifies which leaves to treat as unpaid absence.
 ****************************************************************/
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/vacation_processor.php';
require_once __DIR__ . '/../../includes/custom_functions.php';

// Set a default timezone to avoid warnings with DateTime
date_default_timezone_set('Asia/Riyadh');

$ajaxType = $_POST['ajaxType'] ?? null;

if ($ajaxType == 'applyVacation') {
    // Instantiate the processor
    $vacationProcessor = new VacationProcessor($conDB);
    $request_data = [
        'emp_id'                => $_POST['empid'],
        'vac_type'              => $_POST['vac_type'],
        'start_date'            => $_POST['start_date'],
        'end_date'              => $_POST['end_date'],
        'replacement_person'    => $_POST['replacement_per'],
        'fly_type'              => $_POST['fly_type'],
        'remarks'               => $_POST['remarks'],
        'note'                  => '',
    ];
    $result = $vacationProcessor->submitVacationRequest($_POST['empid'], $request_data);
    if ($result['success']) {
        $data = [
            'title'   => "Applied!",
            'message' => "Vacation applied successfully!",
            'type'    => 'success',
        ];
        echo json_encode($data);
    } else {
        $data = [
            'title'   => "Error!",
            'message' => $result['message'],
            'type'    => 'error',
        ];
        echo json_encode($data);
    }
} elseif ($ajaxType == 'approveVacation') {
    // Instantiate the processor
    $vacationProcessor = new VacationProcessor($conDB);
    $result = $vacationProcessor->approveVacationRequest($_POST['vacation_id'], $_POST['approver_role'], $_POST['ticket_pay'], $_POST['permit_fee']);
    if ($result['success']) {
        $data = [
            'title'   => "Approved!",
            'message' => "Vacation approved successfully!",
            'type'    => 'success',
        ];
        echo json_encode($data);
    } else {
        $data = [
            'title'   => "Error!",
            'message' => $result['message'],
            'type'    => 'error',
        ];
        echo json_encode($data);
    }
} elseif ($ajaxType == 'rejectVacation') {
    // Instantiate the processor
    $vacationProcessor = new VacationProcessor($conDB);
    $result = $vacationProcessor->rejectVacationRequest($_POST['vacation_id'], $_POST['rejection_note'], $_POST['approver_role']);
    if ($result['success']) {
        $data = [
            'title'   => "Rejected!",
            'message' => "The vacation request has been rejected.",
            'type'    => 'success',
        ];
        echo json_encode($data);
    } else {
        $data = [
            'title'   => "Error!",
            'message' => $result['message'],
            'type'    => 'error',
        ];
        echo json_encode($data);
    }
} elseif ($ajaxType == 'returnVacation') {
    // Instantiate the processor
    $vacationProcessor = new VacationProcessor($conDB);
    $result = $vacationProcessor->recordVacationReturn($_POST['vacation_id'], $_POST['returnDate']);
    if ($result['success']) {
        $data = [
            'title'   => "Success!",
            'message' => "Employee return has been recorded successfully. Vacation balance is updated.",
            'type'    => 'success',
        ];
        echo json_encode($data);
    } else {
        $data = [
            'title'   => "Error!",
            'message' => $result['message'],
            'type'    => 'error',
        ];
        echo json_encode($data);
    }
}
/*
================================================================
== UPDATED CODE BLOCK TO HANDLE GENERAL LEAVE APPLICATIONS
================================================================
*/ elseif ($ajaxType == 'applyLeave') {
    // --- DATA RETRIEVAL ---
    $emp_id = $_POST['empid'];
    $leave_type = $_POST['leave_type'];
    $start_date = $_POST['start_date'] ?? null;
    $end_date = ($leave_type === 'Compensatory Leave') ? $start_date : ($_POST['end_date'] ?? null);
    $reason = trim($_POST['reason'] ?? '');
    $trip_destination = trim($_POST['trip_destination'] ?? '');

    // --- SERVER-SIDE VALIDATION ---
    if ($leave_type === 'Business Trip' && empty($trip_destination)) {
        $response = ['title'   => "Validation Error", 'message' => "The Destination field is required for a Business Trip.", 'type'    => 'error'];
        header('Content-Type: application/json');
        echo json_encode($response);
        exit();
    }
    $reason_required_types = ['Sick Leave', 'Casual Leave', 'Maternity Leave', 'Business Trip', 'Compensatory Leave', 'Other Leave'];
    if (in_array($leave_type, $reason_required_types) && empty($reason)) {
        $response = ['title'   => "Validation Error", 'message' => "The Reason / Notes field is required for this leave type.", 'type'    => 'error'];
        header('Content-Type: application/json');
        echo json_encode($response);
        exit();
    }

    // --- UPDATED: DETERMINE IF LEAVE IS DEDUCTIBLE FROM MONTHLY SALARY (AS ABSENT) ---
    $deductible_leave_types = ['Casual Leave', 'Other Leave'];
    $is_deductible = in_array($leave_type, $deductible_leave_types) ? 1 : 0;

    // --- CALCULATE TOTAL DAYS ---
    $vacdays = 0;
    if ($start_date && $end_date) {
        try {
            $start = new DateTime($start_date);
            $end = new DateTime($end_date);
            $end->modify('+1 day');
            $interval = new DateInterval('P1D');
            $date_range = new DatePeriod($start, $interval, $end);
            foreach ($date_range as $date) {
                $vacdays++;
            }
        } catch (Exception $e) {
            $vacdays = 0;
        }
    }

    // --- HANDLE FILE UPLOAD ---
    $attachment_path = null;
    if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] == UPLOAD_ERR_OK) {
        $target_dir = __DIR__ . "/../../uploads/leave_attachments/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0755, true);
        }
        $file_basename = preg_replace("/[^a-zA-Z0-9\._-]/", "", basename($_FILES["attachment"]["name"]));
        $file_name = time() . '_' . $file_basename;
        $target_file = $target_dir . $file_name;
        if (move_uploaded_file($_FILES["attachment"]["tmp_name"], $target_file)) {
            $attachment_path = "uploads/leave_attachments/" . $file_name;
        }
    }

    // --- PREPARE REMARKS/NOTES ---
    $remarks = $reason;
    if ($leave_type === 'Business Trip' && !empty($trip_destination)) {
        $remarks = "Destination: " . $trip_destination . "\nPurpose: " . $reason;
    }

    // --- DATABASE INSERTION ---
    $sql = "INSERT INTO emp_vacation 
                (emp_id, start_date, return_date, vacdays, vac_type, remarks, attachment_path, is_deductible, approval_status, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'apply', NOW())";

    $stmt = $conDB->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("ssissssi", $emp_id, $start_date, $end_date, $vacdays, $leave_type, $remarks, $attachment_path, $is_deductible);

        if ($stmt->execute()) {
            $response = ['title'   => "Success!", 'message' => "Your leave request for '" . htmlspecialchars($leave_type) . "' has been submitted successfully.", 'type'    => 'success'];
        } else {
            $response = ['title'   => "Database Error", 'message' => "Failed to execute statement: " . $stmt->error, 'type'    => 'error'];
        }
        $stmt->close();
    } else {
        $response = ['title'   => "Database Error", 'message' => "Failed to prepare statement: " . $conDB->error, 'type'    => 'error'];
    }

    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
} elseif ($ajaxType == 'updateVacationPayments') { // --- NEW CASE TO HANDLE PAYMENT UPDATES ---
    if (!isset($_POST['vacation_id'], $_POST['ticket_pay'], $_POST['permit_fee'])) {
        send_json_response("Error", "Missing required payment information.", "error");
        exit;
    }

    $vacationId = filter_var($_POST['vacation_id'], FILTER_SANITIZE_NUMBER_INT);
    $ticketPay = filter_var($_POST['ticket_pay'], FILTER_VALIDATE_FLOAT, FILTER_NULL_ON_FAILURE) ?? 0.00;
    $permitFee = filter_var($_POST['permit_fee'], FILTER_VALIDATE_FLOAT, FILTER_NULL_ON_FAILURE) ?? 0.00;

    if (!$vacationId) {
        send_json_response("Error", "Invalid vacation ID.", "error");
        exit;
    }

    try {
        $stmt = $pdo->prepare("UPDATE `emp_vacation` SET `ticket_pay` = :ticket_pay, `permit_fee` = :permit_fee WHERE `id` = :vacation_id");

        $stmt->execute([
            ':ticket_pay' => $ticketPay,
            ':permit_fee' => $permitFee,
            ':vacation_id' => $vacationId
        ]);

        if ($stmt->rowCount() > 0) {
            send_json_response("Success!", "Payment details have been updated successfully.", "success");
        } else {
            send_json_response("Info", "No changes were made to the payment details.", "info");
        }
    } catch (PDOException $e) {
        // In a real app, you would log this error instead of echoing it.
        send_json_response("Database Error", "Failed to update payment details: " . $e->getMessage(), "error");
    }
} else {
    header('Content-Type: application/json');
    echo json_encode(['title'   => 'Invalid Request', 'message' => 'The specified action is not valid.', 'type'    => 'error']);
}
