<?php
require_once __DIR__ . '/includes/session_check.php';
require_once __DIR__ . '/includes/special_access_helper.php';
require_once __DIR__ . '/includes/zk_helpers.php';
// Attendance switched off in App Settings > Integrations
if (!attendance_enabled($conDB)) {
    http_response_code(403);
    die('Attendance is turned off (App Settings > Integrations)');
}
include(__DIR__ . '/includes/avatar_select.php');

$isSystemAdmin = $is_system_admin ?? false;
$canManageDevices = $isSystemAdmin || user_has_special_access($conDB, $empid ?? '', 'manage_device_monitor', $user_role ?? '', $user_type ?? '', $isSystemAdmin);
if (!$canManageDevices) {
    header('Location: dashboard.php');
    exit;
}

$thresholdMinutes = zk_get_offline_threshold_minutes($conDB);

$devices = [];
$result = mysqli_query($conDB, "SELECT * FROM zk_devices ORDER BY device_name ASC");
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $devices[] = $row;
    }
}
?>
<?php
$h = function ($v) { return htmlspecialchars((string)$v, ENT_QUOTES); };
$zk_count = ['online' => 0, 'offline' => 0];
foreach ($devices as $d) {
    $zk_count[$d['state'] === 'online' ? 'online' : 'offline']++;
}
?>
<!doctype html>
<html lang="<?= $current_lang ?? 'en' ?>" <?= ($is_rtl ?? false) ? 'dir="rtl"' : '' ?>>

<head>
    <meta charset="utf-8" />
    <title><?= $site_title ?> - <?= __('zk_devices', 'Biometric Devices') ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta content="Al-Mutlak" name="author" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />

    <link rel="shortcut icon" href="<?= get_setting($conDB, 'favicon') ?>">

    <link href="assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/icons.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/metismenu.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/style.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/style_dark.css" rel="stylesheet" type="text/css" />
    <link href="./plugins/datatables/dataTables.bootstrap4.min.css" rel="stylesheet" type="text/css" />
    <link href="./plugins/datatables/responsive.bootstrap4.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/smart_request.css?v=<?= @filemtime(__DIR__ . '/assets/css/smart_request.css') ?>" rel="stylesheet" type="text/css" />
    <script src="assets/js/modernizr.min.js"></script>
    <?php if ($is_rtl ?? false): ?>
        <link href="assets/css/style_rtl.css" rel="stylesheet" type="text/css" />
    <?php endif; ?>
    <style>
        .sr-page .sr-tiles.zk-tiles { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        .zk-avatar { width: 40px; height: 40px; border-radius: 10px; font-size: 20px; }
        .zk-avatar.is-offline { background: var(--tone-red-bg); color: var(--tone-red-fg); }
        .zk-avatar.is-online { background: var(--tone-green-bg); color: var(--tone-green-fg); }
        /* Sync ZKT now */
        .zk-sync-group { display: inline-flex; }
        .zk-sync-group .sr-btn:first-child { border-start-end-radius: 0; border-end-end-radius: 0; }
        .zk-sync-group .sr-btn + .sr-btn { border-start-start-radius: 0; border-end-start-radius: 0; margin-inline-start: -1px; padding: 0 10px; }
        .zk-sync-bar { display: flex; flex-wrap: wrap; align-items: center; gap: 8px 16px; padding: 12px 16px; margin-bottom: 20px; border-radius: 12px; border: 1px solid var(--sr-border); background: var(--sr-surface); box-shadow: var(--sr-shadow); font-size: 13px; color: var(--sr-text-2); }
        .zk-sync-bar .zk-sync-agent { display: inline-flex; align-items: center; gap: 6px; }
        .zk-sync-bar .zk-sync-msg { flex: 1 1 320px; min-width: 0; }
        .zk-sync-bar .zk-sync-msg i { margin-inline-end: 6px; }
        .zk-sync-bar .zk-sync-msg.is-busy { color: var(--tone-indigo-fg); }
        .zk-sync-bar .zk-sync-msg.is-ok { color: var(--tone-green-fg); }
        .zk-sync-bar .zk-sync-msg.is-bad { color: var(--tone-red-fg); }
        .zk-sync-bar .zk-sync-msg.is-warn { color: var(--tone-amber-fg); }
    </style>
    <script>
        window.lang = <?= json_encode($GLOBALS['translations'] ?? []) ?>;
    </script>
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
                <div class="container-fluid">

                    <div class="sr-head">
                        <div>
                            <h1><?= __('zk_devices', 'Biometric Devices') ?></h1>
                            <p><?= __('device_offline_note', 'A device is marked Offline after') ?> <?= (int)$thresholdMinutes ?> <?= __('minutes_of_silence', 'minute(s) of silence.') ?></p>
                        </div>
                        <div class="sr-head-actions">
                            <div class="zk-sync-group">
                                <button id="btn-zk-sync" type="button" class="sr-btn" title="<?= $h(__('zk_sync_now_hint', 'Ask the ZKT (BioTime) server to send its new punches now')) ?>"><i class="fa-solid fa-cloud-arrow-down"></i> <?= __('sync_zkt', 'Sync ZKT') ?></button>
                                <button id="btn-zk-resync" type="button" class="sr-btn" title="<?= $h(__('zk_full_resync_hint', 'Full re-sync: re-send the whole sync window (e.g. 180 days)')) ?>"><i class="fa-solid fa-clock-rotate-left"></i></button>
                            </div>
                            <button id="btn-add-device" type="button" class="sr-btn sr-btn-primary"><i class="mdi mdi-plus"></i> <?= __('add_device', 'Add Device') ?></button>
                        </div>
                    </div>

                    <div class="zk-sync-bar" id="zkSyncBar">
                        <span class="zk-sync-agent" id="zkSyncAgent"><span class="sr-pill tone-slate"><span class="sr-dot"></span><?= __('zkt_server', 'ZKT server') ?></span></span>
                        <span class="zk-sync-msg" id="zkSyncMsg"><?= __('loading', 'Loading') ?>...</span>
                    </div>

                    <div class="sr-tiles zk-tiles" id="zkTiles">
                        <button type="button" class="sr-tile active" data-key="">
                            <span class="sr-tile-label"><span class="sr-dot dot-all"></span><?= __('all', 'All') ?></span>
                            <span class="sr-tile-value"><?= count($devices) ?></span>
                        </button>
                        <button type="button" class="sr-tile" data-key="online">
                            <span class="sr-tile-label"><span class="sr-dot dot-green"></span><?= __('online', 'Online') ?></span>
                            <span class="sr-tile-value" id="zkOnlineCount"><?= $zk_count['online'] ?></span>
                        </button>
                        <button type="button" class="sr-tile" data-key="offline">
                            <span class="sr-tile-label"><span class="sr-dot dot-red"></span><?= __('offline', 'Offline') ?></span>
                            <span class="sr-tile-value" id="zkOfflineCount"><?= $zk_count['offline'] ?></span>
                        </button>
                    </div>

                    <div class="sr-card">
                        <div class="sr-toolbar">
                            <div class="sr-search">
                                <i class="mdi mdi-magnify"></i>
                                <input type="search" id="zkSearch" placeholder="<?= __('search') ?>..." autocomplete="off" aria-label="<?= __('search') ?>">
                            </div>
                            <div class="sr-toolbar-right">
                                <span class="sr-active-filter"><i class="mdi mdi-refresh"></i> <?= __('live_refresh_15s', 'State refreshes every 15 seconds') ?></span>
                            </div>
                        </div>

                        <div class="sr-table-wrap">
                            <table id="zk_devices_table" class="table sr-table dt-responsive nowrap" style="width: 100%;">
                                <thead>
                                    <tr>
                                        <th><?= __('device_name', 'Device Name') ?></th>
                                        <th><?= __('area', 'Area') ?></th>
                                        <th><?= __('device_ip', 'Device IP') ?></th>
                                        <th><?= __('pull_port', 'Socket Check Port') ?></th>
                                        <th><?= __('state', 'State') ?></th>
                                        <th><?= __('last_activity', 'Last Activity') ?></th>
                                        <th><?= __('transaction_qty', 'Transactions') ?></th>
                                        <th class="text-right"><?= __('action_header', 'Action') ?></th>
                                        <th>state</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($devices as $device):
                                        $on = ($device['state'] === 'online');
                                    ?>
                                        <tr data-serial="<?= $h($device['serial_number']) ?>">
                                            <td data-order="<?= $h($device['device_name']) ?>">
                                                <div class="sr-person">
                                                    <span class="sr-avatar zk-avatar js-device-avatar <?= $on ? 'is-online' : 'is-offline' ?>"><i class="mdi mdi-fingerprint"></i></span>
                                                    <div style="min-width: 0;">
                                                        <span class="sr-cell-title"><?= $h($device['device_name']) ?></span>
                                                        <span class="sr-cell-sub sr-mono"><?= $h($device['serial_number']) ?></span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><?= $device['area'] ? '<span class="sr-chip"><i class="mdi mdi-map-marker"></i>' . $h($device['area']) . '</span>' : '<span class="text-muted">&ndash;</span>' ?></td>
                                            <td class="sr-mono"><?= $h($device['device_ip'] ?: '–') ?></td>
                                            <td class="sr-mono"><?= $h($device['pull_host'] ?? '') ?><?= $device['pull_port'] ? ':' . (int)$device['pull_port'] : '' ?></td>
                                            <td class="js-device-state">
                                                <span class="sr-pill tone-<?= $on ? 'green' : 'red' ?>"><span class="sr-dot"></span><?= $h(__($device['state'], ucfirst($device['state']))) ?></span>
                                            </td>
                                            <td class="js-device-last-activity"><?= $h($device['last_activity'] ?? '-') ?></td>
                                            <td class="js-device-tx-qty"><span class="sr-chip"><?= (int)$device['transaction_qty'] ?></span></td>
                                            <td class="text-right">
                                                <div class="sr-actions">
                                                    <a href="javascript:void(0);" class="sr-open-btn btn-edit-device"
                                                        data-id="<?= (int)$device['id'] ?>"
                                                        data-name="<?= $h($device['device_name']) ?>"
                                                        data-area="<?= $h($device['area'] ?? '') ?>"
                                                        data-ip="<?= $h($device['device_ip'] ?? '') ?>"
                                                        data-pull-host="<?= $h($device['pull_host'] ?? '') ?>"
                                                        data-pull-port="<?= $h((string)($device['pull_port'] ?? '')) ?>">
                                                        <i class="mdi mdi-pencil"></i> <?= __('edit_link', 'Edit') ?>
                                                    </a>
                                                    <div class="btn-group dropdown">
                                                        <a href="javascript:void(0);" class="sr-more-btn dropdown-toggle arrow-none" data-toggle="dropdown" aria-expanded="false"><i class="mdi mdi-dots-vertical"></i></a>
                                                        <div class="dropdown-menu dropdown-menu-right">
                                                            <a class="dropdown-item text-danger deleteAjax" href="javascript:void(0);" data-id="<?= (int)$device['id'] ?>" data-tbl="zk_devices" data-file="0"><i class="fa fa-trash mr-2"></i><?= __('delete_link', 'Delete') ?></a>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="js-device-key"><?= $on ? 'online' : 'offline' ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>

                <footer class="footer">
                    <?= $site_footer ?? '' ?>
                </footer>
            </div>
        </div>
    </div>

    <script src="assets/js/jquery.min.js"></script>
    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/metisMenu.min.js"></script>
    <script src="assets/js/waves.js"></script>
    <script src="assets/js/jquery.slimscroll.js"></script>

    <script src="./plugins/datatables/jquery.dataTables.min.js"></script>
    <script src="./plugins/datatables/dataTables.bootstrap4.min.js"></script>
    <script src="./plugins/datatables/dataTables.responsive.min.js"></script>
    <script src="./plugins/datatables/responsive.bootstrap4.min.js"></script>

    <script src="assets/js/jquery.core.js"></script>
    <script src="assets/js/jquery.app.js?t=<?= time() ?>"></script>
    <script src="assets/js/sr_forms.js?v=<?= @filemtime(__DIR__ . '/assets/js/sr_forms.js') ?>"></script>

    <script>
    $(document).ready(function() {
        const F = window.SRForm, esc = F.esc;
        const KEY_COL = 8;

        const table = $('#zk_devices_table').DataTable({
            dom: 'rtip',
            pageLength: 15,
            order: [[0, 'asc']],
            columnDefs: [
                { targets: [KEY_COL], visible: false },
                { targets: [7], orderable: false }
            ],
            language: {
                info: `${__('showing')} _START_ ${__('to')} _END_ ${__('of')} _TOTAL_ ${__('entries')}`,
                infoEmpty: `${__('showing')} 0 ${__('to')} 0 ${__('of')} 0 ${__('entries')}`,
                infoFiltered: '',
                paginate: { first: __('first'), last: __('last'), next: '<i class="mdi mdi-chevron-right"></i>', previous: '<i class="mdi mdi-chevron-left"></i>' },
                emptyTable: `<div class="sr-empty"><i class="mdi mdi-fingerprint"></i>${__('no_data_available_in_table')}</div>`,
                zeroRecords: `<div class="sr-empty"><i class="mdi mdi-magnify"></i>${__('no_matching_records_found')}</div>`
            }
        });

        let activeKey = '';
        function applyKey() {
            table.column(KEY_COL).search(activeKey ? '^' + activeKey + '$' : '', true, false).draw(false);
        }
        $('#zkTiles').on('click', '.sr-tile', function() {
            $('#zkTiles .sr-tile').removeClass('active');
            $(this).addClass('active');
            activeKey = $(this).data('key') || '';
            applyKey();
        });
        $('#zkSearch').on('input', function() { table.search(this.value).draw(); });

        /* ---------- add / edit popup ---------- */
        function deviceFormHtml(d, isEdit) {
            return '<form id="zkForm" class="sr-page sr-form text-left" autocomplete="off" novalidate>' +
                F.section('mdi-fingerprint', __('device', 'Device'),
                    F.field({ col: 6, name: 'serial_number', id: 'zkSerialNumber', label: __('serial_number', 'Serial Number'), req: !isEdit,
                        ph: isEdit ? __('serial_locked_after_create', 'Locked after creation') : 'e.g. CKJW204960084',
                        attrs: isEdit ? ' disabled' : '',
                        hint: esc(__('device_serial_hint', "Must exactly match the device's own Serial Number - it's how the device identifies itself when it phones home.")) }) +
                    F.field({ col: 6, name: 'device_name', id: 'zkDeviceName', label: __('device_name', 'Device Name'), req: true, value: d.name || '' }) +
                    F.field({ col: 6, name: 'area', id: 'zkArea', label: __('area', 'Area'), value: d.area || '' }) +
                    F.field({ col: 6, name: 'device_ip', id: 'zkDeviceIp', label: __('device_ip', 'Device IP (LAN, informational only)'), value: d.ip || '' })
                ) +
                F.section('mdi-lan', __('socket_check', 'Socket check'),
                    F.field({ col: 7, name: 'pull_host', id: 'zkPullHost', label: __('pull_host', 'Socket Check Host'), value: d.pullHost || '185.137.245.28' }) +
                    F.field({ col: 5, name: 'pull_port', id: 'zkPullPort', type: 'number', label: __('pull_port', 'Socket Check Port'), value: d.pullPort || '',
                        ph: 'e.g. 14370', hint: esc(__('pull_port_hint', 'Leave blank to skip live socket state checking for this device.')) })
                ) +
            '</form>';
        }

        function openDeviceForm(d) {
            const isEdit = !!d.id;
            F.open({
                title: isEdit ? __('edit_device', 'Edit Device') : __('add_device', 'Add Device'),
                html: deviceFormHtml(d, isEdit),
                confirm: __('save', 'Save'),
                didOpen: function() { F.liveClear($('#zkForm')); },
                preConfirm: function() {
                    const msg = F.validate($('#zkForm'));
                    if (msg) { Swal.showValidationMessage(msg); return false; }
                    const data = {
                        action: isEdit ? 'update_device' : 'add_device',
                        device_name: $('#zkDeviceName').val(),
                        area: $('#zkArea').val(),
                        device_ip: $('#zkDeviceIp').val(),
                        pull_host: $('#zkPullHost').val(),
                        pull_port: $('#zkPullPort').val()
                    };
                    if (isEdit) data.id = d.id; else data.serial_number = $('#zkSerialNumber').val();
                    return $.ajax({ url: './includes/ajaxFile/zkDeviceAjax.php', type: 'POST', dataType: 'json', data: data })
                        .then(function(res) {
                            if (!res || res.status !== 'success') throw new Error((res && res.message) || 'Error');
                            return res;
                        })
                        .catch(function(err) {
                            Swal.showValidationMessage((err && err.message) || __('unexpected_error', 'Unexpected error.'));
                        });
                }
            }).then(function(result) {
                F.done(result.isConfirmed && result.value ? { isConfirmed: true, value: { type: 'success', message: result.value.message } } : null);
            });
        }

        $('#btn-add-device').on('click', function() { openDeviceForm({}); });

        $(document).on('click', '.btn-edit-device', function() {
            const $b = $(this);
            openDeviceForm({
                id: $b.data('id'), name: $b.data('name'), area: $b.data('area'), ip: $b.data('ip'),
                pullHost: $b.data('pull-host'), pullPort: $b.data('pull-port')
            });
        });

        // Live state polling - updates State/Last Activity/Transactions cells
        // in place, no full reload, so an admin watching this page sees
        // devices flip online/offline as punches arrive.
        function pollDeviceState() {
            $.ajax({
                url: './includes/ajaxFile/zkDeviceAjax.php',
                type: 'POST',
                dataType: 'json',
                data: { action: 'poll_state' },
            }).done(function(res) {
                if (res.status !== 'success') {
                    return;
                }
                let online = 0, offline = 0;
                table.rows().every(function() {
                    const $row = $(this.node());
                    const info = res.devices[$row.data('serial')];
                    if (!info) {
                        return;
                    }
                    const on = info.state === 'online';
                    on ? online++ : offline++;
                    const stateLabel = __(info.state, info.state.charAt(0).toUpperCase() + info.state.slice(1));
                    $row.find('.js-device-state').html(`<span class="sr-pill tone-${on ? 'green' : 'red'}"><span class="sr-dot"></span>${esc(stateLabel)}</span>`);
                    $row.find('.js-device-avatar').toggleClass('is-online', on).toggleClass('is-offline', !on);
                    $row.find('.js-device-last-activity').text(info.last_activity || '-');
                    $row.find('.js-device-tx-qty').html(`<span class="sr-chip">${esc(info.transaction_qty)}</span>`);
                    table.cell(this.index(), KEY_COL).data(on ? 'online' : 'offline');
                });
                $('#zkOnlineCount').text(online);
                $('#zkOfflineCount').text(offline);
                if (activeKey) applyKey();
            });
        }
        setInterval(pollDeviceState, 15000);

        /* ---------- Sync ZKT now ----------
           The button only queues a request; the agent on the ZKT (BioTime) server
           (zk_local_agent/zk_sync_poll.ps1) checks every minute, runs the sync and
           reports back. Polls fast while a request is active, slowly otherwise. */
        const AGENT_OK_SECONDS = 180;
        let syncTimer = null;
        let lastSyncStatus = null;

        function ago(sec) {
            if (sec === null || sec === undefined) return __('never', 'never');
            if (sec < 60) return sec + 's';
            if (sec < 3600) return Math.floor(sec / 60) + 'm';
            if (sec < 86400) return Math.floor(sec / 3600) + 'h ' + Math.floor((sec % 3600) / 60) + 'm';
            return Math.floor(sec / 86400) + 'd';
        }

        function renderSync(res) {
            const agentOk = res.agent_seen_ago !== null && res.agent_seen_ago <= AGENT_OK_SECONDS;
            $('#zkSyncAgent').html(`<span class="sr-pill tone-${agentOk ? 'green' : 'red'}" title="${esc(res.agent_seen || '')}"><span class="sr-dot"></span>${esc(__('zkt_server', 'ZKT server'))}: ${agentOk
                ? esc(__('connected', 'connected')) + ' · ' + esc(ago(res.agent_seen_ago))
                : esc(__('not_responding', 'not responding')) + ' · ' + esc(__('last_seen', 'last seen')) + ' ' + esc(ago(res.agent_seen_ago))}</span>`);

            const r = res.request;
            const $msg = $('#zkSyncMsg').removeClass('is-busy is-ok is-bad is-warn');
            const who = r && r.requested_name ? ' ' + __('by', 'by') + ' ' + r.requested_name : '';
            const kind = r && r.mode === 'resync' ? __('full_resync', 'Full re-sync') : __('sync', 'Sync');
            let html = '', cls = '';
            if (!r) {
                html = '<i class="fa-solid fa-circle-info"></i>' + esc(__('zk_sync_none', 'No manual sync yet. The ZKT server also sends new punches automatically every 15 minutes.'));
            } else if (r.status === 'pending') {
                cls = 'is-busy';
                html = '<i class="fa-solid fa-spinner fa-spin"></i>' + esc(kind + ' ' + __('requested', 'requested') + who + ' ' + __('at', 'at') + ' ' + r.requested_at + ' - ' +
                    (agentOk ? __('zk_sync_waiting', 'waiting for the ZKT server to pick it up (within a minute)...') : __('zk_sync_waiting_offline', 'the ZKT server has not checked in recently, it will run once it is back.')));
            } else if (r.status === 'running') {
                cls = 'is-busy';
                html = '<i class="fa-solid fa-spinner fa-spin"></i>' + esc(kind + ' ' + __('running_on_zkt', 'running on the ZKT server since') + ' ' + r.picked_at + '...');
            } else if (r.status === 'done') {
                cls = 'is-ok';
                const x = r.result || {};
                html = '<i class="fa-solid fa-circle-check"></i>' + esc(kind + ' ' + __('finished', 'finished') + ' ' + r.finished_at + who + ': ' +
                    (x.inserted || 0).toLocaleString() + ' ' + __('new_punches', 'new punch(es)') + ' (' + (x.sent || 0).toLocaleString() + ' ' + __('sent', 'sent') + ', ' +
                    (x.duplicates || 0).toLocaleString() + ' ' + __('already_stored', 'already stored') + ').');
            } else if (r.status === 'failed') {
                cls = 'is-bad';
                html = '<i class="fa-solid fa-circle-xmark"></i>' + esc(kind + ' ' + __('failed', 'failed') + ' ' + (r.finished_at || '') + ': ' + ((r.result && r.result.message) || __('see_agent_log', 'see zk_biotime_export.log on the ZKT server')));
            } else {
                cls = 'is-warn';
                html = '<i class="fa-solid fa-triangle-exclamation"></i>' + esc(kind + ' ' + __('requested', 'requested') + who + ' ' + __('at', 'at') + ' ' + r.requested_at + ' ' +
                    __('zk_sync_stale', 'was never completed - check that the ZKT server and its "ZKT Sync Poll" task are running.'));
            }
            $msg.addClass(cls).html(html);

            const busy = r && (r.status === 'pending' || r.status === 'running');
            $('#btn-zk-sync, #btn-zk-resync').prop('disabled', !!busy);

            // Finished since the last check -> toast
            if (lastSyncStatus && lastSyncStatus.id === (r && r.id) && ['pending', 'running'].includes(lastSyncStatus.status) && r && ['done', 'failed'].includes(r.status)) {
                Swal.fire({
                    toast: true, position: 'top-end', timer: 5000, showConfirmButton: false,
                    icon: r.status === 'done' ? 'success' : 'error',
                    title: r.status === 'done'
                        ? ((r.result && r.result.inserted) || 0) + ' ' + __('new_punches', 'new punch(es)') + ' ' + __('synced', 'synced')
                        : __('zk_sync_failed', 'ZKT sync failed')
                });
                pollDeviceState();
            }
            lastSyncStatus = r ? { id: r.id, status: r.status } : null;

            clearTimeout(syncTimer);
            syncTimer = setTimeout(loadSyncStatus, busy ? 3000 : 30000);
        }

        function loadSyncStatus() {
            $.post('includes/ajaxFile/zkDeviceAjax.php', { action: 'sync_status' }, null, 'json')
                .done(res => { if (res && res.status === 'success') renderSync(res); })
                .fail(() => { clearTimeout(syncTimer); syncTimer = setTimeout(loadSyncStatus, 30000); });
        }

        function requestSync(mode) {
            $('#btn-zk-sync, #btn-zk-resync').prop('disabled', true);
            $.post('includes/ajaxFile/zkDeviceAjax.php', { action: 'sync_request', mode: mode }, null, 'json')
                .done(res => {
                    if (!res || res.status !== 'success') {
                        Swal.fire({ icon: 'error', title: __('error', 'Error'), text: (res && res.message) || __('request_failed', 'Request failed'), allowOutsideClick: false });
                        loadSyncStatus();
                        return;
                    }
                    if (res.already) {
                        Swal.fire({ toast: true, position: 'top-end', timer: 4000, showConfirmButton: false, icon: 'info', title: __('zk_sync_already', 'A sync is already in progress') });
                    }
                    renderSync(res);
                })
                .fail(() => {
                    Swal.fire({ icon: 'error', title: __('error', 'Error'), text: __('request_failed', 'Request failed'), allowOutsideClick: false });
                    loadSyncStatus();
                });
        }

        $('#btn-zk-sync').on('click', () => requestSync('new'));
        $('#btn-zk-resync').on('click', function() {
            Swal.fire({
                icon: 'warning',
                title: __('full_resync', 'Full re-sync'),
                html: esc(__('zk_full_resync_confirm', 'Re-send ALL punches of the sync window (e.g. 180 days) from the ZKT server? Only needed after the attendance tables were emptied. Already stored punches are skipped. This can take several minutes.')),
                showCancelButton: true,
                confirmButtonText: __('full_resync', 'Full re-sync'),
                cancelButtonText: __('cancel', 'Cancel'),
                allowOutsideClick: false
            }).then(r => { if (r.isConfirmed) requestSync('resync'); });
        });

        loadSyncStatus();
    });
    </script>
</body>
</html>
