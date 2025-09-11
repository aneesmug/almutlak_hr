<?php

/****************************************************************
 * MODIFICATION SUMMARY:
 * 1. SIMPLIFIED FLY STATUS LOGIC: The logic for updating the employee's `fly` status has been simplified and made more robust. It is now triggered for any vacation type except 'Encashed' upon final approval. This ensures that whenever a request is finalized (`review` is set to 'C'), the employee is correctly marked as away.
 * 2. RESTRUCTURED APPROVAL LOGIC: The `approveVacationRequest` method now explicitly checks if the request is moving to the 'gm_approved' status and sets `review = 'C'` accordingly.
 * 3. REMOVED HR WORKFLOW EXCEPTION: The special condition that automatically approved vacation requests for HR department employees has been removed.
 * 4. ADDED HR MANAGER FINAL APPROVAL: The `getApprovalUpdateData` method allows an HR Manager to move a request from 'hr_manager_approved' (post-IT clearance) to the final 'gm_approved' status.
 ****************************************************************/
require_once 'vacation_calculator.php';

/**
 * Handles the processing of vacation requests, including submission, approvals, and rejections.
 * The balance is only updated upon final approval.
 */
class VacationProcessor
{
    private $conDB;
    private $calculator;

    public function __construct($dbConnection)
    {
        $this->conDB = $dbConnection;
        $this->calculator = new VacationCalculator($dbConnection);
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    }

    /**
     * Submits a new vacation request.
     */
    public function submitVacationRequest($emp_id, $request_data)
    {
        try {
            $this->validateRequestData($request_data);

            // Determine if the leave should be deducted from monthly salary (treated as absent)
            $deductible_leave_types = ['Casual Leave', 'Other Leave'];
            $is_deductible = in_array($request_data['vac_type'], $deductible_leave_types) ? 1 : 0;

            $vac_days = 0;
            if ($request_data['vac_type'] === 'Encashed') {
                $balance_info = $this->calculator->getLatestBalance($emp_id);
                if (!$balance_info) {
                    throw new Exception("No vacation balance record exists. An encashment request cannot be submitted.");
                }
                $vac_days = $balance_info['remaining_balance'] ?? 0;
                if ($vac_days <= 0) {
                    throw new Exception("No available vacation days to encash.");
                }
                $request_data['start_date'] = date('Y-m-d');
                $request_data['end_date'] = date('Y-m-d');
                $request_data['fly_type'] = 'annual';
            } else {
                $vac_days = $this->calculateVacationDays(
                    $request_data['start_date'],
                    $request_data['end_date']
                );
            }

            // Pass the is_deductible flag to the insert method
            $vacation_id = $this->insertVacationRecord($emp_id, $request_data, $vac_days, $is_deductible);

            return [
                'success' => true,
                'vacation_id' => $vacation_id,
                'message' => 'Vacation request submitted successfully. It is now pending approval.'
            ];
        } catch (Exception $e) {
            error_log("Vacation submission error for emp_id $emp_id: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to submit vacation request: ' . $e->getMessage()];
        }
    }

    /**
     * Approves a vacation request and updates its status.
     * If the approver is the GM (or an HR Manager acting as final approver), it triggers the final balance calculation.
     */
    public function approveVacationRequest($vacation_id, $approver_role, $ticket_pay = null, $permit_fee = null)
    {
        try {
            $vacation = $this->getVacationRecord($vacation_id);
            if (!$vacation) {
                throw new Exception("Vacation request with ID $vacation_id not found.");
            }
    
            $current_status = $vacation['approval_status'];
            $update_data = $this->getApprovalUpdateData($approver_role, $current_status);
    
            // --- IT Clearance Check ---
            if ($approver_role === 'HR_Manager' && $update_data['new_status'] === 'hr_manager_approved' && $this->employeeHasAssets($vacation['emp_id'])) {
                $update_data['new_status'] = 'it_pending';
            }
    
            // --- Build Query ---
            $is_final_approval = ($update_data['new_status'] === 'gm_approved');
            
            $sql_parts = [];
            $params = [];
            $types = "";
    
            // Status
            $sql_parts[] = "approval_status = ?";
            $params[] = $update_data['new_status'];
            $types .= "s";
    
            // Timestamp
            $sql_parts[] = "{$update_data['approval_field']} = NOW()";
    
            // Review status for final approval
            if ($is_final_approval) {
                $sql_parts[] = "review = 'C'";
            }
            
            // HR Assistant specific fields
            if ($approver_role === 'HR_Assistant') {
                $sql_parts[] = "ticket_pay = ?";
                $params[] = $ticket_pay;
                $types .= "d";
                
                $sql_parts[] = "permit_fee = ?";
                $params[] = $permit_fee;
                $types .= "d";
            }
            
            $query = "UPDATE emp_vacation SET " . implode(", ", $sql_parts) . " WHERE id = ?";
            $params[] = $vacation_id;
            $types .= "i";
            
            $stmt = $this->conDB->prepare($query);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            
            // --- Post-Approval Actions ---
            if ($is_final_approval) {
                $non_balance_deductible_leaves = [
                    'Sick Leave', 'Casual Leave', 'Maternity Leave', 
                    'Business Trip', 'Compensatory Leave', 'Other Leave'
                ];
    
                if (!in_array($vacation['vac_type'], $non_balance_deductible_leaves)) {
                    $this->calculator->calculateVacationBalance($vacation['emp_id'], $vacation_id);
                }
    
                if ($vacation['vac_type'] !== 'Encashed') {
                    $this->updateEmployeeFlyStatus($vacation['emp_id'], 1);
                }
            }
    
            return ['success' => true, 'message' => 'Vacation approval status has been updated.'];
        } catch (Exception $e) {
            error_log("Vacation approval error for vacation_id $vacation_id: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to approve vacation request: ' . $e->getMessage()];
        }
    }

    public function approveITClearance($vacation_id, $it_notes, $approver_id)
    {
        try {
            $query = "UPDATE emp_vacation SET 
                        approval_status = 'hr_manager_approved', 
                        it_approval_status = 'cleared',
                        it_notes = ?,
                        it_approver_id = ?,
                        it_approval_date = NOW()
                      WHERE id = ? AND (approval_status = 'it_pending' OR (approval_status = 'hr_manager_approved' AND it_approval_status = 'pending'))";
            $stmt = $this->conDB->prepare($query);
            $stmt->bind_param("ssi", $it_notes, $approver_id, $vacation_id);
            $stmt->execute();

            if ($stmt->affected_rows > 0) {
                return ['success' => true];
            } else {
                throw new Exception("Request not found, already approved, or you do not have permission.");
            }
        } catch (Exception $e) {
            error_log("IT Clearance Error for vacation_id $vacation_id: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Rejects a vacation request.
     */
    public function rejectVacationRequest($vacation_id, $rejection_note, $approver_role)
    {
        try {
            if (empty($rejection_note) || empty($vacation_id) || empty($approver_role)) {
                throw new Exception("Missing data for rejection.");
            }
            $query = "UPDATE emp_vacation SET 
                        approval_status = 'rejected',
                        note = ?,
                        review = 'R'
                      WHERE id = ?";
            $stmt = $this->conDB->prepare($query);
            $full_note = "Rejected by $approver_role: " . $rejection_note;
            $stmt->bind_param("si", $full_note, $vacation_id);
            $stmt->execute();

            if ($stmt->affected_rows === 0) {
                throw new Exception("Vacation request not found or no update was made.");
            }

            return ['success' => true, 'message' => 'Vacation request has been rejected.'];
        } catch (Exception $e) {
            error_log("Vacation rejection error for vacation_id $vacation_id: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to reject vacation request: ' . $e->getMessage()];
        }
    }

    // --- Private Helper Methods ---

    private function insertVacationRecord($emp_id, $data, $vac_days, $is_deductible)
    {
        $query = "INSERT INTO `emp_vacation` 
                  (`emp_id`, `start_date`, `user_update`, `return_date`, `vacdays`, `vac_type`, `fly_type`, 
                   `remarks`, `review`, `note`, `replacement_person`, `last_vac_date`, `next_vac_date`, 
                   `ticket_pay`, `permit_fee`, `is_deductible`, `approval_status`, `created_at`)
                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'A', ?, ?, ?, ?, ?, ?, ?, 'apply', NOW())";
        $stmt = $this->conDB->prepare($query);
        if (!$stmt) {
            throw new Exception("Database prepare failed: " . $this->conDB->error);
        }

        $start_date_var = $data['start_date'];
        $end_date_var = $data['end_date'];
        $next_vac_var = !empty($end_date_var) ? date('Y-m-d', strtotime($end_date_var . ' +1 year')) : null;
        $user_update_var = $_SESSION['user_name'] ?? 'System';
        $ticket_pay = null;
        $permit_fee = null;

        $stmt->bind_param(
            "ssssisssssssddi",
            $emp_id,
            $start_date_var,
            $user_update_var,
            $end_date_var,
            $vac_days,
            $data['vac_type'],
            $data['fly_type'],
            $data['remarks'],
            $data['note'],
            $data['replacement_person'],
            $data['last_vac_date'],
            $next_vac_var,
            $ticket_pay,
            $permit_fee,
            $is_deductible
        );
        if (!$stmt->execute()) {
            throw new Exception("Database execute failed: " . $stmt->error);
        }
        return $this->conDB->insert_id;
    }

    public function getVacationRecord($vacation_id)
    {
        $query = "SELECT * FROM emp_vacation WHERE id = ?";
        $stmt = $this->conDB->prepare($query);
        $stmt->bind_param("i", $vacation_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    private function getApprovalUpdateData($approver_role, $current_status)
    {
        switch ($approver_role) {
            case 'DPT_Manager':
                if ($current_status === 'apply') {
                    return ['new_status' => 'pending', 'approval_field' => 'dep_manager_approval'];
                }
                break;
            case 'HR_Assistant':
                if ($current_status === 'pending') {
                    return ['new_status' => 'hr_assistant_approved', 'approval_field' => 'hr_assistant_approval'];
                }
                break;
            case 'HR_Manager':
                if ($current_status === 'apply' || $current_status === 'hr_assistant_approved') {
                    return ['new_status' => 'hr_manager_approved', 'approval_field' => 'hr_manager_approval'];
                }
                if ($current_status === 'hr_manager_approved') {
                    return ['new_status' => 'gm_approved', 'approval_field' => 'gm_approval'];
                }
                break;
            case 'GM':
                if ($current_status === 'hr_manager_approved') {
                    return ['new_status' => 'gm_approved', 'approval_field' => 'gm_approval'];
                }
                break;
        }
        throw new Exception("Invalid approval action. Role '$approver_role' cannot approve a request with status '$current_status'.");
    }

    private function validateRequestData($data)
    {
        if (empty($data['vac_type'])) {
            throw new Exception("Vacation type is a required field.");
        }
        if ($data['vac_type'] !== 'Encashed') {
            if (empty($data['start_date']) || empty($data['end_date'])) {
                throw new Exception("Start date and end date are required for this vacation type.");
            }
            if (strtotime($data['start_date']) === false || strtotime($data['end_date']) === false) {
                throw new Exception("The provided date format is invalid.");
            }
        }
        return true;
    }

    private function calculateVacationDays($start_date, $end_date)
    {
        $start = new DateTime($start_date);
        $end = new DateTime($end_date);
        return $end->diff($start)->days + 1;
    }

    private function updateEmployeeFlyStatus($empId, $flystatus)
    {
        $stmtUpdateFly = $this->conDB->prepare("UPDATE `employees` SET `fly` = ? WHERE `emp_id` = ?");
        $stmtUpdateFly->bind_param("is", $flystatus, $empId);
        return $stmtUpdateFly->execute();
    }

    private function employeeHasAssets($emp_id)
    {
        $query = "SELECT COUNT(*) as asset_count FROM employee_assets WHERE emp_id = ? AND status = 'Assigned'";
        $stmt = $this->conDB->prepare($query);
        $stmt->bind_param("s", $emp_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        return ($result['asset_count'] > 0);
    }

    public function recordVacationReturn($vacation_id, $actual_return_date)
    {
        try {
            if (empty($vacation_id) || empty($actual_return_date)) {
                throw new Exception("Vacation ID and actual return date are required.");
            }
            $vacation = $this->getVacationRecord($vacation_id);
            if (!$vacation) {
                throw new Exception("Vacation request with ID $vacation_id not found.");
            }
            if ($vacation['approval_status'] !== 'gm_approved') {
                throw new Exception("Cannot record a return for a vacation that is not fully approved.");
            }
            $new_vac_days = $this->calculateVacationDays($vacation['start_date'], $actual_return_date);
            if ($new_vac_days <= 0) {
                throw new Exception("The return date must be after the start date.");
            }
            $query = "UPDATE emp_vacation SET return_date = ?, vacdays = ? WHERE id = ?";
            $stmt = $this->conDB->prepare($query);
            $stmt->bind_param("sii", $actual_return_date, $new_vac_days, $vacation_id);
            $stmt->execute();
            $this->calculator->calculateVacationBalance($vacation['emp_id'], $vacation_id);
            $this->updateEmployeeFlyStatus($vacation['emp_id'], 0);
            return ['success' => true, 'message' => 'Employee return has been recorded successfully. Vacation balance is updated.'];
        } catch (Exception $e) {
            error_log("Vacation return error for vacation_id $vacation_id: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to record employee return: ' . $e->getMessage()];
        }
    }

    private function getEmployeeDetails($emp_id)
    {
        $query = "SELECT * FROM employees WHERE emp_id = ?";
        $stmt = $this->conDB->prepare($query);
        $stmt->bind_param("s", $emp_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    private function updateVacationStatus($vacation_id, $new_status)
    {
        $query = "UPDATE emp_vacation SET approval_status = ? WHERE id = ?";
        $stmt = $this->conDB->prepare($query);
        $stmt->bind_param("si", $new_status, $vacation_id);
        return $stmt->execute();
    }
}

