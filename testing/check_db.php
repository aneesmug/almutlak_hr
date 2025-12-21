<?php
require 'includes/db.php';
$result = mysqli_query($conDB, "DESCRIBE general_requests");
if ($result) {
    echo "GENERAL_REQUESTS COLUMNS:\n";
    while($row = mysqli_fetch_assoc($result)) {
        echo $row['Field'] . " - " . $row['Type'] . "\n";
    }
} else {
    echo "Error: " . mysqli_error($conDB);
}

echo "\n\nSAMPLE DATA:\n";
$sample = mysqli_query($conDB, "SELECT inv_no, request_title, emp_name, priority, request_category, description, user_dept FROM general_requests LIMIT 1");
if ($sample && mysqli_num_rows($sample) > 0) {
    while ($row = mysqli_fetch_assoc($sample)) {
        print_r($row);
    }
} else {
    echo "No records found";
}
?>
