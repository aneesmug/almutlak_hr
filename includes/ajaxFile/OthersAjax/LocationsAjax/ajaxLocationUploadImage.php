<?php

    require_once __DIR__ . '/../../includes/db.php';

    $id = $_POST['id'];
    $data = $_POST['image'] ?? '';
    $section = $_POST['section'];
    $postion = ($_POST['postion']=='in')?"in_img":"out_img";
    $section_name = str_replace(' ','',$section);
    // Validate this is a legitimate image data URI before decoding
    if (!preg_match('/^data:image\/(png|jpeg|jpg|gif|webp);base64,/i', $data)) {
        echo json_encode(['title' => 'Error!', 'message' => 'Invalid image format.', 'type' => 'error']);
        exit;
    }
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
?>