<?php
/**
 * TEMPORARY DEBUG SCRIPT - DELETE FROM THE SERVER AFTER USE.
 *
 * Usage:
 *   debug_business_trip.php?key=bt-debug-7319            -> step 1 + step 2 (parse check, then run the page)
 *   debug_business_trip.php?key=bt-debug-7319&run=0      -> step 1 only (parse check, no page run)
 *
 * Step 1: parse-checks every PHP file the page depends on (no code executed) and prints
 *         size / line count, so a truncated or corrupt upload shows up immediately.
 * Step 2: runs all_applied_business_trip.php with detailed errors forced on, so the
 *         error card shows the real message, file and line instead of only a reference.
 */

if (($_GET['key'] ?? '') !== 'bt-debug-7319') {
    http_response_code(403);
    exit('debug_business_trip.php reached. Add ?key=bt-debug-7319 to the URL.');
}

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

// db.php only defines this when it does not exist yet - defining it first forces the
// error card to print the technical details (message + file:line) on the live server.
function app_should_show_detailed_errors() {
    return true;
}

$root = __DIR__;
$files = array_merge(
    [$root . '/all_applied_business_trip.php'],
    glob($root . '/includes/*.php') ?: []
);

echo '<div style="font:13px/1.5 monospace;padding:16px;background:#fff;color:#000">';
echo '<h3>PHP ' . PHP_VERSION . ' | ' . php_sapi_name() . ' | memory_limit=' . ini_get('memory_limit') . '</h3>';
echo '<h3>Step 1: parse check</h3><table border="1" cellpadding="4" style="border-collapse:collapse">';
echo '<tr><th>File</th><th>Bytes</th><th>Lines</th><th>Result</th></tr>';

$parseFailed = false;
foreach ($files as $file) {
    $code = @file_get_contents($file);
    $name = substr($file, strlen($root) + 1);
    if ($code === false) {
        echo '<tr style="background:#fdd"><td>' . htmlspecialchars($name) . '</td><td colspan="3">UNREADABLE</td></tr>';
        $parseFailed = true;
        continue;
    }
    $result = 'OK';
    $bad = false;
    try {
        token_get_all($code, TOKEN_PARSE);
    } catch (ParseError $e) {
        $result = 'PARSE ERROR: ' . $e->getMessage() . ' (line ' . $e->getLine() . ')';
        $bad = true;
    }
    if (!$bad && preg_match('/^\s*<\?php/', $code) && !preg_match('/(\}|;|\?>)\s*$/', $code)) {
        $result = 'WARNING: file does not end with } ; or ?> - possibly truncated';
        $bad = true;
    }
    if ($bad) {
        $parseFailed = true;
    }
    echo '<tr' . ($bad ? ' style="background:#fdd"' : '') . '><td>' . htmlspecialchars($name) . '</td><td>'
        . strlen($code) . '</td><td>' . (substr_count($code, "\n") + 1) . '</td><td>' . htmlspecialchars($result) . '</td></tr>';
}
echo '</table>';

foreach (['includes/sr_list_helpers.php' => ['sr_scope_vars', 'sr_status_counts', 'sr_status_tabs'],
          'includes/business_trip_settlement_helper.php' => ['cleanBusinessTripText'],
          'includes/helper_functions.php' => ['getDelegatedFromEmpId', 'format_safe_date', 'getDisplayName']] as $f => $fns) {
    $code = (string)@file_get_contents($root . '/' . $f);
    foreach ($fns as $fn) {
        $found = preg_match('/function\s+' . $fn . '\s*\(/', $code);
        echo ($found ? 'OK ' : '<b style="color:red">MISSING</b> ') . htmlspecialchars("$fn() in $f") . '<br>';
    }
}

if (($_GET['run'] ?? '1') === '0') {
    echo '<p><b>Stopped before running the page (run=0).</b></p></div>';
    exit;
}
if ($parseFailed) {
    echo '<p style="color:red"><b>Fix the red rows above first (re-upload those files). Page run skipped.</b></p></div>';
    exit;
}

echo '<h3>Step 2: running all_applied_business_trip.php (error details will show below if it fails)</h3></div>';

// Last-resort catch, in case the failure happens before db.php installs its own handlers.
register_shutdown_function(function () {
    $e = error_get_last();
    if ($e && in_array($e['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR], true)) {
        echo '<pre style="background:#fdd;padding:12px">FATAL: ' . htmlspecialchars($e['message'] . ' in ' . $e['file'] . ':' . $e['line']) . '</pre>';
    }
});

try {
    require $root . '/all_applied_business_trip.php';
} catch (Throwable $e) {
    echo '<pre style="background:#fdd;padding:12px">' . htmlspecialchars(get_class($e) . ': ' . $e->getMessage()
        . "\n" . $e->getFile() . ':' . $e->getLine() . "\n\n" . $e->getTraceAsString()) . '</pre>';
}
