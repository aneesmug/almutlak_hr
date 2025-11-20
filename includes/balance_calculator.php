<?php
/**
 * Get current vacation balance for an employee
 * Reads from emp_vacation_balance table (updated daily by cron job)
 * NOTE: Live calculation is disabled - balance is now updated once per day via cron
 * 
 * @param mysqli $conDB Database connection
 * @param string $emp_id Employee ID
 * @return float|null Current available balance or null on error
 */
function get_current_vacation_balance($conDB, $emp_id) {
    if (empty($emp_id)) {
        return null;
    }
    
    // Get employee's available_balance from emp_vacation_balance table
    // This is updated daily by the cron job (cron_update_vacation_balances.php)
    $stmt = mysqli_query($conDB, "SELECT `available_balance` FROM `emp_vacation_balance` WHERE `emp_id` = '" . mysqli_real_escape_string($conDB, $emp_id) . "' ORDER BY `last_updated` DESC LIMIT 1");
    
    if ($stmt && mysqli_num_rows($stmt) > 0) {
        $row = mysqli_fetch_assoc($stmt);
        $balance = (float)$row['available_balance'];
        mysqli_free_result($stmt);
        return $balance;
    }
    
    return 0.0; // Return 0 if no balance record found
}
?>
