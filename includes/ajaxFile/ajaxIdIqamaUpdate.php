<?php

    require_once __DIR__ . '/../../includes/db.php';
    include("./../../includes/Hijri_GregorianConvert.class");
    $DateConv=new Hijri_GregorianConvert;
    $format="YYYY-MM-DD";

    if ($_POST['iqama_exp']) {
        $iqama_exp = mysqli_real_escape_string($conDB, $_POST['iqama_exp']);
        $iqama_exp_gup = $DateConv->HijriToGregorian($iqama_exp, $format);
        $iqama_exp_g = date("Y-m-d", strtotime($iqama_exp_gup));
    } else{
        $iqama_exp_g = mysqli_real_escape_string($conDB, $_POST['iqama_exp_g']);
        $iqama_exp = $DateConv->GregorianToHijri($iqama_exp_g, $format);
    }
    // $iqama_exp = mysqli_real_escape_string($conDB, $_POST['iqama_exp_g']);
    /*$data = [
        'title'   => "UPDATE `employees` SET `iqama_exp`='".$iqama_exp."', `iqama_exp_g`='".$iqama_exp_g."' WHERE `id`='".$_POST['id']."'",
    ];
    echo json_encode($data);*/

    $sql="UPDATE `employees` SET `iqama_exp`='".$iqama_exp."', `iqama_exp_g`='".$iqama_exp_g."' WHERE `id`='".$_POST['id']."'";
    if(mysqli_query($conDB, $sql)){
        $data = [
            'title'   => "Updated!",
            'message' => "This record has been update successfully.",
            'type'    => 'success',
        ];
        echo json_encode($data);
    } else {
        $data = [
            'title'   => "Error!",
            'message' => "Record not updated because there are some error.",
            'type'    => 'error',
        ];
        echo json_encode($data);
    }
?> 