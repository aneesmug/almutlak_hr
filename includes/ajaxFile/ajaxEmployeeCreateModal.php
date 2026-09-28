<?php
// Backend for the "Add New Employee" SweetAlert2 modal (assets/js/newEmployeeModal.js).
// Deliberately self-contained per-action (mirrors new_comp_employee.php / new_mnpow_employee.php's
// own style, rather than a shared refactor) - the two create actions are line-for-line copies of
// those two pages' existing insert logic, just returning JSON instead of redirecting.
header('Content-Type: application/json');
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../session_check.php';

$action = $_POST['action'] ?? '';

function ecm_rows(mysqli $conDB, string $sql): array
{
    $rows = [];
    $result = mysqli_query($conDB, $sql);
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $rows[] = $row;
        }
    }
    return $rows;
}

// --- Draft employees ---------------------------------------------------------------------
// Employees whose information isn't complete yet are kept in `employee_drafts` (shared by all
// HR users) until registered. Each draft reserves its emp_id: new forms get the next number
// after both the last employee and the last draft, and registering an employee with an ID
// another draft holds is refused. Registering a draft deletes its row.
function ecm_ensure_drafts_table(PDO $pdo): void
{
    $pdo->exec("CREATE TABLE IF NOT EXISTS `employee_drafts` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `emp_id` VARCHAR(50) NOT NULL,
        `emp_type` ENUM('company','man_power') NOT NULL DEFAULT 'company',
        `name` VARCHAR(255) DEFAULT NULL,
        `form_data` LONGTEXT NOT NULL,
        `is_saved` TINYINT(1) NOT NULL DEFAULT 0,
        `created_by` VARCHAR(255) DEFAULT NULL,
        `created_by_name` VARCHAR(255) DEFAULT NULL,
        `updated_by` VARCHAR(255) DEFAULT NULL,
        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `uq_emp_id` (`emp_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

function ecm_next_emp_id(PDO $pdo): int
{
    $stmt = $pdo->query("SELECT `emp_id` FROM `employees` ORDER BY `emp_id` DESC LIMIT 1");
    $lastEmpId = (int)($stmt->fetch(PDO::FETCH_ASSOC)['emp_id'] ?? 0);
    $lastDraftId = (int)$pdo->query("SELECT MAX(CAST(`emp_id` AS UNSIGNED)) FROM `employee_drafts`")->fetchColumn();
    return max($lastEmpId, $lastDraftId) + 1;
}

function ecm_emp_id_registered(PDO $pdo, string $empId): bool
{
    $stmt = $pdo->prepare("SELECT 1 FROM `employees` WHERE `emp_id` = ? LIMIT 1");
    $stmt->execute([$empId]);
    return (bool)$stmt->fetchColumn();
}

// Name of whoever holds $empId in another draft (not $ownDraftId), or null when it's free.
function ecm_emp_id_reserved_by(PDO $pdo, string $empId, int $ownDraftId): ?string
{
    $stmt = $pdo->prepare("SELECT COALESCE(NULLIF(`name`, ''), `emp_id`) FROM `employee_drafts` WHERE `emp_id` = ? AND `id` <> ? LIMIT 1");
    $stmt->execute([$empId, $ownDraftId]);
    $holder = $stmt->fetchColumn();
    return $holder === false ? null : (string)$holder;
}

function ecm_remove_draft(PDO $pdo, int $draftId): void
{
    if ($draftId > 0) {
        $pdo->prepare("DELETE FROM `employee_drafts` WHERE `id` = ?")->execute([$draftId]);
    }
}

$currentUserId = (string)($empid ?? ($_SESSION['auth_user']['user_id'] ?? ''));
$currentUserName = (string)($fname ?? '');

if ($action === 'get_form_data') {
    ecm_ensure_drafts_table($pdo);
    // `taken` = the draft's ID got registered meanwhile (e.g. from another page) - it gets a
    // fresh ID when opened (see draft_get).
    $drafts = $pdo->query("SELECT d.`id`, d.`emp_id`, d.`emp_type`, d.`name`, d.`is_saved`, d.`created_by_name`, d.`updated_at`,
                                  EXISTS(SELECT 1 FROM `employees` e WHERE e.`emp_id` = d.`emp_id`) AS `taken`
                           FROM `employee_drafts` d ORDER BY d.`updated_at` DESC")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($drafts as &$draftRow) {
        $draftRow['created_by_name'] = $draftRow['created_by_name'] ? parseName($draftRow['created_by_name']) : '';
    }
    unset($draftRow);

    echo json_encode([
        'status' => 'success',
        'next_emp_id' => (string)ecm_next_emp_id($pdo),
        'drafts' => $drafts,
        'countries' => ecm_rows($conDB, "SELECT `id`, `name` FROM `countries` ORDER BY `name` REGEXP '^[^A-Za-z]' ASC, `name`"),
        'departments' => ecm_rows($conDB, "SELECT `id`, `dep_nme`, `dep_nme_ar` FROM `department` ORDER BY `dep_nme` REGEXP '^[^A-Za-z]' ASC, `dep_nme`"),
        'companies' => ecm_rows($conDB, "SELECT `comp_id`, `comp_name`, `comp_name_ar` FROM `companies` ORDER BY `comp_name` REGEXP '^[^A-Za-z]' ASC, `comp_name`"),
        'cities' => ecm_rows($conDB, "SELECT `id`, `name_en`, `name_ar` FROM `saudi_cities` ORDER BY `name_en` ASC"),
        'banks' => ecm_rows($conDB, "SELECT `id`, `name`, `bank_name_ar` FROM `bank_list` ORDER BY `name` REGEXP '^[^A-Za-z]' ASC, `name`"),
        'jobs' => ecm_rows($conDB, "SELECT `id`, `job`, `job_ar` FROM `ac_jobs` ORDER BY `job` REGEXP '^[^A-Za-z]' ASC, `job`"),
        'contract_periods' => ecm_rows($conDB, "SELECT `id`, `period` FROM `contract_period` ORDER BY `period` REGEXP '^[^A-Za-z]' ASC, `period`"),
        'sponsorships' => ecm_rows($conDB, "SELECT `id`, `sponsor`, `sponsor_ar` FROM `sponsorship` ORDER BY `sponsor` REGEXP '^[^A-Za-z]' ASC, `sponsor`"),
        'supervisors' => array_map(function ($row) {
            $row['name'] = parseName($row['name']);
            return $row;
        }, ecm_rows($conDB, "SELECT DISTINCT e.id, e.emp_id, e.name, e.emptype FROM `employees` e WHERE e.status = 1 AND (e.emptype = 'Manager' OR e.emptype = 'Supervisor') ORDER BY e.name")),
        'all_locations' => ecm_rows($conDB, "SELECT `id`, `city_id`, `name_en`, `name_ar` FROM `locations` ORDER BY `name_en` ASC"),
        'all_sub_departments' => ecm_rows($conDB, "SELECT `id`, `department_id`, `name_en`, `name_ar` FROM `sub_departments` ORDER BY `name_en` ASC"),
    ]);
    exit;
}

if ($action === 'draft_save') {
    ecm_ensure_drafts_table($pdo);
    $draftId = (int)($_POST['draft_id'] ?? 0);
    $empType = ($_POST['emp_type'] ?? '') === 'man_power' ? 'man_power' : 'company';
    $isSaved = !empty($_POST['is_saved']) ? 1 : 0;
    $form = json_decode($_POST['form_data'] ?? '', true);
    if (!is_array($form)) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid draft data.']);
        exit;
    }
    $requestedEmpId = preg_replace('/[^0-9]/', '', (string)($form['emp_id'] ?? ''));
    $name = mb_substr(trim((string)($form['name'] ?? '')), 0, 255);

    // A few attempts in case another user grabs the same next ID between our check and insert
    // (the UNIQUE key on emp_id rejects the second one).
    for ($attempt = 0; $attempt < 3; $attempt++) {
        try {
            $pdo->beginTransaction();
            $currentEmpId = null;
            if ($draftId > 0) {
                $cur = $pdo->prepare("SELECT `emp_id` FROM `employee_drafts` WHERE `id` = ? FOR UPDATE");
                $cur->execute([$draftId]);
                $currentEmpId = $cur->fetchColumn();
                if ($currentEmpId === false) {
                    // Deleted meanwhile - keep the typed data as a new draft instead of losing it.
                    $draftId = 0;
                    $currentEmpId = null;
                }
            }

            if ($requestedEmpId !== '' && !ecm_emp_id_registered($pdo, $requestedEmpId) && ecm_emp_id_reserved_by($pdo, $requestedEmpId, $draftId) === null) {
                $empId = $requestedEmpId;
            } elseif ($currentEmpId !== null && !ecm_emp_id_registered($pdo, (string)$currentEmpId)) {
                $empId = (string)$currentEmpId;
            } else {
                $empId = (string)ecm_next_emp_id($pdo);
            }
            $form['emp_id'] = $empId;
            $formJson = json_encode($form, JSON_UNESCAPED_UNICODE);

            if ($draftId > 0) {
                $pdo->prepare("UPDATE `employee_drafts` SET `emp_id` = ?, `emp_type` = ?, `name` = ?, `form_data` = ?,
                               `is_saved` = GREATEST(`is_saved`, ?), `updated_by` = ? WHERE `id` = ?")
                    ->execute([$empId, $empType, $name, $formJson, $isSaved, $currentUserId, $draftId]);
            } else {
                $pdo->prepare("INSERT INTO `employee_drafts` (`emp_id`, `emp_type`, `name`, `form_data`, `is_saved`, `created_by`, `created_by_name`, `updated_by`)
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?)")
                    ->execute([$empId, $empType, $name, $formJson, $isSaved, $currentUserId, $currentUserName, $currentUserId]);
                $draftId = (int)$pdo->lastInsertId();
            }
            $pdo->commit();
            echo json_encode(['status' => 'success', 'draft_id' => $draftId, 'emp_id' => $empId]);
            exit;
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            if ($e->getCode() !== '23000' || $attempt === 2) {
                echo json_encode(['status' => 'error', 'message' => __('database_error') . ': ' . $e->getMessage()]);
                exit;
            }
            $requestedEmpId = ''; // duplicate emp_id - retry with the next free one
        }
    }
    exit;
}

if ($action === 'draft_get') {
    ecm_ensure_drafts_table($pdo);
    $draftId = (int)($_POST['draft_id'] ?? 0);
    $stmt = $pdo->prepare("SELECT * FROM `employee_drafts` WHERE `id` = ?");
    $stmt->execute([$draftId]);
    $draft = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$draft) {
        echo json_encode(['status' => 'error', 'message' => __('draft_not_found', 'This draft no longer exists.')]);
        exit;
    }
    // Its ID got registered meanwhile - move the draft to the next free ID.
    if (ecm_emp_id_registered($pdo, (string)$draft['emp_id'])) {
        $draft['emp_id'] = (string)ecm_next_emp_id($pdo);
        $pdo->prepare("UPDATE `employee_drafts` SET `emp_id` = ? WHERE `id` = ?")->execute([$draft['emp_id'], $draftId]);
    }
    $form = json_decode($draft['form_data'], true) ?: [];
    $form['emp_id'] = $draft['emp_id'];
    echo json_encode([
        'status' => 'success',
        'draft_id' => (int)$draft['id'],
        'emp_type' => $draft['emp_type'],
        'is_saved' => (int)$draft['is_saved'],
        'form_data' => $form,
    ]);
    exit;
}

if ($action === 'draft_delete') {
    ecm_ensure_drafts_table($pdo);
    ecm_remove_draft($pdo, (int)($_POST['draft_id'] ?? 0));
    echo json_encode(['status' => 'success']);
    exit;
}

if ($action === 'create_company_employee') {
    // --- exact whitelist/clean-rule/insert logic from new_comp_employee.php ---
    $allowedColumns = [
        'name', 'emp_id', 'iqama', 'iqama_exp', 'passport_number',
        'passport_exp', 'mobile', 'emg_mobile', 'emg_name', 'country', 'dept',
        'city_id', 'location_id', 'sub_dept_id', 'emptype', 'supervisor_id', 'joining_date', 'dob', 'dob_h', 't_shirt_size',
        'sex', 'mar_status', 'blood_type', 'emp_sup_type', 'actual_Job', 'vac_period',
        'vacation_days', 'salary', 'bank_name', 'iban', 'email', 'address',
        'iqama_exp_g', 'gosi',
        'probation', 'payment_type', 'created_at', 'fly', 'comp_no', 'avatar'
    ];
    $cleanRules = [
        'emp_id' => fn($v) => str_replace(',', '', $v),
        'salary' => fn($v) => str_replace(',', '', $v),
        'iban' => fn($v) => str_replace(' ', '', $v),
        'mobile' => fn($v) => preg_replace('/[^0-9]/', '', explode(' ', $v)[0]),
        'email' => fn($v) => filter_var(trim($v), FILTER_SANITIZE_EMAIL),
        'created_at' => fn($v) => date('Y-m-d H:i:s')
    ];

    $data = $_POST;
    unset($data['action']);
    $data['created_at'] = date('Y-m-d H:i:s');
    $data['fly'] = 0;
    $data['dept'] = $data['department'] ?? null;
    unset($data['department']);
    $data['avatar'] = ($data['sex'] == 1) ? "./assets/emp_pics/defult.png" : "./assets/emp_pics/defultFemale.jpg";

    try {
        $columns = [];
        $placeholders = [];
        $values = [];
        foreach ($data as $column => $value) {
            if (!in_array($column, $allowedColumns)) continue;
            $cleanValue = isset($cleanRules[$column]) ? $cleanRules[$column]($value) : trim($value);
            $columns[] = "`$column`";
            $placeholders[] = ":$column";
            $values[":$column"] = $cleanValue;
        }

        $requiredFields = ['name', 'emp_id', 'iqama', 'mobile', 'salary'];
        foreach ($requiredFields as $field) {
            if (empty($values[":$field"])) {
                throw new Exception("$field is required");
            }
        }
        if (!is_numeric($values[':emp_id'])) {
            throw new Exception(__('employee_id_must_be_numeric'));
        }
        if (!is_numeric($values[':salary'])) {
            throw new Exception(__('salary_must_be_numeric'));
        }
        $dupStmt = $pdo->prepare("SELECT 1 FROM `employees` WHERE `emp_id` = ? LIMIT 1");
        $dupStmt->execute([$values[':emp_id']]);
        if ($dupStmt->fetchColumn()) {
            throw new Exception("This employee no. (\"{$values[':emp_id']}\") is already registered!");
        }
        ecm_ensure_drafts_table($pdo);
        $draftId = (int)($_POST['draft_id'] ?? 0);
        $reservedBy = ecm_emp_id_reserved_by($pdo, (string)$values[':emp_id'], $draftId);
        if ($reservedBy !== null) {
            throw new Exception("This employee no. (\"{$values[':emp_id']}\") is reserved by the draft of $reservedBy!");
        }

        $sql = "INSERT INTO `employees` (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($values);

        $select_stmt = $pdo->prepare("SELECT `id` FROM `employees` WHERE `emp_id` = ? ORDER BY `id` DESC LIMIT 1");
        $select_stmt->execute([$values[':emp_id']]);
        $inserted_employee = $select_stmt->fetch(PDO::FETCH_ASSOC);
        $inserted_emp_id = $inserted_employee['id'] ?? null;

        ecm_remove_draft($pdo, $draftId);

        ActivityLogger::logCreate(
            'Employee',
            'ajaxEmployeeCreateModal.php',
            $inserted_emp_id,
            $values,
            "Created new company employee: " . $values[':name'],
            'employees'
        );

        // --- notifications (same as new_comp_employee.php) ---
        // notify_hr_gr_new_employee() sends one SMTP email per HR/GR recipient sequentially,
        // which can exceed db.php's global 25s max_execution_time once there are several
        // recipients - the employee row above is already inserted by this point, so a
        // timeout here would wrongly report failure on an already-successful registration.
        @set_time_limit(120);
        $employee_notification_data = [
            'emp_id' => $values[':emp_id'],
            'name' => $values[':name'],
            'email' => $values[':email'] ?? '',
            'mobile' => $values[':mobile'] ?? '',
            'iqama' => $values[':iqama'] ?? '',
            'department_name' => '',
            'job_title' => '',
            'joining_date' => $values[':joining_date'] ?? date('Y-m-d'),
            'salary' => $values[':salary'] ?? 0,
            'comp_name' => ''
        ];

        if (!empty($values[':dept'])) {
            $dept_stmt = $pdo->prepare("SELECT dep_nme_ar FROM `department` WHERE `id` = ? LIMIT 1");
            $dept_stmt->execute([$values[':dept']]);
            $dept_row = $dept_stmt->fetch(PDO::FETCH_ASSOC);
            if ($dept_row) {
                $employee_notification_data['department_name'] = $dept_row['dep_nme_ar'] ?? $dept_row['dep_nme'] ?? '';
            }
        }
        if (!empty($values[':actual_Job'])) {
            $job_stmt = $pdo->prepare("SELECT job_ar FROM `ac_jobs` WHERE `id` = ? LIMIT 1");
            $job_stmt->execute([$values[':actual_Job']]);
            $job_row = $job_stmt->fetch(PDO::FETCH_ASSOC);
            if ($job_row) {
                $employee_notification_data['job_title'] = $job_row['job_ar'] ?? $job_row['job'] ?? '';
            }
        }
        if (!empty($values[':comp_no'])) {
            $comp_stmt = $pdo->prepare("SELECT comp_name FROM `companies` WHERE `comp_id` = ? LIMIT 1");
            $comp_stmt->execute([$values[':comp_no']]);
            $comp_row = $comp_stmt->fetch(PDO::FETCH_ASSOC);
            if ($comp_row) {
                $employee_notification_data['comp_name'] = $comp_row['comp_name'] ?? '';
            }
        }
        notify_hr_gr_new_employee($conDB, $employee_notification_data);

        if (!empty($values[':supervisor_id'])) {
            $supervisor_stmt = $pdo->prepare("SELECT al.email, e.name FROM `employees` e
                                JOIN `admin_login` al ON e.emp_id = al.emp_id
                                WHERE e.`emp_id` = ? LIMIT 1");
            $supervisor_stmt->execute([$values[':supervisor_id']]);
            $supervisor_row = $supervisor_stmt->fetch(PDO::FETCH_ASSOC);
            if ($supervisor_row && !empty($supervisor_row['email'])) {
                $supervisor_template_data = [
                    'APPROVER_NAME' => $supervisor_row['name'] ?? 'Unknown Supervisor',
                    'EMPLOYEE_NAME' => $values[':name'],
                    'EMPLOYEE_ID' => $values[':emp_id'],
                    'IQAMA_NUMBER' => $values[':iqama'] ?? 'N/A',
                    'EMPLOYEE_EMAIL' => $values[':email'] ?? 'N/A',
                    'EMPLOYEE_MOBILE' => $values[':mobile'] ?? 'N/A',
                    'DEPARTMENT_NAME' => $employee_notification_data['department_name'],
                    'JOB_TITLE' => $employee_notification_data['job_title'] ?? 'N/A',
                    'JOINING_DATE' => $values[':joining_date'] ?? date('Y-m-d'),
                    'SALARY' => number_format($values[':salary'] ?? 0, 2, '.', ','),
                    'COMPANY_NAME' => $employee_notification_data['comp_name'] ?? 'N/A',
                    'REQUEST_URL' => get_base_url() . '/view_employee.php?emp_id=' . $inserted_emp_id,
                    'ALL_EMPLOYEES_URL' => get_base_url() . '/reg_employee.php'
                ];
                send_approval_email(
                    $conDB,
                    $supervisor_row['email'],
                    $supervisor_row['name'],
                    "New Team Member: " . $values[':name'],
                    'new_employee',
                    $supervisor_template_data
                );
            }
        }

        echo json_encode(['status' => 'success', 'emp_id' => $values[':emp_id']]);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => __('database_error') . ': ' . $e->getMessage()]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

if ($action === 'create_man_power_employee') {
    // --- exact insert/upload logic from new_mnpow_employee.php ---
    $name_emp = trim($_POST['name'] ?? '');
    $emp_id = trim($_POST['emp_id'] ?? '');
    $iqama = trim($_POST['iqama'] ?? '');
    $mobile = trim($_POST['mobile'] ?? '');
    $salary = filter_var($_POST['salary'] ?? 0, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $joining_date = trim($_POST['joining_date'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $comp_no = trim($_POST['comp_no'] ?? '');
    $city_id = !empty($_POST['city_id']) ? (int)$_POST['city_id'] : null;
    $location_id = !empty($_POST['location_id']) ? (int)$_POST['location_id'] : null;
    $sub_dept_id = !empty($_POST['sub_dept_id']) ? (int)$_POST['sub_dept_id'] : null;
    $country = trim($_POST['country'] ?? '');
    $dob = trim($_POST['dob'] ?? '');
    $sex = trim($_POST['sex'] ?? 'male');
    $iqama_exp_g = trim($_POST['iqama_exp_g'] ?? '');
    $emp_sup_type = "man_power";
    $created_at = date('Y-m-d H:i:s');
    $image_path = '';

    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['avatar'];
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $file_type = mime_content_type($file['tmp_name']);

        if (in_array($file_type, $allowed_types)) {
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $safe_iqama = preg_replace('/[^a-zA-Z0-9_-]/', '', $iqama);
            $image_path = "./assets/emp_pics/" . $safe_iqama . "_" . time() . "." . $extension;

            if (!move_uploaded_file($file['tmp_name'], $image_path)) {
                $image_path = '';
            }
        }
    }

    if (empty($image_path)) {
        $image_path = ($sex === "male") ? "./assets/emp_pics/defult.png" : "./assets/emp_pics/defultFemale.jpg";
    }

    if (empty($name_emp) || empty($emp_id) || empty($iqama) || empty($department) || empty($comp_no) || empty($salary)) {
        echo json_encode(['status' => 'error', 'message' => 'Please fill out all required fields in this form!']);
        exit;
    }

    $stmt_check = $conDB->prepare("SELECT `emp_id` FROM `employees` WHERE `emp_id` = ?");
    $stmt_check->bind_param("s", $emp_id);
    $stmt_check->execute();
    $stmt_check->store_result();

    if ($stmt_check->num_rows > 0) {
        echo json_encode(['status' => 'error', 'message' => "This employee no. (\"$emp_id\") is already registered!"]);
        $stmt_check->close();
        exit;
    }

    ecm_ensure_drafts_table($pdo);
    $draftId = (int)($_POST['draft_id'] ?? 0);
    $reservedBy = ecm_emp_id_reserved_by($pdo, $emp_id, $draftId);
    if ($reservedBy !== null) {
        echo json_encode(['status' => 'error', 'message' => "This employee no. (\"$emp_id\") is reserved by the draft of $reservedBy!"]);
        $stmt_check->close();
        exit;
    }

    // NOTE: new_mnpow_employee.php's original INSERT has two bugs (pre-existing, verified
    // against live schema - left untouched in that legacy page per plan scope, fixed here
    // since they'd otherwise make every employee created through this modal broken):
    //  1. References a `date_reg` column that doesn't exist in `employees` - omitted here,
    //     replaced with an explicit `created_at` (the column has no default of its own).
    //  2. Inserts the string 'active' into `status`, an int column - MySQL coerces that to
    //     0 (inactive), so every man-power employee created via the original page ends up
    //     inactive. Inserts 1 here instead, matching what "active" is meant to mean.
    $sql = "INSERT INTO `employees` (`name`, `emp_id`, `iqama`, `mobile`, `salary`, `joining_date`, `created_at`, `status`, `avatar`, `fly`, `dept`, `comp_no`, `city_id`, `location_id`, `sub_dept_id`, `country`, `dob`, `sex`, `emp_sup_type`, `iqama_exp_g`)
            VALUES (?, ?, ?, ?, ?, ?, ?, 1, ?, 'no', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt_insert = $conDB->prepare($sql);
    $stmt_insert->bind_param(
        "ssssdsssssiiisssss",
        $name_emp,
        $emp_id,
        $iqama,
        $mobile,
        $salary,
        $joining_date,
        $created_at,
        $image_path,
        $department,
        $comp_no,
        $city_id,
        $location_id,
        $sub_dept_id,
        $country,
        $dob,
        $sex,
        $emp_sup_type,
        $iqama_exp_g
    );

    if ($stmt_insert->execute()) {
        ecm_remove_draft($pdo, $draftId);
        ActivityLogger::logCreate(
            'Employee',
            'ajaxEmployeeCreateModal.php',
            $emp_id,
            [
                'name' => $name_emp,
                'emp_id' => $emp_id,
                'iqama' => $iqama,
                'mobile' => $mobile,
                'salary' => $salary,
                'joining_date' => $joining_date,
                'department' => $department,
                'emp_sup_type' => $emp_sup_type,
                'country' => $country,
                'dob' => $dob
            ],
            "Created new manpower employee: $name_emp",
            'employees'
        );
        echo json_encode(['status' => 'success', 'emp_id' => $emp_id]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Error: Could not add employee.']);
    }
    $stmt_insert->close();
    $stmt_check->close();
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Unknown action.']);
