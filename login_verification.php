<?php
/********************************************************************************
 * MODIFICATION SUMMARY
 * - Redesigned the UI to match the modern aesthetic of the new login page.
 * - Improved the layout and styling for a cleaner, more focused user experience.
 * - Enhanced the OTP input fields to be larger, more accessible, and visually
 * appealing with focus states.
 * - Styled the countdown timer and "Resend OTP" button for better clarity.
 * - Ensured the design is fully responsive.
 * - ADDED: This page is now fully translatable based on the user's preferred language.
 * - FIXED: All error messages are now passed through the translation function.
 * - ADDED: Cache-control headers to prevent stale content.
 * - FIXED: Removed init.php dependency and added custom language logic to correctly
 * display the user's preferred language during OTP verification.
 ********************************************************************************/
require_once __DIR__ . '/includes/session_bootstrap.php';
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// --- Email Masking Utility Function ---
if (!function_exists('mask_email_for_display')) {
    /**
     * Mask an email address for display (e.g. ane****@****lak.com)
     * @param string $email
     * @return string
     */
    function mask_email_for_display($email) {
        $at_pos = strpos($email, '@');
        $dot_pos = strrpos($email, '.');
        if ($at_pos !== false && $dot_pos !== false && $dot_pos > $at_pos) {
            $name_part = substr($email, 0, $at_pos);
            $domain_part = substr($email, $at_pos + 1, $dot_pos - $at_pos - 1);
            $tld_part = substr($email, $dot_pos);
            $masked_name = substr($name_part, 0, 3) . str_repeat('*', max(0, strlen($name_part) - 3));
            $masked_domain = str_repeat('*', max(0, strlen($domain_part) - 2)) . substr($domain_part, -2);
            return $masked_name . '@' . $masked_domain . $tld_part;
        }
        return '****';
    }
}

// --- START: Custom Language Logic for OTP Page ---
// We cannot use init.php here because the user is not fully logged in.
// We must determine the language based on the user ID stored in the OTP session.

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/translation_functions.php';

$current_lang = 'en'; // Default language

// The user ID is stored in a different session variable on this page.
$otp_user_id = isset($_SESSION['otp_verification']['user_id']) ? $_SESSION['otp_verification']['user_id'] : null;

if ($otp_user_id) {
    // Fetch language from DB for the user attempting to log in.
    $stmt = mysqli_prepare($conDB, "SELECT `preferred_language` FROM `admin_login` WHERE `id_iqama` = ?");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "s", $otp_user_id);
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
    // Fallback to session language if set
    $current_lang = $_SESSION['lang'];
}

$_SESSION['lang'] = $current_lang; // Sync session
$is_rtl = ($current_lang === 'ar'); // RTL flag for Arabic
load_language($current_lang); // Load the appropriate translation file

// Define variables needed by the page, previously from init.php
$site_footer = "2008 - " . date("Y") . " © SnapS Production House";
// --- END: Custom Language Logic for OTP Page ---


$error_message = null;

// Security and Cache-Control headers
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

if (isset($_SESSION['error_message'])) {
    $error_message = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}

if (!isset($_SESSION['otp_verification'])) {
    header("Location: ./index.php");
    exit();
}

if (time() > $_SESSION['otp_verification']['expires']) {
    unset($_SESSION['otp_verification']);
    $_SESSION['error_message'] = __('otp_error_expired');
    header("Location: ./index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['full_otp'])) {
    $user_id = $_SESSION['otp_verification']['user_id'];
    $submitted_otp = $_POST['full_otp'];

    if (strlen($submitted_otp) !== 6) {
        $error_message = __('otp_error_invalid_format');
    } else {
        $query = "SELECT a.*, e.name AS employee_name FROM `admin_login` a LEFT JOIN `employees` e ON a.emp_id = e.emp_id WHERE a.`id_iqama`=? LIMIT 1";
        $stmt = mysqli_prepare($conDB, $query);
        mysqli_stmt_bind_param($stmt, "s", $user_id);
        mysqli_stmt_execute($stmt);
        $user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

        if ($user && !empty($user['employee_name'])) { $user['fullname'] = $user['employee_name']; }

        if ($user && !empty($user['otp']) && password_verify($submitted_otp, $user['otp'])) {
            session_regenerate_id(true);
            $safe_fullname = htmlspecialchars($user['fullname'] ?? '', ENT_QUOTES, 'UTF-8');
            $_SESSION['auth_user'] = [
                'user_id' => $user['id_iqama'], 
                'numeric_id' => $user['id'], 
                'fullname' => $user['fullname'], 
                'email' => $user['email'], 
                'user_type' => $user['user_type'], 
                'dept' => $user['dept']
            ];
            
            // Mark the login time for grace period in session validation
            $_SESSION['session_login_time'] = time();
            
            // LOG LOGIN ACTION
            require_once __DIR__ . '/includes/init.php';
            ActivityLogger::logLogin(
                $user['id_iqama'],
                $user['fullname']
            );

            if (isset($_SESSION['otp_verification']['remember_me']) && $_SESSION['otp_verification']['remember_me'] === true) {
                $token = bin2hex(random_bytes(32));
                $hashed_token = hash('sha256', $token);
                $expiry_date = date('Y-m-d H:i:s', time() + (30 * 24 * 60 * 60)); 
                $update_sql = "UPDATE `admin_login` SET `otp`=NULL, `otp_expiration`=NULL, `last_login`=NOW(), `remember_token`=?, `remember_token_expiry`=? WHERE `id_iqama`=?";
                $update_stmt = mysqli_prepare($conDB, $update_sql);
                mysqli_stmt_bind_param($update_stmt, "sss", $hashed_token, $expiry_date, $user_id);
                mysqli_stmt_execute($update_stmt);
                $cookie_value = $user_id . ':' . $token;
                $expiry_time = time() + (30 * 24 * 60 * 60);
                setcookie('remember_me', $cookie_value, $expiry_time, '/', '', isset($_SERVER['HTTPS']), true);
            } else {
                $update_sql = "UPDATE `admin_login` SET `otp`=NULL, `otp_expiration`=NULL, `last_login`=NOW(), `remember_token`=NULL, `remember_token_expiry`=NULL WHERE `id_iqama`=?";
                $update_stmt = mysqli_prepare($conDB, $update_sql);
                mysqli_stmt_bind_param($update_stmt, "s", $user_id);
                mysqli_stmt_execute($update_stmt);
                setcookie('remember_me', '', time() - 3600, '/');
            }
            unset($_SESSION['otp_verification']);
            
            // Output redirect page that checks localStorage
            header('Content-Type: text/html; charset=utf-8');
            ?>
            <!DOCTYPE html>
            <html>
            <head>
                <title>OTP Verified - Redirecting...</title>
                <meta charset="utf-8">
                <meta name="viewport" content="width=device-width, initial-scale=1">
            </head>
            <body>
                <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
                <script src="https://cdn.tailwindcss.com"></script>
                <style>
                    body { 
                        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
                        background-color: #f3f4f6; 
                        display: flex;
                        justify-content: center;
                        align-items: center;
                        height: 100vh;
                        margin: 0;
                    }
                </style>
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        const userFullName = <?php echo json_encode($safe_fullname); ?>;
                        const totalMs = 3000;
                        const startTime = Date.now();

                        Swal.fire({
                            title: '✓ OTP Verified Successfully',
                            html: '<p>Welcome, ' + userFullName + '!</p>'
                                + '<p>You have been logged in successfully.</p>'
                                + '<p style="margin-top: 15px; font-size: 16px; color: #666;">'
                                + 'Redirecting to dashboard in <strong id="countdown-timer">3</strong> seconds...'
                                + '</p>'
                                + '<div style="margin-top: 20px; width: 100%; height: 6px; background-color: #e5e7eb; border-radius: 3px; overflow: hidden;">'
                                + '<div id="progress-bar" style="height: 100%; background: linear-gradient(90deg, #3b82f6, #0ea5e9); width: 100%; transition: width 0.05s linear;"></div>'
                                + '</div>',
                            icon: 'success',
                            allowOutsideClick: false,
                            allowEscapeKey: false,
                            showConfirmButton: false,
                            didOpen: function() {
                                var progressBar = document.getElementById('progress-bar');
                                var timerEl = document.getElementById('countdown-timer');

                                var tick = setInterval(function() {
                                    var elapsed = Date.now() - startTime;
                                    var remaining = Math.max(0, totalMs - elapsed);
                                    var seconds = Math.ceil(remaining / 1000);

                                    if (timerEl) timerEl.textContent = seconds;
                                    if (progressBar) progressBar.style.width = ((remaining / totalMs) * 100) + '%';

                                    if (elapsed >= totalMs) {
                                        clearInterval(tick);
                                        window.location.replace('./dashboard.php');
                                    }
                                }, 50);
                            }
                        });
                    });
                </script>
            </body>
            </html>
            <?php
            exit();
        } else {
            $_SESSION['otp_verification']['attempts'] = ($_SESSION['otp_verification']['attempts'] ?? 0) + 1;
            if ($_SESSION['otp_verification']['attempts'] >= 3) {
                unset($_SESSION['otp_verification']);
                $_SESSION['error_message'] = __('otp_error_too_many_attempts');
                header("Location: ./index.php");
                exit();
            }
            $error_message = __('otp_error_incorrect');
        }
    }
}
?>
<!doctype html>
<html lang="<?= $current_lang ?? 'en' ?>" <?= ($is_rtl ?? false) ? 'dir="rtl"' : '' ?>>
<head>
    <meta charset="utf-8" />
    <title><?=isset($site_title) ? $site_title : 'OTP Verification'; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta content="Anees Afzal" name="author" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <link rel="shortcut icon" href="<?=get_setting($conDB, 'favicon')?>">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">

    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            background: #050506;
        }

        .otp-card {
            background: radial-gradient(120% 140% at 50% -10%, #1a1b22 0%, #0b0b0f 55%, #08080a 100%);
            border: 1px solid rgba(255, 255, 255, 0.06);
            box-shadow: 0 25px 70px -20px rgba(0, 0, 0, 0.8), 0 0 0 1px rgba(255, 255, 255, 0.02) inset;
            animation: card-in 0.5s cubic-bezier(0.16, 1, 0.3, 1);
        }

        @keyframes card-in {
            from { opacity: 0; transform: translateY(14px) scale(0.98); }
            to   { opacity: 1; transform: translateY(0) scale(1); }
        }

        .eyebrow {
            letter-spacing: 0.2em;
        }

        .otp-row { direction: ltr !important; }

        .otp-box {
            position: relative;
            width: 3rem;
            height: 3.5rem;
            border-radius: 0.75rem;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: border-color 0.25s ease, box-shadow 0.25s ease, transform 0.15s ease, background-color 0.25s ease;
        }
        @media (min-width: 768px) { .otp-box { width: 3.5rem; height: 4rem; } }

        .otp-box.is-filled { transform: scale(1.03); }

        .otp-box:focus-within {
            border-color: #3b82f6;
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.18), 0 0 18px rgba(59, 130, 246, 0.35);
        }

        .otp-digit {
            direction: ltr !important;
            background: transparent;
            color: #f5f5f7;
            width: 100%;
            height: 100%;
            text-align: center;
            font-size: 1.5rem;
            font-weight: 700;
            border: none;
            outline: none;
            border-radius: inherit;
            transition: opacity 0.2s ease;
        }

        .otp-sep {
            color: rgba(255, 255, 255, 0.25);
            font-size: 1.25rem;
            font-weight: 600;
            align-self: center;
        }

        .otp-check {
            position: absolute;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transform: scale(0.4);
            transition: opacity 0.3s ease, transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
            pointer-events: none;
        }
        .otp-check svg { width: 1.4rem; height: 1.4rem; }

        /* Verified state */
        .otp-box.state-verified {
            border-color: rgba(34, 197, 94, 0.6);
            background: rgba(34, 197, 94, 0.08);
            box-shadow: 0 0 16px rgba(34, 197, 94, 0.25);
        }
        .otp-box.state-verified .otp-digit { opacity: 0; }
        .otp-box.state-verified .otp-check { opacity: 1; transform: scale(1); }

        /* Error state */
        .otp-box.state-error {
            border-color: rgba(239, 68, 68, 0.6);
            background: rgba(239, 68, 68, 0.08);
            box-shadow: 0 0 16px rgba(239, 68, 68, 0.25);
        }
        .otp-box.state-error .otp-digit { color: #fca5a5; }

        .otp-row.shake { animation: shake 0.45s cubic-bezier(0.36, 0.07, 0.19, 0.97); }
        @keyframes shake {
            10%, 90% { transform: translateX(-1px); }
            20%, 80% { transform: translateX(2px); }
            30%, 50%, 70% { transform: translateX(-4px); }
            40%, 60% { transform: translateX(4px); }
        }

        .status-dot {
            display: inline-block;
            width: 6px;
            height: 6px;
            border-radius: 9999px;
            background: #6b7280;
            transition: background-color 0.25s ease;
        }
        .status-text.is-verified .status-dot { background: #22c55e; }
        .status-text.is-error .status-dot { background: #ef4444; }
        .status-text.is-verified .status-label { color: #4ade80; }
        .status-text.is-error .status-label { color: #f87171; }

        @keyframes fade-in {
            from { opacity: 0; transform: translateY(4px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .status-label { display: inline-block; animation: fade-in 0.25s ease; }
    </style>
    <script> window.lang = <?= json_encode($GLOBALS['translations'] ?? []) ?>;</script>
</head>
<body>
    <!-- Background Image and Overlay -->
    <div class="fixed inset-0 z-[-1]">
        <div class="absolute inset-0 bg-cover bg-center" style="background-image: url('assets/images/login-background.webp');"></div>
        <div class="absolute inset-0 bg-black/75"></div>
    </div>

    <div class="min-h-screen flex items-center justify-center p-4">
        <div class="w-full max-w-md">
            <div class="otp-card rounded-2xl p-8 md:p-12 text-center">

                <img src="assets/images/logo_color_sm.png" class="w-16 h-16 rounded-full mx-auto mb-5 ring-2 ring-white/10 p-1" alt="Logo">

                <p class="eyebrow text-xs font-semibold text-gray-500 uppercase mb-3"><?=__('security_check', 'Security Check') ?></p>
                <h2 class="text-2xl font-bold text-white mb-2"><?=__('email_verification') ?></h2>
                <?php
                // Mask email for display using reusable function
                $masked_email = '';
                if (isset($_SESSION['otp_verification']['user_id'])) {
                    $user_id = $_SESSION['otp_verification']['user_id'];
                    $stmt = mysqli_prepare($conDB, "SELECT `email` FROM `admin_login` WHERE `id_iqama` = ?");
                    if ($stmt) {
                        mysqli_stmt_bind_param($stmt, "s", $user_id);
                        mysqli_stmt_execute($stmt);
                        $result = mysqli_stmt_get_result($stmt);
                        if ($row = mysqli_fetch_assoc($result)) {
                            $masked_email = mask_email_for_display($row['email']);
                        }
                        mysqli_stmt_close($stmt);
                    }
                }
                ?>
                <p class="text-gray-400 text-sm mb-8">
                    <?=sprintf(__('enter_the_6digit_code_sent_to_your_registered_email_address_masked'), $masked_email) ?>
                </p>

                <div id="message-container" class="hidden"></div>

                <form id="otpForm" method="post" action="login_verification.php">
                    <div id="otpRow" class="otp-row flex justify-center items-center gap-2 md:gap-3 mb-4<?= !empty($error_message) ? ' shake' : '' ?>">
                        <?php for ($i = 0; $i < 6; $i++): ?>
                            <?php if ($i === 3): ?><span class="otp-sep">-</span><?php endif; ?>
                            <div class="otp-box<?= !empty($error_message) ? ' state-error' : '' ?>" data-index="<?= $i ?>">
                                <input type="tel" pattern="\d*" maxlength="1" class="otp-digit" data-index="<?= $i ?>" <?= $i === 0 ? 'autofocus' : '' ?>>
                                <span class="otp-check">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="#4ade80" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                </span>
                            </div>
                        <?php endfor; ?>
                    </div>
                    <input type="hidden" id="fullOtp" name="full_otp">
                </form>

                <p id="statusText" class="status-text<?= !empty($error_message) ? ' is-error' : '' ?> text-sm text-gray-500 mb-6">
                    <span class="status-dot"></span>
                    <span class="status-label" id="statusLabel"><?= !empty($error_message) ? $error_message : __('otp_status_default', 'Enter the 6-digit code') ?></span>
                </p>

                <div class="text-sm text-gray-500">
                    <p id="countdown-text"><?=__('you_can_resend_otp_in')?> <span id="countdown" class="font-bold text-gray-300">120</span>s</p>
                    <button id="resend-btn" class="hidden text-blue-400 hover:text-blue-300 font-medium disabled:opacity-50 disabled:cursor-not-allowed" disabled><?=__('resend_otp') ?></button>
                </div>

                <p class="text-xs text-gray-600 mt-6"><?=__('otp_paste_tip', 'Tip: paste to fill every box at once.') ?></p>

            </div>
            <p class="text-center text-gray-400 mt-8 text-sm"><?=isset($site_footer) ? $site_footer : date('Y') . ' &copy; Al Mutlak Co.'; ?></p>
        </div>
    </div>

    <script src="assets/js/jquery.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const otpDigits = document.querySelectorAll('.otp-digit');
        const otpBoxes = document.querySelectorAll('.otp-box');
        const otpRow = document.getElementById('otpRow');
        const otpForm = document.getElementById('otpForm');
        const fullOtpInput = document.getElementById('fullOtp');
        const messageContainer = document.getElementById('message-container');
        const statusText = document.getElementById('statusText');
        const statusLabel = document.getElementById('statusLabel');

        const DEFAULT_STATUS = <?= json_encode(__('otp_status_default', 'Enter the 6-digit code')) ?>;
        const VERIFIED_STATUS = <?= json_encode(__('otp_status_verifying', 'Code verified')) ?>;

        function setStatus(text, mode) {
            statusLabel.textContent = text;
            statusText.classList.remove('is-error', 'is-verified');
            if (mode) statusText.classList.add(mode);
            // restart the fade-in animation
            statusLabel.style.animation = 'none';
            void statusLabel.offsetWidth;
            statusLabel.style.animation = '';
        }

        function clearErrorState() {
            otpRow.classList.remove('shake');
            otpBoxes.forEach(box => box.classList.remove('state-error'));
            if (statusText.classList.contains('is-error')) {
                setStatus(DEFAULT_STATUS, null);
            }
        }

        // If there's an error message on load, keep the fields empty and focused
        if (statusText.classList.contains('is-error')) {
            otpDigits.forEach(digit => digit.value = '');
            otpDigits[0].focus();
        }

        function playVerifiedAnimation() {
            setStatus(VERIFIED_STATUS, 'is-verified');
            otpBoxes.forEach((box, i) => {
                setTimeout(() => box.classList.add('state-verified'), i * 90);
            });
            // Let the checkmarks finish staggering in before the form actually submits
            setTimeout(() => otpForm.submit(), otpBoxes.length * 90 + 350);
        }

        otpDigits.forEach((digit, index) => {
            digit.addEventListener('input', (e) => {
                clearErrorState();
                const box = otpBoxes[index];
                box.classList.toggle('is-filled', digit.value.length === 1);

                if (digit.value.length === 1 && index < otpDigits.length - 1) {
                    otpDigits[index + 1].focus();
                }
                let otpCode = Array.from(otpDigits).map(d => d.value).join('');
                fullOtpInput.value = otpCode;
                if (otpCode.length === 6) {
                    playVerifiedAnimation();
                }
            });

            digit.addEventListener('keydown', (e) => {
                if (e.key === 'Backspace' && digit.value.length === 0 && index > 0) {
                    otpDigits[index - 1].focus();
                }
            });

            // --- PASTE LOGIC ---
            digit.addEventListener('paste', (e) => {
                e.preventDefault();
                clearErrorState();
                const pastedData = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '');
                if (!pastedData) return;

                const charsToPaste = pastedData.substring(0, otpDigits.length - index);

                charsToPaste.split('').forEach((char, i) => {
                    otpDigits[index + i].value = char;
                    otpBoxes[index + i].classList.add('is-filled');
                });

                let otpCode = Array.from(otpDigits).map(d => d.value).join('');
                fullOtpInput.value = otpCode;

                if (otpCode.length === 6) {
                    otpDigits[5].focus();
                    playVerifiedAnimation();
                } else {
                    otpDigits[otpCode.length].focus();
                }
            });
        });

        let timeLeft = 120;
        const countdownElement = document.getElementById('countdown');
        const countdownText = document.getElementById('countdown-text');
        const resendBtn = document.getElementById('resend-btn');
        let countdownInterval;

        function startCountdown() {
            timeLeft = 120; // Reset timer
            countdownText.style.display = 'block';
            resendBtn.style.display = 'none';
            resendBtn.disabled = true;

            clearInterval(countdownInterval); // Clear any existing interval

            countdownInterval = setInterval(() => {
                timeLeft--;
                countdownElement.textContent = timeLeft;
                if (timeLeft <= 0) {
                    clearInterval(countdownInterval);
                    countdownText.style.display = 'none';
                    resendBtn.style.display = 'block';
                    resendBtn.disabled = false;
                }
            }, 1000);
        }

        // Initial countdown start
        startCountdown();

        // --- START: Resend OTP Logic ---
        function showToast(message, ok) {
            messageContainer.classList.remove('hidden');
            const palette = ok
                ? 'bg-green-500/10 border-green-500/30 text-green-400'
                : 'bg-red-500/10 border-red-500/30 text-red-400';
            messageContainer.innerHTML = `<div class="${palette} border px-3 py-2 rounded-lg text-xs mb-4" role="alert">${message}</div>`;
        }

        resendBtn.addEventListener('click', function() {
            resendBtn.disabled = true;
            resendBtn.textContent = 'Sending...';
            messageContainer.classList.add('hidden');
            messageContainer.innerHTML = '';

            $.ajax({
                url: './resend_otp.php',
                type: 'POST',
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        showToast(response.message, true);
                        clearErrorState();
                        otpDigits.forEach((d, i) => { d.value = ''; otpBoxes[i].classList.remove('is-filled', 'state-verified'); });
                        otpDigits[0].focus();
                        startCountdown(); // Restart the countdown
                    } else {
                        showToast(response.message, false);
                        resendBtn.disabled = false; // Re-enable button on failure
                    }
                },
                error: function() {
                    showToast('An error occurred. Please try again.', false);
                    resendBtn.disabled = false; // Re-enable button on error
                },
                complete: function() {
                     resendBtn.textContent = 'Resend OTP';
                }
            });
        });
        // --- END: Resend OTP Logic ---
    });
    </script>
</body>
</html>

