<?php

    require_once __DIR__ . '/includes/db.php';SELECT * FROM `admin_login` WHERE `id_iqama`

    $query = mysqli_query($conDB, "SELECT * FROM admin_login WHERE username='".$username."'");

        if(mysqli_num_rows($query) == 1){

        include("./includes/avatar_select.php");

    }



    $empgetquery = mysqli_query($conDB, "SELECT * FROM employee WHERE emp_id='".$_GET['hashcode']."' && id='".$_GET['verification']."'  ");

    while ($rec = mysqli_fetch_array($empgetquery)) {

		$verification = $rec["id"];

		$hashcode = $rec["emp_id"];

		$name = $rec["name"];

		$c_email = $rec["c_email"];

	}

/*-------------------------------------------------------------------------*/

require './includes/PHPMailerMaster/PHPMailerAutoload.php';

$userwelext = ucwords((explode(" ",$name)[0])." ".(explode(" ",$name)[1]));

$mail = new PHPMailer;

$mail->isSMTP();                                        // Set mailer to use SMTP 

// $mail->SMTPDebug = 0;

$mail->Debugoutput = 'html';

$mail->Host = "mail.mochachino.store";

$mail->Port = 465;

$mail->SMTPAuth = true;

$mail->SMTPSecure = 'ssl';

$mail->Username = "req@sys.mochachino.store";

$mail->Password = "@DmiN56539306";

$mail->setFrom('info@mochachino.co', 'Mochachino Co.');

// $mail->addAddress($c_email, $userwelext);

$mail->addAddress($c_email, $userwelext);



$mail->Subject = 'New digital QR Code from Mochachino Co.';

$bodycus = $mail->msgHTML(file_get_contents('./includes/PHPMailerMaster/qr_employee.php'), dirname(__FILE__));

$bodycus = preg_replace('/\\\\/','', $bodycus);

$bodycus = str_replace('$userwelext', $userwelext, $bodycus);

$bodycus = str_replace('$verification', $verification, $bodycus);

$bodycus = str_replace('$hashcode', $hashcode, $bodycus);

$bodycus = str_replace('$name', $name, $bodycus);

// $bodycus = str_replace('$date_up', $date_up, $bodycus);

$mail->Body=$bodycus;

$mail->AltBody = 'This is a plain-text message body';

// $mail->addAttachment('phpmailer_mini.png');

if (!$mail->send()) {

    echo "Mailer Error: " . $mail->ErrorInfo;

}



header('Location: ./view_employee.php?id='.$verification);



?>