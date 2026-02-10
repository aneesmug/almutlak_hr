<?php
/**
 * Settlement Attachment Download Handler
 * Securely handles file downloads with access control
 */

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/session_check.php';
require_once __DIR__ . '/includes/settlement_attachments_helper.php';

// Verify user is logged in
if (empty($_SESSION['empid'])) {
    http_response_code(401);
    die('Unauthorized');
}

$attachmentId = intval($_GET['id'] ?? 0);

if ($attachmentId <= 0) {
    http_response_code(400);
    die('Invalid attachement ID');
}

try {
    // Get attachment details
    $attachment = getSettlementAttachment($pdo, $attachmentId);
    
    if (empty($attachment)) {
        http_response_code(404);
        die('Attachment not found');
    }
    
    // Get settlement details to verify access
    $stmtSettlement = $pdo->prepare("
            SELECT sr.*
        FROM settlement_records sr
        WHERE sr.id = :settlement_id
    ");
    $stmtSettlement->execute([':settlement_id' => $attachment['settlement_id']]);
    $settlement = $stmtSettlement->fetch(PDO::FETCH_ASSOC);
    
    if (empty($settlement)) {
        http_response_code(404);
        die('Settlement not found');
    }
    
    // Check access - Allow if:
    // 1. User is the settlement owner
    // 2. User is assigned approver for this settlement
    // 3. User is HR/System Admin
    $hasAccess = false;
    
    // Check if owner
    if ($settlement['emp_id'] == $_SESSION['empid']) {
        $hasAccess = true;
    }
    
    // Check if approver
    if (!$hasAccess) {
        $typeQry = mysqli_query($conDB, "SELECT id FROM approval_request_types WHERE type_name = 'settlement' LIMIT 1");
        if ($typeQry && mysqli_num_rows($typeQry) > 0) {
            $typeRow = mysqli_fetch_assoc($typeQry);
            $typeId = $typeRow['id'];
            mysqli_free_result($typeQry);
            
            $approverQry = mysqli_query($conDB, "
                SELECT COUNT(*) as count FROM request_approvers 
                WHERE request_inv_no = '" . mysqli_real_escape_string($conDB, $settlement['request_inv_no']) . "'
                AND request_type_id = $typeId
                AND approver_id = " . $_SESSION['empid']
            );
            
            if ($approverQry && mysqli_fetch_assoc($approverQry)['count'] > 0) {
                $hasAccess = true;
            }
            if ($approverQry) mysqli_free_result($approverQry);
        }
    }
    
    // Check if HR or Admin
    global $is_system_admin, $isHR;
    if (($is_system_admin ?? false) || ($isHR ?? false)) {
        $hasAccess = true;
    }
    
    if (!$hasAccess) {
        http_response_code(403);
        die('Access denied');
    }
    
    // Verify file exists
    $filePath = resolveSettlementAttachmentPath($attachment, 'settlement_attachments');
    if (!file_exists($filePath) || !is_file($filePath)) {
        http_response_code(404);
        die('File not found on server');
    }
    
    // Log download to audit
    $stmtAudit = $pdo->prepare("
        INSERT INTO settlement_attachments_audit 
        (attachment_id, settlement_id, emp_id, action, file_name, uploaded_by, ip_address)
        VALUES (?, ?, ?, 'downloaded', ?, ?, ?)
    ");
    $stmtAudit->execute([
        $attachmentId,
        $attachment['settlement_id'],
        $attachment['emp_id'],
        $attachment['file_name'],
        $_SESSION['empid'],
        $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN'
    ]);
    
    // Serve file
    $fileName = $attachment['file_name'];
    $fileSize = filesize($filePath);
    $mimeType = $attachment['file_type'] ?? 'application/octet-stream';
    
    // Send headers (inline view)
    header('Content-Type: ' . $mimeType);
    header('Content-Disposition: inline; filename="' . $fileName . '"');
    header('Content-Length: ' . $fileSize);
    header('Cache-Control: public, must-revalidate');
    header('Pragma: public');
    
    // Output file
    readfile($filePath);
    exit;
    
} catch (Exception $e) {
    error_log("Settlement attachment download error: " . $e->getMessage());
    http_response_code(500);
    die('Error downloading file');
}
?>
