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

// Database overview for the stats strip + the table picker
$dbName = '';
$dbTables = [];
$dbTotalRows = 0;
$dbTotalBytes = 0;
$res = $conDB->query("SELECT DATABASE() AS db");
if ($res && ($row = $res->fetch_assoc())) {
    $dbName = (string) $row['db'];
}
$res = $conDB->query("SELECT TABLE_NAME, TABLE_ROWS, DATA_LENGTH + INDEX_LENGTH AS bytes
    FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_TYPE = 'BASE TABLE'
    ORDER BY TABLE_NAME");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $dbTables[] = $row['TABLE_NAME'];
        $dbTotalRows += (int) $row['TABLE_ROWS'];
        $dbTotalBytes += (int) $row['bytes'];
    }
}

function dbx_bytes(int $bytes): string
{
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $i = 0;
    $size = (float) $bytes;
    while ($size >= 1024 && $i < count($units) - 1) {
        $size /= 1024;
        $i++;
    }
    return number_format($size, $i ? 1 : 0) . ' ' . $units[$i];
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
    <link href="./plugins/select2/css/select2.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/smart_request.css?v=<?= @filemtime(__DIR__ . '/assets/css/smart_request.css') ?>" rel="stylesheet" type="text/css" />
    <script src="assets/js/modernizr.min.js"></script>
    <style>
        .dbx-max { max-width: 1320px; }
        .dbx-stats { margin: 0 0 20px; }
        .dbx-stats .sr-stat { background: var(--sr-surface); box-shadow: var(--sr-shadow); }
        .dbx-stats .sr-stat-label i { color: var(--sr-accent); opacity: .55; }

        /* Key field: masked input + inline icon buttons */
        .dbx-key { display: flex; align-items: stretch; border: 1px solid var(--sr-border-strong); border-radius: 11px; background: var(--sr-surface-2); overflow: hidden; transition: border-color .15s, box-shadow .15s; }
        .dbx-key:focus-within { border-color: var(--sr-accent); box-shadow: 0 0 0 3px rgba(99, 102, 241, .16); }
        .dbx-key-icon { display: flex; align-items: center; padding: 0 14px; color: var(--sr-accent); font-size: 15px; border-inline-end: 1px solid var(--sr-border); }
        .dbx-key input { flex: 1 1 auto; min-width: 0; height: 44px; border: 0; background: transparent; padding: 0 12px; color: var(--sr-text);
            font-family: "SFMono-Regular", Consolas, monospace; font-size: 13px; letter-spacing: .03em; outline: 0; }
        .dbx-key-btn { flex: 0 0 auto; width: 44px; border: 0; border-inline-start: 1px solid var(--sr-border); background: transparent; color: var(--sr-muted); cursor: pointer; transition: background .15s, color .15s; }
        .dbx-key-btn:hover { background: var(--sr-accent-soft); color: var(--sr-accent-strong); }
        .dbx-key-btn.is-ok { color: var(--tone-green-fg); background: var(--tone-green-bg); }
        .dbx-key-foot { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 10px; margin-top: 12px; }
        .dbx-key-foot .sr-fhint { margin: 0; font-size: 12px; }
        .dbx-key-used { display: none; margin-top: 12px; }
        .dbx-key-used.is-shown { display: flex; }
        .dbx-key-used .sr-btn { margin-inline-start: auto; flex: 0 0 auto; }

        /* Scope choice cards */
        .dbx-scope { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px; }
        @media (max-width: 575px) { .dbx-scope { grid-template-columns: 1fr; } }
        .dbx-scope-opt { position: relative; margin: 0 !important; cursor: pointer; }
        .dbx-scope-opt input { position: absolute; opacity: 0; pointer-events: none; }
        .dbx-scope-card { display: flex; gap: 12px; align-items: flex-start; height: 100%; padding: 13px 14px; border: 1px solid var(--sr-border-strong); border-radius: 12px; background: var(--sr-surface-2); transition: border-color .15s, background .15s, box-shadow .15s; }
        .dbx-scope-card > i { width: 34px; height: 34px; flex: 0 0 auto; border-radius: 10px; display: inline-flex; align-items: center; justify-content: center; background: var(--sr-surface-3); color: var(--sr-muted); font-size: 15px; }
        .dbx-scope-card b { display: block; font-size: 13.5px; color: var(--sr-text); }
        .dbx-scope-card small { display: block; margin-top: 2px; font-size: 12px; font-weight: 400; color: var(--sr-muted); line-height: 1.4; }
        .dbx-scope-opt input:checked + .dbx-scope-card { border-color: var(--sr-accent); background: var(--sr-accent-soft); box-shadow: 0 0 0 1px var(--sr-accent) inset; }
        .dbx-scope-opt input:checked + .dbx-scope-card > i { background: var(--sr-accent); color: #fff; }
        .dbx-scope-opt input:focus-visible + .dbx-scope-card { box-shadow: 0 0 0 3px rgba(99, 102, 241, .25); }
        .dbx-tables { display: none; }
        .dbx-tables.is-shown { display: block; }
        .dbx-tables .select2-container { width: 100% !important; }
        .dbx-tables-tools { display: flex; gap: 6px; margin-top: 6px; }
        .dbx-actions { display: flex; flex-wrap: wrap; align-items: center; gap: 12px; padding: 14px 18px; border-top: 1px solid var(--sr-border); background: var(--sr-surface-2); border-radius: 0 0 var(--sr-radius) var(--sr-radius); }
        .dbx-actions .sr-btn-primary { height: 40px; padding: 0 18px; }
        .dbx-summary { font-size: 12.5px; color: var(--sr-muted); }
        .dbx-summary b { color: var(--sr-text); }

        /* Terminal-style command */
        .dbx-cmd { position: relative; border-radius: 12px; background: #0f172a; border: 1px solid #1e293b; overflow: hidden; }
        .dbx-cmd-bar { display: flex; align-items: center; gap: 6px; padding: 8px 12px; border-bottom: 1px solid #1e293b; color: #64748b; font-size: 11.5px; font-weight: 600; }
        .dbx-cmd-bar .dot { width: 9px; height: 9px; border-radius: 50%; background: #334155; }
        .dbx-cmd-bar .dbx-cmd-copy { margin-inline-start: auto; border: 0; background: transparent; color: #94a3b8; font-size: 12px; cursor: pointer; padding: 2px 6px; border-radius: 6px; }
        .dbx-cmd-bar .dbx-cmd-copy:hover { background: #1e293b; color: #e2e8f0; }
        .dbx-cmd pre { margin: 0; padding: 12px 14px; color: #e2e8f0; font-family: "SFMono-Regular", Consolas, monospace; font-size: 12.5px; white-space: pre-wrap; word-break: break-all; }
        .dbx-cmd pre .c { color: #64748b; }
        .dbx-cmd pre .p { color: #818cf8; user-select: none; }

        .dbx-side .sr-card-body { font-size: 13px; }
        .dbx-feature { display: flex; gap: 10px; padding: 9px 0; border-bottom: 1px dashed var(--sr-border); }
        .dbx-feature:last-child { border-bottom: 0; padding-bottom: 0; }
        .dbx-feature:first-child { padding-top: 0; }
        .dbx-feature > i { flex: 0 0 auto; width: 28px; height: 28px; border-radius: 8px; display: inline-flex; align-items: center; justify-content: center; font-size: 12px; }
        .dbx-feature b { display: block; color: var(--sr-text); font-size: 13px; }
        .dbx-feature span { display: block; font-size: 12px; color: var(--sr-muted); line-height: 1.45; }
        .dbx-feature .tone-green { background: var(--tone-green-bg); color: var(--tone-green-fg); }
        .dbx-feature .tone-sky { background: var(--tone-sky-bg); color: var(--tone-sky-fg); }
        .dbx-feature .tone-slate { background: var(--tone-slate-bg); color: var(--tone-slate-fg); }
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
                            <h1>Database Export</h1>
                            <p>Generate a merge-safe <code>.sql</code> snapshot of this server so a local copy can be brought up to date.</p>
                        </div>
                        <div class="sr-head-actions">
                            <span class="sr-pill tone-amber"><i class="fa-solid fa-lock"></i> System admin only</span>
                            <a href="db_import_remote.php" class="sr-btn"><i class="fa-solid fa-cloud-arrow-down"></i> Import From Live</a>
                        </div>
                    </div>

                    <div class="sr-stats dbx-stats">
                        <div class="sr-stat is-sky">
                            <div class="sr-stat-label">Database <i class="fa-solid fa-database"></i></div>
                            <div class="sr-stat-value sr-mono" title="<?= htmlspecialchars($dbName) ?>"><?= htmlspecialchars($dbName ?: '-') ?></div>
                            <div class="sr-stat-sub">Source of the export</div>
                        </div>
                        <div class="sr-stat">
                            <div class="sr-stat-label">Tables <i class="fa-solid fa-table"></i></div>
                            <div class="sr-stat-value"><?= number_format(count($dbTables)) ?></div>
                            <div class="sr-stat-sub">Base tables</div>
                        </div>
                        <div class="sr-stat is-green">
                            <div class="sr-stat-label">Rows <i class="fa-solid fa-list-ol"></i></div>
                            <div class="sr-stat-value">~<?= number_format($dbTotalRows) ?></div>
                            <div class="sr-stat-sub">Engine estimate</div>
                        </div>
                        <div class="sr-stat is-amber">
                            <div class="sr-stat-label">Size <i class="fa-solid fa-hard-drive"></i></div>
                            <div class="sr-stat-value"><?= dbx_bytes($dbTotalBytes) ?></div>
                            <div class="sr-stat-sub">Data + indexes</div>
                        </div>
                    </div>

                    <div class="sr-import">
                        <div>
                            <div class="sr-card">
                                <div class="sr-card-head">
                                    <h5 class="sr-card-title"><i class="fa-solid fa-key"></i> Export Key</h5>
                                    <span class="sr-pill sr-pill-xs tone-indigo"><i class="fa-solid fa-rotate"></i> Single use</span>
                                </div>
                                <div class="sr-card-body">
                                    <div class="dbx-key">
                                        <span class="dbx-key-icon"><i class="fa-solid fa-shield-halved"></i></span>
                                        <input type="password" id="exportKeyField" value="<?= htmlspecialchars($exportKey) ?>" readonly autocomplete="off" spellcheck="false" aria-label="Export key">
                                        <button type="button" class="dbx-key-btn" id="toggleKeyBtn" title="Show key" aria-label="Show key"><i class="fa-solid fa-eye"></i></button>
                                        <button type="button" class="dbx-key-btn" id="copyKeyBtn" title="Copy key" aria-label="Copy key"><i class="fa-solid fa-copy"></i></button>
                                    </div>
                                    <div class="dbx-key-foot">
                                        <small class="sr-fhint">Paste this key into <b>Import From Live</b> on your local machine. It rotates after every successful export.</small>
                                        <button type="button" class="sr-btn sr-btn-sm sr-btn-danger" id="regenKeyBtn"><i class="fa-solid fa-arrows-rotate"></i> Regenerate</button>
                                    </div>
                                    <div class="sr-notice tone-amber dbx-key-used" id="keyUsedNotice">
                                        <i class="fa-solid fa-circle-exclamation"></i>
                                        <div>An export was just started. If it succeeded, the key above has already been rotated on the server.</div>
                                        <button type="button" class="sr-btn sr-btn-sm" id="reloadKeyBtn"><i class="fa-solid fa-rotate-right"></i> Load new key</button>
                                    </div>
                                </div>
                            </div>

                            <div class="sr-card">
                                <div class="sr-card-head">
                                    <h5 class="sr-card-title"><i class="fa-solid fa-file-export"></i> Download Export</h5>
                                    <span class="sr-card-sub">INSERT … ON DUPLICATE KEY UPDATE</span>
                                </div>
                                <form method="POST" action="download_db_export.php" target="_blank" id="exportForm" class="sr-form">
                                    <input type="hidden" name="export_key" value="<?= htmlspecialchars($exportKey) ?>">
                                    <input type="hidden" name="tables" id="tablesHidden" value="">
                                    <div class="sr-fgrid" style="padding: 18px;">
                                        <div class="sr-fcol c-12">
                                            <label>Scope</label>
                                            <div class="dbx-scope">
                                                <label class="dbx-scope-opt">
                                                    <input type="radio" name="dbx_scope" value="all" checked>
                                                    <span class="dbx-scope-card"><i class="fa-solid fa-database"></i><span><b>Entire database</b><small>All <?= number_format(count($dbTables)) ?> tables in one file.</small></span></span>
                                                </label>
                                                <label class="dbx-scope-opt">
                                                    <input type="radio" name="dbx_scope" value="tables">
                                                    <span class="dbx-scope-card"><i class="fa-solid fa-table-list"></i><span><b>Selected tables</b><small>Pick only the tables you need.</small></span></span>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="sr-fcol c-12 dbx-tables" id="tablesWrap">
                                            <label for="tablesSelect">Tables</label>
                                            <select id="tablesSelect" multiple data-placeholder="Search and pick tables...">
                                                <?php foreach ($dbTables as $t) : ?>
                                                    <option value="<?= htmlspecialchars($t) ?>"><?= htmlspecialchars($t) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            <div class="dbx-tables-tools">
                                                <button type="button" class="sr-btn sr-btn-sm sr-btn-ghost" id="tablesAllBtn">Select all</button>
                                                <button type="button" class="sr-btn sr-btn-sm sr-btn-ghost" id="tablesNoneBtn">Clear</button>
                                            </div>
                                        </div>
                                        <div class="sr-fcol c-6">
                                            <label for="sinceInput">Only rows changed since <span style="font-weight: 400; text-transform: none;">(optional)</span></label>
                                            <input type="text" class="form-control" name="since" id="sinceInput" placeholder="Any time" autocomplete="off">
                                            <small class="sr-fhint">Applies to tables with <code>updated_at</code>/<code>created_at</code>; others export in full.</small>
                                        </div>
                                    </div>
                                    <div class="dbx-actions">
                                        <button type="submit" class="sr-btn sr-btn-primary" id="downloadBtn"><i class="fa-solid fa-download"></i> Download .sql</button>
                                        <span class="dbx-summary" id="exportSummary"></span>
                                    </div>
                                </form>
                            </div>

                            <div class="sr-card">
                                <div class="sr-card-head">
                                    <h5 class="sr-card-title"><i class="fa-solid fa-terminal"></i> Load the File Locally</h5>
                                    <span class="sr-card-sub">or phpMyAdmin &rarr; Import</span>
                                </div>
                                <div class="sr-card-body">
                                    <div class="dbx-cmd">
                                        <div class="dbx-cmd-bar"><span class="dot"></span><span class="dot"></span><span class="dot"></span>&nbsp; terminal
                                            <button type="button" class="dbx-cmd-copy" id="copyCmdBtn"><i class="fa-solid fa-copy"></i> Copy</button>
                                        </div>
                                        <pre id="cmdText"><span class="c"># run on your LOCAL machine</span>
<span class="p">$ </span><span id="cmdLine">mysql -u root your_local_db_name &lt; almutlak_export_....sql</span></pre>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="dbx-side">
                            <div class="sr-card">
                                <div class="sr-card-head">
                                    <h5 class="sr-card-title"><i class="fa-solid fa-route"></i> How to Sync</h5>
                                </div>
                                <div class="sr-card-body">
                                    <ol class="sr-guide">
                                        <li><b>Copy the export key</b> from this page.</li>
                                        <li>On your local system open <b>Import From Live</b>, enter this server's URL and the key.</li>
                                        <li><b>Pick tables</b> (only those with differences are pre-selected) and import.</li>
                                        <li>No direct access? <b>Download the .sql</b> here and load it with the command on the left.</li>
                                    </ol>
                                </div>
                            </div>

                            <div class="sr-card">
                                <div class="sr-card-head">
                                    <h5 class="sr-card-title"><i class="fa-solid fa-code-merge"></i> What Happens on Import</h5>
                                </div>
                                <div class="sr-card-body">
                                    <div class="dbx-feature"><i class="fa-solid fa-pen tone-sky"></i><div><b>Matching rows updated</b><span>Rows sharing a primary/unique key take the live values.</span></div></div>
                                    <div class="dbx-feature"><i class="fa-solid fa-plus tone-green"></i><div><b>New rows inserted</b><span>Anything missing locally is added.</span></div></div>
                                    <div class="dbx-feature"><i class="fa-solid fa-flask tone-slate"></i><div><b>Local-only rows kept</b><span>Your test data is never deleted.</span></div></div>
                                </div>
                            </div>

                            <div class="sr-notice tone-red">
                                <i class="fa-solid fa-user-shield"></i>
                                <div><strong>Sensitive data.</strong> Exports include salaries, ID numbers and personal records. Keep the file and the key private and delete the file once loaded.</div>
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
    <script src="./plugins/select2/js/select2.min.js"></script>
    <script src="assets/js/jquery.core.js"></script>
    <script src="assets/js/jquery.app.js?t=<?= time() ?>"></script>
    <script>
        const TOTAL_TABLES = <?= count($dbTables) ?>;
        const $keyField = $('#exportKeyField');
        const $form = $('#exportForm');
        const $tablesSelect = $('#tablesSelect');

        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
        });

        function copyText(text) {
            if (navigator.clipboard && window.isSecureContext) {
                return navigator.clipboard.writeText(text);
            }
            const ta = document.createElement('textarea');
            ta.value = text;
            ta.style.position = 'fixed';
            ta.style.opacity = '0';
            document.body.appendChild(ta);
            ta.select();
            const ok = document.execCommand('copy');
            document.body.removeChild(ta);
            return ok ? Promise.resolve() : Promise.reject();
        }

        function flashOk($btn, html) {
            const original = $btn.html();
            $btn.addClass('is-ok').html(html || '<i class="fa-solid fa-check"></i>');
            setTimeout(() => $btn.removeClass('is-ok').html(original), 1500);
        }

        // --- Key field
        $('#toggleKeyBtn').on('click', function () {
            const show = $keyField.attr('type') === 'password';
            $keyField.attr('type', show ? 'text' : 'password');
            $(this).attr('title', show ? 'Hide key' : 'Show key')
                .html(show ? '<i class="fa-solid fa-eye-slash"></i>' : '<i class="fa-solid fa-eye"></i>');
        });

        $('#copyKeyBtn').on('click', function () {
            copyText($keyField.val())
                .then(() => flashOk($(this)))
                .catch(() => Toast.fire({ icon: 'error', title: 'Copy failed - reveal the key and copy manually' }));
        });

        function setKey(key) {
            $keyField.val(key);
            $form.find('input[name="export_key"]').val(key);
        }

        $('#regenKeyBtn').on('click', function () {
            Swal.fire({
                title: 'Regenerate the export key?',
                html: 'Any script or note using the old key will stop working.',
                icon: 'warning',
                showCancelButton: true,
                allowOutsideClick: false,
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
                        setKey(res.export_key);
                        $('#keyUsedNotice').removeClass('is-shown');
                        copyText(res.export_key)
                            .then(() => Toast.fire({ icon: 'success', title: 'New key generated and copied' }))
                            .catch(() => Toast.fire({ icon: 'success', title: 'New key generated' }));
                    })
                    .catch(() => Toast.fire({ icon: 'error', title: 'Request failed' }));
            });
        });

        // The key is single-use: once download_db_export.php succeeds it rotates the
        // stored key, so the value on this page goes stale. Reloading just re-reads it.
        $('#reloadKeyBtn').on('click', () => window.location.reload());

        // --- Scope + table picker
        $tablesSelect.select2({ placeholder: $tablesSelect.data('placeholder'), closeOnSelect: false, width: '100%' });

        function selectedTables() {
            return $tablesSelect.val() || [];
        }

        function currentScope() {
            return $('input[name="dbx_scope"]:checked').val();
        }

        function updateSummary() {
            const scope = currentScope();
            const since = $('#sinceInput').val();
            const count = scope === 'all' ? TOTAL_TABLES : selectedTables().length;
            let text = '<b>' + count.toLocaleString() + '</b> table' + (count === 1 ? '' : 's');
            text += since ? ' &middot; rows changed since <b>' + $('<div>').text(since).html() + '</b>' : ' &middot; all rows';
            $('#exportSummary').html(text);
        }

        $('input[name="dbx_scope"]').on('change', function () {
            $('#tablesWrap').toggleClass('is-shown', currentScope() === 'tables');
            updateSummary();
        });
        $tablesSelect.on('change', updateSummary);
        AppDate.single('#sinceInput', { enableTime: true, allowInput: true });
        $('#sinceInput').on('change input', updateSummary);
        $('#tablesAllBtn').on('click', () => $tablesSelect.find('option').prop('selected', true).end().trigger('change'));
        $('#tablesNoneBtn').on('click', () => $tablesSelect.val(null).trigger('change'));
        updateSummary();

        $form.on('submit', function (e) {
            if (currentScope() === 'tables') {
                const picked = selectedTables();
                if (!picked.length) {
                    e.preventDefault();
                    Swal.fire({ title: 'No tables selected', text: 'Pick at least one table, or switch the scope to Entire database.', icon: 'warning', allowOutsideClick: false });
                    return;
                }
                $('#tablesHidden').val(picked.join(','));
            } else {
                $('#tablesHidden').val('');
            }

            const $btn = $('#downloadBtn').prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin"></i> Preparing...');
            setTimeout(() => {
                $btn.prop('disabled', false).html('<i class="fa-solid fa-download"></i> Download .sql');
                $('#keyUsedNotice').addClass('is-shown');
            }, 2500);
            Toast.fire({ icon: 'info', title: 'Export started in a new tab' });
        });

        // --- Terminal command
        $('#copyCmdBtn').on('click', function () {
            copyText($('#cmdLine').text())
                .then(() => flashOk($(this), '<i class="fa-solid fa-check"></i> Copied'))
                .catch(() => {});
        });
    </script>
</body>
</html>
