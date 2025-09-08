<?php
    header('Content-Type: application/json');
	require_once __DIR__ . '/../../includes/db.php';
    include("./../../includes/custom_functions.php"); // --- Helper Function ---

/****************************************************************
 * MODIFICATION SUMMARY (012-ajaxEmployee.php):
 * 1. ADDED `unassign_asset`: A new block to handle the un-assigning of an asset. It updates the `status` to 'Returned' and sets the `return_date` to the current date for the specified asset ID.
 ****************************************************************/

$ajaxType = $_POST['ajaxType'];

if($ajaxType == 'emp_search') {
    $stmt = mysqli_query($conDB, "SELECT * FROM `employees` WHERE `status`=1 ORDER BY `name` REGEXP '^[^A-Za-z]' ASC, `name` ");
    while($row = mysqli_fetch_assoc($stmt)) {
        $name[] = $row;
    }
    $data = [
        'data'      => $name,
        'status'    => 200
    ];
    echo json_encode($data);
} elseif($ajaxType == 'emp_data') {
    $stmt = mysqli_query($conDB, "SELECT 
    `e`.*,
    `d`.`dep_nme` AS `deptnme`
    FROM `employees` `e`
    LEFT JOIN `department` `d` ON `d`.`id` = `e`.`dept` 
    WHERE `e`.`status`=1 AND `e`.`emp_id`={$_POST['empid']} ");
    while($row = mysqli_fetch_assoc($stmt)) {
        $name[] = $row;
    }
    $data = [
        'data'      => $name,
        'status'    => 200
    ];
    echo json_encode($data);
} elseif($ajaxType == 'emp_department') {
    $stmt = mysqli_query($conDB, "SELECT 
    `e`.*,
    `d`.`dep_nme` AS `deptnme`
    FROM `employees` `e`
    LEFT JOIN `department` `d` ON `d`.`id` = `e`.`dept` 
    WHERE `e`.`status`=1 AND `e`.`dept`={$_POST['dept']} ");
    while($row = mysqli_fetch_assoc($stmt)) {
        $name[] = $row;
    }
    $data = [
        'data'      => $name,
        'status'    => 200
    ];
    echo json_encode($data);
} elseif($ajaxType == 'unassign_asset') {
    try {
        if (empty($_POST['asset_record_id']) || empty($_POST['return_date']) || empty($_POST['return_status'])) {
            throw new Exception('Required fields are missing.');
        }

        $attachment_path = null;
        if (isset($_FILES['return_attachment']) && $_FILES['return_attachment']['error'] == UPLOAD_ERR_OK) {
            $uploadDir = "./../../assets/assets_return/";
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            $fileExtension = pathinfo($_FILES['return_attachment']['name'], PATHINFO_EXTENSION);
            $fileName = "return_" . $_POST['asset_record_id'] . "_" . time() . '.' . $fileExtension;
            $targetPath = $uploadDir . $fileName;

            if (move_uploaded_file($_FILES['return_attachment']['tmp_name'], $targetPath)) {
                $attachment_path = $targetPath;
            } else {
                throw new Exception('Server could not save the uploaded file.');
            }
        }

        $stmt = $pdo->prepare(
            "UPDATE `employee_assets` SET 
                `status` = :return_status, 
                `return_date` = :return_date,
                `return_attachment` = :return_attachment
             WHERE `id` = :asset_record_id"
        );
        
        $stmt->execute([
            ':return_status' => $_POST['return_status'],
            ':return_date' => $_POST['return_date'],
            ':return_attachment' => $attachment_path,
            ':asset_record_id' => $_POST['asset_record_id']
        ]);

        if($stmt->rowCount() > 0){
            send_json_response("Returned!", "Asset has been marked as returned.", "success");
        } else {
            throw new Exception('Could not update the asset record. It may have already been returned.');
        }

    } catch (Exception $e) {
        send_json_response("Error", $e->getMessage(), "error");
    }
    exit;

} elseif($ajaxType == 'get_asset_types') {
    $stmt = mysqli_query($conDB, "SELECT `id`, `name` FROM `assets` ORDER BY `name` ASC");
    $assets = [];
    while($row = mysqli_fetch_assoc($stmt)) {
        $assets[] = $row;
    }
    echo json_encode(['success' => true, 'assets' => $assets]);
    exit;

} elseif($ajaxType == 'assign_asset') {
    try {
        if (empty($_POST['emp_id']) || empty($_POST['asset_id']) || empty($_POST['assigned_date'])) {
            throw new Exception('Required fields are missing.');
        }

        $stmt = $pdo->prepare(
            "INSERT INTO `employee_assets` (`emp_id`, `asset_id`, `serial_number`, `description`, `assigned_date`, `status`) 
             VALUES (:emp_id, :asset_id, :serial_number, :description, :assigned_date, 'Assigned')"
        );
        
        $stmt->execute([
            ':emp_id'         => $_POST['emp_id'],
            ':asset_id'       => $_POST['asset_id'],
            ':serial_number'  => $_POST['serial_number'],
            ':description'    => $_POST['description'],
            ':assigned_date'  => $_POST['assigned_date']
        ]);

        if($stmt->rowCount() > 0){
            send_json_response("Assigned!", "Asset has been assigned successfully.", "success");
        } else {
            throw new Exception('Failed to insert the asset record.');
        }

    } catch (Exception $e) {
        send_json_response("Error", $e->getMessage(), "error");
    }
    exit;
} elseif($ajaxType == 'avatar') {
    $data = $_POST['image'];
    $id = $_POST['id'];
    $emp_id = $_POST['emp_id'];
    $emptype = $_POST['emptype'];
    $emp_name = str_replace(' ','',$_POST['emp_name']);
    list($type, $data) = explode(';', $data);
    list(, $data) = explode(',', $data);
    $data = base64_decode($data);
    $imageName = time() . '.png';
    $filepath = "./../../assets/emp_pics/";
    $filepathup = "./assets/emp_pics/";
    $imagenameu = $emp_id."".$id."".$emp_name."".$imageName;
    if (empty($data) || (isset($data['error']) && $data['error'] == UPLOAD_ERR_NO_FILE)) {
        echo "No Picture upload";
    } else {
        file_put_contents($filepath . $emp_id."".$id."".$emp_name."".$imageName , $data);
        if ($emptype == 'employee') {
            try {
                $stmt = $pdo->prepare("INSERT INTO `employee_temp_contants` (`emp_id`, `type`, `path`) VALUES (:emp_id, 'Profile Picture', :filepath)");
                $stmt->execute([':emp_id' => $emp_id, ':filepath' => $filepathup . $imagenameu]);
            } catch(Exception $e) {
                send_json_response("Database Error", "The catch block is working. The error was: " . $e->getMessage(), "error");
            }
        } else {
            try {
                $stmt = $pdo->prepare("UPDATE `employees` SET `avatar` = :avatar WHERE `id` = :id AND `emp_id` = :emp_id");
                $stmt->execute([':avatar' => $filepathup . $imagenameu, ':id' => $id, ':emp_id' => $emp_id]);
            } catch(Exception $e) {
                send_json_response("Database Error", "The catch block is working. The error was: " . $e->getMessage(), "error");
            }
        }
        if($stmt->rowCount() > 0){
            send_json_response("Success!", "Image Uploaded Successfully.", "success");
        } else {
            send_json_response("Error!", "No changes made to profile picture", "error");
        }
    }
} elseif($ajaxType == 'add_social_links'){
    $emp_id_up = $_POST['emp_id'];
    $link_up = $_POST['link'];
    $social_id_up = $_POST['social_id'];
    $socquery = mysqli_query($conDB, "SELECT * FROM `social` WHERE `emp_id`='".$emp_id_up."' AND `social_id`='".$social_id_up."' ");
    if(mysqli_num_rows($socquery) == 0){
        $query="INSERT INTO `social` (`emp_id`,`s_link`, `social_id`, `created_at`) VALUES ('".$emp_id_up."', '".$link_up."', '".$social_id_up."', '".date('Y-m-d H:i:s')."')";
        if(mysqli_query($conDB, $query)){
            send_json_response("Success!", "This social link has been added successfully.", "success");
        } else {
            send_json_response("Error!", "User not updated because there are some error.", "error");
        }
    } else {
        send_json_response("Error!", "This Social Media already exist.", "error");

    }
} elseif($ajaxType == 'social_links'){
    $stmt = mysqli_query($conDB, "SELECT * FROM `social_list` WHERE `id` NOT IN (
            SELECT `social_list`.`id` FROM `social_list`
            LEFT JOIN `social` ON `social`.`social_id` = `social_list`.`id`
            WHERE `social`.`emp_id`='".$_POST['emp_id']."'
        )"
    );
    while($row = mysqli_fetch_assoc($stmt)) {
        $section_name[] = $row;
    }
    $data = [
        'data'      => $section_name,
        'status'    => 200
    ];
    echo json_encode($data);
} elseif($ajaxType == 'add_portfolio'){
    $emp_id = $_POST['emp_id'];
    $title_up = $_POST['title'];
    $description_up = mysqli_real_escape_string($conDB, $_POST['description']);
    if (file_exists($_FILES['file']['tmp_name']) || is_uploaded_file($_FILES['file']['tmp_name'])) {
        $uploadDir = "./../../assets/emp_documents/";
        $fileName = basename($_FILES['file']['name']);
        $tmp_name = $_FILES['file']['tmp_name'];
        $rand = rand(0000,9999).time();
        $file_ext = explode('.',$fileName);
        $file_ext_count=count($file_ext);
        $cnt=$file_ext_count-1;
        $file_extension= $file_ext[$cnt];
        $filename_po = $id.strtoupper($title_up).$rand.".".$file_extension;
        $uploadFilePath = $uploadDir.$filename_po; 
        move_uploaded_file($tmp_name, $uploadFilePath);
    }
    $sql="INSERT INTO `portfolio` (`emp_id`, `title`, `description`, `attachment`, `created_at`) VALUES ('".$emp_id."', '".$title_up."', '".$description_up."', '".$filename_po."', '".date('Y-m-d H:i:s')."')";
    if(mysqli_query($conDB, $sql)){
        send_json_response("Success!", "This portfoilo has been added successfully.", "success");
    } else {
        send_json_response("Error!", "Record not added because there are some error.", "error");
    }
} elseif($ajaxType == 'id_iqama_update'){
    try{
        // BEFORE these lines can even run, or in the file you are including.
        ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);
        error_reporting(E_ALL);
        // --- END DEBUGGING BLOCK ---
        include("./../../includes/Hijri_GregorianConvert.php");
        $DateConv = new Hijri_GregorianConvert;
        $format="YYYY-MM-DD";
        if ($_POST['iqama_exp']) {
            $iqama_exp = mysqli_real_escape_string($conDB, $_POST['iqama_exp']);
            $iqama_exp_gup = $DateConv->HijriToGregorian($iqama_exp, $format);
            $iqama_exp_g = date("Y-m-d", strtotime($iqama_exp_gup));
        } else{
            $iqama_exp_g = mysqli_real_escape_string($conDB, $_POST['iqama_exp_g']);
            $iqama_exp = $DateConv->GregorianToHijri($iqama_exp_g, $format);
        }
        $stmt = $pdo->prepare("UPDATE `employees` SET `iqama_exp` = :iqama_exp, `iqama_exp_g` = :iqama_exp_g WHERE `id` = :id");
        $stmt->execute([':iqama_exp' => $iqama_exp, ':iqama_exp_g' => $iqama_exp_g, ':id' => $_POST['id']]);
        if($stmt->rowCount() > 0){
            send_json_response("Updated!", "This record has been update successfully.", "success");
        } else {
            send_json_response("Error!", "Record not updated because there are some error.", "error");
        }
    } catch(Exception $e) {
        send_json_response("Database Error", "The catch block is working. The error was: " . $e->getMessage(), "error");
    }
} elseif($ajaxType == 'emp_doc_type'){
    $stmt = mysqli_query($conDB, "SELECT * FROM `docu_type` ORDER BY `duc_type` REGEXP '^[^A-Za-z]' ASC, `duc_type`");
    while($row = mysqli_fetch_assoc($stmt)) {
        $sub_type[] = $row;
    }
    $data = [
        'data'      => $sub_type,
        'status'    => 200
    ];
    echo json_encode($data);
} elseif($ajaxType == 'add_emp_document'){
    try {
        // Validate required inputs
        if (!isset($_POST['id'], $_POST['docu_typ'], $_POST['emp_id'], $_POST['emptype'])) {
            throw new Exception('Missing required parameters');
        }
        // Sanitize inputs
        $id = filter_var($_POST['id'], FILTER_SANITIZE_NUMBER_INT);
        $docu_typ_up = $_POST['docu_typ'];
        $emp_id_up = filter_var($_POST['emp_id'], FILTER_SANITIZE_NUMBER_INT);
        $emptype = $_POST['emptype'];
        // File upload handling
        if (!isset($_FILES['file']) || !is_uploaded_file($_FILES['file']['tmp_name'])) {
            throw new Exception('No file uploaded or upload error');
        }
        $uploadDir = "./../../assets/emp_documents/";
        $filepathup = "./assets/emp_documents/";
        $fileName = basename($_FILES['file']['name']);
        $tmp_name = $_FILES['file']['tmp_name'];
        // Validate file extension
        $file_ext = pathinfo($fileName, PATHINFO_EXTENSION);
        $allowed_extensions = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'];
        if (!in_array(strtolower($file_ext), $allowed_extensions)) {
            throw new Exception('Invalid file type. Allowed types: ' . implode(', ', $allowed_extensions));
        }
        // Generate unique filename
        $rand = rand(0000, 9999) . time();
        $filename_po = $id . strtoupper($docu_typ_up) . $rand . "." . $file_ext;
        $uploadFilePath = $uploadDir . $filename_po;

        // Move uploaded file
        if (!move_uploaded_file($tmp_name, $uploadFilePath)) {
            throw new Exception('Failed to move uploaded file');
        }
        // Begin transaction for multiple database operations
        $pdo->beginTransaction();
        if ($emptype == 'employee') {
            // Insert into employee_temp_contants
            $stmt1 = $pdo->prepare("INSERT INTO `employee_temp_contants` (`emp_id`, `type`, `path`) VALUES (:emp_id, 'Employee Documents', :filepath)");
            $stmt1->execute([':emp_id' => $emp_id_up, ':filepath' => $filepathup . $filename_po ]);
            // Insert into emp_docu with status 'I'
            $stmt2 = $pdo->prepare("INSERT INTO `emp_docu` (`emp_id`, `docu_typ`, `path`, `docu_ext`, `pgid`, `status`) VALUES (:emp_id, :docu_typ, :filename, :ext, :pgid, 'I')");
            $stmt2->execute([':emp_id' => $emp_id_up, ':docu_typ' => $docu_typ_up,':filename' => $filename_po,':ext' => $file_ext,':pgid' => $id]);
        } else {
            // Insert into emp_docu without status
            $stmt = $pdo->prepare("INSERT INTO `emp_docu` (`emp_id`, `docu_typ`, `path`, `docu_ext`, `pgid`) VALUES (:emp_id, :docu_typ, :filename, :ext, :pgid)");
            $stmt->execute([':emp_id' => $emp_id_up,':docu_typ' => $docu_typ_up,':filename' => $filename_po,':ext' => $file_ext,':pgid' => $id]);
        }
        // Commit transaction if all queries succeeded
        $pdo->commit();
        send_json_response("Added!", "Record has been added successfully.", "success");
    } catch (PDOException $e) {
        // Rollback transaction on error
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        // Delete uploaded file if database operation failed
        if (isset($uploadFilePath) && file_exists($uploadFilePath)) {
            unlink($uploadFilePath);
        }
        send_json_response("Database Error", "Failed to add record: " . $e->getMessage(), "error");
    } catch (Exception $e) {
        // Delete uploaded file if validation failed
        if (isset($uploadFilePath) && file_exists($uploadFilePath)) {
            unlink($uploadFilePath);
        }
        send_json_response("Error", $e->getMessage(), "error");
    }
} elseif($ajaxType == 'emp_temp_contannt'){
    $ckh = "SELECT * FROM `employee_temp_contants` WHERE `status` = 'A' AND `emp_id` = '".$_POST['empid']."' AND `id` = '".$_POST['id']."' ";
    // "INSERT INTO `employee_temp_contants` (`emp_id`,`type`,`path`) SELECT `emp_id`,`iqama`,`mobile` FROM `employees` WHERE `emp_id` = '152'"
    $datackh = mysqli_fetch_assoc(mysqli_query($conDB, $ckh));
    if ($_POST['notes'] == 'approve') {
        if ($datackh['type'] == 'Profile Picture' ) {
            mysqli_query($conDB, "UPDATE `employees` SET `avatar`='".$datackh['path']."' WHERE `emp_id`='".$_POST['empid']."' ");
            mysqli_query($conDB, "UPDATE `employee_temp_contants` SET `status`='I', `notes` = 'approve' WHERE `emp_id`='".$_POST['empid']."' AND `id` = '".$_POST['id']."' ");
            send_json_response("Approved!", "Record has been approve successfully.", "success");
        } elseif($datackh['type'] == 'Employee Documents'){
            mysqli_query($conDB, "UPDATE `emp_docu` SET `status`='A' WHERE `emp_id`='".$_POST['empid']."' AND '".$_POST['id']."' ");
            mysqli_query($conDB, "UPDATE `employee_temp_contants` SET `status`='I', `notes` = 'approve' WHERE `emp_id`='".$_POST['empid']."' AND `id` = '".$_POST['id']."' ");
            send_json_response("Approved!", "Record has been approve successfully.", "success");
        } else {
            mysqli_query($conDB, "UPDATE `employees` SET `$_POST[type]` ='".$_POST['path']."' WHERE `emp_id`='".$_POST['empid']."'");
            mysqli_query($conDB, "UPDATE `employee_temp_contants` SET `status`='I', `notes` = 'approve' WHERE `emp_id`='".$_POST['empid']."' AND `id` = '".$_POST['id']."' ");
            send_json_response("Approved!", "Record has been approve successfully.", "success");
        }
    } else {
        mysqli_query($conDB, "UPDATE `employee_temp_contants` SET `status`='I', `notes` = '".$_POST['notes']."' WHERE `emp_id`='".$_POST['empid']."' AND `id` = '".$_POST['id']."' ");
        send_json_response("Rejected!", "Record not approve.", "error");
    }
}elseif ($ajaxType == "bank_list") {
    $stmt = mysqli_query($conDB, "SELECT * FROM `bank_list` ORDER BY `name` REGEXP '^[^A-Za-z]' ASC, `name`");
    while($row = mysqli_fetch_assoc($stmt)) {
        $name[] = $row;
    }
    $data = [
        'data'      => $name,
        'status'    => 200
    ];
    echo json_encode($data);  
}elseif ($ajaxType == "emp_edit_contannt") {
    $sql = "INSERT INTO `employee_temp_contants` (`emp_id`, `type`, `path`) VALUES ('".$_POST['empid']."', '".$_POST['edit_contant_check']."', '".$_POST[$_POST['edit_contant_check']]."')";
    if(mysqli_query($conDB, $sql)){
         send_json_response("Added!", "Record has been added successfully.", "success");
    } else {
        send_json_response("Error!", "Record not added because there are some error.", "error");
    }
} elseif ($ajaxType == "add_note") {
    $stmt = $pdo->prepare("INSERT INTO `emp_notice` (`emp_id`, `note`, `created_at`) VALUES (:emp_id, :note, :created_at)");
    $dataPost = [
        ':emp_id' => $_POST['empid'],
        ':note' => $_POST['note'],
        ':created_at' => date('Y-m-d H:i:s')
    ];
    if($stmt->execute($dataPost)){
        send_json_response("Added!", "Record has been added successfully.", "success");
    } else {
        send_json_response("Error!", "Record not added because there are some error.", "error");
    }
} elseif($ajaxType == "view_notes"){
    // Use INNER JOIN to ensure only employees with notes are returned.
    $sql = "SELECT
                `n`.`id`, `n`.`note`, `n`.`status`, `n`.`created_at`,
                `e`.`name`, `e`.`emp_id`
            FROM `employees` `e`
            INNER JOIN `emp_notice` `n` ON `e`.`emp_id` = `n`.`emp_id` AND `n`.`is_deleted` = 0
            WHERE `e`.`emp_id` = :emp_id
            ORDER BY `n`.`created_at` DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':emp_id', $_POST['emp_id'], PDO::PARAM_INT); // It's better to use PDO::PARAM_INT for IDs
    $stmt->execute();
    $notes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    // This will now correctly return an empty 'notes' array if no records are found
    echo json_encode(['status' => 'success', 'notes' => $notes]);
    exit; // Good practice to exit after an AJAX response
} elseif(isset($ajaxType) && $ajaxType == 'emp_temp_contant'){
    header('Content-Type: application/json');
    $requestId = $_POST['id'];
    $empId = $_POST['empid'];
    $approvalAction = $_POST['contant_check']; // 'approve' or 'not_approve'
    $notes = isset($_POST['notes']) ? trim($_POST['notes']) : '';
    // --- If the request is APPROVED ---
    if ($approvalAction == 'approve') {
        try {
            // 1. Fetch the request details from the temp table
            $stmt = $pdo->prepare("SELECT type, new_value, path FROM employee_temp_contants WHERE id = ? AND emp_id = ?");
            $stmt->execute([$requestId, $empId]);
            $request = $stmt->fetch();

            if (!$request) {
                echo json_encode(['type' => 'error', 'title' => 'Not Found', 'message' => 'The original request could not be found.']);
                exit;
            }
            // 2. Determine which column to update in the main 'employees' table
            // This section is now updated to match your 'employees' table schema.emp_temp_contant
            $updateField = '';
            switch ($request['type']) {
                case 'Mobile':          $updateField = 'mobile'; break;
                case 'Email':           $updateField = 'email'; break;
                case 'Passport No':     $updateField = 'passport_number'; break;
                case 'Passport Exp':    $updateField = 'passport_exp'; break;
                case 'Address':         $updateField = 'address'; break;
                case 'Profile Picture': $updateField = 'avatar'; break;
                // NOTE: 'Employee Documents' case was removed as there is no matching column in your 'employees' table.
                // If you have a column for general document paths, add a case for it here.
            }
            // Use the path for file-based updates, otherwise use new_value
            $updateValue = ($request['path']) ? $request['path'] : $request['new_value'];
            // 3. Update the main employees table if a valid field was found
            if (!empty($updateField)) {
                // IMPORTANT: The query now uses `emp_id` as the WHERE clause key.
                $updateStmt = $pdo->prepare("UPDATE `employees` SET {$updateField} = ? WHERE emp_id = ?");
                $updateStmt->execute([$updateValue, $empId]);
            }
            // 4. Update the status of the temp request to 'Approved'
            $finalStmt = $pdo->prepare("UPDATE employee_temp_contants SET status = 'Approved', notes = ? WHERE id = ?");
            $finalStmt->execute([$notes, $requestId]);
            echo json_encode(['type' => 'success', 'title' => 'Approved!', 'message' => 'Employee information has been successfully updated.']);
        } catch (PDOException $e) {
            // In a real app, log the error
            // For debugging, you can output the error message:
            // echo json_encode(['type' => 'error', 'title' => 'Database Error', 'message' => $e->getMessage()]);
            echo json_encode(['type' => 'error', 'title' => 'Database Error', 'message' => 'An error occurred while updating the data.']);
        }
    // --- If the request is REJECTED ---
    } elseif ($approvalAction == 'not_approve') {
        try {
            // Just update the status to 'Rejected' and add the reason
            $finalStmt = $pdo->prepare("UPDATE employee_temp_contants SET status = 'Rejected', notes = ? WHERE id = ?");
            $finalStmt->execute([$notes, $requestId]);
            echo json_encode(['type' => 'success', 'title' => 'Rejected', 'message' => 'The update request has been rejected.']);
        } catch (PDOException $e) {
            echo json_encode(['type' => 'error', 'title' => 'Database Error', 'message' => 'An error occurred while updating the request status.']);
        }
    } else {
        echo json_encode(['type' => 'error', 'title' => 'Invalid Action', 'message' => 'No valid action was submitted.']);
    }
    exit;
} elseif(isset($ajaxType) && $ajaxType == 'create_update_request'){
    // Set the response header to JSON
    header('Content-Type: application/json');

    // Sanitize and retrieve POST data
    $empId = isset($_POST['emp_id']) ? $_POST['emp_id'] : null;
    $type = isset($_POST['type']) ? $_POST['type'] : null;
    $newValue = isset($_POST['new_value']) ? trim($_POST['new_value']) : null;
    $path = null;

    // Basic Validation
    if (empty($empId) || empty($type)) {
        echo json_encode(['type' => 'error', 'title' => 'Missing Information', 'message' => 'Employee ID or request type is missing.']);
        exit;
    }

    // --- Handle base64 image upload from Croppie ---
    if (isset($_POST['image_base64'])) {
        $data = $_POST['image_base64'];
        // Basic check for base64 string
        if (preg_match('/^data:image\/(\w+);base64,/', $data, $type_match)) {
            $data = substr($data, strpos($data, ',') + 1);
            $image_type = strtolower($type_match[1]); // jpg, png, gif

            $data = base64_decode($data);
            if ($data === false) {
                echo json_encode(['type' => 'error', 'title' => 'Upload Failed', 'message' => 'Base64 decode failed.']);
                exit;
            }

            $uploadDir = './../../assets/emp_pics/emp_' . $empId . '/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            $fileName = 'avatar_' . time() . '.' . $image_type;
            $targetPath = $uploadDir . $fileName;

            if (file_put_contents($targetPath, $data)) {
                $path = $targetPath;
            } else {
                echo json_encode(['type' => 'error', 'title' => 'Upload Failed', 'message' => 'Could not save the cropped image.']);
                exit;
            }
        } else {
            echo json_encode(['type' => 'error', 'title' => 'Invalid Image', 'message' => 'The provided image data was not in a valid format.']);
            exit;
        }
    }
    // --- Handle standard file uploads (for other document types in the future) ---
    else if (isset($_FILES['file']) && $_FILES['file']['error'] == UPLOAD_ERR_OK) {
        $uploadDir = './../../assets/emp_pics/emp_' . $empId . '/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        $fileExtension = pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION);
        $fileName = time() . '_' . uniqid() . '.' . $fileExtension;
        $targetPath = $uploadDir . $fileName;

        if (move_uploaded_file($_FILES['file']['tmp_name'], $targetPath)) {
            $path = $targetPath;
        } else {
            echo json_encode(['type' => 'error', 'title' => 'Upload Failed', 'message' => 'Server could not save the uploaded file.']);
            exit;
        }
    }

    // Final check: ensure a value or a path was provided
    if (empty($newValue) && empty($path)) {
        echo json_encode(['type' => 'error', 'title' => 'Invalid Input', 'message' => 'Please provide a new value or select a file to submit.']);
        exit;
    }

    try {
        // Insert the request into the temporary table for HR approval
        $sql = "INSERT INTO employee_temp_contants (emp_id, type, new_value, path, status, created_at) VALUES (?, ?, ?, ?, 'Pending', NOW())";
        $stmt = mysqli_prepare($conDB, $sql);
        mysqli_stmt_bind_param($stmt, 'isss', $empId, $type, $newValue, $path);
        mysqli_stmt_execute($stmt);

        // Send a success response back to the browser
        echo json_encode([
            'type' => 'success',
            'title' => 'Request Submitted!',
            'message' => 'Your request to update your ' . strtolower($type) . ' has been sent to HR for approval.'
        ]);

    } catch (Exception $e) {
        echo json_encode(['type' => 'error', 'title' => 'Database Error', 'message' => 'Could not submit your request at this time.']);
    }
    // IMPORTANT: Stop script execution after handling the AJAX request
    exit;
}

?>
