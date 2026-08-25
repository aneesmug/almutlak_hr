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
require_once __DIR__ . '/session_bootstrap.php';
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// --- KEEP-ALIVE ENDPOINT ---
// Handle session extension requests from client-side (extend_session=1)
// if (isset($_GET['extend_session']) && $_GET['extend_session'] === '1') {
//     if (isset($_SESSION['auth_user'])) {
//         $_SESSION['last_activity'] = time();
//         session_write_close(); // Force session save to disk immediately
//         header('Content-Type: application/json');
//         echo json_encode(['status' => 'extended', 'message' => 'Session extended']);
//         exit();
//     }
//     header('Content-Type: application/json');
//     echo json_encode(['status' => 'error', 'message' => 'No session']);
//     exit();
// }


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

// --- 2. Initialize Database Connection (needed for timeout logging) ---
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/init.php';
require_once __DIR__ . '/user_activity_logger.php';
include_once __DIR__ . '/helper_functions.php';

// --- 3. Session Timeout Handling ---
$timeout_duration = get_setting($conDB, 'session_timeout');

// Auto sign-out sweep: catches OTHER users whose session went stale without
// them ever sending another request (closed tab, dead phone, lost network) -
// the self-check below only ever catches the current request's own user.
// Driven by its own dedicated "Auto Sign-Out After (hours)" setting rather
// than session_timeout (seconds), which controls the separate lazy per-user check.
$autoSignoutHours = (float) get_setting($conDB, 'auto_signout_hours');
$autoSignoutSeconds = $autoSignoutHours > 0 ? (int) round($autoSignoutHours * 3600) : 28800; // default 8h
$autoSignoutEmployeeHours = (float) get_setting($conDB, 'auto_signout_hours_employee');
$autoSignoutEmployeeSeconds = $autoSignoutEmployeeHours > 0 ? (int) round($autoSignoutEmployeeHours * 3600) : $autoSignoutSeconds;
sweepStaleUserActivity($conDB, $autoSignoutSeconds, $autoSignoutEmployeeSeconds);

// Admin-forced sign-out (Connection Monitor "Sign out" button). A live PHP
// process can't be killed remotely, so this session only notices on its next
// request - checking here means that happens on literally any click/nav,
// not just the next full page load.
if (isset($_SESSION['activity_log_id']) && getUserActivityStatus($conDB, $_SESSION['activity_log_id']) === 'logged_out') {
    session_unset();
    session_destroy();
    setcookie('remember_me', '', time() - 3600, '/'); // don't let remember_me silently re-auth them right back in

    while (ob_get_level()) ob_end_clean();
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Signed Out</title>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    </head>
    <body>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                var redirectMs = 3000;
                var redirectUrl = './index.php';
                var redirected = false;
                function goToLogin() {
                    if (redirected) return;
                    redirected = true;
                    window.location.href = redirectUrl;
                }
                setTimeout(goToLogin, redirectMs + 500);
                try {
                    Swal.mixin({
                        toast: true, position: 'top-end', showConfirmButton: false,
                        showCloseButton: false, allowOutsideClick: false, allowEscapeKey: false,
                        timer: redirectMs, timerProgressBar: true,
                    }).fire({
                        icon: 'info',
                        title: 'You have been signed out by an administrator.',
                    }).then(goToLogin);
                } catch (e) {
                    goToLogin();
                }
            });
        </script>
    </body>
    </html>
    <?php
    exit();
}

if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout_duration) {
    // Log timeout activity before destroying session
    logUserLogout($conDB, 'timeout');

    // End session (remember_me cookie, if present, will allow auto-login on dashboard)
    session_unset();
    session_destroy();

    // Show toast on the same page, then redirect to dashboard
    while (ob_get_level()) ob_end_clean();
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Session Expired</title>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    </head>
    <body>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                var redirectMs = 3000; // extended toast/redirect duration
                var redirectUrl = './dashboard.php';
                var redirected = false;

                function goToDashboard() {
                    if (redirected) return;
                    redirected = true;
                    window.location.href = redirectUrl;
                }

                // Hard fallback: guarantees the redirect fires even if the CDN toast
                // library fails to load/execute (blocked network, ad blocker, etc.),
                // which otherwise leaves the user stuck on this page forever.
                setTimeout(goToDashboard, redirectMs + 500);

                try {
                    var Toast = Swal.mixin({
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        showCloseButton: false,
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        timer: redirectMs,
                        timerProgressBar: true,
                    });
                    Toast.fire({
                        icon: 'info',
                        title: 'Session expired due to inactivity'
                    }).then(goToDashboard);
                } catch (e) {
                    goToDashboard();
                }
            });
        </script>
    </body>
    </html>
<?php
    exit();
}
    $_SESSION['last_activity'] = time();
    if (isset($_SESSION['activity_log_id'])) {
        touchUserActivity($conDB, $_SESSION['activity_log_id']);
    }
    // Store timeout settings in session for header.php to use
    $_SESSION['timeout_duration'] = intval($timeout_duration);
    $_SESSION['last_activity_time'] = isset($_SESSION['last_activity']) ? intval($_SESSION['last_activity']) : time();
    $_SESSION['server_time'] = time();
    
    // Track login time if not already set (for grace period after login)
    if (!isset($_SESSION['session_login_time'])) {
        $_SESSION['session_login_time'] = time();
    }

// --- 3.5 Check if User Has Been Force-Logged-Out ---
// Validate user session status in database, but with grace period for new logins
$user_id_for_query = $_SESSION['auth_user']['user_id']; // id_iqama for regular queries
$numeric_user_id = $_SESSION['auth_user']['numeric_id'] ?? null; // numeric ID for activity log
$sessionId = session_id();

// Give 30 second grace period after login before checking activity log
// BUT always check if user was force-signed-out (grace period only applies to fresh logins)
$sessionLoginTime = $_SESSION['session_login_time'] ?? 0;
$secondsSinceLogin = time() - $sessionLoginTime;
$isGracePeriod = $secondsSinceLogin < 30;

// Always validate if we have numeric ID (grace period is checked inside validator)
if ($numeric_user_id) {
    $verifyActiveSessionQuery = "SELECT `id`, `status` FROM `user_activity_log` 
                                WHERE `user_id` = ? AND `session_id` = ? 
                                LIMIT 1";
    $verifyActiveStmt = mysqli_prepare($conDB, $verifyActiveSessionQuery);
    
    if ($verifyActiveStmt) {
        mysqli_stmt_bind_param($verifyActiveStmt, "is", $numeric_user_id, $sessionId);
        mysqli_stmt_execute($verifyActiveStmt);
        $verifyActiveResult = mysqli_stmt_get_result($verifyActiveStmt);
        $activeSessionRecord = mysqli_fetch_assoc($verifyActiveResult);
        mysqli_stmt_close($verifyActiveStmt);
        
        // If session record exists but status is NOT 'active', user has been force-logged-out
        if ($activeSessionRecord && $activeSessionRecord['status'] !== 'active') {
            // Force logout immediately
            session_unset();
            
            $session_file = session_save_path() . '/sess_' . $sessionId;
            if (file_exists($session_file)) {
                @unlink($session_file);
            }
            
            session_destroy();
            
            setcookie('PHPSESSID', '', time() - 3600, '/');
            setcookie('remember_me', '', time() - 3600, '/');
            
            header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
            header("Pragma: no-cache");
            
            while (ob_get_level()) ob_end_clean();
            ?>
            <!DOCTYPE html>
            <html lang="en">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>Session Terminated</title>
                <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
            </head>
            <body>
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        var redirected = false;
                        function goToLogin() {
                            if (redirected) return;
                            redirected = true;
                            window.location.href = './index.php';
                        }
                        // Safety net: this dialog normally waits for the user to click
                        // "Go to Login" - it should NOT auto-redirect while that's working.
                        // But if the CDN toast library never loaded (blocked network, ad
                        // blocker, etc.), Swal stays undefined and the user would be stuck
                        // on this page forever with no way out. Only redirect here if that
                        // actually happened.
                        setTimeout(function() {
                            if (typeof Swal === 'undefined') {
                                goToLogin();
                            }
                        }, 4000);

                        try {
                            Swal.fire({
                                title: 'Session Terminated',
                                text: 'Your session has been terminated by an administrator. Please log in again.',
                                icon: 'warning',
                                allowOutsideClick: false,
                                allowEscapeKey: false,
                                confirmButtonText: 'Go to Login'
                            }).then(goToLogin);
                        } catch (e) {
                            goToLogin();
                        }
                    });
                </script>
            </body>
            </html>
<?php
            exit();
        }
    }
}

// --- 4. Fetch Full User & Employee Record ---

$query = "SELECT 
            al.*, 
            e.name AS efullname, 
            e.avatar AS eavatar,
            e.comp_no,
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
$user_company = $emprow['comp_no'] ?? 1;

// --- Temporary role coverage (vacation replacement) ---
// If this employee is currently covering someone's vacation (granted via the
// "Transfer Role (Temp)" button on the employee master page), their effective
// user_type becomes the covered employee's role for the duration of the vacation
// window. This is read fresh every request from emp_temp_role_assignments and is
// never written back to admin_login, so it disappears automatically once the
// vacation's return_date passes - no restore step required.
$actual_user_type = $user_type;
$is_temp_role_active = false;
$temp_role_assignment = getActiveTempRoleForEmployee($conDB, $empid);
if ($temp_role_assignment) {
    $user_type = $temp_role_assignment['granted_role'];
    $is_temp_role_active = true;
}

// Store in session for consistency across all pages
$_SESSION['auth_user']['dept'] = $user_dept;
$_SESSION['auth_user']['comp_no'] = $user_company;
$_SESSION['auth_user']['numeric_id'] = $emprow['id']; // Store numeric ID for activity log validation

// --- 4a. Company Access Control (Company-Level Restrictions) ---
// Get allowed companies from admin_login.allowed_companies JSON column
// NULL = full access to all companies
// JSON array = restricted access to specified company IDs only
$allowed_companies = null;  // Default: all companies
$allowed_companies_array = [];  // Default: empty array

if (isset($emprow['allowed_companies']) && !empty($emprow['allowed_companies'])) {
    try {
        $decoded_companies = json_decode($emprow['allowed_companies'], true);
        if (is_array($decoded_companies) && count($decoded_companies) > 0) {
            $allowed_companies = $decoded_companies;
            $allowed_companies_array = array_map('intval', $decoded_companies);
        }
    } catch (Exception $e) {
        // JSON decode error - treat as full access
        $allowed_companies = null;
        $allowed_companies_array = [];
    }
}

// Store in session for use throughout application
$_SESSION['allowed_companies'] = $allowed_companies;
$_SESSION['allowed_companies_array'] = $allowed_companies_array;

// Normalize company restriction IDs to support both companies.id and companies.comp_id
// This prevents scope leaks when legacy records store one format and employees.comp_no uses the other.
if (!empty($allowed_companies_array)) {
    $allowed_companies_array = normalizeAllowedCompanyIds($conDB, $allowed_companies_array);
    $_SESSION['allowed_companies_array'] = $allowed_companies_array;
}

// Determine if user has company restrictions
$has_company_restrictions = !empty($allowed_companies_array) && count($allowed_companies_array) > 0;

// --- 4b. Department Access Control (Department-Level Restrictions) ---
// Get allowed departments from admin_login.allowed_departments JSON column
// NULL = full access to all departments
// JSON array = restricted access to specified department IDs only
$allowed_departments = null;  // Default: all departments
$allowed_departments_array = [];  // Default: empty array

if (isset($emprow['allowed_departments']) && !empty($emprow['allowed_departments'])) {
    try {
        $decoded_departments = json_decode($emprow['allowed_departments'], true);
        if (is_array($decoded_departments) && count($decoded_departments) > 0) {
            $allowed_departments = $decoded_departments;
            $allowed_departments_array = array_map('intval', $decoded_departments);
        }
    } catch (Exception $e) {
        // JSON decode error - treat as full access
        $allowed_departments = null;
        $allowed_departments_array = [];
    }
}

// Store in session for use throughout application
$_SESSION['allowed_departments'] = $allowed_departments;
$_SESSION['allowed_departments_array'] = $allowed_departments_array;

// Determine if user has department restrictions
$has_department_restrictions = !empty($allowed_departments_array) && count($allowed_departments_array) > 0;

// --- 4c. Employee Access Control (Employee-Level Restrictions) ---
// Get allowed employees from admin_login.allowed_employees JSON column
// NULL = full access to all employees
// JSON array = restricted access to specified employee IDs only
$allowed_employees = null;  // Default: all employees
$allowed_employees_array = [];  // Default: empty array

if (isset($emprow['allowed_employees']) && !empty($emprow['allowed_employees'])) {
    try {
        $decoded_employees = json_decode($emprow['allowed_employees'], true);
        if (is_array($decoded_employees) && count($decoded_employees) > 0) {
            $allowed_employees = $decoded_employees;
            $allowed_employees_array = array_map('intval', $decoded_employees);
        }
    } catch (Exception $e) {
        // JSON decode error - treat as full access
        $allowed_employees = null;
        $allowed_employees_array = [];
    }
}

// Store in session for use throughout application
$_SESSION['allowed_employees'] = $allowed_employees;
$_SESSION['allowed_employees_array'] = $allowed_employees_array;

// Manager visibility mode flag
// Rule: emp_type='Manager' or 'Supervisor' is treated as a manager-assigned user,
// restricted to their own assigned direct reports (employees.supervisor_id), never
// department-wide. But strict direct-reports mode should NOT apply to roles with
// full employee visibility (HR, Admin, GM, Finance Manager, etc).
//
// Default-deny: any account with no explicit allowed_companies/allowed_departments/
// allowed_employees configured (admin never deliberately widened their scope) also
// falls into this strict direct-reports-only mode, instead of the old fallback of
// unrestricted access to every employee. Plain operational roles (IT Supporter, etc.)
// must be explicitly widened via allowed_departments/companies or the
// 'view_all_employees' Special Access key - never see everyone by default.
$has_explicit_scope_restrictions = $has_company_restrictions || $has_department_restrictions || !empty($allowed_employees_array);
$is_manager_assigned = ($user_type === 'dept_user'
    || in_array(strtolower((string)$emp_type), ['manager', 'supervisor'], true)
    || !$has_explicit_scope_restrictions);
$is_dept_manager = ($is_manager_assigned && !canSeeAllEmployeesByRole(false));
$_SESSION['manager_direct_reports_only'] = $is_dept_manager;

// --- 4d. Effective Employee Access (Additive to Company/Department) ---
// If company/department access is restricted, include employees from those scopes
// plus any explicitly allowed employees. If company/department is unrestricted,
// do not restrict by employees.
$effective_allowed_employees = resolveEffectiveEmployeeScope(
    $conDB,
    $is_dept_manager,
    $empid,
    $allowed_employees_array,
    $allowed_companies_array,
    $allowed_departments_array
);

// Store effective employee access for filtering
$allowed_employees_array = $effective_allowed_employees;
$_SESSION['allowed_employees_array'] = $allowed_employees_array;

// Determine if user has employee restrictions
$has_employee_restrictions = !empty($allowed_employees_array) && count($allowed_employees_array) > 0;

// --- 5. Log User Activity (First login only) ---
if (!isset($_SESSION['activity_logged'])) {
    require_once __DIR__ . '/user_activity_logger.php';
    $activity_id = logUserActivity($conDB, $emprow['id'], $emprow['emp_id'], $username);
    if ($activity_id) {
        $_SESSION['activity_log_id'] = $activity_id;
        $_SESSION['activity_logged'] = true;
    }
}

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

// --- License Enforcement ---
require_once __DIR__ . '/license_check.php';
license_enforce($conDB);

// HR Team Roles (based on user_type)
$isHR_Manager = ($user_type === 'hr');
$isHR_Senior_BP = ($user_type === 'hr_senior_bp');
$isHR_Operations = ($user_type === 'hr_operations');
$isHR_Supervisor = ($user_type === 'hr_supervisor');
$isHR_Recruitment = ($user_type === 'hr_recruitment');
$isHR_Payroll = ($user_type === 'hr_payroll');
$isHR_Assistant = ($user_type === 'assistant' && $user_dept == 5); // HR Department Assistant


// Combined HR roles (any HR position or HR department)
$isHR = ($user_type === 'hr' || $isHR_Senior_BP || $isHR_Operations || $isHR_Supervisor || $isHR_Recruitment || $isHR_Payroll || $isHR_Assistant || $user_dept == 5);
$isDeptHr = ($user_dept == 5); // Anyone in HR Department

// Finance & Audit Roles
$isFinance_Manager = ($emp_type === 'Manager' && $user_dept == 2); // Finance Department Manager
$isFinance_Officer = ($user_type === 'finance_officer');
$isFinance_Assistant = ($user_type === 'assistant' && $user_dept == 2); // Finance Department Assistant
$isAuditor = ($user_type === 'auditor');
$isFinance = ($user_type === 'finance_officer' || $user_type === 'auditor' || $isFinance_Manager || $isFinance_Assistant || $user_dept == 2);
$isDeptFinance = ($user_dept == 2); // Anyone in Finance Department

// Department & Management Roles
$isDeptManager = ($user_type === 'dept_user' || $user_role === 'DPT_Manager' || $emp_type === 'Manager');
$isSupervisor = (isset($emprow['supervisor_id']) && $emprow['supervisor_id'] !== '' && $emprow['supervisor_id'] !== null); // Has subordinates

// Other Specific Roles
$isGR_Officer = ($user_type === 'gr_officer');
$isAssistant = ($user_type === 'assistant'); // Generic assistant role
$isItTeam = ($user_dept == 6); // Anyone in IT Department
$isItAssistant = ($user_type === 'it' && $user_dept == 6 && $emp_type === 'Supporter'); // IT Department Assistant
$isItManager = ($user_type === 'it' && $emp_type === 'Manager' && $user_dept == 6); // IT Department Manager 
$isAdministration = ($user_dept === 1); // Anyone in Administration Department

// General Role Types
$isEmployee = ($user_type === 'employee');

// Legacy variables (for backward compatibility)
// $isAssistant is now defined above as generic assistant

// --- 7. Page Access Control ---
$current_page = strtolower(basename($_SERVER['PHP_SELF']));

const EMPLOYEE_ALLOWED_PAGES = ['profile.php', 'all_applied_loan.php','vacation_report_details.php', 'employee_vacation_history.php','employee_loan_history.php'];
const ASSISTANT_RESTRICTED_PAGES = ['dashbydepart.php', 'filter_employee.php', 'reg_employee.php', 'search.php', 'manual_vacation.php'];

// Skip access control for AJAX file requests - they return JSON and handle their own auth
$is_ajax_file = (strpos($_SERVER['PHP_SELF'], '/ajaxFile/') !== false);

// Allow specific endpoints to bypass page access redirects while still enforcing auth
if (defined('SKIP_PAGE_ACCESS_CONTROL') && SKIP_PAGE_ACCESS_CONTROL === true) {
    $is_ajax_file = true;
}

if (!$is_ajax_file && ($user_type ?? null) === 'employee' && !in_array($current_page, EMPLOYEE_ALLOWED_PAGES, true)) {
    header("Location: ./profile.php");
    exit();
}

$isSpecialAssistant = ((($user_dept ?? null) == 5 && ($user_type ?? null) === 'assistant') || (($user_dept ?? null) == 6 && ($user_type ?? null) === 'assistant'));
if (!$isSpecialAssistant && ($user_type ?? null) === 'assistant' && in_array($current_page, ASSISTANT_RESTRICTED_PAGES, true)) {
    header("Location: ./dashboard.php");
    exit();
}

// --- 8. Auto-Update Fly Status on Every Page Load ---
update_employee_fly_status_on_session($conDB);

// --- 9. DISABLED: Auto-Update Vacation Balance on Every Page Load ---
// NOTE: Balance should be calculated LIVE only when needed, never persisted on session load
// The live balance is calculated by get_current_vacation_balance() in balance_calculator.php
// and used in the UI. The emp_vacation_balance table is for historical records only.
// update_employee_vacation_balance_on_session($conDB, $empid);

include(__DIR__ . "/menu_active_class.php");

/**
 * Automatically updates employees.fly status based on approved vacation dates.
 * Runs on EVERY page load to ensure immediate updates.
 * 
 * How it works:
 * 1. Sets fly=1 when employee is currently on Fly vacation (annual/emergency)
 * 2. Also sets fly=1 for Local Vacation (annual/emergency) ONLY when vacdays > 5
 * 3. Local Vacation with days <= 5 does NOT trigger fly=1
 * 4. Auto-resets fly=0 once a Local-Vacation-only fly flag no longer qualifies
 *    (employees with real Fly-type history are left to the manual return workflow)
 * 5. Only affects regular vacation (VAC-*), NOT leave requests (LV-*)
 * 
 * @param mysqli $conDB Database connection
 * @return void
 */
function update_employee_fly_status_on_session($conDB) {
    try {
        $today = date('Y-m-d');
        
        // STEP 1: Set fly=1 for active Fly vacations (annual/emergency)
        // Also set fly=1 for Local Vacation (annual/emergency) ONLY when vacdays > 5
        // (Only for regular vacation VAC-*, not leave requests LV-*)
        $sql_find_employees = "
            SELECT DISTINCT v.emp_id
            FROM emp_vacation v
            WHERE (v.current_status = 'approved' OR v.current_status = 'completed')
                AND v.start_date <= ?
                AND v.return_date >= ?
                AND v.request_inv_no LIKE 'VAC-%'
                AND LOWER(COALESCE(v.fly_type, '')) IN ('annual', 'emergency')
                AND v.review = 'A'
                AND (
                    LOWER(v.vac_type) = 'fly'
                    OR (LOWER(v.vac_type) = 'local vacation' AND v.vacdays > 5)
                )
        ";
        
        $stmt_find = mysqli_prepare($conDB, $sql_find_employees);
        if ($stmt_find) {
            mysqli_stmt_bind_param($stmt_find, 'ss', $today, $today);
            if (mysqli_stmt_execute($stmt_find)) {
                $result = mysqli_stmt_get_result($stmt_find);
                while ($row = mysqli_fetch_assoc($result)) {
                    $emp_id = $row['emp_id'];
                    
                    // Update this employee's fly status to 1
                    $sql_update = "UPDATE employees SET fly = 1 WHERE emp_id = ? AND (fly = 0 OR fly = '0' OR LOWER(fly) = 'no' OR fly IS NULL)";
                    $stmt_update = mysqli_prepare($conDB, $sql_update);
                    if ($stmt_update) {
                        mysqli_stmt_bind_param($stmt_update, 's', $emp_id);
                        mysqli_stmt_execute($stmt_update);
                        mysqli_stmt_close($stmt_update);
                    }
                }
                if ($result) mysqli_free_result($result);
            }
            mysqli_stmt_close($stmt_find);
        }
        
        // STEP 2: Auto-reset fly=0 for employees whose fly=1 is stuck due to a Local Vacation
        // that no longer qualifies (window ended, or was always < 5 days).
        // Scope is intentionally narrow: employees who have EVER had a real 'Fly' type vacation
        // are excluded here and continue to rely on the manual returnVacationRequest() workflow
        // in emp_top_info.php. Only employees whose fly=1 can ONLY be explained by a Local
        // Vacation get auto-corrected, since Local Vacation has no manual return-confirmation step.
        $sql_reset_local = "
            UPDATE employees e
            SET e.fly = 0
            WHERE e.fly = 1
              AND NOT EXISTS (
                  SELECT 1 FROM emp_vacation v
                  WHERE v.emp_id = e.emp_id
                    AND v.request_inv_no LIKE 'VAC-%'
                    AND LOWER(v.vac_type) = 'fly'
                    AND v.review = 'A'
                    AND v.current_status IN ('approved', 'completed')
              )
              AND NOT EXISTS (
                  SELECT 1 FROM emp_vacation v
                  WHERE v.emp_id = e.emp_id
                    AND v.request_inv_no LIKE 'VAC-%'
                    AND LOWER(v.vac_type) = 'local vacation'
                    AND LOWER(COALESCE(v.fly_type, '')) IN ('annual', 'emergency')
                    AND v.vacdays > 5
                    AND v.review = 'A'
                    AND v.current_status IN ('approved', 'completed')
                    AND v.start_date <= ?
                    AND v.return_date >= ?
              )
        ";
        if ($stmt_reset_local = mysqli_prepare($conDB, $sql_reset_local)) {
            mysqli_stmt_bind_param($stmt_reset_local, 'ss', $today, $today);
            mysqli_stmt_execute($stmt_reset_local);
            mysqli_stmt_close($stmt_reset_local);
        }

        // STEP 3: Auto-complete LV-* (Excuse Leave) requests after return date has passed
        // At the end of the return_date day, mark review = 'C' (Completed)
        // We run this when current date > return_date by checking return_date < CURDATE()
        $sql_complete_lv = "
            UPDATE emp_vacation
            SET review = 'C', current_status = 'completed' 
            WHERE request_inv_no LIKE 'LV-%'
              AND review <> 'C'
              AND return_date < ?
              AND (current_status = 'approved' OR current_status = 'completed')
        ";
        if ($stmt_lv = mysqli_prepare($conDB, $sql_complete_lv)) {
            mysqli_stmt_bind_param($stmt_lv, 's', $today);
            mysqli_stmt_execute($stmt_lv);
            mysqli_stmt_close($stmt_lv);
        }
        
    } catch (Exception $e) {
        // Silently fail to avoid breaking page loads
        // error_log("update_employee_fly_status: Exception - " . $e->getMessage());
    }
}
