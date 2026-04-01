<?php
/**
 * =================================================================
 * SEND TRAVEL COMPANY EMAIL NOTIFICATION
 * =================================================================
 * This script sends employee travel information to the traveling company
 * when an annual fly vacation is approved.
 * 
 * Usage:
 * - Called from leaveHandler.php after final approval of annual fly vacation
 * - Can also be called manually by passing vacation_id parameter
 * 
 * Parameters:
 * - vacation_id: The ID of the approved vacation request
 * =================================================================
 */

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helper_functions.php';

// Check if vacation_id is provided
if (!isset($_POST['vacation_id']) && !isset($_GET['vacation_id'])) {
    echo json_encode(['success' => false, 'message' => 'Vacation ID is required']);
    exit;
}

$vacation_id = isset($_POST['vacation_id']) ? (int)$_POST['vacation_id'] : (int)$_GET['vacation_id'];

// Fetch vacation and employee details
$sql = "SELECT 
            v.*, 
            e.name as employee_name,
            e.passport_number,
            e.passport_exp,
            c.name as country_name
        FROM emp_vacation v
        JOIN employees e ON v.emp_id = e.emp_id
        LEFT JOIN countries c ON e.country = c.id
        WHERE v.id = ?";

$stmt = $conDB->prepare($sql);
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $conDB->error]);
    exit;
}

$stmt->bind_param('i', $vacation_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Vacation request not found']);
    exit;
}

$vacation = $result->fetch_assoc();
$stmt->close();

// Check if this is an annual fly vacation
if ($vacation['vac_type'] !== 'Fly' || $vacation['fly_type'] !== 'annual') {
    echo json_encode(['success' => false, 'message' => 'Email only sent for annual fly vacations']);
    exit;
}

// Check if vacation is approved
if ($vacation['current_status'] !== 'approved') {
    echo json_encode(['success' => false, 'message' => 'Vacation must be approved before sending travel email']);
    exit;
}

// Check if flight dates are available
if (empty($vacation['departure_date']) || empty($vacation['arrival_date'])) {
    echo json_encode(['success' => false, 'message' => 'Flight dates are required']);
    exit;
}

// Send email to traveling company
$email_sent = send_travel_company_email(
    $conDB,
    $vacation['employee_name'],
    $vacation['passport_number'],
    $vacation['passport_exp'],
    $vacation['country_name'],
    $vacation['departure_date'],
    $vacation['arrival_date'],
    $vacation['request_inv_no']
);

if ($email_sent) {
    // Log the email sending in the database (optional)
    $log_sql = "INSERT INTO email_logs (request_inv_no, email_type, sent_to, sent_at, status) 
                VALUES (?, 'travel_company', ?, NOW(), 'sent')";
    $log_stmt = $conDB->prepare($log_sql);
    if ($log_stmt) {
        $traveling_company_email = get_setting($conDB, 'traveling_company_email');
        $log_stmt->bind_param('ss', $vacation['request_inv_no'], $traveling_company_email);
        $log_stmt->execute();
        $log_stmt->close();
    }
    
    echo json_encode([
        'success' => true, 
        'message' => 'Travel information email sent successfully to traveling company'
    ]);
} else {
    echo json_encode([
        'success' => false, 
        'message' => 'Failed to send email. Please check SMTP settings and traveling company email configuration.'
    ]);
}

$conDB->close();
?>
