<?php
// Shared by ajaxDbImportRemote.php (local side) and includes/api/db_apply_schema.php
// (live side): turns a structured, whitelisted schema operation into one ALTER/CREATE
// statement and runs it. SQL is ALWAYS built here from column metadata read out of
// information_schema - never taken as free text from the browser.
//
// Operation shape:
//   ['op' => 'add',    'table' => 't', 'column' => [name, type, nullable, default, extra], 'after' => 'col'|null]
//   ['op' => 'modify', 'table' => 't', 'column' => [...]]
//   ['op' => 'drop',   'table' => 't', 'column' => ['name' => 'c']]
//   ['op' => 'create', 'table' => 't', 'create_sql' => 'CREATE TABLE `t` (...)']   (local side only)

if (!function_exists('dbss_ident')) {

    function dbss_valid_ident($name): bool
    {
        return is_string($name) && $name !== '' && strlen($name) <= 64 && preg_match('/^[A-Za-z0-9_$]+$/', $name) === 1;
    }

    function dbss_ident(string $name): string
    {
        return '`' . str_replace('`', '``', $name) . '`';
    }

    // COLUMN_TYPE as information_schema reports it: int(11) unsigned, varchar(255),
    // decimal(10,2), enum('a','b'), datetime, ... Nothing else gets through.
    function dbss_valid_type($type): bool
    {
        if (!is_string($type) || $type === '' || strlen($type) > 2000) {
            return false;
        }
        return preg_match("/^[a-z]+(\((?:[0-9, ]+|(?:'(?:[^'\\\\]|''|\\\\.)*'(?:\s*,\s*)?)+)\))?(\s+(unsigned|signed|zerofill))*$/i", $type) === 1;
    }

    // DEFAULT clause from information_schema COLUMN_DEFAULT. MariaDB 10.2+ reports
    // string defaults already quoted ('abc'), NULL as the word NULL and expressions as
    // current_timestamp(); MySQL reports the raw value. Both are handled.
    function dbss_default_sql($default, bool $nullable): ?string
    {
        if ($default === null) {
            return $nullable ? 'DEFAULT NULL' : '';
        }
        $d = (string) $default;
        if (strcasecmp($d, 'NULL') === 0) {
            return 'DEFAULT NULL';
        }
        if (preg_match("/^'(?:[^'\\\\]|''|\\\\.)*'$/s", $d)) {
            return 'DEFAULT ' . $d;
        }
        if (preg_match('/^(current_timestamp|now|localtimestamp|localtime)(\(\d*\))?$/i', $d)) {
            return 'DEFAULT ' . $d;
        }
        if (preg_match('/^-?\d+(\.\d+)?$/', $d)) {
            return 'DEFAULT ' . $d;
        }
        if (preg_match('/^b\'[01]*\'$/i', $d)) {
            return 'DEFAULT ' . $d;
        }
        if (preg_match('/[()]/', $d)) {
            return null; // other expression defaults are not reproduced
        }
        return "DEFAULT '" . str_replace(["\\", "'"], ["\\\\", "''"], $d) . "'";
    }

    // Only auto_increment and ON UPDATE ... are carried over. MySQL 8's
    // DEFAULT_GENERATED is a marker, not syntax; generated columns are refused.
    function dbss_extra_sql($extra): ?string
    {
        $extra = trim((string) $extra);
        if ($extra === '') {
            return '';
        }
        if (preg_match('/\b(VIRTUAL|STORED|PERSISTENT)\b/i', $extra)) {
            return null;
        }
        $parts = [];
        if (stripos($extra, 'auto_increment') !== false) {
            $parts[] = 'AUTO_INCREMENT';
        }
        if (preg_match('/on update (current_timestamp(\(\d*\))?)/i', $extra, $m)) {
            $parts[] = 'ON UPDATE ' . $m[1];
        }
        return implode(' ', $parts);
    }

    // Column definition without the position clause. Returns [sql, error].
    function dbss_column_def(array $col): array
    {
        $name = $col['name'] ?? '';
        $type = $col['type'] ?? '';
        if (!dbss_valid_ident($name)) {
            return [null, 'Invalid column name.'];
        }
        if (!dbss_valid_type($type)) {
            return [null, "Unsupported column type for `$name`: $type"];
        }
        $nullable = !empty($col['nullable']);
        $default = dbss_default_sql($col['default'] ?? null, $nullable);
        if ($default === null) {
            return [null, "Default value of `$name` is an expression that can't be copied automatically."];
        }
        $extra = dbss_extra_sql($col['extra'] ?? '');
        if ($extra === null) {
            return [null, "`$name` is a generated column - create it manually."];
        }
        $sql = dbss_ident($name) . ' ' . $type . ($nullable ? ' NULL' : ' NOT NULL');
        if ($default !== '') {
            $sql .= ' ' . $default;
        }
        if ($extra !== '') {
            $sql .= ' ' . $extra;
        }
        return [$sql, null];
    }

    // Returns [sql, error] for one operation.
    function dbss_build_sql(array $op): array
    {
        $table = $op['table'] ?? '';
        if (!dbss_valid_ident($table)) {
            return [null, 'Invalid table name.'];
        }
        $t = dbss_ident($table);
        switch ($op['op'] ?? '') {
            case 'add':
                [$def, $err] = dbss_column_def((array) ($op['column'] ?? []));
                if ($err) {
                    return [null, $err];
                }
                $after = $op['after'] ?? null;
                if ($after !== null && !dbss_valid_ident($after)) {
                    return [null, 'Invalid AFTER column.'];
                }
                return ["ALTER TABLE $t ADD COLUMN $def " . ($after !== null ? 'AFTER ' . dbss_ident($after) : 'FIRST') . ';', null];
            case 'modify':
                [$def, $err] = dbss_column_def((array) ($op['column'] ?? []));
                if ($err) {
                    return [null, $err];
                }
                return ["ALTER TABLE $t MODIFY COLUMN $def;", null];
            case 'drop':
                $name = $op['column']['name'] ?? '';
                if (!dbss_valid_ident($name)) {
                    return [null, 'Invalid column name.'];
                }
                return ["ALTER TABLE $t DROP COLUMN " . dbss_ident($name) . ';', null];
            case 'create':
                $sql = trim((string) ($op['create_sql'] ?? ''));
                $prefix = 'CREATE TABLE ' . $t . ' (';
                if (stripos($sql, $prefix) !== 0) {
                    return [null, 'Unexpected CREATE TABLE statement.'];
                }
                return [rtrim($sql, "; \n\r\t") . ';', null];
        }
        return [null, 'Unknown operation.'];
    }

    function dbss_table_exists(mysqli $db, string $table): bool
    {
        $stmt = mysqli_prepare($db, "SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?");
        mysqli_stmt_bind_param($stmt, 's', $table);
        mysqli_stmt_execute($stmt);
        $found = (bool) mysqli_fetch_row(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);
        return $found;
    }

    // Runs each operation as its own single statement (mysqli_query never runs more
    // than one statement). DDL auto-commits, so every op reports its own outcome.
    function dbss_apply(mysqli $db, array $ops): array
    {
        $results = [];
        foreach ($ops as $op) {
            [$sql, $err] = dbss_build_sql((array) $op);
            $table = (string) ($op['table'] ?? '');
            if ($err === null && ($op['op'] ?? '') !== 'create' && !dbss_table_exists($db, $table)) {
                $err = "Table `$table` does not exist.";
            }
            if ($err !== null) {
                $results[] = ['ok' => false, 'sql' => $sql, 'error' => $err, 'table' => $table];
                continue;
            }
            try {
                $ok = (bool) @mysqli_query($db, $sql);
                $error = $ok ? null : mysqli_error($db);
            } catch (Throwable $e) {
                $ok = false;
                $error = $e->getMessage();
            }
            $results[] = ['ok' => $ok, 'sql' => $sql, 'error' => $error, 'table' => $table];
        }
        return $results;
    }
}
