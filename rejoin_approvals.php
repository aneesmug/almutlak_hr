<?php
require_once __DIR__ . '/includes/session_check.php';
require_once __DIR__ . '/includes/helper_functions.php';

// Verify user is logged in and is a supervisor
if (empty($_SESSION['empid'])) {
    header('Location: index.php');
    exit;
}

$supervisor_emp_id = $_SESSION['empid'];
?>
<!doctype html>
<html lang="<?= $current_lang ?? 'en' ?>" <?= ($is_rtl ?? false) ? 'dir="rtl"' : '' ?>>
<head>
    <meta charset="utf-8" />
    <title><?= $site_title ?> - <?= __('rejoin_approvals', 'Rejoin Approvals') ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />

    <!-- App favicon -->
    <link rel="shortcut icon" href="<?=get_setting($conDB, 'favicon')?>">

    <!-- DataTables -->
    <link href="./plugins/datatables/dataTables.bootstrap4.min.css" rel="stylesheet" type="text/css" />
    <link href="./plugins/datatables/responsive.bootstrap4.min.css" rel="stylesheet" type="text/css" />
    <link href="./plugins/datatables/buttons.bootstrap4.min.css" rel="stylesheet" type="text/css" />

    <!-- Bootstrap Datepicker -->
    <link href="./plugins/bootstrap-datepicker/css/bootstrap-datepicker.min.css" rel="stylesheet" type="text/css" />

    <!-- App css -->
    <link href="assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/icons.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/metismenu.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/style.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/style_dark.css" rel="stylesheet" type="text/css" />
    
    <?php if ($is_rtl): ?>
        <link href="assets/css/style_rtl.css" rel="stylesheet" type="text/css" />
    <?php endif; ?>
    
    <style>
        /* Tab content display fix */
        .tab-pane {
            display: none;
        }
        
        .tab-pane.active {
            display: block;
        }
        
        /* DataTable width fix */
        .table-responsive {
            width: 100%;
        }
        
        table.dataTable {
            width: 100% !important;
        }
        
        /* Employee Information Container */
        .employee-info-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 25px;
            padding-bottom: 20px;
            border-bottom: 1px solid #e9ecef;
        }
        
        .employee-info-card {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 6px;
            border: 1px solid #e9ecef;
        }
        
        .info-label {
            font-size: 12px;
            font-weight: 600;
            color: #6c757d;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }
        
        .info-value {
            font-size: 18px;
            font-weight: 600;
            color: #212529;
        }
        
        /* Remarks Section */
        .remarks-section {
            margin-bottom: 25px;
            padding-bottom: 20px;
            border-bottom: 1px solid #e9ecef;
        }
        
        .remarks-label {
            font-size: 12px;
            font-weight: 600;
            color: #007bff;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .remarks-items {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        
        .remark-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px;
            background-color: #f8f9fa;
            border-radius: 6px;
            border: 1px solid #e9ecef;
        }
        
        .remark-item i {
            font-size: 20px;
            color: #6c757d;
            min-width: 20px;
        }
        
        .remark-item span {
            font-size: 14px;
            font-weight: 500;
            color: #495057;
        }
        
        /* Action button tabs styling - Card style like vacation */
        .action-cards-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }
        
        .action-card {
            position: relative;
            cursor: pointer;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            transition: all 0.3s ease;
            background-color: #f8f9fa;
        }
        
        .action-card:hover {
            border-color: #dee2e6;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .action-card input[type="radio"] {
            display: none;
        }
        
        .action-card-icon {
            font-size: 32px;
            margin-bottom: 10px;
            display: block;
        }
        
        .action-card-label {
            display: block;
            font-size: 14px;
            font-weight: 500;
            color: #495057;
            word-break: break-word;
        }
        
        /* Active state for approve */
        .action-card input[value="approve"]:checked + .action-card-content,
        .action-card.active[data-action="approve"] .action-card-content {
            color: #28a745;
        }
        
        .action-card input[value="approve"]:checked ~ .action-card-border,
        .action-card.active[data-action="approve"] .action-card-border {
            border-color: #28a745;
            background-color: #f0f9f5;
        }
        
        /* Active state for adjust */
        .action-card input[value="adjust"]:checked + .action-card-content,
        .action-card.active[data-action="adjust"] .action-card-content {
            color: #ffc107;
        }
        
        .action-card input[value="adjust"]:checked ~ .action-card-border,
        .action-card.active[data-action="adjust"] .action-card-border {
            border-color: #ffc107;
            background-color: #fffbf0;
        }
        
        /* Active state for reject */
        .action-card input[value="reject"]:checked + .action-card-content,
        .action-card.active[data-action="reject"] .action-card-content {
            color: #dc3545;
        }
        
        .action-card input[value="reject"]:checked ~ .action-card-border,
        .action-card.active[data-action="reject"] .action-card-border {
            border-color: #dc3545;
            background-color: #fdf5f5;
        }
        
        .action-card-border {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            background-color: #f8f9fa;
            pointer-events: none;
            transition: all 0.3s ease;
        }
        
        .action-card-content {
            position: relative;
            z-index: 1;
            transition: color 0.3s ease;
        }
        
        .action-card input[type="radio"]:checked ~ .action-card-border {
            border-width: 2px;
        }
        .action-card-border {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            background-color: #f8f9fa;
            pointer-events: none;
            transition: all 0.3s ease;
        }
        
        .action-card-content {
            position: relative;
            z-index: 1;
            transition: color 0.3s ease;
        }
        
        .action-card input[type="radio"]:checked ~ .action-card-border {
            border-width: 2px;
        }
    </style>
    
    <script> window.lang = <?= json_encode($GLOBALS['translations'] ?? []) ?>;</script>
</head>

<body class="enlarged" data-keep-enlarged="true">
    <div id="wrapper">
        <!-- Sidebar -->
        <div class="left side-menu">
            <div class="slimscroll-menu" id="remove-scroll">
                <div class="topbar-left">
                    <a href="dashboard.php" class="logo">
                        <span><img src="<?=get_setting($conDB, 'logo')?>" alt="" height="22"></span>
                        <i><img src="<?=get_setting($conDB, 'white_logo')?>" alt="" height="28"></i>
                    </a>
                </div>
                <?php include("./includes/main_menu.php"); ?>
            </div>
        </div>

        <div class="content-page">
            <!-- Top Bar -->
            <?php include("./includes/topbar.php"); ?>

            <!-- Page Content -->
            <div class="content">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-12">
                            <div class="card-box">
                                <h4 class="header-title mb-4">
                                    <i class="fa fa-plane-arrival"></i> <?= __('rejoin_approval_requests', 'Rejoin Approval Requests') ?>
                                </h4>

                                <!-- Filter Tabs -->
                                <ul class="nav nav-tabs mb-3" role="tablist">
                                    <li class="nav-item">
                                        <a class="nav-link active" href="#pending" data-toggle="tab" role="tab">
                                            <i class="fa fa-hourglass-start"></i> <?= __('pending_requests', 'Pending') ?>
                                            <span class="badge badge-warning ml-2" id="pending-count">0</span>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" href="#approved" data-toggle="tab" role="tab">
                                            <i class="fa fa-check-circle"></i> <?= __('approved_requests', 'Approved') ?>
                                            <span class="badge badge-success ml-2" id="approved-count">0</span>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" href="#rejected" data-toggle="tab" role="tab">
                                            <i class="fa fa-times-circle"></i> <?= __('rejected_requests', 'Rejected') ?>
                                            <span class="badge badge-danger ml-2" id="rejected-count">0</span>
                                        </a>
                                    </li>
                                </ul>

                                <!-- Tab Content -->
                                <div class="tab-content">
                                    <!-- Pending Requests -->
                                    <div class="tab-pane fade show active" id="pending" role="tabpanel">
                                        <div class="table-responsive">
                                            <table id="pendingRequestsTable" class="table table-striped table-hover">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th><?= __('employee_name', 'Employee Name') ?></th>
                                                        <th><?= __('employee_id', 'Employee ID') ?></th>
                                                        <th><?= __('planned_return_date', 'Planned Return') ?></th>
                                                        <th><?= __('requested_rejoin_date', 'Requested Rejoin') ?></th>
                                                        <th><?= __('reason', 'Reason') ?></th>
                                                        <th><?= __('submitted_date', 'Submitted') ?></th>
                                                        <th><?= __('actions', 'Actions') ?></th>
                                                    </tr>
                                                </thead>
                                                <tbody></tbody>
                                            </table>
                                        </div>
                                    </div>

                                    <!-- Approved Requests -->
                                    <div class="tab-pane fade" id="approved" role="tabpanel">
                                        <div class="table-responsive">
                                            <table id="approvedRequestsTable" class="table table-striped table-hover">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th><?= __('employee_name', 'Employee Name') ?></th>
                                                        <th><?= __('employee_id', 'Employee ID') ?></th>
                                                        <th><?= __('approved_date', 'Approved Date') ?></th>
                                                        <th><?= __('approval_note', 'Approval Note') ?></th>
                                                        <th><?= __('approved_at', 'Approved At') ?></th>
                                                    </tr>
                                                </thead>
                                                <tbody></tbody>
                                            </table>
                                        </div>
                                    </div>

                                    <!-- Rejected Requests -->
                                    <div class="tab-pane fade" id="rejected" role="tabpanel">
                                        <div class="table-responsive">
                                            <table id="rejectedRequestsTable" class="table table-striped table-hover">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th><?= __('employee_name', 'Employee Name') ?></th>
                                                        <th><?= __('employee_id', 'Employee ID') ?></th>
                                                        <th><?= __('rejection_reason', 'Rejection Reason') ?></th>
                                                        <th><?= __('rejected_at', 'Rejected At') ?></th>
                                                    </tr>
                                                </thead>
                                                <tbody></tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
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
    <script src="./plugins/bootstrap-datepicker/js/bootstrap-datepicker.min.js"></script>
    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/metisMenu.min.js"></script>
    <script src="assets/js/waves.js"></script>
    <script src="assets/js/jquery.slimscroll.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- DataTables -->
    <script src="./plugins/datatables/jquery.dataTables.min.js"></script>
    <script src="./plugins/datatables/dataTables.bootstrap4.min.js"></script>
    <script src="./plugins/datatables/dataTables.responsive.min.js"></script>
    <script src="./plugins/datatables/responsive.bootstrap4.min.js"></script>
    <!-- Select2 -->
    <script src="./plugins/select2/js/select2.min.js"></script>
    <script src="assets/js/jquery.core.js"></script>
    <script src="assets/js/jquery.app.js?t=<?= time() ?>"></script>

    <script>
        let pendingTable, approvedTable, rejectedTable;

        $(document).ready(function() {
            // Initialize DataTables
            pendingTable = $('#pendingRequestsTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: './includes/get_rejoin_requests.php',
                    type: 'POST',
                    data: function(d) {
                        d.status = 'pending';
                    }
                },
                columns: [
                    { data: 'emp_name' },
                    { data: 'emp_id' },
                    { data: 'return_date' },
                    { data: 'requested_rejoin_date' },
                    { data: 'requested_reason' },
                    { data: 'requested_at' },
                    { data: null, orderable: false, searchable: false }
                ],
                columnDefs: [
                    {
                        targets: 2,
                        render: function(data) {
                            return data ? new Date(data).toLocaleDateString() : '-';
                        }
                    },
                    {
                        targets: 3,
                        render: function(data) {
                            return data ? `<span class="badge badge-warning">${new Date(data).toLocaleDateString()}</span>` : '-';
                        }
                    },
                    {
                        targets: 4,
                        render: function(data) {
                            return data || '-';
                        }
                    },
                    {
                        targets: 5,
                        render: function(data) {
                            return data ? new Date(data).toLocaleDateString() : '-';
                        }
                    },
                    {
                        targets: 6,
                        render: function(data, type, row) {
                            return `<button class="btn btn-sm btn-primary" onclick="viewAndApproveRequest(${row.rejoin_request_id}, ${row.emp_id}, '${row.requested_rejoin_date}', '${(row.emp_name || '').replace(/'/g, "\\'")}', '${row.vac_type || ''}')">
                                <i class="fa fa-check"></i> Review
                            </button>`;
                        }
                    }
                ],
                pageLength: 10,
                lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
                order: [[5, 'desc']]
            });

            approvedTable = $('#approvedRequestsTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: './includes/get_rejoin_requests.php',
                    type: 'POST',
                    data: function(d) {
                        d.status = 'approved';
                    }
                },
                columns: [
                    { data: 'emp_name' },
                    { data: 'emp_id' },
                    { data: 'final_approved_date' },
                    { data: 'approval_note' },
                    { data: 'approved_at' }
                ],
                columnDefs: [
                    {
                        targets: 2,
                        render: function(data, type, row) {
                            const approvedDate = data || row.approved_date;
                            return approvedDate ? `<span class="badge badge-success">${new Date(approvedDate).toLocaleDateString()}</span>` : '-';
                        }
                    },
                    {
                        targets: 3,
                        render: function(data) {
                            return data || '-';
                        }
                    },
                    {
                        targets: 4,
                        render: function(data) {
                            return data ? new Date(data).toLocaleDateString() : '-';
                        }
                    }
                ],
                pageLength: 10,
                lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
                order: [[4, 'desc']]
            });

            rejectedTable = $('#rejectedRequestsTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: './includes/get_rejoin_requests.php',
                    type: 'POST',
                    data: function(d) {
                        d.status = 'rejected';
                    }
                },
                columns: [
                    { data: 'emp_name' },
                    { data: 'emp_id' },
                    { data: 'rejection_reason' },
                    { data: 'approved_at' }
                ],
                columnDefs: [
                    {
                        targets: 2,
                        render: function(data) {
                            return data || '-';
                        }
                    },
                    {
                        targets: 3,
                        render: function(data) {
                            return data ? new Date(data).toLocaleDateString() : '-';
                        }
                    }
                ],
                pageLength: 10,
                lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
                order: [[3, 'desc']]
            });

            // Handle tab clicks
            $('.nav-link').on('click', function(e) {
                e.preventDefault();
                
                // Remove active from all tabs
                $('.nav-link').removeClass('active');
                $('.tab-pane').removeClass('active show');
                
                // Add active to clicked tab
                $(this).addClass('active');
                
                // Show corresponding content
                const target = $(this).attr('href');
                $(target).addClass('active show');
                
                // Redraw the visible DataTable after tab is shown
                setTimeout(function() {
                    if (target === '#pending' && pendingTable) {
                        pendingTable.columns.adjust().responsive.recalc();
                    } else if (target === '#approved' && approvedTable) {
                        approvedTable.columns.adjust().responsive.recalc();
                    } else if (target === '#rejected' && rejectedTable) {
                        rejectedTable.columns.adjust().responsive.recalc();
                    }
                }, 150);
            });
        });

        function viewAndApproveRequest(rejoinRequestId, empId, rejoinDate, empName, vacationType) {
            // Determine vacation type label and icon
            const vacationTypeLabel = vacationType && vacationType.toLowerCase() === 'fly' ? '<?= __("fly_vacation", "Fly Vacation") ?>' : '<?= __("local_vacation", "Local Vacation") ?>';
            const vacationIcon = vacationType && vacationType.toLowerCase() === 'fly' ? 'fa-plane' : 'fa-map-marker';
            
            // Open approval modal
            Swal.fire({
                title: '<?= __("approve_rejoin_request", "Approve Rejoin Request") ?>',
                html: `
                    <div class="employee-info-container">
                        <div class="employee-info-card">
                            <div class="info-label"><?= __("employee_name", "Employee Name") ?></div>
                            <div class="info-value">${empName}</div>
                        </div>
                        <div class="employee-info-card">
                            <div class="info-label"><?= __("employee_id", "Employee ID") ?></div>
                            <div class="info-value">${empId}</div>
                        </div>
                    </div>
                    <div class="remarks-section">
                        <div class="remarks-label"><i class="fa fa-pencil"></i> <?= __("remarks", "Remarks") ?></div>
                        <div class="remarks-items">
                            <div class="remark-item">
                                <i class="fa ${vacationIcon}"></i>
                                <span>${vacationTypeLabel}</span>
                            </div>
                            <div class="remark-item">
                                <i class="fa fa-calendar"></i>
                                <span>${rejoinDate}</span>
                            </div>
                        </div>
                    </div>
                    <div class="form-group text-left" style="margin-top: 20px;">
                        <label><?= __("action", "Action") ?></label><br>
                        <div class="action-cards-container">
                            <label class="action-card active" data-action="approve">
                                <input type="radio" name="action" id="actionApprove" value="approve" checked>
                                <div class="action-card-content">
                                    <span class="action-card-icon"><i class="fa fa-check"></i></span>
                                    <span class="action-card-label"><?= __("approve_immediately", "Approve Immediately") ?></span>
                                </div>
                                <div class="action-card-border"></div>
                            </label>
                            <label class="action-card" data-action="adjust">
                                <input type="radio" name="action" id="actionAdjust" value="adjust">
                                <div class="action-card-content">
                                    <span class="action-card-icon"><i class="fa fa-calendar"></i></span>
                                    <span class="action-card-label"><?= __("allow_adjustment", "Allow ±3 Days Adjustment") ?></span>
                                </div>
                                <div class="action-card-border"></div>
                            </label>
                            <label class="action-card" data-action="reject">
                                <input type="radio" name="action" id="actionReject" value="reject">
                                <div class="action-card-content">
                                    <span class="action-card-icon"><i class="fa fa-times"></i></span>
                                    <span class="action-card-label"><?= __("reject_request", "Reject Request") ?></span>
                                </div>
                                <div class="action-card-border"></div>
                            </label>
                        </div>
                    </div>
                    <div class="form-group text-left" id="approvalNoteDiv" style="display:none;">
                        <label for="approvalNote"><?= __("approval_note", "Approval Note") ?> (<?= __("optional", "Optional") ?>)</label>
                        <textarea class="form-control" id="approvalNote" rows="3" placeholder="<?= __("add_note", "Add a note...") ?>"></textarea>
                    </div>
                    <div class="form-group text-left" id="adjustmentDiv" style="display:none;">
                        <label for="adjustmentDate"><?= __("select_adjustment_date", "Select Adjustment Date") ?> <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="adjustmentDate" placeholder="<?= __("select_date", "Select date...") ?>" readonly>
                        <small class="text-muted" id="adjustmentRangeText" style="margin-top: 10px; display: block;"></small>
                        <label for="adjustmentNote" style="margin-top: 15px;"><?= __("adjustment_note", "Adjustment Note") ?> (<?= __("optional", "Optional") ?>)</label>
                        <textarea class="form-control" id="adjustmentNote" rows="2" placeholder="<?= __("explain_adjustment_window", "Explain the adjustment window...") ?>"></textarea>
                    </div>
                    <div class="form-group text-left" id="rejectionReasonDiv" style="display:none;">
                        <label for="rejectionReason"><?= __("rejection_reason", "Rejection Reason") ?> <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="rejectionReason" rows="3" placeholder="<?= __("reason_required", "Please provide a reason...") ?>"></textarea>
                    </div>
                `,
                showCancelButton: true,
                confirmButtonText: '<?= __("submit_rejoin", "Submit") ?>',
                cancelButtonText: '<?= __("cancel", "Cancel") ?>',
                width:"50%",
                allowOutsideClick: false,
                preConfirm: () => {
                    const action = $('input[name="action"]:checked').val();
                    const approvalNote = $('#approvalNote').val();
                    const adjustmentDate = $('#adjustmentDate').val();
                    const adjustmentNote = $('#adjustmentNote').val();
                    const rejectionReason = $('#rejectionReason').val();

                    if (action === 'adjust' && !adjustmentDate) {
                        Swal.showValidationMessage('<?= __("adjustment_date_required", "Please select an adjustment date") ?>');
                        return false;
                    }

                    if (action === 'reject' && !rejectionReason) {
                        Swal.showValidationMessage('<?= __("rejection_reason_required", "Rejection reason is required") ?>');
                        return false;
                    }

                    return {
                        action: action,
                        approval_note: approvalNote,
                        adjustment_date: adjustmentDate,
                        adjustment_note: adjustmentNote,
                        rejection_reason: rejectionReason
                    };
                },
                didOpen: () => {
                    // Calculate adjustment range (±3 days from requested date)
                    const calculateAdjustmentRange = () => {
                        const reqDate = new Date(rejoinDate);
                        const fromDate = new Date(reqDate);
                        // fromDate.setDate(fromDate.getDate() - 3);
                        const toDate = new Date(reqDate);
                        toDate.setDate(toDate.getDate() + 3);
                        
                        const formatDate = (date) => date.toLocaleDateString('<?= $current_lang === "ar" ? "ar-SA" : "en-US" ?>');
                        return {
                            from: formatDate(fromDate),
                            to: formatDate(toDate),
                            fromObj: fromDate,
                            toObj: toDate
                        };
                    };

                    const range = calculateAdjustmentRange();
                    const rangeText = `<?= __("adjustment_window", "Employee can select date between") ?> ${range.from} <?= __("and", "and") ?> ${range.to}`;

                    // Initialize datepicker for adjustment using bootstrap-datepicker
                    $('#adjustmentDate').datepicker({
                        format: "yyyy-mm-dd",
                        todayHighlight: true,
                        autoclose: true,
                        startDate: range.fromObj,
                        endDate: range.toObj
                    });

                    // Handle radio button clicks to toggle card styles and show/hide fields
                    $('input[name="action"]').on('change', function() {
                        const action = $(this).val();
                        
                        // Update card styles
                        $('.action-card').removeClass('active');
                        $('input[name="action"]:checked').closest('.action-card').addClass('active');
                        
                        // Show/hide fields based on action selection
                        $('#approvalNoteDiv').toggle(action === 'approve');
                        $('#adjustmentDiv').toggle(action === 'adjust');
                        $('#rejectionReasonDiv').toggle(action === 'reject');
                        
                        if (action === 'adjust') {
                            $('#adjustmentRangeText').text(rangeText).show();
                            // Set focus to datepicker
                            setTimeout(() => $('#adjustmentDate').focus(), 100);
                        }
                    });
                    
                    // Set initial card state
                    $('input[name="action"]:checked').closest('.action-card').addClass('active');
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    processRejoinApproval(rejoinRequestId, result.value);
                }
            });
        }

        function processRejoinApproval(rejoinRequestId, data) {
            // Show loading state
            Swal.fire({
                title: 'Processing...',
                html: 'Processing rejoin approval and sending notification emails...',
                icon: 'info',
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            $.ajax({
                url: './includes/ajaxFile/leaveHandler.php',
                type: 'POST',
                data: {
                    ajaxType: 'processRejoinApproval',
                    rejoin_request_id: rejoinRequestId,
                    action: data.action,
                    approval_note: data.approval_note,
                    adjustment_date: data.adjustment_date,
                    adjustment_note: data.adjustment_note,
                    rejection_reason: data.rejection_reason
                },
                dataType: 'JSON',
                success: function(response) {
                    Swal.fire({
                        icon: response.type === 'success' ? 'success' : 'error',
                        title: response.title || response.type,
                        text: response.message,
                        confirmButtonText: '<?= __("ok", "OK") ?>'
                    }).then(() => {
                        if (response.type === 'success') {
                            loadRejoinRequests(); // Reload the tables
                        }
                    });
                },
                error: function() {
                    Swal.fire('<?= __("error", "Error") ?>', '<?= __("request_failed", "Request failed") ?>', 'error');
                }
            });
        }
    </script>
</body>
</html>
