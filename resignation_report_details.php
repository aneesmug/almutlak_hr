<?php
/****************************************************************
 * RESIGNATION REPORT DETAILS PAGE
 * Displays comprehensive resignation information including:
 * - Employee Information
 * - Resignation Details
 * - Exit Interview Answers
 * - Replacement Information
 * - Approval Timeline
 ****************************************************************/

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/session_check.php';

$query = mysqli_query($conDB, "SELECT * FROM `admin_login` WHERE `id_iqama`='" . $username . "'");
if (mysqli_num_rows($query) == 1) {
    include("./includes/avatar_select.php");

    // 1. Get and validate the IDs from the URL
    $resignation_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    $emp_id = isset($_GET['emp_id']) ? $_GET['emp_id'] : '';

    if ($resignation_id === 0 || empty($emp_id)) {
        die("Invalid request parameters.");
    }

    // 2. Fetch resignation details
    $sql = "SELECT 
                r.*, 
                e.name as employee_name,
                e.avatar,
                e.emp_id,
                e.iqama,
                d.dep_nme AS `department_name`,
                j.job as designation
            FROM emp_resignations r
            JOIN employees e ON r.emp_id = e.emp_id
            LEFT JOIN department d ON e.dept = d.id
            LEFT JOIN ac_jobs j ON j.id = e.actual_job
            WHERE r.id = ? AND r.emp_id = ?";

    $stmt = $conDB->prepare($sql);
    $stmt->bind_param("is", $resignation_id, $emp_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $resignation = $result->fetch_assoc();
    $stmt->close();

    if (!$resignation) {
        die("Resignation request not found.");
    }

    // 3. Fetch exit interview data
    $exit_interview = [];
    $exit_sql = "SELECT q1_reasons, q2_support, q3_resources, q4_manager, q5_growth, q6_compensation, q7_different, q8_recommend, q9_additional FROM emp_exit_interviews WHERE resignation_id = ?";
    $stmt_exit = $conDB->prepare($exit_sql);
    if ($stmt_exit) {
        $stmt_exit->bind_param("i", $resignation_id);
        $stmt_exit->execute();
        $exit_result = $stmt_exit->get_result();
        if ($row = $exit_result->fetch_assoc()) {
            // Map the database columns to question labels
            $exit_interview = [
                __('q1_reasons') => $row['q1_reasons'],
                __('q2_support') => $row['q2_support'],
                __('q3_resources') => $row['q3_resources'],
                __('q4_manager') => $row['q4_manager'],
                __('q5_growth') => $row['q5_growth'],
                __('q6_compensation') => $row['q6_compensation'],
                __('q7_different') => $row['q7_different'],
                __('q8_recommend') => $row['q8_recommend'],
                __('q9_additional') => $row['q9_additional']
            ];
        }
        $stmt_exit->close();
    }

    // 4. Fetch replacement information
    $replacement_data = [];
    // Try to fetch replacement info, but don't fail if table doesn't exist
    try {
        $replacement_sql = "SELECT need_replacement, job_title, job_description, experience, certificate, academic_achievement, date_of_joining FROM resignation_replacement_info WHERE resignation_id = ?";
        $stmt_replacement = $conDB->prepare($replacement_sql);
        if ($stmt_replacement) {
            $stmt_replacement->bind_param("i", $resignation_id);
            $stmt_replacement->execute();
            $replacement_result = $stmt_replacement->get_result();
            $replacement_data = $replacement_result->fetch_assoc() ?? [];
            $stmt_replacement->close();
        }
    } catch (Exception $e) {
        // Table doesn't exist or query failed - continue without replacement data
        $replacement_data = [];
    }

    // 5. Fetch approval chain
    $request_inv_no = $resignation['request_inv_no'] ?? '';
    $approval_chain = [];
    
    if (!empty($request_inv_no)) {
        $type_query = mysqli_query($conDB, "SELECT `id` FROM `approval_request_types` WHERE `type_name` = 'resignation_request' LIMIT 1");
        if ($type_query && mysqli_num_rows($type_query) > 0) {
            $request_type_row = mysqli_fetch_assoc($type_query);
            $request_type_id = (int)$request_type_row['id'];
            mysqli_free_result($type_query);
            
            if ($request_type_id > 0) {
                $chain_sql = "SELECT ra.*, 
                           e.name AS approver_name, 
                           e.emp_id AS approver_emp_id,
                           al.user_type AS approver_role,
                           d2.dep_nme AS approver_dept_name
                       FROM request_approvers ra
                       LEFT JOIN employees e ON ra.approver_id = e.emp_id
                       LEFT JOIN admin_login al ON e.emp_id = al.emp_id
                       LEFT JOIN department d2 ON e.dept = d2.id
                       WHERE ra.request_inv_no = ? AND ra.request_type_id = ?
                       ORDER BY ra.approval_level ASC";
                $stmt_chain = $conDB->prepare($chain_sql);
                if ($stmt_chain) {
                    $stmt_chain->bind_param("si", $request_inv_no, $request_type_id);
                    $stmt_chain->execute();
                    $chain_result = $stmt_chain->get_result();
                    while ($chain_row = $chain_result->fetch_assoc()) {
                        $approval_chain[] = $chain_row;
                    }
                    $stmt_chain->close();
                }
            }
        }
    }

?>

    <!doctype html>
    <html lang="<?= $current_lang ?? 'en' ?>" <?= ($is_rtl ?? false) ? 'dir="rtl"' : '' ?>>

    <head>
        <meta charset="utf-8" />
        <title><?= $site_title ?> - Resignation Report</title>
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
        <meta content="Anees Afzal" name="author" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />
        <link rel="shortcut icon" href="<?=get_setting($conDB, 'favicon')?>">
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
        <script>
            window.lang = <?= json_encode($GLOBALS['translations'] ?? []) ?>;
        </script>
        <style>
            :root {
                --primary-color: #4a90e2;
                --text-color: #333;
                --muted-color: #6c757d;
                --border-color: #e9ecef;
                --background-light: #f8f9fa;
                --success-color: #28a745;
                --danger-color: #dc3545;
                --warning-color: #ffc107;
            }
            body.enlarged {
                 background-color: #f4f7f6;
            }
            .report-wrapper {
                font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
                max-width: 800px;
                margin: 1rem auto;
                background-color: #fff;
                border-radius: 8px;
                box-shadow: 0 4px 12px rgba(0,0,0,.08);
                color: var(--text-color);
                font-size: 14px;
            }
            .report-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 1rem 1.5rem;
                border-bottom: 1px solid var(--border-color);
            }
            .report-header .logo-container img { max-height: 40px; }
            .report-header .report-meta { text-align: right; }
            .report-header .report-title { font-size: 1.1rem; font-weight: 600; margin: 0; }
            .report-header .report-subtitle { font-size: 0.8rem; color: var(--muted-color); margin: 0; }
            
            [dir="rtl"] .report-header { flex-direction: row-reverse; }
            [dir="rtl"] .report-header .report-meta { text-align: left; }
            
            .report-body { padding: 1.5rem; }
            
            .employee-banner { display: flex; align-items: center; background-color: var(--background-light); padding: 1rem; border-radius: 6px; margin-bottom: 1.5rem; border: 1px solid var(--border-color); }
            .employee-banner .avatar { width: 60px; height: 60px; border-radius: 50%; margin-right: 1rem; }
            .employee-banner .info { flex: 1; }
            .employee-banner .info h4 { font-weight: 600; margin: 0 0 0.2rem 0; font-size: 1.1rem; }
            .employee-banner .info p { color: var(--muted-color); margin: 0; font-size: 0.85rem; }
            
            [dir="rtl"] .employee-banner { flex-direction: row-reverse; }
            [dir="rtl"] .employee-banner .avatar { margin-right: 0; margin-left: 1rem; order: 2; }
            [dir="rtl"] .employee-banner .info { order: 1; text-align: right; }

            .report-section { margin-bottom: 1.5rem; }
            .section-title { font-weight: 600; color: var(--primary-color); margin-bottom: 1rem; font-size: 1rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem; }
            .section-title i { margin-right: 0.5rem; }
            [dir="rtl"] .section-title i { margin-right: 0; margin-left: 0.5rem; }

            .grid-details { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; }
            .detail-item .label { font-size: 0.8rem; color: var(--muted-color); margin-bottom: 0.1rem; }
            .detail-item .value { font-weight: 500; font-size: 0.9rem; }
            .detail-item .value.highlight { font-weight: 700; color: var(--success-color); }

            .approval-timeline { position: relative; padding-left: 5px; }
            .timeline-item { position: relative; padding-bottom: 1rem; padding-left: 30px; min-height: 20px; }
            .timeline-item:last-child { padding-bottom: 0; }
            .timeline-item::before { content: ''; position: absolute; left: 0; top: 10px; bottom: 0; width: 2px; background: var(--border-color); }
            .timeline-item:last-child::before { display: none; }
            .timeline-item .icon { position: absolute; left: -9px; top: 0; width: 20px; height: 20px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 10px; border: 2px solid #fff; }
            .timeline-item.approved .icon { background-color: var(--success-color); }
            .timeline-item.pending .icon { background-color: var(--warning-color); }
            .timeline-item.rejected .icon { background-color: var(--danger-color); }
            .timeline-item.future .icon, .timeline-item .icon { background-color: #ced4da; }
            .timeline-item .status { font-weight: 600; line-height: 20px; font-size: 0.9rem; }
            
            [dir="rtl"] .approval-timeline { padding-left: 0; padding-right: 5px; }
            [dir="rtl"] .timeline-item { padding-left: 0; padding-right: 30px; }
            [dir="rtl"] .timeline-item::before { left: auto; right: 0; }
            [dir="rtl"] .timeline-item .icon { left: auto; right: -9px; }

            .info-table { width: 100%; border-collapse: collapse; }
            .info-table tr { border-bottom: 1px solid var(--border-color); }
            .info-table tr:last-child { border-bottom: none; }
            .info-table td { padding: 0.75rem; }
            .info-table td.label { background-color: var(--background-light); font-weight: 600; color: var(--text-color); width: 35%; }
            .info-table td.value { color: var(--text-color); }

            .report-footer { padding: 1rem 1.5rem; border-top: 1px solid var(--border-color); margin-top: 1.5rem; }

            @media print {
                @page { size: A4; margin: 0.5cm; }
                body { background-color: #fff !important; font-size: 12px; }
                .no-print, .left.side-menu, .footer, .topbar { display: none !important; }
                #wrapper, .content-page, .content, .container-fluid { padding: 0 !important; margin: 0 !important; }
                .report-wrapper { max-width: 100%; margin: 0; box-shadow: none; border: none; border-radius: 0; }
                .report-body { padding: 1cm 0.5cm; }
                .employee-banner { background-color: #f8f9fa !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
                .report-section { margin-bottom: 1rem; }
            }
        </style>
    </head>

    <body class="enlarged" data-keep-enlarged="true">
        <div id="wrapper">
            <div class="left side-menu no-print">
                <div class="slimscroll-menu" id="remove-scroll">
                    <div class="topbar-left"><a href="dashboard.php" class="logo"><span><img src="<?=get_setting($conDB, 'logo')?>" alt="" height="22"></span><i><img src="<?=get_setting($conDB, 'white_logo')?>" alt="" height="28"></i></a></div>
                    <?php include("./includes/main_menu.php"); ?>
                    <div class="clearfix"></div>
                </div>
            </div>

            <div class="content-page">
                <?php include("./includes/topbar.php"); ?>
                <div class="content">
                    <div class="container-fluid">
                        <div class="text-right no-print mb-3">
                            <a href="javascript:void(0);" onclick="window.print()" class="btn btn-primary waves-effect waves-light"><i class="fa fa-print mr-1"></i> <?= __('print_report') ?></a>
                            <a href="all_resignations.php" class="btn btn-secondary waves-effect waves-light"><i class="fa fa-arrow-left mr-1"></i> <?= __('back') ?></a>
                        </div>
                        
                        <div class="report-wrapper">
                            <div class="report-header">
                                <div class="logo-container"><img src="<?=get_setting($conDB, 'logo')?>" alt="Company Logo"></div>
                                <div class="report-meta">
                                    <h2 class="report-title"><?= __('resignation_request_report') ?></h2>
                                    <p class="report-subtitle"><?= __('request_id') ?>: #<?= htmlspecialchars($resignation['id'] ?? 'N/A'); ?></p>
                                </div>
                            </div>

                            <div class="report-body">
                                <!-- Employee Information Banner -->
                                <div class="employee-banner">
                                    <img src="<?= !empty($resignation['avatar']) && file_exists($resignation['avatar']) ? htmlspecialchars($resignation['avatar']) : 'assets/images/users/avatar-1.jpg'; ?>" alt="Employee Avatar" class="avatar">
                                    <div class="info">
                                        <h4><?= getDisplayName($resignation['employee_name']); ?></h4>
                                        <p><?= __('emp_id') ?>: <?= htmlspecialchars($resignation['emp_id'] ?? 'N/A'); ?> | <?= __('iqama_id_label') ?>: <?= htmlspecialchars($resignation['iqama'] ?? 'N/A'); ?></p>
                                    </div>
                                </div>

                                <!-- Resignation Details -->
                                <div class="report-section">
                                    <h5 class="section-title"><i class="fa fa-file-alt"></i> <?= __('resignation_details') ?></h5>
                                    <table class="info-table">
                                        <tr>
                                            <td class="label"><?= __('department') ?></td>
                                            <td class="value"><?= getDisplayName($resignation['department_name']); ?></td>
                                        </tr>
                                        <tr>
                                            <td class="label"><?= __('designation') ?></td>
                                            <td class="value"><?= getDisplayName($resignation['designation']) ; ?></td>
                                        </tr>
                                        <tr>
                                            <td class="label"><?= __('submitted_on') ?></td>
                                            <td class="value"><?= date('d M Y', strtotime($resignation['created_at'])); ?></td>
                                        </tr>
                                        <tr>
                                            <td class="label"><?= __('last_working_day_employee') ?></td>
                                            <td class="value"><strong class="text-danger"><?= date('d M Y', strtotime($resignation['last_working_day'])); ?></strong></td>
                                        </tr>
                                        <tr>
                                            <td class="label"><?= __('status') ?></td>
                                            <td class="value">
                                                <?php 
                                                    $badge_class = 'secondary';
                                                    $status_text = $resignation['status'] ?? 'N/A';
                                                    switch ($status_text) {
                                                        case 'pending': $badge_class = 'warning'; break;
                                                        case 'approved': $badge_class = 'success'; break;
                                                        case 'rejected': $badge_class = 'danger'; break;
                                                    }
                                                ?>
                                                <span class="badge badge-<?= $badge_class; ?>"><?= __(strtolower($status_text)); ?></span>
                                            </td>
                                        </tr>
                                    </table>
                                </div>

                                <!-- Exit Interview Answers -->
                                <?php if (!empty($exit_interview)): ?>
                                <div class="report-section">
                                    <h5 class="section-title"><i class="fa fa-clipboard-check"></i> <?= __('exit_interview_answers') ?></h5>
                                    <table class="info-table">
                                        <?php foreach ($exit_interview as $question => $answer): 
                                            // Skip if both question label and answer are empty
                                            if (empty($question) && empty($answer)) continue;
                                        ?>
                                        <tr>
                                            <td class="label"><?= htmlspecialchars($question); ?></td>
                                            <td class="value"><?= !empty($answer) ? htmlspecialchars($answer) : '<span class="text-muted italic">No response</span>'; ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </table>
                                </div>
                                <?php endif; ?>

                                <!-- Replacement Information -->
                                <?php if (!empty($replacement_data)): ?>
                                <div class="report-section">
                                    <h5 class="section-title"><i class="fa fa-user-tie"></i> <?= __('replacement_information') ?></h5>
                                    <table class="info-table">
                                        <?php if (!empty($replacement_data['need_replacement'])): ?>
                                        <tr>
                                            <td class="label"><?= __('replacement_needed') ?></td>
                                            <td class="value">
                                                <span class="badge badge-<?= $replacement_data['need_replacement'] === 'yes' ? 'success' : 'danger'; ?>">
                                                    <?= htmlspecialchars(strtoupper($replacement_data['need_replacement'])); ?>
                                                </span>
                                            </td>
                                        </tr>
                                        <?php endif; ?>
                                        <?php if (!empty($replacement_data['job_title'])): ?>
                                        <tr>
                                            <td class="label"><?= __('job_title') ?></td>
                                            <td class="value"><?= htmlspecialchars($replacement_data['job_title']); ?></td>
                                        </tr>
                                        <?php endif; ?>
                                        <?php if (!empty($replacement_data['job_description'])): ?>
                                        <tr>
                                            <td class="label"><?= __('job_description') ?></td>
                                            <td class="value"><pre style="margin: 0; white-space: pre-wrap; word-break: break-word;"><?= htmlspecialchars($replacement_data['job_description']); ?></pre></td>
                                        </tr>
                                        <?php endif; ?>
                                        <?php if (!empty($replacement_data['experience'])): ?>
                                        <tr>
                                            <td class="label"><?= __('experience') ?></td>
                                            <td class="value"><?= htmlspecialchars($replacement_data['experience']); ?></td>
                                        </tr>
                                        <?php endif; ?>
                                        <?php if (!empty($replacement_data['certificate'])): ?>
                                        <tr>
                                            <td class="label"><?= __('certificate') ?></td>
                                            <td class="value"><?= htmlspecialchars($replacement_data['certificate']); ?></td>
                                        </tr>
                                        <?php endif; ?>
                                        <?php if (!empty($replacement_data['academic_achievement'])): ?>
                                        <tr>
                                            <td class="label"><?= __('academic_achievement') ?></td>
                                            <td class="value"><?= htmlspecialchars($replacement_data['academic_achievement']); ?></td>
                                        </tr>
                                        <?php endif; ?>
                                        <?php if (!empty($replacement_data['date_of_joining'])): ?>
                                        <tr>
                                            <td class="label"><?= __('date_of_joining') ?></td>
                                            <td class="value"><?= date('d M Y', strtotime($replacement_data['date_of_joining'])); ?></td>
                                        </tr>
                                        <?php endif; ?>
                                    </table>
                                </div>
                                <?php endif; ?>

                                <!-- Approval Timeline -->
                                <?php if (!empty($approval_chain)): ?>
                                <div class="report-section">
                                    <h5 class="section-title"><i class="fa fa-history"></i> <?= __('approval_timeline') ?></h5>
                                    <div class="approval-timeline">
                                        <?php foreach ($approval_chain as $approval): 
                                            $is_approved = $approval['status'] === 'approved';
                                            $is_pending = $approval['status'] === 'awaiting';
                                            $is_rejected = $approval['status'] === 'rejected';
                                            $approval_class = $is_approved ? 'approved' : ($is_pending ? 'pending' : ($is_rejected ? 'rejected' : 'future'));
                                        ?>
                                        <div class="timeline-item <?= $approval_class; ?>">
                                            <div class="icon">
                                                <?php if ($is_approved): ?>
                                                    <i class="fa fa-check"></i>
                                                <?php elseif ($is_rejected): ?>
                                                    <i class="fa fa-times"></i>
                                                <?php elseif ($is_pending): ?>
                                                    <i class="fa fa-clock"></i>
                                                <?php else: ?>
                                                    <i class="fa fa-ellipsis-h"></i>
                                                <?php endif; ?>
                                            </div>
                                            <div class="status">
                                                <strong><?= 'Level ' . htmlspecialchars($approval['approval_level']); ?></strong>
                                                <?php if (!empty($approval['approver_name'])): ?>
                                                    - <?= htmlspecialchars($approval['approver_name']); ?>
                                                <?php endif; ?>
                                                <br>
                                                <small class="text-muted">
                                                    <?php 
                                                        if ($is_approved) {
                                                            echo __('approved');
                                                        } elseif ($is_rejected) {
                                                            echo __('rejected');
                                                        } elseif ($is_pending) {
                                                            echo __('pending_approval');
                                                        } else {
                                                            echo __('awaiting');
                                                        }
                                                    ?>
                                                    <?php if (!empty($approval['approval_date'])): ?>
                                                        - <?= date('d M Y H:i', strtotime($approval['approval_date'])); ?>
                                                    <?php endif; ?>
                                                </small>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="report-footer">
                                <p class="text-muted text-center mb-0">
                                    <small><?= __('generated_on') ?>: <?= date('d M Y H:i'); ?></small>
                                </p>
                            </div>
                        </div>

                    </div>
                </div>
                <footer class="footer no-print"><?= $site_footer ?></footer>
            </div>
        </div>

        <script src="assets/js/jquery.min.js"></script>
        <script src="assets/js/bootstrap.bundle.min.js"></script>
        <script src="assets/js/metisMenu.min.js"></script>
        <script src="assets/js/waves.js"></script>
        <script src="assets/js/jquery.slimscroll.js"></script>
        <script src="assets/js/jquery.core.js"></script>
        <script src="assets/js/jquery.app.js"></script>

    </body>
    </html>
<?php
    $conDB->close();
}
?>
