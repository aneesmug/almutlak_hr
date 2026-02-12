<?php
    require_once __DIR__ . '/includes/session_check.php';
    include(__DIR__ . '/includes/avatar_select.php');
    
    // Determine user role and allowed assets
    $userType = $_SESSION['user_type'] ?? '';
    $empType = $_SESSION['emp_type'] ?? '';
    $isSystemAdmin = $is_system_admin ?? false;
    
    // Define role-based asset access (exclusive - each role only sees their assets)
    $roleAssetAccess = [
        'it' => ['Laptop'],           // IT can only manage Laptops
        'gr_officer' => ['SIM Card', 'Car', 'Mobile Phone'] // GR Officer can only manage SIM Card, Car, Mobile Phone
    ];
    
    // Determine allowed assets for current user
    $allowedAssets = [];
    if ($isSystemAdmin) {
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
    <link href="assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/icons.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/metismenu.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/style.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/style_dark.css" rel="stylesheet" type="text/css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css">
    <script src="assets/js/modernizr.min.js"></script>
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
                        <span>
                            <img src="<?= get_setting($conDB, 'logo') ?>" alt="" height="22">
                        </span>
                        <i>
                            <img src="<?= get_setting($conDB, 'white_logo') ?>" alt="" height="28">
                        </i>
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
                                    <h4 class="m-t-0 header-title"><?= __('asset_inventory', 'Asset Inventory') ?></h4>
                                    <button id="btn-add-asset" type="button" class="btn btn-primary btn-sm waves-effect waves-light">
                                        <i class="mdi mdi-plus-circle mr-2"></i><?= __('add_asset', 'Add Asset') ?>
                                    </button>
                                </div>

                                <div id="response"></div>

                                <div class="row pb-3 border-bottom">
                                    <div class="col-md-4 mb-2">
                                        <label for="filterStatus" class="form-label small font-weight-bold d-block mb-1"><?= __('filter_by_status', 'Filter by Status') ?></label>
                                        <div class="asset_status"></div>
                                    </div>
                                    <div class="col-md-4 mb-2">
                                        <label for="filterType" class="form-label small font-weight-bold d-block mb-1"><?= __('filter_by_asset_type', 'Filter by Asset Type') ?></label>
                                        <div class="asset_type"></div>
                                    </div>
                                </div>

                                <table id="inventory_table" class="table table-striped table-bordered dt-responsive nowrap inventory_table" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                                    <thead>
                                        <tr>
                                            <th><?= __('id', 'ID') ?></th>
                                            <th></th>
                                            <th><?= __('tracking_id', 'Tracking ID') ?></th>
                                            <th><?= __('asset_type', 'Asset Type') ?></th>
                                            <th><?= __('serial_number', 'Serial Number') ?></th>
                                            <th><?= __('description', 'Description') ?></th>
                                            <th><?= __('status', 'Status') ?></th>
                                            <th><?= __('assigned_to', 'Assigned To') ?></th>
                                            <th><?= __('assigned_date', 'Assigned Date') ?></th>
                                            <th style="width: 30px"><?= __('action', 'Action') ?></th>
                                        </tr>
                                    </thead>
                                    <tbody id="inventory-body">
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
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
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.bootstrap5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfobject/2.2.0/pdfobject.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/signature_pad@4.0.0/dist/signature_pad.umd.min.js"></script>
    <script src="assets/js/jquery.app.js"></script>

    <script>
    (function() {
        const apiUrl = './includes/ajaxFile/ajaxAssetInventory.php';
        let inventoryTable;
        
        // User role information from backend
        const userRole = {
            userType: '<?= htmlspecialchars($userType) ?>',
            empType: '<?= htmlspecialchars($empType) ?>',
            isSystemAdmin: <?= $isSystemAdmin ? 'true' : 'false' ?>,
            // Allowed assets for this user
            allowedAssets: <?= json_encode($allowedAssets) ?>,
            // Excluded assets for this user (exclusive filtering)
            excludedAssets: function() {
                if (this.isSystemAdmin) return [];
                if (this.userType === 'it') return ['SIM Card', 'Car', 'Mobile Phone'];
                if (this.userType === 'gr_officer') return ['Laptop'];
                return [];
            }
        };
        
        // Check if user can access asset type
        function canAccessAsset(assetName) {
            if (userRole.isSystemAdmin) return true;
            if (userRole.allowedAssets.length === 0) return false;
            return userRole.allowedAssets.includes(assetName);
        }

        // Initialize DataTable

        function statusBadge(status, translatedStatus) {
            const cls = status === 'Assigned' ? 'success' : (status === 'Available' ? 'secondary' : 'warning');
            return `<span class="badge badge-${cls}">${translatedStatus || status}</span>`;
        }

        function loadInventory() {
            $.ajax({
                url: apiUrl,
                type: 'POST',
                data: { action: 'list_items' },
                dataType: 'json',
                success: function(resp) {
                    if (!resp.success) {
                        Swal.fire('Error', resp.message || 'Failed to load assets', 'error');
                        return;
                    }
                    
                    const rows = resp.data.items || [];
                    
                    // Destroy existing table if it exists
                    if ($.fn.dataTable.isDataTable('#inventory_table')) {
                        inventoryTable.destroy();
                    }
                    
                    // Clear the tbody and rebuild
                    $('#inventory-body').empty();
                    
                    if (rows.length > 0) {
                        rows.forEach(row => {
                            // Filter rows based on user role - exclude assets not allowed for this role
                            if (!userRole.isSystemAdmin) {
                                const excluded = userRole.excludedAssets();
                                if (excluded.includes(row.asset_name)) {
                                    return; // Skip this row
                                }
                            }
                            
                            const rowHtml = `<tr>
                                <td>${row.id}</td>
                                <td><input type="checkbox" name="status" class="asset-status-checkbox" value="${row.id}" /></td>
                                <td><strong>${row.tracking_id || '-'}</strong></td>
                                <td>${__(row.asset_name.toLowerCase().replace(/ /g, '_')) || '-'}</td>
                                <td>${row.serial_number || '-'}</td>
                                <td>${row.description || '-'}</td>
                                <td>${statusBadge(row.status, __(row.status.toLowerCase()))}</td>
                                <td>${row.employee_name || '-'}</td>
                                <td>${row.assigned_date || '-'}</td>
                                <td>
                                    <div class="btn-group dropdown">
                                        <a href="javascript: void(0);" class="table-action-btn dropdown-toggle arrow-none btn btn-light btn-sm" data-toggle="dropdown" aria-expanded="false">
                                            <i class="mdi mdi-dots-horizontal"></i>
                                        </a>
                                        <div class="dropdown-menu dropdown-menu-right">
                                            ${row.status !== 'Assigned' ? `
                                            <a href="javascript:void(0);" class="dropdown-item text-custom editAssetBtn" 
                                                data-id="${row.id}" 
                                                data-asset_id="${row.asset_id}" 
                                                data-asset_name="${(row.asset_name) || ''}"
                                                data-tracking="${row.tracking_id || ''}"
                                                data-serial="${row.serial_number || ''}"
                                                data-description="${(row.description || '').replace(/"/g, '&quot;')}"
                                                data-status="${row.status}">
                                                <i class="fa fa-edit mr-2 font-18 vertical-middle"></i>${__('edit', 'Edit')}
                                            </a>
                                            ` : ''}
                                            ${row.status !== 'Assigned' ? `
                                            <?php if($is_system_admin): ?>
                                            <a href="javascript:void(0);" class="dropdown-item text-danger deleteAjax" 
                                                data-tbl='asset_items'
                                                data-file='0'
                                                data-id="${row.id}">
                                                <i class="fa fa-trash mr-2 font-18 vertical-middle"></i>${__('delete', 'Delete')}
                                            </a>
                                            <?php endif; ?>
                                            ` : ''}
                                            ${row.status === 'Assigned' ? `
                                            <a href="javascript:void(0);" class="dropdown-item text-info print-asset-report" 
                                                data-id="${row.id}">
                                                <i class="fa fa-print mr-2 font-18 vertical-middle"></i>${__('print_report', 'Print Report')}
                                            </a>
                                            ` : ''}
                                            ${row.asset_id != 4 && row.status === 'Available' ? `
                                            <a href="javascript:void(0);" class="dropdown-item btn-assign" 
                                                data-id="${row.id}">
                                                <i class="fa fa-link mr-2 font-18 vertical-middle"></i>${__('assign', 'Assign')}
                                            </a>
                                            ` : ''}
                                            ${row.status === 'Assigned' ? `
                                            <a href="javascript:void(0);" class="dropdown-item text-warning btn-unassign" 
                                                data-id="${row.id}"
                                                data-tracking="${row.tracking_id}">
                                                <i class="fa fa-unlink mr-2 font-18 vertical-middle"></i>${__('unassign', 'Unassign')}
                                            </a>
                                            ` : ''}
                                            ${row.asset_id == 4 && row.status === 'Available' ? `
                                            <a href="javascript:void(0);" class="dropdown-item text-success btn-assign-driver" 
                                                data-id="${row.id}"
                                                data-tracking="${row.tracking_id}">
                                                <i class="fa fa-user mr-2 font-18 vertical-middle"></i>${__('assign_driver', 'Assign Driver')}
                                            </a>
                                            ` : ''}
                                        </div>
                                    </div>
                                </td>
                            </tr>`;
                            $('#inventory-body').append(rowHtml);
                        });
                    }
                    
                    // Reinitialize DataTable
                    initializeDataTable();
                    
                    // Populate filters
                    populateFilters(rows);
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', status, error, xhr.responseText);
                    Swal.fire('Error', 'Could not load assets: ' + (xhr.responseJSON?.message || error), 'error');
                }
            });
        }

        function initializeDataTable() {
            // Export button configuration
            const exportColumns = [2, 3, 4, 5, 6, 7, 8]; // Tracking ID, Asset Type, Serial, Description, Status, Assigned To, Assigned Date
            const buttonConfig = [];
            
            buttonConfig.push({
                extend: 'copy',
                text: '<i class="mdi mdi-content-copy text-info mr-1"></i>Copy',
                exportOptions: { columns: exportColumns }
            });
            buttonConfig.push({
                extend: 'excel',
                text: '<i class="mdi mdi-file-excel text-success mr-1"></i>Excel',
                exportOptions: { columns: exportColumns }
            });
            buttonConfig.push({
                extend: 'csv',
                text: '<i class="mdi mdi-file-document mr-1"></i>CSV',
                exportOptions: { columns: exportColumns }
            });
            buttonConfig.push({
                extend: 'pdf',
                text: '<i class="mdi mdi-file-pdf text-danger mr-1"></i>PDF',
                exportOptions: { columns: exportColumns }
            });
            buttonConfig.push({
                extend: 'print',
                text: '<i class="mdi mdi-printer text-primary mr-1"></i>Print',
                exportOptions: { columns: exportColumns }
            });
            
            inventoryTable = $('#inventory_table').DataTable({
                responsive: true,
                pageLength: 10,
                language: {
                    emptyTable: 'No assets found',
                    loadingRecords: 'Loading...',
                    processing: 'Processing...'
                },
                columnDefs: [
                    { visible: false, searchable: false, targets: 0 },
                    { orderable: false, targets: 1 },
                    { orderable: false, targets: 9 }
                ],
                dom: 'Bfrtip',
                buttons: [{
                    extend: 'collection',
                    className: 'btn-dark',
                    text: '<i class="icon-share-alt me-1 ti-xs"></i> Export',
                    buttons: buttonConfig
                }],
                bDestroy: true,
                language: {
                        search: `<span>${__('search')}:</span> _INPUT_`,
                        searchPlaceholder: `${__('search')}...`,
                        lengthMenu: `${__('show')} _MENU_ ${__('entries')}`,
                        info: `${__('showing')} _START_ ${__('to')} _END_ ${__('of')} _TOTAL_ ${__('entries')}`,
                        infoEmpty: `${__('showing')} 0 ${__('to')} 0 ${__('of')} 0 ${__('entries')}`,
                        infoFiltered: `(${__('filtered_from')} _MAX_ ${__('total_entries')})`,
                        paginate: {
                            first: __('first'),
                            last: __('last'),
                            next: __('next'),
                            previous: __('previous')
                        },
                        emptyTable: __('no_data_available_in_table'),
                        zeroRecords: __('no_matching_records_found'),
                        processing: `<div class="spinner-border text-primary" role="status"><span class="visually-hidden">${__('loading')}...</span></div>`
                    }
            });
        }

        function populateFilters(rows) {
            const selectOpt = '<select class="form-control select2-single" style="width: 100%;"><option value=""></option></select>';
            const statusOptions = new Set();
            const typeOptions = new Set();
            
            rows.forEach(row => {
                // Filter based on user role - only add options for assets the user can see
                if (!userRole.isSystemAdmin) {
                    const excluded = userRole.excludedAssets();
                    if (excluded.includes(row.asset_name)) {
                        return; // Skip this row
                    }
                }
                statusOptions.add(row.status);
                typeOptions.add(row.asset_name);
            });
            
            // Clear existing filters before adding new ones
            $('.asset_status').empty();
            $('.asset_type').empty();
            
            // Status filter
            const statusSelect = $(selectOpt).appendTo('.asset_status').on('change', function() {
                const val = $(this).val();
                inventoryTable.column(6).search(val, true, false).draw();
            });
            Array.from(statusOptions).sort().forEach(status => {
                statusSelect.append(`<option value="${__(status.toLowerCase().replace(/ /g, '_'))}">${__(status.toLowerCase().replace(/ /g, '_'))}</option>`);
            });
            statusSelect.select2({
                placeholder: __('select_status', 'Select Status'),
                allowClear: true,
                width: '100%'
            });
            
            // Asset Type filter
            const typeSelect = $(selectOpt).appendTo('.asset_type').on('change', function() {
                const val = $(this).val();
                inventoryTable.column(3).search(val, true, false).draw();
            });
            Array.from(typeOptions).sort().forEach(type => {
                typeSelect.append(`<option value="${__(type.toLowerCase().replace(/ /g, '_'))}">${__(type.toLowerCase().replace(/ /g, '_'))}</option>`);
            });
            typeSelect.select2({
                placeholder: __('select_asset_type', 'Select Asset Type'),
                allowClear: true,
                width: '100%'
            });
        }

        function initEmployeeSelect(selector) {
            $(selector).select2({
                width: '100%',
                dropdownParent: $('.swal2-container'),
                ajax: {
                    url: apiUrl,
                    dataType: 'json',
                    delay: 250,
                    data: params => ({ action: 'search_employees', q: params.term || '' }),
                    processResults: data => ({ results: data.data.results || [] })
                }
            });
        }

        async function openAssignDriverModal(itemId, trackingId) {
            const { value: form } = await Swal.fire({
                title: 'Assign Driver to Car',
                html: `
                    <div class="form-group text-left">
                        <label>Employee/Driver</label>
                        <select id="swal-driver-emp" class="form-control swal2-select2"></select>
                    </div>
                    <div class="form-group text-left">
                        <label>Assignment Date</label>
                        <input type="date" id="swal-driver-date" class="form-control" value="${new Date().toISOString().slice(0,10)}">
                    </div>
                    <div class="form-group text-left">
                        <label>Notes (Optional)</label>
                        <textarea id="swal-driver-notes" class="form-control" rows="2"></textarea>
                    </div>
                `,
                showCancelButton: true,
                preConfirm: () => {
                    const empId = $('#swal-driver-emp').val();
                    const date = $('#swal-driver-date').val();
                    const notes = $('#swal-driver-notes').val();
                    if (!empId) {
                        Swal.showValidationMessage('Employee is required');
                        return false;
                    }
                    return { emp_id: empId, rcv_date: date, notes: notes };
                },
                didOpen: () => initEmployeeSelect('#swal-driver-emp')
            });
            if (!form) return;
            Swal.showLoading();
            $.ajax({
                url: apiUrl,
                type: 'POST',
                data: { 
                    action: 'assign_driver', 
                    item_id: itemId,
                    tracking_id: trackingId,
                    emp_id: form.emp_id, 
                    rcv_date: form.rcv_date, 
                    notes: form.notes 
                },
                dataType: 'json',
                success: function(resp) {
                    Swal.close();
                    if (!resp.success) {
                        Swal.fire('Error', resp.message || 'Could not assign driver', 'error');
                        return;
                    }
                    Swal.fire('Success', 'Driver assigned successfully', 'success');
                    loadInventory();
                },
                error: function(xhr, status, error) {
                    Swal.close();
                    console.error('Assign Driver Error:', status, error, xhr.responseText);
                    Swal.fire('Error', 'Could not assign driver: ' + (xhr.responseJSON?.message || error), 'error');
                }
            });
        }

        async function openAssignModal(itemId) {
            const { value: form } = await Swal.fire({
                title: 'Assign Asset',
                html: `
                    <div class="form-group text-left">
                        <label>Employee</label>
                        <select id="swal-emp" class="form-control swal2-select2"></select>
                    </div>
                    <div class="form-group text-left">
                        <label>Assign Date</label>
                        <input type="date" id="swal-date" class="form-control" value="${new Date().toISOString().slice(0,10)}">
                    </div>
                    <div class="form-group text-left">
                        <label>Note</label>
                        <textarea id="swal-note" class="form-control" rows="2"></textarea>
                    </div>
                `,
                showCancelButton: true,
                preConfirm: () => {
                    const empId = $('#swal-emp').val();
                    const date = $('#swal-date').val();
                    const note = $('#swal-note').val();
                    if (!empId) {
                        Swal.showValidationMessage('Employee is required');
                        return false;
                    }
                    return { emp_id: empId, assigned_date: date, description: note };
                },
                didOpen: () => initEmployeeSelect('#swal-emp')
            });
            if (!form) return;
            Swal.showLoading();
            $.ajax({
                url: apiUrl,
                type: 'POST',
                data: { action: 'assign_item', item_id: itemId, emp_id: form.emp_id, assigned_date: form.assigned_date, description: form.description },
                dataType: 'json',
                success: function(resp) {
                    Swal.close();
                    if (!resp.success) {
                        Swal.fire('Error', resp.message || 'Could not assign asset', 'error');
                        return;
                    }
                    Swal.fire('Assigned', 'Tracking ID: ' + resp.data.tracking_id, 'success');
                    loadInventory();
                },
                error: function(xhr, status, error) {
                    Swal.close();
                    console.error('Assign Item Error:', status, error, xhr.responseText);
                    Swal.fire('Error', 'Could not assign asset: ' + (xhr.responseJSON?.message || error), 'error');
                }
            });
        }

        function registerAssetModal() {
            // First fetch asset types
            $.ajax({
                url: apiUrl,
                type: 'POST',
                data: { action: 'get_assets' },
                dataType: 'json',
                success: function(resp) {
                    if (!resp.success || !resp.data.assets) {
                        Swal.fire('Error', 'Could not load asset types', 'error');
                        return;
                    }
                    
                    let assets = resp.data.assets;
                    
                    // Filter assets based on user role - only show allowed assets
                    if (!userRole.isSystemAdmin) {
                        if (userRole.allowedAssets.length > 0) {
                            // Check if asset name contains any of the allowed keywords
                            assets = assets.filter(asset => {
                                return userRole.allowedAssets.some(allowed => 
                                    asset.name.toLowerCase().includes(allowed.toLowerCase())
                                );
                            });
                        } else {
                            assets = []; // No assets allowed
                        }
                    }
                    
                    if (assets.length === 0) {
                        Swal.fire('Access Denied', 'You do not have permission to add assets', 'warning');
                        return;
                    }
                    
                    let assetOptions = `<option value="">${__('select_asset_type')}</option>`;
                    assets.forEach(asset => {
                        assetOptions += `<option value="${asset.id}">${__(asset.name.toLowerCase().replace(/ /g, '_'))}</option>`;
                    });
                    
                    Swal.fire({
                        title: __('add_new_asset_item'),
                        html: `
                            <form id="registerAssetForm" class="text-left">
                                <div class="form-group">
                                    <label for="swal-asset-type">${__('asset_type')}</label>
                                    <select id="swal-asset-type" class="form-control">
                                        ${assetOptions}
                                    </select>
                                </div>
                                <div class="form-group" id="car-selector-group" style="display: none;">
                                    <label for="swal-car-select">${__('select_car')}</label>
                                    <select id="swal-car-select" class="form-control">
                                        <option value="">${__('select_car')}</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="swal-serial-number">${__('serial_number_identifier')}</label>
                                    <input id="swal-serial-number" class="form-control" placeholder="${__('enter_serial_number_identifier')}">
                                </div>
                                <div class="form-group">
                                    <label for="swal-description">${__('description')}</label>
                                    <textarea id="swal-description" class="form-control" placeholder="${__('enter_asset_description')}" rows="3"></textarea>
                                </div>
                            </form>
                        `,
                        showCancelButton: true,
                        cancelButtonText: __('cancel'),
                        confirmButtonText: __('add_asset'),
                        confirmButtonColor: '#3085d6',
                        showLoaderOnConfirm: true,
                        allowOutsideClick: () => !Swal.isLoading(),
                        didOpen: () => {
                            // Handle asset type change to show car selector
                            $('#swal-asset-type').on('change', function() {
                                const assetTypeId = $(this).find('option:selected').val();
                                console.log('Selected Asset Type ID:', assetTypeId);
                                const carGroup = $('#car-selector-group');
                                
                                // Only show car selector for Car asset (ID = 4)
                                if (assetTypeId == 4) {
                                    carGroup.show();
                                    // Fetch cars from database
                                    $.ajax({
                                        url: apiUrl,
                                        type: 'POST',
                                        data: { action: 'get_cars' },
                                        dataType: 'json',
                                        success: function(resp) {
                                            if (resp.success && resp.data.cars) {
                                                const carSelect = $('#swal-car-select');
                                                carSelect.empty();
                                                carSelect.append(`<option value="">${__('select_car')}</option>`);
                                                resp.data.cars.forEach(car => {
                                                    const isAssigned = car.is_assigned == 1;
                                                    const displayText = isAssigned ? 
                                                        `${car.maker_name} ${car.model} (${car.plate_no}) - ${__('assigned')} (${car.assigned_to})` :
                                                        `${car.maker_name} ${car.model} (${car.plate_no})`;
                                                    const option = $(`<option value="${car.id}" ${isAssigned ? 'disabled' : ''}>${displayText}</option>`);
                                                    carSelect.append(option);
                                                });
                                                
                                                // Apply Select2 to car selector
                                                carSelect.select2({
                                                    dropdownParent: $('.swal2-container'),
                                                    placeholder: __('search_and_select_a_car'),
                                                    allowClear: true,
                                                    width: '100%'
                                                });
                                            }
                                        }
                                    });
                                } else {
                                    carGroup.hide();
                                }
                            });
                        },
                        preConfirm: () => {
                            const assetId = document.getElementById('swal-asset-type').value;
                            const carId = document.getElementById('swal-car-select').value;
                            const serialNumber = document.getElementById('swal-serial-number').value;
                            const description = document.getElementById('swal-description').value;
                            
                            if (!assetId) {
                                Swal.showValidationMessage(__('please_select_an_asset_type'));
                                return false;
                            }
                            
                            // If it's Car type (ID = 4), require car selection
                            if (assetId == 4 && !carId) {
                                Swal.showValidationMessage(__('please_select_a_car'));
                                return false;
                            }
                            
                            if (!serialNumber && !carId) {
                                Swal.showValidationMessage(__('please_enter_serial_number'));
                                return false;
                            }
                            
                            return {
                                asset_id: assetId,
                                car_id: carId,
                                serial_number: serialNumber || carId,
                                description: description
                            };
                        },
                        allowOutsideClick: false
                    }).then((result) => {
                        if (result.isConfirmed) {
                            $.ajax({
                                type: 'POST',
                                url: apiUrl,
                                data: {
                                    action: 'create_item',
                                    asset_id: result.value.asset_id,
                                    car_id: result.value.car_id,
                                    serial_number: result.value.serial_number,
                                    description: result.value.description
                                },
                                dataType: 'json',
                                success: function(ajaxResponse) {
                                    if (ajaxResponse.success) {
                                        Swal.fire({
                                            title: 'Success',
                                            text: 'Tracking ID: ' + ajaxResponse.data.tracking_id,
                                            icon: 'success',
                                            allowOutsideClick: false
                                        }).then(() => {
                                            loadInventory();
                                        });
                                    } else {
                                        Swal.fire({
                                            title: 'Error',
                                            text: ajaxResponse.message || 'Could not add asset',
                                            icon: 'error',
                                            allowOutsideClick: false
                                        });
                                    }
                                },
                                error: function(xhr, status, error) {
                                    Swal.fire({
                                        title: 'Error',
                                        text: xhr.responseJSON?.message || 'Failed to add asset',
                                        icon: 'error',
                                        allowOutsideClick: false
                                    });
                                }
                            });
                        }
                    });
                },
                error: function(xhr, status, error) {
                    Swal.fire('Error', 'Could not load asset types: ' + (xhr.responseJSON?.message || error), 'error');
                }
            });
        }

        // Event handlers
        $(document).on('click', '#btn-add-asset', function() {
            registerAssetModal();
        });

        $(document).on('click', '.btn-assign', function() {
            openAssignModal($(this).data('id'));
        });

        $(document).on('click', '.btn-assign-driver', function() {
            openAssignDriverModal($(this).data('id'), $(this).data('tracking'));
        });

        $(document).on('click', '.btn-unassign', function() {
            const itemId = $(this).data('id');
            const trackingId = $(this).data('tracking');
            
            Swal.fire({
                title: __('return_asset_item', 'Return Asset Item'),
                html: `
                    <div class="text-left">
                        <div class="alert alert-info mb-3">
                            <strong>${__('instructions', 'Instructions')}:</strong><br>
                            1. ${__('click_print_report_to_print_asset_details', 'Click "Print Report" from the asset actions to print asset details and capture signature')}<br>
                            2. ${__('get_the_printed_document_signed', 'Get the printed document signed by the employee')}<br>
                            3. ${__('upload_the_signed_proof_below', 'Upload the signed proof document below')}
                        </div>
                        
                        <div class="form-group mb-3">
                            <label><strong>${__('asset_condition', 'Asset Condition')}</strong></label>
                            <select id="asset-condition" class="form-control" required>
                                <option value="">${__('select_condition', 'Select Condition')}</option>
                                <option value="Good">${__('good', 'Good')}</option>
                                <option value="Damage">${__('damage', 'Damage')}</option>
                                <option value="Lost">${__('lost', 'Lost')}</option>
                                <option value="Buy">${__('buy', 'Buy')}</option>
                                <option value="Other">${__('other', 'Other')}</option>
                            </select>
                        </div>
                        
                        <div class="form-group mb-3">
                            <label><strong>${__('return_date', 'Return Date')}</strong></label>
                            <input type="date" id="return-date" class="form-control" value="${new Date().toISOString().split('T')[0]}">
                        </div>
                        
                        <div class="form-group mb-3">
                            <label><strong>${__('proof_of_return', 'Proof of Return (Signed Document/Receipt)')}</strong></label>
                            <input type="file" id="proof-file" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
                            <small class="form-text text-muted">${__('accepted_formats', 'Upload the signed printed document here (PDF, JPG, PNG, DOC)')}</small>
                        </div>
                        
                        <div class="form-group">
                            <label><strong>${__('notes_optional', 'Notes (Optional)')}</strong></label>
                            <textarea id="return-notes" class="form-control" rows="2" placeholder="${__('add_return_notes', 'Add any return notes...')}"></textarea>
                        </div>
                    </div>
                `,
                width: '45%',
                showCancelButton: true,
                confirmButtonText: __('confirm_return', 'Confirm Return'),
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#d33',
                cancelButtonText: __('cancel', 'Cancel'),
                showLoaderOnConfirm: true,
                preConfirm: () => {
                    const assetCondition = document.getElementById('asset-condition').value;
                    const returnDate = document.getElementById('return-date').value;
                    const proofFile = document.getElementById('proof-file').files[0];
                    const returnNotes = document.getElementById('return-notes').value;
                    
                    if (!assetCondition) {
                        Swal.showValidationMessage(__('please_select_an_asset_condition', 'Please select an asset condition'));
                        return false;
                    }
                    
                    if (!returnDate) {
                        Swal.showValidationMessage(__('return_date_is_required', 'Return date is required'));
                        return false;
                    }
                    
                    if (!proofFile) {
                        Swal.showValidationMessage(__('proof_of_return_document_is_required', 'Proof of return document is required'));
                        return false;
                    }
                    
                    return {
                        assetCondition: assetCondition,
                        returnDate: returnDate,
                        proofFile: proofFile,
                        notes: returnNotes
                    };
                },
                allowOutsideClick: false
            }).then((result) => {
                if (result.isConfirmed) {
                    const formData = new FormData();
                    formData.append('action', 'unassign_item');
                    formData.append('item_id', itemId);
                    formData.append('tracking_id', trackingId);
                    formData.append('asset_condition', result.value.assetCondition);
                    formData.append('return_date', result.value.returnDate);
                    formData.append('proof_file', result.value.proofFile);
                    formData.append('notes', result.value.notes);
                    
                    $.ajax({
                        type: 'POST',
                        url: apiUrl,
                        data: formData,
                        contentType: false,
                        processData: false,
                        dataType: 'json',
                        success: function(resp) {
                            if (resp.success) {
                                Swal.fire({
                                    title: __('returned', 'Returned'),
                                    text: __('asset_item_returned_and_unassigned_successfully', 'Asset item returned and unassigned successfully'),
                                    icon: 'success',
                                    showCancelButton: true,
                                    confirmButtonText: __('print_report', 'Print Report'),
                                    cancelButtonText: __('done', 'Done'),
                                    confirmButtonColor: '#3085d6',
                                    cancelButtonColor: '#6c757d',
                                    allowOutsideClick: false
                                }).then((printResult) => {
                                    loadInventory();
                                    if (printResult.isConfirmed) {
                                        // Open print report for this asset
                                        window.open('asset_return_report.php?asset_id=' + resp.data.asset_record_id, '_blank');
                                    }
                                });
                            } else {
                                Swal.fire('Error', resp.message || 'Could not unassign asset', 'error');
                            }
                        },
                        error: function(xhr) {
                            Swal.fire('Error', xhr.responseJSON?.message || 'Could not unassign asset', 'error');
                        }
                    });
                }
            });
        });

        $(document).on('click', '.editAssetBtn', function() {
            const itemId = $(this).data('id');
            const assetId = $(this).data('asset_id');
            const assetName = $(this).data('asset_name');
            const serial = $(this).data('serial');
            const description = $(this).data('description');
            
            Swal.fire({
                title: __('edit_asset_item', 'Edit Asset Item'),
                html: `
                    <form id="editAssetForm" class="text-left">
                        <div class="form-group">
                            <label for="edit-asset-name">${__('asset_type', 'Asset Type')}</label>
                            <input type="text" class="form-control" value="${__(assetName.toLowerCase())}" readonly>
                        </div>
                        <div class="form-group">
                            <label for="edit-serial-number">${__('serial_number', 'Serial Number')}</label>
                            <input id="edit-serial-number" type="text" class="form-control" value="${serial}">
                        </div>
                        <div class="form-group">
                            <label for="edit-description">${__('description', 'Description')}</label>
                            <textarea id="edit-description" class="form-control" rows="3">${description}</textarea>
                        </div>
                    </form>
                `,
                showCancelButton: true,
                cancelButtonText: __('cancel', 'Cancel'),
                confirmButtonText: __('update', 'Update'),
                confirmButtonColor: '#28a745',
                showLoaderOnConfirm: true,
                preConfirm: () => {
                    const newSerial = document.getElementById('edit-serial-number').value;
                    const newDesc = document.getElementById('edit-description').value;
                    if (!newSerial) {
                        Swal.showValidationMessage('Serial number is required');
                        return false;
                    }
                    return { serial: newSerial, description: newDesc };
                },
                allowOutsideClick: false
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        type: 'POST',
                        url: apiUrl,
                        data: {
                            action: 'update_item',
                            item_id: itemId,
                            serial_number: result.value.serial,
                            description: result.value.description
                        },
                        dataType: 'json',
                        success: function(resp) {
                            if (resp.success) {
                                Swal.fire({
                                    title: __('updated', 'Updated!'), 
                                    text: __('asset_item_updated_successfully', 'Asset item updated successfully'),
                                    icon: 'success',
                                    confirmButtonText: __('ok', 'OK'),
                                    allowOutsideClick: false
                                }).then(() => {
                                    loadInventory();
                                });
                            } else {
                                Swal.fire({
                                    title: __('error', 'Error'),
                                    text: resp.message || __('could_not_update_asset', 'Could not update asset'),
                                    icon: 'error',
                                    allowOutsideClick: false,
                                    confirmButtonText: __('ok', 'OK')
                                });
                            }
                        },
                        error: function(xhr) {
                            Swal.fire({
                                title: __('error', 'Error'),
                                text: xhr.responseJSON?.message || __('failed_to_update_asset', 'Failed to update asset'),
                                icon: 'error',
                                allowOutsideClick: false,
                                confirmButtonText: __('ok', 'OK')
                            });
                        }
                    });
                }
            });
        });

        $(document).on('click', '.print-asset-report', function() {
            const itemId = $(this).data('id');
            
            // Get the asset tracking ID for the report
            $.ajax({
                type: 'POST',
                url: apiUrl,
                data: {
                    action: 'get_asset_record',
                    asset_id: itemId
                },
                dataType: 'json',
                success: function(resp) {
                    if (resp.success && resp.data && resp.data.tracking_id) {
                        const employeeAssetId = resp.data.employee_asset_id;
                        const reportUrl = 'asset_return_report.php?asset_id=' + employeeAssetId;
                        let signaturePad;
                        let uploadedSignatureImage = null;
                        
                        // Show modal to capture signature and optionally upload signed report before opening
                        Swal.fire({
                            title: __('asset_return_report', 'Asset Return Report'),
                            html: `
                                <div class="">
                                    <div class="alert alert-info mb-3">
                                        <strong>${__('instructions', 'Instructions')}:</strong><br>
                                        1. ${__('review_asset_details_then_click_confirm_to_open_report', 'Review asset details then click Confirm to open the report')}<br>
                                        2. ${__('draw_or_upload_signature_to_attach_as_proof', 'Draw or upload signature to attach as proof')}
                                    </div>
                                    <div class="form-group mb-3">
                                        <label><strong>${__('signature', 'Signature')}</strong></label>
                                        <ul class="nav nav-tabs mb-2" id="print-signature-tabs" role="tablist">
                                            <li class="nav-item" role="presentation">
                                                <button class="nav-link active" id="print-draw-tab" data-bs-toggle="tab" data-bs-target="#print-draw-pane" type="button" role="tab">${__('draw_signature', 'Draw Signature')}</button>
                                            </li>
                                            <li class="nav-item" role="presentation">
                                                <button class="nav-link" id="print-upload-tab" data-bs-toggle="tab" data-bs-target="#print-upload-pane" type="button" role="tab">${__('upload_signature', 'Upload Signature')}</button>
                                            </li>
                                        </ul>
                                        <div class="tab-content">
                                            <div class="tab-pane fade show active" id="print-draw-pane" role="tabpanel">
                                                <div id="print-signature-pad-container" style="border: 2px solid #ccc; border-radius: 4px; background: white; margin-bottom: 10px;">
                                                    <canvas id="print-signature-canvas" width="560" height="250" style="display: block; width: 560px; height: 250px; cursor: crosshair; touch-action: none; border-radius: 4px;"></canvas>
                                                </div>
                                                <small class="form-text text-muted">${__('draw_your_signature_above', 'Draw your signature above')}</small>
                                                <button type="button" id="print-clear-signature-btn" class="btn btn-sm btn-secondary mt-2">${__('clear_signature', 'Clear Signature')}</button>
                                            </div>
                                            <div class="tab-pane fade" id="print-upload-pane" role="tabpanel">
                                                <input type="file" id="print-signature-file" class="form-control mb-2" accept=".jpg,.jpeg,.png,.gif,.bmp">
                                                <small class="form-text text-muted">${__('select_signature_image_file', 'Select a signature image file (JPG, PNG, GIF, BMP)')}</small>
                                                <div id="print-uploaded-signature-preview" style="margin-top: 10px; display: none;">
                                                    <img id="print-signature-preview-img" src="" style="max-width: 100%; max-height: 200px; border: 1px solid #ddd; border-radius: 4px;">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            `,
                            // icon: 'info',
                            showCancelButton: true,
                            confirmButtonText: __('confirm_and_open_report', 'Confirm and Open Report'),
                            cancelButtonText: __('cancel', 'Cancel'),
                            confirmButtonColor: '#3085d6',
                            cancelButtonColor: '#6c757d',
                            allowOutsideClick: false,
                            width: '35%',
                            didOpen: () => {
                                const canvas = document.getElementById('print-signature-canvas');
                                signaturePad = new SignaturePad(canvas, {
                                    backgroundColor: 'rgb(255,255,255)'
                                });
                                document.getElementById('print-clear-signature-btn').addEventListener('click', () => {
                                    signaturePad.clear();
                                });
                                // Tabs toggle
                                const drawTab = document.getElementById('print-draw-tab');
                                const uploadTab = document.getElementById('print-upload-tab');
                                const drawPane = document.getElementById('print-draw-pane');
                                const uploadPane = document.getElementById('print-upload-pane');
                                drawTab.addEventListener('click', () => {
                                    drawTab.classList.add('active');
                                    uploadTab.classList.remove('active');
                                    drawPane.classList.add('show', 'active');
                                    uploadPane.classList.remove('show', 'active');
                                });
                                uploadTab.addEventListener('click', () => {
                                    uploadTab.classList.add('active');
                                    drawTab.classList.remove('active');
                                    uploadPane.classList.add('show', 'active');
                                    drawPane.classList.remove('show', 'active');
                                });
                                // Signature file upload
                                document.getElementById('print-signature-file').addEventListener('change', function(e) {
                                    const file = e.target.files[0];
                                    if (file) {
                                        const reader = new FileReader();
                                        reader.onload = function(event) {
                                            uploadedSignatureImage = event.target.result;
                                            const previewImg = document.getElementById('print-signature-preview-img');
                                            previewImg.src = uploadedSignatureImage;
                                            document.getElementById('print-uploaded-signature-preview').style.display = 'block';
                                        };
                                        reader.readAsDataURL(file);
                                    }
                                });
                            },
                            preConfirm: () => {
                                // Build form data to save signature prior to report open
                                const formData = new FormData();
                                formData.append('action', 'save_print_proof');
                                formData.append('item_id', itemId);
                                formData.append('tracking_id', resp.data.tracking_id);
                                if (employeeAssetId) { formData.append('employee_asset_id', employeeAssetId); }
                                let signature = null;
                                if (uploadedSignatureImage) {
                                    signature = uploadedSignatureImage;
                                } else {
                                    signature = signaturePad.toDataURL('image/png');
                                }
                                if (signature) {
                                    formData.append('signature', signature);
                                }
                                return new Promise((resolve) => {
                                    $.ajax({
                                        type: 'POST',
                                        url: apiUrl,
                                        data: formData,
                                        contentType: false,
                                        processData: false,
                                        dataType: 'json',
                                        success: function(saveResp) {
                                            if (!saveResp.success) {
                                                Swal.showValidationMessage(saveResp.message || __('failed_to_save_signature_or_proof', 'Failed to save signature/proof'));
                                                resolve(false);
                                                return;
                                            }
                                            resolve(true);
                                        },
                                        error: function(xhr) {
                                            Swal.showValidationMessage(xhr.responseJSON?.message || __('failed_to_save_signature_or_proof', 'Failed to save signature/proof'));
                                            resolve(false);
                                        }
                                    });
                                });
                            }
                        }).then((result) => {
                            if (result.isConfirmed) {
                                // Open report in new tab for printing/downloading
                                window.open(reportUrl, '_blank');
                            }
                        });
                    } else {
                        Swal.fire('Error', 'Could not find asset record for printing', 'error');
                    }
                },
                error: function(xhr) {
                    Swal.fire('Error', 'Failed to retrieve asset record', 'error');
                }
            });
        });

        // Initialize on document ready
        $(document).ready(function() {
            setTimeout(function() {
                loadInventory();
            }, 500);
        });
    })();
    </script>
</body>

</html>