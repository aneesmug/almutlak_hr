<?php
/****************************************************************
 * MODIFICATION SUMMARY (RBAC - 014-role_permissions_ajax.php):
 *
 * 1. ADDED "GET PERMISSION DETAILS": A new case, `get_permission_details`, has been added to fetch the name and description for a single permission, used to populate the new "Edit Permission" modal.
 * 2. ADDED "EDIT PERMISSION": A new case, `edit_permission`, handles the server-side logic for updating a permission's details.
 * 3. ADDED "GET GROUP DETAILS": A new case, `get_group_details`, fetches all data for a single feature group, used to populate the new "Edit Group" modal.
 * 4. ADDED "EDIT FEATURE GROUP": A new case, `edit_feature_group`, handles the logic for updating a feature group's name, parent, and associated permissions.
 * 5. This file now contains the complete and final set of AJAX actions needed to fully manage the RBAC system from the user interface.
 ****************************************************************/
require_once __DIR__ . '/../db.php';
header('Content-Type: application/json');

$response = ['status' => 'error', 'message' => 'Invalid Request'];
$action = $_POST['action'] ?? '';

if (!$action) {
    echo json_encode($response);
    exit();
}

switch ($action) {
    case 'get_roles_and_permissions':
        try {
            $roles_query = mysqli_query($conDB, "SELECT id, name FROM `roles` ORDER BY name ASC");
            $roles = mysqli_fetch_all($roles_query, MYSQLI_ASSOC);

            $permissions_query = mysqli_query($conDB, "SELECT id, name, description FROM `permissions` ORDER BY name ASC");
            $permissions = mysqli_fetch_all($permissions_query, MYSQLI_ASSOC);

            $response = [
                'status' => 'success',
                'roles' => $roles,
                'permissions' => $permissions
            ];
        } catch (Exception $e) {
            $response['message'] = 'Database error: ' . $e->getMessage();
        }
        break;

    case 'get_permissions_for_role':
        if (isset($_POST['role_id'])) {
            $role_id = (int)$_POST['role_id'];
            $permissions_query = mysqli_query($conDB, "SELECT permission_id FROM `role_permissions` WHERE role_id = $role_id");
            $permission_ids = [];
            while($row = mysqli_fetch_assoc($permissions_query)){
                $permission_ids[] = $row['permission_id'];
            }
            $response = ['status' => 'success', 'permission_ids' => $permission_ids];
        } else {
            $response['message'] = 'Role ID not provided.';
        }
        break;
    
    case 'get_parent_groups':
        $parents_query = mysqli_query($conDB, "SELECT id, group_name FROM `permission_groups` WHERE parent_id IS NULL ORDER BY group_name ASC");
        $parent_groups = mysqli_fetch_all($parents_query, MYSQLI_ASSOC);
        $response = ['status' => 'success', 'parent_groups' => $parent_groups];
        break;

    case 'save_role_permissions':
        if (isset($_POST['role_id']) && isset($_POST['permissions']) && is_array($_POST['permissions'])) {
            $role_id = (int)$_POST['role_id'];
            $permissions = $_POST['permissions'];

            mysqli_begin_transaction($conDB);
            try {
                // Delete existing permissions for the role
                $delete_stmt = mysqli_prepare($conDB, "DELETE FROM `role_permissions` WHERE role_id = ?");
                mysqli_stmt_bind_param($delete_stmt, "i", $role_id);
                mysqli_stmt_execute($delete_stmt);

                // Insert new permissions
                if (!empty($permissions)) {
                    $insert_sql = "INSERT INTO `role_permissions` (role_id, permission_id) VALUES ";
                    $placeholders = [];
                    $values = [];
                    foreach ($permissions as $perm_id) {
                        $placeholders[] = "(?, ?)";
                        $values[] = $role_id;
                        $values[] = (int)$perm_id;
                    }
                    $insert_sql .= implode(", ", $placeholders);
                    $insert_stmt = mysqli_prepare($conDB, $insert_sql);
                    
                    // Dynamically bind parameters
                    $types = str_repeat('ii', count($permissions));
                    mysqli_stmt_bind_param($insert_stmt, $types, ...$values);
                    mysqli_stmt_execute($insert_stmt);
                }

                mysqli_commit($conDB);
                $response = ['status' => 'success', 'message' => 'Permissions updated successfully!'];

            } catch (Exception $e) {
                mysqli_rollback($conDB);
                $response['message'] = 'Database error: ' . $e->getMessage();
            }

        } else {
            $response['message'] = 'Required data not provided.';
        }
        break;
    
    // --- PERMISSION CRUD ---
    case 'add_permission':
        if (!empty($_POST['name'])) {
            $name = trim($_POST['name']);
            $description = trim($_POST['description'] ?? '');

            $stmt = mysqli_prepare($conDB, "INSERT INTO `permissions` (name, description) VALUES (?, ?)");
            mysqli_stmt_bind_param($stmt, "ss", $name, $description);
            if (mysqli_stmt_execute($stmt)) {
                $response = ['status' => 'success', 'message' => 'Permission created successfully.'];
            } else {
                $response['message'] = 'Database error: Could not create permission.';
            }
        } else {
             $response['message'] = 'Permission name cannot be empty.';
        }
        break;
        
    case 'get_permission_details':
        if(isset($_POST['permission_id'])) {
            $id = (int)$_POST['permission_id'];
            $stmt = mysqli_prepare($conDB, "SELECT * FROM `permissions` WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "i", $id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $data = mysqli_fetch_assoc($result);
            if ($data) {
                $response = ['status' => 'success', 'data' => $data];
            } else {
                $response['message'] = 'Permission not found.';
            }
        } else {
            $response['message'] = 'Permission ID not provided.';
        }
        break;

    case 'edit_permission':
        if (!empty($_POST['permission_id']) && !empty($_POST['name'])) {
            $id = (int)$_POST['permission_id'];
            $name = trim($_POST['name']);
            $description = trim($_POST['description'] ?? '');

            $stmt = mysqli_prepare($conDB, "UPDATE `permissions` SET name = ?, description = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "ssi", $name, $description, $id);
            if (mysqli_stmt_execute($stmt)) {
                $response = ['status' => 'success', 'message' => 'Permission updated successfully.'];
            } else {
                $response['message'] = 'Database error: Could not update permission.';
            }
        } else {
             $response['message'] = 'Permission ID or name cannot be empty.';
        }
        break;

    case 'delete_permission':
        if (!empty($_POST['permission_id'])) {
            $permission_id = (int)$_POST['permission_id'];
            $stmt = mysqli_prepare($conDB, "DELETE FROM `permissions` WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "i", $permission_id);
            if (mysqli_stmt_execute($stmt)) {
                $response = ['status' => 'success', 'message' => 'Permission deleted successfully.'];
            } else {
                $response['message'] = 'Database error: Could not delete permission.';
            }
        } else {
            $response['message'] = 'Permission ID not provided.';
        }
        break;
    
    // --- GROUP CRUD ---
    case 'add_feature_group':
        if (!empty($_POST['group_name'])) {
            $group_name = trim($_POST['group_name']);
            $parent_id = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;
            $view_perm = !empty($_POST['view_perm']) ? trim($_POST['view_perm']) : null;
            $add_perm = !empty($_POST['add_perm']) ? trim($_POST['add_perm']) : null;
            $update_perm = !empty($_POST['update_perm']) ? trim($_POST['update_perm']) : null;
            $delete_perm = !empty($_POST['delete_perm']) ? trim($_POST['delete_perm']) : null;
            
            $stmt = mysqli_prepare($conDB, "INSERT INTO `permission_groups` (group_name, parent_id, view_perm, add_perm, update_perm, delete_perm) VALUES (?, ?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt, "sissss", $group_name, $parent_id, $view_perm, $add_perm, $update_perm, $delete_perm);

            if (mysqli_stmt_execute($stmt)) {
                $response = ['status' => 'success', 'message' => 'Feature group created successfully.'];
            } else {
                 $response['message'] = 'Database error: ' . mysqli_error($conDB);
            }
        } else {
            $response['message'] = 'Group name cannot be empty.';
        }
        break;
        
    case 'get_group_details':
        if(isset($_POST['group_id'])) {
            $id = (int)$_POST['group_id'];
            $stmt = mysqli_prepare($conDB, "SELECT * FROM `permission_groups` WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "i", $id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $data = mysqli_fetch_assoc($result);
            if ($data) {
                $response = ['status' => 'success', 'data' => $data];
            } else {
                $response['message'] = 'Group not found.';
            }
        } else {
            $response['message'] = 'Group ID not provided.';
        }
        break;

    case 'edit_feature_group':
        if (!empty($_POST['group_id']) && !empty($_POST['group_name'])) {
            $group_id = (int)$_POST['group_id'];
            $group_name = trim($_POST['group_name']);
            $parent_id = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;
            $view_perm = !empty($_POST['view_perm']) ? trim($_POST['view_perm']) : null;
            $add_perm = !empty($_POST['add_perm']) ? trim($_POST['add_perm']) : null;
            $update_perm = !empty($_POST['update_perm']) ? trim($_POST['update_perm']) : null;
            $delete_perm = !empty($_POST['delete_perm']) ? trim($_POST['delete_perm']) : null;

            $stmt = mysqli_prepare($conDB, "UPDATE `permission_groups` SET group_name = ?, parent_id = ?, view_perm = ?, add_perm = ?, update_perm = ?, delete_perm = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "sissssi", $group_name, $parent_id, $view_perm, $add_perm, $update_perm, $delete_perm, $group_id);

            if (mysqli_stmt_execute($stmt)) {
                $response = ['status' => 'success', 'message' => 'Feature group updated successfully.'];
            } else {
                $response['message'] = 'Database error: ' . mysqli_error($conDB);
            }
        } else {
            $response['message'] = 'Group ID or Group Name cannot be empty.';
        }
        break;


    case 'delete_feature_group':
        if (!empty($_POST['group_id'])) {
            $group_id = (int)$_POST['group_id'];
            $stmt = mysqli_prepare($conDB, "DELETE FROM `permission_groups` WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "i", $group_id);
            if (mysqli_stmt_execute($stmt)) {
                $response = ['status' => 'success', 'message' => 'Feature group deleted successfully.'];
            } else {
                $response['message'] = 'Database error: Could not delete feature group.';
            }
        } else {
            $response['message'] = 'Group ID not provided.';
        }
        break;

    default:
        // Default response is already set
        break;
}

echo json_encode($response);
exit();

