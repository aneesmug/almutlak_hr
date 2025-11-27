<?php
$conDB = mysqli_connect('localhost', 'root', 'admin123', 'almutlak_db');
if (!$conDB) {
    die('Connection failed: ' . mysqli_connect_error());
}

$empidget = 5430;
$result = mysqli_query($conDB, "SELECT 
    `employees`.`emp_id`,
    `employees`.`fly`,
    `employees`.`status`,
    `employees`.`name`,
    `employees`.`id` AS `eid`
FROM `employees`
WHERE `employees`.`emp_id` = {$empidget}
LIMIT 1");

if ($result && mysqli_num_rows($result) > 0) {
    $row = mysqli_fetch_assoc($result);
    echo "Employee ID: " . $row['emp_id'] . "\n";
    echo "Name: " . $row['name'] . "\n";
    echo "Fly Status: " . $row['fly'] . "\n";
    echo "Status: " . $row['status'] . "\n";
    echo "EID: " . $row['eid'] . "\n";
} else {
    echo "No employee found with ID: " . $empidget . "\n";
}

mysqli_close($conDB);
?>
