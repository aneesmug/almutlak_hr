<?php
	require_once __DIR__ . '/../../includes/db.php';
    $ajaxType = $_POST['ajaxType'];
    if ($_POST['status'] == 'approve') {
        $query = "UPDATE `emp_inv_attachment` SET `status`='$_POST[status]',`note`=NULL, `updated_at`='".date('Y-m-d H:i:s')."' WHERE `srno`='$_POST[srno]' AND `deleted` = '0' ";
        if(mysqli_query($conDB, $query)){
            $data = [
                'title'   => "Added!",
                'message' => "This record has been updated successfully.",
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
    } elseif($_POST['status'] == 'reject') {
        $query = "UPDATE `emp_inv_attachment` SET `status`='$_POST[status]', `note`='$_POST[note]', `updated_at`='".date('Y-m-d H:i:s')."' WHERE `srno`='$_POST[srno]' AND `deleted` = '0' ";
        if(mysqli_query($conDB, $query)){
            $data = [
                'title'   => "Added!",
                'message' => "This record has been updated successfully.",
                'type'    => 'success',
                'status'  => 'reject',
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
    } elseif($ajaxType == 'last_inv_search') {
        $stmt = mysqli_query($conDB, "SELECT * FROM `emp_inv_attachment` GROUP BY `srno` ORDER BY `id` DESC LIMIT 1");
        while($row = mysqli_fetch_assoc($stmt)) {
            // $srno[] = $row;
            $srno = $row['srno'];
        }
        echo json_encode($srno);
    } elseif($ajaxType == 'total_amount') {
    // } elseif($_POST['amount']) {
        $amount = str_replace(",","", $_POST['amount']);
        $query = "UPDATE `emp_inv_attachment` SET `total_amount`='$amount', `apprv_amount`='$amount', `updated_at`='".date('Y-m-d H:i:s')."' WHERE `srno`='$_POST[srno]' AND `deleted` = '0'  ";
        if(mysqli_query($conDB, $query)){
            $data = [
                'title'   => "Added!",
                'message' => "This record has been updated successfully.",
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
    } elseif($ajaxType == 'aprrov_amount') {
        $amount     = str_replace(",","", $_POST['amount']);
        $oldamount  = str_replace(",","", $_POST['oldamount']);
        $query = "UPDATE `emp_inv_attachment` SET `apprv_amount`='$amount', `updated_at`='".date('Y-m-d H:i:s')."' WHERE `srno`='$_POST[srno]' AND `deleted` = '0' ";
        if(mysqli_query($conDB, $query)){
            $data = [
                'title'   => "Added!",
                'message' => "This record has been updated successfully.",
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

    } elseif ($ajaxType == "upload_invo_att_user") {
    if(!empty($_FILES)){
        $empid = $_POST['empid'];
        $count = $_POST['count'];
        $getinv_no = $_POST['getinv_no'];
        $uploadDir = "./../../assets/invo_attach_emp/"; 
        $fileName = basename($_FILES['file']['name']);
        $tmp_name = $_FILES['file']['tmp_name'];
        $rand = md5($fileName);
        $file_ext = explode('.',$fileName);
        $file_ext_count=count($file_ext);
        $cnt=$file_ext_count-1;
        $file_extension= $file_ext[$cnt];
        $filename_po = $getinv_no."_".$rand."_".date('ymdis').".".$file_extension;
        $uploadFilePath = $uploadDir.$filename_po;
        if(move_uploaded_file($tmp_name, $uploadFilePath)){ 
            $sql = "INSERT INTO `emp_inv_attachment` (`emp_id`, `srno`, `attachment`, `docu_ext`, `inv_count`) VALUES ('".$empid."', '".$getinv_no."', '".$filename_po."', '".$file_extension."', '".$count."')"; 
        }

    }
    if(mysqli_query($conDB, $sql)){
        $data = [
            'title'   => "Added!",
            'message' => "Record has been added successfully.",
            'type'    => 'success',
        ];
        echo json_encode($data);
    } else {
        $data = [
            'title'   => "Error!",
            'message' => "Record not added because there are some error.",
            'type'    => 'error',
        ];
        echo json_encode($data);
    }

    } else {
        echo json_encode(['Data' => 'Error']);
    }
?>