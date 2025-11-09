<?php
/****************************************************************
 * MODIFICATION SUMMARY (016-role_check.php):
 * 1. DEPARTMENT-BASED ROLE SYSTEM: Roles now determined by user_type AND department
 * 2. REMOVED 'assistant' USER TYPE: No more generic assistant role
 * 3. DEPARTMENT MAPPING: Each department gets specific team roles:
 *    - dept 5 → HR_Team / HR_Team_Manager
 *    - dept 2 → Finance_Team / Finance_Team_Manager
 *    - dept 6 → IT_Team / IT_Team_Manager
 *    - dept 10 → Executive_Team / Executive_Team_Manager
 * 4. MANAGER ROLES: Managers get department-specific roles based on their department
 * 5. SPECIFIC ROLES: user_type values like 'hr_senior_bp', 'finance_officer' override department logic
 * 6. LEGACY SUPPORT: Added 'it', 'hr', 'finance' legacy user_type mappings for backward compatibility
 ****************************************************************/

// --- Centralized Role Definition ---
$user_role = 'Employee'; // Default role

// Fetch user data for role assignment if not already available
if (!isset($user_type) || !isset($emp_type) || !isset($user_dept) || !isset($empid)) {
    $stmt_role_check = $conDB->prepare("SELECT al.`user_type`, al.`emp_type`, al.`dept`, al.`emp_id`, e.`emp_type` as employee_type 
                                        FROM `admin_login` al 
                                        LEFT JOIN `employees` e ON al.`emp_id` = e.`emp_id`
                                        WHERE al.`id_iqama` = ?");
    $stmt_role_check->bind_param("s", $username);
    $stmt_role_check->execute();
    $result_role_check = $stmt_role_check->get_result();
    if ($result_role_check->num_rows == 1) {
        $user_data_role = $result_role_check->fetch_assoc();
        $user_type = $user_data_role['user_type'];
        $emp_type = $user_data_role['emp_type'];
        $user_dept = $user_data_role['dept'];
        $empid = $user_data_role['emp_id'];
        $employee_type = $user_data_role['employee_type'] ?? $emp_type;
    }
    $stmt_role_check->close();
}

// Role mapping from admin_login.user_type to standardized role names
$role_mapping = [
    'administrator' => 'Administrator',
    'gm' => 'GM',
    'hr_senior_bp' => 'HR_Senior_BP',
    'hr_operations' => 'HR_Operations',
    'hr_supervisor' => 'HR_Supervisor',
    'hr_recruitment' => 'HR_Recruitment',
    'hr_payroll' => 'HR_Payroll',
    'gr_officer' => 'GR_Officer',
    'finance_officer' => 'Finance_Officer',
    'auditor' => 'Auditor',
    'hr' => 'HR_Manager',
    'it' => 'IT_Team_Manager',  // Legacy IT role mapped to IT Team Manager
    'finance' => 'Finance_Manager',
    'dept_user' => 'DPT_Manager',
    'employee' => 'Employee',
];

// Department-based role mapping (when user_type matches department role pattern)
$dept_role_mapping = [
    5 => 'HR_Team',           // HR Department
    2 => 'Finance_Team',      // Finance Department
    6 => 'IT_Team',           // IT Department
    10 => 'Executive_Team',   // GM Department
];

// Assign role based on user_type from database
if (isset($role_mapping[$user_type])) {
    $user_role = $role_mapping[$user_type];
}
// Check if user_type is 'dept_user' or emp_type is 'Manager'
elseif ($user_type == 'dept_user' || $emp_type == 'Manager') {
    // Department managers get specific role based on their department
    if (isset($dept_role_mapping[$user_dept])) {
        $user_role = $dept_role_mapping[$user_dept] . '_Manager';
    } else {
        $user_role = 'DPT_Manager';
    }
}
// Fallback to employees table emp_type
elseif (isset($employee_type)) {
    if ($employee_type == 'Manager') {
        // Assign department-specific manager role
        if (isset($dept_role_mapping[$user_dept])) {
            $user_role = $dept_role_mapping[$user_dept] . '_Manager';
        } else {
            $user_role = 'DPT_Manager';
        }
    } elseif ($employee_type == 'Supporter') {
        $user_role = 'Employee';
    }
}
// If still no role assigned, check department for team roles
else {
    if (isset($dept_role_mapping[$user_dept])) {
        $user_role = $dept_role_mapping[$user_dept];
    }
}
?>
