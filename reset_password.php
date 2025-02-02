<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config.php';

$message = '';
$message_type = '';
$valid_token = false;
$token = isset($_GET['token']) ? $_GET['token'] : '';

if (!empty($token)) {
    // Verify token and check expiry
    $stmt = mysqli_prepare($conn, 
        "SELECT t.*, u.email 
         FROM password_reset_tokens t 
         JOIN users u ON t.user_id = u.user_id 
         WHERE t.token = ? AND t.expiry > NOW()");
    mysqli_stmt_bind_param($stmt, "s", $token);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($token_data = mysqli_fetch_assoc($result)) {
        $valid_token = true;
        
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            $password = $_POST['password'];
            $confirm_password = $_POST['confirm_password'];
            
            if (strlen($password) < 8) {
                $message = "Password must be at least 8 characters long.";
                $message_type = 'error';
            } elseif ($password !== $confirm_password) {
                $message = "Passwords do not match.";
                $message_type = 'error';
            } else {
                // Update password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $update_stmt = mysqli_prepare($conn, "UPDATE users SET password = ? WHERE user_id = ?");
                mysqli_stmt_bind_param($update_stmt, "si", $hashed_password, $token_data['user_id']);
                
                if (mysqli_stmt_execute($update_stmt)) {
                    // Delete used token
                    $delete_stmt = mysqli_prepare($conn, "DELETE FROM password_reset_tokens WHERE token = ?");
                    mysqli_stmt_bind_param($delete_stmt, "s", $token);
                    mysqli_stmt_execute($delete_stmt);
                    
                    $message = "Password has been reset successfully. You can now <a href='login.php' class='text-blue-500 hover:text-blue-800'>login</a> with your new password.";
                    $message_type = 'success';
                } else {
                    $message = "Error updating password. Please try again.";
                    $message_type = 'error';
                }
            }
        }
    } else {
        $message = "Invalid or expired reset token.";
        $message_type = 'error';
    }
    mysqli_stmt_close($stmt);
} else {
    $message = "No reset token provided.";
    $message_type = 'error';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - GEAR EQUIP</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 font-[Poppins]">
    <div class="min-h-screen flex items-center justify-center">
        <div class="bg-white p-8 rounded-lg shadow-lg w-96">
            <div class="text-center mb-8">
                <img src="images/logo.png" alt="GEAR EQUIP" class="h-12 mx-auto mb-4">
                <h2 class="text-2xl font-bold text-gray-800">Reset Password</h2>
                <p class="text-gray-600">Enter your new password</p>
            </div>

            <?php if (!empty($message)): ?>
                <div class="<?php echo $message_type === 'error' ? 'bg-red-100 border-red-400 text-red-700' : 
                                  'bg-green-100 border-green-400 text-green-700'; ?> 
                            px-4 py-3 rounded mb-4 border">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <?php if ($valid_token): ?>
                <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . '?token=' . htmlspecialchars($token); ?>">
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="password">
                            New Password
                        </label>
                        <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                               id="password"
                               type="password"
                               name="password"
                               required
                               minlength="8">
                        <div class="password-strength" style="height: 4px; margin-top: 5px; transition: all 0.3s ease;"></div>
                        <p class="text-gray-600 text-xs mt-1">Must be at least 8 characters long</p>
                    </div>
                    
                    <div class="mb-6">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="confirm_password">
                            Confirm New Password
                        </label>
                        <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                               id="confirm_password"
                               type="password"
                               name="confirm_password"
                               required>
                    </div>
                    
                    <div class="flex items-center justify-between">
                        <button class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline w-full"
                                type="submit">
                            Reset Password
                        </button>
                    </div>
                </form>
            <?php endif; ?>
            
            <div class="text-center">
                <a href="login.php" class="text-sm text-gray-600 hover:text-gray-800">
                    ← Back to Login
                </a>
            </div>
        </div>
    </div>

    <script>
        // Helper function to show error message
        function showError(input, message) {
            const formGroup = input.parentElement;
            const errorDiv = formGroup.querySelector('.error-text') || document.createElement('div');
            errorDiv.className = 'error-text text-red-500 text-sm mt-1';
            errorDiv.textContent = message;
            
            if (!formGroup.querySelector('.error-text')) {
                formGroup.appendChild(errorDiv);
            }
            
            input.classList.add('border-red-500');
            input.classList.remove('border-green-500');
        }

        // Helper function to show success
        function showSuccess(input) {
            const formGroup = input.parentElement;
            const errorDiv = formGroup.querySelector('.error-text');
            if (errorDiv) {
                formGroup.removeChild(errorDiv);
            }
            input.classList.remove('border-red-500');
            input.classList.add('border-green-500');
        }

        // Validate password
        function validatePassword() {
            const passwordInput = document.getElementById('password');
            const password = passwordInput.value.trim();

            if (password === '') {
                showError(passwordInput, 'Password is required');
                return false;
            } else if (password.length < 8) {
                showError(passwordInput, 'Password must be at least 8 characters');
                return false;
            } else {
                showSuccess(passwordInput);
                return true;
            }
        }

        // Validate confirm password
        function validateConfirmPassword() {
            const passwordInput = document.getElementById('password');
            const confirmPasswordInput = document.getElementById('confirm_password');
            const confirmPassword = confirmPasswordInput.value.trim();

            if (confirmPassword === '') {
                showError(confirmPasswordInput, 'Please confirm your password');
                return false;
            } else if (confirmPassword !== passwordInput.value.trim()) {
                showError(confirmPasswordInput, 'Passwords do not match');
                return false;
            } else {
                showSuccess(confirmPasswordInput);
                return true;
            }
        }

        // Add event listeners for live validation
        document.getElementById('password').addEventListener('input', validatePassword);
        document.getElementById('confirm_password').addEventListener('input', validateConfirmPassword);

        // Form submission validation
        document.querySelector('form')?.addEventListener('submit', function(e) {
            e.preventDefault();
            const isPasswordValid = validatePassword();
            const isConfirmPasswordValid = validateConfirmPassword();

            if (isPasswordValid && isConfirmPasswordValid) {
                this.submit();
            }
        });

        // Add password strength indicator
        const passwordInput = document.getElementById('password');
        const strengthIndicator = document.querySelector('.password-strength');

        passwordInput?.addEventListener('input', function() {
            const password = this.value;
            let strength = 0;
            
            // Length check
            if (password.length >= 8) strength += 25;
            
            // Contains number
            if (/\d/.test(password)) strength += 25;
            
            // Contains letter
            if (/[a-zA-Z]/.test(password)) strength += 25;
            
            // Contains special character
            if (/[^A-Za-z0-9]/.test(password)) strength += 25;

            strengthIndicator.style.width = strength + '%';
            
            if (strength <= 25) {
                strengthIndicator.style.backgroundColor = '#e74c3c';
            } else if (strength <= 50) {
                strengthIndicator.style.backgroundColor = '#f39c12';
            } else if (strength <= 75) {
                strengthIndicator.style.backgroundColor = '#3498db';
            } else {
                strengthIndicator.style.backgroundColor = '#2ecc71';
            }
        });
    </script>
</body>
</html>
