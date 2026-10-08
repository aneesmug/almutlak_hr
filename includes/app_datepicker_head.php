<?php
// AppDate date picker (flatpickr + assets/js/app_datepicker.js) on every logged-in page.
// Required from session_check.php. Same output-buffer trick as theme_dark.php: the
// stylesheets + scripts are slipped in right before </head> of any HTML response, so no
// page has to link them itself. Both scripts are jQuery-free, so loading them in <head>
// (before the page's own jQuery/scripts) is safe and AppDate exists by document.ready.
// JSON/file/Excel responses and pages that already link flatpickr are left alone.

if (!function_exists('app_datepicker_head_tags')) {
    function app_datepicker_head_tags() {
        $base = __DIR__ . '/..';
        // Pages in sub-folders need "../" back up to the system root.
        $root = realpath($base);
        $scriptDir = realpath(dirname($_SERVER['SCRIPT_FILENAME'] ?? ''));
        $prefix = '';
        if ($root && $scriptDir && stripos($scriptDir, $root) === 0) {
            $rel = trim(substr($scriptDir, strlen($root)), '\\/');
            $prefix = $rel === '' ? '' : str_repeat('../', count(preg_split('~[\\\\/]+~', $rel)));
        }
        $v = function ($path) use ($base) {
            return (int) @filemtime($base . '/' . $path);
        };
        return '<link rel="stylesheet" href="' . $prefix . 'plugins/flatpickr/flatpickr.min.css">'
            . '<link rel="stylesheet" href="' . $prefix . 'plugins/flatpickr/plugins/monthSelect/style.css">'
            . '<link rel="stylesheet" href="' . $prefix . 'assets/css/app_datepicker.css?v=' . $v('assets/css/app_datepicker.css') . '">'
            . '<script src="' . $prefix . 'plugins/flatpickr/flatpickr.min.js"></script>'
            . '<script src="' . $prefix . 'plugins/flatpickr/plugins/monthSelect/index.js"></script>'
            . '<script src="' . $prefix . 'assets/js/app_datepicker.js?v=' . $v('assets/js/app_datepicker.js') . '"></script>';
    }
}

if (!function_exists('app_sr_table_scroll_tag')) {
    function app_sr_table_scroll_tag() {
        $base = __DIR__ . '/..';
        $root = realpath($base);
        $scriptDir = realpath(dirname($_SERVER['SCRIPT_FILENAME'] ?? ''));
        $prefix = '';
        if ($root && $scriptDir && stripos($scriptDir, $root) === 0) {
            $rel = trim(substr($scriptDir, strlen($root)), '\\/');
            $prefix = $rel === '' ? '' : str_repeat('../', count(preg_split('~[\\\\/]+~', $rel)));
        }
        return '<script src="' . $prefix . 'assets/js/sr_table_scroll.js?v=' . (int) @filemtime($base . '/assets/js/sr_table_scroll.js') . '"></script>';
    }
}

if (PHP_SAPI !== 'cli' && !defined('APP_DATEPICKER_BUFFERED')) {
    define('APP_DATEPICKER_BUFFERED', true);
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
        // sr-* table scroll helper (assets/js/sr_table_scroll.js) goes on every page; the
        // date picker only where the page does not already link flatpickr itself.
        $tags = app_sr_table_scroll_tag();
        if (stripos($buffer, 'flatpickr.min.js') === false) {
            $tags = app_datepicker_head_tags() . $tags;
        }
        return preg_replace('~</head>~i', $tags . '</head>', $buffer, 1);
    });
}
