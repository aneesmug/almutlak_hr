<?php
header('Content-Type: application/json');

require_once("./../../includes/db.php");
require_once("./../../includes/session_check.php");

function parseImportAmount($value): float
{
    if ($value === null || $value === '') {
        return 0.0;
    }

    $normalized = preg_replace('/[^0-9.\-]/', '', (string)$value);
    if ($normalized === '' || $normalized === '-' || $normalized === '.' || $normalized === '-.') {
        return 0.0;
    }

    return (float)$normalized;
}

function normalizeImportMonth($value): ?string
{
    if ($value === null || $value === '') {
        return null;
    }

    if (is_numeric($value)) {
        $days = (int)$value;
        if ($days > 0) {
            $date = new DateTime('1899-12-30');
            $date->modify('+' . $days . ' days');
            return $date->format('Y-m');
        }
    }

    $stringValue = trim((string)$value);
    if ($stringValue === '') {
        return null;
    }

    if (preg_match('/^(\d{4})[-\/](\d{2})$/', $stringValue, $matches)) {
        return $matches[1] . '-' . $matches[2];
    }

    $formats = ['Y-m-d', 'Y/m/d', 'd/m/Y', 'm/d/Y', 'd-m-Y', 'm-Y', 'm/Y', 'F Y', 'M Y'];
    foreach ($formats as $format) {
        $date = DateTime::createFromFormat($format, $stringValue);
        if ($date instanceof DateTime) {
            return $date->format('Y-m');
        }
    }

    $timestamp = strtotime($stringValue);
    if ($timestamp === false) {
        return null;
    }

    return date('Y-m', $timestamp);
}

function normalizeImportStatus($value): ?int
{
    $stringValue = strtolower(trim((string)$value));
    if ($stringValue === '') {
        return 1;
    }

    if (in_array($stringValue, ['1', 'active', 'yes', 'y', 'true', 'enabled'], true)) {
        return 1;
    }

    if (in_array($stringValue, ['0', 'inactive', 'no', 'n', 'false', 'disabled'], true)) {
        return 0;
    }

    return null;
}

function normalizeImportCheckpointCode($value): string
{
    return strtoupper(trim((string)$value));
}

function normalizeBenefitImportType($value): string
{
    return 'by_hours';
}

function normalizeDeductionImportType($value): string
{
    return 'hourly_deduction';
}

function normalizeDeductionName($value): string
{
    return preg_replace('/\s+/', ' ', trim((string)$value));
}

function normalizeGosiComparableText($value): string
{
    $normalized = strtolower((string)$value);
    $normalized = preg_replace('/[^a-z]/', '', $normalized);
    return preg_replace('/(.)\1+/', '$1', $normalized);
}

function isGosiDeductionName(string $value): bool
{
    return strpos(normalizeGosiComparableText(normalizeDeductionName($value)), 'gosi') !== false;
}

function calculateImportedBenefitAmount(array $payrollRecord, float $benefitHours): float
{
    $hours = max(0.0, $benefitHours);
    if ($hours <= 0) {
        return 0.0;
    }

    $totalSalary = (float)($payrollRecord['total_gross_salary'] ?? 0);
    $basicSalary = (float)($payrollRecord['basic_salary'] ?? 0);
    $hourlyRate = ($basicSalary / 240 / 2) + ($totalSalary / 240);
    return $hourlyRate * $hours;
}

function calculateImportedDeductionAmount(array $payrollRecord, float $deductionHours): array
{
    $hours = max(0.0, $deductionHours);
    $totalGrossSalary = (float)($payrollRecord['total_gross_salary'] ?? 0);
    $foodAllowance = (float)($payrollRecord['food_allowance'] ?? 0);

    if ($totalGrossSalary <= 0) {
        $totalGrossSalary = (float)($payrollRecord['basic_salary'] ?? 0)
            + (float)($payrollRecord['housing_allowance'] ?? 0)
            + (float)($payrollRecord['food_allowance'] ?? 0)
            + (float)($payrollRecord['transport_allowance'] ?? 0);
    }

    $deductibleSalary = max($totalGrossSalary - $foodAllowance, 0.0);
    $hourlyRate = $deductibleSalary > 0 ? ($deductibleSalary / 240) : 0;

    return [
        'amount' => $hourlyRate * $hours,
        'hours' => $hours,
        'days' => 0.0,
    ];
}

function buildPayrollImportSkipMessage(int $rowNumber, string $reason, string $empId = ''): string
{
    $employeePart = $empId !== '' ? ' Employee ' . $empId . ':' : ':';
    return 'Row ' . $rowNumber . $employeePart . ' ' . $reason;
}

function addPayrollImportSkip(array &$summary, int $rowNumber, string $reason, string $empId = '', bool $includeInErrors = true): void
{
    $message = buildPayrollImportSkipMessage($rowNumber, $reason, $empId);
    $summary['skipped_rows']++;
    $summary['skipped_details'][] = $message;

    if ($includeInErrors) {
        $summary['errors'][] = $message;
    }
}

function ensurePayrollImportCheckpointTable(PDO $pdo): void
{
    $pdo->exec("CREATE TABLE IF NOT EXISTS payroll_import_checkpoint_registry (
        id INT AUTO_INCREMENT PRIMARY KEY,
        checkpoint_code VARCHAR(255) NOT NULL UNIQUE,
        default_month VARCHAR(7) DEFAULT NULL,
        created_by VARCHAR(100) DEFAULT NULL,
        issued_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        used_at TIMESTAMP NULL DEFAULT NULL,
        INDEX idx_default_month (default_month),
        INDEX idx_used_at (used_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
}

function extractImportCheckpointCode(array $rows): string
{
    $checkpointCodes = [];

    foreach ($rows as $row) {
        $checkpointCode = normalizeImportCheckpointCode(
            $row['checkpoint_code']
            ?? $row['import_checkpoint_code']
            ?? $row['upload_checkpoint_code']
            ?? $row['template_checkpoint_code']
            ?? ''
        );

        if ($checkpointCode !== '') {
            $checkpointCodes[$checkpointCode] = true;
        }
    }

    if (count($checkpointCodes) === 0) {
        throw new RuntimeException('The selected file is not valid. Please upload a valid payroll import file.');
    }

    if (count($checkpointCodes) > 1) {
        throw new RuntimeException('The selected file is not valid. Please upload a valid payroll import file.');
    }

    return (string)array_key_first($checkpointCodes);
}

function reservePayrollImportCheckpoint(PDO $pdo, string $checkpointCode, ?string $defaultMonth, string $createdBy): void
{
    $lookupStmt = $pdo->prepare("SELECT id, used_at FROM payroll_import_checkpoint_registry WHERE checkpoint_code = :checkpoint_code LIMIT 1");
    $lookupStmt->execute([':checkpoint_code' => $checkpointCode]);
    $record = $lookupStmt->fetch(PDO::FETCH_ASSOC) ?: null;

    if ($record === null) {
        throw new RuntimeException('The selected file is not valid. Please upload a valid payroll import file.');
    }

    if (!empty($record['used_at'])) {
        throw new RuntimeException('This file was already used. Please upload a different file.');
    }

    $updateStmt = $pdo->prepare("UPDATE payroll_import_checkpoint_registry SET used_at = CURRENT_TIMESTAMP, default_month = COALESCE(:default_month, default_month), created_by = CASE WHEN created_by IS NULL OR created_by = '' THEN :created_by ELSE created_by END WHERE checkpoint_code = :checkpoint_code AND used_at IS NULL");
    $updateStmt->execute([
        ':checkpoint_code' => $checkpointCode,
        ':default_month' => $defaultMonth,
        ':created_by' => $createdBy,
    ]);

    if ($updateStmt->rowCount() < 1) {
        throw new RuntimeException('This file was already used. Please upload a different file.');
    }
}

function upsertPayrollBenefit(PDO $pdo, string $empId, string $monthYear, string $benefitName, float $benefitAmount, int $status, string $benefitType = 'fixed', float $benefitHours = 0.0): bool
{
    $normalizedHours = $benefitHours > 0 ? (int)round($benefitHours) : null;
    $calculationType = normalizeBenefitImportType($benefitType);

    if ($calculationType === 'fixed') {
        $normalizedHours = null;
    }

    $lookup = $pdo->prepare("SELECT id FROM payroll_benefits WHERE emp_id = :emp_id AND month = :month_year AND benefit = :benefit LIMIT 1");
    $lookup->execute([
        ':emp_id' => $empId,
        ':month_year' => $monthYear,
        ':benefit' => $benefitName,
    ]);
    $existingId = $lookup->fetchColumn();

    if ($existingId) {
        $update = $pdo->prepare("UPDATE payroll_benefits SET note = :amount, status = :status, calculation_type = :calculation_type, hours = :hours, days = NULL, type_id = NULL WHERE id = :id");
        $update->execute([
            ':amount' => number_format($benefitAmount, 2, '.', ''),
            ':status' => $status,
            ':calculation_type' => $calculationType,
            ':hours' => $normalizedHours,
            ':id' => $existingId,
        ]);
        return true;
    }

    $insert = $pdo->prepare("INSERT INTO payroll_benefits (emp_id, benefit, note, hours, days, calculation_type, month, status, type_id) VALUES (:emp_id, :benefit, :amount, :hours, NULL, :calculation_type, :month_year, :status, NULL)");
    $insert->execute([
        ':emp_id' => $empId,
        ':benefit' => $benefitName,
        ':amount' => number_format($benefitAmount, 2, '.', ''),
        ':hours' => $normalizedHours,
        ':calculation_type' => $calculationType,
        ':month_year' => $monthYear,
        ':status' => $status,
    ]);

    return true;
}

function upsertPayrollDeduction(PDO $pdo, string $empId, string $monthYear, string $deductionName, float $deductionAmount, int $status, string $deductionType = 'fixed', float $deductionHours = 0.0, float $deductionDays = 0.0): bool
{
    $deductionName = normalizeDeductionName($deductionName);
    $normalizedHours = $deductionHours > 0 ? (int)round($deductionHours) : null;
    $normalizedDays = $deductionDays > 0 ? (int)round($deductionDays) : null;
    $calculationType = normalizeDeductionImportType($deductionType);

    if ($calculationType === 'fixed') {
        $normalizedHours = null;
        $normalizedDays = null;
    } elseif ($calculationType === 'hourly_deduction') {
        $normalizedDays = null;
    } else {
        $normalizedHours = null;
    }

    if (isGosiDeductionName($deductionName)) {
        $deductionName = 'GOSI';

        $lookup = $pdo->prepare("SELECT id FROM payroll_deductions WHERE emp_id = :emp_id AND month = :month_year AND UPPER(TRIM(deduction)) = 'GOSI' ORDER BY id ASC");
        $lookup->execute([
            ':emp_id' => $empId,
            ':month_year' => $monthYear,
        ]);
        $existingIds = $lookup->fetchAll(PDO::FETCH_COLUMN);
        $existingId = !empty($existingIds) ? (int)$existingIds[0] : 0;

        if ($existingId > 0) {
            $update = $pdo->prepare("UPDATE payroll_deductions SET deduction = 'GOSI', note = :amount, status = :status, calculation_type = 'fixed', hours = NULL, days = NULL WHERE id = :id");
            $update->execute([
                ':amount' => number_format($deductionAmount, 2, '.', ''),
                ':status' => $status,
                ':id' => $existingId,
            ]);

            if (count($existingIds) > 1) {
                $duplicateIds = array_slice(array_map('intval', $existingIds), 1);
                $placeholders = implode(',', array_fill(0, count($duplicateIds), '?'));
                $delete = $pdo->prepare("DELETE FROM payroll_deductions WHERE id IN ($placeholders)");
                $delete->execute($duplicateIds);
            }

            return true;
        }
    } else {
        $lookup = $pdo->prepare("SELECT id FROM payroll_deductions WHERE emp_id = :emp_id AND month = :month_year AND deduction = :deduction LIMIT 1");
        $lookup->execute([
            ':emp_id' => $empId,
            ':month_year' => $monthYear,
            ':deduction' => $deductionName,
        ]);
        $existingId = $lookup->fetchColumn();

        if ($existingId) {
            $update = $pdo->prepare("UPDATE payroll_deductions SET note = :amount, status = :status, calculation_type = :calculation_type, hours = :hours, days = :days WHERE id = :id");
            $update->execute([
                ':amount' => number_format($deductionAmount, 2, '.', ''),
                ':status' => $status,
                ':calculation_type' => $calculationType,
                ':hours' => $normalizedHours,
                ':days' => $normalizedDays,
                ':id' => $existingId,
            ]);
            return true;
        }
    }

    $insert = $pdo->prepare("INSERT INTO payroll_deductions (emp_id, deduction, note, hours, days, calculation_type, month, status) VALUES (:emp_id, :deduction, :amount, :hours, :days, :calculation_type, :month_year, :status)");
    $insert->execute([
        ':emp_id' => $empId,
        ':deduction' => $deductionName,
        ':amount' => number_format($deductionAmount, 2, '.', ''),
        ':hours' => $normalizedHours,
        ':days' => $normalizedDays,
        ':calculation_type' => $calculationType,
        ':month_year' => $monthYear,
        ':status' => $status,
    ]);

    return true;
}

function recalculateImportedPayroll(PDO $pdo, string $empId, string $monthYear): void
{
    $payrollStmt = $pdo->prepare("SELECT id, total_gross_salary, status FROM payrolls WHERE emp_id = :emp_id AND month_year = :month_year LIMIT 1 FOR UPDATE");
    $payrollStmt->execute([
        ':emp_id' => $empId,
        ':month_year' => $monthYear,
    ]);
    $payroll = $payrollStmt->fetch(PDO::FETCH_ASSOC);

    if (!$payroll) {
        throw new RuntimeException('Generated payroll record not found.');
    }

    $benefitsStmt = $pdo->prepare("SELECT COALESCE(SUM(CAST(note AS DECIMAL(10,2))), 0) FROM payroll_benefits WHERE emp_id = :emp_id AND month = :month_year AND status = 1");
    $benefitsStmt->execute([
        ':emp_id' => $empId,
        ':month_year' => $monthYear,
    ]);
    $totalBenefits = (float)$benefitsStmt->fetchColumn();

    $deductionsStmt = $pdo->prepare("SELECT COALESCE(SUM(CAST(note AS DECIMAL(10,2))), 0) FROM payroll_deductions WHERE emp_id = :emp_id AND month = :month_year AND status = 1");
    $deductionsStmt->execute([
        ':emp_id' => $empId,
        ':month_year' => $monthYear,
    ]);
    $totalDeductions = (float)$deductionsStmt->fetchColumn();

    $netSalary = (float)$payroll['total_gross_salary'] + $totalBenefits - $totalDeductions;

    $updateStmt = $pdo->prepare("UPDATE payrolls SET total_benefits = :total_benefits, total_deductions = :total_deductions, net_salary = :net_salary, status = CASE WHEN status = 'paid' THEN 'paid' ELSE 'updated' END WHERE id = :id");
    $updateStmt->execute([
        ':total_benefits' => number_format($totalBenefits, 2, '.', ''),
        ':total_deductions' => number_format($totalDeductions, 2, '.', ''),
        ':net_salary' => number_format($netSalary, 2, '.', ''),
        ':id' => $payroll['id'],
    ]);
}

$input = json_decode(file_get_contents('php://input'), true);
$rows = isset($input['rows']) && is_array($input['rows']) ? $input['rows'] : [];
$defaultMonth = normalizeImportMonth($input['default_month'] ?? null);

if (empty($rows)) {
    echo json_encode(['status' => 'error', 'message' => 'No import rows received.']);
    exit();
}

$pdo = getDbConnection();
$checkpointCode = '';
$summary = [
    'processed_rows' => 0,
    'imported_benefits' => 0,
    'imported_deductions' => 0,
    'updated_payrolls' => 0,
    'skipped_rows' => 0,
    'skipped_details' => [],
    'errors' => [],
];

try {
    ensurePayrollImportCheckpointTable($pdo);
    $checkpointCode = extractImportCheckpointCode($rows);

    $pdo->beginTransaction();
    reservePayrollImportCheckpoint($pdo, $checkpointCode, $defaultMonth, (string)($_SESSION['empid'] ?? $empid ?? '')); 
    $touchedPayrolls = [];
    $seenGosiDeductions = [];

    foreach ($rows as $index => $row) {
        $rowNumber = $index + 2;
        $rowCheckpointCode = normalizeImportCheckpointCode(
            $row['checkpoint_code']
            ?? $row['import_checkpoint_code']
            ?? $row['upload_checkpoint_code']
            ?? $row['template_checkpoint_code']
            ?? ''
        );
        $empId = trim((string)($row['emp_id'] ?? ''));
        $monthYear = normalizeImportMonth($row['month'] ?? $defaultMonth);
        $overtimeStatus = normalizeImportStatus($row['overtime_status'] ?? $row['benefit_status'] ?? $row['overtime_record_status'] ?? $row['status'] ?? '1');
        $benefitType = normalizeBenefitImportType($row['benefit_type'] ?? $row['overtime_type'] ?? 'by_hours');
        $overtimeValue = parseImportAmount($row['benefit_value'] ?? $row['overtime_value'] ?? '');
        $overtimeHours = parseImportAmount($row['benefit_hours'] ?? $row['overtime_hours'] ?? '');
        $overtimeMinutes = parseImportAmount($row['benefit_minutes'] ?? $row['overtime_minutes'] ?? '');
        $overtimeReason = trim((string)($row['benefit_reason'] ?? $row['overtime_reason'] ?? ''));
        $deductionStatus = normalizeImportStatus($row['deduction_status'] ?? $row['deduction_record_status'] ?? $row['status'] ?? '1');
        $deductionType = normalizeDeductionImportType($row['deduction_type'] ?? 'hourly_deduction');
        $deductionValue = parseImportAmount($row['deduction_value'] ?? '');
        $deductionHours = parseImportAmount($row['deduction_hours'] ?? '');
        $deductionMinutes = parseImportAmount($row['deduction_minutes'] ?? '');
        $deductionDays = 0.0;
        $deductionReason = trim((string)($row['deduction_reason'] ?? ''));
        $gosiDeductionSkipped = false;
        $missingOvertimePair = false;
        $missingDeductionPair = false;
        $invalidDeductionPeriodPair = false;

        $overtimeDurationHours = $overtimeHours + ($overtimeMinutes / 60);
        if ($overtimeDurationHours > 0) {
            if ($overtimeReason === '') {
                $missingOvertimePair = true;
            }
        } elseif ($overtimeReason !== '') {
            $missingOvertimePair = true;
        }

        if ($overtimeDurationHours <= 0 || $overtimeReason === '') {
            $overtimeValue = 0.0;
            $overtimeHours = 0.0;
            $overtimeMinutes = 0.0;
            $overtimeReason = '';
        }

        if (($deductionValue > 0 || $deductionHours > 0 || $deductionMinutes > 0) && isGosiDeductionName($deductionReason)) {
            $deductionType = 'hourly_deduction';
            $deductionValue = 0.0;
            $deductionHours = 0.0;
            $deductionMinutes = 0.0;
            $deductionReason = '';
            $gosiDeductionSkipped = true;
        }

        $deductionDurationHours = $deductionHours + ($deductionMinutes / 60);
        if ($deductionDurationHours > 0) {
            if (!$gosiDeductionSkipped && $deductionReason === '') {
                $missingDeductionPair = true;
            }
        } elseif ($deductionReason !== '') {
            $missingDeductionPair = true;
        }

        if ($deductionDurationHours <= 0 || $deductionReason === '') {
            $deductionValue = 0.0;
            $deductionHours = 0.0;
            $deductionMinutes = 0.0;
            $deductionReason = '';
        }

        if ($empId === '' && $monthYear === null && $overtimeValue === 0.0 && $overtimeHours === 0.0 && $overtimeMinutes === 0.0 && $overtimeReason === '' && $deductionValue === 0.0 && $deductionHours === 0.0 && $deductionMinutes === 0.0 && $deductionReason === '') {
            addPayrollImportSkip($summary, $rowNumber, 'Row is empty.', $empId, false);
            continue;
        }

        if ($empId === '') {
            addPayrollImportSkip($summary, $rowNumber, 'Employee ID is required.', $empId);
            continue;
        }

        if ($monthYear === null) {
            addPayrollImportSkip($summary, $rowNumber, 'Month must be a valid payroll month.', $empId);
            continue;
        }

        if (!preg_match('/^\d{4}-\d{2}$/', $monthYear)) {
            addPayrollImportSkip($summary, $rowNumber, 'Month format must be YYYY-MM.', $empId);
            continue;
        }

        if ($rowCheckpointCode === '') {
            addPayrollImportSkip($summary, $rowNumber, 'File is not valid.', $empId);
            continue;
        }

        if ($rowCheckpointCode !== $checkpointCode) {
            addPayrollImportSkip($summary, $rowNumber, 'File is not valid.', $empId);
            continue;
        }

        $hasBenefitEntry = $overtimeHours > 0 || $overtimeMinutes > 0 || $overtimeValue > 0;
        $hasDeductionEntry = $deductionHours > 0 || $deductionMinutes > 0 || $deductionValue > 0;

        if ($hasBenefitEntry && $overtimeStatus === null) {
            addPayrollImportSkip($summary, $rowNumber, 'Overtime status must be Active/Inactive or 1/0.', $empId);
            continue;
        }

        if ($hasDeductionEntry && $deductionStatus === null) {
            addPayrollImportSkip($summary, $rowNumber, 'Deduction status must be Active/Inactive or 1/0.', $empId);
            continue;
        }

        if ($overtimeValue < 0 || $overtimeHours < 0 || $overtimeMinutes < 0 || $deductionValue < 0 || $deductionHours < 0 || $deductionMinutes < 0) {
            addPayrollImportSkip($summary, $rowNumber, 'Overtime and deduction values/hours/minutes cannot be negative.', $empId);
            continue;
        }

        if (!$hasBenefitEntry && !$hasDeductionEntry) {
            if ($gosiDeductionSkipped) {
                addPayrollImportSkip($summary, $rowNumber, 'System-managed GOSI deduction was ignored from import.', $empId, false);
                continue;
            }

            if ($missingOvertimePair || $missingDeductionPair) {
                $reasons = [];
                if ($missingOvertimePair) {
                    $reasons[] = 'hourly benefit requires hours/minutes and reason';
                }
                if ($missingDeductionPair) {
                    $reasons[] = 'hourly deduction requires hours/minutes and reason';
                }
                addPayrollImportSkip($summary, $rowNumber, ucfirst(implode('; ', $reasons)) . '.', $empId, false);
                continue;
            }

            addPayrollImportSkip($summary, $rowNumber, 'Add at least one overtime or deduction entry.', $empId);
            continue;
        }

        $payrollStmt = $pdo->prepare("SELECT id, status, basic_salary, housing_allowance, food_allowance, transport_allowance, total_gross_salary FROM payrolls WHERE emp_id = :emp_id AND month_year = :month_year LIMIT 1");
        $payrollStmt->execute([
            ':emp_id' => $empId,
            ':month_year' => $monthYear,
        ]);
        $payrollRecord = $payrollStmt->fetch(PDO::FETCH_ASSOC);

        if (!$payrollRecord) {
            addPayrollImportSkip($summary, $rowNumber, 'Generate payroll first for month ' . $monthYear . '.', $empId);
            continue;
        }

        if (strtolower((string)$payrollRecord['status']) === 'paid') {
            addPayrollImportSkip($summary, $rowNumber, 'Paid payroll cannot be modified.', $empId);
            continue;
        }

        if ($missingOvertimePair) {
            addPayrollImportSkip($summary, $rowNumber, $benefitType === 'fixed' ? 'Fixed benefit was skipped because both value and reason are required.' : 'Calculated benefit was skipped because hours and reason are required.', $empId, false);
        }

        if ($gosiDeductionSkipped) {
            addPayrollImportSkip($summary, $rowNumber, 'System-managed GOSI deduction was ignored from import.', $empId, false);
        }

        if ($missingDeductionPair) {
            addPayrollImportSkip($summary, $rowNumber, $deductionType === 'fixed' ? 'Fixed deduction was skipped because both value and reason are required.' : ($deductionType === 'hourly_deduction' ? 'Hourly deduction was skipped because hours and reason are required.' : 'Daily deduction was skipped because days and reason are required.'), $empId, false);
        }

        $rowImported = false;

        if ($hasBenefitEntry) {
            $benefitDurationHours = $overtimeHours + ($overtimeMinutes / 60);
            if ($benefitDurationHours > 0 && $overtimeValue <= 0) {
                $overtimeValue = calculateImportedBenefitAmount($payrollRecord, $benefitDurationHours);
            }

            $benefitName = $overtimeReason !== '' ? $overtimeReason : 'Imported Overtime';
            upsertPayrollBenefit($pdo, $empId, $monthYear, $benefitName, $overtimeValue, $overtimeStatus ?? 1, $benefitType, $benefitDurationHours);
            $summary['imported_benefits']++;
            $rowImported = true;
        }

        if ($hasDeductionEntry) {
            if ($deductionValue <= 0) {
                $calculatedDeduction = calculateImportedDeductionAmount($payrollRecord, $deductionHours + ($deductionMinutes / 60));
                $deductionValue = $calculatedDeduction['amount'];
                $deductionHours = $calculatedDeduction['hours'];
                $deductionMinutes = 0.0;
            }

            $deductionName = normalizeDeductionName($deductionReason !== '' ? $deductionReason : 'Imported Deduction');

            if (isGosiDeductionName($deductionName)) {
                $deductionName = 'GOSI';
                $gosiKey = $empId . '|' . $monthYear;

                if (isset($seenGosiDeductions[$gosiKey])) {
                    addPayrollImportSkip($summary, $rowNumber, 'GOSI deduction can only be imported once per employee for the selected payroll month.', $empId);
                    continue;
                }

                $seenGosiDeductions[$gosiKey] = true;
            }

            upsertPayrollDeduction($pdo, $empId, $monthYear, $deductionName, $deductionValue, $deductionStatus ?? 1, $deductionType, $deductionHours, $deductionDays);
            $summary['imported_deductions']++;
            $rowImported = true;
        }

        if (!$rowImported) {
            addPayrollImportSkip($summary, $rowNumber, 'No valid payroll entries were left after validation.', $empId, false);
            continue;
        }

        $summary['processed_rows']++;
        $touchedPayrolls[$empId . '|' . $monthYear] = [$empId, $monthYear];
    }

    foreach ($touchedPayrolls as $payrollKey => $parts) {
        recalculateImportedPayroll($pdo, $parts[0], $parts[1]);
        $summary['updated_payrolls']++;
    }

    if ($summary['processed_rows'] === 0 && !empty($summary['errors'])) {
        $pdo->rollBack();
        echo json_encode([
            'status' => 'error',
            'message' => 'No payroll rows were imported. Review the row errors and make sure payroll is already generated for the same employee and month.',
            'errors' => $summary['errors'],
            'processed_rows' => 0,
            'imported_benefits' => 0,
            'imported_deductions' => 0,
            'updated_payrolls' => 0,
            'skipped_rows' => $summary['skipped_rows'],
            'skipped_details' => $summary['skipped_details'],
        ]);
        exit();
    }

    $pdo->commit();

    echo json_encode([
        'status' => 'success',
        'message' => 'Payroll Excel import completed successfully.',
        'checkpoint_code' => $checkpointCode,
        'processed_rows' => $summary['processed_rows'],
        'imported_benefits' => $summary['imported_benefits'],
        'imported_deductions' => $summary['imported_deductions'],
        'updated_payrolls' => $summary['updated_payrolls'],
        'skipped_rows' => $summary['skipped_rows'],
        'skipped_details' => $summary['skipped_details'],
        'errors' => $summary['errors'],
    ]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log('Payroll Excel import failed: ' . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => 'Payroll Excel import failed: ' . $e->getMessage(),
    ]);
}
?>