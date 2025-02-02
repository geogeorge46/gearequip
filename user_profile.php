<?php
// Include database connection
include 'config.php';

// Start the session for user management
session_start();

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Fetch user data
$user_id = $_SESSION['user_id'];
$sql = "SELECT * FROM users WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile - GEAR EQUIP</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="./styles.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <!-- Reuse the same navigation from index.php -->
    <nav class="navbar">
        <!-- ... existing navigation code ... -->
    </nav>

    <div class="max-w-7xl mx-auto px-4 py-12">
        <h1 class="text-4xl font-extrabold text-center text-[#2c3e50] mb-12 uppercase tracking-wide">My Profile</h1>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <!-- Profile Information Card -->
            <div class="bg-white rounded-lg shadow-lg p-6">
                <div class="text-center mb-6">
                    <img src="<?php echo $user['profile_picture'] ?? 'images/default-avatar.png'; ?>" 
                         alt="Profile Picture"
                         class="w-32 h-32 rounded-full mx-auto mb-4 object-cover border-4 border-blue-500">
                    <h2 class="text-2xl font-bold text-[#2c3e50]"><?php echo htmlspecialchars($user['full_name']); ?></h2>
                    <p class="text-gray-600"><?php echo htmlspecialchars($user['email']); ?></p>
                </div>
                <div class="border-t pt-4">
                    <h3 class="font-semibold text-lg mb-2">Contact Information</h3>
                    <p class="text-gray-600 mb-2">📱 Phone: <?php echo htmlspecialchars($user['phone']); ?></p>
                    <p class="text-gray-600">📍 Location: <?php echo htmlspecialchars($user['location']); ?></p>
                </div>
                <button onclick="window.location.href='edit_profile.php'" 
                        class="w-full mt-6 bg-blue-500 hover:bg-blue-600 text-white font-semibold py-3 px-6 rounded-lg transition duration-300">
                    Edit Profile
                </button>
            </div>

            <!-- Rental History -->
            <div class="md:col-span-2 bg-white rounded-lg shadow-lg p-6">
                <h2 class="text-2xl font-bold text-[#2c3e50] mb-6">Rental History</h2>
                <div class="space-y-4">
                    <?php
                    // Fetch rental history
                    $rental_sql = "SELECT r.*, m.name as machine_name, m.image 
                                 FROM rentals r 
                                 JOIN machines m ON r.machine_id = m.machine_id 
                                 WHERE r.user_id = ? 
                                 ORDER BY r.rental_date DESC";
                    $stmt = $conn->prepare($rental_sql);
                    $stmt->bind_param("i", $user_id);
                    $stmt->execute();
                    $rentals = $stmt->get_result();

                    if ($rentals->num_rows > 0) {
                        while ($rental = $rentals->fetch_assoc()) {
                            ?>
                            <div class="flex items-center gap-4 p-4 border rounded-lg hover:shadow-md transition-shadow">
                                <img src="./images/<?php echo htmlspecialchars($rental['image']); ?>" 
                                     alt="<?php echo htmlspecialchars($rental['machine_name']); ?>"
                                     class="w-24 h-24 object-cover rounded-lg">
                                <div class="flex-1">
                                    <h3 class="text-lg font-semibold"><?php echo htmlspecialchars($rental['machine_name']); ?></h3>
                                    <p class="text-gray-600">Rented on: <?php echo date('M d, Y', strtotime($rental['rental_date'])); ?></p>
                                    <p class="text-gray-600">Status: 
                                        <span class="px-2 py-1 rounded-full text-sm font-semibold
                                            <?php echo $rental['status'] === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'; ?>">
                                            <?php echo ucfirst($rental['status']); ?>
                                        </span>
                                    </p>
                                </div>
                                <div class="text-right">
                                    <p class="text-xl font-bold text-green-600">₹<?php echo number_format($rental['total_amount']); ?></p>
                                </div>
                            </div>
                            <?php
                        }
                    } else {
                        echo '<p class="text-gray-600 text-center py-8">No rental history found.</p>';
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Reuse the same footer from index.php -->
    <footer>
        <!-- ... existing footer code ... -->
    </footer>
</body>
</html>
