<?php
/**
 * HR app employees vs Dynamics 365 workers (system admins, roles allowed in App Settings > Page Access,
 * or the 'access_d365_employee_compare' special access).
 * Shows who is registered in both, who is missing in D365 (payroll sync skips them),
 * who has a D365 worker but no employment, status differences, and D365-only workers.
 * Match key: employees.emp_id = D365 PersonnelNumber.
 */
require_once __DIR__ . '/includes/session_check.php';
require_once __DIR__ . '/includes/D365/D365Payroll.php';
require_once __DIR__ . '/includes/D365/D365Workers.php';

// Microsoft Dynamics 365 switched off in App Settings > D365 Config
if (!d365_enabled($conDB)) {
    http_response_code(403);
    die('Microsoft Dynamics 365 integration is turned off (App Settings > D365 Config)');
}
require_once __DIR__ . '/includes/page_access_helper.php';
$canCompareD365 = page_role_allowed($conDB, 'd365_employee_compare.php', $user_role ?? '', $user_type ?? '', $is_system_admin ?? false)
    || user_has_special_access($conDB, $empid ?? '', 'access_d365_employee_compare', $user_role ?? '', $user_type ?? '', $is_system_admin ?? false);
if (!$canCompareD365) {
    http_response_code(403);
    die('Access Denied: You do not have permission to compare employees with D365');
}
// Registering missing workers / bulk company change write to D365: system admins + 'd365_sync_employee' or 'd365_register_employee' special access
$canRegisterD365 = user_has_special_access($conDB, $empid ?? '', 'd365_sync_employee', $user_role ?? '', $user_type ?? '', $is_system_admin ?? false)
    || user_has_special_access($conDB, $empid ?? '', 'd365_register_employee', $user_role ?? '', $user_type ?? '', $is_system_admin ?? false);
// "Payroll sync" link: system admins + 'd365_sync_payroll' special access only
$canOpenPayrollSync = user_has_special_access($conDB, $empid ?? '', 'd365_sync_payroll', $user_role ?? '', $user_type ?? '', $is_system_admin ?? false);

date_default_timezone_set('Asia/Riyadh');
// D365 (esp. sandbox) can be slow - lift the 25s app-wide limit from includes/db.php for this page
@set_time_limit(180);

if (empty($_SESSION['d365_csrf'])) {
    $_SESSION['d365_csrf'] = bin2hex(random_bytes(16));
}
$csrf = $_SESSION['d365_csrf'];

/** Temp-dir cache file shared with d365_payroll_push.php (same naming: md5(resource url | key)) */
function d365cmp_cache_file(D365Client $client, $key)
{
    return sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'd365_cache_' . md5($client->getResourceUrl() . '|' . $key) . '.json';
}

$client = null;
$error = '';    
try {
    $client = new D365Client();
} catch (Throwable $ex) {
    $error = $ex->getMessage();
}
$environment = $client ? $client->getEnvironment() : 'unknown';

// Register one missing employee in D365 (AJAX, called once per employee by the register popup)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'register') {
    header('Content-Type: application/json; charset=utf-8');
    $regEmp = (string)($_POST['emp_id'] ?? '');
    if (!$canRegisterD365) {
        echo json_encode(['ok' => false, 'error' => 'You do not have permission to register employees in D365']);
        exit;
    }
    if (!hash_equals($csrf, (string)($_POST['csrf'] ?? ''))) {
        echo json_encode(['ok' => false, 'error' => 'Session expired - reload the page']);
        exit;
    }
    if (!$client || !preg_match('/^[A-Za-z0-9\-]{1,20}$/', $regEmp)) {
        echo json_encode(['ok' => false, 'error' => $client ? 'Invalid employee ID' : $error]);
        exit;
    }
    try {
        $result = (new D365Workers($conDB, $client))->register($regEmp, (string)($_POST['company'] ?? ''));
    } catch (Throwable $ex) {
        $result = ['ok' => false, 'error' => $ex->getMessage()];
    }
    if ($result['ok']) {
        // New workers must show up in this page and in the payroll sync right away
        @unlink(d365cmp_cache_file($client, 'compare'));
        @unlink(d365cmp_cache_file($client, 'employments'));
    }
    echo json_encode($result, JSON_UNESCAPED_UNICODE);
    exit;
}

// D365 data is cached in the temp dir (not the session - it is large) for 10 minutes; ?refresh=1 reloads it
if (isset($_GET['refresh'])) {
    unset($_SESSION['d365_compare']);
    if ($client) {
        @unlink(d365cmp_cache_file($client, 'compare'));
    }
    header('Location: d365_employee_compare.php');
    exit;
}

$workers = [];
$employments = [];
$loadedAt = null;
if ($client) {
    unset($_SESSION['d365_compare']); // old session-based cache
    $cacheFile = d365cmp_cache_file($client, 'compare');
    $cache = is_file($cacheFile) ? json_decode((string)file_get_contents($cacheFile), true) : null;
    if ($cache && time() - $cache['t'] < 600) {
        [$workers, $employments, $loadedAt] = [$cache['workers'], $cache['employments'], $cache['t']];
    } else {
        try {
            $res = $client->getAll('Workers', ['$select' => 'PersonnelNumber,Name,WorkerStatus,WorkerType']);
            if ($res['error']) {
                throw new RuntimeException('D365 Workers: ' . $res['error']);
            }
            foreach ($res['data']['value'] as $w) {
                $workers[(string)$w['PersonnelNumber']] = ['name' => $w['Name'], 'status' => $w['WorkerStatus'], 'type' => $w['WorkerType']];
            }
            $employments = (new D365Payroll($conDB, $client))->getEmployments();
            $loadedAt = time();
            @file_put_contents($cacheFile, json_encode(['t' => $loadedAt, 'workers' => $workers, 'employments' => $employments], JSON_UNESCAPED_UNICODE), LOCK_EX);
        } catch (Throwable $ex) {
            $error = $ex->getMessage();
        }
    }
}

// HR app employees
$appEmployees = [];
$res = $conDB->query("SELECT e.emp_id, e.name, e.status, e.joining_date, e.comp_no, c.comp_name, d.dep_nme
    FROM employees e
    LEFT JOIN companies c ON c.comp_id = e.comp_no
    LEFT JOIN department d ON d.id = e.dept");
while ($res && ($r = $res->fetch_assoc())) {
    $appEmployees[(string)$r['emp_id']] = $r;
}

/*
 * State per employee:
 *   ok             active in the app, D365 worker with an active employment
 *   missing        active in the app, no D365 worker            -> payroll sync skips them
 *   no_employment  active in the app, D365 worker but no (active) employment -> payroll sync skips them
 *   status         app inactive but D365 employment still active
 *   only_d365      D365 worker (employed) that is not in the app
 *   inactive       inactive in the app and not employed in D365 (nothing to do)
 */
$rows = [];
if (!$error) {
    foreach ($appEmployees as $id => $a) {
        $w = $workers[$id] ?? null;
        $emp = $employments[$id] ?? null;
        $appActive = (string)$a['status'] === '1';
        $d365Active = $emp && $emp['active'];
        if ($appActive) {
            $state = !$w ? 'missing' : ($d365Active ? 'ok' : 'no_employment');
        } else {
            $state = $d365Active ? 'status' : 'inactive';
        }
        $rows[] = ['id' => $id, 'app' => $a, 'w' => $w, 'emp' => $emp, 'state' => $state];
    }
    foreach ($workers as $id => $w) {
        if (!isset($appEmployees[$id]) && $w['status'] === 'Employed' && $id !== '000001') {
            $rows[] = ['id' => $id, 'app' => null, 'w' => $w, 'emp' => $employments[$id] ?? null, 'state' => 'only_d365'];
        }
    }
    usort($rows, function ($x, $y) {
        return ((int)$x['id'] <=> (int)$y['id']) ?: strcmp($x['id'], $y['id']);
    });
}

$states = [
    'missing'       => ['red', 'Missing in D365', 'Active in the app, no worker in D365 - payroll sync skips them'],
    'no_employment' => ['amber', 'No employment', 'Worker exists in D365 but has no active employment - payroll sync skips them'],
    'status'        => ['amber', 'Status differs', 'Inactive in the app but still employed in D365'],
    'only_d365'     => ['indigo', 'Only in D365', 'Employed worker in D365 that is not in the app'],
    'ok'            => ['green', 'Registered', 'Active in both'],
    'inactive'      => ['slate', 'Inactive', 'Inactive in the app and not employed in D365'],
];
$counts = array_count_values(array_column($rows, 'state'));

// Register popup: suggested D365 company per app company + the D365 companies to choose from
$companySuggest = [];
$legalEntities = [];
if (!$error && $client) {
    $companySuggest = (new D365Workers($conDB, $client))->suggestCompanies($employments);
    $legalEntities = array_values(array_unique(array_column($employments, 'entity')));
    // companies removed in App Settings > D365 Config > Account Templates (closed) are no target for register/transfer
    require_once __DIR__ . '/includes/cost_centers.php';
    $legalEntities = array_values(array_diff($legalEntities, d365_hidden_companies()));
    sort($legalEntities);
}
$canWrite = $client && $client->canWrite();

function cmp_date($v)
{
    if (!$v || strpos($v, '1900-01-01') === 0) {
        return '';
    }
    if (strpos($v, '2154-12-31') === 0) {
        return 'Open';
    }
    $dt = new DateTime($v, new DateTimeZone('UTC'));
    $dt->setTimezone(new DateTimeZone('Asia/Riyadh'));
    return $dt->format('Y-m-d');
}

$e = function ($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); };

// Filter dropdown values (only what occurs in the list)
$filterOpts = ['comp' => [], 'dept' => [], 'd365' => []];
foreach ($rows as $r) {
    if ($r['app']) {
        $filterOpts['comp'][(string)($r['app']['comp_name'] ?? '')] = true;
        $filterOpts['dept'][(string)($r['app']['dep_nme'] ?? '')] = true;
    }
    $filterOpts['d365'][$r['emp'] ? (string)$r['emp']['entity'] : ''] = true;
}
foreach ($filterOpts as &$list) {
    $list = array_keys($list);
    sort($list, SORT_NATURAL | SORT_FLAG_CASE);
}
unset($list);
$filterSelect = function ($id, $all, array $values, $blankLabel) use ($e) {
    $html = '<select id="' . $id . '" class="form-control form-control-sm cmp-filter"><option value="*">' . $e($all) . '</option>';
    foreach ($values as $v) {
        $html .= '<option value="' . $e($v) . '">' . $e($v === '' ? $blankLabel : $v) . '</option>';
    }
    return $html . '</select>';
};
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>D365 Employee Check</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
    <link href="./plugins/sweet-alert/v11/sweetalert2.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/smart_request.css?v=<?= @filemtime(__DIR__ . '/assets/css/smart_request.css') ?>" rel="stylesheet" type="text/css" />
    <style>
        .rg-list { max-height: 50vh; overflow-y: auto; text-align: start; border: 1px solid var(--sr-border); border-radius: 10px; }
        .rg-list table { width: 100%; font-size: 13px; }
        .rg-list th, .rg-list td { padding: 6px 10px; border-bottom: 1px solid var(--sr-border); }
        .rg-list th { position: sticky; top: 0; background: var(--sr-surface-2, #f8fafc); font-size: 11px; text-transform: uppercase; color: var(--sr-muted); }
        .rg-list select { padding: 3px 6px; border-radius: 6px; border: 1px solid var(--sr-border); }
        .rg-bar { height: 12px; border-radius: 6px; background: var(--sr-surface-3, #e5e7eb); overflow: hidden; margin: 14px 0 8px; }
        .rg-bar > div { height: 100%; width: 0; background: var(--sr-accent, #6366f1); transition: width .25s; }
        .rg-log { margin-top: 12px; max-height: 180px; overflow-y: auto; text-align: start; font-size: 12px; }
        .rg-log div { padding: 4px 0; border-bottom: 1px dashed var(--sr-border); color: var(--tone-red-fg, #b91c1c); }
        .cmp-tiles { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 10px; margin-bottom: 16px; }
        .cmp-tile { padding: 12px 14px; border: 1px solid var(--sr-border); border-radius: 12px; background: var(--sr-surface); cursor: pointer; text-align: start; }
        .cmp-tile:hover, .cmp-tile.active { border-color: var(--sr-accent); box-shadow: 0 0 0 3px var(--sr-accent-soft, rgba(99,102,241,.15)); }
        .cmp-tile .n { font-size: 24px; font-weight: 700; color: var(--sr-text); line-height: 1.1; }
        .cmp-tile .l { font-size: 12px; color: var(--sr-muted); margin-top: 4px; }
        .cmp-toolbar { display: flex; flex-wrap: wrap; gap: 10px; align-items: center; }
        .cmp-toolbar input { max-width: 280px; }
        .cmp-toolbar select { width: auto; max-width: 220px; }
        .cmp-muted { color: var(--sr-muted); }
        .cmp-hint { font-size: 12px; color: var(--sr-muted); }
    </style>
</head>
<body class="sr-standalone">
<div class="sr-page sr-standalone-wrap">
    <div class="sr-standalone-brand">
        <a href="dashboard.php"><img src="<?= $e(get_setting($conDB, 'logo')) ?>" alt=""></a>
    </div>
    <div class="sr-head">
        <div>
            <h1>D365 Employee Check</h1>
            <p>HR app employees vs Dynamics 365 workers, matched by Employee ID = Personnel number. Employees marked <b>Missing in D365</b> or <b>No employment</b> are skipped by the payroll sync.</p>
        </div>
        <div class="sr-head-actions">
            <span class="sr-pill tone-<?= $environment === 'sandbox' ? 'sky' : 'red' ?>" title="<?= $e($client ? $client->getResourceUrl() : '') ?>"><span class="sr-dot"></span><?= $e(ucfirst($environment)) ?></span>
            <?php if (!$error && $canRegisterD365): ?>
                <button type="button" class="sr-btn sr-btn-primary" id="btnRegisterAll" <?= $canWrite && !empty($counts['missing']) ? '' : 'disabled' ?>
                    title="<?= $canWrite ? '' : 'Writes are off (App Settings > D365 Config)' ?>">Register missing in D365 (<?= (int)($counts['missing'] ?? 0) ?>)</button>
                <button type="button" class="sr-btn sr-btn-success" id="btnBulkTransfer" <?= $canWrite ? '' : 'disabled' ?>
                    title="<?= $canWrite ? 'Move many employees to other D365 companies from an Excel file' : 'Writes are off (App Settings > D365 Config)' ?>"><i class="mdi mdi-swap-horizontal"></i> Bulk change company (Excel)</button>
            <?php endif; ?>
            <a class="sr-btn sr-btn-ghost" href="d365_employee_compare.php?refresh=1">Reload D365 data</a>
            <?php if ($canOpenPayrollSync): ?><a class="sr-btn sr-btn-ghost" href="d365_payroll_push.php">Payroll sync</a><?php endif; ?>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="sr-notice tone-red"><?= $e($error) ?></div>
    <?php else: ?>
        <div class="cmp-tiles">
            <div class="cmp-tile active" data-filter="">
                <div class="n"><?= count($rows) ?></div><div class="l">All</div>
            </div>
            <?php foreach ($states as $key => [$tone, $label, $hint]): ?>
                <div class="cmp-tile" data-filter="<?= $e($key) ?>" title="<?= $e($hint) ?>">
                    <div class="n"><?= (int)($counts[$key] ?? 0) ?></div>
                    <div class="l"><span class="sr-pill tone-<?= $tone ?>"><span class="sr-dot"></span><?= $e($label) ?></span></div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="sr-card">
            <div class="sr-card-head">
                <div class="sr-card-title">Employees</div>
                <div class="sr-card-sub">D365 data loaded <?= $e(date('Y-m-d H:i', $loadedAt)) ?> (cached 10 min) · <?= count($workers) ?> D365 workers · <?= count($appEmployees) ?> app employees</div>
            </div>
            <div class="sr-card-body">
                <div class="cmp-toolbar">
                    <input type="search" id="cmpSearch" class="form-control" placeholder="Search ID, name, company...">
                    <select id="fAppStatus" class="form-control form-control-sm cmp-filter">
                        <option value="*">App status: all</option><option value="1">Active</option><option value="0">Inactive</option><option value="">Not in app</option>
                    </select>
                    <?= $filterSelect('fAppComp', 'App company: all', $filterOpts['comp'], '(no company)') ?>
                    <?= $filterSelect('fDept', 'Department: all', $filterOpts['dept'], '(no department)') ?>
                    <?= $filterSelect('fD365', 'D365 company: all', $filterOpts['d365'], '(no employment)') ?>
                    <select id="fEmployment" class="form-control form-control-sm cmp-filter">
                        <option value="*">D365 employment: all</option><option value="active">Active</option><option value="ended">Ended</option><option value="">None</option>
                    </select>
                    <button type="button" class="sr-btn sr-btn-ghost sr-btn-sm" id="cmpClear">Clear filters</button>
                    <button type="button" class="sr-btn sr-btn-ghost sr-btn-sm" id="cmpExport">Export CSV</button>
                    <span class="cmp-hint" id="cmpShown"></span>
                </div>
            </div>
            <div class="sr-table-wrap">
                <table class="sr-table" id="cmpTable">
                    <thead>
                    <tr>
                        <th>Emp ID</th><th>Name (app)</th><th>Name (D365)</th><th>App status</th><th>App company</th>
                        <th>Joined (app)</th><th>D365 company</th><th>D365 employment</th><th>Result</th><th></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($rows as $r):
                        [$tone, $label] = $states[$r['state']];
                        $a = $r['app'];
                        $emp = $r['emp']; ?>
                        <tr data-state="<?= $e($r['state']) ?>" data-emp="<?= $e($r['id']) ?>"
                            data-app-status="<?= $a ? $e((string)$a['status'] === '1' ? '1' : '0') : '' ?>" data-app-comp="<?= $a ? $e($a['comp_name']) : '' ?>"
                            data-dept="<?= $a ? $e($a['dep_nme']) : '' ?>" data-d365="<?= $emp ? $e($emp['entity']) : '' ?>"
                            data-employment="<?= $emp ? ($emp['active'] ? 'active' : 'ended') : '' ?>"<?php if ($r['state'] === 'missing'): ?>
                            data-name="<?= $e($a['name']) ?>" data-comp-name="<?= $e($a['comp_name']) ?>" data-company="<?= $e($companySuggest[$a['comp_no']] ?? '') ?>"<?php endif; ?>>
                            <td class="sr-mono"><?= $e($r['id']) ?></td>
                            <td><?= $a ? $e($a['name']) : '<span class="cmp-muted">-</span>' ?></td>
                            <td><?= $r['w'] ? $e($r['w']['name']) : '<span class="cmp-muted">-</span>' ?></td>
                            <td><?= $a ? ((string)$a['status'] === '1' ? 'Active' : 'Inactive') : '<span class="cmp-muted">-</span>' ?></td>
                            <td><?= $a ? $e($a['comp_name']) : '<span class="cmp-muted">-</span>' ?></td>
                            <td><?= $a ? $e($a['joining_date']) : '' ?></td>
                            <td><?= $emp ? '<span class="sr-chip">' . $e($emp['entity']) . '</span>' : '<span class="cmp-muted">-</span>' ?></td>
                            <td><?= $emp ? $e(cmp_date($emp['start'])) . ($emp['active'] ? '' : ' (ended)') : '<span class="cmp-muted">-</span>' ?></td>
                            <td><span class="sr-pill tone-<?= $tone ?>"><span class="sr-dot"></span><?= $e($label) ?></span></td>
                            <td style="white-space:nowrap">
                                <?php if ($r['state'] === 'missing' && $canWrite && $canRegisterD365): ?><button type="button" class="sr-btn sr-btn-primary sr-btn-sm btn-register">Register</button><?php endif; ?>
                                <?php if ($a): ?><a class="sr-btn sr-btn-ghost sr-btn-sm" href="view_employee.php?emp_id=<?= urlencode($r['id']) ?>#d365" target="_blank">Open</a><?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>

<script src="./plugins/sweet-alert/v11/sweetalert2.all.min.js"></script>
<script>
(function () {
    var table = document.getElementById('cmpTable');
    if (!table) return;
    var rows = Array.prototype.slice.call(table.tBodies[0].rows);
    var search = document.getElementById('cmpSearch');
    var shown = document.getElementById('cmpShown');
    var filter = '';
    // Dropdown filters: element + row attribute it matches ('*' = all)
    var selects = [['fAppStatus', 'data-app-status'], ['fAppComp', 'data-app-comp'], ['fDept', 'data-dept'], ['fD365', 'data-d365'], ['fEmployment', 'data-employment']]
        .map(function (p) { return { el: document.getElementById(p[0]), attr: p[1] }; })
        .filter(function (s) { return s.el; });
    selects.forEach(function (s) { s.el.addEventListener('change', function () { apply(); }); });
    document.getElementById('cmpClear').addEventListener('click', function () {
        selects.forEach(function (s) { s.el.value = '*'; });
        search.value = '';
        apply();
    });

    function apply() {
        var q = search.value.trim().toLowerCase(), n = 0;
        rows.forEach(function (tr) {
            var ok = (!filter || tr.getAttribute('data-state') === filter) && (!q || tr.textContent.toLowerCase().indexOf(q) !== -1)
                && selects.every(function (s) { return s.el.value === '*' || tr.getAttribute(s.attr) === s.el.value; });
            tr.style.display = ok ? '' : 'none';
            if (ok) n++;
        });
        shown.textContent = n + ' shown';
    }

    document.querySelectorAll('.cmp-tile').forEach(function (t) {
        t.addEventListener('click', function () {
            document.querySelectorAll('.cmp-tile').forEach(function (x) { x.classList.remove('active'); });
            t.classList.add('active');
            filter = t.getAttribute('data-filter');
            apply();
        });
    });
    search.addEventListener('input', apply);

    document.getElementById('cmpExport').addEventListener('click', function () {
        var head = Array.prototype.map.call(table.tHead.rows[0].cells, function (c) { return c.textContent.trim(); }).slice(0, 9);
        var lines = [head];
        rows.forEach(function (tr) {
            if (tr.style.display === 'none') return;
            lines.push(Array.prototype.map.call(tr.cells, function (c) { return c.textContent.trim(); }).slice(0, 9));
        });
        var csv = '﻿' + lines.map(function (l) {
            return l.map(function (v) { return '"' + v.replace(/"/g, '""') + '"'; }).join(',');
        }).join('\r\n');
        var a = document.createElement('a');
        a.href = URL.createObjectURL(new Blob([csv], { type: 'text/csv;charset=utf-8' }));
        a.download = 'd365_employee_check_' + new Date().toISOString().slice(0, 10) + '.csv';
        a.click();
    });

    apply();

    // ------------------------------------------------------------ register missing employees in D365
    var csrf = <?= json_encode($csrf) ?>;
    var env = <?= json_encode($environment) ?>;
    var entities = <?= json_encode($legalEntities) ?>;
    var entityNames = <?= json_encode((object)array_map(function ($c) { return $c['name']; }, function_exists('payroll_company_list') ? payroll_company_list() : []), JSON_UNESCAPED_UNICODE) ?>;
    function entityLabel(code) { return code + (entityNames[code] ? ' - ' + entityNames[code] : ''); }
    var popupClass = { popup: 'sr-addline-popup sr-page' };

    function esc(s) {
        var d = document.createElement('div');
        d.textContent = s == null ? '' : String(s);
        return d.innerHTML;
    }

    function missingRows(only) {
        return rows.filter(function (tr) {
            return tr.getAttribute('data-state') === 'missing' && (!only || tr === only);
        }).map(function (tr) {
            return { id: tr.getAttribute('data-emp'), name: tr.getAttribute('data-name'), compName: tr.getAttribute('data-comp-name'), company: tr.getAttribute('data-company') };
        });
    }

    function openRegister(list) {
        if (!list.length) return;
        var html = '<p style="font-size:13px;margin:0 0 10px">Creates the worker and the employment in D365 <b>' + esc(env.toUpperCase())
            + '</b> from the HR app (name, joining date, birth date, gender, email). Check the D365 company of each employee.</p>'
            + '<div class="rg-list"><table><thead><tr><th>ID</th><th>Name</th><th>App company</th><th>D365 company</th></tr></thead><tbody>'
            + list.map(function (r, i) {
                return '<tr><td>' + esc(r.id) + '</td><td>' + esc(r.name) + '</td><td>' + esc(r.compName) + '</td><td><select data-i="' + i + '">'
                    + '<option value="">- choose -</option>'
                    + entities.map(function (en) { return '<option value="' + esc(en) + '"' + (en === r.company ? ' selected' : '') + '>' + esc(entityLabel(en)) + '</option>'; }).join('')
                    + '</select></td></tr>';
            }).join('') + '</tbody></table></div>';

        Swal.fire({
            title: 'Register ' + list.length + ' employee' + (list.length > 1 ? 's' : '') + ' in D365',
            html: html,
            width: 760,
            showCancelButton: true,
            confirmButtonText: 'Register',
            allowOutsideClick: false,
            customClass: popupClass,
            preConfirm: function () {
                var missingCompany = false;
                document.querySelectorAll('.rg-list select').forEach(function (s) {
                    list[Number(s.getAttribute('data-i'))].company = s.value;
                    if (!s.value) missingCompany = true;
                });
                if (missingCompany) { Swal.showValidationMessage('Choose the D365 company for every employee'); return false; }
                return true;
            }
        }).then(function (res) { if (res.isConfirmed) runRegister(list); });
    }

    function runRegister(list) {
        var done = 0, ok = 0, failed = 0, stop = false, failures = [];
        Swal.fire({
            title: 'Registering in D365',
            html: '<div class="rg-bar"><div id="rgBar"></div></div>'
                + '<div style="display:flex;justify-content:space-between;font-size:13px"><span id="rgCount">0 / ' + list.length + '</span><span>✔ <b id="rgOk">0</b> &nbsp; ✖ <b id="rgFail">0</b></span></div>'
                + '<div style="font-size:12px;margin-top:6px;color:var(--sr-muted)" id="rgNow"></div>'
                + '<div class="rg-log" id="rgLog"></div>'
                + '<button type="button" class="sr-btn sr-btn-ghost sr-btn-sm" id="rgStop" style="margin-top:12px">Stop</button>',
            showConfirmButton: false,
            allowOutsideClick: false,
            allowEscapeKey: false,
            customClass: popupClass,
            didOpen: function () {
                document.getElementById('rgStop').addEventListener('click', function () { stop = true; this.disabled = true; this.textContent = 'Stopping...'; });
                next();
            }
        });

        function next() {
            if (stop || done >= list.length) { finish(); return; }
            var r = list[done];
            document.getElementById('rgNow').textContent = r.id + ' ' + r.name + ' → ' + r.company;
            var fd = new FormData();
            fd.append('action', 'register');
            fd.append('csrf', csrf);
            fd.append('emp_id', r.id);
            fd.append('company', r.company);
            fetch('d365_employee_compare.php', { method: 'POST', body: fd, credentials: 'same-origin' })
                .then(function (resp) { return resp.json().catch(function () { throw new Error('Server error (HTTP ' + resp.status + ')'); }); })
                .then(function (res) {
                    if (res.ok) ok++;
                    else { failed++; failures.push(r.id + ': ' + (res.error || 'failed')); addLog(r.id + ': ' + (res.error || 'failed')); }
                })
                .catch(function (err) { failed++; failures.push(r.id + ': ' + err.message); addLog(r.id + ': ' + err.message); })
                .then(function () {
                    done++;
                    document.getElementById('rgBar').style.width = Math.round(done / list.length * 100) + '%';
                    document.getElementById('rgCount').textContent = done + ' / ' + list.length;
                    document.getElementById('rgOk').textContent = ok;
                    document.getElementById('rgFail').textContent = failed;
                    next();
                });
        }
        function addLog(text) {
            var d = document.createElement('div');
            d.textContent = text;
            document.getElementById('rgLog').appendChild(d);
        }
        function finish() {
            Swal.fire({
                icon: failed ? 'warning' : 'success',
                title: failed ? 'Finished with errors' : 'Registered in D365',
                html: '<b>' + ok + '</b> registered' + (failed ? ', <b>' + failed + '</b> failed' : '') + (done < list.length ? ', ' + (list.length - done) + ' not sent (stopped)' : '')
                    + (failures.length ? '<div class="rg-log">' + failures.map(function (f) { return '<div>' + esc(f) + '</div>'; }).join('') + '</div>' : ''),
                confirmButtonText: 'Close',
                allowOutsideClick: false,
                customClass: popupClass
            }).then(function () { location.href = 'd365_employee_compare.php?refresh=1'; });
        }
    }

    // ------------------------------------------------------------ bulk change company (Excel)
    // 1. Template = the rows currently shown with an active D365 employment (Emp ID, Name, Current company, New company, Transfer date)
    // 2. Upload the filled file -> preview with checks -> transfer one by one (includes/ajaxFile/d365_employee.php action=transfer)
    var btnBulk = document.getElementById('btnBulkTransfer');
    var XLSX_URL = 'https://cdn.sheetjs.com/xlsx-0.19.3/package/dist/xlsx.full.min.js';

    function withXlsx(cb) {
        if (window.XLSX) { cb(); return; }
        var s = document.createElement('script');
        s.src = XLSX_URL;
        s.onload = cb;
        s.onerror = function () { Swal.fire({ icon: 'error', title: 'Could not load the Excel library', customClass: popupClass }); };
        document.head.appendChild(s);
    }

    function rowInfo(id) {
        var tr = rows.find(function (r) { return r.getAttribute('data-emp') === id; });
        if (!tr) return null;
        return {
            id: id,
            name: (tr.cells[1].textContent.trim() !== '-' ? tr.cells[1] : tr.cells[2]).textContent.trim(),
            company: tr.getAttribute('data-d365'),
            employment: tr.getAttribute('data-employment')
        };
    }

    function downloadTemplate() {
        withXlsx(function () {
            var data = [['Emp ID', 'Name', 'Current company', 'New company', 'Transfer date (YYYY-MM-DD)']];
            rows.forEach(function (tr) {
                if (tr.style.display === 'none' || tr.getAttribute('data-employment') !== 'active') return;
                var i = rowInfo(tr.getAttribute('data-emp'));
                data.push([i.id, i.name, i.company, '', '']);
            });
            var wb = XLSX.utils.book_new();
            var ws = XLSX.utils.aoa_to_sheet(data);
            ws['!cols'] = [{ wch: 10 }, { wch: 40 }, { wch: 16 }, { wch: 14 }, { wch: 26 }];
            XLSX.utils.book_append_sheet(wb, ws, 'Transfer');
            XLSX.utils.book_append_sheet(wb, XLSX.utils.aoa_to_sheet([['Valid company code', 'Name']].concat(entities.map(function (c) { return [c, entityNames[c] || '']; }))), 'Companies');
            XLSX.writeFile(wb, 'd365_change_company_' + new Date().toISOString().slice(0, 10) + '.xlsx');
        });
    }

    /** Excel cell -> 'YYYY-MM-DD' ('' when empty / unreadable). Accepts Date, Excel serial, 2026-10-07, 7-10-2026, 7/10/2026 */
    function toIsoDate(v) {
        if (v === null || v === undefined || v === '') return '';
        if (v instanceof Date && !isNaN(v)) {
            return v.getFullYear() + '-' + String(v.getMonth() + 1).padStart(2, '0') + '-' + String(v.getDate()).padStart(2, '0');
        }
        if (typeof v === 'number') {
            var d = new Date(Math.round((v - 25569) * 86400000));
            return d.getUTCFullYear() + '-' + String(d.getUTCMonth() + 1).padStart(2, '0') + '-' + String(d.getUTCDate()).padStart(2, '0');
        }
        var s = String(v).trim(), m;
        if ((m = s.match(/^(\d{4})[-\/.](\d{1,2})[-\/.](\d{1,2})$/))) return m[1] + '-' + m[2].padStart(2, '0') + '-' + m[3].padStart(2, '0');
        if ((m = s.match(/^(\d{1,2})[-\/.](\d{1,2})[-\/.](\d{4})$/))) return m[3] + '-' + m[2].padStart(2, '0') + '-' + m[1].padStart(2, '0'); // day first
        return null;
    }

    function parseFile(file, defaultDate) {
        return new Promise(function (resolve, reject) {
            var reader = new FileReader();
            reader.onerror = function () { reject(new Error('Could not read the file')); };
            reader.onload = function () {
                try {
                    var wb = XLSX.read(new Uint8Array(reader.result), { type: 'array', cellDates: true });
                    var sheet = wb.Sheets[wb.SheetNames[0]];
                    var aoa = XLSX.utils.sheet_to_json(sheet, { header: 1, raw: true, defval: '' });
                    if (!aoa.length) throw new Error('The file is empty');
                    var head = aoa[0].map(function (h) { return String(h).toLowerCase().trim(); });
                    var col = function (re) { return head.findIndex(function (h) { return re.test(h); }); };
                    var cId = col(/emp|personnel|^id$/), cCo = col(/new\s*company|^company$|new\s*legal/), cDate = col(/date/);
                    if (cId < 0 || cCo < 0) throw new Error('Columns "Emp ID" and "New company" are required in the first sheet');
                    var seen = {};
                    var list = [];
                    aoa.slice(1).forEach(function (r, i) {
                        var id = String(r[cId] === undefined ? '' : r[cId]).trim();
                        var to = String(r[cCo] === undefined ? '' : r[cCo]).trim().toUpperCase();
                        if (!id && !to) return;
                        var date = cDate >= 0 ? toIsoDate(r[cDate]) : '';
                        var item = { line: i + 2, id: id, to: to, date: date || defaultDate, error: null };
                        var info = rowInfo(id);
                        item.name = info ? info.name : '';
                        item.from = info ? info.company : '';
                        if (!id) item.error = 'No Emp ID';
                        else if (seen[id]) item.error = 'Duplicate (line ' + seen[id] + ')';
                        else if (!info) item.error = 'Not in this list';
                        else if (info.employment !== 'active') item.error = 'No active D365 employment';
                        else if (!to) item.error = 'No new company';
                        else if (entities.indexOf(to) === -1) item.error = 'Unknown company ' + to;
                        else if (to === info.company) item.error = 'Already in ' + to;
                        else if (date === null || !item.date) item.error = 'Invalid transfer date';
                        seen[id] = seen[id] || item.line;
                        list.push(item);
                    });
                    if (!list.length) throw new Error('No employees found in the file');
                    resolve(list);
                } catch (e) { reject(e); }
            };
            reader.readAsArrayBuffer(file);
        });
    }

    function openBulkTransfer() {
        var tomorrow = new Date(Date.now() + 86400000).toISOString().slice(0, 10);
        Swal.fire({
            title: 'Bulk change company',
            width: '640px',
            customClass: popupClass,
            allowOutsideClick: false,
            showCancelButton: true,
            confirmButtonText: 'Check file',
            html: '<div class="sr-form" style="text-align:start">'
                + '<div class="sr-notice tone-sky" style="margin-bottom:12px"><i class="mdi mdi-information-outline"></i><div>'
                + '<b>1.</b> Filter the list (e.g. D365 company = MTL) and download the template.<br>'
                + '<b>2.</b> Fill <b>New company</b> (e.g. MMT / MFF) and, if needed, <b>Transfer date</b> per employee.<br>'
                + '<b>3.</b> Upload the file - you see a check before anything is sent to D365 <b>' + esc(env.toUpperCase()) + '</b>.</div></div>'
                + '<button type="button" class="sr-btn sr-btn-ghost sr-btn-sm" id="btDownload" style="margin-bottom:12px"><i class="mdi mdi-download"></i> Download template (' + rows.filter(function (tr) { return tr.style.display !== 'none' && tr.getAttribute('data-employment') === 'active'; }).length + ' shown employees)</button>'
                + '<div class="sr-fgrid">'
                + '<div class="sr-fcol c-6"><label>Excel file <span class="text-danger">*</span></label><input type="file" id="btFile" class="form-control" accept=".xlsx,.xls,.csv"></div>'
                + '<div class="sr-fcol c-6"><label>Default transfer date</label><input type="date" id="btDate" class="form-control" value="' + tomorrow + '"><span class="sr-fhint">First day in the new company - used when the row has no date. Today = active at once, later = Pending in D365 until then.</span></div>'
                + '</div></div>',
            didOpen: function () { document.getElementById('btDownload').addEventListener('click', downloadTemplate); },
            preConfirm: function () {
                var file = document.getElementById('btFile').files[0];
                var date = document.getElementById('btDate').value;
                if (!file) { Swal.showValidationMessage('Choose the Excel file'); return false; }
                if (!date) { Swal.showValidationMessage('Choose the default transfer date'); return false; }
                return new Promise(function (resolve) { withXlsx(resolve); })
                    .then(function () { return parseFile(file, date); })
                    .catch(function (e) { Swal.showValidationMessage(e.message); return false; });
            }
        }).then(function (r) { if (r.isConfirmed && r.value) previewTransfer(r.value); });
    }

    function previewTransfer(list) {
        var ok = list.filter(function (i) { return !i.error; });
        var byTarget = {};
        ok.forEach(function (i) { byTarget[i.to] = (byTarget[i.to] || 0) + 1; });
        Swal.fire({
            title: 'Check before sending',
            width: '820px',
            customClass: popupClass,
            allowOutsideClick: false,
            showCancelButton: true,
            confirmButtonText: 'Change company of ' + ok.length + ' employee' + (ok.length === 1 ? '' : 's'),
            html: '<div style="text-align:start">'
                + '<div class="sr-notice ' + (ok.length ? 'tone-amber' : 'tone-red') + '" style="margin-bottom:10px"><i class="mdi mdi-alert-outline"></i><div>'
                + '<b>' + ok.length + '</b> ready' + (Object.keys(byTarget).length ? ' (' + Object.keys(byTarget).map(function (k) { return esc(k) + ': ' + byTarget[k]; }).join(', ') + ')' : '')
                + ' · <b>' + (list.length - ok.length) + '</b> skipped. Each employee gets a new employment in the new company and the old one ends the day before - in D365 <b>' + esc(env.toUpperCase()) + '</b>.</div></div>'
                + '<div class="rg-list"><table><thead><tr><th>Line</th><th>Emp ID</th><th>Name</th><th>From</th><th>To</th><th>Date</th><th>Check</th></tr></thead><tbody>'
                + list.map(function (i) {
                    return '<tr><td>' + i.line + '</td><td>' + esc(i.id) + '</td><td>' + esc(i.name) + '</td><td>' + esc(i.from) + '</td><td><b>' + esc(i.to) + '</b></td><td>' + esc(i.date || '') + '</td><td>'
                        + (i.error ? '<span class="sr-pill tone-red"><span class="sr-dot"></span>' + esc(i.error) + '</span>' : '<span class="sr-pill tone-green"><span class="sr-dot"></span>OK</span>') + '</td></tr>';
                }).join('') + '</tbody></table></div></div>',
            didOpen: function () { if (!ok.length) Swal.getConfirmButton().disabled = true; }
        }).then(function (r) { if (r.isConfirmed && ok.length) runTransfer(ok); });
    }

    function runTransfer(list) {
        var done = 0, okCount = 0, failures = [], warnings = [], stop = false, started = Date.now();
        Swal.fire({
            title: 'Changing company...',
            width: '600px',
            customClass: popupClass,
            allowOutsideClick: false,
            showConfirmButton: false,
            html: '<div style="text-align:start"><div id="btNow" style="font-size:13px">Starting...</div>'
                + '<div class="rg-bar"><div id="btBar"></div></div>'
                + '<div style="display:flex;justify-content:space-between;font-size:12px"><span id="btCount">0 / ' + list.length + '</span><span id="btEta"></span></div>'
                + '<div style="margin-top:8px;font-size:13px"><span class="text-success">OK <b id="btOk">0</b></span> · <span class="text-danger">Failed <b id="btFail">0</b></span></div>'
                + '<div class="rg-log" id="btLog"></div>'
                + '<button type="button" class="sr-btn sr-btn-ghost sr-btn-sm" id="btStop" style="margin-top:10px">Stop after current</button></div>',
            didOpen: function () {
                document.getElementById('btStop').addEventListener('click', function () { stop = true; this.disabled = true; this.textContent = 'Stopping...'; });
                next();
            }
        });

        function next() {
            if (stop || done >= list.length) { finish(); return; }
            var it = list[done];
            document.getElementById('btNow').innerHTML = 'Moving <b>' + esc(it.id) + '</b> ' + esc(it.name) + ': ' + esc(it.from) + ' &rarr; <b>' + esc(it.to) + '</b> (' + esc(it.date) + ')';
            var fd = new FormData();
            fd.append('action', 'transfer');
            fd.append('csrf', csrf);
            fd.append('emp_id', it.id);
            fd.append('company', it.to);
            fd.append('date', it.date);
            fetch('./includes/ajaxFile/d365_employee.php', { method: 'POST', body: fd, credentials: 'same-origin' })
                .then(function (resp) {
                    if (resp.redirected) throw new Error('Signed out - log in again and re-run (finished employees are skipped: "Already employed")');
                    return resp.json().catch(function () { throw new Error('Server error (HTTP ' + resp.status + ')'); });
                })
                .then(function (res) {
                    if (res.ok) {
                        okCount++;
                        if (res.warning) { warnings.push(it.id + ': ' + res.warning); addLog(it.id + ' (warning): ' + res.warning); }
                    } else {
                        failures.push(it.id + ': ' + (res.error || 'Failed'));
                        addLog(it.id + ': ' + (res.error || 'Failed'));
                    }
                })
                .catch(function (err) {
                    failures.push(it.id + ': ' + err.message);
                    addLog(it.id + ': ' + err.message);
                    if (/Signed out/.test(err.message)) stop = true;
                })
                .then(function () {
                    done++;
                    document.getElementById('btBar').style.width = Math.round(done / list.length * 100) + '%';
                    document.getElementById('btCount').textContent = done + ' / ' + list.length;
                    document.getElementById('btOk').textContent = okCount;
                    document.getElementById('btFail').textContent = failures.length;
                    var per = (Date.now() - started) / done;
                    var left = Math.round(per * (list.length - done) / 1000);
                    document.getElementById('btEta').textContent = left > 0 ? '~' + (left >= 60 ? Math.floor(left / 60) + 'm ' : '') + (left % 60) + 's left' : '';
                    next();
                });
        }

        function addLog(text) {
            var log = document.getElementById('btLog');
            if (!log) return;
            var div = document.createElement('div');
            div.textContent = text;
            log.appendChild(div);
        }

        function finish() {
            var lines = failures.concat(warnings);
            Swal.fire({
                icon: failures.length ? 'warning' : 'success',
                title: okCount + ' of ' + list.length + ' moved' + (stop && done < list.length ? ' (stopped)' : ''),
                width: '600px',
                customClass: popupClass,
                allowOutsideClick: false,
                html: (failures.length ? failures.length + ' failed' : 'All done') + (warnings.length ? ', ' + warnings.length + ' with warnings' : '')
                    + (lines.length ? '<div class="rg-log">' + lines.map(function (f) { return '<div>' + esc(f) + '</div>'; }).join('') + '</div>' : ''),
                confirmButtonText: 'Reload list'
            }).then(function () { location.href = 'd365_employee_compare.php?refresh=1'; });
        }
    }

    if (btnBulk) btnBulk.addEventListener('click', openBulkTransfer);

    var btnAll = document.getElementById('btnRegisterAll');
    if (btnAll) btnAll.addEventListener('click', function () { openRegister(missingRows()); });
    document.querySelectorAll('.btn-register').forEach(function (b) {
        b.addEventListener('click', function () { openRegister(missingRows(b.closest('tr'))); });
    });
})();
</script>
</body>
</html>
