<?php
require_once __DIR__ . '/includes/db.php';

echo "=== EMPLOYEE 5127 STATUS ===\n";

// Check employee fly status
$result = mysqli_query($conDB, "SELECT emp_id, name, fly FROM employees WHERE emp_id = 5127");
if ($row = mysqli_fetch_assoc($result)) {
    echo "Employee ID: " . $row['emp_id'] . "\n";
    echo "Name: " . $row['name'] . "\n";
    echo "Fly Status: " . $row['fly'] . "\n\n";
} else {
    echo "Employee not found\n\n";
}

// Check recent vacation records
echo "=== RECENT VACATION RECORDS ===\n";
$result = mysqli_query($conDB, "SELECT id, emp_id, request_inv_no, start_date, return_date, current_status, vac_type 
                                FROM emp_vacation 
                                WHERE emp_id = 5127 
                                ORDER BY id DESC 
                                LIMIT 3");
while ($row = mysqli_fetch_assoc($result)) {
    echo "ID: " . $row['id'] . "\n";
    echo "Request: " . $row['request_inv_no'] . "\n";
    echo "Type: " . $row['vac_type'] . "\n";
    echo "Dates: " . $row['start_date'] . " to " . $row['return_date'] . "\n";
    echo "Status: " . $row['current_status'] . "\n";
    echo "---\n";
}

// Check if there's any active approved LEAVE REQUEST (vac_type != 'Fly') covering today
echo "\n=== ACTIVE LEAVE REQUESTS TODAY (excluding vac_type='Fly') ===\n";
$today = date('Y-m-d');
$result = mysqli_query($conDB, "SELECT id, request_inv_no, start_date, return_date, vac_type 
                                FROM emp_vacation 
                                WHERE emp_id = 5127 
                                AND current_status = 'approved' 
                                AND vac_type != 'Fly'
                                AND start_date <= '$today' 
                                AND return_date >= '$today'");
$count = mysqli_num_rows($result);
if ($count > 0) {
    echo "Found $count active leave request(s):\n";
    while ($row = mysqli_fetch_assoc($result)) {
        echo "  " . $row['vac_type'] . ": " . $row['start_date'] . " to " . $row['return_date'] . "\n";
    }
} else {
    echo "No active leave requests found for today ($today)\n";
}

// Check if there's any past LEAVE REQUEST (vac_type != 'Fly')
echo "\n=== PAST LEAVE REQUESTS (vac_type != 'Fly', ended before today) ===\n";
$result = mysqli_query($conDB, "SELECT id, request_inv_no, start_date, return_date, vac_type 
                                FROM emp_vacation 
                                WHERE emp_id = 5127 
                                AND current_status = 'approved'
                                AND vac_type != 'Fly'
                                AND start_date <= '$today' 
                                AND return_date < '$today'");
$count = mysqli_num_rows($result);
if ($count > 0) {
    echo "Found $count past leave request(s):\n";
    while ($row = mysqli_fetch_assoc($result)) {
        echo "  " . $row['vac_type'] . ": " . $row['start_date'] . " to " . $row['return_date'] . "\n";
    }
    echo "\nEmployee fly SHOULD be reset to 0\n";
} else {
    echo "No past leave requests found\n";
}

// Now run the UPDATED query that only handles Leave Requests
echo "\n=== RUNNING FLY STATUS UPDATE (Leave Requests Only) ===\n";
$sql = "UPDATE employees e 
        SET e.fly = 0 
        WHERE e.fly = 1 
          AND NOT EXISTS (
              SELECT 1 FROM emp_vacation v 
              WHERE v.emp_id = e.emp_id 
              AND v.current_status = 'approved' 
              AND v.vac_type != 'Fly'
              AND v.start_date <= CURDATE() 
              AND v.return_date >= CURDATE()
          )
          AND EXISTS (
              SELECT 1 FROM emp_vacation v2 
              WHERE v2.emp_id = e.emp_id 
              AND v2.current_status = 'approved' 
              AND v2.vac_type != 'Fly'
              AND v2.start_date <= CURDATE() 
              AND v2.return_date < CURDATE()
          )";
$result = mysqli_query($conDB, $sql);
if ($result) {
    $affected = mysqli_affected_rows($conDB);
    echo "Update successful: $affected row(s) affected\n";
} else {
    echo "Update failed: " . mysqli_error($conDB) . "\n";
}

// Check employee fly status after update
echo "\n=== EMPLOYEE 5127 STATUS AFTER UPDATE ===\n";
$result = mysqli_query($conDB, "SELECT emp_id, name, fly FROM employees WHERE emp_id = 5127");
if ($row = mysqli_fetch_assoc($result)) {
    echo "Employee ID: " . $row['emp_id'] . "\n";
    echo "Name: " . $row['name'] . "\n";
    echo "Fly Status: " . $row['fly'] . "\n";
}
?>
