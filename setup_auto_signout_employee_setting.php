<?php
/**
 * One-time migration: adds a separate "Auto Sign-Out After (hours) - Employee
 * Users" field to App Settings -> General, so admin_login.user_type='employee'
 * accounts can have their own timeout distinct from the general one. Run once
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
</style></head><body><h1>Employee auto sign-out setting migration</h1>";

try {
    $check = mysqli_query($conDB, "SELECT id FROM `app_settings` WHERE `setting_name` = 'auto_signout_hours_employee' LIMIT 1");
    if ($check && mysqli_num_rows($check) > 0) {
        echo "<div class='info'>Setting <code>auto_signout_hours_employee</code> already exists. Nothing to do.</div>";
    } else {
        $stmt = $conDB->prepare("INSERT INTO `app_settings` (`setting_name`, `setting_value`, `setting_group`, `description`, `input_type`, `options`) VALUES ('auto_signout_hours_employee', '8', 'general', ?, 'text', NULL)");
        $description = 'Auto Sign-Out After (hours) - Employee Users <br /><small>(overrides "Auto Sign-Out After (hours)" above, only for accounts with user_type = employee)</small>';
        $stmt->bind_param('s', $description);
        if (!$stmt->execute()) {
            throw new Exception($stmt->error);
        }
        echo "<div class='success'>Added <code>auto_signout_hours_employee</code> setting (default: 8 hours).</div>";
    }

    echo "<div class='success'><h3>Done</h3>
        <p>Open App Settings &rarr; General to set the hours for employee-type accounts.
        The general \"Auto Sign-Out After (hours)\" field now only applies to everyone else.</p>
        <p>Safe to delete this file now.</p>
    </div>";
} catch (Exception $e) {
    echo "<div class='error'>Error: " . htmlspecialchars($e->getMessage()) . "</div>";
}

echo "</body></html>";
