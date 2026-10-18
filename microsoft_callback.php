<?php
/********************************************************************************
 * Microsoft Sign-in callback (redirect URI registered in the Entra app).
 * login.php starts the sign-in (includes/microsoft_auth.php::msauth_begin); Microsoft
 * returns here with ?code&state. The account must be the same one whose ID/Iqama was
 * typed on the login page: matched by the bound Microsoft object ID (ms_oid), or on
 * first sign-in by email, which then binds ms_oid.
 ********************************************************************************/
require_once __DIR__ . '/includes/session_bootstrap.php';
if (session_status() == PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/login_security.php';
require_once __DIR__ . '/includes/microsoft_auth.php';

header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");

$pending = $_SESSION['msauth'] ?? null;
unset($_SESSION['msauth']); // single use
$errorMessage = '';
$iqama = (string) ($pending['id_iqama'] ?? '');

try {
    if (!msauth_enabled($conDB)) {
        throw new RuntimeException('Microsoft sign-in is turned off. Please log in again.');
    }
    if (empty($pending) || time() > (int) $pending['expires']) {
        throw new RuntimeException('Your sign-in session expired. Please log in again.');
    }
    if (isset($_GET['error'])) {
        // Typically the user cancelled, or MFA / Conditional Access blocked the sign-in
        error_log('[MSAUTH] Microsoft returned error for ID=' . $iqama . ': ' . substr((string) ($_GET['error_description'] ?? $_GET['error']), 0, 300));
        throw new RuntimeException('Microsoft sign-in was cancelled or not allowed.');
    }
    if (!hash_equals((string) $pending['state'], (string) ($_GET['state'] ?? ''))) {
        throw new RuntimeException('Sign-in response did not match this browser session. Please log in again.');
    }
    if (empty($_GET['code'])) {
        throw new RuntimeException('Microsoft did not return a sign-in code.');
    }
    if (login_sec_is_locked($conDB, $iqama)) {
        throw new RuntimeException(login_sec_locked_message());
    }

    $claims = msauth_complete($conDB, $pending, $_GET['code']);
    $oid = (string) $claims['oid'];
    $msEmails = array_filter(array_map(function ($v) {
        return strtolower(trim((string) $v));
    }, [$claims['preferred_username'] ?? '', $claims['email'] ?? '', $claims['upn'] ?? '']));

    msauth_ensure_oid_column($conDB);
    $stmt = mysqli_prepare($conDB, "SELECT a.*, e.name AS employee_name FROM `admin_login` a LEFT JOIN `employees` e ON a.emp_id = e.emp_id WHERE a.`id_iqama` = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, "s", $iqama);
    mysqli_stmt_execute($stmt);
    $user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    if (!$user || (int) $user['status'] !== 1 || $user['user_type'] === 'employee' || !msauth_user_assigned($conDB, $iqama)) {
        throw new RuntimeException('This account cannot sign in with Microsoft.');
    }
    if (!empty($user['ms_oid'])) {
        if (!hash_equals((string) $user['ms_oid'], $oid)) {
            throw new RuntimeException('You signed in with a different Microsoft account than the one linked to this ID.');
        }
    } else {
        $appEmail = strtolower(trim((string) $user['email']));
        if ($appEmail === '' || !in_array($appEmail, $msEmails, true)) {
            throw new RuntimeException('The Microsoft account you used does not match the email on file for this ID.');
        }
        $bind = mysqli_prepare($conDB, "UPDATE `admin_login` SET `ms_oid` = ? WHERE `id` = ? AND (`ms_oid` IS NULL OR `ms_oid` = '')");
        $accountId = (int) $user['id'];
        mysqli_stmt_bind_param($bind, "si", $oid, $accountId);
        mysqli_stmt_execute($bind);
    }

    // --- Signed in (same session setup as the email OTP path in login_verification.php) ---
    if (!empty($user['employee_name'])) { $user['fullname'] = $user['employee_name']; }
    login_sec_record($conDB, $iqama, 'msauth', true);
    session_regenerate_id(true);
    unset($_SESSION['otp_verification']);
    $_SESSION['auth_user'] = [
        'user_id' => $user['id_iqama'],
        'numeric_id' => $user['id'],
        'fullname' => $user['fullname'],
        'email' => $user['email'],
        'user_type' => $user['user_type'],
        'dept' => $user['dept'],
    ];
    $_SESSION['session_login_time'] = time();
    $_SESSION['login_method'] = 'microsoft';

    require_once __DIR__ . '/includes/init.php';
    ActivityLogger::logLogin($user['id_iqama'], $user['fullname']);

    if (!empty($pending['remember_me'])) {
        $token = bin2hex(random_bytes(32));
        $hashedToken = hash('sha256', $token);
        $expiryDate = date('Y-m-d H:i:s', time() + (30 * 24 * 60 * 60));
        $upd = mysqli_prepare($conDB, "UPDATE `admin_login` SET `otp`=NULL, `otp_expiration`=NULL, `last_login`=NOW(), `remember_token`=?, `remember_token_expiry`=? WHERE `id_iqama`=?");
        mysqli_stmt_bind_param($upd, "sss", $hashedToken, $expiryDate, $iqama);
        mysqli_stmt_execute($upd);
        setcookie('remember_me', $iqama . ':' . $token, time() + (30 * 24 * 60 * 60), '/', '', isset($_SERVER['HTTPS']), true);
    } else {
        $upd = mysqli_prepare($conDB, "UPDATE `admin_login` SET `otp`=NULL, `otp_expiration`=NULL, `last_login`=NOW(), `remember_token`=NULL, `remember_token_expiry`=NULL WHERE `id_iqama`=?");
        mysqli_stmt_bind_param($upd, "s", $iqama);
        mysqli_stmt_execute($upd);
        setcookie('remember_me', '', time() - 3600, '/');
    }

    header("Location: ./dashboard.php");
    exit();

} catch (RuntimeException $e) {
    $errorMessage = $e->getMessage();
    if ($iqama !== '') {
        login_sec_record($conDB, $iqama, 'msauth', false);
    }
} catch (\Throwable $e) {
    error_log('[MSAUTH] ' . $e->getMessage());
    $errorMessage = 'Microsoft sign-in could not be completed. Please try again.';
}

// --- Failure page ---
$canUseEmail = $iqama !== '' && msauth_enabled($conDB) && msauth_settings($conDB)['mode'] === 'fallback';
$csrfToken = login_sec_csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Microsoft Sign-in</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>body { font-family: 'Roboto', sans-serif; background: #050506; }</style>
</head>
<body class="min-h-screen flex items-center justify-center p-4 text-gray-200">
    <div class="w-full max-w-md bg-[#0b0b0f] border border-white/10 rounded-2xl p-8 shadow-2xl">
        <h1 class="text-2xl font-bold text-white mb-4">Microsoft Sign-in</h1>
        <div class="mb-6 p-3 rounded-lg text-sm bg-red-500/10 border border-red-500/30 text-red-400"><?= htmlspecialchars($errorMessage, ENT_QUOTES) ?></div>
        <?php if ($canUseEmail): ?>
            <form method="post" action="./login.php" class="mb-3">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES) ?>">
                <input type="hidden" name="id_iqama" value="<?= htmlspecialchars($iqama, ENT_QUOTES) ?>">
                <input type="hidden" name="use_email_otp" value="1">
                <?php if (!empty($pending['remember_me'])): ?><input type="hidden" name="remember_me" value="1"><?php endif; ?>
                <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 rounded-lg transition">Send me an email code instead</button>
            </form>
        <?php endif; ?>
        <a href="./index.php" class="block text-center w-full border border-white/15 hover:bg-white/5 py-3 rounded-lg transition">Back to login</a>
    </div>
</body>
</html>
