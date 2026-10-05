<?php
/**
 * HR app payroll -> Dynamics 365 F&O general journal.
 *
 * One UNPOSTED general journal per environment + month + legal entity. Finance reviews and posts it in D365.
 * Per employee (all lines go in one $batch changeset, so an employee is pushed completely or not at all):
 *   Debit  each salary component (basic, housing, ...)   -> mapped expense account
 *   Debit  benefits (overtime, other income, ...)        -> keyword rules / default benefit account
 *   Credit deductions (GOSI, loan, absence, ...)         -> keyword rules / default deduction account
 *   Credit net salary                                    -> net salary payable account
 *   +/-    any difference (net_salary is rounded)        -> rounding account
 * Totals come from `payrolls`; benefit/deduction items are only itemized when they add up to the payroll total (within 1.00).
 * Company and financial dimensions come from the worker's current Employment in D365.
 */
require_once __DIR__ . '/D365Client.php';

class D365Payroll
{
    const COMPONENTS = [
        'basic_salary'            => 'Basic salary',
        'housing_allowance'       => 'Housing allowance',
        'transport_allowance'     => 'Transport allowance',
        'food_allowance'          => 'Food allowance',
        'miscellaneous_allowance' => 'Miscellaneous allowance',
        'cashier_allowance'       => 'Cashier allowance',
        'fuel_allowance'          => 'Fuel allowance',
        'telephone_allowance'     => 'Telephone allowance',
        'other_allowance'         => 'Other allowance',
        'guard_allowance'         => 'Guard allowance',
    ];

    /**
     * Defaults copied from finance's own monthly payroll entry in D365 ("قيد الرواتب لشهر 2026/06",
     * journal BN-012973, MHO, journal name GRN_JRN) so syncing works without typing any account.
     * Deductions other than GOSI/loans reduce salary expense (51010101) - confirm with finance.
     */
    const DEFAULT_SETTINGS = [
        'currency'           => 'SAR',
        'dimension_mode'     => 'employment', // employment = append the worker's D365 employment dimensions to the ledger account
        // Finance: every payroll earning (basic, allowances, overtime, other income) is booked on 51010101
        // in every company; the component name stays in the line text
        'accounts'           => [             // component column => main account
            'basic_salary'            => '51010101', // مرتبات
            'housing_allowance'       => '51010101',
            'transport_allowance'     => '51010101',
            'food_allowance'          => '51010101',
            'miscellaneous_allowance' => '51010101',
            'cashier_allowance'       => '51010101',
            'fuel_allowance'          => '51010101',
            'telephone_allowance'     => '51010101',
            'other_allowance'         => '51010101',
            'guard_allowance'         => '51010101',
        ],
        'benefit_default'    => '51010101',
        'benefit_rules'      => '',
        'deduction_default'  => '51010101',
        'deduction_rules'    => "gosi = 21070111\nloan = 11014102",
        'net_payable'        => '21070102', // رواتب مستحقة
        'rounding'           => '51010101',
    ];

    private $db;
    private $client;

    public function __construct(mysqli $db, D365Client $client = null)
    {
        $this->db = $db;
        $this->client = $client;
        // includes/db.php drops idle connections after 15s, but this class waits on slow D365 calls
        // (employments, journal creation, $batch) between queries - without this MySQL "goes away"
        // and every later prepare() returns false.
        $db->query("SET SESSION wait_timeout = 600, SESSION interactive_timeout = 600");
        self::ensureTables($db);
    }

    /** prepare() that fails loudly with the MySQL error instead of "bind_param() on bool" */
    private function prepare($sql)
    {
        $stmt = $this->db->prepare($sql);
        if ($stmt === false) {
            throw new RuntimeException('Database error: ' . ($this->db->error ?: 'connection lost'));
        }
        return $stmt;
    }

    public static function ensureTables(mysqli $db)
    {
        static $done = false;
        if ($done) {
            return;
        }
        $db->query("CREATE TABLE IF NOT EXISTS d365_payroll_settings (
            setting_key VARCHAR(64) NOT NULL PRIMARY KEY,
            setting_value MEDIUMTEXT NULL,
            updated_by VARCHAR(64) NULL,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $db->query("CREATE TABLE IF NOT EXISTS d365_payroll_journals (
            id INT AUTO_INCREMENT PRIMARY KEY,
            environment VARCHAR(20) NOT NULL,
            month_year VARCHAR(7) NOT NULL,
            legal_entity VARCHAR(10) NOT NULL,
            journal_batch VARCHAR(40) NOT NULL,
            journal_name VARCHAR(20) NOT NULL,
            next_line INT NOT NULL DEFAULT 1,
            created_by VARCHAR(64) NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_env_month_entity (environment, month_year, legal_entity)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $db->query("CREATE TABLE IF NOT EXISTS d365_payroll_push_log (
            id INT AUTO_INCREMENT PRIMARY KEY,
            environment VARCHAR(20) NOT NULL,
            month_year VARCHAR(7) NOT NULL,
            legal_entity VARCHAR(10) NOT NULL,
            journal_batch VARCHAR(40) NULL,
            emp_id VARCHAR(30) NOT NULL,
            line_count INT NOT NULL DEFAULT 0,
            amount DECIMAL(12,2) NOT NULL DEFAULT 0,
            status VARCHAR(10) NOT NULL,
            error TEXT NULL,
            pushed_by VARCHAR(64) NULL,
            pushed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            KEY idx_env_month_emp (environment, month_year, emp_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $done = true;
    }

    // ---------------------------------------------------------------- settings

    /** @var D365AccountRules|null account structures of the connected D365 (loaded with the dimension formats) */
    private static $accountRules = null;

    /** Company part of a journal key ("MTL/02" -> "MTL") */
    public static function keyCompany($key)
    {
        return strtoupper(explode('/', (string)$key, 2)[0]);
    }

    /** Payroll company of an employee: employees.payroll_company (Employee Master), else the D365 employment company */
    public function payrollCompanyFor($empId, $employmentCompany)
    {
        static $hasColumn = null;
        if ($hasColumn === null) {
            $res = $this->db->query("SHOW COLUMNS FROM employees LIKE 'payroll_company'");
            $hasColumn = $res && $res->num_rows > 0;
        }
        if ($hasColumn) {
            $stmt = $this->prepare("SELECT payroll_company FROM employees WHERE emp_id = ?");
            $stmt->bind_param('s', $empId);
            $stmt->execute();
            $value = strtoupper(trim((string)($stmt->get_result()->fetch_row()[0] ?? '')));
            $stmt->close();
            if ($value !== '') {
                return $value;
            }
        }
        return strtoupper((string)$employmentCompany);
    }

    /**
     * Journal key for a company: "MTL/02" when D365 Config has a journal name for that Company dimension
     * value (MTL keeps 01-GV / 02-GV journals with journal control per division), else just the company.
     */
    public function journalKey($company, $dims)
    {
        $company = strtoupper((string)$company);
        $formats = self::$dimFormats ?: ($this->client ? self::fetchDimensionFormats($this->client) : ['default' => []]);
        $i = array_search('company', array_map('strtolower', $formats['default'] ?? []), true);
        $value = $i === false ? '' : trim(explode('-', (string)$dims)[$i] ?? '');
        if ($value !== '' && $this->client && $this->client->getJournalName($company . '/' . $value)) {
            return $company . '/' . $value;
        }
        return $company;
    }

    /**
     * Only one payroll sync per environment + month at a time (two admins / two tabs / a double click
     * would otherwise both see the same employees as pending and push them twice).
     */
    private function withMonthLock($environment, $month, callable $fn)
    {
        $name = 'd365pay_' . md5($environment . '|' . $month);
        $res = $this->db->query("SELECT GET_LOCK('" . $name . "', 120) AS l");
        $got = $res ? (int)($res->fetch_assoc()['l'] ?? 0) : 0;
        if ($got !== 1) {
            throw new RuntimeException("Another payroll sync for $month is still running - try again in a minute");
        }
        try {
            return $fn();
        } finally {
            $this->db->query("DO RELEASE_LOCK('" . $name . "')");
        }
    }

    /**
     * Payroll lines this app already has in D365 for the month, in ANY company and journal (posted or not),
     * found by their text "PAY yyyy-mm <emp> ...". Returns emp_id => ['entity', 'journal', 'lines'].
     * Throws when D365 cannot be checked - nothing is sent without the check.
     */
    public function findExistingInD365($month, array $empIds)
    {
        $found = [];
        foreach (array_chunk($empIds, 10) as $part) {
            $or = implode(' or ', array_map(function ($id) use ($month) {
                return "Text eq 'PAY " . $month . ' ' . str_replace("'", "''", $id) . " *'";
            }, $part));
            $r = $this->client->getAll('LedgerJournalLines', ['$filter' => "($or)", '$select' => 'dataAreaId,JournalBatchNumber,Text'], true);
            if ($r['error']) {
                throw new RuntimeException('D365 duplicate check failed: ' . $r['error']);
            }
            foreach ($r['data']['value'] ?? [] as $l) {
                $p = explode(' ', (string)$l['Text']);
                $id = $p[2] ?? '';
                if (($p[1] ?? '') !== $month || !in_array($id, $part, true)) {
                    continue;
                }
                if (!isset($found[$id])) {
                    $found[$id] = ['entity' => strtoupper($l['dataAreaId']), 'journal' => $l['JournalBatchNumber'], 'lines' => 0];
                }
                $found[$id]['lines']++;
            }
        }
        return $found;
    }

    /**
     * The app's log says these months are synced: check D365 still has the payroll lines (they go when
     * someone deletes the journal or its lines). Months missing in D365 are marked "removed" so they can be
     * synced again. One D365 query per 10 months. Returns the months marked removed.
     */
    public function verifyEmployeePushed($environment, $empId)
    {
        $stmt = $this->prepare("SELECT DISTINCT month_year FROM d365_payroll_push_log WHERE environment = ? AND emp_id = ? AND status = 'ok'");
        $stmt->bind_param('ss', $environment, $empId);
        $stmt->execute();
        $months = array_column($stmt->get_result()->fetch_all(MYSQLI_ASSOC), 'month_year');
        $stmt->close();
        $found = [];
        foreach (array_chunk($months, 10) as $part) {
            $or = implode(' or ', array_map(function ($m) use ($empId) {
                return "Text eq 'PAY " . $m . ' ' . str_replace("'", "''", $empId) . " *'";
            }, $part));
            $r = $this->client->getAll('LedgerJournalLines', ['$filter' => "($or)", '$select' => 'Text'], true);
            if ($r['error']) {
                throw new RuntimeException('D365 check failed: ' . $r['error']);
            }
            foreach ($r['data']['value'] ?? [] as $l) {
                $p = explode(' ', (string)$l['Text']);
                if (($p[2] ?? '') === (string)$empId) {
                    $found[$p[1] ?? ''] = true;
                }
            }
        }
        $removed = array_values(array_filter($months, function ($m) use ($found) { return !isset($found[$m]); }));
        foreach ($removed as $m) {
            $this->markRemoved($environment, $m, $empId);
        }
        return $removed;
    }

    /** Log rows of an employee + month whose lines are no longer in D365: synced -> removed */
    private function markRemoved($environment, $month, $empId)
    {
        $stmt = $this->prepare("UPDATE d365_payroll_push_log SET status = 'removed', error = 'Payroll lines no longer in D365 (journal or lines deleted)'
            WHERE environment = ? AND month_year = ? AND emp_id = ? AND status = 'ok'");
        $stmt->bind_param('sss', $environment, $month, $empId);
        $stmt->execute();
        $stmt->close();
    }

    public function getSettings()
    {
        $settings = self::DEFAULT_SETTINGS;
        $res = $this->db->query("SELECT setting_value FROM d365_payroll_settings WHERE setting_key = 'payroll'");
        if ($res && ($row = $res->fetch_assoc())) {
            $saved = json_decode($row['setting_value'], true);
            if (is_array($saved)) {
                // A blank saved field falls back to the default instead of breaking the sync
                foreach ($saved as $k => $v) {
                    if ($k === 'accounts' && is_array($v)) {
                        foreach ($v as $col => $acc) {
                            if (trim((string)$acc) !== '') {
                                $settings['accounts'][$col] = $acc;
                            }
                        }
                    } elseif (is_string($v) && trim($v) !== '') {
                        $settings[$k] = $v;
                    }
                }
            }
        }
        return $settings;
    }

    public function saveSettings(array $settings, $userId)
    {
        $clean = self::DEFAULT_SETTINGS;
        foreach (['currency', 'benefit_default', 'deduction_default', 'net_payable', 'rounding'] as $k) {
            $clean[$k] = trim((string)($settings[$k] ?? ''));
        }
        $clean['dimension_mode'] = ($settings['dimension_mode'] ?? '') === 'none' ? 'none' : 'employment';
        $clean['benefit_rules'] = trim((string)($settings['benefit_rules'] ?? ''));
        $clean['deduction_rules'] = trim((string)($settings['deduction_rules'] ?? ''));
        foreach (self::COMPONENTS as $col => $label) {
            $clean['accounts'][$col] = trim((string)($settings['accounts'][$col] ?? ''));
        }
        $json = json_encode($clean, JSON_UNESCAPED_UNICODE);
        $stmt = $this->prepare("INSERT INTO d365_payroll_settings (setting_key, setting_value, updated_by) VALUES ('payroll', ?, ?)
            ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_by = VALUES(updated_by)");
        $stmt->bind_param('ss', $json, $userId);
        $stmt->execute();
        $stmt->close();
        return $clean;
    }

    /** "keyword = account" lines -> [[keyword, account], ...] (first match wins) */
    public static function parseRules($text)
    {
        $rules = [];
        foreach (preg_split('/\r?\n/', (string)$text) as $line) {
            if (strpos($line, '=') === false) {
                continue;
            }
            [$kw, $acc] = array_map('trim', explode('=', $line, 2));
            if ($kw !== '' && $acc !== '') {
                $rules[] = [mb_strtolower($kw), $acc];
            }
        }
        return $rules;
    }

    private static function matchAccount($name, array $rules, $default)
    {
        $name = mb_strtolower((string)$name);
        foreach ($rules as [$kw, $acc]) {
            if ($kw !== '' && mb_strpos($name, $kw) !== false) {
                return $acc;
            }
        }
        return $default;
    }

    // ---------------------------------------------------------------- data

    public function getMonths()
    {
        $months = [];
        $res = $this->db->query("SELECT month_year, COUNT(*) n, SUM(net_salary) net FROM payrolls GROUP BY month_year ORDER BY month_year DESC LIMIT 24");
        while ($res && ($r = $res->fetch_assoc())) {
            $months[] = $r;
        }
        return $months;
    }

    public function getApprovalStatus($month)
    {
        $stmt = $this->prepare("SELECT status FROM payroll_approval_requests WHERE payroll_month = ? ORDER BY id DESC LIMIT 1");
        $stmt->bind_param('s', $month);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row['status'] ?? null;
    }

    /** payroll rows for a month, keyed by emp_id, with itemized benefits/deductions */
    public function getPayrolls($month, array $empIds = null)
    {
        $sql = "SELECT p.*, e.name FROM payrolls p LEFT JOIN employees e ON e.emp_id = p.emp_id WHERE p.month_year = ?";
        $types = 's';
        $params = [$month];
        if ($empIds !== null) {
            if (!$empIds) {
                return [];
            }
            $sql .= ' AND p.emp_id IN (' . implode(',', array_fill(0, count($empIds), '?')) . ')';
            $types .= str_repeat('s', count($empIds));
            $params = array_merge($params, array_values($empIds));
        }
        $sql .= ' ORDER BY CAST(p.emp_id AS UNSIGNED), p.emp_id';
        $stmt = $this->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $rows = [];
        $res = $stmt->get_result();
        while ($r = $res->fetch_assoc()) {
            $r['benefit_items'] = [];
            $r['deduction_items'] = [];
            $rows[$r['emp_id']] = $r;
        }
        $stmt->close();
        if (!$rows) {
            return [];
        }

        foreach (['payroll_benefits' => ['benefit', 'benefit_items'], 'payroll_deductions' => ['deduction', 'deduction_items']] as $table => [$nameCol, $key]) {
            $stmt = $this->prepare("SELECT emp_id, $nameCol AS item, note FROM $table WHERE month = ?");
            $stmt->bind_param('s', $month);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($r = $res->fetch_assoc()) {
                if (isset($rows[$r['emp_id']]) && is_numeric($r['note'])) {
                    $rows[$r['emp_id']][$key][] = ['name' => $r['item'], 'amount' => (float)$r['note']];
                }
            }
            $stmt->close();
        }
        return $rows;
    }

    /**
     * Current D365 employment per personnel number: ['5430' => ['entity' => 'MHO', 'dims' => '---5430----'], ...]
     */
    public function getEmployments($personnelNumber = null)
    {
        $query = ['$select' => 'PersonnelNumber,LegalEntityId,EmploymentStartDate,EmploymentEndDate,DimensionDisplayValue'];
        if ($personnelNumber !== null) {
            $query['$filter'] = "PersonnelNumber eq '" . str_replace("'", "''", $personnelNumber) . "'";
        }
        $res = $this->client->getAll('Employments', $query, true);
        if ($res['error']) {
            throw new RuntimeException('D365 Employments: ' . $res['error']);
        }
        $now = gmdate('Y-m-d\TH:i:s\Z');
        $map = [];
        foreach ($res['data']['value'] as $e) {
            $pn = (string)$e['PersonnelNumber'];
            $active = strcmp($e['EmploymentEndDate'], $now) > 0;
            $current = $map[$pn] ?? null;
            // Prefer an active employment, then the latest start date
            if ($current === null
                || ($active && !$current['active'])
                || ($active === $current['active'] && strcmp($e['EmploymentStartDate'], $current['start']) > 0)) {
                $map[$pn] = [
                    'entity' => strtoupper($e['LegalEntityId']),
                    'dims'   => (string)($e['DimensionDisplayValue'] ?? ''),
                    'start'  => $e['EmploymentStartDate'],
                    'end'    => $e['EmploymentEndDate'],
                    'active' => $active,
                ];
            }
        }
        return $map;
    }

    /** emp_id => last successful push row for this environment + month */
    public function getPushedMap($environment, $month)
    {
        $stmt = $this->prepare("SELECT emp_id, legal_entity, journal_batch, amount, pushed_at FROM d365_payroll_push_log
            WHERE environment = ? AND month_year = ? AND status = 'ok' ORDER BY id");
        $stmt->bind_param('ss', $environment, $month);
        $stmt->execute();
        $map = [];
        $res = $stmt->get_result();
        while ($r = $res->fetch_assoc()) {
            $map[$r['emp_id']] = $r;
        }
        $stmt->close();
        return $map;
    }

    /**
     * month => latest push-log row of one employee in this environment (local DB only, no D365 call).
     * Used by the employee master Payrolls tab: no row / 'error' / 'removed' = still to sync.
     */
    public function getEmployeeSyncState($environment, $empId)
    {
        $stmt = $this->prepare("SELECT month_year, legal_entity, journal_batch, amount, status, error, pushed_at
            FROM d365_payroll_push_log WHERE environment = ? AND emp_id = ? ORDER BY id");
        $stmt->bind_param('ss', $environment, $empId);
        $stmt->execute();
        $map = [];
        $res = $stmt->get_result();
        while ($r = $res->fetch_assoc()) {
            // a successful push stays the answer until its journal is removed in D365
            if (($map[$r['month_year']]['status'] ?? '') === 'ok' && $r['status'] === 'error') {
                continue;
            }
            $map[$r['month_year']] = $r;
        }
        $stmt->close();
        return $map;
    }

    public function getEmployeeHistory($empId, $limit = 6)
    {
        $stmt = $this->prepare("SELECT month_year, basic_salary, housing_allowance, transport_allowance, total_gross_salary, total_benefits, total_deductions, net_salary, status
            FROM payrolls WHERE emp_id = ? ORDER BY month_year DESC LIMIT ?");
        $stmt->bind_param('si', $empId, $limit);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        $stmt = $this->prepare("SELECT environment, month_year, legal_entity, journal_batch, line_count, amount, status, error, pushed_at
            FROM d365_payroll_push_log WHERE emp_id = ? ORDER BY id DESC LIMIT 20");
        $stmt->bind_param('s', $empId);
        $stmt->execute();
        $log = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return [$rows, $log];
    }

    // ---------------------------------------------------------------- journal lines

    /**
     * Build balanced journal lines for one employee.
     * Returns ['lines' => [[account, debit, credit, text], ...], 'errors' => [...], 'warnings' => [...], 'total' => debit total]
     */
    public static function buildLines(array $p, array $settings, $month)
    {
        $lines = [];
        $errors = [];
        $warnings = [];
        $emp = $p['emp_id'];
        $add = function ($account, $amount, $side, $label) use (&$lines, &$errors, $emp, $month) {
            $amount = round((float)$amount, 2);
            if (abs($amount) < 0.005) {
                return;
            }
            if ($account === '') {
                $errors[] = "No D365 account mapped for \"$label\"";
                return;
            }
            if ($amount < 0) { // negative amount -> other side
                $amount = -$amount;
                $side = $side === 'D' ? 'C' : 'D';
            }
            $lines[] = [
                'account' => $account,
                'debit'   => $side === 'D' ? $amount : 0.0,
                'credit'  => $side === 'C' ? $amount : 0.0,
                'text'    => mb_substr("PAY $month $emp $label", 0, 60),
            ];
        };

        foreach (self::COMPONENTS as $col => $label) {
            $add($settings['accounts'][$col] ?? '', $p[$col] ?? 0, 'D', $label);
        }

        // Benefits / deductions: itemize only when the items add up to the payroll total
        foreach ([
            ['total_benefits', 'benefit_items', 'benefit_rules', 'benefit_default', 'D', 'Benefits'],
            ['total_deductions', 'deduction_items', 'deduction_rules', 'deduction_default', 'C', 'Deductions'],
        ] as [$totalCol, $itemsKey, $rulesKey, $defaultKey, $side, $label]) {
            $total = round((float)($p[$totalCol] ?? 0), 2);
            if (abs($total) < 0.005) {
                continue;
            }
            $items = $p[$itemsKey] ?? [];
            $itemSum = round(array_sum(array_column($items, 'amount')), 2);
            // Items are often rounded per line (2244.16 vs 2244.00) - small gaps end up on the rounding line
            if ($items && abs($itemSum - $total) < 1.0) {
                $rules = self::parseRules($settings[$rulesKey] ?? '');
                $grouped = [];
                foreach ($items as $it) {
                    $acc = self::matchAccount($it['name'], $rules, $settings[$defaultKey] ?? '');
                    $grouped[$acc][] = $it;
                }
                foreach ($grouped as $acc => $its) {
                    $name = count($its) === 1 ? trim(preg_replace('/\s*\(.*$/', '', $its[0]['name'])) : $label;
                    $add((string)$acc, array_sum(array_column($its, 'amount')), $side, $name !== '' ? $name : $label);
                }
            } else {
                if ($items) {
                    $warnings[] = "$label items (" . number_format($itemSum, 2) . ") do not match total (" . number_format($total, 2) . ") - posted as one line";
                }
                $add($settings[$defaultKey] ?? '', $total, $side, $label);
            }
        }

        $add($settings['net_payable'] ?? '', $p['net_salary'] ?? 0, 'C', 'Net salary');

        $debit = round(array_sum(array_column($lines, 'debit')), 2);
        $credit = round(array_sum(array_column($lines, 'credit')), 2);
        $diff = round($debit - $credit, 2);
        if (abs($diff) >= 0.005 && !$errors) {
            if (abs($diff) > 1) {
                $warnings[] = 'Difference of ' . number_format($diff, 2) . ' between debits and credits sent to rounding account';
            }
            $add($settings['rounding'] ?? '', $diff, 'C', 'Rounding');
            $debit = round(array_sum(array_column($lines, 'debit')), 2);
        }

        return ['lines' => $lines, 'errors' => $errors, 'warnings' => $warnings, 'total' => $debit];
    }

    /**
     * Employment default dimensions use 8 segments ("--10-1872-JD---", "---5430----") while the ledger
     * account structure has main account + 5 ("51040302--81--JD-"): segments 2-6 of the employment value
     * line up with the ledger dimensions (verified against existing journal lines, e.g. 11014102---5430--).
     */
    public static function ledgerAccount($mainAccount, $dims, array $settings, array $extra = [], array $allowed = null)
    {
        if (($settings['dimension_mode'] ?? 'employment') !== 'employment' || trim($dims, '-') === '') {
            return $mainAccount;
        }
        $segments = explode('-', $dims);

        // Preferred: follow the active "formats for data entities" of the connected D365 (they differ
        // between environments) - each ledger segment takes the employment value of the same dimension name
        $f = self::$dimFormats;
        if ($f && $f['default'] && $f['ledger']) {
            $byName = [];
            foreach ($f['default'] as $i => $name) {
                $byName[strtolower($name)] = $segments[$i] ?? '';
            }
            // values that only the ledger format has (e.g. CostCenter while the default format lacks it)
            foreach ($extra as $extraName => $extraValue) {
                if (($byName[strtolower($extraName)] ?? '') === '') {
                    $byName[strtolower($extraName)] = (string)$extraValue;
                }
            }
            $out = [];
            $hasMain = false;
            foreach ($f['ledger'] as $name) {
                if (strtolower($name) === 'mainaccount') {
                    $out[] = $mainAccount;
                    $hasMain = true;
                } elseif ($allowed !== null && !in_array(strtolower($name), $allowed, true)) {
                    $out[] = ''; // not in this company's account structure for this main account
                } else {
                    $out[] = $byName[strtolower($name)] ?? '';
                }
            }
            if (!$hasMain) {
                array_unshift($out, $mainAccount);
            }
            return implode('-', $out);
        }

        if (count($segments) > 5) {
            $segments = array_slice($segments, 1, 5);
        }
        return $mainAccount . '-' . implode('-', $segments);
    }

    /** Active D365 dimension formats for data entities: ['default' => [names], 'ledger' => [names]] */
    private static $dimFormats = null;

    /**
     * Load the connected D365's active default + ledger dimension formats (cached 6h in the temp dir).
     * Throws when the ledger format cannot hold the worker dimension, so nothing is booked without it.
     */
    /** Active default + ledger dimension formats of a D365 environment (cached 6h in the temp dir) */
    public static function fetchDimensionFormats(D365Client $client)
    {
        $file = self::dimensionFormatFile($client);
        $formats = (is_file($file) && time() - filemtime($file) < 900) ? json_decode((string)file_get_contents($file), true) : null;
        if ($formats) {
            return $formats;
        }
        $r = $client->get('DimensionIntegrationFormats', []);
        if ($r['error']) {
            throw new RuntimeException('D365 dimension formats: ' . $r['error']);
        }
        $formats = ['default' => [], 'ledger' => []];
        foreach ($r['data']['value'] ?? [] as $row) {
            if (($row['IsActive'] ?? '') !== 'Yes') {
                continue;
            }
            $names = array_values(array_filter(explode('-', (string)$row['FinancialDimensionFormat']), 'strlen'));
            if ($row['DimensionFormatType'] === 'DataEntityDefaultDimensionFormat') {
                $formats['default'] = $names;
            } elseif ($row['DimensionFormatType'] === 'DataEntityLedgerDimensionFormat') {
                $formats['ledger'] = $names;
            }
        }
        @file_put_contents($file, json_encode($formats), LOCK_EX);
        return $formats;
    }

    private static function dimensionFormatFile(D365Client $client)
    {
        return sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'd365_dimfmt_' . md5($client->getResourceUrl()) . '.json';
    }

    /**
     * Employment dims with the Department segment filled when it is blank (an existing D365 value is never replaced).
     * $dims / result are in the default dimension format, e.g. "---5430----" + IT -> "--IT-5430----".
     */
    public static function withDepartment($dims, $department, array $defaultFormat)
    {
        return self::withDimension($dims, 'Department', $department, $defaultFormat, true);
    }

    /**
     * Set one named dimension in a default-format dims string. $onlyIfBlank keeps an existing value.
     * Returns the dims unchanged when the value is empty or the format has no such dimension.
     */
    public static function withDimension($dims, $name, $value, array $defaultFormat, $onlyIfBlank = false)
    {
        $value = trim((string)$value);
        if ($value === '') {
            return $dims;
        }
        $names = $defaultFormat ?: ['MainAccount', 'Company', 'Department', 'Worker', 'Branch', 'Customer', 'FixedAsset', 'LC'];
        $i = array_search(strtolower($name), array_map('strtolower', $names), true);
        if ($i === false) {
            return $dims;
        }
        $segments = array_pad(explode('-', (string)$dims), count($names), '');
        if ($onlyIfBlank && trim($segments[$i]) !== '') {
            return $dims;
        }
        $segments[$i] = $value;
        return implode('-', $segments);
    }

    /** Does the active D365 default dimension format contain this dimension? */
    public static function formatHas(array $formats, $name, $type = 'default')
    {
        return in_array(strtolower($name), array_map('strtolower', $formats[$type] ?? []), true);
    }

    /** @var D365Workers|null */
    private $workersHelper = null;

    /** Employment dims with a blank Department filled from the app department (see D365Workers::departmentFor) */
    private function dimsWithDepartment($empId, $dims, array $employments = null)
    {
        if (!$this->client || trim((string)$dims, '-') === '') {
            return $dims;
        }
        try {
            if (!$this->workersHelper) {
                require_once __DIR__ . '/D365Workers.php';
                $this->workersHelper = new D365Workers($this->db, $this->client);
            }
            $formats = self::$dimFormats ?: self::fetchDimensionFormats($this->client);
            $dims = self::withDepartment($dims, $this->workersHelper->departmentFor($empId, $employments), $formats['default'] ?? []);
            // Cost center from the HR app (used on the line once the D365 formats contain CostCenter)
            return self::withDimension($dims, 'CostCenter', $this->workersHelper->appCostCenter($empId), $formats['default'] ?? []);
        } catch (Throwable $ex) {
            return $dims; // never block the payroll line on the department lookup
        }
    }

    /** Ledger-only dimension values from the HR app: ['CostCenter' => 'C30'] */
    private function extraLedgerValues($empId)
    {
        try {
            if (!$this->workersHelper) {
                require_once __DIR__ . '/D365Workers.php';
                $this->workersHelper = new D365Workers($this->db, $this->client);
            }
            $cc = $this->workersHelper->appCostCenter($empId);
            return $cc !== '' ? ['CostCenter' => $cc] : [];
        } catch (Throwable $ex) {
            return [];
        }
    }

    public function loadDimensionFormats()
    {
        if (!$this->client) {
            return null;
        }
        $file = self::dimensionFormatFile($this->client);
        $formats = self::fetchDimensionFormats($this->client);
        // Payroll lines must carry the worker. D365 ignores DefaultDimensionDisplayValue on ledger lines
        // (tested), so a ledger format without Worker (e.g. "FixedAsset") cannot be worked around here.
        if (!$formats['ledger'] || !in_array('worker', array_map('strtolower', $formats['ledger']), true)) {
            @unlink($file); // re-read on the next try, once fixed in D365
            throw new RuntimeException('D365 ' . $this->client->getEnvironment() . ': the active ledger dimension format for data entities is "'
                . implode('-', $formats['ledger']) . '", which has no Worker dimension, so payroll cannot be booked per employee. '
                . 'In D365 open General ledger > Chart of accounts > Dimensions > Financial dimension configuration for integrating applications, '
                . 'select "Ledger dimension format" and set the dimensions to MainAccount-Company-Department-Worker-Branch-Customer (same as the sandbox), then sync again.');
        }
        self::$dimFormats = $formats;
        require_once __DIR__ . '/D365AccountRules.php';
        self::$accountRules = new D365AccountRules($this->client);
        return $formats;
    }

    // ---------------------------------------------------------------- push

    /**
     * Sync one employee's payroll month (must be "paid" in the HR app) into D365.
     * Returns ['ok' => bool, 'error' => ?, 'journal' => ?, 'entity' => ?, 'lines' => n, 'amount' => x]
     */
    public function syncEmployeeMonth($empId, $month, $transDate, $userId)
    {
        $environment = $this->client->getEnvironment();
        if (!$this->client->canWrite()) {
            return ['ok' => false, 'error' => "Writes are disabled for the $environment environment (App Settings > D365 Config > Allow Writes)"];
        }
        try {
            return $this->withMonthLock($environment, $month, function () use ($empId, $month, $transDate, $userId, $environment) {
                return $this->doSyncEmployeeMonth($empId, $month, $transDate, $userId, $environment);
            });
        } catch (Throwable $ex) {
            return ['ok' => false, 'error' => $ex->getMessage()];
        }
    }

    private function doSyncEmployeeMonth($empId, $month, $transDate, $userId, $environment)
    {
        // Ledger accounts follow the active D365 dimension formats (see loadDimensionFormats)
        $this->loadDimensionFormats();
        $rows = $this->getPayrolls($month, [$empId]);
        $p = $rows[$empId] ?? null;
        if (!$p) {
            return ['ok' => false, 'error' => "No payroll for $empId in $month"];
        }
        if (strtolower((string)$p['status']) !== 'paid') {
            return ['ok' => false, 'error' => "Payroll $month is \"{$p['status']}\" - only paid payroll can be synced"];
        }
        // Duplicate guard against D365 itself - the source of truth (the app's log may miss lines after a
        // request died mid-way, or still say "synced" after the journal was deleted in D365)
        $pushed = $this->getPushedMap($environment, $month);
        $inD365 = $this->findExistingInD365($month, [$empId]);
        if (isset($inD365[$empId])) {
            $x = $inD365[$empId];
            if (!isset($pushed[$empId])) {
                $this->logPush($environment, $month, $x['entity'], $x['journal'], $empId, ['ok' => true, 'lines' => $x['lines'], 'amount' => 0], $userId);
            }
            return ['ok' => false, 'error' => "Already in D365 journal {$x['journal']} ({$x['entity']}) - not sent again"];
        }
        if (isset($pushed[$empId])) {
            $this->markRemoved($environment, $month, $empId); // logged as synced but gone from D365 - send again
        }

        // Failures before the push are logged too, so the employee's Payrolls tab can show them
        $fail = function ($error, $entity = '-') use ($environment, $month, $empId, $userId) {
            $result = ['ok' => false, 'error' => $error];
            $this->logPush($environment, $month, $entity, null, $empId, $result, $userId);
            return $result;
        };

        $settings = $this->getSettings();
        $built = self::buildLines($p, $settings, $month);
        if ($built['errors']) {
            return $fail(implode('; ', array_unique($built['errors'])) . ' - fill the account mapping on the Payroll settings page');
        }

        // Booked in the employee's payroll company (Employee Master; default = D365 employment company),
        // with the dims of the worker's own employment - only those the company's account structure accepts
        $employment = $this->getEmployments($empId)[$empId] ?? null;
        if (!$employment) {
            return $fail("Personnel number $empId has no employment in D365 - add the worker in D365 first");
        }
        $employment['dims'] = $this->dimsWithDepartment($empId, $employment['dims']);
        $employment['extra'] = $this->extraLedgerValues($empId);
        $employment['entity'] = $this->payrollCompanyFor($empId, $employment['entity']);
        $key = $this->journalKey($employment['entity'], $employment['dims']);

        try {
            $journal = $this->ensureJournal($environment, $month, $key, $settings, $userId);
        } catch (Throwable $ex) {
            return $fail($ex->getMessage(), $key);
        }
        $res = $this->pushEmployee($p, $employment, $journal, $settings, $environment, $month, $transDate, $userId);
        return $res + ['journal' => $journal['journal_batch'], 'entity' => $key];
    }

    /**
     * The app's open journal for env + month + entity, verified against D365. Returns the row while it is
     * still open there, or null once finance posted it (lines stay synced) or someone deleted it in D365
     * (its employees are marked "removed" so they can be synced again).
     */
    public function checkJournal($environment, $month, $entity)
    {
        $stmt = $this->prepare("SELECT * FROM d365_payroll_journals WHERE environment = ? AND month_year = ? AND legal_entity = ?");
        $stmt->bind_param('sss', $environment, $month, $entity);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$row) {
            return null;
        }

        $h = $this->client->get('LedgerJournalHeaders', [
            '$filter' => "JournalBatchNumber eq '" . str_replace("'", "''", $row['journal_batch']) . "' and dataAreaId eq '" . strtolower(self::keyCompany($entity)) . "'",
            '$select' => 'JournalBatchNumber,IsPosted',
        ], true);
        if ($h['error']) {
            throw new RuntimeException('D365 journal check failed: ' . $h['error']);
        }
        $header = $h['data']['value'][0] ?? null;
        if ($header && $header['IsPosted'] !== 'Yes') {
            return $row;
        }

        if (!$header) {
            $stmt = $this->prepare("UPDATE d365_payroll_push_log SET status = 'removed', error = 'Journal deleted in D365'
                WHERE environment = ? AND journal_batch = ? AND status = 'ok'");
            $stmt->bind_param('ss', $environment, $row['journal_batch']);
            $stmt->execute();
            $stmt->close();
        }
        $stmt = $this->prepare("DELETE FROM d365_payroll_journals WHERE id = ?");
        $stmt->bind_param('i', $row['id']);
        $stmt->execute();
        $stmt->close();
        return null;
    }

    /** Existing open or new D365 journal header for env + month + entity */
    public function ensureJournal($environment, $month, $entity, array $settings, $userId)
    {
        $row = $this->checkJournal($environment, $month, $entity);
        if ($row) {
            return $row;
        }

        // $entity is a journal key: company ("MSP") or company + Company dimension ("MTL/02")
        $journalName = $this->client->getJournalName($entity) ?: $this->client->getJournalName(self::keyCompany($entity));
        if (!$journalName) {
            throw new RuntimeException("No payroll journal name for company $entity - add it in App Settings > D365 Config (e.g. $entity=GEN)");
        }
        $res = $this->client->create('LedgerJournalHeaders', [
            'dataAreaId'  => strtolower(self::keyCompany($entity)),
            'JournalName' => $journalName,
            'Description' => "HR Payroll $month",
        ]);
        $batch = $res['data']['JournalBatchNumber'] ?? '';
        if ($res['error'] || $batch === '') {
            throw new RuntimeException("Could not create journal for $entity: " . ($res['error'] ?: 'no JournalBatchNumber returned'));
        }

        $stmt = $this->prepare("INSERT INTO d365_payroll_journals (environment, month_year, legal_entity, journal_batch, journal_name, created_by) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param('ssssss', $environment, $month, $entity, $batch, $journalName, $userId);
        $stmt->execute();
        $stmt->close();
        return ['journal_batch' => $batch, 'next_line' => 1, 'legal_entity' => $entity, 'journal_name' => $journalName];
    }

    /**
     * Push one employee's payroll lines into the journal. Returns ['ok' => bool, 'error' => ?, 'lines' => n, 'amount' => x]
     */
    public function pushEmployee(array $p, array $employment, array $journal, array $settings, $environment, $month, $transDate, $userId)
    {
        $built = self::buildLines($p, $settings, $month);
        $result = ['ok' => false, 'error' => null, 'lines' => count($built['lines']), 'amount' => $built['total']];

        if ($built['errors']) {
            $result['error'] = implode('; ', array_unique($built['errors']));
        } else {
            $res = $this->client->batchCreate('LedgerJournalLines', self::lineBodies($built, $employment, $journal, $settings, $transDate));
            if ($res['ok']) {
                $result['ok'] = true;
            } else {
                $result['error'] = $res['error'];
            }
        }

        $this->logPush($environment, $month, $journal['legal_entity'] ?? $employment['entity'], $journal['journal_batch'], $p['emp_id'], $result, $userId);
        return $result;
    }

    /** D365 LedgerJournalLines bodies for one employee (LineNumber is assigned by D365 - sending it is refused) */
    private static function lineBodies(array $built, array $employment, array $journal, array $settings, $transDate)
    {
        $bodies = [];
        $company = self::keyCompany($employment['entity']);
        foreach ($built['lines'] as $l) {
            // only the dimensions this company's account structure accepts for the main account
            $allowed = self::$accountRules ? self::$accountRules->allowedDimensions($company, $l['account']) : null;
            $body = [
                'dataAreaId'          => strtolower($company),
                'JournalBatchNumber'  => $journal['journal_batch'],
                'TransDate'           => $transDate . 'T12:00:00Z',
                'AccountType'         => 'Ledger',
                'AccountDisplayValue' => self::ledgerAccount($l['account'], $employment['dims'], $settings, $employment['extra'] ?? [], $allowed),
                'DebitAmount'         => $l['debit'],
                'CreditAmount'        => $l['credit'],
                'CurrencyCode'        => $settings['currency'] ?: 'SAR',
                'Text'                => $l['text'],
            ];
            $bodies[] = $body;
        }
        return $bodies;
    }

    private function logPush($environment, $month, $entity, $journalBatch, $empId, array $result, $userId)
    {
        $status = $result['ok'] ? 'ok' : 'error';
        $lines = (int)($result['lines'] ?? 0);
        $amount = (float)($result['amount'] ?? 0);
        $error = $result['error'] ?? null;
        $stmt = $this->prepare("INSERT INTO d365_payroll_push_log (environment, month_year, legal_entity, journal_batch, emp_id, line_count, amount, status, error, pushed_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param('sssssidsss', $environment, $month, $entity, $journalBatch, $empId, $lines, $amount, $status, $error, $userId);
        $stmt->execute();
        $stmt->close();
    }

    // ---------------------------------------------------------------- whole month

    /** Per-month counts for the month picker: employees, paid, already synced in this environment */
    public function getMonthSummary($environment, $limit = 12)
    {
        $stmt = $this->prepare("SELECT p.month_year, COUNT(*) total, SUM(LOWER(p.status) = 'paid') paid, ROUND(SUM(IF(LOWER(p.status) = 'paid', p.net_salary, 0)), 2) paid_net,
                (SELECT COUNT(DISTINCT l.emp_id) FROM d365_payroll_push_log l WHERE l.environment = ? AND l.month_year = p.month_year AND l.status = 'ok') synced
            FROM payrolls p GROUP BY p.month_year ORDER BY p.month_year DESC LIMIT ?");
        $stmt->bind_param('si', $environment, $limit);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $rows;
    }

    /** emp_ids with PAID payroll in $month that are not yet synced to this environment */
    public function getPendingEmpIds($environment, $month)
    {
        $stmt = $this->prepare("SELECT p.emp_id FROM payrolls p
            WHERE p.month_year = ? AND LOWER(p.status) = 'paid'
              AND NOT EXISTS (SELECT 1 FROM d365_payroll_push_log l WHERE l.environment = ? AND l.month_year = p.month_year AND l.emp_id = p.emp_id AND l.status = 'ok')
            ORDER BY CAST(p.emp_id AS UNSIGNED), p.emp_id");
        $stmt->bind_param('ss', $month, $environment);
        $stmt->execute();
        $ids = array_column($stmt->get_result()->fetch_all(MYSQLI_ASSOC), 'emp_id');
        $stmt->close();
        return $ids;
    }

    /**
     * Sync a chunk of employees of one month in a single D365 $batch (one changeset per employee).
     * $employments = getEmployments() map (fetched once by the caller and reused across chunks).
     * Each employee is booked in their own D365 company (from their employment), so one chunk can
     * use the payroll company's (MHO) journal of the month.
     * Returns ['journals' => [company => journal_batch], 'results' => [emp_id => ['ok', 'error', 'lines', 'amount', 'company']]].
     */
    public function syncMonthChunk($month, array $empIds, array $employments, $userId)
    {
        $environment = $this->client->getEnvironment();
        if (!$this->client->canWrite()) {
            throw new RuntimeException("Writes are disabled for the $environment environment (App Settings > D365 Config > Allow Writes)");
        }
        return $this->withMonthLock($environment, $month, function () use ($month, $empIds, $employments, $userId, $environment) {
            return $this->doSyncMonthChunk($month, $empIds, $employments, $userId, $environment);
        });
    }

    private function doSyncMonthChunk($month, array $empIds, array $employments, $userId, $environment)
    {
        $this->loadDimensionFormats();
        $settings = $this->getSettings();
        $transDate = date('Y-m-t', strtotime($month . '-01'));

        $rows = $this->getPayrolls($month, $empIds);
        $pushed = $this->getPushedMap($environment, $month);
        // Duplicate guard against D365 itself: employees whose payroll lines for this month are already
        // in any D365 journal (any company, posted or not) are recorded as synced and never sent again
        // Employees the log calls synced but whose lines are gone from D365 (journal deleted) are sent again.
        $inD365 = $this->findExistingInD365($month, array_values($empIds));
        foreach ($empIds as $id) {
            if (isset($pushed[$id]) && !isset($inD365[$id])) {
                $this->markRemoved($environment, $month, $id);
                unset($pushed[$id]);
            }
        }
        foreach ($inD365 as $id => $x) {
            if (!isset($pushed[$id])) {
                $this->logPush($environment, $month, $x['entity'], $x['journal'], $id, ['ok' => true, 'lines' => $x['lines'], 'amount' => 0], $userId);
                $pushed[$id] = ['legal_entity' => $x['entity'], 'journal_batch' => $x['journal']];
            }
        }
        $journals = [];      // company => journal row (created on first use)
        $journalErrors = []; // company => error, so one bad company does not stop the others
        $results = [];
        $groups = [];
        $built = [];
        foreach ($empIds as $id) {
            $p = $rows[$id] ?? null;
            // journal key: the employee's payroll company (Employee Master, default = D365 employment company),
            // split by Company dimension where D365 Config has e.g. MTL/02=02-GV
            $empDims = isset($employments[$id]) ? $this->dimsWithDepartment($id, $employments[$id]['dims'], $employments) : '';
            $company = isset($employments[$id]) ? $this->journalKey($this->payrollCompanyFor($id, $employments[$id]['entity']), $empDims) : '';
            $result = ['ok' => false, 'error' => null, 'lines' => 0, 'amount' => 0, 'company' => $company];
            if (isset($pushed[$id])) {
                $results[$id] = ['ok' => true, 'skipped' => true, 'error' => null, 'lines' => 0, 'amount' => 0, 'company' => $pushed[$id]['legal_entity']];
                continue;
            }
            if (!$p) {
                $result['error'] = "No payroll for $month";
            } elseif (strtolower((string)$p['status']) !== 'paid') {
                $result['error'] = 'Payroll is "' . $p['status'] . '" - only paid payroll can be synced';
            } elseif ($company === '') {
                $result['error'] = 'No employment in D365 - register the worker in D365 first';
                $result['missing_worker'] = true; // the sync popup offers to register these
                $result['name'] = (string)($p['name'] ?? '');
            } else {
                $b = self::buildLines($p, $settings, $month);
                $result['lines'] = count($b['lines']);
                $result['amount'] = $b['total'];
                if ($b['errors']) {
                    $result['error'] = implode('; ', array_unique($b['errors']));
                } else {
                    if (!isset($journals[$company]) && !isset($journalErrors[$company])) {
                        try {
                            $journals[$company] = $this->ensureJournal($environment, $month, $company, $settings, $userId);
                        } catch (Throwable $ex) {
                            $journalErrors[$company] = $ex->getMessage();
                        }
                    }
                    if (isset($journals[$company])) {
                        $employment = ['entity' => $company, 'dims' => $empDims, 'extra' => $this->extraLedgerValues($id)];
                        $groups[$id] = self::lineBodies($b, $employment, $journals[$company], $settings, $transDate);
                        $built[$id] = $result;
                        continue;
                    }
                    $result['error'] = $journalErrors[$company];
                }
            }
            $results[$id] = $result;
            $this->logPush($environment, $month, $company ?: '-', $journals[$company]['journal_batch'] ?? null, $id, $result, $userId);
        }

        if ($groups) {
            $answers = $this->client->batchCreateMulti('LedgerJournalLines', $groups);
            foreach ($groups as $id => $bodies) {
                $a = $answers[$id] ?? ['ok' => false, 'retry' => true, 'error' => 'No response'];
                if (!$a['ok'] && !empty($a['retry'])) {
                    // D365 stopped after an earlier failure - send this employee on its own
                    $single = $this->client->batchCreate('LedgerJournalLines', $bodies);
                    $a = ['ok' => $single['ok'], 'error' => $single['error']];
                }
                $result = $built[$id];
                $result['ok'] = $a['ok'];
                $result['error'] = $a['ok'] ? null : $a['error'];
                $results[$id] = $result;
                $company = $result['company'];
                $this->logPush($environment, $month, $company, $journals[$company]['journal_batch'], $id, $result, $userId);
            }
        }

        return [
            'journals' => array_map(function ($j) { return $j['journal_batch']; }, $journals),
            'results'  => $results,
        ];
    }

    /** Check every app journal of a month against D365 (deleted ones free their employees again) */
    public function checkMonthJournals($environment, $month)
    {
        $stmt = $this->prepare("SELECT legal_entity FROM d365_payroll_journals WHERE environment = ? AND month_year = ?");
        $stmt->bind_param('ss', $environment, $month);
        $stmt->execute();
        $entities = array_column($stmt->get_result()->fetch_all(MYSQLI_ASSOC), 'legal_entity');
        $stmt->close();
        foreach ($entities as $entity) {
            $this->checkJournal($environment, $month, $entity);
        }
    }
}
