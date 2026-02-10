<?php
/**
 * Settlement Attachments Helper Functions
 * Handles retrieval, display, and management of settlement attachments
 */

require_once __DIR__ . '/db.php';

/**
 * Get all attachments for a settlement
 * @param PDO $pdo PDO database connection
 * @param int $settlementId Settlement ID
 * @param string $requestInvNo Settlement request invoice number
 * @return array Array of attachments
 */
function getSettlementAttachments($pdo, $settlementId, $requestInvNo = null) {
    try {
        if ($requestInvNo) {
            $stmt = $pdo->prepare("
                SELECT * FROM settlement_attachments 
                WHERE settlement_id = :settlement_id OR request_inv_no = :request_inv_no
                ORDER BY uploaded_at DESC
            ");
            $stmt->execute([
                ':settlement_id' => $settlementId,
                ':request_inv_no' => $requestInvNo
            ]);
        } else {
            $stmt = $pdo->prepare("
                SELECT * FROM settlement_attachments 
                WHERE settlement_id = :settlement_id
                ORDER BY uploaded_at DESC
            ");
            $stmt->execute([':settlement_id' => $settlementId]);
        }
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?? [];
    } catch (Exception $e) {
        error_log("Error fetching settlement attachments: " . $e->getMessage());
        return [];
    }
}

/**
 * Get attachments by category
 * @param PDO $pdo PDO database connection
 * @param int $settlementId Settlement ID
 * @param string $category Attachment category (wps_file, payment_proof, supporting_document, other)
 * @return array Array of attachments
 */
function getSettlementAttachmentsByCategory($pdo, $settlementId, $category) {
    try {
        $stmt = $pdo->prepare("
            SELECT * FROM settlement_attachments 
            WHERE settlement_id = :settlement_id 
            AND attachment_category = :category
            ORDER BY uploaded_at DESC
        ");
        $stmt->execute([
            ':settlement_id' => $settlementId,
            ':category' => $category
        ]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?? [];
    } catch (Exception $e) {
        error_log("Error fetching settlement attachments by category: " . $e->getMessage());
        return [];
    }
}

/**
 * Get single attachment details
 * @param PDO $pdo PDO database connection
 * @param int $attachmentId Attachment ID
 * @return array Attachment details or empty array
 */
function getSettlementAttachment($pdo, $attachmentId) {
    try {
        $stmt = $pdo->prepare("
            SELECT * FROM settlement_attachments 
            WHERE id = :id
        ");
        $stmt->execute([':id' => $attachmentId]);
        
        $attachment = $stmt->fetch(PDO::FETCH_ASSOC);
        return $attachment ?? [];
    } catch (Exception $e) {
        error_log("Error fetching settlement attachment: " . $e->getMessage());
        return [];
    }
}

/**
 * Delete attachment and file
 * @param PDO $pdo PDO database connection
 * @param int $attachmentId Attachment ID
 * @param int $currentUserId Current user ID (for audit)
 * @return bool Success status
 */
function deleteSettlementAttachment($pdo, $attachmentId, $currentUserId) {
    try {
        // Get attachment details first
        $attachment = getSettlementAttachment($pdo, $attachmentId);
        
        if (empty($attachment)) {
            return false;
        }
        
        // Delete physical file
        $filePath = resolveSettlementAttachmentPath($attachment, 'settlement_attachments');
        if (!empty($filePath) && file_exists($filePath)) {
            unlink($filePath);
        }
        
        // Delete from database
        $stmt = $pdo->prepare("DELETE FROM settlement_attachments WHERE id = :id");
        $result = $stmt->execute([':id' => $attachmentId]);
        
        // Log deletion to audit
        $stmtAudit = $pdo->prepare("
            INSERT INTO settlement_attachments_audit 
            (attachment_id, settlement_id, emp_id, action, file_name, uploaded_by, ip_address)
            VALUES (?, ?, ?, 'deleted', ?, ?, ?)
        ");
        $stmtAudit->execute([
            $attachmentId,
            $attachment['settlement_id'],
            $attachment['emp_id'],
            $attachment['file_name'],
            $currentUserId,
            $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN'
        ]);
        
        return $result;
    } catch (Exception $e) {
        error_log("Error deleting settlement attachment: " . $e->getMessage());
        return false;
    }
}

/**
 * Generate HTML for displaying attachments list
 * @param array $attachments Array of attachments
 * @param bool $canDelete Whether user can delete attachments
 * @return string HTML markup
 */
function renderSettlementAttachments($attachments, $canDelete = false) {
    if (empty($attachments)) {
        return '<p class="text-muted"><i class="fa fa-file"></i> No attachments</p>';
    }
    
    $html = '<div class="attachment-list">';
    
    foreach ($attachments as $attachment) {
        $fileIcon = getAttachmentIcon($attachment['file_type']);
        $fileSize = formatFileSize($attachment['file_size']);
        $uploadedDate = date('d M Y H:i', strtotime($attachment['uploaded_at']));
        $category = ucfirst(str_replace('_', ' ', $attachment['attachment_category']));
        
        $html .= '
            <div class="attachment-item card mb-2">
                <div class="card-body p-3 d-flex align-items-center justify-content-between">
                    <div class="attachment-info flex-grow-1">
                        <div class="d-flex align-items-center">
                            <i class="' . $fileIcon . ' fa-2x mr-3" style="color: #4a90e2;"></i>
                            <div>
                                <h6 class="mb-1">
                                    <a href="download_settlement_attachment.php?id=' . $attachment['id'] . '" class="text-decoration-none">
                                        ' . htmlspecialchars($attachment['file_name']) . '
                                    </a>
                                </h6>
                                <small class="text-muted d-block">
                                    ' . $fileSize . ' • ' . $uploadedDate . ' • <span class="badge badge-info">' . $category . '</span>
                                </small>
                            </div>
                        </div>
                    </div>
                    <div class="attachment-actions ml-2">
                        <a href="download_settlement_attachment.php?id=' . $attachment['id'] . '" class="btn btn-sm btn-primary" title="Download">
                            <i class="fa fa-download"></i>
                        </a>';
        
        if ($canDelete) {
            $html .= '
                        <button class="btn btn-sm btn-danger ml-1" onclick="deleteSettlementAttachment(' . $attachment['id'] . ')" title="Delete">
                            <i class="fa fa-trash"></i>
                        </button>';
        }
        
        $html .= '
                    </div>
                </div>
            </div>';
    }
    
    $html .= '</div>';
    return $html;
}

/**
 * Get font awesome icon based on file type
 * @param string $mimeType MIME type
 * @return string Font awesome icon class
 */
function getAttachmentIcon($mimeType) {
    $mimeType = strtolower($mimeType);
    
    if (strpos($mimeType, 'pdf') !== false) {
        return 'fad fa-file-pdf';
    } elseif (strpos($mimeType, 'word') !== false || strpos($mimeType, 'document') !== false) {
        return 'fad fa-file-word';
    } elseif (strpos($mimeType, 'excel') !== false || strpos($mimeType, 'spreadsheet') !== false) {
        return 'fad fa-file-excel';
    } elseif (strpos($mimeType, 'image') !== false) {
        return 'fad fa-file-image';
    } elseif (strpos($mimeType, 'zip') !== false || strpos($mimeType, 'rar') !== false) {
        return 'fad fa-file-archive';
    }
    
    return 'fad fa-file';
}

/**
 * Format file size to human readable format
 * @param int $bytes File size in bytes
 * @return string Formatted file size
 */
function formatFileSize($bytes) {
    $bytes = max(0, intval($bytes));
    $arBytes = array(
        0 => array(
            "UNIT" => "TB",
            "VALUE" => pow(1024, 4)
        ),
        1 => array(
            "UNIT" => "GB",
            "VALUE" => pow(1024, 3)
        ),
        2 => array(
            "UNIT" => "MB",
            "VALUE" => pow(1024, 2)
        ),
        3 => array(
            "UNIT" => "KB",
            "VALUE" => 1024
        ),
        4 => array(
            "UNIT" => "B",
            "VALUE" => 1
        ),
    );

    $output = "0 B";
    foreach($arBytes as $arItem) {
        if($bytes >= $arItem["VALUE"]) {
            $output = ($bytes / $arItem["VALUE"]) . " " . $arItem["UNIT"];
            break;
        }
    }

    return $output;
}

/**
 * Count attachments by category for a settlement
 * @param PDO $pdo PDO database connection
 * @param int $settlementId Settlement ID
 * @return array Count by category
 */
function countSettlementAttachmentsByCategory($pdo, $settlementId) {
    try {
        $stmt = $pdo->prepare("
            SELECT attachment_category, COUNT(*) as count 
            FROM settlement_attachments 
            WHERE settlement_id = :settlement_id
            GROUP BY attachment_category
        ");
        $stmt->execute([':settlement_id' => $settlementId]);
        
        $result = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $result[$row['attachment_category']] = $row['count'];
        }
        
        return $result;
    } catch (Exception $e) {
        error_log("Error counting settlement attachments: " . $e->getMessage());
        return [];
    }
}
?>
