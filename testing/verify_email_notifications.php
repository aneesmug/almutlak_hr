<?php
/**
 * Quick Email Notification Verification
 * This script verifies that approval emails are being sent when loans are approved
 * 
 * Usage: Open in browser after approving a loan
 */

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/session_check.php';

// Only show to admins
if (!($is_system_admin ?? false)) {
    die("Access denied. This page is only for administrators.");
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Email Notification Verification</title>
    <style>
        body { font-family: Arial; margin: 20px; }
        .box { border: 1px solid #ccc; padding: 15px; margin: 10px 0; border-radius: 5px; }
        .success { background: #d4edda; border-color: #28a745; }
        .error { background: #f8d7da; border-color: #721c24; }
        .info { background: #d1ecf1; border-color: #0c5460; }
        h2 { color: #333; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #f5f5f5; }
    </style>
</head>
<body>
    <h1>Email Notification Verification</h1>
    
    <div class="box info">
        <h2>📋 Recent Loan Approvals (Last 24 Hours)</h2>
        <?php
        // Check recent loan approvals
        $stmt = $conDB->prepare("
            SELECT 
                l.id,
                l.inv_no,
                l.loan_amount,
                l.status,
                e.name as employee_name,
                e.emp_id,
                sr.created_at,
                sr.status as approval_status
            FROM emp_loan l
            LEFT JOIN employees e ON l.emp_id = e.emp_id
            LEFT JOIN smt_request_status sr ON l.inv_no = sr.inv_no
            WHERE sr.created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
            AND sr.status LIKE '%approved%'
            ORDER BY sr.created_at DESC
            LIMIT 20
        ");
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            echo "<table>";
            echo "<tr><th>Invoice No</th><th>Employee</th><th>Loan Amount</th><th>Status</th><th>Approval Time</th></tr>";
            while ($row = $result->fetch_assoc()) {
                echo "<tr>";
                echo "<td><strong>{$row['inv_no']}</strong></td>";
                echo "<td>{$row['employee_name']}</td>";
                echo "<td>SAR " . number_format($row['loan_amount'], 2) . "</td>";
                echo "<td>{$row['approval_status']}</td>";
                echo "<td>{$row['created_at']}</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p>No recent approvals found.</p>";
        }
        $stmt->close();
        ?>
    </div>

    <div class="box info">
        <h2>📧 Checking Email Function</h2>
        <?php
        if (function_exists('send_approval_email')) {
            echo "<div class='success'>✅ <strong>send_approval_email()</strong> function is available</div>";
        } else {
            echo "<div class='error'>❌ <strong>send_approval_email()</strong> function NOT FOUND</div>";
        }

        if (function_exists('create_browser_notification')) {
            echo "<div class='success'>✅ <strong>create_browser_notification()</strong> function is available</div>";
        } else {
            echo "<div class='error'>❌ <strong>create_browser_notification()</strong> function NOT FOUND</div>";
        }

        if (class_exists('ApprovalChainManager')) {
            echo "<div class='success'>✅ <strong>ApprovalChainManager</strong> class is available</div>";
        } else {
            echo "<div class='error'>❌ <strong>ApprovalChainManager</strong> class NOT FOUND</div>";
        }

        if (class_exists('ActivityLogger')) {
            echo "<div class='success'>✅ <strong>ActivityLogger</strong> class is available</div>";
        } else {
            echo "<div class='error'>❌ <strong>ActivityLogger</strong> class NOT FOUND</div>";
        }
        ?>
    </div>

    <div class="box info">
        <h2>🔗 Approval Chain for Recent Loans</h2>
        <?php
        // Check approval chains
        $stmt = $conDB->prepare("
            SELECT DISTINCT
                ra.request_inv_no,
                GROUP_CONCAT(CONCAT(ra.approval_level, ':', ra.status) ORDER BY ra.approval_level SEPARATOR ' → ') as chain_status
            FROM request_approvers ra
            WHERE ra.created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
            GROUP BY ra.request_inv_no
            LIMIT 10
        ");
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            echo "<table>";
            echo "<tr><th>Request ID</th><th>Approval Chain</th></tr>";
            while ($row = $result->fetch_assoc()) {
                echo "<tr>";
                echo "<td><strong>{$row['request_inv_no']}</strong></td>";
                echo "<td>{$row['chain_status']}</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p>No recent approval chains found.</p>";
        }
        $stmt->close();
        ?>
    </div>

    <div class="box info">
        <h2>📨 Email Log Check</h2>
        <?php
        // Check if email logs exist
        $log_dir = __DIR__ . '/logs/email_logs';
        if (is_dir($log_dir)) {
            $files = scandir($log_dir);
            $email_files = array_filter($files, function($f) { return strpos($f, 'email') !== false; });
            
            if (!empty($email_files)) {
                echo "<p>Email logs found: " . count($email_files) . " files</p>";
                echo "<table>";
                echo "<tr><th>File</th><th>Size</th><th>Modified</th></tr>";
                foreach (array_slice(array_reverse($email_files), 0, 5) as $file) {
                    $filepath = $log_dir . '/' . $file;
                    echo "<tr>";
                    echo "<td>$file</td>";
                    echo "<td>" . filesize($filepath) . " bytes</td>";
                    echo "<td>" . date('Y-m-d H:i:s', filemtime($filepath)) . "</td>";
                    echo "</tr>";
                }
                echo "</table>";
            } else {
                echo "<div class='error'>⚠️ No email log files found</div>";
            }
        } else {
            echo "<div class='error'>⚠️ Email log directory not found: $log_dir</div>";
        }
        ?>
    </div>

    <div class="box info">
        <h2>💡 Manual Test</h2>
        <p>To manually test email sending, use this SQL query to find a pending approval:</p>
        <pre>
SELECT ra.*, e.name, al.email
FROM request_approvers ra
LEFT JOIN employees e ON ra.approver_id = e.emp_id
LEFT JOIN admin_login al ON e.emp_id = al.emp_id
WHERE ra.status = 'pending'
LIMIT 1;
        </pre>
        <p>Then check if an email was received by the next approver.</p>
    </div>

</body>
</html>
