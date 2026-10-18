<?php
/********************************************************************************
 * Microsoft Sign-in (Microsoft Entra ID, OpenID Connect) for OTP users.
 * - Switched on/off in App Settings > Integrations ('msauth_enabled'); configured in
 *   App Settings > Microsoft Sign In (tenant, client ID/secret, mode, allowed domains).
 * - Staff who normally get an email OTP are sent to Microsoft instead; Microsoft applies
 *   the company's password + MFA policy. Normal employees (ID + password) are not affected.
 * - Authorization code flow with PKCE, state and nonce. The ID token comes straight from
 *   the token endpoint over TLS using the client secret, so its claims (issuer, audience,
 *   tenant, expiry, nonce) are checked without a separate signature download.
 * - On first sign-in the account is matched by email (and the ID typed on the login page),
 *   then bound to the Microsoft object ID (admin_login.ms_oid) for later sign-ins.
 ********************************************************************************/

if (!function_exists('msauth_settings')) {

    /** Seeds the Microsoft Sign In settings rows on first use. */
    function msauth_ensure_settings($conDB)
    {
        static $done = false;
        if ($done || !($conDB instanceof mysqli)) {
            return;
        }
        $done = true;
        // name => [default, input_type, description, options]
        $rows = [
            'msauth_enabled' => ['0', 'select', 'Enable Microsoft Sign-in (Entra ID) for OTP users', json_encode(['1' => 'Enabled', '0' => 'Disabled'])],
            'msauth_tenant_id' => ['1dd2e950-8575-4619-859c-bdb07e7d4695', 'text', 'Tenant ID - Azure: App registrations > HR System Sign-in > Overview > "Directory (tenant) ID"', null],
            'msauth_client_id' => ['', 'text', 'Client ID - Azure: App registrations > HR System Sign-in > Overview > "Application (client) ID"', null],
            'msauth_client_secret' => ['', 'password', 'Client Secret - Azure: HR System Sign-in > Certificates & secrets > Client secrets > "Value" column (NOT "Secret ID"; shown only once when created)', null],
            'msauth_mode' => ['fallback', 'select', 'Sign-in Mode - what happens when Microsoft sign-in fails or is cancelled', json_encode([
                'fallback' => 'Microsoft first - email OTP allowed as backup',
                'required' => 'Microsoft only - no email OTP',
            ])],
            // Who signs in with Microsoft: 'selected' (users added in the Microsoft Sign In tab) or 'all'
            // (forced for every OTP user). Edited by the radio buttons on that tab, not a plain field.
            'msauth_scope' => ['selected', 'select', 'Microsoft Sign-in: users (selected / all)', json_encode(['selected' => 'Selected users only', 'all' => 'All OTP users (forced)'])],
            'msauth_allowed_domains' => ['almutlak.com', 'text', 'Allowed Email Domains - comma separated, e.g. almutlak.com (staff with another email domain keep using email OTP)', null],
        ];
        $existing = [];
        $res = $conDB->query("SELECT setting_name FROM app_settings WHERE setting_name LIKE 'msauth\\_%'");
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
                // Keep labels current on installs seeded with older wording
                if ($syncDesc) {
                    $syncDesc->bind_param('sss', $desc, $name, $desc);
                    $syncDesc->execute();
                }
                continue;
            }
            // The master switch lives on the Integrations tab; everything else on its own tab
            $group = $name === 'msauth_enabled' ? 'integrations' : ($name === 'msauth_scope' ? 'msauth_internal' : 'Microsoft_Sign_In');
            $insert->bind_param('ssssss', $name, $value, $group, $desc, $type, $options);
            $insert->execute();
        }
        $insert->close();
    }

    function msauth_settings($conDB)
    {
        static $cache = null;
        if ($cache !== null) {
            return $cache;
        }
        msauth_ensure_settings($conDB);
        $values = [];
        $res = $conDB->query("SELECT setting_name, setting_value FROM app_settings WHERE setting_name LIKE 'msauth\\_%'");
        while ($res && ($r = $res->fetch_assoc())) {
            $values[$r['setting_name']] = trim((string) $r['setting_value']);
        }
        $domains = array_values(array_filter(array_map(function ($d) {
            return strtolower(ltrim(trim($d), '@'));
        }, explode(',', $values['msauth_allowed_domains'] ?? ''))));
        $cache = [
            'enabled' => ($values['msauth_enabled'] ?? '0') === '1',
            'tenant_id' => $values['msauth_tenant_id'] ?? '',
            'client_id' => $values['msauth_client_id'] ?? '',
            'client_secret' => $values['msauth_client_secret'] ?? '',
            'mode' => ($values['msauth_mode'] ?? 'fallback') === 'required' ? 'required' : 'fallback',
            'scope' => ($values['msauth_scope'] ?? 'selected') === 'all' ? 'all' : 'selected',
            'domains' => $domains,
        ];
        return $cache;
    }

    /** Integrations switch only (used by App Settings to show/hide the config tab). */
    function msauth_switch_on($conDB)
    {
        return msauth_settings($conDB)['enabled'];
    }

    /** Switched on AND fully configured - only then does the login page use Microsoft. */
    function msauth_enabled($conDB)
    {
        $s = msauth_settings($conDB);
        return $s['enabled'] && $s['tenant_id'] !== '' && $s['client_id'] !== '' && $s['client_secret'] !== '';
    }

    function msauth_email_allowed($email, array $domains)
    {
        $email = strtolower(trim((string) $email));
        $at = strrpos($email, '@');
        if ($at === false || empty($domains)) {
            return false;
        }
        return in_array(substr($email, $at + 1), $domains, true);
    }

    /** Absolute URL of microsoft_callback.php in the app root (this file lives in includes/MicrosoftAuth/). */
    function msauth_redirect_uri()
    {
        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (($_SERVER['SERVER_PORT'] ?? '') == 443)
            || (strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https');
        $host = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');

        $appRoot = str_replace('\\', '/', dirname(__DIR__, 2));
        $docRoot = rtrim(str_replace('\\', '/', (string) realpath($_SERVER['DOCUMENT_ROOT'] ?? '')), '/');
        if ($docRoot !== '' && stripos($appRoot, $docRoot) === 0) {
            $basePath = substr($appRoot, strlen($docRoot));
        } else {
            $basePath = rtrim(str_replace('\\', '/', dirname((string) ($_SERVER['SCRIPT_NAME'] ?? '/'))), '/');
        }
        return ($https ? 'https' : 'http') . '://' . $host . rtrim($basePath, '/') . '/microsoft_callback.php';
    }

    function msauth_b64url($bytes)
    {
        return rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');
    }

    /**
     * Starts a sign-in for an OTP user: stores state/nonce/PKCE in the session and
     * returns the Microsoft authorize URL to redirect to.
     */
    function msauth_begin($conDB, $idIqama, $email, $rememberMe)
    {
        $s = msauth_settings($conDB);
        $state = bin2hex(random_bytes(16));
        $nonce = bin2hex(random_bytes(16));
        $verifier = msauth_b64url(random_bytes(32));

        // After an admin "Revoke session", Microsoft must ask for the password (and MFA per policy) again
        msauth_ensure_oid_column($conDB);
        $reauthStmt = mysqli_prepare($conDB, "SELECT `ms_reauth` FROM `admin_login` WHERE `id_iqama` = ? LIMIT 1");
        mysqli_stmt_bind_param($reauthStmt, "s", $idIqama);
        mysqli_stmt_execute($reauthStmt);
        $forceReauth = (int) (mysqli_fetch_assoc(mysqli_stmt_get_result($reauthStmt))['ms_reauth'] ?? 0) === 1;

        $_SESSION['msauth'] = [
            'id_iqama' => (string) $idIqama,
            'state' => $state,
            'nonce' => $nonce,
            'verifier' => $verifier,
            'remember_me' => (bool) $rememberMe,
            'expires' => time() + 600,
        ];

        $params = [
            'client_id' => $s['client_id'],
            'response_type' => 'code',
            'redirect_uri' => msauth_redirect_uri(),
            'response_mode' => 'query', // top-level GET back to us, so the SameSite=Lax session cookie is sent
            'scope' => 'openid profile email',
            'state' => $state,
            'nonce' => $nonce,
            'code_challenge' => msauth_b64url(hash('sha256', $verifier, true)),
            'code_challenge_method' => 'S256',
            'login_hint' => (string) $email,
        ];
        if ($forceReauth) {
            $params['prompt'] = 'login';
            $params['max_age'] = 0;
        }
        return 'https://login.microsoftonline.com/' . rawurlencode($s['tenant_id']) . '/oauth2/v2.0/authorize?' . http_build_query($params);
    }

    function msauth_http_post($url, array $fields)
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($fields),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
        ]);
        $body = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        return [$status, $body === false ? null : json_decode($body, true), $error];
    }

    /**
     * Exchanges the authorization code and returns the verified ID token claims.
     * Throws RuntimeException with a user-safe message on any failure.
     */
    function msauth_complete($conDB, array $pending, $code)
    {
        $s = msauth_settings($conDB);
        [$status, $json, $curlError] = msauth_http_post(
            'https://login.microsoftonline.com/' . rawurlencode($s['tenant_id']) . '/oauth2/v2.0/token',
            [
                'client_id' => $s['client_id'],
                'client_secret' => $s['client_secret'],
                'grant_type' => 'authorization_code',
                'code' => (string) $code,
                'redirect_uri' => msauth_redirect_uri(),
                'code_verifier' => $pending['verifier'],
                'scope' => 'openid profile email',
            ]
        );
        if ($status !== 200 || empty($json['id_token'])) {
            error_log('[MSAUTH] Token exchange failed (HTTP ' . $status . '): ' . ($curlError ?: ($json['error_description'] ?? 'no id_token')));
            throw new RuntimeException('Microsoft sign-in could not be completed. Please try again.');
        }

        $parts = explode('.', (string) $json['id_token']);
        $claims = count($parts) === 3 ? json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true) : null;
        if (!is_array($claims)) {
            throw new RuntimeException('Microsoft returned an unreadable sign-in response.');
        }

        $now = time();
        $expectedIssuer = 'https://login.microsoftonline.com/' . $s['tenant_id'] . '/v2.0';
        $checks = [
            'issuer' => strcasecmp((string) ($claims['iss'] ?? ''), $expectedIssuer) === 0,
            'tenant' => strcasecmp((string) ($claims['tid'] ?? ''), $s['tenant_id']) === 0,
            'audience' => (string) ($claims['aud'] ?? '') === $s['client_id'],
            'expiry' => (int) ($claims['exp'] ?? 0) > $now - 60,
            'not_before' => (int) ($claims['nbf'] ?? 0) <= $now + 60,
            'nonce' => hash_equals($pending['nonce'], (string) ($claims['nonce'] ?? '')),
            'object_id' => !empty($claims['oid']),
        ];
        foreach ($checks as $name => $ok) {
            if (!$ok) {
                error_log('[MSAUTH] ID token check failed: ' . $name);
                throw new RuntimeException('Microsoft sign-in response could not be verified.');
            }
        }
        return $claims;
    }

    /** Adds admin_login.ms_oid on first use (Microsoft object ID bound at first sign-in). */
    function msauth_ensure_oid_column($conDB)
    {
        static $done = false;
        if ($done) {
            return;
        }
        $done = true;
        $res = mysqli_query($conDB, "SHOW COLUMNS FROM `admin_login` LIKE 'ms_oid'");
        if ($res && mysqli_num_rows($res) === 0) {
            mysqli_query($conDB, "ALTER TABLE `admin_login` ADD COLUMN `ms_oid` VARCHAR(64) NULL DEFAULT NULL");
        }
        // Per-user second factor, assigned in App Settings > Microsoft Sign In > Users:
        // 'email_otp' (default) or 'microsoft'
        $res = mysqli_query($conDB, "SHOW COLUMNS FROM `admin_login` LIKE 'login_method'");
        if ($res && mysqli_num_rows($res) === 0) {
            mysqli_query($conDB, "ALTER TABLE `admin_login` ADD COLUMN `login_method` VARCHAR(20) NOT NULL DEFAULT 'email_otp'");
        }
        // Set by "Revoke session": the next Microsoft sign-in must re-enter credentials (prompt=login)
        $res = mysqli_query($conDB, "SHOW COLUMNS FROM `admin_login` LIKE 'ms_reauth'");
        if ($res && mysqli_num_rows($res) === 0) {
            mysqli_query($conDB, "ALTER TABLE `admin_login` ADD COLUMN `ms_reauth` TINYINT(1) NOT NULL DEFAULT 0");
        }
    }

    /**
     * Revokes a user's Microsoft 2FA trust only: the next sign-in must go through Microsoft with
     * password + MFA again (prompt=login), and "remember me" (which skips sign-in) is cleared.
     * The user's current app session is NOT ended. Returns [ok, message].
     */
    function msauth_revoke_user($conDB, $idIqama)
    {
        msauth_ensure_oid_column($conDB);
        $stmt = mysqli_prepare($conDB, "SELECT `id` FROM `admin_login` WHERE `id_iqama` = ? AND `user_type` <> 'employee' LIMIT 1");
        mysqli_stmt_bind_param($stmt, "s", $idIqama);
        mysqli_stmt_execute($stmt);
        $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        if (!$row) {
            return [false, 'User not found.'];
        }
        $accountId = (int) $row['id'];

        $upd = mysqli_prepare($conDB, "UPDATE `admin_login` SET `remember_token` = NULL, `remember_token_expiry` = NULL, `ms_reauth` = 1 WHERE `id` = ?");
        mysqli_stmt_bind_param($upd, "i", $accountId);
        mysqli_stmt_execute($upd);

        return [true, 'Microsoft 2FA revoked. The user stays signed in now, but the next sign-in must go through Microsoft with password + MFA again.'];
    }

    /** Clears the forced re-authentication flag after a successful Microsoft sign-in. */
    function msauth_clear_reauth($conDB, $idIqama)
    {
        msauth_ensure_oid_column($conDB);
        $stmt = mysqli_prepare($conDB, "UPDATE `admin_login` SET `ms_reauth` = 0 WHERE `id_iqama` = ?");
        mysqli_stmt_bind_param($stmt, "s", $idIqama);
        mysqli_stmt_execute($stmt);
    }

    /** True when this OTP user must sign in with Microsoft (forced for all, or added to the selected list). */
    function msauth_user_assigned($conDB, $idIqama)
    {
        if (msauth_settings($conDB)['scope'] === 'all') {
            return true;
        }
        msauth_ensure_oid_column($conDB);
        $stmt = mysqli_prepare($conDB, "SELECT `login_method` FROM `admin_login` WHERE `id_iqama` = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, "s", $idIqama);
        mysqli_stmt_execute($stmt);
        $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        return ($row['login_method'] ?? '') === 'microsoft';
    }

    /** OTP users (everyone except normal employees) with their assigned sign-in method. */
    function msauth_list_users($conDB)
    {
        msauth_ensure_oid_column($conDB);
        $users = [];
        $res = mysqli_query($conDB, "SELECT a.`id_iqama`, a.`emp_id`, COALESCE(NULLIF(e.`name`, ''), a.`fullname`) AS `name`, a.`email`, a.`user_type`, a.`login_method`,
                (a.`ms_oid` IS NOT NULL AND a.`ms_oid` <> '') AS `linked`, a.`ms_reauth`,
                (SELECT COUNT(*) FROM `user_activity_log` u WHERE u.`user_id` = a.`id` AND u.`status` = 'active') AS `active_sessions`,
                (SELECT MAX(l.`created_at`) FROM `login_attempts` l WHERE l.`identifier` = a.`id_iqama` AND l.`kind` = 'msauth' AND l.`success` = 1) AS `last_ms_login`
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
                'method' => $r['login_method'] === 'microsoft' ? 'microsoft' : 'email_otp',
                'linked' => (bool) $r['linked'],
                'reauth' => (int) $r['ms_reauth'] === 1,
                'active_sessions' => (int) $r['active_sessions'],
                'last_ms_login' => (string) ($r['last_ms_login'] ?? ''),
            ];
        }
        return $users;
    }

    /** Saves who uses Microsoft sign-in: 'selected' or 'all'. */
    function msauth_set_scope($conDB, $scope)
    {
        msauth_ensure_settings($conDB);
        $scope = $scope === 'all' ? 'all' : 'selected';
        $stmt = mysqli_prepare($conDB, "UPDATE app_settings SET setting_value = ? WHERE setting_name = 'msauth_scope'");
        mysqli_stmt_bind_param($stmt, "s", $scope);
        return mysqli_stmt_execute($stmt) ? [true, 'Saved.'] : [false, 'Could not save.'];
    }

    /** Assigns the sign-in method for one OTP user. Returns [ok, message]. */
    function msauth_set_user_method($conDB, $idIqama, $method)
    {
        msauth_ensure_oid_column($conDB);
        if (!in_array($method, ['email_otp', 'microsoft'], true)) {
            return [false, 'Unknown sign-in method.'];
        }
        $stmt = mysqli_prepare($conDB, "UPDATE `admin_login` SET `login_method` = ? WHERE `id_iqama` = ? AND `user_type` <> 'employee'");
        mysqli_stmt_bind_param($stmt, "ss", $method, $idIqama);
        mysqli_stmt_execute($stmt);
        if (mysqli_stmt_affected_rows($stmt) < 0) {
            return [false, 'Could not update this user.'];
        }
        return [true, 'Saved.'];
    }

    /** Settings test: confirms the tenant, client ID and secret are accepted by Microsoft. */
    function msauth_test_connection($conDB)
    {
        $s = msauth_settings($conDB);
        if ($s['tenant_id'] === '' || $s['client_id'] === '' || $s['client_secret'] === '') {
            return [false, 'Fill in Tenant ID, Client ID and Client Secret first, then save.'];
        }
        [$status, $json, $curlError] = msauth_http_post(
            'https://login.microsoftonline.com/' . rawurlencode($s['tenant_id']) . '/oauth2/v2.0/token',
            [
                'client_id' => $s['client_id'],
                'client_secret' => $s['client_secret'],
                'grant_type' => 'client_credentials',
                'scope' => 'https://graph.microsoft.com/.default',
            ]
        );
        if ($status === 200 && !empty($json['access_token'])) {
            return [true, 'Microsoft accepted the Tenant ID, Client ID and Client Secret. Make sure the Redirect URI below is registered in the app.'];
        }
        $msg = $curlError ?: (string) ($json['error_description'] ?? ('HTTP ' . $status));
        return [false, 'Microsoft rejected the settings: ' . strtok($msg, "\r\n")];
    }
}
