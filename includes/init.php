<?php
// File: includes/init.php (Final Updated)
// This file should be included at the very top of all your user-facing PHP pages.

/**************************************************************************************************
 * MODIFICATION SUMMARY
 *
 * 1.  Removed dependency on ?lang=xx in URL. Now language comes directly from user profile (DB).
 * 2.  Introduced ?change_lang=xx only for switching, then auto-redirects back with same query string
 *     (without leaving change_lang in the URL).
 * 3.  Language preference is saved in DB (for logged-in users) and synced in session.
 * 4.  All original layout/logic preserved.
 **************************************************************************************************/

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include core files
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/translation_functions.php';

// --- User and Language Setup ---

$is_user_logged_in = isset($_SESSION['auth_user']['user_id']);
$username = $is_user_logged_in ? $_SESSION['auth_user']['user_id'] : null;

// ACTION: Handle explicit language change via ?change_lang=xx
if (isset($_GET['change_lang']) && in_array($_GET['change_lang'], ['en', 'ar'])) {
    $selected_lang = $_GET['change_lang'];
    $_SESSION['lang'] = $selected_lang;

    // If the user is logged in, update permanent preference in DB
    if ($is_user_logged_in && $username) {
        $stmt = mysqli_prepare($conDB, "UPDATE `admin_login` SET `preferred_language` = ? WHERE `id_iqama` = ?");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "ss", $selected_lang, $username);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
    }

    // Redirect back to same page without change_lang param, keeping other query params
    $parsedUrl = parse_url($_SERVER['REQUEST_URI']);
    $query = [];
    if (!empty($parsedUrl['query'])) {
        parse_str($parsedUrl['query'], $query);
        unset($query['change_lang']);
    }
    $redirectUrl = $parsedUrl['path'];
    if (!empty($query)) {
        $redirectUrl .= '?' . http_build_query($query);
    }
    header("Location: $redirectUrl");
    exit();
}

// DETERMINATION: Figure out which language to display
$current_lang = 'en'; // Default

if ($is_user_logged_in && $username) {
    // Fetch language from DB
    $stmt = mysqli_prepare($conDB, "SELECT `preferred_language` FROM `admin_login` WHERE `id_iqama` = ?");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "s", $username);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        if ($lang_result = mysqli_fetch_assoc($result)) {
            if (!empty($lang_result['preferred_language']) && in_array($lang_result['preferred_language'], ['en', 'ar'])) {
                $current_lang = $lang_result['preferred_language'];
            }
        }
        mysqli_stmt_close($stmt);
    }
} elseif (isset($_SESSION['lang']) && in_array($_SESSION['lang'], ['en', 'ar'])) {
    $current_lang = $_SESSION['lang'];
}

// Sync session
$_SESSION['lang'] = $current_lang;

// RTL flag
$is_rtl = ($current_lang === 'ar');

// Load translations
load_language($current_lang);

// Global settings
$site_name   = "Al-Mutlak Co.";
$site_title  = "Al-Mutlak Co. | cPanel";
$site_footer = "2008 - " . date("Y") . " © SnapS Production House";
?>
