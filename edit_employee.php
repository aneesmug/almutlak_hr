<?php
// Edit Employee now lives in a SweetAlert2 modal (assets/js/editEmployeeModal.js, saved through
// includes/ajaxFile/ajaxEmployeeEditModal.php). This page only keeps old links and bookmarks working:
// GET opens the employee's profile with the modal (?edit=1), a POST from an old form is still saved.
require_once __DIR__ . '/includes/session_check.php';
require_once __DIR__ . '/includes/employee_edit_helper.php';

$editEmpId = (string)($_POST['emp_id'] ?? $_GET['emp_id'] ?? '');
// Legacy links (edit_vac.php) pass the employees row id instead
if ($editEmpId === '' && ctype_digit((string)($_GET['id'] ?? ''))) {
	$idStmt = $pdo->prepare("SELECT `emp_id` FROM `employees` WHERE `id` = ? LIMIT 1");
	$idStmt->execute([$_GET['id']]);
	$editEmpId = (string)$idStmt->fetchColumn();
}
if (!ctype_digit($editEmpId)) {
	header('Location: ./reg_employee.php');
	exit;
}

$perm = employee_edit_permissions();
if (!$perm['modify']) {
	$_SESSION['error_msg'] = '<div class="col-xl-12">
			<div class="alert alert-danger bg-danger text-white border-0" role="alert">
				<b>Access Denied!</b>
				<h4>You do not have permission to modify employee information.</h4>
			</div>
		</div>';
	header('Location: ./view_employee.php?emp_id=' . $editEmpId);
	exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
	try {
		$result = employee_apply_edit($_POST, $perm);
		$_SESSION['swal_alert'] = [
			'title' => $result['title'],
			'message' => $result['message'],
			'type' => $result['kind'] === 'none' ? 'info' : 'success',
		];
	} catch (Throwable $e) {
		$_SESSION['swal_alert'] = ['title' => __('error'), 'message' => $e->getMessage(), 'type' => 'error'];
	}
	header('Location: ./view_employee.php?emp_id=' . $editEmpId);
	exit;
}

$tab = preg_replace('/[^a-z_]/', '', (string)($_GET['tab'] ?? ''));
header('Location: ./view_employee.php?emp_id=' . $editEmpId . '&edit=1' . ($tab !== '' ? '&edit_tab=' . $tab : ''));
exit;
