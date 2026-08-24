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
    function connmon_issue_token() {
        $expiry = time() + 3600; // 1 hour
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
