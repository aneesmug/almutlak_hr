<?php
	require_once __DIR__ . '/../../includes/db.php';
	require_once __DIR__ . '/../FileUploader.php';
    
    $id = $_POST['cid'];
    $doc_type_up = $_POST['doc_type'];
    $issue_date_up = $_POST['issue_date'];
    $exp_date_up = $_POST['exp_date'];
    if (file_exists($_FILES['file']['tmp_name']) || is_uploaded_file($_FILES['file']['tmp_name'])) {
        $uploadDir = "./../../assets/cars_documents/";
        $tmp_name = $_FILES['file']['tmp_name'];
        $rand = rand(1000, 9999) . time();
        $file_extension = FileUploader::safeExt($_FILES['file']['name'], 'document');
        $filename_po = $id . strtoupper(preg_replace('/[^A-Za-z0-9_-]/', '', (string)$doc_type_up)) . $rand . "." . $file_extension;
        if ($file_extension === null || !FileUploader::moveTo($tmp_name, $uploadDir . $filename_po, 'document')) {
            echo json_encode(['title' => "Error!", 'message' => FileUploader::lastError() ?: 'File type not allowed. Allowed: ' . FileUploader::allowedLabel('document'), 'type' => 'error']);
            exit;
        }
    }
    $sql="INSERT INTO `cars_docu` (`car_id`, `doc_type`, `issue_date`, `exp_date`, `file`, `created_at`) VALUES ('".$id."', '".$doc_type_up."', '".$issue_date_up."', '".$exp_date_up."', '".$filename_po."', '".date('Y-m-d H:i:s')."')";

    if(mysqli_query($conDB, $sql)){
    	$data = [
            'title'   => "Added!",
            'message' => "This car document has been added successfully.",
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