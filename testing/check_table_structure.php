<?php
require_once __DIR__ . '/includes/db.php';

$result = mysqli_query($conDB, "DESCRIBE emp_vacation");
echo "Column | Type | Null | Key | Default\n";
echo str_repeat("-", 80) . "\n";
while ($row = mysqli_fetch_assoc($result)) {
    printf("%-25s | %-20s | %-4s | %-3s | %s\n", 
        $row['Field'], 
        $row['Type'], 
        $row['Null'], 
        $row['Key'], 
        $row['Default'] ?? 'NULL'
    );
}
