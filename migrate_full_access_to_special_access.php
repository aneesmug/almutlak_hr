<?php
/**
 * One-time migration: "Full Access Employees" (app_settings.full_access_emp_ids)
 * has been replaced by the 'view_all_employees' Special Access key, managed from
 * App Settings -> Special Access like every other grant. This copies every emp_id
 * currently listed in full_access_emp_ids into special_access_by_user so nobody
 * loses access, then deletes the old setting row.
 *
 * Safe to run more than once (idempotent) - re-running after the old row is gone
 * is a no-op. Run this once on each environment (local, staging, production) after
 * deploying the code that removes the old "Full Access Employees" UI.
 */

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/special_access_helper.php';

echo "<h2>Migrating Full Access Employees -&gt; Special Access</h2>";

$oldSettingSql = "SELECT setting_value FROM app_settings WHERE setting_name = 'full_access_emp_ids' LIMIT 1";
$oldResult = mysqli_query($conDB, $oldSettingSql);
$oldRow = $oldResult ? mysqli_fetch_assoc($oldResult) : null;

if (!$oldRow) {
    echo "<p>Nothing to migrate - 'full_access_emp_ids' setting no longer exists. Already migrated.</p>";
    exit;
}

$rawValue = (string)($oldRow['setting_value'] ?? '');
$empIds = [];
if ($rawValue !== '') {
    $decoded = json_decode($rawValue, true);
    if (is_array($decoded)) {
        $empIds = array_map('strval', $decoded);
    } else {
        $empIds = array_filter(array_map('trim', explode(',', $rawValue)), static function ($v) {
            return $v !== '';
        });
    }
}

echo "<p>Found " . count($empIds) . " employee(s) with manual full access: " . htmlspecialchars(implode(', ', $empIds)) . "</p>";

$specialAccessSql = "SELECT setting_value FROM app_settings WHERE setting_name = 'special_access_by_user' LIMIT 1";
$specialAccessResult = mysqli_query($conDB, $specialAccessSql);
$specialAccessRow = $specialAccessResult ? mysqli_fetch_assoc($specialAccessResult) : null;
$specialAccessMap = decode_special_access_map($specialAccessRow['setting_value'] ?? '{}');

$changed = 0;
foreach ($empIds as $empId) {
    $empId = trim((string)$empId);
    if ($empId === '') {
        continue;
    }
    $current = $specialAccessMap[$empId] ?? [];
    if (!in_array('view_all_employees', $current, true)) {
        $current[] = 'view_all_employees';
        $specialAccessMap[$empId] = $current;
        $changed++;
        echo "<p>Granted 'view_all_employees' to emp_id {$empId}</p>";
    } else {
        echo "<p>emp_id {$empId} already has 'view_all_employees' - skipped</p>";
    }
}

if ($changed > 0) {
    $newJson = json_encode($specialAccessMap, JSON_UNESCAPED_UNICODE);
    $updateStmt = mysqli_prepare($conDB, "UPDATE app_settings SET setting_value = ? WHERE setting_name = 'special_access_by_user'");
    mysqli_stmt_bind_param($updateStmt, 's', $newJson);
    if (!mysqli_stmt_execute($updateStmt)) {
        die("<p style='color:red;'>Failed to update special_access_by_user: " . mysqli_error($conDB) . "</p>");
    }
    mysqli_stmt_close($updateStmt);
    echo "<p><strong>Updated special_access_by_user with {$changed} new grant(s).</strong></p>";
} else {
    echo "<p>No new grants needed.</p>";
}

$deleteStmt = mysqli_prepare($conDB, "DELETE FROM app_settings WHERE setting_name = 'full_access_emp_ids'");
if (mysqli_stmt_execute($deleteStmt)) {
    echo "<p><strong>Removed the old 'full_access_emp_ids' setting.</strong></p>";
} else {
    echo "<p style='color:red;'>Failed to remove old setting: " . mysqli_error($conDB) . "</p>";
}
mysqli_stmt_close($deleteStmt);

echo "<p>Done. You can delete this file now.</p>";
