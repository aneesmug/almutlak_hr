<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/session_check.php';

// Restrict access to privileged users only.
// (or an explicit 'Access Page: Vacation Dates Editor' Special Access grant)
if (!($is_system_admin || $isHR || $isFinance || user_has_special_access($conDB, $empid ?? '', 'access_vacation_dates_by_inv', $user_role ?? '', $user_type ?? '', $is_system_admin ?? false))) {
    header('Location: ./dashboard.php');
    exit();
}

function normalize_date_or_null($date)
{
    $date = trim((string)$date);
    if ($date === '') {
        return null;
    }

    $dt = DateTime::createFromFormat('Y-m-d', $date);
    if (!$dt || $dt->format('Y-m-d') !== $date) {
        return false;
    }

    return $date;
}

$message = '';
$message_type = 'info';
$search_emp_id = trim((string)($_GET['emp_id'] ?? ''));
$employee_name = '';
$requests = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_dates_ajax') {
    header('Content-Type: application/json; charset=utf-8');

    $request_inv_no = trim((string)($_POST['request_inv_no'] ?? ''));
    $start_date_input = normalize_date_or_null($_POST['start_date'] ?? '');
    $arrival_date_input = normalize_date_or_null($_POST['arrival_date'] ?? '');
    $vacdays = null;

    if ($request_inv_no === '') {
        echo json_encode(['success' => false, 'message' => 'request_inv_no is required.']);
        exit();
    }

    if ($start_date_input === false || $arrival_date_input === false) {
        echo json_encode(['success' => false, 'message' => 'Invalid date format. Use YYYY-MM-DD.']);
        exit();
    }

    if ($start_date_input === null || $arrival_date_input === null) {
        echo json_encode(['success' => false, 'message' => 'Start date and arrival date are required.']);
        exit();
    }

    if (strtotime($arrival_date_input) < strtotime($start_date_input)) {
        echo json_encode(['success' => false, 'message' => 'Arrival date must be the same as or after start date.']);
        exit();
    }

    $vacdays = (int)((strtotime($arrival_date_input) - strtotime($start_date_input)) / 86400) + 1;

    $update_sql = "UPDATE emp_vacation SET start_date = ?, return_date = ?, arrival_date = ?, vacdays = ? WHERE request_inv_no = ? AND review = 'A'";
    $stmt_update = $conDB->prepare($update_sql);

    if (!$stmt_update) {
        echo json_encode(['success' => false, 'message' => 'Could not prepare update query.']);
        exit();
    }

    $stmt_update->bind_param('sssis', $start_date_input, $arrival_date_input, $arrival_date_input, $vacdays, $request_inv_no);
    $ok = $stmt_update->execute();

    if (!$ok) {
        $stmt_update->close();
        echo json_encode(['success' => false, 'message' => 'Failed to update vacation dates.']);
        exit();
    }

    $affected = $stmt_update->affected_rows;
    $stmt_update->close();

    echo json_encode([
        'success' => true,
        'message' => ($affected > 0)
            ? 'Vacation dates updated successfully.'
            : 'No rows updated. Request may be unchanged or not eligible.',
    ]);
    exit();
}

if ($search_emp_id !== '') {
    $select_sql = "SELECT v.id, v.request_inv_no, v.emp_id, v.start_date, v.arrival_date, v.return_date,
                          v.vacdays, v.current_status, v.vac_type, v.fly_type, v.created_at,
                          e.name AS employee_name
                   FROM emp_vacation v
                   LEFT JOIN employees e ON e.emp_id = v.emp_id
                   WHERE v.emp_id = ? AND v.review = 'A'
                   ORDER BY v.created_at DESC, v.id DESC";

    $stmt_select = $conDB->prepare($select_sql);
    if ($stmt_select) {
        $stmt_select->bind_param('s', $search_emp_id);
        $stmt_select->execute();
        $result = $stmt_select->get_result();

        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $requests[] = $row;
            }
        }

        $stmt_select->close();

        if (!empty($requests)) {
            $employee_name = (string)($requests[0]['employee_name'] ?? '');
        } elseif ($message === '') {
            $message = 'No active applied requests (review = A) found for this employee.';
            $message_type = 'warning';
        }
    } else {
        $message = 'Could not prepare search query: ' . $conDB->error;
        $message_type = 'danger';
    }
}
?>
<!doctype html>
<html lang="<?= $current_lang ?? 'en' ?>" <?= ($is_rtl ?? false) ? 'dir="rtl"' : '' ?>>
<head>
    <meta charset="utf-8" />
    <title><?= $site_title ?? 'Vacation System' ?> - Edit Vacation Dates</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta content="Anees Afzal" name="author" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <link rel="shortcut icon" href="<?= get_setting($conDB, 'favicon') ?>">

    <link href="assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/icons.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/metismenu.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/style.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/style_dark.css" rel="stylesheet" type="text/css" />
    <link href="./plugins/bootstrap-datepicker/css/bootstrap-datepicker.min.css" rel="stylesheet" type="text/css" />
    <?php if ($is_rtl): ?>
        <link href="assets/css/style_rtl.css" rel="stylesheet" type="text/css" />
    <?php endif; ?>
    <script src="assets/js/modernizr.min.js"></script>

    <link href="assets/css/smart_request.css?v=<?= @filemtime(__DIR__ . '/assets/css/smart_request.css') ?>" rel="stylesheet" type="text/css" />
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
            <?php include('./includes/main_menu.php'); ?>
            <div class="clearfix"></div>
        </div>
    </div>

    <div class="content-page">
        <?php include('./includes/topbar.php'); ?>

        <div class="content sr-page">
            <div class="container-fluid">
                <div class="sr-head">
                    <div>
                        <h1>Edit Vacation Dates</h1>
                        <p>Load an employee's active applied vacation requests and correct their start / arrival dates.</p>
                    </div>
                </div>

                <?php if ($message !== ''): ?>
                    <?php $vdi_tone = ['danger' => 'tone-red', 'warning' => 'tone-amber', 'success' => 'tone-green'][$message_type] ?? 'tone-sky'; ?>
                    <div class="sr-notice <?= $vdi_tone ?>" role="alert">
                        <i class="mdi mdi-information-outline"></i>
                        <div><?= htmlspecialchars($message) ?></div>
                    </div>
                <?php endif; ?>

                <div class="sr-card">
                    <form method="get" action="vacation_dates_by_inv.php" class="sr-toolbar">
                        <div class="sr-search">
                            <i class="mdi mdi-account-search"></i>
                            <input type="search" id="emp_id" name="emp_id" value="<?= htmlspecialchars($search_emp_id) ?>" placeholder="<?= __('emp_id') ?> - Example: 10045" required autocomplete="off">
                        </div>
                        <div class="sr-toolbar-right">
                            <button type="submit" class="sr-btn sr-btn-primary"><i class="mdi mdi-magnify"></i> Load Requests</button>
                        </div>
                    </form>

                    <?php if (!empty($requests)):
                        $vdi_initials = strtoupper(implode('', array_map(function ($w) { return mb_substr($w, 0, 1); }, array_slice(preg_split('/\s+/', trim($employee_name)), 0, 2))));
                    ?>
                        <div class="sr-card-head">
                            <div class="sr-person">
                                <span class="sr-avatar"><?= htmlspecialchars($vdi_initials) ?></span>
                                <div>
                                    <div class="sr-cell-title"><?= htmlspecialchars($employee_name !== '' ? $employee_name : '-') ?></div>
                                    <div class="sr-cell-sub"><?= __('emp_id') ?>: <?= htmlspecialchars($search_emp_id) ?></div>
                                </div>
                            </div>
                            <span class="sr-chip"><i class="mdi mdi-calendar-check"></i> Active applied requests: <?= count($requests) ?></span>
                        </div>

                        <div class="sr-table-wrap sr-table-scroll">
                            <table class="sr-table">
                                <thead>
                                    <tr>
                                        <th>Request ID</th>
                                        <th>Type</th>
                                        <th>Status</th>
                                        <th>Start Date</th>
                                        <th>Arrival Date</th>
                                        <th>Return Date</th>
                                        <th class="text-center">Days</th>
                                        <th class="text-right">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($requests as $req): ?>
                                    <tr>
                                        <td><span class="sr-chip sr-mono"><?= htmlspecialchars((string)$req['request_inv_no']) ?></span></td>
                                        <td>
                                            <span class="sr-cell-title"><?= htmlspecialchars((string)($req['vac_type'] ?? '-')) ?></span>
                                            <?php if (!empty($req['fly_type'])): ?>
                                                <span class="sr-cell-sub"><?= htmlspecialchars((string)$req['fly_type']) ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td><span class="sr-pill sr-pill-xs tone-slate"><?= htmlspecialchars(ucwords(str_replace('_', ' ', (string)($req['current_status'] ?? '-')))) ?></span></td>
                                        <td class="sr-date"><?= htmlspecialchars((string)($req['start_date'] ?? '-')) ?></td>
                                        <td class="sr-date"><?= htmlspecialchars((string)($req['arrival_date'] ?? '-')) ?></td>
                                        <td class="sr-date"><?= htmlspecialchars((string)($req['return_date'] ?? '-')) ?></td>
                                        <td class="text-center"><span class="sr-chip"><?= htmlspecialchars((string)($req['vacdays'] ?? '-')) ?></span></td>
                                        <td class="text-right">
                                            <button
                                                type="button"
                                                class="sr-open-btn border-0 btn-edit-dates"
                                                data-request-inv="<?= htmlspecialchars((string)$req['request_inv_no'], ENT_QUOTES) ?>"
                                                data-start-date="<?= htmlspecialchars((string)($req['start_date'] ?? ''), ENT_QUOTES) ?>"
                                                data-arrival-date="<?= htmlspecialchars((string)($req['arrival_date'] ?? ''), ENT_QUOTES) ?>"
                                                data-return-date="<?= htmlspecialchars((string)($req['return_date'] ?? ''), ENT_QUOTES) ?>"
                                            ><i class="mdi mdi-pencil"></i> Modify Dates</button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php elseif ($search_emp_id === ''): ?>
                        <div class="sr-empty"><i class="mdi mdi-account-search"></i>Enter an employee ID to load the requests.</div>
                    <?php else: ?>
                        <div class="sr-empty"><i class="mdi mdi-inbox"></i>No active applied requests found.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <footer class="footer"><?= $site_footer ?? '© 2025 Almutlak' ?></footer>
    </div>
</div>

<script src="assets/js/jquery.min.js"></script>
<script src="assets/js/bootstrap.bundle.min.js"></script>
<script src="./plugins/bootstrap-datepicker/js/bootstrap-datepicker.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="assets/js/metisMenu.min.js"></script>
<script src="assets/js/waves.js"></script>
<script src="assets/js/jquery.slimscroll.js"></script>
<script src="assets/js/jquery.core.js"></script>
<script src="assets/js/jquery.app.js?t=<?= time() ?>"></script>
<script>
    $(function () {
        var currentEmpId = <?= json_encode($search_emp_id) ?>;

        function normalizeDateString(value) {
            var v = (value || '').toString().trim();
            if (!v || v === '0000-00-00') {
                return '';
            }
            return v;
        }

        $(document).on('click', '.btn-edit-dates', function () {
            var requestInvNo = $(this).data('request-inv') || '';
            var startDate = normalizeDateString($(this).data('start-date'));
            var arrivalDate = normalizeDateString($(this).data('arrival-date'));
            var returnDate = normalizeDateString($(this).data('return-date'));
            var effectiveArrivalDate = arrivalDate || returnDate || startDate;

            Swal.fire({
                title: 'Modify Vacation Dates',
                html: '' +
                    '<form class="sr-page sr-form" onsubmit="return false;">' +
                    '  <div class="sr-fsec mb-0">' +
                    '    <div class="sr-fsec-head"><span><i class="mdi mdi-calendar-range"></i> <?= __('request_id') ?>: ' + $('<div>').text(requestInvNo).html() + '</span></div>' +
                    '    <div class="sr-fgrid">' +
                    '      <div class="sr-fcol c-6"><label for="swal_start_date"><?= __('start_date') ?></label>' +
                    '        <input type="text" id="swal_start_date" class="form-control" placeholder="YYYY-MM-DD" readonly style="cursor:pointer;"></div>' +
                    '      <div class="sr-fcol c-6"><label for="swal_arrival_date"><?= __('arrival_date') ?></label>' +
                    '        <input type="text" id="swal_arrival_date" class="form-control" placeholder="YYYY-MM-DD" readonly style="cursor:pointer;"></div>' +
                    '      <div class="sr-fcol c-12"><div class="sr-ftotal"><span><?= __('difference_days') ?></span><b id="swal_days_diff">-</b></div></div>' +
                    '    </div>' +
                    '  </div>' +
                    '</form>',
                showCancelButton: true,
                confirmButtonText: '<i class="mdi mdi-content-save"></i> Update Dates',
                confirmButtonColor: APP_COLORS.primary,
                cancelButtonColor: APP_COLORS.danger_dark,
                customClass: { popup: 'sr-addline-popup' },
                cancelButtonText: 'Cancel',
                allowOutsideClick: false,
                didOpen: function () {
                    var $swStart = $('#swal_start_date');
                    var $swArrival = $('#swal_arrival_date');
                    var $swDaysDiff = $('#swal_days_diff');

                    function parseDateSafe(dateText) {
                        if (!dateText || dateText.indexOf('-') === -1) {
                            return null;
                        }
                        var parts = dateText.split('-');
                        if (parts.length !== 3) {
                            return null;
                        }
                        var year = parseInt(parts[0], 10);
                        var month = parseInt(parts[1], 10) - 1;
                        var day = parseInt(parts[2], 10);
                        if (isNaN(year) || isNaN(month) || isNaN(day)) {
                            return null;
                        }
                        return new Date(year, month, day);
                    }

                    function updateDaysDifference() {
                        var s = $swStart.val();
                        var a = $swArrival.val();

                        if (!s || !a) {
                            $swDaysDiff.text('-').css('color', '');
                            return;
                        }

                        var sd = parseDateSafe(s);
                        var ad = parseDateSafe(a);
                        if (!sd || !ad) {
                            $swDaysDiff.text('Invalid date').css('color', 'var(--tone-red-fg)');
                            return;
                        }

                        var days = Math.floor((ad.getTime() - sd.getTime()) / 86400000) + 1;
                        if (days <= 0) {
                            $swDaysDiff.text('Invalid range').css('color', 'var(--tone-red-fg)');
                            return;
                        }

                        $swDaysDiff.text(days + ' day(s)').css('color', '');
                    }

                    $swStart.datepicker({
                        format: 'yyyy-mm-dd',
                        autoclose: true,
                        todayHighlight: true
                    }).on('changeDate', function (e) {
                        if (e.date) {
                            $swArrival.datepicker('setStartDate', e.date);
                        }
                        updateDaysDifference();
                    });

                    $swArrival.datepicker({ 
                        format: 'yyyy-mm-dd',
                        autoclose: true,
                        todayHighlight: true
                    }).on('changeDate', function () {
                        updateDaysDifference();
                    });

                    if (startDate) {
                        $swStart.datepicker('setDate', startDate);
                        $swArrival.datepicker('setStartDate', startDate);
                    }

                    if (effectiveArrivalDate) {
                        $swArrival.datepicker('setDate', effectiveArrivalDate);
                    }

                    updateDaysDifference();
                },
                preConfirm: function () {
                    var newStartDate = $('#swal_start_date').val();
                    var newArrivalDate = $('#swal_arrival_date').val();

                    if (!newStartDate || !newArrivalDate) {
                        Swal.showValidationMessage('Both dates are required.');
                        return false;
                    }

                    if (newArrivalDate < newStartDate) {
                        Swal.showValidationMessage('Arrival date must be the same as or after start date.');
                        return false;
                    }

                    return {
                        request_inv_no: requestInvNo,
                        start_date: newStartDate,
                        arrival_date: newArrivalDate
                    };
                }
            }).then(function (result) {
                if (!result.isConfirmed) {
                    return;
                }

                Swal.fire({
                    title: 'Updating...',
                    allowOutsideClick: false,
                    didOpen: function () {
                        Swal.showLoading();
                    }
                });

                $.ajax({
                    url: 'vacation_dates_by_inv.php',
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'update_dates_ajax',
                        request_inv_no: result.value.request_inv_no,
                        start_date: result.value.start_date,
                        arrival_date: result.value.arrival_date
                    }
                }).done(function (res) {
                    if (res && res.success) {
                        Swal.fire('Success', res.message || 'Updated successfully.', 'success').then(function () {
                            var url = 'vacation_dates_by_inv.php';
                            if (currentEmpId) {
                                url += '?emp_id=' + encodeURIComponent(currentEmpId);
                            }
                            window.location.href = url;
                        });
                    } else {
                        Swal.fire('Error', (res && res.message) ? res.message : 'Failed to update.', 'error');
                    }
                }).fail(function () {
                    Swal.fire('Error', 'Request failed while updating dates.', 'error');
                });
            });
        });
    });
</script>
</body>
</html>
