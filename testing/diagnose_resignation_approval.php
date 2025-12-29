<?php
require_once __DIR__ . '/includes/db.php';

echo "<h2>Resignation Approval Chain Diagnostic</h2>";

// 1. Check the configured approval chain
echo "<h3>1. Configured Approval Chain for resignation_request:</h3>";
$query = mysqli_query($conDB, "SELECT setting_value FROM app_settings WHERE setting_name = 'approval_chain_resignation_request'");

$chain = [];
if ($query && mysqli_num_rows($query) > 0) {
    $row = mysqli_fetch_assoc($query);
    $chain = json_decode($row['setting_value'], true);
    echo "<pre style='background: #f0f0f0; padding: 10px; border-radius: 5px;'>";
    echo json_encode($chain, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    echo "</pre>";
} else {
    echo "<p style='color: red;'><strong>No approval_chain_resignation_request found!</strong></p>";
    exit;
}
if ($query) mysqli_free_result($query);

// 2. Check if approval_request_types has resignation_request
echo "<h3>2. Checking approval_request_types table:</h3>";
$query = mysqli_query($conDB, "SELECT id, type_name, description FROM approval_request_types WHERE type_name = 'resignation_request'");

if ($query && mysqli_num_rows($query) > 0) {
    $row = mysqli_fetch_assoc($query);
    echo "<p><strong>✓ Found:</strong> ID=" . $row['id'] . ", Type=" . $row['type_name'] . "</p>";
    $requestTypeId = $row['id'];
} else {
    echo "<p style='color: red;'><strong>✗ resignation_request NOT found in approval_request_types!</strong></p>";
    echo "<p>This needs to be created. Run this SQL:</p>";
    echo "<pre>INSERT INTO approval_request_types (type_name, description, is_default, is_active, created_at) 
VALUES ('resignation_request', 'Employee resignation approval', 0, 1, NOW());</pre>";
    exit;
}
if ($query) mysqli_free_result($query);

// 3. Test approver resolution for each configured level
echo "<h3>3. Testing Approver Resolution:</h3>";

if (empty($chain)) {
    echo "<p style='color: orange;'><strong>Approval chain is EMPTY!</strong> No levels configured.</p>";
} else {
    foreach ($chain as $step) {
        $level = $step['level'];
        $userType = $step['user_type'];
        echo "<p><strong>Level $level:</strong> Looking for approver with user_type = '<strong>$userType</strong>'</p>";
        
        // Check if any users exist with this user_type
        $userQuery = mysqli_query($conDB, "SELECT emp_id, name, user_type FROM admin_login WHERE user_type = '$userType' AND status = 1 LIMIT 5");
        
        if ($userQuery && mysqli_num_rows($userQuery) > 0) {
            echo "<ul>";
            while ($user = mysqli_fetch_assoc($userQuery)) {
                echo "<li>✓ Found: " . $user['name'] . " (emp_id: " . $user['emp_id'] . ")</li>";
            }
            echo "</ul>";
        } else {
            echo "<p style='color: red;'><strong>✗ NO active users found with user_type = '$userType'!</strong></p>";
            echo "<p style='color: orange;'>Available user types in admin_login:</p>";
            
            $typeQuery = mysqli_query($conDB, "SELECT DISTINCT user_type FROM admin_login WHERE status = 1 ORDER BY user_type");
            if ($typeQuery) {
                echo "<ul>";
                while ($typeRow = mysqli_fetch_assoc($typeQuery)) {
                    echo "<li>" . $typeRow['user_type'] . "</li>";
                }
                echo "</ul>";
                mysqli_free_result($typeQuery);
            }
        }
        if ($userQuery) mysqli_free_result($userQuery);
    }
}

// 4. Check if there are any recent resignations and their approval status
echo "<h3>4. Recent Resignation Requests:</h3>";
$query = mysqli_query($conDB, "
    SELECT r.id, r.request_inv_no, r.emp_id, e.name, r.status, r.created_at 
    FROM emp_resignations r
    JOIN employees e ON e.emp_id = r.emp_id
    ORDER BY r.created_at DESC
    LIMIT 10
");

if ($query && mysqli_num_rows($query) > 0) {
    echo "<table border='1' cellpadding='8' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #f0f0f0;'><th>Request ID</th><th>Employee</th><th>Status</th><th>Created</th><th>Approvers</th></tr>";
    
    while ($res = mysqli_fetch_assoc($query)) {
        echo "<tr>";
        echo "<td>" . $res['request_inv_no'] . "</td>";
        echo "<td>" . $res['name'] . "</td>";
        echo "<td>" . $res['status'] . "</td>";
        echo "<td>" . $res['created_at'] . "</td>";
        
        // Check approvers for this request
        $approverQuery = mysqli_query($conDB, "
            SELECT ra.approval_level, ra.approver_id, ra.status, al.name 
            FROM request_approvers ra
            LEFT JOIN admin_login al ON al.emp_id = ra.approver_id
            WHERE ra.request_inv_no = '" . $res['request_inv_no'] . "'
            ORDER BY ra.approval_level
        ");
        
        $approverCount = mysqli_num_rows($approverQuery);
        if ($approverCount > 0) {
            $approvers = [];
            while ($app = mysqli_fetch_assoc($approverQuery)) {
                $approvers[] = "L" . $app['approval_level'] . ": " . ($app['name'] ?? 'Unknown') . " (" . $app['status'] . ")";
            }
            echo "<td>" . implode("<br>", $approvers) . "</td>";
        } else {
            echo "<td style='color: red;'><strong>NO APPROVERS!</strong></td>";
        }
        mysqli_free_result($approverQuery);
        
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No resignation requests found.</p>";
}
if ($query) mysqli_free_result($query);

echo "<hr>";
echo "<p><strong>Summary:</strong></p>";
echo "<ul>";
echo "<li>If approvers can't be found: Update the approval chain to use user_types that exist in your system</li>";
echo "<li>If recent resignations have NO APPROVERS: The approval chain wasn't created when the resignation was submitted</li>";
echo "<li>Check PHP error logs for more details on why the approval chain creation failed</li>";
echo "</ul>";
?>
