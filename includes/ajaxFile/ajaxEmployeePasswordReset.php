<?php
/********************************************************************************
 * HR password reset for a normal employee (Employee Master > More Actions).
 * - Needs system admin or the 'reset_employee_password' special access
 *   (App Settings > Special Access > Employee Master).
 * - Only accounts with user_type 'employee' can be reset here; OTP users
 *   (admin/HR/finance/GM...) sign in by email and have no password to reset.
 * - Sets a random temporary password, shown once to the HR user, and flags the
 *   account so the employee must choose a new password at the next login.
 * - Clears "remember me" tokens and any login lock for the account.
 ********************************************************************************/
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/session_check.php';
require_once __DIR__ . '/../../includes/special_access_helper.php';
require_once __DIR__ . '/../../includes/login_security.php';

header('Content-Type: application/json; charset=utf-8');

$respond = function ($ok, $message, $extra = []) {
    echo json_encode(['ok' => $ok, 'message' => $message] + $extra);
    exit;
};

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    $respond(false, 'Invalid request method.');
}

if (!user_has_special_access($conDB, $empid ?? '', 'reset_employee_password', $user_role ?? '', $user_type ?? '', $is_system_admin ?? false)) {
    http_response_code(403);
    $respond(false, 'You do not have permission to reset employee passwords.');
}

$iqama = preg_replace('/\D+/', '', (string) ($_POST['iqama'] ?? ''));
if ($iqama === '') {
    $respond(false, 'Employee ID/Iqama is missing.');
}

login_sec_ensure_must_change_column($conDB);

$stmt = mysqli_prepare($conDB, "SELECT `id`, `user_type`, `fullname` FROM `admin_login` WHERE `id_iqama` = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, "s", $iqama);
mysqli_stmt_execute($stmt);
$account = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$account) {
    $respond(false, 'This employee has not registered a login account yet. They can register from the login page.');
}
if ($account['user_type'] !== 'employee') {
    $respond(false, 'This user signs in with an email OTP, so there is no password to reset.');
}

$tempPassword = login_sec_generate_temp_password();
$hash = password_hash($tempPassword, PASSWORD_DEFAULT);

$update = mysqli_prepare($conDB, "UPDATE `admin_login` SET `password` = ?, `must_change_password` = 1, `remember_token` = NULL, `remember_token_expiry` = NULL, `updated_at` = NOW() WHERE `id` = ?");
$accountId = (int) $account['id'];
mysqli_stmt_bind_param($update, "si", $hash, $accountId);
if (!mysqli_stmt_execute($update)) {
    $respond(false, 'The password could not be reset. Please try again.');
}

// A success row lifts any active login lock for this ID
login_sec_record($conDB, $iqama, 'hr_reset', true);

if (class_exists('ActivityLogger')) {
    ActivityLogger::logUpdate('view_employee.php', $accountId, 'Reset login password for employee ' . $iqama . ' (temporary password issued)', null, '[REDACTED]');
}

$respond(true, 'Password reset.', ['temp_password' => $tempPassword]);
