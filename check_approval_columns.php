<?php
require 'includes/db.php';

$tables = ['emp_vacation', 'emp_loan', 'smart_request', 'emp_resignations', 'rejoin_requests'];

foreach($tables as $table) {
    echo "\n=== $table ===\n";
    $result = mysqli_query($conDB, "SHOW COLUMNS FROM $table");
    if($result) {
        while($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
            echo $row['Field'] . ": " . $row['Type'] . "\n";
        }
    } else {
        echo "Error: " . mysqli_error($conDB) . "\n";
    }
}

// Check if approval_comment or approval_reason columns already exist
echo "\n\n=== CHECKING FOR EXISTING APPROVAL COLUMNS ===\n";
foreach($tables as $table) {
    echo "\n$table:\n";
    $result = mysqli_query($conDB, "SHOW COLUMNS FROM $table LIKE 'approval_%'");
    if($result && mysqli_num_rows($result) > 0) {
        while($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
            echo "  - " . $row['Field'] . "\n";
        }
    } else {
        echo "  - No approval columns found\n";
    }
}

mysqli_close($conDB);
?>
