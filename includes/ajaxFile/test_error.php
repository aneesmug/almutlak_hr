<?php
// This is a special test file to prove your catch block works.
header('Content-Type: application/json');
include("./../../includes/custom_functions.php"); // --- Helper Function ---

try {
        ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);
        error_reporting(E_ALL);

    // We will force an error to happen right here.
    // This guarantees the 'catch' block will run.
    throw new Exception("This is a test message from the catch block!");

    // This line will never be reached because of the error above.
    send_json_response("Success", "You should not see this message.", "success", 200);

} catch (Exception $e) {
    // The error is caught, and this code will now run.
    // It will send the error message to your frontend.
    send_json_response(
        "Test Successful!", 
        "The catch block is working. The error was: " . $e->getMessage(), 
        "error",
        500 // Send a 500 error code to trigger the .fail() in AJAX
    );
}
?>
