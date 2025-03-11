<?php
// Ensure session is started if not already
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html>
<head>
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        .navbar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            background: white;
            padding: 1rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            z-index: 1000;
        }

        .nav-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 20px;
        }

        .logo {
            display: flex;
            align-items: center;
        }

        .logo img {
            height: 40px;
            width: auto;
        }

        .nav-links {
            display: flex;
            gap: 2rem;
            align-items: center;
        }

        .nav-links a {
            text-decoration: none;
            color: #2c3e50;
            font-weight: 500;
            font-size: 16px;
            transition: color 0.3s ease;
        }

        .nav-links a:hover {
            color: #27ae60;
        }

        .auth-buttons {
            display: flex;
            gap: 12px;
            align-items: center;
        }

        .login-btn, .register-btn {
            padding: 10px 24px;
            border-radius: 25px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }

        .login-btn {
            background: linear-gradient(145deg, #27ae60, #219a52);
            color: white;
            box-shadow: 0 4px 15px rgba(39, 174, 96, 0.3);
        }

        .register-btn {
            background: linear-gradient(145deg, #2196f3, #1e88e5);
            color: white;
            box-shadow: 0 4px 15px rgba(33, 150, 243, 0.3);
        }

        .login-btn:hover, .register-btn:hover {
            transform: translateY(-2px);
        }

        /* Dropdown styles */
        .dropdown-menu {
            position: absolute;
            right: 0;
            top: 100%;
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            min-width: 200px;
            z-index: 1000;
        }

        .dropdown-menu a {
            display: flex;
            align-items: center;
            padding: 12px 16px;
            color: #2c3e50;
            text-decoration: none;
            transition: background-color 0.3s ease;
        }

        .dropdown-menu a:hover {
            background-color: #f8f9fa;
        }

        .dropdown-menu svg {
            width: 16px;
            height: 16px;
            margin-right: 8px;
        }

        .dropdown-menu hr {
            margin: 8px 0;
            border: none;
            border-top: 1px solid #eee;
        }

        /* Responsive design */
        @media (max-width: 768px) {
            .nav-links {
                display: none;
            }

            .auth-buttons {
                gap: 8px;
            }

            .login-btn, .register-btn {
                padding: 8px 16px;
                font-size: 13px;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="nav-content">
            <a href="index.php" class="logo">
                <img src="images/logo.png" alt="GEAR EQUIP">
            </a>
            <div class="nav-links">
                <a href="index.php">Home</a>
                <a href="index.php#categories">Categories</a>
                <a href="machines.php">Machines</a>
                <a href="index.php#about">About</a>
                <a href="index.php#contact">Contact</a>
            </div>
            <div class="auth-buttons">
                <?php if(isset($_SESSION['user_id'])): ?>
                    <div class="relative" x-data="{ isOpen: false }">
                        <button @click="isOpen = !isOpen" 
                                class="login-btn">
                            <span>Welcome, <?php echo htmlspecialchars($_SESSION['full_name'] ?? ''); ?></span>
                            <svg class="w-4 h-4 inline-block ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                        
                        <div x-show="isOpen" 
                             @click.away="isOpen = false"
                             class="dropdown-menu">
                            <a href="dashboard.php">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                          d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                                </svg>
                                Dashboard
                            </a>
                            <a href="profile.php">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                          d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                                Profile Settings
                            </a>
                            <hr>
                            <a href="logout.php" class="text-red-600">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                          d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                </svg>
                                Logout
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="login.php" class="login-btn">Login</a>
                    <a href="register.php" class="register-btn">Register</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>
</body>
</html> 