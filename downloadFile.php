<?php
// Authentication guard
require_once __DIR__ . '/includes/session_check.php';

// Helper function to redirect safely
function redirectBack() {
    if (!empty($_SERVER['HTTP_REFERER'])) {
        header('Location: ' . $_SERVER['HTTP_REFERER']);
    } else {
        header('Location: dashboard.php');
    }
    exit();
}

// Allowed file extensions for download
$allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt', 'zip'];

// Allowed base directories (relative to application root)
$allowedBaseDirs = [
    realpath(__DIR__ . '/assets/emp_documents'),
    realpath(__DIR__ . '/assets/cars'),
    realpath(__DIR__ . '/assets/machines'),
    realpath(__DIR__ . '/assets/locations'),
    realpath(__DIR__ . '/assets/uploads'),
    realpath(__DIR__ . '/assets/attachments'),
    realpath(__DIR__ . '/assets/gallery'),
];
// Remove any false values from directories that don't exist yet
$allowedBaseDirs = array_filter($allowedBaseDirs);

// Check if file parameter exists and is not empty
if(isset($_GET['file']) && !empty(trim($_GET['file']))){
    // Resolve real path to prevent path traversal attacks
    $requestedPath = realpath(__DIR__ . '/' . $_GET['file']);

    // Safety check: ensure resolved path is within an allowed directory
    $isAllowed = false;
    if ($requestedPath !== false) {
        foreach ($allowedBaseDirs as $baseDir) {
            if (strncmp($requestedPath, $baseDir, strlen($baseDir)) === 0) {
                $isAllowed = true;
                break;
            }
        }
    }

    if (!$isAllowed) {
        http_response_code(403);
        exit('Access denied.');
    }

    $path = $requestedPath;
    $filename = basename($path);

    $file_extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

    // Block disallowed extensions
    if (!in_array($file_extension, $allowedExtensions)) {
        http_response_code(403);
        exit('File type not allowed.');
    }

    if(file_exists($path)) {

        if($file_extension == "jpg" || $file_extension == "jpeg"){
			header('Content-Description: File Transfer');
			header('Content-Type: image/jpeg');
			header('Content-Disposition: attachment; filename='.basename($filename));
			header('Accept-Ranges: bytes');  // For download resume
			header('Content-Transfer-Encoding: binary');
			header('Expires: 0');
			header('Cache-Control: public');
			header('Pragma: public');
			readfile($path);  //this is necessary in order to get it to actually download the file, otherwise it will be 0Kb
		}elseif($file_extension == "pdf"){
//			header('Content-Transfer-Encoding: binary');  // For Gecko browsers mainly
//			header('Last-Modified: ' . gmdate('D, d M Y H:i:s', filemtime($filename)) . ' GMT');
//			header('Accept-Ranges: bytes');  // For download resume
//			header('Content-Length: ' . filesize($filename));  // File size
//			header('Content-Encoding: none');
//			header('Content-Type: application/pdf');  // Change this mime type if the file is not PDF
//			header('Content-Disposition: attachment; filename=' .basename($filename));  // Make the browser display the Save As dialog
//			readfile($path);  //this is necessary in order to get it to actually download the file, otherwise it will be 0Kb
			header('Content-Description: File Transfer');
			header('Content-Type: application/octet-stream');
			header('Content-Disposition: attachment; filename="'.basename($filename).'"');
			header('Expires: 0');
			header('Cache-Control: must-revalidate');
			header('Pragma: public');
			header('Content-Length: ' . filesize($filename));
			flush(); // Flush system output buffer
			readfile($path);
		}
    } else {
        // Redirect to previous page if file not found
        redirectBack();
    }
} else {
    // Redirect to previous page if no file parameter or empty file parameter
    redirectBack();
}
?>