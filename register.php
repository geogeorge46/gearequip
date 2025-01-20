<?php
session_start();
require_once 'config.php';

$registration_success = '';
$registration_error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fullName = trim($_POST['fullName']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirmPassword'];

    // Validate input
    if (empty($fullName) || empty($email) || empty($phone) || empty($password)) {
        $registration_error = "All fields are required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $registration_error = "Invalid email format";
    } elseif ($password !== $confirmPassword) {
        $registration_error = "Passwords do not match";
    } else {
        // Check if email already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        
        if ($stmt->rowCount() > 0) {
            $registration_error = "Email already exists";
        } else {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            try {
                // Insert new user
                $stmt = $conn->prepare("INSERT INTO users (full_name, email, phone, password) VALUES (?, ?, ?, ?)");
                $stmt->execute([$fullName, $email, $phone, $hashed_password]);
                
                $registration_success = "Registration successful! Please login.";
                
                // Redirect to login page after 2 seconds
                header("refresh:2;url=login.php");
            } catch(PDOException $e) {
                $registration_error = "Registration failed: " . $e->getMessage();
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
    <title>Register - GEAR EQUIP</title>
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

        .register-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 15px 30px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 800px;
            display: flex;
            overflow: hidden;
        }

        .register-image {
            flex: 1;
            background: linear-gradient(rgba(0,0,0,0.7), rgba(0,0,0,0.7)), url('images/machinery-bg.jpg') center/cover;
            padding: 40px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            color: white;
            position: relative;
        }

        .register-image::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
        }

        .register-image * {
            position: relative;
            z-index: 1;
        }

        .register-form {
            flex: 1;
            padding: 40px;
            background: white;
        }

        .form-title {
            font-size: 24px;
            color: #333;
            margin-bottom: 30px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-size: 14px;
        }

        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .form-group input:focus {
            border-color: #2196f3;
            box-shadow: 0 0 0 2px rgba(33,150,243,0.1);
            outline: none;
        }

        .error-message {
            color: #e74c3c;
            font-size: 12px;
            margin-top: 5px;
            display: none;
        }

        .register-btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(145deg, #2196f3, #1e88e5);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 20px;
        }

        .register-btn:hover {
            background: linear-gradient(145deg, #1e88e5, #1976d2);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(33,150,243,0.3);
        }

        .login-link {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
            color: #666;
        }

        .login-link a {
            color: #2196f3;
            text-decoration: none;
        }

        .login-link a:hover {
            text-decoration: underline;
        }

        @media (max-width: 768px) {
            .register-image {
                display: none;
            }
        }

        .benefit-item:hover {
            transform: translateX(5px);
            transition: transform 0.3s ease;
        }

        .testimonial {
            transition: all 0.3s ease;
        }

        .testimonial:hover {
            transform: translateY(-5px);
            background: rgba(255,255,255,0.15);
        }

        .trust-badges > div {
            transition: all 0.3s ease;
        }

        .trust-badges > div:hover {
            transform: translateY(-5px);
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-image">
            <div class="logo">
                <img src="images/logo.png" alt="GEAR EQUIP" style="height: 40px;">
            </div>
            <div class="image-content">
                <h2 style="font-size: 28px; margin-bottom: 15px;">Welcome to GEAR EQUIP</h2>
                <div class="benefits" style="margin-bottom: 30px;">
                    <div class="benefit-item" style="display: flex; align-items: center; margin-bottom: 15px;">
                        <span style="background: rgba(255,255,255,0.2); border-radius: 50%; width: 30px; height: 30px; display: flex; align-items: center; justify-content: center; margin-right: 10px;">✓</span>
                        <p>Access to Premium Equipment</p>
                    </div>
                    <div class="benefit-item" style="display: flex; align-items: center; margin-bottom: 15px;">
                        <span style="background: rgba(255,255,255,0.2); border-radius: 50%; width: 30px; height: 30px; display: flex; align-items: center; justify-content: center; margin-right: 10px;">✓</span>
                        <p>Flexible Rental Periods</p>
                    </div>
                    <div class="benefit-item" style="display: flex; align-items: center; margin-bottom: 15px;">
                        <span style="background: rgba(255,255,255,0.2); border-radius: 50%; width: 30px; height: 30px; display: flex; align-items: center; justify-content: center; margin-right: 10px;">✓</span>
                        <p>24/7 Customer Support</p>
                    </div>
                </div>
                <div class="testimonial" style="background: rgba(255,255,255,0.1); padding: 20px; border-radius: 15px; margin-bottom: 20px;">
                    <p style="font-style: italic; margin-bottom: 10px;">"GEAR EQUIP has transformed how we access construction equipment. Their service is unmatched!"</p>
                    <div style="display: flex; align-items: center;">
                        <img src="images/user-avatar.jpg" alt="User" style="width: 40px; height: 40px; border-radius: 50%; margin-right: 10px;">
                        <div>
                            <p style="font-weight: 600;">John Smith</p>
                            <p style="font-size: 12px;">Construction Manager</p>
                        </div>
                    </div>
                </div>
                <div class="trust-badges" style="display: flex; justify-content: space-between; align-items: center;">
                    <div style="text-align: center; flex: 1;">
                        <div style="font-size: 24px; font-weight: bold;">500+</div>
                        <div style="font-size: 12px;">Equipment Items</div>
                    </div>
                    <div style="text-align: center; flex: 1;">
                        <div style="font-size: 24px; font-weight: bold;">1000+</div>
                        <div style="font-size: 12px;">Happy Clients</div>
                    </div>
                    <div style="text-align: center; flex: 1;">
                        <div style="font-size: 24px; font-weight: bold;">24/7</div>
                        <div style="font-size: 12px;">Support</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="register-form">
            <h2 class="form-title">Create Account</h2>
            <form id="registrationForm" method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                <?php if (!empty($registration_error)): ?>
                    <div style="color: #e74c3c; margin-bottom: 20px; padding: 10px; background: rgba(231, 76, 60, 0.1); border-radius: 5px;">
                        <?php echo $registration_error; ?>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($registration_success)): ?>
                    <div style="color: #27ae60; margin-bottom: 20px; padding: 10px; background: rgba(39, 174, 96, 0.1); border-radius: 5px;">
                        <?php echo $registration_success; ?>
                    </div>
                <?php endif; ?>

                <div class="form-group">
                    <label for="fullName">Full Name</label>
                    <input type="text" id="fullName" name="fullName" value="<?php echo isset($_POST['fullName']) ? htmlspecialchars($_POST['fullName']) : ''; ?>" required>
                </div>
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                </div>
                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <input type="tel" id="phone" name="phone" value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>" required>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <div class="form-group">
                    <label for="confirmPassword">Confirm Password</label>
                    <input type="password" id="confirmPassword" name="confirmPassword" required>
                </div>
                <button type="submit" class="register-btn">Create Account</button>
                <div class="login-link">
                    Already have an account? <a href="login.html">Login here</a>
                </div>
            </form>
        </div>
    </div>

    <script>
        function validateForm(event) {
            event.preventDefault();
            let isValid = true;

            // Full Name validation
            const fullName = document.getElementById('fullName').value;
            if(!/^[a-zA-Z\s]{2,50}$/.test(fullName)) {
                document.getElementById('nameError').style.display = 'block';
                isValid = false;
            } else {
                document.getElementById('nameError').style.display = 'none';
            }

            // Email validation
            const email = document.getElementById('email').value;
            if(!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                document.getElementById('emailError').style.display = 'block';
                isValid = false;
            } else {
                document.getElementById('emailError').style.display = 'none';
            }

            // Phone validation
            const phone = document.getElementById('phone').value;
            if(!/^\d{10}$/.test(phone)) {
                document.getElementById('phoneError').style.display = 'block';
                isValid = false;
            } else {
                document.getElementById('phoneError').style.display = 'none';
            }

            // Password validation
            const password = document.getElementById('password').value;
            if(password.length < 8) {
                document.getElementById('passwordError').style.display = 'block';
                isValid = false;
            } else {
                document.getElementById('passwordError').style.display = 'none';
            }

            // Confirm Password validation
            const confirmPassword = document.getElementById('confirmPassword').value;
            if(password !== confirmPassword) {
                document.getElementById('confirmError').style.display = 'block';
                isValid = false;
            } else {
                document.getElementById('confirmError').style.display = 'none';
            }

            if(isValid) {
                // Form is valid, you can submit it
                alert('Registration successful!');
                // Add your form submission code here
            }

            return isValid;
        }
    </script>
</body>
</html>