<?php
/**
 * CHUNKED FILE UPLOAD HANDLER
 * Handles file uploads in 1MB chunks to bypass ModSecurity limits
 */

// Log immediately
error_log("╔════════════════════════════════════════════════════════════════╗");
error_log("║             CHUNKED_UPLOAD_HANDLER.PHP RECEIVED REQUEST        ║");
error_log("╚════════════════════════════════════════════════════════════════╝");
error_log("Timestamp: " . date('Y-m-d H:i:s.u'));
error_log("REQUEST_METHOD: " . $_SERVER['REQUEST_METHOD']);
error_log("POST[action]: " . ($_POST['action'] ?? 'NOT SET'));
error_log("POST[chunk_number]: " . ($_POST['chunk_number'] ?? 'NOT SET'));
error_log("POST[total_chunks]: " . ($_POST['total_chunks'] ?? 'NOT SET'));
error_log("POST[filename]: " . ($_POST['filename'] ?? 'NOT SET'));
error_log("FILES keys: " . json_encode(array_keys($_FILES)));

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/session_check.php';

header('Content-Type: application/json');

// Verify user is logged in
if (empty($_SESSION['empid'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$action = $_POST['action'] ?? '';
$currentUserId = $_SESSION['empid'];

if ($action === 'upload_chunk') {
    uploadChunk($currentUserId);
} elseif ($action === 'finalize_chunk_upload') {
    finalizeChunkUpload($currentUserId);
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Unknown action: ' . $action]);
}

function uploadChunk($currentUserId) {
    error_log("uploadChunk() called - User: $currentUserId");
    
    if (!isset($_FILES['chunk'])) {
        error_log("ERROR: No 'chunk' in FILES");
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'No chunk file provided']);
        return;
    }
    
    if ($_FILES['chunk']['error'] !== UPLOAD_ERR_OK) {
        $errors = [
            UPLOAD_ERR_INI_SIZE => 'Chunk exceeds upload_max_filesize',
            UPLOAD_ERR_FORM_SIZE => 'Chunk exceeds MAX_FILE_SIZE',
            UPLOAD_ERR_PARTIAL => 'Chunk only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No chunk file',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temp folder',
            UPLOAD_ERR_CANT_WRITE => 'Cannot write chunk to disk',
            UPLOAD_ERR_EXTENSION => 'Extension blocked chunk',
        ];
        $msg = $errors[$_FILES['chunk']['error']] ?? "Unknown error";
        error_log("Chunk error: $msg");
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => $msg]);
        return;
    }
    
    $chunkNumber = intval($_POST['chunk_number'] ?? 0);
    $totalChunks = intval($_POST['total_chunks'] ?? 0);
    $originalFilename = $_POST['filename'] ?? 'unknown.bin';
    $chunkTmpPath = $_FILES['chunk']['tmp_name'];
    
    error_log("Processing chunk $chunkNumber/$totalChunks - Original file: $originalFilename");
    
    // Create temp directory for chunks
    $tempDir = sys_get_temp_dir() . '/settlement_uploads/' . $currentUserId;
    @mkdir($tempDir, 0755, true);
    
    // Save chunk
    $chunkPath = $tempDir . '/chunk_' . $chunkNumber;
    if (!move_uploaded_file($chunkTmpPath, $chunkPath)) {
        error_log("ERROR: Failed to save chunk $chunkNumber");
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to save chunk']);
        return;
    }
    
    error_log("Chunk $chunkNumber saved to: $chunkPath");
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'chunk_number' => $chunkNumber,
        'total_chunks' => $totalChunks,
        'message' => "Chunk $chunkNumber/$totalChunks received"
    ]);
    
    if (ob_get_level() > 0) {
        ob_end_flush();
    }
    flush();
}

function finalizeChunkUpload($currentUserId) {
    error_log("finalizeChunkUpload() called - User: $currentUserId");
    
    $totalChunks = intval($_POST['total_chunks'] ?? 0);
    $originalFilename = $_POST['filename'] ?? '';
    $fileExtension = strtolower(pathinfo($originalFilename, PATHINFO_EXTENSION));
    
    if ($totalChunks <= 0 || empty($originalFilename)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid finalize request']);
        return;
    }
    
    error_log("Finalizing upload - $totalChunks chunks, filename: $originalFilename");
    
    $tempDir = sys_get_temp_dir() . '/settlement_uploads/' . $currentUserId;
    
    // Check all chunks exist
    for ($i = 0; $i < $totalChunks; $i++) {
        $chunkPath = $tempDir . '/chunk_' . $i;
        if (!file_exists($chunkPath)) {
            error_log("ERROR: Missing chunk $i");
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => "Missing chunk $i"]);
            return;
        }
    }
    
    // Create final file
    $uploadBaseDir = __DIR__ . '/../../uploads/settlement_attachments';
    $yearMonth = date('Y/m');
    $uploadDir = $uploadBaseDir . '/' . $yearMonth;
    
    @mkdir($uploadDir, 0755, true);
    
    $uniqueFileName = 'settlement_' . uniqid() . '_' . time() . '.' . $fileExtension;
    $filePath = $uploadDir . '/' . $uniqueFileName;
    
    error_log("Assembling chunks into: $filePath");
    
    // Assemble chunks
    $finalHandle = fopen($filePath, 'wb');
    if (!$finalHandle) {
        error_log("ERROR: Cannot open final file for writing: $filePath");
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Cannot create final file']);
        return;
    }
    
    for ($i = 0; $i < $totalChunks; $i++) {
        $chunkPath = $tempDir . '/chunk_' . $i;
        $chunkData = @file_get_contents($chunkPath);
        if ($chunkData === false) {
            error_log("ERROR: Cannot read chunk $i");
            fclose($finalHandle);
            @unlink($filePath);
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => "Cannot read chunk $i"]);
            return;
        }
        fwrite($finalHandle, $chunkData);
        @unlink($chunkPath);
    }
    fclose($finalHandle);
    
    // Cleanup temp directory
    @rmdir($tempDir);
    
    error_log("Chunks assembled successfully - Final file: $filePath");
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'uploaded_filename' => $uniqueFileName,
        'original_filename' => $originalFilename,
        'message' => 'File uploaded successfully'
    ]);
    
    if (ob_get_level() > 0) {
        ob_end_flush();
    }
    flush();
}
?>
