<?php
	require_once __DIR__ . '/../../includes/db.php';
	require_once __DIR__ . '/../../includes/session_check.php';

$ajaxType = $_POST['ajaxType'];

if ($ajaxType == 'add_customer') {
    
} elseif($ajaxType == 'user_upate'){
    $user_type_up = mysqli_real_escape_string($conDB, $_POST['user_type']);
    $email_up = mysqli_real_escape_string($conDB, $_POST['email']);
    $user_id = intval($_POST['id']);
    
    // Fetch old user data for logging
    $old_user_result = mysqli_query($conDB, "SELECT * FROM `admin_login` WHERE `id`='".$user_id."'");
    $old_user = mysqli_fetch_assoc($old_user_result);
    
    // Fetch employee name and emptype from employees table
    $emp_query = "SELECT e.name as emp_name, e.emptype as emp_type FROM admin_login al 
                  LEFT JOIN employees e ON e.emp_id = al.emp_id 
                  WHERE al.id = $user_id";
    $emp_result = mysqli_query($conDB, $emp_query);
    $emp_data = mysqli_fetch_assoc($emp_result);
    $fullname_up = $emp_data['emp_name'] ?? '';
    $emp_type_up = $emp_data['emp_type'] ?? '';
    
    // Update admin_login with user_type and emp_type
    $sql = "UPDATE `admin_login` SET 
            `fullname` = '".$fullname_up."', 
            `user_type` = '".$user_type_up."', 
            `emp_type` = '".$emp_type_up."', 
            `email` = '".$email_up."', 
            `updated_at` = '".date('Y-m-d H:i:s')."' 
            WHERE `id` = '".$user_id."'";
    
    if(mysqli_query($conDB, $sql)){
        // Log user update
        ActivityLogger::logUpdate('User', 'ajaxUser.php', $user_id, $old_user, [
            'user_type' => $user_type_up,
            'email' => $email_up,
            'emp_type' => $emp_type_up
        ], "Updated user: {$fullname_up}, Role: {$user_type_up}", 'admin_login');
        
        $data = [
            'title'   => "Updated!",
            'message' => "User has been updated successfully with role: " . $user_type_up,
            'type'    => 'success',
        ];
        echo json_encode($data);
    } else {
        $data = [
            'title'   => "Error!",
            'message' => "User not updated: " . mysqli_error($conDB),
            'type'    => 'error',
        ];
        echo json_encode($data);
    }  
} elseif($ajaxType == 'password_update') {
    if(isset($_POST['ajax']) && isset($_POST['password'])){
        $user_id = intval($_POST['id']);
        
        // Fetch old user data for logging
        $old_user_result = mysqli_query($conDB, "SELECT id, email FROM `admin_login` WHERE `id`='".$user_id."'");
        $old_user = mysqli_fetch_assoc($old_user_result);
        
        $sqlpass = "UPDATE `admin_login` SET `password`='".sha1(md5($_POST['password']))."', `bk_password`='".$_POST['password']."', `updated_at`='".date('Y-m-d H:i:s')."' WHERE `id`='".$user_id."' ";
        if(mysqli_query($conDB, $sqlpass)){
            // Log password update
            ActivityLogger::logUpdate('User', 'ajaxUser.php', $user_id, $old_user, [
                'password' => '[REDACTED]'
            ], "Updated password for user", 'admin_login');
            
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
    $user_type = $_POST['user_type'];
    $email = isset($_POST['email']) ? $_POST['email'] : '';
    
    // Fetch employee details from employees table
    $sqlusr = "SELECT * FROM `employees` WHERE `emp_id`=?";
    $stmt = $conDB->prepare($sqlusr);
    $stmt->bind_param('i', $emp_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    if (!$row) {
        $data = [
            'title'   => "Error!",
            'message' => "Employee not found.",
            'type'    => 'error',
        ];
        echo json_encode($data);
        exit;
    }
    
    // Get emp_type from employees table
    $emp_type = isset($row['emptype']) ? $row['emptype'] : '';
    
    // Check if user already exists
    $check_sql = "SELECT id FROM `admin_login` WHERE `emp_id` = ?";
    $check_stmt = $conDB->prepare($check_sql);
    $check_stmt->bind_param('i', $emp_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        $data = [
            'title'   => "Error!",
            'message' => "User account already exists for this employee.",
            'type'    => 'error',
        ];
        echo json_encode($data);
        $check_stmt->close();
        exit;
    }
    $check_stmt->close();
    
    // Insert into admin_login with user_type and emp_type
    $sql = "INSERT INTO `admin_login` (`emp_id`, `id_iqama`, `fullname`, `user_type`, `emp_type`, `dept`, `email`, `created_at`) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt2 = $conDB->prepare($sql);
    $created_at = date('Y-m-d H:i:s');
    $stmt2->bind_param('iissssss', 
        $row['emp_id'],
        $row['iqama'],
        $row['name'],
        $user_type,
        $emp_type,
        $row['dept'],
        $email,
        $created_at
    );
    
    if($stmt2->execute()){
        // Get the new user ID
        $new_user_id = $stmt2->insert_id;
        
        // Log user creation
        ActivityLogger::logCreate('User', 'ajaxUser.php', $new_user_id, [
            'emp_id' => $row['emp_id'],
            'fullname' => $row['name'],
            'user_type' => $user_type,
            'emp_type' => $emp_type,
            'email' => $email,
            'dept' => $row['dept']
        ], "Created user account for: {$row['name']}, Role: {$user_type}", 'admin_login');
        
        $data = [
            'title'   => "Created!",
            'message' => "New user has been created successfully with role: " . $user_type,
            'type'    => 'success',
        ];
        echo json_encode($data);
    } else {
        $data = [
            'title'   => "Error!",
            'message' => "User not created: " . $stmt2->error,
            'type'    => 'error',
        ];
        echo json_encode($data);
    }
    $stmt2->close();
    $stmt->close();
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