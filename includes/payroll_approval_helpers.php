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
            UNIQUE KEY uniq_finance_verification (request_inv_no, payroll_month, finance_manager_emp_id),
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

        // Normalize any legacy duplicate rows to a single latest row per manager/request/month.
        $duplicateGroupsStmt = $pdo->query("SELECT request_inv_no, payroll_month, finance_manager_emp_id, COUNT(*) AS row_count
            FROM payroll_finance_verification
            GROUP BY request_inv_no, payroll_month, finance_manager_emp_id
            HAVING COUNT(*) > 1");
        $duplicateGroups = $duplicateGroupsStmt ? $duplicateGroupsStmt->fetchAll(PDO::FETCH_ASSOC) : [];

        foreach ($duplicateGroups as $group) {
            $requestInvNo = (string)($group['request_inv_no'] ?? '');
            $payrollMonth = (string)($group['payroll_month'] ?? '');
            $managerEmpId = (string)($group['finance_manager_emp_id'] ?? '');

            if ($requestInvNo === '' || $payrollMonth === '' || $managerEmpId === '') {
                continue;
            }

            $rowsStmt = $pdo->prepare("SELECT id, finance_officer_emp_id, selected_company_ids, selected_employee_ids
                FROM payroll_finance_verification
                WHERE request_inv_no = :inv_no
                  AND payroll_month = :month_year
                  AND finance_manager_emp_id = :manager_id
                ORDER BY id ASC");
            $rowsStmt->execute([
                ':inv_no' => $requestInvNo,
                ':month_year' => $payrollMonth,
                ':manager_id' => $managerEmpId
            ]);
            $rows = $rowsStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
            if (count($rows) <= 1) {
                continue;
            }

            $mergedCompanyIds = [];
            $seenCompanyIds = [];
            $mergedEmployeeIds = [];
            $seenEmployeeIds = [];

            foreach ($rows as $row) {
                $companyIds = json_decode((string)($row['selected_company_ids'] ?? '[]'), true);
                if (!is_array($companyIds)) {
                    $companyIds = [];
                }

                foreach ($companyIds as $companyIdRaw) {
                    $companyId = trim((string)$companyIdRaw);
                    if ($companyId === '' || isset($seenCompanyIds[$companyId])) {
                        continue;
                    }
                    $seenCompanyIds[$companyId] = true;
                    $mergedCompanyIds[] = $companyId;
                }

                $employeeIds = json_decode((string)($row['selected_employee_ids'] ?? '[]'), true);
                if (!is_array($employeeIds)) {
                    $employeeIds = [];
                }

                foreach ($employeeIds as $employeeIdRaw) {
                    $employeeId = trim((string)$employeeIdRaw);
                    if ($employeeId === '' || isset($seenEmployeeIds[$employeeId])) {
                        continue;
                    }
                    $seenEmployeeIds[$employeeId] = true;
                    $mergedEmployeeIds[] = $employeeId;
                }
            }

            $keeperRow = end($rows);
            $keeperId = (int)($keeperRow['id'] ?? 0);
            if ($keeperId <= 0) {
                continue;
            }

            $updateKeeperStmt = $pdo->prepare("UPDATE payroll_finance_verification
                SET selected_company_ids = :selected_company_ids,
                    selected_employee_ids = :selected_employee_ids
                WHERE id = :id");
            $updateKeeperStmt->execute([
                ':selected_company_ids' => json_encode($mergedCompanyIds, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                ':selected_employee_ids' => json_encode($mergedEmployeeIds, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                ':id' => $keeperId
            ]);

            $deleteDuplicateStmt = $pdo->prepare("DELETE FROM payroll_finance_verification
                WHERE request_inv_no = :inv_no
                  AND payroll_month = :month_year
                  AND finance_manager_emp_id = :manager_id
                  AND id <> :keeper_id");
            $deleteDuplicateStmt->execute([
                ':inv_no' => $requestInvNo,
                ':month_year' => $payrollMonth,
                ':manager_id' => $managerEmpId,
                ':keeper_id' => $keeperId
            ]);
        }

        // Ensure unique key is present for single-row assignment mode.
        $uniqCheckStmt = $pdo->prepare("SELECT COUNT(*)
            FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'payroll_finance_verification'
              AND INDEX_NAME = 'uniq_finance_verification'
              AND NON_UNIQUE = 0");
        $uniqCheckStmt->execute();
        $hasUniqueConstraint = ((int)$uniqCheckStmt->fetchColumn() > 0);
        if (!$hasUniqueConstraint) {
            $pdo->exec("ALTER TABLE payroll_finance_verification
                ADD UNIQUE KEY uniq_finance_verification (request_inv_no, payroll_month, finance_manager_emp_id)");
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

        $summaryStmt = $pdo->prepare("SELECT COUNT(*) AS employee_count, COALESCE(SUM(net_salary), 0) AS total_net_salary
            FROM payrolls
            WHERE month_year = :month_year AND status IN ('generated', 'paid')");
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

