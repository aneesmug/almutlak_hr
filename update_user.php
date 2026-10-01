<?php
	// Activate / deactivate a system user (all_users.php status switch).
	require_once __DIR__ . '/includes/db.php';
	require_once __DIR__ . '/includes/session_check.php';
	require_once __DIR__ . '/includes/page_access_helper.php';
	header('Content-Type: application/json');

	if (!page_role_allowed($conDB, 'all_users.php', $user_role ?? '', $user_type ?? '', $is_system_admin ?? false)) {
		http_response_code(403);
		echo json_encode(['type' => 'error', 'message' => 'Access denied.']);
		exit;
	}

	$id = (int)($_POST['id'] ?? 0);
	$status = (($_POST['status'] ?? '') === '1') ? 1 : 0;
	if ($id <= 0 || !isset($_POST['status'])) {
		http_response_code(422);
		echo json_encode(['type' => 'error', 'message' => 'Invalid request.']);
		exit;
	}

	if (mysqli_query($conDB, "UPDATE `admin_login` SET `status`='" . $status . "' WHERE `id`=" . $id)) {
		echo json_encode(['type' => 'success', 'status' => $status]);
	} else {
		http_response_code(500);
		echo json_encode(['type' => 'error', 'message' => 'Update failed.']);
	}
