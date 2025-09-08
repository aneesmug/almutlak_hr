<?php
	require_once __DIR__ . '/../../includes/db.php';

    $acc_no = $_POST['acc_no'];
    $amount = $_POST['amount'];
    $chq_no = $_POST['chq_no'];
    $emp_v_user = $_POST['emp_v_user'];
    $empid = $_POST['empid'];
    $voucher_type = $_POST['voucher_type'];
    $details = mysqli_real_escape_string($conDB, $_POST['details']);
    
    $voucher_no = strtoupper(str_split($voucher_type)[0])."".$empid."".date('ymdis');

    $query_empqrny = mysqli_query($conDB, "SELECT * FROM `employees` WHERE `emp_id`='".$empid."' ORDER BY `id` DESC LIMIT 1");
    while ($rec = mysqli_fetch_array($query_empqrny)) {
        $dept = $rec["dept"];
    }

    if (file_exists($_FILES['file']['tmp_name']) || is_uploaded_file($_FILES['file']['tmp_name'])) {
        $uploadDir = "./../../assets/voucher_documents/";
        $fileName = basename($_FILES['file']['name']);
        $tmp_name = $_FILES['file']['tmp_name'];
        $rand = rand(0000,9999).time();
        $file_ext = explode('.',$fileName);
        $file_ext_count=count($file_ext);
        $cnt=$file_ext_count-1;
        $file_extension= $file_ext[$cnt];
        $filename_po = $id.strtoupper($doc_type_up).$rand.".".$file_extension;
        $uploadFilePath = $uploadDir.$filename_po; 
        move_uploaded_file($tmp_name, $uploadFilePath);
    }

    $sql="INSERT INTO `vouchers`(`emp_id`, `to_emp`,`voucher_no`, `voucher_type`, `voucher_amount`, `details`, `acc_no`, `chq_no`, `dept`, `file`) VALUES ('$empid','$emp_v_user','$voucher_no','$voucher_type','$amount','$details','$acc_no','$chq_no','$dept','$filename_po')";

    if(mysqli_query($conDB, $sql)){
    	$data = [
            'title'   => "Added!",
            'message' => "This voucher details has been added successfully.",
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