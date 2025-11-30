<?php
include('./includes/db.php');

$empidget = 5430;

$get_emp_data = mysqli_query($conDB, "SELECT 
    `employees`.`emp_id`, 
    `employees`.`id` AS `eid`, 
    `employees`.`fly` AS `flystus_emp`,
    `employees`.`fly`,
    `employees`.`status`,
    `employees`.`name`
FROM `employees`
WHERE `employees`.`emp_id` = {$empidget} 
LIMIT 1");

if ($get_emp_data && mysqli_num_rows($get_emp_data) > 0) {
    $emprow = mysqli_fetch_assoc($get_emp_data);
    echo "Employee ID: " . $emprow['emp_id'] . "\n";
    echo "Name: " . $emprow['name'] . "\n";
    echo "Fly (direct): " . $emprow['fly'] . "\n";
    echo "Flystus_emp (alias): " . $emprow['flystus_emp'] . "\n";
    echo "Status: " . $emprow['status'] . "\n";
    echo "EID: " . $emprow['eid'] . "\n";
    
    // Test the condition logic
    echo "\n--- Condition Tests ---\n";
    if ($emprow["flystus_emp"] == 1) {
        echo "Result: VACATION (yellow) - On Vacation\n";
    } elseif ($emprow["status"] == "0") {
        echo "Result: INACTIVE (red) - Inactive\n";
    } else {
        echo "Result: ACTIVE (green) - Active\n";
    }
} else {
    echo "No employee found\n";
}

mysqli_close($conDB);
?>
