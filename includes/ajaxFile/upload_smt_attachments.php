<?php
/**
 * Dropzone upload for smart request attachments (attach_smt_request.php).
 * Responds JSON: {ok:true,file:"..."} or HTTP 4xx with {ok:false,message:"..."}.
 */
require_once __DIR__ . '/../session_bootstrap.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json');
// Not logged in (phone that scanned the QR code): session_check.php would redirect to a
// relative ./index.php, which from this folder is a 404 page - answer in JSON instead.
if (empty($_SESSION['auth_user']) || !is_array($_SESSION['auth_user'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'message' => 'Your login has expired. Please log in again and retry the upload.']);
    exit;
}

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../FileUploader.php';
require_once __DIR__ . '/../../includes/session_check.php';

function smt_att_fail($message, $code = 422)
{
    http_response_code($code);
    echo json_encode(['ok' => false, 'message' => $message]);
    exit;
}

if (empty($_FILES['file'])) {
    smt_att_fail('No file received.', 400);
}
if ($_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    smt_att_fail($_FILES['file']['error'] === UPLOAD_ERR_INI_SIZE || $_FILES['file']['error'] === UPLOAD_ERR_FORM_SIZE
        ? 'File is larger than the server upload limit.' : 'File upload failed.', 400);
}

$getinv_no = trim((string)($_POST['id'] ?? ''));
if ($getinv_no === '') {
    smt_att_fail('Request number is missing.', 400);
}
$chk = mysqli_prepare($conDB, "SELECT 1 FROM `smart_request` WHERE `inv_no` = ? LIMIT 1");
mysqli_stmt_bind_param($chk, 's', $getinv_no);
mysqli_stmt_execute($chk);
if (!mysqli_fetch_row(mysqli_stmt_get_result($chk))) {
    smt_att_fail('Request not found: ' . $getinv_no, 404);
}

$uploadDir = __DIR__ . "/../../assets/smt_attachment/"; // same folder open_request.php reads from
$file_extension = FileUploader::safeExt($_FILES['file']['name'], 'document');
if ($file_extension === null) {
    smt_att_fail('File type not allowed. Allowed: ' . FileUploader::allowedLabel('document') . '.');
}

// Random name: phone camera photos all arrive as "image.jpg", so a name derived from the
// original file name made every new photo overwrite the previous one.
$filename_po = FileUploader::uniqueName($getinv_no, $file_extension);

if (!FileUploader::moveTo($_FILES['file']['tmp_name'], $uploadDir . $filename_po, 'document')) {
    smt_att_fail(FileUploader::lastError());
}

$ins = mysqli_prepare($conDB, "INSERT INTO `smt_attachment` (`inv_no`, `attachment`, `docu_ext`) VALUES (?, ?, ?)");
mysqli_stmt_bind_param($ins, 'sss', $getinv_no, $filename_po, $file_extension);
if (!mysqli_stmt_execute($ins)) {
    @unlink($uploadDir . $filename_po);
    smt_att_fail('Could not save the attachment record.', 500);
}

ActivityLogger::logUpload('Request', 'upload_smt_attachments.php', $getinv_no,
    $filename_po,
    "Uploaded request attachment: {$filename_po}",
    'smt_attachment',
    $file_extension);

echo json_encode(['ok' => true, 'file' => $filename_po]);
