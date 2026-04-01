<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helper_functions.php';

if (!isset($conDB) || !$conDB) {
    fwrite(STDERR, "Database connection is not available.\n");
    exit(1);
}

$cliArgs = (PHP_SAPI === 'cli' && isset($argv) && is_array($argv)) ? $argv : [];

// Support both CLI and browser mode safely.
// CLI examples:
//   php fix_holiday_vacation_deductions.php --apply --emp=4533,3627
// Browser examples:
//   fix_holiday_vacation_deductions.php?apply=1&emp=4533,3627
$apply = in_array('--apply', $cliArgs, true)
    || (isset($_GET['apply']) && ($_GET['apply'] === '1' || strtolower((string)$_GET['apply']) === 'true'));
$empFilter = [];

foreach ($cliArgs as $arg) {
    if (strpos($arg, '--emp=') === 0) {
        $raw = trim(substr($arg, 6));
        if ($raw !== '') {
            $parts = array_filter(array_map('trim', explode(',', $raw)), static function ($v) {
                return $v !== '';
            });
            foreach ($parts as $p) {
                if (ctype_digit($p)) {
                    $empFilter[] = (int)$p;
                }
            }
            $empFilter = array_values(array_unique($empFilter));
        }
    }
}

// Optional employee filter in browser mode: ?emp=4533,3627
if (empty($empFilter) && isset($_GET['emp'])) {
    $raw = trim((string)$_GET['emp']);
    if ($raw !== '') {
        $parts = array_filter(array_map('trim', explode(',', $raw)), static function ($v) {
            return $v !== '';
        });
        foreach ($parts as $p) {
            if (ctype_digit($p)) {
                $empFilter[] = (int)$p;
            }
        }
        $empFilter = array_values(array_unique($empFilter));
    }
}

function getEmployeeCompanyDept(mysqli $conDB, int $empId): array
{
    $sql = "SELECT COALESCE(c.id, e.comp_no) AS company_id, e.dept AS dept_id
            FROM employees e
            LEFT JOIN companies c ON c.comp_id = e.comp_no
            WHERE e.emp_id = ?
            LIMIT 1";

    $stmt = mysqli_prepare($conDB, $sql);
    if (!$stmt) {
        return ['company_id' => 0, 'dept_id' => 0];
    }

    mysqli_stmt_bind_param($stmt, 'i', $empId);
    if (!mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        return ['company_id' => 0, 'dept_id' => 0];
    }

    $res = mysqli_stmt_get_result($stmt);
    $row = $res ? mysqli_fetch_assoc($res) : null;
    if ($res) {
        mysqli_free_result($res);
    }
    mysqli_stmt_close($stmt);

    return [
        'company_id' => (int)($row['company_id'] ?? 0),
        'dept_id' => (int)($row['dept_id'] ?? 0),
    ];
}

function calculateExcludedDays(mysqli $conDB, string $startDate, string $endDate, int $companyId, int $deptId): int
{
    $activeHolidays = get_active_holidays_in_range($conDB, $startDate, $endDate, $companyId > 0 ? $companyId : null);

    $holidayDateSet = [];
    $appTz = get_setting($conDB, 'timezone') ?: 'Asia/Riyadh';
    $tz = new DateTimeZone($appTz);

    foreach ($activeHolidays as $h) {
        try {
            $hStart = new DateTime((string)$h['start_date'], $tz);
            $hEnd = new DateTime((string)$h['end_date'], $tz);
            $vacStart = new DateTime($startDate, $tz);
            $vacEnd = new DateTime($endDate, $tz);
            $overlapStart = max($hStart->getTimestamp(), $vacStart->getTimestamp());
            $overlapEnd = min($hEnd->getTimestamp(), $vacEnd->getTimestamp());

            if ($overlapStart <= $overlapEnd) {
                $cur = new DateTime('@' . $overlapStart);
                $cur->setTimezone($tz);
                $oe = new DateTime('@' . $overlapEnd);
                $oe->setTimezone($tz);
                while ($cur <= $oe) {
                    $holidayDateSet[$cur->format('Y-m-d')] = true;
                    $cur->modify('+1 day');
                }
            }
        } catch (Exception $e) {
            // Keep processing.
        }
    }

    // Per current requirement, exclude official holiday days only.
    return max(0, count($holidayDateSet));
}

// Get active Eid holidays date range
$holidaySql = "SELECT MIN(start_date) AS min_date, MAX(end_date) AS max_date
               FROM emp_holidays
               WHERE is_active = 1
               ORDER BY start_date DESC
               LIMIT 1";
$holidayRes = mysqli_query($conDB, $holidaySql);
if (!$holidayRes) {
    fwrite(STDERR, "Failed to fetch active holidays: " . mysqli_error($conDB) . "\n");
    exit(1);
}

$holidayRow = mysqli_fetch_assoc($holidayRes);
$holidayMinDate = $holidayRow['min_date'] ?? null;
$holidayMaxDate = $holidayRow['max_date'] ?? null;
mysqli_free_result($holidayRes);

if (!$holidayMinDate || !$holidayMaxDate) {
    fwrite(STDERR, "No active Eid holidays found.\n");
    exit(1);
}

$empWhere = '';
if (!empty($empFilter)) {
    $empWhere = ' AND b.emp_id IN (' . implode(',', array_map('intval', $empFilter)) . ') ';
}

// Filter vacation requests that overlap with Eid holiday period
$sql = "SELECT
            b.id AS balance_id,
            b.emp_id,
            b.vac_id,
            b.opening_balance,
            b.total_days,
            b.used_days,
            b.remaining_balance,
            b.available_balance,
            e.name AS employee_name,
            c.comp_name,
            COALESCE(c.id, e.comp_no) AS company_id,
            v.start_date,
            v.return_date,
            v.vacdays,
            v.current_status
        FROM emp_vacation_balance b
        INNER JOIN emp_vacation v ON v.id = b.vac_id
        INNER JOIN employees e ON e.emp_id = b.emp_id
        LEFT JOIN companies c ON c.comp_id = e.comp_no
        WHERE v.current_status IN ('approved', 'completed')
          AND v.start_date IS NOT NULL
          AND v.return_date IS NOT NULL
          AND COALESCE(v.vacdays, 0) > 0
          AND v.start_date <= ?
          AND v.return_date >= ?
          {$empWhere}
        ORDER BY b.emp_id ASC, b.id ASC";

$stmt = mysqli_prepare($conDB, $sql);
if (!$stmt) {
    fwrite(STDERR, "Failed to prepare query: " . mysqli_error($conDB) . "\n");
    exit(1);
}

mysqli_stmt_bind_param($stmt, 'ss', $holidayMaxDate, $holidayMinDate);
if (!mysqli_stmt_execute($stmt)) {
    fwrite(STDERR, "Failed to execute query: " . mysqli_stmt_error($stmt) . "\n");
    exit(1);
}

$res = mysqli_stmt_get_result($stmt);
if (!$res) {
    fwrite(STDERR, 'Failed to fetch vacation requests: ' . mysqli_error($conDB) . "\n");
    exit(1);
}

$checked = 0;
$affected = 0;
$totalCredited = 0.0;
$details = [];
$requestRows = [];

while ($row = mysqli_fetch_assoc($res)) {
    $checked++;

    $balanceId = (int)$row['balance_id'];
    $empId = (int)$row['emp_id'];
    $vacId = (int)$row['vac_id'];
    $companyName = (string)($row['comp_name'] ?? 'N/A');

    $totalDays = (float)($row['total_days'] ?? 0);
    $usedDays = (float)($row['used_days'] ?? 0);
    $remainingBalance = (float)($row['remaining_balance'] ?? 0);
    $availableBalance = (float)($row['available_balance'] ?? 0);
    $vacdays = (float)($row['vacdays'] ?? 0);

    $startDate = (string)$row['start_date'];
    $endDate = (string)$row['return_date'];

    $meta = getEmployeeCompanyDept($conDB, $empId);
    $companyId = (int)$meta['company_id'];
    $deptId = (int)$meta['dept_id'];

    // Calculate Eid holiday days in this vacation period (company-wise)
    $excludedDays = calculateExcludedDays($conDB, $startDate, $endDate, $companyId, $deptId);
    $expectedUsed = max(0.0, $vacdays - $excludedDays);

    // Show all overlapping vacation requests (not just mismatches)
    $requestRows[] = [
        'emp_id' => $empId,
        'employee_name' => (string)($row['employee_name'] ?? 'N/A'),
        'company' => $companyName,
        'vac_id' => $vacId,
        'start_date' => $startDate,
        'end_date' => $endDate,
        'opening_balance' => (float)($row['opening_balance'] ?? 0),
        'applied_days' => $vacdays,
        'eid_holiday_days' => $excludedDays,
        'days_to_deduct' => $expectedUsed,
        'current_used_days' => $usedDays,
        'balance_id' => $balanceId,
        'total_days' => $totalDays,
    ];

    // Update vacation balance with Eid-adjusted deduction only if not already updated
    if ($apply && abs($usedDays - $expectedUsed) > 0.0001) {
        ini_set('display_errors', '0');
        ini_set('error_reporting', 0);
        
        mysqli_begin_transaction($conDB);
        try {
            $openingBal = (float)($row['opening_balance'] ?? 0);
            // Eid holidays are credited BACK to the balance (preserve exact decimal precision)
            $newAvailableBalance = max(0.0, $openingBal + $excludedDays);
            // remaining_balance = available_balance - used_days (i.e., what's left after this vacation)
            $newRemainingBalance = max(0.0, $newAvailableBalance - $expectedUsed);
            
            $upd = "UPDATE emp_vacation_balance
                    SET opening_balance = ?,
                        used_days = ?,
                        available_balance = ?,
                        remaining_balance = ?,
                        last_updated = NOW()
                    WHERE id = ?";

            $stmtUpd = mysqli_prepare($conDB, $upd);
            if (!$stmtUpd) {
                throw new RuntimeException('Failed to prepare update statement.');
            }

            mysqli_stmt_bind_param($stmtUpd, 'ddddi', $newAvailableBalance, $expectedUsed, $newAvailableBalance, $newRemainingBalance, $balanceId);
            if (!mysqli_stmt_execute($stmtUpd)) {
                $err = mysqli_stmt_error($stmtUpd);
                mysqli_stmt_close($stmtUpd);
                throw new RuntimeException('Update failed: ' . $err);
            }

            mysqli_stmt_close($stmtUpd);
            mysqli_commit($conDB);
            $affected++;
        } catch (Throwable $t) {
            mysqli_rollback($conDB);
            if (PHP_SAPI === 'cli') {
                fwrite(STDERR, 'Error for emp_id=' . $empId . ', vac_id=' . $vacId . ': ' . $t->getMessage() . "\n");
            }
        }
    }
}

mysqli_free_result($res);
mysqli_stmt_close($stmt);

$checked = count($requestRows);
$mode = $apply ? 'APPLY' : 'DRY-RUN';

if (PHP_SAPI === 'cli') {
    echo 'Eid Holiday Period: ' . $holidayMinDate . ' to ' . $holidayMaxDate . PHP_EOL;
    echo 'Mode: ' . $mode . PHP_EOL;
    echo 'Vacation Requests Overlapping with Eid Period: ' . $checked . PHP_EOL;
    if ($apply) {
        echo 'Updated Records: ' . $affected . PHP_EOL;
    }
    echo PHP_EOL;

    if (!empty($requestRows)) {
        echo "emp_id | employee_name | company | vac_id | opening_balance | start_date | end_date | applied | eid_days | to_deduct | current_used" . PHP_EOL;
        echo str_repeat('-', 165) . PHP_EOL;
        foreach ($requestRows as $r) {
            echo sprintf(
                "%d | %s | %s | %d | %.1f | %s | %s | %.0f | %d | %.0f | %.0f\n",
                $r['emp_id'],
                substr((string)$r['employee_name'], 0, 20),
                substr((string)$r['company'], 0, 15),
                $r['vac_id'],
                $r['opening_balance'],
                $r['start_date'],
                $r['end_date'],
                $r['applied_days'],
                $r['eid_holiday_days'],
                $r['days_to_deduct'],
                $r['current_used_days']
            );
        }
    } else {
        echo "No vacation requests found that overlap with Eid period." . PHP_EOL;
    }
} else {
    header('Content-Type: text/html; charset=utf-8');
    echo '<!doctype html><html><head><meta charset="utf-8"><title>Eid Holiday Vacation Deductions</title>';
    echo '<style>body{font-family:Arial,Helvetica,sans-serif;background:#f7f9fc;padding:20px;color:#1f2d3d}';
    echo '.card{background:#fff;border:1px solid #e5e9f2;border-radius:8px;padding:16px;margin-bottom:16px}';
    echo 'table{border-collapse:collapse;width:100%;background:#fff}th,td{border:1px solid #e5e9f2;padding:8px;text-align:left;font-size:13px}th{background:#f3f6fb}';
    echo '.btn{display:inline-block;padding:10px 20px;margin:10px 5px 0 0;border:none;border-radius:4px;font-size:14px;cursor:pointer;font-weight:600}';
    echo '.btn-apply{background:#28a745;color:#fff}.btn-apply:hover{background:#218838}';
    echo '.btn-view{background:#0056b3;color:#fff}.btn-view:hover{background:#003d99}';
    echo '.status{padding:10px;border-radius:4px;margin-bottom:16px}';
    echo '.status-warning{background:#fff3cd;border:1px solid #ffc107;color:#856404}';
    echo '.status-success{background:#d4edda;border:1px solid #28a745;color:#155724}';
    echo '</style></head><body>';
    echo '<div class="card">';
    echo '<h2 style="margin:0 0 8px 0;">Vacation Requests During Eid Holiday Period</h2>';
    echo '<div>Eid Period: <strong>' . htmlspecialchars($holidayMinDate) . ' to ' . htmlspecialchars($holidayMaxDate) . '</strong></div>';
    echo '<div>Total Requests: <strong>' . (int)$checked . '</strong></div>';
    if ($apply) {
        echo '<div class="status status-success">✓ <strong>' . (int)$affected . ' records updated successfully!</strong></div>';
    } else {
        echo '<div class="status status-warning">Preview Mode - No changes applied yet</div>';
    }
    echo '<div>Mode: <strong>' . htmlspecialchars($mode) . '</strong></div>';
    
    // Add Apply button if not in apply mode
    if (!$apply && !empty($requestRows)) {
        $currentParams = http_build_query([
            'apply' => '1',
            'emp' => (!empty($empFilter) ? implode(',', $empFilter) : '')
        ]);
        echo '<form method="GET" style="margin:16px 0;">';
        echo '<input type="hidden" name="apply" value="1">';
        if (!empty($empFilter)) {
            echo '<input type="hidden" name="emp" value="' . htmlspecialchars(implode(',', $empFilter)) . '">';
        }
        echo '<button type="submit" class="btn btn-apply">✓ Apply Updates Now</button>';
        echo '</form>';
    }
    
    echo '</div>';

    echo '<div class="card"><table><thead><tr>';
    echo '<th>emp_id</th><th>Employee Name</th><th>Company</th><th>vac_id</th><th>Opening Balance</th><th>Start Date</th><th>End Date</th><th>Applied Days</th><th>Eid Holiday Days</th><th>Days to Deduct</th><th>Current Used</th>';
    echo '</tr></thead><tbody>';
    if (empty($requestRows)) {
        echo '<tr><td colspan="11">No vacation requests found that overlap with Eid period.</td></tr>';
    } else {
        foreach ($requestRows as $r) {
            echo '<tr>';
            echo '<td>' . (int)$r['emp_id'] . '</td>';
            echo '<td>' . htmlspecialchars((string)$r['employee_name']) . '</td>';
            echo '<td>' . htmlspecialchars((string)$r['company']) . '</td>';
            echo '<td>' . (int)$r['vac_id'] . '</td>';
            echo '<td style="text-align:center">' . number_format((float)$r['opening_balance'], 1) . '</td>';
            echo '<td>' . htmlspecialchars($r['start_date']) . '</td>';
            echo '<td>' . htmlspecialchars($r['end_date']) . '</td>';
            echo '<td style="text-align:center">' . number_format((float)$r['applied_days'], 0) . '</td>';
            echo '<td style="text-align:center"><strong style="color:#dc3545">' . (int)$r['eid_holiday_days'] . '</strong></td>';
            echo '<td style="text-align:center"><strong>' . number_format((float)$r['days_to_deduct'], 0) . '</strong></td>';
            echo '<td style="text-align:center">' . number_format((float)$r['current_used_days'], 0) . '</td>';
            echo '</tr>';
        }
    }
    echo '</tbody></table></div>';
    echo '</body></html>';
}
