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
//   ['op' => 'add_index', 'table' => 't', 'index' => [name, unique, columns => [[name, sub_part], ...]]]
//        A UNIQUE index on a table that already holds duplicates is added by copy-swap:
//        new table with the key, INSERT IGNORE copy (keeps the earliest row per key),
//        RENAME swap, catch-up of rows that arrived meanwhile. The old table is KEPT as
//        `<table>__bak_<timestamp>` - nothing is dropped.

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
            case 'add_index':
                [$def, $err] = dbss_index_def((array) ($op['index'] ?? []));
                if ($err) {
                    return [null, $err];
                }
                return ["ALTER TABLE $t ADD $def;", null];
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

    // "UNIQUE KEY `name` (`a`, `b`(20))" from [name, unique, columns => [[name, sub_part]]]
    function dbss_index_def(array $idx): array
    {
        $name = $idx['name'] ?? '';
        $cols = $idx['columns'] ?? [];
        if (!dbss_valid_ident($name) || strcasecmp($name, 'PRIMARY') === 0) {
            return [null, 'Invalid index name.'];
        }
        if (!is_array($cols) || !$cols || count($cols) > 16) {
            return [null, 'Index has no columns.'];
        }
        $parts = [];
        foreach ($cols as $c) {
            $cn = is_array($c) ? ($c['name'] ?? '') : $c;
            $sub = is_array($c) ? ($c['sub_part'] ?? null) : null;
            if (!dbss_valid_ident($cn)) {
                return [null, 'Invalid index column.'];
            }
            $parts[] = dbss_ident($cn) . ($sub !== null && $sub !== '' ? '(' . (int) $sub . ')' : '');
        }
        return [(!empty($idx['unique']) ? 'UNIQUE KEY ' : 'KEY ') . dbss_ident($name) . ' (' . implode(', ', $parts) . ')', null];
    }

    // Indexes of a table (PRIMARY excluded): name => [name, unique, columns]
    function dbss_table_indexes(mysqli $db, string $table): array
    {
        $out = [];
        $res = @mysqli_query($db, 'SHOW INDEX FROM ' . dbss_ident($table));
        while ($res && ($r = mysqli_fetch_assoc($res))) {
            if ($r['Key_name'] === 'PRIMARY') {
                continue;
            }
            $n = $r['Key_name'];
            $out[$n] = $out[$n] ?? ['name' => $n, 'unique' => (int) $r['Non_unique'] === 0, 'columns' => []];
            $out[$n]['columns'][(int) $r['Seq_in_index']] = ['name' => $r['Column_name'], 'sub_part' => $r['Sub_part']];
        }
        foreach ($out as &$i) {
            ksort($i['columns']);
            $i['columns'] = array_values($i['columns']);
        }
        return $out;
    }

    // Runs SQL, returns [ok, error]
    function dbss_exec(mysqli $db, string $sql): array
    {
        try {
            $ok = (bool) @mysqli_query($db, $sql);
            return [$ok, $ok ? null : mysqli_error($db)];
        } catch (Throwable $e) {
            return [false, $e->getMessage()];
        }
    }

    function dbss_scalar(mysqli $db, string $sql)
    {
        try {
            $res = @mysqli_query($db, $sql);
            $row = $res ? mysqli_fetch_row($res) : null;
            return $row ? $row[0] : null;
        } catch (Throwable $e) {
            return null;
        }
    }

    // Adds an index. For a UNIQUE index whose columns already hold duplicates, rebuilds the
    // table by copy-swap instead of failing. Returns a list of result rows.
    function dbss_add_index(mysqli $db, array $op): array
    {
        $table = (string) $op['table'];
        $t = dbss_ident($table);
        $idx = (array) ($op['index'] ?? []);
        [$def, $err] = dbss_index_def($idx);
        if ($err) {
            return [['ok' => false, 'sql' => null, 'error' => $err, 'table' => $table]];
        }
        if (isset(dbss_table_indexes($db, $table)[$idx['name']])) {
            return [['ok' => true, 'sql' => "-- `$table` already has index `{$idx['name']}`", 'error' => null, 'table' => $table]];
        }
        $plain = "ALTER TABLE $t ADD $def;";
        if (empty($idx['unique'])) {
            [$ok, $e] = dbss_exec($db, $plain);
            return [['ok' => $ok, 'sql' => $plain, 'error' => $e, 'table' => $table]];
        }

        $colList = implode(', ', array_map(fn ($c) => dbss_ident(is_array($c) ? $c['name'] : $c), $idx['columns']));
        $total = (int) dbss_scalar($db, "SELECT COUNT(*) FROM $t");
        $distinct = (int) dbss_scalar($db, "SELECT COUNT(*) FROM (SELECT 1 FROM $t GROUP BY $colList) d");
        if ($total === $distinct) {
            [$ok, $e] = dbss_exec($db, $plain);
            return [['ok' => $ok, 'sql' => $plain, 'error' => $e, 'table' => $table]];
        }

        // Duplicates present -> copy-swap
        @set_time_limit(0);
        $results = [['ok' => true, 'sql' => "-- `$table`: " . number_format($total) . ' rows, ' . number_format($distinct) . ' unique - removing ' . number_format($total - $distinct) . ' duplicate rows', 'error' => null, 'table' => $table]];
        $suffix = '__bak_' . date('YmdHis');
        $newName = substr($table, 0, 64 - 7) . '__dedup';
        $bakName = substr($table, 0, 64 - strlen($suffix)) . $suffix;
        $n = dbss_ident($newName);
        $b = dbss_ident($bakName);

        $pkRes = @mysqli_query($db, "SHOW KEYS FROM $t WHERE Key_name = 'PRIMARY'");
        $pkCols = [];
        while ($pkRes && ($r = mysqli_fetch_assoc($pkRes))) {
            $pkCols[] = $r['Column_name'];
        }
        $pk = count($pkCols) === 1 ? dbss_ident($pkCols[0]) : null;
        $maxPk = $pk ? dbss_scalar($db, "SELECT MAX($pk) FROM $t") : null;

        $steps = [
            "DROP TABLE IF EXISTS $n;",
            "CREATE TABLE $n LIKE $t;",
            "ALTER TABLE $n ADD $def;",
            // Earliest row wins for each key (lowest primary key first)
            "INSERT IGNORE INTO $n SELECT * FROM $t" . ($pk ? ($maxPk !== null ? " WHERE $pk <= " . (int) $maxPk : '') . " ORDER BY $pk" : '') . ';',
            "RENAME TABLE $t TO $b, $n TO $t;",
        ];
        if ($pk && $maxPk !== null) {
            // Rows written to the old table while the copy ran
            $steps[] = "INSERT IGNORE INTO $t SELECT * FROM $b WHERE $pk > " . (int) $maxPk . " ORDER BY $pk;";
        }
        foreach ($steps as $sql) {
            [$ok, $e] = dbss_exec($db, $sql);
            $results[] = ['ok' => $ok, 'sql' => $sql, 'error' => $e, 'table' => $table];
            if (!$ok) {
                if (strpos($sql, 'RENAME') === false) {
                    dbss_exec($db, "DROP TABLE IF EXISTS $n;");
                }
                return $results;
            }
        }
        $after = (int) dbss_scalar($db, "SELECT COUNT(*) FROM $t");
        $results[] = ['ok' => true, 'sql' => "-- `$table` now has " . number_format($after) . " rows; the original table is kept as `$bakName` (drop it once verified)", 'error' => null, 'table' => $table];
        return $results;
    }

    // Preview text for add_index (no DB access needed beyond the plain statement)
    function dbss_add_index_preview(array $op): array
    {
        [$sql, $err] = dbss_build_sql($op);
        if ($err) {
            return [null, $err];
        }
        if (empty($op['index']['unique'])) {
            return [$sql, null];
        }
        return [$sql . "
-- if duplicates exist: rebuilt by copy-swap (CREATE ... LIKE, INSERT IGNORE copy keeping the earliest row per key,
--   RENAME swap, catch-up of new rows). The original table is kept as `" . $op['table'] . "__bak_<timestamp>`.", null];
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
            $op = (array) $op;
            $table = (string) ($op['table'] ?? '');
            if (($op['op'] ?? '') === 'add_index') {
                if (!dbss_valid_ident($table) || !dbss_table_exists($db, $table)) {
                    $results[] = ['ok' => false, 'sql' => null, 'error' => "Table `$table` does not exist.", 'table' => $table];
                    continue;
                }
                $results = array_merge($results, dbss_add_index($db, $op));
                continue;
            }
            [$sql, $err] = dbss_build_sql($op);
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
