<?php
	require_once __DIR__ . '/../../includes/db.php';

$ajaxType = $_POST['ajaxType'];

if ($ajaxType == 'add_location') {
    $section_name = mysqli_real_escape_string($conDB, $_POST['section_name']);
    $dept = mysqli_real_escape_string($conDB, $_POST['dept']);
    $camera_in = mysqli_real_escape_string($conDB, $_POST['camera_in']);
    $camera_out = mysqli_real_escape_string($conDB, $_POST['camera_out']);
    $b_license_exp = mysqli_real_escape_string($conDB, $_POST['b_license_exp']);
    $b_license_no = mysqli_real_escape_string($conDB, $_POST['b_license_no']);
    $location_dist = mysqli_real_escape_string($conDB, $_POST['location_dist']);
    $bulding_base = mysqli_real_escape_string($conDB, $_POST['bulding_base']);
    $bulding_size = mysqli_real_escape_string($conDB, $_POST['bulding_size']);
    $t_bulding_size = mysqli_real_escape_string($conDB, $_POST['t_bulding_size']);
    $latitude = mysqli_real_escape_string($conDB, $_POST['latitude']);
    $longitude = mysqli_real_escape_string($conDB, $_POST['longitude']);
    $loc_address = mysqli_real_escape_string($conDB, $_POST['loc_address']);
    $municipality = mysqli_real_escape_string($conDB, $_POST['municipality']);
    $sub_municipality = mysqli_real_escape_string($conDB, $_POST['sub_municipality']);
    $sql="INSERT INTO `section` (`section_name`, `dept`, `camera_in`, `camera_out`, `b_license_exp`, `b_license_no`, `location_dist`, `bulding_base`, `bulding_size`, `t_bulding_size`, `latitude`, `longitude`, `location_name`, `municipality`, `sub_municipality`, `created_at`) VALUES ('".$section_name."', '".$dept."', '".$camera_in."','".$camera_out."','".$b_license_exp."','".$b_license_no."','".$location_dist."','".$bulding_base."','".$bulding_size."','".$t_bulding_size."','".$latitude."','".$longitude."','".$loc_address."','".$municipality."','".$sub_municipality."','".date('Y-m-d H:i:s')."')";
    if(mysqli_query($conDB, $sql)){
        $data = [
            'title'   => "Added!",
            'message' => "This location has been added successfully.",
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
} elseif($ajaxType == 'edit_location'){
    $section_name = mysqli_real_escape_string($conDB, $_POST['section_name']);
    $dept = mysqli_real_escape_string($conDB, $_POST['dept']);
    $camera_in = mysqli_real_escape_string($conDB, $_POST['camera_in']);
    $camera_out = mysqli_real_escape_string($conDB, $_POST['camera_out']);
    $b_license_exp = mysqli_real_escape_string($conDB, $_POST['b_license_exp']);
    $b_license_no = mysqli_real_escape_string($conDB, $_POST['b_license_no']);
    $location_dist = mysqli_real_escape_string($conDB, $_POST['location_dist']);
    $bulding_base = mysqli_real_escape_string($conDB, $_POST['bulding_base']);
    $bulding_size = mysqli_real_escape_string($conDB, $_POST['bulding_size']);
    $t_bulding_size = mysqli_real_escape_string($conDB, $_POST['t_bulding_size']);
    $latitude = mysqli_real_escape_string($conDB, $_POST['latitude']);
    $longitude = mysqli_real_escape_string($conDB, $_POST['longitude']);
    $loc_address = mysqli_real_escape_string($conDB, $_POST['loc_address']);
    $municipality = mysqli_real_escape_string($conDB, $_POST['municipality']);
    $sub_municipality = mysqli_real_escape_string($conDB, $_POST['sub_municipality']);
    $status = $_POST['status'];

    $sql="UPDATE `section` SET `section_name`='".$section_name."', `dept`='".$dept."',`camera_in`='".$camera_in."',`camera_out`='".$camera_out."',`b_license_exp`='".$b_license_exp."',`b_license_no`='".$b_license_no."',`location_dist`='".$location_dist."', `bulding_base`='".$bulding_base."', `bulding_size`='".$bulding_size."', `t_bulding_size`='".$t_bulding_size."', `latitude`='".$latitude."', `longitude`='".$longitude."', `location_name`='".$loc_address."', `municipality`='".$municipality."', `sub_municipality`='".$sub_municipality."', `status`='".$status."' WHERE `id`='".$_POST['smid']."'";

    if(mysqli_query($conDB, $sql)){
        $data = [
            'title'   => "Updated!",
            'message' => "This location has been update successfully.",
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
} elseif($ajaxType == 'section_view') {
    $stmt = mysqli_query($conDB, "SELECT DISTINCT `section_name` FROM `section` ORDER BY `section_name` REGEXP '^[^A-Za-z]' ASC, section_name");
    while($row = mysqli_fetch_assoc($stmt)) {
        $section_name[] = $row;
    }
    $data = [
        'data'      => $section_name,
        'status'    => 200
    ];
    echo json_encode($data);
} elseif($ajaxType == 'loc_department') {
    $stmt = mysqli_query($conDB, "SELECT * FROM `department` ORDER BY `dep_nme` REGEXP '^[^A-Za-z]' ASC, `dep_nme` ");
    while($row = mysqli_fetch_assoc($stmt)) {
        $dep_nme[] = $row;
    }
    $data = [
        'data'      => $dep_nme,
        'status'    => 200
    ];
    echo json_encode($data);
} elseif($ajaxType == 'upload_image'){
    $id = $_POST['id'];
    $data = $_POST['image'];
    $section = $_POST['section'];
    $postion = ($_POST['postion']=='in')?"in_img":"out_img";
    $section_name = str_replace(' ','',$section);
    list($type, $data) = explode(';', $data);
    list(, $data) = explode(',', $data);
    $data = base64_decode($data);
    $imageName = time() . '.png';
    $filepath = "./../../assets/location_content/";
    $filepathup = "./assets/location_content/";
    $imagenameu = $id."".$section_name."".$imageName;
    if (empty($data) || (isset($data['error']) && $data['error'] == UPLOAD_ERR_NO_FILE)) {
        echo "No Picture upload";
    } else {
        file_put_contents($filepath . $id."".$section_name."".$imageName , $data);
        mysqli_query($conDB, "UPDATE `location_img` SET `".$postion."` ='".$filepathup."".$imagenameu."' WHERE `location_id`='".$id."' ");
        $data = [
            'title'   => "Updated!",
            'message' => "Image Uploaded Successfully",
            'type'    => 'success',
        ];
        echo json_encode($data);
    }
} elseif($ajaxType == 'add_contract'){
    $owner_name = mysqli_real_escape_string($conDB, $_POST['owner_name']);
    $owner_number = mysqli_real_escape_string($conDB, $_POST['owner_number']);
    $owner_email = mysqli_real_escape_string($conDB, $_POST['owner_email']);
    $contract_no = mysqli_real_escape_string($conDB, $_POST['contract_no']);
    $start_cont_date = mysqli_real_escape_string($conDB, $_POST['start_cont_date']);
    $end_cont_date = mysqli_real_escape_string($conDB, $_POST['end_cont_date']);
    $rent = str_replace(',', '', $_POST['rent']);
    $service = str_replace(',', '', $_POST['service']);
    $elect_prc = str_replace(',', '', $_POST['elect_prc']);
    $water_prc = str_replace(',', '', $_POST['water_prc']);
    $incuranse_prc = str_replace(',', '', $_POST['incuranse_prc']);
    $others = str_replace(',', '', $_POST['others']);
    $sql = "INSERT INTO `location_contract` (`location_id`,`owner_name`, `owner_number`, `owner_email`, `contract_no`, `start_cont_date`, `end_cont_date`, `rent`, `service`, `elect_prc`, `water_prc`, `incuranse_prc`, `others`, `created_at`) VALUES ('".$_POST['locid']."','".$owner_name."', '".$owner_number."', '".$owner_email."','".$contract_no."','".$start_cont_date."','".$end_cont_date."','".$rent."','".$service."','".$elect_prc."','".$water_prc."','".$incuranse_prc."','".$others."', '".date('Y-m-d H:i:s')."')";
        mysqli_query($conDB, $query);
        mysqli_query($conDB, "UPDATE `section` SET `location_owner`='".$owner_name."' WHERE `id`='".$_POST['locid']."' ");
    if(mysqli_query($conDB, $sql)){
        $data = [
            'title'   => "Added!",
            'message' => "This record has been added successfully.",
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
} elseif($ajaxType == 'upload_document'){
   $getlocationid = $_POST['location_id'];
    $uploadDir = "./../../assets/location_content/"; 
    $fileName = basename($_FILES['file']['name']);
    $tmp_name = $_FILES['file']['tmp_name'];
    $file_ext = explode('.',$fileName);
    //count taken (if more than one . exist; files like abc.fff.2013.pdf
    $file_ext_count=count($file_ext);
    //minus 1 to make the offset correct
    $cnt=$file_ext_count-1;
    // the variable will have a value pdf as per the sample file name mentioned above.
    $file_extension= $file_ext[$cnt];
    $uploadFilePath = $uploadDir.$fileName; 
    // Upload file to server 
    if(move_uploaded_file($tmp_name, $uploadFilePath)){ 
        // Insert file information in the database 
        $sql = "INSERT INTO `location_docu` (`location_id`, `file_name`, `docu_ext`, `created_at`) VALUES ('".$getlocationid."', '".$fileName."', '".$file_extension."', '".date('Y-m-d H:i:s')."')"; 
        mysqli_query($conDB, $sql);
        $data = [
            'title'   => "Updated!",
            'message' => "File Uploaded Successfully",
            'type'    => 'success',
        ];
        echo json_encode($data);
    }
} elseif($ajaxType == 'section'){
    $stmt = mysqli_query($conDB, "SELECT * FROM `section` ORDER BY `section_name` REGEXP '^[^A-Za-z]' ASC, `section_name` ");
    while($row = mysqli_fetch_assoc($stmt)) {
        $section_name[] = $row;
    }
    $data = [
        'data'      => $section_name,
        'status'    => 200
    ];
    echo json_encode($data);
}

?>