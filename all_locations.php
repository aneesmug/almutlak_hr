<?php
// Locations list. Same look as the Smart Request pages (assets/css/smart_request.css).
// Add / status / delete keep using the shared handlers in assets/js/jquery.app.js
// (addlocarionFunc, .deleteAjax) and includes/ajaxFile/update_loc_stus.php.
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/session_check.php';
require_once __DIR__ . '/includes/special_access_helper.php';
$can_add_location = !empty($is_system_admin) || user_has_special_access($conDB, $empid ?? '', 'locations_add', $user_role ?? '', $user_type ?? '', $is_system_admin ?? false);
$can_delete_location = !empty($is_system_admin) || user_has_special_access($conDB, $empid ?? '', 'locations_delete', $user_role ?? '', $user_type ?? '', $is_system_admin ?? false);
$query = mysqli_query($conDB, "SELECT * FROM `admin_login` WHERE `id_iqama`='" . $username . "'");
if (mysqli_num_rows($query) == 1) {
    include("./includes/avatar_select.php");

    $can_toggle_status = ($user_type == $access1);

    if ($user_type == $access1 or $user_type == $access2) {
        $sql_loc = "SELECT `section`.*, COUNT(`machines`.`location_id`) AS `totalDvc`, `location_img`.`out_img` FROM `section` LEFT JOIN `machines` ON  `machines`.`location_id`= `section`.`id` LEFT JOIN `location_img` ON  `location_img`.`location_id`= `section`.`id` GROUP BY `section`.`id`";
    } else {
        $sql_loc = "SELECT `section`.*, COUNT(`machines`.`location_id`) AS `totalDvc`, `location_img`.`out_img` FROM `section` LEFT JOIN `machines` ON `machines`.`location_id`= `section`.`id` LEFT JOIN `location_img` ON  `location_img`.`location_id`= `section`.`id` WHERE `section`.`dept` = 'POS' OR `section`.`dept` = 'Maintenance' OR `section`.`dept` = 'Warehouse' AND `section`.`status`='A' GROUP BY `section`.`id` ORDER BY `section`.`section_name` REGEXP '^[^A-Za-z]' ASC, `section`.section_name";
    }
    $query_loc = mysqli_query($conDB, $sql_loc);

    $locations = [];
    $count_active = 0;
    $count_closed = 0;
    $count_devices = 0;
    $departments = [];
    while ($query_loc && ($rec = mysqli_fetch_assoc($query_loc))) {
        $locations[] = $rec;
        if ($rec['status'] == "1") { $count_active++; } else { $count_closed++; }
        $count_devices += (int)$rec['totalDvc'];
        if (trim((string)$rec['dept']) !== '') { $departments[$rec['dept']] = true; }
    }
    $departments = array_keys($departments);
    sort($departments, SORT_NATURAL | SORT_FLAG_CASE);

    $initials = function ($name) {
        $parts = preg_split('/[\s\-]+/', trim((string)$name), -1, PREG_SPLIT_NO_EMPTY);
        if (!$parts) return '?';
        return mb_strtoupper(mb_substr($parts[0], 0, 1) . (isset($parts[1]) ? mb_substr($parts[1], 0, 1) : ''));
    };
?>
    <!doctype html>
    <html lang="<?= $current_lang ?? 'en' ?>" <?= ($is_rtl ?? false) ? 'dir="rtl"' : '' ?>>

    <head>
        <meta charset="utf-8" />
        <title><?= $site_title ?> - <?=__('all_locations_title')?></title>
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
        <meta content="Anees Afzal" name="author" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />

        <link rel="shortcut icon" href="<?=get_setting($conDB, 'favicon')?>">

        <!-- DataTables -->
        <link href="./plugins/datatables/dataTables.bootstrap4.min.css" rel="stylesheet" type="text/css" />
        <link href="./plugins/datatables/buttons.bootstrap4.min.css" rel="stylesheet" type="text/css" />
        <link href="./plugins/datatables/responsive.bootstrap4.min.css" rel="stylesheet" type="text/css" />
        <link rel="stylesheet" href="./plugins/croppie/croppie.css">
        <link href="./plugins/bootstrap-datepicker/css/bootstrap-datepicker.min.css" rel="stylesheet">
        <link href="./plugins/bootstrap-timepicker/hijri_css/bootstrap-datetimepicker.min.css" rel="stylesheet">

        <!-- App css -->
        <link href="assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
        <link href="assets/css/icons.css" rel="stylesheet" type="text/css" />
        <link href="assets/css/metismenu.min.css" rel="stylesheet" type="text/css" />
        <link href="assets/css/style.css" rel="stylesheet" type="text/css" />
        <link href="assets/css/style_dark.css" rel="stylesheet" type="text/css" />
        <link href="assets/css/smart_request.css?v=<?= @filemtime(__DIR__ . '/assets/css/smart_request.css') ?>" rel="stylesheet" type="text/css" />
        <script src="assets/js/modernizr.min.js"></script>
        <style>
            .sr-page .sr-tiles.loc-tiles { grid-template-columns: repeat(4, minmax(0, 1fr)); }
            @media (max-width: 767px) { .sr-page .sr-tiles.loc-tiles { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
            .sr-tile.is-info { cursor: default; }
            .sr-tile.is-info:hover { transform: none; border-color: var(--sr-border); }
            .loc-thumb { width: 42px; height: 42px; border-radius: 10px; object-fit: cover; flex: 0 0 auto; background: var(--sr-surface-3); }
            .loc-thumb.sr-avatar { border-radius: 10px; width: 42px; height: 42px; }
            .sr-page table.sr-table tbody tr.is-closed td { opacity: .62; }
            .sr-page table.sr-table tbody tr.is-closed:hover td { opacity: 1; }
            .loc-dept-select {
                height: 34px; padding: 0 30px 0 12px; border-radius: 8px; font-size: 12px; font-weight: 600;
                border: 1px solid var(--sr-border-strong); background-color: var(--sr-surface); color: var(--sr-text-2);
            }
            .loc-status-btn { border: 0; background: transparent; padding: 0; cursor: pointer; }
            .loc-status-btn:hover .sr-pill { box-shadow: 0 0 0 2px var(--sr-accent-soft); }
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
                                <h1><?=__('all_registered_locations_header')?></h1>
                                <p><?= __('locations_subtitle', 'Shops, warehouses and sites with their buildings, contracts and devices.') ?></p>
                            </div>
                            <div class="sr-head-actions">
                                <?php if ($can_add_location): ?>
                                    <button type="button" class="sr-btn sr-btn-primary" onclick="addlocarionFunc()"><i class="fa fa-plus"></i> <?=__('add_location_button')?></button>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="sr-tiles loc-tiles" id="locTiles">
                            <button type="button" class="sr-tile active" data-status="">
                                <span class="sr-tile-label"><span class="sr-dot dot-all"></span><?= __('all', 'All') ?></span>
                                <span class="sr-tile-value"><?= count($locations) ?></span>
                            </button>
                            <button type="button" class="sr-tile" data-status="active">
                                <span class="sr-tile-label"><span class="sr-dot dot-green"></span><?= __('active_status') ?></span>
                                <span class="sr-tile-value"><?= $count_active ?></span>
                            </button>
                            <button type="button" class="sr-tile" data-status="closed">
                                <span class="sr-tile-label"><span class="sr-dot dot-red"></span><?= __('closed_status') ?></span>
                                <span class="sr-tile-value"><?= $count_closed ?></span>
                            </button>
                            <div class="sr-tile is-info">
                                <span class="sr-tile-label"><span class="sr-dot dot-sky"></span><?= __('devices_header') ?></span>
                                <span class="sr-tile-value"><?= $count_devices ?></span>
                            </div>
                        </div>

                        <div class="sr-card">
                            <div class="sr-toolbar">
                                <div class="sr-search">
                                    <i class="mdi mdi-magnify"></i>
                                    <input type="search" id="locSearch" placeholder="<?= __('search') ?>..." autocomplete="off" aria-label="<?= __('search') ?>">
                                </div>
                                <div class="sr-toolbar-right">
                                    <?php if (count($departments) > 1): ?>
                                        <select id="locDept" class="loc-dept-select" aria-label="<?= __('department_header') ?>">
                                            <option value=""><?= __('department_header') ?>: <?= __('all', 'All') ?></option>
                                            <?php foreach ($departments as $d): ?>
                                                <option value="<?= htmlspecialchars($d) ?>"><?= htmlspecialchars($d) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    <?php endif; ?>
                                    <div id="locExportButtons"></div>
                                </div>
                            </div>

                            <div class="sr-table-wrap">
                                <table id="employee_vac" class="table sr-table dt-responsive nowrap" style="width: 100%;">
                                    <thead>
                                        <tr>
                                            <th><?=__('sr_header')?></th>
                                            <th><?=__('section_name_header')?></th>
                                            <th><?=__('department_header')?></th>
                                            <th><?=__('building_base_header')?></th>
                                            <th><?=__('building_size_header')?></th>
                                            <th><?=__('address_header')?></th>
                                            <th class="text-center"><?=__('devices_header')?></th>
                                            <th><?=__('status_header')?></th>
                                            <th class="text-right"><?=__('action_header')?></th>
                                            <th>status-key</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($locations as $rec):
                                            $id = (int)$rec["id"];
                                            $is_active = ($rec["status"] == "1");
                                        ?>
                                            <tr class="<?= $is_active ? '' : 'is-closed' ?>" data-href="./view_location.php?id=<?= $id ?>">
                                                <td><?= $id ?></td>
                                                <td data-order="<?= htmlspecialchars($rec["section_name"]) ?>">
                                                    <div class="sr-person">
                                                        <?php if (!empty($rec["out_img"])): ?>
                                                            <img src="<?= htmlspecialchars($rec["out_img"]) ?>" class="loc-thumb" alt="" loading="lazy">
                                                        <?php else: ?>
                                                            <span class="sr-avatar loc-thumb"><?= htmlspecialchars($initials($rec["section_name"])) ?></span>
                                                        <?php endif; ?>
                                                        <div style="min-width: 0;">
                                                            <span class="sr-cell-title"><?= htmlspecialchars($rec["section_name"]) ?></span>
                                                            <span class="sr-cell-sub"><i class="mdi mdi-map-marker"></i><?= htmlspecialchars($rec["location_name"] ?: '-') ?></span>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td><?= trim((string)$rec["dept"]) !== '' ? '<span class="sr-chip">' . htmlspecialchars($rec["dept"]) . '</span>' : '<span class="text-muted">&ndash;</span>' ?></td>
                                                <td><?= htmlspecialchars($rec["bulding_base"]) ?></td>
                                                <td><?= htmlspecialchars($rec["bulding_size"]) ?></td>
                                                <td><?= htmlspecialchars($rec["location_name"]) ?></td>
                                                <td class="text-center" data-order="<?= (int)$rec["totalDvc"] ?>"><span class="sr-count"><?= (int)$rec["totalDvc"] ?></span></td>
                                                <td>
                                                    <?php $pill = $is_active
                                                        ? '<span class="sr-pill tone-green"><span class="sr-dot"></span>' . __('active_status') . '</span>'
                                                        : '<span class="sr-pill tone-red"><span class="sr-dot"></span>' . __('closed_status') . '</span>'; ?>
                                                    <?php if ($can_toggle_status): ?>
                                                        <button type="button" class="loc-status-btn statusUpd" data-status="<?= $is_active ? '0' : '1' ?>" data-id="<?= $id ?>" title="<?= __('update_status_confirm_title') ?>"><?= $pill ?></button>
                                                    <?php else: ?>
                                                        <?= $pill ?>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-right">
                                                    <div class="sr-actions">
                                                        <a href="./view_location.php?id=<?= $id ?>" class="sr-open-btn"><i class="mdi mdi-eye-outline"></i> <?=__('open_link')?></a>
                                                        <?php if ($can_delete_location): ?>
                                                            <div class="btn-group dropdown">
                                                                <a href="javascript:void(0);" class="sr-more-btn dropdown-toggle arrow-none" data-toggle="dropdown" aria-expanded="false"><i class="mdi mdi-dots-vertical"></i></a>
                                                                <div class="dropdown-menu dropdown-menu-right">
                                                                    <a href="javascript:void(0);" class="dropdown-item text-danger deleteAjax" data-id="<?= $id ?>" data-tbl="section" data-file="0"><i class="fa fa-trash mr-2"></i><?=__('delete_link')?></a>
                                                                </div>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                                <td><?= $is_active ? 'active' : 'closed' ?></td>
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
        <script type="text/javascript" src="./plugins/parsleyjs/parsley.min.js"></script>
        <script src="./plugins/autoNumeric/autoNumeric.js" type="text/javascript"></script>
        <script src="./plugins/moment/moment.js"></script>
        <script src="./plugins/bootstrap-datepicker/js/bootstrap-datepicker.min.js"></script>
        <script src="./plugins/bootstrap-timepicker/hijri/bootstrap-hijri-datetimepicker.min.js"></script>

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
        <script src="assets/js/location_forms.js?v=<?= @filemtime(__DIR__ . '/assets/js/location_forms.js') ?>"></script>

        <script type="text/javascript">
            $(document).ready(function() {
                const exportTitle = <?= json_encode(__('all_locations_title')) ?>;
                const exportColumns = [1, 2, 3, 4, 5, 6];
                const STATUS_COL = 9, DEPT_COL = 2;

                const table = $('#employee_vac').DataTable({
                    dom: 'Brtip',
                    pageLength: 15,
                    responsive: true,
                    order: [[1, 'asc']],
                    buttons: [
                        { extend: 'excel', text: '<i class="mdi mdi-file-excel"></i> Excel', exportOptions: { columns: exportColumns }, title: exportTitle },
                        { extend: 'pdf', text: '<i class="mdi mdi-file-pdf"></i> PDF', exportOptions: { columns: exportColumns }, title: exportTitle },
                        { extend: 'print', text: '<i class="mdi mdi-printer"></i> ' + <?= json_encode(__('print')) ?>, exportOptions: { columns: exportColumns }, title: exportTitle }
                    ],
                    columnDefs: [
                        { targets: [0, STATUS_COL], visible: false },
                        { targets: [7, 8], orderable: false }
                    ],
                    language: {
                        info: `${__('showing')} _START_ ${__('to')} _END_ ${__('of')} _TOTAL_ ${__('entries')}`,
                        infoEmpty: `${__('showing')} 0 ${__('to')} 0 ${__('of')} 0 ${__('entries')}`,
                        infoFiltered: '',
                        paginate: { first: __('first'), last: __('last'), next: '<i class="mdi mdi-chevron-right"></i>', previous: '<i class="mdi mdi-chevron-left"></i>' },
                        emptyTable: `<div class="sr-empty"><i class="mdi mdi-inbox"></i>${__('no_data_available_in_table')}</div>`,
                        zeroRecords: `<div class="sr-empty"><i class="mdi mdi-magnify"></i>${__('no_matching_records_found')}</div>`
                    }
                });
                table.buttons().container().appendTo('#locExportButtons');

                $('#locSearch').on('input', function() { table.search(this.value).draw(); });

                $('#locTiles').on('click', '.sr-tile[data-status]', function() {
                    $('#locTiles .sr-tile').removeClass('active');
                    $(this).addClass('active');
                    const s = $(this).data('status');
                    table.column(STATUS_COL).search(s ? '^' + s + '$' : '', true, false).draw();
                });

                $('#locDept').on('change', function() {
                    const v = this.value;
                    table.column(DEPT_COL).search(v ? '^' + $.fn.dataTable.util.escapeRegex(v) + '$' : '', true, false).draw();
                });

                // Whole row opens the location (except buttons/links/menus)
                $('#employee_vac tbody').on('click', 'tr', function(e) {
                    if ($(e.target).closest('a, button, .dropdown-menu, .dtr-control').length) return;
                    const href = $(this).data('href');
                    if (href) window.location = href;
                });
            });

            $(document).on('click', '.statusUpd', function(e) {
                e.preventDefault();
                e.stopPropagation();
                var itemId = $(this).data('id');
                var status = $(this).data('status');
                Swal.fire({
                    title: <?= json_encode(__('update_status_confirm_title')) ?>,
                    text: <?= json_encode(__('update_status_confirm_text')) ?>,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: APP_COLORS.primary,
                    cancelButtonColor: APP_COLORS.danger_dark,
                    confirmButtonText: <?= json_encode(__('yes_update_button')) ?>,
                    showLoaderOnConfirm: true,
                    allowOutsideClick: false,
                    preConfirm: function() {
                        return $.ajax({
                            url: './includes/ajaxFile/update_loc_stus.php',
                            type: 'GET',
                            data: { id: itemId, status: status },
                            cache: false,
                            dataType: "json"
                        }).catch(function() {
                            Swal.showValidationMessage(<?= json_encode(__('request_failed')) ?>);
                        });
                    }
                }).then(function(result) {
                    if (result.isConfirmed && result.value) {
                        Swal.fire({ title: result.value.title, text: result.value.message, icon: result.value.type, allowOutsideClick: false })
                            .then(function() { location.reload(); });
                    }
                });
            });
        </script>
    </body>
    </html>
<?php } ?>
