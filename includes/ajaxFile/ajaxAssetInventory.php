<?php
header('Content-Type: application/json');
// Treat as JSON endpoint to avoid page chrome
if (!defined('SKIP_PAGE_ACCESS_CONTROL')) {
    define('SKIP_PAGE_ACCESS_CONTROL', true);
}

// Start output buffering to prevent any accidental output before JSON
ob_start();

try {
    require_once __DIR__ . '/../../includes/session_check.php';
} catch (Throwable $e) {
    ob_end_clean();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Include error: ' . $e->getMessage()]);
    exit;
}

// Clear any buffered output from includes
ob_end_clean();

// Ensure PDO exists
if (!isset($pdo) || $pdo === null) {
    try {
        $pdo = getDbConnection();
    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'DB Error: ' . $e->getMessage()]);
        exit;
    }
}

// Define helper functions
function json_fail($message, $code = 400) {
    http_response_code($code);
    echo json_encode(['success' => false, 'message' => $message]);
    exit;
}

function json_ok($data = [], $message = 'ok') {
    echo json_encode(['success' => true, 'message' => $message, 'data' => $data]);
    exit;
}

/**
 * Embed signature image onto proof file
 * Supports image files (JPG, PNG, GIF, BMP)
 */
function embedSignatureOnProofFile($proofFilePath, $signatureImagePath, $outputPath) {
    if (!file_exists($signatureImagePath)) {
        return false;
    }
    
    if (!file_exists($proofFilePath)) {
        return false;
    }
    
    $proofExt = strtolower(pathinfo($proofFilePath, PATHINFO_EXTENSION));
    
    // Only process image files with GD
    if (!in_array($proofExt, ['jpg', 'jpeg', 'png', 'gif', 'bmp'])) {
        // For non-image files (PDF, DOC, etc.), return false and keep files separate
        return false;
    }
    
    try {
        // Load the proof image based on its extension
        $proofImage = null;
        switch ($proofExt) {
            case 'jpg':
            case 'jpeg':
                $proofImage = @imagecreatefromjpeg($proofFilePath);
                break;
            case 'png':
                $proofImage = @imagecreatefrompng($proofFilePath);
                break;
            case 'gif':
                $proofImage = @imagecreatefromgif($proofFilePath);
                break;
            case 'bmp':
                $proofImage = @imagecreatefrombmp($proofFilePath);
                break;
            default:
                return false;
        }
        
        if (!$proofImage) {
            error_log('Failed to load proof image: ' . $proofFilePath);
            return false;
        }
        
        // Load signature image (PNG)
        $signatureImage = @imagecreatefrompng($signatureImagePath);
        if (!$signatureImage) {
            error_log('Failed to load signature image: ' . $signatureImagePath);
            imagedestroy($proofImage);
            return false;
        }
        
        // Get dimensions
        $proofWidth = imagesx($proofImage);
        $proofHeight = imagesy($proofImage);
        $sigWidth = imagesx($signatureImage);
        $sigHeight = imagesy($signatureImage);
        
        // Calculate signature position (bottom-right corner with padding)
        $padding = 10;
        $sigX = $proofWidth - $sigWidth - $padding;
        $sigY = $proofHeight - $sigHeight - $padding;
        
        // If signature is too big, scale it down
        if ($sigWidth > ($proofWidth / 3) || $sigHeight > ($proofHeight / 3)) {
            $maxWidth = (int) ($proofWidth / 3);
            $maxHeight = (int) ($proofHeight / 3);
            $scale = min($maxWidth / $sigWidth, $maxHeight / $sigHeight);
            $newWidth = (int) ($sigWidth * $scale);
            $newHeight = (int) ($sigHeight * $scale);
            
            $resizedSig = imagecreatetruecolor($newWidth, $newHeight);
            imagealphablending($resizedSig, false);
            imagesavealpha($resizedSig, true);
            
            imagecopyresampled($resizedSig, $signatureImage, 0, 0, 0, 0, $newWidth, $newHeight, $sigWidth, $sigHeight);
            imagedestroy($signatureImage);
            $signatureImage = $resizedSig;
            
            $sigWidth = $newWidth;
            $sigHeight = $newHeight;
            $sigX = $proofWidth - $sigWidth - $padding;
            $sigY = $proofHeight - $sigHeight - $padding;
        }
        
        // Merge signature onto proof image
        imagealphablending($proofImage, true);
        imagecopy($proofImage, $signatureImage, $sigX, $sigY, 0, 0, $sigWidth, $sigHeight);
        
        // Save the combined image
        $success = false;
        switch ($proofExt) {
            case 'jpg':
            case 'jpeg':
                $success = imagejpeg($proofImage, $outputPath, 95);
                break;
            case 'png':
                $success = imagepng($proofImage, $outputPath, 9);
                break;
            case 'gif':
                $success = imagegif($proofImage, $outputPath);
                break;
            case 'bmp':
                $success = imagebmp($proofImage, $outputPath);
                break;
        }
        
        imagedestroy($proofImage);
        imagedestroy($signatureImage);
        
        return $success;
    } catch (Throwable $e) {
        error_log('Error embedding signature: ' . $e->getMessage());
        return false;
    }
}

function generate_tracking_id(PDO $pdo): string {
    // Generate unique tracking ID: TRACK-YYYYMMDD-XXXX
    $datePart = date('Ymd');
    $stmt = $pdo->prepare("SELECT COUNT(*) AS cnt FROM asset_items WHERE tracking_id LIKE :pattern");
    $stmt->execute(['pattern' => 'TRACK-' . $datePart . '-%']);
    $count = (int) $stmt->fetchColumn();
    $next = str_pad((string) ($count + 1), 4, '0', STR_PAD_LEFT);
    return 'TRACK-' . $datePart . '-' . $next;
}

// Generate a random alphanumeric serial with special characters (7-10 chars) and ensure it doesn't already exist
function generate_serial_number(PDO $pdo): string {
    $attempts = 0;
    $maxAttempts = 25;
    // Character set: uppercase, lowercase, digits, and special characters
    // $charset = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%^&*-_=+';
    $charset = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    $charsetLen = strlen($charset);
    do {
        $attempts++;
        // Random length between 7 and 10
        $length = random_int(7, 10);
        $serial = '';
        for ($i = 0; $i < $length; $i++) {
            $serial .= $charset[random_int(0, $charsetLen - 1)];
        }
        // Use distinct placeholder names; PDO MySQL doesn't support reusing a named placeholder twice
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM asset_items WHERE serial_number = :sn1 OR tracking_id = :sn2");
        $stmt->execute(['sn1' => $serial, 'sn2' => $serial]);
        $exists = (int)$stmt->fetchColumn() > 0;
        if (!$exists) {
            return $serial;
        }
    } while ($attempts < $maxAttempts);
    return (string) (int) (microtime(true) * 1000000);
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// If no action provided, return a helpful error
if (empty($action)) {
    json_fail('No action specified');
}

try {
    switch ($action) {
        case 'get_assets':
            $stmt = $pdo->query("SELECT id, name FROM assets ORDER BY name ASC");
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            json_ok(['assets' => $rows]);
            break;

        case 'register_asset':
            // Handle asset registration from the registerAssetModal form
            $name = trim($_POST['name'] ?? '');
            $assetType = trim($_POST['asset_type'] ?? '');
            $category = $assetType; // Use asset_type as category
            
            if (empty($name)) {
                json_fail('Asset name is required');
            }
            
            try {
                $stmt = $pdo->prepare("INSERT INTO assets (name, category, is_active) VALUES (:name, :category, 1)");
                $stmt->execute([
                    'name' => $name,
                    'category' => $category
                ]);
                json_ok([
                    'id' => $pdo->lastInsertId(),
                    'name' => $name,
                    'category' => $category
                ], 'Asset registered successfully');
            } catch (PDOException $e) {
                if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                    json_fail('Asset with this name already exists');
                }
                throw $e;
            }
            break;

        case 'search_employees':
            $term = trim($_GET['q'] ?? '');
            $limit = 20;
            $sql = "SELECT id, emp_id, name FROM employees WHERE status = 1 AND (name LIKE ? OR CAST(emp_id AS CHAR) LIKE ?) ORDER BY name LIMIT " . (int)$limit;
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['%' . $term . '%', '%' . $term . '%']);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $formatted = array_map(function ($row) {
                return ['id' => $row['id'], 'text' => $row['name'] . ' (' . $row['emp_id'] . ')'];
            }, $rows);
            json_ok(['results' => $formatted]);
            break;

        case 'list_items':
            try {
                $stmt = $pdo->query("SELECT ai.id, ai.asset_id, ai.tracking_id, ai.serial_number, ai.status, ai.assigned_emp_id, ai.assigned_date, ai.return_date, ai.description,
                                            a.name AS asset_name, e.name AS employee_name
                                     FROM asset_items ai
                                     LEFT JOIN assets a ON ai.asset_id = a.id
                                     LEFT JOIN employees e ON ai.assigned_emp_id = e.id
                                     ORDER BY ai.created_at DESC");
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                json_ok(['items' => $rows]);
            } catch (PDOException $e) {
                json_fail('Query error: ' . $e->getMessage(), 500);
            }
            break;

        case 'create_item':
            $assetId = (int) ($_POST['asset_id'] ?? 0);
            $serialNumber = trim($_POST['serial_number'] ?? '');
            $description = trim($_POST['description'] ?? '');
            if ($assetId <= 0) {
                json_fail('Asset type is required');
            }
            // Serial number must be the device/asset serial entered manually
            if ($serialNumber === '') {
                json_fail('Serial number is required');
            }
            // Generate a numeric tracking ID distinct from the device serial (alphanumeric + special, 7-10 chars)
            $trackingId = generate_serial_number($pdo);
            $stmt = $pdo->prepare("INSERT INTO asset_items (asset_id, tracking_id, serial_number, description, status) VALUES (:asset_id, :tracking_id, :serial_number, :description, 'Available')");
            $stmt->execute([
                'asset_id' => $assetId,
                'tracking_id' => $trackingId,
                'serial_number' => $serialNumber,
                'description' => $description
            ]);
            json_ok(['tracking_id' => $trackingId, 'serial_number' => $serialNumber, 'item_id' => $pdo->lastInsertId()]);
            break;

        case 'assign_item':
            $itemId = (int) ($_POST['item_id'] ?? 0);
            $empId = $_POST['emp_id'] ?? '';  // This is now the employee's primary ID
            $date = $_POST['assigned_date'] ?? date('Y-m-d');
            $note = trim($_POST['description'] ?? '');
            if ($itemId <= 0 || empty($empId)) {
                json_fail('Employee and asset item are required');
            }
            
            // Get the employee's emp_id (the display ID like 5430, 5127, etc.)
            $empStmt = $pdo->prepare("SELECT id, emp_id FROM employees WHERE id = :id");
            $empStmt->execute(['id' => $empId]);
            $empData = $empStmt->fetch(PDO::FETCH_ASSOC);
            if (!$empData) {
                json_fail('Employee not found', 404);
            }
            
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("SELECT ai.id, ai.asset_id, ai.tracking_id, ai.serial_number, ai.status FROM asset_items ai WHERE ai.id = :id FOR UPDATE");
            $stmt->execute(['id' => $itemId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row) {
                $pdo->rollBack();
                json_fail('Asset item not found', 404);
            }
            if ($row['status'] !== 'Available') {
                $pdo->rollBack();
                json_fail('Asset item is not available');
            }
            $stmt = $pdo->prepare("UPDATE asset_items SET status = 'Assigned', assigned_emp_id = :emp_id, assigned_date = :assigned_date WHERE id = :id");
            $stmt->execute([
                'emp_id' => $empData['id'],  // Store internal ID
                'assigned_date' => $date,
                'id' => $itemId
            ]);

            // Insert into employee_assets using emp_id (the display ID)
            $stmt = $pdo->prepare("INSERT INTO employee_assets (emp_id, asset_id, serial_number, description, assigned_date, status)
                                   VALUES (:emp_id, :asset_id, :serial_number, :description, :assigned_date, 'Assigned')");
            $stmt->execute([
                'emp_id' => $empData['emp_id'],  // Use emp_id (e.g., 5430, 5127)
                'asset_id' => $row['asset_id'],
                'serial_number' => $row['tracking_id'],  // Store tracking_id for reference
                'description' => $note,
                'assigned_date' => $date
            ]);
            $pdo->commit();
            json_ok(['tracking_id' => $row['tracking_id']]);
            break;

        case 'unassign_item':
            $itemId = (int) ($_POST['item_id'] ?? 0);
            $trackingId = $_POST['tracking_id'] ?? '';
            $returnDate = $_POST['return_date'] ?? '';
            $signature = $_POST['signature'] ?? '';
            $returnNotes = trim($_POST['notes'] ?? '');
            $assetCondition = trim($_POST['asset_condition'] ?? '');
            
            if ($itemId <= 0) {
                json_fail('Asset item ID is required');
            }
            
            if (empty($returnDate)) {
                json_fail('Return date is required');
            }
            
            if (empty($signature)) {
                json_fail('Signature is required');
            }
            
            if (empty($assetCondition)) {
                json_fail('Asset condition is required');
            }
            
            // Validate asset condition
            $validConditions = ['Good', 'Damage', 'Lost', 'Buy', 'Other'];
            if (!in_array($assetCondition, $validConditions)) {
                json_fail('Invalid asset condition');
            }
            
            // Handle file upload
            $proofFile = '';
            if (isset($_FILES['proof_file']) && $_FILES['proof_file']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = __DIR__ . '/../../uploads/asset_returns/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                $fileExt = strtolower(pathinfo($_FILES['proof_file']['name'], PATHINFO_EXTENSION));
                $allowedExt = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx'];
                
                if (!in_array($fileExt, $allowedExt)) {
                    json_fail('Invalid file type. Allowed: PDF, JPG, PNG, DOC, DOCX');
                }
                
                $fileName = 'return_' . $itemId . '_' . time() . '.' . $fileExt;
                $filePath = $uploadDir . $fileName;
                
                if (!move_uploaded_file($_FILES['proof_file']['tmp_name'], $filePath)) {
                    json_fail('Failed to upload proof file');
                }
                
                $proofFile = 'uploads/asset_returns/' . $fileName;
            } else {
                json_fail('Proof file is required');
            }
            
            // Handle signature upload
            $signatureFile = '';
            $tempSignaturePath = '';
            if (!empty($signature)) {
                $uploadDir = __DIR__ . '/../../uploads/signatures/';
                if (!is_dir($uploadDir)) {
                    if (!mkdir($uploadDir, 0755, true)) {
                        error_log('Failed to create signature upload directory: ' . $uploadDir);
                        json_fail('Failed to create upload directory');
                    }
                }
                
                // Handle both base64 data URLs and regular base64
                $signatureData = $signature;
                if (strpos($signature, 'data:image') === 0) {
                    // It's a base64 data URL - extract the base64 part
                    if (preg_match('/data:image\/(\w+);base64,(.+)/', $signature, $matches)) {
                        $signatureData = base64_decode($matches[2], true);
                    } else {
                        error_log('Invalid signature data URL format for item ' . $itemId);
                        json_fail('Invalid signature format');
                    }
                } else {
                    // Try to decode as base64 anyway
                    $signatureData = base64_decode($signature, true);
                }
                
                if ($signatureData === false || empty($signatureData)) {
                    error_log('Signature decode error for item ' . $itemId . ', signature length: ' . strlen($signature));
                    json_fail('Invalid signature data');
                }
                
                $signatureFileName = 'sig_' . $itemId . '_' . time() . '.png';
                $signatureFilePath = $uploadDir . $signatureFileName;
                
                // Ensure directory is writable
                if (!is_writable($uploadDir)) {
                    error_log('Signature upload directory is not writable: ' . $uploadDir);
                    json_fail('Upload directory is not writable');
                }
                
                $bytesWritten = file_put_contents($signatureFilePath, $signatureData);
                if ($bytesWritten === false) {
                    error_log('Failed to write signature file: ' . $signatureFilePath . ', error: ' . json_encode(error_get_last()));
                    json_fail('Failed to save signature');
                }
                
                if ($bytesWritten === 0) {
                    error_log('No data written to signature file: ' . $signatureFilePath);
                    json_fail('Failed to save signature - no data written');
                }
                
                $signatureFile = 'uploads/signatures/' . $signatureFileName;
                $tempSignaturePath = $signatureFilePath;
                
                // Try to embed signature into proof file if it's an image
                $proofFilePath = __DIR__ . '/../../' . $proofFile;
                if (file_exists($proofFilePath) && $tempSignaturePath) {
                    $outputPath = $proofFilePath;
                    if (embedSignatureOnProofFile($proofFilePath, $tempSignaturePath, $outputPath)) {
                        error_log('Successfully embedded signature into proof file for item ' . $itemId);
                    } else {
                        error_log('Could not embed signature into proof file (non-image format or error), keeping files separate for item ' . $itemId);
                    }
                }
            }
            
            $pdo->beginTransaction();
            try {
                // Get item details
                $stmt = $pdo->prepare("SELECT tracking_id, assigned_emp_id FROM asset_items WHERE id = :id");
                $stmt->execute(['id' => $itemId]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$row) {
                    $pdo->rollBack();
                    json_fail('Asset item not found', 404);
                }
                
                // Update asset_items status back to Available
                $stmt = $pdo->prepare("UPDATE asset_items SET status = 'Available', assigned_emp_id = NULL, assigned_date = NULL WHERE id = :id");
                $stmt->execute(['id' => $itemId]);
                
                // Check if record exists in employee_assets
                $checkStmt = $pdo->prepare("SELECT id FROM employee_assets WHERE serial_number = :serial LIMIT 1");
                $checkStmt->execute(['serial' => $row['tracking_id']]);
                $existingRecord = $checkStmt->fetch(PDO::FETCH_ASSOC);
                
                if ($existingRecord) {
                    // Update existing record
                    $stmt = $pdo->prepare("UPDATE employee_assets SET 
                                          status = 'Returned', 
                                          return_date = :return_date, 
                                          return_notes = :return_notes,
                                          signature_file = :signature_file,
                                          proof_file = :proof_file,
                                          asset_condition = :asset_condition
                                          WHERE serial_number = :serial");
                    $stmt->execute([
                        'return_date' => $returnDate,
                        'return_notes' => $returnNotes,
                        'signature_file' => $signatureFile,
                        'proof_file' => $proofFile,
                        'asset_condition' => $assetCondition,
                        'serial' => $row['tracking_id']
                    ]);
                    
                    $pdo->commit();
                    json_ok(['asset_record_id' => $existingRecord['id']], 'Asset returned and unassigned successfully');
                } else {
                    // No existing employee_assets record, just log the return
                    error_log('No employee_assets record found for serial: ' . $row['tracking_id']);
                    $pdo->commit();
                    json_ok(['message' => 'Asset returned and unassigned successfully']);
                }
            } catch (PDOException $e) {
                $pdo->rollBack();
                error_log('Unassign error for item ' . $itemId . ': ' . $e->getMessage());
                json_fail('Error: ' . $e->getMessage());
            }
            break;

        case 'update_item':
            $itemId = (int) ($_POST['item_id'] ?? 0);
            $serialNumber = trim($_POST['serial_number'] ?? '');
            $description = trim($_POST['description'] ?? '');
            
            if ($itemId <= 0) {
                json_fail('Asset item ID is required');
            }
            if (empty($serialNumber)) {
                json_fail('Serial number is required');
            }
            
            try {
                $stmt = $pdo->prepare("UPDATE asset_items SET serial_number = :serial, description = :description WHERE id = :id");
                $stmt->execute([
                    'serial' => $serialNumber,
                    'description' => $description,
                    'id' => $itemId
                ]);
                json_ok(['item_id' => $itemId], 'Asset item updated successfully');
            } catch (PDOException $e) {
                if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                    json_fail('Serial number already exists');
                }
                throw $e;
            }
            break;

        case 'delete_item':
            $itemId = (int) ($_POST['item_id'] ?? 0);
            
            if ($itemId <= 0) {
                json_fail('Asset item ID is required');
            }
            
            $pdo->beginTransaction();
            try {
                // Get item details
                $stmt = $pdo->prepare("SELECT tracking_id, assigned_emp_id FROM asset_items WHERE id = :id");
                $stmt->execute(['id' => $itemId]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$row) {
                    $pdo->rollBack();
                    json_fail('Asset item not found', 404);
                }
                
                // If assigned to an employee, remove from employee_assets
                if ($row['assigned_emp_id']) {
                    $stmt = $pdo->prepare("DELETE FROM employee_assets WHERE serial_number = :serial");
                    $stmt->execute(['serial' => $row['tracking_id']]);
                }
                
                // Delete the asset item
                $stmt = $pdo->prepare("DELETE FROM asset_items WHERE id = :id");
                $stmt->execute(['id' => $itemId]);
                
                $pdo->commit();
                json_ok(['item_id' => $itemId], 'Asset item deleted successfully');
            } catch (Throwable $e) {
                $pdo->rollBack();
                throw $e;
            }
            break;

        case 'get_asset_record':
            $assetId = (int) ($_POST['asset_id'] ?? 0);
            
            if ($assetId <= 0) {
                json_fail('Asset item ID is required');
            }
            
            try {
                // Get the asset item details and check if it has an employee assignment
                $stmt = $pdo->prepare("
                    SELECT ai.id, ai.tracking_id, ai.serial_number, ea.id AS employee_asset_id
                    FROM asset_items ai
                    LEFT JOIN employee_assets ea ON (ai.tracking_id = ea.serial_number OR ai.serial_number = ea.serial_number)
                    WHERE ai.id = :id
                    LIMIT 1
                ");
                $stmt->execute(['id' => $assetId]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$row) {
                    json_fail('Asset item not found', 404);
                }
                
                // If no employee_asset record, use the tracking ID as fallback
                $recordId = $row['employee_asset_id'] ?? $row['tracking_id'];
                
                json_ok(['asset_id' => $assetId, 'employee_asset_id' => $recordId, 'tracking_id' => $row['tracking_id']]);
            } catch (PDOException $e) {
                json_fail('Query error: ' . $e->getMessage(), 500);
            }
            break;

        default:
            json_fail('Unknown action');
    }
} catch (Throwable $e) {
    if ($pdo && $pdo->inTransaction()) {
        try {
            $pdo->rollBack();
        } catch (Throwable $rb) {
            // Ignore rollback errors
        }
    }
    // Log the full error for debugging
    error_log('Asset Inventory API Error: ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine());
    json_fail('Error: ' . $e->getMessage(), 500);
}
