<?php
require_once __DIR__ . '/includes/session_check.php';
require_once __DIR__ . '/includes/special_access_helper.php';
require_once __DIR__ . '/includes/zk_helpers.php';
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
                            <button id="btn-add-device" type="button" class="sr-btn sr-btn-primary"><i class="mdi mdi-plus"></i> <?= __('add_device', 'Add Device') ?></button>
                        </div>
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
    });
    </script>
</body>
</html>
