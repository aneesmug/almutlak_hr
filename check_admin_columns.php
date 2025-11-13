<?php
require_once __DIR__ . '/includes/db.php';

echo "=== Checking admin_login columns ===\n\n";

$result = mysqli_query($conDB, "DESCRIBE admin_login");
echo "admin_login columns:\n";
while($row = mysqli_fetch_assoc($result)) {
    echo "  {$row['Field']}\n";
}
?>
