<?php
/**
 * D365 payroll settings (account mapping) + month overview.
 * Page + month sync: system admins and the 'd365_sync_payroll' special access; account mapping (save_settings): system admins only.
 * Syncing is done per month here, or per employee from the D365 / Payrolls tabs of view_employee.php (paid months only), as an UNPOSTED general journal.
 * One journal per environment + month + legal entity; finance reviews and posts it in D365.
 * Line building / logging lives in includes/D365Payroll.php.
 */
require_once __DIR__ . '/includes/session_check.php';
require_once __DIR__ . '/includes/D365Payroll.php';

$canSyncPayroll = user_has_special_access($conDB, $empid ?? '', 'd365_sync_payroll', $user_role ?? '', $user_type ?? '', $is_system_admin ?? false);
if (!$canSyncPayroll) {
    http_response_code(403);
    die('Access Denied: You do not have permission to sync payroll to D365');
}

date_default_timezone_set('Asia/Riyadh');
// D365 (esp. sandbox) can be slow - lift the 25s app-wide limit from includes/db.php for this page
@set_time_limit(180);

if (empty($_SESSION['d365_csrf'])) {
    $_SESSION['d365_csrf'] = bin2hex(random_bytes(16));
}
$csrf = $_SESSION['d365_csrf'];
$userId = (string)($empid ?? ($_SESSION['user_id'] ?? ''));

$client = null;
$clientError = '';
try {
    $client = new D365Client();
} catch (Throwable $ex) {
    $clientError = $ex->getMessage();
}
$environment = $client ? $client->getEnvironment() : 'unknown';
$payroll = new D365Payroll($conDB, $client);

/**
 * Cache for D365 lookups that rarely change, kept in the system temp dir (not the session:
 * the employment list is large, and a big session rewritten on every sync request made
 * sign-outs mid-sync more likely). 'refresh_d365' clears it.
 */
function d365_cache_file($key)
{
    global $client, $environment;
    $url = $client ? $client->getResourceUrl() : $environment;
    return sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'd365_cache_' . md5($url . '|' . $key) . '.json';
}
function d365_cached($key, $ttl, callable $fn)
{
    $file = d365_cache_file($key);
    if (is_file($file) && time() - filemtime($file) < $ttl) {
        $value = json_decode((string)file_get_contents($file), true);
        if ($value !== null) {
            return $value;
        }
    }
    $value = $fn();
    @file_put_contents($file, json_encode($value, JSON_UNESCAPED_UNICODE), LOCK_EX);
    return $value;
}
unset($_SESSION['d365_cache']); // old session-based cache

function d365_json($data, $code = 200)
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// ------------------------------------------------------------------ POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($csrf, (string)($_POST['csrf'] ?? ''))) {
        d365_json(['error' => 'Session expired - reload the page'], 400);
    }
    $action = $_POST['action'] ?? '';

    if ($action === 'save_settings') {
        if (!$is_system_admin) {
            http_response_code(403);
            die('Access Denied: Only system administrators can change the D365 account mapping');
        }
        $payroll->saveSettings($_POST['s'] ?? [], $userId);
        header('Location: d365_payroll_push.php?month=' . urlencode($_POST['month'] ?? '') . '&entity=' . urlencode($_POST['entity'] ?? '') . '&saved=1#mapping');
        exit;
    }

    if ($action === 'refresh_d365') {
        foreach (['employments', 'main_accounts', 'journal_names'] as $k) {
            @unlink(d365_cache_file($k));
        }
        d365_json(['ok' => true]);
    }

    // Employees the sync skipped because they are not in D365: names + suggested D365 company, then register one by one
    if ($action === 'missing_workers' || $action === 'register_worker') {
        @set_time_limit(180);
        session_write_close();
        if (!$client) {
            d365_json(['error' => $clientError], 400);
        }
        require_once __DIR__ . '/includes/D365Workers.php';
        $workers = new D365Workers($conDB, $client);
        $valid = function ($id) { return preg_match('/^[A-Za-z0-9\-]{1,20}$/', $id); };
        try {
            if ($action === 'missing_workers') {
                $ids = array_slice(array_values(array_filter(array_map('strval', (array)($_POST['emp_ids'] ?? [])), $valid)), 0, 500);
                $rows = [];
                $entities = [];
                if ($ids) {
                    $stmt = $conDB->prepare('SELECT e.emp_id, e.name, c.comp_name FROM employees e LEFT JOIN companies c ON c.comp_id = e.comp_no WHERE e.emp_id IN (' . implode(',', array_fill(0, count($ids), '?')) . ')');
                    $stmt->bind_param(str_repeat('s', count($ids)), ...$ids);
                    $stmt->execute();
                    $names = [];
                    foreach ($stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $r) {
                        $names[$r['emp_id']] = $r;
                    }
                    $stmt->close();
                    foreach ($ids as $id) {
                        $sug = $workers->suggestCompanyFor($id);
                        $entities = $sug['entities'];
                        $rows[] = ['emp_id' => $id, 'name' => $names[$id]['name'] ?? '', 'app_company' => $names[$id]['comp_name'] ?? '', 'suggest' => $sug['company']];
                    }
                }
                d365_json(['rows' => $rows, 'entities' => $entities, 'can_write' => $client->canWrite()]);
            }
            $regEmp = (string)($_POST['emp_id'] ?? '');
            if (!$valid($regEmp)) {
                d365_json(['ok' => false, 'error' => 'Invalid employee ID'], 400);
            }
            $result = $workers->register($regEmp, (string)($_POST['company'] ?? ''));
            if ($result['ok']) {
                // the next sync must see the new employment straight away
                @unlink(d365_cache_file('employments'));
            }
            d365_json($result);
        } catch (Throwable $ex) {
            d365_json(['ok' => false, 'error' => $ex->getMessage()], 500);
        }
    }

    // Whole-month sync: list the paid employees still to sync, then the browser sends them in chunks
    if ($action === 'month_pending' || $action === 'sync_chunk') {
        @set_time_limit(300);
        // Nothing below writes the session - release its lock so the page's other requests are not held up
        session_write_close();
        $month = (string)($_POST['month'] ?? '');
        if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
            d365_json(['error' => 'Invalid month'], 400);
        }
        if (!$client) {
            d365_json(['error' => $clientError], 400);
        }
        if (!$client->canWrite()) {
            d365_json(['error' => "Writes are disabled for the $environment environment (App Settings > D365 Config > Allow Writes)"], 400);
        }
        if ($action === 'month_pending') {
            try {
                // A journal deleted in D365 frees its employees for a new sync
                $payroll->checkMonthJournals($environment, $month);
                d365_json(['emp_ids' => $payroll->getPendingEmpIds($environment, $month)]);
            } catch (Throwable $ex) {
                d365_json(['error' => $ex->getMessage()], 500);
            }
        }
        $empIds = array_slice(array_values(array_filter(array_map('strval', (array)($_POST['emp_ids'] ?? [])), function ($id) {
            return preg_match('/^[A-Za-z0-9\-]{1,20}$/', $id);
        })), 0, 25);
        if (!$empIds) {
            d365_json(['error' => 'No employees in this chunk'], 400);
        }
        try {
            $employments = d365_cached('employments', 900, function () use ($payroll) { return $payroll->getEmployments(); });
            d365_json($payroll->syncMonthChunk($month, $empIds, $employments, $userId));
        } catch (Throwable $ex) {
            d365_json(['error' => $ex->getMessage()], 500);
        }
    }

    d365_json(['error' => 'Unknown action'], 400);
}

// ------------------------------------------------------------------ page data
$settings = $payroll->getSettings();
$months = $payroll->getMonths();
$monthSummary = $payroll->getMonthSummary($environment, 12);
$month = (string)($_GET['month'] ?? ($months[0]['month_year'] ?? date('Y-m')));
if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
    $month = date('Y-m');
}
$entityFilter = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string)($_GET['entity'] ?? '')));
$transDateDefault = date('Y-m-t', strtotime($month . '-01'));
$approval = $payroll->getApprovalStatus($month);

$d365Error = '';
$employments = [];
$mainAccounts = [];
$journalNames = [];
if ($client) {
    try {
        $employments = d365_cached('employments', 600, function () use ($payroll) { return $payroll->getEmployments(); });
        $mainAccounts = d365_cached('main_accounts', 3600, function () use ($client) {
            $r = $client->getAll('MainAccounts', ['$select' => 'MainAccountId,Name,MainAccountType']);
            if ($r['error']) {
                throw new RuntimeException('D365 MainAccounts: ' . $r['error']);
            }
            return array_values(array_filter(array_map(function ($a) {
                return in_array($a['MainAccountType'], ['Total', 'Reporting'], true) ? null : [$a['MainAccountId'], $a['Name']];
            }, $r['data']['value'])));
        });
        $journalNames = d365_cached('journal_names', 3600, function () use ($client) {
            $r = $client->getAll('JournalNames', ['$select' => 'dataAreaId,Name,Description,OffsetAccountDisplayValue,Type'], true);
            if ($r['error']) {
                throw new RuntimeException('D365 JournalNames: ' . $r['error']);
            }
            return $r['data']['value'];
        });
    } catch (Throwable $ex) {
        $d365Error = $ex->getMessage();
    }
}

$pushed = $payroll->getPushedMap($environment, $month);
$rows = $payroll->getPayrolls($month);
$preview = [];
$entityCounts = [];
foreach ($rows as $id => $p) {
    $emp = $employments[$id] ?? null;
    $built = D365Payroll::buildLines($p, $settings, $month);
    $state = 'ready';
    if (isset($pushed[$id])) {
        $state = 'pushed';
    } elseif (strtolower((string)$p['status']) !== 'paid') {
        $state = 'unpaid';
    } elseif (!$emp) {
        $state = 'missing';
    } elseif ($built['errors']) {
        $state = 'error';
    }
    $ent = $emp['entity'] ?? '-';
    $entityCounts[$ent] = ($entityCounts[$ent] ?? 0) + 1;
    if ($entityFilter !== '' && $ent !== $entityFilter) {
        continue;
    }
    $preview[$id] = ['p' => $p, 'emp' => $emp, 'built' => $built, 'state' => $state];
}
ksort($entityCounts);
$stateCounts = array_count_values(array_column($preview, 'state'));

$e = function ($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); };
$money = function ($v) { return number_format((float)$v, 2); };
$stateTone = ['ready' => ['sky', 'Ready'], 'pushed' => ['green', 'Synced'], 'missing' => ['amber', 'Not in D365'], 'error' => ['red', 'Mapping missing'], 'unpaid' => ['slate', 'Not paid']];
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>D365 Payroll Settings</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
    <link href="./plugins/sweet-alert/v11/sweetalert2.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/smart_request.css?v=<?= @filemtime(__DIR__ . '/assets/css/smart_request.css') ?>" rel="stylesheet" type="text/css" />
    <style>
        .ms-months { display: grid; gap: 8px; text-align: start; max-height: 52vh; overflow-y: auto; padding: 2px; }
        .ms-month { display: flex; align-items: center; gap: 12px; padding: 10px 12px; border: 1px solid var(--sr-border); border-radius: 10px; cursor: pointer; background: var(--sr-surface); }
        .ms-month:hover { border-color: var(--sr-accent); }
        .ms-month.is-disabled { opacity: .55; cursor: not-allowed; }
        .ms-month input { margin: 0; }
        .ms-month .ms-title { font-weight: 700; color: var(--sr-text); min-width: 70px; }
        .ms-month .ms-meta { font-size: 12px; color: var(--sr-muted); flex: 1; }
        .ms-bar { height: 12px; border-radius: 6px; background: var(--sr-surface-3, #e5e7eb); overflow: hidden; margin: 14px 0 8px; }
        .ms-bar > div { height: 100%; width: 0; background: var(--sr-accent, #6366f1); transition: width .25s; }
        .ms-stats { display: flex; justify-content: space-between; gap: 8px; font-size: 13px; color: var(--sr-text-2); }
        .ms-stats b { color: var(--sr-text); }
        .ms-log { margin-top: 12px; max-height: 180px; overflow-y: auto; text-align: start; font-size: 12px; }
        .ms-log div { padding: 4px 0; border-bottom: 1px dashed var(--sr-border); color: var(--tone-red-fg, #b91c1c); }
        .sr-card + .sr-card, .sr-notice + .sr-card, .sr-card + .sr-notice { margin-top: 16px; }
        .pp-filters { display: flex; flex-wrap: wrap; gap: 12px; align-items: flex-end; }
        .pp-filters label { display: block; margin-bottom: 4px; font-size: 12px; font-weight: 600; color: var(--sr-muted); }
        .pp-filters .form-control { min-width: 160px; }
        .pp-map { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 12px 16px; }
        .pp-map label { display: block; margin-bottom: 4px; font-size: 12px; font-weight: 600; color: var(--sr-muted); }
        .pp-map textarea { min-height: 96px; font-family: monospace; font-size: 12px; }
        .pp-help { font-size: 12px; color: var(--sr-muted); margin-top: 4px; }
        .pp-section { margin: 18px 0 8px; font-weight: 700; color: var(--sr-text); }
        .pp-lines td, .pp-lines th { padding: 4px 8px !important; font-size: 12px; }
        .pp-detail > td { background: var(--sr-surface-2); }
        .pp-num { text-align: end; font-variant-numeric: tabular-nums; white-space: nowrap; }
        .pp-tiles { display: flex; flex-wrap: wrap; gap: 8px; }
        .pp-tiles a { text-decoration: none; }
        .pp-progress { height: 8px; border-radius: 4px; background: var(--sr-surface-3); overflow: hidden; margin-top: 10px; display: none; }
        .pp-progress > div { height: 100%; width: 0; background: var(--sr-accent); transition: width .2s; }
        .pp-msg { font-size: 12px; }
        tr[data-toggle-detail] { cursor: pointer; }
    </style>
</head>
<body class="sr-standalone">
<div class="sr-page sr-standalone-wrap">
    <div class="sr-standalone-brand">
        <a href="dashboard.php"><img src="<?= $e(get_setting($conDB, 'logo')) ?>" alt=""></a>
    </div>
    <div class="sr-head">
        <div>
            <h1>D365 Payroll Sync</h1>
            <p>Sync a whole month's <b>paid</b> payroll in one click, or single employees from the Employee lookup page. Each employee is booked in their own D365 company - one <b>unposted</b> journal per company per month that finance posts in D365.</p>
        </div>
        <div class="sr-head-actions">
            <button type="button" class="sr-btn sr-btn-primary" id="btnSyncMonth" <?= $client && $client->canWrite() ? '' : 'disabled title="Writes are off (App Settings > D365 Config)"' ?>>Sync month to D365</button>
            <span class="sr-pill tone-<?= $environment === 'sandbox' ? 'sky' : 'red' ?>" title="<?= $e($client ? $client->getResourceUrl() : '') ?>"><span class="sr-dot"></span><?= $e(ucfirst($environment)) ?></span>
            <span class="sr-pill tone-<?= $client && $client->canWrite() ? 'green' : 'slate' ?>"><span class="sr-dot"></span><?= $client && $client->canWrite() ? 'Writes on' : 'Read only' ?></span>
            <a class="sr-btn sr-btn-ghost" href="d365_employee_compare.php">Employee check</a>
        </div>
    </div>

    <?php if ($clientError): ?><div class="sr-notice tone-red"><?= $e($clientError) ?></div><?php endif; ?>
    <?php if ($d365Error): ?><div class="sr-notice tone-red" style="margin-top:12px"><?= $e($d365Error) ?></div><?php endif; ?>
    <?php if (isset($_GET['saved'])): ?><div class="sr-notice tone-green" style="margin-top:12px">Account mapping saved.</div><?php endif; ?>
    <?php if ($client && !$client->canWrite()): ?>
        <div class="sr-notice tone-amber" style="margin-top:12px">Sync is off for the <?= $e($environment) ?> environment. Set <b>Allow Writes = Yes</b> in App Settings &gt; D365 Config to sync (sandbox first).</div>
    <?php endif; ?>

    <div class="sr-card" style="margin-top:16px">
        <div class="sr-card-body">
            <form method="get" class="pp-filters">
                <div>
                    <label>Payroll month</label>
                    <select name="month" class="form-control" onchange="this.form.submit()">
                        <?php foreach ($months as $m): ?>
                            <option value="<?= $e($m['month_year']) ?>" <?= $m['month_year'] === $month ? 'selected' : '' ?>><?= $e($m['month_year']) ?> · <?= (int)$m['n'] ?> employees · <?= $money($m['net']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label>D365 company</label>
                    <select name="entity" class="form-control" onchange="this.form.submit()">
                        <option value="">All companies (preview)</option>
                        <?php foreach ($entityCounts as $ent => $n): ?>
                            <option value="<?= $e($ent) ?>" <?= $ent === $entityFilter ? 'selected' : '' ?>><?= $e($ent === '-' ? 'Not in D365' : $ent) ?> (<?= $n ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label>Journal date</label>
                    <input type="date" id="transDate" class="form-control" value="<?= $e($transDateDefault) ?>">
                </div>
                <div>
                    <label>Approval</label>
                    <span class="sr-pill tone-<?= in_array($approval, ['approved', 'completed'], true) ? 'green' : 'amber' ?>"><span class="sr-dot"></span><?= $e($approval ? str_replace('_', ' ', $approval) : 'no request') ?></span>
                </div>
                <div style="margin-inline-start:auto">
                    <button type="button" class="sr-btn sr-btn-ghost" id="btnRefresh">Reload D365 data</button>
                </div>
            </form>
            <div class="pp-tiles" style="margin-top:12px">
                <?php foreach ($stateTone as $st => [$tone, $label]): ?>
                    <span class="sr-pill tone-<?= $tone ?>"><span class="sr-dot"></span><?= $label ?>: <?= (int)($stateCounts[$st] ?? 0) ?></span>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="sr-card">
        <div class="sr-card-head">
            <div class="sr-card-title">Employees · <?= $e($month) ?><?= $entityFilter ? ' · ' . $e($entityFilter) : '' ?></div>
            <div class="sr-card-sub">Click a row to preview its journal lines, or Open to sync that employee.</div>
        </div>
        <div class="sr-table-wrap">
            <table class="sr-table">
                <thead>
                <tr>
                    <th style="width:60px"></th>
                    <th>Employee</th><th>Company</th><th class="pp-num">Gross</th><th class="pp-num">Benefits</th><th class="pp-num">Deductions</th><th class="pp-num">Net</th><th class="pp-num">Lines</th><th>Status</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($preview as $id => $r): $p = $r['p']; [$tone, $label] = $stateTone[$r['state']]; ?>
                    <tr data-toggle-detail="<?= $e($id) ?>">
                        <td onclick="event.stopPropagation()"><a class="sr-btn sr-btn-ghost sr-btn-sm" href="view_employee.php?emp_id=<?= urlencode($id) ?>#d365" target="_blank">Open</a></td>
                        <td><div class="sr-cell-title"><?= $e($p['name'] ?? '') ?></div><div class="sr-cell-sub sr-mono"><?= $e($id) ?></div></td>
                        <td><?= $r['emp'] ? '<span class="sr-chip">' . $e($r['emp']['entity']) . '</span>' : '-' ?></td>
                        <td class="pp-num"><?= $money($p['total_gross_salary']) ?></td>
                        <td class="pp-num"><?= $money($p['total_benefits']) ?></td>
                        <td class="pp-num"><?= $money($p['total_deductions']) ?></td>
                        <td class="pp-num"><b><?= $money($p['net_salary']) ?></b></td>
                        <td class="pp-num"><?= count($r['built']['lines']) ?></td>
                        <td class="pp-status">
                            <span class="sr-pill tone-<?= $tone ?>"><span class="sr-dot"></span><?= $label ?></span>
                            <?php if ($r['state'] === 'pushed'): ?><div class="sr-cell-sub"><?= $e($pushed[$id]['journal_batch']) ?></div><?php endif; ?>
                            <?php if ($r['built']['warnings']): ?><div class="sr-cell-sub" title="<?= $e(implode("\n", $r['built']['warnings'])) ?>">⚠ <?= count($r['built']['warnings']) ?> warning(s)</div><?php endif; ?>
                        </td>
                    </tr>
                    <tr class="pp-detail" id="detail-<?= $e($id) ?>" style="display:none">
                        <td></td>
                        <td colspan="8">
                            <?php foreach (array_merge($r['built']['errors'], $r['built']['warnings']) as $msg): ?><div class="pp-msg">⚠ <?= $e($msg) ?></div><?php endforeach; ?>
                            <?php if (!$r['emp']): ?><div class="pp-msg">⚠ Personnel number <?= $e($id) ?> has no employment in D365 - company and dimensions unknown.</div><?php endif; ?>
                            <table class="table pp-lines" style="margin:6px 0 0">
                                <thead><tr><th>D365 account</th><th>Text</th><th class="pp-num">Debit</th><th class="pp-num">Credit</th></tr></thead>
                                <tbody>
                                <?php foreach ($r['built']['lines'] as $l): ?>
                                    <tr>
                                        <td class="sr-mono"><?= $e(D365Payroll::ledgerAccount($l['account'], $r['emp']['dims'] ?? '', $settings)) ?></td>
                                        <td><?= $e($l['text']) ?></td>
                                        <td class="pp-num"><?= $l['debit'] ? $money($l['debit']) : '' ?></td>
                                        <td class="pp-num"><?= $l['credit'] ? $money($l['credit']) : '' ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$preview): ?><tr><td colspan="9"><div class="sr-empty">No payroll rows for this month / company.</div></td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php if ($is_system_admin): ?>
    <div class="sr-card" id="mapping">
        <div class="sr-card-head">
            <div class="sr-card-title">D365 accounts used for payroll</div>
            <div class="sr-card-sub">Filled automatically from finance's own payroll entry in D365 (قيد الرواتب, journal GRN_JRN). Nothing to enter.</div>
        </div>
        <details>
        <summary class="sr-card-body" style="cursor:pointer;font-weight:600">Advanced: change accounts (only if finance asks)</summary>
        <div class="sr-card-body">
            <form method="post">
                <input type="hidden" name="csrf" value="<?= $e($csrf) ?>">
                <input type="hidden" name="action" value="save_settings">
                <input type="hidden" name="month" value="<?= $e($month) ?>">
                <input type="hidden" name="entity" value="<?= $e($entityFilter) ?>">

                <div class="pp-section">Journal</div>
                <div class="pp-map">
                    <div>
                        <label>Company &amp; journal</label>
                        <div class="pp-help" style="margin-top:0">Each employee is booked in their own D365 company. The journal name per company is set in <b>App Settings &gt; D365 Config</b> (use general journals <b>without a fixed offset account</b>).</div>
                    </div>
                    <div>
                        <label>Currency</label>
                        <input type="text" name="s[currency]" class="form-control" value="<?= $e($settings['currency']) ?>">
                    </div>
                    <div>
                        <label>Financial dimensions</label>
                        <select name="s[dimension_mode]" class="form-control">
                            <option value="employment" <?= $settings['dimension_mode'] === 'employment' ? 'selected' : '' ?>>Use worker's D365 employment dimensions</option>
                            <option value="none" <?= $settings['dimension_mode'] === 'none' ? 'selected' : '' ?>>Main account only</option>
                        </select>
                        <div class="pp-help">Employment dimensions look like <code>---5430----</code> (department, worker...).</div>
                    </div>
                </div>

                <div class="pp-section">Salary components (debit)</div>
                <div class="pp-map">
                    <?php foreach (D365Payroll::COMPONENTS as $col => $label): ?>
                        <div>
                            <label><?= $e($label) ?></label>
                            <input type="text" name="s[accounts][<?= $e($col) ?>]" class="form-control" list="dlAccounts" value="<?= $e($settings['accounts'][$col] ?? '') ?>">
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="pp-section">Benefits (debit) and deductions (credit)</div>
                <div class="pp-map">
                    <div>
                        <label>Default benefit account</label>
                        <input type="text" name="s[benefit_default]" class="form-control" list="dlAccounts" value="<?= $e($settings['benefit_default']) ?>">
                        <label style="margin-top:10px">Benefit rules <span class="pp-help">(keyword = account, one per line)</span></label>
                        <textarea name="s[benefit_rules]" class="form-control"><?= $e($settings['benefit_rules']) ?></textarea>
                    </div>
                    <div>
                        <label>Default deduction account</label>
                        <input type="text" name="s[deduction_default]" class="form-control" list="dlAccounts" value="<?= $e($settings['deduction_default']) ?>">
                        <label style="margin-top:10px">Deduction rules <span class="pp-help">(keyword = account, one per line)</span></label>
                        <textarea name="s[deduction_rules]" class="form-control"><?= $e($settings['deduction_rules']) ?></textarea>
                    </div>
                    <div>
                        <label>Net salary payable (credit)</label>
                        <input type="text" name="s[net_payable]" class="form-control" list="dlAccounts" value="<?= $e($settings['net_payable']) ?>">
                        <label style="margin-top:10px">Rounding difference</label>
                        <input type="text" name="s[rounding]" class="form-control" list="dlAccounts" value="<?= $e($settings['rounding']) ?>">
                        <div class="pp-help">Rules match the benefit/deduction name, e.g. <code>gosi = 21050101</code> catches "GOSI". First match wins; others go to the default account.</div>
                    </div>
                </div>

                <div style="margin-top:16px"><button type="submit" class="sr-btn sr-btn-primary">Save mapping</button></div>
            </form>
        </div>
        </details>
    </div>
    <?php endif; ?>

    <datalist id="dlAccounts">
        <?php foreach ($mainAccounts as [$id, $name]): ?><option value="<?= $e($id) ?>"><?= $e($name) ?></option><?php endforeach; ?>
    </datalist>
    <datalist id="dlJournals">
        <?php foreach ($journalNames as $j): if ($entityFilter && strtoupper($j['dataAreaId']) !== $entityFilter) continue; ?>
            <option value="<?= $e($j['Name']) ?>"><?= $e(strtoupper($j['dataAreaId']) . ' · ' . $j['Description'] . ($j['OffsetAccountDisplayValue'] ? ' · offset ' . $j['OffsetAccountDisplayValue'] : '')) ?></option>
        <?php endforeach; ?>
    </datalist>
</div>

<script src="./plugins/sweet-alert/v11/sweetalert2.all.min.js"></script>
<script src="./assets/js/d365_missing_workers.js?v=<?= @filemtime(__DIR__ . '/assets/js/d365_missing_workers.js') ?>"></script>
<script>
(function () {
    var csrf = <?= json_encode($csrf) ?>;
    var month = <?= json_encode($month) ?>;
    var entity = <?= json_encode($entityFilter) ?>;
    var env = <?= json_encode($environment) ?>;

    document.querySelectorAll('tr[data-toggle-detail]').forEach(function (tr) {
        tr.addEventListener('click', function () {
            var d = document.getElementById('detail-' + tr.getAttribute('data-toggle-detail'));
            if (d) d.style.display = d.style.display === 'none' ? '' : 'none';
        });
    });

    function post(data) {
        var fd = new FormData();
        fd.append('csrf', csrf);
        Object.keys(data).forEach(function (k) {
            if (Array.isArray(data[k])) data[k].forEach(function (v) { fd.append(k + '[]', v); });
            else fd.append(k, data[k]);
        });
        return fetch('d365_payroll_push.php', { method: 'POST', body: fd, credentials: 'same-origin' })
            .then(function (r) {
                if (r.redirected) {
                    // The app answered with a login/dashboard page: the session was signed out mid-sync
                    throw new Error('You were signed out of the app during the sync. Reload the page and run Sync again - employees already synced are skipped.');
                }
                return r.json().catch(function () { throw new Error('Server error (HTTP ' + r.status + ')'); });
            });
    }

    document.getElementById('btnRefresh').addEventListener('click', function () {
        post({ action: 'refresh_d365' }).then(function () { location.reload(); });
    });

    // ------------------------------------------------------------ one-click month sync
    var months = <?= json_encode(array_map(function ($m) {
        return ['month' => $m['month_year'], 'total' => (int)$m['total'], 'paid' => (int)$m['paid'], 'paid_net' => (float)$m['paid_net'], 'synced' => (int)$m['synced']];
    }, $monthSummary)) ?>;
    var CHUNK = 10;
    var popupClass = { popup: 'sr-addline-popup sr-page' };

    function esc(s) {
        var d = document.createElement('div');
        d.textContent = s == null ? '' : String(s);
        return d.innerHTML;
    }
    function money(n) {
        return Number(n || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }
    function lastDay(m) {
        var p = m.split('-'), d = new Date(Number(p[0]), Number(p[1]), 0);
        return m + '-' + String(d.getDate()).padStart(2, '0');
    }

    function pickMonth() {
        var html = '<div class="ms-months">' + months.map(function (m, i) {
            var pending = Math.max(m.paid - m.synced, 0);
            var disabled = pending === 0;
            var meta = m.paid === 0 ? 'Not paid yet (' + m.total + ' employees)'
                : m.paid + ' paid · ' + m.synced + ' already synced · <b>' + pending + ' to sync</b> · net ' + money(m.paid_net);
            return '<label class="ms-month' + (disabled ? ' is-disabled' : '') + '">'
                + '<input type="radio" name="msMonth" value="' + esc(m.month) + '"' + (disabled ? ' disabled' : '') + '>'
                + '<span class="ms-title">' + esc(m.month) + '</span><span class="ms-meta">' + meta + '</span></label>';
        }).join('') + '</div>';

        Swal.fire({
            title: 'Sync month to D365',
            html: '<p style="font-size:13px;margin:0 0 12px">Only <b>paid</b> payroll is sent. Employees already synced are skipped.</p>' + html,
            showCancelButton: true,
            confirmButtonText: 'Continue',
            allowOutsideClick: false,
            customClass: popupClass,
            preConfirm: function () {
                var r = document.querySelector('input[name="msMonth"]:checked');
                if (!r) { Swal.showValidationMessage('Select a month'); return false; }
                return r.value;
            }
        }).then(function (res) {
            if (res.isConfirmed) loadPending(res.value);
        });
    }

    function loadPending(m) {
        Swal.fire({ title: 'Preparing ' + m, html: 'Collecting paid employees...', allowOutsideClick: false, customClass: popupClass, didOpen: function () { Swal.showLoading(); } });
        post({ action: 'month_pending', month: m }).then(function (res) {
            if (res.error) { Swal.fire({ icon: 'error', title: 'Cannot sync', text: res.error, customClass: popupClass }); return; }
            var ids = res.emp_ids || [];
            if (!ids.length) { Swal.fire({ icon: 'info', title: 'Nothing to sync', text: 'All paid employees of ' + m + ' are already in D365.', customClass: popupClass }); return; }
            Swal.fire({
                icon: 'question',
                title: 'Sync ' + ids.length + ' employees?',
                html: 'Payroll <b>' + esc(m) + '</b> goes to D365 <b>' + esc(env.toUpperCase()) + '</b> as unposted journals (one per payroll company) dated <b>' + lastDay(m) + '</b>.<br><br>Keep this window open until it finishes.',
                showCancelButton: true,
                confirmButtonText: 'Start sync',
                allowOutsideClick: false,
                customClass: popupClass
            }).then(function (r) { if (r.isConfirmed) runSync(m, ids); });
        }).catch(function (err) {
            Swal.fire({ icon: 'error', title: 'Cannot sync', text: err.message, customClass: popupClass });
        });
    }

    function runSync(m, ids) {
        var done = 0, ok = 0, failed = 0, skipped = 0, stop = false, journals = {}, failures = [], missing = [];
        var started = Date.now();

        Swal.fire({
            title: 'Syncing ' + m + ' to D365',
            html: '<div class="ms-bar"><div id="msBar"></div></div>'
                + '<div class="ms-stats"><span id="msCount">0 / ' + ids.length + '</span><span>✔ <b id="msOk">0</b> &nbsp; ✖ <b id="msFail">0</b></span><span id="msEta">starting...</span></div>'
                + '<div style="font-size:12px;margin-top:6px;color:var(--sr-muted)" id="msJournal">The first step loads D365 employee data and can take a few seconds.</div>'
                + '<div class="ms-log" id="msLog"></div>'
                + '<button type="button" class="sr-btn sr-btn-ghost sr-btn-sm" id="msStop" style="margin-top:12px">Stop after current batch</button>',
            showConfirmButton: false,
            allowOutsideClick: false,
            allowEscapeKey: false,
            customClass: popupClass,
            didOpen: function () {
                document.getElementById('msStop').addEventListener('click', function () {
                    stop = true;
                    this.disabled = true;
                    this.textContent = 'Stopping...';
                });
                next();
            }
        });

        function update() {
            var pct = Math.round(done / ids.length * 100);
            document.getElementById('msBar').style.width = pct + '%';
            document.getElementById('msCount').textContent = done + ' / ' + ids.length + ' (' + pct + '%)';
            document.getElementById('msOk').textContent = ok;
            document.getElementById('msFail').textContent = failed;
            if (done > 0) {
                var secs = Math.round((Date.now() - started) / done * (ids.length - done) / 1000);
                document.getElementById('msEta').textContent = done < ids.length ? '~' + (secs > 90 ? Math.round(secs / 60) + ' min' : secs + ' s') + ' left' : 'done';
            }
            var js = Object.keys(journals).map(function (c) { return c + ' ' + journals[c]; });
            if (js.length) document.getElementById('msJournal').textContent = 'Journals: ' + js.join(', ');
        }
        function logFail(id, msg) {
            failures.push({ id: id, msg: msg });
            var row = document.createElement('div');
            row.textContent = id + ': ' + msg;
            document.getElementById('msLog').appendChild(row);
        }

        function next() {
            if (stop || done >= ids.length) { finish(); return; }
            var part = ids.slice(done, done + CHUNK);
            post({ action: 'sync_chunk', month: m, emp_ids: part }).then(function (res) {
                if (res.error) {
                    // Whole chunk failed (journal, connection...) - stop, nothing in it was sent
                    part.forEach(function (id) { logFail(id, res.error); });
                    failed += part.length;
                    done += part.length;
                    stop = true;
                } else {
                    Object.keys(res.journals || {}).forEach(function (c) { journals[c] = res.journals[c]; });
                    part.forEach(function (id) {
                        var r = res.results[id] || { ok: false, error: 'No result' };
                        if (r.skipped) skipped++;
                        else if (r.ok) ok++;
                        else { failed++; logFail(id, r.error); if (r.missing_worker) missing.push({ id: id, name: r.name || '' }); }
                    });
                    done += part.length;
                }
                update();
                next();
            }).catch(function (err) {
                part.forEach(function (id) { logFail(id, err.message); });
                failed += part.length;
                done += part.length;
                stop = true;
                update();
                next();
            });
        }

        function finish() {
            var notSent = ids.length - done;
            var list = failures.length ? '<div class="ms-log">' + failures.map(function (f) { return '<div>' + esc(f.id) + ': ' + esc(f.msg) + '</div>'; }).join('') + '</div>' : '';
            Swal.fire({
                icon: failed ? 'warning' : 'success',
                title: failed ? 'Finished with errors' : 'Payroll synced',
                html: (missing.length ? '<div style="background:#fff7ed;border:1px solid #fed7aa;border-radius:8px;padding:8px 10px;margin-bottom:10px;font-size:13px;text-align:left"><b>' + missing.length + ' employee' + (missing.length === 1 ? ' is' : 's are') + ' not registered in D365</b> - their payroll was not sent. Use <b>Register in D365</b> below to create them, then sync their payroll.</div>' : '')
                        + '<b>' + ok + '</b> synced' + (skipped ? ', ' + skipped + ' already synced' : '') + (failed ? ', <b>' + failed + '</b> failed' : '') + (notSent ? ', ' + notSent + ' not sent (stopped)' : '')
                    + (Object.keys(journals).length ? '<br>Unposted D365 journals: <b>' + Object.keys(journals).map(function (c) { return esc(c) + ' ' + esc(journals[c]); }).join(', ') + '</b>' : '') + list,
                confirmButtonText: 'Close',
                allowOutsideClick: false,
                showDenyButton: missing.length > 0,
                denyButtonText: 'Register in D365 (' + missing.length + ')',
                denyButtonColor: '#ea580c',
                customClass: popupClass
            }).then(function (res) {
                var reload = function () { location.href = 'd365_payroll_push.php?month=' + encodeURIComponent(m); };
                // Employees not in D365: register them, then sync just their payroll
                if (res.isDenied) {
                    d365MissingWorkers.open({ missing: missing, month: m, post: post, onSync: function (regIds) { runSync(m, regIds); }, onClose: reload });
                } else {
                    reload();
                }
            });
        }
    }

    var btnSync = document.getElementById('btnSyncMonth');
    if (btnSync) btnSync.addEventListener('click', pickMonth);
})();
</script>
</body>
</html>
