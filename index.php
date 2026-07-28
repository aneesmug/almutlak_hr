<?php
/********************************************************************************
 * MODIFICATION SUMMARY
 * - REMOVED: The entire Bootstrap modal HTML structure for password reset.
 * - ADDED: The SweetAlert2 library via CDN in the <head> section.
 * - REPLACED: The password reset logic in the JavaScript section to use
 * SweetAlert2 for a multi-step, interactive alert experience.
 * - The "Forgot Password?" link now triggers a SweetAlert2 prompt.
 * - The verification and password update steps are now handled within a
 * chained sequence of SweetAlert2 alerts.
 * - AJAX calls to `reset_password.php` remain the same, but the UI for
 * displaying success and error messages is now handled by SweetAlert2.
 * - ADDED: The flatpickr library for a user-friendly date picker in the
 * SweetAlert2 verification step.
 ********************************************************************************/
// Drop malformed/spoofed session cookies before starting: an illegal session ID
// makes PHP fail to read the session file and log a warning every time it's sent.
$sessionCookieName = session_name();
if (isset($_COOKIE[$sessionCookieName]) && !preg_match('/^[A-Za-z0-9,\-]{1,128}$/', $_COOKIE[$sessionCookieName])) {
    unset($_COOKIE[$sessionCookieName]);
}
session_start();
// Security headers
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");
header("Referrer-Policy: strict-origin-when-cross-origin");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

require_once __DIR__ . '/includes/db.php';

// --- START: Remember Me Auto-Login Logic ---
if (!isset($_SESSION['auth_user']) && isset($_COOKIE['remember_me'])) {
    list($id_iqama, $token) = explode(':', $_COOKIE['remember_me'], 2);

    if (!empty($id_iqama) && !empty($token)) {
        $id_iqama = mysqli_real_escape_string($conDB, $id_iqama);
        $hashed_token = hash('sha256', $token);

        $query = "SELECT * FROM `admin_login` WHERE `id_iqama` = ? AND `remember_token` IS NOT NULL AND `remember_token_expiry` > NOW() LIMIT 1";
        $stmt = mysqli_prepare($conDB, $query);
        mysqli_stmt_bind_param($stmt, "s", $id_iqama);
        mysqli_stmt_execute($stmt);
        $user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

        if ($user && hash_equals($user['remember_token'], $hashed_token)) {
            session_regenerate_id(true);
            $_SESSION['auth_user'] = [ 'user_id' => $user['id_iqama'], 'fullname' => $user['fullname'], 'email' => $user['email'], 'user_type' => $user['user_type'], 'dept' => $user['dept'] ];
            $cookie_value = $id_iqama . ':' . $token;
            $expiry = time() + (30 * 24 * 60 * 60);
            setcookie('remember_me', $cookie_value, $expiry, '/', '', isset($_SERVER['HTTPS']), true);
            header("Location: ./dashboard.php");
            exit();
        } else {
            setcookie('remember_me', '', time() - 3600, '/');
        }
    }
}
// --- END: Remember Me Auto-Login Logic ---

if (isset($_SESSION['auth_user']) && is_array($_SESSION['auth_user'])) {
    header("Location: ./dashboard.php");
    exit();
}
?>
<!doctype html>
<html lang="en">
    <head>
        <meta charset="utf-8" />
        <title><?=isset($site_title) ? $site_title : 'Login'; ?> - Al Mutlak Access</title>
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
        <meta content="A fully featured system which can be used to build CRM, CMS, etc." name="description" />
        <meta content="Anees Afzal" name="author" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />

        <!-- PWA: Add to Home Screen support (iOS Safari + Android Chrome) -->
        <link rel="manifest" href="/manifest.json">
        <meta name="theme-color" content="#1a3c6e">
        <!-- iOS specific PWA tags -->
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
        <meta name="apple-mobile-web-app-title" content="Al-Mutlak WMS">
        <link rel="apple-touch-icon" sizes="192x192" href="/pwa-icon-192.png">
        <!-- Register service worker for PWA installability -->
        <script>
          if ('serviceWorker' in navigator) {
            window.addEventListener('load', function() {
              navigator.serviceWorker.register('/sw.js');
            });
          }
        </script>
        <link rel="shortcut icon" href="<?=get_setting($conDB, 'favicon')?>">
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
        <script src="https://cdn.tailwindcss.com"></script>
        <!-- SweetAlert2 -->
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <!-- flatpickr Date Picker -->
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
        <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
        <style>
            body { font-family: 'Roboto', sans-serif; }
            #password-field-container, #new-password-container, #remember-me-container, #user-feedback-container, #forgot-password-link { display: none; }

            .store-buttons-grid {
                display: grid;
                grid-template-columns: repeat(1, minmax(0, 1fr));
                gap: 0.75rem;
                width: 100%;
            }

            @media (min-width: 640px) {
                .store-buttons-grid:has(> a:nth-child(2)) {
                    grid-template-columns: repeat(2, minmax(0, 1fr));
                }
            }
            #page-loader {
                position: fixed;
                inset: 0;
                z-index: 9999;
                background: #1a3c6e;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                gap: 1.5rem;
                transition: opacity 0.4s ease;
            }
            #page-loader.hidden {
                opacity: 0;
                pointer-events: none;
            }
            .loader-spinner {
                width: 52px;
                height: 52px;
                border: 5px solid rgba(255,255,255,0.2);
                border-top-color: #ffffff;
                border-radius: 50%;
                animation: spin 0.8s linear infinite;
            }
            @keyframes spin {
                to { transform: rotate(360deg); }
            }
        </style>
    </head>
    <body>
        <!-- Page Loader -->
        <div id="page-loader">
            <img src="assets/images/logo_color_sm.png" alt="Al-Mutlak" style="width:120px;opacity:0.9;">
            <div class="loader-spinner"></div>
        </div>
        <div class="fixed inset-0 z-[-1]">
            <div class="absolute inset-0 bg-cover bg-center" style="background-image: url('assets/images/login-background.webp');"></div>
            <div class="absolute inset-0 bg-black/60"></div>
        </div>
        <div class="min-h-screen flex items-center justify-center p-4">
            <div class="grid md:grid-cols-2 gap-0 w-full max-w-5xl rounded-2xl shadow-2xl overflow-hidden">
                <div class="hidden md:flex flex-col justify-center items-center p-12 bg-gradient-to-br from-blue-600 to-indigo-700 text-white">
                    <img src="assets/images/logo-w.png" alt="Company Logo" class="w-60 mb-6">
                    <h1 class="text-4xl font-bold mb-3 text-center">Welcome Back</h1>
                    <p class="text-indigo-200 text-center">Your central hub for all company resources and information. Please log in to continue.</p>
                </div>
                <div class="bg-white/95 backdrop-blur-sm p-8 md:p-12 flex flex-col justify-center">
                    <div class="md:hidden text-center mb-8">
                         <img src="<?=get_setting($conDB, 'logo')?>" alt="Company Logo" class="w-32 mx-auto">
                    </div>
                    <h2 class="text-3xl font-bold text-gray-800 mb-2">Login to Your Account</h2>
                    <p class="text-gray-500 mb-8">Enter your credentials to access the portal.</p>
                    <form id="login-form" action="./login.php" method="post" class="space-y-6">
                        <div>
                            <label for="id_iqama" class="block text-sm font-medium text-gray-700 mb-1">رقم الهوية (ID / Iqama)</label>
                            <input class="w-full px-4 py-3 bg-white/80 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 transition duration-150" type="tel" inputmode="numeric" pattern="[0-9]*" name="id_iqama" id="id_iqama" required="" placeholder="أدخل رقم هويتك الوطنية" maxlength="10" value="<?= isset($_POST['id_iqama']) ? htmlspecialchars($_POST['id_iqama'], ENT_QUOTES) : (isset($_GET['id_iqama']) ? htmlspecialchars($_GET['id_iqama'], ENT_QUOTES) : '') ?>" >
                        </div>
                        <div id="password-field-container">
                            <div class="flex justify-between items-center">
                                <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                                <a href="#" id="forgot-password-link" class="text-sm text-blue-600 hover:underline">Forgot Password?</a>
                            </div>
                            <input class="w-full px-4 py-3 bg-white/80 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 transition duration-150" type="password" name="password" id="password" placeholder="Enter your password">
                        </div>
                        <div id="new-password-container" class="space-y-4">
                            <div class="bg-blue-50 border-l-4 border-blue-400 text-blue-700 p-4 rounded-md" role="alert">
                                <p class="font-bold">Welcome!</p>
                                <p>Please set a new password to activate your account.</p>
                            </div>
                            <div>
                                <label for="new_password" class="block text-sm font-medium text-gray-700 mb-1">Set New Password</label>
                                <input class="w-full px-4 py-3 bg-white/80 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 transition duration-150" type="password" name="new_password" id="new_password">
                            </div>
                            <div>
                                <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-1">Confirm New Password</label>
                                <input class="w-full px-4 py-3 bg-white/80 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 transition duration-150" type="password" name="confirm_password" id="confirm_password">
                            </div>
                        </div>
                        <div id="remember-me-container">
                            <div class="flex items-center">
                                <input id="remember_me" name="remember_me" type="checkbox" value="1" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                <label for="remember_me" class="ml-2 block text-sm text-gray-900">Remember me on this device</label>
                            </div>
                        </div>
                        <div id="user-feedback-container" class="p-4 rounded-md text-sm"></div>
                        <?php if(isset($_SESSION['error_message']) && !empty($_SESSION['error_message'])){ echo '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg relative" role="alert">'.$_SESSION['error_message'].'</div>'; unset($_SESSION['error_message']); } ?>
                        <div>
                            <button class="w-full flex items-center justify-center bg-blue-600 text-white font-bold py-3 px-4 rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-150" name="submit" type="submit" id="submitBtn">
                                <span>تسجيل الدخول (Log In)</span>
                            </button>
                        </div>
                            <div class="store-buttons-grid">
                            <a id="android-btn" href="assets/apk/almutlak-wms.apk" download class="w-full flex items-center justify-center gap-2 bg-black text-white py-3 px-4 rounded-lg border border-gray-300 hover:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500 transition duration-150" title="Get it on Google Play" aria-label="Get it on Google Play" style="display:none;">
                                <svg class="w-7 h-7 flex-shrink-0" viewBox="0 0 512 512" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">
                                    <polygon points="30,20 300,256 30,492" fill="#00D26A"/>
                                    <polygon points="30,20 390,150 300,256" fill="#00A0FF"/>
                                    <polygon points="300,256 390,150 480,200 390,256" fill="#FF4B55"/>
                                    <polygon points="300,256 390,256 480,312 390,362" fill="#FFD93B"/>
                                    <polygon points="30,492 300,256 390,362" fill="#00D26A"/>
                                </svg>
                                <span class="text-left leading-tight">
                                    <span class="block text-[10px] font-medium tracking-wide uppercase">Get it on</span>
                                    <span class="block text-lg font-semibold">Google Play</span>
                                </span>
                            </a>
                            <!-- <a href="javascript:void(0);" class="w-full flex items-center justify-center gap-2 bg-black text-white py-3 px-4 rounded-lg border border-gray-300 hover:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-700 transition duration-150" title="Download on the App Store" aria-label="Download on the App Store">
                                <svg class="w-7 h-7 flex-shrink-0" viewBox="0 0 384 512" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">
                                    <path fill="#ffffff" d="M318.7 268.7c-.2-46.5 38-68.8 39.7-69.8-21.7-31.7-55.6-36-67.7-36.5-28.9-2.9-56.4 17-71.1 17-14.8 0-37.8-16.6-62.1-16.2-31.9 .5-61.4 18.6-77.8 47.2-33.2 57.6-8.5 142.8 23.8 189.4 15.8 22.8 34.6 48.3 59.4 47.4 23.9-1 32.9-15.5 61.8-15.5s37 15.5 62.3 15c25.8-.4 42.1-23.3 57.7-46.2 18.1-26.4 25.5-52 25.9-53.3-.6-.3-49.6-19-49.9-75zM271.2 131.3c13-15.8 21.8-37.8 19.4-59.7-18.7 .7-41.3 12.5-54.7 28.3-12.1 14-22.7 36.4-19.8 57.9 20.8 1.6 42.1-10.6 55.1-26.5z"/>
                                </svg>
                                <span class="text-left leading-tight">
                                    <span class="block text-[10px] font-medium tracking-wide uppercase">Download on the</span>
                                    <span class="block text-lg font-semibold">App Store</span>
                                </span>
                            </a> -->
                        </div>

                        <!-- iOS Add to Home Screen Guide (shown only on iOS) -->
                        <div id="ios-install-banner" style="display:none;" class="mt-2 rounded-xl border border-blue-200 bg-blue-50 p-4">
                            <p class="text-sm font-semibold text-blue-800 mb-3 flex items-center gap-2">
                                <svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/></svg>
                                Install App on Your iPhone
                            </p>
                            <ol class="text-sm text-blue-700 space-y-2 list-none">
                                <li class="flex items-start gap-2">
                                    <span class="font-bold text-blue-900 min-w-[20px]">1.</span>
                                    <span>Tap the <strong>Share</strong> button
                                        <svg class="inline w-4 h-4 mx-1 -mt-0.5" fill="currentColor" viewBox="0 0 24 24"><path d="M16 5l-1.42 1.42-1.59-1.59V16h-2.08V4.83L9.42 6.42 8 5l4-4zm4 5v11c0 1.1-.9 2-2 2H6a2 2 0 01-2-2V10a2 2 0 012-2h3v2H6v11h12V10h-3V8h3a2 2 0 012 2z"/></svg>
                                        at the bottom of your browser.
                                    </span>
                                </li>
                                <li class="flex items-start gap-2">
                                    <span class="font-bold text-blue-900 min-w-[20px]">2.</span>
                                    <span>Scroll down and tap <strong>"Add to Home Screen"</strong>
                                        <svg class="inline w-4 h-4 mx-1 -mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="3"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>.
                                    </span>
                                </li>
                                <li class="flex items-start gap-2">
                                    <span class="font-bold text-blue-900 min-w-[20px]">3.</span>
                                    <span>Tap <strong>"Add"</strong> in the top-right corner to save the app icon.</span>
                                </li>
                            </ol>
                        </div>

                    </form>
                </div>
            </div>
        </div>
        
        <script src="assets/js/jquery.min.js"></script>
        <script>
        // Hide loader when page is fully loaded
        window.addEventListener('load', function() {
            var loader = document.getElementById('page-loader');
            if (loader) {
                loader.classList.add('hidden');
                setTimeout(function() { loader.style.display = 'none'; }, 400);
            }
        });
        </script>
        <script>
        // Show Android button only on Android, iOS banner only on iOS
        (function() {
            var ua = navigator.userAgent || navigator.vendor || window.opera;
            var isAndroid = /android/i.test(ua);
            var isIOS = /iphone|ipad|ipod/i.test(ua) && !window.MSStream;
            if (isAndroid) {
                var btn = document.getElementById('android-btn');
                if (btn) btn.style.display = 'flex';
            }
            if (isIOS) {
                var banner = document.getElementById('ios-install-banner');
                if (banner) banner.style.display = 'block';
            }
        })();
        </script>
        <script>
        $(document).ready(function() {
            const iqamaInput = $('#id_iqama');
            
            // Restore ID/Iqama from localStorage if form value is empty
            const savedIqama = localStorage.getItem('last_used_iqama');
            if (savedIqama && iqamaInput.val() === '') {
                iqamaInput.val(savedIqama);
            }
            const feedbackContainer = $('#user-feedback-container');
            const loginForm = $('#login-form');
            const submitBtn = $('#submitBtn');
            const forgotPasswordLink = $('#forgot-password-link');
            const passwordContainer = $('#password-field-container');
            const newPasswordContainer = $('#new-password-container');
            const rememberMeContainer = $('#remember-me-container');
            const passwordInput = $('#password');
            const newPasswordInput = $('#new_password');
            const confirmPasswordInput = $('#confirm_password');

            const spinnerIcon = `<svg class="animate-spin h-5 w-5 mr-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>`;
            const originalButtonContent = submitBtn.html();

            function resetConditionalFields() {
                passwordContainer.slideUp();
                newPasswordContainer.slideUp();
                rememberMeContainer.slideUp();
                forgotPasswordLink.hide();
                feedbackContainer.slideUp().removeClass('bg-red-100 border-red-400 text-red-700 bg-green-100 border-green-400 text-green-700').text('');
                
                passwordInput.prop('required', false);
                newPasswordInput.prop('required', false);
                confirmPasswordInput.prop('required', false);
                submitBtn.prop('disabled', false).removeClass('bg-gray-400 cursor-not-allowed');
            }

            function handleIqamaCheck() {
                const id_iqama = iqamaInput.val();

                // Basic validation before sending AJAX
                if (id_iqama.length !== 10 || (id_iqama.charAt(0) !== '1' && id_iqama.charAt(0) !== '2')) {
                    resetConditionalFields();
                    if(id_iqama.length === 10){ // Only show this error if length is 10 but start digit is wrong
                         feedbackContainer.addClass('bg-red-100 border-red-400 text-red-700').text('ID/Iqama must start with 1 or 2.').slideDown();
                    }
                    return;
                }

                $.ajax({
                    url: './includes/check_user_type.php',
                    type: 'POST', data: { id_iqama: id_iqama }, dataType: 'json',
                    beforeSend: function() {
                        submitBtn.prop('disabled', true).addClass('bg-gray-400 cursor-not-allowed').html(spinnerIcon + '<span>Checking...</span>');
                    },
                    success: function(response) {
                        // Reset fields before showing new ones
                        resetConditionalFields(); 
                        if (response.status === 'success') {
                            if (response.user_type === 'employee') {
                                passwordContainer.slideDown();
                                forgotPasswordLink.show();
                                passwordInput.prop('required', true).focus();
                            } else {
                                rememberMeContainer.slideDown();
                            }
                        } else if (response.status === 'needs_registration') {
                            newPasswordContainer.slideDown();
                            newPasswordInput.prop('required', true);
                            confirmPasswordInput.prop('required', true);
                            newPasswordInput.focus();
                        } else if (response.status === 'not_found') {
                            feedbackContainer.addClass('bg-red-100 border-red-400 text-red-700').text(response.message).slideDown();
                        }
                    },
                    error: function() { 
                        resetConditionalFields();
                        feedbackContainer.addClass('bg-red-100 border-red-400 text-red-700').text('An error occurred while checking user details.').slideDown(); 
                    },
                    complete: function() {
                        submitBtn.prop('disabled', false).removeClass('bg-gray-400 cursor-not-allowed').html(originalButtonContent);
                    }
                });
            }

            iqamaInput.on('input', function() { 
                this.value = this.value.replace(/[^0-9]/g, '');
                // Save value to localStorage
                if (this.value.length > 0) {
                    localStorage.setItem('last_used_iqama', this.value);
                }
                
                if (this.value.length === 10) {
                    handleIqamaCheck();
                } else {
                    resetConditionalFields();
                }
            });

            // Optional: for validation on leaving the field if incomplete
            iqamaInput.on('blur', function() {
                const id_iqama = $(this).val();
                if (id_iqama.length > 0 && id_iqama.length < 10) {
                    feedbackContainer.addClass('bg-red-100 border-red-400 text-red-700').text('ID/Iqama must be exactly 10 digits.').slideDown();
                }
            });

            loginForm.on('submit', function(e) {
                const id_iqama = iqamaInput.val();
                if (id_iqama.length !== 10) {
                    e.preventDefault();
                    feedbackContainer.addClass('bg-red-100 border-red-400 text-red-700').text('ID/Iqama must be exactly 10 digits.').slideDown();
                    return;
                }
                // If validation passes, show loading state
                submitBtn.prop('disabled', true).addClass('bg-gray-400 cursor-not-allowed').html(spinnerIcon + '<span>Logging In...</span>');
            });

            // --- START: Password Reset with SweetAlert2 ---
            forgotPasswordLink.on('click', function(e) {
                e.preventDefault();
                const id_iqama = iqamaInput.val();

                Swal.fire({
                    title: 'Reset Password - Step 1',
                    text: 'Please verify your identity.',
                    html: `
                        <input type="text" id="swal-dob" class="swal2-input" placeholder="Date of Birth">
                        <input type="text" id="swal-mobile" class="swal2-input saudi-mobile-number" placeholder="Registered Mobile Number">
                    `,
                    confirmButtonText: 'Verify',
                    showCancelButton: true,
                    allowOutsideClick: false,
                    didOpen: () => {
                        flatpickr("#swal-dob", {
                            dateFormat: "Y-m-d",
                            altInput: true,
                            altFormat: "F j, Y",
                        });
                    },
                    preConfirm: () => {
                        const dob = document.getElementById('swal-dob').value;
                        const mobile = document.getElementById('swal-mobile').value;
                        if (!dob || !mobile) {
                            Swal.showValidationMessage('Please fill out both fields');
                            return false;
                        }
                        return { dob: dob, mobile: mobile };
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: './includes/reset_password.php',
                            type: 'POST',
                            data: {
                                action: 'verify_employee',
                                id_iqama: id_iqama,
                                dob: result.value.dob,
                                mobile: result.value.mobile
                            },
                            dataType: 'json',
                            success: function(response) {
                                if (response.status === 'success') {
                                    promptForNewPassword(id_iqama);
                                } else {
                                    Swal.fire('Verification Failed', response.message, 'error');
                                }
                            },
                            error: function() {
                                Swal.fire('Error', 'An unexpected error occurred.', 'error');
                            }
                        });
                    }
                });
            });

            function promptForNewPassword(id_iqama) {
                Swal.fire({
                    title: 'Reset Password - Step 2',
                    html: `
                        <input type="password" id="swal-new-password" class="swal2-input" placeholder="New Password">
                        <input type="password" id="swal-confirm-password" class="swal2-input" placeholder="Confirm New Password">
                    `,
                    confirmButtonText: 'Update Password',
                    showCancelButton: true,
                    allowOutsideClick: false,
                    preConfirm: () => {
                        const newPassword = document.getElementById('swal-new-password').value;
                        const confirmPassword = document.getElementById('swal-confirm-password').value;
                        if (newPassword.length < 5) {
                            Swal.showValidationMessage('Password must be at least 5 characters long.');
                            return false;
                        }
                        if (newPassword !== confirmPassword) {
                            Swal.showValidationMessage('Passwords do not match.');
                            return false;
                        }
                        return { new_password: newPassword, confirm_password: confirmPassword };
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: './includes/reset_password.php',
                            type: 'POST',
                            data: {
                                action: 'update_password',
                                id_iqama: id_iqama,
                                new_password: result.value.new_password,
                                confirm_password: result.value.confirm_password
                            },
                            dataType: 'json',
                            success: function(response) {
                                if (response.status === 'success') {
                                    Swal.fire('Success!', response.message, 'success');
                                } else {
                                    Swal.fire('Update Failed', response.message, 'error');
                                }
                            },
                            error: function() {
                                Swal.fire('Error', 'An unexpected error occurred.', 'error');
                            }
                        });
                    }
                });
            }
            // --- END: Password Reset with SweetAlert2 ---
        });
        </script>
    </body>
</html>

