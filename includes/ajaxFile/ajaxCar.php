<?php
require_once __DIR__ . '/../FileUploader.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/session_check.php';
require_once __DIR__ . '/../../includes/special_access_helper.php';
include("./../../includes/helper_functions.php");

$ajaxType = $_POST['ajaxType'] ?? '';

// Plain-text POST value, escaped for SQL.
function car_post_str($key, $max = 255) {
    global $conDB;
    $v = trim((string)($_POST[$key] ?? ''));
    if ($max > 0) { $v = mb_substr($v, 0, $max); }
    return mysqli_real_escape_string($conDB, $v);
}
// Y-m-d date or '' when missing / invalid.
function car_post_date($key) {
    $v = trim((string)($_POST[$key] ?? ''));
    $d = DateTime::createFromFormat('Y-m-d', $v);
    return ($d && $d->format('Y-m-d') === $v) ? $v : '';
}

$car_types = ['Bus', 'Car', 'Dyna', 'Fork Lift', 'Jeep', 'Pick Up', 'Truck', 'Van'];

if ($ajaxType === 'add_car' || $ajaxType === 'edit_car') {
    $canManageCar = !empty($is_system_admin) || user_has_special_access($conDB, $empid ?? '', $ajaxType === 'add_car' ? 'cars_add' : 'cars_edit', $user_role ?? '', $user_type ?? '', $is_system_admin ?? false);
    if (!$canManageCar) {
        send_json_response(__('error_title'), 'Access denied.', "error", 403);
    }

    $maker_name = (int)($_POST['maker_name'] ?? 0);
    $maker_model = (int)($_POST['maker_model'] ?? 0);
    $made_year = (int)($_POST['made_year'] ?? 0);
    $plate_no = strtoupper(trim((string)($_POST['plate_no'] ?? '')));
    $type = (string)($_POST['type'] ?? '');
    $remarks = car_post_str('remarks');

    if ($maker_name <= 0 || $maker_model <= 0) {
        send_json_response(__('error_title'), __('enter_car_model_validation'), "error", 422);
    }
    if ($made_year < 1950 || $made_year > (int)date('Y') + 1) {
        send_json_response(__('error_title'), __('enter_car_made_year_validation'), "error", 422);
    }
    if (!preg_match('/^\d{1,4}-[A-Z]{1,3}$/', $plate_no)) {
        send_json_response(__('error_title'), __('enter_car_plate_no_validation'), "error", 422);
    }
    if (!in_array($type, $car_types, true)) {
        send_json_response(__('error_title'), __('select_car_type_validation'), "error", 422);
    }
}

if ($ajaxType == 'add_car') {
    $sql="INSERT INTO `cars` (`maker_name`, `model`, `made_year`, `plate_no`, `type`, `remarks`, `created_at`) VALUES ('".$maker_name."', '".$maker_model."', '".$made_year."', '".$plate_no."', '".$type."', '".$remarks."', '".date('Y-m-d H:i:s')."')";

    if(mysqli_query($conDB, $sql)){
        $car_id = mysqli_insert_id($conDB);

        // Log vehicle creation
        ActivityLogger::logCreate('Vehicle', 'ajaxCar.php', $car_id, [
            'maker_name' => $maker_name,
            'model' => $maker_model,
            'made_year' => $made_year,
            'plate_no' => $plate_no,
            'type' => $type,
            'remarks' => $remarks
        ], "Added vehicle: {$maker_name} {$maker_model} ({$plate_no})", 'cars');

        send_json_response(__('added_successfully'), __('success_record_submitted'), "success");
    } else {
        send_json_response(__('error_title'), __('error_record_submitted'), "error");
    }
} elseif($ajaxType == 'edit_car'){
    $status = (($_POST['status'] ?? '1') === '0') ? '0' : '1';
    $car_id = (int)($_POST['carid'] ?? 0);
    if ($car_id <= 0) {
        send_json_response(__('error_title'), __('error_record_submitted'), "error", 422);
    }

    // Fetch old car data for logging
    $old_result = mysqli_query($conDB, "SELECT * FROM cars WHERE id = '{$car_id}'");
    $old_car = mysqli_fetch_assoc($old_result);

    $sql = "UPDATE `cars` SET `maker_name`='".$maker_name."', `model`='".$maker_model."', `made_year`='".$made_year."', `plate_no`='".$plate_no."', `type`='".$type."', `remarks`='".$remarks."', `status`='".$status."' WHERE `id`='".$car_id."' ";
    if(mysqli_query($conDB, $sql)){
        // Log vehicle update
        ActivityLogger::logUpdate('Vehicle', 'ajaxCar.php', $car_id, $old_car ?? [], [
            'maker_name' => $maker_name,
            'model' => $maker_model,
            'made_year' => $made_year,
            'plate_no' => $plate_no,
            'type' => $type,
            'status' => $status,
            'remarks' => $remarks
        ], "Updated vehicle: {$maker_name} {$maker_model} ({$plate_no})", 'cars');

        send_json_response(__('update'), __('this_record_has_been_updated_successfully'), "success");
    } else {
        send_json_response(__('error_title'), __('error_record_submitted'), "error");
    }
} elseif($ajaxType == 'maint_type') {
    $stmt = mysqli_query($conDB, "SELECT * FROM `maint_type` ORDER BY `type` REGEXP '^[^A-Za-z]' ASC, `type` ");

    $type = [];
    while($row = mysqli_fetch_assoc($stmt)) {
        $type[] = $row;
    }
    $data = [
        'data'      => $type,
        'status'    => 200
    ];
    echo json_encode($data);
} elseif($ajaxType == 'cars_maint') {
    $car_id = (int)($_POST['id'] ?? 0);
    $stmt = mysqli_query($conDB, "SELECT * FROM `cars_maint` WHERE `car_id`='" . $car_id . "' ORDER BY `id` DESC LIMIT 1 ");
    $name = '';
    while($row = mysqli_fetch_assoc($stmt)) {
        $name = $row['meter'];
    }
    $data = [
        'data'      => $name,
    ];
    echo json_encode($data);
} elseif($ajaxType == 'cars_maint_add'){
    $car_id = (int)($_POST['cid'] ?? 0);
    $meter = (int)preg_replace('/\D/', '', (string)($_POST['meter'] ?? ''));
    $car_user = car_post_str('car_user', 50);
    $type = car_post_str('type');
    $diffmeter = car_post_str('diffmeter', 50);
    $date = car_post_date('date');
    $details = car_post_str('details', 0);
    $remarks = car_post_str('remarks', 0);
    if ($car_id <= 0 || $car_user === '' || $type === '' || $date === '' || $details === '' || ($_POST['meter'] ?? '') === '') {
        send_json_response(__('error_title'), __('fill_mandatory_fields'), "error", 422);
    }
    $sql="INSERT INTO `cars_maint`(`car_id`, `meter`, `diffmeter`, `date`, `car_user`, `type`, `details`, `remarks`, `created_at`) VALUES ('$car_id','$meter','$diffmeter','$date','$car_user','$type','$details','$remarks','".date('Y-m-d H:i:s')."')";

    if(mysqli_query($conDB, $sql)){
        send_json_response(__('added_successfully'), __('success_record_submitted'), "success");
    } else {
        send_json_response(__('error_title'), __('error_record_submitted'), "error");
    }
} elseif($ajaxType == 'maker_search'){
    $stmt = mysqli_query($conDB, "
        SELECT * FROM `car_maker`GROUP BY `maker`");
    $items = [];
    while($row = mysqli_fetch_assoc($stmt)) {
        $items[] = $row;
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
        $id = (int)($_POST['maker_name'] ?? 0);
        $stmt = mysqli_query($conDB, "SELECT * FROM `car_model` WHERE `mkid`='$id' ORDER BY `model` REGEXP '^[^A-Za-z]' ASC, `model` ");
        while($row = mysqli_fetch_assoc($stmt)) {
            echo "<option value='" . (int)$row['id'] . "'>" . htmlspecialchars($row['model'], ENT_QUOTES) . "</option>";
        }
    }
} elseif($ajaxType == 'driver_add'){
    $id = (int)($_POST['cid'] ?? 0);
    $car_user_up = car_post_str('car_user', 50);
    $rcv_date_up = car_post_date('rcv_date');
    if ($id <= 0 || $car_user_up === '' || $rcv_date_up === '') {
        send_json_response(__('error_title'), __('fill_mandatory_fields'), "error", 422);
    }
    $busy = mysqli_query($conDB, "SELECT `id` FROM `cars_drv` WHERE `car_id`='" . $id . "' AND `status`='1' LIMIT 1");
    if ($busy && mysqli_num_rows($busy) > 0) {
        send_json_response(__('error_title'), __('car_already_has_driver', 'This car already has a driver. Return it first.'), "error", 409);
    }
    $sql="INSERT INTO `cars_drv` (`car_id`, `car_user`, `rcv_date`, `created_at`) VALUES ('".$id."', '".$car_user_up."', '".$rcv_date_up."', '".date('Y-m-d H:i:s')."')";
    if(mysqli_query($conDB, $sql)){
        send_json_response(__('added_successfully'), __('success_record_submitted'), "success");
    } else {
        send_json_response(__('error_title'), __('error_record_submitted'), "error");
    }
} elseif($ajaxType == 'drvr_rtrn_update'){
    $id = (int)($_POST['pid'] ?? 0);
    $cid = (int)($_POST['pcid'] ?? 0);
    $sql = "UPDATE `cars_drv` SET `status`='0', `rtn_date`='".date('Y-m-d H:i:s')."' WHERE `id`='".$id."' AND `car_id`='".$cid."'";
    if(mysqli_query($conDB, $sql)){
        send_json_response(__('update'), __('this_record_has_been_updated_successfully'), "success");
    } else {
        send_json_response(__('error_title'), __('error_record_submitted'), "error");
    }
} elseif($ajaxType == 'maint_type_add'){
    $type = car_post_str('type', 100);
    if ($type === '') {
        send_json_response(__('error_title'), __('enter_type_name_validation'), "error", 422);
    }
    $exists = mysqli_query($conDB, "SELECT `type` FROM `maint_type` WHERE `type`='" . $type . "' LIMIT 1");
    if ($exists && mysqli_num_rows($exists) > 0) {
        send_json_response(__('added_successfully'), __('success_record_submitted'), "success");
    }
    $sql = "INSERT INTO `maint_type` (`type`) VALUES ('".$type."')";
    if(mysqli_query($conDB, $sql)){
        send_json_response(__('added_successfully'), __('success_record_submitted'), "success");
    } else {
        send_json_response(__('error_title'), __('error_record_submitted'), "error");
    }
} elseif($ajaxType == 'document_add'){
    $id = (int)($_POST['cid'] ?? 0);
    $doc_type_up = (string)($_POST['doc_type'] ?? '');
    $issue_date_up = car_post_date('issue_date');
    $exp_date_up = car_post_date('exp_date');
    if ($id <= 0 || !in_array($doc_type_up, ['Licence', 'Insurance', 'MVPI'], true) || $issue_date_up === '' || $exp_date_up === '') {
        send_json_response(__('error_title'), __('fill_mandatory_fields'), "error", 422);
    }
    $filename_po = '';
    if (!empty($_FILES['file']['tmp_name']) && is_uploaded_file($_FILES['file']['tmp_name'])) {
        $file_extension = strtolower(pathinfo((string)$_FILES['file']['name'], PATHINFO_EXTENSION));
        if (!in_array($file_extension, ['pdf', 'jpg', 'jpeg', 'png'], true)) {
            send_json_response(__('error_title'), __('upload_pdf_jpg_only_validation'), "error", 422);
        }
        if ((int)$_FILES['file']['size'] > 8 * 1048576) {
            send_json_response(__('error_title'), str_replace('%s', '8', __('upload_size_limit_5mb_validation')), "error", 422);
        }
        $uploadDir = "./../../assets/cars_documents/";
        $filename_po = $id . strtoupper($doc_type_up) . rand(1000, 9999) . time() . "." . $file_extension;
        if (!FileUploader::moveTo($_FILES['file']['tmp_name'], $uploadDir . $filename_po, ['pdf', 'jpg', 'jpeg', 'png'])) {
            send_json_response(__('error_title'), __('error_record_submitted'), "error", 500);
        }
    }
    $sql="INSERT INTO `cars_docu` (`car_id`, `doc_type`, `issue_date`, `exp_date`, `file`, `created_at`) VALUES ('".$id."', '".$doc_type_up."', '".$issue_date_up."', '".$exp_date_up."', '".$filename_po."', '".date('Y-m-d H:i:s')."')";
    if(mysqli_query($conDB, $sql)){
        send_json_response(__('added_successfully'), __('success_record_submitted'), "success");
    } else {
        send_json_response(__('error_title'), __('error_record_submitted'), "error");
    }
} elseif($ajaxType == 'model_add'){
    $mkid = (int)($_POST['maker_name'] ?? 0);
    $maker_model = car_post_str('maker_model', 100);
    if ($mkid <= 0 || $maker_model === '') {
        send_json_response(__('error_title'), __('enter_car_model_validation'), "error", 422);
    }
    $exists = mysqli_query($conDB, "SELECT `id` FROM `car_model` WHERE `mkid`='" . $mkid . "' AND `model`='" . $maker_model . "' LIMIT 1");
    if ($exists && ($row = mysqli_fetch_assoc($exists))) {
        send_json_response(__('added_successfully'), __('success_record_submitted'), "success", 200, ['id' => (int)$row['id']]);
    }
    $sql = "INSERT INTO `car_model` (`mkid`, `model`) VALUES ('".$mkid."','".$maker_model."')";
    if(mysqli_query($conDB, $sql)){
        send_json_response(__('added_successfully'), __('success_record_submitted'), "success", 200, ['id' => (int)mysqli_insert_id($conDB)]);
    } else {
        send_json_response(__('error_title'), __('error_record_submitted'), "error");
    }
}

?>
