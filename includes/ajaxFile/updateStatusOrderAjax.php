<?php 
	require_once __DIR__ . '/../../includes/db.php';

    $sql="INSERT INTO `cart_order_status` (`order_id`, `emp_name`, `notes`, `status`, `uid`) VALUES ('".$_POST['id']."', '".$_POST['emp_name']."', '".$_POST['notes']."', '".$_POST['status']."', '".$_POST['uid']."')";      

	if (mysqli_query($conDB, $sql)) {

        $stmt = mysqli_query($conDB, "SELECT `customer_access`.`fullname`,`customer_access`.`email`,`customer_cart_address`.`street_name`,`customer_cart_address`.`building_name`,`customer_cart_address`.`others`,`customer_cart_address`.`city` FROM `customer_access` LEFT JOIN `customer_cart_address` 
            ON `customer_cart_address`.`cust_id` = `customer_access`.`id` WHERE `customer_access`.`id`='".$_POST['uid']."' AND `customer_cart_address`.`default` = '1' ");
        $row = mysqli_fetch_assoc($stmt);

        /*:::::::::::::::::Start Email Send:::::::::::::::::*/
        $variables["fullname"]          = $row['fullname'];
        $variables["orderid"]           = implode("-",str_split($_POST['id'],4));
        $variables["order_id"]          = $_POST['id'];
        $variables["status"]            = $_POST['status'];
        $variables["notes"]             = $_POST['notes'];
        $variables["street_name"]       = $row['street_name'];
        $variables["building_name"]     = $row['building_name'];
        $variables["others"]            = $row['others'];
        $variables["city"]              = $row['city'];
        if ($_POST['status'] == 'preparing') {
            $variables["aboutstatus"]       = 'Your order has been accepted and yet this in preparing stage.';
            $variables["titlestatus"]       = '';
            $variables["status"]            = 'Preparing Stage';
        } elseif($_POST['status'] == 'u_shipping'){
            $variables["aboutstatus"]       = 'Your order is on the way, and can no longer be changed.';
            $variables["titlestatus"]       = '<p> <span> Arriving: </span> <br> <b style="color:#009900;"> '.date("F, d Y",strtotime($_POST['notes'])).' </b> </p>';
            $variables["status"]            = 'Order Shipped';
        }elseif ($_POST['status'] == 'complete') {
            $variables["aboutstatus"]       = 'Your package has been delivered!';
            $variables["titlestatus"]       = '<p> <span> Delivered to: </span> <br> <b style="color:#009900;"> Mr./Mrs./Miss '.$_POST['notes'].' </b> </p>';
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
        $mail->Subject = "Your Mochachino.store order #".implode("-",str_split($_POST['id'],4));
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