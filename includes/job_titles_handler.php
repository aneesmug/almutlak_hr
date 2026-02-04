<?php
/**
 * Job Titles Handler
 * Handles all operations for job titles management
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/session_check.php';

header('Content-Type: application/json');

// Check if user has admin access
if (!isset($is_system_admin) || !$is_system_admin) {
    http_response_code(403);
    die(json_encode(['success' => false, 'message' => 'Access denied. Admin privileges required.']));
}

$action = $_POST['action'] ?? '';

switch ($action) {
    case 'get_job_titles':
        getJobTitles();
        break;
    case 'get_job_title':
        getJobTitle();
        break;
    case 'add_job_title':
        addJobTitle();
        break;
    case 'update_job_title':
        updateJobTitle();
        break;
    case 'delete_job_title':
        deleteJobTitle();
        break;
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

function getJobTitles() {
    global $conDB;
    
    try {
        $sql = "SELECT id, job, job_ar FROM ac_jobs ORDER BY job ASC";
        $result = mysqli_query($conDB, $sql);
        
        if (!$result) {
            throw new Exception(mysqli_error($conDB));
        }
        
        $jobs = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $jobs[] = $row;
        }
        
        echo json_encode(['success' => true, 'jobs' => $jobs]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function getJobTitle() {
    global $conDB;
    
    $jobId = intval($_POST['job_id'] ?? 0);
    
    if (!$jobId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Job ID is required']);
        return;
    }
    
    try {
        $sql = "SELECT id, job, job_ar FROM ac_jobs WHERE id = ?";
        $stmt = $conDB->prepare($sql);
        
        if (!$stmt) {
            throw new Exception($conDB->error);
        }
        
        $stmt->bind_param('i', $jobId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            echo json_encode(['success' => false, 'message' => 'Job title not found']);
            return;
        }
        
        $job = $result->fetch_assoc();
        echo json_encode(['success' => true, 'job' => $job]);
        
        $stmt->close();
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function addJobTitle() {
    global $conDB;
    
    $titleEn = trim($_POST['job_title_en'] ?? '');
    $titleAr = trim($_POST['job_title_ar'] ?? '');
    
    if (!$titleEn || !$titleAr) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Both English and Arabic titles are required']);
        return;
    }
    
    try {
        $sql = "INSERT INTO ac_jobs (job, job_ar) VALUES (?, ?)";
        $stmt = $conDB->prepare($sql);
        
        if (!$stmt) {
            throw new Exception($conDB->error);
        }
        
        $stmt->bind_param('ss', $titleEn, $titleAr);
        
        if (!$stmt->execute()) {
            throw new Exception($stmt->error);
        }
        
        echo json_encode(['success' => true, 'message' => 'Job title added successfully', 'id' => $stmt->insert_id]);
        $stmt->close();
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function updateJobTitle() {
    global $conDB;
    
    $jobId = intval($_POST['job_id'] ?? 0);
    $titleEn = trim($_POST['job_title_en'] ?? '');
    $titleAr = trim($_POST['job_title_ar'] ?? '');
    
    if (!$jobId || !$titleEn || !$titleAr) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Job ID and both titles are required']);
        return;
    }
    
    try {
        $sql = "UPDATE ac_jobs SET job = ?, job_ar = ? WHERE id = ?";
        $stmt = $conDB->prepare($sql);
        
        if (!$stmt) {
            throw new Exception($conDB->error);
        }
        
        $stmt->bind_param('ssi', $titleEn, $titleAr, $jobId);
        
        if (!$stmt->execute()) {
            throw new Exception($stmt->error);
        }
        
        if ($stmt->affected_rows === 0) {
            echo json_encode(['success' => false, 'message' => 'Job title not found']);
            return;
        }
        
        echo json_encode(['success' => true, 'message' => 'Job title updated successfully']);
        $stmt->close();
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function deleteJobTitle() {
    global $conDB;
    
    $jobId = intval($_POST['job_id'] ?? 0);
    
    if (!$jobId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Job ID is required']);
        return;
    }
    
    try {
        $sql = "DELETE FROM ac_jobs WHERE id = ?";
        $stmt = $conDB->prepare($sql);
        
        if (!$stmt) {
            throw new Exception($conDB->error);
        }
        
        $stmt->bind_param('i', $jobId);
        
        if (!$stmt->execute()) {
            throw new Exception($stmt->error);
        }
        
        if ($stmt->affected_rows === 0) {
            echo json_encode(['success' => false, 'message' => 'Job title not found']);
            return;
        }
        
        echo json_encode(['success' => true, 'message' => 'Job title deleted successfully']);
        $stmt->close();
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
?>
