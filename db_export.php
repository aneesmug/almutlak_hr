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

if (($_POST['regenerate_key'] ?? '') === '1') {
    $exportKey = bin2hex(random_bytes(32));
    $stmt = $conDB->prepare("UPDATE app_settings SET setting_value = ? WHERE setting_name = 'db_export_secret_key'");
    $stmt->bind_param('s', $exportKey);
    $stmt->execute();
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
        .info-box { background:#e7f3ff; border-left:4px solid #007bff; padding:12px; margin-bottom:16px; }
        .warn-box { background:#fff3cd; border-left:4px solid #ffc107; padding:12px; margin-bottom:16px; }
        .key-display { font-family: monospace; word-break: break-all; }
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
                            <div class="card-box">
                                <h4 class="header-title m-t-0 m-b-30">Database Export (Live &rarr; Local)</h4>

                                <div class="info-box">
                                    Generates a .sql file of this server's database. Every row is written as
                                    <code>INSERT ... ON DUPLICATE KEY UPDATE</code>, so running the file against your
                                    local database only changes what's different: existing local rows sharing a
                                    primary/unique key get updated to match the live values, new rows are inserted,
                                    and anything that only exists locally (test data) is left untouched.
                                </div>
                                <div class="warn-box">
                                    Contains full employee data including salaries and ID numbers. Keep the
                                    exported file and the key below private. System admin only.
                                </div>

                                <div class="form-group">
                                    <label>Export Key</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control key-display" id="exportKeyField" value="<?= htmlspecialchars($exportKey) ?>" readonly>
                                        <div class="input-group-append">
                                            <button type="button" class="btn btn-secondary" id="copyKeyBtn">Copy</button>
                                        </div>
                                    </div>
                                    <small class="form-text text-muted">One-time use: this key stops working right after your next successful export/import. Come back here for a new one each time.</small>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="regenerate_key" value="1">
                                        <button type="submit" class="btn btn-link btn-sm px-0 mt-1"
                                            onclick="return confirm('Regenerate the key? Any script or note using the old key will stop working.');">
                                            Regenerate Key
                                        </button>
                                    </form>
                                </div>

                                <form method="POST" action="download_db_export.php" target="_blank" id="exportForm">
                                    <input type="hidden" name="export_key" value="<?= htmlspecialchars($exportKey) ?>">

                                    <div class="form-group">
                                        <label>Only these tables (optional)</label>
                                        <input type="text" class="form-control" name="tables"
                                            placeholder="e.g. employees, emp_salary, emp_vacation, payrolls">
                                        <small class="form-text text-muted">Comma-separated. Leave blank for the entire database.</small>
                                    </div>

                                    <div class="form-group">
                                        <label>Only rows changed since (optional)</label>
                                        <input type="datetime-local" class="form-control" name="since" style="max-width: 280px;">
                                        <small class="form-text text-muted">
                                            Only applies to tables with an <code>updated_at</code> or <code>created_at</code> column;
                                            other tables are always exported in full. Leave blank for a complete dump.
                                        </small>
                                    </div>

                                    <button type="submit" class="btn btn-primary waves-effect waves-light">
                                        <i class="fa fa-download"></i> Download Export
                                    </button>
                                </form>

                                <hr>
                                <h6>Importing locally</h6>
                                <p class="text-muted mb-0">
                                    <code>mysql -u root your_local_db_name &lt; almutlak_export_....sql</code>
                                </p>
                                <p class="text-muted">Or use phpMyAdmin &rarr; Import on your local database.</p>
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
    <script src="assets/js/jquery.core.js"></script>
    <script src="assets/js/jquery.app.js?t=<?= time() ?>"></script>
    <script>
        document.getElementById('copyKeyBtn').addEventListener('click', function () {
            const field = document.getElementById('exportKeyField');
            field.select();
            navigator.clipboard.writeText(field.value);
            this.textContent = 'Copied';
            setTimeout(() => { this.textContent = 'Copy'; }, 1500);
        });
    </script>
</body>
</html>
