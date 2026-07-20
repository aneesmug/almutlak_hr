<?php
/**
 * Departments Handler
 * Handles all operations for department management
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/session_check.php';
require_once __DIR__ . '/special_access_helper.php';

header('Content-Type: application/json');

// Allow system admins, plus employees explicitly granted the Departments special-access key.
$canManageDepartments = (isset($is_system_admin) && $is_system_admin)
    || user_has_special_access($conDB, $empid ?? '', 'manage_department_settings', $user_role ?? '', $user_type ?? '', $is_system_admin ?? false);

if (!$canManageDepartments) {
    http_response_code(403);
    die(json_encode(['success' => false, 'message' => 'Access denied. Admin privileges required.']));
}

$action = $_POST['action'] ?? '';

switch ($action) {
    case 'get_departments':
        getDepartments();
        break;
    case 'get_department':
        getDepartment();
        break;
    case 'add_department':
        addDepartment();
        break;
    case 'update_department':
        updateDepartment();
        break;
    case 'delete_department':
        deleteDepartment();
        break;
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}

function normalizeDepartmentColor(string $color): ?string
{
    $color = strtolower(trim($color));
    if ($color === '') {
        return null;
    }

    $allowedColors = ['custom', 'purple', 'primary', 'success'];
    if (!in_array($color, $allowedColors, true)) {
        return null;
    }

    return $color;
}

function getDepartments(): void
{
    global $conDB;

    try {
        $sql = "SELECT id, dep_nme, dep_nme_ar, dept_clr FROM department ORDER BY dep_nme ASC";
        $result = mysqli_query($conDB, $sql);

        if (!$result) {
            throw new Exception(mysqli_error($conDB));
        }

        $departments = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $departments[] = $row;
        }

        echo json_encode(['success' => true, 'departments' => $departments]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function getDepartment(): void
{
    global $conDB;

    $departmentId = (int)($_POST['department_id'] ?? 0);

    if ($departmentId <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Department ID is required']);
        return;
    }

    try {
        $sql = "SELECT id, dep_nme, dep_nme_ar, dept_clr FROM department WHERE id = ?";
        $stmt = $conDB->prepare($sql);

        if (!$stmt) {
            throw new Exception($conDB->error);
        }

        $stmt->bind_param('i', $departmentId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            echo json_encode(['success' => false, 'message' => 'Department not found']);
            $stmt->close();
            return;
        }

        $department = $result->fetch_assoc();
        echo json_encode(['success' => true, 'department' => $department]);

        $stmt->close();
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function addDepartment(): void
{
    global $conDB;

    $departmentEn = trim((string)($_POST['department_en'] ?? ''));
    $departmentAr = trim((string)($_POST['department_ar'] ?? ''));
    $departmentColor = normalizeDepartmentColor((string)($_POST['department_color'] ?? ''));

    if ($departmentEn === '' || $departmentAr === '') {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Both English and Arabic department names are required']);
        return;
    }

    if ($departmentColor === null) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'A valid department color class is required']);
        return;
    }

    try {
        $duplicateSql = "SELECT id FROM department WHERE dep_nme = ? OR dep_nme_ar = ? LIMIT 1";
        $duplicateStmt = $conDB->prepare($duplicateSql);
        if (!$duplicateStmt) {
            throw new Exception($conDB->error);
        }
        $duplicateStmt->bind_param('ss', $departmentEn, $departmentAr);
        $duplicateStmt->execute();
        $duplicateResult = $duplicateStmt->get_result();
        if ($duplicateResult && $duplicateResult->num_rows > 0) {
            $duplicateStmt->close();
            echo json_encode(['success' => false, 'message' => 'Department already exists']);
            return;
        }
        $duplicateStmt->close();

        $sql = "INSERT INTO department (dep_nme, dep_nme_ar, dept_clr) VALUES (?, ?, ?)";
        $stmt = $conDB->prepare($sql);

        if (!$stmt) {
            throw new Exception($conDB->error);
        }

        $stmt->bind_param('sss', $departmentEn, $departmentAr, $departmentColor);

        if (!$stmt->execute()) {
            throw new Exception($stmt->error);
        }

        echo json_encode(['success' => true, 'message' => 'Department added successfully', 'id' => $stmt->insert_id]);
        $stmt->close();
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function updateDepartment(): void
{
    global $conDB;

    $departmentId = (int)($_POST['department_id'] ?? 0);
    $departmentEn = trim((string)($_POST['department_en'] ?? ''));
    $departmentAr = trim((string)($_POST['department_ar'] ?? ''));
    $departmentColor = normalizeDepartmentColor((string)($_POST['department_color'] ?? ''));

    if ($departmentId <= 0 || $departmentEn === '' || $departmentAr === '') {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Department ID and both names are required']);
        return;
    }

    if ($departmentColor === null) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'A valid department color class is required']);
        return;
    }

    try {
        $duplicateSql = "SELECT id FROM department WHERE (dep_nme = ? OR dep_nme_ar = ?) AND id <> ? LIMIT 1";
        $duplicateStmt = $conDB->prepare($duplicateSql);
        if (!$duplicateStmt) {
            throw new Exception($conDB->error);
        }
        $duplicateStmt->bind_param('ssi', $departmentEn, $departmentAr, $departmentId);
        $duplicateStmt->execute();
        $duplicateResult = $duplicateStmt->get_result();
        if ($duplicateResult && $duplicateResult->num_rows > 0) {
            $duplicateStmt->close();
            echo json_encode(['success' => false, 'message' => 'Department already exists']);
            return;
        }
        $duplicateStmt->close();

        $sql = "UPDATE department SET dep_nme = ?, dep_nme_ar = ?, dept_clr = ? WHERE id = ?";
        $stmt = $conDB->prepare($sql);

        if (!$stmt) {
            throw new Exception($conDB->error);
        }

        $stmt->bind_param('sssi', $departmentEn, $departmentAr, $departmentColor, $departmentId);

        if (!$stmt->execute()) {
            throw new Exception($stmt->error);
        }

        if ($stmt->affected_rows === 0) {
            echo json_encode(['success' => false, 'message' => 'Department not found or unchanged']);
            $stmt->close();
            return;
        }

        echo json_encode(['success' => true, 'message' => 'Department updated successfully']);
        $stmt->close();
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function deleteDepartment(): void
{
    global $conDB;

    $departmentId = (int)($_POST['department_id'] ?? 0);

    if ($departmentId <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Department ID is required']);
        return;
    }

    try {
        $sql = "DELETE FROM department WHERE id = ?";
        $stmt = $conDB->prepare($sql);

        if (!$stmt) {
            throw new Exception($conDB->error);
        }

        $stmt->bind_param('i', $departmentId);

        if (!$stmt->execute()) {
            // Foreign key constraint error: department is in use.
            if ((int)$stmt->errno === 1451) {
                echo json_encode(['success' => false, 'message' => 'Cannot delete this department because it is linked to existing records.']);
                $stmt->close();
                return;
            }
            throw new Exception($stmt->error);
        }

        if ($stmt->affected_rows === 0) {
            echo json_encode(['success' => false, 'message' => 'Department not found']);
            $stmt->close();
            return;
        }

        echo json_encode(['success' => true, 'message' => 'Department deleted successfully']);
        $stmt->close();
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
?>