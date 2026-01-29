<?php
require_once 'includes/db.php';

echo "=== Settlement Records Table Structure ===\n";
$q = 'DESCRIBE settlement_records';
$r = mysqli_query($conDB, $q);
while($row = mysqli_fetch_assoc($r)) {
    echo $row['Field'] . " (" . $row['Type'] . ")\n";
}

echo "\n=== Sample Settlement Record ===\n";
$q = 'SELECT * FROM settlement_records LIMIT 1';
$r = mysqli_query($conDB, $q);
if ($row = mysqli_fetch_assoc($r)) {
    foreach ($row as $k => $v) {
        echo $k . ": " . $v . "\n";
    }
}
?>
