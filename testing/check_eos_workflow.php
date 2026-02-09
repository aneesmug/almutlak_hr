<?php
/**
 * EOS to Settlement Workflow Endpoint Checker
 * Traces the entire flow from EOS submission to email sending
 * Identifies where the process breaks
 */

require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/session_check.php';

if ($user_type !== 'admin') {
    die('Access denied - admin only');
}

?>
<!DOCTYPE html>
<html dir="<?=($is_rtl) ? 'rtl' : 'ltr'?>" lang="<?=$current_lang?>">
<head>
    <meta charset="UTF-8">
    <title>EOS Workflow Endpoint Checker</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet" />
    <style>
        body { padding: 20px; background: #f5f7fa; }
        .container { max-width: 1000px; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 5px 20px rgba(0,0,0,0.1); margin: 20px auto; }
        h1 { color: #333; margin-bottom: 30px; }
        .endpoint { 
            padding: 15px; 
            margin: 15px 0; 
            border-left: 5px solid #ccc; 
            border-radius: 4px;
            background: #f9f9f9;
        }
        .endpoint.success { border-left-color: #28a745; background: #d4edda; }
        .endpoint.error { border-left-color: #dc3545; background: #f8d7da; }
        .endpoint.warning { border-left-color: #ffc107; background: #fff3cd; }
        .endpoint.info { border-left-color: #17a2b8; background: #d1ecf1; }
        
        .flow-diagram {
            background: #f0f0f0;
            padding: 20px;
            border-radius: 8px;
            font-family: monospace;
            line-height: 1.8;
            margin: 20px 0;
            white-space: pre-wrap;
            word-break: break-all;
        }
        
        .test-result { margin: 20px 0; padding: 15px; border-radius: 4px; }
        .test-result pre { background: #f5f5f5; padding: 10px; overflow-x: auto; }
        
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        td, th { padding: 10px; border-bottom: 1px solid #ddd; text-align: left; }
        th { background: #f0f0f0; font-weight: bold; }
        
        .status-badge { 
            padding: 5px 10px; 
            border-radius: 20px; 
            font-size: 0.85em;
            font-weight: bold;
        }
        .status-ok { background: #28a745; color: white; }
        .status-fail { background: #dc3545; color: white; }
        .status-warn { background: #ffc107; color: black; }
    </style>
</head>
<body>

<div class="container">
    <h1>🔍 EOS → Settlement Workflow Endpoint Checker</h1>
    <p class="text-muted">This tool checks each step of the EOS submission to settlement creation and email sending process.</p>
    
    <!-- WORKFLOW DIAGRAM -->
    <div class="flow-diagram">
1. USER SUBMITS EOS FORM
         ↓
2. PHP processes form (emp_eos INSERT)
         ↓
3. Settlement Manager called (createSettlement)
         ↓
4. Settlement record created (settlement_records INSERT)
         ↓
5. Approval chain created (ApprovalChainManager)
         ↓
6. Request approvers created (request_approvers INSERT)
         ↓
7. Email notification sent (sendSettlementCreationNotifications)
         ↓
8. Page shows success popup (SweetAlert2)
         ↓
9. Page redirects back (after user confirms)
    </div>
    
    <!-- ENDPOINT CHECKS -->
    <h2>📋 Endpoint Status Checks</h2>
    
    <?php
    
    // 1. CHECK: Database Connection
    echo '<div class="endpoint success">';
    echo '<h4>1. Database Connection</h4>';
    if($conDB && mysqli_ping($conDB)) {
        echo '<p><span class="status-badge status-ok">✓ OK</span> Database connection is active</p>';
    } else {
        echo '<p><span class="status-badge status-fail">✗ FAILED</span> Cannot connect to database</p>';
    }
    echo '</div>';
    
    // 2. CHECK: SettlementManager Class
    echo '<div class="endpoint">';
    echo '<h4>2. SettlementManager Class</h4>';
    if(file_exists(__DIR__ . '/includes/SettlementManager_Corrected.php')) {
        require_once __DIR__ . '/includes/SettlementManager_Corrected.php';
        if(class_exists('SettlementManager')) {
            echo '<p><span class="status-badge status-ok">✓ OK</span> SettlementManager class exists and can be loaded</p>';
        } else {
            echo '<p><span class="status-badge status-fail">✗ FAILED</span> Class file exists but SettlementManager not found</p>';
        }
    } else {
        echo '<p><span class="status-badge status-fail">✗ FAILED</span> SettlementManager_Corrected.php not found</p>';
    }
    echo '</div>';
    
    // 3. CHECK: ApprovalChainManager Class
    echo '<div class="endpoint">';
    echo '<h4>3. ApprovalChainManager Class</h4>';
    if(file_exists(__DIR__ . '/includes/ApprovalChainManager.php')) {
        require_once __DIR__ . '/includes/ApprovalChainManager.php';
        if(class_exists('ApprovalChainManager')) {
            echo '<p><span class="status-badge status-ok">✓ OK</span> ApprovalChainManager class exists and can be loaded</p>';
        } else {
            echo '<p><span class="status-badge status-fail">✗ FAILED</span> Class file exists but ApprovalChainManager not found</p>';
        }
    } else {
        echo '<p><span class="status-badge status-fail">✗ FAILED</span> ApprovalChainManager.php not found</p>';
    }
    echo '</div>';
    
    // 4. CHECK: Helper Functions
    echo '<div class="endpoint">';
    echo '<h4>4. Helper Functions (send_approval_email, create_browser_notification)</h4>';
    require_once __DIR__ . '/includes/helper_functions.php';
    $has_send_email = function_exists('send_approval_email');
    $has_browser_notif = function_exists('create_browser_notification');
    
    if($has_send_email && $has_browser_notif) {
        echo '<p><span class="status-badge status-ok">✓ OK</span> Both functions exist</p>';
    } else {
        echo '<p><span class="status-badge status-fail">✗ MISSING</span>';
        if(!$has_send_email) echo ' send_approval_email';
        if(!$has_browser_notif) echo ' create_browser_notification';
        echo '</p>';
    }
    echo '</div>';
    
    // 5. CHECK: approval_request_types table
    echo '<div class="endpoint">';
    echo '<h4>5. Settlement Request Type Registration</h4>';
    $typeQry = mysqli_query($conDB, "SELECT id, type_name FROM approval_request_types WHERE type_name = 'settlement' LIMIT 1");
    if($typeQry && mysqli_num_rows($typeQry) > 0) {
        $typeRow = mysqli_fetch_assoc($typeQry);
        echo '<p><span class="status-badge status-ok">✓ OK</span> Settlement type is registered (ID: ' . htmlspecialchars($typeRow['id']) . ')</p>';
    } else {
        echo '<p><span class="status-badge status-fail">✗ NOT REGISTERED</span> Add: INSERT INTO approval_request_types (type_name, description) VALUES ("settlement", "Settlement Records");</p>';
    }
    echo '</div>';
    
    // 6. CHECK: approval_chain_settlement Configuration
    echo '<div class="endpoint">';
    echo '<h4>6. Approval Chain Configuration (app_settings)</h4>';
    $chainConfig = get_setting($conDB, 'approval_chain_settlement');
    if(!empty($chainConfig)) {
        $chainArray = json_decode($chainConfig, true);
        $chainCount = count($chainArray['chain'] ?? []);
        echo '<p><span class="status-badge status-ok">✓ OK</span> Approval chain configured with ' . $chainCount . ' level(s)</p>';
        echo '<details style="margin-top: 10px;">';
        echo '<summary style="cursor: pointer; color: #007bff;">View Configuration</summary>';
        echo '<pre>' . json_encode($chainArray, JSON_PRETTY_PRINT) . '</pre>';
        echo '</details>';
    } else {
        echo '<p><span class="status-badge status-fail">✗ NOT CONFIGURED</span> Add approval_chain_settlement to app_settings table</p>';
    }
    echo '</div>';
    
    // 7. CHECK: SMTP Settings
    echo '<div class="endpoint">';
    echo '<h4>7. SMTP Configuration</h4>';
    $smtp_settings = [
        'smtp_host' => get_setting($conDB, 'smtp_host'),
        'smtp_port' => get_setting($conDB, 'smtp_port'),
        'smtp_user' => get_setting($conDB, 'smtp_user'),
        'smtp_pass' => get_setting($conDB, 'smtp_pass'),
        'from_email' => get_setting($conDB, 'from_email'),
        'from_name' => get_setting($conDB, 'from_name'),
        'smtp_encryption' => get_setting($conDB, 'smtp_encryption'),
    ];
    
    $all_smtp_ok = true;
    echo '<table>';
    foreach($smtp_settings as $key => $value) {
        $status = !empty($value) ? 'OK' : 'MISSING';
        if(empty($value)) $all_smtp_ok = false;
        $display = (strpos($key, 'pass') !== false) ? '[HIDDEN]' : htmlspecialchars($value ?? '');
        $badge = empty($value) ? '<span class="status-badge status-warn">⚠</span>' : '<span class="status-badge status-ok">✓</span>';
        echo '<tr><td>' . $badge . ' ' . htmlspecialchars($key) . '</td><td>' . $display . '</td></tr>';
    }
    echo '</table>';
    if($all_smtp_ok) {
        echo '<p style="margin-top: 10px;"><span class="status-badge status-ok">✓ OK</span> All SMTP settings configured</p>';
    } else {
        echo '<p style="margin-top: 10px;"><span class="status-badge status-fail">✗ INCOMPLETE</span> Some SMTP settings missing</p>';
    }
    echo '</div>';
    
    // 8. CHECK: PHPMailer Library
    echo '<div class="endpoint">';
    echo '<h4>8. PHPMailer Library</h4>';
    if(class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
        echo '<p><span class="status-badge status-ok">✓ OK</span> PHPMailer class is available</p>';
    } else {
        echo '<p><span class="status-badge status-fail">✗ NOT FOUND</span> PHPMailer is required for email sending</p>';
    }
    echo '</div>';
    
    // 9. CHECK: Sample Settlement
    echo '<div class="endpoint">';
    echo '<h4>9. Latest Settlement Record</h4>';
    $latestSettlement = mysqli_query($conDB, "
        SELECT 
            sr.*, 
            GROUP_CONCAT(ra.approver_id) as approver_ids,
            COUNT(ra.id) as approver_count
        FROM settlement_records sr
        LEFT JOIN request_approvers ra ON ra.request_inv_no = sr.request_inv_no
        GROUP BY sr.id
        ORDER BY sr.created_at DESC
        LIMIT 1
    ");
    
    if($latestSettlement && mysqli_num_rows($latestSettlement) > 0) {
        $settlement = mysqli_fetch_assoc($latestSettlement);
        echo '<p><strong>Settlement:</strong> ' . htmlspecialchars($settlement['request_inv_no']) . '</p>';
        echo '<p><strong>Created:</strong> ' . htmlspecialchars($settlement['created_at']) . '</p>';
        echo '<p><strong>Status:</strong> ' . htmlspecialchars($settlement['settlement_status']) . '</p>';
        echo '<p><strong>Approvers in Chain:</strong> ' . ($settlement['approver_count'] ?? 0) . '</p>';
        
        if($settlement['approver_count'] > 0) {
            echo '<p><span class="status-badge status-ok">✓ OK</span> Approval chain created</p>';
        } else {
            echo '<p><span class="status-badge status-fail">✗ NO APPROVERS</span> Approval chain was not created</p>';
        }
    } else {
        echo '<p><span class="status-badge status-warn">⚠</span> No settlements found yet (create one to test)</p>';
    }
    echo '</div>';
    
    // 10. CHECK: Error Logs
    echo '<div class="endpoint">';
    echo '<h4>10. Server Error Logs</h4>';
    $error_log = ini_get('error_log');
    if(file_exists($error_log)) {
        $lines = file($error_log, FILE_IGNORE_NEW_LINES);
        $recent = array_slice($lines, -30);
        
        // Filter for settlement-related entries
        $settlement_logs = array_filter($recent, function($line) {
            return stripos($line, 'settlement') !== false || 
                   stripos($line, 'SEND_EMAIL') !== false ||
                   stripos($line, 'approval') !== false;
        });
        
        if(!empty($settlement_logs)) {
            echo '<p><span class="status-badge status-ok">✓ FOUND</span> Settlement-related log entries</p>';
            echo '<details>';
            echo '<summary style="cursor: pointer; color: #007bff;">View Log Entries (' . count($settlement_logs) . ')</summary>';
            echo '<pre>';
            foreach($settlement_logs as $line) {
                echo htmlspecialchars(substr($line, 0, 300)) . "\n";
            }
            echo '</pre>';
            echo '</details>';
        } else {
            echo '<p><span class="status-badge status-warn">⚠</span> No settlement logs found (check if logging is enabled)</p>';
        }
    } else {
        echo '<p><span class="status-badge status-warn">⚠</span> Error log not accessible: ' . htmlspecialchars($error_log) . '</p>';
    }
    echo '</div>';
    
    // SUMMARY
    echo '<h2 style="margin-top: 40px;">✅ Summary & Recommendations</h2>';
    
    $issues = [];
    if(empty($chainConfig)) $issues[] = 'Approval chain configuration missing';
    if(!$all_smtp_ok) $issues[] = 'SMTP settings incomplete';
    if(!class_exists('PHPMailer\\PHPMailer\\PHPMailer')) $issues[] = 'PHPMailer not found';
    
    if(empty($issues)) {
        echo '<div class="endpoint success">';
        echo '<h4>✓ All Endpoints Operational</h4>';
        echo '<p>The EOS → Settlement → Email workflow should work correctly.</p>';
        echo '<p><strong>Next step:</strong> Create a test EOS and monitor the server error log.</p>';
        echo '</div>';
    } else {
        echo '<div class="endpoint error">';
        echo '<h4>✗ Issues Found</h4>';
        echo '<ul>';
        foreach($issues as $issue) {
            echo '<li>' . htmlspecialchars($issue) . '</li>';
        }
        echo '</ul>';
        echo '</div>';
    }
    
    ?>
    
    <hr style="margin-top: 40px;">
    <h3>📝 Testing Instructions</h3>
    <ol>
        <li>Review all endpoint statuses above</li>
        <li>Fix any issues marked with ✗</li>
        <li>Create a test EOS for an employee with a resignation request</li>
        <li>Check the server error log for "=== EOS SETTLEMENT CREATION" messages</li>
        <li>Verify settlement record appears in settlement_records table</li>
        <li>Check approvers in request_approvers table</li>
        <li>Verify email in approver's inbox</li>
    </ol>

</div>

<script src="assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>
