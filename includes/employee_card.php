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
                <?php if(strtolower($emptype) == "manager"): ?>
                    <span class="emp-type-badge manager"><?= __('manager') ?></span>
                <?php else: ?>
                    <span class="emp-type-badge"><?= __(strtolower($emptype)) ?></span>
                <?php endif; ?>
            </div>

            <!-- Stats Section -->
            <?php if($emp_status == 1): ?>
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
            <?php endif;?>

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
