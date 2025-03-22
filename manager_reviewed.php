<?php
include 'config.php';
session_start();

// Check if user is logged in as manager
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'manager') {
    header('Location: login.php');
    exit();
}

// Handle review status updates
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['review_id'])) {
    $review_id = $_POST['review_id'];
    $status = $_POST['status'];
    $reviewed = 1; // Mark as reviewed

    $update_query = "UPDATE reviews SET status = ?, reviewed = ? WHERE review_id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("sii", $status, $reviewed, $review_id);
    $stmt->execute();
}

// Fetch all reviews with user and machine details
$reviews_query = "SELECT r.*, u.full_name as user_name, m.name as machine_name, 
                  DATE_FORMAT(r.created_at, '%M %d, %Y') as formatted_date
                  FROM reviews r
                  JOIN users u ON r.user_id = u.user_id
                  JOIN machines m ON r.machine_id = m.machine_id
                  ORDER BY r.created_at DESC";
$reviews = $conn->query($reviews_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Review Management - GEAR EQUIP</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .star-rating {
            color: #FFD700;
        }
        .review-card {
            transition: all 0.3s ease;
        }
        .review-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
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
                <!-- ... other navigation links ... -->
                <a href="manager_reviewed.php" class="block px-4 py-2 rounded-lg bg-blue-50 text-blue-700 font-medium">
                    <i class="fas fa-star mr-3"></i>Review Management
                </a>
            </div>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="ml-64 p-8">
        <!-- Top Header -->
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-2xl font-bold text-gray-800">Review Management</h1>
            <div class="flex space-x-4">
                <div class="bg-white rounded-lg shadow px-4 py-3">
                    <span class="text-sm text-gray-500">Pending Reviews:</span>
                    <span class="font-bold text-blue-600 ml-2">
                        <?php 
                        $pending = $conn->query("SELECT COUNT(*) as count FROM reviews WHERE reviewed = 0")->fetch_assoc();
                        echo $pending['count'];
                        ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- Reviews Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php while ($review = $reviews->fetch_assoc()): ?>
            <div class="review-card bg-white rounded-lg shadow-md overflow-hidden">
                <div class="p-6">
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <h3 class="font-semibold text-lg text-gray-800">
                                <?php echo htmlspecialchars($review['machine_name']); ?>
                            </h3>
                            <p class="text-sm text-gray-500">
                                by <?php echo htmlspecialchars($review['user_name']); ?>
                            </p>
                        </div>
                        <div class="star-rating flex">
                            <?php for($i = 1; $i <= 5; $i++): ?>
                                <i class="fas fa-star <?php echo $i <= $review['rating'] ? 'text-yellow-400' : 'text-gray-300'; ?>"></i>
                            <?php endfor; ?>
                        </div>
                    </div>

                    <div class="mb-4">
                        <p class="text-gray-600"><?php echo htmlspecialchars($review['comment']); ?></p>
                        <p class="text-sm text-gray-400 mt-2"><?php echo $review['formatted_date']; ?></p>
                    </div>

                    <div class="border-t pt-4">
                        <form method="POST" class="flex items-center space-x-4">
                            <input type="hidden" name="review_id" value="<?php echo $review['review_id']; ?>">
                            <select name="status" class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="pending" <?php echo $review['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="approved" <?php echo $review['status'] == 'approved' ? 'selected' : ''; ?>>Approve</option>
                                <option value="rejected" <?php echo $review['status'] == 'rejected' ? 'selected' : ''; ?>>Reject</option>
                            </select>
                            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition">
                                Update
                            </button>
                        </form>
                    </div>

                    <div class="mt-3 flex items-center">
                        <span class="px-3 py-1 rounded-full text-sm <?php 
                            echo $review['status'] == 'approved' ? 'bg-green-100 text-green-800' : 
                                ($review['status'] == 'rejected' ? 'bg-red-100 text-red-800' : 
                                'bg-yellow-100 text-yellow-800'); ?>">
                            <?php echo ucfirst($review['status']); ?>
                        </span>
                        <span class="ml-3 text-sm text-gray-500">
                            <?php echo $review['reviewed'] ? 'Reviewed' : 'Not Reviewed'; ?>
                        </span>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    </div>

    <script>
        // Add any JavaScript functionality here
        document.addEventListener('DOMContentLoaded', function() {
            // Auto-hide success messages after 3 seconds
            setTimeout(function() {
                const alerts = document.querySelectorAll('.alert');
                alerts.forEach(alert => {
                    alert.style.display = 'none';
                });
            }, 3000);
        });
    </script>
</body>
</html> 