<?php
/**
 * ================================================================
 * ALL EMPLOYEE EVALUATIONS REPORT
 * ================================================================
 * 
 * DESCRIPTION:
 * This page displays a comprehensive report of all employee evaluations
 * across all departments. Provides filtering and search capabilities.
 * 
 * ACCESS CONTROL:
 * - Only accessible to hr_recruitment, hr_supervisor, hr_senior_bp, and GM roles
 * - Regular managers can only see evaluations for their department
 * 
 * FEATURES:
 * - View all employee evaluations in a DataTable
 * - Filter by department, employee, date range, score range
 * - Export to Excel/PDF
 * - View detailed evaluation breakdown
 * - Color-coded performance indicators
 * 
 * CREATED: November 9, 2025
 * ================================================================
 */

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/session_check.php';

// ================================================================
// ACCESS CONTROL - Only HR Recruitment and GM Allowed
// ================================================================
$allowed_roles = ['hr_recruitment', 'hr_supervisor', 'hr_senior_bp', 'gm', 'administrator'];
$has_access = in_array($user_role, $allowed_roles) || $user_type == 'gm' || $user_type == 'administrator';

if (!$has_access) {
    header("Location: ./dashboard.php");
    exit();
}

// Check if user should only see their department
$is_dept_restricted = $isDeptManager && !in_array($user_role, ['hr_recruitment', 'hr_supervisor', 'hr_senior_bp', 'gm', 'administrator']);

// ================================================================
// GET DEPARTMENTS FOR FILTER
// ================================================================
$departments = [];
try {
    if ($is_dept_restricted) {
        $stmt = $pdo->prepare("SELECT id, dep_nme, dep_nme_ar FROM department WHERE id = ? ORDER BY dep_nme ASC");
        $stmt->execute([$user_dept]);
    } else {
        $stmt = $pdo->query("SELECT id, dep_nme, dep_nme_ar FROM department ORDER BY dep_nme ASC");
    }
    $departments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching departments: " . $e->getMessage());
}

?>
<!DOCTYPE html>
<html lang="<?= $current_lang ?? 'en' ?>" <?= ($is_rtl ?? false) ? 'dir="rtl"' : '' ?>>

<head>
    <meta charset="utf-8" />
    <title><?= $site_title ?> - Employee Evaluations Report</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta content="Al-Mutlak HR System" name="description" />
    <meta content="Anees Afzal" name="author" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />

    <!-- App favicon -->
    <link rel="shortcut icon" href="<?=get_setting($conDB, 'favicon')?>">

    <!-- DataTables -->
    <link href="./plugins/datatables/dataTables.bootstrap4.min.css" rel="stylesheet" type="text/css" />
    <link href="./plugins/datatables/buttons.bootstrap4.min.css" rel="stylesheet" type="text/css" />
    <link href="./plugins/datatables/responsive.bootstrap4.min.css" rel="stylesheet" type="text/css" />

    <!-- Plugins css -->
    <link href="./plugins/select2/css/select2.min.css" rel="stylesheet" type="text/css" />

    <!-- App css -->
    <link href="assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/icons.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/metismenu.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/style.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/style_dark.css" rel="stylesheet" type="text/css" />
    <?php if ($is_rtl): ?>
        <link href="assets/css/style_rtl.css" rel="stylesheet" type="text/css" />
    <?php endif; ?>
    <script src="assets/js/modernizr.min.js"></script>
    <script>window.lang = <?= json_encode($GLOBALS['translations'] ?? []) ?>;</script>

    <style>
        .score-badge {
            font-size: 14px;
            padding: 6px 12px;
        }
        .filter-section {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
    </style>
</head>

<body class="enlarged" data-keep-enlarged="true">
    <!-- Begin page -->
    <div id="wrapper">
        <!-- ========== Left Sidebar Start ========== -->
        <div class="left side-menu">
            <div class="slimscroll-menu" id="remove-scroll">
                <!-- LOGO -->
                <div class="topbar-left">
                    <a href="dashboard.php" class="logo">
                        <span>
                            <img src="<?=get_setting($conDB, 'logo')?>" alt="" height="22">
                        </span>
                        <i>
                            <img src="<?=get_setting($conDB, 'white_logo')?>" alt="" height="28">
                        </i>
                    </a>
                </div>
                
                <!--- Sidemenu -->
                <?php include("./includes/main_menu.php"); ?>
                <!-- Sidebar -->

                <div class="clearfix"></div>
            </div>
            <!-- Sidebar -left -->
        </div>
        <!-- Left Sidebar End -->

        <!-- ============================================================== -->
        <!-- Start right Content here -->
        <!-- ============================================================== -->

        <div class="content-page">

            <!-- Top Bar Start -->
            <?php include("./includes/topbar.php"); ?>
            <!-- Top Bar End -->

            <!-- Start Page content -->
            <div class="content">
                <div class="container-fluid">
                    
                    <!-- Page Title -->
                    <div class="row">
                        <div class="col-12">
                            <div class="page-title-box">
                                <h4 class="page-title">Employee Performance Evaluations Report</h4>
                                <ol class="breadcrumb p-0 m-0">
                                    <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                                    <li class="breadcrumb-item active">Employee Evaluations Report</li>
                                </ol>
                            </div>
                        </div>
                    </div>

                    <!-- Filters Section -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body filter-section">
                                    <div class="row">
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label for="filterDepartment">Department</label>
                                                <select class="form-control select2" id="filterDepartment">
                                                    <option value="">All Departments</option>
                                                    <?php foreach ($departments as $dept): ?>
                                                        <option value="<?= $dept['id'] ?>">
                                                            <?= htmlspecialchars($dept['dep_nme']) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label for="filterEmployee">Employee</label>
                                                <input type="text" class="form-control" id="filterEmployee" placeholder="Search by name or ID">
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-2">
                                            <div class="form-group">
                                                <label for="filterFromDate">From Date</label>
                                                <input type="date" class="form-control" id="filterFromDate">
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-2">
                                            <div class="form-group">
                                                <label for="filterToDate">To Date</label>
                                                <input type="date" class="form-control" id="filterToDate">
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-2">
                                            <div class="form-group">
                                                <label for="filterScore">Min Score</label>
                                                <select class="form-control" id="filterScore">
                                                    <option value="">All Scores</option>
                                                    <option value="90">90+ (Excellent)</option>
                                                    <option value="70">70+ (Good)</option>
                                                    <option value="50">50+ (Average)</option>
                                                    <option value="0">Below 50 (Poor)</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-12">
                                            <button type="button" class="btn btn-primary" id="applyFilters">
                                                <i class="mdi mdi-filter"></i> Apply Filters
                                            </button>
                                            <button type="button" class="btn btn-secondary" id="resetFilters">
                                                <i class="mdi mdi-refresh"></i> Reset
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Evaluations Table -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body">
                                    <h4 class="header-title mb-4">All Employee Evaluations</h4>
                                    
                                    <table id="evaluationsTable" class="table table-striped table-bordered dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Employee ID</th>
                                                <th>Employee Name</th>
                                                <th>Department</th>
                                                <th>Position</th>
                                                <th>Evaluated By</th>
                                                <th>Total Score</th>
                                                <th>Evaluation Date</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <!-- Data loaded via AJAX -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                </div> <!-- container -->

            </div> <!-- content -->

            <footer class="footer">
                <?=$site_footer?>
            </footer>

        </div>
        <!-- ============================================================== -->
        <!-- End Right content here -->
        <!-- ============================================================== -->

    </div>
    <!-- END wrapper -->

    <!-- Evaluation Details Modal -->
    <div class="modal fade" id="evaluationModal" tabindex="-1" role="dialog" aria-labelledby="evaluationModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="evaluationModalLabel">Evaluation Details</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body" id="evaluationModalBody">
                    <div class="text-center">
                        <div class="spinner-border text-primary" role="status">
                            <span class="sr-only">Loading...</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- jQuery  -->
    <script src="assets/js/jquery.min.js"></script>
    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/metisMenu.min.js"></script>
    <script src="assets/js/waves.js"></script>
    <script src="assets/js/jquery.slimscroll.js"></script>

    <!-- Required datatable js -->
    <script src="./plugins/datatables/jquery.dataTables.min.js"></script>
    <script src="./plugins/datatables/dataTables.bootstrap4.min.js"></script>
    <!-- Buttons examples -->
    <script src="./plugins/datatables/dataTables.buttons.min.js"></script>
    <script src="./plugins/datatables/buttons.bootstrap4.min.js"></script>
    <script src="./plugins/datatables/jszip.min.js"></script>
    <script src="./plugins/datatables/pdfmake.min.js"></script>
    <script src="./plugins/datatables/vfs_fonts.js"></script>
    <script src="./plugins/datatables/buttons.html5.min.js"></script>
    <script src="./plugins/datatables/buttons.print.min.js"></script>
    <!-- Responsive examples -->
    <script src="./plugins/datatables/dataTables.responsive.min.js"></script>
    <script src="./plugins/datatables/responsive.bootstrap4.min.js"></script>

    <!-- Select2 -->
    <script src="./plugins/select2/js/select2.min.js"></script>

    <!-- App js -->
    <script src="assets/js/jquery.core.js"></script>
    <script src="assets/js/jquery.app.js"></script>

    <script>
    $(document).ready(function() {
        
        // Initialize Select2
        $('.select2').select2({
            placeholder: "Select an option",
            allowClear: true
        });

        // Initialize DataTable
        var table = $('#evaluationsTable').DataTable({
            dom: "Bfrtip",
            buttons: [
                {
                    extend: 'excel',
                    title: 'Employee Evaluations Report',
                    className: 'btn-success',
                    exportOptions: {
                        columns: [1, 2, 3, 4, 5, 6, 7]
                    }
                },
                {
                    extend: 'pdf',
                    title: 'Employee Evaluations Report',
                    className: 'btn-danger',
                    exportOptions: {
                        columns: [1, 2, 3, 4, 5, 6, 7]
                    }
                },
                {
                    extend: 'print',
                    title: 'Employee Evaluations Report',
                    className: 'btn-dark',
                    exportOptions: {
                        columns: [1, 2, 3, 4, 5, 6, 7]
                    }
                }
            ],
            processing: true,
            serverSide: false,
            ajax: {
                url: './includes/ajaxFile/ajaxEvaluationReport.php',
                type: 'POST',
                data: function(d) {
                    d.action = 'get_all_evaluations';
                    d.dept_id = $('#filterDepartment').val();
                    d.employee_search = $('#filterEmployee').val();
                    d.from_date = $('#filterFromDate').val();
                    d.to_date = $('#filterToDate').val();
                    d.min_score = $('#filterScore').val();
                    d.is_dept_restricted = <?= $is_dept_restricted ? 'true' : 'false' ?>;
                    d.user_dept = <?= $user_dept ?>;
                }
            },
            columns: [
                { data: 'id' },
                { data: 'employee_emp_id' },
                { data: 'employee_name' },
                { data: 'dept_name' },
                { data: 'employee_position' },
                { data: 'manager_name' },
                { 
                    data: 'total_score',
                    render: function(data, type, row) {
                        var badgeClass = 'success';
                        if (data < 50) badgeClass = 'danger';
                        else if (data < 70) badgeClass = 'warning';
                        else if (data < 90) badgeClass = 'info';
                        
                        return '<span class="badge badge-' + badgeClass + ' score-badge">' + data + '/100</span>';
                    }
                },
                { data: 'created_at' },
                {
                    data: null,
                    orderable: false,
                    render: function(data, type, row) {
                        return '<button class="btn btn-sm btn-primary view-eval-details" data-id="' + row.id + '" data-toggle="modal" data-target="#evaluationModal"><i class="mdi mdi-eye"></i> View</button>';
                    }
                }
            ],
            order: [[0, 'desc']],
            columnDefs: [
                {
                    targets: [0],
                    visible: false
                }
            ],
            language: {
                processing: '<div class="spinner-border text-primary" role="status"><span class="sr-only">Loading...</span></div>'
            }
        });

        // Apply filters
        $('#applyFilters').on('click', function() {
            table.ajax.reload();
        });

        // Reset filters
        $('#resetFilters').on('click', function() {
            $('#filterDepartment').val('').trigger('change');
            $('#filterEmployee').val('');
            $('#filterFromDate').val('');
            $('#filterToDate').val('');
            $('#filterScore').val('');
            table.ajax.reload();
        });

        // Load evaluation details when view button is clicked
        $(document).on('click', '.view-eval-details', function() {
            var evalId = $(this).data('id');
            
            $('#evaluationModalBody').html('<div class="text-center"><div class="spinner-border text-primary" role="status"><span class="sr-only">Loading...</span></div></div>');
            
            $.ajax({
                url: 'includes/ajaxFile/ajaxEvaluation.php',
                method: 'POST',
                data: { 
                    action: 'get_evaluation_details', 
                    evaluation_id: evalId 
                },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        var data = response.data;
                        var html = `
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>Employee Name:</strong> ${data.employee_name}</p>
                                    <p><strong>Employee ID:</strong> ${data.employee_emp_id}</p>
                                    <p><strong>Department:</strong> ${data.dept_name}</p>
                                    <p><strong>Position:</strong> ${data.employee_position}</p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Evaluated By:</strong> ${data.manager_name || 'N/A'}</p>
                                    <p><strong>Evaluation Date:</strong> ${data.created_at}</p>
                                    <p><strong>Total Score:</strong> <span class="badge badge-success score-badge">${data.total_score}/100</span></p>
                                </div>
                            </div>
                            <hr>
                            <h5>Evaluation Criteria</h5>
                            <table class="table table-bordered table-sm">
                                <thead>
                                    <tr>
                                        <th>Criteria</th>
                                        <th width="100">Score</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr><td>Punctuality Attendance</td><td class="text-center"><span class="badge badge-primary">${data.punctuality}/10</span></td></tr>
                                    <tr><td>Achieving at the specified time</td><td class="text-center"><span class="badge badge-primary">${data.achieving_time}/10</span></td></tr>
                                    <tr><td>Knowledge of job</td><td class="text-center"><span class="badge badge-primary">${data.job_knowledge}/10</span></td></tr>
                                    <tr><td>The Ability to solve problems</td><td class="text-center"><span class="badge badge-primary">${data.problem_solving}/10</span></td></tr>
                                    <tr><td>Receptiveness to Feedback and Instructions</td><td class="text-center"><span class="badge badge-primary">${data.feedback_receptiveness}/10</span></td></tr>
                                    <tr><td>Self & Professional Development</td><td class="text-center"><span class="badge badge-primary">${data.self_development}/10</span></td></tr>
                                    <tr><td>Work under pressure</td><td class="text-center"><span class="badge badge-primary">${data.work_under_pressure}/10</span></td></tr>
                                    <tr><td>Communication skills and Teamwork</td><td class="text-center"><span class="badge badge-primary">${data.communication_teamwork}/10</span></td></tr>
                                    <tr><td>Creativity and speed of response</td><td class="text-center"><span class="badge badge-primary">${data.creativity_response}/10</span></td></tr>
                                    <tr><td>Initiative and cooperation</td><td class="text-center"><span class="badge badge-primary">${data.initiative_cooperation}/10</span></td></tr>
                                </tbody>
                            </table>
                            <hr>
                            <h5>Observation/Remarks</h5>
                            <p>${data.observation || 'No observation provided.'}</p>
                        `;
                        $('#evaluationModalBody').html(html);
                    } else {
                        $('#evaluationModalBody').html('<div class="alert alert-danger">Failed to load evaluation details.</div>');
                    }
                },
                error: function() {
                    $('#evaluationModalBody').html('<div class="alert alert-danger">An error occurred while loading the evaluation details.</div>');
                }
            });
        });
    });
    </script>

</body>
</html>
