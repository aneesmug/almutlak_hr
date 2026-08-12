<?php
// Resolve department, company & actual job title for this card.
// Callers only pass `$rec` (raw `SELECT * FROM employees` row) with `dept` (department.id),
// `comp_no` (companies.comp_id) and `actual_job` (ac_jobs.id) - no name join exists upstream -
// so look them up here, cached per-request (via $GLOBALS, since a plain top-level `static`
// isn't guaranteed across separate include() calls) to avoid a query per card when a page
// lists many employees.
if (!isset($GLOBALS['__employee_card_dept_cache'])) {
    $GLOBALS['__employee_card_dept_cache'] = [];
}
if (!isset($GLOBALS['__employee_card_comp_cache'])) {
    $GLOBALS['__employee_card_comp_cache'] = [];
}
if (!isset($GLOBALS['__employee_card_job_cache'])) {
    $GLOBALS['__employee_card_job_cache'] = [];
}

$card_dept_id = isset($rec['dept']) ? (int)$rec['dept'] : 0;
$card_comp_no = isset($rec['comp_no']) ? (int)$rec['comp_no'] : 0;
$card_job_id = isset($rec['actual_job']) ? (int)$rec['actual_job'] : 0;

$card_dept_name = '';
if ($card_dept_id > 0) {
    if (!array_key_exists($card_dept_id, $GLOBALS['__employee_card_dept_cache'])) {
        $card_dept_name_resolved = '';
        $dept_stmt = mysqli_prepare($conDB, "SELECT `dep_nme`, `dep_nme_ar` FROM `department` WHERE `id` = ? LIMIT 1");
        if ($dept_stmt) {
            mysqli_stmt_bind_param($dept_stmt, "i", $card_dept_id);
            mysqli_stmt_execute($dept_stmt);
            $dept_row = mysqli_stmt_get_result($dept_stmt)->fetch_assoc();
            mysqli_stmt_close($dept_stmt);
            if ($dept_row) {
                $card_dept_name_resolved = (!empty($is_rtl) && !empty($dept_row['dep_nme_ar'])) ? $dept_row['dep_nme_ar'] : $dept_row['dep_nme'];
            }
        }
        $GLOBALS['__employee_card_dept_cache'][$card_dept_id] = $card_dept_name_resolved;
    }
    $card_dept_name = $GLOBALS['__employee_card_dept_cache'][$card_dept_id];
}

$card_comp_name = '';
if ($card_comp_no > 0) {
    if (!array_key_exists($card_comp_no, $GLOBALS['__employee_card_comp_cache'])) {
        $card_comp_name_resolved = '';
        $comp_stmt = mysqli_prepare($conDB, "SELECT `comp_name`, `comp_name_ar` FROM `companies` WHERE `comp_id` = ? LIMIT 1");
        if ($comp_stmt) {
            mysqli_stmt_bind_param($comp_stmt, "i", $card_comp_no);
            mysqli_stmt_execute($comp_stmt);
            $comp_row = mysqli_stmt_get_result($comp_stmt)->fetch_assoc();
            mysqli_stmt_close($comp_stmt);
            if ($comp_row) {
                $card_comp_name_resolved = (!empty($is_rtl) && !empty($comp_row['comp_name_ar'])) ? $comp_row['comp_name_ar'] : $comp_row['comp_name'];
            }
        }
        $GLOBALS['__employee_card_comp_cache'][$card_comp_no] = $card_comp_name_resolved;
    }
    $card_comp_name = $GLOBALS['__employee_card_comp_cache'][$card_comp_no];
}

// Actual job title (employees.actual_job -> ac_jobs.id), same join used by view_employee.php.
$card_job_title = '';
if ($card_job_id > 0) {
    if (!array_key_exists($card_job_id, $GLOBALS['__employee_card_job_cache'])) {
        $card_job_title_resolved = '';
        $job_stmt = mysqli_prepare($conDB, "SELECT `job`, `job_ar` FROM `ac_jobs` WHERE `id` = ? LIMIT 1");
        if ($job_stmt) {
            mysqli_stmt_bind_param($job_stmt, "i", $card_job_id);
            mysqli_stmt_execute($job_stmt);
            $job_row = mysqli_stmt_get_result($job_stmt)->fetch_assoc();
            mysqli_stmt_close($job_stmt);
            if ($job_row) {
                $card_job_title_resolved = (!empty($is_rtl) && !empty($job_row['job_ar'])) ? $job_row['job_ar'] : $job_row['job'];
            }
        }
        $GLOBALS['__employee_card_job_cache'][$card_job_id] = $card_job_title_resolved;
    }
    $card_job_title = $GLOBALS['__employee_card_job_cache'][$card_job_id];
}
?>
<!-- ============================================
    NEW MODERN GUI DESIGN - Employee Card
    ============================================ -->
<div class="col-lg-3 col-md-6 mb-4">
    <div class="employee-card-modern <?= $status_class ?>">
        <div class="employee-card-top-line"></div>
        <!-- Card Header with Background -->
        <div class="employee-card-header">
            <div class="header-gradient"></div>
            
            <!-- Employee Avatar -->
            <div class="employee-avatar-wrapper">
                <img src="<?= htmlspecialchars($emp_avatar) ?>" class="employee-avatar-modern" alt="<?= htmlspecialchars($name) ?>">
                <div class="avatar-status-badge <?= str_replace('status-', '', $status_class) ?>"></div>
            </div>

            <!-- Quick Actions -->
            <div class="card-actions-modern">
                <?php
                require_once __DIR__ . '/special_access_helper.php';
                // System admin, HR department, or anyone granted the 'access_edit_employee' special access
                $can_modify_employee = (
                    ($is_system_admin ?? false) ||
                    ($isDeptHr ?? false) ||
                    user_has_special_access($conDB, $empid ?? '', 'access_edit_employee', $user_role ?? '', $user_type ?? '', $is_system_admin ?? false)
                );
                ?>
                <?php if ($emp_status == 1 && $can_modify_employee): ?>
                    <a href="edit_employee.php?emp_id=<?= $emp_id ?>" class="action-btn edit-btn" title="<?= __('edit') ?>" data-toggle="tooltip">
                        <i class="fa fa-solid fa-pen-to-square"></i>
                    </a>
                <?php endif; ?>
                <?php if (isset($is_system_admin) && $is_system_admin): ?>
                    <a href="javascript:void(0);" class="action-btn delete-btn deleteAjax" data-id="<?= $id ?>" data-tbl="employee" data-file='0' title="<?= __('delete') ?>" data-toggle="tooltip">
                        <i class="fa fa-solid fa-trash-alt"></i>
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Card Body -->
        <div class="employee-card-body">
            <!-- Name -->
            <div class="employee-info-primary">
                <h5 class="employee-name"><?= getDisplayName($name) ?></h5>
            </div>

            <!-- Position/Type Badge -->
            <div class="employee-type-section">
                <?php if (!empty($card_job_title)): ?>
                    <span class="emp-type-badge manager"><?= htmlspecialchars($card_job_title) ?></span>
                <?php endif; ?>
            </div>

            <!-- Department / Company -->
            <?php if (!empty($card_dept_name) || !empty($card_comp_name)): ?>
            <div class="employee-org-row">
                <?php if (!empty($card_dept_name)): ?>
                    <span class="employee-org-item" title="<?= __('department') ?>">
                        <i class="fad fa-sitemap"></i> <?= htmlspecialchars($card_dept_name) ?>
                    </span>
                <?php endif; ?>
                <?php if (!empty($card_comp_name)): ?>
                    <span class="employee-org-item" title="<?= __('company') ?>">
                        <i class="fad fa-building"></i> <?= htmlspecialchars($card_comp_name) ?>
                    </span>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- Stats Section -->
            <?php /* if($emp_status == 1): ?>
                <div class="employee-stats">
                    <div class="stat-item">
                        <span class="stat-label"><?= __('fly') ?></span>
                        <span class="stat-value"><?= $cont_fly ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label"><?= __('encashed') ?></span>
                        <span class="stat-value"><?= $cont_encashed ?></span>
                    </div>
                </div>
            <?php endif; */?>

            <!-- Employee Details -->
            <div class="employee-details-grid">
                <div class="detail-item">
                    <span class="detail-label"><?= __('employee_id') ?></span>
                    <span class="detail-value"><?= $emp_id ?></span>
                </div>
                <div class="detail-item">
                    <span class="detail-label"><?= __('iqama_id') ?></span>
                    <span class="detail-value copyToClipboard" title="<?= __('copy') ?>"><?= $iqama ?></span>
                </div>
            </div>

            <!-- Primary Action Button -->
            <a href="view_employee.php?emp_id=<?= $emp_id ?>" class="btn-view-details">
                <span><?= __('view_details') ?></span>
                <i class="fa fa-solid fa-arrow-right"></i>
            </a>
        </div>
    </div>
</div>

<!-- ============================================
    OLD DESIGN - COMMENTED OUT FOR RESTORATION
    ============================================ 

<div class="col-lg-3 col-md-6 mb-4">
    <div class="card card-employee shadow-sm h-100 <?= $status_class ?>">
        <div class="card-actions">
            <div class="btn-group" role="group">
                <?php
                require_once __DIR__ . '/special_access_helper.php';
                // System admin, HR department, or anyone granted the 'access_edit_employee' special access
                $can_modify_employee = (
                    ($is_system_admin ?? false) ||
                    ($isDeptHr ?? false) ||
                    user_has_special_access($conDB, $empid ?? '', 'access_edit_employee', $user_role ?? '', $user_type ?? '', $is_system_admin ?? false)
                );
                ?>
                <?php if ($emp_status == 1 && $can_modify_employee): ?>
                    <a href="edit_employee.php?emp_id=<?= $emp_id ?>" class="btn btn-light btn-sm" title="<?= __('edit') ?>">
                        <i class="fa fa-solid fa-user-pen"></i>
                    </a>
                <?php endif; ?>
                <?php if (isset($is_system_admin) && $is_system_admin): ?>
                    <a href="javascript:void(0);" class="btn btn-danger btn-sm deleteAjax" data-id="<?= $id ?>" data-tbl="employee" data-file='0' title="<?= __('delete') ?>">
                        <i class="fa fa-solid fa-remove"></i>
                    </a>
                <?php endif; ?>
            </div>
        </div>
        <div class="card-body text-center d-flex flex-column">
            <img src="<?= htmlspecialchars($emp_avatar) ?>" class="rounded-circle mx-auto mb-3 emp-avatar" alt="Profile Image">

            <h5 class="mb-0 font-weight-bold"><?= ($name) ?></h5>
            <p class="text-muted small"><?= (strtolower($emptype) == "manager") ? "<span class=\"badge badge-info\">".__(strtolower($emptype))."</span>" : __(strtolower($emptype)) ?></p>
            <?php if($emp_status == 1): ?>
                <span class="badge badge-dark badge-pill mx-auto my-3"><?= __('fly') ?>: <?= $cont_fly ?> | <?= __('encashed') ?>: <?= $cont_encashed ?></span>
            <?php endif;?>
            <a href="view_employee.php?emp_id=<?= $emp_id ?>" class="btn btn-primary btn-block mt-auto waves-effect waves-light"><i class="fa fa-solid fa-eye mr-2"></i><?= __('view_details') ?></a>

            <div class="mt-4 pt-3 border-top">
                <div class="row">
                    <div class="col-6 text-center">
                        <p class="text-muted mb-0 small text-uppercase"><?= __('employee_id') ?></p>
                        <h6 class="mb-0"><?= $emp_id ?></h6>
                    </div>
                    <div class="col-6 text-center border-left">
                        <p class="text-muted mb-0 small text-uppercase"><?= __('iqama_id') ?></p>
                        <h6 class="mb-0 copyToClipboard" title="Copy ID"><?= $iqama ?></h6>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

-->
