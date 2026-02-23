<?php
require_once __DIR__ . '/includes/session_check.php';

// Check authorization - only administrators and HR can access
if ($user_type != 'administrator') {
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
    <style>
        .supervisor-info-box {
            background: #f8f9fa;
            border-left: 4px solid #007bff;
            padding: 15px;
            border-radius: 4px;
            margin-top: 15px;
        }
        .supervisor-item {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .supervisor-item:hover {
            background-color: #e7f3ff;
            border-color: #007bff;
        }
        .supervisor-item.current {
            background-color: #d4edda;
            border-color: #28a745;
        }
        .supervisor-item.selected {
            background-color: #007bff;
            border-color: #007bff;
            color: white;
        }
        .supervisor-item.selected strong {
            color: white;
        }
        .supervisor-item.selected small {
            color: rgba(255, 255, 255, 0.8);
        }
        .search-results {
            max-height: 200px;
            overflow-y: auto;
        }
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
            <div class="content">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-12">
                            <div class="card-box">
                                <h4 class="card-title mb-4">
                                    <i class="fa fa-users-gear"></i> <?= __('manage_employee_supervisors') ?>
                                </h4>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="employee-search" class="font-weight-bold">
                                                <?= __('search_employees_placeholder') ?> <span class="text-danger">*</span>
                                            </label>
                                            <input type="text" id="employee-search" class="form-control" 
                                                   placeholder="<?= __('enter_employee_name_or_id') ?>"
                                                   autocomplete="off" />
                                            <div id="search-results" class="search-results" style="display: none; margin-top: 10px;"></div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Selected Employee Info -->
                                <div id="employee-info" style="display: none;">
                                    <div class="supervisor-info-box">
                                        <h5><?= __('selected_employee') ?>:</h5>
                                        <p class="mb-0">
                                            <strong id="selected-emp-name"></strong> 
                                            <small class="text-muted">(ID: <span id="selected-emp-id"></span>)</small>
                                        </p>
                                        <p class="mb-2 text-muted">
                                            <small><?= __('designation') ?>: <span id="selected-emp-designation"></span></small>
                                        </p>
                                    </div>

                                    <div class="supervisor-info-box mt-3">
                                        <h5><?= __('current_vacation_approver') ?>:</h5>
                                        <p id="current-supervisor-info" class="mb-0"></p>
                                    </div>

                                    <div class="mt-4">
                                        <button type="button" class="btn btn-info" id="change-supervisor-btn">
                                            <i class="fa fa-edit"></i> <?= __('change_supervisor') ?>
                                        </button>
                                        <button type="button" class="btn btn-secondary" id="clear-selection-btn">
                                            <i class="fa fa-times"></i> <?= __('clear') ?>
                                        </button>
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
        // Employee Search
        $('#employee-search').on('keyup', function() {
            const term = $(this).val().trim();
            
            if (term.length < 2) {
                $('#search-results').hide();
                return;
            }

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
                        let html = '<div class="list-group">';
                        response.employees.forEach(emp => {
                            html += `<button type="button" class="list-group-item list-group-item-action employee-item" 
                                            data-emp-id="${emp.id}" data-emp-name="${emp.name}" 
                                            data-emp-designation="${emp.designation}">
                                        <strong>${emp.name}</strong><br>
                                        <small class="text-muted">ID: ${emp.emp_id} - ${emp.designation}</small>
                                        <small class="badge badge-info ml-2">Pending Vacation Requests: ${emp.vacation_count}</small>
                                    </button>`;
                        });
                        html += '</div>';
                        $('#search-results').html(html).show();
                    } else {
                        $('#search-results').html('<p class="text-muted">'+ __('no_employees_with_active_vacation_requests_found') +'</p>').show();
                    }
                }
            });
        });

        // Select employee from search results
        $(document).on('click', '.employee-item', function() {
            const empId = $(this).data('emp-id');
            const empName = $(this).data('emp-name');
            const empDesignation = $(this).data('emp-designation');

            $('#employee-search').val('');
            $('#search-results').hide();
            $('#selected-emp-id-value').val(empId);
            $('#selected-emp-name').text(empName);
            $('#selected-emp-id').text(empId);
            $('#selected-emp-designation').text(empDesignation);

            // Fetch current supervisor
            fetchCurrentSupervisor(empId);

            $('#employee-info').show();
        });

        // Fetch current supervisor
        function fetchCurrentSupervisor(empId) {
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
                                `<strong>${currentSup.name}</strong><br>
                                <small class="text-muted">${currentSup.designation} (${currentSup.user_type})</small>`
                            );
                        } else {
                            $('#current-supervisor-info').html('<em class="text-danger"><?= __("no_supervisor_assigned_to_this_employee") ?></em>');
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

        // Show supervisor selector modal
        function showSupervisorSelector(supervisors, currentSupervisorId) {
            let supervisorHtml = '<div style="text-align: left;">';
            supervisorHtml += '<p class="mb-3"><?= __("select_new_vacation_approver") ?>:</p>';
            supervisorHtml += '<div class="form-group mb-3">';
            supervisorHtml += '<input type="text" id="supervisor-search-input" class="form-control" placeholder="Search by ID or Name..." style="margin-bottom: 10px;">';
            supervisorHtml += '</div>';
            supervisorHtml += '<div style="max-height: 400px; overflow-y: auto;" id="supervisor-list-container">';

            supervisors.forEach(sup => {
                const isCurrent = sup.id == currentSupervisorId;
                supervisorHtml += `
                    <div class="supervisor-item ${isCurrent ? 'current' : ''}" data-sup-id="${sup.id}" data-sup-name="${sup.name}" data-sup-id-text="${sup.id}">
                        <strong>${sup.name}</strong>
                        <br><small class="text-muted">ID: ${sup.id} - ${sup.designation} (${sup.user_type})</small>
                        ${isCurrent ? '<br><small class="text-success">✓ <?= __("current") ?></small>' : ''}
                    </div>
                `;
            });

            supervisorHtml += '</div>';
            supervisorHtml += '<div class="custom-control custom-checkbox mt-3">';
            supervisorHtml += '<input type="checkbox" class="custom-control-input" id="update-employee-supervisor" />';
            supervisorHtml += '<label class="custom-control-label" for="update-employee-supervisor">';
            supervisorHtml += '<?= __("update_employee_supervisor") ?>';
            supervisorHtml += '</label>';
            supervisorHtml += '</div></div>';

            Swal.fire({
                title: '<?= __("select_supervisor") ?>',
                html: supervisorHtml,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: '<?= __("confirm") ?>',
                cancelButtonText: '<?= __("cancel") ?>',
                width: '500px',
                didOpen: () => {
                    // Add click handler to supervisor items
                    document.querySelectorAll('.supervisor-item').forEach(item => {
                        item.addEventListener('click', function() {
                            document.querySelectorAll('.supervisor-item').forEach(i => i.classList.remove('selected'));
                            this.classList.add('selected');
                            this.dataset.selected = 'true';
                        });
                    });

                    // Add search functionality
                    const searchInput = document.getElementById('supervisor-search-input');
                    searchInput.addEventListener('keyup', function() {
                        const searchTerm = this.value.toLowerCase().trim();
                        const supervisorItems = document.querySelectorAll('.supervisor-item');
                        
                        supervisorItems.forEach(item => {
                            const name = item.dataset.supName.toLowerCase();
                            const id = item.dataset.supIdText.toLowerCase();
                            
                            if (searchTerm === '' || name.includes(searchTerm) || id.includes(searchTerm)) {
                                item.style.display = 'block';
                            } else {
                                item.style.display = 'none';
                            }
                        });
                    });
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
                        Swal.fire(
                            '<?= __("success") ?>',
                            response.message,
                            'success'
                        ).then(() => {
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
            $('#search-results').hide();
            $('#employee-info').hide();
            $('#selected-emp-id-value').val('');
        });
    </script>
</body>
</html>
