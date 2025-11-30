<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/session_check.php';

// Define who can see reports
$can_see_reports_page = ['Administrator', 'GM', 'Auditor', 'HR_Senior_BP', 'HR_Operations', 'HR_Supervisor', 'Finance_Officer', 'DPT_Manager', 'HR_Manager', 'Finance_Manager'];

// Check authorization
if (!in_array($user_role, $can_see_reports_page) && $user_type !== 'is_system_admin') {
    header("Location: dashboard.php");
    exit();
}

// Determine if user has full access (can see all departments)
$has_full_access = in_array($user_role, ['Administrator', 'GM', 'Auditor', 'HR_Senior_BP', 'HR_Operations', 'HR_Supervisor']) || $user_type === 'is_system_admin';

// Get user's department for filtering
$user_dept = isset($_SESSION['user_dept']) ? $_SESSION['user_dept'] : '';

$query = mysqli_query($conDB, "SELECT * FROM `admin_login` WHERE `id_iqama`='".$username."'");
if(mysqli_num_rows($query) == 1){
include("./includes/avatar_select.php");
?>
<!doctype html>
<html lang="en">
    <head>
        <meta charset="utf-8" />
        <title><?= $site_title ?> - Reports</title>
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
        <meta content="Anees Afzal" name="author" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />

        <!-- App favicon -->
        <link rel="shortcut icon" href="<?=get_setting($conDB, 'favicon')?>">

        <!-- App css -->
        <link href="assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
        <link href="assets/css/icons.css" rel="stylesheet" type="text/css" />
        <link href="assets/css/metismenu.min.css" rel="stylesheet" type="text/css" />
        <link href="assets/css/style.css" rel="stylesheet" type="text/css" />
		<link href="assets/css/style_dark.css" rel="stylesheet" type="text/css" />

        <!-- DataTables -->
        <link href="./plugins/datatables/dataTables.bootstrap4.min.css" rel="stylesheet" type="text/css" />
        <link href="./plugins/datatables/buttons.bootstrap4.min.css" rel="stylesheet" type="text/css" />
        <link href="./plugins/datatables/responsive.bootstrap4.min.css" rel="stylesheet" type="text/css" />

        <!-- Select2 (CDN) -->
            <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
            <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap4-theme@1.0.0/dist/select2-bootstrap4.min.css" rel="stylesheet" />

        <!-- <link rel="stylesheet" href="./plugins/bootstrap-select/css/bootstrap-select.min.css"> -->
		<!-- <link rel="stylesheet" href="./plugins/select2/css/select2.min.css"> -->

        <script src="assets/js/modernizr.min.js"></script>

        <style>
            .column-selector {
                max-height: 300px;
                overflow-y: auto;
                border: 1px solid #dee2e6;
                padding: 15px;
                border-radius: 4px;
                background-color: #f8f9fa;
            }
            .column-checkbox {
                display: block;
                margin-bottom: 8px;
            }
            .filter-section {
                background-color: #fff;
                padding: 20px;
                border-radius: 4px;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                margin-bottom: 20px;
            }
            .report-actions {
                margin-top: 15px;
            }
            #reportTableContainer {
                margin-top: 30px;
            }
            .select2-container--bootstrap4 .select2-selection--multiple {
                min-height: 42px;
            }
            .select2-container {
            width: 100% !important;
            }
            /* Select2 Multi-select polish */
            .select2-container {
                width: 100% !important;
            }
            .select2-container--bootstrap4 .select2-selection--multiple {
                min-height: 38px;
                border: 1px solid #ced4da;
                border-radius: 4px;
                padding: 3px 6px;
                background-color: #fff;
                transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
            }
            .select2-container--bootstrap4.select2-container--focus .select2-selection--multiple {
                border-color: #80bdff;
                outline: 0;
                box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
            }
            .select2-container--bootstrap4 .select2-selection--multiple .select2-selection__rendered {
                display: flex;
                flex-wrap: wrap;
                gap: 4px;
                padding: 0;
            }
            .select2-container--bootstrap4 .select2-selection--multiple .select2-selection__choice {
                background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
                color: #ffffff;
                border: none;
                border-radius: 14px;
                padding: 2px 10px 2px 24px;
                font-size: 12px;
                font-weight: 500;
                line-height: 18px;
                margin: 0;
                box-shadow: 0 2px 4px rgba(40, 167, 69, 0.3);
                transition: all 0.2s ease;
                position: relative;
            }
            .select2-container--bootstrap4 .select2-selection--multiple .select2-selection__choice:hover {
                transform: translateY(-1px);
                box-shadow: 0 3px 6px rgba(40, 167, 69, 0.4);
            }
            .select2-container--bootstrap4 .select2-selection--multiple .select2-selection__choice__remove {
                color: #fff;
                background: rgba(255, 255, 255, 0.25);
                border-radius: 50%;
                width: 16px;
                height: 16px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                margin-right: 0;
                position: absolute;
                left: 4px;
                top: 50%;
                transform: translateY(-50%);
                font-size: 14px;
                font-weight: bold;
                transition: background 0.2s ease;
            }
            .select2-container--bootstrap4 .select2-selection--multiple .select2-selection__choice__remove:hover {
                background: rgba(255, 255, 255, 0.4);
                color: #fff;
            }
            .select2-dropdown {
                border: 1px solid #ced4da;
                border-radius: 4px;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            }
            .select2-container--bootstrap4 .select2-dropdown .select2-search__field {
                border: 1px solid #ced4da;
                border-radius: 4px;
                padding: 6px 12px;
                font-size: 14px;
            }
            .select2-container--bootstrap4 .select2-dropdown .select2-search__field:focus {
                border-color: #80bdff;
                outline: 0;
                box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
            }
            .select2-results__option {
                font-size: 14px;
                padding: 8px 12px;
                transition: background-color 0.15s ease;
            }
            .select2-results__option--highlighted {
                background-color: #007bff !important;
                color: white !important;
            }
            .select2-results__option[aria-selected=true] {
                background-color: #f8f9fa;
                font-weight: 500;
            }
            /* Draggable Column Styles */
            .column-item {
                display: flex;
                align-items: center;
                padding: 8px 10px;
                margin-bottom: 0;
                background-color: #fff;
                border: 1px solid #dee2e6;
                border-radius: 4px;
                cursor: move;
                transition: all 0.2s ease;
                user-select: none;
                font-size: 13px;
            }
            .column-item:hover {
                background-color: #f8f9fa;
                box-shadow: 0 1px 4px rgba(0, 0, 0, 0.1);
                border-color: #007bff;
            }
            .column-item.dragging {
                opacity: 0.5;
                background-color: #e7f3ff;
                border-color: #007bff;
                box-shadow: 0 3px 8px rgba(0, 123, 255, 0.3);
            }
            .column-item.drag-over {
                background-color: #d4edff;
                border-color: #0056b3;
                border-top: 2px solid #0056b3;
            }
            .column-item input[type="checkbox"] {
                margin: 0 8px 0 0;
                cursor: pointer;
                width: 16px;
                height: 16px;
                flex-shrink: 0;
            }
            .column-item .drag-handle {
                display: flex;
                align-items: center;
                justify-content: center;
                color: #999;
                font-size: 14px;
                margin-right: 6px;
                cursor: grab;
                flex-shrink: 0;
            }
            .column-item .drag-handle:active {
                cursor: grabbing;
            }
            .column-item label {
                flex-grow: 1;
                margin: 0;
                cursor: pointer;
                font-size: 13px;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }
            #columnSortableContainer {
                scrollbar-width: thin;
                scrollbar-color: #007bff #f1f1f1;
            }
            #columnSortableContainer::-webkit-scrollbar {
                width: 8px;
            }
            #columnSortableContainer::-webkit-scrollbar-track {
                background: #f1f1f1;
                border-radius: 4px;
            }
            #columnSortableContainer::-webkit-scrollbar-thumb {
                background: #007bff;
                border-radius: 4px;
            }
            #columnSortableContainer::-webkit-scrollbar-thumb:hover {
                background: #0056b3;
            }
            #selectByTable {
                border: 1px solid #ced4da;
                border-radius: 4px;
                padding: 5px 10px;
                font-size: 13px;
            }
            #selectByTable:focus {
                border-color: #007bff;
                outline: none;
                box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
            }
            .select2-search__field {
                font-size: 14px;
            }
            /* Badge styling for selected count */
            .badge-success {
                background-color: #28a745;
                padding: 4px 10px;
                border-radius: 12px;
                font-size: 12px;
                font-weight: 600;
                margin-left: 8px;
                box-shadow: 0 2px 4px rgba(40, 167, 69, 0.3);
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

                    <!-- User box -->

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
                <div class="topbar">
                    <?php include './includes/topbar.php'; ?>
                </div>
                <!-- Top Bar End -->

                <!-- Start Page content -->
                <div class="content">
                    <div class="container-fluid">
                        <div class="row">
                            <div class="col-12">
                                <div class="page-title-box">
                                    <h4 class="page-title float-left">
                                        <i class="mdi mdi-file-chart mr-2"></i>Reports
                                    </h4>
                                    <div class="clearfix"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Filter Section -->
                        <div class="row">
                            <div class="col-12">
                                <div class="filter-section">
                                    <h5 class="mb-3">Report Configuration</h5>
                                    
                                    <div class="row">
                        <!-- Report Type -->
                        <div class="col-md-4 mb-3">
                            <label for="reportType">Report Type</label>
                            <select class="form-control" id="reportType">
                                <option value="">Select Report Type</option>
                                <option value="employee">Employee Report</option>
                                <option value="vacation">Vacation Report</option>
                                <option value="loan">Loan Report</option>
                                <option value="salary">Salary Report</option>
                                <option value="payroll">Payroll Report</option>
                                <option value="attendance">Attendance Report</option>
                                <option value="document">Document Report</option>
                                <option value="evaluation">Evaluation Report</option>
                                <option value="resignation">Resignation Report</option>
                                <option value="eos">End of Service Report</option>
                                <option value="dept_comparison">Department Comparison Report</option>
                                <option value="custom">Custom Report</option>
                            </select>
                        </div>                        <!-- Department Filter (if authorized) -->
                        <?php if ($has_full_access): ?>
                        <div class="col-md-4 mb-3" id="singleDeptFilter">
                            <label for="deptFilter">Department</label>
                            <select class="form-control" id="deptFilter">
                                <option value="">All Departments</option>
                                <?php
                                $dept_query = mysqli_query($conDB, "SELECT DISTINCT id, dep_nme FROM department ORDER BY dep_nme");
                                while ($dept = mysqli_fetch_assoc($dept_query)) {
                                    echo '<option value="'.$dept['id'].'">'.$dept['dep_nme'].'</option>';
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3" id="multiDeptFilter" style="display:none;">
                            <label for="deptMultiFilter">Select Departments</label>
                            <select class="form-control" id="deptMultiFilter" multiple="multiple" size="8" style="height: auto;">
                                <option value="all" data-select-all="true">✓ All Departments</option>
                                <?php
                                $dept_query2 = mysqli_query($conDB, "SELECT DISTINCT id, dep_nme FROM department ORDER BY dep_nme");
                                while ($dept = mysqli_fetch_assoc($dept_query2)) {
                                    echo '<option value="'.$dept['id'].'">'.$dept['dep_nme'].'</option>';
                                }
                                ?>
                            </select>
                            <small class="text-muted d-block mt-1">Select "All Departments" or choose specific departments.</small>
                        </div>
                        <?php endif; ?>                                        <!-- Date Range -->
                                        <div class="col-md-4 mb-3">
                                            <label for="dateFrom">Date From</label>
                                            <input type="text" class="form-control datepicker" id="dateFrom" placeholder="Select Start Date">
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label for="dateTo">Date To</label>
                                            <input type="text" class="form-control datepicker" id="dateTo" placeholder="Select End Date">
                                        </div>

                                        <!-- Status Filter -->
                                        <div class="col-md-4 mb-3">
                                            <label for="statusFilter">Status</label>
                                            <select class="form-control" id="statusFilter">
                                                <option value="">All Status</option>
                                                <option value="1">Active</option>
                                                <option value="0">Inactive</option>
                                            </select>
                                        </div>

                                        <!-- Custom Report Table Selection -->
                                        <div class="col-md-4 mb-3" id="customTableSelection" style="display:none;">
                                            <label for="customTables">Select Tables (Multi-select)</label>
                                            <select class="form-control" id="customTables" multiple="multiple" style="width: 100%; height: auto;">
                                                <?php
                                                $tables_query = mysqli_query($conDB, "SHOW TABLES");
                                                $excluded_tables = ['admin_login', 'settings', 'notifications', 'audit_log'];
                                                
                                                // User-friendly table names mapping
                                                $table_names = [
                                                    'employees' => 'Employees',
                                                    'department' => 'Departments',
                                                    'section' => 'Sections',
                                                    'ac_jobs' => 'Job Titles',
                                                    'countries' => 'Countries',
                                                    'bank_list' => 'Banks',
                                                    'emp_vacation' => 'Employee Vacations',
                                                    'emp_loan' => 'Employee Loans',
                                                    'emp_loan_payments' => 'Loan Payments',
                                                    'emp_salary' => 'Employee Salaries',
                                                    'emp_docu' => 'Employee Documents',
                                                    'emp_evaluations' => 'Employee Evaluations',
                                                    'emp_resignations' => 'Employee Resignations',
                                                    'emp_eos' => 'End of Service',
                                                    'payrolls' => 'Payrolls',
                                                    'attendance' => 'Attendance Records',
                                                    'gender' => 'Gender',
                                                    'user_type' => 'User Types',
                                                    'locations' => 'Locations',
                                                    'machines' => 'Machines',
                                                    'car' => 'Vehicles',
                                                    'brands' => 'Brands'
                                                ];
                                                
                                                while ($table = mysqli_fetch_row($tables_query)) {
                                                    if (!in_array($table[0], $excluded_tables)) {
                                                        $display_name = isset($table_names[$table[0]]) ? $table_names[$table[0]] : str_replace('_', ' ', ucwords(str_replace('_', ' ', $table[0])));
                                                        echo '<option value="'.$table[0].'">'.$display_name.'</option>';
                                                    }
                                                }
                                                ?>
                                            </select>
                                            <small class="text-muted d-block mt-1">Select one or more tables. Columns from all selected tables will be available.</small>
                                        </div>

                                        <!-- Department Filter for Custom Report -->
                                        <div class="col-md-4 mb-3" id="customDeptFilter" style="display:none;">
                                            <label for="customDeptMultiFilter">Select Departments</label>
                                            <select class="form-control" id="customDeptMultiFilter" multiple="multiple" style="width: 100%; height: auto;">
                                                <option value="all" data-select-all="true">✓ All Departments</option>
                                                <?php
                                                $dept_query_custom = mysqli_query($conDB, "SELECT DISTINCT id, dep_nme FROM department ORDER BY dep_nme");
                                                while ($dept = mysqli_fetch_assoc($dept_query_custom)) {
                                                    echo '<option value="'.$dept['id'].'">'.$dept['dep_nme'].'</option>';
                                                }
                                                ?>
                                            </select>
                                            <small class="text-muted d-block mt-1">Select "All Departments" or choose specific departments.</small>
                                        </div>
                                        
                                        <!-- Date Range Filter for Custom Report -->
                                        <div class="col-md-4 mb-3" id="customDateFromFilter" style="display:none;">
                                            <label for="customDateFrom">From Date</label>
                                            <input type="date" class="form-control" id="customDateFrom">
                                            <small class="text-muted">Optional: Filter records from this date</small>
                                        </div>
                                        <div class="col-md-4 mb-3" id="customDateToFilter" style="display:none;">
                                            <label for="customDateTo">To Date</label>
                                            <input type="date" class="form-control" id="customDateTo">
                                            <small class="text-muted">Optional: Filter records up to this date</small>
                                        </div>
                                    </div>

                                    <!-- Column Selection -->
                                    <div class="row mt-3" id="columnSelectionRow" style="display:none;">
                                        <div class="col-12">
                                            <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap">
                                                <label for="columnMultiSelect" class="mb-0 mb-md-0"><strong>Select Columns to Display:</strong> <span id="selectedColumnCount" class="badge badge-primary">0 selected</span></label>
                                                <div class="d-flex align-items-center flex-wrap">
                                                    <div class="mr-2 mb-2 mb-md-0" id="selectByTableContainer" style="display:none;">
                                                        <select class="form-control form-control-sm" id="selectByTable" style="width: auto; min-width: 200px;">
                                                            <option value="">Select by Table...</option>
                                                        </select>
                                                    </div>
                                                    <div>
                                                        <button type="button" class="btn btn-sm btn-info mr-2" id="selectAllColumnsBtn">
                                                            <i class="mdi mdi-check-all mr-1"></i>Select All
                                                        </button>
                                                        <button type="button" class="btn btn-sm btn-warning" id="deselectAllColumnsBtn">
                                                            <i class="mdi mdi-close-circle mr-1"></i>Deselect All
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                            <div id="columnSortableContainer" style="border: 1px solid #ddd; border-radius: 4px; padding: 15px; background-color: #f9f9f9; max-height: 400px; overflow-y: auto; display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 8px;">
                                                <!-- Sortable columns will be rendered here -->
                                            </div>
                                            <small class="text-muted d-block mt-2"><i class="mdi mdi-information mr-1"></i>Drag columns to reorder • Click checkbox to select/deselect • Scroll to see more</small>
                                        </div>
                                    </div>

                                    <!-- Report Actions -->
                                    <div class="report-actions">
                                        <button type="button" class="btn btn-primary" id="generateReportBtn">
                                            <i class="mdi mdi-file-chart mr-1"></i>Generate Report
                                        </button>
                                        <button type="button" class="btn btn-success" id="exportExcelBtn" style="display:none;">
                                            <i class="mdi mdi-file-excel mr-1"></i>Export to Excel
                                        </button>
                                        <button type="button" class="btn btn-danger" id="exportPdfBtn" style="display:none;">
                                            <i class="mdi mdi-file-pdf mr-1"></i>Export to PDF
                                        </button>
                                        <button type="button" class="btn btn-secondary" id="resetBtn">
                                            <i class="mdi mdi-refresh mr-1"></i>Reset
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Report Table Container -->
                        <div class="row" id="reportTableContainer" style="display:none;">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-body">
                                        <h5 class="card-title" id="reportTitle">Report Results</h5>
                                        <div class="table-responsive">
                                            <table id="reportTable" class="table table-bordered table-striped dt-responsive nowrap" style="width:100%">
                                                <thead id="reportTableHead">
                                                    <!-- Dynamic headers -->
                                                </thead>
                                                <tbody id="reportTableBody">
                                                    <!-- Dynamic rows -->
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div> <!-- container -->
                </div> <!-- content -->

                <footer class="footer text-right">
                    &copy; <?= date("Y") ?> <?= get_setting($conDB, 'company_name') ?>
                </footer>

            </div>
            <!-- ============================================================== -->
            <!-- End Right content here -->
            <!-- ============================================================== -->

        </div>
        <!-- END wrapper -->

        <!-- jQuery  -->
        <script src="assets/js/jquery.min.js"></script>
        <!-- <script src="assets/js/popper.min.js"></script> -->
        <script src="assets/js/bootstrap.min.js"></script>
        <script src="assets/js/metisMenu.min.js"></script>
        <script src="assets/js/waves.js"></script>
        <script src="assets/js/jquery.slimscroll.js"></script>

        <!-- DataTables -->
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

        <!-- Date Picker -->
        <script src="./plugins/bootstrap-datepicker/js/bootstrap-datepicker.min.js"></script>

        <!-- SweetAlert2 -->
        <!-- <script src="./assets/plugins/sweet-alert2/sweetalert2.min.js"></script> -->

        <!-- Select2 (CDN) -->
        <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

        <!-- App js -->
        <script src="assets/js/jquery.core.js"></script>
        <script src="assets/js/jquery.app.js"></script>

        <script>
            $(document).ready(function() {
                // Hide department and date filters by default until a report type is selected
                $('#singleDeptFilter').hide();
                $('#multiDeptFilter').hide();
                $('#dateFrom').closest('.col-md-4, .form-group').hide();
                $('#dateTo').closest('.col-md-4, .form-group').hide();
                // Initialize date pickers
                $('.datepicker').datepicker({
                    format: 'yyyy-mm-dd',
                    autoclose: true,
                    todayHighlight: true
                });

                // Format function for dropdown options with checkmarks
                function formatDeptOption(data) {
                    if (!data.id) { 
                        return data.text; 
                    }
                    
                    // Special formatting for "All Departments"
                    if (data.id === 'all') {
                        var $result = $('<span><i class="mdi mdi-select-all text-primary mr-2" style="font-size: 16px;"></i><strong>' + data.text + '</strong></span>');
                        return $result;
                    }
                    // Special formatting for "Deselect All"
                    if (data.id === 'none') {
                        var $result = $('<span><i class="mdi mdi-close-circle text-danger mr-2" style="font-size: 16px;"></i><strong>' + data.text + '</strong></span>');
                        return $result;
                    }
                    var $select = $('#deptMultiFilter');
                    var selectedValues = $select.val() || [];
                    var isSelected = selectedValues.indexOf(data.id) !== -1;
                    
                    var checkmark = isSelected 
                        ? '<i class="mdi mdi-check-circle text-success mr-2" style="font-size: 16px;"></i>' 
                        : '<i class="mdi mdi-checkbox-blank-circle-outline text-muted mr-2" style="font-size: 16px;"></i>';
                    
                    var $result = $('<span>' + checkmark + data.text + '</span>');
                    return $result;
                }

                // Initialize Select2 for multi-department filter (only if plugin loaded)
                function initDeptSelect2() {
                    if ($.fn.select2) {
                        // Dynamically add Deselect All option if all departments are selected
                        function updateDeselectOption() {
                            var $select = $('#deptMultiFilter');
                            var allValues = [];
                            $select.find('option').each(function() {
                                if ($(this).val() !== 'all' && $(this).val() !== 'none') {
                                    allValues.push($(this).val());
                                }
                            });
                            var selected = $select.val() || [];
                            var $none = $select.find('option[value="none"]');
                            if (selected.length === allValues.length && !$none.length) {
                                $select.prepend('<option value="none" data-deselect-all="true">✗ Deselect All</option>');
                            } else if (selected.length !== allValues.length && $none.length) {
                                $none.remove();
                            }
                        }
                        $('#deptMultiFilter').select2({
                            theme: 'bootstrap4',
                            placeholder: 'Select departments',
                            allowClear: true,
                            closeOnSelect: false,
                            width: '100%',
                            templateResult: formatDeptOption,
                            templateSelection: function (data) {
                                if (data.id === 'all') {
                                    return 'All Departments';
                                }
                                if (data.id === 'none') {
                                    return 'Deselect All';
                                }
                                return data.text;
                            }
                        });
                        // Handle All Departments selection
                        $('#deptMultiFilter').on('select2:select', function(e) {
                            var data = e.params.data;
                            if (data.id === 'all') {
                                var allValues = [];
                                $('#deptMultiFilter option').each(function() {
                                    if ($(this).val() !== 'all' && $(this).val() !== 'none') {
                                        allValues.push($(this).val());
                                    }
                                });
                                $(this).val(allValues).trigger('change');
                            }
                            if (data.id === 'none') {
                                $(this).val(null).trigger('change');
                            }
                            updateDeptSelectionCount();
                            updateDeselectOption();
                            var $select = $(this);
                            if ($select.data('select2') && $select.data('select2').isOpen()) {
                                $select.select2('close');
                                setTimeout(function() {
                                    $select.select2('open');
                                }, 50);
                            }
                        });
                        $('#deptMultiFilter').on('select2:unselect', function(e) {
                            updateDeptSelectionCount();
                            updateDeselectOption();
                            var $select = $(this);
                            if ($select.data('select2') && $select.data('select2').isOpen()) {
                                $select.select2('close');
                                setTimeout(function() {
                                    $select.select2('open');
                                }, 50);
                            }
                        });
                        $('#deptMultiFilter').on('change', function() {
                            updateDeselectOption();
                        });
                        updateDeptSelectionCount();
                        updateDeselectOption();
                    }
                }
                
                function initCustomTablesSelect2() {
                    if ($.fn.select2) {
                        $('#customTables').select2({
                            theme: 'bootstrap4',
                            placeholder: 'Select tables to generate report',
                            allowClear: true,
                            closeOnSelect: false,
                            width: '100%'
                        });
                    }
                }
                
                function initCustomDeptSelect2() {
                    if ($.fn.select2) {
                        // Dynamically add Deselect All option if all departments are selected
                        function updateCustomDeselectOption() {
                            var $select = $('#customDeptMultiFilter');
                            var allValues = [];
                            $select.find('option').each(function() {
                                if ($(this).val() !== 'all' && $(this).val() !== 'none') {
                                    allValues.push($(this).val());
                                }
                            });
                            var selected = $select.val() || [];
                            var $none = $select.find('option[value="none"]');
                            if (selected.length === allValues.length && !$none.length) {
                                $select.prepend('<option value="none" data-deselect-all="true">✗ Deselect All</option>');
                            } else if (selected.length !== allValues.length && $none.length) {
                                $none.remove();
                            }
                        }
                        $('#customDeptMultiFilter').select2({
                            theme: 'bootstrap4',
                            placeholder: 'Select departments',
                            allowClear: true,
                            closeOnSelect: false,
                            width: '100%',
                            templateResult: formatDeptOption,
                            templateSelection: function (data) {
                                if (data.id === 'all') {
                                    return 'All Departments';
                                }
                                if (data.id === 'none') {
                                    return 'Deselect All';
                                }
                                return data.text;
                            }
                        });
                        // Handle All Departments selection
                        $('#customDeptMultiFilter').on('select2:select', function(e) {
                            var data = e.params.data;
                            if (data.id === 'all') {
                                var allValues = [];
                                $('#customDeptMultiFilter option').each(function() {
                                    if ($(this).val() !== 'all' && $(this).val() !== 'none') {
                                        allValues.push($(this).val());
                                    }
                                });
                                $(this).val(allValues).trigger('change');
                            }
                            if (data.id === 'none') {
                                $(this).val(null).trigger('change');
                            }
                            updateCustomDeselectOption();
                            var $select = $(this);
                            if ($select.data('select2') && $select.data('select2').isOpen()) {
                                $select.select2('close');
                                setTimeout(function() {
                                    $select.select2('open');
                                }, 50);
                            }
                        });
                        $('#customDeptMultiFilter').on('select2:unselect', function(e) {
                            updateCustomDeselectOption();
                            var $select = $(this);
                            if ($select.data('select2') && $select.data('select2').isOpen()) {
                                $select.select2('close');
                                setTimeout(function() {
                                    $select.select2('open');
                                }, 50);
                            }
                        });
                        $('#customDeptMultiFilter').on('change', function() {
                            updateCustomDeselectOption();
                        });
                        updateCustomDeselectOption();
                    }
                }
                
                initDeptSelect2();

                // Column definitions for each report type
                const reportColumns = {
                    employee: [
                        { id: 'name', label: 'Name', default: true },
                        { id: 'emp_id', label: 'Employee ID', default: true },
                        { id: 'iqama', label: 'Iqama', default: true },
                        { id: 'mobile', label: 'Mobile', default: true },
                        { id: 'email', label: 'Email', default: false },
                        { id: 'dept', label: 'Department', default: true },
                        { id: 'sectin_nme', label: 'Section', default: false },
                        { id: 'actual_job', label: 'Job Title', default: true },
                        { id: 'emptype', label: 'Employee Type', default: true },
                        { id: 'salary', label: 'Salary', default: false },
                        { id: 'joining_date', label: 'Joining Date', default: true },
                        { id: 'country', label: 'Nationality', default: false },
                        { id: 'supervisor_id', label: 'Supervisor', default: false },
                        { id: 'vacation_days', label: 'Vacation Days', default: false },
                        { id: 'fly', label: 'Flight Ticket', default: false },
                        { id: 'bank_name', label: 'Bank Name', default: false },
                        { id: 'iban', label: 'IBAN', default: false },
                        { id: 'dob', label: 'Date of Birth', default: false },
                        { id: 'sex', label: 'Gender', default: false },
                        { id: 'blood_type', label: 'Blood Type', default: false },
                        { id: 'mar_status', label: 'Marital Status', default: false },
                        { id: 'gosi', label: 'GOSI', default: false },
                        { id: 'insurance_no', label: 'Insurance No', default: false },
                        { id: 'status', label: 'Status', default: true }
                    ],
                    vacation: [
                        { id: 'emp_id', label: 'Employee ID', default: true },
                        { id: 'emp_name', label: 'Employee Name', default: true },
                        { id: 'dept', label: 'Department', default: true },
                        { id: 'vac_type', label: 'Vacation Type', default: true },
                        { id: 'start_date', label: 'Start Date', default: true },
                        { id: 'return_date', label: 'Return Date', default: true },
                        { id: 'vacdays', label: 'Days', default: true },
                        { id: 'fly_type', label: 'Flight Type', default: false },
                        { id: 'permit_no', label: 'Permit No', default: false },
                        { id: 'current_status', label: 'Status', default: true },
                        { id: 'created_at', label: 'Applied Date', default: false }
                    ],
                    loan: [
                        { id: 'emp_id', label: 'Employee ID', default: true },
                        { id: 'emp_name', label: 'Employee Name', default: true },
                        { id: 'dept', label: 'Department', default: true },
                        { id: 'loan_amount', label: 'Loan Amount', default: true },
                        { id: 'monthly_deduction', label: 'Monthly Deduction', default: true },
                        { id: 'start_date', label: 'Start Date', default: true },
                        { id: 'end_date', label: 'End Date', default: true },
                        { id: 'loan_type', label: 'Loan Type', default: true },
                        { id: 'status', label: 'Status', default: true },
                        { id: 'final_approved_amount', label: 'Approved Amount', default: false },
                        { id: 'total_payable', label: 'Total Payable', default: false },
                        { id: 'remaining_amount', label: 'Remaining Amount', default: true }
                    ],
                    salary: [
                        { id: 'emp_id', label: 'Employee ID', default: true },
                        { id: 'emp_name', label: 'Employee Name', default: true },
                        { id: 'dept', label: 'Department', default: true },
                        { id: 'basic', label: 'Basic Salary', default: true },
                        { id: 'housing', label: 'Housing', default: true },
                        { id: 'transport', label: 'Transport', default: true },
                        { id: 'food', label: 'Food', default: false },
                        { id: 'misc', label: 'Misc', default: false },
                        { id: 'fuel', label: 'Fuel', default: false },
                        { id: 'tel', label: 'Telephone', default: false },
                        { id: 'cashier', label: 'Cashier', default: false },
                        { id: 'other', label: 'Other', default: false },
                        { id: 'guard', label: 'Guard', default: false },
                        { id: 'total_salary', label: 'Total Salary', default: true }
                    ],
                    payroll: [
                        { id: 'payroll_id', label: 'Payroll ID', default: true },
                        { id: 'month', label: 'Month', default: true },
                        { id: 'year', label: 'Year', default: true },
                        { id: 'total_employees', label: 'Total Employees', default: true },
                        { id: 'total_salary', label: 'Total Salary', default: true },
                        { id: 'total_deductions', label: 'Total Deductions', default: true },
                        { id: 'net_salary', label: 'Net Salary', default: true },
                        { id: 'generated_by', label: 'Generated By', default: false },
                        { id: 'created_at', label: 'Generated Date', default: true }
                    ],
                    attendance: [
                        { id: 'emp_id', label: 'Employee ID', default: true },
                        { id: 'emp_name', label: 'Employee Name', default: true },
                        { id: 'dept', label: 'Department', default: true },
                        { id: 'date', label: 'Date', default: true },
                        { id: 'check_in', label: 'Check In', default: true },
                        { id: 'check_out', label: 'Check Out', default: true },
                        { id: 'hours', label: 'Hours', default: true },
                        { id: 'status', label: 'Status', default: true }
                    ],
                    document: [
                        { id: 'emp_id', label: 'Employee ID', default: true },
                        { id: 'emp_name', label: 'Employee Name', default: true },
                        { id: 'dept', label: 'Department', default: true },
                        { id: 'document_type', label: 'Document Type', default: true },
                        { id: 'document_name', label: 'Document Name', default: true },
                        { id: 'upload_date', label: 'Upload Date', default: true },
                        { id: 'status', label: 'Status', default: true }
                    ],
                    evaluation: [
                        { id: 'emp_id', label: 'Employee ID', default: true },
                        { id: 'emp_name', label: 'Employee Name', default: true },
                        { id: 'dept', label: 'Department', default: true },
                        { id: 'evaluation_date', label: 'Evaluation Date', default: true },
                        { id: 'score', label: 'Score', default: true },
                        { id: 'rating', label: 'Rating', default: true },
                        { id: 'evaluator', label: 'Evaluator', default: false }
                    ],
                    resignation: [
                        { id: 'emp_id', label: 'Employee ID', default: true },
                        { id: 'emp_name', label: 'Employee Name', default: true },
                        { id: 'dept', label: 'Department', default: true },
                        { id: 'resignation_date', label: 'Resignation Date', default: true },
                        { id: 'last_working_day', label: 'Last Working Day', default: true },
                        { id: 'reason', label: 'Reason', default: false },
                        { id: 'status', label: 'Status', default: true }
                    ],
                    eos: [
                        { id: 'emp_id', label: 'Employee ID', default: true },
                        { id: 'emp_name', label: 'Employee Name', default: true },
                        { id: 'dept', label: 'Department', default: true },
                        { id: 'joining_date', label: 'Joining Date', default: true },
                        { id: 'termination_date', label: 'Termination Date', default: true },
                        { id: 'service_years', label: 'Service Years', default: true },
                        { id: 'eos_amount', label: 'EOS Amount', default: true },
                        { id: 'vacation_balance', label: 'Vacation Balance', default: false },
                        { id: 'total_amount', label: 'Total Amount', default: true }
                    ],
                    dept_comparison: [
                        { id: 'department', label: 'Department', default: true },
                        { id: 'total_employees', label: 'Total Employees', default: true },
                        { id: 'active_employees', label: 'Active Employees', default: true },
                        { id: 'inactive_employees', label: 'Inactive Employees', default: false },
                        { id: 'total_salary', label: 'Total Salary', default: true },
                        { id: 'avg_salary', label: 'Average Salary', default: true },
                        { id: 'pending_vacations', label: 'Pending Vacations', default: true },
                        { id: 'approved_vacations', label: 'Approved Vacations', default: false },
                        { id: 'active_loans', label: 'Active Loans', default: true },
                        { id: 'total_loan_amount', label: 'Total Loan Amount', default: true },
                        { id: 'avg_service_years', label: 'Avg Service Years', default: false }
                    ],
                    custom: []
                };

                // When report type changes, load column checkboxes
                $('#reportType').on('change', function() {
                    const reportType = $(this).val();
                    
                    console.log('*** REPORT TYPE CHANGED TO:', reportType);
                    
                    // Destroy DataTable if exists BEFORE hiding/clearing
                    if ($.fn.DataTable.isDataTable('#reportTable')) {
                        try {
                            const table = $('#reportTable').DataTable();
                            table.destroy(); // Destroy DataTable but keep table structure
                            console.log('DataTable destroyed successfully');
                        } catch (e) {
                            console.error('Error destroying DataTable:', e);
                        }
                    }
                    
                    // Hide report table and export buttons when changing report type
                    $('#reportTableContainer').hide();
                    $('#exportExcelBtn, #exportPdfBtn').hide();
                    
                    // Clear table content AFTER destroying DataTable
                    $('#reportTableHead').empty();
                    $('#reportTableBody').empty()
                    
                    // Reset all filter fields
                    $('#dateFrom').val('');
                    $('#dateTo').val('');
                    $('#statusFilter').val('');
                    $('#deptFilter').val('');
                    
                    // Clear multi-select department filters
                    $('#deptMultiFilter').val(null).trigger('change');
                    $('#customDeptMultiFilter').val(null).trigger('change');
                    
                    // Clear column selection container and hidden select
                    $('#columnSortableContainer').empty();
                    $('#columnMultiSelect').empty();
                    $('#selectedColumnCount').text('0 selected');
                    
                    console.log('Cleared column container and select');
                    
                    // Hide select by table dropdown and clear it
                    $('#selectByTableContainer').hide();
                    $('#selectByTable').empty().append('<option value="">Select by Table...</option>');
                    
                    // Show/hide custom table selector
                    if (reportType === 'custom') {
                        $('#customTableSelection').show();
                        $('#customDeptFilter').hide(); // Hide initially until tables selected
                        $('#customDateFromFilter').hide(); // Hide initially until tables selected
                        $('#customDateToFilter').hide(); // Hide initially until tables selected
                        $('#customDateFrom').val('');
                        $('#customDateTo').val('');
                        // Hide global date filters to avoid duplicates in custom mode
                        $('#dateFrom').closest('.col-md-4, .form-group').hide();
                        $('#dateTo').closest('.col-md-4, .form-group').hide();
                        $('#multiDeptFilter').hide();
                        $('#singleDeptFilter').hide();
                        $('#columnSelectionRow').hide();
                        $('#customTables').val(null).trigger('change');
                        initCustomTablesSelect2();
                        initCustomDeptSelect2();
                    } else {
                        $('#customTableSelection').hide();
                        $('#customDeptFilter').hide();
                        $('#customDateFromFilter').hide();
                        $('#customDateToFilter').hide();
                        // Show global date filters for non-custom reports
                        $('#dateFrom').closest('.col-md-4, .form-group').show();
                        $('#dateTo').closest('.col-md-4, .form-group').show();
                        
                        // Hide column selection first
                        $('#columnSelectionRow').hide();
                        
                        // Show/hide department filters based on report type
                        // Show multi-department filter for all report types
                        if (reportType) {
                            $('#singleDeptFilter').hide();
                            $('#multiDeptFilter').show(); // show only after report type selected
                            $('#dateFrom').closest('.col-md-4, .form-group').show();
                            $('#dateTo').closest('.col-md-4, .form-group').show();
                            initDeptSelect2();
                            updateDeptSelectionCount();
                            
                            // Only show column selection if report type has columns defined
                            if (reportColumns[reportType]) {
                                console.log('Calling loadColumnCheckboxes with forceDefault=true');
                                loadColumnCheckboxes(reportType, true); // Force default selections
                                $('#columnSelectionRow').show();
                            }
                        } else {
                            // Keep both hidden when no report type selected
                            $('#singleDeptFilter').hide();
                            $('#multiDeptFilter').hide();
                            $('#dateFrom').closest('.col-md-4, .form-group').hide();
                            $('#dateTo').closest('.col-md-4, .form-group').hide();
                        }
                    }
                });

                // Handle custom table selection
                $('#customTables').on('change', function() {
                    const selectedTables = $(this).val();
                    if (selectedTables && selectedTables.length > 0) {
                        loadCustomTableColumns(selectedTables);
                    }
                });

                function loadCustomTableColumns(tableNames) {
                    $.ajax({
                        url: 'includes/ajaxFile/getTableColumns.php',
                        method: 'POST',
                        data: { tables: JSON.stringify(tableNames) },
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                // Update reportColumns.custom with fetched columns from all tables
                                reportColumns.custom = response.columns.map(col => ({
                                    id: col,
                                    label: col.replace(/_/g, ' ').split(' ').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' '),
                                    default: true
                                }));
                                
                                // Check if any selected table has department-related columns
                                const hasDeptColumn = response.columns.some(col => {
                                    const colName = col.includes('.') ? col.split('.')[1] : col;
                                    return ['dept', 'dept_id', 'department'].includes(colName.toLowerCase());
                                });
                                
                                // Check if any selected table has date-related columns
                                const hasDateColumn = response.columns.some(col => {
                                    const colName = col.includes('.') ? col.split('.')[1] : col;
                                    return colName.toLowerCase().includes('date') || 
                                           ['created_at', 'updated_at', 'month_year', 'start_date', 'end_date', 'joining_date'].includes(colName.toLowerCase());
                                });
                                
                                // Show/hide department filter based on column availability
                                if (hasDeptColumn) {
                                    $('#customDeptFilter').show();
                                } else {
                                    $('#customDeptFilter').hide();
                                    $('#customDeptMultiFilter').val(null).trigger('change');
                                }
                                
                                // Show/hide date filters based on column availability
                                if (hasDateColumn) {
                                    // Show custom date filters and keep global hidden in custom mode
                                    $('#customDateFromFilter').show();
                                    $('#customDateToFilter').show();
                                    $('#dateFrom').closest('.col-md-4, .form-group').hide();
                                    $('#dateTo').closest('.col-md-4, .form-group').hide();
                                } else {
                                    // Hide custom date filters and ensure global remain hidden in custom mode
                                    $('#customDateFromFilter').hide();
                                    $('#customDateToFilter').hide();
                                    $('#customDateFrom').val('');
                                    $('#customDateTo').val('');
                                    $('#dateFrom').closest('.col-md-4, .form-group').hide();
                                    $('#dateTo').closest('.col-md-4, .form-group').hide();
                                }
                                
                                console.log('Department columns available:', hasDeptColumn);
                                console.log('Date columns available:', hasDateColumn);
                                
                                // Populate table selector dropdown for custom reports
                                if (tableNames.length > 1) {
                                    const $selectByTable = $('#selectByTable');
                                    $selectByTable.empty();
                                    $selectByTable.append('<option value="">Select by Table...</option>');
                                    
                                    // Get unique table names from columns
                                    const tables = {};
                                    response.columns.forEach(col => {
                                        if (col.includes('.')) {
                                            const tableName = col.split('.')[0];
                                            if (!tables[tableName]) {
                                                tables[tableName] = tableName;
                                            }
                                        }
                                    });
                                    
                                    // Add options for each table
                                    Object.keys(tables).forEach(table => {
                                        const displayName = table.replace(/_/g, ' ').split(' ').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ');
                                        $selectByTable.append(`<option value="${table}">${displayName}</option>`);
                                    });
                                    
                                    $('#selectByTableContainer').show();
                                } else {
                                    $('#selectByTableContainer').hide();
                                }
                                
                                // Load checkboxes for custom report
                                loadColumnCheckboxes('custom');
                                $('#columnSelectionRow').show();
                            } else {
                                Swal.fire('Error', response.message || 'Failed to load columns', 'error');
                            }
                        },
                        error: function() {
                            Swal.fire('Error', 'Failed to fetch table columns', 'error');
                        }
                    });
                }

                // Update department selection count
                function updateDeptSelectionCount() {
                    const count = $('#deptMultiFilter').val() ? $('#deptMultiFilter').val().length : 0;
                    const label = $('#multiDeptFilter label');
                    if (count > 0) {
                        label.html(`Select Departments <span class="badge badge-success">${count} selected</span>`);
                    } else {
                        label.html('Select Departments');
                    }
                }

                // Track Select2 changes
                $('#deptMultiFilter').on('select2:select select2:unselect', function() {
                    updateDeptSelectionCount();
                });

                function loadColumnCheckboxes(reportType, forceDefault = false) {
                    console.log('=== loadColumnCheckboxes START ===');
                    console.log('reportType:', reportType);
                    console.log('forceDefault:', forceDefault);
                    console.log('reportColumns[reportType] length:', reportColumns[reportType] ? reportColumns[reportType].length : 'undefined');
                    console.log('reportColumns[reportType]:', reportColumns[reportType]);
                    
                    const columns = reportColumns[reportType];
                    const $container = $('#columnSortableContainer');
                    const $select = $('#columnMultiSelect');
                    
                    console.log('Before clear - $select.val():', $select.val());
                    
                    // Clear existing items and select options FIRST
                    $container.empty();
                    $select.empty();
                    
                    // Destroy Select2 if exists
                    if ($select.data('select2')) {
                        $select.select2('destroy');
                    }
                    
                    // Get currently selected columns to preserve user selections (only if not forcing default)
                    // Since we cleared above, this will always be empty when forceDefault is true
                    const currentSelected = forceDefault ? [] : [];
                    
                    console.log('currentSelected:', currentSelected);
                    
                    // Extract base column names from currently selected (remove table prefix)
                    const currentSelectedBase = currentSelected.map(col => {
                        const parts = col.split('.');
                        return parts.length > 1 ? parts[parts.length - 1] : col;
                    });
                    
                    console.log('currentSelectedBase:', currentSelectedBase);
                    
                    // Track default and selected columns
                    const defaultColumns = [];
                    let htmlItems = '';
                    
                    columns.forEach(function(col, index) {
                        const option = $('<option></option>')
                            .attr('value', col.id)
                            .text(col.label);
                        
                        // Extract base column name for comparison
                        const colBase = col.id.split('.').pop();
                        
                        // Determine if column should be selected
                        let isSelected = false;
                        
                        if (forceDefault) {
                            // Force default selection when switching report types - ignore previous selections
                            if (col.default) {
                                isSelected = true;
                                defaultColumns.push(col.id);
                            }
                        } else {
                            // Preserve user selections when not forcing defaults
                            if (currentSelected.includes(col.id)) {
                                isSelected = true;
                                defaultColumns.push(col.id);
                            } else if (currentSelectedBase.includes(colBase) && reportType === 'custom') {
                                isSelected = true;
                                defaultColumns.push(col.id);
                            } else if (col.default && reportType !== 'custom') {
                                isSelected = true;
                                defaultColumns.push(col.id);
                            }
                        }
                        
                        if (isSelected) {
                            option.attr('selected', 'selected');
                        }
                        
                        $select.append(option);
                        
                        // Build draggable column item
                        const checked = isSelected ? 'checked' : '';
                        // Escape column ID for use in HTML attribute
                        const safeColId = col.id.replace(/[^a-zA-Z0-9_]/g, '_');
                        htmlItems += `
                            <div class="column-item" draggable="true" data-column-id="${col.id}" data-column-index="${index}">
                                <span class="drag-handle">☰</span>
                                <input type="checkbox" id="col_${safeColId}" value="${col.id}" class="column-checkbox" ${checked}>
                                <label for="col_${safeColId}">${col.label}</label>
                            </div>
                        `;
                    });
                    
                    // Render draggable items
                    $container.html(htmlItems);
                    
                    // Initialize drag and drop
                    initDragAndDrop();
                    
                    // Initialize or reinitialize Select2
                    if ($select.data('select2')) {
                        $select.select2('destroy');
                    }
                    
                    $select.select2({
                        theme: 'bootstrap4',
                        placeholder: 'Select columns to display',
                        allowClear: true,
                        closeOnSelect: false,
                        width: '100%'
                    });
                    
                    console.log('defaultColumns to set:', defaultColumns);
                    
                    // Set selected values
                    $select.val(defaultColumns).trigger('change');
                    
                    console.log('After setting - $select.val():', $select.val());
                    console.log('Column items in DOM:', $('.column-item').length);
                    console.log('=== loadColumnCheckboxes END ===');
                    
                    // Update column count badge
                    updateColumnCount();
                }

                // Initialize drag and drop for columns
                function initDragAndDrop() {
                    let draggedElement = null;

                    $(document).on('dragstart', '.column-item', function(e) {
                        draggedElement = this;
                        $(this).addClass('dragging');
                        e.originalEvent.dataTransfer.effectAllowed = 'move';
                        e.originalEvent.dataTransfer.setData('text/html', this.innerHTML);
                    });

                    $(document).on('dragend', '.column-item', function(e) {
                        $(this).removeClass('dragging');
                        $('.column-item').removeClass('drag-over');
                    });

                    $(document).on('dragover', '.column-item', function(e) {
                        e.preventDefault();
                        e.originalEvent.dataTransfer.dropEffect = 'move';
                        if (this !== draggedElement) {
                            $(this).addClass('drag-over');
                        }
                    });

                    $(document).on('dragleave', '.column-item', function(e) {
                        $(this).removeClass('drag-over');
                    });

                    $(document).on('drop', '.column-item', function(e) {
                        e.preventDefault();
                        if (this !== draggedElement) {
                            // Swap the elements
                            $(draggedElement).insertBefore($(this));
                            updateColumnSelectOrder();
                        }
                        $('.column-item').removeClass('drag-over');
                    });
                }

                // Update the hidden select with new column order
                function updateColumnSelectOrder() {
                    const $select = $('#columnMultiSelect');
                    const newOrder = [];
                    
                    $('#columnSortableContainer .column-item').each(function() {
                        const columnId = $(this).data('column-id');
                        newOrder.push(columnId);
                    });
                    
                    // Reorder the select options to match the drag order
                    newOrder.forEach(colId => {
                        const $option = $select.find(`option[value="${colId}"]`);
                        $select.append($option);
                    });
                }

                // Update selected column count badge
                function updateColumnCount() {
                    const count = $('#columnSortableContainer .column-checkbox:checked').length;
                    const total = $('#columnSortableContainer .column-checkbox').length;
                    $('#selectedColumnCount').text(count + ' of ' + total + ' selected');
                }

                // Handle column checkbox changes
                $(document).on('change', '.column-checkbox', function() {
                    const columnId = $(this).val();
                    const $select = $('#columnMultiSelect');
                    const $option = $select.find(`option[value="${columnId}"]`);
                    
                    if ($(this).is(':checked')) {
                        $option.attr('selected', 'selected');
                        $(this).closest('.column-item').css('background-color', '#fff9e6');
                    } else {
                        $option.removeAttr('selected');
                        $(this).closest('.column-item').css('background-color', '#fff');
                    }
                    
                    $select.val($select.val()).trigger('change');
                    updateColumnCount();
                });

                // Select All Columns
                $('#selectAllColumnsBtn').on('click', function() {
                    $('#columnSortableContainer .column-checkbox:not(:checked)').click();
                });

                // Deselect All Columns
                $('#deselectAllColumnsBtn').on('click', function() {
                    $('#columnSortableContainer .column-checkbox:checked').click();
                });

                // Select columns by table
                $('#selectByTable').on('change', function() {
                    const selectedTable = $(this).val();
                    if (!selectedTable) return;
                    
                    // Find all checkboxes that belong to the selected table
                    $('#columnSortableContainer .column-checkbox').each(function() {
                        const columnId = $(this).val();
                        // Check if column starts with table name (e.g., "employees.")
                        if (columnId.startsWith(selectedTable + '.')) {
                            if (!$(this).is(':checked')) {
                                $(this).click();
                            }
                        }
                    });
                    
                    // Reset dropdown
                    $(this).val('');
                });

                // Generate Report
                $('#generateReportBtn').on('click', function() {
                    const reportType = $('#reportType').val();
                    if (!reportType) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Report Type Required',
                            text: 'Please select a report type'
                        });
                        return;
                    }

                    // Get selected columns in their dragged order (from the draggable container)
                    const selectedColumns = [];
                    $('#columnSortableContainer .column-checkbox:checked').each(function() {
                        selectedColumns.push($(this).val());
                    });

                    if (selectedColumns.length === 0) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'No Columns Selected',
                            text: 'Please select at least one column to display'
                        });
                        return;
                    }

                    // Get department filter value(s)
                    let departments = [];
                    if ($('#multiDeptFilter').is(':visible')) {
                        // Multi-select for all report types
                        $('#deptMultiFilter option:selected').each(function() {
                            departments.push($(this).val());
                        });
                        // If none selected, treat as all departments
                    } else {
                        // Single select for other reports
                        const singleDept = $('#deptFilter').val();
                        if (singleDept) {
                            departments = [singleDept];
                        }
                    }

                    // Prepare filter data
                    const filterData = {
                        reportType: reportType,
                        columns: selectedColumns,
                        departments: departments,
                        dateFrom: $('#dateFrom').val(),
                        dateTo: $('#dateTo').val(),
                        status: $('#statusFilter').val(),
                        hasFullAccess: <?= $has_full_access ? 'true' : 'false' ?>,
                        userDept: '<?= $user_dept ?>'
                    };

                    // Add custom table name if custom report
                    if (reportType === 'custom') {
                        const customTables = $('#customTables').val();
                        if (!customTables || customTables.length === 0) {
                            Swal.fire({
                                icon: 'warning',
                                title: 'Tables Required',
                                text: 'Please select at least one table for the custom report'
                            });
                            return;
                        }
                        
                        // Get selected departments for custom report
                        let customDepts = [];
                        $('#customDeptMultiFilter option:selected').each(function() {
                            customDepts.push($(this).val());
                        });
                        
                        filterData.customTables = customTables;
                        filterData.customDepartments = customDepts;
                        
                        // Get custom date filters if visible
                        if ($('#customDateFromFilter').is(':visible')) {
                            filterData.dateFrom = $('#customDateFrom').val();
                        }
                        if ($('#customDateToFilter').is(':visible')) {
                            filterData.dateTo = $('#customDateTo').val();
                        }
                    }

                    // Show loading
                    Swal.fire({
                        title: 'Generating Report...',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    // AJAX request to generate report
                    $.ajax({
                        url: 'includes/ajaxFile/ajaxReports.php',
                        type: 'POST',
                        data: filterData,
                        dataType: 'json',
                        success: function(response) {
                            Swal.close();
                            console.log('=== REPORT RESPONSE ===');
                            console.log('Full response:', response);
                            console.log('Success:', response.success);
                            console.log('Data length:', response.data ? response.data.length : 0);
                            console.log('Headers:', response.headers);
                            console.log('First data row:', response.data && response.data.length > 0 ? response.data[0] : 'No data');
                            console.log('======================');
                            
                            if (response.success) {
                                displayReport(response.data, response.headers, reportType, selectedColumns, filterData.columns);
                                $('#exportExcelBtn, #exportPdfBtn').show();
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: response.message || 'Failed to generate report'
                                });
                            }
                        },
                        error: function(xhr, status, error) {
                            Swal.close();
                            console.error('AJAX error:', error, xhr);
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: 'Failed to generate report: ' + error
                            });
                        }
                    });
                });

                function displayReport(data, headers, reportType, selectedColumnsOrder, columnIds) {
                    console.log('displayReport called with:');
                    console.log('- Data:', data);
                    console.log('- Data length:', data ? data.length : 0);
                    console.log('- Headers:', headers);
                    console.log('- Headers length:', headers ? headers.length : 0);
                    console.log('- Column IDs:', columnIds);
                    console.log('- Column IDs length:', columnIds ? columnIds.length : 0);
                    console.log('- Report type:', reportType);
                    console.log('- Selected columns order:', selectedColumnsOrder);
                    
                    // Ensure headers and columnIds have same length
                    if (headers.length !== columnIds.length) {
                        console.error('MISMATCH: Headers length (' + headers.length + ') != Column IDs length (' + columnIds.length + ')');
                        console.error('Headers:', headers);
                        console.error('Column IDs:', columnIds);
                    }
                    
                    // Build table headers (store, apply later after cleanup)
                    let headerHtml = '<tr>';
                    headers.forEach(function(header) {
                        headerHtml += `<th>${header}</th>`;
                    });
                    headerHtml += '</tr>';

                    // Build table rows (store, apply later)
                    let bodyHtml = '';
                    
                    if (!data || data.length === 0) {
                        console.warn('No data returned for report');
                        bodyHtml = '<tr><td colspan="' + headers.length + '" class="text-center text-muted py-4">No records found</td></tr>';
                    } else {
                        console.log('Processing', data.length, 'rows...');
                        data.forEach(function(row, rowIndex) {
                            if (rowIndex < 3) { // Log first 3 rows for debugging
                                console.log('Row', rowIndex, ':', row);
                            }
                            bodyHtml += '<tr>';
                            
                            // IMPORTANT: Use the same number of columns as headers
                            // If we have more columnIds than headers, only use headers.length
                            const columnsToUse = columnIds.slice(0, headers.length);
                            
                            columnsToUse.forEach(function(columnId, colIndex) {
                                // Try to find the column in the row data
                                let cell = '';
                                
                                // Direct key match
                                if (row.hasOwnProperty(columnId)) {
                                    cell = row[columnId];
                                    if (rowIndex < 3 && colIndex < 5) {
                                        console.log('Direct match - columnId:', columnId, 'cell:', cell);
                                    }
                                } else {
                                    // Try with underscore replacement for prefixed columns (replace all dots)
                                    const altKey = columnId.replace(/\./g, '_');
                                    if (row.hasOwnProperty(altKey)) {
                                        cell = row[altKey];
                                        if (rowIndex < 3 && colIndex < 5) {
                                            console.log('Alt match - columnId:', columnId, 'altKey:', altKey, 'cell:', cell);
                                        }
                                    } else {
                                        if (rowIndex < 3 && colIndex < 5) {
                                            console.error('NO MATCH - columnId:', columnId, 'altKey:', altKey, 'Available keys:', Object.keys(row).slice(0, 10));
                                        }
                                    }
                                }
                                
                                bodyHtml += `<td>${cell !== null && cell !== undefined ? cell : ''}</td>`;
                            });
                            bodyHtml += '</tr>';
                        });
                    }
                    console.log('Body HTML length:', bodyHtml.length);
                    console.log('Header columns:', headers.length, 'Body columns per row:', columnIds.slice(0, headers.length).length);
                    
                    // (Delay applying header/body until after potential wrapper cleanup)
                    
                    console.log('Table body HTML set, checking DOM...');
                    console.log('Rows in tbody:', $('#reportTableBody tr').length);
                    
                    // CRITICAL: Verify column counts match
                    const firstRow = $('#reportTableBody tr:first');
                    const tdCount = firstRow.find('td').length;
                    const thCount = $('#reportTableHead th').length;
                    console.log('TH count:', thCount, 'TD count in first row:', tdCount);
                    
                    if (thCount !== tdCount && data.length > 0) {
                        console.error('COLUMN MISMATCH! TH:', thCount, 'TD:', tdCount);
                        console.error('This will cause DataTables to fail');
                        Swal.fire({
                            icon: 'error',
                            title: 'Table Structure Error',
                            text: 'Column count mismatch. Headers: ' + thCount + ', Data columns: ' + tdCount
                        });
                        return;
                    }

                    // Update title
                    $('#reportTitle').text(reportType.charAt(0).toUpperCase() + reportType.slice(1) + ' Report - ' + data.length + ' records');

                    // Show/hide table container based on data
                    if (data.length > 0 && headers.length > 0) {
                        console.log('Showing report table container');
                        $('#reportTableContainer').show();
                        
                        // Safely destroy/reinitialize DataTable
                        if ($.fn.DataTable.isDataTable('#reportTable')) {
                            try {
                                $('#reportTable').DataTable().destroy();
                                console.log('DataTable destroyed in displayReport');
                            } catch (e) {
                                console.error('Error destroying DataTable in displayReport:', e);
                            }
                        }
                        // Remove previous DataTables wrapper if exists
                        if ($('#reportTable_wrapper').length) {
                            $('#reportTable_wrapper').remove();
                        }
                        // Rebuild single clean table markup
                        const tableMarkup = '<table id="reportTable" class="table table-bordered table-striped dt-responsive nowrap" width="100%">'
                            + '<thead id="reportTableHead">' + headerHtml + '</thead>'
                            + '<tbody id="reportTableBody">' + bodyHtml + '</tbody>'
                            + '</table>';
                        $('#reportTableContainer').html(tableMarkup);
                        
                        // Generate filename with report name and timestamp
                        const reportName = $('#reportType').val();
                        const now = new Date();
                        const hours = String(now.getHours()).padStart(2, '0');
                        const minutes = String(now.getMinutes()).padStart(2, '0');
                        const seconds = String(now.getSeconds()).padStart(2, '0');
                        const date = String(now.getDate()).padStart(2, '0');
                        const month = String(now.getMonth() + 1).padStart(2, '0');
                        const year = now.getFullYear();
                        const filename = `${reportName}_${hours}${minutes}${seconds}${date}${month}${year}`;
                        
                        console.log('Initializing DataTable on fresh table...');
                        
                        // Use setTimeout to ensure DOM is fully rendered
                        setTimeout(function() {
                            try {
                                $('#reportTable').DataTable({
                                    dom: 'Bfrtip',
                                    buttons: [
                                        'copy',
                                        {
                                            extend: 'excel',
                                            filename: filename
                                        },
                                        {
                                            extend: 'pdf',
                                            filename: filename
                                        },
                                        'print'
                                    ],
                                    responsive: true,
                                    pageLength: 50
                                });
                                console.log('DataTable initialized successfully');
                            } catch (e) {
                                console.error('Error initializing DataTable:', e);
                            }
                        }, 100); // Small delay to ensure DOM is ready
                    } else {
                        $('#reportTableContainer').hide();
                        if ($.fn.DataTable.isDataTable('#reportTable')) {
                            $('#reportTable').DataTable().clear().destroy();
                        }
                    }
                }

                // Export to Excel
                $('#exportExcelBtn').on('click', function() {
                    $('#reportTable').DataTable().button('.buttons-excel').trigger();
                });

                // Export to PDF
                $('#exportPdfBtn').on('click', function() {
                    $('#reportTable').DataTable().button('.buttons-pdf').trigger();
                });

                // Reset form
                $('#resetBtn').on('click', function() {
                    console.log('Reset button clicked');
                    
                    // Safely destroy DataTable FIRST before clearing anything
                    if ($.fn.DataTable.isDataTable('#reportTable')) {
                        try {
                            $('#reportTable').DataTable().destroy();
                            console.log('DataTable destroyed during reset');
                        } catch (e) {
                            console.error('Error destroying DataTable during reset:', e);
                        }
                    }
                    
                    // Reset report type
                    $('#reportType').val('');
                    
                    // Reset all filter fields
                    $('#deptFilter').val('');
                    $('#dateFrom').val('');
                    $('#dateTo').val('');
                    $('#statusFilter').val('');
                    // Show global date filters after reset
                    $('#dateFrom').closest('.col-md-4, .form-group').show();
                    $('#dateTo').closest('.col-md-4, .form-group').show();
                    
                    // Reset custom report date filters
                    $('#customDateFrom').val('');
                    $('#customDateTo').val('');
                    
                    // Reset multi-select filters
                    $('#deptMultiFilter').val(null).trigger('change');
                    $('#customDeptMultiFilter').val(null).trigger('change');
                    $('#customTables').val(null).trigger('change');
                    
                    // Clear and hide column selection
                    $('#columnSortableContainer').empty();
                    $('#columnMultiSelect').empty();
                    $('#selectedColumnCount').text('0 selected');
                    $('#columnSelectionRow').hide();
                    
                    // Hide custom report elements
                    $('#customTableSelection').hide();
                    $('#customDeptFilter').hide();
                    $('#customDateFromFilter').hide();
                    $('#customDateToFilter').hide();
                    $('#selectByTableContainer').hide();
                    $('#selectByTable').empty().append('<option value="">Select by Table...</option>');
                    
                    // Hide report table and export buttons
                    $('#reportTableContainer').hide();
                    $('#exportExcelBtn, #exportPdfBtn').hide();
                    
                    // Clear report table content AFTER destroying DataTable
                    $('#reportTableHead').empty();
                    $('#reportTableBody').empty();
                    
                    // Show default department filter
                    $('#singleDeptFilter').show();
                    $('#multiDeptFilter').hide();
                    
                    // Reset department selection count
                    updateDeptSelectionCount();
                    
                    console.log('Reset completed');
                });
            });
        </script>

    </body>
</html>
<?php
}else{
    header("Location: login.php");
    exit();
}
?>
