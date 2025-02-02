<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config.php';

$registration_error = '';
$registration_success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $full_name = mysqli_real_escape_string($conn, trim($_POST['full_name']));
    $email = mysqli_real_escape_string($conn, trim($_POST['email']));
    $phone = mysqli_real_escape_string($conn, trim($_POST['phone']));
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validation
    if (empty($full_name) || empty($email) || empty($password) || empty($confirm_password)) {
        $registration_error = "Please fill in all required fields";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $registration_error = "Please enter a valid email address";
    } elseif (strlen($password) < 8) {
        $registration_error = "Password must be at least 8 characters long";
    } elseif ($password !== $confirm_password) {
        $registration_error = "Passwords do not match";
    } else {
        // Check if email already exists
        $check_email = mysqli_prepare($conn, "SELECT user_id FROM users WHERE email = ?");
        mysqli_stmt_bind_param($check_email, "s", $email);
        mysqli_stmt_execute($check_email);
        mysqli_stmt_store_result($check_email);
        
        if (mysqli_stmt_num_rows($check_email) > 0) {
            $registration_error = "This email is already registered";
        } else {
            // Insert new user
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = mysqli_prepare($conn, "INSERT INTO users (full_name, email, password, phone, role) VALUES (?, ?, ?, ?, 'user')");
            mysqli_stmt_bind_param($stmt, "ssss", $full_name, $email, $hashed_password, $phone);
            
            if (mysqli_stmt_execute($stmt)) {
                $registration_success = "Registration successful! You can now <a href='login.php' style='color: #4a90e2; text-decoration: underline;'>login</a>.";
            } else {
                $registration_error = "Registration failed. Please try again.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - GEAR EQUIP</title>
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

        .error-message {
            color: #e74c3c;
            margin-bottom: 20px;
            padding: 10px;
            background: #fdf0ef;
            border-radius: 5px;
            text-align: center;
        }

        .success-message {
            color: #27ae60;
            margin-bottom: 20px;
            padding: 10px;
            background: #edf7ed;
            border-radius: 5px;
            text-align: center;
        }

        .login-link {
            text-align: center;
            margin-top: 15px;
        }

        .login-link a {
            color: #4a90e2;
            text-decoration: none;
            font-size: 14px;
            transition: color 0.3s;
        }

        .login-link a:hover {
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
                <h2>Join GEAR EQUIP</h2>
                <p>Create an account to start renting equipment and managing your rentals.</p>
            </div>
        </div>
        <div class="login-form">
            <h2 class="form-title">Create Account</h2>
            
            <?php if (!empty($registration_error)): ?>
                <div class="error-message">
                    <?php echo htmlspecialchars($registration_error); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($registration_success)): ?>
                <div class="success-message">
                    <?php echo $registration_success; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                <div class="form-group">
                    <label for="full_name">Full Name *</label>
                    <input type="text" id="full_name" name="full_name" value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>" required>
                </div>

                <div class="form-group">
                    <label for="email">Email Address *</label>
                    <input type="email" id="email" name="email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                </div>

                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <input type="tel" id="phone" name="phone" value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label for="password">Password *</label>
                    <input type="password" id="password" name="password" required minlength="8">
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm Password *</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>

                <button type="submit" class="submit-btn">Create Account</button>
                
                <div class="login-link">
                    <span style="color: #666;">Already have an account? </span>
                    <a href="login.php">Login</a>
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

        // Helper function to check if phone is valid
        function isValidPhone(phone) {
            const re = /^[0-9]{10}$/;
            return re.test(phone);
        }

        // Validate full name
        function validateFullName() {
            const fullNameInput = document.getElementById('full_name');
            const fullName = fullNameInput.value.trim();

            if (fullName === '') {
                showError(fullNameInput, 'Full name is required');
                return false;
            } else if (fullName.length < 2) {
                showError(fullNameInput, 'Full name must be at least 2 characters');
                return false;
            } else {
                showSuccess(fullNameInput);
                return true;
            }
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

        // Validate phone
        function validatePhone() {
            const phoneInput = document.getElementById('phone');
            const phone = phoneInput.value.trim();

            if (phone !== '' && !isValidPhone(phone)) {
                showError(phoneInput, 'Please enter a valid 10-digit phone number');
                return false;
            } else {
                showSuccess(phoneInput);
                return true;
            }
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
        document.getElementById('full_name').addEventListener('input', validateFullName);
        document.getElementById('email').addEventListener('input', validateEmail);
        document.getElementById('phone').addEventListener('input', validatePhone);
        document.getElementById('password').addEventListener('input', validatePassword);
        document.getElementById('confirm_password').addEventListener('input', validateConfirmPassword);

        // Form submission validation
        document.querySelector('form').addEventListener('submit', function(e) {
            // Prevent form from submitting
            e.preventDefault();

            // Validate all fields
            const isFullNameValid = validateFullName();
            const isEmailValid = validateEmail();
            const isPhoneValid = validatePhone();
            const isPasswordValid = validatePassword();
            const isConfirmPasswordValid = validateConfirmPassword();

            // If all validations pass, submit the form
            if (isFullNameValid && isEmailValid && isPhoneValid && 
                isPasswordValid && isConfirmPasswordValid) {
                this.submit();
            }
        });

        // Add password strength indicator
        const passwordInput = document.getElementById('password');
        const strengthIndicator = document.createElement('div');
        strengthIndicator.className = 'password-strength';
        strengthIndicator.style.height = '4px';
        strengthIndicator.style.marginTop = '5px';
        strengthIndicator.style.transition = 'all 0.3s ease';
        passwordInput.parentElement.appendChild(strengthIndicator);

        passwordInput.addEventListener('input', function() {
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