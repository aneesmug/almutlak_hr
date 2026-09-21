<?php
/**
 * Dynamic Excel/CSV import: pick any table, map spreadsheet columns to table columns, import.
 * Actions (POST ajaxType, or GET ?ajaxType=downloadTemplate):
 *   listTables, getColumns, downloadTemplate, uploadFile, runImport (dry_run=1 validates only)
 * Access: system admin, or Special Access 'access_import_excel_dynamic'.
 * Sensitive tables (users, settings, access) are importable by system admins only.
 */
require_once __DIR__ . '/../session_check.php';
require_once __DIR__ . '/../special_access_helper.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as XlDate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

const DYNIMP_MAX_BYTES = 10 * 1024 * 1024;
const DYNIMP_MAX_ROWS  = 50000;
const DYNIMP_BLOCKED_TABLES = [
    'admin_login', 'app_settings', 'emp_temp_role_assignments', 'user_special_access',
    'sessions', 'password_resets', 'api_tokens', 'login_attempts',
];

$ajaxType = $_POST['ajaxType'] ?? $_GET['ajaxType'] ?? '';

$canUse = $is_system_admin
    || user_has_special_access($conDB, $empid ?? '', 'access_import_excel_dynamic', $user_role ?? '', $actual_user_type ?? ($user_type ?? ''), $is_system_admin ?? false);

function dynimp_json(array $payload, int $code = 200): void
{
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

if (!$canUse) {
    dynimp_json(['status' => 'error', 'message' => 'You do not have access to the dynamic import tool.'], 403);
}
if (session_status() !== PHP_SESSION_ACTIVE) {
    @session_start();
}

function dynimp_all_tables(mysqli $conn): array
{
    $tables = [];
    $res = $conn->query('SHOW FULL TABLES WHERE Table_type = "BASE TABLE"');
    while ($res && ($r = $res->fetch_row())) {
        $tables[] = $r[0];
    }
    return $tables;
}

function dynimp_allowed_tables(mysqli $conn, bool $isAdmin): array
{
    $tables = dynimp_all_tables($conn);
    if (!$isAdmin) {
        $tables = array_values(array_filter($tables, fn($t) => !in_array(strtolower($t), DYNIMP_BLOCKED_TABLES, true)));
    }
    return $tables;
}

function dynimp_require_table(mysqli $conn, string $table, bool $isAdmin): string
{
    if (!in_array($table, dynimp_allowed_tables($conn, $isAdmin), true)) {
        dynimp_json(['status' => 'error', 'message' => 'Table not found or not allowed.'], 400);
    }
    return $table;
}

function dynimp_columns(mysqli $conn, string $table): array
{
    $cols = [];
    $res = $conn->query('SHOW FULL COLUMNS FROM `' . str_replace('`', '``', $table) . '`');
    while ($res && ($r = $res->fetch_assoc())) {
        $type = strtolower($r['Type']);
        $kind = 'text';
        if (preg_match('/^(tinyint|smallint|mediumint|int|bigint)/', $type)) $kind = 'int';
        elseif (preg_match('/^(decimal|float|double|numeric)/', $type)) $kind = 'decimal';
        elseif (str_starts_with($type, 'datetime') || str_starts_with($type, 'timestamp')) $kind = 'datetime';
        elseif (str_starts_with($type, 'date')) $kind = 'date';
        elseif (str_starts_with($type, 'time')) $kind = 'time';
        $cols[] = [
            'name'     => $r['Field'],
            'type'     => $r['Type'],
            'kind'     => $kind,
            'nullable' => $r['Null'] === 'YES',
            'key'      => $r['Key'],
            'default'  => $r['Default'],
            'auto'     => stripos($r['Extra'], 'auto_increment') !== false,
            'generated'=> stripos($r['Extra'], 'GENERATED') !== false,
            'required' => $r['Null'] === 'NO' && $r['Default'] === null && stripos($r['Extra'], 'auto_increment') === false && stripos($r['Extra'], 'GENERATED') === false,
        ];
    }
    return $cols;
}

/** Convert one spreadsheet cell into a value for a DB column. Returns [value, errorOrNull]. */
function dynimp_convert($raw, array $col): array
{
    if (is_string($raw)) $raw = trim($raw);
    if ($raw === null || $raw === '') {
        if ($col['nullable']) return [null, null];
        if ($col['kind'] === 'text') return ['', null];
        return [null, 'empty value for NOT NULL column'];
    }
    switch ($col['kind']) {
        case 'int':
        case 'decimal':
            if (is_string($raw)) $raw = str_replace([',', ' '], '', $raw);
            if (!is_numeric($raw)) return [null, 'not a number: ' . $raw];
            return [$col['kind'] === 'int' ? (string)(int)round((float)$raw) : (string)$raw, null];
        case 'date':
        case 'datetime':
            if (is_numeric($raw)) {
                $dt = XlDate::excelToDateTimeObject((float)$raw);
                return [$dt->format($col['kind'] === 'date' ? 'Y-m-d' : 'Y-m-d H:i:s'), null];
            }
            $ts = strtotime((string)$raw);
            if ($ts === false) return [null, 'invalid date: ' . $raw];
            return [date($col['kind'] === 'date' ? 'Y-m-d' : 'Y-m-d H:i:s', $ts), null];
        case 'time':
            if (is_numeric($raw) && (float)$raw < 1) {
                return [gmdate('H:i:s', (int)round((float)$raw * 86400)), null];
            }
            return [(string)$raw, null];
        default:
            if (is_float($raw) && floor($raw) === $raw) $raw = (string)(int)$raw; // 12345.0 -> "12345"
            return [(string)$raw, null];
    }
}

function dynimp_read_sheet(string $path): array
{
    $reader = IOFactory::createReaderForFile($path);
    $reader->setReadDataOnly(true);
    $spreadsheet = $reader->load($path);
    $rows = $spreadsheet->getActiveSheet()->toArray(null, true, false, false);
    $spreadsheet->disconnectWorksheets();
    return $rows;
}

switch ($ajaxType) {
    case 'listTables':
        dynimp_json(['status' => 'success', 'tables' => dynimp_allowed_tables($conDB, $is_system_admin)]);

    case 'getColumns':
        $table = dynimp_require_table($conDB, (string)($_POST['table'] ?? ''), $is_system_admin);
        dynimp_json(['status' => 'success', 'columns' => dynimp_columns($conDB, $table)]);

    case 'downloadTemplate':
        $table = dynimp_require_table($conDB, (string)($_GET['table'] ?? $_POST['table'] ?? ''), $is_system_admin);
        $wanted = array_filter(explode(',', (string)($_GET['columns'] ?? '')));
        $cols = array_values(array_filter(dynimp_columns($conDB, $table), fn($c) => !$c['generated'] && (!$wanted || in_array($c['name'], $wanted, true))));
        $ss = new Spreadsheet();
        $ws = $ss->getActiveSheet();
        foreach ($cols as $i => $c) {
            $ws->setCellValue(Coordinate::stringFromColumnIndex($i + 1) . '1', $c['name']);
        }
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="template_' . preg_replace('/[^A-Za-z0-9_]/', '', $table) . '.xlsx"');
        (new Xlsx($ss))->save('php://output');
        exit;

    case 'uploadFile':
        if (empty($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            dynimp_json(['status' => 'error', 'message' => 'File upload failed.'], 400);
        }
        if ($_FILES['file']['size'] > DYNIMP_MAX_BYTES) {
            dynimp_json(['status' => 'error', 'message' => 'File is larger than 10 MB.'], 400);
        }
        $ext = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['xlsx', 'xls', 'csv'], true)) {
            dynimp_json(['status' => 'error', 'message' => 'Only .xlsx, .xls or .csv files are allowed.'], 400);
        }
        $token = bin2hex(random_bytes(16));
        $dest = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'dynimp_' . $token . '.' . $ext;
        if (!move_uploaded_file($_FILES['file']['tmp_name'], $dest)) {
            dynimp_json(['status' => 'error', 'message' => 'Could not store the uploaded file.'], 500);
        }
        try {
            $rows = dynimp_read_sheet($dest);
        } catch (Throwable $e) {
            @unlink($dest);
            dynimp_json(['status' => 'error', 'message' => 'Could not read the file: ' . $e->getMessage()], 400);
        }
        if (count($rows) < 2) {
            @unlink($dest);
            dynimp_json(['status' => 'error', 'message' => 'The file needs a header row and at least one data row.'], 400);
        }
        if (count($rows) - 1 > DYNIMP_MAX_ROWS) {
            @unlink($dest);
            dynimp_json(['status' => 'error', 'message' => 'Too many rows (max ' . DYNIMP_MAX_ROWS . ').'], 400);
        }
        $_SESSION['dynimp_files'][$token] = $dest;
        $headers = array_map(fn($h) => trim((string)$h), $rows[0]);
        dynimp_json([
            'status'     => 'success',
            'token'      => $token,
            'headers'    => $headers,
            'total_rows' => count($rows) - 1,
            'sample'     => array_slice($rows, 1, 5),
        ]);

    case 'runImport':
        $table  = dynimp_require_table($conDB, (string)($_POST['table'] ?? ''), $is_system_admin);
        $token  = (string)($_POST['token'] ?? '');
        $path   = $_SESSION['dynimp_files'][$token] ?? null;
        if (!$path || !is_file($path)) {
            dynimp_json(['status' => 'error', 'message' => 'Uploaded file expired. Upload it again.'], 400);
        }
        $mapping = json_decode((string)($_POST['mapping'] ?? '{}'), true); // excelIndex => dbColumn
        $mode    = (string)($_POST['mode'] ?? 'insert');
        $keyCol  = (string)($_POST['key_column'] ?? '');
        $dryRun  = ($_POST['dry_run'] ?? '0') === '1';
        $onError = ($_POST['on_error'] ?? 'skip') === 'abort' ? 'abort' : 'skip';
        if (!in_array($mode, ['insert', 'insert_ignore', 'upsert', 'update'], true)) {
            dynimp_json(['status' => 'error', 'message' => 'Invalid import mode.'], 400);
        }
        if (!is_array($mapping) || !$mapping) {
            dynimp_json(['status' => 'error', 'message' => 'Map at least one column.'], 400);
        }

        $colsByName = [];
        foreach (dynimp_columns($conDB, $table) as $c) $colsByName[$c['name']] = $c;
        $map = []; // excelIndex => column meta
        $used = [];
        foreach ($mapping as $idx => $colName) {
            if ($colName === '' || $colName === null) continue;
            if (!isset($colsByName[$colName]) || $colsByName[$colName]['generated']) {
                dynimp_json(['status' => 'error', 'message' => 'Unknown column: ' . $colName], 400);
            }
            if (isset($used[$colName])) {
                dynimp_json(['status' => 'error', 'message' => 'Column mapped twice: ' . $colName], 400);
            }
            $used[$colName] = true;
            $map[(int)$idx] = $colsByName[$colName];
        }
        if (!$map) dynimp_json(['status' => 'error', 'message' => 'Map at least one column.'], 400);

        if ($mode === 'update') {
            if (!isset($used[$keyCol])) {
                dynimp_json(['status' => 'error', 'message' => 'Pick a key column that is also mapped.'], 400);
            }
            if (count($map) < 2) dynimp_json(['status' => 'error', 'message' => 'Map at least one column besides the key.'], 400);
        } else {
            foreach ($colsByName as $n => $c) {
                if ($c['required'] && !isset($used[$n])) {
                    dynimp_json(['status' => 'error', 'message' => "Required column '$n' (NOT NULL, no default) is not mapped."], 400);
                }
            }
        }

        $q = fn($n) => '`' . str_replace('`', '``', $n) . '`';
        $mapCols = array_values(array_map(fn($c) => $c['name'], $map));
        $mapIdx  = array_keys($map);
        $tq = $q($table);
        if ($mode === 'update') {
            $sql = ''; $bindCols = [];
        } else {
            $ph = implode(', ', array_fill(0, count($mapCols), '?'));
            $cl = implode(', ', array_map($q, $mapCols));
            $sql = ($mode === 'insert_ignore' ? "INSERT IGNORE INTO" : "INSERT INTO") . " $tq ($cl) VALUES ($ph)";
            if ($mode === 'upsert') {
                $upd = array_filter($mapCols, fn($n) => !$colsByName[$n]['auto'] && $colsByName[$n]['key'] !== 'PRI');
                if ($upd) $sql .= ' ON DUPLICATE KEY UPDATE ' . implode(', ', array_map(fn($n) => $q($n) . ' = VALUES(' . $q($n) . ')', $upd));
            }
            $bindCols = $mapCols;
        }

        try {
            $rows = dynimp_read_sheet($path);
        } catch (Throwable $e) {
            dynimp_json(['status' => 'error', 'message' => 'Could not read the file: ' . $e->getMessage()], 400);
        }
        array_shift($rows); // header

        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        $keepEmpty = ($_POST['keep_empty'] ?? '1') === '1'; // update mode: blank cell keeps the existing value
        $stmtCache = [];
        $existsStmt = $mode === 'update' ? $conDB->prepare("SELECT 1 FROM $tq WHERE " . $q($keyCol) . ' = ? LIMIT 1') : null;
        $stmt = $mode === 'update' ? null : $conDB->prepare($sql);
        $types = str_repeat('s', count($bindCols));
        $errors = [];
        $inserted = $affected = $skipped = $unchanged = 0;

        $conDB->begin_transaction();
        try {
            foreach ($rows as $i => $row) {
                $rowNo = $i + 2; // 1-based, header is row 1
                $allEmpty = true;
                foreach ($mapIdx as $ix) { if (isset($row[$ix]) && trim((string)$row[$ix]) !== '') { $allEmpty = false; break; } }
                if ($allEmpty) { $skipped++; continue; }

                $vals = []; $rowErr = null;
                foreach ($map as $ix => $c) {
                    [$v, $err] = dynimp_convert($row[$ix] ?? null, $c);
                    if ($err) { $rowErr = $c['name'] . ': ' . $err; break; }
                    $vals[$c['name']] = $v;
                }
                if (!$rowErr && $mode === 'update') {
                    $keyVal = $vals[$keyCol] ?? null;
                    if ($keyVal === null || $keyVal === '') {
                        $rowErr = $keyCol . ': key value is empty';
                    } else {
                        $setCols = array_values(array_filter($mapCols, fn($n) => $n !== $keyCol && (!$keepEmpty || ($vals[$n] !== null && $vals[$n] !== ''))));
                        try {
                            $existsStmt->bind_param('s', $keyVal);
                            $existsStmt->execute();
                            $found = $existsStmt->get_result()->num_rows > 0;
                            if (!$found) {
                                $rowErr = 'no record found with ' . $keyCol . ' = ' . $keyVal;
                            } elseif (!$setCols) {
                                $unchanged++; // every mapped cell was blank
                            } else {
                                $sig = implode('|', $setCols);
                                if (!isset($stmtCache[$sig])) {
                                    $stmtCache[$sig] = $conDB->prepare("UPDATE $tq SET " . implode(', ', array_map(fn($n) => $q($n) . ' = ?', $setCols)) . ' WHERE ' . $q($keyCol) . ' = ?');
                                }
                                $us = $stmtCache[$sig];
                                $params = array_map(fn($n) => $vals[$n], $setCols);
                                $params[] = $keyVal;
                                $us->bind_param(str_repeat('s', count($params)), ...$params);
                                $us->execute();
                                if ($us->affected_rows > 0) $affected++; else $unchanged++;
                            }
                        } catch (mysqli_sql_exception $e) {
                            $rowErr = $e->getMessage();
                        }
                    }
                } elseif (!$rowErr) {
                    $params = array_map(fn($n) => $vals[$n], $bindCols);
                    try {
                        $stmt->bind_param($types, ...$params);
                        $stmt->execute();
                        if ($stmt->affected_rows > 0) $affected++;
                        else $unchanged++;
                    } catch (mysqli_sql_exception $e) {
                        $rowErr = $e->getMessage();
                    }
                }
                if ($rowErr) {
                    $errors[] = ['row' => $rowNo, 'error' => $rowErr];
                    if ($onError === 'abort') throw new RuntimeException('Row ' . $rowNo . ': ' . $rowErr);
                }
            }
            if ($dryRun) $conDB->rollback(); else $conDB->commit();
        } catch (Throwable $e) {
            $conDB->rollback();
            dynimp_json([
                'status' => 'error',
                'message' => 'Import aborted, nothing was saved. ' . $e->getMessage(),
                'errors' => array_slice($errors, 0, 100),
            ], 400);
        }

        if (!$dryRun) {
            @unlink($path);
            unset($_SESSION['dynimp_files'][$token]);
            error_log(sprintf('[dynamic_import] user=%s table=%s mode=%s ok=%d unchanged=%d skipped=%d failed=%d', $empid ?? '?', $table, $mode, $affected, $unchanged, $skipped, count($errors)));
        }
        dynimp_json([
            'status'    => 'success',
            'dry_run'   => $dryRun,
            'saved'     => $affected,
            'unchanged' => $unchanged,
            'empty_rows_skipped' => $skipped,
            'failed'    => count($errors),
            'errors'    => array_slice($errors, 0, 100),
        ]);

    default:
        dynimp_json(['status' => 'error', 'message' => 'Unknown action.'], 400);
}
