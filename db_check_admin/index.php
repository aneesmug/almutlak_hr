<?php
/**
 * Database Health Check - Dynamic Token Access
 * 
 * Flow:
 * 1. User visits without token -> shown "Generate Token" form
 * 2. Clicks "Generate Token" -> token sent to email
 * 3. User enters token from email -> token verified and dashboard shown
 * 
 * Last Updated: April 8, 2026
 */

// Load configuration and token manager
require_once __DIR__ . '/token_config.php';
require_once __DIR__ . '/TokenManager.php';

// Initialize token manager
$tokenManager = new TokenManager();

// Handle form submissions
$action = $_REQUEST['action'] ?? '';
$providedToken = $_REQUEST['checkpoint'] ?? $_POST['token'] ?? '';
$tokenError = '';
$tokenSuccess = '';
$showGenerateForm = true;
$showDashboard = false;

// STEP 1: Generate new token and send email
if ($action === 'generate_token') {
    try {
        // Increase timeout for email operation
        @set_time_limit(30);
        
        // Generate token
        $newToken = $tokenManager->generateToken();
        
        // Send email with error handling
        $emailSender = new EmailSender(ADMIN_EMAIL, SENDER_EMAIL);
        
        // Capture any output or errors
        ob_start();
        $emailResult = $emailSender->sendTokenEmail($newToken);
        $emailOutput = ob_get_clean();
        
        logTokenRequest('Token Generated', $newToken, ADMIN_EMAIL, 'SUCCESS');
        
        if ($emailResult) {
            $tokenSuccess = 'Access token has been sent to ' . ADMIN_EMAIL . '. Check your email for the token. Tokens are valid for 30 minutes.';
        } else {
            $tokenError = 'Token was generated but email delivery failed. Please try again or contact system administrator.';
            if (!empty($emailOutput)) {
                $tokenError .= ' [Debug: ' . htmlspecialchars(substr($emailOutput, 0, 100)) . ']';
            }
            logTokenRequest('Token Email Failed', $newToken, ADMIN_EMAIL, 'ERROR');
        }
    } catch (Exception $e) {
        $tokenError = 'Failed to generate token: ' . htmlspecialchars($e->getMessage());
        @error_log('Token Generation Error: ' . $e->getMessage() . "\n TRACE: " . $e->getTraceAsString());
        logTokenRequest('Token Generation Error', '', ADMIN_EMAIL, 'ERROR');
    }
}

// STEP 2: Verify provided token
if (!empty($providedToken)) {
    if ($tokenManager->verifyToken($providedToken)) {
        $showGenerateForm = false;
        $showDashboard = true;
        logTokenRequest('Token Verified - Dashboard Access', $providedToken, ADMIN_EMAIL, 'SUCCESS');
        $tokenManager->markTokenAsUsed($providedToken);
    } else {
        $tokenError = 'Invalid or expired token. Please request a new token.';
        logTokenRequest('Invalid Token Attempt', $providedToken, ADMIN_EMAIL, 'WARNING');
    }
}

// If we have a valid token in URL and need to show dashboard, continue to load dashboard
if ($showDashboard) {
    // Now load the database connection and show dashboard
    require_once '../includes/db.php';
    global $conDB, $pdo;
    
    $healthData = [];
    $errors = [];
    
    /**
     * Helper function to safely execute queries
     */
    function safeQuery($query, $type = 'mysqli') {
        global $conDB, $pdo, $errors;
        try {
            if ($type === 'pdo' && $pdo) {
                return $pdo->query($query);
            } else if ($conDB) {
                return mysqli_query($conDB, $query);
            }
            return null;
        } catch (Exception $e) {
            $errors[] = "Query Error: " . $e->getMessage();
            return null;
        }
    }
    
    /**
     * Get all databases and tables
     */
    function getAllTables() {
        global $conDB;
        $tables = [];
        
        // Get current database
        $dbResult = mysqli_query($conDB, "SELECT DATABASE() as db");
        $dbRow = mysqli_fetch_assoc($dbResult);
        $currentDb = $dbRow['db'] ?? '';
        
        if ($currentDb) {
            $result = mysqli_query($conDB, "SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = '$currentDb'");
            while ($row = mysqli_fetch_assoc($result)) {
                $tables[] = $row['TABLE_NAME'];
            }
        }
        
        return $currentDb;
    }
    
    /**
     * Render HTML table from result set
     */
    function renderResultTable($result) {
        if (!$result) {
            return '<p class="text-muted">No results returned.</p>';
        }
        
        $html = '<table class="table table-sm table-striped table-bordered">';
        $html .= '<thead class="table-dark"><tr>';
        
        // Get column names
        $fields = [];
        if (method_exists($result, 'fetch_field')) {
            // mysqli result
            while ($field = $result->fetch_field()) {
                $fields[] = $field->name;
                $html .= '<th>' . htmlspecialchars($field->name) . '</th>';
            }
        } else {
            return '<p class="text-muted">Unable to process result set.</p>';
        }
        
        $html .= '</tr></thead><tbody>';
        
        // Get rows
        $rowCount = 0;
        if (method_exists($result, 'fetch_assoc')) {
            while ($row = $result->fetch_assoc()) {
                $html .= '<tr>';
                foreach ($fields as $field) {
                    $value = $row[$field];
                    // Format large numbers
                    if (is_numeric($value) && $value > 1000000) {
                        $value = number_format($value);
                    }
                    $html .= '<td>' . htmlspecialchars((string)$value) . '</td>';
                }
                $html .= '</tr>';
                $rowCount++;
            }
        }
        
        $html .= '</tbody></table>';
        
        if ($rowCount === 0) {
            return '<p class="text-muted">No data found.</p>';
        }
        
        return $html;
    }
    
    // Get current database
    $currentDb = getAllTables();
    
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Health Check</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .token-form-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            padding: 40px;
            max-width: 500px;
            width: 100%;
        }
        
        .token-form-container h2 {
            color: #333;
            margin-bottom: 10px;
            font-weight: 600;
        }
        
        .token-form-container p {
            color: #666;
            margin-bottom: 30px;
            font-size: 14px;
        }
        
        .alert {
            border-radius: 6px;
            margin-bottom: 20px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .btn-generate {
            width: 100%;
            padding: 12px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s;
            margin-top: 10px;
        }
        
        .btn-generate:hover {
            background: #5568d3;
        }
        
        .btn-verify {
            width: 100%;
            padding: 12px;
            background: #28a745;
            color: white;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s;
            margin-bottom: 10px;
        }
        
        .btn-verify:hover {
            background: #218838;
        }
        
        .info-box {
            background: #f8f9fa;
            border-left: 4px solid #667eea;
            padding: 15px;
            border-radius: 6px;
            margin: 20px 0;
            font-size: 14px;
            color: #555;
        }
        
        .step-number {
            display: inline-block;
            width: 28px;
            height: 28px;
            background: #667eea;
            color: white;
            border-radius: 50%;
            text-align: center;
            line-height: 28px;
            font-weight: bold;
            margin-right: 10px;
        }
        
        .dashboard-wrapper {
            background: #f5f5f5;
            padding: 1rem;
        }
        
        .health-section {
            margin-top: 2rem;
            padding: 1.5rem;
            background: #f8f9fa;
            border-left: 5px solid #007bff;
            border-radius: 4px;
        }
        
        .health-section h3 {
            color: #333;
            margin-bottom: 1rem;
            font-weight: 600;
        }
        
        .table-sm {
            font-size: 0.875rem;
        }
        
        .security-banner {
            background: #d4edda;
            border-left: 5px solid #28a745;
            padding: 1rem;
            margin-bottom: 2rem;
            border-radius: 4px;
        }
        
        .security-banner strong {
            color: #155724;
        }
        
        .code-block {
            background: #f5f5f5;
            border: 1px solid #ddd;
            padding: 1rem;
            border-radius: 4px;
            overflow-x: auto;
            font-family: monospace;
            font-size: 0.85rem;
            line-height: 1.4;
        }
    </style>
</head>
<body>

<?php if ($showDashboard): ?>
    <!-- DASHBOARD VIEW -->
    <div class="dashboard-wrapper" style="width: 100%;">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <!-- SECURITY BANNER -->
                    <div class="security-banner">
                        <div>
                            <strong>✅ Access Verified - Database Health Check Active</strong><br>
                            <small>Token authenticated. You have access to the health check dashboard for 30 minutes.</small>
                        </div>
                    </div>
                    
                    <h1 style="margin-bottom: 2rem; color: white;">
                        <span style="color: #007bff;">Database Health Check Dashboard</span>
                        <small class="text-muted" style="font-size: 0.7em;">Last refresh: <?php echo date('Y-m-d H:i:s'); ?></small>
                    </h1>
                    
                    <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger" role="alert">
                        <strong>Errors Encountered:</strong>
                        <ul style="margin: 0.5rem 0 0 1.5rem;">
                            <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endif; ?>
                    
                    <!-- DATABASE INFO SECTION -->
                    <div class="health-section">
                        <h3>📊 Database Information</h3>
                        <p><strong>Database Name:</strong> <code><?php echo htmlspecialchars($currentDb); ?></code></p>
                        <?php
                            $versionResult = mysqli_query($conDB, "SELECT VERSION() as version");
                            $versionRow = mysqli_fetch_assoc($versionResult);
                            echo '<p><strong>MySQL Version:</strong> <code>' . htmlspecialchars($versionRow['version']) . '</code></p>';
                        ?>
                    </div>
                    
                    <!-- ACTIVE CONNECTIONS SECTION -->
                    <div class="health-section">
                        <h3>🔗 Active Connections & Locks</h3>
                        <p style="color: #666; margin-bottom: 1rem;">Shows all active database connections.</p>
                        <button class="btn btn-sm btn-primary" onclick="copyToClipboard(`SHOW FULL PROCESSLIST;`)">Copy SQL</button>
                        <hr>
                        <?php
                            $processResult = mysqli_query($conDB, "SHOW FULL PROCESSLIST");
                            echo renderResultTable($processResult);
                        ?>
                    </div>
                    
                    <!-- TABLE OVERVIEW SECTION -->
                    <div class="health-section">
                        <h3>📋 All Tables Overview</h3>
                        <p style="color: #666; margin-bottom: 1rem;">Complete status of all tables including row count and size.</p>
                        <button class="btn btn-sm btn-primary" onclick="copyToClipboard(`SELECT TABLE_NAME, TABLE_ROWS, ROUND(((data_length + index_length) / 1024 / 1024), 2) AS Size_MB FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() ORDER BY TABLE_ROWS DESC;`)">Copy SQL</button>
                        <hr>
                        <?php
                            $tablesQuery = "
                                SELECT 
                                    TABLE_NAME,
                                    TABLE_ROWS,
                                    ROUND(((data_length + index_length) / 1024 / 1024), 2) AS 'Size_MB',
                                    ROUND((data_length / 1024 / 1024), 2) AS 'Data_MB',
                                    ROUND((index_length / 1024 / 1024), 2) AS 'Index_MB'
                                FROM INFORMATION_SCHEMA.TABLES
                                WHERE TABLE_SCHEMA = DATABASE()
                                ORDER BY TABLE_ROWS DESC
                            ";
                            $tablesResult = mysqli_query($conDB, $tablesQuery);
                            echo renderResultTable($tablesResult);
                        ?>
                    </div>
                    
                    <!-- INDEX STATUS SECTION -->
                    <div class="health-section">
                        <h3>🔍 Index Information</h3>
                        <p style="color: #666; margin-bottom: 1rem;">All indexes across all tables.</p>
                        <button class="btn btn-sm btn-primary" onclick="copyToClipboard(`SELECT TABLE_NAME, INDEX_NAME, COLUMN_NAME FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() ORDER BY TABLE_NAME;`)">Copy SQL</button>
                        <hr>
                        <?php
                            $indexQuery = "
                                SELECT 
                                    TABLE_NAME,
                                    INDEX_NAME,
                                    COLUMN_NAME,
                                    SEQ_IN_INDEX
                                FROM INFORMATION_SCHEMA.STATISTICS
                                WHERE TABLE_SCHEMA = DATABASE()
                                ORDER BY TABLE_NAME, INDEX_NAME, SEQ_IN_INDEX
                            ";
                            $indexResult = mysqli_query($conDB, $indexQuery);
                            echo renderResultTable($indexResult);
                        ?>
                    </div>
                    
                </div>
            </div>
        </div>
    </div>
    
    <script>
    function copyToClipboard(text) {
        text = text.replace(/^\s+|\s+$/g, '');
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(text).then(() => {
                alert('SQL copied to clipboard!');
            }).catch(() => {
                fallbackCopyToClipboard(text);
            });
        } else {
            fallbackCopyToClipboard(text);
        }
    }
    
    function fallbackCopyToClipboard(text) {
        const textarea = document.createElement('textarea');
        textarea.value = text;
        textarea.style.position = 'fixed';
        textarea.style.opacity = '0';
        document.body.appendChild(textarea);
        textarea.select();
        try {
            document.execCommand('copy');
            alert('SQL copied to clipboard!');
        } catch (err) {
            alert('Failed to copy');
        }
        document.body.removeChild(textarea);
    }
    
    // Auto-refresh every 5 minutes
    setTimeout(function() {
        location.reload();
    }, 5 * 60 * 1000);
    </script>

<?php else: ?>
    <!-- TOKEN REQUEST FORM -->
    <div class="token-form-container">
        <div style="text-align: center; margin-bottom: 30px;">
            <div style="font-size: 48px; margin-bottom: 15px;">🔒</div>
            <h2>Database Health Check</h2>
            <p>Secure access to database monitoring dashboard</p>
        </div>
        
        <?php if (!empty($tokenError)): ?>
        <div class="alert alert-danger">
            <strong>Error:</strong> <?php echo htmlspecialchars($tokenError); ?>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($tokenSuccess)): ?>
        <div class="alert alert-success">
            <strong>Success!</strong> <?php echo htmlspecialchars($tokenSuccess); ?>
        </div>
        <?php endif; ?>
        
        <div class="info-box">
            <div><span class="step-number">1</span> <strong>Generate Token</strong></div>
            <div style="margin-left: 38px; margin-top: 8px; color: #666; font-size: 13px;">
                Click the button below to generate a unique access token and send it to your email.
            </div>
        </div>
        
        <form method="POST" style="margin-bottom: 20px;">
            <input type="hidden" name="action" value="generate_token">
            <button type="submit" class="btn-generate">📧 Generate & Send Token</button>
        </form>
        
        <hr style="margin: 25px 0;">
        
        <div class="info-box">
            <div><span class="step-number">2</span> <strong>Enter Token</strong></div>
            <div style="margin-left: 38px; margin-top: 8px; color: #666; font-size: 13px;">
                Paste the token received in your email to access the dashboard.
            </div>
        </div>
        
        <form method="POST">
            <div class="form-group">
                <label for="token">Access Token (from email)</label>
                <input type="text" id="token" name="token" placeholder="Paste your token here..." required>
            </div>
            <button type="submit" class="btn-verify">✓ Verify & Access Dashboard</button>
        </form>
        
        <div class="info-box" style="margin-top: 20px; border-left-color: #ffc107; background: #fffacd;">
            <strong>⏱️ Token Duration:</strong> Tokens are valid for 30 minutes from generation. 
            Generate a new token if your current one expires.
        </div>
        
        <div class="info-box" style="margin-top: 20px; border-left-color: #17a2b8; background: #d1ecf1;">
            <strong>🔧 Troubleshooting:</strong> If you don't receive the token email:<br>
            1. Check <a href="test_mail.php" style="color: #17a2b8; font-weight: bold;">PHP mail() Function Test</a> (simple test)<br>
            2. Check <a href="test_smtp.php" style="color: #17a2b8; font-weight: bold;">SMTP Connection Status</a> (advanced diagnostics)
        </div>
    </div>

<?php endif; ?>
