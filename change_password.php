<?php
/********************************************************************************
 * Forced password change after an HR reset (temporary password).
 * login.php sets $_SESSION['must_change_password'] when the account is flagged;
 * includes/session_check.php blocks every other page until this form succeeds.
 ********************************************************************************/
require_once __DIR__ . '/includes/session_bootstrap.php';
if (session_status() == PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/login_security.php';

header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");

if (empty($_SESSION['auth_user']['user_id'])) {
    header("Location: ./index.php");
    exit();
}
if (empty($_SESSION['must_change_password'])) {
    header("Location: ./dashboard.php");
    exit();
}

$error = '';
$csrfToken = login_sec_csrf_token();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newPassword = (string) ($_POST['new_password'] ?? '');
    $confirmPassword = (string) ($_POST['confirm_password'] ?? '');
    $iqama = (string) $_SESSION['auth_user']['user_id'];

    if (!login_sec_csrf_valid()) {
        $error = login_sec_csrf_message();
    } elseif ($newPassword !== $confirmPassword) {
        $error = 'Passwords do not match.';
    } elseif ($passwordError = login_sec_password_error($newPassword)) {
        $error = $passwordError;
    } else {
        // The new password must differ from the temporary one
        $stmt = mysqli_prepare($conDB, "SELECT `password` FROM `admin_login` WHERE `id_iqama` = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, "s", $iqama);
        mysqli_stmt_execute($stmt);
        $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

        if ($row && password_verify($newPassword, $row['password'])) {
            $error = 'Please choose a password different from the temporary one.';
        } else {
            login_sec_ensure_must_change_column($conDB);
            $hash = password_hash($newPassword, PASSWORD_DEFAULT);
            $update = mysqli_prepare($conDB, "UPDATE `admin_login` SET `password` = ?, `must_change_password` = 0, `updated_at` = NOW() WHERE `id_iqama` = ?");
            mysqli_stmt_bind_param($update, "ss", $hash, $iqama);
            if (mysqli_stmt_execute($update)) {
                unset($_SESSION['must_change_password']);
                session_regenerate_id(true);
                header("Location: ./dashboard.php");
                exit();
            }
            $error = 'Could not update your password. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set New Password</title>
    <link rel="shortcut icon" href="<?= htmlspecialchars((string) get_setting($conDB, 'favicon'), ENT_QUOTES) ?>">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: 'Roboto', sans-serif; background: #050506; }
        .field-input { width: 100%; background: #111116; border: 1px solid #2a2a33; color: #fff; border-radius: 10px; padding: 12px 14px; outline: none; }
        .field-input:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,.2); }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4 text-gray-200">
    <div class="w-full max-w-md bg-[#0b0b0f] border border-white/10 rounded-2xl p-8 shadow-2xl">
        <h1 class="text-2xl font-bold text-white mb-2">Set a New Password</h1>
        <p class="text-gray-400 text-sm mb-6">
            Hello <?= htmlspecialchars((string) ($_SESSION['auth_user']['fullname'] ?? ''), ENT_QUOTES) ?>,
            your password was reset by HR. Please choose a new password to continue.
        </p>
        <?php if ($error !== ''): ?>
            <div class="mb-4 p-3 rounded-lg text-sm bg-red-500/10 border border-red-500/30 text-red-400"><?= htmlspecialchars($error, ENT_QUOTES) ?></div>
        <?php endif; ?>
        <form method="post" class="space-y-4" autocomplete="off">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES) ?>">
            <div>
                <label for="new_password" class="block text-sm font-medium mb-1">New Password</label>
                <input class="field-input" type="password" name="new_password" id="new_password" minlength="<?= (int) LOGIN_SEC_MIN_PASSWORD ?>" required>
                <p class="text-xs text-gray-500 mt-1">At least <?= (int) LOGIN_SEC_MIN_PASSWORD ?> characters.</p>
            </div>
            <div>
                <label for="confirm_password" class="block text-sm font-medium mb-1">Confirm New Password</label>
                <input class="field-input" type="password" name="confirm_password" id="confirm_password" minlength="<?= (int) LOGIN_SEC_MIN_PASSWORD ?>" required>
            </div>
            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 rounded-lg transition">Save Password</button>
        </form>
        <a href="./logout.php" class="block text-center text-sm text-gray-400 hover:text-gray-200 mt-5">Log out</a>
    </div>
</body>
</html>
