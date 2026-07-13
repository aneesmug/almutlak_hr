<?php
	require_once __DIR__ . '/../../includes/db.php';
	require_once __DIR__ . '/../../includes/session_check.php';
	include("./../../includes/helper_functions.php");

$ajaxType = $_POST['ajaxType'];

if($ajaxType == 'sub_type') {
    $stmt = mysqli_query($conDB, "SELECT * FROM `smt_subject_type` ORDER BY `sub_type` REGEXP '^[^A-Za-z]' ASC, `sub_type` ");
    while($row = mysqli_fetch_assoc($stmt)) {
        $sub_type[] = $row;
    }
    $data = [
        'data'      => $sub_type,
        'status'    => 200
    ];
    echo json_encode($data);
} elseif($ajaxType == 'request_update') {
    try{
        ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);
        error_reporting(E_ALL);

        $request_date = $_POST['request_date'] ?? '';
        $parsed_request_date = DateTime::createFromFormat('Y-m-d', $request_date);
        if (!$parsed_request_date || $parsed_request_date->format('Y-m-d') !== $request_date) {
            send_json_response("Invalid Date", "Please select a valid request date.", "error");
        }
        
        // Fetch old values before update
        $fetch_stmt = $pdo->prepare("SELECT * FROM `smart_request` WHERE `inv_no` = :reqid ORDER BY `created_at` DESC, `id` DESC LIMIT 1");
        $fetch_stmt->execute([':reqid' => $_POST['reqid']]);
        $old_request = $fetch_stmt->fetch(PDO::FETCH_ASSOC);

        $tally_id = $_POST['tally_id'] ?? ($old_request['tally_id'] ?? null);
        $injazat_id = $_POST['injazat_id'] ?? ($old_request['injazat_id'] ?? null);
        $remarks = $_POST['remarks'] ?? '';
        
        $stmt = $pdo->prepare("UPDATE `smart_request` SET `sub_type`=:sub_type_up, `sub_title`=:sub_title_up, `tally_id`=:tally_id_up, `injazat_id`=:injazat_id_up, `remarks`=:remarks_up, `created_at`=CONCAT(:request_date_up, ' ', TIME(`created_at`)) WHERE `inv_no`=:reqid ");
        $stmt->execute([
            ':sub_type_up' => $_POST['sub_type'], 
            ':sub_title_up' => mysqli_real_escape_string($conDB, $_POST['sub_title']), 
            ':tally_id_up' => $tally_id !== null ? mysqli_real_escape_string($conDB, $tally_id) : null, 
            ':injazat_id_up' => $injazat_id !== null ? mysqli_real_escape_string($conDB, $injazat_id) : null, 
            ':remarks_up' => mysqli_real_escape_string($conDB, $remarks), 
            ':request_date_up' => $request_date,
            ':reqid' => $_POST['reqid'],
        ]);
        if($stmt->rowCount() > 0){
            // Log request update via AJAX
            ActivityLogger::logUpdate('Request', 'ajaxSmartRequest.php', $old_request['id'], $old_request, [
                'sub_type' => $_POST['sub_type'],
                'sub_title' => $_POST['sub_title'],
                'tally_id' => $tally_id,
                'injazat_id' => $injazat_id,
                'remarks' => $remarks,
                'created_at' => $request_date
            ], "Updated request via AJAX: {$_POST['reqid']}", 'smart_request');
            
            send_json_response("Updated!", "This request has been update successfully.", "success");
        } else {
            send_json_response("Error!", "Record not updated because there are some error.", "error");
        }
    } catch(Exception $e) {
        send_json_response("Database Error", "The catch block is working. The error was: " . $e->getMessage(), "error");
    }
} elseif($ajaxType == 'request_line_update'){
    try {
        ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);
        error_reporting(E_ALL);
        
        // Fetch old line values
        $fetch_stmt = $pdo->prepare("SELECT * FROM `smart_request` WHERE `id` = :itemid");
        $fetch_stmt->execute([':itemid' => $_POST['itemid']]);
        $old_line = $fetch_stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$old_line) {
            send_json_response("Error", "Record with ID " . $_POST['itemid'] . " not found.", "error");
            return;
        }
        
        $stmt = $pdo->prepare("UPDATE `smart_request` SET `item_name` = :item_name, `reference` = :reference, `location` = :location, `quantity` = :quantity, `product_price` = :product_price, `itmvalue` = :itmvalue, `vat_rate` = :vat_rate, `vat_val` = :vat_val, `amount` = :amount, `idiscount` = :idiscount, `total_cost` = :total_cost 
                WHERE `id` = :itemid");
        $result = $stmt->execute([
            ':item_name'     => $_POST['item_name'],
            ':reference'     => $_POST['reference'] ?? '',
            ':location'      => $_POST['location'],
            ':quantity'      => $_POST['quantity'],
            ':product_price' => $_POST['product_price'],
            ':itmvalue'      => $_POST['itmvalue'],
            ':vat_rate'      => $_POST['vat_rate'],
            ':vat_val'       => $_POST['vat_val'],
            ':amount'        => $_POST['amount'],
            ':idiscount'     => $_POST['idiscount'],
            ':total_cost'    => $_POST['total_cost'],
            ':itemid'        => $_POST['itemid']
        ]);
        
        // Log request line update (always log after successful execution)
        ActivityLogger::logUpdate('Request', 'ajaxSmartRequest.php', $_POST['itemid'], $old_line, [
            'item_name' => $_POST['item_name'],
            'reference' => $_POST['reference'] ?? '',
            'location' => $_POST['location'],
            'quantity' => $_POST['quantity'],
            'product_price' => $_POST['product_price'],
            'total_cost' => $_POST['total_cost']
        ], "Updated request line item via AJAX", 'smart_request');

        send_json_response("Updated!", "This line has been updated successfully.", "success");
    } catch (PDOException $e) {
        send_json_response("Database Error", "The catch block is working. The error was: " . $e->getMessage(), "error");
    }
} elseif($ajaxType == 'smt_attachments'){
    // File path configuration 
    $getinv_no = $_POST['id'];
    $uploadDir = "./../../assets/smt_attachment/"; 
    $fileName = basename($_FILES['file']['name']);
    $tmp_name = $_FILES['file']['tmp_name'];
    $rand = md5($fileName);
    $file_ext = explode('.',$fileName);
    $file_ext_count=count($file_ext);
    $cnt=$file_ext_count-1;
    $file_extension= $file_ext[$cnt];
    $filename_po = $getinv_no."_".$rand.".".$file_extension;
    $uploadFilePath = $uploadDir.$filename_po;    
    // Upload file to server 
    if(move_uploaded_file($tmp_name, $uploadFilePath)){ 
        // Insert file information in the database 
        $sql = "INSERT INTO `smt_attachment` (`inv_no`, `attachment`, `docu_ext`) VALUES ('".$getinv_no."', '".$filename_po."', '".$file_extension."')"; 
        mysqli_query($conDB, $sql);
        $att_id = mysqli_insert_id($conDB);
        
        // Log file upload for request
        ActivityLogger::logUpload('Request', 'ajaxSmartRequest.php', $att_id, [
            'inv_no' => $getinv_no,
            'attachment' => $filename_po,
            'file_ext' => $file_extension
        ], "Uploaded attachment for request: {$getinv_no}", 'smt_attachment');
    }

}

?>