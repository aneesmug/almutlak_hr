<?php
require_once __DIR__ . '/includes/db.php';

$result = mysqli_query($conDB, "SELECT id, emp_id, departure_date, arrival_date, vacation_salary_type, created_at FROM emp_vacation WHERE id = 518");
$row = mysqli_fetch_assoc($result);

echo json_encode($row, JSON_PRETTY_PRINT);
