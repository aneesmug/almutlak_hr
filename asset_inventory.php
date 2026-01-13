<?php
    require_once __DIR__ . '/includes/session_check.php';
    include(__DIR__ . '/includes/avatar_select.php');
?>

<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <title><?= $site_title ?> - Asset Inventory</title>
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
                                    <h4 class="m-t-0 header-title">Asset Inventory</h4>
                                    <button id="btn-add-asset" type="button" class="btn btn-primary btn-sm waves-effect waves-light">
                                        <i class="mdi mdi-plus-circle mr-2"></i>Add Asset
                                    </button>
                                </div>

                                <div id="response"></div>

                                <div class="row pb-3 border-bottom">
                                    <div class="col-md-4 mb-2">
                                        <label for="filterStatus" class="form-label small font-weight-bold d-block mb-1">Filter by Status</label>
                                        <div class="asset_status"></div>
                                    </div>
                                    <div class="col-md-4 mb-2">
                                        <label for="filterType" class="form-label small font-weight-bold d-block mb-1">Filter by Asset Type</label>
                                        <div class="asset_type"></div>
                                    </div>
                                </div>

                                <table id="inventory_table" class="table table-striped table-bordered dt-responsive nowrap inventory_table" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th></th>
                                            <th>Tracking ID</th>
                                            <th>Asset Type</th>
                                            <th>Serial Number</th>
                                            <th>Description</th>
                                            <th>Status</th>
                                            <th>Assigned To</th>
                                            <th>Assigned Date</th>
                                            <th style="width: 30px">Action</th>
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

        // Initialize DataTable
        

        function statusBadge(status) {
            const cls = status === 'Assigned' ? 'success' : (status === 'Available' ? 'secondary' : 'warning');
            return `<span class="badge badge-${cls}">${status}</span>`;
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
                            const rowHtml = `<tr>
                                <td>${row.id}</td>
                                <td><input type="checkbox" name="status" class="asset-status-checkbox" value="${row.id}" /></td>
                                <td><strong>${row.tracking_id || '-'}</strong></td>
                                <td>${row.asset_name || '-'}</td>
                                <td>${row.serial_number || '-'}</td>
                                <td>${row.description || '-'}</td>
                                <td>${statusBadge(row.status)}</td>
                                <td>${row.employee_name || '-'}</td>
                                <td>${row.assigned_date || '-'}</td>
                                <td>
                                    <div class="btn-group dropdown">
                                        <a href="javascript: void(0);" class="table-action-btn dropdown-toggle arrow-none btn btn-light btn-sm" data-toggle="dropdown" aria-expanded="false">
                                            <i class="mdi mdi-dots-horizontal"></i>
                                        </a>
                                        <div class="dropdown-menu dropdown-menu-right">
                                            <a href="javascript:void(0);" class="dropdown-item text-custom editAssetBtn" 
                                                data-id="${row.id}" 
                                                data-asset_id="${row.asset_id}" 
                                                data-asset_name="${row.asset_name || ''}"
                                                data-tracking="${row.tracking_id || ''}"
                                                data-serial="${row.serial_number || ''}"
                                                data-description="${(row.description || '').replace(/"/g, '&quot;')}"
                                                data-status="${row.status}">
                                                <i class="fa fa-edit mr-2 font-18 vertical-middle"></i>Edit
                                            </a>
                                            <a href="javascript:void(0);" class="dropdown-item text-danger deleteAssetBtn" 
                                                data-id="${row.id}" 
                                                data-tracking="${row.tracking_id}">
                                                <i class="fa fa-trash mr-2 font-18 vertical-middle"></i>Delete
                                            </a>
                                            <a href="javascript:void(0);" class="dropdown-item text-info print-asset-report" 
                                                data-id="${row.id}">
                                                <i class="fa fa-print mr-2 font-18 vertical-middle"></i>Print Report
                                            </a>
                                            ${row.status === 'Available' ? `
                                            <a href="javascript:void(0);" class="dropdown-item btn-assign" 
                                                data-id="${row.id}">
                                                <i class="fa fa-link mr-2 font-18 vertical-middle"></i>Assign
                                            </a>
                                            ` : ''}
                                            ${row.status === 'Assigned' ? `
                                            <a href="javascript:void(0);" class="dropdown-item text-warning btn-unassign" 
                                                data-id="${row.id}"
                                                data-tracking="${row.tracking_id}">
                                                <i class="fa fa-unlink mr-2 font-18 vertical-middle"></i>Unassign
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
                bDestroy: true
            });
        }

        function populateFilters(rows) {
            const selectOpt = '<select class="form-control select2-single" style="width: 100%;"><option value=""></option></select>';
            const statusOptions = new Set();
            const typeOptions = new Set();
            
            rows.forEach(row => {
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
                statusSelect.append(`<option value="${status}">${status}</option>`);
            });
            statusSelect.select2({
                placeholder: 'Select Status',
                allowClear: true,
                width: '100%'
            });
            
            // Asset Type filter
            const typeSelect = $(selectOpt).appendTo('.asset_type').on('change', function() {
                const val = $(this).val();
                inventoryTable.column(3).search(val, true, false).draw();
            });
            Array.from(typeOptions).sort().forEach(type => {
                typeSelect.append(`<option value="${type}">${type}</option>`);
            });
            typeSelect.select2({
                placeholder: 'Select Asset Type',
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
                    
                    const assets = resp.data.assets;
                    let assetOptions = '<option value="">-- Select Asset Type --</option>';
                    assets.forEach(asset => {
                        assetOptions += `<option value="${asset.id}">${asset.name}</option>`;
                    });
                    
                    Swal.fire({
                        title: 'Add New Asset Item',
                        html: `
                            <form id="registerAssetForm" class="text-left">
                                <div class="form-group">
                                    <label for="swal-asset-type">Asset Type</label>
                                    <select id="swal-asset-type" class="form-control">
                                        ${assetOptions}
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="swal-serial-number">Serial Number / Identifier</label>
                                    <input id="swal-serial-number" class="form-control" placeholder="Enter serial number or identifier">
                                </div>
                                <div class="form-group">
                                    <label for="swal-description">Description</label>
                                    <textarea id="swal-description" class="form-control" placeholder="Enter asset description" rows="3"></textarea>
                                </div>
                            </form>
                        `,
                        showCancelButton: true,
                        cancelButtonText: 'Cancel',
                        confirmButtonText: 'Add Asset',
                        showLoaderOnConfirm: true,
                        preConfirm: () => {
                            const assetId = document.getElementById('swal-asset-type').value;
                            const serialNumber = document.getElementById('swal-serial-number').value;
                            const description = document.getElementById('swal-description').value;
                            
                            if (!assetId) {
                                Swal.showValidationMessage('Please select an asset type');
                                return false;
                            }
                            if (!serialNumber) {
                                Swal.showValidationMessage('Please enter serial number');
                                return false;
                            }
                            
                            return {
                                asset_id: assetId,
                                serial_number: serialNumber,
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

        $(document).on('click', '.btn-unassign', function() {
            const itemId = $(this).data('id');
            const trackingId = $(this).data('tracking');
            let signaturePad;
            let uploadedSignatureImage = null;
            
            Swal.fire({
                title: 'Return Asset Item',
                html: `
                    <div class="text-left">
                        <div class="form-group mb-3">
                            <label><strong>Asset Condition</strong></label>
                            <select id="asset-condition" class="form-control" required>
                                <option value="">-- Select Condition --</option>
                                <option value="Good">Good</option>
                                <option value="Damage">Damage</option>
                                <option value="Lost">Lost</option>
                                <option value="Buy">Buy</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        
                        <div class="form-group mb-3">
                            <label><strong>Signature</strong></label>
                            <ul class="nav nav-tabs mb-2" id="signature-tabs" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link active" id="draw-tab" data-bs-toggle="tab" data-bs-target="#draw-pane" type="button" role="tab">Draw Signature</button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="upload-tab" data-bs-toggle="tab" data-bs-target="#upload-pane" type="button" role="tab">Upload Signature</button>
                                </li>
                            </ul>
                            <div class="tab-content">
                                <div class="tab-pane fade show active" id="draw-pane" role="tabpanel">
                                    <div id="signature-pad-container" style="border: 2px solid #ccc; border-radius: 4px; background: white; margin-bottom: 10px;">
                                        <canvas id="signature-canvas" width="400" height="150" style="display: block; cursor: crosshair; touch-action: none; border-radius: 4px;"></canvas>
                                    </div>
                                    <small class="form-text text-muted">Draw your signature above</small>
                                    <button type="button" id="clear-signature-btn" class="btn btn-sm btn-secondary mt-2">Clear Signature</button>
                                </div>
                                <div class="tab-pane fade" id="upload-pane" role="tabpanel">
                                    <input type="file" id="signature-file" class="form-control mb-2" accept=".jpg,.jpeg,.png,.gif,.bmp">
                                    <small class="form-text text-muted">Select a signature image file (JPG, PNG, GIF, BMP)</small>
                                    <div id="uploaded-signature-preview" style="margin-top: 10px; display: none;">
                                        <img id="signature-preview-img" src="" style="max-width: 100%; max-height: 200px; border: 1px solid #ddd; border-radius: 4px;">
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group mb-3">
                            <label><strong>Return Date</strong></label>
                            <input type="date" id="return-date" class="form-control" value="${new Date().toISOString().split('T')[0]}">
                        </div>
                        
                        <div class="form-group mb-3">
                            <label><strong>Proof of Return (Document/Receipt)</strong></label>
                            <input type="file" id="proof-file" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
                            <small class="form-text text-muted">Accepted formats: PDF, JPG, PNG, DOC</small>
                        </div>
                        
                        <div class="form-group">
                            <label><strong>Notes (Optional)</strong></label>
                            <textarea id="return-notes" class="form-control" rows="2" placeholder="Add any return notes..."></textarea>
                        </div>
                    </div>
                `,
                width: '600px',
                showCancelButton: true,
                confirmButtonText: 'Confirm Return',
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#d33',
                showLoaderOnConfirm: true,
                didOpen: (modal) => {
                    const canvas = document.getElementById('signature-canvas');
                    signaturePad = new SignaturePad(canvas, {
                        backgroundColor: 'rgb(255,255,255)'
                    });
                    
                    document.getElementById('clear-signature-btn').addEventListener('click', () => {
                        signaturePad.clear();
                    });
                    
                    // Handle tab switching
                    const drawTab = document.getElementById('draw-tab');
                    const uploadTab = document.getElementById('upload-tab');
                    const drawPane = document.getElementById('draw-pane');
                    const uploadPane = document.getElementById('upload-pane');
                    
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
                    
                    // Handle signature file upload
                    document.getElementById('signature-file').addEventListener('change', function(e) {
                        const file = e.target.files[0];
                        if (file) {
                            const reader = new FileReader();
                            reader.onload = function(event) {
                                uploadedSignatureImage = event.target.result;
                                const previewImg = document.getElementById('signature-preview-img');
                                previewImg.src = uploadedSignatureImage;
                                document.getElementById('uploaded-signature-preview').style.display = 'block';
                            };
                            reader.readAsDataURL(file);
                        }
                    });
                },
                preConfirm: () => {
                    const assetCondition = document.getElementById('asset-condition').value;
                    const returnDate = document.getElementById('return-date').value;
                    const proofFile = document.getElementById('proof-file').files[0];
                    const returnNotes = document.getElementById('return-notes').value;
                    
                    if (!assetCondition) {
                        Swal.showValidationMessage('Please select an asset condition');
                        return false;
                    }
                    
                    // Check if using uploaded signature or drawn signature
                    let signature = null;
                    
                    if (uploadedSignatureImage) {
                        signature = uploadedSignatureImage;
                    } else {
                        signature = signaturePad.toDataURL('image/png');
                        const isSignatureEmpty = signaturePad.isEmpty();
                        if (isSignatureEmpty) {
                            Swal.showValidationMessage('Please draw a signature or upload a signature image');
                            return false;
                        }
                    }
                    
                    if (!returnDate) {
                        Swal.showValidationMessage('Return date is required');
                        return false;
                    }
                    
                    if (!proofFile) {
                        Swal.showValidationMessage('Proof of return document is required');
                        return false;
                    }
                    
                    return {
                        assetCondition: assetCondition,
                        returnDate: returnDate,
                        signature: signature,
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
                    formData.append('signature', result.value.signature);
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
                                    title: 'Returned!',
                                    text: 'Asset item returned and unassigned successfully',
                                    icon: 'success',
                                    showCancelButton: true,
                                    confirmButtonText: 'Print Report',
                                    cancelButtonText: 'Done',
                                    confirmButtonColor: '#3085d6',
                                    cancelButtonColor: '#6c757d'
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
                title: 'Edit Asset Item',
                html: `
                    <form id="editAssetForm" class="text-left">
                        <div class="form-group">
                            <label for="edit-asset-name">Asset Type</label>
                            <input type="text" class="form-control" value="${assetName}" readonly>
                        </div>
                        <div class="form-group">
                            <label for="edit-serial-number">Serial Number</label>
                            <input id="edit-serial-number" type="text" class="form-control" value="${serial}">
                        </div>
                        <div class="form-group">
                            <label for="edit-description">Description</label>
                            <textarea id="edit-description" class="form-control" rows="3">${description}</textarea>
                        </div>
                    </form>
                `,
                showCancelButton: true,
                confirmButtonText: 'Update',
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
                                Swal.fire('Updated!', 'Asset item updated successfully', 'success').then(() => {
                                    loadInventory();
                                });
                            } else {
                                Swal.fire('Error', resp.message || 'Could not update asset', 'error');
                            }
                        },
                        error: function(xhr) {
                            Swal.fire('Error', xhr.responseJSON?.message || 'Failed to update asset', 'error');
                        }
                    });
                }
            });
        });

        $(document).on('click', '.deleteAssetBtn', function() {
            const itemId = $(this).data('id');
            const tracking = $(this).data('tracking');
            
            Swal.fire({
                title: 'Are you sure?',
                text: 'Delete asset with tracking ID: ' + tracking + '?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                confirmButtonText: 'Yes, Delete!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        type: 'POST',
                        url: apiUrl,
                        data: {
                            action: 'delete_item',
                            item_id: itemId
                        },
                        dataType: 'json',
                        success: function(resp) {
                            if (resp.success) {
                                Swal.fire('Deleted!', 'Asset item deleted successfully', 'success').then(() => {
                                    loadInventory();
                                });
                            } else {
                                Swal.fire('Error', resp.message || 'Could not delete asset', 'error');
                            }
                        },
                        error: function(xhr) {
                            Swal.fire('Error', xhr.responseJSON?.message || 'Failed to delete asset', 'error');
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
                    if (resp.success && resp.data && resp.data.employee_asset_id) {
                        const reportUrl = 'asset_return_report.php?asset_id=' + resp.data.employee_asset_id;
                        
                        // Show SweetAlert2 modal with print options
                        Swal.fire({
                            title: 'Asset Return Report',
                            html: `
                                <div class="text-center">
                                    <p>Choose an action for the asset report:</p>
                                </div>
                            `,
                            icon: 'info',
                            showCancelButton: true,
                            showDenyButton: true,
                            confirmButtonText: '<i class="mdi mdi-printer mr-2"></i>Print',
                            denyButtonText: '<i class="mdi mdi-eye mr-2"></i>Preview in New Tab',
                            cancelButtonText: 'Cancel',
                            confirmButtonColor: '#3085d6',
                            denyButtonColor: '#17a2b8',
                            cancelButtonColor: '#6c757d',
                            allowOutsideClick: false
                        }).then((result) => {
                            if (result.isConfirmed) {
                                // Print directly
                                $.ajax({
                                    url: reportUrl,
                                    method: 'GET',
                                    success: function(data) {
                                        const printWindow = window.open('', '', 'height=600,width=900');
                                        printWindow.document.write(data);
                                        printWindow.document.close();
                                        printWindow.print();
                                    },
                                    error: function() {
                                        Swal.fire('Error', 'Could not load report for printing', 'error');
                                    }
                                });
                            } else if (result.isDenied) {
                                // Open in new tab for preview
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