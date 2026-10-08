<?php
	require_once __DIR__ . '/../../includes/db.php';
    require_once __DIR__ . '/../FileUploader.php';
	require_once __DIR__ . '/../../includes/session_check.php';
	include("./../../includes/helper_functions.php");

$ajaxType = $_POST['ajaxType'];

/**
 * Reads the line arrays posted by the shared line editor (assets/js/smart_request_lines.js)
 * and recalculates every amount server-side from quantity / unit cost / VAT option / item
 * discount. Returns PDO params per line, or an error message string when a line is invalid.
 */
function smart_request_lines_from_post($vat_setting) {
    $item_names = (array)($_POST['item_name'] ?? []);
    if (count($item_names) === 0) {
        return __('fill_required_fields_validation', 'Please fill out the form!');
    }
    $lines = [];
    foreach (array_values($item_names) as $i => $item_name) {
        $item_name = trim((string)$item_name);
        $location = trim((string)($_POST['location'][$i] ?? ''));
        $qty = (float)($_POST['quantity'][$i] ?? 0);
        $price = (float)($_POST['product_price'][$i] ?? 0);
        $idiscount = (float)($_POST['idiscount'][$i] ?? 0);
        $vat_option = (string)($_POST['vat_option'][$i] ?? 'exclude');

        if ($item_name === '' || $location === '' || $qty <= 0 || $price < 0 || $idiscount < 0) {
            return __('fill_required_fields_validation', 'Please fill out the form!') . ' (#' . ($i + 1) . ')';
        }

        $vat_rate = $vat_option === 'no_vat' ? 0 : $vat_setting;
        $gross = $qty * $price;
        if ($vat_option === 'include') {
            $amount = $gross;
            $itmvalue = $gross / (1 + $vat_rate / 100);
            $vat_val = $amount - $itmvalue;
        } else {
            $itmvalue = $gross;
            $vat_val = $itmvalue * $vat_rate / 100;
            $amount = $itmvalue + $vat_val;
        }

        $lines[] = [
            ':item_name'     => $item_name,
            ':reference'     => trim((string)($_POST['reference'][$i] ?? '')),
            ':location'      => $location,
            ':quantity'      => $qty,
            ':product_price' => round($price, 2),
            ':itmvalue'      => round($itmvalue, 2),
            ':vat_rate'      => $vat_rate,
            ':vat_val'       => round($vat_val, 2),
            ':amount'        => round($amount, 2),
            ':idiscount'     => round($idiscount, 2),
            ':total_cost'    => round($amount - $idiscount, 2),
        ];
    }
    return $lines;
}


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
} elseif($ajaxType == 'request_create'){
    // New Request modal (all_requests.php). Creates the request as a draft with its lines,
    // then the page opens it so the creator can attach files and pick approvers.
    // Same rules as new_request.php: request-type block + supervisor must be assigned.
    try {
        require_once __DIR__ . '/../../includes/validate_supervisor.php';
        require_once __DIR__ . '/../../includes/special_access_helper.php';

        $block_status = is_employee_request_blocked($conDB, $empid, 'smart_request');
        if ($block_status['blocked']) {
            send_json_response("Error!", $block_status['reason'], "error");
        }
        $supervisor_validation = validate_employee_supervisor($conDB, $empid);
        if (!$supervisor_validation['valid']) {
            send_json_response("Error!", strip_tags($supervisor_validation['message']), "error");
        }

        $sub_title = trim((string)($_POST['sub_title'] ?? ''));
        $sub_type = trim((string)($_POST['sub_type'] ?? ''));
        $remarks = trim((string)($_POST['remarks'] ?? ''));
        $discount = (float)($_POST['discount'] ?? 0);
        if ($sub_title === '' || $sub_type === '' || $discount < 0) {
            send_json_response("Error!", __('fill_required_fields_validation', 'Please fill out the form!'), "error");
        }
        $type_check = $pdo->prepare("SELECT 1 FROM `smt_subject_type` WHERE `sub_type` = :sub_type LIMIT 1");
        $type_check->execute([':sub_type' => $sub_type]);
        if (!$type_check->fetchColumn()) {
            send_json_response("Error!", __('fill_required_fields_validation', 'Please fill out the form!'), "error");
        }

        $lines = smart_request_lines_from_post((float)get_setting($conDB, 'vat'));
        if (is_string($lines)) {
            send_json_response("Error!", $lines, "error");
        }

        // Same number format as before (SMT + emp id + ymdis); bump the seconds part if taken.
        $exists = $pdo->prepare("SELECT 1 FROM `smart_request` WHERE `inv_no` = :inv_no LIMIT 1");
        $ts = time();
        do {
            $inv_no = "SMT" . $empid . date('ymdis', $ts++);
            $exists->execute([':inv_no' => $inv_no]);
        } while ($exists->fetchColumn());

        $insert = $pdo->prepare("INSERT INTO `smart_request`
            (`inv_no`, `location`, `sub_type`, `sub_title`, `item_name`, `reference`, `quantity`, `product_price`, `itmvalue`, `vat_rate`, `vat_val`, `amount`, `idiscount`, `total_cost`, `discount`, `department`, `prep_by`, `remarks`, `emp_id`, `submitted_by_emp_id`, `current_status`, `current_approval_level`)
            VALUES
            (:inv_no, :location, :sub_type, :sub_title, :item_name, :reference, :quantity, :product_price, :itmvalue, :vat_rate, :vat_val, :amount, :idiscount, :total_cost, :discount, :department, :prep_by, :remarks, :emp_id, :submitted_by_emp_id, 'draft', NULL)");

        $pdo->beginTransaction();
        $new_ids = [];
        foreach ($lines as $line) {
            $insert->execute($line + [
                ':inv_no'              => $inv_no,
                ':sub_type'            => $sub_type,
                ':sub_title'           => $sub_title,
                ':discount'            => round($discount, 2),
                ':department'          => $user_dept,
                ':prep_by'             => $userwel,
                ':remarks'             => $remarks,
                ':emp_id'              => (int)$empid,
                ':submitted_by_emp_id' => (int)$empid,
            ]);
            $new_ids[] = $pdo->lastInsertId();
        }
        $status_log = $pdo->prepare("INSERT INTO `smt_request_status` (`emp_id`, `inv_no`, `emp_name`, `status`) VALUES (:emp_id, :inv_no, :emp_name, 'draft')");
        $status_log->execute([':emp_id' => $empid, ':inv_no' => $inv_no, ':emp_name' => $userwel]);
        $pdo->commit();

        ActivityLogger::logCreate('Request', 'ajaxSmartRequest.php', $inv_no, [
            'inv_no'    => $inv_no,
            'sub_title' => $sub_title,
            'lines'     => count($lines),
            'ids'       => implode(',', $new_ids),
        ], "Created smart request {$inv_no} via AJAX", 'smart_request');

        send_json_response(__('success', 'Success') . "!", __('added_successfully', 'Added successfully.'), "success", 200, ['inv_no' => $inv_no]);
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        send_json_response("Database Error", "The error was: " . $e->getMessage(), "error");
    }
} elseif($ajaxType == 'request_line_add'){
    // Add one or more lines to a draft request (open_request.php > Add Line modal).
    // Only the creator, while the request is still a draft. Amounts are recalculated here
    // from quantity / unit cost / VAT option / item discount instead of trusting the client.
    try {
        require_once __DIR__ . '/../../includes/validate_supervisor.php';

        $inv_no = trim((string)($_POST['inv_no'] ?? ''));
        $head_stmt = $pdo->prepare("SELECT * FROM `smart_request` WHERE `inv_no` = :inv_no ORDER BY (`emp_id` > 0) DESC, `id` ASC LIMIT 1");
        $head_stmt->execute([':inv_no' => $inv_no]);
        $head = $head_stmt->fetch(PDO::FETCH_ASSOC);

        if (!$head) {
            send_json_response("Error!", __('error_request_not_found', 'The requested item was not found or the link is invalid.'), "error");
        }
        if ($head['current_status'] !== 'draft' || (int)$head['emp_id'] !== (int)$empid) {
            send_json_response("Error!", __('add_line_draft_only', 'Lines can only be added by the creator while the request is a draft.'), "error");
        }
        $supervisor_validation = validate_employee_supervisor($conDB, $empid);
        if (!$supervisor_validation['valid']) {
            send_json_response("Error!", strip_tags($supervisor_validation['message']), "error");
        }

        $lines = smart_request_lines_from_post((float)get_setting($conDB, 'vat'));
        if (is_string($lines)) {
            send_json_response("Error!", $lines, "error");
        }

        // Request-level fields are copied from the existing request so every row stays consistent.
        $insert = $pdo->prepare("INSERT INTO `smart_request`
            (`inv_no`, `tally_id`, `injazat_id`, `location`, `sub_type`, `sub_title`, `remarks`, `item_name`, `reference`, `quantity`, `product_price`, `itmvalue`, `vat_rate`, `vat_val`, `amount`, `idiscount`, `total_cost`, `discount`, `department`, `prep_by`, `emp_id`, `submitted_by_emp_id`, `current_status`, `created_at`)
            VALUES
            (:inv_no, :tally_id, :injazat_id, :location, :sub_type, :sub_title, :remarks, :item_name, :reference, :quantity, :product_price, :itmvalue, :vat_rate, :vat_val, :amount, :idiscount, :total_cost, :discount, :department, :prep_by, :emp_id, :submitted_by_emp_id, 'draft', :created_at)");

        $pdo->beginTransaction();
        $new_ids = [];
        foreach ($lines as $line) {
            $insert->execute($line + [
                ':inv_no'              => $head['inv_no'],
                ':tally_id'            => trim((string)($_POST['tally_id'] ?? '')) !== '' ? trim((string)$_POST['tally_id']) : ($head['tally_id'] ?? ''),
                ':injazat_id'          => trim((string)($_POST['injazat_id'] ?? '')) !== '' ? trim((string)$_POST['injazat_id']) : ($head['injazat_id'] ?? ''),
                ':sub_type'            => $head['sub_type'],
                ':sub_title'           => $head['sub_title'],
                ':remarks'             => $head['remarks'],
                ':discount'            => $head['discount'] ?? 0,
                ':department'          => $head['department'],
                ':prep_by'             => $head['prep_by'],
                ':emp_id'              => $head['emp_id'],
                ':submitted_by_emp_id' => (int)$empid,
                ':created_at'          => $head['created_at'],
            ]);
            $new_ids[] = $pdo->lastInsertId();
        }
        $pdo->commit();

        ActivityLogger::logCreate('Request', 'ajaxSmartRequest.php', $head['inv_no'], [
            'inv_no' => $head['inv_no'],
            'lines'  => count($lines),
            'ids'    => implode(',', $new_ids),
        ], "Added " . count($lines) . " line(s) to smart request via AJAX", 'smart_request');

        send_json_response(__('success', 'Success') . "!", count($lines) . ' ' . __('lines_added_successfully', 'line(s) added successfully.'), "success");
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        send_json_response("Database Error", "The error was: " . $e->getMessage(), "error");
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
    $file_extension = (string)FileUploader::safeExt($fileName, 'document');
    $filename_po = $getinv_no."_".$rand.".".$file_extension;
    $uploadFilePath = $uploadDir.$filename_po;    
    // Upload file to server 
    if(FileUploader::moveTo($tmp_name, $uploadFilePath, 'document')){ 
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
    } else {
        http_response_code(422);
        echo FileUploader::lastError();
    }

}

?>