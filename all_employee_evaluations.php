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
$has_access = in_array($user_role, $allowed_roles) || $user_type == 'gm' || $user_type == 'administrator'
    // or an explicit 'Access Page: All Employee Evaluations' Special Access grant
    || user_has_special_access($conDB, $empid ?? '', 'access_all_employee_evaluations', $user_role ?? '', $user_type ?? '', $is_system_admin ?? false);

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

    <link href="assets/css/smart_request.css?v=<?= @filemtime(__DIR__ . '/assets/css/smart_request.css') ?>" rel="stylesheet" type="text/css" />
    <style>
        .aee-stats { display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)); gap: 12px; margin-bottom: 20px; }
        @media (max-width: 1199px) { .aee-stats { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
        @media (max-width: 575px) { .aee-stats { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
        .aee-stats .sr-stat { background: var(--sr-surface); }
        .aee-bar { height: 6px; border-radius: 6px; background: var(--sr-surface-3); overflow: hidden; }
        .aee-bar span { display: block; height: 100%; border-radius: 6px; background: var(--sr-accent); }
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
            <div class="content sr-page">
                <div class="container-fluid">
                    <div class="sr-head">
                        <div>
                            <h1>Employee Performance Evaluations</h1>
                            <p>Every evaluation submitted by managers, with score breakdown.</p>
                        </div>
                    </div>

                    <div class="aee-stats">
                        <div class="sr-stat">
                            <div class="sr-stat-label">Evaluations <i class="mdi mdi-clipboard-text"></i></div>
                            <div class="sr-stat-value" data-aee="all">&ndash;</div>
                            <div class="sr-stat-sub">Average score: <b data-aee="avg">&ndash;</b></div>
                        </div>
                        <div class="sr-stat is-green">
                            <div class="sr-stat-label">Excellent (90+) <i class="mdi mdi-star"></i></div>
                            <div class="sr-stat-value" data-aee="excellent">&ndash;</div>
                        </div>
                        <div class="sr-stat is-sky">
                            <div class="sr-stat-label">Good (70-89) <i class="mdi mdi-thumb-up-outline"></i></div>
                            <div class="sr-stat-value" data-aee="good">&ndash;</div>
                        </div>
                        <div class="sr-stat is-amber">
                            <div class="sr-stat-label">Average (50-69) <i class="mdi mdi-minus-circle-outline"></i></div>
                            <div class="sr-stat-value" data-aee="average">&ndash;</div>
                        </div>
                        <div class="sr-stat is-red">
                            <div class="sr-stat-label">Poor (&lt;50) <i class="mdi mdi-alert-circle-outline"></i></div>
                            <div class="sr-stat-value" data-aee="poor">&ndash;</div>
                        </div>
                    </div>

                    <div class="sr-card">
                        <!-- Filters -->
                        <div class="sr-filter-grid" style="border-bottom: 1px solid var(--sr-border);">
                            <div>
                                <label for="filterDepartment">Department</label>
                                <select class="form-control select2" id="filterDepartment">
                                    <option value="">All Departments</option>
                                    <?php foreach ($departments as $dept): ?>
                                        <option value="<?= $dept['id'] ?>"><?= htmlspecialchars($dept['dep_nme']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label for="filterEmployee">Employee</label>
                                <input type="text" class="form-control" id="filterEmployee" placeholder="Search by name or ID">
                            </div>
                            <div>
                                <label for="filterFromDate">From Date</label>
                                <input type="date" class="form-control" id="filterFromDate">
                            </div>
                            <div>
                                <label for="filterToDate">To Date</label>
                                <input type="date" class="form-control" id="filterToDate">
                            </div>
                            <div>
                                <label for="filterScore">Min Score</label>
                                <select class="form-control" id="filterScore">
                                    <option value="">All Scores</option>
                                    <option value="90">90+ (Excellent)</option>
                                    <option value="70">70+ (Good)</option>
                                    <option value="50">50+ (Average)</option>
                                    <option value="0">Below 50 (Poor)</option>
                                </select>
                            </div>
                            <div class="sr-filter-actions">
                                <button type="button" class="sr-btn sr-btn-primary" id="applyFilters"><i class="mdi mdi-filter-variant"></i> Apply</button>
                                <button type="button" class="sr-btn" id="resetFilters"><i class="mdi mdi-refresh"></i> Reset</button>
                            </div>
                        </div>

                        <div class="sr-toolbar">
                            <div class="sr-search">
                                <i class="mdi mdi-magnify"></i>
                                <input type="search" id="aeeSearch" placeholder="Search..." autocomplete="off">
                            </div>
                            <div class="sr-toolbar-right">
                                <div id="srExportButtons"></div>
                            </div>
                        </div>

                        <div class="sr-table-wrap">
                            <table id="evaluationsTable" class="table sr-table dt-responsive nowrap" style="width: 100%;">
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
                                        <th class="text-right">Action</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
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
    <script src="assets/js/jquery.app.js?t=<?= time() ?>"></script>

    <script>
    $(document).ready(function() {

        function esc(s) {
            return String(s === null || s === undefined ? '' : s)
                .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
        }
        function initials(name) {
            return String(name || '').trim().split(/\s+/).slice(0, 2).map(function(w) { return w.charAt(0); }).join('').toUpperCase();
        }
        function scoreTone(score) {
            score = parseFloat(score) || 0;
            if (score < 50) return 'tone-red';
            if (score < 70) return 'tone-amber';
            if (score < 90) return 'tone-sky';
            return 'tone-green';
        }

        // Initialize Select2
        $('.select2').select2({
            placeholder: "Select an option",
            allowClear: true,
            width: '100%'
        });

        var exportCols = [1, 2, 3, 4, 5, 6, 7];

        // Initialize DataTable
        var table = $('#evaluationsTable').DataTable({
            dom: "Brtip",
            buttons: [
                { extend: 'excel', text: '<i class="mdi mdi-file-excel"></i> Excel', title: 'Employee Evaluations Report', exportOptions: { columns: exportCols, orthogonal: 'export' } },
                { extend: 'pdf', text: '<i class="mdi mdi-file-pdf"></i> PDF', title: 'Employee Evaluations Report', exportOptions: { columns: exportCols, orthogonal: 'export' } },
                { extend: 'print', text: '<i class="mdi mdi-printer"></i> Print', title: 'Employee Evaluations Report', exportOptions: { columns: exportCols, orthogonal: 'export' } }
            ],
            processing: true,
            serverSide: false,
            responsive: true,
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
                    d.user_dept = <?= (int)$user_dept ?>;
                },
                dataSrc: function(json) {
                    var rows = (json && json.data) || [];
                    var c = { all: rows.length, excellent: 0, good: 0, average: 0, poor: 0 }, sum = 0;
                    rows.forEach(function(r) {
                        var s = parseFloat(r.total_score) || 0;
                        sum += s;
                        if (s >= 90) c.excellent++; else if (s >= 70) c.good++; else if (s >= 50) c.average++; else c.poor++;
                    });
                    $.each(c, function(k, v) { $('[data-aee="' + k + '"]').text(v); });
                    $('[data-aee="avg"]').text(rows.length ? (sum / rows.length).toFixed(1) : '0');
                    return rows;
                }
            },
            columns: [
                { data: 'id' },
                { data: 'employee_emp_id', visible: false },
                {
                    data: 'employee_name',
                    render: function(data, type, row) {
                        if (type !== 'display') return data;
                        return '<div class="sr-person"><span class="sr-avatar sr-avatar-sm">' + esc(initials(data)) + '</span>'
                            + '<div><span class="sr-person-name">' + esc(data) + '</span><span class="sr-cell-sub">' + esc(row.employee_emp_id) + '</span></div></div>';
                    }
                },
                { data: 'dept_name', render: function(data, type) { return type === 'display' ? esc(data) : data; } },
                { data: 'employee_position', render: function(data, type) { return type === 'display' ? esc(data) : data; } },
                { data: 'manager_name', render: function(data, type) { return type === 'display' ? esc(data || 'N/A') : data; } },
                {
                    data: 'total_score',
                    render: function(data, type) {
                        if (type !== 'display') return data;
                        return '<span class="sr-pill ' + scoreTone(data) + '"><span class="sr-dot"></span>' + esc(data) + '/100</span>';
                    }
                },
                { data: 'created_at', render: function(data, type) { return type === 'display' ? '<span class="sr-date">' + esc(data) + '</span>' : data; } },
                {
                    data: null,
                    orderable: false,
                    className: 'text-right',
                    render: function(data, type, row) {
                        return '<button type="button" class="sr-open-btn border-0 view-eval-details" data-id="' + esc(row.id) + '"><i class="mdi mdi-eye-outline"></i> View</button>';
                    }
                }
            ],
            order: [[0, 'desc']],
            columnDefs: [
                { targets: [0], visible: false }
            ],
            language: {
                info: 'Showing _START_ to _END_ of _TOTAL_ evaluations',
                infoEmpty: 'No evaluations',
                infoFiltered: '',
                emptyTable: '<div class="sr-empty"><i class="mdi mdi-clipboard-text"></i>No evaluations found</div>',
                zeroRecords: '<div class="sr-empty"><i class="mdi mdi-magnify"></i>No matching evaluations</div>',
                paginate: { next: '<i class="mdi mdi-chevron-right"></i>', previous: '<i class="mdi mdi-chevron-left"></i>' },
                processing: '<div class="spinner-border text-primary" role="status"><span class="sr-only">Loading...</span></div>'
            }
        });

        table.buttons().container().appendTo('#srExportButtons');
        $('#aeeSearch').on('input', function() { table.search(this.value).draw(); });

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

        // Whole row opens the details (except clicks on buttons)
        $('#evaluationsTable tbody').on('click', 'tr', function(e) {
            if ($(e.target).closest('a, button, .dtr-control').length || $(this).hasClass('child')) return;
            var row = table.row(this).data();
            if (row && row.id) showEvaluation(row.id);
        });
        $(document).on('click', '.view-eval-details', function() {
            showEvaluation($(this).data('id'));
        });

        // Evaluation details popup
        var criteria = [
            ['punctuality', 'Punctuality Attendance'],
            ['achieving_time', 'Achieving at the specified time'],
            ['job_knowledge', 'Knowledge of job'],
            ['problem_solving', 'The Ability to solve problems'],
            ['feedback_receptiveness', 'Receptiveness to Feedback and Instructions'],
            ['self_development', 'Self & Professional Development'],
            ['work_under_pressure', 'Work under pressure'],
            ['communication_teamwork', 'Communication skills and Teamwork'],
            ['creativity_response', 'Creativity and speed of response'],
            ['initiative_cooperation', 'Initiative and cooperation']
        ];

        function showEvaluation(evalId) {
            Swal.fire({
                title: 'Evaluation Details',
                html: '<div class="py-4"><div class="spinner-border text-primary" role="status"><span class="sr-only">Loading...</span></div></div>',
                width: '820px',
                showCloseButton: true,
                showConfirmButton: false,
                allowOutsideClick: false,
                customClass: { popup: 'sr-addline-popup' },
                didOpen: function() {
                    $.ajax({
                        url: 'includes/ajaxFile/ajaxEvaluation.php',
                        method: 'POST',
                        data: { action: 'get_evaluation_details', evaluation_id: evalId },
                        dataType: 'json'
                    }).done(function(response) {
                        if (!response || response.status !== 'success') {
                            Swal.update({ html: '<div class="sr-page"><div class="sr-notice tone-red mb-0"><i class="mdi mdi-alert-circle-outline"></i><div>Failed to load evaluation details.</div></div></div>' });
                            return;
                        }
                        var d = response.data;
                        var kv = function(label, value) {
                            return '<div class="row-kv"><dt>' + esc(label) + '</dt><dd>' + value + '</dd></div>';
                        };
                        var rows = criteria.map(function(c) {
                            var v = parseFloat(d[c[0]]) || 0;
                            return '<tr><td>' + esc(c[1]) + '</td>'
                                + '<td style="width: 38%;"><div class="aee-bar"><span style="width:' + Math.max(0, Math.min(100, v * 10)) + '%"></span></div></td>'
                                + '<td class="text-right"><span class="sr-pill sr-pill-xs ' + scoreTone(v * 10) + '">' + esc(d[c[0]]) + '/10</span></td></tr>';
                        }).join('');
                        var html = '<div class="sr-page text-left">'
                            + '<div class="row"><div class="col-md-6"><dl class="sr-kv">'
                            + kv('Employee Name', esc(d.employee_name)) + kv('Employee ID', esc(d.employee_emp_id))
                            + kv('Department', esc(d.dept_name)) + kv('Position', esc(d.employee_position))
                            + '</dl></div><div class="col-md-6"><dl class="sr-kv">'
                            + kv('Evaluated By', esc(d.manager_name || 'N/A')) + kv('Evaluation Date', esc(d.created_at))
                            + kv('Total Score', '<span class="sr-pill ' + scoreTone(d.total_score) + '">' + esc(d.total_score) + '/100</span>')
                            + '</dl></div></div>'
                            + '<div class="sr-fsec mt-3"><div class="sr-fsec-head"><span><i class="mdi mdi-format-list-checks"></i> Evaluation Criteria</span></div>'
                            + '<table class="sr-lines"><tbody>' + rows + '</tbody></table></div>'
                            + '<div class="sr-fsec mb-0"><div class="sr-fsec-head"><span><i class="mdi mdi-comment-text-outline"></i> Observation/Remarks</span></div>'
                            + '<div class="p-3" style="white-space: pre-wrap;">' + esc(d.observation || 'No observation provided.') + '</div></div>'
                            + '</div>';
                        Swal.update({ html: html });
                    }).fail(function() {
                        Swal.update({ html: '<div class="sr-page"><div class="sr-notice tone-red mb-0"><i class="mdi mdi-alert-circle-outline"></i><div>An error occurred while loading the evaluation details.</div></div></div>' });
                    });
                }
            });
        }
    });
    </script>

</body>
</html>
