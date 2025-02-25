<?php
session_start();
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get user's rental history
$user_id = $_SESSION['user_id'];
$rentals_query = "SELECT r.*, m.name as machine_name, m.daily_rate 
                  FROM rentals r 
                  JOIN machines m ON r.machine_id = m.machine_id 
                  WHERE r.user_id = ? 
                  ORDER BY r.created_at DESC";

$stmt = $conn->prepare($rentals_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$rentals = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - GEAR EQUIP</title>
    <link href="styles.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body>
    <?php include 'nav.php'; ?>

    <div class="container mx-auto px-4 py-8 mt-32">
        <!-- Add Book Equipment Button -->
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-3xl font-bold">Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?></h1>
            <a href="index.php" 
               class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded-lg 
                      transition duration-300 ease-in-out transform hover:scale-105">
                Book Equipment
            </a>
        </div>

        <!-- Dashboard Summary -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white p-6 rounded-lg shadow">
                <h3 class="text-xl font-semibold mb-2">Active Rentals</h3>
                <p class="text-3xl text-blue-600">
                    <?php 
                    $active_rentals = mysqli_query($conn, 
                        "SELECT COUNT(*) as count FROM rentals 
                         WHERE user_id = $user_id AND status = 'active'");
                    $count = mysqli_fetch_assoc($active_rentals);
                    echo $count['count'];
                    ?>
                </p>
            </div>
            
            <div class="bg-white p-6 rounded-lg shadow">
                <h3 class="text-xl font-semibold mb-2">Total Spent</h3>
                <p class="text-3xl text-green-600">₹
                    <?php 
                    $total_spent = mysqli_query($conn, 
                        "SELECT SUM(total_amount) as total FROM rentals 
                         WHERE user_id = $user_id AND status != 'cancelled'");
                    $total = mysqli_fetch_assoc($total_spent);
                    echo number_format($total['total'] ?? 0, 2);
                    ?>
                </p>
            </div>
            
            <div class="bg-white p-6 rounded-lg shadow">
                <h3 class="text-xl font-semibold mb-2">Completed Rentals</h3>
                <p class="text-3xl text-purple-600">
                    <?php 
                    $completed_rentals = mysqli_query($conn, 
                        "SELECT COUNT(*) as count FROM rentals 
                         WHERE user_id = $user_id AND status = 'completed'");
                    $completed = mysqli_fetch_assoc($completed_rentals);
                    echo $completed['count'];
                    ?>
                </p>
            </div>
        </div>

        <!-- Recent Rentals -->
        <h2 class="text-2xl font-bold mb-4">Recent Rentals</h2>
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <table class="min-w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Machine</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Start Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">End Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php while ($rental = $rentals->fetch_assoc()): ?>
                    <tr>
                        <td class="px-6 py-4"><?php echo htmlspecialchars($rental['machine_name']); ?></td>
                        <td class="px-6 py-4"><?php echo date('M d, Y', strtotime($rental['start_date'])); ?></td>
                        <td class="px-6 py-4"><?php echo date('M d, Y', strtotime($rental['end_date'])); ?></td>
                        <td class="px-6 py-4">₹<?php echo number_format($rental['total_amount'], 2); ?></td>
                        <td class="px-6 py-4">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                <?php echo $rental['status'] == 'active' ? 'bg-green-100 text-green-800' : 
                                    ($rental['status'] == 'completed' ? 'bg-blue-100 text-blue-800' : 
                                    'bg-gray-100 text-gray-800'); ?>">
                                <?php echo ucfirst($rental['status']); ?>
                            </span>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>