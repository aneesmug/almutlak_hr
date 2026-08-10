<?php
require_once __DIR__ . '/includes/session_check.php';
require_once __DIR__ . '/includes/special_access_helper.php';
if (file_exists(__DIR__ . '/includes/functions.php')) {
    require_once __DIR__ . '/includes/functions.php';
}

// Load translations
$current_lang = $_SESSION['lang'] ?? 'en';
load_language($current_lang);

// Input
$request_inv_no = isset($_GET['request_inv_no']) ? trim($_GET['request_inv_no']) : '';
if ($request_inv_no === '') {
    die('<div style="padding:16px;margin:16px;border:1px solid #f5c2c7;background:#f8d7da;color:#842029;border-radius:8px;">ERROR: Salary increment request invoice number not provided.</div>');
}

// Salary increment details
$req = null;
$sql = "SELECT si.*,
               e.name AS employee_name, e.avatar, e.sex, e.dept,
               d.dep_nme AS department_name,
               d.dep_nme_ar AS department_name_ar,
               sup.name AS submitted_by_name
        FROM emp_salary_increment si
        JOIN employees e ON si.emp_id = e.emp_id
        LEFT JOIN department d ON e.dept = d.id
        LEFT JOIN employees sup ON si.submitted_by = sup.emp_id
        WHERE si.request_inv_no = ?
        LIMIT 1";
$stmt = $conDB->prepare($sql);
$stmt->bind_param('s', $request_inv_no);
$stmt->execute();
$res = $stmt->get_result();
if ($res && $res->num_rows === 1) {
    $req = $res->fetch_assoc();
}
$stmt->close();
if (!$req) {
    die('<div style="padding:16px;margin:16px;border:1px solid #f5c2c7;background:#f8d7da;color:#842029;border-radius:8px;">ERROR: Salary increment request not found.</div>');
}

// Current salary information (only non-zero components are shown)
$salary_info = null;
$salary_stmt = $conDB->prepare("SELECT basic, housing, transport, food, misc, cashier, fuel, tel, other, guard,
        (basic + housing + transport + food + misc + cashier + fuel + tel + other + guard) AS total_salary
    FROM emp_salary WHERE emp_id = ? AND status = 1 ORDER BY id DESC LIMIT 1");
if ($salary_stmt) {
    $salary_stmt->bind_param('s', $req['emp_id']);
    $salary_stmt->execute();
    $salary_res = $salary_stmt->get_result();
    if ($salary_res && ($salary_row = $salary_res->fetch_assoc())) {
        $salary_info = $salary_row;
    }
    $salary_stmt->close();
}
$salary_components = [
    ['basic_salary', 'Basic Salary', $salary_info['basic'] ?? 0],
    ['housing_allowance', 'Housing Allowance', $salary_info['housing'] ?? 0],
    ['transport_allowance', 'Transport Allowance', $salary_info['transport'] ?? 0],
    ['food_allowance', 'Food Allowance', $salary_info['food'] ?? 0],
    ['miscellaneous_allowance', 'Miscellaneous Allowance', $salary_info['misc'] ?? 0],
    ['cashier_allowance', 'Cashier Allowance', $salary_info['cashier'] ?? 0],
    ['fuel_allowance', 'Fuel Allowance', $salary_info['fuel'] ?? 0],
    ['telephone_allowance', 'Telephone Allowance', $salary_info['tel'] ?? 0],
    ['other_allowance', 'Others', $salary_info['other'] ?? 0],
    ['guard_allowance', 'Guard Allowance', $salary_info['guard'] ?? 0],
];

// Restrict access: Employees cannot view this detailed report page for someone
// else's request, unless explicitly granted via app_settings -> Special Access.
// Viewing their own salary increment history is always allowed.
$isSelfRecord = (string)($req['emp_id'] ?? '') === (string)($empid ?? '');
if (
    isset($isEmployee) && $isEmployee === true && !$isSelfRecord
    && !user_has_special_access($conDB, $empid ?? '', 'access_salary_increment_status_history', $user_role ?? '', $user_type ?? '', $is_system_admin ?? false)
) {
    header("Location: ./profile.php");
    exit();
}

// Enforce department scoping: Only HR and System Admin can view other departments,
// otherwise allow if the user is part of the approval chain, the submitting supervisor,
// or the request is about their own record.
$canSeeAll = ($is_system_admin ?? false) || ($isHR ?? false);
if (!$canSeeAll && !$isSelfRecord) {
    $userDeptId = $user_dept ?? null;
    $sameDept = ($userDeptId !== null) ? ((int)$req['dept'] === (int)$userDeptId) : false;
    $isSubmitter = ((string)($req['submitted_by'] ?? '') === (string)$empid);
    $inChain = false;
    if (!$sameDept && !$isSubmitter) {
        if ($chk = $conDB->prepare("SELECT 1 FROM request_approvers ra JOIN approval_request_types t ON t.id = ra.request_type_id AND t.type_name = 'salary_increment' WHERE ra.request_inv_no = ? AND ra.approver_id = ? LIMIT 1")) {
            $chk->bind_param('si', $request_inv_no, $empid);
            if ($chk->execute()) {
                $resChk = $chk->get_result();
                $inChain = ($resChk && $resChk->num_rows > 0);
            }
            $chk->close();
        }
    }
    if (!$sameDept && !$isSubmitter && !$inChain) {
        http_response_code(403);
        die('<div style="padding:16px;margin:16px;border:1px solid #f5c2c7;background:#f8d7da;color:#842029;border-radius:8px;">Access denied: You are not authorized to view this request.</div>');
    }
}

// Status history - Build from smt_request_status
$history = [];
$stmt = $conDB->prepare("SELECT status, note, emp_name, created_at FROM smt_request_status WHERE inv_no = ? ORDER BY created_at ASC");
$stmt->bind_param('s', $request_inv_no);
$stmt->execute();
$hr = $stmt->get_result();
while ($row = $hr->fetch_assoc()) {
    $history[] = $row;
}
$stmt->close();

// Approval chain
$chain = [];

// Resolve request_type_id for 'salary_increment' dynamically
$request_type_id = 0;
if ($typeStmt = $conDB->prepare("SELECT id FROM approval_request_types WHERE type_name = 'salary_increment' LIMIT 1")) {
    $typeStmt->execute();
    $typeRes = $typeStmt->get_result();
    if ($typeRow = $typeRes->fetch_assoc()) {
        $request_type_id = (int)$typeRow['id'];
    }
    $typeStmt->close();
}

if ($request_type_id > 0) {
    $stmt = $conDB->prepare("SELECT ra.approval_level, ra.status, ra.action_date,
                                    COALESCE(e.name, al.fullname, al.username) AS approver_name,
                                    al.user_type, ra.note
                             FROM request_approvers ra
                             LEFT JOIN employees e ON ra.approver_id = e.emp_id
                             LEFT JOIN admin_login al ON al.emp_id = ra.approver_id
                             WHERE ra.request_inv_no = ? AND ra.request_type_id = ?
                             ORDER BY ra.approval_level ASC");
    $stmt->bind_param('si', $request_inv_no, $request_type_id);
    $stmt->execute();
    $cr = $stmt->get_result();
    while ($row = $cr->fetch_assoc()) {
        $chain[] = $row;
    }
    $stmt->close();
}

// Helpers
$status_class = 'secondary';
$status_icon = 'fa-circle';
if ($req['current_status'] === 'approved') { $status_class = 'success'; $status_icon = 'fa-check-circle'; }
elseif ($req['current_status'] === 'rejected') { $status_class = 'danger'; $status_icon = 'fa-times-circle'; }
elseif ($req['current_status'] === 'cancelled') { $status_class = 'secondary'; $status_icon = 'fa-ban'; }
elseif (strpos($req['current_status'], 'pending') !== false) { $status_class = 'warning'; $status_icon = 'fa-clock'; }

// Use getAvatarImagePath function for proper gender-based default avatars
$avatar_path = getAvatarImagePath($req['avatar'] ?? '', $req['sex'] ?? 1);
?>
<!doctype html>
<html lang="<?= isset($current_lang) ? $current_lang : 'en' ?>" <?= (isset($is_rtl) && $is_rtl) ? 'dir="rtl"' : '' ?>>
<head>
    <meta charset="utf-8" />
    <title><?= isset($site_title) ? $site_title : 'Al-Mutlak' ?> - Salary Increment Approval History</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="shortcut icon" href="<?= function_exists('get_setting') ? (get_setting($conDB, 'favicon') ?? 'assets/images/favicon.ico') : 'assets/images/favicon.ico' ?>">
    <link href="assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/style.css" rel="stylesheet" type="text/css" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .page-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #fff; padding: 30px 0; margin-bottom: 30px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); text-align: <?= $is_rtl ?? false ? 'right' : 'left' ?>; }
        .page-header h3 { margin: 0; font-weight: 600; }
        .info-card { background: #fff; border-radius: 10px; padding: 25px; margin-bottom: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); text-align: <?= $is_rtl ?? false ? 'right' : 'left' ?>; }
        .info-card h5 { color: #667eea; margin-bottom: 20px; font-weight: 600; border-bottom: 2px solid #f0f0f0; padding-bottom: 10px; }
        .employee-header { display: flex; align-items: center; margin-bottom: 20px; text-align: <?= $is_rtl ?? false ? 'right' : 'left' ?>; }
        .employee-avatar { width: 80px; height: 80px; border-radius: 50%; object-fit: cover; <?= $is_rtl ?? false ? 'margin-left' : 'margin-right' ?>: 20px; border: 3px solid #667eea; }
        .info-row { display: flex; padding: 12px 0; border-bottom: 1px solid #f0f0f0; }
        .info-row:last-child { border-bottom: none; }
        .info-label { font-weight: 600; color: #666; width: 40%; display: flex; align-items: center; }
        .info-label i { margin-right: 8px; color: #667eea; width: 20px; }
        .info-value { color: #333; width: 60%; }

        /* Modern Timeline Design */
        .timeline { position: relative; padding: 10px 0; }
        .timeline:before { content: ''; position: absolute; left: 19px; top: 0; bottom: 0; width: 2px; background: #eaedf1; }
        .timeline-item { position: relative; padding-left: 50px; margin-bottom: 20px; }
        .timeline-item:last-child { margin-bottom: 0; }
        .timeline-marker {
            position: absolute; left: -11px; top: 15px; width: 24px; height: 24px;
            border-radius: 50%; background: #fff; border: 2px solid #adb5bd;
            z-index: 1; display:flex; align-items:center; justify-content:center;
            font-size: 10px; color: #adb5bd; transition: all 0.3s ease;
        }
        .timeline-item.approved .timeline-marker { border-color: #28a745; color: #28a745; background-color: #f6ffed; }
        .timeline-item.rejected .timeline-marker { border-color: #dc3545; color: #dc3545; background-color: #fff1f0; }
        .timeline-item.pending .timeline-marker { border-color: #ffc107; color: #ffc107; background-color: #fffbe6; }

        .timeline-content {
            background: #fff; padding: 15px; border-radius: 8px;
            border: 1px solid #e3e8ee; box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            position: relative; transition: all 0.2s ease-in-out;
            text-align: <?= $is_rtl ?? false ? 'right' : 'left' ?>;
        }
        .timeline-content:hover { border-color: #667eea; box-shadow: 0 4px 12px rgba(102, 126, 234, 0.1); }
        .status-badge { padding: 4px 10px; border-radius: 4px; font-size: 10px; font-weight: 700; text-transform: uppercase; }
        .timeline-time { color: #999; font-size: 12px; }

        /* RTL Adjustments */
        [dir="rtl"] .timeline:before { left: auto; right: 19px; }
        [dir="rtl"] .timeline-item { padding-left: 0; padding-right: 50px; }
        [dir="rtl"] .timeline-marker { left: auto; right: 8px; }
        .btn-back { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none; color: #fff; padding: 12px 30px; border-radius: 25px; font-weight: 600; }
    </style>
</head>
<body class="enlarged">
    <div class="page-header">
        <div class="container">
            <h3><i class="fas fa-history"></i> <?= __('salary_increment_approval_history', 'Salary Increment Approval History') ?></h3>
            <p class="mb-0"><?= __('complete_approval_timeline') ?></p>
        </div>
    </div>

    <div class="container" style="margin-bottom:30px;">
        <!-- Employee Info -->
        <div class="info-card">
            <div class="employee-header">
                <img src="<?= $avatar_path ?>" class="employee-avatar" alt="avatar" />
                <div>
                    <h4 style="margin:0;"><?= getDisplayName($req['employee_name'])?></h4>
                    <div class="text-muted" style="margin-top:6px;">
                        <strong><?= __('emp_id') ?>:</strong> <?= htmlspecialchars($req['emp_id']) ?>
                        &nbsp; | &nbsp;
                        <strong><?= __('department') ?>:</strong> <?= ($is_rtl ?? false ? $req['department_name_ar'] ?? 'N/A' : $req['department_name'] ?? 'N/A') ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="info-card">
                    <h5><i class="fas fa-arrow-trend-up"></i> <?= __('increment_details', 'Increment Details')?></h5>
                    <div class="info-row"><div class="info-label"><i class="fas fa-barcode"></i> <?= __('request_id') ?>:</div><div class="info-value"><code><?= htmlspecialchars($req['request_inv_no']) ?></code></div></div>
                    <div class="info-row"><div class="info-label"><i class="fas fa-arrow-trend-up"></i> <?= __('increment_amount', 'Increment Amount')?>:</div><div class="info-value"><strong><?= number_format((float)$req['increment_amount'], 2) ?></strong></div></div>
                    <?php if ($req['approved_amount'] !== null): ?>
                    <div class="info-row"><div class="info-label"><i class="fas fa-check-circle"></i> <?= __('approved_amount', 'Approved Amount')?>:</div><div class="info-value"><strong><?= number_format((float)$req['approved_amount'], 2) ?></strong></div></div>
                    <?php endif; ?>
                    <?php if (!empty($req['last_increment_date'])): ?>
                    <div class="info-row"><div class="info-label"><i class="fas fa-calendar-check"></i> <?= __('increment_effective_date', 'Increment Effective Date')?>:</div><div class="info-value"><?= htmlspecialchars((string)$req['last_increment_date']) ?></div></div>
                    <?php endif; ?>
                    <div class="info-row"><div class="info-label"><i class="fas fa-clipboard-check"></i> <?= __('evaluation_score', 'Evaluation Score')?>:</div><div class="info-value"><?= $req['evaluation_score'] !== null ? number_format((float)$req['evaluation_score'], 2) : 'N/A' ?></div></div>
                    <div class="info-row"><div class="info-label"><i class="fas fa-user-tie"></i> <?= __('submitted_by', 'Submitted By') ?>:</div><div class="info-value"><?= htmlspecialchars((string)($req['submitted_by_name'] ?? $req['submitted_by'])) ?></div></div>
                    <div class="info-row"><div class="info-label"><i class="fas fa-info-circle"></i> <?= __('reason', 'Reason') ?>:</div><div class="info-value"><?= nl2br(htmlspecialchars(getDisplayName($req['reason'] ?? 'N/A'))) ?></div></div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="info-card">
                    <h5><i class="fas fa-file-alt"></i> <?= __('request_information')?></h5>
                    <div class="info-row"><div class="info-label"><i class="fas fa-circle"></i> <?= __('status') ?>:</div><div class="info-value"><span class="badge badge-<?= htmlspecialchars($status_class) ?>"><i class="fas <?= htmlspecialchars($status_icon) ?>"></i> <?= htmlspecialchars(getDisplayName(ucfirst(str_replace('_', ' ', $req['current_status'])))) ?></span></div></div>
                    <div class="info-row"><div class="info-label"><i class="fas fa-user-check"></i> <?= __('approval_level') ?>:</div><div class="info-value"><?= (int)($req['current_approval_level'] ?? 0) ?></div></div>
                    <div class="info-row"><div class="info-label"><i class="fas fa-calendar"></i> <?= __('submitted_date') ?>:</div><div class="info-value"><?= isset($req['created_at']) ? format_safe_date($req['created_at'], 'd M Y H:i') : 'N/A' ?></div></div>
                </div>

                <div class="info-card">
                    <h5><i class="fas fa-money-bill-wave"></i> <?= __('salary_information', 'Salary Information') ?></h5>
                    <?php if (!$salary_info): ?>
                        <div class="alert alert-info"><i class="fas fa-info-circle"></i> <?= __('no_data_found', 'No data found') ?></div>
                    <?php else: ?>
                        <?php foreach ($salary_components as [$key, $fallback, $value]): if ((float)$value <= 0) continue; ?>
                        <div class="info-row"><div class="info-label"><i class="fas fa-coins"></i> <?= __($key, $fallback) ?>:</div><div class="info-value"><?= number_format((float)$value, 2) ?></div></div>
                        <?php endforeach; ?>
                        <div class="info-row"><div class="info-label"><i class="fas fa-sack-dollar"></i> <?= __('total_salary', 'Total salary') ?>:</div><div class="info-value"><strong style="color:#667eea;"><?= number_format((float)$salary_info['total_salary'], 2) ?></strong></div></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Approval Chain Timeline -->
        <div class="info-card">
            <h5 style="display:flex;justify-content:space-between;align-items:center;cursor:pointer;margin-bottom:0;" onclick="toggleApprovalChain()">
                <span><i class="fas fa-sitemap"></i> <?= __('approval_chain') ?></span>
                <span id="approvalChainToggleIcon" class="text-muted"><i class="fas fa-chevron-down"></i></span>
            </h5>
            <div id="approvalChainBody" style="display:none; margin-top:20px;">
            <?php if (empty($chain)): ?>
                <div class="alert alert-info"><i class="fas fa-info-circle"></i> <?= __('no_approvers') ?></div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th><?= __('level') ?></th>
                                <th><?= __('approver') ?></th>
                                <th><?= __('status') ?></th>
                                <th><?= __('action_date') ?></th>
                                <th><?= __('note') ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($chain as $item): ?>
                            <tr>
                                <td><span class="badge badge-primary"><?= (int)$item['approval_level'] ?></span></td>
                                <td><?= getDisplayName($item['approver_name']) ?></td>
                                <td>
                                    <?php
                                    $status = $item['status'] ?? 'awaiting';
                                    $badge_class = 'secondary';
                                    if ($status === 'approved') $badge_class = 'success';
                                    elseif ($status === 'rejected') $badge_class = 'danger';
                                    elseif ($status === 'pending') $badge_class = 'warning';
                                    elseif ($status === 'awaiting') $badge_class = 'info';
                                    ?>
                                    <span class="badge badge-<?= htmlspecialchars($badge_class) ?>"><?= htmlspecialchars(getDisplayName(ucfirst(str_replace('_', ' ', $status)))) ?></span>
                                </td>
                                <td><?= $item['action_date'] ? format_safe_date($item['action_date'], 'd M Y H:i') : __('pending') ?></td>
                                <td><small><?= htmlspecialchars(getDisplayName($item['note'] ?? '-')) ?></small></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
            </div>
        </div>

        <!-- Status History Timeline -->
        <div class="info-card">
            <h5><i class="fas fa-history"></i> <?= __('status_history_timeline') ?></h5>
            <?php if (empty($history)): ?>
                <div class="alert alert-info"><i class="fas fa-info-circle"></i> <?= __('no_history') ?></div>
            <?php else: ?>
                <div class="timeline">
                    <?php foreach ($history as $event):
                        $event_status = strtolower($event['status'] ?? '');
                        $event_class = 'pending';
                        if (strpos($event_status, 'approved') !== false || strpos($event_status, 'completed') !== false) {
                            $event_class = 'approved';
                        } elseif (strpos($event_status, 'reject') !== false) {
                            $event_class = 'rejected';
                        } elseif (strpos($event_status, 'pending') !== false) {
                            $event_class = 'pending';
                        }
                    ?>
                    <div class="timeline-item <?= htmlspecialchars($event_class) ?>">
                        <div class="timeline-marker">
                            <?php if ($event_class === 'approved'): ?>
                                <i class="fas fa-check" style="font-size:10px;"></i>
                            <?php elseif ($event_class === 'rejected'): ?>
                                <i class="fas fa-times" style="font-size:10px;"></i>
                            <?php else: ?>
                                <i class="fas fa-clock" style="font-size:10px;"></i>
                            <?php endif; ?>
                        </div>
                        <div class="timeline-content">
                            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
                                <strong><?= ucfirst(htmlspecialchars(str_replace('_', ' ', $event['status']))) ?></strong>
                                <span class="timeline-time"><i class="fas fa-clock"></i> <?= format_safe_date($event['created_at'] ?? null, 'd M Y H:i') ?></span>
                            </div>
                            <div style="color:#666; margin-bottom:6px;">
                                <i class="fas fa-user"></i> <strong><?= getDisplayName($event['emp_name']) ?></strong>
                            </div>
                            <?php if (!empty($event['note'])): ?>
                            <div style="background:#f8f9fa; padding:8px; border-radius:4px; margin-top:8px; font-size:13px;">
                                <strong><?= __('note') ?>:</strong> <?= nl2br(htmlspecialchars($event['note'])) ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <div class="text-center mt-4">
                <a href="all_applied_salary_increment.php" class="btn btn-back"><i class="fas fa-arrow-left"></i> <?= __('back_to_requests') ?></a>
            </div>
        </div>
    </div>

    <script src="assets/js/jquery.min.js"></script>
    <script src="assets/js/jquery.slimscroll.js"></script>
    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/jquery.app.js?t=<?= time() ?>"></script>
    <script>
        function toggleApprovalChain() {
            const body = document.getElementById('approvalChainBody');
            const icon = document.getElementById('approvalChainToggleIcon');
            const isHidden = body.style.display === 'none';
            body.style.display = isHidden ? 'block' : 'none';
            icon.innerHTML = isHidden ? '<i class="fas fa-chevron-up"></i>' : '<i class="fas fa-chevron-down"></i>';
        }
    </script>
</body>
</html>
<?php $conDB->close(); ?>
