<?php
/**
 * Calculates and updates employee vacation balances.
 * This class is responsible for all logic related to the `emp_vacation_balance` table.
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
                $balance_data['vacation_id'] = $vacation_id,
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
            $emp_data = $this->getEmployeeData($emp_id);
            if (!$emp_data) {
                throw new Exception("Employee contract data not found for emp_id: $emp_id");
            }
            
            $contract_info = $this->parseContractPeriod($emp_data['vac_period']);
            $total_vac_days = $contract_info['total_days'];

            $period_dates = $this->calculateContractPeriod(
                $emp_data['joining_date'], 
                $contract_info['years']
            );
            
            // Get the sum of all GM-approved vacation days within the current period.
            $used_days = $this->getUsedVacationDays(
                $emp_id,
                $period_dates['start'],
                $period_dates['end']
            );
            
            $carryover = $this->calculateCarryover(
                $emp_id,
                $emp_data['vac_period'],
                $total_vac_days,
                $period_dates['start'],
                $contract_info['years']
            );
            
            $earned_days = $this->calculateEarnedDays(
                $total_vac_days,
                $period_dates['start'],
                $period_dates['end']
            );
            
            // Calculate final balance figures.
            $remaining_balance = $total_vac_days - $used_days;
            $available_balance = $earned_days + $carryover - $used_days;

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
            error_log("Failed to get calculated balance for emp_id $emp_id: " . $e->getMessage());
            return null;
        }
    }
    
    // --- Private Helper Methods ---

    /**
     * Gets the total number of used vacation days that are fully approved by the GM.
     * It excludes emergency fly vacations from the total.
     */
    private function getUsedVacationDays($emp_id, $period_start, $period_end) {
        // This query now correctly checks for 'gm_approved' status.
        $query = "SELECT COALESCE(SUM(`vacdays`), 0) AS `used_days` 
                  FROM `emp_vacation` 
                  WHERE `emp_id` = ? 
                    AND `approval_status` = 'gm_approved'
                    AND NOT (`vac_type` = 'Fly' AND `fly_type` = 'emergency')
                    AND `start_date` BETWEEN ? AND ?";
                    
        $stmt = $this->conDB->prepare($query);
        $stmt->bind_param(
            "sss", 
            $emp_id,
            $period_start->format('Y-m-d'),
            $period_end->format('Y-m-d')
        );
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
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
        $joining = new DateTime($joining_date);
        $today = new DateTime();
        
        $years_employed = $today->diff($joining)->y;
        $contracts_completed = floor($years_employed / $contract_years);
        
        $current_start = (clone $joining)->add(new DateInterval("P" . ($contracts_completed * $contract_years) . "Y"));
        $current_end = (clone $current_start)->add(new DateInterval("P" . $contract_years . "Y"));
        
        return ['start' => $current_start, 'end' => $current_end];
    }
    
    private function calculateCarryover($emp_id, $contract_id, $total_vac_days, $current_start, $contract_years) {
        // Calculate the start date of the previous period.
        $prev_period_start = (clone $current_start)->sub(new DateInterval("P" . $contract_years . "Y"));
        
        $query = "SELECT remaining_balance 
                  FROM emp_vacation_balance 
                  WHERE emp_id = ? AND contract_id = ? AND period_start = ?";
        $stmt = $this->conDB->prepare($query);
        $stmt->bind_param("sis", $emp_id, $contract_id, $prev_period_start->format('Y-m-d'));
        $stmt->execute();
        $prev_balance = (float)($stmt->get_result()->fetch_assoc()['remaining_balance'] ?? 0);
        
        // The maximum allowed carryover is 50% of the total vacation days for the contract.
        $max_carryover = $total_vac_days * 0.5;
        
        return min($prev_balance, $max_carryover);
    }
    
    private function calculateEarnedDays($total_vac_days, $period_start, $period_end) {
        $today = new DateTime();
        // If the current date is before the period starts, no days have been earned.
        if ($today < $period_start) return 0;
        // If the current date is after the period has ended, all days have been earned.
        if ($today >= $period_end) return $total_vac_days;

        $days_elapsed = $today->diff($period_start)->days;
        $total_days_in_period = $period_end->diff($period_start)->days;

        if ($total_days_in_period == 0) return 0;
        
        // Prorate the earned days based on how much of the period has passed.
        // return round($total_vac_days * ($days_elapsed / $total_days_in_period), 2);
        return floor($total_vac_days * ($days_elapsed / $total_days_in_period));
    }
}
