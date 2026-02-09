<?php
/**
 * Settlement Email Configuration Validator & Auto-Fixer
 * This script checks all prerequisites for settlement emails and can auto-fix common issues
 */

require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/session_check.php';

// Check admin permission (only HR admin can run this)
if ($user_type !== 'admin') {
    die('<h2>Access Denied</h2><p>Only administrators can access this page.</p>');
}

?>
<!DOCTYPE html>
<html dir="<?=($is_rtl) ? 'rtl' : 'ltr'?>" lang="<?=$current_lang?>">
<head>
    <meta charset="UTF-8">
    <title>Settlement Email Configuration Checker</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet" />
    <link href="assets/css/icons.css" rel="stylesheet" />
    <style>
        body { 
            padding: 20px; 
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
        }
        .container {
            max-width: 900px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            padding: 30px;
            margin-top: 20px;
        }
        .check-item {
            padding: 15px;
            margin: 10px 0;
            border-left: 5px solid #ccc;
            border-radius: 4px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .check-item.success {
            background: #d4edda;
            border-left-color: #28a745;
        }
        .check-item.error {
            background: #f8d7da;
            border-left-color: #dc3545;
        }
        .check-item.warning {
            background: #fff3cd;
            border-left-color: #ffc107;
        }
        .check-item.info {
            background: #d1ecf1;
            border-left-color: #17a2b8;
        }
        .badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.85em;
        }
        .btn-group-config {
            margin-top: 20px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        .form-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        h3 { color: #333; margin-top: 30px; }
        .status-icon { 
            font-size: 20px; 
            margin-right: 10px;
        }
        table { 
            width: 100%; 
            margin-top: 10px;
            border-collapse: collapse;
        }
        td { 
            padding: 8px; 
            border-bottom: 1px solid #ddd;
        }
        td:first-child {
            font-weight: bold;
            width: 35%;
            color: #555;
        }
    </style>
</head>
<body>

<div class="container">
    <h1>⚙️ Settlement Email Configuration Checker</h1>
    <p class="text-muted">This tool validates all prerequisites for settlement email sending and provides auto-fixes.</p>
    
    <?php
    
    // PHASE 1: CHECK SMTP SETTINGS
    echo '<h3>📧 Phase 1: SMTP Configuration</h3>';
    
    $smtp_checks = [
        'smtp_host' => get_setting($conDB, 'smtp_host'),
        'smtp_port' => get_setting($conDB, 'smtp_port'),
        'smtp_user' => get_setting($conDB, 'smtp_user'),
        'smtp_pass' => get_setting($conDB, 'smtp_pass'),
        'from_email' => get_setting($conDB, 'from_email'),
        'from_name' => get_setting($conDB, 'from_name'),
        'smtp_encryption' => get_setting($conDB, 'smtp_encryption'),
    ];
    
    $smtp_complete = true;
    foreach ($smtp_checks as $key => $value) {
        $status = !empty($value) ? 'success' : 'error';
        $statusText = !empty($value) ? '✓ Configured' : '✗ Missing';
        $display = (strpos($key, 'pass') !== false) ? '[HIDDEN]' : htmlspecialchars($value ?? '');
        
        if (empty($value)) $smtp_complete = false;
        
        echo "<div class='check-item $status'>";
        echo "<div>";
        echo "<strong>$key</strong><br>";
        echo "<small class='text-muted'>$display</small>";
        echo "</div>";
        echo "<span class='badge " . ($status === 'success' ? 'badge-success' : 'badge-danger') . "'>$statusText</span>";
        echo "</div>";
    }
    
    // PHASE 2: CHECK SETTLEMENT REQUEST TYPE
    echo '<h3>🏷️ Phase 2: Settlement Request Type</h3>';
    
    $typeQry = mysqli_query($conDB, "SELECT id FROM approval_request_types WHERE type_name = 'settlement' LIMIT 1");
    $typeExists = $typeQry && mysqli_num_rows($typeQry) > 0;
    
    echo "<div class='check-item " . ($typeExists ? 'success' : 'error') . "'>";
    echo "<div>";
    echo "<strong>Settlement Request Type</strong><br>";
    echo "<small class='text-muted'>" . ($typeExists ? 'Type ID found' : 'Type not registered') . "</small>";
    echo "</div>";
    echo "<span class='badge " . ($typeExists ? 'badge-success' : 'badge-danger') . "'>" . ($typeExists ? '✓ Exists' : '✗ Missing') . "</span>";
    echo "</div>";
    
    // PHASE 3: CHECK APPROVAL CHAIN CONFIGURATION
    echo '<h3>⛓️ Phase 3: Approval Chain Configuration</h3>';
    
    $chainConfig = get_setting($conDB, 'approval_chain_settlement');
    $chainConfigured = !empty($chainConfig);
    
    echo "<div class='check-item " . ($chainConfigured ? 'success' : 'error') . "'>";
    echo "<div>";
    echo "<strong>approval_chain_settlement Setting</strong><br>";
    if($chainConfigured) {
        echo "<small class='text-muted'>✓ Configured with " . count(json_decode($chainConfig, true)['chain'] ?? []) . " approvers</small>";
    } else {
        echo "<small class='text-muted'>✗ Not configured</small>";
    }
    echo "</div>";
    echo "<span class='badge " . ($chainConfigured ? 'badge-success' : 'badge-danger') . "'>" . ($chainConfigured ? '✓ Set' : '✗ Missing') . "</span>";
    echo "</div>";
    
    if($chainConfigured) {
        echo "<details style='margin: 10px 0;'>";
        echo "<summary style='cursor: pointer; color: #007bff;'>View Configuration</summary>";
        echo "<pre>" . json_encode(json_decode($chainConfig, true), JSON_PRETTY_PRINT) . "</pre>";
        echo "</details>";
    }
    
    // PHASE 4: CHECK SAMPLE SETTLEMENT
    echo '<h3>📋 Phase 4: Sample Settlement Verification</h3>';
    
    $lastSettlement = mysqli_query($conDB, "
        SELECT sr.*, GROUP_CONCAT(DISTINCT ra.approver_id) as approver_ids
        FROM settlement_records sr
        LEFT JOIN request_approvers ra ON ra.request_inv_no = sr.request_inv_no
        ORDER BY sr.created_at DESC
        LIMIT 1
    ");
    
    if($lastSettlement && mysqli_num_rows($lastSettlement) > 0) {
        $settlement = mysqli_fetch_assoc($lastSettlement);
        echo "<div class='check-item info'>";
        echo "<div>";
        echo "<strong>Latest Settlement</strong><br>";
        echo "<small class='text-muted'>" . htmlspecialchars($settlement['request_inv_no']) . " - " . htmlspecialchars($settlement['created_at']) . "</small>";
        echo "</div>";
        echo "</div>";
        
        // Check approvers
        if(!empty($settlement['approver_ids'])) {
            echo "<div class='check-item success'>";
            echo "<div>";
            echo "<strong>Approvers Found</strong><br>";
            echo "<small class='text-muted'>" . count(explode(',', $settlement['approver_ids'])) . " approvers in chain</small>";
            echo "</div>";
            echo "</div>";
        } else {
            echo "<div class='check-item error'>";
            echo "<div>";
            echo "<strong>No Approvers Found</strong><br>";
            echo "<small class='text-muted'>Approval chain was not created for this settlement</small>";
            echo "</div>";
            echo "</div>";
        }
    } else {
        echo "<div class='check-item warning'>";
        echo "<div>";
        echo "<strong>No Settlements Found</strong><br>";
        echo "<small class='text-muted'>Create an EOS to generate a settlement first</small>";
        echo "</div>";
        echo "</div>";
    }
    
    // PHASE 5: CHECK APPROVER EMAILS
    echo '<h3>👥 Phase 5: Approver Email Addresses</h3>';
    
    $approversQry = mysqli_query($conDB, "
        SELECT DISTINCT 
            ra.approver_id, 
            e.name,
            al.email
        FROM request_approvers ra
        JOIN employees e ON e.emp_id = ra.approver_id
        LEFT JOIN admin_login al ON al.emp_id = ra.approver_id
        WHERE ra.request_type_id IN (
            SELECT id FROM approval_request_types WHERE type_name = 'settlement'
        )
        ORDER BY e.name
    ");
    
    if($approversQry && mysqli_num_rows($approversQry) > 0) {
        echo "<table>";
        echo "<tr><th>Approver Name</th><th>Approver ID</th><th>Email</th><th>Status</th></tr>";
        while($approver = mysqli_fetch_assoc($approversQry)) {
            $emailStatus = empty($approver['email']) ? '<span class="badge badge-danger">✗ NO EMAIL</span>' : '<span class="badge badge-success">✓ ' . htmlspecialchars($approver['email']) . '</span>';
            echo "<tr>";
            echo "<td>" . htmlspecialchars($approver['name']) . "</td>";
            echo "<td>" . htmlspecialchars($approver['approver_id']) . "</td>";
            echo "<td colspan='2'>" . $emailStatus . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<div class='check-item warning'>";
        echo "<div>No approvers found yet. Create a settlement to see approver list.</div>";
        echo "</div>";
    }
    
    // SUMMARY
    echo '<h3>✅ Summary</h3>';
    
    $allGood = $smtp_complete && $typeExists && $chainConfigured;
    
    if($allGood) {
        echo "<div class='alert alert-success'>";
        echo "<h4>✓ All Prerequisites Met!</h4>";
        echo "<p>Settlement emails should be sent successfully. If emails still aren't arriving:</p>";
        echo "<ol>";
        echo "<li>Check approver email addresses (Phase 5 above)</li>";
        echo "<li>Verify SMTP server accepts connections</li>";
        echo "<li>Check server error log for mail errors</li>";
        echo "<li>Look in spam/junk folder</li>";
        echo "</ol>";
        echo "</div>";
    } else {
        echo "<div class='alert alert-danger'>";
        echo "<h4>✗ Configuration Issues Found</h4>";
        echo "<p>The following need to be fixed:</p>";
        echo "<ul>";
        if(!$smtp_complete) echo "<li>Fill in all SMTP settings (Phase 1)</li>";
        if(!$typeExists) echo "<li>Register settlement request type (Phase 2)</li>";
        if(!$chainConfigured) echo "<li>Configure approval chain (Phase 3)</li>";
        echo "</ul>";
        echo "</div>";
    }
    
    // AUTO-FIX BUTTON
    if(isset($_POST['auto_fix']) && $_POST['auto_fix'] == '1') {
        echo "<h3 style='color: green;'>🔧 Auto-Fix Results</h3>";
        
        $fixes_applied = 0;
        
        // Fix 1: Register settlement type if missing
        if(!$typeExists) {
            $insertType = mysqli_query($conDB, "
                INSERT INTO approval_request_types (type_name, description) 
                VALUES ('settlement', 'Settlement Records')
            ");
            if($insertType) {
                echo "<div class='alert alert-success'>✓ Settlement request type registered</div>";
                $fixes_applied++;
            } else {
                echo "<div class='alert alert-warning'>⚠ Could not register settlement type</div>";
            }
        }
        
        // Fix 2: Update SMTP encryption if TLS not set
        if(empty($smtp_checks['smtp_encryption'])) {
            $updateEnc = mysqli_query($conDB, "
                INSERT INTO app_settings (setting_key, setting_value) 
                VALUES ('smtp_encryption', 'TLS')
                ON DUPLICATE KEY UPDATE setting_value = 'TLS'
            ");
            if($updateEnc) {
                echo "<div class='alert alert-success'>✓ SMTP encryption set to TLS</div>";
                $fixes_applied++;
            }
        }
        
        if($fixes_applied > 0) {
            echo "<div class='alert alert-info'><strong>$fixes_applied fix(es) applied.</strong> Please refresh this page to verify.</div>";
        } else {
            echo "<div class='alert alert-info'>No automatic fixes were needed.</div>";
        }
    }
    
    // CONFIGURATION FORM
    if(!$smtp_complete || !$typeExists || !$chainConfigured) {
        echo '<form method="POST" style="margin-top: 20px;">';
        
        if(!$smtp_complete) {
            echo '<div class="form-section">';
            echo '<h4>📧 Configure SMTP Settings</h4>';
            echo '<p>Add your SMTP configuration directly in the database or through app_settings interface.</p>';
            echo '<p>Visit: <code>app_settings.php</code> to configure SMTP manually.</p>';
            echo '</div>';
        }
        
        if(!$typeExists || !$chainConfigured) {
            echo '<div class="form-section">';
            echo '<h4>🔧 Run Auto-Fixes</h4>';
            echo '<button type="submit" name="auto_fix" value="1" class="btn btn-primary">';
            echo 'Apply Automatic Fixes';
            echo '</button>';
            echo '<p class="text-muted mt-2">This will register missing request types and set default values.</p>';
            echo '</div>';
        }
        
        echo '</form>';
    }
    
    ?>
    
    <div style="margin-top: 40px; padding-top: 20px; border-top: 2px solid #ddd;">
        <h4>📌 Need Help?</h4>
        <ul>
            <li>Read: <code>SETTLEMENT_EMAIL_QUICK_GUIDE.txt</code></li>
            <li>Troubleshoot: <code>debug_settlement_email.php?settlement_inv_no=SETL-YOUR-NUMBER</code></li>
            <li>Full Guide: <code>SETTLEMENT_EMAIL_TROUBLESHOOTING.md</code></li>
        </ul>
        <p class="text-muted"><em>Last check: <?= date('Y-m-d H:i:s') ?></em></p>
    </div>
</div>

<script src="assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>
