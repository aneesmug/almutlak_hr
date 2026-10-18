<?php
/********************************************************************************
 * Google Authenticator step of the staff sign-in (includes/GoogleAuth/google_auth.php).
 * - mode 'verify': enrolled user types the 6-digit code from the app (set by login.php).
 * - mode 'enroll': first sign-in, after the email OTP was verified (set by
 *   login_verification.php): shows the QR code; the secret is saved and the user is signed
 *   in only after a valid code from the app.
 ********************************************************************************/
require_once __DIR__ . '/includes/session_bootstrap.php';
if (session_status() == PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/login_security.php';
require_once __DIR__ . '/includes/two_factor.php';

header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

function gauth_page_abort($message)
{
    unset($_SESSION['gauth_flow']);
    $_SESSION['error_message'] = $message;
    header("Location: ./index.php");
    exit();
}

$flow = $_SESSION['gauth_flow'] ?? null;
if (empty($flow) || empty($flow['id_iqama'])) {
    header("Location: ./index.php");
    exit();
}
if (time() > (int) $flow['expires']) {
    gauth_page_abort('Your sign-in session expired. Please log in again.');
}
if (!gauth_enabled($conDB)) {
    gauth_page_abort('Google Authenticator sign-in is turned off. Please log in again.');
}

$iqama = (string) $flow['id_iqama'];
$isEnroll = ($flow['mode'] ?? '') === 'enroll';
$errorMessage = '';

$stmt = mysqli_prepare($conDB, "SELECT `id_iqama`, `email`, `user_type`, `status` FROM `admin_login` WHERE `id_iqama` = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, "s", $iqama);
mysqli_stmt_execute($stmt);
$account = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
if (!$account || (int) $account['status'] !== 1 || $account['user_type'] === 'employee' || !gauth_user_assigned($conDB, $iqama)) {
    gauth_page_abort('This account cannot sign in with Google Authenticator.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['gauth_code'])) {
    if (!login_sec_csrf_valid()) {
        $errorMessage = login_sec_csrf_message();
    } elseif (login_sec_is_locked($conDB, $iqama)) {
        gauth_page_abort(login_sec_locked_message());
    } else {
        $code = (string) $_POST['gauth_code'];
        $ok = false;
        try {
            if ($isEnroll) {
                $step = gauth_match_step((string) $flow['secret'], $code);
                if ($step !== null) {
                    $ok = gauth_save_secret($conDB, $iqama, (string) $flow['secret'], $step);
                }
            } else {
                $ok = gauth_verify_user_code($conDB, $iqama, $code);
            }
            if ($ok) {
                gauth_finish_login($conDB, $iqama, !empty($flow['remember_me']));
                header("Location: ./dashboard.php");
                exit();
            }
        } catch (RuntimeException $e) {
            error_log('[GAUTH] ' . $e->getMessage());
            gauth_page_abort($e->getMessage());
        }

        login_sec_record($conDB, $iqama, 'gauth', false);
        $_SESSION['gauth_flow']['attempts'] = (int) ($flow['attempts'] ?? 0) + 1;
        if ($_SESSION['gauth_flow']['attempts'] >= GAUTH_MAX_ATTEMPTS) {
            gauth_page_abort('Too many incorrect codes. Please log in again.');
        }
        $errorMessage = 'Incorrect code. Check the time on your phone is set automatically and try the current code.';
    }
}

// What else this user may do from here
$twoFactor = login_second_factors($conDB, $account);
$canUseEmail = !$isEnroll && $twoFactor['email_fallback'] && !empty($account['email']);
$canUseMicrosoft = in_array('microsoft', $twoFactor['methods'], true);
$csrfToken = login_sec_csrf_token();

$qrSvg = '';
$secretGrouped = '';
if ($isEnroll) {
    $label = !empty($account['email']) ? (string) $account['email'] : $iqama;
    $qrSvg = gauth_qr_svg(gauth_otpauth_uri($conDB, (string) $flow['secret'], $label));
    $secretGrouped = trim(chunk_split((string) $flow['secret'], 4, ' '));
}
$issuer = gauth_settings($conDB)['issuer'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $isEnroll ? 'Set up Google Authenticator' : 'Google Authenticator' ?></title>
    <link rel="shortcut icon" href="<?= htmlspecialchars((string) get_setting($conDB, 'favicon'), ENT_QUOTES) ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: 'Roboto', sans-serif; background: #050506; }
        .ga-card {
            background: radial-gradient(120% 140% at 50% -10%, #1a1b22 0%, #0b0b0f 55%, #08080a 100%);
            border: 1px solid rgba(255, 255, 255, 0.06);
            box-shadow: 0 25px 70px -20px rgba(0, 0, 0, 0.8);
            animation: card-in .5s cubic-bezier(0.16, 1, 0.3, 1);
        }
        @keyframes card-in { from { opacity: 0; transform: translateY(14px) scale(.98); } to { opacity: 1; transform: none; } }
        .eyebrow { letter-spacing: .2em; }
        .ga-code {
            direction: ltr; width: 100%; text-align: center; font-size: 2rem; font-weight: 700; letter-spacing: .5em;
            padding: .7rem 0 .7rem .5em; border-radius: .9rem; color: #f5f5f7; outline: none;
            background: rgba(255, 255, 255, .03); border: 1px solid rgba(255, 255, 255, .12);
            transition: border-color .2s, box-shadow .2s; font-variant-numeric: tabular-nums;
        }
        .ga-code:focus { border-color: #3b82f6; box-shadow: 0 0 0 4px rgba(59, 130, 246, .18); }
        .ga-code.is-error { border-color: rgba(239, 68, 68, .6); animation: shake .45s; }
        @keyframes shake { 20%, 80% { transform: translateX(2px); } 40%, 60% { transform: translateX(-4px); } }
        .ga-qr { background: #fff; border-radius: 14px; padding: 12px; display: inline-block; }
        .ga-qr svg { display: block; width: 190px; height: 190px; }
        .ga-step { display: flex; gap: 12px; text-align: start; }
        .ga-step-n { flex: 0 0 26px; height: 26px; border-radius: 50%; background: rgba(59, 130, 246, .15); color: #93c5fd; font-size: 13px; font-weight: 700; display: inline-flex; align-items: center; justify-content: center; }
        .ga-key { direction: ltr; font-family: ui-monospace, Consolas, monospace; letter-spacing: .08em; }
        .ga-logo { width: 56px; height: 56px; border-radius: 16px; background: #fff; display: inline-flex; align-items: center; justify-content: center; }
    </style>
</head>
<body class="text-gray-200">
    <div class="fixed inset-0 z-[-1]">
        <div class="absolute inset-0 bg-cover bg-center" style="background-image: url('assets/images/login-background.webp');"></div>
        <div class="absolute inset-0 bg-black/75"></div>
    </div>

    <div class="min-h-screen flex items-center justify-center p-4">
        <div class="w-full <?= $isEnroll ? 'max-w-lg' : 'max-w-md' ?>">
            <div class="ga-card rounded-2xl p-8 md:p-10 text-center">
                <span class="ga-logo mb-4">
                    <svg viewBox="0 0 48 48" width="34" height="34" aria-hidden="true"><circle cx="24" cy="24" r="20" fill="none" stroke="#1a73e8" stroke-width="5"/><path d="M24 4a20 20 0 0 1 17.3 10" fill="none" stroke="#ea4335" stroke-width="5"/><path d="M41.3 34A20 20 0 0 1 24 44" fill="none" stroke="#34a853" stroke-width="5"/><path d="M24 14v10l7 5" fill="none" stroke="#5f6368" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </span>
                <p class="eyebrow text-xs font-semibold text-gray-500 uppercase mb-2">Security Check</p>

                <?php if ($isEnroll): ?>
                    <h1 class="text-2xl font-bold text-white mb-2">Set up Google Authenticator</h1>
                    <p class="text-gray-400 text-sm mb-6">Email verified. Link the authenticator app to your account - you only do this once.</p>

                    <div class="space-y-5 mb-6">
                        <div class="ga-step">
                            <span class="ga-step-n">1</span>
                            <p class="text-sm text-gray-300">Install <b class="text-white">Google Authenticator</b> on your phone
                                (<a class="text-blue-400 hover:underline" href="https://play.google.com/store/apps/details?id=com.google.android.apps.authenticator2" target="_blank" rel="noopener">Android</a> /
                                <a class="text-blue-400 hover:underline" href="https://apps.apple.com/app/google-authenticator/id388497605" target="_blank" rel="noopener">iPhone</a>).</p>
                        </div>
                        <div class="ga-step">
                            <span class="ga-step-n">2</span>
                            <div class="text-sm text-gray-300 flex-1 min-w-0">
                                <p class="mb-3">In the app tap <b class="text-white">+</b> &gt; <b class="text-white">Scan a QR code</b>:</p>
                                <div class="text-center"><span class="ga-qr"><?= $qrSvg ?></span></div>
                                <p class="mt-3 text-xs text-gray-500">Can't scan? Choose <b>Enter a setup key</b> and type:</p>
                                <div class="mt-1 flex items-center justify-center gap-2">
                                    <code class="ga-key text-sm text-white bg-white/5 border border-white/10 rounded-lg px-3 py-1.5" id="ga-key"><?= htmlspecialchars($secretGrouped, ENT_QUOTES) ?></code>
                                    <button type="button" id="ga-copy" class="text-xs text-blue-400 hover:text-blue-300">Copy</button>
                                </div>
                                <p class="mt-1 text-xs text-gray-500">Account: <?= htmlspecialchars($issuer, ENT_QUOTES) ?> &middot; Time based</p>
                            </div>
                        </div>
                        <div class="ga-step">
                            <span class="ga-step-n">3</span>
                            <p class="text-sm text-gray-300">Type the 6-digit code the app shows for <b class="text-white"><?= htmlspecialchars($issuer, ENT_QUOTES) ?></b>:</p>
                        </div>
                    </div>
                <?php else: ?>
                    <h1 class="text-2xl font-bold text-white mb-2">Google Authenticator</h1>
                    <p class="text-gray-400 text-sm mb-6">Open the app on your phone and enter the 6-digit code for <b class="text-gray-200"><?= htmlspecialchars($issuer, ENT_QUOTES) ?></b>.</p>
                <?php endif; ?>

                <?php if ($errorMessage !== ''): ?>
                    <div class="mb-4 p-3 rounded-lg text-sm bg-red-500/10 border border-red-500/30 text-red-400"><?= htmlspecialchars($errorMessage, ENT_QUOTES) ?></div>
                <?php endif; ?>

                <form method="post" action="./google_authenticator.php" id="ga-form" class="mb-5">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES) ?>">
                    <input class="ga-code<?= $errorMessage !== '' ? ' is-error' : '' ?>" type="text" name="gauth_code" id="ga-code" inputmode="numeric" pattern="[0-9]*" maxlength="6" autocomplete="one-time-code" placeholder="000000" required autofocus>
                    <button type="submit" id="ga-submit" class="mt-4 w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 rounded-lg transition">
                        <?= $isEnroll ? 'Verify and finish setup' : 'Verify' ?>
                    </button>
                </form>

                <?php if ($canUseMicrosoft): ?>
                    <form method="post" action="./login.php" class="mb-3">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES) ?>">
                        <input type="hidden" name="id_iqama" value="<?= htmlspecialchars($iqama, ENT_QUOTES) ?>">
                        <input type="hidden" name="second_factor" value="microsoft">
                        <?php if (!empty($flow['remember_me'])): ?><input type="hidden" name="remember_me" value="1"><?php endif; ?>
                        <button type="submit" class="w-full border border-white/15 hover:bg-white/5 py-2.5 rounded-lg text-sm transition">Sign in with Microsoft instead</button>
                    </form>
                <?php endif; ?>
                <?php if ($canUseEmail): ?>
                    <form method="post" action="./login.php" class="mb-3">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES) ?>">
                        <input type="hidden" name="id_iqama" value="<?= htmlspecialchars($iqama, ENT_QUOTES) ?>">
                        <input type="hidden" name="use_email_otp" value="1">
                        <?php if (!empty($flow['remember_me'])): ?><input type="hidden" name="remember_me" value="1"><?php endif; ?>
                        <button type="submit" class="w-full border border-white/15 hover:bg-white/5 py-2.5 rounded-lg text-sm transition">No phone? Send me an email code instead</button>
                    </form>
                <?php endif; ?>
                <a href="./index.php" class="inline-block text-sm text-gray-500 hover:text-gray-300 mt-1">Back to login</a>
                <?php if (!$isEnroll): ?>
                    <p class="text-xs text-gray-600 mt-5">Lost or changed your phone? Ask IT support to reset your authenticator.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
    (function () {
        var input = document.getElementById('ga-code');
        var form = document.getElementById('ga-form');
        var sent = false;
        input.addEventListener('input', function () {
            this.value = this.value.replace(/\D/g, '').slice(0, 6);
            this.classList.remove('is-error');
            if (this.value.length === 6 && !sent) { sent = true; form.submit(); }
        });
        form.addEventListener('submit', function () { sent = true; document.getElementById('ga-submit').disabled = true; });
        var copy = document.getElementById('ga-copy');
        if (copy) copy.addEventListener('click', function () {
            var key = document.getElementById('ga-key').textContent.replace(/\s/g, '');
            if (navigator.clipboard) navigator.clipboard.writeText(key).then(function () { copy.textContent = 'Copied'; });
        });
    })();
    </script>
</body>
</html>
