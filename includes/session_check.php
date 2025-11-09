<?php
/****************************************************************
 * MODIFICATION SUMMARY (021-session_check.php):
 * 1. DEPARTMENT-BASED PERMISSIONS: Role variables now check both user_type AND department
 * 2. REMOVED 'assistant' ROLE: No more generic assistant user type
 * 3. TEAM-BASED CHECKS: Added department team variables:
 *    - $isDeptHr: Anyone in HR Department (dept 5)
 *    - $isDeptFinance: Anyone in Finance Department (dept 2)
 *    - $isItTeam: Anyone in IT Department (dept 6)
 * 4. COMBINED ROLE GROUPS: 
 *    - $isHR: Combines specific HR roles + anyone in dept 5
 *    - $isFinance: Combines Finance Officer + Auditor + anyone in dept 2
 * 5. FLEXIBLE PERMISSIONS: Department-based access allows easy team member management
 ****************************************************************/
/****************************************************************
 * MODIFICATION SUMMARY (018-session_check.php):
 * 1. ADDED SYSTEM ADMIN VARIABLE: A new variable, `$is_system_admin`, is now created. It is set to `true` only if the user's `user_type` from the database is 'administrator'.
 * 2. SEPARATION OF CONCERNS: This change separates the user's system-wide permissions (like seeing the admin menu) from their functional role in a workflow (like approving vacations as a DPT_Manager).
 * 3. GLOBAL AVAILABILITY: This new variable is now available globally, allowing other files like `main_menu.php` to use it for permission checks.
 ****************************************************************/
/****************************************************************
 * MODIFICATION SUMMARY (001-session_check.php): 
 * 1. RELIABLE PAGE DETECTION: Changed the method for getting the current page from using `REQUEST_URI` to `PHP_SELF`. This provides a more consistent and reliable way to identify the executing script, fixing the bug that caused incorrect redirects for employees.
 ****************************************************************/
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// --- 1. Authentication Check ---
if (!isset($_SESSION['auth_user']) || !is_array($_SESSION['auth_user'])) {
    // Check if remember_me cookie exists before redirecting
    if (isset($_COOKIE['remember_me'])) {
        // Redirect to index.php which will handle the auto-login
        header("Location: ./index.php");
        exit();
    }
    session_unset();
    session_destroy();
    header("Location: ./index.php");
    exit();
}

// --- 2. Session Timeout Handling ---
$timeout_duration = 3600; // 60 minutes
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout_duration) {
    // Check if remember_me cookie exists
    if (isset($_COOKIE['remember_me'])) {
        // User has remember_me enabled, redirect to index for auto-login instead of destroying session
        session_unset();
        session_destroy();
        header("Location: ./index.php");
        exit();
    }
    // No remember_me cookie, normal timeout behavior
    session_unset();
    session_destroy();
    $_SESSION['error_message'] = "Your session has timed out due to inactivity.";
    header("Location: ./index.php");
    exit();
}
$_SESSION['last_activity'] = time();

// --- 3. Fetch Full User & Employee Record ---
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/init.php';
include_once __DIR__ . '/helper_functions.php';

$user_id_for_query = $_SESSION['auth_user']['user_id'];

$query = "SELECT 
            al.*, 
            e.name AS efullname, 
            e.avatar AS eavatar,
            e.sex
          FROM `admin_login` al
          LEFT JOIN `employees` e ON al.emp_id = e.emp_id
          WHERE al.id_iqama = ? LIMIT 1";

$stmt = mysqli_prepare($conDB, $query);
mysqli_stmt_bind_param($stmt, "s", $user_id_for_query);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$emprow = mysqli_fetch_assoc($result);

if (!$emprow) {
    session_unset();
    session_destroy();
    header("Location: ./index.php");
    exit();
}
mysqli_stmt_close($stmt);

// --- 4. Set Global User Variables ---
$username = $_SESSION['auth_user']['user_id'];
$user_type = $emprow['user_type'];
$emp_type = $emprow['emp_type'];
$user_dept = $emprow['dept'];
$fname = $emprow['efullname'];
$avatar = $emprow['eavatar'];
$empid = $emprow['emp_id'];

$userwel = parseName($fname);
$usracc = ucfirst($user_type);

$checkGander = ($emprow['sex'] == 1) ? './assets/emp_pics/defult.png' : './assets/emp_pics/defultFemale.jpg';
$avatar = (!empty($avatar) && file_exists(ltrim($avatar, './'))) ? $avatar : $checkGander;

$_SESSION['user'] = $username;
$_SESSION['user_type'] = $user_type;
$_SESSION['verify_user_type'] = $user_type;
$_SESSION['user_dept'] = $user_dept;
$_SESSION['empid'] = $empid;

// --- 5. Centralized Role Definition ---
require_once __DIR__ . '/role_check.php';

// --- 6. Role-Based Permission Variables ---
// These variables provide easy permission checking throughout the application
// All roles are dynamically assigned based on admin_login.user_type and department

// Administrative Roles
$is_system_admin = ($user_type === 'administrator');
$isGM = ($user_type === 'gm');

// HR Team Roles (based on user_type)
$isHR_Senior_BP = ($user_type === 'hr_senior_bp');
$isHR_Operations = ($user_type === 'hr_operations');
$isHR_Supervisor = ($user_type === 'hr_supervisor');
$isHR_Recruitment = ($user_type === 'hr_recruitment');
$isHR_Payroll = ($user_type === 'hr_payroll');

// Combined HR roles (any HR position or HR department)
$isHR = ($user_type === 'hr' || $isHR_Senior_BP || $isHR_Operations || $isHR_Supervisor || $isHR_Recruitment || $isHR_Payroll || $user_dept == 5);
$isDeptHr = ($user_dept == 5); // Anyone in HR Department

// Finance & Audit Roles
$isFinance_Officer = ($user_type === 'finance_officer');
$isAuditor = ($user_type === 'auditor');
$isFinance = ($user_type === 'finance_officer' || $user_type === 'auditor' || $user_dept == 2);
$isDeptFinance = ($user_dept == 2); // Anyone in Finance Department

// Other Specific Roles
$isGR_Officer = ($user_type === 'gr_officer');
$isItTeam = ($user_dept == 6); // Anyone in IT Department

// General Role Types
$isEmployee = ($user_type === 'employee');
$isDeptManager = ($user_type === 'dept_user' || $user_role === 'DPT_Manager' || $emp_type === 'Manager');

// Legacy variables (for backward compatibility)
$isAssistant = false; // No more assistant role
$isItAssistant = ($user_dept == 6); // IT team member

// --- 7. Page Access Control ---
$current_page = strtolower(basename($_SERVER['PHP_SELF']));

const EMPLOYEE_ALLOWED_PAGES = ['profile.php', 'all_applied_loan.php','all_applied_vac.php'];
const ASSISTANT_RESTRICTED_PAGES = ['dashbydepart.php', 'filter_employee.php', 'reg_employee.php', 'search.php', 'manual_vacation.php'];


if (($emprow['user_type'] ?? null) === 'employee' && !in_array($current_page, EMPLOYEE_ALLOWED_PAGES, true)) {
    header("Location: ./profile.php");
    exit();
}

$isSpecialAssistant = ((($user_dept ?? null) == 5 && ($user_type ?? null) === 'assistant') || (($user_dept ?? null) == 6 && ($user_type ?? null) === 'assistant'));
if (!$isSpecialAssistant && ($user_type ?? null) === 'assistant' && in_array($current_page, ASSISTANT_RESTRICTED_PAGES, true)) {
    header("Location: ./dashboard.php");
    exit();
}

include(__DIR__ . "/menu_active_class.php");

?>
