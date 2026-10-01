<?php
    require_once __DIR__ . '/includes/session_check.php';
    require_once __DIR__ . '/includes/special_access_helper.php';
    include(__DIR__ . '/includes/avatar_select.php');

    // Determine user role and allowed assets
    $userType = $_SESSION['user_type'] ?? '';
    $empType = $_SESSION['emp_type'] ?? '';
    $isSystemAdmin = $is_system_admin ?? false;

    // Per-user Special Access grants (App Settings > Special Access), independent
    // of the role-based asset-type restriction below.
    $can_add_asset = $isSystemAdmin || user_has_special_access($conDB, $empid ?? '', 'asset_inventory_add', $user_role ?? '', $user_type ?? '', $isSystemAdmin);
    $can_edit_asset = $isSystemAdmin || user_has_special_access($conDB, $empid ?? '', 'asset_inventory_edit', $user_role ?? '', $user_type ?? '', $isSystemAdmin);
    $can_delete_asset = $isSystemAdmin || user_has_special_access($conDB, $empid ?? '', 'asset_inventory_delete', $user_role ?? '', $user_type ?? '', $isSystemAdmin);

    // Define role-based asset access (exclusive - each role only sees their assets)
    $roleAssetAccess = [
        'it' => ['Laptop'],           // IT can only manage Laptops
        'gr_officer' => ['SIM Card', 'Car', 'Mobile Phone'] // GR Officer can only manage SIM Card, Car, Mobile Phone
    ];

    // Determine allowed assets for current user. A 'asset_inventory_add' Special
    // Access grant lifts the role-based asset-type restriction the same way
    // being a system admin does, without granting any other admin ability.
    $allowedAssets = [];
    if ($isSystemAdmin || $can_add_asset) {
        $allowedAssets = []; // Empty means all assets
    } else {
        $allowedAssets = $roleAssetAccess[$userType] ?? [];
    }
?>

<!doctype html>
<html lang="<?= $current_lang ?? 'en' ?>" <?= ($is_rtl ?? false) ? 'dir="rtl"' : '' ?>>

<head>
    <meta charset="utf-8" />
    <title><?= $site_title ?> - <?= __('asset_inventory', 'Asset Inventory') ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta content="Al-Mutlak WMS" name="description" />
    <meta content="Al-Mutlak" name="author" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />

    <link rel="shortcut icon" href="<?= get_setting($conDB, 'favicon') ?>">

    <link href="./plugins/select2/css/select2.min.css" rel="stylesheet" type="text/css" />
    <link href="./plugins/bootstrap-datepicker/css/bootstrap-datepicker.min.css" rel="stylesheet">
    <link href="./plugins/datatables/dataTables.bootstrap4.min.css" rel="stylesheet" type="text/css" />
    <link href="./plugins/datatables/buttons.bootstrap4.min.css" rel="stylesheet" type="text/css" />
    <link href="./plugins/datatables/responsive.bootstrap4.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/icons.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/metismenu.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/style.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/style_dark.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/smart_request.css?v=<?= @filemtime(__DIR__ . '/assets/css/smart_request.css') ?>" rel="stylesheet" type="text/css" />
    <script src="assets/js/modernizr.min.js"></script>
    <?php if ($is_rtl): ?>
        <link href="assets/css/style_rtl.css" rel="stylesheet" type="text/css" />
    <?php endif; ?>
    <script>
        window.lang = <?= json_encode($GLOBALS['translations'] ?? []) ?>;
    </script>
    <style>
        .sr-page .sr-tiles.asset-tiles { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        @media (max-width: 575px) { .sr-page .sr-tiles.asset-tiles { grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 8px; } }
        .asset-avatar { width: 40px; height: 40px; border-radius: 10px; font-size: 20px; }
        .asset-types { display: flex; flex-wrap: wrap; gap: 6px; }
        .asset-type-btn {
            display: inline-flex; align-items: center; gap: 6px; height: 34px; padding: 0 12px; border-radius: 8px; cursor: pointer;
            border: 1px solid var(--sr-border-strong); background: var(--sr-surface); color: var(--sr-text-2); font-size: 12px; font-weight: 600;
        }
        .asset-type-btn:hover { border-color: var(--sr-accent); color: var(--sr-accent-strong); }
        .asset-type-btn.active { background: var(--sr-accent-soft); border-color: var(--sr-accent); color: var(--sr-accent-strong); }
        .asset-type-btn .sr-count { height: 18px; min-width: 18px; padding: 0 5px; }
        .asset-type-btn.active .sr-count { background: var(--sr-accent); color: #fff; }
        .sr-toolbar.asset-toolbar-2 { border-bottom: 1px solid var(--sr-border); padding-top: 0; }

        /* Details popup */
        .asset-dh { display: flex; align-items: center; gap: 14px; padding: 14px 16px; border-radius: 12px; background: var(--sr-accent-soft); margin-bottom: 12px; }
        .asset-dh .sr-avatar { width: 52px; height: 52px; font-size: 24px; border-radius: 14px; background: var(--sr-surface); overflow: hidden; }
        .asset-dh .sr-avatar img { width: 100%; height: 100%; object-fit: cover; }
        .asset-dh-title { font-size: 16px; font-weight: 700; color: var(--sr-text); }
        .asset-dh-sub { font-size: 12px; color: var(--sr-muted); }
        .asset-dh .sr-pill { margin-inline-start: auto; }
        .asset-actions { display: flex; flex-wrap: wrap; gap: 8px; padding: 14px; }
        .sr-form .sr-kv { padding: 6px 14px; }

        /* Signature pad */
        .sig-wrap { border: 1px solid var(--sr-border-strong); border-radius: 10px; background: #fff; overflow: hidden; }
        .sig-wrap canvas { display: block; width: 100%; height: 220px; cursor: crosshair; touch-action: none; }
        .sig-tools { display: flex; justify-content: space-between; align-items: center; margin-top: 6px; }
        .sig-preview { margin-top: 8px; max-height: 160px; max-width: 100%; border: 1px solid var(--sr-border); border-radius: 8px; display: none; background: #fff; }
        .sr-steps-list { margin: 0; padding-inline-start: 18px; font-size: 12.5px; }
        .sr-steps-list li { margin: 2px 0; }
    </style>
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
                            <h1><?= __('asset_inventory', 'Asset Inventory') ?></h1>
                            <p><?= __('asset_inventory_subtitle', 'Laptops, phones, SIM cards and cars handed out to employees.') ?></p>
                        </div>
                        <div class="sr-head-actions">
                            <?php if ($can_add_asset): ?>
                                <button id="btn-add-asset" type="button" class="sr-btn sr-btn-primary"><i class="fa fa-plus"></i> <?= __('add_asset', 'Add Asset') ?></button>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="sr-tiles asset-tiles" id="assetTiles">
                        <button type="button" class="sr-tile active" data-status="">
                            <span class="sr-tile-label"><span class="sr-dot dot-all"></span><?= __('all', 'All') ?></span>
                            <span class="sr-tile-value" data-count="all">&ndash;</span>
                        </button>
                        <button type="button" class="sr-tile" data-status="Available">
                            <span class="sr-tile-label"><span class="sr-dot dot-green"></span><?= __('available', 'Available') ?></span>
                            <span class="sr-tile-value" data-count="Available">&ndash;</span>
                        </button>
                        <button type="button" class="sr-tile" data-status="Assigned">
                            <span class="sr-tile-label"><span class="sr-dot dot-indigo"></span><?= __('assigned', 'Assigned') ?></span>
                            <span class="sr-tile-value" data-count="Assigned">&ndash;</span>
                        </button>
                    </div>

                    <div class="sr-card">
                        <div class="sr-toolbar">
                            <div class="sr-search">
                                <i class="mdi mdi-magnify"></i>
                                <input type="search" id="assetSearch" placeholder="<?= __('search') ?>..." autocomplete="off" aria-label="<?= __('search') ?>">
                            </div>
                            <div class="sr-toolbar-right">
                                <div id="assetExportButtons"></div>
                            </div>
                        </div>
                        <div class="sr-toolbar asset-toolbar-2">
                            <div class="asset-types" id="assetTypes"></div>
                        </div>

                        <div class="sr-table-wrap">
                            <table id="inventory_table" class="table sr-table dt-responsive nowrap" style="width: 100%;">
                                <thead>
                                    <tr>
                                        <th><?= __('asset_type', 'Asset') ?></th>
                                        <th><?= __('serial_number', 'Serial Number') ?></th>
                                        <th><?= __('description', 'Description') ?></th>
                                        <th><?= __('status', 'Status') ?></th>
                                        <th><?= __('assigned_to', 'Assigned To') ?></th>
                                        <th class="text-right"><?= __('action', 'Action') ?></th>
                                        <th>status</th>
                                        <th>type</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
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

    <script src="assets/js/jquery.min.js"></script>
    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/metisMenu.min.js"></script>
    <script src="assets/js/waves.js"></script>
    <script src="assets/js/jquery.slimscroll.js"></script>
    <script src="assets/js/jquery.core.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="./plugins/select2/js/select2.min.js"></script>
    <script src="./plugins/bootstrap-datepicker/js/bootstrap-datepicker.min.js"></script>
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
    <script src="https://cdn.jsdelivr.net/npm/signature_pad@4.0.0/dist/signature_pad.umd.min.js"></script>
    <script src="assets/js/jquery.app.js?t=<?= time() ?>"></script>
    <script src="assets/js/sr_forms.js?v=<?= @filemtime(__DIR__ . '/assets/js/sr_forms.js') ?>"></script>

    <script>
    (function() {
        const apiUrl = './includes/ajaxFile/ajaxAssetInventory.php';
        const F = window.SRForm, t = F.t, esc = F.esc;
        const CAR_ASSET_ID = 4;
        let inventoryTable;
        let allRows = [];
        let typeFilter = '';

        // User role information from backend
        const userRole = {
            userType: '<?= htmlspecialchars($userType) ?>',
            isSystemAdmin: <?= $isSystemAdmin ? 'true' : 'false' ?>,
            // Special Access grants (App Settings > Special Access), independent of role
            canAdd: <?= $can_add_asset ? 'true' : 'false' ?>,
            canEdit: <?= $can_edit_asset ? 'true' : 'false' ?>,
            canDelete: <?= $can_delete_asset ? 'true' : 'false' ?>,
            allowedAssets: <?= json_encode($allowedAssets) ?>,
            // Asset types hidden from this role (exclusive filtering)
            excludedAssets: function() {
                if (this.isSystemAdmin || this.canAdd) return [];
                if (this.userType === 'it') return ['SIM Card', 'Car', 'Mobile Phone'];
                if (this.userType === 'gr_officer') return ['Laptop'];
                return [];
            }
        };

        function typeKey(name) { return String(name || '').toLowerCase().replace(/ /g, '_'); }
        function typeLabel(name) { return name ? t(typeKey(name), name) : '-'; }
        function typeIcon(name) {
            return ({ 'Laptop': 'mdi-laptop', 'Mobile Phone': 'mdi-cellphone', 'SIM Card': 'mdi-sim', 'Car': 'mdi-car' })[name] || 'mdi-package-variant';
        }
        function statusPill(status, xs) {
            const tone = status === 'Assigned' ? 'indigo' : (status === 'Available' ? 'green' : 'amber');
            return `<span class="sr-pill ${xs ? 'sr-pill-xs ' : ''}tone-${tone}"><span class="sr-dot"></span>${esc(t(typeKey(status), status))}</span>`;
        }
        function fail(title, xhr, fallback) {
            Swal.fire({ allowOutsideClick: false, title: title || t('error', 'Error'), text: (xhr && xhr.responseJSON && xhr.responseJSON.message) || (xhr && xhr.message) || fallback || '', icon: 'error', confirmButtonText: t('ok', 'OK') });
        }

        /* ---------------- List ---------------- */

        function actionsHtml(row) {
            const isAssigned = row.status === 'Assigned', isCar = row.asset_id == CAR_ASSET_ID;
            let menu = '';
            if (!isAssigned && userRole.canEdit) {
                menu += `<a href="javascript:void(0);" class="dropdown-item editAssetBtn" data-id="${row.id}"><i class="mdi mdi-pencil mr-2"></i>${esc(t('edit', 'Edit'))}</a>`;
            }
            if (row.status === 'Available') {
                menu += isCar
                    ? `<a href="javascript:void(0);" class="dropdown-item btn-assign-driver" data-id="${row.id}"><i class="mdi mdi-steering mr-2"></i>${esc(t('assign_driver', 'Assign driver'))}</a>`
                    : `<a href="javascript:void(0);" class="dropdown-item btn-assign" data-id="${row.id}"><i class="mdi mdi-link-variant mr-2"></i>${esc(t('assign', 'Assign'))}</a>`;
            }
            if (isAssigned) {
                menu += `<a href="javascript:void(0);" class="dropdown-item print-asset-report" data-id="${row.id}"><i class="mdi mdi-printer mr-2"></i>${esc(t('print_report', 'Print report'))}</a>`;
                menu += `<a href="javascript:void(0);" class="dropdown-item text-warning btn-unassign" data-id="${row.id}"><i class="mdi mdi-link-variant-off mr-2"></i>${esc(t('unassign', 'Unassign'))}</a>`;
            }
            if (!isAssigned && userRole.canDelete) {
                menu += `<div class="dropdown-divider"></div><a href="javascript:void(0);" class="dropdown-item text-danger deleteAjax" data-tbl="asset_items" data-file="0" data-id="${row.id}"><i class="fa fa-trash mr-2"></i>${esc(t('delete', 'Delete'))}</a>`;
            }
            return `<div class="sr-actions">
                <a href="javascript:void(0);" class="sr-open-btn view-asset-details" data-id="${row.id}"><i class="mdi mdi-eye-outline"></i> ${esc(t('view_details', 'View'))}</a>
                ${menu ? `<div class="btn-group dropdown">
                    <a href="javascript:void(0);" class="sr-more-btn dropdown-toggle arrow-none" data-toggle="dropdown" aria-expanded="false"><i class="mdi mdi-dots-vertical"></i></a>
                    <div class="dropdown-menu dropdown-menu-right">${menu}</div>
                </div>` : ''}
            </div>`;
        }

        function initTable() {
            const exportOptions = { columns: [0, 1, 2, 3, 4], orthogonal: 'export' };
            const title = <?= json_encode(__('asset_inventory', 'Asset Inventory')) ?>;
            inventoryTable = $('#inventory_table').DataTable({
                dom: 'Brtip',
                pageLength: 15,
                responsive: true,
                order: [],
                data: [],
                columns: [
                    { data: 'asset_name', render: function(d, type, row) {
                        if (type === 'export') return typeLabel(d) + ' - ' + (row.tracking_id || '');
                        if (type !== 'display') return typeLabel(d) + ' ' + (row.tracking_id || '');
                        return `<div class="sr-person">
                            <span class="sr-avatar asset-avatar"><i class="mdi ${typeIcon(d)}"></i></span>
                            <div style="min-width:0;">
                                <span class="sr-cell-title">${esc(typeLabel(d))}</span>
                                <span class="sr-cell-sub sr-mono"><i class="mdi mdi-barcode-scan"></i>${esc(row.tracking_id || '-')}</span>
                            </div>
                        </div>`;
                    } },
                    { data: 'serial_number', render: function(d, type) { return type === 'display' ? `<span class="sr-mono">${esc(d || '-')}</span>` : (d || ''); } },
                    { data: 'description', render: function(d, type) { return type === 'display' ? (d ? esc(d) : '<span class="text-muted">&ndash;</span>') : (d || ''); } },
                    { data: 'status', render: function(d, type) { return type === 'display' ? statusPill(d) : t(typeKey(d), d); } },
                    { data: 'employee_name', render: function(d, type, row) {
                        if (type !== 'display') return d ? d + (row.assigned_date ? ' (' + row.assigned_date + ')' : '') : '';
                        if (!d) return '<span class="text-muted">&ndash;</span>';
                        return `<span class="sr-person-name">${esc(d)}</span><span class="sr-cell-sub"><i class="mdi mdi-calendar-check"></i>${esc(row.assigned_date || '')}</span>`;
                    } },
                    { data: null, orderable: false, className: 'text-right', render: function(d, type, row) { return type === 'display' ? actionsHtml(row) : ''; } },
                    { data: 'status', visible: false },
                    { data: 'asset_name', visible: false }
                ],
                createdRow: function(tr, row) { $(tr).attr('data-id', row.id); },
                buttons: [
                    { extend: 'excel', text: '<i class="mdi mdi-file-excel"></i> Excel', exportOptions: exportOptions, title: title },
                    { extend: 'csv', text: '<i class="mdi mdi-file-document"></i> CSV', exportOptions: exportOptions, title: title },
                    { extend: 'pdf', text: '<i class="mdi mdi-file-pdf"></i> PDF', exportOptions: exportOptions, title: title },
                    { extend: 'print', text: '<i class="mdi mdi-printer"></i> ' + t('print', 'Print'), exportOptions: exportOptions, title: title }
                ],
                language: {
                    info: `${__('showing')} _START_ ${__('to')} _END_ ${__('of')} _TOTAL_ ${__('entries')}`,
                    infoEmpty: `${__('showing')} 0 ${__('to')} 0 ${__('of')} 0 ${__('entries')}`,
                    infoFiltered: '',
                    paginate: { first: __('first'), last: __('last'), next: '<i class="mdi mdi-chevron-right"></i>', previous: '<i class="mdi mdi-chevron-left"></i>' },
                    emptyTable: `<div class="sr-empty"><i class="mdi mdi-package-variant"></i>${__('no_data_available_in_table')}</div>`,
                    zeroRecords: `<div class="sr-empty"><i class="mdi mdi-magnify"></i>${__('no_matching_records_found')}</div>`,
                    loadingRecords: `<div class="sr-empty"><i class="mdi mdi-loading mdi-spin"></i>${t('loading', 'Loading')}...</div>`
                }
            });
            inventoryTable.buttons().container().appendTo('#assetExportButtons');

            $('#assetSearch').on('input', function() { inventoryTable.search(this.value).draw(); });
            $('#assetTiles').on('click', '.sr-tile', function() {
                $('#assetTiles .sr-tile').removeClass('active');
                $(this).addClass('active');
                const s = $(this).data('status');
                inventoryTable.column(6).search(s ? '^' + s + '$' : '', true, false).draw();
            });
            $('#assetTypes').on('click', '.asset-type-btn', function() {
                $('#assetTypes .asset-type-btn').removeClass('active');
                $(this).addClass('active');
                typeFilter = $(this).data('type') || '';
                inventoryTable.column(7).search(typeFilter ? '^' + $.fn.dataTable.util.escapeRegex(typeFilter) + '$' : '', true, false).draw();
            });
            // Whole row opens the details (except buttons/links/menus)
            $('#inventory_table tbody').on('click', 'tr', function(e) {
                if ($(e.target).closest('a, button, .dropdown-menu, .dtr-control').length) return;
                const id = $(this).data('id');
                if (id) showAssetDetailsModal(id);
            });
        }

        function renderCounts(rows) {
            const by = { all: rows.length, Available: 0, Assigned: 0 }, types = {};
            rows.forEach(r => { by[r.status] = (by[r.status] || 0) + 1; types[r.asset_name] = (types[r.asset_name] || 0) + 1; });
            $('#assetTiles [data-count]').each(function() { $(this).text(by[$(this).data('count')] || 0); });
            const names = Object.keys(types).sort();
            let html = `<button type="button" class="asset-type-btn ${typeFilter ? '' : 'active'}" data-type=""><i class="mdi mdi-view-grid"></i>${esc(t('all_types', 'All types'))} <span class="sr-count">${rows.length}</span></button>`;
            names.forEach(n => {
                html += `<button type="button" class="asset-type-btn ${typeFilter === n ? 'active' : ''}" data-type="${esc(n)}"><i class="mdi ${typeIcon(n)}"></i>${esc(typeLabel(n))} <span class="sr-count">${types[n]}</span></button>`;
            });
            $('#assetTypes').html(html);
        }

        function loadInventory() {
            $.ajax({ url: apiUrl, type: 'POST', data: { action: 'list_items' }, dataType: 'json' })
                .done(function(resp) {
                    if (!resp.success) { fail(null, null, resp.message || 'Failed to load assets'); return; }
                    const excluded = userRole.isSystemAdmin ? [] : userRole.excludedAssets();
                    allRows = (resp.data.items || []).filter(r => !excluded.includes(r.asset_name));
                    renderCounts(allRows);
                    inventoryTable.clear().rows.add(allRows).draw(false);
                })
                .fail(function(xhr) { fail(null, xhr, 'Could not load assets'); });
        }

        /* ---------------- Details ---------------- */

        function showAssetDetailsModal(itemId) {
            Swal.fire({ title: t('loading', 'Loading') + '...', didOpen: () => Swal.showLoading(), allowOutsideClick: false, showConfirmButton: false });
            $.ajax({ url: apiUrl, type: 'POST', data: { action: 'get_item_details', item_id: itemId }, dataType: 'json' })
                .done(function(resp) {
                    if (!resp.success || !resp.data.item) { fail(null, null, resp.message || 'Could not load asset details'); return; }
                    const it = resp.data.item;
                    const row = allRows.find(r => r.id == it.id) || {};
                    const isAssigned = it.status === 'Assigned', isCar = row.asset_id == CAR_ASSET_ID || it.asset_name === 'Car';
                    const avatar = (isAssigned && it.avatar_url) ? `<img src="${esc(it.avatar_url)}" alt="">` : `<i class="mdi ${isAssigned ? 'mdi-account' : typeIcon(it.asset_name)}"></i>`;
                    const kv = (label, value) => `<div class="row-kv"><dt>${esc(label)}</dt><dd>${value ? esc(value) : '<span class="text-muted">&ndash;</span>'}</dd></div>`;

                    let actions = '';
                    if (isAssigned) {
                        actions += `<button type="button" class="sr-btn sr-btn-sm print-asset-report" data-id="${it.id}"><i class="mdi mdi-printer"></i> ${esc(t('print_report', 'Print report'))}</button>`;
                        actions += `<button type="button" class="sr-btn sr-btn-sm btn-unassign" data-id="${it.id}"><i class="mdi mdi-link-variant-off"></i> ${esc(t('unassign', 'Unassign'))}</button>`;
                    } else {
                        actions += isCar
                            ? `<button type="button" class="sr-btn sr-btn-sm sr-btn-primary btn-assign-driver" data-id="${it.id}"><i class="mdi mdi-steering"></i> ${esc(t('assign_driver', 'Assign driver'))}</button>`
                            : `<button type="button" class="sr-btn sr-btn-sm sr-btn-primary btn-assign" data-id="${it.id}"><i class="mdi mdi-link-variant"></i> ${esc(t('assign', 'Assign'))}</button>`;
                        if (userRole.canEdit) actions += `<button type="button" class="sr-btn sr-btn-sm editAssetBtn" data-id="${it.id}"><i class="mdi mdi-pencil"></i> ${esc(t('edit', 'Edit'))}</button>`;
                        if (userRole.canDelete) actions += `<button type="button" class="sr-btn sr-btn-sm sr-btn-ghost text-danger deleteAjax" data-tbl="asset_items" data-file="0" data-id="${it.id}"><i class="fa fa-trash"></i> ${esc(t('delete', 'Delete'))}</button>`;
                    }

                    const html = `<div class="sr-page sr-form text-left">
                        <div class="asset-dh">
                            <span class="sr-avatar">${avatar}</span>
                            <div style="min-width:0;">
                                <div class="asset-dh-title">${esc(isAssigned ? (it.employee_name || '-') : typeLabel(it.asset_name))}</div>
                                <div class="asset-dh-sub sr-mono">${isAssigned ? esc(t('emp_id', 'Emp ID') + ': ' + (it.emp_id || '-')) + ' &middot; ' : ''}${esc(it.tracking_id || '-')}</div>
                            </div>
                            ${statusPill(it.status)}
                        </div>
                        <div class="sr-fsec">
                            <div class="sr-fsec-head"><span><i class="mdi mdi-package-variant"></i> ${esc(t('asset_information', 'Asset information'))}</span></div>
                            <dl class="sr-kv">
                                ${kv(t('asset_type', 'Asset type'), typeLabel(it.asset_name))}
                                ${kv(t('tracking_id', 'Tracking ID'), it.tracking_id)}
                                ${kv(t('serial_number', 'Serial number'), it.serial_number)}
                                ${kv(t('description', 'Description'), it.description)}
                            </dl>
                        </div>
                        ${isAssigned ? `<div class="sr-fsec">
                            <div class="sr-fsec-head"><span><i class="mdi mdi-account-check"></i> ${esc(t('assignment_information', 'Assignment'))}</span></div>
                            <dl class="sr-kv">
                                ${kv(t('assigned_to', 'Assigned to'), it.employee_name)}
                                ${kv(t('employee_id', 'Employee ID'), it.emp_id)}
                                ${kv(t('department', 'Department'), it.dept)}
                                ${kv(t('mobile', 'Mobile'), it.mobile)}
                                ${kv(t('assigned_date', 'Assigned date'), it.assigned_date)}
                                ${it.assignment_note ? kv(t('note', 'Note'), it.assignment_note) : ''}
                            </dl>
                        </div>` : ''}
                        <div class="sr-fsec" style="margin-bottom:0;">
                            <div class="sr-fsec-head"><span><i class="mdi mdi-link-variant"></i> ${esc(t('actions', 'Actions'))}</span></div>
                            <div class="asset-actions">${actions}</div>
                        </div>
                    </div>`;

                    Swal.fire({ allowOutsideClick: false,
                        title: t('asset_details', 'Asset details'),
                        html: html,
                        width: '640px',
                        showConfirmButton: false,
                        showCloseButton: true,
                        customClass: { popup: 'sr-addline-popup' }
                    });
                })
                .fail(function(xhr) { fail(null, xhr, 'Could not load asset details'); });
        }

        /* ---------------- Add / edit ---------------- */

        function registerAssetModal() {
            $.ajax({ url: apiUrl, type: 'POST', data: { action: 'get_assets' }, dataType: 'json' })
                .done(function(resp) {
                    if (!resp.success || !resp.data.assets) { fail(null, null, 'Could not load asset types'); return; }
                    let assets = resp.data.assets;
                    if (!userRole.isSystemAdmin && !userRole.canAdd) {
                        assets = userRole.allowedAssets.length
                            ? assets.filter(a => userRole.allowedAssets.some(al => a.name.toLowerCase().includes(al.toLowerCase())))
                            : [];
                    }
                    if (!assets.length) {
                        Swal.fire({ title: t('access_denied', 'Access denied'), text: t('no_permission_add_assets', 'You do not have permission to add assets'), icon: 'warning', allowOutsideClick: false });
                        return;
                    }

                    const html = '<form id="registerAssetForm" class="sr-page sr-form text-left" autocomplete="off" novalidate>' +
                        F.section('mdi-package-variant', t('asset_type', 'Asset type'),
                            F.field({ col: 12, name: 'asset_id', html: F.choices('asset_id', assets.map(a => ({ v: a.id, l: typeLabel(a.name), icon: typeIcon(a.name) })), true, t('please_select_an_asset_type', 'Select an asset type')) })
                        ) +
                        F.section('mdi-information-outline', t('details', 'Details'),
                            F.field({ col: 12, name: 'car_id', label: t('select_car', 'Car'), req: false,
                                html: F.select({ name: 'car_id', id: 'swal-car-select', placeholder: t('search_and_select_a_car', 'Search and select a car'), msg: t('please_select_a_car', 'Select a car') }),
                                hint: esc(t('cars_already_assigned_disabled', 'Cars that already have a driver cannot be picked.')) }).replace('sr-fcol c-12', 'sr-fcol c-12 js-car-row" style="display:none;') +
                            F.field({ col: 12, name: 'serial_number', id: 'swal-serial-number', label: t('serial_number_identifier', 'Serial number / identifier'), req: true, cls: 'sr-mono',
                                ph: t('enter_serial_number_identifier', ''), msg: t('please_enter_serial_number', 'Enter the serial number') }).replace('sr-fcol c-12', 'sr-fcol c-12 js-serial-row') +
                            F.field({ col: 12, name: 'description', id: 'swal-description', type: 'textarea', label: t('description', 'Description'), ph: t('enter_asset_description', '') })
                        ) +
                    '</form>';

                    F.open({
                        title: t('add_new_asset_item', 'Add asset'),
                        html: html,
                        width: '680px',
                        confirm: t('add_asset', 'Add asset'),
                        didOpen: function() {
                            const $form = $('#registerAssetForm');
                            F.liveClear($form);
                            let carsLoaded = false;
                            $form.on('change', 'input[name="asset_id"]', function() {
                                const isCar = this.value == CAR_ASSET_ID;
                                $form.find('.js-car-row').toggle(isCar);
                                $form.find('.js-serial-row').toggle(!isCar);
                                $('#swal-serial-number').attr('data-required', isCar ? null : '1');
                                $('#swal-car-select').attr('data-required', isCar ? '1' : null);
                                if (isCar && !carsLoaded) {
                                    carsLoaded = true;
                                    $.ajax({ url: apiUrl, type: 'POST', data: { action: 'get_cars' }, dataType: 'json' }).done(function(r) {
                                        if (!r.success || !r.data.cars) return;
                                        const $sel = $('#swal-car-select');
                                        r.data.cars.forEach(car => {
                                            const label = `${car.maker_name || ''} ${car.model || ''} (${car.plate_no})`.trim();
                                            const assigned = car.is_assigned == 1;
                                            $sel.append($('<option>', { value: car.id, disabled: assigned, 'data-label': label })
                                                .text(assigned ? `${label} - ${t('assigned', 'Assigned')} (${car.assigned_to || ''})` : label));
                                        });
                                        F.select2($sel, { placeholder: t('search_and_select_a_car', 'Search and select a car'), allowClear: true });
                                    });
                                }
                            });
                            $form.on('change', '#swal-car-select', function() {
                                const label = $(this).find(':selected').data('label');
                                const $d = $('#swal-description');
                                if (label && (!$d.val() || $d.data('auto'))) { $d.val(label).data('auto', true); }
                            });
                            $form.on('input', '#swal-description', function() { $(this).data('auto', false); });
                        },
                        preConfirm: function() {
                            const $form = $('#registerAssetForm');
                            const msg = F.validate($form);
                            if (msg) { Swal.showValidationMessage(msg); return false; }
                            const assetId = $form.find('input[name="asset_id"]:checked').val();
                            const isCar = assetId == CAR_ASSET_ID;
                            return F.post(apiUrl, {
                                action: 'create_item',
                                asset_id: assetId,
                                car_id: isCar ? $('#swal-car-select').val() : '',
                                serial_number: isCar ? '' : $.trim($('#swal-serial-number').val()),
                                description: $.trim($('#swal-description').val())
                            });
                        }
                    }).then(function(result) {
                        if (!(result.isConfirmed && result.value)) return;
                        Swal.fire({ allowOutsideClick: false, title: t('added_successfully', 'Added'), html: esc(t('tracking_id', 'Tracking ID')) + ': <b class="sr-mono">' + esc(result.value.data.tracking_id) + '</b>', icon: 'success', confirmButtonText: t('ok', 'OK') })
                            .then(loadInventory);
                    });
                })
                .fail(function(xhr) { fail(null, xhr, 'Could not load asset types'); });
        }

        function openEditModal(itemId) {
            const row = allRows.find(r => r.id == itemId);
            if (!row) return;
            // The list trims long descriptions - fetch the full one
            $.ajax({ url: apiUrl, type: 'POST', data: { action: 'get_item_details', item_id: itemId }, dataType: 'json' }).always(function(resp) {
                const full = (resp && resp.success && resp.data.item) ? resp.data.item : row;
                const html = '<form id="editAssetForm" class="sr-page sr-form text-left" autocomplete="off" novalidate>' +
                    F.section(typeIcon(row.asset_name), typeLabel(row.asset_name),
                        F.field({ col: 12, name: 'serial_number', id: 'edit-serial-number', label: t('serial_number', 'Serial number'), req: true, cls: 'sr-mono',
                            value: full.serial_number || '', msg: t('serial_number_required', 'Serial number is required') }) +
                        F.field({ col: 12, name: 'description', id: 'edit-description', type: 'textarea', label: t('description', 'Description'), value: full.description || '' }),
                        `<span class="sr-chip sr-mono"><i class="mdi mdi-barcode-scan"></i>${esc(row.tracking_id || '')}</span>`
                    ) +
                '</form>';
                F.open({
                    title: t('edit_asset_item', 'Edit asset'),
                    html: html,
                    width: '620px',
                    confirm: t('update', 'Update'),
                    didOpen: function() { F.liveClear($('#editAssetForm')); $('#edit-serial-number').trigger('focus'); },
                    preConfirm: function() {
                        const $form = $('#editAssetForm');
                        const msg = F.validate($form);
                        if (msg) { Swal.showValidationMessage(msg); return false; }
                        return F.post(apiUrl, { action: 'update_item', item_id: itemId, serial_number: $.trim($('#edit-serial-number').val()), description: $('#edit-description').val() });
                    }
                }).then(function(result) {
                    if (!(result.isConfirmed && result.value)) return;
                    Swal.fire({ allowOutsideClick: false, title: t('updated', 'Updated'), text: t('asset_item_updated_successfully', 'Asset item updated successfully'), icon: 'success', confirmButtonText: t('ok', 'OK') }).then(loadInventory);
                });
            });
        }

        /* ---------------- Assign ---------------- */

        function employeeSelect($sel) {
            F.select2($sel, {
                placeholder: t('search_employee', 'Search by name or ID'),
                minimumInputLength: 0,
                ajax: {
                    url: apiUrl,
                    dataType: 'json',
                    delay: 250,
                    data: params => ({ action: 'search_employees', q: params.term || '' }),
                    processResults: data => ({ results: (data && data.data && data.data.results) || [] })
                }
            });
        }

        function assetSummary(row) {
            return `<div class="asset-dh" style="margin-bottom:12px;">
                <span class="sr-avatar"><i class="mdi ${typeIcon(row.asset_name)}"></i></span>
                <div style="min-width:0;"><div class="asset-dh-title">${esc(typeLabel(row.asset_name))}</div>
                <div class="asset-dh-sub sr-mono">${esc(row.tracking_id || '')}${row.serial_number ? ' &middot; ' + esc(row.serial_number) : ''}</div></div>
                ${row.status ? statusPill(row.status, true) : ''}
            </div>`;
        }

        function openAssignModal(itemId, isCar) {
            const row = allRows.find(r => r.id == itemId) || { id: itemId };
            const html = '<form id="assignAssetForm" class="sr-page sr-form text-left" autocomplete="off" novalidate>' +
                assetSummary(row) +
                F.section(isCar ? 'mdi-steering' : 'mdi-account-check', isCar ? t('driver', 'Driver') : t('employee', 'Employee'),
                    F.field({ col: 7, name: 'emp_id', label: isCar ? t('employee_driver', 'Employee / driver') : t('employee', 'Employee'), req: true,
                        html: '<select name="emp_id" id="swal-emp" class="form-control" data-required="1" data-msg="' + esc(t('employee_is_required', 'Select the employee')) + '"></select>' }) +
                    F.field({ col: 5, name: 'date', id: 'swal-date', label: isCar ? t('assignment_date', 'Assignment date') : t('assign_date', 'Assign date'), req: true, value: F.today(), ph: 'YYYY-MM-DD' }) +
                    F.field({ col: 12, name: 'note', id: 'swal-note', type: 'textarea', rows: 2, label: isCar ? t('notes_optional', 'Notes (optional)') : t('note', 'Note') })
                ) +
            '</form>';
            F.open({
                title: isCar ? t('assign_driver_to_car', 'Assign driver to car') : t('assign_asset', 'Assign asset'),
                html: html,
                width: '660px',
                icon: 'mdi-link-variant',
                confirm: t('assign', 'Assign'),
                didOpen: function() {
                    F.liveClear($('#assignAssetForm'));
                    employeeSelect($('#swal-emp'));
                    F.datepicker($('#swal-date'));
                },
                preConfirm: function() {
                    const $form = $('#assignAssetForm');
                    const msg = F.validate($form);
                    if (msg) { Swal.showValidationMessage(msg); return false; }
                    const data = isCar
                        ? { action: 'assign_driver', item_id: itemId, tracking_id: row.tracking_id || '', emp_id: $('#swal-emp').val(), rcv_date: $('#swal-date').val(), notes: $('#swal-note').val() }
                        : { action: 'assign_item', item_id: itemId, emp_id: $('#swal-emp').val(), assigned_date: $('#swal-date').val(), description: $('#swal-note').val() };
                    return F.post(apiUrl, data);
                }
            }).then(function(result) {
                if (!(result.isConfirmed && result.value)) return;
                const tid = result.value.data && result.value.data.tracking_id;
                Swal.fire({ allowOutsideClick: false,
                    title: isCar ? t('driver_assigned', 'Driver assigned') : t('assigned', 'Assigned'),
                    html: tid ? esc(t('tracking_id', 'Tracking ID')) + ': <b class="sr-mono">' + esc(tid) + '</b>' : '',
                    icon: 'success', confirmButtonText: t('ok', 'OK')
                }).then(loadInventory);
            });
        }

        /* ---------------- Return (unassign) ---------------- */

        function openReturnModal(itemId) {
            const row = allRows.find(r => r.id == itemId) || { id: itemId };
            const TYPES = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx'];
            const html = '<form id="returnAssetForm" class="sr-page sr-form text-left" autocomplete="off" novalidate>' +
                assetSummary(row) +
                '<div class="sr-notice tone-sky" style="margin-bottom:12px;"><i class="mdi mdi-information-outline"></i><div><strong>' + esc(t('instructions', 'Instructions')) + '</strong>' +
                    '<ol class="sr-steps-list">' +
                        '<li>' + esc(t('click_print_report_to_print_asset_details', 'Print the report from the asset actions')) + '</li>' +
                        '<li>' + esc(t('get_the_printed_document_signed', 'Get the printed document signed by the employee')) + '</li>' +
                        '<li>' + esc(t('upload_the_signed_proof_below', 'Upload the signed proof below')) + '</li>' +
                    '</ol></div></div>' +
                F.section('mdi-clipboard-check', t('asset_condition', 'Condition'),
                    F.field({ col: 12, name: 'asset_condition', html: F.choices('asset_condition', [
                        { v: 'Good', l: t('good', 'Good'), icon: 'mdi-check-circle' },
                        { v: 'Damage', l: t('damage', 'Damage'), icon: 'mdi-alert-circle-outline' },
                        { v: 'Lost', l: t('lost', 'Lost'), icon: 'mdi-help-circle' },
                        { v: 'Buy', l: t('buy', 'Buy'), icon: 'mdi-cash-multiple' },
                        { v: 'Other', l: t('other', 'Other'), icon: 'mdi-dots-horizontal' }
                    ], true, t('please_select_an_asset_condition', 'Select the asset condition')) }) +
                    F.field({ col: 5, name: 'return_date', id: 'return-date', label: t('return_date', 'Return date'), req: true, value: F.today(), ph: 'YYYY-MM-DD',
                        msg: t('return_date_is_required', 'Return date is required') }) +
                    F.field({ col: 7, name: 'notes', id: 'return-notes', label: t('notes_optional', 'Notes (optional)'), ph: t('add_return_notes', 'Add any return notes...') })
                ) +
                F.section('mdi-paperclip', t('proof_of_return', 'Proof of return'),
                    F.field({ col: 12, name: 'proof_file', html: F.filePicker({ id: 'proof-file', accept: '.pdf,.jpg,.jpeg,.png,.doc,.docx',
                        title: t('upload_signed_document', 'Upload the signed document'), hint: 'PDF, JPG, PNG, DOC' }) })
                ) +
            '</form>';
            F.open({
                title: t('return_asset_item', 'Return asset'),
                html: html,
                width: '720px',
                icon: 'mdi-keyboard-return',
                confirm: t('confirm_return', 'Confirm return'),
                confirmColor: window.APP_COLORS && APP_COLORS.success,
                didOpen: function() {
                    const $form = $('#returnAssetForm');
                    F.liveClear($form);
                    F.bindFilePicker($form);
                    F.datepicker($('#return-date'), { endDate: '+0d' });
                },
                preConfirm: function() {
                    const $form = $('#returnAssetForm');
                    const msg = F.validate($form, function() {
                        const input = $('#proof-file')[0];
                        if (!input.files.length) return { el: $form.find('.sr-filepick')[0], msg: t('proof_of_return_document_is_required', 'Proof of return document is required') };
                        const m = F.checkFile(input, TYPES, 10);
                        return m ? { el: $form.find('.sr-filepick')[0], msg: m } : null;
                    });
                    if (msg) { Swal.showValidationMessage(msg); return false; }
                    const fd = new FormData();
                    fd.append('action', 'unassign_item');
                    fd.append('item_id', itemId);
                    fd.append('tracking_id', row.tracking_id || '');
                    fd.append('asset_condition', $form.find('input[name="asset_condition"]:checked').val());
                    fd.append('return_date', $('#return-date').val());
                    fd.append('proof_file', $('#proof-file')[0].files[0]);
                    fd.append('notes', $('#return-notes').val());
                    return F.post(apiUrl, fd, true);
                }
            }).then(function(result) {
                if (!(result.isConfirmed && result.value)) return;
                const recId = result.value.data && result.value.data.asset_record_id;
                Swal.fire({ allowOutsideClick: false,
                    title: t('returned', 'Returned'),
                    text: t('asset_item_returned_and_unassigned_successfully', 'Asset item returned and unassigned successfully'),
                    icon: 'success',
                    showCancelButton: !!recId,
                    confirmButtonText: recId ? '<i class="mdi mdi-printer"></i> ' + esc(t('print_report', 'Print report')) : t('ok', 'OK'),
                    cancelButtonText: t('done', 'Done'),
                    confirmButtonColor: window.APP_COLORS && APP_COLORS.primary,
                    cancelButtonColor: window.APP_COLORS && APP_COLORS.secondary
                }).then(function(r) {
                    loadInventory();
                    if (r.isConfirmed && recId) window.open('asset_return_report.php?asset_id=' + recId, '_blank');
                });
            });
        }

        /* ---------------- Print report + signature ---------------- */

        function openPrintModal(itemId) {
            $.ajax({ type: 'POST', url: apiUrl, data: { action: 'get_asset_record', asset_id: itemId }, dataType: 'json' })
                .done(function(resp) {
                    if (!(resp.success && resp.data && resp.data.tracking_id)) { fail(null, null, t('could_not_find_asset_record', 'Could not find the asset record for printing')); return; }
                    const employeeAssetId = resp.data.employee_asset_id;
                    const reportUrl = 'asset_return_report.php?asset_id=' + employeeAssetId;
                    let pad = null, uploaded = null, mode = 'draw';
                    const html = '<form id="printAssetForm" class="sr-page sr-form text-left" autocomplete="off" novalidate>' +
                        '<div class="sr-notice tone-sky" style="margin-bottom:12px;"><i class="mdi mdi-information-outline"></i><div>' +
                            '<ol class="sr-steps-list">' +
                                '<li>' + esc(t('review_asset_details_then_click_confirm_to_open_report', 'Review the details, then confirm to open the report')) + '</li>' +
                                '<li>' + esc(t('draw_or_upload_signature_to_attach_as_proof', 'Draw or upload a signature to attach as proof')) + '</li>' +
                            '</ol></div></div>' +
                        F.section('mdi-lead-pencil', t('signature', 'Signature'),
                            F.field({ col: 12, name: 'mode', html: '<div class="sr-seg">' +
                                '<label class="sr-seg-opt is-on checked"><input type="radio" name="sig_mode" value="draw" checked><span><i class="mdi mdi-lead-pencil"></i> ' + esc(t('draw_signature', 'Draw')) + '</span></label>' +
                                '<label class="sr-seg-opt is-on"><input type="radio" name="sig_mode" value="upload"><span><i class="mdi mdi-upload"></i> ' + esc(t('upload_signature', 'Upload')) + '</span></label>' +
                            '</div>' }) +
                            F.field({ col: 12, name: 'pad', html:
                                '<div class="js-pane-draw"><div class="sig-wrap ad-keep"><canvas id="print-signature-canvas"></canvas></div>' +
                                '<div class="sig-tools"><small class="sr-fhint">' + esc(t('draw_your_signature_above', 'Draw the signature above')) + '</small>' +
                                '<button type="button" class="sr-btn sr-btn-sm sr-btn-ghost js-clear"><i class="mdi mdi-eraser"></i> ' + esc(t('clear_signature', 'Clear')) + '</button></div></div>' +
                                '<div class="js-pane-upload" style="display:none;">' +
                                    F.filePicker({ id: 'print-signature-file', accept: '.jpg,.jpeg,.png', title: t('select_signature_image_file', 'Choose a signature image'), hint: 'JPG, PNG' }) +
                                    '<img class="sig-preview" id="print-signature-preview" alt="">' +
                                '</div>' })
                        ) +
                    '</form>';
                    F.open({
                        title: t('asset_return_report', 'Asset return report'),
                        html: html,
                        width: '620px',
                        icon: 'mdi-printer',
                        confirm: t('confirm_and_open_report', 'Confirm and open report'),
                        didOpen: function() {
                            const $form = $('#printAssetForm');
                            F.liveClear($form);
                            F.bindFilePicker($form);
                            const canvas = document.getElementById('print-signature-canvas');
                            const ratio = Math.max(window.devicePixelRatio || 1, 1);
                            canvas.width = canvas.offsetWidth * ratio;
                            canvas.height = canvas.offsetHeight * ratio;
                            canvas.getContext('2d').scale(ratio, ratio);
                            if (window.SignaturePad) pad = new SignaturePad(canvas, { backgroundColor: 'rgb(255,255,255)' });
                            $form.on('click', '.js-clear', () => pad && pad.clear());
                            $form.on('change', 'input[name="sig_mode"]', function() {
                                mode = this.value;
                                $form.find('.js-pane-draw').toggle(mode === 'draw');
                                $form.find('.js-pane-upload').toggle(mode === 'upload');
                            });
                            $('#print-signature-file').on('change', function() {
                                const f = this.files[0];
                                uploaded = null;
                                $('#print-signature-preview').hide();
                                if (!f || F.checkFile(this, ['jpg', 'jpeg', 'png'], 5)) return;
                                const reader = new FileReader();
                                reader.onload = e => { uploaded = e.target.result; $('#print-signature-preview').attr('src', uploaded).show(); };
                                reader.readAsDataURL(f);
                            });
                        },
                        preConfirm: function() {
                            const $form = $('#printAssetForm');
                            let signature = null;
                            if (mode === 'upload') {
                                const m = F.checkFile($('#print-signature-file')[0], ['jpg', 'jpeg', 'png'], 5);
                                if (m) { $form.find('.sr-filepick').addClass('is-invalid'); Swal.showValidationMessage(m); return false; }
                                signature = uploaded;
                            } else if (pad && !pad.isEmpty()) {
                                signature = pad.toDataURL('image/png');
                            }
                            const fd = new FormData();
                            fd.append('action', 'save_print_proof');
                            fd.append('item_id', itemId);
                            fd.append('tracking_id', resp.data.tracking_id);
                            if (employeeAssetId) fd.append('employee_asset_id', employeeAssetId);
                            if (signature) fd.append('signature', signature);
                            return F.post(apiUrl, fd, true);
                        }
                    }).then(function(result) {
                        if (result.isConfirmed && result.value) window.open(reportUrl, '_blank');
                    });
                })
                .fail(function(xhr) { fail(null, xhr, t('failed_to_retrieve_asset_record', 'Failed to retrieve the asset record')); });
        }

        /* ---------------- Events ---------------- */

        $(document).on('click', '#btn-add-asset', registerAssetModal);
        $(document).on('click', '.view-asset-details', function() { showAssetDetailsModal($(this).data('id')); });
        $(document).on('click', '.btn-assign', function() { openAssignModal($(this).data('id'), false); });
        $(document).on('click', '.btn-assign-driver', function() { openAssignModal($(this).data('id'), true); });
        $(document).on('click', '.btn-unassign', function() { openReturnModal($(this).data('id')); });
        $(document).on('click', '.editAssetBtn', function() { openEditModal($(this).data('id')); });
        $(document).on('click', '.print-asset-report', function() { openPrintModal($(this).data('id')); });

        $(document).ready(function() {
            initTable();
            loadInventory();
        });
    })();
    </script>
</body>

</html>
