<?php
	// Legacy employee document delete (not linked from any page; the Documents tab uses
	// includes/ajaxFile/deleteAjax.php). Same rule: 'delete_employee_documents' Special Access key.
	require_once __DIR__ . '/db.php';
	require_once __DIR__ . '/session_check.php';
	require_once __DIR__ . '/special_access_helper.php';
	if (!user_has_special_access($conDB, $empid ?? '', 'delete_employee_documents', $user_role ?? '', $user_type ?? '', $is_system_admin ?? false)) {
		http_response_code(403);
		exit('You do not have permission to delete employee documents.');
	}
	$docId = (int) ($_GET['id'] ?? 0);
	$docEmpId = (string) ($_GET['emp_id'] ?? '');
	$stmt = $conDB->prepare("SELECT `path`, `pgid` FROM `emp_docu` WHERE `id` = ? AND `emp_id` = ?");
	$stmt->bind_param("is", $docId, $docEmpId);
	$stmt->execute();
	$row = $stmt->get_result()->fetch_assoc();
	$stmt->close();
	if (!$row) {
		http_response_code(404);
		exit('Document not found.');
	}
	$file = __DIR__ . '/../assets/emp_documents/' . basename((string) $row['path']);
	if ($row['path'] !== '' && is_file($file)) {
		unlink($file);
	}
	$stmt = $conDB->prepare("DELETE FROM `emp_docu` WHERE `id` = ? AND `emp_id` = ?");
	$stmt->bind_param("is", $docId, $docEmpId);
	$stmt->execute();
	$stmt->close();
	header("Location: ../view_employee.php?id=" . urlencode((string) $row['pgid']));
?>
