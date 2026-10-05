<?php
/**
 * Microsoft Dynamics 365 Finance & Operations OData client.
 *
 * Auth: Microsoft Entra ID OAuth2 client-credentials flow (app registration "HR system").
 * The app must also be listed in F&O under System administration > Setup >
 * Microsoft Entra ID applications, mapped to an F&O user with the needed roles.
 *
 * Settings come from includes/d365_config.ini (git-ignored, see d365_config.ini.example).
 */
class D365Client
{
    private $tenantId;
    private $clientId;
    private $clientSecret;
    private $resourceUrl;
    private $environment;
    private $journalNames = [];
    private $allowWrites;
    private $token = null;
    private $tokenExpires = 0;

    public function __construct(array $config = null)
    {
        if ($config === null) {
            $config = self::loadConfig();
        }
        $this->tenantId     = trim($config['TENANT_ID'] ?? '');
        $this->clientId     = trim($config['CLIENT_ID'] ?? '');
        $this->clientSecret = trim($config['CLIENT_SECRET'] ?? '');
        $this->resourceUrl  = rtrim(trim($config['RESOURCE_URL'] ?? ''), '/');
        $this->journalNames = self::journalNames($config);
        $this->environment  = strtolower(trim($config['ENVIRONMENT'] ?? 'production'));
        // Writes are off unless explicitly enabled - protects live finance data
        $this->allowWrites  = in_array(strtolower(trim($config['ALLOW_WRITES'] ?? '')), ['1', 'true', 'yes', 'on'], true);

        foreach (['tenantId' => 'TENANT_ID', 'clientId' => 'CLIENT_ID', 'clientSecret' => 'CLIENT_SECRET', 'resourceUrl' => 'RESOURCE_URL'] as $prop => $key) {
            if ($this->$prop === '') {
                throw new RuntimeException("D365 setting $key is empty - fill it in App Settings > D365 Config");
            }
        }
    }

    public static function configPath()
    {
        return __DIR__ . '/d365_config.ini';
    }

    /**
     * Settings come from App Settings > D365 Config (app_settings rows d365_*). A value still empty
     * there falls back to the legacy includes/d365_config.ini, if that file exists.
     */
    public static function loadConfig()
    {
        $config = [];

        global $conDB;
        if ($conDB instanceof mysqli) {
            $keys = [
                'd365_tenant_id'     => 'TENANT_ID',
                'd365_client_id'     => 'CLIENT_ID',
                'd365_client_secret' => 'CLIENT_SECRET',
                'd365_resource_url'  => 'RESOURCE_URL',
                'd365_environment'   => 'ENVIRONMENT',
                'd365_allow_writes'  => 'ALLOW_WRITES',
                'd365_journal_names' => 'JOURNAL_NAMES',
                'd365_department_map' => 'DEPARTMENT_MAP',
            ];
            $res = @$conDB->query("SELECT setting_name, setting_value FROM app_settings WHERE setting_name LIKE 'd365\\_%'");
            while ($res && ($r = $res->fetch_assoc())) {
                if (isset($keys[$r['setting_name']]) && trim((string)$r['setting_value']) !== '') {
                    $config[$keys[$r['setting_name']]] = trim((string)$r['setting_value']);
                }
            }
        }

        $path = self::configPath();
        if (is_readable($path)) {
            $ini = parse_ini_file($path, true, INI_SCANNER_RAW);
            foreach (($ini['d365'] ?? []) as $k => $v) {
                if (!isset($config[$k]) && trim((string)$v) !== '') {
                    $config[$k] = $v;
                }
            }
        }

        if (!$config) {
            throw new RuntimeException('D365 is not configured - fill in App Settings > D365 Config');
        }
        return $config;
    }

    /** Payroll journal name per legal entity, from "MHO=GRN_JRN, MSP=RY-GEN, ..." in D365 Config */
    public static function journalNames(array $config = null)
    {
        $config = $config ?? self::loadConfig();
        $map = [];
        foreach (preg_split('/[,;\r\n]+/', (string)($config['JOURNAL_NAMES'] ?? '')) as $pair) {
            if (strpos($pair, '=') !== false) {
                [$company, $journal] = array_map('trim', explode('=', $pair, 2));
                if ($company !== '' && $journal !== '') {
                    $map[strtoupper($company)] = $journal;
                }
            }
        }
        return $map;
    }

    /** Payroll journal name configured for a legal entity (D365 Config), or null */
    public function getJournalName($company)
    {
        return $this->journalNames[strtoupper((string)$company)] ?? null;
    }

    public function getResourceUrl()
    {
        return $this->resourceUrl;
    }

    public function getEnvironment()
    {
        return $this->environment;
    }

    public function canWrite()
    {
        return $this->allowWrites;
    }

    /**
     * Bearer token, cached in the system temp dir (outside the web root) until shortly before expiry.
     */
    public function getToken($forceRefresh = false)
    {
        if (!$forceRefresh && $this->token && time() < $this->tokenExpires - 120) {
            return $this->token;
        }

        $cacheFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'd365_token_' . md5($this->tenantId . $this->clientId . $this->resourceUrl) . '.json';
        if (!$forceRefresh && is_readable($cacheFile)) {
            $cached = json_decode((string)@file_get_contents($cacheFile), true);
            if (!empty($cached['token']) && time() < (int)$cached['expires'] - 120) {
                $this->token = $cached['token'];
                $this->tokenExpires = (int)$cached['expires'];
                return $this->token;
            }
        }

        $res = $this->http('POST', 'https://login.microsoftonline.com/' . rawurlencode($this->tenantId) . '/oauth2/v2.0/token', [
            'Content-Type: application/x-www-form-urlencoded',
        ], http_build_query([
            'grant_type'    => 'client_credentials',
            'client_id'     => $this->clientId,
            'client_secret' => $this->clientSecret,
            'scope'         => $this->resourceUrl . '/.default',
        ]));

        if ($res['status'] !== 200 || empty($res['data']['access_token'])) {
            $msg = $res['data']['error_description'] ?? $res['error'] ?? ('HTTP ' . $res['status']);
            throw new RuntimeException('Entra ID token request failed: ' . $msg);
        }

        $this->token = $res['data']['access_token'];
        $this->tokenExpires = time() + (int)($res['data']['expires_in'] ?? 3599);
        @file_put_contents($cacheFile, json_encode(['token' => $this->token, 'expires' => $this->tokenExpires]), LOCK_EX);

        return $this->token;
    }

    /**
     * Read an OData entity set, e.g. get('Workers', ['$top' => 10, '$select' => 'PersonnelNumber,Name']).
     * Returns ['status' => int, 'data' => array|null, 'error' => string|null].
     */
    public function get($entity, array $query = [], $crossCompany = false)
    {
        if ($crossCompany) {
            $query['cross-company'] = 'true';
        }
        return $this->request('GET', $entity, $query);
    }

    public function create($entity, array $body)
    {
        return $this->request('POST', $entity, [], $body);
    }

    /**
     * Update one record by its key, e.g. update('Workers', ['PersonnelNumber' => '5430'], ['BirthDate' => '1989-04-14T12:00:00Z']).
     * Company-specific entities need 'dataAreaId' in $keys.
     */
    public function update($entity, array $keys, array $body)
    {
        $parts = [];
        foreach ($keys as $k => $v) {
            $parts[] = $k . "='" . rawurlencode(str_replace("'", "''", (string)$v)) . "'";
        }
        return $this->request('PATCH', $entity . '(' . implode(',', $parts) . ')', [], $body);
    }

    /**
     * Create several records in one OData $batch changeset - all succeed or all roll back.
     * Returns ['ok' => bool, 'status' => int, 'results' => [['status' => int, 'data' => array|null], ...], 'error' => string|null].
     */
    public function batchCreate($entity, array $bodies)
    {
        if (!$this->allowWrites) {
            return ['ok' => false, 'status' => 0, 'results' => [], 'error' => "Writes to D365 are disabled (ALLOW_WRITES is off for the {$this->environment} environment in d365_config.ini)"];
        }
        if (!$bodies) {
            return ['ok' => true, 'status' => 200, 'results' => [], 'error' => null];
        }

        $batchId = 'batch_' . bin2hex(random_bytes(8));
        $changesetId = 'changeset_' . bin2hex(random_bytes(8));
        $entityUrl = $this->resourceUrl . '/data/' . ltrim($entity, '/');

        $parts = [];
        $i = 0;
        foreach ($bodies as $body) {
            $i++;
            $parts[] = "--$changesetId\r\n"
                . "Content-Type: application/http\r\n"
                . "Content-Transfer-Encoding: binary\r\n"
                . "Content-ID: $i\r\n\r\n"
                . "POST $entityUrl HTTP/1.1\r\n"
                . "Content-Type: application/json; type=entry\r\n"
                . "Accept: application/json\r\n\r\n"
                . json_encode($body) . "\r\n";
        }
        $payload = "--$batchId\r\n"
            . "Content-Type: multipart/mixed; boundary=$changesetId\r\n\r\n"
            . implode('', $parts)
            . "--$changesetId--\r\n"
            . "--$batchId--\r\n";

        $send = function () use ($batchId, $payload) {
            return $this->http('POST', $this->resourceUrl . '/data/$batch', [
                'Authorization: Bearer ' . $this->getToken(),
                'Content-Type: multipart/mixed; boundary=' . $batchId,
                'Accept: multipart/mixed',
                'OData-Version: 4.0',
                'OData-MaxVersion: 4.0',
            ], $payload, true);
        };
        $res = $send();
        if ($res['status'] === 401) {
            $this->getToken(true);
            $res = $send();
        }
        if ($res['error'] && $res['status'] === 0) {
            return ['ok' => false, 'status' => 0, 'results' => [], 'error' => $res['error']];
        }

        // Each inner response: "HTTP/1.1 201 Created" ... headers ... blank line ... JSON body
        $results = [];
        $firstError = null;
        if (preg_match_all('#HTTP/1\.1 (\d{3})[^\r\n]*\r?\n(.*?)(?=\r?\n--|\z)#s', (string)$res['raw'], $m, PREG_SET_ORDER)) {
            foreach ($m as $match) {
                $status = (int)$match[1];
                $sections = preg_split("/\r?\n\r?\n/", $match[2], 2);
                $data = isset($sections[1]) ? json_decode(trim($sections[1]), true) : null;
                $results[] = ['status' => $status, 'data' => $data];
                if ($status >= 400 && $firstError === null) {
                    $firstError = $data['error']['innererror']['message'] ?? $data['error']['message'] ?? ('HTTP ' . $status);
                }
            }
        }

        $ok = $res['status'] < 400 && $firstError === null && count($results) === count($bodies);
        if (!$ok && $firstError === null) {
            $firstError = $res['error'] ?: ('Unexpected $batch response (HTTP ' . $res['status'] . ', ' . count($results) . ' of ' . count($bodies) . ' results)');
        }
        return ['ok' => $ok, 'status' => $res['status'], 'results' => $results, 'error' => $ok ? null : $firstError];
    }

    /**
     * Create several independent groups in ONE $batch request - one changeset per group, so each group
     * is all-or-nothing on its own (e.g. one employee's journal lines). Returns one result per group key:
     * [key => ['ok' => bool, 'error' => ?string]]. Groups D365 did not answer (it may stop after a failed
     * changeset) come back with 'ok' => false, 'retry' => true so the caller can resend them alone.
     */
    public function batchCreateMulti($entity, array $groups)
    {
        $out = [];
        if (!$this->allowWrites) {
            foreach ($groups as $key => $bodies) {
                $out[$key] = ['ok' => false, 'error' => "Writes to D365 are disabled (ALLOW_WRITES is off for the {$this->environment} environment in d365_config.ini)"];
            }
            return $out;
        }
        if (!$groups) {
            return $out;
        }

        $batchId = 'batch_' . bin2hex(random_bytes(8));
        $entityUrl = $this->resourceUrl . '/data/' . ltrim($entity, '/');
        $payload = '';
        $contentId = 0;
        foreach ($groups as $bodies) {
            $changesetId = 'changeset_' . bin2hex(random_bytes(8));
            $payload .= "--$batchId\r\nContent-Type: multipart/mixed; boundary=$changesetId\r\n\r\n";
            foreach ($bodies as $body) {
                $contentId++;
                $payload .= "--$changesetId\r\n"
                    . "Content-Type: application/http\r\n"
                    . "Content-Transfer-Encoding: binary\r\n"
                    . "Content-ID: $contentId\r\n\r\n"
                    . "POST $entityUrl HTTP/1.1\r\n"
                    . "Content-Type: application/json; type=entry\r\n"
                    . "Accept: application/json\r\n\r\n"
                    . json_encode($body) . "\r\n";
            }
            $payload .= "--$changesetId--\r\n";
        }
        $payload .= "--$batchId--\r\n";

        $send = function () use ($batchId, $payload) {
            return $this->http('POST', $this->resourceUrl . '/data/$batch', [
                'Authorization: Bearer ' . $this->getToken(),
                'Content-Type: multipart/mixed; boundary=' . $batchId,
                'Accept: multipart/mixed',
                'Prefer: odata.continue-on-error',
                'OData-Version: 4.0',
                'OData-MaxVersion: 4.0',
            ], $payload, true);
        };
        $res = $send();
        if ($res['status'] === 401) {
            $this->getToken(true);
            $res = $send();
        }

        // Split the response by its own batch boundary: one part per changeset, in request order
        $raw = (string)($res['raw'] ?? '');
        $parts = [];
        if (preg_match('/^--(\S+)/', ltrim($raw), $bm)) {
            $boundary = $bm[1];
            foreach (explode('--' . $boundary, $raw) as $chunk) {
                $chunk = trim($chunk);
                if ($chunk !== '' && $chunk !== '--') {
                    $parts[] = $chunk;
                }
            }
        }

        $i = 0;
        foreach ($groups as $key => $bodies) {
            $part = $parts[$i++] ?? null;
            if ($part === null) {
                $out[$key] = ['ok' => false, 'retry' => true, 'error' => $res['error'] ?: 'No response from D365 for this group'];
                continue;
            }
            preg_match_all('#HTTP/1\.1 (\d{3})[^\r\n]*\r?\n(.*?)(?=\r?\n--|\z)#s', $part, $m, PREG_SET_ORDER);
            $error = null;
            foreach ($m as $match) {
                if ((int)$match[1] >= 400) {
                    $sections = preg_split("/\r?\n\r?\n/", $match[2], 2);
                    $data = isset($sections[1]) ? json_decode(trim($sections[1]), true) : null;
                    $error = $data['error']['innererror']['message'] ?? $data['error']['message'] ?? ('HTTP ' . $match[1]);
                    break;
                }
            }
            if ($error === null && count($m) !== count($bodies)) {
                $error = 'Unexpected $batch response (' . count($m) . ' of ' . count($bodies) . ' results)';
            }
            $out[$key] = ['ok' => $error === null, 'error' => $error];
        }
        return $out;
    }

    /**
     * PATCH one record inside a $batch - plain PATCH only returns "An error has occurred", while the
     * $batch answer carries D365's real Infolog message. $keyPath like "Workers(PersonnelNumber='5430')".
     * Returns ['ok' => bool, 'error' => ?string].
     */
    public function batchUpdate($keyPath, array $body)
    {
        if (!$this->allowWrites) {
            return ['ok' => false, 'error' => "Writes to D365 are disabled for the {$this->environment} environment"];
        }
        $batchId = 'batch_' . bin2hex(random_bytes(8));
        $changesetId = 'changeset_' . bin2hex(random_bytes(8));
        $payload = "--$batchId\r\nContent-Type: multipart/mixed; boundary=$changesetId\r\n\r\n"
            . "--$changesetId\r\nContent-Type: application/http\r\nContent-Transfer-Encoding: binary\r\nContent-ID: 1\r\n\r\n"
            . "PATCH {$this->resourceUrl}/data/" . ltrim($keyPath, '/') . " HTTP/1.1\r\n"
            . "Content-Type: application/json; type=entry\r\nAccept: application/json\r\n\r\n"
            . json_encode($body) . "\r\n"
            . "--$changesetId--\r\n--$batchId--\r\n";

        $res = $this->http('POST', $this->resourceUrl . '/data/$batch', [
            'Authorization: Bearer ' . $this->getToken(),
            'Content-Type: multipart/mixed; boundary=' . $batchId,
            'Accept: multipart/mixed',
            'OData-Version: 4.0',
            'OData-MaxVersion: 4.0',
        ], $payload, true);
        if ($res['status'] === 0) {
            return ['ok' => false, 'error' => $res['error']];
        }
        if (!preg_match('#HTTP/1\.1 (\d{3})[^\r\n]*\r?\n(.*?)(?=\r?\n--|\z)#s', (string)$res['raw'], $m)) {
            return ['ok' => false, 'error' => $res['error'] ?: 'Unexpected $batch response (HTTP ' . $res['status'] . ')'];
        }
        if ((int)$m[1] < 400) {
            return ['ok' => true, 'error' => null];
        }
        $sections = preg_split("/\r?\n\r?\n/", $m[2], 2);
        $data = isset($sections[1]) ? json_decode(trim($sections[1]), true) : null;
        return ['ok' => false, 'error' => $data['error']['innererror']['message'] ?? $data['error']['message'] ?? ('HTTP ' . $m[1])];
    }

    /**
     * Fetch every page of an entity set (follows @odata.nextLink).
     */
    public function getAll($entity, array $query = [], $crossCompany = false, $maxPages = 50)
    {
        $res = $this->get($entity, $query, $crossCompany);
        if ($res['error']) {
            return $res;
        }
        $rows = $res['data']['value'] ?? [];
        $next = $res['data']['@odata.nextLink'] ?? null;
        while ($next && --$maxPages > 0) {
            $page = $this->http('GET', $next, [
                'Authorization: Bearer ' . $this->getToken(),
                'Accept: application/json',
                'OData-Version: 4.0',
                'OData-MaxVersion: 4.0',
            ]);
            if ($page['status'] >= 400 || $page['error']) {
                return ['status' => $page['status'], 'data' => null, 'error' => $page['error'] ?: ('HTTP ' . $page['status'])];
            }
            $rows = array_merge($rows, $page['data']['value'] ?? []);
            $next = $page['data']['@odata.nextLink'] ?? null;
        }
        return ['status' => 200, 'data' => ['value' => $rows], 'error' => null];
    }

    public function request($method, $entity, array $query = [], array $body = null)
    {
        if ($method !== 'GET' && !$this->allowWrites) {
            return ['status' => 0, 'data' => null, 'error' => "Writes to D365 are disabled (ALLOW_WRITES is off for the {$this->environment} environment in d365_config.ini)"];
        }

        $url = $this->resourceUrl . '/data/' . ltrim($entity, '/');
        if ($query) {
            // RFC3986 encodes spaces as %20 - OData filters reject "+"
            $url .= '?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986);
        }

        $headers = [
            'Authorization: Bearer ' . $this->getToken(),
            'Accept: application/json',
            'OData-Version: 4.0',
            'OData-MaxVersion: 4.0',
        ];
        $payload = null;
        if ($body !== null) {
            $headers[] = 'Content-Type: application/json';
            $payload = json_encode($body);
        }

        $res = $this->http($method, $url, $headers, $payload);

        // Token revoked/expired early: retry once with a fresh one
        if ($res['status'] === 401) {
            $headers[0] = 'Authorization: Bearer ' . $this->getToken(true);
            $res = $this->http($method, $url, $headers, $payload);
        }

        if ($res['status'] >= 400 && !$res['error']) {
            $res['error'] = $res['data']['error']['message']
                ?? $res['data']['Message']
                ?? ('HTTP ' . $res['status'] . ($res['status'] === 401 ? ' - app not registered in F&O (Microsoft Entra ID applications) or user has no roles' : ''));
        }
        return $res;
    }

    private function http($method, $url, array $headers, $body = null, $keepRaw = false)
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT        => 60,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);
        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }

        $raw = curl_exec($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = $raw === false ? ('cURL error ' . curl_errno($ch) . ': ' . curl_error($ch)) : null;
        curl_close($ch);

        $data = null;
        if (is_string($raw) && $raw !== '') {
            $data = json_decode($raw, true);
            if ($data === null && $status >= 400) {
                $error = $error ?: ('HTTP ' . $status . ': ' . substr(strip_tags($raw), 0, 300));
            }
        }

        $out = ['status' => $status, 'data' => $data, 'error' => $error];
        if ($keepRaw) {
            $out['raw'] = $raw;
        }
        return $out;
    }
}
