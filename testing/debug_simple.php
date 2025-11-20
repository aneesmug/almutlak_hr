<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Step 1: Loading db.php\n";
require_once './includes/db.php';
echo "SUCCESS: DB loaded\n\n";

$emp_id = '5430';

echo "Step 2: Querying emp_vacation\n";
$sql = "SELECT id, emp_id, start_date, return_date, vacdays, current_status FROM emp_vacation WHERE emp_id = ? ORDER BY start_date DESC LIMIT 20";
$stmt = $conDB->prepare($sql);
if (!$stmt) {
    echo "ERROR preparing statement: " . $conDB->error . "\n";
    die();
}
$stmt->bind_param("s", $emp_id);
$stmt->execute();
$result = $stmt->get_result();

echo "Found " . $result->num_rows . " records\n\n";

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "ID: {$row['id']}, Days: {$row['vacdays']}, Status: {$row['current_status']}, Start: {$row['start_date']}, Return: {$row['return_date']}\n";
    }
}
?>
