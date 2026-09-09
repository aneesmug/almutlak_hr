<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/session_check.php';
require_once __DIR__ . '/includes/special_access_helper.php';
include(__DIR__ . '/includes/avatar_select.php');

$isSystemAdmin = $is_system_admin ?? false;
$canManageAttendance = $isSystemAdmin || user_has_special_access($conDB, $empid ?? '', 'manage_attendance', $user_role ?? '', $user_type ?? '', $isSystemAdmin);
if (!$canManageAttendance) {
    header('Location: dashboard.php');
    exit;
}
?>

<!doctype html>
<html lang="<?= $current_lang ?? 'en' ?>" <?= ($is_rtl ?? false) ? 'dir="rtl"' : '' ?>>

<head>
    <meta charset="utf-8" />
    <title><?= $site_title ?> - <?= __('attendance_record', 'Attendance Record') ?></title>
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
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap4-theme@1.0.0/dist/select2-bootstrap4.min.css" rel="stylesheet" />
    <script src="assets/js/modernizr.min.js"></script>
    <?php if ($is_rtl ?? false): ?>
        <link href="assets/css/style_rtl.css" rel="stylesheet" type="text/css" />
    <?php endif; ?>
    <style>
        .attendance-source-badge { font-size: 11px; padding: 3px 8px; border-radius: 10px; }
        .attendance-source-device { background-color: #e3f2ff; color: #1c6fd6; }
        .attendance-source-manual { background-color: #fff2df; color: #d68c1c; }
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
                                    <h4 class="m-t-0 header-title"><?= __('attendance_record', 'Attendance Record') ?></h4>
                                    <button id="btn-add-attendance" type="button" class="btn btn-primary btn-sm waves-effect waves-light">
                                        <i class="mdi mdi-plus-circle mr-2"></i><?= __('add_attendance', 'Add Record') ?>
                                    </button>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-4">
                                        <label><?= __('employee', 'Employee') ?></label>
                                        <select id="filterEmployee" class="form-control" style="width: 100%;"></select>
                                    </div>
                                    <div class="col-md-3">
                                        <label><?= __('from', 'From') ?></label>
                                        <input type="date" id="filterFromDate" class="form-control">
                                    </div>
                                    <div class="col-md-3">
                                        <label><?= __('to', 'To') ?></label>
                                        <input type="date" id="filterToDate" class="form-control">
                                    </div>
                                    <div class="col-md-2">
                                        <label>&nbsp;</label>
                                        <button id="btn-apply-filters" type="button" class="btn btn-secondary btn-block"><?= __('filter', 'Filter') ?></button>
                                    </div>
                                </div>

                                <table id="attendance_table" class="table table-striped table-bordered dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                                    <thead>
                                        <tr>
                                            <th><?= __('emp_id', 'Emp ID') ?></th>
                                            <th><?= __('employee_name', 'Employee Name') ?></th>
                                            <th><?= __('date') ?></th>
                                            <th><?= __('check_in', 'Check In') ?></th>
                                            <th><?= __('check_out', 'Check Out') ?></th>
                                            <th><?= __('state', 'State') ?></th>
                                            <th><?= __('source', 'Source') ?></th>
                                            <th><?= __('note') ?></th>
                                            <th width="60"><?= __('action_header', 'Action') ?></th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
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
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script src="assets/js/jquery.core.js"></script>
    <script src="assets/js/jquery.app.js?t=<?= time() ?>"></script>

    <script>
    $(document).ready(function() {
        $('#filterEmployee').select2({
            theme: 'bootstrap4',
            placeholder: __('all_employees', 'All employees'),
            allowClear: true,
            ajax: {
                url: './includes/ajaxFile/hrHandler.php',
                type: 'POST',
                dataType: 'json',
                delay: 250,
                data: function(params) {
                    return { ajaxType: 'emp_search_select2', search: params.term };
                },
                processResults: function(response) {
                    if (response.status === 200 && response.data) {
                        return {
                            results: response.data.map(function(emp) {
                                return { id: emp.emp_id, text: emp.emp_id + ' - ' + emp.name + (emp.department ? ' (' + emp.department + ')' : '') };
                            })
                        };
                    }
                    return { results: [] };
                }
            }
        });

        var table = $('#attendance_table').DataTable({
            processing: true,
            serverSide: true,
            order: [[2, 'desc']],
            ajax: {
                url: './includes/ajaxFile/attendanceAjax.php',
                type: 'POST',
                data: function(d) {
                    d.action = 'list_attendance';
                    d.emp_id = $('#filterEmployee').val() || '';
                    d.from_date = $('#filterFromDate').val() || '';
                    d.to_date = $('#filterToDate').val() || '';
                }
            },
            columns: [
                { data: 'emp_id' },
                { data: 'name' },
                { data: 'date' },
                { data: 'time_in' },
                { data: 'time_out' },
                { data: 'state' },
                { data: 'source', render: function(source) {
                    var cls = source === 'manual' ? 'attendance-source-manual' : 'attendance-source-device';
                    var label = source === 'manual' ? __('manual', 'Manual') : __('device', 'Device');
                    return '<span class="attendance-source-badge ' + cls + '">' + label + '</span>';
                }},
                { data: 'note' },
                { data: 'action', orderable: false, searchable: false }
            ],
            language: {
                search: `<span>${__('search')}:</span> _INPUT_`,
                searchPlaceholder: `${__('search')}...`,
                lengthMenu: `${__('show')} _MENU_ ${__('entries')}`,
                info: `${__('showing')} _START_ ${__('to')} _END_ ${__('of')} _TOTAL_ ${__('entries')}`,
                paginate: { first: __('first'), last: __('last'), next: __('next'), previous: __('previous') },
                emptyTable: __('no_data_available_in_table'),
                zeroRecords: __('no_matching_records_found'),
                processing: __('processing', 'Processing...'),
            }
        });

        $('#btn-apply-filters').on('click', function() {
            table.ajax.reload();
        });
        $('#filterEmployee').on('change', function() {
            table.ajax.reload();
        });

        function attendanceFormHtml(empId, empLabel, date, timeIn, timeOut, state, note) {
            var stateOptions = ['Present', 'Absent', 'Late', 'Half Day', 'Leave', 'Incomplete'].map(function(s) {
                return `<option value="${s}" ${s === state ? 'selected' : ''}>${s}</option>`;
            }).join('');
            // Infer punch type from existing data so editing a partial record keeps its shape
            var punchType = 'both';
            if (timeIn && !timeOut) punchType = 'in';
            else if (!timeIn && timeOut) punchType = 'out';
            var punchOptions = [
                ['both', __('punch_both', 'Check In & Check Out')],
                ['in', __('punch_in_only', 'Check In Only')],
                ['out', __('punch_out_only', 'Check Out Only')],
            ].map(function(o) {
                return `<option value="${o[0]}" ${o[0] === punchType ? 'selected' : ''}>${o[1]}</option>`;
            }).join('');
            return `
                <div class="form-group text-left">
                    <label>${__('employee', 'Employee')} *</label>
                    <select id="attEmpId" class="form-control" style="width: 100%;" ${empId ? 'disabled' : ''}></select>
                    <input type="hidden" id="attEmpIdValue" value="${empId || ''}">
                </div>
                <div class="form-group text-left">
                    <label>${__('date')} *</label>
                    <input type="date" id="attDate" class="form-control" value="${date || ''}" ${date ? 'disabled' : ''}>
                    <input type="hidden" id="attDateValue" value="${date || ''}">
                </div>
                <div class="form-group text-left">
                    <label>${__('punch_type', 'Punch Type')} *</label>
                    <select id="attPunchType" class="form-control">${punchOptions}</select>
                </div>
                <div class="form-group text-left" id="attTimeInGroup">
                    <label>${__('check_in', 'Check In')} *</label>
                    <input type="time" id="attTimeIn" class="form-control" value="${timeIn || ''}">
                </div>
                <div class="form-group text-left" id="attTimeOutGroup">
                    <label>${__('check_out', 'Check Out')} *</label>
                    <input type="time" id="attTimeOut" class="form-control" value="${timeOut || ''}">
                </div>
                <div class="form-group text-left">
                    <label>${__('state', 'State')} *</label>
                    <select id="attState" class="form-control">${stateOptions}</select>
                </div>
                <div class="form-group text-left">
                    <label>${__('note')}</label>
                    <textarea id="attNote" class="form-control">${note || ''}</textarea>
                </div>
            `;
        }

        function applyPunchTypeVisibility() {
            var type = $('#attPunchType').val();
            $('#attTimeInGroup').toggle(type === 'both' || type === 'in');
            $('#attTimeOutGroup').toggle(type === 'both' || type === 'out');
            if (type === 'in') $('#attTimeOut').val('');
            if (type === 'out') $('#attTimeIn').val('');
        }

        function initEmpSelectInModal(preselectId, preselectLabel) {
            var $sel = $('#attEmpId');
            $sel.select2({
                theme: 'bootstrap4',
                dropdownParent: Swal.getContainer ? $(Swal.getContainer()) : undefined,
                ajax: {
                    url: './includes/ajaxFile/hrHandler.php',
                    type: 'POST',
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return { ajaxType: 'emp_search_select2', search: params.term };
                    },
                    processResults: function(response) {
                        if (response.status === 200 && response.data) {
                            return {
                                results: response.data.map(function(emp) {
                                    return { id: emp.emp_id, text: emp.emp_id + ' - ' + emp.name + (emp.department ? ' (' + emp.department + ')' : '') };
                                })
                            };
                        }
                        return { results: [] };
                    }
                }
            });
            if (preselectId) {
                var opt = new Option(preselectLabel, preselectId, true, true);
                $sel.append(opt).trigger('change');
            }
        }

        $('#btn-add-attendance').on('click', function() {
            Swal.fire({
                title: __('add_attendance', 'Add Record'),
                html: attendanceFormHtml('', '', '', '', '', 'Present', ''),
                showCancelButton: true,
                confirmButtonColor: APP_COLORS.primary,
                cancelButtonColor: APP_COLORS.danger_dark,
                confirmButtonText: __('save', 'Save'),
                cancelButtonText: __('cancel'),
                showLoaderOnConfirm: true,
                allowOutsideClick: false,
                didOpen: function() {
                    initEmpSelectInModal('', '');
                    applyPunchTypeVisibility();
                    $('#attPunchType').on('change', applyPunchTypeVisibility);
                },
                preConfirm: function() {
                    var empId = $('#attEmpId').val();
                    var date = $('#attDate').val();
                    var punchType = $('#attPunchType').val();
                    var hasIn = !!$('#attTimeIn').val();
                    var hasOut = !!$('#attTimeOut').val();
                    var punchOk = (punchType === 'both' && hasIn && hasOut) ||
                                  (punchType === 'in' && hasIn) ||
                                  (punchType === 'out' && hasOut);
                    if (!empId || !date || !punchOk) {
                        Swal.showValidationMessage(__('fill_required_fields', 'Please fill all required fields.'));
                        return false;
                    }
                    return new Promise(function(resolve, reject) {
                        $.ajax({
                            url: './includes/ajaxFile/attendanceAjax.php',
                            type: 'POST',
                            dataType: 'json',
                            data: {
                                action: 'add_edit_attendance',
                                emp_id: empId,
                                date: date,
                                time_in: $('#attTimeIn').val(),
                                time_out: $('#attTimeOut').val(),
                                state: $('#attState').val(),
                                note: $('#attNote').val(),
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
                    table.ajax.reload(null, false);
                }
            });
        });

        $(document).on('click', '.btn-edit-attendance', function() {
            var empId = $(this).data('emp-id');
            var empLabel = $(this).data('emp-label');
            var date = $(this).data('date');
            var timeIn = $(this).data('time-in');
            var timeOut = $(this).data('time-out');
            var state = $(this).data('state');
            var note = $(this).data('note');

            Swal.fire({
                title: __('edit_attendance', 'Edit Record'),
                html: attendanceFormHtml(empId, empLabel, date, timeIn, timeOut, state, note),
                showCancelButton: true,
                confirmButtonColor: APP_COLORS.primary,
                cancelButtonColor: APP_COLORS.danger_dark,
                confirmButtonText: __('save', 'Save'),
                cancelButtonText: __('cancel'),
                showLoaderOnConfirm: true,
                allowOutsideClick: false,
                didOpen: function() {
                    initEmpSelectInModal(empId, empLabel);
                    applyPunchTypeVisibility();
                    $('#attPunchType').on('change', applyPunchTypeVisibility);
                },
                preConfirm: function() {
                    var punchType = $('#attPunchType').val();
                    var hasIn = !!$('#attTimeIn').val();
                    var hasOut = !!$('#attTimeOut').val();
                    var punchOk = (punchType === 'both' && hasIn && hasOut) ||
                                  (punchType === 'in' && hasIn) ||
                                  (punchType === 'out' && hasOut);
                    if (!punchOk) {
                        Swal.showValidationMessage(__('fill_required_fields', 'Please fill all required fields.'));
                        return false;
                    }
                    return new Promise(function(resolve, reject) {
                        $.ajax({
                            url: './includes/ajaxFile/attendanceAjax.php',
                            type: 'POST',
                            dataType: 'json',
                            data: {
                                action: 'add_edit_attendance',
                                emp_id: $('#attEmpIdValue').val(),
                                date: $('#attDateValue').val(),
                                time_in: $('#attTimeIn').val(),
                                time_out: $('#attTimeOut').val(),
                                state: $('#attState').val(),
                                note: $('#attNote').val(),
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
                    table.ajax.reload(null, false);
                }
            });
        });
    });
    </script>
</body>
</html>
