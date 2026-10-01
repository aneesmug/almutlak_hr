<?php
// Lines are now added from open_request.php > "Add Line" (SweetAlert2 modal, saved through
// includes/ajaxFile/ajaxSmartRequest.php ajaxType=request_line_add). Old links land here and
// are sent to the request page with the modal opened.
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/session_check.php';

$inv_no = isset($_GET['id']) ? trim((string)$_GET['id']) : '';
if ($inv_no === '') {
    header('Location: all_requests.php?error=request_not_found');
    exit;
}
header('Location: open_request.php?id=' . urlencode($inv_no) . '&addline=1');
exit;
