<?php
include 'config.php';
session_start();

// Check if user is logged in and is a manager
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'manager') {
    header('Location: login.php');
    exit();
}

// Fetch categories
$categories_query = "SELECT * FROM categories";
$categories = $conn->query($categories_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manager Dashboard - GEAR EQUIP</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
<header class="bg-white shadow-md fixed top-0 left-0 right-0 z-50">
    <div class="max-w-7xl mx-auto px-4 py-3">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4">
                <img src="images/logo.png" alt="GEAR EQUIP Logo" class="h-10">
                <span class="text-xl font-bold text-gray-800">GEAR EQUIP</span>
            </div>
            <div class="flex items-center space-x-4">
                <span class="text-gray-600">Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                <a href="logout.php" class="bg-red-500 text-white px-4 py-2 rounded-lg hover:bg-red-600 transition">Logout</a>
            </div>
        </div>
    </div>
</header>
    

    <div class="max-w-7xl mx-auto px-4 py-16 mt-20">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <!-- Dashboard Stats -->
            <div class="bg-white p-6 rounded-lg shadow-md">
                <h3 class="text-lg font-semibold text-gray-800">Total Machines</h3>
                <p class="text-3xl font-bold text-blue-600">
                    <?php
                    $machines_query = "SELECT COUNT(*) as count FROM machines";
                    $result = $conn->query($machines_query);
                    echo $result ? $result->fetch_assoc()['count'] : '0';
                    ?>
                </p>
            </div>
            
            <div class="bg-white p-6 rounded-lg shadow-md">
                <h3 class="text-lg font-semibold text-gray-800">Active Rentals</h3>
                <p class="text-3xl font-bold text-green-600">
                    <?php
                    $rentals_query = "SELECT COUNT(*) as count FROM rentals WHERE status = 'active'";
                    $result = $conn->query($rentals_query);
                    echo $result ? $result->fetch_assoc()['count'] : '0';
                    ?>
                </p>
            </div>

            <div class="bg-white p-6 rounded-lg shadow-md">
                <h3 class="text-lg font-semibold text-gray-800">Pending Reviews</h3>
                <p class="text-3xl font-bold text-yellow-600">
                    <?php
                    $reviews_query = "SELECT COUNT(*) as count FROM reviews WHERE reviewed = 0";
                    $result = $conn->query($reviews_query);
                    echo $result ? $result->fetch_assoc()['count'] : '0';
                    ?>
                </p>
            </div>

            <div class="bg-white p-6 rounded-lg shadow-md">
                <h3 class="text-lg font-semibold text-gray-800">Total Revenue</h3>
                <p class="text-3xl font-bold text-purple-600">
                    ₹<?php
                    $payments_query = "SELECT SUM(amount) as total FROM payments WHERE payment_status = 'completed'";
                    $result = $conn->query($payments_query);
                    echo $result ? number_format($result->fetch_assoc()['total'], 2) : '0.00';
                    ?>
                </p>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="flex space-x-4 mb-8">
            <a href="add_machine.php" class="bg-blue-500 text-white px-6 py-2 rounded-lg hover:bg-blue-600 transition">
                Add New Machine
            </a>
            <a href="manage_rates.php" class="bg-green-500 text-white px-6 py-2 rounded-lg hover:bg-green-600 transition">
                Manage Rates
            </a>
            <a href="review_management.php" class="bg-yellow-500 text-white px-6 py-2 rounded-lg hover:bg-yellow-600 transition">
                Review Management
            </a>
            <a href="payment_tracking.php" class="bg-purple-500 text-white px-6 py-2 rounded-lg hover:bg-purple-600 transition">
                Payment Tracking
            </a>
        </div>

        <!-- Category Tabs -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="border-b border-gray-200">
                <nav class="-mb-px flex space-x-8">
                    <?php 
                    if ($categories && $categories->num_rows > 0) {
                        while($category = $categories->fetch_assoc()): 
                    ?>
                        <a href="#" class="border-b-2 border-blue-500 pb-4 px-1 text-sm font-medium text-blue-600">
                            <?php echo htmlspecialchars($category['category_name']); ?>
                        </a>
                    <?php 
                        endwhile;
                    } else {
                        echo '<p class="text-gray-600">No categories found. Add some categories to get started.</p>';
                    }
                    ?>
                </nav>
            </div>

            <!-- Machines List -->
            <div class="mt-6">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead>
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Machine</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Daily Rate</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php
                        $machines_query = "SELECT * FROM machines ORDER BY category_id, name";
                        $machines = $conn->query($machines_query);
                        if ($machines && $machines->num_rows > 0) {
                            while($machine = $machines->fetch_assoc()):
                        ?>
                        <tr>
                            <td class="px-6 py-4">
                                <div class="flex items-center">
                                    <img class="h-10 w-10 rounded-full" src="<?php echo htmlspecialchars($machine['image_url']); ?>" alt="">
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-gray-900">
                                            <?php echo htmlspecialchars($machine['name']); ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-900">₹<?php echo number_format($machine['daily_rate'], 2); ?></div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                    <?php echo $machine['status'] === 'available' ? 'bg-green-100 text-green-800' : 
                                        ($machine['status'] === 'rented' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800'); ?>">
                                    <?php echo ucfirst($machine['status']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm font-medium">
                                <a href="edit_machine.php?id=<?php echo $machine['machine_id']; ?>" class="text-blue-600 hover:text-blue-900">Edit</a>
                                <a href="delete_machine.php?id=<?php echo $machine['machine_id']; ?>" 
                                   class="ml-3 text-red-600 hover:text-red-900"
                                   onclick="return confirm('Are you sure you want to delete this machine?')">Delete</a>
                            </td>
                        </tr>
                        <?php 
                            endwhile;
                        } else {
                            echo '<tr><td colspan="4" class="px-6 py-4 text-gray-600">No machines found.</td></tr>';
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>
</body>
</html>