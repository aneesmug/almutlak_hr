<?php
/**
 * Settlement Creation Test & Debug Tool
 * Simulates the settlement creation process step-by-step
 */

require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/session_check.php';

if ($user_type !== 'admin') {
    die('Access denied - admin only');
}

$test_results = [];

// Test 1: Load SettlementManager
$test_results['Load SettlementManager'] = function_exists('mysqli_query') ? 'OK' : 'FAILED';

try {
    require_once __DIR__ . '/includes/SettlementManager_Corrected.php';
    $test_results['SettlementManager Class'] = class_exists('SettlementManager') ? 'OK' : 'FAILED';
} catch (Exception $e) {
    $test_results['SettlementManager Class'] = 'ERROR: ' . $e->getMessage();
}

// Test 2: Load ApprovalChainManager
try {
    require_once __DIR__ . '/includes/ApprovalChainManager.php';
    $test_results['ApprovalChainManager Class'] = class_exists('ApprovalChainManager') ? 'OK' : 'FAILED';
} catch (Exception $e) {
    $test_results['ApprovalChainManager Class'] = 'ERROR: ' . $e->getMessage();
}

// Test 3: Load helper functions
try {
    require_once __DIR__ . '/includes/helper_functions.php';
    $test_results['send_approval_email'] = function_exists('send_approval_email') ? 'OK' : 'FAILED';
    $test_results['create_browser_notification'] = function_exists('create_browser_notification') ? 'OK' : 'FAILED';
} catch (Exception $e) {
    $test_results['Helper Functions'] = 'ERROR: ' . $e->getMessage();
}

// Test 4: Check database tables
$tables = [
    'settlement_records' => "SHOW TABLES LIKE 'settlement_records'",
    'request_approvers' => "SHOW TABLES LIKE 'request_approvers'",
    'approval_request_types' => "SHOW TABLES LIKE 'approval_request_types'",
];

foreach($tables as $name => $query) {
    $result = mysqli_query($conDB, $query);
    $test_results["Table: $name"] = (mysqli_num_rows($result) > 0) ? 'EXISTS' : 'MISSING';
}

// Test 5: Check settlement type
$typeQry = mysqli_query($conDB, "SELECT id FROM approval_request_types WHERE type_name = 'settlement'");
$test_results['Settlement Request Type'] = (mysqli_num_rows($typeQry) > 0) ? 'REGISTERED' : 'NOT REGISTERED';

// Test 6: Check approval chain config
$chainConfig = get_setting($conDB, 'approval_chain_settlement');
$test_results['Approval Chain Config'] = !empty($chainConfig) ? 'CONFIGURED' : 'MISSING';

// Test 7: Check SMTP
$smtp_ok = !empty(get_setting($conDB, 'smtp_host')) && 
           !empty(get_setting($conDB, 'smtp_port')) &&
           !empty(get_setting($conDB, 'smtp_user')) &&
           !empty(get_setting($conDB, 'smtp_pass')) &&
           !empty(get_setting($conDB, 'from_email'));
$test_results['SMTP Configuration'] = $smtp_ok ? 'COMPLETE' : 'INCOMPLETE';

// Test 8: Check PHPMailer
$test_results['PHPMailer Class'] = class_exists('PHPMailer\\PHPMailer\\PHPMailer') ? 'AVAILABLE' : 'NOT AVAILABLE';

// Test 9: Check last settlement
$lastSett = mysqli_query($conDB, "SELECT COUNT(*) as cnt FROM settlement_records LIMIT 1");
$settRow = mysqli_fetch_assoc($lastSett);
$test_results['Settlement Records'] = $settRow['cnt'] . ' total';

// Test 10: Check recent errors
$error_log = ini_get('error_log');
$test_results['Error Log File'] = file_exists($error_log) ? 'FOUND' : 'NOT FOUND';

?>
<!DOCTYPE html>
<html dir="<?=($is_rtl) ? 'rtl' : 'ltr'?>" lang="<?=$current_lang?>">
<head>
    <meta charset="UTF-8">
    <title>Settlement Creation Test Tool</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet" />
    <style>
        body { padding: 20px; background: #f5f7fa; }
        .container { max-width: 900px; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 5px 20px rgba(0,0,0,0.1); margin: 20px auto; }
        h1 { color: #333; margin-bottom: 30px; }
        
        .test-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }
        
        .test-item {
            padding: 15px;
            border-radius: 8px;
            border-left: 5px solid #ccc;
            background: #f9f9f9;
        }
        
        .test-item.ok { 
            border-left-color: #28a745; 
            background: #d4edda;
        }
        
        .test-item.error { 
            border-left-color: #dc3545; 
            background: #f8d7da;
        }
        
        .test-item.warning { 
            border-left-color: #ffc107; 
            background: #fff3cd;
        }
        
        .test-label { 
            font-weight: bold; 
            color: #333;
            margin-bottom: 5px;
        }
        
        .test-result { 
            font-size: 0.9em; 
            color: #666;
        }
        
        .status-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 0.8em;
            font-weight: bold;
            margin-left: 10px;
        }
        
        .badge-ok { background: #28a745; color: white; }
        .badge-error { background: #dc3545; color: white; }
        .badge-warning { background: #ffc107; color: black; }
        
        .test-instructions {
            background: #e7f3ff;
            padding: 20px;
            border-radius: 8px;
            border-left: 5px solid #0066cc;
            margin-top: 30px;
        }
        
        .test-instructions h3 { color: #0066cc; }
        
        code {
            background: #f0f0f0;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: monospace;
        }
    </style>
</head>
<body>

<div class="container">
    <h1>🧪 Settlement Creation Test Tool</h1>
    <p class="text-muted">Pre-flight checks before creating a settlement. All should be GREEN.</p>
    
    <div class="test-grid">
        <?php
        
        foreach($test_results as $test_name => $result) {
            // Determine status
            $status = 'ok';
            if(stripos($result, 'failed') !== false || stripos($result, 'missing') !== false || 
               stripos($result, 'not ') !== false || stripos($result, 'error') !== false ||
               stripos($result, 'unavailable') !== false) {
                $status = 'error';
            } elseif(stripos($result, 'incomplete') !== false || stripos($result, 'warning') !== false) {
                $status = 'warning';
            }
            
            // Determine badge
            $badge_class = 'badge-' . $status;
            $badge_text = ($status === 'ok') ? '✓ PASS' : (($status === 'warning') ? '⚠ WARN' : '✗ FAIL');
            
            echo '<div class="test-item ' . $status . '">';
            echo '<div class="test-label">' . htmlspecialchars($test_name) . '<span class="status-badge ' . $badge_class . '">' . $badge_text . '</span></div>';
            echo '<div class="test-result">' . htmlspecialchars($result) . '</div>';
            echo '</div>';
        }
        
        ?>
    </div>
    
    <!-- OVERALL RESULT -->
    <?php
    $failures = array_filter($test_results, fn($r) => 
        stripos($r, 'failed') !== false || 
        stripos($r, 'missing') !== false || 
        stripos($r, 'error') !== false ||
        stripos($r, 'unavailable') !== false ||
        stripos($r, 'not registered') !== false ||
        stripos($r, 'not found') !== false
    );
    
    if(empty($failures)) {
        echo '<div style="background: #d4edda; padding: 20px; border-radius: 8px; text-align: center; margin-top: 30px;">';
        echo '<h2 style="color: #28a745;">✓ All Tests Passed</h2>';
        echo '<p>Settlement creation should work correctly.</p>';
        echo '<p><strong>Next:</strong> Create a test EOS and check server logs for errors.</p>';
        echo '</div>';
    } else {
        echo '<div style="background: #f8d7da; padding: 20px; border-radius: 8px; border-left: 5px solid #dc3545; margin-top: 30px;">';
        echo '<h2 style="color: #dc3545;">✗ Some Tests Failed</h2>';
        echo '<p>Fix the issues below before settlement creation will work:</p>';
        echo '<ul>';
        foreach($failures as $test => $result) {
            echo '<li><strong>' . htmlspecialchars($test) . ':</strong> ' . htmlspecialchars($result) . '</li>';
        }
        echo '</ul>';
        echo '</div>';
    }
    ?>
    
    <div class="test-instructions">
        <h3>📋 Manual Testing Steps</h3>
        <ol>
            <li>Fix any failing tests above</li>
            <li>Create an employee with a resignation request</li>
            <li>Approve the resignation</li>
            <li>Navigate to EOS page and submit the form</li>
            <li>Check that SweetAlert2 shows "Processing..."</li>
            <li>Wait for success popup to appear (at least 2 seconds)</li>
            <li>Click OK to redirect</li>
            <li>Check server error log:<br>
                <code>tail -f <?= htmlspecialchars($error_log) ?> | grep -i settlement</code></li>
            <li>Check settlement_records table:<br>
                <code>SELECT * FROM settlement_records ORDER BY created_at DESC LIMIT 1;</code></li>
            <li>Check approver's email inbox</li>
        </ol>
    </div>
    
    <div style="background: #fff3cd; padding: 15px; border-radius: 8px; margin-top: 20px;">
        <strong>🔍 Debugging Tools:</strong>
        <ul style="margin-bottom: 0;">
            <li><a href="check_eos_workflow.php">check_eos_workflow.php</a> - Full endpoint verification</li>
            <li><a href="analyze_settlement_logs.php">analyze_settlement_logs.php</a> - Log analysis & filtering</li>
            <li><a href="validate_settlement_config.php">validate_settlement_config.php</a> - Configuration dashboard</li>
        </ul>
    </div>

</div>

<script src="assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>
