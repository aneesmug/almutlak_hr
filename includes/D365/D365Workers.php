<?php
/**
 * Register HR app employees as workers in Dynamics 365 F&O.
 *
 * Uses the EmployeesV2 entity, which creates the person, the worker (PersonnelNumber = emp_id)
 * and the employment in the given legal entity in one call. Financial dimensions (department...)
 * are not set here - HR/finance complete them in D365 if needed.
 */
require_once __DIR__ . '/D365Client.php';

class D365Workers
{
    private $db;
    private $client;

    public function __construct(mysqli $db, D365Client $client)
    {
        $this->db = $db;
        $this->client = $client;
        // includes/db.php drops idle connections after 15s; D365 calls can take longer
        $db->query("SET SESSION wait_timeout = 600, SESSION interactive_timeout = 600");
        $db->query("CREATE TABLE IF NOT EXISTS d365_worker_status (
            emp_id VARCHAR(30) NOT NULL,
            environment VARCHAR(20) NOT NULL,
            status VARCHAR(20) NOT NULL,            -- registered | missing | failed | pending
            legal_entity VARCHAR(10) NULL,
            d365_name VARCHAR(255) NULL,
            last_error TEXT NULL,
            checked_at DATETIME NULL,
            registered_at DATETIME NULL,
            synced_at DATETIME NULL,
            PRIMARY KEY (emp_id, environment)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    // ---------------------------------------------------------------- status (header widget)

    /**
     * D365 status of one employee for the current environment. Uses the stored row while it is
     * fresher than $maxAgeSeconds, otherwise asks D365 (Workers + Employments) and stores the answer.
     */
    public function getStatus($empId, $maxAgeSeconds = 21600)
    {
        $env = $this->client->getEnvironment();
        $row = $this->loadStatus($empId);
        $fresh = $row && $row['checked_at'] && (time() - strtotime($row['checked_at']) < $maxAgeSeconds);
        // a failed/pending registration is kept as-is until someone retries or refreshes ($maxAgeSeconds = 0)
        if ($row && $maxAgeSeconds > 0 && ($fresh || in_array($row['status'], ['failed', 'pending'], true))) {
            return $row;
        }

        $q = "PersonnelNumber eq '" . str_replace("'", "''", $empId) . "'";
        $w = $this->client->get('Workers', ['$filter' => $q, '$select' => 'PersonnelNumber,Name']);
        if ($w['error']) {
            throw new RuntimeException('D365: ' . $w['error']);
        }
        $worker = $w['data']['value'][0] ?? null;
        $entity = null;
        if ($worker) {
            $e = $this->client->get('Employments', ['$filter' => $q, '$select' => 'LegalEntityId,EmploymentStartDate,EmploymentEndDate'], true);
            $now = gmdate('Y-m-d\TH:i:s\Z');
            foreach ($e['data']['value'] ?? [] as $emp) {
                if ($entity === null || strcmp($emp['EmploymentEndDate'], $now) > 0) {
                    $entity = strtoupper($emp['LegalEntityId']);
                }
            }
        }
        $this->saveStatus($empId, $worker ? 'registered' : 'missing', $entity, $worker['Name'] ?? null, null);
        return $this->loadStatus($empId);
    }

    public function loadStatus($empId)
    {
        $env = $this->client->getEnvironment();
        $stmt = $this->db->prepare("SELECT * FROM d365_worker_status WHERE emp_id = ? AND environment = ?");
        $stmt->bind_param('ss', $empId, $env);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc() ?: null;
        $stmt->close();
        return $row;
    }

    private function saveStatus($empId, $status, $entity, $name, $error, $stamp = null)
    {
        $env = $this->client->getEnvironment();
        $registeredAt = $stamp === 'registered' ? date('Y-m-d H:i:s') : null;
        $syncedAt = $stamp === 'synced' ? date('Y-m-d H:i:s') : null;
        $stmt = $this->db->prepare("INSERT INTO d365_worker_status (emp_id, environment, status, legal_entity, d365_name, last_error, checked_at, registered_at, synced_at)
            VALUES (?, ?, ?, ?, ?, ?, NOW(), ?, ?)
            ON DUPLICATE KEY UPDATE status = VALUES(status), legal_entity = COALESCE(VALUES(legal_entity), legal_entity),
                d365_name = COALESCE(VALUES(d365_name), d365_name), last_error = VALUES(last_error), checked_at = NOW(),
                registered_at = COALESCE(VALUES(registered_at), registered_at), synced_at = COALESCE(VALUES(synced_at), synced_at)");
        $stmt->bind_param('ssssssss', $empId, $env, $status, $entity, $name, $error, $registeredAt, $syncedAt);
        $stmt->execute();
        $stmt->close();
    }

    /**
     * Suggested D365 company for an app company, from a map cached for a day in the system temp
     * dir (building it needs every D365 employment, ~3s).
     */
    public function suggestCompanyFor($empId)
    {
        $file = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'd365_company_map_' . md5($this->client->getResourceUrl()) . '.json';
        $cached = is_readable($file) ? json_decode((string)@file_get_contents($file), true) : null;
        if (!$cached || time() - (int)$cached['t'] > 86400) {
            require_once __DIR__ . '/D365Payroll.php';
            $employments = (new D365Payroll($this->db, $this->client))->getEmployments();
            $cached = [
                't'        => time(),
                'map'      => $this->suggestCompanies($employments),
                'entities' => array_values(array_unique(array_column($employments, 'entity'))),
            ];
            sort($cached['entities']);
            @file_put_contents($file, json_encode($cached), LOCK_EX);
        }
        $stmt = $this->db->prepare("SELECT comp_no FROM employees WHERE emp_id = ?");
        $stmt->bind_param('s', $empId);
        $stmt->execute();
        $comp = $stmt->get_result()->fetch_assoc()['comp_no'] ?? null;
        $stmt->close();
        $map = self::withCompanyMapping($cached['map'] ?? []); // mapping changes apply at once, not after the 24h cache
        return ['company' => $map[$comp] ?? '', 'entities' => $cached['entities']];
    }

    // ---------------------------------------------------------------- sync (update) + auto register

    /**
     * Push the app's person details to the existing D365 worker (names, birth date, gender,
     * email, mobile, marital status). Company/employment changes are not touched.
     */
    public function syncWorker($empId)
    {
        if (!$this->client->canWrite()) {
            return ['ok' => false, 'error' => 'Writes are disabled for the ' . $this->client->getEnvironment() . ' environment (App Settings > D365 Config > Allow Writes)'];
        }
        $stmt = $this->db->prepare("SELECT emp_id, name, joining_date, dob, sex, email, c_email, mobile, mar_status, iban FROM employees WHERE emp_id = ?");
        $stmt->bind_param('s', $empId);
        $stmt->execute();
        $e = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$e) {
            return ['ok' => false, 'error' => 'Employee not found in the HR app'];
        }

        $body = array_intersect_key(self::buildBody($e, 'X'), array_flip(['FirstName', 'MiddleName', 'LastName', 'BirthDate', 'Gender', 'PrimaryContactEmail']));
        $body['MiddleName'] = $body['MiddleName'] ?? '';
        if (trim((string)$e['mobile']) !== '') {
            $body['PrimaryContactPhone'] = trim((string)$e['mobile']);
        }
        $marital = ['married' => 'Married', 'single' => 'Single', 'divorced' => 'Divorced', 'widowed' => 'Widowed'][strtolower(trim((string)$e['mar_status']))] ?? null;
        if ($marital) {
            $body['MaritalStatus'] = $marital;
        }

        $res = $this->client->batchUpdate("Workers(PersonnelNumber='" . rawurlencode(str_replace("'", "''", $empId)) . "')", $body);
        if ($res['ok']) {
            // Salary bank account (IBAN) - a failure here does not undo the person details above
            $bank = $this->syncBankAccount($empId, (string)$e['iban']);
            $res['bank'] = $bank['action'] ?? null;
            if (!$bank['ok']) {
                $res['warning'] = 'Bank account not synced: ' . $bank['error'];
            }
            // Department financial dimension of the employment (only filled when blank in D365)
            try {
                $dept = $this->syncDepartment($empId);
            } catch (Throwable $ex) {
                $dept = ['ok' => false, 'error' => $ex->getMessage()];
            }
            $res['department'] = $dept['action'] ?? null;
            $res['department_value'] = $dept['value'] ?? null;
            if (!$dept['ok']) {
                $res['warning'] = trim(($res['warning'] ?? '') . ' Department not synced: ' . $dept['error']);
            }
        }
        $row = $this->loadStatus($empId);
        if ($res['ok']) {
            $this->saveStatus($empId, 'registered', $row['legal_entity'] ?? null, $e['name'], $res['warning'] ?? null, 'synced');
        } else {
            $this->saveStatus($empId, $row['status'] ?? 'registered', $row['legal_entity'] ?? null, null, 'Sync failed: ' . $res['error']);
        }
        return $res;
    }

    /** @var array|null app department (id and lower-case name) => D365 Department value */
    private $departmentMap = null;

    /** @var bool true = departmentFor() never calls D365 (uses the config map and the last vote cache) */
    private $offline = false;

    /** D365 Department for the profile page without calling D365 ('' when not known yet) */
    public function departmentForOffline($empId)
    {
        $this->offline = true;
        try {
            return $this->departmentFor($empId);
        } finally {
            $this->offline = false;
        }
    }

    /**
     * D365 Department value for an employee's app department: D365 Config "Department map" first
     * (APP DEPARTMENT=VALUE, by name or id), else the value most colleagues of that app department
     * already have in D365 (cached 24h). '' when unknown.
     * $employments = D365Payroll::getEmployments() map, to avoid fetching it again.
     */
    public function departmentFor($empId, array $employments = null)
    {
        if ($this->departmentMap === null) {
            $this->departmentMap = $this->buildDepartmentMap($employments);
        }
        $stmt = $this->db->prepare("SELECT e.dept, d.dep_nme FROM employees e LEFT JOIN department d ON d.id = e.dept WHERE e.emp_id = ?");
        $stmt->bind_param('s', $empId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$row) {
            return '';
        }
        return $this->departmentMap['id:' . $row['dept']]
            ?? $this->departmentMap['name:' . mb_strtolower(trim((string)$row['dep_nme']))]
            ?? $this->departmentMap['vote:' . $row['dept']]
            ?? '';
    }

    private function buildDepartmentMap(array $employments = null)
    {
        $map = [];
        $config = D365Client::loadConfig();
        foreach (preg_split('/[,;\r\n]+/', (string)($config['DEPARTMENT_MAP'] ?? '')) as $pair) {
            if (strpos($pair, '=') === false) {
                continue;
            }
            [$app, $d365] = array_map('trim', explode('=', $pair, 2));
            if ($app === '' || $d365 === '') {
                continue;
            }
            $map[(ctype_digit($app) ? 'id:' : 'name:') . mb_strtolower($app)] = $d365;
        }

        // Fallback: majority vote of colleagues already carrying a Department in D365
        $file = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'd365_dept_vote_' . md5($this->client->getResourceUrl()) . '.json';
        $votes = (is_file($file) && (time() - filemtime($file) < 86400 || $this->offline)) ? json_decode((string)file_get_contents($file), true) : null;
        if (!is_array($votes) && $this->offline) {
            $votes = []; // profile page: no D365 call, the config map only
        }
        if (!is_array($votes)) {
            require_once __DIR__ . '/D365Payroll.php';
            $employments = $employments ?? (new D365Payroll($this->db, $this->client))->getEmployments();
            $formats = D365Payroll::fetchDimensionFormats($this->client);
            $i = array_search('department', array_map('strtolower', $formats['default'] ?: []), true);
            $i = $i === false ? 2 : $i;
            $count = [];
            $res = $this->db->query("SELECT emp_id, dept FROM employees");
            while ($res && ($r = $res->fetch_assoc())) {
                $value = trim(explode('-', (string)($employments[$r['emp_id']]['dims'] ?? ''))[$i] ?? '');
                if ($value !== '') {
                    $count[$r['dept']][$value] = ($count[$r['dept']][$value] ?? 0) + 1;
                }
            }
            $votes = [];
            foreach ($count as $dept => $values) {
                arsort($values);
                $votes[$dept] = (string)key($values);
            }
            @file_put_contents($file, json_encode($votes), LOCK_EX);
        }
        foreach ($votes as $dept => $value) {
            $map['vote:' . $dept] = $value;
        }
        return $map;
    }

    /**
     * Fill the Department of the worker's current D365 employment when it is blank (never replaces a value
     * finance set). Returns ['ok' => bool, 'error' => ?, 'action' => set|unchanged|unknown|skipped, 'value' => ?]
     */
    public function syncDepartment($empId)
    {
        require_once __DIR__ . '/D365Payroll.php';
        $employment = (new D365Payroll($this->db, $this->client))->getEmployments($empId)[$empId] ?? null;
        if (!$employment) {
            return ['ok' => true, 'error' => null, 'action' => 'skipped'];
        }
        $formats = D365Payroll::fetchDimensionFormats($this->client);
        $dims = $employment['dims'];
        $out = ['ok' => true, 'error' => null, 'action' => 'unchanged', 'value' => null];

        // Department: only filled when blank (finance's value is kept)
        $dept = $this->departmentFor($empId);
        if ($dept === '') {
            $out['action'] = 'unknown';
        } else {
            $withDept = D365Payroll::withDepartment($dims, $dept, $formats['default'] ?? []);
            if ($withDept !== $dims) {
                $out['action'] = 'set';
                $out['value'] = $dept;
                $dims = $withDept;
            }
        }

        if ($dims === $employment['dims']) {
            return $out;
        }
        $q = function ($v) { return "'" . rawurlencode(str_replace("'", "''", (string)$v)) . "'"; };
        $key = 'Employments(PersonnelNumber=' . $q($empId) . ',LegalEntityId=' . $q($employment['entity'])
            . ',EmploymentStartDate=' . $employment['start'] . ',EmploymentEndDate=' . $employment['end'] . ')';
        $res = $this->client->batchUpdate($key, ['DimensionDisplayValue' => $dims]);
        // payroll pages cache the employment list - drop it so the next sync sees the new dimensions
        @unlink(sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'd365_cache_' . md5($this->client->getResourceUrl() . '|employments') . '.json');
        $out['ok'] = $res['ok'];
        $out['error'] = $res['error'];
        return $out;
    }

    /** Payroll company: employees.payroll_company, else the D365 employment company */
    public function payrollCompany($empId, $employmentCompany)
    {
        require_once __DIR__ . '/D365Payroll.php';
        return (new D365Payroll($this->db, $this->client))->payrollCompanyFor($empId, $employmentCompany);
    }

    /** Whether the company's account structures have CostCenter; null when D365 rules cannot be read */
    public function companyUsesCostCenter($company)
    {
        try {
            require_once __DIR__ . '/D365AccountRules.php';
            $rules = new D365AccountRules($this->client);
            if (!isset($rules->companies()[strtoupper((string)$company)])) {
                return null;
            }
            return in_array(strtoupper((string)$company), $rules->companiesWithDimension('CostCenter'), true);
        } catch (Throwable $ex) {
            error_log('D365 account rules: ' . $ex->getMessage());
            return null;
        }
    }

    /** The employee's cost center in the HR app ('' when the column does not exist yet) */
    public function appCostCenter($empId)
    {
        $res = $this->db->query("SHOW COLUMNS FROM employees LIKE 'cost_center'");
        if (!$res || $res->num_rows === 0) {
            return '';
        }
        $stmt = $this->db->prepare("SELECT cost_center FROM employees WHERE emp_id = ?");
        $stmt->bind_param('s', $empId);
        $stmt->execute();
        $cc = trim((string)($stmt->get_result()->fetch_row()[0] ?? ''));
        $stmt->close();
        return $cc;
    }

    /** Saudi IBAN bank code (characters 5-6) => [D365 bank name, SWIFT] */
    const SAUDI_BANKS = [
        '05' => ['ALINMA BANK', 'INMASARIXXX'],
        '10' => ['SAUDI NATIONAL BANK', 'NCBKSAJEXXX'],
        '15' => ['BANK ALBILAD', 'ALBISARIXXX'],
        '20' => ['RIYADH BANK', 'RIBLSARIXXX'],
        '30' => ['ARAB NATIONAL BANK', 'ARNBSARIXXX'],
        '40' => ['SAMBA FINANCIAL GROUP', 'SAMBSARIXXX'],
        '45' => ['SAUDI BRITISH BANK (SAB)', 'SABBSARIXXX'],
        '50' => ['ALAWWAL BANK', 'AAALSARIXXX'],
        '55' => ['BANQUE SAUDI FRANSI', 'BSFRSARIXXX'],
        '60' => ['BANK ALJAZIRA', 'BJAZSAJEXXX'],
        '65' => ['SAUDI INVESTMENT BANK', 'SIBCSARIXXX'],
        '80' => ['AL RAJHI BANK', 'RJHISARIXXX'],
    ];

    /**
     * Make the worker's D365 bank account carry the app's IBAN: nothing when it already does,
     * update the first existing account when it differs, create account "1" when there is none.
     * Returns ['ok' => bool, 'error' => ?, 'action' => unchanged|updated|created|skipped]
     */
    public function syncBankAccount($empId, $iban)
    {
        $iban = strtoupper(preg_replace('/\s+/', '', $iban));
        if (!preg_match('/^SA\d{22}$/', $iban)) {
            return ['ok' => true, 'error' => null, 'action' => 'skipped']; // no (valid Saudi) IBAN in the app
        }
        $existing = $this->client->get('WorkerBankAccounts', ['$filter' => "PersonnelNumber eq '" . str_replace("'", "''", $empId) . "'"], true);
        if ($existing['error']) {
            return ['ok' => false, 'error' => $existing['error']];
        }
        $accounts = $existing['data']['value'] ?? [];
        foreach ($accounts as $a) {
            if (strtoupper(preg_replace('/\s+/', '', (string)$a['BankIBAN'])) === $iban) {
                return ['ok' => true, 'error' => null, 'action' => 'unchanged'];
            }
        }

        [$bankName, $swift] = self::SAUDI_BANKS[substr($iban, 4, 2)] ?? ['', ''];
        $body = [
            'BankIBAN'          => $iban,
            'BankAccountNumber' => ltrim(substr($iban, 6), '0'),
            'BankAccountType'   => 'CheckingAccount',
        ];
        if ($bankName !== '') {
            $body['Name'] = $bankName;
            $body['SWIFTNo'] = $swift;
        }

        if ($accounts) {
            $key = "WorkerBankAccounts(PersonnelNumber='" . rawurlencode(str_replace("'", "''", $empId)) . "',AccountIdentification='" . rawurlencode(str_replace("'", "''", $accounts[0]['AccountIdentification'])) . "')";
            $res = $this->client->batchUpdate($key, $body);
            return ['ok' => $res['ok'], 'error' => $res['error'], 'action' => 'updated'];
        }
        $res = $this->client->batchCreate('WorkerBankAccounts', [['PersonnelNumber' => $empId, 'AccountIdentification' => '1'] + $body]);
        return ['ok' => $res['ok'], 'error' => $res['error'], 'action' => 'created'];
    }

    /**
     * Called right after a new employee is saved in the HR app. Never throws - a failure is stored
     * so the employee header shows the manual "Add to D365" button.
     */
    public function autoRegister($empId, $company = '')
    {
        try {
            $company = strtoupper(trim((string)$company)); // chosen in the new-employee modal ('' = suggest)
            if (!$this->client->canWrite()) {
                $this->saveStatus($empId, 'pending', $company !== '' ? $company : null, null, 'Writes to D365 are off - add manually when enabled');
                return;
            }
            if ($company === '') {
                $company = $this->suggestCompanyFor($empId)['company'];
            }
            if ($company === '') {
                $this->saveStatus($empId, 'failed', null, null, 'No D365 company known for this app company - choose it manually');
                return;
            }
            $this->register($empId, $company);
        } catch (Throwable $ex) {
            $this->saveStatus($empId, 'failed', null, null, $ex->getMessage());
        }
    }

    /**
     * Most common D365 legal entity per app company (comp_no), learned from employees registered in both.
     * $employments = D365Payroll::getEmployments() map. Returns [comp_no => 'MHO', ...]
     */
    public function suggestCompanies(array $employments)
    {
        $votes = [];
        $res = $this->db->query("SELECT emp_id, comp_no FROM employees");
        while ($res && ($r = $res->fetch_assoc())) {
            if (isset($employments[$r['emp_id']])) {
                $entity = $employments[$r['emp_id']]['entity'];
                $votes[$r['comp_no']][$entity] = ($votes[$r['comp_no']][$entity] ?? 0) + 1;
            }
        }
        $map = [];
        foreach ($votes as $comp => $v) {
            arsort($v);
            $map[$comp] = (string)key($v);
        }
        return self::withCompanyMapping($map);
    }

    /** Company mapping of D365 Config > Companies wins over the learned majority vote */
    private static function withCompanyMapping(array $map)
    {
        require_once __DIR__ . '/../cost_centers.php';
        foreach (d365_company_mapping() as $compId => $code) {
            $map[$compId] = $code;
        }
        return $map;
    }

    /** EmployeesV2 body built from the app's employee record */
    public static function buildBody(array $e, $legalEntity)
    {
        $parts = preg_split('/\s+/', trim((string)$e['name']));
        $first = (string)array_shift($parts);
        $last = $parts ? (string)array_pop($parts) : '';
        $middle = implode(' ', $parts);

        // Start date at midnight Riyadh time, sent as UTC (D365 shows it back as the same Riyadh date)
        $start = null;
        if (!empty($e['joining_date']) && $e['joining_date'] !== '0000-00-00') {
            $dt = new DateTime($e['joining_date'] . ' 00:00:00', new DateTimeZone('Asia/Riyadh'));
            $dt->setTimezone(new DateTimeZone('UTC'));
            $start = $dt->format('Y-m-d\TH:i:s\Z');
        }
        $email = trim((string)($e['c_email'] ?: $e['email']));

        return array_filter([
            'PersonnelNumber'         => (string)$e['emp_id'],
            'EmploymentLegalEntityId' => strtoupper($legalEntity),
            'FirstName'               => $first,
            'MiddleName'              => $middle,
            'LastName'                => $last,
            'EmploymentStartDate'     => $start,
            'BirthDate'               => (!empty($e['dob']) && $e['dob'] !== '0000-00-00') ? $e['dob'] . 'T12:00:00Z' : null,
            'Gender'                  => (string)$e['sex'] === '1' ? 'Male' : ((string)$e['sex'] === '2' ? 'Female' : null),
            'PrimaryContactEmail'     => filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null,
        ], function ($v) { return $v !== null && $v !== ''; });
    }

    /**
     * Create the worker + employment in D365. Returns ['ok' => bool, 'error' => ?string, 'name' => string].
     */
    public function register($empId, $legalEntity)
    {
        if (!$this->client->canWrite()) {
            return ['ok' => false, 'error' => 'Writes are disabled for the ' . $this->client->getEnvironment() . ' environment (App Settings > D365 Config > Allow Writes)'];
        }
        if (!preg_match('/^[A-Za-z0-9]{2,10}$/', $legalEntity)) {
            return ['ok' => false, 'error' => 'Choose the D365 company'];
        }

        $stmt = $this->db->prepare("SELECT emp_id, name, joining_date, dob, sex, email, c_email, status FROM employees WHERE emp_id = ?");
        if (!$stmt) {
            return ['ok' => false, 'error' => 'Database error: ' . $this->db->error];
        }
        $stmt->bind_param('s', $empId);
        $stmt->execute();
        $e = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$e) {
            return ['ok' => false, 'error' => 'Employee not found in the HR app'];
        }
        if (trim((string)$e['name']) === '' || empty($e['joining_date']) || $e['joining_date'] === '0000-00-00') {
            return ['ok' => false, 'error' => 'Name or joining date is missing in the HR app'];
        }

        // Never create a second worker with the same personnel number
        $existing = $this->client->get('Workers', ['$filter' => "PersonnelNumber eq '" . str_replace("'", "''", $empId) . "'", '$select' => 'PersonnelNumber']);
        if ($existing['error']) {
            return ['ok' => false, 'error' => 'D365 check failed: ' . $existing['error']];
        }
        if (!empty($existing['data']['value'])) {
            $this->saveStatus($empId, 'registered', null, null, null);
            return ['ok' => false, 'error' => 'Already registered in D365 (reload D365 data)'];
        }

        $res = $this->client->batchCreate('EmployeesV2', [self::buildBody($e, $legalEntity)]);
        if (!$res['ok']) {
            $this->saveStatus($empId, 'failed', null, null, $res['error']);
            return ['ok' => false, 'error' => $res['error'], 'name' => $e['name']];
        }
        $this->saveStatus($empId, 'registered', strtoupper($legalEntity), $e['name'], null, 'registered');

        // EmployeesV2 only takes the basics - fill the rest right away (mobile, marital status,
        // email, birth date, gender) plus the salary bank account, same as "Sync to D365"
        $out = ['ok' => true, 'error' => null, 'name' => $e['name']];
        $sync = $this->syncWorker($empId);
        if (!$sync['ok']) {
            $out['warning'] = 'Worker created, but the details sync failed: ' . $sync['error'] . ' - use Sync to D365';
        } elseif (!empty($sync['warning'])) {
            $out['warning'] = 'Worker created. ' . $sync['warning'];
        }
        $out['bank'] = $sync['bank'] ?? null;
        return $out;
    }

    /** Employments of a worker from EmploymentsV2 (all companies), newest first, with 'active' flag */
    public function employmentsV2($empId)
    {
        $res = $this->client->getAll('EmploymentsV2', [
            '$filter' => "PersonnelNumber eq '" . str_replace("'", "''", $empId) . "'",
            '$select' => 'PersonnelNumber,LegalEntityId,EmploymentId,EmploymentStartDate,EmploymentEndDate,DimensionDisplayValue,WorkerType',
        ], true);
        if ($res['error']) {
            throw new RuntimeException('D365 EmploymentsV2: ' . $res['error']);
        }
        $now = gmdate('Y-m-d\TH:i:s\Z');
        $rows = [];
        foreach ($res['data']['value'] as $e) {
            $e['LegalEntityId'] = strtoupper((string)$e['LegalEntityId']);
            $e['active'] = strcmp((string)$e['EmploymentEndDate'], $now) > 0;
            $e['start_local'] = self::localDate($e['EmploymentStartDate']);
            $e['end_local'] = strpos((string)$e['EmploymentEndDate'], '2154') === 0 ? '' : self::localDate($e['EmploymentEndDate']);
            $rows[] = $e;
        }
        usort($rows, function ($a, $b) { return strcmp($b['EmploymentStartDate'], $a['EmploymentStartDate']); });
        return $rows;
    }

    /** D365 UTC timestamp -> Riyadh date (Y-m-d) */
    private static function localDate($utc)
    {
        try {
            return (new DateTime((string)$utc))->setTimezone(new DateTimeZone('Asia/Riyadh'))->format('Y-m-d');
        } catch (Throwable $ex) {
            return '';
        }
    }

    /**
     * Move a worker to another D365 company (legal entity) from $date (Y-m-d, Riyadh):
     *   1. create a new employment in $newCompany starting $date (same financial dimensions; without them if D365 refuses)
     *   2. end the current employment the day before
     * The new employment is created first so the worker is never left without one. Positions of the old
     * company are not moved (assign a position in the new company in D365 when needed).
     * Returns ['ok' => bool, 'error' => ?, 'warning' => ?, 'from' => 'MHO', 'to' => 'MTL']
     */
    public function transferCompany($empId, $newCompany, $date)
    {
        if (!$this->client->canWrite()) {
            return ['ok' => false, 'error' => 'Writes are disabled for the ' . $this->client->getEnvironment() . ' environment (App Settings > D365 Config > Allow Writes)'];
        }
        $newCompany = strtoupper(trim((string)$newCompany));
        if (!preg_match('/^[A-Z0-9]{2,10}$/', $newCompany)) {
            return ['ok' => false, 'error' => 'Choose the new D365 company'];
        }
        $tz = new DateTimeZone('Asia/Riyadh');
        $start = DateTime::createFromFormat('!Y-m-d', (string)$date, $tz);
        if (!$start) {
            return ['ok' => false, 'error' => 'Choose the transfer date'];
        }
        $startUtc = (clone $start)->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d\TH:i:s\Z');
        $endOldUtc = (clone $start)->modify('-1 second')->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d\TH:i:s\Z');

        $all = $this->employmentsV2($empId);
        $current = null;
        foreach ($all as $e) {
            if ($e['active']) {
                if ($e['LegalEntityId'] === $newCompany) {
                    return ['ok' => false, 'error' => "Already employed in $newCompany"];
                }
                $current = $current ?: $e;
            }
        }
        if (!$current) {
            return ['ok' => false, 'error' => 'No active employment in D365 to transfer from'];
        }
        if (strcmp($startUtc, (string)$current['EmploymentStartDate']) <= 0) {
            return ['ok' => false, 'error' => 'Transfer date must be after the current employment start (' . $current['start_local'] . ')'];
        }

        $body = [
            'PersonnelNumber'     => (string)$empId,
            'LegalEntityId'       => strtolower($newCompany),
            'EmploymentStartDate' => $startUtc,
            'EmploymentEndDate'   => '2154-12-31T23:59:59Z',
            'WorkerType'          => $current['WorkerType'] ?: 'Employee',
        ];
        $warning = null;
        $dims = (string)($current['DimensionDisplayValue'] ?? '');
        $created = $this->client->create('EmploymentsV2', $dims !== '' && trim($dims, '-') !== '' ? $body + ['DimensionDisplayValue' => $dims] : $body);
        if (!empty($created['error']) && trim($dims, '-') !== '') {
            // dimension values of the old company may not be valid in the new one - create without them
            $created = $this->client->create('EmploymentsV2', $body);
            $warning = 'Financial dimensions were not copied (' . $dims . ') - set them on the new employment.';
        }
        if (!empty($created['error'])) {
            return ['ok' => false, 'error' => 'Creating the employment in ' . $newCompany . ' failed: ' . $created['error']];
        }

        $ended = $this->client->update('EmploymentsV2', [
            'PersonnelNumber' => $current['PersonnelNumber'],
            'LegalEntityId'   => strtolower($current['LegalEntityId']),
            'EmploymentId'    => $current['EmploymentId'],
        ], ['EmploymentEndDate' => $endOldUtc]);
        if (!empty($ended['error'])) {
            $warning = trim(($warning ? $warning . ' ' : '') . 'The new employment was created, but ending the ' . $current['LegalEntityId']
                . ' employment failed: ' . $ended['error'] . ' - end it in D365 (Worker > Employment history).');
        }

        $this->saveStatus($empId, 'registered', $newCompany, null, null);
        // Payroll company pinned to the old company in the app -> back to automatic (= new D365 company)
        $stmt = $this->db->prepare("UPDATE employees SET payroll_company = NULL WHERE emp_id = ? AND payroll_company = ?");
        if ($stmt) {
            $old = $current['LegalEntityId'];
            $stmt->bind_param('ss', $empId, $old);
            $stmt->execute();
            $stmt->close();
        }
        return ['ok' => true, 'error' => null, 'warning' => $warning, 'from' => $current['LegalEntityId'], 'to' => $newCompany];
    }

    /**
     * Everything D365 holds for one personnel number (read only), for the employee master D365 tab.
     * Returns ['worker' => ?array, 'employments' => [], 'positions' => [], 'banks' => [], 'journal_lines' => [], 'errors' => []]
     */
    public function getEmployeeDetails($empId)
    {
        $out = ['worker' => null, 'employments' => [], 'positions' => [], 'banks' => [], 'journal_lines' => [], 'errors' => []];
        $filter = ['$filter' => "PersonnelNumber eq '" . str_replace("'", "''", $empId) . "'"];

        $res = $this->client->get('Workers', $filter);
        if ($res['error']) {
            $out['errors'][] = 'Workers: ' . $res['error'];
            return $out;
        }
        $worker = $res['data']['value'][0] ?? null;
        if (!$worker) {
            return $out;
        }
        unset($worker['@odata.etag']);
        $out['worker'] = $worker;

        foreach ([
            'employments' => ['Employments', true],
            'positions'   => ['PositionWorkerAssignments', false],
            'banks'       => ['WorkerBankAccounts', true],
        ] as $key => [$entity, $cross]) {
            $r = $this->client->get($entity, $filter, $cross);
            if ($r['error']) {
                $out['errors'][] = $entity . ': ' . $r['error'];
            } else {
                $out[$key] = $r['data']['value'] ?? [];
            }
        }

        // General journal lines of this worker: pushed by this app ("PAY yyyy-mm <emp> ...") or carrying the
        // worker in the ledger dimension ("<account>-...-<emp>-..."). The wildcard scans every line (~45s across
        // all companies), so only the worker's own companies from shortly before the first employment (~2s).
        $companies = [];
        $firstStart = null;
        foreach ($out['employments'] as $emp) {
            $companies[strtolower($emp['LegalEntityId'])] = true;
            if ($firstStart === null || strcmp($emp['EmploymentStartDate'], $firstStart) < 0) {
                $firstStart = $emp['EmploymentStartDate'];
            }
        }
        // payroll can be booked in another company (Payroll Company in the Employee Master) - look there too
        if ($companies) {
            try {
                $pc = $this->payrollCompany($empId, '');
                if ($pc !== '') {
                    $companies[strtolower($pc)] = true;
                }
                $stmt = $this->db->prepare("SELECT DISTINCT legal_entity FROM d365_payroll_push_log WHERE emp_id = ? AND environment = ? AND status = 'ok'");
                $env = $this->client->getEnvironment();
                $stmt->bind_param('ss', $empId, $env);
                $stmt->execute();
                foreach ($stmt->get_result()->fetch_all() as $row) {
                    $companies[strtolower(explode('/', (string)$row[0], 2)[0])] = true;
                }
                $stmt->close();
            } catch (Throwable $ex) {
            }
        }
        if ($companies) {
            $since = gmdate('Y-m-d\T00:00:00\Z', strtotime(substr($firstStart, 0, 10) . ' -90 days'));
            $companyFilter = implode(' or ', array_map(function ($c) { return "dataAreaId eq '" . $c . "'"; }, array_keys($companies)));
            $r = $this->client->get('LedgerJournalLines', [
                // F&O OData has no contains(); "eq '*x*'" is its wildcard match
                '$filter' => "($companyFilter) and TransDate ge $since and (Text eq '* " . $empId . " *' or AccountDisplayValue eq '*-" . $empId . "-*')",
                '$select' => 'dataAreaId,JournalBatchNumber,LineNumber,TransDate,Voucher,AccountDisplayValue,DebitAmount,CreditAmount,Text',
                '$top'    => 300,
            ], true);
            if ($r['error']) {
                $out['errors'][] = 'LedgerJournalLines: ' . $r['error'];
            } else {
                $lines = $r['data']['value'] ?? [];
                usort($lines, function ($a, $b) {
                    return strcmp($b['TransDate'], $a['TransDate']) ?: strcmp($a['JournalBatchNumber'], $b['JournalBatchNumber']) ?: $a['LineNumber'] <=> $b['LineNumber'];
                });
                $out['journal_lines'] = $lines;
            }
        }

        // Posted / unposted state of those journals ("company|batch" => header)
        $out['journals'] = [];
        $keys = [];
        foreach ($out['journal_lines'] as $jl) {
            $keys[strtolower($jl['dataAreaId']) . '|' . $jl['JournalBatchNumber']] = true;
        }
        foreach (array_chunk(array_keys($keys), 15) as $chunk) {
            $or = implode(' or ', array_map(function ($k) {
                [$c, $b] = explode('|', $k, 2);
                return "(dataAreaId eq '" . $c . "' and JournalBatchNumber eq '" . str_replace("'", "''", $b) . "')";
            }, $chunk));
            $r = $this->client->get('LedgerJournalHeaders', ['$filter' => $or, '$select' => 'dataAreaId,JournalBatchNumber,JournalName,Description,IsPosted'], true);
            foreach ($r['data']['value'] ?? [] as $h) {
                $out['journals'][strtolower($h['dataAreaId']) . '|' . $h['JournalBatchNumber']] = $h;
            }
        }
        return $out;
    }

    /** Main account id => name (chart of accounts), cached 24h in the system temp dir */
    public function mainAccountNames()
    {
        $file = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'd365_main_accounts_' . md5($this->client->getResourceUrl()) . '.json';
        if (is_file($file) && time() - filemtime($file) < 86400) {
            $map = json_decode((string)file_get_contents($file), true);
            if (is_array($map)) {
                return $map;
            }
        }
        $r = $this->client->getAll('MainAccounts', ['$select' => 'MainAccountId,Name']);
        if ($r['error']) {
            return [];
        }
        $map = [];
        foreach ($r['data']['value'] ?? [] as $a) {
            $map[$a['MainAccountId']] = $a['Name'];
        }
        @file_put_contents($file, json_encode($map, JSON_UNESCAPED_UNICODE));
        return $map;
    }
}
