<?php
/**
 * Access gate for connection_monitor.php and its two endpoints - deliberately
 * bypasses the normal login/session chain (session_check.php), which itself
 * runs several DB queries. If MySQL's connection pool is already exhausted,
 * the normal login can fail too, locking admins out of the one tool that
 * would show them why. This gate is pure PHP - no DB, no session - so it
 * works even when every connection slot is taken.
 *
 * Trade-off: the OTP is just the current server time (HHMM), not tied to a
 * real admin identity. Anyone who knows the scheme can get in. Treat this
 * page's URL as admin-only information, not something to link from a menu.
 */
if (!defined('CONNMON_OTP_SECRET')) {
    define('CONNMON_OTP_SECRET', 'almutlak-connmon-8f2ad61c9b7e4f0a');
}

// ---- Session timing config - the one place to change these ----
// CONNMON_SESSION_SECONDS: total time granted after entering the access code.
// CONNMON_EXTEND_SECONDS: time granted per "Extend session" click (kept separate
//   from SESSION so you can shrink SESSION for testing without also shrinking
//   what Extend grants - Extend should still prove out a real-length session).
// CONNMON_ALERT_SECONDS: comma-separated "show a warning at N seconds remaining"
//   checkpoints - e.g. '300,60' warns at 5 minutes and again at 1 minute left.
if (!defined('CONNMON_SESSION_SECONDS')) {
    define('CONNMON_SESSION_SECONDS', 3600);
}
if (!defined('CONNMON_EXTEND_SECONDS')) {
    define('CONNMON_EXTEND_SECONDS', 3600);
}
if (!defined('CONNMON_ALERT_SECONDS')) {
    define('CONNMON_ALERT_SECONDS', '300,60');
}

if (!function_exists('connmon_alert_thresholds')) {
    function connmon_alert_thresholds() {
        $values = array_map('intval', explode(',', CONNMON_ALERT_SECONDS));
        $values = array_values(array_filter($values, fn($v) => $v > 0));
        rsort($values);
        return $values ?: [300, 60];
    }
}

if (!function_exists('connmon_current_otp')) {
    function connmon_current_otp($offsetMinutes = 0) {
        date_default_timezone_set('Asia/Riyadh');
        return date('Hi', time() + ($offsetMinutes * 60));
    }
}

if (!function_exists('connmon_verify_otp')) {
    function connmon_verify_otp($submitted) {
        $submitted = trim((string) $submitted);
        // +/-1 minute grace so a code typed right as the clock ticks over still works.
        return in_array($submitted, [
            connmon_current_otp(0),
            connmon_current_otp(-1),
            connmon_current_otp(1),
        ], true);
    }
}

if (!function_exists('connmon_issue_token')) {
    function connmon_issue_token($seconds = null) {
        $seconds = $seconds !== null ? (int) $seconds : CONNMON_SESSION_SECONDS;
        $expiry = time() + (int) $seconds;
        $sig = hash_hmac('sha256', (string) $expiry, CONNMON_OTP_SECRET);
        setcookie('connmon_access', $expiry . '.' . $sig, [
            'expires'  => $expiry,
            'path'     => '/',
            'httponly' => true,
            'samesite' => 'Strict',
            'secure'   => !empty($_SERVER['HTTPS']),
        ]);
        return $expiry; // setcookie() doesn't update $_COOKIE mid-request, so callers needing
                         // the fresh value right away (e.g. the extend endpoint) must use this.
    }
}

if (!function_exists('connmon_clear_token')) {
    function connmon_clear_token() {
        setcookie('connmon_access', '', [
            'expires'  => time() - 3600,
            'path'     => '/',
            'httponly' => true,
            'samesite' => 'Strict',
            'secure'   => !empty($_SERVER['HTTPS']),
        ]);
    }
}

if (!function_exists('connmon_has_valid_token')) {
    function connmon_has_valid_token() {
        return connmon_token_expiry() !== null;
    }
}

if (!function_exists('connmon_token_expiry')) {
    function connmon_token_expiry() {
        $cookie = $_COOKIE['connmon_access'] ?? '';
        if (!$cookie || strpos($cookie, '.') === false) {
            return null;
        }
        [$expiry, $sig] = explode('.', $cookie, 2);
        $expiry = (int) $expiry;
        if ($expiry < time()) {
            return null;
        }
        $expected = hash_hmac('sha256', (string) $expiry, CONNMON_OTP_SECRET);
        return hash_equals($expected, $sig) ? $expiry : null;
    }
}
