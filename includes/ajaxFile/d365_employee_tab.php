<?php
// Employee master "D365" tab (view_employee.php) - system admins + 'view_employee_d365_tab' special access.
// POST csrf (= $_SESSION['d365_csrf']), emp_id, refresh=1 to bypass the 10 min session cache.
// Returns an HTML fragment: HR app vs D365 comparison, worker record, employments, positions,
// bank accounts and the financial transactions of the worker (general journal lines; the payroll synced by this
// app is listed per month and in the Transactions table, but kept out of the financial summary).
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/session_check.php';
require_once __DIR__ . '/../../includes/D365Workers.php';
require_once __DIR__ . '/../../includes/D365Payroll.php';

header('Content-Type: text/html; charset=utf-8');
// D365 can be slow - lift the 25s app-wide limit from includes/db.php
@set_time_limit(120);

$e = function ($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); };

if (!user_has_special_access($conDB, $empid ?? '', 'view_employee_d365_tab', $user_role ?? '', $user_type ?? '', $is_system_admin ?? false)) {
    http_response_code(403);
    exit('<div class="sr-notice tone-red">You do not have access to D365 data.</div>');
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_SESSION['d365_csrf']) || !hash_equals($_SESSION['d365_csrf'], (string)($_POST['csrf'] ?? ''))) {
    exit('<div class="sr-notice tone-amber">Session expired - reload the page.</div>');
}
$targetEmp = (string)($_POST['emp_id'] ?? '');
if (!preg_match('/^[A-Za-z0-9\-]{1,20}$/', $targetEmp)) {
    exit('<div class="sr-notice tone-red">Invalid employee ID.</div>');
}
// Report mode (reports.php "D365 Employee Report"): read-only (no Sync / Add buttons) and the journal lines
// are narrowed to date_from..date_to (yyyy-mm-dd, Riyadh dates, either side optional)
$reportMode = !empty($_POST['report']);
$dateFrom = preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)($_POST['date_from'] ?? '')) ? $_POST['date_from'] : '';
$dateTo = preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)($_POST['date_to'] ?? '')) ? $_POST['date_to'] : '';

// D365 stores dates in UTC; placeholders 1900-01-01 / 2154-12-31 mean "empty" / "no end"
function d365tab_date($value, $withTime = false)
{
    if (!is_string($value) || $value === '' || strpos($value, '1900-01-01') === 0) {
        return '';
    }
    if (strpos($value, '2154-12-31') === 0) {
        return 'Open';
    }
    try {
        $dt = new DateTime($value, new DateTimeZone('UTC'));
        $dt->setTimezone(new DateTimeZone('Asia/Riyadh'));
        return $dt->format($withTime ? 'Y-m-d H:i' : 'Y-m-d');
    } catch (Exception $ex) {
        return $value;
    }
}
function d365tab_empty($v)
{
    return $v === '' || $v === null || $v === 'None' || $v === 'No' || $v === 0 || is_array($v)
        || (is_string($v) && (strpos($v, '1900-01-01') === 0 || strpos($v, '2154-12-31') === 0));
}
function d365tab_norm($v)
{
    return preg_replace('/\s+/', ' ', mb_strtolower(trim((string)$v)));
}

try {
    $client = new D365Client();
} catch (Throwable $ex) {
    exit('<div class="sr-notice tone-amber">D365 is not configured: ' . $e($ex->getMessage()) . ' (App Settings &gt; D365 Config)</div>');
}
$env = $client->getEnvironment();
$workers = new D365Workers($conDB, $client);

// D365 data, cached per employee for 10 minutes in the temp dir (kept out of the session - it can be large)
unset($_SESSION['d365_tab_cache']); // old session-based cache
session_write_close();
$cacheFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'd365_tab_' . md5($client->getResourceUrl() . '|v2|' . $targetEmp) . '.json';
$cached = is_file($cacheFile) ? json_decode((string)file_get_contents($cacheFile), true) : null;
if (!empty($_POST['refresh']) || !$cached || time() - $cached['t'] > 600) {
    $details = $workers->getEmployeeDetails($targetEmp);
    $cached = ['t' => time(), 'v' => $details];
    if (!$details['errors']) {
        @file_put_contents($cacheFile, json_encode($cached, JSON_UNESCAPED_UNICODE), LOCK_EX);
    }
    // keep the header widget status in line with what was just read
    $workers->getStatus($targetEmp, $details['worker'] ? 21600 : 0);
} elseif (!empty($cached['v']['journal_lines'])) {
    // Cached data: journals can be deleted / posted in D365 meanwhile - re-read their headers (one small query)
    // so lines of a deleted journal disappear and the Posted state is current
    $keys = [];
    foreach ($cached['v']['journal_lines'] as $jl) {
        $keys[strtolower($jl['dataAreaId']) . '|' . $jl['JournalBatchNumber']] = true;
    }
    $headers = [];
    $checked = true;
    foreach (array_chunk(array_keys($keys), 15) as $chunk) {
        $or = implode(' or ', array_map(function ($k) {
            [$c, $b] = explode('|', $k, 2);
            return "(dataAreaId eq '" . $c . "' and JournalBatchNumber eq '" . str_replace("'", "''", $b) . "')";
        }, $chunk));
        $r = $client->get('LedgerJournalHeaders', ['$filter' => $or, '$select' => 'dataAreaId,JournalBatchNumber,JournalName,Description,IsPosted'], true);
        if ($r['error']) {
            $checked = false; // D365 unreachable - show the cache as it is
            break;
        }
        foreach ($r['data']['value'] ?? [] as $h) {
            $headers[strtolower($h['dataAreaId']) . '|' . $h['JournalBatchNumber']] = $h;
        }
    }
    if ($checked) {
        $before = count($cached['v']['journal_lines']);
        $cached['v']['journals'] = $headers;
        $cached['v']['journal_lines'] = array_values(array_filter($cached['v']['journal_lines'], function ($jl) use ($headers) {
            return isset($headers[strtolower($jl['dataAreaId']) . '|' . $jl['JournalBatchNumber']]);
        }));
        if (count($cached['v']['journal_lines']) !== $before) {
            @file_put_contents($cacheFile, json_encode($cached, JSON_UNESCAPED_UNICODE), LOCK_EX);
        }
    }
}
$d = $cached['v'];
if ($dateFrom !== '' || $dateTo !== '') {
    $d['journal_lines'] = array_values(array_filter($d['journal_lines'], function ($jl) use ($dateFrom, $dateTo) {
        $day = d365tab_date((string)$jl['TransDate']);
        return ($dateFrom === '' || strcmp($day, $dateFrom) >= 0) && ($dateTo === '' || strcmp($day, $dateTo) <= 0);
    }));
}
$loadedAt = date('Y-m-d H:i', $cached['t']);
$worker = $d['worker'];
$employments = $d['employments'];

// HR app record
$stmt = $conDB->prepare('SELECT e.*, d.dep_nme FROM employees e LEFT JOIN department d ON d.id = e.dept WHERE e.emp_id = ? LIMIT 1');
$stmt->bind_param('s', $targetEmp);
$stmt->execute();
$local = $stmt->get_result()->fetch_assoc() ?: null;
$stmt->close();

// Current employment = latest start
$current = null;
foreach ($employments as $emp) {
    if ($current === null || strcmp($emp['EmploymentStartDate'], $current['EmploymentStartDate']) > 0) {
        $current = $emp;
    }
}

// Financial transactions of the worker in D365 (general journal lines). Payroll synced by this app ("PAY yyyy-mm ...")
// goes to $payroll (per month card + Transactions table) and stays out of the summary / by-account totals;
// finance's own salary entries (رواتب) are left out
$accountNames = $worker ? $workers->mainAccountNames() : [];
$fin = [];
$totDebit = 0.0;
$totCredit = 0.0;
$byAccount = [];
$byCompany = [];
$postedCount = 0;
$payroll = [];       // app payroll lines
$payrollMonths = []; // 'yyyy-mm' => company, journal, posted, debit, credit, net, lines
foreach ($d['journal_lines'] as $jl) {
    $text = (string)$jl['Text'];
    if (strpos($text, 'PAY ') === 0) {
        $header = ($d['journals'] ?? [])[strtolower($jl['dataAreaId']) . '|' . $jl['JournalBatchNumber']] ?? null;
        $jl['_main'] = explode('-', (string)$jl['AccountDisplayValue'])[0];
        $jl['_posted'] = $header ? ($header['IsPosted'] === 'Yes') : null;
        $jl['_type'] = 'payroll';
        $payroll[] = $jl;
        $month = explode(' ', $text)[1] ?? '';
        $pm = &$payrollMonths[$month];
        $pm = [
            'company' => strtoupper($jl['dataAreaId']), 'journal' => $jl['JournalBatchNumber'], 'posted' => $jl['_posted'],
            'debit' => ($pm['debit'] ?? 0) + (float)$jl['DebitAmount'], 'credit' => ($pm['credit'] ?? 0) + (float)$jl['CreditAmount'],
            'net' => ($pm['net'] ?? 0) + (stripos($text, 'Net salary') !== false ? (float)$jl['CreditAmount'] : 0),
            'lines' => ($pm['lines'] ?? 0) + 1,
        ];
        unset($pm);
        continue;
    }
    if (mb_strpos($text, 'رواتب') !== false) {
        continue;
    }
    $debit = (float)$jl['DebitAmount'];
    $credit = (float)$jl['CreditAmount'];
    $company = strtoupper($jl['dataAreaId']);
    $main = explode('-', (string)$jl['AccountDisplayValue'])[0];
    $header = ($d['journals'] ?? [])[strtolower($jl['dataAreaId']) . '|' . $jl['JournalBatchNumber']] ?? null;
    $posted = $header ? ($header['IsPosted'] === 'Yes') : null;
    $jl['_main'] = $main;
    $jl['_posted'] = $posted;
    $jl['_journal'] = $header;
    $jl['_type'] = 'other';
    $fin[] = $jl;
    $totDebit += $debit;
    $totCredit += $credit;
    $postedCount += $posted ? 1 : 0;
    $a = &$byAccount[$main];
    $a = ['debit' => ($a['debit'] ?? 0) + $debit, 'credit' => ($a['credit'] ?? 0) + $credit, 'count' => ($a['count'] ?? 0) + 1, 'last' => max($a['last'] ?? '', (string)$jl['TransDate'])];
    unset($a);
    $c = &$byCompany[$company];
    $c = ['debit' => ($c['debit'] ?? 0) + $debit, 'credit' => ($c['credit'] ?? 0) + $credit, 'count' => ($c['count'] ?? 0) + 1];
    unset($c);
}
uasort($byAccount, function ($x, $y) { return abs($y['debit'] - $y['credit']) <=> abs($x['debit'] - $x['credit']); });
ksort($byCompany);
krsort($payrollMonths);
// Transactions table: other lines + payroll lines, newest first
$allLines = array_merge($fin, $payroll);
usort($allLines, function ($a, $b) {
    return strcmp($b['TransDate'], $a['TransDate']) ?: strcmp($a['JournalBatchNumber'], $b['JournalBatchNumber']) ?: $a['LineNumber'] <=> $b['LineNumber'];
});
$money = function ($v) { return number_format((float)$v, 2); };
?>
<div class="d365tab">
    <div class="d365tab-bar">
        <div>
            <span class="sr-pill tone-<?= $env === 'sandbox' ? 'sky' : 'red' ?>"><span class="sr-dot"></span>D365 <?= $e(ucfirst($env)) ?></span>
            <?php if ($worker): ?>
                <span class="sr-pill tone-green"><span class="sr-dot"></span>Registered<?= $current ? ' · ' . $e(strtoupper($current['LegalEntityId'])) : '' ?></span>
            <?php else: ?>
                <span class="sr-pill tone-red"><span class="sr-dot"></span>Not in D365</span>
            <?php endif; ?>
            <?php if ($dateFrom !== '' || $dateTo !== ''): ?>
                <span class="sr-chip"><i class="mdi mdi-calendar-range"></i> <?= $e(($dateFrom ?: 'Start') . ' → ' . ($dateTo ?: 'Today')) ?></span>
            <?php endif; ?>
            <span class="d365tab-muted">Loaded <?= $e($loadedAt) ?> · dates in Riyadh time</span>
        </div>
        <button type="button" class="d365tab-btn" id="d365TabRefresh"><i class="mdi mdi-refresh"></i> Refresh from D365</button>
    </div>

    <?php foreach ($d['errors'] as $err): ?>
        <div class="sr-notice tone-amber"><?= $e($err) ?></div>
    <?php endforeach; ?>

    <?php if (!$worker && !$d['errors']): ?>
        <div class="sr-notice tone-amber">Personnel number <b><?= $e($targetEmp) ?></b> is not in D365 <?= $e($env) ?><?= $reportMode ? '.' : ' - register the worker (with contact details and bank account).' ?>
            <?php if (!$reportMode): ?><button type="button" class="d365tab-btn" id="d365TabRegister" style="margin-left:8px"<?= $client->canWrite() ? '' : ' disabled title="Writes are off (App Settings &gt; D365 Config)"' ?>><i class="mdi mdi-account-plus"></i> Add to D365</button><?php endif; ?></div>
    <?php endif; ?>

    <?php if ($worker):
        // Department dimension: what D365 has on the current employment vs what the app department maps to
        $deptFormats = ['default' => []];
        $deptExpected = '';
        try {
            $deptFormats = D365Payroll::fetchDimensionFormats($client);
            $deptExpected = $workers->departmentFor($targetEmp);
        } catch (Throwable $ex) {
        }
        $deptIdx = array_search('department', array_map('strtolower', $deptFormats['default'] ?: []), true);
        $deptD365 = trim(explode('-', (string)($current['DimensionDisplayValue'] ?? ''))[$deptIdx === false ? 2 : $deptIdx] ?? '');
        // Cost center is not synced to the D365 employment - payroll lines take it from the HR app (MHO only)
        $ccCompany = $workers->payrollCompany($targetEmp, $current['LegalEntityId'] ?? '');
        $ccUsed = $workers->companyUsesCostCenter($ccCompany) !== false;
        $ccRow = ['Cost Center (payroll)', $ccUsed ? (string)($local['cost_center'] ?? '') : 'Not used in ' . $ccCompany, 'Taken from HR app on payroll lines', false];
        $compare = [
            ['Name', $local['name'] ?? '', $worker['Name'] ?? '', true],
            ['Joining date', $local['joining_date'] ?? '', d365tab_date($current['EmploymentStartDate'] ?? ''), true],
            ['Date of birth', $local['dob'] ?? '', d365tab_date($worker['BirthDate'] ?? ''), true],
            ['Email', ($local['c_email'] ?? '') ?: ($local['email'] ?? ''), $worker['PrimaryContactEmail'] ?? '', true],
            ['Mobile', $local['mobile'] ?? '', $worker['PrimaryContactPhone'] ?? '', true],
            ['IBAN', $local['iban'] ?? '', $d['banks'][0]['BankIBAN'] ?? '', true],
            ['Gender', ['1' => 'Male', '2' => 'Female'][(string)($local['sex'] ?? '')] ?? (string)($local['sex'] ?? ''),d365tab_empty($worker['Gender'] ?? '') ? '' : $worker['Gender'], true],
            ['Marital status', $local['mar_status'] ?? '', d365tab_empty($worker['MaritalStatus'] ?? '') ? '' : $worker['MaritalStatus'], false],
            ['Department' . (!empty($local['dep_nme']) ? ' (' . $local['dep_nme'] . ')' : ''), $deptExpected, $deptD365, true],
            $ccRow,
            ['Status', isset($local['status']) ? ((string)$local['status'] === '1' ? 'Active' : 'Inactive (' . $local['status'] . ')') : '', $worker['WorkerStatus'] ?? '', false],
        ]; ?>
        <div class="sr-card">
            <div class="sr-card-head">
                <div class="sr-card-title">HR app vs D365</div>
                <div class="sr-card-sub">
                    <?php if (!$reportMode): ?>Sync sends name, birth date, gender, email, mobile, marital status, IBAN and a missing department from the HR app to D365
                    <button type="button" class="d365tab-btn" id="d365TabSync" style="margin-left:8px"<?= $client->canWrite() ? '' : ' disabled title="Writes are off (App Settings > D365 Config)"' ?>><i class="mdi mdi-sync"></i> Sync to D365</button><?php else: ?>Employee record in the HR app compared with D365<?php endif; ?>
                </div>
            </div>
            <div class="sr-table-wrap">
                <table class="sr-table">
                    <thead><tr><th>Field</th><th>HR app</th><th>D365</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($compare as $row):
                        [$label, $lv, $dv, $check] = $row;
                        $tone = $row[4] ?? null;
                        if ($check && $local) {
                            if ($lv === '' && $dv === '') { $tone = null; }
                            elseif ($dv === '') { $tone = ['amber', 'Missing in D365']; }
                            elseif ($lv === '') { $tone = ['amber', 'Missing in app']; }
                            elseif (d365tab_norm($lv) === d365tab_norm($dv)) { $tone = ['green', 'Match']; }
                            else { $tone = ['red', 'Different']; }
                        } ?>
                        <tr>
                            <td class="d365tab-muted"><?= $e($label) ?></td>
                            <td><?= $lv !== '' ? $e($lv) : '<span class="d365tab-muted">-</span>' ?></td>
                            <td><?= $dv !== '' ? $e($dv) : '<span class="d365tab-muted">-</span>' ?></td>
                            <td><?php if ($tone): ?><span class="sr-pill tone-<?= $tone[0] ?>"><span class="sr-dot"></span><?= $tone[1] ?></span><?php endif; ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="d365tab-grid">
            <div class="sr-card">
                <div class="sr-card-head"><div class="sr-card-title">D365 worker record</div><div class="sr-card-sub">Filled fields only</div></div>
                <div class="sr-card-body">
                    <dl class="sr-kv">
                        <?php foreach ($worker as $k => $v):
                            if (d365tab_empty($v)) continue;
                            if (is_string($v) && preg_match('/^\d{4}-\d{2}-\d{2}T/', $v)) $v = d365tab_date($v, true); ?>
                            <div class="row-kv"><dt><?= $e($k) ?></dt><dd><?= $e(is_bool($v) ? ($v ? 'Yes' : 'No') : $v) ?></dd></div>
                        <?php endforeach; ?>
                    </dl>
                </div>
            </div>

            <div>
                <div class="sr-card">
                    <div class="sr-card-head"><div class="sr-card-title">Employments</div><div class="sr-card-sub"><?= count($employments) ?> record(s)</div></div>
                    <div class="sr-table-wrap">
                        <table class="sr-table">
                            <thead><tr><th>Company</th><th>Start</th><th>End</th><th>Dimensions</th></tr></thead>
                            <tbody>
                            <?php foreach ($employments as $emp): ?>
                                <tr>
                                    <td><span class="sr-chip"><?= $e(strtoupper($emp['LegalEntityId'])) ?></span></td>
                                    <td><?= $e(d365tab_date($emp['EmploymentStartDate'])) ?></td>
                                    <td><?= $e(d365tab_date($emp['EmploymentEndDate'])) ?></td>
                                    <td class="sr-mono"><?= $e($emp['DimensionDisplayValue'] ?? '') ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (!$employments): ?><tr><td colspan="4" class="d365tab-muted">None</td></tr><?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="sr-card">
                    <div class="sr-card-head"><div class="sr-card-title">Position assignments</div><div class="sr-card-sub"><?= count($d['positions']) ?> record(s)</div></div>
                    <div class="sr-table-wrap">
                        <table class="sr-table">
                            <thead><tr><th>Position</th><th>From</th><th>To</th></tr></thead>
                            <tbody>
                            <?php foreach ($d['positions'] as $p): ?>
                                <tr>
                                    <td><?= $e($p['PositionId'] ?? '') ?></td>
                                    <td><?= $e(d365tab_date($p['ValidFrom'] ?? '')) ?></td>
                                    <td><?= $e(d365tab_date($p['ValidTo'] ?? '')) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (!$d['positions']): ?><tr><td colspan="3" class="d365tab-muted">None</td></tr><?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="sr-card">
                    <div class="sr-card-head"><div class="sr-card-title">Bank accounts</div><div class="sr-card-sub"><?= count($d['banks']) ?> record(s)</div></div>
                    <div class="sr-table-wrap">
                        <table class="sr-table">
                            <thead><tr><th>Bank</th><th>IBAN</th></tr></thead>
                            <tbody>
                            <?php foreach ($d['banks'] as $b): ?>
                                <tr>
                                    <td><?= $e($b['BankName'] ?? $b['Name'] ?? $b['BankAccountId'] ?? '') ?></td>
                                    <td class="sr-mono"><?= $e($b['BankIBAN'] ?? '') ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (!$d['banks']): ?><tr><td colspan="2" class="d365tab-muted">None</td></tr><?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($worker): ?>
        <div class="sr-card">
            <div class="sr-card-head">
                <div class="sr-card-title">Financial summary</div>
                <div class="sr-card-sub">General journal lines of <?= $e($targetEmp) ?> in D365 (fees, advances, loans, settlements, transfers...) · payroll shown separately below</div>
            </div>
            <div class="sr-card-body">
                <div class="d365fin-tiles">
                    <div class="d365fin-tile"><span>Transactions</span><b><?= count($fin) ?></b><small><?= $postedCount ?> posted · <?= count($fin) - $postedCount ?> not posted</small></div>
                    <div class="d365fin-tile"><span>Total debit</span><b><?= $money($totDebit) ?></b><small>SAR</small></div>
                    <div class="d365fin-tile"><span>Total credit</span><b><?= $money($totCredit) ?></b><small>SAR</small></div>
                    <div class="d365fin-tile is-<?= $totDebit - $totCredit > 0.004 ? 'debit' : ($totCredit - $totDebit > 0.004 ? 'credit' : 'zero') ?>">
                        <span>Net balance</span><b><?= $money(abs($totDebit - $totCredit)) ?></b>
                        <small><?= $totDebit - $totCredit > 0.004 ? 'Debit (owed by employee / paid out)' : ($totCredit - $totDebit > 0.004 ? 'Credit (owed to employee / recovered)' : 'Balanced') ?></small>
                    </div>
                </div>
                <?php if ($byCompany): ?>
                    <div class="d365fin-companies">
                        <?php foreach ($byCompany as $co => $c): ?>
                            <span class="sr-chip"><?= $e($co) ?> · <?= $c['count'] ?> lines · net <?= $money($c['debit'] - $c['credit']) ?></span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="sr-card">
            <div class="sr-card-head">
                <div class="sr-card-title">By account</div>
                <div class="sr-card-sub">Main accounts the worker's transactions were booked to</div>
            </div>
            <div class="sr-table-wrap">
                <table class="sr-table">
                    <thead><tr><th>Account</th><th class="text-right">Lines</th><th class="text-right">Debit</th><th class="text-right">Credit</th><th class="text-right">Net</th><th>Last date</th></tr></thead>
                    <tbody>
                    <?php foreach ($byAccount as $acc => $a): $net = $a['debit'] - $a['credit']; ?>
                        <tr>
                            <td><div class="sr-cell-title"><?= $e($accountNames[$acc] ?? '-') ?></div><div class="sr-cell-sub sr-mono"><?= $e($acc) ?></div></td>
                            <td class="text-right"><?= $a['count'] ?></td>
                            <td class="text-right"><?= $a['debit'] ? $money($a['debit']) : '' ?></td>
                            <td class="text-right"><?= $a['credit'] ? $money($a['credit']) : '' ?></td>
                            <td class="text-right"><b class="d365fin-<?= $net >= 0 ? 'debit' : 'credit' ?>"><?= $money($net) ?></b></td>
                            <td><?= $e(d365tab_date($a['last'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$byAccount): ?><tr><td colspan="6" class="d365tab-muted">No financial transactions in D365</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="sr-card">
            <div class="sr-card-head">
                <div class="sr-card-title"><i class="mdi mdi-cash-multiple"></i> Payroll in D365</div>
                <div class="sr-card-sub">Payroll synced by the HR app, per month (lines "PAY yyyy-mm <?= $e($targetEmp) ?> ...")</div>
            </div>
            <div class="sr-table-wrap">
                <table class="sr-table">
                    <thead><tr><th>Month</th><th>Company</th><th>Journal</th><th class="text-right">Lines</th><th class="text-right">Gross (debit)</th><th class="text-right">Net salary</th></tr></thead>
                    <tbody>
                    <?php foreach ($payrollMonths as $m => $pm): ?>
                        <tr>
                            <td><b><?= $e($m !== '' ? date('F Y', strtotime($m . '-01')) : '-') ?></b></td>
                            <td><span class="sr-chip"><?= $e($pm['company']) ?></span></td>
                            <td>
                                <div class="sr-mono"><?= $e($pm['journal']) ?></div>
                                <?php if ($pm['posted'] === true): ?><span class="sr-pill tone-green"><span class="sr-dot"></span>Posted</span>
                                <?php elseif ($pm['posted'] === false): ?><span class="sr-pill tone-amber"><span class="sr-dot"></span>Not posted</span><?php endif; ?>
                            </td>
                            <td class="text-right"><?= $pm['lines'] ?></td>
                            <td class="text-right"><?= $money($pm['debit']) ?></td>
                            <td class="text-right"><b class="d365fin-credit"><?= $money($pm['net']) ?></b></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$payrollMonths): ?><tr><td colspan="6" class="d365tab-muted">No payroll synced to D365 yet</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="sr-card">
            <div class="sr-card-head">
                <div class="sr-card-title">Transactions</div>
                <div class="sr-card-sub">Newest first · amount = debit (+) / credit (-)
                    <span class="d365fin-filter" style="margin-left:8px">
                        <button type="button" class="d365tab-btn is-active" data-fin-type="">All (<?= count($allLines) ?>)</button>
                        <button type="button" class="d365tab-btn" data-fin-type="Payroll">Payroll (<?= count($payroll) ?>)</button>
                        <button type="button" class="d365tab-btn" data-fin-type="Other">Other (<?= count($fin) ?>)</button>
                    </span>
                </div>
            </div>
            <div class="sr-table-wrap" style="padding:0 12px 12px">
                <table class="sr-table" id="d365FinTable" style="width:100%">
                    <thead><tr><th>Date</th><th>Type</th><th>Company</th><th>Journal</th><th>Voucher</th><th>Account</th><th>Description</th><th class="text-right">Amount</th></tr></thead>
                    <tbody>
                    <?php foreach ($allLines as $jl): $amt = (float)$jl['DebitAmount'] - (float)$jl['CreditAmount']; ?>
                        <tr>
                            <td data-order="<?= $e(substr((string)$jl['TransDate'], 0, 10)) ?>"><?= $e(d365tab_date($jl['TransDate'])) ?></td>
                            <td><?= $jl['_type'] === 'payroll' ? '<span class="sr-pill tone-sky"><span class="sr-dot"></span>Payroll</span>' : '<span class="sr-pill tone-slate"><span class="sr-dot"></span>Other</span>' ?></td>
                            <td><span class="sr-chip"><?= $e(strtoupper($jl['dataAreaId'])) ?></span></td>
                            <td>
                                <div class="sr-mono"><?= $e($jl['JournalBatchNumber']) ?></div>
                                <?php if ($jl['_posted'] === true): ?><span class="sr-pill tone-green"><span class="sr-dot"></span>Posted</span>
                                <?php elseif ($jl['_posted'] === false): ?><span class="sr-pill tone-amber"><span class="sr-dot"></span>Not posted</span><?php endif; ?>
                            </td>
                            <td class="sr-mono"><?= $e($jl['Voucher']) ?></td>
                            <td><div class="sr-cell-title"><?= $e($accountNames[$jl['_main']] ?? '') ?></div><div class="sr-cell-sub sr-mono"><?= $e($jl['AccountDisplayValue']) ?></div></td>
                            <td><?= $e($jl['Text']) ?></td>
                            <td class="text-right" data-order="<?= $amt ?>"><b class="d365fin-<?= $amt >= 0 ? 'debit' : 'credit' ?>"><?= $money($amt) ?></b></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>
