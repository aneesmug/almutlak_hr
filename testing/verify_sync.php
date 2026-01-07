<?php
require_once 'includes/db.php';

// Check a few employees to verify both columns match
$employees = ['1061', '1496', '5160', '1574'];

foreach ($employees as $emp_id) {
    $query = "SELECT emp_vacation_balance.emp_id, employees.name, emp_vacation_balance.available_balance, emp_vacation_balance.total_days
              FROM emp_vacation_balance 
              JOIN employees ON emp_vacation_balance.emp_id = employees.emp_id 
              WHERE emp_vacation_balance.emp_id = '$emp_id' 
              LIMIT 1";
              
    $result = mysqli_query($conDB, $query);
    if ($row = mysqli_fetch_assoc($result)) {
        $match = ($row['available_balance'] == $row['total_days']) ? '✓ MATCH' : '✗ MISMATCH';
        echo $emp_id . " (" . $row['name'] . ")\n";
        echo "  available_balance: " . $row['available_balance'] . "\n";
        echo "  total_days: " . $row['total_days'] . "\n";
        echo "  Status: $match\n\n";
    }
}
?>
