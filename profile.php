<?php
// Include database connection
include 'config.php';

// Start the session
session_start();

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Fetch user data
$user_id = $_SESSION['user_id'];
$sql = "SELECT *, DATE_FORMAT(created_at, '%M %d, %Y') as member_since FROM users WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $update_successful = false;
    
    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Invalid email format";
    } else {
        // Check if email already exists for other users
        $check_sql = "SELECT user_id FROM users WHERE email = ? AND user_id != ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("si", $email, $user_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $error_message = "Email already exists";
        } else {
            // Initialize update query and parameters
            if (!empty($current_password) && !empty($new_password)) {
                // Verify current password
                if (!password_verify($current_password, $user['password'])) {
                    $error_message = "Current password is incorrect";
                } else {
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $update_sql = "UPDATE users SET full_name = ?, email = ?, phone = ?, address = ?, password = ? WHERE user_id = ?";
                    $update_stmt = $conn->prepare($update_sql);
                    $update_stmt->bind_param("sssssi", $full_name, $email, $phone, $address, $hashed_password, $user_id);
                    $update_successful = true;
                }
            } else {
                $update_sql = "UPDATE users SET full_name = ?, email = ?, phone = ?, address = ? WHERE user_id = ?";
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bind_param("ssssi", $full_name, $email, $phone, $address, $user_id);
                $update_successful = true;
            }
            
            // Only execute update if everything is valid
            if ($update_successful && $update_stmt && $update_stmt->execute()) {
                $success_message = "Profile updated successfully!";
                // Refresh user data
                $stmt->execute();
                $result = $stmt->get_result();
                $user = $result->fetch_assoc();
            } else if ($update_successful) {
                $error_message = "Error updating profile: " . $conn->error;
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
    <title>Profile - GEAR EQUIP</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="./styles.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-gray-50">
    <!-- Include your existing navigation here -->
    <?php include 'nav.php'; ?>

    <div class="max-w-4xl mx-auto px-4 py-16 mt-20">
        <h1 class="text-3xl font-bold text-center text-gray-800 mb-8">Profile</h1>

        <?php if (isset($success_message)): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                <?php echo $success_message; ?>
            </div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <div class="bg-white rounded-lg shadow-lg p-6">
            <form method="POST" action="profile.php" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Full Name</label>
                        <input type="text" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Phone Number</label>
                        <input type="tel" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Member Since</label>
                        <input type="text" value="<?php echo htmlspecialchars($user['member_since']); ?>" disabled
                            class="w-full px-4 py-2 bg-gray-100 border border-gray-300 rounded-lg">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Role</label>
                        <input type="text" value="<?php echo ucfirst(htmlspecialchars($user['role'] ?? 'user')); ?>" disabled
                            class="w-full px-4 py-2 bg-gray-100 border border-gray-300 rounded-lg">
                    </div>
                </div>

                <div class="mt-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Address</label>
                    <textarea name="address" rows="3" 
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    ><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                </div>

                <div class="border-t border-gray-200 pt-6 mt-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Change Password</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Current Password</label>
                            <input type="password" name="current_password"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">New Password</label>
                            <input type="password" name="new_password"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                    </div>
                </div>

                <div class="flex justify-end">
                    <button type="submit"
                        class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                        Update Profile
                    </button>
                </div>
            </form>
        </div>

        <!-- Recent Activity Section -->
        <div class="mt-8">
            <h2 class="text-2xl font-bold text-gray-800 mb-4">Recent Activity</h2>
            <div class="bg-white rounded-lg shadow-lg p-6">
                <div class="space-y-4">
                    <?php
                    // Fetch recent rentals
                    $rental_query = "SELECT r.*, m.name as machine_name, m.image_url, m.daily_rate 
                                     FROM rentals r 
                                     JOIN machines m ON r.machine_id = m.machine_id 
                                     WHERE r.user_id = ? 
                                     ORDER BY r.created_at DESC";

                    $stmt = $conn->prepare($rental_query);
                    $stmt->bind_param("i", $_SESSION['user_id']);
                    $stmt->execute();
                    $rentals = $stmt->get_result();

                    if ($rentals->num_rows > 0):
                        while ($rental = $rentals->fetch_assoc()):
                    ?>
                        <div class="flex items-center justify-between border-b pb-4">
                            <div>
                                <h3 class="font-semibold text-gray-800">Machine Rental</h3>
                                <p class="text-sm text-gray-600">
                                    Rented on <?php echo date('M j, Y', strtotime($rental['created_at'])); ?>
                                </p>
                            </div>
                            <span class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-sm">
                                ₹<?php echo number_format($rental['total_amount']); ?>
                            </span>
                        </div>
                    <?php 
                        endwhile;
                    else:
                    ?>
                        <p class="text-gray-600 text-center py-4">No recent activity</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Include your existing footer here -->
    <?php include 'footer.php'; ?>
</body>
</html>