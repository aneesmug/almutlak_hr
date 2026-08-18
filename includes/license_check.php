<?php
// Product license enforcement - phones home to the license server on
// snapsbilling.com. Self-heals its own app_settings rows on first use (same
// pattern as the DB export secret key), no migration file needed.
if (defined('LICENSE_CHECK_INCLUDED')) {
    return;
}
define('LICENSE_CHECK_INCLUDED', true);

define('LICENSE_GRACE_DAYS', 3);
define('LICENSE_RECHECK_INTERVAL_SECONDS', 60); // revoke/expiry should take effect within about a minute

function license_ensure_settings_exist($conDB) {
    $defaults = [
        'license_api_url' => '',
        'license_serial_key' => '',
        'license_token' => '',
        'license_cache_valid' => '0',
        'license_cache_status' => 'not_configured',
        'license_cache_message' => 'License key not configured.',
        'license_cache_expires_at' => '',
        'license_last_attempt_at' => '',
        'license_last_verified_at' => '',
    ];
    foreach ($defaults as $name => $value) {
        $stmt = mysqli_prepare($conDB, "INSERT IGNORE INTO app_settings (setting_name, setting_value, setting_group) VALUES (?, ?, 'license')");
        mysqli_stmt_bind_param($stmt, 'ss', $name, $value);
        mysqli_stmt_execute($stmt);
    }
}

function license_update_setting($conDB, $name, $value) {
    $stmt = mysqli_prepare($conDB, "UPDATE app_settings SET setting_value = ? WHERE setting_name = ?");
    mysqli_stmt_bind_param($stmt, 'ss', $value, $name);
    mysqli_stmt_execute($stmt);
}

/**
 * Calls the remote license API. Returns the decoded response array on a
 * successful HTTP 200 JSON reply, or null on any network/parse failure (the
 * caller falls back to the cached verdict + grace period in that case).
 */
function license_perform_remote_check($apiUrl, $serialKey, $token, $domain) {
    $ch = curl_init($apiUrl);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query(['serial_key' => $serialKey, 'token' => $token, 'domain' => $domain]),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 8,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_USERAGENT => 'AlmutlakLicenseClient/1.0',
    ]);
    $body = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error || $httpCode !== 200) {
        return null;
    }
    $data = json_decode((string) $body, true);
    if (!is_array($data) || !isset($data['valid'])) {
        return null;
    }
    return $data;
}

/**
 * Forces an immediate remote check (used right after the admin saves a new
 * key, so they get instant feedback instead of waiting for the hourly cache).
 */
function license_force_check($conDB, $apiUrl, $serialKey, $token) {
    license_ensure_settings_exist($conDB);
    license_update_setting($conDB, 'license_api_url', $apiUrl);
    $domain = $_SERVER['HTTP_HOST'] ?? '';
    $result = license_perform_remote_check($apiUrl, $serialKey, $token, $domain);
    $now = time();
    license_update_setting($conDB, 'license_last_attempt_at', date('Y-m-d H:i:s', $now));

    if ($result === null) {
        return ['valid' => false, 'status' => 'unreachable', 'message' => 'Could not reach the license server. Check the key and try again.'];
    }

    license_update_setting($conDB, 'license_cache_valid', $result['valid'] ? '1' : '0');
    license_update_setting($conDB, 'license_cache_status', (string) $result['status']);
    license_update_setting($conDB, 'license_cache_message', (string) $result['message']);
    license_update_setting($conDB, 'license_cache_expires_at', (string) ($result['expires_at'] ?? ''));
    if ($result['valid']) {
        license_update_setting($conDB, 'license_last_verified_at', date('Y-m-d H:i:s', $now));
    }

    return $result;
}

/**
 * Runs the license check (using the hourly cache when fresh) and redirects to
 * license_locked.php if invalid - unless the current page is exempt (so the
 * admin always has a way back in to fix the key).
 */
function license_enforce($conDB) {
    // Called from db.php (every request) and again from session_check.php as a
    // second layer - only actually run the check once per request.
    static $alreadyEnforced = false;
    if ($alreadyEnforced) {
        return;
    }
    $alreadyEnforced = true;

    // Never gate CLI execution (cron jobs) - there is no browser to redirect and
    // no SCRIPT_NAME/PATH_INFO in the web sense. Licensing controls who can load
    // the app over HTTP, not scheduled server-side automation.
    if (PHP_SAPI === 'cli') {
        return;
    }

    // No local-dev bypass: enforcement applies everywhere, including localhost /
    // *.local hostnames - a hosts-file alias to 127.0.0.1 is still a real deployment
    // the admin wants to control. Keep a valid key configured on any copy that
    // should always stay unlocked.
    license_ensure_settings_exist($conDB);

    // app_settings.php is intentionally NOT exempt: when the license is invalid,
    // fixing it must go through license_locked.php's own activation modal, not the
    // normal settings screen - the License tab there is only for updating an
    // already-valid key.
    // SCRIPT_NAME, not PHP_SELF: PHP_SELF includes attacker-controlled PATH_INFO
    // (e.g. requesting dashboard.php/license_locked.php executes dashboard.php but
    // basename(PHP_SELF) reads as "license_locked.php", an exempt page - bypassing
    // enforcement entirely). SCRIPT_NAME always reflects the real executing script.
    $currentPage = basename($_SERVER['SCRIPT_NAME'] ?? '');
    $exemptPages = ['license_locked.php', 'index.php', 'logout.php', 'ajaxLicenseCheck.php', 'ajaxLicenseStatus.php'];

    $serialKey = trim((string) get_setting($conDB, 'license_serial_key'));
    $token = trim((string) get_setting($conDB, 'license_token'));
    if ($serialKey === '' || $token === '') {
        if (!in_array($currentPage, $exemptPages, true)) {
            header('Location: license_locked.php');
            exit;
        }
        return;
    }

    $cachedValid = get_setting($conDB, 'license_cache_valid') === '1';
    $lastAttemptAt = (string) get_setting($conDB, 'license_last_attempt_at');
    $lastVerifiedAt = (string) get_setting($conDB, 'license_last_verified_at');

    $now = time();
    $shouldRecheck = $lastAttemptAt === '' || ($now - strtotime($lastAttemptAt)) >= LICENSE_RECHECK_INTERVAL_SECONDS;

    if ($shouldRecheck) {
        $apiUrl = trim((string) get_setting($conDB, 'license_api_url'));
        $domain = $_SERVER['HTTP_HOST'] ?? '';
        $result = $apiUrl !== '' ? license_perform_remote_check($apiUrl, $serialKey, $token, $domain) : null;
        license_update_setting($conDB, 'license_last_attempt_at', date('Y-m-d H:i:s', $now));

        if ($result !== null) {
            $cachedValid = (bool) $result['valid'];
            license_update_setting($conDB, 'license_cache_valid', $cachedValid ? '1' : '0');
            license_update_setting($conDB, 'license_cache_status', (string) $result['status']);
            license_update_setting($conDB, 'license_cache_message', (string) $result['message']);
            license_update_setting($conDB, 'license_cache_expires_at', (string) ($result['expires_at'] ?? ''));
            if ($cachedValid) {
                license_update_setting($conDB, 'license_last_verified_at', date('Y-m-d H:i:s', $now));
                $lastVerifiedAt = date('Y-m-d H:i:s', $now);
            }
        }
        // else: network failure - keep using the cached verdict, grace period below decides.
    }

    // A valid cached verdict only stays trusted for LICENSE_GRACE_DAYS without a
    // fresh successful contact. An explicitly invalid verdict (revoked/expired/
    // domain mismatch) is authoritative and blocks immediately, no grace period.
    $allowed = false;
    if ($cachedValid && $lastVerifiedAt !== '') {
        $daysSinceVerified = ($now - strtotime($lastVerifiedAt)) / 86400;
        $allowed = $daysSinceVerified <= LICENSE_GRACE_DAYS;
    }

    if (!$allowed && !in_array($currentPage, $exemptPages, true)) {
        header('Location: license_locked.php');
        exit;
    }
}
