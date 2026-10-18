<?php
/********************************************************************************
 * Second factor for staff (OTP users, i.e. every user_type except 'employee').
 * Combines Microsoft Sign-in (includes/MicrosoftAuth) and Google Authenticator
 * (includes/GoogleAuth): which of them a user has, and whether the email OTP may still be
 * used. Used by the login page (choice popup via check_user_type.php), login.php,
 * microsoft_callback.php and google_authenticator.php.
 ********************************************************************************/

require_once __DIR__ . '/MicrosoftAuth/microsoft_auth.php';
require_once __DIR__ . '/GoogleAuth/google_auth.php';

if (!function_exists('login_second_factors')) {

    /**
     * Which second factors an OTP user (non-'employee') has, for the login page and login.php:
     *  - methods:        available methods in display order, subset of ['microsoft', 'google']
     *  - email_fallback: email OTP may be used instead (no assigned method is in 'required' mode)
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
        return ['methods' => $methods, 'email_fallback' => !$required, 'blocked' => $blocked];
    }
}
