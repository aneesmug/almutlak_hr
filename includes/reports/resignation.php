<?php
/**
 * Resignation report - body part of request_report.php?type=resignation&id=..&emp_id=..
 * Header, employee banner, approval chain, signatures and footer come from includes/report_layout.php.
 * (Built from resignation_report_details.php.)
 */
/****************************************************************
 * RESIGNATION REPORT DETAILS PAGE
 * Displays comprehensive resignation information including:
 * - Employee Information
 * - Resignation Details
 * - Exit Interview Answers
 * - Replacement Information
 * - Approval Timeline
 ****************************************************************/


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

    // 4. Fetch replacement information (stored on emp_resignations itself)
    $replacement_data = [];
    if (!empty($resignation['needs_replacement'])) {
        $replacement_data['need_replacement'] = $resignation['needs_replacement'] ? 'yes' : 'no';
        if (!empty($resignation['replacement_data'])) {
            $decoded = json_decode($resignation['replacement_data'], true);
            if (is_string($decoded)) {
                // Handle double-encoded JSON from older records
                $decoded = json_decode($decoded, true);
            }
            if (is_array($decoded)) {
                $replacement_data = array_merge($replacement_data, $decoded);
            }
        }
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
                           d2.dep_nme AS approver_dept_name,
                           e.avatar AS approver_avatar, e.sex AS approver_sex
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

    // ---- Shared report layout (includes/report_layout.php) ----
    $report = [
        'title'      => __('resignation_request_report'),
        'request_no' => !empty($resignation['request_inv_no']) ? $resignation['request_inv_no'] : ('#' . (int)$resignation['id']),
        'status'     => (string)($resignation['status'] ?? ''),
        'dates'      => [__('submitted_date', 'Submitted Date') => $resignation['created_at'] ?? null],
        'emp_id'     => (string)$resignation['emp_id'],
    ];
    include dirname(__DIR__) . '/report_open.php';
?>
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

<?php
    $report_end = [
        'chain'        => $approval_chain,
        'chain_status' => (string)($resignation['status'] ?? ''),
        'signatures'   => ['employee_signature', 'hr_manager_signature', 'general_manager_signature'],
    ];
    include dirname(__DIR__) . '/report_close.php';
?>
<?php
    $conDB->close();
}
?>
