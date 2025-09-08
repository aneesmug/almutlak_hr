<?php
/****************************************************************
 * MODIFICATION SUMMARY (012-role_check.php):
 * 1. ADDED FINANCE ROLES: New `elseif` conditions have been added to specifically identify 'Finance_Manager' and 'Finance_Assistant' based on their department (dept 2).
 * 2. REFINED DPT_MANAGER LOGIC: The final condition has been updated to correctly assign the 'DPT_Manager' role to administrators or managers who are NOT in the specialized departments (Finance, HR, or GM).
 * 3. COMPLETE ROLE COVERAGE: This logic now correctly interprets every user role present in your `admin_login` table, ensuring permissions are applied accurately across the board.
 ****************************************************************/

// --- Centralized Role Definition ---
$user_role = 'Employee'; // Default role

// Fetch user data for role assignment if not already available
if (!isset($user_type) || !isset($emp_type) || !isset($user_dept)) {
    $stmt_role_check = $conDB->prepare("SELECT `user_type`, `emp_type`, `dept` FROM `admin_login` WHERE `id_iqama` = ?");
    $stmt_role_check->bind_param("s", $username);
    $stmt_role_check->execute();
    $result_role_check = $stmt_role_check->get_result();
    if ($result_role_check->num_rows == 1) {
        $user_data_role = $result_role_check->fetch_assoc();
        $user_type = $user_data_role['user_type'];
        $emp_type = $user_data_role['emp_type'];
        $user_dept = $user_data_role['dept'];
    }
    $stmt_role_check->close();
}

// Determine role based on a clear hierarchy
if ($user_type == "gm" && $emp_type == "Manager" && $user_dept == 10) {
    $user_role = 'GM';
} elseif ($user_type == "hr" && $user_dept == 5) {
    $user_role = 'HR_Manager';
} elseif ($user_type == "assistant" && $user_dept == 5) {
    $user_role = 'HR_Assistant';
} elseif ($emp_type == "Manager" && $user_dept == 2) { // Dept 2 is Finance
    $user_role = 'Finance_Manager';
} elseif ($user_type == "assistant" && $user_dept == 2) { // Dept 2 is Finance
    $user_role = 'Finance_Assistant';
} elseif (($user_type == "administrator" || $emp_type == "Manager") && !in_array($user_dept, [2, 5, 10])) {
    $user_role = 'DPT_Manager';
}
?>
