<?php
require_once __DIR__ . '/../../includes/db.php';

// Collect POST data with basic sanitation
$id_up        = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$username_up  = mysqli_real_escape_string($conDB, $_POST['username'] ?? '');
$fullname_up  = mysqli_real_escape_string($conDB, $_POST['fullname'] ?? '');
$dept_up      = mysqli_real_escape_string($conDB, $_POST['dept'] ?? '');
$user_type_up = mysqli_real_escape_string($conDB, $_POST['user_type'] ?? '');
$email_up     = mysqli_real_escape_string($conDB, $_POST['email'] ?? '');
$email_pass_up= mysqli_real_escape_string($conDB, $_POST['email_pass'] ?? '');
$mobile_up    = mysqli_real_escape_string($conDB, $_POST['mobile'] ?? '');
$status_up    = mysqli_real_escape_string($conDB, $_POST['status'] ?? '0');

// 1) Find linked employee id for this admin_login record
$emp_id_linked = null;
if ($id_up > 0) {
    $res = mysqli_query($conDB, "SELECT `emp_id` FROM `admin_login` WHERE `id` = " . $id_up . " LIMIT 1");
    if ($res && mysqli_num_rows($res) === 1) {
        $emp_id_linked = mysqli_fetch_assoc($res)['emp_id'] ?? null;
    }
}

// 2) Get employees.emptype for linked emp_id
$emp_type_from_emp = null;
if (!empty($emp_id_linked)) {
    $emp_id_safe = mysqli_real_escape_string($conDB, $emp_id_linked);
    $res2 = mysqli_query($conDB, "SELECT `emptype` FROM `employees` WHERE `emp_id`='" . $emp_id_safe . "' LIMIT 1");
    if ($res2 && mysqli_num_rows($res2) === 1) {
        $emp_type_from_emp = mysqli_fetch_assoc($res2)['emptype'] ?? null;
    }
}

// 3) Decide the admin_login.emp_type to set
//    - If user selects dept_user, force 'Manager'
//    - Else, use employees.emptype when available; fallback to existing value or 'Employee'
$emp_type_to_set = 'Employee';
if (strtolower($user_type_up) === 'dept_user') {
    $emp_type_to_set = 'Manager';
} elseif (!empty($emp_type_from_emp)) {
    $emp_type_to_set = $emp_type_from_emp;
}
$emp_type_safe = mysqli_real_escape_string($conDB, $emp_type_to_set);

// 4) Perform update
$sql = "UPDATE `admin_login` SET 
            `username`='".$username_up."', 
            `fullname`='".$fullname_up."', 
            `dept`='".$dept_up."', 
            `user_type`='".$user_type_up."', 
            `emp_type`='".$emp_type_safe."', 
            `email`='".$email_up."', 
            `email_pass`='".$email_pass_up."', 
            `mobile`='".$mobile_up."', 
            `status`='".$status_up."', 
            `updated_at`='".date('Y-m-d H:i:s')."' 
        WHERE `id`='".$id_up."' ";

if (mysqli_query($conDB, $sql)) {
    $data = [
        'title'   => 'Updated!',
        'message' => 'This user has been update successfully.',
        'type'    => 'success',
    ];
    echo json_encode($data);
} else {
    $data = [
        'title'   => 'Error!',
        'message' => 'User not updated because there are some error.',
        'type'    => 'error',
    ];
    echo json_encode($data);
}

?>