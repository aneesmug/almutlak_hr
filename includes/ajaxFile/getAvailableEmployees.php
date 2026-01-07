<?php

require_once __DIR__ . '/../../includes/session_check.php';

// Get employees who do NOT exist in admin_login table
$sql = "SELECT e.emp_id, e.name, e.emptype, d.dep_nme as dept_name 
        FROM employees e 
        LEFT JOIN department d ON e.dept = d.id 
        LEFT JOIN admin_login al ON e.emp_id = al.emp_id 
        WHERE e.status = 1 
        AND al.id IS NULL 
        ORDER BY d.dep_nme ASC, e.name ASC";

$result = mysqli_query($conDB, $sql);

if (!$result) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Database query failed: ' . mysqli_error($conDB),
        'data' => []
    ]);
    exit;
}

$employees = [];
while ($row = mysqli_fetch_assoc($result)) {
    $employees[] = [
        'emp_id' => $row['emp_id'],
        'name' => $row['name'],
        'emptype' => $row['emptype'],
        'dept_name' => $row['dept_name'] ?? 'N/A',
        'display_text' => $row['name'] . ' (' . ($row['dept_name'] ?? 'N/A') . ') - ' . $row['emptype']
    ];
}

echo json_encode([
    'status' => 'success',
    'data' => $employees,
    'count' => count($employees)
]);
