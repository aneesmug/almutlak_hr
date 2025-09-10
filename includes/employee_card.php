<div class="col-lg-3 col-md-6 mb-4">
    <div class="card card-employee shadow-sm h-100 <?= $status_class ?>">
        <div class="card-actions">
            <div class="btn-group" role="group">
                <?php if ($emp_status == 1 && ($user_type ?? '') != "dept_user"): ?>
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

            <h5 class="mb-0 font-weight-bold"><?= parseName($name) ?></h5>
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