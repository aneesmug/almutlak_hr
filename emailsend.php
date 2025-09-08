<?php
$pin = '444212';
require './includes/PHPMailerMaster/PHPMailerAutoload.php';
    $mail = new PHPMailer;
    $mail->isSMTP();                                        // Set mailer to use SMTP 
    // $mail->SMTPDebug = 0;
    $mail->Debugoutput = 'html';
    $mail->Host = 'smtp.office365.com';
    $mail->Port       = 587;
    $mail->SMTPSecure = 'tls';
    $mail->SMTPAuth = true;                                 // Enable SMTP authentication 
    $mail->Username = 'a.afzal@almutlak.com';           // SMTP username 
    $mail->Password = '@DmiN56539306@';                      // SMTP password 
    // $mail->SMTPSecure = 'ssl';                              // Enable TLS encryption, `ssl` also accepted 
    // $mail->Port = 465;
    $mail->setFrom("a.afzal@almutlak.com", "Al Mutlak System");
    $mail->addAddress("a.afzal@almutlak.com", 'Anees');
    $mail->Subject = "OTP [ ".$pin." ] from Al Mutlak System";
    $mail->Body="
        <html>
            <body>
                <h2>You are Attempting to Login in Al Mutlak System</h2>
                <p>Here is yout OTP (One-Time PIN) to verify your Identity.</p>
                <h3><b>".$pin."</b></h3>
            </body>
        </html>
    ";
    $mail->AltBody = 'This is a plain-text message body';
    if (!$mail->send()) {
        echo "Mailer Error: " . $mail->ErrorInfo;
    }
