<?php
// App Settings > D365 Config > Account Templates / Employee Dimensions (system admins), and the standalone
// Employee Dimensions page (d365_employee_dimensions.php) for the 'd365_employee_dimensions' special access -
// those users only get the employee actions below; templates, departments and companies stay admin only.
// Actions (POST, CSRF = $_SESSION['d365_csrf']):
//   load            -> companies, templates, ledger format dimensions, dimensions per company structure
//   save_template   -> company + dims (JSON [{dimension, default}]); empty list removes the template
//   employees       -> employees whose payroll company = company, with their stored values
//   save_employee   -> emp_id + values (JSON {dimension: value}) [+ payroll_company]
//   dim_values      -> D365 values of one dimension (for the pickers)
//   fill_from_d365  -> fill BLANK values of a company's employees from their D365 employment dims
//   import_template -> .xlsx of all active employees + current values (edit and upload back)
//   import          -> upload .xlsx/.csv (file) [+ dry_run=1 for the preview]; saves every valid row at once
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/session_check.php';
require_once __DIR__ . '/../../includes/D365/D365Payroll.php';
require_once __DIR__ . '/../../includes/D365/D365Workers.php';
require_once __DIR__ . '/../../includes/D365/D365AccountRules.php';
require_once __DIR__ . '/../../includes/D365/D365Dimensions.php';
require_once __DIR__ . '/../../includes/cost_centers.php';

header('Content-Type: application/json; charset=utf-8');
@set_time_limit(180);

// Microsoft Dynamics 365 switched off in App Settings > D365 Config
if (!d365_enabled($conDB)) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Microsoft Dynamics 365 integration is turned off']);
    exit;
}
// Actions of the Employee Dimensions screen (read setup + edit employees' payroll company and values)
const D365DIM_EMPLOYEE_ACTIONS = ['load', 'employees', 'save_employee', 'save_employees', 'dim_values'];
$d365DimAllowed = ($is_system_admin ?? false)
    || (in_array((string)($_POST['action'] ?? ''), D365DIM_EMPLOYEE_ACTIONS, true)
        && user_has_special_access($conDB, $empid ?? '', 'd365_employee_dimensions', $user_role ?? '', $user_type ?? '', false));
if (!$d365DimAllowed) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Forbidden']);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_SESSION['d365_csrf']) || !hash_equals($_SESSION['d365_csrf'], (string)($_POST['csrf'] ?? ''))) {
    echo json_encode(['ok' => false, 'error' => 'Session expired - reload the page']);
    exit;
}

$userId = (string)($empid ?? ($_SESSION['user_id'] ?? ''));
$action = (string)($_POST['action'] ?? '');

/** Active employees with stored + effective payroll company (effective = stored, else D365 employment company) */
function d365dim_employees(mysqli $db, $environment, array $employments = null)
{
    cost_center_ensure_column($db);
    // app_company / app_company_name = the company assigned in the HR app (employees.comp_no), not D365
    $stmt = $db->prepare("SELECT e.emp_id, e.name, e.payroll_company, e.comp_no AS app_company,
            (SELECT c.comp_name FROM companies c WHERE c.comp_id = e.comp_no LIMIT 1) AS app_company_name,
            (SELECT ws.legal_entity FROM d365_worker_status ws WHERE ws.emp_id = e.emp_id AND ws.legal_entity <> ''
             ORDER BY ws.environment = ? DESC, ws.checked_at DESC LIMIT 1) AS employment_company
        FROM employees e WHERE e.status = 1 ORDER BY CAST(e.emp_id AS UNSIGNED), e.emp_id");
    $stmt->bind_param('s', $environment);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    foreach ($rows as &$r) {
        if ($employments !== null && isset($employments[$r['emp_id']])) {
            $r['employment_company'] = $employments[$r['emp_id']]['entity'];
        }
        $r['app_company'] = (string)($r['app_company'] ?? '');
        $r['app_company_name'] = (string)($r['app_company_name'] ?? '');
        $r['payroll_company'] = strtoupper(trim((string)$r['payroll_company']));
        $r['employment_company'] = strtoupper(trim((string)$r['employment_company']));
        $r['company'] = $r['payroll_company'] !== '' ? $r['payroll_company'] : $r['employment_company'];
    }
    return $rows;
}

/**
 * Fills BLANK employees.payroll_company of active employees by emp_id: their D365 employment company when
 * known, else the D365 company mapped to their app company (D365 Config > Companies, d365_company_map).
 * Values already set (by hand or earlier) are never changed. Returns [emp_id => company] of what was filled.
 */
function d365dim_autofill_companies(mysqli $db, $environment)
{
    cost_center_ensure_column($db);
    $mapping = d365_company_mapping();
    $hidden = d365_hidden_companies();
    $stmt = $db->prepare("SELECT e.emp_id, e.comp_no,
            (SELECT ws.legal_entity FROM d365_worker_status ws WHERE ws.emp_id = e.emp_id AND ws.legal_entity <> ''
             ORDER BY ws.environment = ? DESC, ws.checked_at DESC LIMIT 1) AS employment_company
        FROM employees e WHERE e.status = 1 AND (e.payroll_company IS NULL OR TRIM(e.payroll_company) = '')");
    $stmt->bind_param('s', $environment);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    $filled = [];
    $up = $db->prepare("UPDATE employees SET payroll_company = ? WHERE emp_id = ? AND (payroll_company IS NULL OR TRIM(payroll_company) = '')");
    foreach ($rows as $r) {
        $code = strtoupper(trim((string)$r['employment_company']));
        if ($code === '' || in_array($code, $hidden, true)) {
            $code = (string)($mapping[(int)$r['comp_no']] ?? '');
        }
        if ($code === '' || in_array($code, $hidden, true) || !preg_match('/^[A-Z0-9_]{1,10}$/', $code)) {
            continue; // nothing known - stays blank, pick it by hand
        }
        $up->bind_param('ss', $code, $r['emp_id']);
        $up->execute();
        if ($up->affected_rows > 0) {
            $filled[$r['emp_id']] = $code;
        }
    }
    $up->close();
    return $filled;
}

/** d365_department_map setting -> ['id:6' => 'IT', 'name:finance' => '10', ...] */
function d365dept_read_map(mysqli $db)
{
    $map = [];
    $raw = (string)(get_setting($db, 'd365_department_map') ?? '');
    foreach (preg_split('/[,;\r\n]+/', $raw) as $pair) {
        if (strpos($pair, '=') === false) {
            continue;
        }
        [$app, $value] = array_map('trim', explode('=', $pair, 2));
        if ($app !== '' && $value !== '') {
            $map[(ctype_digit($app) ? 'id:' : 'name:') . mb_strtolower($app)] = $value;
        }
    }
    return $map;
}

/** Current mapping by app department id: [6 => 'IT', ...] (name entries resolved to ids) */
function d365dept_read_ids(mysqli $db)
{
    $map = d365dept_read_map($db);
    $out = [];
    $res = $db->query("SELECT id, dep_nme FROM department");
    while ($res && ($r = $res->fetch_assoc())) {
        $v = $map['id:' . $r['id']] ?? $map['name:' . mb_strtolower(trim((string)$r['dep_nme']))] ?? '';
        if ($v !== '') {
            $out[(int)$r['id']] = $v;
        }
    }
    return $out;
}

/** Save [app_dept_id => D365 value] as "ID=VALUE, ..." ('' = not mapped) */
function d365dept_write_map(mysqli $db, array $map)
{
    $pairs = [];
    foreach ($map as $id => $value) {
        $value = trim((string)$value);
        if ((int)$id > 0 && $value !== '') {
            D365Dimensions::cleanValue($value);
            $pairs[] = (int)$id . '=' . $value;
        }
    }
    $text = implode(', ', $pairs);
    $stmt = $db->prepare("UPDATE app_settings SET setting_value = ? WHERE setting_name = 'd365_department_map'");
    $stmt->bind_param('s', $text);
    $stmt->execute();
    $stmt->close();
}

/** Dimensions of all templates (template order, Worker excluded): ['CostCenter', 'Department', ...] */
function d365dim_all_dims(array $templates)
{
    $dims = [];
    foreach ($templates as $tpl) {
        foreach ($tpl as $d) {
            if (!D365Dimensions::isAuto($d['dimension']) && !in_array($d['dimension'], $dims, true)) {
                $dims[] = $d['dimension'];
            }
        }
    }
    return $dims;
}

/** Header text -> comparable key ("Emp ID" / "emp_id" -> "empid") */
function d365dim_key($text)
{
    return preg_replace('/[^a-z0-9]/', '', strtolower(trim((string)$text)));
}

/** Excel cell -> trimmed text (whole numbers lose the ".0") */
function d365dim_cell($v)
{
    if ($v === null) {
        return '';
    }
    if (is_float($v) && floor($v) == $v && abs($v) < 1e15) {
        return (string)(int)$v;
    }
    return trim((string)$v);
}

try {
    $client = new D365Client();
    new D365Workers($conDB, $client); // creates d365_worker_status when missing
    D365Dimensions::ensureTables($conDB);

    if ($action === 'load') {
        $warnings = [];
        $autoFilled = d365dim_autofill_companies($conDB, $client->getEnvironment());
        $hidden = d365_hidden_companies();
        $ledger = [];
        try {
            $formats = D365Payroll::fetchDimensionFormats($client);
            $ledger = array_values(array_filter($formats['ledger'] ?? [], function ($n) { return strtolower($n) !== 'mainaccount'; }));
        } catch (Throwable $ex) {
            $warnings[] = $ex->getMessage();
        }
        $companies = [];
        try {
            $rules = new D365AccountRules($client);
            foreach ($rules->companies() as $code => $name) {
                if (in_array($code, $hidden, true)) {
                    continue;
                }
                $companies[] = ['code' => $code, 'name' => $name, 'structure' => $rules->companyDimensions($code)];
            }
        } catch (Throwable $ex) {
            $warnings[] = 'Account structures: ' . $ex->getMessage();
        }
        $templates = D365Dimensions::getTemplates($conDB);
        foreach (array_keys($templates) as $code) { // template of a company D365 did not list (offline)
            if (!in_array($code, $hidden, true) && !in_array($code, array_column($companies, 'code'), true)) {
                $companies[] = ['code' => $code, 'name' => '', 'structure' => []];
            }
        }
        usort($companies, function ($a, $b) { return strcmp($a['code'], $b['code']); });
        $counts = [];
        foreach (d365dim_employees($conDB, $client->getEnvironment()) as $r) {
            $key = $r['company'] !== '' ? $r['company'] : '-';
            $counts[$key] = ($counts[$key] ?? 0) + 1;
        }
        echo json_encode(['ok' => true, 'environment' => $client->getEnvironment(), 'ledger' => $ledger, 'companies' => $companies,
            'templates' => (object)$templates, 'counts' => (object)$counts, 'auto_filled' => count($autoFilled), 'warnings' => $warnings, 'hidden' => $hidden], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($action === 'company_remove' || $action === 'company_restore') {
        // Closed companies (e.g. MTL): hidden from Account Templates and every company picker - D365 itself is not changed
        $code = strtoupper(trim((string)($_POST['company'] ?? '')));
        if (!preg_match('/^[A-Z0-9_]{1,10}$/', $code)) {
            throw new InvalidArgumentException('Invalid company');
        }
        $hidden = d365_hidden_companies();
        if ($action === 'company_remove') {
            $hidden[] = $code;
            D365Dimensions::saveTemplate($conDB, $code, [], $userId); // its template goes too
        } else {
            $hidden = array_diff($hidden, [$code]);
        }
        $text = implode(', ', array_values(array_unique($hidden)));
        $stmt = $conDB->prepare("INSERT INTO app_settings (setting_name, setting_value, setting_group, description, input_type)
            VALUES ('d365_hidden_companies', ?, 'D365_Config', 'D365 Companies removed from the app (closed companies, comma separated, e.g. MTL)', 'text')
            ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
        $stmt->bind_param('s', $text);
        $stmt->execute();
        $stmt->close();
        // employees still pinned to a removed company keep it until changed - tell the user how many
        $pinned = 0;
        $st = $conDB->prepare("SELECT COUNT(*) FROM employees WHERE status = 1 AND payroll_company = ?");
        $st->bind_param('s', $code);
        $st->execute();
        $pinned = (int)$st->get_result()->fetch_row()[0];
        $st->close();
        echo json_encode(['ok' => true, 'hidden' => array_values(array_unique($hidden)), 'pinned' => $pinned]);
        exit;
    }

    if ($action === 'save_template') {
        $dims = json_decode((string)($_POST['dims'] ?? '[]'), true);
        if (!is_array($dims)) {
            throw new InvalidArgumentException('Invalid template');
        }
        D365Dimensions::saveTemplate($conDB, (string)($_POST['company'] ?? ''), $dims, $userId);
        echo json_encode(['ok' => true, 'templates' => (object)D365Dimensions::getTemplates($conDB)]);
        exit;
    }

    if ($action === 'employees') {
        // company: '*' = all active employees, '-' = no known payroll company, else one company
        // scope: 'applied' = payroll company has a template (sync uses it), 'unapplied' = the rest, '' = all
        $company = strtoupper(trim((string)($_POST['company'] ?? '*')));
        $scope = (string)($_POST['scope'] ?? '');
        $templates = D365Dimensions::getTemplates($conDB);
        $rows = array_values(array_filter(d365dim_employees($conDB, $client->getEnvironment()), function ($r) use ($company, $scope, $templates) {
            $applied = !empty($templates[$r['company']]);
            if (($scope === 'applied' && !$applied) || ($scope === 'unapplied' && $applied)) {
                return false;
            }
            if ($company === '*' || $company === '') {
                return true;
            }
            return $company === '-' ? $r['company'] === '' : $r['company'] === $company;
        }));
        $values = D365Dimensions::getEmployeeValues($conDB, array_column($rows, 'emp_id'));
        foreach ($rows as &$r) {
            $tpl = $templates[$r['company']] ?? [];
            $r['values'] = (object)($values[$r['emp_id']] ?? []);
            $r['has_template'] = (bool)$tpl;
            $r['missing'] = $tpl ? D365Dimensions::resolve($tpl, $r['emp_id'], $values[$r['emp_id']] ?? [])['missing'] : [];
        }
        unset($r);
        echo json_encode(['ok' => true, 'company' => $company, 'templates' => (object)$templates, 'employees' => $rows], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($action === 'save_employee') {
        $target = (string)($_POST['emp_id'] ?? '');
        if (!preg_match('/^[A-Za-z0-9\-]{1,20}$/', $target)) {
            throw new InvalidArgumentException('Invalid employee ID');
        }
        $values = json_decode((string)($_POST['values'] ?? '{}'), true);
        if (!is_array($values)) {
            throw new InvalidArgumentException('Invalid values');
        }
        D365Dimensions::saveEmployeeValues($conDB, $target, $values, $userId);
        if (isset($_POST['payroll_company'])) {
            $pc = payroll_company_clean((string)$_POST['payroll_company']);
            $pc = $pc === '' ? null : $pc;
            $stmt = $conDB->prepare("UPDATE employees SET payroll_company = ? WHERE emp_id = ?");
            $stmt->bind_param('ss', $pc, $target);
            $stmt->execute();
            $stmt->close();
        }
        echo json_encode(['ok' => true, 'values' => (object)(D365Dimensions::getEmployeeValues($conDB, [$target])[$target] ?? [])]);
        exit;
    }

    if ($action === 'save_employees') {
        // "Add employees" popup: same payroll company + values for several employees at once
        $ids = json_decode((string)($_POST['emp_ids'] ?? '[]'), true);
        $values = json_decode((string)($_POST['values'] ?? '{}'), true);
        if (!is_array($ids) || !$ids || !is_array($values)) {
            throw new InvalidArgumentException('Pick at least one employee');
        }
        $pc = payroll_company_clean((string)($_POST['payroll_company'] ?? ''));
        if ($pc === '') {
            throw new InvalidArgumentException('Pick a payroll company');
        }
        $values = array_filter($values, function ($v) { return trim((string)$v) !== ''; }); // blanks never clear existing values here
        foreach ($values as $v) {
            D365Dimensions::cleanValue($v); // validate before writing anything
        }
        $stmt = $conDB->prepare("UPDATE employees SET payroll_company = ? WHERE emp_id = ?");
        $saved = 0;
        foreach ($ids as $id) {
            $id = (string)$id;
            if (!preg_match('/^[A-Za-z0-9\-]{1,20}$/', $id)) {
                continue;
            }
            $stmt->bind_param('ss', $pc, $id);
            $stmt->execute();
            D365Dimensions::saveEmployeeValues($conDB, $id, $values, $userId);
            $saved++;
        }
        $stmt->close();
        echo json_encode(['ok' => true, 'saved' => $saved]);
        exit;
    }

    // ---------------------------------------------------------------- Departments (app -> D365)
    // Mapping is stored in the D365 Config setting d365_department_map as "APP_DEPT_ID=D365_VALUE, ..."
    // (D365Workers::departmentFor reads it: id entries win over name entries and the colleagues' vote).

    if ($action === 'dept_load') {
        $map = d365dept_read_map($conDB);
        $rows = [];
        $res = $conDB->query("SELECT d.id, d.dep_nme, d.dep_nme_ar,
                (SELECT COUNT(*) FROM employees e WHERE e.dept = d.id AND e.status = 1) AS employees
            FROM department d ORDER BY d.dep_nme");
        while ($res && ($r = $res->fetch_assoc())) {
            $r['d365'] = $map['id:' . $r['id']] ?? $map['name:' . mb_strtolower(trim((string)$r['dep_nme']))] ?? '';
            $rows[] = $r;
        }
        $values = D365Dimensions::dimensionValues($client, 'Department', true); // always live: suspends/creates in D365 show at once
        // Suspended in D365 = gone: drop it from the list and from the saved mapping
        $suspended = [];
        foreach ($values as $v) {
            if (!$v['active']) {
                $suspended[$v['value']] = true;
            }
        }
        $values = array_values(array_filter($values, function ($v) { return $v['active']; }));
        $unmapped = [];
        if ($suspended) {
            $ids = d365dept_read_ids($conDB);
            foreach ($ids as $id => $code) {
                if (isset($suspended[$code])) {
                    unset($ids[$id]);
                    $unmapped[] = $code;
                }
            }
            if ($unmapped) {
                d365dept_write_map($conDB, $ids);
                foreach ($rows as &$r) {
                    if (isset($suspended[$r['d365']])) {
                        $r['removed'] = $r['d365'];
                        $r['d365'] = '';
                    }
                }
                unset($r);
            }
        }
        echo json_encode(['ok' => true, 'environment' => $client->getEnvironment(), 'can_write' => $client->canWrite(),
            'departments' => $rows, 'd365' => $values, 'unmapped_suspended' => array_values(array_unique($unmapped))], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($action === 'dept_save_map') {
        $map = json_decode((string)($_POST['map'] ?? '{}'), true);
        if (!is_array($map)) {
            throw new InvalidArgumentException('Invalid mapping');
        }
        // One D365 department per app department
        $owner = [];
        $names = [];
        $res = $conDB->query("SELECT id, dep_nme FROM department");
        while ($res && ($r = $res->fetch_row())) {
            $names[(int)$r[0]] = $r[1];
        }
        foreach ($map as $id => $value) {
            $key = strtolower(trim((string)$value));
            if ($key === '') {
                continue;
            }
            if (isset($owner[$key])) {
                throw new InvalidArgumentException('D365 department ' . trim((string)$value) . ' is mapped to both ' .
                    ($names[$owner[$key]] ?? $owner[$key]) . ' and ' . ($names[(int)$id] ?? $id) . ' - each D365 department can be used once');
            }
            $owner[$key] = (int)$id;
        }
        d365dept_write_map($conDB, $map);
        echo json_encode(['ok' => true]);
        exit;
    }

    if ($action === 'dept_create') {
        // Creates the department (operating unit) in D365 - its Department dimension value follows automatically
        if (!$client->canWrite()) {
            throw new RuntimeException('Writes are disabled for the ' . $client->getEnvironment() . ' environment (D365 Config > Allow Writes)');
        }
        $deptId = (int)($_POST['dept_id'] ?? 0);
        $code = trim((string)($_POST['code'] ?? ''));
        if ($code === '' && $deptId > 0) {
            $code = (string)$deptId; // D365 code = app department ID
        }
        $name = trim((string)($_POST['name'] ?? ''));
        if ($deptId <= 0 || !preg_match('/^[A-Za-z0-9_]{1,20}$/', $code) || $name === '' || mb_strlen($name) > 60) {
            throw new InvalidArgumentException('Code must be 1-20 letters/digits (no dashes) and the name 1-60 characters');
        }
        foreach (D365Dimensions::dimensionValues($client, 'Department', true) as $v) {
            if (strcasecmp($v['value'], $code) === 0) {
                throw new RuntimeException("D365 already has department $code ({$v['name']}) - pick it from the list instead");
            }
        }
        $r = $client->create('OperatingUnits', [
            'OperatingUnitNumber' => $code,
            'OperatingUnitType'   => 'OMDepartment',
            'Name'                => $name,
            'NameAlias'           => mb_substr($name, 0, 20),
            'LanguageId'          => 'en-US',
        ]);
        if (!empty($r['error'])) {
            throw new RuntimeException('D365: ' . $r['error']);
        }
        $created = (string)($r['data']['OperatingUnitNumber'] ?? $code); // D365 may number it itself
        $map = d365dept_read_ids($conDB);
        $map[$deptId] = $created;
        d365dept_write_map($conDB, $map);
        D365Dimensions::dimensionValues($client, 'Department', true); // refresh the cached list
        echo json_encode(['ok' => true, 'code' => $created, 'renumbered' => $created !== $code], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($action === 'dept_assign') {
        // Department dimension of every active employee from their app department (Employee Dimensions)
        $overwrite = !empty($_POST['overwrite']);
        $map = d365dept_read_map($conDB);
        $res = $conDB->query("SELECT e.emp_id, e.dept, d.dep_nme FROM employees e LEFT JOIN department d ON d.id = e.dept WHERE e.status = 1");
        $rows = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
        $stored = D365Dimensions::getEmployeeValues($conDB, array_column($rows, 'emp_id'));
        $set = $kept = $unmapped = 0;
        foreach ($rows as $r) {
            $value = $map['id:' . $r['dept']] ?? $map['name:' . mb_strtolower(trim((string)$r['dep_nme']))] ?? '';
            if ($value === '') {
                $unmapped++;
                continue;
            }
            $current = (string)($stored[$r['emp_id']]['Department'] ?? '');
            if ($current === $value || ($current !== '' && !$overwrite)) {
                $kept++;
                continue;
            }
            D365Dimensions::saveEmployeeValues($conDB, $r['emp_id'], ['Department' => $value], $userId);
            $set++;
        }
        echo json_encode(['ok' => true, 'set' => $set, 'kept' => $kept, 'unmapped' => $unmapped]);
        exit;
    }

    // ---------------------------------------------------------------- Companies (app company -> D365 company)
    // Stored in setting d365_company_map as "COMP_ID=CODE, ..." (cost_centers.php d365_company_mapping);
    // used by the new-employee modal and D365 registration (D365Workers::suggestCompanyFor)

    if ($action === 'comp_load') {
        $mapping = d365_company_mapping();
        $suggest = [];
        $file = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'd365_company_map_' . md5($client->getResourceUrl()) . '.json';
        $cached = is_readable($file) ? json_decode((string)@file_get_contents($file), true) : null;
        foreach ((array)($cached['map'] ?? []) as $id => $code) {
            $suggest[(int)$id] = $code; // learned from where colleagues are employed in D365
        }
        $rows = [];
        $res = $conDB->query("SELECT c.comp_id, c.comp_name, c.comp_name_ar,
                (SELECT COUNT(*) FROM employees e WHERE e.comp_no = c.comp_id AND e.status = 1) AS employees
            FROM companies c ORDER BY c.comp_name");
        while ($res && ($r = $res->fetch_assoc())) {
            $id = (int)$r['comp_id'];
            $r['d365'] = $mapping[$id] ?? '';
            $r['suggest'] = $suggest[$id] ?? '';
            $rows[] = $r;
        }
        $companies = [];
        foreach (payroll_company_list() as $code => $c) {
            $companies[] = ['code' => $code, 'name' => $c['name']];
        }
        echo json_encode(['ok' => true, 'app' => $rows, 'companies' => $companies], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($action === 'comp_save_map') {
        $map = json_decode((string)($_POST['map'] ?? '{}'), true);
        if (!is_array($map)) {
            throw new InvalidArgumentException('Invalid mapping');
        }
        $pairs = [];
        foreach ($map as $id => $code) {
            $code = strtoupper(trim((string)$code));
            if ((int)$id > 0 && preg_match('/^[A-Z0-9_]{1,10}$/', $code)) {
                $pairs[] = (int)$id . '=' . $code;
            }
        }
        $text = implode(', ', $pairs);
        $stmt = $conDB->prepare("INSERT INTO app_settings (setting_name, setting_value, setting_group, description, input_type)
            VALUES ('d365_company_map', ?, 'D365_Config', 'D365 Company per App Company (APP COMPANY ID=D365 COMPANY, comma separated) - set in D365 Config > Companies', 'text')
            ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
        $stmt->bind_param('s', $text);
        $stmt->execute();
        $stmt->close();
        echo json_encode(['ok' => true]);
        exit;
    }

    // ---------------------------------------------------------------- Excel bulk upload
    // Columns: Emp ID | Name (ignored) | Payroll Company | one column per template dimension.
    // Blank cells keep the current value; a blank Payroll Company keeps the employee's company.

    if ($action === 'import_template') {
        require_once __DIR__ . '/../../vendor/autoload.php';
        d365dim_autofill_companies($conDB, $client->getEnvironment());
        $templates = D365Dimensions::getTemplates($conDB);
        $dims = d365dim_all_dims($templates);
        $rows = d365dim_employees($conDB, $client->getEnvironment());
        $values = D365Dimensions::getEmployeeValues($conDB, array_column($rows, 'emp_id'));

        $book = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $book->getActiveSheet();
        $sheet->setTitle('Employee Dimensions');
        $sheet->fromArray(array_merge(['Emp ID', 'Name', 'Payroll Company'], $dims), null, 'A1');
        $line = 2;
        foreach ($rows as $r) {
            $sheet->setCellValueExplicit('A' . $line, (string)$r['emp_id'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValue('B' . $line, (string)$r['name']);
            $sheet->setCellValueExplicit('C' . $line, $r['company'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            foreach ($dims as $i => $dim) {
                $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(4 + $i);
                $sheet->setCellValueExplicit($col . $line, (string)($values[$r['emp_id']][$dim] ?? ''), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            }
            $line++;
        }
        $last = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(3 + count($dims));
        $sheet->getStyle('A1:' . $last . '1')->getFont()->setBold(true);
        $sheet->getStyle('A1:' . $last . '1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('DDEBF7');
        $sheet->getStyle('A:' . $last)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_TEXT);
        $sheet->freezePane('D2');
        for ($i = 1; $i <= 3 + count($dims); $i++) {
            $sheet->getColumnDimension(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i))->setAutoSize(true);
        }

        // Help sheet: which dimensions each company's template uses
        $help = $book->createSheet();
        $help->setTitle('Templates');
        $help->fromArray(['Payroll Company', 'Dimensions used (fill these columns)'], null, 'A1');
        $help->getStyle('A1:B1')->getFont()->setBold(true);
        $line = 2;
        foreach ($templates as $code => $tpl) {
            $help->setCellValue('A' . $line, $code);
            $help->setCellValue('B' . $line, implode(', ', array_filter(array_column($tpl, 'dimension'), function ($d) { return !D365Dimensions::isAuto($d); })));
            $line++;
        }
        $help->setCellValue('A' . ($line + 1), 'Blank cells keep the current value. Values may not contain dashes (-).');
        $help->getColumnDimension('A')->setAutoSize(true);
        $help->getColumnDimension('B')->setAutoSize(true);
        $book->setActiveSheetIndex(0);

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="employee_dimensions_' . date('Ymd') . '.xlsx"');
        header('Cache-Control: no-store');
        (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($book))->save('php://output');
        exit;
    }

    if ($action === 'import') {
        require_once __DIR__ . '/../../vendor/autoload.php';
        $dryRun = !empty($_POST['dry_run']);
        $file = $_FILES['file'] ?? null;
        if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
            throw new InvalidArgumentException('Pick an Excel file to upload');
        }
        $ext = strtolower(pathinfo((string)$file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['xlsx', 'xls', 'csv'], true)) {
            throw new InvalidArgumentException('Only .xlsx, .xls or .csv files');
        }
        if ($file['size'] > 5 * 1024 * 1024) {
            throw new InvalidArgumentException('File is larger than 5 MB');
        }
        $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader($ext === 'csv' ? 'Csv' : ($ext === 'xls' ? 'Xls' : 'Xlsx'));
        $reader->setReadDataOnly(true);
        $data = $reader->load($file['tmp_name'])->getSheet(0)->toArray(null, true, false, false);
        if (count($data) < 2) {
            throw new InvalidArgumentException('The file has no rows under the header');
        }

        // Header -> columns
        $templates = D365Dimensions::getTemplates($conDB);
        $known = [];
        foreach (d365dim_all_dims($templates) as $dim) {
            $known[d365dim_key($dim)] = $dim;
        }
        $empCol = $companyCol = null;
        $dimCols = [];
        $ignored = [];
        foreach ($data[0] as $i => $head) {
            $k = d365dim_key($head);
            if ($k === '') {
                continue;
            }
            if (in_array($k, ['empid', 'employeeid', 'id', 'empno', 'personnelnumber'], true)) {
                $empCol = $i;
            } elseif ($k === 'payrollcompany') {
                $companyCol = $i;
            } elseif (isset($known[$k])) {
                $dimCols[$i] = $known[$k];
            } elseif (!in_array($k, ['name', 'employeename', 'empname'], true)) {
                $ignored[] = trim((string)$head);
            }
        }
        if ($empCol === null) {
            throw new InvalidArgumentException('Column "Emp ID" not found in the first row');
        }
        if (!$dimCols && $companyCol === null) {
            throw new InvalidArgumentException('No dimension column found - use the headers of the downloaded template (' . implode(', ', array_values($known)) . ')');
        }

        $employees = [];
        foreach (d365dim_employees($conDB, $client->getEnvironment()) as $r) {
            $employees[(string)$r['emp_id']] = $r;
        }
        $stored = D365Dimensions::getEmployeeValues($conDB, array_keys($employees));
        $d365Lists = []; // dimension => [lowercase value => true] (only when D365 / cache answers)

        $valid = $errors = $warnings = [];
        $seen = [];
        $changes = $skipped = 0;
        for ($n = 1; $n < count($data); $n++) {
            $row = $data[$n];
            $line = $n + 1;
            $id = d365dim_cell($row[$empCol] ?? null);
            $rowValues = [];
            foreach ($dimCols as $i => $dim) {
                $rowValues[$dim] = d365dim_cell($row[$i] ?? null);
            }
            $company = $companyCol !== null ? strtoupper(d365dim_cell($row[$companyCol] ?? null)) : '';
            if ($id === '' && $company === '' && !array_filter($rowValues, 'strlen')) {
                continue; // empty line
            }
            if ($id === '' || !isset($employees[$id])) { // only status = 1 employees can be imported
                $why = 'Emp ID is empty';
                if ($id !== '') {
                    $st = $conDB->prepare("SELECT status FROM employees WHERE emp_id = ? LIMIT 1");
                    $st->bind_param('s', $id);
                    $st->execute();
                    $found = $st->get_result()->fetch_row();
                    $st->close();
                    $why = $found ? 'Employee is not active (status ' . $found[0] . ') - only active employees are imported' : 'Employee not found';
                }
                $errors[] = ['row' => $line, 'emp_id' => $id, 'error' => $why];
                continue;
            }
            if (isset($seen[$id])) {
                $errors[] = ['row' => $line, 'emp_id' => $id, 'error' => 'Emp ID repeated (first on row ' . $seen[$id] . ')'];
                continue;
            }
            $seen[$id] = $line;
            if ($company !== '' && payroll_company_clean($company) === '') {
                $errors[] = ['row' => $line, 'emp_id' => $id, 'error' => "Unknown payroll company \"$company\""];
                continue;
            }
            $values = [];
            $bad = null;
            foreach ($rowValues as $dim => $v) {
                if ($v === '') {
                    continue; // blank keeps the current value
                }
                try {
                    $values[$dim] = D365Dimensions::cleanValue($v);
                } catch (InvalidArgumentException $ex) {
                    $bad = "$dim: " . $ex->getMessage();
                    break;
                }
            }
            if ($bad !== null) {
                $errors[] = ['row' => $line, 'emp_id' => $id, 'error' => $bad];
                continue;
            }
            $effective = $company !== '' ? $company : $employees[$id]['company'];
            if (empty($templates[$effective])) {
                $warnings[] = ['row' => $line, 'emp_id' => $id, 'warning' => ($effective === '' ? 'No payroll company' : "$effective has no account template") . ' - values are saved but payroll sync will not use them'];
            }
            $rowChanges = 0;
            foreach ($values as $dim => $v) {
                if ((string)($stored[$id][$dim] ?? '') !== $v) {
                    $rowChanges++;
                }
                if (!array_key_exists($dim, $d365Lists)) {
                    $d365Lists[$dim] = null;
                    try {
                        $d365Lists[$dim] = [];
                        foreach (D365Dimensions::dimensionValues($client, $dim) as $dv) {
                            $d365Lists[$dim][strtolower($dv['value'])] = true;
                        }
                    } catch (Throwable $ex) {
                        $d365Lists[$dim] = null; // D365 unreachable - no check
                    }
                }
                if (!empty($d365Lists[$dim]) && !isset($d365Lists[$dim][strtolower($v)])) {
                    $warnings[] = ['row' => $line, 'emp_id' => $id, 'warning' => "$dim \"$v\" is not a D365 value"];
                }
            }
            if ($company !== '' && $company !== $employees[$id]['payroll_company']) {
                $rowChanges++;
            }
            if (!$rowChanges) {
                $skipped++;
                continue; // nothing new for this employee
            }
            $changes += $rowChanges;
            $valid[] = ['emp_id' => $id, 'company' => $company, 'values' => $values];
        }

        $saved = 0;
        if (!$dryRun && $valid) {
            $pcStmt = $conDB->prepare("UPDATE employees SET payroll_company = ? WHERE emp_id = ?");
            $conDB->begin_transaction();
            try {
                foreach ($valid as $v) {
                    if ($v['company'] !== '') {
                        $pcStmt->bind_param('ss', $v['company'], $v['emp_id']);
                        $pcStmt->execute();
                    }
                    if ($v['values']) {
                        D365Dimensions::saveEmployeeValues($conDB, $v['emp_id'], $v['values'], $userId);
                    }
                    $saved++;
                }
                $conDB->commit();
            } catch (Throwable $ex) {
                $conDB->rollback();
                throw $ex;
            }
            $pcStmt->close();
        }
        echo json_encode(['ok' => true, 'dry_run' => $dryRun, 'columns' => array_values($dimCols), 'company_column' => $companyCol !== null,
            'ignored' => $ignored, 'employees' => count($valid), 'changes' => $changes, 'unchanged' => $skipped, 'saved' => $saved,
            'errors' => $errors, 'warnings' => array_slice($warnings, 0, 300), 'warning_count' => count($warnings)], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($action === 'dim_values') {
        $dim = (string)($_POST['dimension'] ?? '');
        if (!preg_match('/^[A-Za-z0-9_]{1,40}$/', $dim)) {
            throw new InvalidArgumentException('Invalid dimension');
        }
        echo json_encode(['ok' => true, 'values' => D365Dimensions::dimensionValues($client, $dim, !empty($_POST['refresh']))], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($action === 'fill_from_d365') {
        // Copies what D365 already has on each employment (default dimension format) into the app - only
        // into BLANK values, so nothing typed here is overwritten. Department falls back to the app's
        // department mapping (D365 Config > department map / colleagues' majority).
        $company = strtoupper(trim((string)($_POST['company'] ?? '')));
        $template = D365Dimensions::getTemplate($conDB, $company);
        if (!$template) {
            throw new RuntimeException("$company has no account template yet");
        }
        $payroll = new D365Payroll($conDB, $client);
        $employments = $payroll->getEmployments();
        $format = array_map('strtolower', D365Payroll::fetchDimensionFormats($client)['default'] ?? []);
        $workers = new D365Workers($conDB, $client);
        $rows = array_values(array_filter(d365dim_employees($conDB, $client->getEnvironment(), $employments), function ($r) use ($company) {
            return $r['company'] === $company;
        }));
        $stored = D365Dimensions::getEmployeeValues($conDB, array_column($rows, 'emp_id'));
        $filled = 0;
        $employeesTouched = 0;
        foreach ($rows as $r) {
            $id = $r['emp_id'];
            $segments = isset($employments[$id]) ? explode('-', (string)$employments[$id]['dims']) : [];
            $new = [];
            foreach ($template as $t) {
                $dim = $t['dimension'];
                if (D365Dimensions::isAuto($dim) || trim((string)($stored[$id][$dim] ?? '')) !== '') {
                    continue;
                }
                $i = array_search(strtolower($dim), $format, true);
                $value = $i === false ? '' : trim((string)($segments[$i] ?? ''));
                if ($value === '' && strtolower($dim) === 'department') {
                    $value = (string)$workers->departmentFor($id, $employments);
                }
                if ($value !== '' && preg_match('/^[\p{L}\p{N}_.\/ ]{1,40}$/u', $value)) {
                    $new[$dim] = $value;
                }
            }
            if ($new) {
                D365Dimensions::saveEmployeeValues($conDB, $id, $new, $userId);
                $filled += count($new);
                $employeesTouched++;
            }
        }
        echo json_encode(['ok' => true, 'filled' => $filled, 'employees' => $employeesTouched, 'checked' => count($rows)]);
        exit;
    }

    echo json_encode(['ok' => false, 'error' => 'Unknown action']);
} catch (Throwable $ex) {
    echo json_encode(['ok' => false, 'error' => $ex->getMessage()]);
}
