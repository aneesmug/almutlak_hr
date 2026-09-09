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
    <script src="assets/js/modernizr.min.js"></script>
    <?php if ($is_rtl ?? false): ?>
        <link href="assets/css/style_rtl.css" rel="stylesheet" type="text/css" />
    <?php endif; ?>
    <style>
        .device-state-dot { display: inline-block; width: 10px; height: 10px; border-radius: 50%; margin-right: 6px; }
        .device-state-online { background-color: #1abc9c; }
        .device-state-offline { background-color: #f1556c; }
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

            <div class="content">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-12">
                            <div class="card-box table-responsive">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h4 class="m-t-0 header-title"><?= __('zk_devices', 'Biometric Devices') ?></h4>
                                    <button id="btn-add-device" type="button" class="btn btn-primary btn-sm waves-effect waves-light">
                                        <i class="mdi mdi-plus-circle mr-2"></i><?= __('add_device', 'Add Device') ?>
                                    </button>
                                </div>
                                <p class="text-muted small">
                                    <?= __('device_offline_note', 'A device is marked Offline after') ?> <?= (int)$thresholdMinutes ?> <?= __('minutes_of_silence', 'minute(s) of silence.') ?>
                                </p>

                                <table id="zk_devices_table" class="table table-striped table-bordered dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                                    <thead>
                                        <tr>
                                            <th><?= __('device_name', 'Device Name') ?></th>
                                            <th><?= __('serial_number', 'Serial Number') ?></th>
                                            <th><?= __('area', 'Area') ?></th>
                                            <th><?= __('device_ip', 'Device IP') ?></th>
                                            <th><?= __('pull_port', 'Socket Check Port') ?></th>
                                            <th><?= __('state', 'State') ?></th>
                                            <th><?= __('last_activity', 'Last Activity') ?></th>
                                            <th><?= __('transaction_qty', 'Transactions') ?></th>
                                            <th width="60"><?= __('action_header', 'Action') ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($devices as $device): ?>
                                            <tr data-serial="<?= htmlspecialchars($device['serial_number'], ENT_QUOTES) ?>">
                                                <td><?= htmlspecialchars($device['device_name'], ENT_QUOTES) ?></td>
                                                <td><?= htmlspecialchars($device['serial_number'], ENT_QUOTES) ?></td>
                                                <td><?= htmlspecialchars($device['area'] ?? '', ENT_QUOTES) ?></td>
                                                <td><?= htmlspecialchars($device['device_ip'] ?? '', ENT_QUOTES) ?></td>
                                                <td><?= htmlspecialchars($device['pull_host'] ?? '', ENT_QUOTES) ?><?= $device['pull_port'] ? ':' . (int)$device['pull_port'] : '' ?></td>
                                                <td class="js-device-state">
                                                    <span class="device-state-dot device-state-<?= $device['state'] ?>"></span><?= ucfirst($device['state']) ?>
                                                </td>
                                                <td class="js-device-last-activity"><?= htmlspecialchars($device['last_activity'] ?? '-', ENT_QUOTES) ?></td>
                                                <td class="js-device-tx-qty"><?= (int)$device['transaction_qty'] ?></td>
                                                <td>
                                                    <div class='btn-group dropdown'>
                                                        <a href='javascript: void(0);' class='table-action-btn dropdown-toggle arrow-none btn btn-light btn-sm' data-toggle='dropdown' aria-expanded='false'><i class='mdi mdi-dots-horizontal'></i></a>
                                                        <div class='dropdown-menu dropdown-menu-right'>
                                                            <a class='dropdown-item text-dark btn-edit-device' href='javascript:void(0);'
                                                                data-id="<?= (int)$device['id'] ?>"
                                                                data-name="<?= htmlspecialchars($device['device_name'], ENT_QUOTES) ?>"
                                                                data-area="<?= htmlspecialchars($device['area'] ?? '', ENT_QUOTES) ?>"
                                                                data-ip="<?= htmlspecialchars($device['device_ip'] ?? '', ENT_QUOTES) ?>"
                                                                data-pull-host="<?= htmlspecialchars($device['pull_host'] ?? '', ENT_QUOTES) ?>"
                                                                data-pull-port="<?= htmlspecialchars((string)($device['pull_port'] ?? ''), ENT_QUOTES) ?>">
                                                                <i class='mdi mdi-pencil mr-2'></i><?= __('edit_link', 'Edit') ?>
                                                            </a>
                                                            <a class='dropdown-item text-danger deleteAjax' href='javascript:void(0);' data-id='<?= (int)$device['id'] ?>' data-tbl='zk_devices' data-file='0'>
                                                                <i class='fa fa-trash mr-2'></i><?= __('delete_link', 'Delete') ?>
                                                            </a>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
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

    <script>
    $(document).ready(function() {
        $('#zk_devices_table').DataTable({
            order: [[0, 'asc']],
            language: {
                search: `<span>${__('search')}:</span> _INPUT_`,
                searchPlaceholder: `${__('search')}...`,
                lengthMenu: `${__('show')} _MENU_ ${__('entries')}`,
                info: `${__('showing')} _START_ ${__('to')} _END_ ${__('of')} _TOTAL_ ${__('entries')}`,
                paginate: { first: __('first'), last: __('last'), next: __('next'), previous: __('previous') },
                emptyTable: __('no_data_available_in_table'),
                zeroRecords: __('no_matching_records_found'),
            }
        });

        function deviceFormHtml(name, area, ip, pullHost, pullPort) {
            return `
                <input type="hidden" id="zkDeviceId" value="">
                <div class="form-group text-left">
                    <label>${__('serial_number', 'Serial Number')} *</label>
                    <input type="text" id="zkSerialNumber" class="form-control" placeholder="e.g. CKJW204960084">
                    <small class="text-muted">${__('device_serial_hint', "Must exactly match the device's own Serial Number - it's how the device identifies itself when it phones home.")}</small>
                </div>
                <div class="form-group text-left">
                    <label>${__('device_name', 'Device Name')} *</label>
                    <input type="text" id="zkDeviceName" class="form-control" value="${name || ''}">
                </div>
                <div class="form-group text-left">
                    <label>${__('area', 'Area')}</label>
                    <input type="text" id="zkArea" class="form-control" value="${area || ''}">
                </div>
                <div class="form-group text-left">
                    <label>${__('device_ip', 'Device IP (LAN, informational only)')}</label>
                    <input type="text" id="zkDeviceIp" class="form-control" value="${ip || ''}">
                </div>
                <hr>
                <div class="form-group text-left">
                    <label>${__('pull_host', 'Socket Check Host')}</label>
                    <input type="text" id="zkPullHost" class="form-control" value="${pullHost || '212.118.124.212'}">
                </div>
                <div class="form-group text-left">
                    <label>${__('pull_port', 'Socket Check Port')}</label>
                    <input type="number" id="zkPullPort" class="form-control" value="${pullPort || ''}" placeholder="e.g. 14370 (router-forwarded to this device's port 4370)">
                    <small class="text-muted">${__('pull_port_hint', 'Leave blank to skip live socket state checking for this device.')}</small>
                </div>
            `;
        }

        $('#btn-add-device').on('click', function() {
            Swal.fire({
                title: __('add_device', 'Add Device'),
                html: deviceFormHtml('', '', '', '', ''),
                showCancelButton: true,
                confirmButtonColor: APP_COLORS.primary,
                cancelButtonColor: APP_COLORS.danger_dark,
                confirmButtonText: __('save', 'Save'),
                cancelButtonText: __('cancel'),
                showLoaderOnConfirm: true,
                allowOutsideClick: false,
                preConfirm: function() {
                    return new Promise(function(resolve, reject) {
                        $.ajax({
                            url: './includes/ajaxFile/zkDeviceAjax.php',
                            type: 'POST',
                            dataType: 'json',
                            data: {
                                action: 'add_device',
                                serial_number: $('#zkSerialNumber').val(),
                                device_name: $('#zkDeviceName').val(),
                                area: $('#zkArea').val(),
                                device_ip: $('#zkDeviceIp').val(),
                                pull_host: $('#zkPullHost').val(),
                                pull_port: $('#zkPullPort').val(),
                            },
                        }).done(function(res) {
                            if (res.status !== 'success') {
                                reject(res.message);
                                return;
                            }
                            resolve(res);
                        }).fail(function() {
                            reject(__('unexpected_error', 'Unexpected error.'));
                        });
                    });
                },
            }).then(function(result) {
                if (result.isConfirmed) {
                    location.reload();
                }
            });
        });

        $(document).on('click', '.btn-edit-device', function() {
            var id = $(this).data('id');
            var name = $(this).data('name');
            var area = $(this).data('area');
            var ip = $(this).data('ip');
            var pullHost = $(this).data('pull-host');
            var pullPort = $(this).data('pull-port');

            Swal.fire({
                title: __('edit_device', 'Edit Device'),
                html: deviceFormHtml(name, area, ip, pullHost, pullPort),
                showCancelButton: true,
                confirmButtonColor: APP_COLORS.primary,
                cancelButtonColor: APP_COLORS.danger_dark,
                confirmButtonText: __('save', 'Save'),
                cancelButtonText: __('cancel'),
                showLoaderOnConfirm: true,
                allowOutsideClick: false,
                didOpen: function() {
                    $('#zkSerialNumber').val('').prop('disabled', true).attr('placeholder', __('serial_locked_after_create', 'Locked after creation'));
                },
                preConfirm: function() {
                    return new Promise(function(resolve, reject) {
                        $.ajax({
                            url: './includes/ajaxFile/zkDeviceAjax.php',
                            type: 'POST',
                            dataType: 'json',
                            data: {
                                action: 'update_device',
                                id: id,
                                device_name: $('#zkDeviceName').val(),
                                area: $('#zkArea').val(),
                                device_ip: $('#zkDeviceIp').val(),
                                pull_host: $('#zkPullHost').val(),
                                pull_port: $('#zkPullPort').val(),
                            },
                        }).done(function(res) {
                            if (res.status !== 'success') {
                                reject(res.message);
                                return;
                            }
                            resolve(res);
                        }).fail(function() {
                            reject(__('unexpected_error', 'Unexpected error.'));
                        });
                    });
                },
            }).then(function(result) {
                if (result.isConfirmed) {
                    location.reload();
                }
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
                $('#zk_devices_table tbody tr').each(function() {
                    var serial = $(this).data('serial');
                    var info = res.devices[serial];
                    if (!info) {
                        return;
                    }
                    var $row = $(this);
                    var stateLabel = info.state.charAt(0).toUpperCase() + info.state.slice(1);
                    $row.find('.js-device-state').html('<span class="device-state-dot device-state-' + info.state + '"></span>' + stateLabel);
                    $row.find('.js-device-last-activity').text(info.last_activity || '-');
                    $row.find('.js-device-tx-qty').text(info.transaction_qty);
                });
            });
        }
        setInterval(pollDeviceState, 15000);
    });
    </script>
</body>
</html>
