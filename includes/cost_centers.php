<?php
/**
 * Employee Cost Center (D365 financial dimension "CostCenter").
 *
 * - employees.cost_center VARCHAR(20) NOT NULL DEFAULT 'C30' (added on first use)
 * - The list of cost centers is read from Dynamics 365 (FinancialDimensionValues, CostCenter), so a new
 *   cost center created in D365 shows up in the app by itself. It is cached for 6 hours in the temp dir;
 *   when D365 cannot be reached the last cached list is used, then the values already on employees.
 */

if (!defined('COST_CENTER_DEFAULT')) {
    define('COST_CENTER_DEFAULT', 'C30');
}

/** Add employees.cost_center (default C30) when it does not exist yet */
function cost_center_ensure_column($conDB)
{
    static $done = false;
    if ($done || !($conDB instanceof mysqli)) {
        return;
    }
    $done = true;
    $res = @$conDB->query("SHOW COLUMNS FROM `employees` LIKE 'cost_center'");
    if ($res && $res->num_rows === 0) {
        @$conDB->query("ALTER TABLE `employees` ADD COLUMN `cost_center` VARCHAR(20) NOT NULL DEFAULT '" . COST_CENTER_DEFAULT . "'");
    }
    // D365 legal entity the employee's payroll is booked in (NULL = the employee's D365 employment company)
    $res = @$conDB->query("SHOW COLUMNS FROM `employees` LIKE 'payroll_company'");
    if ($res && $res->num_rows === 0) {
        @$conDB->query("ALTER TABLE `employees` ADD COLUMN `payroll_company` VARCHAR(10) NULL DEFAULT NULL");
    }
}

/**
 * D365 legal entities for the payroll company select: ['MHO' => ['name' => ..., 'cost_center' => bool], ...]
 * cost_center = the company's account structure has a CostCenter segment (cost center only matters there).
 */
function payroll_company_list()
{
    static $list = null;
    if ($list !== null) {
        return $list;
    }
    $list = [];
    try {
        require_once __DIR__ . '/D365AccountRules.php';
        $config = D365Client::loadConfig();
        if (!empty($config['CLIENT_SECRET']) && !empty($config['RESOURCE_URL'])) {
            $rules = new D365AccountRules(new D365Client($config));
            $withCc = $rules->companiesWithDimension('CostCenter');
            foreach ($rules->companies() as $code => $name) {
                $list[$code] = ['name' => $name, 'cost_center' => in_array($code, $withCc, true)];
            }
        }
    } catch (Throwable $ex) {
        error_log('Payroll companies from D365: ' . $ex->getMessage());
    }
    return $list;
}

/** D365 employment company of an employee as last seen by the app (d365_worker_status), '' when unknown */
function payroll_employment_company($conDB, $empId)
{
    try {
        $env = '';
        require_once __DIR__ . '/D365Client.php';
        $env = (string)(new D365Client(D365Client::loadConfig()))->getEnvironment();
    } catch (Throwable $ex) {
    }
    $stmt = @$conDB->prepare("SELECT legal_entity FROM d365_worker_status WHERE emp_id = ? AND legal_entity <> ''
        ORDER BY environment = ? DESC, checked_at DESC LIMIT 1");
    if (!$stmt) {
        return '';
    }
    $empId = (string)$empId;
    $stmt->bind_param('ss', $empId, $env);
    $stmt->execute();
    $value = strtoupper(trim((string)($stmt->get_result()->fetch_row()[0] ?? '')));
    $stmt->close();
    return $value;
}

/**
 * Everything the profile's D365 block shows, from the app DB only (no D365 call on page load):
 * ['environment', 'status' row of d365_worker_status|null, 'employment_company', 'payroll_company', 'payroll_auto',
 *  'uses_cost_center', 'department' (D365 value the app department maps to, '' unknown)]
 */
function d365_profile_info($conDB, array $emprow)
{
    $empId = (string)($emprow['emp_id'] ?? '');
    $info = ['environment' => '', 'status' => null, 'employment_company' => '', 'payroll_company' => '', 'payroll_auto' => true,
        'uses_cost_center' => true, 'department' => ''];
    $client = null;
    try {
        require_once __DIR__ . '/D365Workers.php';
        $client = new D365Client(D365Client::loadConfig());
        $info['environment'] = (string)$client->getEnvironment();
        $workers = new D365Workers($conDB, $client);
        $info['status'] = $workers->loadStatus($empId);
        $info['department'] = $workers->departmentForOffline($empId);
    } catch (Throwable $ex) {
        error_log('D365 profile block: ' . $ex->getMessage());
    }
    $info['employment_company'] = strtoupper((string)($info['status']['legal_entity'] ?? '')) ?: payroll_employment_company($conDB, $empId);
    $stored = strtoupper(trim((string)($emprow['payroll_company'] ?? '')));
    $info['payroll_auto'] = $stored === '';
    $info['payroll_company'] = $stored !== '' ? $stored : $info['employment_company'];
    $info['uses_cost_center'] = payroll_company_uses_cost_center($info['payroll_company']);
    return $info;
}

/** Company the employee's payroll is booked in: employees.payroll_company, else the D365 employment company ('' = unknown) */
function payroll_company_effective($conDB, $empId, $stored = null)
{
    $stored = strtoupper(trim((string)$stored));
    return $stored !== '' ? $stored : payroll_employment_company($conDB, $empId);
}

/**
 * Whether a company's account structures have a CostCenter segment (only MHO today).
 * Unknown company / D365 unreachable = true, so the field is never hidden by mistake.
 */
function payroll_company_uses_cost_center($company)
{
    $company = strtoupper(trim((string)$company));
    $list = payroll_company_list();
    if ($company === '' || !isset($list[$company])) {
        return true;
    }
    return $list[$company]['cost_center'];
}

/** <option> list for the payroll company; '' = automatic (D365 employment company, shown when known) */
function payroll_company_options_html($selected, $employmentCompany = '')
{
    $selected = strtoupper(trim((string)$selected));
    $employmentCompany = strtoupper(trim((string)$employmentCompany));
    $list = payroll_company_list();
    $autoUsesCc = payroll_company_uses_cost_center($employmentCompany);
    $html = '<option value="" data-cost-center="' . ($autoUsesCc ? '1' : '0') . '" data-company="' . htmlspecialchars($employmentCompany, ENT_QUOTES, 'UTF-8') . '"'
        . ($selected === '' ? ' selected' : '') . '>'
        . htmlspecialchars(__('payroll_company_auto', 'Auto - D365 employment company') . ($employmentCompany !== '' ? ' (' . $employmentCompany . ')' : ''), ENT_QUOTES, 'UTF-8') . '</option>';
    if ($selected !== '' && !isset($list[$selected])) {
        $list[$selected] = ['name' => '', 'cost_center' => false];
    }
    foreach ($list as $code => $c) {
        $html .= '<option value="' . htmlspecialchars($code, ENT_QUOTES, 'UTF-8') . '" data-cost-center="' . ($c['cost_center'] ? '1' : '0') . '"'
            . ($code === $selected ? ' selected' : '') . '>' . htmlspecialchars($code . ($c['name'] !== '' ? ' - ' . $c['name'] : ''), ENT_QUOTES, 'UTF-8') . '</option>';
    }
    return $html;
}

/** Normalise a posted payroll company: known D365 legal entity or '' (automatic) */
function payroll_company_clean($value)
{
    $value = strtoupper(trim((string)$value));
    return ($value !== '' && isset(payroll_company_list()[$value])) ? $value : '';
}

/**
 * Cost centers: [['value' => 'C30', 'name' => 'الاداريين', 'active' => true], ...] sorted by value.
 * $refresh = true skips the 6h cache.
 */
function cost_center_list($conDB, $refresh = false)
{
    static $list = null;
    if ($list !== null && !$refresh) {
        return $list;
    }
    $file = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'almutlak_cost_centers.json';
    $cached = is_file($file) ? json_decode((string)@file_get_contents($file), true) : null;
    if (!$refresh && is_array($cached) && time() - (int)($cached['t'] ?? 0) < 21600 && !empty($cached['list'])) {
        return $list = $cached['list'];
    }

    $fresh = null;
    try {
        require_once __DIR__ . '/D365Client.php';
        $config = D365Client::loadConfig();
        if (!empty($config['CLIENT_SECRET']) && !empty($config['RESOURCE_URL'])) {
            $r = (new D365Client($config))->getAll('FinancialDimensionValues', [
                '$filter' => "FinancialDimension eq 'CostCenter'",
                '$select' => 'DimensionValue,Description,IsSuspended',
            ]);
            if (!$r['error']) {
                $fresh = [];
                foreach ($r['data']['value'] ?? [] as $v) {
                    $fresh[] = ['value' => (string)$v['DimensionValue'], 'name' => (string)$v['Description'], 'active' => ($v['IsSuspended'] ?? 'No') !== 'Yes'];
                }
                usort($fresh, function ($a, $b) { return strnatcmp($a['value'], $b['value']); });
            }
        }
    } catch (Throwable $ex) {
        error_log('Cost centers from D365: ' . $ex->getMessage());
    }

    if ($fresh) {
        @file_put_contents($file, json_encode(['t' => time(), 'list' => $fresh], JSON_UNESCAPED_UNICODE), LOCK_EX);
        return $list = $fresh;
    }
    if (is_array($cached) && !empty($cached['list'])) {
        return $list = $cached['list']; // D365 unreachable - last known list
    }
    // Nothing from D365 yet: the values already used by employees, plus the default
    $values = [COST_CENTER_DEFAULT => true];
    cost_center_ensure_column($conDB);
    $res = @$conDB->query("SELECT DISTINCT cost_center FROM employees WHERE cost_center <> ''");
    while ($res && ($row = $res->fetch_row())) {
        $values[$row[0]] = true;
    }
    $list = [];
    foreach (array_keys($values) as $v) {
        $list[] = ['value' => (string)$v, 'name' => '', 'active' => true];
    }
    return $list;
}

/** <option> list; suspended cost centers only appear when already selected */
function cost_center_options_html($conDB, $selected = null)
{
    $selected = (string)($selected ?? '') !== '' ? (string)$selected : COST_CENTER_DEFAULT;
    $html = '';
    $found = false;
    foreach (cost_center_list($conDB) as $cc) {
        $isSel = $cc['value'] === $selected;
        if (!$cc['active'] && !$isSel) {
            continue;
        }
        $found = $found || $isSel;
        $label = $cc['value'] . ($cc['name'] !== '' ? ' - ' . $cc['name'] : '') . ($cc['active'] ? '' : ' (suspended in D365)');
        $html .= '<option value="' . htmlspecialchars($cc['value'], ENT_QUOTES, 'UTF-8') . '"' . ($isSel ? ' selected' : '') . '>'
            . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</option>';
    }
    if (!$found) { // value on the employee that D365 no longer has
        $html = '<option value="' . htmlspecialchars($selected, ENT_QUOTES, 'UTF-8') . '" selected>' . htmlspecialchars($selected, ENT_QUOTES, 'UTF-8') . '</option>' . $html;
    }
    return $html;
}

/** "C30 - الاداريين" for display (just the code when D365 has no name for it) */
function cost_center_label($conDB, $value)
{
    $value = (string)($value ?? '') !== '' ? (string)$value : COST_CENTER_DEFAULT;
    foreach (cost_center_list($conDB) as $cc) {
        if ($cc['value'] === $value) {
            return $value . ($cc['name'] !== '' ? ' - ' . $cc['name'] : '') . ($cc['active'] ? '' : ' (suspended in D365)');
        }
    }
    return $value;
}

/** Normalise a posted cost center: known value, else the default */
function cost_center_clean($conDB, $value)
{
    $value = strtoupper(trim((string)$value));
    if ($value === '') {
        return COST_CENTER_DEFAULT;
    }
    foreach (cost_center_list($conDB) as $cc) {
        if (strtoupper($cc['value']) === $value) {
            return $cc['value'];
        }
    }
    return COST_CENTER_DEFAULT;
}
