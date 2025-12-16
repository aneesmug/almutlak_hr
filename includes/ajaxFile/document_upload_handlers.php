<?php
/**
 * Document Upload Handlers for Employee Documents
 * These functions handle:
 * 1. Loading document types from database
 * 2. Uploading employee documents with validation
 */

// ============================================================================
// GET DOCUMENT TYPES FROM DATABASE
// ============================================================================
// ajaxType: 'get_document_types'
// Returns: JSON with array of document types from docu_type table
// Usage: Populates dropdown in document upload modal

function handle_get_document_types($pdo) {
    try {
        $stmt = $pdo->prepare("SELECT `id`, `duc_type` FROM `docu_type` ORDER BY `duc_type` ASC");
        $stmt->execute();
        $documentTypes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'status' => 200,
            'data' => $documentTypes,
            'message' => 'Document types loaded successfully.'
        ]);
    } catch (PDOException $e) {
        echo json_encode([
            'status' => 500,
            'data' => [],
            'message' => 'Error loading document types: ' . $e->getMessage()
        ]);
    }
}

// ============================================================================
// UPLOAD EMPLOYEE DOCUMENT
// ============================================================================
// ajaxType: 'upload_employee_document'
// POST Parameters:
//   - emp_id: Employee ID
//   - document_type: Document type ID from docu_type table
//   - document_file: File upload (via $_FILES)
// Returns: JSON success/error response
// Storage: Files stored in uploads/employee_documents/ directory
//          Database record stored in smt_attachment table

function handle_upload_employee_document($pdo, $username) {
    try {
        $emp_id = isset($_POST['emp_id']) ? (int)$_POST['emp_id'] : 0;
        $document_type = isset($_POST['document_type']) ? (int)$_POST['document_type'] : 0;
        $document_file = isset($_FILES['document_file']) ? $_FILES['document_file'] : null;
        
        // Validation: Employee ID
        if ($emp_id <= 0) {
            send_json_response("Error", "Invalid employee ID.", "error");
            return;
        }
        
        // Validation: Document Type
        if ($document_type <= 0) {
            send_json_response("Error", "Invalid document type.", "error");
            return;
        }
        
        // Validation: File Upload
        if (!$document_file || $document_file['error'] !== UPLOAD_ERR_OK) {
            send_json_response("Error", "No file uploaded or upload error occurred.", "error");
            return;
        }
        
        // Validation: File Size (max 5MB)
        $maxFileSize = 5 * 1024 * 1024; // 5MB
        if ($document_file['size'] > $maxFileSize) {
            send_json_response("Error", "File size exceeds maximum allowed size (5MB).", "error");
            return;
        }
        
        // Validation: File Type (MIME check)
        $allowedMimes = [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'image/jpeg',
            'image/png',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        ];
        
        $fileType = mime_content_type($document_file['tmp_name']);
        if (!in_array($fileType, $allowedMimes)) {
            send_json_response("Error", "File type not allowed. Allowed types: PDF, DOC, DOCX, JPG, JPEG, PNG, XLS, XLSX", "error");
            return;
        }
        
        // Verify Employee Exists
        $stmt = $pdo->prepare("SELECT `emp_id`, `name` FROM `employees` WHERE `emp_id` = :emp_id");
        $stmt->execute([':emp_id' => $emp_id]);
        $employee = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$employee) {
            send_json_response("Error", "Employee not found.", "error");
            return;
        }
        
        // Verify Document Type Exists
        $stmt = $pdo->prepare("SELECT `id`, `duc_type` FROM `docu_type` WHERE `id` = :id");
        $stmt->execute([':id' => $document_type]);
        $docType = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$docType) {
            send_json_response("Error", "Document type not found.", "error");
            return;
        }
        
        // Create Upload Directory
        $uploadDir = __DIR__ . '/../../uploads/employee_documents/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Generate Unique Filename
        $fileExtension = pathinfo($document_file['name'], PATHINFO_EXTENSION);
        $fileName = 'emp_' . $emp_id . '_' . $document_type . '_' . time() . '.' . $fileExtension;
        $filePath = $uploadDir . $fileName;
        
        // Move Uploaded File
        if (!move_uploaded_file($document_file['tmp_name'], $filePath)) {
            send_json_response("Error", "Failed to save uploaded file.", "error");
            return;
        }
        
        // Store in Database (smt_attachment table)
        $stmt = $pdo->prepare("INSERT INTO `smt_attachment` 
            (`emp_id`, `docu_type_id`, `file_name`, `file_path`, `created_at`, `created_by`, `status`) 
            VALUES (:emp_id, :docu_type_id, :file_name, :file_path, NOW(), :created_by, 1)");
        
        $stmt->execute([
            ':emp_id' => $emp_id,
            ':docu_type_id' => $document_type,
            ':file_name' => $document_file['name'],
            ':file_path' => 'uploads/employee_documents/' . $fileName,
            ':created_by' => $username ?? 'System'
        ]);
        
        $doc_id = $pdo->lastInsertId();
        
        // Log document upload
        require_once __DIR__ . '/../../includes/session_check.php';
        ActivityLogger::logUpload('Employee', 'document_upload_handlers.php', $emp_id, 
            $document_file['name'], 
            "Uploaded employee document: {$docType['duc_type']}", 
            'smt_attachment', 
            strtoupper($fileExtension), 
            $doc_id);
        
        send_json_response("Success", "Document uploaded successfully for " . $docType['duc_type'] . ".", "success");
        
    } catch (Exception $e) {
        send_json_response("Error", "An error occurred: " . $e->getMessage(), "error");
    }
}

?>
