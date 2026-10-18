<?php
/********************************************************************************
 * Second factor for staff (OTP users, i.e. every user_type except 'employee').
 * Combines Microsoft Sign-in (includes/MicrosoftAuth) and Google Authenticator
 * (includes/GoogleAuth): which of them a user has, and whether the email OTP may still be
 * used. Used by the login page (choice popup via check_user_type.php), login.php,
 * microsoft_callback.php and google_authenticator.php.
 * - Integrations switch 'email_otp_fallback' (default on): lets users who have Microsoft /
 *   Google pick an email code instead (no phone at hand). Off = they must use their 2FA.
 *   Staff without any 2FA method always keep the email OTP, and the one-time email check
 *   before the Google Authenticator QR code is not affected.
 ********************************************************************************/

require_once __DIR__ . '/MicrosoftAuth/microsoft_auth.php';
require_once __DIR__ . '/GoogleAuth/google_auth.php';

if (!function_exists('login_second_factors')) {

    /** Seeds the Integrations switch on first use (on, i.e. same behaviour as before it existed). */
    function email_otp_fallback_ensure_setting($conDB)
    {
        static $done = false;
        if ($done || !($conDB instanceof mysqli)) {
            return;
        }
        $done = true;
        $res = $conDB->query("SELECT 1 FROM app_settings WHERE setting_name = 'email_otp_fallback' LIMIT 1");
        if ($res && $res->num_rows === 0) {
            $conDB->query("INSERT INTO app_settings (setting_name, setting_value, setting_group, description, input_type, options)
                VALUES ('email_otp_fallback', '1', 'integrations', 'Allow the email OTP as a backup for users with Microsoft / Google 2FA', 'select', '{\"1\":\"Enabled\",\"0\":\"Disabled\"}')");
        }
    }

    /** Integrations switch: email code may replace Microsoft / Google 2FA. */
    function email_otp_fallback_enabled($conDB)
    {
        static $cache = null;
        if ($cache === null) {
            email_otp_fallback_ensure_setting($conDB);
            $res = $conDB->query("SELECT setting_value FROM app_settings WHERE setting_name = 'email_otp_fallback' LIMIT 1");
            $row = $res ? $res->fetch_assoc() : null;
            $cache = trim((string) ($row['setting_value'] ?? '1')) !== '0';
        }
        return $cache;
    }

    /**
     * Which second factors an OTP user (non-'employee') has, for the login page and login.php:
     *  - methods:        available methods in display order, subset of ['microsoft', 'google']
     *  - email_fallback: email OTP may be used instead (Integrations switch on and no assigned
     *                    method is in 'required' mode)
     *  - blocked:        message when the user cannot sign in at all, else null
     */
    function login_second_factors($conDB, array $user)
    {
        $iqama = (string) $user['id_iqama'];
        $methods = [];
        $required = false;
        $blocked = null;

        if (msauth_enabled($conDB) && msauth_user_assigned($conDB, $iqama)) {
            $ms = msauth_settings($conDB);
            $required = $required || $ms['mode'] === 'required';
            if (msauth_email_allowed($user['email'] ?? '', $ms['domains'])) {
                $methods[] = 'microsoft';
            } elseif ($ms['mode'] === 'required') {
                $blocked = 'Your account email is not a company Microsoft account, so you cannot sign in. Please contact IT support.';
            }
        }
        if (gauth_enabled($conDB) && gauth_user_assigned($conDB, $iqama)) {
            $required = $required || gauth_settings($conDB)['mode'] === 'required';
            $methods[] = 'google';
        }
        if ($methods) {
            $blocked = null; // another method is still available
        }
        return ['methods' => $methods, 'email_fallback' => !$required && email_otp_fallback_enabled($conDB), 'blocked' => $blocked];
    }
}
