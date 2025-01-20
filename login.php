<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once 'config.php';

$login_error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    try {
        if (empty($email) || empty($password)) {
            $login_error = "Please fill in all fields";
        } else {
            $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['full_name'];
                
                // Debug message
                echo "Login successful! Redirecting...";
                
                header("Location: index.php");
                exit();
            } else {
                $login_error = "Invalid email or password";
            }
        }
    } catch(PDOException $e) {
        $login_error = "Login error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - GEAR EQUIP</title>
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

        .remember-forgot {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .remember-me {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .forgot-password {
            color: #2196f3;
            text-decoration: none;
        }

        .forgot-password:hover {
            text-decoration: underline;
        }

        .login-btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(145deg, #27ae60, #219a52);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-bottom: 20px;
        }

        .login-btn:hover {
            background: linear-gradient(145deg, #219a52, #1e8449);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(39,174,96,0.3);
        }

        .register-link {
            text-align: center;
            font-size: 14px;
            color: #666;
        }

        .register-link a {
            color: #2196f3;
            text-decoration: none;
            font-weight: 500;
        }

        .register-link a:hover {
            text-decoration: underline;
        }

        .benefit-item {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
            transition: transform 0.3s ease;
        }

        .benefit-item:hover {
            transform: translateX(5px);
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
            <div class="image-content">
                <h2 style="font-size: 28px; margin-bottom: 20px;">Welcome Back!</h2>
                <div class="benefits" style="margin-bottom: 30px;">
                    <div class="benefit-item">
                        <span style="background: rgba(255,255,255,0.2); border-radius: 50%; width: 30px; height: 30px; display: flex; align-items: center; justify-content: center; margin-right: 10px;">✓</span>
                        <p>Quick Equipment Access</p>
                    </div>
                    <div class="benefit-item">
                        <span style="background: rgba(255,255,255,0.2); border-radius: 50%; width: 30px; height: 30px; display: flex; align-items: center; justify-content: center; margin-right: 10px;">✓</span>
                        <p>View Rental History</p>
                    </div>
                    <div class="benefit-item">
                        <span style="background: rgba(255,255,255,0.2); border-radius: 50%; width: 30px; height: 30px; display: flex; align-items: center; justify-content: center; margin-right: 10px;">✓</span>
                        <p>Exclusive Member Discounts</p>
                    </div>
                </div>
                <div style="background: rgba(255,255,255,0.1); padding: 20px; border-radius: 15px; margin-bottom: 20px;">
                    <p style="font-style: italic; margin-bottom: 10px;">"The best equipment rental platform I've ever used. Seamless experience every time!"</p>
                    <div style="display: flex; align-items: center;">
                        <img src="images/user-avatar.jpg" alt="User" style="width: 40px; height: 40px; border-radius: 50%; margin-right: 10px;">
                        <div>
                            <p style="font-weight: 600;">Sarah Johnson</p>
                            <p style="font-size: 12px;">Project Manager</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="login-form">
            <h2 class="form-title">Login to Your Account</h2>
            <form id="loginForm" method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                <?php if (!empty($login_error)): ?>
                    <div style="color: #e74c3c; margin-bottom: 20px; padding: 10px; background: rgba(231, 76, 60, 0.1); border-radius: 5px;">
                        <?php echo $login_error; ?>
                    </div>
                <?php endif; ?>

                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <div class="remember-forgot">
                    <label class="remember-me">
                        <input type="checkbox" name="remember">
                        <span>Remember me</span>
                    </label>
                    <a href="#" class="forgot-password">Forgot Password?</a>
                </div>
                <button type="submit" class="login-btn">Login</button>
                <div class="register-link">
                    Don't have an account? <a href="register.html">Register here</a>
                </div>
            </form>
        </div>
    </div>

    <script>
        function validateLogin(event) {
            event.preventDefault();
            
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            
            // Basic validation
            if (!email || !password) {
                alert('Please fill in all fields');
                return false;
            }
            
            if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                alert('Please enter a valid email address');
                return false;
            }
            
            // If validation passes
            alert('Login successful!');
            // Add your login logic here
            
            return false; // Prevent form submission for this example
        }
    </script>
</body>
</html>