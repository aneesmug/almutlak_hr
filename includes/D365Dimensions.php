<?php
/**
 * D365 account templates per company + financial dimension values per employee.
 *
 * Every D365 company books payroll with its own dimensions (account structures differ), e.g.
 *   MHO => MainAccount, CostCenter, Department
 *   MTL => MainAccount, Company, Department
 *   MFF => MainAccount, Branch, Department
 * The template (App Settings > D365 Config > Account Templates) lists the dimensions a company uses;
 * the values are set per employee (App Settings > D365 Config > Employee Dimensions). Payroll lines
 * of an employee whose payroll company has a template are built ONLY from these stored values -
 * nothing is guessed from the D365 employment - and a missing value stops that employee's sync
 * with a clear message instead of sending a wrong account.
 *
 * MainAccount is implicit (comes from the payroll account mapping). Worker is filled with the
 * employee ID. The order inside AccountDisplayValue always follows the active D365 ledger dimension
 * format, not the template order.
 *
 * Tables:
 *   d365_account_templates (company, position, dimension, default_value)
 *   d365_employee_dimensions (emp_id, dimension, value)
 */
class D365Dimensions
{
    /** Dimensions filled automatically (never typed per employee) */
    const AUTO = ['worker'];

    public static function ensureTables(mysqli $db)
    {
        static $done = false;
        if ($done) {
            return;
        }
        $db->query("CREATE TABLE IF NOT EXISTS d365_account_templates (
            company VARCHAR(10) NOT NULL,
            position TINYINT UNSIGNED NOT NULL DEFAULT 0,
            dimension VARCHAR(40) NOT NULL,
            default_value VARCHAR(40) NOT NULL DEFAULT '',
            updated_by VARCHAR(64) NULL,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (company, dimension)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $db->query("CREATE TABLE IF NOT EXISTS d365_employee_dimensions (
            emp_id VARCHAR(30) NOT NULL,
            dimension VARCHAR(40) NOT NULL,
            value VARCHAR(40) NOT NULL,
            updated_by VARCHAR(64) NULL,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (emp_id, dimension)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $done = true;
    }

    public static function isAuto($dimension)
    {
        return in_array(strtolower((string)$dimension), self::AUTO, true);
    }

    // ---------------------------------------------------------------- templates

    /** ['MHO' => [['dimension' => 'CostCenter', 'default' => ''], ...], ...] in template order */
    public static function getTemplates(mysqli $db)
    {
        self::ensureTables($db);
        $out = [];
        $res = $db->query("SELECT company, dimension, default_value FROM d365_account_templates ORDER BY company, position, dimension");
        while ($res && ($r = $res->fetch_assoc())) {
            $out[$r['company']][] = ['dimension' => $r['dimension'], 'default' => $r['default_value']];
        }
        return $out;
    }

    /** Template of one company, [] when none */
    public static function getTemplate(mysqli $db, $company)
    {
        return self::getTemplates($db)[strtoupper(trim((string)$company))] ?? [];
    }

    /** Replace a company's template. $dims = [['dimension' => 'Department', 'default' => ''], ...]; empty list deletes it */
    public static function saveTemplate(mysqli $db, $company, array $dims, $userId)
    {
        self::ensureTables($db);
        $company = strtoupper(trim((string)$company));
        if (!preg_match('/^[A-Z0-9_]{1,10}$/', $company)) {
            throw new InvalidArgumentException('Invalid company');
        }
        $db->begin_transaction();
        try {
            $del = $db->prepare("DELETE FROM d365_account_templates WHERE company = ?");
            $del->bind_param('s', $company);
            $del->execute();
            $del->close();
            $ins = $db->prepare("INSERT INTO d365_account_templates (company, position, dimension, default_value, updated_by) VALUES (?, ?, ?, ?, ?)");
            $seen = [];
            $pos = 0;
            foreach ($dims as $d) {
                $name = trim((string)($d['dimension'] ?? ''));
                if (!preg_match('/^[A-Za-z0-9_]{1,40}$/', $name) || strtolower($name) === 'mainaccount' || isset($seen[strtolower($name)])) {
                    continue;
                }
                $seen[strtolower($name)] = true;
                $default = self::isAuto($name) ? '' : self::cleanValue($d['default'] ?? '');
                $pos++;
                $ins->bind_param('sisss', $company, $pos, $name, $default, $userId);
                $ins->execute();
            }
            $ins->close();
            $db->commit();
        } catch (Throwable $ex) {
            $db->rollback();
            throw $ex;
        }
    }

    // ---------------------------------------------------------------- employee values

    public static function cleanValue($value)
    {
        $value = trim((string)$value);
        if ($value !== '' && !preg_match('/^[\p{L}\p{N}_.\/ ]{1,40}$/u', $value)) {
            throw new InvalidArgumentException("Invalid dimension value \"$value\" (no dashes - D365 uses '-' as separator)");
        }
        return $value;
    }

    /** emp_id => [dimension => value]; $empIds null = everyone */
    public static function getEmployeeValues(mysqli $db, array $empIds = null)
    {
        self::ensureTables($db);
        $out = [];
        if ($empIds !== null) {
            $empIds = array_values(array_unique(array_map('strval', $empIds)));
            if (!$empIds) {
                return [];
            }
            $in = implode(',', array_fill(0, count($empIds), '?'));
            $stmt = $db->prepare("SELECT emp_id, dimension, value FROM d365_employee_dimensions WHERE emp_id IN ($in)");
            $stmt->bind_param(str_repeat('s', count($empIds)), ...$empIds);
            $stmt->execute();
            $res = $stmt->get_result();
        } else {
            $res = $db->query("SELECT emp_id, dimension, value FROM d365_employee_dimensions");
        }
        while ($res && ($r = $res->fetch_assoc())) {
            if (strtolower($r['dimension']) !== 'costcenter') {
                $out[$r['emp_id']][$r['dimension']] = $r['value'];
            }
        }
        if (isset($stmt)) {
            $stmt->close();
        }
        // CostCenter lives in employees.cost_center (Edit Employee has its own select) - one source only
        $cc = @$db->query("SHOW COLUMNS FROM employees LIKE 'cost_center'");
        if ($cc && $cc->num_rows > 0) {
            if ($empIds !== null) {
                $in = implode(',', array_fill(0, count($empIds), '?'));
                $stmt = $db->prepare("SELECT emp_id, cost_center FROM employees WHERE emp_id IN ($in)");
                $stmt->bind_param(str_repeat('s', count($empIds)), ...$empIds);
                $stmt->execute();
                $res = $stmt->get_result();
            } else {
                $res = $db->query("SELECT emp_id, cost_center FROM employees");
            }
            while ($res && ($r = $res->fetch_row())) {
                if (trim((string)$r[1]) !== '') {
                    $out[$r[0]]['CostCenter'] = trim((string)$r[1]);
                }
            }
            if ($empIds !== null) {
                $stmt->close();
            }
        }
        return $out;
    }

    /** Save [dimension => value] for one employee ('' removes the value). CostCenter is mirrored to employees.cost_center. */
    public static function saveEmployeeValues(mysqli $db, $empId, array $values, $userId)
    {
        self::ensureTables($db);
        $empId = (string)$empId;
        $up = $db->prepare("INSERT INTO d365_employee_dimensions (emp_id, dimension, value, updated_by) VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE value = VALUES(value), updated_by = VALUES(updated_by)");
        $del = $db->prepare("DELETE FROM d365_employee_dimensions WHERE emp_id = ? AND dimension = ?");
        foreach ($values as $dim => $value) {
            $dim = trim((string)$dim);
            if (!preg_match('/^[A-Za-z0-9_]{1,40}$/', $dim) || self::isAuto($dim)) {
                continue;
            }
            $value = self::cleanValue($value);
            if (strtolower($dim) === 'costcenter') {
                // stored on the employee (see getEmployeeValues); blank keeps the current value
                if ($value !== '') {
                    $cc = $db->prepare("UPDATE employees SET cost_center = ? WHERE emp_id = ?");
                    if ($cc) {
                        $cc->bind_param('ss', $value, $empId);
                        $cc->execute();
                        $cc->close();
                    }
                }
            } elseif ($value === '') {
                $del->bind_param('ss', $empId, $dim);
                $del->execute();
            } else {
                $up->bind_param('ssss', $empId, $dim, $value, $userId);
                $up->execute();
            }
        }
        $up->close();
        $del->close();
    }

    /**
     * Values of a company template for one employee:
     * ['values' => [dimension => value], 'missing' => [dimension, ...]]
     * Order of lookup: employee value, template default; Worker = emp_id.
     */
    public static function resolve(array $template, $empId, array $stored)
    {
        $values = [];
        $missing = [];
        foreach ($template as $t) {
            $dim = $t['dimension'];
            if (self::isAuto($dim)) {
                $values[$dim] = (string)$empId;
                continue;
            }
            $v = trim((string)($stored[$dim] ?? ''));
            if ($v === '') {
                $v = trim((string)($t['default'] ?? ''));
            }
            if ($v === '') {
                $missing[] = $dim;
            }
            $values[$dim] = $v;
        }
        return ['values' => $values, 'missing' => $missing];
    }

    // ---------------------------------------------------------------- D365 lookups

    /** Values of one financial dimension from D365 (cached 6h): [['value' => 'IT', 'name' => '...', 'active' => bool], ...] */
    public static function dimensionValues(D365Client $client, $dimension, $refresh = false)
    {
        $dimension = (string)$dimension;
        $file = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'd365_dimvals_' . md5($client->getResourceUrl() . '|' . strtolower($dimension)) . '.json';
        $cached = is_file($file) ? json_decode((string)@file_get_contents($file), true) : null;
        if (!$refresh && is_array($cached) && time() - filemtime($file) < 21600) {
            return $cached;
        }
        $r = $client->getAll('FinancialDimensionValues', [
            '$filter' => "FinancialDimension eq '" . str_replace("'", "''", $dimension) . "'",
            '$select' => 'DimensionValue,Description,IsSuspended',
        ]);
        if ($r['error']) {
            if (is_array($cached)) {
                return $cached; // D365 unreachable - last known list
            }
            throw new RuntimeException("D365 values of $dimension: " . $r['error']);
        }
        $list = [];
        foreach ($r['data']['value'] ?? [] as $v) {
            $list[] = ['value' => (string)$v['DimensionValue'], 'name' => (string)($v['Description'] ?? ''), 'active' => ($v['IsSuspended'] ?? 'No') !== 'Yes'];
        }
        usort($list, function ($a, $b) { return strnatcasecmp($a['value'], $b['value']); });
        @file_put_contents($file, json_encode($list, JSON_UNESCAPED_UNICODE), LOCK_EX);
        return $list;
    }
}
