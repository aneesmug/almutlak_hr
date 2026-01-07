<?php
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/vacation_calculator.php';

$calc = new VacationCalculator($conDB);
$res = $calc->getCalculatedBalance('5430');
var_export($res);
