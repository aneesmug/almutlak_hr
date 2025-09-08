<?php
	require_once __DIR__ . '/../../includes/db.php';
	include("./../../includes/custom_functions.php");

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
        $stmt = $pdo->prepare("UPDATE `smart_request` SET `sub_type`=:sub_type_up, `sub_title`=:sub_title_up, `tally_id`=:tally_id_up, `injazat_id`=:injazat_id_up,`remarks`=:remarks_up WHERE `inv_no`=:reqid ");
        $stmt->execute([
            ':sub_type_up' => $_POST['sub_type'], 
            ':sub_title_up' => mysqli_real_escape_string($conDB, $_POST['sub_title']), 
            ':tally_id_up' => mysqli_real_escape_string($conDB, $_POST['tally_id']), 
            ':injazat_id_up' => mysqli_real_escape_string($conDB, $_POST['injazat_id']), 
            ':remarks_up' => mysqli_real_escape_string($conDB, $_POST['remarks']), 
            ':reqid' => $_POST['reqid'],
        ]);
        if($stmt->rowCount() > 0){
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
        $stmt = $pdo->prepare("UPDATE `smart_request` SET `item_name` = :item_name, `location` = :location, `quantity` = :quantity, `product_price` = :product_price, `itmvalue` = :itmvalue, `vat_rate` = :vat_rate, `vat_val` = :vat_val, `amount` = :amount, `idiscount` = :idiscount, `total_cost` = :total_cost 
                WHERE `id` = :itemid");
        $stmt->execute([
            ':item_name'     => $_POST['item_name'],
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
        if ($stmt->rowCount() > 0) {
            send_json_response("Updated!", "This line has been updated successfully.", "success");
        } else {
            send_json_response("No Changes", "The record was not found or the submitted data was identical.", "info");
        }
    } catch (PDOException $e) {
        send_json_response("Database Error", "The catch block is working. The error was: " . $e->getMessage(), "error");
    }
} elseif($ajaxType == 'smt_attachments'){
    // File path configuration 
    $getinv_no = $_POST['id'];
    // $getinv_no = $_GET['id'];
    $uploadDir = "./../../assets/smt_attachment/"; 
    $fileName = basename($_FILES['file']['name']);
    $tmp_name = $_FILES['file']['tmp_name'];
    // $rand = rand(0000,9999).time();
    // $rand = md5(microtime(true));
    $rand = md5($fileName);
    $file_ext = explode('.',$fileName);
    //count taken (if more than one . exist; files like abc.fff.2013.pdf
    $file_ext_count=count($file_ext);
    //minus 1 to make the offset correct
    $cnt=$file_ext_count-1;
    // the variable will have a value pdf as per the sample file name mentioned above.
    $file_extension= $file_ext[$cnt];
    /*$filename_po = $getinv_no."_".date('dmYHis')."_".$rand.".".$file_extension;*/
    $filename_po = $getinv_no."_".$rand.".".$file_extension;
    $uploadFilePath = $uploadDir.$filename_po;    
    // Upload file to server 
    if(move_uploaded_file($tmp_name, $uploadFilePath)){ 
        // Insert file information in the database 
        $sql = "INSERT INTO `smt_attachment` (`inv_no`, `attachment`, `docu_ext`) VALUES ('".$getinv_no."', '".$filename_po."', '".$file_extension."')"; 
        mysqli_query($conDB, $sql);
    }

}

?>