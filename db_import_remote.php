<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/session_check.php';

if (!($is_system_admin ?? false)) {
    header("Location: error403.php?page=" . urlencode(basename(__FILE__)));
    exit;
}
?>
<!doctype html>
<html lang="<?= $current_lang ?? 'en' ?>" <?= ($is_rtl ?? false) ? 'dir="rtl"' : '' ?>>

<head>
    <meta charset="utf-8" />
    <title><?= $site_title ?? '' ?> - Import From Live Server</title>
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
        .conn-dot { display:inline-block; width:10px; height:10px; border-radius:50%; margin-right:6px; }
        .conn-dot.ok { background:#28a745; }
        .conn-dot.fail { background:#dc3545; }
        #tableListWrap { max-height:400px; overflow-y:auto; border:1px solid #dee2e6; border-radius:6px; padding:10px; }
        .table-row:hover { background:#f8f9fa; }
        .table-row small { color:#6c757d; }
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
                                <h4 class="header-title m-t-0 m-b-30">Import From Live Server</h4>

                                <div class="info-box">
                                    Pulls data directly from the live server's export API into this local database.
                                    Every row is upserted: matching rows are updated, new rows inserted, and anything
                                    that only exists locally is left untouched.
                                </div>
                                <div class="warn-box">
                                    This writes directly to your local database. Only use with a live server URL and
                                    key you trust - System admin only.
                                </div>

                                <div class="row">
                                    <div class="col-md-8">
                                        <div class="form-group">
                                            <label>Live Server URL</label>
                                            <input type="text" class="form-control" id="baseUrl" placeholder="https://your-live-domain.com">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Export Key</label>
                                            <input type="password" class="form-control" id="exportKey" placeholder="Key from db_export.php on live">
                                        </div>
                                    </div>
                                </div>
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="rememberUrl">
                                    <label class="form-check-label" for="rememberUrl">Remember this URL on this browser (key is never saved)</label>
                                </div>

                                <button type="button" class="btn btn-secondary" id="testConnBtn">
                                    <i class="fa fa-plug"></i> Test Connection
                                </button>
                                <span id="connStatus" class="ml-2"></span>

                                <div id="pickerSection" style="display:none;" class="mt-4">
                                    <hr>
                                    <div class="row align-items-end mb-2">
                                        <div class="col-md-6">
                                            <label class="mb-0">Tables (<span id="tableCount">0</span> shown of <span id="tableTotalCount">0</span>)</label>
                                            <div class="form-check d-inline-block ml-3">
                                                <input class="form-check-input" type="checkbox" id="showAllTables">
                                                <label class="form-check-label" for="showAllTables">Show all tables</label>
                                            </div>
                                        </div>
                                        <div class="col-md-6 text-md-end">
                                            <input type="text" class="form-control form-control-sm d-inline-block" style="max-width:200px;" id="tableFilter" placeholder="Filter tables...">
                                            <button type="button" class="btn btn-link btn-sm" id="selectAllBtn">Select All</button>
                                            <button type="button" class="btn btn-link btn-sm" id="selectNoneBtn">Select None</button>
                                            <button type="button" class="btn btn-link btn-sm" id="selectDiffBtn">Select Diff Only</button>
                                        </div>
                                    </div>
                                    <div id="tableListWrap"></div>

                                    <div class="form-group mt-3" style="max-width:320px;">
                                        <label>Only rows changed since (optional)</label>
                                        <input type="datetime-local" class="form-control" id="sinceInput">
                                        <small class="form-text text-muted">Applies only to tables with an <code>updated_at</code>/<code>created_at</code> column.</small>
                                    </div>

                                    <div class="form-check mt-3">
                                        <input class="form-check-input" type="checkbox" id="truncateFirst">
                                        <label class="form-check-label" for="truncateFirst">
                                            Truncate selected tables before import (full replace)
                                        </label>
                                        <small class="form-text text-muted">
                                            Deletes all local rows in the selected tables first, then imports live data.
                                            Use this when local and live have the same row count but different data -
                                            a plain upsert won't catch that. Cannot be combined with "changed since".
                                        </small>
                                    </div>

                                    <button type="button" class="btn btn-primary waves-effect waves-light mt-2" id="importBtn">
                                        <i class="fa fa-download"></i> Import Selected Into Local DB
                                    </button>
                                </div>
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
        let liveTables = [];

        const $baseUrl = $('#baseUrl');
        const $exportKey = $('#exportKey');

        const savedUrl = localStorage.getItem('db_import_remote_base_url');
        if (savedUrl) {
            $baseUrl.val(savedUrl);
            $('#rememberUrl').prop('checked', true);
        }

        function renderTableList(tables) {
            liveTables = tables;
            $('#tableTotalCount').text(tables.length);
            const $wrap = $('#tableListWrap').empty();
            const $table = $('<table class="table table-sm mb-0"><thead><tr>' +
                '<th></th><th>Table</th><th class="text-right">Local</th>' +
                '<th class="text-right">Live</th><th class="text-right">Diff</th>' +
                '</tr></thead><tbody></tbody></table>');
            const $tbody = $table.find('tbody');

            tables.forEach(t => {
                const diff = t.diff || 0;
                const localLabel = t.exists_locally ? t.local_rows.toLocaleString() : '<span class="text-muted">missing</span>';
                let diffLabel = '0';
                let diffClass = 'text-muted';
                if (diff > 0) { diffLabel = '+' + diff.toLocaleString(); diffClass = 'text-success'; }
                else if (diff < 0) { diffLabel = diff.toLocaleString(); diffClass = 'text-danger'; }

                const $row = $(`
                    <tr class="table-row" data-diff="${diff}">
                        <td><input type="checkbox" class="table-check" value="${t.name}" ${diff !== 0 ? 'checked' : ''}></td>
                        <td>${t.name}</td>
                        <td class="text-right">${localLabel}</td>
                        <td class="text-right">${t.live_rows.toLocaleString()}</td>
                        <td class="text-right ${diffClass}">${diffLabel}</td>
                    </tr>
                `);
                $tbody.append($row);
            });
            $wrap.append($table);
            $('#pickerSection').show();
            applyRowVisibility();
        }

        // Default view: only tables with a diff. "Show all tables" reveals the rest;
        // the text filter narrows within whichever set is currently visible-eligible.
        function applyRowVisibility() {
            const showAll = $('#showAllTables').is(':checked');
            const q = $('#tableFilter').val().toLowerCase();
            let shown = 0;
            $('.table-row').each(function() {
                const $row = $(this);
                const diff = parseInt($row.attr('data-diff'), 10) || 0;
                const name = $row.find('.table-check').val().toLowerCase();
                const matchesFilter = name.includes(q);
                const matchesDiff = showAll || diff !== 0;
                const visible = matchesFilter && matchesDiff;
                $row.toggle(visible);
                if (visible) shown++;
            });
            $('#tableCount').text(shown);
        }

        $('#tableFilter').on('input', applyRowVisibility);
        $('#showAllTables').on('change', applyRowVisibility);

        const $sinceInput = $('#sinceInput');
        const $truncateFirst = $('#truncateFirst');

        $truncateFirst.on('change', function() {
            if ($(this).is(':checked')) {
                $sinceInput.val('').prop('disabled', true);
            } else {
                $sinceInput.prop('disabled', false);
            }
        });

        $('#selectAllBtn').on('click', () => $('.table-row:visible .table-check').prop('checked', true));
        $('#selectNoneBtn').on('click', () => $('.table-row:visible .table-check').prop('checked', false));
        $('#selectDiffBtn').on('click', function() {
            $('.table-row:visible').each(function() {
                const $cb = $(this).find('.table-check');
                const name = $cb.val();
                const t = liveTables.find(x => x.name === name);
                $cb.prop('checked', !!(t && t.diff !== 0));
            });
        });

        $('#testConnBtn').on('click', function() {
            const baseUrl = $baseUrl.val().trim();
            const exportKey = $exportKey.val().trim();
            if (!baseUrl || !exportKey) {
                Swal.fire('Missing info', 'Enter both the live server URL and export key.', 'warning');
                return;
            }

            if ($('#rememberUrl').is(':checked')) {
                localStorage.setItem('db_import_remote_base_url', baseUrl);
                localStorage.setItem('db_import_remote_export_key', exportKey);
            } else {
                localStorage.removeItem('db_import_remote_base_url');
                localStorage.removeItem('db_import_remote_export_key');
            }

            const $btn = $(this).prop('disabled', true);
            $('#connStatus').html('<span class="text-muted">Connecting...</span>');

            $.ajax({
                url: './includes/ajaxFile/ajaxDbImportRemote.php',
                type: 'POST',
                dataType: 'json',
                data: { action: 'test_connection', base_url: baseUrl, export_key: exportKey }
            }).done(function(res) {
                if (res.status === 'success') {
                    $('#connStatus').html('<span class="conn-dot ok"></span>Connected - ' + res.database);
                    renderTableList(res.tables || []);
                } else {
                    $('#connStatus').html('<span class="conn-dot fail"></span>' + (res.message || 'Connection failed'));
                    $('#pickerSection').hide();
                }
            }).fail(function() {
                $('#connStatus').html('<span class="conn-dot fail"></span>Request failed');
                $('#pickerSection').hide();
            }).always(function() {
                $btn.prop('disabled', false);
            });
        });

        function runImportStep(baseUrl, exportKey, tables, since, truncate) {
            Swal.update({
                title: 'Importing...',
                html: 'Pulling ' + tables.length + ' table(s) from the live server and applying to local DB.'
            });

            $.ajax({
                url: './includes/ajaxFile/ajaxDbImportRemote.php',
                type: 'POST',
                dataType: 'json',
                data: { action: 'run_import', base_url: baseUrl, export_key: exportKey, tables: tables, since: since, truncate: truncate ? 1 : 0 }
            }).done(function(res) {
                if (res.status === 'success' || res.status === 'warning') {
                    const counts = res.table_counts || {};
                    const rowsHtml = Object.keys(counts).map(function(t) {
                        return '<tr><td class="text-left">' + t + '</td><td class="text-right">' + counts[t].toLocaleString() + '</td></tr>';
                    }).join('');
                    Swal.fire({
                        title: res.status === 'warning' ? 'Imported with warnings' : 'Imported',
                        icon: res.status === 'warning' ? 'warning' : 'success',
                        html: '<p>' + res.message + '</p>' +
                            (rowsHtml ? '<table class="table table-sm mb-0"><tbody>' + rowsHtml + '</tbody></table>' : '')
                    });
                } else {
                    Swal.fire({
                        title: 'Import Error',
                        html: (res.message || 'Import failed') + (res.errors ? '<br><small>' + res.errors.join('<br>') + '</small>' : ''),
                        icon: 'error'
                    });
                }
            }).fail(function() {
                Swal.fire('Error', 'Request failed.', 'error');
            });
        }

        function doImport(baseUrl, exportKey, tables, since, truncate) {
            Swal.fire({
                title: truncate ? 'Truncating...' : 'Importing...',
                html: truncate
                    ? 'Deleting existing local rows in ' + tables.length + ' table(s) before importing.'
                    : 'Pulling ' + tables.length + ' table(s) from the live server and applying to local DB.',
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => Swal.showLoading()
            });

            if (!truncate) {
                runImportStep(baseUrl, exportKey, tables, since, false);
                return;
            }

            $.ajax({
                url: './includes/ajaxFile/ajaxDbImportRemote.php',
                type: 'POST',
                dataType: 'json',
                data: { action: 'truncate_tables', base_url: baseUrl, export_key: exportKey, tables: tables }
            }).done(function(res) {
                if (res.status !== 'success') {
                    Swal.fire({
                        title: 'Truncate Error',
                        html: (res.message || 'Truncate failed') + (res.errors ? '<br><small>' + res.errors.join('<br>') + '</small>' : ''),
                        icon: 'error'
                    });
                    return;
                }
                runImportStep(baseUrl, exportKey, tables, since, true);
            }).fail(function() {
                Swal.fire('Error', 'Truncate request failed - nothing was imported.', 'error');
            });
        }

        $('#importBtn').on('click', function() {
            const baseUrl = $baseUrl.val().trim();
            const exportKey = $exportKey.val().trim();
            const tables = $('.table-check:checked').map(function() { return this.value; }).get();
            const since = $truncateFirst.is(':checked') ? '' : $sinceInput.val();
            const truncate = $truncateFirst.is(':checked');

            if (!tables.length) {
                Swal.fire('Nothing selected', 'Pick at least one table.', 'warning');
                return;
            }

            if (truncate) {
                Swal.fire({
                    title: 'Truncate before import?',
                    icon: 'warning',
                    html: 'This deletes ALL local rows in <b>' + tables.length + ' table(s)</b> first, then loads live data. ' +
                        'Any row that exists only locally will be permanently lost. This cannot be undone.',
                    showCancelButton: true,
                    confirmButtonText: 'Truncate & Import',
                    confirmButtonColor: '#dc3545',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        doImport(baseUrl, exportKey, tables, since, true);
                    }
                });
                return;
            }

            doImport(baseUrl, exportKey, tables, since, false);
        });
    </script>
</body>
</html>
