<?php
/**
 * Calculates and updates employee vacation balances.
 * This class is responsible for all logic related to the `emp_vacation_balance` table.
 * * MODIFIED: This version now uses 30/360 day-count logic to match the AS400 system.
 * * MODIFIED: Restored the snapshot/anchor-date logic in getCalculatedBalance.
 */
class VacationCalculator {
    private $conDB;
    
    public function __construct($dbConnection) {
        $this->conDB = $dbConnection;
    }
    
    /**
     * The main public method to save an employee's full vacation balance to the database.
     * It fetches all necessary data and updates the `emp_vacation_balance` table.
     * This should only be called upon final GM approval of a vacation request.
     *
     * @param string $emp_id The employee's ID.
     * @return bool True on success, false on failure.
     */
    public function calculateVacationBalance($emp_id, $vacation_id) {
        // This method now gets the calculated balance and its only job is to save it.
        $balance_data = $this->getCalculatedBalance($emp_id);
        
        if ($balance_data) {
            $this->updateBalanceRecord(
                $balance_data['emp_id'],
                $vacation_id,
                $balance_data['contract_id'],
                $balance_data['period_start'],
                $balance_data['period_end'],
                $balance_data['total_days'],
                $balance_data['used_days'],
                $balance_data['remaining_balance'],
                $balance_data['available_balance'],
                $balance_data['carryover_days']
            );
            return true;
        }
        
        return false;
    }

    /**
     * MODIFIED: Gets the most recent vacation balance record for an employee.
     * If no record exists, it calculates the current theoretical balance without saving it.
     *
     * @param string $emp_id The employee's ID.
     * @return array|null The balance record or null if not found.
     */
    public function getLatestBalance($emp_id) {
        $query = "SELECT * FROM `emp_vacation_balance` 
                  WHERE `emp_id` = ? 
                  ORDER BY `period_end` DESC 
                  LIMIT 1";
        $stmt = $this->conDB->prepare($query);
        $stmt->bind_param("s", $emp_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        // If no balance record exists in the database (e.g., for a new employee),
        // we calculate the current theoretical balance without saving it.
        // This allows submitting an encashment request based on earned days.
        if (!$result) {
            return $this->getCalculatedBalance($emp_id);
        }
        
        return $result;
    }

    /**
     * NEW: Performs all balance calculations without saving them to the database.
     * This can be used to get a "live" view of the balance for requests before approval.
     *
     * @param string $emp_id The employee's ID.
     * @return array|null An array with balance data or null on failure.
     */
    public function getCalculatedBalance($emp_id) {
        try {
            error_log("getCalculatedBalance START for emp_id=$emp_id");
            
            $emp_data = $this->getEmployeeData($emp_id);
            error_log("getEmployeeData result: " . json_encode($emp_data));
            if (!$emp_data) {
                throw new Exception("Employee contract data not found for emp_id: $emp_id");
            }
            
            $contract_info = $this->parseContractPeriod($emp_data['vac_period']);
            error_log("parseContractPeriod result: " . json_encode($contract_info));
            $total_vac_days = $contract_info['total_days'];

            $period_dates = $this->calculateContractPeriod(
                $emp_data['joining_date'], 
                $contract_info['years']
            );
            error_log("calculateContractPeriod result: start=" . $period_dates['start']->format('Y-m-d') . " end=" . $period_dates['end']->format('Y-m-d'));

            // Get the sum of all GM-approved vacation days within the current period.
            $used_days = $this->getUsedVacationDays(
                $emp_id,
                $period_dates['start'],
                $period_dates['end']
            );
            error_log("getUsedVacationDays result: $used_days");

            $carryover = $this->calculateCarryover(
                $emp_id,
                $emp_data['vac_period'],
                $total_vac_days,
                $period_dates['start'],
                $contract_info['years']
            );
            error_log("calculateCarryover result: $carryover");

            // This is the baseline calculation using the 360-day logic
            $earned_days = $this->calculateEarnedDays(
                $total_vac_days,
                $period_dates['start'],
                $period_dates['end']
            );
            error_log("calculateEarnedDays (360-day) result: $earned_days");

            // Calculate final balance figures (baseline full-period method)
            $remaining_balance = $total_vac_days - $used_days;
            $available_balance = $earned_days + $carryover - $used_days;
            
            if ($emp_id === '5160') {
                error_log("DEBUG emp_5160: earned_days=$earned_days, carryover=$carryover, used_days=$used_days, baseline_available_balance=$available_balance");
            }

            // --- Snapshot logic using last_updated as anchor for DAILY ACCRUAL ---
            // This calculates: stored_available + fresh_accrual_from_last_updated
            // Used to show live balance with today's accrual included
            $snap = null;
            $snap_q_latest = "SELECT available_balance, used_days, period_end, carryover_days, last_updated, created_at
                               FROM emp_vacation_balance
                               WHERE emp_id = ?
                               ORDER BY id DESC
                               LIMIT 1";
            if ($stmtSnap = $this->conDB->prepare($snap_q_latest)) {
                $stmtSnap->bind_param("s", $emp_id);
                if ($stmtSnap->execute()) {
                    $snap = $stmtSnap->get_result()->fetch_assoc();
                }
                $stmtSnap->close();
            }

            // Add fresh accrual from snapshot last_updated to snapshot available_balance
            try {
                error_log("getCalculatedBalance START for emp_id=$emp_id");
                $emp_data = $this->getEmployeeData($emp_id);
                error_log("getEmployeeData result: " . json_encode($emp_data));
                if (!$emp_data) {
                    error_log("ERROR: Employee contract data not found for emp_id: $emp_id");
                    throw new Exception("Employee contract data not found for emp_id: $emp_id");
                }

                $contract_info = $this->parseContractPeriod($emp_data['vac_period']);
                error_log("parseContractPeriod result: " . json_encode($contract_info));
                if (!$contract_info || !isset($contract_info['total_days'])) {
                    error_log("ERROR: Contract info missing or invalid for emp_id: $emp_id");
                    throw new Exception("Contract info missing or invalid for emp_id: $emp_id");
                }
                $total_vac_days = $contract_info['total_days'];

                $period_dates = $this->calculateContractPeriod($emp_data['joining_date'], $contract_info['years']);
                if (!$period_dates || !isset($period_dates['start']) || !isset($period_dates['end'])) {
                    error_log("ERROR: Period dates missing or invalid for emp_id: $emp_id");
                    throw new Exception("Period dates missing or invalid for emp_id: $emp_id");
                }
                error_log("calculateContractPeriod result: start=" . $period_dates['start']->format('Y-m-d') . " end=" . $period_dates['end']->format('Y-m-d'));

                $used_days = $this->getUsedVacationDays($emp_id, $period_dates['start'], $period_dates['end']);
                error_log("getUsedVacationDays result: $used_days");

                $carryover = $this->calculateCarryover($emp_id, $emp_data['vac_period'], $total_vac_days, $period_dates['start'], $contract_info['years']);
                error_log("calculateCarryover result: $carryover");

                // AS400-style cumulative rounding accrual
                $today = new DateTime();
                $today->setTime(0, 0, 0);
                $earned_days = $this->calculateEarnedDays($total_vac_days, $period_dates['start'], $period_dates['end']);
                error_log("calculateEarnedDays (360-day) result: $earned_days");

                $remaining_balance = $total_vac_days - $used_days;
                $available_balance = $earned_days + $carryover - $used_days;

                // --- Snapshot logic using last_updated as anchor for DAILY ACCRUAL ---
                $snap = null;
                $snap_q_latest = "SELECT available_balance, used_days, period_end, carryover_days, last_updated, created_at FROM emp_vacation_balance WHERE emp_id = ? ORDER BY id DESC LIMIT 1";
                if ($stmtSnap = $this->conDB->prepare($snap_q_latest)) {
                    $stmtSnap->bind_param("s", $emp_id);
                    if ($stmtSnap->execute()) {
                        $snap = $stmtSnap->get_result()->fetch_assoc();
                    }
                    $stmtSnap->close();
                }

                if ($snap && isset($snap['available_balance'])) {
                    $snapshot_available = (float)$snap['available_balance'];
                    $anchor = null;
                    if (!empty($snap['last_updated'])) {
                        $anchor = new DateTime($snap['last_updated']);
                        $anchor->setTime(0, 0, 0);
                    } elseif (!empty($snap['created_at'])) {
                        $anchor = new DateTime($snap['created_at']);
                        $anchor->setTime(0, 0, 0);
                    } else {
                        $anchor = new DateTime('2025-11-01');
                        $anchor->setTime(0, 0, 0);
                    }

                    $today_live = new DateTime();
                    $today_live->setTime(0, 0, 0);

                    $days_elapsed = 0;
                    if ($today_live > $anchor) {
                        $days_elapsed = self::calculate360DayDiff($anchor, $today_live);
                    }

                    $cp = $this->parseContractPeriod($emp_data['vac_period']);
                    $yrs = $cp['years'];
                    $annual_rate = ($yrs == 2) ? ($total_vac_days / 2) : $total_vac_days;
                    $daily_rate_360 = $annual_rate / 360.0; 
                    $accrued_raw = $days_elapsed * $daily_rate_360;
                    $accrued_days = round($accrued_raw, 2);
                    $available_balance = round($snapshot_available + $accrued_days, 2);
                    error_log("360-day last_updated-accrual for emp $emp_id: opening_available={$snapshot_available}, anchor=" . $anchor->format('Y-m-d') . ", days_360={$days_elapsed}, daily_rate_360={$daily_rate_360}, accrued_days={$accrued_days}, final={$available_balance}");
                }

                error_log("Final calculations: remaining=$remaining_balance, available=$available_balance");

                $final_available = max(0, $available_balance);
                return [
                    'emp_id' => $emp_id,
                    'contract_id' => $emp_data['vac_period'],
                    'period_start' => $period_dates['start'],
                    'period_end' => $period_dates['end'],
                    'total_days' => $total_vac_days,
                    'used_days' => $used_days,
                    'remaining_balance' => $remaining_balance,
                    'available_balance' => $final_available,
                    'carryover_days' => $carryover
                ];
            } catch (Exception $e) {
                error_log("getCalculatedBalance EXCEPTION for emp_id $emp_id: " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine());
                return null;
            }
        } catch (Exception $e) {
            error_log("getCalculatedBalance EXCEPTION for emp_id $emp_id: " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine());
            return null;
        }
    }

    /**
     * Gets the sum of all GM-approved vacation days for an employee within a given date range.
     *
     * @param string $emp_id The employee's ID.
     * @param DateTime $period_start The start of the period.
     * @param DateTime $period_end The end of the period.
     * @return float The total vacation days used.
     */
    private function getUsedVacationDays($emp_id, DateTime $period_start, DateTime $period_end) {
        $start_str = $period_start->format('Y-m-d');
        $end_str = $period_end->format('Y-m-d');

        // CRITICAL FIX: Emergency vacations are NOT deducted from available_balance
        // They are special leave that doesn't count against the employee's allocation.
        // Only count ANNUAL vacations and Local Vacations.
        // If an employee rejoins early from an emergency vacation, the balance doesn't go negative.
        $query = "SELECT `id`, `vacdays`, `start_date`, `return_date`
                  FROM `emp_vacation`
                  WHERE `emp_id` = ?
                    AND `current_status` IN ('approved', 'gm_approved')
                    AND ((`vac_type` = 'Fly' AND `fly_type` IN ('annual')) OR (`vac_type` = 'Local Vacation'))
                    AND `start_date` BETWEEN ? AND ?";

        $stmt = $this->conDB->prepare($query);
        if (!$stmt) {
            error_log("getUsedVacationDays prepare failed: " . $this->conDB->error);
            return 0;
        }
        $stmt->bind_param("sss", $emp_id, $start_str, $end_str);
        if (!$stmt->execute()) {
            error_log("getUsedVacationDays execute failed: " . $stmt->error);
            $stmt->close();
            return 0;
        }
        
        // === FIX: Calculate total with full holiday deduction ===
        $result = $stmt->get_result();
        $total_used_days = 0.0;
        
        while ($vacation_row = $result->fetch_assoc()) {
            $vac_days = (float)$vacation_row['vacdays'];
            $vac_start = $vacation_row['start_date'];
            $vac_end = $vacation_row['return_date'];
            
            // Get ALL active holidays that exist during this vacation period
            // Business rule: subtract ALL holiday days, not just overlap
            $holiday_query = "SELECT total_days FROM emp_holidays 
                            WHERE is_active = 1 
                            AND start_date <= ? 
                            AND end_date >= ? ";
            $h_stmt = $this->conDB->prepare($holiday_query);
            if ($h_stmt) {
                $h_stmt->bind_param("ss", $vac_end, $vac_start);
                $h_stmt->execute();
                $h_result = $h_stmt->get_result();
                
                $total_holiday_days = 0;
                while ($holiday_row = $h_result->fetch_assoc()) {
                    // Use the total_days field directly - this is the full holiday period
                    $holiday_total = (float)($holiday_row['total_days'] ?? 0);
                    $total_holiday_days += $holiday_total;
                }
                
                $h_result->free();
                $h_stmt->close();
                
                // Deductible days = vacation days - total holiday days
                $deductible_days = max(0, $vac_days - $total_holiday_days);
                $total_used_days += $deductible_days;
            } else {
                // If holiday query fails, use full vacation days (safer fallback)
                $total_used_days += $vac_days;
            }
        }
        
        $result->free();
        $stmt->close();
        
        return $total_used_days;
    }
    
    /**
     * Inserts a new balance record or updates it if one for the current period already exists.
     * Requires a UNIQUE key on `(emp_id, contract_id, period_start)` in the table.
     */
    private function updateBalanceRecord($emp_id, $vacation_id, $contract_id, $period_start, $period_end, $total_days, $used_days, $remaining_balance, $available_balance, $carryover) {
        $query = "INSERT INTO `emp_vacation_balance` 
                    (`emp_id`, `vac_id`, `contract_id`, `period_start`, `period_end`, `total_days`, 
                     `used_days`, `remaining_balance`, `available_balance`, `carryover_days`)
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                  ON DUPLICATE KEY UPDATE
                     `used_days` = VALUES(`used_days`),
                     `remaining_balance` = VALUES(`remaining_balance`),
                     `available_balance` = VALUES(`available_balance`),
                     `carryover_days` = VALUES(`carryover_days`),
                     `last_updated` = NOW()";
                     
        $stmt = $this->conDB->prepare($query);
        $stmt->bind_param(
            "ssisssdddd", 
            $emp_id,
            $vacation_id,
            $contract_id,
            $period_start->format('Y-m-d'),
            $period_end->format('Y-m-d'),
            $total_days,
            $used_days,
            $remaining_balance,
            $available_balance,
            $carryover
        );
        $stmt->execute();
    }

    private function getEmployeeData($emp_id) {
        $query = "SELECT `e`.`joining_date`, `e`.`vac_period`, `cp`.`period`, `cp`.`vac_period` AS `contract_vac_days` 
                  FROM `employees` `e`
                  JOIN `contract_period` `cp` ON `e`.`vac_period` = `cp`.`id`
                  WHERE `e`.`emp_id` = ?";
        $stmt = $this->conDB->prepare($query);
        $stmt->bind_param("s", $emp_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }
    
    private function parseContractPeriod($contract_id) {
        $query = "SELECT period, vac_period FROM contract_period WHERE id = ?";
        $stmt = $this->conDB->prepare($query);
        $stmt->bind_param("i", $contract_id);
        $stmt->execute();
        $contract = $stmt->get_result()->fetch_assoc();
        
        // Extracts the number of years from a string like "2 Years - 30".
        preg_match('/(\d+)/', $contract['period'], $matches);
        $years = $matches[0] ?? 1; // Default to 1 year if not found.
        
        return [
            'years' => (int)$years,
            'total_days' => (float)$contract['vac_period']
        ];
    }
    
    private function calculateContractPeriod($joining_date, $contract_years) {
        // Normalize joining date (handles formats like DD/MM/YYYY)
        $joinStr = is_string($joining_date) ? str_replace('/', '-', $joining_date) : $joining_date;
        $joining = new DateTime($joinStr);
        $joining->setTime(0, 0, 0);
        $today = new DateTime();
        $today->setTime(0, 0, 0);
        
        // FIXED: Use day-based calculation instead of year difference to avoid losing days
        // This ensures we don't miss any days at the contract boundary
        $interval = $joining->diff($today);
        $days_employed = $interval->days;  // Total days employed
        $days_per_contract = $contract_years * 360;  // Using 360-day contract
        $contracts_completed = floor($days_employed / $days_per_contract);
        
        $current_start = (clone $joining)->add(new DateInterval("P" . ($contracts_completed * $contract_years) . "Y"));
        $current_start->setTime(0, 0, 0);
        $current_end = (clone $current_start)->add(new DateInterval("P" . $contract_years . "Y"));
        $current_end->setTime(0, 0, 0);
        
        // VALIDATION: Ensure today falls within the calculated period
        if ($today < $current_start || $today >= $current_end) {
            error_log("WARNING: Date validation failed for emp_id calculation. today=" . $today->format('Y-m-d') . 
                     ", current_start=" . $current_start->format('Y-m-d') . ", current_end=" . $current_end->format('Y-m-d'));
        }
        
        return ['start' => $current_start, 'end' => $current_end];
    }
    
    private function calculateCarryover($emp_id, $contract_id, $total_vac_days, $current_start, $contract_years) {
        // Calculate the start date of the previous period.
        $prev_period_start = (clone $current_start)->sub(new DateInterval("P" . $contract_years . "Y"));
        
        $query = "SELECT remaining_balance 
                  FROM emp_vacation_balance 
                  WHERE emp_id = ? AND contract_id = ? AND period_start = ?";
        
        // Format date BEFORE bind_param to avoid temporary string references
        $prev_start_str = $prev_period_start instanceof DateTime ? $prev_period_start->format('Y-m-d') : (string)$prev_period_start;
        
        $stmt = $this->conDB->prepare($query);
        if (!$stmt) {
            error_log("calculateCarryover prepare failed: " . $this->conDB->error);
            return 0;
        }
        $stmt->bind_param("sis", $emp_id, $contract_id, $prev_start_str);
        if (!$stmt->execute()) {
            error_log("calculateCarryover execute failed: " . $stmt->error);
            return 0;
        }
        $prev_balance = (float)($stmt->get_result()->fetch_assoc()['remaining_balance'] ?? 0);
        $stmt->close();
        
        // The maximum allowed carryover is 50% of the total vacation days for the contract.
        $max_carryover = $total_vac_days * 0.5;
        
        return min($prev_balance, $max_carryover);
    }
    
    /**
     * *** NEW FUNCTION ***
     * Calculates the difference between two dates using the 30/360 day-count basis.
     * This matches the AS400/financial calculation shown in the screenshots.
     *
     * VALIDATION: For full contract periods:
     * - 1-year contract (Jan 1 to Dec 31) = 360 days
     * - 2-year contract = 720 days
     * - Period boundaries are checked to prevent day loss
     *
     * @param DateTime $date_start
     * @param DateTime $date_end
     * @return int
     */
    private function calculate360DayDiff(DateTime $date_start, DateTime $date_end) {
        $y1 = (int)$date_start->format('Y');
        $m1 = (int)$date_start->format('m');
        $d1 = (int)$date_start->format('d');
        
        $y2 = (int)$date_end->format('Y');
        $m2 = (int)$date_end->format('m');
        $d2 = (int)$date_end->format('d');
        
        // This is the key formula for the 30/360 method that matches your screenshots
        // (e.g., May 18 to Nov 17 = 179 days)
        // (e.g., Oct 15 to Nov 17 = 32 days)
        $diff = (($y2 - $y1) * 360) + (($m2 - $m1) * 30) + ($d2 - $d1);
        
        // VALIDATION: Warn if calculating a full year boundary with unexpected result
        $interval = $date_start->diff($date_end);
        if ($interval->y > 0 && $diff < 0) {
            error_log("WARNING: calculate360DayDiff returned negative value: " . 
                     $date_start->format('Y-m-d') . " to " . $date_end->format('Y-m-d') . " = " . $diff . " days");
        }
        
        return $diff;
    }

    /**
     * *** MODIFIED FUNCTION ***
     * Calculates the prorated earned days.
     * NOW USES 30/360 logic to match the AS400.
     */
    private function calculateEarnedDays($total_vac_days, $period_start, $period_end) {
        $today = new DateTime();
        $today->setTime(0, 0, 0); // Normalize today's date

        // If the current date is before the period starts, no days have been earned.
        if ($today < $period_start) {
            return 0;
        }
        
        // If the current date is after the period has ended, all days have been earned.
        if ($today >= $period_end) {
            return $total_vac_days;
        }

        // Use the new 360-day diff function
        // This calculates days elapsed from the start of the period until today
        $days_elapsed = $this->calculate360DayDiff($period_start, $today);
        
        // Calculate the total days in the period, also using 360-day logic.
        // For a 1-year contract, this will be 360.
        $total_days_in_period = $this->calculate360DayDiff($period_start, $period_end);

        if ($total_days_in_period == 0) {
            return 0; // Avoid division by zero
        }
        
        // Prorate the earned days using the 360-day values
        // (e.g., 30 * (179 / 360) = 14.916...)
        $earned = $total_vac_days * ($days_elapsed / $total_days_in_period);
        
        // Match AS400 rounding (2 decimal places)
        // (e.g., 14.916... becomes 14.92)
        // (e.g., 2.666... becomes 2.67)
        return round($earned, 2);
    }
}

/**
 * Function to deduct vacation days from balance after final approval
 * This function is called when a vacation request receives its final approval
 * and needs to update the emp_vacation_balance table
 * 
 * @param mysqli $conDB - MySQLi connection
 * @param int $vacation_id - The vacation record ID
 * @return bool - True if successful, false otherwise
 */
function update_vacation_balance_on_approval(mysqli $conDB, $vacation_id) {
    try {
        // Get vacation details
        $sql_vac = "SELECT emp_id, vacdays, vac_type FROM emp_vacation WHERE id = ?";
        $stmt_vac = mysqli_prepare($conDB, $sql_vac);
        if (!$stmt_vac) {
            throw new Exception("Database prepare error: " . mysqli_error($conDB));
        }
        
        mysqli_stmt_bind_param($stmt_vac, "i", $vacation_id);
        if (!mysqli_stmt_execute($stmt_vac)) {
            throw new Exception("Query execution error: " . mysqli_stmt_error($stmt_vac));
        }
        
        $result = mysqli_stmt_get_result($stmt_vac);
        $vac_record = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt_vac);
        
        if (!$vac_record) {
            throw new Exception("Vacation record not found for ID: $vacation_id");
        }
        
        $emp_id = $vac_record['emp_id'];
        $vacdays = floatval($vac_record['vacdays']);
        $vac_type = $vac_record['vac_type'];
        
        // Skip balance deduction for non-deductible vacation types
        $non_deductible_types = [
            'Sick Leave', 
            'Exam Leave', 
            'Hajj Leave', 
            'Maternity Leave', 
            'Marriage Leave', 
            'Newborn Leave',
            'Death Leave',
            'Business Trip',
        ];
        
        if (in_array($vac_type, $non_deductible_types)) {
            error_log("Vacation ID $vacation_id (Type: $vac_type) is non-deductible - skipping balance update");
            return true; // Not an error, just don't deduct
        }
        
        // Get the latest vacation balance record for this employee
        $sql_balance = "SELECT id, available_balance, used_days FROM emp_vacation_balance 
                        WHERE emp_id = ? 
                        ORDER BY period_end DESC 
                        LIMIT 1";
        $stmt_balance = mysqli_prepare($conDB, $sql_balance);
        if (!$stmt_balance) {
            throw new Exception("Database prepare error: " . mysqli_error($conDB));
        }
        
        mysqli_stmt_bind_param($stmt_balance, "s", $emp_id);
        if (!mysqli_stmt_execute($stmt_balance)) {
            throw new Exception("Query execution error: " . mysqli_stmt_error($stmt_balance));
        }
        
        $result_balance = mysqli_stmt_get_result($stmt_balance);
        $balance_record = mysqli_fetch_assoc($result_balance);
        mysqli_stmt_close($stmt_balance);
        
        if (!$balance_record) {
            error_log("No vacation balance record found for emp_id: $emp_id - skipping balance update");
            return true; // Not an error, just no record to update
        }
        
        $balance_id = $balance_record['id'];
        $current_available = floatval($balance_record['available_balance']);
        $current_used = floatval($balance_record['used_days']);
        
        // Calculate new values
        $new_available_balance = max(0, $current_available - $vacdays); // Don't go below 0
        $new_used_days = $current_used + $vacdays;
        
        // Update the balance record
        $sql_update = "UPDATE emp_vacation_balance 
                       SET available_balance = ?,
                           remaining_balance  = ?,
                           total_days  = ?,
                           used_days = ?, 
                           vac_id = ?, 
                           last_updated = NOW() 
                       WHERE id = ?";
        $stmt_update = mysqli_prepare($conDB, $sql_update);
        if (!$stmt_update) {
            throw new Exception("Database prepare error: " . mysqli_error($conDB));
        }

        // Bind variables (expressions are not allowed in bind_param)
        $new_remaining_balance = $new_available_balance; // remaining = available after deduction
        $total_days_by_balance = $new_available_balance + $new_used_days; // for reporting/consistency

        // Use correct types: doubles for balances and used, ints for IDs
        mysqli_stmt_bind_param(
            $stmt_update,
            "ddddii",
            $new_available_balance,
            $new_remaining_balance,
            $total_days_by_balance,
            $new_used_days,
            $vacation_id,
            $balance_id
        );
        if (!mysqli_stmt_execute($stmt_update)) {
            throw new Exception("Query execution error: " . mysqli_stmt_error($stmt_update));
        }
        
        $affected = mysqli_stmt_affected_rows($stmt_update);
        mysqli_stmt_close($stmt_update);
        
        if ($affected > 0) {
            error_log("Vacation balance updated for emp_id $emp_id (vac_id $vacation_id): " .
                     "available_balance $current_available → $new_available_balance, " .
                     "used_days $current_used → $new_used_days, " .
                     "deduction: $vacdays days");
            return true;
        } else {
            error_log("No rows affected when updating vacation balance for emp_id: $emp_id");
            return false;
        }
        
    } catch (Exception $e) {
        error_log("Error in update_vacation_balance_on_approval: " . $e->getMessage());
        return false;
    }
}