<?php
// Helper to get employee's remaining vacation balance
function get_employee_vacation_balance($conDB, $emp_id) {
    $sql = "SELECT remaining_balance FROM emp_vacation_balance WHERE emp_id = ? ORDER BY id DESC LIMIT 1";
    $stmt = mysqli_prepare($conDB, $sql);
    if (!$stmt) return false;
    mysqli_stmt_bind_param($stmt, "i", $emp_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($res);
    mysqli_free_result($res);
    mysqli_stmt_close($stmt);
    return $row ? (float)$row['remaining_balance'] : false;
}
