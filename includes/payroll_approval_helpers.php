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

if (!function_exists('ensurePayrollChecklistSupportTables')) {
    function ensurePayrollChecklistSupportTables(PDO $pdo): void
    {
        ensurePayrollChecklistFeedbackTable($pdo);
        ensurePayrollChecklistReviewTable($pdo);
        ensurePayrollCompanyReportDispatchTable($pdo);
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

if (!function_exists('notifyPayrollFinanceOfficer')) {
    function notifyPayrollFinanceOfficer($conDB, PDO $pdo, string $requestInvNo, string $monthYear): array
    {
        $result = [
            'notification_sent' => false,
            'email_sent' => false,
            'recipient_count' => 0
        ];

        $financeStmt = $pdo->prepare("SELECT al.emp_id, al.email, e.name
            FROM admin_login al
            INNER JOIN employees e ON e.emp_id = al.emp_id
            WHERE LOWER(TRIM(al.user_type)) = :user_type
                            AND al.dept = :admin_dept
                            AND e.sectin_nme = :employee_section");
        $financeStmt->execute([
            ':user_type' => 'finance_officer',
                        ':admin_dept' => 2,
                        ':employee_section' => 3
        ]);
        $financeUsers = $financeStmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($financeUsers)) {
            return $result;
        }

        $summaryStmt = $pdo->prepare("SELECT COUNT(*) AS employee_count, COALESCE(SUM(net_salary), 0) AS total_net_salary
            FROM payrolls
            WHERE month_year = :month_year AND status IN ('generated', 'updated', 'paid')");
        $summaryStmt->execute([':month_year' => $monthYear]);
        $summary = $summaryStmt->fetch(PDO::FETCH_ASSOC) ?: [];

        foreach ($financeUsers as $financeUser) {
            $financeEmpId = (string)($financeUser['emp_id'] ?? '');
            if ($financeEmpId === '') {
                continue;
            }

            if (function_exists('create_and_show_notification')) {
                create_and_show_notification(
                    $conDB,
                    $financeEmpId,
                    'Payroll Ready for Payment',
                    'Payroll ' . $monthYear . ' has completed GM approval and is ready for payment processing.',
                    'all_payroll_approvals.php?status=approved',
                    'info'
                );
                $result['notification_sent'] = true;
            } elseif (function_exists('create_browser_notification')) {
                create_browser_notification(
                    $conDB,
                    (int)$financeEmpId,
                    'Payroll Ready for Payment',
                    'Payroll ' . $monthYear . ' has completed GM approval and is ready for payment processing.',
                    'all_payroll_approvals.php?status=approved'
                );
                $result['notification_sent'] = true;
            }

            if (!empty($financeUser['email']) && function_exists('send_approval_email')) {
                $templateData = [
                    'APPROVER_NAME' => !empty($financeUser['name']) ? htmlspecialchars($financeUser['name']) : 'Finance Officer',
                    'REQUEST_ID' => $requestInvNo,
                    'REQUEST_TYPE' => 'Payroll Payment Processing',
                    'EMAIL_MESSAGE' => 'Payroll ' . $monthYear . ' has completed GM approval and is now ready for payment processing. No approval action is required from you; please start the payment process.',
                    'PAYROLL_MONTH' => $monthYear,
                    'EMPLOYEE_COUNT' => (string)($summary['employee_count'] ?? '0'),
                    'TOTAL_NET_SALARY' => number_format((float)($summary['total_net_salary'] ?? 0), 2),
                    'REQUEST_URL' => (function_exists('get_base_url') ? get_base_url() : 'https://hr.almutlaksystem.com') . '/all_payroll_approvals.php?status=approved'
                ];

                $sent = (bool)send_approval_email(
                    $conDB,
                    $financeUser['email'],
                    $financeUser['name'] ?? 'Finance Officer',
                    'Payroll Ready for Payment - ' . $monthYear . ' (' . $requestInvNo . ')',
                    'payroll_request',
                    $templateData
                );
                $result['email_sent'] = $result['email_sent'] || $sent;
            }

            $result['recipient_count']++;
        }

        return $result;
    }
}
