<?php

if (!function_exists('ensurePayrollApprovalTable')) {
    function ensurePayrollApprovalTable(PDO $pdo): void
    {
        $pdo->exec("CREATE TABLE IF NOT EXISTS payroll_approval_requests (
            id INT AUTO_INCREMENT PRIMARY KEY,
            request_inv_no VARCHAR(255) NOT NULL UNIQUE,
            payroll_month VARCHAR(7) NOT NULL UNIQUE,
            requested_by VARCHAR(255) DEFAULT NULL,
            approved_by VARCHAR(255) DEFAULT NULL,
            status ENUM('pending_approval','approved','rejected','completed') NOT NULL DEFAULT 'pending_approval',
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            approved_at DATETIME DEFAULT NULL,
            processed_at DATETIME DEFAULT NULL,
            processed_by VARCHAR(255) DEFAULT NULL,
            INDEX idx_payroll_month (payroll_month),
            INDEX idx_status (status),
            INDEX idx_requested_by (requested_by)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
    }
}

if (!function_exists('ensurePayrollChecklistFeedbackTable')) {
    function ensurePayrollChecklistFeedbackTable(PDO $pdo): void
    {
        $pdo->exec("CREATE TABLE IF NOT EXISTS payroll_checklist_feedback (
            id INT AUTO_INCREMENT PRIMARY KEY,
            request_inv_no VARCHAR(255) NOT NULL,
            payroll_month VARCHAR(7) NOT NULL,
            emp_id VARCHAR(50) NOT NULL,
            approver_id VARCHAR(50) NOT NULL,
            feedback_note TEXT NOT NULL,
            status ENUM('open','resolved') NOT NULL DEFAULT 'open',
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            resolved_at DATETIME DEFAULT NULL,
            resolved_by VARCHAR(50) DEFAULT NULL,
            followup_sent_at DATETIME DEFAULT NULL,
            followup_sent_by VARCHAR(50) DEFAULT NULL,
            INDEX idx_request_inv_no (request_inv_no),
            INDEX idx_payroll_month (payroll_month),
            INDEX idx_emp_id (emp_id),
            INDEX idx_status (status),
            INDEX idx_followup_sent_at (followup_sent_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

        $followupColumnStmt = $pdo->query("SHOW COLUMNS FROM payroll_checklist_feedback LIKE 'followup_sent_at'");
        $hasFollowupColumn = $followupColumnStmt && $followupColumnStmt->fetch(PDO::FETCH_ASSOC);
        if (!$hasFollowupColumn) {
            $pdo->exec("ALTER TABLE payroll_checklist_feedback
                ADD COLUMN followup_sent_at DATETIME DEFAULT NULL AFTER resolved_by,
                ADD COLUMN followup_sent_by VARCHAR(50) DEFAULT NULL AFTER followup_sent_at,
                ADD INDEX idx_followup_sent_at (followup_sent_at)");
        }
    }
}

if (!function_exists('ensurePayrollChecklistReviewTable')) {
    function ensurePayrollChecklistReviewTable(PDO $pdo): void
    {
        $pdo->exec("CREATE TABLE IF NOT EXISTS payroll_checklist_employee_checks (
            id INT AUTO_INCREMENT PRIMARY KEY,
            request_inv_no VARCHAR(255) NOT NULL,
            payroll_month VARCHAR(7) NOT NULL,
            emp_id VARCHAR(50) NOT NULL,
            approver_id VARCHAR(50) NOT NULL,
            is_checked TINYINT(1) NOT NULL DEFAULT 1,
            checked_at DATETIME DEFAULT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_payroll_emp_check (request_inv_no, payroll_month, emp_id, approver_id),
            INDEX idx_request_inv_no (request_inv_no),
            INDEX idx_payroll_month (payroll_month),
            INDEX idx_emp_id (emp_id),
            INDEX idx_approver_id (approver_id),
            INDEX idx_is_checked (is_checked)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
    }
}

if (!function_exists('ensurePayrollCompanyReportDispatchTable')) {
    function ensurePayrollCompanyReportDispatchTable(PDO $pdo): void
    {
        $pdo->exec("CREATE TABLE IF NOT EXISTS payroll_company_report_dispatch (
            id INT AUTO_INCREMENT PRIMARY KEY,
            request_inv_no VARCHAR(255) NOT NULL,
            payroll_month VARCHAR(7) NOT NULL,
            company_id VARCHAR(64) NOT NULL,
            manager_emp_id VARCHAR(50) NOT NULL,
            manager_email VARCHAR(255) NOT NULL,
            sent_by VARCHAR(50) NOT NULL,
            sent_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_company_dispatch (request_inv_no, payroll_month, company_id),
            INDEX idx_request_month (request_inv_no, payroll_month),
            INDEX idx_manager_emp_id (manager_emp_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
    }
}

if (!function_exists('ensurePayrollSupervisorReportDispatchTable')) {
    function ensurePayrollSupervisorReportDispatchTable(PDO $pdo): void
    {
        $pdo->exec("CREATE TABLE IF NOT EXISTS payroll_supervisor_report_dispatch (
            id INT AUTO_INCREMENT PRIMARY KEY,
            request_inv_no VARCHAR(255) NOT NULL,
            payroll_month VARCHAR(7) NOT NULL,
            supervisor_emp_id VARCHAR(50) NOT NULL,
            supervisor_email VARCHAR(255) NOT NULL,
            sent_by VARCHAR(50) NOT NULL,
            sent_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            included_supervisor_ids LONGTEXT DEFAULT NULL,
            merged_into_supervisor_emp_id VARCHAR(50) DEFAULT NULL,
            UNIQUE KEY uniq_supervisor_dispatch (request_inv_no, payroll_month, supervisor_emp_id),
            INDEX idx_request_month (request_inv_no, payroll_month),
            INDEX idx_supervisor_emp_id (supervisor_emp_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

        $includedColumnStmt = $pdo->query("SHOW COLUMNS FROM payroll_supervisor_report_dispatch LIKE 'included_supervisor_ids'");
        $hasIncludedColumn = $includedColumnStmt && $includedColumnStmt->fetch(PDO::FETCH_ASSOC);
        if (!$hasIncludedColumn) {
            $pdo->exec("ALTER TABLE payroll_supervisor_report_dispatch
                ADD COLUMN included_supervisor_ids LONGTEXT DEFAULT NULL AFTER sent_at,
                ADD COLUMN merged_into_supervisor_emp_id VARCHAR(50) DEFAULT NULL AFTER included_supervisor_ids");
        }
    }
}

if (!function_exists('getLatestGeneratedPayrollMonth')) {
    // The "Send Payroll Report by Direct Supervisor" pipeline reports each supervisor's
    // team using the most recently generated payroll cycle system-wide, not whichever
    // month the approval-request card it was opened from happens to be for - approval
    // can lag behind generation, and the supervisor should always get the freshest figures.
    function getLatestGeneratedPayrollMonth(PDO $pdo): string
    {
        $stmt = $pdo->query("SELECT MAX(month_year) FROM payrolls");
        return (string)($stmt->fetchColumn() ?: '');
    }
}

if (!function_exists('ensurePayrollSupervisorAssignmentsTable')) {
    // Payroll-specific "who reports this employee's payroll to whom" mapping - kept
    // separate from employees.supervisor_id (which drives vacation-approval routing in
    // manage_employee_supervisors.php) so HR can assign a payroll reporting supervisor
    // without disturbing the vacation approval chain.
    function ensurePayrollSupervisorAssignmentsTable(PDO $pdo): void
    {
        $pdo->exec("CREATE TABLE IF NOT EXISTS payroll_supervisor_assignments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            emp_id VARCHAR(50) NOT NULL,
            supervisor_emp_id VARCHAR(50) NOT NULL,
            effective_month VARCHAR(7) NOT NULL DEFAULT '2000-01',
            assigned_by VARCHAR(50) NOT NULL,
            assigned_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_emp_month_assignment (emp_id, effective_month),
            INDEX idx_supervisor_emp_id (supervisor_emp_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

        // Upgrade path for installs created before assignments became month-aware: one
        // row per employee used to mean "supervisor, always" - give existing rows a
        // sentinel effective_month low enough to still apply to every past/future month
        // until HR explicitly assigns a new supervisor from some later month onward.
        $effColumnStmt = $pdo->query("SHOW COLUMNS FROM payroll_supervisor_assignments LIKE 'effective_month'");
        $hasEffColumn = $effColumnStmt && $effColumnStmt->fetch(PDO::FETCH_ASSOC);
        if (!$hasEffColumn) {
            $pdo->exec("ALTER TABLE payroll_supervisor_assignments
                ADD COLUMN effective_month VARCHAR(7) NOT NULL DEFAULT '2000-01' AFTER supervisor_emp_id");
            $pdo->exec("ALTER TABLE payroll_supervisor_assignments DROP INDEX uniq_emp_assignment");
            $pdo->exec("ALTER TABLE payroll_supervisor_assignments ADD UNIQUE KEY uniq_emp_month_assignment (emp_id, effective_month)");
        }
    }
}

if (!function_exists('payrollSupervisorHasReportAccess')) {
    // True when this employee is the actual recipient of a "Send Payroll Report by
    // Direct Supervisor" batch for this request/month (either as the selected primary
    // supervisor, or as a supervisor whose employees were merged into another
    // supervisor's report - see sendSupervisorPayrollReport()). Rows merged into
    // someone else's batch (merged_into_supervisor_emp_id IS NOT NULL) never received
    // the email/attachment themselves, so they are excluded here.
    function payrollSupervisorHasReportAccess(PDO $pdo, string $requestInvNo, string $monthYear, string $empId): bool
    {
        if ($requestInvNo === '' || $monthYear === '' || $empId === '') {
            return false;
        }

        $stmt = $pdo->prepare("SELECT COUNT(*)
            FROM payroll_supervisor_report_dispatch
            WHERE request_inv_no = :request_inv_no
              AND payroll_month = :payroll_month
              AND supervisor_emp_id = :supervisor_emp_id
              AND merged_into_supervisor_emp_id IS NULL");
        $stmt->execute([
            ':request_inv_no' => $requestInvNo,
            ':payroll_month' => $monthYear,
            ':supervisor_emp_id' => $empId
        ]);

        return ((int)$stmt->fetchColumn() > 0);
    }
}

if (!function_exists('ensurePayrollFinanceVerificationTable')) {
    function ensurePayrollFinanceVerificationTable(PDO $pdo): void
    {
        $pdo->exec("CREATE TABLE IF NOT EXISTS payroll_finance_verification (
            id INT AUTO_INCREMENT PRIMARY KEY,
            request_inv_no VARCHAR(255) NOT NULL,
            payroll_month VARCHAR(7) NOT NULL,
            finance_manager_emp_id VARCHAR(50) NOT NULL,
            finance_officer_emp_id VARCHAR(50) NOT NULL,
            selected_company_ids LONGTEXT NOT NULL,
            selected_employee_ids LONGTEXT NOT NULL,
            is_confirmed TINYINT(1) NOT NULL DEFAULT 0,
            confirmed_at DATETIME DEFAULT NULL,
            officer_approved TINYINT(1) NOT NULL DEFAULT 0,
            officer_approved_at DATETIME DEFAULT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_finance_verification (request_inv_no, payroll_month, finance_manager_emp_id, finance_officer_emp_id),
            INDEX idx_finance_verification_request (request_inv_no, payroll_month),
            INDEX idx_finance_verification_manager (finance_manager_emp_id),
            INDEX idx_finance_verification_officer (finance_officer_emp_id),
            INDEX idx_finance_verification_confirmed (is_confirmed)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

        $approvedColumnStmt = $pdo->query("SHOW COLUMNS FROM payroll_finance_verification LIKE 'officer_approved'");
        $hasApprovedColumn = $approvedColumnStmt && $approvedColumnStmt->fetch(PDO::FETCH_ASSOC);
        if (!$hasApprovedColumn) {
            $pdo->exec("ALTER TABLE payroll_finance_verification
                ADD COLUMN officer_approved TINYINT(1) NOT NULL DEFAULT 0 AFTER confirmed_at,
                ADD COLUMN officer_approved_at DATETIME DEFAULT NULL AFTER officer_approved");
        }

        // Migrate the unique key to include finance_officer_emp_id, so a manager can have
        // several officers assigned concurrently to the same request - each keeps their own
        // row/company scope instead of every reassignment overwriting the one shared row.
        $uniqIndexStmt = $pdo->query("SHOW INDEX FROM payroll_finance_verification WHERE Key_name = 'uniq_finance_verification'");
        $uniqIndexRows = $uniqIndexStmt ? $uniqIndexStmt->fetchAll(PDO::FETCH_ASSOC) : [];
        $uniqIndexColumns = array_map(static function ($row) {
            return (string)($row['Column_name'] ?? '');
        }, $uniqIndexRows);

        if (empty($uniqIndexColumns)) {
            $pdo->exec("ALTER TABLE payroll_finance_verification
                ADD UNIQUE KEY uniq_finance_verification (request_inv_no, payroll_month, finance_manager_emp_id, finance_officer_emp_id)");
        } elseif (!in_array('finance_officer_emp_id', $uniqIndexColumns, true)) {
            $pdo->exec("ALTER TABLE payroll_finance_verification DROP INDEX uniq_finance_verification");
            $pdo->exec("ALTER TABLE payroll_finance_verification
                ADD UNIQUE KEY uniq_finance_verification (request_inv_no, payroll_month, finance_manager_emp_id, finance_officer_emp_id)");
        }
    }
}

if (!function_exists('ensurePayrollFinanceVerificationHistoryTable')) {
    function ensurePayrollFinanceVerificationHistoryTable(PDO $pdo): void
    {
        $pdo->exec("CREATE TABLE IF NOT EXISTS payroll_finance_verification_history (
            id INT AUTO_INCREMENT PRIMARY KEY,
            request_inv_no VARCHAR(255) NOT NULL,
            payroll_month VARCHAR(7) NOT NULL,
            finance_manager_emp_id VARCHAR(50) NOT NULL,
            finance_officer_emp_id VARCHAR(50) DEFAULT NULL,
            action_type VARCHAR(50) NOT NULL,
            action_note TEXT DEFAULT NULL,
            selected_company_ids LONGTEXT DEFAULT NULL,
            created_by VARCHAR(50) NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_fin_hist_request (request_inv_no, payroll_month),
            INDEX idx_fin_hist_action (action_type),
            INDEX idx_fin_hist_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
    }
}

if (!function_exists('ensurePayrollFinanceOfficerCompanyDefaultsTable')) {
    function ensurePayrollFinanceOfficerCompanyDefaultsTable(PDO $pdo): void
    {
        $pdo->exec("CREATE TABLE IF NOT EXISTS payroll_finance_officer_company_defaults (
            id INT AUTO_INCREMENT PRIMARY KEY,
            finance_manager_emp_id VARCHAR(50) NOT NULL,
            finance_officer_emp_id VARCHAR(50) NOT NULL,
            selected_company_ids LONGTEXT NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_fin_officer_company_defaults (finance_manager_emp_id, finance_officer_emp_id),
            INDEX idx_fin_officer_defaults_manager (finance_manager_emp_id),
            INDEX idx_fin_officer_defaults_officer (finance_officer_emp_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
    }
}

if (!function_exists('ensurePayrollChecklistSupportTables')) {
    function ensurePayrollChecklistSupportTables(PDO $pdo): void
    {
        ensurePayrollChecklistFeedbackTable($pdo);
        ensurePayrollChecklistReviewTable($pdo);
        ensurePayrollCompanyReportDispatchTable($pdo);
        ensurePayrollSupervisorReportDispatchTable($pdo);
        ensurePayrollFinanceVerificationTable($pdo);
        ensurePayrollFinanceVerificationHistoryTable($pdo);
        ensurePayrollFinanceOfficerCompanyDefaultsTable($pdo);
    }
}

if (!function_exists('ensurePayrollApprovalRequestType')) {
    function ensurePayrollApprovalRequestType(PDO $pdo): int
    {
        $stmt = $pdo->prepare("SELECT id FROM approval_request_types WHERE type_name = :type_name LIMIT 1");
        $stmt->execute([':type_name' => 'payroll_request']);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!empty($row['id'])) {
            return (int)$row['id'];
        }

        $insert = $pdo->prepare("INSERT INTO approval_request_types (type_name, main_table_name, description, is_default, is_active, created_at)
            VALUES (:type_name, :main_table, :description, 0, 1, NOW())");
        $insert->execute([
            ':type_name' => 'payroll_request',
            ':main_table' => 'payroll_approval_requests',
            ':description' => 'Payroll generation approval chain by payroll month'
        ]);

        return (int)$pdo->lastInsertId();
    }
}

if (!function_exists('generatePayrollApprovalInvNo')) {
    function generatePayrollApprovalInvNo(PDO $pdo, string $monthYear): string
    {
        $base = 'PAY-' . str_replace('-', '', $monthYear);
        $candidate = $base;
        $suffix = 1;

        while (true) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM payroll_approval_requests WHERE request_inv_no = :inv_no");
            $stmt->execute([':inv_no' => $candidate]);
            $exists = (int)$stmt->fetchColumn() > 0;

            if (!$exists) {
                return $candidate;
            }

            $candidate = $base . '-R' . $suffix;
            $suffix++;
        }
    }
}

if (!function_exists('getEmployeeDepartmentId')) {
    function getEmployeeDepartmentId(PDO $pdo, $empId): ?int
    {
        if (empty($empId)) {
            return null;
        }

        $stmt = $pdo->prepare("SELECT dept FROM employees WHERE emp_id = :emp_id LIMIT 1");
        $stmt->execute([':emp_id' => (string)$empId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row || $row['dept'] === null || $row['dept'] === '') {
            return null;
        }

        return (int)$row['dept'];
    }
}

if (!function_exists('getPendingPayrollApproverDetails')) {
    function getPendingPayrollApproverDetails(PDO $pdo, string $requestInvNo): ?array
    {
        $stmt = $pdo->prepare("SELECT ra.approver_id, ra.approval_level, al.email, e.name
            FROM request_approvers ra
            LEFT JOIN admin_login al ON al.emp_id = ra.approver_id
            LEFT JOIN employees e ON e.emp_id = ra.approver_id
            WHERE ra.request_inv_no = :inv_no
              AND ra.status IN ('pending', 'awaiting')
            ORDER BY ra.approval_level ASC
            LIMIT 1");
        $stmt->execute([':inv_no' => $requestInvNo]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }
}

if (!function_exists('canSeeAllPayrollEmployees')) {
    function canSeeAllPayrollEmployees(bool $useSession = true): bool
    {
        $resolvedUserType = '';

        if ($useSession && isset($_SESSION['user_type'])) {
            $resolvedUserType = strtolower(trim((string)$_SESSION['user_type']));
        } elseif (isset($GLOBALS['user_type'])) {
            $resolvedUserType = strtolower(trim((string)$GLOBALS['user_type']));
        }

        if (function_exists('isHeadOfficeFinanceOfficer') && isHeadOfficeFinanceOfficer($useSession)) {
            return true;
        }

        return function_exists('canSeeAllEmployeesByRole')
            ? canSeeAllEmployeesByRole($useSession)
            : (
                ($GLOBALS['is_system_admin'] ?? false)
                || $resolvedUserType === 'administrator'
                || (($GLOBALS['user_dept'] ?? null) == 5)
                || ($GLOBALS['isHR'] ?? false)
                || ($GLOBALS['isDeptHr'] ?? false)
            );
    }
}

if (!function_exists('isHeadOfficeFinanceOfficer')) {
    function isHeadOfficeFinanceOfficer(bool $useSession = true): bool
    {
        global $conDB;

        $resolvedUserType = '';
        if ($useSession && isset($_SESSION['user_type'])) {
            $resolvedUserType = strtolower(trim((string)$_SESSION['user_type']));
        } elseif (isset($GLOBALS['user_type'])) {
            $resolvedUserType = strtolower(trim((string)$GLOBALS['user_type']));
        }

        $resolvedAdminDept = null;
        if ($useSession && isset($_SESSION['user_dept'])) {
            $resolvedAdminDept = (int)$_SESSION['user_dept'];
        } elseif (isset($GLOBALS['user_dept'])) {
            $resolvedAdminDept = (int)$GLOBALS['user_dept'];
        }

        if ($resolvedUserType !== 'finance_officer' || $resolvedAdminDept !== 2) {
            return false;
        }

        $resolvedEmpId = '';
        if ($useSession && isset($_SESSION['empid'])) {
            $resolvedEmpId = trim((string)$_SESSION['empid']);
        } elseif (isset($GLOBALS['empid'])) {
            $resolvedEmpId = trim((string)$GLOBALS['empid']);
        }

        if ($resolvedEmpId === '' || !($conDB instanceof mysqli)) {
            return false;
        }

        $stmt = $conDB->prepare("SELECT sectin_nme FROM employees WHERE emp_id = ? LIMIT 1");
        if (!$stmt) {
            return false;
        }

        $stmt->bind_param('s', $resolvedEmpId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result ? $result->fetch_assoc() : null;
        $stmt->close();

        return (int)($row['sectin_nme'] ?? 0) === 3;
    }
}

if (!function_exists('notifyPayrollApprovalApprover')) {
    function notifyPayrollApprovalApprover($conDB, PDO $pdo, string $requestInvNo, string $monthYear): bool
    {
        $nextApprover = getPendingPayrollApproverDetails($pdo, $requestInvNo);
        if (empty($nextApprover['approver_id'])) {
            return false;
        }

        if (function_exists('create_and_show_notification')) {
            create_and_show_notification(
                $conDB,
                $nextApprover['approver_id'],
                'Payroll Requires Your Approval',
                'Payroll ' . $monthYear . ' is awaiting your approval at level ' . (int)($nextApprover['approval_level'] ?? 1) . '.',
                'all_payroll_approvals.php?status=my_pending',
                'info'
            );
        }

        if (empty($nextApprover['email']) || !function_exists('send_approval_email')) {
            return false;
        }

        // Matches the Payroll Checklist Report scope: no status filter, excludes Hold employees (payment_type = 3).
        $summaryStmt = $pdo->prepare("SELECT COUNT(*) AS employee_count, COALESCE(SUM(p.net_salary), 0) AS total_net_salary
            FROM payrolls p
            INNER JOIN employees e ON e.emp_id = p.emp_id
            WHERE p.month_year = :month_year
              AND COALESCE(e.payment_type, 1) <> 3");
        $summaryStmt->execute([':month_year' => $monthYear]);
        $summary = $summaryStmt->fetch(PDO::FETCH_ASSOC) ?: [];

        $emailSubject = 'Payroll Approval Required - ' . $monthYear . ' (' . $requestInvNo . ')';
        $templateData = [
            'APPROVER_NAME' => !empty($nextApprover['name']) ? htmlspecialchars($nextApprover['name']) : 'Approver',
            'REQUEST_ID' => $requestInvNo,
            'REQUEST_TYPE' => 'Payroll Approval',
            'EMAIL_MESSAGE' => 'A payroll request is awaiting your approval at level ' . (int)($nextApprover['approval_level'] ?? 1) . '. Please review and take appropriate action.',
            'PAYROLL_MONTH' => $monthYear,
            'EMPLOYEE_COUNT' => (string)($summary['employee_count'] ?? '0'),
            'TOTAL_NET_SALARY' => number_format((float)($summary['total_net_salary'] ?? 0), 2),
            'REQUEST_URL' => (function_exists('get_base_url') ? get_base_url() : 'https://hr.almutlaksystem.com') . '/all_payroll_approvals.php?status=my_pending'
        ];

        return (bool)send_approval_email(
            $conDB,
            $nextApprover['email'],
            $nextApprover['name'] ?? 'Approver',
            $emailSubject,
            'payroll_request',
            $templateData
        );
    }
}

if (!function_exists('removePayrollFinanceOfficerStep')) {
    function removePayrollFinanceOfficerStep(PDO $pdo, string $requestInvNo): int
    {
        if ($requestInvNo === '') {
            return 0;
        }

        $deleteStmt = $pdo->prepare("DELETE ra FROM request_approvers ra
            INNER JOIN admin_login al ON al.emp_id = ra.approver_id
            INNER JOIN approval_request_types art ON art.id = ra.request_type_id
            WHERE ra.request_inv_no = :inv_no
              AND art.type_name = 'payroll_request'
              AND LOWER(TRIM(al.user_type)) = 'finance_officer'");
        $deleteStmt->execute([':inv_no' => $requestInvNo]);

        return (int)$deleteStmt->rowCount();
    }
}

