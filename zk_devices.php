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
        .zk-sync-bar .zk-sync-open { cursor: pointer; text-decoration: underline dotted; }
        /* Sync ZKT step-by-step modal */
        .zks-head { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 10px 14px; margin-bottom: 14px; border-radius: 10px; background: var(--sr-surface-2, #f6f7fb); border: 1px solid var(--sr-border, #e5e7eb); font-size: 13px; color: var(--sr-text-2, #4b5563); text-align: start; }
        .zks-head b { color: var(--sr-text, #111827); }
        .zks-elapsed { font-variant-numeric: tabular-nums; white-space: nowrap; }
        .zks-steps { list-style: none; margin: 0; padding: 0; text-align: start; }
        .zks-step { position: relative; display: flex; gap: 12px; padding: 0 0 14px; }
        .zks-step:not(:last-child)::before { content: ''; position: absolute; inset-inline-start: 13px; top: 30px; bottom: 2px; width: 2px; background: var(--sr-border, #e5e7eb); }
        .zks-step.is-done:not(:last-child)::before { background: var(--tone-green-fg, #16a34a); opacity: .35; }
        .zks-ico { flex: 0 0 28px; height: 28px; border-radius: 50%; display: grid; place-items: center; font-size: 13px; background: var(--sr-surface-2, #f1f5f9); color: var(--sr-text-3, #94a3b8); border: 1px solid var(--sr-border, #e5e7eb); }
        .zks-step.is-active .zks-ico { background: var(--tone-indigo-bg, #eef2ff); color: var(--tone-indigo-fg, #4f46e5); border-color: transparent; }
        .zks-step.is-done .zks-ico { background: var(--tone-green-bg, #dcfce7); color: var(--tone-green-fg, #16a34a); border-color: transparent; }
        .zks-step.is-error .zks-ico { background: var(--tone-red-bg, #fee2e2); color: var(--tone-red-fg, #dc2626); border-color: transparent; }
        .zks-step.is-warn .zks-ico { background: var(--tone-amber-bg, #fef3c7); color: var(--tone-amber-fg, #d97706); border-color: transparent; }
        .zks-body { flex: 1; min-width: 0; padding-top: 3px; }
        .zks-title { display: flex; justify-content: space-between; gap: 8px; font-size: 14px; font-weight: 600; color: var(--sr-text, #111827); }
        .zks-step.is-pending .zks-title { color: var(--sr-text-3, #94a3b8); font-weight: 500; }
        .zks-time { font-size: 12px; font-weight: 400; color: var(--sr-text-3, #94a3b8); font-variant-numeric: tabular-nums; }
        .zks-detail { margin-top: 2px; font-size: 12.5px; color: var(--sr-text-2, #4b5563); }
        .zks-step.is-error .zks-detail { color: var(--tone-red-fg, #dc2626); }
        .zks-step.is-warn .zks-detail { color: var(--tone-amber-fg, #d97706); }
        .zks-chips { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 6px; }
        .zks-chips .sr-chip { font-variant-numeric: tabular-nums; }
        .zks-result { margin-top: 4px; padding: 12px 14px; border-radius: 10px; font-size: 14px; text-align: start; }
        .zks-result.is-ok { background: var(--tone-green-bg, #dcfce7); color: var(--tone-green-fg, #16a34a); }
        .zks-result.is-bad { background: var(--tone-red-bg, #fee2e2); color: var(--tone-red-fg, #dc2626); }
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
           (zk_local_agent/zk_sync_poll.ps1) checks every few seconds, runs the sync,
           forwards every step (zk_sync_request.php action=progress) and reports back.
           The modal shows each step live; polls fast while a request is active. */
        const AGENT_OK_SECONDS = 180;
        let syncTimer = null;
        let lastSyncStatus = null;
        let lastSyncRes = null;
        let modalOpen = false;
        let elapsedTimer = null;

        function ago(sec) {
            if (sec === null || sec === undefined) return __('never', 'never');
            if (sec < 60) return sec + 's';
            if (sec < 3600) return Math.floor(sec / 60) + 'm';
            if (sec < 86400) return Math.floor(sec / 3600) + 'h ' + Math.floor((sec % 3600) / 60) + 'm';
            return Math.floor(sec / 86400) + 'd';
        }
        const num = v => (parseInt(v, 10) || 0).toLocaleString();
        const hms = dt => dt ? String(dt).substring(11, 19) : '';
        const parseDt = dt => dt ? Date.parse(String(dt).replace(' ', 'T')) : NaN;
        function clock(sec) {
            sec = Math.max(0, Math.floor(sec));
            const m = Math.floor(sec / 60), s = sec % 60;
            return (m >= 60 ? Math.floor(m / 60) + ':' + String(m % 60).padStart(2, '0') : m) + ':' + String(s).padStart(2, '0');
        }

        function renderSync(res) {
            lastSyncRes = res;
            const agentOk = res.agent_seen_ago !== null && res.agent_seen_ago <= AGENT_OK_SECONDS;
            $('#zkSyncAgent').html(`<span class="sr-pill tone-${agentOk ? 'green' : 'red'}" title="${esc(res.agent_seen || '')}"><span class="sr-dot"></span>${esc(__('zkt_server', 'ZKT server'))}: ${agentOk
                ? esc(__('connected', 'connected')) + ' · ' + esc(ago(res.agent_seen_ago))
                : esc(__('not_responding', 'not responding')) + ' · ' + esc(__('last_seen', 'last seen')) + ' ' + esc(ago(res.agent_seen_ago))}</span>`);

            const r = res.request;
            const $msg = $('#zkSyncMsg').removeClass('is-busy is-ok is-bad is-warn');
            const who = r && r.requested_name ? ' ' + __('by', 'by') + ' ' + r.requested_name : '';
            const kind = r && r.mode === 'resync' ? __('full_resync', 'Full re-sync') : __('sync', 'Sync');
            const details = ' <a href="#" class="zk-sync-open" data-open-sync>' + esc(__('show_steps', 'show steps')) + '</a>';
            let html = '', cls = '';
            if (!r) {
                html = '<i class="fa-solid fa-circle-info"></i>' + esc(__('zk_sync_none', 'No manual sync yet. The ZKT server also sends new punches automatically every 15 minutes.'));
            } else if (r.status === 'pending') {
                cls = 'is-busy';
                html = '<i class="fa-solid fa-spinner fa-spin"></i>' + esc(kind + ' ' + __('requested', 'requested') + who + ' ' + __('at', 'at') + ' ' + r.requested_at + ' - ' +
                    (agentOk ? __('zk_sync_waiting', 'waiting for the ZKT server to connect...') : __('zk_sync_waiting_offline', 'the ZKT server has not checked in recently, it will run once it is back.'))) + details;
            } else if (r.status === 'running') {
                cls = 'is-busy';
                html = '<i class="fa-solid fa-spinner fa-spin"></i>' + esc(kind + ' ' + __('running_on_zkt', 'running on the ZKT server since') + ' ' + r.picked_at + '...') + details;
            } else if (r.status === 'done') {
                cls = 'is-ok';
                const x = r.result || {};
                html = '<i class="fa-solid fa-circle-check"></i>' + esc(kind + ' ' + __('finished', 'finished') + ' ' + r.finished_at + who + ': ' +
                    num(x.inserted) + ' ' + __('new_punches', 'new punch(es)') + ' (' + num(x.sent) + ' ' + __('sent', 'sent') + ', ' +
                    num(x.duplicates) + ' ' + __('already_stored', 'already stored') + ').') + details;
            } else if (r.status === 'failed') {
                cls = 'is-bad';
                html = '<i class="fa-solid fa-circle-xmark"></i>' + esc(kind + ' ' + __('failed', 'failed') + ' ' + (r.finished_at || '') + ': ' + ((r.result && r.result.message) || __('see_agent_log', 'see zk_biotime_export.log on the ZKT server'))) + details;
            } else {
                cls = 'is-warn';
                html = '<i class="fa-solid fa-triangle-exclamation"></i>' + esc(kind + ' ' + __('requested', 'requested') + who + ' ' + __('at', 'at') + ' ' + r.requested_at + ' ' +
                    __('zk_sync_stale', 'was never completed - check that the ZKT server and its "ZKT Sync Poll" task are running.')) + details;
            }
            $msg.addClass(cls).html(html);

            const busy = !!(r && (r.status === 'pending' || r.status === 'running'));
            $('#btn-zk-sync, #btn-zk-resync').prop('disabled', busy);

            const finishedNow = lastSyncStatus && r && lastSyncStatus.id === r.id && ['pending', 'running'].includes(lastSyncStatus.status) && ['done', 'failed'].includes(r.status);
            if (modalOpen) {
                renderSyncModal(res);
            } else if (finishedNow) {
                // Modal was sent to the background -> toast
                Swal.fire({
                    toast: true, position: 'top-end', timer: 5000, showConfirmButton: false,
                    icon: r.status === 'done' ? 'success' : 'error',
                    title: r.status === 'done'
                        ? num(r.result && r.result.inserted) + ' ' + __('new_punches', 'new punch(es)') + ' ' + __('synced', 'synced')
                        : __('zk_sync_failed', 'ZKT sync failed')
                });
            }
            if (finishedNow) pollDeviceState();
            lastSyncStatus = r ? { id: r.id, status: r.status } : null;

            clearTimeout(syncTimer);
            syncTimer = setTimeout(loadSyncStatus, busy ? (modalOpen ? 1500 : 3000) : 30000);
        }

        /* ---- Step-by-step modal ----
           Agent stages: starting > db_connect > db_ok > devices > (fetch > upload) x batches > finishing */
        const STAGE_ORDER = ['starting', 'db_connect', 'db_ok', 'devices', 'fetch', 'upload', 'finishing'];
        function stageIdx(stage) { const i = STAGE_ORDER.indexOf(stage); return i < 0 ? 0 : i; }

        function buildSteps(res) {
            const r = res.request || {};
            const p = r.progress || {};
            const t = r.stage_times || {};
            const agentOk = res.agent_seen_ago !== null && res.agent_seen_ago <= AGENT_OK_SECONDS;
            const st = r.status;
            const started = !!r.picked_at;
            const failed = st === 'failed' || (st === 'stale' && started);
            const idx = !started ? -1 : (st === 'done' ? STAGE_ORDER.length : stageIdx(p.stage));
            const batch = parseInt(p.batch, 10) || 0;
            // state of a step covering stages [from..to]
            const stateFor = (from, to) => {
                if (!started) return 'pending';
                if (idx > to) return 'done';
                if (idx >= from) return failed ? 'error' : 'active';
                return 'pending';
            };
            const steps = [];

            // 1. Request queued on the live app
            steps.push({
                state: 'done', title: __('zks_step_request', 'Sync request sent'), time: hms(r.requested_at),
                detail: (r.mode === 'resync' ? __('full_resync', 'Full re-sync') : __('zks_new_only', 'New punches only')) + (r.requested_name ? ' · ' + r.requested_name : '')
            });

            // 2. ZKT server connects and takes the request
            const s2 = { icon: 'fa-plug', title: __('zks_step_connect', 'Connecting to ZKT server'), time: hms(r.picked_at) };
            if (started) {
                Object.assign(s2, { state: 'done', title: __('zks_step_connected', 'Connected to ZKT server'), detail: __('zks_connected_detail', 'ZKT server accepted the request') });
            } else if (st === 'stale') {
                Object.assign(s2, { state: 'error', detail: __('zks_no_answer', 'The ZKT server did not answer. Check that the server is on and its "ZKT Sync Poll" task is running.') });
            } else {
                const busyAgo = r.agent_busy_at && res.now ? (parseDt(res.now) - parseDt(r.agent_busy_at)) / 1000 : null;
                if (busyAgo !== null && busyAgo <= 30) {
                    Object.assign(s2, { state: 'warn', detail: __('zks_agent_busy', 'ZKT server is finishing its scheduled sync - yours starts right after.') });
                } else if (agentOk) {
                    Object.assign(s2, { state: 'active', detail: __('zks_waiting', 'Waiting for the ZKT server to pick up the request...') + ' (' + __('last_seen', 'last seen') + ' ' + ago(res.agent_seen_ago) + ')' });
                } else {
                    Object.assign(s2, { state: 'warn', detail: __('zks_offline', 'ZKT server is not responding') + ' (' + __('last_seen', 'last seen') + ' ' + ago(res.agent_seen_ago) + '). ' + __('zks_offline_2', 'The sync starts as soon as it is back online.') });
                }
            }
            steps.push(s2);

            // 3. BioTime database (on the local server)
            steps.push({
                icon: 'fa-database', title: __('zks_step_db', 'Connecting to BioTime database'), time: hms(t.db_connect),
                state: stateFor(0, 1),
                detail: idx > 1 && p.sync_days ? __('connected', 'connected') + ' · ' + __('zks_window', 'sync window') + ' ' + p.sync_days + ' ' + __('days', 'days') + (p.window_from ? ' (' + __('from', 'from') + ' ' + p.window_from + ')' : '') : ''
            });

            // 4. Device status
            steps.push({
                icon: 'fa-fingerprint', title: __('zks_step_devices', 'Reading device status'), time: hms(t.devices || t.db_ok),
                state: stateFor(2, 2),
                detail: p.devices !== undefined && idx > 2 ? num(p.devices) + ' ' + __('devices', 'device(s)') : ''
            });

            // 5 + 6. Fetch / upload loop, batch by batch
            let fetchState = stateFor(3, 4), upState = stateFor(5, 5);
            if (started && idx === 5) fetchState = 'done';   // batch read, now uploading
            if (started && idx === 4 && batch > 1) upState = failed ? 'error' : 'active';   // between batches
            steps.push({
                icon: 'fa-server', title: __('zks_step_fetch', 'Fetching punches from local server'), time: hms(t.fetch),
                state: fetchState,
                detail: batch ? __('batch', 'Batch') + ' ' + batch + ' · ' + num(p.fetched) + ' ' + __('zks_read', 'punch(es) read') +
                    (p.batch_size ? ' · ' + num(p.batch_size) + ' ' + __('zks_per_batch', 'per batch') : '') : ''
            });
            steps.push({
                icon: 'fa-cloud-arrow-up', title: __('zks_step_upload', 'Uploading to live app'), time: hms(t.upload),
                state: upState,
                detail: (idx === 5 && p.message ? p.message : '') + (p.last_time ? (idx === 5 && p.message ? ' · ' : '') + __('up_to', 'up to') + ' ' + p.last_time : ''),
                chips: t.upload ? [
                    [__('sent', 'sent'), num(p.sent)],
                    [__('new', 'new'), num(p.inserted)],
                    [__('already_stored', 'already stored'), num(p.duplicates)]
                ] : null
            });

            // 7. Result
            const fin = { icon: 'fa-flag-checkered', title: __('zks_step_finish', 'Finished'), time: hms(r.finished_at), state: 'pending' };
            if (st === 'done') {
                fin.state = 'done';
            } else if (st === 'failed') {
                Object.assign(fin, { state: 'error', title: __('zks_step_failed', 'Sync failed'), detail: (r.result && r.result.message) || __('see_agent_log', 'see zk_biotime_export.log on the ZKT server') });
            } else if (st === 'stale' && started) {
                Object.assign(fin, { state: 'error', title: __('zks_step_failed', 'Sync failed'), detail: __('zks_lost', 'Lost contact with the ZKT server during the sync.') });
            } else if (idx === 6) {
                fin.state = 'active';
            }
            steps.push(fin);
            return steps;
        }

        function stepHtml(s) {
            const icon = s.state === 'done' ? 'fa-check' : s.state === 'error' ? 'fa-xmark' : s.state === 'active' ? 'fa-spinner fa-spin' : s.state === 'warn' ? 'fa-hourglass-half' : (s.icon || 'fa-circle');
            const chips = s.chips ? '<div class="zks-chips">' + s.chips.map(c => `<span class="sr-chip">${esc(c[0])}: <b>${esc(c[1])}</b></span>`).join('') + '</div>' : '';
            return `<li class="zks-step is-${s.state}">
                <span class="zks-ico"><i class="fa-solid ${icon}"></i></span>
                <div class="zks-body">
                    <div class="zks-title"><span>${esc(s.title)}</span><span class="zks-time">${esc(s.time || '')}</span></div>
                    ${s.detail ? `<div class="zks-detail">${esc(s.detail)}</div>` : ''}${chips}
                </div></li>`;
        }

        function renderSyncModal(res) {
            const r = res.request;
            if (!r || !$('#zksSteps').length) return;
            $('#zksSteps').html(buildSteps(res).map(stepHtml).join(''));
            $('#zksMode').text(r.mode === 'resync' ? __('full_resync', 'Full re-sync') : __('zks_new_only', 'New punches only'));

            const ended = ['done', 'failed', 'stale'].includes(r.status);
            let result = '';
            if (r.status === 'done') {
                const x = r.result || {};
                result = `<div class="zks-result is-ok"><i class="fa-solid fa-circle-check"></i> <b>${esc(num(x.inserted))}</b> ${esc(__('new_punches', 'new punch(es)'))} · ${esc(num(x.sent))} ${esc(__('sent', 'sent'))} · ${esc(num(x.duplicates))} ${esc(__('already_stored', 'already stored'))}</div>`;
            } else if (r.status === 'failed' || r.status === 'stale') {
                result = `<div class="zks-result is-bad"><i class="fa-solid fa-circle-xmark"></i> ${esc(__('zk_sync_failed', 'ZKT sync failed'))}</div>`;
            }
            $('#zksResult').html(result);

            // Elapsed: based on the server clock, ticks locally between polls
            const startMs = parseDt(r.requested_at), nowMs = parseDt(res.now);
            clearInterval(elapsedTimer);
            if (!isNaN(startMs) && !isNaN(nowMs)) {
                if (ended) {
                    const endMs = parseDt(r.finished_at || (r.progress && r.progress.at));
                    $('#zksElapsed').text(clock(((isNaN(endMs) ? nowMs : endMs) - startMs) / 1000));
                } else {
                    const base = (nowMs - startMs) / 1000, t0 = Date.now();
                    const tick = () => $('#zksElapsed').text(clock(base + (Date.now() - t0) / 1000));
                    tick();
                    elapsedTimer = setInterval(tick, 1000);
                }
            }

            $(Swal.getTitle()).text(r.status === 'done' ? __('zk_sync_done', 'ZKT sync complete') : r.status === 'failed' || r.status === 'stale' ? __('zk_sync_failed', 'ZKT sync failed') : __('sync_zkt', 'Sync ZKT'));
            $(Swal.getConfirmButton()).text(ended ? __('close', 'Close') : __('run_in_background', 'Run in background'));
        }

        function openSyncModal() {
            if (modalOpen) return;
            modalOpen = true;
            Swal.fire({
                title: __('sync_zkt', 'Sync ZKT'),
                html: `<div class="zks-head"><span><i class="fa-solid fa-cloud-arrow-down"></i> <b id="zksMode"></b></span><span class="zks-elapsed"><i class="fa-regular fa-clock"></i> <span id="zksElapsed">0:00</span></span></div>
                       <ol class="zks-steps" id="zksSteps"></ol><div id="zksResult"></div>`,
                width: 580,
                confirmButtonText: __('run_in_background', 'Run in background'),
                allowOutsideClick: false,
                didOpen: () => {
                    if (lastSyncRes) renderSyncModal(lastSyncRes);
                    clearTimeout(syncTimer);
                    loadSyncStatus();
                },
                willClose: () => {
                    modalOpen = false;
                    clearInterval(elapsedTimer);
                }
            });
        }

        function loadSyncStatus() {
            $.post('includes/ajaxFile/zkDeviceAjax.php', { action: 'sync_status' }, null, 'json')
                .done(res => { if (res && res.status === 'success') renderSync(res); })
                .fail(() => { clearTimeout(syncTimer); syncTimer = setTimeout(loadSyncStatus, modalOpen ? 3000 : 30000); });
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
                    lastSyncRes = res;
                    openSyncModal();   // also when one was already running: shows its steps
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
        $(document).on('click', '[data-open-sync]', function(e) {
            e.preventDefault();
            openSyncModal();
        });

        loadSyncStatus();
    });
    </script>
</body>
</html>
