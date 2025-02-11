<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config.php';
require_once 'includes/mail_helper.php';

$message = '';
$message_type = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = mysqli_real_escape_string($conn, trim($_POST['email']));
    
    // Check if email exists
    $stmt = mysqli_prepare($conn, "SELECT user_id, full_name FROM users WHERE email = ?");
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($user = mysqli_fetch_assoc($result)) {
        // Generate unique token
        $token = bin2hex(random_bytes(32));
        $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        // Delete any existing tokens for this user
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
                sendPasswordResetEmail($email, $user['full_name'], $reset_link);
                $message = "Password reset instructions have been sent to your email address.";
                $message_type = 'success';
            } catch (Exception $e) {
                $message = "Error sending password reset email. Please try again later.";
                $message_type = 'error';
                // Log the error for administrators
                error_log("Password reset email error: " . $e->getMessage());
            }
        } else {
            $message = "Error generating reset token. Please try again.";
            $message_type = 'error';
        }
    } else {
        // Don't reveal if email exists or not for security
        $message = "If the email exists in our system, you will receive a password reset link.";
        $message_type = 'info';
    }
    mysqli_stmt_close($stmt);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - GEAR EQUIP</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 15px 30px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 800px;
            display: flex;
            overflow: hidden;
        }

        .login-image {
            flex: 1;
            background: linear-gradient(rgba(0,0,0,0.7), rgba(0,0,0,0.7)), url('images/machinery-bg.jpg') center/cover;
            padding: 40px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            color: white;
            position: relative;
        }

        .login-form {
            flex: 1;
            padding: 40px;
            background: white;
        }

        .form-title {
            font-size: 28px;
            color: #333;
            margin-bottom: 30px;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            color: #666;
            margin-bottom: 8px;
        }

        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s;
        }

        .form-group input:focus {
            border-color: #4a90e2;
            outline: none;
        }

        .submit-btn {
            width: 100%;
            padding: 14px;
            background: #4a90e2;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s;
            margin-bottom: 15px;
        }

        .submit-btn:hover {
            background: #357abd;
        }

        .message {
            margin-bottom: 20px;
            padding: 10px;
            border-radius: 5px;
            text-align: center;
        }

        .message.error {
            color: #e74c3c;
            background: #fdf0ef;
        }

        .message.success {
            color: #27ae60;
            background: #edf7ed;
        }

        .message.info {
            color: #2980b9;
            background: #ebf5fb;
        }

        .back-to-login {
            text-align: center;
            margin-top: 15px;
        }

        .back-to-login a {
            color: #4a90e2;
            text-decoration: none;
            font-size: 14px;
            transition: color 0.3s;
        }

        .back-to-login a:hover {
            color: #357abd;
            text-decoration: underline;
        }

        @media (max-width: 768px) {
            .login-image {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-image">
            <div class="logo">
                <img src="images/logo.png" alt="GEAR EQUIP" style="height: 40px;">
            </div>
            <div class="welcome-text">
                <h2>Reset Password</h2>
                <p>Enter your email address and we'll send you a link to reset your password.</p>
            </div>
        </div>
        <div class="login-form">
            <h2 class="form-title">Forgot Password</h2>
            <?php if (!empty($message)): ?>
                <div class="message <?php echo $message_type; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" required>
                </div>
                <button type="submit" class="submit-btn">Send Reset Link</button>
                <div class="back-to-login">
                    <a href="login.php">← Back to Login</a>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Helper function to show error message
        function showError(input, message) {
            const formGroup = input.parentElement;
            const errorDiv = formGroup.querySelector('.error-text') || document.createElement('div');
            errorDiv.className = 'error-text';
            errorDiv.style.color = '#e74c3c';
            errorDiv.style.fontSize = '12px';
            errorDiv.style.marginTop = '5px';
            errorDiv.textContent = message;
            
            if (!formGroup.querySelector('.error-text')) {
                formGroup.appendChild(errorDiv);
            }
            
            input.style.borderColor = '#e74c3c';
        }

        // Helper function to show success
        function showSuccess(input) {
            const formGroup = input.parentElement;
            const errorDiv = formGroup.querySelector('.error-text');
            if (errorDiv) {
                formGroup.removeChild(errorDiv);
            }
            input.style.borderColor = '#2ecc71';
        }

        // Helper function to check if email is valid
        function isValidEmail(email) {
            const re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
            return re.test(String(email).toLowerCase());
        }

        // Validate email
        function validateEmail() {
            const emailInput = document.getElementById('email');
            const email = emailInput.value.trim();

            if (email === '') {
                showError(emailInput, 'Email is required');
                return false;
            } else if (!isValidEmail(email)) {
                showError(emailInput, 'Please enter a valid email');
                return false;
            } else {
                showSuccess(emailInput);
                return true;
            }
        }

        // Add event listener for live validation
        document.getElementById('email').addEventListener('input', validateEmail);

        // Form submission validation
        document.querySelector('form').addEventListener('submit', function(e) {
            e.preventDefault();
            if (validateEmail()) {
                this.submit();
            }
        });
    </script>
</body>
</html>
