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
if (session_status() == PHP_SESSION_NONE) {
    session_start();
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
        $query = "SELECT * FROM `admin_login` WHERE `id_iqama`=? LIMIT 1";
        $stmt = mysqli_prepare($conDB, $query);
        mysqli_stmt_bind_param($stmt, "s", $user_id);
        mysqli_stmt_execute($stmt);
        $user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

        if ($user && !empty($user['otp']) && password_verify($submitted_otp, $user['otp'])) {
            session_regenerate_id(true);
            $_SESSION['auth_user'] = [
                'user_id' => $user['id_iqama'], 'fullname' => $user['fullname'], 'email' => $user['email'], 'user_type' => $user['user_type'], 'dept' => $user['dept']
            ];

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
            header("Location: ./dashboard.php");
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
    <title><?php echo isset($site_title) ? $site_title : 'OTP Verification'; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta content="Anees Afzal" name="author" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <link rel="shortcut icon" href="assets/images/favicon.ico">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">

    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { 
            font-family: 'Roboto', sans-serif; 
        }
        .otp-digit:focus { 
            box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.5); 
        }
        .otp-digit {
            direction: ltr !important;
        }
    </style>
    <script> window.lang = <?= json_encode($GLOBALS['translations'] ?? []) ?>;</script>
</head>
<body>
    <!-- Background Image and Overlay -->
    <div class="fixed inset-0 z-[-1]">
        <div class="absolute inset-0 bg-cover bg-center" style="background-image: url('assets/images/login-background.webp');"></div>
        <div class="absolute inset-0 bg-black/60"></div>
    </div>

    <div class="min-h-screen flex items-center justify-center p-4">
        <div class="w-full max-w-md">
            <div class="bg-white rounded-2xl shadow-2xl p-8 md:p-12 text-center">
                
                <img src="assets/images/logo_color_sm.png" class="w-24 h-24 rounded-full mx-auto mb-6 ring-4 ring-gray-200 p-1" alt="Logo">
                
                <h2 class="text-2xl font-bold text-gray-800 mb-2"><?=__('email_verification') ?></h2>
                <p class="text-gray-500 mb-6"><?=__('enter_the_6digit_code_sent_to_your_registered_email_address') ?></p>
                
                <div id="message-container" class="mb-4">
                    <?php if(!empty($error_message)): ?>
                        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg relative" role="alert">
                            <?php echo $error_message; ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <form id="otpForm" method="post" action="login_verification.php">
                    <div class="flex justify-center gap-2 md:gap-4 mb-6" dir="ltr">
                        <input type="tel" pattern="\d*" maxlength="1" class="otp-digit w-12 h-14 md:w-14 md:h-16 text-center text-2xl font-bold border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 transition duration-150" data-index="0" autofocus>
                        <input type="tel" pattern="\d*" maxlength="1" class="otp-digit w-12 h-14 md:w-14 md:h-16 text-center text-2xl font-bold border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 transition duration-150" data-index="1">
                        <input type="tel" pattern="\d*" maxlength="1" class="otp-digit w-12 h-14 md:w-14 md:h-16 text-center text-2xl font-bold border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 transition duration-150" data-index="2">
                        <input type="tel" pattern="\d*" maxlength="1" class="otp-digit w-12 h-14 md:w-14 md:h-16 text-center text-2xl font-bold border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 transition duration-150" data-index="3">
                        <input type="tel" pattern="\d*" maxlength="1" class="otp-digit w-12 h-14 md:w-14 md:h-16 text-center text-2xl font-bold border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 transition duration-150" data-index="4">
                        <input type="tel" pattern="\d*" maxlength="1" class="otp-digit w-12 h-14 md:w-14 md:h-16 text-center text-2xl font-bold border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 transition duration-150" data-index="5">
                    </div>
                    <input type="hidden" id="fullOtp" name="full_otp">
                </form>

                <div class="text-sm text-gray-500">
                    <p id="countdown-text"><?=__('you_can_resend_otp_in')?> <span id="countdown" class="font-bold text-gray-700">120</span>s</p>
                    <button id="resend-btn" class="hidden text-blue-600 hover:text-blue-800 font-medium disabled:opacity-50 disabled:cursor-not-allowed" disabled><?=__('resend_otp') ?></button>
                </div>

            </div>
            <p class="text-center text-gray-200 mt-8 text-sm"><?php echo isset($site_footer) ? $site_footer : date('Y') . ' &copy; Al Mutlak Co.'; ?></p>
        </div>
    </div>

    <script src="assets/js/jquery.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const otpDigits = document.querySelectorAll('.otp-digit');
        const otpForm = document.getElementById('otpForm');
        const fullOtpInput = document.getElementById('fullOtp');
        const messageContainer = document.getElementById('message-container');
        
        // If there's an error message, clear the OTP fields
        if (messageContainer.querySelector('.bg-red-100')) {
            otpDigits.forEach(digit => digit.value = '');
            otpDigits[0].focus();
        }

        otpDigits.forEach((digit, index) => {
            digit.addEventListener('input', (e) => {
                if (digit.value.length === 1 && index < otpDigits.length - 1) {
                    otpDigits[index + 1].focus();
                }
                let otpCode = Array.from(otpDigits).map(d => d.value).join('');
                fullOtpInput.value = otpCode;
                if (otpCode.length === 6) {
                    otpForm.submit();
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
                const pastedData = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '');
                if (!pastedData) return;

                const charsToPaste = pastedData.substring(0, otpDigits.length - index);
                
                charsToPaste.split('').forEach((char, i) => {
                    otpDigits[index + i].value = char;
                });

                let otpCode = Array.from(otpDigits).map(d => d.value).join('');
                fullOtpInput.value = otpCode;

                if (otpCode.length === 6) {
                    otpDigits[5].focus();
                    otpForm.submit();
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
        resendBtn.addEventListener('click', function() {
            resendBtn.disabled = true;
            resendBtn.textContent = 'Sending...';
            // We only need to clear our own message container, not the PHP-rendered one.
            const existingAlert = messageContainer.querySelector('.alert-js');
            if (existingAlert) {
                existingAlert.remove();
            }

            $.ajax({
                url: './resend_otp.php',
                type: 'POST',
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        messageContainer.innerHTML = `<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg relative alert-js" role="alert">${response.message}</div>`;
                        startCountdown(); // Restart the countdown
                    } else {
                        messageContainer.innerHTML = `<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg relative alert-js" role="alert">${response.message}</div>`;
                        resendBtn.disabled = false; // Re-enable button on failure
                    }
                },
                error: function() {
                    messageContainer.innerHTML = `<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg relative alert-js" role="alert">An error occurred. Please try again.</div>`;
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

