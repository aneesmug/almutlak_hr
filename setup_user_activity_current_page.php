<?php
/**
 * One-time migration: adds `current_page` to user_activity_log so Connection
 * Monitor can show which page each logged-in user is currently on. Run once
 * after upload, then safe to delete.
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
</style></head><body><h1>user_activity_log: current_page migration</h1>";

try {
    $check = mysqli_query($conDB, "SHOW COLUMNS FROM `user_activity_log` LIKE 'current_page'");
    if ($check && mysqli_num_rows($check) > 0) {
        echo "<div class='info'>Column <code>current_page</code> already exists. Nothing to do.</div>";
    } else {
        if (!mysqli_query($conDB, "ALTER TABLE `user_activity_log` ADD COLUMN `current_page` VARCHAR(255) DEFAULT NULL AFTER `last_activity`")) {
            throw new Exception(mysqli_error($conDB));
        }
        echo "<div class='success'>Added <code>current_page</code> column.</div>";
    }

    echo "<div class='success'><h3>Done</h3>
        <p>Connection Monitor will now show each active user's current page,
        updated on their every request.</p>
        <p>Safe to delete this file now.</p>
    </div>";
} catch (Exception $e) {
    echo "<div class='error'>Error: " . htmlspecialchars($e->getMessage()) . "</div>";
}

echo "</body></html>";
