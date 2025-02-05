<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require 'includes/PHPMailer/src/Exception.php';
require 'includes/PHPMailer/src/PHPMailer.php';
require 'includes/PHPMailer/src/SMTP.php';

// Test email parameters
$testEmail = 'geogeorge24680@gmail.com'; // Replace with the email where you want to receive the test
$testName = 'Test User';

try {
    $mail = new PHPMailer(true);
    $mail->SMTPDebug = 2; // Enable verbose debug output
    
    // Server settings
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'geogeorge24680@gmail.com';
    $mail->Password = 'nemr ruqm mtik bhfv';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;

    // Recipients
    $mail->setFrom('geogeorge24680@gmail.com', 'GEAR EQUIP');
    $mail->addAddress($testEmail, $testName);

    // Content
    $mail->isHTML(true);
    $mail->Subject = 'Test Email from GEAR EQUIP';
    $mail->Body = 'This is a test email from GEAR EQUIP. If you receive this, the email system is working!';

    $mail->send();
    echo "\nTest email sent successfully to $testEmail!";
} catch (Exception $e) {
    echo "\nError sending test email: " . $e->getMessage();
}
