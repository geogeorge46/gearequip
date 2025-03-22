<?php
include 'config.php';
session_start();

// Check if user is logged in as manager
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'manager') {
    header('Location: login.php');
    exit();
}

// Handle approval/rejection actions
if (isset($_POST['action']) && isset($_POST['rental_id'])) {
    $rental_id = (int)$_POST['rental_id'];
    $action = $_POST['action'];
    
    if ($action === 'approve') {
        // First get the rental details including user_id
        $get_rental = "SELECT user_id FROM rentals WHERE rental_id = ?";
        $stmt = mysqli_prepare($conn, $get_rental);
        mysqli_stmt_bind_param($stmt, "i", $rental_id);
        mysqli_stmt_execute($stmt);
        $rental_result = mysqli_stmt_get_result($stmt);
        $rental = mysqli_fetch_assoc($rental_result);

        // Update rental status to active and machine status to rented
        $update_query = "UPDATE rentals r 
                        JOIN machines m ON r.machine_id = m.machine_id 
                        SET r.status = 'active', 
                            m.status = 'rented',
                            r.updated_at = CURRENT_TIMESTAMP 
                        WHERE r.rental_id = ? 
                        AND r.status = 'pending' 
                        AND r.payment_status = 'paid'";
        
        $stmt = mysqli_prepare($conn, $update_query);
        mysqli_stmt_bind_param($stmt, "i", $rental_id);
        
        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['success'] = "Rental #$rental_id has been approved successfully.";
            
            // Add user notification with the correct user_id
            if ($rental && isset($rental['user_id'])) {
                $notification = "INSERT INTO user_notifications (user_id, message, type) 
                               VALUES (?, ?, 'rental_approved')";
                $message = "Your rental #$rental_id has been approved!";
                $stmt = mysqli_prepare($conn, $notification);
                mysqli_stmt_bind_param($stmt, "is", $rental['user_id'], $message);
                mysqli_stmt_execute($stmt);
            }
        } else {
            $_SESSION['error'] = "Error approving rental: " . mysqli_error($conn);
        }
    } elseif ($action === 'reject') {
        // First get the rental details including user_id
        $get_rental = "SELECT user_id FROM rentals WHERE rental_id = ?";
        $stmt = mysqli_prepare($conn, $get_rental);
        mysqli_stmt_bind_param($stmt, "i", $rental_id);
        mysqli_stmt_execute($stmt);
        $rental_result = mysqli_stmt_get_result($stmt);
        $rental = mysqli_fetch_assoc($rental_result);

        // Update rental status to cancelled and machine status back to available
        $update_query = "UPDATE rentals r 
                        JOIN machines m ON r.machine_id = m.machine_id 
                        SET r.status = 'cancelled', 
                            r.payment_status = 'refunded',
                            m.status = 'available',
                            r.updated_at = CURRENT_TIMESTAMP 
                        WHERE r.rental_id = ?";
        
        $stmt = mysqli_prepare($conn, $update_query);
        mysqli_stmt_bind_param($stmt, "i", $rental_id);
        
        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['success'] = "Rental #$rental_id has been rejected and marked for refund.";
            
            // Add user notification with the correct user_id
            if ($rental && isset($rental['user_id'])) {
                $notification = "INSERT INTO user_notifications (user_id, message, type) 
                               VALUES (?, ?, 'rental_rejected')";
                $message = "Your rental #$rental_id has been rejected. A refund will be processed.";
                $stmt = mysqli_prepare($conn, $notification);
                mysqli_stmt_bind_param($stmt, "is", $rental['user_id'], $message);
                mysqli_stmt_execute($stmt);
            }
        } else {
            $_SESSION['error'] = "Error rejecting rental: " . mysqli_error($conn);
        }
    }
    
    header('Location: manager_approvals.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rental Approvals - GEAR EQUIP</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .approval-card {
            transition: all 0.3s ease;
        }
        .approval-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
    </style>
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
                <a href="manager_dashboard.php" class="block px-4 py-2 rounded-lg hover:bg-gray-50 text-gray-700">
                    <i class="fas fa-home mr-3"></i>Overview
                </a>
                
                <a href="manager_approvals.php" class="block px-4 py-2 rounded-lg bg-blue-50 text-blue-700 font-medium">
                    <i class="fas fa-check-circle mr-3"></i>Rental Approvals
                </a>
            </div>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="ml-64 p-8">
        <!-- Top Header -->
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-2xl font-bold text-gray-800">Rental Approvals</h1>
            <div class="flex items-center space-x-4">
                <a href="manager_notifications.php" class="relative">
                    <svg class="w-6 h-6 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                    </svg>
                    <?php
                    // Get unread notifications count
                    $unread_query = "SELECT COUNT(*) as count FROM manager_notifications WHERE is_read = FALSE";
                    $result = mysqli_query($conn, $unread_query);
                    $unread_count = mysqli_fetch_assoc($result)['count'];
                    if($unread_count > 0) {
                        echo "<span class='absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-5 h-5 flex items-center justify-center text-xs font-bold'>$unread_count</span>";
                    }
                    ?>
                </a>
                <a href="logout.php" class="bg-red-500 text-white px-4 py-2 rounded-lg hover:bg-red-600 transition">
                    Logout
                </a>
            </div>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6">
                <?php 
                echo $_SESSION['success'];
                unset($_SESSION['success']);
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6">
                <?php 
                echo $_SESSION['error'];
                unset($_SESSION['error']);
                ?>
            </div>
        <?php endif; ?>

        <!-- Approval Cards Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php
            // Fetch pending rentals that are paid
            $query = "SELECT r.*, 
                             u.full_name, u.email,
                             m.name as machine_name, m.daily_rate,
                             m.image_url
                      FROM rentals r 
                      JOIN users u ON r.user_id = u.user_id 
                      JOIN machines m ON r.machine_id = m.machine_id 
                      WHERE r.status = 'pending' 
                      AND r.payment_status = 'paid' 
                      ORDER BY r.created_at DESC";
            
            $result = mysqli_query($conn, $query);
            
            if (mysqli_num_rows($result) > 0):
                while ($rental = mysqli_fetch_assoc($result)):
                    $rental_days = (strtotime($rental['end_date']) - strtotime($rental['start_date'])) / (60 * 60 * 24);
            ?>
                <div class="approval-card bg-white rounded-lg shadow-md overflow-hidden">
                    <img src="<?php echo htmlspecialchars($rental['image_url']); ?>" 
                         alt="<?php echo htmlspecialchars($rental['machine_name']); ?>"
                         class="w-full h-48 object-cover">
                    
                    <div class="p-6">
                        <div class="flex justify-between items-start mb-4">
                            <div>
                                <h3 class="text-xl font-semibold text-gray-800">
                                    <?php echo htmlspecialchars($rental['machine_name']); ?>
                                </h3>
                                <p class="text-gray-600">
                                    Rental #<?php echo $rental['rental_id']; ?>
                                </p>
                            </div>
                            <span class="px-3 py-1 bg-yellow-100 text-yellow-800 rounded-full text-sm">
                                Pending
                            </span>
                        </div>

                        <div class="space-y-3 mb-6">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Customer:</span>
                                <span class="text-gray-800 font-medium">
                                    <?php echo htmlspecialchars($rental['full_name']); ?>
                                </span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Duration:</span>
                                <span class="text-gray-800">
                                    <?php echo $rental_days; ?> days
                                </span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Start Date:</span>
                                <span class="text-gray-800">
                                    <?php echo date('M d, Y', strtotime($rental['start_date'])); ?>
                                </span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">End Date:</span>
                                <span class="text-gray-800">
                                    <?php echo date('M d, Y', strtotime($rental['end_date'])); ?>
                                </span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Total Amount:</span>
                                <span class="text-gray-800 font-semibold">
                                    ₹<?php echo number_format($rental['total_amount'], 2); ?>
                                </span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Security Deposit:</span>
                                <span class="text-gray-800">
                                    ₹<?php echo number_format($rental['security_deposit'], 2); ?>
                                </span>
                            </div>
                        </div>

                        <div class="flex space-x-3">
                            <form method="POST" class="flex-1">
                                <input type="hidden" name="rental_id" value="<?php echo $rental['rental_id']; ?>">
                                <input type="hidden" name="action" value="approve">
                                <button type="submit" 
                                        class="w-full bg-green-500 hover:bg-green-600 text-white py-2 px-4 rounded-lg transition duration-200">
                                    Approve
                                </button>
                            </form>
                            <form method="POST" class="flex-1">
                                <input type="hidden" name="rental_id" value="<?php echo $rental['rental_id']; ?>">
                                <input type="hidden" name="action" value="reject">
                                <button type="submit" 
                                        class="w-full bg-red-500 hover:bg-red-600 text-white py-2 px-4 rounded-lg transition duration-200">
                                    Reject
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php 
                endwhile;
            else:
            ?>
                <div class="col-span-full text-center py-12">
                    <div class="text-gray-400 text-6xl mb-4">
                        <i class="fas fa-clipboard-check"></i>
                    </div>
                    <h3 class="text-xl font-medium text-gray-600">No Pending Approvals</h3>
                    <p class="text-gray-500 mt-2">All rental requests have been processed.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://kit.fontawesome.com/your-code.js" crossorigin="anonymous"></script>
</body>
</html> 