<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../session_check.php';

header('Content-Type: application/json');

// Check authorization
$can_see_reports_page = ['Administrator', 'GM', 'Auditor', 'HR_Senior_BP', 'HR_Operations', 'HR_Supervisor', 'Finance_Officer', 'DPT_Manager', 'HR_Manager', 'Finance_Manager'];

if (!in_array($user_role, $can_see_reports_page) && !$is_system_admin) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Get table names from request (support both single table and multiple tables)
$input = isset($_POST['table']) ? $_POST['table'] : (isset($_POST['tables']) ? $_POST['tables'] : '');

if (empty($input)) {
    echo json_encode(['success' => false, 'message' => 'Table name(s) required']);
    exit();
}

// Handle multiple tables (JSON array or single string)
$tableNames = [];
if (is_string($input)) {
    // Try to decode as JSON array
    $decoded = json_decode($input, true);
    if (is_array($decoded)) {
        $tableNames = $decoded;
    } else {
        // Single table name
        $tableNames = [$input];
    }
}

if (empty($tableNames)) {
    echo json_encode(['success' => false, 'message' => 'No valid tables provided']);
    exit();
}

// Validate and collect columns from all tables
$allColumns = [];
$validatedTables = [];

foreach ($tableNames as $tableName) {
    if (empty($tableName)) continue;
    
    // Validate table name (check if table exists)
    $validateQuery = mysqli_query($conDB, "SHOW TABLES LIKE '" . mysqli_real_escape_string($conDB, $tableName) . "'");
    if (mysqli_num_rows($validateQuery) === 0) {
        continue; // Skip invalid tables
    }
    
    $validatedTables[] = $tableName;
    
    // Get columns from the table
    $columnsQuery = mysqli_query($conDB, "SHOW COLUMNS FROM `" . mysqli_real_escape_string($conDB, $tableName) . "`");
    
    if (!$columnsQuery) {
        continue;
    }
    
    while ($row = mysqli_fetch_assoc($columnsQuery)) {
        $columnName = $row['Field'];
        
        // Skip ID columns (id, primary keys, foreign keys ending with _id)
        if ($columnName === 'id' || strtolower($columnName) === 'id') {
            continue;
        }
        
        // Prefix column with table name if multiple tables
        if (count($tableNames) > 1) {
            $columnName = $tableName . '.' . $columnName;
        }
        
        // Avoid duplicates
        if (!in_array($columnName, $allColumns)) {
            $allColumns[] = $columnName;
        }
    }
}

if (empty($allColumns)) {
    echo json_encode(['success' => false, 'message' => 'No columns found in selected tables']);
    exit();
}

echo json_encode([
    'success' => true,
    'columns' => $allColumns,
    'tables' => $validatedTables
]);
?>
