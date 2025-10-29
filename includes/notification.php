<?php
// --- NEW: Force suppression of errors in output (Use cautiously in production) ---
// @error_reporting(0); // Comment out during debugging
// @ini_set('display_errors', 0); // Comment out during debugging
// --- END NEW ---

// --- Start output buffering ---
ob_start();

// --- Set header EARLY ---
// Ensure this is before ANY output, including potential whitespace or errors
header('Content-Type: application/json; charset=utf-8'); // Specify charset

// Include necessary files
require_once __DIR__ . '/db.php'; // Assuming db.php is in the same directory (includes/)
require_once __DIR__ . '/session_check.php'; // Assuming session_check.php is in the same directory (includes/)
// helper_functions.php should be included via session_check.php or db.php, or include it explicitly:
// require_once __DIR__ . '/helper_functions.php';


// Ensure user is logged in
if (!isset($empid) || empty($empid)) {
    // Log attempt to access without auth
    error_log("notification.php: Endpoint accessed without authentication.");
    // --- Clean buffer before error response ---
    ob_clean(); // Clear any potential output before sending JSON
    http_response_code(401); // Unauthorized
    echo json_encode(['status' => 'error', 'message' => 'User not authenticated.']);
    exit;
}

// Log the session emp_id being used
// error_log("DEBUG: notification.php - Running for session emp_id: " . $empid); // Keep commented unless debugging


$response_data = []; // Prepare response data array

try {
    // Ensure helper function exists before calling
    if (!function_exists('get_unread_notifications')) {
        throw new Exception("Helper function 'get_unread_notifications' not found.");
    }

    // 1. Fetch unread notifications for the logged-in user
    $notifications = get_unread_notifications($conDB, $empid); // Function from helper_functions.php

    // --- Log the result of the fetch (optional) ---
    // error_log("DEBUG: notification.php - get_unread_notifications returned count: " . count($notifications));
    // error_log("DEBUG: notification.php - Raw notifications data: " . json_encode($notifications));
    // --- END LOGGING ---

    $response_data = ['status' => 'success', 'notifications' => $notifications];

    // --- Clean buffer just before final output ---
    ob_clean();

    // 2. Send the notifications back to the browser
    echo json_encode($response_data);

    // Ensure output is sent, especially if using FastCGI
    if (function_exists('fastcgi_finish_request')) {
        fastcgi_finish_request();
    }

} catch (Exception $e) {
    // Log any caught exceptions
    error_log("ERROR: notification.php - Exception caught: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine()); // Log file and line
    // --- Clean buffer before error response ---
    ob_clean();
    http_response_code(500); // Internal Server Error
    echo json_encode(['status' => 'error', 'message' => 'Server error processing notifications. Please check server logs.']); // More generic error message for user
}

exit; // Explicitly exit


?>