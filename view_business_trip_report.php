<?php
/****************************************************************
 * Business Trip Report - Quick View & Approval Page
 * 
 * Similar structure to vacation_report_details.php
 * Displays trip details with approval/rejection actions
 ****************************************************************/

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/session_check.php';

$query = mysqli_query($conDB, "SELECT * FROM `admin_login` WHERE `id_iqama`='" . $username . "'");
if (mysqli_num_rows($query) == 1) {
    include("./includes/avatar_select.php");

    $request_inv_no = trim((string)($_GET['id'] ?? ''));
    if ($request_inv_no === '') {
        die('Invalid request id.');
    }

    // --- Get Request Type ID for 'business_trip' ---
    $type_query = mysqli_query($conDB, "SELECT id FROM approval_request_types WHERE type_name = 'business_trip' LIMIT 1");
    if (!$type_query || mysqli_num_rows($type_query) == 0) {
        die("CRITICAL ERROR: 'business_trip' type not found in `approval_request_types` table.");
    }
    $request_type_id = (int)mysqli_fetch_assoc($type_query)['id'];
    mysqli_free_result($type_query);

    // Fetch full business trip data
    $stmt = $pdo->prepare("SELECT 
                bt.*, 
                e.name as employee_name, 
                e.avatar,
                e.emp_id,
                d.dep_nme AS dept_name,
                d.dep_nme_ar AS dept_name_ar,
                comp.comp_name,
                comp.comp_name_ar,
                fc.name_en as from_city_name_en,
                fc.name_ar as from_city_name_ar,
                tc.name_en as to_city_name_en,
                tc.name_ar as to_city_name_ar
            FROM emp_business_trip bt
            LEFT JOIN employees e ON bt.emp_id = e.emp_id
            LEFT JOIN department d ON e.dept = d.id
            LEFT JOIN companies comp ON e.comp_no = comp.comp_id
            LEFT JOIN saudi_cities fc ON bt.from_city_id = fc.id 
            LEFT JOIN saudi_cities tc ON bt.to_city_id = tc.id 
            WHERE bt.request_inv_no = ? LIMIT 1");
    if (!$stmt) {
        die('Failed to prepare query.');
    }
    $stmt->execute([$request_inv_no]);
    $request = $stmt->fetch();

    if (!$request) {
        die('Business trip request not found.');
    }

    // Fetch approval chain
    $chain = [];
    $chain_stmt = $pdo->prepare("SELECT 
                            ra.approval_level, 
                            ra.approver_id, 
                            ra.status, 
                            ra.note, 
                            ra.action_date, 
                            e.name as approver_name,
                            e.emp_id as approver_emp_id,
                            al.user_type AS approver_role,
                            d2.dep_nme AS approver_dept_name
                        FROM request_approvers ra 
                        LEFT JOIN employees e ON e.emp_id = ra.approver_id 
                        LEFT JOIN admin_login al ON e.emp_id = al.emp_id
                        LEFT JOIN department d2 ON e.dept = d2.id
                        WHERE ra.request_inv_no = ? AND ra.request_type_id = ? 
                        ORDER BY ra.approval_level ASC, ra.id ASC");
    if ($chain_stmt) {
        $chain_stmt->execute([$request_inv_no, $request_type_id]);
        while ($row = $chain_stmt->fetch()) {
            $chain[] = $row;
        }
    }

    $current_pending_approver_id = 0;
    $current_pending_level = 0;
    $pending_stmt = $pdo->prepare("SELECT approver_id, approval_level FROM request_approvers WHERE request_inv_no = ? AND request_type_id = ? AND status = 'pending' ORDER BY id DESC LIMIT 1");
    if ($pending_stmt) {
        $pending_stmt->execute([$request_inv_no, $request_type_id]);
        if ($pending_row = $pending_stmt->fetch()) {
            $current_pending_approver_id = (int)($pending_row['approver_id'] ?? 0);
            $current_pending_level = (int)($pending_row['approval_level'] ?? 0);
        }
    }

    $can_take_action = ($current_pending_approver_id > 0 && $current_pending_approver_id === (int)$empid && ($request['current_status'] ?? '') === 'pending_approval');
    
    $route_text = '';
    if (($request['trip_type'] ?? '') === 'international') {
        $route_text = (string)($request['destination_country'] ?? 'N/A');
    } else {
        $from_name = ($is_rtl ?? false) ? ($request['from_city_name_ar'] ?? '') : ($request['from_city_name_en'] ?? '');
        $to_name = ($is_rtl ?? false) ? ($request['to_city_name_ar'] ?? '') : ($request['to_city_name_en'] ?? '');
        $route_text = trim((string)$from_name) . ' → ' . trim((string)$to_name);
    }

    // Get current request status for badge
    $current_status = $request['current_status'] ?? 'pending_approval';
    $status_badge_class = 'secondary';
    if ($current_status === 'pending_approval') $status_badge_class = 'warning';
    if ($current_status === 'approved') $status_badge_class = 'success';
    if ($current_status === 'rejected') $status_badge_class = 'danger';
    if ($current_status === 'completed') $status_badge_class = 'info';
?>
<!doctype html>
<html lang="<?= $current_lang ?? 'en' ?>" <?= ($is_rtl ?? false) ? 'dir="rtl"' : '' ?>>
<head>
    <meta charset="utf-8" />
    <title><?= $site_title ?? 'System' ?> - <?= __('business_trip_details') ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <link rel="shortcut icon" href="<?= get_setting($conDB, 'favicon') ?>">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/icons.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/metismenu.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/style.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/style_dark.css" rel="stylesheet" type="text/css" />
    <script src="assets/js/modernizr.min.js"></script>
    <?php if ($is_rtl): ?>
        <link href="assets/css/style_rtl.css" rel="stylesheet" type="text/css" />
    <?php endif; ?>
    <style>
        .request-details-card { border-radius: 14px; box-shadow: 0 8px 22px rgba(0,0,0,.08); border: none; }
        .request-details-card .card-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #fff; border-radius: 14px 14px 0 0; }
        .detail-row { margin-bottom: .8rem; font-size: 1.02rem; }
        .detail-row strong { min-width: 170px; display: inline-block; color: #6c757d; }
        .chain-badge { min-width: 92px; }
        
        .approval-timeline { position: relative; padding-left: 5px; }
        .timeline-item { position: relative; padding-bottom: 1rem; padding-left: 30px; min-height: 20px; }
        .timeline-item:last-child { padding-bottom: 0; }
        .timeline-item::before { content: ''; position: absolute; left: 0; top: 10px; bottom: 0; width: 2px; background: #e9ecef; }
        .timeline-item:last-child::before { display: none; }
        .timeline-item .icon { position: absolute; left: -9px; top: 0; width: 20px; height: 20px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 10px; border: 2px solid #fff; }
        .timeline-item.approved .icon { background-color: #28a745; }
        .timeline-item.pending .icon { background-color: #ffc107; }
        .timeline-item.rejected .icon { background-color: #dc3545; }
        .timeline-item.future .icon { background-color: #ced4da; }
        .timeline-item .status { font-weight: 600; line-height: 20px; font-size: 0.9rem; }
        
        [dir="rtl"] .approval-timeline { padding-left: 0; padding-right: 5px; }
        [dir="rtl"] .timeline-item { padding-left: 0; padding-right: 30px; }
        [dir="rtl"] .timeline-item::before { left: auto; right: 0; }
        [dir="rtl"] .timeline-item .icon { left: auto; right: -9px; }
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
            <?php include('./includes/main_menu.php'); ?>
            <div class="clearfix"></div>
        </div>
    </div>

    <div class="content-page">
        <?php include('./includes/topbar.php'); ?>
        <div class="content">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-xl-9 col-lg-10 mx-auto">
                        <div class="card request-details-card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <div>
                                    <h4 class="mb-0"><?= __('business_trip_request') ?></h4>
                                    <small class="text-white-50"><?= htmlspecialchars((string)$request['request_inv_no']) ?></small>
                                </div>
                                <span class="badge badge-<?= $status_badge_class ?> p-2" style="font-size: 0.9rem;"><?= htmlspecialchars((string)$current_status) ?></span>
                            </div>
                            <div class="card-body">
                                <!-- Employee Info -->
                                <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1.5rem; padding: 1rem; background-color: #f8f9fa; border-radius: 8px;">
                                    <img src="<?= !empty($request['avatar']) ? 'assets/emp_pics/' . htmlspecialchars($request['avatar']) : 'assets/images/avatar.png' ?>" alt="Employee" style="width: 60px; height: 60px; border-radius: 50%; object-fit: cover;">
                                    <div>
                                        <h5 class="mb-0"><?= getDisplayName(parseName((string)$request['employee_name'])) ?></h5>
                                        <small class="text-muted"><?= htmlspecialchars((string)$request['emp_id']) ?> • <?= htmlspecialchars((string)($request['dept_name'] ?? '')) ?></small>
                                    </div>
                                </div>

                                <!-- Trip Details -->
                                <div class="detail-row"><strong><?= __('trip_type') ?>:</strong> <?= htmlspecialchars(ucfirst((string)$request['trip_type'])) ?></div>
                                <div class="detail-row"><strong><?= __('destination') ?>:</strong> <?= htmlspecialchars((string)$route_text) ?></div>
                                <div class="detail-row"><strong><?= __('trip_dates') ?>:</strong> <?= htmlspecialchars((string)$request['trip_start_date']) ?> → <?= htmlspecialchars((string)$request['trip_end_date']) ?></div>
                                <div class="detail-row"><strong><?= __('purpose') ?>:</strong> <?= htmlspecialchars((string)($request['trip_purpose'] ?? '')) ?></div>
                                <div class="detail-row"><strong><?= __('transportation') ?>:</strong> <?= htmlspecialchars(ucfirst((string)($request['transportation_type'] ?? 'N/A'))) ?></div>
                                <div class="detail-row"><strong><?= __('notes') ?>:</strong> <?= nl2br(htmlspecialchars((string)($request['request_notes'] ?? ''))) ?></div>
                                <div class="detail-row"><strong><?= __('submitted_at') ?>:</strong> <?= htmlspecialchars((string)$request['created_at']) ?></div>
                                <?php if ($current_pending_level > 0): ?>
                                    <div class="detail-row"><strong><?= __('current_level') ?>:</strong> <?= (int)$current_pending_level ?></div>
                                <?php endif; ?>

                                <hr>
                                <h5 class="mb-3"><?= __('request_chain') ?></h5>
                                
                                <?php if (!empty($chain)): ?>
                                    <div class="approval-timeline">
                                        <?php foreach ($chain as $step): ?>
                                            <?php
                                            $step_status = $step['status'] ?? 'pending';
                                            $step_class = 'future';
                                            if ($step_status === 'approved') $step_class = 'approved';
                                            elseif ($step_status === 'pending') $step_class = 'pending';
                                            elseif ($step_status === 'rejected') $step_class = 'rejected';
                                            ?>
                                            <div class="timeline-item <?= $step_class ?>">
                                                <div class="icon">
                                                    <?php if ($step_status === 'approved'): ?>
                                                        <i class="fa fa-check" style="font-size: 8px;"></i>
                                                    <?php elseif ($step_status === 'rejected'): ?>
                                                        <i class="fa fa-times" style="font-size: 8px;"></i>
                                                    <?php else: ?>
                                                        <i class="fa fa-clock" style="font-size: 8px;"></i>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="status">
                                                    <strong><?= htmlspecialchars((string)($step['approver_name'] ?? ('Emp#' . (int)($step['approver_id'] ?? 0)))) ?></strong>
                                                    <span class="badge badge-<?php
                                                        if ($step_status === 'approved') echo 'success';
                                                        elseif ($step_status === 'rejected') echo 'danger';
                                                        elseif ($step_status === 'pending') echo 'warning';
                                                        else echo 'secondary';
                                                    ?>" style="margin-left: 0.5rem;"><?= htmlspecialchars((string)($step['status'] ?? '')) ?></span>
                                                </div>
                                                <small class="text-muted" style="display: block; margin-top: 0.2rem;">
                                                    Level <?= (int)($step['approval_level'] ?? 0) ?>
                                                    <?php if (!empty($step['action_date'])): ?>
                                                        • <?= htmlspecialchars((string)$step['action_date']) ?>
                                                    <?php endif; ?>
                                                </small>
                                                <?php if (!empty($step['note'])): ?>
                                                    <small class="text-muted" style="display: block; margin-top: 0.5rem; font-style: italic;">
                                                        <?= nl2br(htmlspecialchars((string)$step['note'])) ?>
                                                    </small>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-info">No approval chain found for this request.</div>
                                <?php endif; ?>

                                <?php if ($can_take_action): ?>
                                    <hr>
                                    <div class="d-flex justify-content-end" style="gap: .5rem;">
                                        <button class="btn btn-danger" onclick="rejectBusinessTrip('<?= htmlspecialchars((string)$request['request_inv_no'], ENT_QUOTES); ?>')"><i class="fa fa-times"></i> <?= __('reject') ?></button>
                                        <button class="btn btn-success" onclick="approveBusinessTrip('<?= htmlspecialchars((string)$request['request_inv_no'], ENT_QUOTES); ?>')"><i class="fa fa-check"></i> <?= __('approve') ?></button>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <footer class="footer"><?= $site_footer ?? '' ?></footer>
    </div>
</div>

<script src="assets/js/jquery.min.js"></script>
<script src="assets/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/metisMenu.min.js"></script>
<script src="assets/js/waves.js"></script>
<script src="assets/js/jquery.slimscroll.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="assets/js/jquery.core.js"></script>
<script src="assets/js/jquery.app.js?t=<?= time() ?>"></script>
<script>
function approveBusinessTrip(tripId) {
    Swal.fire({
        title: __('confirm_approval') || 'Confirm Approval',
        input: 'textarea',
        inputLabel: __('approval_comment_optional') || 'Approval Comment (Optional)',
        inputPlaceholder: __('enter_comment_here') || 'Enter comment...',
        showCancelButton: true,
        confirmButtonColor: APP_COLORS.success,
        cancelButtonColor: APP_COLORS.danger,
        confirmButtonText: __('approve') || 'Approve',
        showLoaderOnConfirm: true,
        preConfirm: (approvalComment) => {
            return new Promise((resolve, reject) => {
                $.ajax({
                    url: './includes/ajaxFile/ajaxBusinessTrip.php',
                    type: 'POST',
                    dataType: 'JSON',
                    data: {
                        ajaxType: 'approveBusinessTrip',
                        trip_id: tripId,
                        approval_comment: approvalComment || ''
                    },
                    success: function (response) {
                        if (response.status === 'success') {
                            resolve(response);
                        } else {
                            reject(response.message || 'Approval failed');
                        }
                    },
                    error: function () {
                        reject('Approval request failed.');
                    }
                });
            }).catch(error => Swal.showValidationMessage(error));
        }
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire(result.value.title || 'Approved', result.value.message || '', 'success').then(() => location.reload());
        }
    });
}

function rejectBusinessTrip(tripId) {
    Swal.fire({
        title: __('confirm_rejection') || 'Confirm Rejection',
        input: 'textarea',
        inputLabel: __('provide_rejection_reason') || 'Provide rejection reason',
        inputPlaceholder: __('enter_reason_here') || 'Enter rejection reason...',
        showCancelButton: true,
        confirmButtonColor: APP_COLORS.danger,
        cancelButtonColor: APP_COLORS.secondary,
        confirmButtonText: __('reject') || 'Reject',
        showLoaderOnConfirm: true,
        preConfirm: (rejectionReason) => {
            if (!rejectionReason || rejectionReason.trim() === '') {
                Swal.showValidationMessage(__('provide_rejection_reason') || 'Rejection reason is required');
                return false;
            }
            return new Promise((resolve, reject) => {
                $.ajax({
                    url: './includes/ajaxFile/ajaxBusinessTrip.php',
                    type: 'POST',
                    dataType: 'JSON',
                    data: {
                        ajaxType: 'rejectBusinessTrip',
                        trip_id: tripId,
                        rejection_reason: rejectionReason.trim()
                    },
                    success: function (response) {
                        if (response.status === 'success') {
                            resolve(response);
                        } else {
                            reject(response.message || 'Rejection failed');
                        }
                    },
                    error: function () {
                        reject('Rejection request failed.');
                    }
                });
            }).catch(error => Swal.showValidationMessage(error));
        }
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire(result.value.title || 'Rejected', result.value.message || '', 'success').then(() => location.reload());
        }
    });
}
</script>
</body>
</html>
<?php
    $conDB->close();
}
?>
?>
<!doctype html>
<html lang="<?= $current_lang ?? 'en' ?>" <?= ($is_rtl ?? false) ? 'dir="rtl"' : '' ?>>
<head>
    <meta charset="utf-8" />
    <title><?= $site_title ?? 'System' ?> - <?= __('business_trip_details') ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <link rel="shortcut icon" href="<?= get_setting($conDB, 'favicon') ?>">
    <link href="assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/icons.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/metismenu.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/style.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/style_dark.css" rel="stylesheet" type="text/css" />
    <script src="assets/js/modernizr.min.js"></script>
    <style>
        .request-details-card { border-radius: 14px; box-shadow: 0 8px 22px rgba(0,0,0,.08); border: none; }
        .request-details-card .card-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #fff; border-radius: 14px 14px 0 0; }
        .detail-row { margin-bottom: .8rem; font-size: 1.02rem; }
        .detail-row strong { min-width: 170px; display: inline-block; color: #6c757d; }
        .chain-badge { min-width: 92px; }
    </style>
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
        <div class="content">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-xl-9 col-lg-10 mx-auto">
                        <div class="card request-details-card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h4 class="mb-0"><?= __('business_trip_request') ?> - <?= htmlspecialchars((string)$request['request_inv_no']) ?></h4>
                                <span class="badge badge-light p-2"><?= htmlspecialchars((string)$request['current_status']) ?></span>
                            </div>
                            <div class="card-body">
                                <div class="detail-row"><strong><?= __('employee') ?>:</strong> <?= getDisplayName(parseName((string)$request['employee_name'])) ?> (<?= htmlspecialchars((string)$request['emp_id']) ?>)</div>
                                <div class="detail-row"><strong><?= __('trip_type') ?>:</strong> <?= htmlspecialchars((string)$request['trip_type']) ?></div>
                                <div class="detail-row"><strong><?= __('destination') ?>:</strong> <?= htmlspecialchars((string)$route_text) ?></div>
                                <div class="detail-row"><strong><?= __('trip_dates') ?>:</strong> <?= htmlspecialchars((string)$request['trip_start_date']) ?> → <?= htmlspecialchars((string)$request['trip_end_date']) ?></div>
                                <div class="detail-row"><strong><?= __('purpose') ?>:</strong> <?= htmlspecialchars((string)($request['trip_purpose'] ?? '')) ?></div>
                                <div class="detail-row"><strong><?= __('notes') ?>:</strong> <?= nl2br(htmlspecialchars((string)($request['request_notes'] ?? ''))) ?></div>
                                <div class="detail-row"><strong><?= __('submitted_at') ?>:</strong> <?= htmlspecialchars((string)$request['created_at']) ?></div>
                                <?php if ($current_pending_level > 0): ?>
                                    <div class="detail-row"><strong><?= __('current_level') ?>:</strong> <?= (int)$current_pending_level ?></div>
                                <?php endif; ?>

                                <hr>
                                <h5 class="mb-3"><?= __('request_chain') ?></h5>
                                <div class="table-responsive">
                                    <table class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th><?= __('level') ?></th>
                                                <th><?= __('approver') ?></th>
                                                <th><?= __('status') ?></th>
                                                <th><?= __('note') ?></th>
                                                <th><?= __('action_date') ?></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        <?php if (!empty($chain)): ?>
                                            <?php foreach ($chain as $step): ?>
                                                <?php
                                                $badge = 'secondary';
                                                if (($step['status'] ?? '') === 'pending') $badge = 'warning';
                                                if (($step['status'] ?? '') === 'approved') $badge = 'success';
                                                if (($step['status'] ?? '') === 'rejected') $badge = 'danger';
                                                if (($step['status'] ?? '') === 'awaiting') $badge = 'info';
                                                ?>
                                                <tr>
                                                    <td><?= (int)($step['approval_level'] ?? 0) ?></td>
                                                    <td><?= htmlspecialchars(getDisplayName(parseName((string)($step['approver_name'] ?? ('Emp#' . (int)($step['approver_id'] ?? 0))))) ) ?></td>
                                                    <td><span class="badge badge-<?= $badge ?> chain-badge"><?= htmlspecialchars((string)($step['status'] ?? '')) ?></span></td>
                                                    <td><?= nl2br(htmlspecialchars((string)($step['note'] ?? ''))) ?></td>
                                                    <td><?= htmlspecialchars((string)($step['action_date'] ?? '')) ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr><td colspan="5" class="text-center text-muted"><?= __('no_data_found') ?></td></tr>
                                        <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>

                                <?php if ($can_take_action): ?>
                                    <hr>
                                    <div class="d-flex justify-content-end" style="gap: .5rem;">
                                        <button class="btn btn-danger" onclick="rejectBusinessTrip(<?= (int)$request['id'] ?>)"><i class="fa fa-times"></i> <?= __('reject') ?></button>
                                        <button class="btn btn-success" onclick="approveBusinessTrip(<?= (int)$request['id'] ?>)"><i class="fa fa-check"></i> <?= __('approve') ?></button>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <footer class="footer"><?= $site_footer ?? '' ?></footer>
    </div>
</div>

<script src="assets/js/jquery.min.js"></script>
<script src="assets/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/metisMenu.min.js"></script>
<script src="assets/js/waves.js"></script>
<script src="assets/js/jquery.slimscroll.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="assets/js/jquery.core.js"></script>
<script src="assets/js/jquery.app.js?t=<?= time() ?>"></script>
<script>
function approveBusinessTrip(tripId) {
    Swal.fire({
        title: __('confirm_approval') || 'Confirm Approval',
        input: 'textarea',
        inputLabel: __('approval_comment_optional') || 'Approval Comment (Optional)',
        inputPlaceholder: __('enter_comment_here') || 'Enter comment...',
        showCancelButton: true,
        confirmButtonColor: APP_COLORS.success,
        cancelButtonColor: APP_COLORS.danger,
        confirmButtonText: __('approve') || 'Approve',
        showLoaderOnConfirm: true,
        preConfirm: (approvalComment) => {
            return new Promise((resolve, reject) => {
                $.ajax({
                    url: './includes/ajaxFile/ajaxBusinessTrip.php',
                    type: 'POST',
                    dataType: 'JSON',
                    data: {
                        ajaxType: 'approveBusinessTrip',
                        trip_id: tripId,
                        approval_comment: approvalComment || ''
                    },
                    success: function (response) {
                        if (response.status === 'success') {
                            resolve(response);
                        } else {
                            reject(response.message || 'Approval failed');
                        }
                    },
                    error: function () {
                        reject('Approval request failed.');
                    }
                });
            }).catch(error => Swal.showValidationMessage(error));
        }
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire(result.value.title || 'Approved', result.value.message || '', 'success').then(() => location.reload());
        }
    });
}

function rejectBusinessTrip(tripId) {
    Swal.fire({
        title: __('confirm_rejection') || 'Confirm Rejection',
        input: 'textarea',
        inputLabel: __('provide_rejection_reason') || 'Provide rejection reason',
        inputPlaceholder: __('enter_reason_here') || 'Enter rejection reason...',
        showCancelButton: true,
        confirmButtonColor: APP_COLORS.danger,
        cancelButtonColor: APP_COLORS.secondary,
        confirmButtonText: __('reject') || 'Reject',
        showLoaderOnConfirm: true,
        preConfirm: (rejectionReason) => {
            if (!rejectionReason || rejectionReason.trim() === '') {
                Swal.showValidationMessage(__('provide_rejection_reason') || 'Rejection reason is required');
                return false;
            }
            return new Promise((resolve, reject) => {
                $.ajax({
                    url: './includes/ajaxFile/ajaxBusinessTrip.php',
                    type: 'POST',
                    dataType: 'JSON',
                    data: {
                        ajaxType: 'rejectBusinessTrip',
                        trip_id: tripId,
                        rejection_reason: rejectionReason.trim()
                    },
                    success: function (response) {
                        if (response.status === 'success') {
                            resolve(response);
                        } else {
                            reject(response.message || 'Rejection failed');
                        }
                    },
                    error: function () {
                        reject('Rejection request failed.');
                    }
                });
            }).catch(error => Swal.showValidationMessage(error));
        }
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire(result.value.title || 'Rejected', result.value.message || '', 'success').then(() => location.reload());
        }
    });
}
</script>
</body>
</html>
<?php
$conDB->close();
?>