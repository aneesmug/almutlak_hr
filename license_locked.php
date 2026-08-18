<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/session_check.php';

require_once __DIR__ . '/includes/license_check.php';

$statusMessage = trim((string) get_setting($conDB, 'license_cache_message'));
if ($statusMessage === '') {
    $statusMessage = 'This system is not licensed for use.';
}
$currentApiUrl = trim((string) get_setting($conDB, 'license_api_url'));
$currentSerialKey = trim((string) get_setting($conDB, 'license_serial_key'));
?>
<!doctype html>
<html lang="<?= $current_lang ?? 'en' ?>" <?= ($is_rtl ?? false) ? 'dir="rtl"' : '' ?>>
<head>
    <meta charset="utf-8" />
    <title><?= $site_title ?? '' ?> - License Required</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link href="assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
    <style>
        body { display: flex; align-items: center; justify-content: center; min-height: 100vh; background: #f4f6f9; }
        .box { max-width: 480px; background: #fff; border-radius: 10px; padding: 32px; box-shadow: 0 8px 24px rgba(0,0,0,.1); text-align: center; }
        .icon { font-size: 48px; color: #dc3545; margin-bottom: 16px; }
    </style>
</head>
<body>
    <div class="box">
        <div class="icon">&#128274;</div>
        <h4>System Locked</h4>
        <p class="text-muted"><?= htmlspecialchars($statusMessage) ?></p>
        <?php if ($is_system_admin ?? false): ?>
            <button type="button" class="btn btn-primary" id="enterLicenseKeyBtn">Enter License Key</button>
        <?php else: ?>
            <p class="text-muted small">Please contact your system administrator.</p>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // If another tab (or another admin) activates the license while this tab is
        // still sitting on the locked screen, pick that up and leave automatically -
        // no need to re-enter the key here too.
        setInterval(async function () {
            try {
                const response = await fetch('./includes/ajaxFile/ajaxLicenseStatus.php');
                const data = await response.json();
                if (data.valid) {
                    window.location.href = 'dashboard.php';
                }
            } catch (err) {
                // network hiccup - just try again on the next tick
            }
        }, 15000);

        const enterLicenseKeyBtn = document.getElementById('enterLicenseKeyBtn');
        if (enterLicenseKeyBtn) {
            enterLicenseKeyBtn.addEventListener('click', function () {
                Swal.fire({
                    title: 'Activate License',
                    html: `
                        <div class="form-group text-left mb-2">
                            <label class="small font-weight-bold mb-1">Serial Key</label>
                            <input type="text" id="swal-license-serial-key" class="swal2-input" style="width:90%;margin:0;" placeholder="SPH-XXXX-XXXX-XXXX-XXXX">
                        </div>
                        <div class="form-group text-left mb-0">
                            <label class="small font-weight-bold mb-1">Verify URL</label>
                            <input type="text" id="swal-license-verify-url" class="swal2-input" style="width:90%;margin:0;" placeholder="Full URL your vendor gave you, e.g. https://license.example.com/api/check.php?token=...">
                        </div>
                    `,
                    confirmButtonText: 'Verify & Activate',
                    showCancelButton: true,
                    focusConfirm: false,
                    allowOutsideClick: false,
                    preConfirm: () => {
                        const serialKey = document.getElementById('swal-license-serial-key').value.trim();
                        const verifyUrlRaw = document.getElementById('swal-license-verify-url').value.trim();
                        if (!serialKey || !verifyUrlRaw) {
                            Swal.showValidationMessage('Serial key and Verify URL are both required.');
                            return false;
                        }
                        let apiUrl, token;
                        try {
                            const parsed = new URL(verifyUrlRaw);
                            token = parsed.searchParams.get('token');
                            apiUrl = parsed.origin + parsed.pathname;
                        } catch (e) {
                            Swal.showValidationMessage('Verify URL is not a valid URL.');
                            return false;
                        }
                        if (!token) {
                            Swal.showValidationMessage('Verify URL must contain a ?token=... parameter - paste the full URL from the license admin panel.');
                            return false;
                        }
                        return { apiUrl, serialKey, token };
                    }
                }).then(async (result) => {
                    if (!result.isConfirmed) return;

                    Swal.fire({ title: 'Verifying...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

                    try {
                        const response = await fetch('./includes/ajaxFile/ajaxLicenseCheck.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                            body: new URLSearchParams({ api_url: result.value.apiUrl, serial_key: result.value.serialKey, token: result.value.token })
                        });
                        const data = await response.json();

                        if (!data.success) {
                            Swal.fire('Error', data.message || 'Could not save the key.', 'error');
                            return;
                        }
                        if (data.valid) {
                            await Swal.fire('License Active', data.message || 'License verified successfully.', 'success');
                            window.location.href = 'dashboard.php';
                        } else {
                            Swal.fire('License Not Active', data.message || 'This key is not valid.', 'error');
                        }
                    } catch (err) {
                        Swal.fire('Error', 'Request failed: ' + err.message, 'error');
                    }
                });
            });
        }
    </script>
</body>
</html>
