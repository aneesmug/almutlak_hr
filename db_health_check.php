<?php
/**
 * Database Health Check Dashboard
 * 
 * This page provides comprehensive diagnostics for all database tables.
 * It checks:
 * - Active database connections and locks
 * - All tables in the database with their status
 * - Index information and efficiency
 * - Table sizes and row counts
 * - Slow or locked queries
 * - Database maintenance needs
 * 
 * Access: Admins only (verify through session check)
 * Last Updated: April 8, 2026
 */

// Start output buffering to prevent accidental output
ob_start();

// Include required files
require_once 'includes/session_check.php';
require_once 'includes/header.php';

// Verify admin access
if (!isset($_SESSION['admin_user_id']) && !isset($_SESSION['user_id'])) {
    die('<div class="alert alert-danger">Unauthorized Access. Admin login required.</div>');
}

// Try to verify admin role - adjust based on your role system
$isAdmin = $_SESSION['user_type'] === 'admin' || $_SESSION['user_type'] === 'system_admin' || isset($_SESSION['admin_user_id']);
if (!$isAdmin) {
    die('<div class="alert alert-danger">Access Denied. Administrator privileges required.</div>');
}

// Get database connection
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
        // Assume it's a result set
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

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Health Check</title>
    <style>
        .health-section {
            margin-top: 2rem;
            padding: 1.5rem;
            background: #f8f9fa;
            border-left: 5px solid #007bff;
            border-radius: 4px;
        }
        
        .health-section.warning {
            background: #fff3cd;
            border-left-color: #ffc107;
        }
        
        .health-section.danger {
            background: #f8d7da;
            border-left-color: #dc3545;
        }
        
        .health-section.success {
            background: #d4edda;
            border-left-color: #28a745;
        }
        
        .health-section h3 {
            color: #333;
            margin-bottom: 1rem;
            font-weight: 600;
        }
        
        .stat-box {
            display: inline-block;
            padding: 1rem;
            margin: 0.5rem;
            background: white;
            border-radius: 4px;
            border: 1px solid #ddd;
            text-align: center;
            min-width: 150px;
        }
        
        .stat-label {
            font-size: 0.85rem;
            color: #666;
            text-transform: uppercase;
        }
        
        .stat-value {
            font-size: 1.5rem;
            font-weight: bold;
            color: #007bff;
        }
        
        .table-sm {
            font-size: 0.875rem;
        }
        
        .table-sm td {
            padding: 0.4rem;
        }
        
        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
        }
        
        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 3px;
            font-size: 0.75rem;
            font-weight: bold;
        }
        
        .status-ok { background: #d4edda; color: #155724; }
        .status-warning { background: #fff3cd; color: #856404; }
        .status-error { background: #f8d7da; color: #721c24; }
        
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
        
        .refresh-btn {
            float: right;
            margin-top: -2rem;
        }
    </style>
</head>
<body style="background: #fff; padding: 1rem;">

<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <h1 style="margin-bottom: 2rem;">
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
                <p style="color: #666; margin-bottom: 1rem;">
                    Shows all active database connections. Look for connections in "Locked" or "Waiting" state.
                </p>
                <button class="btn btn-sm btn-primary" onclick="copyToClipboard(`SHOW FULL PROCESSLIST;`)">
                    Copy SQL
                </button>
                <hr>
                <?php
                    $processResult = mysqli_query($conDB, "SHOW FULL PROCESSLIST");
                    echo renderResultTable($processResult);
                ?>
            </div>
            
            <!-- TABLE OVERVIEW SECTION -->
            <div class="health-section">
                <h3>📋 All Tables Overview</h3>
                <p style="color: #666; margin-bottom: 1rem;">
                    Complete status of all tables including row count, data size, and index status.
                </p>
                <button class="btn btn-sm btn-primary" onclick="copyToClipboard(`SELECT * FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() ORDER BY TABLE_ROWS DESC;`)">
                    Copy SQL
                </button>
                <hr>
                <?php
                    $tablesQuery = "
                        SELECT 
                            TABLE_NAME,
                            TABLE_ROWS,
                            ROUND(((data_length + index_length) / 1024 / 1024), 2) AS 'Size_MB',
                            ROUND((data_length / 1024 / 1024), 2) AS 'Data_MB',
                            ROUND((index_length / 1024 / 1024), 2) AS 'Index_MB',
                            TABLE_COLLATION,
                            UPDATE_TIME,
                            CHECK_TIME
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
                <h3>🔍 Index Information (All Tables)</h3>
                <p style="color: #666; margin-bottom: 1rem;">
                    Shows all indexes across all tables. Look for tables with missing indexes on frequently queried columns.
                </p>
                <button class="btn btn-sm btn-primary" onclick="copyToClipboard(`SELECT TABLE_NAME, INDEX_NAME, COLUMN_NAME, SEQ_IN_INDEX, NON_UNIQUE FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() ORDER BY TABLE_NAME, INDEX_NAME;`)">
                    Copy SQL
                </button>
                <hr>
                <?php
                    $indexQuery = "
                        SELECT 
                            TABLE_NAME,
                            INDEX_NAME,
                            COLUMN_NAME,
                            SEQ_IN_INDEX,
                            NON_UNIQUE,
                            INDEX_TYPE
                        FROM INFORMATION_SCHEMA.STATISTICS
                        WHERE TABLE_SCHEMA = DATABASE()
                        ORDER BY TABLE_NAME, INDEX_NAME, SEQ_IN_INDEX
                    ";
                    $indexResult = mysqli_query($conDB, $indexQuery);
                    echo renderResultTable($indexResult);
                ?>
            </div>
            
            <!-- TABLE SIZES & STORAGE SECTION -->
            <div class="health-section">
                <h3>💾 Storage Analysis</h3>
                <p style="color: #666; margin-bottom: 1rem;">
                    Total database size and breakdown by data vs. index storage.
                </p>
                <button class="btn btn-sm btn-primary" onclick="copyToClipboard(`SELECT ROUND(SUM(((data_length + index_length) / 1024 / 1024)), 2) AS total_size_mb, ROUND(SUM(data_length / 1024 / 1024), 2) as data_mb, ROUND(SUM(index_length / 1024 / 1024), 2) as index_mb FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE();`)">
                    Copy SQL
                </button>
                <hr>
                <?php
                    $sizeQuery = "
                        SELECT 
                            ROUND(SUM(((data_length + index_length) / 1024 / 1024)), 2) AS 'Total_Size_MB',
                            ROUND(SUM(data_length / 1024 / 1024), 2) AS 'Data_MB',
                            ROUND(SUM(index_length / 1024 / 1024), 2) AS 'Index_MB',
                            COUNT(*) AS 'Table_Count'
                        FROM INFORMATION_SCHEMA.TABLES
                        WHERE TABLE_SCHEMA = DATABASE()
                    ";
                    $sizeResult = mysqli_query($conDB, $sizeQuery);
                    echo renderResultTable($sizeResult);
                ?>
            </div>
            
            <!-- KEY METRICS SECTION -->
            <div class="health-section">
                <h3>⚡ Key Performance Metrics</h3>
                <button class="btn btn-sm btn-primary" onclick="copyToClipboard(`SHOW STATUS LIKE 'Threads%'; SHOW STATUS LIKE 'Questions'; SHOW STATUS LIKE 'Slow_queries'; SHOW STATUS LIKE '%lock%';`)">
                    Copy SQL
                </button>
                <hr>
                <?php
                    $statusResults = [
                        'SHOW STATUS LIKE "Threads%"',
                        'SHOW STATUS LIKE "Questions"',
                        'SHOW STATUS LIKE "Slow_queries"',
                        'SHOW STATUS LIKE "%lock%"'
                    ];
                    
                    foreach ($statusResults as $statusSql) {
                        echo '<p style="margin-top: 1rem; margin-bottom: 0.5rem;"><strong>Query:</strong> <code>' . htmlspecialchars($statusSql) . '</code></p>';
                        $statusResult = mysqli_query($conDB, $statusSql);
                        echo renderResultTable($statusResult);
                    }
                ?>
            </div>
            
            <!-- TABLE HEALTH CHECK SECTION -->
            <div class="health-section">
                <h3>✅ Table Integrity Check (Sample)</h3>
                <p style="color: #666; margin-bottom: 1rem;">
                    These commands check table integrity. Run on a per-table basis to avoid excessive system load.
                </p>
                <button class="btn btn-sm btn-primary" onclick="copyToClipboard(document.getElementById('checkTableCode').textContent)">
                    Copy SQL
                </button>
                <hr>
                <div class="code-block" id="checkTableCode">-- Run these checks on individual tables:
-- Replace 'table_name' with your actual table name

-- Check table for errors
CHECK TABLE table_name;

-- Analyze table structure
ANALYZE TABLE table_name;

-- Optimize table (removes unused space)
OPTIMIZE TABLE table_name;

-- For maintaining table statistics
ANALYZE TABLE table_name;

-- Check all tables in database
-- (Execute CHECK TABLE for each table)
<?php
    $allTablesResult = mysqli_query($conDB, "SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE()");
    while ($tableRow = mysqli_fetch_assoc($allTablesResult)) {
        echo "\nCHECK TABLE " . $tableRow['TABLE_NAME'] . ";";
    }
?></div>
            </div>
            
            <!-- SLOW QUERY ANALYSIS SECTION -->
            <div class="health-section warning">
                <h3>🐢 Slow Query Log Status</h3>
                <p style="color: #666; margin-bottom: 1rem;">
                    Check if slow query logging is enabled and review slow queries.
                </p>
                <button class="btn btn-sm btn-primary" onclick="copyToClipboard(`SHOW VARIABLES LIKE 'slow_query%'; SELECT * FROM mysql.slow_log LIMIT 20;`)">
                    Copy SQL
                </button>
                <hr>
                <?php
                    echo '<p><strong>Slow Query Log Status:</strong></p>';
                    $slowLogResult = mysqli_query($conDB, "SHOW VARIABLES LIKE 'slow_query%'");
                    echo renderResultTable($slowLogResult);
                ?>
            </div>
            
            <!-- CONNECTION LIMITS SECTION -->
            <div class="health-section">
                <h3>🔐 Connection & Resource Limits</h3>
                <button class="btn btn-sm btn-primary" onclick="copyToClipboard(`SHOW VARIABLES LIKE 'max_connections'; SHOW VARIABLES LIKE 'max_allowed_packet'; SHOW VARIABLES LIKE 'innodb_buffer_pool_size'; SHOW VARIABLES LIKE 'query_cache%';`)">
                    Copy SQL
                </button>
                <hr>
                <?php
                    $limitVars = [
                        'max_connections',
                        'max_allowed_packet',
                        'innodb_buffer_pool_size',
                        'query_cache_size',
                        'tmp_table_size',
                        'max_heap_table_size'
                    ];
                    
                    foreach ($limitVars as $var) {
                        $varQuery = "SHOW VARIABLES LIKE '$var'";
                        $varResult = mysqli_query($conDB, $varQuery);
                        if ($varResult) {
                            while ($varRow = mysqli_fetch_assoc($varResult)) {
                                echo '<p><strong>' . htmlspecialchars($varRow['Variable_name']) . ':</strong> <code>' . htmlspecialchars($varRow['Value']) . '</code></p>';
                            }
                        }
                    }
                ?>
            </div>
            
            <!-- RECOMMENDATIONS SECTION -->
            <div class="health-section success">
                <h3>💡 Maintenance Recommendations</h3>
                <div style="background: white; padding: 1rem; border-radius: 4px;">
                    <h4>Regular Maintenance Tasks:</h4>
                    <ol>
                        <li><strong>Weekly:</strong> Run <code>CHECK TABLE</code> on critical tables (employees, admin_login)</li>
                        <li><strong>Monthly:</strong> Run <code>ANALYZE TABLE</code> on all tables to update statistics</li>
                        <li><strong>Monthly:</strong> Review <code>SHOW FULL PROCESSLIST</code> for locked or slow queries</li>
                        <li><strong>Quarterly:</strong> Run <code>OPTIMIZE TABLE</code> on tables with frequent INSERTs/UPDATEs/DELETEs</li>
                        <li><strong>Ongoing:</strong> Monitor index usage (unused indexes should be removed)</li>
                        <li><strong>Ongoing:</strong> Check for table corruption using <code>CHECKSUM TABLE</code></li>
                    </ol>
                    
                    <h4 style="margin-top: 1.5rem;">Critical Tables to Monitor:</h4>
                    <ul>
                        <li><code>employees</code> - Ensure index on <code>iqama</code> column exists</li>
                        <li><code>admin_login</code> - Ensure index on <code>id_iqama</code> column exists</li>
                        <li>Any table receiving high DML (INSERT/UPDATE/DELETE) activity</li>
                    </ul>
                    
                    <h4 style="margin-top: 1.5rem;">When to Take Action:</h4>
                    <ul>
                        <li>⚠️ If <code>Threads_running</code> exceeds 50: Connection pool may be exhausted</li>
                        <li>⚠️ If active locks are found: Run maintenance mode toggle to identify blocking queries</li>
                        <li>⚠️ If table size exceeds 500MB: Consider partitioning or archiving old data</li>
                        <li>⚠️ If index size exceeds data size: Review index usage and remove unused indexes</li>
                    </ul>
                </div>
            </div>
            
            <!-- REFERENCE QUERIES SECTION -->
            <div class="health-section">
                <h3>📚 Reference Queries</h3>
                <p style="color: #666; margin-bottom: 1rem;">Keep these handy for quick diagnostics:</p>
                
                <h5>Kill Long-Running Queries (use with caution):</h5>
                <div class="code-block">-- Get the query ID (replace ID with actual ID from PROCESSLIST)
KILL [CONNECTION | QUERY] <ID>;

-- Example: Kill specific query
KILL QUERY 42;</div>
                
                <h5 style="margin-top: 1rem;">Find Queries Using Specific Tables:</h5>
                <div class="code-block">-- Check which tables are in use
SELECT 
    OBJECT_SCHEMA, 
    OBJECT_NAME, 
    LOCK_TYPE 
FROM performance_schema.table_io_waits_summary_by_table 
ORDER BY SUM_TIMER_WAIT DESC;</div>
                
                <h5 style="margin-top: 1rem;">Enable/Disable Slow Query Log:</h5>
                <div class="code-block">-- Enable slow query log
SET GLOBAL slow_query_log = 'ON';

-- Set slow query threshold (in seconds)
SET GLOBAL long_query_time = 2;

-- Check current settings
SHOW VARIABLES LIKE 'long_query_time';</div>
            </div>
            
        </div>
    </div>
</div>

<script>
function copyToClipboard(text) {
    // Remove any leading/trailing whitespace and multiple spaces
    text = text.replace(/^\s+|\s+$/g, '');
    
    // Use modern clipboard API
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
        alert('Failed to copy: ' + err);
    }
    document.body.removeChild(textarea);
}

// Auto-refresh page every 5 minutes
setTimeout(function() {
    location.reload();
}, 5 * 60 * 1000);
</script>

</body>
</html>

<?php
// Clear output buffer
ob_end_flush();
?>
