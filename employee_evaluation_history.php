<?php
/**
 * Employee Evaluation History Page
 * Displays all evaluations for a specific employee
 */

require_once("./includes/session_check.php");
require_once __DIR__ . '/includes/sr_history_helpers.php';
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
$ack_counts = ['pending' => 0, 'acknowledged' => 0, 'objected' => 0];
$score_sum = 0.0;
foreach ($evaluations as $ev) {
    $k = (string)($ev['manager_acknowledgment_status'] ?? '');
    if (isset($ack_counts[$k])) $ack_counts[$k]++;
    $score_sum += (float)$ev['total_score'];
}
$avg_score = $evaluations ? $score_sum / count($evaluations) : 0;
$latest = $evaluations[0] ?? null;

srh_page_open(__('evaluation_history', 'Evaluation History'), srh_portal_head());
echo srh_header(__('evaluation_history', 'Evaluation History'), '', srh_profile_url($emprow));
echo srh_emp_hero($emprow);
echo srh_tiles([
    [__('evaluations', 'Evaluations'), count($evaluations), 'indigo', ''],
    [__('average_score', 'Average Score'), $evaluations ? number_format($avg_score, 1) . '<small style="display: inline; font-size: 13px;">/100</small>' : '-', 'green', ''],
    [__('latest_score', 'Latest Score'), $latest ? (int)$latest['total_score'] . '<small style="display: inline; font-size: 13px;">/100</small>' : '-', '', $latest ? sr_h(format_safe_date($latest['eval_date'], 'd M Y')) : ''],
    [__('pending_acknowledgment', 'Pending Acknowledgment'), $ack_counts['pending'], $ack_counts['pending'] ? 'amber' : '', $ack_counts['objected'] ? sr_h(__('objected', 'Objected')) . ': ' . $ack_counts['objected'] : ''],
]);

$score_tone = function ($s) {
    if ($s < 50) return 'red';
    if ($s < 70) return 'amber';
    if ($s < 90) return 'sky';
    return 'green';
};

if (!$evaluations) {
    $body = sr_empty_state(__('no_evaluation_history_found', 'No evaluation history found.'));
} else {
    $body = '<div class="sr-table-wrap"><table class="sr-table srh-dt"><thead><tr>'
        . '<th>' . sr_h(__('id', 'ID')) . '</th>'
        . '<th>' . sr_h(__('evaluation_date', 'Evaluation Date')) . '</th>'
        . '<th>' . sr_h(__('evaluated_by', 'Evaluated By')) . '</th>'
        . '<th>' . sr_h(__('total_score', 'Total Score')) . '</th>'
        . '<th>' . sr_h(__('acknowledgment_status', 'Status')) . '</th>'
        . '<th class="text-right no-print">' . sr_h(__('action', 'Action')) . '</th>'
        . '</tr></thead><tbody>';
    foreach ($evaluations as $eval) {
        $score = (float)$eval['total_score'];
        $ack = (string)$eval['manager_acknowledgment_status'];
        if ($ack === 'pending') $pill = sr_pill('amber', __('pending', 'Pending'), false, 'mdi-clock');
        elseif ($ack === 'acknowledged') $pill = sr_pill('green', __('acknowledged', 'Acknowledged'), false, 'mdi-check-circle');
        else $pill = sr_pill('red', __('objected', 'Objected'), false, 'mdi-close-circle');

        $actions = '<div class="sr-actions">'
            . '<button type="button" class="sr-open-btn view-eval-details" data-id="' . (int)$eval['id'] . '"><i class="mdi mdi-eye"></i> ' . sr_h(__('view_details', 'View Details')) . '</button>';
        if ($ack === 'pending') {
            $actions .= '<button type="button" class="sr-btn sr-btn-sm sr-btn-success acknowledge-eval" data-id="' . (int)$eval['id'] . '" data-manager-name="' . sr_h($eval['manager_name']) . '" title="' . sr_h(__('acknowledge_evaluation', 'Acknowledge Evaluation')) . '"><i class="mdi mdi-check-circle"></i> ' . sr_h(__('acknowledge', 'Acknowledge')) . '</button>'
                . '<button type="button" class="sr-btn sr-btn-sm sr-btn-danger object-eval" data-id="' . (int)$eval['id'] . '" data-manager-name="' . sr_h($eval['manager_name']) . '" title="' . sr_h(__('object_to_evaluation', 'Object to Evaluation')) . '"><i class="mdi mdi-close-circle"></i> ' . sr_h(__('object', 'Object')) . '</button>';
        }
        $actions .= '</div>';

        $body .= '<tr>'
            . '<td><span class="sr-mono">#' . (int)$eval['id'] . '</span></td>'
            . '<td data-order="' . sr_h($eval['eval_date']) . '"><span class="sr-date">' . sr_h(format_safe_date($eval['eval_date'], 'd M Y')) . '<small>' . sr_h(format_safe_date($eval['eval_date'], 'H:i')) . '</small></span></td>'
            . '<td>' . sr_person_cell(getDisplayName($eval['manager_name']), ($is_rtl ?? false) ? ($eval['dep_nme_ar'] ?? '') : ($eval['dep_nme'] ?? '')) . '</td>'
            . '<td data-order="' . $score . '"><div class="srh-score"><b class="tone-' . $score_tone($score) . '">' . (int)$score . '</b><span>/100</span></div>'
                . '<div class="srh-progress srh-progress-sm" style="max-width: 120px;"><span style="width: ' . min(100, max(0, $score)) . '%;"></span></div></td>'
            . '<td>' . $pill . '</td>'
            . '<td class="text-right no-print">' . $actions . '</td>'
            . '</tr>';
    }
    $body .= '</tbody></table></div>';
}
echo srh_card('mdi-star', __('evaluation_history', 'Evaluation History'), $body, $evaluations ? srh_search_box() : '', 'sr-card-body p-0');

// Labels the popups need (PHP-translated once, read by the JS below).
$L = [
    'evaluation_criteria' => __('evaluation_criteria'),
    'criteria' => __('criteria'),
    'score' => __('score'),
    'observationremarks' => __('observationremarks'),
    'no_observation_provided' => __('no_observation_provided'),
    'acknowledgment' => __('acknowledgment'),
    'objection' => __('objection'),
    'acknowledgment_status' => __('acknowledgment_status'),
    'status' => __('status'),
    'pending_acknowledgment' => __('pending_acknowledgment'),
    'acknowledged' => __('acknowledged'),
    'acknowledged_by' => __('acknowledged_by'),
    'objected' => __('objected'),
    'objected_by' => __('objected_by'),
    'objection_note' => __('objection_note'),
    'date' => __('date'),
    'unknown' => __('unknown'),
    'objection_note_min_length' => __('objection_note_min_length', 'Please provide at least 10 characters.'),
];

$eval_js = '<script>window.SRH_EVAL_L = ' . json_encode($L, JSON_UNESCAPED_UNICODE) . ';</script>' . <<<'JS'
<script>
$(function () {
    const L = window.SRH_EVAL_L || {};
    const esc = (v) => $('<div>').text(v == null ? '' : String(v)).html();
    const toneFor100 = (s) => s < 60 ? 'red' : s < 70 ? 'amber' : s < 80 ? 'sky' : s < 90 ? 'indigo' : 'green';
    const toneFor10 = (s) => s >= 9 ? 'green' : s >= 8 ? 'indigo' : s >= 7 ? 'sky' : s >= 6 ? 'amber' : 'red';
    const translateInto = (name, selector) => {
        if (name && typeof getCurrentLanguage === 'function' && getCurrentLanguage() === 'ar' && typeof translateName === 'function') {
            translateName(name, 'en', 'ar', function (translated) {
                const el = document.querySelector(selector);
                if (el) el.textContent = translated;
            });
        }
    };

    // View evaluation details
    $(document).on('click', '.view-eval-details', function () {
        const evalId = $(this).data('id');
        Swal.fire({ title: __('loading'), text: __('fetching_evaluation_details', 'Fetching evaluation details'), allowOutsideClick: false, didOpen: () => Swal.showLoading() });
        $.ajax({
            url: 'includes/ajaxFile/ajaxEvaluation.php',
            method: 'POST',
            data: { action: 'get_evaluation_details', evaluation_id: evalId },
            dataType: 'json',
            success: function (response) {
                if (response.status !== 'success') {
                    Swal.fire('Error', 'Failed to load evaluation details', 'error');
                    return;
                }
                const ev = response.data;
                const total = Number(ev.total_score || 0);
                const criteria = [
                    ['punctuality_attendance', ev.punctuality], ['achieving_at_the_specified_time', ev.achieving_time],
                    ['knowledge_of_job', ev.job_knowledge], ['the_ability_to_solve_problems', ev.problem_solving],
                    ['receptiveness_to_feedback_and_instructions', ev.feedback_receptiveness], ['self_professional_development', ev.self_development],
                    ['work_under_pressure', ev.work_under_pressure], ['communication_skills_and_teamwork', ev.communication_teamwork],
                    ['creativity_and_speed_of_response', ev.creativity_response], ['initiative_and_cooperation', ev.initiative_cooperation]
                ];
                const rows = criteria.map(([key, val]) => {
                    const v = Number(val || 0);
                    return `<tr><td>${esc(__(key))}</td><td style="width: 45%;"><div class="srh-crit"><div class="srh-progress srh-progress-sm"><span style="width: ${v * 10}%;"></span></div><span class="sr-pill sr-pill-xs tone-${toneFor10(v)}">${v}/10</span></div></td></tr>`;
                }).join('');

                let ackHtml;
                const st = ev.manager_acknowledgment_status;
                if (st === 'pending') {
                    ackHtml = `<div class="sr-notice tone-amber" style="margin: 0;"><i class="mdi mdi-clock"></i><div><strong>${esc(L.status)}:</strong> ${esc(L.pending_acknowledgment)}</div></div>`;
                } else if (st === 'acknowledged') {
                    ackHtml = `<div class="sr-notice tone-green" style="margin: 0;"><i class="mdi mdi-check-circle"></i><div>
                        <div><strong>${esc(L.status)}:</strong> ${esc(L.acknowledged)}</div>
                        ${ev.acknowledged_by_name ? `<div><strong>${esc(L.acknowledged_by)}:</strong> <span class="acknow_by_name">${esc(ev.acknowledged_by_name)}</span></div>` : ''}
                        ${ev.acknowledgment_date ? `<div><strong>${esc(L.date)}:</strong> ${esc(ev.acknowledgment_date)}</div>` : ''}</div></div>`;
                } else if (st === 'objected') {
                    ackHtml = `<div class="sr-notice tone-red" style="margin: 0;"><i class="mdi mdi-close-circle"></i><div>
                        <div><strong>${esc(L.status)}:</strong> ${esc(L.objected)}</div>
                        ${ev.manager_objection_note ? `<div style="margin-top: 6px;"><strong>${esc(L.objection_note)}:</strong><div class="srh-note" style="margin-top: 4px;">${esc(ev.manager_objection_note)}</div></div>` : ''}
                        ${ev.acknowledged_by_name ? `<div style="margin-top: 6px;"><strong>${esc(L.objected_by)}:</strong> <span class="acknow_by_name">${esc(ev.acknowledged_by_name)}</span></div>` : ''}
                        ${ev.acknowledgment_date ? `<div><strong>${esc(L.date)}:</strong> ${esc(ev.acknowledgment_date)}</div>` : ''}</div></div>`;
                } else {
                    ackHtml = `<div class="sr-notice tone-slate" style="margin: 0;"><i class="mdi mdi-information-outline"></i><div><strong>${esc(L.status)}:</strong> ${esc(L.unknown)}</div></div>`;
                }

                const html = `<div class="sr-page"><div class="sr-form" id="evaluationDetailsPrint">
                    <div class="srh-tiles" style="--srh-cols: 3; margin-bottom: 12px;">
                        <div class="srh-tile"><span>${esc(__('evaluated_by'))}</span><b class="manager-name" style="font-size: 15px;">${esc(ev.manager_name)}</b></div>
                        <div class="srh-tile"><span>${esc(__('evaluation_date'))}</span><b style="font-size: 15px;">${esc(ev.created_at ? ev.created_at.substring(0, 16).replace('T', ' ') : 'N/A')}</b></div>
                        <div class="srh-tile tone-${toneFor100(total) === 'red' ? 'red' : toneFor100(total) === 'amber' ? 'amber' : 'green'}"><span>${esc(__('total_score'))}</span><b>${total}<small style="display: inline; font-size: 13px;">/100</small></b></div>
                    </div>
                    <div class="sr-fsec"><div class="sr-fsec-head"><span><i class="mdi mdi-format-list-checks"></i> ${esc(L.evaluation_criteria)}</span></div>
                        <div class="sr-table-wrap"><table class="sr-table" style="margin: 0;"><thead><tr><th>${esc(L.criteria)}</th><th>${esc(L.score)}</th></tr></thead><tbody>${rows}</tbody></table></div></div>
                    <div class="sr-fsec"><div class="sr-fsec-head"><span><i class="mdi mdi-comment-text-outline"></i> ${esc(L.observationremarks)}</span></div>
                        <div class="sr-fsec-body" style="padding: 12px 14px;"><div class="srh-note">${ev.observation ? esc(ev.observation) : esc(L.no_observation_provided)}</div></div></div>
                    <div class="sr-fsec mb-0"><div class="sr-fsec-head"><span><i class="mdi mdi-account-check"></i> ${esc(st === 'acknowledged' ? L.acknowledgment : st === 'objected' ? L.objection : L.acknowledgment_status)}</span></div>
                        <div class="sr-fsec-body" style="padding: 12px 14px;">${ackHtml}</div></div>
                </div></div>`;

                Swal.fire({
                    title: __('evaluation_details'),
                    html: html,
                    width: '900px',
                    showCloseButton: true,
                    confirmButtonText: __('close'),
                    allowOutsideClick: false,
                    customClass: { popup: 'sr-addline-popup sr-page' },
                    didOpen: () => {
                        translateInto(ev.acknowledged_by_name, '.acknow_by_name');
                        translateInto(ev.manager_name, '.manager-name');
                    }
                });
            },
            error: function () {
                Swal.fire('Error', 'An error occurred while loading the evaluation details', 'error');
            }
        });
    });

    // Acknowledge evaluation
    $(document).on('click', '.acknowledge-eval', function () {
        const evalId = $(this).data('id');
        const managerName = $(this).data('manager-name');
        Swal.fire({
            title: __('acknowledge_evaluation', 'Acknowledge Evaluation'),
            html: `<p>${__('manager_acknowledgment_confirm', 'Are you sure you want to acknowledge this evaluation from')} <strong class="manager-name">${esc(managerName)}</strong>?</p>`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#6c757d',
            confirmButtonText: __('yes_acknowledge', 'Yes, Acknowledge'),
            cancelButtonText: __('cancel', 'Cancel'),
            allowOutsideClick: false,
            customClass: { popup: 'sr-addline-popup sr-page' },
            didOpen: () => translateInto(managerName, '.manager-name')
        }).then((result) => {
            if (result.isConfirmed) submitManagerAcknowledgment(evalId, 'acknowledge', null);
        });
    });

    // Object to evaluation
    $(document).on('click', '.object-eval', function () {
        const evalId = $(this).data('id');
        const managerName = $(this).data('manager-name');
        Swal.fire({
            title: __('object_to_evaluation', 'Object to Evaluation'),
            html: `<p>${__('please_enter_your_objection_reasonnote', 'Please provide your objection reason for the evaluation from')} <strong class="manager-name">${esc(managerName)}</strong>:</p>`,
            input: 'textarea',
            inputPlaceholder: __('enter_your_objection_here', 'Enter your objection reason here...'),
            inputAttributes: { id: 'objection-note', rows: 5 },
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: __('submit_objection', 'Submit Objection'),
            cancelButtonText: __('cancel', 'Cancel'),
            allowOutsideClick: false,
            customClass: { popup: 'sr-addline-popup sr-page' },
            didOpen: () => translateInto(managerName, '.manager-name'),
            preConfirm: () => {
                const objectionNote = Swal.getInput().value;
                if (!objectionNote || objectionNote.trim() === '') {
                    Swal.showValidationMessage(__('please_provide_an_objection_reason', 'Please provide an objection reason'));
                    return false;
                }
                if (objectionNote.length < 10) {
                    Swal.showValidationMessage(L.objection_note_min_length);
                    return false;
                }
                return objectionNote;
            }
        }).then((result) => {
            if (result.isConfirmed) submitManagerAcknowledgment(evalId, 'object', result.value);
        });
    });

    function submitManagerAcknowledgment(evaluationId, action, objectionNote) {
        Swal.fire({ title: 'Processing...', text: 'Please wait', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
        $.ajax({
            url: 'includes/ajaxFile/ajaxEvaluationAcknowledgment.php',
            method: 'POST',
            data: { action: action, evaluation_id: evaluationId, objection_note: objectionNote },
            dataType: 'json',
            success: function (response) {
                if (response.status === 'success') {
                    Swal.fire({ icon: 'success', title: __('success', 'Success'), text: response.message, confirmButtonText: __('ok', 'OK') })
                        .then(() => location.reload());
                } else {
                    Swal.fire({ icon: 'error', title: __('error', 'Error'), text: response.message, confirmButtonText: __('ok', 'OK') });
                }
            },
            error: function () {
                Swal.fire({ icon: 'error', title: __('error', 'Error'), text: __('an_error_occurred', 'An error occurred. Please try again.'), confirmButtonText: __('ok', 'OK') });
            }
        });
    }
});
</script>
JS;

srh_page_close('<script src="assets/js/employee_profile.js"></script>' . $eval_js
    . srh_dt_js('.srh-dt', 1, 'desc', 'mdi-star', 'srhSearch', [5]));
