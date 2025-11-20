<?php
require_once __DIR__ . '/includes/db.php';

$emp_id = '5430';

echo "<h2>All Vacations for Employee 5430</h2>";
echo "<pre>";

$sql = "SELECT id, emp_id, start_date, return_date, vacdays, current_status, vac_type, fly_type, review, note FROM emp_vacation WHERE emp_id = ? ORDER BY start_date DESC LIMIT 20";
$stmt = $conDB->prepare($sql);
$stmt->bind_param("s", $emp_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "ID: {$row['id']} | Start: {$row['start_date']} | Days: {$row['vacdays']} | Status: {$row['current_status']} | Type: {$row['vac_type']} | Fly: {$row['fly_type']} | Note: {$row['note']} | Review: {$row['review']}\n";
    }
} else {
    echo "No vacations found\n";
}

echo "</pre>";
?>
