<?php
/**
 * Companies Handler
 * Handles all operations for company management
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/session_check.php';
require_once __DIR__ . '/special_access_helper.php';

header('Content-Type: application/json');

// Allow system admins, plus employees explicitly granted the Companies special-access key.
$canManageCompanies = (isset($is_system_admin) && $is_system_admin)
    || user_has_special_access($conDB, $empid ?? '', 'manage_company_settings', $user_role ?? '', $user_type ?? '', $is_system_admin ?? false);

if (!$canManageCompanies) {
    http_response_code(403);
    die(json_encode(['success' => false, 'message' => 'Access denied. Admin privileges required.']));
}

$action = $_POST['action'] ?? '';

switch ($action) {
    case 'get_companies':
        getCompanies();
        break;
    case 'get_company':
        getCompany();
        break;
    case 'add_company':
        addCompany();
        break;
    case 'update_company':
        updateCompany();
        break;
    case 'delete_company':
        deleteCompany();
        break;
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}

function getTimetablesList(): array
{
    global $conDB;

    $timetables = [];
    $result = mysqli_query($conDB, "SELECT id, name FROM timetables ORDER BY name ASC");
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $timetables[] = $row;
        }
    }

    return $timetables;
}

function getCompanies(): void
{
    global $conDB;

    try {
        $sql = "SELECT c.id, c.comp_id, c.comp_name, c.comp_name_ar, c.timetable_id, t.name AS timetable_name
                FROM companies c
                LEFT JOIN timetables t ON t.id = c.timetable_id
                ORDER BY c.comp_name ASC";
        $result = mysqli_query($conDB, $sql);

        if (!$result) {
            throw new Exception(mysqli_error($conDB));
        }

        $companies = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $companies[] = $row;
        }

        echo json_encode(['success' => true, 'companies' => $companies, 'timetables' => getTimetablesList()]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function getCompany(): void
{
    global $conDB;

    $companyId = (int)($_POST['company_id'] ?? 0);

    if ($companyId <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Company ID is required']);
        return;
    }

    try {
        $sql = "SELECT id, comp_id, comp_name, comp_name_ar, timetable_id FROM companies WHERE id = ?";
        $stmt = $conDB->prepare($sql);

        if (!$stmt) {
            throw new Exception($conDB->error);
        }

        $stmt->bind_param('i', $companyId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            echo json_encode(['success' => false, 'message' => 'Company not found']);
            $stmt->close();
            return;
        }

        $company = $result->fetch_assoc();
        echo json_encode(['success' => true, 'company' => $company]);

        $stmt->close();
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function addCompany(): void
{
    global $conDB;

    $companyEn = trim((string)($_POST['company_en'] ?? ''));
    $companyAr = trim((string)($_POST['company_ar'] ?? ''));
    $compCode = (int)($_POST['comp_id'] ?? 0);
    $timetableIdRaw = trim((string)($_POST['timetable_id'] ?? ''));
    $timetableId = $timetableIdRaw === '' ? null : (int)$timetableIdRaw;

    if ($companyEn === '' || $companyAr === '') {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Both English and Arabic company names are required']);
        return;
    }

    if ($compCode <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'A valid Company Code is required']);
        return;
    }

    try {
        // Name uniqueness (same pattern as Departments) and comp_id uniqueness - the
        // 'comp_id' column has no DB-level UNIQUE constraint, and employees.comp_no
        // matches companies by this code, so a duplicate would silently mix two
        // companies' employees together.
        $duplicateSql = "SELECT id FROM companies WHERE comp_name = ? OR comp_name_ar = ? OR comp_id = ? LIMIT 1";
        $duplicateStmt = $conDB->prepare($duplicateSql);
        if (!$duplicateStmt) {
            throw new Exception($conDB->error);
        }
        $duplicateStmt->bind_param('ssi', $companyEn, $companyAr, $compCode);
        $duplicateStmt->execute();
        $duplicateResult = $duplicateStmt->get_result();
        if ($duplicateResult && $duplicateResult->num_rows > 0) {
            $duplicateStmt->close();
            echo json_encode(['success' => false, 'message' => 'A company with this name or Company Code already exists']);
            return;
        }
        $duplicateStmt->close();

        $sql = "INSERT INTO companies (comp_id, comp_name, comp_name_ar, timetable_id) VALUES (?, ?, ?, ?)";
        $stmt = $conDB->prepare($sql);

        if (!$stmt) {
            throw new Exception($conDB->error);
        }

        $stmt->bind_param('issi', $compCode, $companyEn, $companyAr, $timetableId);

        if (!$stmt->execute()) {
            throw new Exception($stmt->error);
        }

        echo json_encode(['success' => true, 'message' => 'Company added successfully', 'id' => $stmt->insert_id]);
        $stmt->close();
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function updateCompany(): void
{
    global $conDB;

    $companyId = (int)($_POST['company_id'] ?? 0);
    $companyEn = trim((string)($_POST['company_en'] ?? ''));
    $companyAr = trim((string)($_POST['company_ar'] ?? ''));
    $compCode = (int)($_POST['comp_id'] ?? 0);
    $timetableIdRaw = trim((string)($_POST['timetable_id'] ?? ''));
    $timetableId = $timetableIdRaw === '' ? null : (int)$timetableIdRaw;

    if ($companyId <= 0 || $companyEn === '' || $companyAr === '') {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Company ID and both names are required']);
        return;
    }

    if ($compCode <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'A valid Company Code is required']);
        return;
    }

    try {
        $duplicateSql = "SELECT id FROM companies WHERE (comp_name = ? OR comp_name_ar = ? OR comp_id = ?) AND id <> ? LIMIT 1";
        $duplicateStmt = $conDB->prepare($duplicateSql);
        if (!$duplicateStmt) {
            throw new Exception($conDB->error);
        }
        $duplicateStmt->bind_param('ssii', $companyEn, $companyAr, $compCode, $companyId);
        $duplicateStmt->execute();
        $duplicateResult = $duplicateStmt->get_result();
        if ($duplicateResult && $duplicateResult->num_rows > 0) {
            $duplicateStmt->close();
            echo json_encode(['success' => false, 'message' => 'A company with this name or Company Code already exists']);
            return;
        }
        $duplicateStmt->close();

        $sql = "UPDATE companies SET comp_id = ?, comp_name = ?, comp_name_ar = ?, timetable_id = ? WHERE id = ?";
        $stmt = $conDB->prepare($sql);

        if (!$stmt) {
            throw new Exception($conDB->error);
        }

        $stmt->bind_param('issii', $compCode, $companyEn, $companyAr, $timetableId, $companyId);

        if (!$stmt->execute()) {
            throw new Exception($stmt->error);
        }

        echo json_encode(['success' => true, 'message' => 'Company updated successfully']);
        $stmt->close();
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function deleteCompany(): void
{
    global $conDB;

    $companyId = (int)($_POST['company_id'] ?? 0);

    if ($companyId <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Company ID is required']);
        return;
    }

    try {
        // employees.comp_no references companies.comp_id with no DB-level foreign key
        // (unlike holiday_companies, which does have a real FK and would surface as a
        // 1451 error below) - so this has to be checked in application code, or deleting
        // a company silently orphans every employee assigned to it.
        $lookupStmt = $conDB->prepare("SELECT comp_id, comp_name FROM companies WHERE id = ?");
        if (!$lookupStmt) {
            throw new Exception($conDB->error);
        }
        $lookupStmt->bind_param('i', $companyId);
        $lookupStmt->execute();
        $lookupResult = $lookupStmt->get_result();
        $companyRow = $lookupResult ? $lookupResult->fetch_assoc() : null;
        $lookupStmt->close();

        if (!$companyRow) {
            echo json_encode(['success' => false, 'message' => 'Company not found']);
            return;
        }

        $compCode = (int) $companyRow['comp_id'];
        $countStmt = $conDB->prepare("SELECT COUNT(*) AS cnt FROM employees WHERE comp_no = ?");
        if (!$countStmt) {
            throw new Exception($conDB->error);
        }
        $countStmt->bind_param('i', $compCode);
        $countStmt->execute();
        $countRow = $countStmt->get_result()->fetch_assoc();
        $countStmt->close();

        if ((int) $countRow['cnt'] > 0) {
            echo json_encode([
                'success' => false,
                'message' => 'Cannot delete "' . $companyRow['comp_name'] . '" because ' . (int) $countRow['cnt'] . ' employee(s) are still assigned to it.',
            ]);
            return;
        }

        $sql = "DELETE FROM companies WHERE id = ?";
        $stmt = $conDB->prepare($sql);

        if (!$stmt) {
            throw new Exception($conDB->error);
        }

        $stmt->bind_param('i', $companyId);

        if (!$stmt->execute()) {
            // Foreign key constraint error (e.g. holiday_companies): company is in use.
            if ((int)$stmt->errno === 1451) {
                echo json_encode(['success' => false, 'message' => 'Cannot delete this company because it is linked to existing records.']);
                $stmt->close();
                return;
            }
            throw new Exception($stmt->error);
        }

        if ($stmt->affected_rows === 0) {
            echo json_encode(['success' => false, 'message' => 'Company not found']);
            $stmt->close();
            return;
        }

        echo json_encode(['success' => true, 'message' => 'Company deleted successfully']);
        $stmt->close();
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
?>
