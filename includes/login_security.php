<?php
/********************************************************************************
 * Login security helpers (brute-force throttling, OTP generation, password policy)
 * - Failed attempts are recorded in `login_attempts` (table self-creates on first use).
 * - An identifier (ID/Iqama) is locked after LOGIN_SEC_MAX_PER_ID failures, and a
 *   client IP after LOGIN_SEC_MAX_PER_IP failures, within LOGIN_SEC_WINDOW seconds.
 *   A successful login resets the identifier's counter.
 * - OTP emails are limited to one per LOGIN_SEC_OTP_COOLDOWN seconds.
 ********************************************************************************/

if (!defined('LOGIN_SEC_WINDOW')) {
    define('LOGIN_SEC_WINDOW', 900);          // 15 minutes
    define('LOGIN_SEC_MAX_PER_ID', 5);
    define('LOGIN_SEC_MAX_PER_IP', 50);       // generous: many staff may share one office IP
    define('LOGIN_SEC_OTP_COOLDOWN', 60);     // seconds between OTP emails
    define('LOGIN_SEC_OTP_TTL', 120);         // OTP lifetime in seconds
    define('LOGIN_SEC_MIN_PASSWORD', 8);
}

if (!function_exists('login_sec_client_ip')) {
    function login_sec_client_ip()
    {
        return substr((string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown'), 0, 45);
    }

    function login_sec_ensure_table($conDB)
    {
        static $done = false;
        if ($done) {
            return;
        }
        $done = true;
        mysqli_query($conDB, "CREATE TABLE IF NOT EXISTS `login_attempts` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `identifier` VARCHAR(64) NOT NULL,
            `ip_address` VARCHAR(45) NOT NULL,
            `kind` VARCHAR(20) NOT NULL,
            `success` TINYINT(1) NOT NULL DEFAULT 0,
            `created_at` DATETIME NOT NULL,
            PRIMARY KEY (`id`),
            KEY `idx_identifier_time` (`identifier`, `created_at`),
            KEY `idx_ip_time` (`ip_address`, `created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    function login_sec_record($conDB, $identifier, $kind, $success)
    {
        login_sec_ensure_table($conDB);
        $identifier = substr((string) $identifier, 0, 64);
        $ip = login_sec_client_ip();
        $successInt = $success ? 1 : 0;
        $stmt = mysqli_prepare($conDB, "INSERT INTO `login_attempts` (`identifier`, `ip_address`, `kind`, `success`, `created_at`) VALUES (?, ?, ?, ?, NOW())");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "sssi", $identifier, $ip, $kind, $successInt);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
        if (!$success) {
            error_log('[LOGIN] Failed ' . $kind . ' for ID=' . $identifier . ' from ' . $ip);
        }
    }

    /**
     * Returns true when this identifier or the client IP has too many recent failures.
     * Failures before the identifier's last successful login are not counted.
     */
    function login_sec_is_locked($conDB, $identifier)
    {
        login_sec_ensure_table($conDB);
        $identifier = substr((string) $identifier, 0, 64);
        $ip = login_sec_client_ip();
        $window = (int) LOGIN_SEC_WINDOW;

        $sql = "SELECT COUNT(*) AS c FROM `login_attempts`
                WHERE `identifier` = ? AND `success` = 0
                  AND `created_at` > DATE_SUB(NOW(), INTERVAL {$window} SECOND)
                  AND `created_at` > COALESCE((SELECT MAX(s.`created_at`) FROM (SELECT `created_at` FROM `login_attempts` WHERE `identifier` = ? AND `success` = 1) s), '1970-01-01')";
        $stmt = mysqli_prepare($conDB, $sql);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "ss", $identifier, $identifier);
            mysqli_stmt_execute($stmt);
            $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
            mysqli_stmt_close($stmt);
            if ((int) ($row['c'] ?? 0) >= LOGIN_SEC_MAX_PER_ID) {
                return true;
            }
        }

        $stmt = mysqli_prepare($conDB, "SELECT COUNT(*) AS c FROM `login_attempts` WHERE `ip_address` = ? AND `success` = 0 AND `created_at` > DATE_SUB(NOW(), INTERVAL {$window} SECOND)");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "s", $ip);
            mysqli_stmt_execute($stmt);
            $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
            mysqli_stmt_close($stmt);
            if ((int) ($row['c'] ?? 0) >= LOGIN_SEC_MAX_PER_IP) {
                return true;
            }
        }
        return false;
    }

    /**
     * Per-IP rate limit for unauthenticated lookups (e.g. ID check, reset verification).
     * Records this call and returns true when the IP exceeded $limit calls of $kind in the window.
     */
    function login_sec_ip_rate_limited($conDB, $kind, $limit)
    {
        login_sec_ensure_table($conDB);
        $ip = login_sec_client_ip();
        $window = (int) LOGIN_SEC_WINDOW;
        $stmt = mysqli_prepare($conDB, "SELECT COUNT(*) AS c FROM `login_attempts` WHERE `ip_address` = ? AND `kind` = ? AND `created_at` > DATE_SUB(NOW(), INTERVAL {$window} SECOND)");
        $count = 0;
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "ss", $ip, $kind);
            mysqli_stmt_execute($stmt);
            $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
            mysqli_stmt_close($stmt);
            $count = (int) ($row['c'] ?? 0);
        }
        if ($count >= $limit) {
            return true;
        }
        // Identifier is namespaced so these rows never reset or count toward an account lock
        login_sec_record($conDB, $kind . ':' . $ip, $kind, true);
        return false;
    }

    /** Normalizes a Saudi mobile typed as 5XXXXXXXX, 9665XXXXXXXX or +966 5XX... to the stored 05XXXXXXXX form. */
    function login_sec_normalize_mobile($mobile)
    {
        $digits = preg_replace('/\D+/', '', (string) $mobile);
        if (preg_match('/^(?:00)?966(5\d{8})$/', $digits, $m)) {
            return '0' . $m[1];
        }
        if (preg_match('/^5\d{8}$/', $digits)) {
            return '0' . $digits;
        }
        return $digits;
    }

    /** Returns this session's CSRF token, creating it on first use. Session must be started. */
    function login_sec_csrf_token()
    {
        if (empty($_SESSION['csrf_login_token'])) {
            $_SESSION['csrf_login_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_login_token'];
    }

    /** Checks the token sent as POST field `csrf_token` or header `X-CSRF-Token`. */
    function login_sec_csrf_valid()
    {
        $sent = (string) ($_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ''));
        $expected = (string) ($_SESSION['csrf_login_token'] ?? '');
        return $expected !== '' && $sent !== '' && hash_equals($expected, $sent);
    }

    function login_sec_csrf_message()
    {
        return 'Your session has expired. Please refresh the page and try again.';
    }

    function login_sec_locked_message()
    {
        return 'Too many failed attempts. Please wait ' . (int) (LOGIN_SEC_WINDOW / 60) . ' minutes and try again.';
    }

    /** Cryptographically secure 6-digit OTP. */
    function login_sec_generate_otp()
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    /** True when an OTP was sent less than LOGIN_SEC_OTP_COOLDOWN seconds ago (based on its expiry time). */
    function login_sec_otp_recently_sent($otpExpiryTimestamp)
    {
        if (empty($otpExpiryTimestamp)) {
            return false;
        }
        $expiry = is_numeric($otpExpiryTimestamp) ? (int) $otpExpiryTimestamp : strtotime((string) $otpExpiryTimestamp);
        if (!$expiry) {
            return false;
        }
        $sentAt = $expiry - LOGIN_SEC_OTP_TTL;
        return (time() - $sentAt) < LOGIN_SEC_OTP_COOLDOWN;
    }

    /** Random 10-character temporary password without look-alike characters (0/O, 1/l/I). */
    function login_sec_generate_temp_password()
    {
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789';
        $out = '';
        for ($i = 0; $i < 10; $i++) {
            $out .= $chars[random_int(0, strlen($chars) - 1)];
        }
        return $out;
    }

    /** Adds admin_login.must_change_password on first use (set by an HR reset, cleared once the employee picks a new password). */
    function login_sec_ensure_must_change_column($conDB)
    {
        static $done = false;
        if ($done) {
            return;
        }
        $done = true;
        $res = mysqli_query($conDB, "SHOW COLUMNS FROM `admin_login` LIKE 'must_change_password'");
        if ($res && mysqli_num_rows($res) === 0) {
            mysqli_query($conDB, "ALTER TABLE `admin_login` ADD COLUMN `must_change_password` TINYINT(1) NOT NULL DEFAULT 0");
        }
    }

    /** Returns an error message, or null when the password meets the policy. */
    function login_sec_password_error($password)
    {
        if (strlen((string) $password) < LOGIN_SEC_MIN_PASSWORD) {
            return 'Password must be at least ' . LOGIN_SEC_MIN_PASSWORD . ' characters long.';
        }
        return null;
    }

    /**
     * Sends the OTP email (dark template when available). Throws on failure.
     */
    function login_send_otp_mail($conDB, $toEmail, $toName, $otp)
    {
        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = get_setting($conDB, 'smtp_host');
        $mail->SMTPAuth = true;
        $mail->Username = get_setting($conDB, 'smtp_user');
        $mail->Password = get_setting($conDB, 'smtp_pass');
        $encryption_setting = get_setting($conDB, 'smtp_encryption');
        switch (strtolower($encryption_setting)) {
            case 'tls':
                $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                break;
            case 'ssl':
                $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
                break;
        }
        $mail->Port = get_setting($conDB, 'smtp_port');
        $mail->CharSet = 'UTF-8';
        $mail->setFrom(get_setting($conDB, 'smtp_user'), get_setting($conDB, 'application_name'));
        $mail->addAddress($toEmail, $toName);
        $mail->isHTML(true);
        $mail->Subject = __("email_otp_subject"); // Use translation

        if (file_exists(__DIR__ . '/PHPMailerMaster/otp_email_template_dark.html')) {
            $emailBody = file_get_contents(__DIR__ . '/PHPMailerMaster/otp_email_template_dark.html');

            $logoUrl = 'https://hr.almutlaksystem.com/assets/logo/logo_color_sm.png'; // Use white logo for dark theme

            // Build per-digit boxes so the code is easy to read/select in any mail client (Outlook's Word engine ignores <input>)
            $otpDigitCells = '';
            $otpDigits = str_split((string)$otp);
            foreach ($otpDigits as $i => $digit) {
                if ($i > 0) {
                    $otpDigitCells .= '<td style="width:10px;font-size:0;line-height:0;">&nbsp;</td>';
                }
                $otpDigitCells .= '<td style="background-color:#2a2a2a;border:1px solid #444444;border-radius:6px;width:40px;height:52px;text-align:center;vertical-align:middle;font-family:\'Inter\',Arial,sans-serif;font-size:30px;font-weight:700;color:#ffffff;">' . htmlspecialchars($digit) . '</td>';
            }

            // Replace placeholders with variables
            $replacements = [
                '{{LOGO_URL}}' => $logoUrl,
                '{{USER_FULLNAME}}' => htmlspecialchars($toName),
                '{{OTP_CODE}}' => $otp,
                '{{OTP_DIGIT_CELLS}}' => $otpDigitCells,
                '{{EMAIL_HEADING}}' => __('email_heading'),
                '{{EMAIL_GREETING}}' => __('email_greeting'),
                '{{EMAIL_VERIFICATION_CODE_LABEL}}' => __('email_verification_code_label'),
                '{{EMAIL_EXPIRATION_NOTICE}}' => __('email_expiration_notice'),
                '{{EMAIL_SECURITY_NOTICE}}' => __('email_security_notice'),
            ];
            $emailBody = str_replace(array_keys($replacements), array_values($replacements), $emailBody);
            // Add schema.org microdata for Outlook's "Copy Code" feature
            $schemaMarkup = '<div style="display:none; max-height:0; overflow:hidden;"><div itemscope itemtype="http://schema.org/EmailMessage"><div itemprop="potentialAction" itemscope itemtype="http://schema.org/ViewAction"><div itemprop="result" itemscope itemtype="http://schema.org/OneTimeCode"><meta itemprop="code" content="' . $otp . '"/></div></div></div></div>';
            $mail->Body = $emailBody . $schemaMarkup;
        } else {
            $mail->Body = "Your one-time password (OTP) is: <strong>$otp</strong>";
        }

        $mail->send();
    }
}
