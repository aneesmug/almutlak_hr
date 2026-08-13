<?php
/**
 * Sub-Departments Handler
 * Manages the `sub_departments` lookup table (each sub-department belongs to one department).
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/session_check.php';
require_once __DIR__ . '/special_access_helper.php';

header('Content-Type: application/json');

$canManageSubDepartments = (isset($is_system_admin) && $is_system_admin)
    || user_has_special_access($conDB, $empid ?? '', 'manage_sub_department_settings', $user_role ?? '', $user_type ?? '', $is_system_admin ?? false);

if (!$canManageSubDepartments) {
    http_response_code(403);
    die(json_encode(['success' => false, 'message' => 'Access denied. Admin privileges required.']));
}

$action = $_POST['action'] ?? '';

switch ($action) {
    case 'get_departments':
        getDepartments();
        break;
    case 'get_sub_departments':
        getSubDepartments();
        break;
    case 'get_sub_department':
        getSubDepartment();
        break;
    case 'add_sub_department':
        addSubDepartment();
        break;
    case 'update_sub_department':
        updateSubDepartment();
        break;
    case 'delete_sub_department':
        deleteSubDepartment();
        break;
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

function getDepartments() {
    global $conDB;
    try {
        $result = mysqli_query($conDB, "SELECT id, dep_nme, dep_nme_ar FROM department ORDER BY dep_nme ASC");
        if (!$result) throw new Exception(mysqli_error($conDB));
        $departments = [];
        while ($row = mysqli_fetch_assoc($result)) $departments[] = $row;
        echo json_encode(['success' => true, 'departments' => $departments]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function getSubDepartments() {
    global $conDB;
    try {
        $sql = "SELECT s.id, s.department_id, s.name_en, s.name_ar, d.dep_nme, d.dep_nme_ar
                FROM sub_departments s
                LEFT JOIN department d ON d.id = s.department_id
                ORDER BY d.dep_nme ASC, s.name_en ASC";
        $result = mysqli_query($conDB, $sql);
        if (!$result) throw new Exception(mysqli_error($conDB));
        $subDepartments = [];
        while ($row = mysqli_fetch_assoc($result)) $subDepartments[] = $row;
        echo json_encode(['success' => true, 'sub_departments' => $subDepartments]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function getSubDepartment() {
    global $conDB;
    $subDeptId = intval($_POST['sub_dept_id'] ?? 0);

    if (!$subDeptId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Sub-department ID is required']);
        return;
    }

    try {
        $stmt = $conDB->prepare("SELECT id, department_id, name_en, name_ar FROM sub_departments WHERE id = ?");
        if (!$stmt) throw new Exception($conDB->error);
        $stmt->bind_param('i', $subDeptId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            echo json_encode(['success' => false, 'message' => 'Sub-department not found']);
            return;
        }

        echo json_encode(['success' => true, 'sub_department' => $result->fetch_assoc()]);
        $stmt->close();
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function addSubDepartment() {
    global $conDB;
    $departmentId = intval($_POST['department_id'] ?? 0);
    $nameEn = trim($_POST['name_en'] ?? '');
    $nameAr = trim($_POST['name_ar'] ?? '');

    if (!$departmentId || !$nameEn || !$nameAr) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Department, English name and Arabic name are required']);
        return;
    }

    try {
        $stmt = $conDB->prepare("INSERT INTO sub_departments (department_id, name_en, name_ar) VALUES (?, ?, ?)");
        if (!$stmt) throw new Exception($conDB->error);
        $stmt->bind_param('iss', $departmentId, $nameEn, $nameAr);
        if (!$stmt->execute()) throw new Exception($stmt->error);

        echo json_encode(['success' => true, 'message' => 'Sub-department added successfully', 'id' => $stmt->insert_id]);
        $stmt->close();
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function updateSubDepartment() {
    global $conDB;
    $subDeptId = intval($_POST['sub_dept_id'] ?? 0);
    $departmentId = intval($_POST['department_id'] ?? 0);
    $nameEn = trim($_POST['name_en'] ?? '');
    $nameAr = trim($_POST['name_ar'] ?? '');

    if (!$subDeptId || !$departmentId || !$nameEn || !$nameAr) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Sub-department ID, department, and both names are required']);
        return;
    }

    try {
        $stmt = $conDB->prepare("UPDATE sub_departments SET department_id = ?, name_en = ?, name_ar = ? WHERE id = ?");
        if (!$stmt) throw new Exception($conDB->error);
        $stmt->bind_param('issi', $departmentId, $nameEn, $nameAr, $subDeptId);
        if (!$stmt->execute()) throw new Exception($stmt->error);

        if ($stmt->affected_rows === 0) {
            echo json_encode(['success' => false, 'message' => 'Sub-department not found']);
            return;
        }

        echo json_encode(['success' => true, 'message' => 'Sub-department updated successfully']);
        $stmt->close();
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function deleteSubDepartment() {
    global $conDB;
    $subDeptId = intval($_POST['sub_dept_id'] ?? 0);

    if (!$subDeptId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Sub-department ID is required']);
        return;
    }

    try {
        $stmt = $conDB->prepare("DELETE FROM sub_departments WHERE id = ?");
        if (!$stmt) throw new Exception($conDB->error);
        $stmt->bind_param('i', $subDeptId);
        if (!$stmt->execute()) throw new Exception($stmt->error);

        if ($stmt->affected_rows === 0) {
            echo json_encode(['success' => false, 'message' => 'Sub-department not found']);
            return;
        }

        echo json_encode(['success' => true, 'message' => 'Sub-department deleted successfully']);
        $stmt->close();
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
