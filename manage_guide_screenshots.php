<?php
require_once(__DIR__ . "/includes/init.php");
require_once(__DIR__ . "/includes/session_check.php");

// Check admin access
$can_see_all_employees = (
		$is_system_admin || 
        $isItManager
	);
if (!$can_see_all_employees) {
    $_SESSION['error_msg'] = sprintf(
        '<div class="col-xl-12">
            <div class="alert alert-danger bg-danger text-white border-0" role="alert">
                <b>Access Denied!</b> 
                <h4>You don\'t have access for ( %s ) Department.</h4>
            </div>
        </div>',
        $emprow["deptnme"]
    );
    header("Location: ./dashboard.php");
    exit;
}

$message = '';
$message_type = '';

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] == 'POST' && (isset($_FILES['screenshot_en']) || isset($_FILES['screenshot_ar']))) {
    $section = $_POST['section'] ?? '';
    $step_number = $_POST['step_number'] ?? 0;
    $title = $_POST['title'] ?? '';
    $title_ar = $_POST['title_ar'] ?? $title;
    
    if (!$section || !$step_number || !$title) {
        $message = 'All fields are required';
        $message_type = 'danger';
    } else {
        $upload_dir = __DIR__ . '/assets/screenshots/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $section_dir = $upload_dir . $section . '/';
        if (!is_dir($section_dir)) {
            mkdir($section_dir, 0755, true);
        }
        
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $uploaded_count = 0;
        $errors = [];
        
        // Process English screenshot
        if (isset($_FILES['screenshot_en']) && $_FILES['screenshot_en']['error'] == 0) {
            $file_en = $_FILES['screenshot_en'];
            
            if (!in_array($file_en['type'], $allowed_types)) {
                $errors[] = 'English: Only image files are allowed';
            } elseif ($file_en['size'] > 5 * 1024 * 1024) {
                $errors[] = 'English: File size must be less than 5MB';
            } else {
                $ext = pathinfo($file_en['name'], PATHINFO_EXTENSION);
                $filename_en = $section . '_' . $step_number . '_en_' . time() . '.' . $ext;
                $file_path_en = $section_dir . $filename_en;
                
                if (move_uploaded_file($file_en['tmp_name'], $file_path_en)) {
                    try {
                        $order_stmt = $pdo->prepare("
                            SELECT COALESCE(MAX(display_order), 0) + 1 as next_order 
                            FROM guide_screenshots 
                            WHERE section = :section AND step_number = :step_number AND language = 'en'
                        ");
                        $order_stmt->execute([':section' => $section, ':step_number' => $step_number]);
                        $order_result = $order_stmt->fetch(PDO::FETCH_ASSOC);
                        $next_order = $order_result['next_order'] ?? 1;
                        
                        $user_id = $_SESSION['user_id'] ?? $_SESSION['id'] ?? 0;
                        
                        $stmt = $pdo->prepare("
                            INSERT INTO guide_screenshots 
                            (section, step_number, title, language, filename, file_path, display_order, uploaded_by)
                            VALUES (:section, :step_number, :title, 'en', :filename, :file_path, :display_order, :user_id)
                        ");
                        
                        $stmt->execute([
                            ':section' => $section,
                            ':step_number' => $step_number,
                            ':title' => $title,
                            ':filename' => $filename_en,
                            ':file_path' => 'assets/screenshots/' . $section . '/' . $filename_en,
                            ':display_order' => $next_order,
                            ':user_id' => $user_id
                        ]);
                        
                        // Log activity
                        $screenshot_id = $pdo->lastInsertId();
                        ActivityLogger::logCreate(
                            'System Guide',
                            'manage_guide_screenshots.php',
                            $screenshot_id,
                            ['section' => $section, 'step' => $step_number, 'language' => 'en', 'filename' => $filename_en],
                            "Uploaded English screenshot: $title (Section: $section, Step: $step_number)",
                            'guide_screenshots'
                        );
                        
                        $uploaded_count++;
                    } catch (PDOException $e) {
                        $errors[] = 'English DB error: ' . $e->getMessage();
                        unlink($file_path_en);
                    }
                }
            }
        }
        
        // Process Arabic screenshot
        if (isset($_FILES['screenshot_ar']) && $_FILES['screenshot_ar']['error'] == 0) {
            $file_ar = $_FILES['screenshot_ar'];
            
            if (!in_array($file_ar['type'], $allowed_types)) {
                $errors[] = 'Arabic: Only image files are allowed';
            } elseif ($file_ar['size'] > 5 * 1024 * 1024) {
                $errors[] = 'Arabic: File size must be less than 5MB';
            } else {
                $ext = pathinfo($file_ar['name'], PATHINFO_EXTENSION);
                $filename_ar = $section . '_' . $step_number . '_ar_' . time() . '.' . $ext;
                $file_path_ar = $section_dir . $filename_ar;
                
                if (move_uploaded_file($file_ar['tmp_name'], $file_path_ar)) {
                    try {
                        $order_stmt = $pdo->prepare("
                            SELECT COALESCE(MAX(display_order), 0) + 1 as next_order 
                            FROM guide_screenshots 
                            WHERE section = :section AND step_number = :step_number AND language = 'ar'
                        ");
                        $order_stmt->execute([':section' => $section, ':step_number' => $step_number]);
                        $order_result = $order_stmt->fetch(PDO::FETCH_ASSOC);
                        $next_order = $order_result['next_order'] ?? 1;
                        
                        $user_id = $_SESSION['user_id'] ?? $_SESSION['id'] ?? 0;
                        
                        $stmt = $pdo->prepare("
                            INSERT INTO guide_screenshots 
                            (section, step_number, title, language, filename, file_path, display_order, uploaded_by)
                            VALUES (:section, :step_number, :title, 'ar', :filename, :file_path, :display_order, :user_id)
                        ");
                        
                        $stmt->execute([
                            ':section' => $section,
                            ':step_number' => $step_number,
                            ':title' => $title_ar,
                            ':filename' => $filename_ar,
                            ':file_path' => 'assets/screenshots/' . $section . '/' . $filename_ar,
                            ':display_order' => $next_order,
                            ':user_id' => $user_id
                        ]);
                        
                        // Log activity
                        $screenshot_id = $pdo->lastInsertId();
                        ActivityLogger::logCreate(
                            'System Guide',
                            'manage_guide_screenshots.php',
                            $screenshot_id,
                            ['section' => $section, 'step' => $step_number, 'language' => 'ar', 'filename' => $filename_ar],
                            "Uploaded Arabic screenshot: $title_ar (Section: $section, Step: $step_number)",
                            'guide_screenshots'
                        );
                        
                        $uploaded_count++;
                    } catch (PDOException $e) {
                        $errors[] = 'Arabic DB error: ' . $e->getMessage();
                        unlink($file_path_ar);
                    }
                }
            }
        }
        
        if ($uploaded_count > 0) {
            $message = "Successfully uploaded $uploaded_count screenshot(s)!";
            $message_type = 'success';
        }
        
        if (!empty($errors)) {
            $message .= (!empty($message) ? '<br>' : '') . implode('<br>', $errors);
            $message_type = empty($uploaded_count) ? 'danger' : 'warning';
        }
        
        if ($uploaded_count == 0 && empty($errors)) {
            $message = 'Please select at least one screenshot to upload';
            $message_type = 'warning';
        }
    }
}

// Handle delete
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_id'])) {
    $delete_id = $_POST['delete_id'];
    
    try {
        // Get screenshot details before deleting
        $stmt = $pdo->prepare("SELECT * FROM guide_screenshots WHERE id = :id");
        $stmt->execute([':id' => $delete_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            $file_path = __DIR__ . '/' . $result['file_path'];
            if (file_exists($file_path)) {
                unlink($file_path);
            }
            
            $delete_stmt = $pdo->prepare("DELETE FROM guide_screenshots WHERE id = :id");
            $delete_stmt->execute([':id' => $delete_id]);
            
            // Log deletion
            ActivityLogger::logDelete(
                'System Guide',
                'manage_guide_screenshots.php',
                $delete_id,
                [
                    'title' => $result['title'],
                    'section' => $result['section'],
                    'step_number' => $result['step_number'],
                    'language' => $result['language'],
                    'filename' => $result['filename']
                ],
                "Deleted screenshot: {$result['title']} (Section: {$result['section']}, Step: {$result['step_number']}, Lang: {$result['language']})",
                'guide_screenshots'
            );
            
            $message = 'Screenshot deleted successfully';
            $message_type = 'success';
        }
    } catch (PDOException $e) {
        $message = 'Error deleting screenshot: ' . $e->getMessage();
        $message_type = 'danger';
    }
}

// Handle update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_id'])) {
    $update_id = $_POST['update_id'];
    $section = $_POST['section'] ?? '';
    $step_number = $_POST['step_number'] ?? 0;
    $title = $_POST['title'] ?? '';
    
    if (!$section || !$step_number || !$title) {
        $message = 'All fields are required';
        $message_type = 'danger';
    } else {
        try {
            // Get old values for logging
            $old_stmt = $pdo->prepare("SELECT * FROM guide_screenshots WHERE id = :id");
            $old_stmt->execute([':id' => $update_id]);
            $old_data = $old_stmt->fetch(PDO::FETCH_ASSOC);
            
            // Update basic fields
            $stmt = $pdo->prepare("
                UPDATE guide_screenshots 
                SET section = :section, step_number = :step_number, title = :title
                WHERE id = :id
            ");
            $stmt->execute([
                ':section' => $section,
                ':step_number' => $step_number,
                ':title' => $title,
                ':id' => $update_id
            ]);
            
            // Log basic field updates
            $changes = [];
            $old_values = [];
            $new_values = [];
            
            if ($old_data['section'] != $section) {
                $changes[] = "Section: {$old_data['section']} → $section";
                $old_values['section'] = $old_data['section'];
                $new_values['section'] = $section;
            }
            if ($old_data['step_number'] != $step_number) {
                $changes[] = "Step: {$old_data['step_number']} → $step_number";
                $old_values['step_number'] = $old_data['step_number'];
                $new_values['step_number'] = $step_number;
            }
            if ($old_data['title'] != $title) {
                $changes[] = "Title: {$old_data['title']} → $title";
                $old_values['title'] = $old_data['title'];
                $new_values['title'] = $title;
            }
            
            if (!empty($changes)) {
                ActivityLogger::logUpdate(
                    'System Guide',
                    'manage_guide_screenshots.php',
                    $update_id,
                    $old_values,
                    $new_values,
                    "Updated screenshot: " . implode(", ", $changes),
                    'guide_screenshots'
                );
            }
            
            // Handle screenshot replacement if provided
            if (isset($_FILES['screenshot']) && $_FILES['screenshot']['error'] == 0) {
                $file = $_FILES['screenshot'];
                $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                
                if (!in_array($file['type'], $allowed_types)) {
                    $message = 'Only image files are allowed';
                    $message_type = 'danger';
                } elseif ($file['size'] > 5 * 1024 * 1024) {
                    $message = 'File size must be less than 5MB';
                    $message_type = 'danger';
                } else {
                    // Get old file path
                    $old_stmt = $pdo->prepare("SELECT file_path, language FROM guide_screenshots WHERE id = :id");
                    $old_stmt->execute([':id' => $update_id]);
                    $old_data = $old_stmt->fetch(PDO::FETCH_ASSOC);
                    
                    // Delete old file
                    if ($old_data) {
                        $old_file = __DIR__ . '/' . $old_data['file_path'];
                        if (file_exists($old_file)) {
                            unlink($old_file);
                        }
                    }
                    
                    // Upload new file
                    $upload_dir = __DIR__ . '/assets/screenshots/';
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0755, true);
                    }
                    
                    $section_dir = $upload_dir . $section . '/';
                    if (!is_dir($section_dir)) {
                        mkdir($section_dir, 0755, true);
                    }
                    
                    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                    $lang = $old_data['language'] ?? 'en';
                    $filename = $section . '_' . $step_number . '_' . $lang . '_' . time() . '.' . $ext;
                    $file_path = $section_dir . $filename;
                    
                    if (move_uploaded_file($file['tmp_name'], $file_path)) {
                        $update_file_stmt = $pdo->prepare("
                            UPDATE guide_screenshots 
                            SET filename = :filename, file_path = :file_path
                            WHERE id = :id
                        ");
                        $update_file_stmt->execute([
                            ':filename' => $filename,
                            ':file_path' => 'assets/screenshots/' . $section . '/' . $filename,
                            ':id' => $update_id
                        ]);
                        
                        // Log file replacement
                        ActivityLogger::logUpdate(
                            'System Guide',
                            'manage_guide_screenshots.php',
                            $update_id,
                            ['filename' => $old_data['filename']],
                            ['filename' => $filename],
                            "Replaced screenshot file for: $title",
                            'guide_screenshots'
                        );
                    }
                }
            }
            
            $message = 'Screenshot updated successfully';
            $message_type = 'success';
        } catch (PDOException $e) {
            $message = 'Error updating screenshot: ' . $e->getMessage();
            $message_type = 'danger';
        }
    }
}

// Get all screenshots
try {
    $stmt = $pdo->query("
        SELECT * FROM guide_screenshots 
        WHERE is_active = 1 
        ORDER BY section, step_number, display_order
    ");
    $screenshots = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $screenshots = [];
    $message = 'Error fetching screenshots: ' . $e->getMessage();
    $message_type = 'danger';
}

$sections = ['vacations', 'loans', 'excuse', 'resignation', 'rejoin'];
?>

<!DOCTYPE html>
<html lang="<?= $current_lang ?? 'en' ?>" <?= ($is_rtl ?? false) ? 'dir="rtl"' : '' ?>>
<head>
    <meta charset="utf-8" />
    <title><?= $site_title ?> - Manage Guide Screenshots</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    
    <link rel="shortcut icon" href="<?= get_setting($conDB, 'favicon') ?>">
    <link href="assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/style.css" rel="stylesheet" type="text/css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" />
    
    <style>
        body { background: #f5f5f5; }
        .container { padding: 20px; }
        .upload-box { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); margin-bottom: 30px; }
        .upload-box h3 { margin-bottom: 20px; color: #333; }
        .form-group label { font-weight: 600; color: #555; }
        .preview-img { max-width: 100%; max-height: 300px; margin-top: 10px; border-radius: 8px; }
        .screenshot-item { background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.1); margin-bottom: 15px; }
        .screenshot-item img { width: 100%; height: 300px; object-fit: cover; }
        .screenshot-info { padding: 15px; }
        .screenshot-info h5 { margin: 0 0 10px 0; color: #333; font-size: 16px; font-weight: 600; }
        .screenshot-info p { margin: 5px 0 12px 0; color: #666; font-size: 14px; }
        .screenshot-info button { margin-right: 8px; margin-bottom: 8px; }
        .btn-info { background: #17a2b8 !important; color: white !important; border: none !important; padding: 6px 12px !important; border-radius: 4px !important; cursor: pointer !important; font-size: 13px !important; transition: all 0.2s ease !important; }
        .btn-info:hover { background: #138496 !important; }
        .btn-info i { margin-right: 4px; }
        .delete-btn { background: #dc3545; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; font-size: 13px; margin-right: 8px; margin-bottom: 8px; transition: all 0.2s ease; }
        .delete-btn:hover { background: #c82333; }
        .badge-section { background: #007bff; color: white; padding: 4px 8px; border-radius: 4px; font-size: 12px; margin-right: 5px; }
        .alert { margin-bottom: 20px; }
        
        /* DataTable Styles */
        .table-responsive { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .dataTables_wrapper .dataTables_filter input { border: 1px solid #ddd; border-radius: 4px; padding: 6px 12px; }
        .dataTables_wrapper .dataTables_length select { border: 1px solid #ddd; border-radius: 4px; padding: 6px 12px; }
        table.dataTable thead th { background: #f8f9fa; font-weight: 600; border-bottom: 2px solid #dee2e6; }
        table.dataTable tbody td { vertical-align: middle; }
        .action-buttons { display: flex; gap: 5px; }
        .btn-action { padding: 5px 10px; border: none; border-radius: 4px; cursor: pointer; font-size: 13px; transition: all 0.2s; }
        .btn-action i { margin-right: 3px; }
        .btn-view { background: #17a2b8; color: white; }
        .btn-view:hover { background: #138496; }
        .btn-edit { background: #ffc107; color: #000; }
        .btn-edit:hover { background: #e0a800; }
        .btn-delete { background: #dc3545; color: white; }
        .btn-delete:hover { background: #c82333; }
        .screenshot-thumb { width: 80px; height: 60px; object-fit: cover; border-radius: 4px; cursor: pointer; }

        /* Zoom Modal Styles */
        .zoom-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.95);
            z-index: 9999;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .zoom-modal-content {
            position: relative;
            width: 90%;
            height: 90%;
            display: flex;
            flex-direction: column;
            background: #1a1a1a;
            border-radius: 8px;
            box-shadow: 0 0 50px rgba(0, 0, 0, 0.8);
        }

        .zoom-close {
            position: absolute;
            top: 10px;
            right: 20px;
            color: white;
            font-size: 40px;
            cursor: pointer;
            z-index: 10000;
            transition: all 0.2s ease;
        }

        .zoom-close:hover {
            color: #ff6b6b;
            transform: scale(1.2);
        }

        .zoom-toolbar {
            background: #2a2a2a;
            padding: 12px 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            border-bottom: 1px solid #444;
            flex-wrap: wrap;
        }

        .zoom-btn {
            background: #444;
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.2s ease;
        }

        .zoom-btn:hover {
            background: #555;
            transform: scale(1.05);
        }

        .zoom-level {
            color: #aaa;
            font-size: 14px;
            font-weight: bold;
            margin: 0 10px;
        }

        .zoom-container {
            flex: 1;
            overflow: auto;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
        }

        #zoomImage {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
            transition: transform 0.1s ease;
            cursor: grab;
        }

        #zoomImage:active {
            cursor: grabbing;
        }

        .zoom-title {
            background: #2a2a2a;
            color: #aaa;
            padding: 12px 20px;
            border-top: 1px solid #444;
            font-size: 14px;
            text-align: center;
            max-height: 60px;
            overflow: auto;
        }

        @media (max-width: 768px) {
            .zoom-modal-content {
                width: 95%;
                height: 95%;
            }

            .zoom-toolbar {
                padding: 8px 12px;
                gap: 6px;
            }

            .zoom-btn {
                padding: 6px 10px;
                font-size: 12px;
            }

            .zoom-level {
                font-size: 12px;
                margin: 0 5px;
            }
        }
    </style>
</head>
<body dir="<?= ($is_rtl ?? false) ? 'rtl' : 'ltr' ?>">
    <div class="container-fluid" style="padding: 20px;">
        <!-- Back Button -->
        <div class="mb-3">
            <a href="system_guide.php" class="btn btn-secondary">
                <i class="fa fa-arrow-left"></i> Back to Guide
            </a>
        </div>

        <h1 class="mb-4"><i class="fa fa-image"></i> Manage Guide Screenshots</h1>

        <!-- Upload Form with Multiple Selection -->
        <div class="upload-box">
            <h3><i class="fa fa-upload"></i> Upload Screenshots</h3>
            
            <div class="alert alert-info mb-4">
                <i class="fa fa-lightbulb"></i> <strong>Tip:</strong> Select section first to see recommended steps. You can upload multiple images for the same step or add one step at a time.
            </div>

            <form method="POST" enctype="multipart/form-data" id="uploadForm">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label for="section">Section *</label>
                            <select name="section" id="section" class="form-control" required onchange="updateSteps()">
                                <option value="">-- Select Section --</option>
                                <option value="vacations">Vacations & Leaves</option>
                                <option value="loans">Loans</option>
                                <option value="excuse">Excuse Leave</option>
                                <option value="resignation">Resignation</option>
                                <option value="rejoin">Rejoin Request</option>
                            </select>
                            <small class="text-muted d-block mt-2" id="sectionInfo"></small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label for="step_number">Step Number *</label>
                            <select name="step_number" id="step_number" class="form-control" required>
                                <option value="">-- Select Step --</option>
                            </select>
                            <small class="text-muted d-block mt-2" id="stepInfo"></small>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label for="title">English Title / Description *</label>
                            <input type="text" name="title" id="title" class="form-control" required placeholder="e.g., Go to Profile Page, Click More Button">
                            <small class="text-muted">Description in English</small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label for="title_ar">Arabic Title / Description (Optional)</label>
                            <input type="text" name="title_ar" id="title_ar" class="form-control" placeholder="مثلا: اذهب إلى صفحة الملف الشخصي" dir="rtl">
                            <small class="text-muted">Description in Arabic (defaults to English if empty)</small>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label for="screenshot_en">
                                <span class="badge bg-primary">🇬🇧 EN</span> English Screenshot (JPEG, PNG, GIF, WebP - Max 5MB)
                            </label>
                            <div class="input-group">
                                <input type="file" name="screenshot_en" id="screenshot_en" class="form-control" accept="image/*" onchange="previewImage(this, 'en')">
                                <button class="btn btn-outline-primary" type="button" onclick="document.getElementById('screenshot_en').click()">
                                    <i class="fa fa-folder-open"></i> Browse
                                </button>
                            </div>
                            <small class="text-muted d-block mt-2">Upload at least one language version</small>
                        </div>
                        
                        <!-- English Preview -->
                        <div id="previewContainerEn" style="display:none; margin-bottom: 20px;">
                            <label><span class="badge bg-primary">EN Preview</span></label>
                            <div style="border: 2px dashed #007bff; border-radius: 8px; padding: 15px; text-align: center;">
                                <img id="previewEn" class="preview-img" style="max-height: 300px;">
                                <div id="fileInfoEn" style="margin-top: 10px; color: #666; font-size: 14px;"></div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label for="screenshot_ar">
                                <span class="badge bg-success">🇸🇦 AR</span> Arabic Screenshot (JPEG, PNG, GIF, WebP - Max 5MB)
                            </label>
                            <div class="input-group">
                                <input type="file" name="screenshot_ar" id="screenshot_ar" class="form-control" accept="image/*" onchange="previewImage(this, 'ar')">
                                <button class="btn btn-outline-success" type="button" onclick="document.getElementById('screenshot_ar').click()">
                                    <i class="fa fa-folder-open"></i> Browse
                                </button>
                            </div>
                            <small class="text-muted d-block mt-2">Upload at least one language version</small>
                        </div>
                        
                        <!-- Arabic Preview -->
                        <div id="previewContainerAr" style="display:none; margin-bottom: 20px;">
                            <label><span class="badge bg-success">AR Preview</span></label>
                            <div style="border: 2px dashed #28a745; border-radius: 8px; padding: 15px; text-align: center;">
                                <img id="previewAr" class="preview-img" style="max-height: 300px;">
                                <div id="fileInfoAr" style="margin-top: 10px; color: #666; font-size: 14px;"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-group mb-3">
                    <label>
                        <input type="checkbox" id="continueUpload" checked>
                        Continue uploading more screenshots after this one
                    </label>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="fa fa-cloud-upload"></i> Upload Screenshot
                    </button>
                    <button type="reset" class="btn btn-secondary btn-lg">
                        <i class="fa fa-redo"></i> Reset Form
                    </button>
                </div>
            </form>

            <!-- Step Information Guide -->
            <div class="mt-5 p-4" style="background: #f8f9fa; border-radius: 8px; border-left: 4px solid #007bff;">
                <h5 style="margin-bottom: 15px;"><i class="fa fa-info-circle"></i> Screenshot Steps Guide</h5>
                <div id="stepGuide" style="font-size: 14px; color: #555;">
                    <p class="text-muted">Select a section above to see recommended steps for that section.</p>
                </div>
            </div>
        </div>

        <!-- Screenshots DataTable -->
        <div class="table-responsive">
            <h3 class="mb-4"><i class="fa fa-list"></i> All Screenshots</h3>
            <table id="screenshotsTable" class="table table-striped table-bordered" style="width:100%">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Thumbnail</th>
                        <th>Section</th>
                        <th>Step</th>
                        <th>Language</th>
                        <th>Title</th>
                        <th>Order</th>
                        <th>Uploaded</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($screenshots as $shot): ?>
                        <tr>
                            <td><?= $shot['id'] ?></td>
                            <td>
                                <img src="<?= $shot['file_path'] ?>" 
                                     alt="<?= htmlspecialchars($shot['title']) ?>" 
                                     class="screenshot-thumb"
                                     onclick="openZoomModal('<?= htmlspecialchars($shot['file_path'], ENT_QUOTES) ?>', '<?= htmlspecialchars($shot['title'], ENT_QUOTES) ?>')"
                                     onerror="this.src='assets/placeholder.png'">
                            </td>
                            <td><?= ucfirst(str_replace('_', ' ', $shot['section'])) ?></td>
                            <td><?= $shot['step_number'] ?></td>
                            <td>
                                <?php 
                                $lang = $shot['language'] ?? 'en';
                                $badge_color = $lang === 'en' ? 'primary' : 'success';
                                $flag = $lang === 'en' ? '🇬🇧' : '🇸🇦';
                                ?>
                                <span class="badge bg-<?= $badge_color ?>"><?= $flag ?> <?= strtoupper($lang) ?></span>
                            </td>
                            <td><?= htmlspecialchars($shot['title']) ?></td>
                            <td><?= $shot['display_order'] ?></td>
                            <td><?= date('M d, Y', strtotime($shot['created_at'])) ?></td>
                            <td>
                                <div class="action-buttons">
                                    <button type="button" class="btn-action btn-view" 
                                            onclick="openZoomModal('<?= htmlspecialchars($shot['file_path'], ENT_QUOTES) ?>', '<?= htmlspecialchars($shot['title'], ENT_QUOTES) ?>')"
                                            title="View">
                                        <i class="fa fa-eye"></i>
                                    </button>
                                    <button type="button" class="btn-action btn-edit" 
                                            onclick="editScreenshot(<?= htmlspecialchars(json_encode($shot), ENT_QUOTES) ?>)"
                                            title="Edit">
                                        <i class="fa fa-edit"></i>
                                    </button>
                                    <button type="button" class="btn-action btn-delete" 
                                            onclick="deleteScreenshot(<?= $shot['id'] ?>, '<?= htmlspecialchars($shot['title'], ENT_QUOTES) ?>')"
                                            title="Delete">
                                        <i class="fa fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if (empty($screenshots)): ?>
            <div class="alert alert-info">
                <i class="fa fa-info-circle"></i> No screenshots uploaded yet. Upload your first screenshot above!
            </div>
        <?php endif; ?>
    </div>

    <!-- Zoom Modal -->
    <div id="zoomModal" class="zoom-modal" style="display: none;">
        <div class="zoom-modal-content">
            <span class="zoom-close" onclick="closeZoomModal()">&times;</span>
            <div class="zoom-toolbar">
                <button class="zoom-btn" onclick="zoomIn()" title="Zoom In (Ctrl++)">
                    <i class="fa fa-plus"></i>
                </button>
                <button class="zoom-btn" onclick="zoomOut()" title="Zoom Out (Ctrl+-)">
                    <i class="fa fa-minus"></i>
                </button>
                <button class="zoom-btn" onclick="resetZoom()" title="Reset Zoom (Ctrl+0)">
                    <i class="fa fa-arrows-alt"></i>
                </button>
                <span class="zoom-level" id="zoomLevel">100%</span>
                <button class="zoom-btn" onclick="downloadImage()" title="Download">
                    <i class="fa fa-download"></i>
                </button>
            </div>
            <div class="zoom-container" id="zoomContainer">
                <img id="zoomImage" src="" alt="Zoomed image">
            </div>
            <div class="zoom-title" id="zoomTitle"></div>
        </div>
    </div>

    <script src="assets/js/jquery.min.js"></script>
    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

    <script>
        // Show SweetAlert2 message if there's a message from PHP
        <?php if ($message): ?>
            Swal.fire({
                icon: '<?= $message_type === "success" ? "success" : ($message_type === "warning" ? "warning" : "error") ?>',
                title: '<?= $message_type === "success" ? "Success!" : ($message_type === "warning" ? "Warning!" : "Error!") ?>',
                html: '<?= addslashes($message) ?>',
                confirmButtonColor: '<?= $message_type === "success" ? "#28a745" : ($message_type === "warning" ? "#ffc107" : "#dc3545") ?>',
                confirmButtonText: 'OK',
                allowOutsideClick: false,
                timer: <?= $message_type === "success" ? "3000" : "null" ?>,
                timerProgressBar: <?= $message_type === "success" ? "true" : "false" ?>
            }).then((result) => {
                // Redirect to avoid form resubmission
                window.location.href = 'manage_guide_screenshots.php';
            });
        <?php endif; ?>

        // Initialize DataTable
        $(document).ready(function() {
            $('#screenshotsTable').DataTable({
                order: [[0, 'desc']],
                pageLength: 25,
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
                columnDefs: [
                    { orderable: false, targets: [1, 8] } // Disable sorting on thumbnail and actions
                ]
            });
        });

        // Edit Screenshot Function
        function editScreenshot(screenshot) {
            // Generate step options based on current section
            const getStepOptionsHTML = (section, currentStep) => {
                const stepGuides = {
                    vacations: {
                        1: "Annual Leave",
                        2: "Emergency Leave",
                        3: "Encashment"
                    },
                    loans: {
                        1: "EOS Loan",
                        2: "House Loan",
                        3: "Advance Salary"
                    },
                    excuse: {
                        3: "Excuse Leave"
                    },
                    resignation: {
                        1: "Resignation"
                    },
                    rejoin: {
                        3: "Rejoin"
                    }
                };
                
                let options = '<option value="">-- Select Step --</option>';
                if (stepGuides[section]) {
                    Object.keys(stepGuides[section]).forEach(stepNum => {
                        const stepName = stepGuides[section][stepNum];
                        const selected = stepNum == currentStep ? 'selected' : '';
                        options += `<option value="${stepNum}" ${selected}>Step ${stepNum}: ${stepName}</option>`;
                    });
                }
                return options;
            };
            
            Swal.fire({
                title: 'Edit Screenshot',
                html: `
                    <div style="text-align: left;">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Section</label>
                            <select id="edit_section" class="form-control" onchange="updateEditSteps()">
                                <option value="vacations" ${screenshot.section === 'vacations' ? 'selected' : ''}>Vacations & Leaves</option>
                                <option value="loans" ${screenshot.section === 'loans' ? 'selected' : ''}>Loans</option>
                                <option value="excuse" ${screenshot.section === 'excuse' ? 'selected' : ''}>Excuse Leave</option>
                                <option value="resignation" ${screenshot.section === 'resignation' ? 'selected' : ''}>Resignation</option>
                                <option value="rejoin" ${screenshot.section === 'rejoin' ? 'selected' : ''}>Rejoin Request</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">Step Number</label>
                            <select id="edit_step" class="form-control">
                                ${getStepOptionsHTML(screenshot.section, screenshot.step_number)}
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">Title / Description</label>
                            <input type="text" id="edit_title" class="form-control" value="${screenshot.title}">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">Current Screenshot</label>
                            <div style="text-align: center; margin: 10px 0;">
                                <img src="${screenshot.file_path}" style="max-width: 100%; max-height: 200px; border-radius: 8px; border: 2px solid #ddd;">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">Replace Screenshot (Optional)</label>
                            <input type="file" id="edit_screenshot" class="form-control" accept="image/jpeg,image/png,image/gif,image/webp">
                            <small class="text-muted">Leave empty to keep current screenshot. Max 5MB (JPEG, PNG, GIF, WebP)</small>
                        </div>
                    </div>
                `,
                width: '600px',
                showCancelButton: true,
                confirmButtonText: '<i class="fa fa-save"></i> Update',
                cancelButtonText: 'Cancel',
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#6c757d',
                didOpen: () => {
                    // Add event listener for section change
                    window.updateEditSteps = function() {
                        const section = document.getElementById('edit_section').value;
                        const stepSelect = document.getElementById('edit_step');
                        stepSelect.innerHTML = getStepOptionsHTML(section, '');
                    };
                },
                preConfirm: () => {
                    const section = document.getElementById('edit_section').value;
                    const step = document.getElementById('edit_step').value;
                    const title = document.getElementById('edit_title').value;
                    const fileInput = document.getElementById('edit_screenshot');
                    
                    if (!section || !step || !title) {
                        Swal.showValidationMessage('All fields are required');
                        return false;
                    }
                    
                    if (fileInput.files.length > 0) {
                        const file = fileInput.files[0];
                        if (file.size > 5 * 1024 * 1024) {
                            Swal.showValidationMessage('File size must be less than 5MB');
                            return false;
                        }
                    }
                    
                    return { section, step, title, file: fileInput.files[0] };
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    const formData = new FormData();
                    formData.append('update_id', screenshot.id);
                    formData.append('section', result.value.section);
                    formData.append('step_number', result.value.step);
                    formData.append('title', result.value.title);
                    
                    if (result.value.file) {
                        formData.append('screenshot', result.value.file);
                    }
                    
                    // Submit via AJAX
                    fetch('manage_guide_screenshots.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.text())
                    .then(() => {
                        Swal.fire({
                            icon: 'success',
                            title: 'Updated!',
                            text: 'Screenshot has been updated successfully',
                            confirmButtonColor: '#28a745',
                            timer: 2000,
                            timerProgressBar: true
                        }).then(() => {
                            window.location.href = 'manage_guide_screenshots.php';
                        });
                    })
                    .catch(error => {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: 'Failed to update screenshot',
                            confirmButtonColor: '#dc3545'
                        });
                    });
                }
            });
        }

        // Delete Screenshot Function
        function deleteScreenshot(id, title) {
            Swal.fire({
                title: 'Delete Screenshot?',
                html: `Are you sure you want to delete:<br><strong>${title}</strong>`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '<i class="fa fa-trash"></i> Yes, delete it!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    const formData = new FormData();
                    formData.append('delete_id', id);
                    
                    fetch('manage_guide_screenshots.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.text())
                    .then(() => {
                        Swal.fire({
                            icon: 'success',
                            title: 'Deleted!',
                            text: 'Screenshot has been deleted',
                            confirmButtonColor: '#28a745',
                            timer: 2000,
                            timerProgressBar: true
                        }).then(() => {
                            window.location.href = 'manage_guide_screenshots.php';
                        });
                    })
                    .catch(error => {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: 'Failed to delete screenshot',
                            confirmButtonColor: '#dc3545'
                        });
                    });
                }
            });
        }

        // Step information for each section
        const stepGuides = {
            vacations: {
                info: "Vacations & Leaves section has 3 subsections with 7 steps each",
                steps: {
                    1: { name: "Annual Leave", desc: "7 steps: Profile → More → Apply → Form → Dates → Type → Submit" },
                    2: { name: "Emergency Leave", desc: "6-7 steps: Similar to Annual Leave but for emergency situations" },
                    3: { name: "Encashment", desc: "6-7 steps: Convert unused vacation days to cash" }
                }
            },
            loans: {
                info: "Loans section has 3 types with 4 steps each",
                steps: {
                    1: { name: "EOS Loan", desc: "4 steps: Select → Amount → Installment → Review & Submit" },
                    2: { name: "House Loan", desc: "4 steps: Select → Property Details → Upload Contract → Loan Details" },
                    3: { name: "Advance Salary", desc: "4 steps: Select → Amount → Repayment Period → Submit" }
                }
            },
            excuse: {
                info: "Excuse Leave section needs 4 steps",
                steps: {
                    3: { name: "Excuse Leave", desc: "4 steps: Select → Date → Reason → Submit" }
                }
            },
            resignation: {
                info: "Resignation section needs 3 steps",
                steps: {
                    1: { name: "Resignation", desc: "3 steps: Select → Fill Form → Confirm & Submit" }
                }
            },
            rejoin: {
                info: "Rejoin Request section needs 3 steps",
                steps: {
                    3: { name: "Rejoin", desc: "3 steps: Select → Confirm Date → Submit" }
                }
            }
        };

        function updateSteps() {
            const section = document.getElementById('section').value;
            const stepSelect = document.getElementById('step_number');
            const sectionInfo = document.getElementById('sectionInfo');
            const stepGuideDiv = document.getElementById('stepGuide');
            
            // Clear previous options
            stepSelect.innerHTML = '<option value="">-- Select Step --</option>';
            stepSelect.disabled = !section;
            
            if (!section) {
                sectionInfo.textContent = '';
                stepGuideDiv.innerHTML = '<p class="text-muted">Select a section above to see recommended steps for that section.</p>';
                return;
            }
            
            const sectionData = stepGuides[section];
            if (sectionData) {
                sectionInfo.textContent = sectionData.info;
                
                // Add steps to dropdown
                let guideHTML = '<div style="margin-bottom: 15px;"><strong>Steps for ' + section.toUpperCase() + ':</strong></div>';
                
                Object.keys(sectionData.steps).forEach(stepNum => {
                    const stepData = sectionData.steps[stepNum];
                    const option = document.createElement('option');
                    option.value = stepNum;
                    option.textContent = 'Step ' + stepNum + ': ' + stepData.name;
                    stepSelect.appendChild(option);
                    
                    guideHTML += `
                        <div style="margin-bottom: 12px; padding: 10px; background: white; border-radius: 4px; border-left: 3px solid #007bff;">
                            <strong>Step ${stepNum}: ${stepData.name}</strong><br>
                            <small style="color: #666;">${stepData.desc}</small>
                        </div>
                    `;
                });
                
                stepGuideDiv.innerHTML = guideHTML;
            }
        }

        function previewImage(input, lang) {
            const previewContainer = document.getElementById('previewContainer' + (lang === 'en' ? 'En' : 'Ar'));
            const preview = document.getElementById('preview' + (lang === 'en' ? 'En' : 'Ar'));
            const fileInfo = document.getElementById('fileInfo' + (lang === 'en' ? 'En' : 'Ar'));
            
            if (input.files && input.files[0]) {
                const file = input.files[0];
                const reader = new FileReader();
                
                // Show file info
                const fileSizeMB = (file.size / (1024 * 1024)).toFixed(2);
                const langLabel = lang === 'en' ? 'US English' : '🇸🇦 Arabic';
                fileInfo.innerHTML = `
                    <strong>${langLabel}: ${file.name}</strong><br>
                    Size: ${fileSizeMB} MB | Type: ${file.type}
                    ${fileSizeMB > 2 ? '<br><span style="color: #ff6b6b;">⚠️ Tip: Consider compressing this image</span>' : ''}
                `;
                
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    previewContainer.style.display = 'block';
                };
                reader.readAsDataURL(file);
            } else {
                previewContainer.style.display = 'none';
            }
        }

        // Handle form submission
        document.getElementById('uploadForm').addEventListener('submit', function(e) {
            // Validate that at least one screenshot is uploaded
            const hasEnglish = document.getElementById('screenshot_en').files.length > 0;
            const hasArabic = document.getElementById('screenshot_ar').files.length > 0;
            
            if (!hasEnglish && !hasArabic) {
                e.preventDefault();
                alert('Please upload at least one screenshot (English or Arabic)');
                return false;
            }
            
            const continueUpload = document.getElementById('continueUpload').checked;
            
            // If continue checkbox is checked, don't prevent default
            // The form will submit normally and stay on the page
            if (continueUpload) {
                // The form will auto-refresh and keep the section/step selected
                const section = document.getElementById('section').value;
                const step = document.getElementById('step_number').value;
                
                // After successful upload, we could pre-fill for convenience
                // This is handled by the PHP response
            }
        });

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            // If there's a message, auto-clear the form if user wants to continue
            const continueUpload = document.getElementById('continueUpload').checked;
            if (continueUpload) {
                document.getElementById('title').focus();
            }
        });

        // Zoom Functionality
        let currentZoom = 100;
        const minZoom = 50;
        const maxZoom = 300;
        const zoomStep = 10;

        function openZoomModal(imagePath, title) {
            const modal = document.getElementById('zoomModal');
            const zoomImage = document.getElementById('zoomImage');
            const zoomTitle = document.getElementById('zoomTitle');
            
            zoomImage.src = imagePath;
            zoomTitle.textContent = title || 'Screenshot';
            currentZoom = 100;
            updateZoomLevel();
            modal.style.display = 'flex';
            
            // Add keyboard listeners
            document.addEventListener('keydown', handleZoomKeys);
        }

        function closeZoomModal() {
            const modal = document.getElementById('zoomModal');
            modal.style.display = 'none';
            document.removeEventListener('keydown', handleZoomKeys);
        }

        function zoomIn() {
            if (currentZoom < maxZoom) {
                currentZoom = Math.min(currentZoom + zoomStep, maxZoom);
                updateZoomLevel();
            }
        }

        function zoomOut() {
            if (currentZoom > minZoom) {
                currentZoom = Math.max(currentZoom - zoomStep, minZoom);
                updateZoomLevel();
            }
        }

        function resetZoom() {
            currentZoom = 100;
            updateZoomLevel();
        }

        function updateZoomLevel() {
            const zoomImage = document.getElementById('zoomImage');
            const zoomLevel = document.getElementById('zoomLevel');
            
            zoomImage.style.transform = `scale(${currentZoom / 100})`;
            zoomLevel.textContent = currentZoom + '%';
        }

        function downloadImage() {
            const zoomImage = document.getElementById('zoomImage');
            const zoomTitle = document.getElementById('zoomTitle');
            const link = document.createElement('a');
            link.href = zoomImage.src;
            link.download = zoomTitle.textContent.replace(/\s+/g, '_') + '.png';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }

        function handleZoomKeys(event) {
            if (!document.getElementById('zoomModal').style.display || document.getElementById('zoomModal').style.display === 'none') {
                return;
            }
            
            switch(event.key) {
                case '+':
                case '=':
                    event.preventDefault();
                    zoomIn();
                    break;
                case '-':
                case '_':
                    event.preventDefault();
                    zoomOut();
                    break;
                case '0':
                    if (event.ctrlKey || event.metaKey) {
                        event.preventDefault();
                        resetZoom();
                    }
                    break;
                case 'Escape':
                    closeZoomModal();
                    break;
            }
        }

        // Close modal on outside click
        document.getElementById('zoomModal').addEventListener('click', function(event) {
            if (event.target === this) {
                closeZoomModal();
            }
        });
    </script>
</body>
</html>
