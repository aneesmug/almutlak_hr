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

                                    <button type="button" class="btn btn-outline-secondary waves-effect waves-light mt-2" id="checkSchemaBtn">
                                        <i class="fa fa-list-alt"></i> Check Schema Differences
                                    </button>
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

        // Shared by the "Test Connection" button and the post-import refresh below - both
        // just need the current Local/Live/Diff counts re-pulled and re-rendered.
        function refreshConnectionAndTables(baseUrl, exportKey, onDone) {
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
                if (onDone) onDone();
            });
        }

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
            refreshConnectionAndTables(baseUrl, exportKey, () => $btn.prop('disabled', false));
        });

        // --- Two-step progress UI: Step 1 (only when Truncate is checked) truncates the
        // selected tables in one batched call and ticks each one off from that single
        // response. Step 2 imports the selected tables ONE AT A TIME (a separate run_import
        // call per table) so each table's row/spinner/checkmark updates as it completes -
        // this also means one slow/large table timing out only affects that table, instead
        // of the whole combined multi-table export being thrown away (the old single
        // combined-download behavior).

        // Row status is tracked in a plain JS object (name -> {icon, count, countClass}) and
        // the WHOLE table is re-rendered into the Swal on every change via Swal.update({html}).
        // Deliberately not doing targeted DOM patches (find-by-id into the popup's content) -
        // that approach turned out unreliable against SweetAlert2's own content
        // rendering/timing, silently no-op'ing every row update while the title (a separate,
        // simpler Swal.update call) kept working fine. Re-rendering the full small table on
        // each change trades a little redundant work for actually being guaranteed correct.
        function makeInitialStates(tables) {
            const states = {};
            tables.forEach(t => { states[t] = { icon: ICON_PENDING, count: '-', countClass: 'text-muted' }; });
            return states;
        }

        function renderRowsHtml(tables, states) {
            const rows = tables.map(t => {
                const s = states[t] || { icon: ICON_PENDING, count: '-', countClass: 'text-muted' };
                return `<tr>
                    <td class="text-left">${t}</td>
                    <td class="text-center" style="width:36px;">${s.icon}</td>
                    <td class="text-right ${s.countClass}" style="width:110px;">${s.count}</td>
                </tr>`;
            }).join('');
            return '<div style="max-height:320px; overflow-y:auto;">' +
                '<table class="table table-sm mb-0"><thead><tr><th class="text-left">Table</th><th class="text-center">Status</th><th class="text-right">Rows</th></tr></thead><tbody>' +
                rows + '</tbody></table></div>';
        }

        function renderStep(title, descriptionHtml, tables, states) {
            Swal.update({
                title: title,
                html: (descriptionHtml || '') + renderRowsHtml(tables, states)
            });
        }

        function ajaxPost(data) {
            return $.ajax({
                url: './includes/ajaxFile/ajaxDbImportRemote.php',
                type: 'POST',
                dataType: 'json',
                data: data
            });
        }

        function sleep(ms) {
            return new Promise(resolve => setTimeout(resolve, ms));
        }

        const ICON_PENDING = '<i class="fa fa-circle-o text-muted"></i>';
        const ICON_SPINNING = '<i class="fa fa-spinner fa-spin text-warning"></i>';
        const ICON_DONE = '<i class="fa fa-check text-success"></i>';
        const ICON_SKIPPED = '<i class="fa fa-minus text-muted"></i>';
        const ICON_WARN = '<i class="fa fa-exclamation-triangle text-warning"></i>';
        const ICON_ERROR = '<i class="fa fa-times text-danger"></i>';

        // Step 1 - the truncate itself is one batched local call (fast, no per-table
        // network round trip), so there's nothing to show "live" while it's in flight -
        // instead, once the result comes back, each table's outcome is revealed one at a
        // time with a short pause between them, so it visibly ticks down row by row
        // instead of the whole table flipping to its end state in one frame.
        const ROW_REVEAL_DELAY_MS = 220;

        const TRUNCATE_DESC = '<div class="text-left mb-2 text-muted">Deleting existing local rows before import.</div>';

        async function runTruncateStep(baseUrl, exportKey, tables, states) {
            tables.forEach(t => { states[t] = { icon: ICON_SPINNING, count: '-', countClass: 'text-muted' }; });
            renderStep('Step 1 of 2: Truncating Tables', TRUNCATE_DESC, tables, states);
            await sleep(50); // let the spinners actually paint before the request fires

            let res;
            try {
                res = await ajaxPost({ action: 'truncate_tables', base_url: baseUrl, export_key: exportKey, tables: tables });
            } catch (e) {
                res = { status: 'error', message: 'Truncate request failed.' };
            }

            if (res.status !== 'success') {
                for (const t of tables) {
                    states[t] = { icon: ICON_ERROR, count: 'failed', countClass: 'text-danger' };
                    renderStep('Truncate Error', '<div class="text-danger mb-2">' + (res.message || 'Truncate failed') +
                        (res.errors ? '<br><small>' + res.errors.join('<br>') + '</small>' : '') + '</div>', tables, states);
                    await sleep(ROW_REVEAL_DELAY_MS);
                }
                return { ok: false };
            }

            const truncated = res.truncated_tables || [];
            const skipped = res.skipped_tables || [];
            for (const t of tables) {
                if (truncated.includes(t)) states[t] = { icon: ICON_DONE, count: 'truncated', countClass: 'text-success' };
                else if (skipped.includes(t)) states[t] = { icon: ICON_SKIPPED, count: 'skipped', countClass: 'text-muted' };
                else states[t] = { icon: ICON_ERROR, count: 'error', countClass: 'text-danger' };
                renderStep('Step 1 of 2: Truncating Tables', TRUNCATE_DESC, tables, states);
                await sleep(ROW_REVEAL_DELAY_MS);
            }
            return { ok: true };
        }

        // Step 2 - one run_import call PER table, sequentially, updating that table's row
        // as soon as its own call finishes before moving to the next. Each table enforces a
        // minimum visible spinner time so a fast/small table doesn't just flash past -
        // the point of this step is to actually SEE it move table by table.
        const MIN_ROW_VISIBLE_MS = 350;

        const IMPORT_DESC = '<div class="text-left mb-2 text-muted">Importing tables one at a time from the live server.</div>';

        async function runImportStepPerTable(baseUrl, exportKey, tables, since, truncate, stepLabel, states) {
            tables.forEach(t => { states[t] = { icon: ICON_PENDING, count: '-', countClass: 'text-muted' }; });
            renderStep(stepLabel + ' (0/' + tables.length + ')', IMPORT_DESC, tables, states);
            await sleep(50); // let the fresh table paint before the first request fires

            const tableCounts = {};
            const errors = [];

            for (let i = 0; i < tables.length; i++) {
                const t = tables[i];
                const startedAt = Date.now();
                states[t] = { icon: ICON_SPINNING, count: '-', countClass: 'text-muted' };
                renderStep(stepLabel + ' (' + (i + 1) + '/' + tables.length + ')', IMPORT_DESC, tables, states);

                let res;
                try {
                    res = await ajaxPost({ action: 'run_import', base_url: baseUrl, export_key: exportKey, tables: [t], since: since, truncate: truncate ? 1 : 0 });
                } catch (e) {
                    res = { status: 'error', message: 'Request failed.' };
                }

                // The export key is single-use - the live server rotates it on every
                // successful pull, so the NEXT table's request must use the value it just
                // handed back, not the one this loop started with. Also reflect it in the
                // visible field so the user has the current key if this wizard gets
                // interrupted partway through.
                if (res.next_export_key) {
                    exportKey = res.next_export_key;
                    $exportKey.val(exportKey);
                }

                const elapsed = Date.now() - startedAt;
                if (elapsed < MIN_ROW_VISIBLE_MS) {
                    await sleep(MIN_ROW_VISIBLE_MS - elapsed);
                }

                if (res.status === 'success' || res.status === 'warning') {
                    const count = (res.table_counts && res.table_counts[t]) || 0;
                    tableCounts[t] = count;
                    if (res.status === 'warning') {
                        states[t] = { icon: ICON_WARN, count: count.toLocaleString() + ' rows', countClass: 'text-warning' };
                        errors.push(t + ': ' + res.message);
                    } else {
                        states[t] = { icon: ICON_DONE, count: count.toLocaleString() + ' rows', countClass: 'text-success' };
                    }
                } else {
                    states[t] = { icon: ICON_ERROR, count: 'failed', countClass: 'text-danger' };
                    errors.push(t + ': ' + (res.message || 'Import failed'));
                }
                renderStep(stepLabel + ' (' + (i + 1) + '/' + tables.length + ')', IMPORT_DESC, tables, states);
            }

            return { tableCounts, errors };
        }

        // All the actual async work runs from didOpen, not right after Swal.fire() - fire()
        // returns before the popup has necessarily finished mounting, so an update issued
        // too early can land before there's anything open to update and get silently
        // dropped. Everything from here on only starts once SweetAlert2 confirms the modal
        // is actually on screen, so every status change is guaranteed to render.
        async function runImportWizard(baseUrl, exportKey, tables, since, truncate) {
            const states = makeInitialStates(tables);

            if (truncate) {
                const truncResult = await runTruncateStep(baseUrl, exportKey, tables, states);
                if (!truncResult.ok) {
                    Swal.update({ showConfirmButton: true, confirmButtonText: 'Close' });
                    return;
                }
                await sleep(400); // let the finished truncate checklist stay on screen for a beat
            }

            const stepLabel = truncate ? 'Step 2 of 2: Importing Tables' : 'Importing Tables';
            const { tableCounts, errors } = await runImportStepPerTable(baseUrl, exportKey, tables, since, truncate, stepLabel, states);

            const totalRecords = Object.values(tableCounts).reduce((a, b) => a + b, 0);
            const rowsHtml = Object.keys(tableCounts).map(function(t) {
                return '<tr><td class="text-left">' + t + '</td><td class="text-right">' + tableCounts[t].toLocaleString() + '</td></tr>';
            }).join('');

            Swal.fire({
                title: errors.length ? 'Imported With Errors' : 'Import Complete',
                icon: errors.length ? 'warning' : 'success',
                width: '600px',
                html: '<p>Imported ' + totalRecords.toLocaleString() + ' record(s) across ' + Object.keys(tableCounts).length + ' table(s).</p>' +
                    (rowsHtml ? '<table class="table table-sm mb-2"><thead><tr><th class="text-left">Table</th><th class="text-right">Rows Imported</th></tr></thead><tbody>' + rowsHtml + '</tbody></table>' : '') +
                    (errors.length ? '<div class="text-danger text-left small">' + errors.join('<br>') + '</div>' : '')
            }).then(() => {
                // Refresh the Local/Live/Diff table list so it reflects what was just
                // imported, instead of leaving the pre-import counts on screen. Read the
                // key fresh from the field rather than this function's own exportKey
                // parameter - it's been rotated (and the field updated) per table above.
                refreshConnectionAndTables($baseUrl.val().trim(), $exportKey.val().trim());
            });
        }

        function doImport(baseUrl, exportKey, tables, since, truncate) {
            const initialStates = makeInitialStates(tables);
            Swal.fire({
                title: truncate ? 'Step 1 of 2: Truncating Tables' : 'Importing Tables',
                html: (truncate ? TRUNCATE_DESC : '') + renderRowsHtml(tables, initialStates),
                width: '600px',
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,
                didOpen: () => {
                    runImportWizard(baseUrl, exportKey, tables, since, truncate);
                }
            });
        }

        // Builds one column definition fragment (used inside ADD COLUMN / for display) from
        // the {name, type, nullable, default, extra} shape returned by check_schema.
        function buildColumnDefSql(col) {
            let sql = '`' + col.name + '` ' + col.type;
            sql += col.nullable ? ' NULL' : ' NOT NULL';
            if (col.default !== null && col.default !== undefined) {
                sql += /^CURRENT_TIMESTAMP/i.test(col.default)
                    ? ' DEFAULT ' + col.default
                    : " DEFAULT '" + String(col.default).replace(/'/g, "''") + "'";
            }
            if (col.extra) {
                sql += ' ' + col.extra;
            }
            sql += col.after ? ' AFTER `' + col.after + '`' : ' FIRST';
            return sql;
        }

        function generateAlterSql(report) {
            const localStatements = [];
            const liveStatements = [];

            Object.keys(report).forEach(function(table) {
                const r = report[table];
                const localClauses = [];
                const liveClauses = [];

                (r.missing_in_local || []).forEach(function(col) {
                    const action = $('input[name="oiAction_' + table + '_' + col.name + '"]:checked').val();
                    if (action === 'add_local') {
                        localClauses.push('ADD COLUMN ' + buildColumnDefSql(col));
                    } else if (action === 'remove_live') {
                        liveClauses.push('DROP COLUMN `' + col.name + '`');
                    }
                });
                (r.missing_in_live || []).forEach(function(col) {
                    const action = $('input[name="oiAction_' + table + '_' + col.name + '"]:checked').val();
                    if (action === 'drop_local') {
                        localClauses.push('DROP COLUMN `' + col.name + '`');
                    } else if (action === 'add_live') {
                        liveClauses.push('ADD COLUMN ' + buildColumnDefSql(col));
                    }
                });

                if (localClauses.length) {
                    localStatements.push('ALTER TABLE `' + table + '`\n  ' + localClauses.join(',\n  ') + ';');
                }
                if (liveClauses.length) {
                    liveStatements.push('ALTER TABLE `' + table + '`\n  ' + liveClauses.join(',\n  ') + ';');
                }
            });

            let out = '';
            if (localStatements.length) {
                out += '-- Run on LOCAL database\n' + localStatements.join('\n\n');
            }
            if (liveStatements.length) {
                out += (out ? '\n\n' : '') + '-- Run on LIVE database\n' + liveStatements.join('\n\n');
            }
            return out;
        }

        function showSchemaDiffModal(res) {
            const report = res.report || {};

            const tableBlocks = Object.keys(report).map(function(table) {
                const r = report[table];
                let body = '';

                if (!r.exists_locally) {
                    body += '<p class="text-danger mb-1">Table does not exist locally.</p>';
                }
                if (r.missing_in_local && r.missing_in_local.length) {
                    body += '<div class="text-danger small mb-1">Missing locally - live has this column:</div>';
                    r.missing_in_local.forEach(function(col) {
                        const name = 'oiAction_' + table + '_' + col.name;
                        body += '<div class="mb-2"><code>' + col.name + '</code> ' + col.type + (col.nullable ? '' : ' NOT NULL') +
                            '<div class="form-check form-check-inline ml-2">' +
                            '<input type="radio" class="form-check-input" name="' + name + '" id="' + name + '_add" value="add_local" checked>' +
                            '<label class="form-check-label" for="' + name + '_add">Add to Local</label></div>' +
                            '<div class="form-check form-check-inline">' +
                            '<input type="radio" class="form-check-input" name="' + name + '" id="' + name + '_rm" value="remove_live">' +
                            '<label class="form-check-label" for="' + name + '_rm">Remove from Live</label></div>' +
                            '<div class="form-check form-check-inline">' +
                            '<input type="radio" class="form-check-input" name="' + name + '" id="' + name + '_skip" value="skip">' +
                            '<label class="form-check-label" for="' + name + '_skip">Skip</label></div>' +
                            '</div>';
                    });
                }
                if (r.missing_in_live && r.missing_in_live.length) {
                    body += '<div class="text-warning small mb-1 mt-2">Extra locally - live does not have this column:</div>';
                    r.missing_in_live.forEach(function(col) {
                        const name = 'oiAction_' + table + '_' + col.name;
                        body += '<div class="mb-2"><code>' + col.name + '</code> ' + col.type +
                            '<div class="form-check form-check-inline ml-2">' +
                            '<input type="radio" class="form-check-input" name="' + name + '" id="' + name + '_drop" value="drop_local" checked>' +
                            '<label class="form-check-label" for="' + name + '_drop">Drop from Local</label></div>' +
                            '<div class="form-check form-check-inline">' +
                            '<input type="radio" class="form-check-input" name="' + name + '" id="' + name + '_addlive" value="add_live">' +
                            '<label class="form-check-label" for="' + name + '_addlive">Add to Live</label></div>' +
                            '<div class="form-check form-check-inline">' +
                            '<input type="radio" class="form-check-input" name="' + name + '" id="' + name + '_skip" value="skip">' +
                            '<label class="form-check-label" for="' + name + '_skip">Skip</label></div>' +
                            '</div>';
                    });
                }
                if (r.type_mismatches && r.type_mismatches.length) {
                    body += '<div class="text-warning small mb-1 mt-2">Type differs (review manually, not auto-generated):</div>';
                    r.type_mismatches.forEach(function(m) {
                        body += '<div class="small">' + m.column + ' - local: <code>' + m.local_type + '</code> vs live: <code>' + m.live_type + '</code></div>';
                    });
                }
                if (!body) {
                    body = '<span class="text-success">Matches</span>';
                }

                return '<div class="mb-3"><div class="font-weight-bold">' + table + '</div>' + body + '</div>';
            }).join('<hr>');

            Swal.fire({
                title: res.any_mismatch ? 'Schema differences found' : 'Schema matches',
                icon: res.any_mismatch ? 'warning' : 'success',
                width: '750px',
                html: '<div style="max-height:350px; overflow-y:auto; text-align:left;">' + tableBlocks + '</div>' +
                    (res.any_mismatch ? (
                        '<div class="mt-3 text-left">' +
                        '<button type="button" class="btn btn-sm btn-primary" id="genAlterBtn">Generate ALTER SQL</button> ' +
                        '<button type="button" class="btn btn-sm btn-secondary" id="copyAlterBtn" style="display:none;">Copy</button>' +
                        '<textarea id="alterSqlOut" class="form-control mt-2" rows="8" readonly style="display:none; font-family:monospace; font-size:12px;"></textarea>' +
                        '<small class="form-text text-muted">Output is split into LOCAL and LIVE sections based on your selections above - run each on the matching server.</small>' +
                        '</div>'
                    ) : ''),
                showConfirmButton: true,
                confirmButtonText: 'Close'
            });

            $(document).off('click', '#genAlterBtn').on('click', '#genAlterBtn', function() {
                const sql = generateAlterSql(report);
                const $out = $('#alterSqlOut');
                $out.val(sql || '-- No columns selected.').show();
                $('#copyAlterBtn').show();
            });

            $(document).off('click', '#copyAlterBtn').on('click', '#copyAlterBtn', function() {
                const $out = $('#alterSqlOut');
                $out.select();
                navigator.clipboard.writeText($out.val()).then(function() {
                    $('#copyAlterBtn').text('Copied!');
                    setTimeout(function() { $('#copyAlterBtn').text('Copy'); }, 1500);
                }).catch(function() {
                    document.execCommand('copy');
                });
            });
        }

        $('#checkSchemaBtn').on('click', function() {
            const baseUrl = $baseUrl.val().trim();
            const exportKey = $exportKey.val().trim();
            const tables = $('.table-check:checked').map(function() { return this.value; }).get();

            if (!tables.length) {
                Swal.fire('Nothing selected', 'Pick at least one table.', 'warning');
                return;
            }

            const $btn = $(this).prop('disabled', true);
            Swal.fire({
                title: 'Checking schema...',
                html: 'Comparing columns for ' + tables.length + ' table(s) against the live server.',
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => Swal.showLoading()
            });

            $.ajax({
                url: './includes/ajaxFile/ajaxDbImportRemote.php',
                type: 'POST',
                dataType: 'json',
                data: { action: 'check_schema', base_url: baseUrl, export_key: exportKey, tables: tables }
            }).done(function(res) {
                if (res.status !== 'success') {
                    Swal.fire('Schema check failed', res.message || 'Unknown error.', 'error');
                    return;
                }

                showSchemaDiffModal(res);
            }).fail(function() {
                Swal.fire('Request failed', 'Could not reach the local server.', 'error');
            }).always(function() {
                $btn.prop('disabled', false);
            });
        });

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
