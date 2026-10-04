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
// Online presence: any 'active' row in user_activity_log for this emp_id.
// sweepStaleUserActivity() (run on every session_check.php load, across all
// users) flips stale 'active' rows to 'timeout', so this stays accurate
// without a dedicated cron/heartbeat.
if (!isset($GLOBALS['__employee_card_online_cache'])) {
    $GLOBALS['__employee_card_online_cache'] = [];
}
$card_emp_id_for_online = $emp_id ?? '';
$card_is_online = false;
if ($card_emp_id_for_online !== '') {
    if (!array_key_exists($card_emp_id_for_online, $GLOBALS['__employee_card_online_cache'])) {
        $online_resolved = false;
        $online_stmt = mysqli_prepare($conDB, "SELECT 1 FROM `user_activity_log` WHERE `emp_id` = ? AND `status` = 'active' LIMIT 1");
        if ($online_stmt) {
            mysqli_stmt_bind_param($online_stmt, "s", $card_emp_id_for_online);
            mysqli_stmt_execute($online_stmt);
            $online_resolved = (bool) mysqli_stmt_get_result($online_stmt)->fetch_row();
            mysqli_stmt_close($online_stmt);
        }
        $GLOBALS['__employee_card_online_cache'][$card_emp_id_for_online] = $online_resolved;
    }
    $card_is_online = $GLOBALS['__employee_card_online_cache'][$card_emp_id_for_online];
}

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
<?php
require_once __DIR__ . '/special_access_helper.php';
// System admin, HR department, or anyone granted the 'access_edit_employee' special access
$can_modify_employee = (
    ($is_system_admin ?? false) ||
    ($isDeptHr ?? false) ||
    user_has_special_access($conDB, $empid ?? '', 'access_edit_employee', $user_role ?? '', $user_type ?? '', $is_system_admin ?? false)
);
$card_can_edit = ($emp_status == 1 && $can_modify_employee);
$card_can_delete = !empty($is_system_admin);

$card_state = str_replace('status-', '', (string)$status_class); // active | fly | inactive
$card_state_meta = [
    'active'   => ['tone' => 'green', 'label' => __('active', 'Active'), 'icon' => 'fa-check'],
    'fly'      => ['tone' => 'sky',   'label' => __('on_vacation', 'On vacation'), 'icon' => 'fa-plane'],
    'inactive' => ['tone' => 'slate', 'label' => __('inactive', 'Inactive'), 'icon' => 'fa-minus'],
][$card_state] ?? ['tone' => 'slate', 'label' => '', 'icon' => 'fa-minus'];
$card_view_url = 'view_employee.php?emp_id=' . urlencode((string)$emp_id);
$card_display_name = getDisplayName($name);
?>
<!-- Employee card - "profile cover" layout (new GUI - assets/css/smart_request.css: .sr-emp-card).
     The card carries its own .sr-page scope so the design tokens work on any page. -->
<div class="col-xl-3 col-lg-4 col-md-6 mb-4 sr-emp-col">
    <div class="sr-page sr-emp-card is-<?= htmlspecialchars($card_state) ?>">

        <div class="sr-emp-cover">
            <span class="sr-emp-state"><i class="fa <?= $card_state_meta['icon'] ?>"></i><?= htmlspecialchars($card_state_meta['label']) ?></span>
            <?php if ($card_can_delete): ?>
                <div class="btn-group dropdown sr-emp-menu">
                    <a href="javascript:void(0);" class="dropdown-toggle arrow-none" data-toggle="dropdown" aria-expanded="false" title="<?= __('actions') ?>"><i class="fa fa-ellipsis-v"></i></a>
                    <div class="dropdown-menu dropdown-menu-right">
                        <?php if ($card_can_edit): ?>
                            <a class="dropdown-item" href="edit_employee.php?emp_id=<?= urlencode((string)$emp_id) ?>"><i class="fa fa-pen-to-square mr-2"></i><?= __('edit') ?></a>
                        <?php endif; ?>
                        <a class="dropdown-item text-danger deleteAjax" href="javascript:void(0);" data-id="<?= (int)$id ?>" data-tbl="employee" data-file="0"><i class="fa fa-trash-alt mr-2"></i><?= __('delete') ?></a>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <a href="<?= htmlspecialchars($card_view_url) ?>" class="sr-emp-avatar<?= $card_is_online ? ' is-online' : '' ?>" title="<?= $card_is_online ? __('online', 'Online') : __('offline', 'Offline') ?>">
            <img src="<?= htmlspecialchars($emp_avatar) ?>" alt="<?= htmlspecialchars($name) ?>" loading="lazy">
            <span class="sr-emp-online"></span>
        </a>

        <div class="sr-emp-body">
            <a href="<?= htmlspecialchars($card_view_url) ?>" class="sr-emp-name" title="<?= htmlspecialchars($card_display_name) ?>"><?= htmlspecialchars($card_display_name) ?></a>
            <span class="sr-emp-job"><?= !empty($card_job_title) ? htmlspecialchars($card_job_title) : '&ndash;' ?></span>

            <div class="sr-emp-chips">
                <?php if ($card_dept_name !== ''): ?>
                    <span class="sr-chip" title="<?= __('department') ?>"><i class="fa fa-sitemap"></i><?= htmlspecialchars($card_dept_name) ?></span>
                <?php endif; ?>
                <?php if ($card_comp_name !== ''): ?>
                    <span class="sr-chip" title="<?= __('company') ?>"><i class="fa fa-building"></i><?= htmlspecialchars($card_comp_name) ?></span>
                <?php endif; ?>
                <?php if ($card_is_online): ?>
                    <span class="sr-chip sr-emp-online-chip"><i class="fa fa-circle"></i><?= __('online', 'Online') ?></span>
                <?php endif; ?>
            </div>

            <div class="sr-emp-kv">
                <div>
                    <small><?= __('employee_id') ?></small>
                    <b class="sr-mono"><?= htmlspecialchars((string)$emp_id) ?></b>
                </div>
                <div>
                    <small><?= __('iqama_id') ?></small>
                    <b class="sr-mono copyToClipboard" title="<?= __('copy') ?>"><?= htmlspecialchars((string)$iqama) ?></b>
                </div>
            </div>

            <div class="sr-emp-actions">
                <a href="<?= htmlspecialchars($card_view_url) ?>" class="sr-btn sr-btn-primary sr-btn-sm sr-emp-view"><?= __('view_details') ?> <i class="fa fa-arrow-right"></i></a>
                <?php if ($card_can_edit): ?>
                    <a href="edit_employee.php?emp_id=<?= urlencode((string)$emp_id) ?>" class="sr-btn sr-btn-sm sr-btn-icon" title="<?= __('edit') ?>"><i class="fa fa-pen-to-square"></i></a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
