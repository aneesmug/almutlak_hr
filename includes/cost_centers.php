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
