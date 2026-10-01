<?php
// Cars list. Same look as the Smart Request pages (assets/css/smart_request.css).
// Add / edit forms: assets/js/car_forms.js (addCarFunc, .editCarAttr); delete: .deleteAjax in jquery.app.js.
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/session_check.php';
require_once __DIR__ . '/includes/special_access_helper.php';
$can_add_car = !empty($is_system_admin) || user_has_special_access($conDB, $empid ?? '', 'cars_add', $user_role ?? '', $user_type ?? '', $is_system_admin ?? false);
$can_edit_car = !empty($is_system_admin) || user_has_special_access($conDB, $empid ?? '', 'cars_edit', $user_role ?? '', $user_type ?? '', $is_system_admin ?? false);
$can_delete_car = !empty($is_system_admin) || user_has_special_access($conDB, $empid ?? '', 'cars_delete', $user_role ?? '', $user_type ?? '', $is_system_admin ?? false);
$query = mysqli_query($conDB, "SELECT * FROM `admin_login` WHERE `id_iqama`='" . $username . "'");
if (mysqli_num_rows($query) == 1) {
    include("./includes/avatar_select.php");

    $doc_sql = function ($type) {
        return "(SELECT `d`.`exp_date` FROM `cars_docu` `d` WHERE `d`.`car_id` = `cars`.`id` AND `d`.`doc_type` = '" . $type . "' ORDER BY `d`.`id` DESC LIMIT 1)";
    };
    $sql_cars = "SELECT
        `cars`.*, `car_maker`.`maker` AS `maker_label`, `car_model`.`model` AS `model_label`, `car_model`.`id` AS `mdid`, `car_maker`.`id` AS `mkid`,
        `drv`.`car_user` AS `driver_id`, `drv`.`rcv_date` AS `driver_since`, `emp`.`name` AS `driver_name`,
        " . $doc_sql('Licence') . " AS `exp_licence`,
        " . $doc_sql('Insurance') . " AS `exp_insurance`,
        " . $doc_sql('MVPI') . " AS `exp_mvpi`
    FROM `cars`
    LEFT JOIN `car_maker` ON `car_maker`.`id` = `cars`.`maker_name`
    LEFT JOIN `car_model` ON `car_model`.`id` = `cars`.`model`
    LEFT JOIN `cars_drv` `drv` ON `drv`.`id` = (SELECT `x`.`id` FROM `cars_drv` `x` WHERE `x`.`car_id` = `cars`.`id` AND `x`.`status` = '1' ORDER BY `x`.`id` DESC LIMIT 1)
    LEFT JOIN `employees` `emp` ON `emp`.`emp_id` = `drv`.`car_user`
    ORDER BY `cars`.`id` DESC";
    $query_cars = mysqli_query($conDB, $sql_cars);

    $today = strtotime(date('Y-m-d'));
    // Days left on a document + its tone (same thresholds as view_car.php).
    $doc_state = function ($exp) use ($today) {
        if (empty($exp)) return ['days' => null, 'tone' => 'slate'];
        $days = (int)floor((strtotime($exp) - $today) / 86400);
        return ['days' => $days, 'tone' => $days < 7 ? 'red' : ($days <= 30 ? 'amber' : 'green')];
    };

    $cars = [];
    $count = ['active' => 0, 'inactive' => 0, 'driver' => 0, 'docwarn' => 0];
    while ($query_cars && ($rec = mysqli_fetch_assoc($query_cars))) {
        $rec['docs'] = [
            'Licence' => $doc_state($rec['exp_licence']),
            'Insurance' => $doc_state($rec['exp_insurance']),
            'MVPI' => $doc_state($rec['exp_mvpi']),
        ];
        $rec['exp'] = ['Licence' => $rec['exp_licence'], 'Insurance' => $rec['exp_insurance'], 'MVPI' => $rec['exp_mvpi']];
        $rec['is_active'] = ($rec['status'] == '1');
        $rec['has_driver'] = !empty($rec['driver_id']);
        $rec['doc_warn'] = false;
        foreach ($rec['docs'] as $d) {
            if ($d['days'] === null || $d['days'] <= 30) { $rec['doc_warn'] = true; }
        }
        $count[$rec['is_active'] ? 'active' : 'inactive']++;
        if ($rec['is_active'] && $rec['has_driver']) $count['driver']++;
        if ($rec['is_active'] && $rec['doc_warn']) $count['docwarn']++;
        $cars[] = $rec;
    }

    $h = function ($v) { return htmlspecialchars((string)$v, ENT_QUOTES); };
    $short_name = function ($name) {
        $p = preg_split('/\s+/', trim((string)$name), -1, PREG_SPLIT_NO_EMPTY);
        return implode(' ', array_slice($p, 0, 2));
    };
    $doc_short = ['Licence' => __('licence', 'Licence'), 'Insurance' => __('insurance', 'Insurance'), 'MVPI' => __('mvpi', 'MVPI')];
?>
    <!doctype html>
    <html lang="<?= $current_lang ?? 'en' ?>" <?= ($is_rtl ?? false) ? 'dir="rtl"' : '' ?>>

    <head>
        <meta charset="utf-8" />
        <title><?= $site_title ?> - <?= __('all_cars', 'All Cars') ?></title>
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
        <meta content="Anees Afzal" name="author" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />

        <link rel="shortcut icon" href="<?=get_setting($conDB, 'favicon')?>">

        <!-- Plugins css -->
        <link href="./plugins/bootstrap-datepicker/css/bootstrap-datepicker.min.css" rel="stylesheet">
        <link href="./plugins/select2/css/select2.min.css" rel="stylesheet" type="text/css" />
        <!-- DataTables -->
        <link href="./plugins/datatables/dataTables.bootstrap4.min.css" rel="stylesheet" type="text/css" />
        <link href="./plugins/datatables/buttons.bootstrap4.min.css" rel="stylesheet" type="text/css" />
        <link href="./plugins/datatables/responsive.bootstrap4.min.css" rel="stylesheet" type="text/css" />

        <!-- App css -->
        <link href="assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
        <link href="assets/css/icons.css" rel="stylesheet" type="text/css" />
        <link href="assets/css/metismenu.min.css" rel="stylesheet" type="text/css" />
        <link href="assets/css/style.css" rel="stylesheet" type="text/css" />
        <link href="assets/css/style_dark.css" rel="stylesheet" type="text/css" />
        <link href="assets/css/smart_request.css?v=<?= @filemtime(__DIR__ . '/assets/css/smart_request.css') ?>" rel="stylesheet" type="text/css" />
        <script src="assets/js/modernizr.min.js"></script>
        <style>
            .sr-page .sr-tiles.car-tiles { grid-template-columns: repeat(5, minmax(0, 1fr)); }
            @media (max-width: 991px) { .sr-page .sr-tiles.car-tiles { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
            @media (max-width: 575px) { .sr-page .sr-tiles.car-tiles { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
            .car-avatar { width: 40px; height: 40px; border-radius: 10px; font-size: 20px; }
            .car-avatar.is-off { background: var(--sr-surface-3); color: var(--sr-muted); }
            .sr-page table.sr-table tbody tr.is-inactive td { opacity: .62; }
            .sr-page table.sr-table tbody tr.is-inactive:hover td { opacity: 1; }
            .car-type-select {
                height: 34px; padding: 0 30px 0 12px; border-radius: 8px; font-size: 12px; font-weight: 600;
                border: 1px solid var(--sr-border-strong); background-color: var(--sr-surface); color: var(--sr-text-2);
            }
        </style>
        <?php if ($is_rtl): ?>
            <link href="assets/css/style_rtl.css" rel="stylesheet" type="text/css" />
        <?php endif; ?>
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
                            <span><img src="<?=get_setting($conDB, 'logo')?>" alt="" height="22"></span>
                            <i><img src="<?=get_setting($conDB, 'white_logo')?>" alt="" height="28"></i>
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
                                <h1><?= __('all_registered_cars', 'Company cars') ?></h1>
                                <p><?= __('cars_subtitle', 'Vehicles with their drivers, documents and maintenance.') ?></p>
                            </div>
                            <div class="sr-head-actions">
                                <?php if ($can_add_car): ?>
                                    <button type="button" class="sr-btn sr-btn-primary" onclick="addCarFunc()"><i class="fa fa-plus"></i> <?= __('add_car', 'Add car') ?></button>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="sr-tiles car-tiles" id="carTiles">
                            <button type="button" class="sr-tile" data-key="">
                                <span class="sr-tile-label"><span class="sr-dot dot-all"></span><?= __('all', 'All') ?></span>
                                <span class="sr-tile-value"><?= count($cars) ?></span>
                            </button>
                            <button type="button" class="sr-tile active" data-key="active">
                                <span class="sr-tile-label"><span class="sr-dot dot-green"></span><?= __('active', 'Active') ?></span>
                                <span class="sr-tile-value"><?= $count['active'] ?></span>
                            </button>
                            <button type="button" class="sr-tile" data-key="inactive">
                                <span class="sr-tile-label"><span class="sr-dot dot-slate"></span><?= __('inactive', 'Inactive') ?></span>
                                <span class="sr-tile-value"><?= $count['inactive'] ?></span>
                            </button>
                            <button type="button" class="sr-tile" data-key="driver">
                                <span class="sr-tile-label"><span class="sr-dot dot-sky"></span><?= __('with_driver', 'With driver') ?></span>
                                <span class="sr-tile-value"><?= $count['driver'] ?></span>
                            </button>
                            <button type="button" class="sr-tile" data-key="docwarn">
                                <span class="sr-tile-label"><span class="sr-dot dot-amber"></span><?= __('documents_due', 'Documents due') ?></span>
                                <span class="sr-tile-value"><?= $count['docwarn'] ?></span>
                            </button>
                        </div>

                        <div class="sr-card">
                            <div class="sr-toolbar">
                                <div class="sr-search">
                                    <i class="mdi mdi-magnify"></i>
                                    <input type="search" id="carSearch" placeholder="<?= __('search') ?>..." autocomplete="off" aria-label="<?= __('search') ?>">
                                </div>
                                <div class="sr-toolbar-right">
                                    <select id="carType" class="car-type-select" aria-label="<?= __('type') ?>">
                                        <option value=""><?= __('type') ?>: <?= __('all', 'All') ?></option>
                                        <?php foreach (array_unique(array_column($cars, 'type')) as $tp): if ($tp === '' || $tp === null) continue; ?>
                                            <option value="<?= $h($tp) ?>"><?= $h(__(strtolower(str_replace(' ', '_', $tp)), $tp)) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div id="carExportButtons"></div>
                                </div>
                            </div>

                            <div class="sr-table-wrap">
                                <table id="carsTable" class="table sr-table dt-responsive nowrap" style="width: 100%;">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th><?= __('car', 'Car') ?></th>
                                            <th><?= __('plate_no') ?></th>
                                            <th><?= __('type') ?></th>
                                            <th><?= __('driver_label', 'Driver') ?></th>
                                            <th><?= __('documents', 'Documents') ?></th>
                                            <th><?= __('status') ?></th>
                                            <th class="text-right"><?= __('action') ?></th>
                                            <th>key</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($cars as $rec):
                                            $id = (int)$rec['id'];
                                            $title = trim($rec['maker_label'] . ' ' . $rec['model_label']);
                                            $plate = explode('-', (string)$rec['plate_no'], 2);
                                            $type_label = __(strtolower(str_replace(' ', '_', (string)$rec['type'])), (string)$rec['type']);
                                            $keys = [$rec['is_active'] ? 'active' : 'inactive'];
                                            if ($rec['is_active'] && $rec['has_driver']) $keys[] = 'driver';
                                            if ($rec['is_active'] && $rec['doc_warn']) $keys[] = 'docwarn';
                                            $doc_export = [];
                                        ?>
                                            <tr class="<?= $rec['is_active'] ? '' : 'is-inactive' ?>" data-href="./view_car.php?id=<?= $id ?>">
                                                <td><?= $id ?></td>
                                                <td data-order="<?= $h($title) ?>" data-export="<?= $h($title . ' (' . $rec['made_year'] . ')') ?>">
                                                    <div class="sr-person">
                                                        <span class="sr-avatar car-avatar <?= $rec['is_active'] ? '' : 'is-off' ?>"><i class="mdi mdi-car"></i></span>
                                                        <div style="min-width: 0;">
                                                            <span class="sr-cell-title"><?= $h($title ?: '-') ?></span>
                                                            <span class="sr-cell-sub"><i class="mdi mdi-calendar"></i><?= $h($rec['made_year']) ?><?php if (!empty($rec['remarks'])): ?> &middot; <?= $h(mb_strimwidth($rec['remarks'], 0, 40, '…')) ?><?php endif; ?></span>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td data-export="<?= $h($rec['plate_no']) ?>">
                                                    <span class="car-plate ad-keep"><span><?= $h($plate[0] ?? '') ?></span><span><?= $h($plate[1] ?? '') ?></span></span>
                                                </td>
                                                <td data-search="<?= $h($rec['type']) ?>"><span class="sr-chip"><?= $h($type_label) ?></span></td>
                                                <td data-export="<?= $h($rec['has_driver'] ? $rec['driver_name'] : '') ?>">
                                                    <?php if ($rec['has_driver']): ?>
                                                        <span class="sr-person-name"><?= $h($short_name($rec['driver_name']) ?: $rec['driver_id']) ?></span>
                                                        <span class="sr-cell-sub"><i class="mdi mdi-calendar-check"></i><?= $rec['driver_since'] ? date('d M Y', strtotime($rec['driver_since'])) : '' ?></span>
                                                    <?php else: ?>
                                                        <span class="text-muted"><?= __('no_driver_text', 'No driver') ?></span>
                                                    <?php endif; ?>
                                                </td>
                                                <?php ob_start(); ?>
                                                <span class="car-docs">
                                                    <?php foreach ($rec['docs'] as $type => $d):
                                                        if ($d['days'] === null) { $txt = '—'; $tip = __('no_document', 'No document'); }
                                                        elseif ($d['days'] < 0) { $txt = __('expired', 'Expired'); $tip = date('d M Y', strtotime($rec['exp'][$type])); }
                                                        else { $txt = $d['days'] . 'd'; $tip = date('d M Y', strtotime($rec['exp'][$type])); }
                                                        $doc_export[] = $doc_short[$type] . ': ' . ($d['days'] === null ? '-' : $rec['exp'][$type]);
                                                    ?>
                                                        <span class="sr-pill sr-pill-xs tone-<?= $d['tone'] ?>" title="<?= $h($doc_short[$type] . ' · ' . $tip) ?>"><b><?= $h($doc_short[$type]) ?></b> <?= $h($txt) ?></span>
                                                    <?php endforeach; ?>
                                                </span>
                                                <?php $docs_html = ob_get_clean(); ?>
                                                <td data-export="<?= $h(implode(', ', $doc_export)) ?>"><?= $docs_html ?></td>
                                                <td>
                                                    <?= $rec['is_active']
                                                        ? '<span class="sr-pill tone-green"><span class="sr-dot"></span>' . __('active', 'Active') . '</span>'
                                                        : '<span class="sr-pill tone-slate"><span class="sr-dot"></span>' . __('inactive', 'Inactive') . '</span>' ?>
                                                </td>
                                                <td class="text-right">
                                                    <div class="sr-actions">
                                                        <a href="./view_car.php?id=<?= $id ?>" class="sr-open-btn"><i class="mdi mdi-eye-outline"></i> <?= __('open') ?></a>
                                                        <?php if ($can_edit_car || $can_delete_car): ?>
                                                            <div class="btn-group dropdown">
                                                                <a href="javascript:void(0);" class="sr-more-btn dropdown-toggle arrow-none" data-toggle="dropdown" aria-expanded="false"><i class="mdi mdi-dots-vertical"></i></a>
                                                                <div class="dropdown-menu dropdown-menu-right">
                                                                    <?php if ($can_edit_car): ?>
                                                                        <a href="javascript:void(0);" class="dropdown-item editCarAttr" data-id="<?= $id ?>" data-maker_name="<?= $h($rec['mkid']) ?>" data-model="<?= $h($rec['mdid']) ?>" data-made_year="<?= $h($rec['made_year']) ?>" data-plate_no="<?= $h($rec['plate_no']) ?>" data-type="<?= $h($rec['type']) ?>" data-remarks="<?= $h($rec['remarks']) ?>" data-status="<?= $h($rec['status']) ?>"><i class="mdi mdi-pencil mr-2"></i><?= __('edit') ?></a>
                                                                    <?php endif; ?>
                                                                    <?php if ($can_delete_car): ?>
                                                                        <a href="javascript:void(0);" class="dropdown-item text-danger deleteAjax" data-id="<?= $id ?>" data-tbl="cars" data-file="0"><i class="fa fa-trash mr-2"></i><?= __('delete') ?></a>
                                                                    <?php endif; ?>
                                                                </div>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                                <td><?= implode(' ', $keys) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                    </div>
                </div>

                <footer class="footer">
                    <?= $site_footer ?>
                </footer>
            </div>
        </div>

        <!-- jQuery  -->
        <script src="assets/js/jquery.min.js"></script>
        <script src="assets/js/bootstrap.bundle.min.js"></script>
        <script src="assets/js/metisMenu.min.js"></script>
        <script src="assets/js/waves.js"></script>
        <script src="assets/js/jquery.slimscroll.js"></script>
        <script src="./plugins/moment/moment.js"></script>
        <script src="./plugins/bootstrap-datepicker/js/bootstrap-datepicker.min.js"></script>
        <script src="./plugins/select2/js/select2.min.js" type="text/javascript"></script>

        <!-- Required datatable js -->
        <script src="./plugins/datatables/jquery.dataTables.min.js"></script>
        <script src="./plugins/datatables/dataTables.bootstrap4.min.js"></script>
        <script src="./plugins/datatables/dataTables.buttons.min.js"></script>
        <script src="./plugins/datatables/buttons.bootstrap4.min.js"></script>
        <script src="./plugins/datatables/jszip.min.js"></script>
        <script src="./plugins/datatables/pdfmake.min.js"></script>
        <script src="./plugins/datatables/vfs_fonts.js"></script>
        <script src="./plugins/datatables/buttons.html5.min.js"></script>
        <script src="./plugins/datatables/buttons.print.min.js"></script>
        <script src="./plugins/datatables/dataTables.responsive.min.js"></script>
        <script src="./plugins/datatables/responsive.bootstrap4.min.js"></script>

        <!-- App js -->
        <script src="assets/js/jquery.core.js"></script>
        <script src="assets/js/jquery.app.js?t=<?= time() ?>"></script>
        <script src="assets/js/sr_forms.js?v=<?= @filemtime(__DIR__ . '/assets/js/sr_forms.js') ?>"></script>
        <script src="assets/js/car_forms.js?v=<?= @filemtime(__DIR__ . '/assets/js/car_forms.js') ?>"></script>

        <script type="text/javascript">
            $(document).ready(function() {
                const exportTitle = <?= json_encode(__('all_cars', 'All Cars')) ?>;
                const exportOptions = {
                    columns: [1, 2, 3, 4, 5, 6],
                    format: { body: function(data, row, col, node) { return $(node).attr('data-export') || $(node).text().trim(); } }
                };
                const KEY_COL = 8, TYPE_COL = 3;

                const table = $('#carsTable').DataTable({
                    dom: 'Brtip',
                    pageLength: 15,
                    responsive: true,
                    order: [[0, 'desc']],
                    buttons: [
                        { extend: 'excel', text: '<i class="mdi mdi-file-excel"></i> Excel', exportOptions: exportOptions, title: exportTitle },
                        { extend: 'pdf', text: '<i class="mdi mdi-file-pdf"></i> PDF', exportOptions: exportOptions, title: exportTitle },
                        { extend: 'print', text: '<i class="mdi mdi-printer"></i> ' + <?= json_encode(__('print')) ?>, exportOptions: exportOptions, title: exportTitle }
                    ],
                    columnDefs: [
                        { targets: [0, KEY_COL], visible: false },
                        { targets: [5, 7], orderable: false }
                    ],
                    language: {
                        info: `${__('showing')} _START_ ${__('to')} _END_ ${__('of')} _TOTAL_ ${__('entries')}`,
                        infoEmpty: `${__('showing')} 0 ${__('to')} 0 ${__('of')} 0 ${__('entries')}`,
                        infoFiltered: '',
                        paginate: { first: __('first'), last: __('last'), next: '<i class="mdi mdi-chevron-right"></i>', previous: '<i class="mdi mdi-chevron-left"></i>' },
                        emptyTable: `<div class="sr-empty"><i class="mdi mdi-car"></i>${__('no_data_available_in_table')}</div>`,
                        zeroRecords: `<div class="sr-empty"><i class="mdi mdi-magnify"></i>${__('no_matching_records_found')}</div>`
                    }
                });
                table.buttons().container().appendTo('#carExportButtons');

                function applyKey(key) {
                    table.column(KEY_COL).search(key ? '\\b' + key + '\\b' : '', true, false).draw();
                }
                applyKey('active');

                $('#carSearch').on('input', function() { table.search(this.value).draw(); });

                $('#carTiles').on('click', '.sr-tile', function() {
                    $('#carTiles .sr-tile').removeClass('active');
                    $(this).addClass('active');
                    applyKey($(this).data('key'));
                });

                $('#carType').on('change', function() {
                    const v = this.value;
                    table.column(TYPE_COL).search(v ? '^' + $.fn.dataTable.util.escapeRegex(v) + '$' : '', true, false).draw();
                });

                // Whole row opens the car (except buttons/links/menus)
                $('#carsTable tbody').on('click', 'tr', function(e) {
                    if ($(e.target).closest('a, button, .dropdown-menu, .dtr-control').length) return;
                    const href = $(this).data('href');
                    if (href) window.location = href;
                });
            });
        </script>
    </body>
    </html>
<?php } ?>
