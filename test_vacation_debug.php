<?php
// Debug script to test vacation submission
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/includes/db.php';

echo "<h2>Testing Vacation Submission</h2>";

// Simulate the data
$test_data = [
    'emp_id' => '5426',
    'vac_type' => 'Fly',
    'fly_type' => 'annual',
    'replacement_per' => '5422',
    'start_date' => '2025-12-01',
    'end_date' => '2025-12-10',
    'departure_date' => '2025-12-02',
    'arrival_date' => '2025-12-09',
    'remarks' => 'Test remarks',
    'vacation_salary_type' => 'payroll',
    'first_approver_id' => '3013'
];

echo "<h3>Test Data:</h3>";
echo "<pre>" . print_r($test_data, true) . "</pre>";

// Check if columns exist
$columns_check = mysqli_query($conDB, "SHOW COLUMNS FROM emp_vacation LIKE 'departure_date'");
if ($columns_check && mysqli_num_rows($columns_check) > 0) {
    echo "<p style='color:green'>✓ departure_date column EXISTS</p>";
} else {
    echo "<p style='color:red'>✗ departure_date column MISSING</p>";
}

$columns_check2 = mysqli_query($conDB, "SHOW COLUMNS FROM emp_vacation LIKE 'arrival_date'");
if ($columns_check2 && mysqli_num_rows($columns_check2) > 0) {
    echo "<p style='color:green'>✓ arrival_date column EXISTS</p>";
} else {
    echo "<p style='color:red'>✗ arrival_date column MISSING</p>";
}

// Try the INSERT
$sql = "INSERT INTO `emp_vacation` 
            (`emp_id`, `vac_type`, `fly_type`, `replacement_person`, `start_date`, `return_date`, `departure_date`, `arrival_date`, `vacdays`, `remarks`, `vacation_salary_type`, `request_inv_no`, `current_status`, `current_approval_level`) 
        VALUES 
            (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending_approval', 1)";

echo "<h3>SQL Statement:</h3>";
echo "<pre>" . htmlspecialchars($sql) . "</pre>";

$stmt = mysqli_prepare($conDB, $sql);
if (!$stmt) {
    echo "<p style='color:red'>✗ PREPARE FAILED: " . mysqli_error($conDB) . "</p>";
    exit;
} else {
    echo "<p style='color:green'>✓ Prepare successful</p>";
}

$emp_id = $test_data['emp_id'];
$vac_type = $test_data['vac_type'];
$fly_type = $test_data['fly_type'];
$replacement_per = $test_data['replacement_per'];
$start_date = $test_data['start_date'];
$end_date = $test_data['end_date'];
$departure_date = $test_data['departure_date'];
$arrival_date = $test_data['arrival_date'];
$vacdays = 10;
$remarks = $test_data['remarks'];
$vacation_salary_type = $test_data['vacation_salary_type'];
$request_inv_no = 'TEST-' . time();

mysqli_stmt_bind_param($stmt, "ssssssssisss", 
    $emp_id, 
    $vac_type, 
    $fly_type, 
    $replacement_per,
    $start_date, 
    $end_date,
    $departure_date,
    $arrival_date,
    $vacdays,
    $remarks,
    $vacation_salary_type,
    $request_inv_no
);

if (mysqli_stmt_execute($stmt)) {
    echo "<p style='color:green'>✓ INSERT successful! ID: " . mysqli_insert_id($conDB) . "</p>";
} else {
    echo "<p style='color:red'>✗ EXECUTE FAILED: " . mysqli_stmt_error($stmt) . "</p>";
}

mysqli_stmt_close($stmt);
?>
