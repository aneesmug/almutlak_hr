<?php
require 'includes/db.php';
require 'includes/helper_functions.php';

// Get a sample request
$request_query = mysqli_query($conDB, "SELECT gr.*, d.dep_nme FROM general_requests gr LEFT JOIN department d ON d.id = gr.user_dept WHERE inv_no = 'SMT54302512160015' LIMIT 1");

if ($request_query && mysqli_num_rows($request_query) > 0) {
    $request = mysqli_fetch_assoc($request_query);
    
    echo "REQUEST DATA:<br>";
    echo "request_title: " . ($request['request_title'] ?? 'NOT SET') . "<br>";
    echo "emp_name: " . ($request['emp_name'] ?? 'NOT SET') . "<br>";
    echo "priority: " . ($request['priority'] ?? 'NOT SET') . "<br>";
    echo "<br>";
    
    $template_data = [
        'APPROVER_NAME' => 'Test Approver',
        'REQUEST_ID' => $request['inv_no'],
        'REQUEST_TITLE' => $request['request_title'] ?? 'N/A',
        'REQUESTER_NAME' => $request['emp_name'] ?? 'N/A',
        'DEPARTMENT' => $request['dep_nme'] ?? 'N/A',
        'PRIORITY' => ucfirst($request['priority'] ?? 'N/A'),
        'CATEGORY' => $request['request_category'] ?? 'N/A',
        'DESCRIPTION' => $request['description'] ?? 'No description',
        'EMAIL_MESSAGE' => 'Test message',
        'REQUEST_URL' => 'http://test.local'
    ];
    
    echo "TEMPLATE DATA:<br>";
    echo "<pre>";
    print_r($template_data);
    echo "</pre>";
    
    // Load template
    $template_path = __DIR__ . '/includes/PHPMailerMaster/general_request_email_template.html';
    if (file_exists($template_path)) {
        $html = file_get_contents($template_path);
        
        echo "BEFORE REPLACEMENT - First 500 chars:<br>";
        echo "<pre>";
        echo htmlspecialchars(substr($html, 0, 500));
        echo "</pre><br>";
        
        // Replace placeholders
        foreach ($template_data as $key => $value) {
            $html = str_replace('{{' . $key . '}}', htmlspecialchars($value, ENT_QUOTES, 'UTF-8'), $html);
        }
        
        // Save the output to a file for inspection
        file_put_contents('/tmp/email_output.html', $html);
        
        // Check if placeholders were replaced
        if (strpos($html, '{{REQUEST_TITLE}}') === false) {
            echo "Placeholder {{REQUEST_TITLE}} was replaced<br>";
        } else {
            echo "ERROR: Placeholder {{REQUEST_TITLE}} was NOT replaced<br>";
        }
        
        // Look for the actual value
        if (strpos($html, 'We need mouse') !== false) {
            echo "Value 'We need mouse' IS in the output<br>";
        } else {
            echo "ERROR: Value 'We need mouse' NOT in output<br>";
        }
        
        // Look for escaped version
        if (strpos($html, 'We need mouse') === false && strpos($html, htmlspecialchars('We need mouse')) === false) {
            echo "ERROR: Neither the original nor escaped version found in output<br>";
        }

        
    } else {
        echo "Template file not found at " . $template_path;
    }
} else {
    echo "Request not found";
}
?>
