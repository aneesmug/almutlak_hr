<?php
// use PHPMailer\PHPMailer\PHPMailer;
// use PHPMailer\PHPMailer\Exception;

// require __DIR__ . '/system/includes/vendor/phpmailer/phpmailer/src/Exception.php';
// require __DIR__ . '/system/includes/vendor/phpmailer/phpmailer/src/PHPMailer.php';
// require __DIR__ . '/system/includes/vendor/phpmailer/phpmailer/src/SMTP.php';

require './includes/db.php';
require './includes/vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$mail = new PHPMailer(true);

try {
    // ========== SMTP SETTINGS ==========
    $mail->isSMTP();
    $mail->Host = 'smtp.office365.com'; // Use settings
    $mail->SMTPAuth = true;
    $mail->Username = 'noreply@almutlak.com'; // Use settings
    $mail->Password = 'HO@66887'; // Use settings
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // Use settings (e.g., PHPMailer::ENCRYPTION_STARTTLS)
    $mail->Port = 587; // Use settings
    $mail->CharSet = 'UTF-8';
    // $mail->Host = get_setting($conDB, 'smtp_host'); // Use settings
    // $mail->SMTPAuth = true;
    // $mail->Username = get_setting($conDB, 'smtp_user'); // Use settings
    // $mail->Password = get_setting($conDB, 'smtp_pass'); // Use settings
    // $mail->SMTPSecure = get_setting($conDB, 'smtp_secure'); // Use settings (e.g., PHPMailer::ENCRYPTION_STARTTLS)
    // $mail->Port = get_setting($conDB, 'smtp_port'); // Use settings
    // $mail->CharSet = 'UTF-8';


    // ========== SENDER & RECIPIENT ==========
    $toEmail   = 'a.afzal@almutlak.com';
    $mail->setFrom('noreply@almutlak.com', 'Human Resource System'); // Use settings
    $mail->addAddress($toEmail ?? 'a.afzal@almutlak.com');

    // ========== MESSAGE CONTENT ==========
    $subject = 'Test Email — PHPMailer Fix';
    $body    = '<h3>This is a test email</h3><p>No deprecation warnings should appear now.</p>';

    $mail->isHTML(true);
    $mail->Subject = $subject ?? '';
    $mail->Body    = $body ?? '';
    $mail->AltBody = strip_tags($body ?? '');

    // ========== FINAL NULL-SAFE PATCH ==========
    $mail->Subject  = isset($mail->Subject) && $mail->Subject !== null ? (string)$mail->Subject : '';
    $mail->Body     = isset($mail->Body) && $mail->Body !== null ? (string)$mail->Body : '';
    $mail->AltBody  = isset($mail->AltBody) && $mail->AltBody !== null ? (string)$mail->AltBody : '';
    $mail->FromName = isset($mail->FromName) && $mail->FromName !== null ? (string)$mail->FromName : '';
    $mail->From     = isset($mail->From) && $mail->From !== null ? (string)$mail->From : '';
    // ===========================================

    // SEND MAIL
    $mail->send();
    echo "✅ Test email sent successfully. No deprecated warnings!";
} catch (Exception $e) {
    echo "❌ Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
}
