<?php
require_once __DIR__ . '/includes/session_check.php';

// Check authorization - only administrators and HR can access
// (or an explicit 'Access Page: Manage Employee Supervisors' Special Access grant)
if ($user_type != 'administrator' && !user_has_special_access($conDB, $empid ?? '', 'access_manage_employee_supervisors', $user_role ?? '', $user_type ?? '', $is_system_admin ?? false)) {
    header("Location: dashboard.php");
    exit();
}

$msg = '';
$employee_data = null;

// Handle AJAX search for employees
if (isset($_POST['action']) && $_POST['action'] == 'search_employees') {
    header('Content-Type: application/json');
    
    $search_term = isset($_POST['term']) ? trim($_POST['term']) : '';
    
    if (strlen($search_term) < 1) {
        echo json_encode(['success' => false, 'message' => 'Please enter search term']);
        exit;
    }
    
    try {
        // Select appropriate job name based on RTL setting
        $job_col = ($is_rtl ?? false) ? 'job_ar' : 'job';
        
        $query = "SELECT DISTINCT e.`emp_id`, e.`name`, e.`actual_job`, e.`dept`, 
                         j.`$job_col` as job_name,
                         COUNT(ev.`id`) as vacation_request_count
                  FROM `employees` e
                  LEFT JOIN `ac_jobs` j ON e.`actual_job` = j.`id`
                  INNER JOIN `emp_vacation` ev ON e.`emp_id` = ev.`emp_id`
                  WHERE (`e`.`name` LIKE ? OR `e`.`emp_id` LIKE ?) 
                  AND `e`.`status` = 1
                  AND `ev`.`review` NOT IN ('C', 'R')
                  GROUP BY e.`emp_id`, e.`name`, e.`actual_job`, e.`dept`
                  ORDER BY e.`name`
                  LIMIT 20";
        
        $stmt = $pdo->prepare($query);
        $search_pattern = '%' . $search_term . '%';
        $stmt->execute([$search_pattern, $search_pattern]);
        $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Reindex with proper keys
        $result_employees = [];
        foreach ($employees as $emp) {
            $result_employees[] = [
                'id' => $emp['emp_id'],
                'name' => $emp['name'],
                'emp_id' => $emp['emp_id'],
                'designation' => !empty($emp['job_name']) ? $emp['job_name'] : 'Job ID: ' . $emp['actual_job'],
                'dept' => $emp['dept'],
                'vacation_count' => $emp['vacation_request_count']
            ];
        }
        
        echo json_encode(['success' => true, 'employees' => $result_employees]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Search error: ' . $e->getMessage()]);
    }
    exit;
}

// Handle AJAX get supervisors list
if (isset($_POST['action']) && $_POST['action'] == 'get_supervisors') {
    header('Content-Type: application/json');
    
    $emp_id = isset($_POST['emp_id']) ? (int)$_POST['emp_id'] : 0;
    
    if ($emp_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid employee ID']);
        exit;
    }
    
    // Get current employee department to show supervisors from same or higher departments
    $emp_query = "SELECT `emp_id`, `supervisor_id`, `dept` FROM `employees` WHERE `emp_id` = $emp_id LIMIT 1";
    $emp_result = mysqli_query($conDB, $emp_query);
    $emp_data = mysqli_fetch_assoc($emp_result);
    
    if (!$emp_data) {
        echo json_encode(['success' => false, 'message' => 'Employee not found']);
        exit;
    }
    
    $current_supervisor_id = $emp_data['supervisor_id'];
    
    // Get list of all potential supervisors (only non-employee admin users)
    // Select appropriate job name based on RTL setting
    $job_col = ($is_rtl ?? false) ? 'job_ar' : 'job';
    
    $supervisors_query = "SELECT e.`emp_id`, e.`name`, e.`actual_job`, j.`$job_col` as job_name, al.`user_type`
                          FROM `employees` e
                          LEFT JOIN `ac_jobs` j ON e.`actual_job` = j.`id`
                          JOIN `admin_login` al ON e.`emp_id` = al.`emp_id`
                          WHERE e.`status` = 1 AND e.`emp_id` != $emp_id 
                          AND al.`user_type` != 'employee'
                          ORDER BY e.`name`";
    
    $supervisors_result = mysqli_query($conDB, $supervisors_query);
    $supervisors = [];
    
    while ($row = mysqli_fetch_assoc($supervisors_result)) {
        $supervisors[] = [
            'id' => $row['emp_id'],
            'name' => $row['name'],
            'designation' => !empty($row['job_name']) ? $row['job_name'] : 'Job ID: ' . $row['actual_job'],
            'user_type' => $row['user_type'],
            'is_current' => ($row['emp_id'] == $current_supervisor_id)
        ];
    }
    
    echo json_encode([
        'success' => true,
        'supervisors' => $supervisors,
        'current_supervisor_id' => $current_supervisor_id
    ]);
    exit;
}

// Handle AJAX update supervisor
if (isset($_POST['action']) && $_POST['action'] == 'update_supervisor') {
    header('Content-Type: application/json');
    
    $emp_id = isset($_POST['emp_id']) ? (int)$_POST['emp_id'] : 0;
    $new_supervisor_id = isset($_POST['supervisor_id']) ? (int)$_POST['supervisor_id'] : 0;
    $update_employee_supervisor = isset($_POST['update_employee_supervisor']) ? (bool)$_POST['update_employee_supervisor'] : false;
    
    if ($emp_id <= 0 || $new_supervisor_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid employee or supervisor ID']);
        exit;
    }
    
    // Get old supervisor for logging
    $old_query = "SELECT `supervisor_id` FROM `employees` WHERE `emp_id` = $emp_id LIMIT 1";
    $old_result = mysqli_query($conDB, $old_query);
    $old_data = mysqli_fetch_assoc($old_result);
    $old_supervisor_id = $old_data['supervisor_id'] ?? null;
    
    // Get employee name and job for logging
    $job_col = ($is_rtl ?? false) ? 'job_ar' : 'job';
    $emp_query = "SELECT e.`name`, j.`$job_col` as job_name FROM `employees` e 
                  LEFT JOIN `ac_jobs` j ON e.`actual_job` = j.`id`
                  WHERE e.`emp_id` = $emp_id LIMIT 1";
    $emp_result = mysqli_query($conDB, $emp_query);
    $emp_data = mysqli_fetch_assoc($emp_result);
    $emp_name = $emp_data['name'] ?? 'Unknown';
    $emp_job = !empty($emp_data['job_name']) ? $emp_data['job_name'] : 'N/A';
    
    // Get new supervisor name and job for logging
    $sup_query = "SELECT e.`name`, j.`$job_col` as job_name FROM `employees` e 
                  LEFT JOIN `ac_jobs` j ON e.`actual_job` = j.`id`
                  WHERE e.`emp_id` = $new_supervisor_id LIMIT 1";
    $sup_result = mysqli_query($conDB, $sup_query);
    $sup_data = mysqli_fetch_assoc($sup_result);
    $sup_name = $sup_data['name'] ?? 'Unknown';
    $sup_job = !empty($sup_data['job_name']) ? $sup_data['job_name'] : 'N/A';
    
    // Start transaction for atomic updates
    mysqli_query($conDB, "START TRANSACTION");
    
    try {
        // Update supervisor in employees table (only if checkbox is checked)
        if ($update_employee_supervisor) {
            $update_emp_query = "UPDATE `employees` SET `supervisor_id` = $new_supervisor_id WHERE `emp_id` = $emp_id";
            if (!mysqli_query($conDB, $update_emp_query)) {
                throw new Exception("Failed to update employees table: " . mysqli_error($conDB));
            }
        }
        
        // Update approver_id in request_approvers table for this employee's vacation requests
        // Get the request type ID for vacation requests
        $req_type_query = "SELECT `id` FROM `approval_request_types` WHERE `type_name` = 'vacation_request' LIMIT 1";
        $req_type_result = mysqli_query($conDB, $req_type_query);
        $req_type_row = mysqli_fetch_assoc($req_type_result);
        $vacation_request_type_id = $req_type_row['id'] ?? null;
        
        if ($vacation_request_type_id) {
            $update_approvers_query = "UPDATE `request_approvers` 
                                       SET `approver_id` = $new_supervisor_id 
                                       WHERE `approver_id` = $old_supervisor_id 
                                       AND `request_type_id` = $vacation_request_type_id
                                       AND `status` IN ('pending', 'awaiting')";
            
            if (!mysqli_query($conDB, $update_approvers_query)) {
                throw new Exception("Failed to update request_approvers table: " . mysqli_error($conDB));
            }
        }
        
        // Commit transaction
        mysqli_query($conDB, "COMMIT");
        
        // Log the change
        require_once __DIR__ . '/includes/helper_functions.php';
        
        $update_type = $update_employee_supervisor ? 'both employees.supervisor_id and request_approvers.approver_id' : 'request_approvers.approver_id only';
        $description = "Changed vacation approver for {$emp_name} ({$emp_job}) from ID {$old_supervisor_id} to {$sup_name} ({$sup_job}) (ID {$new_supervisor_id}). Updated {$update_type}.";
        
        ActivityLogger::logUpdate(
            'Employee Vacation Approver',
            'manage_employee_supervisors.php',
            $emp_id,
            ['supervisor_id' => $old_supervisor_id],
            ['supervisor_id' => $new_supervisor_id],
            $description,
            'employees'
        );
        
        $msg_text = $update_employee_supervisor ? "Vacation approver updated successfully for {$emp_name}. Updated both employee record and vacation request chain." : "Vacation approver updated successfully for {$emp_name}. Updated vacation request chain only.";
        
        echo json_encode([
            'success' => true,
            'message' => $msg_text,
            'new_supervisor_name' => $sup_name
        ]);
    } catch (Exception $e) {
        // Rollback transaction on error
        mysqli_query($conDB, "ROLLBACK");
        
        echo json_encode([
            'success' => false,
            'message' => 'Failed to update: ' . $e->getMessage()
        ]);
    }
    exit;
}

?>
<!doctype html>
<html lang="<?= $current_lang ?? 'en' ?>" <?= ($is_rtl ?? false) ? 'dir="rtl"' : '' ?>>
<head>
    <meta charset="utf-8" />
    <title><?= $site_title ?> - <?= __('manage_employee_supervisors') ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta content="Anees Afzal" name="author" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <link rel="shortcut icon" href="<?=get_setting($conDB, 'favicon')?>">
    
    <link href="./plugins/select2/css/select2.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/icons.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/metismenu.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/style.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/style_dark.css" rel="stylesheet" type="text/css" />
    
    <?php if ($is_rtl): ?>
        <link href="assets/css/style_rtl.css" rel="stylesheet" type="text/css" />
    <?php endif; ?>
    
    <script src="assets/js/modernizr.min.js"></script>
    <link href="assets/css/smart_request.css?v=<?= @filemtime(__DIR__ . '/assets/css/smart_request.css') ?>" rel="stylesheet" type="text/css" />
    <style>
        .sr-page .mes-search { max-width: none; }
        .sr-page .search-results { margin-top: 10px; max-height: 340px; overflow-y: auto; border: 1px solid var(--sr-border); border-radius: 12px; }
        .sr-page .search-results:empty { display: none !important; }
        .mes-emp, .supervisor-item {
            display: flex; align-items: center; gap: 10px; width: 100%; padding: 10px 12px; text-align: start; cursor: pointer;
            border: 0; border-bottom: 1px solid var(--sr-border); background: var(--sr-surface); color: var(--sr-text-2); transition: background .15s;
        }
        .mes-emp:last-child { border-bottom: 0; }
        .mes-emp:hover, .supervisor-item:hover { background: var(--sr-accent-soft); }
        .mes-emp .sr-cell-title { max-width: none; }
        .mes-empty { padding: 14px; font-size: 13px; color: var(--sr-muted); }
        .mes-current { padding: 12px 14px; border-radius: 12px; border: 1px solid var(--sr-border); background: var(--sr-surface-2); }
        .mes-list { max-height: 380px; overflow-y: auto; border: 1px solid var(--sr-border); border-radius: 12px; }
        .supervisor-item { border-radius: 0; }
        .supervisor-item:last-child { border-bottom: 0; }
        .supervisor-item.current { background: var(--tone-green-bg); }
        .supervisor-item.selected { background: var(--sr-accent); color: #fff; }
        .supervisor-item.selected .sr-cell-title, .supervisor-item.selected .sr-cell-sub { color: #fff; }
        .supervisor-item.selected .sr-avatar { background: rgba(255, 255, 255, .2); color: #fff; }
    </style>
    <script>
        window.lang = <?= json_encode($GLOBALS['translations'] ?? []) ?>;
    </script>
    <?php if ($is_rtl): ?>
        <link href="assets/css/style_rtl.css" rel="stylesheet" type="text/css" />
    <?php endif; ?>
</head>
<body class="enlarged" data-keep-enlarged="true">
    <div id="wrapper">
        <div class="left side-menu">
            <div class="slimscroll-menu" id="remove-scroll">
                <div class="topbar-left">
                    <a href="dashboard.php" class="logo">
                        <span> <img src="<?=get_setting($conDB, 'logo')?>" alt="" height="22"> </span>
                        <i> <img src="<?=get_setting($conDB, 'white_logo')?>" alt="" height="28"> </i>
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
                            <h1><?= __('manage_employee_supervisors') ?></h1>
                            <p><?= __('manage_employee_supervisors_sub', 'Change who approves the pending vacation requests of an employee.') ?></p>
                        </div>
                    </div>

                    <div class="sr-import">
                        <div class="sr-card">
                            <div class="sr-card-head">
                                <h2 class="sr-card-title"><i class="mdi mdi-account-search"></i> <?= __('search_employees_placeholder') ?> <span class="text-danger">*</span></h2>
                            </div>
                            <div class="sr-card-body">
                                <div class="sr-search mes-search">
                                    <i class="mdi mdi-magnify"></i>
                                    <input type="search" id="employee-search" placeholder="<?= __('enter_employee_name_or_id') ?>" autocomplete="off" />
                                </div>
                                <div id="search-results" class="search-results" style="display: none;"></div>
                                <p class="sr-hint mb-0"><?= __('mes_search_hint', 'Only employees with pending vacation requests are listed.') ?></p>
                            </div>
                        </div>

                        <div class="sr-card">
                            <div class="sr-card-head">
                                <h2 class="sr-card-title"><i class="mdi mdi-account-card-details"></i> <?= __('selected_employee') ?></h2>
                            </div>
                            <div class="sr-card-body">
                                <div id="employee-empty" class="sr-card-sub"><?= __('enter_employee_name_or_id') ?></div>

                                <!-- Selected Employee Info -->
                                <div id="employee-info" style="display: none;">
                                    <div class="sr-person mb-3">
                                        <span class="sr-avatar" id="selected-emp-avatar"></span>
                                        <div>
                                            <div class="sr-cell-title" id="selected-emp-name"></div>
                                            <div class="sr-cell-sub">ID: <span id="selected-emp-id"></span> &middot; <span id="selected-emp-designation"></span></div>
                                        </div>
                                    </div>

                                    <div class="sr-field-label"><?= __('current_vacation_approver') ?></div>
                                    <div class="mes-current" id="current-supervisor-info"></div>

                                    <div class="sr-import-actions">
                                        <button type="button" class="sr-btn sr-btn-primary" id="change-supervisor-btn"><i class="mdi mdi-account-switch"></i> <?= __('change_supervisor') ?></button>
                                        <button type="button" class="sr-btn sr-btn-ghost" id="clear-selection-btn"><i class="mdi mdi-close"></i> <?= __('clear') ?></button>
                                    </div>
                                </div>

                                <!-- Hidden input to store selected employee -->
                                <input type="hidden" id="selected-emp-id-value" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <footer class="footer"><?= $site_footer ?></footer>
        </div>
    </div>

    <!-- Scripts -->
    <script src="assets/js/jquery.min.js"></script>
    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/metisMenu.min.js"></script>
    <script src="assets/js/waves.js"></script>
    <script src="assets/js/jquery.slimscroll.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Select2 -->
    <script src="./plugins/select2/js/select2.min.js"></script>
    <script src="assets/js/jquery.core.js"></script>
    <script src="assets/js/jquery.app.js?t=<?= time() ?>"></script>

    <script>
        function mesEsc(v) { return $('<div>').text(v == null ? '' : String(v)).html(); }
        function mesInitials(name) {
            return String(name || '').trim().split(/\s+/).slice(0, 2).map(function(w) { return w.charAt(0); }).join('').toUpperCase();
        }

        // Employee Search
        var mesTimer = null;
        $('#employee-search').on('input', function() {
            const term = $(this).val().trim();
            clearTimeout(mesTimer);

            if (term.length < 2) {
                $('#search-results').hide().empty();
                return;
            }

            mesTimer = setTimeout(function() {
                $.ajax({
                    url: 'manage_employee_supervisors.php',
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'search_employees',
                        term: term
                    },
                    success: function(response) {
                        if (response.success && response.employees.length > 0) {
                            let html = '';
                            response.employees.forEach(emp => {
                                html += '<button type="button" class="mes-emp employee-item" data-emp-id="' + mesEsc(emp.id) + '" data-emp-name="' + mesEsc(emp.name) + '" data-emp-designation="' + mesEsc(emp.designation) + '">'
                                    + '<span class="sr-avatar sr-avatar-sm">' + mesEsc(mesInitials(emp.name)) + '</span>'
                                    + '<span class="flex-grow-1" style="min-width:0;"><span class="sr-cell-title">' + mesEsc(emp.name) + '</span>'
                                    + '<span class="sr-cell-sub">ID: ' + mesEsc(emp.emp_id) + ' &middot; ' + mesEsc(emp.designation) + '</span></span>'
                                    + '<span class="sr-pill sr-pill-xs tone-amber" title="Pending Vacation Requests"><i class="mdi mdi-calendar-clock"></i>' + mesEsc(emp.vacation_count) + '</span>'
                                    + '</button>';
                            });
                            $('#search-results').html(html).show();
                        } else {
                            $('#search-results').html('<div class="mes-empty">' + mesEsc(__('no_employees_with_active_vacation_requests_found')) + '</div>').show();
                        }
                    }
                });
            }, 250);
        });

        // Select employee from search results
        $(document).on('click', '.employee-item', function() {
            const empId = $(this).data('emp-id');
            const empName = $(this).data('emp-name');
            const empDesignation = $(this).data('emp-designation');

            $('#employee-search').val('');
            $('#search-results').hide().empty();
            $('#selected-emp-id-value').val(empId);
            $('#selected-emp-name').text(empName);
            $('#selected-emp-avatar').text(mesInitials(empName));
            $('#selected-emp-id').text(empId);
            $('#selected-emp-designation').text(empDesignation);

            // Fetch current supervisor
            fetchCurrentSupervisor(empId);

            $('#employee-empty').hide();
            $('#employee-info').show();
        });

        // Fetch current supervisor
        function fetchCurrentSupervisor(empId) {
            $('#current-supervisor-info').html('<i class="mdi mdi-spin mdi-loading"></i>');
            $.ajax({
                url: 'manage_employee_supervisors.php',
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'get_supervisors',
                    emp_id: empId
                },
                success: function(response) {
                    if (response.success) {
                        // Find and display current supervisor
                        const currentSup = response.supervisors.find(s => s.is_current);
                        if (currentSup) {
                            $('#current-supervisor-info').html(
                                '<div class="sr-person"><span class="sr-avatar sr-avatar-sm">' + mesEsc(mesInitials(currentSup.name)) + '</span>'
                                + '<div><div class="sr-cell-title">' + mesEsc(currentSup.name) + '</div>'
                                + '<div class="sr-cell-sub">' + mesEsc(currentSup.designation) + ' (' + mesEsc(currentSup.user_type) + ')</div></div></div>'
                            );
                        } else {
                            $('#current-supervisor-info').html('<span class="sr-pill tone-red"><i class="mdi mdi-alert-circle-outline"></i><?= __("no_supervisor_assigned_to_this_employee") ?></span>');
                        }
                    }
                }
            });
        }

        // Change Supervisor Button
        $('#change-supervisor-btn').on('click', function() {
            const empId = $('#selected-emp-id-value').val();

            $.ajax({
                url: 'manage_employee_supervisors.php',
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'get_supervisors',
                    emp_id: empId
                },
                success: function(response) {
                    if (response.success && response.supervisors.length > 0) {
                        showSupervisorSelector(response.supervisors, response.current_supervisor_id);
                    } else {
                        Swal.fire('<?= __("error") ?>', '<?= __("no_supervisors_available") ?>', 'error');
                    }
                }
            });
        });

        // Show supervisor selector popup
        function showSupervisorSelector(supervisors, currentSupervisorId) {
            let list = '';
            supervisors.forEach(sup => {
                const isCurrent = sup.id == currentSupervisorId;
                list += '<div class="supervisor-item' + (isCurrent ? ' current' : '') + '" data-sup-id="' + mesEsc(sup.id) + '" data-sup-name="' + mesEsc(sup.name) + '" data-sup-id-text="' + mesEsc(sup.id) + '">'
                    + '<span class="sr-avatar sr-avatar-sm">' + mesEsc(mesInitials(sup.name)) + '</span>'
                    + '<div class="flex-grow-1" style="min-width:0;"><div class="sr-cell-title">' + mesEsc(sup.name) + '</div>'
                    + '<div class="sr-cell-sub">ID: ' + mesEsc(sup.id) + ' &middot; ' + mesEsc(sup.designation) + ' (' + mesEsc(sup.user_type) + ')</div></div>'
                    + (isCurrent ? '<span class="sr-pill sr-pill-xs tone-green"><i class="mdi mdi-check"></i><?= __("current") ?></span>' : '')
                    + '</div>';
            });

            const html = '<div class="sr-page sr-form text-left">'
                + '<div class="sr-fsec"><div class="sr-fsec-head"><span><i class="mdi mdi-account-switch"></i> <?= __("select_new_vacation_approver") ?></span></div>'
                + '<div class="p-3"><div class="sr-search mb-2" style="max-width:none;"><i class="mdi mdi-magnify"></i>'
                + '<input type="search" id="supervisor-search-input" placeholder="Search by ID or Name..." autocomplete="off"></div>'
                + '<div class="mes-list" id="supervisor-list-container">' + list + '</div></div></div>'
                + '<label class="sr-check"><input type="checkbox" id="update-employee-supervisor"> <?= __("update_employee_supervisor") ?></label>'
                + '</div>';

            Swal.fire({
                title: '<?= __("select_supervisor") ?>',
                html: html,
                showCancelButton: true,
                confirmButtonText: '<i class="mdi mdi-check"></i> <?= __("confirm") ?>',
                cancelButtonText: '<?= __("cancel") ?>',
                confirmButtonColor: APP_COLORS.primary,
                cancelButtonColor: APP_COLORS.danger_dark,
                width: '620px',
                allowOutsideClick: false,
                customClass: { popup: 'sr-addline-popup' },
                didOpen: () => {
                    // Add click handler to supervisor items
                    document.querySelectorAll('.supervisor-item').forEach(item => {
                        item.addEventListener('click', function() {
                            document.querySelectorAll('.supervisor-item').forEach(i => i.classList.remove('selected'));
                            this.classList.add('selected');
                            Swal.resetValidationMessage();
                        });
                    });

                    // Add search functionality
                    const searchInput = document.getElementById('supervisor-search-input');
                    searchInput.addEventListener('input', function() {
                        const searchTerm = this.value.toLowerCase().trim();
                        document.querySelectorAll('.supervisor-item').forEach(item => {
                            const name = item.dataset.supName.toLowerCase();
                            const id = item.dataset.supIdText.toLowerCase();
                            item.style.display = (searchTerm === '' || name.includes(searchTerm) || id.includes(searchTerm)) ? '' : 'none';
                        });
                    });
                    searchInput.focus();
                },
                preConfirm: () => {
                    const selected = document.querySelector('.supervisor-item.selected');
                    if (!selected) {
                        Swal.showValidationMessage('<?= __("please_select_supervisor") ?>');
                        return false;
                    }
                    const updateEmployeeSupervisor = document.getElementById('update-employee-supervisor').checked;
                    return {
                        supervisorId: selected.dataset.supId,
                        updateEmployeeSupervisor: updateEmployeeSupervisor
                    };
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    updateSupervisor(result.value.supervisorId, result.value.updateEmployeeSupervisor);
                }
            });
        }

        // Update supervisor
        function updateSupervisor(newSupervisorId, updateEmployeeSupervisor) {
            const empId = $('#selected-emp-id-value').val();

            Swal.fire({
                title: '<?= __("processing") ?>',
                html: '<?= __("please_wait") ?>',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            $.ajax({
                url: 'manage_employee_supervisors.php',
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'update_supervisor',
                    emp_id: empId,
                    supervisor_id: newSupervisorId,
                    update_employee_supervisor: updateEmployeeSupervisor ? 1 : 0
                },
                success: function(response) {
                    if (response.success) {
                        Swal.fire({ title: '<?= __("success") ?>', text: response.message, icon: 'success', allowOutsideClick: false }).then(() => {
                            fetchCurrentSupervisor(empId);
                        });
                    } else {
                        Swal.fire('<?= __("error") ?>', response.message, 'error');
                    }
                },
                error: function() {
                    Swal.fire('<?= __("error") ?>', '<?= __("an_error_occurred") ?>', 'error');
                }
            });
        }

        // Clear selection
        $('#clear-selection-btn').on('click', function() {
            $('#employee-search').val('');
            $('#search-results').hide().empty();
            $('#employee-info').hide();
            $('#employee-empty').show();
            $('#selected-emp-id-value').val('');
        });
    </script>
</body>
</html>
