<?php
/**
 * ================================================================
 * EMPLOYEE PERFORMANCE EVALUATION SYSTEM
 * ================================================================
 * 
 * DESCRIPTION:
 * This page allows department managers to evaluate their employees
 * based on 10 performance criteria, each scored from 1-10.
 * 
 * FEATURES:
 * - Department managers can only evaluate employees in their department
 * - Auto-calculates total score (sum of all 10 criteria)
 * - Displays employee name, department, and current position
 * - Uses Select2 for employee selection
 * - Shows previous evaluations history
 * - Validates manager permissions before submission
 * 
 * EVALUATION CRITERIA (1-10 scale):
 * 1. Punctuality Attendance (الإنتظام وعدم التأخير)
 * 2. Achieving at the specified time (التحقيق في الوقت المحدد)
 * 3. Knowledge of job (معرفة الوظيفة)
 * 4. The Ability to solve problems (القدرة على حل المشاكل)
 * 5. Receptiveness to Feedback and Instructions (تقبل التوجيهات والتعليمات)
 * 6. Self & Professional Development (السعي لتطوير المهارات والمعرفة وتحسين الأداء بإستمرار)
 * 7. Work under pressure (العمل تحت الضغط)
 * 8. Communication skills and Teamwork (مهارات التواصل والعمل الجماعي)
 * 9. Creativity and speed of response (الإبداع وسرعة الإستجابة)
 * 10. Initiative and cooperation (المبادرة والتعاون)
 * 
 * ACCESS CONTROL:
 * - Only managers (emptype='Manager' or user_type='dept_user') can access
 * - Regular employees are redirected
 * 
 * CREATED: November 9, 2025
 * ================================================================
 */

// Include necessary files
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/session_check.php';
require_once __DIR__ . '/includes/helper_functions.php';

// ================================================================
// ACCESS CONTROL - Only Managers Allowed
// ================================================================
// (or an explicit 'Access Page: Employee Evaluation' Special Access grant)
// Direct supervisors (anyone set as supervisor_id of an active employee) always get access.
if (!$isDeptManager && empty($isSupervisor) && !user_has_special_access($conDB, $empid ?? '', 'access_employee_evaluation', $user_role ?? '', $user_type ?? '', $is_system_admin ?? false)) {
    header("Location: ./dashboard.php");
    exit();
}

// ================================================================
// HANDLE FORM SUBMISSION
// ================================================================
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_evaluation'])) {
    try {
        // Get form data
        $employee_emp_id = trim($_POST['employee_emp_id'] ?? '');
        $punctuality = (int)($_POST['punctuality'] ?? 10);
        $achieving_time = (int)($_POST['achieving_time'] ?? 10);
        $job_knowledge = (int)($_POST['job_knowledge'] ?? 10);
        $problem_solving = (int)($_POST['problem_solving'] ?? 10);
        $feedback_receptiveness = (int)($_POST['feedback_receptiveness'] ?? 10);
        $self_development = (int)($_POST['self_development'] ?? 10);
        $work_under_pressure = (int)($_POST['work_under_pressure'] ?? 10);
        $communication_teamwork = (int)($_POST['communication_teamwork'] ?? 10);
        $creativity_response = (int)($_POST['creativity_response'] ?? 10);
        $initiative_cooperation = (int)($_POST['initiative_cooperation'] ?? 10);
        $observation = trim($_POST['observation'] ?? '');
        
        // Validate employee selection
        if (empty($employee_emp_id)) {
            throw new Exception('Please select an employee to evaluate.');
        }
        
        // Validate score range (1-10)
        $scores = [
            $punctuality, $achieving_time, $job_knowledge, $problem_solving,
            $feedback_receptiveness, $self_development, $work_under_pressure,
            $communication_teamwork, $creativity_response, $initiative_cooperation
        ];
        
        foreach ($scores as $score) {
            if ($score < 1 || $score > 10) {
                throw new Exception('All scores must be between 1 and 10.');
            }
        }
        
        // Get employee details and verify they belong to manager's supervision
        $stmt = $pdo->prepare("
            SELECT e.emp_id, e.name, e.dept, e.actual_job, e.supervisor_id, d.dep_nme, j.job
            FROM employees e
            LEFT JOIN department d ON e.dept = d.id
            LEFT JOIN ac_jobs j ON e.actual_job = j.id
            WHERE e.emp_id = ? AND e.status = 1
        ");
        $stmt->execute([$employee_emp_id]);
        $employee = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$employee) {
            throw new Exception('Selected employee not found or inactive.');
        }
        
        // Verify manager has permission to evaluate this employee (must be their direct supervisor)
        if ($employee['supervisor_id'] != $empid) {
            throw new Exception('You can only evaluate employees who are directly under your supervision.');
        }
        
        // Calculate total score
        $total_score = array_sum($scores);
        
        // Begin transaction
        $pdo->beginTransaction();
        
        // Insert evaluation
        $stmt = $pdo->prepare("
            INSERT INTO emp_evaluations (
                manager_emp_id, employee_emp_id, dept_id, dept_name, 
                employee_name, employee_position,
                punctuality, achieving_time, job_knowledge, problem_solving,
                feedback_receptiveness, self_development, work_under_pressure,
                communication_teamwork, creativity_response, initiative_cooperation,
                observation, total_score, manager_acknowledgment_status
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
            )
        ");
        
        $stmt->execute([
            $empid,
            $employee['emp_id'],
            $employee['dept'],
            $employee['dep_nme'] ?? '',
            $employee['name'],
            $employee['job'] ?? '',
            $punctuality,
            $achieving_time,
            $job_knowledge,
            $problem_solving,
            $feedback_receptiveness,
            $self_development,
            $work_under_pressure,
            $communication_teamwork,
            $creativity_response,
            $initiative_cooperation,
            $observation,
            $total_score,
            'pending'  // Set initial status to pending - evaluation won't appear in reports until acknowledged/objected
        ]);
        
        $pdo->commit();
        
        // $success_message = 'Evaluation submitted successfully! Total Score: ' . $total_score . '/100';
        // salert(__('added_successfully'), sprintf(__('evaluation_submitted_successfully_total_score'), $total_score), $type = 'success', $redirectUrl = "", $btn = 'OK');

        $_SESSION['swal_alert'] = [
            'title' => __("success"),
            'message' => sprintf(__('evaluation_submitted_successfully_total_score'), $total_score),
            'type' => 'success'
        ];
        
        // Clear form by redirecting to avoid resubmission
        header("Location: employee_evaluation.php?success=1&score=" . $total_score);
        exit();
        
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $error_message = $e->getMessage();
    }
}

// Handle success redirect
if (isset($_GET['success']) && $_GET['success'] == '1') {
    $success_message = 'Evaluation submitted successfully! Total Score: ' . ($_GET['score'] ?? '0') . '/100';
}

// ================================================================
// GET DIRECT SUBORDINATE EMPLOYEES FOR DROPDOWN
// ================================================================
$dept_employees = [];
try {
    // Get employees where current user is their direct supervisor
    // Exclude employees who have been evaluated in the current month
    $stmt = $pdo->prepare("
        SELECT e.emp_id, e.name, e.actual_job, j.job
        FROM employees e
        LEFT JOIN ac_jobs j ON e.actual_job = j.id
        LEFT JOIN emp_evaluations ev ON ev.employee_emp_id = e.emp_id 
            AND YEAR(ev.created_at) = YEAR(CURDATE()) 
            AND MONTH(ev.created_at) = MONTH(CURDATE())
        WHERE e.supervisor_id = ? AND e.status = 1 AND e.emp_id != ?
            AND ev.id IS NULL
        ORDER BY e.name ASC
    ");
    $stmt->execute([$empid, $empid]); // Filter by supervisor_id instead of dept
    $dept_employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching direct subordinate employees: " . $e->getMessage());
}

// Total direct reports (to tell "none assigned" apart from "all evaluated")
$total_subordinates = 0;
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM employees WHERE supervisor_id = ? AND status = 1 AND emp_id != ?");
    $stmt->execute([$empid, $empid]);
    $total_subordinates = (int)$stmt->fetchColumn();
} catch (PDOException $e) {
    error_log("Error counting direct subordinates: " . $e->getMessage());
}

// ================================================================
// GET DEPARTMENT NAME
// ================================================================
$dept_name = '';
try {
    $stmt = $pdo->prepare("SELECT dep_nme FROM department WHERE id = ?");
    $stmt->execute([$user_dept]);
    $dept = $stmt->fetch(PDO::FETCH_ASSOC);
    $dept_name = $dept['dep_nme'] ?? 'Unknown Department';
} catch (PDOException $e) {
    error_log("Error fetching department: " . $e->getMessage());
}

?>
<?php
// ---- View (same look as the Smart Request pages, assets/css/smart_request.css) ----
$criteria = [
    'punctuality'            => ['Punctuality Attendance', 'الإنتظام وعدم التأخير', 'mdi-clock'],
    'achieving_time'         => ['Achieving at the specified time', 'التحقيق في الوقت المحدد', 'mdi-calendar-check'],
    'job_knowledge'          => ['Knowledge of job', 'معرفة الوظيفة', 'mdi-school'],
    'problem_solving'        => ['The Ability to solve problems', 'القدرة على حل المشاكل', 'mdi-lightbulb-on'],
    'feedback_receptiveness' => ['Receptiveness to Feedback and Instructions', 'تقبل التوجيهات والتعليمات', 'mdi-comment-check'],
    'self_development'       => ['Self & Professional Development', 'السعي لتطوير المهارات والمعرفة وتحسين الأداء بإستمرار', 'mdi-trending-up'],
    'work_under_pressure'    => ['Work under pressure', 'العمل تحت الضغط', 'mdi-speedometer'],
    'communication_teamwork' => ['Communication skills and Teamwork', 'مهارات التواصل والعمل الجماعي', 'mdi-account-multiple'],
    'creativity_response'    => ['Creativity and speed of response', 'الإبداع وسرعة الإستجابة', 'mdi-flash'],
    'initiative_cooperation' => ['Initiative and cooperation', 'المبادرة والتعاون', 'mdi-account-multiple-plus'],
];
// Keep the manager's scores when the server rejects a submission.
$posted = ($_SERVER['REQUEST_METHOD'] === 'POST') ? $_POST : [];
$h = function ($v) { return htmlspecialchars((string)$v, ENT_QUOTES); };
$swal = $_SESSION['swal_alert'] ?? null;
unset($_SESSION['swal_alert']);
?>
<!DOCTYPE html>
<html lang="<?= $current_lang ?? 'en' ?>" <?= ($is_rtl ?? false) ? 'dir="rtl"' : '' ?>>

<head>
    <meta charset="utf-8" />
    <title><?= $site_title ?> - <?= __('employee_performance_evaluation', 'Employee Evaluation') ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta content="Al-Mutlak HR System" name="description" />
    <meta content="Anees Afzal" name="author" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />

    <link rel="shortcut icon" href="<?= get_setting($conDB, 'favicon') ?>">
    <link href="./plugins/select2/css/select2.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/icons.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/metismenu.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/style.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/style_dark.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/smart_request.css?v=<?= @filemtime(__DIR__ . '/assets/css/smart_request.css') ?>" rel="stylesheet" type="text/css" />
    <?php if ($is_rtl): ?>
        <link href="assets/css/style_rtl.css" rel="stylesheet" type="text/css" />
    <?php endif; ?>
    <style>
        .ev-crit { display: flex; align-items: center; gap: 14px; padding: 14px 18px; border-bottom: 1px solid var(--sr-border); }
        .ev-crit:last-child { border-bottom: 0; }
        .ev-crit-no { flex: 0 0 auto; width: 34px; height: 34px; border-radius: 10px; display: inline-flex; align-items: center; justify-content: center; background: var(--sr-accent-soft); color: var(--sr-accent-strong); font-size: 17px; }
        .ev-crit-text { flex: 1 1 260px; min-width: 0; }
        .ev-crit-text b { display: block; font-size: 13.5px; color: var(--sr-text); }
        .ev-crit-text small { display: block; font-size: 12px; color: var(--sr-muted); }
        .ev-scale { display: flex; gap: 4px; flex: 0 0 auto; }
        .ev-scale label { margin: 0; position: relative; }
        .ev-scale input { position: absolute; opacity: 0; pointer-events: none; }
        .ev-scale span {
            display: inline-flex; align-items: center; justify-content: center; width: 32px; height: 32px; border-radius: 8px; cursor: pointer;
            border: 1px solid var(--sr-border-strong); background: var(--sr-surface); font-size: 12.5px; font-weight: 700; color: var(--sr-text-2);
            font-variant-numeric: tabular-nums; transition: background .12s, border-color .12s, color .12s;
        }
        .ev-scale span:hover { border-color: var(--sr-accent); color: var(--sr-accent-strong); }
        .ev-scale input:focus-visible + span { box-shadow: 0 0 0 3px rgba(99, 102, 241, .25); }
        .ev-scale label.in span { background: var(--sr-accent-soft); border-color: transparent; color: var(--sr-accent-strong); }
        .ev-scale input:checked + span { background: var(--sr-accent); border-color: var(--sr-accent); color: #fff; }
        .ev-crit[data-tone="amber"] .ev-scale input:checked + span { background: #f59e0b; border-color: #f59e0b; }
        .ev-crit[data-tone="red"] .ev-scale input:checked + span { background: #ef4444; border-color: #ef4444; }
        .ev-crit[data-tone="amber"] .ev-scale label.in span { background: var(--tone-amber-bg); color: var(--tone-amber-fg); }
        .ev-crit[data-tone="red"] .ev-scale label.in span { background: var(--tone-red-bg); color: var(--tone-red-fg); }
        @media (max-width: 991px) { .ev-crit { flex-wrap: wrap; } .ev-scale { width: 100%; justify-content: space-between; } .ev-scale span { width: 28px; } }

        .ev-emp { display: flex; align-items: center; gap: 12px; margin-top: 14px; padding: 12px 14px; border-radius: 12px; background: var(--sr-surface-2); border: 1px dashed var(--sr-border-strong); }
        .ev-emp .sr-avatar { width: 44px; height: 44px; font-size: 15px; }
        .ev-emp b { display: block; color: var(--sr-text); font-size: 14px; }
        .ev-emp small { display: block; color: var(--sr-muted); font-size: 12px; }

        .ev-ring { position: relative; width: 150px; height: 150px; margin: 6px auto 10px; }
        .ev-ring svg { transform: rotate(-90deg); }
        .ev-ring circle { fill: none; stroke-width: 12; }
        .ev-ring .bg { stroke: var(--sr-surface-3); }
        .ev-ring .fg { stroke: var(--sr-accent); stroke-linecap: round; transition: stroke-dashoffset .35s, stroke .2s; }
        .ev-ring-val { position: absolute; inset: 0; display: flex; flex-direction: column; align-items: center; justify-content: center; }
        .ev-ring-val b { font-size: 34px; font-weight: 800; color: var(--sr-text); line-height: 1; font-variant-numeric: tabular-nums; }
        .ev-ring-val span { font-size: 12px; color: var(--sr-muted); margin-top: 4px; }
        .ev-grade { text-align: center; margin-bottom: 14px; }
        .ev-quick { display: flex; gap: 6px; flex-wrap: wrap; justify-content: center; margin-bottom: 14px; }
        .sr-page .sr-card textarea.form-control { min-height: 110px; }
        .ev-prev-score { font-variant-numeric: tabular-nums; font-weight: 700; }
    </style>
    <script>window.lang = <?= json_encode($GLOBALS['translations'] ?? []) ?>;</script>
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

            <div class="content sr-page">
                <div class="container-fluid">

                    <div class="sr-head">
                        <div>
                            <h1><?= __('employee_performance_evaluation', 'Employee performance evaluation') ?></h1>
                            <p><?= __('evaluation_subtitle', 'Score your direct reports on 10 criteria (1-10). Each employee can be evaluated once a month.') ?></p>
                        </div>
                        <div class="sr-head-actions">
                            <a href="employee_evaluation_history.php" class="sr-btn"><i class="mdi mdi-history"></i> <?= __('evaluation_history', 'Evaluation history') ?></a>
                        </div>
                    </div>

                    <?php if ($error_message): ?>
                        <div class="sr-notice tone-red"><i class="mdi mdi-alert-circle"></i><div><strong><?= __('error') ?>:</strong> <?= $h($error_message) ?></div></div>
                    <?php endif; ?>

                    <form method="POST" action="" id="evaluationForm" novalidate>
                        <input type="hidden" name="submit_evaluation" value="1">
                        <div class="row">
                            <div class="col-xl-8">
                                <!-- Employee -->
                                <div class="sr-card">
                                    <div class="sr-card-head">
                                        <h5 class="sr-card-title"><i class="mdi mdi-account"></i> <?= __('new_employee_evaluation', 'New evaluation') ?></h5>
                                        <span class="sr-chip"><i class="mdi mdi-domain"></i><?= $h($dept_name) ?></span>
                                    </div>
                                    <div class="sr-card-body">
                                        <label class="sr-field-label" for="employee_emp_id"><?= __('select_employee') ?> <span class="text-danger">*</span></label>
                                        <select id="employee_emp_id" name="employee_emp_id" style="width: 100%;">
                                            <option value=""></option>
                                            <?php foreach ($dept_employees as $emp): ?>
                                                <option value="<?= $h($emp['emp_id']) ?>" data-position="<?= $h($emp['job'] ?? '') ?>" data-name="<?= $h($emp['name']) ?>"
                                                    <?= (($posted['employee_emp_id'] ?? '') == $emp['emp_id']) ? 'selected' : '' ?>>
                                                    <?= $h(getDisplayName($emp['name'])) ?> (<?= $h($emp['emp_id']) ?>)
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <?php if (empty($dept_employees)): ?>
                                            <div class="sr-notice tone-amber mt-2"><i class="mdi mdi-information"></i><div>
                                                <?= $total_subordinates > 0
                                                    ? __('no_employees_to_evaluate', 'All your direct reports have been evaluated this month.')
                                                    : __('no_direct_reports', 'No employees have you set as their direct supervisor.') ?>
                                            </div></div>
                                        <?php else: ?>
                                            <div class="sr-hint"><?= sprintf(__('employees_left_to_evaluate', '%d employee(s) left to evaluate this month.'), count($dept_employees)) ?></div>
                                        <?php endif; ?>
                                        <div class="ev-emp" id="evEmp" style="display: none;">
                                            <span class="sr-avatar" id="evEmpAvatar"></span>
                                            <div style="min-width: 0;">
                                                <b id="evEmpName"></b>
                                                <small><i class="mdi mdi-briefcase"></i> <span id="evEmpPos"></span> &middot; <span class="sr-mono" id="evEmpId"></span></small>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Criteria -->
                                <div class="sr-card">
                                    <div class="sr-card-head">
                                        <h5 class="sr-card-title"><i class="mdi mdi-star"></i> <?= __('evaluation_criteria_scale', 'Evaluation criteria (1-10)') ?></h5>
                                        <span class="sr-card-sub"><?= __('default') ?>: 10</span>
                                    </div>
                                    <?php $n = 0; foreach ($criteria as $key => $c): $n++;
                                        $val = (int)($posted[$key] ?? 10);
                                        if ($val < 1 || $val > 10) $val = 10;
                                    ?>
                                        <div class="ev-crit" data-key="<?= $key ?>">
                                            <span class="ev-crit-no"><i class="mdi <?= $c[2] ?>"></i></span>
                                            <div class="ev-crit-text">
                                                <b><?= $n ?>. <?= $h($c[0]) ?></b>
                                                <small dir="rtl" style="text-align: start;"><?= $h($c[1]) ?></small>
                                            </div>
                                            <div class="ev-scale" role="radiogroup" aria-label="<?= $h($c[0]) ?>">
                                                <?php for ($i = 1; $i <= 10; $i++): ?>
                                                    <label><input type="radio" name="<?= $key ?>" value="<?= $i ?>" class="evaluation-score" <?= $i === $val ? 'checked' : '' ?>><span><?= $i ?></span></label>
                                                <?php endfor; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>

                                <!-- Remarks -->
                                <div class="sr-card">
                                    <div class="sr-card-head"><h5 class="sr-card-title"><i class="mdi mdi-comment-text"></i> <?= __('remarks_observations', 'Remarks / observations') ?></h5></div>
                                    <div class="sr-card-body">
                                        <textarea class="form-control" id="observation" name="observation" rows="4" placeholder="<?= $h(__('enter_any_additional_remarks_or_observations_about_the_employees_performance')) ?>"><?= $h($posted['observation'] ?? '') ?></textarea>
                                    </div>
                                </div>
                            </div>

                            <!-- Score summary -->
                            <div class="col-xl-4">
                                <div class="sr-sticky">
                                    <div class="sr-card sr-action-card">
                                        <div class="sr-card-head"><h5 class="sr-card-title"><i class="mdi mdi-chart-donut"></i> <?= __('total_score', 'Total score') ?> <small class="text-muted">(مجموع النقاط)</small></h5></div>
                                        <div class="sr-card-body">
                                            <div class="ev-ring">
                                                <svg width="150" height="150" viewBox="0 0 150 150"><circle class="bg" cx="75" cy="75" r="62"></circle><circle class="fg" id="evRing" cx="75" cy="75" r="62"></circle></svg>
                                                <div class="ev-ring-val"><b id="totalScore">100</b><span>/ 100 &middot; <span id="scorePercentage">100%</span></span></div>
                                            </div>
                                            <div class="ev-grade"><span class="sr-pill sr-pill-lg tone-green" id="evGrade"><span class="sr-dot"></span><span></span></span></div>
                                            <div class="ev-quick">
                                                <?php foreach ([10, 8, 6] as $q): ?>
                                                    <button type="button" class="sr-btn sr-btn-sm js-all" data-v="<?= $q ?>"><?= __('set_all', 'All') ?> <?= $q ?></button>
                                                <?php endforeach; ?>
                                            </div>
                                            <dl class="sr-kv">
                                                <div class="row-kv"><dt><?= __('lowest_score', 'Lowest') ?></dt><dd id="evLow">10</dd></div>
                                                <div class="row-kv"><dt><?= __('average', 'Average') ?></dt><dd id="evAvg">10.0</dd></div>
                                                <div class="row-kv"><dt><?= __('below_6', 'Criteria below 6') ?></dt><dd id="evWeak">0</dd></div>
                                            </dl>
                                            <button type="submit" class="sr-btn sr-btn-primary sr-btn-block mt-3" id="evSubmit" <?= empty($dept_employees) ? 'disabled' : '' ?>><i class="mdi mdi-check"></i> <?= __('submit_evaluation', 'Submit evaluation') ?></button>
                                            <a href="dashboard.php" class="sr-btn sr-btn-ghost sr-btn-block mt-2"><?= __('cancel') ?></a>
                                        </div>
                                    </div>

                                    <!-- Previous evaluations -->
                                    <div class="sr-card" id="previousEvaluationsSection" style="display: none;">
                                        <div class="sr-card-head"><h5 class="sr-card-title"><i class="mdi mdi-history"></i> <?= __('previous_evaluations', 'Previous evaluations') ?></h5></div>
                                        <div class="sr-card-body" style="padding-top: 6px;">
                                            <dl class="sr-kv" id="previousEvaluationsBody"></dl>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>

                </div>
            </div>

            <footer class="footer">
                <?= $site_footer ?>
            </footer>
        </div>
    </div>

    <script src="assets/js/jquery.min.js"></script>
    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/metisMenu.min.js"></script>
    <script src="assets/js/waves.js"></script>
    <script src="assets/js/jquery.slimscroll.js"></script>
    <script src="./plugins/select2/js/select2.min.js"></script>
    <script src="assets/js/jquery.core.js"></script>
    <script src="assets/js/jquery.app.js?t=<?= time() ?>"></script>

    <script>
    $(document).ready(function() {
        const esc = s => $('<div>').text(s == null ? '' : String(s)).html();
        const T = {
            selectEmployee: <?= json_encode(__('select_employee')) ?>,
            excellent: <?= json_encode(__('excellent', 'Excellent')) ?>,
            good: <?= json_encode(__('good', 'Good')) ?>,
            fair: <?= json_encode(__('fair', 'Needs improvement')) ?>,
            poor: <?= json_encode(__('poor', 'Poor')) ?>,
            confirmTitle: <?= json_encode(__('submit_evaluation', 'Submit evaluation')) ?>,
            confirmText: <?= json_encode(__('evaluation_confirm_text', 'Submit this evaluation for %s with a total of %d / 100? It cannot be edited afterwards.')) ?>,
            yes: <?= json_encode(__('yes_submit', 'Yes, submit')) ?>,
            cancel: <?= json_encode(__('cancel')) ?>,
            none: <?= json_encode(__('no_previous_evaluations', 'No previous evaluations.')) ?>
        };
        const RING = 2 * Math.PI * 62;
        $('#evRing').attr({ 'stroke-dasharray': RING, 'stroke-dashoffset': 0 });

        $('#employee_emp_id').select2({ placeholder: T.selectEmployee, allowClear: true, width: '100%' });

        function scores() { return $('.ev-crit').map(function() { return parseInt($(this).find('input:checked').val(), 10) || 0; }).get(); }

        function paint() {
            $('.ev-crit').each(function() {
                const v = parseInt($(this).find('input:checked').val(), 10) || 0;
                $(this).attr('data-tone', v <= 4 ? 'red' : (v <= 6 ? 'amber' : ''));
                $(this).find('label').each(function(i) { $(this).toggleClass('in', i + 1 < v); });
            });
            const s = scores(), total = s.reduce((a, b) => a + b, 0);
            $('#totalScore').text(total);
            $('#scorePercentage').text(total + '%');
            $('#evRing').attr('stroke-dashoffset', RING * (1 - total / 100));
            let tone = 'green', label = T.excellent, color = '#22c55e';
            if (total < 50) { tone = 'red'; label = T.poor; color = '#ef4444'; }
            else if (total < 70) { tone = 'amber'; label = T.fair; color = '#f59e0b'; }
            else if (total < 90) { tone = 'indigo'; label = T.good; color = '#6366f1'; }
            $('#evRing').css('stroke', color);
            $('#evGrade').attr('class', 'sr-pill sr-pill-lg tone-' + tone).find('span:last').text(label);
            $('#evLow').text(Math.min.apply(null, s));
            $('#evAvg').text((total / s.length).toFixed(1));
            $('#evWeak').text(s.filter(v => v < 6).length);
        }
        $(document).on('change', '.evaluation-score', paint);
        $('.js-all').on('click', function() {
            const v = $(this).data('v');
            $('.ev-crit').each(function() { $(this).find('input[value="' + v + '"]').prop('checked', true); });
            paint();
        });

        function loadPrevious(empId) {
            const $box = $('#previousEvaluationsSection'), $body = $('#previousEvaluationsBody');
            $.post('includes/ajaxFile/ajaxEvaluation.php', { action: 'get_previous_evaluations', employee_emp_id: empId }, null, 'json')
                .done(function(res) {
                    const rows = (res && res.status === 'success' && res.data) || [];
                    $body.html(rows.length ? rows.map(function(ev) {
                        const sc = parseInt(ev.total_score, 10) || 0;
                        const tone = sc >= 90 ? 'green' : sc >= 70 ? 'indigo' : sc >= 50 ? 'amber' : 'red';
                        return `<div class="row-kv"><dt>${esc(ev.created_at)}<br><small>${esc(ev.manager_name || '')}</small>${ev.observation ? `<br><small title="${esc(ev.observation)}">${esc(ev.observation.length > 50 ? ev.observation.slice(0, 50) + '…' : ev.observation)}</small>` : ''}</dt>
                            <dd><span class="sr-pill sr-pill-xs tone-${tone} ev-prev-score">${sc}/100</span></dd></div>`;
                    }).join('') : `<div class="sr-hint">${esc(T.none)}</div>`);
                    $box.show();
                })
                .fail(function() { $box.hide(); });
        }

        $('#employee_emp_id').on('change', function() {
            const $o = $(this).find('option:selected'), id = $(this).val();
            if (!id) { $('#evEmp, #previousEvaluationsSection').hide(); return; }
            const name = String($o.data('name') || '');
            $('#evEmpAvatar').text(name.trim().split(/\s+/).slice(0, 2).map(w => w[0]).join('').toUpperCase());
            $('#evEmpName').text(name);
            $('#evEmpPos').text($o.data('position') || 'N/A');
            $('#evEmpId').text(id);
            $('#evEmp').show();
            $(this).next('.select2-container').find('.select2-selection').css('border-color', '');
            loadPrevious(id);
        }).trigger('change');

        $('#evaluationForm').on('submit', function(e) {
            const form = this, $sel = $('#employee_emp_id');
            if (form.dataset.ok) return true;
            e.preventDefault();
            if (!$sel.val()) {
                $sel.next('.select2-container').find('.select2-selection').css('border-color', '#dc2626');
                $sel.select2('open');
                return false;
            }
            const total = scores().reduce((a, b) => a + b, 0);
            Swal.fire({
                title: T.confirmTitle,
                text: T.confirmText.replace('%s', $sel.find('option:selected').data('name')).replace('%d', total),
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: T.yes,
                cancelButtonText: T.cancel,
                confirmButtonColor: window.APP_COLORS && APP_COLORS.primary,
                cancelButtonColor: window.APP_COLORS && APP_COLORS.danger_dark,
                allowOutsideClick: false
            }).then(function(r) {
                if (!r.isConfirmed) return;
                form.dataset.ok = '1';
                $('#evSubmit').prop('disabled', true).html('<i class="mdi mdi-loading mdi-spin"></i>');
                form.submit();
            });
        });

        paint();

        <?php if ($swal): ?>
        setTimeout(function() {
            if (typeof Swal === 'undefined') return;
            Swal.fire({
                title: <?= json_encode((string)$swal['title']) ?>,
                text: <?= json_encode((string)$swal['message']) ?>,
                icon: <?= json_encode((string)$swal['type']) ?>,
                confirmButtonText: <?= json_encode(__('ok')) ?>,
                allowOutsideClick: false
            });
        }, 500);
        <?php endif; ?>
    });
    </script>
</body>
</html>
