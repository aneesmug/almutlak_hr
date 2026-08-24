<?php
/**
 * Ends the Connection Monitor's own OTP-granted access (clears the 1-hour
 * cookie), separate from the per-employee "Sign out" button inside the page.
 */
require_once __DIR__ . '/includes/connmon_gate.php';

connmon_clear_token();
header('Location: connection_monitor.php');
exit;
