<?php
/**
 * Session GC safety ceiling.
 *
 * Real session expiry is enforced by the app's own configurable
 * "Set Session Timeout" value (app_settings.session_timeout), checked
 * against $_SESSION['last_activity'] in session_check.php on every request.
 *
 * That check only runs if the session file still exists. Many hosts default
 * session.gc_maxlifetime far below any realistic app timeout (often 1440s /
 * 24 min), so PHP's own garbage collector can delete the session file first,
 * silently logging the user out long before the configured value is reached.
 * This never shows up locally (low traffic, GC rarely fires) but shows up
 * constantly in production. Setting a large ceiling here does not change
 * when sessions actually expire - it only stops PHP from expiring them
 * before the app's own check gets a chance to.
 *
 * Must run before session_start().
 */
if (!function_exists('app_bootstrap_session_gc')) {
    function app_bootstrap_session_gc() {
        static $applied = false;
        if ($applied) {
            return;
        }
        $applied = true;

        @ini_set('session.gc_maxlifetime', 86400); // 24h ceiling
    }
}

app_bootstrap_session_gc();
