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

            // ---
            // START of restored "Snapshot" logic (now using 360-day math)
            // This logic attempts to find the *last* saved balance and add
            // new accrual from a fixed anchor date (e.g., Nov 1st) to today.
            // This will OVERWRITE the $available_balance if a snapshot is found.
            // ---
            $snap = null;
            $snap_q_latest = "SELECT available_balance, used_days, period_end, carryover_days, created_at
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

            // Add fresh accrual from snapshot created_at to snapshot available_balance
            if ($snap && isset($snap['available_balance'])) {
                $snapshot_available = (float)$snap['available_balance'];
                
                // Anchor to created_at (when snapshot was recorded/imported)
                $anchor = null;
                if (!empty($snap['created_at'])) {
                    $anchor = new DateTime($snap['created_at']);
                    $anchor->setTime(0, 0, 0);
                } else {
                    // Fallback to Nov 1st if created_at is missing
                    $anchor = new DateTime('2025-11-01');
                    $anchor->setTime(0, 0, 0);
                }
                
                $today_live = new DateTime();
                $today_live->setTime(0, 0, 0);

                // ** MODIFIED to use 360-day logic **
                $days_elapsed = 0;
                if ($today_live > $anchor) {
                    $days_elapsed = $this->calculate360DayDiff($anchor, $today_live);
                }

                $cp = $this->parseContractPeriod($emp_data['vac_period']);
                $yrs = $cp['years'];
                $annual_rate = ($yrs == 2) ? ($total_vac_days / 2) : $total_vac_days;
                
                // ** MODIFIED to use 360-day rate **
                $daily_rate_360 = $annual_rate / 360.0; 

                $accrued_raw = $days_elapsed * $daily_rate_360;
                $accrued_days = floor($accrued_raw * 100) / 100; // floor to 2 decimals

                // Final: stored_available + fresh_accrual_from_created_at
                // This logic OVERWRITES the baseline calculation.
                $available_balance = round($snapshot_available + $accrued_days, 2);
                error_log("360-day created_at-accrual for emp $emp_id: opening_available={$snapshot_available}, anchor=" . $anchor->format('Y-m-d') . ", days_360={$days_elapsed}, daily_rate_360={$daily_rate_360}, accrued_floor={$accrued_days}, final={$available_balance}");
            }

            error_log("Final calculations: remaining=$remaining_balance, available=$available_balance");

            // Return the calculated data as an array.
            return [
                'emp_id' => $emp_id,
                'contract_id' => $emp_data['vac_period'],
                'period_start' => $period_dates['start'],
                'period_end' => $period_dates['end'],
                'total_days' => $total_vac_days,
                'used_days' => $used_days,
                'remaining_balance' => $remaining_balance,
                'available_balance' => max(0, $available_balance), // Available balance cannot be negative.
                'carryover_days' => $carryover
            ];

        } catch (Exception $e) {
            error_log("getCalculatedBalance EXCEPTION for emp_id $emp_id: " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine());
            return null;
        }
    }
    
    // --- Private Helper Methods ---

    /**
     * Gets the total number of used vacation days that are fully approved by the GM.
     * It excludes emergency fly vacations from the total.
     */
    private function getUsedVacationDays($emp_id, $period_start, $period_end) {
        // Normalize dates once
        $start_str = $period_start instanceof DateTime ? $period_start->format('Y-m-d') : (string)$period_start;
        $end_str = $period_end instanceof DateTime ? $period_end->format('Y-m-d') : (string)$period_end;

        // 1) Prefer persisted balance within current period
        $persist_q = "SELECT used_days, period_end, created_at 
                      FROM emp_vacation_balance 
                      WHERE emp_id = ? 
                        AND period_start >= ? 
                        AND period_end   <= ? 
                      ORDER BY period_end DESC, id DESC 
                      LIMIT 1";
        $persist_stmt = $this->conDB->prepare($persist_q);
        if ($persist_stmt) {
            $persist_stmt->bind_param("sss", $emp_id, $start_str, $end_str);
            if ($persist_stmt->execute()) {
                $persist_res = $persist_stmt->get_result()->fetch_assoc();
                if ($persist_res && isset($persist_res['used_days'])) {
                    $persist_val = (float)$persist_res['used_days'];
                    $anchor_str = null;
                    if (!empty($persist_res['created_at'])) {
                        try {
                            $anchor_dt = new DateTime($persist_res['created_at']);
                            $anchor_dt->setTime(0, 0, 0);
                            $anchor_str = $anchor_dt->format('Y-m-d');
                        } catch (Exception $e) {
                            $anchor_str = null;
                        }
                    }
                    if (!$anchor_str && !empty($persist_res['period_end'])) {
                        $anchor_str = $persist_res['period_end'];
                    }
                    if ($persist_val > 0) {
                        // Add used since anchor (created_at or period_end)
                        $delta = 0.0;
                        if (!empty($anchor_str)) {
                            $delta_q = "SELECT COALESCE(SUM(`vacdays`), 0) AS addl
                                        FROM `emp_vacation`
                                        WHERE `emp_id` = ?
                                          AND `current_status` IN ('approved', 'gm_approved')
                                          AND ((`vac_type` = 'Fly' AND `fly_type` IN ('annual','emergency')) OR (`vac_type` = 'Local Vacation'))
                                          AND `start_date` > ? AND `start_date` <= ?";
                            $delta_stmt = $this->conDB->prepare($delta_q);
                            if ($delta_stmt) {
                                $delta_stmt->bind_param("sss", $emp_id, $anchor_str, $end_str);
                                if ($delta_stmt->execute()) {
                                    $delta_res = $delta_stmt->get_result()->fetch_assoc();
                                    $delta = (float)($delta_res['addl'] ?? 0);
                                }
                                $delta_stmt->close();
                            }
                        }
                        $persist_stmt->close();
                        return $persist_val + $delta;
                    }
                }
            } else {
                error_log("getUsedVacationDays persisted query execute failed: " . $persist_stmt->error);
            }
            $persist_stmt->close();
        } else {
            error_log("getUsedVacationDays persisted query prepare failed: " . $this->conDB->error);
        }

        // 2) Fallback: Sum approved vacations within the current period
        $query = "SELECT COALESCE(SUM(`vacdays`), 0) AS `used_days`
                  FROM `emp_vacation`
                  WHERE `emp_id` = ?
                    AND `current_status` IN ('approved', 'gm_approved')
                    AND ((`vac_type` = 'Fly' AND `fly_type` IN ('annual','emergency')) OR (`vac_type` = 'Local Vacation'))
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
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return (float)($result['used_days'] ?? 0);
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
        
        $years_employed = $today->diff($joining)->y;
        $contracts_completed = floor($years_employed / $contract_years);
        
        $current_start = (clone $joining)->add(new DateInterval("P" . ($contracts_completed * $contract_years) . "Y"));
        $current_start->setTime(0, 0, 0);
        $current_end = (clone $current_start)->add(new DateInterval("P" . $contract_years . "Y"));
        $current_end->setTime(0, 0, 0);
        
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
        return (($y2 - $y1) * 360) + (($m2 - $m1) * 30) + ($d2 - $d1);
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