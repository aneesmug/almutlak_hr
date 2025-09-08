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
        <title><?php echo isset($site_title) ? $site_title : 'Login'; ?> - Al Mutlak Access</title>
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
        <meta content="A fully featured system which can be used to build CRM, CMS, etc." name="description" />
        <meta content="Anees Afzal" name="author" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />
        <link rel="shortcut icon" href="assets/images/favicon.ico">
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
        </style>
    </head>
    <body>
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
                         <img src="assets/images/logo.png" alt="Company Logo" class="w-32 mx-auto">
                    </div>
                    <h2 class="text-3xl font-bold text-gray-800 mb-2">Login to Your Account</h2>
                    <p class="text-gray-500 mb-8">Enter your credentials to access the portal.</p>
                    <form id="login-form" action="./login.php" method="post" class="space-y-6">
                        <div>
                            <label for="id_iqama" class="block text-sm font-medium text-gray-700 mb-1">رقم الهوية (ID / Iqama)</label>
                            <input class="w-full px-4 py-3 bg-white/80 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 transition duration-150" type="tel" inputmode="numeric" pattern="[0-9]*" name="id_iqama" id="id_iqama" required="" placeholder="أدخل رقم هويتك الوطنية" maxlength="10">
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
                    </form>
                </div>
            </div>
        </div>
        
        <script src="assets/js/jquery.min.js"></script>
        <script>
        $(document).ready(function() {
            const iqamaInput = $('#id_iqama');
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

