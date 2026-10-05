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
        return ['company' => $cached['map'][$comp] ?? '', 'entities' => $cached['entities']];
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
        }
        $row = $this->loadStatus($empId);
        if ($res['ok']) {
            $this->saveStatus($empId, 'registered', $row['legal_entity'] ?? null, $e['name'], $res['warning'] ?? null, 'synced');
        } else {
            $this->saveStatus($empId, $row['status'] ?? 'registered', $row['legal_entity'] ?? null, null, 'Sync failed: ' . $res['error']);
        }
        return $res;
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
    public function autoRegister($empId)
    {
        try {
            if (!$this->client->canWrite()) {
                $this->saveStatus($empId, 'pending', null, null, 'Writes to D365 are off - add manually when enabled');
                return;
            }
            $suggest = $this->suggestCompanyFor($empId);
            if ($suggest['company'] === '') {
                $this->saveStatus($empId, 'failed', null, null, 'No D365 company known for this app company - choose it manually');
                return;
            }
            $this->register($empId, $suggest['company']);
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
