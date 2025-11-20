<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once './includes/db.php';

$emp_id = '5430';

echo "=== VACATION RECORDS ===\n";
$sql = "SELECT id, vacdays, current_status FROM emp_vacation WHERE emp_id = ?";
$stmt = $conDB->prepare($sql);
$stmt->bind_param("s", $emp_id);
$stmt->execute();
$result = $stmt->get_result();

echo "Total records: " . $result->num_rows . "\n";

while ($row = $result->fetch_assoc()) {
    echo "ID {$row['id']}: {$row['vacdays']} days ({$row['current_status']})\n";
}

echo "\nDatabase connection working!\n";
?>
