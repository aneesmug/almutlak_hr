<?php
    require_once __DIR__ . '/../../includes/db.php';
    
    $emp_id_up = $_POST['emp_id'];
    $link_up = $_POST['link'];
    $social_id_up = $_POST['social_id'];

    $socquery = mysqli_query($conDB, "SELECT * FROM `social` WHERE `emp_id`='".$emp_id_up."' AND `social_id`='".$social_id_up."' ");
    if(mysqli_num_rows($socquery) == 0){
        $query="INSERT INTO `social` (`emp_id`,`s_link`, `social_id`, `created_at`) VALUES ('".$emp_id_up."', '".$link_up."', '".$social_id_up."', '".date('Y-m-d H:i:s')."')";
        if(mysqli_query($conDB, $query)){
            $data = [
                'title'   => "Added!",
                'message' => "This social link has been added successfully.",
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
    } else {
        $data = [
            'title'   => "Error!",
            'message' => "This Social Media already exist.",
            'type'    => 'error',
        ];
        echo json_encode($data);
    }
?>