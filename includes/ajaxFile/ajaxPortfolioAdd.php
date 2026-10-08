<?php
    require_once __DIR__ . '/../../includes/db.php';
	require_once __DIR__ . '/../FileUploader.php';

    /*$data = [
        'title'   => $_POST,
    ];
    echo json_encode($data);*/
    
    $emp_id = $_POST['emp_id'];
    $title_up = $_POST['title'];
    $description_up = mysqli_real_escape_string($conDB, $_POST['description']);
    if (file_exists($_FILES['file']['tmp_name']) || is_uploaded_file($_FILES['file']['tmp_name'])) {
        $uploadDir = "./../../assets/emp_documents/";
        $tmp_name = $_FILES['file']['tmp_name'];
        $rand = rand(1000, 9999) . time();
        $file_extension = FileUploader::safeExt($_FILES['file']['name'], 'document');
        $filename_po = $id . strtoupper(preg_replace('/[^A-Za-z0-9_-]/', '', (string)$title_up)) . $rand . "." . $file_extension;
        if ($file_extension === null || !FileUploader::moveTo($tmp_name, $uploadDir . $filename_po, 'document')) {
            echo json_encode(['title' => "Error!", 'message' => FileUploader::lastError() ?: 'File type not allowed. Allowed: ' . FileUploader::allowedLabel('document'), 'type' => 'error']);
            exit;
        }
    }
    $sql="INSERT INTO `portfolio` (`emp_id`, `title`, `description`, `attachment`, `created_at`) VALUES ('".$emp_id."', '".$title_up."', '".$description_up."', '".$filename_po."', '".date('Y-m-d H:i:s')."')";

    if(mysqli_query($conDB, $sql)){
        $data = [
            'title'   => "Added!",
            'message' => "This portfoilo has been added successfully.",
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
?>