<?php
// Dark theme. Per user: App Settings > Theme Config > Screen Settings > "Theme"
// (Light / Dark) wins; "Default" falls back to the global App Settings > Theme
// Config > "App theme".
// Required from session_check.php, so it reaches every logged-in page - including
// the ones that never include main_menu.php (profile.php, *_history.php, reports,
// print pages...). Instead of editing each page's <head>, an output buffer slips
// the stylesheet + script in right before </head> of any HTML response. Loading in
// <head> (not mid-body) also means the page never flashes white first.
// JSON/file/Excel responses are left alone: only text/html output that actually
// has a </head> is touched.

if (!function_exists('app_dark_theme_enabled')) {
    function app_dark_theme_enabled($conDB, $empId = null) {
        static $enabled = null;
        if ($enabled === null) {
            $enabled = false;
            if ($conDB) {
                if ($empId === null) {
                    $empId = $GLOBALS['empid'] ?? '';
                }
                require_once __DIR__ . '/screen_settings_helper.php';
                $userTheme = get_user_screen_settings($conDB, $empId)['theme'] ?? 'default';
                $enabled = $userTheme === 'default'
                    ? get_setting($conDB, 'app_theme') === 'dark'
                    : $userTheme === 'dark';
            }
        }
        return $enabled;
    }
}

if (!function_exists('app_dark_theme_head_tags')) {
    function app_dark_theme_head_tags() {
        $base = __DIR__ . '/../assets';
        // Pages in sub-folders need "../" back up to the system root.
        $root = realpath(__DIR__ . '/..');
        $scriptDir = realpath(dirname($_SERVER['SCRIPT_FILENAME'] ?? ''));
        $prefix = '';
        if ($root && $scriptDir && stripos($scriptDir, $root) === 0) {
            $rel = trim(substr($scriptDir, strlen($root)), '\\/');
            $prefix = $rel === '' ? '' : str_repeat('../', count(preg_split('~[\\\\/]+~', $rel)));
        }
        return '<link rel="stylesheet" media="screen" href="' . $prefix . 'assets/css/app_dark.css?v=' . (int) @filemtime($base . '/css/app_dark.css') . '">'
            . '<script>document.documentElement.classList.add("app-dark");</script>'
            . '<script src="' . $prefix . 'assets/js/app_dark.js?v=' . (int) @filemtime($base . '/js/app_dark.js') . '" defer></script>';
    }
}

if (PHP_SAPI !== 'cli' && isset($conDB) && app_dark_theme_enabled($conDB) && !defined('APP_DARK_THEME_BUFFERED')) {
    define('APP_DARK_THEME_BUFFERED', true); // main_menu.php skips its own copy of the tags
    ob_start(function ($buffer) {
        static $done = false;
        if ($done || stripos($buffer, '</head>') === false) {
            return $buffer;
        }
        foreach (headers_list() as $header) {
            if (stripos($header, 'Content-Type:') === 0 && stripos($header, 'text/html') === false) {
                return $buffer;
            }
        }
        $done = true;
        return preg_replace('~</head>~i', app_dark_theme_head_tags() . '</head>', $buffer, 1);
    });
}
