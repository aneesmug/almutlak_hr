<?php
/**
 * Supervisor Validation Helper
 * 
 * This file provides a centralized function to validate that an employee has a direct supervisor assigned
 * before allowing them to create any type of request (vacation, loan, resignation, etc.)
 * 
 * Created: December 10, 2025
 */

if (!function_exists('validate_employee_supervisor')) {
    /**
     * Check if an employee has a direct supervisor assigned
     * 
    * Higher management (GM, Administrator, CEO) are exempt from supervisor requirement
     * 
     * @param mysqli $conDB Database connection
     * @param string|int $emp_id Employee ID to check
     * @return array Returns ['valid' => bool, 'supervisor_id' => string|null, 'message' => string]
     */
    function validate_employee_supervisor($conDB, $emp_id) {
        if (empty($emp_id)) {
            return [
                'valid' => false,
                'supervisor_id' => null,
                'message' => __('invalid_employee_id', 'Invalid employee ID')
            ];
        }

        // Query to get supervisor assignment, user type, and employee type
        $sql = "SELECT e.`supervisor_id`, e.`name`, e.`emptype`, al.`user_type` 
                FROM `employees` e 
                LEFT JOIN `admin_login` al ON e.`emp_id` = al.`emp_id` 
                WHERE e.`emp_id` = ? 
                LIMIT 1";
        $stmt = $conDB->prepare($sql);
        
        if (!$stmt) {
            return [
                'valid' => false,
                'supervisor_id' => null,
                'message' => __('database_error', 'Database error occurred')
            ];
        }

        $stmt->bind_param('s', $emp_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $stmt->close();
            return [
                'valid' => false,
                'supervisor_id' => null,
                'message' => __('employee_not_found', 'Employee not found')
            ];
        }

        $row = $result->fetch_assoc();
        $stmt->close();

        $supervisor_id = $row['supervisor_id'] ?? null;
        $emp_name = $row['name'] ?? 'Unknown';
        $emptype = $row['emptype'] ?? '';
        $user_type = $row['user_type'] ?? '';

        // Exempt only top-level roles from supervisor requirement
        $exempt_user_types = ['gm', 'ceo'];

        if (in_array($user_type, $exempt_user_types)) {
            return [
                'valid' => true,
                'supervisor_id' => $supervisor_id, // May be null for exempt users
                'message' => __('exempt_from_supervisor_check', 'User exempt from supervisor requirement')
            ];
        }

        // Check if supervisor is assigned for non-exempt employees
        if (empty($supervisor_id) || trim($supervisor_id) === '') {
            return [
                'valid' => false,
                'supervisor_id' => null,
                'message' => __('supervisor_not_assigned', 'Direct supervisor not assigned. Please contact HR to assign a direct supervisor before submitting any requests.')
            ];
        }

        // Supervisor is assigned
        return [
            'valid' => true,
            'supervisor_id' => $supervisor_id,
            'message' => __('supervisor_validation_success', 'Supervisor validation successful')
        ];
    }
}

/**
 * Send standardized JSON error response for supervisor validation failure
 * 
 * @param string $message Error message to display
 * @param int $http_code HTTP status code (default: 200 for proper AJAX handling)
 */
if (!function_exists('send_supervisor_validation_error')) {
    function send_supervisor_validation_error($message = null, $http_code = 200) {
        if ($message === null) {
            $message = __('supervisor_not_assigned', 'Direct supervisor not assigned. Please contact HR to assign a direct supervisor before submitting any requests.');
        }

        http_response_code($http_code);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'title' => __('supervisor_required', 'Supervisor Required'),
            'message' => $message,
            'type' => 'error'
        ]);
        exit;
    }
}
