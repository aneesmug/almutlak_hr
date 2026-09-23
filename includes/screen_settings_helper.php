<?php

if (defined('SCREEN_SETTINGS_HELPER_INCLUDED')) {
    return;
}
define('SCREEN_SETTINGS_HELPER_INCLUDED', true);

/**
 * Per-user "Screen Settings" (Scale % + reference Display Resolution + auto
 * Fullscreen), stored the same way as special_access_by_user: one JSON map
 * (emp_id => settings) in a single app_settings row named
 * 'screen_settings_by_user'. Resolution is stored for reference/display only
 * and does not itself change rendering - only 'scale' (applied as CSS zoom
 * on <html>) and 'fullscreen' (auto-request Fullscreen API on load) do.
 * 'theme' is the user's own color theme: 'light' / 'dark' override the global
 * App Settings > Theme Config "App theme" for this user only, 'default' follows it
 * (applied by includes/theme_dark.php).
 */

if (!function_exists('default_screen_settings')) {
    function default_screen_settings() {
        return [
            'scale' => 100,
            'width' => 1920,
            'height' => 1080,
            'fullscreen' => 0,
            'theme' => 'default',
        ];
    }
}

if (!function_exists('normalize_screen_setting')) {
    function normalize_screen_setting($entry) {
        $defaults = default_screen_settings();
        if (!is_array($entry)) {
            $entry = [];
        }

        $scale = isset($entry['scale']) ? (int) $entry['scale'] : $defaults['scale'];
        $scale = max(25, min(300, $scale ?: $defaults['scale']));

        $width = isset($entry['width']) ? (int) $entry['width'] : $defaults['width'];
        $width = max(800, min(7680, $width ?: $defaults['width']));

        $height = isset($entry['height']) ? (int) $entry['height'] : $defaults['height'];
        $height = max(600, min(4320, $height ?: $defaults['height']));

        $fullscreen = !empty($entry['fullscreen']) && $entry['fullscreen'] !== '0' ? 1 : 0;

        $theme = strtolower(trim((string) ($entry['theme'] ?? $defaults['theme'])));
        if (!in_array($theme, ['default', 'light', 'dark'], true)) {
            $theme = $defaults['theme'];
        }

        return [
            'scale' => $scale,
            'width' => $width,
            'height' => $height,
            'fullscreen' => $fullscreen,
            'theme' => $theme,
        ];
    }
}

if (!function_exists('decode_screen_settings_map')) {
    function decode_screen_settings_map($rawValue) {
        if (!is_string($rawValue) || trim($rawValue) === '') {
            return [];
        }

        $decoded = json_decode($rawValue, true);
        if (!is_array($decoded)) {
            return [];
        }

        $normalized = [];
        foreach ($decoded as $empId => $entry) {
            $key = trim((string) $empId);
            // Skip anything that isn't a real settings entry (e.g. a null hole from a
            // client sending a JSON array instead of an object) instead of expanding it
            // into a full default record - that expansion is what previously let one bad
            // client-side payload balloon into thousands of rows and blow past the
            // setting_value column's TEXT limit, truncating and corrupting the whole map.
            if ($key === '' || !is_array($entry)) {
                continue;
            }
            $normalized[$key] = normalize_screen_setting($entry);
        }

        return $normalized;
    }
}

if (!function_exists('get_screen_settings_map')) {
    function get_screen_settings_map($conDB) {
        $raw = '';
        if (function_exists('get_setting')) {
            $raw = (string) get_setting($conDB, 'screen_settings_by_user', '{}');
        }
        return decode_screen_settings_map($raw);
    }
}

if (!function_exists('get_user_screen_settings')) {
    function get_user_screen_settings($conDB, $empId) {
        $empKey = trim((string) $empId);
        if ($empKey === '') {
            return default_screen_settings();
        }

        $map = get_screen_settings_map($conDB);
        return $map[$empKey] ?? default_screen_settings();
    }
}
