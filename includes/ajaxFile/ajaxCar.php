<?php
require_once __DIR__ . '/../../includes/db.php';
include("./../../includes/custom_functions.php");

$ajaxType = $_POST['ajaxType'];

if ($ajaxType == 'add_car') {
    $maker_name = $_POST['maker_name'];
    $maker_model = $_POST['maker_model'];
    $made_year = $_POST['made_year'];
    $plate_no = strtoupper($_POST['plate_no']);
    $type = $_POST['type'];
    $remarks = $_POST['remarks'];
    $sql="INSERT INTO `cars` (`maker_name`, `model`, `made_year`, `plate_no`, `type`, `remarks`, `created_at`) VALUES ('".$maker_name."', '".$maker_model."', '".$made_year."', '".$plate_no."', '".$type."', '".$remarks."', '".date('Y-m-d H:i:s')."')";

    if(mysqli_query($conDB, $sql)){
        send_json_response(__('added_successfully'), __('success_record_submitted'), "success");
    } else {
        send_json_response(__('error_title'), __('error_record_submitted'), "error");
    }
} elseif($ajaxType == 'edit_car'){
    $maker_name = $_POST['maker_name'];
    $maker_model = $_POST['maker_model'];
    $made_year = $_POST['made_year'];
    $plate_no = strtoupper($_POST['plate_no']);
    $type = $_POST['type'];
    $remarks = $_POST['remarks'];
    $status = $_POST['status'];
    $sql = "UPDATE `cars` SET `maker_name`='".$maker_name."', `model`='".$maker_model."', `made_year`='".$made_year."', `plate_no`='".$plate_no."', `type`='".$type."', `remarks`='".$remarks."', `status`='".$status."' WHERE `id`='".$_POST['carid']."' ";
    if(mysqli_query($conDB, $sql)){
        send_json_response(__('update'), __('this_record_has_been_updated_successfully'), "success");
    } else {
        send_json_response(__('error_title'), __('error_record_submitted'), "error");
    }
} elseif($ajaxType == 'maint_type') {
    $stmt = mysqli_query($conDB, "SELECT * FROM `maint_type` ORDER BY `type` REGEXP '^[^A-Za-z]' ASC, `type` ");

    while($row = mysqli_fetch_assoc($stmt)) {
        $type[] = $row;
    }
    $data = [
        'data'      => $type,
        'status'    => 200
    ];
    echo json_encode($data);
} elseif($ajaxType == 'cars_maint') {
    $stmt = mysqli_query($conDB, "SELECT * FROM `cars_maint` WHERE `car_id`='$_POST[id]' ORDER BY `id` DESC LIMIT 1 ");
    while($row = mysqli_fetch_assoc($stmt)) {
        $name = $row['meter'];
    }
    $data = [
        'data'      => $name,
    ];
    echo json_encode($data);
} elseif($ajaxType == 'cars_maint_add'){
    $car_id = $_POST['cid'];
    $meter = $_POST['meter'];
    $car_user = $_POST['car_user'];
    $type = $_POST['type'];
    $diffmeter = $_POST['diffmeter'];
    $date = $_POST['date'];
    $details = mysqli_real_escape_string($conDB, $_POST['details']);
    $remarks = mysqli_real_escape_string($conDB, $_POST['remarks']);
    $sql="INSERT INTO `cars_maint`(`car_id`, `meter`, `diffmeter`, `date`, `car_user`, `type`, `details`, `remarks`, `created_at`) VALUES ('$car_id','$meter','$diffmeter','$date','$car_user','$type','$details','$remarks','".date('Y-m-d H:i:s')."')";

    if(mysqli_query($conDB, $sql)){
        send_json_response(__('added_successfully'), __('success_record_submitted'), "success");
    } else {
        send_json_response(__('error_title'), __('error_record_submitted'), "error");
    }
} elseif($ajaxType == 'maker_search'){
    $stmt = mysqli_query($conDB, "
        SELECT * FROM `car_maker`GROUP BY `maker`");
    if (mysqli_num_rows($stmt) > 1) {
        while($row = mysqli_fetch_assoc($stmt)) {
            $items[] = $row;
        }
    }
    $data = [
        'status'    => 200,
        'data'      => $items,
    ];
    echo json_encode($data);
} elseif($ajaxType == 'model_search'){
    $request = 0;
    if(isset($_POST['request'])){
        $request = $_POST['request'];
    }
    if($request == 1){
        $id = $_POST['maker_name'];
        $stmt = mysqli_query($conDB, "SELECT * FROM `car_model` WHERE `mkid`='$id' ORDER BY `model` REGEXP '^[^A-Za-z]' ASC, `model` ");
        while($row = mysqli_fetch_assoc($stmt)) {
            echo "<option value='$row[id]'>$row[model]</option>";
        }
    }
} elseif($ajaxType == 'driver_add'){
    $id = $_POST['cid'];
    $car_user_up = $_POST['car_user'];
    $rcv_date_up = $_POST['rcv_date'];
    $sql="INSERT INTO `cars_drv` (`car_id`, `car_user`, `rcv_date`, `created_at`) VALUES ('".$id."', '".$car_user_up."', '".$rcv_date_up."', '".date('Y-m-d H:i:s')."')";
    if(mysqli_query($conDB, $sql)){
        send_json_response(__('added_successfully'), __('success_record_submitted'), "success");
    } else {
        send_json_response(__('error_title'), __('error_record_submitted'), "error");
    }
} elseif($ajaxType == 'drvr_rtrn_update'){
    $id = $_POST['pid'];
    $cid = $_POST['pcid'];
    $sql = "UPDATE `cars_drv` SET `status`='0', `rtn_date`='".date('Y-m-d H:i:s')."' WHERE `id`='".$id."' AND `car_id`='".$cid."'";
    if(mysqli_query($conDB, $sql)){
        send_json_response(__('update'), __('this_record_has_been_updated_successfully'), "success");
    } else {
        send_json_response(__('error_title'), __('error_record_submitted'), "error");
    }
} elseif($ajaxType == 'maint_type_add'){
    $type = $_POST['type'];
    $sql = "INSERT INTO `maint_type` (`type`) VALUES ('".$type."')";
    if(mysqli_query($conDB, $sql)){
        send_json_response(__('added_successfully'), __('success_record_submitted'), "success");
    } else {
        send_json_response(__('error_title'), __('error_record_submitted'), "error");
    }
} elseif($ajaxType == 'document_add'){
    $id = $_POST['cid'];
    $doc_type_up = $_POST['doc_type'];
    $issue_date_up = $_POST['issue_date'];
    $exp_date_up = $_POST['exp_date'];
    if (file_exists($_FILES['file']['tmp_name']) || is_uploaded_file($_FILES['file']['tmp_name'])) {
        $uploadDir = "./../../assets/cars_documents/";
        $fileName = basename($_FILES['file']['name']);
        $tmp_name = $_FILES['file']['tmp_name'];
        $rand = rand(0000,9999).time();
        $file_ext = explode('.',$fileName);
        $file_ext_count=count($file_ext);
        $cnt=$file_ext_count-1;
        $file_extension= $file_ext[$cnt];
        $filename_po = $id.strtoupper($doc_type_up).$rand.".".$file_extension;
        $uploadFilePath = $uploadDir.$filename_po; 
        move_uploaded_file($tmp_name, $uploadFilePath);
    }
    $sql="INSERT INTO `cars_docu` (`car_id`, `doc_type`, `issue_date`, `exp_date`, `file`, `created_at`) VALUES ('".$id."', '".$doc_type_up."', '".$issue_date_up."', '".$exp_date_up."', '".$filename_po."', '".date('Y-m-d H:i:s')."')";
    if(mysqli_query($conDB, $sql)){
        send_json_response(__('added_successfully'), __('success_record_submitted'), "success");
    } else {
        send_json_response(__('error_title'), __('error_record_submitted'), "error");
    }
} elseif($ajaxType == 'model_add'){
    $mkid = $_POST['maker_name'];
    $maker_model = $_POST['maker_model'];
    $sql = "INSERT INTO `car_model` (`mkid`, `model`) VALUES ('".$mkid."','".$maker_model."')";
    if(mysqli_query($conDB, $sql)){
        send_json_response(__('added_successfully'), __('success_record_submitted'), "success");
    } else {
        send_json_response(__('error_title'), __('error_record_submitted'), "error");
    }
}

?>