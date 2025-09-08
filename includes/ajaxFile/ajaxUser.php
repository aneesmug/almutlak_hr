<?php
	require_once __DIR__ . '/../../includes/db.php';

$ajaxType = $_POST['ajaxType'];

if ($ajaxType == 'add_customer') {
    
} elseif($ajaxType == 'user_upate'){
    $username_up = $_POST['username'];
    $fullname_up = $_POST['fullname'];
    $dept_up = $_POST['dept'];
    $user_type_up = $_POST['user_type'];
    $email_up = $_POST['email'];
    $email_pass_up = $_POST['email_pass'];
    $mobile_up = $_POST['mobile'];
    $status_up = $_POST['status'];
    $sql = "UPDATE `admin_login` SET `username`='".$username_up."', `fullname`='".$fullname_up."', `user_type`='".$user_type_up."', `email`='".$email_up."', `email_pass`='".$email_pass_up."', `mobile`='".$mobile_up."', `status`='".$status_up."', `updated_at`='".date('Y-m-d H:i:s')."' WHERE `id`='".$_POST['id']."' ";
    if(mysqli_query($conDB, $sql)){
        $data = [
            'title'   => "Updated!",
            'message' => "This user has been update successfully.",
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
    $sql = "INSERT INTO `admin_login` (`emp_id`,`id_iqama`,`fullname`, `user_type`, `dept`, `email`, `created_at`) VALUES (?,?,?, ?,?,?,?)"; // 
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
}
?>