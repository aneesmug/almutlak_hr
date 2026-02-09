<?php
/**
 * Settlement Email Log Analyzer
 * Shows detailed execution logs from settlement creation to email sending
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
    <title>Settlement Email Execution Log Analyzer</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet" />
    <style>
        body { padding: 20px; background: #1e1e1e; color: #eee; font-family: 'Courier New', monospace; }
        .container { max-width: 1200px; background: #252526; padding: 20px; border-radius: 5px; box-shadow: 0 5px 20px rgba(0,0,0,0.5); margin: 20px auto; }
        h1 { color: #4ec9b0; margin-bottom: 20px; }
        h2 { color: #569cd6; margin-top: 30px; }
        
        .log-line { 
            padding: 8px; 
            border-left: 3px solid #666;
            margin: 2px 0;
            background: #1e1e1e;
            font-size: 0.9em;
            line-height: 1.4;
            word-break: break-word;
        }
        
        .log-line.start { 
            border-left-color: #4ec9b0; 
            background: #1a2a2a;
            font-weight: bold;
        }
        
        .log-line.end { 
            border-left-color: #569cd6; 
            background: #1a1f2a;
            font-weight: bold;
        }
        
        .log-line.success { 
            border-left-color: #6a9955; 
            color: #6a9955;
        }
        
        .log-line.error { 
            border-left-color: #f48771; 
            color: #f48771;
            background: #2a1a1a;
        }
        
        .log-line.warning { 
            border-left-color: #dcdcaa; 
            color: #dcdcaa;
        }
        
        .log-line.info { 
            border-left-color: #569cd6; 
            color: #9cdcfe;
        }
        
        .log-container {
            background: #252526;
            padding: 15px;
            border-radius: 4px;
            margin: 15px 0;
            max-height: 600px;
            overflow-y: auto;
            border: 1px solid #444;
        }
        
        .filter-section {
            background: #2d2d30;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        
        .filter-btn {
            padding: 8px 15px;
            margin: 5px 5px 5px 0;
            border: 1px solid #666;
            background: #3e3e42;
            color: #eee;
            border-radius: 3px;
            cursor: pointer;
        }
        
        .filter-btn.active {
            background: #569cd6;
            border-color: #569cd6;
        }
        
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .stat-box {
            background: #2d2d30;
            padding: 15px;
            border-radius: 4px;
            border-left: 4px solid #569cd6;
        }
        
        .stat-box .label { color: #999; font-size: 0.9em; }
        .stat-box .value { color: #4ec9b0; font-size: 1.5em; font-weight: bold; }
        
        .timestamp { color: #858585; font-size: 0.85em; }
    </style>
</head>
<body>

<div class="container">
    <h1>📊 Settlement Email Execution Log Analyzer</h1>
    <p style="color: #999;">Analyze server logs to find where the EOS settlement creation and email sending process fails.</p>
    
    <?php
    
    $error_log = ini_get('error_log');
    
    if(!file_exists($error_log)) {
        echo '<div style="color: #f48771; padding: 20px; background: #2a1a1a; border-radius: 4px;">';
        echo '<strong>✗ Error Log Not Found</strong><br>';
        echo 'Path: ' . htmlspecialchars($error_log) . '<br>';
        echo 'Enable error logging in php.ini';
        echo '</div>';
        die();
    }
    
    // Read log file
    $lines = file($error_log, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $lines = array_reverse($lines); // Most recent first
    
    // Filter logs for settlement-related entries
    $eos_logs = array_filter($lines, function($line) {
        return stripos($line, 'eos') !== false ||
               stripos($line, 'settlement') !== false ||
               stripos($line, 'send_email') !== false ||
               stripos($line, 'approval') !== false ||
               stripos($line, 'notification') !== false;
    });
    
    // Further filter to get last 100 entries
    $eos_logs = array_slice(array_values($eos_logs), 0, 100);
    
    // Categorize logs
    $categorized = [
        'start' => [],
        'success' => [],
        'error' => [],
        'warning' => [],
        'info' => [],
        'end' => []
    ];
    
    foreach($eos_logs as $line) {
        if(stripos($line, 'START') !== false || stripos($line, 'BEGIN') !== false) {
            $categorized['start'][] = $line;
        } elseif(stripos($line, 'successfully') !== false || stripos($line, 'success') !== false || 
                 stripos($line, 'created') !== false || stripos($line, 'sent') !== false) {
            $categorized['success'][] = $line;
        } elseif(stripos($line, 'error') !== false || stripos($line, 'failed') !== false || 
                 stripos($line, 'exception') !== false) {
            $categorized['error'][] = $line;
        } elseif(stripos($line, 'warning') !== false || stripos($line, 'critical') !== false) {
            $categorized['warning'][] = $line;
        } elseif(stripos($line, 'END') !== false) {
            $categorized['end'][] = $line;
        } else {
            $categorized['info'][] = $line;
        }
    }
    
    // Calculate statistics
    $stats = [
        'Total Entries' => count($eos_logs),
        'Start Messages' => count($categorized['start']),
        'Success Messages' => count($categorized['success']),
        'Error Messages' => count($categorized['error']),
        'Warning Messages' => count($categorized['warning']),
        'End Messages' => count($categorized['end']),
    ];
    
    ?>
    
    <!-- STATISTICS -->
    <div class="stats">
        <?php foreach($stats as $label => $value): ?>
        <div class="stat-box">
            <div class="label"><?= htmlspecialchars($label) ?></div>
            <div class="value"><?= $value ?></div>
        </div>
        <?php endforeach; ?>
    </div>
    
    <!-- FILTER BUTTONS -->
    <div class="filter-section">
        <strong style="color: #dcdcaa;">Filter by Type:</strong>
        <button class="filter-btn active" onclick="showLogs('all')">All</button>
        <button class="filter-btn" onclick="showLogs('start')">Start</button>
        <button class="filter-btn" onclick="showLogs('success')">Success</button>
        <button class="filter-btn" onclick="showLogs('error')">Errors</button>
        <button class="filter-btn" onclick="showLogs('warning')">Warnings</button>
        <button class="filter-btn" onclick="showLogs('end')">End</button>
        <button class="filter-btn" onclick="copyToClipboard()">📋 Copy All</button>
    </div>
    
    <!-- LOG CONTAINER -->
    <h2>Recent Settlement Logs</h2>
    <div class="log-container" id="logContainer">
        <?php
        
        if(empty($eos_logs)) {
            echo '<div style="color: #dcdcaa; padding: 20px;">';
            echo '⚠ No settlement-related logs found in the last 100 entries.<br>';
            echo 'This could mean:<br>';
            echo '1. No EOS/settlement creation was attempted<br>';
            echo '2. Logging is not enabled<br>';
            echo '3. Log file is elsewhere (check php.ini)<br>';
            echo '</div>';
        } else {
            // Show all logs grouped by type
            foreach(['start', 'success', 'error', 'warning', 'info', 'end'] as $type) {
                if(!empty($categorized[$type])) {
                    foreach($categorized[$type] as $line) {
                        $class = $type === 'info' ? 'info' : $type;
                        $display_line = htmlspecialchars($line);
                        echo '<div class="log-line ' . $class . '" data-type="' . $type . '">' . $display_line . '</div>';
                    }
                }
            }
        }
        
        ?>
    </div>
    
    <!-- EXECUTION FLOW ANALYSIS -->
    <h2>🔍 Execution Flow Analysis</h2>
    
    <?php
    
    $flow_analysis = [
        'EOS form submitted' => !empty($categorized['start']),
        'Settlement creation started' => count(array_filter($categorized['start'], fn($l) => stripos($l, 'settlement') !== false)) > 0,
        'Settlement record created' => count(array_filter($categorized['success'], fn($l) => stripos($l, 'settlement_records') !== false || stripos($l, 'created') !== false)) > 0,
        'Approval chain created' => count(array_filter($categorized['success'], fn($l) => stripos($l, 'approval') !== false)) > 0,
        'Email sent' => count(array_filter($categorized['success'], fn($l) => stripos($l, 'email') !== false || stripos($l, 'send') !== false)) > 0,
        'Process completed' => !empty($categorized['end']),
    ];
    
    foreach($flow_analysis as $step => $completed) {
        $status = $completed ? '✓ DONE' : '✗ PENDING/FAILED';
        $color = $completed ? '#6a9955' : '#f48771';
        echo '<div style="padding: 10px; margin: 10px 0; border-left: 4px solid ' . $color . '; background: #2a2a2a;">';
        echo '<span style="color: ' . $color . ';">' . $status . '</span> ' . htmlspecialchars($step);
        echo '</div>';
    }
    
    // Check for errors
    if(!empty($categorized['error'])) {
        echo '<div style="padding: 15px; margin-top: 20px; background: #2a1a1a; border: 1px solid #f48771; border-radius: 4px;">';
        echo '<h3 style="color: #f48771;">⚠ Errors Found</h3>';
        echo '<p>The process encountered errors. Review these messages:</p>';
        echo '<div class="log-container">';
        foreach($categorized['error'] as $error) {
            echo '<div class="log-line error">' . htmlspecialchars($error) . '</div>';
        }
        echo '</div>';
        echo '</div>';
    }
    
    ?>
    
    <hr style="border-color: #444; margin: 30px 0;">
    
    <h2>💡 Troubleshooting Guide</h2>
    
    <div style="background: #2d2d30; padding: 15px; border-radius: 4px; margin-bottom: 15px;">
        <strong style="color: #dcdcaa;">If you see errors above:</strong>
        <ol style="color: #9cdcfe;">
            <li><strong>Settlement creation failed:</strong> Check database permissions and settlement_records table</li>
            <li><strong>Approval chain failed:</strong> Check app_settings approval_chain_settlement configuration</li>
            <li><strong>Email failed:</strong> Check SMTP settings in app_settings table</li>
            <li><strong>No logs at all:</strong> Enable error logging in php.ini</li>
        </ol>
    </div>
    
    <div style="background: #2d2d30; padding: 15px; border-radius: 4px;">
        <strong style="color: #dcdcaa;">Quick checks:</strong>
        <ul style="color: #9cdcfe;">
            <li>Visit: <code style="background: #1e1e1e; padding: 2px 5px;">check_eos_workflow.php</code> for endpoint verification</li>
            <li>Create a new EOS and watch this log in real-time</li>
            <li>Search log for "EOS SETTLEMENT CREATION" to find the exact issue</li>
            <li>Check email spam/junk folder if email shows as sent</li>
        </ul>
    </div>

</div>

<script>
function showLogs(type) {
    const logs = document.querySelectorAll('.log-line');
    const buttons = document.querySelectorAll('.filter-btn');
    
    // Update active button
    buttons.forEach(btn => btn.classList.remove('active'));
    event.target.classList.add('active');
    
    // Filter logs
    logs.forEach(log => {
        if(type === 'all') {
            log.style.display = 'block';
        } else {
            log.style.display = log.dataset.type === type ? 'block' : 'none';
        }
    });
}

function copyToClipboard() {
    const logContainer = document.getElementById('logContainer');
    const text = logContainer.innerText;
    
    navigator.clipboard.writeText(text).then(() => {
        alert('Logs copied to clipboard!');
    }).catch(() => {
        alert('Failed to copy logs');
    });
}
</script>

</body>
</html>
