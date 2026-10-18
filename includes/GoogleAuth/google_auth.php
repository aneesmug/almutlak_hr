<?php
/********************************************************************************
 * Google Authenticator sign-in (TOTP, RFC 6238) for OTP users.
 * - Switched on/off in App Settings > Integrations ('gauth_enabled'); configured in
 *   App Settings > Google Authenticator (issuer name, mode, users).
 * - Works with any TOTP app (Google Authenticator, Microsoft Authenticator, Authy...).
 *   Nothing is sent to Google: codes are checked locally against the user's secret.
 * - Enrollment: the first time an assigned user signs in, the email OTP is sent first
 *   (proves who they are - an ID/Iqama alone is not secret), then a QR code is shown
 *   to scan. The secret is saved only after the user types a valid code from the app.
 * - Secrets are stored encrypted (AES-256-GCM) with a key kept in a PHP file outside the
 *   database (includes/GoogleAuth/gauth_secret_key.php, created on first use - back it up; if it is
 *   lost every user has to scan a new QR code).
 * - TOTP codes, secret encryption and the QR code live in google_totp.php; which 2FA
 *   methods a user has (Microsoft / Google / email) is decided in includes/two_factor.php.
 ********************************************************************************/

require_once __DIR__ . '/google_totp.php';

if (!function_exists('gauth_settings')) {

    define('GAUTH_MAX_ATTEMPTS', 5);    // wrong codes per sign-in before going back to the login page
    define('GAUTH_FLOW_TTL', 600);      // seconds to finish the code / QR page

    /** Seeds the Google Authenticator settings rows on first use. */
    function gauth_ensure_settings($conDB)
    {
        static $done = false;
        if ($done || !($conDB instanceof mysqli)) {
            return;
        }
        $done = true;
        // name => [default, input_type, description, options]
        $rows = [
            'gauth_enabled' => ['0', 'select', 'Enable Google Authenticator (2FA) for OTP users', json_encode(['1' => 'Enabled', '0' => 'Disabled'])],
            'gauth_issuer' => ['Al Mutlak HR', 'text', 'Issuer Name - shown above the 6-digit code in the authenticator app (changing it only affects new QR codes)', null],
            'gauth_mode' => ['fallback', 'select', 'Sign-in Mode - what happens when the user cannot use the authenticator app', json_encode([
                'fallback' => 'Authenticator first - email OTP allowed as backup',
                'required' => 'Authenticator only - no email OTP',
            ])],
            // Who uses Google Authenticator: 'selected' (users added in the tab) or 'all' (forced).
            'gauth_scope' => ['selected', 'select', 'Google Authenticator: users (selected / all)', json_encode(['selected' => 'Selected users only', 'all' => 'All OTP users (forced)'])],
        ];
        $existing = [];
        $res = $conDB->query("SELECT setting_name FROM app_settings WHERE setting_name LIKE 'gauth\\_%'");
        while ($res && ($r = $res->fetch_assoc())) {
            $existing[$r['setting_name']] = true;
        }
        $insert = $conDB->prepare("INSERT INTO app_settings (setting_name, setting_value, setting_group, description, input_type, options) VALUES (?, ?, ?, ?, ?, ?)");
        if (!$insert) {
            return;
        }
        $syncDesc = $conDB->prepare("UPDATE app_settings SET description = ? WHERE setting_name = ? AND description <> ?");
        foreach ($rows as $name => [$value, $type, $desc, $options]) {
            if (isset($existing[$name])) {
                if ($syncDesc) {
                    $syncDesc->bind_param('sss', $desc, $name, $desc);
                    $syncDesc->execute();
                }
                continue;
            }
            $group = $name === 'gauth_enabled' ? 'integrations' : ($name === 'gauth_scope' ? 'gauth_internal' : 'Google_Authenticator');
            $insert->bind_param('ssssss', $name, $value, $group, $desc, $type, $options);
            $insert->execute();
        }
        $insert->close();
    }

    function gauth_settings($conDB)
    {
        static $cache = null;
        if ($cache !== null) {
            return $cache;
        }
        gauth_ensure_settings($conDB);
        $values = [];
        $res = $conDB->query("SELECT setting_name, setting_value FROM app_settings WHERE setting_name LIKE 'gauth\\_%'");
        while ($res && ($r = $res->fetch_assoc())) {
            $values[$r['setting_name']] = trim((string) $r['setting_value']);
        }
        $cache = [
            'enabled' => ($values['gauth_enabled'] ?? '0') === '1',
            'issuer' => ($values['gauth_issuer'] ?? '') !== '' ? $values['gauth_issuer'] : 'Al Mutlak HR',
            'mode' => ($values['gauth_mode'] ?? 'fallback') === 'required' ? 'required' : 'fallback',
            'scope' => ($values['gauth_scope'] ?? 'selected') === 'all' ? 'all' : 'selected',
        ];
        return $cache;
    }

    /** Integrations switch (nothing else to configure - no external credentials needed). */
    function gauth_enabled($conDB)
    {
        return gauth_settings($conDB)['enabled'];
    }

    /** Adds the admin_login columns on first use. */
    function gauth_ensure_columns($conDB)
    {
        static $done = false;
        if ($done) {
            return;
        }
        $done = true;
        $columns = [
            // Added in App Settings > Google Authenticator > Users
            'gauth_assigned' => "TINYINT(1) NOT NULL DEFAULT 0",
            // Encrypted TOTP secret, set once the user scanned the QR code and confirmed a code
            'gauth_secret' => "VARCHAR(255) NULL DEFAULT NULL",
            'gauth_enrolled_at' => "DATETIME NULL DEFAULT NULL",
            // Last accepted 30s time step - the same code cannot be used twice
            'gauth_last_step' => "BIGINT NULL DEFAULT NULL",
        ];
        foreach ($columns as $name => $definition) {
            $res = mysqli_query($conDB, "SHOW COLUMNS FROM `admin_login` LIKE '" . $name . "'");
            if ($res && mysqli_num_rows($res) === 0) {
                mysqli_query($conDB, "ALTER TABLE `admin_login` ADD COLUMN `" . $name . "` " . $definition);
            }
        }
    }

    // ---------------------------------------------------------------- users

    function gauth_user_row($conDB, $idIqama)
    {
        gauth_ensure_columns($conDB);
        $stmt = mysqli_prepare($conDB, "SELECT `id`, `id_iqama`, `email`, `gauth_assigned`, `gauth_secret`, `gauth_last_step` FROM `admin_login` WHERE `id_iqama` = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, "s", $idIqama);
        mysqli_stmt_execute($stmt);
        return mysqli_fetch_assoc(mysqli_stmt_get_result($stmt)) ?: null;
    }

    /** True when this OTP user must use Google Authenticator (forced for all, or added to the list). */
    function gauth_user_assigned($conDB, $idIqama)
    {
        if (gauth_settings($conDB)['scope'] === 'all') {
            return true;
        }
        $row = gauth_user_row($conDB, $idIqama);
        return $row && (int) $row['gauth_assigned'] === 1;
    }

    /** True once the user scanned the QR code and the stored secret can be read. */
    function gauth_user_enrolled($conDB, $idIqama)
    {
        $row = gauth_user_row($conDB, $idIqama);
        return $row && !empty($row['gauth_secret']) && gauth_decrypt($row['gauth_secret']) !== null;
    }

    /**
     * Checks a code for an enrolled user and burns its time step (no replay).
     * Returns true on success.
     */
    function gauth_verify_user_code($conDB, $idIqama, $code)
    {
        $row = gauth_user_row($conDB, $idIqama);
        $secret = $row ? gauth_decrypt($row['gauth_secret']) : null;
        if ($secret === null) {
            return false;
        }
        $step = gauth_match_step($secret, $code);
        if ($step === null) {
            return false;
        }
        $id = (int) $row['id'];
        $upd = mysqli_prepare($conDB, "UPDATE `admin_login` SET `gauth_last_step` = ? WHERE `id` = ? AND (`gauth_last_step` IS NULL OR `gauth_last_step` < ?)");
        mysqli_stmt_bind_param($upd, "iii", $step, $id, $step);
        mysqli_stmt_execute($upd);
        return mysqli_stmt_affected_rows($upd) === 1;
    }

    /** Saves a confirmed secret (enrollment finished). */
    function gauth_save_secret($conDB, $idIqama, $secret, $step)
    {
        gauth_ensure_columns($conDB);
        $enc = gauth_encrypt($secret);
        $stmt = mysqli_prepare($conDB, "UPDATE `admin_login` SET `gauth_secret` = ?, `gauth_enrolled_at` = NOW(), `gauth_last_step` = ? WHERE `id_iqama` = ?");
        mysqli_stmt_bind_param($stmt, "sis", $enc, $step, $idIqama);
        return mysqli_stmt_execute($stmt);
    }

    /** OTP users (everyone except normal employees) with their Google Authenticator state. */
    function gauth_list_users($conDB)
    {
        gauth_ensure_columns($conDB);
        $users = [];
        $res = mysqli_query($conDB, "SELECT a.`id_iqama`, a.`emp_id`, COALESCE(NULLIF(e.`name`, ''), a.`fullname`) AS `name`, a.`email`, a.`user_type`, a.`gauth_assigned`,
                (a.`gauth_secret` IS NOT NULL AND a.`gauth_secret` <> '') AS `enrolled`, a.`gauth_enrolled_at`,
                (SELECT COUNT(*) FROM `user_activity_log` u WHERE u.`user_id` = a.`id` AND u.`status` = 'active') AS `active_sessions`,
                (SELECT MAX(l.`created_at`) FROM `login_attempts` l WHERE l.`identifier` = a.`id_iqama` AND l.`kind` = 'gauth' AND l.`success` = 1) AS `last_login`
            FROM `admin_login` a LEFT JOIN `employees` e ON e.`emp_id` = a.`emp_id`
            WHERE a.`user_type` <> 'employee' AND a.`status` = 1
            ORDER BY `name`");
        while ($res && ($r = mysqli_fetch_assoc($res))) {
            $users[] = [
                'id_iqama' => (string) $r['id_iqama'],
                'emp_id' => (string) $r['emp_id'],
                'name' => (string) $r['name'],
                'email' => (string) $r['email'],
                'user_type' => (string) $r['user_type'],
                'assigned' => (int) $r['gauth_assigned'] === 1,
                'enrolled' => (bool) $r['enrolled'],
                'enrolled_at' => (string) ($r['gauth_enrolled_at'] ?? ''),
                'active_sessions' => (int) $r['active_sessions'],
                'last_login' => (string) ($r['last_login'] ?? ''),
            ];
        }
        return $users;
    }

    function gauth_set_scope($conDB, $scope)
    {
        gauth_ensure_settings($conDB);
        $scope = $scope === 'all' ? 'all' : 'selected';
        $stmt = mysqli_prepare($conDB, "UPDATE app_settings SET setting_value = ? WHERE setting_name = 'gauth_scope'");
        mysqli_stmt_bind_param($stmt, "s", $scope);
        return mysqli_stmt_execute($stmt) ? [true, 'Saved.'] : [false, 'Could not save.'];
    }

    /** Adds (true) or removes (false) a user from the Google Authenticator list. */
    function gauth_set_user_assigned($conDB, $idIqama, $assigned)
    {
        gauth_ensure_columns($conDB);
        $flag = $assigned ? 1 : 0;
        $stmt = mysqli_prepare($conDB, "UPDATE `admin_login` SET `gauth_assigned` = ? WHERE `id_iqama` = ? AND `user_type` <> 'employee'");
        mysqli_stmt_bind_param($stmt, "is", $flag, $idIqama);
        if (!mysqli_stmt_execute($stmt)) {
            return [false, 'Could not update this user.'];
        }
        return [true, 'Saved.'];
    }

    /**
     * Lost / new phone: deletes the user's authenticator secret and "remember me", so the next
     * sign-in verifies the email OTP again and shows a new QR code. The current session is kept.
     */
    function gauth_reset_user($conDB, $idIqama)
    {
        gauth_ensure_columns($conDB);
        $stmt = mysqli_prepare($conDB, "UPDATE `admin_login` SET `gauth_secret` = NULL, `gauth_enrolled_at` = NULL, `gauth_last_step` = NULL, `remember_token` = NULL, `remember_token_expiry` = NULL WHERE `id_iqama` = ? AND `user_type` <> 'employee'");
        mysqli_stmt_bind_param($stmt, "s", $idIqama);
        mysqli_stmt_execute($stmt);
        if (mysqli_stmt_affected_rows($stmt) < 1) {
            return [false, 'User not found.'];
        }
        return [true, 'Authenticator reset. At the next sign-in the user confirms the email code and scans a new QR code.'];
    }

    // ---------------------------------------------------------------- sign-in

    /** Signs the user in after a valid code (same session setup as the email OTP / Microsoft paths). */
    function gauth_finish_login($conDB, $idIqama, $rememberMe)
    {
        $stmt = mysqli_prepare($conDB, "SELECT a.*, e.name AS employee_name FROM `admin_login` a LEFT JOIN `employees` e ON a.emp_id = e.emp_id WHERE a.`id_iqama` = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, "s", $idIqama);
        mysqli_stmt_execute($stmt);
        $user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        if (!$user || (int) $user['status'] !== 1 || $user['user_type'] === 'employee') {
            throw new RuntimeException('This account cannot sign in.');
        }
        if (!empty($user['employee_name'])) {
            $user['fullname'] = $user['employee_name'];
        }

        login_sec_record($conDB, $idIqama, 'gauth', true);
        session_regenerate_id(true);
        unset($_SESSION['otp_verification'], $_SESSION['gauth_flow']);
        $_SESSION['auth_user'] = [
            'user_id' => $user['id_iqama'],
            'numeric_id' => $user['id'],
            'fullname' => $user['fullname'],
            'email' => $user['email'],
            'user_type' => $user['user_type'],
            'dept' => $user['dept'],
        ];
        $_SESSION['session_login_time'] = time();
        $_SESSION['login_method'] = 'google';

        require_once __DIR__ . '/../init.php';
        ActivityLogger::logLogin($user['id_iqama'], $user['fullname']);

        if ($rememberMe) {
            $token = bin2hex(random_bytes(32));
            $hashedToken = hash('sha256', $token);
            $expiryDate = date('Y-m-d H:i:s', time() + (30 * 24 * 60 * 60));
            $upd = mysqli_prepare($conDB, "UPDATE `admin_login` SET `otp`=NULL, `otp_expiration`=NULL, `last_login`=NOW(), `remember_token`=?, `remember_token_expiry`=? WHERE `id_iqama`=?");
            mysqli_stmt_bind_param($upd, "sss", $hashedToken, $expiryDate, $idIqama);
            mysqli_stmt_execute($upd);
            setcookie('remember_me', $idIqama . ':' . $token, time() + (30 * 24 * 60 * 60), '/', '', isset($_SERVER['HTTPS']), true);
        } else {
            $upd = mysqli_prepare($conDB, "UPDATE `admin_login` SET `otp`=NULL, `otp_expiration`=NULL, `last_login`=NOW(), `remember_token`=NULL, `remember_token_expiry`=NULL WHERE `id_iqama`=?");
            mysqli_stmt_bind_param($upd, "s", $idIqama);
            mysqli_stmt_execute($upd);
            setcookie('remember_me', '', time() - 3600, '/');
        }
        return $user;
    }
}
