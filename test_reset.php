<?php
require_once 'config.php';
require_once 'includes/mail_helper.php';

// Test email (your email)
$test_email = 'geogeorge24680@gmail.com';

// Check if email exists
$stmt = mysqli_prepare($conn, "SELECT user_id, full_name FROM users WHERE email = ?");
mysqli_stmt_bind_param($stmt, "s", $test_email);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($user = mysqli_fetch_assoc($result)) {
    echo "Found user: " . $user['full_name'] . "\n";
    
    // Generate token
    $token = bin2hex(random_bytes(32));
    $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
    
    // Delete old tokens
    $delete_stmt = mysqli_prepare($conn, "DELETE FROM password_reset_tokens WHERE user_id = ?");
    mysqli_stmt_bind_param($delete_stmt, "i", $user['user_id']);
    mysqli_stmt_execute($delete_stmt);
    
    // Insert new token
    $insert_stmt = mysqli_prepare($conn, "INSERT INTO password_reset_tokens (user_id, token, expiry) VALUES (?, ?, ?)");
    mysqli_stmt_bind_param($insert_stmt, "iss", $user['user_id'], $token, $expiry);
    
    if (mysqli_stmt_execute($insert_stmt)) {
        // Create reset link
        $reset_link = "http://" . $_SERVER['HTTP_HOST'] . 
                     dirname($_SERVER['PHP_SELF']) . 
                     "/reset_password.php?token=" . $token;
        
        try {
            // Send password reset email
            sendPasswordResetEmail($test_email, $user['full_name'], $reset_link);
            echo "Success: Password reset email sent!\n";
            echo "Check your email: $test_email\n";
        } catch (Exception $e) {
            echo "Error sending email: " . $e->getMessage() . "\n";
        }
    } else {
        echo "Error creating reset token\n";
    }
} else {
    echo "Email not found in database\n";
}
