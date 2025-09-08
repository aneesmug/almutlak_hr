<?php

    require_once __DIR__ . '/../../includes/db.php';

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
?> 