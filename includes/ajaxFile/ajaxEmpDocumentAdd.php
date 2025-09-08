<?php
	require_once __DIR__ . '/../../includes/db.php';

    $id = $_POST['id'];
    $docu_typ_up = $_POST['docu_typ'];
    $emp_id_up = $_POST['emp_id'];
    if (file_exists($_FILES['file']['tmp_name']) || is_uploaded_file($_FILES['file']['tmp_name'])) {
        $uploadDir = "./../../assets/emp_documents/";
        $fileName = basename($_FILES['file']['name']);
        $tmp_name = $_FILES['file']['tmp_name'];
        $rand = rand(0000,9999).time();
        $file_ext = explode('.',$fileName);
        $file_ext_count=count($file_ext);
        $cnt=$file_ext_count-1;
        $file_extension= $file_ext[$cnt];
        $filename_po = $id.strtoupper($docu_typ_up).$rand.".".$file_extension;
        $uploadFilePath = $uploadDir.$filename_po;
        move_uploaded_file($tmp_name, $uploadFilePath);
    }
    $sql="INSERT INTO `emp_docu` (`emp_id`, `docu_typ`, `attachment`, `docu_ext`, `pgid`) VALUES ('".$emp_id_up."', '".$docu_typ_up."', '".$filename_po."', '".$file_extension."','".$id."')";

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
    		'type' 	  => 'error',
    	];
        echo json_encode($data);
    }

?>