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
    <link href="assets/css/metismenu.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/style.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/style_dark.css" rel="stylesheet" type="text/css" />
    <script src="assets/js/modernizr.min.js"></script>
    <style>
        .db-export-header { display:flex; align-items:center; gap:14px; margin-bottom:24px; }
        .db-export-header .icon-circle {
            width:48px; height:48px; border-radius:50%; background:#eef2ff; color:#4f5cd4;
            display:flex; align-items:center; justify-content:center; font-size:20px; flex-shrink:0;
        }
        .db-export-header h4 { margin:0; }
        .db-export-header p { margin:2px 0 0; color:#6c757d; font-size:0.9rem; }
        .section-card {
            background:#fff; border:1px solid #eef0f5; border-radius:10px;
            padding:22px 24px; margin-bottom:20px;
        }
        .section-card h6 {
            font-weight:600; text-transform:uppercase; font-size:0.78rem; letter-spacing:.04em;
            color:#8892a3; margin-bottom:16px; display:flex; align-items:center; gap:8px;
        }
        .section-card h6 i { color:#4f5cd4; }
        .key-display {
            font-family: 'Consolas', monospace; word-break: break-all; background:#f7f8fb !important;
            font-size:0.92rem; letter-spacing:.02em;
        }
        .key-input-group .btn { font-size:0.85rem; }
        .import-cmd {
            background:#282c34; color:#e6e6e6; border-radius:8px; padding:12px 16px;
            font-family:'Consolas', monospace; font-size:0.85rem; overflow-x:auto; margin-bottom:8px;
        }
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
            <div class="content">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-12">
                            <div class="db-export-header">
                                <div class="icon-circle"><i class="fa fa-database"></i></div>
                                <div>
                                    <h4>Database Export <span class="text-muted font-weight-normal">&middot; Live &rarr; Local</span></h4>
                                    <p>Pull a diff-friendly copy of this server's data down to your local environment.</p>
                                </div>
                            </div>

                            <div class="alert alert-info d-flex align-items-start" role="alert">
                                <i class="fa fa-info-circle mt-1 mr-2"></i>
                                <div>
                                    Generates a <code>.sql</code> file of this server's database. Every row is written as
                                    <code>INSERT ... ON DUPLICATE KEY UPDATE</code>, so running the file against your
                                    local database only changes what's different: existing local rows sharing a
                                    primary/unique key get updated to match the live values, new rows are inserted,
                                    and anything that only exists locally (test data) is left untouched.
                                </div>
                            </div>
                            <div class="alert alert-warning d-flex align-items-start" role="alert">
                                <i class="fa fa-exclamation-triangle mt-1 mr-2"></i>
                                <div>
                                    <span class="badge badge-warning mr-1">System admin only</span>
                                    Contains full employee data including salaries and ID numbers. Keep the
                                    exported file and the key below private.
                                </div>
                            </div>

                            <div class="section-card">
                                <h6><i class="fa fa-key"></i> Export Key</h6>
                                <div class="form-group mb-2">
                                    <div class="input-group input-group-lg key-input-group">
                                        <input type="text" class="form-control key-display" id="exportKeyField" value="<?= htmlspecialchars($exportKey) ?>" readonly>
                                        <div class="input-group-append">
                                            <button type="button" class="btn btn-outline-secondary" id="copyKeyBtn" title="Copy key to clipboard">
                                                <i class="fa fa-copy"></i> Copy
                                            </button>
                                            <button type="button" class="btn btn-outline-danger" id="regenKeyBtn" title="Generate a new key">
                                                <i class="fa fa-sync-alt"></i> Regenerate
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <small class="form-text text-muted">
                                    <i class="fa fa-clock"></i> One-time use - this key stops working right after your next successful export/import. Come back here for a new one each time.
                                </small>
                            </div>

                            <div class="section-card">
                                <h6><i class="fa fa-download"></i> Download Export</h6>
                                <form method="POST" action="download_db_export.php" target="_blank" id="exportForm">
                                    <input type="hidden" name="export_key" value="<?= htmlspecialchars($exportKey) ?>">

                                    <div class="row">
                                        <div class="col-md-7">
                                            <div class="form-group">
                                                <label>Only these tables <span class="text-muted font-weight-normal">(optional)</span></label>
                                                <input type="text" class="form-control" name="tables"
                                                    placeholder="e.g. employees, emp_salary, emp_vacation, payrolls">
                                                <small class="form-text text-muted">Comma-separated. Leave blank for the entire database.</small>
                                            </div>
                                        </div>
                                        <div class="col-md-5">
                                            <div class="form-group">
                                                <label>Only rows changed since <span class="text-muted font-weight-normal">(optional)</span></label>
                                                <input type="datetime-local" class="form-control" name="since">
                                                <small class="form-text text-muted">
                                                    Only tables with <code>updated_at</code>/<code>created_at</code>; others export in full.
                                                </small>
                                            </div>
                                        </div>
                                    </div>

                                    <button type="submit" class="btn btn-primary waves-effect waves-light">
                                        <i class="fa fa-download"></i> Download Export
                                    </button>
                                </form>
                            </div>

                            <div class="section-card mb-0">
                                <h6><i class="fa fa-terminal"></i> Importing Locally</h6>
                                <div class="import-cmd">mysql -u root your_local_db_name &lt; almutlak_export_....sql</div>
                                <p class="text-muted mb-0">Or use phpMyAdmin &rarr; Import on your local database.</p>
                            </div>
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
            this.innerHTML = '<i class="fa fa-check"></i> Copied';
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
