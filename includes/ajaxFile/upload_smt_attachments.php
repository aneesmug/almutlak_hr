<?php
/**
 * Smart request attachments for the QR upload page (attach_smt_request.php).
 * POST id=<inv_no>, t=<QR token> (or a logged-in session), plus:
 *   action=upload (default, Dropzone "file")  -> {ok:true,file:"..."}
 *   action=list                               -> {ok:true,files:[{id,file,ext,can_delete}]}
 *   action=delete, att_id=<smt_attachment.id> -> {ok:true}
 * Errors: HTTP 4xx with {ok:false,message:"..."}.
 *
 * Delete is limited to files uploaded from this same browser session: the QR link is shared on
 * paper, so its holder must not be able to remove attachments other people added. Staff delete
 * any attachment from open_request.php as before.
 */
require_once __DIR__ . '/../session_bootstrap.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json');

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../FileUploader.php';
require_once __DIR__ . '/../smt_qr_token.php';

function smt_att_fail($message, $code = 422)
{
    http_response_code($code);
    echo json_encode(['ok' => false, 'message' => $message]);
    exit;
}

$action = (string)($_POST['action'] ?? 'upload');
$getinv_no = trim((string)($_POST['id'] ?? ''));
if ($getinv_no === '') {
    smt_att_fail('Request number is missing.', 400);
}
// Phone from the QR code: no login, the signed token (t) allows this request only.
// Logged-in users (no token) go through the normal session check.
if (!smt_qr_verify($conDB, $getinv_no, trim((string)($_POST['t'] ?? '')))) {
    if (empty($_SESSION['auth_user']) || !is_array($_SESSION['auth_user'])) {
        smt_att_fail('This upload link is not valid. Please scan the QR code on the request again.', 403);
    }
    require_once __DIR__ . '/../session_check.php';
}
$chk = mysqli_prepare($conDB, "SELECT 1 FROM `smart_request` WHERE `inv_no` = ? LIMIT 1");
mysqli_stmt_bind_param($chk, 's', $getinv_no);
mysqli_stmt_execute($chk);
if (!mysqli_fetch_row(mysqli_stmt_get_result($chk))) {
    smt_att_fail('Request not found: ' . $getinv_no, 404);
}

$uploadDir = __DIR__ . "/../../assets/smt_attachment/"; // same folder open_request.php reads from
$mine = $_SESSION['smt_qr_uploads'][$getinv_no] ?? []; // attachment ids uploaded from this session

if ($action === 'list') {
    $st = mysqli_prepare($conDB, "SELECT `id`, `attachment`, `docu_ext` FROM `smt_attachment` WHERE `inv_no` = ? ORDER BY `id` DESC");
    mysqli_stmt_bind_param($st, 's', $getinv_no);
    mysqli_stmt_execute($st);
    $res = mysqli_stmt_get_result($st);
    $files = [];
    while ($r = mysqli_fetch_assoc($res)) {
        $files[] = [
            'id'         => (int)$r['id'],
            'file'       => $r['attachment'],
            'ext'        => strtolower((string)$r['docu_ext']),
            'can_delete' => in_array((int)$r['id'], $mine, true),
        ];
    }
    echo json_encode(['ok' => true, 'files' => $files]);
    exit;
}

if ($action === 'delete') {
    $attId = (int)($_POST['att_id'] ?? 0);
    if (!in_array($attId, $mine, true)) {
        smt_att_fail('Only files you uploaded from this page can be deleted here.', 403);
    }
    $st = mysqli_prepare($conDB, "SELECT `attachment` FROM `smt_attachment` WHERE `id` = ? AND `inv_no` = ? LIMIT 1");
    mysqli_stmt_bind_param($st, 'is', $attId, $getinv_no);
    mysqli_stmt_execute($st);
    $row = mysqli_fetch_row(mysqli_stmt_get_result($st));
    if (!$row) {
        smt_att_fail('Attachment not found.', 404);
    }
    $del = mysqli_prepare($conDB, "DELETE FROM `smt_attachment` WHERE `id` = ? AND `inv_no` = ?");
    mysqli_stmt_bind_param($del, 'is', $attId, $getinv_no);
    mysqli_stmt_execute($del);
    $path = $uploadDir . basename($row[0]);
    if (is_file($path)) {
        @unlink($path);
    }
    $_SESSION['smt_qr_uploads'][$getinv_no] = array_values(array_diff($mine, [$attId]));
    ActivityLogger::log('DELETE', 'Request', "Deleted request attachment: {$row[0]}", [
        'page' => 'upload_smt_attachments.php', 'record_id' => $getinv_no, 'table_name' => 'smt_attachment',
        'old_values' => ['filename' => $row[0]],
    ]);
    echo json_encode(['ok' => true]);
    exit;
}

if ($action !== 'upload') {
    smt_att_fail('Unknown action.', 400);
}
if (empty($_FILES['file'])) {
    smt_att_fail('No file received.', 400);
}
if ($_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    smt_att_fail($_FILES['file']['error'] === UPLOAD_ERR_INI_SIZE || $_FILES['file']['error'] === UPLOAD_ERR_FORM_SIZE
        ? 'File is larger than the server upload limit.' : 'File upload failed.', 400);
}

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
$_SESSION['smt_qr_uploads'][$getinv_no][] = (int)mysqli_insert_id($conDB);

ActivityLogger::logUpload('Request', 'upload_smt_attachments.php', $filename_po, $getinv_no, 'smt_attachment');

echo json_encode(['ok' => true, 'file' => $filename_po]);
