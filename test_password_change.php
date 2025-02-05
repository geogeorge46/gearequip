<?php
require_once 'config.php';

// Get the latest token for testing
$stmt = mysqli_prepare($conn, 
    "SELECT t.*, u.email, u.full_name 
     FROM password_reset_tokens t 
     JOIN users u ON t.user_id = u.user_id 
     ORDER BY t.created_at DESC LIMIT 1");
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($token_data = mysqli_fetch_assoc($result)) {
    echo "Found reset token for user: " . $token_data['full_name'] . "\n";
    echo "Token: " . $token_data['token'] . "\n\n";
    
    // Simulate setting new password
    $new_password = "NewPassword123!";
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
    
    // Update password
    $update_stmt = mysqli_prepare($conn, "UPDATE users SET password = ? WHERE user_id = ?");
    mysqli_stmt_bind_param($update_stmt, "si", $hashed_password, $token_data['user_id']);
    
    if (mysqli_stmt_execute($update_stmt)) {
        // Delete used token
        $delete_stmt = mysqli_prepare($conn, "DELETE FROM password_reset_tokens WHERE token = ?");
        mysqli_stmt_bind_param($delete_stmt, "s", $token_data['token']);
        mysqli_stmt_execute($delete_stmt);
        
        echo "Password successfully changed to: $new_password\n";
        echo "Token has been invalidated\n";
        echo "\nYou can now try to login with:\n";
        echo "Email: " . $token_data['email'] . "\n";
        echo "Password: " . $new_password . "\n";
    } else {
        echo "Error updating password\n";
    }
} else {
    echo "No reset token found\n";
}
