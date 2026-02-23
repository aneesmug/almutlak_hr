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
if (!$isDeptManager) {
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

<!DOCTYPE html>
<html lang="<?= $current_lang ?? 'en' ?>" <?= ($is_rtl ?? false) ? 'dir="rtl"' : '' ?>>

<head>
    <meta charset="utf-8" />
    <title><?= $site_title ?> - Employee Evaluation</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta content="Al-Mutlak HR System" name="description" />
    <meta content="Anees Afzal" name="author" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />

    <!-- App favicon -->
    <link rel="shortcut icon" href="<?=get_setting($conDB, 'favicon')?>">

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
                                <h4 class="page-title"><?=__('employee_performance_evaluation') ?></h4>
                            </div>
                        </div>
                    </div>

                    <!-- Success/Error Messages -->
                    <?php if ($success_message): ?>
                    <div class="row">
                        <div class="col-12">
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                                <strong><?=__('success') ?>!</strong> <?=htmlspecialchars($success_message); ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ($error_message): ?>
                    <div class="row">
                        <div class="col-12">
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                                <strong><?=__('error') ?>!</strong> <?=htmlspecialchars($error_message); ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Evaluation Form -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body">
                                    <h4 class="header-title mb-4"><?=__('new_employee_evaluation') ?></h4>
                                    
                                    <form method="POST" action="" id="evaluationForm">
                                        
                                        <!-- Department and Employee Selection -->
                                        <div class="row mb-4">
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label for="dept_name"><?=__('department') ?> <span class="text-danger">*</span></label>
                                                    <input type="text" class="form-control" id="dept_name" value="<?=($dept_name); ?>" readonly>
                                                </div>
                                            </div>
                                            
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label for="employee_emp_id"><?=__('select_employee') ?> <span class="text-danger">*</span></label>
                                                    <select class="form-control select2" id="employee_emp_id" name="employee_emp_id" required>
                                                        <option value="">-- <?=__('select_employee') ?> --</option>
                                                        <?php foreach ($dept_employees as $emp): ?>
                                                            <option value="<?=($emp['emp_id']); ?>" 
                                                                    data-position="<?=($emp['job'] ?? 'N/A'); ?>"
                                                                    data-name="<?=($emp['name']); ?>">
                                                                <?=getDisplayName($emp['name']); ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                            </div>
                                            
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label for="employee_name"><?=__('employee_name') ?></label>
                                                    <input type="text" class="form-control" id="employee_name" readonly>
                                                </div>
                                            </div>

                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label for="employee_position"><?=__('employee_position') ?></label>
                                                    <input type="text" class="form-control" id="employee_position" readonly>
                                                </div>
                                            </div>

                                        </div>

                                        <hr>

                                        <!-- Evaluation Criteria -->
                                        <h5 class="mb-3"><?=__('evaluation_criteria_scale') ?></h5>
                                        <div class="table-responsive">
                                            <table class="table table-bordered table-striped">
                                                <thead class="thead-light">
                                                    <tr>
                                                        <th width="5%">#</th>
                                                        <th width="50%"><?=__('criteria_english_arabic') ?></th>
                                                        <th width="30%"><?=__('score_1_10') ?></th>
                                                        <th width="15%"><?=__('default') ?></th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr>
                                                        <td>1</td>
                                                        <td>
                                                            <strong>Punctuality Attendance</strong><br>
                                                            <small class="text-muted">الإنتظام وعدم التأخير</small>
                                                        </td>
                                                        <td>
                                                            <select class="form-control evaluation-score" name="punctuality" required>
                                                                <?php for ($i = 10; $i >= 1; $i--): ?>
                                                                    <option value="<?php echo $i; ?>" <?php echo $i == 10 ? 'selected' : ''; ?>><?php echo $i; ?></option>
                                                                <?php endfor; ?>
                                                            </select>
                                                        </td>
                                                        <td class="text-center"><span class="badge badge-success">10</span></td>
                                                    </tr>
                                                    
                                                    <tr>
                                                        <td>2</td>
                                                        <td>
                                                            <strong>Achieving at the specified time</strong><br>
                                                            <small class="text-muted">التحقيق في الوقت المحدد</small>
                                                        </td>
                                                        <td>
                                                            <select class="form-control evaluation-score" name="achieving_time" required>
                                                                <?php for ($i = 10; $i >= 1; $i--): ?>
                                                                    <option value="<?php echo $i; ?>" <?php echo $i == 10 ? 'selected' : ''; ?>><?php echo $i; ?></option>
                                                                <?php endfor; ?>
                                                            </select>
                                                        </td>
                                                        <td class="text-center"><span class="badge badge-success">10</span></td>
                                                    </tr>
                                                    
                                                    <tr>
                                                        <td>3</td>
                                                        <td>
                                                            <strong>Knowledge of job</strong><br>
                                                            <small class="text-muted">معرفة الوظيفة</small>
                                                        </td>
                                                        <td>
                                                            <select class="form-control evaluation-score" name="job_knowledge" required>
                                                                <?php for ($i = 10; $i >= 1; $i--): ?>
                                                                    <option value="<?php echo $i; ?>" <?php echo $i == 10 ? 'selected' : ''; ?>><?php echo $i; ?></option>
                                                                <?php endfor; ?>
                                                            </select>
                                                        </td>
                                                        <td class="text-center"><span class="badge badge-success">10</span></td>
                                                    </tr>
                                                    
                                                    <tr>
                                                        <td>4</td>
                                                        <td>
                                                            <strong>The Ability to solve problems</strong><br>
                                                            <small class="text-muted">القدرة على حل المشاكل</small>
                                                        </td>
                                                        <td>
                                                            <select class="form-control evaluation-score" name="problem_solving" required>
                                                                <?php for ($i = 10; $i >= 1; $i--): ?>
                                                                    <option value="<?php echo $i; ?>" <?php echo $i == 10 ? 'selected' : ''; ?>><?php echo $i; ?></option>
                                                                <?php endfor; ?>
                                                            </select>
                                                        </td>
                                                        <td class="text-center"><span class="badge badge-success">10</span></td>
                                                    </tr>
                                                    
                                                    <tr>
                                                        <td>5</td>
                                                        <td>
                                                            <strong>Receptiveness to Feedback and Instructions</strong><br>
                                                            <small class="text-muted">تقبل التوجيهات والتعليمات</small>
                                                        </td>
                                                        <td>
                                                            <select class="form-control evaluation-score" name="feedback_receptiveness" required>
                                                                <?php for ($i = 10; $i >= 1; $i--): ?>
                                                                    <option value="<?php echo $i; ?>" <?php echo $i == 10 ? 'selected' : ''; ?>><?php echo $i; ?></option>
                                                                <?php endfor; ?>
                                                            </select>
                                                        </td>
                                                        <td class="text-center"><span class="badge badge-success">10</span></td>
                                                    </tr>
                                                    
                                                    <tr>
                                                        <td>6</td>
                                                        <td>
                                                            <strong>Self & Professional Development</strong><br>
                                                            <small class="text-muted">السعي لتطوير المهارات والمعرفة وتحسين الأداء بإستمرار</small>
                                                        </td>
                                                        <td>
                                                            <select class="form-control evaluation-score" name="self_development" required>
                                                                <?php for ($i = 10; $i >= 1; $i--): ?>
                                                                    <option value="<?php echo $i; ?>" <?php echo $i == 10 ? 'selected' : ''; ?>><?php echo $i; ?></option>
                                                                <?php endfor; ?>
                                                            </select>
                                                        </td>
                                                        <td class="text-center"><span class="badge badge-success">10</span></td>
                                                    </tr>
                                                    
                                                    <tr>
                                                        <td>7</td>
                                                        <td>
                                                            <strong>Work under pressure</strong><br>
                                                            <small class="text-muted">العمل تحت الضغط</small>
                                                        </td>
                                                        <td>
                                                            <select class="form-control evaluation-score" name="work_under_pressure" required>
                                                                <?php for ($i = 10; $i >= 1; $i--): ?>
                                                                    <option value="<?php echo $i; ?>" <?php echo $i == 10 ? 'selected' : ''; ?>><?php echo $i; ?></option>
                                                                <?php endfor; ?>
                                                            </select>
                                                        </td>
                                                        <td class="text-center"><span class="badge badge-success">10</span></td>
                                                    </tr>
                                                    
                                                    <tr>
                                                        <td>8</td>
                                                        <td>
                                                            <strong>Communication skills and Teamwork</strong><br>
                                                            <small class="text-muted">مهارات التواصل والعمل الجماعي</small>
                                                        </td>
                                                        <td>
                                                            <select class="form-control evaluation-score" name="communication_teamwork" required>
                                                                <?php for ($i = 10; $i >= 1; $i--): ?>
                                                                    <option value="<?php echo $i; ?>" <?php echo $i == 10 ? 'selected' : ''; ?>><?php echo $i; ?></option>
                                                                <?php endfor; ?>
                                                            </select>
                                                        </td>
                                                        <td class="text-center"><span class="badge badge-success">10</span></td>
                                                    </tr>
                                                    
                                                    <tr>
                                                        <td>9</td>
                                                        <td>
                                                            <strong>Creativity and speed of response</strong><br>
                                                            <small class="text-muted">الإبداع وسرعة الإستجابة</small>
                                                        </td>
                                                        <td>
                                                            <select class="form-control evaluation-score" name="creativity_response" required>
                                                                <?php for ($i = 10; $i >= 1; $i--): ?>
                                                                    <option value="<?php echo $i; ?>" <?php echo $i == 10 ? 'selected' : ''; ?>><?php echo $i; ?></option>
                                                                <?php endfor; ?>
                                                            </select>
                                                        </td>
                                                        <td class="text-center"><span class="badge badge-success">10</span></td>
                                                    </tr>
                                                    
                                                    <tr>
                                                        <td>10</td>
                                                        <td>
                                                            <strong>Initiative and cooperation</strong><br>
                                                            <small class="text-muted">المبادرة والتعاون</small>
                                                        </td>
                                                        <td>
                                                            <select class="form-control evaluation-score" name="initiative_cooperation" required>
                                                                <?php for ($i = 10; $i >= 1; $i--): ?>
                                                                    <option value="<?php echo $i; ?>" <?php echo $i == 10 ? 'selected' : ''; ?>><?php echo $i; ?></option>
                                                                <?php endfor; ?>
                                                            </select>
                                                        </td>
                                                        <td class="text-center"><span class="badge badge-success">10</span></td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>

                                        <!-- Observation/Remarks -->
                                        <div class="row mt-4">
                                            <div class="col-md-12">
                                                <div class="form-group">
                                                    <label for="observation"><?=__('remarks_observations') ?></label>
                                                    <textarea class="form-control" id="observation" name="observation" rows="4" placeholder="<?=__('enter_any_additional_remarks_or_observations_about_the_employees_performance') ?>"></textarea>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Total Score Display -->
                                        <div class="row mt-4">
                                            <div class="col-md-12">
                                                <div class="alert alert-info">
                                                    <h5 class="mb-0">
                                                        Total Score (مجموع النقاط): 
                                                        <strong id="totalScore">100</strong> / 100
                                                        <span id="scorePercentage" class="ml-3">(100%)</span>
                                                    </h5>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Submit Button -->
                                        <div class="row mt-4">
                                            <div class="col-md-12">
                                                <button type="submit" name="submit_evaluation" class="btn btn-primary btn-lg">
                                                    <i class="fa fa-check"></i> <?=__('submit_evaluation') ?>
                                                </button>
                                                <a href="dashboard.php" class="btn btn-secondary btn-lg ml-2">
                                                    <i class="fa fa-times"></i> <?=__('cancel') ?>
                                                </a>
                                            </div>
                                        </div>

                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Previous Evaluations Section -->
                    <div class="row" id="previousEvaluationsSection" style="display: none;">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body">
                                    <h4 class="header-title mb-4">Previous Evaluations for <span id="prevEvalEmployeeName"></span></h4>
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-hover" id="previousEvaluationsTable">
                                            <thead class="thead-light">
                                                <tr>
                                                    <th>Date</th>
                                                    <th>Manager</th>
                                                    <th>Total Score</th>
                                                    <th>Percentage</th>
                                                    <th>Remarks</th>
                                                </tr>
                                            </thead>
                                            <tbody id="previousEvaluationsBody">
                                                <!-- Populated via AJAX -->
                                            </tbody>
                                        </table>
                                    </div>
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

    <!-- jQuery  -->
    <script src="assets/js/jquery.min.js"></script>
    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/metisMenu.min.js"></script>
    <script src="assets/js/waves.js"></script>
    <script src="assets/js/jquery.slimscroll.js"></script>

    <!-- Select2 -->
    <script src="./plugins/select2/js/select2.min.js"></script>

    <!-- App js -->
    <script src="assets/js/jquery.core.js"></script>
    <script src="assets/js/jquery.app.js?t=<?= time() ?>"></script>

    <!-- JavaScript -->
    <script>
    $(document).ready(function() {
        
        // Initialize Select2
        $('.select2').select2({
            placeholder: "-- Select Employee --",
            allowClear: true
        });

        // Update employee name and position when selection changes
        $('#employee_emp_id').on('change', function() {
            var selectedOption = $(this).find('option:selected');
            var employeeName = selectedOption.data('name') || '';
            var position = selectedOption.data('position') || 'N/A';
            
            $('#employee_name').val(employeeName);
            $('#employee_position').val(position);

            // Load previous evaluations if employee selected
            if ($(this).val()) {
                loadPreviousEvaluations($(this).val(), employeeName);
            } else {
                $('#previousEvaluationsSection').hide();
            }
        });

        // Calculate total score on any score change
        $('.evaluation-score').on('change', function() {
            calculateTotalScore();
        });

        // Function to calculate total score
        function calculateTotalScore() {
            var total = 0;
            $('.evaluation-score').each(function() {
                total += parseInt($(this).val()) || 0;
            });
            
            $('#totalScore').text(total);
            
            var percentage = (total / 100 * 100).toFixed(0);
            $('#scorePercentage').text('(' + percentage + '%)');
            
            // Update alert color based on score
            var alertBox = $('#totalScore').closest('.alert');
            alertBox.removeClass('alert-success alert-warning alert-danger alert-info');
            
            if (total >= 90) {
                alertBox.addClass('alert-success');
            } else if (total >= 70) {
                alertBox.addClass('alert-info');
            } else if (total >= 50) {
                alertBox.addClass('alert-warning');
            } else {
                alertBox.addClass('alert-danger');
            }
        }

        // Function to load previous evaluations
        function loadPreviousEvaluations(empId, empName) {
            $.ajax({
                url: 'includes/ajaxFile/ajaxEvaluation.php',
                method: 'POST',
                data: { 
                    action: 'get_previous_evaluations', 
                    employee_emp_id: empId 
                },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success' && response.data.length > 0) {
                        var tbody = $('#previousEvaluationsBody');
                        tbody.empty();
                        
                        $('#prevEvalEmployeeName').text(empName);
                        
                        $.each(response.data, function(index, eval) {
                            var percentage = (eval.total_score / 100 * 100).toFixed(0);
                            var badgeClass = eval.total_score >= 90 ? 'success' : 
                                           eval.total_score >= 70 ? 'info' : 
                                           eval.total_score >= 50 ? 'warning' : 'danger';
                            
                            var row = '<tr>' +
                                '<td>' + eval.created_at + '</td>' +
                                '<td>' + eval.manager_name + '</td>' +
                                '<td><span class="badge badge-' + badgeClass + ' badge-pill">' + eval.total_score + '/100</span></td>' +
                                '<td>' + percentage + '%</td>' +
                                '<td>' + (eval.observation || 'N/A') + '</td>' +
                                '</tr>';
                            
                            tbody.append(row);
                        });
                        
                        $('#previousEvaluationsSection').show();
                    } else {
                        $('#previousEvaluationsSection').hide();
                    }
                },
                error: function() {
                    console.log('Error loading previous evaluations');
                }
            });
        }

        // Initial calculation
        calculateTotalScore();
        
        // Check for SweetAlert message from session (after page load)
        <?php if (isset($_SESSION['swal_alert'])): ?>
        // Wait for SweetAlert2 to be loaded
        setTimeout(function() {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: '<?= addslashes($_SESSION['swal_alert']['title']) ?>',
                    text: '<?= addslashes($_SESSION['swal_alert']['message']) ?>',
                    icon: '<?= $_SESSION['swal_alert']['type'] ?>',
                    confirmButtonText: '<?= __("ok") ?>',
                    customClass: {
                        confirmButton: 'btn btn-primary'
                    },
                    buttonsStyling: false,
                    allowOutsideClick: false
                });
            }
        }, 500);
        <?php unset($_SESSION['swal_alert']); ?>
        <?php endif; ?>
    });

    </script>

</body>
</html>
