<?php
require_once 'includes/db.php';

$result = mysqli_query($conDB, "DESCRIBE emp_vacation");
echo "emp_vacation schema:\n";
echo "====================\n";
while ($row = mysqli_fetch_assoc($result)) {
    if (in_array($row['Field'], ['departure_date', 'arrival_date', 'start_date', 'return_date'])) {
        echo $row['Field'] . " => Type: " . $row['Type'] . ", Null: " . $row['Null'] . ", Default: " . ($row['Default'] ?? 'NULL') . "\n";
    }
}

// Also check actual values
echo "\nSample data:\n";
echo "====================\n";
$result2 = mysqli_query($conDB, "SELECT id, start_date, return_date, departure_date, arrival_date FROM emp_vacation ORDER BY id DESC LIMIT 3");
while ($row = mysqli_fetch_assoc($result2)) {
    echo "ID: {$row['id']}, start: {$row['start_date']}, return: {$row['return_date']}, departure: {$row['departure_date']}, arrival: {$row['arrival_date']}\n";
}
?>
