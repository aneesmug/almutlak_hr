<?php
// Which employee a self-service page (profile.php and its "Employee records" pages) shows.
// No ?emp_id, or the logged-in employee's own ID -> the logged-in employee.
// Another employee's ID -> only when canEmployeeSupervisorAccess() allows it (same rule as
// view_employee.php); otherwise the user is sent to the dashboard with "Access Denied".
// Requires session_check.php (sets $logged_in_empid, $user_dept, $user_type, $user_role).

if (!function_exists('profile_target_emp_id')) {
    function profile_target_deny() {
        $_SESSION['error_msg'] = sprintf(
            '<div class="col-xl-12"><div class="alert alert-danger bg-danger text-white border-0" role="alert"><b>%s</b><h4>%s</h4></div></div>',
            __('access_denied', 'Access Denied!'),
            __('access_denied_employee_view_message', "You don't have access to view this employee. Your access is limited to employees in your department and company.")
        );
        header('Location: ./dashboard.php');
        exit;
    }

    function profile_target_emp_id() {
        global $conDB, $logged_in_empid, $user_dept, $user_type, $user_role;

        $self = (string)($logged_in_empid ?? ($_SESSION['empid'] ?? ''));
        $requested = isset($_GET['emp_id']) ? trim((string)$_GET['emp_id']) : '';
        if ($requested === '' || $requested === $self) {
            return $self;
        }
        if (!ctype_digit($requested)) {
            profile_target_deny();
        }

        $stmt = mysqli_prepare($conDB, "SELECT `emp_id`, `supervisor_id`, `dept`, `comp_no` FROM `employees` WHERE `emp_id` = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, 's', $requested);
        mysqli_stmt_execute($stmt);
        $target = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);
        if (!$target) {
            profile_target_deny();
        }

        $user_data = [
            'emp_id' => $_SESSION['auth_user']['emp_id'] ?? $self,
            'dept' => $_SESSION['auth_user']['dept'] ?? $user_dept,
            'comp_no' => $_SESSION['auth_user']['comp_no'] ?? 1,
            'user_type' => $user_type,
            'accessible_departments' => getAccessibleDepartments(true),
        ];
        $employee_data = [
            'emp_id' => $target['emp_id'],
            'supervisor_id' => $target['supervisor_id'] ?? null,
            'dept' => $target['dept'] ?? null,
            'comp_no' => $target['comp_no'] ?? 1,
        ];
        if (!canEmployeeSupervisorAccess($employee_data, $user_data, $user_role ?? '')) {
            profile_target_deny();
        }
        return $requested;
    }
}

// Resolved once per request; pages read these.
$profile_target_empid = profile_target_emp_id();
$is_viewing_other_employee = $profile_target_empid !== (string)($logged_in_empid ?? ($_SESSION['empid'] ?? ''));
