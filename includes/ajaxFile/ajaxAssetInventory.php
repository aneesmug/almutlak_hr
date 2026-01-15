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

// Define role-based asset access control
$roleAssetAccess = [
    'it' => ['Laptop'],
    'gr_officer' => ['SIM Card', 'Car', 'Mobile Phone']
];

$isSystemAdmin = $is_system_admin ?? false;
$userType = $_SESSION['user_type'] ?? '';
$empType = $_SESSION['emp_type'] ?? '';
$allowedAssets = [];

if (!$isSystemAdmin && isset($roleAssetAccess[$userType])) {
    $allowedAssets = $roleAssetAccess[$userType];
}

/**
 * Check if user can access/manage a specific asset
 * Checks if the asset name contains any of the allowed asset keywords
 */
function canAccessAsset($assetName, $isAdmin, $userAllowedAssets) {
    if ($isAdmin) return true;
    if (empty($userAllowedAssets)) return false;
    
    // Check if asset name contains any of the allowed asset keywords
    foreach ($userAllowedAssets as $allowed) {
        if (stripos($assetName, $allowed) !== false) {
            return true;
        }
    }
    return false;
}

/**
 * Get allowed assets for current user
 */
function getAllowedAssetsForUser($isAdmin, $userAllowedAssets) {
    if ($isAdmin) return [];
    return $userAllowedAssets;
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

        case 'get_cars':
            // Fetch all cars with proper maker and model names using LEFT JOIN
            // Also check if car is currently assigned to an employee
            try {
                $stmt = $pdo->query("
                    SELECT 
                        c.id,
                        TRIM(cm.maker) as maker_name,
                        TRIM(cmodel.model) as model,
                        c.plate_no,
                        c.made_year,
                        c.type,
                        c.status,
                        IF(cd.status = 1, 1, 0) as is_assigned,
                        IF(cd.status = 1, COALESCE(e.name, e2.name), NULL) as assigned_to
                    FROM cars c
                    LEFT JOIN car_maker cm ON c.maker_name = cm.id
                    LEFT JOIN car_model cmodel ON c.model = cmodel.id
                    LEFT JOIN cars_drv cd ON c.id = cd.car_id AND cd.status = 1
                    LEFT JOIN employees e ON CAST(cd.car_user AS UNSIGNED) = e.id
                    LEFT JOIN employees e2 ON cd.car_user = e2.emp_id
                    WHERE c.status = '1'
                    ORDER BY cm.maker ASC, cmodel.model ASC
                ");
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                json_ok(['cars' => $rows]);
            } catch (PDOException $e) {
                json_fail('Failed to fetch cars: ' . $e->getMessage());
            }
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
            $carId = (int) ($_POST['car_id'] ?? 0);
            $serialNumber = trim($_POST['serial_number'] ?? '');
            $description = trim($_POST['description'] ?? '');
            if ($assetId <= 0) {
                json_fail('Asset type is required');
            }
            
            // Check role-based access
            $assetStmt = $pdo->prepare("SELECT name FROM assets WHERE id = :id");
            $assetStmt->execute(['id' => $assetId]);
            $assetRow = $assetStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$assetRow) {
                json_fail('Asset type not found');
            }
            
            if (!canAccessAsset($assetRow['name'], $isSystemAdmin, $allowedAssets)) {
                json_fail('You do not have permission to add this asset type', 403);
            }
            
            // If it's a Car asset and carId is provided, fetch car details
            if ($assetRow['name'] === 'Car' && $carId > 0) {
                $carStmt = $pdo->prepare("SELECT maker_name, model, plate_no FROM cars WHERE id = :id");
                $carStmt->execute(['id' => $carId]);
                $carRow = $carStmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$carRow) {
                    json_fail('Car not found');
                }
                
                // Use car plate number as serial number if not provided
                if (empty($serialNumber)) {
                    $serialNumber = $carRow['plate_no'];
                }
                
                // Update description with car details
                if (empty($description)) {
                    $description = $carRow['maker_name'] . ' ' . $carRow['model'] . ' (' . $carRow['plate_no'] . ')';
                }
            } else {
                // Serial number must be the device/asset serial entered manually for non-car assets
                if ($serialNumber === '') {
                    json_fail('Serial number is required');
                }
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
            
            // Check role-based access - verify user can assign this asset type
            $assetCheckStmt = $pdo->prepare("SELECT a.name FROM asset_items ai 
                                            JOIN assets a ON ai.asset_id = a.id 
                                            WHERE ai.id = :id");
            $assetCheckStmt->execute(['id' => $itemId]);
            $assetCheck = $assetCheckStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$assetCheck) {
                json_fail('Asset not found');
            }
            
            if (!canAccessAsset($assetCheck['name'], $isSystemAdmin, $allowedAssets)) {
                json_fail('You do not have permission to assign this asset type', 403);
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

        case 'assign_driver':
            $itemId = (int) ($_POST['item_id'] ?? 0);
            $trackingId = $_POST['tracking_id'] ?? '';
            $empId = (int) ($_POST['emp_id'] ?? 0);
            $rcvDate = $_POST['rcv_date'] ?? date('Y-m-d');
            $notes = trim($_POST['notes'] ?? '');
            
            if ($itemId <= 0 || $empId <= 0) {
                json_fail('Item ID and Employee ID are required');
            }
            
            try {
                // Check role-based access - verify user can manage this car
                $assetCheckStmt = $pdo->prepare("SELECT a.name FROM asset_items ai 
                                                LEFT JOIN assets a ON ai.asset_id = a.id 
                                                WHERE ai.id = :id");
                $assetCheckStmt->execute(['id' => $itemId]);
                $assetCheck = $assetCheckStmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$assetCheck) {
                    json_fail('Asset item not found');
                }
                
                if (!canAccessAsset($assetCheck['name'], $isSystemAdmin, $allowedAssets)) {
                    json_fail('You do not have permission to assign drivers for this asset type', 403);
                }
                
                // Get the car details and asset_id from asset_items
                $carStmt = $pdo->prepare("SELECT ai.asset_id, ai.serial_number, ai.description FROM asset_items ai WHERE ai.id = :id");
                $carStmt->execute(['id' => $itemId]);
                $carData = $carStmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$carData) {
                    json_fail('Asset item not found');
                }
                
                $assetId = $carData['asset_id'];  // Get the asset_id (4 for Car type)
                
                // Get the employee details
                $empStmt = $pdo->prepare("SELECT id, emp_id, name FROM employees WHERE id = :id");
                $empStmt->execute(['id' => $empId]);
                $empData = $empStmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$empData) {
                    json_fail('Employee not found');
                }
                
                // Try to find the car ID from the asset item
                // If it's a car asset, the description should contain the car details
                $carId = null;
                
                // First, try to match by serial number (plate_no) from cars table
                $carIdStmt = $pdo->prepare("SELECT id FROM cars WHERE plate_no = :plate_no LIMIT 1");
                $carIdStmt->execute(['plate_no' => $carData['serial_number']]);
                $carIdRow = $carIdStmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$carIdRow) {
                    // Try to get car_id from description if available
                    // Description might be stored as "maker model plate_no"
                    // Extract plate_no and search again
                    preg_match('/\(([^)]+)\)/', $carData['description'], $matches);
                    if (!empty($matches[1])) {
                        $carIdStmt2 = $pdo->prepare("SELECT id FROM cars WHERE plate_no = :plate_no LIMIT 1");
                        $carIdStmt2->execute(['plate_no' => $matches[1]]);
                        $carIdRow = $carIdStmt2->fetch(PDO::FETCH_ASSOC);
                    }
                }
                
                if (!$carIdRow) {
                    json_fail('Could not find associated car. Please ensure the car is properly registered in the system.');
                }
                
                $carId = $carIdRow['id'];
                
                // Deactivate any existing driver records for this car
                $deactiveStmt = $pdo->prepare("UPDATE cars_drv SET status = 0 WHERE car_id = :car_id AND status = 1");
                $deactiveStmt->execute(['car_id' => $carId]);
                
                // Insert new driver record into cars_drv table
                $driverStmt = $pdo->prepare("
                    INSERT INTO cars_drv (car_id, car_user, rcv_date, status, created_at) 
                    VALUES (:car_id, :car_user, :rcv_date, 1, NOW())
                ");
                $driverStmt->execute([
                    'car_id' => $carId,
                    'car_user' => $empData['emp_id'],  // Store emp_id (e.g., 5430)
                    'rcv_date' => $rcvDate
                ]);
                
                $driverId = $pdo->lastInsertId();
                
                // Update asset_items to mark it as assigned
                $updateItemStmt = $pdo->prepare("
                    UPDATE asset_items 
                    SET assigned_emp_id = :assigned_emp_id, assigned_date = :assigned_date, status = 'Assigned'
                    WHERE id = :item_id
                ");
                $updateItemStmt->execute([
                    'assigned_emp_id' => $empId,  // Employee's primary ID
                    'assigned_date' => $rcvDate,
                    'item_id' => $itemId
                ]);
                
                // Also insert into employee_assets table for audit trail and reporting
                $empAssetStmt = $pdo->prepare("
                    INSERT INTO employee_assets (emp_id, asset_id, serial_number, description, assigned_date, status)
                    VALUES (:emp_id, :asset_id, :serial_number, :description, :assigned_date, 'Assigned')
                ");
                $empAssetStmt->execute([
                    'emp_id' => $empData['emp_id'],  // Store emp_id
                    'asset_id' => $assetId,          // Asset ID from asset_items (4 for Car)
                    'serial_number' => $carData['serial_number'],  // Plate number
                    'description' => $carData['description'],      // Car details
                    'assigned_date' => $rcvDate
                ]);
                
                json_ok([
                    'driver_id' => $driverId,
                    'employee_name' => $empData['name'],
                    'car_id' => $carId,
                    'rcv_date' => $rcvDate
                ], 'Driver assigned successfully');
            } catch (PDOException $e) {
                error_log('Assign Driver Error: ' . $e->getMessage());
                json_fail('Error assigning driver: ' . $e->getMessage(), 500);
            }
            break;

        case 'unassign_driver':
            $itemId = (int) ($_POST['item_id'] ?? 0);
            $trackingId = $_POST['tracking_id'] ?? '';
            $returnStatus = trim($_POST['return_status'] ?? 'Good');
            $returnDate = $_POST['return_date'] ?? date('Y-m-d');
            
            if ($itemId <= 0) {
                json_fail('Item ID is required');
            }
            
            try {
                // Check role-based access
                $assetCheckStmt = $pdo->prepare("SELECT a.name FROM asset_items ai 
                                                LEFT JOIN assets a ON ai.asset_id = a.id 
                                                WHERE ai.id = :id");
                $assetCheckStmt->execute(['id' => $itemId]);
                $assetCheck = $assetCheckStmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$assetCheck) {
                    json_fail('Asset item not found');
                }
                
                if (!canAccessAsset($assetCheck['name'], $isSystemAdmin, $allowedAssets)) {
                    json_fail('You do not have permission to unassign drivers for this asset type', 403);
                }
                
                // Get the asset item details including assigned employee
                $itemStmt = $pdo->prepare("SELECT ai.asset_id, ai.serial_number, ai.assigned_emp_id FROM asset_items ai WHERE ai.id = :id");
                $itemStmt->execute(['id' => $itemId]);
                $itemData = $itemStmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$itemData) {
                    json_fail('Asset item not found');
                }
                
                // Get employee info by assigned_emp_id
                $empStmt = $pdo->prepare("SELECT emp_id FROM employees WHERE id = :id");
                $empStmt->execute(['id' => $itemData['assigned_emp_id']]);
                $empData = $empStmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$empData) {
                    json_fail('Employee not found for this asset');
                }
                
                // Find and deactivate the cars_drv record
                $carsStmt = $pdo->prepare("SELECT id FROM cars WHERE plate_no = :plate_no LIMIT 1");
                $carsStmt->execute(['plate_no' => $itemData['serial_number']]);
                $carsRow = $carsStmt->fetch(PDO::FETCH_ASSOC);
                
                if ($carsRow) {
                    // Deactivate the driver record
                    $deactiveStmt = $pdo->prepare("UPDATE cars_drv SET status = 0, rtn_date = :rtn_date WHERE car_id = :car_id AND status = 1");
                    $deactiveStmt->execute([
                        'car_id' => $carsRow['id'],
                        'rtn_date' => $returnDate
                    ]);
                }
                
                // Update asset_items to mark it as unassigned
                $updateItemStmt = $pdo->prepare("
                    UPDATE asset_items 
                    SET assigned_emp_id = NULL, status = 'Available'
                    WHERE id = :item_id
                ");
                $updateItemStmt->execute(['item_id' => $itemId]);
                
                // Update employee_assets record status
                $updateAssetStmt = $pdo->prepare("
                    UPDATE employee_assets 
                    SET status = :status
                    WHERE emp_id = :emp_id AND asset_id = :asset_id AND serial_number = :serial_number AND status = 'Assigned'
                    ORDER BY assigned_date DESC
                    LIMIT 1
                ");
                $updateAssetStmt->execute([
                    'status' => $returnStatus,
                    'emp_id' => $empData['emp_id'],
                    'asset_id' => $itemData['asset_id'],
                    'serial_number' => $itemData['serial_number']
                ]);
                
                json_ok([
                    'item_id' => $itemId,
                    'return_status' => $returnStatus,
                    'return_date' => $returnDate
                ], 'Driver unassigned successfully');
            } catch (PDOException $e) {
                error_log('Unassign Driver Error: ' . $e->getMessage());
                json_fail('Error unassigning driver: ' . $e->getMessage(), 500);
            }
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
            
            // Check role-based access - verify user can receive/return this asset type
            $assetReturnCheckStmt = $pdo->prepare("SELECT a.name FROM asset_items ai 
                                                   JOIN assets a ON ai.asset_id = a.id 
                                                   WHERE ai.id = :id");
            $assetReturnCheckStmt->execute(['id' => $itemId]);
            $assetReturnCheck = $assetReturnCheckStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$assetReturnCheck) {
                json_fail('Asset not found');
            }
            
            if (!canAccessAsset($assetReturnCheck['name'], $isSystemAdmin, $allowedAssets)) {
                json_fail('You do not have permission to return this asset type', 403);
            }
            
            if (empty($returnDate)) {
                json_fail('Return date is required');
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
            // Note: Signature is now captured during print flow, not during unassign
            $signatureFile = '';
            
            $pdo->beginTransaction();
            try {
                // Get item details including asset type and assigned_date to check if it's a car
                $stmt = $pdo->prepare("SELECT ai.id, ai.tracking_id, ai.assigned_emp_id, ai.serial_number, ai.description, ai.assigned_date, a.name as asset_name 
                                       FROM asset_items ai
                                       JOIN assets a ON ai.asset_id = a.id
                                       WHERE ai.id = :id");
                $stmt->execute(['id' => $itemId]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$row) {
                    $pdo->rollBack();
                    json_fail('Asset item not found', 404);
                }
                
                error_log('Unassigning item: ' . $itemId . ', asset_name: ' . $row['asset_name'] . ', serial: ' . $row['serial_number']);
                
                // Store the assigned_date before we clear it
                $assignedDate = $row['assigned_date'];
                
                // If it's a Car asset, also update cars_drv table (deactivate active driver)
                if ($row['asset_name'] === 'Car' && $row['assigned_emp_id']) {
                    error_log('Processing car unassignment for assigned_emp_id: ' . $row['assigned_emp_id']);
                    
                    // Get the employee's emp_id from the employees table
                    $empStmt = $pdo->prepare("SELECT emp_id FROM employees WHERE id = :id LIMIT 1");
                    $empStmt->execute(['id' => $row['assigned_emp_id']]);
                    $empData = $empStmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($empData) {
                        error_log('Found employee emp_id: ' . $empData['emp_id']);
                        
                        // Update cars_drv: Find active records where this employee is the driver and deactivate them
                        // This handles the case where the car might not match by plate_no
                        $updateCarDrvStmt = $pdo->prepare("UPDATE cars_drv 
                                                           SET status = 0, rtn_date = :return_date 
                                                           WHERE car_user = :emp_id AND status = 1");
                        $affectedRows = $updateCarDrvStmt->execute([
                            'emp_id' => $empData['emp_id'],
                            'return_date' => $returnDate
                        ]);
                        
                        $rowCount = $updateCarDrvStmt->rowCount();
                        error_log('Updated cars_drv: ' . $rowCount . ' records deactivated for emp_id: ' . $empData['emp_id']);
                        
                        if ($rowCount === 0) {
                            error_log('WARNING: No active cars_drv records found for emp_id: ' . $empData['emp_id']);
                        }
                    } else {
                        error_log('WARNING: Could not find employee with id: ' . $row['assigned_emp_id']);
                    }
                }
                
                // Update asset_items status back to Available
                $stmt = $pdo->prepare("UPDATE asset_items SET status = 'Available', assigned_emp_id = NULL, assigned_date = NULL WHERE id = :id");
                $stmt->execute(['id' => $itemId]);
                error_log('Updated asset_items for item_id: ' . $itemId);
                
                // Check if record exists in employee_assets by tracking_id (stored in serial_number field)
                $checkStmt = $pdo->prepare("SELECT id, emp_id FROM employee_assets WHERE serial_number = :serial LIMIT 1");
                $checkStmt->execute(['serial' => $row['tracking_id']]);
                $existingRecord = $checkStmt->fetch(PDO::FETCH_ASSOC);
                
                error_log('Looking for employee_assets record with tracking_id: ' . $row['tracking_id'] . ', found: ' . ($existingRecord ? 'yes (id=' . $existingRecord['id'] . ')' : 'no'));
                
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
                    error_log('Updated employee_assets record for tracking_id: ' . $row['tracking_id']);
                    
                    $pdo->commit();
                    json_ok(['asset_record_id' => $existingRecord['id']], 'Asset returned and unassigned successfully');
                } else {
                    // No existing employee_assets record, create one with return info
                    error_log('No employee_assets record found for tracking_id: ' . $row['tracking_id'] . ', creating new one');
                    
                    $insertStmt = $pdo->prepare("INSERT INTO employee_assets 
                                                (emp_id, asset_id, serial_number, description, assigned_date, return_date, status, return_notes, signature_file, proof_file, asset_condition)
                                                VALUES (:emp_id, :asset_id, :serial_number, :description, :assigned_date, :return_date, 'Returned', :return_notes, :signature_file, :proof_file, :asset_condition)");
                    
                    // Get asset_id and emp_id for this item
                    $getAssetIdStmt = $pdo->prepare("SELECT asset_id FROM asset_items WHERE id = :id");
                    $getAssetIdStmt->execute(['id' => $itemId]);
                    $assetIdRow = $getAssetIdStmt->fetch(PDO::FETCH_ASSOC);
                    
                    // Get employee emp_id from employees table
                    $getEmpIdStmt = $pdo->prepare("SELECT emp_id FROM employees WHERE id = :id");
                    $getEmpIdStmt->execute(['id' => $row['assigned_emp_id']]);
                    $empIdRow = $getEmpIdStmt->fetch(PDO::FETCH_ASSOC);
                    
                    $insertStmt->execute([
                        'emp_id' => $empIdRow ? $empIdRow['emp_id'] : $row['assigned_emp_id'],
                        'asset_id' => $assetIdRow ? $assetIdRow['asset_id'] : null,
                        'serial_number' => $row['tracking_id'],  // Store tracking_id like assign does
                        'description' => $row['description'],
                        'assigned_date' => $assignedDate,  // Use the actual assigned date from asset_items
                        'return_date' => $returnDate,
                        'return_notes' => $returnNotes,
                        'signature_file' => $signatureFile,
                        'proof_file' => $proofFile,
                        'asset_condition' => $assetCondition
                    ]);
                    
                    $pdo->commit();
                    json_ok(['asset_record_id' => $pdo->lastInsertId()], 'Asset returned and unassigned successfully');
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
                // Get the asset item details including status
                $stmt = $pdo->prepare("
                    SELECT ai.id, ai.tracking_id, ai.serial_number, ai.status, ai.assigned_emp_id, ai.assigned_date, ai.asset_id,
                           ea.id AS employee_asset_id,
                           a.name as asset_name
                    FROM asset_items ai
                    LEFT JOIN employee_assets ea ON (ai.tracking_id = ea.serial_number OR ai.serial_number = ea.serial_number)
                    LEFT JOIN assets a ON ai.asset_id = a.id
                    WHERE ai.id = :id
                    LIMIT 1
                ");
                $stmt->execute(['id' => $assetId]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$row) {
                    json_fail('Asset item not found', 404);
                }
                
                // If no employee_assets record exists but item is assigned, create one now
                if (!$row['employee_asset_id'] && $row['status'] === 'Assigned' && $row['assigned_emp_id']) {
                    try {
                        $empStmt = $pdo->prepare("SELECT emp_id FROM employees WHERE id = :id");
                        $empStmt->execute(['id' => $row['assigned_emp_id']]);
                        $empData = $empStmt->fetch(PDO::FETCH_ASSOC);
                        
                        if ($empData) {
                            $createStmt = $pdo->prepare("INSERT INTO employee_assets (emp_id, asset_id, serial_number, description, assigned_date, status) 
                                                         VALUES (:emp_id, :asset_id, :serial_number, :description, :assigned_date, 'Assigned')");
                            $createStmt->execute([
                                'emp_id' => $empData['emp_id'],
                                'asset_id' => $row['asset_id'],
                                'serial_number' => $row['tracking_id'],
                                'description' => '',
                                'assigned_date' => $row['assigned_date']
                            ]);
                            $row['employee_asset_id'] = $pdo->lastInsertId();
                        }
                    } catch (PDOException $e) {
                        error_log('Failed to create employee_assets record: ' . $e->getMessage());
                        // Continue anyway - fallback to asset_id in URL
                    }
                }
                
                // Use employee_asset_id if available, otherwise use asset item id
                $reportId = $row['employee_asset_id'] ?: $assetId;
                
                json_ok(['asset_id' => $reportId, 'employee_asset_id' => $reportId, 'tracking_id' => $row['tracking_id'], 'asset_name' => $row['asset_name'], 'status' => $row['status']]);
            } catch (PDOException $e) {
                json_fail('Query error: ' . $e->getMessage(), 500);
            }
            break;

        case 'save_print_proof':
            // Save signature image prior to unassign (signed report will be uploaded in unassign flow)
            $itemId = (int)($_POST['item_id'] ?? 0);
            $trackingId = trim($_POST['tracking_id'] ?? '');
            $employeeAssetId = isset($_POST['employee_asset_id']) ? (int)$_POST['employee_asset_id'] : 0;
            $signature = $_POST['signature'] ?? '';

            if ($itemId <= 0 || empty($trackingId)) {
                json_fail('Item ID and Tracking ID are required');
            }

            $uploadDir = __DIR__ . '/../../uploads/asset_returns/';
            if (!is_dir($uploadDir)) {
                @mkdir($uploadDir, 0777, true);
            }

            $savedSignatureFile = '';

            // Handle signature base64 (PNG/JPEG expected)
            if (!empty($signature)) {
                // Expecting data URL like data:image/png;base64,....
                if (preg_match('/^data:image\/(png|jpeg);base64,/', $signature)) {
                    $base64 = preg_replace('/^data:image\/(png|jpeg);base64,/', '', $signature);
                    $base64 = str_replace(' ', '+', $base64);
                    $binary = base64_decode($base64);
                    if ($binary !== false) {
                        $sigExt = (strpos($signature, 'image/jpeg') !== false) ? 'jpg' : 'png';
                        $sigName = 'print_signature_' . preg_replace('/[^A-Za-z0-9_-]/', '_', $trackingId) . '_' . time() . '.' . $sigExt;
                        $sigPath = $uploadDir . $sigName;
                        if (@file_put_contents($sigPath, $binary) !== false) {
                            $savedSignatureFile = 'uploads/asset_returns/' . $sigName;
                        }
                    }
                }
            }

            // If we have an employee asset record, persist the signature for the report
            if ($employeeAssetId > 0 && !empty($savedSignatureFile)) {
                try {
                    $stmt = $pdo->prepare("UPDATE employee_assets SET signature_file = :sig WHERE id = :id");
                    $stmt->execute(['sig' => $savedSignatureFile, 'id' => $employeeAssetId]);
                } catch (PDOException $e) {
                    // Do not block printing on a persistence error; just log it
                    error_log('save_print_proof: failed to update employee_assets.id=' . $employeeAssetId . ' with signature: ' . $e->getMessage());
                }
            }

            json_ok([
                'signature_file' => $savedSignatureFile,
                'tracking_id' => $trackingId,
                'item_id' => $itemId,
                'employee_asset_id' => $employeeAssetId
            ], 'Print proof saved');
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
