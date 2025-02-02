<?php
include 'config.php';
session_start();

// Check if user is logged in as admin
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header('Location: admin_login.php');
    exit();
}

// Handle role update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['user_id'])) {
    $user_id = $_POST['user_id'];
    $new_role = $_POST['role'];
    
    $update_sql = "UPDATE users SET role = ? WHERE user_id = ?";
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("si", $new_role, $user_id);
    
    if ($stmt->execute()) {
        $success_message = "User role updated successfully!";
    } else {
        $error_message = "Error updating user role.";
    }
}

// Fetch all users except current admin
$sql = "SELECT user_id, full_name, email, role, created_at FROM users WHERE user_id != ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$users = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - GEAR EQUIP</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <header class="bg-white shadow-md fixed top-0 left-0 right-0 z-50">
        <div class="max-w-7xl mx-auto px-4 py-3 flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <img src="images/logo.png" alt="GEAR EQUIP" class="h-10">
                <span class="text-xl font-bold text-gray-800">GEAR EQUIP</span>
            </div>
            <div class="flex items-center space-x-4">
                <span class="text-gray-600">Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                <a href="logout.php" class="bg-red-500 text-white px-4 py-2 rounded-lg hover:bg-red-600 transition">Logout</a>
            </div>
        </div>
    </header>

    <div class="max-w-7xl mx-auto px-4 py-16 mt-20">
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-3xl font-bold text-gray-800">Admin Dashboard</h1>
        </div>

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

        <div class="bg-white rounded-lg shadow-lg overflow-hidden">
            <div class="p-6 bg-gray-50 border-b border-gray-200">
                <h2 class="text-xl font-semibold text-gray-800">User Management</h2>
                <p class="text-gray-600">Manage user roles and permissions</p>
            </div>
            
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Current Role</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Member Since</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php while ($user = $users->fetch_assoc()): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">
                                    <?php echo htmlspecialchars($user['full_name']); ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">
                                    <?php echo htmlspecialchars($user['email']); ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                    <?php echo $user['role'] == 'admin' ? 'bg-purple-100 text-purple-800' : 
                                        ($user['role'] == 'manager' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'); ?>">
                                    <?php echo ucfirst($user['role']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo date('M j, Y', strtotime($user['created_at'])); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <form method="POST" action="" class="inline-flex items-center space-x-2">
                                    <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                                    <select name="role" class="text-sm border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                        <option value="user" <?php echo $user['role'] == 'user' ? 'selected' : ''; ?>>User</option>
                                        <option value="manager" <?php echo $user['role'] == 'manager' ? 'selected' : ''; ?>>Manager</option>
                                    </select>
                                    <button type="submit" class="bg-blue-500 text-white px-3 py-1 rounded-md text-sm hover:bg-blue-600">
                                        Update Role
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php include 'footer.php'; ?>
</body>
</html>