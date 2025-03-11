<?php
include 'config.php';
session_start();

// Check if user is logged in as manager
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'manager') {
    header('Location: login.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manager Dashboard - GEAR EQUIP</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <!-- Side Navigation -->
    <div class="fixed left-0 top-0 w-64 h-full bg-white shadow-lg">
        <!-- Logo Section -->
        <div class="p-4 border-b">
            <div class="flex items-center space-x-3">
                <img src="images/logo.png" alt="GEAR EQUIP" class="h-8">
                <span class="text-xl font-bold text-gray-800">GEAR EQUIP</span>
            </div>
        </div>
        
        <!-- Manager Info -->
        <div class="p-4 border-b">
            <p class="text-sm text-gray-500">Welcome,</p>
            <p class="font-semibold text-gray-800"><?php echo htmlspecialchars($_SESSION['full_name']); ?></p>
        </div>

        <!-- Navigation Links -->
        <nav class="p-4">
            <div class="space-y-2">
                <a href="manager_dashboard.php" class="block px-4 py-2 rounded-lg bg-blue-50 text-blue-700 font-medium">
                    <i class="fas fa-home mr-3"></i>Overview
                </a>
                <a href="available_machines.php" class="block px-4 py-2 rounded-lg hover:bg-gray-50 text-gray-700">
                    <i class="fas fa-cogs mr-3"></i>Available Machines
                </a>
                <a href="add_machine.php" class="block px-4 py-2 rounded-lg hover:bg-gray-50 text-gray-700">
                    <i class="fas fa-plus mr-3"></i>Add Machine
                </a>
                <a href="manage_rates.php" class="block px-4 py-2 rounded-lg hover:bg-gray-50 text-gray-700">
                    <i class="fas fa-dollar-sign mr-3"></i>Manage Rates
                </a>
                <a href="review_management.php" class="block px-4 py-2 rounded-lg hover:bg-gray-50 text-gray-700">
                    <i class="fas fa-star mr-3"></i>Review Management
                </a>
                <a href="approvals.php" class="block px-4 py-2 rounded-lg hover:bg-gray-50 text-gray-700">
                    <i class="fas fa-check-circle mr-3"></i>Approvals
                </a>
                <a href="profile_settings.php" class="block px-4 py-2 rounded-lg hover:bg-gray-50 text-gray-700">
                    <i class="fas fa-user-cog mr-3"></i>Profile Settings
                </a>
            </div>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="ml-64 p-8">
        <!-- Top Header -->
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-2xl font-bold text-gray-800">Manager Dashboard</h1>
            <a href="logout.php" class="bg-red-500 text-white px-4 py-2 rounded-lg hover:bg-red-600 transition">
                Logout
            </a>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-gray-500 text-sm font-medium">Total Machines</h3>
                <p class="text-3xl font-bold text-gray-800">
                    <?php 
                    // Sum up all available_count from machines
                    $machines_count_query = "SELECT SUM(available_count) as total_count 
                                           FROM machines 
                                           WHERE status = 'available'";  // Only count available machines
                    $result = mysqli_query($conn, $machines_count_query);
                    $row = mysqli_fetch_assoc($result);
                    echo $row['total_count'] ?? 0;  // Show 0 if null
                    ?>
                </p>
                <p class="text-sm text-gray-600 mt-1">Available for Rent</p>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-gray-500 text-sm font-medium">Total Inventory</h3>
                <p class="text-3xl font-bold text-gray-800">
                    <?php 
                    // Sum up all available_count from machines regardless of status
                    $total_inventory_query = "SELECT SUM(available_count) as total_inventory 
                                            FROM machines";
                    $result = mysqli_query($conn, $total_inventory_query);
                    $row = mysqli_fetch_assoc($result);
                    echo $row['total_inventory'] ?? 0;  // Show 0 if null
                    ?>
                </p>
                <p class="text-sm text-gray-600 mt-1">Total Machines</p>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-gray-500 text-sm font-medium">Active Rentals</h3>
                <p class="text-3xl font-bold text-gray-800">
                    <?php 
                    $result = mysqli_query($conn, "SELECT COUNT(*) as count FROM rentals WHERE status = 'active'");
                    $row = mysqli_fetch_assoc($result);
                    echo $row['count'];
                    ?>
                </p>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-gray-500 text-sm font-medium">Pending Reviews</h3>
                <p class="text-3xl font-bold text-gray-800">
                    <?php 
                    $result = mysqli_query($conn, "SELECT COUNT(*) as count FROM reviews WHERE reviewed = 0");
                    $row = mysqli_fetch_assoc($result);
                    echo $row['count'];
                    ?>
                </p>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-gray-500 text-sm font-medium">Today's Revenue</h3>
                <p class="text-3xl font-bold text-gray-800">₹
                    <?php 
                    $result = mysqli_query($conn, "SELECT SUM(total_amount) as total FROM rentals 
                                                 WHERE DATE(created_at) = CURDATE() AND status = 'active'");
                    $row = mysqli_fetch_assoc($result);
                    echo number_format($row['total'] ?? 0);
                    ?>
                </p>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="bg-white rounded-lg shadow">
            <div class="p-6 border-b">
                <h2 class="text-xl font-semibold text-gray-800">Recent Activity</h2>
            </div>
            <div class="p-6">
                <!-- Add your recent activity content here -->
                <div class="space-y-4">
                    <?php
                    // Fetch recent rentals
                    $query = "SELECT r.*, u.full_name, m.name as machine_name 
                             FROM rentals r 
                             JOIN users u ON r.user_id = u.user_id 
                             JOIN machines m ON r.machine_id = m.machine_id 
                             ORDER BY r.created_at DESC LIMIT 5";
                    $result = mysqli_query($conn, $query);
                    while($row = mysqli_fetch_assoc($result)) {
                        echo '<div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">';
                        echo '<div>';
                        echo '<p class="font-semibold">' . htmlspecialchars($row['full_name']) . '</p>';
                        echo '<p class="text-sm text-gray-600">Rented ' . htmlspecialchars($row['machine_name']) . '</p>';
                        echo '</div>';
                        echo '<span class="text-sm text-gray-500">' . date('M d, Y', strtotime($row['created_at'])) . '</span>';
                        echo '</div>';
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Font Awesome for icons -->
    <script src="https://kit.fontawesome.com/your-code.js" crossorigin="anonymous"></script>
</body>
</html>