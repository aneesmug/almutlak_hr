<?php
require_once __DIR__ . '/includes/db.php';

function tableApiStartSession(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        @session_start();
    }
}

function tableApiSendJson($payload, int $statusCode = 200): void
{
    if (ob_get_length()) {
        @ob_clean();
    }

    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: *');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

    echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function tableApiSendCsv(array $rows, string $table): void
{
    if (ob_get_length()) {
        @ob_clean();
    }

    http_response_code(200);
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: inline; filename="' . $table . '_export_' . date('Ymd_His') . '.csv"');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

    echo "\xEF\xBB\xBF";

    $output = fopen('php://output', 'w');
    if ($output === false) {
        exit;
    }

    if (empty($rows)) {
        fputcsv($output, ['message']);
        fputcsv($output, ['No data found']);
        fclose($output);
        exit;
    }

    $firstRow = (array) $rows[0];
    fputcsv($output, array_keys($firstRow));

    foreach ($rows as $row) {
        $normalizedRow = [];
        foreach ((array) $row as $value) {
            if (is_array($value) || is_object($value)) {
                $normalizedRow[] = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            } else {
                $normalizedRow[] = $value;
            }
        }

        fputcsv($output, $normalizedRow);
    }

    fclose($output);
    exit;
}

function tableApiError(string $message, int $statusCode = 400, array $extra = []): void
{
    tableApiSendJson(array_merge([
        'status' => 'error',
        'message' => $message,
    ], $extra), $statusCode);
}

function tableApiNormalizeIdentifier(string $value): string
{
    $value = trim($value);
    return preg_match('/^[A-Za-z0-9_]+$/', $value) ? $value : '';
}

function tableApiRequestValue(string $key, $default = null)
{
    if (isset($_POST[$key])) {
        return $_POST[$key];
    }

    if (isset($_GET[$key])) {
        return $_GET[$key];
    }

    return $default;
}

function tableApiBoolParam(string $key, bool $default = false): bool
{
    $value = tableApiRequestValue($key, null);
    if ($value === null) {
        return $default;
    }

    $value = strtolower(trim((string) $value));
    return in_array($value, ['1', 'true', 'yes', 'on'], true);
}

function tableApiLimitParam(int $default = 500): int
{
    $limit = (int) tableApiRequestValue('limit', $default);
    if ($limit < 1) {
        $limit = $default;
    }

    return min($limit, 10000);
}

function tableApiParseJoins(?string $joinsRaw): array
{
    $joins = [];
    $seen = [];
    $joinsRaw = trim((string) $joinsRaw);

    if ($joinsRaw === '') {
        return [];
    }

    $lines = preg_split('/[\r\n,;]+/', $joinsRaw);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '') {
            continue;
        }

        $parts = preg_split('/[|:]/', $line);
        if (count($parts) < 3) {
            continue;
        }

        $joinTable = tableApiNormalizeIdentifier($parts[0]);
        $localColumn = tableApiNormalizeIdentifier($parts[1]);
        $foreignColumn = tableApiNormalizeIdentifier($parts[2]);
        $selectedColumns = [];

        foreach (array_slice($parts, 3) as $extraColumn) {
            $extraColumn = tableApiNormalizeIdentifier($extraColumn);
            if ($extraColumn !== '') {
                $selectedColumns[] = $extraColumn;
            }
        }

        $selectedColumns = array_values(array_unique($selectedColumns));

        if ($joinTable === '' || $localColumn === '' || $foreignColumn === '') {
            continue;
        }

        $key = strtolower($joinTable . '|' . $localColumn . '|' . $foreignColumn . '|' . implode('|', $selectedColumns));
        if (isset($seen[$key])) {
            continue;
        }

        $seen[$key] = true;
        $joins[] = [
            'join_table' => $joinTable,
            'local_column' => $localColumn,
            'foreign_column' => $foreignColumn,
            'selected_columns' => $selectedColumns,
            'source' => 'manual',
        ];
    }

    return $joins;
}

function tableApiSerializeJoins(array $joins): string
{
    $serialized = [];
    foreach ($joins as $join) {
        $item = $join['join_table'] . '|' . $join['local_column'] . '|' . $join['foreign_column'];
        if (!empty($join['selected_columns']) && is_array($join['selected_columns'])) {
            $item .= '|' . implode('|', $join['selected_columns']);
        }
        $serialized[] = $item;
    }

    return implode(';', $serialized);
}

function tableApiBuildToken(string $table, array $joins, int $limit, bool $onlyData, bool $autoJoin, string $customQuerySignature = ''): string
{
    $payload = strtolower(trim($table))
        . '|' . tableApiSerializeJoins($joins)
        . '|' . $limit
        . '|' . ($onlyData ? '1' : '0')
        . '|' . ($autoJoin ? '1' : '0')
        . '|' . strtolower(trim($customQuerySignature));

    return hash_hmac('sha256', $payload, DB_NAME . '|' . DB_USER . '|' . DB_PASS);
}

function tableApiNormalizeSql(string $sql): string
{
    $sql = str_replace(["\r\n", "\r"], "\n", trim($sql));
    $sql = preg_replace('/;+$/', '', $sql);
    return trim($sql);
}

function tableApiStripSqlComments(string $sql): string
{
    $sql = preg_replace('#/\*.*?\*/#s', ' ', $sql);
    $sql = preg_replace('/--[^\n]*$/m', ' ', $sql);
    return trim($sql);
}

function tableApiIsSafeReadOnlyQuery(string $sql): bool
{
    $sql = tableApiStripSqlComments(tableApiNormalizeSql($sql));
    if ($sql === '') {
        return false;
    }

    if (!preg_match('/^(SELECT|WITH)\b/i', $sql)) {
        return false;
    }

    if (preg_match('/\b(INSERT|UPDATE|DELETE|DROP|ALTER|TRUNCATE|CREATE|REPLACE|RENAME|GRANT|REVOKE|CALL|EXEC(?:UTE)?|MERGE|HANDLER|LOCK|UNLOCK)\b/i', $sql)) {
        return false;
    }

    if (preg_match('/\b(INTO\s+OUTFILE|INTO\s+DUMPFILE|LOAD_FILE|LOAD\s+DATA)\b/i', $sql)) {
        return false;
    }

    return true;
}

function tableApiGetQueryCachePath(): string
{
    $cacheDir = __DIR__ . '/cache';
    if (!is_dir($cacheDir)) {
        @mkdir($cacheDir, 0775, true);
    }

    return $cacheDir . '/table_json_api_queries_' . md5(DB_NAME) . '.json';
}

function tableApiLoadQueryCache(): array
{
    $cachePath = tableApiGetQueryCachePath();
    if (!file_exists($cachePath)) {
        return [];
    }

    $decoded = json_decode((string) @file_get_contents($cachePath), true);
    return is_array($decoded) ? $decoded : [];
}

function tableApiSaveQueryCache(array $cache): void
{
    $cachePath = tableApiGetQueryCachePath();
    @file_put_contents(
        $cachePath,
        json_encode($cache, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        LOCK_EX
    );
}

function tableApiStoreCustomQuery(string $sql): string
{
    $sql = tableApiNormalizeSql($sql);
    if ($sql === '') {
        return '';
    }

    $key = substr(hash('sha256', $sql), 0, 32);
    $cache = tableApiLoadQueryCache();
    $cache[$key] = [
        'sql' => $sql,
        'updated_at' => date('Y-m-d H:i:s'),
    ];
    tableApiSaveQueryCache($cache);

    return $key;
}

function tableApiResolveCustomQuery(string $customQueryText = '', string $queryKey = ''): string
{
    $customQueryText = tableApiNormalizeSql($customQueryText);
    if ($customQueryText !== '') {
        return $customQueryText;
    }

    $queryKey = trim($queryKey);
    if ($queryKey === '') {
        return '';
    }

    $cache = tableApiLoadQueryCache();
    return isset($cache[$queryKey]['sql']) ? tableApiNormalizeSql((string) $cache[$queryKey]['sql']) : '';
}

function tableApiTableExists(PDO $pdo, string $table): bool
{
    static $cache = [];
    $key = strtolower($table);

    if (isset($cache[$key])) {
        return $cache[$key];
    }

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table");
    $stmt->execute(['table' => $table]);

    return $cache[$key] = ((int) $stmt->fetchColumn() > 0);
}

function tableApiGetColumns(PDO $pdo, string $table): array
{
    static $cache = [];
    $key = strtolower($table);

    if (isset($cache[$key])) {
        return $cache[$key];
    }

    $stmt = $pdo->prepare("SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table ORDER BY ORDINAL_POSITION");
    $stmt->execute(['table' => $table]);

    return $cache[$key] = $stmt->fetchAll(PDO::FETCH_COLUMN);
}

function tableApiHasColumn(PDO $pdo, string $table, string $column): bool
{
    return in_array($column, tableApiGetColumns($pdo, $table), true);
}

function tableApiIsSensitiveColumn(string $column): bool
{
    $column = strtolower($column);
    $blockedPatterns = [
        'password',
        'token',
        'otp',
        'secret',
        'api_key',
        'email_pass',
    ];

    foreach ($blockedPatterns as $pattern) {
        if (strpos($column, $pattern) !== false) {
            return true;
        }
    }

    return false;
}

function tableApiFilterExistingColumns(PDO $pdo, string $table, array $columns): array
{
    $validColumns = [];

    foreach ($columns as $column) {
        $column = tableApiNormalizeIdentifier((string) $column);
        if ($column === '' || tableApiIsSensitiveColumn($column)) {
            continue;
        }

        if (tableApiHasColumn($pdo, $table, $column)) {
            $validColumns[] = $column;
        }
    }

    return array_values(array_unique($validColumns));
}

function tableApiLooksRelationalColumn(string $column): bool
{
    $column = strtolower($column);
    $ignoreColumns = [
        'id', 'name', 'title', 'status', 'date', 'created_at', 'updated_at', 'email', 'mobile',
        'avatar', 'salary', 'amount', 'note', 'remarks', 'description'
    ];

    if (in_array($column, $ignoreColumns, true)) {
        return false;
    }

    return (bool) preg_match('/(_id$|^dept$|^department$|^dept_id$|^comp_no$|^company_id$|^country$|^country_id$|^bank_name$|^bank_id$|^supervisor_id$|^manager_id$|^emp_id$|^customer_id$|^cust_id$|^location_id$|^machine_id$|^brand_id$|^item_id$|^car_id$|^request_id$)/', $column);
}

function tableApiFindTablesByColumn(PDO $pdo, string $mainTable, string $column): array
{
    static $cache = [];
    $key = strtolower($mainTable . '|' . $column);

    if (isset($cache[$key])) {
        return $cache[$key];
    }

    $matches = [];
    foreach (tableApiGuessRelatedTables($column) as $candidateTable) {
        if ($candidateTable !== ''
            && strtolower($candidateTable) !== strtolower($mainTable)
            && tableApiTableExists($pdo, $candidateTable)
        ) {
            $matches[] = $candidateTable;
        }
    }

    // Dynamic lookup: tables where this same column name is the primary key.
    $stmt = $pdo->prepare(
        "SELECT TABLE_NAME FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND COLUMN_NAME = :col AND COLUMN_KEY = 'PRI'
           AND TABLE_NAME <> :main
         ORDER BY TABLE_NAME LIMIT 3"
    );
    $stmt->execute(['col' => $column, 'main' => $mainTable]);
    foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $candidateTable) {
        $candidateTable = tableApiNormalizeIdentifier((string) $candidateTable);
        if ($candidateTable !== '') {
            $matches[] = $candidateTable;
        }
    }

    return $cache[$key] = array_values(array_unique($matches));
}

function tableApiGetSinglePrimaryKey(PDO $pdo, string $table): string
{
    static $cache = [];
    $key = strtolower($table);

    if (isset($cache[$key])) {
        return $cache[$key];
    }

    $stmt = $pdo->prepare("SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table AND COLUMN_KEY = 'PRI'");
    $stmt->execute(['table' => $table]);
    $pk = $stmt->fetchAll(PDO::FETCH_COLUMN);

    return $cache[$key] = (count($pk) === 1 ? (string) $pk[0] : '');
}

function tableApiGuessRelatedTables(string $column): array
{
    $column = strtolower($column);
    $map = [
        'dept' => ['company_departments', 'department', 'departments'],
        'department' => ['company_departments', 'department', 'departments'],
        'dept_id' => ['company_departments', 'department', 'departments'],
        'emp_id' => ['employees'],
        'employee_id' => ['employees'],
        'user_id' => ['admin_login', 'users'],
        'supervisor_id' => ['employees'],
        'manager_id' => ['employees'],
        'customer_id' => ['customers'],
        'cust_id' => ['customers'],
        'comp_no' => ['company', 'companies'],
        'company_id' => ['company', 'companies'],
        'country' => ['countries', 'country'],
        'country_id' => ['countries', 'country'],
        'bank_name' => ['banks', 'bank'],
        'bank_id' => ['banks', 'bank'],
        'location_id' => ['locations'],
        'machine_id' => ['machines'],
        'brand_id' => ['brands'],
        'item_id' => ['items'],
        'car_id' => ['cars'],
        'request_id' => ['requests', 'smt_request'],
    ];

    $candidates = $map[$column] ?? [];

    if (preg_match('/_id$/', $column)) {
        $base = preg_replace('/_id$/', '', $column);
        $candidates[] = $base;
        $candidates[] = $base . 's';
        $candidates[] = $base . 'es';
    }

    return array_values(array_unique(array_filter(array_map('tableApiNormalizeIdentifier', $candidates))));
}

function tableApiGetPreferredJoinColumns(PDO $pdo, string $joinTable, array $selectedColumns = [], string $source = 'manual'): array
{
    $selectedColumns = tableApiFilterExistingColumns($pdo, $joinTable, $selectedColumns);
    if (!empty($selectedColumns)) {
        return $selectedColumns;
    }

    $allColumns = tableApiGetColumns($pdo, $joinTable);
    if ($source !== 'auto' && $source !== 'foreign_key') {
        return $allColumns;
    }

    $preferredColumns = [
        'id', 'code', 'name', 'name_en', 'name_ar', 'dep_nme', 'dep_nme_ar', 'department_name', 'dept_name', 'fullname',
        'title', 'comp_name', 'comp_name_ar', 'company_name', 'customer_name', 'country_name',
        'bank_name', 'bank_name_ar', 'section_name', 'sponsor', 'sponsor_ar', 'email', 'mobile', 'status'
    ];

    // Case-insensitive match (e.g. BANK_NAME) while keeping the real column name.
    $allColumnsLower = [];
    foreach ($allColumns as $realColumn) {
        $allColumnsLower[strtolower($realColumn)] = $realColumn;
    }

    $columns = [];
    foreach ($preferredColumns as $column) {
        if (isset($allColumnsLower[$column]) && !tableApiIsSensitiveColumn($column)) {
            $columns[] = $allColumnsLower[$column];
        }
    }

    if (empty($columns)) {
        foreach ($allColumns as $column) {
            if (!tableApiIsSensitiveColumn($column)) {
                $columns[] = $column;
            }
            if (count($columns) >= 4) {
                break;
            }
        }
    }

    return array_values(array_unique($columns));
}

function tableApiKnownRelations(): array
{
    // local column => [join table, foreign column, selected columns (optional), allow self join]
    return [
        'dept' => ['department', 'id'],
        'city_id' => ['saudi_cities', 'id'],
        'city' => ['saudi_cities', 'id'],
        'country' => ['countries', 'id'],
        'bank_name' => ['bank', 'id'],
        'comp_no' => ['companies', 'comp_id'],
        'location_id' => ['locations', 'id'],
        'sub_dept_id' => ['sub_departments', 'id'],
        'sectin_nme' => ['section', 'id'],
        'supervisor_id' => ['employees', 'emp_id', ['emp_id', 'name', 'mobile'], true],
    ];
}

function tableApiAddJoinIfValid(PDO $pdo, string $mainTable, array &$joins, array &$seen, string $joinTable, string $localColumn, string $foreignColumn, string $source, array $selectedColumns = [], bool $allowSelf = false): void
{
    if ($joinTable === '' || $localColumn === '' || $foreignColumn === '' || (!$allowSelf && $joinTable === $mainTable)) {
        return;
    }

    if (!tableApiTableExists($pdo, $joinTable)) {
        return;
    }

    if (!tableApiHasColumn($pdo, $mainTable, $localColumn) || !tableApiHasColumn($pdo, $joinTable, $foreignColumn)) {
        return;
    }

    $selectedColumns = tableApiGetPreferredJoinColumns($pdo, $joinTable, $selectedColumns, $source);
    $key = strtolower($joinTable . '|' . $localColumn . '|' . $foreignColumn . '|' . implode('|', $selectedColumns));
    if (isset($seen[$key])) {
        return;
    }

    $seen[$key] = true;
    $joins[] = [
        'join_table' => $joinTable,
        'local_column' => $localColumn,
        'foreign_column' => $foreignColumn,
        'selected_columns' => $selectedColumns,
        'source' => $source,
        'allow_self' => $allowSelf,
    ];
}

function tableApiFindAutoJoins(PDO $pdo, string $mainTable): array
{
    $joins = [];
    $seen = [];
    $mainColumns = tableApiGetColumns($pdo, $mainTable);
    $maxAutoJoins = 25;

    $fkStmt = $pdo->prepare(
        "SELECT COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
         FROM information_schema.KEY_COLUMN_USAGE
         WHERE TABLE_SCHEMA = DATABASE()
           AND TABLE_NAME = :table
           AND REFERENCED_TABLE_NAME IS NOT NULL"
    );
    $fkStmt->execute(['table' => $mainTable]);

    foreach ($fkStmt->fetchAll() as $fk) {
        tableApiAddJoinIfValid(
            $pdo,
            $mainTable,
            $joins,
            $seen,
            (string) $fk['REFERENCED_TABLE_NAME'],
            (string) $fk['COLUMN_NAME'],
            (string) $fk['REFERENCED_COLUMN_NAME'],
            'foreign_key'
        );

        if (count($joins) >= $maxAutoJoins) {
            return $joins;
        }
    }

    foreach (tableApiKnownRelations() as $localColumn => $relation) {
        if (!in_array($localColumn, $mainColumns, true)) {
            continue;
        }

        tableApiAddJoinIfValid(
            $pdo,
            $mainTable,
            $joins,
            $seen,
            $relation[0],
            $localColumn,
            $relation[1],
            'auto',
            $relation[2] ?? [],
            (bool) ($relation[3] ?? false)
        );
    }

    foreach ($mainColumns as $column) {
        if (!tableApiLooksRelationalColumn($column)) {
            continue;
        }

        $candidateTables = array_merge(
            tableApiGuessRelatedTables($column),
            tableApiFindTablesByColumn($pdo, $mainTable, $column)
        );

        foreach (array_values(array_unique($candidateTables)) as $candidateTable) {
            $candidateTable = tableApiNormalizeIdentifier((string) $candidateTable);
            if ($candidateTable === '') {
                continue;
            }

            $foreignColumn = '';
            if (tableApiHasColumn($pdo, $candidateTable, $column)) {
                $foreignColumn = $column;
            } elseif (in_array(strtolower($column), ['comp_no', 'company_id'], true) && tableApiHasColumn($pdo, $candidateTable, 'comp_id')) {
                $foreignColumn = 'comp_id';
            } elseif (tableApiHasColumn($pdo, $candidateTable, 'id')) {
                $foreignColumn = 'id';
            } else {
                $foreignColumn = tableApiGetSinglePrimaryKey($pdo, $candidateTable);
            }

            if ($foreignColumn !== '') {
                tableApiAddJoinIfValid($pdo, $mainTable, $joins, $seen, $candidateTable, $column, $foreignColumn, 'auto');
            }

            if (count($joins) >= $maxAutoJoins) {
                return $joins;
            }
        }
    }

    return $joins;
}

function tableApiBuildCustomQueryResponse(PDO $pdo, string $customQuery, int $limit, bool $onlyData = false): array
{
    $customQuery = tableApiNormalizeSql($customQuery);
    if ($customQuery === '') {
        throw new InvalidArgumentException('Please enter a custom SELECT query.');
    }

    if (!tableApiIsSafeReadOnlyQuery($customQuery)) {
        throw new RuntimeException('Only safe read-only SELECT queries are allowed in Manual SQL Query.');
    }

    $normalizedSql = tableApiStripSqlComments($customQuery);
    $hasLimit = (bool) preg_match('/\blimit\s+\d+(\s*,\s*\d+)?\s*$/i', $normalizedSql);
    $sqlToRun = $customQuery;

    if (!$hasLimit) {
        $sqlToRun .= "\nLIMIT {$limit}";
    }

    try {
        $rows = $pdo->query($sqlToRun)->fetchAll();
    } catch (PDOException $e) {
        if (stripos($e->getMessage(), 'Duplicate column name') !== false) {
            throw new RuntimeException('Your query has duplicate output column names. Please rename repeated fields with a unique alias, for example use `AS sex_text` instead of `AS sex`.');
        }

        throw $e;
    }

    if ($onlyData) {
        return $rows;
    }

    return [
        'status' => 'success',
        'table' => 'custom_query',
        'mode' => 'custom_query',
        'rows_returned' => count($rows),
        'total_rows_in_table' => count($rows),
        'limit' => $limit,
        'auto_join' => false,
        'joins_applied' => [],
        'generated_at' => date('Y-m-d H:i:s'),
        'sql_preview' => $sqlToRun,
        'data' => $rows,
    ];
}

function tableApiBuildResponse(PDO $pdo, string $table, string $joinsRaw, int $limit, bool $onlyData = false, bool $autoJoin = true): array
{
    $table = tableApiNormalizeIdentifier($table);
    if ($table === '') {
        throw new InvalidArgumentException('Please enter a valid table name using letters, numbers, and underscores only.');
    }

    if (!tableApiTableExists($pdo, $table)) {
        throw new RuntimeException('The selected table was not found in the current database.');
    }

    $mainColumns = tableApiGetColumns($pdo, $table);
    if (empty($mainColumns)) {
        throw new RuntimeException('No readable columns were found for the selected table.');
    }

    $manualJoins = tableApiParseJoins($joinsRaw);
    $joins = [];
    $seen = [];

    foreach ($manualJoins as $join) {
        tableApiAddJoinIfValid(
            $pdo,
            $table,
            $joins,
            $seen,
            $join['join_table'],
            $join['local_column'],
            $join['foreign_column'],
            'manual',
            $join['selected_columns'] ?? []
        );
    }

    if ($autoJoin) {
        foreach (tableApiFindAutoJoins($pdo, $table) as $join) {
            tableApiAddJoinIfValid(
                $pdo,
                $table,
                $joins,
                $seen,
                $join['join_table'],
                $join['local_column'],
                $join['foreign_column'],
                $join['source'],
                $join['selected_columns'] ?? [],
                (bool) ($join['allow_self'] ?? false)
            );
        }
    }

    $selectParts = [];
    foreach ($mainColumns as $column) {
        if (tableApiIsSensitiveColumn($column)) {
            continue;
        }

        $selectParts[] = "t0.`{$column}` AS `{$column}`";
    }

    $joinSql = [];
    foreach ($joins as $index => $join) {
        $alias = 'j' . ($index + 1);
        $joinTable = $join['join_table'];
        $localColumn = $join['local_column'];
        $foreignColumn = $join['foreign_column'];
        $columnsToSelect = tableApiGetPreferredJoinColumns(
            $pdo,
            $joinTable,
            $join['selected_columns'] ?? [],
            $join['source'] ?? 'manual'
        );

        $joinSql[] = "LEFT JOIN `{$joinTable}` {$alias} ON t0.`{$localColumn}` = {$alias}.`{$foreignColumn}`";

        foreach ($columnsToSelect as $column) {
            if (tableApiIsSensitiveColumn($column)) {
                continue;
            }

            $columnAlias = $joinTable . '__' . $localColumn . '__' . $column;
            $selectParts[] = "{$alias}.`{$column}` AS `{$columnAlias}`";
        }
    }

    $orderBy = '';
    foreach (['id', 'created_at', 'date', 'payroll_month'] as $preferredOrderColumn) {
        if (in_array($preferredOrderColumn, $mainColumns, true)) {
            $direction = in_array($preferredOrderColumn, ['id', 'created_at'], true) ? 'DESC' : 'ASC';
            $orderBy = " ORDER BY t0.`{$preferredOrderColumn}` {$direction}";
            break;
        }
    }

    $sql = "SELECT\n    " . implode(",\n    ", $selectParts) . "\nFROM `{$table}` t0\n";
    if (!empty($joinSql)) {
        $sql .= implode("\n", $joinSql) . "\n";
    }
    $sql .= $orderBy . "\nLIMIT {$limit}";

    $rows = $pdo->query($sql)->fetchAll();
    $totalRows = (int) $pdo->query("SELECT COUNT(*) FROM `{$table}`")->fetchColumn();

    if ($onlyData) {
        return $rows;
    }

    return [
        'status' => 'success',
        'table' => $table,
        'rows_returned' => count($rows),
        'total_rows_in_table' => $totalRows,
        'limit' => $limit,
        'auto_join' => $autoJoin,
        'joins_applied' => array_values($joins),
        'generated_at' => date('Y-m-d H:i:s'),
        'sql_preview' => $sql,
        'data' => $rows,
    ];
}

function tableApiIsLocalHost(string $host): bool
{
    $host = strtolower(trim(preg_replace('/:\\d+$/', '', $host)));

    if (in_array($host, ['localhost', '127.0.0.1', '::1'], true)) {
        return true;
    }

    return (bool) preg_match('/^(10\\.|192\\.168\\.|172\\.(1[6-9]|2\\d|3[0-1])\\.)/', $host);
}

function tableApiCurrentBaseUrl(?string $forceScheme = null): string
{
    $isHttps = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    $requestScheme = $isHttps ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $path = strtok($_SERVER['REQUEST_URI'] ?? basename(__FILE__), '?');

    if ($path === false || $path === '') {
        $path = '/' . basename(__FILE__);
    }

    if ($forceScheme !== null) {
        $scheme = $forceScheme;
    } else {
        $scheme = tableApiIsLocalHost($host) ? 'http' : $requestScheme;
    }

    return $scheme . '://' . $host . $path;
}

$responseFormat = strtolower(trim((string) tableApiRequestValue('format', '')));
$isApiRequest = in_array($responseFormat, ['json', 'csv'], true);
$tableName = trim((string) tableApiRequestValue('table', ''));
$joinsText = trim((string) tableApiRequestValue('joins', ''));
$customQueryInput = trim((string) tableApiRequestValue('custom_query', ''));
$queryKey = trim((string) tableApiRequestValue('query_key', ''));
$limit = tableApiLimitParam(500);
$autoJoin = tableApiBoolParam('auto_join', true);
$onlyData = tableApiBoolParam('only_data', true);
$excelMode = tableApiBoolParam('excel_mode', false);
$normalizedJoins = tableApiParseJoins($joinsText);
$customQueryText = tableApiResolveCustomQuery($customQueryInput, $queryKey);
$customQuerySignature = $customQueryText !== ''
    ? ($queryKey !== '' ? $queryKey : substr(hash('sha256', $customQueryText), 0, 32))
    : '';

if ($isApiRequest) {
    tableApiStartSession();

    $expectedToken = tableApiBuildToken($tableName, $normalizedJoins, $limit, $onlyData, $autoJoin, $customQuerySignature);
    $providedToken = trim((string) tableApiRequestValue('token', ''));
    $hasSession = isset($_SESSION['auth_user']) && is_array($_SESSION['auth_user']);
    $hasValidToken = ($providedToken !== '' && hash_equals($expectedToken, $providedToken));

    if (!$hasSession && !$hasValidToken) {
        tableApiError('Unauthorized request. Open this page from the system or use the generated secure token.', 401);
    }

    try {
        $forceDataOnly = ($responseFormat === 'csv') ? true : $onlyData;
        $response = $customQueryText !== ''
            ? tableApiBuildCustomQueryResponse($pdo, $customQueryText, $limit, $forceDataOnly)
            : tableApiBuildResponse($pdo, $tableName, $joinsText, $limit, $forceDataOnly, $autoJoin);

        if ($responseFormat === 'csv') {
            $exportName = $customQueryText !== '' ? 'custom_report' : ($tableName !== '' ? $tableName : 'export');
            tableApiSendCsv(is_array($response) ? $response : (($response['data'] ?? [])), $exportName);
        }

        tableApiSendJson($response);
    } catch (Throwable $e) {
        tableApiError($e->getMessage(), 400);
    }
}

require_once __DIR__ . '/includes/session_check.php';

if (!($is_system_admin ?? false) && !($isItTeam ?? false)) {
    http_response_code(403);
    echo '<div style="padding:20px;font-family:Arial,sans-serif;color:#b91c1c;">Access denied. This page is available for administrators and IT team only.</div>';
    exit;
}

$query = mysqli_query($conDB, "SELECT * FROM `admin_login` WHERE `id_iqama`='" . mysqli_real_escape_string($conDB, $username) . "'");
if (mysqli_num_rows($query) == 1) {
    include './includes/avatar_select.php';
}

$previewPayload = null;
$previewError = '';
$generatedApiUrl = '';
$generatedCsvUrl = '';
$generatedBrowserUrl = '';
$generatedExcelFormula = '';
$showSslNote = false;

if ($tableName !== '' || $customQueryText !== '') {
    try {
        $previewPayload = $customQueryText !== ''
            ? tableApiBuildCustomQueryResponse($pdo, $customQueryText, min($limit, 20), false)
            : tableApiBuildResponse($pdo, $tableName, $joinsText, min($limit, 20), false, $autoJoin);

        $savedQueryKey = $customQueryText !== '' ? tableApiStoreCustomQuery($customQueryText) : '';
        $token = tableApiBuildToken($tableName, $normalizedJoins, $limit, $onlyData, $autoJoin, $customQuerySignature);
        $baseParams = [
            'limit' => $limit,
            'auto_join' => $autoJoin ? '1' : '0',
            'only_data' => $onlyData ? '1' : '0',
            'excel_mode' => $excelMode ? '1' : '0',
            'token' => $token,
        ];

        if ($tableName !== '') {
            $baseParams['table'] = $tableName;
        }
        if ($joinsText !== '') {
            $baseParams['joins'] = $joinsText;
        }
        if ($customQueryText !== '') {
            $baseParams['query_key'] = $savedQueryKey !== '' ? $savedQueryKey : $customQuerySignature;
        }

        $generatedApiUrl = tableApiCurrentBaseUrl() . '?' . http_build_query(array_merge($baseParams, ['format' => 'json']));
        $generatedCsvUrl = tableApiCurrentBaseUrl() . '?' . http_build_query(array_merge($baseParams, ['format' => 'csv']));
        $generatedBrowserUrl = tableApiCurrentBaseUrl((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http') . '?' . http_build_query(array_merge($baseParams, ['format' => 'json']));
        $showSslNote = (stripos($generatedApiUrl, 'http://') === 0 && stripos($generatedBrowserUrl, 'https://') === 0);

        $jsonSourceReference = $onlyData ? 'Source' : 'Source[data]';
        $safeJsonUrl = str_replace('"', '""', $generatedApiUrl);
        $generatedExcelFormula = "let\n    Source = Json.Document(Web.Contents(\"{$safeJsonUrl}\")),\n    Data = Table.FromRecords({$jsonSourceReference})\nin\n    Data";
    } catch (Throwable $e) {
        $previewError = $e->getMessage();
    }
}

$siteTitle = get_setting($conDB, 'site_title') ?: 'Al-Mutlak WMS';
?>
<!doctype html>
<html lang="<?= htmlspecialchars($current_lang ?? 'en') ?>" <?= ($is_rtl ?? false) ? 'dir="rtl"' : '' ?>>
<head>
    <meta charset="utf-8" />
    <title><?= htmlspecialchars($siteTitle) ?> - Table JSON API</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta content="Anees Afzal" name="author" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <link rel="shortcut icon" href="<?= htmlspecialchars(get_setting($conDB, 'favicon') ?: '') ?>">
    <link href="assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/icons.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/metismenu.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/style.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/style_dark.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/smart_request.css?v=<?= @filemtime(__DIR__ . '/assets/css/smart_request.css') ?>" rel="stylesheet" type="text/css" />
    <script src="assets/js/modernizr.min.js"></script>
    <style>
        .api-card {
            border-radius: 18px;
            border: 1px solid #dce6ef;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.08);
        }
        .api-url-box,
        .json-preview-box {
            background: #0f172a;
            color: #e2e8f0;
            border-radius: 12px;
            padding: 14px;
            font-family: Consolas, monospace;
            font-size: 12px;
            white-space: pre-wrap;
            word-break: break-word;
        }
        .help-chip {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 999px;
            background: #eff6ff;
            color: #1d4ed8;
            font-weight: 600;
            margin-right: 6px;
            margin-bottom: 6px;
        }
        .api-option-card {
            border: 1px solid #dbe7f3;
            border-radius: 14px;
            background: #f8fbff;
            padding: 14px 16px;
            margin-bottom: 16px;
        }
        .api-option-title {
            font-size: 14px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 4px;
        }
        .api-option-subtitle {
            font-size: 12px;
            color: #64748b;
            margin-bottom: 0;
        }
        .manual-query-panel {
            display: none;
            border: 1px dashed #bcd0e5;
            border-radius: 12px;
            background: #fcfdff;
            padding: 15px;
            margin-bottom: 16px;
        }
        .manual-query-panel.is-visible {
            display: block;
        }
        /* ---------- New GUI ---------- */
        .sr-page .manual-query-panel { padding: 0; border-style: solid; border-color: var(--sr-border); border-radius: 12px; background: var(--sr-surface); }
        .sr-page .api-url-box, .sr-page .json-preview-box { border-radius: 10px; }
        .sr-page .json-preview-box { max-height: 520px; overflow: auto; }
        .tja-switches { display: flex; flex-direction: column; justify-content: flex-end; gap: 6px; }
        .tja-btns { display: flex; flex-wrap: wrap; gap: 8px; margin: 8px 0 16px; }
        .tja-stats { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 12px; margin-bottom: 20px; }
        @media (max-width: 991px) { .tja-stats { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
        .tja-stats .sr-stat { background: var(--sr-surface); }
    </style>
</head>
<body class="enlarged" data-keep-enlarged="true">
    <div id="wrapper">
        <div class="left side-menu">
            <div class="slimscroll-menu" id="remove-scroll">
                <div class="topbar-left">
                    <a href="dashboard.php" class="logo">
                        <span><img src="<?= htmlspecialchars(get_setting($conDB, 'logo') ?: '') ?>" alt="logo" height="22"></span>
                        <i><img src="<?= htmlspecialchars(get_setting($conDB, 'white_logo') ?: '') ?>" alt="logo" height="28"></i>
                    </a>
                </div>
                <?php include './includes/main_menu.php'; ?>
                <div class="clearfix"></div>
            </div>
        </div>

        <div class="content-page">
            <?php include './includes/topbar.php'; ?>

            <div class="content sr-page">
                <div class="container-fluid">
                    <div class="sr-head">
                        <div>
                            <h1>Table JSON API Export</h1>
                            <p>Enter a table name and the system will automatically join common related tables when possible. Use Custom Joins only for special cases.</p>
                        </div>
                        <div class="sr-head-actions">
                            <span class="sr-chip"><i class="mdi mdi-file-excel"></i> Excel Ready</span>
                            <span class="sr-chip"><i class="mdi mdi-code-braces"></i> JSON Response</span>
                            <span class="sr-chip"><i class="mdi mdi-link-variant"></i> Auto Join</span>
                        </div>
                    </div>

                    <form method="post" class="sr-form">
                        <div class="sr-card">
                            <div class="sr-card-head">
                                <div>
                                    <h2 class="sr-card-title"><i class="mdi mdi-database"></i> Report Source</h2>
                                    <span class="sr-card-sub">Use normal table mode for quick exports, or enable advanced SQL for custom reports.</span>
                                </div>
                                <label class="sr-check"><input type="checkbox" id="use_manual_query" name="use_manual_query" value="1" <?= $customQueryText !== '' ? 'checked' : '' ?>> Use advanced SQL query</label>
                            </div>
                            <div class="sr-card-body">
                                <div class="sr-fsec">
                                    <div class="sr-fgrid">
                                        <div class="sr-fcol c-5">
                                            <label for="table">Table Name</label>
                                            <input type="text" class="form-control" id="table" name="table" value="<?= htmlspecialchars($tableName) ?>" placeholder="Example: employees" <?= $customQueryText !== '' ? 'disabled' : '' ?>>
                                        </div>
                                        <div class="sr-fcol c-2">
                                            <label for="limit">Row Limit</label>
                                            <input type="number" class="form-control" id="limit" name="limit" min="1" max="10000" value="<?= (int) $limit ?>">
                                        </div>
                                        <div class="sr-fcol c-5 tja-switches">
                                            <label class="sr-check"><input type="checkbox" id="auto_join" name="auto_join" value="1" <?= $autoJoin ? 'checked' : '' ?> <?= $customQueryText !== '' ? 'disabled' : '' ?>> Auto join related tables</label>
                                            <label class="sr-check"><input type="checkbox" id="only_data" name="only_data" value="1" <?= $onlyData ? 'checked' : '' ?>> Only return data array</label>
                                            <label class="sr-check"><input type="checkbox" id="excel_mode" name="excel_mode" value="1" <?= $excelMode ? 'checked' : '' ?>> Fetch data into Excel</label>
                                        </div>
                                    </div>
                                </div>

                                <div id="manualQueryPanel" class="sr-fsec manual-query-panel <?= $customQueryText !== '' ? 'is-visible' : '' ?>">
                                    <div class="sr-fsec-head"><span><i class="mdi mdi-code-tags"></i> Manual SQL Query (SELECT only)</span></div>
                                    <div class="sr-fgrid">
                                        <div class="sr-fcol c-12">
                                            <textarea class="form-control sr-mono" id="custom_query" name="custom_query" rows="10" placeholder="SELECT employees.emp_id, employees.name FROM employees LIMIT 10"><?= htmlspecialchars($customQueryText) ?></textarea>
                                            <span class="sr-fhint">Advanced mode: if you fill this box, it will override Table Name and Custom Joins in the generated API.</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="sr-fsec mb-0">
                                    <div class="sr-fsec-head"><span><i class="mdi mdi-link-variant"></i> Optional Custom Joins</span></div>
                                    <div class="sr-fgrid">
                                        <div class="sr-fcol c-12">
                                            <textarea class="form-control sr-mono" id="joins" name="joins" rows="4" placeholder="department|dept|id|dep_nme&#10;employees|emp_id|emp_id|name" <?= $customQueryText !== '' ? 'disabled' : '' ?>><?= htmlspecialchars($joinsText) ?></textarea>
                                            <span class="sr-fhint">Special cases only - one per line: <code>join_table|local_column|foreign_column|optional_column_name</code>. Keep empty for automatic joins. Example: <code>department|dept|id|dep_nme</code></span>
                                        </div>
                                    </div>
                                </div>

                                <div class="sr-import-actions">
                                    <button type="submit" class="sr-btn sr-btn-primary"><i class="mdi mdi-database"></i> Generate API Link</button>
                                    <a href="table_json_api.php" class="sr-btn sr-btn-ghost"><i class="mdi mdi-refresh"></i> Reset</a>
                                </div>
                            </div>
                        </div>
                    </form>

                    <?php if ($excelMode): ?>
                        <div class="sr-notice tone-sky">
                            <i class="mdi mdi-information-outline"></i>
                            <div><strong>Excel steps:</strong> Excel → <strong>Data</strong> → <strong>Get Data</strong> → <strong>From Web</strong> → paste the generated CSV or JSON URL below.</div>
                        </div>
                    <?php endif; ?>

                    <?php if ($previewError !== ''): ?>
                        <div class="sr-notice tone-red"><i class="mdi mdi-alert-circle-outline"></i><div><?= htmlspecialchars($previewError) ?></div></div>
                    <?php endif; ?>

                    <?php if ($generatedApiUrl !== ''): ?>
                        <div class="sr-card">
                            <div class="sr-card-head">
                                <h2 class="sr-card-title"><i class="mdi mdi-link"></i> Generated Links</h2>
                            </div>
                            <div class="sr-card-body">
                                <?php if ($excelMode): ?>
                                    <?php if ($showSslNote): ?>
                                        <div class="sr-notice tone-amber">
                                            <i class="mdi mdi-alert"></i>
                                            <div><strong>Excel SSL note:</strong> your local XAMPP <code>https://</code> certificate is not trusted by Excel.
                                            Use the <code>http://</code> link below for Excel import, or install a trusted SSL certificate on Windows.</div>
                                        </div>
                                    <?php endif; ?>

                                    <div class="sr-notice tone-sky">
                                        <i class="mdi mdi-information-outline"></i>
                                        <div><strong>Why Excel shows only <code>Record</code>:</strong> this is normal for JSON lists.
                                        Use the <strong>CSV link</strong> below for direct columns, or paste the <strong>Power Query formula</strong> to auto-expand JSON into a table.</div>
                                    </div>

                                    <div class="sr-field-label">Excel CSV URL (best for direct columns)</div>
                                    <div id="csvUrlBox" class="api-url-box"><?= htmlspecialchars($generatedCsvUrl) ?></div>
                                    <div class="tja-btns">
                                        <button type="button" class="sr-btn sr-btn-sm sr-btn-success" onclick="copyTextFromElement('csvUrlBox')"><i class="mdi mdi-content-copy"></i> Copy CSV Link</button>
                                        <a href="<?= htmlspecialchars($generatedCsvUrl) ?>" target="_blank" class="sr-btn sr-btn-sm"><i class="mdi mdi-open-in-new"></i> Open CSV</a>
                                    </div>

                                    <div class="sr-field-label">Power Query Formula for JSON → Table</div>
                                    <div id="excelFormulaBox" class="api-url-box"><?= htmlspecialchars($generatedExcelFormula) ?></div>
                                    <div class="tja-btns">
                                        <button type="button" class="sr-btn sr-btn-sm sr-btn-primary" onclick="copyTextFromElement('excelFormulaBox')"><i class="mdi mdi-content-copy"></i> Copy Power Query Formula</button>
                                    </div>
                                <?php endif; ?>

                                <div class="sr-field-label">JSON API URL</div>
                                <div id="apiUrlBox" class="api-url-box"><?= htmlspecialchars($generatedApiUrl) ?></div>
                                <div class="tja-btns mb-0">
                                    <button type="button" class="sr-btn sr-btn-sm sr-btn-success" onclick="copyTextFromElement('apiUrlBox')"><i class="mdi mdi-content-copy"></i> Copy JSON Link</button>
                                    <a href="<?= htmlspecialchars($generatedApiUrl) ?>" target="_blank" class="sr-btn sr-btn-sm"><i class="mdi mdi-open-in-new"></i> Open JSON</a>
                                </div>

                                <?php if ($excelMode && $showSslNote): ?>
                                    <div class="sr-field-label mt-3">Current browser URL</div>
                                    <div class="api-url-box"><?= htmlspecialchars($generatedBrowserUrl) ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (is_array($previewPayload)): ?>
                        <div class="tja-stats">
                            <div class="sr-stat is-sky">
                                <div class="sr-stat-label">Table <i class="mdi mdi-table"></i></div>
                                <div class="sr-stat-value sr-mono"><?= htmlspecialchars($previewPayload['table']) ?></div>
                            </div>
                            <div class="sr-stat">
                                <div class="sr-stat-label">Preview Rows <i class="mdi mdi-format-list-bulleted"></i></div>
                                <div class="sr-stat-value"><?= (int) $previewPayload['rows_returned'] ?></div>
                            </div>
                            <div class="sr-stat is-green">
                                <div class="sr-stat-label">Total Rows in Table <i class="mdi mdi-database"></i></div>
                                <div class="sr-stat-value"><?= (int) $previewPayload['total_rows_in_table'] ?></div>
                            </div>
                            <div class="sr-stat is-amber">
                                <div class="sr-stat-label">Joins Applied <i class="mdi mdi-link-variant"></i></div>
                                <div class="sr-stat-value"><?= count($previewPayload['joins_applied']) ?></div>
                            </div>
                        </div>

                        <div class="sr-card">
                            <div class="sr-card-head">
                                <h2 class="sr-card-title"><i class="mdi mdi-link-variant"></i> Applied Joins</h2>
                            </div>
                            <?php if (!empty($previewPayload['joins_applied'])): ?>
                                <div class="sr-table-wrap sr-table-scroll">
                                    <table class="sr-table">
                                        <thead><tr><th>Join table</th><th>Local column</th><th>Foreign column</th><th>Source</th></tr></thead>
                                        <tbody>
                                            <?php foreach ($previewPayload['joins_applied'] as $join): ?>
                                                <tr>
                                                    <td><code><?= htmlspecialchars($join['join_table']) ?></code></td>
                                                    <td><code><?= htmlspecialchars($join['local_column']) ?></code></td>
                                                    <td><code><?= htmlspecialchars($join['foreign_column']) ?></code></td>
                                                    <td><span class="sr-pill sr-pill-xs tone-slate"><?= htmlspecialchars($join['source']) ?></span></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="sr-empty"><i class="mdi mdi-link-variant-off"></i>No joins were applied. The API will return the main table only.</div>
                            <?php endif; ?>
                        </div>

                        <div class="sr-card">
                            <div class="sr-card-head">
                                <h2 class="sr-card-title"><i class="mdi mdi-code-braces"></i> JSON Preview</h2>
                                <span class="sr-card-sub">first 20 rows max</span>
                            </div>
                            <div class="sr-card-body">
                                <div class="json-preview-box"><?= htmlspecialchars(json_encode($previewPayload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?></div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/js/jquery.min.js"></script>
    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/metisMenu.min.js"></script>
    <script src="assets/js/waves.js"></script>
    <script src="assets/js/jquery.slimscroll.js"></script>
    <script src="assets/js/jquery.core.js"></script>
    <script src="assets/js/jquery.app.js"></script>
    <script>
        function copyTextFromElement(elementId) {
            var box = document.getElementById(elementId);
            if (!box) {
                return;
            }

            var text = box.innerText || box.textContent;
            navigator.clipboard.writeText(text).then(function () {
                alert('Copied successfully.');
            }).catch(function () {
                alert('Unable to copy automatically. Please copy the text manually.');
            });
        }

        function toggleManualQueryMode() {
            var toggle = document.getElementById('use_manual_query');
            var panel = document.getElementById('manualQueryPanel');
            var tableInput = document.getElementById('table');
            var autoJoinInput = document.getElementById('auto_join');
            var joinsInput = document.getElementById('joins');

            if (!toggle || !panel) {
                return;
            }

            var enabled = !!toggle.checked;
            panel.classList.toggle('is-visible', enabled);

            if (tableInput) {
                tableInput.disabled = enabled;
            }
            if (autoJoinInput) {
                autoJoinInput.disabled = enabled;
            }
            if (joinsInput) {
                joinsInput.disabled = enabled;
            }
        }

        document.addEventListener('DOMContentLoaded', function () {
            var toggle = document.getElementById('use_manual_query');
            if (toggle) {
                toggleManualQueryMode();
                toggle.addEventListener('change', toggleManualQueryMode);
            }
        });
    </script>
</body>
</html>
