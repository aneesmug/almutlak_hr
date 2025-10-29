<?php
// File: includes/mark_notification_read.php
// Handles AJAX request to mark a single notification as read.

@error_reporting(0);
@ini_set('display_errors', 0);
ob_start();

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/session_check.php'; // Includes custom_functions.php

header('Content-Type: application/json; charset=utf-8');

// Ensure user is logged in
if (!isset($empid) || empty($empid)) {
    ob_clean();
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'User not authenticated.']);
    exit;
}

// Check if notification ID is provided via POST
if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
    ob_clean();
    http_response_code(400); // Bad Request
    echo json_encode(['status' => 'error', 'message' => 'Invalid or missing notification ID.']);
    exit;
}

$notification_id = (int)$_POST['id'];
$user_id = (int)$empid; // The logged-in user

try {
    // Call the existing function to mark the notification as read
    // IMPORTANT: Modify mark_notifications_as_read to handle a single ID or update DB directly here
    // For now, let's assume direct update for simplicity
    
    $sql = "UPDATE `user_notifications` 
            SET `is_read` = 1 
            WHERE `id` = $notification_id AND `emp_id` = $user_id"; // Ensure user owns notification

    if (mysqli_query($conDB, $sql)) {
        if (mysqli_affected_rows($conDB) > 0) {
            ob_clean();
            echo json_encode(['status' => 'success', 'message' => 'Notification marked as read.']);
        } else {
            // ID might not exist or belong to this user
            ob_clean();
            http_response_code(404); // Not Found (or Forbidden)
            echo json_encode(['status' => 'error', 'message' => 'Notification not found or access denied.']);
        }
    } else {
        // Database error during update
        error_log("ERROR: mark_notification_read.php - Failed to update notification ID $notification_id. Error: " . mysqli_error($conDB));
        ob_clean();
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Database error marking notification as read.']);
    }

} catch (Exception $e) {
    error_log("ERROR: mark_notification_read.php - Exception: " . $e->getMessage());
    ob_clean();
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Server error.']);
}

exit;