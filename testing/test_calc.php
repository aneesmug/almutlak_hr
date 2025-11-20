<?php
require_once './includes/db.php';
require_once './includes/vacation_calculator.php';

$emp_id = isset($_GET['emp_id']) ? trim($_GET['emp_id']) : '5430';
$calc = new VacationCalculator($conDB);
$balance = $calc->getCalculatedBalance($emp_id);

header('Content-Type: application/json');
echo json_encode($balance);
