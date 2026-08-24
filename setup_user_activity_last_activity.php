<?php
/**
 * One-time migration: adds `last_activity` to user_activity_log so stale
 * sessions (browser closed, no further requests) can be auto-swept instead
 * of sitting "active" forever. Run this once after upload, then it's safe to
 * leave in place (idempotent) or delete.
 */
require_once __DIR__ . '/includes/session_check.php';

if (empty($is_system_admin)) {
    http_response_code(403);
    die('Access denied.');
}

echo "<!DOCTYPE html><html><head><title>Migration</title><style>
body{font-family:Arial,sans-serif;max-width:800px;margin:50px auto;padding:20px}
.success{color:green;background:#d4edda;padding:15px;border-radius:5px;margin:10px 0}
.error{color:red;background:#f8d7da;padding:15px;border-radius:5px;margin:10px 0}
.info{color:#055;background:#d1ecf1;padding:15px;border-radius:5px;margin:10px 0}
code{background:#f4f4f4;padding:2px 5px;border-radius:3px}
</style></head><body><h1>user_activity_log: last_activity migration</h1>";

try {
    $check = mysqli_query($conDB, "SHOW COLUMNS FROM `user_activity_log` LIKE 'last_activity'");
    if ($check && mysqli_num_rows($check) > 0) {
        echo "<div class='info'>Column <code>last_activity</code> already exists. Nothing to do.</div>";
    } else {
        if (!mysqli_query($conDB, "ALTER TABLE `user_activity_log` ADD COLUMN `last_activity` DATETIME DEFAULT NULL AFTER `login_time`")) {
            throw new Exception(mysqli_error($conDB));
        }
        echo "<div class='success'>Added <code>last_activity</code> column.</div>";

        if (!mysqli_query($conDB, "ALTER TABLE `user_activity_log` ADD KEY `idx_last_activity` (`last_activity`)")) {
            throw new Exception(mysqli_error($conDB));
        }
        echo "<div class='success'>Added index <code>idx_last_activity</code>.</div>";

        mysqli_query($conDB, "UPDATE `user_activity_log` SET `last_activity` = `login_time` WHERE `last_activity` IS NULL");
        echo "<div class='success'>Backfilled existing rows (last_activity = login_time).</div>";
    }

    echo "<div class='success'><h3>Done</h3>
        <p>Stale-session auto sign-out is now active, driven by the existing
        \"Set Session Timeout\" value in App Settings &rarr; General.</p>
        <p>Safe to delete this file now.</p>
    </div>";
} catch (Exception $e) {
    echo "<div class='error'>Error: " . htmlspecialchars($e->getMessage()) . "</div>";
}

echo "</body></html>";
