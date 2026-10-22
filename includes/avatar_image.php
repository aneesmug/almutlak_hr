<?php
/**
 * Fallback image for an employee picture that failed to load in the browser.
 *
 * jquery.app.js swaps any broken <img src=".../assets/emp_pics/..."> to
 * includes/avatar_image.php?src=<the broken src>. This finds the employee the picture belongs to
 * (emp_<emp_id>/ folder, or the employee whose stored avatar has that file name), fixes their
 * stored avatar (includes/employee_avatar.php) and sends the right picture: their newest
 * existing upload, else the default for their gender.
 */
require_once __DIR__ . '/session_check.php';
require_once __DIR__ . '/employee_avatar.php';

$src = employee_avatar_normalize((string)($_GET['src'] ?? ''));
$row = null;

if ($src !== '') {
	$empId = preg_match('#/emp_pics/emp_(\d+)/#', $src, $m) ? $m[1] : '';
	if ($empId !== '') {
		$stmt = $conDB->prepare("SELECT `id`, `emp_id`, `name`, `sex`, `avatar` FROM `employees` WHERE `emp_id` = ? LIMIT 1");
		$stmt->bind_param('s', $empId);
	} else {
		$like = '%/' . basename($src);
		$stmt = $conDB->prepare("SELECT `id`, `emp_id`, `name`, `sex`, `avatar` FROM `employees` WHERE `avatar` LIKE ? ORDER BY `status` = 1 DESC LIMIT 1");
		$stmt->bind_param('s', $like);
	}
	$stmt->execute();
	$row = $stmt->get_result()->fetch_assoc() ?: null;
	$stmt->close();
}

if ($row) {
	employee_avatar_sync($conDB, (string)$row['emp_id']);
	$web = employee_avatar_resolve($row, employee_avatar_unapproved_files($conDB));
} else {
	// Unknown picture (e.g. a login without an employee record): gender from ?sex= when the page knows it
	$web = employee_avatar_default($_GET['sex'] ?? '');
}

$file = employee_avatar_abs($web);
if (!is_file($file)) {
	$file = employee_avatar_abs(EMP_AVATAR_MALE);
}
$types = ['png' => 'image/png', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'gif' => 'image/gif', 'webp' => 'image/webp'];
$ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));

while (ob_get_level() > 0) {
	ob_end_clean();
}
header('Content-Type: ' . ($types[$ext] ?? 'application/octet-stream'));
header('Content-Length: ' . filesize($file));
header('Cache-Control: private, max-age=3600');
header('X-Content-Type-Options: nosniff');
readfile($file);
exit;
