<?php
/**
 * Employee Evaluation History Page
 * Displays all evaluations for a specific employee
 */

require_once("./includes/session_check.php");
include('./includes/MainClass.php');
include("./includes/Hijri_GregorianConvert.php");
require_once './includes/evaluation_acknowledgment_handler.php';

$DateConv = new Hijri_GregorianConvert;
$format = "YYYY-MM-DD";

// Get employee ID securely from session
$emp_id = $_SESSION['empid'] ?? ($empid ?? null);
if (empty($emp_id)) {
    header("Location: ./profile.php");
    exit();
}

// Fetch employee data
$emp_query = "SELECT e.* FROM employees e WHERE e.emp_id = ?";
$stmt = $pdo->prepare($emp_query);
$stmt->execute([$emp_id]);
$emprow = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$emprow) {
    die("Employee not found.");
}

// Fetch evaluation history
$eval_query = $pdo->prepare("
    SELECT 
        ev.id,
        ev.manager_emp_id,
        em.name AS manager_name,
        de.dep_nme,
        de.dep_nme_ar,
        ev.total_score,
        ev.observation,
        ev.manager_acknowledgment_status,
        ev.manager_objection_note,
        ev.manager_acknowledged_by,
        DATE_FORMAT(ev.created_at, '%Y-%m-%d %H:%i') AS eval_date,
        DATE_FORMAT(ev.manager_acknowledgment_date, '%Y-%m-%d %H:%i') AS acknowledgment_date,
        ack_em.name AS acknowledged_by_name
    FROM emp_evaluations ev
    LEFT JOIN employees em ON ev.manager_emp_id = em.emp_id
    LEFT JOIN employees ack_em ON ev.manager_acknowledged_by = ack_em.emp_id
    LEFT JOIN department de ON em.dept = de.id
    WHERE ev.employee_emp_id = ?
    ORDER BY ev.created_at DESC
");
$eval_query->execute([$emprow['emp_id']]);
$evaluations = $eval_query->fetchAll(PDO::FETCH_ASSOC);
?>

<!doctype html>
<html lang="<?= $current_lang ?? 'en' ?>" <?= ($is_rtl ?? false) ? 'dir="rtl"' : '' ?>>
<head>
    <meta charset="utf-8" />
    <title><?= $site_title ?> - Evaluation History</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="shortcut icon" href="<?=get_setting($conDB, 'favicon')?>">
    <link href="./plugins/datatables/dataTables.bootstrap4.min.css" rel="stylesheet" type="text/css" />
    <link href="./plugins/datatables/buttons.bootstrap4.min.css" rel="stylesheet" type="text/css" />
    <link href="./assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
    <link href="./assets/css/metisMenu.min.css" rel="stylesheet" type="text/css" />
    <link href="./assets/css/icons.min.css" rel="stylesheet" type="text/css" />
    <link href="./assets/css/style.css" rel="stylesheet" type="text/css" />
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #007bff;
            --secondary: #6c757d;
            --success: #28a745;
            --danger: #dc3545;
            --warning: #ffc107;
            --info: #17a2b8;
            --light: #f8f9fa;
            --dark: #343a40;
            --white: #ffffff;
            --shadow-sm: 0 1px 3px rgba(0, 0, 0, 0.12);
            --shadow-md: 0 2px 8px rgba(0, 0, 0, 0.15);
            --shadow-lg: 0 4px 16px rgba(0, 0, 0, 0.2);
        }

        body.authentication-bg-pattern {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
        }

        .profile-container { max-width: 1400px; margin: 0 auto; padding: 20px; }

        .profile-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--info) 100%);
            border-radius: 16px;
            padding: 28px;
            color: white;
            margin: 20px auto 24px;
            box-shadow: var(--shadow-lg);
            position: relative;
            overflow: hidden;
        }

        .profile-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -10%;
            width: 420px;
            height: 420px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
        }

        .profile-header .container-custom {
            display: grid;
            grid-template-columns: auto 1fr auto;
            gap: 24px;
            align-items: center;
            position: relative;
            z-index: 1;
        }

        .profile-avatar {
            width: 90px;
            height: 90px;
            border-radius: 50%;
            border: 4px solid rgba(255, 255, 255, 0.3);
            object-fit: cover;
            background: rgba(255, 255, 255, 0.1);
        }

        .profile-header-info h1 { font-size: 24px; font-weight: 700; margin-bottom: 4px; }
        .profile-header-info p { font-size: 13px; opacity: 0.95; margin: 2px 0; }

        .qr-code { width: 100px; height: 100px; }

        .card { border: none; border-radius: 12px; box-shadow: var(--shadow-md); }
        .card .card-body { padding: 24px; }

        .section-title { font-size: 20px; font-weight: 700; color: var(--dark); margin-bottom: 16px; display: flex; align-items: center; gap: 10px; }
        .section-title i { font-size: 22px; color: var(--primary); }

        .score-badge {
            font-size: 16px;
            padding: 6px 12px;
            border-radius: 6px;
            font-weight: 600;
        }

        @media (max-width: 768px) {
            .profile-header { padding: 20px; }
            .profile-header .container-custom { grid-template-columns: auto 1fr; gap: 16px; }
            .qr-code { width: 80px; height: 80px; }
            .profile-avatar { width: 70px; height: 70px; }
        }
        @media (max-width: 480px) {
            .profile-header .container-custom { grid-template-columns: 1fr; text-align: center; }
            .qr-code { justify-self: center; }
        }
    </style>
    <?php if ($is_rtl): ?>
        <link href="assets/css/style_rtl.css" rel="stylesheet" type="text/css" />
    <?php endif; ?>
    <script>
        window.lang = <?= json_encode($GLOBALS['translations'] ?? []) ?>;
    </script>
</head>
<body class="authentication-bg-pattern">
    <?php include('./includes/profile_employee_header.php'); ?>
    <div class="account-pages" style="max-width: 1400px; margin: 20px auto; padding: 0 20px;">
        <div class="container-fluid" style="max-width: none;">
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body">
                            <h4 class="section-title">
                                <i class="fa fa-star"></i> <?= __('evaluation_history', 'Evaluation History') ?>
                            </h4>
                            
                            <div class="table-responsive">
                                <table class="table table-hover mb-0 datatable">
                                    <thead>
                                        <tr>
                                            <th><?= __('id', 'ID') ?></th>
                                            <th><?= __('evaluation_date', 'Evaluation Date') ?></th>
                                            <th><?= __('evaluated_by', 'Evaluated By') ?></th>
                                            <th><?= __('department', 'Department') ?></th>
                                            <th><?= __('total_score', 'Total Score') ?></th>
                                            <th><?= __('acknowledgment_status', 'Status') ?></th>
                                            <th><?= __('action', 'Action') ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                            if (count($evaluations) > 0):
                                                foreach ($evaluations as $eval):
                                                    $score_class = 'success';
                                                    if ($eval['total_score'] < 50) $score_class = 'danger';
                                                    elseif ($eval['total_score'] < 70) $score_class = 'warning';
                                                    elseif ($eval['total_score'] < 90) $score_class = 'info';
                                        ?>
                                            <tr>
                                                <td><?= htmlspecialchars($eval['id']) ?></td>
                                                <td>
                                                    <?= htmlspecialchars($eval['eval_date']) ?>
                                                </td>
                                                <td><?= translate_name($eval['manager_name'], $current_lang ?? 'en') ?></td>
                                                <td><?= ($is_rtl ?? false ? $eval['dep_nme_ar'] : $eval['dep_nme']) ?></td>
                                                <td>
                                                    <span class="badge badge-<?= $score_class ?> score-badge">
                                                        <?= htmlspecialchars($eval['total_score']) ?>/100
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if ($eval['manager_acknowledgment_status'] === 'pending'): ?>
                                                        <span class="badge badge-warning" style="padding: 8px 12px;">
                                                            <i class="mdi mdi-clock-outline"></i> <?= __('pending', 'Pending') ?>
                                                        </span>
                                                    <?php elseif ($eval['manager_acknowledgment_status'] === 'acknowledged'): ?>
                                                        <span class="badge badge-success" style="padding: 8px 12px;">
                                                            <i class="mdi mdi-check-circle"></i> <?= __('acknowledged', 'Acknowledged') ?>
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="badge badge-danger" style="padding: 8px 12px;">
                                                            <i class="mdi mdi-close-circle"></i> <?= __('objected', 'Objected') ?>
                                                        </span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div class="btn-group" role="group">
                                                        <button class="btn btn-sm btn-primary view-eval-details" 
                                                            data-id="<?= $eval['id'] ?>">
                                                            <i class="mdi mdi-eye"></i> <?= __('view_details', 'View Details') ?>
                                                        </button>
                                                        <?php if ($eval['manager_acknowledgment_status'] === 'pending'): ?>
                                                            <button class="btn btn-sm btn-success acknowledge-eval" 
                                                                data-id="<?= $eval['id'] ?>"
                                                                data-manager-name="<?= htmlspecialchars($eval['manager_name']) ?>"
                                                                title="<?= __('acknowledge_evaluation', 'Acknowledge Evaluation') ?>">
                                                                <i class="mdi mdi-check-circle"></i> <?= __('acknowledge', 'Acknowledge') ?>
                                                            </button>
                                                            <button class="btn btn-sm btn-warning object-eval" 
                                                                data-id="<?= $eval['id'] ?>"
                                                                data-manager-name="<?= htmlspecialchars($eval['manager_name']) ?>"
                                                                title="<?= __('object_to_evaluation', 'Object to Evaluation') ?>">
                                                                <i class="mdi mdi-close-circle"></i> <?= __('object', 'Object') ?>
                                                            </button>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php 
                                                endforeach;
                                            endif;
                                        ?>
                                    </tbody>
                                </table>
                            </div>

                            <?php if (count($evaluations) == 0): ?>
                                <div class="alert alert-info mt-3">
                                    <i class="fa fa-info-circle"></i> <?= __('no_evaluation_history_found', 'No evaluation history found.') ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="./assets/js/jquery.min.js"></script>
    <script src="./assets/js/bootstrap.bundle.min.js"></script>
    <script src="./plugins/datatables/jquery.dataTables.min.js"></script>
    <script src="./plugins/datatables/dataTables.bootstrap4.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="assets/js/employee_profile.js"></script>
    <script>
        $(document).ready(function() {
            $('.datatable').DataTable({
                order: [[1, 'desc']],
                pageLength: 10,
                language: {
                    search: `<span>${__('search')}:</span> _INPUT_`,
                    searchPlaceholder: `${__('search')}...`,
                    lengthMenu: `${__('show')} _MENU_ ${__('entries')}`,
                    info: `${__('showing')} _START_ ${__('to')} _END_ ${__('of')} _TOTAL_ ${__('entries')}`,
                    infoEmpty: `${__('showing')} 0 ${__('to')} 0 ${__('of')} 0 ${__('entries')}`,
                    infoFiltered: `(${__('filtered_from')} _MAX_ ${__('total_entries')})`,
                    paginate: {
                        first: __('first'),
                        last: __('last'),
                        next: __('next'),
                        previous: __('previous')
                    },
                    emptyTable: __('no_data_available_in_table'),
                    zeroRecords: __('no_matching_records_found'),
                    processing: `<div class="spinner-border text-primary" role="status"><span class="visually-hidden">${__('loading')}...</span></div>`
                }
            });

            // View Evaluation Details
            $(document).on('click', '.view-eval-details', function() {
                var evalId = $(this).data('id');

                Swal.fire({
                    title: __('loading'),
                    text: __('fetching_evaluation_details', 'Fetching evaluation details'),
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

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
                            var eval = response.data;

                            let totalScoreBadge = 'success';
                            if (eval.total_score < 60) {
                                totalScoreBadge = 'danger';
                            } else if (eval.total_score < 70) {
                                totalScoreBadge = 'warning';
                            } else if (eval.total_score < 80) {
                                totalScoreBadge = 'info';
                            } else if (eval.total_score < 90) {
                                totalScoreBadge = 'primary';
                            }

                            function getScoreBadge(score) {
                                if (score >= 9) return 'success';
                                if (score >= 8) return 'primary';
                                if (score >= 7) return 'info';
                                if (score >= 6) return 'warning';
                                return 'danger';
                            }

                            let detailsHtml = `
                                <div class="evaluation-details-print" id="evaluationDetailsPrint" style="text-align: left; padding: 20px;">
                                    <div style="display: flex; justify-content: space-between; margin-bottom: 30px; padding-bottom: 20px; border-bottom: 2px solid #dee2e6;">
                                        <div style="flex: 1;">
                                            <p style="margin-bottom: 10px;"><strong>${__('evaluated_by')}:</strong> <span class="manager-name">${eval.manager_name}</span></p>
                                            <p style="margin-bottom: 10px;"><strong>${__('evaluation_date')}:</strong> ${eval.created_at ? eval.created_at.substring(0, 16).replace('T', ' ') : 'N/A'}</p>
                                        </div>
                                        <div style="flex: 1; text-align: right;">
                                            <p style="margin-bottom: 10px;"><strong>${__('total_score')}:</strong> <span class="badge badge-${totalScoreBadge}" style="font-size: 14px; padding: 5px 10px;">${eval.total_score || '0'}/100</span></p>
                                        </div>
                                    </div>
                                    
                                    <h5 style="margin-top: 20px; margin-bottom: 15px; color: #333;"><?= __('evaluation_criteria') ?></h5>
                                    <table class="table table-bordered" style="width: 100%; margin-bottom: 20px;">
                                        <thead style="background-color: #f8f9fa;">
                                            <tr>
                                                <th style="padding: 10px; width: 70%;"><?= __('criteria') ?></th>
                                                <th style="padding: 10px; text-align: center;"><?= __('score') ?></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr><td style="padding: 10px;">${__('punctuality_attendance')}</td><td style="padding: 10px; text-align: center;"><span class="badge badge-${getScoreBadge(eval.punctuality || 0)}">${eval.punctuality || '0'}/10</span></td></tr>
                                            <tr><td style="padding: 10px;">${__('achieving_at_the_specified_time')}</td><td style="padding: 10px; text-align: center;"><span class="badge badge-${getScoreBadge(eval.achieving_time || 0)}">${eval.achieving_time || '0'}/10</span></td></tr>
                                            <tr><td style="padding: 10px;">${__('knowledge_of_job')}</td><td style="padding: 10px; text-align: center;"><span class="badge badge-${getScoreBadge(eval.job_knowledge || 0)}">${eval.job_knowledge || '0'}/10</span></td></tr>
                                            <tr><td style="padding: 10px;">${__('the_ability_to_solve_problems')}</td><td style="padding: 10px; text-align: center;"><span class="badge badge-${getScoreBadge(eval.problem_solving || 0)}">${eval.problem_solving || '0'}/10</span></td></tr>
                                            <tr><td style="padding: 10px;">${__('receptiveness_to_feedback_and_instructions')}</td><td style="padding: 10px; text-align: center;"><span class="badge badge-${getScoreBadge(eval.feedback_receptiveness || 0)}">${eval.feedback_receptiveness || '0'}/10</span></td></tr>
                                            <tr><td style="padding: 10px;">${__('self_professional_development')}</td><td style="padding: 10px; text-align: center;"><span class="badge badge-${getScoreBadge(eval.self_development || 0)}">${eval.self_development || '0'}/10</span></td></tr>
                                            <tr><td style="padding: 10px;">${__('work_under_pressure')}</td><td style="padding: 10px; text-align: center;"><span class="badge badge-${getScoreBadge(eval.work_under_pressure || 0)}">${eval.work_under_pressure || '0'}/10</span></td></tr>
                                            <tr><td style="padding: 10px;">${__('communication_skills_and_teamwork')}</td><td style="padding: 10px; text-align: center;"><span class="badge badge-${getScoreBadge(eval.communication_teamwork || 0)}">${eval.communication_teamwork || '0'}/10</span></td></tr>
                                            <tr><td style="padding: 10px;">${__('creativity_and_speed_of_response')}</td><td style="padding: 10px; text-align: center;"><span class="badge badge-${getScoreBadge(eval.creativity_response || 0)}">${eval.creativity_response || '0'}/10</span></td></tr>
                                            <tr><td style="padding: 10px;">${__('initiative_and_cooperation')}</td><td style="padding: 10px; text-align: center;"><span class="badge badge-${getScoreBadge(eval.initiative_cooperation || 0)}">${eval.initiative_cooperation || '0'}/10</span></td></tr>
                                        </tbody>
                                    </table>
                                    
                                    ${eval.observation 
                                        ? `<h5 style="margin-top: 30px; margin-bottom: 15px; color: #333;"><?= __('observationremarks') ?></h5><p style="padding: 15px; background-color: #f8f9fa; border-radius: 5px; border-left: 4px solid #007bff;">${eval.observation}</p>` 
                                        : `<h5 style="margin-top: 30px; margin-bottom: 15px; color: #333;"><?= __('observationremarks') ?></h5><p style="padding: 15px; background-color: #f8f9fa; border-radius: 5px;"><?= __('no_observation_provided') ?></p>`}
                                    
                                    <div style="margin-top: 30px; padding-top: 20px; border-top: 2px solid #dee2e6;">
                                        <h5 style="margin-bottom: 15px; color: #333;">
                                            ${eval.manager_acknowledgment_status === 'acknowledged' ? ('<?= __('acknowledgment') ?>') : eval.manager_acknowledgment_status === 'objected' ? '<?= __('objection') ?>' : '<?= __('acknowledgment_status') ?>'}
                                        </h5>
                                        ${eval.manager_acknowledgment_status === 'pending' 
                                            ? `<div class="alert alert-warning" style="border-left: 4px solid #ffc107;"><i class="mdi mdi-clock-outline"></i> <strong><?= __('status') ?>:</strong> <?= __('pending_acknowledgment') ?></div>`
                                            : eval.manager_acknowledgment_status === 'acknowledged'
                                                ? `<div class="alert alert-success" style="border-left: 4px solid #28a745;">
                                                    <p style="margin-bottom: 5px;"><i class="mdi mdi-check-circle"></i> <strong><?= __('status') ?>:</strong> <?= __('acknowledged') ?></p>
                                                    ${eval.acknowledged_by_name ? `<p style="margin-bottom: 5px;"><strong><?= __('acknowledged_by') ?>:</strong> <span class='acknow_by_name'>${eval.acknowledged_by_name}</span></p>` : ''}
                                                    ${eval.acknowledgment_date ? `<p style="margin-bottom: 0;"><strong><?= __('date') ?>:</strong> ${eval.acknowledgment_date}</p>` : ''}
                                                </div>`
                                                : eval.manager_acknowledgment_status === 'objected'
                                                    ? `<div class="alert alert-danger" style="border-left: 4px solid #dc3545;">
                                                        <p style="margin-bottom: 10px;"><i class="mdi mdi-close-circle"></i> <strong><?= __('status') ?>:</strong> <?= __('objected') ?></p>
                                                        ${eval.manager_objection_note ? `<p style="margin-bottom: 10px;"><strong><?= __('objection_note') ?>:</strong></p><p style="padding: 10px; background-color: #fff; border-radius: 4px; white-space: pre-wrap;">${eval.manager_objection_note}</p>` : ''}
                                                        ${eval.acknowledged_by_name ? `<p style="margin-bottom: 5px;"><strong><?= __('objected_by') ?>:</strong> <span class="acknow_by_name">${eval.acknowledged_by_name}</span></p>` : ''}
                                                        ${eval.acknowledgment_date ? `<p style="margin-bottom: 0;"><strong><?= __('date') ?>:</strong> ${eval.acknowledgment_date}</p>` : ''}
                                                    </div>`
                                                    : `<div class="alert alert-secondary"><i class="mdi mdi-information-outline"></i> <strong><?= __('status') ?>:</strong> <?= __('unknown') ?></div>`
                                        }
                                    </div>
                                </div>
                            `;

                            Swal.fire({
                                title: __('evaluation_details'),
                                html: detailsHtml,
                                width: '900px',
                                showCloseButton: true,
                                confirmButtonText: __('close'),
                                allowOutsideClick: false,
                                didOpen: () => {
                                    var currentLang = getCurrentLanguage();
									// Translate employee name
									if (eval.acknowledged_by_name && currentLang === 'ar') {
										translateName(eval.acknowledged_by_name, 'en', 'ar', function(translated) {
											const empNameEl = document.querySelector('.acknow_by_name');
											if (empNameEl) empNameEl.textContent = translated;
										});
									}
									// Translate employee name
									if (eval.employee_name && currentLang === 'ar') {
										translateName(eval.employee_name, 'en', 'ar', function(translated) {
											const empNameEl = document.querySelector('.emp-name');
											if (empNameEl) empNameEl.textContent = translated;
										});
									}
									// Translate department name
									if (eval.dept_name && currentLang === 'ar') {
										translateName(eval.dept_name, 'en', 'ar', function(translated) {
											const deptNameEl = document.querySelector('.dept-name');
											if (deptNameEl) deptNameEl.textContent = translated;
										});
									}
									// Translate position
									if (eval.employee_position && currentLang === 'ar') {
										translateName(eval.employee_position, 'en', 'ar', function(translated) {
											const empPosEl = document.querySelector('.emp-position');
											if (empPosEl) empPosEl.textContent = translated;
										});
									}
									// Translate manager name
									if (eval.manager_name && currentLang === 'ar') {
										translateName(eval.manager_name, 'en', 'ar', function(translated) {
											const managerNameEl = document.querySelector('.manager-name');
											if (managerNameEl) managerNameEl.textContent = translated;
										});
									}
								}
                            });
                        } else {
                            Swal.fire('Error', 'Failed to load evaluation details', 'error');
                        }
                    },
                    error: function() {
                        Swal.fire('Error', 'An error occurred while loading the evaluation details', 'error');
                    }
                });
            });

            // Acknowledge Evaluation
            $(document).on('click', '.acknowledge-eval', function() {
                var evalId = $(this).data('id');
                var managerName = $(this).data('manager-name');

                Swal.fire({
                    title: __('acknowledge_evaluation', 'Acknowledge Evaluation'),
                    html: `<p>${__('manager_acknowledgment_confirm', 'Are you sure you want to acknowledge this evaluation from')} <strong class="manager-name">${managerName}</strong>?</p>`,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#28a745',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: __('yes_acknowledge', 'Yes, Acknowledge'),
                    cancelButtonText: __('cancel', 'Cancel'),
                    allowOutsideClick: false,
                    didOpen: () => {
						var currentLang = getCurrentLanguage();
						// Translate manager name
						if (managerName && currentLang === 'ar') {
							translateName(managerName, 'en', 'ar', function(translated) {
								const managerNameEl = document.querySelector('.manager-name');
								if (managerNameEl) managerNameEl.textContent = translated;
							});
						}
					}
                }).then((result) => {
                    if (result.isConfirmed) {
                        submitManagerAcknowledgment(evalId, 'acknowledge', null);
                    }
                });
            });

            // Object to Evaluation
            $(document).on('click', '.object-eval', function() {
                var evalId = $(this).data('id');
                var managerName = $(this).data('manager-name');

                Swal.fire({
                    title: __('object_to_evaluation', 'Object to Evaluation'),
                    html: `<p>${__('please_enter_your_objection_reasonnote', 'Please provide your objection reason for the evaluation from')} <strong class="manager-name">${managerName}</strong>:</p>`,
                    input: 'textarea',
                    inputPlaceholder: __('enter_your_objection_here', 'Enter your objection reason here...'),
                    inputAttributes: {
                        id: 'objection-note',
                        class: 'swal2-textarea',
                        rows: 5
                    },
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#ffc107',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: __('submit_objection', 'Submit Objection'),
                    cancelButtonText: __('cancel', 'Cancel'),
                    allowOutsideClick: false,
                    didOpen: () => {
						var currentLang = getCurrentLanguage();
						// Translate manager name
						if (managerName && currentLang === 'ar') {
							translateName(managerName, 'en', 'ar', function(translated) {
								const managerNameEl = document.querySelector('.manager-name');
								if (managerNameEl) managerNameEl.textContent = translated;
							});
						}
					},
                    preConfirm: () => {
                        const objectionNote = Swal.getInput().value;
                        if (!objectionNote || objectionNote.trim() === '') {
                            Swal.showValidationMessage(__('please_provide_an_objection_reason', 'Please provide an objection reason'));
                            return false;
                        }
                        if (objectionNote.length < 10) {
							Swal.showValidationMessage('<?= __('objection_note_min_length', 'Please provide at least 10 characters.') ?>');
							return false;
						}
						return objectionNote;
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        submitManagerAcknowledgment(evalId, 'object', result.value);
                    }
                });
            });

            function submitManagerAcknowledgment(evaluationId, action, objectionNote) {
                Swal.fire({
                    title: 'Processing...',
                    text: 'Please wait',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                $.ajax({
                    url: 'includes/ajaxFile/ajaxEvaluationAcknowledgment.php',
                    method: 'POST',
                    data: {
                        action: action,
                        evaluation_id: evaluationId,
                        objection_note: objectionNote
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 'success') {
                            Swal.fire({
                                icon: 'success',
                                title: __('success', 'Success'),
                                text: response.message,
                                confirmButtonText: __('ok', 'OK')
                            }).then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: __('error', 'Error'),
                                text: response.message,
                                confirmButtonText: __('ok', 'OK')
                            });
                        }
                    },
                    error: function() {
                        Swal.fire({
                            icon: 'error',
                            title: __('error', 'Error'),
                            text: __('an_error_occurred', 'An error occurred. Please try again.'),
                            confirmButtonText: __('ok', 'OK')
                        });
                    }
                });
            }
        });
    </script>
</body>
</html>
