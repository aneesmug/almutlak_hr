<?php
/**
 * =======================================================================================
 * API Endpoint: update_payroll_status.php
 * =======================================================================================
 *
 * MODIFICATION SUMMARY:
 * 1. TABLE NAME CHANGE: The SQL UPDATE statement has been modified to update the `payrolls` table instead of `generated_payrolls`.
 *
 * Description:
 * This script handles updating the status of one or more payroll records in the database.
 * It's designed to be called via a POST request from the frontend, typically after a
 * user selects records in the payroll report and clicks "Mark as Paid".
 *
 */

// Set the content type to JSON for all responses
header('Content-Type: application/json');

// Include the database connection file. Adjust the path as needed for your project structure.
require_once(__DIR__ . "/../../includes/db.php"); 

// Get the raw POST data from the request body
$input = json_decode(file_get_contents('php://input'), true);

// --- 1. Input Validation ---

$payrollIds = $input['payroll_ids'] ?? [];
$status = $input['status'] ?? '';

// Basic validation to ensure we have the necessary, correctly-formatted data.
if (empty($payrollIds) || !is_array($payrollIds) || empty($status)) {
    http_response_code(400); 
    echo json_encode(['status' => 'error', 'message' => 'Invalid input. Please provide an array of payroll IDs and a status.']);
    exit(); // Stop script execution
}

if ($status !== 'paid') {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid status provided. Only "paid" is an accepted value.']);
    exit();
}

// --- 2. Database Operation ---

try {
    // Establish a database connection using the function from your db.php file
    $pdo = getDbConnection();

    // Begin a transaction.
    $pdo->beginTransaction();

    // Create the correct number of '?' placeholders for the SQL IN clause.
    $placeholders = implode(',', array_fill(0, count($payrollIds), '?'));

    // Prepare the SQL statement to update the 'status' column in the 'payrolls' table.
    $stmt = $pdo->prepare("
        UPDATE payrolls
        SET status = ?
        WHERE id IN ($placeholders) AND (status = 'generated' OR status = 'updated')
    ");

    // Combine the status and the array of payroll IDs into a single parameters array for execution.
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

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    // Log the detailed error message to the server's error log for debugging purposes.
    error_log('Payroll status update (PDO) error: ' . $e->getMessage());

    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'A database error occurred. Please try again later.']);
}
?>