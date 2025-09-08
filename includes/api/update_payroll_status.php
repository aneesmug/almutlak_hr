<?php
/**
 * =======================================================================================
 * API Endpoint: update_payroll_status.php
 * =======================================================================================
 *
 * MODIFICATION SUMMARY:
 * 1. TABLE NAME CHANGE: The SQL UPDATE statement has been modified to update the `payrolls` table instead of `generated_payrolls` to align with the new database schema.
 *
 * Description:
 * This script handles updating the status of one or more payroll records in the database.
 * It's designed to be called via a POST request from the frontend, typically after a
 * user selects records in the payroll report and clicks "Mark as Paid".
 *
 * Expected JSON Input:
 * {
 * "payroll_ids": [101, 102, 105], // An array of integer IDs for the generated payrolls
 * "status": "paid"                 // The new status to set. Currently, only 'paid' is accepted.
 * }
 *
 * Success JSON Output:
 * {
 * "status": "success",
 * "message": "Successfully updated 3 payroll record(s) to 'paid'.",
 * "updated_count": 3
 * }
 *
 * Error JSON Output:
 * {
 * "status": "error",
 * "message": "Descriptive error message here."
 * }
 *
 */

// Set the content type to JSON for all responses
header('Content-Type: application/json');

// Include the database connection file. Adjust the path as needed for your project structure.
// It's recommended to place 'db.php' outside of the public web root for security.
require_once(__DIR__ . "/../../includes/db.php"); 

// Get the raw POST data from the request body
$input = json_decode(file_get_contents('php://input'), true);

// --- 1. Input Validation ---

// Safely get the payroll IDs and the new status from the decoded JSON input.
// Use the null coalescing operator (??) to provide default empty values if keys don't exist.
$payrollIds = $input['payroll_ids'] ?? [];
$status = $input['status'] ?? '';

// Basic validation to ensure we have the necessary, correctly-formatted data.
if (empty($payrollIds) || !is_array($payrollIds) || empty($status)) {
    // If validation fails, send a 400 Bad Request HTTP status code and a JSON error message.
    http_response_code(400); 
    echo json_encode(['status' => 'error', 'message' => 'Invalid input. Please provide an array of payroll IDs and a status.']);
    exit(); // Stop script execution
}

// You can add more specific status checks if needed. For now, we only allow 'paid'.
if ($status !== 'paid') {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid status provided. Only "paid" is an accepted value.']);
    exit();
}

// --- 2. Database Operation ---

try {
    // Establish a database connection using the function from your db.php file
    $pdo = getDbConnection();

    // Begin a transaction. This ensures that all updates succeed or none do, maintaining data integrity.
    $pdo->beginTransaction();

    // Create the correct number of '?' placeholders for the SQL IN clause.
    // This is crucial for securely binding multiple IDs in a prepared statement.
    $placeholders = implode(',', array_fill(0, count($payrollIds), '?'));

    // Prepare the SQL statement to update the 'status' column in the 'payrolls' table.
    // Using prepared statements prevents SQL injection attacks.
    $stmt = $pdo->prepare("
        UPDATE payrolls
        SET status = ?
        WHERE id IN ($placeholders) AND status = 'generated' -- Only update records that are not already paid
    ");

    // Combine the status and the array of payroll IDs into a single parameters array for execution.
    // The status parameter corresponds to the first '?' in the SET clause.
    // The payrollIds are then spread to match the placeholders in the IN clause.
    $params = array_merge([$status], $payrollIds);

    // Execute the prepared statement with the combined parameters
    $stmt->execute($params);

    // Get the number of rows that were actually affected by the UPDATE query.
    $updatedCount = $stmt->rowCount();

    // If the transaction is successful, commit the changes to the database, making them permanent.
    $pdo->commit();

    // --- 3. Success Response ---

    // Send a success response back to the client with a descriptive message.
    echo json_encode([
        'status' => 'success',
        'message' => "Successfully updated $updatedCount payroll record(s) to '$status'.",
        'updated_count' => $updatedCount
    ]);

} catch (PDOException $e) {
    // --- 4. Error Handling ---

    // If any database error occurs during the try block, this catch block will be executed.
    // First, check if a transaction was started and, if so, roll it back to undo any partial changes.
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    // Log the detailed error message to the server's error log for debugging purposes.
    // This should not be shown to the end-user.
    error_log('Payroll status update (PDO) error: ' . $e->getMessage());

    // Send a generic 500 Internal Server Error HTTP status code and a user-friendly error message.
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'A database error occurred. Please try again later.']);
}
?>
