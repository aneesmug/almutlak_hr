<?php
// --- NEW: Force suppression of errors in output ---
@error_reporting(0);
@ini_set('display_errors', 0);
// --- END NEW ---

// --- Start output buffering ---
ob_start(); 

// Include necessary files
require_once __DIR__ . '/db.php'; // Assuming db.php is in the same directory (includes/)
require_once __DIR__ . '/session_check.php'; // Assuming session_check.php is in the same directory (includes/)

// --- Set header AFTER includes ---
header('Content-Type: application/json; charset=utf-8'); // Specify charset


// Ensure user is logged in
if (!isset($empid) || empty($empid)) {
    // Log attempt to access without auth
    error_log("Notification endpoint accessed without authentication.");
    // --- Clean buffer before error response ---
    ob_clean(); 
    http_response_code(401); // Unauthorized
    echo json_encode(['status' => 'error', 'message' => 'User not authenticated.']);
    exit;
}

// Log the start of the request for this user
// error_log("DEBUG: notification.php - Fetching for emp_id: " . $empid);

$response_data = []; // Prepare response data array

try {
    // 1. Fetch unread notifications for the logged-in user
    $notifications = get_unread_notifications($conDB, $empid);
    // error_log("DEBUG: notification.php - Found " . count($notifications) . " unread notifications."); // Log count
    $response_data = ['status' => 'success', 'notifications' => $notifications];


    // 2. Collect IDs to mark as read (do this *before* sending response if not using fastcgi_finish_request)
    $notification_ids = [];
    if (!empty($notifications)) {
        foreach ($notifications as $notification) {
            $notification_ids[] = $notification['id'];
        }
        // error_log("DEBUG: notification.php - IDs to mark as read: " . implode(',', $notification_ids)); // Log IDs
    }

    // --- Clean buffer just before final output ---
    ob_clean();

    // 3. Send the notifications back to the browser
    echo json_encode($response_data);

    // Ensure output is sent before potentially long-running DB operation
    if (function_exists('fastcgi_finish_request')) {
        fastcgi_finish_request(); // If available, allows the script to continue processing after sending response
    }

    // 4. Mark them as read *after* sending them
    if (!empty($notification_ids)) {
        // error_log("DEBUG: notification.php - Calling mark_notifications_as_read..."); // Log before call
        $mark_result = mark_notifications_as_read($conDB, $notification_ids);

        if ($mark_result) {
            // error_log("DEBUG: notification.php - Successfully marked notifications as read.");
        } else {
            error_log("ERROR: notification.php - Failed to mark notifications as read. DB Error: " . mysqli_error($conDB));
        }
    } else {
         // error_log("DEBUG: notification.php - No notifications found to mark as read."); // Log if none found
    }

} catch (Exception $e) {
    // Log any caught exceptions
    error_log("ERROR: notification.php - Exception caught: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine()); // Log file and line
    // --- Clean buffer before error response ---
    ob_clean(); 
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Server error processing notifications. Check logs.']); // More generic error message
}

exit; // Explicitly exit
