<?php
	require_once __DIR__ . '/../../includes/db.php';
	require_once __DIR__ . '/../../includes/session_check.php';
	require_once __DIR__ . '/../special_access_helper.php';

	header('Content-Type: application/json');

	$invNo = trim((string)($_POST['id'] ?? ''));
	if ($invNo === '') {
		echo json_encode([
			'title'   => 'Error!',
			'message' => 'Request number is missing.',
			'type'    => 'error',
		]);
		exit;
	}

	// Self-service soft cancel: owner cancels their own request while it's still
	// draft/pending_approval/approved (not yet completed/rejected/cancelled). This
	// keeps the record and its history, unlike the admin hard-delete path below.
	if (($_POST['mode'] ?? '') === 'self_cancel') {
		$check = mysqli_prepare($conDB, "SELECT emp_id, current_status FROM smart_request WHERE inv_no = ? LIMIT 1");
		mysqli_stmt_bind_param($check, 's', $invNo);
		mysqli_stmt_execute($check);
		$checkRow = mysqli_fetch_assoc(mysqli_stmt_get_result($check));
		mysqli_stmt_close($check);

		if (!$checkRow) {
			echo json_encode(['title' => 'Error!', 'message' => __('request_not_found', 'Request not found'), 'type' => 'error']);
			exit;
		}

		if ((string)$checkRow['emp_id'] !== (string)($empid ?? '')) {
			echo json_encode(['title' => 'Error!', 'message' => __('you_can_only_cancel_your_own_requests', 'You can only cancel your own requests'), 'type' => 'error']);
			exit;
		}

		if (!in_array($checkRow['current_status'], ['draft', 'pending_approval', 'approved'], true)) {
			echo json_encode(['title' => 'Error!', 'message' => 'Request in status "' . $checkRow['current_status'] . '" cannot be cancelled.', 'type' => 'error']);
			exit;
		}

		$update = mysqli_prepare($conDB, "UPDATE smart_request SET current_status = 'cancelled' WHERE inv_no = ? AND current_status = ?");
		mysqli_stmt_bind_param($update, 'ss', $invNo, $checkRow['current_status']);
		mysqli_stmt_execute($update);
		$affected = mysqli_stmt_affected_rows($update);
		mysqli_stmt_close($update);

		if ($affected <= 0) {
			echo json_encode(['title' => 'Error!', 'message' => 'Failed to cancel request - status may have changed.', 'type' => 'error']);
			exit;
		}

		$note = 'Cancelled by employee (emp_id ' . ($empid ?? '') . ').';
		$hist = mysqli_prepare($conDB, "INSERT INTO smt_request_status (inv_no, emp_id, emp_name, note, status) VALUES (?, ?, 'System', ?, 'cancelled')");
		mysqli_stmt_bind_param($hist, 'sss', $invNo, $empid, $note);
		mysqli_stmt_execute($hist);
		mysqli_stmt_close($hist);

		$ra = mysqli_prepare($conDB, "UPDATE request_approvers ra JOIN approval_request_types art ON art.id = ra.request_type_id AND art.type_name = 'smart_request' SET ra.status = 'cancelled' WHERE ra.request_inv_no = ? AND ra.status IN ('pending', 'awaiting')");
		mysqli_stmt_bind_param($ra, 's', $invNo);
		mysqli_stmt_execute($ra);
		mysqli_stmt_close($ra);

		echo json_encode(['title' => 'Cancelled', 'message' => 'Your request has been cancelled successfully.', 'type' => 'success']);
		exit;
	}

	$canCancelSmartRequests = (
		!empty($is_system_admin)
		|| user_has_special_access($conDB, $empid ?? '', 'cancel_smart_requests', $user_role ?? '', $user_type ?? '', $is_system_admin ?? false)
	);

	if (!$canCancelSmartRequests) {
		echo json_encode([
			'title'   => 'Error!',
			'message' => __('access_denied', 'Access denied'),
			'type'    => 'error',
		]);
		exit;
	}

	$stmt = mysqli_prepare($conDB, "DELETE FROM `smart_request` WHERE `inv_no` = ?");
	mysqli_stmt_bind_param($stmt, 's', $invNo);

	if (mysqli_stmt_execute($stmt)) {
		$statusStmt = mysqli_prepare($conDB, "DELETE FROM `smt_request_status` WHERE `inv_no` = ?");
		mysqli_stmt_bind_param($statusStmt, 's', $invNo);
		mysqli_stmt_execute($statusStmt);
		mysqli_stmt_close($statusStmt);

		$attachStmt = mysqli_prepare($conDB, "SELECT `attachment` FROM `smt_attachment` WHERE `inv_no` = ?");
		mysqli_stmt_bind_param($attachStmt, 's', $invNo);
		mysqli_stmt_execute($attachStmt);
		$attachResult = mysqli_stmt_get_result($attachStmt);
		while ($row = mysqli_fetch_assoc($attachResult)) {
			$attachment = $row['attachment'];
			if ($attachment !== '') {
				@unlink(__DIR__ . '/../../assets/assets/smt_attachment/' . $attachment);
			}
		}
		mysqli_stmt_close($attachStmt);

		$deleteAttachStmt = mysqli_prepare($conDB, "DELETE FROM `smt_attachment` WHERE `inv_no` = ?");
		mysqli_stmt_bind_param($deleteAttachStmt, 's', $invNo);
		mysqli_stmt_execute($deleteAttachStmt);
		mysqli_stmt_close($deleteAttachStmt);

		echo json_encode([
			'title'   => 'Deleted!',
			'message' => 'Record Deleted Successfully ...',
			'type'    => 'success',
		]);
	} else {
		echo json_encode([
			'title'   => 'Error!',
			'message' => 'Unable to delete this record ...',
			'type'    => 'error',
		]);
	}

	mysqli_stmt_close($stmt);
