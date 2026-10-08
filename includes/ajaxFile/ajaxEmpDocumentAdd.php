<?php
	require_once __DIR__ . '/../../includes/db.php';
	require_once __DIR__ . '/../FileUploader.php';

    $id = $_POST['id'];
    $docu_typ_up = $_POST['docu_typ'];
    $emp_id_up = $_POST['emp_id'];
    if (file_exists($_FILES['file']['tmp_name']) || is_uploaded_file($_FILES['file']['tmp_name'])) {
        $uploadDir = "./../../assets/emp_documents/";
        $tmp_name = $_FILES['file']['tmp_name'];
        $rand = rand(1000, 9999) . time();
        $file_extension = FileUploader::safeExt($_FILES['file']['name'], 'document');
        $filename_po = $id . strtoupper(preg_replace('/[^A-Za-z0-9_-]/', '', (string)$docu_typ_up)) . $rand . "." . $file_extension;
        if ($file_extension === null || !FileUploader::moveTo($tmp_name, $uploadDir . $filename_po, 'document')) {
            echo json_encode(['title' => "Error!", 'message' => FileUploader::lastError() ?: 'File type not allowed. Allowed: ' . FileUploader::allowedLabel('document'), 'type' => 'error']);
            exit;
        }
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