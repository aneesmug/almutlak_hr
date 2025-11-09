<?php
	require_once __DIR__ . '/../../includes/db.php';

$ajaxType = $_POST['ajaxType'];

if ($ajaxType == 'add_customer') {
    
} elseif($ajaxType == 'user_upate'){
    $user_type_up = mysqli_real_escape_string($conDB, $_POST['user_type']);
    $email_up = mysqli_real_escape_string($conDB, $_POST['email']);
    $user_id = intval($_POST['id']);
    
    // Fetch employee name from employees table
    $emp_name_query = "SELECT e.name as emp_name FROM admin_login al 
                       LEFT JOIN employees e ON e.emp_id = al.emp_id 
                       WHERE al.id = $user_id";
    $emp_result = mysqli_query($conDB, $emp_name_query);
    $emp_data = mysqli_fetch_assoc($emp_result);
    $fullname_up = $emp_data['emp_name'] ?? '';
    
    $sql = "UPDATE `admin_login` SET `fullname`='".$fullname_up."', `user_type`='".$user_type_up."', `email`='".$email_up."', `updated_at`='".date('Y-m-d H:i:s')."' WHERE `id`='".$user_id."' ";
    if(mysqli_query($conDB, $sql)){
        $data = [
            'title'   => "Updated!",
            'message' => "This user has been updated successfully.",
            'type'    => 'success',
        ];
        echo json_encode($data);
    } else {
        $data = [
            'title'   => "Error!",
            'message' => "User not updated because there are some error.",
            'type'    => 'error',
        ];
        echo json_encode($data);
    }  
} elseif($ajaxType == 'password_update') {
    if(isset($_POST['ajax']) && isset($_POST['password'])){
        $sqlpass = "UPDATE `admin_login` SET `password`='".sha1(md5($_POST['password']))."', `bk_password`='".$_POST['password']."', `updated_at`='".date('Y-m-d H:i:s')."' WHERE `id`='".$_POST['id']."' ";
        if(mysqli_query($conDB, $sqlpass)){
          $data = [
                'title'     => "Updated!",
                'message'   => "This user has been update successfully.",
                'type'      => 'success',
            ];
            echo json_encode($data);
        } else {
            $data = [
                'title'     => "Error!",
                'message'   => "Password not updated because there are some error.",
                'type'      => 'error',
            ];
            echo json_encode($data);
        }
    }
} elseif($ajaxType == 'create_user') {
    $emp_id = $_POST['emp_id'];
    
    $sqlusr = "SELECT * FROM `employees` WHERE `emp_id`=?";
    $stmt = $conDB->prepare($sqlusr);
    $stmt->bind_param('i', $emp_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    // Insert into admin_login
    $sql = "INSERT INTO `admin_login` (`emp_id`,`id_iqama`,`fullname`, `user_type`, `dept`, `email`, `created_at`) VALUES (?,?,?, ?,?,?,?)";
    $stmt2 = $conDB->prepare($sql);
    $stmt2->bind_param('iississ', $row['emp_id'],$row['iqama'],$row['name'],$_POST['user_type'],$row['dept'],$_POST['email'], date('Y-m-d H:i:s') );
    
    if($stmt2->execute()){
        $data = [
            'title'   => "Created!",
            'message' => "New user has been created successfully.",
            'type'    => 'success',
        ];
        echo json_encode($data);
    } else {
        $data = [
            'title'   => "Error!",
            'message' => "User not created because there was an error.",
            'type'    => 'error',
        ];
        echo json_encode($data);
    }
    $stmt2->close();
} elseif($ajaxType == 'load_supervisors') {
    $emp_id = isset($_POST['emp_id']) ? intval($_POST['emp_id']) : 0;
    
    // Get all active employees who are Managers or Supervisors, excluding the current employee
    $sql = "SELECT e.emp_id, e.name, e.emptype, d.name as dept_name 
            FROM employees e 
            LEFT JOIN department d ON e.dept = d.id 
            WHERE e.status = 1 
            AND e.emptype IN ('Manager', 'Supervisor') 
            AND e.emp_id != ? 
            ORDER BY d.name ASC, e.emptype = 'Manager' DESC, e.name ASC";
    
    $stmt = $conDB->prepare($sql);
    $stmt->bind_param('i', $emp_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $supervisors = [];
    while($row = $result->fetch_assoc()) {
        $supervisors[] = [
            'emp_id' => $row['emp_id'],
            'name' => $row['name'],
            'emptype' => $row['emptype'],
            'dept_name' => $row['dept_name'] ?? 'N/A'
        ];
    }
    $stmt->close();
    
    echo json_encode([
        'status' => 200,
        'data' => $supervisors
    ]);
}
?>