<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/session_check.php';

if (!($is_system_admin ?? false)) {
    header("Location: error403.php?page=" . urlencode(basename(__FILE__)));
    exit;
}

// Self-heal: make sure a secret key exists so the export endpoint isn't left
// wide open just because the setting row was never created.
$exportKey = trim((string) get_setting($conDB, 'db_export_secret_key'));
if ($exportKey === '') {
    $exportKey = bin2hex(random_bytes(32));
    $stmt = $conDB->prepare("INSERT INTO app_settings (setting_name, setting_value) VALUES ('db_export_secret_key', ?)
        ON DUPLICATE KEY UPDATE setting_value = IF(setting_value = '' OR setting_value IS NULL, VALUES(setting_value), setting_value)");
    $stmt->bind_param('s', $exportKey);
    $stmt->execute();
    $exportKey = trim((string) get_setting($conDB, 'db_export_secret_key'));
}

?>
<!doctype html>
<html lang="<?= $current_lang ?? 'en' ?>" <?= ($is_rtl ?? false) ? 'dir="rtl"' : '' ?>>

<head>
    <meta charset="utf-8" />
    <title><?= $site_title ?? '' ?> - Database Export</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta content="Anees Afzal" name="author" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <link rel="shortcut icon" href="<?= get_setting($conDB, 'favicon') ?>">
    <link href="assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/icons.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/metismenu.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/style.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/style_dark.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/smart_request.css?v=<?= @filemtime(__DIR__ . '/assets/css/smart_request.css') ?>" rel="stylesheet" type="text/css" />
    <script src="assets/js/modernizr.min.js"></script>
    <style>
        .dbx-key { display: flex; gap: 8px; align-items: stretch; }
        .dbx-key .form-control { flex: 1 1 auto; font-family: "SFMono-Regular", Consolas, monospace; font-size: 13px; letter-spacing: .02em; }
        .dbx-cmd {
            background: #0f172a; color: #e2e8f0; border-radius: 10px; padding: 12px 16px;
            font-family: "SFMono-Regular", Consolas, monospace; font-size: 13px; overflow-x: auto; margin-bottom: 8px;
        }
        .dbx-max { max-width: 980px; }
    </style>
    <?php if ($is_rtl ?? false) : ?>
        <link href="assets/css/style_rtl.css" rel="stylesheet" type="text/css" />
    <?php endif; ?>
</head>

<body class="enlarged" data-keep-enlarged="true">
    <div id="wrapper">
        <div class="left side-menu">
            <div class="slimscroll-menu" id="remove-scroll">
                <div class="topbar-left">
                    <a href="dashboard.php" class="logo">
                        <span><img src="<?= get_setting($conDB, 'logo') ?>" alt="" height="22"></span>
                        <i><img src="<?= get_setting($conDB, 'white_logo') ?>" alt="" height="28"></i>
                    </a>
                </div>
                <?php include("./includes/main_menu.php"); ?>
                <div class="clearfix"></div>
            </div>
        </div>

        <div class="content-page">
            <?php include("./includes/topbar.php"); ?>
            <div class="content sr-page">
                <div class="container-fluid dbx-max">

                    <div class="sr-head">
                        <div>
                            <h1>Database Export <span style="color: var(--sr-muted); font-weight: 500;">&middot; Live &rarr; Local</span></h1>
                            <p>Pull a diff-friendly copy of this server's data down to your local environment.</p>
                        </div>
                        <div class="sr-head-actions">
                            <span class="sr-pill tone-amber"><i class="mdi mdi-lock"></i> System admin only</span>
                        </div>
                    </div>

                    <div class="sr-notice tone-sky">
                        <i class="mdi mdi-information-outline"></i>
                        <div>
                            Generates a <code>.sql</code> file of this server's database. Every row is written as
                            <code>INSERT ... ON DUPLICATE KEY UPDATE</code>, so running the file against your
                            local database only changes what's different: existing local rows sharing a
                            primary/unique key get updated to match the live values, new rows are inserted,
                            and anything that only exists locally (test data) is left untouched.
                        </div>
                    </div>
                    <div class="sr-notice tone-amber">
                        <i class="mdi mdi-alert"></i>
                        <div>
                            Contains full employee data including salaries and ID numbers. Keep the
                            exported file and the key below private.
                        </div>
                    </div>

                    <div class="sr-card">
                        <div class="sr-card-head">
                            <h5 class="sr-card-title"><i class="mdi mdi-key"></i> Export Key</h5>
                            <span class="sr-card-sub"><i class="mdi mdi-clock"></i> One-time use</span>
                        </div>
                        <div class="sr-card-body sr-form">
                            <div class="dbx-key">
                                <input type="text" class="form-control" id="exportKeyField" value="<?= htmlspecialchars($exportKey) ?>" readonly>
                                <button type="button" class="sr-btn" id="copyKeyBtn" title="Copy key to clipboard"><i class="mdi mdi-content-copy"></i> Copy</button>
                                <button type="button" class="sr-btn sr-btn-danger" id="regenKeyBtn" title="Generate a new key"><i class="mdi mdi-refresh"></i> Regenerate</button>
                            </div>
                            <small class="sr-fhint">This key stops working right after your next successful export/import. Come back here for a new one each time.</small>
                        </div>
                    </div>

                    <div class="sr-card">
                        <div class="sr-card-head">
                            <h5 class="sr-card-title"><i class="mdi mdi-download"></i> Download Export</h5>
                        </div>
                        <form method="POST" action="download_db_export.php" target="_blank" id="exportForm" class="sr-form">
                            <input type="hidden" name="export_key" value="<?= htmlspecialchars($exportKey) ?>">
                            <div class="sr-fgrid" style="padding: 18px;">
                                <div class="sr-fcol c-7">
                                    <label>Only these tables <span style="font-weight: 400;">(optional)</span></label>
                                    <input type="text" class="form-control" name="tables" placeholder="e.g. employees, emp_salary, emp_vacation, payrolls">
                                    <small class="sr-fhint">Comma-separated. Leave blank for the entire database.</small>
                                </div>
                                <div class="sr-fcol c-5">
                                    <label>Only rows changed since <span style="font-weight: 400;">(optional)</span></label>
                                    <input type="datetime-local" class="form-control" name="since">
                                    <small class="sr-fhint">Only tables with <code>updated_at</code>/<code>created_at</code>; others export in full.</small>
                                </div>
                                <div class="sr-fcol c-12">
                                    <button type="submit" class="sr-btn sr-btn-primary"><i class="mdi mdi-download"></i> Download Export</button>
                                </div>
                            </div>
                        </form>
                    </div>

                    <div class="sr-card">
                        <div class="sr-card-head">
                            <h5 class="sr-card-title"><i class="mdi mdi-console"></i> Importing Locally</h5>
                        </div>
                        <div class="sr-card-body">
                            <div class="dbx-cmd">mysql -u root your_local_db_name &lt; almutlak_export_....sql</div>
                            <p class="mb-0" style="color: var(--sr-muted);">Or use phpMyAdmin &rarr; Import on your local database.</p>
                        </div>
                    </div>

                </div>
            </div>
            <footer class="footer"><?= $site_footer ?? '' ?></footer>
        </div>
    </div>

    <script src="assets/js/jquery.min.js"></script>
    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/metisMenu.min.js"></script>
    <script src="assets/js/waves.js"></script>
    <script src="assets/js/jquery.slimscroll.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="assets/js/jquery.core.js"></script>
    <script src="assets/js/jquery.app.js?t=<?= time() ?>"></script>
    <script>
        document.getElementById('copyKeyBtn').addEventListener('click', function () {
            const field = document.getElementById('exportKeyField');
            field.select();
            navigator.clipboard.writeText(field.value);
            const original = this.innerHTML;
            this.innerHTML = '<i class="mdi mdi-check"></i> Copied';
            setTimeout(() => { this.innerHTML = original; }, 1500);
        });

        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
        });

        document.getElementById('regenKeyBtn').addEventListener('click', function () {
            Swal.fire({
                title: 'Regenerate the export key?',
                html: 'Any script or note using the old key will stop working.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Regenerate',
                confirmButtonColor: '#dc3545',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (!result.isConfirmed) {
                    return;
                }

                fetch('./includes/ajaxFile/ajaxRegenerateExportKey.php', { method: 'POST' })
                    .then(res => res.json())
                    .then(res => {
                        if (res.status !== 'success') {
                            Toast.fire({ icon: 'error', title: res.message || 'Failed to regenerate key' });
                            return;
                        }

                        const field = document.getElementById('exportKeyField');
                        field.value = res.export_key;
                        document.getElementById('exportForm').querySelector('input[name="export_key"]').value = res.export_key;

                        field.select();
                        navigator.clipboard.writeText(res.export_key).then(() => {
                            Toast.fire({ icon: 'success', title: 'New key generated and copied to clipboard' });
                        }).catch(() => {
                            Toast.fire({ icon: 'success', title: 'New key generated' });
                        });
                    })
                    .catch(() => {
                        Toast.fire({ icon: 'error', title: 'Request failed' });
                    });
            });
        });
    </script>
</body>
</html>
