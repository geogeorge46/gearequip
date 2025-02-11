<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

function sendWelcomeEmail($userEmail, $userName) {
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';  // Gmail SMTP server
        $mail->SMTPAuth = true;
        $mail->Username = 'geogeorge24680@gmail.com'; // Your Gmail address
        $mail->Password = 'nemr ruqm mtik bhfv';    // Your Gmail App Password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // Recipients
        $mail->setFrom('geogeorge24680@gmail.com', 'GEAR EQUIP'); // Use the same Gmail address as above
        $mail->addAddress($userEmail, $userName);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Welcome to GEAR EQUIP!';
        
        // Email body
        $body = '
        <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
            <div style="background-color: #4a90e2; padding: 20px; text-align: center;">
                <h1 style="color: white; margin: 0;">Welcome to GEAR EQUIP!</h1>
            </div>
            
            <div style="padding: 20px; background-color: #f9f9f9;">
                <p>Dear ' . htmlspecialchars($userName) . ',</p>
                
                <p>Thank you for registering with GEAR EQUIP! We\'re excited to have you on board.</p>
                
                <p>With your new account, you can:</p>
                <ul>
                    <li>Browse our extensive equipment catalog</li>
                    <li>Make equipment reservations</li>
                    <li>Track your rental history</li>
                    <li>Manage your profile</li>
                </ul>
                
                <div style="text-align: center; margin: 30px 0;">
                    <a href="http://localhost/MINI/GEAR_EQUIP/login.php" 
                       style="background-color: #4a90e2; color: white; padding: 12px 25px; text-decoration: none; border-radius: 5px;">
                        Login to Your Account
                    </a>
                </div>
                
                <p>If you have any questions or need assistance, please don\'t hesitate to contact our support team.</p>
                
                <p>Best regards,<br>The GEAR EQUIP Team</p>
            </div>
            
            <div style="background-color: #333; color: white; padding: 15px; text-align: center; font-size: 12px;">
                <p>&copy; ' . date('Y') . ' GEAR EQUIP. All rights reserved.</p>
            </div>
        </div>';
        
        $mail->Body = $body;
        $mail->AltBody = strip_tags(str_replace(['<br>', '</p>'], ["\n", "\n\n"], $body));

        $mail->send();
        return true;
    } catch (Exception $e) {
        throw new Exception("Email could not be sent. Mailer Error: {$mail->ErrorInfo}");
    }
}

function sendPasswordResetEmail($userEmail, $userName, $resetLink) {
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';  // Gmail SMTP server
        $mail->SMTPAuth = true;
        $mail->Username = 'geogeorge24680@gmail.com';
        $mail->Password = 'nemr ruqm mtik bhfv';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // Recipients
        $mail->setFrom('geogeorge24680@gmail.com', 'GEAR EQUIP');
        $mail->addAddress($userEmail, $userName);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Password Reset Request - GEAR EQUIP';
        
        // Email body
        $body = '
        <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
            <div style="background-color: #4a90e2; padding: 20px; text-align: center;">
                <h1 style="color: white; margin: 0;">Password Reset Request</h1>
            </div>
            
            <div style="padding: 20px; background-color: #f9f9f9;">
                <p>Dear ' . htmlspecialchars($userName) . ',</p>
                
                <p>We received a request to reset your password for your GEAR EQUIP account. Click the button below to reset your password:</p>
                
                <div style="text-align: center; margin: 30px 0;">
                    <a href="' . $resetLink . '" style="background-color: #4a90e2; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block;">Reset Password</a>
                </div>
                
                <p>This link will expire in 1 hour for security reasons.</p>
                
                <p>If you did not request a password reset, please ignore this email or contact us if you have concerns.</p>
                
                <p>Best regards,<br>GEAR EQUIP Team</p>
            </div>
            
            <div style="text-align: center; padding: 20px; color: #666; font-size: 12px;">
                <p>This is an automated email, please do not reply.</p>
            </div>
        </div>';
        
        $mail->Body = $body;
        $mail->send();
        return true;
    } catch (Exception $e) {
        throw new Exception("Password reset email could not be sent. Mailer Error: {$mail->ErrorInfo}");
    }
}
