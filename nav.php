<?php
// Ensure session is started if not already
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!-- Header/Navigation -->
<nav class="navbar">
    <div class="nav-content">
        <a href="/" class="logo">
            <img src="images/logo.png" alt="GEAR EQUIP">
        </a>
        <div class="nav-links">
            <a href="#home">Home</a>
            <a href="#categories">Categories</a>
            <a href="#machines">Machines</a>
            <a href="#about">About</a>
            <a href="#contact">Contact</a>
        </div>
        <div class="auth-buttons" style="display: flex; gap: 12px;">
            <?php if(isset($_SESSION['user_id'])): ?>
                <!-- Replace existing user info with dropdown -->
                <div class="relative" x-data="{ open: false }">
                    <button @click="open = !open" class="flex items-center space-x-2 cursor-pointer" style="padding: 10px 24px; 
                        background: linear-gradient(145deg, #27ae60, #219a52); 
                        color: white; 
                        border: none; 
                        border-radius: 25px; 
                        font-size: 14px; 
                        font-weight: 600; 
                        transition: all 0.3s ease;
                        box-shadow: 0 4px 15px rgba(39, 174, 96, 0.3);">
                        <span>Welcome, <?php echo htmlspecialchars($_SESSION['full_name'] ?? ''); ?></span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                    
                    <!-- Dropdown Menu -->
                    <div x-show="open" 
                        @click.away="open = false"
                        class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-xl z-50"
                        style="display: none;">
                        <a href="dashboard.php" class="block px-4 py-2 text-gray-800 hover:bg-gray-100">
                            <svg class="w-4 h-4 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                            </svg>
                            Dashboard
                        </a>
                        <a href="orders.php" class="block px-4 py-2 text-gray-800 hover:bg-gray-100">
                            <svg class="w-4 h-4 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                            </svg>
                            My Orders
                        </a>
                        <a href="profile.php" class="block px-4 py-2 text-gray-800 hover:bg-gray-100">
                            <svg class="w-4 h-4 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                            Profile Settings
                        </a>
                        <hr class="my-1">
                        <a href="logout.php" class="block px-4 py-2 text-red-600 hover:bg-gray-100">
                            <svg class="w-4 h-4 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                            </svg>
                            Logout
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <!-- Show login/register buttons when not logged in -->
                <a href="login.php" class="login-btn" style="padding: 10px 24px; 
                    background: linear-gradient(145deg, #27ae60, #219a52); 
                    color: white; 
                    border: none; 
                    border-radius: 25px; 
                    font-size: 14px; 
                    font-weight: 600; 
                    cursor: pointer; 
                    transition: all 0.3s ease;
                    box-shadow: 0 4px 15px rgba(39, 174, 96, 0.3);
                    text-decoration: none;
                    display: inline-block;">
                    Login
                </a>
                <a href="register.php" class="register-btn" style="padding: 10px 24px; 
                    background: linear-gradient(145deg, #2196f3, #1e88e5); 
                    color: white; 
                    border: none; 
                    border-radius: 25px; 
                    font-size: 14px; 
                    font-weight: 500; 
                    cursor: pointer; 
                    transition: all 0.3s ease;
                    box-shadow: 0 4px 15px rgba(33, 150, 243, 0.3);
                    text-decoration: none;
                    display: inline-block;">
                    Register
                </a>
            <?php endif; ?>
        </div>
    </div>
</nav> 