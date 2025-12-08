<?php
/**
 * Manager Evaluation Acknowledgment/Objection Handler
 * 
 * Functions to manage manager acknowledgment and objection of employee evaluations
 * This file should be included in evaluation-related pages
 */

/**
 * Update evaluation with manager acknowledgment
 * 
 * @param mysqli $conDB Database connection
 * @param int $eval_id Evaluation ID
 * @param int $manager_id Manager employee ID
 * @param string $status 'acknowledged' or 'objected'
 * @param string $objection_note Optional objection note
 * @return array Result with status and message
 */
function update_manager_acknowledgment($conDB, $eval_id, $manager_id, $status, $objection_note = '') {
    $result = ['status' => false, 'message' => ''];
    
    // Validate status
    if (!in_array($status, ['acknowledged', 'objected'])) {
        $result['message'] = 'Invalid acknowledgment status';
        return $result;
    }
    
    // If objected, require a note
    if ($status === 'objected' && empty($objection_note)) {
        $result['message'] = 'Objection note is required when objecting to an evaluation';
        return $result;
    }
    
    // Prepare the update query
    $stmt = $conDB->prepare("UPDATE `emp_evaluations` 
                            SET `manager_acknowledgment_status` = ?,
                                `manager_acknowledgment_date` = NOW(),
                                `manager_acknowledged_by` = ?,
                                `manager_objection_note` = ?
                            WHERE `id` = ?");
    
    if (!$stmt) {
        $result['message'] = 'Database error: ' . $conDB->error;
        return $result;
    }
    
    // Bind parameters (objection_note can be NULL for acknowledged status)
    $note = ($status === 'objected') ? $objection_note : NULL;
    $stmt->bind_param('sisi', $status, $manager_id, $note, $eval_id);
    
    if ($stmt->execute()) {
        $result['status'] = true;
        $result['message'] = ucfirst($status) . ' successfully recorded';
        $stmt->close();
        return $result;
    } else {
        $result['message'] = 'Database error: ' . $stmt->error;
        $stmt->close();
        return $result;
    }
}

/**
 * Get evaluation acknowledgment status
 * 
 * @param mysqli $conDB Database connection
 * @param int $eval_id Evaluation ID
 * @return array Acknowledgment data
 */
function get_evaluation_acknowledgment($conDB, $eval_id) {
    $stmt = $conDB->prepare("SELECT 
                                `manager_acknowledgment_status`,
                                `manager_objection_note`,
                                `manager_acknowledgment_date`,
                                `manager_acknowledged_by`,
                                e.name as manager_name,
                                e.emptype as manager_role
                            FROM `emp_evaluations` ev
                            LEFT JOIN `employees` e ON ev.`manager_acknowledged_by` = e.`emp_id`
                            WHERE ev.`id` = ?");
    
    if (!$stmt) {
        return null;
    }
    
    $stmt->bind_param('i', $eval_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    $stmt->close();
    
    return $data;
}

/**
 * Get pending acknowledgments for management report
 * 
 * @param mysqli $conDB Database connection
 * @param string $filter 'pending', 'acknowledged', or 'objected'
 * @return array List of evaluations
 */
function get_acknowledgment_report($conDB, $filter = 'pending') {
    $valid_filters = ['pending', 'acknowledged', 'objected'];
    
    if (!in_array($filter, $valid_filters)) {
        return [];
    }
    
    $query = "SELECT 
                ev.`id`,
                ev.`emp_id`,
                ev.`evaluation_date`,
                ev.`manager_acknowledgment_status`,
                ev.`manager_acknowledgment_date`,
                ev.`manager_objection_note`,
                emp.`name` as employee_name,
                emp.`emp_id` as employee_id,
                emp.`position` as employee_position,
                mgr.`name` as manager_name,
                mgr.`emptype` as manager_role
            FROM `emp_evaluations` ev
            JOIN `employees` emp ON ev.`emp_id` = emp.`emp_id`
            LEFT JOIN `employees` mgr ON ev.`manager_acknowledged_by` = mgr.`emp_id`
            WHERE ev.`manager_acknowledgment_status` = ?
            ORDER BY ev.`evaluation_date` DESC";
    
    $stmt = $conDB->prepare($query);
    
    if (!$stmt) {
        return [];
    }
    
    $stmt->bind_param('s', $filter);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    
    $stmt->close();
    return $data;
}

/**
 * Check if current user can acknowledge evaluations
 * 
 * @param string $user_type User type
 * @param string $user_role User role
 * @return bool True if user can acknowledge
 */
function can_acknowledge_evaluations($user_type, $user_role) {
    // Only managers who conducted the evaluation can acknowledge
    // This should be checked against the actual evaluation record
    $acknowledgment_roles = [
        'DPT_Manager',      // Department Manager
        'HR_Manager',       // HR Manager
        'IT_Team_Manager',  // IT Team Manager
        'HR_Team_Manager',  // HR Team Manager
        'Finance_Team_Manager', // Finance Team Manager
        'Executive_Team_Manager' // Executive Team Manager
    ];
    
    return in_array($user_role, $acknowledgment_roles) || $user_type === 'administrator';
}

/**
 * Check if current user can view acknowledgment report
 * 
 * @param string $user_type User type
 * @param string $user_role User role
 * @return bool True if user can view report
 */
function can_view_acknowledgment_report($user_type, $user_role) {
    // Only specific management roles can view reports
    $report_roles = [
        'Administrator',
        'HR_Recruitment',
        'HR_Manager',
        'HR_Senior_BP',
        'GM'
    ];
    
    return in_array($user_role, $report_roles) || $user_type === 'administrator';
}
?>
