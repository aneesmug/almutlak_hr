<?php 
	require_once __DIR__ . '/../../includes/db.php';
	require_once __DIR__ . '/../../includes/session_check.php';

    $order_id = $_POST['id'];
    $emp_name = $_POST['emp_name'];
    $notes = $_POST['notes'];
    $status = $_POST['status'];
    $uid = $_POST['uid'];

    $sql="INSERT INTO `cart_order_status` (`order_id`, `emp_name`, `notes`, `status`, `uid`) VALUES ('".$order_id."', '".$emp_name."', '".$notes."', '".$status."', '".$uid."')";      

	if (mysqli_query($conDB, $sql)) {
		$status_id = mysqli_insert_id($conDB);
		
		// Log order status update
			ActivityLogger::logApproval('Orders', 'updateStatusOrderAjax.php', $order_id, $status, "Updated order status to: {$status}", 'cart_order_status');

        /*:::::::::::::::::Start Email Send:::::::::::::::::*/
        $variables["fullname"]          = $row['fullname'];
        $variables["orderid"]           = implode("-",str_split($order_id,4));
        $variables["order_id"]          = $order_id;
        $variables["status"]            = $status;
        $variables["notes"]             = $notes;
        $variables["street_name"]       = $row['street_name'];
        $variables["building_name"]     = $row['building_name'];
        $variables["others"]            = $row['others'];
        $variables["city"]              = $row['city'];
        if ($status == 'preparing') {
            $variables["aboutstatus"]       = 'Your order has been accepted and yet this in preparing stage.';
            $variables["titlestatus"]       = '';
            $variables["status"]            = 'Preparing Stage';
        } elseif($status == 'u_shipping'){
            $variables["aboutstatus"]       = 'Your order is on the way, and can no longer be changed.';
            $variables["titlestatus"]       = '<p> <span> Arriving: </span> <br> <b style="color:#009900;"> '.date("F, d Y",strtotime($notes)).' </b> </p>';
            $variables["status"]            = 'Order Shipped';
        }elseif ($status == 'complete') {
            $variables["aboutstatus"]       = 'Your package has been delivered!';
            $variables["titlestatus"]       = '<p> <span> Delivered to: </span> <br> <b style="color:#009900;"> Mr./Mrs./Miss '.$notes.' </b> </p>';
            $variables["status"]            = 'Order Delivered';
        }else{
            $variables["aboutstatus"]       = "We're writing to let you know that your order has been cancelled.";
            $variables["titlestatus"]       = '<p> <span style="color:#e00;font-weight:bold;"> Cancelled: </span><br> <span> Please contact with </span> <b><a href="mailto:sales@mochachino.co">Sales Department</a></b></p>';
            $variables["status"]            = 'Cancelled';
        }
        require './../../includes/PHPMailerMaster/PHPMailerAutoload.php';
        $mail = new PHPMailer;
        $mail->isSMTP();
        $mail->Debugoutput = 'html';
        $mail->Host = "sys.mochachino.store";
        $mail->SMTPAuth = true;                                 // Enable SMTP authentication 
        $mail->Username = 'req@sys.mochachino.store';           // SMTP username 
        $mail->Password = '@DmiN56539306';                      // SMTP password
        $mail->SMTPSecure = 'ssl';                              // Enable TLS encryption, `ssl` also accepted
        $mail->Port = 465;
        $mail->setFrom("noreply@mochachino.store", "Mochachino Store");
        $mail->addAddress($row['email'], $row['fullname']);
        $mail->Subject = "Your Mochachino.store order #".implode("-",str_split($order_id,4));
        $emailbody = file_get_contents("./../../includes/PHPMailerMaster/email_order_status.php");
        foreach ($variables as $key => $value){
            $emailbody = str_replace('{{ ' . $key . ' }}', $value, $emailbody);
        }
        $mail->isHTML(true);
        $mail->Body=$emailbody;
        $mail->send();
        /*:::::::::::::::::End Email Send:::::::::::::::::*/
        
        $data = [
            'title'   => "Updated!",
    		'message' => "Status Updated Successfully ...",
    		'type' 	  => 'success',
    	];
    	echo json_encode($data);
    }else {
    	$data = [
    		'title'   => "Error!",
    		'message' => "Unable to update this record ...",
    		'type' 	  => 'error',
    	];
        echo json_encode($data);
    }
    mysqli_close($conDB);
?>